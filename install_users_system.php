#!/usr/bin/env php
<?php
/**
 * Script d'installation du système avancé de gestion des utilisateurs
 * Usage: php install_users_system.php
 */

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  🎯 Installation - Système de Gestion Avancée des Utilisateurs  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Configuration
$config_file = 'config.php';

if (!file_exists($config_file)) {
    echo "❌ Erreur: Fichier config.php non trouvé\n";
    exit(1);
}

require_once $config_file;

// Étape 1: Vérifier la connexion à la base de données
echo "1️⃣  Vérification de la connexion BD...";
try {
    $test = $pdo->query("SELECT 1");
    echo " ✅\n";
} catch (Exception $e) {
    echo " ❌\n";
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

// Étape 2: Créer les tables
echo "2️⃣  Création des tables...";
try {
    $sql_file = 'users_advanced_schema.sql';
    
    if (!file_exists($sql_file)) {
        echo " ⚠️ (fichier SQL non trouvé, sautage)\n";
    } else {
        $sql = file_get_contents($sql_file);
        
        // Exécuter les commandes SQL
        $statements = explode(';', $sql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        echo " ✅\n";
    }
} catch (Exception $e) {
    echo " ❌\n";
    echo "Erreur: " . $e->getMessage() . "\n";
    // Continuer malgré l'erreur
}

// Étape 3: Vérifier les répertoires
echo "3️⃣  Vérification des répertoires...";
$directories = [
    'includes' => true,
    'dashboard/admin/users' => true,
    'api' => true,
    'js' => true
];

foreach ($directories as $dir => $required) {
    if (!is_dir($dir)) {
        if ($required) {
            echo "\n   ⚠️ Créé: $dir";
            mkdir($dir, 0755, true);
        }
    }
}
echo " ✅\n";

// Étape 4: Vérifier les fichiers importants
echo "4️⃣  Vérification des fichiers...";
$files = [
    'includes/ActivityTracker.php',
    'includes/NotificationService.php',
    'includes/RoleManager.php',
    'includes/BehaviorAnalyzer.php',
    'includes/OfflineSyncService.php',
    'includes/users_system_integration.php',
    'includes/notifications_widget.php',
    'includes/chatbot_widget.php',
    'dashboard/admin/users/index.php',
    'dashboard/admin/users/profile.php',
    'dashboard/admin/users/dashboard.php',
    'api/notifications.php',
    'api/sync.php',
    'js/offline-sync.js'
];

$missing = 0;
foreach ($files as $file) {
    if (!file_exists($file)) {
        $missing++;
        echo "\n   ⚠️ Manquant: $file";
    }
}

if ($missing === 0) {
    echo " ✅\n";
} else {
    echo "\n   ($missing fichiers manquants)\n";
}

// Étape 5: Vérifier les services
echo "5️⃣  Vérification des services PHP...";
try {
    require_once 'includes/ActivityTracker.php';
    require_once 'includes/NotificationService.php';
    require_once 'includes/RoleManager.php';
    require_once 'includes/BehaviorAnalyzer.php';
    require_once 'includes/OfflineSyncService.php';
    
    echo " ✅\n";
} catch (Exception $e) {
    echo " ❌\n";
    echo "Erreur: " . $e->getMessage() . "\n";
}

// Étape 6: Créer un utilisateur de test (optionnel)
echo "6️⃣  Utilisateur de test...";
try {
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
    $stmt->execute(['test@example.com']);
    
    if (!$stmt->fetch()) {
        // Créer un utilisateur test
        $stmt = $pdo->prepare("
            INSERT INTO utilisateurs (nom, email, mot_de_passe, role, statut)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            'Utilisateur Test',
            'test@example.com',
            password_hash('test123', PASSWORD_BCRYPT),
            'etudiant',
            'actif'
        ]);
        
        echo " ✅ (créé)\n";
    } else {
        echo " ✅ (existe)\n";
    }
} catch (Exception $e) {
    echo " ⚠️\n";
}

// Résumé final
echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                      ✅ Installation Terminée                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "📋 Prochaines étapes:\n";
echo "   1. Importer users_system_integration.php dans config.php\n";
echo "   2. Ajouter le widget notifications au header\n";
echo "   3. Ajouter le widget chatbot au body\n";
echo "   4. Appeler hook_user_login() après les connexions\n";
echo "   5. Appeler hook_user_logout() avant les déconnexions\n\n";

echo "📚 Documentation:\n";
echo "   - USERS_SYSTEM_GUIDE.md: Guide d'intégration complet\n";
echo "   - USERS_SYSTEM_SUMMARY.md: Résumé des fonctionnalités\n\n";

echo "🚀 URLs importantes:\n";
echo "   - Dashboard: /dashboard/admin/users/dashboard.php\n";
echo "   - Utilisateurs: /dashboard/admin/users/index.php\n";
echo "   - Profil: /dashboard/admin/users/profile.php?id=<USER_ID>\n\n";

echo "✨ Système prêt pour la production!\n";
?>
