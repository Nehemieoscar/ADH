<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';

if (!est_connecte() || !est_admin()) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Accès refusé']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['application_id'])) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Données manquantes']);
    exit;
}

$appId = intval($input['application_id']);
$action = $input['action'] ?? 'approve';

try {
    $stmt = $pdo->prepare("SELECT * FROM teacher_applications WHERE id = ?");
    $stmt->execute([$appId]);
    $app = $stmt->fetch();
    if (!$app) {
        echo json_encode(['success'=>false,'message'=>'Demande introuvable']); exit;
    }

    if ($action === 'reject') {
        $u = $pdo->prepare("UPDATE teacher_applications SET statut = 'rejected' WHERE id = ?");
        $u->execute([$appId]);
        echo json_encode(['success'=>true,'message'=>'Candidature rejetée']); exit;
    }

    // Approve: create user
    if (empty($input['password'])) {
        echo json_encode(['success'=>false,'message'=>'Mot de passe requis pour créer le compte']); exit;
    }

    $password = $input['password'];
    // Utiliser ARGON2ID si disponible, sinon PASSWORD_DEFAULT
    $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
    $hash = password_hash($password, $algo);

    // Vérifier email unique
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
    $stmt->execute([$app['email']]);
    if ($stmt->fetch()) {
        echo json_encode(['success'=>false,'message'=>'Un utilisateur avec cet email existe déjà']); exit;
    }

    // Insérer utilisateur professeur
    $ins = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role, date_inscription, statut) VALUES (?, ?, ?, 'professeur', NOW(), 'actif')");
    $ins->execute([$app['nom'], $app['email'], $hash]);
    $newUserId = $pdo->lastInsertId();

    // Mettre à jour la candidature
    $u = $pdo->prepare("UPDATE teacher_applications SET statut = 'approved' WHERE id = ?");
    $u->execute([$appId]);

    echo json_encode(['success'=>true,'message'=>'Professeur créé','user_id'=>$newUserId]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    exit;
}
