<?php
include 'config.php';

if (!est_connecte()) {
    header('Location: login.php');
    exit;
}

$utilisateur = obtenir_utilisateur_connecte();

// Récupérer les quiz disponibles
$stmt_quiz = $pdo->prepare("
    SELECT q.*, c.titre as cours_titre, 
           COUNT(qu.id) as nombre_questions,
           (SELECT COUNT(*) FROM quiz_tentatives qt WHERE qt.quiz_id = q.id AND qt.utilisateur_id = ?) as tentatives
    FROM quiz q
    LEFT JOIN cours c ON q.cours_id = c.id
    LEFT JOIN questions qu ON q.id = qu.quiz_id
    WHERE q.statut = 'actif'
    GROUP BY q.id
    ORDER BY q.date_creation DESC
");
$stmt_quiz->execute([$_SESSION['utilisateur_id']]);
$quiz_list = $stmt_quiz->fetchAll();

// Récupérer les résultats récents
$stmt_resultats = $pdo->prepare("
    SELECT qt.*, q.titre as quiz_titre
    FROM quiz_tentatives qt
    JOIN quiz q ON qt.quiz_id = q.id
    WHERE qt.utilisateur_id = ?
    ORDER BY qt.date_tentative DESC
    LIMIT 5
");
$stmt_resultats->execute([$_SESSION['utilisateur_id']]);
$resultats_recents = $stmt_resultats->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $utilisateur['mode_sombre'] ? 'sombre' : 'clair'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz et Évaluations - ADH</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/quiz.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>

        <main class="dashboard-main">
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>❓ Quiz et Évaluations</h1>
                    <p>Testez vos connaissances et mesurez votre progression</p>
                </div>
                <div class="header-right">
                    <a href="quiz-defis.php" class="btn btn-outline">🎯 Défis quotidiens</a>
                </div>
            </header>

            <div class="dashboard-content">
                <!-- Statistiques rapides -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($quiz_list); ?></div>
                        <div class="stat-label">Quiz disponibles</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php
                            $quiz_completes = 0;
                            foreach ($quiz_list as $quiz) {
                                if ($quiz['tentatives'] > 0) $quiz_completes++;
                            }
                            echo $quiz_completes;
                            ?>
                        </div>
                        <div class="stat-label">Quiz complétés</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php
                            $moyenne = 0;
                            if (!empty($resultats_recents)) {
                                $total = 0;
                                foreach ($resultats_recents as $resultat) {
                                    $total += $resultat['score'];
                                }
                                $moyenne = round($total / count($resultats_recents));
                            }
                            echo $moyenne;
                            ?>%
                        </div>
                        <div class="stat-label">Score moyen</div>
                    </div>
                    <div class="stat-card secondary">
                        <div class="stat-number">
                            <?php
                            $meilleur_score = 0;
                            foreach ($resultats_recents as $resultat) {
                                if ($resultat['score'] > $meilleur_score) {
                                    $meilleur_score = $resultat['score'];
                                }
                            }
                            echo $meilleur_score;
                            ?>%
                        </div>
                        <div class="stat-label">Meilleur score</div>
                    </div>
                </div>

                <div class="grid grid-2" style="gap: 2rem; align-items: start;">
                    <!-- Liste des quiz -->
                    <div>
                        <div class="card">
                            <h2 style="margin-bottom: 1.5rem;">📚 Quiz disponibles</h2>
                            
                            <?php if (empty($quiz_list)): ?>
                                <p style="text-align: center; padding: 2rem; color: #666;">
                                    Aucun quiz disponible pour le moment.
                                </p>
                            <?php else: ?>
                                <div class="quiz-list">
                                    <?php foreach ($quiz_list as $quiz): ?>
                                        <div class="quiz-item">
                                            <div class="quiz-info">
                                                <h3><?php echo $quiz['titre']; ?></h3>
                                                <p><?php echo $quiz['description']; ?></p>
                                                <div class="quiz-meta">
                                                    <span>📖 <?php echo $quiz['cours_titre'] ?: 'Général'; ?></span>
                                                    <span>❓ <?php echo $quiz['nombre_questions']; ?> questions</span>
                                                    <span>⏱️ <?php echo $quiz['duree'] ? $quiz['duree'] . ' min' : 'Illimité'; ?></span>
                                                </div>
                                            </div>
                                            <div class="quiz-actions">
                                                <?php if ($quiz['tentatives'] > 0): ?>
                                                    <div class="quiz-score">
                                                        <span>Déjà tenté</span>
                                                        <a href="resultat-quiz.php?id=<?php echo $quiz['id']; ?>" class="btn btn-outline">Voir résultat</a>
                                                    </div>
                                                <?php else: ?>
                                                    <a href="passer-quiz.php?id=<?php echo $quiz['id']; ?>" class="btn btn-primary">Commencer</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Résultats récents et défis -->
                    <div>
                        <!-- Résultats récents -->
                        <div class="card" style="margin-bottom: 1.5rem;">
                            <h2 style="margin-bottom: 1.5rem;">📊 Derniers résultats</h2>
                            
                            <?php if (empty($resultats_recents)): ?>
                                <p style="text-align: center; padding: 1rem; color: #666;">
                                    Aucun résultat récent.
                                </p>
                            <?php else: ?>
                                <div class="resultats-list">
                                    <?php foreach ($resultats_recents as $resultat): ?>
                                        <div class="resultat-item">
                                            <div class="resultat-info">
                                                <strong><?php echo $resultat['quiz_titre']; ?></strong>
                                                <span><?php echo date('d/m/Y', strtotime($resultat['date_tentative'])); ?></span>
                                            </div>
                                            <div class="resultat-score">
                                                <span class="score <?php 
                                                    echo $resultat['score'] >= 80 ? 'excellent' : 
                                                         ($resultat['score'] >= 60 ? 'bon' : 'faible');
                                                ?>">
                                                    <?php echo $resultat['score']; ?>%
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Défi du jour -->
                        <div class="card challenge-card">
                            <h2 style="margin-bottom: 1rem;">🎯 Défi du jour</h2>
                            <div class="challenge-content">
                                <h3>Quiz Express : Développement Web</h3>
                                <p>5 questions - 3 minutes</p>
                                <div class="challenge-reward">
                                    <span>🎁 +10 points d'expérience</span>
                                </div>
                                <button class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                                    Relever le défi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progression globale -->
                <div class="card">
                    <h2 style="margin-bottom: 1.5rem;">📈 Votre progression</h2>
                    <div class="progression-chart">
                        <!-- Graphique de progression simulé -->
                        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem; height: 100px; align-items: end;">
                            <?php for ($i = 0; $i < 7; $i++): ?>
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div style="
                                        width: 30px; 
                                        background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-secondaire)); 
                                        border-radius: 5px 5px 0 0;
                                        height: <?php echo rand(20, 100); ?>%;"></div>
                                    <span style="font-size: 0.7rem; margin-top: 0.5rem;"><?php echo ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'][$i]; ?></span>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="js/script.js"></script>
    <script src="js/dashboard.js"></script>
    <script src="js/suiz.js"></script>
</body>
</html>