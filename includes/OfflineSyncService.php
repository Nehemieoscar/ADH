<?php
/**
 * Service de synchronisation hors-ligne
 * Gère les files d'attente de synchronisation
 */

class OfflineSyncService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Ajoute une action à la file de synchronisation
     */
    public function queue_action($utilisateur_id, $action_type, $entite_type, $entite_id, $donnees_json) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO offline_sync_queue 
                (utilisateur_id, action_type, entite_type, entite_id, donnees_json, statut)
                VALUES (?, ?, ?, ?, ?, 'en_attente')
            ");
            
            return $stmt->execute([
                $utilisateur_id,
                $action_type,
                $entite_type,
                $entite_id,
                $donnees_json
            ]);
        } catch (Exception $e) {
            error_log("Erreur queue_action: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère la file d'attente d'un utilisateur
     */
    public function get_queue($utilisateur_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM offline_sync_queue
                WHERE utilisateur_id = ? AND statut IN ('en_attente', 'erreur')
                ORDER BY date_creation ASC
            ");
            $stmt->execute([$utilisateur_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erreur get_queue: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Synchronise les actions en attente
     */
    public function sync_pending_actions($utilisateur_id) {
        try {
            $queue = $this->get_queue($utilisateur_id);
            
            foreach ($queue as $action) {
                $success = $this->process_action($action);
                
                if ($success) {
                    // Marquer comme synchronisée
                    $stmt = $this->pdo->prepare("
                        UPDATE offline_sync_queue
                        SET statut = 'synchronise', date_synchronisation = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$action['id']]);
                } else {
                    // Incrémenter les tentatives
                    $stmt = $this->pdo->prepare("
                        UPDATE offline_sync_queue
                        SET tentatives = tentatives + 1, statut = 'erreur'
                        WHERE id = ?
                    ");
                    $stmt->execute([$action['id']]);
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Erreur sync_pending_actions: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Traite une action individuelle
     */
    private function process_action($action) {
        try {
            $donnees = json_decode($action['donnees_json'], true);
            
            switch ($action['action_type']) {
                case 'submission':
                    // Soumettre un devoir/quiz
                    return $this->submit_assignment($action['utilisateur_id'], $action['entite_id'], $donnees);
                
                case 'comment':
                    // Poster un commentaire
                    return $this->post_comment($action['utilisateur_id'], $action['entite_id'], $donnees);
                
                case 'mark_complete':
                    // Marquer comme terminé
                    return $this->mark_complete($action['utilisateur_id'], $action['entite_id'], $action['entite_type']);
                
                case 'message':
                    // Envoyer un message
                    return $this->send_message($action['utilisateur_id'], $donnees);
                
                default:
                    return false;
            }
        } catch (Exception $e) {
            error_log("Erreur process_action: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Soumet une assignment
     */
    private function submit_assignment($utilisateur_id, $assignment_id, $donnees) {
        // Logique de soumission
        return true;
    }
    
    /**
     * Poste un commentaire
     */
    private function post_comment($utilisateur_id, $cours_id, $donnees) {
        // Logique de commentaire
        return true;
    }
    
    /**
     * Marque un élément comme terminé
     */
    private function mark_complete($utilisateur_id, $element_id, $element_type) {
        // Logique de marquage
        return true;
    }
    
    /**
     * Envoie un message
     */
    private function send_message($utilisateur_id, $donnees) {
        // Logique d'envoi de message
        return true;
    }
    
    /**
     * Récupère le statut de synchronisation
     */
    public function get_sync_status($utilisateur_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(CASE WHEN statut = 'en_attente' THEN 1 END) as pending,
                    COUNT(CASE WHEN statut = 'erreur' THEN 1 END) as errors,
                    COUNT(CASE WHEN statut = 'synchronise' THEN 1 END) as synced
                FROM offline_sync_queue
                WHERE utilisateur_id = ?
            ");
            $stmt->execute([$utilisateur_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Erreur get_sync_status: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Nettoie les actions synchronisées anciennes
     */
    public function cleanup_old_records($days = 30) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM offline_sync_queue
                WHERE statut = 'synchronise'
                AND date_creation < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            return $stmt->execute([$days]);
        } catch (Exception $e) {
            error_log("Erreur cleanup_old_records: " . $e->getMessage());
            return false;
        }
    }
}

function get_offline_sync_service() {
    global $pdo;
    if (!isset($GLOBALS['offline_sync_service'])) {
        $GLOBALS['offline_sync_service'] = new OfflineSyncService($pdo);
    }
    return $GLOBALS['offline_sync_service'];
}
?>
