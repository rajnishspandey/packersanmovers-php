<?php
require_once 'config.php';
require_login();

$page_title = 'Dashboard';
$user_role = get_user_role();
$permissions = get_user_permissions();

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Dashboard - <?php echo ucfirst($user_role); ?></h1>
    </div>
    
    <div class="row">
        <?php if (has_permission('leads', 'read')): ?>
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <h5>Leads</h5>
                    <a href="/leads" class="btn btn-light btn-sm">View Leads</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (has_permission('quotes', 'read')): ?>
        <div class="col-md-3 mb-3">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-file-alt fa-2x mb-2"></i>
                    <h5>Quotes</h5>
                    <a href="/quotes" class="btn btn-light btn-sm">View Quotes</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (has_permission('invoices', 'read')): ?>
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-receipt fa-2x mb-2"></i>
                    <h5>Invoices</h5>
                    <a href="/invoices" class="btn btn-light btn-sm">View Invoices</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (has_permission('analytics', 'read')): ?>
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-chart-bar fa-2x mb-2"></i>
                    <h5>Analytics</h5>
                    <a href="/analytics" class="btn btn-light btn-sm">View Analytics</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Your Permissions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($permissions as $module => $access): ?>
                        <div class="col-md-3 mb-2">
                            <span class="badge bg-<?php echo $access === 'crud' ? 'success' : ($access === 'read' ? 'info' : 'secondary'); ?> me-1">
                                <?php echo ucfirst($module); ?>: <?php echo strtoupper($access); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>