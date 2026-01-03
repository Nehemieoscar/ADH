<?php
/**
 * Exemple d'intégration des hooks d'activité dans les fichiers d'authentification
 * 
 * Ce fichier montre exactement où et comment intégrer le système de suivi des utilisateurs
 * dans vos fichiers login.php, logout.php, etc.
 */

// ============================================================================
// INTÉGRATION DANS login.php
// ============================================================================

/**
 * AVANT (login.php existant):
 * 
 * if ($password_correct) {
 *     $_SESSION['utilisateur_id'] = $user['id'];
 *     $_SESSION['utilisateur_nom'] = $user['nom'];
 *     header('Location: index.php');
 * }
 */

/**
 * APRÈS (avec hooks du système de gestion):
 */

// À ajouter dans login.php après le code d'authentification réussi:
if ($password_correct) {
    $_SESSION['utilisateur_id'] = $user['id'];
    $_SESSION['utilisateur_nom'] = $user['nom'];
    
    // 🆕 AJOUTER CES LIGNES:
    // Charger les services du système de gestion des utilisateurs
    require_once __DIR__ . '/includes/users_system_integration.php';
    
    // Enregistrer la connexion
    $tracker = get_activity_tracker();
    $tracker->log_login($user['id']);
    
    // Mettre à jour le statut en ligne
    $tracker->update_online_status($user['id'], true, 'en ligne');
    
    // Log pour debugging (optionnel)
    error_log("Utilisateur {$user['id']} connecté à " . date('Y-m-d H:i:s'));
    
    // Redirection existante
    header('Location: index.php');
}

// ============================================================================
// INTÉGRATION DANS logout.php
// ============================================================================

/**
 * AVANT (logout.php existant):
 * 
 * session_destroy();
 * header('Location: index.php');
 */

/**
 * APRÈS (avec hooks du système de gestion):
 */

// À ajouter dans logout.php avant la destruction de session:

// Récupérer l'ID utilisateur avant de détruire la session
$user_id = $_SESSION['utilisateur_id'] ?? null;

// Charger les services du système de gestion
require_once __DIR__ . '/includes/users_system_integration.php';

if ($user_id) {
    // Calculer la durée de session
    $session_start = $_SESSION['start_time'] ?? $_SERVER['REQUEST_TIME'];
    $session_duration = $_SERVER['REQUEST_TIME'] - $session_start;
    
    // 🆕 AJOUTER CES LIGNES:
    // Enregistrer la déconnexion
    $tracker = get_activity_tracker();
    $tracker->log_logout($user_id, $session_duration);
    
    // Mettre à jour le statut hors-ligne
    $tracker->update_online_status($user_id, false, 'hors ligne');
    
    // Log pour debugging (optionnel)
    error_log("Utilisateur $user_id déconnecté après $session_duration secondes");
}

// Code existant
session_destroy();
header('Location: index.php');

// ============================================================================
// INTÉGRATION DANS config.php (INITIALISATION)
// ============================================================================

/**
 * À ajouter à la fin de config.php pour initialiser les services globalement:
 */

// === INITIALISATION DU SYSTÈME DE GESTION DES UTILISATEURS ===

// Vérifier que les fichiers de services existent
if (file_exists(__DIR__ . '/includes/users_system_integration.php')) {
    require_once __DIR__ . '/includes/users_system_integration.php';
    
    // Les services sont maintenant disponibles globalement:
    // - get_activity_tracker()
    // - get_notification_service()
    // - get_role_manager()
    // - get_behavior_analyzer()
    // - get_offline_sync_service()
} else {
    // Log un warning si le système n'est pas installé
    error_log('AVERTISSEMENT: Système de gestion des utilisateurs non trouvé');
}

// ============================================================================
// INTÉGRATION DANS les pages de cours (cours.php, etc.)
// ============================================================================

/**
 * Lorsqu'un utilisateur commence un cours:
 */

if ($user_id && $course_id) {
    $tracker = get_activity_tracker();
    $tracker->log_activity(
        $user_id,
        'cours_commence',
        "Cours débuté: $course_title",
        'cours',
        $course_id
    );
}

/**
 * Lorsqu'un utilisateur termine un cours:
 */

if ($user_id && $course_id) {
    $tracker = get_activity_tracker();
    $tracker->log_activity(
        $user_id,
        'cours_termine',
        "Cours terminé: $course_title",
        'cours',
        $course_id
    );
}

// ============================================================================
// INTÉGRATION DANS le système de quiz (quiz.php)
// ============================================================================

/**
 * Lorsqu'un quiz est soumis:
 */

if ($quiz_submitted) {
    $tracker = get_activity_tracker();
    $tracker->log_quiz_submitted(
        $user_id,
        $quiz_id,
        $final_score  // Score obtenu
    );
    
    // Envoyer une notification optionnellement
    $notification_service = get_notification_service();
    if ($final_score < 50) {
        $notification_service->send_notification(
            $user_id,
            'Quiz - Note faible',
            "Vous avez obtenu $final_score% au quiz: $quiz_title",
            'warning',
            'normale'
        );
    }
}

// ============================================================================
// INTÉGRATION DANS le forum (forum.php)
// ============================================================================

/**
 * Lorsqu'un message est posté au forum:
 */

if ($message_posted) {
    $tracker = get_activity_tracker();
    $tracker->log_activity(
        $user_id,
        'message',
        "Message posté au sujet: $topic_title",
        'forum',
        $topic_id
    );
    
    // Notifier les modérateurs si contenu signalé
    if ($flagged_for_review) {
        $notification_service = get_notification_service();
        $notification_service->send_notification(
            1,  // Admin ID (ajuster selon votre système)
            'Forum - Contenu à modérer',
            "Un message a été signalé pour révision dans: $topic_title",
            'warning',
            'haute'
        );
    }
}

// ============================================================================
// INTÉGRATION DANS les uploads de fichiers (devoirs, etc.)
// ============================================================================

/**
 * Lorsqu'un fichier est uploadé:
 */

if ($file_uploaded) {
    $tracker = get_activity_tracker();
    $tracker->log_activity(
        $user_id,
        'fichier_upload',
        "Fichier uploadé: $file_name",
        'devoir',
        $assignment_id
    );
    
    // Notifier le professeur
    $notification_service = get_notification_service();
    $notification_service->send_notification(
        $teacher_id,
        'Devoir - Nouveau fichier',
        "$student_name a soumis un devoir: $assignment_title",
        'info',
        'normale'
    );
}

// ============================================================================
// INTÉGRATION DANS formations.php
// ============================================================================

/**
 * Lorsqu'un utilisateur s'inscrit à une formation:
 */

if ($formation_enrolled) {
    $tracker = get_activity_tracker();
    $tracker->log_activity(
        $user_id,
        'formation_inscrite',
        "Inscription à la formation: $formation_title",
        'formation',
        $formation_id
    );
    
    // Envoyer une notification de bienvenue
    $notification_service = get_notification_service();
    $notification_service->send_notification(
        $user_id,
        'Bienvenue!',
        "Vous êtes maintenant inscrit à $formation_title",
        'success',
        'haute'
    );
}

/**
 * Lorsqu'un utilisateur abandonne une formation:
 */

if ($formation_dropped) {
    $tracker = get_activity_tracker();
    $tracker->log_activity(
        $user_id,
        'formation_abandonnee',
        "Abandon de la formation: $formation_title",
        'formation',
        $formation_id
    );
}

// ============================================================================
// INTÉGRATION DANS header.php (WIDGETS)
// ============================================================================

/**
 * À ajouter dans header.php pour afficher les widgets:
 */

<?php
// Vérifier que l'utilisateur est connecté
if (est_connecte()) {
    // Inclure les widgets
    include_once __DIR__ . '/includes/notifications_widget.php';
    include_once __DIR__ . '/includes/chatbot_widget.php';
    
    // Inclure la librairie de synchronisation hors-ligne
    echo '<script src="js/offline-sync.js"></script>';
    
    // Initialiser la synchronisation hors-ligne
    echo '<script>
        const offlineSync = new OfflineSyncClient();
        window.addEventListener("online", () => {
            console.log("Connexion rétablie, synchronisation...");
            offlineSync.syncPendingActions();
        });
    </script>';
}
?>

// ============================================================================
// EXEMPLE COMPLET: LOGIN.PHP INTÉGRÉ
// ============================================================================

/**
 * Voici à quoi devrait ressembler votre login.php après intégration:
 */

<?php
require_once 'config.php';

// Charger les services du système de gestion
require_once __DIR__ . '/includes/users_system_integration.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Votre logique d'authentification existante
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['mot_de_passe'])) {
        // ✅ INITIALISER LA SESSION
        $_SESSION['utilisateur_id'] = $user['id'];
        $_SESSION['utilisateur_nom'] = $user['nom'];
        $_SESSION['utilisateur_email'] = $user['email'];
        $_SESSION['utilisateur_role'] = $user['role'];
        $_SESSION['start_time'] = $_SERVER['REQUEST_TIME'];
        
        // ✅ ENREGISTRER LA CONNEXION DANS LE SYSTÈME
        try {
            $tracker = get_activity_tracker();
            $tracker->log_login($user['id']);
            $tracker->update_online_status($user['id'], true, 'en ligne');
        } catch (Exception $e) {
            error_log("Erreur lors de l'enregistrement de la connexion: " . $e->getMessage());
        }
        
        // Redirection
        header('Location: index.php');
        exit;
    } else {
        $error = "Email ou mot de passe incorrect";
    }
}
?>

// ============================================================================
// EXEMPLE COMPLET: LOGOUT.PHP INTÉGRÉ
// ============================================================================

<?php
require_once 'config.php';
require_once __DIR__ . '/includes/users_system_integration.php';

// Récupérer les infos de session avant destruction
$user_id = $_SESSION['utilisateur_id'] ?? null;
$session_start = $_SESSION['start_time'] ?? $_SERVER['REQUEST_TIME'];
$session_duration = $_SERVER['REQUEST_TIME'] - $session_start;

// Enregistrer la déconnexion
if ($user_id) {
    try {
        $tracker = get_activity_tracker();
        $tracker->log_logout($user_id, $session_duration);
        $tracker->update_online_status($user_id, false, 'hors ligne');
    } catch (Exception $e) {
        error_log("Erreur lors de l'enregistrement de la déconnexion: " . $e->getMessage());
    }
}

// Détruire la session
session_destroy();
header('Location: index.php');
exit;
?>

// ============================================================================
// CHECKLIST D'INTÉGRATION
// ============================================================================

/**
 * ☐ 1. Ajouter require_once pour users_system_integration.php dans config.php
 * ☐ 2. Intégrer log_login() et update_online_status() dans login.php
 * ☐ 3. Intégrer log_logout() et update_online_status() dans logout.php
 * ☐ 4. Ajouter log_activity() pour cours_commence/cours_termine
 * ☐ 5. Ajouter log_quiz_submitted() dans la page de soumission de quiz
 * ☐ 6. Ajouter log_activity() pour les messages de forum
 * ☐ 7. Ajouter log_activity() pour les uploads de fichiers
 * ☐ 8. Intégrer les widgets (notifications + chatbot) dans header.php
 * ☐ 9. Ajouter offline-sync.js dans le header
 * ☐ 10. Configurer les tâches cron (voir CRON_SETUP_GUIDE.md)
 * ☐ 11. Exécuter le script d'installation (install_users_system.php)
 * ☐ 12. Tester chaque action utilisateur
 */

?>
