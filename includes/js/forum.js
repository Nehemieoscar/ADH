// Gestion du forum
class ForumManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupMessageActions();
        this.setupSearch();
    }

    setupEventListeners() {
        // Gestion des likes
        const likeButtons = document.querySelectorAll('.like-btn');
        likeButtons.forEach(btn => {
            btn.addEventListener('click', (e) => this.handleLike(e));
        });

        // Gestion des citations
        const quoteButtons = document.querySelectorAll('.quote-btn');
        quoteButtons.forEach(btn => {
            btn.addEventListener('click', (e) => this.handleQuote(e));
        });

        // Prévisualisation des messages
        const messageTextarea = document.getElementById('message-contenu');
        if (messageTextarea) {
            messageTextarea.addEventListener('input', (e) => this.updatePreview(e));
        }
    }

    setupMessageActions() {
        // Actions contextuelles sur les messages
        const messageItems = document.querySelectorAll('.message-item');
        messageItems.forEach(item => {
            item.addEventListener('mouseenter', () => this.showMessageActions(item));
            item.addEventListener('mouseleave', () => this.hideMessageActions(item));
        });
    }

    setupSearch() {
        const searchForm = document.getElementById('forum-search');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => this.handleSearch(e));
        }
    }

    handleLike(e) {
        e.preventDefault();
        const button = e.target.closest('.like-btn');
        const messageId = button.getAttribute('data-message-id');
        
        // Animation visuelle
        button.classList.add('liking');
        button.innerHTML = '❤️';
        
        // Simuler l'envoi au serveur
        setTimeout(() => {
            button.classList.remove('liking');
            button.innerHTML = '❤️ 1';
        }, 500);
    }

    handleQuote(e) {
        e.preventDefault();
        const button = e.target.closest('.quote-btn');
        const messageId = button.getAttribute('data-message-id');
        const author = button.getAttribute('data-author');
        const content = button.getAttribute('data-content');
        
        const messageTextarea = document.getElementById('message-contenu');
        if (messageTextarea) {
            const quote = `[quote="${author}"]\n${content}\n[/quote]\n\n`;
            messageTextarea.value += quote;
            messageTextarea.focus();
            
            // Scroll vers le formulaire
            messageTextarea.scrollIntoView({ behavior: 'smooth' });
        }
    }

    updatePreview(e) {
        const preview = document.getElementById('message-preview');
        if (preview) {
            // Conversion basique du markdown
            let content = e.target.value;
            content = content.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            content = content.replace(/\*(.*?)\*/g, '<em>$1</em>');
            content = content.replace(/`(.*?)`/g, '<code>$1</code>');
            content = content.replace(/\n/g, '<br>');
            
            preview.innerHTML = content;
        }
    }

    showMessageActions(item) {
        const actions = item.querySelector('.message-actions');
        if (actions) {
            actions.style.opacity = '1';
        }
    }

    hideMessageActions(item) {
        const actions = item.querySelector('.message-actions');
        if (actions) {
            actions.style.opacity = '0';
        }
    }

    handleSearch(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const query = formData.get('query');
        
        // Animation de recherche
        const resultsContainer = document.querySelector('.search-results');
        if (resultsContainer) {
            resultsContainer.innerHTML = '<div class="loading">Recherche en cours...</div>';
        }
        
        // Redirection ou affichage des résultats
        window.location.href = `recherche-forum.php?q=${encodeURIComponent(query)}`;
    }

    // Fonction pour marquer un sujet comme lu
    markTopicAsRead(topicId) {
        const readTopics = JSON.parse(localStorage.getItem('readTopics') || '[]');
        if (!readTopics.includes(topicId)) {
            readTopics.push(topicId);
            localStorage.setItem('readTopics', JSON.stringify(readTopics));
        }
    }

    // Fonction pour vérifier si un sujet est lu
    isTopicRead(topicId) {
        const readTopics = JSON.parse(localStorage.getItem('readTopics') || '[]');
        return readTopics.includes(topicId);
    }
}

// Gestion des éditeurs de texte enrichi
class TextEditor {
    constructor(textareaId) {
        this.textarea = document.getElementById(textareaId);
        this.init();
    }

    init() {
        if (!this.textarea) return;

        this.createToolbar();
        this.setupToolbarEvents();
    }

    createToolbar() {
        const toolbar = document.createElement('div');
        toolbar.className = 'editor-toolbar';
        toolbar.innerHTML = `
            <button type="button" data-action="bold" title="Gras">B</button>
            <button type="button" data-action="italic" title="Italique">I</button>
            <button type="button" data-action="code" title="Code">&lt;/&gt;</button>
            <button type="button" data-action="link" title="Lien">🔗</button>
            <button type="button" data-action="quote" title="Citation">❝</button>
        `;

        this.textarea.parentNode.insertBefore(toolbar, this.textarea);
    }

    setupToolbarEvents() {
        const buttons = this.textarea.previousElementSibling.querySelectorAll('button');
        buttons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleAction(btn.getAttribute('data-action'));
            });
        });
    }

    handleAction(action) {
        const start = this.textarea.selectionStart;
        const end = this.textarea.selectionEnd;
        const selectedText = this.textarea.value.substring(start, end);

        let newText = '';
        let cursorPos = start;

        switch (action) {
            case 'bold':
                newText = `**${selectedText}**`;
                cursorPos = start + 2;
                break;
            case 'italic':
                newText = `*${selectedText}*`;
                cursorPos = start + 1;
                break;
            case 'code':
                newText = `\`${selectedText}\``;
                cursorPos = start + 1;
                break;
            case 'link':
                newText = `[${selectedText}](url)`;
                cursorPos = start + 1;
                break;
            case 'quote':
                newText = `> ${selectedText}`;
                cursorPos = start + 2;
                break;
        }

        this.textarea.value = this.textarea.value.substring(0, start) + newText + this.textarea.value.substring(end);
        this.textarea.focus();
        this.textarea.setSelectionRange(cursorPos, cursorPos + selectedText.length);
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    new ForumManager();
    
    // Initialiser l'éditeur de texte si présent
    const messageTextarea = document.getElementById('message-contenu');
    if (messageTextarea) {
        new TextEditor('message-contenu');
    }

    // Gestion du chargement infini
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                loadMoreMessages();
            }
        });
    });

    const sentinel = document.querySelector('.load-more-sentinel');
    if (sentinel) {
        observer.observe(sentinel);
    }
});

// Fonction pour charger plus de messages
function loadMoreMessages() {
    const container = document.querySelector('.messages-list');
    const page = parseInt(container.getAttribute('data-page') || '1');
    
    // Simuler le chargement
    const loading = document.createElement('div');
    loading.className = 'loading';
    loading.textContent = 'Chargement...';
    container.appendChild(loading);

    // Simuler une requête AJAX
    setTimeout(() => {
        loading.remove();
        // Ajouter de nouveaux messages ici
        container.setAttribute('data-page', (page + 1).toString());
    }, 1000);
}