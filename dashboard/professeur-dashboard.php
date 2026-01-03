<?php
include '../config.php';
securite_professeur();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Professeur - ADH</title>
</head>
<body>
    <h1>Dashboard Professeur</h1>
    <p>Bienvenue, <?php echo $_SESSION['utilisateur_nom']; ?>!</p>
    <a href="../logout.php">Déconnexion</a>
</body>
</html>