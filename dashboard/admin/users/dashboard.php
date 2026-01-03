<?php
include '../../../config.php';

if (!est_admin()) {
    header('Location: ../../dashboard.php');
    exit;
}

require_once '../../../includes/ActivityTracker.php';
require_once '../../../includes/RoleManager.php';

$tracker = get_activity_tracker();
$role_manager = get_role_manager();

// Récupérer les statistiques
$stmt_users = $pdo->query("SELECT COUNT(*) as total FROM utilisateurs");
$total_users = $stmt_users->fetch()['total'];

$stmt_active = $pdo->query("SELECT COUNT(*) as total FROM utilisateurs WHERE statut = 'actif'");
$active_users = $stmt_active->fetch()['total'];

$stmt_online = $pdo->query("SELECT COUNT(*) as total FROM user_online_status WHERE est_connecte = TRUE");
$online_users = $stmt_online->fetch()['total'] ?? 0;

$stmt_formations = $pdo->query("SELECT COUNT(*) as total FROM formations WHERE statut IN ('en_cours', 'termine')");
$total_formations = $stmt_formations->fetch()['total'];

// Statistiques par rôle
$stmt_roles = $pdo->query("
    SELECT role, COUNT(*) as nombre
    FROM user_roles
    WHERE statut = 'actif'
    GROUP BY role
    ORDER BY nombre DESC
");
$roles_count = $stmt_roles->fetchAll();

// Utilisateurs récemment inscrits
$stmt_recent = $pdo->query("
    SELECT u.*, COUNT(ur.role) as role_count
    FROM utilisateurs u
    LEFT JOIN user_roles ur ON u.id = ur.utilisateur_id AND ur.statut = 'actif'
    ORDER BY u.created_at DESC
    LIMIT 5
");
$recent_users = $stmt_recent->fetchAll();

// Activités aujourd'hui
$stmt_activities = $pdo->query("
    SELECT type_activite, COUNT(*) as nombre
    FROM user_activity
    WHERE DATE(date_activite) = CURDATE()
    GROUP BY type_activite
    ORDER BY nombre DESC
    LIMIT 5
");
$activities = $stmt_activities->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo obtenir_utilisateur_connecte()['mode_sombre'] ? 'sombre' : 'clair'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs & Accès - ADH Admin</title>
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-secondaire));
            color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .admin-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid var(--couleur-primaire);
        }
        
        .stat-value {
            font-size: 2.2rem;
            font-weight: bold;
            color: var(--couleur-primaire);
            margin: 0.5rem 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .stat-change {
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }
        
        .change-positive {
            color: #28a745;
        }
        
        .change-negative {
            color: #dc3545;
        }
        
        .quick-actions {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .quick-actions h3 {
            margin-top: 0;
            color: var(--couleur-primaire);
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.7rem;
            padding: 1.2rem;
            background: var(--couleur-fond);
            border: 2px solid var(--couleur-primaire);
            border-radius: 8px;
            color: var(--couleur-primaire);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .action-btn:hover {
            background: var(--couleur-primaire);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .action-btn span {
            font-size: 1.5rem;
        }
        
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .card h3 {
            margin-top: 0;
            color: var(--couleur-primaire);
            border-bottom: 2px solid var(--couleur-fond);
            padding-bottom: 0.5rem;
        }
        
        .user-item {
            padding: 0.8rem 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .user-item:last-child {
            border-bottom: none;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--couleur-primaire);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.85rem;
        }
        
        .user-details h4 {
            margin: 0;
            font-size: 0.95rem;
        }
        
        .user-details p {
            margin: 0.2rem 0 0 0;
            font-size: 0.85rem;
            color: #999;
        }
        
        .role-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            background: #e7f3ff;
            color: #0066cc;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .activity-item {
            padding: 0.8rem 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-type {
            background: var(--couleur-primaire);
            color: white;
            padding: 0.3rem 0.7rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .activity-count {
            font-weight: bold;
            color: var(--couleur-primaire);
        }
        
        @media (max-width: 768px) {
            .two-column {
                grid-template-columns: 1fr;
            }
            
            .admin-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../../sidebar.php'; ?>
        
        <main class="dashboard-main">
            <div class="admin-header">
                <h1>👥 Gestion des Utilisateurs & Accès</h1>
                <p>Tableau de bord de gestion complète des utilisateurs, rôles et permissions</p>
            </div>
            
            <div class="dashboard-content">
                <!-- Statistiques principales -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <p class="stat-label">Utilisateurs Total</p>
                        <div class="stat-value"><?php echo $total_users; ?></div>
                        <p class="stat-change change-positive">↑ 12 cette semaine</p>
                    </div>
                    
                    <div class="stat-card">
                        <p class="stat-label">Actif Maintenant</p>
                        <div class="stat-value" style="color: #28a745;"><?php echo $online_users; ?></div>
                        <p class="stat-change"><?php echo round(($online_users / $active_users) * 100, 1); ?>% du total</p>
                    </div>
                    
                    <div class="stat-card">
                        <p class="stat-label">Utilisateurs Actifs</p>
                        <div class="stat-value"><?php echo $active_users; ?></div>
                        <p class="stat-change change-negative">−2 inactifs</p>
                    </div>
                    
                    <div class="stat-card">
                        <p class="stat-label">Formations Actives</p>
                        <div class="stat-value"><?php echo $total_formations; ?></div>
                        <p class="stat-change">En cours et terminées</p>
                    </div>
                </div>
                
                <!-- Actions rapides -->
                <div class="quick-actions">
                    <h3>🚀 Actions Rapides</h3>
                    <div class="actions-grid">
                        <a href="index.php" class="action-btn">
                            <span>📋</span>
                            <span>Voir tous les utilisateurs</span>
                        </a>
                        <a href="index.php?filtre_role=professeur" class="action-btn">
                            <span>👨‍🏫</span>
                            <span>Gestion des profs</span>
                        </a>
                        <a href="index.php?filtre_statut=inactif" class="action-btn">
                            <span>⚠️</span>
                            <span>Utilisateurs inactifs</span>
                        </a>
                        <a href="../../../dashboard/admin/index.php" class="action-btn">
                            <span>📊</span>
                            <span>Rapports détaillés</span>
                        </a>
                    </div>
                </div>
                
                <!-- Deux colonnes: Utilisateurs récents et Activités -->
                <div class="two-column">
                    <div class="card">
                        <h3>👥 Utilisateurs Récents</h3>
                        <?php if (empty($recent_users)): ?>
                            <p style="color: #999;">Aucun utilisateur</p>
                        <?php else: ?>
                            <?php foreach ($recent_users as $user): ?>
                                <div class="user-item">
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['nom'], 0, 1)); ?>
                                        </div>
                                        <div class="user-details">
                                            <h4><?php echo htmlspecialchars($user['nom']); ?></h4>
                                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                                        </div>
                                    </div>
                                    <a href="profile.php?id=<?php echo $user['id']; ?>" style="color: var(--couleur-primaire); text-decoration: none; font-weight: 600;">→</a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card">
                        <h3>📊 Activités Aujourd'hui</h3>
                        <?php if (empty($activities)): ?>
                            <p style="color: #999;">Aucune activité enregistrée</p>
                        <?php else: ?>
                            <?php foreach ($activities as $activity): ?>
                                <div class="activity-item">
                                    <span class="activity-type">
                                        <?php echo ucfirst(str_replace('_', ' ', $activity['type_activite'])); ?>
                                    </span>
                                    <span class="activity-count"><?php echo $activity['nombre']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Rôles actifs -->
                <div class="card">
                    <h3>🎭 Distribution des Rôles Actifs</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                        <?php if (empty($roles_count)): ?>
                            <p style="color: #999;">Aucun rôle assigné</p>
                        <?php else: ?>
                            <?php foreach ($roles_count as $role): ?>
                                <div style="padding: 1rem; background: var(--couleur-fond); border-radius: 4px; text-align: center;">
                                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--couleur-primaire);">
                                        <?php echo $role['nombre']; ?>
                                    </div>
                                    <div style="font-size: 0.9rem; color: #666;">
                                        <?php echo ucfirst($role['role']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Informations utiles -->
                <div class="card" style="margin-top: 2rem; background: #f0f7ff; border-left-color: #0066cc;">
                    <h3>ℹ️ Informations Système</h3>
                    <ul style="margin: 0; padding: 0 0 0 1.5rem; color: #666;">
                        <li>Les notifications en temps réel sont activées</li>
                        <li>Le suivi d'activité est enregistré automatiquement</li>
                        <li>L'analyse IA du comportement s'exécute chaque nuit</li>
                        <li>La synchronisation hors-ligne est disponible</li>
                        <li>Les rôles modulables permettent plusieurs rôles par utilisateur</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
