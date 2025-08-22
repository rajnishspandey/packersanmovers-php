<?php
require_once 'config.php';

// Simple test to get expenses
$stmt = $pdo->query("SELECT e.*, u.username FROM business_expenses e LEFT JOIN users u ON e.created_by = u.id ORDER BY expense_date DESC");
$expenses = $stmt->fetchAll();

echo "Found " . count($expenses) . " expenses:\n";
foreach ($expenses as $expense) {
    echo "- " . $expense['expense_date'] . " | " . $expense['category'] . " | Rs " . $expense['amount'] . "\n";
}
?>