<?php
/**
 * API: Ajouter une nouvelle formation
 * POST /dashboard/api/add_formation.php
 * 
 * Payload JSON:
 * {
 *   "titre": "string (requis)",
 *   "description": "string",
 *   "statut": "brouillon|en_cours|termine",
 *   "date_disponibilite": "YYYY-MM-DD" (si statut = brouillon)
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
    // Vérifier que l'utilisateur est connecté et est un admin
    if (!est_connecte() || $_SESSION['utilisateur_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accès refusé']);
        exit;
    }

    // Récupérer et valider les données
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['titre']) || empty(trim($input['titre']))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Le titre est obligatoire']);
        exit;
    }

    $titre = securiser($input['titre']);
    $description = securiser($input['description'] ?? '');
    $statut = $input['statut'] ?? 'brouillon';
    $date_disponibilite = isset($input['date_disponibilite']) ? $input['date_disponibilite'] : null;

    // Validation du statut
    $valid_statuts = ['brouillon', 'en_cours', 'termine'];
    if (!in_array($statut, $valid_statuts)) {
        $statut = 'brouillon';
    }

    // Insérer la formation en base de données
    $stmt = $pdo->prepare("
        INSERT INTO formations (titre, description, statut, date_disponibilite, date_creation)
        VALUES (?, ?, ?, ?, NOW())
    ");

    if ($stmt->execute([$titre, $description, $statut, $date_disponibilite])) {
        $formation_id = $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Formation créée avec succès',
            'formation_id' => $formation_id,
            'formation' => [
                'id' => $formation_id,
                'titre' => $titre,
                'description' => $description,
                'statut' => $statut,
                'date_disponibilite' => $date_disponibilite,
                'date_creation' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la création de la formation']);
    }
} catch (Exception $e) {
    error_log("Error in add_formation.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>