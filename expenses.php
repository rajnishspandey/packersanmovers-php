<?php
require_once 'config.php';
require_admin();

$page_title = 'Expenses Management';
$invoice_id = $_GET['invoice_id'] ?? 0;

// Get invoice details
$stmt = $pdo->prepare("SELECT i.*, q.quote_number, l.name FROM invoices i JOIN quotes q ON i.quote_id = q.id JOIN leads l ON q.lead_id = l.id WHERE i.id = ?");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    die('Invoice not found');
}

// Handle expense creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_expense') {
    $category = $_POST['category'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $expense_date = $_POST['expense_date'];
    
    $stmt = $pdo->prepare("INSERT INTO expenses (invoice_id, category, description, amount, expense_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$invoice_id, $category, $description, $amount, $expense_date, $_SESSION['user_id']]);
    
    $_SESSION['message'] = 'Expense added successfully!';
    redirect("/expenses?invoice_id=$invoice_id");
}

// Handle expense deletion (AJAX or form)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_expense') {
    $expense_id = intval($_POST['expense_id'] ?? 0);
    if ($expense_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ? AND invoice_id = ?");
        $stmt->execute([$expense_id, $invoice_id]);
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            // AJAX request
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } else {
            $_SESSION['message'] = 'Expense deleted successfully!';
            redirect("/expenses?invoice_id=$invoice_id");
        }
    }
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid expense ID']);
        exit;
    }
}

// Get expenses for this invoice
$stmt = $pdo->prepare("SELECT * FROM expenses WHERE invoice_id = ? ORDER BY expense_date DESC");
$stmt->execute([$invoice_id]);
$expenses = $stmt->fetchAll();

// Calculate totals
$total_expenses = array_sum(array_column($expenses, 'amount'));
$profit = $invoice['total_amount'] - $total_expenses;

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Expenses - <?php echo $invoice['invoice_number']; ?></h1>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">Add Expense</button>
            <a href="/invoices" class="btn btn-secondary">Back to Invoices</a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Invoice Amount</h5>
                    <h3>Rs <?php echo number_format($invoice['total_amount'], 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5>Total Expenses</h5>
                    <h3>Rs <?php echo number_format($total_expenses, 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-<?php echo $profit >= 0 ? 'success' : 'warning'; ?> text-white">
                <div class="card-body">
                    <h5><?php echo $profit >= 0 ? 'Profit' : 'Loss'; ?></h5>
                    <h3>Rs <?php echo number_format(abs($profit), 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>Profit Margin</h5>
                    <h3><?php echo $invoice['total_amount'] > 0 ? number_format(($profit / $invoice['total_amount']) * 100, 1) : 0; ?>%</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Expense Details - <?php echo $invoice['name']; ?></h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                            <td>
                                <span class="badge bg-secondary"><?php echo ucfirst($expense['category']); ?></span>
                            </td>
                            <td><?php echo $expense['description']; ?></td>
                            <td>Rs <?php echo number_format($expense['amount'], 2); ?></td>
                            <td>
                                <form method="POST" class="d-inline delete-expense-form" data-expense-id="<?php echo $expense['id']; ?>">
                                    <input type="hidden" name="action" value="delete_expense">
                                    <input type="hidden" name="expense_id" value="<?php echo $expense['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($expenses)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No expenses recorded yet</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_expense">
                    
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select" required>
                            <option value="">Select category...</option>
                            <option value="fuel">Fuel</option>
                            <option value="labor">Labor</option>
                            <option value="materials">Materials</option>
                            <option value="vehicle">Vehicle</option>
                            <option value="food">Food & Accommodation</option>
                            <option value="toll">Toll & Parking</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" name="amount" class="form-control" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Expense Date</label>
                        <input type="date" name="expense_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.delete-expense-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to delete this expense?')) {
            var formData = new FormData(form);
            fetch('', {
                method: 'POST',
                body: formData,
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Delete failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(() => alert('Delete failed due to network error.'));
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>