<?php
// Récupérer les données de l'étudiant
$utilisateur_id = $_SESSION['utilisateur_id'];

// Cours en cours
$stmt_cours = $pdo->prepare("
    SELECT c.*, i.progression 
    FROM inscriptions i 
    JOIN cours c ON i.cours_id = c.id 
    WHERE i.utilisateur_id = ? AND i.date_completion IS NULL 
    ORDER BY i.date_inscription DESC 
    LIMIT 6
");
$stmt_cours->execute([$utilisateur_id]);
$cours_encours = $stmt_cours->fetchAll();

// Statistiques
$stmt_stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_cours,
        SUM(CASE WHEN i.date_completion IS NOT NULL THEN 1 ELSE 0 END) as cours_termines,
        AVG(CASE WHEN i.date_completion IS NOT NULL THEN i.progression ELSE NULL END) as moyenne_progression
    FROM inscriptions i 
    WHERE i.utilisateur_id = ?
");
$stmt_stats->execute([$utilisateur_id]);
$stats = $stmt_stats->fetch();

// Prochain événement
$stmt_evenements = $pdo->prepare("
    SELECT * FROM evenements 
    WHERE date_debut > NOW() 
    ORDER BY date_debut ASC 
    LIMIT 1
");
$stmt_evenements->execute();
$prochain_evenement = $stmt_evenements->fetch();
?>

<!-- Statistiques rapides -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['total_cours']; ?></div>
        <div class="stat-label">Cours suivis</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['cours_termines']; ?></div>
        <div class="stat-label">Cours terminés</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo round($stats['moyenne_progression'] ?? 0); ?>%</div>
        <div class="stat-label">Progression moyenne</div>
    </div>
    <div class="stat-card secondary">
        <div class="stat-number">0</div>
        <div class="stat-label">Certifications</div>
    </div>
</div>

<div class="grid grid-2" style="gap: 2rem; margin-bottom: 2rem;">
    <!-- Mes cours en cours -->
    <div class="card">
        <h3 style="margin-bottom: 1.5rem;">Mes cours en cours</h3>
        
        <?php if (empty($cours_encours)): ?>
            <p style="text-align: center; padding: 2rem; color: #666;">
                Aucun cours en cours. <a href="cours.php">Parcourir les cours</a>
            </p>
        <?php else: ?>
            <div class="courses-grid">
                <?php foreach ($cours_encours as $cours): ?>
                    <div class="course-card">
                        <?php if ($cours['image_cours']): ?>
                            <img src="assets/cours/<?php echo $cours['image_cours']; ?>" alt="<?php echo $cours['titre']; ?>" class="course-image">
                        <?php else: ?>
                            <div class="course-image" style="background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-secondaire)); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                                📚
                            </div>
                        <?php endif; ?>
                        
                        <div class="course-content">
                            <span class="course-category"><?php echo ucfirst($cours['niveau']); ?></span>
                            <h4 class="course-title"><?php echo $cours['titre']; ?></h4>
                            <p class="course-description"><?php echo substr($cours['description'], 0, 100) . '...'; ?></p>
                            
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $cours['progression']; ?>%"></div>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #666;">
                                <span>Progression: <?php echo $cours['progression']; ?>%</span>
                                <span><?php echo $cours['duree']; ?>h</span>
                            </div>
                            
                            <a href="cours.php?id=<?php echo $cours['id']; ?>" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Continuer</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Activité récente et événements -->
    <div>
        <!-- Prochain événement -->
        <?php if ($prochain_evenement): ?>
            <div class="card" style="margin-bottom: 1.5rem;">
                <h3 style="margin-bottom: 1rem;">Prochain événement</h3>
                <div style="padding: 1rem; background: linear-gradient(135deg, var(--couleur-primaire), #0066cc); color: white; border-radius: 8px;">
                    <h4><?php echo $prochain_evenement['titre']; ?></h4>
                    <p style="margin: 0.5rem 0;"><?php echo date('d/m/Y à H:i', strtotime($prochain_evenement['date_debut'])); ?></p>
                    <p style="margin: 0.5rem 0; opacity: 0.9;"><?php echo $prochain_evenement['lieu']; ?></p>
                    <a href="evenement.php?id=<?php echo $prochain_evenement['id']; ?>" class="btn" style="background: white; color: var(--couleur-primaire); margin-top: 0.5rem;">Voir détails</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Actions rapides -->
        <div class="card">
            <h3 style="margin-bottom: 1rem;">Actions rapides</h3>
            <div style="display: grid; gap: 0.5rem;">
                <a href="cours.php" class="btn btn-outline" style="text-align: left; justify-content: flex-start;">
                    <span style="margin-right: 0.5rem;">📚</span> Parcourir les cours
                </a>
                <a href="assistant-ia.php" class="btn btn-outline" style="text-align: left; justify-content: flex-start;">
                    <span style="margin-right: 0.5rem;">🤖</span> Assistant IA
                </a>
                <a href="forum.php" class="btn btn-outline" style="text-align: left; justify-content: flex-start;">
                    <span style="margin-right: 0.5rem;">💬</span> Forum communautaire
                </a>
                <a href="quiz.php" class="btn btn-outline" style="text-align: left; justify-content: flex-start;">
                    <span style="margin-right: 0.5rem;">❓</span> Tests et quiz
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recommandations -->
<div class="card">
    <h3 style="margin-bottom: 1.5rem;">Cours recommandés pour vous</h3>
    <div class="courses-grid">
        <!-- Cours recommandés basés sur l'historique -->
        <?php
        $stmt_recommandations = $pdo->prepare("
            SELECT c.* 
            FROM cours c 
            WHERE c.statut = 'publie' 
            AND c.id NOT IN (SELECT cours_id FROM inscriptions WHERE utilisateur_id = ?)
            ORDER BY RAND() 
            LIMIT 3
        ");
        $stmt_recommandations->execute([$utilisateur_id]);
        $cours_recommandes = $stmt_recommandations->fetchAll();
        ?>
        
        <?php foreach ($cours_recommandes as $cours): ?>
            <div class="course-card">
                <?php if ($cours['image_cours']): ?>
                    <img src="assets/cours/<?php echo $cours['image_cours']; ?>" alt="<?php echo $cours['titre']; ?>" class="course-image">
                <?php else: ?>
                    <div class="course-image" style="background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                        🎯
                    </div>
                <?php endif; ?>
                
                <div class="course-content">
                    <span class="course-category"><?php echo ucfirst($cours['niveau']); ?></span>
                    <h4 class="course-title"><?php echo $cours['titre']; ?></h4>
                    <p class="course-description"><?php echo substr($cours['description'], 0, 100) . '...'; ?></p>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                        <span style="font-size: 0.8rem; color: #666;"><?php echo $cours['duree']; ?>h</span>
                        <a href="cours.php?id=<?php echo $cours['id']; ?>" class="btn btn-primary">Découvrir</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>