<?php
/**
 * API: Ajouter un module à un cours
 * POST /dashboard/api/add_module.php
 * 
 * Payload JSON:
 * {
 *   "cours_id": "int (requis)",
 *   "titre": "string (requis)",
 *   "description": "string",
 *   "ordre": "int",
 *   "duree_estimee": "int (en minutes)"
 * }
 */

header('Content-Type: application/json');

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

    if (!isset($input['cours_id']) || !isset($input['titre'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'cours_id et titre sont obligatoires']);
        exit;
    }

    $cours_id = intval($input['cours_id']);
    $titre = securiser($input['titre']);
    $description = securiser($input['description'] ?? '');
    $ordre = intval($input['ordre'] ?? 1);
    $duree_estimee = intval($input['duree_estimee'] ?? 0);

    // Vérifier que le cours existe
    $stmt = $pdo->prepare("SELECT id FROM cours WHERE id = ?");
    $stmt->execute([$cours_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Cours introuvable']);
        exit;
    }

    // Insérer le module
    $stmt = $pdo->prepare("
        INSERT INTO modules (cours_id, titre, description, ordre, duree_estimee)
        VALUES (?, ?, ?, ?, ?)
    ");

    if ($stmt->execute([$cours_id, $titre, $description, $ordre, $duree_estimee])) {
        $module_id = $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Module créé avec succès',
            'module_id' => $module_id,
            'module' => [
                'id' => $module_id,
                'cours_id' => $cours_id,
                'titre' => $titre,
                'description' => $description,
                'ordre' => $ordre,
                'duree_estimee' => $duree_estimee
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la création du module']);
    }
} catch (Exception $e) {
    error_log("Error in add_module.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>
