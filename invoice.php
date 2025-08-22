<?php
require_once 'config.php';

// Get secure token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    http_response_code(404);
    die('Invalid access. Please use the link provided in your email.');
}

// Get invoice data using secure token
$invoice = get_invoice_by_token($token);

if (!$invoice) {
    http_response_code(404);
    die('Invoice not found. Please contact us for assistance.');
}

// Check if invoice is overdue
$is_overdue = strtotime($invoice['due_date']) < time() && $invoice['status'] != 'paid';
$items = json_decode($invoice['items'], true);

// Calculate consolidated balance for related invoices (parent + supplementary)
$parent_id = $invoice['parent_invoice_id'] ?: $invoice['id'];
$stmt = $pdo->prepare("SELECT SUM(total_amount) as total, SUM(paid_amount) as paid FROM invoices WHERE (id = ? OR parent_invoice_id = ?) AND status != 'abandoned'");
$stmt->execute([$parent_id, $parent_id]);
$consolidated = $stmt->fetch();

$consolidated_total = $consolidated['total'] ?: $invoice['total_amount'];
$consolidated_paid = $consolidated['paid'] ?: $invoice['paid_amount'];
$balance_due = $consolidated_total - $consolidated_paid;

// Ensure balance is not negative
$balance_due = max(0, $balance_due);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo $invoice['invoice_number']; ?> - <?php echo get_setting('company_name', 'PackersAnMovers'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .invoice-container { max-width: 800px; margin: 2rem auto; }
        .company-header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; }
        .status-paid { background-color: #28a745; }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-overdue { background-color: #dc3545; }
        .status-partial { background-color: #17a2b8; }
        /* Better web display */
        .col-print-left, .col-print-right {
            display: block;
        }
        
        /* Improve web layout */
        @media (min-width: 768px) {
            .col-print-right {
                text-align: right;
            }
        }
        
        .invoice-header-section {
            margin-bottom: 1.5rem;
        }
        
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .card { box-shadow: none !important; border: 1px solid #ddd; }
            .table-responsive { overflow: visible !important; }
            
            /* Two column layout for invoice header */
            .col-print-left {
                width: 50% !important;
                float: left !important;
                display: block !important;
            }
            .col-print-right {
                width: 50% !important;
                float: right !important;
                display: block !important;
                text-align: right !important;
            }
            
            /* Two column layout for moving details */
            .col-print-left-details {
                width: 50% !important;
                float: left !important;
                display: block !important;
            }
            .col-print-right-details {
                width: 50% !important;
                float: right !important;
                display: block !important;
            }
            
            /* Other columns stack vertically */
            .col-md-4, .col-12:not(.col-print-left):not(.col-print-right):not(.col-print-left-details):not(.col-print-right-details) { 
                width: 100% !important; 
                float: none !important; 
                display: block !important;
                margin-bottom: 15px;
            }
            
            /* Clear floats */
            .row::after {
                content: "";
                display: table;
                clear: both;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="card shadow-lg">
            <!-- Company Header -->
            <div class="company-header p-4 text-center">
                <h1 class="mb-0" style="font-size: 2.5rem; font-weight: bold;"><?php echo get_setting('company_name', 'PackersAnMovers'); ?></h1>
                <p class="mb-0" style="font-size: 1.1rem; opacity: 0.9;">Professional Packers & Movers</p>
                <?php if (get_setting('company_tagline')): ?>
                <p class="mb-0" style="font-size: 0.8rem; opacity: 0.7; margin-top: 5px;"><?php echo get_setting('company_tagline'); ?></p>
                <?php endif; ?>
                <?php if (isset($invoice['tax_enabled']) && $invoice['tax_enabled'] == 1 && get_setting('gst_number')): ?>
                <p class="mb-0" style="font-size: 0.9rem; opacity: 0.8; margin-top: 8px;"><strong>GST No:</strong> <?php echo get_setting('gst_number'); ?></p>
                <?php endif; ?>
            </div>

            <div class="card-body p-4">
                <!-- Invoice Header -->
                <div class="row mb-4 invoice-header-section">
                    <div class="col-md-6 col-print-left">
                        <h2 class="text-primary mb-3">INVOICE</h2>
                        <div class="invoice-details">
                            <p class="mb-2"><strong>Invoice #:</strong> <?php echo $invoice['invoice_number']; ?>
                                <?php if ($invoice['parent_invoice_id']): ?>
                                <br><small class="text-muted">Supplementary Invoice</small>
                                <?php endif; ?>
                            </p>
                            <p class="mb-2"><strong>Quote #:</strong> <?php echo $invoice['quote_number']; ?></p>
                            <p class="mb-2"><strong>Date:</strong> <?php echo date('M d, Y', strtotime($invoice['created_at'])); ?></p>
                            <p class="mb-0"><strong>Due Date:</strong> 
                                <span class="<?php echo $is_overdue ? 'text-danger fw-bold' : ''; ?>">
                                    <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?>
                                    <?php echo $is_overdue ? ' (OVERDUE)' : ''; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6 col-print-right">
                        <div class="bill-to-section">
                            <h5 class="text-primary mb-3">Bill To</h5>
                            <div class="customer-details">
                                <p class="mb-2"><strong><?php echo htmlspecialchars($invoice['name']); ?></strong></p>
                                <?php if (!empty($invoice['email'])): ?>
                                <p class="mb-2"><i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($invoice['email']); ?></p>
                                <?php endif; ?>
                                <p class="mb-3"><i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($invoice['phone']); ?></p>
                                
                                <span class="badge fs-6 status-<?php echo $invoice['status']; ?>">
                                    <?php echo strtoupper($invoice['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Moving Details -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title text-primary mb-3">Moving Details</h5>
                                <div class="row">
                                    <div class="col-md-6 col-print-left-details">
                                        <div class="moving-route">
                                            <p class="mb-2"><i class="fas fa-map-marker-alt text-success me-2"></i><strong>From:</strong> <?php echo htmlspecialchars($invoice['move_from']); ?></p>
                                            <p class="mb-2"><i class="fas fa-map-marker-alt text-danger me-2"></i><strong>To:</strong> <?php echo htmlspecialchars($invoice['move_to']); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-print-right-details">
                                        <div class="moving-schedule">
                                            <p class="mb-2"><i class="fas fa-calendar-alt text-primary me-2"></i><strong>Scheduled Date:</strong> <?php echo date('M d, Y', strtotime($invoice['move_date'])); ?></p>
                                            <p class="mb-2"><i class="fas fa-home text-info me-2"></i><strong>Property Size:</strong> <?php echo htmlspecialchars($invoice['home_size']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead class="table-primary">
                            <tr>
                                <th>Service Description</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Rate (Rs)</th>
                                <th class="text-end">Amount (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['description']); ?></td>
                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                <td class="text-end"><?php echo number_format($item['rate'], 2); ?></td>
                                <td class="text-end"><?php echo number_format($item['quantity'] * $item['rate'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <?php 
                            $tax_enabled = isset($invoice['tax_enabled']) && $invoice['tax_enabled'] == 1;
                            $has_service = isset($invoice['service_amount']) && $invoice['service_amount'] > 0;
                            $has_tax = isset($invoice['tax_amount']) && $invoice['tax_amount'] > 0;
                            ?>
                            
                            <?php if ($tax_enabled): ?>
                            <!-- Tax enabled - show breakdown -->
                            <tr>
                                <th colspan="3" class="text-end">Subtotal:</th>
                                <th class="text-end">Rs <?php echo number_format($invoice['subtotal'], 2); ?></th>
                            </tr>
                            <?php if ($has_service): ?>
                            <tr>
                                <th colspan="3" class="text-end">Service Charge (<?php echo $invoice['service_rate'] ?? 5; ?>%):</th>
                                <th class="text-end">Rs <?php echo number_format($invoice['service_amount'], 2); ?></th>
                            </tr>
                            <?php endif; ?>
                            <?php if ($has_tax): ?>
                            <tr>
                                <th colspan="3" class="text-end">GST (<?php echo $invoice['tax_rate']; ?>%):</th>
                                <th class="text-end">Rs <?php echo number_format($invoice['tax_amount'], 2); ?></th>
                            </tr>
                            <?php endif; ?>
                            <tr class="table-primary">
                                <th colspan="3" class="text-end"><?php echo $invoice['parent_invoice_id'] ? 'This Invoice Amount:' : 'Total Amount:'; ?></th>
                                <th class="text-end">Rs <?php echo number_format($invoice['total_amount'], 2); ?></th>
                            </tr>
                            <?php else: ?>
                            <!-- Tax disabled - simple total -->
                            <tr class="table-primary">
                                <th colspan="3" class="text-end"><?php echo $invoice['parent_invoice_id'] ? 'This Invoice Amount:' : 'Total Amount:'; ?></th>
                                <th class="text-end">Rs <?php echo number_format($invoice['subtotal'], 2); ?></th>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if ($invoice['parent_invoice_id'] || $consolidated_total != $invoice['total_amount']): ?>
                            <!-- Show breakdown of all related invoices -->
                            <?php
                            $stmt = $pdo->prepare("SELECT invoice_number, total_amount, invoice_type FROM invoices WHERE (id = ? OR parent_invoice_id = ?) AND status != 'abandoned' ORDER BY created_at");
                            $stmt->execute([$parent_id, $parent_id]);
                            $related_invoices = $stmt->fetchAll();
                            ?>
                            <tr><td colspan="4"><hr class="my-2"></td></tr>
                            <tr class="table-secondary">
                                <th colspan="4" class="text-center">Invoice Breakdown</th>
                            </tr>
                            <?php foreach ($related_invoices as $rel_inv): ?>
                            <tr class="table-light">
                                <td colspan="2"><?php echo $rel_inv['invoice_type'] == 'supplementary' ? 'Supplementary' : 'Main'; ?> Invoice: <strong><?php echo $rel_inv['invoice_number']; ?></strong></td>
                                <td class="text-end">Amount:</td>
                                <td class="text-end">Rs <?php echo number_format($rel_inv['total_amount'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="table-info">
                                <th colspan="3" class="text-end">Total (All Invoices):</th>
                                <th class="text-end">Rs <?php echo number_format($consolidated_total, 2); ?></th>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if ($consolidated_paid > 0): ?>
                            <tr class="table-success">
                                <th colspan="3" class="text-end">Total Paid Amount:</th>
                                <th class="text-end">Rs <?php echo number_format($consolidated_paid, 2); ?></th>
                            </tr>
                            <?php if ($balance_due > 0): ?>
                            <tr class="table-warning">
                                <th colspan="3" class="text-end">Balance Due:</th>
                                <th class="text-end">Rs <?php echo number_format($balance_due, 2); ?></th>
                            </tr>
                            <?php endif; ?>
                            <?php endif; ?>
                        </tfoot>
                    </table>
                </div>

                <!-- Payment Information -->
                <?php if ($balance_due > 0): ?>
                <div class="card bg-light mb-4">
                    <div class="card-body">
                        <h6 class="card-title text-primary">Payment Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Payment Methods:</strong></p>
                                <ul class="mb-0">
                                    <li>Cash payment on moving day</li>
                                    <li>Bank transfer</li>
                                    <li>Online payment</li>
                                    <li>UPI/Digital wallets</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Payment Terms:</strong></p>
                                <ul class="mb-0">
                                    <li>25% advance to confirm booking</li>
                                    <li>Balance payment before/on moving day</li>
                                    <li>Late payment charges may apply</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="text-center no-print">
                    <?php if ($balance_due > 0): ?>
                    <a href="tel:<?php echo get_setting('company_phone1'); ?>" class="btn btn-success btn-lg me-2">
                        <i class="fas fa-phone"></i> Call for Payment
                    </a>
                    <a href="https://wa.me/<?php echo str_replace(['+', ' ', '-'], '', get_setting('company_phone1')); ?>?text=Hi, I want to make payment for invoice <?php echo $invoice['invoice_number']; ?>. Amount: Rs <?php echo number_format($balance_due, 2); ?>" class="btn btn-success btn-lg me-2" target="_blank">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                    <?php endif; ?>
                    <button onclick="window.print()" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>

                <?php if ($is_overdue): ?>
                <div class="alert alert-danger text-center mt-4">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Payment Overdue!</strong> Please make payment immediately to avoid service delays.
                </div>
                <?php elseif ($invoice['status'] == 'paid'): ?>
                <div class="alert alert-success text-center mt-4">
                    <i class="fas fa-check-circle"></i>
                    <strong>Payment Complete!</strong> Thank you for choosing our services.
                </div>
                <?php endif; ?>
            </div>

            <!-- Company Footer -->
            <div class="card-footer bg-light text-center">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Contact Us</strong><br>
                        <i class="fas fa-phone"></i> <?php echo get_setting('company_phone1'); ?><br>
                        <i class="fas fa-envelope"></i> <?php echo get_setting('company_email'); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Address</strong><br>
                        <?php echo get_setting('company_address'); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Website</strong><br>
                        <a href="<?php echo get_setting('company_website'); ?>" target="_blank">
                            <?php echo get_setting('company_website'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>