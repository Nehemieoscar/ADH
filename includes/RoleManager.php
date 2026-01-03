<?php
/**
 * Service de gestion des rôles modulables
 * Permet à un utilisateur d'avoir plusieurs rôles
 */

class RoleManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Ajoute un rôle à un utilisateur
     */
    public function add_role($utilisateur_id, $role, $date_debut = null, $date_fin = null, $permission_level = 1) {
        try {
            $date_debut = $date_debut ?? date('Y-m-d');
            
            $stmt = $this->pdo->prepare("
                INSERT INTO user_roles 
                (utilisateur_id, role, statut, date_debut, date_fin, permission_level)
                VALUES (?, ?, 'actif', ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    statut = 'actif',
                    date_fin = VALUES(date_fin),
                    permission_level = VALUES(permission_level)
            ");
            
            return $stmt->execute([$utilisateur_id, $role, $date_debut, $date_fin, $permission_level]);
        } catch (Exception $e) {
            error_log("Erreur add_role: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Retire un rôle à un utilisateur
     */
    public function remove_role($utilisateur_id, $role) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE user_roles 
                SET statut = 'inactif', date_fin = CURDATE()
                WHERE utilisateur_id = ? AND role = ?
            ");
            return $stmt->execute([$utilisateur_id, $role]);
        } catch (Exception $e) {
            error_log("Erreur remove_role: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère tous les rôles actifs d'un utilisateur
     */
    public function get_active_roles($utilisateur_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT role, permission_level, date_debut, date_fin 
                FROM user_roles 
                WHERE utilisateur_id = ? 
                AND statut = 'actif'
                AND (date_fin IS NULL OR date_fin >= CURDATE())
                ORDER BY permission_level DESC
            ");
            $stmt->execute([$utilisateur_id]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            error_log("Erreur get_active_roles: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère le rôle principal (celui avec le plus de permissions)
     */
    public function get_primary_role($utilisateur_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT role FROM user_roles 
                WHERE utilisateur_id = ? 
                AND statut = 'actif'
                AND (date_fin IS NULL OR date_fin >= CURDATE())
                ORDER BY permission_level DESC
                LIMIT 1
            ");
            $stmt->execute([$utilisateur_id]);
            $result = $stmt->fetch();
            return $result['role'] ?? null;
        } catch (Exception $e) {
            error_log("Erreur get_primary_role: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupère l'historique des rôles d'un utilisateur
     */
    public function get_role_history($utilisateur_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM role_history 
                WHERE utilisateur_id = ?
                ORDER BY date_changement DESC
            ");
            $stmt->execute([$utilisateur_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erreur get_role_history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Enregistre un changement de rôle dans l'historique
     */
    public function log_role_change($utilisateur_id, $ancien_role, $nouveau_role, $raison = null, $modifie_par = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO role_history 
                (utilisateur_id, ancien_role, nouveau_role, raison, modifie_par)
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$utilisateur_id, $ancien_role, $nouveau_role, $raison, $modifie_par]);
        } catch (Exception $e) {
            error_log("Erreur log_role_change: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérife si un utilisateur a un rôle spécifique
     */
    public function has_role($utilisateur_id, $role) {
        $active_roles = $this->get_active_roles($utilisateur_id);
        return in_array($role, $active_roles);
    }
    
    /**
     * Vérife si un utilisateur a au moins l'un des rôles
     */
    public function has_any_role($utilisateur_id, $roles) {
        $active_roles = $this->get_active_roles($utilisateur_id);
        foreach ($roles as $role) {
            if (in_array($role, $active_roles)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Récupère le niveau de permission d'un utilisateur pour un rôle
     */
    public function get_permission_level($utilisateur_id, $role) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT permission_level FROM user_roles 
                WHERE utilisateur_id = ? AND role = ? AND statut = 'actif'
                AND (date_fin IS NULL OR date_fin >= CURDATE())
            ");
            $stmt->execute([$utilisateur_id, $role]);
            $result = $stmt->fetch();
            return $result['permission_level'] ?? 0;
        } catch (Exception $e) {
            error_log("Erreur get_permission_level: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Change le statut d'un rôle
     */
    public function change_role_status($utilisateur_id, $role, $nouveau_statut) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE user_roles 
                SET statut = ?
                WHERE utilisateur_id = ? AND role = ?
            ");
            return $stmt->execute([$nouveau_statut, $utilisateur_id, $role]);
        } catch (Exception $e) {
            error_log("Erreur change_role_status: " . $e->getMessage());
            return false;
        }
    }
}

function get_role_manager() {
    global $pdo;
    if (!isset($GLOBALS['role_manager'])) {
        $GLOBALS['role_manager'] = new RoleManager($pdo);
    }
    return $GLOBALS['role_manager'];
}
?>
