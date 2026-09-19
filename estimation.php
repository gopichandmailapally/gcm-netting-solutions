<?php
/**
 * GCM Netting Solutions - Price Estimation Page
 * Interactive calculators for all services
 */

define('GCM_INIT', true);
require_once 'config/config.php';
require_once 'config/database.php';

$page_title = 'Safety Net Price Calculator | Instant Cost Estimation for Pigeon Nets, Cricket Nets & More in Chennai';
$meta_description = 'Get instant price estimates for Pigeon Nets, Bird Nets, Cricket Nets, Invisible Grills, Safety Nets, Cloth Hangers & more. Free online calculator with per sqft pricing. Calculate exact cost for balcony netting, sports nets installation in Chennai. Transparent pricing, no hidden charges.';
$meta_keywords = 'pigeon net price, cricket net cost, invisible grill price, safety net charges, bird netting cost, cloth hanger price, balcony net estimation, sports net calculator, netting services price Chennai, per sqft cost, installation charges, price calculator online, free estimation, instant quotation, affordable netting solutions';

// Additional CSS for estimation page
$additional_css = ['assets/css/estimation.css', 'assets/css/estimation-seo.css'];
// Additional JS for estimation page
$additional_js = ['assets/js/estimation.js'];
$current_page = 'estimation';

include 'includes/modern-header.php';
?>

<div class="estimation-page">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header text-center">
            <h1><i class="fas fa-calculator"></i> Safety Net Price Calculator | Instant Cost Estimation for All Netting Services in Chennai</h1>
            <p class="lead">Get accurate price estimates for Pigeon Nets, Bird Nets, Cricket Nets, Invisible Grills, Balcony Safety Nets, Cloth Hangers & more. Free online calculator with transparent per sqft pricing - No hidden charges!</p>
            <div class="seo-keywords-bar">
                <span class="keyword-badge">Pigeon Net Price</span>
                <span class="keyword-badge">Cricket Net Cost</span>
                <span class="keyword-badge">Invisible Grill Price</span>
                <span class="keyword-badge">Safety Net Charges</span>
                <span class="keyword-badge">Instant Estimation</span>
            </div>
        </div>
        
        <!-- Calculator Tabs -->
        <div class="calculator-tabs">
            <button class="tab-btn active" data-calculator="safety-nets">
                <i class="fas fa-shield-alt"></i>
                <span>Safety Nets</span>
            </button>
            <button class="tab-btn" data-calculator="cricket-nets">
                <i class="fas fa-volleyball-ball"></i>
                <span>Cricket Nets</span>
            </button>
            <button class="tab-btn" data-calculator="invisible-grills">
                <i class="fas fa-grip-lines"></i>
                <span>Invisible Grills</span>
            </button>
            <button class="tab-btn" data-calculator="cloth-hangers">
                <i class="fas fa-tshirt"></i>
                <span>Cloth Hangers</span>
            </button>
        </div>
        
        <!-- Calculator 1: Safety Nets / Pigeon Nets / Balcony Safety Nets -->
        <div class="calculator-panel active" id="safety-nets">
            <div class="calculator-card">
                <div class="calculator-header">
                    <h2><i class="fas fa-shield-alt"></i> Safety Nets / Pigeon Nets Estimation</h2>
                    <p>Calculate the cost based on square feet, net thickness, and square gap</p>
                </div>
                
                <div class="calculator-body">
                    <div class="calculator-grid">
                        <!-- Input Section -->
                        <div class="input-section">
                            <h3>Enter Measurements</h3>
                            
                            <div class="input-method-toggle">
                                <button class="method-btn active" data-method="area">By Square Feet</button>
                                <button class="method-btn" data-method="dimensions">By Length × Height</button>
                            </div>
                            
                            <div class="input-area active" id="area-method">
                                <div class="form-group">
                                    <label>Total Area (Square Feet) *</label>
                                    <input type="number" id="safety-area" class="form-control" placeholder="Enter area in SFT" min="1" step="1">
                                </div>
                            </div>
                            
                            <div class="input-area" id="dimensions-method">
                                <div class="form-group">
                                    <label>Length (Feet) *</label>
                                    <input type="number" id="safety-length" class="form-control" placeholder="Enter length" min="1" step="0.1">
                                </div>
                                <div class="form-group">
                                    <label>Height/Width (Feet) *</label>
                                    <input type="number" id="safety-height" class="form-control" placeholder="Enter height/width" min="1" step="0.1">
                                </div>
                                <div class="calculated-area">
                                    <strong>Calculated Area:</strong> <span id="calc-area">0</span> SFT
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Net Thickness *</label>
                                <select id="safety-thickness" class="form-control">
                                    <option value="">Select Thickness</option>
                                    <option value="1.5">1.5mm</option>
                                    <option value="2">2mm</option>
                                    <option value="2.5">2.5mm</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Square Gap *</label>
                                <select id="safety-gap" class="form-control">
                                    <option value="">Select Gap Size</option>
                                    <option value="30">30mm</option>
                                    <option value="40">40mm</option>
                                    <option value="45">45mm</option>
                                    <option value="50">50mm</option>
                                </select>
                            </div>
                            
                            <button class="btn btn-primary btn-large" onclick="calculateSafetyNets()">
                                <i class="fas fa-calculator"></i> Calculate Price
                            </button>
                        </div>
                        
                        <!-- Result Section -->
                        <div class="result-section">
                            <h3>Price Estimate</h3>
                            <div class="result-card" id="safety-result">
                                <div class="result-placeholder">
                                    <i class="fas fa-info-circle"></i>
                                    <p>Enter measurements and click calculate to see price estimate</p>
                                </div>
                            </div>
                            
                            <!-- Price Chart -->
                            <div class="price-chart">
                                <h4>Price Guide</h4>
                                <table class="price-table">
                                    <thead>
                                        <tr>
                                            <th>Area Range</th>
                                            <th>1.5mm (30mm)</th>
                                            <th>2mm (40mm-50mm)</th>
                                            <th>2.5mm (40mm-50mm)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Below 100 SFT</td>
                                            <td>₹1,500</td>
                                            <td>₹1,650-1,800</td>
                                            <td>₹1,800</td>
                                        </tr>
                                        <tr>
                                            <td>100-250 SFT</td>
                                            <td>₹16-20/SFT</td>
                                            <td>₹20-28/SFT</td>
                                            <td>₹22-30/SFT</td>
                                        </tr>
                                        <tr>
                                            <td>250-500 SFT</td>
                                            <td>₹14-18/SFT</td>
                                            <td>₹16-24/SFT</td>
                                            <td>₹18-26/SFT</td>
                                        </tr>
                                        <tr>
                                            <td>500-1000 SFT</td>
                                            <td>₹12-16/SFT</td>
                                            <td>₹14-22/SFT</td>
                                            <td>₹16-24/SFT</td>
                                        </tr>
                                        <tr>
                                            <td>1000-5000 SFT</td>
                                            <td>₹10-14/SFT</td>
                                            <td>₹10-18/SFT</td>
                                            <td>₹12-20/SFT</td>
                                        </tr>
                                        <tr>
                                            <td>Above 5000 SFT</td>
                                            <td colspan="3" class="text-center">Call for Best Price</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Calculator 2: Cricket Nets -->
        <div class="calculator-panel" id="cricket-nets">
            <div class="calculator-card">
                <div class="calculator-header">
                    <h2><i class="fas fa-volleyball-ball"></i> Cricket Nets Estimation</h2>
                    <p>Calculate total area using formula: L×W + 2×L×H + 2×W×H</p>
                </div>
                
                <div class="calculator-body">
                    <div class="calculator-grid">
                        <!-- Input Section -->
                        <div class="input-section">
                            <h3>Enter Dimensions</h3>
                            
                            <div class="form-group">
                                <label>Length (Feet) *</label>
                                <input type="number" id="cricket-length" class="form-control" placeholder="Enter length" min="1" step="0.1">
                            </div>
                            
                            <div class="form-group">
                                <label>Width (Feet) *</label>
                                <input type="number" id="cricket-width" class="form-control" placeholder="Enter width" min="1" step="0.1">
                            </div>
                            
                            <div class="form-group">
                                <label>Height (Feet) *</label>
                                <input type="number" id="cricket-height" class="form-control" placeholder="Enter height" min="1" step="0.1">
                            </div>
                            
                            <div class="formula-display">
                                <strong>Formula:</strong> L×W + 2×L×H + 2×W×H
                            </div>
                            
                            <div class="calculated-area">
                                <strong>Total Area:</strong> <span id="cricket-calc-area">0</span> SFT
                            </div>
                            
                            <div class="form-group">
                                <label>Square Gap *</label>
                                <select id="cricket-gap" class="form-control">
                                    <option value="">Select Gap Size</option>
                                    <option value="40">40mm</option>
                                    <option value="45">45mm</option>
                                    <option value="50">50mm</option>
                                </select>
                            </div>
                            
                            <button class="btn btn-primary btn-large" onclick="calculateCricketNets()">
                                <i class="fas fa-calculator"></i> Calculate Price
                            </button>
                        </div>
                        
                        <!-- Result Section -->
                        <div class="result-section">
                            <h3>Price Estimate</h3>
                            <div class="result-card" id="cricket-result">
                                <div class="result-placeholder">
                                    <i class="fas fa-info-circle"></i>
                                    <p>Enter dimensions and click calculate to see price estimate</p>
                                </div>
                            </div>
                            
                            <!-- Price Chart -->
                            <div class="price-chart">
                                <h4>Price Guide</h4>
                                <table class="price-table">
                                    <thead>
                                        <tr>
                                            <th>Area Range</th>
                                            <th>40mm</th>
                                            <th>45mm</th>
                                            <th>50mm</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>1000-5000 SFT</td>
                                            <td>₹15-18/SFT</td>
                                            <td>₹14-17/SFT</td>
                                            <td>₹13-16/SFT</td>
                                        </tr>
                                        <tr>
                                            <td>5000-10000 SFT</td>
                                            <td>₹14-17/SFT</td>
                                            <td>₹13-16/SFT</td>
                                            <td>₹12-15/SFT</td>
                                        </tr>
                                        <tr>
                                            <td>10000-15000 SFT</td>
                                            <td>₹12-15/SFT</td>
                                            <td>₹11-14/SFT</td>
                                            <td>₹10-13/SFT</td>
                                        </tr>
                                        <tr>
                                            <td>15000-20000 SFT</td>
                                            <td>₹11-14/SFT</td>
                                            <td>₹10-13/SFT</td>
                                            <td>₹9-12/SFT</td>
                                        </tr>
                                        <tr>
                                            <td>Above 20000 SFT</td>
                                            <td>₹10-12/SFT</td>
                                            <td>₹9-11/SFT</td>
                                            <td>₹8-10/SFT</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Calculator 3: Invisible Grills -->
        <div class="calculator-panel" id="invisible-grills">
            <div class="calculator-card">
                <div class="calculator-header">
                    <h2><i class="fas fa-grip-lines"></i> Invisible Grills Estimation</h2>
                    <p>Calculate cost based on square feet, SS wire thickness, and line gap</p>
                </div>
                
                <div class="calculator-body">
                    <div class="calculator-grid">
                        <!-- Input Section -->
                        <div class="input-section">
                            <h3>Enter Measurements</h3>
                            
                            <div class="input-method-toggle">
                                <button class="method-btn active" data-method="area">By Square Feet</button>
                                <button class="method-btn" data-method="dimensions">By Length × Height</button>
                            </div>
                            
                            <div class="input-area active" id="grill-area-method">
                                <div class="form-group">
                                    <label>Total Area (Square Feet) *</label>
                                    <input type="number" id="grill-area" class="form-control" placeholder="Enter area in SFT" min="1" step="1">
                                </div>
                            </div>
                            
                            <div class="input-area" id="grill-dimensions-method">
                                <div class="form-group">
                                    <label>Length (Feet) *</label>
                                    <input type="number" id="grill-length" class="form-control" placeholder="Enter length" min="1" step="0.1">
                                </div>
                                <div class="form-group">
                                    <label>Height/Width (Feet) *</label>
                                    <input type="number" id="grill-height" class="form-control" placeholder="Enter height/width" min="1" step="0.1">
                                </div>
                                <div class="calculated-area">
                                    <strong>Calculated Area:</strong> <span id="grill-calc-area">0</span> SFT
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>SS Wire Thickness *</label>
                                <select id="grill-thickness" class="form-control">
                                    <option value="">Select Thickness</option>
                                    <option value="1.5">1.5mm</option>
                                    <option value="2">2mm</option>
                                    <option value="2.5">2.5mm</option>
                                    <option value="3">3mm</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Line Gap *</label>
                                <select id="grill-gap" class="form-control">
                                    <option value="">Select Line Gap</option>
                                    <option value="2">2 Inch</option>
                                    <option value="3">3 Inch</option>
                                </select>
                            </div>
                            
                            <button class="btn btn-primary btn-large" onclick="calculateInvisibleGrills()">
                                <i class="fas fa-calculator"></i> Calculate Price
                            </button>
                        </div>
                        
                        <!-- Result Section -->
                        <div class="result-section">
                            <h3>Price Estimate</h3>
                            <div class="result-card" id="grill-result">
                                <div class="result-placeholder">
                                    <i class="fas fa-info-circle"></i>
                                    <p>Enter measurements and click calculate to see price estimate</p>
                                </div>
                            </div>
                            
                            <!-- Price Chart -->
                            <div class="price-chart">
                                <h4>Price Guide (Per SFT)</h4>
                                <table class="price-table">
                                    <thead>
                                        <tr>
                                            <th>Thickness</th>
                                            <th>2 Inch Gap</th>
                                            <th>3 Inch Gap</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>1.5mm</td>
                                            <td>₹130-150/SFT</td>
                                            <td>₹120-140/SFT</td>
                                        </tr>
                                        <tr>
                                            <td>2mm</td>
                                            <td>₹140-160/SFT</td>
                                            <td>₹130-150/SFT</td>
                                        </tr>
                                        <tr>
                                            <td>2.5mm</td>
                                            <td>₹150-170/SFT</td>
                                            <td>₹140-160/SFT</td>
                                        </tr>
                                        <tr>
                                            <td>3mm</td>
                                            <td>₹160-180/SFT</td>
                                            <td>₹150-170/SFT</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Calculator 4: Cloth Hangers -->
        <div class="calculator-panel" id="cloth-hangers">
            <div class="calculator-card">
                <div class="calculator-header">
                    <h2><i class="fas fa-tshirt"></i> Cloth Hangers Estimation</h2>
                    <p>Choose hanger type and length for price estimate</p>
                </div>
                
                <div class="calculator-body">
                    <div class="calculator-grid">
                        <!-- Input Section -->
                        <div class="input-section">
                            <h3>Select Options</h3>
                            
                            <div class="form-group">
                                <label>Hanger Type *</label>
                                <select id="hanger-type" class="form-control">
                                    <option value="">Select Hanger Type</option>
                                    <option value="ceiling">Ceiling Cloth Hangers</option>
                                    <option value="wall">Wall Mounted Cloth Hangers</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Hanger Length *</label>
                                <select id="hanger-length" class="form-control">
                                    <option value="">Select Length</option>
                                    <option value="4">4 Feet</option>
                                    <option value="5">5 Feet</option>
                                    <option value="6">6 Feet</option>
                                    <option value="7">7 Feet</option>
                                    <option value="8">8 Feet</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Quantity</label>
                                <input type="number" id="hanger-quantity" class="form-control" placeholder="Number of hangers" min="1" value="1">
                            </div>
                            
                            <button class="btn btn-primary btn-large" onclick="calculateClothHangers()">
                                <i class="fas fa-calculator"></i> Calculate Price
                            </button>
                        </div>
                        
                        <!-- Result Section -->
                        <div class="result-section">
                            <h3>Price Estimate</h3>
                            <div class="result-card" id="hanger-result">
                                <div class="result-placeholder">
                                    <i class="fas fa-info-circle"></i>
                                    <p>Select options and click calculate to see price estimate</p>
                                </div>
                            </div>
                            
                            <!-- Price Chart -->
                            <div class="price-chart">
                                <h4>Price Guide</h4>
                                <table class="price-table">
                                    <thead>
                                        <tr>
                                            <th>Length</th>
                                            <th>Ceiling Mounted</th>
                                            <th>Wall Mounted</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>4 Feet</td>
                                            <td>₹2,000-2,500</td>
                                            <td>₹2,500-3,000</td>
                                        </tr>
                                        <tr>
                                            <td>5 Feet</td>
                                            <td>₹2,250-2,750</td>
                                            <td>₹2,750-3,250</td>
                                        </tr>
                                        <tr>
                                            <td>6 Feet</td>
                                            <td>₹2,500-3,000</td>
                                            <td>₹3,000-3,500</td>
                                        </tr>
                                        <tr>
                                            <td>7 Feet</td>
                                            <td>₹2,750-3,250</td>
                                            <td>₹3,250-3,750</td>
                                        </tr>
                                        <tr>
                                            <td>8 Feet</td>
                                            <td>₹3,000-3,500</td>
                                            <td>₹3,500-4,000</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contact CTA -->
        <div class="estimation-cta">
            <div class="cta-content">
                <h3>Need Help with Estimation?</h3>
                <p>Our experts are here to help you with accurate measurements and pricing</p>
                <div class="cta-buttons">
                    <a href="tel:<?php echo COMPANY_PHONE; ?>" class="btn btn-primary">
                        <i class="fas fa-phone"></i> Call <?php echo COMPANY_PHONE; ?>
                    </a>
                    <a href="https://wa.me/<?php echo COMPANY_WHATSAPP; ?>" class="btn btn-success" target="_blank">
                        <i class="fab fa-whatsapp"></i> WhatsApp Us
                    </a>
                    <a href="contact.php" class="btn btn-secondary">
                        <i class="fas fa-envelope"></i> Contact Form
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Notes Section -->
        <div class="estimation-notes">
            <h3><i class="fas fa-info-circle"></i> Important Notes</h3>
            <ul>
                <li><i class="fas fa-check"></i> All prices are approximate and include installation charges</li>
                <li><i class="fas fa-check"></i> Final pricing may vary based on site conditions and accessibility</li>
                <li><i class="fas fa-check"></i> Free site visit and accurate measurement available</li>
                <li><i class="fas fa-check"></i> Quality materials with 5-year warranty</li>
                <li><i class="fas fa-check"></i> Discounts available for bulk orders</li>
                <li><i class="fas fa-check"></i> GST applicable as per government norms</li>
            </ul>
        </div>
    </div>
</div>

<?php 
// Include SEO Content for Search Engine Optimization
include 'includes/estimation-seo-content.php';
include 'includes/estimation-seo-faq.php';
?>

<?php
// Inject live rates from admin Rate Management into the calculator
$_gcm_rates_file = __DIR__ . '/config/service-rates.json';
$_gcm_rates_data = file_exists($_gcm_rates_file)
    ? json_decode(file_get_contents($_gcm_rates_file), true)
    : [];
?>
<script>
/* Rates loaded from config/service-rates.json — editable via admin Rate Management */
window.GCM_RATES = <?php echo json_encode($_gcm_rates_data ?: []); ?>;
</script>
<?php include 'includes/modern-footer.php'; ?>
