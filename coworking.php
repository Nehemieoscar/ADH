<?php
include 'config.php';

// Récupérer les projets publics
$stmt_projets = $pdo->prepare("
    SELECT p.*, u.nom as createur_nom, COUNT(pm.id) as nombre_membres
    FROM projets_coworking p
    LEFT JOIN utilisateurs u ON p.createur_id = u.id
    LEFT JOIN projet_membres pm ON p.id = pm.projet_id
    WHERE p.statut = 'actif'
    GROUP BY p.id
    ORDER BY p.date_creation DESC
    LIMIT 12
");
$stmt_projets->execute();
$projets = $stmt_projets->fetchAll();

// Récupérer les projets de l'utilisateur connecté
$mes_projets = [];
if (est_connecte()) {
    $stmt_mes_projets = $pdo->prepare("
        SELECT p.*, u.nom as createur_nom, COUNT(pm.id) as nombre_membres
        FROM projets_coworking p
        LEFT JOIN utilisateurs u ON p.createur_id = u.id
        LEFT JOIN projet_membres pm ON p.id = pm.projet_id
        WHERE pm.utilisateur_id = ?
        GROUP BY p.id
        ORDER BY p.date_creation DESC
    ");
    $stmt_mes_projets->execute([$_SESSION['utilisateur_id']]);
    $mes_projets = $stmt_mes_projets->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo isset($_SESSION['utilisateur_id']) ? (obtenir_utilisateur_connecte()['mode_sombre'] ? 'sombre' : 'clair') : 'clair'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Coworking - ADH</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/coworking.css">
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
                <li><a href="forum.php" class="nav-link">Forum</a></li>
                <li><a href="coworking.php" class="nav-link active">Coworking</a></li>
                <li><a href="assistant-ia.php" class="nav-link">Assistant IA</a></li>
            </ul>
            
            <div class="nav-actions">
                <button class="theme-toggle">🌙</button>
                <?php if (est_connecte()): ?>
                    <a href="dashboard/dashboard.php" class="btn btn-outline">Tableau de bord</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Connexion</a>
                    <a href="register.php" class="btn btn-primary">Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <div class="coworking-header">
                <h1>🚀 Espace Coworking</h1>
                <p>Collaborez sur des projets innovants avec la communauté ADH</p>
                
                <?php if (est_connecte()): ?>
                    <div class="coworking-actions">
                        <a href="creer-projet.php" class="btn btn-primary">➕ Créer un projet</a>
                        <a href="mes-projets.php" class="btn btn-outline">📋 Mes projets</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Projets de l'utilisateur -->
            <?php if (est_connecte() && !empty($mes_projets)): ?>
                <section class="coworking-section">
                    <h2>📁 Mes projets</h2>
                    <div class="projets-grid">
                        <?php foreach ($mes_projets as $projet): ?>
                            <div class="projet-card card">
                                <div class="projet-header">
                                    <h3><?php echo $projet['titre']; ?></h3>
                                    <span class="projet-statut badge badge-success">Actif</span>
                                </div>
                                
                                <p class="projet-description">
                                    <?php echo substr($projet['description'], 0, 100) . '...'; ?>
                                </p>
                                
                                <div class="projet-meta">
                                    <div class="projet-info">
                                        <span>👤 Créé par: <?php echo $projet['createur_nom']; ?></span>
                                        <span>👥 Membres: <?php echo $projet['nombre_membres']; ?></span>
                                    </div>
                                    <div class="projet-date">
                                        <?php echo date('d/m/Y', strtotime($projet['date_creation'])); ?>
                                    </div>
                                </div>
                                
                                <div class="projet-actions">
                                    <a href="projet.php?id=<?php echo $projet['id']; ?>" class="btn btn-primary">Accéder au projet</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Tous les projets -->
            <section class="coworking-section">
                <h2>🌐 Tous les projets</h2>
                
                <?php if (empty($projets)): ?>
                    <div class="card" style="text-align: center; padding: 3rem;">
                        <h3>Aucun projet actif</h3>
                        <p>Soyez le premier à créer un projet de coworking !</p>
                        <?php if (est_connecte()): ?>
                            <a href="creer-projet.php" class="btn btn-primary">Créer un projet</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary">Se connecter pour créer un projet</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="projets-grid">
                        <?php foreach ($projets as $projet): ?>
                            <div class="projet-card card">
                                <div class="projet-header">
                                    <h3><?php echo $projet['titre']; ?></h3>
                                    <span class="projet-statut badge badge-<?php 
                                        echo $projet['statut'] == 'actif' ? 'success' : 
                                             ($projet['statut'] == 'termine' ? 'secondary' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst($projet['statut']); ?>
                                    </span>
                                </div>
                                
                                <p class="projet-description">
                                    <?php echo substr($projet['description'], 0, 150) . '...'; ?>
                                </p>
                                
                                <div class="projet-meta">
                                    <div class="projet-info">
                                        <span>👤 Créé par: <?php echo $projet['createur_nom']; ?></span>
                                        <span>👥 Membres: <?php echo $projet['nombre_membres']; ?></span>
                                    </div>
                                    <div class="projet-date">
                                        <?php echo date('d/m/Y', strtotime($projet['date_creation'])); ?>
                                    </div>
                                </div>
                                
                                <div class="projet-actions">
                                    <?php if (est_connecte()): ?>
                                        <?php 
                                        $est_membre = false;
                                        foreach ($mes_projets as $mon_projet) {
                                            if ($mon_projet['id'] == $projet['id']) {
                                                $est_membre = true;
                                                break;
                                            }
                                        }
                                        ?>
                                        
                                        <?php if ($est_membre): ?>
                                            <a href="projet.php?id=<?php echo $projet['id']; ?>" class="btn btn-primary">Accéder</a>
                                        <?php else: ?>
                                            <form method="POST" action="rejoindre-projet.php" style="display: inline;">
                                                <input type="hidden" name="projet_id" value="<?php echo $projet['id']; ?>">
                                                <button type="submit" class="btn btn-outline">Rejoindre</button>
                                            </form>
                                            <a href="projet.php?id=<?php echo $projet['id']; ?>" class="btn btn-outline">Voir</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-outline">Se connecter</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="text-align: center; margin-top: 2rem;">
                        <a href="tous-les-projets.php" class="btn btn-outline">Voir tous les projets</a>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Fonctionnalités du coworking -->
            <section class="coworking-features">
                <h2 style="text-align: center; margin-bottom: 3rem;">💡 Comment fonctionne le coworking ?</h2>
                <div class="grid grid-3">
                    <div class="feature-card card" style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">👥</div>
                        <h3>Créez ou rejoignez</h3>
                        <p>Lancez votre projet ou rejoignez une équipe existante pour collaborer</p>
                    </div>
                    
                    <div class="feature-card card" style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">💬</div>
                        <h3>Collaborez en temps réel</h3>
                        <p>Discutez, partagez des fichiers et travaillez ensemble en synchronisé</p>
                    </div>
                    
                    <div class="feature-card card" style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">🚀</div>
                        <h3>Réalisez vos idées</h3>
                        <p>Transformez vos concepts en projets concrets avec l'aide de la communauté</p>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script src="js/script.js"></script>
    <script src="js/coworking.js"></script>
</body>
</html>