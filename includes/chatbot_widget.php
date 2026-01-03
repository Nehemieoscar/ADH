<!-- Widget Chatbot Interne -->
<div id="chatbot-widget" class="chatbot-widget">
    <div class="chatbot-header" onclick="toggleChatbot()">
        <div class="chatbot-title">
            <span style="font-size: 1.3rem;">🤖</span>
            <span>Assistant ADH</span>
        </div>
        <button class="chatbot-minimize" id="chatbot-minimize" onclick="toggleChatbot(event)">−</button>
    </div>
    
    <div class="chatbot-body" id="chatbot-body" style="display: none;">
        <div class="chatbot-messages" id="chatbot-messages">
            <div class="chatbot-message bot">
                <div class="message-content">
                    Bonjour 👋 ! Je suis l'Assistant ADH. Comment puis-je vous aider ?
                </div>
            </div>
        </div>
        
        <div class="chatbot-input-area">
            <input 
                type="text" 
                id="chatbot-input" 
                class="chatbot-input" 
                placeholder="Posez votre question..."
                onkeypress="handleChatbotKeypress(event)"
            >
            <button class="chatbot-send" onclick="sendChatbotMessage()">↑</button>
        </div>
    </div>
</div>

<style>
.chatbot-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 380px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 999;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    display: flex;
    flex-direction: column;
    max-height: 600px;
}

.chatbot-header {
    background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-secondaire));
    color: white;
    padding: 1.2rem;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
}

.chatbot-title {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    font-weight: 600;
    font-size: 1rem;
}

.chatbot-minimize {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

.chatbot-minimize:hover {
    background: rgba(255,255,255,0.3);
}

.chatbot-body {
    display: flex;
    flex-direction: column;
    height: 400px;
    background: white;
}

.chatbot-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1.2rem;
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.chatbot-message {
    display: flex;
    margin-bottom: 0.5rem;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.chatbot-message.bot {
    justify-content: flex-start;
}

.chatbot-message.user {
    justify-content: flex-end;
}

.message-content {
    max-width: 75%;
    padding: 0.7rem 1rem;
    border-radius: 12px;
    line-height: 1.4;
    font-size: 0.95rem;
}

.chatbot-message.bot .message-content {
    background: #f0f0f0;
    color: #333;
}

.chatbot-message.user .message-content {
    background: var(--couleur-primaire);
    color: white;
}

.message-suggestions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.suggestion-btn {
    background: #f0f0f0;
    border: 1px solid #ddd;
    padding: 0.5rem 0.8rem;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.85rem;
    text-align: left;
    transition: all 0.2s;
}

.suggestion-btn:hover {
    background: #e0e0e0;
    border-color: var(--couleur-primaire);
}

.chatbot-input-area {
    display: flex;
    gap: 0.5rem;
    padding: 1rem;
    background: #f9f9f9;
    border-top: 1px solid #eee;
    border-radius: 0 0 12px 12px;
}

.chatbot-input {
    flex: 1;
    padding: 0.7rem 1rem;
    border: 1px solid #ddd;
    border-radius: 20px;
    font-size: 0.95rem;
    outline: none;
    transition: border-color 0.2s;
}

.chatbot-input:focus {
    border-color: var(--couleur-primaire);
}

.chatbot-send {
    background: var(--couleur-primaire);
    color: white;
    border: none;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

.chatbot-send:hover {
    background: var(--couleur-secondaire);
}

@media (max-width: 480px) {
    .chatbot-widget {
        width: calc(100% - 40px);
        max-height: 70vh;
    }
    
    .chatbot-messages {
        height: auto;
        max-height: 300px;
    }
}

/* Scrollbar personnalisée */
.chatbot-messages::-webkit-scrollbar {
    width: 6px;
}

.chatbot-messages::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.chatbot-messages::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.chatbot-messages::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>

<script>
let chatbotState = {
    isOpen: false,
    userRole: '<?php echo $_SESSION['utilisateur_role'] ?? 'guest'; ?>',
    userName: '<?php echo $_SESSION['utilisateur_nom'] ?? 'Utilisateur'; ?>'
};

const chatbotResponses = {
    greeting: [
        "Bonjour ! Je suis l'Assistant ADH. Comment puis-je vous aider ?",
        "Bienvenue ! Posez-moi vos questions sur la plateforme."
    ],
    
    help: {
        etudiant: [
            "✅ Consulter mes cours",
            "✅ Voir ma progression",
            "✅ Envoyer un devoir",
            "✅ Poser une question",
            "✅ Contacter un prof"
        ],
        professeur: [
            "✅ Créer un cours",
            "✅ Consulter les devoirs",
            "✅ Noter les étudiants",
            "✅ Créer un quiz",
            "✅ Contacter l'admin"
        ],
        admin: [
            "✅ Gérer les utilisateurs",
            "✅ Consulter les rapports",
            "✅ Créer une formation",
            "✅ Envoyer des alertes",
            "✅ Voir les statistiques"
        ]
    },
    
    responses: {
        'consultation de cours': "Vous pouvez consulter vos cours dans le menu 'Mes Cours' ou 'ADH Online'.",
        'progression': "Consultez votre progression dans votre tableau de bord personnel.",
        'devoir': "Les devoirs peuvent être envoyés via la page du cours correspondant.",
        'question': "Vous pouvez poser des questions dans le forum ou contacter directement le professeur.",
        'contact': "Utilisez le formulaire de contact ou envoyez un email à support@adh.com",
        'technical': "Pour les problèmes techniques, contactez support@adh.com avec une capture d'écran.",
        'password': "Utilisez l'option 'Mot de passe oublié' sur la page de connexion.",
        'profil': "Modifiez votre profil dans les paramètres du tableau de bord."
    }
};

function toggleChatbot(e) {
    if (e) e.stopPropagation();
    const body = document.getElementById('chatbot-body');
    const minimize = document.getElementById('chatbot-minimize');
    
    chatbotState.isOpen = !chatbotState.isOpen;
    
    if (chatbotState.isOpen) {
        body.style.display = 'flex';
        minimize.textContent = '−';
        setTimeout(() => {
            const messages = document.getElementById('chatbot-messages');
            messages.scrollTop = messages.scrollHeight;
        }, 100);
    } else {
        body.style.display = 'none';
        minimize.textContent = '+';
    }
}

function sendChatbotMessage() {
    const input = document.getElementById('chatbot-input');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Ajouter le message utilisateur
    addChatbotMessage(message, 'user');
    input.value = '';
    
    // Générer la réponse
    setTimeout(() => {
        const response = generateChatbotResponse(message);
        addChatbotMessage(response, 'bot');
    }, 500);
}

function addChatbotMessage(message, sender, suggestions = null) {
    const messagesContainer = document.getElementById('chatbot-messages');
    
    const messageEl = document.createElement('div');
    messageEl.className = `chatbot-message ${sender}`;
    
    let content = `<div class="message-content">${escapeHtml(message)}</div>`;
    
    if (suggestions) {
        content += '<div class="message-suggestions">' +
            suggestions.map(s => `<button class="suggestion-btn" onclick="handleSuggestion('${escapeHtml(s)}')">${s}</button>`).join('') +
            '</div>';
    }
    
    messageEl.innerHTML = content;
    messagesContainer.appendChild(messageEl);
    
    // Scroller vers le bas
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function generateChatbotResponse(userMessage) {
    const msg = userMessage.toLowerCase();
    
    // Aide personnalisée par rôle
    if (msg.includes('aide') || msg.includes('help') || msg.includes('?')) {
        const helps = chatbotResponses.help[chatbotState.userRole] || chatbotResponses.help.etudiant;
        addChatbotMessage('Voici ce que je peux vous aider à faire :', 'bot', helps);
        return 'Cliquez sur une option pour plus de détails.';
    }
    
    // Chercher une réponse
    for (const [key, response] of Object.entries(chatbotResponses.responses)) {
        if (msg.includes(key)) {
            return response;
        }
    }
    
    // Réponse par défaut
    if (msg.includes('merci') || msg.includes('thanks')) {
        return 'De rien ! N\'hésitez pas si vous avez d\'autres questions.';
    }
    
    if (msg.includes('bonjour') || msg.includes('hi')) {
        return `Bonjour ${chatbotState.userName} ! Comment puis-je vous aider ?`;
    }
    
    return "Je ne suis pas certain de comprendre. Pouvez-vous reformuler ? Ou tapez 'aide' pour voir comment je peux vous aider.";
}

function handleSuggestion(suggestion) {
    const input = document.getElementById('chatbot-input');
    input.value = suggestion;
    sendChatbotMessage();
}

function handleChatbotKeypress(e) {
    if (e.key === 'Enter') {
        sendChatbotMessage();
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Auto-open sur première visite (optionnel)
// window.addEventListener('load', () => {
//     if (!localStorage.getItem('chatbot_first_visit')) {
//         setTimeout(() => toggleChatbot(), 2000);
//         localStorage.setItem('chatbot_first_visit', 'true');
//     }
// });
</script>
