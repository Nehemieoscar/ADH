// Gestion du mode enfants
class EnfantsManager {
    constructor() {
        this.currentGame = null;
        this.gameData = {
            maths: {
                title: "Mathématiques Magiques",
                description: "Résous des énigmes mathématiques amusantes",
                instructions: "Résous le problème mathématique le plus rapidement possible !"
            },
            logique: {
                title: "Défis Logiques", 
                description: "Développe ta logique avec des puzzles captivants",
                instructions: "Trouve la solution au puzzle logique !"
            },
            coding: {
                title: "Aventure Coding",
                description: "Apprends les bases de la programmation",
                instructions: "Assemble les blocs de code pour résoudre le défi !"
            },
            memory: {
                title: "Memory des Sciences",
                description: "Retrouve les paires et apprends les sciences",
                instructions: "Retrouve toutes les paires de cartes !"
            }
        };
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadProgress();
    }

    setupEventListeners() {
        // Boutons de jeu
        document.querySelectorAll('.play-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const gameCard = e.target.closest('.game-card');
                const gameType = gameCard.getAttribute('data-game');
                this.startGame(gameType);
            });
        });

        // Fermer le modal
        document.querySelector('.close-modal').addEventListener('click', () => {
            this.closeGame();
        });
    }

    startGame(gameType) {
        this.currentGame = gameType;
        this.openGameModal();
        this.loadGame(gameType);
    }

    openGameModal() {
        const modal = document.getElementById('game-modal');
        const gameData = this.gameData[this.currentGame];
        
        document.getElementById('game-title').textContent = gameData.title;
        modal.style.display = 'block';
    }

    closeGame() {
        document.getElementById('game-modal').style.display = 'none';
        this.currentGame = null;
        document.getElementById('game-container').innerHTML = '';
    }

    loadGame(gameType) {
        const gameContainer = document.getElementById('game-container');
        
        switch (gameType) {
            case 'maths':
                this.loadMathGame(gameContainer);
                break;
            case 'logique':
                this.loadLogicGame(gameContainer);
                break;
            case 'coding':
                this.loadCodingGame(gameContainer);
                break;
            case 'memory':
                this.loadMemoryGame(gameContainer);
                break;
        }
    }

    loadMathGame(container) {
        container.innerHTML = `
            <div class="math-game">
                <div class="game-instructions">
                    <p>${this.gameData.maths.instructions}</p>
                </div>
                <div class="math-problem">
                    <h2 id="problem-text">5 + 3 = ?</h2>
                </div>
                <div class="math-options">
                    <button class="math-option" data-answer="7">7</button>
                    <button class="math-option" data-answer="8">8</button>
                    <button class="math-option" data-answer="9">9</button>
                    <button class="math-option" data-answer="10">10</button>
                </div>
                <div class="game-feedback" id="math-feedback"></div>
                <div class="game-stats">
                    <span>Score: <span id="math-score">0</span></span>
                    <span>Temps: <span id="math-time">60</span>s</span>
                </div>
            </div>
        `;

        this.setupMathGame();
    }

    setupMathGame() {
        let score = 0;
        let timeLeft = 60;
        let timer;

        const updateProblem = () => {
            const num1 = Math.floor(Math.random() * 10) + 1;
            const num2 = Math.floor(Math.random() * 10) + 1;
            const operators = ['+', '-', '*'];
            const operator = operators[Math.floor(Math.random() * operators.length)];
            
            let answer;
            switch (operator) {
                case '+': answer = num1 + num2; break;
                case '-': answer = num1 - num2; break;
                case '*': answer = num1 * num2; break;
            }

            document.getElementById('problem-text').textContent = `${num1} ${operator} ${num2} = ?`;
            
            // Générer des options
            const options = [answer];
            while (options.length < 4) {
                const wrongAnswer = answer + Math.floor(Math.random() * 10) - 5;
                if (wrongAnswer !== answer && !options.includes(wrongAnswer)) {
                    options.push(wrongAnswer);
                }
            }
            
            // Mélanger les options
            options.sort(() => Math.random() - 0.5);
            
            const optionButtons = document.querySelectorAll('.math-option');
            optionButtons.forEach((btn, index) => {
                btn.textContent = options[index];
                btn.setAttribute('data-answer', options[index]);
                btn.classList.remove('correct', 'incorrect');
            });

            return answer;
        };

        let currentAnswer = updateProblem();

        document.querySelectorAll('.math-option').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const selectedAnswer = parseInt(e.target.getAttribute('data-answer'));
                const feedback = document.getElementById('math-feedback');

                if (selectedAnswer === currentAnswer) {
                    e.target.classList.add('correct');
                    feedback.textContent = 'Correct ! 🎉';
                    feedback.style.color = 'lightgreen';
                    score += 10;
                    document.getElementById('math-score').textContent = score;
                    
                    setTimeout(() => {
                        currentAnswer = updateProblem();
                        feedback.textContent = '';
                    }, 1000);
                } else {
                    e.target.classList.add('incorrect');
                    feedback.textContent = 'Essaie encore ! 💪';
                    feedback.style.color = 'lightcoral';
                }
            });
        });

        // Timer
        timer = setInterval(() => {
            timeLeft--;
            document.getElementById('math-time').textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(timer);
                this.endMathGame(score);
            }
        }, 1000);
    }

    endMathGame(score) {
        const container = document.getElementById('game-container');
        container.innerHTML = `
            <div class="game-results">
                <h2>🎮 Partie Terminée !</h2>
                <p>Ton score: <strong>${score}</strong> points</p>
                <div class="game-actions">
                    <button class="btn btn-primary" id="play-again">Rejouer</button>
                    <button class="btn btn-outline" onclick="enfantsManager.closeGame()">Quitter</button>
                </div>
            </div>
        `;

        document.getElementById('play-again').addEventListener('click', () => {
            this.startGame('maths');
        });

        this.saveProgress('maths', score);
    }

    loadLogicGame(container) {
        container.innerHTML = `
            <div class="logic-game">
                <div class="game-instructions">
                    <p>${this.gameData.logique.instructions}</p>
                </div>
                <div class="logic-puzzle">
                    <h3>Devine la suite logique:</h3>
                    <p>2, 4, 6, 8, ?</p>
                    <input type="number" id="logic-answer" placeholder="Ta réponse">
                    <button id="check-logic">Vérifier</button>
                </div>
                <div class="game-feedback" id="logic-feedback"></div>
            </div>
        `;

        document.getElementById('check-logic').addEventListener('click', () => {
            const answer = parseInt(document.getElementById('logic-answer').value);
            const feedback = document.getElementById('logic-feedback');

            if (answer === 10) {
                feedback.textContent = 'Bravo ! 🎉 La suite est +2 à chaque fois.';
                feedback.style.color = 'lightgreen';
                this.saveProgress('logique', 100);
            } else {
                feedback.textContent = 'Presque ! Essaie encore. 💡';
                feedback.style.color = 'lightcoral';
            }
        });
    }

    loadCodingGame(container) {
        container.innerHTML = `
            <div class="coding-game">
                <div class="game-instructions">
                    <p>${this.gameData.coding.instructions}</p>
                </div>
                <div class="coding-challenge">
                    <p>Fais avancer le robot jusqu'à la fin :</p>
                    <div class="coding-blocks">
                        <div class="block" data-command="avancer">Avancer</div>
                        <div class="block" data-command="tourner">Tourner</div>
                        <div class="block" data-command="repetition">Répéter</div>
                    </div>
                    <div class="coding-area" id="coding-area"></div>
                    <button id="run-code">Exécuter</button>
                </div>
            </div>
        `;

        this.setupCodingGame();
    }

    setupCodingGame() {
        let selectedBlocks = [];

        document.querySelectorAll('.coding-blocks .block').forEach(block => {
            block.addEventListener('click', () => {
                const command = block.getAttribute('data-command');
                const clonedBlock = block.cloneNode(true);
                document.getElementById('coding-area').appendChild(clonedBlock);
                selectedBlocks.push(command);
            });
        });

        document.getElementById('run-code').addEventListener('click', () => {
            if (selectedBlocks.join(',') === 'avancer,avancer,avancer,tourner,avancer') {
                alert('Félicitations ! 🎉 Le robot a atteint la destination.');
                this.saveProgress('coding', 100);
            } else {
                alert('Le robot est perdu ! 😅 Essaie une autre combinaison.');
            }
        });
    }

    loadMemoryGame(container) {
        container.innerHTML = `
            <div class="memory-game">
                <div class="game-instructions">
                    <p>${this.gameData.memory.instructions}</p>
                </div>
                <div class="memory-grid" id="memory-grid"></div>
                <div class="game-stats">
                    <span>Paires trouvées: <span id="pairs-found">0</span>/8</span>
                    <span>Essais: <span id="attempts">0</span></span>
                </div>
            </div>
        `;

        this.setupMemoryGame();
    }

    setupMemoryGame() {
        const symbols = ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼'];
        const cards = [...symbols, ...symbols];
        
        // Mélanger les cartes
        cards.sort(() => Math.random() - 0.5);

        const grid = document.getElementById('memory-grid');
        let flippedCards = [];
        let matchedPairs = 0;
        let attempts = 0;

        cards.forEach((symbol, index) => {
            const card = document.createElement('div');
            card.className = 'memory-card';
            card.innerHTML = `
                <div class="card-front">?</div>
                <div class="card-back">${symbol}</div>
            `;
            card.addEventListener('click', () => this.flipCard(card, symbol));
            grid.appendChild(card);
        });

        this.flipCard = (card, symbol) => {
            if (card.classList.contains('flipped') || flippedCards.length >= 2) {
                return;
            }

            card.classList.add('flipped');
            flippedCards.push({ card, symbol });

            if (flippedCards.length === 2) {
                attempts++;
                document.getElementById('attempts').textContent = attempts;

                const [card1, card2] = flippedCards;
                if (card1.symbol === card2.symbol) {
                    // Paire trouvée
                    matchedPairs++;
                    document.getElementById('pairs-found').textContent = matchedPairs;
                    flippedCards = [];

                    if (matchedPairs === symbols.length) {
                        setTimeout(() => {
                            alert('Félicitations ! 🎉 Tu as trouvé toutes les paires !');
                            this.saveProgress('memory', 100);
                        }, 500);
                    }
                } else {
                    // Pas une paire
                    setTimeout(() => {
                        card1.card.classList.remove('flipped');
                        card2.card.classList.remove('flipped');
                        flippedCards = [];
                    }, 1000);
                }
            }
        };
    }

    saveProgress(gameType, score) {
        // Sauvegarder la progression du joueur
        const progress = {
            game: gameType,
            score: score,
            date: new Date().toISOString()
        };

        // Envoyer au serveur
        fetch('api/save-game-progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(progress)
        })
        .then(response => response.json())
        .then(data => {
            console.log('Progression sauvegardée:', data);
        })
        .catch(error => {
            console.error('Erreur sauvegarde:', error);
        });
    }

    loadProgress() {
        // Charger la progression depuis le serveur
        fetch('api/get-game-progress.php')
            .then(response => response.json())
            .then(progress => {
                this.updateProgressUI(progress);
            })
            .catch(error => {
                console.error('Erreur chargement progression:', error);
            });
    }

    updateProgressUI(progress) {
        // Mettre à jour l'interface avec la progression
        // Implémentation spécifique selon la structure des données
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    window.enfantsManager = new EnfantsManager();
});