<?php
require_once 'config.php';
require_admin();

$page_title = 'Admin Panel';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_user':
                $username = sanitize_input($_POST['username']);
                $email = sanitize_input($_POST['email']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $role = sanitize_input($_POST['role']);
                $is_admin = ($role === 'admin') ? 1 : 0;
                
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, is_admin) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$username, $email, $password, $role, $is_admin])) {
                    $_SESSION['message'] = 'User added successfully!';
                } else {
                    $_SESSION['message'] = 'Error adding user.';
                }
                break;
                
            case 'update_user':
                $user_id = $_POST['user_id'];
                $username = sanitize_input($_POST['username']);
                $email = sanitize_input($_POST['email']);
                $role = sanitize_input($_POST['role']);
                $is_admin = ($role === 'admin') ? 1 : 0;
                
                if (!empty($_POST['password'])) {
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password_hash = ?, role = ?, is_admin = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $password, $role, $is_admin, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, is_admin = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $role, $is_admin, $user_id]);
                }
                $_SESSION['message'] = 'User updated successfully!';
                break;
                
            case 'delete_user':
                $user_id = $_POST['user_id'];
                if ($user_id != $_SESSION['user_id']) {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $_SESSION['message'] = 'User deleted successfully!';
                }
                break;
        }
        redirect('/admin');
    }
}

// Get comprehensive statistics
$stats = [
    // User Stats
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    
    // Lead Stats - Dynamic from database
    'total_leads' => $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn(),
    
    // Quote/Estimate Stats - Dynamic from database
    'total_quotes' => $pdo->query("SELECT COUNT(*) FROM quotes")->fetchColumn(),
    
    // Invoice Stats - Dynamic from database
    'total_invoices' => $pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn(),
    
    // Financial Stats - Calculate consolidated totals (parent + supplementary)
    'total_revenue' => $pdo->query("
        SELECT COALESCE(SUM(CASE WHEN parent_invoice_id IS NULL THEN 
            (SELECT SUM(total_amount) FROM invoices sub WHERE (sub.id = invoices.id OR sub.parent_invoice_id = invoices.id) AND sub.status != 'abandoned')
        ELSE 0 END), 0) 
        FROM invoices WHERE status != 'abandoned'
    ")->fetchColumn(),
    'total_paid' => $pdo->query("
        SELECT COALESCE(SUM(CASE WHEN parent_invoice_id IS NULL THEN 
            (SELECT SUM(paid_amount) FROM invoices sub WHERE (sub.id = invoices.id OR sub.parent_invoice_id = invoices.id) AND sub.status != 'abandoned')
        ELSE 0 END), 0) 
        FROM invoices WHERE status != 'abandoned'
    ")->fetchColumn(),
    'total_invoice_expenses' => $pdo->query("SELECT COALESCE(SUM(e.amount), 0) FROM expenses e JOIN invoices i ON e.invoice_id = i.id WHERE i.status != 'abandoned'")->fetchColumn(),
    'total_business_expenses' => $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM business_expenses")->fetchColumn(),
    'pending_amount' => $pdo->query("
        SELECT COALESCE(SUM(CASE WHEN parent_invoice_id IS NULL THEN 
            (SELECT SUM(total_amount) - SUM(paid_amount) FROM invoices sub WHERE (sub.id = invoices.id OR sub.parent_invoice_id = invoices.id) AND sub.status != 'abandoned')
        ELSE 0 END), 0) 
        FROM invoices WHERE status != 'paid' AND status != 'abandoned'
    ")->fetchColumn()
];

// Add dynamic status counts
$lead_statuses = get_statuses('lead');
foreach ($lead_statuses as $status) {
    $count = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE status = ?");
    $count->execute([$status['status_key']]);
    $stats['lead_' . $status['status_key']] = $count->fetchColumn();
}

$quote_statuses = get_statuses('quote');
foreach ($quote_statuses as $status) {
    $count = $pdo->prepare("SELECT COUNT(*) FROM quotes WHERE status = ?");
    $count->execute([$status['status_key']]);
    $stats['quote_' . $status['status_key']] = $count->fetchColumn();
}

$invoice_statuses = get_statuses('invoice');
foreach ($invoice_statuses as $status) {
    $count = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE status = ?");
    $count->execute([$status['status_key']]);
    $stats['invoice_' . $status['status_key']] = $count->fetchColumn();
}

// Calculate profit
$stats['total_expenses'] = $stats['total_invoice_expenses'] + $stats['total_business_expenses'];
$stats['gross_profit'] = $stats['total_paid'] - $stats['total_invoice_expenses'];
$stats['net_profit'] = $stats['gross_profit'] - $stats['total_business_expenses'];
$stats['profit_margin'] = $stats['total_paid'] > 0 ? ($stats['net_profit'] / $stats['total_paid']) * 100 : 0;

// Get recent activities
$recent_leads = $pdo->query("SELECT name, status, created_at FROM leads ORDER BY created_at DESC LIMIT 10")->fetchAll();
$recent_quotes = $pdo->query("SELECT q.quote_number, q.status, q.total_amount, l.name FROM quotes q JOIN leads l ON q.lead_id = l.id ORDER BY q.created_at DESC LIMIT 10")->fetchAll();
$recent_invoices = $pdo->query("SELECT i.invoice_number, i.status, i.total_amount, l.name FROM invoices i JOIN quotes q ON i.quote_id = q.id JOIN leads l ON q.lead_id = l.id WHERE i.parent_invoice_id IS NULL AND i.status != 'abandoned' ORDER BY i.created_at DESC LIMIT 5")->fetchAll();

// Get monthly data for charts
$monthly_data = $pdo->query("SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as leads_count,
    COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) as completed_count
    FROM leads 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month")->fetchAll();

$revenue_data = $pdo->query("SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COALESCE(SUM(total_amount), 0) as revenue,
    COALESCE(SUM(paid_amount), 0) as collected
    FROM invoices 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) AND status != 'abandoned' AND parent_invoice_id IS NULL
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month")->fetchAll();

// Get users with roles
$users_stmt = $pdo->query("SELECT u.*, r.role_label FROM users u LEFT JOIN roles r ON u.role = r.role_name ORDER BY u.created_at DESC");
$users = $users_stmt->fetchAll();

// Get all roles for dropdowns
$all_roles = get_all_roles();

// Prepare chart data for lead statuses
$lead_chart_data = [];
$lead_chart_labels = [];
$lead_chart_colors = [];

$chart_status_mapping = [
    'inquiry' => ['label' => 'New Inquiries', 'color' => '#007bff'],
    'survey_scheduled' => ['label' => 'Survey Scheduled', 'color' => '#17a2b8'],
    'survey_done' => ['label' => 'Survey Done', 'color' => '#ffc107'],
    'estimate_sent' => ['label' => 'Estimates Sent', 'color' => '#6c757d'],
    'estimate_approved' => ['label' => 'Estimates Approved', 'color' => '#fd7e14'],
    'booking_confirmed' => ['label' => 'Bookings Confirmed', 'color' => '#28a745'],
    'in_progress' => ['label' => 'In Progress', 'color' => '#dc3545'],
    'completed' => ['label' => 'Completed', 'color' => '#343a40'],
    'cancelled' => ['label' => 'Cancelled', 'color' => '#6f42c1']
];

foreach ($lead_statuses as $status) {
    $status_key = $status['status_key'];
    $count = $stats['lead_' . $status_key] ?? 0;
    
    if ($count > 0) { // Only include statuses that have data
        $lead_chart_data[] = $count;
        $lead_chart_labels[] = $chart_status_mapping[$status_key]['label'] ?? ucwords(str_replace('_', ' ', $status_key));
        $lead_chart_colors[] = $chart_status_mapping[$status_key]['color'] ?? '#6c757d';
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Admin Dashboard</h1>
        <div>
            <a href="/leads" class="btn btn-outline-primary">Leads</a>
            <a href="/quotes" class="btn btn-outline-secondary">Quotes</a>
            <a href="/invoices" class="btn btn-outline-success">Invoices</a>
            <a href="/analytics" class="btn btn-outline-info">Analytics</a>
        </div>
    </div>
    
    <!-- Key Metrics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3>Rs <?php echo number_format($stats['total_revenue'], 0); ?></h3>
                            <small>Total Revenue</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-rupee-sign fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3>Rs <?php echo number_format($stats['total_paid'], 0); ?></h3>
                            <small>Amount Collected</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-money-bill-wave fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-<?php echo $stats['net_profit'] >= 0 ? 'primary' : 'warning'; ?> text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3>Rs <?php echo number_format($stats['net_profit'], 0); ?></h3>
                            <small>Net Profit (<?php echo number_format($stats['profit_margin'], 1); ?>%)</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3>Rs <?php echo number_format($stats['pending_amount'], 0); ?></h3>
                            <small>Pending Collection</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Business Stats -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4><?php echo $stats['total_leads']; ?></h4>
                    <small>Total Leads</small>
                </div>
            </div>
        </div>
        <?php foreach (array_slice($lead_statuses, 0, 5) as $status): ?>
        <div class="col-md-2">
            <div class="card bg-<?php echo $status['status_color']; ?> text-white">
                <div class="card-body text-center">
                    <h4><?php echo $stats['lead_' . $status['status_key']]; ?></h4>
                    <small><?php echo $status['status_label']; ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="row">
        <div class="col-lg-12">
            
            <!-- Analytics Charts -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-area"></i> Monthly Revenue Trend</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-pie"></i> Lead Status Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="leadStatusChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activities -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-users"></i> Recent Leads</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach (array_slice($recent_leads, 0, 5) as $index => $lead): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong><?php echo $lead['name']; ?></strong><br>
                                    <small class="text-muted"><?php echo date('M d, H:i', strtotime($lead['created_at'])); ?></small>
                                </div>
                                <span class="badge bg-primary"><?php echo ucwords(str_replace('_', ' ', $lead['status'])); ?></span>
                            </div>
                            <?php if ($index < 4): ?><hr><?php endif; ?>
                            <?php endforeach; ?>
                            <a href="/leads" class="btn btn-sm btn-outline-primary w-100">View All Leads</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-file-alt"></i> Recent Estimates</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach (array_slice($recent_quotes, 0, 5) as $index => $quote): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong><?php echo $quote['quote_number']; ?></strong><br>
                                    <small class="text-muted"><?php echo $quote['name']; ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-secondary"><?php echo ucfirst($quote['status']); ?></span><br>
                                    <small>Rs <?php echo number_format($quote['total_amount'], 0); ?></small>
                                </div>
                            </div>
                            <?php if ($index < 4): ?><hr><?php endif; ?>
                            <?php endforeach; ?>
                            <a href="/quotes" class="btn btn-sm btn-outline-primary w-100">View All Estimates</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-receipt"></i> Recent Invoices</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($recent_invoices as $invoice): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong><?php echo $invoice['invoice_number']; ?></strong><br>
                                    <small class="text-muted"><?php echo $invoice['name']; ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?php echo $invoice['status'] == 'paid' ? 'success' : 'warning'; ?>"><?php echo ucfirst($invoice['status']); ?></span><br>
                                    <small>Rs <?php echo number_format($invoice['total_amount'], 0); ?></small>
                                </div>
                            </div>
                            <hr>
                            <?php endforeach; ?>
                            <a href="/invoices" class="btn btn-sm btn-outline-success w-100">View All Invoices</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Users Section -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3><i class="fas fa-users-cog"></i> Users Management</h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">Add User</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'manager' ? 'success' : ($user['role'] === 'sales' ? 'primary' : ($user['role'] === 'accounts' ? 'info' : 'secondary'))); ?>">
                                            <?php echo $user['role_label'] ?: ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['id']; ?>">Edit</button>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_user">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <?php foreach ($all_roles as $role): ?>
                            <option value="<?php echo $role['role_name']; ?>"><?php echo $role['role_label']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modals -->
<?php foreach ($users as $user): ?>
<div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" value="<?php echo $user['username']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo $user['email']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" name="password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" required>
                            <?php foreach ($all_roles as $role): ?>
                            <option value="<?php echo $role['role_name']; ?>" <?php echo $user['role'] === $role['role_name'] ? 'selected' : ''; ?>>
                                <?php echo $role['role_label']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
// Auto-hide success messages
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 3000);
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: [<?php echo "'" . implode("','", array_column($revenue_data, 'month')) . "'"; ?>],
        datasets: [{
            label: 'Revenue',
            data: [<?php echo implode(',', array_column($revenue_data, 'revenue')); ?>],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.1
        }, {
            label: 'Collected',
            data: [<?php echo implode(',', array_column($revenue_data, 'collected')); ?>],
            borderColor: 'rgb(54, 162, 235)',
            backgroundColor: 'rgba(54, 162, 235, 0.1)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rs ' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Lead Status Pie Chart - Fixed with proper data
const leadStatusCtx = document.getElementById('leadStatusChart').getContext('2d');
const leadStatusChart = new Chart(leadStatusCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($lead_chart_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($lead_chart_data); ?>,
            backgroundColor: <?php echo json_encode($lead_chart_colors); ?>
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>