// ========================================
// Course Management - Dashboard
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    
    // ===== 1. MODAL GESTION FORMATIONS =====
    const modal = document.getElementById('modal-add-course');
    const modalFormation = document.getElementById('modal-add-formation');
    const btnNewCourse = document.getElementById('btn-new-formation') || document.getElementById('btn-new-course');
    const btnCloseModal = document.getElementById('btn-close-modal');
    const formAddCourse = document.getElementById('form-add-course');
    const formAddFormation = document.getElementById('form-add-formation') || null;
    const formMessage = document.getElementById('form-message');

    // Ouvrir le modal
    if (btnNewCourse) {
        btnNewCourse.addEventListener('click', function(e) {
            e.preventDefault();
            // Ouvrir la modal de création de formation si elle existe, sinon fallback sur modal-add-course
            if (modalFormation) {
                modalFormation.style.display = 'flex';
                const el = modalFormation.querySelector('#course-titre') || modalFormation.querySelector('input, textarea');
                if (el) el.focus();
            } else if (modal) {
                modal.style.display = 'flex';
                const el = document.getElementById('course-titre');
                if (el) el.focus();
            }
        });
    }

    // Fermer le modal
    if (btnCloseModal) {
        btnCloseModal.addEventListener('click', function() {
            closeModal();
        });
    }

    // Fermer si on clique en dehors du modal
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    // Fermer si on clique en dehors du modal de formation
    if (modalFormation) {
        modalFormation.addEventListener('click', function(e) {
            if (e.target === modalFormation) {
                closeModal();
            }
        });
    }

    function closeModal() {
        if (modal) modal.style.display = 'none';
        if (modalFormation) modalFormation.style.display = 'none';
        if (formAddCourse) try { formAddCourse.reset(); } catch (e) {}
        if (formAddFormation) try { formAddFormation.reset(); } catch (e) {}
        if (formMessage) {
            formMessage.style.display = 'none';
            formMessage.textContent = '';
        }
    }

    // ===== 2. SOUMISSION FORMULAIRE =====
    const formToUse = formAddFormation || formAddCourse;
    if (formToUse) {
        formToUse.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Récupérer les données du formulaire
            const formData = {
                titre: (document.getElementById('course-titre') || {}).value || '',
                description: (document.getElementById('course-description') || {}).value || '',
                niveau: (document.getElementById('course-niveau') || {}).value || 'debutant',
                type: (document.getElementById('course-type') || {}).value || 'en_ligne',
                duree: parseInt((document.getElementById('course-duree') || {}).value) || 0,
                prix: parseFloat((document.getElementById('course-prix') || {}).value) || 0.00
            };

            // Validation
            if (!formData.titre.trim()) {
                showMessage('Le titre est obligatoire', 'error');
                return;
            }

            // Afficher le message de chargement
            showMessage('Création en cours...', 'info');
            const submitBtn = formToUse.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            try {
                // Envoyer à l'API
                const response = await fetch('/ADH/dashboard/api/add_formation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    showMessage('✓ Formation créée avec succès ! Rechargement de la page...', 'success');
                    
                    // Attendre 1.5s puis recharger
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessage('✗ Erreur: ' + (result.message || 'Impossible de créer la formation'), 'error');
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Erreur:', error);
                showMessage('✗ Erreur réseau: ' + error.message, 'error');
                submitBtn.disabled = false;
            }
        });
    }

    function showMessage(text, type) {
        const styles = {
            'success': { background: '#d4edda', color: '#155724', border: '1px solid #c3e6cb' },
            'error': { background: '#f8d7da', color: '#721c24', border: '1px solid #f5c6cb' },
            'info': { background: '#d1ecf1', color: '#0c5460', border: '1px solid #bee5eb' }
        };

        formMessage.style.display = 'block';
        formMessage.textContent = text;
        Object.assign(formMessage.style, styles[type]);
    }

    // ===== 3. DÉLÉGATION GLOBALE D'ÉVÉNEMENTS POUR TOUS LES BOUTONS =====
    document.addEventListener('click', function(e) {
        
        // BOUTON: Ajouter un cours à une formation
        if (e.target.closest('.btn-add-course')) {
            e.preventDefault();
            const btn = e.target.closest('.btn-add-course');
            const formationId = btn.getAttribute('data-formation-id');
            console.log('➕ Ajout de cours à formation:', formationId);
            openCourseModal(formationId);
            return;
        }

        // BOUTON: Ajouter un module à un cours
        if (e.target.closest('.btn-add-module')) {
            e.preventDefault();
            const btn = e.target.closest('.btn-add-module');
            const courseId = btn.getAttribute('data-course-id');
            console.log('➕ Ajout de module au cours:', courseId);
            openModuleModal(courseId);
            return;
        }

        // BOUTON: Changer le statut
        if (e.target.closest('.btn-change-statut')) {
            e.preventDefault();
            const btn = e.target.closest('.btn-change-statut');
            const formationId = btn.getAttribute('data-formation-id');
            const currentStatus = btn.getAttribute('data-current-statut');
            console.log('📝 Changement de statut:', formationId, '->', currentStatus);
            changeFormationStatus(btn, formationId, currentStatus);
            return;
        }
    });

    // ===== 4. FONCTION: Ouvrir modal pour ajouter un cours =====
    function openCourseModal(formationId) {
        const courseModal = document.createElement('div');
        courseModal.id = 'modal-add-course-to-formation-' + formationId;
        courseModal.style.cssText = 'display: flex; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1001; align-items: center; justify-content: center;';
        
        courseModal.innerHTML = `
            <div style="background: white; padding: 2rem; border-radius: 12px; width: 90%; max-width: 500px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                <h3 style="margin: 0 0 1.5rem 0;">Ajouter un Cours</h3>
                <form id="form-add-course-modal-${formationId}">
                    <div style="margin-bottom: 1rem;">
                        <label for="course-titre-${formationId}" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Titre du Cours *</label>
                        <input type="text" id="course-titre-${formationId}" required placeholder="Titre du cours" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label for="course-niveau-${formationId}" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Niveau</label>
                            <select id="course-niveau-${formationId}" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                <option value="debutant">Débutant</option>
                                <option value="intermediaire">Intermédiaire</option>
                                <option value="avance">Avancé</option>
                            </select>
                        </div>
                        <div>
                            <label for="course-duree-${formationId}" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Durée (h)</label>
                            <input type="number" id="course-duree-${formationId}" placeholder="0" min="0" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                        </div>
                    </div>
                    <div id="course-msg-${formationId}" style="display: none; padding: 1rem; margin-bottom: 1rem; border-radius: 6px;"></div>
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1; padding: 0.75rem; background: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Créer le Cours</button>
                        <button type="button" class="btn-close-modal" style="flex: 1; padding: 0.75rem; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Annuler</button>
                    </div>
                </form>
            </div>
        `;
        
        document.body.appendChild(courseModal);
        
        // Fermer le modal
        courseModal.querySelector('.btn-close-modal').addEventListener('click', function() {
            courseModal.remove();
        });
        
        courseModal.addEventListener('click', function(e) {
            if (e.target === courseModal) courseModal.remove();
        });
        
        // Soumission du formulaire
        courseModal.querySelector(`#form-add-course-modal-${formationId}`).addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                formation_id: parseInt(formationId),
                titre: document.getElementById(`course-titre-${formationId}`).value,
                niveau: document.getElementById(`course-niveau-${formationId}`).value,
                duree: parseInt(document.getElementById(`course-duree-${formationId}`).value) || 0
            };
            
            console.log('📤 Envoi des données:', formData);
            
            try {
                const response = await fetch('/ADH/dashboard/api/add_course.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                const msgDiv = document.getElementById(`course-msg-${formationId}`);
                
                if (result.success) {
                    msgDiv.style.display = 'block';
                    msgDiv.style.background = '#d4edda';
                    msgDiv.style.color = '#155724';
                    msgDiv.style.borderLeft = '4px solid #28a745';
                    msgDiv.textContent = '✓ ' + result.message;
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    msgDiv.style.display = 'block';
                    msgDiv.style.background = '#f8d7da';
                    msgDiv.style.color = '#721c24';
                    msgDiv.style.borderLeft = '4px solid #dc3545';
                    msgDiv.textContent = '✗ ' + (result.message || 'Erreur lors de la création du cours');
                }
            } catch (error) {
                console.error('Erreur:', error);
                const msgDiv = document.getElementById(`course-msg-${formationId}`);
                msgDiv.style.display = 'block';
                msgDiv.style.background = '#f8d7da';
                msgDiv.style.color = '#721c24';
                msgDiv.style.borderLeft = '4px solid #dc3545';
                msgDiv.textContent = '✗ Erreur réseau: ' + error.message;
            }
        });
    }

    // ===== 5. FONCTION: Ouvrir modal pour ajouter un module =====
    function openModuleModal(courseId) {
        const moduleModal = document.createElement('div');
        moduleModal.id = 'modal-add-module-' + courseId;
        moduleModal.style.cssText = 'display: flex; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1001; align-items: center; justify-content: center;';
        
        moduleModal.innerHTML = `
            <div style="background: white; padding: 2rem; border-radius: 12px; width: 90%; max-width: 500px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                <h3 style="margin: 0 0 1.5rem 0;">Ajouter un Module</h3>
                <form id="form-add-module-modal-${courseId}">
                    <div style="margin-bottom: 1rem;">
                        <label for="module-titre-${courseId}" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Titre du Module *</label>
                        <input type="text" id="module-titre-${courseId}" required placeholder="Titre du module" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label for="module-duree-${courseId}" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Durée (min)</label>
                            <input type="number" id="module-duree-${courseId}" placeholder="0" min="0" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                        </div>
                        <div>
                            <label for="module-ordre-${courseId}" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Ordre</label>
                            <input type="number" id="module-ordre-${courseId}" placeholder="1" value="1" min="1" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                        </div>
                    </div>
                    <div id="module-msg-${courseId}" style="display: none; padding: 1rem; margin-bottom: 1rem; border-radius: 6px;"></div>
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1; padding: 0.75rem; background: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Créer le Module</button>
                        <button type="button" class="btn-close-modal" style="flex: 1; padding: 0.75rem; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Annuler</button>
                    </div>
                </form>
            </div>
        `;
        
        document.body.appendChild(moduleModal);
        
        // Fermer le modal
        moduleModal.querySelector('.btn-close-modal').addEventListener('click', function() {
            moduleModal.remove();
        });
        
        moduleModal.addEventListener('click', function(e) {
            if (e.target === moduleModal) moduleModal.remove();
        });
        
        // Soumission du formulaire
        moduleModal.querySelector(`#form-add-module-modal-${courseId}`).addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                cours_id: parseInt(courseId),
                titre: document.getElementById(`module-titre-${courseId}`).value,
                duree_estimee: parseInt(document.getElementById(`module-duree-${courseId}`).value) || 0,
                ordre: parseInt(document.getElementById(`module-ordre-${courseId}`).value) || 1
            };
            
            try {
                const response = await fetch('/ADH/dashboard/api/add_module.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                const msgDiv = document.getElementById(`module-msg-${courseId}`);
                
                if (result.success) {
                    msgDiv.style.display = 'block';
                    msgDiv.style.background = '#d4edda';
                    msgDiv.style.color = '#155724';
                    msgDiv.style.borderLeft = '4px solid #28a745';
                    msgDiv.textContent = '✓ ' + result.message;
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    msgDiv.style.display = 'block';
                    msgDiv.style.background = '#f8d7da';
                    msgDiv.style.color = '#721c24';
                    msgDiv.style.borderLeft = '4px solid #dc3545';
                    msgDiv.textContent = '✗ ' + (result.message || 'Erreur lors de la création du module');
                }
            } catch (error) {
                console.error('Erreur:', error);
                const msgDiv = document.getElementById(`module-msg-${courseId}`);
                msgDiv.style.display = 'block';
                msgDiv.style.background = '#f8d7da';
                msgDiv.style.color = '#721c24';
                msgDiv.style.borderLeft = '4px solid #dc3545';
                msgDiv.textContent = '✗ Erreur réseau: ' + error.message;
            }
        });
    }

    // ===== 6. FONCTION: Changer le statut d'une formation =====
    function changeFormationStatus(btn, formationId, currentStatus) {
        const statusOptions = ['brouillon', 'en_cours', 'termine'];
        const select = document.createElement('select');
        select.style.cssText = 'padding: 0.5rem; border-radius: 4px; border: 1px solid #ddd; cursor: pointer; font-size: 0.85rem;';
        
        statusOptions.forEach(status => {
            const option = document.createElement('option');
            option.value = status;
            option.textContent = status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
            option.selected = status === currentStatus;
            select.appendChild(option);
        });
        
        select.addEventListener('change', async function(e) {
            const newStatus = this.value;
            console.log('📝 Changement de statut:', formationId, 'vers', newStatus);
            
            try {
                const response = await fetch('/ADH/dashboard/api/update_formation_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        formation_id: parseInt(formationId),
                        statut: newStatus
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    console.log('✓ Statut mis à jour');
                    window.location.reload();
                } else {
                    alert('Erreur: ' + (result.message || 'Impossible de mettre à jour le statut'));
                    select.value = currentStatus;
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur réseau: ' + error.message);
                select.value = currentStatus;
            }
        });
        
        btn.replaceWith(select);
    }

    // ===== 3. GESTION BOUTONS "AJOUTER MODULE" (Délégation d'événements) =====
    // Le code est géré dans la section DÉLÉGATION GLOBALE plus haut

    console.log('✅ Course Management initialisé avec délégation d\'événements');
});