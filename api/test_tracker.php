<?php
/**
 * API de test pour ActivityTracker
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/users_system_integration.php';

header('Content-Type: application/json');

if (!est_connecte() || !est_admin()) {
    echo json_encode(['success' => false, 'message' => '❌ Accès refusé']);
    exit;
}

try {
    $tracker = get_activity_tracker();
    $user_id = obtenir_utilisateur_connecte();
    
    // Test 1: Enregistrer une activité
    $result = $tracker->log_activity(
        $user_id,
        'test_system',
        'Test du système de gestion des utilisateurs',
        'test',
        1
    );
    
    if (!$result) {
        throw new Exception('Impossible d\'enregistrer l\'activité');
    }
    
    // Test 2: Récupérer l'historique
    $activities = $tracker->get_activity_history($user_id, 5);
    
    if (!is_array($activities)) {
        throw new Exception('Impossible de récupérer l\'historique');
    }
    
    // Test 3: Obtenir les stats
    $stats = $tracker->get_activity_stats($user_id);
    
    if (!is_array($stats)) {
        throw new Exception('Impossible de récupérer les statistiques');
    }
    
    echo json_encode([
        'success' => true,
        'message' => '✅ ActivityTracker fonctionne correctement<br>' .
                    'Activités enregistrées: ' . count($activities) . '<br>' .
                    'Statistiques disponibles: ' . count($stats)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '❌ Erreur: ' . htmlspecialchars($e->getMessage())
    ]);
}
?>
