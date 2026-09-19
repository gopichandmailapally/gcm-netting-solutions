<?php
/**
 * Offers Management
 * Create and manage promotional offers
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();
$success = '';
$error = '';

// Check for session messages from import
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
if (isset($_SESSION['info_message'])) {
    $success = $_SESSION['info_message'];
    unset($_SESSION['info_message']);
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_offer'])) {
        $title = $_POST['title'];
        $subtitle = $_POST['subtitle'];
        $discount_text = $_POST['discount_text'];
        $description = $_POST['description'];
        $badge_text = $_POST['badge_text'];
        $valid_from = $_POST['valid_from'];
        $valid_until = $_POST['valid_until'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $db->execute(
            "INSERT INTO offers (title, subtitle, discount_text, description, badge_text, valid_from, valid_until, is_active) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$title, $subtitle, $discount_text, $description, $badge_text, $valid_from, $valid_until, $is_active],
            'sssssssi'
        );
        
        $success = 'Offer created successfully!';
    }
    
    if (isset($_POST['update_offer'])) {
        $id = (int)$_POST['offer_id'];
        $title = $_POST['title'];
        $subtitle = $_POST['subtitle'];
        $discount_text = $_POST['discount_text'];
        $description = $_POST['description'];
        $badge_text = $_POST['badge_text'];
        $valid_from = $_POST['valid_from'];
        $valid_until = $_POST['valid_until'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $db->execute(
            "UPDATE offers SET title = ?, subtitle = ?, discount_text = ?, description = ?, badge_text = ?, valid_from = ?, valid_until = ?, is_active = ? WHERE id = ?",
            [$title, $subtitle, $discount_text, $description, $badge_text, $valid_from, $valid_until, $is_active, $id],
            'sssssssii'
        );
        
        $success = 'Offer updated successfully!';
    }
    
    if (isset($_POST['toggle_active'])) {
        $id = (int)$_POST['offer_id'];
        $current = (int)$_POST['current_active'];
        $new_active = $current ? 0 : 1;
        
        $db->execute("UPDATE offers SET is_active = ? WHERE id = ?", [$new_active, $id], 'ii');
        $success = $new_active ? 'Offer activated!' : 'Offer deactivated!';
    }
    
    if (isset($_POST['delete_offer'])) {
        $id = (int)$_POST['offer_id'];
        $db->execute("DELETE FROM offers WHERE id = ?", [$id], 'i');
        $success = 'Offer deleted successfully!';
    }
}

// Fetch all offers
$offers = $db->fetchAll("SELECT * FROM offers ORDER BY created_at DESC");

$page_title = 'Offers Management';
require_once '../includes/header.php';
?>

<style>
.offer-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    background: white;
}
.offer-active { border-left: 5px solid #28a745; }
.offer-inactive { border-left: 5px solid #dc3545; }
.offer-expired { border-left: 5px solid #6c757d; opacity: 0.7; }
.coupon-code {
    background: #f8f9fa;
    padding: 10px 20px;
    border: 2px dashed #0066CC;
    border-radius: 5px;
    font-family: monospace;
    font-size: 20px;
    font-weight: bold;
    color: #0066CC;
    display: inline-block;
}
</style>

<div class="content-wrapper">
    <div class="mb-4">
        <h2><i class="fas fa-tags"></i> Promotional Offers Management</h2>
        <p class="text-muted mb-0">Edit your two promotional offers displayed on the website (no coupon codes needed)</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($offers)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No offers created yet. Click "Create New Offer" to get started!
        </div>
    <?php else: ?>
        <?php foreach ($offers as $offer): 
            $is_expired = strtotime($offer['valid_until']) < time();
            $card_class = $is_expired ? 'offer-expired' : ($offer['is_active'] ? 'offer-active' : 'offer-inactive');
        ?>
            <div class="offer-card <?php echo $card_class; ?>">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <?php if (!empty($offer['badge_text'])): ?>
                            <span class="badge bg-warning mb-2"><?php echo htmlspecialchars($offer['badge_text']); ?></span>
                        <?php endif; ?>
                        <h4><?php echo htmlspecialchars($offer['title']); ?></h4>
                        <?php if (!empty($offer['subtitle'])): ?>
                            <p class="text-muted"><?php echo htmlspecialchars($offer['subtitle']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($offer['is_active'] && !$is_expired): ?>
                            <span class="badge bg-success">ACTIVE</span>
                        <?php elseif ($is_expired): ?>
                            <span class="badge bg-secondary">EXPIRED</span>
                        <?php else: ?>
                            <span class="badge bg-danger">INACTIVE</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <h3 class="text-success"><?php echo htmlspecialchars($offer['discount_text']); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars($offer['description'])); ?></p>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong><i class="fas fa-calendar-check"></i> Valid From:</strong><br>
                        <?php echo !empty($offer['valid_from'] ?? '') ? date('F j, Y', strtotime($offer['valid_from'])) : '<em style="color:#999">Not set</em>'; ?>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="fas fa-calendar-times"></i> Valid Until:</strong><br>
                        <?php echo !empty($offer['valid_until'] ?? '') ? date('F j, Y', strtotime($offer['valid_until'])) : '<em style="color:#999">Not set</em>'; ?>
                    </div>
                </div>

                <div class="mt-3">
                    <?php if (!$is_expired): ?>
                        <button class="btn btn-<?php echo $offer['is_active'] ? 'warning' : 'success'; ?> btn-sm" 
                                onclick="toggleActive(<?php echo $offer['id']; ?>, <?php echo $offer['is_active']; ?>)">
                            <i class="fas fa-power-off"></i> <?php echo $offer['is_active'] ? 'Deactivate' : 'Activate'; ?>
                        </button>
                    <?php endif; ?>
                    <button class="btn btn-primary btn-sm" onclick="editOffer(<?php echo $offer['id']; ?>)">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteOffer(<?php echo $offer['id']; ?>)">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Edit Offer Modal -->
<div class="modal fade" id="editOfferModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Offer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editOfferForm">
                <input type="hidden" name="offer_id" id="edit_offer_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label>Offer Title *</label>
                            <input type="text" name="title" id="edit_title" class="form-control" required 
                                   placeholder="e.g., Limited Time Offer!">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Badge Text</label>
                            <input type="text" name="badge_text" id="edit_badge_text" class="form-control" 
                                   placeholder="e.g., Special Deal">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Subtitle</label>
                            <input type="text" name="subtitle" id="edit_subtitle" class="form-control" 
                                   placeholder="e.g., Professional Safety Net Installation">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Discount/Offer Text * (Main promotional message)</label>
                            <input type="text" name="discount_text" id="edit_discount_text" class="form-control" required 
                                   placeholder="e.g., Get 15% OFF on all services">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Description/Features</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3" 
                                      placeholder="e.g., Free Inspection, Same Day Service, 5 Year Warranty, Quality Materials"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Valid From *</label>
                            <input type="date" name="valid_from" id="edit_valid_from" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Valid Until *</label>
                            <input type="date" name="valid_until" id="edit_valid_until" class="form-control" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="edit_is_active">
                                <label class="form-check-label" for="edit_is_active">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_offer" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Store offers data for editing (with refresh)
const offersData = <?php echo !empty($offers) ? json_encode($offers) : '[]'; ?>;

function editOffer(id) {
    console.log('Edit button clicked for offer ID:', id);
    console.log('Available offers:', offersData);
    
    const offer = offersData.find(o => o.id == id);
    if (!offer) {
        alert('Offer not found! Please refresh the page.');
        console.error('Offer not found for ID:', id);
        return;
    }
    
    console.log('Found offer:', offer);
    
    // Populate form
    document.getElementById('edit_offer_id').value = offer.id;
    document.getElementById('edit_title').value = offer.title || '';
    document.getElementById('edit_badge_text').value = offer.badge_text || '';
    document.getElementById('edit_subtitle').value = offer.subtitle || '';
    document.getElementById('edit_discount_text').value = offer.discount_text || '';
    document.getElementById('edit_description').value = offer.description || '';
    document.getElementById('edit_valid_from').value = offer.valid_from || '';
    document.getElementById('edit_valid_until').value = offer.valid_until || '';
    document.getElementById('edit_is_active').checked = offer.is_active == 1;
    
    console.log('Form populated, showing modal...');
    
    // Show modal
    const modalElement = document.getElementById('editOfferModal');
    if (!modalElement) {
        alert('Modal not found! Please refresh the page.');
        console.error('Modal element not found');
        return;
    }
    
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    
    console.log('Modal shown successfully');
}

function toggleActive(id, current) {
    const action = current ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this offer?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="offer_id" value="${id}">
            <input type="hidden" name="current_active" value="${current}">
            <input type="hidden" name="toggle_active" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteOffer(id) {
    if (confirm('Are you sure you want to delete this offer?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="offer_id" value="${id}">
            <input type="hidden" name="delete_offer" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
