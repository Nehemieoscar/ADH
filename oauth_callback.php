<?php
// oauth_callback.php
require_once 'config.php';

// Détecter le provider via le paramètre GET ou via le state
$provider = $_GET['provider'] ?? '';
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

// Si pas de provider dans l'URL, détecter via la session
if (empty($provider) && isset($_SESSION['oauth_provider'])) {
    $provider = $_SESSION['oauth_provider'];
}

// Vérification du state pour la sécurité
if (empty($state) || $state !== ($_SESSION['oauth_state'] ?? '')) {
    die('Erreur de validation du state');
}

unset($_SESSION['oauth_state']);
unset($_SESSION['oauth_provider']);

if (empty($code)) {
    die('Code d\'autorisation manquant');
}

try {
    switch ($provider) {
        case 'facebook':
            $userData = handleFacebookAuth($code);
            break;
        case 'google':
            $userData = handleGoogleAuth($code);
            break;
        case 'linkedin':
            $userData = handleLinkedInAuth($code);
            break;
        default:
            die('Provider non valide');
    }

    // Enregistrer ou mettre à jour l'utilisateur
    $userId = saveOrUpdateUser($userData, $provider);

    // Créer la session utilisateur
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $userData['username'];
    $_SESSION['email'] = $userData['email'];
    $_SESSION['role'] = $userData['role'];

    // Rediriger vers la page principale
    header('Location: index.php');
    exit;

} catch (Exception $e) {
    die('Erreur d\'authentification : ' . $e->getMessage());
}

// Fonction pour Facebook
function handleFacebookAuth($code) {
    // Échanger le code contre un access token
    $tokenUrl = 'https://graph.facebook.com/v18.0/oauth/access_token';
    $tokenParams = [
        'client_id' => FACEBOOK_APP_ID,
        'client_secret' => FACEBOOK_APP_SECRET,
        'redirect_uri' => FACEBOOK_REDIRECT_URI,
        'code' => $code
    ];

    $tokenResponse = file_get_contents($tokenUrl . '?' . http_build_query($tokenParams));
    $tokenData = json_decode($tokenResponse, true);

    if (!isset($tokenData['access_token'])) {
        throw new Exception('Token Facebook non reçu');
    }

    // Récupérer les infos utilisateur
    $userUrl = 'https://graph.facebook.com/me?fields=id,name,email,picture&access_token=' . $tokenData['access_token'];
    $userResponse = file_get_contents($userUrl);
    $fbUser = json_decode($userResponse, true);

    return [
        'oauth_uid' => $fbUser['id'],
        'username' => $fbUser['name'] ?? 'user_fb_' . $fbUser['id'],
        'email' => $fbUser['email'] ?? '',
        'profile_picture' => $fbUser['picture']['data']['url'] ?? '',
        'oauth_token' => $tokenData['access_token'],
        'role' => 'user'
    ];
}

// Fonction pour Google
function handleGoogleAuth($code) {
    // Échanger le code contre un access token
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $tokenParams = [
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'code' => $code,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenParams));
    $tokenResponse = curl_exec($ch);
    curl_close($ch);

    $tokenData = json_decode($tokenResponse, true);

    if (!isset($tokenData['access_token'])) {
        throw new Exception('Token Google non reçu');
    }

    // Récupérer les infos utilisateur
    $userUrl = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $tokenData['access_token'];
    $userResponse = file_get_contents($userUrl);
    $googleUser = json_decode($userResponse, true);

    return [
        'oauth_uid' => $googleUser['id'],
        'username' => $googleUser['name'] ?? explode('@', $googleUser['email'])[0],
        'email' => $googleUser['email'] ?? '',
        'profile_picture' => $googleUser['picture'] ?? '',
        'oauth_token' => $tokenData['access_token'],
        'role' => 'user'
    ];
}

// Fonction pour LinkedIn
function handleLinkedInAuth($code) {
    // Échanger le code contre un access token
    $tokenUrl = 'https://www.linkedin.com/oauth/v2/accessToken';
    $tokenParams = [
        'client_id' => LINKEDIN_CLIENT_ID,
        'client_secret' => LINKEDIN_CLIENT_SECRET,
        'redirect_uri' => LINKEDIN_REDIRECT_URI,
        'code' => $code,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenParams));
    $tokenResponse = curl_exec($ch);
    curl_close($ch);

    $tokenData = json_decode($tokenResponse, true);

    if (!isset($tokenData['access_token'])) {
        throw new Exception('Token LinkedIn non reçu');
    }

    // Récupérer les infos utilisateur
    $ch = curl_init('https://api.linkedin.com/v2/userinfo');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $tokenData['access_token']
    ]);
    $userResponse = curl_exec($ch);
    curl_close($ch);

    $linkedinUser = json_decode($userResponse, true);

    return [
        'oauth_uid' => $linkedinUser['sub'],
        'username' => $linkedinUser['name'] ?? explode('@', $linkedinUser['email'])[0],
        'email' => $linkedinUser['email'] ?? '',
        'profile_picture' => $linkedinUser['picture'] ?? '',
        'oauth_token' => $tokenData['access_token'],
        'role' => 'user'
    ];
}

// Fonction pour enregistrer ou mettre à jour l'utilisateur
function saveOrUpdateUser($userData, $provider) {
    global $pdo;

    // Vérifier si l'utilisateur existe déjà (par OAuth UID)
    $stmt = $pdo->prepare("
        SELECT id FROM utilisateurs 
        WHERE oauth_provider = ? AND oauth_uid = ?
    ");
    $stmt->execute([$provider, $userData['oauth_uid']]);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        // Mettre à jour l'utilisateur existant
        $stmt = $pdo->prepare("
            UPDATE utilisateurs 
            SET oauth_token = ?, profile_picture = ?, last_login = NOW(), updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $userData['oauth_token'],
            $userData['profile_picture'],
            $existingUser['id']
        ]);
        return $existingUser['id'];
    }

    // Vérifier si un utilisateur avec cet email existe déjà
    if (!empty($userData['email'])) {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$userData['email']]);
        $emailUser = $stmt->fetch();

        if ($emailUser) {
            // Lier le compte OAuth à l'utilisateur existant
            $stmt = $pdo->prepare("
                UPDATE utilisateurs 
                SET oauth_provider = ?, oauth_uid = ?, oauth_token = ?, 
                    profile_picture = ?, last_login = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $provider,
                $userData['oauth_uid'],
                $userData['oauth_token'],
                $userData['profile_picture'],
                $emailUser['id']
            ]);
            return $emailUser['id'];
        }
    }

    // Créer un nouvel utilisateur
    $stmt = $pdo->prepare("
        INSERT INTO utilisateurs 
        (username, email, password, role, oauth_provider, oauth_uid, oauth_token, profile_picture, created_at, updated_at, last_login)
        VALUES (?, ?, '', ?, ?, ?, ?, ?, NOW(), NOW(), NOW())
    ");
    $stmt->execute([
        $userData['username'],
        $userData['email'],
        $userData['role'],
        $provider,
        $userData['oauth_uid'],
        $userData['oauth_token'],
        $userData['profile_picture']
    ]);

    return $pdo->lastInsertId();
}
?>