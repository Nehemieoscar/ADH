<?php
// Configuration MonCash pour Haïti

class MonCashConfig {
    // Credentials MonCash (sandbox ou production)
    const CLIENT_ID = 'your_client_id'; // À remplacer
    const CLIENT_SECRET = 'your_client_secret'; // À remplacer
    
    // URLs MonCash
    const BASE_URL = 'https://sandbox.moncashbutton.digicelgroup.com/Moncash-business'; // Sandbox
    // const BASE_URL = 'https://moncashbutton.digicelgroup.com/Moncash-business'; // Production
    
    // URLs de callback
    const RETURN_URL = 'https://votresite.com/paiement/moncash-return.php';
    const NOTIFY_URL = 'https://votresite.com/api/moncash-webhook.php';
    
    // Timeout en secondes
    const TIMEOUT = 30;
}
?>