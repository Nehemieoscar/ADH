<?php
/**
 * API: Créer un quiz avec questions
 * POST /dashboard/api/add_quiz.php
 */
header('Content-Type: application/json');
require_once '../../config.php';

if (!est_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['module_id']) || !isset($input['titre']) || !isset($input['questions'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données obligatoires manquantes']);
    exit;
}

try {
    $moduleId = intval($input['module_id']);
    $titre = securiser($input['titre']);
    $description = securiser($input['description'] ?? '');
    $points_max = intval($input['points_max'] ?? 100);
    $date_limite = $input['date_limite'] ?? null;
    $tentatives_permises = intval($input['tentatives_permises'] ?? 3);
    $temps_limite_minutes = intval($input['temps_limite_minutes'] ?? null);
    $questions = $input['questions'] ?? [];

    // Vérifier que le module existe
    $stmt = $pdo->prepare("SELECT id FROM modules WHERE id = ?");
    $stmt->execute([$moduleId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Module introuvable']);
        exit;
    }

    // Insérer le quiz
    $stmt = $pdo->prepare("
        INSERT INTO quiz (module_id, titre, description, points_max, date_limite, tentatives_permises, temps_limite_minutes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$moduleId, $titre, $description, $points_max, $date_limite, $tentatives_permises, $temps_limite_minutes]);
    $quizId = $pdo->lastInsertId();

    // Insérer les questions
    foreach ($questions as $qIndex => $question) {
        $enonce = securiser($question['enonce'] ?? '');
        $type = in_array($question['type'] ?? '', ['multiple_choice', 'true_false', 'short_answer']) ? $question['type'] : 'multiple_choice';
        $points = intval($question['points'] ?? 10);
        $options = $question['options'] ?? [];

        if (!$enonce) continue;

        $qstmt = $pdo->prepare("
            INSERT INTO quiz_questions (quiz_id, enonce, type, points, ordre)
            VALUES (?, ?, ?, ?, ?)
        ");
        $qstmt->execute([$quizId, $enonce, $type, $points, $qIndex + 1]);
        $questionId = $pdo->lastInsertId();

        // Insérer les options de réponse
        if (in_array($type, ['multiple_choice', 'true_false'])) {
            foreach ($options as $oIndex => $option) {
                $ostmt = $pdo->prepare("
                    INSERT INTO quiz_reponses_options (question_id, texte_option, est_correcte, ordre)
                    VALUES (?, ?, ?, ?)
                ");
                $ostmt->execute([
                    $questionId,
                    securiser($option['texte'] ?? ''),
                    $option['est_correcte'] ? 1 : 0,
                    $oIndex + 1
                ]);
            }
        }
    }

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Quiz créé avec succès',
        'quiz_id' => $quizId,
        'questions_count' => count($questions)
    ]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
