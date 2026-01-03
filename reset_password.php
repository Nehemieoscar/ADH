<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    // Vérification du token CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf_token)) {
        throw new Exception('Token CSRF invalide');
    }

    // Récupération et nettoyage des données
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation des champs vides
    if (empty($email)) {
        throw new Exception('Veuillez remplir le champ email');
    }
    if (empty($newPassword)) {
        throw new Exception('Veuillez remplir le champ nouveau mot de passe');
    }
    if (empty($confirmPassword)) {
        throw new Exception('Veuillez confirmer votre nouveau mot de passe');
    }

    // Validation du format email
    if (!validateEmail($email)) {
        throw new Exception('Format d\'email invalide');
    }

    // Validation du mot de passe
    if (!validatePassword($newPassword)) {
        throw new Exception('Le nouveau mot de passe doit contenir au moins 6 caractères');
    }

    // Vérification de la confirmation du mot de passe
    if ($newPassword !== $confirmPassword) {
        throw new Exception('Veuillez confirmer votre nouveau mot de passe');
    }

    // Connexion à la base de données
    $pdo = DatabaseConfig::getConnection();

    // Vérifier si l'email existe dans la base de données
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('Aucun compte associé à cet email');
    }

    // Hachage sécurisé du nouveau mot de passe
    $passwordHash = password_hash($newPassword, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);

    // Mise à jour du mot de passe dans la base de données
    $stmt = $pdo->prepare("UPDATE utilisateurs SET password = ?, updated_at = NOW(), login_attempts = 0, account_locked = FALSE, locked_until = NULL WHERE email = ?");
    $result = $stmt->execute([$passwordHash, $email]);

    if ($result) {
        // Log de sécurité
        error_log("Mot de passe modifié pour l'utilisateur: " . $email);
        
        // Nettoyer les variables sensibles
        unset($newPassword, $confirmPassword, $passwordHash);
        
        echo json_encode([
            'success' => true,
            'message' => 'Mot de passe modifié avec succès'
        ]);
    } else {
        throw new Exception('Erreur lors de la modification du mot de passe');
    }

} catch (Exception $e) {
    // Log de l'erreur (sans données sensibles)
    error_log("Erreur modification mot de passe: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Nettoyer toutes les variables sensibles
    if (isset($newPassword)) unset($newPassword);
    if (isset($confirmPassword)) unset($confirmPassword);
    if (isset($passwordHash)) unset($passwordHash);
}
?>