<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/PaymentProcessor.php';

// Vérifier l'authentification
session_start();
if (!isset($_SESSION['utilisateur_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

// Récupérer les données
$data = json_decode(file_get_contents('php://input'), true);

// Validation
$required_fields = ['amount', 'payment_method', 'course_id'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Champ manquant: $field"]);
        exit;
    }
}

try {
    // Initialiser le processeur de paiement
    $pdo = getPDO();
    $processor = new PaymentProcessor($pdo);
    
    $user_id = $_SESSION['utilisateur_id'];
    $amount = floatval($data['amount']);
    $payment_method = $data['payment_method'];
    $course_id = intval($data['course_id']);
    $teacher_id = $data['teacher_id'] ?? null;
    
    // Vérifier si l'utilisateur a déjà payé ce cours
    $db = new PaymentDatabase($pdo);
    if ($db->hasUserPaidForCourse($user_id, $course_id)) {
        echo json_encode([
            'success' => false, 
            'error' => 'Vous avez déjà payé ce cours'
        ]);
        exit;
    }
    
    // Traiter selon la méthode de paiement
    if ($payment_method === 'stripe') {
        $result = $processor->createStripePayment(
            $user_id, 
            $amount, 
            'eur', 
            [
                'course_id' => $course_id,
                'teacher_id' => $teacher_id
            ]
        );
    } elseif ($payment_method === 'moncash') {
        $result = $processor->createMonCashPayment(
            $user_id,
            $amount,
            $course_id,
            $teacher_id
        );
    } else {
        throw new Exception("Méthode de paiement non supportée");
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Create Payment Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur interne du serveur'
    ]);
}
?>