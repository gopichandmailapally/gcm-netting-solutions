<?php
/**
 * Service Rate Management
 * Edits config/service-rates.json — changes apply live to estimation.php calculator
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Rate Management';

// Count entries
$total_combos    = count($sn_combos ?? []) * count($sn_ranges ?? []);
$total_cn        = count($cn_ranges ?? []) * count($cn_gaps ?? []);
$total_ig        = count($ig_thicknesses ?? []) * count($ig_gaps ?? []);
$total_ch        = count($ch_lengths ?? []) * 4;

// Load rates
$rates_file = dirname(dirname(__DIR__)) . '/config/service-rates.json';
$R = file_exists($rates_file) ? json_decode(file_get_contents($rates_file), true) : [];
$sn = $R['safety_nets']     ?? [];
$cn = $R['cricket_nets']    ?? [];
$ig = $R['invisible_grills']?? [];
$ch = $R['cloth_hangers']   ?? [];

// Safety net combos
$sn_combos = [
    ['t'=>'1.5','g'=>'30','label'=>'1.5mm / 30mm Gap'],
    ['t'=>'2',  'g'=>'40','label'=>'2mm / 40mm Gap'],
    ['t'=>'2',  'g'=>'45','label'=>'2mm / 45mm Gap'],
    ['t'=>'2',  'g'=>'50','label'=>'2mm / 50mm Gap'],
    ['t'=>'2.5','g'=>'40','label'=>'2.5mm / 40mm Gap'],
    ['t'=>'2.5','g'=>'45','label'=>'2.5mm / 45mm Gap'],
    ['t'=>'2.5','g'=>'50','label'=>'2.5mm / 50mm Gap'],
];
$sn_ranges = [
    ['key'=>'below-100', 'label'=>'Below 100 SFT', 'unit'=>'Fixed ₹'],
    ['key'=>'100-250',   'label'=>'100 – 250 SFT',  'unit'=>'/SFT'],
    ['key'=>'250-500',   'label'=>'250 – 500 SFT',  'unit'=>'/SFT'],
    ['key'=>'500-1000',  'label'=>'500 – 1000 SFT', 'unit'=>'/SFT'],
    ['key'=>'1000-5000', 'label'=>'1000 – 5000 SFT','unit'=>'/SFT'],
];

// Cricket nets
$cn_ranges = [
    ['key'=>'1000-5000',   'label'=>'1000 – 5000 SFT'],
    ['key'=>'5000-10000',  'label'=>'5000 – 10000 SFT'],
    ['key'=>'10000-15000', 'label'=>'10000 – 15000 SFT'],
    ['key'=>'15000-20000', 'label'=>'15000 – 20000 SFT'],
    ['key'=>'above-20000', 'label'=>'Above 20000 SFT'],
];
$cn_gaps = ['40'=>'40mm Gap','45'=>'45mm Gap','50'=>'50mm Gap'];

// Invisible grills
$ig_thicknesses = ['1.5'=>'1.5mm SS Wire','2'=>'2mm SS Wire','2.5'=>'2.5mm SS Wire','3'=>'3mm SS Wire'];
$ig_gaps = ['2'=>'2 Inch Gap','3'=>'3 Inch Gap'];

// Cloth hangers
$ch_lengths = ['4'=>'4 Feet','5'=>'5 Feet','6'=>'6 Feet','7'=>'7 Feet','8'=>'8 Feet'];

include '../includes/header.php';

function rv($R, ...$keys) {
    foreach ($keys as $k) {
        if (!isset($R[$k])) return 0;
        $R = $R[$k];
    }
    return is_array($R) ? 0 : $R;
}
?>

<style>
/* ── Page wrapper ───────────────────────────────────── */
.seo-page { padding: 0; }

/* ── Hero header card ───────────────────────────────── */
.seo-hero { background: white; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.07); padding: 32px 36px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; border-left: 6px solid transparent; border-image: linear-gradient(180deg,#667eea,#764ba2) 1; }
.seo-hero-left h1 { font-size: 32px; font-weight: 800; color: #1e293b; margin: 0 0 6px; }
.seo-hero-left h1 i { color: #667eea; margin-right: 10px; }
.seo-hero-left p { color: #64748b; font-size: 15px; margin: 0; }
.seo-hero-badge { background: linear-gradient(135deg,#10b981,#059669); color: white; padding: 8px 18px; border-radius: 30px; font-size: 13px; font-weight: 700; white-space: nowrap; }

/* ── Solution banner ────────────────────────────────── */
.solution-banner { background: linear-gradient(135deg, rgba(102,126,234,.08) 0%, rgba(118,75,162,.05) 100%); border: 1px solid rgba(102,126,234,.2); border-left: 5px solid #667eea; border-radius: 12px; padding: 18px 22px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 14px; }
.solution-banner .sol-icon { width: 40px; height: 40px; background: linear-gradient(135deg,#667eea,#764ba2); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; flex-shrink: 0; margin-top: 2px; }
.solution-banner strong { color: #3730a3; font-size: 14px; font-weight: 700; display: block; margin-bottom: 4px; }
.solution-banner p { color: #4338ca; font-size: 13.5px; margin: 0; line-height: 1.6; }

/* ── Stat cards ─────────────────────────────────────── */
.stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 0; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 24px; }
.stat-card { background: white; padding: 26px 22px; display: flex !important; align-items: center !important; gap: 16px !important; text-align: left !important; border-left: none !important; position: relative; transition: background .2s; }
.stat-card:hover { background: #f8faff !important; }
.stat-card + .stat-card::before { content: ''; position: absolute; left: 0; top: 16%; bottom: 16%; width: 1px; background: #e2e8f0; }
.stat-icon-wrap { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: white; flex-shrink: 0; }
.stat-icon-wrap.blue   { background: linear-gradient(135deg,#667eea,#764ba2); }
.stat-icon-wrap.green  { background: linear-gradient(135deg,#10b981,#059669); }
.stat-icon-wrap.orange { background: linear-gradient(135deg,#f59e0b,#d97706); }
.stat-icon-wrap.teal   { background: linear-gradient(135deg,#06b6d4,#0891b2); }
.stat-text-wrap { flex: 1; min-width: 0; }
.stat-card h3 { font-size: 10px !important; color: #94a3b8 !important; text-transform: uppercase !important; letter-spacing: .7px !important; font-weight: 700 !important; margin: 0 0 5px !important; }
.stat-card .value { font-size: 2rem !important; font-weight: 800 !important; color: #1e293b !important; line-height: 1.1 !important; margin: 0 !important; }
.stat-card .sub { font-size: 11px !important; color: #94a3b8 !important; margin: 3px 0 0 !important; }

/* ── Section card ───────────────────────────────────── */
.seo-section { background: white; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,.07); margin-bottom: 24px; overflow: hidden; }
.seo-section-head { padding: 22px 32px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; }
.seo-section-head .sec-num { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; color: white; flex-shrink: 0; }
.sec-num.purple { background: linear-gradient(135deg,#667eea,#764ba2); }
.sec-num.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.sec-num.amber  { background: linear-gradient(135deg,#f59e0b,#d97706); }
.sec-num.teal   { background: linear-gradient(135deg,#06b6d4,#0891b2); }
.seo-section-head h2 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0; }
.seo-section-body { padding: 28px 32px; }

/* ── Rate tables ─────────────────────────────────────── */
.rm-notice { background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 8px; padding: 12px 18px; font-size: 13px; color: #1e40af; display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
table.rm-table { width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom: 16px; }
table.rm-table thead tr { background: #f8fafc; }
table.rm-table th { padding: 12px 14px; text-align: left; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
table.rm-table td { padding: 10px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
table.rm-table tr:last-child td { border-bottom: none; }
table.rm-table tr:hover td { background: #f8faff; }
.combo-label { font-size: 12px; font-weight: 700; color: #667eea; text-transform: uppercase; letter-spacing: .5px; padding: 10px 14px 4px; }
.ri { width: 90px; padding: 7px 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; text-align: center; transition: border-color .2s; }
.ri:focus { outline: none; border-color: #667eea; background: #eef2ff; }
.ri.changed { border-color: #f59e0b; background: #fef3c7; }
.unit-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; background: #e2e8f0; color: #475569; }
.range-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; background: #ede9fe; color: #6d28d9; }

/* ── Save bar ────────────────────────────────────────── */
.save-bar { position: sticky; bottom: 0; background: white; border-top: 2px solid #e2e8f0; padding: 16px 32px; display: flex; gap: 14px; align-items: center; z-index: 10; box-shadow: 0 -4px 20px rgba(0,0,0,.08); border-radius: 0 0 18px 18px; }
.changes-pill { background: #fef3c7; color: #92400e; border-radius: 20px; padding: 6px 16px; font-size: 13px; font-weight: 700; }

/* ── Action buttons ──────────────────────────────────── */
.action-row { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 8px; }
.btn-action { display: inline-flex; align-items: center; gap: 10px; padding: 13px 28px; border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .25s; }
.btn-action:hover { transform: translateY(-3px); text-decoration: none; }
.btn-action.green  { background: linear-gradient(135deg,#10b981,#059669); color: white; box-shadow: 0 6px 20px rgba(16,185,129,.35); }
.btn-action.green:hover  { box-shadow: 0 10px 30px rgba(16,185,129,.45); }
.btn-action.gray   { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; box-shadow: none; }
.btn-action.gray:hover   { background: #e2e8f0; box-shadow: none; transform: none; }

/* ── Alert ───────────────────────────────────────────── */
.rm-alert { padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 14px; }
.rm-alert-success { background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; }
.rm-alert-error   { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }

@media(max-width:900px){ .stats-grid { grid-template-columns: repeat(2,1fr); } }
@media(max-width:560px){ .stats-grid { grid-template-columns: 1fr; } .seo-hero { flex-direction: column; } .seo-section-body { padding: 20px 18px; } }
</style>

<div class="seo-page">

<!-- Hero Header -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-rupee-sign"></i> Service Rate Management</h1>
        <p>Edit pricing rates for all services — changes apply live to the estimation calculator</p>
    </div>
    <span class="seo-hero-badge"><i class="fas fa-bolt" style="margin-right:6px;"></i>Live Pricing</span>
</div>

<!-- Solution Banner -->
<div class="solution-banner">
    <div class="sol-icon"><i class="fas fa-rupee-sign"></i></div>
    <div>
        <strong>Live Rate Control</strong>
        <p>All changes save directly to <code>config/service-rates.json</code> and update the estimation calculator on the website in real time. Edit min/max rates per area range, wire thickness, and gap size.</p>
    </div>
</div>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><i class="fas fa-shield-alt"></i></div>
        <div class="stat-text-wrap">
            <h3>Safety Net Combos</h3>
            <div class="value"><?php echo count($sn_combos); ?></div>
            <p class="sub"><?php echo count($sn_ranges); ?> area ranges each</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap green"><i class="fas fa-volleyball-ball"></i></div>
        <div class="stat-text-wrap">
            <h3>Cricket Net Ranges</h3>
            <div class="value"><?php echo count($cn_ranges); ?></div>
            <p class="sub"><?php echo count($cn_gaps); ?> gap sizes per range</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap orange"><i class="fas fa-grip-lines"></i></div>
        <div class="stat-text-wrap">
            <h3>Invisible Grill Types</h3>
            <div class="value"><?php echo count($ig_thicknesses); ?></div>
            <p class="sub"><?php echo count($ig_gaps); ?> gap options each</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap teal"><i class="fas fa-tshirt"></i></div>
        <div class="stat-text-wrap">
            <h3>Cloth Hanger Sizes</h3>
            <div class="value"><?php echo count($ch_lengths); ?></div>
            <p class="sub">Ceiling &amp; wall types</p>
        </div>
    </div>
</div>

<div id="alertBanner" style="display:none;" class="rm-alert"></div>

<!-- Section 1: Safety Nets -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num purple">1</div>
        <h2><i class="fas fa-shield-alt" style="color:#667eea;margin-right:8px;"></i>Safety Nets / Pigeon Nets <small style="font-size:14px;font-weight:500;color:#94a3b8;margin-left:6px;">(per SFT)</small></h2>
    </div>
    <div class="seo-section-body">
            <div class="rm-notice"><i class="fas fa-info-circle"></i> &ldquo;Below 100 SFT&rdquo; is a <strong>fixed total price</strong> (not per SFT). All other ranges are per SFT rates.</div>
            <?php foreach ($sn_combos as $combo): ?>
            <div class="combo-label"><i class="fas fa-layer-group" style="margin-right:5px;"></i><?php echo $combo['label']; ?></div>
            <table class="rm-table">
                <thead>
                    <tr>
                        <th style="width:34%;">Area Range</th>
                        <th style="width:18%;">Min Rate (₹)</th>
                        <th style="width:18%;">Max Rate (₹)</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sn_ranges as $rng): ?>
                    <?php
                    $min = rv($sn, $rng['key'], $combo['t'], $combo['g'], 'min');
                    $max = rv($sn, $rng['key'], $combo['t'], $combo['g'], 'max');
                    ?>
                    <tr class="rate-row"
                        data-section="safety_nets"
                        data-range="<?php echo $rng['key']; ?>"
                        data-thickness="<?php echo $combo['t']; ?>"
                        data-gap="<?php echo $combo['g']; ?>">
                        <td><span class="range-badge"><?php echo $rng['label']; ?></span></td>
                        <td><input type="number" class="ri min-rate" value="<?php echo $min; ?>" min="0" step="1" onchange="markChanged(this)"></td>
                        <td><input type="number" class="ri max-rate" value="<?php echo $max; ?>" min="0" step="1" onchange="markChanged(this)"></td>
                        <td><span class="unit-badge"><?php echo $rng['unit']; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endforeach; ?>
    </div>
</div>

<!-- Section 2: Cricket Nets -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num blue">2</div>
        <h2><i class="fas fa-volleyball-ball" style="color:#3b82f6;margin-right:8px;"></i>Cricket Nets <small style="font-size:14px;font-weight:500;color:#94a3b8;margin-left:6px;">(per SFT)</small></h2>
    </div>
    <div class="seo-section-body">
            <table class="rm-table">
                <thead>
                    <tr>
                        <th style="width:34%;">Area Range</th>
                        <?php foreach ($cn_gaps as $g => $gl): ?>
                        <th><?php echo $gl; ?> Min</th>
                        <th><?php echo $gl; ?> Max</th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cn_ranges as $rng): ?>
                    <tr>
                        <td><span class="range-badge"><?php echo $rng['label']; ?></span></td>
                        <?php foreach ($cn_gaps as $g => $gl): ?>
                        <?php
                        $min = rv($cn, $rng['key'], $g, 'min');
                        $max = rv($cn, $rng['key'], $g, 'max');
                        ?>
                        <td><input type="number" class="ri min-rate" value="<?php echo $min; ?>" min="0" step="1"
                            onchange="markChanged(this)"
                            data-section="cricket_nets" data-range="<?php echo $rng['key']; ?>" data-gap="<?php echo $g; ?>" data-field="min"></td>
                        <td><input type="number" class="ri max-rate" value="<?php echo $max; ?>" min="0" step="1"
                            onchange="markChanged(this)"
                            data-section="cricket_nets" data-range="<?php echo $rng['key']; ?>" data-gap="<?php echo $g; ?>" data-field="max"></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
    </div>
</div>

<!-- Section 3: Invisible Grills -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num amber">3</div>
        <h2><i class="fas fa-grip-lines" style="color:#f59e0b;margin-right:8px;"></i>Invisible Grills <small style="font-size:14px;font-weight:500;color:#94a3b8;margin-left:6px;">(per SFT)</small></h2>
    </div>
    <div class="seo-section-body">
            <table class="rm-table">
                <thead>
                    <tr>
                        <th style="width:30%;">SS Wire Thickness</th>
                        <?php foreach ($ig_gaps as $g => $gl): ?>
                        <th><?php echo $gl; ?> Min</th>
                        <th><?php echo $gl; ?> Max</th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ig_thicknesses as $t => $tl): ?>
                    <tr>
                        <td><span class="range-badge"><?php echo $tl; ?></span></td>
                        <?php foreach ($ig_gaps as $g => $gl): ?>
                        <?php $min = rv($ig, $t, $g, 'min'); $max = rv($ig, $t, $g, 'max'); ?>
                        <td><input type="number" class="ri min-rate" value="<?php echo $min; ?>" min="0" step="1"
                            onchange="markChanged(this)"
                            data-section="invisible_grills" data-thickness="<?php echo $t; ?>" data-gap="<?php echo $g; ?>" data-field="min"></td>
                        <td><input type="number" class="ri max-rate" value="<?php echo $max; ?>" min="0" step="1"
                            onchange="markChanged(this)"
                            data-section="invisible_grills" data-thickness="<?php echo $t; ?>" data-gap="<?php echo $g; ?>" data-field="max"></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
    </div>
</div>

<!-- Section 4: Cloth Hangers -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num teal">4</div>
        <h2><i class="fas fa-tshirt" style="color:#06b6d4;margin-right:8px;"></i>Cloth Hangers <small style="font-size:14px;font-weight:500;color:#94a3b8;margin-left:6px;">(per unit)</small></h2>
    </div>
    <div class="seo-section-body">
            <table class="rm-table">
                <thead>
                    <tr>
                        <th style="width:22%;">Length</th>
                        <th>Ceiling Min</th><th>Ceiling Max</th>
                        <th>Wall Min</th><th>Wall Max</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ch_lengths as $len => $ll): ?>
                    <?php
                    $cmin = rv($ch, 'ceiling', $len, 'min'); $cmax = rv($ch, 'ceiling', $len, 'max');
                    $wmin = rv($ch, 'wall',    $len, 'min'); $wmax = rv($ch, 'wall',    $len, 'max');
                    ?>
                    <tr>
                        <td><span class="range-badge"><?php echo $ll; ?></span></td>
                        <td><input type="number" class="ri" value="<?php echo $cmin; ?>" min="0" step="50" onchange="markChanged(this)"
                            data-section="cloth_hangers" data-type="ceiling" data-length="<?php echo $len; ?>" data-field="min"></td>
                        <td><input type="number" class="ri" value="<?php echo $cmax; ?>" min="0" step="50" onchange="markChanged(this)"
                            data-section="cloth_hangers" data-type="ceiling" data-length="<?php echo $len; ?>" data-field="max"></td>
                        <td><input type="number" class="ri" value="<?php echo $wmin; ?>" min="0" step="50" onchange="markChanged(this)"
                            data-section="cloth_hangers" data-type="wall" data-length="<?php echo $len; ?>" data-field="min"></td>
                        <td><input type="number" class="ri" value="<?php echo $wmax; ?>" min="0" step="50" onchange="markChanged(this)"
                            data-section="cloth_hangers" data-type="wall" data-length="<?php echo $len; ?>" data-field="max"></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
    </div>
</div>

<!-- Save Bar -->
<div class="seo-section">
    <div class="save-bar">
        <button class="btn-action green" onclick="saveAllRates()">
            <i class="fas fa-save"></i> Save All Changes → Live Website
        </button>
        <button class="btn-action gray" onclick="location.reload()"><i class="fas fa-undo"></i> Reset</button>
        <span class="changes-pill" id="changesPill" style="display:none;">
            <i class="fas fa-pencil-alt"></i> <span id="changesCount">0</span> changes pending
        </span>
    </div>
</div>

</div>

<script>
let _changes = 0;

function markChanged(el) {
    el.classList.add('changed');
    _changes++;
    document.getElementById('changesCount').textContent = _changes;
    document.getElementById('changesPill').style.display = '';
}

function showAlert(msg, type) {
    const b = document.getElementById('alertBanner');
    b.className = 'rm-alert rm-alert-' + type;
    b.innerHTML = '<i class="fas fa-' + (type==='success'?'check-circle':'exclamation-circle') + '"></i> ' + msg;
    b.style.display = 'flex';
    b.scrollIntoView({behavior:'smooth', block:'nearest'});
    if (type === 'success') setTimeout(() => b.style.display = 'none', 5000);
}

function saveAllRates() {
    // Build the full nested JSON structure from all inputs
    const newRates = {
        safety_nets: {},
        cricket_nets: {},
        invisible_grills: {},
        cloth_hangers: { ceiling: {}, wall: {} }
    };

    // ── Safety Nets (rows with data-section="safety_nets" on the TR)
    document.querySelectorAll('tr.rate-row[data-section="safety_nets"]').forEach(row => {
        const range = row.dataset.range;
        const t     = row.dataset.thickness;
        const g     = row.dataset.gap;
        const min   = parseFloat(row.querySelector('.min-rate').value) || 0;
        const max   = parseFloat(row.querySelector('.max-rate').value) || 0;
        if (!newRates.safety_nets[range]) newRates.safety_nets[range] = {};
        if (!newRates.safety_nets[range][t]) newRates.safety_nets[range][t] = {};
        newRates.safety_nets[range][t][g] = {min, max};
    });

    // ── Cricket Nets (inputs have data-section="cricket_nets")
    document.querySelectorAll('input[data-section="cricket_nets"]').forEach(inp => {
        const range = inp.dataset.range;
        const g     = inp.dataset.gap;
        const field = inp.dataset.field;
        if (!newRates.cricket_nets[range]) newRates.cricket_nets[range] = {};
        if (!newRates.cricket_nets[range][g]) newRates.cricket_nets[range][g] = {min:0, max:0};
        newRates.cricket_nets[range][g][field] = parseFloat(inp.value) || 0;
    });

    // ── Invisible Grills
    document.querySelectorAll('input[data-section="invisible_grills"]').forEach(inp => {
        const t     = inp.dataset.thickness;
        const g     = inp.dataset.gap;
        const field = inp.dataset.field;
        if (!newRates.invisible_grills[t]) newRates.invisible_grills[t] = {};
        if (!newRates.invisible_grills[t][g]) newRates.invisible_grills[t][g] = {min:0, max:0};
        newRates.invisible_grills[t][g][field] = parseFloat(inp.value) || 0;
    });

    // ── Cloth Hangers
    document.querySelectorAll('input[data-section="cloth_hangers"]').forEach(inp => {
        const type  = inp.dataset.type;
        const len   = inp.dataset.length;
        const field = inp.dataset.field;
        if (!newRates.cloth_hangers[type][len]) newRates.cloth_hangers[type][len] = {min:0, max:0};
        newRates.cloth_hangers[type][len][field] = parseFloat(inp.value) || 0;
    });

    fetch('../api/save-rates.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({rates: newRates})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert('✅ All rates saved! The estimation calculator on the website is now updated.', 'success');
            document.querySelectorAll('.ri.changed').forEach(el => {
                el.classList.remove('changed');
                el.style.background = '';
            });
            _changes = 0;
            document.getElementById('changesPill').style.display = 'none';
        } else {
            showAlert('❌ Error: ' + data.message, 'error');
        }
    })
    .catch(() => showAlert('❌ Network error. Please try again.', 'error'));
}
</script>

<?php include '../includes/footer.php'; ?>
