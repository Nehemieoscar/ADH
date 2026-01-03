<?php
/**
 * API: Mettre à jour l'ordre des modules (drag-and-drop)
 * POST /dashboard/api/update_module_order.php
 */
header('Content-Type: application/json');
require_once '../../config.php';

if (!est_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['modules']) || !is_array($input['modules'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    foreach ($input['modules'] as $index => $moduleId) {
        $stmt = $pdo->prepare("UPDATE modules SET ordre = ? WHERE id = ?");
        $stmt->execute([$index + 1, intval($moduleId)]);
    }

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Ordre des modules mis à jour']);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
