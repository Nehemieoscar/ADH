<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/PaymentProcessor.php';

// Vérifier les permissions admin
session_start();
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['utilisateur_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès refusé']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['transaction_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Transaction ID manquant']);
    exit;
}

try {
    $pdo = getPDO();
    $processor = new PaymentProcessor($pdo);
    
    $result = $processor->processRefund(
        $data['transaction_id'],
        $data['amount'] ?? null,
        $data['reason'] ?? 'admin_refund'
    );
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Refund Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors du remboursement'
    ]);
}
?>