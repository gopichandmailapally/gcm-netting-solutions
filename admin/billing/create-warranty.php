<?php
define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Create Warranty Card';
$additional_css = ['../assets/css/billing-forms.css'];
include '../includes/header.php';

$db = Database::getInstance();
$products = $db->fetchAll("SELECT * FROM billing_products WHERE is_active = 1 ORDER BY product_name");
$companies = $db->fetchAll("SELECT * FROM billing_company_settings ORDER BY id ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_warranty'])) {
    $company = $db->fetchOne("SELECT * FROM billing_company_settings WHERE id = ?", [$_POST['company_id']]);
    $warranty_number = $company['warranty_prefix'] . str_pad($company['next_warranty_number'], 4, '0', STR_PAD_LEFT);
    
    $db->execute("INSERT INTO billing_invoices (invoice_number, invoice_type, customer_name, customer_phone, customer_email, customer_address, invoice_date, warranty_period, warranty_terms, notes, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [$warranty_number, 'warranty', $_POST['customer_name'], $_POST['customer_phone'], $_POST['customer_email'], $_POST['customer_address'], $_POST['invoice_date'], $_POST['warranty_period'], $_POST['warranty_terms'], $_POST['notes'], 'draft', $_POST['company_id']]
    );
    
    $invoice_id = $db->lastInsertId();
    
    $items = json_decode($_POST['items_json'], true);
    foreach ($items as $item) {
        $db->execute("INSERT INTO billing_invoice_items (invoice_id, product_id, product_name, description, quantity, unit) VALUES (?, ?, ?, ?, ?, ?)",
            [$invoice_id, $item['product_id'], $item['product_name'], $item['description'], $item['quantity'], $item['unit']]
        );
    }
    
    $db->execute("UPDATE billing_company_settings SET next_warranty_number = next_warranty_number + 1 WHERE id = ?", [$_POST['company_id']]);
    
    $_SESSION['success_message'] = "Warranty card created successfully!";
    header("Location: view-invoice.php?id=$invoice_id");
    exit;
}
?>

<div class="invoice-creator">
    <div class="page-header">
        <h1><i class="fas fa-certificate"></i> Create Warranty Card</h1>
        <button class="btn btn-secondary" onclick="window.location.href='index.php'">
            <i class="fas fa-arrow-left"></i> Back
        </button>
    </div>
    
    <form id="warrantyForm" method="POST">
        <input type="hidden" name="save_warranty" value="1">
        <input type="hidden" name="items_json" id="itemsJson">
        
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
                                    data-warranty="<?php echo htmlspecialchars($company['default_warranty_terms']); ?>">
                                <?php echo htmlspecialchars($company['company_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="companyPreview" style="display:none; margin: 20px 0; padding: 20px; background: linear-gradient(135deg, #FBCFE8, #F9A8D4); border-radius: 12px; border: 2px solid #EC4899;">
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <div id="companyLogoPreview"></div>
                        <div>
                            <h3 id="companyNamePreview" style="margin: 0 0 8px 0; color: #1E293B;"></h3>
                            <p id="companyAddressPreview" style="margin: 0; color: #475569; font-size: 14px;"></p>
                            <p id="companyPhonePreview" style="margin: 4px 0 0 0; color: #475569; font-size: 14px;"></p>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Customer Name *</label>
                        <input type="text" name="customer_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Phone *</label>
                        <input type="text" name="customer_phone" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="customer_email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Issue Date *</label>
                        <input type="date" name="invoice_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Address *</label>
                    <textarea name="customer_address" class="form-control" rows="2" required></textarea>
                </div>
                <div class="form-group">
                    <label>Warranty Period *</label>
                    <select name="warranty_period" class="form-control" required>
                        <option value="6 Months">6 Months</option>
                        <option value="1 Year" selected>1 Year</option>
                        <option value="2 Years">2 Years</option>
                        <option value="3 Years">3 Years</option>
                        <option value="5 Years">5 Years</option>
                        <option value="10 Years">10 Years</option>
                        <option value="Lifetime">Lifetime</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="content-card">
            <div class="card-header">
                <h2>Products Covered</h2>
                <button type="button" class="btn btn-primary btn-sm" onclick="addItem()">
                    <i class="fas fa-plus"></i> Add Product
                </button>
            </div>
            <div class="card-body">
                <div class="items-table">
                    <table class="data-table" id="itemsTable">
                        <thead>
                            <tr>
                                <th style="width: 250px;">Product</th>
                                <th style="width: 300px;">Description/Serial No.</th>
                                <th style="width: 100px;">Quantity</th>
                                <th style="width: 100px;">Unit</th>
                                <th style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="content-card">
            <div class="card-body">
                <div class="form-group">
                    <label>Warranty Terms & Conditions *</label>
                    <textarea name="warranty_terms" id="warrantyTerms" class="form-control" rows="5" required></textarea>
                    <small>Coverage details, exclusions, claim process, etc.</small>
                </div>
                <div class="form-group">
                    <label>Additional Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Installation date, special conditions, etc."></textarea>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success btn-large">
                <i class="fas fa-save"></i> Generate Warranty Card
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
            document.getElementById('companyLogoPreview').innerHTML = '<div style="width: 60px; height: 60px; background: linear-gradient(135deg, #EC4899, #DB2777); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;"><i class="fas fa-building"></i></div>';
        }
        
        document.getElementById('companyNamePreview').textContent = option.dataset.name;
        document.getElementById('companyAddressPreview').textContent = option.dataset.address || '';
        document.getElementById('companyPhonePreview').textContent = option.dataset.phone ? 'Phone: ' + option.dataset.phone : '';
        document.getElementById('warrantyTerms').value = option.dataset.warranty || '';
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
                ${products.map(p => `<option value="${p.id}" data-unit="${p.unit}">${p.product_name}</option>`).join('')}
            </select>
        </td>
        <td><input type="text" class="form-control description" placeholder="Serial number, color, size, etc."></td>
        <td><input type="number" class="form-control qty" value="1" min="1" step="1"></td>
        <td><input type="text" class="form-control unit" readonly></td>
        <td><button type="button" class="btn-icon danger" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
    `;
    document.getElementById('itemsBody').appendChild(row);
}

function selectProduct(select) {
    const option = select.options[select.selectedIndex];
    const row = select.closest('tr');
    row.querySelector('.unit').value = option.dataset.unit || '';
}

function removeRow(btn) {
    btn.closest('tr').remove();
}

document.getElementById('warrantyForm').addEventListener('submit', function(e) {
    const items = [];
    document.querySelectorAll('#itemsBody tr').forEach(row => {
        const select = row.querySelector('select');
        const option = select.options[select.selectedIndex];
        items.push({
            product_id: select.value,
            product_name: option.text,
            description: row.querySelector('.description').value,
            quantity: row.querySelector('.qty').value,
            unit: row.querySelector('.unit').value
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
.form-actions { margin-top: 20px; display: flex; gap: 12px; }
.btn-large { padding: 14px 28px; font-size: 16px; }
@media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
</style>

<?php include '../includes/footer.php'; ?>
