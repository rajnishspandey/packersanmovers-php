<?php
require_once 'config.php';
require_permission('analytics', 'read');

$page_title = 'Analytics Dashboard';

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Revenue Analytics - Calculate consolidated totals (parent + supplementary)
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT CASE WHEN parent_invoice_id IS NULL THEN id END) as total_invoices,
        SUM(CASE WHEN parent_invoice_id IS NULL THEN 
            (SELECT SUM(total_amount) FROM invoices sub WHERE (sub.id = invoices.id OR sub.parent_invoice_id = invoices.id) AND sub.status != 'abandoned')
        ELSE 0 END) as total_revenue,
        SUM(CASE WHEN parent_invoice_id IS NULL THEN 
            (SELECT SUM(paid_amount) FROM invoices sub WHERE (sub.id = invoices.id OR sub.parent_invoice_id = invoices.id) AND sub.status != 'abandoned')
        ELSE 0 END) as total_paid
    FROM invoices 
    WHERE created_at BETWEEN ? AND ? AND status != 'abandoned'
");
$stmt->execute([$start_date, $end_date]);
$revenue_stats = $stmt->fetch();
$revenue_stats['avg_invoice_value'] = $revenue_stats['total_invoices'] > 0 ? $revenue_stats['total_revenue'] / $revenue_stats['total_invoices'] : 0;

// Invoice Expense Analytics (exclude abandoned invoices, include all invoice expenses)
$stmt = $pdo->prepare("SELECT 
    SUM(e.amount) as total_expenses,
    COUNT(*) as total_expense_records
    FROM expenses e
    JOIN invoices i ON e.invoice_id = i.id
    WHERE i.created_at BETWEEN ? AND ? AND i.status != 'abandoned'");
$stmt->execute([$start_date, $end_date]);
$invoice_expense_stats = $stmt->fetch();

// Business Expense Analytics
$stmt = $pdo->prepare("SELECT 
    SUM(amount) as total_business_expenses,
    COUNT(*) as total_business_expense_records
    FROM business_expenses 
    WHERE expense_date BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$business_expense_stats = $stmt->fetch();

// Calculate all values
$total_revenue = $revenue_stats['total_revenue'] ?? 0;
$total_paid = $revenue_stats['total_paid'] ?? 0;
$pending_collections = $total_revenue - $total_paid;
$total_invoice_expenses = $invoice_expense_stats['total_expenses'] ?? 0;
$total_business_expenses = $business_expense_stats['total_business_expenses'] ?? 0;
$total_all_expenses = $total_invoice_expenses + $total_business_expenses;

$gross_profit = $total_paid - $total_invoice_expenses;
$net_profit = $gross_profit - $total_business_expenses;
$gross_margin = $total_paid > 0 ? ($gross_profit / $total_paid) * 100 : 0;
$net_margin = $total_paid > 0 ? ($net_profit / $total_paid) * 100 : 0;

// Monthly trends (exclude abandoned and supplementary invoices)
$stmt = $pdo->query("SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    SUM(total_amount) as revenue,
    COUNT(*) as invoice_count
    FROM invoices 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) AND status != 'abandoned' AND parent_invoice_id IS NULL
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month");
$monthly_trends = $stmt->fetchAll();

// Category-wise invoice expenses (exclude abandoned invoices)
$stmt = $pdo->prepare("SELECT 
    e.category,
    SUM(e.amount) as total_amount,
    COUNT(*) as count
    FROM expenses e
    JOIN invoices i ON e.invoice_id = i.id
    WHERE i.created_at BETWEEN ? AND ? AND i.status != 'abandoned'
    GROUP BY e.category
    ORDER BY total_amount DESC");
$stmt->execute([$start_date, $end_date]);
$invoice_expense_categories = $stmt->fetchAll();

// Category-wise business expenses
$stmt = $pdo->prepare("SELECT 
    category,
    SUM(amount) as total_amount,
    COUNT(*) as count
    FROM business_expenses
    WHERE expense_date BETWEEN ? AND ?
    GROUP BY category
    ORDER BY total_amount DESC");
$stmt->execute([$start_date, $end_date]);
$business_expense_categories = $stmt->fetchAll();

// Top customers (exclude abandoned and supplementary invoices)
$stmt = $pdo->prepare("SELECT 
    l.name,
    l.email,
    SUM(i.total_amount) as total_spent,
    COUNT(i.id) as invoice_count
    FROM leads l
    JOIN quotes q ON l.id = q.lead_id
    JOIN invoices i ON q.id = i.quote_id
    WHERE i.created_at BETWEEN ? AND ? AND i.status != 'abandoned' AND i.parent_invoice_id IS NULL
    GROUP BY l.id
    ORDER BY total_spent DESC
    LIMIT 10");
$stmt->execute([$start_date, $end_date]);
$top_customers = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">Dashboard</a></li>
            <li class="breadcrumb-item active">Analytics</li>
        </ol>
    </nav>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-chart-bar"></i> Analytics Dashboard</h1>
        <div class="d-flex gap-2">
            <a href="/leads" class="btn btn-outline-info"><i class="fas fa-users"></i> Leads</a>
            <a href="/quotes" class="btn btn-outline-primary"><i class="fas fa-file-alt"></i> Quotes</a>
            <a href="/invoices" class="btn btn-outline-success"><i class="fas fa-receipt"></i> Invoices</a>
        </div>
    </div>
    
    <!-- Date Filter -->
    <div class="card border-0 shadow-lg mb-5">
        <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-muted small mb-1">FROM DATE</label>
                    <input type="date" name="start_date" class="form-control form-control-lg border-0 bg-light" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small mb-1">TO DATE</label>
                    <input type="date" name="end_date" class="form-control form-control-lg border-0 bg-light" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark btn-lg w-100 rounded-pill">Apply</button>
                </div>
                <div class="col-md-2">
                    <a href="/analytics" class="btn btn-outline-secondary btn-lg w-100 rounded-pill">Reset</a>
                </div>
                <div class="col-md-2">
                    <div class="text-center">
                        <small class="text-muted d-block">PERIOD</small>
                        <span class="fw-medium"><?php echo date('M d', strtotime($start_date)) . ' - ' . date('M d', strtotime($end_date)); ?></span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Financial Overview -->
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card border-0 bg-primary text-white text-center">
                <div class="card-body py-3">
                    <h6 class="mb-1">Rs <?php echo number_format($total_revenue, 0); ?></h6>
                    <small>Total Revenue</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 bg-success text-white text-center">
                <div class="card-body py-3">
                    <h6 class="mb-1">Rs <?php echo number_format($total_paid, 0); ?></h6>
                    <small>Collections</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 bg-warning text-white text-center">
                <div class="card-body py-3">
                    <h6 class="mb-1">Rs <?php echo number_format($pending_collections, 0); ?></h6>
                    <small>Pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 bg-info text-white text-center">
                <div class="card-body py-3">
                    <h6 class="mb-1">Rs <?php echo number_format($total_invoice_expenses, 0); ?></h6>
                    <small>Invoice Expenses</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 bg-danger text-white text-center">
                <div class="card-body py-3">
                    <h6 class="mb-1">Rs <?php echo number_format($total_business_expenses, 0); ?></h6>
                    <small>Business Expenses</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 bg-<?php echo $net_profit >= 0 ? 'success' : 'danger'; ?> text-white text-center">
                <div class="card-body py-3">
                    <h6 class="mb-1">Rs <?php echo number_format($net_profit, 0); ?></h6>
                    <small>Net Profit</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Ratios -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <h5 class="text-primary"><?php echo $total_revenue > 0 ? number_format(($total_paid / $total_revenue) * 100, 1) : 0; ?>%</h5>
                            <p class="text-muted mb-0">Collection Rate</p>
                            <small class="text-muted">of total revenue</small>
                        </div>
                        <div class="col-md-3">
                            <h5 class="text-info"><?php echo $total_paid > 0 ? number_format(($total_all_expenses / $total_paid) * 100, 1) : 0; ?>%</h5>
                            <p class="text-muted mb-0">Expense Ratio</p>
                            <small class="text-muted">of collections</small>
                        </div>
                        <div class="col-md-3">
                            <h5 class="text-warning"><?php echo number_format($gross_margin, 1); ?>%</h5>
                            <p class="text-muted mb-0">Gross Margin</p>
                            <small class="text-muted">after invoice expenses</small>
                        </div>
                        <div class="col-md-3">
                            <h5 class="text-<?php echo $net_margin >= 0 ? 'success' : 'danger'; ?>"><?php echo number_format($net_margin, 1); ?>%</h5>
                            <p class="text-muted mb-0">Net Margin</p>
                            <small class="text-muted">after all expenses</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Financial Breakdown</h6>
                            <small class="text-muted">Detailed expense analysis</small>
                        </div>
                        <a href="/analytics-export?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download me-1"></i>Export Excel
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border-start border-3 border-success ps-3">
                                <h6 class="mb-0 text-success">Rs <?php echo number_format($gross_profit, 0); ?></h6>
                                <small class="text-muted">Gross Profit (<?php echo number_format($gross_margin, 1); ?>%)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border-start border-3 border-warning ps-3">
                                <h6 class="mb-0 text-warning">Rs <?php echo number_format($total_invoice_expenses, 0); ?></h6>
                                <small class="text-muted">Invoice Expenses</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border-start border-3 border-danger ps-3">
                                <h6 class="mb-0 text-danger">Rs <?php echo number_format($total_business_expenses, 0); ?></h6>
                                <small class="text-muted">Business Expenses</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border-start border-3 border-info ps-3">
                                <h6 class="mb-0 text-info"><?php echo $total_paid > 0 ? number_format(($total_all_expenses / $total_paid) * 100, 1) : 0; ?>%</h6>
                                <small class="text-muted">Expense Ratio</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-lg h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="card-title mb-0">Collection Status</h5>
                    <p class="text-muted small mb-0">Payment collection overview</p>
                </div>
                <div class="card-body pt-3">
                    <div class="text-center mb-4">
                        <div class="position-relative d-inline-block">
                            <canvas id="collectionChart" width="120" height="120"></canvas>
                            <div class="position-absolute top-50 start-50 translate-middle text-center">
                                <h6 class="mb-0"><?php echo $total_revenue > 0 ? number_format(($total_paid / $total_revenue) * 100, 0) : 0; ?>%</h6>
                                <small class="text-muted">Collected</small>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Collected</span>
                        <span class="fw-medium text-success">Rs <?php echo number_format($total_paid, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Pending</span>
                        <span class="fw-medium text-warning">Rs <?php echo number_format($pending_collections, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Monthly Trends -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">Revenue Trend</h5>
                    <p class="text-muted small mb-0">Monthly performance over the last 12 months</p>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <!-- Expense Categories -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">Expense Categories</h5>
                    <p class="text-muted small mb-0">Invoice expense breakdown</p>
                </div>
                <div class="card-body">
                    <?php if (!empty($invoice_expense_categories)): ?>
                    <?php foreach ($invoice_expense_categories as $index => $category): ?>
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-<?php echo ['primary', 'success', 'warning', 'info', 'danger'][$index % 5]; ?> d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-<?php echo ['truck', 'users', 'gas-pump', 'tools', 'utensils'][$index % 5]; ?> text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0"><?php echo ucfirst($category['category']); ?></h6>
                            <small class="text-muted"><?php echo $category['count']; ?> transactions</small>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold">Rs <?php echo number_format($category['total_amount'], 2); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No expense data available</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Business Insights -->
    <div class="row g-4 mt-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">Business Expenses</h5>
                    <p class="text-muted small mb-0">Operational cost breakdown</p>
                </div>
                <div class="card-body">
                    <?php if (!empty($business_expense_categories)): ?>
                    <?php foreach ($business_expense_categories as $index => $category): ?>
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-<?php echo ['danger', 'warning', 'info', 'secondary', 'dark'][$index % 5]; ?> d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-<?php echo ['building', 'car', 'wifi', 'phone', 'coffee'][$index % 5]; ?> text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0"><?php echo ucfirst($category['category']); ?></h6>
                            <small class="text-muted"><?php echo $category['count']; ?> transactions</small>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold">Rs <?php echo number_format($category['total_amount'], 2); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-building fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No business expense data</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Top Customers -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">Top Customers</h5>
                    <p class="text-muted small mb-0">Highest value customers this period</p>
                </div>
                <div class="card-body">
                    <?php if (!empty($top_customers)): ?>
                    <?php foreach (array_slice($top_customers, 0, 5) as $index => $customer): ?>
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <span class="text-white fw-bold"><?php echo strtoupper(substr($customer['name'], 0, 1)); ?></span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0"><?php echo $customer['name']; ?></h6>
                            <small class="text-muted"><?php echo $customer['invoice_count']; ?> orders</small>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold">Rs <?php echo number_format($customer['total_spent'], 2); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No customer data available</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Chart
const ctx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [<?php echo "'" . implode("','", array_column($monthly_trends, 'month')) . "'"; ?>],
        datasets: [{
            label: 'Revenue',
            data: [<?php echo implode(',', array_column($monthly_trends, 'revenue')); ?>],
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#667eea',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                border: {
                    display: false
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                },
                border: {
                    display: false
                },
                ticks: {
                    callback: function(value) {
                        return 'Rs ' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Collection Chart
const collectionCtx = document.getElementById('collectionChart').getContext('2d');
const collectionChart = new Chart(collectionCtx, {
    type: 'doughnut',
    data: {
        datasets: [{
            data: [<?php echo $total_paid; ?>, <?php echo $pending_collections; ?>],
            backgroundColor: ['#28a745', '#ffc107'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: false,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>