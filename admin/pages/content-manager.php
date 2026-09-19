<?php
/**
 * Content Manager - View, Lock, and Delete Generated Pages
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../includes/security.php';
require_once '../includes/content-security.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$security = new AdminSecurity();
$contentSecurity = new ContentSecurity();

$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!$security->verifyCSRFToken($csrf_token)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        switch ($action) {
            case 'lock_content':
                $page_path = $_POST['page_path'] ?? '';
                $page_id = basename($page_path, '.php');
                
                if ($contentSecurity->lockContent('page', $page_id, $page_path, $_SESSION['admin_id'], 'Locked via content manager')) {
                    $message = 'Page locked successfully! Password required to delete.';
                    $security->logSecurityEvent($_SESSION['admin_id'], 'content_locked', 'Page: ' . $page_id);
                } else {
                    $error = 'Failed to lock page.';
                }
                break;
                
            case 'unlock_content':
                $page_id = $_POST['page_id'] ?? '';
                $password = $_POST['unlock_password'] ?? '';
                
                if ($contentSecurity->unlockContent('page', $page_id, $password, $_SESSION['admin_id'])) {
                    $message = 'Page unlocked successfully!';
                } else {
                    $error = 'Invalid password. Cannot unlock page.';
                }
                break;
                
            case 'delete_content':
                $page_path = $_POST['page_path'] ?? '';
                $page_id = basename($page_path, '.php');
                $password = $_POST['delete_password'] ?? '';
                
                $result = $contentSecurity->deleteContent('page', $page_id, $page_path, $password, $_SESSION['admin_id']);
                
                if ($result['success']) {
                    $message = 'Page deleted successfully!';
                } else {
                    $error = $result['error'];
                }
                break;
                
            case 'bulk_lock':
                $selected_pages = $_POST['selected_pages'] ?? [];
                if (!empty($selected_pages)) {
                    $items = [];
                    foreach ($selected_pages as $page_path) {
                        $items[] = [
                            'type' => 'page',
                            'id' => basename($page_path, '.php'),
                            'path' => $page_path
                        ];
                    }
                    $locked = $contentSecurity->bulkLockContent($items, $_SESSION['admin_id']);
                    $message = "Locked {$locked} page(s) successfully!";
                } else {
                    $error = 'No pages selected.';
                }
                break;
        }
    }
}

// Get all generated pages
$root_dir = '../../';
$all_pages = glob($root_dir . '*-in-*.php');

// Get lock status for each page
$pages_data = [];
foreach ($all_pages as $page_path) {
    $page_id = basename($page_path, '.php');
    $is_locked = $contentSecurity->isContentLocked('page', $page_id);
    $lock_info = $is_locked ? $contentSecurity->getLockInfo('page', $page_id) : null;
    
    // Parse page info
    preg_match('/^(.+)-in-(.+)\.php$/', basename($page_path), $matches);
    $service = $matches[1] ?? '';
    $area = $matches[2] ?? '';
    
    $pages_data[] = [
        'path' => $page_path,
        'id' => $page_id,
        'service' => str_replace('-', ' ', ucwords($service, '-')),
        'area' => str_replace('-', ' ', ucwords($area, '-')),
        'size' => filesize($page_path),
        'modified' => filemtime($page_path),
        'is_locked' => $is_locked,
        'lock_info' => $lock_info
    ];
}

// Sort by modified date (newest first)
usort($pages_data, function($a, $b) {
    return $b['modified'] - $a['modified'];
});

// Pagination
$per_page = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total_pages = ceil(count($pages_data) / $per_page);
$offset = ($page - 1) * $per_page;
$pages_display = array_slice($pages_data, $offset, $per_page);

// Statistics
$total_locked = count(array_filter($pages_data, function($p) { return $p['is_locked']; }));

$page_title = 'Content Manager';
include '../includes/header.php';
?>

<style>
.content-manager {
    padding: 20px;
}

.stats-bar {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.stat-box {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.stat-box h3 {
    font-size: 28px;
    margin: 0 0 8px 0;
    color: #3B82F6;
}

.stat-box p {
    margin: 0;
    color: #64748B;
    font-size: 14px;
}

.content-table-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.table-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 12px;
}

.search-box {
    flex: 1;
    min-width: 250px;
}

.search-box input {
    width: 100%;
    padding: 10px 16px;
    border: 2px solid #E2E8F0;
    border-radius: 8px;
    font-size: 14px;
}

.bulk-actions {
    display: flex;
    gap: 8px;
}

.btn {
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    transition: all 0.3s;
}

.btn-primary {
    background: #3B82F6;
    color: white;
}

.btn-success {
    background: #10B981;
    color: white;
}

.btn-danger {
    background: #EF4444;
    color: white;
}

.btn-secondary {
    background: #64748B;
    color: white;
}

.btn-small {
    padding: 6px 12px;
    font-size: 12px;
}

.content-table {
    width: 100%;
    border-collapse: collapse;
}

.content-table th {
    background: #F8FAFC;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    color: #475569;
    border-bottom: 2px solid #E2E8F0;
}

.content-table td {
    padding: 12px;
    border-bottom: 1px solid #E2E8F0;
    font-size: 13px;
}

.content-table tr:hover {
    background: #F8FAFC;
}

.lock-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.lock-badge.locked {
    background: #FEF3C7;
    color: #92400E;
}

.lock-badge.unlocked {
    background: #D1FAE5;
    color: #065F46;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 24px;
}

.pagination a,
.pagination span {
    padding: 8px 12px;
    border: 1px solid #E2E8F0;
    border-radius: 6px;
    text-decoration: none;
    color: #475569;
}

.pagination .active {
    background: #3B82F6;
    color: white;
    border-color: #3B82F6;
}

.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.alert-success {
    background: #D1FAE5;
    color: #065F46;
    border: 1px solid #6EE7B7;
}

.alert-error {
    background: #FEE2E2;
    color: #991B1B;
    border: 1px solid #FCA5A5;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 12px;
    padding: 24px;
    max-width: 500px;
    width: 90%;
}

.modal-content h3 {
    margin: 0 0 16px 0;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 2px solid #E2E8F0;
    border-radius: 8px;
}

@media (max-width: 768px) {
    .stats-bar {
        grid-template-columns: 1fr 1fr;
    }
}
</style>

<div class="content-manager">
    <div class="page-header">
        <h1><i class="fas fa-folder-open"></i> Content Manager</h1>
        <p>Manage, lock, and delete generated pages</p>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div class="stats-bar">
        <div class="stat-box">
            <h3><?php echo count($pages_data); ?></h3>
            <p>Total Pages</p>
        </div>
        <div class="stat-box">
            <h3><?php echo $total_locked; ?></h3>
            <p>Locked Pages</p>
        </div>
        <div class="stat-box">
            <h3><?php echo count($pages_data) - $total_locked; ?></h3>
            <p>Unlocked Pages</p>
        </div>
        <div class="stat-box">
            <h3><?php echo number_format(array_sum(array_column($pages_data, 'size')) / 1024 / 1024, 2); ?> MB</h3>
            <p>Total Size</p>
        </div>
    </div>
    
    <!-- Content Table -->
    <div class="content-table-card">
        <form id="bulkForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($security->generateCSRFToken()); ?>">
            <input type="hidden" name="action" value="bulk_lock">
            
            <div class="table-controls">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="🔍 Search pages..." onkeyup="filterTable()">
                </div>
                <div class="bulk-actions">
                    <button type="submit" class="btn btn-secondary btn-small">
                        <i class="fas fa-lock"></i> Lock Selected
                    </button>
                    <button type="button" class="btn btn-primary btn-small" onclick="selectAll()">
                        <i class="fas fa-check-square"></i> Select All
                    </button>
                </div>
            </div>
            
            <div style="overflow-x:auto;">
                <table class="content-table" id="contentTable">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAllCheckbox" onchange="toggleAll(this)"></th>
                            <th>Service</th>
                            <th>Area</th>
                            <th>Status</th>
                            <th>Size</th>
                            <th>Modified</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages_display as $page): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="selected_pages[]" value="<?php echo htmlspecialchars($page['path']); ?>" class="page-checkbox">
                            </td>
                            <td><strong><?php echo htmlspecialchars($page['service']); ?></strong></td>
                            <td><?php echo htmlspecialchars($page['area']); ?></td>
                            <td>
                                <?php if ($page['is_locked']): ?>
                                    <span class="lock-badge locked">
                                        <i class="fas fa-lock"></i> Locked
                                    </span>
                                <?php else: ?>
                                    <span class="lock-badge unlocked">
                                        <i class="fas fa-lock-open"></i> Unlocked
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format($page['size'] / 1024, 1); ?> KB</td>
                            <td><?php echo date('M d, Y H:i', $page['modified']); ?></td>
                            <td>
                                <div style="display:flex;gap:4px;">
                                    <a href="<?php echo str_replace('../../', '../../', $page['path']); ?>" target="_blank" class="btn btn-primary btn-small" title="View Page">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if ($page['is_locked']): ?>
                                        <button type="button" class="btn btn-secondary btn-small" onclick="showUnlockModal('<?php echo htmlspecialchars($page['id']); ?>')" title="Unlock">
                                            <i class="fas fa-unlock"></i>
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($security->generateCSRFToken()); ?>">
                                            <input type="hidden" name="action" value="lock_content">
                                            <input type="hidden" name="page_path" value="<?php echo htmlspecialchars($page['path']); ?>">
                                            <button type="submit" class="btn btn-success btn-small" title="Lock">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-danger btn-small" 
                                            onclick="showDeleteModal('<?php echo htmlspecialchars($page['path']); ?>', '<?php echo htmlspecialchars($page['service'] . ' in ' . $page['area']); ?>', <?php echo $page['is_locked'] ? 'true' : 'false'; ?>)" 
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Unlock Modal -->
<div id="unlockModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-unlock"></i> Unlock Page</h3>
        <p>Enter your generation password to unlock this page:</p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($security->generateCSRFToken()); ?>">
            <input type="hidden" name="action" value="unlock_content">
            <input type="hidden" name="page_id" id="unlock_page_id">
            
            <div class="form-group">
                <label>Generation Password</label>
                <input type="password" name="unlock_password" class="form-control" required>
            </div>
            
            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn btn-success" style="flex:1;">
                    <i class="fas fa-unlock"></i> Unlock
                </button>
                <button type="button" class="btn btn-secondary" style="flex:1;" onclick="hideUnlockModal()">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-trash"></i> Delete Page</h3>
        <p id="deleteMessage"></p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($security->generateCSRFToken()); ?>">
            <input type="hidden" name="action" value="delete_content">
            <input type="hidden" name="page_path" id="delete_page_path">
            
            <div class="form-group" id="passwordGroup" style="display:none;">
                <label>Generation Password (Required for locked content)</label>
                <input type="password" name="delete_password" class="form-control" id="delete_password">
            </div>
            
            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn btn-danger" style="flex:1;">
                    <i class="fas fa-trash"></i> Delete
                </button>
                <button type="button" class="btn btn-secondary" style="flex:1;" onclick="hideDeleteModal()">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showUnlockModal(pageId) {
    document.getElementById('unlock_page_id').value = pageId;
    document.getElementById('unlockModal').style.display = 'flex';
}

function hideUnlockModal() {
    document.getElementById('unlockModal').style.display = 'none';
}

function showDeleteModal(pagePath, pageName, isLocked) {
    document.getElementById('delete_page_path').value = pagePath;
    document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete "' + pageName + '"?';
    
    if (isLocked) {
        document.getElementById('passwordGroup').style.display = 'block';
        document.getElementById('delete_password').required = true;
    } else {
        document.getElementById('passwordGroup').style.display = 'none';
        document.getElementById('delete_password').required = false;
    }
    
    document.getElementById('deleteModal').style.display = 'flex';
}

function hideDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

function toggleAll(checkbox) {
    const checkboxes = document.querySelectorAll('.page-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

function selectAll() {
    const checkboxes = document.querySelectorAll('.page-checkbox');
    checkboxes.forEach(cb => cb.checked = true);
    document.getElementById('selectAllCheckbox').checked = true;
}

function filterTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('contentTable');
    const tr = table.getElementsByTagName('tr');
    
    for (let i = 1; i < tr.length; i++) {
        const td = tr[i].getElementsByTagName('td');
        let found = false;
        
        for (let j = 1; j < td.length - 1; j++) {
            if (td[j]) {
                const txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        tr[i].style.display = found ? '' : 'none';
    }
}

// Close modals on outside click
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
