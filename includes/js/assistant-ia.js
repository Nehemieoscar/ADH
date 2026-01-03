// Gestion de l'assistant IA
class AssistantIAManager {
    constructor() {
        this.currentConversationId = null;
        this.isLoading = false;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupMessageInput();
        this.loadInitialConversation();
    }

    setupEventListeners() {
        // Nouvelle conversation
        document.getElementById('new-chat-btn').addEventListener('click', () => {
            this.startNewConversation();
        });

        // Suggestions rapides
        document.querySelectorAll('.suggestion').forEach(suggestion => {
            suggestion.addEventListener('click', (e) => {
                const prompt = e.target.getAttribute('data-prompt');
                this.sendMessage(prompt);
            });
        });

        // Fonctionnalités
        document.querySelectorAll('.btn-feature').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const feature = e.target.getAttribute('data-feature');
                this.handleFeature(feature);
            });
        });

        // Paramètres
        document.querySelector('.btn-icon[title="Paramètres"]').addEventListener('click', () => {
            this.openSettings();
        });

        // Fermer le modal
        document.querySelector('.close-modal').addEventListener('click', () => {
            this.closeSettings();
        });

        // Clic en dehors du modal pour fermer
        window.addEventListener('click', (e) => {
            const modal = document.getElementById('settings-modal');
            if (e.target === modal) {
                this.closeSettings();
            }
        });

        // Conversations
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.addEventListener('click', () => {
                this.loadConversation(item.getAttribute('data-conversation-id'));
            });
        });
    }

    setupMessageInput() {
        const messageInput = document.getElementById('message-input');
        const charCount = document.querySelector('.char-count');
        const sendBtn = document.getElementById('send-btn');

        // Auto-resize
        messageInput.addEventListener('input', () => {
            this.autoResizeTextarea(messageInput);
            charCount.textContent = `${messageInput.value.length}/2000`;
        });

        // Soumission du formulaire
        document.getElementById('chat-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleMessageSubmit();
        });

        // Touche Entrée pour envoyer (avec Shift+Entrée pour nouvelle ligne)
        messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.handleMessageSubmit();
            }
        });

        // Dictée vocale
        document.getElementById('voice-btn').addEventListener('click', () => {
            this.toggleVoiceRecognition();
        });
    }

    autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
    }

    async handleMessageSubmit() {
        const messageInput = document.getElementById('message-input');
        const message = messageInput.value.trim();

        if (!message || this.isLoading) return;

        this.sendMessage(message);
        messageInput.value = '';
        this.autoResizeTextarea(messageInput);
        document.querySelector('.char-count').textContent = '0/2000';
    }

    async sendMessage(message) {
        this.isLoading = true;
        
        // Afficher le message de l'utilisateur
        this.displayUserMessage(message);

        // Afficher l'indicateur de frappe
        this.showTypingIndicator();

        // Désactiver l'entrée
        this.setInputEnabled(false);

        try {
            const response = await this.sendMessageToAPI(message);
            this.displayAssistantMessage(response);
            
            // Sauvegarder la conversation
            await this.saveConversation(message, response);
            
        } catch (error) {
            this.displayErrorMessage('Désolé, une erreur est survenue. Veuillez réessayer.');
            console.error('Erreur assistant IA:', error);
        } finally {
            this.isLoading = false;
            this.hideTypingIndicator();
            this.setInputEnabled(true);
        }
    }

    displayUserMessage(message) {
        const chatMessages = document.getElementById('chat-messages');
        const messageElement = this.createMessageElement('user', message);
        chatMessages.appendChild(messageElement);
        this.scrollToBottom();
    }

    displayAssistantMessage(message) {
        const chatMessages = document.getElementById('chat-messages');
        const messageElement = this.createMessageElement('assistant', message);
        
        // Remplacer l'indicateur de frappe
        const typingIndicator = document.querySelector('.typing-indicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
        
        chatMessages.appendChild(messageElement);
        this.scrollToBottom();
    }

    displayErrorMessage(message) {
        const chatMessages = document.getElementById('chat-messages');
        const errorElement = this.createMessageElement('assistant', message);
        errorElement.classList.add('error');
        
        const typingIndicator = document.querySelector('.typing-indicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
        
        chatMessages.appendChild(errorElement);
        this.scrollToBottom();
    }

    createMessageElement(sender, content) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}`;
        
        const avatar = document.createElement('div');
        avatar.className = 'message-avatar';
        avatar.textContent = sender === 'user' ? '👤' : '🤖';
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        
        const textDiv = document.createElement('div');
        textDiv.className = 'message-text';
        textDiv.innerHTML = this.formatMessage(content);
        
        const timeDiv = document.createElement('div');
        timeDiv.className = 'message-time';
        timeDiv.textContent = new Date().toLocaleTimeString('fr-FR', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        contentDiv.appendChild(textDiv);
        contentDiv.appendChild(timeDiv);
        messageDiv.appendChild(avatar);
        messageDiv.appendChild(contentDiv);
        
        return messageDiv;
    }

    formatMessage(content) {
        // Conversion basique du markdown
        return content
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code>$1</code>')
            .replace(/\n/g, '<br>')
            .replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" target="_blank">$1</a>');
    }

    showTypingIndicator() {
        const chatMessages = document.getElementById('chat-messages');
        const typingDiv = document.createElement('div');
        typingDiv.className = 'typing-indicator active';
        typingDiv.innerHTML = `
            <div class="message assistant">
                <div class="message-avatar">🤖</div>
                <div class="message-content">
                    <div class="message-text">
                        <span class="loading-dots">L'assistant rédige une réponse</span>
                    </div>
                </div>
            </div>
        `;
        chatMessages.appendChild(typingDiv);
        this.scrollToBottom();
    }

    hideTypingIndicator() {
        const typingIndicator = document.querySelector('.typing-indicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }

    scrollToBottom() {
        const chatMessages = document.getElementById('chat-messages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    setInputEnabled(enabled) {
        const messageInput = document.getElementById('message-input');
        const sendBtn = document.getElementById('send-btn');
        
        messageInput.disabled = !enabled;
        sendBtn.disabled = !enabled;
        
        if (!enabled) {
            sendBtn.innerHTML = '<span>⏳</span>';
        } else {
            sendBtn.innerHTML = '<span>📤</span>';
        }
    }

    async sendMessageToAPI(message) {
        // Simulation de l'API IA - À remplacer par un vrai appel API
        return new Promise((resolve) => {
            setTimeout(() => {
                const responses = {
                    'Explique-moi les bases de PHP': `PHP (Hypertext Preprocessor) est un langage de script côté serveur conçu pour le développement web. Voici les bases :

<strong>Syntaxe de base :</strong>
\`\`\`php
<?php
// Commentaire sur une ligne
echo "Hello World!";

// Variables
$nom = "Jean";
$age = 25;

// Conditions
if ($age >= 18) {
    echo "Majeur";
} else {
    echo "Mineur";
}

// Boucles
for ($i = 0; $i < 5; $i++) {
    echo $i;
}
?>
\`\`\`

<strong>Concepts importants :</strong>
• Variables (commencent par $)
• Tableaux associatifs
• Fonctions
• Classes et objets
• Gestion des formulaires
• Connexion aux bases de données

Souhaitez-vous que je détaille un point spécifique ?`,

                    'Propose-moi un plan d\'étude pour le développement web': `Voici un plan d'étude structuré pour le développement web :

<strong>🚀 Phase 1 : Fondamentaux (4-6 semaines)</strong>
• HTML5 - Structure sémantique
• CSS3 - Flexbox et Grid
• JavaScript basique
• Git et GitHub

<strong>🎯 Phase 2 : Frontend (6-8 semaines)</strong>
• JavaScript avancé (ES6+)
• React.js ou Vue.js
• Responsive Design
• Accessibilité web

<strong>🔧 Phase 3 : Backend (8-10 semaines)</strong>
• Node.js ou PHP
• Bases de données (SQL/NoSQL)
• API REST
• Authentification

<strong>⚡ Phase 4 : Avancé (4-6 semaines)</strong>
• Testing (Jest, Cypress)
• Déploiement
• Performance
• Sécurité

Voulez-vous que je personnalise ce plan selon vos objectifs ?`,

                    'default': `Je comprends votre demande concernant "${message}". 

En tant qu'assistant IA pédagogique d'ADH, je peux vous aider à approfondir ce sujet. Voici quelques points que je pourrais développer :

• Explications détaillées avec des exemples pratiques
• Ressources d'apprentissage recommandées
• Exercices pour pratiquer
• Projets concrets à réaliser
• Conseils pour progresser efficacement

Pouvez-vous me préciser quel aspect vous intéresse le plus ?`
                };

                resolve(responses[message] || responses['default']);
            }, 2000);
        });
    }

    async saveConversation(userMessage, assistantResponse) {
        // Sauvegarde simulée - À implémenter avec une vraie API
        console.log('Conversation sauvegardée:', { userMessage, assistantResponse });
    }

    startNewConversation() {
        this.currentConversationId = null;
        document.getElementById('chat-messages').innerHTML = `
            <div class="message assistant">
                <div class="message-avatar">🤖</div>
                <div class="message-content">
                    <div class="message-text">
                        Bonjour ! 👋<br><br>
                        Je suis votre assistant IA pédagogique. Comment puis-je vous aider aujourd'hui ?
                    </div>
                    <div class="message-time">${new Date().toLocaleTimeString('fr-FR')}</div>
                </div>
            </div>
        `;
    }

    loadConversation(conversationId) {
        // Chargement simulé - À implémenter avec une vraie API
        console.log('Chargement conversation:', conversationId);
        this.currentConversationId = conversationId;
        
        // Mettre à jour l'interface
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.classList.remove('active');
        });
        document.querySelector(`[data-conversation-id="${conversationId}"]`).classList.add('active');
    }

    loadInitialConversation() {
        // Charger la dernière conversation ou en créer une nouvelle
        const lastConversation = document.querySelector('.conversation-item');
        if (lastConversation) {
            this.loadConversation(lastConversation.getAttribute('data-conversation-id'));
        }
    }

    handleFeature(feature) {
        const prompts = {
            'parcours': "Je souhaite créer un parcours d'apprentissage personnalisé. Pouvez-vous m'aider ?",
            'analyse': "Analysez mes compétences actuelles et recommandez-moi des axes d'amélioration.",
            'orientation': "Quels métiers du numérique correspondent à mon profil et mes intérêts ?",
            'exercices': "J'ai besoin d'aide pour résoudre un exercice ou comprendre un concept difficile."
        };

        this.sendMessage(prompts[feature]);
    }

    openSettings() {
        document.getElementById('settings-modal').style.display = 'block';
    }

    closeSettings() {
        document.getElementById('settings-modal').style.display = 'none';
    }

    toggleVoiceRecognition() {
        // Implémentation basique de la reconnaissance vocale
        if (!('webkitSpeechRecognition' in window)) {
            alert('La reconnaissance vocale n\'est pas supportée par votre navigateur.');
            return;
        }

        const recognition = new webkitSpeechRecognition();
        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.lang = 'fr-FR';

        recognition.onstart = () => {
            document.getElementById('voice-btn').style.background = 'var(--couleur-success)';
        };

        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            document.getElementById('message-input').value = transcript;
            this.autoResizeTextarea(document.getElementById('message-input'));
        };

        recognition.onerror = (event) => {
            console.error('Erreur reconnaissance vocale:', event.error);
        };

        recognition.onend = () => {
            document.getElementById('voice-btn').style.background = '';
        };

        recognition.start();
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    window.assistantManager = new AssistantIAManager();
});

// Fonctions utilitaires pour l'IA
class AIUtils {
    static async generateLearningPath(competences, objectifs) {
        // Génération de parcours d'apprentissage personnalisé
        return {
            phases: [
                {
                    titre: "Fondamentaux",
                    duree: "4 semaines",
                    competences: ["HTML", "CSS", "JavaScript basique"],
                    ressources: ["Cours ADH HTML/CSS", "Projet portfolio"]
                },
                {
                    titre: "Développement Frontend",
                    duree: "6 semaines",
                    competences: ["React", "Responsive Design", "API REST"],
                    ressources: ["Cours ADH React", "Projet application météo"]
                }
            ]
        };
    }

    static async analyzeSkills(progressData) {
        // Analyse des compétences basée sur la progression
        return {
            forces: ["Logique algorithmique", "Résolution de problèmes"],
            ameliorations: ["Design patterns", "Tests unitaires"],
            recommandations: [
                "Pratiquer les algorithmes sur LeetCode",
                "Suivre le cours avancé JavaScript"
            ]
        };
    }

    static async getCareerRecommendations(interests, skills) {
        // Recommandations de carrière basées sur les intérêts et compétences
        return [
            {
                metier: "Développeur Full Stack",
                description: "Développement frontend et backend",
                competencesRequises: ["JavaScript", "Node.js", "React", "Base de données"],
                match: 85
            },
            {
                metier: "Data Analyst",
                description: "Analyse et visualisation de données",
                competencesRequises: ["Python", "SQL", "Statistiques", "Visualisation"],
                match: 70
            }
        ];
    }
}