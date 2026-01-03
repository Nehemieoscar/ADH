<?php 
include 'config.php';

// CORRECTION : Utiliser la nouvelle fonction
rediriger_si_connecte();

$erreur = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = securiser($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe'];
    
    if (empty($email) || empty($mot_de_passe)) {
        $erreur = "Tous les champs sont obligatoires.";
    } else {
        try {
            // Vérifier l'utilisateur avec le bon champ mot_de_passe
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? AND statut = 'actif'");
            $stmt->execute([$email]);
            $utilisateur = $stmt->fetch();
            
            if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
                // Connexion réussie
                $_SESSION['utilisateur_id'] = $utilisateur['id'];
                $_SESSION['utilisateur_nom'] = $utilisateur['nom'];
                $_SESSION['utilisateur_role'] = $utilisateur['role'];
                $_SESSION['utilisateur_email'] = $utilisateur['email'];
                
                // Mettre à jour la date de dernière connexion
                $stmt = $pdo->prepare("UPDATE utilisateurs SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$utilisateur['id']]);
                
                // Redirection selon rôle
                switch ($utilisateur['role']) {
                    case 'etudiant':
                        header('Location: dashboard/etudiant-dashboard.php');
                        break;
                    case 'professeur':
                        header('Location: dashboard/professeur-dashboard.php');
                        break;
                    case 'admin':
                    default:
                        header('Location: dashboard/dashboard.php');
                        break;
                }
                exit;
                
            } else {
                $erreur = "Email ou mot de passe incorrect.";
            }
        } catch (PDOException $e) {
            $erreur = "Erreur lors de la connexion : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - ADH</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Votre CSS existant */
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <span>ADH</span>
            </div>
            <div class="nav-actions">
                <button class="theme-toggle">🌙</button>
                <a href="register.php" class="btn btn-outline">S'inscrire</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <div class="card">
                <h2>Connexion</h2>
                <!-- Dans login.php et register.php -->
<div class="social-login">
    <a class="btn-social btn-google" href="oauth_login.php?provider=google" rel="noopener noreferrer" title="Se connecter avec Google">
        <svg width="18" height="18" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path fill="#fff" d="M44.5 20H24v8.5h11.9C34.4 33.7 30 36 24 36c-8.8 0-16-7.2-16-16s7.2-16 16-16c4.1 0 7.8 1.6 10.5 4.1l6.1-6.1C37.8 2.9 31.2 0 24 0 10.7 0 0 10.7 0 24s10.7 24 24 24c12.3 0 22.4-9.4 23.9-21.5.1-.9.1-1.5.1-1.5z"/>
        </svg>
        Google
    </a>
    <a class="btn-social btn-linkedin" href="oauth_login.php?provider=linkedin" rel="noopener noreferrer" title="Se connecter avec LinkedIn">
        <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path fill="#fff" d="M4.98 3.5C4.98 4.88 3.87 6 2.5 6S0 4.88 0 3.5 1.12 1 2.5 1 4.98 2.12 4.98 3.5zM0 8.5h5v15H0v-15zM8.5 8.5h4.6v2h.1c.6-1 2.1-2 4.3-2 4.6 0 5.5 3 5.5 6.9v8.1h-5V16c0-2.1 0-4.8-3-4.8-3 0-3.4 2.3-3.4 4.6v7.7h-5v-15z"/>
        </svg>
        LinkedIn
    </a>
</div>
                <?php if ($erreur): ?>
                    <div class="alert alert-error"><?php echo $erreur; ?></div>
                <?php endif; ?>
                
                <form id="login-form" method="POST">
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="mot_de_passe" class="form-label">Mot de passe</label>
                        <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Se connecter</button>
                </form>
                
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="mot-de-passe-oublie.php">Mot de passe oublié ?</a>
                </div>

                <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #eee;">
                    <p style="color: #666; font-size: 0.9rem;">
                        Pas de compte ? 
                        <a href="register.php" style="color: #667eea; text-decoration: none; font-weight: 500;">
                            S'inscrire
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>