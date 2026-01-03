<?php 
include 'config.php';

// Redirection selon le rôle si déjà connecté
if (est_connecte()) {
    rediriger_selon_role();
}

$erreur = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = securiser($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe'];
    
    if (empty($email) || empty($mot_de_passe)) {
        $erreur = "Tous les champs sont obligatoires.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? AND statut = 'actif'");
            $stmt->execute([$email]);
            $utilisateur = $stmt->fetch();
            
            if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
                // Connexion réussie
                $_SESSION['utilisateur_id'] = $utilisateur['id'];
                $_SESSION['utilisateur_nom'] = $utilisateur['nom'];
                $_SESSION['utilisateur_role'] = $utilisateur['role'];
                $_SESSION['utilisateur_email'] = $utilisateur['email'];
                
                // Redirection selon le rôle
                rediriger_selon_role();
                
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
    <style>
        /* Votre CSS existant pour le login */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-logo span {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .container {
            width: 100%;
            max-width: 400px;
        }
        
        .card {
            background: white;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .alert {
            padding: 12px 16px;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .alert-error {
            background: #fee;
            border: 1px solid #fdd;
            color: #c00;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .btn {
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            background: #667eea;
            color: white;
        }
        
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <span>ADH</span>
            </div>
            <div class="nav-actions">
                <a href="register.php" class="btn btn-outline">S'inscrire</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <div class="card">
                <h2 style="text-align: center; margin-bottom: 2rem;">Connexion</h2>
                
                <?php if ($erreur): ?>
                    <div class="alert alert-error"><?php echo $erreur; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="mot_de_passe" class="form-label">Mot de passe</label>
                        <input type="password" id="mot_de_passe" name="mot_de_passe" 
                               class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn">Se connecter</button>
                </form>
                
                <div class="register-link">
                    <p>
                        Pas encore de compte ? 
                        <a href="register.php">S'inscrire</a>
                    </p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>