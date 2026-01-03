<?php
include 'config.php';

// Créer un compte administrateur
$nom = "Administrateur ADH";
$email = "admin@adh.com";
$mot_de_passe = "Admin123!"; // À changer après

try {
    // Vérifier si l'admin existe déjà
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    
    if (!$stmt->fetch()) {
        $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role, statut) VALUES (?, ?, ?, 'admin', 'actif')");
        
        if ($stmt->execute([$nom, $email, $mot_de_passe_hash])) {
            echo "Compte administrateur créé avec succès!<br>";
            echo "Email: $email<br>";
            echo "Mot de passe: $mot_de_passe<br>";
            echo "<strong>Note : Changez ce mot de passe immédiatement après la première connexion!</strong>";
        }
    } else {
        echo "Le compte administrateur existe déjà.";
    }
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}
?>