<?php
include '../config.php';
// Restreindre l'accès aux étudiants uniquement
securite_etudiant();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Étudiant - ADH</title>
</head>
<body>
    <h1>Dashboard Étudiant</h1>
    <p>Bienvenue, <?php echo $_SESSION['utilisateur_nom']; ?>!</p>
    <a href="../logout.php">Déconnexion</a>
</body>
</html>