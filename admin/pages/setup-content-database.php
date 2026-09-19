<?php
/**
 * Standalone Content Database Setup
 * No external dependencies - creates everything from scratch
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database file path - adjust if needed
$db_file = __DIR__ . '/../../data/local-test.db';

// Ensure data directory exists
$data_dir = dirname($db_file);
if (!file_exists($data_dir)) {
    mkdir($data_dir, 0755, true);
}

try {
    // Connect to SQLite database
    $conn = new PDO('sqlite:' . $db_file);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $success_count = 0;
    $errors = [];
    
    // Create tables
    $tables = [
        "CREATE TABLE IF NOT EXISTS services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            service_id VARCHAR(50) NOT NULL UNIQUE,
            service_name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            category VARCHAR(100),
            is_active INTEGER DEFAULT 1,
            display_order INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS keywords (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            service_id INTEGER NOT NULL,
            keyword TEXT NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            is_active INTEGER DEFAULT 1,
            display_order INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS areas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            area_name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            zone VARCHAR(50) DEFAULT 'Chennai',
            is_active INTEGER DEFAULT 1,
            display_order INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS generated_pages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            keyword_id INTEGER NOT NULL,
            area_id INTEGER NOT NULL,
            page_url VARCHAR(500) NOT NULL UNIQUE,
            page_title VARCHAR(255),
            content TEXT,
            is_published INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS pillar_pages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            service_id INTEGER NOT NULL,
            page_url VARCHAR(500) NOT NULL UNIQUE,
            page_title VARCHAR(255),
            content TEXT,
            is_published INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS main_pages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_name VARCHAR(255) NOT NULL,
            page_url VARCHAR(500) NOT NULL UNIQUE,
            is_published INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_name VARCHAR(255) NOT NULL,
            rating INTEGER DEFAULT 5,
            review_text TEXT,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS blogs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            content TEXT,
            is_published INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS faqs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            question TEXT NOT NULL,
            answer TEXT NOT NULL,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )"
    ];
    
    foreach ($tables as $sql) {
        $conn->exec($sql);
        $success_count++;
    }
    
    // Check if data exists
    $services_count = $conn->query("SELECT COUNT(*) FROM services")->fetchColumn();
    
    if ($services_count == 0) {
        // Insert all 64 services
        $services = [
            ['pigeon-nets', 'Pigeon Nets', 'PIGEON NETS', 1],
            ['pigeon-net', 'Pigeon Net', 'PIGEON NETS', 2],
            ['balcony-netting', 'Balcony Netting', 'PIGEON NETS', 3],
            ['pigeon-net-for-balcony', 'Pigeon Net For Balcony', 'PIGEON NETS', 4],
            ['pigeon-nets-installation', 'Pigeon Nets Installation', 'PIGEON NETS', 5],
            ['pigeon-bird-netting', 'Pigeon Bird Netting', 'PIGEON NETS', 6],
            ['pigeon-net-installation', 'Pigeon Net Installation', 'PIGEON NETS', 7],
            ['pigeon-net-near-me', 'Pigeon Net Near Me', 'PIGEON NETS', 8],
            ['pigeon-net-for-balcony-near-me', 'Pigeon Net For Balcony Near Me', 'PIGEON NETS', 9],
            ['pigeon-net-installation-near-me', 'Pigeon Net Installation Near Me', 'PIGEON NETS', 10],
            ['pigeon-safety-nets', 'Pigeon Safety Nets', 'PIGEON NETS', 11],
            ['pigeon-net-price', 'Pigeon Net Price', 'PIGEON NETS', 12],
            ['kabutar-jali-near-me', 'Kabutar Jali Near Me', 'PIGEON NETS', 13],
            ['bird-nets', 'Bird Nets', 'BIRD NETS', 14],
            ['bird-net', 'Bird Net', 'BIRD NETS', 15],
            ['bird-net-for-balcony', 'Bird Net For Balcony', 'BIRD NETS', 16],
            ['bird-net-near-me', 'Bird Net Near Me', 'BIRD NETS', 17],
            ['nets-for-birds', 'Nets For Birds', 'BIRD NETS', 18],
            ['net-for-birds', 'Net For Birds', 'BIRD NETS', 19],
            ['industrial-bird-netting', 'Industrial Bird Netting', 'BIRD NETS', 20],
            ['bird-netting', 'Bird Netting', 'BIRD NETS', 21],
            ['anti-bird-netting', 'Anti Bird Netting', 'BIRD NETS', 22],
            ['safety-nets', 'Safety Nets', 'SAFETY NETS', 23],
            ['balcony-safety-nets', 'Balcony Safety Nets', 'SAFETY NETS', 24],
            ['safety-nets-for-balconies', 'Safety Nets For Balconies', 'SAFETY NETS', 25],
            ['duct-area-safety-nets', 'Duct Area Safety Nets', 'SAFETY NETS', 26],
            ['monkey-safety-nets', 'Monkey Safety Nets', 'SAFETY NETS', 27],
            ['construction-safety-nets', 'Construction Safety Nets', 'SAFETY NETS', 28],
            ['industrial-safety-nets', 'Industrial Safety Nets', 'SAFETY NETS', 29],
            ['fall-safety-nets', 'Fall Safety Nets', 'SAFETY NETS', 30],
            ['fall-protection-nets', 'Fall Protection Nets', 'SAFETY NETS', 31],
            ['children-safety-nets', 'Children Safety Nets', 'SAFETY NETS', 32],
            ['pet-safety-nets', 'Pet Safety Nets', 'SAFETY NETS', 33],
            ['cricket-nets', 'Cricket Nets', 'SPORTS NETS', 34],
            ['cricket-nets-price', 'Cricket Nets Price', 'SPORTS NETS', 35],
            ['cricket-nets-near-me', 'Cricket Nets Near Me', 'SPORTS NETS', 36],
            ['cricket-practice-net', 'Cricket Practice Net', 'SPORTS NETS', 37],
            ['cricket-practice-nets', 'Cricket Practice Nets', 'SPORTS NETS', 38],
            ['cricket-net-price', 'Cricket Net Price', 'SPORTS NETS', 39],
            ['cricket-indoor-nets-near-me', 'Cricket Indoor Nets Near Me', 'SPORTS NETS', 40],
            ['indoor-cricket-nets-near-me', 'Indoor Cricket Nets Near Me', 'SPORTS NETS', 41],
            ['sports-nets', 'Sports Nets', 'SPORTS NETS', 42],
            ['sports-netting', 'Sports Netting', 'SPORTS NETS', 43],
            ['cricket-netting', 'Cricket Netting', 'SPORTS NETS', 44],
            ['box-cricket-net', 'Box Cricket Net', 'SPORTS NETS', 45],
            ['cricket-net-installation', 'Cricket Net Installation', 'SPORTS NETS', 46],
            ['invisible-grills', 'Invisible Grills', 'INVISIBLE GRILLS', 47],
            ['invisible-grill-near-me', 'Invisible Grill Near Me', 'INVISIBLE GRILLS', 48],
            ['ss-invisible-grills', 'SS Invisible Grills', 'INVISIBLE GRILLS', 49],
            ['invisible-grill-for-balcony', 'Invisible Grill For Balcony', 'INVISIBLE GRILLS', 50],
            ['balcony-invisible-grill', 'Balcony Invisible Grill', 'INVISIBLE GRILLS', 51],
            ['invisible-grill-for-balcony-near-me', 'Invisible Grill For Balcony Near Me', 'INVISIBLE GRILLS', 52],
            ['invisible-safety-grill', 'Invisible Safety Grill', 'INVISIBLE GRILLS', 53],
            ['invisible-grill-for-safety', 'Invisible Grill For Safety', 'INVISIBLE GRILLS', 54],
            ['invisible-grill-for-pigeons', 'Invisible Grill For Pigeons', 'INVISIBLE GRILLS', 55],
            ['ceiling-cloth-hangers', 'Ceiling Cloth Hangers', 'CLOTH HANGERS', 56],
            ['dry-cloth-hangers', 'Dry Cloth Hangers', 'CLOTH HANGERS', 57],
            ['cloth-drying-hangers', 'Cloth Drying Hangers', 'CLOTH HANGERS', 58],
            ['cloth-hanger-for-balcony', 'Cloth Hanger For Balcony', 'CLOTH HANGERS', 59],
            ['pulley-cloth-drying-hanger', 'Pulley Cloth Drying Hanger', 'CLOTH HANGERS', 60],
            ['pulley-cloth-hanger', 'Pulley Cloth Hanger', 'CLOTH HANGERS', 61],
            ['laundry-hanger-dryer', 'Laundry Hanger Dryer', 'CLOTH HANGERS', 62],
            ['clothes-hanger-to-dry-clothes', 'Clothes Hanger To Dry Clothes', 'CLOTH HANGERS', 63],
            ['clothes-hanger-drier', 'Clothes Hanger Drier', 'CLOTH HANGERS', 64]
        ];
        
        $stmt = $conn->prepare("INSERT INTO services (service_id, service_name, slug, category, display_order) VALUES (?, ?, ?, ?, ?)");
        foreach ($services as $service) {
            $stmt->execute([$service[0], $service[1], $service[0], $service[2], $service[3]]);
            $success_count++;
        }
        
        // Insert keywords
        $stmt = $conn->prepare("INSERT INTO keywords (service_id, keyword, slug, display_order) VALUES (?, ?, ?, ?)");
        foreach ($services as $index => $service) {
            $stmt->execute([$index + 1, $service[1], $service[0], $service[3]]);
            $success_count++;
        }
        
        // Insert all 188 areas
        $areas = ['Abids','Adikmet','Afzalgunj','Aliabad','Alwal','Amberpet','Ameerpet','Ananthagiri Hills','Asif Nagar','Asifabad','Attapur','Attapur Metro','Ayyappa Society','Bachupally','Badangpet','Bagh Amberpet','Bagh Lingampally','Bahadurpura','Balkampet','Balnagar','Bandlaguda','Banjara Hills','Barkas','Basheerbagh','Begum Bazar','Begumpet','Boduppal','Borabanda','Bowenpally','Boysguda','Champapet','Chanda Nagar','Chandanagar','Charminar','Chikkadpally','Chintal','Chintalkunta','Dabeerpura','Dammaiguda','Dar-ul-Shifa','Dhoolpet','Dilsukhnagar','Domalguda','Dundigal','East Marredpally','ECIL','ECIL Cross Roads','Edi Bazar','Erragadda','Falaknuma','Fateh Nagar','Ferozguda','Film Nagar','Financial District','Gachibowli','Gaddiannaram','Gajularamaram','Gandhi Nagar','Ghatkesar','Golconda','Goshamahal','Gowlidoddy','Gudimelakunta','Habsiguda','Hafeezpet','Hayathnagar','Himayatnagar','Hitech City','Hussainialam','Hyderguda','Ibrahim Bagh','Ibrahimpatnam','IDA Bollaram','IS Sadan','Izzat Nagar','Jagadgirigutta','Jahanuma','Jamia Osmania','Jeedimetla','Jubilee Hills','Kachiguda','Kailash Nagar','Kalimandir','Kamala Nagar','Kapra','Karkhana','Karwan','Kattedan','Khairtabad','Khajaguda','Kishanbagh','Kismatkhan Gudda','Kompally','Kondapur','Kothapet','KPHB Colony','Kukatpally','Lakdikapul','Lalapet','Langer Houz','LB Nagar','Lingampally','Madannapet','Madhapur','Madinaguda','Mahdipatnam','Malakpet','Mallapur','Manikonda','Marredpally','Masab Tank','Meerpet','Mehdipatnam','Mettuguda','Miyapur','Moghalpura','Moosarambagh','Moti Nagar','Moula Ali','Musheerabad','Nacharam','Nagaram','Nagole','Nallakunta','Nampally','Nanakramguda','Nanal Nagar','Narayanguda','Neredmet','New Bowenpally','Nizampet','Old Bowenpally','Old City','Old Malakpet','Osman Nagar','Osmangunj','Osmania University','Padmarao Nagar','Panjagutta','Panjagutta Circle','Paradise','Patancheru','Patel Road','Peerzadiguda','Pragathi Nagar','Purani Haveli','Qila Mohd. Nagar','Quthbullapur','Rajendranagar','Ramakrishna Puram','Ramanthapur','Ramnagar','RC Puram','Red Hills','Safilguda','Saidabad','Sainikpuri','Sanath Nagar','Sangareddy','Santosh Nagar','Saroornagar','Tambaram','Serilingampally','Shaikpet','Shamirpet','Shamshabad','Shankarpally','Tank Bund','Tappachabutra','Tarnaka','Tolichowki','Trimulgherry','Umdanagar','Uppal','Uppuguda','Vanasthalipuram','Vengal Rao Nagar','Vidyanagar','Vikrampuri','Warasiguda','West Marredpally','Yakutpura','Yousufguda','Zakir Hussain Colony','Zamistanpur','Zeregumbad','Ziaguda','Zohra Nagar'];
        
        $stmt = $conn->prepare("INSERT INTO areas (area_name, slug, display_order) VALUES (?, ?, ?)");
        foreach ($areas as $index => $area) {
            $slug = strtolower(str_replace([' ', '.'], ['-', ''], $area));
            $stmt->execute([$area, $slug, $index + 1]);
            $success_count++;
        }
    }
    
    // Get counts
    $keywords_count = $conn->query("SELECT COUNT(*) FROM keywords")->fetchColumn();
    $areas_count = $conn->query("SELECT COUNT(*) FROM areas")->fetchColumn();
    $possible_pages = $keywords_count * $areas_count;
    
} catch (PDOException $e) {
    $errors[] = $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Content Database Setup</title>
    <style>
        body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#667eea,#764ba2);padding:40px 20px;margin:0}
        .container{max-width:900px;margin:0 auto;background:#fff;border-radius:20px;padding:40px;box-shadow:0 20px 60px rgba(0,0,0,0.3)}
        h1{color:#333;margin:0 0 20px 0;font-size:32px}
        .success{background:linear-gradient(135deg,#D1FAE5,#A7F3D0);border:3px solid #10B981;border-radius:15px;padding:25px;margin:25px 0}
        .success h2{margin:0 0 10px 0;color:#065F46;font-size:24px}
        .highlight{background:linear-gradient(135deg,#FEF3C7,#FDE68A);border:3px solid #F59E0B;border-radius:15px;padding:25px;margin:25px 0}
        .highlight h3{color:#92400E;margin:0 0 15px 0}
        .highlight p{color:#78350F;font-size:18px;font-weight:600;margin:10px 0}
        .btn{display:inline-block;padding:15px 30px;background:linear-gradient(135deg,#10B981,#059669);color:#fff;text-decoration:none;border-radius:10px;font-weight:600;margin:10px 10px 10px 0}
        .error{background:linear-gradient(135deg,#FEE2E2,#FECACA);border:3px solid #EF4444;border-radius:15px;padding:25px;margin:25px 0;color:#991B1B}
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Content Database Setup</h1>
        
        <?php if (empty($errors)): ?>
        <div class="success">
            <h2>✅ Success!</h2>
            <p><strong><?php echo $success_count; ?></strong> operations completed.</p>
        </div>
        
        <div class="highlight">
            <h3>🎯 Database Ready</h3>
            <p>✅ <strong><?php echo $keywords_count; ?> Keywords</strong> loaded</p>
            <p>✅ <strong><?php echo $areas_count; ?> Areas</strong> loaded</p>
            <p>✅ <strong><?php echo number_format($possible_pages); ?> Possible Pages</strong> (<?php echo $keywords_count; ?> × <?php echo $areas_count; ?>)</p>
        </div>
        
        <div style="margin-top:30px">
            <a href="generate-pages.php" class="btn">🚀 Go to Page Generator</a>
            <a href="pillar-page-generator.php" class="btn">📄 Pillar Pages</a>
        </div>
        <?php else: ?>
        <div class="error">
            <h3>❌ Errors Occurred</h3>
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
