<?php
include '../config.php';
securite_admin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - ADH</title>
</head>
<body>
    <h1>Dashboard Administrateur</h1>
    <p>Bienvenue, <?php echo $_SESSION['utilisateur_nom']; ?>!</p>
    <a href="../logout.php">Déconnexion</a>
</body>
</html>