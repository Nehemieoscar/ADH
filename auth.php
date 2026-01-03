<?php
// auth.php - Gestion de l'authentification
session_start();

/**
 * Vérifie si l'utilisateur est connecté
 */
function est_connecte() {
    return isset($_SESSION['utilisateur_id']) && !empty($_SESSION['utilisateur_id']);
}

/**
 * Récupère les informations de l'utilisateur connecté
 */
function obtenir_utilisateur_connecte() {
    if (est_connecte()) {
        return [
            'id' => $_SESSION['utilisateur_id'] ?? null,
            'email' => $_SESSION['utilisateur_email'] ?? '',
            'nom' => $_SESSION['utilisateur_nom'] ?? '',
            'prenom' => $_SESSION['utilisateur_prenom'] ?? '',
            'role' => $_SESSION['utilisateur_role'] ?? 'utilisateur'
        ];
    }
    return null;
}

/**
 * Connecte un utilisateur (version simplifiée sans mise à jour de date)
 */
function connecter_utilisateur($utilisateur_id, $utilisateur_email, $utilisateur_nom, $utilisateur_prenom, $role = 'utilisateur') {
    $_SESSION['utilisateur_id'] = $utilisateur_id;
    $_SESSION['utilisateur_email'] = $utilisateur_email;
    $_SESSION['utilisateur_nom'] = $utilisateur_nom;
    $_SESSION['utilisateur_prenom'] = $utilisateur_prenom;
    $_SESSION['utilisateur_role'] = $role;
    
    // Optionnel: Mettre à jour la date de connexion si la colonne existe
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE utilisateurs SET date_connexion = NOW() WHERE id = ?");
        $stmt->execute([$utilisateur_id]);
    } catch (Exception $e) {
        // Ignorer l'erreur si la colonne n'existe pas
        error_log("Note: La colonne date_connexion n'existe pas encore - " . $e->getMessage());
    }
}

/**
 * Déconnecte l'utilisateur
 */
function deconnecter_utilisateur() {
    session_unset();
    session_destroy();
}

/**
 * Vérifie les identifiants de connexion
 */
function verifier_identifiants($email, $password) {
    try {
        $db = getDB();
        
        // Version sécurisée avec requête préparée
        $stmt = $db->prepare("SELECT id, email, nom, prenom, password, role FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($utilisateur && password_verify($password, $utilisateur['password'])) {
            return $utilisateur;
        }
        
        return false;
        
    } catch (PDOException $e) {
        error_log("Erreur de connexion: " . $e->getMessage());
        return false;
    }
}

/**
 * Crée un nouvel utilisateur
 */
function creer_utilisateur($prenom, $nom, $email, $password) {
    try {
        $db = getDB();
        
        // Vérifier si l'email existe déjà
        $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            return "Cet email est déjà utilisé";
        }
        
        // Hasher le mot de passe
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insérer le nouvel utilisateur
        $stmt = $db->prepare("INSERT INTO utilisateurs (prenom, nom, email, password, date_inscription) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$prenom, $nom, $email, $password_hash]);
        
        return $db->lastInsertId();
        
    } catch (PDOException $e) {
        error_log("Erreur d'inscription: " . $e->getMessage());
        return "Erreur lors de l'inscription";
    }
}
?>