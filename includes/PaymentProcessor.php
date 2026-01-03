<?php
require_once __DIR__ . '/../config/stripe-config.php';
require_once __DIR__ . '/../config/moncash-config.php';
require_once __DIR__ . '/Database.php';

class PaymentProcessor {
    private $stripe;
    private $db;
    private $commission_rate;
    
    public function __construct($pdo) {
        // Initialiser Stripe
        \Stripe\Stripe::setApiKey(StripeConfig::getSecretKey());
        $this->stripe = new \Stripe\StripeClient(StripeConfig::getSecretKey());
        
        // Initialiser la base de données
        $this->db = new PaymentDatabase($pdo);
        
        // Taux de commission
        $this->commission_rate = StripeConfig::PLATFORM_COMMISSION;
    }
    
    /**
     * Crée un paiement Stripe
     */
    public function createStripePayment($user_id, $amount, $currency = 'eur', $metadata = []) {
        try {
            // Générer un ID de transaction unique
            $transaction_id = 'stripe_' . time() . '_' . uniqid();
            
            // Créer ou récupérer le client Stripe
            $customer = $this->getOrCreateStripeCustomer($user_id);
            
            // Créer un PaymentIntent
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $this->amountToCents($amount, $currency),
                'currency' => $currency,
                'customer' => $customer->id,
                'metadata' => array_merge($metadata, [
                    'transaction_id' => $transaction_id,
                    'user_id' => $user_id
                ]),
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);
            
            // Calculer les montants (commission 20%)
            $platform_fee = $amount * $this->commission_rate;
            $teacher_amount = $amount - $platform_fee;
            
            // Enregistrer la transaction
            $this->db->createTransaction([
                'transaction_id' => $transaction_id,
                'payment_method' => 'stripe',
                'user_id' => $user_id,
                'amount' => $amount,
                'platform_fee' => $platform_fee,
                'teacher_amount' => $teacher_amount,
                'currency' => strtoupper($currency),
                'payment_intent_id' => $paymentIntent->id,
                'customer_id' => $customer->id,
                'status' => 'pending',
                'metadata' => $metadata
            ]);
            
            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'transaction_id' => $transaction_id,
                'payment_intent_id' => $paymentIntent->id,
                'publishable_key' => StripeConfig::getPublishableKey()
            ];
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Stripe API Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        } catch (Exception $e) {
            error_log("Payment Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Une erreur est survenue lors du traitement du paiement.'
            ];
        }
    }
    
    /**
     * Crée un paiement MonCash
     */
    public function createMonCashPayment($user_id, $amount_eur, $course_id = null, $teacher_id = null) {
        try {
            // Convertir EUR en HTG (Gourdes haïtiennes)
            $amount_htg = $amount_eur * MonCashConfig::EUR_TO_HTG;
            $amount_htg = round($amount_htg, 2);
            
            // Générer un ID de transaction unique
            $transaction_id = 'moncash_' . time() . '_' . uniqid();
            
            // Préparer la transaction
            $order_id = 'ORDER_' . $transaction_id;
            
            // Calculer les montants (commission 20%)
            $platform_fee = $amount_eur * $this->commission_rate;
            $teacher_amount = $amount_eur - $platform_fee;
            
            // Enregistrer la transaction
            $transaction_data = [
                'transaction_id' => $transaction_id,
                'payment_method' => 'moncash',
                'user_id' => $user_id,
                'teacher_id' => $teacher_id,
                'course_id' => $course_id,
                'amount' => $amount_eur,
                'platform_fee' => $platform_fee,
                'teacher_amount' => $teacher_amount,
                'currency' => 'HTG',
                'status' => 'pending',
                'metadata' => [
                    'order_id' => $order_id,
                    'amount_htg' => $amount_htg,
                    'exchange_rate' => MonCashConfig::EUR_TO_HTG
                ]
            ];
            
            $this->db->createTransaction($transaction_data);
            
            // Préparer la requête pour MonCash
            $redirect_url = $this->createMonCashRedirect($order_id, $amount_htg);
            
            if (!$redirect_url) {
                throw new Exception("Erreur lors de la création du lien MonCash");
            }
            
            return [
                'success' => true,
                'redirect_url' => $redirect_url,
                'transaction_id' => $transaction_id,
                'order_id' => $order_id,
                'amount_htg' => $amount_htg
            ];
            
        } catch (Exception $e) {
            error_log("MonCash Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Vérifie le statut d'un paiement
     */
    public function verifyPaymentStatus($transaction_id) {
        $sql = "SELECT status, payment_method, payment_intent_id 
                FROM transactions 
                WHERE transaction_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$transaction_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            return ['success' => false, 'error' => 'Transaction non trouvée'];
        }
        
        // Vérifier le statut selon la méthode de paiement
        if ($transaction['payment_method'] === 'stripe' && $transaction['payment_intent_id']) {
            try {
                $paymentIntent = $this->stripe->paymentIntents->retrieve(
                    $transaction['payment_intent_id']
                );
                
                $status = $paymentIntent->status;
                
                // Mettre à jour le statut si nécessaire
                if ($status === 'succeeded' && $transaction['status'] !== 'completed') {
                    $this->db->updateTransactionStatus($transaction_id, 'completed');
                } elseif ($status === 'canceled' && $transaction['status'] !== 'failed') {
                    $this->db->updateTransactionStatus($transaction_id, 'failed');
                }
                
                return [
                    'success' => true,
                    'status' => $status,
                    'transaction_status' => $transaction['status']
                ];
                
            } catch (\Stripe\Exception\ApiErrorException $e) {
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'transaction_status' => $transaction['status']
                ];
            }
        }
        
        return [
            'success' => true,
            'status' => $transaction['status']
        ];
    }
    
    /**
     * Effectue un remboursement
     */
    public function processRefund($transaction_id, $amount = null, $reason = 'customer_request') {
        try {
            // Récupérer la transaction
            $sql = "SELECT * FROM transactions WHERE transaction_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$transaction_id]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                throw new Exception("Transaction non trouvée");
            }
            
            if ($transaction['status'] !== 'completed') {
                throw new Exception("Seules les transactions complétées peuvent être remboursées");
            }
            
            // Traiter le remboursement selon la méthode de paiement
            if ($transaction['payment_method'] === 'stripe') {
                return $this->processStripeRefund($transaction, $amount, $reason);
            } elseif ($transaction['payment_method'] === 'moncash') {
                return $this->processMonCashRefund($transaction, $amount, $reason);
            } else {
                throw new Exception("Méthode de paiement non supportée pour le remboursement");
            }
            
        } catch (Exception $e) {
            error_log("Refund Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Payer un professeur
     */
    public function payTeacher($teacher_id, $payment_method = 'stripe_transfer') {
        try {
            // Calculer le montant dû au professeur
            $sql = "SELECT SUM(teacher_amount) as total_due
                    FROM transactions 
                    WHERE teacher_id = ? 
                    AND status = 'completed'
                    AND teacher_payment_status = 'pending'";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$teacher_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $total_due = $result['total_due'] ?? 0;
            
            if ($total_due <= 0) {
                return [
                    'success' => false,
                    'error' => 'Aucun paiement en attente pour ce professeur'
                ];
            }
            
            // Créer un transfert Stripe
            if ($payment_method === 'stripe_transfer') {
                $teacher = $this->getTeacherStripeAccount($teacher_id);
                
                if (!$teacher || !$teacher['stripe_account_id']) {
                    throw new Exception("Le professeur n'a pas de compte Stripe connecté");
                }
                
                // Créer le transfert
                $transfer = $this->stripe->transfers->create([
                    'amount' => $this->amountToCents($total_due, 'eur'),
                    'currency' => 'eur',
                    'destination' => $teacher['stripe_account_id'],
                    'transfer_group' => 'TEACHER_' . $teacher_id . '_' . time()
                ]);
                
                // Enregistrer le paiement
                $this->recordTeacherPayment($teacher_id, $total_due, $transfer->id);
                
                // Marquer les transactions comme payées
                $this->markTransactionsAsPaid($teacher_id, $transfer->id);
                
                return [
                    'success' => true,
                    'transfer_id' => $transfer->id,
                    'amount' => $total_due,
                    'status' => $transfer->status
                ];
            }
            
            // Pour les autres méthodes de paiement (virement bancaire, MonCash)
            // Implémentez la logique spécifique ici
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Stripe Transfer Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        } catch (Exception $e) {
            error_log("Teacher Payment Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Méthodes privées utilitaires
    private function getOrCreateStripeCustomer($user_id) {
        // Vérifier si le client existe déjà
        $sql = "SELECT stripe_customer_id FROM user_payment_methods WHERE user_id = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['stripe_customer_id']) {
            try {
                return $this->stripe->customers->retrieve($result['stripe_customer_id']);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                // Le client n'existe plus chez Stripe, on en crée un nouveau
            }
        }
        
        // Récupérer les infos utilisateur
        $sql = "SELECT nom, email FROM utilisateurs WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Créer un nouveau client Stripe
        $customer = $this->stripe->customers->create([
            'email' => $user['email'],
            'name' => $user['nom'],
            'metadata' => ['user_id' => $user_id]
        ]);
        
        // Enregistrer dans la base de données
        $sql = "INSERT INTO user_payment_methods (user_id, stripe_customer_id, is_default) 
                VALUES (?, ?, TRUE)
                ON DUPLICATE KEY UPDATE stripe_customer_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id, $customer->id, $customer->id]);
        
        return $customer;
    }
    
    private function amountToCents($amount, $currency) {
        // Stripe utilise des centimes pour l'EUR
        if (in_array(strtolower($currency), ['eur', 'usd', 'cad', 'aud'])) {
            return (int)($amount * 100);
        }
        // Certaines devises n'utilisent pas de décimales
        return (int)$amount;
    }
    
    private function createMonCashRedirect($order_id, $amount) {
        // Implémentez l'intégration MonCash API ici
        // Cette fonction doit retourner l'URL de redirection pour le paiement
        
        // Pour l'exemple, retournons une URL factice
        // En production, utilisez l'API MonCash
        return MonCashConfig::BASE_URL . "/Payment/Redirect?token=" . urlencode($order_id);
    }
    
    private function processStripeRefund($transaction, $amount, $reason) {
        $refund_data = [
            'payment_intent' => $transaction['payment_intent_id']
        ];
        
        if ($amount) {
            $refund_data['amount'] = $this->amountToCents($amount, $transaction['currency']);
        }
        
        $refund = $this->stripe->refunds->create($refund_data);
        
        // Enregistrer le remboursement
        $this->recordRefund($transaction['transaction_id'], $refund->id, 
                           $amount ?: $transaction['amount'], $reason);
        
        // Mettre à jour le statut de la transaction
        $new_status = $amount && $amount < $transaction['amount'] ? 'partially_refunded' : 'refunded';
        $this->db->updateTransactionStatus($transaction['transaction_id'], $new_status);
        
        return [
            'success' => true,
            'refund_id' => $refund->id,
            'status' => $refund->status,
            'amount' => $amount ?: $transaction['amount']
        ];
    }
    
    private function recordRefund($transaction_id, $refund_id, $amount, $reason) {
        $sql = "INSERT INTO refunds (transaction_id, refund_id, amount, reason, status) 
                VALUES (?, ?, ?, ?, 'pending')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$transaction_id, $refund_id, $amount, $reason]);
    }
    
    private function getTeacherStripeAccount($teacher_id) {
        // Récupérer le compte Stripe du professeur
        $sql = "SELECT stripe_account_id FROM teacher_stripe_accounts WHERE teacher_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$teacher_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function recordTeacherPayment($teacher_id, $amount, $transfer_id) {
        $sql = "INSERT INTO teacher_payments (teacher_id, amount, transfer_id, status) 
                VALUES (?, ?, ?, 'pending')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$teacher_id, $amount, $transfer_id]);
    }
    
    private function markTransactionsAsPaid($teacher_id, $transfer_id) {
        $sql = "UPDATE transactions 
                SET teacher_payment_status = 'paid', 
                    teacher_payment_date = NOW(),
                    teacher_transfer_id = ?
                WHERE teacher_id = ? 
                AND status = 'completed' 
                AND teacher_payment_status = 'pending'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$transfer_id, $teacher_id]);
    }
}
?>