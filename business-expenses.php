<?php
/***************************************************************
 * business-expenses.php  (FULL SINGLE FILE)
 * - Business Expenses + User Transactions (no debug mode)
 * - Tabs UI, Filters, Pagination, Add/Edit/Delete, Modals, AJAX
 ***************************************************************/

require_once 'config.php';
require_permission('leads', 'read');

$page_title = 'Business Transactions';

/* ---------------------------- Helpers ---------------------------- */

function input($key,$default=null){ return $_POST[$key] ?? $_GET[$key] ?? $default; }
function i($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ------------------------ Inline AJAX API ------------------------ */
/* Edit modal ko fill karne ke liye: GET ?ajax=user_txn&id=123 */
if (isset($_GET['ajax']) && $_GET['ajax']==='user_txn') {
    header('Content-Type: application/json');
    if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
        echo json_encode(['error'=>'invalid id']); exit;
    }
    $id = (int)$_GET['id'];
    // Only owner or admin can fetch
    if (is_admin()) {
        $stmt = $pdo->prepare("SELECT * FROM user_transactions WHERE id=? LIMIT 1");
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM user_transactions WHERE id=? AND user_id=? LIMIT 1");
        $stmt->execute([$id, $_SESSION['user_id']]);
    }
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { echo json_encode(['error'=>'not found']); exit; }
    echo json_encode($row); exit;
}

/* ------------------------- Init containers ------------------------ */
$expenses = [];
$categories = [];
$users_list = [];
$user_transactions = [];
$user_balances = [];
$total_expenses_val = 0.0;
$total_records = 0;
$total_pages = 1;

/* ------------------------ Handle POST actions --------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add_expense') {
        require_permission('leads', 'create');
        $expense_date = input('expense_date');
        $category     = sanitize_input(input('category',''));
        $description  = sanitize_input(input('description',''));
        $amount       = (float)input('amount',0);
        $payment_method = sanitize_input(input('payment_method','cash'));
        $receipt_number = trim((string)input('receipt_number','')) ?: null;
        $notes          = trim((string)input('notes','')) ?: null;

        $stmt = $pdo->prepare("
            INSERT INTO business_expenses 
                (expense_date, category, description, amount, payment_method, receipt_number, notes, created_by)
            VALUES (?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([$expense_date,$category,$description,$amount,$payment_method,$receipt_number,$notes,$_SESSION['user_id']]);

        $_SESSION['message'] = 'Business expense added successfully!';
        redirect('/business-expenses');

    } elseif ($action === 'delete_expense') {
        require_admin();
        $id = (int)input('expense_id',0);
        $stmt = $pdo->prepare("DELETE FROM business_expenses WHERE id=?");
        $stmt->execute([$id]);
        $_SESSION['message'] = 'Business expense deleted successfully!';
        redirect('/business-expenses');

    } elseif ($action === 'add_user_transaction') {
        require_permission('leads', 'create');
        $user_id = $_SESSION['user_id'];
        $transaction_type = input('transaction_type','collection');
        $amount = (float)input('amount',0);
        $description = sanitize_input(input('description',''));
        $transaction_date = input('transaction_date', date('Y-m-d'));
        $reference_type = input('reference_type','cash');

        $stmt = $pdo->prepare("
            INSERT INTO user_transactions
                (user_id, transaction_type, amount, description, transaction_date, reference_type, created_by)
            VALUES (?,?,?,?,?,?,?)
        ");
        $stmt->execute([$user_id,$transaction_type,$amount,$description,$transaction_date,$reference_type,$_SESSION['user_id']]);

        $_SESSION['message'] = 'Transaction added successfully!';
        redirect('/business-expenses#user-transactions');

    } elseif ($action === 'edit_user_transaction') {
        require_permission('leads', 'update');
        $id = (int)input('transaction_id',0);
        $transaction_type = input('transaction_type','collection');
        $amount = (float)input('amount',0);
        $description = sanitize_input(input('description',''));
        $transaction_date = input('transaction_date', date('Y-m-d'));
        $reference_type = input('reference_type','cash');

        if (is_admin()) {
            $stmt = $pdo->prepare("
                UPDATE user_transactions
                SET transaction_type=?, amount=?, description=?, transaction_date=?, reference_type=?
                WHERE id=?
            ");
            $stmt->execute([$transaction_type,$amount,$description,$transaction_date,$reference_type,$id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE user_transactions
                SET transaction_type=?, amount=?, description=?, transaction_date=?, reference_type=?
                WHERE id=? AND user_id=?
            ");
            $stmt->execute([$transaction_type,$amount,$description,$transaction_date,$reference_type,$id,$_SESSION['user_id']]);
        }

        $_SESSION['message'] = 'Transaction updated successfully!';
        redirect('/business-expenses#user-transactions');

    } elseif ($action === 'delete_user_transaction') {
        require_admin();
        $id = (int)input('transaction_id',0);
        $stmt = $pdo->prepare("DELETE FROM user_transactions WHERE id=?");
        $stmt->execute([$id]);
        $_SESSION['message'] = 'User transaction deleted successfully!';
        redirect('/business-expenses#user-transactions');
    }
}

/* ---------------- Business Expenses: Simple Query ---------- */
// Simple query without filters for now
$stmt = $pdo->query("SELECT e.*, u.username FROM business_expenses e LEFT JOIN users u ON e.created_by = u.id ORDER BY e.expense_date DESC");
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total
$total_stmt = $pdo->query("SELECT SUM(amount) FROM business_expenses");
$total_expenses_val = $total_stmt->fetchColumn() ?: 0;

// Get categories
$categories = $pdo->query("SELECT DISTINCT category FROM business_expenses WHERE category IS NOT NULL ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

$total_records = count($expenses);
$total_pages = 1;
$available_months = [];
$month_filter = '';
$category_filter = '';

/* ------------------------ User Transactions ----------------------- */
try {
    $users_list = $pdo->query("SELECT id, username FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $has_txn_tbl = $pdo->query("SHOW TABLES LIKE 'user_transactions'");
    $user_transactions_table_exists = $has_txn_tbl && $has_txn_tbl->rowCount() > 0;

    if ($user_transactions_table_exists) {
        if (is_admin()) {
            $stmt = $pdo->query("
                SELECT ut.*, u.username, c.username AS created_by_name
                FROM user_transactions ut
                LEFT JOIN users u ON u.id = ut.user_id
                LEFT JOIN users c ON c.id = ut.created_by
                ORDER BY ut.transaction_date DESC, ut.created_at DESC
                LIMIT 100
            ");
            $user_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } else {
            $stmt = $pdo->prepare("
                SELECT ut.*, u.username, c.username AS created_by_name
                FROM user_transactions ut
                LEFT JOIN users u ON u.id = ut.user_id
                LEFT JOIN users c ON c.id = ut.created_by
                WHERE ut.user_id = ?
                ORDER BY ut.transaction_date DESC, ut.created_at DESC
                LIMIT 100
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $user_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        // balances
        if (is_admin()) {
            foreach ($users_list as $u) {
                $b = $pdo->prepare("
                    SELECT
                      SUM(CASE WHEN transaction_type='collection' THEN amount ELSE 0 END) AS collections,
                      SUM(CASE WHEN transaction_type='expense'    THEN amount ELSE 0 END) AS expenses,
                      SUM(CASE WHEN transaction_type='deposit'    THEN amount ELSE 0 END) AS deposits
                    FROM user_transactions WHERE user_id=?
                ");
                $b->execute([$u['id']]);
                $row = $b->fetch(PDO::FETCH_ASSOC) ?: ['collections'=>0,'expenses'=>0,'deposits'=>0];
                $user_balances[$u['id']] = (float)$row['collections'] - (float)$row['expenses'] - (float)$row['deposits'];
            }
        } else {
            $b = $pdo->prepare("
                SELECT
                  SUM(CASE WHEN transaction_type='collection' THEN amount ELSE 0 END) AS collections,
                  SUM(CASE WHEN transaction_type='expense'    THEN amount ELSE 0 END) AS expenses,
                  SUM(CASE WHEN transaction_type='deposit'    THEN amount ELSE 0 END) AS deposits
                FROM user_transactions WHERE user_id=?
            ");
            $b->execute([$_SESSION['user_id']]);
            $row = $b->fetch(PDO::FETCH_ASSOC) ?: ['collections'=>0,'expenses'=>0,'deposits'=>0];
            $user_balances[$_SESSION['user_id']] = (float)$row['collections'] - (float)$row['expenses'] - (float)$row['deposits'];
        }
    } else {
        $user_transactions_table_exists = false;
    }
} catch (Throwable $e) {
    $users_list = [];
    $user_transactions = [];
    $user_balances = [];
    $user_transactions_table_exists = false;
}

include 'includes/header.php';
?>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-exchange-alt"></i> Business Transactions</h1>
        <div>
            <a href="/analytics" class="btn btn-outline-info"><i class="fas fa-chart-bar"></i> Analytics</a>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="transactionTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="expenses-tab" data-bs-toggle="tab" data-bs-target="#expenses" type="button" role="tab">
                <i class="fas fa-building me-2"></i>Business Expenses
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="user-transactions-tab" data-bs-toggle="tab" data-bs-target="#user-transactions" type="button" role="tab">
                <i class="fas fa-users me-2"></i>User Transactions
            </button>
        </li>
    </ul>

    <div class="tab-content" id="transactionTabsContent">

        <!-- ================= Business Expenses Tab ================= -->
        <div class="tab-pane fade show active" id="expenses" role="tabpanel" aria-labelledby="expenses-tab">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Business Expenses</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal"><i class="fas fa-plus"></i> Add Expense</button>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h3>Rs <?= number_format($total_expenses_val, 2); ?></h3>
                            <small>Total Business Expenses<?= isset($month_filter) && $month_filter ? " (".date('F Y', strtotime($month_filter.'-01')).")" : ""; ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h3><?= number_format($total_records); ?></h3>
                            <small>Total Records</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3" action="/business-expenses">
                        <div class="col-md-3">
                            <label class="form-label">Filter by Month:</label>
                            <select name="month" class="form-select">
                                <option value="">All Months</option>
                                <?php foreach (($available_months ?? []) as $month): ?>
                                    <option value="<?= i($month); ?>" <?= ($month_filter ?? '') === $month ? 'selected' : ''; ?>>
                                        <?= date('F Y', strtotime($month.'-01')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Filter by Category:</label>
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach (($categories ?? []) as $c): ?>
                                    <option value="<?= i($c); ?>" <?= ($category_filter ?? '') === $c ? 'selected' : ''; ?>><?= i($c); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100 d-block"><i class="fas fa-search"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <a href="/business-expenses" class="btn btn-secondary w-100 d-block"><i class="fas fa-times"></i> Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Expenses Table -->
            <div class="card">
                <div class="card-body">
                    <?php if (($month_filter ?? '') || ($category_filter ?? '')): ?>
                        <div class="alert alert-info">
                            <strong>Active Filters:</strong>
                            <?php if ($month_filter): ?>
                                Month: <?= date('F Y', strtotime($month_filter.'-01')); ?>
                            <?php endif; ?>
                            <?php if ($category_filter): ?>
                                <?= $month_filter ? ' | ' : ''; ?>Category: <?= i($category_filter); ?>
                            <?php endif; ?>
                            - Showing <?= number_format($total_records); ?> record(s)
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Receipt #</th>
                                    <th>Added By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($expenses)): ?>
                                <?php foreach ($expenses as $expense): ?>
                                    <tr>
                                        <td><?= date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                                        <td><span class="badge bg-secondary"><?= i($expense['category']); ?></span></td>
                                        <td><?= i($expense['description']); ?></td>
                                        <td><strong>Rs <?= number_format((float)$expense['amount'], 2); ?></strong></td>
                                        <td><?= ucfirst(str_replace('_',' ', (string)$expense['payment_method'])); ?></td>
                                        <td><?= $expense['receipt_number'] ? i($expense['receipt_number']) : '-'; ?></td>
                                        <td><?= $expense['username'] ? i($expense['username']) : 'Unknown'; ?></td>
                                        <td>
                                            <?php if (is_admin()): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this expense?')">
                                                <input type="hidden" name="action" value="delete_expense">
                                                <input type="hidden" name="expense_id" value="<?= (int)$expense['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <?php if (($month_filter ?? '') || ($category_filter ?? '')): ?>
                                            No business expenses found for the selected filters. <br>
                                            <a href="/business-expenses" class="btn btn-sm btn-outline-primary mt-2">Clear Filters</a>
                                        <?php else: ?>
                                            No business expenses found. Click "Add Expense" to get started.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Expenses pagination" class="mt-3">
                            <ul class="pagination justify-content-center">
                                <?php
                                $query_params = $_GET;
                                for ($i=1;$i<=$total_pages;$i++):
                                    $query_params['page'] = $i;
                                    $qs = http_build_query($query_params);
                                ?>
                                    <li class="page-item <?= ($i === (int)$page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?= $qs; ?>"><?= $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- ================= End Business Expenses ================= -->

        <!-- ================= User Transactions Tab ================= -->
        <div class="tab-pane fade" id="user-transactions" role="tabpanel" aria-labelledby="user-transactions-tab">
            <div class="container-fluid p-0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>User Money Tracking</h4>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserTransactionModal">
                        <i class="fas fa-plus"></i> Add Transaction
                    </button>
                </div>

                <?php if (!$user_transactions_table_exists): ?>
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Notice:</strong> User transactions table does not exist.
                </div>
                <?php endif; ?>

                <!-- Balance Card -->
                <div class="row mb-4">
                    <?php if (is_admin()): ?>
                        <?php foreach ($users_list as $u): 
                            $balance = $user_balances[$u['id']] ?? 0;
                            $card_class = $balance >= 0 ? 'bg-success' : 'bg-danger';
                            $status_text = $balance >= 0 ? 'Has' : 'Owes';
                        ?>
                        <div class="col-md-3">
                            <div class="card <?= $card_class; ?> text-white mb-3">
                                <div class="card-body text-center">
                                    <h4>Rs <?= number_format(abs($balance), 2); ?></h4>
                                    <small><?= i($u['username']); ?> (<?= $status_text; ?>)</small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php 
                            $balance = $user_balances[$_SESSION['user_id']] ?? 0;
                            $card_class = $balance >= 0 ? 'bg-success' : 'bg-danger';
                            $status_text = $balance >= 0 ? 'You Have' : 'You Owe';
                        ?>
                        <div class="col-md-4">
                            <div class="card <?= $card_class; ?> text-white mb-3">
                                <div class="card-body text-center">
                                    <h4>Rs <?= number_format(abs($balance), 2); ?></h4>
                                    <small><?= $status_text; ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Transactions Table -->
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Transactions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>User</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Description</th>
                                        <th>Reference</th>
                                        <th>Added By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($user_transactions)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <?= $user_transactions_table_exists ? 'No transactions yet. Click "Add Transaction" to get started.' : 'User transactions table not found.'; ?>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($user_transactions as $t): 
                                            $type_class = 'bg-secondary';
                                            $type_text = ucfirst($t['transaction_type']);
                                            if ($t['transaction_type']==='collection'){ $type_class='bg-success'; $type_text='Collection'; }
                                            elseif ($t['transaction_type']==='expense'){ $type_class='bg-warning text-dark'; $type_text='Expense'; }
                                            elseif ($t['transaction_type']==='deposit'){ $type_class='bg-info'; $type_text='Deposit'; }
                                        ?>
                                        <tr>
                                            <td><?= date('M d, Y', strtotime($t['transaction_date'])); ?></td>
                                            <td><strong><?= i($t['username']); ?></strong></td>
                                            <td><span class="badge <?= $type_class; ?>"><?= $type_text; ?></span></td>
                                            <td><strong>Rs <?= number_format((float)$t['amount'], 2); ?></strong></td>
                                            <td><?= i($t['description']); ?></td>
                                            <td><?= i(ucfirst((string)$t['reference_type'])); ?></td>
                                            <td><?= i($t['created_by_name'] ?: 'Unknown'); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary me-1" 
                                                        onclick="editTransaction(<?= (int)$t['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if (is_admin()): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this transaction?')">
                                                    <input type="hidden" name="action" value="delete_user_transaction">
                                                    <input type="hidden" name="transaction_id" value="<?= (int)$t['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div><!-- /card -->
            </div>
        </div>
        <!-- ================= End User Transactions ================= -->
    </div><!-- /tab-content -->
</div><!-- /container-fluid -->


<!-- ======================== Modals ======================== -->

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Add Business Expense</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="action" value="add_expense">
            <div class="mb-3">
                <label class="form-label">Expense Date *</label>
                <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Category *</label>
                <input type="text" name="category" class="form-control" placeholder="e.g., Office Rent, Fuel, Marketing" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description *</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Brief description" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Amount (Rs) *</label>
                <input type="number" name="amount" class="form-control" step="0.01" min="0" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Payment Method</label>
                <select name="payment_method" class="form-select">
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="card">Card</option>
                    <option value="cheque">Cheque</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Receipt Number</label>
                <input type="text" name="receipt_number" class="form-control" placeholder="Optional">
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes (optional)"></textarea>
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

<!-- Add User Transaction Modal -->
<div class="modal fade" id="addUserTransactionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Add User Transaction</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="action" value="add_user_transaction">
            <div class="mb-3">
                <label class="form-label">Transaction Type *</label>
                <select name="transaction_type" class="form-select" required>
                    <option value="collection">Collection (Money received by user)</option>
                    <option value="expense">Expense (Money spent by user)</option>
                    <option value="deposit">Deposit (Money given to company)</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Amount (Rs) *</label>
                <input type="number" name="amount" class="form-control" step="0.01" min="0" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description *</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Brief description of the transaction" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Transaction Date *</label>
                <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Reference Type</label>
                <select name="reference_type" class="form-select">
                    <option value="cash">Cash</option>
                    <option value="invoice">Invoice Related</option>
                    <option value="other">Other</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Add Transaction</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit User Transaction Modal -->
<div class="modal fade" id="editUserTransactionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Edit User Transaction</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="action" value="edit_user_transaction">
            <input type="hidden" name="transaction_id">
            <?php if (is_admin()): ?>
            <div class="mb-3">
                <label class="form-label">User *</label>
                <select name="user_id" class="form-select" disabled>
                    <?php foreach ($users_list as $u): ?>
                        <option value="<?= (int)$u['id']; ?>"><?= i($u['username']); ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">User cannot be changed here.</small>
            </div>
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label">Transaction Type *</label>
                <select name="transaction_type" class="form-select" required>
                    <option value="collection">Collection (Money received by user)</option>
                    <option value="expense">Expense (Money spent by user)</option>
                    <option value="deposit">Deposit (Money given to company)</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Amount (Rs) *</label>
                <input type="number" name="amount" class="form-control" step="0.01" min="0" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description *</label>
                <textarea name="description" class="form-control" rows="2" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Transaction Date *</label>
                <input type="date" name="transaction_date" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Reference Type</label>
                <select name="reference_type" class="form-select">
                    <option value="cash">Cash</option>
                    <option value="invoice">Invoice Related</option>
                    <option value="other">Other</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Transaction</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Initialize Bootstrap tabs + URL hash
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#user-transactions') {
        var userTransTab = document.getElementById('user-transactions-tab');
        if (userTransTab) new bootstrap.Tab(userTransTab).show();
    }
});

// Edit modal loader (inline AJAX)
function editTransaction(id) {
    fetch('?ajax=user_txn&id=' + encodeURIComponent(id))
        .then(r => r.json())
        .then(data => {
            if (data.error) { alert('Error: ' + data.error); return; }
            const m = document.getElementById('editUserTransactionModal');
            m.querySelector('[name="transaction_id"]').value = data.id;
            const userSelect = m.querySelector('[name="user_id"]');
            if (userSelect) userSelect.value = data.user_id;
            m.querySelector('[name="transaction_type"]').value = data.transaction_type;
            m.querySelector('[name="amount"]').value = data.amount;
            m.querySelector('[name="description"]').value = data.description;
            m.querySelector('[name="transaction_date"]').value = data.transaction_date;
            m.querySelector('[name="reference_type"]').value = data.reference_type;
            new bootstrap.Modal(m).show();
        })
        .catch(err => { console.error(err); alert('Failed to load transaction'); });
}
</script>

<?php include 'includes/footer.php'; ?>
