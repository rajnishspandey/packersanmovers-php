<?php
require_once 'config.php';
require_admin();

$lead_id = $_GET['id'] ?? 0;

// Get lead details
$stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
$stmt->execute([$lead_id]);
$lead = $stmt->fetch();

if (!$lead) {
    $_SESSION['message'] = 'Lead not found!';
    redirect('/leads');
}

// Get related quotes
$stmt = $pdo->prepare("SELECT * FROM quotes WHERE lead_id = ? ORDER BY created_at DESC");
$stmt->execute([$lead_id]);
$quotes = $stmt->fetchAll();

// Get related invoices
$stmt = $pdo->prepare("SELECT i.*, q.quote_number FROM invoices i JOIN quotes q ON i.quote_id = q.id WHERE q.lead_id = ? ORDER BY i.created_at DESC");
$stmt->execute([$lead_id]);
$invoices = $stmt->fetchAll();

$page_title = 'Lead Details - ' . $lead['name'];
include 'includes/header.php';
?>

<div class="container-fluid my-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="/leads">Leads</a></li>
            <li class="breadcrumb-item active">Lead Details</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-user"></i> <?php echo $lead['name']; ?></h1>
        <div>
            <a href="/leads" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Leads</a>
            <a href="/quotes?lead_id=<?php echo $lead['id']; ?>" class="btn btn-primary"><i class="fas fa-file-alt"></i> Create Estimate</a>
        </div>
    </div>

    <div class="row">
        <!-- Lead Information -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> Contact Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo $lead['name']; ?></p>
                    <p><strong>Email:</strong> <a href="mailto:<?php echo $lead['email']; ?>"><?php echo $lead['email']; ?></a></p>
                    <p><strong>Phone:</strong> <a href="tel:<?php echo $lead['phone']; ?>"><?php echo $lead['phone']; ?></a></p>
                    <p><strong>Status:</strong> 
                        <?php 
                        $status_info = get_status_info('lead', $lead['status']);
                        $color = $status_info ? $status_info['status_color'] : 'secondary';
                        $label = $status_info ? $status_info['status_label'] : ucfirst($lead['status']);
                        ?>
                        <span class="badge bg-<?php echo $color; ?>"><?php echo $label; ?></span>
                    </p>
                    <p><strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime($lead['created_at'])); ?></p>
                </div>
            </div>
        </div>

        <!-- Moving Details -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-truck"></i> Moving Details</h5>
                </div>
                <div class="card-body">
                    <p><strong>From:</strong> <?php echo $lead['move_from']; ?></p>
                    <p><strong>To:</strong> <?php echo $lead['move_to']; ?></p>
                    <p><strong>Move Date:</strong> <?php echo date('M d, Y', strtotime($lead['move_date'])); ?></p>
                    <p><strong>Home Size:</strong> <span class="badge bg-info"><?php echo $lead['home_size']; ?></span></p>
                    <?php if ($lead['additional_services']): ?>
                    <p><strong>Additional Services:</strong> <?php echo $lead['additional_services']; ?></p>
                    <?php endif; ?>
                    <?php if ($lead['notes']): ?>
                    <p><strong>Notes:</strong> <?php echo $lead['notes']; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Records -->
    <div class="row mt-4">
        <!-- Estimates -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-file-alt"></i> Estimates (<?php echo count($quotes); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if ($quotes): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Estimate #</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quotes as $quote): ?>
                                <tr>
                                    <td><?php echo $quote['quote_number']; ?></td>
                                    <td>Rs <?php echo number_format($quote['total_amount'], 2); ?></td>
                                    <td>
                                        <?php 
                                        $status_info = get_status_info('quote', $quote['status']);
                                        $color = $status_info ? $status_info['status_color'] : 'secondary';
                                        $label = $status_info ? $status_info['status_label'] : ucfirst($quote['status']);
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>"><?php echo $label; ?></span>
                                    </td>
                                    <td>
                                        <a href="/estimate?id=<?php echo $quote['id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">No estimates created yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Invoices -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-receipt"></i> Invoices (<?php echo count($invoices); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if ($invoices): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td><?php echo $invoice['invoice_number']; ?></td>
                                    <td>Rs <?php echo number_format($invoice['total_amount'], 2); ?></td>
                                    <td>
                                        <?php 
                                        $status_info = get_status_info('invoice', $invoice['status']);
                                        $color = $status_info ? $status_info['status_color'] : 'secondary';
                                        $label = $status_info ? $status_info['status_label'] : ucfirst($invoice['status']);
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>"><?php echo $label; ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($invoice['secure_token'])): ?>
                                        <a href="/invoice?token=<?php echo $invoice['secure_token']; ?>" class="btn btn-sm btn-outline-success" target="_blank">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        <?php else: ?>
                                        <span class="btn btn-sm btn-outline-secondary disabled">No Token</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">No invoices created yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>