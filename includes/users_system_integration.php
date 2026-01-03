<?php
/**
 * Configuration d'intégration du système avancé de gestion des utilisateurs
 * Incluez ce fichier dans votre config.php principal
 */

// ============================================================================
// SERVICES À CHARGER AUTOMATIQUEMENT
// ============================================================================

// Charger tous les services
require_once 'includes/ActivityTracker.php';
require_once 'includes/NotificationService.php';
require_once 'includes/RoleManager.php';
require_once 'includes/BehaviorAnalyzer.php';
require_once 'includes/OfflineSyncService.php';

// ============================================================================
// INITIALISATION DES SERVICES
// ============================================================================

// Initialiser les services globalement
$activity_tracker = get_activity_tracker();
$notification_service = get_notification_service();
$role_manager = get_role_manager();
$behavior_analyzer = get_behavior_analyzer();
$offline_sync = get_offline_sync_service();

// ============================================================================
// HOOKS D'INTÉGRATION AUTOMATIQUE
// ============================================================================

/**
 * Hook de connexion - À appeler après une connexion réussie
 */
function hook_user_login($utilisateur_id) {
    global $activity_tracker;
    
    // Enregistrer la connexion
    $activity_tracker->log_login($utilisateur_id);
    
    // Mettre à jour le statut en ligne
    $activity_tracker->update_online_status($utilisateur_id, true, 'en ligne');
}

/**
 * Hook de déconnexion - À appeler avant une déconnexion
 */
function hook_user_logout($utilisateur_id, $duree_session = null) {
    global $activity_tracker;
    
    // Enregistrer la déconnexion
    $activity_tracker->log_logout($utilisateur_id, $duree_session);
    
    // Mettre à jour le statut en ligne
    $activity_tracker->update_online_status($utilisateur_id, false, 'hors ligne');
}

/**
 * Hook d'activité générique
 */
function hook_user_activity($utilisateur_id, $type_activite, $description = null, $entite_type = null, $entite_id = null) {
    global $activity_tracker;
    
    return $activity_tracker->log_activity($utilisateur_id, $type_activite, $description, $entite_type, $entite_id);
}

/**
 * Hook pour envoyer une notification
 */
function hook_send_notification($utilisateur_id, $titre, $message, $type = 'info', $priorite = 'normale', $admin_id = null) {
    global $notification_service;
    
    return $notification_service->send_notification($utilisateur_id, $titre, $message, $type, $priorite, $admin_id);
}

/**
 * Hook pour mettre à jour le statut en ligne
 */
function hook_update_online_status($utilisateur_id, $est_connecte = true, $statut = 'en ligne') {
    global $activity_tracker;
    
    return $activity_tracker->update_online_status($utilisateur_id, $est_connecte, $statut);
}

/**
 * Hook pour envoyer une alerte d'inactivité
 */
function hook_inactivity_check($utilisateur_id, $jours = 10) {
    global $notification_service;
    
    return $notification_service->send_inactivity_reminder($utilisateur_id, $jours);
}

/**
 * Hook pour analyser le comportement
 */
function hook_analyze_behavior($utilisateur_id, $days = 30) {
    global $behavior_analyzer;
    
    return $behavior_analyzer->analyze_user_behavior($utilisateur_id, $days);
}

// ============================================================================
// VÉRIFICATIONS DE SÉCURITÉ AVANCÉES
// ============================================================================

/**
 * Vérifie si un utilisateur a un rôle spécifique
 */
function a_role($utilisateur_id, $role) {
    global $role_manager;
    return $role_manager->has_role($utilisateur_id, $role);
}

/**
 * Vérifie si un utilisateur a au moins un des rôles
 */
function a_any_role($utilisateur_id, $roles) {
    global $role_manager;
    return $role_manager->has_any_role($utilisateur_id, $roles);
}

/**
 * Récupère le rôle principal d'un utilisateur
 */
function get_primary_role($utilisateur_id) {
    global $role_manager;
    return $role_manager->get_primary_role($utilisateur_id);
}

/**
 * Récupère tous les rôles actifs
 */
function get_user_roles($utilisateur_id) {
    global $role_manager;
    return $role_manager->get_active_roles($utilisateur_id);
}

// ============================================================================
// HELPERS POUR LES TEMPLATES
// ============================================================================

/**
 * Affiche le statut en ligne avec une icône
 */
function display_online_status($statut_temps_reel) {
    $icons = [
        'connecte' => '🟢',
        'inactif' => '🟡',
        'en_session' => '🔵',
        'indisponible' => '🔴',
        'hors_ligne' => '⚪'
    ];
    
    $icon = $icons[$statut_temps_reel] ?? '⚪';
    $label = ucfirst(str_replace('_', ' ', $statut_temps_reel));
    
    return "$icon $label";
}

/**
 * Affiche les rôles d'un utilisateur
 */
function display_user_roles($utilisateur_id) {
    global $role_manager;
    $roles = $role_manager->get_active_roles($utilisateur_id);
    
    $html = '';
    foreach ($roles as $role) {
        $html .= '<span class="role-badge">' . ucfirst($role) . '</span>';
    }
    
    return $html;
}

// ============================================================================
// TÂCHES PROGRAMMÉES (CRON)
// ============================================================================

/**
 * À exécuter chaque nuit via un cron
 * Pour configurer : 0 2 * * * php /path/to/cron_jobs.php
 */
function run_nightly_tasks() {
    global $pdo, $behavior_analyzer, $offline_sync;
    
    // Analyser le comportement de tous les utilisateurs
    $stmt = $pdo->query("SELECT id FROM utilisateurs WHERE statut = 'actif'");
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        $behavior_analyzer->analyze_user_behavior($user['id'], 30);
    }
    
    // Nettoyer les actions synchronisées anciennes
    $offline_sync->cleanup_old_records(30);
    
    // Vérifier les inactivités
    check_inactivities();
    
    error_log("Tâches nocturnes exécutées avec succès");
}

/**
 * Vérifie les utilisateurs inactifs et envoie des rappels
 */
function check_inactivities() {
    global $pdo, $notification_service;
    
    // Récupérer les utilisateurs inactifs depuis 10 jours
    $stmt = $pdo->query("
        SELECT u.id, u.nom
        FROM utilisateurs u
        WHERE u.statut = 'actif'
        AND u.derniere_connexion < DATE_SUB(NOW(), INTERVAL 10 DAY)
    ");
    
    $inactive_users = $stmt->fetchAll();
    
    foreach ($inactive_users as $user) {
        $notification_service->send_inactivity_reminder($user['id'], 10);
    }
}

// ============================================================================
// MIDDLEWARES
// ============================================================================

/**
 * Middleware pour vérifier les rôles avancés
 */
function require_role($role) {
    global $role_manager;
    $utilisateur_id = $_SESSION['utilisateur_id'] ?? 0;
    
    if (!$role_manager->has_role($utilisateur_id, $role)) {
        http_response_code(403);
        die('Accès refusé. Vous n\'avez pas les permissions nécessaires.');
    }
}

/**
 * Middleware pour vérifier plusieurs rôles
 */
function require_any_role($roles) {
    global $role_manager;
    $utilisateur_id = $_SESSION['utilisateur_id'] ?? 0;
    
    if (!$role_manager->has_any_role($utilisateur_id, $roles)) {
        http_response_code(403);
        die('Accès refusé. Vous n\'avez pas les permissions nécessaires.');
    }
}

// ============================================================================
// GESTION DES ERREURS
// ============================================================================

/**
 * Enregistre une erreur comme activité
 */
function log_error_activity($utilisateur_id, $error_message) {
    global $activity_tracker;
    
    return $activity_tracker->log_activity(
        $utilisateur_id,
        'erreur_systeme',
        "Erreur détectée: $error_message",
        'systeme',
        null
    );
}

?>
