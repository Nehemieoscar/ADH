<?php
/**
 * API de test pour RoleManager
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/users_system_integration.php';

header('Content-Type: application/json');

if (!est_connecte() || !est_admin()) {
    echo json_encode(['success' => false, 'message' => '❌ Accès refusé']);
    exit;
}

try {
    $role_mgr = get_role_manager();
    $user_id = obtenir_utilisateur_connecte();
    
    // Test 1: Ajouter un rôle de test
    $result = $role_mgr->add_role(
        $user_id,
        'test_role',
        date('Y-m-d'),
        null,  // Pas de date de fin
        50     // Niveau de permission
    );
    
    if (!$result) {
        throw new Exception('Impossible d\'ajouter le rôle de test');
    }
    
    // Test 2: Récupérer les rôles actifs
    $active_roles = $role_mgr->get_active_roles($user_id);
    
    if (!is_array($active_roles)) {
        throw new Exception('Impossible de récupérer les rôles actifs');
    }
    
    // Test 3: Vérifier un rôle
    $has_test_role = $role_mgr->has_role($user_id, 'test_role');
    
    // Test 4: Récupérer l'historique
    $history = $role_mgr->get_role_history($user_id);
    
    if (!is_array($history)) {
        throw new Exception('Impossible de récupérer l\'historique des rôles');
    }
    
    echo json_encode([
        'success' => true,
        'message' => '✅ RoleManager fonctionne correctement<br>' .
                    'Rôles actifs: ' . count($active_roles) . '<br>' .
                    'Rôle de test présent: ' . ($has_test_role ? 'Oui' : 'Non') . '<br>' .
                    'Entrées d\'historique: ' . count($history)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '❌ Erreur: ' . htmlspecialchars($e->getMessage())
    ]);
}
?>
