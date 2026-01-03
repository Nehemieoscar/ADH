<?php
include 'config.php';

if (!est_connecte() || $_SESSION['utilisateur_role'] != 'professeur') {
    header('Location: login.php');
    exit;
}

$erreur = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titre = securiser($_POST['titre']);
    $description = securiser($_POST['description']);
    $niveau = securiser($_POST['niveau']);
    $type = securiser($_POST['type']);
    $duree = securiser($_POST['duree']);
    $prix = securiser($_POST['prix']);
    $statut = securiser($_POST['statut']);
    
    // Gestion de l'upload d'image
    $image_cours = null;
    if (isset($_FILES['image_cours']) && $_FILES['image_cours']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image_cours']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $extension = pathinfo($_FILES['image_cours']['name'], PATHINFO_EXTENSION);
            $image_cours = uniqid() . '.' . $extension;
            move_uploaded_file($_FILES['image_cours']['tmp_name'], 'assets/cours/' . $image_cours);
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO cours (titre, description, image_cours, niveau, type, duree, prix, formateur_id, statut) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $titre, $description, $image_cours, $niveau, $type, $duree, $prix, 
            $_SESSION['utilisateur_id'], $statut
        ]);
        
        $cours_id = $pdo->lastInsertId();
        $success = "Cours créé avec succès !";
        
        // Redirection vers la gestion du cours
        header("Location: gestion-cours.php?id=$cours_id");
        exit;
        
    } catch (PDOException $e) {
        $erreur = "Erreur lors de la création du cours: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo obtenir_utilisateur_connecte()['mode_sombre'] ? 'sombre' : 'clair'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un cours - ADH</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>

        <main class="dashboard-main">
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>Créer un nouveau cours</h1>
                    <p>Partagez vos connaissances avec la communauté ADH</p>
                </div>
            </header>

            <div class="dashboard-content">
                <div class="card">
                    <?php if ($erreur): ?>
                        <div class="alert alert-error"><?php echo $erreur; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="grid grid-2" style="gap: 1.5rem;">
                            <div class="form-group">
                                <label for="titre" class="form-label">Titre du cours *</label>
                                <input type="text" id="titre" name="titre" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="niveau" class="form-label">Niveau *</label>
                                <select id="niveau" name="niveau" class="form-control" required>
                                    <option value="debutant">Débutant</option>
                                    <option value="intermediaire">Intermédiaire</option>
                                    <option value="avance">Avancé</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Description *</label>
                            <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
                        </div>

                        <div class="grid grid-3" style="gap: 1.5rem;">
                            <div class="form-group">
                                <label for="type" class="form-label">Type *</label>
                                <select id="type" name="type" class="form-control" required>
                                    <option value="en_ligne">En ligne</option>
                                    <option value="presentiel">Présentiel</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="duree" class="form-label">Durée (heures) *</label>
                                <input type="number" id="duree" name="duree" class="form-control" min="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="prix" class="form-label">Prix (€)</label>
                                <input type="number" id="prix" name="prix" class="form-control" min="0" step="0.01" value="0">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="image_cours" class="form-label">Image du cours</label>
                            <input type="file" id="image_cours" name="image_cours" class="form-control" accept="image/*">
                            <small style="color: #666;">Formats acceptés: JPG, PNG, GIF (max 2MB)</small>
                        </div>

                        <div class="form-group">
                            <label for="statut" class="form-label">Statut *</label>
                            <select id="statut" name="statut" class="form-control" required>
                                <option value="brouillon">Brouillon</option>
                                <option value="publie">Publié</option>
                            </select>
                        </div>

                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary">Créer le cours</button>
                            <a href="mes-cours.php" class="btn btn-outline">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="js/script.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>