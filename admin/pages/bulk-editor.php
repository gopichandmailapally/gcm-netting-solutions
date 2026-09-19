<?php
/**
 * Bulk Page Editor
 * Fix Google Search Console errors and bulk edit pages
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Bulk Editor';
include '../includes/header.php';

// Get statistics (database not required - using default values)
$total_pages = ['count' => 0];
$total_blogs = ['count' => 0];
?>

<div class="bulk-editor">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h1><i class="fas fa-edit"></i> Bulk Page Editor</h1>
            <p>Fix Google Search Console errors and bulk edit content</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="showModal('findReplace')">
                <i class="fas fa-search"></i> Find & Replace
            </button>
        </div>
    </div>
    
    <!-- Quick Actions Grid -->
    <div class="quick-actions-grid">
        <!-- Fix Meta Descriptions -->
        <div class="action-card" onclick="showModal('metaDescriptions')">
            <div class="action-icon purple">
                <i class="fas fa-file-alt"></i>
            </div>
            <h3>Fix Meta Descriptions</h3>
            <p>Add missing meta descriptions to pages</p>
            <span class="action-arrow">→</span>
        </div>
        
        <!-- Fix Canonical URLs -->
        <div class="action-card" onclick="showModal('canonicalUrls')">
            <div class="action-icon blue">
                <i class="fas fa-link"></i>
            </div>
            <h3>Fix Canonical URLs</h3>
            <p>Add canonical tags to prevent duplicates</p>
            <span class="action-arrow">→</span>
        </div>
        
        <!-- Fix 404 Errors -->
        <div class="action-card" onclick="showModal('fix404')">
            <div class="action-icon red">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Fix 404 Errors</h3>
            <p>Create redirects for broken URLs</p>
            <span class="action-arrow">→</span>
        </div>
        
        <!-- Update Schema Markup -->
        <div class="action-card" onclick="showModal('schemaMarkup')">
            <div class="action-icon green">
                <i class="fas fa-code"></i>
            </div>
            <h3>Update Schema Markup</h3>
            <p>Add or update structured data</p>
            <span class="action-arrow">→</span>
        </div>
        
        <!-- Bulk Find & Replace -->
        <div class="action-card" onclick="showModal('findReplace')">
            <div class="action-icon orange">
                <i class="fas fa-search-plus"></i>
            </div>
            <h3>Find & Replace</h3>
            <p>Replace text across all pages</p>
            <span class="action-arrow">→</span>
        </div>
        
        <!-- Fix Mobile Usability -->
        <div class="action-card" onclick="showModal('mobileUsability')">
            <div class="action-icon teal">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <h3>Mobile Usability</h3>
            <p>Fix mobile-friendly issues</p>
            <span class="action-arrow">→</span>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-row">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fas fa-globe"></i></div>
            <div class="stat-content">
                <h3><?php echo number_format($total_pages['count']); ?></h3>
                <p>Service Pages</p>
            </div>
        </div>
        
        <div class="stat-card purple">
            <div class="stat-icon"><i class="fas fa-blog"></i></div>
            <div class="stat-content">
                <h3><?php echo number_format($total_blogs['count']); ?></h3>
                <p>Blog Posts</p>
            </div>
        </div>
        
        <div class="stat-card green">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content">
                <h3 id="indexedCount">0</h3>
                <p>Indexed Pages</p>
            </div>
        </div>
        
        <div class="stat-card orange">
            <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
            <div class="stat-content">
                <h3 id="errorCount">0</h3>
                <p>GSC Errors</p>
            </div>
        </div>
    </div>
    
    <!-- Recent Bulk Operations -->
    <div class="content-card">
        <div class="card-header">
            <h2><i class="fas fa-history"></i> Recent Bulk Operations</h2>
        </div>
        <div class="card-body">
            <div class="operations-list" id="recentOperations">
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No bulk operations yet</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Find & Replace Modal -->
<div id="findReplaceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-search-plus"></i> Find & Replace</h2>
            <button class="close-modal" onclick="closeModal('findReplace')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="findReplaceForm">
                <div class="form-group">
                    <label><i class="fas fa-search"></i> Find Text</label>
                    <input type="text" name="find_text" class="form-control" placeholder="Text to find..." required>
                    <small>Enter the exact text you want to replace</small>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-edit"></i> Replace With</label>
                    <input type="text" name="replace_text" class="form-control" placeholder="Replacement text...">
                    <small>Leave empty to remove the text</small>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-filter"></i> Apply To</label>
                    <select name="apply_to" class="form-control">
                        <option value="all_pages">All Pages</option>
                        <option value="service_pages">Service Pages Only</option>
                        <option value="blog_posts">Blog Posts Only</option>
                        <option value="specific_pages">Specific Pages</option>
                    </select>
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" name="case_sensitive"> Case Sensitive
                    </label>
                    <label>
                        <input type="checkbox" name="preview_first" checked> Preview Before Applying
                    </label>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('findReplace')">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-play"></i> Execute Replace
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Meta Descriptions Modal -->
<div id="metaDescriptionsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-file-alt"></i> Fix Meta Descriptions</h2>
            <button class="close-modal" onclick="closeModal('metaDescriptions')">&times;</button>
        </div>
        <div class="modal-body">
            <p class="modal-info">
                <i class="fas fa-info-circle"></i>
                AI will automatically generate unique meta descriptions for pages that are missing them.
            </p>
            
            <div class="form-group">
                <label>Target Pages</label>
                <select id="metaTarget" class="form-control">
                    <option value="missing_only">Pages Missing Meta Descriptions</option>
                    <option value="all">All Pages (Regenerate)</option>
                    <option value="service_pages">Service Pages Only</option>
                    <option value="blog_posts">Blog Posts Only</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Description Length</label>
                <input type="number" id="metaLength" class="form-control" value="155" min="120" max="160">
                <small>Recommended: 150-160 characters</small>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('metaDescriptions')">Cancel</button>
                <button type="button" class="btn btn-success" onclick="generateMetaDescriptions()">
                    <i class="fas fa-magic"></i> AI Generate & Apply
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Other Modals (Similar Structure) -->
<div id="canonicalUrlsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-link"></i> Fix Canonical URLs</h2>
            <button class="close-modal" onclick="closeModal('canonicalUrls')">&times;</button>
        </div>
        <div class="modal-body">
            <p class="modal-info">
                <i class="fas fa-info-circle"></i>
                Add canonical tags to prevent duplicate content issues.
            </p>
            
            <div class="form-group">
                <label>Apply To</label>
                <select id="canonicalTarget" class="form-control">
                    <option value="all">All Pages</option>
                    <option value="service_pages">Service Pages</option>
                    <option value="blog_posts">Blog Posts</option>
                </select>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('canonicalUrls')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="addCanonicalUrls()">
                    <i class="fas fa-check"></i> Add Canonical Tags
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.bulk-editor {
    padding: 20px;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.action-card {
    background: linear-gradient(135deg, #FFFFFF 0%, #F8FAFC 100%);
    padding: 30px;
    border-radius: 16px;
    border: 2px solid #E2E8F0;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    overflow: hidden;
}

.action-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
    opacity: 0;
    transition: opacity 0.3s;
}

.action-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    border-color: #3B82F6;
}

.action-card:hover::before {
    opacity: 1;
}

.action-card:active {
    transform: translateY(-4px) scale(0.98);
}

.action-icon {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #FFFFFF;
    margin-bottom: 16px;
    transition: all 0.3s;
}

.action-card:hover .action-icon {
    transform: scale(1.1) rotate(5deg);
}

.action-icon.purple { background: linear-gradient(135deg, #8B5CF6, #7C3AED); }
.action-icon.blue { background: linear-gradient(135deg, #3B82F6, #2563EB); }
.action-icon.red { background: linear-gradient(135deg, #EF4444, #DC2626); }
.action-icon.green { background: linear-gradient(135deg, #10B981, #059669); }
.action-icon.orange { background: linear-gradient(135deg, #F59E0B, #D97706); }
.action-icon.teal { background: linear-gradient(135deg, #14B8A6, #0D9488); }

.action-card h3 {
    font-size: 18px;
    font-weight: 700;
    color: #1E293B;
    margin-bottom: 8px;
}

.action-card p {
    font-size: 14px;
    color: #64748B;
    margin-bottom: 12px;
}

.action-arrow {
    position: absolute;
    bottom: 20px;
    right: 20px;
    font-size: 24px;
    color: #3B82F6;
    opacity: 0;
    transform: translateX(-10px);
    transition: all 0.3s;
}

.action-card:hover .action-arrow {
    opacity: 1;
    transform: translateX(0);
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    animation: fadeIn 0.3s;
}

.modal-content {
    background: #FFFFFF;
    margin: 50px auto;
    max-width: 600px;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from { transform: translateY(50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.modal-header {
    padding: 24px;
    border-bottom: 1px solid #E2E8F0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    font-size: 20px;
    color: #1E293B;
    margin: 0;
}

.close-modal {
    background: none;
    border: none;
    font-size: 28px;
    color: #64748B;
    cursor: pointer;
    transition: all 0.3s;
    width: 36px;
    height: 36px;
    border-radius: 8px;
}

.close-modal:hover {
    background: #F1F5F9;
    color: #1E293B;
    transform: rotate(90deg);
}

.modal-body {
    padding: 24px;
}

.modal-info {
    background: #F0F9FF;
    border-left: 4px solid #3B82F6;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    color: #1E40AF;
}

.modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
}

.checkbox-group {
    display: flex;
    gap: 20px;
}

.checkbox-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: normal;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        margin: 20px;
        max-width: calc(100% - 40px);
    }
    
    .modal-actions {
        flex-direction: column;
    }
    
    .modal-actions .btn {
        width: 100%;
    }
}
</style>

<script>
function showModal(modalName) {
    document.getElementById(modalName + 'Modal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal(modalName) {
    document.getElementById(modalName + 'Modal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Find & Replace
document.getElementById('findReplaceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    if (!confirm('Execute find & replace operation?')) return;
    
    fetch('../api/bulk-find-replace.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Success! Replaced ${data.count} occurrences in ${data.pages} pages.`);
            closeModal('findReplace');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
});

// Generate Meta Descriptions
function generateMetaDescriptions() {
    const target = document.getElementById('metaTarget').value;
    const length = document.getElementById('metaLength').value;
    
    if (!confirm('Generate meta descriptions with AI?')) return;
    
    fetch('../api/generate-meta-descriptions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({target, length})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Success! Generated descriptions for ${data.count} pages.`);
            closeModal('metaDescriptions');
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// Add Canonical URLs
function addCanonicalUrls() {
    const target = document.getElementById('canonicalTarget').value;
    
    if (!confirm('Add canonical tags to pages?')) return;
    
    fetch('../api/add-canonical-urls.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({target})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Success! Added canonical tags to ${data.count} pages.`);
            closeModal('canonicalUrls');
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// Close modal on outside click
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
