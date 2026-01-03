<?php
/**
 * API: Ajouter une nouvelle formation/cours
 * POST /dashboard/api/add_course.php
 * 
 * Payload JSON:
 * {
 *   "titre": "string (requis)",
 *   "description": "string",
 *   "niveau": "debutant|intermediaire|avance",
 *   "duree": "int (en heures)",
 *   "prix": "decimal",
 *   "type": "presentiel|en_ligne",
 *   "parent_cours_id": "int|null (si c'est un sous-cours)"
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

    $formation_id = intval($input['formation_id']);
    $titre = securiser($input['titre']);
    $description = securiser($input['description'] ?? '');
    $niveau = $input['niveau'] ?? 'debutant';
    $duree = intval($input['duree'] ?? 0);
    $prix = floatval($input['prix'] ?? 0.00);
    $type = $input['type'] ?? 'en_ligne';
    $parent_cours_id = isset($input['parent_cours_id']) ? intval($input['parent_cours_id']) : null;
    $formateur_id = $_SESSION['utilisateur_id'];


     // Vérifier que la formation existe
    $stmt = $pdo->prepare("SELECT id FROM formations WHERE id = ?");
    $stmt->execute([$formation_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Formation introuvable']);
        exit;
    }

    // Validation des valeurs énumérées
    $valid_niveaux = ['debutant', 'intermediaire', 'avance'];
    $valid_types = ['presentiel', 'en_ligne'];

    if (!in_array($niveau, $valid_niveaux)) {
        $niveau = 'debutant';
    }
    if (!in_array($type, $valid_types)) {
        $type = 'en_ligne';
    }

    // Insérer le cours en base de données
    $stmt = $pdo->prepare("
        INSERT INTO cours (titre, description, niveau, duree, prix, formateur_id, type, formation_id, date_creation, statut)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'brouillon')
    ");

    if ($stmt->execute([$titre, $description, $niveau, $duree, $prix, $formateur_id, $type, $formation_id > 0 ? $formation_id : null])) {
        $course_id = $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Cours créé avec succès',
            'course_id' => $course_id,
            'course' => [
                'id' => $course_id,
                'formation_id' => $formation_id > 0 ? $formation_id : null,
                'titre' => $titre,
                'description' => $description,
                'niveau' => $niveau,
                'duree' => $duree,
                'prix' => $prix,
                'type' => $type,
                'date_creation' => date('Y-m-d H:i:s'),
                'statut' => 'brouillon'
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la création du cours']);
    }
} catch (Exception $e) {
    error_log("Error in add_course.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
