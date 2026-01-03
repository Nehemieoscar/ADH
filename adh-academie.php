<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo isset($_SESSION['utilisateur_id']) ? (obtenir_utilisateur_connecte()['mode_sombre'] ? 'sombre' : 'clair') : 'clair'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADH Académie - Centre de formation physique</title>
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
                <li><a href="adh-academie.php" class="nav-link active">ADH Académie</a></li>
                <li><a href="adh-online.php" class="nav-link">ADH Online</a></li>
                <li><a href="cours.php" class="nav-link">Cours</a></li>
                <li><a href="formations.php" class="nav-link">Formations</a></li>
                <li><a href="evenements.php" class="nav-link">Événements</a></li>
                <li><a href="contact.php" class="nav-link">Contact</a></li>
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
        <section class="hero-section" style="background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-secondaire)); color: white; padding: 4rem 0; text-align: center;">
            <div class="container">
                <h1 style="font-size: 3rem; margin-bottom: 1rem;">ADH Académie</h1>
                <p style="font-size: 1.2rem; margin-bottom: 2rem;">Centre physique de formation et d'événements technologiques</p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="#formations" class="btn" style="background: white; color: var(--couleur-primaire);">Voir les formations</a>
                    <a href="#evenements" class="btn" style="background: transparent; border: 2px solid white; color: white;">Événements à venir</a>
                    <a href="#visite" class="btn" style="background: transparent; border: 2px solid white; color: white;">Visite virtuelle</a>
                </div>
            </div>
        </section>

        <!-- Mission et Vision -->
        <section class="container" style="padding: 4rem 0;">
            <div class="grid grid-2">
                <div class="card">
                    <h2>🎯 Notre Mission</h2>
                    <p>Former la prochaine génération de talents numériques haïtiens through des programmes éducatifs innovants et des expériences pratiques en présentiel.</p>
                    <ul>
                        <li>Formations pratiques et intensives</li>
                        <li>Accès à des équipements de pointe</li>
                        <li>Réseau professionnel actif</li>
                        <li>Opportunités de carrière</li>
                    </ul>
                </div>
                
                <div class="card">
                    <h2>👁️ Notre Vision</h2>
                    <p>Devenir le centre d'excellence technologique de référence en Haïti, catalyseur de l'innovation et de la transformation digitale du pays.</p>
                    <ul>
                        <li>Centre d'innovation technologique</li>
                        <li>Hub de la communauté tech haïtienne</li>
                        <li>Partenaire des entreprises locales</li>
                        <li>Influenceur de l'écosystème digital</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Formations en présentiel -->
        <section id="formations" style="background-color: var(--couleur-fond); padding: 4rem 0;">
            <div class="container">
                <h2 style="text-align: center; margin-bottom: 3rem;">Formations en Présentiel</h2>
                <div class="grid grid-3">
                    <div class="card">
                        <h3>💻 Développement Web</h3>
                        <p>Formation complète en développement web full-stack</p>
                        <ul>
                            <li>HTML, CSS, JavaScript</li>
                            <li>React, Node.js</li>
                            <li>Bases de données</li>
                            <li>Projet final</li>
                        </ul>
                        <div style="margin-top: 1rem;">
                            <span class="badge badge-info">12 semaines</span>
                            <span class="badge badge-success">Certifiante</span>
                        </div>
                    </div>
                    
                    <div class="card">
                        <h3>📱 Développement Mobile</h3>
                        <p>Création d'applications mobiles natives et hybrides</p>
                        <ul>
                            <li>Android (Kotlin)</li>
                            <li>iOS (Swift)</li>
                            <li>React Native</li>
                            <li>Publication d'apps</li>
                        </ul>
                        <div style="margin-top: 1rem;">
                            <span class="badge badge-info">10 semaines</span>
                            <span class="badge badge-success">Certifiante</span>
                        </div>
                    </div>
                    
                    <div class="card">
                        <h3>🎨 Design UI/UX</h3>
                        <p>Conception d'interfaces utilisateur et expériences digitales</p>
                        <ul>
                            <li>Design thinking</li>
                            <li>Figma, Adobe XD</li>
                            <li>Prototypage</li>
                            <li>Tests utilisateurs</li>
                        </ul>
                        <div style="margin-top: 1rem;">
                            <span class="badge badge-info">8 semaines</span>
                            <span class="badge badge-success">Certifiante</span>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="formations.php" class="btn btn-primary">Voir toutes les formations</a>
                </div>
            </div>
        </section>

        <!-- Événements à venir -->
        <section id="evenements" class="container" style="padding: 4rem 0;">
            <h2 style="text-align: center; margin-bottom: 3rem;">Événements à Venir</h2>
            <div class="grid grid-2">
                <div class="card">
                    <h3>🚀 Hackathon National</h3>
                    <p><strong>Date:</strong> 15-16 Novembre 2024</p>
                    <p><strong>Lieu:</strong> Campus ADH Académie</p>
                    <p>48 heures de développement intensif pour résoudre des défis locaux. Prix à gagner et opportunités de recrutement.</p>
                    <a href="evenement.php?id=1" class="btn btn-outline">S'inscrire</a>
                </div>
                
                <div class="card">
                    <h3>💼 Job Fair Tech</h3>
                    <p><strong>Date:</strong> 30 Novembre 2024</p>
                    <p><strong>Lieu:</strong> Campus ADH Académie</p>
                    <p>Rencontrez les entreprises tech locales et internationales. Postulez à des offres d'emploi et stages.</p>
                    <a href="evenement.php?id=2" class="btn btn-outline">S'inscrire</a>
                </div>
            </div>
        </section>

        <!-- Infrastructures -->
        <section style="background-color: var(--couleur-fond); padding: 4rem 0;">
            <div class="container">
                <h2 style="text-align: center; margin-bottom: 3rem;">Nos Infrastructures</h2>
                <div class="grid grid-3">
                    <div class="card" style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">💻</div>
                        <h3>Salles équipées</h3>
                        <p>Ordinateurs performants, écrans multiples, connexion haut débit</p>
                    </div>
                    
                    <div class="card" style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">🔧</div>
                        <h3>Labo technologique</h3>
                        <p>Équipements IoT, robots, imprimantes 3D, réalité virtuelle</p>
                    </div>
                    
                    <div class="card" style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">☕</div>
                        <h3>Espace détente</h3>
                        <p>Zones de coworking, cafétéria, espaces verts</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="container" style="padding: 4rem 0; text-align: center;">
            <div class="card" style="background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-secondaire)); color: white;">
                <h2 style="margin-bottom: 1rem;">Prêt à transformer votre avenir ?</h2>
                <p style="margin-bottom: 2rem; opacity: 0.9;">Rejoignez notre communauté et développez vos compétences dans un environnement stimulant.</p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="contact.php" class="btn" style="background: white; color: var(--couleur-primaire);">Nous contacter</a>
                    <a href="visite-virtuelle.php" class="btn" style="background: transparent; border: 2px solid white; color: white;">Visite virtuelle</a>
                </div>
            </div>
        </section>
    </main>

    <script src="js/script.js"></script>
</body>
</html>