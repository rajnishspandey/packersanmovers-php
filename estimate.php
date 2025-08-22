<?php
require_once 'config.php';

// Check if admin access or public token access
$token = $_GET['token'] ?? '';
$quote_id = $_GET['id'] ?? '';
$is_admin_access = false;

if ($quote_id && is_logged_in()) {
    // Admin access with ID
    $is_admin_access = true;
    $stmt = $pdo->prepare("SELECT q.*, l.name, l.email, l.phone, l.move_from, l.move_to, l.move_date, l.home_size 
                          FROM quotes q 
                          JOIN leads l ON q.lead_id = l.id 
                          WHERE q.id = ?");
    $stmt->execute([$quote_id]);
    $quote = $stmt->fetch();
    
    if (!$quote) {
        http_response_code(404);
        die('Quote not found.');
    }
} elseif ($token) {
    // Public access with token
    $quote = get_quote_by_token($token);
    
    if (!$quote) {
        http_response_code(404);
        die('Estimate not found or has expired. Please contact us for assistance.');
    }
} else {
    http_response_code(404);
    die('Invalid access. Please use the link provided in your email.');
}

// Check if quote is still valid
$is_expired = strtotime($quote['valid_until']) < time();
$items = json_decode($quote['items'], true);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estimate <?php echo $quote['quote_number']; ?> - <?php echo get_setting('company_name', 'PackersAnMovers'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .estimate-container { max-width: 800px; margin: 2rem auto; }
        .company-header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; }
        .expired-badge { background-color: #dc3545; }
        .valid-badge { background-color: #28a745; }
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
        
        .estimate-header-section {
            margin-bottom: 1.5rem;
        }
        
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .card { box-shadow: none !important; border: 1px solid #ddd; }
            .table-responsive { overflow: visible !important; }
            
            /* Two column layout for estimate header */
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
    <?php if ($is_admin_access): ?>
    <div class="alert alert-danger text-center mb-0" style="border-radius: 0;">
        <strong><i class="fas fa-user-shield"></i> ADMIN PREVIEW</strong> - This view is only accessible to logged-in users
    </div>
    <?php endif; ?>
    
    <div class="estimate-container">
        <div class="card shadow-lg">
            <!-- Company Header -->
            <div class="company-header p-4 text-center">
                <h1 class="mb-0" style="font-size: 2.5rem; font-weight: bold;"><?php echo get_setting('company_name', 'PackersAnMovers'); ?></h1>
                <p class="mb-0" style="font-size: 1.1rem; opacity: 0.9;">Professional Packers & Movers</p>
                <?php if (get_setting('company_tagline')): ?>
                <p class="mb-0" style="font-size: 0.8rem; opacity: 0.7; margin-top: 5px;"><?php echo get_setting('company_tagline'); ?></p>
                <?php endif; ?>
                <?php if (isset($quote['tax_enabled']) && $quote['tax_enabled'] == 1 && get_setting('gst_number')): ?>
                <p class="mb-0" style="font-size: 0.9rem; opacity: 0.8; margin-top: 8px;"><strong>GST No:</strong> <?php echo get_setting('gst_number'); ?></p>
                <?php endif; ?>
            </div>

            <div class="card-body p-4">
                <!-- Estimate Header -->
                <div class="row mb-4 estimate-header-section">
                    <div class="col-md-6 col-print-left">
                        <h2 class="text-primary mb-3">Moving Estimate</h2>
                        <div class="estimate-details">
                            <p class="mb-2"><strong>Estimate #:</strong> <?php echo $quote['quote_number']; ?></p>
                            <p class="mb-2"><strong>Date:</strong> <?php echo date('M d, Y', strtotime($quote['created_at'])); ?></p>
                            <p class="mb-2">
                                <strong>Status:</strong> 
                                <span class="badge <?php echo $quote['status'] == 'draft' ? 'bg-secondary' : ($is_expired ? 'expired-badge' : 'valid-badge'); ?>">
                                    <?php echo $quote['status'] == 'draft' ? 'DRAFT' : ($is_expired ? 'EXPIRED' : 'VALID'); ?>
                                </span>
                            </p>
                            <p class="mb-0">
                                <strong>Valid Until:</strong> <?php echo date('M d, Y', strtotime($quote['valid_until'])); ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6 col-print-right">
                        <div class="customer-section">
                            <h5 class="text-primary mb-3">Customer Details</h5>
                            <div class="customer-info">
                                <p class="mb-2"><strong><?php echo htmlspecialchars($quote['name']); ?></strong></p>
                                <?php if (!empty($quote['email'])): ?>
                                <p class="mb-2"><i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($quote['email']); ?></p>
                                <?php endif; ?>
                                <p class="mb-0"><i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($quote['phone']); ?></p>
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
                                            <p class="mb-2"><i class="fas fa-map-marker-alt text-success me-2"></i><strong>From:</strong> <?php echo htmlspecialchars($quote['move_from']); ?></p>
                                            <p class="mb-2"><i class="fas fa-map-marker-alt text-danger me-2"></i><strong>To:</strong> <?php echo htmlspecialchars($quote['move_to']); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-print-right-details">
                                        <div class="moving-schedule">
                                            <p class="mb-2"><i class="fas fa-calendar-alt text-primary me-2"></i><strong>Preferred Date:</strong> <?php echo date('M d, Y', strtotime($quote['move_date'])); ?></p>
                                            <p class="mb-2"><i class="fas fa-home text-info me-2"></i><strong>Property Size:</strong> <?php echo htmlspecialchars($quote['home_size']); ?></p>
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
                            $tax_enabled = isset($quote['tax_enabled']) && $quote['tax_enabled'] == 1;
                            $has_service = isset($quote['service_amount']) && $quote['service_amount'] > 0;
                            $has_tax = isset($quote['tax_amount']) && $quote['tax_amount'] > 0;
                            ?>
                            
                            <?php if ($tax_enabled): ?>
                            <!-- Tax enabled - show breakdown -->
                            <tr>
                                <th colspan="3" class="text-end">Subtotal:</th>
                                <th class="text-end">Rs <?php echo number_format($quote['subtotal'], 2); ?></th>
                            </tr>
                            <?php if ($has_service): ?>
                            <tr>
                                <th colspan="3" class="text-end">Service Charge (<?php echo $quote['service_rate'] ?? 5; ?>%):</th>
                                <th class="text-end">Rs <?php echo number_format($quote['service_amount'], 2); ?></th>
                            </tr>
                            <?php endif; ?>
                            <?php if ($has_tax): ?>
                            <tr>
                                <th colspan="3" class="text-end">GST (<?php echo $quote['tax_rate']; ?>%):</th>
                                <th class="text-end">Rs <?php echo number_format($quote['tax_amount'], 2); ?></th>
                            </tr>
                            <?php endif; ?>
                            <tr class="table-primary">
                                <th colspan="3" class="text-end">Total Amount:</th>
                                <th class="text-end">Rs <?php echo number_format($quote['total_amount'], 2); ?></th>
                            </tr>
                            <?php else: ?>
                            <!-- Tax disabled - simple total -->
                            <tr class="table-primary">
                                <th colspan="3" class="text-end">Total Amount:</th>
                                <th class="text-end">Rs <?php echo number_format($quote['subtotal'], 2); ?></th>
                            </tr>
                            <?php endif; ?>
                        </tfoot>
                    </table>
                </div>

                <!-- Terms and Conditions -->
                <div class="card bg-light mb-4">
                    <div class="card-body">
                        <h6 class="card-title">Terms & Conditions</h6>
                        <ul class="mb-0 small">
                            <li>This estimate is valid until <?php echo date('M d, Y', strtotime($quote['valid_until'])); ?></li>
                            <li>Prices may vary based on actual inventory and additional services required</li>
                            <li>25% advance payment required to confirm booking</li>
                            <li>All items will be packed professionally with insurance coverage</li>
                            <li>Additional charges may apply for stairs, long carry, or special handling</li>
                        </ul>
                    </div>
                </div>

                <!-- Draft Notice -->
                <?php if ($quote['status'] == 'draft'): ?>
                <div class="alert alert-<?php echo $is_admin_access ? 'warning' : 'info'; ?> text-center mb-4">
                    <i class="fas fa-<?php echo $is_admin_access ? 'exclamation-triangle' : 'info-circle'; ?>"></i>
                    <?php if ($is_admin_access): ?>
                    <strong>DRAFT ESTIMATE - ADMIN VIEW ONLY</strong><br>
                    This is a draft estimate visible only to logged-in users. Customers cannot access this until status is changed to 'sent'.
                    <?php else: ?>
                    <strong>This is a DRAFT estimate.</strong> Prices and terms are preliminary and subject to change. Please contact us to finalize your quote.
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="text-center no-print">
                    <?php if ($is_admin_access): ?>
                    <a href="/quotes" class="btn btn-secondary btn-lg me-2">
                        <i class="fas fa-arrow-left"></i> Back to Quotes
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-primary btn-lg me-2">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <?php if ($quote['status'] == 'draft'): ?>
                    <a href="/quotes" class="btn btn-success btn-lg">
                        <i class="fas fa-paper-plane"></i> Send to Customer
                    </a>
                    <?php endif; ?>
                    <?php else: ?>
                    <?php if (!$is_expired): ?>
                    <a href="tel:<?php echo get_setting('company_phone1'); ?>" class="btn btn-success btn-lg me-2">
                        <i class="fas fa-phone"></i> <?php echo $quote['status'] == 'draft' ? 'Call to Discuss' : 'Call to Book Now'; ?>
                    </a>
                    <a href="https://wa.me/<?php echo str_replace(['+', ' ', '-'], '', get_setting('company_phone1')); ?>?text=Hi, I would like to <?php echo $quote['status'] == 'draft' ? 'discuss the draft estimate' : 'book the moving service for estimate'; ?> <?php echo $quote['quote_number']; ?>" class="btn btn-success btn-lg me-2" target="_blank">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                    <?php endif; ?>
                    <button onclick="window.print()" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <?php endif; ?>
                </div>

                <?php if ($is_expired): ?>
                <div class="alert alert-warning text-center mt-4">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>This estimate has expired.</strong> Please contact us for an updated quote.
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
                        <?php if (isset($quote['tax_enabled']) && $quote['tax_enabled'] && get_setting('gst_number')): ?>
                        <br><strong>GST No:</strong> <?php echo get_setting('gst_number'); ?>
                        <?php endif; ?>
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