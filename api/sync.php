<?php
header('Content-Type: application/json');
include 'config.php';

if (!est_connecte()) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

require_once 'includes/OfflineSyncService.php';
$sync_service = get_offline_sync_service();

$action = $_GET['action'] ?? '';
$utilisateur_id = $_SESSION['utilisateur_id'];

switch ($action) {
    case 'sync_all':
        // Synchroniser toutes les actions en attente
        $success = $sync_service->sync_pending_actions($utilisateur_id);
        
        if ($success) {
            $status = $sync_service->get_sync_status($utilisateur_id);
            echo json_encode([
                'success' => true,
                'message' => 'Synchronisation en cours',
                'status' => $status
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur de synchronisation']);
        }
        break;
    
    case 'get_status':
        // Récupérer le statut de synchronisation
        $status = $sync_service->get_sync_status($utilisateur_id);
        echo json_encode([
            'success' => true,
            'status' => $status
        ]);
        break;
    
    case 'get_queue':
        // Récupérer la file d'attente
        $queue = $sync_service->get_queue($utilisateur_id);
        echo json_encode([
            'success' => true,
            'queue' => $queue
        ]);
        break;
    
    case 'queue_action':
        // Ajouter une action à la file d'attente
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['action_type']) || !isset($data['entite_type'])) {
            echo json_encode(['success' => false, 'message' => 'Données manquantes']);
            exit;
        }
        
        $success = $sync_service->queue_action(
            $utilisateur_id,
            $data['action_type'],
            $data['entite_type'],
            $data['entite_id'] ?? null,
            json_encode($data['donnees'] ?? [])
        );
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Action ajoutée à la file d\'attente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout']);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Action inconnue']);
}
?>
