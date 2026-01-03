<?php
// Gestionnaire de base de données pour les transactions

class PaymentDatabase {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Crée les tables nécessaires pour les paiements
     */
    public function createPaymentTables() {
        $queries = [
            // Table des transactions
            "CREATE TABLE IF NOT EXISTS transactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                transaction_id VARCHAR(255) UNIQUE NOT NULL,
                payment_method ENUM('stripe', 'moncash', 'bank_transfer') NOT NULL,
                user_id INT NOT NULL,
                teacher_id INT,
                course_id INT,
                amount DECIMAL(10,2) NOT NULL,
                platform_fee DECIMAL(10,2) DEFAULT 0,
                teacher_amount DECIMAL(10,2) DEFAULT 0,
                currency VARCHAR(3) DEFAULT 'EUR',
                status ENUM('pending', 'completed', 'failed', 'refunded', 'partially_refunded') DEFAULT 'pending',
                payment_intent_id VARCHAR(255),
                customer_id VARCHAR(255),
                payment_details JSON,
                metadata JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_teacher (teacher_id),
                INDEX idx_status (status),
                INDEX idx_created (created_at),
                FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
                FOREIGN KEY (teacher_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
                FOREIGN KEY (course_id) REFERENCES cours(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
            
            // Table des paiements aux professeurs
            "CREATE TABLE IF NOT EXISTS teacher_payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                transaction_id VARCHAR(255) NOT NULL,
                teacher_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                commission DECIMAL(10,2) DEFAULT 0,
                net_amount DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
                payment_date DATE,
                payment_method ENUM('stripe_transfer', 'bank_transfer', 'moncash') DEFAULT 'stripe_transfer',
                transfer_id VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_teacher (teacher_id),
                INDEX idx_status (status),
                FOREIGN KEY (teacher_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
            
            // Table des remboursements
            "CREATE TABLE IF NOT EXISTS refunds (
                id INT AUTO_INCREMENT PRIMARY KEY,
                transaction_id VARCHAR(255) NOT NULL,
                refund_id VARCHAR(255) UNIQUE NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                reason VARCHAR(500),
                status ENUM('pending', 'succeeded', 'failed') DEFAULT 'pending',
                metadata JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_transaction (transaction_id),
                FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
            
            // Table des cartes de crédit (pour Stripe)
            "CREATE TABLE IF NOT EXISTS user_payment_methods (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                stripe_customer_id VARCHAR(255),
                stripe_payment_method_id VARCHAR(255),
                card_brand VARCHAR(50),
                card_last4 VARCHAR(4),
                card_exp_month INT,
                card_exp_year INT,
                is_default BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        ];
        
        foreach ($queries as $query) {
            try {
                $this->pdo->exec($query);
            } catch (PDOException $e) {
                error_log("Erreur création table: " . $e->getMessage());
                throw $e;
            }
        }
    }
    
    /**
     * Enregistre une nouvelle transaction
     */
    public function createTransaction($data) {
        $sql = "INSERT INTO transactions (
            transaction_id, payment_method, user_id, teacher_id, course_id,
            amount, platform_fee, teacher_amount, currency, status,
            payment_intent_id, customer_id, payment_details, metadata
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['transaction_id'],
            $data['payment_method'],
            $data['user_id'],
            $data['teacher_id'] ?? null,
            $data['course_id'] ?? null,
            $data['amount'],
            $data['platform_fee'] ?? 0,
            $data['teacher_amount'] ?? 0,
            $data['currency'] ?? 'EUR',
            $data['status'] ?? 'pending',
            $data['payment_intent_id'] ?? null,
            $data['customer_id'] ?? null,
            json_encode($data['payment_details'] ?? []),
            json_encode($data['metadata'] ?? [])
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Met à jour le statut d'une transaction
     */
    public function updateTransactionStatus($transaction_id, $status, $payment_intent_id = null) {
        $sql = "UPDATE transactions SET status = ?, updated_at = NOW()";
        $params = [$status];
        
        if ($payment_intent_id) {
            $sql .= ", payment_intent_id = ?";
            $params[] = $payment_intent_id;
        }
        
        $sql .= " WHERE transaction_id = ?";
        $params[] = $transaction_id;
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Récupère les transactions avec filtres
     */
    public function getTransactions($filters = [], $limit = 50, $offset = 0) {
        $sql = "SELECT t.*, 
                       u.nom as user_name, 
                       u.email as user_email,
                       ut.nom as teacher_name,
                       c.titre as course_title
                FROM transactions t
                LEFT JOIN utilisateurs u ON t.user_id = u.id
                LEFT JOIN utilisateurs ut ON t.teacher_id = ut.id
                LEFT JOIN cours c ON t.course_id = c.id
                WHERE 1=1";
        
        $params = [];
        
        // Appliquer les filtres
        if (!empty($filters['user_id'])) {
            $sql .= " AND t.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['teacher_id'])) {
            $sql .= " AND t.teacher_id = ?";
            $params[] = $filters['teacher_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['payment_method'])) {
            $sql .= " AND t.payment_method = ?";
            $params[] = $filters['payment_method'];
        }
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(t.created_at) >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(t.created_at) <= ?";
            $params[] = $filters['end_date'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (t.transaction_id LIKE ? OR u.nom LIKE ? OR u.email LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère les statistiques de paiement
     */
    public function getPaymentStats($teacher_id = null, $start_date = null, $end_date = null) {
        $sql = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN status = 'completed' THEN platform_fee ELSE 0 END) as total_commission,
                    SUM(CASE WHEN status = 'completed' THEN teacher_amount ELSE 0 END) as total_teacher_payments,
                    COUNT(DISTINCT user_id) as unique_payers
                FROM transactions
                WHERE status = 'completed'";
        
        $params = [];
        
        if ($teacher_id) {
            $sql .= " AND teacher_id = ?";
            $params[] = $teacher_id;
        }
        
        if ($start_date) {
            $sql .= " AND DATE(created_at) >= ?";
            $params[] = $start_date;
        }
        
        if ($end_date) {
            $sql .= " AND DATE(created_at) <= ?";
            $params[] = $end_date;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Vérifie si un utilisateur a payé un cours
     */
    public function hasUserPaidForCourse($user_id, $course_id) {
        $sql = "SELECT COUNT(*) as paid 
                FROM transactions 
                WHERE user_id = ? 
                AND course_id = ? 
                AND status = 'completed'";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id, $course_id]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['paid'] > 0;
    }
    
    /**
     * Récupère les paiements d'un professeur
     */
    public function getTeacherPayments($teacher_id, $status = null) {
        $sql = "SELECT tp.*, t.amount as transaction_amount, t.created_at as payment_date
                FROM teacher_payments tp
                JOIN transactions t ON tp.transaction_id = t.transaction_id
                WHERE tp.teacher_id = ?";
        
        $params = [$teacher_id];
        
        if ($status) {
            $sql .= " AND tp.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY tp.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>