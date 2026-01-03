<?php
/**
 * API: Soumettre un quiz et calculer les résultats
 * POST /dashboard/api/submit_quiz.php
 */
header('Content-Type: application/json');
require_once '../../config.php';

if (!est_connecte()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['quiz_id']) || !isset($input['answers'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

try {
    $quizId = intval($input['quiz_id']);
    $userId = intval($_SESSION['utilisateur_id']);
    $answers = $input['answers']; // ['question_id' => 'answer_text' ou 'option_id']

    // Vérifier que le quiz existe
    $stmt = $pdo->prepare("SELECT * FROM quiz WHERE id = ?");
    $stmt->execute([$quizId]);
    $quiz = $stmt->fetch();
    if (!$quiz) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Quiz introuvable']);
        exit;
    }

    // Créer une soumission de quiz
    $sub = $pdo->prepare("
        INSERT INTO quiz_soumissions (quiz_id, utilisateur_id, statut)
        VALUES (?, ?, 'completee')
    ");
    $sub->execute([$quizId, $userId]);
    $submissionId = $pdo->lastInsertId();

    $totalScore = 0;

    // Calculer les scores pour chaque question
    foreach ($answers as $questionId => $answerData) {
        $qId = intval($questionId);

        // Récupérer la question
        $qstmt = $pdo->prepare("SELECT type, points FROM quiz_questions WHERE id = ?");
        $qstmt->execute([$qId]);
        $question = $qstmt->fetch();
        if (!$question) continue;

        $score = 0;

        // Évaluer selon le type de question
        if ($question['type'] === 'multiple_choice' || $question['type'] === 'true_false') {
            $selectedOptionId = intval($answerData['option_id'] ?? 0);
            if ($selectedOptionId > 0) {
                // Vérifier si la réponse est correcte
                $ostmt = $pdo->prepare("SELECT est_correcte FROM quiz_reponses_options WHERE id = ?");
                $ostmt->execute([$selectedOptionId]);
                $option = $ostmt->fetch();
                if ($option && $option['est_correcte']) {
                    $score = $question['points'];
                }
            }
        } elseif ($question['type'] === 'short_answer') {
            // Pour les réponses courtes, on enregistre la réponse sans évaluation automatique
            // L'admin devra évaluer manuellement
            $score = 0; // À compléter par l'administrateur
        }

        $totalScore += $score;

        // Enregistrer la réponse de l'étudiant
        $rans = $pdo->prepare("
            INSERT INTO quiz_reponses_etudiant (soumission_id, question_id, reponse_texte, reponse_option_id, points_obtenus)
            VALUES (?, ?, ?, ?, ?)
        ");
        $rans->execute([
            $submissionId,
            $qId,
            $answerData['texte'] ?? null,
            $answerData['option_id'] ?? null,
            $score
        ]);
    }

    // Mettre à jour le score final
    $upd = $pdo->prepare("UPDATE quiz_soumissions SET score_final = ? WHERE id = ?");
    $upd->execute([$totalScore, $submissionId]);

    // Envoyer le résultat à l'étudiant et l'admin
    $percentage = ($quiz['points_max'] > 0) ? round(($totalScore / $quiz['points_max']) * 100) : 0;

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Quiz soumis avec succès',
        'submission_id' => $submissionId,
        'score' => $totalScore,
        'max_points' => $quiz['points_max'],
        'percentage' => $percentage
    ]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
