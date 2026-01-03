<?php
include '../../../config.php';

// Vérifier que l'utilisateur est admin
if (!est_admin()) {
    header('Location: ../../dashboard.php');
    exit;
}

// Importer les services
require_once '../../../includes/ActivityTracker.php';
require_once '../../../includes/RoleManager.php';
require_once '../../../includes/BehaviorAnalyzer.php';

$tracker = get_activity_tracker();
$role_manager = get_role_manager();

// Récupérer les filtres
$filtre_role = $_GET['role'] ?? '';
$filtre_statut = $_GET['statut'] ?? '';
$filtre_niveau = $_GET['niveau'] ?? '';
$filtre_groupe = $_GET['groupe'] ?? '';
$recherche = $_GET['recherche'] ?? '';

// Construire la requête
$where = "1=1";
$params = [];

if ($filtre_role) {
    $where .= " AND ur.role = ?";
    $params[] = $filtre_role;
}

if ($filtre_statut) {
    $where .= " AND u.statut = ?";
    $params[] = $filtre_statut;
}

if ($filtre_groupe) {
    $where .= " AND u.groupe_id = ?";
    $params[] = $filtre_groupe;
}

if ($recherche) {
    $where .= " AND (u.nom LIKE ? OR u.email LIKE ?)";
    $params[] = "%$recherche%";
    $params[] = "%$recherche%";
}

// Récupérer les utilisateurs
$stmt = $pdo->prepare("
    SELECT DISTINCT 
        u.id, u.nom, u.email, u.avatar, u.statut, u.created_at,
        u.statut_temps_reel, u.groupe_id,
        COUNT(DISTINCT ur.role) as nombre_roles,
        GROUP_CONCAT(DISTINCT ur.role) as roles,
        (SELECT COUNT(*) FROM user_activity WHERE utilisateur_id = u.id AND DATE(date_activite) = CURDATE()) as activites_aujourd_hui
    FROM utilisateurs u
    LEFT JOIN user_roles ur ON u.id = ur.utilisateur_id AND ur.statut = 'actif'
    WHERE $where
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT 100
");

$stmt->execute($params);
$utilisateurs = $stmt->fetchAll();

// Récupérer les groupes pour le filtre
$stmt_groupes = $pdo->query("SELECT id, nom FROM groupes ORDER BY nom");
$groupes = $stmt_groupes->fetchAll();

// Récupérer les rôles disponibles
$roles_disponibles = ['etudiant', 'professeur', 'formateur', 'superviseur', 'admin', 'moderateur'];

?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo obtenir_utilisateur_connecte()['mode_sombre'] ? 'sombre' : 'clair'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - ADH Admin</title>
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .users-filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--couleur-fond);
            border-radius: 8px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .users-table-container {
            overflow-x: auto;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }
        
        .users-table thead {
            background: var(--couleur-primaire);
            color: white;
        }
        
        .users-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }
        
        .users-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .users-table tbody tr:hover {
            background: var(--couleur-fond);
        }
        
        .user-cell {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--couleur-primaire);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-actif {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactif {
            background: #f8d7da;
            color: #721c24;
        }
        
        .role-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 0.5rem;
            white-space: nowrap;
        }
        
        .role-etudiant {
            background: #e7f3ff;
            color: #004085;
        }
        
        .role-professeur {
            background: #fff3cd;
            color: #856404;
        }
        
        .role-admin {
            background: #f8d7da;
            color: #721c24;
        }
        
        .role-formateur {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .action-links {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-links a {
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            font-size: 0.85rem;
            text-decoration: none;
            background: var(--couleur-primaire);
            color: white;
            transition: background 0.3s;
        }
        
        .action-links a:hover {
            background: var(--couleur-secondaire);
        }
        
        .btn-group {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .btn-apply,
        .btn-reset,
        .btn-export {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-apply {
            background: var(--couleur-primaire);
            color: white;
        }
        
        .btn-reset {
            background: #6c757d;
            color: white;
        }
        
        .btn-export {
            background: #28a745;
            color: white;
        }
        
        .online-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }
        
        .online-connecte {
            background: #28a745;
        }
        
        .online-inactif {
            background: #ffc107;
        }
        
        .online-hors-ligne {
            background: #6c757d;
        }
        
        .pagination {
            margin-top: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../../sidebar.php'; ?>
        
        <main class="dashboard-main">
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>Gestion des Utilisateurs</h1>
                    <p>Visualisez et gérez tous les utilisateurs de la plateforme</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-export" onclick="exportUsers('excel')">📊 Exporter Excel</button>
                    <button class="btn btn-export" onclick="exportUsers('pdf')">📄 Exporter PDF</button>
                </div>
            </header>
            
            <div class="dashboard-content">
                <!-- Filtres -->
                <div class="card">
                    <h2>Filtres</h2>
                    <form method="GET" class="users-filters">
                        <div class="filter-group">
                            <label for="recherche">Rechercher</label>
                            <input type="text" id="recherche" name="recherche" placeholder="Nom ou email..." value="<?php echo htmlspecialchars($recherche); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="role">Rôle</label>
                            <select id="role" name="role">
                                <option value="">Tous les rôles</option>
                                <?php foreach ($roles_disponibles as $role): ?>
                                    <option value="<?php echo $role; ?>" <?php echo $filtre_role === $role ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($role); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="statut">Statut</label>
                            <select id="statut" name="statut">
                                <option value="">Tous les statuts</option>
                                <option value="actif" <?php echo $filtre_statut === 'actif' ? 'selected' : ''; ?>>Actif</option>
                                <option value="inactif" <?php echo $filtre_statut === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="groupe">Groupe</label>
                            <select id="groupe" name="groupe">
                                <option value="">Tous les groupes</option>
                                <?php foreach ($groupes as $groupe): ?>
                                    <option value="<?php echo $groupe['id']; ?>" <?php echo $filtre_groupe == $groupe['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($groupe['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group" style="justify-content: flex-end;">
                            <button type="submit" class="btn-apply">Appliquer les filtres</button>
                        </div>
                    </form>
                    
                    <div class="btn-group" style="margin-top: 1rem;">
                        <a href="?page=1" class="btn-reset">Réinitialiser les filtres</a>
                    </div>
                </div>
                
                <!-- Tableau des utilisateurs -->
                <div class="card" style="margin-top: 2rem;">
                    <h2>Utilisateurs (<?php echo count($utilisateurs); ?>)</h2>
                    
                    <div class="users-table-container">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Email</th>
                                    <th>Statut</th>
                                    <th>Rôles</th>
                                    <th>Connecté</th>
                                    <th>Activités (Auj)</th>
                                    <th>Inscrit</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($utilisateurs)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 2rem;">
                                            Aucun utilisateur trouvé
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($utilisateurs as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="user-cell">
                                                    <div class="user-avatar">
                                                        <?php echo strtoupper(substr($user['nom'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($user['nom']); ?></strong>
                                                        <br>
                                                        <small style="color: #999;">#<?php echo $user['id']; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $user['statut']; ?>">
                                                    <?php echo ucfirst($user['statut']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                $roles = explode(',', $user['roles'] ?? '');
                                                foreach ($roles as $role): 
                                                    if ($role):
                                                ?>
                                                    <span class="role-badge role-<?php echo trim($role); ?>">
                                                        <?php echo ucfirst(trim($role)); ?>
                                                    </span>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                ?>
                                            </td>
                                            <td>
                                                <span class="online-indicator online-<?php echo strtolower($user['statut_temps_reel']); ?>"></span>
                                                <?php echo ucfirst($user['statut_temps_reel']); ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <?php echo $user['activites_aujourd_hui'] ?? 0; ?>
                                            </td>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                            </td>
                                            <td>
                                                <div class="action-links">
                                                    <a href="profile.php?id=<?php echo $user['id']; ?>">Voir profil</a>
                                                    <a href="edit.php?id=<?php echo $user['id']; ?>">Éditer</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function exportUsers(format) {
            const url = window.location.href.includes('?') 
                ? window.location.href + '&export=' + format
                : window.location.href + '?export=' + format;
            window.location.href = url;
        }
    </script>
</body>
</html>
