<?php
/**
 * INDEX PRINCIPAL - Système Avancé de Gestion des Utilisateurs
 * Page d'accueil et centre d'accès à tous les composants
 */

require_once __DIR__ . '/config.php';

// Vérifier l'authentification
$is_logged_in = est_connecte();
$is_admin = $is_logged_in && est_admin();
$current_user = $is_logged_in ? obtenir_utilisateur_connecte() : null;

// Déterminer le statut du système
$status = 'checking';
$status_message = 'Vérification du système...';

try {
    require_once __DIR__ . '/includes/users_system_integration.php';
    
    // Vérifier les services
    $tracker = get_activity_tracker();
    $notif = get_notification_service();
    $role_mgr = get_role_manager();
    $analyzer = get_behavior_analyzer();
    $sync = get_offline_sync_service();
    
    // Vérifier la base de données
    $stmt = $pdo->query("SELECT COUNT(*) FROM user_activity");
    $stmt->fetchColumn();
    
    $status = 'ok';
    $status_message = '✅ Système opérationnel';
} catch (Exception $e) {
    $status = 'error';
    $status_message = '❌ Erreur: ' . substr($e->getMessage(), 0, 50);
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Système de Gestion des Utilisateurs - Centre d'accès</title>
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
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 10px 10px 0 0;
            padding: 40px 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .header h1 {
            color: #333;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 1.1em;
        }
        
        .status-banner {
            background: white;
            border-top: 4px solid #667eea;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.95em;
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
        
        .user-info {
            background: white;
            padding: 15px 30px;
            text-align: right;
            border-bottom: 1px solid #eee;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .user-info a {
            color: #667eea;
            text-decoration: none;
            margin-left: 20px;
            font-weight: 500;
        }
        
        .user-info a:hover {
            text-decoration: underline;
        }
        
        .content {
            background: white;
            padding: 40px 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .section {
            margin-bottom: 50px;
        }
        
        .section h2 {
            color: #333;
            font-size: 1.8em;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .card {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 25px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .card:hover {
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
            transform: translateY(-5px);
            border-color: #667eea;
        }
        
        .card h3 {
            color: #667eea;
            margin-bottom: 12px;
            font-size: 1.3em;
        }
        
        .card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
            font-size: 0.95em;
        }
        
        .card-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .button:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .button.secondary {
            background: #6c757d;
        }
        
        .button.secondary:hover {
            background: #5a6268;
        }
        
        .button.danger {
            background: #e74c3c;
        }
        
        .button.danger:hover {
            background: #c0392b;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .feature-list {
            list-style: none;
            margin-top: 10px;
        }
        
        .feature-list li {
            padding: 8px 0;
            color: #666;
            display: flex;
            align-items: center;
        }
        
        .feature-list li:before {
            content: '✓';
            color: #27ae60;
            font-weight: bold;
            margin-right: 10px;
            font-size: 1.1em;
        }
        
        .card.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .card.disabled:hover {
            transform: none;
            box-shadow: none;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .badge.admin {
            background: #667eea;
            color: white;
        }
        
        .badge.user {
            background: #27ae60;
            color: white;
        }
        
        .footer {
            background: white;
            border-top: 1px solid #eee;
            padding: 30px;
            text-align: center;
            color: #666;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-radius: 0 0 10px 10px;
            margin-top: 0;
        }
        
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        .alert {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            color: #856404;
        }
        
        .alert.error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .alert.success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8em;
            }
            
            .grid {
                grid-template-columns: 1fr;
            }
            
            .status-banner {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>🎯 Système Avancé de Gestion des Utilisateurs</h1>
            <p>Centre d'accès centralisé - Gestion complète et intelligence artificielle</p>
        </div>
        
        <!-- STATUS BANNER -->
        <div class="status-banner">
            <div>
                <span class="status-badge <?php echo $status; ?>">
                    <?php echo $status_message; ?>
                </span>
            </div>
            <div>
                <?php if ($is_logged_in): ?>
                    <span style="color: #666;">
                        <strong><?php echo htmlspecialchars(obtenir_utilisateur_connecte()); ?></strong>
                        <?php if ($is_admin): ?>
                            <span class="badge admin">Admin</span>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- USER INFO -->
        <?php if ($is_logged_in): ?>
        <div class="user-info">
            <a href="<?php echo $is_admin ? 'dashboard/admin/users/dashboard.php' : '#'; ?>">
                <?php echo $is_admin ? '📊 Tableau de bord' : '👤 Mon profil'; ?>
            </a>
            <a href="logout.php">🚪 Déconnexion</a>
        </div>
        <?php else: ?>
        <div class="user-info">
            <a href="login.php">🔑 Connexion</a>
            <a href="register.php">📝 Inscription</a>
        </div>
        <?php endif; ?>
        
        <!-- CONTENT -->
        <div class="content">
            <!-- ALERTE SI PAS CONNECTÉ -->
            <?php if (!$is_logged_in): ?>
            <div class="alert">
                ℹ️ Vous devez être <strong>connecté</strong> pour accéder à la plupart des fonctionnalités.
                <a href="login.php" style="margin-left: 20px;">Se connecter →</a>
            </div>
            <?php endif; ?>
            
            <!-- ALERTE SI PAS ADMIN -->
            <?php if ($is_logged_in && !$is_admin): ?>
            <div class="alert">
                ℹ️ Certaines fonctionnalités sont réservées aux administrateurs.
            </div>
            <?php endif; ?>
            
            <!-- SECTION 1: DÉMARRAGE -->
            <div class="section">
                <h2>🚀 Démarrage</h2>
                <div class="grid">
                    <div class="card">
                        <div class="card-icon">📋</div>
                        <h3>Démarrage Rapide</h3>
                        <p>Guide en 5 minutes pour commencer immédiatement</p>
                        <a href="QUICK_START.md" class="button" target="_blank">Consulter</a>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">🧪</div>
                        <h3>Tests du Système</h3>
                        <p>Vérifiez que tous les composants fonctionnent correctement</p>
                        <a href="test_users_system.php" class="button">Accéder</a>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">⚙️</div>
                        <h3>Installation</h3>
                        <p>Installer ou réinitialiser le système</p>
                        <a href="install_users_system.php" class="button">Démarrer</a>
                    </div>
                </div>
            </div>
            
            <!-- SECTION 2: ADMINISTRATION -->
            <?php if ($is_admin): ?>
            <div class="section">
                <h2>👥 Administration</h2>
                <div class="grid">
                    <div class="card">
                        <div class="card-icon">📊</div>
                        <h3>Tableau de Bord</h3>
                        <p>Vue d'ensemble du système avec statistiques en temps réel</p>
                        <a href="dashboard/admin/users/dashboard.php" class="button">Accéder</a>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">👤</div>
                        <h3>Gestion Utilisateurs</h3>
                        <p>Liste complète avec filtres avancés et gestion des rôles</p>
                        <a href="dashboard/admin/users/index.php" class="button">Accéder</a>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">📈</div>
                        <h3>Analyses & Rapports</h3>
                        <p>Comportements, progression et statistiques de participation</p>
                        <a href="dashboard/admin/users/dashboard.php" class="button">Accéder</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- SECTION 3: DOCUMENTATION -->
            <div class="section">
                <h2>📚 Documentation</h2>
                <div class="grid">
                    <div class="card">
                        <div class="card-icon">📖</div>
                        <h3>Guide Principal</h3>
                        <p>Vue d'ensemble complète du système avec tous les détails</p>
                        <a href="README_USERS_SYSTEM.md" class="button" target="_blank">Lire</a>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">🔧</div>
                        <h3>Guide d'Intégration</h3>
                        <p>Instructions et exemples pour intégrer dans vos fichiers</p>
                        <a href="AUTH_INTEGRATION_GUIDE.php" class="button" target="_blank">Consulter</a>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">⏰</div>
                        <h3>Configuration Cron</h3>
                        <p>Mise en place des tâches programmées automatiques</p>
                        <a href="CRON_SETUP_GUIDE.md" class="button" target="_blank">Lire</a>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">🔄</div>
                        <h3>Migration de Données</h3>
                        <p>Guide pour migrer vos utilisateurs existants</p>
                        <a href="MIGRATION_GUIDE.md" class="button" target="_blank">Lire</a>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">✅</div>
                        <h3>Checklist Déploiement</h3>
                        <p>Vérifications complètes avant le déploiement en production</p>
                        <a href="DEPLOYMENT_CHECKLIST.md" class="button" target="_blank">Lire</a>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">📋</div>
                        <h3>Résumé Technique</h3>
                        <p>Sommaire complet avec toutes les fonctionnalités</p>
                        <a href="USERS_SYSTEM_SUMMARY.md" class="button" target="_blank">Lire</a>
                    </div>
                </div>
            </div>
            
            <!-- SECTION 4: FEATURES -->
            <div class="section">
                <h2>✨ Fonctionnalités Principales</h2>
                <div class="grid">
                    <div class="card">
                        <div class="card-icon">📊</div>
                        <h3>Suivi d'Activité</h3>
                        <ul class="feature-list">
                            <li>Connexions/Déconnexions</li>
                            <li>Activités de cours</li>
                            <li>Soumissions de quiz</li>
                            <li>Messages de forum</li>
                            <li>Uploads de fichiers</li>
                        </ul>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">🤖</div>
                        <h3>Intelligence Artificielle</h3>
                        <ul class="feature-list">
                            <li>Analyse de comportement</li>
                            <li>Détection de patterns</li>
                            <li>Scores d'engagement</li>
                            <li>Résumés auto-générés</li>
                            <li>Prédictions de progression</li>
                        </ul>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">🔔</div>
                        <h3>Notifications</h3>
                        <ul class="feature-list">
                            <li>Notifications en temps réel</li>
                            <li>Priorités configurables</li>
                            <li>Alertes automatiques</li>
                            <li>Rappels d'inactivité</li>
                            <li>Notifications de progression</li>
                        </ul>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">👥</div>
                        <h3>Gestion des Rôles</h3>
                        <ul class="feature-list">
                            <li>Rôles multiples par utilisateur</li>
                            <li>Historique des changements</li>
                            <li>Niveaux de permission</li>
                            <li>Validité temporelle</li>
                            <li>Permissions granulaires</li>
                        </ul>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">📱</div>
                        <h3>Synchronisation Hors-Ligne</h3>
                        <ul class="feature-list">
                            <li>Queueing automatique</li>
                            <li>Sync à la reconnexion</li>
                            <li>Gestion des erreurs</li>
                            <li>Retry automatique</li>
                            <li>IndexedDB client</li>
                        </ul>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">💬</div>
                        <h3>Chatbot IA</h3>
                        <ul class="feature-list">
                            <li>Réponses intelligentes</li>
                            <li>Rôle-based guidance</li>
                            <li>Support 24/7</li>
                            <li>Suggestions rapides</li>
                            <li>Intégration widget</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- SECTION 5: API & INTEGRATIONS -->
            <div class="section">
                <h2>🔌 API & Intégrations</h2>
                <div class="grid">
                    <div class="card">
                        <div class="card-icon">📡</div>
                        <h3>API Notifications</h3>
                        <p>Endpoints JSON pour gérer les notifications</p>
                        <ul class="feature-list">
                            <li>GET notifications</li>
                            <li>POST mark_as_read</li>
                            <li>POST delete</li>
                        </ul>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">🔄</div>
                        <h3>API Synchronisation</h3>
                        <p>Endpoints pour la synchronisation hors-ligne</p>
                        <ul class="feature-list">
                            <li>GET sync_status</li>
                            <li>POST queue_action</li>
                            <li>GET sync_all</li>
                        </ul>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">🔗</div>
                        <h3>Hooks & Helpers</h3>
                        <p>Fonctions d'intégration simplifiées</p>
                        <ul class="feature-list">
                            <li>hook_user_login()</li>
                            <li>hook_user_logout()</li>
                            <li>hook_user_activity()</li>
                            <li>a_role(), require_role()</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- SECTION 6: SUPPORT & AIDE -->
            <div class="section">
                <h2>🆘 Support & Aide</h2>
                <div class="grid">
                    <div class="card">
                        <div class="card-icon">❓</div>
                        <h3>FAQ</h3>
                        <p>Questions fréquemment posées et réponses</p>
                        <div class="button-group">
                            <a href="README_USERS_SYSTEM.md#dépannage" class="button secondary" target="_blank">Consultez</a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">🐛</div>
                        <h3>Dépannage</h3>
                        <p>Guide de résolution des problèmes courants</p>
                        <div class="button-group">
                            <a href="test_users_system.php" class="button">Tests</a>
                            <a href="logs/cron_jobs.log" class="button secondary" target="_blank">Logs</a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">📞</div>
                        <h3>Contact</h3>
                        <p>Besoin d'aide supplémentaire?</p>
                        <p style="margin-top: 15px; font-size: 0.9em; color: #999;">
                            Consultez la documentation ou vérifiez les logs du système
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FOOTER -->
        <div class="footer">
            <p><strong>Système Avancé de Gestion des Utilisateurs v1.0</strong></p>
            <p>Une solution complète pour la gestion, le suivi et l'analyse des utilisateurs</p>
            <p style="margin-top: 15px; font-size: 0.9em;">
                <a href="README_USERS_SYSTEM.md" target="_blank">Documentation</a> • 
                <a href="QUICK_START.md" target="_blank">Démarrage rapide</a> • 
                <a href="test_users_system.php">Tests</a>
            </p>
            <p style="margin-top: 15px; color: #999; font-size: 0.85em;">
                2024 - Tous droits réservés
            </p>
        </div>
    </div>
</body>
</html>
