// Gestion de la bibliothèque numérique
class BibliothequeManager {
    constructor() {
        this.ressources = [];
        this.filtresActifs = {
            categories: [],
            types: [],
            niveaux: []
        };
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.chargerRessources();
        this.setupFiltres();
    }

    setupEventListeners() {
        // Recherche
        document.getElementById('search-input').addEventListener('input', (e) => {
            this.rechercherRessources(e.target.value);
        });

        // Options d'affichage
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.changerAffichage(e.target.getAttribute('data-view'));
            });
        });

        // Filtres
        document.getElementById('apply-filters').addEventListener('click', () => {
            this.appliquerFiltres();
        });

        document.getElementById('reset-filters').addEventListener('click', () => {
            this.reinitialiserFiltres();
        });

        // Fermer les modals
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                this.fermerModal();
            });
        });
    }

    chargerRessources() {
        // Charger les ressources depuis l'API
        fetch('api/get-ressources.php')
            .then(response => response.json())
            .then(ressources => {
                this.ressources = ressources;
                this.afficherRessources(ressources);
            })
            .catch(error => {
                console.error('Erreur chargement ressources:', error);
            });
    }

    afficherRessources(ressources) {
        const container = document.getElementById('ressources-container');
        
        if (ressources.length === 0) {
            container.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: #666;">
                    <p>Aucune ressource ne correspond à vos critères.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = ressources.map(ressource => this.creerCarteRessource(ressource)).join('');
    }

    creerCarteRessource(ressource) {
        const icon = this.getRessourceIcon(ressource.type);
        const estTelecharge = ressource.telecharge > 0;
        const estNouveau = ressource.est_nouveau;

        return `
            <div class="ressource-card" 
                 data-categorie="${ressource.categorie_id}" 
                 data-type="${ressource.type}" 
                 data-niveau="${ressource.niveau}"
                 data-id="${ressource.id}">
                <div class="ressource-header">
                    <div class="ressource-icon">${icon}</div>
                    <div class="ressource-badges">
                        ${estTelecharge ? '<span class="badge badge-success">📥 Téléchargé</span>' : ''}
                        ${estNouveau ? '<span class="badge badge-info">🆕 Nouveau</span>' : ''}
                    </div>
                </div>
                
                <div class="ressource-content">
                    <h3>${ressource.titre}</h3>
                    <p class="ressource-description">${ressource.description}</p>
                    
                    <div class="ressource-meta">
                        <span class="categorie">${ressource.categorie_nom}</span>
                        <span class="niveau badge badge-${this.getNiveauBadgeClass(ressource.niveau)}">
                            ${this.capitalize(ressource.niveau)}
                        </span>
                    </div>
                    
                    <div class="ressource-stats">
                        ${ressource.nombre_pages ? `<span>📖 ${ressource.nombre_pages} pages</span>` : ''}
                        ${ressource.duree ? `<span>⏱️ ${ressource.duree} min</span>` : ''}
                        <span>⭐ ${ressource.note_moyenne || '4.5'}/5</span>
                    </div>
                </div>
                
                <div class="ressource-actions">
                    <button class="btn btn-primary" onclick="bibliothequeManager.consulterRessource(${ressource.id})">
                        Consulter
                    </button>
                    <button class="btn btn-outline" onclick="bibliothequeManager.telechargerRessource(${ressource.id})">
                        📥 Télécharger
                    </button>
                </div>
            </div>
        `;
    }

    getRessourceIcon(type) {
        const icons = {
            'pdf': '📄',
            'video': '🎥',
            'audio': '🎵',
            'presentation': '📊',
            'exercice': '📝'
        };
        return icons[type] || '📄';
    }

    getNiveauBadgeClass(niveau) {
        const classes = {
            'debutant': 'info',
            'intermediaire': 'warning',
            'avance': 'success'
        };
        return classes[niveau] || 'info';
    }

    capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    setupFiltres() {
        // Écouter les changements de filtres
        document.querySelectorAll('input[name="categorie"]').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.mettreAJourFiltres();
            });
        });

        document.querySelectorAll('input[name="type"]').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.mettreAJourFiltres();
            });
        });

        document.querySelectorAll('input[name="niveau"]').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.mettreAJourFiltres();
            });
        });
    }

    mettreAJourFiltres() {
        // Catégories
        this.filtresActifs.categories = Array.from(document.querySelectorAll('input[name="categorie"]:checked'))
            .map(checkbox => checkbox.value);

        // Types
        this.filtresActifs.types = Array.from(document.querySelectorAll('input[name="type"]:checked'))
            .map(checkbox => checkbox.value);

        // Niveaux
        this.filtresActifs.niveaux = Array.from(document.querySelectorAll('input[name="niveau"]:checked'))
            .map(checkbox => checkbox.value);
    }

    appliquerFiltres() {
        this.mettreAJourFiltres();
        this.filtrerRessources();
    }

    filtrerRessources() {
        const ressourcesFiltrees = this.ressources.filter(ressource => {
            // Filtre par catégorie
            if (this.filtresActifs.categories.length > 0 && 
                !this.filtresActifs.categories.includes(ressource.categorie_id.toString())) {
                return false;
            }

            // Filtre par type
            if (this.filtresActifs.types.length > 0 && 
                !this.filtresActifs.types.includes(ressource.type)) {
                return false;
            }

            // Filtre par niveau
            if (this.filtresActifs.niveaux.length > 0 && 
                !this.filtresActifs.niveaux.includes(ressource.niveau)) {
                return false;
            }

            return true;
        });

        this.afficherRessources(ressourcesFiltrees);
    }

    reinitialiserFiltres() {
        // Réinitialiser toutes les cases à cocher
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.checked = true;
        });

        this.filtresActifs = {
            categories: [],
            types: [],
            niveaux: []
        };

        this.afficherRessources(this.ressources);
    }

    rechercherRessources(terme) {
        if (!terme.trim()) {
            this.afficherRessources(this.ressources);
            return;
        }

        const termeMinuscule = terme.toLowerCase();
        const resultats = this.ressources.filter(ressource => {
            return ressource.titre.toLowerCase().includes(termeMinuscule) ||
                   ressource.description.toLowerCase().includes(termeMinuscule) ||
                   ressource.categorie_nom.toLowerCase().includes(termeMinuscule);
        });

        this.afficherRessources(resultats);
    }

    changerAffichage(vue) {
        const container = document.getElementById('ressources-container');
        const boutons = document.querySelectorAll('.view-btn');

        // Mettre à jour les boutons
        boutons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.getAttribute('data-view') === vue) {
                btn.classList.add('active');
            }
        });

        // Changer l'affichage
        container.className = `ressources-container ${vue}-view`;
    }

    async consulterRessource(ressourceId) {
        try {
            // Enregistrer la consultation
            await fetch('api/enregistrer-consultation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ ressource_id: ressourceId })
            });

            // Charger la ressource
            const response = await fetch(`api/get-ressource.php?id=${ressourceId}`);
            const ressource = await response.json();

            this.afficherRessourceModal(ressource);

        } catch (error) {
            console.error('Erreur consultation ressource:', error);
            alert('Erreur lors du chargement de la ressource');
        }
    }

    afficherRessourceModal(ressource) {
        const modal = document.getElementById('consultation-modal');
        const titre = document.getElementById('ressource-modal-title');
        const contenu = document.getElementById('ressource-content');

        titre.textContent = ressource.titre;

        // Afficher le contenu selon le type
        switch (ressource.type) {
            case 'pdf':
                contenu.innerHTML = this.creerLecteurPDF(ressource);
                break;
            case 'video':
                contenu.innerHTML = this.creerLecteurVideo(ressource);
                break;
            case 'audio':
                contenu.innerHTML = this.creerLecteurAudio(ressource);
                break;
            case 'presentation':
                contenu.innerHTML = this.creerVisionneusePresentation(ressource);
                break;
            case 'exercice':
                contenu.innerHTML = this.creerVisionneuseExercice(ressource);
                break;
            default:
                contenu.innerHTML = this.creerVisionneuseParDefaut(ressource);
        }

        modal.style.display = 'block';
    }

    creerLecteurPDF(ressource) {
        return `
            <div class="pdf-viewer">
                <iframe src="${ressource.url}" width="100%" height="100%"></iframe>
            </div>
            <div class="ressource-actions" style="margin-top: 1rem; display: flex; gap: 1rem;">
                <button class="btn btn-primary" onclick="bibliothequeManager.telechargerRessource(${ressource.id})">
                    📥 Télécharger le PDF
                </button>
                <button class="btn btn-outline" onclick="bibliothequeManager.fermerModal()">
                    Fermer
                </button>
            </div>
        `;
    }

    creerLecteurVideo(ressource) {
        return `
            <div class="video-container">
                <iframe src="${ressource.url}" allowfullscreen></iframe>
            </div>
            <div class="video-info" style="margin-top: 1rem;">
                <p><strong>Durée:</strong> ${ressource.duree} minutes</p>
                <p><strong>Description:</strong> ${ressource.description}</p>
            </div>
        `;
    }

    creerLecteurAudio(ressource) {
        return `
            <div class="audio-player">
                <h4>${ressource.titre}</h4>
                <audio controls>
                    <source src="${ressource.url}" type="audio/mpeg">
                    Votre navigateur ne supporte pas l'élément audio.
                </audio>
                <p>${ressource.description}</p>
            </div>
        `;
    }

    creerVisionneusePresentation(ressource) {
        return `
            <div class="presentation-viewer">
                <iframe src="${ressource.url}" width="100%" height="100%"></iframe>
            </div>
            <div class="presentation-actions" style="margin-top: 1rem;">
                <button class="btn btn-primary" onclick="bibliothequeManager.telechargerRessource(${ressource.id})">
                    📥 Télécharger la présentation
                </button>
            </div>
        `;
    }

    creerVisionneuseExercice(ressource) {
        return `
            <div class="exercice-viewer">
                <h4>Exercice: ${ressource.titre}</h4>
                <div class="exercice-content" style="background: var(--couleur-fond); padding: 2rem; border-radius: 10px; margin: 1rem 0;">
                    ${ressource.contenu || 'Contenu de l\'exercice à compléter...'}
                </div>
                <div class="exercice-actions">
                    <button class="btn btn-primary">Soumettre la réponse</button>
                    <button class="btn btn-outline">Voir la correction</button>
                </div>
            </div>
        `;
    }

    creerVisionneuseParDefaut(ressource) {
        return `
            <div class="ressource-default">
                <h4>${ressource.titre}</h4>
                <p>${ressource.description}</p>
                <div class="ressource-actions">
                    <a href="${ressource.url}" class="btn btn-primary" target="_blank">
                        Ouvrir la ressource
                    </a>
                    <button class="btn btn-outline" onclick="bibliothequeManager.telechargerRessource(${ressource.id})">
                        📥 Télécharger
                    </button>
                </div>
            </div>
        `;
    }

    async telechargerRessource(ressourceId) {
        try {
            // Enregistrer le téléchargement
            await fetch('api/enregistrer-telechargement.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ ressource_id: ressourceId })
            });

            // Télécharger le fichier
            const ressource = this.ressources.find(r => r.id === ressourceId);
            if (ressource && ressource.url) {
                const link = document.createElement('a');
                link.href = ressource.url;
                link.download = ressource.titre;
                link.click();
            }

            // Mettre à jour l'interface
            this.mettreAJourBadgeTelechargement(ressourceId);

        } catch (error) {
            console.error('Erreur téléchargement:', error);
            alert('Erreur lors du téléchargement');
        }
    }

    mettreAJourBadgeTelechargement(ressourceId) {
        const carte = document.querySelector(`[data-id="${ressourceId}"]`);
        if (carte) {
            const badges = carte.querySelector('.ressource-badges');
            if (!badges.querySelector('.badge-success')) {
                badges.innerHTML += '<span class="badge badge-success">📥 Téléchargé</span>';
            }
        }
    }

    fermerModal() {
        document.getElementById('consultation-modal').style.display = 'none';
    }
}

// Fonctions globales pour les appels depuis HTML
function consulterRessource(id) {
    window.bibliothequeManager.consulterRessource(id);
}

function telechargerRessource(id) {
    window.bibliothequeManager.telechargerRessource(id);
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    window.bibliothequeManager = new BibliothequeManager();
});