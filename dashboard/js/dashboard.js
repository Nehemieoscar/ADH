// Dashboard JavaScript (Version Admin Haut de Gamme) - VERSION CORRIGÉE FINALE

// ===================================================================
// TOUT LE CODE EST ENCAPSULÉ DANS DOMContentLoaded
// ===================================================================

document.addEventListener('DOMContentLoaded', function() {
    
    console.log('✅ Dashboard JS initialisé - DOM complètement chargé');

    // --- UTILITAIRES ---
    
    /**
     * Fonction de debouncing pour limiter les appels de fonctions coûteuses
     */
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

    /**
     * Fonctions d'accessibilité
     */
    function announceToScreenReader(message) {
        const announcer = document.getElementById('aria-announcer') || createAriaAnnouncer();
        announcer.textContent = message;
    }

    function createAriaAnnouncer() {
        const announcer = document.createElement('div');
        announcer.id = 'aria-announcer';
        announcer.setAttribute('aria-live', 'polite');
        announcer.setAttribute('aria-atomic', 'true');
        announcer.classList.add('sr-only');
        document.body.appendChild(announcer);
        return announcer;
    }

    // --- 1. GESTION DES NAVIGATIONS / TRANSITIONS (CORRIGÉ) ---
    
    const navLinks = document.querySelectorAll('.sidebar nav a');
    const sections = document.querySelectorAll('.admin-section');
    
    console.log(`🔗 Liens de navigation trouvés: ${navLinks.length}`);
    console.log(`📄 Sections trouvées: ${sections.length}`);
    
    function navigateToSection(targetId) {
        console.log(`🎯 Navigation vers: ${targetId}`);
        
        // Masquer toutes les sections
        sections.forEach(section => {
            section.classList.remove('active');
            section.style.display = 'none';
        });

        // Afficher la section cible
        const targetSection = document.getElementById(targetId);
        if (targetSection) {
            targetSection.style.display = 'block'; 
            // Forcer le reflow pour que l'animation fonctionne
            targetSection.offsetHeight; 
            targetSection.classList.add('active');

            const label = targetSection.getAttribute('aria-label') || 
                         targetSection.querySelector('h1, h2, h3')?.textContent || 
                         targetId;
            announceToScreenReader(`Section affichée : ${label}`);
            
            console.log(`✅ Section ${targetId} affichée avec succès`);
        } else {
            console.error(`❌ Section introuvable: ${targetId}`);
        }
    }

    // Gestionnaire de clics sur les liens de navigation
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            console.log('🖱️ Clic sur lien de navigation');
            
            // Retirer la classe active de tous les liens
            navLinks.forEach(l => l.classList.remove('active'));
            // Ajouter la classe active au lien cliqué
            this.classList.add('active');
            
            const targetId = this.getAttribute('data-target');
            if (targetId) {
                navigateToSection(targetId);
            } else {
                console.warn('⚠️ Aucun data-target défini pour ce lien');
            }
        });
    });
    
    // Afficher la première section au chargement
    console.log('🚀 Initialisation de la première section...');
    if (navLinks.length > 0 && sections.length > 0) {
        const firstLink = navLinks[0];
        firstLink.classList.add('active');
        const firstTarget = firstLink.getAttribute('data-target');
        
        if (firstTarget) {
            console.log(`📍 Première section à afficher: ${firstTarget}`);
            navigateToSection(firstTarget);
        } else {
            // Fallback: afficher la première section disponible
            console.warn('⚠️ Pas de data-target, affichage de la première section disponible');
            if (sections[0]) {
                sections[0].style.display = 'block';
                sections[0].classList.add('active');
            }
        }
    } else {
        console.error('❌ ERREUR CRITIQUE: Aucun lien de navigation ou section trouvé!');
        // Afficher toutes les sections par défaut en cas d'erreur
        sections.forEach(section => {
            section.style.display = 'block';
        });
    }

    // --- 2. COMPTEURS ANIMÉS ---
    
    function animateCount(element, finalValue) {
        const start = 0;
        const duration = 1500;
        let startTimestamp = null;
        
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            element.textContent = Math.floor(progress * finalValue).toLocaleString('fr-FR');
            
            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                const label = element.getAttribute('aria-label') || 'Compteur';
                announceToScreenReader(`${label} : ${finalValue}`);
            }
        };
        window.requestAnimationFrame(step);
    }
    
    const statElements = document.querySelectorAll('.stat-value[data-count]');
    console.log(`📊 ${statElements.length} compteurs trouvés`);
    
    statElements.forEach(stat => {
        const finalValue = parseInt(stat.getAttribute('data-count'), 10);
        if (!isNaN(finalValue)) {
            animateCount(stat, finalValue);
        }
    });

    // --- 3. LOGIQUE DRAG & DROP ---
    
    const dnd = {
        dragged: null,
        placeholder: null,
        
        init: function() {
            const containers = document.querySelectorAll('.draggable-container');
            console.log(`🎯 ${containers.length} conteneurs drag&drop initialisés`);
            
            containers.forEach(container => {
                container.addEventListener('dragstart', this.handleDragStart.bind(this));
                container.addEventListener('dragover', this.handleDragOver.bind(this));
                container.addEventListener('drop', this.handleDrop.bind(this));
                container.addEventListener('dragend', this.handleDragEnd.bind(this));
            });
        },
        
        handleDragStart: function(e) {
            if (!e.target.classList.contains('draggable-item')) return;
            this.dragged = e.target;
            e.dataTransfer.effectAllowed = 'move';
            
            this.placeholder = document.createElement('div');
            this.placeholder.classList.add('drag-placeholder');
            this.placeholder.style.height = this.dragged.offsetHeight + 'px';
            
            setTimeout(() => {
                if (this.dragged) {
                    this.dragged.style.opacity = '0.4';
                    this.dragged.parentNode.insertBefore(this.placeholder, this.dragged.nextSibling);
                    const label = this.dragged.getAttribute('aria-label') || 'Élément';
                    announceToScreenReader(`${label} : mode déplacement activé`);
                }
            }, 0);
        },
        
        handleDragOver: function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            if (!this.dragged || !this.placeholder) return;
            
            const target = e.target.closest('.draggable-item') || e.target.closest('.calendar-day');
            
            if (target && target !== this.dragged) {
                const rect = target.getBoundingClientRect();
                const isAfter = e.clientY > rect.top + rect.height / 2;
                
                if (target.classList.contains('draggable-item')) {
                    if (isAfter) {
                        target.parentNode.insertBefore(this.placeholder, target.nextSibling);
                    } else {
                        target.parentNode.insertBefore(this.placeholder, target);
                    }
                } else if (target.classList.contains('calendar-day')) {
                    target.appendChild(this.placeholder);
                }
            } else if (e.target.classList.contains('calendar-day') && !e.target.querySelector('.drag-placeholder')) {
                e.target.appendChild(this.placeholder);
            }
        },
        
        handleDrop: function(e) {
            e.preventDefault();
            if (!this.dragged || !this.placeholder) return;

            this.placeholder.parentNode.insertBefore(this.dragged, this.placeholder);
            
            const newOrder = Array.from(this.dragged.parentNode.children)
                .filter(el => el.classList.contains('draggable-item'))
                .map(el => el.id);
            const isTaskMove = this.dragged.classList.contains('task-item');
            
            if (isTaskMove) {
                const newDate = this.dragged.closest('.calendar-day')?.getAttribute('data-date');
                console.log(`📅 AJAX: Tâche ${this.dragged.id} déplacée au ${newDate}`);
                announceToScreenReader(`Tâche déplacée au ${newDate}`);
            } else {
                console.log(`📋 AJAX: Réorganisation des modules :`, newOrder);
                announceToScreenReader('Réorganisation des modules enregistrée');
            }

            this.dragged.classList.add('btn-success-feedback');
            setTimeout(() => {
                if (this.dragged) {
                    this.dragged.classList.remove('btn-success-feedback');
                }
            }, 400); 
        },
        
        handleDragEnd: function() {
            if (this.dragged) {
                this.dragged.style.opacity = '1';
                const label = this.dragged.getAttribute('aria-label') || 'Élément';
                announceToScreenReader(`${label} : déplacement terminé`);
            }
            if (this.placeholder && this.placeholder.parentNode) {
                this.placeholder.parentNode.removeChild(this.placeholder);
            }
            this.dragged = null;
            this.placeholder = null;
        }
    };

    dnd.init();

    // --- 4. RECHERCHE INTELLIGENTE (AVEC FILTRES) ---
    
    const smartSearchHandler = debounce(function() {
        const query = document.getElementById('main-search-input')?.value || '';
        const filterRole = document.getElementById('filter-role')?.value || '';
        const filterStatus = document.getElementById('filter-status')?.value || '';
        const filterCourse = document.getElementById('filter-course')?.value || '';
        
        console.log(`🔍 AJAX: Recherche | Query:'${query}', Rôle:'${filterRole}', Statut:'${filterStatus}', Cours:'${filterCourse}'`);
        announceToScreenReader('Recherche lancée');
    }, 300); 

    const searchInput = document.getElementById('main-search-input');
    const filterRole = document.getElementById('filter-role');
    const filterStatus = document.getElementById('filter-status');
    const filterCourse = document.getElementById('filter-course');

    if (searchInput) {
        searchInput.addEventListener('input', smartSearchHandler);
        console.log('✅ Recherche intelligente initialisée');
    }
    if (filterRole) filterRole.addEventListener('change', smartSearchHandler);
    if (filterStatus) filterStatus.addEventListener('change', smartSearchHandler);
    if (filterCourse) filterCourse.addEventListener('change', smartSearchHandler);

    // --- 5. TABLEAU DE BORD PRÉDICTIF (AI) ---
    
    const aiButton = document.getElementById('run-ai-analysis-btn');
    if (aiButton) {
        aiButton.addEventListener('click', function() {
            this.textContent = 'Analyse en cours...';
            this.disabled = true;
            announceToScreenReader('Analyse prédictive en cours');
            
            setTimeout(() => {
                this.textContent = 'Suggestions Mises à Jour';
                this.disabled = false;
                
                const suggestionsCard = document.getElementById('ai-suggestions-card');
                if (suggestionsCard) {
                    suggestionsCard.innerHTML = `
                        <div class="card" style="margin-top: 1rem; border: 2px solid var(--warning-color);">
                            <i class="fas fa-chart-line" style="color: var(--warning-color);"></i> 
                            <strong>Étudiant à risque:</strong> 5 utilisateurs inactifs depuis > 7 jours. 
                            <button class="btn btn-sm btn-primary ml-3" data-tooltip="Envoyer un email de relance personnalisé">
                                Envoyer Rappel Auto
                            </button>
                        </div>
                        <div class="card" style="margin-top: 1rem; border: 2px solid var(--success-color);">
                            <i class="fas fa-check-circle" style="color: var(--success-color);"></i> 
                            <strong>Automatisation succès:</strong> 25 félicitations automatiques envoyées aujourd'hui.
                        </div>
                        <button class="btn btn-primary" style="margin-top: 1rem;" disabled>Suggestions Mises à Jour</button>
                    `;
                }
                announceToScreenReader('Analyse terminée. Suggestions mises à jour.');
                console.log('🤖 Analyse AI terminée');
            }, 2000);
        });0
        console.log('✅ Bouton AI initialisé');
    }
    
    // --- 6. TOOLTIPS INTELLIGENTS (VERSION FINALE CORRIGÉE) ---
    
    let activeTooltipTarget = null;
    let tooltip = null;

    // Créer le tooltip une seule fois au chargement
    function createTooltip() {
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = 'smart-tooltip';
            tooltip.classList.add('smart-tooltip-style');
            tooltip.setAttribute('role', 'tooltip');
            tooltip.setAttribute('aria-hidden', 'true');
            tooltip.style.cssText = `
                position: fixed;
                background: rgba(0, 0, 0, 0.9);
                color: white;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 0.85rem;
                pointer-events: none;
                z-index: 10000;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.2s ease, visibility 0.2s ease;
                white-space: nowrap;
                max-width: 300px;
            `;
            document.body.appendChild(tooltip);
            console.log('✅ Tooltip créé');
        }
        return tooltip;
    }

    // Fonction pour afficher le tooltip
    function showTooltip(target) {
        const tooltipText = target.getAttribute('data-tooltip');
        if (!tooltipText) return;

        const tooltipEl = createTooltip();
        tooltipEl.textContent = tooltipText;

        const rect = target.getBoundingClientRect();
        const tooltipRect = tooltipEl.getBoundingClientRect();
        
        // Position au-dessus de l'élément
        let left = rect.left + (rect.width / 2);
        let top = rect.top - 10;

        tooltipEl.style.left = left + 'px';
        tooltipEl.style.top = top + 'px';
        tooltipEl.style.transform = 'translate(-50%, -100%)';
        
        // Afficher avec animation
        requestAnimationFrame(() => {
            tooltipEl.style.opacity = '1';
            tooltipEl.style.visibility = 'visible';
            tooltipEl.setAttribute('aria-hidden', 'false');
        });

        announceToScreenReader(tooltipText);
        activeTooltipTarget = target;
    }

    // Fonction pour masquer le tooltip
    function hideTooltip() {
        if (tooltip) {
            tooltip.style.opacity = '0';
            tooltip.style.visibility = 'hidden';
            tooltip.setAttribute('aria-hidden', 'true');
        }
        activeTooltipTarget = null;
    }

    // Gestionnaire d'événements délégué pour les tooltips
    document.addEventListener('mouseover', function(e) {
        // Vérifier que c'est un élément HTML valide
        if (!e.target || typeof e.target.closest !== 'function') return;
        
        const target = e.target.closest('[data-tooltip]');
        if (target && target !== activeTooltipTarget) {
            showTooltip(target);
        }
    });

    document.addEventListener('mouseout', function(e) {
        // Vérifier que c'est un élément HTML valide
        if (!e.target || typeof e.target.closest !== 'function') return;
        
        const target = e.target.closest('[data-tooltip]');
        if (target === activeTooltipTarget) {
            hideTooltip();
        }
    });

    console.log('✅ Système de tooltips initialisé');
    
    // --- 7. AMÉLIORATION NAVIGATION CLAVIER ---
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab' && !e.shiftKey) {
            const currentSection = document.querySelector('.admin-section.active');
            if (currentSection) {
                const focusableElements = currentSection.querySelectorAll(
                    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                );
                if (focusableElements.length > 0) {
                    const lastElement = focusableElements[focusableElements.length - 1];
                    if (document.activeElement === lastElement) {
                        e.preventDefault();
                        focusableElements[0].focus();
                        announceToScreenReader('Fin de la section. Retour au début de la section.');
                    }
                }
            }
        }
    });

    console.log('✅ Navigation clavier améliorée initialisée');
    console.log('✅ Tous les gestionnaires d\'événements sont initialisés');
    console.log(`📊 Résumé:`);
    console.log(`  - Sections: ${sections.length}`);
    console.log(`  - Liens de navigation: ${navLinks.length}`);
    console.log(`  - Compteurs: ${statElements.length}`);

}); // FIN DOMContentLoaded

console.log('📜 Script dashboard.js chargé');