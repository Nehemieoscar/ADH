<?php
include 'config.php';

if (!est_connecte()) {
    header('Location: login.php');
    exit;
}

$utilisateur = obtenir_utilisateur_connecte();

// Récupérer les ressources de la bibliothèque
$stmt_ressources = $pdo->prepare("
    SELECT r.*, c.titre as categorie_nom, 
           (SELECT COUNT(*) FROM telechargements t WHERE t.ressource_id = r.id AND t.utilisateur_id = ?) as telecharge
    FROM ressources r
    LEFT JOIN categories_ressources c ON r.categorie_id = c.id
    WHERE r.statut = 'publie'
    ORDER BY r.date_publication DESC
");
$stmt_ressources->execute([$_SESSION['utilisateur_id']]);
$ressources = $stmt_ressources->fetchAll();

// Récupérer les catégories
$stmt_categories = $pdo->prepare("SELECT * FROM categories_ressources ORDER BY nom");
$stmt_categories->execute();
$categories = $stmt_categories->fetchAll();

// Récupérer les ressources récemment consultées
$stmt_recentes = $pdo->prepare("
    SELECT r.*, MAX(v.date_consultation) as derniere_consultation
    FROM ressources r
    JOIN consultations v ON r.id = v.ressource_id
    WHERE v.utilisateur_id = ?
    GROUP BY r.id
    ORDER BY derniere_consultation DESC
    LIMIT 5
");
$stmt_recentes->execute([$_SESSION['utilisateur_id']]);
$ressources_recentes = $stmt_recentes->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $utilisateur['mode_sombre'] ? 'sombre' : 'clair'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliothèque Numérique - ADH</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/bibliotheque.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>

        <main class="dashboard-main">
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>📚 Bibliothèque Numérique</h1>
                    <p>Accédez à des milliers de ressources éducatives</p>
                </div>
                <div class="header-right">
                    <div class="search-box">
                        <input type="text" id="search-input" placeholder="Rechercher une ressource...">
                        <button class="btn btn-primary">🔍</button>
                    </div>
                </div>
            </header>

            <div class="dashboard-content">
                <div class="bibliotheque-container">
                    <!-- Sidebar de filtres -->
                    <div class="filters-sidebar">
                        <!-- Catégories -->
                        <div class="card">
                            <h3>📂 Catégories</h3>
                            <div class="categories-list">
                                <?php foreach ($categories as $categorie): ?>
                                    <label class="category-item">
                                        <input type="checkbox" name="categorie" value="<?php echo $categorie['id']; ?>">
                                        <span><?php echo $categorie['nom']; ?></span>
                                        <span class="count">(<?php 
                                            $count = 0;
                                            foreach ($ressources as $r) {
                                                if ($r['categorie_id'] == $categorie['id']) $count++;
                                            }
                                            echo $count;
                                        ?>)</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Types de ressources -->
                        <div class="card">
                            <h3>📄 Types</h3>
                            <div class="types-list">
                                <label>
                                    <input type="checkbox" name="type" value="pdf" checked> PDF
                                </label>
                                <label>
                                    <input type="checkbox" name="type" value="video" checked> Vidéo
                                </label>
                                <label>
                                    <input type="checkbox" name="type" value="audio" checked> Audio
                                </label>
                                <label>
                                    <input type="checkbox" name="type" value="presentation" checked> Présentation
                                </label>
                                <label>
                                    <input type="checkbox" name="type" value="exercice" checked> Exercice
                                </label>
                            </div>
                        </div>

                        <!-- Niveaux -->
                        <div class="card">
                            <h3>🎯 Niveaux</h3>
                            <div class="niveaux-list">
                                <label>
                                    <input type="checkbox" name="niveau" value="debutant" checked> Débutant
                                </label>
                                <label>
                                    <input type="checkbox" name="niveau" value="intermediaire" checked> Intermédiaire
                                </label>
                                <label>
                                    <input type="checkbox" name="niveau" value="avance" checked> Avancé
                                </label>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="card">
                            <h3>⚡ Actions</h3>
                            <button class="btn btn-outline" id="apply-filters" style="width: 100%; margin-bottom: 0.5rem;">
                                Appliquer les filtres
                            </button>
                            <button class="btn btn-outline" id="reset-filters" style="width: 100%;">
                                Réinitialiser
                            </button>
                        </div>
                    </div>

                    <!-- Contenu principal -->
                    <div class="bibliotheque-main">
                        <!-- Statistiques rapides -->
                        <div class="stats-grid" style="margin-bottom: 2rem;">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo count($ressources); ?></div>
                                <div class="stat-label">Ressources disponibles</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">
                                    <?php
                                    $telechargees = 0;
                                    foreach ($ressources as $r) {
                                        if ($r['telecharge'] > 0) $telechargees++;
                                    }
                                    echo $telechargees;
                                    ?>
                                </div>
                                <div class="stat-label">Ressources téléchargées</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo count($categories); ?></div>
                                <div class="stat-label">Catégories</div>
                            </div>
                            <div class="stat-card secondary">
                                <div class="stat-number">
                                    <?php
                                    $total_pages = 0;
                                    foreach ($ressources as $r) {
                                        $total_pages += $r['nombre_pages'] ?? 0;
                                    }
                                    echo $total_pages;
                                    ?>
                                </div>
                                <div class="stat-label">Pages totales</div>
                            </div>
                        </div>

                        <!-- Ressources récentes -->
                        <?php if (!empty($ressources_recentes)): ?>
                            <div class="card" style="margin-bottom: 2rem;">
                                <h2 style="margin-bottom: 1.5rem;">🕐 Récemment consultées</h2>
                                <div class="ressources-grid">
                                    <?php foreach ($ressources_recentes as $ressource): ?>
                                        <div class="ressource-card">
                                            <div class="ressource-icon">
                                                <?php
                                                $icon = '📄';
                                                switch ($ressource['type']) {
                                                    case 'video': $icon = '🎥'; break;
                                                    case 'audio': $icon = '🎵'; break;
                                                    case 'presentation': $icon = '📊'; break;
                                                    case 'exercice': $icon = '📝'; break;
                                                }
                                                echo $icon;
                                                ?>
                                            </div>
                                            <div class="ressource-content">
                                                <h3><?php echo $ressource['titre']; ?></h3>
                                                <p><?php echo substr($ressource['description'], 0, 100) . '...'; ?></p>
                                                <div class="ressource-meta">
                                                    <span class="categorie"><?php echo $ressource['categorie_nom']; ?></span>
                                                    <span class="niveau"><?php echo ucfirst($ressource['niveau']); ?></span>
                                                    <?php if ($ressource['nombre_pages']): ?>
                                                        <span class="pages"><?php echo $ressource['nombre_pages']; ?> pages</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="ressource-actions">
                                                <button class="btn btn-primary" onclick="consulterRessource(<?php echo $ressource['id']; ?>)">
                                                    Consulter
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Toutes les ressources -->
                        <div class="card">
                            <div class="ressources-header">
                                <h2>📚 Toutes les ressources</h2>
                                <div class="view-options">
                                    <button class="view-btn active" data-view="grid">⏹️ Grille</button>
                                    <button class="view-btn" data-view="list">📃 Liste</button>
                                </div>
                            </div>

                            <?php if (empty($ressources)): ?>
                                <p style="text-align: center; padding: 3rem; color: #666;">
                                    Aucune ressource disponible pour le moment.
                                </p>
                            <?php else: ?>
                                <div class="ressources-container grid-view" id="ressources-container">
                                    <?php foreach ($ressources as $ressource): ?>
                                        <div class="ressource-card" data-categorie="<?php echo $ressource['categorie_id']; ?>" 
                                             data-type="<?php echo $ressource['type']; ?>" 
                                             data-niveau="<?php echo $ressource['niveau']; ?>">
                                            <div class="ressource-header">
                                                <div class="ressource-icon">
                                                    <?php
                                                    $icon = '📄';
                                                    switch ($ressource['type']) {
                                                        case 'video': $icon = '🎥'; break;
                                                        case 'audio': $icon = '🎵'; break;
                                                        case 'presentation': $icon = '📊'; break;
                                                        case 'exercice': $icon = '📝'; break;
                                                    }
                                                    echo $icon;
                                                    ?>
                                                </div>
                                                <div class="ressource-badges">
                                                    <?php if ($ressource['telecharge'] > 0): ?>
                                                        <span class="badge badge-success">📥 Téléchargé</span>
                                                    <?php endif; ?>
                                                    <?php if ($ressource['est_nouveau']): ?>
                                                        <span class="badge badge-info">🆕 Nouveau</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="ressource-content">
                                                <h3><?php echo $ressource['titre']; ?></h3>
                                                <p class="ressource-description"><?php echo $ressource['description']; ?></p>
                                                
                                                <div class="ressource-meta">
                                                    <span class="categorie"><?php echo $ressource['categorie_nom']; ?></span>
                                                    <span class="niveau badge badge-<?php 
                                                        echo $ressource['niveau'] == 'debutant' ? 'info' : 
                                                             ($ressource['niveau'] == 'intermediaire' ? 'warning' : 'success');
                                                    ?>">
                                                        <?php echo ucfirst($ressource['niveau']); ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="ressource-stats">
                                                    <?php if ($ressource['nombre_pages']): ?>
                                                        <span>📖 <?php echo $ressource['nombre_pages']; ?> pages</span>
                                                    <?php endif; ?>
                                                    <?php if ($ressource['duree']): ?>
                                                        <span>⏱️ <?php echo $ressource['duree']; ?> min</span>
                                                    <?php endif; ?>
                                                    <span>⭐ <?php echo $ressource['note_moyenne'] ?? '4.5'; ?>/5</span>
                                                </div>
                                            </div>
                                            
                                            <div class="ressource-actions">
                                                <button class="btn btn-primary" onclick="consulterRessource(<?php echo $ressource['id']; ?>)">
                                                    Consulter
                                                </button>
                                                <button class="btn btn-outline" onclick="telechargerRessource(<?php echo $ressource['id']; ?>)">
                                                    📥 Télécharger
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal de consultation -->
    <div id="consultation-modal" class="modal">
        <div class="modal-content large-modal">
            <div class="modal-header">
                <h3 id="ressource-modal-title">Ressource</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="ressource-content">
                    <!-- Contenu de la ressource chargé dynamiquement -->
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script src="js/dashboard.js"></script>
    <script src="js/bibliotheque.js"></script>
</body>
</html>