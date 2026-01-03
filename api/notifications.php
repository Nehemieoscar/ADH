<?php
header('Content-Type: application/json');
include '../config.php';

if (!est_connecte()) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

require_once 'includes/NotificationService.php';
$notification_service = get_notification_service();

$action = $_GET['action'] ?? '';
$utilisateur_id = $_SESSION['utilisateur_id'];

switch ($action) {
    case 'get_notifications':
        // Récupérer les notifications
        $limit = (int)($_GET['limit'] ?? 10);
        $notifications = $notification_service->get_notifications($utilisateur_id, $limit);
        $unread_count = $notification_service->get_unread_count($utilisateur_id);
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unread_count
        ]);
        break;
    
    case 'mark_as_read':
        // Marquer une notification comme lue
        $notification_id = (int)($_POST['notification_id'] ?? 0);
        if (!$notification_id) {
            echo json_encode(['success' => false, 'message' => 'ID manquant']);
            exit;
        }
        
        if ($notification_service->mark_as_read($notification_id, $utilisateur_id)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur de mise à jour']);
        }
        break;
    
    case 'delete_notification':
        // Supprimer une notification
        $notification_id = (int)($_POST['notification_id'] ?? 0);
        if (!$notification_id) {
            echo json_encode(['success' => false, 'message' => 'ID manquant']);
            exit;
        }
        
        if ($notification_service->delete_notification($notification_id, $utilisateur_id)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur de suppression']);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Action inconnue']);
}
?>
