<?php
/**
 * Public Invoice View - No Login Required
 * Accessible via secure token for WhatsApp/Email sharing
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

$db = Database::getInstance();

// Get invoice by share token
$share_token = $_GET['token'] ?? '';

if (empty($share_token)) {
    die('Invalid access. Please use the link provided in your email or WhatsApp message.');
}

// Fetch invoice by share token
$invoice = $db->fetchOne("SELECT * FROM billing_invoices WHERE share_token = ?", [$share_token]);

if (!$invoice) {
    die('Invoice not found or link has expired.');
}

$invoice_id = $invoice['id'];
$items = $db->fetchAll("SELECT * FROM billing_invoice_items WHERE invoice_id = ?", [$invoice_id]);
$company = $db->fetchOne("SELECT * FROM billing_company_settings WHERE id = 1");
$bank = $db->fetchOne("SELECT * FROM billing_bank_details WHERE id = ?", [$invoice['bank_details_id']]);

// Function to convert number to words (Indian format)
function numberToWords($number) {
    $words = array(
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
        16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty',
        30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
        80 => 'Eighty', 90 => 'Ninety'
    );
    
    if ($number == 0) return 'Zero';
    
    $crore = floor($number / 10000000);
    $number -= $crore * 10000000;
    $lakh = floor($number / 100000);
    $number -= $lakh * 100000;
    $thousand = floor($number / 1000);
    $number -= $thousand * 1000;
    $hundred = floor($number / 100);
    $number -= $hundred * 100;
    
    $result = '';
    if ($crore) $result .= numberToWords($crore) . ' Crore ';
    if ($lakh) $result .= numberToWords($lakh) . ' Lakh ';
    if ($thousand) $result .= numberToWords($thousand) . ' Thousand ';
    if ($hundred) $result .= $words[$hundred] . ' Hundred ';
    
    if ($number > 0) {
        if ($number < 20) {
            $result .= $words[$number];
        } else {
            $result .= $words[floor($number / 10) * 10];
            if ($number % 10) $result .= ' ' . $words[$number % 10];
        }
    }
    
    return trim($result);
}

$amount_in_words = 'Rupees ' . numberToWords(floor($invoice['grand_total'])) . ' Only';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo $invoice['invoice_number']; ?> - <?php echo htmlspecialchars($company['company_name']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 10px;
            font-size: 11px;
        }
        
        .invoice-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #3B82F6;
        }
        
        .company-info { flex: 1; }
        .company-logo { max-width: 120px; max-height: 60px; }
        .company-name { font-size: 18px; font-weight: bold; color: #1E293B; margin: 5px 0; }
        .company-details { font-size: 10px; color: #64748B; line-height: 1.5; }
        
        .invoice-title {
            text-align: right;
            flex: 1;
        }
        .invoice-title h1 {
            font-size: 24px;
            color: #3B82F6;
            margin: 0 0 5px 0;
        }
        .invoice-number {
            font-size: 11px;
            color: #64748B;
        }
        
        .customer-section {
            margin-bottom: 30px;
        }
        
        .section-box {
            background: #F8FAFC;
            padding: 12px;
            border-radius: 6px;
            border-left: 3px solid #3B82F6;
        }
        
        .section-title {
            font-weight: bold;
            color: #1E293B;
            margin-bottom: 6px;
            font-size: 11px;
        }
        
        .section-content {
            font-size: 10px;
            color: #475569;
            line-height: 1.6;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            table-layout: auto;
        }
        
        th {
            background: #3B82F6;
            color: white !important;
            padding: 8px 6px;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            white-space: nowrap;
        }
        
        td {
            padding: 7px 6px;
            border-bottom: 1px solid #E2E8F0;
            font-size: 10px;
            vertical-align: top;
        }
        
        tr:hover { background: #F8FAFC; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        table th:nth-child(1), table td:nth-child(1) { width: 3%; text-align: center; }
        table th:nth-child(2), table td:nth-child(2) { width: 25%; }
        table th:nth-child(3), table td:nth-child(3) { width: 9%; }
        table th:nth-child(4), table td:nth-child(4) { width: 10%; text-align: center; }
        table th:nth-child(5), table td:nth-child(5) { width: 11%; }
        table th:nth-child(6), table td:nth-child(6) { width: 12%; }
        table th:nth-child(7), table td:nth-child(7) { width: 6%; text-align: center; }
        table th:nth-child(8), table td:nth-child(8) { width: 11%; }
        table th:nth-child(9), table td:nth-child(9) { width: 13%; }
        
        .bottom-section {
            margin-top: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            align-items: start;
        }
        
        .bank-details-box {
            background: #F8FAFC;
            padding: 12px;
            border-radius: 6px;
            border-left: 3px solid #3B82F6;
        }
        
        .totals-box {
            display: flex;
            justify-content: flex-end;
        }
        
        .totals-table {
            width: 350px;
        }
        
        .totals-table td {
            padding: 6px 10px;
            font-size: 10px;
        }
        
        .grand-total {
            background: #3B82F6;
            font-weight: bold;
            font-size: 12px;
        }
        
        .grand-total td {
            color: white !important;
            white-space: nowrap;
        }
        
        .amount-words {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        .signature-section {
            margin-top: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            padding-top: 20px;
            border-top: 2px solid #E2E8F0;
        }
        
        .signature-box {
            text-align: center;
        }
        
        .signature-line {
            border-top: 2px solid #1E293B;
            margin: 60px 20px 10px 20px;
        }
        
        .signature-label {
            font-size: 10px;
            color: #64748B;
            font-weight: 600;
        }
        
        .stamp-space {
            height: 70px;
            border: 2px dashed #CBD5E1;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 10px 15px;
        }
        
        .stamp-label {
            color: #94A3B8;
            font-size: 10px;
            font-weight: 600;
        }
        
        .auth-name {
            font-size: 9px;
            color: #64748B;
            margin-top: 5px;
        }
        
        .terms-section {
            margin-top: 20px;
            font-size: 10px;
            color: #64748B;
            line-height: 1.5;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-primary { background: #3B82F6; color: white; }
        .btn-success { background: #10B981; color: white; }
        
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        
        @media print {
            body { background: white; padding: 0; }
            footer { display: none !important; }
            .admin-footer { display: none !important; }
            
            @page {
                size: A4;
                margin: 15mm;
            }
            
            .invoice-container { 
                box-shadow: none; 
                margin: 0; 
                padding: 0;
                width: 100%;
                max-width: 100%;
            }
            .action-buttons { display: none; }
            .page-break { page-break-before: always; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
            thead { display: table-header-group; }
            .bottom-section, .signature-section { page-break-inside: avoid; }
        }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            .invoice-container { padding: 20px; }
            .invoice-header { flex-direction: column; }
            .invoice-title { text-align: left; margin-top: 20px; }
            .bottom-section { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="action-buttons">
    <button class="btn btn-primary" onclick="window.print()">
        <i class="fas fa-print"></i> Print Invoice
    </button>
    <button class="btn btn-success" onclick="downloadPDF()">
        <i class="fas fa-download"></i> Download PDF
    </button>
</div>

<div class="invoice-container">
    <!-- Header -->
    <div class="invoice-header">
        <div class="company-info">
            <?php if ($company['company_logo_path']): ?>
                <img src="../../<?php echo $company['company_logo_path']; ?>" alt="Company Logo" class="company-logo">
            <?php endif; ?>
            <div class="company-name"><?php echo htmlspecialchars($company['company_name']); ?></div>
            <div class="company-details">
                <?php echo nl2br(htmlspecialchars($company['company_address'])); ?><br>
                <?php if ($company['company_phone']): ?>Phone: <?php echo htmlspecialchars($company['company_phone']); ?><br><?php endif; ?>
                <?php if ($company['company_email']): ?>Email: <?php echo htmlspecialchars($company['company_email']); ?><br><?php endif; ?>
                <?php if ($company['company_gstin']): ?>GSTIN: <?php echo htmlspecialchars($company['company_gstin']); ?><?php endif; ?>
            </div>
        </div>
        <div class="invoice-title">
            <h1>TAX INVOICE</h1>
            <div class="invoice-number">
                Invoice #: <strong><?php echo $invoice['invoice_number']; ?></strong><br>
                Date: <strong><?php echo date('d-M-Y', strtotime($invoice['invoice_date'])); ?></strong>
            </div>
        </div>
    </div>
    
    <!-- Customer Details -->
    <div class="customer-section">
        <div class="section-box">
            <div class="section-title">BILL TO:</div>
            <div class="section-content">
                <strong><?php echo htmlspecialchars($invoice['customer_name']); ?></strong><br>
                <?php if ($invoice['customer_address']): echo nl2br(htmlspecialchars($invoice['customer_address'])) . '<br>'; endif; ?>
                <?php if ($invoice['customer_phone']): echo 'Phone: ' . htmlspecialchars($invoice['customer_phone']) . '<br>'; endif; ?>
                <?php if ($invoice['customer_email']): echo 'Email: ' . htmlspecialchars($invoice['customer_email']) . '<br>'; endif; ?>
                <?php if ($invoice['customer_gstin']): echo 'GSTIN: ' . htmlspecialchars($invoice['customer_gstin']); endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Items Table -->
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product/Service</th>
                <?php if ($invoice['invoice_type'] == 'invoice_gst' || $invoice['invoice_type'] == 'estimation_gst'): ?>
                <th>HSN</th>
                <?php endif; ?>
                <th class="text-center">Qty</th>
                <th class="text-right">Rate</th>
                <th class="text-right">Amount</th>
                <?php if ($invoice['invoice_type'] == 'invoice_gst' || $invoice['invoice_type'] == 'estimation_gst'): ?>
                <th class="text-center">GST%</th>
                <th class="text-right">GST Amt</th>
                <?php endif; ?>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (empty($items)) {
                $colspan = ($invoice['invoice_type'] == 'invoice_gst' || $invoice['invoice_type'] == 'estimation_gst') ? 9 : 6;
                echo '<tr><td colspan="' . $colspan . '" class="text-center" style="color: #ef4444; padding: 20px;">No items found for this invoice</td></tr>';
            } else {
                $sno = 1;
                foreach ($items as $item): 
            ?>
            <tr>
                <td><?php echo $sno++; ?></td>
                <td><?php echo htmlspecialchars($item['product_name'] ?? 'N/A'); ?></td>
                <?php if ($invoice['invoice_type'] == 'invoice_gst' || $invoice['invoice_type'] == 'estimation_gst'): ?>
                <td><?php echo htmlspecialchars($item['hsn_code'] ?? ''); ?></td>
                <?php endif; ?>
                <td class="text-center"><?php echo $item['quantity'] ?? 0; ?> <?php echo $item['unit'] ?? ''; ?></td>
                <td class="text-right">₹<?php echo number_format($item['rate'] ?? 0, 2); ?></td>
                <td class="text-right">₹<?php echo number_format($item['amount'] ?? 0, 2); ?></td>
                <?php if ($invoice['invoice_type'] == 'invoice_gst' || $invoice['invoice_type'] == 'estimation_gst'): ?>
                <td class="text-center"><?php echo $item['gst_percentage'] ?? 0; ?>%</td>
                <td class="text-right">₹<?php echo number_format($item['gst_amount'] ?? 0, 2); ?></td>
                <?php endif; ?>
                <td class="text-right">₹<?php echo number_format($item['total_amount'] ?? 0, 2); ?></td>
            </tr>
            <?php 
                endforeach;
            }
            ?>
        </tbody>
    </table>
    
    <!-- Totals and Bank Details -->
    <div class="bottom-section">
        <div class="bank-details-box">
            <?php if ($bank): ?>
            <div class="section-title">BANK DETAILS:</div>
            <div class="section-content">
                <strong><?php echo htmlspecialchars($bank['bank_name']); ?></strong><br>
                A/c No: <?php echo htmlspecialchars($bank['account_number']); ?><br>
                IFSC: <?php echo htmlspecialchars($bank['ifsc_code']); ?><br>
                <?php if ($bank['branch_name']): echo 'Branch: ' . htmlspecialchars($bank['branch_name']) . '<br>'; endif; ?>
                <?php if ($bank['upi_id']): echo 'UPI: ' . htmlspecialchars($bank['upi_id']); endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="totals-box">
            <table class="totals-table">
                <tr>
                    <td><strong>Subtotal:</strong></td>
                    <td class="text-right">₹<?php echo number_format($invoice['subtotal'], 2); ?></td>
                </tr>
                <tr>
                    <td>CGST:</td>
                    <td class="text-right">₹<?php echo number_format($invoice['cgst_amount'], 2); ?></td>
                </tr>
                <tr>
                    <td>SGST:</td>
                    <td class="text-right">₹<?php echo number_format($invoice['sgst_amount'], 2); ?></td>
                </tr>
                <tr>
                    <td><strong>Total GST:</strong></td>
                    <td class="text-right"><strong>₹<?php echo number_format($invoice['total_gst'], 2); ?></strong></td>
                </tr>
                <tr class="grand-total">
                    <td><strong>GRAND TOTAL:</strong></td>
                    <td class="text-right"><strong>₹<?php echo number_format($invoice['grand_total'], 2); ?></strong></td>
                </tr>
            </table>
        </div>
    </div>
    
    <!-- Amount in Words -->
    <div class="amount-words">
        <?php echo $amount_in_words; ?>
    </div>
    
    <!-- Terms & Conditions -->
    <?php if ($invoice['terms_conditions']): ?>
    <div class="terms-section">
        <strong>Terms & Conditions:</strong><br>
        <?php echo nl2br(htmlspecialchars($invoice['terms_conditions'])); ?>
    </div>
    <?php endif; ?>
    
    <?php if ($invoice['notes']): ?>
    <div class="terms-section">
        <strong>Notes:</strong><br>
        <?php echo nl2br(htmlspecialchars($invoice['notes'])); ?>
    </div>
    <?php endif; ?>
    
    <!-- Signatures -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-label">Customer Signature</div>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box">
            <div class="stamp-space">
                <div class="stamp-label">Company Stamp</div>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-label">Authorized Signature</div>
            <div class="signature-line"></div>
            <div class="auth-name">For <?php echo htmlspecialchars($company['company_name']); ?></div>
        </div>
    </div>
</div>

<script>
function downloadPDF() {
    window.print();
}
</script>

</body>
</html>
