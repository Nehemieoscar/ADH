<?php
include 'config.php';

if (!est_connecte() || $_SESSION['utilisateur_role'] != 'professeur') {
    header('Location: login.php');
    exit;
}

$professeur_id = $_SESSION['utilisateur_id'];

// Récupérer les cours du professeur
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(i.id) as nombre_etudiants,
           AVG(i.progression) as progression_moyenne
    FROM cours c 
    LEFT JOIN inscriptions i ON c.id = i.cours_id 
    WHERE c.formateur_id = ? 
    GROUP BY c.id 
    ORDER BY c.date_creation DESC
");
$stmt->execute([$professeur_id]);
$cours = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo obtenir_utilisateur_connecte()['mode_sombre'] ? 'sombre' : 'clair'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Cours - ADH</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>

        <main class="dashboard-main">
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>Mes Cours</h1>
                    <p>Gérez vos cours et suivez la progression de vos étudiants</p>
                </div>
                <div class="header-right">
                    <a href="creer-cours.php" class="btn btn-primary">➕ Créer un cours</a>
                </div>
            </header>

            <div class="dashboard-content">
                <?php if (empty($cours)): ?>
                    <div class="card" style="text-align: center; padding: 3rem;">
                        <h3>Vous n'avez pas encore créé de cours</h3>
                        <p>Commencez par créer votre premier cours pour partager vos connaissances</p>
                        <a href="creer-cours.php" class="btn btn-primary">Créer mon premier cours</a>
                    </div>
                <?php else: ?>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Cours</th>
                                    <th>Étudiants</th>
                                    <th>Progression</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cours as $c): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <?php if ($c['image_cours']): ?>
                                                    <img src="assets/cours/<?php echo $c['image_cours']; ?>" alt="<?php echo $c['titre']; ?>" style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo $c['titre']; ?></strong>
                                                    <div style="display: flex; gap: 0.5rem; margin-top: 0.25rem;">
                                                        <span class="badge badge-info"><?php echo ucfirst($c['niveau']); ?></span>
                                                        <span class="badge badge-<?php echo $c['type'] == 'presentiel' ? 'warning' : 'success'; ?>">
                                                            <?php echo $c['type'] == 'presentiel' ? '🏢 Présentiel' : '🌐 En ligne'; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo $c['nombre_etudiants']; ?></strong> étudiants
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <div class="progress-bar" style="flex: 1;">
                                                    <div class="progress-fill" style="width: <?php echo $c['progression_moyenne'] ?? 0; ?>%"></div>
                                                </div>
                                                <span style="font-size: 0.8rem; min-width: 40px;"><?php echo round($c['progression_moyenne'] ?? 0); ?>%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo $c['statut'] == 'publie' ? 'success' : 
                                                     ($c['statut'] == 'brouillon' ? 'warning' : 'secondary'); 
                                            ?>">
                                                <?php echo ucfirst($c['statut']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 0.25rem;">
                                                <a href="gestion-cours.php?id=<?php echo $c['id']; ?>" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Gérer</a>
                                                <a href="cours-detail.php?id=<?php echo $c['id']; ?>" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Voir</a>
                                                <a href="statistiques-cours.php?id=<?php echo $c['id']; ?>" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Stats</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="js/script.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>