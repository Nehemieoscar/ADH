<?php
// Récupérer les données du professeur
$professeur_id = $_SESSION['utilisateur_id'];

// Cours créés par le professeur
$stmt_cours = $pdo->prepare("
    SELECT c.*, 
           COUNT(i.id) as nombre_etudiants,
           AVG(i.progression) as progression_moyenne
    FROM cours c 
    LEFT JOIN inscriptions i ON c.id = i.cours_id 
    WHERE c.formateur_id = ? 
    GROUP BY c.id 
    ORDER BY c.date_creation DESC 
    LIMIT 4
");
$stmt_cours->execute([$professeur_id]);
$mes_cours = $stmt_cours->fetchAll();

// Statistiques du professeur
$stmt_stats = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT c.id) as total_cours,
        COUNT(DISTINCT i.utilisateur_id) as total_etudiants,
        AVG(i.progression) as progression_moyenne,
        SUM(CASE WHEN i.date_completion IS NOT NULL THEN 1 ELSE 0 END) as cours_termines
    FROM cours c 
    LEFT JOIN inscriptions i ON c.id = i.cours_id 
    WHERE c.formateur_id = ?
");
$stmt_stats->execute([$professeur_id]);
$stats = $stmt_stats->fetch();
?>

<!-- Statistiques du professeur -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['total_cours']; ?></div>
        <div class="stat-label">Cours créés</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['total_etudiants']; ?></div>
        <div class="stat-label">Étudiants</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo round($stats['progression_moyenne'] ?? 0); ?>%</div>
        <div class="stat-label">Progression moyenne</div>
    </div>
    <div class="stat-card secondary">
        <div class="stat-number"><?php echo $stats['cours_termines']; ?></div>
        <div class="stat-label">Cours terminés</div>
    </div>
</div>

<div class="grid grid-2" style="gap: 2rem; margin-bottom: 2rem;">
    <!-- Mes cours -->
    <div class="card">
        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0;">Mes cours</h3>
            <a href="creer-cours.php" class="btn btn-primary">➕ Créer un cours</a>
        </div>
        
        <?php if (empty($mes_cours)): ?>
            <p style="text-align: center; padding: 2rem; color: #666;">
                Vous n'avez pas encore créé de cours. <a href="creer-cours.php">Créez votre premier cours</a>
            </p>
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
                        <?php foreach ($mes_cours as $cours): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $cours['titre']; ?></strong><br>
                                    <small style="color: #666;"><?php echo ucfirst($cours['niveau']); ?></small>
                                </td>
                                <td><?php echo $cours['nombre_etudiants']; ?></td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $cours['progression_moyenne'] ?? 0; ?>%"></div>
                                    </div>
                                    <small><?php echo round($cours['progression_moyenne'] ?? 0); ?>%</small>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $cours['statut'] == 'publie' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($cours['statut']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="gestion-cours.php?id=<?php echo $cours['id']; ?>" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Gérer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Actions rapides et notifications -->
    <div>
        <!-- Actions rapides -->
        <div class="card" style="margin-bottom: 1.5rem;">
            <h3 style="margin-bottom: 1rem;">Actions rapides</h3>
            <div style="display: grid; gap: 0.5rem;">
                <a href="creer-cours.php" class="btn btn-outline" style="text-align: left; justify-content: flex-start;">
                    <span style="margin-right: 0.5rem;">➕</span> Créer un nouveau cours
                </a>
                <a href="evaluations.php" class="btn btn-outline" style="text-align: left; justify-content: flex-start;">
                    <span style="margin-right: 0.5rem;">📝</span> Gérer les évaluations
                </a>
                <a href="statistiques.php" class="btn btn-outline" style="text-align: left; justify-content: flex-start;">
                    <span style="margin-right: 0.5rem;">📊</span> Voir les statistiques
                </a>
                <a href="forum.php" class="btn btn-outline" style="text-align: left; justify-content: flex-start;">
                    <span style="margin-right: 0.5rem;">💬</span> Forum des formateurs
                </a>
            </div>
        </div>

        <!-- Dernières activités -->
        <div class="card">
            <h3 style="margin-bottom: 1rem;">Dernières activités</h3>
            <div style="display: grid; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 1rem; padding: 0.5rem; background: var(--couleur-fond); border-radius: 5px;">
                    <div style="background: var(--couleur-primaire); color: white; padding: 0.5rem; border-radius: 50%;">📚</div>
                    <div>
                        <strong>Nouvel étudiant inscrit</strong>
                        <p style="margin: 0; font-size: 0.8rem; color: #666;">À votre cours "Développement Web"</p>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 1rem; padding: 0.5rem; background: var(--couleur-fond); border-radius: 5px;">
                    <div style="background: var(--couleur-success); color: white; padding: 0.5rem; border-radius: 50%;">🎓</div>
                    <div>
                        <strong>Cours terminé</strong>
                        <p style="margin: 0; font-size: 0.8rem; color: #666;">Par Jean Pierre dans "Introduction à PHP"</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Évaluations en attente -->
<div class="card">
    <h3 style="margin-bottom: 1.5rem;">Évaluations en attente de correction</h3>
    <div style="text-align: center; padding: 2rem; color: #666;">
        <p>Aucune évaluation en attente de correction.</p>
        <a href="evaluations.php" class="btn btn-outline">Voir toutes les évaluations</a>
    </div>
</div>