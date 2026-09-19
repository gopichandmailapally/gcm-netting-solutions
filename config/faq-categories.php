<?php
/**
 * FAQ Categories Configuration
 * Single source of truth for all FAQ categories used across the website
 */

if (!defined('GCM_INIT')) {
    define('GCM_INIT', true);
}

// Define all FAQ categories - USE THIS EVERYWHERE (30 Categories)
define('FAQ_CATEGORIES', [
    'General Information',
    'Safety Nets (Balcony/Children)',
    'Pigeon Nets & Bird Control',
    'Bird Netting Solutions',
    'Sports Nets',
    'Cricket Practice Nets',
    'Invisible Grills',
    'Balcony Safety Nets',
    'Children Safety Nets',
    'Staircase Safety Nets',
    'Window Safety Nets',
    'Terrace Safety Nets',
    'Cloth Hangers & Drying',
    'Construction Safety Nets',
    'Industrial Safety Nets',
    'Monkey Protection Nets',
    'Cat Protection Nets',
    'Installation & Service',
    'Pricing & Quotation',
    'Warranty & Maintenance',
    'Materials & Quality',
    'Maintenance & Care',
    'Net Colors & Types',
    'Net Thickness & Mesh Size',
    'UV Protection & Durability',
    'Service Areas & Coverage',
    'Emergency Services',
    'Commercial Projects',
    'Residential Projects',
    'Custom Solutions'
]);

/**
 * Get all FAQ categories as array
 */
function get_faq_categories() {
    return FAQ_CATEGORIES;
}

/**
 * Get categories as options for select dropdown
 */
function get_faq_category_options($selected = '') {
    $html = '<option value="">Select Category</option>';
    foreach (FAQ_CATEGORIES as $category) {
        $sel = ($selected === $category) ? 'selected' : '';
        $html .= '<option value="' . htmlspecialchars($category) . '" ' . $sel . '>' . htmlspecialchars($category) . '</option>';
    }
    return $html;
}
