<?php
/**
 * Pillar Page Template
 * Shows service with 150 area buttons
 * Example: /balcony-netting.php
 */

// Page configuration (set by generator)
$service_name = $service_name ?? 'Service Name';
$service_slug = $service_slug ?? 'service-slug';
$meta_title = $meta_title ?? $service_name . ' in Chennai | Professional Installation';
$meta_description = $meta_description ?? 'Professional ' . $service_name . ' services in Chennai. Choose from 150+ locations. Expert installation, quality materials, affordable prices. Call now!';

// Service areas (150 locations)
$service_areas = [
    'Abids', 'Adikmet', 'Afzalgunj', 'Aliwal', 'Amberpet', 'Ameerpet', 'Ananthagiri Hills', 'Asif Nagar',
    'Asifabad', 'Attapur', 'Attapur Metro', 'Bagh Lingampally', 'Bagh Amberpet', 'Bahadurpura', 'Balkampet',
    'Balnagar', 'Bandlaguda', 'Banjara Hills', 'Barkas', 'Basheerbagh', 'Begum Bazar', 'Begumpet', 'Borabanda',
    'Champapet', 'Chandanagar', 'Charminar', 'Chikkadpally', 'Chintal', 'Chintalkunta', 'Dabeerpura', 
    'Dilsukhnagar', 'Domalguda', 'Ecil', 'Erragadda', 'Falaknuma', 'Gachibowli', 'Gaddiannaram', 'Gandhi Nagar',
    'Golconda', 'Goshamahal', 'Gudimelakunta', 'Habsiguda', 'Hafeezpet', 'Hayathnagar', 'Himayatnagar', 
    'Hussainialam', 'Hyderguda', 'Jeedimetla', 'Jubilee Hills', 'Kachiguda', 'Kailash Nagar', 'Kalimandir',
    'Kamala Nagar', 'Kapra', 'Karkhana', 'Karwan', 'Kattedan', 'Khairtabad', 'Khajaguda', 'Kishanbagh',
    'Kismatkhan Gudda', 'Kompally', 'Kondapur', 'Kothapet', 'Kukatpally', 'LB Nagar', 'Langar Houz',
    'Lingampally', 'Madhapur', 'Madinaguda', 'Mahdipatnam', 'Malakpet', 'Mallapur', 'Marredpally', 
    'Masab Tank', 'Manikonda', 'Mehdipatnam', 'Mettuguda', 'Miyapur', 'Moghalpura', 'Moosarambagh', 
    'Moti Nagar', 'Moula Ali', 'Musheerabad', 'Nacharam', 'Nagaram', 'Nagole', 'Nallakunta', 'Nanakramguda',
    'Narayanguda', 'Nizampet', 'Old City', 'Osmania University', 'Padmarao Nagar', 'Panjagutta', 'Paradise',
    'Patelguda', 'Patny', 'Peerzadiguda', 'PJR Nagar', 'Pragathi Nagar', 'Puppalaguda', 'Quthbullapur',
    'Rajendranagar', 'Ramanthapur', 'Ramgopalpet', 'Ramnagar', 'Ramnagar Extension', 'Red Hills', 
    'Safilguda', 'Saidabad', 'Sainikpuri', 'Sanath Nagar', 'Sanghi Nagar', 'Santosh Nagar', 'Saroor Nagar',
    'Tambaram', 'Serilingampally', 'Shadnagar', 'Shah Ali Banda', 'Shaikpet', 'Shamshabad', 
    'Shivam Road', 'Somajiguda', 'SR Nagar', 'Sultan Bazar', 'Suncity', 'Tarnaka', 'Toli Chowki',
    'Tolichowki', 'Trimulgherry', 'Tukkuguda', 'Turkayamjal', 'Uppal', 'Vanasthalipuram', 'Vidyanagar',
    'Vikrampuri', 'Vijayanagar Colony', 'West Marredpally', 'Yapral', 'Yousufguda', 'Zaheerabad'
];

define('GCM_INIT', true);
require_once 'config/database.php';
require_once 'config/constants.php';

$current_page = 'pillar';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($meta_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($service_slug); ?>, <?php echo htmlspecialchars($service_name); ?> chennai, professional installation">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>/uploads/favicon.png">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/modern-header.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/modern-footer.css">
    
    <style>
        /* Pillar Page Styles */
        .pillar-hero {
            background: linear-gradient(135deg, #3B82F6, #8B5CF6);
            color: #FFFFFF;
            padding: 80px 20px;
            text-align: center;
        }
        
        .pillar-hero h1 {
            font-size: 48px;
            font-weight: 700;
            margin: 0 0 16px 0;
        }
        
        .pillar-hero p {
            font-size: 20px;
            margin: 0 0 32px 0;
            opacity: 0.9;
        }
        
        .pillar-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 18px;
            font-weight: 600;
        }
        
        .stat-item i {
            font-size: 24px;
            color: #FEF3C7;
        }
        
        .pillar-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 60px 20px;
        }
        
        .content-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .content-header h2 {
            font-size: 36px;
            font-weight: 700;
            color: #1E293B;
            margin: 0 0 12px 0;
        }
        
        .content-header p {
            font-size: 18px;
            color: #64748B;
        }
        
        .areas-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
        }
        
        .area-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 20px;
            background: #FFFFFF;
            border: 2px solid #E2E8F0;
            border-radius: 12px;
            color: #475569;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .area-button:hover {
            background: linear-gradient(135deg, #3B82F6, #8B5CF6);
            color: #FFFFFF;
            border-color: #3B82F6;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
        }
        
        .area-button i {
            font-size: 12px;
        }
        
        .info-section {
            background: linear-gradient(135deg, #F8FAFC, #EFF6FF);
            border-radius: 16px;
            padding: 40px;
            margin-top: 60px;
        }
        
        .info-section h3 {
            font-size: 28px;
            font-weight: 700;
            color: #1E293B;
            margin: 0 0 20px 0;
        }
        
        .info-section p {
            font-size: 16px;
            line-height: 1.8;
            color: #475569;
            margin-bottom: 16px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-top: 40px;
        }
        
        .feature-card {
            background: #FFFFFF;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .feature-card i {
            font-size: 40px;
            color: #3B82F6;
            margin-bottom: 16px;
        }
        
        .feature-card h4 {
            font-size: 18px;
            font-weight: 700;
            color: #1E293B;
            margin: 0 0 8px 0;
        }
        
        .feature-card p {
            font-size: 14px;
            color: #64748B;
            margin: 0;
        }
        
        @media (max-width: 1024px) {
            .areas-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .pillar-hero h1 {
                font-size: 32px;
            }
            
            .areas-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .content-header h2 {
                font-size: 28px;
            }
        }
        
        @media (max-width: 480px) {
            .areas-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/modern-header.php'; ?>
    
    <!-- Hero Section -->
    <section class="pillar-hero">
        <div class="hero-container">
            <h1><?php echo htmlspecialchars($service_name); ?> in Chennai</h1>
            <p>Professional Installation | Quality Materials | Affordable Prices</p>
            <div class="pillar-stats">
                <div class="stat-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>150+ Locations</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-users"></i>
                    <span>10,000+ Customers</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>15+ Years Experience</span>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Main Content -->
    <section class="pillar-content">
        <div class="content-header">
            <h2>Choose Your Location</h2>
            <p>Click on your area to view detailed service information and get instant quote</p>
        </div>
        
        <!-- Areas Grid -->
        <div class="areas-grid">
            <?php foreach ($service_areas as $area): 
                $area_slug = strtolower(str_replace(' ', '-', $area));
                $page_url = SITE_URL . '/' . $service_slug . '-in-' . $area_slug;
            ?>
            <a href="<?php echo $page_url; ?>" class="area-button">
                <i class="fas fa-map-marker-alt"></i>
                <?php echo htmlspecialchars($area); ?>
            </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Info Section -->
        <div class="info-section">
            <h3>About <?php echo htmlspecialchars($service_name); ?> Services</h3>
            <p>
                We provide professional <?php echo htmlspecialchars($service_name); ?> services across all areas of Chennai. 
                Our team of experienced technicians ensures high-quality installation using premium materials.
            </p>
            <p>
                With over 15 years of experience and 10,000+ satisfied customers, we are the trusted choice for 
                <?php echo htmlspecialchars($service_name); ?> in Chennai. We offer competitive pricing, quick installation, 
                and excellent after-sales service.
            </p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-star"></i>
                    <h4>Quality Materials</h4>
                    <p>We use only premium, durable materials for all installations</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-tools"></i>
                    <h4>Expert Installation</h4>
                    <p>Trained professionals with years of experience</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-shield-check"></i>
                    <h4>Warranty Included</h4>
                    <p>All installations come with comprehensive warranty</p>
                </div>
            </div>
        </div>
    </section>
    
    <?php include 'includes/modern-footer.php'; ?>
</body>
</html>
