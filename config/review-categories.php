<?php
/**
 * Review Categories Configuration
 * Categories for customer reviews and testimonials
 */

if (!defined('GCM_INIT')) {
    define('GCM_INIT', true);
}

// Define all Review categories
define('REVIEW_CATEGORIES', [
    'Customer Service',
    'Installation Quality',
    'Product Quality',
    'Site Management',
    'Children Safety Nets',
    'Pigeon Safety Nets',
    'Balcony Safety Nets',
    'Sports Nets',
    'Invisible Grills',
    'Cloth Hangers',
    'Construction Safety Nets',
    'Bird Netting',
    'Staircase Safety Nets',
    'Window Safety Nets',
    'Timeliness',
    'Pricing & Value',
    'Warranty Coverage',
    'Professional Service',
    'Overall Experience',
    'Material Quality',
    'Durability',
    'Emergency Service',
    'Commercial Projects',
    'Residential Projects',
    'After Sales Support'
]);

/**
 * Get all review categories as array
 */
function get_review_categories() {
    return REVIEW_CATEGORIES;
}

/**
 * Get categories as options for select dropdown
 */
function get_review_category_options($selected = '') {
    $html = '<option value="">Select Category (Optional)</option>';
    foreach (REVIEW_CATEGORIES as $category) {
        $sel = ($selected === $category) ? 'selected' : '';
        $html .= '<option value="' . htmlspecialchars($category) . '" ' . $sel . '>' . htmlspecialchars($category) . '</option>';
    }
    return $html;
}

/**
 * Get random category (for auto-assignment)
 */
function get_random_review_category() {
    $categories = REVIEW_CATEGORIES;
    return $categories[array_rand($categories)];
}
