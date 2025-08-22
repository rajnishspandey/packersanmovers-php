<?php
require_once 'config.php';
require_once 'includes/mail.php';
require_permission('leads', 'read');

$page_title = 'Leads Management';

// Handle status updates and auto-quote creation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'update_status') {
            require_permission('leads', 'update');
            $lead_id = $_POST['lead_id'];
            $new_status = $_POST['status'];
            
            // Get current lead status for logging
            $stmt = $pdo->prepare("SELECT status FROM leads WHERE id = ?");
            $stmt->execute([$lead_id]);
            $old_status = $stmt->fetchColumn();
            
            // Update lead status
            $stmt = $pdo->prepare("UPDATE leads SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $lead_id]);
            
            // Debug logging
            error_log("Lead status changed from $old_status to $new_status for lead ID: $lead_id");
            
            // Handle draft status - create estimate
            if ($new_status == 'draft') {
                // First check if there's already a draft quote to send
                $stmt = $pdo->prepare("SELECT * FROM quotes WHERE lead_id = ? AND status = 'draft' ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$lead_id]);
                $draft_quote = $stmt->fetch();
                
                if ($draft_quote) {
                    // Send the existing draft quote
                    $stmt = $pdo->prepare("UPDATE quotes SET status = 'sent' WHERE id = ?");
                    $stmt->execute([$draft_quote['id']]);
                    
                    // Get lead details for email
                    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
                    $stmt->execute([$lead_id]);
                    $lead = $stmt->fetch();
                    
                    // Send quote email to customer only if email exists
                    if (!empty($draft_quote['secure_token']) && $lead && !empty($lead['email'])) {
                        try {
                            send_quote_email($draft_quote, $lead, 'sent');
                        } catch (Exception $e) {
                            error_log("Quote email failed: " . $e->getMessage());
                        }
                    }
                    
                    $_SESSION['message'] = "Lead status updated and draft estimate {$draft_quote['quote_number']} sent to customer!";
                } else {
                error_log("Attempting to create estimate for lead ID: $lead_id");
                // Check if quote already exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM quotes WHERE lead_id = ?");
                $stmt->execute([$lead_id]);
                
                if ($stmt->fetchColumn() == 0) {
                    // Get lead details
                    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
                    $stmt->execute([$lead_id]);
                    $lead = $stmt->fetch();
                    
                    // Create default quote items based on home size
                    $default_items = [];
                    switch ($lead['home_size']) {
                        case '1 BHK':
                            $default_items = [
                                ['description' => 'Packing & Moving Service (1 BHK)', 'quantity' => 1, 'rate' => 8000],
                                ['description' => 'Loading & Unloading', 'quantity' => 1, 'rate' => 2000],
                                ['description' => 'Transportation', 'quantity' => 1, 'rate' => 3000]
                            ];
                            break;
                        case '2 BHK':
                            $default_items = [
                                ['description' => 'Packing & Moving Service (2 BHK)', 'quantity' => 1, 'rate' => 12000],
                                ['description' => 'Loading & Unloading', 'quantity' => 1, 'rate' => 3000],
                                ['description' => 'Transportation', 'quantity' => 1, 'rate' => 4000]
                            ];
                            break;
                        case '3 BHK':
                            $default_items = [
                                ['description' => 'Packing & Moving Service (3 BHK)', 'quantity' => 1, 'rate' => 18000],
                                ['description' => 'Loading & Unloading', 'quantity' => 1, 'rate' => 4000],
                                ['description' => 'Transportation', 'quantity' => 1, 'rate' => 5000]
                            ];
                            break;
                        default:
                            $default_items = [
                                ['description' => 'Packing & Moving Service', 'quantity' => 1, 'rate' => 15000],
                                ['description' => 'Loading & Unloading', 'quantity' => 1, 'rate' => 3500],
                                ['description' => 'Transportation', 'quantity' => 1, 'rate' => 4500]
                            ];
                    }
                    
                    $subtotal = array_sum(array_map(function($item) { return $item['quantity'] * $item['rate']; }, $default_items));
                    $tax_rate = 18.00;
                    $tax_amount = ($subtotal * $tax_rate) / 100;
                    $total_amount = $subtotal + $tax_amount;
                    
                    // Generate quote number and secure token
                    $quote_number = 'EST' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                    $secure_token = generate_secure_token();
                    $valid_until = date('Y-m-d', strtotime('+30 days'));
                    
                    // Create quote as DRAFT so admin can edit before sending
                    try {
                        $stmt = $pdo->prepare("INSERT INTO quotes (lead_id, quote_number, secure_token, items, subtotal, tax_rate, tax_amount, total_amount, valid_until, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?)");
                        $stmt->execute([$lead_id, $quote_number, $secure_token, json_encode($default_items), $subtotal, $tax_rate, $tax_amount, $total_amount, $valid_until, $_SESSION['user_id']]);
                    } catch (Exception $e) {
                        // Fallback without secure_token if column doesn't exist
                        $stmt = $pdo->prepare("INSERT INTO quotes (lead_id, quote_number, items, subtotal, tax_rate, tax_amount, total_amount, valid_until, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?)");
                        $stmt->execute([$lead_id, $quote_number, json_encode($default_items), $subtotal, $tax_rate, $tax_amount, $total_amount, $valid_until, $_SESSION['user_id']]);
                        $secure_token = null;
                    }
                    
                    $_SESSION['message'] = "Lead status updated and DRAFT estimate $quote_number created!";
                }
            }
            }
            
            // Handle sent status - send existing draft estimate
            elseif ($new_status == 'sent') {
                // Check if there's a draft quote to send
                $stmt = $pdo->prepare("SELECT * FROM quotes WHERE lead_id = ? AND status = 'draft' ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$lead_id]);
                $draft_quote = $stmt->fetch();
                
                if ($draft_quote) {
                    // Send the existing draft quote
                    $stmt = $pdo->prepare("UPDATE quotes SET status = 'sent' WHERE id = ?");
                    $stmt->execute([$draft_quote['id']]);
                    
                    // Get lead details for email
                    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
                    $stmt->execute([$lead_id]);
                    $lead = $stmt->fetch();
                    
                    // Send quote email to customer only if email exists
                    if (!empty($draft_quote['secure_token']) && $lead && !empty($lead['email'])) {
                        try {
                            send_quote_email($draft_quote, $lead, 'sent');
                        } catch (Exception $e) {
                            error_log("Quote email failed: " . $e->getMessage());
                        }
                    }
                    
                    $_SESSION['message'] = "Lead status updated and estimate {$draft_quote['quote_number']} sent to customer!";
                } else {
                    $_SESSION['message'] = 'No draft estimate found to send. Please create an estimate first.';
                }
            }
            
            // Auto-convert to invoice when estimate is approved
            elseif ($new_status == 'estimate_approved') {
                // Get the quote for this lead
                $stmt = $pdo->prepare("SELECT * FROM quotes WHERE lead_id = ? ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$lead_id]);
                $quote = $stmt->fetch();
                
                if ($quote) {
                    // Update quote status to approved
                    $stmt = $pdo->prepare("UPDATE quotes SET status = 'approved' WHERE id = ?");
                    $stmt->execute([$quote['id']]);
                    
                    // Check if invoice already exists
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE quote_id = ?");
                    $stmt->execute([$quote['id']]);
                    
                    if ($stmt->fetchColumn() == 0) {
                        // Generate invoice number and secure token
                        $invoice_number = 'INV' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                        $invoice_secure_token = generate_secure_token();
                        $due_date = date('Y-m-d', strtotime('+30 days'));
                        
                        // Create invoice with all tax fields
                        try {
                            $stmt = $pdo->prepare("INSERT INTO invoices (quote_id, invoice_number, secure_token, items, subtotal, service_rate, service_amount, tax_rate, tax_amount, total_amount, tax_enabled, due_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
                            $stmt->execute([$quote['id'], $invoice_number, $invoice_secure_token, $quote['items'], $quote['subtotal'], $quote['service_rate'] ?? 0, $quote['service_amount'] ?? 0, $quote['tax_rate'], $quote['tax_amount'], $quote['total_amount'], $quote['tax_enabled'] ?? 0, $due_date, $_SESSION['user_id']]);
                        } catch (Exception $e) {
                            // Fallback without new columns if they don't exist
                            try {
                                $stmt = $pdo->prepare("INSERT INTO invoices (quote_id, invoice_number, secure_token, items, subtotal, tax_rate, tax_amount, total_amount, due_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
                                $stmt->execute([$quote['id'], $invoice_number, $invoice_secure_token, $quote['items'], $quote['subtotal'], $quote['tax_rate'], $quote['tax_amount'], $quote['total_amount'], $due_date, $_SESSION['user_id']]);
                            } catch (Exception $e2) {
                                // Final fallback without secure_token
                                $stmt = $pdo->prepare("INSERT INTO invoices (quote_id, invoice_number, items, subtotal, tax_rate, tax_amount, total_amount, due_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
                                $stmt->execute([$quote['id'], $invoice_number, $quote['items'], $quote['subtotal'], $quote['tax_rate'], $quote['tax_amount'], $quote['total_amount'], $due_date, $_SESSION['user_id']]);
                                $invoice_secure_token = null;
                            }
                        }
                        
                        // Get lead details for email
                        $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
                        $stmt->execute([$lead_id]);
                        $lead = $stmt->fetch();
                        
                        // Create invoice data for email
                        $invoice_data = [
                            'id' => $pdo->lastInsertId(),
                            'invoice_number' => $invoice_number,
                            'secure_token' => $invoice_secure_token,
                            'total_amount' => $quote['total_amount'],
                            'due_date' => $due_date
                        ];
                        
                        // Send professional invoice email only if email exists
                        if (!empty($lead['email'])) {
                            try {
                                send_invoice_email($invoice_data, $quote, $lead, 'converted');
                            } catch (Exception $e) {
                                error_log("Invoice email failed: " . $e->getMessage());
                            }
                        }
                        

                        
                        $_SESSION['message'] = "Lead status updated, estimate approved, and invoice $invoice_number created automatically!";
                    } else {
                        $_SESSION['message'] = 'Lead status updated and estimate approved!';
                    }
                } else {
                    $_SESSION['message'] = 'Lead status updated! (No estimate found to approve)';
                }
            }
            
            else {
                $_SESSION['message'] = 'Lead status updated!';
            }
            
            redirect('/leads');
        }
        
        if ($_POST['action'] == 'delete_lead') {
            require_permission('leads', 'delete');
            $lead_id = $_POST['lead_id'];
            
            // Delete related records first
            $stmt = $pdo->prepare("DELETE FROM communications WHERE lead_id = ?");
            $stmt->execute([$lead_id]);
            
            // Delete quotes and related invoices
            $stmt = $pdo->prepare("SELECT id FROM quotes WHERE lead_id = ?");
            $stmt->execute([$lead_id]);
            $quotes = $stmt->fetchAll();
            
            foreach ($quotes as $quote) {
                $stmt = $pdo->prepare("DELETE FROM payments WHERE invoice_id IN (SELECT id FROM invoices WHERE quote_id = ?)");
                $stmt->execute([$quote['id']]);
                $stmt = $pdo->prepare("DELETE FROM invoices WHERE quote_id = ?");
                $stmt->execute([$quote['id']]);
            }
            
            $stmt = $pdo->prepare("DELETE FROM quotes WHERE lead_id = ?");
            $stmt->execute([$lead_id]);
            
            $stmt = $pdo->prepare("DELETE FROM leads WHERE id = ?");
            $stmt->execute([$lead_id]);
            
            $_SESSION['message'] = 'Lead deleted successfully!';
            redirect('/leads');
        }
        
        if ($_POST['action'] == 'create_lead') {
            require_permission('leads', 'create');
            $name = sanitize_input($_POST['name']);
            $email = sanitize_input($_POST['email']);
            $phone = sanitize_input($_POST['phone']);
            $move_from = sanitize_input($_POST['move_from']);
            $move_to = sanitize_input($_POST['move_to']);
            $move_date = $_POST['move_date'];
            $home_size = sanitize_input($_POST['home_size']);
            $additional_services = isset($_POST['additional_services']) ? implode(', ', $_POST['additional_services']) : '';
            $notes = sanitize_input($_POST['notes'] ?? '');
            $source = sanitize_input($_POST['source'] ?? 'Manual Entry');
            
            // Validate required fields (email is now optional)
            // Note: No date validation for admin - can enter any date including past dates
            if (!empty($name) && !empty($phone) && !empty($move_from) && 
                !empty($move_to) && !empty($move_date) && !empty($home_size)) {
                
                try {
                    // Insert lead into database
                    $stmt = $pdo->prepare("INSERT INTO leads (name, email, phone, move_from, move_to, move_date, home_size, additional_services, notes, status, thank_you_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'inquiry', ?)");
                    $stmt->execute([$name, $email, $phone, $move_from, $move_to, $move_date, $home_size, $additional_services, $notes, $thank_you_token]);
                    $lead_id = $pdo->lastInsertId();
                    
                    // Send new lead notification to support team
                    $lead_data = [
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'move_from' => $move_from,
                        'move_to' => $move_to,
                        'move_date' => $move_date,
                        'home_size' => $home_size,
                        'additional_services' => $additional_services,
                        'notes' => $notes
                    ];
                    // Send email notification only if email provided
                    if (!empty($email)) {
                        try {
                            send_lead_notification($lead_data);
                        } catch (Exception $e) {
                            // Log email error but don't fail lead creation
                            error_log("Email notification failed: " . $e->getMessage());
                        }
                    }
                    

                
                $_SESSION['message'] = "Lead '$name' created successfully!";
                } catch (Exception $e) {
                    $_SESSION['message'] = 'Error creating lead. Please try again.';
                }
            } else {
                $_SESSION['message'] = 'Please fill in all required fields.';
            }
            
            redirect('/leads');
        }
    }
}

// Get lead statistics dynamically from status table
$lead_stats = ['total_leads' => $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn()];
$lead_statuses = get_statuses('lead');
foreach ($lead_statuses as $status) {
    $count = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE status = ?");
    $count->execute([$status['status_key']]);
    $lead_stats[$status['status_key']] = $count->fetchColumn();
}

// Get leads with pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR move_from LIKE ? OR move_to LIKE ?)";
    $search_param = "%$search%";
    $params = array_fill(0, 5, $search_param);
}

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$stmt = $pdo->prepare("SELECT * FROM leads $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$leads = $stmt->fetchAll();

// Get total count for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM leads $where_clause");
$count_stmt->execute($params);
$total_leads = $count_stmt->fetchColumn();
$total_pages = ceil($total_leads / $limit);

include 'includes/header.php';
?>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-users"></i> Leads Management</h1>
        <div>
            <?php if (has_permission('leads', 'create')): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createLeadModal"><i class="fas fa-plus"></i> Add Lead</button>
            <?php endif; ?>
            <a href="/quotes" class="btn btn-outline-primary"><i class="fas fa-file-alt"></i> Estimates</a>
            <a href="/invoices" class="btn btn-outline-success"><i class="fas fa-receipt"></i> Invoices</a>
            <button class="btn btn-outline-info" onclick="testWorkflow()"><i class="fas fa-play"></i> Test Workflow</button>
        </div>
    </div>

    <!-- Workflow Indicator -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-route"></i> Packers & Movers Workflow</h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <?php 
                $step = 1;
                foreach ($lead_statuses as $status): 
                ?>
                <div class="col-md-2">
                    <div class="badge bg-<?php echo $status['status_color']; ?> p-2"><?php echo $step; ?></div><br>
                    <small><?php echo $status['status_label']; ?></small>
                </div>
                <?php if ($step < count($lead_statuses)): ?>
                <div class="col-md-1">
                    <i class="fas fa-arrow-right text-muted"></i>
                </div>
                <?php endif; ?>
                <?php 
                $step++;
                endforeach; 
                ?>
            </div>
        </div>
    </div>

    <!-- Lead Statistics -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3><?php echo $lead_stats['total_leads']; ?></h3>
                    <small>Total Leads</small>
                </div>
            </div>
        </div>
        <?php foreach ($lead_statuses as $status): ?>
        <div class="col-md-2">
            <div class="card bg-<?php echo $status['status_color']; ?> text-white">
                <div class="card-body text-center">
                    <h3><?php echo $lead_stats[$status['status_key']]; ?></h3>
                    <small><?php echo $status['status_label']; ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Search leads..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <?php 
                        $filter_statuses = get_statuses('lead');
                        foreach ($filter_statuses as $status): 
                        ?>
                        <option value="<?php echo $status['status_key']; ?>" <?php echo $status_filter == $status['status_key'] ? 'selected' : ''; ?>>
                            <?php echo $status['status_label']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="/leads" class="btn btn-secondary w-100"><i class="fas fa-times"></i> Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Leads Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Route</th>
                            <th>Move Date</th>
                            <th>Home Size</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td><?php echo $lead['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($lead['name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($lead['email']); ?></small>
                            </td>
                            <td>
                                <a href="tel:<?php echo $lead['phone']; ?>" class="text-decoration-none">
                                    <i class="fas fa-phone"></i> <?php echo $lead['phone']; ?>
                                </a>
                            </td>
                            <td>
                                <small>
                                    <strong>From:</strong> <?php echo htmlspecialchars($lead['move_from']); ?><br>
                                    <strong>To:</strong> <?php echo htmlspecialchars($lead['move_to']); ?>
                                </small>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($lead['move_date'])); ?></td>
                            <td><span class="badge bg-info"><?php echo $lead['home_size']; ?></span></td>
                            <td>
                                <?php if (has_permission('leads', 'update') && !in_array($lead['status'], ['completed', 'cancelled'])): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="lead_id" value="<?php echo $lead['id']; ?>">
                                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 140px;">
                                        <?php 
                                        $lead_statuses = get_statuses('lead');
                                        foreach ($lead_statuses as $status): 
                                        ?>
                                        <option value="<?php echo $status['status_key']; ?>" <?php echo $lead['status'] == $status['status_key'] ? 'selected' : ''; ?>>
                                            <?php echo $status['status_label']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                                <?php else: ?>
                                <?php 
                                $status_info = get_status_info('lead', $lead['status']);
                                ?>
                                <span class="badge bg-<?php echo $status_info['status_color']; ?>">
                                    <?php echo $status_info['status_label']; ?>
                                    <?php if (in_array($lead['status'], ['completed', 'cancelled'])): ?>
                                    <i class="fas fa-lock ms-1" title="Status locked"></i>
                                    <?php endif; ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($lead['created_at'])); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-info" onclick="viewLead(<?php echo $lead['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if (has_permission('leads', 'delete')): ?>
                                    <?php
                                    // Check if lead has quotes
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM quotes WHERE lead_id = ?");
                                    $stmt->execute([$lead['id']]);
                                    $has_quotes = $stmt->fetchColumn() > 0;
                                    ?>
                                    <?php if ($has_quotes): ?>
                                    <button class="btn btn-sm btn-secondary" disabled title="Cannot delete - Estimates exist">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                    <?php else: ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this lead?')">
                                        <input type="hidden" name="action" value="delete_lead">
                                        <input type="hidden" name="lead_id" value="<?php echo $lead['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    <a href="/quotes?lead_id=<?php echo $lead['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-file-alt"></i>
                                    </a>
                                    <a href="https://wa.me/<?php echo str_replace(['+', ' ', '-'], '', $lead['phone']); ?>?text=Hi <?php echo urlencode($lead['name']); ?>, thank you for your moving inquiry. We'll provide you with the best quote!" class="btn btn-sm btn-success" target="_blank">
                                        <i class="fab fa-whatsapp"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- View Modal -->
                        <div class="modal fade" id="viewModal<?php echo $lead['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Lead Details - <?php echo $lead['name']; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Contact Information</h6>
                                                <p><strong>Name:</strong> <?php echo $lead['name']; ?></p>
                                                <p><strong>Email:</strong> <?php echo $lead['email']; ?></p>
                                                <p><strong>Phone:</strong> <?php echo $lead['phone']; ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Moving Details</h6>
                                                <p><strong>From:</strong> <?php echo $lead['move_from']; ?></p>
                                                <p><strong>To:</strong> <?php echo $lead['move_to']; ?></p>
                                                <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($lead['move_date'])); ?></p>
                                                <p><strong>Home Size:</strong> <?php echo $lead['home_size']; ?></p>
                                            </div>
                                        </div>
                                        <?php if ($lead['additional_services']): ?>
                                        <h6>Additional Services</h6>
                                        <p><?php echo $lead['additional_services']; ?></p>
                                        <?php endif; ?>
                                        <?php if ($lead['notes']): ?>
                                        <h6>Notes</h6>
                                        <p><?php echo $lead['notes']; ?></p>
                                        <?php endif; ?>
                                        <p><strong>Status:</strong> <span class="badge bg-primary"><?php echo ucfirst($lead['status']); ?></span></p>
                                        <p><strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime($lead['created_at'])); ?></p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <a href="/quotes?lead_id=<?php echo $lead['id']; ?>" class="btn btn-primary">Create Quote</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Lead Modal -->
<div class="modal fade" id="createLeadModal" tabindex="-1" aria-labelledby="createLeadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createLeadModalLabel"><i class="fas fa-user-plus"></i> Add New Lead</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_lead">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required maxlength="100">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" maxlength="100">
                                <small class="text-muted">Optional - for email notifications</small>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required maxlength="15" placeholder="+91 9876543210">
                            </div>
                            <div class="mb-3">
                                <label for="source" class="form-label">Lead Source</label>
                                <select class="form-select" id="source" name="source">
                                    <option value="Manual Entry">Manual Entry</option>
                                    <option value="WhatsApp">WhatsApp</option>
                                    <option value="Phone Call">Phone Call</option>
                                    <option value="Website">Website</option>
                                    <option value="Referral">Referral</option>
                                    <option value="Social Media">Social Media</option>
                                    <option value="Walk-in">Walk-in</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="move_from" class="form-label">Moving From *</label>
                                <input type="text" class="form-control" id="move_from" name="move_from" required maxlength="200" placeholder="Current address">
                            </div>
                            <div class="mb-3">
                                <label for="move_to" class="form-label">Moving To *</label>
                                <input type="text" class="form-control" id="move_to" name="move_to" required maxlength="200" placeholder="Destination address">
                            </div>
                            <div class="mb-3">
                                <label for="move_date" class="form-label">Preferred Move Date *</label>
                                <input type="date" class="form-control" id="move_date" name="move_date" required>
                                <small class="text-muted">Admin can select any date including past dates</small>
                            </div>
                            <div class="mb-3">
                                <label for="home_size" class="form-label">Property Size *</label>
                                <select class="form-select" id="home_size" name="home_size" required>
                                    <option value="">Select property size</option>
                                    <option value="1 BHK">1 BHK</option>
                                    <option value="2 BHK">2 BHK</option>
                                    <option value="3 BHK">3 BHK</option>
                                    <option value="4+ BHK">4+ BHK</option>
                                    <option value="Villa/Bungalow">Villa/Bungalow</option>
                                    <option value="Office">Office</option>
                                    <option value="Warehouse">Warehouse</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Additional Services</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="additional_services[]" value="Packing" id="packing">
                                    <label class="form-check-label" for="packing">Packing Services</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="additional_services[]" value="Storage" id="storage">
                                    <label class="form-check-label" for="storage">Storage Services</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="additional_services[]" value="Insurance" id="insurance">
                                    <label class="form-check-label" for="insurance">Insurance Coverage</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="additional_services[]" value="Unpacking" id="unpacking">
                                    <label class="form-check-label" for="unpacking">Unpacking Services</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="additional_services[]" value="Cleaning" id="cleaning">
                                    <label class="form-check-label" for="cleaning">Cleaning Services</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="additional_services[]" value="Assembly" id="assembly">
                                    <label class="form-check-label" for="assembly">Furniture Assembly</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="500" placeholder="Any special requirements, customer preferences, or additional information"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Lead</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function testWorkflow() {
    alert('Complete Workflow Automation:\n\n' +
          '1. Change lead status to "Estimate Sent" → Auto-creates estimate\n' +
          '2. Change lead status to "Estimate Approved" → Auto-creates invoice\n' +
          '3. Add payments to invoice → Auto-updates status\n' +
          '4. Track expenses → Auto-calculates profit/loss\n\n' +
          'Try it: Change any lead status and see the magic!');
}

function viewLead(leadId) {
    // Simple redirect to avoid modal issues
    window.location.href = '/lead-details?id=' + leadId;
}
</script>

<?php include 'includes/footer.php'; ?>