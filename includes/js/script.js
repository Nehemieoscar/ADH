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
        
        // Mettre à jour le bouton toggle
        const toggleBtn = document.querySelector('.theme-toggle');
        if (toggleBtn) {
            toggleBtn.innerHTML = this.theme === 'sombre' ? '☀️' : '🌙';
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

// Gestion des formulaires
class FormManager {
    static showAlert(message, type = 'success') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;
        
        document.body.insertBefore(alertDiv, document.body.firstChild);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }

    static validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    static validatePassword(password) {
        return password.length >= 6;
    }
}

// Gestion de l'interface utilisateur
class UIManager {
    static toggleMenu() {
        const menu = document.querySelector('.nav-menu');
        if (menu) {
            menu.classList.toggle('active');
        }
    }

    static showLoading() {
        // Implémentation du loading
    }

    static hideLoading() {
        // Implémentation du loading
    }
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le gestionnaire de thème
    new ThemeManager();

    // Gestion du menu mobile
    const menuToggle = document.querySelector('.menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', UIManager.toggleMenu);
    }

     // Gestion des formulaires de connexion/inscription
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            try {
                // validation côté client (ne pas empêcher la soumission par défaut
                // sauf en cas d'erreur de validation)
                const email = this.querySelector('#email').value;
                const password = this.querySelector('#mot_de_passe').value;

                if (!FormManager.validateEmail(email)) {
                    e.preventDefault();
                    FormManager.showAlert('Veuillez entrer un email valide', 'error');
                    return;
                }

                if (!FormManager.validatePassword(password)) {
                    e.preventDefault();
                    FormManager.showAlert('Le mot de passe doit contenir au moins 6 caractères', 'error');
                    return;
                }

                // si tout est ok, laisser le formulaire se soumettre normalement
            } catch (err) {
                console.error('Erreur dans loginForm submit handler :', err);
                // en cas d'erreur JS, ne pas bloquer la soumission côté serveur
                // (utile si PHP doit gérer la validation)
            }
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            try {
                // validation côté client
                const password = this.querySelector('#mot_de_passe').value;
                const confirmPassword = this.querySelector('#confirmation_mot_de_passe').value;

                if (password !== confirmPassword) {
                    e.preventDefault();
                    FormManager.showAlert('Les mots de passe ne correspondent pas', 'error');
                    return;
                }

                if (!FormManager.validatePassword(password)) {
                    e.preventDefault();
                    FormManager.showAlert('Le mot de passe doit contenir au moins 6 caractères', 'error');
                    return;
                }

                // si tout est ok, laisser le formulaire se soumettre normalement
            } catch (err) {
                console.error('Erreur dans registerForm submit handler :', err);
                // ne pas bloquer la soumission si JS plante
            }
        });
    }
});

// Fonctions utilitaires
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('fr-FR', options);
}