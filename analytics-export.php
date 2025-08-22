<?php
require_once 'config.php';
require_permission('analytics', 'read');

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Get all analytics data
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_invoices,
    SUM(total_amount) as total_revenue,
    SUM(paid_amount) as total_paid,
    AVG(total_amount) as avg_invoice_value
    FROM invoices 
    WHERE created_at BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$revenue_stats = $stmt->fetch();

$stmt = $pdo->prepare("SELECT 
    SUM(e.amount) as total_expenses,
    COUNT(*) as total_expense_records
    FROM expenses e
    JOIN invoices i ON e.invoice_id = i.id
    WHERE i.created_at BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$invoice_expense_stats = $stmt->fetch();

$stmt = $pdo->prepare("SELECT 
    SUM(amount) as total_business_expenses,
    COUNT(*) as total_business_expense_records
    FROM business_expenses 
    WHERE expense_date BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$business_expense_stats = $stmt->fetch();

// Calculate values
$total_revenue = $revenue_stats['total_revenue'] ?? 0;
$total_paid = $revenue_stats['total_paid'] ?? 0;
$pending_collections = $total_revenue - $total_paid;
$total_invoice_expenses = $invoice_expense_stats['total_expenses'] ?? 0;
$total_business_expenses = $business_expense_stats['total_business_expenses'] ?? 0;
$gross_profit = $total_paid - $total_invoice_expenses;
$net_profit = $gross_profit - $total_business_expenses;

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Analytics_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

echo "Analytics Report\n";
echo "Period: " . date('M d, Y', strtotime($start_date)) . " to " . date('M d, Y', strtotime($end_date)) . "\n\n";

echo "FINANCIAL OVERVIEW\n";
echo "Metric\tAmount\n";
echo "Total Revenue\t" . number_format($total_revenue, 2) . "\n";
echo "Collections\t" . number_format($total_paid, 2) . "\n";
echo "Pending Collections\t" . number_format($pending_collections, 2) . "\n";
echo "Invoice Expenses\t" . number_format($total_invoice_expenses, 2) . "\n";
echo "Business Expenses\t" . number_format($total_business_expenses, 2) . "\n";
echo "Gross Profit\t" . number_format($gross_profit, 2) . "\n";
echo "Net Profit\t" . number_format($net_profit, 2) . "\n\n";

echo "KEY RATIOS\n";
echo "Metric\tPercentage\n";
echo "Collection Rate\t" . ($total_revenue > 0 ? number_format(($total_paid / $total_revenue) * 100, 1) : 0) . "%\n";
echo "Expense Ratio\t" . ($total_paid > 0 ? number_format((($total_invoice_expenses + $total_business_expenses) / $total_paid) * 100, 1) : 0) . "%\n";
echo "Gross Margin\t" . ($total_paid > 0 ? number_format(($gross_profit / $total_paid) * 100, 1) : 0) . "%\n";
echo "Net Margin\t" . ($total_paid > 0 ? number_format(($net_profit / $total_paid) * 100, 1) : 0) . "%\n";
?>