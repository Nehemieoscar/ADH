<?php
// Configuration sécurisée pour Stripe
// Stockez ces clés dans des variables d'environnement en production

class StripeConfig {
    // Mode de test ou production
    const MODE = 'test'; // 'test' ou 'live'
    
    // Clés API Stripe
    private static $config = [
        'test' => [
            'secret_key' => 'sk_test_...', // À remplacer
            'publishable_key' => 'pk_test_...', // À remplacer
            'webhook_secret' => 'whsec_...' // À remplacer
        ],
        'live' => [
            'secret_key' => 'sk_live_...', // À remplacer
            'publishable_key' => 'pk_live_...', // À remplacer
            'webhook_secret' => 'whsec_...' // À remplacer
        ]
    ];
    
    public static function getSecretKey() {
        return self::$config[self::MODE]['secret_key'];
    }
    
    public static function getPublishableKey() {
        return self::$config[self::MODE]['publishable_key'];
    }
    
    public static function getWebhookSecret() {
        return self::$config[self::MODE]['webhook_secret'];
    }
    
    // Commission de la plateforme (20%)
    const PLATFORM_COMMISSION = 0.20;
    
    // Devise par défaut (euros)
    const DEFAULT_CURRENCY = 'eur';
    
    // Taux de conversion pour MonCash (1 EUR = 132.5 HTG)
    const EUR_TO_HTG = 132.5;
}
?>