<?php
// Statistiques globales
$stmt_stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_utilisateurs,
        COUNT(CASE WHEN role = 'etudiant' THEN 1 END) as etudiants,
        COUNT(CASE WHEN role = 'professeur' THEN 1 END) as professeurs,
        COUNT(CASE WHEN role = 'admin' THEN 1 END) as admins,
        COUNT(*) as total_cours,
        COUNT(CASE WHEN statut = 'publie' THEN 1 END) as cours_publies,
        AVG(progression) as progression_moyenne
    FROM utilisateurs, cours
");
$stmt_stats->execute();
$stats = $stmt_stats->fetch();

// Derniers utilisateurs inscrits
$stmt_utilisateurs = $pdo->prepare("
    SELECT * FROM utilisateurs 
    ORDER BY date_inscription DESC 
    LIMIT 5
");
$stmt_utilisateurs->execute();
$derniers_utilisateurs = $stmt_utilisateurs->fetchAll();

// Cours récemment créés
$stmt_cours = $pdo->prepare("
    SELECT c.*, u.nom as formateur_nom 
    FROM cours c 
    LEFT JOIN utilisateurs u ON c.formateur_id = u.id 
    ORDER BY c.date_creation DESC 
    LIMIT 5
");
$stmt_cours->execute();
$derniers_cours = $stmt_cours->fetchAll();
?>

<!-- Statistiques globales -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['total_utilisateurs']; ?></div>
        <div class="stat-label">Utilisateurs totaux</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['etudiants']; ?></div>
        <div class="stat-label">Étudiants</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['professeurs']; ?></div>
        <div class="stat-label">Formateurs</div>
    </div>
    <div class="stat-card secondary">
        <div class="stat-number"><?php echo $stats['total_cours']; ?></div>
        <div class="stat-label">Cours créés</div>
    </div>
</div>

<div class="grid grid-2" style="gap: 2rem; margin-bottom: 2rem;">
    <!-- Derniers utilisateurs -->
    <div class="card">
        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0;">Derniers utilisateurs</h3>
            <a href="gestion-utilisateurs.php" class="btn btn-outline">Voir tout</a>
        </div>
        
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($derniers_utilisateurs as $utilisateur): ?>
                        <tr>
                            <td>
                                <strong><?php echo $utilisateur['nom']; ?></strong>
                            </td>
                            <td><?php echo $utilisateur['email']; ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $utilisateur['role'] == 'admin' ? 'warning' : 
                                         ($utilisateur['role'] == 'professeur' ? 'info' : 'success'); 
                                ?>">
                                    <?php echo ucfirst($utilisateur['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($utilisateur['date_inscription'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Derniers cours -->
    <div class="card">
        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0;">Derniers cours</h3>
            <a href="gestion-cours.php" class="btn btn-outline">Voir tout</a>
        </div>
        
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>Cours</th>
                        <th>Formateur</th>
                        <th>Niveau</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($derniers_cours as $cours): ?>
                        <tr>
                            <td>
                                <strong><?php echo $cours['titre']; ?></strong>
                            </td>
                            <td><?php echo $cours['formateur_nom'] ?? 'Système'; ?></td>
                            <td><?php echo ucfirst($cours['niveau']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $cours['statut'] == 'publie' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($cours['statut']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid grid-2" style="gap: 2rem;">
    <!-- Actions d'administration -->
    <div class="card">
        <h3 style="margin-bottom: 1rem;">Actions d'administration</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <a href="gestion-utilisateurs.php" class="btn btn-outline" style="text-align: center; padding: 1rem;">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">👥</div>
                Gérer les utilisateurs
            </a>
            <a href="gestion-cours.php" class="btn btn-outline" style="text-align: center; padding: 1rem;">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">📚</div>
                Gérer les cours
            </a>
            <a href="statistiques-globales.php" class="btn btn-outline" style="text-align: center; padding: 1rem;">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">📊</div>
                Statistiques globales
            </a>
            <a href="parametres.php" class="btn btn-outline" style="text-align: center; padding: 1rem;">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">⚙️</div>
                Paramètres
            </a>
        </div>
    </div>

    <!-- Alertes système -->
    <div class="card">
        <h3 style="margin-bottom: 1rem;">Alertes système</h3>
        <div style="display: grid; gap: 1rem;">
            <div style="padding: 1rem; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                <strong>⚠️ Maintenance planifiée</strong>
                <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">Maintenance système prévue ce weekend.</p>
            </div>
            
            <div style="padding: 1rem; background: #d1ecf1; border-left: 4px solid #0c5460; border-radius: 4px;">
                <strong>ℹ️ Nouvelle version</strong>
                <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">Version 2.1 déployée avec succès.</p>
            </div>
            
            <div style="padding: 1rem; background: #d4edda; border-left: 4px solid #28a745; border-radius: 4px;">
                <strong>✅ Système opérationnel</strong>
                <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">Tous les services fonctionnent normalement.</p>
            </div>
        </div>
    </div>
</div>