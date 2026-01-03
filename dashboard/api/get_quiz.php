<?php
// ========================================
// API: Récupérer un quiz avec ses questions
// ========================================

header('Content-Type: application/json');
require_once('../../config.php');

if (!isset($_GET['quiz_id'])) {
    echo json_encode(['success' => false, 'message' => 'Quiz ID requis']);
    exit;
}

$quiz_id = intval($_GET['quiz_id']);

try {
    // Récupérer le quiz
    $stmt = $pdo->prepare("
        SELECT id, titre, description, points_max 
        FROM quiz 
        WHERE id = ?
    ");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quiz) {
        echo json_encode(['success' => false, 'message' => 'Quiz non trouvé']);
        exit;
    }

    // Récupérer les questions
    $stmt = $pdo->prepare("
        SELECT id, enonce, type, points 
        FROM quiz_questions 
        WHERE quiz_id = ? 
        ORDER BY ordre
    ");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les options pour chaque question
    foreach ($questions as &$question) {
        $stmt = $pdo->prepare("
            SELECT id, texte_option, est_correcte 
            FROM quiz_reponses_options 
            WHERE question_id = ? 
            ORDER BY ordre
        ");
        $stmt->execute([$question['id']]);
        $question['options'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $quiz['questions'] = $questions;

    echo json_encode([
        'success' => true,
        'data' => $quiz
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>
