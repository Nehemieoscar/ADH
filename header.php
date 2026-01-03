<?php
// Inclure les fichiers d'authentification et de configuration
include_once 'auth.php';
include_once 'config.php';
?>
<!DOCTYPE html>
<html lang="fr" data-theme="clair">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADH - Votre Plateforme d'Apprentissage</title>
    
    <!-- Meta données pour le SEO -->
    <meta name="description" content="ADH - Plateforme d'apprentissage en ligne avec cours, formations et communauté">
    <meta name="keywords" content="cours, formations, e-learning, éducation, ADH">
    
    <!-- Favicon -->
    <link rel="icon" href="assets/favicon.ico" type="image/x-icon">
    
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Police Google Fonts pour le style luxe -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Variables CSS pour les couleurs et le mode sombre */
        :root {
            --couleur-primaire: #0052b4; /* Bleu */
            --couleur-secondaire: #d21034; /* Rouge */
            --couleur-blanc: #ffffff;
            --couleur-noir: #000000;
            --couleur-fond: #f5f5f5;
            --couleur-texte: #333333;
            --couleur-border: #dddddd;
            --couleur-success: #28a745;
            --couleur-warning: #ffc107;
            --couleur-danger: #dc3545;
            --ombre-legere: 0 2px 5px rgba(0,0,0,0.1);
            --ombre-moyenne: 0 4px 10px rgba(0,0,0,0.15);
            
            /* Polices */
            --font-primary: 'Inter', sans-serif;
            --font-heading: 'Playfair Display', serif;
        }

        /* Mode sombre */
        [data-theme="sombre"] {
            --couleur-fond: #1a1a1a;
            --couleur-texte: #ffffff;
            --couleur-border: #444444;
        }

        /* Reset et styles de base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-primary);
            background-color: var(--couleur-fond);
            color: var(--couleur-texte);
            transition: background-color 0.3s, color 0.3s;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Styles pour le header réutilisable */

        /* Barre supérieure */
        .top-bar {
            background-color: var(--couleur-noir);
            color: var(--couleur-blanc);
            padding: 0.5rem 0;
            font-size: 0.9rem;
        }

        .top-bar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .contact-info {
            display: flex;
            gap: 1.5rem;
        }

        .contact-link {
            color: var(--couleur-blanc);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s;
        }

        .contact-link:hover {
            color: var(--couleur-secondaire);
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-links a {
            color: var(--couleur-blanc);
            text-decoration: none;
            transition: color 0.3s;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }

        .social-links a:hover {
            color: var(--couleur-secondaire);
            background: rgba(255, 255, 255, 0.2);
        }

        /* Barre de navigation */
        .navbar {
            background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-secondaire));
            color: var(--couleur-blanc);
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: var(--ombre-moyenne);
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Logo */
        .nav-logo {
            font-size: 1.8rem;
            font-weight: bold;
        }

        .logo-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: var(--couleur-blanc);
        }

        .logo-img {
            height: 48px;
            max-height: 64px;
            width: auto;
            display: block;
            object-fit: contain;
        }

        .logo-text {
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 1.5rem;
        }

        /* Menu principal */
        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            color: var(--couleur-blanc);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Menu déroulant */
        .dropdown {
            position: relative;
        }

        .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .dropdown-icon {
            font-size: 0.8rem;
            transition: transform 0.3s;
        }

        .dropdown:hover .dropdown-icon {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background-color: var(--couleur-blanc);
            min-width: 220px;
            box-shadow: var(--ombre-moyenne);
            border-radius: 5px;
            padding: 0.5rem 0;
            list-style: none;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-link {
            display: block;
            padding: 0.75rem 1.5rem;
            color: var(--couleur-texte);
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .dropdown-link:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        /* Actions utilisateur */
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Recherche */
        .search-container {
            position: relative;
        }

        .search-form {
            display: flex;
            align-items: center;
        }

        .search-input {
            padding: 0.5rem 1rem;
            border: 1px solid var(--couleur-border);
            border-radius: 25px 0 0 25px;
            outline: none;
            width: 0;
            opacity: 0;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
            color: var(--couleur-blanc);
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-container.active .search-input {
            width: 200px;
            opacity: 1;
        }

        .search-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 0 25px 25px 0;
            padding: 0.5rem 0.75rem;
            color: var(--couleur-blanc);
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .search-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Bouton thème */
        .theme-toggle {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--couleur-blanc);
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Boutons */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
            font-weight: 600;
            font-family: var(--font-primary);
        }

        .btn-primary {
            background-color: var(--couleur-blanc);
            color: var(--couleur-primaire);
        }

        .btn-primary:hover {
            background-color: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--couleur-secondaire);
            color: var(--couleur-blanc);
        }

        .btn-secondary:hover {
            background-color: #a80d2a;
            transform: translateY(-2px);
        }

        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--couleur-blanc);
            color: var(--couleur-blanc);
        }

        .btn-outline:hover {
            background-color: var(--couleur-blanc);
            color: var(--couleur-primaire);
        }

        /* Menu hamburger */
        .hamburger {
            display: none;
            flex-direction: column;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            gap: 4px;
        }

        .hamburger span {
            display: block;
            width: 25px;
            height: 2px;
            background-color: var(--couleur-blanc);
            transition: all 0.3s ease;
        }

        /* Styles pour le contenu principal */
        .main-content {
            margin-top: 120px; /* Compense la navbar fixe */
            min-height: calc(100vh - 200px);
        }

        /* Footer */
        .footer {
            background-color: var(--couleur-primaire);
            color: var(--couleur-blanc);
            padding: 2rem 0;
            text-align: center;
            margin-top: 4rem;
        }

        /* Styles pour l'authentification */
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-welcome {
            color: var(--couleur-blanc);
            font-weight: 500;
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
        }

        /* Styles responsifs */
        @media (max-width: 1024px) {
            .nav-menu {
                gap: 1rem;
            }
            
            .search-container.active .search-input {
                width: 150px;
            }
            
            .nav-actions {
                gap: 0.75rem;
            }
        }

        @media (max-width: 768px) {
            .top-bar {
                display: none;
            }
            
            .hamburger {
                display: flex;
            }
            
            .nav-menu {
                position: fixed;
                top: 100%;
                left: 0;
                width: 100%;
                background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-secondaire));
                flex-direction: column;
                padding: 2rem;
                transform: translateY(-100%);
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
                z-index: 999;
                gap: 0;
            }
            
            .nav-menu.active {
                transform: translateY(0);
                opacity: 1;
                visibility: visible;
            }
            
            .nav-link {
                padding: 1rem;
                width: 100%;
                justify-content: space-between;
            }
            
            .dropdown-menu {
                position: static;
                opacity: 1;
                visibility: visible;
                transform: none;
                box-shadow: none;
                background: transparent;
                padding-left: 1rem;
                width: 100%;
            }
            
            .dropdown-link {
                color: var(--couleur-blanc);
                padding: 0.75rem 1rem;
            }
            
            .dropdown-link:hover {
                background-color: rgba(255, 255, 255, 0.1);
            }
            
            .search-container {
                order: -1;
                width: 100%;
                margin-bottom: 1rem;
            }
            
            .search-container.active .search-input {
                width: 100%;
            }
            
            .nav-actions {
                flex-direction: column;
                width: 100%;
                gap: 1rem;
                order: 1;
            }
            
            .hamburger.active span:nth-child(1) {
                transform: rotate(45deg) translate(6px, 6px);
            }
            
            .hamburger.active span:nth-child(2) {
                opacity: 0;
            }
            
            .hamburger.active span:nth-child(3) {
                transform: rotate(-45deg) translate(6px, -6px);
            }
            
            .main-content {
                margin-top: 80px;
            }

            .user-menu {
                flex-direction: column;
                width: 100%;
            }
            
            .auth-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .user-welcome {
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .nav-container {
                padding: 0 15px;
            }
            
            .logo-text {
                display: none;
            }
            
            .btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }
        }

        /* Mode sombre */
        [data-theme="sombre"] .top-bar {
            background-color: #111;
        }

        [data-theme="sombre"] .dropdown-menu {
            background-color: #2a2a2a;
            border: 1px solid #444;
        }

        [data-theme="sombre"] .dropdown-link {
            color: var(--couleur-blanc);
        }

        [data-theme="sombre"] .dropdown-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="sombre"] .search-input {
            background-color: #2a2a2a;
            color: var(--couleur-blanc);
            border-color: #444;
        }
    </style>
</head>
<body>
    <!-- Header Réutilisable -->
    <header class="header">
        <!-- Barre de contact et informations -->
        <div class="top-bar">
            <div class="container">
                <div class="top-bar-content">
                    <div class="contact-info">
                        <a href="tel:+33123456789" class="contact-link">
                            <i class="fas fa-phone"></i>
                            <span>+33 1 23 45 67 89</span>
                        </a>
                        <a href="mailto:contact@adh.fr" class="contact-link">
                            <i class="fas fa-envelope"></i>
                            <span>contact@adh.fr</span>
                        </a>
                    </div>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" aria-label="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barre de navigation principale -->
        <nav class="navbar">
            <div class="nav-container">
                <!-- Logo -->
                <div class="nav-logo">
                    <a href="index.php" class="logo-link">
                        <img src="assets/logo.jpg" alt="Logo ADH" class="logo-img">
                        <!-- <span class="logo-text">ADH</span> -->
                    </a>
                </div>

                <!-- Menu principal -->
                <ul class="nav-menu">
                    <!-- <li><a href="index.php" class="nav-link">Accueil</a></li> -->
                    
                    <!-- Menu déroulant "Qui sommes-nous" -->
                    <li class="dropdown">
                        <a href="#" class="nav-link dropdown-toggle">
                            Qui sommes-nous
                            <i class="fas fa-chevron-down dropdown-icon"></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="about.php" class="dropdown-link">À propos</a></li>
                            <li><a href="mission.php" class="dropdown-link">Notre mission</a></li>
                            <li><a href="equipe.php" class="dropdown-link">Notre équipe</a></li>
                            <li><a href="valeurs.php" class="dropdown-link">Nos valeurs</a></li>
                            <li><a href="partenaires.php" class="dropdown-link">Partenaires</a></li>
                        </ul>
                    </li>
                    
                    <!-- <li><a href="cours.php" class="nav-link">Cours</a></li> -->
                    <li><a href="formations.php" class="nav-link">Formations</a></li>
                    <li><a href="forum.php" class="nav-link">Forum</a></li>
                    <li><a href="coworking.php" class="nav-link">Coworking</a></li>
                    <!-- <li><a href="quiz.php" class="nav-link">Quiz</a></li>
                    <li><a href="agenda.php" class="nav-link">Agenda</a></li> -->
                    
                    <!-- Bouton Contact qui dirige vers la section contact -->
                    <li><a href="#contact" class="nav-link contact-btn">Contact</a></li>
                </ul>

                <!-- Actions utilisateur -->
                <div class="nav-actions">
                    <!-- Champ de recherche -->
                    <div class="search-container">
                        <form class="search-form" action="recherche.php" method="GET">
                            <input type="text" name="q" class="search-input" placeholder="Rechercher..." aria-label="Rechercher">
                            <button type="submit" class="search-btn" aria-label="Lancer la recherche">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Bouton mode sombre/clair -->
                    <button class="theme-toggle" aria-label="Changer le thème">
                        <i class="fas fa-moon"></i>
                    </button>

                    <!-- Actions de connexion/inscription ou tableau de bord -->
                    <?php if (est_connecte()): 
                        $utilisateur = obtenir_utilisateur_connecte();
                    ?>
                        <div class="user-menu">
                            <span class="user-welcome">Bonjour, <?php 
    if (isset($utilisateur['prenom']) && !empty($utilisateur['prenom'])) {
        echo htmlspecialchars($utilisateur['prenom']);
    } else {
        echo 'Utilisateur';
    }
?></span>
                            <a href="dashboard/dashboard.php" class="btn btn-outline">Tableau de bord</a>
                            <a href="logout.php" class="btn btn-secondary">Déconnexion</a>
                        </div>
                    <?php else: ?>
                        <div class="auth-buttons">
                            <a href="login.php" class="btn btn-outline">Connexion</a>
                            <a href="register.php" class="btn btn-primary">Inscription</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Menu hamburger pour mobile -->
                <button class="hamburger" aria-label="Ouvrir le menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </nav>
    </header>

    <!-- Contenu Principal de la Page -->
    <main class="main-content">

    <script>
        // Gestion du mode sombre/clair
        class ThemeManager {
            constructor() {
                this.theme = localStorage.getItem('theme') || 'clair';
                this.init();
            }

            init() {
                this.applyTheme();
                this.setupEventListeners();
            }

            applyTheme() {
                document.documentElement.setAttribute('data-theme', this.theme);
                localStorage.setItem('theme', this.theme);
                
                // Mettre à jour le bouton toggle avec des icônes
                const toggleBtn = document.querySelector('.theme-toggle');
                if (toggleBtn) {
                    if (this.theme === 'sombre') {
                        toggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
                        toggleBtn.setAttribute('aria-label', 'Activer le mode clair');
                    } else {
                        toggleBtn.innerHTML = '<i class="fas fa-moon"></i>';
                        toggleBtn.setAttribute('aria-label', 'Activer le mode sombre');
                    }
                }
            }

            toggleTheme() {
                this.theme = this.theme === 'clair' ? 'sombre' : 'clair';
                this.applyTheme();
            }

            setupEventListeners() {
                const toggleBtn = document.querySelector('.theme-toggle');
                if (toggleBtn) {
                    toggleBtn.addEventListener('click', () => this.toggleTheme());
                }
            }
        }

        // Gestion du menu mobile et autres interactions
        class HeaderManager {
            constructor() {
                this.init();
            }

            init() {
                this.setupMobileMenu();
                this.setupSearchToggle();
                this.setupSmoothScroll();
                this.setupDropdowns();
                this.setupClickOutside();
            }

            // Menu mobile
            setupMobileMenu() {
                const hamburger = document.querySelector('.hamburger');
                const navMenu = document.querySelector('.nav-menu');
                const navActions = document.querySelector('.nav-actions');

                if (hamburger && navMenu) {
                    hamburger.addEventListener('click', () => {
                        hamburger.classList.toggle('active');
                        navMenu.classList.toggle('active');
                        
                        // Sur mobile, réorganiser les éléments
                        if (window.innerWidth <= 768) {
                            if (navMenu.classList.contains('active')) {
                                navMenu.appendChild(navActions);
                            } else {
                                document.querySelector('.nav-container').appendChild(navActions);
                            }
                        }
                    });
                }

                // Fermer le menu en cliquant sur un lien
                const navLinks = document.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        if (window.innerWidth <= 768) {
                            hamburger.classList.remove('active');
                            navMenu.classList.remove('active');
                        }
                    });
                });
            }

            // Recherche
            setupSearchToggle() {
                const searchBtn = document.querySelector('.search-btn');
                const searchContainer = document.querySelector('.search-container');

                if (searchBtn && searchContainer) {
                    searchBtn.addEventListener('click', (e) => {
                        // Sur desktop, on veut juste activer/désactiver le champ
                        if (window.innerWidth > 768) {
                            e.preventDefault();
                            searchContainer.classList.toggle('active');
                            
                            // Focus sur le champ quand il s'active
                            if (searchContainer.classList.contains('active')) {
                                const searchInput = searchContainer.querySelector('.search-input');
                                searchInput.focus();
                            }
                        }
                        // Sur mobile, le formulaire se soumet normalement
                    });
                }

                // Fermer la recherche en cliquant ailleurs
                document.addEventListener('click', (e) => {
                    if (!searchContainer.contains(e.target) && searchContainer.classList.contains('active')) {
                        searchContainer.classList.remove('active');
                    }
                });
            }

            // Défilement fluide pour le bouton Contact
            setupSmoothScroll() {
                const contactBtn = document.querySelector('.contact-btn');
                
                if (contactBtn) {
                    contactBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        
                        const targetId = contactBtn.getAttribute('href');
                        const targetElement = document.querySelector(targetId);
                        
                        if (targetElement) {
                            // Fermer le menu mobile si ouvert
                            if (window.innerWidth <= 768) {
                                const hamburger = document.querySelector('.hamburger');
                                const navMenu = document.querySelector('.nav-menu');
                                hamburger.classList.remove('active');
                                navMenu.classList.remove('active');
                            }
                            
                            targetElement.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    });
                }
            }

            // Gestion des dropdowns sur mobile
            setupDropdowns() {
                const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
                
                dropdownToggles.forEach(toggle => {
                    toggle.addEventListener('click', (e) => {
                        // Sur mobile, on veut pouvoir ouvrir/fermer les dropdowns
                        if (window.innerWidth <= 768) {
                            e.preventDefault();
                            const dropdown = toggle.closest('.dropdown');
                            dropdown.classList.toggle('active');
                        }
                    });
                });
            }

            // Fermer les menus en cliquant à l'extérieur
            setupClickOutside() {
                document.addEventListener('click', (e) => {
                    // Fermer les dropdowns desktop
                    if (window.innerWidth > 768) {
                        const dropdowns = document.querySelectorAll('.dropdown');
                        dropdowns.forEach(dropdown => {
                            if (!dropdown.contains(e.target)) {
                                dropdown.classList.remove('active');
                            }
                        });
                    }
                    
                    // Fermer la recherche
                    const searchContainer = document.querySelector('.search-container');
                    if (searchContainer && !searchContainer.contains(e.target)) {
                        searchContainer.classList.remove('active');
                    }
                });
            }
        }

        // Initialisation au chargement de la page
        document.addEventListener('DOMContentLoaded', () => {
            new ThemeManager();
            new HeaderManager();
            
            console.log('ADH Platform loaded successfully');
        });
    </script>