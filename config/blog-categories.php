<?php
/**
 * Blog Categories Configuration
 * Categories for blog posts and articles
 */

if (!defined('GCM_INIT')) {
    define('GCM_INIT', true);
}

// Define all Blog categories (30 Categories)
define('BLOG_CATEGORIES', [
    'General Information',
    'Safety Tips & Guides',
    'Pigeon Nets & Bird Control',
    'Bird Netting Solutions',
    'Sports Nets & Cricket',
    'Invisible Grills Installation',
    'Balcony Safety Nets',
    'Children Safety Tips',
    'Staircase Safety Solutions',
    'Window Safety Nets',
    'Terrace Protection',
    'Cloth Hangers & Drying',
    'Construction Safety',
    'Industrial Safety Nets',
    'Pet Protection Solutions',
    'Installation Guides',
    'Maintenance & Care Tips',
    'Product Reviews',
    'Material Quality Guide',
    'UV Protection & Durability',
    'Cost & Pricing Guide',
    'Warranty Information',
    'Before & After Projects',
    'Customer Success Stories',
    'Chennai Service Areas',
    'Seasonal Safety Tips',
    'Emergency Services',
    'Commercial Projects',
    'Residential Solutions',
    'DIY Tips & Tricks'
]);

/**
 * Get all blog categories as array
 */
function get_blog_categories() {
    return BLOG_CATEGORIES;
}

/**
 * Get categories as options for select dropdown
 */
function get_blog_category_options($selected = '') {
    $html = '<option value="">Select Category</option>';
    foreach (BLOG_CATEGORIES as $category) {
        $sel = ($selected === $category) ? 'selected' : '';
        $html .= '<option value="' . htmlspecialchars($category) . '" ' . $sel . '>' . htmlspecialchars($category) . '</option>';
    }
    return $html;
}

/**
 * Get random category (for auto-assignment)
 */
function get_random_blog_category() {
    $categories = BLOG_CATEGORIES;
    return $categories[array_rand($categories)];
}

/**
 * Get category slug (URL-friendly)
 */
function get_blog_category_slug($category) {
    return strtolower(str_replace([' ', '&', '/'], ['-', 'and', '-'], $category));
}

/**
 * Get category icon (FontAwesome)
 */
function get_blog_category_icon($category) {
    $icons = [
        'General Information' => 'fa-info-circle',
        'Safety Tips & Guides' => 'fa-shield-alt',
        'Pigeon Nets & Bird Control' => 'fa-dove',
        'Bird Netting Solutions' => 'fa-kiwi-bird',
        'Sports Nets & Cricket' => 'fa-baseball-ball',
        'Invisible Grills Installation' => 'fa-grip-lines',
        'Balcony Safety Nets' => 'fa-home',
        'Children Safety Tips' => 'fa-child',
        'Staircase Safety Solutions' => 'fa-stairs',
        'Window Safety Nets' => 'fa-window-restore',
        'Terrace Protection' => 'fa-building',
        'Cloth Hangers & Drying' => 'fa-tshirt',
        'Construction Safety' => 'fa-hard-hat',
        'Industrial Safety Nets' => 'fa-industry',
        'Pet Protection Solutions' => 'fa-paw',
        'Installation Guides' => 'fa-tools',
        'Maintenance & Care Tips' => 'fa-wrench',
        'Product Reviews' => 'fa-star',
        'Material Quality Guide' => 'fa-certificate',
        'UV Protection & Durability' => 'fa-sun',
        'Cost & Pricing Guide' => 'fa-rupee-sign',
        'Warranty Information' => 'fa-file-contract',
        'Before & After Projects' => 'fa-images',
        'Customer Success Stories' => 'fa-users',
        'Chennai Service Areas' => 'fa-map-marked-alt',
        'Seasonal Safety Tips' => 'fa-calendar-alt',
        'Emergency Services' => 'fa-ambulance',
        'Commercial Projects' => 'fa-briefcase',
        'Residential Solutions' => 'fa-house-user',
        'DIY Tips & Tricks' => 'fa-lightbulb'
    ];
    
    return $icons[$category] ?? 'fa-file-alt';
}
