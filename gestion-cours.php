<?php
include 'config.php';

if (!est_connecte() || $_SESSION['utilisateur_role'] != 'professeur') {
    header('Location: login.php');
    exit;
}

$cours_id = $_GET['id'] ?? 0;

// Vérifier que le cours appartient au professeur
$stmt = $pdo->prepare("SELECT * FROM cours WHERE id = ? AND formateur_id = ?");
$stmt->execute([$cours_id, $_SESSION['utilisateur_id']]);
$cours = $stmt->fetch();

if (!$cours) {
    header('Location: mes-cours.php');
    exit;
}

// Récupérer les modules du cours
$stmt_modules = $pdo->prepare("SELECT * FROM modules WHERE cours_id = ? ORDER BY ordre");
$stmt_modules->execute([$cours_id]);
$modules = $stmt_modules->fetchAll();

// Récupérer les étudiants inscrits
$stmt_etudiants = $pdo->prepare("
    SELECT u.nom, u.email, i.progression, i.date_inscription 
    FROM inscriptions i 
    JOIN utilisateurs u ON i.utilisateur_id = u.id 
    WHERE i.cours_id = ? 
    ORDER BY i.date_inscription DESC
");
$stmt_etudiants->execute([$cours_id]);
$etudiants = $stmt_etudiants->fetchAll();

$erreur = '';
$success = '';

// Ajouter un module
if ($_POST['action'] == 'ajouter_module') {
    $titre = securiser($_POST['titre_module']);
    $description = securiser($_POST['description_module']);
    $ordre = securiser($_POST['ordre_module']);
    $duree_estimee = securiser($_POST['duree_estimee_module']);
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO modules (cours_id, titre, description, ordre, duree_estimee) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$cours_id, $titre, $description, $ordre, $duree_estimee]);
        $success = "Module ajouté avec succès !";
        header("Location: gestion-cours.php?id=$cours_id");
        exit;
    } catch (PDOException $e) {
        $erreur = "Erreur lors de l'ajout du module: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo obtenir_utilisateur_connecte()['mode_sombre'] ? 'sombre' : 'clair'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion du cours - ADH</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>

        <main class="dashboard-main">
            <header class="dashboard-header">
                <div class="header-left">
                    <h1><?php echo $cours['titre']; ?></h1>
                    <p>Gestion du cours et de son contenu</p>
                </div>
                <div class="header-right">
                    <a href="cours-detail.php?id=<?php echo $cours_id; ?>" class="btn btn-outline" target="_blank">Voir le cours</a>
                </div>
            </header>

            <div class="dashboard-content">
                <?php if ($erreur): ?>
                    <div class="alert alert-error"><?php echo $erreur; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="grid grid-2" style="gap: 2rem; align-items: start;">
                    <!-- Modules du cours -->
                    <div>
                        <div class="card">
                            <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 1.5rem;">
                                <h2>Modules du cours</h2>
                                <button type="button" class="btn btn-primary" onclick="toggleModuleForm()">➕ Ajouter un module</button>
                            </div>

                            <!-- Formulaire d'ajout de module (caché par défaut) -->
                            <div id="module-form" style="display: none; margin-bottom: 1.5rem; padding: 1.5rem; background: var(--couleur-fond); border-radius: 8px;">
                                <h3 style="margin-bottom: 1rem;">Nouveau module</h3>
                                <form method="POST">
                                    <input type="hidden" name="action" value="ajouter_module">
                                    
                                    <div class="form-group">
                                        <label for="titre_module" class="form-label">Titre du module *</label>
                                        <input type="text" id="titre_module" name="titre_module" class="form-control" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="description_module" class="form-label">Description</label>
                                        <textarea id="description_module" name="description_module" class="form-control" rows="3"></textarea>
                                    </div>
                                    
                                    <div class="grid grid-2" style="gap: 1rem;">
                                        <div class="form-group">
                                            <label for="ordre_module" class="form-label">Ordre *</label>
                                            <input type="number" id="ordre_module" name="ordre_module" class="form-control" min="1" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="duree_estimee_module" class="form-label">Durée estimée (heures)</label>
                                            <input type="number" id="duree_estimee_module" name="duree_estimee_module" class="form-control" min="1">
                                        </div>
                                    </div>
                                    
                                    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                                        <button type="submit" class="btn btn-primary">Ajouter le module</button>
                                        <button type="button" class="btn btn-outline" onclick="toggleModuleForm()">Annuler</button>
                                    </div>
                                </form>
                            </div>

                            <!-- Liste des modules -->
                            <?php if (empty($modules)): ?>
                                <p style="text-align: center; padding: 2rem; color: #666;">
                                    Aucun module créé pour le moment.
                                </p>
                            <?php else: ?>
                                <div class="modules-list">
                                    <?php foreach ($modules as $module): ?>
                                        <div class="module-item" style="border: 1px solid var(--couleur-border); border-radius: 8px; margin-bottom: 1rem; overflow: hidden;">
                                            <div style="padding: 1rem; background: var(--couleur-fond); display: flex; justify-content: between; align-items: center;">
                                                <div>
                                                    <h4 style="margin: 0;"><?php echo $module['titre']; ?></h4>
                                                    <p style="margin: 0.25rem 0 0 0; color: #666; font-size: 0.9rem;">
                                                        Ordre: <?php echo $module['ordre']; ?> • 
                                                        Durée: <?php echo $module['duree_estimee'] ?? 'Non définie'; ?>h
                                                    </p>
                                                </div>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <a href="gestion-module.php?id=<?php echo $module['id']; ?>" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Gérer</a>
                                                </div>
                                            </div>
                                            <?php if ($module['description']): ?>
                                                <div style="padding: 1rem;">
                                                    <p style="margin: 0;"><?php echo $module['description']; ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Étudiants inscrits -->
                    <div>
                        <div class="card">
                            <h2 style="margin-bottom: 1.5rem;">Étudiants inscrits (<?php echo count($etudiants); ?>)</h2>
                            
                            <?php if (empty($etudiants)): ?>
                                <p style="text-align: center; padding: 2rem; color: #666;">
                                    Aucun étudiant inscrit pour le moment.
                                </p>
                            <?php else: ?>
                                <div class="data-table">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Étudiant</th>
                                                <th>Progression</th>
                                                <th>Date d'inscription</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($etudiants as $etudiant): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo $etudiant['nom']; ?></strong>
                                                        <br>
                                                        <small style="color: #666;"><?php echo $etudiant['email']; ?></small>
                                                    </td>
                                                    <td>
                                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                            <div class="progress-bar" style="flex: 1;">
                                                                <div class="progress-fill" style="width: <?php echo $etudiant['progression']; ?>%"></div>
                                                            </div>
                                                            <span style="font-size: 0.8rem;"><?php echo $etudiant['progression']; ?>%</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php echo date('d/m/Y', strtotime($etudiant['date_inscription'])); ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Statistiques rapides -->
                        <div class="card">
                            <h2 style="margin-bottom: 1.5rem;">Statistiques du cours</h2>
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo count($etudiants); ?></div>
                                    <div class="stat-label">Étudiants</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo count($modules); ?></div>
                                    <div class="stat-label">Modules</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo $cours['duree']; ?>h</div>
                                    <div class="stat-label">Durée totale</div>
                                </div>
                                <div class="stat-card secondary">
                                    <div class="stat-number">
                                        <?php 
                                        $progression_moyenne = 0;
                                        if (!empty($etudiants)) {
                                            $total_progression = 0;
                                            foreach ($etudiants as $etudiant) {
                                                $total_progression += $etudiant['progression'];
                                            }
                                            $progression_moyenne = round($total_progression / count($etudiants));
                                        }
                                        echo $progression_moyenne;
                                        ?>%
                                    </div>
                                    <div class="stat-label">Progression moyenne</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    function toggleModuleForm() {
        const form = document.getElementById('module-form');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
    </script>

    <script src="js/script.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>