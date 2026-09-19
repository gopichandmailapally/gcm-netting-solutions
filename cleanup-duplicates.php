<?php
/**
 * cleanup-duplicates.php
 * Deletes all (1) duplicate files and folders created by Hostinger zip extraction.
 * IMPORTANT: Delete this script from the server after running it once!
 */

$root = __DIR__;
$deleted = [];
$errors  = [];
$dry_run = !isset($_GET['confirm']);

function rrmdir($dir) {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        is_dir($path) ? rrmdir($path) : unlink($path);
    }
    rmdir($dir);
}

// Find all (1) items in root only (non-recursive for safety — these are all at root level)
$all_items = scandir($root);
$candidates = [];
foreach ($all_items as $item) {
    if ($item === '.' || $item === '..') continue;
    // Match: name(1).ext  OR  (1).name  OR  name(1)  OR  .name(1).ext
    if (preg_match('/\(1\)/', $item)) {
        $candidates[] = $item;
    }
}

if ($_GET['confirm'] ?? false) {
    foreach ($candidates as $item) {
        $path = $root . '/' . $item;
        try {
            if (is_dir($path)) {
                rrmdir($path);
                $deleted[] = ['type' => 'folder', 'name' => $item];
            } else {
                unlink($path);
                $deleted[] = ['type' => 'file', 'name' => $item];
            }
        } catch (Throwable $e) {
            $errors[] = $item . ': ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Cleanup Duplicates</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; padding: 32px; }
h1 { font-size: 24px; font-weight: 800; margin-bottom: 6px; color: #f8fafc; }
.sub { font-size: 13px; color: #94a3b8; margin-bottom: 28px; }
.card { background: #1e293b; border-radius: 14px; padding: 22px 26px; margin-bottom: 20px; border: 1px solid #334155; }
.card h2 { font-size: 15px; font-weight: 700; margin-bottom: 14px; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; }
.item { display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 8px; margin-bottom: 6px; font-size: 13px; }
.item.folder { background: #1e3a5f; color: #93c5fd; }
.item.file   { background: #1e293b; border: 1px solid #334155; color: #cbd5e1; }
.item.done   { background: #052e16; color: #4ade80; border: 1px solid #166534; }
.item.err    { background: #450a0a; color: #fca5a5; border: 1px solid #7f1d1d; }
.badge { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 4px; text-transform: uppercase; flex-shrink: 0; }
.badge.folder { background: #1d4ed8; color: #fff; }
.badge.file   { background: #475569; color: #fff; }
.btn { display: inline-block; padding: 14px 32px; border-radius: 10px; font-size: 15px; font-weight: 700; text-decoration: none; cursor: pointer; border: none; }
.btn-danger { background: #dc2626; color: #fff; }
.btn-danger:hover { background: #b91c1c; }
.btn-safe   { background: #475569; color: #fff; margin-left: 12px; }
.warn { background: #7c2d12; border: 1px solid #c2410c; border-radius: 10px; padding: 14px 20px; font-size: 13px; color: #fed7aa; margin-bottom: 20px; }
.success { background: #052e16; border: 1px solid #166534; border-radius: 10px; padding: 14px 20px; font-size: 14px; color: #4ade80; margin-bottom: 20px; font-weight: 700; }
.empty { color: #64748b; font-size: 13px; padding: 12px; text-align: center; }
</style>
</head>
<body>

<h1>🧹 Duplicate File Cleanup</h1>
<p class="sub">Removes all <strong>(1)</strong> duplicate files and folders created by Hostinger double-extraction</p>

<?php if ($dry_run): ?>

<div class="warn">
    ⚠️ <strong>Preview Mode</strong> — nothing has been deleted yet. Review the list below, then click <strong>Delete All Duplicates</strong> to confirm.
</div>

<div class="card">
    <h2>Found <?php echo count($candidates); ?> duplicate items to delete</h2>
    <?php if (empty($candidates)): ?>
        <div class="empty">✅ No (1) duplicate files found — already clean!</div>
    <?php else: foreach ($candidates as $item):
        $is_dir = is_dir($root . '/' . $item); ?>
        <div class="item <?php echo $is_dir ? 'folder' : 'file'; ?>">
            <span class="badge <?php echo $is_dir ? 'folder' : 'file'; ?>"><?php echo $is_dir ? '📁 folder' : '📄 file'; ?></span>
            <?php echo htmlspecialchars($item); ?>
        </div>
    <?php endforeach; endif; ?>
</div>

<?php if (!empty($candidates)): ?>
<form method="get">
    <input type="hidden" name="confirm" value="1">
    <button type="submit" class="btn btn-danger">🗑️ Delete All <?php echo count($candidates); ?> Duplicates</button>
    <a href="/" class="btn btn-safe">Cancel</a>
</form>
<?php endif; ?>

<?php else: ?>

<?php if (empty($errors)): ?>
<div class="success">✅ Done! Deleted <?php echo count($deleted); ?> duplicate items successfully.</div>
<?php endif; ?>

<div class="card">
    <h2>✅ Deleted (<?php echo count($deleted); ?> items)</h2>
    <?php if (empty($deleted)): ?>
        <div class="empty">Nothing was deleted.</div>
    <?php else: foreach ($deleted as $d): ?>
        <div class="item done">
            <span class="badge <?php echo $d['type'] === 'folder' ? 'folder' : 'file'; ?>"><?php echo $d['type']; ?></span>
            <?php echo htmlspecialchars($d['name']); ?>
        </div>
    <?php endforeach; endif; ?>
</div>

<?php if (!empty($errors)): ?>
<div class="card">
    <h2>❌ Errors (<?php echo count($errors); ?>)</h2>
    <?php foreach ($errors as $e): ?>
        <div class="item err"><?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div style="margin-top:28px;background:#7c2d12;border:1px solid #c2410c;border-radius:10px;padding:16px 20px;font-size:13px;color:#fed7aa;">
    <strong>⚠️ IMPORTANT:</strong> Now delete this <code>cleanup-duplicates.php</code> file from your server using File Manager — it should not stay live on the server.
</div>

<?php endif; ?>

</body>
</html>
