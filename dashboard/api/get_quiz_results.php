<?php
// ========================================
// API: Récupérer les résultats des quiz
// ========================================

header('Content-Type: application/json');
require_once('../../config.php');
securite_admin();

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$courseId = isset($input['course_id']) ? intval($input['course_id']) : 0;
$quizId = isset($input['quiz_id']) ? intval($input['quiz_id']) : 0;
$studentName = isset($input['student_name']) ? $input['student_name'] : '';

try {
    // Requête de base
    $sql = "
        SELECT 
            qs.id as submission_id,
            u.nom as student_name,
            q.titre as quiz_titre,
            q.points_max,
            qs.score_final,
            qs.date_soumission
        FROM quiz_soumissions qs
        JOIN utilisateurs u ON qs.utilisateur_id = u.id
        JOIN quiz q ON qs.quiz_id = q.id
        WHERE 1=1
    ";

    $params = [];

    if ($quizId > 0) {
        $sql .= " AND qs.quiz_id = ?";
        $params[] = $quizId;
    }

    if (!empty($studentName)) {
        $sql .= " AND u.nom LIKE ?";
        $params[] = '%' . $studentName . '%';
    }

    $sql .= " ORDER BY qs.date_soumission DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculer les statistiques
    $totalSubmissions = count($results);
    $avgScore = 0;
    $passingCount = 0;

    foreach ($results as $result) {
        $percentage = ($result['score_final'] / $result['points_max']) * 100;
        $avgScore += $percentage;
        if ($percentage >= 60) {
            $passingCount++;
        }
    }

    $avgScore = $totalSubmissions > 0 ? $avgScore / $totalSubmissions : 0;
    $passingRate = $totalSubmissions > 0 ? ($passingCount / $totalSubmissions) * 100 : 0;

    echo json_encode([
        'success' => true,
        'results' => $results,
        'stats' => [
            'total' => $totalSubmissions,
            'avg_percentage' => $avgScore,
            'passing_rate' => $passingRate
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>
