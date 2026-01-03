<?php
header('Content-Type: application/json');
include '../config.php';

if (!est_connecte()) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$formation_id = (int)($data['formation_id'] ?? 0);

if (!$formation_id) {
    echo json_encode(['success' => false, 'message' => 'ID de formation invalide']);
    exit;
}

$utilisateur_id = $_SESSION['utilisateur_id'];

try {
    // Vérifier que la formation existe et est disponible
    $stmt_verify = $pdo->prepare("SELECT id FROM formations WHERE id = ? AND statut IN ('en_cours', 'termine')");
    $stmt_verify->execute([$formation_id]);
    if (!$stmt_verify->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Formation non disponible']);
        exit;
    }
    
    // Vérifier si l'utilisateur est déjà inscrit
    $stmt_check = $pdo->prepare("SELECT id FROM inscriptions WHERE utilisateur_id = ? AND formation_id = ?");
    $stmt_check->execute([$utilisateur_id, $formation_id]);
    if ($stmt_check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Vous êtes déjà inscrit à cette formation']);
        exit;
    }
    
    // Inscrire l'utilisateur
    $stmt_insert = $pdo->prepare("
        INSERT INTO inscriptions (utilisateur_id, formation_id, date_inscription, progression) 
        VALUES (?, ?, NOW(), 0)
    ");
    $stmt_insert->execute([$utilisateur_id, $formation_id]);
    
    echo json_encode(['success' => true, 'message' => 'Inscription réussie']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?>
