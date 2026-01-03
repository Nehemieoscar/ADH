<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo isset($_SESSION['utilisateur_id']) ? (obtenir_utilisateur_connecte()['mode_sombre'] ? 'sombre' : 'clair') : 'clair'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADH Online - Plateforme de formation numérique</title>
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
                <li><a href="adh-online.php" class="nav-link active">ADH Online</a></li>
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
        <!-- Hero Section -->
        <section class="hero-section" style="background: linear-gradient(135deg, var(--couleur-secondaire), var(--couleur-primaire)); color: white; padding: 4rem 0; text-align: center;">
            <div class="container">
                <h1 style="font-size: 3rem; margin-bottom: 1rem;">ADH Online</h1>
                <p style="font-size: 1.2rem; margin-bottom: 2rem;">Plateforme numérique innovante pour la formation à distance et l'accompagnement</p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="#cours" class="btn" style="background: white; color: var(--couleur-secondaire);">Explorer les cours</a>
                    <a href="#features" class="btn" style="background: transparent; border: 2px solid white; color: white;">Découvrir les features</a>
                    <a href="assistant-ia.php" class="btn" style="background: transparent; border: 2px solid white; color: white;">Assistant IA</a>
                </div>
            </div>
        </section>

        <!-- Fonctionnalités principales -->
        <section id="features" class="container" style="padding: 4rem 0;">
            <h2 style="text-align: center; margin-bottom: 3rem;">Fonctionnalités Innovantes</h2>
            <div class="grid grid-3">
                <div class="card" style="text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🤖</div>
                    <h3>Assistant IA Pédagogique</h3>
                    <p>Assistant intelligent pour vous guider dans votre apprentissage et répondre à vos questions 24h/24</p>
                </div>
                
                <div class="card" style="text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">💬</div>
                    <h3>Forum Communautaire</h3>
                    <p>Échangez avec la communauté, posez vos questions et collaborez sur des projets</p>
                </div>
                
                <div class="card" style="text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">👥</div>
                    <h3>Coworking Virtuel</h3>
                    <p>Espace de travail collaboratif avec gestion de projets et partage de fichiers</p>
                </div>
                
                <div class="card" style="text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
                    <h3>Suivi Personnalisé</h3>
                    <p>Tableau de bord personnalisé avec suivi de progression et recommandations</p>
                </div>
                
                <div class="card" style="text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🎮</div>
                    <h3>Apprentissage Ludique</h3>
                    <p>Quiz interactifs, jeux éducatifs et système de récompenses</p>
                </div>
                
                <div class="card" style="text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📱</div>
                    <h3>Multi-plateforme</h3>
                    <p>Accédez à vos cours depuis n'importe quel appareil, même hors ligne</p>
                </div>
            </div>
        </section>

        <!-- Cours en ligne -->
        <section id="cours" style="background-color: var(--couleur-fond); padding: 4rem 0;">
            <div class="container">
                <h2 style="text-align: center; margin-bottom: 3rem;">Cours en Ligne Populaires</h2>
                <div class="grid grid-3">
                    <?php
                    // Récupérer les cours des formateurs ET les formations des administrateurs
                    $stmt_cours = $pdo->prepare("
                        SELECT c.*, COUNT(i.id) as nombre_etudiants, u.id as formateur_id, u.role
                        FROM cours c 
                        LEFT JOIN inscriptions i ON c.id = i.cours_id 
                        LEFT JOIN utilisateurs u ON c.formateur_id = u.id
                        WHERE c.type = 'en_ligne' AND c.statut = 'publie'
                        GROUP BY c.id 
                        ORDER BY nombre_etudiants DESC 
                        LIMIT 3
                    ");
                    $stmt_cours->execute();
                    $cours_populaires = $stmt_cours->fetchAll();
                    
                    // Récupérer les formations créées par les administrateurs avec statut 'en_cours' ou 'termine'
                    $stmt_formations = $pdo->prepare("
                        SELECT f.*, COUNT(i.id) as nombre_etudiants
                        FROM formations f 
                        LEFT JOIN inscriptions i ON f.id = i.formation_id
                        WHERE f.statut IN ('en_cours', 'termine')
                        GROUP BY f.id 
                        ORDER BY f.date_creation DESC 
                        LIMIT 3
                    ");
                    $stmt_formations->execute();
                    $formations_actives = $stmt_formations->fetchAll();
                    
                    // Fusionner et afficher les cours et formations
                    $tous_les_cours = array_merge($cours_populaires, $formations_actives);
                    
                    foreach ($tous_les_cours as $cours):
                        // Déterminer si c'est une formation ou un cours
                        $est_formation = isset($cours['statut']) && in_array($cours['statut'], ['en_cours', 'termine']);
                        $titre = $cours['titre'] ?? '';
                        $description = $cours['description'] ?? '';
                        $nombre_etudiants = $cours['nombre_etudiants'] ?? 0;
                        $cours_id = $cours['id'] ?? '';
                        $statut = $cours['statut'] ?? 'brouillon';
                        
                        // Image et niveau (spécifiques aux cours)
                        $image_cours = $cours['image_cours'] ?? null;
                        $niveau = $cours['niveau'] ?? 'debutant';
                    ?>
                        <div class="card">
                            <div style="width: 100%; height: 160px; background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-secondaire)); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; border-radius: 8px 8px 0 0; position: relative;">
                                📚
                                <?php if ($est_formation): ?>
                                    <span class="badge" style="position: absolute; top: 10px; right: 10px; background: #28a745; padding: 0.3rem 0.6rem; border-radius: 20px; font-size: 0.75rem; color: white;">
                                        <?php echo $statut == 'en_cours' ? 'En cours' : 'Terminée'; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div style="padding: 1.5rem;">
                                <?php if (!$est_formation): ?>
                                    <span class="badge badge-info"><?php echo ucfirst($niveau); ?></span>
                                <?php else: ?>
                                    <span class="badge" style="background: #ffc107; color: #333;"><?php echo $statut == 'en_cours' ? 'Formation active' : 'Formation terminée'; ?></span>
                                <?php endif; ?>
                                <h3 style="margin: 0.5rem 0;"><?php echo htmlspecialchars($titre); ?></h3>
                                <p style="color: #666; margin-bottom: 1rem;"><?php echo substr($description, 0, 100) . '...'; ?></p>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 0.8rem; color: #888;"><?php echo $nombre_etudiants; ?> <?php echo $nombre_etudiants > 1 ? 'participants' : 'participant'; ?></span>
                                    <?php if ($est_formation): ?>
                                        <a href="formations.php?id=<?php echo $cours_id; ?>" class="btn btn-primary">Voir la formation</a>
                                    <?php else: ?>
                                        <a href="cours.php?id=<?php echo $cours_id; ?>" class="btn btn-primary">Voir le cours</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="cours.php" class="btn btn-primary">Voir tous les cours</a>
                </div>
            </div>
        </section>

        <!-- Témoignages -->
        <section class="container" style="padding: 4rem 0;">
            <h2 style="text-align: center; margin-bottom: 3rem;">Ce que disent nos étudiants</h2>
            <div class="grid grid-2">
                <div class="card">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div style="width: 50px; height: 50px; background: var(--couleur-primaire); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">MP</div>
                        <div>
                            <strong>Marie Pierre</strong>
                            <p style="margin: 0; color: #666;">Développeuse Web</p>
                        </div>
                    </div>
                    <p>"La plateforme ADH Online m'a permis de me reconvertir en développement web tout en continuant à travailler. L'assistant IA est incroyablement utile !"</p>
                </div>
                
                <div class="card">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div style="width: 50px; height: 50px; background: var(--couleur-secondaire); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">JL</div>
                        <div>
                            <strong>Jean Louis</strong>
                            <p style="margin: 0; color: #666;">Étudiant en Data Science</p>
                        </div>
                    </div>
                    <p>"Le forum communautaire et les espaces de coworking virtuel rendent l'apprentissage à distance beaucoup plus interactif et motivant."</p>
                </div>
            </div>
        </section>

        <!-- CTA Final -->
        <section style="background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-secondaire)); color: white; padding: 4rem 0; text-align: center;">
            <div class="container">
                <h2 style="margin-bottom: 1rem;">Commencez votre apprentissage en ligne aujourd'hui</h2>
                <p style="margin-bottom: 2rem; opacity: 0.9;">Rejoignez des milliers d'apprenants et développez vos compétences à votre rythme.</p>
                
                <?php if (est_connecte()): ?>
                    <a href="dashboard.php" class="btn" style="background: white; color: var(--couleur-primaire);">Accéder à mon dashboard</a>
                <?php else: ?>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="register.php" class="btn" style="background: white; color: var(--couleur-primaire);">Créer un compte gratuit</a>
                        <a href="cours.php" class="btn" style="background: transparent; border: 2px solid white; color: white;">Explorer les cours</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script src="js/script.js"></script>
</body>
</html>