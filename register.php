<?php 
include 'config.php'; 
// require_once 'auth.php';
rediriger_si_connecte();

$erreur = '';
$success = '';
$nom = $email = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $nom = securiser($_POST['nom']);
    $email = securiser($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe'];
    $confirmation_mot_de_passe = $_POST['confirmation_mot_de_passe'];
    
    // Validation
    if (empty($nom) || empty($email) || empty($mot_de_passe) || empty($confirmation_mot_de_passe)) {
        $erreur = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";
    } elseif (strlen($mot_de_passe) < 6) {
        $erreur = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif ($mot_de_passe !== $confirmation_mot_de_passe) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } else {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $erreur = "Cet email est déjà utilisé.";
            } else {
                // Hacher le mot de passe
                $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                $token_verification = bin2hex(random_bytes(32));
                
                // Insérer l'utilisateur
               
$stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, token_verification, role, date_inscription) VALUES (?, ?, ?, ?, 'etudiant', NOW())");
                
                if ($stmt->execute([$nom, $email, $mot_de_passe_hash, $token_verification])) {
                    $success = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
                    // Réinitialiser les champs après succès
                    $nom = $email = '';
                } else {
                    $erreur = "Une erreur est survenue lors de la création du compte.";
                }
            }
        } catch (PDOException $e) {
            $erreur = "Erreur lors de l'inscription : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - ADH</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
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
        
        .nav-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .theme-toggle {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.5rem;
        }
        
        .btn {
            padding: 0.5rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .btn-outline {
            background: transparent;
            border-color: #667eea;
            color: #667eea;
        }
        
        .btn-outline:hover {
            background: #667eea;
            color: white;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .social-login {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .btn-social {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: 10px 14px;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            font-weight: 600;
        }

        .btn-google { background: #db4437; }
        .btn-linkedin { background: #0077b5; }
        
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
        
        h2 {
            text-align: center;
            margin-bottom: 2rem;
            color: #333;
            font-weight: 600;
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
        
        .alert-success {
            background: #efe;
            border: 1px solid #ded;
            color: #080;
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
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-control:invalid {
            border-color: #e74c3c;
        }
        
        button[type="submit"] {
            width: 100%;
            padding: 14px;
            font-size: 1rem;
            margin-top: 0.5rem;
        }
        
        @media (max-width: 480px) {
            .card {
                padding: 2rem 1.5rem;
            }
            
            .nav-container {
                padding: 0 1rem;
            }
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
                <button class="theme-toggle">🌙</button>
                <a href="login.php" class="btn btn-outline">Se connecter</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <div class="card">
                <h2>Créer un compte</h2>
                
                <div class="social-login">
                    <a class="btn-social btn-google" href="oauth_login.php?provider=google" rel="noopener noreferrer" title="S'inscrire avec Google">
                        <svg width="18" height="18" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="#fff" d="M44.5 20H24v8.5h11.9C34.4 33.7 30 36 24 36c-8.8 0-16-7.2-16-16s7.2-16 16-16c4.1 0 7.8 1.6 10.5 4.1l6.1-6.1C37.8 2.9 31.2 0 24 0 10.7 0 0 10.7 0 24s10.7 24 24 24c12.3 0 22.4-9.4 23.9-21.5.1-.9.1-1.5.1-1.5z"/></svg>
                        Google
                    </a>
                    <a class="btn-social btn-linkedin" href="oauth_login.php?provider=linkedin" rel="noopener noreferrer" title="S'inscrire avec LinkedIn">
                        <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="#fff" d="M4.98 3.5C4.98 4.88 3.87 6 2.5 6S0 4.88 0 3.5 1.12 1 2.5 1 4.98 2.12 4.98 3.5zM0 8.5h5v15H0v-15zM8.5 8.5h4.6v2h.1c.6-1 2.1-2 4.3-2 4.6 0 5.5 3 5.5 6.9v8.1h-5V16c0-2.1 0-4.8-3-4.8-3 0-3.4 2.3-3.4 4.6v7.7h-5v-15z"/></svg>
                        LinkedIn
                    </a>
                </div>

                <?php if ($erreur): ?>
                    <div class="alert alert-error"><?php echo $erreur; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form id="register-form" method="POST" novalidate>
                    <div class="form-group">
                        <label for="nom" class="form-label">Nom complet</label>
                        <input type="text" id="nom" name="nom" class="form-control" 
                               value="<?php echo htmlspecialchars($nom); ?>" 
                               required minlength="2" maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($email); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="mot_de_passe" class="form-label">Mot de passe</label>
                        <input type="password" id="mot_de_passe" name="mot_de_passe" 
                               class="form-control" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmation_mot_de_passe" class="form-label">Confirmer le mot de passe</label>
                        <input type="password" id="confirmation_mot_de_passe" 
                               name="confirmation_mot_de_passe" class="form-control" 
                               required minlength="6">
                    </div>
                    
                    <button type="submit" name="register" class="btn btn-primary">S'inscrire</button>
                </form>
                
                <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #eee;">
                    <p style="color: #666; font-size: 0.9rem;">
                        Déjà un compte ? 
                        <a href="login.php" style="color: #667eea; text-decoration: none; font-weight: 500;">
                            Se connecter
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Validation côté client
        document.getElementById('register-form').addEventListener('submit', function(e) {
            const password = document.getElementById('mot_de_passe').value;
            const confirmPassword = document.getElementById('confirmation_mot_de_passe').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 6 caractères.');
                return false;
            }
        });
        
        // Vérification en temps réel de la correspondance des mots de passe
        const passwordFields = ['mot_de_passe', 'confirmation_mot_de_passe'];
        passwordFields.forEach(field => {
            document.getElementById(field).addEventListener('input', function() {
                const password = document.getElementById('mot_de_passe').value;
                const confirmPassword = document.getElementById('confirmation_mot_de_passe').value;
                
                if (password && confirmPassword) {
                    if (password !== confirmPassword) {
                        document.getElementById('confirmation_mot_de_passe').style.borderColor = '#e74c3c';
                    } else {
                        document.getElementById('confirmation_mot_de_passe').style.borderColor = '#27ae60';
                    }
                }
            });
        });
    </script>
</body>
</html>