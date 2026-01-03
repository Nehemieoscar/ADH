<?php
/**
 * API: Mettre à jour le statut d'une formation
 * POST /dashboard/api/update_formation_statut.php
 * 
 * Payload JSON:
 * {
 *   "formation_id": "int (requis)",
 *   "nouveau_statut": "brouillon|en_cours|termine",
 *   "date_disponibilite": "YYYY-MM-DD" (optionnel, pour brouillon)
 * }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

require_once '../../config.php';

try {
    if (!est_connecte() || $_SESSION['utilisateur_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accès refusé']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['formation_id']) || !isset($input['nouveau_statut'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'formation_id et nouveau_statut sont obligatoires']);
        exit;
    }

    $formation_id = intval($input['formation_id']);
    $nouveau_statut = $input['nouveau_statut'];
    $date_disponibilite = isset($input['date_disponibilite']) ? $input['date_disponibilite'] : null;

    // Validation du statut
    $valid_statuts = ['brouillon', 'en_cours', 'termine'];
    if (!in_array($nouveau_statut, $valid_statuts)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Statut invalide']);
        exit;
    }

    // Vérifier que la formation existe
    $stmt = $pdo->prepare("SELECT id FROM formations WHERE id = ?");
    $stmt->execute([$formation_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Formation introuvable']);
        exit;
    }

    // Mettre à jour le statut
    if ($nouveau_statut === 'brouillon' && $date_disponibilite) {
        $stmt = $pdo->prepare("
            UPDATE formations 
            SET statut = ?, date_disponibilite = ?
            WHERE id = ?
        ");
        $result = $stmt->execute([$nouveau_statut, $date_disponibilite, $formation_id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE formations 
            SET statut = ?
            WHERE id = ?
        ");
        $result = $stmt->execute([$nouveau_statut, $formation_id]);
    }

    if ($result) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Statut mis à jour avec succès',
            'nouveau_statut' => $nouveau_statut,
            'date_disponibilite' => $date_disponibilite
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
    }
} catch (Exception $e) {
    error_log("Error in update_formation_statut.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>