<?php
// Review card template (included in reviews-management.php)
$stars = str_repeat('⭐', $review['rating']);
?>
<div class="review-card review-<?php echo $review['status']; ?>">
    <div class="review-header">
        <div>
            <h5><?php echo htmlspecialchars($review['customer_name']); ?></h5>
            <div class="stars"><?php echo $stars; ?> (<?php echo $review['rating']; ?>/5)</div>
        </div>
        <div>
            <?php if ($review['display_on_website']): ?>
                <span class="badge bg-success"><i class="fas fa-eye"></i> Visible</span>
            <?php else: ?>
                <span class="badge bg-secondary"><i class="fas fa-eye-slash"></i> Hidden</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <strong><i class="fas fa-envelope"></i> Email:</strong><br>
            <?php echo htmlspecialchars($review['email'] ?: 'Not provided'); ?>
        </div>
        <div class="col-md-4">
            <strong><i class="fas fa-phone"></i> Phone:</strong><br>
            <?php echo htmlspecialchars($review['phone'] ?: 'Not provided'); ?>
        </div>
        <div class="col-md-4">
            <strong><i class="fas fa-cogs"></i> Service:</strong><br>
            <?php echo htmlspecialchars($review['service']); ?>
        </div>
    </div>

    <div class="mb-3">
        <strong><i class="fas fa-map-marker-alt"></i> Location:</strong>
        <?php echo htmlspecialchars($review['location']); ?>
    </div>

    <div class="mb-3">
        <strong><i class="fas fa-comment"></i> Review:</strong>
        <p><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
    </div>

    <div class="text-muted small mb-3">
        <i class="fas fa-calendar"></i> Submitted: <?php echo date('F j, Y g:i A', strtotime($review['submitted_at'])); ?>
        <?php if ($review['status'] === 'approved' && $review['approved_at']): ?>
            | Approved: <?php echo date('F j, Y g:i A', strtotime($review['approved_at'])); ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($review['admin_notes'])): ?>
        <div class="alert alert-secondary">
            <strong><i class="fas fa-sticky-note"></i> Admin Notes:</strong>
            <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['admin_notes'])); ?></p>
        </div>
    <?php endif; ?>

    <div class="mt-3">
        <?php if ($review['status'] === 'pending'): ?>
            <button class="btn btn-success btn-sm" onclick="approveReview(<?php echo $review['id']; ?>)">
                <i class="fas fa-check"></i> Approve & Publish
            </button>
            <button class="btn btn-danger btn-sm" onclick="rejectReview(<?php echo $review['id']; ?>)">
                <i class="fas fa-times"></i> Reject
            </button>
        <?php endif; ?>
        
        <?php if ($review['status'] === 'approved'): ?>
            <button class="btn btn-<?php echo $review['display_on_website'] ? 'warning' : 'success'; ?> btn-sm" 
                    onclick="toggleDisplay(<?php echo $review['id']; ?>, <?php echo $review['display_on_website']; ?>)">
                <i class="fas fa-eye<?php echo $review['display_on_website'] ? '-slash' : ''; ?>"></i> 
                <?php echo $review['display_on_website'] ? 'Hide' : 'Show'; ?> on Website
            </button>
        <?php endif; ?>
        
        <button class="btn btn-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#edit-<?php echo $review['id']; ?>">
            <i class="fas fa-edit"></i> Edit
        </button>
        <button class="btn btn-danger btn-sm" onclick="deleteReview(<?php echo $review['id']; ?>)">
            <i class="fas fa-trash"></i> Delete
        </button>
    </div>

    <div class="collapse mt-3" id="edit-<?php echo $review['id']; ?>">
        <div class="card card-body">
            <form method="POST">
                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Customer Name</label>
                        <input type="text" name="customer_name" class="form-control" 
                               value="<?php echo htmlspecialchars($review['customer_name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Rating</label>
                        <select name="rating" class="form-select" required>
                            <?php for($i=1; $i<=5; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $review['rating'] == $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label>Review Text</label>
                        <textarea name="review_text" class="form-control" rows="4" required><?php echo htmlspecialchars($review['review_text']); ?></textarea>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label>Admin Notes (Internal Only)</label>
                        <textarea name="admin_notes" class="form-control" rows="2"><?php echo htmlspecialchars($review['admin_notes']); ?></textarea>
                    </div>
                </div>
                <button type="submit" name="update_review" class="btn btn-success">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
    </div>
</div>
