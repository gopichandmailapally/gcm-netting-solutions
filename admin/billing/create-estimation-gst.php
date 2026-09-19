<?php
define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Create GST Estimation';
$additional_css = ['../assets/css/billing-forms.css'];
include '../includes/header.php';

$db = Database::getInstance();
$products = $db->fetchAll("SELECT * FROM billing_products WHERE is_active = 1 ORDER BY product_name");
$bank_details = $db->fetchAll("SELECT * FROM billing_bank_details ORDER BY is_default DESC");
$companies = $db->fetchAll("SELECT * FROM billing_company_settings ORDER BY id ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_estimation'])) {
    $company = $db->fetchOne("SELECT * FROM billing_company_settings WHERE id = ?", [$_POST['company_id']]);
    $estimation_number = $company['estimation_prefix'] . str_pad($company['next_estimation_number'], 4, '0', STR_PAD_LEFT);
    
    $db->execute("INSERT INTO billing_invoices (invoice_number, invoice_type, customer_name, customer_phone, customer_email, customer_address, customer_gstin, invoice_date, subtotal, cgst_amount, sgst_amount, total_gst, grand_total, notes, terms_conditions, bank_details_id, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [$estimation_number, 'estimation_gst', $_POST['customer_name'], $_POST['customer_phone'], $_POST['customer_email'], $_POST['customer_address'], $_POST['customer_gstin'], $_POST['invoice_date'], $_POST['subtotal'], $_POST['cgst_amount'], $_POST['sgst_amount'], $_POST['total_gst'], $_POST['grand_total'], $_POST['notes'], $_POST['terms_conditions'], $_POST['bank_details_id'], 'draft', $_POST['company_id']]
    );
    
    $invoice_id = $db->lastInsertId();
    
    $items = json_decode($_POST['items_json'], true);
    foreach ($items as $item) {
        $db->execute("INSERT INTO billing_invoice_items (invoice_id, product_id, product_name, hsn_code, quantity, unit, rate, amount, gst_percentage, cgst_amount, sgst_amount, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$invoice_id, $item['product_id'], $item['product_name'], $item['hsn_code'], $item['quantity'], $item['unit'], $item['rate'], $item['amount'], $item['gst_percentage'], $item['cgst_amount'], $item['sgst_amount'], $item['total_amount']]
        );
    }
    
    $db->execute("UPDATE billing_company_settings SET next_estimation_number = next_estimation_number + 1 WHERE id = ?", [$_POST['company_id']]);
    
    $_SESSION['success_message'] = "Estimation created successfully!";
    header("Location: view-invoice.php?id=$invoice_id");
    exit;
}
?>

<div class="invoice-creator">
    <div class="page-header">
        <h1><i class="fas fa-percent"></i> Create Estimation with GST</h1>
        <button class="btn btn-secondary" onclick="window.location.href='index.php'">
            <i class="fas fa-arrow-left"></i> Back
        </button>
    </div>
    
    <form id="estimationForm" method="POST">
        <input type="hidden" name="save_estimation" value="1">
        <input type="hidden" name="items_json" id="itemsJson">
        <input type="hidden" name="subtotal" id="subtotalInput">
        <input type="hidden" name="cgst_amount" id="cgstInput">
        <input type="hidden" name="sgst_amount" id="sgstInput">
        <input type="hidden" name="total_gst" id="totalGstInput">
        <input type="hidden" name="grand_total" id="grandTotalInput">
        
        <div class="content-card">
            <div class="card-header"><h2>Company & Customer Details</h2></div>
            <div class="card-body">
                <div class="form-group">
                    <label>Select Company *</label>
                    <select name="company_id" id="companyId" class="form-control" required onchange="updateCompanyPreview()">
                        <option value="">Choose Company</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($company['company_name']); ?>"
                                    data-logo="<?php echo $company['company_logo_path']; ?>"
                                    data-address="<?php echo htmlspecialchars($company['company_address']); ?>"
                                    data-phone="<?php echo htmlspecialchars($company['company_phone']); ?>"
                                    data-gstin="<?php echo htmlspecialchars($company['company_gstin']); ?>"
                                    data-terms="<?php echo htmlspecialchars($company['default_terms_conditions']); ?>">
                                <?php echo htmlspecialchars($company['company_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="companyPreview" style="display:none; margin: 20px 0; padding: 20px; background: linear-gradient(135deg, #F0F9FF, #E0F2FE); border-radius: 12px; border: 2px solid #3B82F6;">
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <div id="companyLogoPreview"></div>
                        <div>
                            <h3 id="companyNamePreview" style="margin: 0 0 8px 0; color: #1E293B;"></h3>
                            <p id="companyAddressPreview" style="margin: 0; color: #475569; font-size: 14px;"></p>
                            <p id="companyPhonePreview" style="margin: 4px 0 0 0; color: #475569; font-size: 14px;"></p>
                            <p id="companyGstinPreview" style="margin: 4px 0 0 0; color: #475569; font-size: 14px; font-weight: 600;"></p>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Customer Name *</label>
                        <input type="text" name="customer_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="customer_phone" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="customer_email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Customer GSTIN</label>
                        <input type="text" name="customer_gstin" class="form-control" placeholder="22AAAAA0000A1Z5">
                    </div>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="customer_address" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Estimation Date *</label>
                        <input type="date" name="invoice_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Bank Account *</label>
                        <select name="bank_details_id" class="form-control" required>
                            <option value="">Select Bank</option>
                            <?php foreach ($bank_details as $bank): ?>
                                <option value="<?php echo $bank['id']; ?>"><?php echo $bank['bank_name']; ?> - <?php echo $bank['account_number']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content-card">
            <div class="card-header">
                <h2>Estimation Items</h2>
                <button type="button" class="btn btn-primary btn-sm" onclick="addItem()">
                    <i class="fas fa-plus"></i> Add Item
                </button>
            </div>
            <div class="card-body">
                <div class="items-table">
                    <table class="data-table" id="itemsTable">
                        <thead>
                            <tr>
                                <th style="width: 200px;">Product</th>
                                <th style="width: 100px;">HSN</th>
                                <th style="width: 80px;">GST %</th>
                                <th style="width: 80px;">Qty</th>
                                <th style="width: 80px;">Unit</th>
                                <th style="width: 100px;">Rate</th>
                                <th style="width: 100px;">Amount</th>
                                <th style="width: 100px;">CGST</th>
                                <th style="width: 100px;">SGST</th>
                                <th style="width: 120px;">Total</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody"></tbody>
                    </table>
                </div>
                
                <div class="totals-section">
                    <div class="totals-grid">
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <strong id="subtotalDisplay">₹0.00</strong>
                        </div>
                        <div class="total-row">
                            <span>CGST:</span>
                            <strong id="cgstDisplay">₹0.00</strong>
                        </div>
                        <div class="total-row">
                            <span>SGST:</span>
                            <strong id="sgstDisplay">₹0.00</strong>
                        </div>
                        <div class="total-row grand">
                            <span>Grand Total:</span>
                            <strong id="grandTotalDisplay">₹0.00</strong>
                        </div>
                        <div class="amount-words" id="amountWords"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content-card">
            <div class="card-body">
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Terms & Conditions</label>
                    <textarea name="terms_conditions" id="termsConditions" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success btn-large">
                <i class="fas fa-save"></i> Save Estimation
            </button>
            <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">Cancel</button>
        </div>
    </form>
</div>

<script>
const products = <?php echo json_encode($products); ?>;

function updateCompanyPreview() {
    const select = document.getElementById('companyId');
    const option = select.options[select.selectedIndex];
    const preview = document.getElementById('companyPreview');
    
    if (select.value) {
        preview.style.display = 'block';
        
        const logo = option.dataset.logo;
        if (logo) {
            document.getElementById('companyLogoPreview').innerHTML = '<img src="<?php echo SITE_URL; ?>/' + logo + '" style="max-width: 150px; max-height: 60px;">';
        } else {
            document.getElementById('companyLogoPreview').innerHTML = '<div style="width: 60px; height: 60px; background: linear-gradient(135deg, #3B82F6, #8B5CF6); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;"><i class="fas fa-building"></i></div>';
        }
        
        document.getElementById('companyNamePreview').textContent = option.dataset.name;
        document.getElementById('companyAddressPreview').textContent = option.dataset.address || '';
        document.getElementById('companyPhonePreview').textContent = option.dataset.phone ? 'Phone: ' + option.dataset.phone : '';
        document.getElementById('companyGstinPreview').textContent = option.dataset.gstin ? 'GSTIN: ' + option.dataset.gstin : '';
        document.getElementById('termsConditions').value = option.dataset.terms || '';
    } else {
        preview.style.display = 'none';
    }
}

function addItem() {
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <select class="form-control" onchange="selectProduct(this)">
                <option value="">Select Product</option>
                ${products.map(p => `<option value="${p.id}" data-hsn="${p.hsn_code}" data-gst="${p.gst_percentage}" data-unit="${p.unit}" data-rate="${p.default_rate}">${p.product_name}</option>`).join('')}
            </select>
        </td>
        <td><input type="text" class="form-control hsn" readonly></td>
        <td><input type="text" class="form-control gst" readonly></td>
        <td><input type="number" class="form-control qty" value="1" min="0.01" step="0.01" onchange="calculateRow(this)"></td>
        <td><input type="text" class="form-control unit" readonly></td>
        <td><input type="number" class="form-control rate" min="0" step="0.01" onchange="calculateRow(this)"></td>
        <td><input type="text" class="form-control amount" readonly></td>
        <td><input type="text" class="form-control cgst" readonly></td>
        <td><input type="text" class="form-control sgst" readonly></td>
        <td><input type="text" class="form-control total" readonly></td>
        <td><button type="button" class="btn-icon danger" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
    `;
    document.getElementById('itemsBody').appendChild(row);
}

function selectProduct(select) {
    const option = select.options[select.selectedIndex];
    const row = select.closest('tr');
    row.querySelector('.hsn').value = option.dataset.hsn || '';
    row.querySelector('.gst').value = option.dataset.gst || '';
    row.querySelector('.unit').value = option.dataset.unit || '';
    row.querySelector('.rate').value = option.dataset.rate || '';
    calculateRow(row);
}

function calculateRow(element) {
    const row = element.closest ? element.closest('tr') : element;
    const qty = parseFloat(row.querySelector('.qty').value) || 0;
    const rate = parseFloat(row.querySelector('.rate').value) || 0;
    const gstPer = parseFloat(row.querySelector('.gst').value) || 0;
    
    const amount = qty * rate;
    const cgst = (amount * gstPer) / 200;
    const sgst = (amount * gstPer) / 200;
    const total = amount + cgst + sgst;
    
    row.querySelector('.amount').value = amount.toFixed(2);
    row.querySelector('.cgst').value = cgst.toFixed(2);
    row.querySelector('.sgst').value = sgst.toFixed(2);
    row.querySelector('.total').value = total.toFixed(2);
    
    calculateTotals();
}

function removeRow(btn) {
    btn.closest('tr').remove();
    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0, totalCgst = 0, totalSgst = 0;
    
    document.querySelectorAll('#itemsBody tr').forEach(row => {
        subtotal += parseFloat(row.querySelector('.amount').value) || 0;
        totalCgst += parseFloat(row.querySelector('.cgst').value) || 0;
        totalSgst += parseFloat(row.querySelector('.sgst').value) || 0;
    });
    
    const grandTotal = subtotal + totalCgst + totalSgst;
    
    document.getElementById('subtotalDisplay').textContent = '₹' + subtotal.toFixed(2);
    document.getElementById('cgstDisplay').textContent = '₹' + totalCgst.toFixed(2);
    document.getElementById('sgstDisplay').textContent = '₹' + totalSgst.toFixed(2);
    document.getElementById('grandTotalDisplay').textContent = '₹' + grandTotal.toFixed(2);
    document.getElementById('amountWords').textContent = 'Amount in Words: ' + numberToWords(grandTotal);
    
    document.getElementById('subtotalInput').value = subtotal.toFixed(2);
    document.getElementById('cgstInput').value = totalCgst.toFixed(2);
    document.getElementById('sgstInput').value = totalSgst.toFixed(2);
    document.getElementById('totalGstInput').value = (totalCgst + totalSgst).toFixed(2);
    document.getElementById('grandTotalInput').value = grandTotal.toFixed(2);
}

function numberToWords(num) {
    const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
    const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    const teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    
    if (num === 0) return 'Zero Rupees Only';
    
    const crore = Math.floor(num / 10000000);
    const lakh = Math.floor((num % 10000000) / 100000);
    const thousand = Math.floor((num % 100000) / 1000);
    const hundred = Math.floor((num % 1000) / 100);
    const remainder = Math.floor(num % 100);
    
    let words = '';
    if (crore > 0) words += ones[crore] + ' Crore ';
    if (lakh > 0) words += (lakh < 10 ? ones[lakh] : tens[Math.floor(lakh/10)] + ' ' + ones[lakh%10]) + ' Lakh ';
    if (thousand > 0) words += (thousand < 10 ? ones[thousand] : tens[Math.floor(thousand/10)] + ' ' + ones[thousand%10]) + ' Thousand ';
    if (hundred > 0) words += ones[hundred] + ' Hundred ';
    if (remainder >= 20) words += tens[Math.floor(remainder/10)] + ' ' + ones[remainder%10];
    else if (remainder >= 10) words += teens[remainder-10];
    else if (remainder > 0) words += ones[remainder];
    
    return words.trim() + ' Rupees Only';
}

document.getElementById('estimationForm').addEventListener('submit', function(e) {
    const items = [];
    document.querySelectorAll('#itemsBody tr').forEach(row => {
        const select = row.querySelector('select');
        const option = select.options[select.selectedIndex];
        items.push({
            product_id: select.value,
            product_name: option.text,
            hsn_code: row.querySelector('.hsn').value,
            gst_percentage: row.querySelector('.gst').value,
            quantity: row.querySelector('.qty').value,
            unit: row.querySelector('.unit').value,
            rate: row.querySelector('.rate').value,
            amount: row.querySelector('.amount').value,
            cgst_amount: row.querySelector('.cgst').value,
            sgst_amount: row.querySelector('.sgst').value,
            total_amount: row.querySelector('.total').value
        });
    });
    document.getElementById('itemsJson').value = JSON.stringify(items);
});

addItem();
</script>

<style>
.invoice-creator { padding: 20px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.items-table { overflow-x: auto; }
.totals-section { margin-top: 20px; padding: 20px; background: linear-gradient(135deg, #F0F9FF, #E0F2FE); border-radius: 12px; }
.totals-grid { max-width: 400px; margin-left: auto; }
.total-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 16px; }
.total-row.grand { border-top: 2px solid #3B82F6; padding-top: 12px; font-size: 20px; color: #1E293B; }
.amount-words { margin-top: 12px; padding: 12px; background: white; border-radius: 8px; font-weight: 600; color: #3B82F6; text-align: center; }
.form-actions { margin-top: 20px; display: flex; gap: 12px; }
.btn-large { padding: 14px 28px; font-size: 16px; }
@media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
</style>

<?php include '../includes/footer.php'; ?>
