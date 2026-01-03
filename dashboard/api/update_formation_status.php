<?php
// update_formation_status.php
// Update formation status (brouillon, en_cours, termine)

include '../../config.php';

header('Content-Type: application/json');

// Vérifier que l'utilisateur est connecté et est un admin
if (!est_connecte() || $_SESSION['utilisateur_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);

// Validation
if (empty($data['formation_id']) || empty($data['statut'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$formation_id = intval($data['formation_id']);
$statut = $data['statut'];

// Valider le statut
$statuts_valides = ['brouillon', 'en_cours', 'termine'];
if (!in_array($statut, $statuts_valides)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
    exit;
}

try {
    // Vérifier que la formation existe
    $stmt = $pdo->prepare("SELECT id FROM formations WHERE id = ?");
    $stmt->execute([$formation_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Formation non trouvée']);
        exit;
    }

    // Mettre à jour le statut
    $stmt = $pdo->prepare("UPDATE formations SET statut = ? WHERE id = ?");
    $stmt->execute([$statut, $formation_id]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Statut de la formation mis à jour avec succès'
    ]);

} catch (PDOException $e) {
    error_log("DB Error in update_formation_status.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur base de données']);
}
