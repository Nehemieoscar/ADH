<?php
/**
 * Service de gestion des alertes et notifications
 */

class NotificationService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Envoie une notification à un utilisateur
     */
    public function send_notification($utilisateur_id, $titre, $message, $type = 'info', $priorite = 'normale', $admin_id = null, $lien_action = null, $date_expiration = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_notifications 
                (utilisateur_id, admin_id, titre, message, type, priorite, lien_action, date_expiration, date_creation, lu)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), FALSE)
            ");
            
            return $stmt->execute([
                $utilisateur_id,
                $admin_id,
                $titre,
                $message,
                $type,
                $priorite,
                $lien_action,
                $date_expiration
            ]);
        } catch (Exception $e) {
            error_log("Erreur send_notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envoie des rappels d'inactivité
     */
    public function send_inactivity_reminder($utilisateur_id, $jours_inactif = 10) {
        $titre = "Rappel : Vous êtes inactif depuis $jours_inactif jours";
        $message = "Nous avons remarqué que vous n'avez pas accédé à vos formations depuis $jours_inactif jours. Continuez votre apprentissage !";
        
        return $this->send_notification(
            $utilisateur_id,
            $titre,
            $message,
            'warning',
            'haute',
            null,
            'dashboard/dashboard.php'
        );
    }
    
    /**
     * Envoie une alerte de retard
     */
    public function send_late_submission_alert($utilisateur_id, $entite_nom) {
        $titre = "Alerte : Délai de remise dépassé";
        $message = "La date limite de remise pour \"$entite_nom\" est dépassée. Veuillez compléter au plus tôt.";
        
        return $this->send_notification(
            $utilisateur_id,
            $titre,
            $message,
            'error',
            'urgente'
        );
    }
    
    /**
     * Envoie une alerte de progression faible
     */
    public function send_low_progress_alert($utilisateur_id, $formation_nom, $progression) {
        $titre = "Progression faible détectée";
        $message = "Votre progression dans \"$formation_nom\" est à $progression%. Accélérez votre apprentissage pour ne pas prendre de retard.";
        
        return $this->send_notification(
            $utilisateur_id,
            $titre,
            $message,
            'warning',
            'normale'
        );
    }
    
    /**
     * Récupère les notifications d'un utilisateur
     */
    public function get_notifications($utilisateur_id, $limit = 20, $non_lues_seulement = false) {
        try {
            $where = "WHERE utilisateur_id = ?";
            $params = [$utilisateur_id];
            
            if ($non_lues_seulement) {
                $where .= " AND lu = FALSE";
            }
            
            // Exclure les notifications expirées
            $where .= " AND (date_expiration IS NULL OR date_expiration > NOW())";
            
            $stmt = $this->pdo->prepare("
                SELECT * FROM user_notifications 
                $where
                ORDER BY date_creation DESC
                LIMIT ?
            ");
            
            $params[] = $limit;
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erreur get_notifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Marque une notification comme lue
     */
    public function mark_as_read($notification_id, $utilisateur_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE user_notifications 
                SET lu = TRUE, date_lecture = NOW()
                WHERE id = ? AND utilisateur_id = ?
            ");
            return $stmt->execute([$notification_id, $utilisateur_id]);
        } catch (Exception $e) {
            error_log("Erreur mark_as_read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère le nombre de notifications non lues
     */
    public function get_unread_count($utilisateur_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM user_notifications 
                WHERE utilisateur_id = ? AND lu = FALSE 
                AND (date_expiration IS NULL OR date_expiration > NOW())
            ");
            $stmt->execute([$utilisateur_id]);
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Erreur get_unread_count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Supprime une notification
     */
    public function delete_notification($notification_id, $utilisateur_id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM user_notifications WHERE id = ? AND utilisateur_id = ?");
            return $stmt->execute([$notification_id, $utilisateur_id]);
        } catch (Exception $e) {
            error_log("Erreur delete_notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envoie une notification en masse aux utilisateurs
     */
    public function send_bulk_notification($utilisateur_ids, $titre, $message, $type = 'info', $admin_id = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_notifications 
                (utilisateur_id, admin_id, titre, message, type, date_creation, lu)
                VALUES (?, ?, ?, ?, ?, NOW(), FALSE)
            ");
            
            foreach ($utilisateur_ids as $uid) {
                if (!$stmt->execute([$uid, $admin_id, $titre, $message, $type])) {
                    error_log("Erreur lors de l'envoi en masse à l'utilisateur $uid");
                    return false;
                }
            }
            return true;
        } catch (Exception $e) {
            error_log("Erreur send_bulk_notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Applique une règle d'alerte automatique
     */
    public function apply_alert_rule($rule_id, $utilisateur_ids = []) {
        try {
            // Récupérer la règle
            $stmt_rule = $this->pdo->prepare("SELECT * FROM alert_rules WHERE id = ?");
            $stmt_rule->execute([$rule_id]);
            $rule = $stmt_rule->fetch();
            
            if (!$rule) return false;
            
            $titre = $rule['titre'];
            $message = $rule['message_template'];
            $type = 'alert';
            
            if (empty($utilisateur_ids)) {
                // Appliquer à tous les utilisateurs affectés selon la règle
                $utilisateur_ids = $this->get_users_for_rule($rule);
            }
            
            return $this->send_bulk_notification($utilisateur_ids, $titre, $message, $type);
        } catch (Exception $e) {
            error_log("Erreur apply_alert_rule: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère les utilisateurs concernés par une règle d'alerte
     */
    private function get_users_for_rule($rule) {
        // Cette fonction contiendrait la logique pour identifier les utilisateurs
        // selon les conditions de la règle (inactivité, retards, etc.)
        // À adapter selon vos besoins spécifiques
        return [];
    }
}

function get_notification_service() {
    global $pdo;
    if (!isset($GLOBALS['notification_service'])) {
        $GLOBALS['notification_service'] = new NotificationService($pdo);
    }
    return $GLOBALS['notification_service'];
}
?>
