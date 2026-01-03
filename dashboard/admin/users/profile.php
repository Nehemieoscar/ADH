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
require_once '../../../includes/NotificationService.php';

$tracker = get_activity_tracker();
$role_manager = get_role_manager();
$behavior = get_behavior_analyzer();
$notifications = get_notification_service();

// Récupérer l'ID de l'utilisateur
$user_id = (int)($_GET['id'] ?? 0);
if (!$user_id) {
    header('Location: index.php');
    exit;
}

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("
    SELECT u.*, 
           up.bio, up.phone, up.adresse, up.ville, up.code_postal, up.pays,
           up.date_naissance, up.genre, up.competences, up.secteur_activite,
           up.entreprise, up.niveau_etudes, up.photo_profil, up.resume_comportement_ia,
           uba.score_engagement, uba.score_assiduité, uba.heures_pic_activite,
           uba.jour_plus_actif, uba.taux_participation_forum, uba.taux_completion_cours,
           uba.pattern_comportement
    FROM utilisateurs u
    LEFT JOIN user_profiles up ON u.id = up.utilisateur_id
    LEFT JOIN user_behavior_analysis uba ON u.id = uba.utilisateur_id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('HTTP/1.0 404 Not Found');
    echo 'Utilisateur non trouvé';
    exit;
}

// Récupérer les rôles
$roles = $role_manager->get_active_roles($user_id);
$rôles_history = $role_manager->get_role_history($user_id);

// Récupérer les formations
$stmt_formations = $pdo->prepare("
    SELECT f.*, fp.progression_global, fp.statut_inscription, fp.date_inscription, fp.date_completion
    FROM formations f
    LEFT JOIN formation_progression fp ON f.id = fp.formation_id
    WHERE fp.utilisateur_id = ?
    ORDER BY fp.date_inscription DESC
");
$stmt_formations->execute([$user_id]);
$formations = $stmt_formations->fetchAll();

// Récupérer les cours
$stmt_cours = $pdo->prepare("
    SELECT c.*, cp.progression, cp.statut_inscription, cp.date_inscription, cp.date_completion, cp.temps_suivi
    FROM cours c
    LEFT JOIN cours_progression cp ON c.id = cp.cours_id
    WHERE cp.utilisateur_id = ?
    ORDER BY cp.date_inscription DESC
");
$stmt_cours->execute([$user_id]);
$cours = $stmt_cours->fetchAll();

// Récupérer les certifications
$stmt_certs = $pdo->prepare("
    SELECT * FROM user_certifications
    WHERE utilisateur_id = ?
    ORDER BY date_obtention DESC
");
$stmt_certs->execute([$user_id]);
$certifications = $stmt_certs->fetchAll();

// Récupérer les activités récentes
$activites = $tracker->get_activity_history($user_id, 20);

// Récupérer les statistiques de participation
$stmt_participation = $pdo->prepare("
    SELECT * FROM participation_stats
    WHERE utilisateur_id = ?
    ORDER BY annee DESC, mois DESC
    LIMIT 1
");
$stmt_participation->execute([$user_id]);
$participation = $stmt_participation->fetch();

// Gérer l'envoi de notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_notification') {
    $titre = $_POST['titre'] ?? '';
    $message = $_POST['message'] ?? '';
    $type = $_POST['type'] ?? 'info';
    $priorite = $_POST['priorite'] ?? 'normale';
    
    if ($titre && $message) {
        $notifications->send_notification($user_id, $titre, $message, $type, $priorite, $_SESSION['utilisateur_id']);
        $success_msg = "Notification envoyée avec succès";
    }
}

// Générer l'analyse comportement si elle n'existe pas
if (!$user['score_engagement']) {
    $behavior->analyze_user_behavior($user_id);
    // Récupérer les nouvelles données
    $stmt = $pdo->prepare("SELECT * FROM user_behavior_analysis WHERE utilisateur_id = ?");
    $stmt->execute([$user_id]);
    $behavior_data = $stmt->fetch();
    if ($behavior_data) {
        $user['score_engagement'] = $behavior_data['score_engagement'];
        $user['score_assiduité'] = $behavior_data['score_assiduité'];
    }
}

?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo obtenir_utilisateur_connecte()['mode_sombre'] ? 'sombre' : 'clair'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?php echo htmlspecialchars($user['nom']); ?> - ADH Admin</title>
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-secondaire));
            color: white;
            padding: 3rem 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 2rem;
            align-items: start;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            border: 3px solid white;
        }
        
        .profile-info h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
        }
        
        .profile-info p {
            margin: 0.3rem 0;
            opacity: 0.9;
        }
        
        .profile-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .profile-actions button,
        .profile-actions a {
            padding: 0.7rem 1.2rem;
            border: none;
            border-radius: 4px;
            background: rgba(255,255,255,0.2);
            color: white;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: background 0.3s;
            font-weight: 600;
        }
        
        .profile-actions button:hover,
        .profile-actions a:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .profile-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--couleur-primaire);
        }
        
        .profile-section h2 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: var(--couleur-primaire);
            border-bottom: 2px solid var(--couleur-fond);
            padding-bottom: 0.5rem;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .profile-item {
            padding: 1rem;
            background: var(--couleur-fond);
            border-radius: 4px;
        }
        
        .profile-item-label {
            font-weight: 600;
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }
        
        .profile-item-value {
            font-size: 1.1rem;
            color: var(--couleur-primaire);
        }
        
        .role-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            background: var(--couleur-primaire);
            color: white;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .score-bar {
            height: 8px;
            background: #eee;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        
        .score-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--couleur-primaire), var(--couleur-secondaire));
            transition: width 0.3s;
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
        
        .status-en-session {
            background: #cfe2ff;
            color: #084298;
        }
        
        .formation-item,
        .cours-item,
        .cert-item {
            padding: 1rem;
            background: var(--couleur-fond);
            border-radius: 4px;
            margin-bottom: 1rem;
            border-left: 4px solid var(--couleur-primaire);
        }
        
        .formation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .formation-header h4 {
            margin: 0;
            color: var(--couleur-primaire);
        }
        
        .progression-bar {
            height: 6px;
            background: #eee;
            border-radius: 3px;
            overflow: hidden;
            margin: 0.5rem 0;
        }
        
        .progression-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
        }
        
        .activity-item {
            padding: 0.8rem;
            background: var(--couleur-fond);
            border-radius: 4px;
            margin-bottom: 0.8rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .activity-type {
            background: var(--couleur-primaire);
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .notification-form {
            background: var(--couleur-fond);
            padding: 1.5rem;
            border-radius: 4px;
            margin-top: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.7rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn-send {
            background: var(--couleur-primaire);
            color: white;
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-send:hover {
            background: var(--couleur-secondaire);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .stat-card {
            background: var(--couleur-fond);
            padding: 1rem;
            border-radius: 4px;
            text-align: center;
            border-top: 3px solid var(--couleur-primaire);
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--couleur-primaire);
            margin: 0.5rem 0;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #666;
        }
        
        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #eee;
        }
        
        .tab-button {
            padding: 0.8rem 1.5rem;
            border: none;
            background: none;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab-button.active {
            color: var(--couleur-primaire);
            border-bottom-color: var(--couleur-primaire);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../../sidebar.php'; ?>
        
        <main class="dashboard-main">
            <!-- En-tête profil -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['nom'], 0, 1)); ?>
                </div>
                
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['nom']); ?></h1>
                    <p><strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
                    <p>
                        <span class="status-badge status-<?php echo $user['statut']; ?>">
                            <?php echo ucfirst($user['statut']); ?>
                        </span>
                        <span class="status-badge status-<?php echo strtolower(str_replace('_', '-', $user['statut_temps_reel'])); ?>">
                            🔴 <?php echo ucfirst($user['statut_temps_reel']); ?>
                        </span>
                    </p>
                    <p style="margin-top: 0.5rem;">Inscrit le <?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?></p>
                </div>
                
                <div class="profile-actions">
                    <a href="index.php">← Retour à la liste</a>
                    <button onclick="document.getElementById('notification-form').scrollIntoView()">📬 Envoyer alerte</button>
                    <a href="edit.php?id=<?php echo $user['id']; ?>">✏️ Éditer</a>
                </div>
            </div>
            
            <!-- Onglets -->
            <div class="tabs">
                <button class="tab-button active" onclick="switchTab(event, 'overview')">Aperçu</button>
                <button class="tab-button" onclick="switchTab(event, 'formations')">Formations & Cours</button>
                <button class="tab-button" onclick="switchTab(event, 'activites')">Activités</button>
                <button class="tab-button" onclick="switchTab(event, 'participation')">Participation</button>
                <button class="tab-button" onclick="switchTab(event, 'comportement')">Comportement IA</button>
                <button class="tab-button" onclick="switchTab(event, 'roles')">Rôles & Permissions</button>
            </div>
            
            <!-- Onglet: Aperçu -->
            <div id="overview" class="tab-content active">
                <?php if (isset($success_msg)): ?>
                    <div class="alert alert-success"><?php echo $success_msg; ?></div>
                <?php endif; ?>
                
                <!-- Informations personnelles -->
                <div class="profile-section">
                    <h2>Informations Personnelles</h2>
                    <div class="profile-grid">
                        <div class="profile-item">
                            <div class="profile-item-label">Prénom & Nom</div>
                            <div class="profile-item-value"><?php echo htmlspecialchars($user['nom']); ?></div>
                        </div>
                        <div class="profile-item">
                            <div class="profile-item-label">Email</div>
                            <div class="profile-item-value"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        <div class="profile-item">
                            <div class="profile-item-label">Téléphone</div>
                            <div class="profile-item-value"><?php echo $user['phone'] ?? '-'; ?></div>
                        </div>
                        <div class="profile-item">
                            <div class="profile-item-label">Genre</div>
                            <div class="profile-item-value"><?php echo $user['genre'] ?? '-'; ?></div>
                        </div>
                        <div class="profile-item">
                            <div class="profile-item-label">Date de naissance</div>
                            <div class="profile-item-value"><?php echo $user['date_naissance'] ? date('d/m/Y', strtotime($user['date_naissance'])) : '-'; ?></div>
                        </div>
                        <div class="profile-item">
                            <div class="profile-item-label">Entreprise</div>
                            <div class="profile-item-value"><?php echo $user['entreprise'] ?? '-'; ?></div>
                        </div>
                    </div>
                    
                    <?php if ($user['bio']): ?>
                        <div style="margin-top: 1rem; padding: 1rem; background: var(--couleur-fond); border-radius: 4px;">
                            <strong>Bio:</strong>
                            <p><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Scores et engagement -->
                <div class="profile-section">
                    <h2>Scores & Engagement</h2>
                    <div class="profile-grid">
                        <div class="profile-item">
                            <div class="profile-item-label">Score d'Engagement</div>
                            <div class="profile-item-value"><?php echo (int)($user['score_engagement'] ?? 0); ?>/100</div>
                            <div class="score-bar">
                                <div class="score-fill" style="width: <?php echo (int)($user['score_engagement'] ?? 0); ?>%"></div>
                            </div>
                        </div>
                        <div class="profile-item">
                            <div class="profile-item-label">Score d'Assiduité</div>
                            <div class="profile-item-value"><?php echo (int)($user['score_assiduité'] ?? 0); ?>/100</div>
                            <div class="score-bar">
                                <div class="score-fill" style="width: <?php echo (int)($user['score_assiduité'] ?? 0); ?>%"></div>
                            </div>
                        </div>
                        <div class="profile-item">
                            <div class="profile-item-label">Taux de Complétion</div>
                            <div class="profile-item-value"><?php echo number_format($user['taux_completion_cours'] ?? 0, 1); ?>%</div>
                            <div class="score-bar">
                                <div class="score-fill" style="width: <?php echo (int)($user['taux_completion_cours'] ?? 0); ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Certifications -->
                <?php if (!empty($certifications)): ?>
                    <div class="profile-section">
                        <h2>Certifications & Badges</h2>
                        <?php foreach ($certifications as $cert): ?>
                            <div class="cert-item">
                                <div class="formation-header">
                                    <h4><?php echo htmlspecialchars($cert['titre']); ?></h4>
                                    <span class="status-badge status-<?php echo $cert['statut']; ?>">
                                        <?php echo ucfirst($cert['statut']); ?>
                                    </span>
                                </div>
                                <p style="margin: 0.5rem 0 0 0; color: #666;">
                                    <strong>Obtenue le:</strong> <?php echo date('d/m/Y', strtotime($cert['date_obtention'])); ?>
                                    <?php if ($cert['date_expiration']): ?>
                                        | <strong>Expire le:</strong> <?php echo date('d/m/Y', strtotime($cert['date_expiration'])); ?>
                                    <?php endif; ?>
                                </p>
                                <?php if ($cert['description']): ?>
                                    <p style="margin: 0.5rem 0 0 0; color: #666; font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($cert['description']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Onglet: Formations & Cours -->
            <div id="formations" class="tab-content">
                <div class="profile-section">
                    <h2>Formations en Cours</h2>
                    <?php 
                    $formations_en_cours = array_filter($formations, fn($f) => $f['statut_inscription'] === 'en_cours');
                    if (empty($formations_en_cours)): 
                    ?>
                        <p style="color: #999;">Aucune formation en cours</p>
                    <?php else: ?>
                        <?php foreach ($formations_en_cours as $formation): ?>
                            <div class="formation-item">
                                <div class="formation-header">
                                    <h4><?php echo htmlspecialchars($formation['titre']); ?></h4>
                                </div>
                                <div class="progression-bar">
                                    <div class="progression-fill" style="width: <?php echo $formation['progression_global']; ?>%"></div>
                                </div>
                                <p style="margin: 0.5rem 0 0 0; color: #666; font-size: 0.9rem;">
                                    Progression: <strong><?php echo $formation['progression_global']; ?>%</strong> | 
                                    Inscrit le: <?php echo date('d/m/Y', strtotime($formation['date_inscription'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="profile-section">
                    <h2>Cours Suivis</h2>
                    <?php if (empty($cours)): ?>
                        <p style="color: #999;">Aucun cours suivi</p>
                    <?php else: ?>
                        <?php foreach ($cours as $c): ?>
                            <div class="cours-item">
                                <div class="formation-header">
                                    <h4><?php echo htmlspecialchars($c['titre']); ?></h4>
                                    <span class="status-badge status-<?php echo $c['statut_inscription']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $c['statut_inscription'])); ?>
                                    </span>
                                </div>
                                <div class="progression-bar">
                                    <div class="progression-fill" style="width: <?php echo $c['progression']; ?>%"></div>
                                </div>
                                <p style="margin: 0.5rem 0 0 0; color: #666; font-size: 0.9rem;">
                                    Progression: <strong><?php echo $c['progression']; ?>%</strong> | 
                                    Temps suivi: <strong><?php echo $c['temps_suivi']; ?> min</strong> | 
                                    Inscrit le: <?php echo date('d/m/Y', strtotime($c['date_inscription'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Onglet: Activités -->
            <div id="activites" class="tab-content">
                <div class="profile-section">
                    <h2>Activités Récentes (20 dernières)</h2>
                    <?php if (empty($activites)): ?>
                        <p style="color: #999;">Aucune activité enregistrée</p>
                    <?php else: ?>
                        <?php foreach ($activites as $activite): ?>
                            <div class="activity-item">
                                <div>
                                    <span class="activity-type"><?php echo ucfirst(str_replace('_', ' ', $activite['type_activite'])); ?></span>
                                    <p style="margin: 0.5rem 0 0 0;">
                                        <?php echo htmlspecialchars($activite['description'] ?? $activite['type_activite']); ?>
                                    </p>
                                    <small style="color: #999;">
                                        <?php echo date('d/m/Y à H:i', strtotime($activite['date_activite'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Onglet: Participation -->
            <div id="participation" class="tab-content">
                <div class="profile-section">
                    <h2>Taux de Participation</h2>
                    <?php if ($participation): ?>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-label">Taux de Présence</div>
                                <div class="stat-value"><?php echo number_format($participation['taux_presence'], 1); ?>%</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-label">Sessions</div>
                                <div class="stat-value"><?php echo $participation['nombre_sessions']; ?></div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-label">Messages</div>
                                <div class="stat-value"><?php echo $participation['nombre_messages']; ?></div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-label">Devoirs Remis</div>
                                <div class="stat-value"><?php echo $participation['nombre_devoirs_remis']; ?></div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-label">Quizzes</div>
                                <div class="stat-value"><?php echo $participation['nombre_quiz_participations']; ?></div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-label">Retards</div>
                                <div class="stat-value" style="color: #dc3545;"><?php echo $participation['retards']; ?></div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-label">Demandes en Attente</div>
                                <div class="stat-value" style="color: #ffc107;"><?php echo $participation['demandes_en_attente']; ?></div>
                            </div>
                        </div>
                        <p style="margin-top: 1rem; color: #999;">
                            Période: <?php echo date('m/Y', mktime(0, 0, 0, $participation['mois'], 1, $participation['annee'])); ?>
                        </p>
                    <?php else: ?>
                        <p style="color: #999;">Aucune statistique de participation disponible</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Onglet: Comportement IA -->
            <div id="comportement" class="tab-content">
                <div class="profile-section">
                    <h2>Analyse de Comportement IA</h2>
                    <div class="profile-grid">
                        <div class="profile-item">
                            <div class="profile-item-label">Heures de Pic</div>
                            <div class="profile-item-value"><?php echo $user['heures_pic_activite'] ?? 'Non disponible'; ?></div>
                        </div>
                        <div class="profile-item">
                            <div class="profile-item-label">Jour Plus Actif</div>
                            <div class="profile-item-value"><?php echo $user['jour_plus_actif'] ?? 'Non disponible'; ?></div>
                        </div>
                        <div class="profile-item">
                            <div class="profile-item-label">Participation Forum</div>
                            <div class="profile-item-value"><?php echo number_format($user['taux_participation_forum'] ?? 0, 2); ?>%</div>
                        </div>
                    </div>
                    
                    <?php if ($user['pattern_comportement']): ?>
                        <div style="margin-top: 1.5rem; padding: 1rem; background: var(--couleur-fond); border-radius: 4px;">
                            <strong>Patterns détectés:</strong>
                            <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem;">
                                <?php 
                                $patterns = json_decode($user['pattern_comportement'], true) ?? [];
                                foreach ($patterns as $pattern): 
                                ?>
                                    <li><?php echo htmlspecialchars($pattern); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($user['resume_comportement_ia']): ?>
                        <div style="margin-top: 1rem; padding: 1rem; background: #e7f3ff; border-left: 4px solid #0066cc; border-radius: 4px;">
                            <strong>🤖 Résumé IA:</strong>
                            <p style="margin: 0.5rem 0 0 0;">
                                <?php echo htmlspecialchars($user['resume_comportement_ia']); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Onglet: Rôles & Permissions -->
            <div id="roles" class="tab-content">
                <div class="profile-section">
                    <h2>Rôles Actifs</h2>
                    <div>
                        <?php if (empty($roles)): ?>
                            <p style="color: #999;">Aucun rôle assigné</p>
                        <?php else: ?>
                            <?php foreach ($roles as $role): ?>
                                <span class="role-badge"><?php echo ucfirst($role); ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($rôles_history)): ?>
                    <div class="profile-section">
                        <h2>Historique des Rôles</h2>
                        <?php foreach ($rôles_history as $history): ?>
                            <div class="formation-item">
                                <p style="margin: 0;">
                                    <strong><?php echo $history['ancien_role'] ? ucfirst($history['ancien_role']) : 'N/A'; ?></strong> 
                                    → 
                                    <strong><?php echo ucfirst($history['nouveau_role']); ?></strong>
                                </p>
                                <p style="margin: 0.5rem 0 0 0; color: #666; font-size: 0.9rem;">
                                    <?php echo date('d/m/Y à H:i', strtotime($history['date_changement'])); ?>
                                    <?php if ($history['raison']): ?>
                                        | Raison: <?php echo htmlspecialchars($history['raison']); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Formulaire d'envoi d'alerte -->
            <div id="notification-form" class="profile-section" style="margin-top: 3rem;">
                <h2>Envoyer une Alerte/Notification</h2>
                <form method="POST" class="notification-form">
                    <input type="hidden" name="action" value="send_notification">
                    
                    <div class="form-group">
                        <label for="titre">Titre *</label>
                        <input type="text" id="titre" name="titre" required placeholder="Ex: Rappel de participation">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" required placeholder="Contenu de l'alerte..."></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="type">Type</label>
                            <select id="type" name="type">
                                <option value="info">Info</option>
                                <option value="warning">Avertissement</option>
                                <option value="error">Erreur</option>
                                <option value="alert">Alerte</option>
                                <option value="rappel">Rappel</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="priorite">Priorité</label>
                            <select id="priorite" name="priorite">
                                <option value="basse">Basse</option>
                                <option value="normale" selected>Normale</option>
                                <option value="haute">Haute</option>
                                <option value="urgente">Urgente</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-send">📬 Envoyer la notification</button>
                </form>
            </div>
        </main>
    </div>
    
    <script>
        function switchTab(event, tabName) {
            event.preventDefault();
            
            // Masquer tous les onglets
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Désactiver tous les boutons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Afficher l'onglet sélectionné
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
