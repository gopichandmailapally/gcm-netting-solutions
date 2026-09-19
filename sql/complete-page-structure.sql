-- ============================================
-- COMPLETE PAGE STRUCTURE DATABASE
-- Permanent storage for services, areas, and pages
-- This ensures accurate counts always
-- ============================================

-- ============================================
-- 1. SERVICES TABLE (64 Keywords)
-- ============================================
CREATE TABLE IF NOT EXISTS `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL UNIQUE,
  `category` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert all 64 service keywords
INSERT INTO services (keyword, slug, category) VALUES
-- PIGEON NETS (13 keywords)
('Pigeon Nets', 'pigeon-nets', 'PIGEON NETS'),
('Pigeon Net', 'pigeon-net', 'PIGEON NETS'),
('Balcony Netting', 'balcony-netting', 'PIGEON NETS'),
('Pigeon Net For Balcony', 'pigeon-net-for-balcony', 'PIGEON NETS'),
('Pigeon Nets Installation', 'pigeon-nets-installation', 'PIGEON NETS'),
('Pigeon Bird Netting', 'pigeon-bird-netting', 'PIGEON NETS'),
('Pigeon Net Installation', 'pigeon-net-installation', 'PIGEON NETS'),
('Pigeon Net Near Me', 'pigeon-net-near-me', 'PIGEON NETS'),
('Pigeon Net For Balcony Near Me', 'pigeon-net-for-balcony-near-me', 'PIGEON NETS'),
('Pigeon Net Installation Near Me', 'pigeon-net-installation-near-me', 'PIGEON NETS'),
('Pigeon Safety Nets', 'pigeon-safety-nets', 'PIGEON NETS'),
('Pigeon Net Price', 'pigeon-net-price', 'PIGEON NETS'),
('Kabutar Jali Near Me', 'kabutar-jali-near-me', 'PIGEON NETS'),

-- BIRD NETS (9 keywords)
('Bird Nets', 'bird-nets', 'BIRD NETS'),
('Bird Net', 'bird-net', 'BIRD NETS'),
('Bird Net For Balcony', 'bird-net-for-balcony', 'BIRD NETS'),
('Bird Net Near Me', 'bird-net-near-me', 'BIRD NETS'),
('Nets For Birds', 'nets-for-birds', 'BIRD NETS'),
('Net For Birds', 'net-for-birds', 'BIRD NETS'),
('Industrial Bird Netting', 'industrial-bird-netting', 'BIRD NETS'),
('Bird Netting', 'bird-netting', 'BIRD NETS'),
('Anti Bird Netting', 'anti-bird-netting', 'BIRD NETS'),

-- SAFETY NETS (11 keywords)
('Safety Nets', 'safety-nets', 'SAFETY NETS'),
('Balcony Safety Nets', 'balcony-safety-nets', 'SAFETY NETS'),
('Safety Nets For Balconies', 'safety-nets-for-balconies', 'SAFETY NETS'),
('Duct Area Safety Nets', 'duct-area-safety-nets', 'SAFETY NETS'),
('Monkey Safety Nets', 'monkey-safety-nets', 'SAFETY NETS'),
('Construction Safety Nets', 'construction-safety-nets', 'SAFETY NETS'),
('Industrial Safety Nets', 'industrial-safety-nets', 'SAFETY NETS'),
('Fall Safety Nets', 'fall-safety-nets', 'SAFETY NETS'),
('Fall Protection Nets', 'fall-protection-nets', 'SAFETY NETS'),
('Children Safety Nets', 'children-safety-nets', 'SAFETY NETS'),
('Pet Safety Nets', 'pet-safety-nets', 'SAFETY NETS'),

-- SPORTS NETS (13 keywords)
('Cricket Nets', 'cricket-nets', 'SPORTS NETS'),
('Cricket Nets Price', 'cricket-nets-price', 'SPORTS NETS'),
('Cricket Nets Near Me', 'cricket-nets-near-me', 'SPORTS NETS'),
('Cricket Practice Net', 'cricket-practice-net', 'SPORTS NETS'),
('Cricket Practice Nets', 'cricket-practice-nets', 'SPORTS NETS'),
('Cricket Net Price', 'cricket-net-price', 'SPORTS NETS'),
('Cricket Indoor Nets Near Me', 'cricket-indoor-nets-near-me', 'SPORTS NETS'),
('Indoor Cricket Nets Near Me', 'indoor-cricket-nets-near-me', 'SPORTS NETS'),
('Sports Nets', 'sports-nets', 'SPORTS NETS'),
('Sports Netting', 'sports-netting', 'SPORTS NETS'),
('Cricket Netting', 'cricket-netting', 'SPORTS NETS'),
('Box Cricket Net', 'box-cricket-net', 'SPORTS NETS'),
('Cricket Net Installation', 'cricket-net-installation', 'SPORTS NETS'),

-- INVISIBLE GRILLS (9 keywords)
('Invisible Grills', 'invisible-grills', 'INVISIBLE GRILLS'),
('Invisible Grill Near Me', 'invisible-grill-near-me', 'INVISIBLE GRILLS'),
('SS Invisible Grills', 'ss-invisible-grills', 'INVISIBLE GRILLS'),
('Invisible Grill For Balcony', 'invisible-grill-for-balcony', 'INVISIBLE GRILLS'),
('Balcony Invisible Grill', 'balcony-invisible-grill', 'INVISIBLE GRILLS'),
('Invisible Grill For Balcony Near Me', 'invisible-grill-for-balcony-near-me', 'INVISIBLE GRILLS'),
('Invisible Safety Grill', 'invisible-safety-grill', 'INVISIBLE GRILLS'),
('Invisible Grill For Safety', 'invisible-grill-for-safety', 'INVISIBLE GRILLS'),
('Invisible Grill For Pigeons', 'invisible-grill-for-pigeons', 'INVISIBLE GRILLS'),

-- CLOTH HANGERS (9 keywords)
('Ceiling Cloth Hangers', 'ceiling-cloth-hangers', 'CLOTH HANGERS'),
('Dry Cloth Hangers', 'dry-cloth-hangers', 'CLOTH HANGERS'),
('Cloth Drying Hangers', 'cloth-drying-hangers', 'CLOTH HANGERS'),
('Cloth Hanger For Balcony', 'cloth-hanger-for-balcony', 'CLOTH HANGERS'),
('Pulley Cloth Drying Hanger', 'pulley-cloth-drying-hanger', 'CLOTH HANGERS'),
('Pulley Cloth Hanger', 'pulley-cloth-hanger', 'CLOTH HANGERS'),
('Laundry Hanger Dryer', 'laundry-hanger-dryer', 'CLOTH HANGERS'),
('Clothes Hanger To Dry Clothes', 'clothes-hanger-to-dry-clothes', 'CLOTH HANGERS'),
('Clothes Hanger Drier', 'clothes-hanger-drier', 'CLOTH HANGERS')
ON DUPLICATE KEY UPDATE keyword=VALUES(keyword);

-- ============================================
-- 2. AREAS TABLE (188 Locations)
-- ============================================
CREATE TABLE IF NOT EXISTS `areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `area_name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL UNIQUE,
  `zone` varchar(50) DEFAULT 'Chennai',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_zone` (`zone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert all 188 areas
INSERT INTO areas (area_name, slug, zone) VALUES
('Abids', 'abids', 'Chennai'),
('Adikmet', 'adikmet', 'Chennai'),
('Afzalgunj', 'afzalgunj', 'Chennai'),
('Aliabad', 'aliabad', 'Chennai'),
('Alwal', 'alwal', 'Chennai'),
('Amberpet', 'amberpet', 'Chennai'),
('Ameerpet', 'ameerpet', 'Chennai'),
('Ananthagiri Hills', 'ananthagiri-hills', 'Chennai'),
('Asif Nagar', 'asif-nagar', 'Chennai'),
('Asifabad', 'asifabad', 'Chennai'),
('Attapur', 'attapur', 'Chennai'),
('Attapur Metro', 'attapur-metro', 'Chennai'),
('Ayyappa Society', 'ayyappa-society', 'Chennai'),
('Bachupally', 'bachupally', 'Chennai'),
('Badangpet', 'badangpet', 'Chennai'),
('Bagh Amberpet', 'bagh-amberpet', 'Chennai'),
('Bagh Lingampally', 'bagh-lingampally', 'Chennai'),
('Bahadurpura', 'bahadurpura', 'Chennai'),
('Balkampet', 'balkampet', 'Chennai'),
('Balnagar', 'balnagar', 'Chennai'),
('Bandlaguda', 'bandlaguda', 'Chennai'),
('Banjara Hills', 'banjara-hills', 'Chennai'),
('Barkas', 'barkas', 'Chennai'),
('Basheerbagh', 'basheerbagh', 'Chennai'),
('Begum Bazar', 'begum-bazar', 'Chennai'),
('Begumpet', 'begumpet', 'Chennai'),
('Boduppal', 'boduppal', 'Chennai'),
('Borabanda', 'borabanda', 'Chennai'),
('Bowenpally', 'bowenpally', 'Chennai'),
('Boysguda', 'boysguda', 'Chennai'),
('Champapet', 'champapet', 'Chennai'),
('Chanda Nagar', 'chanda-nagar', 'Chennai'),
('Chandanagar', 'chandanagar', 'Chennai'),
('Charminar', 'charminar', 'Chennai'),
('Chikkadpally', 'chikkadpally', 'Chennai'),
('Chintal', 'chintal', 'Chennai'),
('Chintalkunta', 'chintalkunta', 'Chennai'),
('Dabeerpura', 'dabeerpura', 'Chennai'),
('Dammaiguda', 'dammaiguda', 'Chennai'),
('Dar-ul-Shifa', 'dar-ul-shifa', 'Chennai'),
('Dhoolpet', 'dhoolpet', 'Chennai'),
('Dilsukhnagar', 'dilsukhnagar', 'Chennai'),
('Domalguda', 'domalguda', 'Chennai'),
('Dundigal', 'dundigal', 'Chennai'),
('East Marredpally', 'east-marredpally', 'Chennai'),
('ECIL', 'ecil', 'Chennai'),
('ECIL Cross Roads', 'ecil-cross-roads', 'Chennai'),
('Edi Bazar', 'edi-bazar', 'Chennai'),
('Erragadda', 'erragadda', 'Chennai'),
('Falaknuma', 'falaknuma', 'Chennai'),
('Fateh Nagar', 'fateh-nagar', 'Chennai'),
('Ferozguda', 'ferozguda', 'Chennai'),
('Film Nagar', 'film-nagar', 'Chennai'),
('Financial District', 'financial-district', 'Chennai'),
('Gachibowli', 'gachibowli', 'Chennai'),
('Gaddiannaram', 'gaddiannaram', 'Chennai'),
('Gajularamaram', 'gajularamaram', 'Chennai'),
('Gandhi Nagar', 'gandhi-nagar', 'Chennai'),
('Ghatkesar', 'ghatkesar', 'Chennai'),
('Golconda', 'golconda', 'Chennai'),
('Goshamahal', 'goshamahal', 'Chennai'),
('Gowlidoddy', 'gowlidoddy', 'Chennai'),
('Gudimelakunta', 'gudimelakunta', 'Chennai'),
('Habsiguda', 'habsiguda', 'Chennai'),
('Hafeezpet', 'hafeezpet', 'Chennai'),
('Hayathnagar', 'hayathnagar', 'Chennai'),
('Himayatnagar', 'himayatnagar', 'Chennai'),
('Hitech City', 'hitech-city', 'Chennai'),
('Hussainialam', 'hussainialam', 'Chennai'),
('Hyderguda', 'hyderguda', 'Chennai'),
('Ibrahim Bagh', 'ibrahim-bagh', 'Chennai'),
('Ibrahimpatnam', 'ibrahimpatnam', 'Chennai'),
('IDA Bollaram', 'ida-bollaram', 'Chennai'),
('IS Sadan', 'is-sadan', 'Chennai'),
('Izzat Nagar', 'izzat-nagar', 'Chennai'),
('Jagadgirigutta', 'jagadgirigutta', 'Chennai'),
('Jahanuma', 'jahanuma', 'Chennai'),
('Jamia Osmania', 'jamia-osmania', 'Chennai'),
('Jeedimetla', 'jeedimetla', 'Chennai'),
('Jubilee Hills', 'jubilee-hills', 'Chennai'),
('Kachiguda', 'kachiguda', 'Chennai'),
('Kailash Nagar', 'kailash-nagar', 'Chennai'),
('Kalimandir', 'kalimandir', 'Chennai'),
('Kamala Nagar', 'kamala-nagar', 'Chennai'),
('Kapra', 'kapra', 'Chennai'),
('Karkhana', 'karkhana', 'Chennai'),
('Karwan', 'karwan', 'Chennai'),
('Kattedan', 'kattedan', 'Chennai'),
('Khairtabad', 'khairtabad', 'Chennai'),
('Khajaguda', 'khajaguda', 'Chennai'),
('Kishanbagh', 'kishanbagh', 'Chennai'),
('Kismatkhan Gudda', 'kismatkhan-gudda', 'Chennai'),
('Kompally', 'kompally', 'Chennai'),
('Kondapur', 'kondapur', 'Chennai'),
('Kothapet', 'kothapet', 'Chennai'),
('KPHB Colony', 'kphb-colony', 'Chennai'),
('Kukatpally', 'kukatpally', 'Chennai'),
('Lakdikapul', 'lakdikapul', 'Chennai'),
('Lalapet', 'lalapet', 'Chennai'),
('Langer Houz', 'langer-houz', 'Chennai'),
('LB Nagar', 'lb-nagar', 'Chennai'),
('Lingampally', 'lingampally', 'Chennai'),
('Madannapet', 'madannapet', 'Chennai'),
('Madhapur', 'madhapur', 'Chennai'),
('Madinaguda', 'madinaguda', 'Chennai'),
('Mahdipatnam', 'mahdipatnam', 'Chennai'),
('Malakpet', 'malakpet', 'Chennai'),
('Mallapur', 'mallapur', 'Chennai'),
('Manikonda', 'manikonda', 'Chennai'),
('Marredpally', 'marredpally', 'Chennai'),
('Masab Tank', 'masab-tank', 'Chennai'),
('Meerpet', 'meerpet', 'Chennai'),
('Mehdipatnam', 'mehdipatnam', 'Chennai'),
('Mettuguda', 'mettuguda', 'Chennai'),
('Miyapur', 'miyapur', 'Chennai'),
('Moghalpura', 'moghalpura', 'Chennai'),
('Moosarambagh', 'moosarambagh', 'Chennai'),
('Moti Nagar', 'moti-nagar', 'Chennai'),
('Moula Ali', 'moula-ali', 'Chennai'),
('Musheerabad', 'musheerabad', 'Chennai'),
('Nacharam', 'nacharam', 'Chennai'),
('Nagaram', 'nagaram', 'Chennai'),
('Nagole', 'nagole', 'Chennai'),
('Nallakunta', 'nallakunta', 'Chennai'),
('Nampally', 'nampally', 'Chennai'),
('Nanakramguda', 'nanakramguda', 'Chennai'),
('Nanal Nagar', 'nanal-nagar', 'Chennai'),
('Narayanguda', 'narayanguda', 'Chennai'),
('Neredmet', 'neredmet', 'Chennai'),
('New Bowenpally', 'new-bowenpally', 'Chennai'),
('Nizampet', 'nizampet', 'Chennai'),
('Old Bowenpally', 'old-bowenpally', 'Chennai'),
('Old City', 'old-city', 'Chennai'),
('Old Malakpet', 'old-malakpet', 'Chennai'),
('Osman Nagar', 'osman-nagar', 'Chennai'),
('Osmangunj', 'osmangunj', 'Chennai'),
('Osmania University', 'osmania-university', 'Chennai'),
('Padmarao Nagar', 'padmarao-nagar', 'Chennai'),
('Panjagutta', 'panjagutta', 'Chennai'),
('Panjagutta Circle', 'panjagutta-circle', 'Chennai'),
('Paradise', 'paradise', 'Chennai'),
('Patancheru', 'patancheru', 'Chennai'),
('Patel Road', 'patel-road', 'Chennai'),
('Peerzadiguda', 'peerzadiguda', 'Chennai'),
('Pragathi Nagar', 'pragathi-nagar', 'Chennai'),
('Purani Haveli', 'purani-haveli', 'Chennai'),
('Qila Mohd. Nagar', 'qila-mohd-nagar', 'Chennai'),
('Quthbullapur', 'quthbullapur', 'Chennai'),
('Rajendranagar', 'rajendranagar', 'Chennai'),
('Ramakrishna Puram', 'ramakrishna-puram', 'Chennai'),
('Ramanthapur', 'ramanthapur', 'Chennai'),
('Ramnagar', 'ramnagar', 'Chennai'),
('RC Puram', 'rc-puram', 'Chennai'),
('Red Hills', 'red-hills', 'Chennai'),
('Safilguda', 'safilguda', 'Chennai'),
('Saidabad', 'saidabad', 'Chennai'),
('Sainikpuri', 'sainikpuri', 'Chennai'),
('Sanath Nagar', 'sanath-nagar', 'Chennai'),
('Sangareddy', 'sangareddy', 'Chennai'),
('Santosh Nagar', 'santosh-nagar', 'Chennai'),
('Saroornagar', 'saroornagar', 'Chennai'),
('Tambaram', 'secunderabad', 'Chennai'),
('Serilingampally', 'serilingampally', 'Chennai'),
('Shaikpet', 'shaikpet', 'Chennai'),
('Shamirpet', 'shamirpet', 'Chennai'),
('Shamshabad', 'shamshabad', 'Chennai'),
('Shankarpally', 'shankarpally', 'Chennai'),
('Tank Bund', 'tank-bund', 'Chennai'),
('Tappachabutra', 'tappachabutra', 'Chennai'),
('Tarnaka', 'tarnaka', 'Chennai'),
('Tolichowki', 'tolichowki', 'Chennai'),
('Trimulgherry', 'trimulgherry', 'Chennai'),
('Umdanagar', 'umdanagar', 'Chennai'),
('Uppal', 'uppal', 'Chennai'),
('Uppuguda', 'uppuguda', 'Chennai'),
('Vanasthalipuram', 'vanasthalipuram', 'Chennai'),
('Vengal Rao Nagar', 'vengal-rao-nagar', 'Chennai'),
('Vidyanagar', 'vidyanagar', 'Chennai'),
('Vikrampuri', 'vikrampuri', 'Chennai'),
('Warasiguda', 'warasiguda', 'Chennai'),
('West Marredpally', 'west-marredpally', 'Chennai'),
('Yakutpura', 'yakutpura', 'Chennai'),
('Yousufguda', 'yousufguda', 'Chennai'),
('Zakir Hussain Colony', 'zakir-hussain-colony', 'Chennai'),
('Zamistanpur', 'zamistanpur', 'Chennai'),
('Zeregumbad', 'zeregumbad', 'Chennai'),
('Ziaguda', 'ziaguda', 'Chennai'),
('Zohra Nagar', 'zohra-nagar', 'Chennai')
ON DUPLICATE KEY UPDATE area_name=VALUES(area_name);

-- ============================================
-- 3. PILLAR PAGES TABLE (64 Pages)
-- One pillar page per service keyword
-- ============================================
CREATE TABLE IF NOT EXISTS `pillar_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `page_title` varchar(200) NOT NULL,
  `page_slug` varchar(200) NOT NULL UNIQUE,
  `page_type` enum('service_pillar') DEFAULT 'service_pillar',
  `is_generated` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_service_id` (`service_id`),
  KEY `idx_slug` (`page_slug`),
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. STATIC PAGES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `static_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_name` varchar(100) NOT NULL,
  `page_slug` varchar(100) NOT NULL UNIQUE,
  `page_type` enum('main','blog','other') DEFAULT 'main',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_slug` (`page_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert static pages
INSERT INTO static_pages (page_name, page_slug, page_type) VALUES
('Homepage', 'index', 'main'),
('About Us', 'about', 'main'),
('Contact', 'contact', 'main'),
('Gallery', 'gallery', 'main'),
('Reviews', 'reviews', 'main'),
('FAQs', 'faqs', 'main'),
('Videos', 'videos', 'main'),
('Estimation', 'estimation', 'main'),
('Privacy Policy', 'privacy-policy', 'main'),
('Terms & Conditions', 'terms-conditions', 'main')
ON DUPLICATE KEY UPDATE page_name=VALUES(page_name);

-- ============================================
-- 5. PAGE COUNT SUMMARY TABLE
-- Automatically calculated counts
-- ============================================
CREATE TABLE IF NOT EXISTS `page_count_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_type` varchar(50) NOT NULL,
  `count` int(11) NOT NULL,
  `calculation` varchar(200) DEFAULT NULL,
  `last_updated` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_type` (`page_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert calculated counts
INSERT INTO page_count_summary (page_type, count, calculation) VALUES
('services', 64, 'COUNT(services)'),
('areas', 188, 'COUNT(areas)'),
('service_pages', 12032, '64 services × 188 areas'),
('pillar_pages', 64, 'One per service keyword'),
('static_pages', 10, 'Main pages only'),
('total_pages', 12106, '12032 + 64 + 10')
ON DUPLICATE KEY UPDATE count=VALUES(count), calculation=VALUES(calculation);

-- ============================================
-- 6. VIEW FOR EASY ACCESS
-- ============================================
CREATE OR REPLACE VIEW v_page_counts AS
SELECT 
    (SELECT COUNT(*) FROM services WHERE is_active = 1) as total_services,
    (SELECT COUNT(*) FROM areas WHERE is_active = 1) as total_areas,
    (SELECT COUNT(*) FROM services WHERE is_active = 1) * 
    (SELECT COUNT(*) FROM areas WHERE is_active = 1) as total_service_pages,
    (SELECT COUNT(*) FROM services WHERE is_active = 1) as total_pillar_pages,
    (SELECT COUNT(*) FROM static_pages WHERE is_active = 1) as total_static_pages,
    (SELECT COUNT(*) FROM services WHERE is_active = 1) * 
    (SELECT COUNT(*) FROM areas WHERE is_active = 1) + 
    (SELECT COUNT(*) FROM services WHERE is_active = 1) + 
    (SELECT COUNT(*) FROM static_pages WHERE is_active = 1) as grand_total;

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

-- Check service count
-- SELECT COUNT(*) as service_count FROM services WHERE is_active = 1;
-- Expected: 64

-- Check area count
-- SELECT COUNT(*) as area_count FROM areas WHERE is_active = 1;
-- Expected: 188

-- Check page counts
-- SELECT * FROM v_page_counts;
-- Expected: 64 services, 188 areas, 12032 service pages, 64 pillar pages, 10 static pages, 12106 total

-- Get breakdown by category
-- SELECT category, COUNT(*) as count FROM services WHERE is_active = 1 GROUP BY category;
