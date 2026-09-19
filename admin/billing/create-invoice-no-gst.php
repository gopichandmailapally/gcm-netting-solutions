<?php
/**
 * Create Invoice WITHOUT GST
 * Simple billing without tax calculations
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_invoice'])) {
    try {
        $company = $db->fetchOne("SELECT * FROM billing_company_settings WHERE id = 1");
        
        if (!$company) {
            throw new Exception("Company settings not found. Please configure company settings first.");
        }
        
        if (empty($_POST['customer_name']) || empty($_POST['invoice_date']) || empty($_POST['items_json']) || $_POST['items_json'] == '[]') {
            throw new Exception("Please fill all required fields and add at least one item.");
        }
        
        $invoice_number = $company['invoice_prefix'] . str_pad($company['next_invoice_number'], 4, '0', STR_PAD_LEFT);
        
        $result = $db->execute("INSERT INTO billing_invoices (invoice_number, invoice_type, customer_name, customer_phone, customer_email, customer_address, invoice_date, subtotal, grand_total, notes, terms_conditions, bank_details_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$invoice_number, 'invoice_no_gst', $_POST['customer_name'], $_POST['customer_phone'] ?? '', $_POST['customer_email'] ?? '', $_POST['customer_address'] ?? '', $_POST['invoice_date'], $_POST['subtotal'], $_POST['grand_total'], $_POST['notes'] ?? '', $_POST['terms_conditions'] ?? '', $_POST['bank_details_id'] ?? null, 'draft']
        );
        
        if (!$result) {
            throw new Exception("Failed to save invoice.");
        }
        
        $invoice_id = $db->lastInsertId();
        $items = json_decode($_POST['items_json'], true);
        
        foreach ($items as $item) {
            $db->execute("INSERT INTO billing_invoice_items (invoice_id, product_id, product_name, quantity, unit, rate, amount, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$invoice_id, $item['product_id'], $item['product_name'], $item['quantity'], $item['unit'], $item['rate'], $item['amount'], $item['total_amount']]
            );
        }
        
        $db->execute("UPDATE billing_company_settings SET next_invoice_number = next_invoice_number + 1 WHERE id = 1");
        
        $_SESSION['success_message'] = "Invoice #$invoice_number created successfully!";
        header("Location: view-invoice.php?id=$invoice_id");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}

$products = $db->fetchAll("SELECT * FROM billing_products WHERE is_active = 1 ORDER BY product_name");
$bank_details = $db->fetchAll("SELECT * FROM billing_bank_details ORDER BY is_default DESC");
$company = $db->fetchOne("SELECT * FROM billing_company_settings WHERE id = 1");

$page_title = 'Create Invoice (No GST)';
$additional_css = ['../assets/css/billing-forms.css'];
include '../includes/header.php';
?>

<div class="invoice-creator">
        <div class="page-header">
            <h1><i class="fas fa-file-invoice"></i> Create Invoice (No GST)</h1>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span>
            </div>
        <?php endif; ?>
        
        <form id="invoiceForm" method="POST">
            <input type="hidden" name="save_invoice" value="1">
            <input type="hidden" name="items_json" id="itemsJson">
            <input type="hidden" name="subtotal" id="subtotalInput">
            <input type="hidden" name="grand_total" id="grandTotalInput">
            
            <div class="content-card">
                <div class="card-header"><h2>Customer Details</h2></div>
                <div class="card-body">
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
                            <label>Invoice Date *</label>
                            <input type="date" name="invoice_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="customer_address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Bank Account</label>
                        <select name="bank_details_id" class="form-control">
                            <option value="">Select Bank (Optional)</option>
                            <?php foreach ($bank_details as $bank): ?>
                                <option value="<?php echo $bank['id']; ?>"><?php echo $bank['bank_name']; ?> - <?php echo $bank['account_number']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="content-card">
                <div class="card-header">
                    <h2>Invoice Items</h2>
                    <button type="button" class="btn btn-primary btn-sm" onclick="addItem()">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                </div>
                <div class="card-body">
                    <div style="overflow-x: auto;">
                        <table class="data-table" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 250px;">Product</th>
                                    <th style="width: 100px;">Qty</th>
                                    <th style="width: 100px;">Unit</th>
                                    <th style="width: 120px;">Rate</th>
                                    <th style="width: 150px;">Amount</th>
                                    <th style="width: 60px;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody"></tbody>
                        </table>
                    </div>
                    
                    <div class="totals-section">
                        <div class="totals-grid">
                            <div class="total-row grand">
                                <span>Total Amount:</span>
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
                        <textarea name="terms_conditions" class="form-control" rows="2"><?php echo $company['default_terms_conditions'] ?? ''; ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success btn-large">
                    <i class="fas fa-save"></i> Save Invoice
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
</div>

<script>
    const products = <?php echo json_encode($products); ?>;
    
    function addItem() {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <select class="form-control" onchange="selectProduct(this)">
                    <option value="">Select Product</option>
                    ${products.map(p => `<option value="${p.id}" data-unit="${p.unit}" data-rate="${p.default_rate}">${p.product_name}</option>`).join('')}
                </select>
            </td>
            <td><input type="number" class="form-control qty" value="1" min="0.01" step="0.01" onchange="calculateRow(this)"></td>
            <td><input type="text" class="form-control unit" readonly></td>
            <td><input type="number" class="form-control rate" min="0" step="0.01" onchange="calculateRow(this)"></td>
            <td><input type="text" class="form-control amount" readonly></td>
            <td><button type="button" class="btn-icon danger" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
        `;
        document.getElementById('itemsBody').appendChild(row);
    }
    
    function selectProduct(select) {
        const option = select.options[select.selectedIndex];
        const row = select.closest('tr');
        row.querySelector('.unit').value = option.dataset.unit || '';
        row.querySelector('.rate').value = option.dataset.rate || '';
        calculateRow(row);
    }
    
    function calculateRow(element) {
        const row = element.closest ? element.closest('tr') : element;
        const qty = parseFloat(row.querySelector('.qty').value) || 0;
        const rate = parseFloat(row.querySelector('.rate').value) || 0;
        const amount = qty * rate;
        row.querySelector('.amount').value = amount.toFixed(2);
        calculateTotals();
    }
    
    function removeRow(btn) {
        btn.closest('tr').remove();
        calculateTotals();
    }
    
    function calculateTotals() {
        let total = 0;
        document.querySelectorAll('#itemsBody tr').forEach(row => {
            total += parseFloat(row.querySelector('.amount').value) || 0;
        });
        document.getElementById('grandTotalDisplay').textContent = '₹' + total.toFixed(2);
        document.getElementById('amountWords').textContent = 'Amount in Words: ' + numberToWords(total);
        document.getElementById('subtotalInput').value = total.toFixed(2);
        document.getElementById('grandTotalInput').value = total.toFixed(2);
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
    
    document.getElementById('invoiceForm').addEventListener('submit', function(e) {
        const items = [];
        document.querySelectorAll('#itemsBody tr').forEach(row => {
            const select = row.querySelector('select');
            const option = select.options[select.selectedIndex];
            items.push({
                product_id: select.value,
                product_name: option.text,
                quantity: row.querySelector('.qty').value,
                unit: row.querySelector('.unit').value,
                rate: row.querySelector('.rate').value,
                amount: row.querySelector('.amount').value,
                total_amount: row.querySelector('.amount').value
            });
        });
        document.getElementById('itemsJson').value = JSON.stringify(items);
    });
    
    addItem();
</script>

<style>
.invoice-creator { padding: 20px; animation: fadeInUp 0.6s ease; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    animation: slideInDown 0.5s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

@keyframes slideInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.alert-success {
    background: linear-gradient(135deg, #D1FAE5, #A7F3D0);
    color: #065F46;
    border: 2px solid #10B981;
}

.alert-error {
    background: linear-gradient(135deg, #FEE2E2, #FECACA);
    color: #991B1B;
    border: 2px solid #EF4444;
}

.alert i {
    font-size: 20px;
}

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
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
