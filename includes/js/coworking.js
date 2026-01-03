// Gestion de l'espace coworking
class CoworkingManager {
    constructor() {
        this.socket = null;
        this.currentProjectId = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeFeatures();
        this.setupProjectManagement();
    }

    setupEventListeners() {
        // Gestion des formulaires de projet
        const projectForms = document.querySelectorAll('.project-form');
        projectForms.forEach(form => {
            form.addEventListener('submit', (e) => this.handleProjectForm(e));
        });

        // Gestion du chat en temps réel
        this.setupChat();

        // Gestion du drag & drop pour les fichiers
        this.setupFileUpload();

        // Gestion des tâches
        this.setupTaskManagement();
    }

    setupRealTimeFeatures() {
        // Connexion WebSocket pour le temps réel
        if (typeof io !== 'undefined') {
            this.socket = io();
            this.setupSocketEvents();
        }
    }

    setupSocketEvents() {
        if (!this.socket) return;

        this.socket.on('chat-message', (data) => {
            this.displayChatMessage(data);
        });

        this.socket.on('task-updated', (data) => {
            this.updateTask(data);
        });

        this.socket.on('file-uploaded', (data) => {
            this.addFileToList(data);
        });

        this.socket.on('member-joined', (data) => {
            this.addMemberToList(data);
        });

        this.socket.on('member-left', (data) => {
            this.removeMemberFromList(data);
        });
    }

    setupChat() {
        const chatForm = document.getElementById('chat-form');
        const chatInput = document.getElementById('chat-input');
        const chatMessages = document.querySelector('.chat-messages');

        if (chatForm && chatInput) {
            chatForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.sendChatMessage(chatInput.value);
                chatInput.value = '';
            });

            // Auto-scroll vers le bas
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }
    }

    sendChatMessage(message) {
        if (!message.trim()) return;

        const data = {
            projectId: this.currentProjectId,
            message: message,
            timestamp: new Date().toISOString()
        };

        if (this.socket) {
            this.socket.emit('chat-message', data);
        } else {
            // Fallback AJAX
            this.sendMessageViaAJAX(data);
        }
    }

    displayChatMessage(data) {
        const chatMessages = document.querySelector('.chat-messages');
        if (!chatMessages) return;

        const messageElement = document.createElement('div');
        messageElement.className = `chat-message ${data.isOwn ? 'own' : 'other'}`;
        
        messageElement.innerHTML = `
            <div class="message-header">
                <span class="message-auteur">${data.auteur}</span>
                <span class="message-date">${this.formatTime(data.timestamp)}</span>
            </div>
            <div class="message-content">${this.escapeHtml(data.message)}</div>
        `;

        chatMessages.appendChild(messageElement);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    setupFileUpload() {
        const dropZone = document.getElementById('file-drop-zone');
        const fileInput = document.getElementById('file-input');

        if (dropZone) {
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('dragover');
            });

            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('dragover');
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                this.handleFiles(files);
            });

            dropZone.addEventListener('click', () => {
                fileInput?.click();
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                this.handleFiles(e.target.files);
            });
        }
    }

    handleFiles(files) {
        for (let file of files) {
            this.uploadFile(file);
        }
    }

    uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('projectId', this.currentProjectId);

        fetch('upload-file.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.addFileToList(data.file);
            } else {
                this.showError('Erreur lors du téléchargement: ' + data.error);
            }
        })
        .catch(error => {
            this.showError('Erreur réseau: ' + error.message);
        });
    }

    addFileToList(fileData) {
        const filesGrid = document.querySelector('.fichiers-grid');
        if (!filesGrid) return;

        const fileCard = document.createElement('div');
        fileCard.className = 'fichier-card';
        fileCard.innerHTML = `
            <div class="fichier-icon">${this.getFileIcon(fileData.type)}</div>
            <div class="fichier-nom">${fileData.name}</div>
            <div class="fichier-info">
                ${this.formatFileSize(fileData.size)}<br>
                Par ${fileData.uploader}
            </div>
            <div class="fichier-actions" style="margin-top: 0.5rem;">
                <a href="${fileData.url}" class="btn btn-outline" download style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Télécharger</a>
            </div>
        `;

        filesGrid.appendChild(fileCard);
    }

    setupTaskManagement() {
        // Gestion des cases à cocher des tâches
        const taskCheckboxes = document.querySelectorAll('.tache-checkbox input');
        taskCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                this.updateTaskStatus(e.target);
            });
        });

        // Gestion de l'ajout de tâches
        const taskForm = document.getElementById('add-task-form');
        if (taskForm) {
            taskForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.addNewTask(taskForm);
            });
        }
    }

    updateTaskStatus(checkbox) {
        const taskId = checkbox.getAttribute('data-task-id');
        const isCompleted = checkbox.checked;

        const taskItem = checkbox.closest('.tache-item');
        if (taskItem) {
            taskItem.classList.toggle('tache-terminee', isCompleted);
        }

        // Envoyer la mise à jour au serveur
        fetch('update-task.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                taskId: taskId,
                completed: isCompleted
            })
        });
    }

    addNewTask(form) {
        const formData = new FormData(form);
        const taskData = {
            titre: formData.get('titre'),
            description: formData.get('description'),
            projectId: this.currentProjectId
        };

        fetch('add-task.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(taskData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.addTaskToList(data.task);
                form.reset();
            } else {
                this.showError('Erreur lors de l\'ajout de la tâche');
            }
        });
    }

    addTaskToList(taskData) {
        const tasksList = document.querySelector('.taches-list');
        if (!tasksList) return;

        const taskItem = document.createElement('div');
        taskItem.className = 'tache-item';
        taskItem.innerHTML = `
            <div class="tache-checkbox">
                <input type="checkbox" data-task-id="${taskData.id}">
            </div>
            <div class="tache-content">
                <div class="tache-titre">${taskData.titre}</div>
                <div class="tache-description">${taskData.description}</div>
                <div class="tache-meta">
                    <span>Ajouté par ${taskData.creator}</span>
                    <span>${this.formatTime(taskData.createdAt)}</span>
                </div>
            </div>
        `;

        tasksList.appendChild(taskItem);

        // Ajouter l'event listener à la nouvelle checkbox
        const newCheckbox = taskItem.querySelector('.tache-checkbox input');
        newCheckbox.addEventListener('change', (e) => {
            this.updateTaskStatus(e.target);
        });
    }

    setupProjectManagement() {
        // Gestion des invitations
        const inviteForm = document.getElementById('invite-form');
        if (inviteForm) {
            inviteForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.sendInvitation(inviteForm);
            });
        }

        // Gestion des paramètres du projet
        const settingsForm = document.getElementById('project-settings');
        if (settingsForm) {
            settingsForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.updateProjectSettings(settingsForm);
            });
        }
    }

    sendInvitation(form) {
        const formData = new FormData(form);
        const email = formData.get('email');

        fetch('invite-member.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: email,
                projectId: this.currentProjectId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showSuccess('Invitation envoyée avec succès');
                form.reset();
            } else {
                this.showError('Erreur: ' + data.error);
            }
        });
    }

    // Utilitaires
    formatTime(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString('fr-FR', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    getFileIcon(fileType) {
        const icons = {
            'pdf': '📕',
            'doc': '📄',
            'docx': '📄',
            'xls': '📊',
            'xlsx': '📊',
            'zip': '📦',
            'image': '🖼️',
            'video': '🎥',
            'audio': '🎵',
            'default': '📎'
        };

        if (fileType.startsWith('image/')) return icons.image;
        if (fileType.startsWith('video/')) return icons.video;
        if (fileType.startsWith('audio/')) return icons.audio;

        const extension = fileType.split('/').pop();
        return icons[extension] || icons.default;
    }

    escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.textContent = message;
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '1000';

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    new CoworkingManager();

    // Initialiser le projet courant si disponible
    const projectIdElement = document.querySelector('[data-project-id]');
    if (projectIdElement) {
        window.coworkingManager.currentProjectId = projectIdElement.getAttribute('data-project-id');
    }

    // Gestion des onglets
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabName = button.getAttribute('data-tab');
            
            // Désactiver tous les onglets
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // Activer l'onglet sélectionné
            button.classList.add('active');
            document.getElementById(tabName).classList.add('active');
        });
    });
});

// Fonction pour rejoindre un projet
function joinProject(projectId) {
    fetch('rejoindre-projet.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ projectId: projectId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Erreur: ' + data.error);
        }
    });
}