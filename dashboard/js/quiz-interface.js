// ========================================
// Quiz Taking Interface - Student View
// ========================================

document.addEventListener('DOMContentLoaded', function() {

    // Charger et afficher un quiz
    const quizContainer = document.getElementById('quiz-container');
    if (quizContainer) {
        const quizId = quizContainer.getAttribute('data-quiz-id');
        loadQuiz(quizId);
    }

    async function loadQuiz(quizId) {
        try {
            const response = await fetch(`/ADH/dashboard/api/get_quiz.php?quiz_id=${quizId}`);
            const quiz = await response.json();

            if (!quiz.success) {
                quizContainer.innerHTML = '<p style="color: red;">Quiz non trouvé</p>';
                return;
            }

            renderQuiz(quiz.data);
        } catch (error) {
            console.error('Erreur:', error);
            quizContainer.innerHTML = '<p style="color: red;">Erreur lors du chargement du quiz</p>';
        }
    }

    function renderQuiz(quiz) {
        let html = `<div style="max-width: 800px; margin: 0 auto; padding: 2rem;">`;
        html += `<h2>${quiz.titre}</h2>`;
        html += `<p>${quiz.description || ''}</p>`;
        html += `<p style="color: #666;">Points totaux: ${quiz.points_max}</p>`;

        html += `<form id="quiz-form">`;

        quiz.questions.forEach((question, idx) => {
            html += `<div style="background: #f9f9f9; padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 8px; border-left: 4px solid #007bff;">`;
            html += `<h4>Question ${idx + 1}</h4>`;
            html += `<p style="font-weight: 600; margin-bottom: 1rem;">${question.enonce}</p>`;

            if (question.type === 'multiple_choice') {
                question.options.forEach((option) => {
                    html += `
                        <label style="display: block; margin-bottom: 0.75rem;">
                            <input type="radio" name="question_${question.id}" value="${option.id}" style="margin-right: 0.5rem;">
                            ${option.texte}
                        </label>
                    `;
                });
            } else if (question.type === 'true_false') {
                html += `
                    <label style="display: block; margin-bottom: 0.75rem;">
                        <input type="radio" name="question_${question.id}" value="true" style="margin-right: 0.5rem;">
                        Vrai
                    </label>
                    <label style="display: block; margin-bottom: 0.75rem;">
                        <input type="radio" name="question_${question.id}" value="false" style="margin-right: 0.5rem;">
                        Faux
                    </label>
                `;
            } else if (question.type === 'short_answer') {
                html += `
                    <textarea name="question_${question.id}" placeholder="Écrivez votre réponse..." style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; min-height: 100px;"></textarea>
                `;
            }

            html += `</div>`;
        });

        html += `
            <div id="quiz-result" style="display: none; padding: 1.5rem; background: #d4edda; border-radius: 8px; margin-bottom: 1rem;">
                <h4>Résultats</h4>
                <p id="result-score" style="font-size: 1.2rem; font-weight: 600;"></p>
                <p id="result-percentage" style="color: #666;"></p>
            </div>

            <button type="submit" style="padding: 0.75rem 1.5rem; background: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Soumettre le Quiz</button>
        </form>
        </div>`;

        quizContainer.innerHTML = html;

        document.getElementById('quiz-form').addEventListener('submit', (e) => {
            e.preventDefault();
            submitQuiz(quiz.id);
        });
    }

    async function submitQuiz(quizId) {
        const form = document.getElementById('quiz-form');
        const formData = new FormData(form);
        const answers = {};

        // Récupérer les réponses du formulaire
        const quizInputs = form.querySelectorAll('input[type="radio"], textarea');
        quizInputs.forEach(input => {
            const questionId = input.name.replace('question_', '');
            if (input.type === 'radio' && input.checked) {
                answers[questionId] = { option_id: input.value };
            } else if (input.type === 'textarea' && input.value) {
                answers[questionId] = { texte: input.value };
            }
        });

        try {
            const response = await fetch('/ADH/dashboard/api/submit_quiz.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ quiz_id: quizId, answers })
            });
            const result = await response.json();

            if (result.success) {
                const percentage = ((result.score / result.max_points) * 100).toFixed(1);
                document.getElementById('result-score').textContent = `Score: ${result.score} / ${result.max_points}`;
                document.getElementById('result-percentage').textContent = `Pourcentage: ${percentage}%`;
                document.getElementById('quiz-result').style.display = 'block';
                form.style.display = 'none';
            } else {
                alert('Erreur: ' + result.message);
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert('Erreur lors de la soumission du quiz');
        }
    }

    console.log('✅ Quiz Interface initialisée');
});
