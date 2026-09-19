<?php
/**
 * Pseudo-cron v2 — visitor-triggered random daily content scheduler.
 *
 * No cron job needed. Piggybacked on real visitor traffic via track-visitor.php.
 * Each day a DIFFERENT random hour is assigned to each content type:
 *   Blogs   → random between 6 AM  – 11 AM
 *   Reviews → random between 12 PM – 5 PM
 *   FAQs    → random between 6 PM  – 10 PM
 *
 * Robustness measures:
 *   1. Lock file (60 s TTL) — prevents two concurrent visitors both firing the same generator.
 *   2. curl-first HTTP firing — most reliable method on Hostinger; fsockopen + file_get_contents fallbacks.
 *   3. ignore_user_abort(true) on generators — they keep running even after our short curl disconnects.
 *   4. Catch-up window: if a scheduled hour is missed, it fires on the next visitor (any later hour works).
 *   5. End-of-day safety net: if 11 PM and content still not generated, mark urgent and always try.
 *   6. Log file at data/pseudo-cron.log — full visibility into every trigger and result.
 */

if (!defined('GCM_INIT')) define('GCM_INIT', true);
require_once dirname(__DIR__) . '/config/config.php';

/* ══════════════════════════════════════════════════════════════════
   MAIN ENTRY POINT (called from track-visitor.php or directly)
   ════════════════════════════════════════════════════════════════ */

function _pcron_check(): array {
    $data_dir      = dirname(__DIR__) . '/data';
    $schedule_file = $data_dir . '/pseudo-cron-schedule.json';
    $lock_file     = $data_dir . '/pseudo-cron.lock';
    $today         = date('Y-m-d');
    $hour          = (int)date('G');

    /* ── Distributed lock: skip if another visitor is already inside ── */
    if (file_exists($lock_file) && (time() - @filemtime($lock_file)) < 60) {
        return ['skipped' => 'locked'];
    }
    @touch($lock_file);

    try {
        /* ── Load or create today's schedule ─────────────────────────── */
        $s = [];
        if (file_exists($schedule_file)) {
            $s = @json_decode(@file_get_contents($schedule_file), true) ?: [];
        }

        if (($s['date'] ?? '') !== $today) {
            $s = [
                'date'         => $today,
                'blog_hour'    => rand(6,  11),
                'review_hour'  => rand(12, 17),
                'faq_hour'     => rand(18, 22),
                'backup_hour'  => rand(1, 5),
                'blog_done'    => false,
                'review_done'  => false,
                'faq_done'     => false,
                'backup_done'  => false,
                'blog_fired_at'   => null,
                'review_fired_at' => null,
                'faq_fired_at'    => null,
                'backup_fired_at' => null,
            ];
            @file_put_contents($schedule_file, json_encode($s, JSON_PRETTY_PRINT));
            _pcron_log("New daily schedule: backup@{$s['backup_hour']}h blogs@{$s['blog_hour']}h reviews@{$s['review_hour']}h faqs@{$s['faq_hour']}h");
            @unlink($lock_file);
            return ['scheduled' => true, 'schedule' => $s];
        }

        /* End-of-day safety net: 11 PM = treat everything as overdue */
        $eod = ($hour >= 23);

        $fired = [];

        /* ── Blogs ──────────────────────────────────────────────────── */
        if (!$s['blog_done'] && ($hour >= $s['blog_hour'] || $eod)) {
            $s['blog_done']    = true;
            $s['blog_fired_at'] = date('H:i:s');
            _pcron_fire(SITE_URL . '/admin/api/auto-blog-generator.php?generate=1&token=' . urlencode(PSEUDO_CRON_TOKEN), 'blog');
            $fired[] = 'blog';
        }

        /* ── Reviews ────────────────────────────────────────────────── */
        if (!$s['review_done'] && ($hour >= $s['review_hour'] || $eod)) {
            $tok = _pcron_review_token();
            if ($tok) {
                $s['review_done']    = true;
                $s['review_fired_at'] = date('H:i:s');
                _pcron_fire(SITE_URL . '/admin/api/auto-review-generator.php?generate=1&token=' . urlencode($tok), 'review');
                $fired[] = 'review';
            } else {
                _pcron_log('WARN: review cron_token not found in DB — skipping reviews');
            }
        }

        /* ── FAQs ───────────────────────────────────────────────────── */
        if (!$s['faq_done'] && ($hour >= $s['faq_hour'] || $eod)) {
            $s['faq_done']    = true;
            $s['faq_fired_at'] = date('H:i:s');
            _pcron_fire(SITE_URL . '/admin/api/auto-faq-generator.php?generate=1&token=' . urlencode(PSEUDO_CRON_TOKEN), 'faq');
            $fired[] = 'faq';
        }

        /* ── Server backup (outside public_html) ───────────────────── */
        if (!$s['backup_done'] && ($hour >= (int)($s['backup_hour'] ?? 2) || $eod)) {
            $s['backup_done']     = true;
            $s['backup_fired_at'] = date('H:i:s');
            _pcron_fire(SITE_URL . '/admin/api/restore-content.php?type=server_backup&token=' . urlencode(PSEUDO_CRON_TOKEN), 'server_backup');
            $fired[] = 'server_backup';
        }

        if (!empty($fired)) {
            @file_put_contents($schedule_file, json_encode($s, JSON_PRETTY_PRINT));
        }

    } finally {
        @unlink($lock_file); /* Always release the lock */
    }

    return ['fired' => $fired ?? [], 'schedule' => $s ?? []];
}

/* ══════════════════════════════════════════════════════════════════
   FIRE — send HTTP request without waiting for the full response.
   Generators have ignore_user_abort(true) so they keep running
   even after we disconnect at 3 seconds.
   ════════════════════════════════════════════════════════════════ */

function _pcron_fire(string $url, string $label = ''): void {
    _pcron_log("Firing {$label}: {$url}");
    $fired = false;

    /* Method 1: curl (most reliable — connects, sends request, disconnects) */
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 3,   /* We disconnect after 3 s; generator keeps running */
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'GCM-PseudoCron/2.0',
            CURLOPT_HTTPHEADER     => ['X-GCM-Internal: pseudo-cron'],
        ]);
        @curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        /* CURLE_OPERATION_TIMEDOUT (28) is expected and fine */
        if (empty($err) || strpos($err, 'timed out') !== false || $code > 0) {
            _pcron_log("  curl OK (code={$code}, err=" . ($err ?: 'none') . ")");
            $fired = true;
        } else {
            _pcron_log("  curl failed: {$err}");
        }
    }

    if ($fired) return;

    /* Method 2: SSL fsockopen */
    $host  = parse_url($url, PHP_URL_HOST);
    $path  = parse_url($url, PHP_URL_PATH) ?: '/';
    $query = parse_url($url, PHP_URL_QUERY);
    $full  = $path . ($query ? '?' . $query : '');

    $fp = @fsockopen('ssl://' . $host, 443, $errno, $errstr, 5);
    if ($fp) {
        fwrite($fp, "GET {$full} HTTP/1.1\r\nHost: {$host}\r\nConnection: close\r\n\r\n");
        fclose($fp);
        _pcron_log("  fsockopen SSL OK");
        return;
    }

    /* Method 3: plain HTTP fsockopen */
    $fp = @fsockopen($host, 80, $errno, $errstr, 5);
    if ($fp) {
        fwrite($fp, "GET {$full} HTTP/1.1\r\nHost: {$host}\r\nConnection: close\r\n\r\n");
        fclose($fp);
        _pcron_log("  fsockopen HTTP OK");
        return;
    }

    /* Method 4: file_get_contents (last resort) */
    $ctx = stream_context_create([
        'http' => ['timeout' => 3],
        'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    @file_get_contents($url, false, $ctx);
    _pcron_log("  file_get_contents fallback used");
}

/* ══════════════════════════════════════════════════════════════════
   HELPERS
   ════════════════════════════════════════════════════════════════ */

function _pcron_review_token(): string {
    try {
        $db  = Database::getInstance();
        $row = $db->fetchOne("SELECT setting_value FROM review_settings WHERE setting_key='cron_token'");
        return trim($row['setting_value'] ?? '');
    } catch (\Exception $e) {
        return '';
    }
}

function _pcron_log(string $msg): void {
    $log = dirname(__DIR__) . '/data/pseudo-cron.log';
    /* Keep log under 100 KB — rotate if too large */
    if (file_exists($log) && filesize($log) > 100000) {
        @rename($log, $log . '.old');
    }
    @file_put_contents($log, date('Y-m-d H:i:s') . ' | ' . $msg . "\n", FILE_APPEND | LOCK_EX);
}

/* ── When called directly as a web endpoint (not included by track-visitor.php) */
if (!defined('PCRON_INCLUDED')) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true] + _pcron_check(), JSON_PRETTY_PRINT);
}
