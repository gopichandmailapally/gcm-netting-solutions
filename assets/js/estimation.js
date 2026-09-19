/**
 * Estimation Calculator JavaScript
 * Handles all calculator logic with exact pricing structure
 */

(function() {
    'use strict';
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initCalculators();
    });
    
    /**
     * Initialize calculator tabs and method toggles
     */
    function initCalculators() {
        // Tab switching
        const tabButtons = document.querySelectorAll('.tab-btn');
        tabButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const calculator = this.dataset.calculator;
                switchCalculator(calculator);
            });
        });
        
        // Input method toggles (Area vs Dimensions)
        const methodButtons = document.querySelectorAll('.method-btn');
        methodButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const method = this.dataset.method;
                const parent = this.closest('.input-section');
                
                // Toggle active class
                parent.querySelectorAll('.method-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Show/hide input areas
                parent.querySelectorAll('.input-area').forEach(area => area.classList.remove('active'));
                parent.querySelector(`#${method}-method, #grill-${method}-method`).classList.add('active');
            });
        });
        
        // Auto-calculate area when dimensions change
        setupDimensionCalculation('safety', 'safety-length', 'safety-height', 'calc-area');
        setupDimensionCalculation('grill', 'grill-length', 'grill-height', 'grill-calc-area');
        setupCricketAreaCalculation();
    }
    
    /**
     * Switch between calculators
     */
    function switchCalculator(calculator) {
        // Update tabs
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelector(`[data-calculator="${calculator}"]`).classList.add('active');
        
        // Update panels
        document.querySelectorAll('.calculator-panel').forEach(panel => panel.classList.remove('active'));
        document.getElementById(calculator).classList.add('active');
    }
    
    /**
     * Setup automatic area calculation from dimensions
     */
    function setupDimensionCalculation(prefix, lengthId, heightId, resultId) {
        const lengthInput = document.getElementById(lengthId);
        const heightInput = document.getElementById(heightId);
        
        if (!lengthInput || !heightInput) return;
        
        const calculate = () => {
            const length = parseFloat(lengthInput.value) || 0;
            const height = parseFloat(heightInput.value) || 0;
            const area = length * height;
            document.getElementById(resultId).textContent = area.toFixed(2);
        };
        
        lengthInput.addEventListener('input', calculate);
        heightInput.addEventListener('input', calculate);
    }
    
    /**
     * Setup cricket net area calculation (L×W + 2×L×H + 2×W×H)
     */
    function setupCricketAreaCalculation() {
        const length = document.getElementById('cricket-length');
        const width = document.getElementById('cricket-width');
        const height = document.getElementById('cricket-height');
        
        if (!length || !width || !height) return;
        
        const calculate = () => {
            const L = parseFloat(length.value) || 0;
            const W = parseFloat(width.value) || 0;
            const H = parseFloat(height.value) || 0;
            
            // Formula: L×W + 2×L×H + 2×W×H
            const area = (L * W) + (2 * L * H) + (2 * W * H);
            document.getElementById('cricket-calc-area').textContent = area.toFixed(2);
        };
        
        length.addEventListener('input', calculate);
        width.addEventListener('input', calculate);
        height.addEventListener('input', calculate);
    }
    
    /**
     * Calculate Safety Nets / Pigeon Nets Price
     */
    window.calculateSafetyNets = function() {
        // Get area (either direct or calculated)
        let area;
        const areaMethod = document.querySelector('#safety-nets .input-area.active');
        
        if (areaMethod.id === 'area-method') {
            area = parseFloat(document.getElementById('safety-area').value);
        } else {
            const length = parseFloat(document.getElementById('safety-length').value) || 0;
            const height = parseFloat(document.getElementById('safety-height').value) || 0;
            area = length * height;
        }
        
        const thickness = parseFloat(document.getElementById('safety-thickness').value);
        const gap = parseFloat(document.getElementById('safety-gap').value);
        
        // Validation
        if (!area || area <= 0) {
            showError('safety-result', 'Please enter valid area/dimensions');
            return;
        }
        if (!thickness) {
            showError('safety-result', 'Please select net thickness');
            return;
        }
        if (!gap) {
            showError('safety-result', 'Please select square gap');
            return;
        }
        
        let minPrice, maxPrice, priceText;

        // Helper: lookup from GCM_RATES with numeric key coercion
        function rLook(section, ...keys) {
            let node = (window.GCM_RATES || {})[section];
            for (const k of keys) {
                if (!node) return null;
                node = node[String(k)];
            }
            return (node && typeof node.min !== 'undefined') ? node : null;
        }

        // Below 100 SFT - Fixed prices
        if (area < 100) {
            const r = rLook('safety_nets', 'below-100', thickness, gap);
            if (r) {
                minPrice = maxPrice = r.min;
                priceText = '\u20B9' + r.min.toLocaleString() + ' With Installation';
            } else if (thickness === 1.5) {
                minPrice = maxPrice = 1500; priceText = '\u20B91,500 With Installation';
            } else if (thickness === 2) {
                minPrice = maxPrice = 1650; priceText = '\u20B91,650 With Installation';
            } else if (thickness === 2.5) {
                minPrice = maxPrice = 1800; priceText = '\u20B91,800 With Installation';
            }
        }
        // 100-250 SFT
        else if (area >= 100 && area < 250) {
            const rates = getSafetyNetRates(thickness, gap, '100-250');
            minPrice = area * rates.min;
            maxPrice = area * rates.max;
            priceText = `₹${rates.min}-${rates.max} per SFT`;
        }
        // 250-500 SFT
        else if (area >= 250 && area < 500) {
            const rates = getSafetyNetRates(thickness, gap, '250-500');
            minPrice = area * rates.min;
            maxPrice = area * rates.max;
            priceText = `₹${rates.min}-${rates.max} per SFT`;
        }
        // 500-1000 SFT
        else if (area >= 500 && area < 1000) {
            const rates = getSafetyNetRates(thickness, gap, '500-1000');
            minPrice = area * rates.min;
            maxPrice = area * rates.max;
            priceText = `₹${rates.min}-${rates.max} per SFT`;
        }
        // 1000-5000 SFT
        else if (area >= 1000 && area < 5000) {
            const rates = getSafetyNetRates(thickness, gap, '1000-5000');
            minPrice = area * rates.min;
            maxPrice = area * rates.max;
            priceText = `₹${rates.min}-${rates.max} per SFT`;
        }
        // Above 5000 SFT
        else {
            showCallForPrice('safety-result', area);
            return;
        }
        
        showResult('safety-result', {
            area: area,
            thickness: thickness + 'mm',
            gap: gap + 'mm',
            rate: priceText,
            minPrice: minPrice,
            maxPrice: maxPrice
        });
    };
    
    /**
     * Get safety net rates — from GCM_RATES (admin-editable) with hardcoded fallback
     */
    function getSafetyNetRates(thickness, gap, range) {
        const R = window.GCM_RATES;
        if (R && R.safety_nets && R.safety_nets[range]) {
            const t = String(thickness), g = String(gap);
            if (R.safety_nets[range][t] && R.safety_nets[range][t][g]) {
                return R.safety_nets[range][t][g];
            }
        }
        // Hardcoded fallback
        const rates = {
            '100-250':  { 1.5: { 30: {min:16,max:20} }, 2: { 40:{min:24,max:28}, 45:{min:22,max:26}, 50:{min:20,max:24} }, 2.5: { 40:{min:26,max:30}, 45:{min:24,max:28}, 50:{min:22,max:24} } },
            '250-500':  { 1.5: { 30: {min:14,max:18} }, 2: { 40:{min:20,max:24}, 45:{min:18,max:22}, 50:{min:16,max:20} }, 2.5: { 40:{min:22,max:26}, 45:{min:20,max:24}, 50:{min:18,max:22} } },
            '500-1000': { 1.5: { 30: {min:12,max:16} }, 2: { 40:{min:18,max:22}, 45:{min:16,max:20}, 50:{min:14,max:18} }, 2.5: { 40:{min:20,max:24}, 45:{min:18,max:22}, 50:{min:16,max:20} } },
            '1000-5000':{ 1.5: { 30: {min:10,max:14} }, 2: { 40:{min:14,max:18}, 45:{min:12,max:16}, 50:{min:10,max:14} }, 2.5: { 40:{min:16,max:20}, 45:{min:14,max:18}, 50:{min:12,max:16} } }
        };
        return rates[range][thickness][gap];
    }
    
    /**
     * Calculate Cricket Nets Price
     */
    window.calculateCricketNets = function() {
        const length = parseFloat(document.getElementById('cricket-length').value) || 0;
        const width = parseFloat(document.getElementById('cricket-width').value) || 0;
        const height = parseFloat(document.getElementById('cricket-height').value) || 0;
        const gap = parseFloat(document.getElementById('cricket-gap').value);
        
        // Validation
        if (!length || !width || !height) {
            showError('cricket-result', 'Please enter all dimensions');
            return;
        }
        if (!gap) {
            showError('cricket-result', 'Please select square gap');
            return;
        }
        
        // Calculate area: L×W + 2×L×H + 2×W×H
        const area = (length * width) + (2 * length * height) + (2 * width * height);
        
        let minPrice, maxPrice, priceText;

        // Range key lookup helper for cricket nets
        function cnRate(rangeKey) {
            const R = window.GCM_RATES;
            if (R && R.cricket_nets && R.cricket_nets[rangeKey] && R.cricket_nets[rangeKey][String(gap)])
                return R.cricket_nets[rangeKey][String(gap)];
            const fb = {
                '1000-5000':   {40:{min:15,max:18},45:{min:14,max:17},50:{min:13,max:16}},
                '5000-10000':  {40:{min:14,max:17},45:{min:13,max:16},50:{min:12,max:15}},
                '10000-15000': {40:{min:12,max:15},45:{min:11,max:14},50:{min:10,max:13}},
                '15000-20000': {40:{min:11,max:14},45:{min:10,max:13},50:{min:9,max:12}},
                'above-20000': {40:{min:10,max:12},45:{min:9,max:11},50:{min:8,max:10}}
            };
            return fb[rangeKey][gap];
        }

        let cnKey = null;
        if      (area >= 1000  && area < 5000)  cnKey = '1000-5000';
        else if (area >= 5000  && area < 10000) cnKey = '5000-10000';
        else if (area >= 10000 && area < 15000) cnKey = '10000-15000';
        else if (area >= 15000 && area < 20000) cnKey = '15000-20000';
        else if (area >= 20000)                 cnKey = 'above-20000';
        else { showError('cricket-result', 'Cricket nets require minimum 1000 SFT area. Please check dimensions.'); return; }

        const cnR = cnRate(cnKey);
        minPrice = area * cnR.min;
        maxPrice = area * cnR.max;
        priceText = `\u20B9${cnR.min}-${cnR.max} per SFT`;
        
        showResult('cricket-result', {
            area: area,
            dimensions: `${length}ft × ${width}ft × ${height}ft`,
            gap: gap + 'mm',
            rate: priceText,
            minPrice: minPrice,
            maxPrice: maxPrice,
            formula: 'L×W + 2×L×H + 2×W×H'
        });
    };
    
    /**
     * Calculate Invisible Grills Price
     */
    window.calculateInvisibleGrills = function() {
        // Get area (either direct or calculated)
        let area;
        const areaMethod = document.querySelector('#invisible-grills .input-area.active');
        
        if (areaMethod.id === 'grill-area-method') {
            area = parseFloat(document.getElementById('grill-area').value);
        } else {
            const length = parseFloat(document.getElementById('grill-length').value) || 0;
            const height = parseFloat(document.getElementById('grill-height').value) || 0;
            area = length * height;
        }
        
        const thickness = parseFloat(document.getElementById('grill-thickness').value);
        const gap = parseFloat(document.getElementById('grill-gap').value);
        
        // Validation
        if (!area || area <= 0) {
            showError('grill-result', 'Please enter valid area/dimensions');
            return;
        }
        if (!thickness) {
            showError('grill-result', 'Please select SS wire thickness');
            return;
        }
        if (!gap) {
            showError('grill-result', 'Please select line gap');
            return;
        }
        
        // Rates from GCM_RATES with fallback
        const R_ig = window.GCM_RATES;
        let rate;
        if (R_ig && R_ig.invisible_grills && R_ig.invisible_grills[String(thickness)] && R_ig.invisible_grills[String(thickness)][String(gap)]) {
            rate = R_ig.invisible_grills[String(thickness)][String(gap)];
        } else {
            const rates = {
                1.5:{2:{min:130,max:150},3:{min:120,max:140}},
                2:  {2:{min:140,max:160},3:{min:130,max:150}},
                2.5:{2:{min:150,max:170},3:{min:140,max:160}},
                3:  {2:{min:160,max:180},3:{min:150,max:170}}
            };
            rate = rates[thickness][gap];
        }
        const minPrice = area * rate.min;
        const maxPrice = area * rate.max;
        const priceText = `₹${rate.min}-${rate.max} per SFT`;
        
        showResult('grill-result', {
            area: area,
            thickness: thickness + 'mm SS Wire',
            gap: gap + ' Inch',
            rate: priceText,
            minPrice: minPrice,
            maxPrice: maxPrice
        });
    };
    
    /**
     * Calculate Cloth Hangers Price
     */
    window.calculateClothHangers = function() {
        const type = document.getElementById('hanger-type').value;
        const length = parseFloat(document.getElementById('hanger-length').value);
        const quantity = parseInt(document.getElementById('hanger-quantity').value) || 1;
        
        // Validation
        if (!type) {
            showError('hanger-result', 'Please select hanger type');
            return;
        }
        if (!length) {
            showError('hanger-result', 'Please select hanger length');
            return;
        }
        
        // Rates from GCM_RATES with fallback
        const R_ch = window.GCM_RATES;
        let rate;
        if (R_ch && R_ch.cloth_hangers && R_ch.cloth_hangers[type] && R_ch.cloth_hangers[type][String(length)]) {
            rate = R_ch.cloth_hangers[type][String(length)];
        } else {
            const rates = {
                ceiling:{4:{min:2000,max:2500},5:{min:2250,max:2750},6:{min:2500,max:3000},7:{min:2750,max:3250},8:{min:3000,max:3500}},
                wall:   {4:{min:2500,max:3000},5:{min:2750,max:3250},6:{min:3000,max:3500},7:{min:3250,max:3750},8:{min:3500,max:4000}}
            };
            rate = rates[type][length];
        }
        const minPrice = rate.min * quantity;
        const maxPrice = rate.max * quantity;
        const priceText = `₹${rate.min}-${rate.max} per hanger`;
        
        showResult('hanger-result', {
            type: type === 'ceiling' ? 'Ceiling Mounted' : 'Wall Mounted',
            length: length + ' Feet',
            quantity: quantity,
            rate: priceText,
            minPrice: minPrice,
            maxPrice: maxPrice
        });
    };
    
    /**
     * Show calculation result
     */
    function showResult(resultId, data) {
        const resultDiv = document.getElementById(resultId);
        
        let html = `
            <div class="result-success">
                <div class="result-header">
                    <i class="fas fa-check-circle"></i>
                    <h4>Estimated Price</h4>
                </div>
                <div class="result-price">
                    <div class="price-range">
                        <span class="price-label">Price Range:</span>
                        <span class="price-value">₹${formatNumber(data.minPrice)} - ₹${formatNumber(data.maxPrice)}</span>
                    </div>
                </div>
                <div class="result-details">
        `;
        
        if (data.area) html += `<div class="detail-item"><strong>Area:</strong> ${data.area.toFixed(2)} SFT</div>`;
        if (data.dimensions) html += `<div class="detail-item"><strong>Dimensions:</strong> ${data.dimensions}</div>`;
        if (data.thickness) html += `<div class="detail-item"><strong>Thickness:</strong> ${data.thickness}</div>`;
        if (data.gap) html += `<div class="detail-item"><strong>Gap:</strong> ${data.gap}</div>`;
        if (data.type) html += `<div class="detail-item"><strong>Type:</strong> ${data.type}</div>`;
        if (data.length) html += `<div class="detail-item"><strong>Length:</strong> ${data.length}</div>`;
        if (data.quantity) html += `<div class="detail-item"><strong>Quantity:</strong> ${data.quantity}</div>`;
        if (data.rate) html += `<div class="detail-item"><strong>Rate:</strong> ${data.rate}</div>`;
        if (data.formula) html += `<div class="detail-item"><strong>Formula:</strong> ${data.formula}</div>`;
        
        html += `
                </div>
                <div class="result-note">
                    <i class="fas fa-info-circle"></i>
                    <p>Prices include installation. Final cost may vary based on site conditions.</p>
                </div>
                <div class="result-actions">
                    <a href="tel:${COMPANY_PHONE || '9912399224'}" class="btn btn-primary">
                        <i class="fas fa-phone"></i> Call for Confirmation
                    </a>
                    <a href="contact.php" class="btn btn-secondary">
                        <i class="fas fa-envelope"></i> Request Quote
                    </a>
                </div>
            </div>
        `;
        
        resultDiv.innerHTML = html;
        resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    /**
     * Show error message
     */
    function showError(resultId, message) {
        const resultDiv = document.getElementById(resultId);
        resultDiv.innerHTML = `
            <div class="result-error">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${message}</p>
            </div>
        `;
    }
    
    /**
     * Show call for price message
     */
    function showCallForPrice(resultId, area) {
        const resultDiv = document.getElementById(resultId);
        resultDiv.innerHTML = `
            <div class="result-call">
                <i class="fas fa-phone-volume"></i>
                <h4>Call for Best Price</h4>
                <p>For projects above 5,000 SFT (Your area: ${area.toFixed(2)} SFT)</p>
                <p>Contact us for special bulk pricing and discounts</p>
                <div class="result-actions">
                    <a href="tel:${COMPANY_PHONE || '9912399224'}" class="btn btn-primary btn-large">
                        <i class="fas fa-phone"></i> Call Now
                    </a>
                    <a href="https://wa.me/${COMPANY_WHATSAPP || '919912399224'}" class="btn btn-success btn-large" target="_blank">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                </div>
            </div>
        `;
    }
    
    /**
     * Format number with commas
     */
    function formatNumber(num) {
        return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
})();

// Make COMPANY constants available
const COMPANY_PHONE = '9912399224';
const COMPANY_WHATSAPP = '919912399224';
