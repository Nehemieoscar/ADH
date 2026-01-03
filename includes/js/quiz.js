// Gestion des quiz
class QuizManager {
    constructor() {
        this.currentQuiz = null;
        this.currentQuestionIndex = 0;
        this.userAnswers = [];
        this.timer = null;
        this.timeLeft = 0;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadQuizData();
    }

    setupEventListeners() {
        // Navigation entre les questions
        document.getElementById('prev-btn')?.addEventListener('click', () => {
            this.previousQuestion();
        });

        document.getElementById('next-btn')?.addEventListener('click', () => {
            this.nextQuestion();
        });

        document.getElementById('submit-quiz')?.addEventListener('click', () => {
            this.submitQuiz();
        });

        // Sélection des options
        document.addEventListener('click', (e) => {
            if (e.target.closest('.option-item')) {
                const optionItem = e.target.closest('.option-item');
                this.selectOption(optionItem);
            }
        });

        // Raccourcis clavier
        document.addEventListener('keydown', (e) => {
            if (e.key >= '1' && e.key <= '9') {
                const index = parseInt(e.key) - 1;
                this.selectOptionByIndex(index);
            }
        });
    }

    async loadQuizData() {
        const quizId = this.getQuizIdFromURL();
        if (!quizId) return;

        try {
            // Simulation du chargement des données du quiz
            this.currentQuiz = await this.fetchQuizData(quizId);
            this.initializeQuiz();
        } catch (error) {
            console.error('Erreur lors du chargement du quiz:', error);
            this.showError('Impossible de charger le quiz');
        }
    }

    async fetchQuizData(quizId) {
        // Simulation - À remplacer par un appel API réel
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve({
                    id: quizId,
                    titre: "Quiz JavaScript Avancé",
                    description: "Testez vos connaissances en JavaScript moderne",
                    duree: 30, // minutes
                    questions: [
                        {
                            id: 1,
                            texte: "Quelle est la différence entre `let` et `const` en JavaScript ?",
                            type: "choix_multiple",
                            options: [
                                { id: 1, texte: "`let` permet de déclarer des variables mutables, `const` des constantes" },
                                { id: 2, texte: "`let` est pour les nombres, `const` pour les chaînes" },
                                { id: 3, texte: "Il n'y a pas de différence" },
                                { id: 4, texte: "`let` est plus rapide que `const`" }
                            ],
                            reponse_correcte: [1],
                            points: 10
                        },
                        {
                            id: 2,
                            texte: "Qu'affiche le code suivant : `console.log(typeof NaN)` ?",
                            type: "choix_unique",
                            options: [
                                { id: 1, texte: "'number'" },
                                { id: 2, texte: "'NaN'" },
                                { id: 3, texte: "'undefined'" },
                                { id: 4, texte: "'string'" }
                            ],
                            reponse_correcte: [1],
                            points: 10
                        },
                        {
                            id: 3,
                            texte: "Quelles méthodes permettent de cloner un objet en JavaScript ? (choix multiple)",
                            type: "choix_multiple",
                            options: [
                                { id: 1, texte: "Object.assign()" },
                                { id: 2, texte: "JSON.parse(JSON.stringify(obj))" },
                                { id: 3, texte: "obj.clone()" },
                                { id: 4, texte: "L'opérateur spread {...obj}" }
                            ],
                            reponse_correcte: [1, 2, 4],
                            points: 15
                        }
                    ]
                });
            }, 1000);
        });
    }

    initializeQuiz() {
        this.userAnswers = new Array(this.currentQuiz.questions.length).fill(null);
        this.currentQuestionIndex = 0;
        this.timeLeft = this.currentQuiz.duree * 60; // Convertir en secondes

        this.updateQuizInterface();
        this.startTimer();
        this.displayCurrentQuestion();
    }

    updateQuizInterface() {
        // Mettre à jour le titre et la progression
        document.getElementById('quiz-title').textContent = this.currentQuiz.titre;
        this.updateProgress();
        this.updateNavigation();
    }

    displayCurrentQuestion() {
        const question = this.currentQuiz.questions[this.currentQuestionIndex];
        const questionCard = document.getElementById('question-card');
        
        questionCard.innerHTML = `
            <div class="question-header">
                <div class="question-text">${question.texte}</div>
            </div>
            <div class="options-list">
                ${question.options.map((option, index) => `
                    <div class="option-item" data-option-id="${option.id}">
                        <div class="option-marker">${String.fromCharCode(65 + index)}</div>
                        <div class="option-text">${option.texte}</div>
                    </div>
                `).join('')}
            </div>
        `;

        // Restaurer la sélection précédente
        const previousAnswer = this.userAnswers[this.currentQuestionIndex];
        if (previousAnswer) {
            previousAnswer.forEach(optionId => {
                const optionItem = questionCard.querySelector(`[data-option-id="${optionId}"]`);
                if (optionItem) {
                    optionItem.classList.add('selected');
                }
            });
        }

        this.updateNavigation();
    }

    selectOption(optionItem) {
        const question = this.currentQuiz.questions[this.currentQuestionIndex];
        const optionId = parseInt(optionItem.getAttribute('data-option-id'));

        if (question.type === 'choix_unique') {
            // Désélectionner toutes les autres options
            optionItem.parentElement.querySelectorAll('.option-item').forEach(item => {
                item.classList.remove('selected');
            });
            optionItem.classList.add('selected');
            this.userAnswers[this.currentQuestionIndex] = [optionId];
        } else {
            // Choix multiple - basculer la sélection
            optionItem.classList.toggle('selected');
            const selectedOptions = Array.from(optionItem.parentElement.querySelectorAll('.option-item.selected'))
                .map(item => parseInt(item.getAttribute('data-option-id')));
            this.userAnswers[this.currentQuestionIndex] = selectedOptions.length > 0 ? selectedOptions : null;
        }

        this.updateNavigation();
    }

    selectOptionByIndex(index) {
        const optionItems = document.querySelectorAll('.option-item');
        if (index < optionItems.length) {
            this.selectOption(optionItems[index]);
        }
    }

    previousQuestion() {
        if (this.currentQuestionIndex > 0) {
            this.currentQuestionIndex--;
            this.displayCurrentQuestion();
        }
    }

    nextQuestion() {
        if (this.currentQuestionIndex < this.currentQuiz.questions.length - 1) {
            this.currentQuestionIndex++;
            this.displayCurrentQuestion();
        }
    }

    updateProgress() {
        const progressFill = document.querySelector('.progress-fill');
        const progressText = document.querySelector('.progress-text');
        const progress = ((this.currentQuestionIndex + 1) / this.currentQuiz.questions.length) * 100;

        if (progressFill) {
            progressFill.style.width = `${progress}%`;
        }

        if (progressText) {
            progressText.innerHTML = `
                <span>Question ${this.currentQuestionIndex + 1} sur ${this.currentQuiz.questions.length}</span>
                <span>${Math.round(progress)}% complété</span>
            `;
        }
    }

    updateNavigation() {
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        const submitBtn = document.getElementById('submit-quiz');

        if (prevBtn) {
            prevBtn.disabled = this.currentQuestionIndex === 0;
        }

        if (nextBtn) {
            const hasAnswer = this.userAnswers[this.currentQuestionIndex] !== null;
            nextBtn.disabled = this.currentQuestionIndex === this.currentQuiz.questions.length - 1;
            nextBtn.textContent = this.currentQuestionIndex === this.currentQuiz.questions.length - 1 ? 'Terminer' : 'Suivant';
        }

        if (submitBtn) {
            const allAnswered = this.userAnswers.every(answer => answer !== null);
            submitBtn.style.display = this.currentQuestionIndex === this.currentQuiz.questions.length - 1 ? 'block' : 'none';
            submitBtn.disabled = !allAnswered;
        }
    }

    startTimer() {
        this.updateTimerDisplay();

        this.timer = setInterval(() => {
            this.timeLeft--;

            if (this.timeLeft <= 0) {
                this.timeLeft = 0;
                this.submitQuiz();
            }

            this.updateTimerDisplay();
        }, 1000);
    }

    updateTimerDisplay() {
        const timerElement = document.getElementById('quiz-timer');
        if (timerElement) {
            const minutes = Math.floor(this.timeLeft / 60);
            const seconds = this.timeLeft % 60;
            timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

            // Changement de couleur selon le temps restant
            if (this.timeLeft < 300) { // 5 minutes
                timerElement.style.background = 'var(--couleur-danger)';
            } else if (this.timeLeft < 600) { // 10 minutes
                timerElement.style.background = 'var(--couleur-warning)';
            }
        }
    }

    async submitQuiz() {
        if (this.timer) {
            clearInterval(this.timer);
        }

        // Calculer le score
        const score = this.calculateScore();
        
        // Afficher les résultats
        this.displayResults(score);
        
        // Sauvegarder les résultats
        await this.saveQuizResults(score);
    }

    calculateScore() {
        let totalPoints = 0;
        let earnedPoints = 0;

        this.currentQuiz.questions.forEach((question, index) => {
            totalPoints += question.points;
            const userAnswer = this.userAnswers[index];

            if (userAnswer && this.isAnswerCorrect(question, userAnswer)) {
                earnedPoints += question.points;
            }
        });

        return {
            score: Math.round((earnedPoints / totalPoints) * 100),
            earnedPoints,
            totalPoints,
            correctAnswers: this.countCorrectAnswers(),
            totalQuestions: this.currentQuiz.questions.length
        };
    }

    isAnswerCorrect(question, userAnswer) {
        if (!userAnswer) return false;

        const correctAnswers = question.reponse_correcte;
        
        // Vérifier que toutes les réponses correctes sont sélectionnées
        // et qu'aucune réponse incorrecte n'est sélectionnée
        const allCorrectSelected = correctAnswers.every(correctId => 
            userAnswer.includes(correctId)
        );
        const noIncorrectSelected = userAnswer.every(userId =>
            correctAnswers.includes(userId)
        );

        return allCorrectSelected && noIncorrectSelected;
    }

    countCorrectAnswers() {
        return this.currentQuiz.questions.reduce((count, question, index) => {
            const userAnswer = this.userAnswers[index];
            return count + (this.isAnswerCorrect(question, userAnswer) ? 1 : 0);
        }, 0);
    }

    displayResults(score) {
        const quizInterface = document.querySelector('.quiz-interface');
        quizInterface.innerHTML = `
            <div class="resultats-container">
                <div class="resultat-score-circle" style="--score-percentage: ${score.score}%">
                    <div class="score-text">
                        <span>${score.score}%</span>
                        <small>Score</small>
                    </div>
                </div>
                
                <div class="score-message">
                    <h2>${this.getScoreMessage(score.score)}</h2>
                    <p>Vous avez répondu correctement à ${score.correctAnswers} sur ${score.totalQuestions} questions</p>
                </div>

                <div class="badges-earned">
                    ${score.score >= 80 ? '<span class="badge-quiz">🏆 Excellent</span>' : ''}
                    ${score.score >= 60 ? '<span class="badge-quiz">⭐ Bon travail</span>' : ''}
                    <span class="badge-quiz">📚 ${score.earnedPoints}/${score.totalPoints} points</span>
                </div>

                <div class="quiz-actions">
                    <button id="review-quiz" class="btn btn-outline">📝 Voir les corrections</button>
                    <button id="retry-quiz" class="btn btn-primary">🔄 Recommencer</button>
                    <a href="quiz.php" class="btn btn-secondary">📚 Retour aux quiz</a>
                </div>
            </div>
        `;

        // Ajouter les écouteurs d'événements pour les nouveaux boutons
        document.getElementById('review-quiz').addEventListener('click', () => {
            this.showReview();
        });

        document.getElementById('retry-quiz').addEventListener('click', () => {
            this.retryQuiz();
        });
    }

    getScoreMessage(score) {
        if (score >= 90) return "Excellent ! 🎉";
        if (score >= 80) return "Très bien ! 👍";
        if (score >= 70) return "Bon travail ! 👏";
        if (score >= 60) return "Pas mal ! 💪";
        if (score >= 50) return "Peut mieux faire 📚";
        return "Continuez à pratiquer ! 🎯";
    }

    showReview() {
        // Afficher chaque question avec les corrections
        const quizInterface = document.querySelector('.quiz-interface');
        let reviewHTML = `
            <div class="quiz-header">
                <h2>Correction du quiz</h2>
                <a href="quiz.php" class="btn btn-outline">📚 Retour aux quiz</a>
            </div>
        `;

        this.currentQuiz.questions.forEach((question, index) => {
            const userAnswer = this.userAnswers[index];
            const isCorrect = this.isAnswerCorrect(question, userAnswer);

            reviewHTML += `
                <div class="question-card ${isCorrect ? 'correct' : 'incorrect'}">
                    <div class="question-header">
                        <div class="question-text">${question.texte}</div>
                        <div class="question-status ${isCorrect ? 'correct' : 'incorrect'}">
                            ${isCorrect ? '✅ Correct' : '❌ Incorrect'} - ${question.points} points
                        </div>
                    </div>
                    <div class="options-list">
                        ${question.options.map(option => {
                            const isUserSelected = userAnswer && userAnswer.includes(option.id);
                            const isCorrectAnswer = question.reponse_correcte.includes(option.id);
                            let className = 'option-item';

                            if (isCorrectAnswer) className += ' correct';
                            if (isUserSelected && !isCorrectAnswer) className += ' incorrect';
                            if (isUserSelected) className += ' selected';

                            return `
                                <div class="${className}">
                                    <div class="option-marker">${String.fromCharCode(65 + question.options.indexOf(option))}</div>
                                    <div class="option-text">${option.texte}</div>
                                    ${isCorrectAnswer ? '<div class="option-feedback">✓ Bonne réponse</div>' : ''}
                                    ${isUserSelected && !isCorrectAnswer ? '<div class="option-feedback">✗ Votre réponse</div>' : ''}
                                </div>
                            `;
                        }).join('')}
                    </div>
                    ${!isCorrect ? `
                        <div class="explanation">
                            <strong>Explication :</strong> 
                            La bonne réponse était ${question.reponse_correcte.map(id => 
                                String.fromCharCode(64 + question.options.findIndex(opt => opt.id === id) + 1)
                            ).join(', ')}
                        </div>
                    ` : ''}
                </div>
            `;
        });

        quizInterface.innerHTML = reviewHTML;
    }

    retryQuiz() {
        this.initializeQuiz();
    }

    async saveQuizResults(score) {
        // Sauvegarde simulée - À implémenter avec une vraie API
        try {
            const response = await fetch('save-quiz-results.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    quizId: this.currentQuiz.id,
                    score: score.score,
                    answers: this.userAnswers,
                    timeSpent: (this.currentQuiz.duree * 60) - this.timeLeft
                })
            });

            if (!response.ok) {
                throw new Error('Erreur lors de la sauvegarde');
            }
        } catch (error) {
            console.error('Erreur sauvegarde résultats:', error);
        }
    }

    getQuizIdFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('id');
    }

    showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-error';
        errorDiv.textContent = message;
        document.querySelector('.dashboard-content').prepend(errorDiv);
    }
}

// Gestion des défis quotidiens
class DailyChallengeManager {
    constructor() {
        this.init();
    }

    init() {
        this.checkDailyChallenge();
        this.setupEventListeners();
    }

    setupEventListeners() {
        document.querySelectorAll('.challenge-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.startDailyChallenge();
            });
        });
    }

    checkDailyChallenge() {
        const lastCompletion = localStorage.getItem('lastDailyChallenge');
        const today = new Date().toDateString();

        if (lastCompletion === today) {
            this.markChallengeAsCompleted();
        }
    }

    startDailyChallenge() {
        // Démarrer le défi du jour
        window.location.href = 'passer-quiz.php?type=daily';
    }

    markChallengeAsCompleted() {
        const challengeCard = document.querySelector('.challenge-card');
        if (challengeCard) {
            challengeCard.innerHTML = `
                <h2>🎯 Défi du jour</h2>
                <div class="challenge-content">
                    <h3>✅ Défi complété !</h3>
                    <p>Revenez demain pour un nouveau défi</p>
                    <div class="challenge-reward">
                        <span>🎁 +10 points gagnés</span>
                    </div>
                </div>
            `;
        }
    }

    awardPoints(points) {
        // Attribuer les points d'expérience
        const currentXP = parseInt(localStorage.getItem('userXP') || '0');
        localStorage.setItem('userXP', (currentXP + points).toString());
        
        // Marquer comme complété pour aujourd'hui
        localStorage.setItem('lastDailyChallenge', new Date().toDateString());
        
        this.markChallengeAsCompleted();
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaire principal des quiz
    if (document.querySelector('.quiz-interface')) {
        window.quizManager = new QuizManager();
    }

    // Gestionnaire des défis quotidiens
    window.challengeManager = new DailyChallengeManager();
});