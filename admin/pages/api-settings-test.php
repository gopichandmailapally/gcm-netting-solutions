<?php
/**
 * API Settings - PIN-Protected Multi-Key Management
 */
// ob_start();
// error_reporting(0);
ini_set('display_errors', 0);

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

// Belt-and-suspenders: ensure GCM_ADMIN_SESSION is always used
if (session_status() === PHP_SESSION_NONE) {
    session_name('GCM_ADMIN_SESSION');
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Session expired. Please refresh the page and log in again.']);
        exit;
    }
    header('Location: ../login.php'); exit;
}

$page_title = 'API Settings';
$db = Database::getInstance();

/* ================================================================
   AJAX ACTION HANDLERS
================================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_clean();
    header('Content-Type: application/json');

    $action = $_POST['action'];
    $csrf   = $_POST['csrf_token'] ?? '';
    $sess_csrf = $_SESSION['csrf_token'] ?? $_SESSION[defined('CSRF_TOKEN_NAME') ? CSRF_TOKEN_NAME : 'gcm_csrf_token'] ?? '';
    if (empty($csrf) || ($sess_csrf !== '' && $csrf !== $sess_csrf)) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']); exit;
    }

    /* ── Count existing keys ── */
    $key_count = (int)($db->fetchOne("SELECT COUNT(*) as c FROM gemini_api_keys")['c'] ?? 0);

    /* ── ADD KEY ── */
    if ($action === 'add_key') {
        $label   = trim($_POST['key_label'] ?? 'API Key');
        $api_key = trim($_POST['api_key'] ?? '');
        $pin     = trim($_POST['pin'] ?? '');
        $pin2    = trim($_POST['pin2'] ?? '');

        if (empty($api_key)) { echo json_encode(['success'=>false,'message'=>'API key is required.']); exit; }
        if ($key_count >= 3)  { echo json_encode(['success'=>false,'message'=>'Maximum 3 API keys allowed.']); exit; }
        if (!preg_match('/^\d{6}$/', $pin)) { echo json_encode(['success'=>false,'message'=>'PIN must be exactly 6 digits.']); exit; }
        if ($pin !== $pin2)   { echo json_encode(['success'=>false,'message'=>'PINs do not match.']); exit; }

        $is_primary = ($key_count === 0) ? 1 : 0;
        $pin_hash   = password_hash($pin, PASSWORD_BCRYPT);
        $db->execute(
            "INSERT INTO gemini_api_keys (key_label, api_key, pin_hash, is_primary) VALUES (?, ?, ?, ?)",
            [$label, $api_key, $pin_hash, $is_primary]
        );
        $new_id = $db->lastInsertId();

        if ($is_primary) _syncPrimaryToConfig($api_key);

        ob_clean(); echo json_encode(['success'=>true,'message'=>'API key added successfully!','key_id'=>$new_id,'is_primary'=>$is_primary]); exit;
    }

    /* ── VERIFY PIN ── */
    if ($action === 'verify_pin') {
        $key_id = (int)($_POST['key_id'] ?? 0);
        $pin    = trim($_POST['pin'] ?? '');
        $key    = $db->fetchOne("SELECT * FROM gemini_api_keys WHERE id = ?", [$key_id]);
        if (!$key) { echo json_encode(['success'=>false,'message'=>'Key not found.']); exit; }
        if (password_verify($pin, $key['pin_hash'])) {
            $_SESSION['pin_verified_' . $key_id] = time();
            ob_clean(); echo json_encode(['success'=>true,'api_key'=>$key['api_key'],'key_label'=>$key['key_label']]);
        } else {
            echo json_encode(['success'=>false,'message'=>'Incorrect PIN. Please try again.']);
        }
        exit;
    }

    /* ── EDIT KEY ── */
    if ($action === 'edit_key') {
        $key_id  = (int)($_POST['key_id'] ?? 0);
        $label   = trim($_POST['key_label'] ?? '');
        $api_key = trim($_POST['api_key'] ?? '');
        if (!_isPinSessionValid($key_id)) { echo json_encode(['success'=>false,'message'=>'PIN not verified.']); exit; }
        $db->execute("UPDATE gemini_api_keys SET key_label=?, api_key=?, test_status='untested', test_model=NULL, tested_at=NULL, updated_at=CURRENT_TIMESTAMP WHERE id=?", [$label, $api_key, $key_id]);
        $updated = $db->fetchOne("SELECT is_primary FROM gemini_api_keys WHERE id=?", [$key_id]);
        if ($updated['is_primary']) _syncPrimaryToConfig($api_key);
        unset($_SESSION['pin_verified_' . $key_id]);
        ob_clean(); echo json_encode(['success'=>true,'message'=>'API key updated successfully!']); exit;
    }

    /* ── DELETE KEY ── */
    if ($action === 'delete_key') {
        $key_id = (int)($_POST['key_id'] ?? 0);
        if (!_isPinSessionValid($key_id)) { echo json_encode(['success'=>false,'message'=>'PIN not verified.']); exit; }
        $key = $db->fetchOne("SELECT is_primary FROM gemini_api_keys WHERE id=?", [$key_id]);
        $db->execute("DELETE FROM gemini_api_keys WHERE id=?", [$key_id]);
        if ($key && $key['is_primary']) {
            $next = $db->fetchOne("SELECT id, api_key FROM gemini_api_keys ORDER BY id ASC LIMIT 1");
            if ($next) {
                $db->execute("UPDATE gemini_api_keys SET is_primary=1 WHERE id=?", [$next['id']]);
                _syncPrimaryToConfig($next['api_key']);
            }
        }
        unset($_SESSION['pin_verified_' . $key_id]);
        ob_clean(); echo json_encode(['success'=>true,'message'=>'API key deleted.']); exit;
    }

    /* ── SET PRIMARY ── */
    if ($action === 'set_primary') {
        $key_id = (int)($_POST['key_id'] ?? 0);
        if (!_isPinSessionValid($key_id)) { echo json_encode(['success'=>false,'message'=>'PIN not verified.']); exit; }
        $db->execute("UPDATE gemini_api_keys SET is_primary=0");
        $db->execute("UPDATE gemini_api_keys SET is_primary=1 WHERE id=?", [$key_id]);
        $key = $db->fetchOne("SELECT api_key FROM gemini_api_keys WHERE id=?", [$key_id]);
        if ($key) _syncPrimaryToConfig($key['api_key']);
        unset($_SESSION['pin_verified_' . $key_id]);
        ob_clean(); echo json_encode(['success'=>true,'message'=>'Primary key updated.']); exit;
    }

    /* ── TEST KEY ── */
    if ($action === 'test_key') {
        $key_id = (int)($_POST['key_id'] ?? 0);
        $key    = $db->fetchOne("SELECT api_key FROM gemini_api_keys WHERE id=?", [$key_id]);
        if (!$key) { echo json_encode(['success'=>false,'message'=>'Key not found.']); exit; }
        require_once '../../includes/gemini-api.php';
        try {
            $gemini = new GeminiAPI($key['api_key']);
            $result = $gemini->testConnection();
            if ($result['success']) {
                $model = $gemini->getLastUsedModel() ?: 'gemini-2.0-flash-001';
                $db->execute("UPDATE gemini_api_keys SET test_status='success', test_model=?, tested_at=CURRENT_TIMESTAMP WHERE id=?", [$model, $key_id]);
                echo json_encode(['success'=>true,'message'=>'API key is working! ✅','model'=>$model]);
            } else {
                $db->execute("UPDATE gemini_api_keys SET test_status='failed', test_model=NULL, tested_at=CURRENT_TIMESTAMP WHERE id=?", [$key_id]);
                echo json_encode(['success'=>false,'message'=>$result['message'] ?? 'Connection failed.']);
            }
        } catch (Exception $e) {
            $db->execute("UPDATE gemini_api_keys SET test_status='failed', tested_at=CURRENT_TIMESTAMP WHERE id=?", [$key_id]);
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;
    }

    /* ── FORGOT PIN (reset via admin password) ── */
    if ($action === 'reset_pin') {
        $key_id      = (int)($_POST['key_id'] ?? 0);
        $admin_pass  = trim($_POST['admin_password'] ?? '');
        $new_pin     = trim($_POST['new_pin'] ?? '');
        $new_pin2    = trim($_POST['new_pin2'] ?? '');

        $admin = $db->fetchOne("SELECT password_hash FROM admin_users WHERE username=?", [$_SESSION['admin_username'] ?? 'admin']);
        if (!$admin || !password_verify($admin_pass, $admin['password_hash'])) {
            echo json_encode(['success'=>false,'message'=>'Incorrect admin password.']); exit;
        }
        if (!preg_match('/^\d{6}$/', $new_pin)) { echo json_encode(['success'=>false,'message'=>'New PIN must be exactly 6 digits.']); exit; }
        if ($new_pin !== $new_pin2) { echo json_encode(['success'=>false,'message'=>'New PINs do not match.']); exit; }

        $db->execute("UPDATE gemini_api_keys SET pin_hash=?, updated_at=CURRENT_TIMESTAMP WHERE id=?", [password_hash($new_pin, PASSWORD_BCRYPT), $key_id]);
        echo json_encode(['success'=>true,'message'=>'PIN has been reset successfully!']); exit;
    }

    /* ── SAVE AI SETTINGS ── */
    if ($action === 'save_settings') {
        $model       = trim($_POST['gemini_model'] ?? 'gemini-2.0-flash-001');
        $timeout     = (int)($_POST['api_timeout'] ?? 30);
        $retry       = (int)($_POST['api_retry_count'] ?? 3);
        $max_tokens  = (int)($_POST['max_tokens'] ?? 4096);
        $config_file = dirname(dirname(__DIR__)) . '/config/config.php';
        if (file_exists($config_file) && is_writable($config_file)) {
            $c = file_get_contents($config_file);
            $c = preg_replace("/define\(\s*'MAX_TOKENS'\s*,\s*\d+\s*\);/", "define('MAX_TOKENS', {$max_tokens});", $c);
            file_put_contents($config_file, $c);
            echo json_encode(['success'=>true,'message'=>'AI settings saved!']); exit;
        }
        echo json_encode(['success'=>false,'message'=>'Could not write config file.']); exit;
    }

    ob_clean(); echo json_encode(['success'=>false,'message'=>'Unknown action.']); exit;
}

/* ── Helpers ── */
function _isPinSessionValid($key_id) {
    $ts = $_SESSION['pin_verified_' . $key_id] ?? 0;
    return (time() - $ts) < 300; // 5-minute window
}
function _syncPrimaryToConfig($api_key) {
    $config_file = dirname(dirname(__DIR__)) . '/config/config.php';
    if (!file_exists($config_file) || !is_writable($config_file)) return;
    $c = file_get_contents($config_file);
    $c = preg_replace("/define\(\s*'GEMINI_API_KEY'\s*,\s*'[^']*'\s*\);/", "define('GEMINI_API_KEY', '{$api_key}');", $c);
    file_put_contents($config_file, $c);
}

/* ── CSRF Token ── */
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ── Load Data ── */
$api_keys = $db->fetchAll("SELECT * FROM gemini_api_keys ORDER BY is_primary DESC, id ASC");
$primary_key = null;
foreach ($api_keys as $k) { if ($k['is_primary']) { $primary_key = $k; break; } }

include '../includes/header.php';

function mask_key($key) {
    if (strlen($key) <= 8) return str_repeat('*', strlen($key));
    return substr($key, 0, 8) . str_repeat('*', max(0, strlen($key) - 12)) . substr($key, -4);
}
?>

<div class="seo-page">

<!-- Hero -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-key"></i> API Settings</h1>
        <p>Manage Gemini API keys with PIN protection</p>
    </div>
    <div class="hero-actions">
        <?php if ($primary_key): ?>
        <button class="btn-action-hero green sm" onclick="testKey(<?php echo $primary_key['id']; ?>, true)">
            <i class="fas fa-check-circle"></i> Test Primary Key
        </button>
        <?php endif; ?>
        <a href="../dashboard.php" class="btn-action-hero gray sm"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>
</div>

<!-- Flash Messages -->
<div id="flashMsg" style="display:none;" class="flash-msg"></div>

<!-- Status Notice -->
<?php if (!$primary_key): ?>
<div class="api-notice warning">
    <div class="api-notice-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="fas fa-exclamation-triangle"></i></div>
    <div><h4>No API Key Configured!</h4><p>Add a Gemini API key below. Get a free key at <a href="https://aistudio.google.com/app/apikey" target="_blank">aistudio.google.com</a></p></div>
</div>
<?php else: ?>
<div class="api-notice <?php echo $primary_key['test_status']==='success'?'success':($primary_key['test_status']==='failed'?'error':'info'); ?>" id="primaryStatusBanner">
    <div class="api-notice-icon" style="background:<?php echo $primary_key['test_status']==='success'?'linear-gradient(135deg,#10b981,#059669)':($primary_key['test_status']==='failed'?'linear-gradient(135deg,#ef4444,#dc2626)':'linear-gradient(135deg,#3b82f6,#2563eb)'); ?>"><i class="fas <?php echo $primary_key['test_status']==='success'?'fa-check-circle':($primary_key['test_status']==='failed'?'fa-times-circle':'fa-info-circle'); ?>"></i></div>
    <span id="primaryStatusText">
        <?php if ($primary_key['test_status']==='success'): ?>✅ Primary key working — <?php echo htmlspecialchars($primary_key['test_model']??'verified');
        ?><?php elseif ($primary_key['test_status']==='failed'): ?>❌ Primary key test failed — click "Test Primary Key" to retry
        <?php else: ?>ℹ️ Primary key not tested yet — click "Test Primary Key" to verify<?php endif; ?>
    </span>
</div>
<?php endif; ?>

<!-- Section 1: API Keys -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num blue">1</div>
        <h2><i class="fas fa-shield-alt" style="color:#3b82f6;margin-right:8px;"></i>Gemini API Keys</h2>
        <span class="sec-badge"><?php echo count($api_keys); ?> / 3</span>
        <div style="margin-left:auto;">
            <?php if (count($api_keys) < 3): ?>
            <button class="btn-action-hero blue sm" onclick="openAddKeyModal()"><i class="fas fa-plus"></i> Add New Key</button>
            <?php else: ?><span class="badge-max">Max 3 keys reached</span><?php endif; ?>
        </div>
    </div>
    <div class="seo-section-body">
        <?php if (empty($api_keys)): ?>
        <div class="empty-state">
            <i class="fas fa-key"></i>
            <h3>No API Keys Added Yet</h3>
            <p>Add your first Gemini API key to enable AI features</p>
            <button class="btn-action-hero blue" onclick="openAddKeyModal()" style="margin-top:16px;"><i class="fas fa-plus"></i> Add First Key</button>
        </div>
        <?php else: ?>
        <div class="keys-grid">
            <?php foreach ($api_keys as $key): ?>
            <div class="key-card <?php echo $key['is_primary'] ? 'primary' : ''; ?>" id="keyCard<?php echo $key['id']; ?>">
                <?php if ($key['is_primary']): ?>
                <div class="primary-badge"><i class="fas fa-star"></i> PRIMARY</div>
                <?php endif; ?>
                <div class="key-card-header">
                    <div class="key-icon"><i class="fas fa-key"></i></div>
                    <div class="key-info">
                        <h4><?php echo htmlspecialchars($key['key_label']); ?></h4>
                        <code class="masked-key"><?php echo mask_key($key['api_key']); ?></code>
                    </div>
                    <div class="test-status-badge <?php echo $key['test_status']; ?>">
                        <?php if ($key['test_status']==='success'): ?><i class="fas fa-check-circle"></i> Verified
                        <?php elseif ($key['test_status']==='failed'): ?><i class="fas fa-times-circle"></i> Failed
                        <?php else: ?><i class="fas fa-question-circle"></i> Untested<?php endif; ?>
                    </div>
                </div>
                <?php if ($key['test_model']): ?>
                <div class="key-model-info"><i class="fas fa-robot"></i> <?php echo htmlspecialchars($key['test_model']); ?></div>
                <?php endif; ?>
                <div class="key-card-actions">
                    <button class="btn-action btn-test" onclick="testKey(<?php echo $key['id']; ?>)"><i class="fas fa-plug"></i> Test</button>
                    <?php if (!$key['is_primary']): ?>
                    <button class="btn-action btn-primary-set" onclick="openPinModal(<?php echo $key['id']; ?>, 'set_primary', '<?php echo htmlspecialchars($key['key_label'],ENT_QUOTES); ?>')"><i class="fas fa-star"></i> Set Primary</button>
                    <?php endif; ?>
                    <button class="btn-action btn-edit" onclick="openPinModal(<?php echo $key['id']; ?>, 'edit', '<?php echo htmlspecialchars($key['key_label'],ENT_QUOTES); ?>')"><i class="fas fa-edit"></i> Edit</button>
                    <button class="btn-action btn-delete" onclick="openPinModal(<?php echo $key['id']; ?>, 'delete', '<?php echo htmlspecialchars($key['key_label'],ENT_QUOTES); ?>')"><i class="fas fa-trash"></i> Delete</button>
                    <button class="btn-action btn-forgot" onclick="openForgotPinModal(<?php echo $key['id']; ?>, '<?php echo htmlspecialchars($key['key_label'],ENT_QUOTES); ?>')"><i class="fas fa-lock-open"></i> Forgot PIN</button>
                </div>
                <div class="key-loading" id="testLoader<?php echo $key['id']; ?>" style="display:none;"><i class="fas fa-spinner fa-spin"></i> Testing key...</div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Section 2: AI Configuration -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num purple">2</div>
        <h2><i class="fas fa-cog" style="color:#8b5cf6;margin-right:8px;"></i>AI Configuration</h2>
    </div>
    <div class="seo-section-body">
        <div class="form-grid">
            <div class="form-group">
                <label><i class="fas fa-robot"></i> Gemini Model</label>
                <select id="gemini_model" class="form-control">
                    <option value="gemini-2.0-flash-001">Gemini 2.0 Flash (Fastest) ⚡</option>
                    <option value="gemini-1.5-pro">Gemini 1.5 Pro (Best Quality)</option>
                    <option value="gemini-1.5-flash">Gemini 1.5 Flash</option>
                    <option value="gemini-pro">Gemini Pro (Legacy)</option>
                </select>
                <small class="form-hint">Model used for all AI content generation</small>
            </div>
            <div class="form-group">
                <label><i class="fas fa-clock"></i> Request Timeout (seconds)</label>
                <input type="number" id="api_timeout" class="form-control" value="30" min="10" max="120">
                <small class="form-hint">How long to wait for API response</small>
            </div>
            <div class="form-group">
                <label><i class="fas fa-redo"></i> Retry Count</label>
                <input type="number" id="api_retry_count" class="form-control" value="3" min="0" max="10">
                <small class="form-hint">Number of retries on failure</small>
            </div>
            <div class="form-group">
                <label><i class="fas fa-text-width"></i> Max Tokens</label>
                <select id="max_tokens" class="form-control">
                    <option value="1024">1024 tokens (~750 words)</option>
                    <option value="2048">2048 tokens (~1,500 words)</option>
                    <option value="4096" selected>4096 tokens (~3,000 words) ⭐ Recommended</option>
                    <option value="8192">8192 tokens (~6,000 words)</option>
                </select>
                <small class="form-hint">Use 4096+ for 900-1200 word blogs</small>
            </div>
        </div>
        <div class="form-actions">
            <button class="btn-action-hero blue" onclick="saveSettings()"><i class="fas fa-save"></i> Save AI Settings</button>
        </div>
    </div>
</div>

<!-- Section 3: Features -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num teal">3</div>
        <h2><i class="fas fa-plug" style="color:#14b8a6;margin-right:8px;"></i>Features Using This API</h2>
    </div>
    <div class="seo-section-body">
        <div class="features-grid">
            <div class="feature-item"><div class="feature-icon blue"><i class="fas fa-blog"></i></div><div><h4>Blog Generator</h4><p>Auto-generate blog posts daily</p></div></div>
            <div class="feature-item"><div class="feature-icon green"><i class="fas fa-star"></i></div><div><h4>Review Generator</h4><p>Generate customer reviews</p></div></div>
            <div class="feature-item"><div class="feature-icon purple"><i class="fas fa-question-circle"></i></div><div><h4>FAQ Generator</h4><p>Create FAQs automatically</p></div></div>
            <div class="feature-item"><div class="feature-icon orange"><i class="fas fa-layer-group"></i></div><div><h4>Page Generator</h4><p>Generate service area pages</p></div></div>
            <div class="feature-item"><div class="feature-icon red"><i class="fas fa-file-alt"></i></div><div><h4>Meta Descriptions</h4><p>Auto-generate SEO meta tags</p></div></div>
            <div class="feature-item"><div class="feature-icon teal"><i class="fas fa-magic"></i></div><div><h4>Content Enhancement</h4><p>Improve existing content</p></div></div>
        </div>
    </div>
</div>

<!-- Bottom nav -->
<div class="action-row-bottom">
    <a href="../dashboard.php" class="btn-action-hero gray"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

</div>

<!-- ============================
     MODALS
============================= -->

<!-- Add Key Modal -->
<div class="modal-overlay" id="addKeyModal">
    <div class="modal-box modal-wide">
        <div class="modal-header-gradient">
            <div class="modal-title-row">
                <div class="modal-title-icon"><i class="fas fa-key"></i></div>
                <div>
                    <h3>Add New API Key</h3>
                    <p>Secure your key with a 6-digit PIN</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal('addKeyModal')">✕</button>
        </div>
        <div class="modal-body add-key-body">
            <!-- Left: Key Info -->
            <div class="add-key-col add-key-left">
                <div class="step-col-header">
                    <div class="step-badge">1</div>
                    <span class="step-title">Key Information</span>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label>Key Label</label>
                    <input type="text" id="add_label" class="form-control" placeholder='e.g. "Primary", "Backup"' maxlength="50">
                </div>
                <div class="form-group">
                    <label>Gemini API Key <span style="color:#EF4444;">*</span></label>
                    <div class="api-key-input-wrap">
                        <span class="api-key-prefix"><i class="fas fa-plug"></i></span>
                        <input type="password" id="add_api_key" class="form-control api-key-input" placeholder="AIzaSy...">
                        <button type="button" class="toggle-vis" onclick="toggleVis('add_api_key', this)"><i class="fas fa-eye"></i></button>
                    </div>
                    <small class="form-hint" style="margin-top:8px;"><i class="fas fa-external-link-alt"></i> <a href="https://aistudio.google.com/app/apikey" target="_blank">Get free API key →</a></small>
                </div>
            </div>

            <!-- Vertical divider -->
            <div class="add-key-vdivider"></div>

            <!-- Right: PIN -->
            <div class="add-key-col add-key-right">
                <div class="step-col-header">
                    <div class="step-badge step-badge-lock"><i class="fas fa-lock"></i></div>
                    <span class="step-title">Security PIN</span>
                </div>
                <p class="step-desc">6-digit PIN to protect this key. You'll need it to edit or delete.</p>
                <div class="pin-stack">
                    <div>
                        <label class="pin-label">Enter PIN</label>
                        <div class="otp-boxes" id="otp_add_pin"></div>
                        <input type="hidden" id="add_pin">
                    </div>
                    <div>
                        <label class="pin-label">Confirm PIN</label>
                        <div class="otp-boxes" id="otp_add_pin2"></div>
                        <input type="hidden" id="add_pin2">
                    </div>
                </div>
                <div class="pin-match-msg" id="pinMatchMsg" style="display:none;"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('addKeyModal')"><i class="fas fa-times"></i> Cancel</button>
            <button class="btn btn-add-key" onclick="submitAddKey()"><i class="fas fa-plus-circle"></i> Add Key</button>
        </div>
    </div>
</div>

<!-- PIN Verify Modal -->
<div class="modal-overlay" id="pinModal">
    <div class="modal-box modal-small">
        <div class="modal-header-gradient" style="background:linear-gradient(135deg,#1E293B,#334155);">
            <div class="modal-title-row">
                <div class="modal-title-icon" style="background:rgba(255,255,255,0.15);"><i class="fas fa-lock"></i></div>
                <div>
                    <h3 style="font-size:17px;">Security PIN Required</h3>
                    <p id="pinModalDesc" style="font-size:12px;"></p>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal('pinModal')">✕</button>
        </div>
        <div class="modal-body" style="text-align:center;padding:28px 28px 20px;">
            <div class="otp-big-wrap" id="otp_pin_verify"></div>
            <input type="hidden" id="pin_input">
            <div class="pin-error" id="pinError" style="display:none;margin-top:14px;background:#FEF2F2;border:1px solid #FECACA;color:#DC2626;padding:8px 12px;border-radius:8px;font-size:13px;font-weight:600;"></div>
        </div>
        <div class="modal-footer" style="flex-direction:column;gap:10px;align-items:stretch;">
            <button class="btn btn-add-key btn-full" onclick="submitPin()"><i class="fas fa-unlock-alt"></i> Verify PIN</button>
            <button class="btn-link" style="justify-content:center;" id="forgotPinLink" onclick="openForgotFromPin()"><i class="fas fa-question-circle"></i> Forgot PIN? Reset it here</button>
        </div>
    </div>
</div>

<!-- Edit Key Modal -->
<div class="modal-overlay" id="editKeyModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit API Key</h3>
            <button class="modal-close" onclick="closeModal('editKeyModal')">✕</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit_key_id">
            <div class="form-group">
                <label>Key Label</label>
                <input type="text" id="edit_label" class="form-control" maxlength="50">
            </div>
            <div class="form-group">
                <label>Gemini API Key</label>
                <div class="input-with-toggle">
                    <input type="text" id="edit_api_key" class="form-control">
                    <button type="button" class="toggle-vis" onclick="toggleVis('edit_api_key', this)"><i class="fas fa-eye-slash"></i></button>
                </div>
                <small class="form-hint text-warning"><i class="fas fa-exclamation-triangle"></i> Saving a new key will mark it as untested.</small>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('editKeyModal')">Cancel</button>
            <button class="btn btn-primary" onclick="submitEditKey()"><i class="fas fa-save"></i> Save Changes</button>
        </div>
    </div>
</div>

<!-- Forgot PIN Modal -->
<div class="modal-overlay" id="forgotPinModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-lock-open"></i> Reset PIN</h3>
            <button class="modal-close" onclick="closeModal('forgotPinModal')">✕</button>
        </div>
        <div class="modal-body">
            <p style="color:#64748B;margin-bottom:20px;">Enter your admin password to reset the PIN for <strong id="forgotKeyName"></strong>.</p>
            <input type="hidden" id="forgot_key_id">
            <div class="form-group">
                <label>Admin Password</label>
                <div class="input-with-toggle">
                    <input type="password" id="forgot_admin_pass" class="form-control" placeholder="Enter your admin password">
                    <button type="button" class="toggle-vis" onclick="toggleVis('forgot_admin_pass', this)"><i class="fas fa-eye"></i></button>
                </div>
            </div>
            <div class="pin-section">
                <div class="pin-section-title"><i class="fas fa-lock"></i> New PIN</div>
                <div class="pin-inputs-row">
                    <div class="form-group">
                        <label>New 6-Digit PIN</label>
                        <input type="password" id="forgot_new_pin" class="form-control pin-field" placeholder="••••••" maxlength="6" inputmode="numeric">
                    </div>
                    <div class="form-group">
                        <label>Confirm New PIN</label>
                        <input type="password" id="forgot_new_pin2" class="form-control pin-field" placeholder="••••••" maxlength="6" inputmode="numeric">
                    </div>
                </div>
            </div>
            <div class="pin-error" id="forgotError" style="display:none;color:#EF4444;margin-top:10px;"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('forgotPinModal')">Cancel</button>
            <button class="btn btn-warning" onclick="submitForgotPin()"><i class="fas fa-sync"></i> Reset PIN</button>
        </div>
    </div>
</div>

<style>
/* ═══ API Settings — Complete SEO System Theme ═══ */
@keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}

.seo-page{padding:0;}
.seo-hero{background:white;border-radius:18px;box-shadow:0 4px 24px rgba(0,0,0,.07);padding:32px 36px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;gap:20px;border-left:6px solid transparent;border-image:linear-gradient(180deg,#3b82f6,#8b5cf6) 1;}
.seo-hero-left h1{font-size:32px;font-weight:800;color:#1e293b;margin:0 0 6px;}
.seo-hero-left h1 i{color:#3b82f6;margin-right:10px;}
.seo-hero-left p{color:#64748b;font-size:15px;margin:0;}
.hero-actions{display:flex;gap:12px;flex-shrink:0;flex-wrap:wrap;}

.api-notice{display:flex;align-items:center;gap:16px;border-radius:14px;border:1.5px solid;padding:16px 22px;margin-bottom:24px;}
.api-notice.success{background:#f0fdf4;border-color:#86efac;color:#065f46;}
.api-notice.error{background:#fef2f2;border-color:#fca5a5;color:#991b1b;}
.api-notice.info{background:#eff6ff;border-color:#93c5fd;color:#1e40af;}
.api-notice.warning{background:#fffbeb;border-color:#fde68a;color:#92400e;}
.api-notice-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;font-size:18px;flex-shrink:0;}
.api-notice h4{font-size:14px;font-weight:700;margin:0 0 3px;}
.api-notice p{font-size:13px;margin:0;}
.api-notice a{color:#3b82f6;font-weight:600;}

.seo-section{background:white;border-radius:18px;box-shadow:0 4px 20px rgba(0,0,0,.07);margin-bottom:24px;overflow:hidden;}
.seo-section-head{padding:22px 32px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
.sec-num{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:800;color:white;flex-shrink:0;}
.sec-num.blue{background:linear-gradient(135deg,#3b82f6,#2563eb);}
.sec-num.purple{background:linear-gradient(135deg,#8b5cf6,#7c3aed);}
.sec-num.teal{background:linear-gradient(135deg,#14b8a6,#0d9488);}
.seo-section-head h2{font-size:20px;font-weight:700;color:#1e293b;margin:0;}
.sec-badge{background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;}
.seo-section-body{padding:28px 32px;}
.badge-max{background:#fef3c7;color:#92400e;border:1px solid #fde68a;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;}

.empty-state{text-align:center;padding:48px 20px;}
.empty-state i{font-size:56px;color:#bfdbfe;display:block;margin-bottom:14px;animation:float 3s ease-in-out infinite;}
.empty-state h3{font-size:18px;font-weight:700;color:#1e293b;margin:0 0 6px;}
.empty-state p{font-size:13px;color:#64748b;margin:0;}

.btn-action-hero{display:inline-flex;align-items:center;gap:8px;padding:11px 20px;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;text-decoration:none;transition:all .25s;white-space:nowrap;}
.btn-action-hero:hover{transform:translateY(-2px);text-decoration:none;}
.btn-action-hero.blue{background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;box-shadow:0 4px 14px rgba(59,130,246,.35);}
.btn-action-hero.blue:hover{box-shadow:0 8px 24px rgba(59,130,246,.45);}
.btn-action-hero.green{background:linear-gradient(135deg,#10b981,#059669);color:white;box-shadow:0 4px 14px rgba(16,185,129,.35);}
.btn-action-hero.green:hover{box-shadow:0 8px 24px rgba(16,185,129,.45);}
.btn-action-hero.gray{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;box-shadow:none;}
.btn-action-hero.gray:hover{background:#e2e8f0;transform:none;}
.btn-action-hero.sm{padding:9px 16px;font-size:13px;}
.action-row-bottom{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:32px;}

.flash-msg{padding:14px 20px;border-radius:10px;margin-bottom:16px;font-weight:600;display:flex;align-items:center;gap:10px;}
.flash-msg.success{background:#d1fae5;border:2px solid #10b981;color:#065f46;}
.flash-msg.error{background:#fee2e2;border:2px solid #ef4444;color:#991b1b;}

/* ====== KEYS GRID ====== */
.keys-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:20px; }
.empty-state { text-align:center; padding:40px; color:#94A3B8; }
.empty-state p { margin:16px 0; font-size:16px; }

.key-card { background:#F8FAFC; border:2px solid #E2E8F0; border-radius:14px; padding:20px; position:relative; transition:all 0.3s; }
.key-card:hover { border-color:#CBD5E1; box-shadow:0 4px 16px rgba(0,0,0,0.08); }
.key-card.primary { background:linear-gradient(135deg,#EFF6FF,#DBEAFE); border-color:#93C5FD; }

.primary-badge { position:absolute; top:14px; right:14px; background:linear-gradient(135deg,#F59E0B,#D97706); color:#fff; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; letter-spacing:0.5px; }
.key-card-header { display:flex; align-items:flex-start; gap:12px; margin-bottom:12px; }
.key-icon { width:44px; height:44px; background:linear-gradient(135deg,#3B82F6,#2563EB); border-radius:10px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px; flex-shrink:0; }
.key-info { flex:1; min-width:0; }
.key-info h4 { margin:0 0 4px; font-size:15px; color:#1E293B; font-weight:600; }
.masked-key { font-size:12px; color:#64748B; background:#E2E8F0; padding:2px 8px; border-radius:4px; display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.key-model-info { font-size:12px; color:#3B82F6; margin-bottom:12px; }
.key-model-info i { margin-right:4px; }

.test-status-badge { font-size:12px; font-weight:600; padding:3px 10px; border-radius:20px; white-space:nowrap; }
.test-status-badge.success { background:#D1FAE5; color:#065F46; }
.test-status-badge.failed  { background:#FEE2E2; color:#991B1B; }
.test-status-badge.untested{ background:#F1F5F9; color:#64748B; }

.key-card-actions { display:flex; flex-wrap:wrap; gap:6px; margin-top:14px; }
.btn-action { padding:6px 12px; border-radius:8px; border:none; cursor:pointer; font-size:12px; font-weight:600; display:flex; align-items:center; gap:5px; transition:all 0.2s; }
.btn-test     { background:#EFF6FF; color:#3B82F6; }
.btn-test:hover { background:#DBEAFE; }
.btn-primary-set { background:#FEF3C7; color:#92400E; }
.btn-primary-set:hover { background:#FDE68A; }
.btn-edit   { background:#F0FDF4; color:#166534; }
.btn-edit:hover { background:#DCFCE7; }
.btn-delete { background:#FEF2F2; color:#991B1B; }
.btn-delete:hover { background:#FEE2E2; }
.btn-forgot { background:#F5F3FF; color:#5B21B6; }
.btn-forgot:hover { background:#EDE9FE; }
.key-loading { text-align:center; padding:8px; color:#3B82F6; font-size:13px; }

/* ====== FORM ====== */
.form-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:24px; margin-bottom:24px; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-group label { font-size:13px; font-weight:600; color:#374151; }
.form-group label i { color:#3B82F6; margin-right:5px; }
.form-control { padding:10px 14px; border:2px solid #E2E8F0; border-radius:8px; font-size:14px; color:#1E293B; outline:none; transition:border-color 0.2s; }
.form-control:focus { border-color:#3B82F6; }
.form-hint { font-size:12px; color:#64748B; }
.form-hint a { color:#3B82F6; text-decoration:none; font-weight:600; }
.text-warning { color:#D97706 !important; }
.form-actions { display:flex; gap:12px; }

/* ====== FEATURES ====== */
.features-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; }
.feature-item { display:flex; gap:14px; padding:16px; background:#F8FAFC; border-radius:12px; border:2px solid #E2E8F0; }
.feature-item:hover { border-color:#3B82F6; }
.feature-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px; color:#fff; flex-shrink:0; }
.feature-icon.blue   { background:linear-gradient(135deg,#3B82F6,#2563EB); }
.feature-icon.green  { background:linear-gradient(135deg,#10B981,#059669); }
.feature-icon.purple { background:linear-gradient(135deg,#8B5CF6,#7C3AED); }
.feature-icon.orange { background:linear-gradient(135deg,#F59E0B,#D97706); }
.feature-icon.red    { background:linear-gradient(135deg,#EF4444,#DC2626); }
.feature-icon.teal   { background:linear-gradient(135deg,#14B8A6,#0D9488); }
.feature-item h4 { margin:0 0 4px; font-size:14px; font-weight:600; color:#1E293B; }
.feature-item p  { margin:0; font-size:12px; color:#64748B; }

/* ====== FLASH MSG ====== */
.flash-msg { padding:14px 20px; border-radius:10px; margin-bottom:16px; font-weight:600; display:flex; align-items:center; gap:10px; }
.flash-msg.success { background:#D1FAE5; border:2px solid #10B981; color:#065F46; }
.flash-msg.error   { background:#FEE2E2; border:2px solid #EF4444; color:#991B1B; }

/* ====== MODALS ====== */
/* ====== MODAL BASE ====== */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; padding:20px; }
.modal-overlay.active { display:flex; }
.modal-box { background:#fff; border-radius:20px; width:100%; max-width:500px; box-shadow:0 24px 80px rgba(0,0,0,0.3); animation:modalIn 0.3s cubic-bezier(0.34,1.56,0.64,1); overflow:hidden; }
.modal-wide { max-width:760px; }
.modal-small { max-width:380px; }
@keyframes modalIn { from { opacity:0; transform:scale(0.85) translateY(20px); } to { opacity:1; transform:scale(1) translateY(0); } }
.modal-close { background:rgba(255,255,255,0.2); border:none; width:32px; height:32px; border-radius:50%; cursor:pointer; color:#fff; font-size:16px; display:flex; align-items:center; justify-content:center; transition:background 0.2s; flex-shrink:0; }
.modal-close:hover { background:rgba(255,255,255,0.35); }
.modal-body { padding:28px; }
.modal-footer { display:flex; gap:12px; justify-content:flex-end; padding:18px 28px; border-top:2px solid #F1F5F9; background:#FAFBFC; }

/* ====== GRADIENT HEADER ====== */
.modal-header-gradient { background:linear-gradient(135deg,#4F46E5,#7C3AED); padding:22px 24px; display:flex; justify-content:space-between; align-items:center; }
.modal-title-row { display:flex; align-items:center; gap:14px; }
.modal-title-icon { width:46px; height:46px; background:rgba(255,255,255,0.2); border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:20px; }
.modal-title-row h3 { margin:0 0 3px; font-size:20px; font-weight:700; color:#fff; }
.modal-title-row p  { margin:0; font-size:13px; color:rgba(255,255,255,0.75); }

/* ====== STANDARD MODAL HEADER ====== */
.modal-header { display:flex; justify-content:space-between; align-items:center; padding:20px 24px; border-bottom:2px solid #F1F5F9; background:linear-gradient(135deg,#F8FAFC,#F1F5F9); }
.modal-header h3 { margin:0; font-size:18px; color:#1E293B; display:flex; align-items:center; gap:8px; }
.modal-header h3 i { color:#4F46E5; }
.modal-header .modal-close { background:#E2E8F0; color:#64748B; }
.modal-header .modal-close:hover { background:#CBD5E1; color:#1E293B; }

/* ====== STEP LAYOUT ====== */
.modal-step { display:flex; gap:16px; align-items:flex-start; }
.modal-step + .modal-step { margin-top:4px; }
.step-badge { width:34px; height:34px; border-radius:50%; background:linear-gradient(135deg,#4F46E5,#7C3AED); color:#fff; font-size:15px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:2px; box-shadow:0 4px 12px rgba(79,70,229,0.35); }
.step-badge-lock { background:linear-gradient(135deg,#059669,#047857); box-shadow:0 4px 12px rgba(5,150,105,0.35); font-size:14px; }
.step-content { flex:1; min-width:0; }
.step-title { font-size:15px; font-weight:700; color:#1E293B; margin-bottom:14px; }
.step-desc { font-size:13px; color:#64748B; margin:0 0 16px; line-height:1.5; }
.modal-divider { border:none; border-top:2px dashed #E2E8F0; margin:22px 0; }

/* ====== ADD KEY TWO-COLUMN MODAL ====== */
.add-key-body { display:flex; gap:0; padding:0 !important; min-height:300px; }
.add-key-col  { flex:1; padding:28px 28px; display:flex; flex-direction:column; gap:0; }
.add-key-left { background:#FAFBFF; border-right:none; }
.add-key-right{ background:#F0FDF4; }
.add-key-vdivider { width:2px; background:linear-gradient(to bottom, transparent, #E2E8F0 20%, #E2E8F0 80%, transparent); flex-shrink:0; }
.step-col-header { display:flex; align-items:center; gap:10px; margin-bottom:20px; }
.step-col-header .step-title { margin-bottom:0; font-size:16px; }
.pin-stack { display:flex; flex-direction:column; gap:18px; }
.pin-stack .otp-boxes { justify-content:flex-start; }

/* ====== API KEY INPUT ====== */
.api-key-input-wrap { display:flex; align-items:center; border:2px solid #E2E8F0; border-radius:10px; overflow:hidden; transition:border-color 0.2s; background:#fff; }
.api-key-input-wrap:focus-within { border-color:#4F46E5; box-shadow:0 0 0 3px rgba(79,70,229,0.1); }
.api-key-prefix { padding:0 12px; background:#F1F5F9; color:#4F46E5; font-size:16px; height:46px; display:flex; align-items:center; border-right:2px solid #E2E8F0; }
.api-key-input { border:none !important; box-shadow:none !important; flex:1; padding:12px 12px; font-size:14px; font-family:monospace; }
.api-key-input:focus { outline:none; }
.api-key-input-wrap .toggle-vis { border:none; border-left:2px solid #E2E8F0; border-radius:0; padding:0 14px; background:#F8FAFC; height:46px; }

/* ====== OTP PIN BOXES ====== */
.otp-boxes { display:flex; gap:8px; }
.otp-box { width:44px; height:54px; border:2px solid #E2E8F0; border-radius:10px; font-size:22px; font-weight:700; text-align:center; color:#1E293B; background:#F8FAFC; transition:all 0.2s; outline:none; caret-color:transparent; }
.otp-box:focus { border-color:#4F46E5; background:#fff; box-shadow:0 0 0 3px rgba(79,70,229,0.15); transform:scale(1.05); }
.otp-box.filled { border-color:#7C3AED; background:#F5F3FF; color:#4F46E5; }
.otp-box.match  { border-color:#10B981; background:#ECFDF5; color:#059669; }
.otp-box.mismatch { border-color:#EF4444; background:#FEF2F2; color:#DC2626; }
.pin-label { font-size:12px; font-weight:600; color:#64748B; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:8px; }
.pin-row-wrap { display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.pin-col { flex:1; min-width:180px; }
.pin-col-sep { font-size:12px; color:#94A3B8; font-weight:600; text-align:center; padding-top:24px; }
.pin-col-sep span { background:#F1F5F9; padding:4px 10px; border-radius:20px; }
.pin-match-msg { margin-top:10px; font-size:13px; font-weight:600; padding:8px 12px; border-radius:8px; }
.pin-match-msg.ok  { background:#ECFDF5; color:#059669; }
.pin-match-msg.bad { background:#FEF2F2; color:#DC2626; }

/* ====== OTP (big single) ====== */
.otp-big-wrap { display:flex; gap:10px; justify-content:center; margin:8px 0 4px; }
.otp-big-box { width:52px; height:64px; border:2px solid #E2E8F0; border-radius:12px; font-size:26px; font-weight:700; text-align:center; color:#1E293B; background:#F8FAFC; transition:all 0.2s; outline:none; caret-color:transparent; }
.otp-big-box:focus { border-color:#4F46E5; background:#fff; box-shadow:0 0 0 4px rgba(79,70,229,0.15); transform:scale(1.06); }
.otp-big-box.filled { border-color:#7C3AED; background:#EDE9FE; }

/* ====== BUTTONS ====== */
.btn-ghost { background:#F1F5F9; color:#64748B; border:2px solid #E2E8F0; padding:10px 20px; border-radius:10px; cursor:pointer; font-weight:600; font-size:14px; display:flex; align-items:center; gap:7px; transition:all 0.2s; }
.btn-ghost:hover { background:#E2E8F0; color:#1E293B; }
.btn-add-key { background:linear-gradient(135deg,#4F46E5,#7C3AED); color:#fff; border:none; padding:11px 24px; border-radius:10px; cursor:pointer; font-weight:700; font-size:14px; display:flex; align-items:center; gap:8px; box-shadow:0 4px 14px rgba(79,70,229,0.4); transition:all 0.2s; }
.btn-add-key:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(79,70,229,0.5); }
.btn-full { width:100%; justify-content:center; }
.btn-link { background:none; border:none; color:#4F46E5; cursor:pointer; font-size:13px; font-weight:600; display:flex; align-items:center; gap:5px; }
.btn-link:hover { text-decoration:underline; }
.btn-warning { background:linear-gradient(135deg,#F59E0B,#D97706); color:#fff; border:none; padding:10px 20px; border-radius:10px; cursor:pointer; font-weight:600; display:flex; align-items:center; gap:8px; }
.btn-warning:hover { transform:translateY(-1px); }
.input-with-toggle { display:flex; gap:8px; }
.input-with-toggle input { flex:1; }
.toggle-vis { padding:0 14px; background:#F1F5F9; border:2px solid #E2E8F0; border-radius:8px; cursor:pointer; }
.toggle-vis:hover { background:#E2E8F0; }
.pin-big { font-size:22px; letter-spacing:8px; text-align:center; border-radius:12px; padding:14px; }

@media(max-width:768px) {
    .form-grid { grid-template-columns:1fr; }
    .keys-grid { grid-template-columns:1fr; }
    .pin-inputs-row { grid-template-columns:1fr; }
}
</style>

<script>
const CSRF = '<?php echo $_SESSION['csrf_token']; ?>';

/* ============================
   FLASH MESSAGES
============================= */
function showFlash(msg, type='success') {
    const el = document.getElementById('flashMsg');
    el.className = 'flash-msg ' + type;
    el.innerHTML = `<i class="fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'}"></i> ${msg}`;
    el.style.display = 'flex';
    setTimeout(() => el.style.display='none', 4000);
    window.scrollTo({top:0, behavior:'smooth'});
}

/* ============================
   MODAL HELPERS
============================= */
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

/* ============================
   OTP BOX BUILDER
============================= */
function buildOTPBoxes(containerId, hiddenId, size='normal', onComplete=null) {
    const container = document.getElementById(containerId);
    const hidden    = document.getElementById(hiddenId);
    container.innerHTML = '';
    const cls = size === 'big' ? 'otp-big-box' : 'otp-box';
    const boxes = [];
    for (let i = 0; i < 6; i++) {
        const inp = document.createElement('input');
        inp.type = 'tel'; inp.maxLength = 1; inp.className = cls;
        inp.setAttribute('inputmode','numeric'); inp.pattern = '[0-9]';
        inp.addEventListener('input', e => {
            inp.value = inp.value.replace(/\D/,'').slice(-1);
            if (inp.value) { inp.classList.add('filled'); if (i < 5) boxes[i+1].focus(); }
            else inp.classList.remove('filled','match','mismatch');
            syncHidden();
            if (onComplete) onComplete();
        });
        inp.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !inp.value && i > 0) { boxes[i-1].focus(); boxes[i-1].value=''; boxes[i-1].classList.remove('filled','match','mismatch'); syncHidden(); if(onComplete)onComplete(); }
            if (e.key === 'Enter') { if(onComplete)onComplete(); else submitPin(); }
        });
        inp.addEventListener('paste', e => {
            e.preventDefault();
            const pasted = (e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
            pasted.split('').forEach((ch,j) => { if(boxes[j]){ boxes[j].value=ch; boxes[j].classList.add('filled'); } });
            syncHidden(); if(onComplete)onComplete();
            if(boxes[Math.min(pasted.length,5)]) boxes[Math.min(pasted.length,5)].focus();
        });
        container.appendChild(inp);
        boxes.push(inp);
    }
    function syncHidden() { hidden.value = boxes.map(b=>b.value).join(''); }
    function clearAll() { boxes.forEach(b=>{ b.value=''; b.className=cls; }); hidden.value=''; }
    function setStates(cls2) { boxes.forEach(b=>{ if(b.value) { b.classList.remove('filled','match','mismatch'); b.classList.add(cls2); } }); }
    return { boxes, clearAll, setStates, syncHidden };
}

let _otpAddPin, _otpAddPin2;
function checkPinMatch() {
    const p1 = document.getElementById('add_pin').value;
    const p2 = document.getElementById('add_pin2').value;
    const msg = document.getElementById('pinMatchMsg');
    if (p1.length === 6 && p2.length === 6) {
        if (p1 === p2) { msg.className='pin-match-msg ok'; msg.textContent='✅ PINs match!'; msg.style.display='block'; _otpAddPin.setStates('match'); _otpAddPin2.setStates('match'); }
        else           { msg.className='pin-match-msg bad'; msg.textContent='❌ PINs do not match'; msg.style.display='block'; _otpAddPin2.setStates('mismatch'); }
    } else { msg.style.display='none'; }
}

/* ============================
   ADD KEY
============================= */
function openAddKeyModal() {
    document.getElementById('add_label').value = '';
    document.getElementById('add_api_key').value = '';
    document.getElementById('pinMatchMsg').style.display = 'none';
    _otpAddPin  = buildOTPBoxes('otp_add_pin',  'add_pin',  'normal', checkPinMatch);
    _otpAddPin2 = buildOTPBoxes('otp_add_pin2', 'add_pin2', 'normal', checkPinMatch);
    openModal('addKeyModal');
    setTimeout(()=>document.getElementById('add_label').focus(), 250);
}
function submitAddKey() {
    const label   = document.getElementById('add_label').value.trim() || 'API Key';
    const api_key = document.getElementById('add_api_key').value.trim();
    const pin     = document.getElementById('add_pin').value;
    const pin2    = document.getElementById('add_pin2').value;
    if (!api_key)              return showFlash('API key is required.','error');
    if (!/^\d{6}$/.test(pin))  return showFlash('Enter all 6 digits of your PIN.','error');
    if (pin !== pin2)          return showFlash('PINs do not match. Please re-enter.','error');

    apiPost({action:'add_key', key_label:label, api_key, pin, pin2}).then(r => {
        if (r.success) {
            closeModal('addKeyModal');
            showFlash(r.message);
            setTimeout(()=>location.reload(), 1200);
        } else showFlash(r.message,'error');
    });
}

/* ============================
   PIN VERIFICATION FLOW
============================= */
let _pinKeyId=0, _pinAction='', _pinKeyName='', _otpPinVerify=null;
function openPinModal(keyId, action, keyName) {
    _pinKeyId   = keyId;
    _pinAction  = action;
    _pinKeyName = keyName;
    const desc = action==='edit' ? `Edit "${keyName}"` :
                 action==='delete' ? `Delete "${keyName}"` :
                 `Set "${keyName}" as primary`;
    document.getElementById('pinModalDesc').textContent = desc;
    document.getElementById('pinError').style.display = 'none';
    _otpPinVerify = buildOTPBoxes('otp_pin_verify', 'pin_input', 'big', null);
    openModal('pinModal');
    setTimeout(()=>_otpPinVerify.boxes[0].focus(), 250);
}
function submitPin() {
    const pin = document.getElementById('pin_input').value;
    if (!/^\d{6}$/.test(pin)) {
        document.getElementById('pinError').textContent = 'Enter all 6 digits of your PIN.';
        document.getElementById('pinError').style.display = 'block';
        return;
    }
    apiPost({action:'verify_pin', key_id:_pinKeyId, pin}).then(r => {
        if (r.success) {
            closeModal('pinModal');
            if (_pinAction === 'edit') {
                document.getElementById('edit_key_id').value  = _pinKeyId;
                document.getElementById('edit_label').value   = _pinKeyName;
                document.getElementById('edit_api_key').value = r.api_key;
                openModal('editKeyModal');
            } else if (_pinAction === 'delete') {
                if (confirm(`Delete "${_pinKeyName}"? This cannot be undone.`)) {
                    apiPost({action:'delete_key', key_id:_pinKeyId}).then(r2 => {
                        if (r2.success) { showFlash(r2.message); setTimeout(()=>location.reload(),1200); }
                        else showFlash(r2.message,'error');
                    });
                }
            } else if (_pinAction === 'set_primary') {
                apiPost({action:'set_primary', key_id:_pinKeyId}).then(r2 => {
                    if (r2.success) { showFlash(r2.message); setTimeout(()=>location.reload(),1200); }
                    else showFlash(r2.message,'error');
                });
            }
        } else {
            document.getElementById('pinError').textContent = r.message;
            document.getElementById('pinError').style.display = 'block';
        }
    });
}

/* ============================
   EDIT KEY
============================= */
function submitEditKey() {
    const key_id   = document.getElementById('edit_key_id').value;
    const key_label= document.getElementById('edit_label').value.trim();
    const api_key  = document.getElementById('edit_api_key').value.trim();
    if (!api_key) return showFlash('API key cannot be empty.','error');
    apiPost({action:'edit_key', key_id, key_label, api_key}).then(r => {
        if (r.success) {
            closeModal('editKeyModal');
            showFlash(r.message);
            setTimeout(()=>location.reload(), 1200);
        } else showFlash(r.message,'error');
    });
}

/* ============================
   TEST KEY
============================= */
function testKey(keyId, isPrimary=false) {
    const loader = document.getElementById('testLoader'+keyId);
    if (loader) loader.style.display = 'block';
    apiPost({action:'test_key', key_id:keyId}).then(r => {
        if (loader) loader.style.display = 'none';
        if (r.success) {
            showFlash(`✅ Key working! Model: ${r.model}`,'success');
            if (isPrimary) {
                const banner = document.getElementById('primaryStatusBanner');
                const text   = document.getElementById('primaryStatusText');
                if (banner) { banner.className='status-banner success'; text.textContent=`✅ Primary key working — ${r.model}`; }
            }
            setTimeout(()=>location.reload(), 2000);
        } else {
            showFlash('❌ Test failed: ' + r.message,'error');
            setTimeout(()=>location.reload(), 2000);
        }
    });
}

/* ============================
   FORGOT PIN
============================= */
function openForgotPinModal(keyId, keyName) {
    document.getElementById('forgot_key_id').value = keyId;
    document.getElementById('forgotKeyName').textContent = keyName;
    document.getElementById('forgot_admin_pass').value = '';
    document.getElementById('forgot_new_pin').value = '';
    document.getElementById('forgot_new_pin2').value = '';
    document.getElementById('forgotError').style.display = 'none';
    openModal('forgotPinModal');
}
function openForgotFromPin() {
    closeModal('pinModal');
    openForgotPinModal(_pinKeyId, _pinKeyName);
}
function submitForgotPin() {
    const key_id        = document.getElementById('forgot_key_id').value;
    const admin_password= document.getElementById('forgot_admin_pass').value;
    const new_pin       = document.getElementById('forgot_new_pin').value.trim();
    const new_pin2      = document.getElementById('forgot_new_pin2').value.trim();
    const errEl         = document.getElementById('forgotError');

    if (!admin_password)           { errEl.textContent='Admin password required.'; errEl.style.display='block'; return; }
    if (!/^\d{6}$/.test(new_pin))  { errEl.textContent='New PIN must be 6 digits.'; errEl.style.display='block'; return; }
    if (new_pin !== new_pin2)      { errEl.textContent='PINs do not match.'; errEl.style.display='block'; return; }

    apiPost({action:'reset_pin', key_id, admin_password, new_pin, new_pin2}).then(r => {
        if (r.success) {
            closeModal('forgotPinModal');
            showFlash(r.message);
        } else {
            errEl.textContent = r.message;
            errEl.style.display = 'block';
        }
    });
}

/* ============================
   SAVE AI SETTINGS
============================= */
function saveSettings() {
    apiPost({
        action:'save_settings',
        gemini_model:     document.getElementById('gemini_model').value,
        api_timeout:      document.getElementById('api_timeout').value,
        api_retry_count:  document.getElementById('api_retry_count').value,
        max_tokens:       document.getElementById('max_tokens').value
    }).then(r => showFlash(r.message, r.success?'success':'error'));
}

/* ============================
   TOGGLE VISIBILITY
============================= */
function toggleVis(fieldId, btn) {
    const f = document.getElementById(fieldId);
    const i = btn.querySelector('i');
    if (f.type === 'password') { f.type='text'; i.className='fas fa-eye-slash'; }
    else { f.type='password'; i.className='fas fa-eye'; }
}

/* ============================
   API POST HELPER
============================= */
function apiPost(data) {
    data.csrf_token = CSRF;
    return fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams(data),
        credentials: 'same-origin'
    }).then(r => {
        const ct = r.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            return r.text().then(t => {
                console.error('Non-JSON response:', t.substring(0, 500));
                throw new Error('Server returned non-JSON. Check console for details.');
            });
        }
        return r.json();
    }).catch(err => {
        showFlash('Error: ' + err.message, 'error');
        return {success: false, message: err.message};
    });
}

/* Close modal on overlay click */
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => { if(e.target===overlay) overlay.classList.remove('active'); });
});
</script>

<?php include '../includes/footer.php'; ?>
