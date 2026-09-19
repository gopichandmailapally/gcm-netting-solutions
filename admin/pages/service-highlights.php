<?php
/**
 * Service Highlights Management
 * Manage homepage service highlights (6-10 items)
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Add new highlight
        if (isset($_POST['add_highlight'])) {
            if (empty($_POST['title'])) {
                throw new Exception("Title is required");
            }
            
            $db->execute("
                INSERT INTO service_highlights (title, icon_class, description, highlight_color, display_order, is_active)
                VALUES (?, ?, ?, ?, ?, ?)
            ", [
                $_POST['title'],
                $_POST['icon_class'] ?? 'fas fa-star',
                $_POST['description'] ?? '',
                $_POST['highlight_color'] ?? '#667eea',
                $_POST['display_order'] ?? 0,
                isset($_POST['is_active']) ? 1 : 0
            ]);
            
            $_SESSION['success_message'] = "Highlight added successfully!";
        }
        
        // Update highlight
        if (isset($_POST['update_highlight'])) {
            if (empty($_POST['title']) || empty($_POST['id'])) {
                throw new Exception("Title and ID are required");
            }
            
            $db->execute("
                UPDATE service_highlights 
                SET title = ?, icon_class = ?, description = ?, highlight_color = ?, display_order = ?, is_active = ?
                WHERE id = ?
            ", [
                $_POST['title'],
                $_POST['icon_class'] ?? 'fas fa-star',
                $_POST['description'] ?? '',
                $_POST['highlight_color'] ?? '#667eea',
                $_POST['display_order'] ?? 0,
                isset($_POST['is_active']) ? 1 : 0,
                $_POST['id']
            ]);
            
            $_SESSION['success_message'] = "Highlight updated successfully!";
        }
        
        // Delete highlight
        if (isset($_POST['delete_highlight'])) {
            if (empty($_POST['id'])) {
                throw new Exception("ID is required");
            }
            
            $db->execute("DELETE FROM service_highlights WHERE id = ?", [$_POST['id']]);
            $_SESSION['success_message'] = "Highlight deleted successfully!";
        }
        
        // Toggle active status
        if (isset($_POST['toggle_active'])) {
            if (empty($_POST['id'])) {
                throw new Exception("ID is required");
            }
            
            $db->execute("UPDATE service_highlights SET is_active = NOT is_active WHERE id = ?", [$_POST['id']]);
            $_SESSION['success_message'] = "Status updated successfully!";
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Get all highlights
$highlights = $db->fetchAll("SELECT * FROM service_highlights ORDER BY display_order ASC, id ASC");

$page_title = 'Service Highlights';
include '../includes/header.php';
?>

<div class="seo-page">

<!-- Hero -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-star"></i> Service Highlights</h1>
        <p>Manage homepage service highlights &mdash; shown below the hero slider</p>
    </div>
    <div class="hero-actions">
        <a href="../dashboard.php" class="btn-sh gray sm"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>
</div>

<?php if (isset($_SESSION['success_message'])): ?>
<div class="sh-notice success"><div class="sh-notice-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="fas fa-check-circle"></i></div><span><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span></div>
<?php endif; ?>
<?php if (isset($_SESSION['error_message'])): ?>
<div class="sh-notice error"><div class="sh-notice-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626);"><i class="fas fa-exclamation-circle"></i></div><span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span></div>
<?php endif; ?>

<div class="sh-notice info">
    <div class="sh-notice-icon" style="background:linear-gradient(135deg,#3b82f6,#2563eb);"><i class="fas fa-info-circle"></i></div>
    <span><strong>Note:</strong> These highlights replace the offers section on the homepage. Recommended: 6-10 active highlights. They appear in order below the hero slider.</span>
</div>

<!-- Section 1: Add New Highlight -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num green">1</div>
        <h2><i class="fas fa-plus-circle" style="color:#10b981;margin-right:8px;"></i>Add New Highlight</h2>
    </div>
    <div class="seo-section-body">
        <form method="POST">
            <input type="hidden" name="add_highlight" value="1">
            <div class="form-row">
                <div class="form-group">
                    <label>Title <span class="req">*</span></label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g., 15+ Years Experience">
                </div>
                <div class="form-group">
                    <label>Icon Class</label>
                    <input type="text" name="icon_class" class="form-control" value="fas fa-star" placeholder="e.g., fas fa-award">
                    <small class="form-hint">Font Awesome icon. Browse: <a href="https://fontawesome.com/icons" target="_blank">fontawesome.com/icons</a></small>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Short description about this highlight"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Highlight Color</label>
                    <input type="color" name="highlight_color" class="form-control color-input" value="#667eea">
                </div>
                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order" class="form-control" value="0" min="0">
                    <small class="form-hint">Lower numbers appear first</small>
                </div>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" checked>
                    <span>Active (show on homepage)</span>
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-sh green"><i class="fas fa-plus"></i> Add Highlight</button>
            </div>
        </form>
    </div>
</div>

<!-- Section 2: Current Highlights -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num blue">2</div>
        <h2><i class="fas fa-list" style="color:#3b82f6;margin-right:8px;"></i>Current Highlights</h2>
        <span class="sec-badge"><?php echo count($highlights); ?> total</span>
    </div>
    <div class="seo-section-body">
        <?php if (empty($highlights)): ?>
        <div class="empty-state">
            <i class="fas fa-star"></i>
            <h3>No Highlights Yet</h3>
            <p>Add your first service highlight above</p>
        </div>
        <?php else: ?>
        <div class="highlights-list">
            <?php foreach ($highlights as $highlight): ?>
            <div class="hl-row <?php echo $highlight['is_active'] ? 'active' : 'inactive'; ?>">
                <div class="hl-icon" style="background:<?php echo htmlspecialchars($highlight['highlight_color']); ?>;">
                    <i class="<?php echo htmlspecialchars($highlight['icon_class']); ?>"></i>
                </div>
                <div class="hl-content">
                    <div class="hl-title-row">
                        <h3><?php echo htmlspecialchars($highlight['title']); ?></h3>
                        <span class="hl-status <?php echo $highlight['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $highlight['is_active'] ? 'Active' : 'Inactive'; ?></span>
                        <span class="hl-order">Order: <?php echo $highlight['display_order']; ?></span>
                    </div>
                    <?php if (!empty($highlight['description'])): ?>
                    <p class="hl-desc"><?php echo htmlspecialchars($highlight['description']); ?></p>
                    <?php endif; ?>
                    <div class="hl-meta"><i class="<?php echo htmlspecialchars($highlight['icon_class']); ?>"></i> <?php echo htmlspecialchars($highlight['icon_class']); ?> &bull; Color: <?php echo htmlspecialchars($highlight['highlight_color']); ?></div>
                </div>
                <div class="hl-actions">
                    <button onclick="editHighlight(<?php echo htmlspecialchars(json_encode($highlight)); ?>)" class="hl-btn edit" title="Edit"><i class="fas fa-edit"></i></button>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="toggle_active" value="1">
                        <input type="hidden" name="id" value="<?php echo $highlight['id']; ?>">
                        <button type="submit" class="hl-btn <?php echo $highlight['is_active'] ? 'warn' : 'activate'; ?>" title="<?php echo $highlight['is_active'] ? 'Deactivate' : 'Activate'; ?>"><i class="fas fa-<?php echo $highlight['is_active'] ? 'eye-slash' : 'eye'; ?>"></i></button>
                    </form>
                    <button onclick="deleteHighlight(<?php echo $highlight['id']; ?>, '<?php echo htmlspecialchars($highlight['title'],ENT_QUOTES); ?>')" class="hl-btn del" title="Delete"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bottom nav -->
<div class="action-row-bottom">
    <a href="../dashboard.php" class="btn-sh gray"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

</div>

<!-- Edit Modal -->
<div id="editModal" class="sh-modal-overlay">
    <div class="sh-modal-box">
        <div class="sh-modal-head">
            <div class="sh-modal-title">
                <div class="sh-modal-icon"><i class="fas fa-edit"></i></div>
                <div><h3>Edit Highlight</h3><p>Update this service highlight</p></div>
            </div>
            <button class="sh-modal-close" onclick="closeEditModal()">&#x2715;</button>
        </div>
        <div class="sh-modal-body">
            <form method="POST" id="editForm">
                <input type="hidden" name="update_highlight" value="1">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Title <span class="req">*</span></label>
                    <input type="text" name="title" id="edit_title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Icon Class</label>
                    <input type="text" name="icon_class" id="edit_icon_class" class="form-control">
                    <small class="form-hint">Font Awesome icon class</small>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Highlight Color</label>
                        <input type="color" name="highlight_color" id="edit_highlight_color" class="form-control color-input">
                    </div>
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" name="display_order" id="edit_display_order" class="form-control" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" id="edit_is_active">
                        <span>Active (show on homepage)</span>
                    </label>
                </div>
                <div class="sh-modal-footer">
                    <button type="button" class="btn-sh gray" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-sh green"><i class="fas fa-save"></i> Update Highlight</button>
                </div>
            </form>
        </div>
    </div>
</div>
    
<style>
/* ═══ Service Highlights — Complete SEO System Theme ═══ */
@keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}

.seo-page{padding:0;}
.seo-hero{background:white;border-radius:18px;box-shadow:0 4px 24px rgba(0,0,0,.07);padding:32px 36px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;gap:20px;border-left:6px solid transparent;border-image:linear-gradient(180deg,#f59e0b,#10b981) 1;}
.seo-hero-left h1{font-size:32px;font-weight:800;color:#1e293b;margin:0 0 6px;}
.seo-hero-left h1 i{color:#f59e0b;margin-right:10px;}
.seo-hero-left p{color:#64748b;font-size:15px;margin:0;}
.hero-actions{display:flex;gap:12px;flex-shrink:0;flex-wrap:wrap;}

.sh-notice{display:flex;align-items:center;gap:14px;border-radius:14px;border:1.5px solid;padding:14px 20px;margin-bottom:18px;font-size:14px;}
.sh-notice.success{background:#f0fdf4;border-color:#86efac;color:#065f46;}
.sh-notice.error{background:#fef2f2;border-color:#fca5a5;color:#991b1b;}
.sh-notice.info{background:#eff6ff;border-color:#93c5fd;color:#1e40af;}
.sh-notice-icon{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;color:white;font-size:16px;flex-shrink:0;}

.seo-section{background:white;border-radius:18px;box-shadow:0 4px 20px rgba(0,0,0,.07);margin-bottom:24px;overflow:hidden;}
.seo-section-head{padding:22px 32px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
.sec-num{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:800;color:white;flex-shrink:0;}
.sec-num.green{background:linear-gradient(135deg,#10b981,#059669);}
.sec-num.blue{background:linear-gradient(135deg,#3b82f6,#2563eb);}
.seo-section-head h2{font-size:20px;font-weight:700;color:#1e293b;margin:0;}
.sec-badge{background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;}
.seo-section-body{padding:28px 32px;}

.form-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:4px;}
.form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:16px;}
.form-group label{font-size:13px;font-weight:600;color:#374151;}
.form-control{padding:10px 14px;border:2px solid #e2e8f0;border-radius:8px;font-size:14px;color:#1e293b;outline:none;transition:border-color .2s;}
.form-control:focus{border-color:#3b82f6;}
.color-input{height:44px;padding:4px 8px;cursor:pointer;}
.form-hint{font-size:12px;color:#64748b;}
.form-hint a{color:#3b82f6;font-weight:600;text-decoration:none;}
.req{color:#ef4444;}
.checkbox-label{display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;font-weight:500;color:#374151;}
.form-actions{padding-top:4px;}

.btn-sh{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;text-decoration:none;transition:all .25s;white-space:nowrap;}
.btn-sh:hover{transform:translateY(-2px);text-decoration:none;}
.btn-sh.green{background:linear-gradient(135deg,#10b981,#059669);color:white;box-shadow:0 4px 14px rgba(16,185,129,.35);}
.btn-sh.green:hover{box-shadow:0 8px 24px rgba(16,185,129,.45);}
.btn-sh.gray{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;box-shadow:none;}
.btn-sh.gray:hover{background:#e2e8f0;transform:none;}
.btn-sh.sm{padding:9px 16px;font-size:13px;}
.action-row-bottom{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:32px;}

.empty-state{text-align:center;padding:48px 20px;}
.empty-state i{font-size:56px;color:#fef08a;display:block;margin-bottom:14px;animation:float 3s ease-in-out infinite;}
.empty-state h3{font-size:18px;font-weight:700;color:#1e293b;margin:0 0 6px;}
.empty-state p{font-size:13px;color:#64748b;margin:0;}

.highlights-list{display:grid;gap:16px;}
.hl-row{display:flex;align-items:flex-start;gap:18px;padding:20px 24px;border-radius:14px;border:2px solid #e2e8f0;background:#fafbfc;transition:all .2s;}
.hl-row.active{border-color:#86efac;background:#f0fdf4;}
.hl-row:hover{box-shadow:0 4px 16px rgba(0,0,0,.07);}
.hl-icon{width:60px;height:60px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:white;font-size:24px;flex-shrink:0;}
.hl-content{flex:1;min-width:0;}
.hl-title-row{display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap;}
.hl-title-row h3{margin:0;font-size:17px;font-weight:700;color:#1e293b;}
.hl-status{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.hl-status.active{background:#dcfce7;color:#166534;}
.hl-status.inactive{background:#f1f5f9;color:#64748b;}
.hl-order{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:#eff6ff;color:#1d4ed8;}
.hl-desc{font-size:13px;color:#64748b;margin:0 0 8px;}
.hl-meta{font-size:11px;color:#94a3b8;}
.hl-actions{display:flex;gap:8px;flex-shrink:0;}
.hl-btn{width:36px;height:36px;border:none;border-radius:8px;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;transition:all .2s;}
.hl-btn.edit{background:#eff6ff;color:#3b82f6;}
.hl-btn.edit:hover{background:#dbeafe;}
.hl-btn.warn{background:#fef3c7;color:#d97706;}
.hl-btn.warn:hover{background:#fde68a;}
.hl-btn.activate{background:#f0fdf4;color:#059669;}
.hl-btn.activate:hover{background:#dcfce7;}
.hl-btn.del{background:#fef2f2;color:#ef4444;}
.hl-btn.del:hover{background:#fee2e2;}

.sh-modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.65);backdrop-filter:blur(4px);z-index:9999;align-items:center;justify-content:center;padding:20px;}
.sh-modal-overlay.active{display:flex;}
.sh-modal-box{background:white;border-radius:20px;width:100%;max-width:560px;box-shadow:0 24px 80px rgba(0,0,0,.3);overflow:hidden;max-height:90vh;overflow-y:auto;animation:fadeInUp .3s ease;}
.sh-modal-head{background:linear-gradient(135deg,#f59e0b,#d97706);padding:22px 24px;display:flex;justify-content:space-between;align-items:center;}
.sh-modal-title{display:flex;align-items:center;gap:14px;}
.sh-modal-icon{width:44px;height:44px;background:rgba(255,255,255,.2);border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;font-size:20px;}
.sh-modal-title h3{margin:0 0 3px;font-size:18px;font-weight:700;color:white;}
.sh-modal-title p{margin:0;font-size:12px;color:rgba(255,255,255,.75);}
.sh-modal-close{background:rgba(255,255,255,.2);border:none;width:32px;height:32px;border-radius:50%;cursor:pointer;color:white;font-size:16px;display:flex;align-items:center;justify-content:center;transition:background .2s;}
.sh-modal-close:hover{background:rgba(255,255,255,.35);}
.sh-modal-body{padding:28px;}
.sh-modal-footer{display:flex;gap:12px;justify-content:flex-end;margin-top:24px;padding-top:20px;border-top:2px solid #f1f5f9;}

@media(max-width:768px){
    .form-row{grid-template-columns:1fr;}
    .hl-row{flex-wrap:wrap;}
}
</style>

    <script>
    function editHighlight(highlight) {
        document.getElementById('edit_id').value = highlight.id;
        document.getElementById('edit_title').value = highlight.title;
        document.getElementById('edit_icon_class').value = highlight.icon_class;
        document.getElementById('edit_description').value = highlight.description || '';
        document.getElementById('edit_highlight_color').value = highlight.highlight_color;
        document.getElementById('edit_display_order').value = highlight.display_order;
        document.getElementById('edit_is_active').checked = highlight.is_active == 1;
        document.getElementById('editModal').classList.add('active');
    }
    
    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
    }
    
    function deleteHighlight(id, title) {
        if (!confirm(`Are you sure you want to delete "${title}"?`)) {
            return;
        }
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="delete_highlight" value="1">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
    </script>
<?php include '../includes/footer.php'; ?>
