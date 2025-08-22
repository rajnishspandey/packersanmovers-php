<?php
require_once 'config.php';
require_once 'includes/mail.php';
require_once 'includes/mail.php';
require_permission('quotes', 'read');

$page_title = 'Quotes Management';

// Handle quote creation/update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'update_quote_status') {
            $quote_id = $_POST['quote_id'];
            $new_status = $_POST['status'];
            
            // Check if invoice already exists for this quote
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE quote_id = ?");
            $stmt->execute([$quote_id]);
            
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['message'] = 'Cannot update quote status - Invoice already created!';
            } else {
                $stmt = $pdo->prepare("UPDATE quotes SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $quote_id]);
                // Get quote and lead details for email notification
            $stmt = $pdo->prepare("SELECT q.*, l.* FROM quotes q JOIN leads l ON q.lead_id = l.id WHERE q.id = ?");
            $stmt->execute([$quote_id]);
            $quote_data = $stmt->fetch();
            
            if ($quote_data && $new_status == 'sent') {
                // Only send email if changing FROM non-draft status TO sent
                // Don't send if it was already draft before the update
                $stmt_old = $pdo->prepare("SELECT status FROM quotes WHERE id = ?");
                $stmt_old->execute([$quote_id]);
                $old_status = $stmt_old->fetchColumn();
                
                if ($old_status != 'draft') {
                    // Send professional quote email when status changes to 'sent' and email exists
                    if (!empty($quote_data['secure_token']) && !empty($quote_data['email'])) {
                        try {
                            send_quote_email($quote_data, $quote_data, 'sent');
                        } catch (Exception $e) {
                            error_log("Quote email failed: " . $e->getMessage());
                        }
                    } elseif (!empty($quote_data['email'])) {
                    // Fallback professional email without secure token
                    $customer_content = '
                        <h2 style="color: #28a745;">Your Moving Estimate - Ready for Review</h2>
                        <p>Dear <strong>' . $quote_data['name'] . '</strong>,</p>
                        <p>We have prepared your moving estimate and it\'s ready for your review.</p>
                        
                        <div class="success">
                            <h3>Estimate Details</h3>
                            <p><strong>Estimate Number:</strong> ' . $quote_data['quote_number'] . '</p>
                            <p><strong>Total Amount:</strong> Rs ' . number_format($quote_data['total_amount'], 2) . '</p>
                            <p><strong>Valid Until:</strong> ' . date('M d, Y', strtotime($quote_data['valid_until'])) . '</p>
                        </div>
                        
                        <div class="highlight">
                            <h3>Moving Details</h3>
                            <p><strong>From:</strong> ' . $quote_data['move_from'] . '</p>
                            <p><strong>To:</strong> ' . $quote_data['move_to'] . '</p>
                            <p><strong>Preferred Date:</strong> ' . date('M d, Y', strtotime($quote_data['move_date'])) . '</p>
                            <p><strong>Property Size:</strong> ' . $quote_data['home_size'] . '</p>
                        </div>
                        
                        <p>Please review the estimate and contact us to proceed with your booking.</p>
                        <p>We look forward to making your move smooth and hassle-free!</p>
                    ';
                    
                        try {
                            $customer_template = get_email_template('Moving Estimate - ' . $quote_data['quote_number'], $customer_content, 'This estimate is valid until ' . date('M d, Y', strtotime($quote_data['valid_until'])) . '. Prices may vary based on actual inventory and additional services.');
                            send_email([$quote_data['email']], 'Your Moving Estimate - ' . $quote_data['quote_number'], $customer_template);
                        } catch (Exception $e) {
                            error_log("Quote email failed: " . $e->getMessage());
                        }
                }
                
                    // Send notification to support team
                    $support_template = get_email_template(
                        'Quote Status Updated - ' . $quote_data['quote_number'],
                        '<h2 style="color: #007bff;">Quote Status Updated to Sent</h2>
                        <div class="success">
                            <p><strong>Quote:</strong> ' . $quote_data['quote_number'] . '</p>
                            <p><strong>Customer:</strong> ' . $quote_data['name'] . ' (' . $quote_data['email'] . ')</p>
                            <p><strong>Amount:</strong> Rs ' . number_format($quote_data['total_amount'], 2) . '</p>
                            <p><strong>Status:</strong> Sent to Customer</p>
                        </div>'
                    );
                    send_email(get_support_emails(), 'Quote Sent - ' . $quote_data['quote_number'], $support_template);
                } else {
                    // Just update status for draft quotes without sending emails
                    error_log('Draft quote status updated to sent but no email sent: ' . $quote_data['quote_number']);
                }
            }
            
            $_SESSION['message'] = $quote_data && $new_status == 'sent' && $old_status == 'draft' ? 
                'Quote status updated! (Draft quotes are not sent to customers)' : 
                'Quote status updated successfully!';
            }
            redirect('/quotes');
        }
        
        if ($_POST['action'] == 'create_quote') {
            $lead_id = $_POST['lead_id'];
            $items = json_encode($_POST['items']);
            $subtotal = $_POST['subtotal'];
            $enable_tax = isset($_POST['enable_tax']) ? 1 : 0;
            $service_rate = $enable_tax ? ($_POST['service_rate'] ?? 5.00) : 0;
            $tax_rate = $enable_tax ? ($_POST['tax_rate'] ?? 18.00) : 0;
            
            // Calculate service charge and tax
            $service_amount = ($subtotal * $service_rate) / 100;
            $subtotal_with_service = $subtotal + $service_amount;
            $tax_amount = ($subtotal_with_service * $tax_rate) / 100;
            $total_amount = $subtotal_with_service + $tax_amount;
            $valid_until = $_POST['valid_until'];
            
            // Generate estimate number and secure token
            $quote_number = 'EST' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $secure_token = generate_secure_token();
            
            try {
                $stmt = $pdo->prepare("INSERT INTO quotes (lead_id, quote_number, secure_token, items, subtotal, service_rate, service_amount, tax_rate, tax_amount, total_amount, tax_enabled, valid_until, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$lead_id, $quote_number, $secure_token, $items, $subtotal, $service_rate, $service_amount, $tax_rate, $tax_amount, $total_amount, $enable_tax, $valid_until, $_SESSION['user_id']]);
            } catch (Exception $e) {
                // Fallback for older schema
                $stmt = $pdo->prepare("INSERT INTO quotes (lead_id, quote_number, items, subtotal, tax_rate, tax_amount, total_amount, valid_until, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$lead_id, $quote_number, $items, $subtotal, $tax_rate, $tax_amount, $total_amount, $valid_until, $_SESSION['user_id']]);
                $secure_token = null;
            }
            
            // Update lead status to draft when quote is created
            $stmt = $pdo->prepare("UPDATE leads SET status = 'draft' WHERE id = ?");
            $stmt->execute([$lead_id]);
            
            // No emails sent for new quotes - they start as draft
            
            $_SESSION['message'] = 'Quote created as draft successfully! Lead status updated to Draft.';
            redirect('/quotes');
        }
        
        if ($_POST['action'] == 'edit_quote') {
            $quote_id = $_POST['quote_id'];
            $items = $_POST['items'];
            $subtotal = $_POST['subtotal'];
            $enable_tax = isset($_POST['enable_tax']) ? 1 : 0;
            $service_rate = $enable_tax ? ($_POST['service_rate'] ?? 5.00) : 0;
            $tax_rate = $enable_tax ? ($_POST['tax_rate'] ?? 18.00) : 0;
            
            // Calculate service charge and tax
            $service_amount = ($subtotal * $service_rate) / 100;
            $subtotal_with_service = $subtotal + $service_amount;
            $tax_amount = ($subtotal_with_service * $tax_rate) / 100;
            $total_amount = $subtotal_with_service + $tax_amount;
            
            $stmt = $pdo->prepare("UPDATE quotes SET items = ?, subtotal = ?, service_rate = ?, service_amount = ?, tax_rate = ?, tax_amount = ?, total_amount = ?, tax_enabled = ? WHERE id = ?");
            $stmt->execute([json_encode($items), $subtotal, $service_rate, $service_amount, $tax_rate, $tax_amount, $total_amount, $enable_tax, $quote_id]);
            
            // No emails sent for quote edits - only when status changes to 'sent'
            
            $_SESSION['message'] = 'Estimate updated successfully!';
            redirect('/quotes');
        }
        
        // Abandoned quotes are handled by update_quote_status action above
        
        if ($_POST['action'] == 'convert_to_invoice') {
            $quote_id = $_POST['quote_id'];
            
            // Get quote details
            $stmt = $pdo->prepare("SELECT * FROM quotes WHERE id = ?");
            $stmt->execute([$quote_id]);
            $quote = $stmt->fetch();
            
            if ($quote) {
                // Generate invoice number and secure token
                $invoice_number = 'INV' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $invoice_secure_token = generate_secure_token();
                $due_date = date('Y-m-d', strtotime('+30 days'));
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO invoices (quote_id, invoice_number, secure_token, items, subtotal, service_rate, service_amount, tax_rate, tax_amount, total_amount, tax_enabled, due_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$quote_id, $invoice_number, $invoice_secure_token, $quote['items'], $quote['subtotal'], $quote['service_rate'] ?? 0, $quote['service_amount'] ?? 0, $quote['tax_rate'], $quote['tax_amount'], $quote['total_amount'], $quote['tax_enabled'] ?? 0, $due_date, $_SESSION['user_id']]);
                } catch (Exception $e) {
                    // Fallback without new columns if they don't exist
                    try {
                        $stmt = $pdo->prepare("INSERT INTO invoices (quote_id, invoice_number, secure_token, items, subtotal, tax_rate, tax_amount, total_amount, due_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$quote_id, $invoice_number, $invoice_secure_token, $quote['items'], $quote['subtotal'], $quote['tax_rate'], $quote['tax_amount'], $quote['total_amount'], $due_date, $_SESSION['user_id']]);
                    } catch (Exception $e2) {
                        // Final fallback without secure_token
                        $stmt = $pdo->prepare("INSERT INTO invoices (quote_id, invoice_number, items, subtotal, tax_rate, tax_amount, total_amount, due_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$quote_id, $invoice_number, $quote['items'], $quote['subtotal'], $quote['tax_rate'], $quote['tax_amount'], $quote['total_amount'], $due_date, $_SESSION['user_id']]);
                        $invoice_secure_token = null;
                    }
                }
                
                // Update quote status
                $stmt = $pdo->prepare("UPDATE quotes SET status = 'accepted' WHERE id = ?");
                $stmt->execute([$quote_id]);
                
                // Get lead details for email
                $stmt = $pdo->prepare("SELECT l.* FROM leads l JOIN quotes q ON l.id = q.lead_id WHERE q.id = ?");
                $stmt->execute([$quote_id]);
                $lead = $stmt->fetch();
                
                if ($lead) {
                    // Create invoice data for email
                    $invoice_data = [
                        'id' => $pdo->lastInsertId(),
                        'invoice_number' => $invoice_number,
                        'secure_token' => $invoice_secure_token,
                        'total_amount' => $quote['total_amount'],
                        'due_date' => $due_date
                    ];
                    
                    // Send professional invoice email to customer only if email exists
                    if (!empty($lead['email'])) {
                        try {
                            send_invoice_email($invoice_data, $quote, $lead, 'converted');
                        } catch (Exception $e) {
                            error_log("Invoice email failed: " . $e->getMessage());
                        }
                    }
                    
                    // Send professional notification to support team
                    $support_content = '
                        <h2 style="color: #28a745;">Quote Successfully Converted to Invoice</h2>
                        <div class="success">
                            <h3>Conversion Details</h3>
                            <p><strong>Original Quote:</strong> ' . $quote['quote_number'] . '</p>
                            <p><strong>New Invoice:</strong> ' . $invoice_number . '</p>
                            <p><strong>Amount:</strong> Rs ' . number_format($quote['total_amount'], 2) . '</p>
                            <p><strong>Due Date:</strong> ' . date('M d, Y', strtotime($due_date)) . '</p>
                        </div>
                        <div class="highlight">
                            <h3>Customer Details</h3>
                            <p><strong>Name:</strong> ' . $lead['name'] . '</p>
                            <p><strong>Email:</strong> ' . $lead['email'] . '</p>
                            <p><strong>Phone:</strong> ' . $lead['phone'] . '</p>
                        </div>
                        <p><strong>Next Steps:</strong></p>
                        <ul>
                            <li>Follow up for advance payment (25% recommended)</li>
                            <li>Schedule pre-move survey if required</li>
                            <li>Confirm moving date and logistics</li>
                        </ul>
                    ';
                    
                    $support_template = get_email_template('Quote Converted to Invoice - ' . $invoice_number, $support_content);
                    send_email(get_support_emails(), 'Quote Converted to Invoice - ' . $invoice_number, $support_template);
                }
                
                $_SESSION['message'] = 'Quote converted to invoice and sent successfully!';
            }
            redirect('/quotes');
        }
    }
}

// Get all quotes with lead details and invoice status (show abandoned but mark them)
$stmt = $pdo->query("SELECT q.*, l.name, l.email, l.phone, l.move_from, l.move_to, 
                     i.id as invoice_id, i.invoice_number, i.status as invoice_status
                     FROM quotes q 
                     JOIN leads l ON q.lead_id = l.id 
                     LEFT JOIN invoices i ON q.id = i.quote_id
                     ORDER BY q.created_at DESC");
$quotes = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">Dashboard</a></li>
            <li class="breadcrumb-item active">Estimates</li>
        </ol>
    </nav>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-file-alt"></i> Estimates Management</h1>
        <div>
            <a href="/leads" class="btn btn-outline-info"><i class="fas fa-users"></i> Leads</a>
            <a href="/invoices" class="btn btn-outline-success"><i class="fas fa-receipt"></i> Invoices</a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createQuoteModal"><i class="fas fa-plus"></i> Create Estimate</button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Estimate #</th>
                            <th>Customer</th>
                            <th>Route</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Valid Until</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quotes as $quote): ?>
                        <tr>
                            <td><?php echo $quote['quote_number']; ?></td>
                            <td>
                                <?php echo $quote['name']; ?><br>
                                <small class="text-muted"><?php echo $quote['email']; ?></small>
                            </td>
                            <td><?php echo $quote['move_from'] . ' → ' . $quote['move_to']; ?></td>
                            <td>Rs <?php echo number_format($quote['total_amount'], 2); ?></td>
                            <td>
                                <?php if ($quote['status'] == 'abandoned'): ?>
                                    <span class="badge bg-secondary">Abandoned</span>
                                <?php elseif ($quote['invoice_id']): ?>
                                    <span class="badge bg-success">Invoiced</span><br>
                                    <small class="text-muted"><?php echo $quote['invoice_number']; ?></small>
                                <?php else: ?>
                                    <?php 
                                    $status_info = get_status_info('quote', $quote['status']);
                                    $is_final_status = in_array($quote['status'], ['rejected', 'expired', 'cancelled']);
                                    ?>
                                    <?php if ($is_final_status): ?>
                                        <span class="badge bg-<?php echo $status_info ? $status_info['status_color'] : 'secondary'; ?>">
                                            <?php echo $status_info ? $status_info['status_label'] : ucfirst($quote['status']); ?>
                                        </span>
                                    <?php else: ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="update_quote_status">
                                            <input type="hidden" name="quote_id" value="<?php echo $quote['id']; ?>">
                                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 140px;">
                                                <?php 
                                                $quote_statuses = get_statuses('quote');
                                                foreach ($quote_statuses as $status): 
                                                ?>
                                                <option value="<?php echo $status['status_key']; ?>" <?php echo $quote['status'] == $status['status_key'] ? 'selected' : ''; ?>>
                                                    <?php echo $status['status_label']; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($quote['valid_until'])); ?></td>
                            <td>
                            <div class="btn-group" role="group">
                                <a href="/estimate?id=<?php echo $quote['id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank" title="View/Print PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                <?php if (!$quote['invoice_id'] && !in_array($quote['status'], ['abandoned', 'rejected', 'expired', 'cancelled'])): ?>
                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editQuoteModal<?php echo $quote['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Mark this quote as abandoned? It will be excluded from reports.')">
                                    <input type="hidden" name="action" value="update_quote_status">
                                    <input type="hidden" name="quote_id" value="<?php echo $quote['id']; ?>">
                                    <input type="hidden" name="status" value="abandoned">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Mark as Abandoned">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php if (!empty($quote['secure_token'])): ?>
                                <a href="https://wa.me/<?php echo str_replace(['+', ' ', '-'], '', $quote['phone']); ?>?text=Hi <?php echo urlencode($quote['name']); ?>, your quote <?php echo $quote['quote_number']; ?> is ready! Total: Rs <?php echo number_format($quote['total_amount'], 2); ?>. View: <?php echo get_setting('website_url'); ?>/estimate?token=<?php echo $quote['secure_token']; ?>" class="btn btn-sm btn-success" target="_blank">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                                <?php else: ?>
                                <a href="https://wa.me/<?php echo str_replace(['+', ' ', '-'], '', $quote['phone']); ?>?text=Hi <?php echo urlencode($quote['name']); ?>, your quote <?php echo $quote['quote_number']; ?> is ready! Total: Rs <?php echo number_format($quote['total_amount'], 2); ?>" class="btn btn-sm btn-success" target="_blank">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($quote['invoice_id']): ?>
                                    <?php
                                    // Get invoice secure token
                                    $invoice_stmt = $pdo->prepare("SELECT secure_token FROM invoices WHERE id = ?");
                                    $invoice_stmt->execute([$quote['invoice_id']]);
                                    $invoice_token = $invoice_stmt->fetchColumn();
                                    ?>
                                    <?php if (!empty($invoice_token)): ?>
                                    <a href="/invoice?token=<?php echo $invoice_token; ?>" class="btn btn-sm btn-success" target="_blank" title="View Invoice">
                                        <i class="fas fa-receipt"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="btn btn-sm btn-outline-secondary disabled">No Token</span>
                                    <?php endif; ?>
                                <?php elseif ($quote['status'] == 'approved'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="convert_to_invoice">
                                        <input type="hidden" name="quote_id" value="<?php echo $quote['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Convert this quote to invoice?')">
                                            <i class="fas fa-arrow-right"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Quote Modal -->
<div class="modal fade" id="createQuoteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Quote</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_quote">
                    
                    <div class="mb-3">
                        <label class="form-label">Select Lead</label>
                        <select name="lead_id" class="form-select" required>
                            <option value="">Choose a lead...</option>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM leads WHERE status IN ('inquiry', 'survey_scheduled', 'survey_done') ORDER BY created_at DESC");
                            $leads = $stmt->fetchAll();
                            foreach ($leads as $lead):
                            ?>
                            <option value="<?php echo $lead['id']; ?>"><?php echo $lead['name'] . ' - ' . $lead['move_from'] . ' to ' . $lead['move_to']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Quote Items</label>
                        <div id="quote-items">
                            <div class="row mb-2">
                                <div class="col-6">
                                    <input type="text" name="items[0][description]" class="form-control" placeholder="Service description" required>
                                </div>
                                <div class="col-2">
                                    <input type="number" name="items[0][quantity]" class="form-control" placeholder="Qty" value="1" required>
                                </div>
                                <div class="col-3">
                                    <input type="number" name="items[0][rate]" class="form-control" placeholder="Rate" step="0.01" required>
                                </div>
                                <div class="col-1">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">×</button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="addItem()">Add Item</button>
                    </div>
                    
                    <hr>
                    <div class="mb-3 p-3 bg-light border rounded">
                        <h6 class="mb-3">Tax Configuration</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="enable_tax" name="enable_tax" value="1" onchange="toggleTaxFields()">
                            <label class="form-check-label fw-bold" for="enable_tax">
                                ✓ Enable Tax/GST for this quote (includes 5% service charge)
                            </label>
                        </div>
                        <small class="text-muted">Check this box to add service charges and GST to the quote</small>
                    </div>
                    
                    <div class="row" id="tax-fields" style="display: none;">
                        <div class="col-3">
                            <label class="form-label">Service Charge (%)</label>
                            <input type="number" name="service_rate" class="form-control" value="5" step="0.01" onchange="calculateTotal()">
                        </div>
                        <div class="col-3">
                            <label class="form-label">GST Rate (%)</label>
                            <input type="number" name="tax_rate" class="form-control" value="18" step="0.01" onchange="calculateTotal()">
                        </div>
                        <div class="col-3">
                            <label class="form-label">Valid Until</label>
                            <input type="date" name="valid_until" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                        </div>
                        <div class="col-3">
                            <label class="form-label"><strong>Total Amount</strong></label>
                            <input type="number" id="total-preview" class="form-control fw-bold" readonly style="background-color: #e9ecef; font-size: 1.1rem;" placeholder="Rs 0.00">
                        </div>
                    </div>
                    
                    <div class="row" id="no-tax-fields">
                        <div class="col-6">
                            <label class="form-label">Valid Until</label>
                            <input type="date" name="valid_until" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                            <input type="hidden" name="service_rate" value="0">
                            <input type="hidden" name="tax_rate" value="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label"><strong>Total Amount</strong></label>
                            <input type="number" id="total-preview-simple" class="form-control fw-bold" readonly style="background-color: #e9ecef; font-size: 1.1rem;" placeholder="Rs 0.00">
                        </div>
                    </div>
                    
                    <input type="hidden" name="subtotal" id="subtotal" value="0">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Quote</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let itemCount = 1;

function addItem() {
    const container = document.getElementById('quote-items');
    const newItem = document.createElement('div');
    newItem.className = 'row mb-2';
    newItem.innerHTML = `
        <div class="col-6">
            <input type="text" name="items[${itemCount}][description]" class="form-control" placeholder="Service description" required>
        </div>
        <div class="col-2">
            <input type="number" name="items[${itemCount}][quantity]" class="form-control" placeholder="Qty" value="1" required>
        </div>
        <div class="col-3">
            <input type="number" name="items[${itemCount}][rate]" class="form-control" placeholder="Rate" step="0.01" required>
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">×</button>
        </div>
    `;
    container.appendChild(newItem);
    itemCount++;
}

function removeItem(button) {
    button.closest('.row').remove();
    calculateTotal();
}

function calculateTotal() {
    let subtotal = 0;
    const items = document.querySelectorAll('#quote-items .row');
    items.forEach(item => {
        const qty = parseFloat(item.querySelector('input[name*="[quantity]"]').value) || 0;
        const rate = parseFloat(item.querySelector('input[name*="[rate]"]').value) || 0;
        subtotal += qty * rate;
    });
    document.getElementById('subtotal').value = subtotal.toFixed(2);
    
    // Update display if tax preview is needed
    const enableTax = document.getElementById('enable_tax')?.checked;
    const totalPreview = document.getElementById('total-preview');
    const totalPreviewSimple = document.getElementById('total-preview-simple');
    
    if (enableTax) {
        const serviceRate = parseFloat(document.querySelector('input[name="service_rate"]')?.value) || 0;
        const taxRate = parseFloat(document.querySelector('input[name="tax_rate"]')?.value) || 0;
        
        const serviceAmount = (subtotal * serviceRate) / 100;
        const subtotalWithService = subtotal + serviceAmount;
        const taxAmount = (subtotalWithService * taxRate) / 100;
        const totalAmount = subtotalWithService + taxAmount;
        
        if (totalPreview) totalPreview.value = totalAmount.toFixed(2);
    } else {
        if (totalPreviewSimple) totalPreviewSimple.value = subtotal.toFixed(2);
    }
}

// Add event listeners for real-time calculation
document.addEventListener('input', function(e) {
    if (e.target.name && (e.target.name.includes('[quantity]') || e.target.name.includes('[rate]'))) {
        calculateTotal();
    }
});

// Toggle tax fields
function toggleTaxFields() {
    const enableTax = document.getElementById('enable_tax').checked;
    const taxFields = document.getElementById('tax-fields');
    const noTaxFields = document.getElementById('no-tax-fields');
    
    if (enableTax) {
        taxFields.style.display = 'flex';
        noTaxFields.style.display = 'none';
        // Enable tax fields
        taxFields.querySelectorAll('input').forEach(input => input.disabled = false);
    } else {
        taxFields.style.display = 'none';
        noTaxFields.style.display = 'flex';
        // Reset tax values when disabled
        document.querySelector('input[name="service_rate"]').value = 0;
        document.querySelector('input[name="tax_rate"]').value = 0;
    }
}
</script>

<!-- Edit Quote Modals -->
<?php foreach ($quotes as $quote): ?>
<?php if (!$quote['invoice_id']): // Only show edit for quotes without invoices ?>
<?php $quote_items = json_decode($quote['items'], true); ?>
<div class="modal fade" id="editQuoteModal<?php echo $quote['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Estimate - <?php echo $quote['quote_number']; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_quote">
                    <input type="hidden" name="quote_id" value="<?php echo $quote['id']; ?>">
                    <input type="hidden" name="subtotal" value="<?php echo $quote['subtotal']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Customer: <strong><?php echo $quote['name']; ?></strong></label>
                    </div>
                    
                    <hr>
                    <div class="mb-3 p-3 bg-light border rounded">
                        <h6 class="mb-3">Tax Configuration</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_enable_tax_<?php echo $quote['id']; ?>" name="enable_tax" value="1" <?php echo (isset($quote['tax_enabled']) && $quote['tax_enabled']) ? 'checked' : ''; ?> onchange="toggleEditTaxFields(<?php echo $quote['id']; ?>)">
                            <label class="form-check-label fw-bold" for="edit_enable_tax_<?php echo $quote['id']; ?>">
                                ✓ Enable Tax/GST for this quote (includes 5% service charge)
                            </label>
                        </div>
                        <small class="text-muted">Check this box to add service charges and GST to the quote</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Estimate Items</label>
                        <div id="edit-quote-items-<?php echo $quote['id']; ?>">
                            <?php foreach ($quote_items as $index => $item): ?>
                            <div class="row mb-2 item-row">
                                <div class="col-6">
                                    <input type="text" name="items[<?php echo $index; ?>][description]" class="form-control" value="<?php echo htmlspecialchars($item['description']); ?>" required>
                                </div>
                                <div class="col-2">
                                    <input type="number" name="items[<?php echo $index; ?>][quantity]" class="form-control" value="<?php echo $item['quantity']; ?>" required>
                                </div>
                                <div class="col-3">
                                    <input type="number" name="items[<?php echo $index; ?>][rate]" class="form-control" value="<?php echo $item['rate']; ?>" step="0.01" required>
                                </div>
                                <div class="col-1">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeEditItem(this, <?php echo $quote['id']; ?>)">×</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="addEditItem(<?php echo $quote['id']; ?>)">Add Item</button>
                    </div>
                    
                    <div class="row" id="edit-tax-fields-<?php echo $quote['id']; ?>" style="display: <?php echo (isset($quote['tax_enabled']) && $quote['tax_enabled']) ? 'flex' : 'none'; ?>;">
                        <div class="col-3">
                            <label class="form-label">Service Charge (%)</label>
                            <input type="number" name="service_rate" id="edit-service-rate-<?php echo $quote['id']; ?>" class="form-control" value="<?php echo $quote['service_rate'] ?? 5; ?>" step="0.01" onchange="calculateEditTotal(<?php echo $quote['id']; ?>)">
                        </div>
                        <div class="col-3">
                            <label class="form-label">GST Rate (%)</label>
                            <input type="number" name="tax_rate" id="edit-tax-rate-<?php echo $quote['id']; ?>" class="form-control" value="<?php echo $quote['tax_rate']; ?>" step="0.01" onchange="calculateEditTotal(<?php echo $quote['id']; ?>)">
                        </div>
                        <div class="col-3">
                            <label class="form-label">Subtotal</label>
                            <input type="number" name="subtotal" id="edit-subtotal-<?php echo $quote['id']; ?>" class="form-control" value="<?php echo $quote['subtotal']; ?>" readonly>
                        </div>
                        <div class="col-3">
                            <label class="form-label">Service Amount</label>
                            <input type="number" id="edit-service-amount-<?php echo $quote['id']; ?>" class="form-control" value="<?php echo $quote['service_amount'] ?? 0; ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="row mt-2" id="edit-tax-totals-<?php echo $quote['id']; ?>" style="display: <?php echo (isset($quote['tax_enabled']) && $quote['tax_enabled']) ? 'flex' : 'none'; ?>;">
                        <div class="col-6">
                            <label class="form-label">Tax Amount</label>
                            <input type="number" id="edit-tax-amount-<?php echo $quote['id']; ?>" class="form-control" value="<?php echo $quote['tax_amount']; ?>" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label"><strong>Total Amount</strong></label>
                            <input type="number" id="edit-total-amount-<?php echo $quote['id']; ?>" class="form-control fw-bold" value="<?php echo $quote['total_amount']; ?>" readonly style="background-color: #e9ecef; font-size: 1.1rem;">
                        </div>
                    </div>
                    
                    <div class="row" id="edit-no-tax-fields-<?php echo $quote['id']; ?>" style="display: <?php echo (isset($quote['tax_enabled']) && $quote['tax_enabled']) ? 'none' : 'flex'; ?>;">
                        <div class="col-6">
                            <label class="form-label">Subtotal</label>
                            <input type="number" name="subtotal" id="edit-subtotal-simple-<?php echo $quote['id']; ?>" class="form-control" value="<?php echo $quote['subtotal']; ?>" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label"><strong>Total Amount</strong></label>
                            <input type="number" id="edit-total-simple-<?php echo $quote['id']; ?>" class="form-control fw-bold" value="<?php echo $quote['subtotal']; ?>" readonly style="background-color: #e9ecef; font-size: 1.1rem;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Estimate</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<script>
let editItemCounters = {};

// Initialize counters for each quote
<?php foreach ($quotes as $quote): ?>
<?php if (!$quote['invoice_id']): ?>
editItemCounters[<?php echo $quote['id']; ?>] = <?php echo count(json_decode($quote['items'], true)); ?>;
<?php endif; ?>
<?php endforeach; ?>

function addEditItem(quoteId) {
    const container = document.getElementById(`edit-quote-items-${quoteId}`);
    const newItem = document.createElement('div');
    newItem.className = 'row mb-2 item-row';
    newItem.innerHTML = `
        <div class="col-6">
            <input type="text" name="items[${editItemCounters[quoteId]}][description]" class="form-control" placeholder="Service description" required>
        </div>
        <div class="col-2">
            <input type="number" name="items[${editItemCounters[quoteId]}][quantity]" class="form-control" placeholder="Qty" value="1" required>
        </div>
        <div class="col-3">
            <input type="number" name="items[${editItemCounters[quoteId]}][rate]" class="form-control" placeholder="Rate" step="0.01" required>
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-sm btn-danger" onclick="removeEditItem(this, ${quoteId})">×</button>
        </div>
    `;
    container.appendChild(newItem);
    editItemCounters[quoteId]++;
    calculateEditTotal(quoteId);
}

function removeEditItem(button, quoteId) {
    button.closest('.row').remove();
    calculateEditTotal(quoteId);
}

function toggleEditTaxFields(quoteId) {
    const enableTax = document.getElementById(`edit_enable_tax_${quoteId}`).checked;
    const taxFields = document.getElementById(`edit-tax-fields-${quoteId}`);
    const taxTotals = document.getElementById(`edit-tax-totals-${quoteId}`);
    const noTaxFields = document.getElementById(`edit-no-tax-fields-${quoteId}`);
    
    if (enableTax) {
        taxFields.style.display = 'flex';
        taxTotals.style.display = 'flex';
        noTaxFields.style.display = 'none';
        // Set default values when enabling
        document.getElementById(`edit-service-rate-${quoteId}`).value = 5;
        document.getElementById(`edit-tax-rate-${quoteId}`).value = 18;
    } else {
        taxFields.style.display = 'none';
        taxTotals.style.display = 'none';
        noTaxFields.style.display = 'flex';
        // Reset values when disabling
        document.getElementById(`edit-service-rate-${quoteId}`).value = 0;
        document.getElementById(`edit-tax-rate-${quoteId}`).value = 0;
    }
    calculateEditTotal(quoteId);
}

function calculateEditTotal(quoteId) {
    let subtotal = 0;
    const items = document.querySelectorAll(`#edit-quote-items-${quoteId} .item-row`);
    items.forEach(item => {
        const qty = parseFloat(item.querySelector('input[name*="[quantity]"]').value) || 0;
        const rate = parseFloat(item.querySelector('input[name*="[rate]"]').value) || 0;
        subtotal += qty * rate;
    });
    
    const enableTax = document.getElementById(`edit_enable_tax_${quoteId}`).checked;
    
    // Update subtotal in hidden field for form submission
    const subtotalField = document.querySelector(`#editQuoteModal${quoteId} input[name="subtotal"]`);
    if (subtotalField) {
        subtotalField.value = subtotal.toFixed(2);
    }
    
    if (enableTax) {
        const serviceRate = parseFloat(document.getElementById(`edit-service-rate-${quoteId}`).value) || 0;
        const taxRate = parseFloat(document.getElementById(`edit-tax-rate-${quoteId}`).value) || 0;
        
        const serviceAmount = (subtotal * serviceRate) / 100;
        const subtotalWithService = subtotal + serviceAmount;
        const taxAmount = (subtotalWithService * taxRate) / 100;
        const totalAmount = subtotalWithService + taxAmount;
        
        document.getElementById(`edit-subtotal-${quoteId}`).value = subtotal.toFixed(2);
        document.getElementById(`edit-service-amount-${quoteId}`).value = serviceAmount.toFixed(2);
        document.getElementById(`edit-tax-amount-${quoteId}`).value = taxAmount.toFixed(2);
        document.getElementById(`edit-total-amount-${quoteId}`).value = totalAmount.toFixed(2);
    } else {
        document.getElementById(`edit-subtotal-simple-${quoteId}`).value = subtotal.toFixed(2);
        document.getElementById(`edit-total-simple-${quoteId}`).value = subtotal.toFixed(2);
    }
}

// Add event listeners for real-time calculation on edit modals
document.addEventListener('input', function(e) {
    if (e.target.name && (e.target.name.includes('[quantity]') || e.target.name.includes('[rate]')) || e.target.id && e.target.id.includes('edit-tax-rate')) {
        // Extract quote ID from the modal
        const modal = e.target.closest('.modal');
        if (modal && modal.id.includes('editQuoteModal')) {
            const quoteId = modal.id.replace('editQuoteModal', '');
            calculateEditTotal(quoteId);
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>