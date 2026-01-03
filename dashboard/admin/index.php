<?php
include '../../config.php';

// Vérifier si l'utilisateur est admin
if (!est_connecte() || !est_admin()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

$utilisateur = obtenir_utilisateur_connecte();
$initiales = obtenir_initiales($utilisateur['nom']);

// Initialiser toutes les variables pour éviter les erreurs
$erreur_bdd = '';
$stats = [
    'total_etudiants' => 0,
    'total_professeurs' => 0, 
    'total_cours' => 0,
    'taux_completion_moyen' => 0,
    'inscriptions_du_jour' => 0,
    'messages_du_jour' => 0
];
$activite_hebdo = [];
$cours_populaires = [];
$enseignants_attente = [];
$etudiants_inactifs = [];
$alertes = [];

// Récupération des statistiques globales
try {
    // Statistiques principales - CORRIGÉ
    $stmt_stats = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM utilisateurs WHERE role = 'etudiant' AND statut = 'actif') as total_etudiants,
            (SELECT COUNT(*) FROM utilisateurs WHERE role = 'professeur' AND statut = 'actif') as total_professeurs,
            (SELECT COUNT(*) FROM cours WHERE statut = 'publie') as total_cours,
            (SELECT COALESCE(AVG(progression), 0) FROM inscriptions) as taux_completion_moyen,
            (SELECT COUNT(*) FROM utilisateurs WHERE DATE(date_inscription) = CURDATE()) as inscriptions_du_jour,
            (SELECT COUNT(*) FROM forum_messages WHERE DATE(date_creation) = CURDATE()) as messages_du_jour
    ");
    $stats_result = $stmt_stats->fetch();
    if ($stats_result) {
        $stats = array_merge($stats, $stats_result);
    }

    // Activité hebdomadaire - CORRIGÉ (utilisation de date_inscription au lieu de date_creation)
    $stmt_activite = $pdo->query("
        SELECT 
            DATE(date_inscription) as date,
            COUNT(*) as connexions
        FROM utilisateurs 
        WHERE date_inscription >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(date_inscription)
        ORDER BY date
    ");
    $activite_hebdo = $stmt_activite->fetchAll() ?: [];

    // Cours les plus populaires - CORRIGÉ (suppression de la colonne problématique)
    $stmt_cours_populaires = $pdo->query("
        SELECT 
            c.titre,
            c.niveau,
            COUNT(i.id) as inscriptions,
            COALESCE(AVG(i.progression), 0) as progression_moyenne
        FROM cours c
        LEFT JOIN inscriptions i ON c.id = i.cours_id
        WHERE c.statut = 'publie'
        GROUP BY c.id
        ORDER BY inscriptions DESC
        LIMIT 5
    ");
    $cours_populaires = $stmt_cours_populaires->fetchAll() ?: [];

    // Enseignants en attente de validation - DÉJÀ CORRECT
    $stmt_enseignants_attente = $pdo->prepare("
        SELECT id, nom, email, date_inscription 
        FROM utilisateurs 
        WHERE role = 'professeur' AND statut = 'inactif'
        ORDER BY date_inscription DESC
    ");
    $stmt_enseignants_attente->execute();
    $enseignants_attente = $stmt_enseignants_attente->fetchAll() ?: [];

    // Étudiants inactifs - CORRIGÉ (utilisation de date_inscription)
    $stmt_etudiants_inactifs = $pdo->query("
        SELECT 
            u.id,
            u.nom,
            u.email,
            MAX(i.date_inscription) as derniere_activite,
            COUNT(i.id) as cours_inscrits
        FROM utilisateurs u
        LEFT JOIN inscriptions i ON u.id = i.utilisateur_id
        WHERE u.role = 'etudiant' 
        AND u.statut = 'actif'
        GROUP BY u.id
        HAVING derniere_activite < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        OR derniere_activite IS NULL 
        LIMIT 10
    ");
    $etudiants_inactifs = $stmt_etudiants_inactifs->fetchAll() ?: [];

    // Alertes et notifications - CORRIGÉ (simplifié pour éviter les erreurs)
    $stmt_alertes = $pdo->query("
        SELECT 
            'cours_sans_enseignant' as type,
            COUNT(*) as count,
            'Cours sans enseignant assigné' as message
        FROM cours 
        WHERE formateur_id IS NULL AND statut = 'publie'
    ");
    $alertes = $stmt_alertes->fetchAll() ?: [];

} catch (PDOException $e) {
    $erreur_bdd = "Erreur lors du chargement des données: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - ADH</title>
    <link rel="stylesheet" href="../css/admin-dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Styles temporaires pour éviter les erreurs CSS */
        :root {
            --primary-color: #667eea;
            --primary-dark: #5a6fd8;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --error-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f9fafb;
            --border-color: #e5e7eb;
            --sidebar-width: 250px;
            --header-height: 70px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light-color);
        }
        
        .navbar {
            background: white;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 70px;
        }
        
        .nav-logo span {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .nav-search {
            flex: 1;
            max-width: 400px;
            margin: 0 2rem;
        }
        
        .search-box {
            position: relative;
            max-width: 400px;
        }
        
        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 12px 10px 40px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: #f9fafb;
            font-size: 0.9rem;
        }
        
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .nav-icon {
            position: relative;
            background: none;
            border: none;
            padding: 8px;
            border-radius: 6px;
            color: #6b7280;
            cursor: pointer;
        }
        
        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: var(--error-color);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .profile-dropdown {
            position: relative;
        }
        
        .profile-toggle {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: none;
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .avatar-initials {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .avatar-initials.large {
            width: 48px;
            height: 48px;
            font-size: 1.1rem;
        }
        
        .avatar-initials.small {
            width: 40px;
            height: 40px;
            font-size: 0.8rem;
        }
        
        .admin-container {
            display: flex;
            min-height: calc(100vh - var(--header-height));
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: var(--dark-color);
            color: white;
            position: fixed;
            height: calc(100vh - var(--header-height));
            overflow-y: auto;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: #d1d5db;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .nav-item:hover,
        .nav-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--primary-color);
        }
        
        .admin-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 2rem;
            background: var(--light-color);
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .admin-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark-color);
        }
        
        .header-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--dark-color);
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-danger {
            background: var(--error-color);
            color: white;
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
            border-left: 4px solid transparent;
        }
        
        .stat-card:nth-child(1) { border-left-color: var(--primary-color); }
        .stat-card:nth-child(2) { border-left-color: var(--success-color); }
        .stat-card:nth-child(3) { border-left-color: var(--warning-color); }
        .stat-card:nth-child(4) { border-left-color: #3b82f6; }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }
        
        .stat-icon.primary { background: var(--primary-color); }
        .stat-icon.success { background: var(--success-color); }
        .stat-icon.warning { background: var(--warning-color); }
        .stat-icon.info { background: #3b82f6; }
        
        .stat-content h3 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--dark-color);
        }
        
        .stat-content p {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-trend {
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .stat-trend.positive {
            color: var(--success-color);
        }
        
        .alertes-container {
            margin-bottom: 2rem;
        }
        
        .alerte-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: white;
            border-left: 4px solid var(--warning-color);
            border-radius: 8px;
            margin-bottom: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-content {
            padding: 1.5rem;
        }
        
        .user-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .user-item:last-child {
            border-bottom: none;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-info strong {
            display: block;
            margin-bottom: 0.25rem;
        }
        
        .user-info span {
            font-size: 0.8rem;
            color: #6b7280;
            display: block;
            margin-bottom: 0.25rem;
        }
        
        .user-info small {
            font-size: 0.7rem;
            color: #9ca3af;
        }
        
        .user-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-warning {
            background: var(--warning-color);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem 1rem;
            color: #9ca3af;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .view-all {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Admin -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <span>ADH Admin</span>
            </div>
            
            <div class="nav-search">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Rechercher utilisateurs, cours...">
                </div>
            </div>

            <div class="nav-actions">
                <button class="nav-icon" id="notifications-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge"><?php echo count($alertes); ?></span>
                </button>
                
                <div class="profile-dropdown">
                    <button class="profile-toggle">
                        <div class="avatar-initials"><?php echo $initiales; ?></div>
                        <span><?php echo htmlspecialchars($utilisateur['nom']); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar Admin -->
    <div class="admin-container">
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Vue d'ensemble</span>
                </a>
                <a href="utilisateurs.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Utilisateurs</span>
                </a>
                <a href="cours.php" class="nav-item">
                    <i class="fas fa-book"></i>
                    <span>Cours</span>
                </a>
                <a href="enseignants.php" class="nav-item">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Enseignants</span>
                </a>
                <a href="analytics.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </a>
                <a href="support.php" class="nav-item">
                    <i class="fas fa-headset"></i>
                    <span>Centre d'aide</span>
                </a>
                <a href="rapports.php" class="nav-item">
                    <i class="fas fa-file-export"></i>
                    <span>Rapports</span>
                </a>
                <a href="parametres.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Paramètres</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <h1>Vue d'ensemble de la plateforme</h1>
                <div class="header-actions">
                    <button class="btn btn-outline" id="export-btn">
                        <i class="fas fa-download"></i>
                        Exporter rapport
                    </button>
                    <button class="btn btn-primary" id="refresh-btn">
                        <i class="fas fa-sync-alt"></i>
                        Actualiser
                    </button>
                </div>
            </div>

            <?php if ($erreur_bdd): ?>
                <div class="alertes-container">
                    <div class="alerte-item">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="alerte-content">
                            <strong>Erreur base de données</strong>
                            <span><?php echo $erreur_bdd; ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Alertes et notifications -->
            <?php if (!empty($alertes)): ?>
            <div class="alertes-container">
                <?php foreach ($alertes as $alerte): ?>
                    <div class="alerte-item alerte-<?php echo $alerte['type']; ?>">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="alerte-content">
                            <strong><?php echo htmlspecialchars($alerte['message']); ?></strong>
                            <span><?php echo $alerte['count']; ?> élément(s) concerné(s)</span>
                        </div>
                        <button class="btn btn-outline btn-sm">Voir</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Statistiques principales -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_etudiants']; ?></h3>
                        <p>Étudiants inscrits</p>
                        <span class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i>
                            <?php echo $stats['inscriptions_du_jour']; ?> aujourd'hui
                        </span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_professeurs']; ?></h3>
                        <p>Enseignants actifs</p>
                        <span class="stat-trend">
                            <?php echo count($enseignants_attente); ?> en attente
                        </span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_cours']; ?></h3>
                        <p>Cours disponibles</p>
                        <span class="stat-trend">
                            Tous niveaux
                        </span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo round($stats['taux_completion_moyen'], 1); ?>%</h3>
                        <p>Complétion moyenne</p>
                        <span class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i>
                            2.5% vs mois dernier
                        </span>
                    </div>
                </div>
            </div>

            <!-- Contenu principal en grille -->
            <div class="content-grid">
                <!-- Cours populaires -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>Cours les plus populaires</h3>
                        <a href="cours.php" class="view-all">Voir tout</a>
                    </div>
                    <div class="card-content">
                        <?php if (!empty($cours_populaires)): ?>
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <?php foreach ($cours_populaires as $cours): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid #eee;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($cours['titre']); ?></strong>
                                        <div style="font-size: 0.8rem; color: #6b7280;">
                                            <?php echo $cours['inscriptions']; ?> inscriptions • 
                                            Progression: <?php echo round($cours['progression_moyenne'], 1); ?>%
                                        </div>
                                    </div>
                                    <span class="badge"><?php echo ucfirst($cours['niveau']); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-book"></i>
                                <p>Aucun cours disponible</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Enseignants en attente -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>Enseignants en attente de validation</h3>
                        <span class="badge badge-warning"><?php echo count($enseignants_attente); ?></span>
                    </div>
                    <div class="card-content">
                        <?php if (!empty($enseignants_attente)): ?>
                            <?php foreach ($enseignants_attente as $enseignant): ?>
                            <div class="user-item">
                                <div class="user-avatar">
                                    <div class="avatar-initials small">
                                        <?php echo obtenir_initiales($enseignant['nom']); ?>
                                    </div>
                                </div>
                                <div class="user-info">
                                    <strong><?php echo htmlspecialchars($enseignant['nom']); ?></strong>
                                    <span><?php echo htmlspecialchars($enseignant['email']); ?></span>
                                    <small>Inscrit le <?php echo date('d/m/Y', strtotime($enseignant['date_inscription'])); ?></small>
                                </div>
                                <div class="user-actions">
                                    <button class="btn btn-success btn-sm" onclick="validerEnseignant(<?php echo $enseignant['id']; ?>)">
                                        <i class="fas fa-check"></i>
                                        Valider
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="refuserEnseignant(<?php echo $enseignant['id']; ?>)">
                                        <i class="fas fa-times"></i>
                                        Refuser
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-check-circle"></i>
                                <p>Aucun enseignant en attente de validation</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Étudiants inactifs -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>Étudiants inactifs</h3>
                        <a href="utilisateurs.php?filter=inactif" class="view-all">Voir tout</a>
                    </div>
                    <div class="card-content">
                        <?php if (!empty($etudiants_inactifs)): ?>
                            <?php foreach ($etudiants_inactifs as $etudiant): ?>
                            <div class="user-item">
                                <div class="user-avatar">
                                    <div class="avatar-initials small">
                                        <?php echo obtenir_initiales($etudiant['nom']); ?>
                                    </div>
                                </div>
                                <div class="user-info">
                                    <strong><?php echo htmlspecialchars($etudiant['nom']); ?></strong>
                                    <span><?php echo htmlspecialchars($etudiant['email']); ?></span>
                                    <small>
                                        <?php echo $etudiant['cours_inscrits']; ?> cours • 
                                        Dernière activité: 
                                        <?php echo $etudiant['derniere_activite'] ? date('d/m/Y', strtotime($etudiant['derniere_activite'])) : 'Jamais'; ?>
                                    </small>
                                </div>
                                <div class="user-actions">
                                    <button class="btn btn-outline btn-sm" onclick="contacterEtudiant(<?php echo $etudiant['id']; ?>)">
                                        <i class="fas fa-envelope"></i>
                                        Contacter
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-user-check"></i>
                                <p>Aucun étudiant inactif récent</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Activité récente -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>Activité récente</h3>
                    </div>
                    <div class="card-content">
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div style="display: flex; align-items: center; gap: 1rem; padding: 0.5rem 0;">
                                <div style="width: 32px; height: 32px; background: var(--primary-color); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div>
                                    <p style="margin: 0; font-size: 0.9rem;">Nouvel étudiant inscrit</p>
                                    <small style="color: #6b7280;">Il y a 5 minutes</small>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 1rem; padding: 0.5rem 0;">
                                <div style="width: 32px; height: 32px; background: var(--success-color); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div>
                                    <p style="margin: 0; font-size: 0.9rem;">Cours complété par 3 étudiants</p>
                                    <small style="color: #6b7280;">Il y a 1 heure</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function validerEnseignant(id) {
            if (confirm('Voulez-vous vraiment valider cet enseignant ?')) {
                alert('Fonction de validation pour l\'enseignant ' + id);
                // Implémentation AJAX à ajouter
            }
        }

        function refuserEnseignant(id) {
            if (confirm('Voulez-vous vraiment refuser cet enseignant ?')) {
                alert('Fonction de refus pour l\'enseignant ' + id);
                // Implémentation AJAX à ajouter
            }
        }

        function contacterEtudiant(id) {
            alert('Fonction de contact pour l\'étudiant ' + id);
        }

        // Rafraîchissement manuel
        document.getElementById('refresh-btn').addEventListener('click', () => {
            location.reload();
        });
    </script>
</body>
</html>