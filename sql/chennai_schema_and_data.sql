-- ========================================================
-- GCM Netting Solutions - Chennai Database Schema & Data
-- Target Geography: Chennai & Outskirts, Tamil Nadu
-- Total Dynamic Page Scale: 615,950 URLs (635 Areas x 970 Keywords)
-- ========================================================
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET NAMES utf8mb4;

-- 1. Services Table
DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(100) NOT NULL,
  `service_slug` varchar(100) NOT NULL UNIQUE,
  `icon_class` varchar(50) DEFAULT 'fa-shield-alt',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `show_in_slider` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `services` VALUES
(1,'Pigeon Safety Nets','pigeon-safety-nets','fa-dove','Premium pigeon netting and bird exclusion solutions for balconies in Chennai.',1,1,1),
(2,'Bird Nets','bird-nets','fa-kiwi-bird','Anti-bird netting for residential apartments, villas, and commercial complexes.',1,2,1),
(3,'Safety Nets','safety-nets','fa-shield-alt','High-tensile balcony safety nets and child protection netting.',1,3,1),
(4,'Invisible Grills','invisible-grills','fa-th','Ultra-strong SS 316 marine-grade invisible grills for balconies and windows in coastal Chennai.',1,4,1),
(5,'Sports Nets','sports-nets','fa-baseball-ball','Box cricket nets, football netting, and sports practice netting.',1,5,1),
(6,'Cloth Hangers','cloth-hangers','fa-tshirt','Ceiling mounted cloth drying hangers and pulley systems.',1,6,1);

-- 2. Service Areas Table (Chennai & Outskirts)
DROP TABLE IF EXISTS `service_areas`;
CREATE TABLE `service_areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `area_name` varchar(100) NOT NULL,
  `area_slug` varchar(100) NOT NULL UNIQUE,
  `zone` varchar(100) DEFAULT 'Chennai',
  `city` varchar(100) DEFAULT 'Chennai',
  `state` varchar(100) DEFAULT 'Tamil Nadu',
  `pincode` varchar(10) DEFAULT '600001',
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_slug` (`area_slug`),
  KEY `idx_zone` (`zone`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `service_areas` (`area_name`, `area_slug`, `zone`, `city`, `state`, `pincode`, `is_active`, `display_order`) VALUES
('Thiruvottiyur', 'thiruvottiyur', 'Thiruvottiyur', 'Chennai', 'Tamil Nadu', '600019', 1, 1),
('Ennore', 'ennore', 'Thiruvottiyur', 'Chennai', 'Tamil Nadu', '600057', 1, 2),
('Kathivakkam', 'kathivakkam', 'Thiruvottiyur', 'Chennai', 'Tamil Nadu', '600057', 1, 3),
('Ernavoor', 'ernavoor', 'Thiruvottiyur', 'Chennai', 'Tamil Nadu', '600057', 1, 4),
('Wimco Nagar', 'wimco-nagar', 'Thiruvottiyur', 'Chennai', 'Tamil Nadu', '600057', 1, 5),
('Tollgate Thiruvottiyur', 'tollgate-thiruvottiyur', 'Thiruvottiyur', 'Chennai', 'Tamil Nadu', '600019', 1, 6),
('Theradi', 'theradi', 'Thiruvottiyur', 'Chennai', 'Tamil Nadu', '600019', 1, 7),
('Kaladipet', 'kaladipet', 'Thiruvottiyur', 'Chennai', 'Tamil Nadu', '600019', 1, 8),
('Raja Shanmugam Nagar', 'raja-shanmugam-nagar', 'Thiruvottiyur', 'Chennai', 'Tamil Nadu', '600019', 1, 9),
('Sathangadu', 'sathangadu', 'Thiruvottiyur', 'Chennai', 'Tamil Nadu', '600068', 1, 10),
('Jothi Nagar Thiruvottiyur', 'jothi-nagar-thiruvottiyur', 'Thiruvottiyur', 'Chennai', 'Tamil Nadu', '600019', 1, 11),
('Bharathi Nagar Thiruvottiyur', 'bharathi-nagar-thiruvottiyur', 'Thiruvottiyur', 'Chennai', 'Tamil Nadu', '600019', 1, 12),
('Ajay Nagar', 'ajay-nagar', 'Thiruvottiyur', 'Chennai', 'Tamil Nadu', '600019', 1, 13),
('Tsunami Colony Ernavoor', 'tsunami-colony-ernavoor', 'Thiruvottiyur', 'Chennai', 'Tamil Nadu', '600057', 1, 14),
('Nandiyambakkam', 'nandiyambakkam', 'Thiruvottiyur', 'Chennai', 'Tamil Nadu', '600120', 1, 15),
('Manali', 'manali', 'Manali', 'Chennai', 'Tamil Nadu', '600068', 1, 16),
('Manali New Town', 'manali-new-town', 'Manali', 'Chennai', 'Tamil Nadu', '600103', 1, 17),
('Mathur MMDA', 'mathur-mmda', 'Manali', 'Chennai', 'Tamil Nadu', '600068', 1, 18),
('Chinnasekadu', 'chinnasekadu', 'Manali', 'Chennai', 'Tamil Nadu', '600068', 1, 19),
('Kosapur', 'kosapur', 'Manali', 'Chennai', 'Tamil Nadu', '600060', 1, 20),
('Edayanchavadi', 'edayanchavadi', 'Manali', 'Chennai', 'Tamil Nadu', '600103', 1, 21),
('Vichoor', 'vichoor', 'Manali', 'Chennai', 'Tamil Nadu', '600103', 1, 22),
('Andarkuppam', 'andarkuppam', 'Manali', 'Chennai', 'Tamil Nadu', '600103', 1, 23),
('Amullavoyal', 'amullavoyal', 'Manali', 'Chennai', 'Tamil Nadu', '600068', 1, 24),
('Sadaiyankuppam', 'sadaiyankuppam', 'Manali', 'Chennai', 'Tamil Nadu', '600103', 1, 25),
('Kadappakkam', 'kadappakkam', 'Manali', 'Chennai', 'Tamil Nadu', '600103', 1, 26),
('Madhavaram', 'madhavaram', 'Madhavaram', 'Chennai', 'Tamil Nadu', '600060', 1, 27),
('Madhavaram Milk Colony', 'madhavaram-milk-colony', 'Madhavaram', 'Chennai', 'Tamil Nadu', '600051', 1, 28),
('Puzhal', 'puzhal', 'Madhavaram', 'Chennai', 'Tamil Nadu', '600066', 1, 29),
('Red Hills', 'red-hills', 'Madhavaram', 'Chennai', 'Tamil Nadu', '600052', 1, 30),
('Surapet', 'surapet', 'Madhavaram', 'Chennai', 'Tamil Nadu', '600066', 1, 31),
('Padianallur', 'padianallur', 'Madhavaram', 'Chennai', 'Tamil Nadu', '600052', 1, 32),
('Kavangarai', 'kavangarai', 'Madhavaram', 'Chennai', 'Tamil Nadu', '600066', 1, 33),
('Vadakarai', 'vadakarai', 'Madhavaram', 'Chennai', 'Tamil Nadu', '600052', 1, 34),
('Granthian', 'granthian', 'Madhavaram', 'Chennai', 'Tamil Nadu', '600052', 1, 35),
('Theeyambakkam', 'theeyambakkam', 'Madhavaram', 'Chennai', 'Tamil Nadu', '600060', 1, 36),
('Assisi Nagar', 'assisi-nagar', 'Madhavaram', 'Chennai', 'Tamil Nadu', '600060', 1, 37),
('Lakshmipuram Madhavaram', 'lakshmipuram-madhavaram', 'Madhavaram', 'Chennai', 'Tamil Nadu', '600099', 1, 38),
('Vinayagapuram', 'vinayagapuram', 'Madhavaram', 'Chennai', 'Tamil Nadu', '600099', 1, 39),
('Kallikuppam', 'kallikuppam', 'Madhavaram', 'Chennai', 'Tamil Nadu', '600053', 1, 40),
('Retteri', 'retteri', 'Madhavaram', 'Chennai', 'Tamil Nadu', '600099', 1, 41),
('Tondiarpet', 'tondiarpet', 'Tondiarpet', 'Chennai', 'Tamil Nadu', '600081', 1, 42),
('Washermanpet', 'washermanpet', 'Tondiarpet', 'Chennai', 'Tamil Nadu', '600021', 1, 43),
('Old Washermanpet', 'old-washermanpet', 'Tondiarpet', 'Chennai', 'Tamil Nadu', '600021', 1, 44),
('New Washermanpet', 'new-washermanpet', 'Tondiarpet', 'Chennai', 'Tamil Nadu', '600081', 1, 45),
('Korukkupet', 'korukkupet', 'Tondiarpet', 'Chennai', 'Tamil Nadu', '600021', 1, 46),
('Cowl Bazaar North', 'cowl-bazaar-north', 'Tondiarpet', 'Chennai', 'Tamil Nadu', '600081', 1, 47),
('Stanley Nagar', 'stanley-nagar', 'Tondiarpet', 'Chennai', 'Tamil Nadu', '600021', 1, 48),
('Meenakshi Amman Nagar', 'meenakshi-amman-nagar', 'Tondiarpet', 'Chennai', 'Tamil Nadu', '600081', 1, 49),
('Vaidyanathan Street', 'vaidyanathan-street', 'Tondiarpet', 'Chennai', 'Tamil Nadu', '600081', 1, 50),
('Seniamman Koil', 'seniamman-koil', 'Tondiarpet', 'Chennai', 'Tamil Nadu', '600081', 1, 51),
('Royapuram', 'royapuram', 'Royapuram', 'Chennai', 'Tamil Nadu', '600013', 1, 52),
('George Town', 'george-town', 'Royapuram', 'Chennai', 'Tamil Nadu', '600001', 1, 53),
('Sowcarpet', 'sowcarpet', 'Royapuram', 'Chennai', 'Tamil Nadu', '600079', 1, 54),
('Parrys Corner', 'parrys-corner', 'Royapuram', 'Chennai', 'Tamil Nadu', '600001', 1, 55),
('Mannady', 'mannady', 'Royapuram', 'Chennai', 'Tamil Nadu', '600001', 1, 56),
('Broadway Chennai', 'broadway-chennai', 'Royapuram', 'Chennai', 'Tamil Nadu', '600108', 1, 57),
('Park Town', 'park-town', 'Royapuram', 'Chennai', 'Tamil Nadu', '600003', 1, 58),
('Central Railway Station Area', 'central-railway-station-area', 'Royapuram', 'Chennai', 'Tamil Nadu', '600003', 1, 59),
('Mint Street', 'mint-street', 'Royapuram', 'Chennai', 'Tamil Nadu', '600079', 1, 60),
('Kothawal Chavadi', 'kothawal-chavadi', 'Royapuram', 'Chennai', 'Tamil Nadu', '600001', 1, 61),
('Kasimedu', 'kasimedu', 'Royapuram', 'Chennai', 'Tamil Nadu', '600013', 1, 62),
('Kalmandapam', 'kalmandapam', 'Royapuram', 'Chennai', 'Tamil Nadu', '600013', 1, 63),
('Singarachari Street', 'singarachari-street', 'Royapuram', 'Chennai', 'Tamil Nadu', '600013', 1, 64),
('Seven Wells', 'seven-wells', 'Royapuram', 'Chennai', 'Tamil Nadu', '600001', 1, 65),
('Edapalayam', 'edapalayam', 'Royapuram', 'Chennai', 'Tamil Nadu', '600003', 1, 66),
('Perambur', 'perambur', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600011', 1, 67),
('Kolathur', 'kolathur', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600099', 1, 68),
('Thiru Vi Ka Nagar', 'thiru-vi-ka-nagar', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600082', 1, 69),
('Vyasarpadi', 'vyasarpadi', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600039', 1, 70),
('Kodungaiyur', 'kodungaiyur', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600118', 1, 71),
('Erukkanchery', 'erukkanchery', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600118', 1, 72),
('Sembium', 'sembium', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600011', 1, 73),
('Periyar Nagar', 'periyar-nagar', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600082', 1, 74),
('Jawahar Nagar', 'jawahar-nagar', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600082', 1, 75),
('Agaram', 'agaram', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600082', 1, 76),
('GKM Colony', 'gkm-colony', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600082', 1, 77),
('Kumaran Nagar Kolathur', 'kumaran-nagar-kolathur', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600099', 1, 78),
('Poombuhar Nagar', 'poombuhar-nagar', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600099', 1, 79),
('Teachers Colony Kolathur', 'teachers-colony-kolathur', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600099', 1, 80),
('MKB Nagar', 'mkb-nagar', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600039', 1, 81),
('Muthamizh Nagar', 'muthamizh-nagar', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600118', 1, 82),
('Kaviarasu Kannadasan Nagar', 'kaviarasu-kannadasan-nagar', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600118', 1, 83),
('Moorthy Nagar', 'moorthy-nagar', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600118', 1, 84),
('Sidco Nagar Villivakkam', 'sidco-nagar-villivakkam', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600049', 1, 85),
('Villivakkam', 'villivakkam', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600049', 1, 86),
('Konnur High Road', 'konnur-high-road', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600023', 1, 87),
('Ayanavaram', 'ayanavaram', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600023', 1, 88),
('Otteri', 'otteri', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600012', 1, 89),
('Pattalam', 'pattalam', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600012', 1, 90),
('Pulianthope', 'pulianthope', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600012', 1, 91),
('Choolai', 'choolai', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600112', 1, 92),
('Periamet', 'periamet', 'Thiru Vi Ka Nagar', 'Chennai', 'Tamil Nadu', '600003', 1, 93),
('Ambattur', 'ambattur', 'Ambattur', 'Chennai', 'Tamil Nadu', '600053', 1, 94),
('Ambattur OT', 'ambattur-ot', 'Ambattur', 'Chennai', 'Tamil Nadu', '600053', 1, 95),
('Ambattur Industrial Estate', 'ambattur-industrial-estate', 'Ambattur', 'Chennai', 'Tamil Nadu', '600058', 1, 96),
('Mogappair', 'mogappair', 'Ambattur', 'Chennai', 'Tamil Nadu', '600037', 1, 97),
('Mogappair East', 'mogappair-east', 'Ambattur', 'Chennai', 'Tamil Nadu', '600037', 1, 98),
('Mogappair West', 'mogappair-west', 'Ambattur', 'Chennai', 'Tamil Nadu', '600037', 1, 99),
('Nolambur', 'nolambur', 'Ambattur', 'Chennai', 'Tamil Nadu', '600095', 1, 100),
('Padi', 'padi', 'Ambattur', 'Chennai', 'Tamil Nadu', '600050', 1, 101),
('Korattur', 'korattur', 'Ambattur', 'Chennai', 'Tamil Nadu', '600080', 1, 102),
('Mannurpet', 'mannurpet', 'Ambattur', 'Chennai', 'Tamil Nadu', '600050', 1, 103),
('Oragadam Ambattur', 'oragadam-ambattur', 'Ambattur', 'Chennai', 'Tamil Nadu', '600053', 1, 104),
('Venkatapuram Ambattur', 'venkatapuram-ambattur', 'Ambattur', 'Chennai', 'Tamil Nadu', '600053', 1, 105),
('Vijayalakshmipuram', 'vijayalakshmipuram', 'Ambattur', 'Chennai', 'Tamil Nadu', '600053', 1, 106),
('Varadarajapuram Ambattur', 'varadarajapuram-ambattur', 'Ambattur', 'Chennai', 'Tamil Nadu', '600053', 1, 107),
('Ram Nagar Ambattur', 'ram-nagar-ambattur', 'Ambattur', 'Chennai', 'Tamil Nadu', '600053', 1, 108),
('Prithvipakkam', 'prithvipakkam', 'Ambattur', 'Chennai', 'Tamil Nadu', '600053', 1, 109),
('Menambedu', 'menambedu', 'Ambattur', 'Chennai', 'Tamil Nadu', '600053', 1, 110),
('Karukku', 'karukku', 'Ambattur', 'Chennai', 'Tamil Nadu', '600053', 1, 111),
('Gnanamoorthy Nagar', 'gnanamoorthy-nagar', 'Ambattur', 'Chennai', 'Tamil Nadu', '600053', 1, 112),
('TVS Nagar', 'tvs-nagar', 'Ambattur', 'Chennai', 'Tamil Nadu', '600080', 1, 113),
('Central Avenue Korattur', 'central-avenue-korattur', 'Ambattur', 'Chennai', 'Tamil Nadu', '600080', 1, 114),
('Jeeva Nagar Korattur', 'jeeva-nagar-korattur', 'Ambattur', 'Chennai', 'Tamil Nadu', '600080', 1, 115),
('Golden Jubilee Flats Mogappair', 'golden-jubilee-flats-mogappair', 'Ambattur', 'Chennai', 'Tamil Nadu', '600037', 1, 116),
('VGN Minerva Nolambur', 'vgn-minerva-nolambur', 'Ambattur', 'Chennai', 'Tamil Nadu', '600095', 1, 117),
('City Light Meadows Nolambur', 'city-light-meadows-nolambur', 'Ambattur', 'Chennai', 'Tamil Nadu', '600095', 1, 118),
('Anna Nagar', 'anna-nagar', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600040', 1, 119),
('Anna Nagar East', 'anna-nagar-east', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600102', 1, 120),
('Anna Nagar West', 'anna-nagar-west', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600040', 1, 121),
('Anna Nagar West Extension', 'anna-nagar-west-extension', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600101', 1, 122),
('Shenoy Nagar', 'shenoy-nagar', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600030', 1, 123),
('Kilpauk', 'kilpauk', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600010', 1, 124),
('Kilpauk Garden', 'kilpauk-garden', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600010', 1, 125),
('Kellys', 'kellys', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600010', 1, 126),
('Chetpet', 'chetpet', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600031', 1, 127),
('Harrington Road', 'harrington-road', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600031', 1, 128),
('Spur Tank Road', 'spur-tank-road', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600031', 1, 129),
('McNichols Road', 'mcnichols-road', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600031', 1, 130),
('Koyambedu', 'koyambedu', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600107', 1, 131),
('CMBT Koyambedu', 'cmbt-koyambedu', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600107', 1, 132),
('SAF Games Village', 'saf-games-village', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600107', 1, 133),
('Arumbakkam', 'arumbakkam', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600106', 1, 134),
('MMDA Colony Arumbakkam', 'mmda-colony-arumbakkam', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600106', 1, 135),
('Jafferkhanpet', 'jafferkhanpet', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600083', 1, 136),
('Choolaimedu', 'choolaimedu', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600094', 1, 137),
('Gill Nagar', 'gill-nagar', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600094', 1, 138),
('Bajanai Koil Choolaimedu', 'bajanai-koil-choolaimedu', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600094', 1, 139),
('Shanti Colony Anna Nagar', 'shanti-colony-anna-nagar', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600040', 1, 140),
('Tower Park Anna Nagar', 'tower-park-anna-nagar', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600040', 1, 141),
('Roundtana Anna Nagar', 'roundtana-anna-nagar', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600040', 1, 142),
('Thirumangalam', 'thirumangalam', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600040', 1, 143),
('Park Road Anna Nagar', 'park-road-anna-nagar', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600101', 1, 144),
('Collector Nagar', 'collector-nagar', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600101', 1, 145),
('Padikuppam', 'padikuppam', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600107', 1, 146),
('Aminjikarai', 'aminjikarai', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600029', 1, 147),
('TP Chatram', 'tp-chatram', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600030', 1, 148),
('Gajalakshmi Colony', 'gajalakshmi-colony', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600030', 1, 149),
('Teynampet', 'teynampet', 'Teynampet', 'Chennai', 'Tamil Nadu', '600018', 1, 150),
('Nungambakkam', 'nungambakkam', 'Teynampet', 'Chennai', 'Tamil Nadu', '600034', 1, 151),
('Sterling Road', 'sterling-road', 'Teynampet', 'Chennai', 'Tamil Nadu', '600034', 1, 152),
('College Road Nungambakkam', 'college-road-nungambakkam', 'Teynampet', 'Chennai', 'Tamil Nadu', '600034', 1, 153),
('Haddows Road', 'haddows-road', 'Teynampet', 'Chennai', 'Tamil Nadu', '600006', 1, 154),
('Khader Nawaz Khan Road', 'khader-nawaz-khan-road', 'Teynampet', 'Chennai', 'Tamil Nadu', '600006', 1, 155),
('Thousand Lights', 'thousand-lights', 'Teynampet', 'Chennai', 'Tamil Nadu', '600006', 1, 156),
('Greams Road', 'greams-road', 'Teynampet', 'Chennai', 'Tamil Nadu', '600006', 1, 157),
('Gopalapuram', 'gopalapuram', 'Teynampet', 'Chennai', 'Tamil Nadu', '600086', 1, 158),
('Lloyds Road', 'lloyds-road', 'Teynampet', 'Chennai', 'Tamil Nadu', '600014', 1, 159),
('Royapettah', 'royapettah', 'Teynampet', 'Chennai', 'Tamil Nadu', '600014', 1, 160),
('Triplicane', 'triplicane', 'Teynampet', 'Chennai', 'Tamil Nadu', '600005', 1, 161),
('Ice House', 'ice-house', 'Teynampet', 'Chennai', 'Tamil Nadu', '600005', 1, 162),
('Chepauk', 'chepauk', 'Teynampet', 'Chennai', 'Tamil Nadu', '600005', 1, 163),
('Marina Beach Road', 'marina-beach-road', 'Teynampet', 'Chennai', 'Tamil Nadu', '600005', 1, 164),
('Mylapore', 'mylapore', 'Teynampet', 'Chennai', 'Tamil Nadu', '600004', 1, 165),
('Luz Mylapore', 'luz-mylapore', 'Teynampet', 'Chennai', 'Tamil Nadu', '600004', 1, 166),
('San Thome', 'san-thome', 'Teynampet', 'Chennai', 'Tamil Nadu', '600004', 1, 167),
('Mandaveli', 'mandaveli', 'Teynampet', 'Chennai', 'Tamil Nadu', '600028', 1, 168),
('Raja Annamalai Puram', 'raja-annamalai-puram', 'Teynampet', 'Chennai', 'Tamil Nadu', '600028', 1, 169),
('RA Puram', 'ra-puram', 'Teynampet', 'Chennai', 'Tamil Nadu', '600028', 1, 170),
('Alwarpet', 'alwarpet', 'Teynampet', 'Chennai', 'Tamil Nadu', '600018', 1, 171),
('TTK Road Alwarpet', 'ttk-road-alwarpet', 'Teynampet', 'Chennai', 'Tamil Nadu', '600018', 1, 172),
('Eldams Road', 'eldams-road', 'Teynampet', 'Chennai', 'Tamil Nadu', '600018', 1, 173),
('Chamiers Road', 'chamiers-road', 'Teynampet', 'Chennai', 'Tamil Nadu', '600018', 1, 174),
('Poes Garden', 'poes-garden', 'Teynampet', 'Chennai', 'Tamil Nadu', '600086', 1, 175),
('Poes Road', 'poes-road', 'Teynampet', 'Chennai', 'Tamil Nadu', '600018', 1, 176),
('Kavignar Bharathidasan Road', 'kavignar-bharathidasan-road', 'Teynampet', 'Chennai', 'Tamil Nadu', '600018', 1, 177),
('Oliver Road', 'oliver-road', 'Teynampet', 'Chennai', 'Tamil Nadu', '600004', 1, 178),
('Kapaleeshwarar Nagar', 'kapaleeshwarar-nagar', 'Teynampet', 'Chennai', 'Tamil Nadu', '600004', 1, 179),
('Abiramapuram', 'abiramapuram', 'Teynampet', 'Chennai', 'Tamil Nadu', '600018', 1, 180),
('Kodambakkam', 'kodambakkam', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600024', 1, 181),
('T Nagar', 't-nagar', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600017', 1, 182),
('Pondy Bazaar', 'pondy-bazaar', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600017', 1, 183),
('Panagal Park', 'panagal-park', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600017', 1, 184),
('Usman Road', 'usman-road', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600017', 1, 185),
('Gopathi Narayanaswami Chetty Road', 'gopathi-narayanaswami-chetty-road', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600017', 1, 186),
('Burkit Road', 'burkit-road', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600017', 1, 187),
('Venkatnarayana Road', 'venkatnarayana-road', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600017', 1, 188),
('West Mambalam', 'west-mambalam', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600033', 1, 189),
('Arya Gowda Road', 'arya-gowda-road', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600033', 1, 190),
('Station Road West Mambalam', 'station-road-west-mambalam', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600033', 1, 191),
('Postal Colony West Mambalam', 'postal-colony-west-mambalam', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600033', 1, 192),
('Vadapalani', 'vadapalani', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600026', 1, 193),
('100 Feet Road Vadapalani', '100-feet-road-vadapalani', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600026', 1, 194),
('Vadapalani Murugan Temple Area', 'vadapalani-murugan-temple-area', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600026', 1, 195),
('Saligramam', 'saligramam', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600093', 1, 196),
('Kumaran Colony', 'kumaran-colony', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600026', 1, 197),
('Ashok Nagar', 'ashok-nagar', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600083', 1, 198),
('Ashok Pillar Area', 'ashok-pillar-area', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600083', 1, 199),
('1st Avenue Ashok Nagar', '1st-avenue-ashok-nagar', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600083', 1, 200),
('4th Avenue Ashok Nagar', '4th-avenue-ashok-nagar', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600083', 1, 201),
('KK Nagar', 'kk-nagar', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600078', 1, 202),
('Munusamy Salai', 'munusamy-salai', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600078', 1, 203),
('Ramasamy Salai', 'ramasamy-salai', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600078', 1, 204),
('Sector 1 KK Nagar', 'sector-1-kk-nagar', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600078', 1, 205),
('Sector 10 KK Nagar', 'sector-10-kk-nagar', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600078', 1, 206),
('MGR Nagar', 'mgr-nagar', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600078', 1, 207),
('Nesapakkam', 'nesapakkam', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600078', 1, 208),
('Saidapet', 'saidapet', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600015', 1, 209),
('Saidapet West', 'saidapet-west', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600015', 1, 210),
('Jones Road Saidapet', 'jones-road-saidapet', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600015', 1, 211),
('CIT Nagar', 'cit-nagar', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600035', 1, 212),
('Nandanam', 'nandanam', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600035', 1, 213),
('Chamiers Road Nandanam', 'chamiers-road-nandanam', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600035', 1, 214),
('Cenotaph Road', 'cenotaph-road', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600018', 1, 215),
('Valasaravakkam', 'valasaravakkam', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600087', 1, 216),
('Alwarthirunagar', 'alwarthirunagar', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600087', 1, 217),
('Virugambakkam', 'virugambakkam', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600092', 1, 218),
('Kasi Theater Area', 'kasi-theater-area', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600083', 1, 219),
('Chinmaya Nagar', 'chinmaya-nagar', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600092', 1, 220),
('Natesan Nagar', 'natesan-nagar', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600092', 1, 221),
('Porur', 'porur', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600116', 1, 222),
('Porur Lake Area', 'porur-lake-area', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600116', 1, 223),
('Porur Roundtana', 'porur-roundtana', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600116', 1, 224),
('Ramapuram', 'ramapuram', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600089', 1, 225),
('SRM Easwari College Area', 'srm-easwari-college-area', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600089', 1, 226),
('Rayala Nagar', 'rayala-nagar', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600089', 1, 227),
('Iyyappanthangal', 'iyyappanthangal', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600056', 1, 228),
('Oil Mill Road Iyyappanthangal', 'oil-mill-road-iyyappanthangal', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600056', 1, 229),
('Prestige Bella Vista Area', 'prestige-bella-vista-area', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600056', 1, 230),
('Kattupakkam', 'kattupakkam', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600056', 1, 231),
('Mugalivakkam', 'mugalivakkam', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600125', 1, 232),
('Manapakkam', 'manapakkam', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600125', 1, 233),
('L&T Infotech Manapakkam', 'l-t-infotech-manapakkam', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600125', 1, 234),
('DLF IT Park Porur', 'dlf-it-park-porur', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600089', 1, 235),
('Mount Poonamallee Road', 'mount-poonamallee-road', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600089', 1, 236),
('Karasangal', 'karasangal', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '601301', 1, 237),
('Gerugambakkam', 'gerugambakkam', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600128', 1, 238),
('Kolapakkam Porur', 'kolapakkam-porur', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600128', 1, 239),
('Madhanandapuram', 'madhanandapuram', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600125', 1, 240),
('Mowlivakkam', 'mowlivakkam', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600125', 1, 241),
('Srinivasapuram', 'srinivasapuram', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600056', 1, 242),
('Alandur', 'alandur', 'Alandur', 'Chennai', 'Tamil Nadu', '600016', 1, 243),
('Guindy', 'guindy', 'Alandur', 'Chennai', 'Tamil Nadu', '600032', 1, 244),
('Guindy Industrial Estate', 'guindy-industrial-estate', 'Alandur', 'Chennai', 'Tamil Nadu', '600032', 1, 245),
('Kathipara', 'kathipara', 'Alandur', 'Chennai', 'Tamil Nadu', '600016', 1, 246),
('Ekkatuthangal', 'ekkatuthangal', 'Alandur', 'Chennai', 'Tamil Nadu', '600032', 1, 247),
('CIPET Guindy', 'cipet-guindy', 'Alandur', 'Chennai', 'Tamil Nadu', '600032', 1, 248),
('Little Mount', 'little-mount', 'Alandur', 'Chennai', 'Tamil Nadu', '600015', 1, 249),
('Nanganallur', 'nanganallur', 'Alandur', 'Chennai', 'Tamil Nadu', '600061', 1, 250),
('Hindu Colony Nanganallur', 'hindu-colony-nanganallur', 'Alandur', 'Chennai', 'Tamil Nadu', '600061', 1, 251),
('Anjaneyar Temple Nanganallur', 'anjaneyar-temple-nanganallur', 'Alandur', 'Chennai', 'Tamil Nadu', '600061', 1, 252),
('Pazhavanthangal', 'pazhavanthangal', 'Alandur', 'Chennai', 'Tamil Nadu', '600114', 1, 253),
('Meenambakkam', 'meenambakkam', 'Alandur', 'Chennai', 'Tamil Nadu', '600027', 1, 254),
('Chennai Airport Area', 'chennai-airport-area', 'Alandur', 'Chennai', 'Tamil Nadu', '600027', 1, 255),
('Adambakkam', 'adambakkam', 'Alandur', 'Chennai', 'Tamil Nadu', '600088', 1, 256),
('Nilamangai Nagar', 'nilamangai-nagar', 'Alandur', 'Chennai', 'Tamil Nadu', '600088', 1, 257),
('Surendra Nagar', 'surendra-nagar', 'Alandur', 'Chennai', 'Tamil Nadu', '600088', 1, 258),
('Kakkan Nagar', 'kakkan-nagar', 'Alandur', 'Chennai', 'Tamil Nadu', '600088', 1, 259),
('Moovarasanpet', 'moovarasanpet', 'Alandur', 'Chennai', 'Tamil Nadu', '600091', 1, 260),
('St Thomas Mount', 'st-thomas-mount', 'Alandur', 'Chennai', 'Tamil Nadu', '600016', 1, 261),
('Butt Road St Thomas Mount', 'butt-road-st-thomas-mount', 'Alandur', 'Chennai', 'Tamil Nadu', '600016', 1, 262),
('Military Quarters Nandambakkam', 'military-quarters-nandambakkam', 'Alandur', 'Chennai', 'Tamil Nadu', '600089', 1, 263),
('Nandambakkam Trade Centre', 'nandambakkam-trade-centre', 'Alandur', 'Chennai', 'Tamil Nadu', '600089', 1, 264),
('Adyar', 'adyar', 'Adyar', 'Chennai', 'Tamil Nadu', '600020', 1, 265),
('Gandhi Nagar Adyar', 'gandhi-nagar-adyar', 'Adyar', 'Chennai', 'Tamil Nadu', '600020', 1, 266),
('Kasturba Nagar Adyar', 'kasturba-nagar-adyar', 'Adyar', 'Chennai', 'Tamil Nadu', '600020', 1, 267),
('Indira Nagar Adyar', 'indira-nagar-adyar', 'Adyar', 'Chennai', 'Tamil Nadu', '600020', 1, 268),
('Sardar Patel Road', 'sardar-patel-road', 'Adyar', 'Chennai', 'Tamil Nadu', '600020', 1, 269),
('Besant Nagar', 'besant-nagar', 'Adyar', 'Chennai', 'Tamil Nadu', '600090', 1, 270),
('Elliot Beach Besant Nagar', 'elliot-beach-besant-nagar', 'Adyar', 'Chennai', 'Tamil Nadu', '600090', 1, 271),
('Kalakshetra Colony', 'kalakshetra-colony', 'Adyar', 'Chennai', 'Tamil Nadu', '600090', 1, 272),
('Urvashi Nagar Besant Nagar', 'urvashi-nagar-besant-nagar', 'Adyar', 'Chennai', 'Tamil Nadu', '600090', 1, 273),
('Thiruvanmiyur', 'thiruvanmiyur', 'Adyar', 'Chennai', 'Tamil Nadu', '600041', 1, 274),
('Valmiki Nagar Thiruvanmiyur', 'valmiki-nagar-thiruvanmiyur', 'Adyar', 'Chennai', 'Tamil Nadu', '600041', 1, 275),
('Thiruvalluvar Nagar Thiruvanmiyur', 'thiruvalluvar-nagar-thiruvanmiyur', 'Adyar', 'Chennai', 'Tamil Nadu', '600041', 1, 276),
('Kamaraj Nagar Thiruvanmiyur', 'kamaraj-nagar-thiruvanmiyur', 'Adyar', 'Chennai', 'Tamil Nadu', '600041', 1, 277),
('RTO Office Thiruvanmiyur', 'rto-office-thiruvanmiyur', 'Adyar', 'Chennai', 'Tamil Nadu', '600041', 1, 278),
('Kotturpuram', 'kotturpuram', 'Adyar', 'Chennai', 'Tamil Nadu', '600085', 1, 279),
('Chitra Nagar Kotturpuram', 'chitra-nagar-kotturpuram', 'Adyar', 'Chennai', 'Tamil Nadu', '600085', 1, 280),
('Anna University Area', 'anna-university-area', 'Adyar', 'Chennai', 'Tamil Nadu', '600025', 1, 281),
('IIT Madras Campus', 'iit-madras-campus', 'Adyar', 'Chennai', 'Tamil Nadu', '600036', 1, 282),
('Cancer Institute Adyar', 'cancer-institute-adyar', 'Adyar', 'Chennai', 'Tamil Nadu', '600020', 1, 283),
('Malviya Avenue', 'malviya-avenue', 'Adyar', 'Chennai', 'Tamil Nadu', '600041', 1, 284),
('Lattice Bridge Road Adyar', 'lattice-bridge-road-adyar', 'Adyar', 'Chennai', 'Tamil Nadu', '600020', 1, 285),
('Velachery', 'velachery', 'Perungudi', 'Chennai', 'Tamil Nadu', '600042', 1, 286),
('Velachery Main Road', 'velachery-main-road', 'Perungudi', 'Chennai', 'Tamil Nadu', '600042', 1, 287),
('Velachery Bypass Road', 'velachery-bypass-road', 'Perungudi', 'Chennai', 'Tamil Nadu', '600042', 1, 288),
('Vijayanagar Velachery', 'vijayanagar-velachery', 'Perungudi', 'Chennai', 'Tamil Nadu', '600042', 1, 289),
('Baby Nagar Velachery', 'baby-nagar-velachery', 'Perungudi', 'Chennai', 'Tamil Nadu', '600042', 1, 290),
('Dhandeeswaram Nagar', 'dhandeeswaram-nagar', 'Perungudi', 'Chennai', 'Tamil Nadu', '600042', 1, 291),
('AGS Colony Velachery', 'ags-colony-velachery', 'Perungudi', 'Chennai', 'Tamil Nadu', '600042', 1, 292),
('Tansi Nagar', 'tansi-nagar', 'Perungudi', 'Chennai', 'Tamil Nadu', '600042', 1, 293),
('Bhuvaneshwari Nagar', 'bhuvaneshwari-nagar', 'Perungudi', 'Chennai', 'Tamil Nadu', '600042', 1, 294),
('Phoenix MarketCity Area', 'phoenix-marketcity-area', 'Perungudi', 'Chennai', 'Tamil Nadu', '600042', 1, 295),
('Madipakkam', 'madipakkam', 'Perungudi', 'Chennai', 'Tamil Nadu', '600091', 1, 296),
('Kuberan Nagar Madipakkam', 'kuberan-nagar-madipakkam', 'Perungudi', 'Chennai', 'Tamil Nadu', '600091', 1, 297),
('Balaiah Garden', 'balaiah-garden', 'Perungudi', 'Chennai', 'Tamil Nadu', '600091', 1, 298),
('Ram Nagar Madipakkam', 'ram-nagar-madipakkam', 'Perungudi', 'Chennai', 'Tamil Nadu', '600091', 1, 299),
('Puzhuthivakkam', 'puzhuthivakkam', 'Perungudi', 'Chennai', 'Tamil Nadu', '600091', 1, 300),
('Ullagaram', 'ullagaram', 'Perungudi', 'Chennai', 'Tamil Nadu', '600091', 1, 301),
('Keelkattalai', 'keelkattalai', 'Perungudi', 'Chennai', 'Tamil Nadu', '600117', 1, 302),
('Keelkattalai Junction', 'keelkattalai-junction', 'Perungudi', 'Chennai', 'Tamil Nadu', '600117', 1, 303),
('Perungudi', 'perungudi', 'Perungudi', 'Chennai', 'Tamil Nadu', '600096', 1, 304),
('Perungudi Industrial Estate', 'perungudi-industrial-estate', 'Perungudi', 'Chennai', 'Tamil Nadu', '600096', 1, 305),
('Kallukuttai', 'kallukuttai', 'Perungudi', 'Chennai', 'Tamil Nadu', '600096', 1, 306),
('Seevaram Perungudi', 'seevaram-perungudi', 'Perungudi', 'Chennai', 'Tamil Nadu', '600096', 1, 307),
('Kandanchavadi', 'kandanchavadi', 'Perungudi', 'Chennai', 'Tamil Nadu', '600096', 1, 308),
('Kandanchavadi MGR Salai', 'kandanchavadi-mgr-salai', 'Perungudi', 'Chennai', 'Tamil Nadu', '600096', 1, 309),
('Taramani', 'taramani', 'Perungudi', 'Chennai', 'Tamil Nadu', '600113', 1, 310),
('TIDEL Park Area', 'tidel-park-area', 'Perungudi', 'Chennai', 'Tamil Nadu', '600113', 1, 311),
('Ascendas IT Park Area', 'ascendas-it-park-area', 'Perungudi', 'Chennai', 'Tamil Nadu', '600113', 1, 312),
('SRP Tools OMR', 'srp-tools-omr', 'Perungudi', 'Chennai', 'Tamil Nadu', '600041', 1, 313),
('Palavanthangal Lake Area', 'palavanthangal-lake-area', 'Perungudi', 'Chennai', 'Tamil Nadu', '600114', 1, 314),
('Sholinganallur', 'sholinganallur', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '600119', 1, 315),
('Sholinganallur Junction', 'sholinganallur-junction', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '600119', 1, 316),
('Classic Farms Sholinganallur', 'classic-farms-sholinganallur', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '600119', 1, 317),
('ELCOT SEZ Sholinganallur', 'elcot-sez-sholinganallur', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '600119', 1, 318),
('Thoraipakkam', 'thoraipakkam', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '600097', 1, 319),
('Okkiyam Thoraipakkam', 'okkiyam-thoraipakkam', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '600097', 1, 320),
('Annanagar Thoraipakkam', 'annanagar-thoraipakkam', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '600097', 1, 321),
('Mettukuppam OMR', 'mettukuppam-omr', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '600097', 1, 322),
('Karapakkam', 'karapakkam', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '600097', 1, 323),
('TCS Karapakkam Area', 'tcs-karapakkam-area', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '600097', 1, 324),
('Semmancheri', 'semmancheri', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '600119', 1, 325),
('Sathyabama University Area', 'sathyabama-university-area', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '600119', 1, 326),
('Thalambur', 'thalambur', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '600130', 1, 327),
('Navalur', 'navalur', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '600130', 1, 328),
('Marina Mall Area Navalur', 'marina-mall-area-navalur', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '600130', 1, 329),
('Egattur', 'egattur', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '603103', 1, 330),
('Hiranandani Upscale Egattur', 'hiranandani-upscale-egattur', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '603103', 1, 331),
('Siruseri', 'siruseri', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '603103', 1, 332),
('SIPCOT IT Park Siruseri', 'sipcot-it-park-siruseri', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '603103', 1, 333),
('Kazhipattur', 'kazhipattur', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '603103', 1, 334),
('Padur', 'padur', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '603103', 1, 335),
('Kelambakkam', 'kelambakkam', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '603103', 1, 336),
('Kelambakkam Junction', 'kelambakkam-junction', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '603103', 1, 337),
('Thaiyur', 'thaiyur', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '603103', 1, 338),
('Thiruporur', 'thiruporur', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '603110', 1, 339),
('Alathur', 'alathur', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '603110', 1, 340),
('Illalur', 'illalur', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '603110', 1, 341),
('Kovalam Road Kelambakkam', 'kovalam-road-kelambakkam', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '603103', 1, 342),
('Pudupakkam', 'pudupakkam', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '603103', 1, 343),
('Vandalur Kelambakkam Road', 'vandalur-kelambakkam-road', 'Sholinganallur', 'Chennai', 'Tamil Nadu', '603103', 1, 344),
('Kottivakkam', 'kottivakkam', 'ECR', 'Chennai', 'Tamil Nadu', '600041', 1, 345),
('Kottivakkam Beach Road', 'kottivakkam-beach-road', 'ECR', 'Chennai', 'Tamil Nadu', '600041', 1, 346),
('Palavakkam', 'palavakkam', 'ECR', 'Chennai', 'Tamil Nadu', '600041', 1, 347),
('Palavakkam Beach', 'palavakkam-beach', 'ECR', 'Chennai', 'Tamil Nadu', '600041', 1, 348),
('Neelankarai', 'neelankarai', 'ECR', 'Chennai', 'Tamil Nadu', '600115', 1, 349),
('Kapaleeswarar Nagar Neelankarai', 'kapaleeswarar-nagar-neelankarai', 'ECR', 'Chennai', 'Tamil Nadu', '600115', 1, 350),
('Worker Colony Neelankarai', 'worker-colony-neelankarai', 'ECR', 'Chennai', 'Tamil Nadu', '600115', 1, 351),
('Injambakkam', 'injambakkam', 'ECR', 'Chennai', 'Tamil Nadu', '600115', 1, 352),
('Prarthana Beach Injambakkam', 'prarthana-beach-injambakkam', 'ECR', 'Chennai', 'Tamil Nadu', '600115', 1, 353),
('VGP Golden Beach Area', 'vgp-golden-beach-area', 'ECR', 'Chennai', 'Tamil Nadu', '600115', 1, 354),
('Akkarai', 'akkarai', 'ECR', 'Chennai', 'Tamil Nadu', '600119', 1, 355),
('Akkarai Beach', 'akkarai-beach', 'ECR', 'Chennai', 'Tamil Nadu', '600119', 1, 356),
('Panaiyur', 'panaiyur', 'ECR', 'Chennai', 'Tamil Nadu', '600119', 1, 357),
('Uthandi', 'uthandi', 'ECR', 'Chennai', 'Tamil Nadu', '600119', 1, 358),
('Uthandi Tollgate', 'uthandi-tollgate', 'ECR', 'Chennai', 'Tamil Nadu', '600119', 1, 359),
('Kanathur', 'kanathur', 'ECR', 'Chennai', 'Tamil Nadu', '603112', 1, 360),
('Muttukadu', 'muttukadu', 'ECR', 'Chennai', 'Tamil Nadu', '603112', 1, 361),
('Muttukadu Boat House', 'muttukadu-boat-house', 'ECR', 'Chennai', 'Tamil Nadu', '603112', 1, 362),
('Kovalam ECR', 'kovalam-ecr', 'ECR', 'Chennai', 'Tamil Nadu', '603112', 1, 363),
('Nemmeli', 'nemmeli', 'ECR', 'Chennai', 'Tamil Nadu', '603104', 1, 364),
('Pattipulam', 'pattipulam', 'ECR', 'Chennai', 'Tamil Nadu', '603104', 1, 365),
('Salavankuppam', 'salavankuppam', 'ECR', 'Chennai', 'Tamil Nadu', '603104', 1, 366),
('Mahabalipuram', 'mahabalipuram', 'ECR', 'Chennai', 'Tamil Nadu', '603104', 1, 367),
('Mamallapuram', 'mamallapuram', 'ECR', 'Chennai', 'Tamil Nadu', '603104', 1, 368),
('Poonjeri', 'poonjeri', 'ECR', 'Chennai', 'Tamil Nadu', '603104', 1, 369),
('Tambaram', 'tambaram', 'Tambaram', 'Chennai', 'Tamil Nadu', '600045', 1, 370),
('Tambaram West', 'tambaram-west', 'Tambaram', 'Chennai', 'Tamil Nadu', '600045', 1, 371),
('Tambaram East', 'tambaram-east', 'Tambaram', 'Chennai', 'Tamil Nadu', '600059', 1, 372),
('Tambaram Sanatorium', 'tambaram-sanatorium', 'Tambaram', 'Chennai', 'Tamil Nadu', '600047', 1, 373),
('Chromepet', 'chromepet', 'Tambaram', 'Chennai', 'Tamil Nadu', '600044', 1, 374),
('Radha Nagar Chromepet', 'radha-nagar-chromepet', 'Tambaram', 'Chennai', 'Tamil Nadu', '600044', 1, 375),
('Hasthinapuram', 'hasthinapuram', 'Tambaram', 'Chennai', 'Tamil Nadu', '600064', 1, 376),
('Nemilichery Chromepet', 'nemilichery-chromepet', 'Tambaram', 'Chennai', 'Tamil Nadu', '600044', 1, 377),
('Pallavaram', 'pallavaram', 'Tambaram', 'Chennai', 'Tamil Nadu', '600043', 1, 378),
('East Pallavaram', 'east-pallavaram', 'Tambaram', 'Chennai', 'Tamil Nadu', '600043', 1, 379),
('Old Pallavaram', 'old-pallavaram', 'Tambaram', 'Chennai', 'Tamil Nadu', '600117', 1, 380),
('Zamin Pallavaram', 'zamin-pallavaram', 'Tambaram', 'Chennai', 'Tamil Nadu', '600043', 1, 381),
('Cantonment Pallavaram', 'cantonment-pallavaram', 'Tambaram', 'Chennai', 'Tamil Nadu', '600043', 1, 382),
('Pammal', 'pammal', 'Tambaram', 'Chennai', 'Tamil Nadu', '600075', 1, 383),
('Anakaputhur', 'anakaputhur', 'Tambaram', 'Chennai', 'Tamil Nadu', '600070', 1, 384),
('Pozhichalur', 'pozhichalur', 'Tambaram', 'Chennai', 'Tamil Nadu', '600074', 1, 385),
('Cowl Bazaar', 'cowl-bazaar', 'Tambaram', 'Chennai', 'Tamil Nadu', '600074', 1, 386),
('Tirusulam', 'tirusulam', 'Tambaram', 'Chennai', 'Tamil Nadu', '600043', 1, 387),
('Chitlapakkam', 'chitlapakkam', 'Tambaram', 'Chennai', 'Tamil Nadu', '600064', 1, 388),
('Chitlapakkam Lake Area', 'chitlapakkam-lake-area', 'Tambaram', 'Chennai', 'Tamil Nadu', '600064', 1, 389),
('Selaiyur', 'selaiyur', 'Tambaram', 'Chennai', 'Tamil Nadu', '600073', 1, 390),
('Camp Road Selaiyur', 'camp-road-selaiyur', 'Tambaram', 'Chennai', 'Tamil Nadu', '600073', 1, 391),
('Rajakilpakkam', 'rajakilpakkam', 'Tambaram', 'Chennai', 'Tamil Nadu', '600073', 1, 392),
('Sembakkam', 'sembakkam', 'Tambaram', 'Chennai', 'Tamil Nadu', '600073', 1, 393),
('Gowrivakkam', 'gowrivakkam', 'Tambaram', 'Chennai', 'Tamil Nadu', '600073', 1, 394),
('Medavakkam', 'medavakkam', 'Tambaram', 'Chennai', 'Tamil Nadu', '600100', 1, 395),
('Medavakkam Koot Road', 'medavakkam-koot-road', 'Tambaram', 'Chennai', 'Tamil Nadu', '600100', 1, 396),
('Vengaivasal', 'vengaivasal', 'Tambaram', 'Chennai', 'Tamil Nadu', '600126', 1, 397),
('Madambakkam', 'madambakkam', 'Tambaram', 'Chennai', 'Tamil Nadu', '600126', 1, 398),
('Perumbakkam', 'perumbakkam', 'Tambaram', 'Chennai', 'Tamil Nadu', '600100', 1, 399),
('Global Hospital Perumbakkam', 'global-hospital-perumbakkam', 'Tambaram', 'Chennai', 'Tamil Nadu', '600100', 1, 400),
('Sithalapakkam', 'sithalapakkam', 'Tambaram', 'Chennai', 'Tamil Nadu', '600126', 1, 401),
('Ottiyambakkam', 'ottiyambakkam', 'Tambaram', 'Chennai', 'Tamil Nadu', '600130', 1, 402),
('Jalladianpet', 'jalladianpet', 'Tambaram', 'Chennai', 'Tamil Nadu', '600100', 1, 403),
('Kovilambakkam', 'kovilambakkam', 'Tambaram', 'Chennai', 'Tamil Nadu', '600129', 1, 404),
('Nanmangalam', 'nanmangalam', 'Tambaram', 'Chennai', 'Tamil Nadu', '600129', 1, 405),
('Nemilichery Medavakkam', 'nemilichery-medavakkam', 'Tambaram', 'Chennai', 'Tamil Nadu', '600100', 1, 406),
('Santhosapuram', 'santhosapuram', 'Tambaram', 'Chennai', 'Tamil Nadu', '600073', 1, 407),
('Mambakkam Tambaram', 'mambakkam-tambaram', 'Tambaram', 'Chennai', 'Tamil Nadu', '600127', 1, 408),
('Ponmar', 'ponmar', 'Tambaram', 'Chennai', 'Tamil Nadu', '600127', 1, 409),
('Polachery', 'polachery', 'Tambaram', 'Chennai', 'Tamil Nadu', '600127', 1, 410),
('Vengambakkam', 'vengambakkam', 'Tambaram', 'Chennai', 'Tamil Nadu', '600127', 1, 411),
('Kandigai', 'kandigai', 'Tambaram', 'Chennai', 'Tamil Nadu', '600127', 1, 412),
('Rathinamangalam', 'rathinamangalam', 'Tambaram', 'Chennai', 'Tamil Nadu', '600127', 1, 413),
('Tagore Engineering College Area', 'tagore-engineering-college-area', 'Tambaram', 'Chennai', 'Tamil Nadu', '600127', 1, 414),
('Perungalathur', 'perungalathur', 'GST Road', 'Chennai', 'Tamil Nadu', '600063', 1, 415),
('New Perungalathur', 'new-perungalathur', 'GST Road', 'Chennai', 'Tamil Nadu', '600063', 1, 416),
('Old Perungalathur', 'old-perungalathur', 'GST Road', 'Chennai', 'Tamil Nadu', '600063', 1, 417),
('Peerkankaranai', 'peerkankaranai', 'GST Road', 'Chennai', 'Tamil Nadu', '600063', 1, 418),
('Mudichur', 'mudichur', 'GST Road', 'Chennai', 'Tamil Nadu', '600048', 1, 419),
('Varadharajapuram Mudichur', 'varadharajapuram-mudichur', 'GST Road', 'Chennai', 'Tamil Nadu', '600048', 1, 420),
('Mannivakkam', 'mannivakkam', 'GST Road', 'Chennai', 'Tamil Nadu', '600048', 1, 421),
('Vandalur', 'vandalur', 'GST Road', 'Chennai', 'Tamil Nadu', '600048', 1, 422),
('Vandalur Zoo Area', 'vandalur-zoo-area', 'GST Road', 'Chennai', 'Tamil Nadu', '600048', 1, 423),
('Kilambakkam', 'kilambakkam', 'GST Road', 'Chennai', 'Tamil Nadu', '600048', 1, 424),
('KCBT Kilambakkam Bus Terminus', 'kcbt-kilambakkam-bus-terminus', 'GST Road', 'Chennai', 'Tamil Nadu', '600048', 1, 425),
('Urapakkam', 'urapakkam', 'GST Road', 'Chennai', 'Tamil Nadu', '603210', 1, 426),
('Urapakkam West', 'urapakkam-west', 'GST Road', 'Chennai', 'Tamil Nadu', '603210', 1, 427),
('Urapakkam East', 'urapakkam-east', 'GST Road', 'Chennai', 'Tamil Nadu', '603210', 1, 428),
('Guduvanchery', 'guduvanchery', 'GST Road', 'Chennai', 'Tamil Nadu', '603202', 1, 429),
('Nandivaram Guduvanchery', 'nandivaram-guduvanchery', 'GST Road', 'Chennai', 'Tamil Nadu', '603202', 1, 430),
('Thailavaram', 'thailavaram', 'GST Road', 'Chennai', 'Tamil Nadu', '603203', 1, 431),
('Potheri', 'potheri', 'GST Road', 'Chennai', 'Tamil Nadu', '603203', 1, 432),
('SRM University Potheri', 'srm-university-potheri', 'GST Road', 'Chennai', 'Tamil Nadu', '603203', 1, 433),
('Kattankulathur', 'kattankulathur', 'GST Road', 'Chennai', 'Tamil Nadu', '603203', 1, 434),
('Maraimalai Nagar', 'maraimalai-nagar', 'GST Road', 'Chennai', 'Tamil Nadu', '603209', 1, 435),
('Ford Plant Area Maraimalai Nagar', 'ford-plant-area-maraimalai-nagar', 'GST Road', 'Chennai', 'Tamil Nadu', '603209', 1, 436),
('Mahindra World City', 'mahindra-world-city', 'GST Road', 'Chennai', 'Tamil Nadu', '603002', 1, 437),
('Paranur', 'paranur', 'GST Road', 'Chennai', 'Tamil Nadu', '603002', 1, 438),
('Singaperumal Koil', 'singaperumal-koil', 'GST Road', 'Chennai', 'Tamil Nadu', '603204', 1, 439),
('Appur', 'appur', 'GST Road', 'Chennai', 'Tamil Nadu', '603204', 1, 440),
('Chettipunyam', 'chettipunyam', 'GST Road', 'Chennai', 'Tamil Nadu', '603204', 1, 441),
('Chengalpattu', 'chengalpattu', 'GST Road', 'Chennai', 'Tamil Nadu', '603001', 1, 442),
('Chengalpattu Town', 'chengalpattu-town', 'GST Road', 'Chennai', 'Tamil Nadu', '603001', 1, 443),
('Gundu Medu Chengalpattu', 'gundu-medu-chengalpattu', 'GST Road', 'Chennai', 'Tamil Nadu', '603001', 1, 444),
('Melamaiyur', 'melamaiyur', 'GST Road', 'Chennai', 'Tamil Nadu', '603001', 1, 445),
('Thimmavaram', 'thimmavaram', 'GST Road', 'Chennai', 'Tamil Nadu', '603101', 1, 446),
('Alapakkam Chengalpattu', 'alapakkam-chengalpattu', 'GST Road', 'Chennai', 'Tamil Nadu', '603003', 1, 447),
('Poonamallee', 'poonamallee', 'West Corridor', 'Chennai', 'Tamil Nadu', '600056', 1, 448),
('Poonamallee Bus Terminus', 'poonamallee-bus-terminus', 'West Corridor', 'Chennai', 'Tamil Nadu', '600056', 1, 449),
('Kumananchavadi', 'kumananchavadi', 'West Corridor', 'Chennai', 'Tamil Nadu', '600056', 1, 450),
('Mangadu', 'mangadu', 'West Corridor', 'Chennai', 'Tamil Nadu', '600122', 1, 451),
('Mangadu Temple Area', 'mangadu-temple-area', 'West Corridor', 'Chennai', 'Tamil Nadu', '600122', 1, 452),
('Senneerkuppam', 'senneerkuppam', 'West Corridor', 'Chennai', 'Tamil Nadu', '600056', 1, 453),
('Nazarathpet', 'nazarathpet', 'West Corridor', 'Chennai', 'Tamil Nadu', '600123', 1, 454),
('Varadharajapuram Poonamallee', 'varadharajapuram-poonamallee', 'West Corridor', 'Chennai', 'Tamil Nadu', '600123', 1, 455),
('Thirumazhisai', 'thirumazhisai', 'West Corridor', 'Chennai', 'Tamil Nadu', '600124', 1, 456),
('Thirumazhisai Satellite Town', 'thirumazhisai-satellite-town', 'West Corridor', 'Chennai', 'Tamil Nadu', '600124', 1, 457),
('Chembarambakkam', 'chembarambakkam', 'West Corridor', 'Chennai', 'Tamil Nadu', '600123', 1, 458),
('Chembarambakkam Lake Area', 'chembarambakkam-lake-area', 'West Corridor', 'Chennai', 'Tamil Nadu', '600123', 1, 459),
('Irungattukottai', 'irungattukottai', 'West Corridor', 'Chennai', 'Tamil Nadu', '602117', 1, 460),
('SIPCOT Irungattukottai', 'sipcot-irungattukottai', 'West Corridor', 'Chennai', 'Tamil Nadu', '602117', 1, 461),
('Thandalam', 'thandalam', 'West Corridor', 'Chennai', 'Tamil Nadu', '602105', 1, 462),
('Saveetha University Area Thandalam', 'saveetha-university-area-thandalam', 'West Corridor', 'Chennai', 'Tamil Nadu', '602105', 1, 463),
('Nemam', 'nemam', 'West Corridor', 'Chennai', 'Tamil Nadu', '600124', 1, 464),
('Pennalur', 'pennalur', 'West Corridor', 'Chennai', 'Tamil Nadu', '602117', 1, 465),
('Sriperumbudur', 'sriperumbudur', 'West Corridor', 'Chennai', 'Tamil Nadu', '602105', 1, 466),
('Sriperumbudur SIPCOT', 'sriperumbudur-sipcot', 'West Corridor', 'Chennai', 'Tamil Nadu', '602105', 1, 467),
('Rajiv Gandhi Memorial Area', 'rajiv-gandhi-memorial-area', 'West Corridor', 'Chennai', 'Tamil Nadu', '602105', 1, 468),
('Sunguvarchatram', 'sunguvarchatram', 'West Corridor', 'Chennai', 'Tamil Nadu', '602106', 1, 469),
('Mambakkam Sriperumbudur', 'mambakkam-sriperumbudur', 'West Corridor', 'Chennai', 'Tamil Nadu', '602105', 1, 470),
('Vallam Vadagal', 'vallam-vadagal', 'West Corridor', 'Chennai', 'Tamil Nadu', '602105', 1, 471),
('Oragadam', 'oragadam', 'West Corridor', 'Chennai', 'Tamil Nadu', '602105', 1, 472),
('Oragadam Industrial Corridor', 'oragadam-industrial-corridor', 'West Corridor', 'Chennai', 'Tamil Nadu', '602105', 1, 473),
('Padappai', 'padappai', 'West Corridor', 'Chennai', 'Tamil Nadu', '601301', 1, 474),
('Salamangalam', 'salamangalam', 'West Corridor', 'Chennai', 'Tamil Nadu', '601301', 1, 475),
('Manimangalam', 'manimangalam', 'West Corridor', 'Chennai', 'Tamil Nadu', '601301', 1, 476),
('Somangalam', 'somangalam', 'West Corridor', 'Chennai', 'Tamil Nadu', '602109', 1, 477),
('Karanithangal', 'karanithangal', 'West Corridor', 'Chennai', 'Tamil Nadu', '602105', 1, 478),
('Kundrathur', 'kundrathur', 'West Corridor', 'Chennai', 'Tamil Nadu', '600069', 1, 479),
('Kundrathur Murugan Temple Area', 'kundrathur-murugan-temple-area', 'West Corridor', 'Chennai', 'Tamil Nadu', '600069', 1, 480),
('Mehta Nagar Kundrathur', 'mehta-nagar-kundrathur', 'West Corridor', 'Chennai', 'Tamil Nadu', '600069', 1, 481),
('Anakaputhur West', 'anakaputhur-west', 'West Corridor', 'Chennai', 'Tamil Nadu', '600070', 1, 482),
('Thiruneermalai', 'thiruneermalai', 'West Corridor', 'Chennai', 'Tamil Nadu', '600044', 1, 483),
('Thiruneermalai Temple Area', 'thiruneermalai-temple-area', 'West Corridor', 'Chennai', 'Tamil Nadu', '600044', 1, 484),
('Thandalam Kundrathur', 'thandalam-kundrathur', 'West Corridor', 'Chennai', 'Tamil Nadu', '600069', 1, 485),
('Kovur', 'kovur', 'West Corridor', 'Chennai', 'Tamil Nadu', '600128', 1, 486),
('Chikkarayapuram', 'chikkarayapuram', 'West Corridor', 'Chennai', 'Tamil Nadu', '600069', 1, 487),
('Avadi', 'avadi', 'Avadi', 'Chennai', 'Tamil Nadu', '600054', 1, 488),
('Avadi Checkpost', 'avadi-checkpost', 'Avadi', 'Chennai', 'Tamil Nadu', '600054', 1, 489),
('HVF Estate Avadi', 'hvf-estate-avadi', 'Avadi', 'Chennai', 'Tamil Nadu', '600054', 1, 490),
('IAF Avadi', 'iaf-avadi', 'Avadi', 'Chennai', 'Tamil Nadu', '600055', 1, 491),
('OCF Avadi', 'ocf-avadi', 'Avadi', 'Chennai', 'Tamil Nadu', '600054', 1, 492),
('CRPF Camp Avadi', 'crpf-camp-avadi', 'Avadi', 'Chennai', 'Tamil Nadu', '600065', 1, 493),
('Pattabiram', 'pattabiram', 'Avadi', 'Chennai', 'Tamil Nadu', '600072', 1, 494),
('TIDEL Park Pattabiram', 'tidel-park-pattabiram', 'Avadi', 'Chennai', 'Tamil Nadu', '600072', 1, 495),
('Thiruninravur', 'thiruninravur', 'Avadi', 'Chennai', 'Tamil Nadu', '602024', 1, 496),
('Thiruninravur Lake Area', 'thiruninravur-lake-area', 'Avadi', 'Chennai', 'Tamil Nadu', '602024', 1, 497),
('Mittanamallee', 'mittanamallee', 'Avadi', 'Chennai', 'Tamil Nadu', '600055', 1, 498),
('Morai', 'morai', 'Avadi', 'Chennai', 'Tamil Nadu', '600055', 1, 499),
('Veerapuram', 'veerapuram', 'Avadi', 'Chennai', 'Tamil Nadu', '600055', 1, 500),
('Kovilpathu', 'kovilpathu', 'Avadi', 'Chennai', 'Tamil Nadu', '600062', 1, 501),
('Thirumullaivoyal', 'thirumullaivoyal', 'Avadi', 'Chennai', 'Tamil Nadu', '600062', 1, 502),
('Thirumullaivoyal Women Industrial Estate', 'thirumullaivoyal-women-industrial-estate', 'Avadi', 'Chennai', 'Tamil Nadu', '600062', 1, 503),
('Pachaiamman Nagar', 'pachaiamman-nagar', 'Avadi', 'Chennai', 'Tamil Nadu', '600062', 1, 504),
('Senthil Nagar Thirumullaivoyal', 'senthil-nagar-thirumullaivoyal', 'Avadi', 'Chennai', 'Tamil Nadu', '600062', 1, 505),
('Vellanur', 'vellanur', 'Avadi', 'Chennai', 'Tamil Nadu', '600062', 1, 506),
('Pothur', 'pothur', 'Avadi', 'Chennai', 'Tamil Nadu', '600062', 1, 507),
('Alamathi', 'alamathi', 'Avadi', 'Chennai', 'Tamil Nadu', '600052', 1, 508),
('Sholavaram', 'sholavaram', 'Avadi', 'Chennai', 'Tamil Nadu', '600067', 1, 509),
('Karanodai', 'karanodai', 'Avadi', 'Chennai', 'Tamil Nadu', '600067', 1, 510),
('Janapachatram', 'janapachatram', 'Avadi', 'Chennai', 'Tamil Nadu', '600067', 1, 511),
('Minjur', 'minjur', 'Avadi', 'Chennai', 'Tamil Nadu', '601203', 1, 512),
('Ponneri', 'ponneri', 'Avadi', 'Chennai', 'Tamil Nadu', '601204', 1, 513),
('Gummudipoondi', 'gummudipoondi', 'Avadi', 'Chennai', 'Tamil Nadu', '601201', 1, 514),
('Kavaraipettai', 'kavaraipettai', 'Avadi', 'Chennai', 'Tamil Nadu', '601206', 1, 515),
('Thiruvallur', 'thiruvallur', 'Avadi', 'Chennai', 'Tamil Nadu', '602001', 1, 516),
('Thiruvallur Town', 'thiruvallur-town', 'Avadi', 'Chennai', 'Tamil Nadu', '602001', 1, 517),
('Veeraraghavaswamy Temple Area', 'veeraraghavaswamy-temple-area', 'Avadi', 'Chennai', 'Tamil Nadu', '602001', 1, 518),
('Manavala Nagar', 'manavala-nagar', 'Avadi', 'Chennai', 'Tamil Nadu', '602002', 1, 519),
('Kakkalur', 'kakkalur', 'Avadi', 'Chennai', 'Tamil Nadu', '602003', 1, 520),
('Kakkalur Industrial Estate', 'kakkalur-industrial-estate', 'Avadi', 'Chennai', 'Tamil Nadu', '602003', 1, 521),
('Putlur', 'putlur', 'Avadi', 'Chennai', 'Tamil Nadu', '602025', 1, 522),
('Sevvapet', 'sevvapet', 'Avadi', 'Chennai', 'Tamil Nadu', '602025', 1, 523),
('Vepampattu', 'vepampattu', 'Avadi', 'Chennai', 'Tamil Nadu', '602024', 1, 524),
('Perumalpattu', 'perumalpattu', 'Avadi', 'Chennai', 'Tamil Nadu', '602024', 1, 525),
('Nemilichery Avadi', 'nemilichery-avadi', 'Avadi', 'Chennai', 'Tamil Nadu', '602024', 1, 526),
('Besant Avenue Adyar', 'besant-avenue-adyar', 'Adyar', 'Chennai', 'Tamil Nadu', '600020', 1, 527),
('Padmanabha Nagar Adyar', 'padmanabha-nagar-adyar', 'Adyar', 'Chennai', 'Tamil Nadu', '600020', 1, 528),
('Karpagam Gardens Adyar', 'karpagam-gardens-adyar', 'Adyar', 'Chennai', 'Tamil Nadu', '600020', 1, 529),
('Sastri Nagar Adyar', 'sastri-nagar-adyar', 'Adyar', 'Chennai', 'Tamil Nadu', '600020', 1, 530),
('Damodarapuram Adyar', 'damodarapuram-adyar', 'Adyar', 'Chennai', 'Tamil Nadu', '600020', 1, 531),
('Nehru Nagar Adyar', 'nehru-nagar-adyar', 'Adyar', 'Chennai', 'Tamil Nadu', '600020', 1, 532),
('Venkatarathinam Nagar Adyar', 'venkatarathinam-nagar-adyar', 'Adyar', 'Chennai', 'Tamil Nadu', '600020', 1, 533),
('Indira Nagar 1st Avenue', 'indira-nagar-1st-avenue', 'Adyar', 'Chennai', 'Tamil Nadu', '600020', 1, 534),
('Indira Nagar Water Tank Area', 'indira-nagar-water-tank-area', 'Adyar', 'Chennai', 'Tamil Nadu', '600020', 1, 535),
('Customs Colony Besant Nagar', 'customs-colony-besant-nagar', 'Adyar', 'Chennai', 'Tamil Nadu', '600090', 1, 536),
('CPWD Quarters Besant Nagar', 'cpwd-quarters-besant-nagar', 'Adyar', 'Chennai', 'Tamil Nadu', '600090', 1, 537),
('Tiger Varadachari Road', 'tiger-varadachari-road', 'Adyar', 'Chennai', 'Tamil Nadu', '600090', 1, 538),
('Rukmini Road Besant Nagar', 'rukmini-road-besant-nagar', 'Adyar', 'Chennai', 'Tamil Nadu', '600090', 1, 539),
('Arunachalam Avenue Thiruvanmiyur', 'arunachalam-avenue-thiruvanmiyur', 'Adyar', 'Chennai', 'Tamil Nadu', '600041', 1, 540),
('South Mada Street Thiruvanmiyur', 'south-mada-street-thiruvanmiyur', 'Adyar', 'Chennai', 'Tamil Nadu', '600041', 1, 541),
('Marundeeswarar Temple Area', 'marundeeswarar-temple-area', 'Adyar', 'Chennai', 'Tamil Nadu', '600041', 1, 542),
('Seaward Road Thiruvanmiyur', 'seaward-road-thiruvanmiyur', 'Adyar', 'Chennai', 'Tamil Nadu', '600041', 1, 543),
('Radhakrishnan Nagar Thiruvanmiyur', 'radhakrishnan-nagar-thiruvanmiyur', 'Adyar', 'Chennai', 'Tamil Nadu', '600041', 1, 544),
('Kalaivanar Nagar Thiruvanmiyur', 'kalaivanar-nagar-thiruvanmiyur', 'Adyar', 'Chennai', 'Tamil Nadu', '600041', 1, 545),
('Brodies Road RA Puram', 'brodies-road-ra-puram', 'Teynampet', 'Chennai', 'Tamil Nadu', '600028', 1, 546),
('Greenways Road', 'greenways-road', 'Teynampet', 'Chennai', 'Tamil Nadu', '600028', 1, 547),
('Bishop Garden RA Puram', 'bishop-garden-ra-puram', 'Teynampet', 'Chennai', 'Tamil Nadu', '600028', 1, 548),
('Karpagam Avenue RA Puram', 'karpagam-avenue-ra-puram', 'Teynampet', 'Chennai', 'Tamil Nadu', '600028', 1, 549),
('De Silva Road Mylapore', 'de-silva-road-mylapore', 'Teynampet', 'Chennai', 'Tamil Nadu', '600004', 1, 550),
('Kutchery Road Mylapore', 'kutchery-road-mylapore', 'Teynampet', 'Chennai', 'Tamil Nadu', '600004', 1, 551),
('Mundakakanni Amman Koil', 'mundakakanni-amman-koil', 'Teynampet', 'Chennai', 'Tamil Nadu', '600004', 1, 552),
('Luz Church Road', 'luz-church-road', 'Teynampet', 'Chennai', 'Tamil Nadu', '600004', 1, 553),
('Alamelumangapuram', 'alamelumangapuram', 'Teynampet', 'Chennai', 'Tamil Nadu', '600004', 1, 554),
('Pelathope Mylapore', 'pelathope-mylapore', 'Teynampet', 'Chennai', 'Tamil Nadu', '600004', 1, 555),
('Venkatesa Agraharam', 'venkatesa-agraharam', 'Teynampet', 'Chennai', 'Tamil Nadu', '600004', 1, 556),
('CIT Colony Mylapore', 'cit-colony-mylapore', 'Teynampet', 'Chennai', 'Tamil Nadu', '600004', 1, 557),
('Nageswara Rao Park Area', 'nageswara-rao-park-area', 'Teynampet', 'Chennai', 'Tamil Nadu', '600004', 1, 558),
('Warren Road Mylapore', 'warren-road-mylapore', 'Teynampet', 'Chennai', 'Tamil Nadu', '600004', 1, 559),
('Bheemanna Garden Alwarpet', 'bheemanna-garden-alwarpet', 'Teynampet', 'Chennai', 'Tamil Nadu', '600018', 1, 560),
('Sriman Srinivasa Road Alwarpet', 'sriman-srinivasa-road-alwarpet', 'Teynampet', 'Chennai', 'Tamil Nadu', '600018', 1, 561),
('Co-operative Colony Alwarpet', 'co-operative-colony-alwarpet', 'Teynampet', 'Chennai', 'Tamil Nadu', '600018', 1, 562),
('Seethammal Colony', 'seethammal-colony', 'Teynampet', 'Chennai', 'Tamil Nadu', '600018', 1, 563),
('Seethammal Extension', 'seethammal-extension', 'Teynampet', 'Chennai', 'Tamil Nadu', '600018', 1, 564),
('Teynampet Signal Area', 'teynampet-signal-area', 'Teynampet', 'Chennai', 'Tamil Nadu', '600018', 1, 565),
('SIET College Area', 'siet-college-area', 'Teynampet', 'Chennai', 'Tamil Nadu', '600018', 1, 566),
('Cathedral Road', 'cathedral-road', 'Teynampet', 'Chennai', 'Tamil Nadu', '600086', 1, 567),
('Stella Maris College Area', 'stella-maris-college-area', 'Teynampet', 'Chennai', 'Tamil Nadu', '600086', 1, 568),
('Binny Road', 'binny-road', 'Teynampet', 'Chennai', 'Tamil Nadu', '600006', 1, 569),
('Peshwar Road', 'peshwar-road', 'Teynampet', 'Chennai', 'Tamil Nadu', '600006', 1, 570),
('Spurtank Road Chetpet', 'spurtank-road-chetpet', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600031', 1, 571),
('Mayor Ramanathan Salai', 'mayor-ramanathan-salai', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600031', 1, 572),
('Ormes Road Kilpauk', 'ormes-road-kilpauk', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600010', 1, 573),
('Poonamallee High Road Kilpauk', 'poonamallee-high-road-kilpauk', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600010', 1, 574),
('Balfour Road Kilpauk', 'balfour-road-kilpauk', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600010', 1, 575),
('Landons Road Kilpauk', 'landons-road-kilpauk', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600010', 1, 576),
('Halls Road Egmore', 'halls-road-egmore', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600008', 1, 577),
('Pantheon Road Egmore', 'pantheon-road-egmore', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600008', 1, 578),
('Gandhi Irwin Road Egmore', 'gandhi-irwin-road-egmore', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600008', 1, 579),
('Egmore Railway Station Area', 'egmore-railway-station-area', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600008', 1, 580),
('Rajarathinam Stadium Area', 'rajarathinam-stadium-area', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600008', 1, 581),
('Police Commissioner Road Egmore', 'police-commissioner-road-egmore', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600008', 1, 582),
('Whannels Road Egmore', 'whannels-road-egmore', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600008', 1, 583),
('Montieth Road Egmore', 'montieth-road-egmore', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600008', 1, 584),
('Commander-in-Chief Road', 'commander-in-chief-road', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600105', 1, 585),
('Ethiraj Salai', 'ethiraj-salai', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600008', 1, 586),
('Chamiers Road Nandanam Extension', 'chamiers-road-nandanam-extension', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600035', 1, 587),
('Turnbulls Road Nandanam', 'turnbulls-road-nandanam', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600035', 1, 588),
('CIT Nagar East', 'cit-nagar-east', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600035', 1, 589),
('CIT Nagar West', 'cit-nagar-west', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600035', 1, 590),
('South Boag Road', 'south-boag-road', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600017', 1, 591),
('North Boag Road', 'north-boag-road', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600017', 1, 592),
('Habibullah Road T Nagar', 'habibullah-road-t-nagar', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600017', 1, 593),
('Vidyodaya 1st Cross Street', 'vidyodaya-1st-cross-street', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600017', 1, 594),
('Raghavaiah Road T Nagar', 'raghavaiah-road-t-nagar', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600017', 1, 595),
('Bazullah Road T Nagar', 'bazullah-road-t-nagar', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600017', 1, 596),
('Venkatanarayana Road T Nagar', 'venkatanarayana-road-t-nagar', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600017', 1, 597),
('Mangesh Street T Nagar', 'mangesh-street-t-nagar', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600017', 1, 598),
('Madley Road T Nagar', 'madley-road-t-nagar', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600017', 1, 599),
('Doraiswamy Subway Area', 'doraiswamy-subway-area', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600017', 1, 600),
('Lake View Road West Mambalam', 'lake-view-road-west-mambalam', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600033', 1, 601),
('Govindan Road West Mambalam', 'govindan-road-west-mambalam', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600033', 1, 602),
('Thambiah Road West Mambalam', 'thambiah-road-west-mambalam', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600033', 1, 603),
('Jubilee Road West Mambalam', 'jubilee-road-west-mambalam', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600033', 1, 604),
('Eswaran Koil Street West Mambalam', 'eswaran-koil-street-west-mambalam', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600033', 1, 605),
('Brindavan Street West Mambalam', 'brindavan-street-west-mambalam', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600033', 1, 606),
('Rangarajapuram', 'rangarajapuram', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600024', 1, 607),
('United India Colony', 'united-india-colony', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600024', 1, 608),
('Trustpuram Kodambakkam', 'trustpuram-kodambakkam', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600024', 1, 609),
('Subbarayan Nagar Kodambakkam', 'subbarayan-nagar-kodambakkam', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600024', 1, 610),
('Puliyur Kodambakkam', 'puliyur-kodambakkam', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600024', 1, 611),
('Arcot Road Kodambakkam', 'arcot-road-kodambakkam', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600024', 1, 612),
('Palani Andavar Koil Street', 'palani-andavar-koil-street', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600024', 1, 613),
('Vadapalani Signal Area', 'vadapalani-signal-area', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600026', 1, 614),
('AVM Studios Area Vadapalani', 'avm-studios-area-vadapalani', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600026', 1, 615),
('SIMS Hospital Area Vadapalani', 'sims-hospital-area-vadapalani', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600026', 1, 616),
('Doshi Symphony Area Vadapalani', 'doshi-symphony-area-vadapalani', 'Kodambakkam', 'Chennai', 'Tamil Nadu', '600026', 1, 617),
('Vembuliamman Koil Virugambakkam', 'vembuliamman-koil-virugambakkam', 'Valasaravakkam', 'Chennai', 'Tamil Nadu', '600092', 1, 618),
('Koyambedu Wholesale Market', 'koyambedu-wholesale-market', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600107', 1, 619),
('Rohini Silver Screens Area', 'rohini-silver-screens-area', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600107', 1, 620),
('Koyambedu Roundtana', 'koyambedu-roundtana', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600107', 1, 621),
('Choolaimedu High Road', 'choolaimedu-high-road', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600094', 1, 622),
('Sowrashtra Nagar Choolaimedu', 'sowrashtra-nagar-choolaimedu', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600094', 1, 623),
('Namachivaya Puram', 'namachivaya-puram', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600094', 1, 624),
('Metha Nagar Choolaimedu', 'metha-nagar-choolaimedu', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600094', 1, 625),
('Railway Colony Aminjikarai', 'railway-colony-aminjikarai', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600029', 1, 626),
('Pulla Avenue Shenoy Nagar', 'pulla-avenue-shenoy-nagar', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600030', 1, 627),
('East Club Road Shenoy Nagar', 'east-club-road-shenoy-nagar', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600030', 1, 628),
('West Club Road Shenoy Nagar', 'west-club-road-shenoy-nagar', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600030', 1, 629),
('Kandhasamy Street Shenoy Nagar', 'kandhasamy-street-shenoy-nagar', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600030', 1, 630),
('Anna Nagar 2nd Avenue', 'anna-nagar-2nd-avenue', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600040', 1, 631),
('Anna Nagar 3rd Avenue', 'anna-nagar-3rd-avenue', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600040', 1, 632),
('Anna Nagar 4th Avenue', 'anna-nagar-4th-avenue', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600040', 1, 633),
('Anna Nagar 5th Avenue', 'anna-nagar-5th-avenue', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600040', 1, 634),
('Anna Nagar 6th Avenue', 'anna-nagar-6th-avenue', 'Anna Nagar', 'Chennai', 'Tamil Nadu', '600040', 1, 635);

-- 3. SEO Service Keywords Table (970 Keywords)
DROP TABLE IF EXISTS `seo_service_keywords`;
/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: gcmsafetynets_db
-- ------------------------------------------------------
-- Server version	11.8.6-MariaDB-5ubuntu0.1 from Ubuntu

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `seo_service_keywords`
--

DROP TABLE IF EXISTS `seo_service_keywords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seo_service_keywords` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `keyword_name` varchar(255) NOT NULL,
  `keyword_slug` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `search_volume` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `difficulty` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_keyword_slug` (`keyword_slug`),
  UNIQUE KEY `uq_kw_slug` (`keyword_slug`(191)),
  KEY `idx_service_id` (`service_id`),
  CONSTRAINT `kw_service_fk` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=929418 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seo_service_keywords`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `seo_service_keywords` WRITE;
/*!40000 ALTER TABLE `seo_service_keywords` DISABLE KEYS */;
INSERT INTO `seo_service_keywords` VALUES (1,1,'Pigeon Nets','pigeon-nets','PIGEON NETS',1000,1,1,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (2,1,'Pigeon Net','pigeon-net','PIGEON NETS',1000,1,2,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (3,66,'Balcony Netting','balcony-netting','SAFETY NETS',1000,1,3,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (4,1,'Pigeon Net For Balcony','pigeon-net-for-balcony','PIGEON NETS',1000,1,4,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (5,1,'Pigeon Nets Installation','pigeon-nets-installation','PIGEON NETS',1000,1,5,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (6,6,'Pigeon Bird Netting','pigeon-bird-netting','PIGEON NETS',1000,1,6,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (7,1,'Pigeon Net Installation','pigeon-net-installation','PIGEON NETS',1000,1,7,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (8,1,'Pigeon Net Near Me','pigeon-net-near-me','PIGEON NETS',1000,1,8,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (9,1,'Pigeon Net For Balcony Near Me','pigeon-net-for-balcony-near-me','PIGEON NETS',1000,1,9,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (10,10,'Pigeon Net Installation Near Me','pigeon-net-installation-near-me','PIGEON NETS',1000,1,10,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (11,1,'Pigeon Safety Nets','pigeon-safety-nets','PIGEON NETS',1000,1,11,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (12,1,'Pigeon Net Price','pigeon-net-price','PIGEON NETS',1000,1,12,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (13,13,'Kabutar Jali Near Me','kabutar-jali-near-me','PIGEON NETS',1000,1,13,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (14,10,'Bird Nets','bird-nets','BIRD NETS',1000,1,14,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (15,10,'Bird Net','bird-net','BIRD NETS',1000,1,15,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (16,10,'Bird Net For Balcony','bird-net-for-balcony','BIRD NETS',1000,1,16,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (17,10,'Bird Net Near Me','bird-net-near-me','BIRD NETS',1000,1,17,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (18,18,'Nets For Birds','nets-for-birds','BIRD NETS',1000,1,18,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (19,19,'Net For Birds','net-for-birds','BIRD NETS',1000,1,19,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (20,10,'Industrial Bird Netting','industrial-bird-netting','BIRD NETS',1000,1,20,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (21,21,'Bird Netting','bird-netting','BIRD NETS',1000,1,21,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (23,23,'Safety Nets','safety-nets','SAFETY NETS',1000,1,23,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (25,66,'Safety Nets For Balconies','safety-nets-for-balconies','SAFETY NETS',1000,1,25,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (26,26,'Duct Area Safety Nets','duct-area-safety-nets','SAFETY NETS',1000,1,26,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (27,27,'Monkey Safety Nets','monkey-safety-nets','SAFETY NETS',1000,1,27,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (28,28,'Construction Safety Nets','construction-safety-nets','SAFETY NETS',1000,1,28,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (29,29,'Industrial Safety Nets','industrial-safety-nets','SAFETY NETS',1000,1,29,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (30,30,'Fall Safety Nets','fall-safety-nets','SAFETY NETS',1000,1,30,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (31,31,'Fall Protection Nets','fall-protection-nets','SAFETY NETS',1000,1,31,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (32,32,'Children Safety Nets','children-safety-nets','SAFETY NETS',1000,1,32,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (33,33,'Pet Safety Nets','pet-safety-nets','SAFETY NETS',1000,1,33,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (36,515,'Cricket Nets Near Me','cricket-nets-near-me','SPORTS NETS',1000,1,36,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (37,515,'Cricket Practice Net','cricket-practice-net','SPORTS NETS',1000,1,37,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (38,515,'Cricket Practice Nets','cricket-practice-nets','SPORTS NETS',1000,1,38,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (39,39,'Cricket Net Price','cricket-net-price','SPORTS NETS',1000,1,39,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (40,40,'Cricket Indoor Nets Near Me','cricket-indoor-nets-near-me','SPORTS NETS',1000,1,40,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (41,515,'Indoor Cricket Nets Near Me','indoor-cricket-nets-near-me','SPORTS NETS',1000,1,41,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (42,42,'Sports Nets','sports-nets','SPORTS NETS',1000,1,42,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (43,515,'Sports Netting','sports-netting','SPORTS NETS',1000,1,43,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (44,44,'Cricket Netting','cricket-netting','SPORTS NETS',1000,1,44,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (45,513,'Box Cricket Net','box-cricket-net','SPORTS NETS',1000,1,45,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (46,46,'Cricket Net Installation','cricket-net-installation','SPORTS NETS',1000,1,46,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (47,47,'Invisible Grills','invisible-grills','INVISIBLE GRILLS',1000,1,47,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (48,48,'Invisible Grill Near Me','invisible-grill-near-me','INVISIBLE GRILLS',1000,1,48,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (49,49,'SS Invisible Grills','ss-invisible-grills','INVISIBLE GRILLS',1000,1,49,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (50,50,'Invisible Grill For Balcony','invisible-grill-for-balcony','INVISIBLE GRILLS',1000,1,50,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (51,51,'Balcony Invisible Grill','balcony-invisible-grill','INVISIBLE GRILLS',1000,1,51,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (52,52,'Invisible Grill For Balcony Near Me','invisible-grill-for-balcony-near-me','INVISIBLE GRILLS',1000,1,52,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (53,53,'Invisible Safety Grill','invisible-safety-grill','INVISIBLE GRILLS',1000,1,53,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (54,54,'Invisible Grill For Safety','invisible-grill-for-safety','INVISIBLE GRILLS',1000,1,54,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (55,55,'Invisible Grill For Pigeons','invisible-grill-for-pigeons','INVISIBLE GRILLS',1000,1,55,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (56,509,'Ceiling Cloth Hangers','ceiling-cloth-hangers','CLOTH HANGERS',1000,1,56,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (57,509,'Dry Cloth Hangers','dry-cloth-hangers','CLOTH HANGERS',1000,1,57,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (58,509,'Cloth Drying Hangers','cloth-drying-hangers','CLOTH HANGERS',1000,1,58,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (59,59,'Cloth Hanger For Balcony','cloth-hanger-for-balcony','CLOTH HANGERS',1000,1,59,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (60,60,'Pulley Cloth Drying Hanger','pulley-cloth-drying-hanger','CLOTH HANGERS',1000,1,60,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (61,61,'Pulley Cloth Hanger','pulley-cloth-hanger','CLOTH HANGERS',1000,1,61,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (62,509,'Laundry Hanger Dryer','laundry-hanger-dryer','CLOTH HANGERS',1000,1,62,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (63,63,'Clothes Hanger To Dry Clothes','clothes-hanger-to-dry-clothes','CLOTH HANGERS',1000,1,63,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (64,509,'Clothes Hanger Drier','clothes-hanger-drier','CLOTH HANGERS',1000,1,64,'2026-04-05 17:00:44',0);
INSERT INTO `seo_service_keywords` VALUES (5401,14,'Anti Bird Netting','anti-bird-netting','BIRD NETS',540,1,22,'2026-04-09 13:03:29',41);
INSERT INTO `seo_service_keywords` VALUES (47962,66,'Balcony Safety Nets','balcony-safety-nets','SAFETY NETS',1100,1,24,'2026-04-09 15:07:26',52);
INSERT INTO `seo_service_keywords` VALUES (47973,515,'Cricket Nets Price','cricket-nets-price','SPORTS NETS',480,1,35,'2026-04-09 15:07:26',39);
INSERT INTO `seo_service_keywords` VALUES (57573,515,'Cricket Nets','cricket-nets','SPORTS NETS',1500,1,34,'2026-04-09 19:04:16',60);
INSERT INTO `seo_service_keywords` VALUES (927249,66,'Balcony Safety Nets Services','balcony-safety-nets-services','SAFETY NETS',1000,1,2,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927251,66,'Balcony Nets','balcony-nets','SAFETY NETS',1000,1,4,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927252,66,'Balcony Protection Nets','balcony-protection-nets','SAFETY NETS',1000,1,5,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927253,66,'Safety Nets For Balcony','safety-nets-for-balcony','SAFETY NETS',1000,1,6,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927254,66,'Safety Nets For Balcony Services','safety-nets-for-balcony-services','SAFETY NETS',1000,1,7,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927256,66,'Balcony Mesh','balcony-mesh','SAFETY NETS',1000,1,9,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927257,66,'Nylon Balcony Safety Nets','nylon-balcony-safety-nets','SAFETY NETS',1000,1,10,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927258,66,'Garware Balcony Safety Nets','garware-balcony-safety-nets','SAFETY NETS',1000,1,11,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927259,66,'High Rise Balcony Safety Nets','high-rise-balcony-safety-nets','SAFETY NETS',1000,1,12,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927260,66,'Apartment Balcony Safety Nets','apartment-balcony-safety-nets','SAFETY NETS',1000,1,13,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927261,66,'Villa Balcony Safety Nets','villa-balcony-safety-nets','SAFETY NETS',1000,1,14,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927262,66,'Balcony Grill Netting','balcony-grill-netting','SAFETY NETS',1000,1,15,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927263,66,'Transparent Balcony Safety Nets','transparent-balcony-safety-nets','SAFETY NETS',1000,1,16,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927264,66,'Balcony Fall Protection Nets','balcony-fall-protection-nets','SAFETY NETS',1000,1,17,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927266,32,'Children Safety Nets Services','children-safety-nets-services','SAFETY NETS',1000,1,19,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927267,32,'Child Safety Nets','child-safety-nets','SAFETY NETS',1000,1,20,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927268,32,'Child Safety Nets Services','child-safety-nets-services','SAFETY NETS',1000,1,21,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927269,32,'Kids Safety Nets','kids-safety-nets','SAFETY NETS',1000,1,22,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927270,32,'Baby Safety Nets','baby-safety-nets','SAFETY NETS',1000,1,23,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927271,32,'Child Safety Nets For Balcony','child-safety-nets-for-balcony','SAFETY NETS',1000,1,24,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927272,32,'Staircase Child Safety Nets','staircase-child-safety-nets','SAFETY NETS',1000,1,25,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927273,32,'Window Child Safety Nets','window-child-safety-nets','SAFETY NETS',1000,1,26,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927274,32,'Toddler Safety Nets','toddler-safety-nets','SAFETY NETS',1000,1,27,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927275,32,'High Tension Child Safety Nets','high-tension-child-safety-nets','SAFETY NETS',1000,1,28,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927276,32,'Children Balcony Fall Protection','children-balcony-fall-protection','SAFETY NETS',1000,1,29,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927278,33,'Pet Safety Nets Services','pet-safety-nets-services','SAFETY NETS',1000,1,31,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927279,33,'Cat Safety Nets','cat-safety-nets','SAFETY NETS',1000,1,32,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927280,33,'Cat Safety Nets Services','cat-safety-nets-services','SAFETY NETS',1000,1,33,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927281,33,'Cat Netting For Balcony','cat-netting-for-balcony','SAFETY NETS',1000,1,34,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927282,33,'Dog Safety Nets','dog-safety-nets','SAFETY NETS',1000,1,35,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927283,33,'Animal Safety Nets','animal-safety-nets','SAFETY NETS',1000,1,36,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927285,27,'Anti Monkey Nets','anti-monkey-nets','SAFETY NETS',1000,1,38,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927286,27,'Monkey Protection Nets','monkey-protection-nets','SAFETY NETS',1000,1,39,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927287,27,'Monkey Barrier Nets','monkey-barrier-nets','SAFETY NETS',1000,1,40,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927288,33,'Pet Fall Protection Nets','pet-fall-protection-nets','SAFETY NETS',1000,1,41,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927290,28,'Construction Safety Nets Services','construction-safety-nets-services','SAFETY NETS',1000,1,43,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927291,28,'Building Safety Nets','building-safety-nets','SAFETY NETS',1000,1,44,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927294,26,'Duct Area Safety Nets Services','duct-area-safety-nets-services','SAFETY NETS',1000,1,47,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927295,23,'Open Area Safety Nets','open-area-safety-nets','SAFETY NETS',1000,1,48,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927296,23,'Staircase Safety Nets','staircase-safety-nets','SAFETY NETS',1000,1,49,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927297,23,'Shaft Safety Nets','shaft-safety-nets','SAFETY NETS',1000,1,50,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927298,28,'Debris Netting','debris-netting','SAFETY NETS',1000,1,51,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927301,23,'Swimming Pool Safety Nets','swimming-pool-safety-nets','SAFETY NETS',1000,1,54,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927302,23,'Coconut Tree Safety Nets','coconut-tree-safety-nets','SAFETY NETS',1000,1,55,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927303,28,'Scaffolding Safety Nets','scaffolding-safety-nets','SAFETY NETS',1000,1,56,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927304,23,'Glass Balcony Safety Nets','glass-balcony-safety-nets','SAFETY NETS',1000,1,57,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927305,23,'Heavy Duty Safety Nets','heavy-duty-safety-nets','SAFETY NETS',1000,1,58,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927306,23,'Garware Safety Nets','garware-safety-nets','SAFETY NETS',1000,1,59,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927307,23,'Garware Safety Nets Services','garware-safety-nets-services','SAFETY NETS',1000,1,60,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927308,23,'Nylon Safety Nets','nylon-safety-nets','SAFETY NETS',1000,1,61,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927309,23,'Nylon Safety Nets Services','nylon-safety-nets-services','SAFETY NETS',1000,1,62,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927310,23,'HDPE Safety Nets','hdpe-safety-nets','SAFETY NETS',1000,1,63,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927311,23,'Safety Netting Solutions','safety-netting-solutions','SAFETY NETS',1000,1,64,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927312,66,'Best Balcony Safety Nets','best-balcony-safety-nets','SAFETY NETS',1000,1,65,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927313,32,'Best Child Safety Nets','best-child-safety-nets','SAFETY NETS',1000,1,66,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927314,28,'Best Construction Safety Nets','best-construction-safety-nets','SAFETY NETS',1000,1,67,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927315,66,'Top Balcony Safety Nets','top-balcony-safety-nets','SAFETY NETS',1000,1,68,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927316,32,'Top Child Safety Nets','top-child-safety-nets','SAFETY NETS',1000,1,69,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927317,28,'Top Construction Safety Nets','top-construction-safety-nets','SAFETY NETS',1000,1,70,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927318,32,'Garware Child Safety Nets','garware-child-safety-nets','SAFETY NETS',1000,1,71,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927319,28,'Garware Construction Safety Nets','garware-construction-safety-nets','SAFETY NETS',1000,1,72,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927320,66,'Same Day Balcony Safety Nets','same-day-balcony-safety-nets','SAFETY NETS',1000,1,73,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927321,32,'Same Day Child Safety Nets','same-day-child-safety-nets','SAFETY NETS',1000,1,74,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927322,28,'Same Day Construction Safety Nets','same-day-construction-safety-nets','SAFETY NETS',1000,1,75,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927323,66,'Affordable Balcony Safety Nets','affordable-balcony-safety-nets','SAFETY NETS',1000,1,76,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927324,32,'Affordable Child Safety Nets','affordable-child-safety-nets','SAFETY NETS',1000,1,77,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927325,28,'Affordable Construction Safety Nets','affordable-construction-safety-nets','SAFETY NETS',1000,1,78,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927326,66,'High Quality Balcony Safety Nets','high-quality-balcony-safety-nets','SAFETY NETS',1000,1,79,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927327,32,'High Quality Child Safety Nets','high-quality-child-safety-nets','SAFETY NETS',1000,1,80,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927328,28,'High Quality Construction Safety Nets','high-quality-construction-safety-nets','SAFETY NETS',1000,1,81,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927329,66,'Balcony Safety Nets Near Me','balcony-safety-nets-near-me','SAFETY NETS',1000,1,82,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927330,66,'Balcony Netting Near Me','balcony-netting-near-me','SAFETY NETS',1000,1,83,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927331,66,'Balcony Nets Near Me','balcony-nets-near-me','SAFETY NETS',1000,1,84,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927332,66,'Balcony Protection Nets Near Me','balcony-protection-nets-near-me','SAFETY NETS',1000,1,85,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927333,66,'Safety Nets For Balcony Near Me','safety-nets-for-balcony-near-me','SAFETY NETS',1000,1,86,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927334,66,'Safety Nets For Balconies Near Me','safety-nets-for-balconies-near-me','SAFETY NETS',1000,1,87,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927335,66,'Balcony Mesh Near Me','balcony-mesh-near-me','SAFETY NETS',1000,1,88,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927336,66,'Nylon Balcony Safety Nets Near Me','nylon-balcony-safety-nets-near-me','SAFETY NETS',1000,1,89,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927337,66,'Garware Balcony Safety Nets Near Me','garware-balcony-safety-nets-near-me','SAFETY NETS',1000,1,90,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927338,66,'High Rise Balcony Safety Nets Near Me','high-rise-balcony-safety-nets-near-me','SAFETY NETS',1000,1,91,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927339,66,'Apartment Balcony Safety Nets Near Me','apartment-balcony-safety-nets-near-me','SAFETY NETS',1000,1,92,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927340,66,'Villa Balcony Safety Nets Near Me','villa-balcony-safety-nets-near-me','SAFETY NETS',1000,1,93,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927341,66,'Balcony Grill Netting Near Me','balcony-grill-netting-near-me','SAFETY NETS',1000,1,94,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927342,66,'Transparent Balcony Safety Nets Near Me','transparent-balcony-safety-nets-near-me','SAFETY NETS',1000,1,95,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927343,66,'Balcony Fall Protection Nets Near Me','balcony-fall-protection-nets-near-me','SAFETY NETS',1000,1,96,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927344,66,'Balcony Net Fixing Near Me','balcony-net-fixing-near-me','SAFETY NETS',1000,1,97,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927345,32,'Children Safety Nets Near Me','children-safety-nets-near-me','SAFETY NETS',1000,1,98,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927346,32,'Child Safety Nets Near Me','child-safety-nets-near-me','SAFETY NETS',1000,1,99,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927347,32,'Kids Safety Nets Near Me','kids-safety-nets-near-me','SAFETY NETS',1000,1,100,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927348,32,'Baby Safety Nets Near Me','baby-safety-nets-near-me','SAFETY NETS',1000,1,101,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927349,32,'Child Safety Nets For Balcony Near Me','child-safety-nets-for-balcony-near-me','SAFETY NETS',1000,1,102,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927350,32,'Staircase Child Safety Nets Near Me','staircase-child-safety-nets-near-me','SAFETY NETS',1000,1,103,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927351,32,'Window Child Safety Nets Near Me','window-child-safety-nets-near-me','SAFETY NETS',1000,1,104,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927352,32,'Toddler Safety Nets Near Me','toddler-safety-nets-near-me','SAFETY NETS',1000,1,105,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927353,32,'High Tension Child Safety Nets Near Me','high-tension-child-safety-nets-near-me','SAFETY NETS',1000,1,106,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927354,32,'Children Balcony Fall Protection Near Me','children-balcony-fall-protection-near-me','SAFETY NETS',1000,1,107,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927355,33,'Pet Safety Nets Near Me','pet-safety-nets-near-me','SAFETY NETS',1000,1,108,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927356,33,'Cat Safety Nets Near Me','cat-safety-nets-near-me','SAFETY NETS',1000,1,109,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927357,33,'Cat Netting For Balcony Near Me','cat-netting-for-balcony-near-me','SAFETY NETS',1000,1,110,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927358,33,'Dog Safety Nets Near Me','dog-safety-nets-near-me','SAFETY NETS',1000,1,111,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927359,33,'Animal Safety Nets Near Me','animal-safety-nets-near-me','SAFETY NETS',1000,1,112,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927360,27,'Monkey Safety Nets Near Me','monkey-safety-nets-near-me','SAFETY NETS',1000,1,113,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927361,27,'Anti Monkey Nets Near Me','anti-monkey-nets-near-me','SAFETY NETS',1000,1,114,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927362,27,'Monkey Protection Nets Near Me','monkey-protection-nets-near-me','SAFETY NETS',1000,1,115,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927363,27,'Monkey Barrier Nets Near Me','monkey-barrier-nets-near-me','SAFETY NETS',1000,1,116,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927364,33,'Pet Fall Protection Nets Near Me','pet-fall-protection-nets-near-me','SAFETY NETS',1000,1,117,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927365,28,'Construction Safety Nets Near Me','construction-safety-nets-near-me','SAFETY NETS',1000,1,118,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927366,28,'Building Safety Nets Near Me','building-safety-nets-near-me','SAFETY NETS',1000,1,119,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927367,29,'Industrial Safety Nets Near Me','industrial-safety-nets-near-me','SAFETY NETS',1000,1,120,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927369,1,'Pigeon Nets Services','pigeon-nets-services','PIGEON NETS',1000,1,122,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927372,1,'Pigeon Safety Nets Services','pigeon-safety-nets-services','PIGEON NETS',1000,1,125,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927373,1,'Pigeon Netting','pigeon-netting','PIGEON NETS',1000,1,126,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927374,1,'Pigeon Nets For Balcony','pigeon-nets-for-balcony','PIGEON NETS',1000,1,127,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927375,1,'Pigeon Nets For Balcony Services','pigeon-nets-for-balcony-services','PIGEON NETS',1000,1,128,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927377,1,'Pigeon Control Nets','pigeon-control-nets','PIGEON NETS',1000,1,130,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927378,1,'Anti Pigeon Netting','anti-pigeon-netting','PIGEON NETS',1000,1,131,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927379,1,'Anti Pigeon Nets','anti-pigeon-nets','PIGEON NETS',1000,1,132,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927380,1,'Pigeon Protection Nets','pigeon-protection-nets','PIGEON NETS',1000,1,133,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927381,1,'AC Unit Pigeon Nets','ac-unit-pigeon-nets','PIGEON NETS',1000,1,134,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927382,1,'Duct Area Pigeon Nets','duct-area-pigeon-nets','PIGEON NETS',1000,1,135,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927383,1,'Window Pigeon Nets','window-pigeon-nets','PIGEON NETS',1000,1,136,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927384,1,'Terrace Pigeon Nets','terrace-pigeon-nets','PIGEON NETS',1000,1,137,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927385,1,'Garware Pigeon Nets','garware-pigeon-nets','PIGEON NETS',1000,1,138,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927386,1,'Nylon Pigeon Nets','nylon-pigeon-nets','PIGEON NETS',1000,1,139,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927387,1,'Translucent Pigeon Nets','translucent-pigeon-nets','PIGEON NETS',1000,1,140,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927388,1,'Balcony Pigeon Netting','balcony-pigeon-netting','PIGEON NETS',1000,1,141,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927389,1,'Pigeon Barrier Nets','pigeon-barrier-nets','PIGEON NETS',1000,1,142,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927390,1,'Pigeon Proofing Nets','pigeon-proofing-nets','PIGEON NETS',1000,1,143,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927391,1,'Best Pigeon Nets','best-pigeon-nets','PIGEON NETS',1000,1,144,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927392,1,'Top Pigeon Nets','top-pigeon-nets','PIGEON NETS',1000,1,145,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927393,1,'Same Day Pigeon Nets','same-day-pigeon-nets','PIGEON NETS',1000,1,146,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927394,1,'Affordable Pigeon Nets','affordable-pigeon-nets','PIGEON NETS',1000,1,147,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927395,1,'High Quality Pigeon Nets','high-quality-pigeon-nets','PIGEON NETS',1000,1,148,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927396,1,'Pigeon Nets Near Me','pigeon-nets-near-me','PIGEON NETS',1000,1,149,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927398,1,'Pigeon Safety Nets Near Me','pigeon-safety-nets-near-me','PIGEON NETS',1000,1,151,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927399,1,'Pigeon Netting Near Me','pigeon-netting-near-me','PIGEON NETS',1000,1,152,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927400,1,'Pigeon Nets For Balcony Near Me','pigeon-nets-for-balcony-near-me','PIGEON NETS',1000,1,153,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927402,1,'Pigeon Control Nets Near Me','pigeon-control-nets-near-me','PIGEON NETS',1000,1,155,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927403,1,'Anti Pigeon Netting Near Me','anti-pigeon-netting-near-me','PIGEON NETS',1000,1,156,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927404,1,'Anti Pigeon Nets Near Me','anti-pigeon-nets-near-me','PIGEON NETS',1000,1,157,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927405,1,'Pigeon Protection Nets Near Me','pigeon-protection-nets-near-me','PIGEON NETS',1000,1,158,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927406,1,'AC Unit Pigeon Nets Near Me','ac-unit-pigeon-nets-near-me','PIGEON NETS',1000,1,159,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927407,1,'Duct Area Pigeon Nets Near Me','duct-area-pigeon-nets-near-me','PIGEON NETS',1000,1,160,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927408,1,'Window Pigeon Nets Near Me','window-pigeon-nets-near-me','PIGEON NETS',1000,1,161,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927409,1,'Terrace Pigeon Nets Near Me','terrace-pigeon-nets-near-me','PIGEON NETS',1000,1,162,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927410,1,'Garware Pigeon Nets Near Me','garware-pigeon-nets-near-me','PIGEON NETS',1000,1,163,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927411,1,'Nylon Pigeon Nets Near Me','nylon-pigeon-nets-near-me','PIGEON NETS',1000,1,164,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927412,1,'Translucent Pigeon Nets Near Me','translucent-pigeon-nets-near-me','PIGEON NETS',1000,1,165,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927413,1,'Balcony Pigeon Netting Near Me','balcony-pigeon-netting-near-me','PIGEON NETS',1000,1,166,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927414,1,'Pigeon Barrier Nets Near Me','pigeon-barrier-nets-near-me','PIGEON NETS',1000,1,167,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927415,1,'Pigeon Proofing Nets Near Me','pigeon-proofing-nets-near-me','PIGEON NETS',1000,1,168,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927418,1,'Pigeon Safety Nets Installation','pigeon-safety-nets-installation','PIGEON NETS',1000,1,171,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927419,1,'Pigeon Netting Installation','pigeon-netting-installation','PIGEON NETS',1000,1,172,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927420,1,'Pigeon Nets For Balcony Installation','pigeon-nets-for-balcony-installation','PIGEON NETS',1000,1,173,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927421,1,'Pigeon Net For Balcony Installation','pigeon-net-for-balcony-installation','PIGEON NETS',1000,1,174,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927422,1,'Pigeon Control Nets Installation','pigeon-control-nets-installation','PIGEON NETS',1000,1,175,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927423,1,'Anti Pigeon Netting Installation','anti-pigeon-netting-installation','PIGEON NETS',1000,1,176,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927424,1,'Anti Pigeon Nets Installation','anti-pigeon-nets-installation','PIGEON NETS',1000,1,177,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927425,1,'Pigeon Protection Nets Installation','pigeon-protection-nets-installation','PIGEON NETS',1000,1,178,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927426,1,'AC Unit Pigeon Nets Installation','ac-unit-pigeon-nets-installation','PIGEON NETS',1000,1,179,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927427,1,'Duct Area Pigeon Nets Installation','duct-area-pigeon-nets-installation','PIGEON NETS',1000,1,180,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927428,1,'Window Pigeon Nets Installation','window-pigeon-nets-installation','PIGEON NETS',1000,1,181,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927429,1,'Terrace Pigeon Nets Installation','terrace-pigeon-nets-installation','PIGEON NETS',1000,1,182,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927430,1,'Garware Pigeon Nets Installation','garware-pigeon-nets-installation','PIGEON NETS',1000,1,183,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927431,1,'Nylon Pigeon Nets Installation','nylon-pigeon-nets-installation','PIGEON NETS',1000,1,184,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927432,1,'Translucent Pigeon Nets Installation','translucent-pigeon-nets-installation','PIGEON NETS',1000,1,185,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927433,1,'Balcony Pigeon Netting Installation','balcony-pigeon-netting-installation','PIGEON NETS',1000,1,186,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927434,1,'Pigeon Barrier Nets Installation','pigeon-barrier-nets-installation','PIGEON NETS',1000,1,187,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927435,1,'Pigeon Proofing Nets Installation','pigeon-proofing-nets-installation','PIGEON NETS',1000,1,188,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927436,1,'Pigeon Nets Price','pigeon-nets-price','PIGEON NETS',1000,1,189,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927438,1,'Pigeon Safety Nets Price','pigeon-safety-nets-price','PIGEON NETS',1000,1,191,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927439,1,'Pigeon Netting Price','pigeon-netting-price','PIGEON NETS',1000,1,192,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927440,1,'Pigeon Nets For Balcony Price','pigeon-nets-for-balcony-price','PIGEON NETS',1000,1,193,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927441,1,'Pigeon Net For Balcony Price','pigeon-net-for-balcony-price','PIGEON NETS',1000,1,194,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927442,1,'Pigeon Control Nets Price','pigeon-control-nets-price','PIGEON NETS',1000,1,195,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927443,1,'Anti Pigeon Netting Price','anti-pigeon-netting-price','PIGEON NETS',1000,1,196,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927444,1,'Anti Pigeon Nets Price','anti-pigeon-nets-price','PIGEON NETS',1000,1,197,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927445,1,'Pigeon Protection Nets Price','pigeon-protection-nets-price','PIGEON NETS',1000,1,198,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927446,1,'AC Unit Pigeon Nets Price','ac-unit-pigeon-nets-price','PIGEON NETS',1000,1,199,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927447,1,'Duct Area Pigeon Nets Price','duct-area-pigeon-nets-price','PIGEON NETS',1000,1,200,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927448,1,'Window Pigeon Nets Price','window-pigeon-nets-price','PIGEON NETS',1000,1,201,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927449,1,'Terrace Pigeon Nets Price','terrace-pigeon-nets-price','PIGEON NETS',1000,1,202,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927450,1,'Garware Pigeon Nets Price','garware-pigeon-nets-price','PIGEON NETS',1000,1,203,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927451,1,'Nylon Pigeon Nets Price','nylon-pigeon-nets-price','PIGEON NETS',1000,1,204,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927452,1,'Translucent Pigeon Nets Price','translucent-pigeon-nets-price','PIGEON NETS',1000,1,205,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927453,1,'Balcony Pigeon Netting Price','balcony-pigeon-netting-price','PIGEON NETS',1000,1,206,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927454,1,'Pigeon Barrier Nets Price','pigeon-barrier-nets-price','PIGEON NETS',1000,1,207,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927455,1,'Pigeon Proofing Nets Price','pigeon-proofing-nets-price','PIGEON NETS',1000,1,208,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927456,1,'Pigeon Nets Dealers','pigeon-nets-dealers','PIGEON NETS',1000,1,209,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927457,1,'Pigeon Net Dealers','pigeon-net-dealers','PIGEON NETS',1000,1,210,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927458,1,'Pigeon Safety Nets Dealers','pigeon-safety-nets-dealers','PIGEON NETS',1000,1,211,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927459,1,'Pigeon Netting Dealers','pigeon-netting-dealers','PIGEON NETS',1000,1,212,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927460,1,'Pigeon Nets For Balcony Dealers','pigeon-nets-for-balcony-dealers','PIGEON NETS',1000,1,213,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927461,1,'Pigeon Net For Balcony Dealers','pigeon-net-for-balcony-dealers','PIGEON NETS',1000,1,214,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927462,1,'Pigeon Control Nets Dealers','pigeon-control-nets-dealers','PIGEON NETS',1000,1,215,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927463,1,'Anti Pigeon Netting Dealers','anti-pigeon-netting-dealers','PIGEON NETS',1000,1,216,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927464,1,'Anti Pigeon Nets Dealers','anti-pigeon-nets-dealers','PIGEON NETS',1000,1,217,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927465,1,'Pigeon Protection Nets Dealers','pigeon-protection-nets-dealers','PIGEON NETS',1000,1,218,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927466,1,'AC Unit Pigeon Nets Dealers','ac-unit-pigeon-nets-dealers','PIGEON NETS',1000,1,219,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927467,1,'Duct Area Pigeon Nets Dealers','duct-area-pigeon-nets-dealers','PIGEON NETS',1000,1,220,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927468,1,'Window Pigeon Nets Dealers','window-pigeon-nets-dealers','PIGEON NETS',1000,1,221,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927469,1,'Terrace Pigeon Nets Dealers','terrace-pigeon-nets-dealers','PIGEON NETS',1000,1,222,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927470,1,'Garware Pigeon Nets Dealers','garware-pigeon-nets-dealers','PIGEON NETS',1000,1,223,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927471,1,'Nylon Pigeon Nets Dealers','nylon-pigeon-nets-dealers','PIGEON NETS',1000,1,224,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927472,1,'Translucent Pigeon Nets Dealers','translucent-pigeon-nets-dealers','PIGEON NETS',1000,1,225,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927473,1,'Balcony Pigeon Netting Dealers','balcony-pigeon-netting-dealers','PIGEON NETS',1000,1,226,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927474,1,'Pigeon Barrier Nets Dealers','pigeon-barrier-nets-dealers','PIGEON NETS',1000,1,227,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927475,1,'Pigeon Proofing Nets Dealers','pigeon-proofing-nets-dealers','PIGEON NETS',1000,1,228,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927476,1,'Pigeon Nets Fixing','pigeon-nets-fixing','PIGEON NETS',1000,1,229,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927477,1,'Pigeon Safety Nets Fixing','pigeon-safety-nets-fixing','PIGEON NETS',1000,1,230,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927478,1,'Pigeon Nets For Balcony Fixing','pigeon-nets-for-balcony-fixing','PIGEON NETS',1000,1,231,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927479,1,'Pigeon Nets Cost','pigeon-nets-cost','PIGEON NETS',1000,1,232,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927480,1,'Pigeon Safety Nets Cost','pigeon-safety-nets-cost','PIGEON NETS',1000,1,233,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927481,1,'Pigeon Nets For Balcony Cost','pigeon-nets-for-balcony-cost','PIGEON NETS',1000,1,234,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927482,1,'Pigeon Nets Contractors','pigeon-nets-contractors','PIGEON NETS',1000,1,235,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927483,1,'Pigeon Net Contractors','pigeon-net-contractors','PIGEON NETS',1000,1,236,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927484,1,'Pigeon Safety Nets Contractors','pigeon-safety-nets-contractors','PIGEON NETS',1000,1,237,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927485,1,'Pigeon Netting Contractors','pigeon-netting-contractors','PIGEON NETS',1000,1,238,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927486,1,'Pigeon Nets For Balcony Contractors','pigeon-nets-for-balcony-contractors','PIGEON NETS',1000,1,239,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927487,1,'Pigeon Net For Balcony Contractors','pigeon-net-for-balcony-contractors','PIGEON NETS',1000,1,240,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927489,10,'Anti Bird Netting Services','anti-bird-netting-services','BIRD NETS',1000,1,242,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927490,10,'Bird Safety Nets','bird-safety-nets','BIRD NETS',1000,1,243,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927493,10,'Bird Nets For Balcony','bird-nets-for-balcony','BIRD NETS',1000,1,246,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927494,10,'Bird Nets For Balcony Services','bird-nets-for-balcony-services','BIRD NETS',1000,1,247,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927496,10,'Window Bird Nets','window-bird-nets','BIRD NETS',1000,1,249,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927497,10,'Terrace Bird Nets','terrace-bird-nets','BIRD NETS',1000,1,250,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927498,10,'Commercial Bird Netting','commercial-bird-netting','BIRD NETS',1000,1,251,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927500,10,'Warehouse Bird Netting','warehouse-bird-netting','BIRD NETS',1000,1,253,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927501,10,'Factory Bird Netting','factory-bird-netting','BIRD NETS',1000,1,254,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927502,10,'Transparent Bird Netting','transparent-bird-netting','BIRD NETS',1000,1,255,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927503,10,'Sparrow Protection Nets','sparrow-protection-nets','BIRD NETS',1000,1,256,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927504,10,'Crow Protection Nets','crow-protection-nets','BIRD NETS',1000,1,257,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927505,10,'Bird Protection Nets','bird-protection-nets','BIRD NETS',1000,1,258,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927506,10,'Garware Bird Nets','garware-bird-nets','BIRD NETS',1000,1,259,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927507,10,'Nylon Bird Nets','nylon-bird-nets','BIRD NETS',1000,1,260,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927508,10,'Bird Proofing Solutions','bird-proofing-solutions','BIRD NETS',1000,1,261,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927509,512,'Anti Bird Spikes','anti-bird-spikes','BIRD NETS',1000,1,262,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927510,512,'Anti Bird Spikes Services','anti-bird-spikes-services','BIRD NETS',1000,1,263,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927511,512,'Bird Spikes','bird-spikes','BIRD NETS',1000,1,264,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927512,512,'Bird Spikes Services','bird-spikes-services','BIRD NETS',1000,1,265,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927513,512,'Pigeon Spikes','pigeon-spikes','BIRD NETS',1000,1,266,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927514,512,'Stainless Steel Bird Spikes','stainless-steel-bird-spikes','BIRD NETS',1000,1,267,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927515,512,'Polycarbonate Bird Spikes','polycarbonate-bird-spikes','BIRD NETS',1000,1,268,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927516,512,'Window Bird Spikes','window-bird-spikes','BIRD NETS',1000,1,269,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927517,512,'Balcony Bird Spikes','balcony-bird-spikes','BIRD NETS',1000,1,270,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927518,512,'AC Outdoor Unit Bird Spikes','ac-outdoor-unit-bird-spikes','BIRD NETS',1000,1,271,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927519,512,'Bird Deterrent Spikes','bird-deterrent-spikes','BIRD NETS',1000,1,272,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927520,512,'Commercial Bird Spikes','commercial-bird-spikes','BIRD NETS',1000,1,273,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927521,10,'Best Anti Bird Netting','best-anti-bird-netting','BIRD NETS',1000,1,274,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927522,10,'Top Anti Bird Netting','top-anti-bird-netting','BIRD NETS',1000,1,275,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927523,10,'Garware Anti Bird Netting','garware-anti-bird-netting','BIRD NETS',1000,1,276,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927524,10,'Same Day Anti Bird Netting','same-day-anti-bird-netting','BIRD NETS',1000,1,277,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927525,10,'Affordable Anti Bird Netting','affordable-anti-bird-netting','BIRD NETS',1000,1,278,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927526,10,'High Quality Anti Bird Netting','high-quality-anti-bird-netting','BIRD NETS',1000,1,279,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927527,10,'Anti Bird Netting Near Me','anti-bird-netting-near-me','BIRD NETS',1000,1,280,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927528,10,'Bird Safety Nets Near Me','bird-safety-nets-near-me','BIRD NETS',1000,1,281,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927529,10,'Bird Nets Near Me','bird-nets-near-me','BIRD NETS',1000,1,282,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927531,10,'Bird Netting Services Near Me','bird-netting-services-near-me','BIRD NETS',1000,1,284,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927532,10,'Bird Nets For Balcony Near Me','bird-nets-for-balcony-near-me','BIRD NETS',1000,1,285,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927533,10,'Bird Net For Balcony Near Me','bird-net-for-balcony-near-me','BIRD NETS',1000,1,286,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927534,10,'Window Bird Nets Near Me','window-bird-nets-near-me','BIRD NETS',1000,1,287,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927535,10,'Terrace Bird Nets Near Me','terrace-bird-nets-near-me','BIRD NETS',1000,1,288,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927536,10,'Commercial Bird Netting Near Me','commercial-bird-netting-near-me','BIRD NETS',1000,1,289,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927537,10,'Industrial Bird Netting Near Me','industrial-bird-netting-near-me','BIRD NETS',1000,1,290,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927538,10,'Warehouse Bird Netting Near Me','warehouse-bird-netting-near-me','BIRD NETS',1000,1,291,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927539,10,'Factory Bird Netting Near Me','factory-bird-netting-near-me','BIRD NETS',1000,1,292,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927540,10,'Transparent Bird Netting Near Me','transparent-bird-netting-near-me','BIRD NETS',1000,1,293,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927541,10,'Sparrow Protection Nets Near Me','sparrow-protection-nets-near-me','BIRD NETS',1000,1,294,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927542,10,'Crow Protection Nets Near Me','crow-protection-nets-near-me','BIRD NETS',1000,1,295,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927543,10,'Bird Protection Nets Near Me','bird-protection-nets-near-me','BIRD NETS',1000,1,296,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927544,10,'Garware Bird Nets Near Me','garware-bird-nets-near-me','BIRD NETS',1000,1,297,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927545,10,'Nylon Bird Nets Near Me','nylon-bird-nets-near-me','BIRD NETS',1000,1,298,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927546,10,'Bird Proofing Solutions Near Me','bird-proofing-solutions-near-me','BIRD NETS',1000,1,299,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927547,512,'Anti Bird Spikes Near Me','anti-bird-spikes-near-me','BIRD NETS',1000,1,300,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927548,512,'Bird Spikes Near Me','bird-spikes-near-me','BIRD NETS',1000,1,301,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927549,512,'Pigeon Spikes Near Me','pigeon-spikes-near-me','BIRD NETS',1000,1,302,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927550,512,'Stainless Steel Bird Spikes Near Me','stainless-steel-bird-spikes-near-me','BIRD NETS',1000,1,303,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927551,512,'Polycarbonate Bird Spikes Near Me','polycarbonate-bird-spikes-near-me','BIRD NETS',1000,1,304,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927552,512,'Window Bird Spikes Near Me','window-bird-spikes-near-me','BIRD NETS',1000,1,305,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927553,512,'Balcony Bird Spikes Near Me','balcony-bird-spikes-near-me','BIRD NETS',1000,1,306,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927554,512,'AC Outdoor Unit Bird Spikes Near Me','ac-outdoor-unit-bird-spikes-near-me','BIRD NETS',1000,1,307,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927555,512,'Bird Deterrent Spikes Near Me','bird-deterrent-spikes-near-me','BIRD NETS',1000,1,308,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927556,512,'Commercial Bird Spikes Near Me','commercial-bird-spikes-near-me','BIRD NETS',1000,1,309,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927557,10,'Anti Bird Netting Installation','anti-bird-netting-installation','BIRD NETS',1000,1,310,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927558,10,'Bird Safety Nets Installation','bird-safety-nets-installation','BIRD NETS',1000,1,311,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927559,10,'Bird Nets Installation','bird-nets-installation','BIRD NETS',1000,1,312,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927560,10,'Bird Net Installation','bird-net-installation','BIRD NETS',1000,1,313,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927561,10,'Bird Netting Services Installation','bird-netting-services-installation','BIRD NETS',1000,1,314,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927562,10,'Bird Nets For Balcony Installation','bird-nets-for-balcony-installation','BIRD NETS',1000,1,315,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927563,10,'Bird Net For Balcony Installation','bird-net-for-balcony-installation','BIRD NETS',1000,1,316,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927564,10,'Window Bird Nets Installation','window-bird-nets-installation','BIRD NETS',1000,1,317,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927565,10,'Terrace Bird Nets Installation','terrace-bird-nets-installation','BIRD NETS',1000,1,318,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927566,10,'Commercial Bird Netting Installation','commercial-bird-netting-installation','BIRD NETS',1000,1,319,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927567,10,'Industrial Bird Netting Installation','industrial-bird-netting-installation','BIRD NETS',1000,1,320,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927568,10,'Warehouse Bird Netting Installation','warehouse-bird-netting-installation','BIRD NETS',1000,1,321,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927569,10,'Factory Bird Netting Installation','factory-bird-netting-installation','BIRD NETS',1000,1,322,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927570,10,'Transparent Bird Netting Installation','transparent-bird-netting-installation','BIRD NETS',1000,1,323,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927571,10,'Sparrow Protection Nets Installation','sparrow-protection-nets-installation','BIRD NETS',1000,1,324,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927572,10,'Crow Protection Nets Installation','crow-protection-nets-installation','BIRD NETS',1000,1,325,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927573,10,'Bird Protection Nets Installation','bird-protection-nets-installation','BIRD NETS',1000,1,326,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927574,10,'Garware Bird Nets Installation','garware-bird-nets-installation','BIRD NETS',1000,1,327,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927575,10,'Nylon Bird Nets Installation','nylon-bird-nets-installation','BIRD NETS',1000,1,328,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927576,10,'Bird Proofing Solutions Installation','bird-proofing-solutions-installation','BIRD NETS',1000,1,329,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927577,512,'Anti Bird Spikes Installation','anti-bird-spikes-installation','BIRD NETS',1000,1,330,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927578,512,'Bird Spikes Installation','bird-spikes-installation','BIRD NETS',1000,1,331,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927579,512,'Pigeon Spikes Installation','pigeon-spikes-installation','BIRD NETS',1000,1,332,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927580,512,'Stainless Steel Bird Spikes Installation','stainless-steel-bird-spikes-installation','BIRD NETS',1000,1,333,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927581,512,'Polycarbonate Bird Spikes Installation','polycarbonate-bird-spikes-installation','BIRD NETS',1000,1,334,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927582,512,'Window Bird Spikes Installation','window-bird-spikes-installation','BIRD NETS',1000,1,335,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927583,512,'Balcony Bird Spikes Installation','balcony-bird-spikes-installation','BIRD NETS',1000,1,336,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927584,512,'AC Outdoor Unit Bird Spikes Installation','ac-outdoor-unit-bird-spikes-installation','BIRD NETS',1000,1,337,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927585,512,'Bird Deterrent Spikes Installation','bird-deterrent-spikes-installation','BIRD NETS',1000,1,338,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927586,512,'Commercial Bird Spikes Installation','commercial-bird-spikes-installation','BIRD NETS',1000,1,339,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927587,10,'Anti Bird Netting Price','anti-bird-netting-price','BIRD NETS',1000,1,340,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927589,47,'Invisible Grills Services','invisible-grills-services','INVISIBLE GRILLS',1000,1,342,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927590,47,'Balcony Invisible Grills','balcony-invisible-grills','INVISIBLE GRILLS',1000,1,343,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927591,47,'Balcony Invisible Grills Services','balcony-invisible-grills-services','INVISIBLE GRILLS',1000,1,344,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927592,47,'Window Invisible Grills','window-invisible-grills','INVISIBLE GRILLS',1000,1,345,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927593,47,'Invisible Grills For Balcony','invisible-grills-for-balcony','INVISIBLE GRILLS',1000,1,346,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927594,47,'Invisible Grills For Windows','invisible-grills-for-windows','INVISIBLE GRILLS',1000,1,347,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927595,47,'SS 316 Invisible Grills','ss-316-invisible-grills','INVISIBLE GRILLS',1000,1,348,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927596,47,'SS 316 Invisible Grills Services','ss-316-invisible-grills-services','INVISIBLE GRILLS',1000,1,349,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927597,47,'Stainless Steel Invisible Grills','stainless-steel-invisible-grills','INVISIBLE GRILLS',1000,1,350,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927598,47,'Modern Invisible Grills','modern-invisible-grills','INVISIBLE GRILLS',1000,1,351,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927599,47,'Invisible Safety Grills','invisible-safety-grills','INVISIBLE GRILLS',1000,1,352,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927600,47,'Transparent Invisible Grills','transparent-invisible-grills','INVISIBLE GRILLS',1000,1,353,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927601,47,'High Rise Invisible Grills','high-rise-invisible-grills','INVISIBLE GRILLS',1000,1,354,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927602,47,'Apartment Invisible Grills','apartment-invisible-grills','INVISIBLE GRILLS',1000,1,355,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927603,47,'Villa Invisible Grills','villa-invisible-grills','INVISIBLE GRILLS',1000,1,356,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927604,47,'Invisible Balcony Safety Grills','invisible-balcony-safety-grills','INVISIBLE GRILLS',1000,1,357,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927605,47,'Invisible Safety Grill For Windows','invisible-safety-grill-for-windows','INVISIBLE GRILLS',1000,1,358,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927606,47,'Best Invisible Grills','best-invisible-grills','INVISIBLE GRILLS',1000,1,359,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927607,47,'Top Invisible Grills','top-invisible-grills','INVISIBLE GRILLS',1000,1,360,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927608,47,'Garware Invisible Grills','garware-invisible-grills','INVISIBLE GRILLS',1000,1,361,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927609,47,'Same Day Invisible Grills','same-day-invisible-grills','INVISIBLE GRILLS',1000,1,362,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927610,47,'Affordable Invisible Grills','affordable-invisible-grills','INVISIBLE GRILLS',1000,1,363,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927611,47,'High Quality Invisible Grills','high-quality-invisible-grills','INVISIBLE GRILLS',1000,1,364,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927612,47,'Invisible Grills Near Me','invisible-grills-near-me','INVISIBLE GRILLS',1000,1,365,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927613,47,'Balcony Invisible Grills Near Me','balcony-invisible-grills-near-me','INVISIBLE GRILLS',1000,1,366,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927614,47,'Window Invisible Grills Near Me','window-invisible-grills-near-me','INVISIBLE GRILLS',1000,1,367,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927615,47,'Invisible Grills For Balcony Near Me','invisible-grills-for-balcony-near-me','INVISIBLE GRILLS',1000,1,368,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927616,47,'Invisible Grills For Windows Near Me','invisible-grills-for-windows-near-me','INVISIBLE GRILLS',1000,1,369,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927617,47,'SS 316 Invisible Grills Near Me','ss-316-invisible-grills-near-me','INVISIBLE GRILLS',1000,1,370,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927618,47,'Stainless Steel Invisible Grills Near Me','stainless-steel-invisible-grills-near-me','INVISIBLE GRILLS',1000,1,371,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927619,47,'Modern Invisible Grills Near Me','modern-invisible-grills-near-me','INVISIBLE GRILLS',1000,1,372,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927620,47,'Invisible Safety Grills Near Me','invisible-safety-grills-near-me','INVISIBLE GRILLS',1000,1,373,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927621,47,'Transparent Invisible Grills Near Me','transparent-invisible-grills-near-me','INVISIBLE GRILLS',1000,1,374,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927622,47,'High Rise Invisible Grills Near Me','high-rise-invisible-grills-near-me','INVISIBLE GRILLS',1000,1,375,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927623,47,'Apartment Invisible Grills Near Me','apartment-invisible-grills-near-me','INVISIBLE GRILLS',1000,1,376,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927624,47,'Villa Invisible Grills Near Me','villa-invisible-grills-near-me','INVISIBLE GRILLS',1000,1,377,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927625,47,'Invisible Balcony Safety Grills Near Me','invisible-balcony-safety-grills-near-me','INVISIBLE GRILLS',1000,1,378,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927626,47,'Invisible Safety Grill For Windows Near Me','invisible-safety-grill-for-windows-near-me','INVISIBLE GRILLS',1000,1,379,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927627,47,'Invisible Grills Installation','invisible-grills-installation','INVISIBLE GRILLS',1000,1,380,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927628,47,'Balcony Invisible Grills Installation','balcony-invisible-grills-installation','INVISIBLE GRILLS',1000,1,381,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927629,47,'Window Invisible Grills Installation','window-invisible-grills-installation','INVISIBLE GRILLS',1000,1,382,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927630,47,'Invisible Grills For Balcony Installation','invisible-grills-for-balcony-installation','INVISIBLE GRILLS',1000,1,383,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927631,47,'Invisible Grills For Windows Installation','invisible-grills-for-windows-installation','INVISIBLE GRILLS',1000,1,384,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927632,47,'SS 316 Invisible Grills Installation','ss-316-invisible-grills-installation','INVISIBLE GRILLS',1000,1,385,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927633,47,'Stainless Steel Invisible Grills Installation','stainless-steel-invisible-grills-installation','INVISIBLE GRILLS',1000,1,386,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927634,47,'Modern Invisible Grills Installation','modern-invisible-grills-installation','INVISIBLE GRILLS',1000,1,387,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927635,47,'Invisible Safety Grills Installation','invisible-safety-grills-installation','INVISIBLE GRILLS',1000,1,388,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927636,47,'Transparent Invisible Grills Installation','transparent-invisible-grills-installation','INVISIBLE GRILLS',1000,1,389,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927637,47,'High Rise Invisible Grills Installation','high-rise-invisible-grills-installation','INVISIBLE GRILLS',1000,1,390,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927638,47,'Apartment Invisible Grills Installation','apartment-invisible-grills-installation','INVISIBLE GRILLS',1000,1,391,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927639,47,'Villa Invisible Grills Installation','villa-invisible-grills-installation','INVISIBLE GRILLS',1000,1,392,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927640,47,'Invisible Balcony Safety Grills Installation','invisible-balcony-safety-grills-installation','INVISIBLE GRILLS',1000,1,393,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927641,47,'Invisible Safety Grill For Windows Installation','invisible-safety-grill-for-windows-installation','INVISIBLE GRILLS',1000,1,394,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927642,47,'Invisible Grills Price','invisible-grills-price','INVISIBLE GRILLS',1000,1,395,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927643,47,'Balcony Invisible Grills Price','balcony-invisible-grills-price','INVISIBLE GRILLS',1000,1,396,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927644,47,'Window Invisible Grills Price','window-invisible-grills-price','INVISIBLE GRILLS',1000,1,397,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927645,47,'Invisible Grills For Balcony Price','invisible-grills-for-balcony-price','INVISIBLE GRILLS',1000,1,398,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927646,47,'Invisible Grills For Windows Price','invisible-grills-for-windows-price','INVISIBLE GRILLS',1000,1,399,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927647,47,'SS 316 Invisible Grills Price','ss-316-invisible-grills-price','INVISIBLE GRILLS',1000,1,400,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927648,47,'Stainless Steel Invisible Grills Price','stainless-steel-invisible-grills-price','INVISIBLE GRILLS',1000,1,401,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927649,47,'Modern Invisible Grills Price','modern-invisible-grills-price','INVISIBLE GRILLS',1000,1,402,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927650,47,'Invisible Safety Grills Price','invisible-safety-grills-price','INVISIBLE GRILLS',1000,1,403,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927651,47,'Transparent Invisible Grills Price','transparent-invisible-grills-price','INVISIBLE GRILLS',1000,1,404,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927652,47,'High Rise Invisible Grills Price','high-rise-invisible-grills-price','INVISIBLE GRILLS',1000,1,405,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927653,47,'Apartment Invisible Grills Price','apartment-invisible-grills-price','INVISIBLE GRILLS',1000,1,406,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927654,47,'Villa Invisible Grills Price','villa-invisible-grills-price','INVISIBLE GRILLS',1000,1,407,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927655,47,'Invisible Balcony Safety Grills Price','invisible-balcony-safety-grills-price','INVISIBLE GRILLS',1000,1,408,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927656,47,'Invisible Safety Grill For Windows Price','invisible-safety-grill-for-windows-price','INVISIBLE GRILLS',1000,1,409,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927657,47,'Invisible Grills Dealers','invisible-grills-dealers','INVISIBLE GRILLS',1000,1,410,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927658,47,'Balcony Invisible Grills Dealers','balcony-invisible-grills-dealers','INVISIBLE GRILLS',1000,1,411,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927659,47,'Window Invisible Grills Dealers','window-invisible-grills-dealers','INVISIBLE GRILLS',1000,1,412,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927660,47,'Invisible Grills For Balcony Dealers','invisible-grills-for-balcony-dealers','INVISIBLE GRILLS',1000,1,413,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927661,47,'Invisible Grills For Windows Dealers','invisible-grills-for-windows-dealers','INVISIBLE GRILLS',1000,1,414,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927662,47,'SS 316 Invisible Grills Dealers','ss-316-invisible-grills-dealers','INVISIBLE GRILLS',1000,1,415,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927663,47,'Stainless Steel Invisible Grills Dealers','stainless-steel-invisible-grills-dealers','INVISIBLE GRILLS',1000,1,416,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927664,47,'Modern Invisible Grills Dealers','modern-invisible-grills-dealers','INVISIBLE GRILLS',1000,1,417,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927665,47,'Invisible Safety Grills Dealers','invisible-safety-grills-dealers','INVISIBLE GRILLS',1000,1,418,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927666,47,'Transparent Invisible Grills Dealers','transparent-invisible-grills-dealers','INVISIBLE GRILLS',1000,1,419,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927667,47,'High Rise Invisible Grills Dealers','high-rise-invisible-grills-dealers','INVISIBLE GRILLS',1000,1,420,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927668,47,'Apartment Invisible Grills Dealers','apartment-invisible-grills-dealers','INVISIBLE GRILLS',1000,1,421,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927669,47,'Villa Invisible Grills Dealers','villa-invisible-grills-dealers','INVISIBLE GRILLS',1000,1,422,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927670,47,'Invisible Balcony Safety Grills Dealers','invisible-balcony-safety-grills-dealers','INVISIBLE GRILLS',1000,1,423,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927671,47,'Invisible Safety Grill For Windows Dealers','invisible-safety-grill-for-windows-dealers','INVISIBLE GRILLS',1000,1,424,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927672,47,'Invisible Grills Fixing','invisible-grills-fixing','INVISIBLE GRILLS',1000,1,425,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927673,47,'Balcony Invisible Grills Fixing','balcony-invisible-grills-fixing','INVISIBLE GRILLS',1000,1,426,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927674,47,'SS 316 Invisible Grills Fixing','ss-316-invisible-grills-fixing','INVISIBLE GRILLS',1000,1,427,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927675,47,'Invisible Grills Cost','invisible-grills-cost','INVISIBLE GRILLS',1000,1,428,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927676,47,'Balcony Invisible Grills Cost','balcony-invisible-grills-cost','INVISIBLE GRILLS',1000,1,429,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927677,47,'SS 316 Invisible Grills Cost','ss-316-invisible-grills-cost','INVISIBLE GRILLS',1000,1,430,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927679,515,'Cricket Nets Services','cricket-nets-services','SPORTS NETS',1000,1,432,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927681,515,'Cricket Practice Nets Services','cricket-practice-nets-services','SPORTS NETS',1000,1,434,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927683,513,'Box Cricket Nets','box-cricket-nets','SPORTS NETS',1000,1,436,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927684,513,'Box Cricket Nets Services','box-cricket-nets-services','SPORTS NETS',1000,1,437,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927686,513,'Box Cricket Netting','box-cricket-netting','SPORTS NETS',1000,1,439,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927687,513,'Box Cricket Arena Nets','box-cricket-arena-nets','SPORTS NETS',1000,1,440,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927688,513,'Box Cricket Turf Nets','box-cricket-turf-nets','SPORTS NETS',1000,1,441,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927689,515,'Terrace Cricket Nets','terrace-cricket-nets','SPORTS NETS',1000,1,442,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927690,515,'Rooftop Cricket Nets','rooftop-cricket-nets','SPORTS NETS',1000,1,443,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927691,515,'Outdoor Cricket Nets','outdoor-cricket-nets','SPORTS NETS',1000,1,444,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927692,515,'Indoor Cricket Nets','indoor-cricket-nets','SPORTS NETS',1000,1,445,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927693,515,'Football Boundary Nets','football-boundary-nets','SPORTS NETS',1000,1,446,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927694,515,'Badminton Court Nets','badminton-court-nets','SPORTS NETS',1000,1,447,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927695,515,'Volleyball Court Nets','volleyball-court-nets','SPORTS NETS',1000,1,448,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927696,515,'Sports Safety Nets','sports-safety-nets','SPORTS NETS',1000,1,449,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927697,515,'Golf Practice Nets','golf-practice-nets','SPORTS NETS',1000,1,450,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927699,515,'Best Cricket Nets','best-cricket-nets','SPORTS NETS',1000,1,452,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927700,515,'Top Cricket Nets','top-cricket-nets','SPORTS NETS',1000,1,453,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927701,515,'Garware Cricket Nets','garware-cricket-nets','SPORTS NETS',1000,1,454,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927702,515,'Same Day Cricket Nets','same-day-cricket-nets','SPORTS NETS',1000,1,455,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927703,515,'Affordable Cricket Nets','affordable-cricket-nets','SPORTS NETS',1000,1,456,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927704,515,'High Quality Cricket Nets','high-quality-cricket-nets','SPORTS NETS',1000,1,457,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927706,515,'Cricket Practice Nets Near Me','cricket-practice-nets-near-me','SPORTS NETS',1000,1,459,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927707,515,'Cricket Practice Net Near Me','cricket-practice-net-near-me','SPORTS NETS',1000,1,460,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927708,513,'Box Cricket Nets Near Me','box-cricket-nets-near-me','SPORTS NETS',1000,1,461,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927709,513,'Box Cricket Net Near Me','box-cricket-net-near-me','SPORTS NETS',1000,1,462,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927710,513,'Box Cricket Netting Near Me','box-cricket-netting-near-me','SPORTS NETS',1000,1,463,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927711,513,'Box Cricket Arena Nets Near Me','box-cricket-arena-nets-near-me','SPORTS NETS',1000,1,464,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927712,513,'Box Cricket Turf Nets Near Me','box-cricket-turf-nets-near-me','SPORTS NETS',1000,1,465,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927713,515,'Terrace Cricket Nets Near Me','terrace-cricket-nets-near-me','SPORTS NETS',1000,1,466,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927714,515,'Rooftop Cricket Nets Near Me','rooftop-cricket-nets-near-me','SPORTS NETS',1000,1,467,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927715,515,'Outdoor Cricket Nets Near Me','outdoor-cricket-nets-near-me','SPORTS NETS',1000,1,468,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927717,515,'Football Boundary Nets Near Me','football-boundary-nets-near-me','SPORTS NETS',1000,1,470,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927718,515,'Badminton Court Nets Near Me','badminton-court-nets-near-me','SPORTS NETS',1000,1,471,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927719,515,'Volleyball Court Nets Near Me','volleyball-court-nets-near-me','SPORTS NETS',1000,1,472,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927720,515,'Sports Safety Nets Near Me','sports-safety-nets-near-me','SPORTS NETS',1000,1,473,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927721,515,'Golf Practice Nets Near Me','golf-practice-nets-near-me','SPORTS NETS',1000,1,474,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927722,515,'Sports Netting Near Me','sports-netting-near-me','SPORTS NETS',1000,1,475,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927723,515,'Cricket Nets Installation','cricket-nets-installation','SPORTS NETS',1000,1,476,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927724,515,'Cricket Practice Nets Installation','cricket-practice-nets-installation','SPORTS NETS',1000,1,477,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927725,515,'Cricket Practice Net Installation','cricket-practice-net-installation','SPORTS NETS',1000,1,478,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927726,513,'Box Cricket Nets Installation','box-cricket-nets-installation','SPORTS NETS',1000,1,479,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927727,513,'Box Cricket Net Installation','box-cricket-net-installation','SPORTS NETS',1000,1,480,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927728,513,'Box Cricket Netting Installation','box-cricket-netting-installation','SPORTS NETS',1000,1,481,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927729,513,'Box Cricket Arena Nets Installation','box-cricket-arena-nets-installation','SPORTS NETS',1000,1,482,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927730,513,'Box Cricket Turf Nets Installation','box-cricket-turf-nets-installation','SPORTS NETS',1000,1,483,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927731,515,'Terrace Cricket Nets Installation','terrace-cricket-nets-installation','SPORTS NETS',1000,1,484,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927732,515,'Rooftop Cricket Nets Installation','rooftop-cricket-nets-installation','SPORTS NETS',1000,1,485,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927733,515,'Outdoor Cricket Nets Installation','outdoor-cricket-nets-installation','SPORTS NETS',1000,1,486,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927734,515,'Indoor Cricket Nets Installation','indoor-cricket-nets-installation','SPORTS NETS',1000,1,487,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927735,515,'Football Boundary Nets Installation','football-boundary-nets-installation','SPORTS NETS',1000,1,488,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927736,515,'Badminton Court Nets Installation','badminton-court-nets-installation','SPORTS NETS',1000,1,489,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927737,515,'Volleyball Court Nets Installation','volleyball-court-nets-installation','SPORTS NETS',1000,1,490,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927738,515,'Sports Safety Nets Installation','sports-safety-nets-installation','SPORTS NETS',1000,1,491,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927739,515,'Golf Practice Nets Installation','golf-practice-nets-installation','SPORTS NETS',1000,1,492,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927740,515,'Sports Netting Installation','sports-netting-installation','SPORTS NETS',1000,1,493,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927742,515,'Cricket Practice Nets Price','cricket-practice-nets-price','SPORTS NETS',1000,1,495,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927743,515,'Cricket Practice Net Price','cricket-practice-net-price','SPORTS NETS',1000,1,496,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927744,513,'Box Cricket Nets Price','box-cricket-nets-price','SPORTS NETS',1000,1,497,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927745,513,'Box Cricket Net Price','box-cricket-net-price','SPORTS NETS',1000,1,498,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927746,513,'Box Cricket Netting Price','box-cricket-netting-price','SPORTS NETS',1000,1,499,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927747,513,'Box Cricket Arena Nets Price','box-cricket-arena-nets-price','SPORTS NETS',1000,1,500,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927748,513,'Box Cricket Turf Nets Price','box-cricket-turf-nets-price','SPORTS NETS',1000,1,501,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927749,515,'Terrace Cricket Nets Price','terrace-cricket-nets-price','SPORTS NETS',1000,1,502,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927750,515,'Rooftop Cricket Nets Price','rooftop-cricket-nets-price','SPORTS NETS',1000,1,503,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927751,515,'Outdoor Cricket Nets Price','outdoor-cricket-nets-price','SPORTS NETS',1000,1,504,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927752,515,'Indoor Cricket Nets Price','indoor-cricket-nets-price','SPORTS NETS',1000,1,505,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927753,515,'Football Boundary Nets Price','football-boundary-nets-price','SPORTS NETS',1000,1,506,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927754,515,'Badminton Court Nets Price','badminton-court-nets-price','SPORTS NETS',1000,1,507,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927755,515,'Volleyball Court Nets Price','volleyball-court-nets-price','SPORTS NETS',1000,1,508,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927756,515,'Sports Safety Nets Price','sports-safety-nets-price','SPORTS NETS',1000,1,509,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927757,515,'Golf Practice Nets Price','golf-practice-nets-price','SPORTS NETS',1000,1,510,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927758,515,'Sports Netting Price','sports-netting-price','SPORTS NETS',1000,1,511,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927759,515,'Cricket Nets Dealers','cricket-nets-dealers','SPORTS NETS',1000,1,512,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927760,515,'Cricket Practice Nets Dealers','cricket-practice-nets-dealers','SPORTS NETS',1000,1,513,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927761,515,'Cricket Practice Net Dealers','cricket-practice-net-dealers','SPORTS NETS',1000,1,514,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927762,513,'Box Cricket Nets Dealers','box-cricket-nets-dealers','SPORTS NETS',1000,1,515,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927763,513,'Box Cricket Net Dealers','box-cricket-net-dealers','SPORTS NETS',1000,1,516,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927764,513,'Box Cricket Netting Dealers','box-cricket-netting-dealers','SPORTS NETS',1000,1,517,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927765,513,'Box Cricket Arena Nets Dealers','box-cricket-arena-nets-dealers','SPORTS NETS',1000,1,518,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927766,513,'Box Cricket Turf Nets Dealers','box-cricket-turf-nets-dealers','SPORTS NETS',1000,1,519,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927767,515,'Terrace Cricket Nets Dealers','terrace-cricket-nets-dealers','SPORTS NETS',1000,1,520,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927769,509,'Cloth Drying Hangers Services','cloth-drying-hangers-services','CLOTH HANGERS',1000,1,522,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927771,509,'Ceiling Cloth Hangers Services','ceiling-cloth-hangers-services','CLOTH HANGERS',1000,1,524,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927772,509,'Ceiling Cloth Drying Hangers','ceiling-cloth-drying-hangers','CLOTH HANGERS',1000,1,525,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927773,509,'Ceiling Clothes Hangers','ceiling-clothes-hangers','CLOTH HANGERS',1000,1,526,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927774,509,'Balcony Cloth Hangers','balcony-cloth-hangers','CLOTH HANGERS',1000,1,527,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927775,509,'Pulley Cloth Drying Hangers','pulley-cloth-drying-hangers','CLOTH HANGERS',1000,1,528,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927776,509,'Pulley Cloth Hangers','pulley-cloth-hangers','CLOTH HANGERS',1000,1,529,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927777,509,'Stainless Steel Cloth Hangers','stainless-steel-cloth-hangers','CLOTH HANGERS',1000,1,530,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927778,509,'6 Pipe Cloth Drying Hangers','6-pipe-cloth-drying-hangers','CLOTH HANGERS',1000,1,531,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927779,509,'8 Pipe Cloth Drying Hangers','8-pipe-cloth-drying-hangers','CLOTH HANGERS',1000,1,532,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927780,509,'Ceiling Hangers For Balcony','ceiling-hangers-for-balcony','CLOTH HANGERS',1000,1,533,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927781,509,'Clothes Drying Rope Pulley','clothes-drying-rope-pulley','CLOTH HANGERS',1000,1,534,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927785,509,'Best Cloth Drying Hangers','best-cloth-drying-hangers','CLOTH HANGERS',1000,1,538,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927786,509,'Top Cloth Drying Hangers','top-cloth-drying-hangers','CLOTH HANGERS',1000,1,539,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927787,509,'Garware Cloth Drying Hangers','garware-cloth-drying-hangers','CLOTH HANGERS',1000,1,540,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927788,509,'Same Day Cloth Drying Hangers','same-day-cloth-drying-hangers','CLOTH HANGERS',1000,1,541,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927789,509,'Affordable Cloth Drying Hangers','affordable-cloth-drying-hangers','CLOTH HANGERS',1000,1,542,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927790,509,'High Quality Cloth Drying Hangers','high-quality-cloth-drying-hangers','CLOTH HANGERS',1000,1,543,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927791,509,'Cloth Drying Hangers Near Me','cloth-drying-hangers-near-me','CLOTH HANGERS',1000,1,544,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927792,509,'Ceiling Cloth Hangers Near Me','ceiling-cloth-hangers-near-me','CLOTH HANGERS',1000,1,545,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927793,509,'Ceiling Cloth Drying Hangers Near Me','ceiling-cloth-drying-hangers-near-me','CLOTH HANGERS',1000,1,546,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927794,509,'Ceiling Clothes Hangers Near Me','ceiling-clothes-hangers-near-me','CLOTH HANGERS',1000,1,547,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927795,509,'Balcony Cloth Hangers Near Me','balcony-cloth-hangers-near-me','CLOTH HANGERS',1000,1,548,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927796,509,'Pulley Cloth Drying Hangers Near Me','pulley-cloth-drying-hangers-near-me','CLOTH HANGERS',1000,1,549,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927797,509,'Pulley Cloth Hangers Near Me','pulley-cloth-hangers-near-me','CLOTH HANGERS',1000,1,550,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927798,509,'Stainless Steel Cloth Hangers Near Me','stainless-steel-cloth-hangers-near-me','CLOTH HANGERS',1000,1,551,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927799,509,'6 Pipe Cloth Drying Hangers Near Me','6-pipe-cloth-drying-hangers-near-me','CLOTH HANGERS',1000,1,552,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927800,509,'8 Pipe Cloth Drying Hangers Near Me','8-pipe-cloth-drying-hangers-near-me','CLOTH HANGERS',1000,1,553,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927801,509,'Ceiling Hangers For Balcony Near Me','ceiling-hangers-for-balcony-near-me','CLOTH HANGERS',1000,1,554,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927802,509,'Clothes Drying Rope Pulley Near Me','clothes-drying-rope-pulley-near-me','CLOTH HANGERS',1000,1,555,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927803,509,'Dry Cloth Hangers Near Me','dry-cloth-hangers-near-me','CLOTH HANGERS',1000,1,556,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927804,509,'Laundry Hanger Dryer Near Me','laundry-hanger-dryer-near-me','CLOTH HANGERS',1000,1,557,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927805,509,'Clothes Hanger Drier Near Me','clothes-hanger-drier-near-me','CLOTH HANGERS',1000,1,558,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927806,509,'Cloth Drying Hangers Installation','cloth-drying-hangers-installation','CLOTH HANGERS',1000,1,559,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927807,509,'Ceiling Cloth Hangers Installation','ceiling-cloth-hangers-installation','CLOTH HANGERS',1000,1,560,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927808,509,'Ceiling Cloth Drying Hangers Installation','ceiling-cloth-drying-hangers-installation','CLOTH HANGERS',1000,1,561,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927809,509,'Ceiling Clothes Hangers Installation','ceiling-clothes-hangers-installation','CLOTH HANGERS',1000,1,562,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927810,509,'Balcony Cloth Hangers Installation','balcony-cloth-hangers-installation','CLOTH HANGERS',1000,1,563,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927811,509,'Pulley Cloth Drying Hangers Installation','pulley-cloth-drying-hangers-installation','CLOTH HANGERS',1000,1,564,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927812,509,'Pulley Cloth Hangers Installation','pulley-cloth-hangers-installation','CLOTH HANGERS',1000,1,565,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927813,509,'Stainless Steel Cloth Hangers Installation','stainless-steel-cloth-hangers-installation','CLOTH HANGERS',1000,1,566,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927814,509,'6 Pipe Cloth Drying Hangers Installation','6-pipe-cloth-drying-hangers-installation','CLOTH HANGERS',1000,1,567,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927815,509,'8 Pipe Cloth Drying Hangers Installation','8-pipe-cloth-drying-hangers-installation','CLOTH HANGERS',1000,1,568,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927816,509,'Ceiling Hangers For Balcony Installation','ceiling-hangers-for-balcony-installation','CLOTH HANGERS',1000,1,569,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927817,509,'Clothes Drying Rope Pulley Installation','clothes-drying-rope-pulley-installation','CLOTH HANGERS',1000,1,570,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927818,509,'Dry Cloth Hangers Installation','dry-cloth-hangers-installation','CLOTH HANGERS',1000,1,571,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927819,509,'Laundry Hanger Dryer Installation','laundry-hanger-dryer-installation','CLOTH HANGERS',1000,1,572,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927820,509,'Clothes Hanger Drier Installation','clothes-hanger-drier-installation','CLOTH HANGERS',1000,1,573,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927821,509,'Cloth Drying Hangers Price','cloth-drying-hangers-price','CLOTH HANGERS',1000,1,574,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927822,509,'Ceiling Cloth Hangers Price','ceiling-cloth-hangers-price','CLOTH HANGERS',1000,1,575,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927823,509,'Ceiling Cloth Drying Hangers Price','ceiling-cloth-drying-hangers-price','CLOTH HANGERS',1000,1,576,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927824,509,'Ceiling Clothes Hangers Price','ceiling-clothes-hangers-price','CLOTH HANGERS',1000,1,577,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927825,509,'Balcony Cloth Hangers Price','balcony-cloth-hangers-price','CLOTH HANGERS',1000,1,578,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927826,509,'Pulley Cloth Drying Hangers Price','pulley-cloth-drying-hangers-price','CLOTH HANGERS',1000,1,579,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927827,509,'Pulley Cloth Hangers Price','pulley-cloth-hangers-price','CLOTH HANGERS',1000,1,580,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927828,509,'Stainless Steel Cloth Hangers Price','stainless-steel-cloth-hangers-price','CLOTH HANGERS',1000,1,581,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927829,509,'6 Pipe Cloth Drying Hangers Price','6-pipe-cloth-drying-hangers-price','CLOTH HANGERS',1000,1,582,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927830,509,'8 Pipe Cloth Drying Hangers Price','8-pipe-cloth-drying-hangers-price','CLOTH HANGERS',1000,1,583,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927831,509,'Ceiling Hangers For Balcony Price','ceiling-hangers-for-balcony-price','CLOTH HANGERS',1000,1,584,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927832,509,'Clothes Drying Rope Pulley Price','clothes-drying-rope-pulley-price','CLOTH HANGERS',1000,1,585,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927833,509,'Dry Cloth Hangers Price','dry-cloth-hangers-price','CLOTH HANGERS',1000,1,586,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927834,509,'Laundry Hanger Dryer Price','laundry-hanger-dryer-price','CLOTH HANGERS',1000,1,587,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927835,509,'Clothes Hanger Drier Price','clothes-hanger-drier-price','CLOTH HANGERS',1000,1,588,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927836,509,'Cloth Drying Hangers Dealers','cloth-drying-hangers-dealers','CLOTH HANGERS',1000,1,589,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927837,509,'Ceiling Cloth Hangers Dealers','ceiling-cloth-hangers-dealers','CLOTH HANGERS',1000,1,590,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927838,509,'Ceiling Cloth Drying Hangers Dealers','ceiling-cloth-drying-hangers-dealers','CLOTH HANGERS',1000,1,591,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927839,509,'Ceiling Clothes Hangers Dealers','ceiling-clothes-hangers-dealers','CLOTH HANGERS',1000,1,592,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927840,509,'Balcony Cloth Hangers Dealers','balcony-cloth-hangers-dealers','CLOTH HANGERS',1000,1,593,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927841,509,'Pulley Cloth Drying Hangers Dealers','pulley-cloth-drying-hangers-dealers','CLOTH HANGERS',1000,1,594,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927842,509,'Pulley Cloth Hangers Dealers','pulley-cloth-hangers-dealers','CLOTH HANGERS',1000,1,595,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927843,509,'Stainless Steel Cloth Hangers Dealers','stainless-steel-cloth-hangers-dealers','CLOTH HANGERS',1000,1,596,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927844,509,'6 Pipe Cloth Drying Hangers Dealers','6-pipe-cloth-drying-hangers-dealers','CLOTH HANGERS',1000,1,597,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927845,509,'8 Pipe Cloth Drying Hangers Dealers','8-pipe-cloth-drying-hangers-dealers','CLOTH HANGERS',1000,1,598,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927846,509,'Ceiling Hangers For Balcony Dealers','ceiling-hangers-for-balcony-dealers','CLOTH HANGERS',1000,1,599,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927847,509,'Clothes Drying Rope Pulley Dealers','clothes-drying-rope-pulley-dealers','CLOTH HANGERS',1000,1,600,'2026-09-19 02:25:55',0);
INSERT INTO `seo_service_keywords` VALUES (927855,66,'Balcony Safety Nets Installation','balcony-safety-nets-installation','SAFETY NETS',1000,1,2,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927856,66,'Balcony Safety Nets Dealers','balcony-safety-nets-dealers','SAFETY NETS',1000,1,3,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927857,66,'Balcony Safety Nets Contractors','balcony-safety-nets-contractors','SAFETY NETS',1000,1,4,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927858,66,'Balcony Safety Nets Price','balcony-safety-nets-price','SAFETY NETS',1000,1,5,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927859,66,'Balcony Safety Nets Cost','balcony-safety-nets-cost','SAFETY NETS',1000,1,6,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927861,66,'Balcony Safety Nets Fixing','balcony-safety-nets-fixing','SAFETY NETS',1000,1,8,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927862,66,'Balcony Safety Nets Fitting','balcony-safety-nets-fitting','SAFETY NETS',1000,1,9,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927865,66,'Safety Nets For Balcony Installation','safety-nets-for-balcony-installation','SAFETY NETS',1000,1,12,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927866,66,'Safety Nets For Balcony Dealers','safety-nets-for-balcony-dealers','SAFETY NETS',1000,1,13,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927867,66,'Safety Nets For Balcony Contractors','safety-nets-for-balcony-contractors','SAFETY NETS',1000,1,14,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927868,66,'Safety Nets For Balcony Price','safety-nets-for-balcony-price','SAFETY NETS',1000,1,15,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927869,66,'Safety Nets For Balcony Cost','safety-nets-for-balcony-cost','SAFETY NETS',1000,1,16,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927871,66,'Safety Nets For Balcony Fixing','safety-nets-for-balcony-fixing','SAFETY NETS',1000,1,18,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927872,66,'Safety Nets For Balcony Fitting','safety-nets-for-balcony-fitting','SAFETY NETS',1000,1,19,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927888,1,'Pigeon Nets Fitting','pigeon-nets-fitting','PIGEON NETS',1000,1,35,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927898,1,'Pigeon Safety Nets Fitting','pigeon-safety-nets-fitting','PIGEON NETS',1000,1,45,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927908,1,'Pigeon Nets For Balcony Fitting','pigeon-nets-for-balcony-fitting','PIGEON NETS',1000,1,55,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927920,10,'Anti Bird Netting Dealers','anti-bird-netting-dealers','BIRD NETS',1000,1,67,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927921,10,'Anti Bird Netting Contractors','anti-bird-netting-contractors','BIRD NETS',1000,1,68,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927923,10,'Anti Bird Netting Cost','anti-bird-netting-cost','BIRD NETS',1000,1,70,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927925,10,'Anti Bird Netting Fixing','anti-bird-netting-fixing','BIRD NETS',1000,1,72,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927926,10,'Anti Bird Netting Fitting','anti-bird-netting-fitting','BIRD NETS',1000,1,73,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927930,10,'Bird Safety Nets Dealers','bird-safety-nets-dealers','BIRD NETS',1000,1,77,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927931,10,'Bird Safety Nets Contractors','bird-safety-nets-contractors','BIRD NETS',1000,1,78,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927932,10,'Bird Safety Nets Price','bird-safety-nets-price','BIRD NETS',1000,1,79,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927936,512,'Anti Bird Spikes Dealers','anti-bird-spikes-dealers','BIRD NETS',1000,1,83,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927937,512,'Anti Bird Spikes Contractors','anti-bird-spikes-contractors','BIRD NETS',1000,1,84,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927938,512,'Anti Bird Spikes Price','anti-bird-spikes-price','BIRD NETS',1000,1,85,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927939,512,'Anti Bird Spikes Cost','anti-bird-spikes-cost','BIRD NETS',1000,1,86,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927941,512,'Anti Bird Spikes Fixing','anti-bird-spikes-fixing','BIRD NETS',1000,1,88,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927942,512,'Anti Bird Spikes Fitting','anti-bird-spikes-fitting','BIRD NETS',1000,1,89,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927946,512,'Bird Spikes Dealers','bird-spikes-dealers','BIRD NETS',1000,1,93,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927947,512,'Bird Spikes Contractors','bird-spikes-contractors','BIRD NETS',1000,1,94,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927948,512,'Bird Spikes Price','bird-spikes-price','BIRD NETS',1000,1,95,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927949,512,'Bird Spikes Cost','bird-spikes-cost','BIRD NETS',1000,1,96,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927951,512,'Bird Spikes Fixing','bird-spikes-fixing','BIRD NETS',1000,1,98,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927952,512,'Bird Spikes Fitting','bird-spikes-fitting','BIRD NETS',1000,1,99,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927963,47,'Invisible Grills Contractors','invisible-grills-contractors','INVISIBLE GRILLS',1000,1,110,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927968,47,'Invisible Grills Fitting','invisible-grills-fitting','INVISIBLE GRILLS',1000,1,115,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927973,47,'Balcony Invisible Grills Contractors','balcony-invisible-grills-contractors','INVISIBLE GRILLS',1000,1,120,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927978,47,'Balcony Invisible Grills Fitting','balcony-invisible-grills-fitting','INVISIBLE GRILLS',1000,1,125,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927984,47,'Invisible Grills For Balcony Contractors','invisible-grills-for-balcony-contractors','INVISIBLE GRILLS',1000,1,131,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (927990,47,'Invisible Grills For Windows Contractors','invisible-grills-for-windows-contractors','INVISIBLE GRILLS',1000,1,137,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928001,32,'Children Safety Nets Installation','children-safety-nets-installation','SAFETY NETS',1000,1,148,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928002,32,'Children Safety Nets Dealers','children-safety-nets-dealers','SAFETY NETS',1000,1,149,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928003,32,'Children Safety Nets Contractors','children-safety-nets-contractors','SAFETY NETS',1000,1,150,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928004,32,'Children Safety Nets Price','children-safety-nets-price','SAFETY NETS',1000,1,151,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928005,32,'Children Safety Nets Cost','children-safety-nets-cost','SAFETY NETS',1000,1,152,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928007,32,'Children Safety Nets Fixing','children-safety-nets-fixing','SAFETY NETS',1000,1,154,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928008,32,'Children Safety Nets Fitting','children-safety-nets-fitting','SAFETY NETS',1000,1,155,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928011,32,'Child Safety Nets Installation','child-safety-nets-installation','SAFETY NETS',1000,1,158,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928012,32,'Child Safety Nets Dealers','child-safety-nets-dealers','SAFETY NETS',1000,1,159,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928013,32,'Child Safety Nets Contractors','child-safety-nets-contractors','SAFETY NETS',1000,1,160,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928014,32,'Child Safety Nets Price','child-safety-nets-price','SAFETY NETS',1000,1,161,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928015,32,'Child Safety Nets Cost','child-safety-nets-cost','SAFETY NETS',1000,1,162,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928017,32,'Child Safety Nets Fixing','child-safety-nets-fixing','SAFETY NETS',1000,1,164,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928018,32,'Child Safety Nets Fitting','child-safety-nets-fitting','SAFETY NETS',1000,1,165,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928021,32,'Child Safety Nets For Balcony Installation','child-safety-nets-for-balcony-installation','SAFETY NETS',1000,1,168,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928022,32,'Child Safety Nets For Balcony Dealers','child-safety-nets-for-balcony-dealers','SAFETY NETS',1000,1,169,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928023,32,'Child Safety Nets For Balcony Contractors','child-safety-nets-for-balcony-contractors','SAFETY NETS',1000,1,170,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928024,32,'Child Safety Nets For Balcony Price','child-safety-nets-for-balcony-price','SAFETY NETS',1000,1,171,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928030,33,'Pet Safety Nets Installation','pet-safety-nets-installation','SAFETY NETS',1000,1,177,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928031,33,'Pet Safety Nets Dealers','pet-safety-nets-dealers','SAFETY NETS',1000,1,178,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928032,33,'Pet Safety Nets Contractors','pet-safety-nets-contractors','SAFETY NETS',1000,1,179,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928033,33,'Pet Safety Nets Price','pet-safety-nets-price','SAFETY NETS',1000,1,180,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928034,33,'Pet Safety Nets Cost','pet-safety-nets-cost','SAFETY NETS',1000,1,181,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928036,33,'Pet Safety Nets Fixing','pet-safety-nets-fixing','SAFETY NETS',1000,1,183,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928037,33,'Pet Safety Nets Fitting','pet-safety-nets-fitting','SAFETY NETS',1000,1,184,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928040,33,'Cat Safety Nets Installation','cat-safety-nets-installation','SAFETY NETS',1000,1,187,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928041,33,'Cat Safety Nets Dealers','cat-safety-nets-dealers','SAFETY NETS',1000,1,188,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928042,33,'Cat Safety Nets Contractors','cat-safety-nets-contractors','SAFETY NETS',1000,1,189,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928043,33,'Cat Safety Nets Price','cat-safety-nets-price','SAFETY NETS',1000,1,190,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928044,33,'Cat Safety Nets Cost','cat-safety-nets-cost','SAFETY NETS',1000,1,191,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928046,33,'Cat Safety Nets Fixing','cat-safety-nets-fixing','SAFETY NETS',1000,1,193,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928047,33,'Cat Safety Nets Fitting','cat-safety-nets-fitting','SAFETY NETS',1000,1,194,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928052,515,'Cricket Nets Contractors','cricket-nets-contractors','SPORTS NETS',1000,1,199,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928054,515,'Cricket Nets Cost','cricket-nets-cost','SPORTS NETS',1000,1,201,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928056,515,'Cricket Nets Fixing','cricket-nets-fixing','SPORTS NETS',1000,1,203,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928057,515,'Cricket Nets Fitting','cricket-nets-fitting','SPORTS NETS',1000,1,204,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928062,515,'Cricket Practice Nets Contractors','cricket-practice-nets-contractors','SPORTS NETS',1000,1,209,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928064,515,'Cricket Practice Nets Cost','cricket-practice-nets-cost','SPORTS NETS',1000,1,211,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928066,515,'Cricket Practice Nets Fixing','cricket-practice-nets-fixing','SPORTS NETS',1000,1,213,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928067,515,'Cricket Practice Nets Fitting','cricket-practice-nets-fitting','SPORTS NETS',1000,1,214,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928072,513,'Box Cricket Nets Contractors','box-cricket-nets-contractors','SPORTS NETS',1000,1,219,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928074,513,'Box Cricket Nets Cost','box-cricket-nets-cost','SPORTS NETS',1000,1,221,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928076,513,'Box Cricket Nets Fixing','box-cricket-nets-fixing','SPORTS NETS',1000,1,223,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928077,513,'Box Cricket Nets Fitting','box-cricket-nets-fitting','SPORTS NETS',1000,1,224,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928082,515,'Terrace Cricket Nets Contractors','terrace-cricket-nets-contractors','SPORTS NETS',1000,1,229,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928091,509,'Cloth Drying Hangers Contractors','cloth-drying-hangers-contractors','CLOTH HANGERS',1000,1,238,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928093,509,'Cloth Drying Hangers Cost','cloth-drying-hangers-cost','CLOTH HANGERS',1000,1,240,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928095,509,'Cloth Drying Hangers Fixing','cloth-drying-hangers-fixing','CLOTH HANGERS',1000,1,242,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928096,509,'Cloth Drying Hangers Fitting','cloth-drying-hangers-fitting','CLOTH HANGERS',1000,1,243,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928101,509,'Ceiling Cloth Hangers Contractors','ceiling-cloth-hangers-contractors','CLOTH HANGERS',1000,1,248,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928103,509,'Ceiling Cloth Hangers Cost','ceiling-cloth-hangers-cost','CLOTH HANGERS',1000,1,250,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928105,509,'Ceiling Cloth Hangers Fixing','ceiling-cloth-hangers-fixing','CLOTH HANGERS',1000,1,252,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928106,509,'Ceiling Cloth Hangers Fitting','ceiling-cloth-hangers-fitting','CLOTH HANGERS',1000,1,253,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928111,509,'Ceiling Cloth Drying Hangers Contractors','ceiling-cloth-drying-hangers-contractors','CLOTH HANGERS',1000,1,258,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928118,28,'Construction Safety Nets Installation','construction-safety-nets-installation','SAFETY NETS',1000,1,265,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928119,28,'Construction Safety Nets Dealers','construction-safety-nets-dealers','SAFETY NETS',1000,1,266,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928120,28,'Construction Safety Nets Contractors','construction-safety-nets-contractors','SAFETY NETS',1000,1,267,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928121,28,'Construction Safety Nets Price','construction-safety-nets-price','SAFETY NETS',1000,1,268,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928122,28,'Construction Safety Nets Cost','construction-safety-nets-cost','SAFETY NETS',1000,1,269,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928124,28,'Construction Safety Nets Fixing','construction-safety-nets-fixing','SAFETY NETS',1000,1,271,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928125,28,'Construction Safety Nets Fitting','construction-safety-nets-fitting','SAFETY NETS',1000,1,272,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928128,28,'Building Safety Nets Installation','building-safety-nets-installation','SAFETY NETS',1000,1,275,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928129,28,'Building Safety Nets Dealers','building-safety-nets-dealers','SAFETY NETS',1000,1,276,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928130,28,'Building Safety Nets Contractors','building-safety-nets-contractors','SAFETY NETS',1000,1,277,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928131,28,'Building Safety Nets Price','building-safety-nets-price','SAFETY NETS',1000,1,278,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928134,29,'Industrial Safety Nets Installation','industrial-safety-nets-installation','SAFETY NETS',1000,1,281,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928135,29,'Industrial Safety Nets Dealers','industrial-safety-nets-dealers','SAFETY NETS',1000,1,282,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928136,29,'Industrial Safety Nets Contractors','industrial-safety-nets-contractors','SAFETY NETS',1000,1,283,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928137,29,'Industrial Safety Nets Price','industrial-safety-nets-price','SAFETY NETS',1000,1,284,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928140,26,'Duct Area Safety Nets Installation','duct-area-safety-nets-installation','SAFETY NETS',1000,1,287,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928141,26,'Duct Area Safety Nets Dealers','duct-area-safety-nets-dealers','SAFETY NETS',1000,1,288,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928142,26,'Duct Area Safety Nets Contractors','duct-area-safety-nets-contractors','SAFETY NETS',1000,1,289,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928143,26,'Duct Area Safety Nets Price','duct-area-safety-nets-price','SAFETY NETS',1000,1,290,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928144,26,'Duct Area Safety Nets Cost','duct-area-safety-nets-cost','SAFETY NETS',1000,1,291,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928145,26,'Duct Area Safety Nets Near Me','duct-area-safety-nets-near-me','SAFETY NETS',1000,1,292,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928146,26,'Duct Area Safety Nets Fixing','duct-area-safety-nets-fixing','SAFETY NETS',1000,1,293,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928147,26,'Duct Area Safety Nets Fitting','duct-area-safety-nets-fitting','SAFETY NETS',1000,1,294,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928151,23,'Garware Safety Nets Installation','garware-safety-nets-installation','SAFETY NETS',1000,1,298,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928152,23,'Garware Safety Nets Dealers','garware-safety-nets-dealers','SAFETY NETS',1000,1,299,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928153,23,'Garware Safety Nets Contractors','garware-safety-nets-contractors','SAFETY NETS',1000,1,300,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928154,23,'Garware Safety Nets Price','garware-safety-nets-price','SAFETY NETS',1000,1,301,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928155,23,'Garware Safety Nets Cost','garware-safety-nets-cost','SAFETY NETS',1000,1,302,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928156,23,'Garware Safety Nets Near Me','garware-safety-nets-near-me','SAFETY NETS',1000,1,303,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928157,23,'Garware Safety Nets Fixing','garware-safety-nets-fixing','SAFETY NETS',1000,1,304,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928158,23,'Garware Safety Nets Fitting','garware-safety-nets-fitting','SAFETY NETS',1000,1,305,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928161,23,'Nylon Safety Nets Installation','nylon-safety-nets-installation','SAFETY NETS',1000,1,308,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928162,23,'Nylon Safety Nets Dealers','nylon-safety-nets-dealers','SAFETY NETS',1000,1,309,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928163,23,'Nylon Safety Nets Contractors','nylon-safety-nets-contractors','SAFETY NETS',1000,1,310,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928164,23,'Nylon Safety Nets Price','nylon-safety-nets-price','SAFETY NETS',1000,1,311,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928165,23,'Nylon Safety Nets Cost','nylon-safety-nets-cost','SAFETY NETS',1000,1,312,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928166,23,'Nylon Safety Nets Near Me','nylon-safety-nets-near-me','SAFETY NETS',1000,1,313,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928167,23,'Nylon Safety Nets Fixing','nylon-safety-nets-fixing','SAFETY NETS',1000,1,314,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928168,23,'Nylon Safety Nets Fitting','nylon-safety-nets-fitting','SAFETY NETS',1000,1,315,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928217,66,'Balcony Netting Installation','balcony-netting-installation','SAFETY NETS',1000,1,364,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928218,66,'Balcony Netting Dealers','balcony-netting-dealers','SAFETY NETS',1000,1,365,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928219,66,'Balcony Netting Price','balcony-netting-price','SAFETY NETS',1000,1,366,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928222,66,'Balcony Nets Installation','balcony-nets-installation','SAFETY NETS',1000,1,369,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928223,66,'Balcony Nets Dealers','balcony-nets-dealers','SAFETY NETS',1000,1,370,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928224,66,'Balcony Nets Price','balcony-nets-price','SAFETY NETS',1000,1,371,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928227,66,'Balcony Protection Nets Installation','balcony-protection-nets-installation','SAFETY NETS',1000,1,374,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928228,66,'Balcony Protection Nets Dealers','balcony-protection-nets-dealers','SAFETY NETS',1000,1,375,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928229,66,'Balcony Protection Nets Price','balcony-protection-nets-price','SAFETY NETS',1000,1,376,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928232,66,'Safety Nets For Balconies Installation','safety-nets-for-balconies-installation','SAFETY NETS',1000,1,379,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928233,66,'Safety Nets For Balconies Dealers','safety-nets-for-balconies-dealers','SAFETY NETS',1000,1,380,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928234,66,'Safety Nets For Balconies Price','safety-nets-for-balconies-price','SAFETY NETS',1000,1,381,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928237,66,'Balcony Mesh Installation','balcony-mesh-installation','SAFETY NETS',1000,1,384,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928238,66,'Balcony Mesh Dealers','balcony-mesh-dealers','SAFETY NETS',1000,1,385,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928239,66,'Balcony Mesh Price','balcony-mesh-price','SAFETY NETS',1000,1,386,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928241,66,'Nylon Balcony Safety Nets Installation','nylon-balcony-safety-nets-installation','SAFETY NETS',1000,1,388,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928242,66,'Nylon Balcony Safety Nets Dealers','nylon-balcony-safety-nets-dealers','SAFETY NETS',1000,1,389,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928243,66,'Nylon Balcony Safety Nets Price','nylon-balcony-safety-nets-price','SAFETY NETS',1000,1,390,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928245,66,'Garware Balcony Safety Nets Installation','garware-balcony-safety-nets-installation','SAFETY NETS',1000,1,392,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928246,66,'Garware Balcony Safety Nets Dealers','garware-balcony-safety-nets-dealers','SAFETY NETS',1000,1,393,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928247,66,'Garware Balcony Safety Nets Price','garware-balcony-safety-nets-price','SAFETY NETS',1000,1,394,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928249,66,'High Rise Balcony Safety Nets Installation','high-rise-balcony-safety-nets-installation','SAFETY NETS',1000,1,396,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928250,66,'High Rise Balcony Safety Nets Dealers','high-rise-balcony-safety-nets-dealers','SAFETY NETS',1000,1,397,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928251,66,'High Rise Balcony Safety Nets Price','high-rise-balcony-safety-nets-price','SAFETY NETS',1000,1,398,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928253,66,'Apartment Balcony Safety Nets Installation','apartment-balcony-safety-nets-installation','SAFETY NETS',1000,1,400,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928254,66,'Apartment Balcony Safety Nets Dealers','apartment-balcony-safety-nets-dealers','SAFETY NETS',1000,1,401,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928255,66,'Apartment Balcony Safety Nets Price','apartment-balcony-safety-nets-price','SAFETY NETS',1000,1,402,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928257,66,'Villa Balcony Safety Nets Installation','villa-balcony-safety-nets-installation','SAFETY NETS',1000,1,404,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928258,66,'Villa Balcony Safety Nets Dealers','villa-balcony-safety-nets-dealers','SAFETY NETS',1000,1,405,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928259,66,'Villa Balcony Safety Nets Price','villa-balcony-safety-nets-price','SAFETY NETS',1000,1,406,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928262,66,'Balcony Grill Netting Installation','balcony-grill-netting-installation','SAFETY NETS',1000,1,409,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928263,66,'Balcony Grill Netting Dealers','balcony-grill-netting-dealers','SAFETY NETS',1000,1,410,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928264,66,'Balcony Grill Netting Price','balcony-grill-netting-price','SAFETY NETS',1000,1,411,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928266,66,'Transparent Balcony Safety Nets Installation','transparent-balcony-safety-nets-installation','SAFETY NETS',1000,1,413,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928267,66,'Transparent Balcony Safety Nets Dealers','transparent-balcony-safety-nets-dealers','SAFETY NETS',1000,1,414,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928268,66,'Transparent Balcony Safety Nets Price','transparent-balcony-safety-nets-price','SAFETY NETS',1000,1,415,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928271,66,'Balcony Fall Protection Nets Installation','balcony-fall-protection-nets-installation','SAFETY NETS',1000,1,418,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928272,66,'Balcony Fall Protection Nets Dealers','balcony-fall-protection-nets-dealers','SAFETY NETS',1000,1,419,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928273,66,'Balcony Fall Protection Nets Price','balcony-fall-protection-nets-price','SAFETY NETS',1000,1,420,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928275,66,'Balcony Net Fixing Installation','balcony-net-fixing-installation','SAFETY NETS',1000,1,422,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928276,66,'Balcony Net Fixing Dealers','balcony-net-fixing-dealers','SAFETY NETS',1000,1,423,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928277,66,'Balcony Net Fixing Price','balcony-net-fixing-price','SAFETY NETS',1000,1,424,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928358,10,'Bird Nets Dealers','bird-nets-dealers','BIRD NETS',1000,1,505,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928359,10,'Bird Nets Price','bird-nets-price','BIRD NETS',1000,1,506,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928363,10,'Bird Net Dealers','bird-net-dealers','BIRD NETS',1000,1,510,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928364,10,'Bird Net Price','bird-net-price','BIRD NETS',1000,1,511,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928367,10,'Bird Netting Services Dealers','bird-netting-services-dealers','BIRD NETS',1000,1,514,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928368,10,'Bird Netting Services Price','bird-netting-services-price','BIRD NETS',1000,1,515,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928372,10,'Bird Nets For Balcony Dealers','bird-nets-for-balcony-dealers','BIRD NETS',1000,1,519,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928373,10,'Bird Nets For Balcony Price','bird-nets-for-balcony-price','BIRD NETS',1000,1,520,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928374,10,'Bird Nets For Balcony Cost','bird-nets-for-balcony-cost','BIRD NETS',1000,1,521,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928376,10,'Bird Nets For Balcony Fixing','bird-nets-for-balcony-fixing','BIRD NETS',1000,1,523,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928379,10,'Bird Net For Balcony Dealers','bird-net-for-balcony-dealers','BIRD NETS',1000,1,526,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928380,10,'Bird Net For Balcony Price','bird-net-for-balcony-price','BIRD NETS',1000,1,527,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928384,10,'Window Bird Nets Dealers','window-bird-nets-dealers','BIRD NETS',1000,1,531,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928385,10,'Window Bird Nets Price','window-bird-nets-price','BIRD NETS',1000,1,532,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928389,10,'Terrace Bird Nets Dealers','terrace-bird-nets-dealers','BIRD NETS',1000,1,536,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928390,10,'Terrace Bird Nets Price','terrace-bird-nets-price','BIRD NETS',1000,1,537,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928394,10,'Commercial Bird Netting Dealers','commercial-bird-netting-dealers','BIRD NETS',1000,1,541,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928395,10,'Commercial Bird Netting Price','commercial-bird-netting-price','BIRD NETS',1000,1,542,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928399,10,'Industrial Bird Netting Dealers','industrial-bird-netting-dealers','BIRD NETS',1000,1,546,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928400,10,'Industrial Bird Netting Price','industrial-bird-netting-price','BIRD NETS',1000,1,547,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928404,10,'Warehouse Bird Netting Dealers','warehouse-bird-netting-dealers','BIRD NETS',1000,1,551,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928405,10,'Warehouse Bird Netting Price','warehouse-bird-netting-price','BIRD NETS',1000,1,552,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928409,10,'Factory Bird Netting Dealers','factory-bird-netting-dealers','BIRD NETS',1000,1,556,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928410,10,'Factory Bird Netting Price','factory-bird-netting-price','BIRD NETS',1000,1,557,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928414,10,'Transparent Bird Netting Dealers','transparent-bird-netting-dealers','BIRD NETS',1000,1,561,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928415,10,'Transparent Bird Netting Price','transparent-bird-netting-price','BIRD NETS',1000,1,562,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928419,10,'Sparrow Protection Nets Dealers','sparrow-protection-nets-dealers','BIRD NETS',1000,1,566,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928420,10,'Sparrow Protection Nets Price','sparrow-protection-nets-price','BIRD NETS',1000,1,567,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928424,10,'Crow Protection Nets Dealers','crow-protection-nets-dealers','BIRD NETS',1000,1,571,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928425,10,'Crow Protection Nets Price','crow-protection-nets-price','BIRD NETS',1000,1,572,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928429,10,'Bird Protection Nets Dealers','bird-protection-nets-dealers','BIRD NETS',1000,1,576,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928430,10,'Bird Protection Nets Price','bird-protection-nets-price','BIRD NETS',1000,1,577,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928434,10,'Garware Bird Nets Dealers','garware-bird-nets-dealers','BIRD NETS',1000,1,581,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928435,10,'Garware Bird Nets Price','garware-bird-nets-price','BIRD NETS',1000,1,582,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928439,10,'Nylon Bird Nets Dealers','nylon-bird-nets-dealers','BIRD NETS',1000,1,586,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928440,10,'Nylon Bird Nets Price','nylon-bird-nets-price','BIRD NETS',1000,1,587,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928444,10,'Bird Proofing Solutions Dealers','bird-proofing-solutions-dealers','BIRD NETS',1000,1,591,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928445,10,'Bird Proofing Solutions Price','bird-proofing-solutions-price','BIRD NETS',1000,1,592,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928449,512,'Pigeon Spikes Dealers','pigeon-spikes-dealers','BIRD NETS',1000,1,596,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928450,512,'Pigeon Spikes Price','pigeon-spikes-price','BIRD NETS',1000,1,597,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928453,512,'Stainless Steel Bird Spikes Dealers','stainless-steel-bird-spikes-dealers','BIRD NETS',1000,1,600,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928454,512,'Stainless Steel Bird Spikes Price','stainless-steel-bird-spikes-price','BIRD NETS',1000,1,601,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928457,512,'Polycarbonate Bird Spikes Dealers','polycarbonate-bird-spikes-dealers','BIRD NETS',1000,1,604,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928458,512,'Polycarbonate Bird Spikes Price','polycarbonate-bird-spikes-price','BIRD NETS',1000,1,605,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928461,512,'Window Bird Spikes Dealers','window-bird-spikes-dealers','BIRD NETS',1000,1,608,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928462,512,'Window Bird Spikes Price','window-bird-spikes-price','BIRD NETS',1000,1,609,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928465,512,'Balcony Bird Spikes Dealers','balcony-bird-spikes-dealers','BIRD NETS',1000,1,612,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928466,512,'Balcony Bird Spikes Price','balcony-bird-spikes-price','BIRD NETS',1000,1,613,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928469,512,'AC Outdoor Unit Bird Spikes Dealers','ac-outdoor-unit-bird-spikes-dealers','BIRD NETS',1000,1,616,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928470,512,'AC Outdoor Unit Bird Spikes Price','ac-outdoor-unit-bird-spikes-price','BIRD NETS',1000,1,617,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928474,512,'Bird Deterrent Spikes Dealers','bird-deterrent-spikes-dealers','BIRD NETS',1000,1,621,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928475,512,'Bird Deterrent Spikes Price','bird-deterrent-spikes-price','BIRD NETS',1000,1,622,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928478,512,'Commercial Bird Spikes Dealers','commercial-bird-spikes-dealers','BIRD NETS',1000,1,625,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928479,512,'Commercial Bird Spikes Price','commercial-bird-spikes-price','BIRD NETS',1000,1,626,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928531,32,'Kids Safety Nets Installation','kids-safety-nets-installation','SAFETY NETS',1000,1,678,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928532,32,'Kids Safety Nets Dealers','kids-safety-nets-dealers','SAFETY NETS',1000,1,679,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928533,32,'Kids Safety Nets Price','kids-safety-nets-price','SAFETY NETS',1000,1,680,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928536,32,'Baby Safety Nets Installation','baby-safety-nets-installation','SAFETY NETS',1000,1,683,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928537,32,'Baby Safety Nets Dealers','baby-safety-nets-dealers','SAFETY NETS',1000,1,684,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928538,32,'Baby Safety Nets Price','baby-safety-nets-price','SAFETY NETS',1000,1,685,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928540,32,'Staircase Child Safety Nets Installation','staircase-child-safety-nets-installation','SAFETY NETS',1000,1,687,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928541,32,'Staircase Child Safety Nets Dealers','staircase-child-safety-nets-dealers','SAFETY NETS',1000,1,688,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928542,32,'Staircase Child Safety Nets Price','staircase-child-safety-nets-price','SAFETY NETS',1000,1,689,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928544,32,'Window Child Safety Nets Installation','window-child-safety-nets-installation','SAFETY NETS',1000,1,691,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928545,32,'Window Child Safety Nets Dealers','window-child-safety-nets-dealers','SAFETY NETS',1000,1,692,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928546,32,'Window Child Safety Nets Price','window-child-safety-nets-price','SAFETY NETS',1000,1,693,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928549,32,'Toddler Safety Nets Installation','toddler-safety-nets-installation','SAFETY NETS',1000,1,696,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928550,32,'Toddler Safety Nets Dealers','toddler-safety-nets-dealers','SAFETY NETS',1000,1,697,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928551,32,'Toddler Safety Nets Price','toddler-safety-nets-price','SAFETY NETS',1000,1,698,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928553,32,'High Tension Child Safety Nets Installation','high-tension-child-safety-nets-installation','SAFETY NETS',1000,1,700,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928554,32,'High Tension Child Safety Nets Dealers','high-tension-child-safety-nets-dealers','SAFETY NETS',1000,1,701,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928555,32,'High Tension Child Safety Nets Price','high-tension-child-safety-nets-price','SAFETY NETS',1000,1,702,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928558,32,'Children Balcony Fall Protection Installation','children-balcony-fall-protection-installation','SAFETY NETS',1000,1,705,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928559,32,'Children Balcony Fall Protection Dealers','children-balcony-fall-protection-dealers','SAFETY NETS',1000,1,706,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928560,32,'Children Balcony Fall Protection Price','children-balcony-fall-protection-price','SAFETY NETS',1000,1,707,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928563,33,'Cat Netting For Balcony Installation','cat-netting-for-balcony-installation','SAFETY NETS',1000,1,710,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928564,33,'Cat Netting For Balcony Dealers','cat-netting-for-balcony-dealers','SAFETY NETS',1000,1,711,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928565,33,'Cat Netting For Balcony Price','cat-netting-for-balcony-price','SAFETY NETS',1000,1,712,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928568,33,'Dog Safety Nets Installation','dog-safety-nets-installation','SAFETY NETS',1000,1,715,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928569,33,'Dog Safety Nets Dealers','dog-safety-nets-dealers','SAFETY NETS',1000,1,716,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928570,33,'Dog Safety Nets Price','dog-safety-nets-price','SAFETY NETS',1000,1,717,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928573,33,'Animal Safety Nets Installation','animal-safety-nets-installation','SAFETY NETS',1000,1,720,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928574,33,'Animal Safety Nets Dealers','animal-safety-nets-dealers','SAFETY NETS',1000,1,721,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928575,33,'Animal Safety Nets Price','animal-safety-nets-price','SAFETY NETS',1000,1,722,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928578,27,'Monkey Safety Nets Installation','monkey-safety-nets-installation','SAFETY NETS',1000,1,725,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928579,27,'Monkey Safety Nets Dealers','monkey-safety-nets-dealers','SAFETY NETS',1000,1,726,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928580,27,'Monkey Safety Nets Price','monkey-safety-nets-price','SAFETY NETS',1000,1,727,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928583,27,'Anti Monkey Nets Installation','anti-monkey-nets-installation','SAFETY NETS',1000,1,730,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928584,27,'Anti Monkey Nets Dealers','anti-monkey-nets-dealers','SAFETY NETS',1000,1,731,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928585,27,'Anti Monkey Nets Price','anti-monkey-nets-price','SAFETY NETS',1000,1,732,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928588,27,'Monkey Protection Nets Installation','monkey-protection-nets-installation','SAFETY NETS',1000,1,735,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928589,27,'Monkey Protection Nets Dealers','monkey-protection-nets-dealers','SAFETY NETS',1000,1,736,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928590,27,'Monkey Protection Nets Price','monkey-protection-nets-price','SAFETY NETS',1000,1,737,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928593,27,'Monkey Barrier Nets Installation','monkey-barrier-nets-installation','SAFETY NETS',1000,1,740,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928594,27,'Monkey Barrier Nets Dealers','monkey-barrier-nets-dealers','SAFETY NETS',1000,1,741,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928595,27,'Monkey Barrier Nets Price','monkey-barrier-nets-price','SAFETY NETS',1000,1,742,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928598,33,'Pet Fall Protection Nets Installation','pet-fall-protection-nets-installation','SAFETY NETS',1000,1,745,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928599,33,'Pet Fall Protection Nets Dealers','pet-fall-protection-nets-dealers','SAFETY NETS',1000,1,746,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928600,33,'Pet Fall Protection Nets Price','pet-fall-protection-nets-price','SAFETY NETS',1000,1,747,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928628,515,'Rooftop Cricket Nets Dealers','rooftop-cricket-nets-dealers','SPORTS NETS',1000,1,775,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928632,515,'Outdoor Cricket Nets Dealers','outdoor-cricket-nets-dealers','SPORTS NETS',1000,1,779,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928636,515,'Indoor Cricket Nets Dealers','indoor-cricket-nets-dealers','SPORTS NETS',1000,1,783,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928641,515,'Football Boundary Nets Dealers','football-boundary-nets-dealers','SPORTS NETS',1000,1,788,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928646,515,'Badminton Court Nets Dealers','badminton-court-nets-dealers','SPORTS NETS',1000,1,793,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928651,515,'Volleyball Court Nets Dealers','volleyball-court-nets-dealers','SPORTS NETS',1000,1,798,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928656,515,'Sports Safety Nets Dealers','sports-safety-nets-dealers','SPORTS NETS',1000,1,803,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928661,515,'Golf Practice Nets Dealers','golf-practice-nets-dealers','SPORTS NETS',1000,1,808,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928666,515,'Sports Netting Dealers','sports-netting-dealers','SPORTS NETS',1000,1,813,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928713,509,'Dry Cloth Hangers Dealers','dry-cloth-hangers-dealers','CLOTH HANGERS',1000,1,860,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928718,509,'Laundry Hanger Dryer Dealers','laundry-hanger-dryer-dealers','CLOTH HANGERS',1000,1,865,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928723,509,'Clothes Hanger Drier Dealers','clothes-hanger-drier-dealers','CLOTH HANGERS',1000,1,870,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928727,23,'Open Area Safety Nets Installation','open-area-safety-nets-installation','SAFETY NETS',1000,1,874,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928728,23,'Open Area Safety Nets Dealers','open-area-safety-nets-dealers','SAFETY NETS',1000,1,875,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928729,23,'Open Area Safety Nets Price','open-area-safety-nets-price','SAFETY NETS',1000,1,876,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928730,23,'Open Area Safety Nets Near Me','open-area-safety-nets-near-me','SAFETY NETS',1000,1,877,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928732,23,'Staircase Safety Nets Installation','staircase-safety-nets-installation','SAFETY NETS',1000,1,879,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928733,23,'Staircase Safety Nets Dealers','staircase-safety-nets-dealers','SAFETY NETS',1000,1,880,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928734,23,'Staircase Safety Nets Price','staircase-safety-nets-price','SAFETY NETS',1000,1,881,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928735,23,'Staircase Safety Nets Near Me','staircase-safety-nets-near-me','SAFETY NETS',1000,1,882,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928737,23,'Shaft Safety Nets Installation','shaft-safety-nets-installation','SAFETY NETS',1000,1,884,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928738,23,'Shaft Safety Nets Dealers','shaft-safety-nets-dealers','SAFETY NETS',1000,1,885,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928739,23,'Shaft Safety Nets Price','shaft-safety-nets-price','SAFETY NETS',1000,1,886,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928740,23,'Shaft Safety Nets Near Me','shaft-safety-nets-near-me','SAFETY NETS',1000,1,887,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928742,28,'Debris Netting Installation','debris-netting-installation','SAFETY NETS',1000,1,889,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928743,28,'Debris Netting Dealers','debris-netting-dealers','SAFETY NETS',1000,1,890,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928744,28,'Debris Netting Price','debris-netting-price','SAFETY NETS',1000,1,891,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928745,28,'Debris Netting Near Me','debris-netting-near-me','SAFETY NETS',1000,1,892,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928747,31,'Fall Protection Nets Installation','fall-protection-nets-installation','SAFETY NETS',1000,1,894,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928748,31,'Fall Protection Nets Dealers','fall-protection-nets-dealers','SAFETY NETS',1000,1,895,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928749,31,'Fall Protection Nets Price','fall-protection-nets-price','SAFETY NETS',1000,1,896,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928750,31,'Fall Protection Nets Near Me','fall-protection-nets-near-me','SAFETY NETS',1000,1,897,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928752,30,'Fall Safety Nets Installation','fall-safety-nets-installation','SAFETY NETS',1000,1,899,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928753,30,'Fall Safety Nets Dealers','fall-safety-nets-dealers','SAFETY NETS',1000,1,900,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928754,30,'Fall Safety Nets Price','fall-safety-nets-price','SAFETY NETS',1000,1,901,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928755,30,'Fall Safety Nets Near Me','fall-safety-nets-near-me','SAFETY NETS',1000,1,902,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928757,23,'Swimming Pool Safety Nets Installation','swimming-pool-safety-nets-installation','SAFETY NETS',1000,1,904,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928758,23,'Swimming Pool Safety Nets Dealers','swimming-pool-safety-nets-dealers','SAFETY NETS',1000,1,905,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928759,23,'Swimming Pool Safety Nets Price','swimming-pool-safety-nets-price','SAFETY NETS',1000,1,906,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928760,23,'Swimming Pool Safety Nets Near Me','swimming-pool-safety-nets-near-me','SAFETY NETS',1000,1,907,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928762,23,'Coconut Tree Safety Nets Installation','coconut-tree-safety-nets-installation','SAFETY NETS',1000,1,909,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928763,23,'Coconut Tree Safety Nets Dealers','coconut-tree-safety-nets-dealers','SAFETY NETS',1000,1,910,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928764,23,'Coconut Tree Safety Nets Price','coconut-tree-safety-nets-price','SAFETY NETS',1000,1,911,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928765,23,'Coconut Tree Safety Nets Near Me','coconut-tree-safety-nets-near-me','SAFETY NETS',1000,1,912,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928767,28,'Scaffolding Safety Nets Installation','scaffolding-safety-nets-installation','SAFETY NETS',1000,1,914,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928768,28,'Scaffolding Safety Nets Dealers','scaffolding-safety-nets-dealers','SAFETY NETS',1000,1,915,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928769,28,'Scaffolding Safety Nets Price','scaffolding-safety-nets-price','SAFETY NETS',1000,1,916,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928770,28,'Scaffolding Safety Nets Near Me','scaffolding-safety-nets-near-me','SAFETY NETS',1000,1,917,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928771,23,'Glass Balcony Safety Nets Installation','glass-balcony-safety-nets-installation','SAFETY NETS',1000,1,918,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928772,23,'Glass Balcony Safety Nets Dealers','glass-balcony-safety-nets-dealers','SAFETY NETS',1000,1,919,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928773,23,'Glass Balcony Safety Nets Price','glass-balcony-safety-nets-price','SAFETY NETS',1000,1,920,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928774,23,'Glass Balcony Safety Nets Near Me','glass-balcony-safety-nets-near-me','SAFETY NETS',1000,1,921,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928776,23,'Heavy Duty Safety Nets Installation','heavy-duty-safety-nets-installation','SAFETY NETS',1000,1,923,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928777,23,'Heavy Duty Safety Nets Dealers','heavy-duty-safety-nets-dealers','SAFETY NETS',1000,1,924,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928778,23,'Heavy Duty Safety Nets Price','heavy-duty-safety-nets-price','SAFETY NETS',1000,1,925,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928779,23,'Heavy Duty Safety Nets Near Me','heavy-duty-safety-nets-near-me','SAFETY NETS',1000,1,926,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928781,23,'HDPE Safety Nets Installation','hdpe-safety-nets-installation','SAFETY NETS',1000,1,928,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928782,23,'HDPE Safety Nets Dealers','hdpe-safety-nets-dealers','SAFETY NETS',1000,1,929,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928783,23,'HDPE Safety Nets Price','hdpe-safety-nets-price','SAFETY NETS',1000,1,930,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928784,23,'HDPE Safety Nets Near Me','hdpe-safety-nets-near-me','SAFETY NETS',1000,1,931,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928786,23,'Safety Netting Solutions Installation','safety-netting-solutions-installation','SAFETY NETS',1000,1,933,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928787,23,'Safety Netting Solutions Dealers','safety-netting-solutions-dealers','SAFETY NETS',1000,1,934,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928788,23,'Safety Netting Solutions Price','safety-netting-solutions-price','SAFETY NETS',1000,1,935,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928789,23,'Safety Netting Solutions Near Me','safety-netting-solutions-near-me','SAFETY NETS',1000,1,936,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928790,23,'Safety Net Installation','safety-net-installation','SAFETY NETS',1000,1,937,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928791,23,'Safety Net Installation Installation','safety-net-installation-installation','SAFETY NETS',1000,1,938,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928792,23,'Safety Net Installation Dealers','safety-net-installation-dealers','SAFETY NETS',1000,1,939,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928793,23,'Safety Net Installation Price','safety-net-installation-price','SAFETY NETS',1000,1,940,'2026-09-19 02:27:21',0);
INSERT INTO `seo_service_keywords` VALUES (928794,23,'Safety Net Installation Near Me','safety-net-installation-near-me','SAFETY NETS',1000,1,941,'2026-09-19 02:27:21',0);
/*!40000 ALTER TABLE `seo_service_keywords` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-09-19  4:17:34


-- 4. Admin Users Table
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `email`, `role`, `is_active`) VALUES
(1, 'gopichand24', '$2y$12$kNfa1CF/siYHzzFksyMYWegMbX3VJrgKsBCFgYm2n8kKxugjWUqVu', 'gopichandmailapally@gmail.com', 'super_admin', 1);

-- 5. Inquiries Table
DROP TABLE IF EXISTS `contact_inquiries`;
CREATE TABLE `contact_inquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `service` varchar(100) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT 'Chennai',
  `message` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'new',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
