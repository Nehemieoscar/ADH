// ========================================
// Module Content Management - Drag & Drop
// Leçons, Devoirs, Quiz
// ========================================

document.addEventListener('DOMContentLoaded', function() {

    // ===== 1. DRAG AND DROP DES MODULES =====
    const modulesList = document.getElementById('modules-list');
    if (modulesList) {
        // Rendre les modules draggables
        const modules = modulesList.querySelectorAll('.module-item');
        modules.forEach(module => {
            module.setAttribute('draggable', 'true');
            module.addEventListener('dragstart', handleDragStart);
            module.addEventListener('dragend', handleDragEnd);
            module.addEventListener('dragover', handleDragOver);
            module.addEventListener('drop', handleDrop);
        });
    }

    let draggedElement = null;

    function handleDragStart(e) {
        draggedElement = this;
        this.style.opacity = '0.5';
        e.dataTransfer.effectAllowed = 'move';
    }

    function handleDragEnd(e) {
        this.style.opacity = '1';
        draggedElement = null;
        saveModuleOrder();
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        if (this !== draggedElement) {
            this.parentNode.insertBefore(draggedElement, this);
        }
    }

    function handleDrop(e) {
        e.preventDefault();
    }

    async function saveModuleOrder() {
        const modules = document.querySelectorAll('.module-item');
        const moduleIds = Array.from(modules).map(m => m.getAttribute('data-module-id'));

        try {
            const response = await fetch('/ADH/dashboard/api/update_module_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ modules: moduleIds })
            });
            const result = await response.json();
            if (result.success) {
                console.log('✓ Ordre des modules mise à jour');
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    // ===== 2. GESTION DES LEÇONS =====
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-add-lesson')) {
            const btn = e.target.closest('.btn-add-lesson');
            const moduleId = btn.getAttribute('data-module-id');
            openLessonModal(moduleId);
        }

        if (e.target.closest('.btn-add-assignment')) {
            const btn = e.target.closest('.btn-add-assignment');
            const moduleId = btn.getAttribute('data-module-id');
            openAssignmentModal(moduleId);
        }

        if (e.target.closest('.btn-add-quiz')) {
            const btn = e.target.closest('.btn-add-quiz');
            const moduleId = btn.getAttribute('data-module-id');
            openQuizModal(moduleId);
        }
    });

    // Modal pour ajouter une leçon
    function openLessonModal(moduleId) {
        const modal = document.createElement('div');
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1100; display: flex; align-items: center; justify-content: center;';

        modal.innerHTML = `
            <div style="background: white; padding: 2rem; border-radius: 12px; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto;">
                <h3 style="margin-top: 0;">Ajouter une Leçon</h3>
                <form id="form-lesson-modal">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Titre de la leçon</label>
                        <input type="text" id="lesson-titre" required placeholder="Ex: Introduction au JavaScript" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Contenu (HTML accepté)</label>
                        <textarea id="lesson-contenu" placeholder="Écrivez le contenu de la leçon..." style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; min-height: 200px;"></textarea>
                    </div>
                    <div id="lesson-msg" style="display: none; padding: 1rem; margin-bottom: 1rem; border-radius: 6px;"></div>
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1; padding: 0.75rem; background: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer;">Créer la Leçon</button>
                        <button type="button" class="btn-close-modal" style="flex: 1; padding: 0.75rem; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer;">Annuler</button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(modal);

        modal.querySelector('.btn-close-modal').addEventListener('click', () => modal.remove());
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });

        modal.querySelector('#form-lesson-modal').addEventListener('submit', async (e) => {
            e.preventDefault();
            const titre = document.getElementById('lesson-titre').value;
            const contenu = document.getElementById('lesson-contenu').value;

            try {
                const response = await fetch('/ADH/dashboard/api/add_lesson.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ module_id: moduleId, titre, contenu })
                });
                const result = await response.json();

                if (result.success) {
                    const msgDiv = document.getElementById('lesson-msg');
                    msgDiv.style.display = 'block';
                    msgDiv.style.background = '#d4edda';
                    msgDiv.style.color = '#155724';
                    msgDiv.textContent = '✓ ' + result.message;
                    setTimeout(() => { modal.remove(); window.location.reload(); }, 1500);
                } else {
                    const msgDiv = document.getElementById('lesson-msg');
                    msgDiv.style.display = 'block';
                    msgDiv.style.background = '#f8d7da';
                    msgDiv.style.color = '#721c24';
                    msgDiv.textContent = '✗ ' + (result.message || 'Erreur');
                }
            } catch (error) {
                console.error('Erreur:', error);
                const msgDiv = document.getElementById('lesson-msg');
                msgDiv.style.display = 'block';
                msgDiv.style.background = '#f8d7da';
                msgDiv.style.color = '#721c24';
                msgDiv.textContent = '✗ Erreur réseau';
            }
        });
    }

    // Modal pour ajouter un devoir
    function openAssignmentModal(moduleId) {
        const modal = document.createElement('div');
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1100; display: flex; align-items: center; justify-content: center;';

        modal.innerHTML = `
            <div style="background: white; padding: 2rem; border-radius: 12px; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto;">
                <h3 style="margin-top: 0;">Ajouter un Devoir</h3>
                <form id="form-assignment-modal" enctype="multipart/form-data">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Titre</label>
                        <input type="text" id="assignment-titre" required placeholder="Titre du devoir" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Description</label>
                        <textarea id="assignment-desc" placeholder="Description du devoir" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; min-height: 120px;"></textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Type</label>
                            <select id="assignment-type" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
                                <option value="individuel">Individuel</option>
                                <option value="groupe">En groupe</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Date limite</label>
                            <input type="datetime-local" id="assignment-deadline" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
                        </div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Fichier PDF (optionnel)</label>
                        <input type="file" id="assignment-pdf" accept=".pdf" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                    <div id="assignment-msg" style="display: none; padding: 1rem; margin-bottom: 1rem; border-radius: 6px;"></div>
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1; padding: 0.75rem; background: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer;">Créer le Devoir</button>
                        <button type="button" class="btn-close-modal" style="flex: 1; padding: 0.75rem; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer;">Annuler</button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(modal);

        modal.querySelector('.btn-close-modal').addEventListener('click', () => modal.remove());
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });

        modal.querySelector('#form-assignment-modal').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData();
            formData.append('module_id', moduleId);
            formData.append('titre', document.getElementById('assignment-titre').value);
            formData.append('description', document.getElementById('assignment-desc').value);
            formData.append('type_remise', document.getElementById('assignment-type').value);
            formData.append('date_limite', document.getElementById('assignment-deadline').value);
            formData.append('points_max', 100);

            const pdfFile = document.getElementById('assignment-pdf').files[0];
            if (pdfFile) {
                formData.append('fichier_pdf', pdfFile);
            }

            try {
                const response = await fetch('/ADH/dashboard/api/add_assignment.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    const msgDiv = document.getElementById('assignment-msg');
                    msgDiv.style.display = 'block';
                    msgDiv.style.background = '#d4edda';
                    msgDiv.style.color = '#155724';
                    msgDiv.textContent = '✓ ' + result.message;
                    setTimeout(() => { modal.remove(); window.location.reload(); }, 1500);
                } else {
                    const msgDiv = document.getElementById('assignment-msg');
                    msgDiv.style.display = 'block';
                    msgDiv.style.background = '#f8d7da';
                    msgDiv.style.color = '#721c24';
                    msgDiv.textContent = '✗ ' + (result.message || 'Erreur');
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        });
    }

    // Modal pour ajouter un quiz
    function openQuizModal(moduleId) {
        const modal = document.createElement('div');
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1100; display: flex; align-items: center; justify-content: center;';

        modal.innerHTML = `
            <div style="background: white; padding: 2rem; border-radius: 12px; width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto;">
                <h3 style="margin-top: 0;">Ajouter un Quiz</h3>
                <form id="form-quiz-modal">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Titre du Quiz</label>
                        <input type="text" id="quiz-titre" required placeholder="Titre du quiz" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
                    </div>

                    <div id="questions-container">
                        <div class="quiz-question" style="padding: 1rem; background: #f9f9f9; border-radius: 6px; margin-bottom: 1rem;">
                            <h4>Question 1</h4>
                            <input type="text" class="question-enonce" placeholder="Énoncé de la question" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 0.75rem;">
                            
                            <select class="question-type" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 0.75rem;">
                                <option value="multiple_choice">Choix multiple</option>
                                <option value="true_false">Vrai/Faux</option>
                                <option value="short_answer">Réponse courte</option>
                            </select>

                            <div class="question-options">
                                <input type="text" class="option-text" placeholder="Option 1" style="width: 100%; padding: 0.5rem; margin-bottom: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                <label style="margin-bottom: 0.5rem;">
                                    <input type="checkbox" class="option-correct"> Correct
                                </label>
                                <button type="button" class="btn-add-option" style="padding: 0.5rem 1rem; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">+ Ajouter option</button>
                            </div>
                        </div>
                    </div>

                    <button type="button" id="btn-add-question" style="padding: 0.75rem 1.5rem; background: #17a2b8; color: white; border: none; border-radius: 6px; cursor: pointer; margin-bottom: 1rem;">+ Ajouter Question</button>

                    <div id="quiz-msg" style="display: none; padding: 1rem; margin-bottom: 1rem; border-radius: 6px;"></div>
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1; padding: 0.75rem; background: #dc3545; color: white; border: none; border-radius: 6px; cursor: pointer;">Créer le Quiz</button>
                        <button type="button" class="btn-close-modal" style="flex: 1; padding: 0.75rem; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer;">Annuler</button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(modal);

        // Gestion des questions
        let questionCount = 1;
        modal.querySelector('#btn-add-question').addEventListener('click', () => {
            questionCount++;
            const container = modal.querySelector('#questions-container');
            const newQuestion = document.createElement('div');
            newQuestion.className = 'quiz-question';
            newQuestion.style.cssText = 'padding: 1rem; background: #f9f9f9; border-radius: 6px; margin-bottom: 1rem;';
            newQuestion.innerHTML = `
                <h4>Question ${questionCount}</h4>
                <input type="text" class="question-enonce" placeholder="Énoncé de la question" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 0.75rem;">
                <select class="question-type" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 0.75rem;">
                    <option value="multiple_choice">Choix multiple</option>
                    <option value="true_false">Vrai/Faux</option>
                    <option value="short_answer">Réponse courte</option>
                </select>
                <div class="question-options">
                    <input type="text" class="option-text" placeholder="Option 1" style="width: 100%; padding: 0.5rem; margin-bottom: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                    <label style="margin-bottom: 0.5rem;">
                        <input type="checkbox" class="option-correct"> Correct
                    </label>
                    <button type="button" class="btn-add-option" style="padding: 0.5rem 1rem; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">+ Ajouter option</button>
                </div>
            `;
            container.appendChild(newQuestion);
        });

        // Delegation pour ajouter des options
        modal.addEventListener('click', (e) => {
            if (e.target.closest('.btn-add-option')) {
                const optionsDiv = e.target.closest('.question-options');
                const newOption = document.createElement('div');
                newOption.style.cssText = 'margin-bottom: 0.5rem;';
                newOption.innerHTML = `
                    <input type="text" class="option-text" placeholder="Option" style="width: 100%; padding: 0.5rem; margin-bottom: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                    <label style="margin-bottom: 0.5rem;">
                        <input type="checkbox" class="option-correct"> Correct
                    </label>
                `;
                optionsDiv.insertBefore(newOption, e.target);
            }
        });

        modal.querySelector('.btn-close-modal').addEventListener('click', () => modal.remove());
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });

        modal.querySelector('#form-quiz-modal').addEventListener('submit', async (e) => {
            e.preventDefault();
            const titre = modal.querySelector('#quiz-titre').value;
            const questions = [];

            modal.querySelectorAll('.quiz-question').forEach((qDiv) => {
                const enonce = qDiv.querySelector('.question-enonce').value;
                const type = qDiv.querySelector('.question-type').value;
                const options = [];

                qDiv.querySelectorAll('.question-options input[type="text"]').forEach((input, idx) => {
                    const checkbox = input.parentElement.querySelector('input[type="checkbox"]');
                    options.push({
                        texte: input.value,
                        est_correcte: checkbox ? checkbox.checked : false
                    });
                });

                questions.push({ enonce, type, options, points: 10 });
            });

            try {
                const response = await fetch('/ADH/dashboard/api/add_quiz.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ module_id: moduleId, titre, questions, points_max: questions.length * 10 })
                });
                const result = await response.json();

                if (result.success) {
                    const msgDiv = document.getElementById('quiz-msg');
                    msgDiv.style.display = 'block';
                    msgDiv.style.background = '#d4edda';
                    msgDiv.style.color = '#155724';
                    msgDiv.textContent = '✓ ' + result.message;
                    setTimeout(() => { modal.remove(); window.location.reload(); }, 1500);
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        });
    }

    console.log('✅ Module Content Management initialisé');
});
