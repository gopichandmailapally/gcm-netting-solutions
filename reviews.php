<?php
/**
 * GCM Netting Solutions - Reviews Page
 * Customer reviews and testimonials
 */

define('GCM_INIT', true);
require_once 'config/config.php';
require_once 'config/faq-categories.php';

$page_title = 'Customer Reviews & Testimonials';
$meta_description = 'Read customer reviews and testimonials about GCM Netting Solutions installation services in Chennai. Real feedback from satisfied customers.';
$meta_keywords = 'safety nets reviews, customer testimonials, chennai, ratings';

// Get selected category/rating from URL
$selected_filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Load reviews from JSON files (only approved reviews)
$all_reviews = [];
$reviews_dir = __DIR__ . '/data/reviews/';

if (is_dir($reviews_dir)) {
    $files = glob($reviews_dir . '*.json');
    foreach ($files as $file) {
        // Skip stats and pending directory
        $basename = basename($file);
        if (in_array($basename, ['stats.json', 'auto-settings.json']) || is_dir($file)) {
            continue;
        }
        
        $json_content = file_get_contents($file);
        $review = json_decode($json_content, true);
        
        // Only load approved reviews
        if ($review && isset($review['customer_name']) && isset($review['review_text'])) {
            if (isset($review['status']) && $review['status'] === 'approved') {
                $all_reviews[] = $review;
            }
        }
    }
}

// Sort by created_at (newest first)
usort($all_reviews, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Store total count before filtering
$total_reviews_count = count($all_reviews);

// Calculate average rating
$total_rating = 0;
foreach ($all_reviews as $review) {
    $total_rating += $review['rating'];
}
$avg_rating = $total_reviews_count > 0 ? round($total_rating / $total_reviews_count, 1) : 0;

// Count reviews by rating
$rating_counts = [];
for ($i = 5; $i >= 1; $i--) {
    $rating_counts[$i] = 0;
}
foreach ($all_reviews as $review) {
    $rating = $review['rating'];
    if (isset($rating_counts[$rating])) {
        $rating_counts[$rating]++;
    }
}

// Filter by rating if selected
if ($selected_filter !== 'all' && is_numeric($selected_filter)) {
    $all_reviews = array_filter($all_reviews, function($review) use ($selected_filter) {
        return $review['rating'] == $selected_filter;
    });
}

// Pagination
$per_page_rv  = 50;
$current_pg   = max(1, (int)($_GET['pg'] ?? 1));
$filtered_rv  = count($all_reviews);
$total_pgs_rv = max(1, (int)ceil($filtered_rv / $per_page_rv));
$current_pg   = min($current_pg, $total_pgs_rv);
$offset_rv    = ($current_pg - 1) * $per_page_rv;
$paged_reviews = array_slice(array_values($all_reviews), $offset_rv, $per_page_rv);

$current_page = 'reviews';

include 'includes/modern-header.php';
?>

<style>
/* Reviews Page Styles - Matching FAQs Design */
.reviews-page {
    background: #f8f9fa;
    min-height: 100vh;
    padding-bottom: 60px;
}

.page-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 80px 0;
    margin-bottom: 50px;
    color: white;
    text-align: center;
}

.page-banner h1 {
    font-size: 48px;
    font-weight: 700;
    margin-bottom: 15px;
}

.page-banner p {
    font-size: 20px;
    opacity: 0.95;
}

.review-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 40px;
    margin-top: 30px;
}

/* Sidebar - Matching FAQs Sidebar */
.review-sidebar {
    position: sticky;
    top: 100px;
    height: fit-content;
}

.sidebar-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.sidebar-card h3 {
    font-size: 18px;
    margin-bottom: 20px;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 10px;
}

.rating-filter-list {
    list-style: none;
}

.rating-filter-list li {
    margin-bottom: 10px;
}

.rating-filter-list a {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #f7fafc;
    border-radius: 10px;
    color: #4a5568;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 14px;
}

.rating-filter-list a:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    transform: translateX(5px);
}

.rating-filter-list a.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
}

.filter-count {
    background: rgba(255,255,255,0.3);
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.rating-filter-list a.active .filter-count {
    background: rgba(255,255,255,0.3);
}

.stars {
    color: #fbbf24;
}

.rating-filter-list a:hover .stars {
    color: white;
}

.rating-filter-list a.active .stars {
    color: white;
}

.avg-rating-box {
    text-align: center;
    padding: 20px;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    border-radius: 10px;
    margin-bottom: 20px;
}

.avg-rating-box .big-rating {
    font-size: 48px;
    font-weight: 700;
    color: #667eea;
    line-height: 1;
}

.avg-rating-box .stars {
    font-size: 24px;
    margin: 10px 0;
}

.avg-rating-box p {
    color: #718096;
    font-size: 14px;
    margin-top: 5px;
}

/* Review Content */
.review-content {
    min-height: 400px;
}

.review-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.review-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.review-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.review-header {
    padding: 18px 20px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 14px;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
    transition: all 0.3s ease;
    cursor: pointer !important;
}

.review-header:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
}

.review-header-content {
    flex: 1;
}

.review-header-content h3 {
    font-size: 16px;
    color: #2d3748;
    font-weight: 600;
    margin-bottom: 6px;
}

.review-rating {
    display: flex;
    gap: 3px;
    margin-bottom: 8px;
}

.review-rating i {
    color: #fbbf24;
    font-size: 16px;
}

.review-rating i.text-gray {
    color: #cbd5e0 !important;
}

.review-meta {
    font-size: 13px;
    color: #718096;
    display: flex;
    gap: 15px;
    margin-top: 5px;
}

.review-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    transition: transform 0.3s ease;
    flex-shrink: 0;
}

.review-icon:hover {
    transform: scale(1.1);
}

.review-card.active .review-icon {
    transform: rotate(180deg);
}

.review-body {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.review-body-content {
    padding: 16px 20px;
    color: #4a5568;
    font-size: 14px;
    line-height: 1.7;
    border-top: 2px solid rgba(102, 126, 234, 0.1);
}

.review-card.active .review-body {
    max-height: 1000px;
}

.verified-badge {
    display: inline-block;
    padding: 4px 12px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-left: 10px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 15px;
}

.empty-state i {
    font-size: 80px;
    color: #cbd5e0;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 24px;
    color: #2d3748;
    margin-bottom: 10px;
}

.empty-state p {
    color: #718096;
    font-size: 16px;
}

/* Pagination */
.pagination-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 30px;
    padding: 20px 0 10px;
    border-top: 2px solid #e2e8f0;
}
.pag-info {
    color: #718096;
    font-size: 14px;
}
.pag-links {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.pag-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 12px;
    border-radius: 10px;
    background: white;
    color: #4a5568;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    border: 2px solid #e2e8f0;
    transition: all 0.2s ease;
}
.pag-btn:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: transparent;
}
.pag-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: transparent;
    pointer-events: none;
}
.pag-dots {
    color: #a0aec0;
    font-size: 14px;
    padding: 0 4px;
}

/* Responsive */
@media (max-width: 992px) {
    .review-layout {
        grid-template-columns: 1fr;
    }
    .review-sidebar {
        position: static;
    }
    .review-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 768px) {
    .page-banner h1 {
        font-size: 32px;
    }
    .review-header-content h3 {
        font-size: 15px;
    }
    .review-grid {
        grid-template-columns: 1fr;
    }
}

/* Arrow icon inherits cursor:pointer from .review-header */
.review-icon { cursor: pointer; }
</style>

<div class="reviews-page">
    <!-- Page Header -->
    <div class="page-banner">
        <div class="container">
            <h1><i class="fas fa-star"></i> Customer Reviews</h1>
            <p>Read what our customers say about us</p>
        </div>
    </div>
    
    <div class="container">
        <!-- Review Layout: Sidebar + Content -->
        <div class="review-layout">
            <!-- Sidebar -->
            <aside class="review-sidebar">
                <div class="sidebar-card">
                    <div class="avg-rating-box">
                        <div class="big-rating"><?php echo $avg_rating; ?></div>
                        <div class="stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?php echo $i <= $avg_rating ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p>Based on <?php echo $total_reviews_count; ?> reviews</p>
                    </div>
                </div>

                <div class="sidebar-card">
                    <h3><i class="fas fa-filter"></i> Filter by Rating</h3>
                    <ul class="rating-filter-list">
                        <li>
                            <a href="?filter=all" class="<?php echo $selected_filter === 'all' ? 'active' : ''; ?>">
                                <span><i class="fas fa-th"></i> All Reviews</span>
                                <span class="filter-count"><?php echo $total_reviews_count; ?></span>
                            </a>
                        </li>
                        <?php for ($i = 5; $i >= 1; $i--): 
                            $count = $rating_counts[$i] ?? 0;
                            if ($count > 0): ?>
                            <li>
                                <a href="?filter=<?php echo $i; ?>" 
                                   class="<?php echo $selected_filter == $i ? 'active' : ''; ?>">
                                    <span class="stars">
                                        <?php for ($j = 1; $j <= $i; $j++): ?>
                                            <i class="fas fa-star"></i>
                                        <?php endfor; ?>
                                    </span>
                                    <span class="filter-count"><?php echo $count; ?></span>
                                </a>
                            </li>
                        <?php endif; endfor; ?>
                    </ul>
                </div>

                <div class="sidebar-card">
                    <h3><i class="fas fa-phone"></i> Need Help?</h3>
                    <p style="font-size: 14px; color: #718096; margin-bottom: 15px;">
                        Can't find what you're looking for? Contact us directly!
                    </p>
                    <a href="tel:+919121399234" style="display: block; padding: 12px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; text-align: center; border-radius: 10px; text-decoration: none; font-weight: 600; margin-bottom: 10px;">
                        <i class="fas fa-phone-alt"></i> +91 91213 99234
                    </a>
                    <a href="contact.php" style="display: block; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-align: center; border-radius: 10px; text-decoration: none; font-weight: 600;">
                        <i class="fas fa-envelope"></i> Contact Form
                    </a>
                </div>

                <!-- Write a Review CTA -->
                <div class="sidebar-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h3 style="color: white; margin-bottom: 12px;"><i class="fas fa-pen"></i> Share Your Experience</h3>
                    <p style="font-size: 14px; opacity: 0.9; margin-bottom: 15px;">Used our services? Let others know what you think!</p>
                    <a href="#write-review" onclick="document.getElementById('write-review').scrollIntoView({behavior:'smooth'}); return false;"
                       style="display: block; padding: 12px; background: white; color: #667eea; text-align: center; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 15px;">
                        <i class="fas fa-star"></i> Write a Review
                    </a>
                </div>
            </aside>

            <!-- Review Content -->
            <div class="review-content">

                <!-- ── Write a Review Form ─────────────────────────────── -->
                <div id="write-review" style="margin-bottom: 40px;">
                    <div style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 8px 30px rgba(0,0,0,0.1);">
                        <div style="text-align: center; margin-bottom: 30px;">
                            <h2 style="font-size: 28px; color: #2d3748; margin-bottom: 8px;">
                                <i class="fas fa-pen" style="color: #667eea;"></i> Write a Review
                            </h2>
                            <p style="color: #718096; font-size: 16px;">Share your experience with GCM Netting Solutions. Your review helps others make better decisions.</p>
                        </div>

                        <!-- Success / Error messages -->
                        <div id="review-success" style="display:none; background: #d1fae5; color: #065f46; padding: 18px 22px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; font-size: 16px; text-align: center;">
                            <i class="fas fa-check-circle"></i> <span id="review-success-text"></span>
                        </div>
                        <div id="review-error" style="display:none; background: #fee2e2; color: #991b1b; padding: 14px 18px; border-radius: 10px; margin-bottom: 20px;">
                            <i class="fas fa-exclamation-circle"></i> <span id="review-error-text"></span>
                        </div>

                        <form id="reviewForm" novalidate>
                            <!-- Honeypot -->
                            <input type="text" name="website" style="display:none;" tabindex="-1" autocomplete="off">

                            <!-- Star Rating -->
                            <div style="margin-bottom: 25px; text-align: center;">
                                <label style="display: block; font-weight: 700; color: #2d3748; margin-bottom: 12px; font-size: 16px;">Your Rating <span style="color:#e53e3e;">*</span></label>
                                <div id="star-rating" style="display: inline-flex; gap: 8px;">
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <i class="fas fa-star review-star" data-value="<?php echo $s; ?>"
                                       style="font-size: 36px; color: #e2e8f0; cursor: pointer; transition: color 0.15s, transform 0.15s;"
                                       onmouseover="hoverStars(<?php echo $s; ?>)"
                                       onmouseout="resetStars()"
                                       onclick="selectStar(<?php echo $s; ?>)"></i>
                                    <?php endfor; ?>
                                </div>
                                <input type="hidden" name="rating" id="ratingInput" value="0">
                                <div id="rating-label" style="margin-top: 8px; color: #718096; font-size: 14px; height: 20px;"></div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                                <div>
                                    <label style="display:block; font-weight:600; color:#374151; margin-bottom:6px;">Your Name <span style="color:#e53e3e;">*</span></label>
                                    <input type="text" name="customer_name" placeholder="e.g. Ramesh Kumar"
                                           style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:15px; outline:none; box-sizing:border-box; transition:border-color 0.2s;"
                                           onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'">
                                </div>
                                <div>
                                    <label style="display:block; font-weight:600; color:#374151; margin-bottom:6px;">Location</label>
                                    <input type="text" name="location" placeholder="e.g. Gachibowli, Chennai"
                                           style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:15px; outline:none; box-sizing:border-box; transition:border-color 0.2s;"
                                           onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'">
                                </div>
                            </div>

                            <div style="margin-bottom: 16px;">
                                <label style="display:block; font-weight:600; color:#374151; margin-bottom:6px;">Service Used <span style="color:#e53e3e;">*</span></label>
                                <select name="service"
                                        style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:15px; outline:none; box-sizing:border-box; transition:border-color 0.2s; background:white;"
                                        onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'">
                                    <option value="">Select Service *</option>
                                    <?php foreach (FAQ_CATEGORIES as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div style="margin-bottom: 16px;">
                                <label style="display:block; font-weight:600; color:#374151; margin-bottom:6px;">Your Review <span style="color:#e53e3e;">*</span></label>
                                <textarea name="review_text" rows="5" placeholder="Please share your experience with our service, installation quality, team behaviour, etc. (minimum 20 characters)"
                                          style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:15px; outline:none; box-sizing:border-box; resize:vertical; font-family:inherit; transition:border-color 0.2s;"
                                          onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
                                <div id="char-count" style="text-align:right; font-size:12px; color:#a0aec0; margin-top:4px;">0 / 20 minimum</div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                                <div>
                                    <label style="display:block; font-weight:600; color:#374151; margin-bottom:6px;">Email <span style="color:#a0aec0; font-weight:400;">(optional)</span></label>
                                    <input type="email" name="email" placeholder="your@email.com"
                                           style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:15px; outline:none; box-sizing:border-box; transition:border-color 0.2s;"
                                           onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'">
                                </div>
                                <div>
                                    <label style="display:block; font-weight:600; color:#374151; margin-bottom:6px;">Phone <span style="color:#a0aec0; font-weight:400;">(optional)</span></label>
                                    <input type="tel" name="phone" placeholder="10-digit mobile number"
                                           style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:15px; outline:none; box-sizing:border-box; transition:border-color 0.2s;"
                                           onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'">
                                </div>
                            </div>

                            <p style="font-size: 13px; color: #a0aec0; margin-bottom: 20px; text-align: center;">
                                <i class="fas fa-shield-alt"></i> Your review will be published after admin verification (usually within 24 hours). Your email/phone will not be shown publicly.
                            </p>

                            <button type="submit" id="submitReviewBtn"
                                    style="width:100%; padding:15px; background:linear-gradient(135deg,#667eea,#764ba2); color:white; border:none; border-radius:12px; font-size:17px; font-weight:700; cursor:pointer; transition:opacity 0.2s, transform 0.1s;">
                                <i class="fas fa-paper-plane"></i> Submit My Review
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Customer Reviews -->
                <div style="margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 14px;">
                    <h2 style="font-size: 22px; color: #2d3748; margin: 0;">
                        <i class="fas fa-comments" style="color: #667eea;"></i> Customer Reviews
                    </h2>
                </div>

                <?php if (empty($all_reviews)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Reviews Found</h3>
                        <p>
                            <?php if ($selected_filter !== 'all'): ?>
                                No reviews with this rating yet. Try viewing all reviews.
                            <?php else: ?>
                                Reviews will appear here once they are approved.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="review-grid">
                        <?php foreach ($paged_reviews as $review): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div class="review-header-content">
                                        <h3>
                                            <?php echo htmlspecialchars($review['customer_name']); ?>
                                            <?php if ($review['verified'] ?? false): ?>
                                                <span class="verified-badge"><i class="fas fa-check-circle"></i> Verified</span>
                                            <?php endif; ?>
                                        </h3>
                                        <div class="review-rating">
                                            <?php 
                                            $rating = intval($review['rating']); 
                                            for ($i = 1; $i <= 5; $i++): 
                                            ?>
                                                <i class="fas fa-star<?php echo ($i <= $rating) ? '' : ' text-gray'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="review-meta">
                                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($review['location'] ?? 'Chennai'); ?></span>
                                            <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="review-icon">
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                </div>
                                <div class="review-body">
                                    <div class="review-body-content">
                                        <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                                        <?php if (!empty($review['category'])): ?>
                                            <p style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e2e8f0; color: #718096;">
                                                <strong>Service:</strong> <?php echo htmlspecialchars($review['category']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($total_pgs_rv > 1): ?>
                    <div class="pagination-bar">
                        <span class="pag-info">Showing <?php echo $offset_rv + 1; ?>–<?php echo min($offset_rv + $per_page_rv, $filtered_rv); ?> of <?php echo $filtered_rv; ?> reviews</span>
                        <div class="pag-links">
                            <?php if ($current_pg > 1): ?>
                                <a href="?filter=<?php echo urlencode($selected_filter); ?>&pg=<?php echo $current_pg - 1; ?>" class="pag-btn">&#8249; Prev</a>
                            <?php endif;
                            $sp = max(1, $current_pg - 3);
                            $ep = min($total_pgs_rv, $current_pg + 3);
                            if ($sp > 1) echo '<span class="pag-dots">…</span>';
                            for ($pp = $sp; $pp <= $ep; $pp++): ?>
                                <a href="?filter=<?php echo urlencode($selected_filter); ?>&pg=<?php echo $pp; ?>" class="pag-btn <?php echo $pp == $current_pg ? 'active' : ''; ?>"><?php echo $pp; ?></a>
                            <?php endfor;
                            if ($ep < $total_pgs_rv) echo '<span class="pag-dots">…</span>';
                            if ($current_pg < $total_pgs_rv): ?>
                                <a href="?filter=<?php echo urlencode($selected_filter); ?>&pg=<?php echo $current_pg + 1; ?>" class="pag-btn">Next &#8250;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                <?php endif; ?>

            </div><!-- /.review-content -->
        </div>
    </div>
</div>

<script>
// ── Star Rating ──────────────────────────────────────────────────
const ratingLabels = {1:'Poor',2:'Fair',3:'Good',4:'Very Good',5:'Excellent!'};
let selectedRating = 0;

function hoverStars(n) {
    document.querySelectorAll('.review-star').forEach((s,i) => {
        s.style.color = i < n ? '#f59e0b' : '#e2e8f0';
        s.style.transform = i < n ? 'scale(1.1)' : 'scale(1)';
    });
    const lbl = document.getElementById('rating-label');
    if (lbl) lbl.textContent = ratingLabels[n] || '';
}

function resetStars() {
    document.querySelectorAll('.review-star').forEach((s,i) => {
        s.style.color = i < selectedRating ? '#f59e0b' : '#e2e8f0';
        s.style.transform = 'scale(1)';
    });
    const lbl = document.getElementById('rating-label');
    if (lbl) lbl.textContent = selectedRating ? ratingLabels[selectedRating] : '';
}

function selectStar(n) {
    selectedRating = n;
    const ri = document.getElementById('ratingInput');
    if (ri) ri.value = n;
    resetStars();
}

function showReviewError(msg) {
    const el = document.getElementById('review-error-text');
    const box = document.getElementById('review-error');
    if (el) el.textContent = msg;
    if (box) { box.style.display = 'block'; box.scrollIntoView({behavior:'smooth', block:'center'}); }
}

// ── Review accordion — event delegation (no inline onclick needed) ──
document.addEventListener('click', function(e) {
    const header = e.target.closest('.review-header');
    if (!header) return;
    const card = header.closest('.review-card');
    if (!card) return;
    const wasActive = card.classList.contains('active');
    document.querySelectorAll('.review-card').forEach(c => c.classList.remove('active'));
    if (!wasActive) card.classList.add('active');
});

// ── DOM-ready: form wiring + open first card ──────────────────────
document.addEventListener('DOMContentLoaded', function() {

    // Open first review by default
    const firstReview = document.querySelector('.review-card');
    if (firstReview) firstReview.classList.add('active');

    // Char counter
    const rtEl = document.querySelector('[name="review_text"]');
    if (rtEl) {
        rtEl.addEventListener('input', function() {
            const len = this.value.length;
            const el  = document.getElementById('char-count');
            if (el) {
                el.textContent = len + (len < 20 ? ' / 20 minimum' : ' characters');
                el.style.color = len >= 20 ? '#10b981' : '#a0aec0';
            }
        });
    }

    // Form submit
    const form = document.getElementById('reviewForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('submitReviewBtn');
            const successBox = document.getElementById('review-success');
            const errorBox   = document.getElementById('review-error');

            if (successBox) successBox.style.display = 'none';
            if (errorBox)   errorBox.style.display   = 'none';

            if (selectedRating === 0) {
                showReviewError('Please select a star rating.');
                const sr = document.getElementById('star-rating');
                if (sr) sr.scrollIntoView({behavior:'smooth', block:'center'});
                return;
            }

            const fd = new FormData(this);
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting…'; }

            fetch('api/submit-review.php', {method:'POST', body:fd})
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const st = document.getElementById('review-success-text');
                        if (st) st.textContent = data.message;
                        if (successBox) { successBox.style.display = 'block'; successBox.scrollIntoView({behavior:'smooth', block:'center'}); }
                        form.reset();
                        selectedRating = 0;
                        resetStars();
                        const cc = document.getElementById('char-count');
                        if (cc) { cc.textContent = '0 / 20 minimum'; cc.style.color = '#a0aec0'; }
                    } else {
                        showReviewError(data.message || 'Something went wrong. Please try again.');
                        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit My Review'; }
                    }
                })
                .catch(() => {
                    showReviewError('Network error. Please check your connection and try again.');
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit My Review'; }
                });
        });
    }
});
</script>

<?php include 'includes/modern-footer.php'; ?>
