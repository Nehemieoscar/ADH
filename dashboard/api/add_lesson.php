<?php
/**
 * API: Ajouter une leçon à un module
 * POST /dashboard/api/add_lesson.php
 * 
 * Payload JSON:
 * {
 *   "module_id": "int (requis)",
 *   "titre": "string (requis)",
 *   "contenu": "string (texte de la leçon)"
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
    if (!est_admin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accès refusé']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['module_id']) || !isset($input['titre'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'module_id et titre sont obligatoires']);
        exit;
    }

    $module_id = intval($input['module_id']);
    $titre = securiser($input['titre']);
    $contenu = $input['contenu'] ?? ''; // Allow HTML

    // Vérifier que le module existe
    $stmt = $pdo->prepare("SELECT id FROM modules WHERE id = ?");
    $stmt->execute([$module_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Module introuvable']);
        exit;
    }

    // Insérer la leçon
    $stmt = $pdo->prepare("
        INSERT INTO lecons (module_id, titre, contenu, ordre)
        VALUES (?, ?, ?, (SELECT COALESCE(MAX(ordre), 0) + 1 FROM lecons WHERE module_id = ?))
    ");

    if ($stmt->execute([$module_id, $titre, $contenu, $module_id])) {
        $lesson_id = $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Leçon créée avec succès',
            'lesson_id' => $lesson_id,
            'lesson' => [
                'id' => $lesson_id,
                'module_id' => $module_id,
                'titre' => $titre,
                'contenu' => $contenu
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la création de la leçon']);
    }
} catch (Exception $e) {
    error_log("Error in add_lesson.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>
