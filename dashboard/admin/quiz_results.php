<?php
// ========================================
// Page Admin: Quiz Results Dashboard
// ========================================

require_once('../../config.php');
securite_admin();

$page = 'quiz-results';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats Quiz - Admin</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .results-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }

        .results-table th, .results-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .results-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .score-good { color: #28a745; font-weight: 600; }
        .score-medium { color: #ffc107; font-weight: 600; }
        .score-bad { color: #dc3545; font-weight: 600; }

        .filter-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-section select, .filter-section input {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            flex: 1;
            min-width: 200px;
        }

        .btn-view-details {
            padding: 0.5rem 1rem;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-view-details:hover {
            background: #0056b3;
        }

        .stats-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #007bff;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #007bff;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include('../header.php'); ?>

    <div class="results-container">
        <h2>📊 Résultats des Quiz</h2>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stats-card">
                <div class="stat-value" id="total-submissions">0</div>
                <div class="stat-label">Soumissions totales</div>
            </div>
            <div class="stats-card">
                <div class="stat-value" id="avg-score">0%</div>
                <div class="stat-label">Score moyen</div>
            </div>
            <div class="stats-card">
                <div class="stat-value" id="passing-rate">0%</div>
                <div class="stat-label">Taux de réussite (>60%)</div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filter-section">
            <select id="filter-course" style="flex: 2;">
                <option value="">-- Tous les cours --</option>
            </select>
            <select id="filter-quiz" style="flex: 2;">
                <option value="">-- Tous les quiz --</option>
            </select>
            <input type="text" id="filter-student" placeholder="Filtrer par étudiant">
            <button onclick="loadResults()" style="padding: 0.75rem 1.5rem; background: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer;">Filtrer</button>
        </div>

        <!-- Tableau des résultats -->
        <table class="results-table">
            <thead>
                <tr>
                    <th>Étudiant</th>
                    <th>Quiz</th>
                    <th>Score</th>
                    <th>Pourcentage</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="results-tbody">
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem;">Chargement...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
        async function loadResults() {
            const courseId = document.getElementById('filter-course').value || '';
            const quizId = document.getElementById('filter-quiz').value || '';
            const studentName = document.getElementById('filter-student').value || '';

            try {
                const response = await fetch('/ADH/dashboard/api/get_quiz_results.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        course_id: courseId,
                        quiz_id: quizId,
                        student_name: studentName
                    })
                });

                const data = await response.json();
                if (data.success) {
                    renderResults(data.results, data.stats);
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }

        function renderResults(results, stats) {
            const tbody = document.getElementById('results-tbody');
            if (results.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 2rem;">Aucun résultat</td></tr>';
                return;
            }

            let html = '';
            results.forEach(result => {
                const percentage = ((result.score_final / result.points_max) * 100).toFixed(1);
                const scoreClass = percentage >= 60 ? 'score-good' : (percentage >= 40 ? 'score-medium' : 'score-bad');
                html += `
                    <tr>
                        <td>${result.student_name}</td>
                        <td>${result.quiz_titre}</td>
                        <td class="${scoreClass}">${result.score_final}/${result.points_max}</td>
                        <td class="${scoreClass}">${percentage}%</td>
                        <td>${new Date(result.date_soumission).toLocaleDateString()}</td>
                        <td>
                            <button class="btn-view-details" onclick="viewDetails(${result.submission_id})">Détails</button>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;

            if (stats) {
                document.getElementById('total-submissions').textContent = stats.total;
                document.getElementById('avg-score').textContent = stats.avg_percentage.toFixed(1) + '%';
                document.getElementById('passing-rate').textContent = stats.passing_rate.toFixed(1) + '%';
            }
        }

        function viewDetails(submissionId) {
            // Ouvrir modal avec détails
            alert('Détails de la soumission #' + submissionId);
            // À implémenter: modal avec réponses détaillées
        }

        loadResults();
    </script>
</body>
</html>
