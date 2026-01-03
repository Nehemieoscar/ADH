<?php
/**
 * API de test pour NotificationService
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/users_system_integration.php';

header('Content-Type: application/json');

if (!est_connecte() || !est_admin()) {
    echo json_encode(['success' => false, 'message' => '❌ Accès refusé']);
    exit;
}

try {
    $notif = get_notification_service();
    $user_id = obtenir_utilisateur_connecte();
    $admin_id = $user_id;
    
    // Test 1: Envoyer une notification de test
    $result = $notif->send_notification(
        $user_id,
        'Test du système',
        'Ceci est une notification de test du système de gestion des utilisateurs',
        'info',
        'normale',
        $admin_id,
        'test_users_system.php'
    );
    
    if (!$result) {
        throw new Exception('Impossible d\'envoyer la notification');
    }
    
    // Test 2: Récupérer les notifications
    $notifications = $notif->get_notifications($user_id, 10);
    
    if (!is_array($notifications)) {
        throw new Exception('Impossible de récupérer les notifications');
    }
    
    // Test 3: Vérifier le compte non-lu
    $unread = $notif->get_unread_count($user_id);
    
    if ($unread === false) {
        throw new Exception('Impossible de récupérer le compte non-lu');
    }
    
    echo json_encode([
        'success' => true,
        'message' => '✅ NotificationService fonctionne correctement<br>' .
                    'Notifications trouvées: ' . count($notifications) . '<br>' .
                    'Non-lues: ' . $unread
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '❌ Erreur: ' . htmlspecialchars($e->getMessage())
    ]);
}
?>
