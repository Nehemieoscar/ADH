<?php
/**
 * API de test pour OfflineSyncService
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/users_system_integration.php';

header('Content-Type: application/json');

if (!est_connecte() || !est_admin()) {
    echo json_encode(['success' => false, 'message' => '❌ Accès refusé']);
    exit;
}

try {
    $sync = get_offline_sync_service();
    $user_id = obtenir_utilisateur_connecte();
    
    // Test 1: Mettre en file d'attente une action
    $result = $sync->queue_action(
        $user_id,
        'test_action',
        'test_entity',
        1,
        ['test_data' => 'test_value']
    );
    
    if (!$result) {
        throw new Exception('Impossible de mettre en file d\'attente l\'action');
    }
    
    // Test 2: Récupérer la file d'attente
    $queue = $sync->get_queue($user_id);
    
    if (!is_array($queue)) {
        throw new Exception('Impossible de récupérer la file d\'attente');
    }
    
    // Test 3: Obtenir le statut de synchronisation
    $status = $sync->get_sync_status($user_id);
    
    if (!is_array($status)) {
        throw new Exception('Impossible de récupérer le statut de synchronisation');
    }
    
    echo json_encode([
        'success' => true,
        'message' => '✅ OfflineSyncService fonctionne correctement<br>' .
                    'Éléments en file d\'attente: ' . count($queue) . '<br>' .
                    'Actions en attente: ' . ($status['pending'] ?? 0) . '<br>' .
                    'Actions synchronisées: ' . ($status['synced'] ?? 0) . '<br>' .
                    'Erreurs: ' . ($status['failed'] ?? 0)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '❌ Erreur: ' . htmlspecialchars($e->getMessage())
    ]);
}
?>
