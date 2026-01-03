<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo isset($_SESSION['utilisateur_id']) ? (obtenir_utilisateur_connecte()['mode_sombre'] ? 'sombre' : 'clair') : 'clair'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formations - ADH Online</title>
    <link rel="stylesheet" href="css/style.css">
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
                <li><a href="coworking.php" class="nav-link">Coworking</a></li>
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
        <?php
        // Récupérer les formations actives (en_cours ou termine)
        $stmt_formations = $pdo->prepare("
            SELECT f.*, COUNT(i.id) as nombre_inscrits
            FROM formations f 
            LEFT JOIN inscriptions i ON f.id = i.formation_id
            WHERE f.statut IN ('en_cours', 'termine')
            GROUP BY f.id 
            ORDER BY 
                CASE WHEN f.statut = 'en_cours' THEN 0 ELSE 1 END,
                f.date_creation DESC
        ");
        $stmt_formations->execute();
        $formations = $stmt_formations->fetchAll();
        
        // Récupérer une formation spécifique si demandée
        $formation_detaillee = null;
        if (isset($_GET['id'])) {
            $id_formation = (int)$_GET['id'];
            $stmt_detail = $pdo->prepare("
                SELECT f.*, COUNT(i.id) as nombre_inscrits
                FROM formations f 
                LEFT JOIN inscriptions i ON f.id = i.formation_id
                WHERE f.id = ? AND f.statut IN ('en_cours', 'termine')
                GROUP BY f.id
            ");
            $stmt_detail->execute([$id_formation]);
            $formation_detaillee = $stmt_detail->fetch();
            
            if (!$formation_detaillee) {
                header('HTTP/1.0 404 Not Found');
                echo '<div class="container"><h2>Formation non trouvée</h2></div>';
                exit;
            }
        }
        
        // Récupérer les cours associés à la formation
        $cours_formation = [];
        if ($formation_detaillee) {
            $stmt_cours = $pdo->prepare("
                SELECT c.* FROM cours c 
                WHERE c.formation_id = ?
                ORDER BY c.ordre
            ");
            $stmt_cours->execute([$formation_detaillee['id']]);
            $cours_formation = $stmt_cours->fetchAll();
        }
        ?>
        
        <?php if ($formation_detaillee): ?>
            <!-- Page de détail d'une formation -->
            <section style="background: linear-gradient(135deg, var(--couleur-secondaire), var(--couleur-primaire)); color: white; padding: 4rem 0; text-align: center;">
                <div class="container">
                    <a href="adh-online.php#cours" style="color: white; text-decoration: none; margin-bottom: 1rem; display: inline-block;">← Retour aux cours</a>
                    <h1 style="font-size: 2.5rem; margin-bottom: 1rem;"><?php echo htmlspecialchars($formation_detaillee['titre']); ?></h1>
                    <div style="display: flex; gap: 2rem; justify-content: center; align-items: center; flex-wrap: wrap;">
                        <span class="badge" style="background: <?php echo $formation_detaillee['statut'] == 'en_cours' ? '#28a745' : '#17a2b8'; ?>; padding: 0.5rem 1rem; border-radius: 20px;">
                            <?php echo $formation_detaillee['statut'] == 'en_cours' ? '🔴 En cours' : '✓ Terminée'; ?>
                        </span>
                        <span style="font-size: 1.1rem;">👥 <?php echo $formation_detaillee['nombre_inscrits']; ?> <?php echo $formation_detaillee['nombre_inscrits'] > 1 ? 'participants' : 'participant'; ?></span>
                    </div>
                </div>
            </section>

            <section class="container" style="padding: 4rem 0;">
                <div class="grid grid-3" style="gap: 2rem;">
                    <div style="grid-column: 1 / -1;">
                        <div class="card">
                            <h2>À propos de cette formation</h2>
                            <p><?php echo nl2br(htmlspecialchars($formation_detaillee['description'])); ?></p>
                            
                            <?php if ($formation_detaillee['date_disponibilite']): ?>
                                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #ddd;">
                                    <strong>Date de disponibilité :</strong> <?php echo date('d/m/Y', strtotime($formation_detaillee['date_disponibilite'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (est_connecte()): ?>
                                <button class="btn btn-primary" style="margin-top: 1.5rem;" onclick="inscrire_formation(<?php echo $formation_detaillee['id']; ?>)">
                                    S'inscrire à la formation
                                </button>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary" style="margin-top: 1.5rem; display: inline-block;">
                                    Se connecter pour s'inscrire
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($cours_formation)): ?>
                        <div style="grid-column: 1 / -1;">
                            <h2 style="margin-bottom: 2rem;">Cours de cette formation</h2>
                            <div class="grid grid-3">
                                <?php foreach ($cours_formation as $cours): ?>
                                    <div class="card">
                                        <div style="width: 100%; height: 160px; background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-secondaire)); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; border-radius: 8px 8px 0 0;">
                                            📖
                                        </div>
                                        <div style="padding: 1.5rem;">
                                            <span class="badge badge-info"><?php echo ucfirst($cours['niveau'] ?? 'debutant'); ?></span>
                                            <h3 style="margin: 0.5rem 0;"><?php echo htmlspecialchars($cours['titre']); ?></h3>
                                            <p style="color: #666; margin-bottom: 1rem; font-size: 0.9rem;"><?php echo substr($cours['description'] ?? '', 0, 80) . '...'; ?></p>
                                            
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                                                <?php if ($cours['duree_estimee'] ?? false): ?>
                                                    <span style="font-size: 0.8rem; color: #888;">⏱️ <?php echo $cours['duree_estimee']; ?> h</span>
                                                <?php endif; ?>
                                                <a href="cours.php?id=<?php echo $cours['id']; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Voir</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
        <?php else: ?>
            <!-- Liste de toutes les formations -->
            <section style="background: linear-gradient(135deg, var(--couleur-secondaire), var(--couleur-primaire)); color: white; padding: 4rem 0; text-align: center;">
                <div class="container">
                    <a href="adh-online.php#cours" style="color: white; text-decoration: none; margin-bottom: 1rem; display: inline-block;">← Retour à ADH Online</a>
                    <h1 style="font-size: 3rem; margin-bottom: 1rem;">Formations Disponibles</h1>
                    <p style="font-size: 1.2rem; margin-bottom: 2rem;">Découvrez notre sélection de formations accessibles et publiées</p>
                </div>
            </section>

            <section class="container" style="padding: 4rem 0;">
                <?php if (empty($formations)): ?>
                    <div class="card" style="text-align: center; padding: 3rem;">
                        <p style="font-size: 1.2rem; color: #666;">Aucune formation disponible pour le moment.</p>
                        <a href="adh-online.php" class="btn btn-primary" style="margin-top: 1rem;">Retour à ADH Online</a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-3">
                        <?php foreach ($formations as $formation): ?>
                            <div class="card">
                                <div style="width: 100%; height: 160px; background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-secondaire)); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; border-radius: 8px 8px 0 0; position: relative;">
                                    📚
                                    <span class="badge" style="position: absolute; top: 10px; right: 10px; background: <?php echo $formation['statut'] == 'en_cours' ? '#28a745' : '#17a2b8'; ?>; padding: 0.3rem 0.6rem; border-radius: 20px; font-size: 0.75rem; color: white;">
                                        <?php echo $formation['statut'] == 'en_cours' ? 'En cours' : 'Terminée'; ?>
                                    </span>
                                </div>
                                
                                <div style="padding: 1.5rem;">
                                    <h3 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($formation['titre']); ?></h3>
                                    <p style="color: #666; margin-bottom: 1rem; font-size: 0.9rem;"><?php echo substr($formation['description'] ?? '', 0, 100) . '...'; ?></p>
                                    
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="font-size: 0.8rem; color: #888;">👥 <?php echo $formation['nombre_inscrits']; ?> <?php echo $formation['nombre_inscrits'] > 1 ? 'participants' : 'participant'; ?></span>
                                        <a href="formations.php?id=<?php echo $formation['id']; ?>" class="btn btn-primary">Voir</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <script src="js/script.js"></script>
    <script>
        function inscrire_formation(formation_id) {
            if (!<?php echo est_connecte() ? 'true' : 'false'; ?>) {
                window.location.href = 'login.php';
                return;
            }
            
            fetch('dashboard/api/inscrire_formation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    formation_id: formation_id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Inscription réussie !');
                    location.reload();
                } else {
                    alert('Erreur : ' + (data.message || 'Une erreur est survenue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de l\'inscription');
            });
        }
    </script>
</body>
</html>
