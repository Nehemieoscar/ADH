<?php
include 'config.php';

// Récupérer les catégories du forum avec statistiques
$stmt_categories = $pdo->prepare("
    SELECT fc.*, 
           COUNT(fs.id) as nombre_sujets,
           COUNT(fm.id) as nombre_messages,
           MAX(fm.date_creation) as dernier_message_date
    FROM forum_categories fc
    LEFT JOIN forum_sujets fs ON fc.id = fs.categorie_id
    LEFT JOIN forum_messages fm ON fs.id = fm.sujet_id
    GROUP BY fc.id
    ORDER BY fc.ordre
");
$stmt_categories->execute();
$categories = $stmt_categories->fetchAll();

// Récupérer les derniers messages
$stmt_derniers_messages = $pdo->prepare("
    SELECT fm.*, fs.titre as sujet_titre, u.nom as auteur_nom, fc.nom as categorie_nom
    FROM forum_messages fm
    JOIN forum_sujets fs ON fm.sujet_id = fs.id
    JOIN forum_categories fc ON fs.categorie_id = fc.id
    JOIN utilisateurs u ON fm.utilisateur_id = u.id
    ORDER BY fm.date_creation DESC
    LIMIT 5
");
$stmt_derniers_messages->execute();
$derniers_messages = $stmt_derniers_messages->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo isset($_SESSION['utilisateur_id']) ? (obtenir_utilisateur_connecte()['mode_sombre'] ? 'sombre' : 'clair') : 'clair'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum Communautaire - ADH</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/forum.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <span>ADH</span>
            </div>
            
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">Accueil</a></li>
                <li><a href="adh-academie.php" class="nav-link">ADH Académie</a></li>
                <li><a href="adh-online.php" class="nav-link">ADH Online</a></li>
                <li><a href="cours.php" class="nav-link">Cours</a></li>
                <li><a href="forum.php" class="nav-link active">Forum</a></li>
                <li><a href="coworking.php" class="nav-link">Coworking</a></li>
                <li><a href="assistant-ia.php" class="nav-link">Assistant IA</a></li>
            </ul>
            
            <div class="nav-actions">
                <button class="theme-toggle">🌙</button>
                <?php if (est_connecte()): ?>
                    <a href="dashboard.php" class="btn btn-outline">Tableau de bord</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Connexion</a>
                    <a href="register.php" class="btn btn-primary">Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <div class="forum-header">
                <h1>Forum Communautaire ADH</h1>
                <p>Échangez, posez vos questions et collaborez avec la communauté</p>
                
                <?php if (est_connecte()): ?>
                    <div class="forum-actions">
                        <a href="nouveau-sujet.php" class="btn btn-primary">➕ Nouveau sujet</a>
                        <a href="mes-sujets.php" class="btn btn-outline">📝 Mes sujets</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Catégories du forum -->
            <div class="forum-categories">
                <?php foreach ($categories as $categorie): ?>
                    <div class="forum-category card">
                        <div class="category-header">
                            <div class="category-info">
                                <h3><?php echo $categorie['nom']; ?></h3>
                                <p><?php echo $categorie['description']; ?></p>
                            </div>
                            <div class="category-stats">
                                <div class="stat">
                                    <span class="number"><?php echo $categorie['nombre_sujets']; ?></span>
                                    <span class="label">Sujets</span>
                                </div>
                                <div class="stat">
                                    <span class="number"><?php echo $categorie['nombre_messages']; ?></span>
                                    <span class="label">Messages</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="category-actions">
                            <a href="categorie-forum.php?id=<?php echo $categorie['id']; ?>" class="btn btn-outline">Voir les sujets</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Derniers messages et sidebar -->
            <div class="forum-content">
                <div class="main-content">
                    <!-- Derniers messages -->
                    <div class="card">
                        <h2 style="margin-bottom: 1.5rem;">💬 Derniers messages</h2>
                        
                        <?php if (empty($derniers_messages)): ?>
                            <p style="text-align: center; padding: 2rem; color: #666;">
                                Aucun message dans le forum pour le moment.
                            </p>
                        <?php else: ?>
                            <div class="messages-list">
                                <?php foreach ($derniers_messages as $message): ?>
                                    <div class="message-item">
                                        <div class="message-avatar">
                                            <div class="avatar-placeholder">
                                                <?php echo strtoupper(substr($message['auteur_nom'], 0, 1)); ?>
                                            </div>
                                        </div>
                                        <div class="message-content">
                                            <div class="message-header">
                                                <strong><?php echo $message['auteur_nom']; ?></strong>
                                                <span class="message-date">
                                                    <?php echo date('d/m/Y à H:i', strtotime($message['date_creation'])); ?>
                                                </span>
                                            </div>
                                            <p class="message-text">
                                                <?php echo substr($message['contenu'], 0, 150) . '...'; ?>
                                            </p>
                                            <div class="message-meta">
                                                <span class="badge badge-info"><?php echo $message['categorie_nom']; ?></span>
                                                <a href="sujet.php?id=<?php echo $message['sujet_id']; ?>" class="sujet-link">
                                                    Dans: <?php echo $message['sujet_titre']; ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sidebar du forum -->
                <div class="forum-sidebar">
                    <?php if (!est_connecte()): ?>
                        <div class="card">
                            <h3 style="margin-bottom: 1rem;">👋 Rejoignez la conversation</h3>
                            <p style="margin-bottom: 1rem;">Connectez-vous pour participer au forum et poser vos questions.</p>
                            <div style="display: grid; gap: 0.5rem;">
                                <a href="login.php" class="btn btn-primary">Se connecter</a>
                                <a href="register.php" class="btn btn-outline">S'inscrire</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <h3 style="margin-bottom: 1rem;">📊 Vos statistiques</h3>
                            <?php
                            $utilisateur_id = $_SESSION['utilisateur_id'];
                            $stmt_stats = $pdo->prepare("
                                SELECT 
                                    COUNT(DISTINCT fs.id) as mes_sujets,
                                    COUNT(fm.id) as mes_messages
                                FROM forum_messages fm
                                LEFT JOIN forum_sujets fs ON fm.sujet_id = fs.id AND fs.utilisateur_id = ?
                                WHERE fm.utilisateur_id = ?
                            ");
                            $stmt_stats->execute([$utilisateur_id, $utilisateur_id]);
                            $stats = $stmt_stats->fetch();
                            ?>
                            <div style="display: grid; gap: 1rem;">
                                <div style="display: flex; justify-content: between;">
                                    <span>Sujets créés:</span>
                                    <strong><?php echo $stats['mes_sujets']; ?></strong>
                                </div>
                                <div style="display: flex; justify-content: between;">
                                    <span>Messages:</span>
                                    <strong><?php echo $stats['mes_messages']; ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Règles du forum -->
                    <div class="card">
                        <h3 style="margin-bottom: 1rem;">📏 Règles du forum</h3>
                        <ul style="font-size: 0.9rem; color: #666; padding-left: 1rem;">
                            <li>Soyez respectueux envers les autres membres</li>
                            <li>Recherchez avant de créer un nouveau sujet</li>
                            <li>Utilisez des titres clairs et descriptifs</li>
                            <li>Postez dans la bonne catégorie</li>
                            <li>Pas de spam ou de publicité</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="js/script.js"></script>
    <script src="js/forum.js"></script>
</body>
</html>