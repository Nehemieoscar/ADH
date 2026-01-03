<?php
/**
 * API de test pour BehaviorAnalyzer
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/users_system_integration.php';

header('Content-Type: application/json');

if (!est_connecte() || !est_admin()) {
    echo json_encode(['success' => false, 'message' => '❌ Accès refusé']);
    exit;
}

try {
    $analyzer = get_behavior_analyzer();
    $user_id = obtenir_utilisateur_connecte();
    
    // Test 1: Analyser le comportement de l'utilisateur
    $analysis = $analyzer->analyze_user_behavior($user_id, 30);
    
    if (!$analysis) {
        throw new Exception('Impossible d\'analyser le comportement');
    }
    
    // Test 2: Récupérer les heures de pic
    $peak_hours = $analyzer->get_peak_hours($user_id);
    
    // Test 3: Récupérer le jour le plus actif
    $active_day = $analyzer->get_most_active_day($user_id);
    
    // Test 4: Calculer le score d'engagement
    $engagement = $analyzer->calculate_engagement_score($user_id);
    
    if ($engagement === false) {
        throw new Exception('Impossible de calculer le score d\'engagement');
    }
    
    // Test 5: Calculer le score d'assiduité
    $attendance = $analyzer->calculate_attendance_score($user_id);
    
    if ($attendance === false) {
        throw new Exception('Impossible de calculer le score d\'assiduité');
    }
    
    echo json_encode([
        'success' => true,
        'message' => '✅ BehaviorAnalyzer fonctionne correctement<br>' .
                    'Heures de pic: ' . ($peak_hours ? $peak_hours : 'Aucune') . '<br>' .
                    'Jour le plus actif: ' . ($active_day ? $active_day : 'N/A') . '<br>' .
                    'Score d\'engagement: ' . round($engagement, 2) . '%<br>' .
                    'Score d\'assiduité: ' . round($attendance, 2) . '%'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '❌ Erreur: ' . htmlspecialchars($e->getMessage())
    ]);
}
?>
