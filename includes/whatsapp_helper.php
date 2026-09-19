<?php
/**
 * Smart Dynamic WhatsApp Helper for GCM Netting Solutions (Chennai)
 * Generates rich, high-converting WhatsApp inquiry messages tailored to the specific product/service, location, and page.
 * Format matches GCM Enterprises standard:
 * 
 * Hi GCM Netting Solutions, I would like to inquire about *[Service]* in *[Location]*.
 * • Service: [Service]
 * • Location: [Location]
 * • Page: [Full URL]
 * • Website: www.gcmnettingsolutions.com
 * [Pricing/Technical prompt]
 */

if (!function_exists('getGcmPageContext')) {
function getGcmPageContext($customService = null, $customLocation = null) {
    global $page_title, $page_canonical, $current_service, $area_name, $area_slug, $service_title;

    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $cleanUri = trim(strtok($uri, '?'), '/');

    // 1. Detect Product / Service Name
    $service = $customService;
    if (empty($service) && !empty($service_title)) {
        $service = $service_title;
    }
    if (empty($service) && !empty($current_service['name'])) {
        $service = $current_service['name'];
    }

    if (empty($service)) {
        // Pattern: {service}-in-{area}
        if (preg_match('/^([a-z0-9-]+)-in-([a-z0-9-]+)/', $cleanUri, $m)) {
            $slug = $m[1];
            $svcMap = [
                'balcony-safety-nets' => 'Balcony Safety Nets Installation',
                'pigeon-safety-nets' => 'Pigeon Safety Nets & Netting',
                'pigeon-net-for-balcony' => 'Pigeon Net for Balcony',
                'pigeon-nets' => 'Pigeon Safety Nets',
                'invisible-grill-for-balcony' => 'Invisible Grill for Balcony',
                'invisible-grills' => 'Stainless Steel 316 Invisible Grills',
                'children-safety-nets' => 'Children Safety Nets for Balcony & Stairs',
                'anti-bird-nets' => 'Anti-Bird Protection Netting',
                'cricket-nets' => 'Cricket Practice Pitch Nets',
                'cricket-practice-nets' => 'Cricket Practice Net Installation',
                'sports-nets' => 'Sports & Court Perimeter Netting',
                'box-cricket-nets' => 'Box Cricket Ground Setup & Netting',
                'duct-area-safety-nets' => 'Duct Area Safety Nets',
                'cloth-drying-hanger' => 'Balcony & Ceiling Cloth Drying Hanger',
                'pulley-cloth-drying-hanger' => 'Pulley Cloth Drying Hanger',
                'bird-spikes' => 'Anti-Bird & Pigeon Spikes',
                'industrial-safety-nets' => 'Industrial & Construction Safety Nets',
                'fall-safety-nets' => 'Fall Protection Safety Nets',
                'monkey-safety-nets' => 'Monkey Protection Safety Nets',
                'staircase-safety-nets' => 'Staircase Safety Nets',
                'swimming-pool-safety-nets' => 'Swimming Pool Safety Nets'
            ];
            if (!empty($svcMap[$slug])) {
                $service = $svcMap[$slug];
            } else {
                $service = ucwords(str_replace('-', ' ', $slug));
            }
        }
    }

    if (empty($service)) {
        $checkStr = strtolower(($page_title ?? '') . ' ' . $cleanUri . ' ' . $script);
        if (strpos($checkStr, 'cloth-hanger') !== false || strpos($checkStr, 'cloth hanger') !== false || strpos($checkStr, 'drying-hanger') !== false || strpos($checkStr, 'pulley-cloth') !== false) {
            $service = 'Balcony & Ceiling Cloth Drying Hanger';
        } elseif (strpos($checkStr, 'invisible-grill') !== false || strpos($checkStr, 'invisible grill') !== false) {
            $service = 'Stainless Steel 316 Invisible Grills for Balcony';
        } elseif (strpos($checkStr, 'box-cricket') !== false || strpos($checkStr, 'box cricket') !== false) {
            $service = 'Box Cricket Ground Setup & Netting';
        } elseif (strpos($checkStr, 'cricket') !== false) {
            $service = 'Cricket Practice Pitch Nets Installation';
        } elseif (strpos($checkStr, 'sports') !== false) {
            $service = 'Sports & Ground Perimeter Netting';
        } elseif (strpos($checkStr, 'bird-spike') !== false || strpos($checkStr, 'spike') !== false) {
            $service = 'Anti-Bird & Pigeon Protection Spikes';
        } elseif (strpos($checkStr, 'pigeon') !== false || strpos($checkStr, 'kabutar') !== false) {
            $service = 'Pigeon Safety Nets Installation';
        } elseif (strpos($checkStr, 'child') !== false || strpos($checkStr, 'toddler') !== false) {
            $service = 'Children Balcony & Stair Safety Nets';
        } elseif (strpos($checkStr, 'duct') !== false) {
            $service = 'Duct Area Safety Nets';
        } elseif (strpos($checkStr, 'monkey') !== false) {
            $service = 'Monkey Protection Safety Nets';
        } elseif (strpos($checkStr, 'fall') !== false) {
            $service = 'Fall Protection Safety Nets';
        } elseif (strpos($checkStr, 'balcony') !== false) {
            $service = 'Balcony Safety Nets Installation';
        } elseif (!empty($page_title)) {
            $cleaned = preg_replace('/[–—|\-].*$/', '', $page_title);
            $service = trim($cleaned) ?: 'Safety Net Installation';
        } else {
            $service = 'Safety Nets & Balcony Solutions';
        }
    }

    // 2. Detect Chennai Location / Area
    $location = $customLocation;
    if (empty($location) && !empty($area_name)) {
        $location = $area_name . ', Chennai';
    }

    if (empty($location)) {
        if (preg_match('/-in-([a-z0-9-]+)/', $cleanUri, $m)) {
            $slug = $m[1];
            $locFormatted = ucwords(str_replace('-', ' ', $slug));
            $location = $locFormatted . ', Chennai';
        }
    }

    if (empty($location)) {
        $location = 'Chennai, Tamil Nadu (All Areas & Outskirts)';
    }

    // 3. Determine Pricing / Unit Prompt
    $searchKey = strtolower($service . ' ' . $cleanUri . ' ' . $script);
    if (strpos($searchKey, 'hanger') !== false || strpos($searchKey, 'drying') !== false) {
        $pricingText = 'Please share pricing per piece / set, model options, technical specifications, and installation availability.';
    } elseif (strpos($searchKey, 'spike') !== false) {
        $pricingText = 'Please share pricing per piece / per strip, technical specifications, and installation availability.';
    } elseif (strpos($searchKey, 'box cricket') !== false || strpos($searchKey, 'turf') !== false) {
        $pricingText = 'Please share turnkey court setup cost / pricing per sq.ft, technical specifications, and installation availability.';
    } else {
        $pricingText = 'Please share pricing per sq.ft, warranty details, and same-day installation availability.';
    }

    // 4. Build Full Canonical URL Reference
    $pageUrl = $page_canonical ?? '';
    if (empty($pageUrl)) {
        $host = $_SERVER['HTTP_HOST'] ?? 'gcmnettingsolutions.com';
        $pageUrl = 'https://' . $host . '/' . ltrim($uri, '/');
    }

    return [
        'service'      => $service,
        'location'     => $location,
        'page_url'     => $pageUrl,
        'website'      => 'www.gcmnettingsolutions.com',
        'company'      => 'GCM Netting Solutions',
        'pricing_text' => $pricingText
    ];
}
}

if (!function_exists('getGcmWhatsAppUrl')) {
function getGcmWhatsAppUrl($customService = null, $customLocation = null, $phone = '919912399224') {
    $ctx = getGcmPageContext($customService, $customLocation);

    $msg = "Hi " . $ctx['company'] . ", I would like to inquire about *" . $ctx['service'] . "* in *" . $ctx['location'] . "*.\n\n"
         . "• Service: " . $ctx['service'] . "\n"
         . "• Location: " . $ctx['location'] . "\n"
         . "• Page: " . $ctx['page_url'] . "\n"
         . "• Website: " . $ctx['website'] . "\n\n"
         . $ctx['pricing_text'];

    return "https://wa.me/" . $phone . "?text=" . rawurlencode($msg);
}
}
