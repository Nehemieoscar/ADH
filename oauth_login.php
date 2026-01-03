
<?php
// oauth_login.php
require_once 'config.php';

$provider = $_GET['provider'] ?? '';

// Stocker le provider dans la session
$_SESSION['oauth_provider'] = $provider;

switch ($provider) {
    case 'facebook':
        $params = [
            'client_id' => FACEBOOK_APP_ID,
            'redirect_uri' => FACEBOOK_REDIRECT_URI,
            'scope' => 'email,public_profile',
            'response_type' => 'code',
            'state' => bin2hex(random_bytes(16))
        ];
        $_SESSION['oauth_state'] = $params['state'];
        $authUrl = 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
        break;

    case 'google':
        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'scope' => 'openid email profile',
            'response_type' => 'code',
            'state' => bin2hex(random_bytes(16)),
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        $_SESSION['oauth_state'] = $params['state'];
        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
        break;

    case 'linkedin':
        $params = [
            'client_id' => LINKEDIN_CLIENT_ID,
            'redirect_uri' => LINKEDIN_REDIRECT_URI,
            'scope' => 'openid profile email',
            'response_type' => 'code',
            'state' => bin2hex(random_bytes(16)),
            'prompt' => 'login'
        ];
        $_SESSION['oauth_state'] = $params['state'];
        $authUrl = 'https://www.linkedin.com/oauth/v2/authorization?' . http_build_query($params);
        break;

    default:
        die('Provider non valide');
}

header('Location: ' . $authUrl);
exit;
?>
