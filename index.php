<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo isset($_SESSION['utilisateur_id']) ? (obtenir_utilisateur_connecte()['mode_sombre'] ? 'sombre' : 'clair') : 'clair'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADH - Académie Digitale d'Haïti</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Barre de navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="index.php" style="display:flex;align-items:center;gap:0.5rem;text-decoration:none;">
                    <img src="assets/logo-removebg-preview.png" alt="Logo ADH" style="height:48px;max-height:64px;width:auto;display:block;object-fit:contain;">
                Accueil</a>
            </div>
            
            <ul class="nav-menu">
                <li><a href="about.php" class="nav-link">À propos</a></li>
                <li><a href="cours.php" class="nav-link">Cours</a></li>
                <li><a href="formations.php" class="nav-link">Formations</a></li>
                <li><a href="forum.php" class="nav-link">Forum</a></li>
                <li><a href="teacher_apply.php" class="nav-link">Devenir Professeur</a></li>
                <!-- <li><a href="coworking.php" class="nav-link">Coworking</a></li>
                <li><a href="agenda.php" class="nav-link">Agenda</a></li> -->
                <li><a href="contact.php" class="nav-link">Contact</a></li>
            </ul>
            
            <div class="nav-actions">
                <button class="theme-toggle">🌙</button>
                <?php if (est_connecte()): 
                    $utilisateur = obtenir_utilisateur_connecte();
                ?>
                    <a href="dashboard/dashboard.php" class="btn btn-outline">Tableau de bord</a>
                    <a href="logout.php" class="btn btn-secondary">Déconnexion</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Connexion</a>
                    <a href="register.php" class="btn btn-primary">Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <main class="main-content">
        <section class="hero-section" style="background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-secondaire)); color: white; padding: 4rem 0; text-align: center;">
            <div class="container">
                <h1 style="font-size: 3rem; margin-bottom: 1rem;">Académie Digitale d'Haïti</h1>
                <p style="font-size: 1.2rem; margin-bottom: 2rem;">Formation, Innovation et Transformation Digitale</p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="adh-academie.php" class="btn" style="background: white; color: var(--couleur-primaire);">ADH Académie</a>
                    <a href="adh-online.php" class="btn" style="background: transparent; border: 2px solid white; color: white;">ADH Online</a>
                </div>
            </div>
        </section>

        <section class="container" style="padding: 4rem 0;">
            <div class="grid grid-2">
                <div class="card">
                    <h2>ADH Académie</h2>
                    <p>Centre physique de formation et d'événements technologiques. Rejoignez notre communauté pour des formations en présentiel, des ateliers pratiques et des événements networking.</p>
                    <ul>
                        <li>Formations en présentiel</li>
                        <li>Événements et conférences</li>
                        <li>Réseautage professionnel</li>
                        <li>Certifications reconnues</li>
                    </ul>
                    <a href="adh-academie.php" class="btn btn-primary">Découvrir</a>
                </div>
                
                <div class="card">
                    <h2>ADH Online</h2>
                    <p>Plateforme numérique innovante pour la formation à distance, l'accompagnement et l'innovation. Apprenez à votre rythme avec nos ressources en ligne.</p>
                    <ul>
                        <li>Formations en ligne</li>
                        <li>Assistant IA pédagogique</li>
                        <li>Forum communautaire</li>
                        <li>Coworking virtuel</li>
                    </ul>
                    <a href="adh-online.php" class="btn btn-primary">Explorer</a>
                </div>
            </div>
        </section>

        <section style="background-color: var(--couleur-fond); padding: 4rem 0;">
            <div class="container">
                <h2 style="text-align: center; margin-bottom: 3rem;">Nos Domaines de Formation</h2>
                <div class="grid grid-4">
                    <div class="card" style="text-align: center;">
                        <h3>💻 Développement</h3>
                        <p>Web, Mobile, Logiciel</p>
                    </div>
                    <div class="card" style="text-align: center;">
                        <h3>🎨 Design</h3>
                        <p>UI/UX, Graphique, Digital</p>
                    </div>
                    <div class="card" style="text-align: center;">
                        <h3>📊 Data</h3>
                        <p>Analyse, Science, IA</p>
                    </div>
                    <div class="card" style="text-align: center;">
                        <h3>🔐 Cybersécurité</h3>
                        <p>Sécurité, Réseaux, Cloud</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="js/script.js"></script>
</body>
</html>