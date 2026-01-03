<?php
/**
 * Script de test du système de gestion des utilisateurs
 * Accédez à: http://localhost/ADH/test_users_system.php
 * 
 * ⚠️ IMPORTANT: Supprimez ce fichier en production!
 */

// Vérifier que l'utilisateur est admin
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/users_system_integration.php';

if (!est_connecte() || !est_admin()) {
    die('❌ Accès refusé. Vous devez être administrateur.');
}

// Récupérer l'utilisateur admin connecté
$user_id = obtenir_utilisateur_connecte();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Système de Gestion des Utilisateurs</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 0.95em;
        }
        
        .content {
            padding: 30px;
        }
        
        .section {
            margin-bottom: 40px;
        }
        
        .section h2 {
            color: #333;
            font-size: 1.5em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .test-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .test-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: #f9f9f9;
            transition: all 0.3s ease;
        }
        
        .test-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .test-card h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        
        .test-card p {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9em;
            transition: all 0.3s ease;
        }
        
        .button:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .button.success {
            background: #27ae60;
        }
        
        .button.success:hover {
            background: #229954;
        }
        
        .button.danger {
            background: #e74c3c;
        }
        
        .button.danger:hover {
            background: #c0392b;
        }
        
        .result {
            margin-top: 15px;
            padding: 15px;
            border-radius: 5px;
            font-size: 0.9em;
            display: none;
        }
        
        .result.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }
        
        .result.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }
        
        .result.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            display: block;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .status-badge.ok {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-badge.warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-badge.info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 0.9em;
        }
        
        .table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        .table td {
            border-bottom: 1px solid #ddd;
            padding: 12px;
        }
        
        .table tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            color: #856404;
        }
        
        .footer {
            background: #f9f9f9;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Test - Système de Gestion des Utilisateurs</h1>
            <p>Vérifiez que tous les composants du système fonctionnent correctement</p>
        </div>
        
        <div class="content">
            <div class="warning">
                ⚠️ <strong>ATTENTION:</strong> Cette page est destinée au test en développement. 
                <strong>Supprimez-la en production!</strong> 
                (<code>rm test_users_system.php</code>)
            </div>
            
            <!-- SECTION 1: VÉRIFICATION DES FICHIERS -->
            <div class="section">
                <h2>📁 Vérification des Fichiers</h2>
                <div class="test-group">
                    <?php
                    $files_to_check = [
                        'includes/ActivityTracker.php' => 'Service de suivi des activités',
                        'includes/NotificationService.php' => 'Service de notifications',
                        'includes/RoleManager.php' => 'Gestionnaire de rôles',
                        'includes/BehaviorAnalyzer.php' => 'Analyseur de comportement',
                        'includes/OfflineSyncService.php' => 'Service de synchronisation hors-ligne',
                        'includes/users_system_integration.php' => 'Intégration du système',
                        'includes/notifications_widget.php' => 'Widget de notifications',
                        'includes/chatbot_widget.php' => 'Widget chatbot',
                        'js/offline-sync.js' => 'Client de synchronisation',
                        'dashboard/admin/users/dashboard.php' => 'Tableau de bord admin',
                        'dashboard/admin/users/index.php' => 'Liste des utilisateurs',
                        'dashboard/admin/users/profile.php' => 'Profil utilisateur',
                        'api/notifications.php' => 'API notifications',
                        'api/sync.php' => 'API synchronisation',
                    ];
                    
                    foreach ($files_to_check as $file => $description) {
                        $exists = file_exists(__DIR__ . '/' . $file);
                        $status = $exists ? '✅ OK' : '❌ MANQUANT';
                        $class = $exists ? 'ok' : 'error';
                        ?>
                        <div class="test-card">
                            <h3><?php echo htmlspecialchars($description); ?></h3>
                            <p><code><?php echo htmlspecialchars($file); ?></code></p>
                            <span class="status-badge <?php echo $class; ?>"><?php echo $status; ?></span>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            
            <!-- SECTION 2: VÉRIFICATION DES SERVICES -->
            <div class="section">
                <h2>🔧 Vérification des Services</h2>
                <div class="test-group">
                    <?php
                    // Test ActivityTracker
                    try {
                        $tracker = get_activity_tracker();
                        $is_tracker_ok = $tracker !== null;
                    } catch (Exception $e) {
                        $is_tracker_ok = false;
                    }
                    ?>
                    <div class="test-card">
                        <h3>ActivityTracker</h3>
                        <p>Service de suivi des activités utilisateur</p>
                        <span class="status-badge <?php echo $is_tracker_ok ? 'ok' : 'error'; ?>">
                            <?php echo $is_tracker_ok ? '✅ Fonctionnel' : '❌ Erreur'; ?>
                        </span>
                        <?php if ($is_tracker_ok): ?>
                        <button class="button" onclick="testActivityTracker()">Tester</button>
                        <div id="tracker-result" class="result"></div>
                        <?php endif; ?>
                    </div>
                    
                    <?php
                    // Test NotificationService
                    try {
                        $notif = get_notification_service();
                        $is_notif_ok = $notif !== null;
                    } catch (Exception $e) {
                        $is_notif_ok = false;
                    }
                    ?>
                    <div class="test-card">
                        <h3>NotificationService</h3>
                        <p>Service de gestion des notifications</p>
                        <span class="status-badge <?php echo $is_notif_ok ? 'ok' : 'error'; ?>">
                            <?php echo $is_notif_ok ? '✅ Fonctionnel' : '❌ Erreur'; ?>
                        </span>
                        <?php if ($is_notif_ok): ?>
                        <button class="button" onclick="testNotificationService()">Tester</button>
                        <div id="notif-result" class="result"></div>
                        <?php endif; ?>
                    </div>
                    
                    <?php
                    // Test RoleManager
                    try {
                        $role_mgr = get_role_manager();
                        $is_role_ok = $role_mgr !== null;
                    } catch (Exception $e) {
                        $is_role_ok = false;
                    }
                    ?>
                    <div class="test-card">
                        <h3>RoleManager</h3>
                        <p>Gestionnaire de rôles utilisateur</p>
                        <span class="status-badge <?php echo $is_role_ok ? 'ok' : 'error'; ?>">
                            <?php echo $is_role_ok ? '✅ Fonctionnel' : '❌ Erreur'; ?>
                        </span>
                        <?php if ($is_role_ok): ?>
                        <button class="button" onclick="testRoleManager()">Tester</button>
                        <div id="role-result" class="result"></div>
                        <?php endif; ?>
                    </div>
                    
                    <?php
                    // Test BehaviorAnalyzer
                    try {
                        $analyzer = get_behavior_analyzer();
                        $is_analyzer_ok = $analyzer !== null;
                    } catch (Exception $e) {
                        $is_analyzer_ok = false;
                    }
                    ?>
                    <div class="test-card">
                        <h3>BehaviorAnalyzer</h3>
                        <p>Analyse de comportement IA</p>
                        <span class="status-badge <?php echo $is_analyzer_ok ? 'ok' : 'error'; ?>">
                            <?php echo $is_analyzer_ok ? '✅ Fonctionnel' : '❌ Erreur'; ?>
                        </span>
                        <?php if ($is_analyzer_ok): ?>
                        <button class="button" onclick="testBehaviorAnalyzer()">Tester</button>
                        <div id="analyzer-result" class="result"></div>
                        <?php endif; ?>
                    </div>
                    
                    <?php
                    // Test OfflineSyncService
                    try {
                        $sync = get_offline_sync_service();
                        $is_sync_ok = $sync !== null;
                    } catch (Exception $e) {
                        $is_sync_ok = false;
                    }
                    ?>
                    <div class="test-card">
                        <h3>OfflineSyncService</h3>
                        <p>Service de synchronisation hors-ligne</p>
                        <span class="status-badge <?php echo $is_sync_ok ? 'ok' : 'error'; ?>">
                            <?php echo $is_sync_ok ? '✅ Fonctionnel' : '❌ Erreur'; ?>
                        </span>
                        <?php if ($is_sync_ok): ?>
                        <button class="button" onclick="testOfflineSync()">Tester</button>
                        <div id="sync-result" class="result"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- SECTION 3: STATISTIQUES DE LA BASE DE DONNÉES -->
            <div class="section">
                <h2>📊 Statistiques de la Base de Données</h2>
                <?php
                try {
                    // Compter les enregistrements
                    $tables = [
                        'user_activity' => 'Activités',
                        'user_notifications' => 'Notifications',
                        'user_roles' => 'Rôles',
                        'user_profiles' => 'Profils',
                        'user_behavior_analysis' => 'Analyses comportement',
                        'offline_sync_queue' => 'Queue de synch',
                        'participation_stats' => 'Statistiques participation',
                    ];
                    
                    $stats = [];
                    foreach ($tables as $table => $label) {
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                            $count = $stmt->fetchColumn();
                            $stats[$label] = ['count' => $count, 'ok' => true];
                        } catch (Exception $e) {
                            $stats[$label] = ['count' => 0, 'ok' => false, 'error' => $e->getMessage()];
                        }
                    }
                    ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Table</th>
                                <th>Enregistrements</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats as $label => $data): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($label); ?></td>
                                <td><?php echo number_format($data['count']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $data['ok'] ? 'ok' : 'error'; ?>">
                                        <?php echo $data['ok'] ? '✅ OK' : '❌ Erreur'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php } catch (Exception $e): ?>
                    <div class="result error">
                        ❌ Erreur lors de la récupération des statistiques: <?php echo htmlspecialchars($e->getMessage()); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- SECTION 4: LIENS UTILES -->
            <div class="section">
                <h2>🔗 Liens Utiles</h2>
                <div class="test-group">
                    <div class="test-card">
                        <h3>📋 Guide d'Intégration</h3>
                        <p>Instructions détaillées pour intégrer le système dans vos fichiers</p>
                        <a href="AUTH_INTEGRATION_GUIDE.php" class="button">Consulter</a>
                    </div>
                    
                    <div class="test-card">
                        <h3>⏰ Configuration Cron</h3>
                        <p>Guide pour configurer les tâches programmées</p>
                        <a href="CRON_SETUP_GUIDE.md" class="button">Consulter</a>
                    </div>
                    
                    <div class="test-card">
                        <h3>👥 Tableau de Bord Admin</h3>
                        <p>Vue d'ensemble du système de gestion des utilisateurs</p>
                        <a href="dashboard/admin/users/dashboard.php" class="button">Accéder</a>
                    </div>
                    
                    <div class="test-card">
                        <h3>📚 Liste des Utilisateurs</h3>
                        <p>Gestion complète des utilisateurs avec filtres avancés</p>
                        <a href="dashboard/admin/users/index.php" class="button">Accéder</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>Système de Gestion des Utilisateurs - Test v1.0</p>
            <p>⚠️ Cette page doit être supprimée en production pour des raisons de sécurité</p>
        </div>
    </div>
    
    <script>
        function testActivityTracker() {
            fetch('api/test_tracker.php')
                .then(r => r.json())
                .then(data => {
                    const result = document.getElementById('tracker-result');
                    result.className = data.success ? 'result success' : 'result error';
                    result.innerHTML = data.message;
                })
                .catch(e => {
                    const result = document.getElementById('tracker-result');
                    result.className = 'result error';
                    result.innerHTML = '❌ Erreur: ' + e.message;
                });
        }
        
        function testNotificationService() {
            fetch('api/test_notifications.php')
                .then(r => r.json())
                .then(data => {
                    const result = document.getElementById('notif-result');
                    result.className = data.success ? 'result success' : 'result error';
                    result.innerHTML = data.message;
                })
                .catch(e => {
                    const result = document.getElementById('notif-result');
                    result.className = 'result error';
                    result.innerHTML = '❌ Erreur: ' + e.message;
                });
        }
        
        function testRoleManager() {
            fetch('api/test_roles.php')
                .then(r => r.json())
                .then(data => {
                    const result = document.getElementById('role-result');
                    result.className = data.success ? 'result success' : 'result error';
                    result.innerHTML = data.message;
                })
                .catch(e => {
                    const result = document.getElementById('role-result');
                    result.className = 'result error';
                    result.innerHTML = '❌ Erreur: ' + e.message;
                });
        }
        
        function testBehaviorAnalyzer() {
            fetch('api/test_behavior.php')
                .then(r => r.json())
                .then(data => {
                    const result = document.getElementById('analyzer-result');
                    result.className = data.success ? 'result success' : 'result error';
                    result.innerHTML = data.message;
                })
                .catch(e => {
                    const result = document.getElementById('analyzer-result');
                    result.className = 'result error';
                    result.innerHTML = '❌ Erreur: ' + e.message;
                });
        }
        
        function testOfflineSync() {
            fetch('api/test_sync.php')
                .then(r => r.json())
                .then(data => {
                    const result = document.getElementById('sync-result');
                    result.className = data.success ? 'result success' : 'result error';
                    result.innerHTML = data.message;
                })
                .catch(e => {
                    const result = document.getElementById('sync-result');
                    result.className = 'result error';
                    result.innerHTML = '❌ Erreur: ' + e.message;
                });
        }
    </script>
</body>
</html>
