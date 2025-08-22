<?php
require_once '../config.php';
require_permission('leads', 'read');

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Transaction ID required']);
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM user_transactions WHERE id = ?");
$stmt->execute([$id]);
$transaction = $stmt->fetch();

if (!$transaction) {
    http_response_code(404);
    echo json_encode(['error' => 'Transaction not found']);
    exit;
}

echo json_encode($transaction);
?>