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
}

// Mise à jour du gestionnaire de thème pour utiliser des icônes
class EnhancedThemeManager extends ThemeManager {
    applyTheme() {
        document.documentElement.setAttribute('data-theme', this.theme);
        localStorage.setItem('theme', this.theme);
        
        // Mettre à jour le bouton toggle avec des icônes
        const toggleBtn = document.querySelector('.theme-toggle');
        if (toggleBtn) {
            if (this.theme === 'sombre') {
                toggleBtn.innerHTML = '<i class="icon-sun"></i>';
                toggleBtn.setAttribute('aria-label', 'Activer le mode clair');
            } else {
                toggleBtn.innerHTML = '<i class="icon-moon"></i>';
                toggleBtn.setAttribute('aria-label', 'Activer le mode sombre');
            }
        }
    }
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    new EnhancedThemeManager();
    new HeaderManager();
});