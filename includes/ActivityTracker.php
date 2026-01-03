<?php
/**
 * Service de tracking des activités utilisateur
 * Enregistre toutes les actions des utilisateurs dans la base de données
 */

class ActivityTracker {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Enregistre une activité utilisateur
     */
    public function log_activity($utilisateur_id, $type_activite, $description = null, $entite_type = null, $entite_id = null) {
        try {
            $adresse_ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt = $this->pdo->prepare("
                INSERT INTO user_activity 
                (utilisateur_id, type_activite, description, entite_type, entite_id, adresse_ip, user_agent, date_activite)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            return $stmt->execute([
                $utilisateur_id,
                $type_activite,
                $description,
                $entite_type,
                $entite_id,
                $adresse_ip,
                $user_agent
            ]);
        } catch (Exception $e) {
            error_log("Erreur ActivityTracker: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enregistre une connexion utilisateur
     */
    public function log_login($utilisateur_id) {
        return $this->log_activity($utilisateur_id, 'connexion', 'Connexion à la plateforme');
    }
    
    /**
     * Enregistre une déconnexion utilisateur
     */
    public function log_logout($utilisateur_id, $durée_session = null) {
        try {
            $adresse_ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt = $this->pdo->prepare("
                INSERT INTO user_activity 
                (utilisateur_id, type_activite, description, durée_session, adresse_ip, user_agent, date_activite)
                VALUES (?, 'deconnexion', 'Déconnexion de la plateforme', ?, ?, ?, NOW())
            ");
            
            return $stmt->execute([
                $utilisateur_id,
                $durée_session,
                $adresse_ip,
                $user_agent
            ]);
        } catch (Exception $e) {
            error_log("Erreur ActivityTracker logout: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enregistre une participation au forum
     */
    public function log_forum_message($utilisateur_id, $sujet_id) {
        return $this->log_activity($utilisateur_id, 'participation_forum', 'Message posté au forum', 'forum_sujet', $sujet_id);
    }
    
    /**
     * Enregistre la soumission d'un quiz
     */
    public function log_quiz_submitted($utilisateur_id, $quiz_id, $score) {
        return $this->log_activity($utilisateur_id, 'quiz_soumis', "Quiz soumis avec un score de $score%", 'quiz', $quiz_id);
    }
    
    /**
     * Enregistre le début d'un cours
     */
    public function log_course_started($utilisateur_id, $cours_id) {
        return $this->log_activity($utilisateur_id, 'cours_commence', 'Cours commencé', 'cours', $cours_id);
    }
    
    /**
     * Enregistre la fin d'un cours
     */
    public function log_course_completed($utilisateur_id, $cours_id) {
        return $this->log_activity($utilisateur_id, 'cours_termine', 'Cours terminé', 'cours', $cours_id);
    }
    
    /**
     * Met à jour le statut en ligne d'un utilisateur
     */
    public function update_online_status($utilisateur_id, $est_connecte = true, $statut_personnel = null) {
        try {
            // Vérifier si l'enregistrement existe
            $stmt_check = $this->pdo->prepare("SELECT id FROM user_online_status WHERE utilisateur_id = ?");
            $stmt_check->execute([$utilisateur_id]);
            
            if ($stmt_check->fetch()) {
                // Mise à jour
                $stmt = $this->pdo->prepare("
                    UPDATE user_online_status 
                    SET est_connecte = ?, 
                        derniere_activite = NOW(),
                        statut_personnel = ?
                    WHERE utilisateur_id = ?
                ");
                return $stmt->execute([$est_connecte, $statut_personnel ?? 'en ligne', $utilisateur_id]);
            } else {
                // Insertion
                $stmt = $this->pdo->prepare("
                    INSERT INTO user_online_status 
                    (utilisateur_id, est_connecte, derniere_activite, statut_personnel)
                    VALUES (?, ?, NOW(), ?)
                ");
                return $stmt->execute([$utilisateur_id, $est_connecte, $statut_personnel ?? 'en ligne']);
            }
        } catch (Exception $e) {
            error_log("Erreur update_online_status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère l'historique d'activité d'un utilisateur
     */
    public function get_activity_history($utilisateur_id, $limit = 50, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM user_activity 
                WHERE utilisateur_id = ?
                ORDER BY date_activite DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$utilisateur_id, $limit, $offset]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erreur get_activity_history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les statistiques d'activité
     */
    public function get_activity_stats($utilisateur_id, $date_debut = null, $date_fin = null) {
        try {
            $where = "WHERE utilisateur_id = ?";
            $params = [$utilisateur_id];
            
            if ($date_debut) {
                $where .= " AND date_activite >= ?";
                $params[] = $date_debut;
            }
            if ($date_fin) {
                $where .= " AND date_activite <= ?";
                $params[] = $date_fin;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    type_activite,
                    COUNT(*) as nombre,
                    MAX(date_activite) as derniere_date
                FROM user_activity 
                $where
                GROUP BY type_activite
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur get_activity_stats: " . $e->getMessage());
            return [];
        }
    }
}

// Initialisation globale
function get_activity_tracker() {
    global $pdo;
    if (!isset($GLOBALS['activity_tracker'])) {
        $GLOBALS['activity_tracker'] = new ActivityTracker($pdo);
    }
    return $GLOBALS['activity_tracker'];
}
?>
