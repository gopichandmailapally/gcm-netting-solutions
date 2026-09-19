<?php
/**
 * GCM Netting Solutions - FAQs Page
 * Frequently Asked Questions with proper header/footer
 */

define('GCM_INIT', true);
require_once 'config/config.php';
require_once 'config/faq-categories.php';

$page_title = 'Frequently Asked Questions (FAQs)';
$meta_description = 'Get answers to common questions about safety nets, pigeon nets, invisible grills installation, pricing, warranty, and services in Chennai.';
$meta_keywords = 'safety nets faq, pigeon nets questions, installation faq, warranty information, chennai';

// Get all FAQ categories
$categories = get_faq_categories();

// Get selected category from URL
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Load FAQs from JSON files (each FAQ is a separate file)
$all_faqs = [];
$faq_data_dir = __DIR__ . '/data/faqs/';

if (is_dir($faq_data_dir)) {
    $files = glob($faq_data_dir . '*.json');
    foreach ($files as $file) {
        // Skip index and stats files
        $basename = basename($file);
        if ($basename === 'index.json' || $basename === 'stats.json') {
            continue;
        }
        
        $json_content = file_get_contents($file);
        $faq = json_decode($json_content, true);
        
        if ($faq && isset($faq['question']) && isset($faq['answer'])) {
            // Use category_name if available, otherwise use category key
            $faq['category'] = $faq['category_name'] ?? $faq['category'] ?? 'General Information';
            $all_faqs[] = $faq;
        }
    }
}

// Store total count before filtering
$total_faqs_count = count($all_faqs);

// Filter by category if selected
if ($selected_category !== 'all') {
    $all_faqs = array_filter($all_faqs, function($faq) use ($selected_category) {
        return $faq['category'] === $selected_category;
    });
}

// Pagination
$per_page_fq  = 50;
$current_pg   = max(1, (int)($_GET['pg'] ?? 1));
$filtered_fq  = count($all_faqs);
$total_pgs_fq = max(1, (int)ceil($filtered_fq / $per_page_fq));
$current_pg   = min($current_pg, $total_pgs_fq);
$offset_fq    = ($current_pg - 1) * $per_page_fq;
$paged_faqs   = array_slice(array_values($all_faqs), $offset_fq, $per_page_fq);

// Count FAQs per category
$category_counts = [];
foreach ($categories as $category) {
    $category_counts[$category] = 0;
}

// Count from loaded FAQs
if (is_dir($faq_data_dir)) {
    $files = glob($faq_data_dir . '*.json');
    foreach ($files as $file) {
        $basename = basename($file);
        if ($basename === 'index.json' || $basename === 'stats.json') {
            continue;
        }
        
        $json_content = file_get_contents($file);
        $faq = json_decode($json_content, true);
        
        if ($faq && isset($faq['category_name'])) {
            $cat = $faq['category_name'];
            if (isset($category_counts[$cat])) {
                $category_counts[$cat]++;
            }
        }
    }
}

function sanitize_filename($str) {
    return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $str));
}

$current_page = 'faqs';

include 'includes/modern-header.php';
?>

<style>
/* FAQ Page Styles */
.faqs-page {
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

.faq-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 40px;
    margin-top: 30px;
}

/* Sidebar */
.faq-sidebar {
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

.category-list {
    list-style: none;
}

.category-list li {
    margin-bottom: 10px;
}

.category-list a {
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

.category-list a:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    transform: translateX(5px);
}

.category-list a.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
}

.category-count {
    background: rgba(255,255,255,0.3);
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.category-list a.active .category-count {
    background: rgba(255,255,255,0.3);
}

/* FAQ Content */
.faq-content {
    min-height: 400px;
}

.faq-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.faq-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.faq-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.faq-question {
    padding: 18px 20px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
    transition: all 0.3s ease;
}

.faq-question:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
}

.faq-question-content h3 {
    font-size: 15px;
    color: #2d3748;
    font-weight: 600;
    margin-bottom: 6px;
}

.faq-category-badge {
    display: inline-block;
    padding: 4px 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.faq-icon {
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

.faq-card.active .faq-icon {
    transform: rotate(180deg);
}

.faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.faq-answer-content {
    padding: 16px 20px;
    color: #4a5568;
    font-size: 14px;
    line-height: 1.7;
    border-top: 2px solid rgba(102, 126, 234, 0.1);
}

.faq-card.active .faq-answer {
    max-height: 1000px;
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
.pag-info { color: #718096; font-size: 14px; }
.pag-links { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
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
.pag-btn:hover { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); color: white; border-color: transparent; }
.pag-btn.active { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); color: white; border-color: transparent; pointer-events: none; }
.pag-dots { color: #a0aec0; font-size: 14px; padding: 0 4px; }

/* Responsive */
@media (max-width: 992px) {
    .faq-layout {
        grid-template-columns: 1fr;
    }
    .faq-sidebar {
        position: static;
    }
    .faq-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 768px) {
    .page-banner h1 {
        font-size: 32px;
    }
    .faq-question-content h3 {
        font-size: 14px;
    }
    .faq-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="faqs-page">
    <!-- Page Header -->
    <div class="page-banner">
        <div class="container">
            <h1><i class="fas fa-question-circle"></i> Frequently Asked Questions</h1>
            <p>Find answers to common questions about safety nets in Chennai</p>
        </div>
    </div>
    
    <div class="container">
        <!-- FAQ Layout: Sidebar + Content -->
        <div class="faq-layout">
            <!-- Sidebar -->
            <aside class="faq-sidebar">
                <div class="sidebar-card">
                    <h3><i class="fas fa-filter"></i> Categories</h3>
                    <ul class="category-list">
                        <li>
                            <a href="?category=all" class="<?php echo $selected_category === 'all' ? 'active' : ''; ?>">
                                <span><i class="fas fa-th"></i> All Categories</span>
                                <span class="category-count"><?php echo $total_faqs_count; ?></span>
                            </a>
                        </li>
                        <?php foreach ($categories as $category): 
                            $count = $category_counts[$category] ?? 0;
                            if ($count > 0): ?>
                            <li>
                                <a href="?category=<?php echo urlencode($category); ?>" 
                                   class="<?php echo $selected_category === $category ? 'active' : ''; ?>">
                                    <span><?php echo htmlspecialchars($category); ?></span>
                                    <span class="category-count"><?php echo $count; ?></span>
                                </a>
                            </li>
                        <?php endif; endforeach; ?>
                    </ul>
                </div>

                <div class="sidebar-card">
                    <h3><i class="fas fa-phone"></i> Need Help?</h3>
                    <p style="font-size: 14px; color: #718096; margin-bottom: 15px;">
                        Can't find what you're looking for? Contact us directly!
                    </p>
                    <a href="tel:+919912399224" style="display: block; padding: 12px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; text-align: center; border-radius: 10px; text-decoration: none; font-weight: 600; margin-bottom: 10px;">
                        <i class="fas fa-phone-alt"></i> +91 99123 99224
                    </a>
                    <a href="contact.php" style="display: block; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-align: center; border-radius: 10px; text-decoration: none; font-weight: 600;">
                        <i class="fas fa-envelope"></i> Contact Form
                    </a>
                </div>
            </aside>

            <!-- FAQ Content -->
            <div class="faq-content">
                <?php if (empty($all_faqs)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No FAQs Found</h3>
                        <p>
                            <?php if ($selected_category !== 'all'): ?>
                                No FAQs available in this category yet. Try viewing all categories.
                            <?php else: ?>
                                FAQs will appear here once they are generated by the admin.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="faq-grid">
                        <?php foreach ($paged_faqs as $faq): ?>
                            <div class="faq-card">
                                <div class="faq-question" onclick="toggleFAQ(this)">
                                    <div class="faq-question-content">
                                        <div class="faq-category-badge"><?php echo htmlspecialchars($faq['category']); ?></div>
                                        <h3><?php echo htmlspecialchars($faq['question']); ?></h3>
                                    </div>
                                    <div class="faq-icon">
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                </div>
                                <div class="faq-answer">
                                    <div class="faq-answer-content">
                                        <?php echo $faq['answer']; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($total_pgs_fq > 1): ?>
                    <div class="pagination-bar">
                        <span class="pag-info">Showing <?php echo $offset_fq + 1; ?>–<?php echo min($offset_fq + $per_page_fq, $filtered_fq); ?> of <?php echo $filtered_fq; ?> FAQs</span>
                        <div class="pag-links">
                            <?php if ($current_pg > 1): ?>
                                <a href="?category=<?php echo urlencode($selected_category); ?>&pg=<?php echo $current_pg - 1; ?>" class="pag-btn">&#8249; Prev</a>
                            <?php endif;
                            $sp = max(1, $current_pg - 3);
                            $ep = min($total_pgs_fq, $current_pg + 3);
                            if ($sp > 1) echo '<span class="pag-dots">…</span>';
                            for ($pp = $sp; $pp <= $ep; $pp++): ?>
                                <a href="?category=<?php echo urlencode($selected_category); ?>&pg=<?php echo $pp; ?>" class="pag-btn <?php echo $pp == $current_pg ? 'active' : ''; ?>"><?php echo $pp; ?></a>
                            <?php endfor;
                            if ($ep < $total_pgs_fq) echo '<span class="pag-dots">…</span>';
                            if ($current_pg < $total_pgs_fq): ?>
                                <a href="?category=<?php echo urlencode($selected_category); ?>&pg=<?php echo $current_pg + 1; ?>" class="pag-btn">Next &#8250;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleFAQ(element) {
    const card = element.closest('.faq-card');
    const wasActive = card.classList.contains('active');
    
    // Close all FAQs
    document.querySelectorAll('.faq-card').forEach(c => {
        c.classList.remove('active');
    });
    
    // Open clicked FAQ if it wasn't active
    if (!wasActive) {
        card.classList.add('active');
    }
}

// Open first FAQ by default
document.addEventListener('DOMContentLoaded', function() {
    const firstFaq = document.querySelector('.faq-card');
    if (firstFaq) {
        firstFaq.classList.add('active');
    }
});
</script>

<?php include 'includes/modern-footer.php'; ?>
