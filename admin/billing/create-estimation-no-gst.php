<?php
/**
 * Create Estimation WITHOUT GST
 * Quote/Estimate for customers
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_estimation'])) {
    try {
        $company = $db->fetchOne("SELECT * FROM billing_company_settings WHERE id = 1");
        
        if (!$company) {
            throw new Exception("Company settings not found.");
        }
        
        if (empty($_POST['customer_name']) || empty($_POST['invoice_date']) || empty($_POST['items_json']) || $_POST['items_json'] == '[]') {
            throw new Exception("Please fill all required fields and add at least one item.");
        }
        
        $estimation_number = $company['estimation_prefix'] . str_pad($company['next_estimation_number'], 4, '0', STR_PAD_LEFT);
        
        $result = $db->execute("INSERT INTO billing_invoices (invoice_number, invoice_type, customer_name, customer_phone, customer_email, customer_address, invoice_date, subtotal, grand_total, notes, terms_conditions, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$estimation_number, 'estimation_no_gst', $_POST['customer_name'], $_POST['customer_phone'] ?? '', $_POST['customer_email'] ?? '', $_POST['customer_address'] ?? '', $_POST['invoice_date'], $_POST['subtotal'], $_POST['grand_total'], $_POST['notes'] ?? '', $_POST['terms_conditions'] ?? '', 'draft']
        );
        
        if (!$result) {
            throw new Exception("Failed to save estimation.");
        }
        
        $estimation_id = $db->lastInsertId();
        $items = json_decode($_POST['items_json'], true);
        
        foreach ($items as $item) {
            $db->execute("INSERT INTO billing_invoice_items (invoice_id, product_id, product_name, quantity, unit, rate, amount, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$estimation_id, $item['product_id'], $item['product_name'], $item['quantity'], $item['unit'], $item['rate'], $item['amount'], $item['total_amount']]
            );
        }
        
        $db->execute("UPDATE billing_company_settings SET next_estimation_number = next_estimation_number + 1 WHERE id = 1");
        
        $_SESSION['success_message'] = "Estimation #$estimation_number created successfully!";
        header("Location: view-invoice.php?id=$estimation_id");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}

$products = $db->fetchAll("SELECT * FROM billing_products WHERE is_active = 1 ORDER BY product_name");
$company = $db->fetchOne("SELECT * FROM billing_company_settings WHERE id = 1");

$page_title = 'Create Estimation (No GST)';
$additional_css = ['../assets/css/billing-forms.css'];
include '../includes/header.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-panel.css">
</head>
<body>
    <div style="padding: 20px; max-width: 1200px; margin: 0 auto;" class="fade-in-up">
        <div class="page-header">
            <h1><i class="fas fa-file-alt"></i> Create Estimation (No GST)</h1>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span>
            </div>
        <?php endif; ?>
        
        <form id="estimationForm" method="POST">
            <input type="hidden" name="save_estimation" value="1">
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
                            <label>Estimation Date *</label>
                            <input type="date" name="invoice_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="customer_address" class="form-control" rows="2"></textarea>
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
                                <span>Estimated Total:</span>
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
                        <textarea name="notes" class="form-control" rows="2" placeholder="Valid for 30 days"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Terms & Conditions</label>
                        <textarea name="terms_conditions" class="form-control" rows="2"><?php echo $company['default_terms_conditions'] ?? ''; ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success btn-large">
                    <i class="fas fa-save"></i> Save Estimation
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
    
    document.getElementById('estimationForm').addEventListener('submit', function(e) {
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
</body>
</html>
