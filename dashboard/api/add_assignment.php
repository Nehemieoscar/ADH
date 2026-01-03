<?php
/**
 * API: Ajouter un devoir
 * POST /dashboard/api/add_assignment.php
 * 
 * Accepte multipart/form-data pour upload de PDF
 */
header('Content-Type: application/json');
require_once '../../config.php';

if (!est_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

try {
    $moduleId = intval($_POST['module_id'] ?? 0);
    $titre = securiser($_POST['titre'] ?? '');
    $description = securiser($_POST['description'] ?? '');
    $type_remise = in_array($_POST['type_remise'] ?? '', ['individuel', 'groupe']) ? $_POST['type_remise'] : 'individuel';
    $date_limite = $_POST['date_limite'] ?? null;
    $points_max = intval($_POST['points_max'] ?? 100);

    if (!$moduleId || !$titre) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'module_id et titre obligatoires']);
        exit;
    }

    // Vérifier que le module existe
    $stmt = $pdo->prepare("SELECT id FROM modules WHERE id = ?");
    $stmt->execute([$moduleId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Module introuvable']);
        exit;
    }

    // Gérer l'upload du PDF
    $fichier_pdf = null;
    if (isset($_FILES['fichier_pdf']) && $_FILES['fichier_pdf']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['fichier_pdf'];
        if ($file['type'] === 'application/pdf' && $file['size'] <= 50 * 1024 * 1024) { // 50 MB max
            $upload_dir = __DIR__ . '/../../uploads/assignments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $filename = uniqid() . '.pdf';
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                $fichier_pdf = 'uploads/assignments/' . $filename;
            }
        }
    }

    // Insérer le devoir
    $stmt = $pdo->prepare("
        INSERT INTO devoirs (module_id, titre, description, fichier_pdf, type_remise, date_limite, points_max)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$moduleId, $titre, $description, $fichier_pdf, $type_remise, $date_limite, $points_max]);
    $assignmentId = $pdo->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Devoir créé avec succès',
        'assignment_id' => $assignmentId
    ]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
