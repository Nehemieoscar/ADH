<?php
include 'config.php';

if (!est_connecte()) {
    http_response_code(401);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $mode_sombre = $input['mode_sombre'] ? 1 : 0;
    
    $stmt = $pdo->prepare("UPDATE utilisateurs SET mode_sombre = ? WHERE id = ?");
    if ($stmt->execute([$mode_sombre, $_SESSION['utilisateur_id']])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>