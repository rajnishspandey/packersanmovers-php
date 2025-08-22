<?php
require_once 'config.php';
require_permission('invoices', 'read');

$page_title = 'Invoices Management';

// Handle invoice actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'abandon_invoice') {
            require_permission('invoices', 'update');
            $invoice_id = $_POST['invoice_id'];
            
            // Mark invoice as abandoned
            $stmt = $pdo->prepare("UPDATE invoices SET status = 'abandoned' WHERE id = ?");
            $stmt->execute([$invoice_id]);
            
            $_SESSION['message'] = 'Invoice marked as abandoned successfully!';
            redirect('/invoices');
        }
    }
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'create_supplementary') {
            $parent_invoice_id = $_POST['parent_invoice_id'];
            $items = json_decode($_POST['items'], true);
            
            // Get parent invoice details
            $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
            $stmt->execute([$parent_invoice_id]);
            $parent_invoice = $stmt->fetch();
            
            // Calculate totals with service charges
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['quantity'] * $item['rate'];
            }
            
            $service_rate = $parent_invoice['service_rate'] ?? 0;
            $service_amount = ($subtotal * $service_rate) / 100;
            $subtotal_with_service = $subtotal + $service_amount;
            
            $tax_rate = $parent_invoice['tax_rate'];
            $tax_amount = ($subtotal_with_service * $tax_rate) / 100;
            $total_amount = $subtotal_with_service + $tax_amount;
            
            // Generate supplementary invoice number
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE parent_invoice_id = ?");
            $stmt->execute([$parent_invoice_id]);
            $supp_count = $stmt->fetchColumn() + 1;
            $invoice_number = $parent_invoice['invoice_number'] . '-S' . $supp_count;
            
            // Create supplementary invoice with service charges
            $stmt = $pdo->prepare("INSERT INTO invoices (quote_id, invoice_number, parent_invoice_id, invoice_type, items, subtotal, service_rate, service_amount, tax_rate, tax_amount, total_amount, status, due_date, created_by) VALUES (?, ?, ?, 'supplementary', ?, ?, ?, ?, ?, ?, ?, 'linked', ?, ?)");
            $stmt->execute([
                $parent_invoice['quote_id'],
                $invoice_number,
                $parent_invoice_id,
                json_encode($items),
                $subtotal,
                $service_rate,
                $service_amount,
                $tax_rate,
                $tax_amount,
                $total_amount,
                $parent_invoice['due_date'],
                $_SESSION['user_id']
            ]);
            
            $_SESSION['message'] = 'Supplementary invoice created successfully!';
            redirect('/invoices');
        } elseif ($_POST['action'] == 'add_payment') {
            $invoice_id = $_POST['invoice_id'];
            $payment_amount = $_POST['payment_amount'];
            $payment_notes = sanitize_input($_POST['payment_notes']);
            
            // Get current invoice details with quote and lead info
            $stmt = $pdo->prepare("SELECT i.*, q.id as quote_id, q.lead_id FROM invoices i JOIN quotes q ON i.quote_id = q.id WHERE i.id = ?");
            $stmt->execute([$invoice_id]);
            $invoice = $stmt->fetch();
            
            // Calculate consolidated totals for related invoices (exclude abandoned)
            $parent_id = $invoice['parent_invoice_id'] ?: $invoice_id;
            $stmt = $pdo->prepare("SELECT SUM(total_amount) as total, SUM(paid_amount) as paid FROM invoices WHERE (id = ? OR parent_invoice_id = ?) AND status != 'abandoned'");
            $stmt->execute([$parent_id, $parent_id]);
            $consolidated = $stmt->fetch();
            
            $new_paid_amount = $invoice['paid_amount'] + $payment_amount;
            
            // Update the specific invoice payment
            $stmt = $pdo->prepare("UPDATE invoices SET paid_amount = ? WHERE id = ?");
            $stmt->execute([$new_paid_amount, $invoice_id]);
            
            // Record payment in payments table
            $stmt = $pdo->prepare("INSERT INTO payments (invoice_id, amount, payment_date, notes, created_by) VALUES (?, ?, CURRENT_DATE, ?, ?)");
            $stmt->execute([$invoice_id, $payment_amount, $payment_notes, $_SESSION['user_id']]);
            
            // Recalculate consolidated totals after payment (exclude abandoned)
            $stmt = $pdo->prepare("SELECT SUM(total_amount) as total, SUM(paid_amount) as paid FROM invoices WHERE (id = ? OR parent_invoice_id = ?) AND status != 'abandoned'");
            $stmt->execute([$parent_id, $parent_id]);
            $new_consolidated = $stmt->fetch();
            
            // Update status for all related invoices based on consolidated payment
            if ($new_consolidated['paid'] >= $new_consolidated['total']) {
                // All invoices fully paid
                $stmt = $pdo->prepare("UPDATE invoices SET status = 'paid' WHERE (id = ? OR parent_invoice_id = ?) AND status != 'abandoned'");
                $stmt->execute([$parent_id, $parent_id]);
                
                // Update quote and lead status
                $stmt = $pdo->prepare("UPDATE quotes SET status = 'approved' WHERE id = ?");
                $stmt->execute([$invoice['quote_id']]);
                
                $stmt = $pdo->prepare("UPDATE leads SET status = 'completed' WHERE id = ?");
                $stmt->execute([$invoice['lead_id']]);
                
                $_SESSION['message'] = 'Payment recorded successfully! All related invoices are now fully paid. Quote and Lead status updated to completed.';
            } else {
                // Partial payment - update status to partial for all related invoices
                $stmt = $pdo->prepare("UPDATE invoices SET status = 'partial' WHERE (id = ? OR parent_invoice_id = ?) AND status != 'abandoned' AND paid_amount > 0");
                $stmt->execute([$parent_id, $parent_id]);
                
                $_SESSION['message'] = 'Payment recorded successfully! Remaining balance: Rs ' . number_format($new_consolidated['total'] - $new_consolidated['paid'], 2);
            }
            
            redirect('/invoices');
        }
    }
}

// Get parent invoices with their children
$stmt = $pdo->query("SELECT i.*, q.quote_number, l.name, l.email, l.phone, l.move_from, l.move_to
                     FROM invoices i 
                     JOIN quotes q ON i.quote_id = q.id 
                     JOIN leads l ON q.lead_id = l.id 
                     WHERE i.parent_invoice_id IS NULL
                     ORDER BY i.created_at DESC");
$parent_invoices = $stmt->fetchAll();

// Get all supplementary invoices grouped by parent
$stmt = $pdo->query("SELECT * FROM invoices WHERE parent_invoice_id IS NOT NULL ORDER BY created_at");
$supplementary_invoices = $stmt->fetchAll();
$grouped_supplementary = [];
foreach ($supplementary_invoices as $supp) {
    $grouped_supplementary[$supp['parent_invoice_id']][] = $supp;
}

// Function to calculate consolidated totals for parent + children (exclude abandoned)
function get_consolidated_totals($parent_invoice, $children) {
    $total_amount = $parent_invoice['status'] != 'abandoned' ? $parent_invoice['total_amount'] : 0;
    $total_paid = $parent_invoice['status'] != 'abandoned' ? $parent_invoice['paid_amount'] : 0;
    
    foreach ($children as $child) {
        if ($child['status'] != 'abandoned') {
            $total_amount += $child['total_amount'];
            $total_paid += $child['paid_amount'];
        }
    }
    
    return [
        'total_amount' => $total_amount,
        'total_paid' => $total_paid,
        'balance' => $total_amount - $total_paid
    ];
}

// Function to get related invoices (parent + all supplementary)
function get_related_invoices($pdo, $invoice_id, $parent_invoice_id = null) {
    if ($parent_invoice_id) {
        // This is a supplementary invoice, get parent + all siblings
        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? OR parent_invoice_id = ? ORDER BY created_at");
        $stmt->execute([$parent_invoice_id, $parent_invoice_id]);
    } else {
        // This might be a parent invoice, get it + all supplementary
        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? OR parent_invoice_id = ? ORDER BY created_at");
        $stmt->execute([$invoice_id, $invoice_id]);
    }
    return $stmt->fetchAll();
}

// Function to calculate total outstanding for related invoices
function get_total_outstanding($related_invoices) {
    $total_amount = 0;
    $total_paid = 0;
    foreach ($related_invoices as $inv) {
        $total_amount += $inv['total_amount'];
        $total_paid += $inv['paid_amount'];
    }
    return $total_amount - $total_paid;
}

include 'includes/header.php';
?>

<div class="container-fluid my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">Dashboard</a></li>
            <li class="breadcrumb-item active">Invoices</li>
        </ol>
    </nav>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-receipt"></i> Invoices Management</h1>
        <div>
            <a href="/leads" class="btn btn-outline-info"><i class="fas fa-users"></i> Leads</a>
            <a href="/quotes" class="btn btn-outline-primary"><i class="fas fa-file-alt"></i> Quotes</a>
            <a href="/analytics" class="btn btn-success"><i class="fas fa-chart-bar"></i> Analytics</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Type</th>
                            <th>Quote #</th>
                            <th>Customer</th>
                            <th>Route</th>
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parent_invoices as $invoice): ?>
                        <?php 
                        $children = $grouped_supplementary[$invoice['id']] ?? [];
                        $consolidated = get_consolidated_totals($invoice, $children);
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo $invoice['invoice_number']; ?></strong>
                                <?php if (count($children) > 0): ?>
                                <br><small class="text-success">+ <?php echo count($children); ?> supplementary</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-primary">Main Invoice</span>
                                <?php if (count($children) > 0): ?>
                                <br><span class="badge bg-info">Consolidated</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $invoice['quote_number']; ?></td>
                            <td>
                                <?php echo $invoice['name']; ?><br>
                                <small class="text-muted"><?php echo $invoice['email']; ?></small>
                            </td>
                            <td><?php echo $invoice['move_from'] . ' → ' . $invoice['move_to']; ?></td>
                            <td>
                                Rs <?php echo number_format($consolidated['total_amount'], 2); ?>
                                <?php if (count($children) > 0): ?>
                                <br><small class="text-muted">Base: Rs <?php echo number_format($invoice['total_amount'], 2); ?> + Extra: Rs <?php echo number_format($consolidated['total_amount'] - $invoice['total_amount'], 2); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>Rs <?php echo number_format($consolidated['total_paid'], 2); ?></td>
                            <td>
                                <?php 
                                $status = $consolidated['balance'] <= 0 ? 'paid' : ($consolidated['total_paid'] > 0 ? 'partial' : 'pending');
                                ?>
                                <?php 
                                $status_info = get_status_info('invoice', $invoice['status']);
                                if ($status_info): 
                                ?>
                                <span class="badge bg-<?php echo $status_info['status_color']; ?>">
                                    <?php echo $status_info['status_label']; ?>
                                </span>
                                <?php else: ?>
                                <span class="badge bg-secondary">
                                    <?php echo ucfirst($invoice['status']); ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($consolidated['balance'] > 0): ?>
                                <br><small class="text-muted">Balance: Rs <?php echo number_format($consolidated['balance'], 2); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($invoice['due_date'])); ?></td>
                            <td>
                                <?php if (!empty($invoice['secure_token'])): ?>
                                <?php if (!empty($invoice['secure_token'])): ?>
                                <a href="/invoice?token=<?php echo $invoice['secure_token']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">View</a>
                                <a href="https://wa.me/<?php echo str_replace(['+', ' ', '-'], '', $invoice['phone']); ?>?text=Hi <?php echo urlencode($invoice['name']); ?>, your invoice <?php echo $invoice['invoice_number']; ?> is ready! Amount: Rs <?php echo number_format($consolidated['total_amount'], 2); ?>. View: <?php echo get_setting('website_url'); ?>/invoice?token=<?php echo $invoice['secure_token']; ?>" class="btn btn-sm btn-success" target="_blank">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                                <?php else: ?>
                                <span class="btn btn-sm btn-outline-secondary disabled">No Token</span>
                                <a href="https://wa.me/<?php echo str_replace(['+', ' ', '-'], '', $invoice['phone']); ?>?text=Hi <?php echo urlencode($invoice['name']); ?>, your invoice <?php echo $invoice['invoice_number']; ?> is ready! Amount: Rs <?php echo number_format($consolidated['total_amount'], 2); ?>" class="btn btn-sm btn-success" target="_blank">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                                <?php endif; ?>
                                <?php else: ?>
                                <span class="btn btn-sm btn-outline-secondary disabled">No Token</span>
                                <a href="https://wa.me/<?php echo str_replace(['+', ' ', '-'], '', $invoice['phone']); ?>?text=Hi <?php echo urlencode($invoice['name']); ?>, your invoice <?php echo $invoice['invoice_number']; ?> is ready! Amount: Rs <?php echo number_format($consolidated['total_amount'], 2); ?>" class="btn btn-sm btn-success" target="_blank">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($invoice['status'] != 'paid' && $invoice['status'] != 'abandoned'): ?>
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal<?php echo $invoice['id']; ?>">Add Payment</button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#paymentsModal<?php echo $invoice['id']; ?>">View Payments</button>
                                <?php if ($consolidated['total_paid'] > 0 && $consolidated['balance'] > 0): ?>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#suppModal<?php echo $invoice['id']; ?>">Add Extra Items</button>
                                <?php endif; ?>
                                <?php if (count($children) > 0): ?>
                                <button class="btn btn-sm btn-outline-info" onclick="toggleChildren(<?php echo $invoice['id']; ?>)">Toggle Details</button>
                                <?php endif; ?>
                                <a href="/expenses?invoice_id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-outline-secondary">Expenses</a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Mark this invoice as abandoned? It will be excluded from reports.')">
                                    <input type="hidden" name="action" value="abandon_invoice">
                                    <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Mark as Abandoned">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        
                        <!-- Child/Supplementary Invoices -->
                        <?php if (isset($grouped_supplementary[$invoice['id']])): ?>
                        <?php foreach ($grouped_supplementary[$invoice['id']] as $child): ?>
                        <tr class="child-row child-<?php echo $invoice['id']; ?>" style="display: none; background-color: #f8f9fa;">
                            <td style="padding-left: 30px;">
                                <i class="fas fa-level-up-alt fa-rotate-90 text-muted"></i>
                                <?php echo $child['invoice_number']; ?>
                                <br><small class="text-muted">Added: <?php echo date('M d, Y', strtotime($child['created_at'])); ?></small>
                            </td>
                            <td><span class="badge bg-info">Supplementary</span></td>
                            <td colspan="2"><small class="text-muted">Extra items for main invoice</small></td>
                            <td>Rs <?php echo number_format($child['total_amount'], 2); ?></td>
                            <td>Rs <?php echo number_format($child['paid_amount'], 2); ?></td>
                            <td>
                                <span class="badge bg-secondary">
                                    Linked to Parent
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($child['due_date'])); ?></td>
                            <td>
                                <?php if (!empty($child['secure_token'])): ?>
                                <?php if (!empty($child['secure_token'])): ?>
                                <a href="/invoice?token=<?php echo $child['secure_token']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">View</a>
                                <?php else: ?>
                                <span class="btn btn-sm btn-outline-secondary disabled">No Token</span>
                                <?php endif; ?>
                                <?php else: ?>
                                <span class="btn btn-sm btn-outline-secondary disabled">No Token</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Supplementary Invoice Modals -->
<?php foreach ($parent_invoices as $invoice): ?>
<?php 
$children = $grouped_supplementary[$invoice['id']] ?? [];
$consolidated = get_consolidated_totals($invoice, $children);
if ($consolidated['total_paid'] > 0 && $consolidated['balance'] > 0): 
?>
<div class="modal fade" id="suppModal<?php echo $invoice['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Extra Items - <?php echo $invoice['invoice_number']; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_supplementary">
                    <input type="hidden" name="parent_invoice_id" value="<?php echo $invoice['id']; ?>">
                    <input type="hidden" name="items" id="suppItems<?php echo $invoice['id']; ?>">
                    
                    <div class="alert alert-info">
                        <strong>Current Total:</strong> Rs <?php echo number_format($consolidated['total_amount'], 2); ?> 
                        (Paid: Rs <?php echo number_format($consolidated['total_paid'], 2); ?>, Balance: Rs <?php echo number_format($consolidated['balance'], 2); ?>)
                        <?php if (count($children) > 0): ?>
                        <br><small>Includes <?php echo count($children); ?> supplementary invoice(s)</small>
                        <?php endif; ?>
                    </div>
                    
                    <div id="suppItemsList<?php echo $invoice['id']; ?>">
                        <div class="row mb-2">
                            <div class="col-md-5"><strong>Description</strong></div>
                            <div class="col-md-2"><strong>Qty</strong></div>
                            <div class="col-md-3"><strong>Rate</strong></div>
                            <div class="col-md-2"><strong>Amount</strong></div>
                        </div>
                        <div class="supp-item-row">
                            <div class="row mb-2">
                                <div class="col-md-5">
                                    <input type="text" class="form-control item-desc" placeholder="Extra packing materials" required>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control item-qty" value="1" min="1" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" class="form-control item-rate" step="0.01" placeholder="0.00" required>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" class="form-control item-amount" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSuppItem(<?php echo $invoice['id']; ?>)">Add Another Item</button>
                    
                    <hr>
                    <div class="row">
                        <div class="col-md-8"></div>
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between">
                                <strong>Subtotal:</strong>
                                <span id="suppSubtotal<?php echo $invoice['id']; ?>">Rs 0.00</span>
                            </div>
                            <?php if (isset($invoice['service_rate']) && $invoice['service_rate'] > 0): ?>
                            <div class="d-flex justify-content-between">
                                <strong>Service Charge (<?php echo $invoice['service_rate']; ?>%):</strong>
                                <span id="suppService<?php echo $invoice['id']; ?>">Rs 0.00</span>
                            </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between">
                                <strong>Tax (<?php echo $invoice['tax_rate']; ?>%):</strong>
                                <span id="suppTax<?php echo $invoice['id']; ?>">Rs 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <strong>Total:</strong>
                                <span id="suppTotal<?php echo $invoice['id']; ?>">Rs 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Create Supplementary Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>



<!-- Payment Modals -->
<?php foreach ($parent_invoices as $invoice): ?>
<?php 
$children = $grouped_supplementary[$invoice['id']] ?? [];
$consolidated = get_consolidated_totals($invoice, $children);
?>
<div class="modal fade" id="paymentModal<?php echo $invoice['id']; ?>" tabindex="-1" aria-labelledby="paymentModalLabel<?php echo $invoice['id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel<?php echo $invoice['id']; ?>">Mark Invoice as Paid</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_payment">
                    <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Invoice Number</label>
                        <input type="text" class="form-control" value="<?php echo $invoice['invoice_number']; ?>" readonly>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Total Amount (Consolidated)</label>
                                <input type="text" class="form-control" value="Rs <?php echo number_format($consolidated['total_amount'], 2); ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Already Paid</label>
                                <input type="text" class="form-control" value="Rs <?php echo number_format($consolidated['total_paid'], 2); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Amount</label>
                        <div class="input-group">
                            <input type="number" name="payment_amount" id="paymentAmount<?php echo $invoice['id']; ?>" class="form-control" step="0.01" min="1" max="<?php echo $consolidated['balance']; ?>" required>
                            <button type="button" class="btn btn-outline-success" onclick="setFullPayment(<?php echo $invoice['id']; ?>, <?php echo $consolidated['balance']; ?>)">Full Payment</button>
                        </div>
                        <small class="text-muted">Total Outstanding: Rs <?php echo number_format($consolidated['balance'], 2); ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Notes</label>
                        <textarea name="payment_notes" class="form-control" rows="2" placeholder="Payment method, reference number, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- View Payments Modals -->
<?php foreach ($parent_invoices as $invoice): ?>
<?php
$children = $grouped_supplementary[$invoice['id']] ?? [];
$consolidated = get_consolidated_totals($invoice, $children);
// Get payments for this invoice and all children
$all_invoice_ids = [$invoice['id']];
foreach ($children as $child) {
    $all_invoice_ids[] = $child['id'];
}
$placeholders = str_repeat('?,', count($all_invoice_ids) - 1) . '?';
$stmt = $pdo->prepare("SELECT p.*, u.username, i.invoice_number FROM payments p 
                      LEFT JOIN users u ON p.created_by = u.id 
                      LEFT JOIN invoices i ON p.invoice_id = i.id
                      WHERE p.invoice_id IN ($placeholders) ORDER BY p.created_at DESC");
$stmt->execute($all_invoice_ids);
$payments = $stmt->fetchAll();
?>
<div class="modal fade" id="paymentsModal<?php echo $invoice['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment History - <?php echo $invoice['invoice_number']; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Total Amount:</strong> Rs <?php echo number_format($consolidated['total_amount'], 2); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Paid Amount:</strong> Rs <?php echo number_format($consolidated['total_paid'], 2); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Balance:</strong> Rs <?php echo number_format($consolidated['balance'], 2); ?>
                    </div>
                </div>
                
                <?php if ($payments): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Invoice</th>
                                <th>Amount</th>
                                <th>Notes</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                <td><small><?php echo $payment['invoice_number']; ?></small></td>
                                <td>Rs <?php echo number_format($payment['amount'], 2); ?></td>
                                <td><?php echo $payment['notes'] ?: '-'; ?></td>
                                <td><?php echo $payment['username'] ?: 'System'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">No payments recorded yet.</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
// Toggle child invoices visibility
function toggleChildren(parentId) {
    const children = document.querySelectorAll('.child-' + parentId);
    children.forEach(child => {
        child.style.display = child.style.display === 'none' ? 'table-row' : 'none';
    });
}

// Set full payment amount
function setFullPayment(invoiceId, amount) {
    document.getElementById('paymentAmount' + invoiceId).value = amount.toFixed(2);
}

// Supplementary invoice functions
function addSuppItem(invoiceId) {
    const container = document.getElementById('suppItemsList' + invoiceId);
    const newRow = document.createElement('div');
    newRow.className = 'supp-item-row';
    newRow.innerHTML = `
        <div class="row mb-2">
            <div class="col-md-5">
                <input type="text" class="form-control item-desc" placeholder="Item description" required>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control item-qty" value="1" min="1" required>
            </div>
            <div class="col-md-3">
                <input type="number" class="form-control item-rate" step="0.01" placeholder="0.00" required>
            </div>
            <div class="col-md-2">
                <div class="input-group">
                    <input type="text" class="form-control item-amount" readonly>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.supp-item-row').remove(); calculateSuppTotal(${invoiceId});">×</button>
                </div>
            </div>
        </div>
    `;
    container.appendChild(newRow);
    
    // Add event listeners to new inputs
    const qtyInput = newRow.querySelector('.item-qty');
    const rateInput = newRow.querySelector('.item-rate');
    qtyInput.addEventListener('input', () => calculateSuppTotal(invoiceId));
    rateInput.addEventListener('input', () => calculateSuppTotal(invoiceId));
}

function calculateSuppTotal(invoiceId) {
    const container = document.getElementById('suppItemsList' + invoiceId);
    const rows = container.querySelectorAll('.supp-item-row');
    let subtotal = 0;
    const items = [];
    
    rows.forEach(row => {
        const desc = row.querySelector('.item-desc').value;
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const rate = parseFloat(row.querySelector('.item-rate').value) || 0;
        const amount = qty * rate;
        
        row.querySelector('.item-amount').value = 'Rs ' + amount.toFixed(2);
        subtotal += amount;
        
        if (desc && qty && rate) {
            items.push({description: desc, quantity: qty, rate: rate, amount: amount});
        }
    });
    
    // Get rates from parent invoice
    const serviceRate = <?php echo $parent_invoices[0]['service_rate'] ?? 0; ?>;
    const taxRate = <?php echo $parent_invoices[0]['tax_rate'] ?? 18; ?>;
    
    // Calculate service charge
    const serviceAmount = (subtotal * serviceRate) / 100;
    const subtotalWithService = subtotal + serviceAmount;
    
    // Calculate tax on subtotal + service
    const taxAmount = (subtotalWithService * taxRate) / 100;
    const total = subtotalWithService + taxAmount;
    
    document.getElementById('suppSubtotal' + invoiceId).textContent = 'Rs ' + subtotal.toFixed(2);
    if (document.getElementById('suppService' + invoiceId)) {
        document.getElementById('suppService' + invoiceId).textContent = 'Rs ' + serviceAmount.toFixed(2);
    }
    document.getElementById('suppTax' + invoiceId).textContent = 'Rs ' + taxAmount.toFixed(2);
    document.getElementById('suppTotal' + invoiceId).textContent = 'Rs ' + total.toFixed(2);
    document.getElementById('suppItems' + invoiceId).value = JSON.stringify(items);
}

// Ensure modals are properly initialized
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all modals
    var modals = document.querySelectorAll('.modal');
    modals.forEach(function(modal) {
        new bootstrap.Modal(modal, {
            backdrop: 'static',
            keyboard: false
        });
    });
    
    // Add event listeners to supplementary invoice inputs
    document.querySelectorAll('.supp-item-row').forEach(row => {
        const invoiceId = row.closest('.modal').id.replace('suppModal', '');
        row.querySelector('.item-qty').addEventListener('input', () => calculateSuppTotal(invoiceId));
        row.querySelector('.item-rate').addEventListener('input', () => calculateSuppTotal(invoiceId));
    });
    
    // Prevent modal backdrop issues
    document.addEventListener('hidden.bs.modal', function (event) {
        document.body.classList.remove('modal-open');
        var backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>