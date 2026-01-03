<?php
/**
 * Page publique: Demande d'inscription pour les professeurs
 * Crée une entrée dans la table `teacher_applications` (voir SQL ci-dessous)
 *
 * SQL suggéré pour la table (exécuter une fois dans la base de données):
 *
 * CREATE TABLE teacher_applications (
 *   id INT AUTO_INCREMENT PRIMARY KEY,
 *   nom VARCHAR(255) NOT NULL,
 *   email VARCHAR(255) NOT NULL,
 *   telephone VARCHAR(50) DEFAULT NULL,
 *   qualifications TEXT,
 *   message TEXT,
 *   statut ENUM('pending','approved','rejected') DEFAULT 'pending',
 *   created_at DATETIME DEFAULT CURRENT_TIMESTAMP
 * );
 */

require_once 'config.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = securiser($_POST['nom'] ?? '');
    $email = securiser($_POST['email'] ?? '');
    $telephone = securiser($_POST['telephone'] ?? '');
    $qualifications = securiser($_POST['qualifications'] ?? '');
    $message = securiser($_POST['message'] ?? '');

    if (empty($nom) || empty($email)) {
        $errors[] = 'Le nom et l\'email sont obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email non valide.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO teacher_applications (nom, email, telephone, qualifications, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $email, $telephone, $qualifications, $message]);
            $success = 'Votre demande a été envoyée. Un administrateur la traitera sous peu.';
        } catch (PDOException $e) {
            $errors[] = 'Erreur serveur: ' . $e->getMessage();
        }
    }
}

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Demande Professeur - ADH</title>
    <link rel="stylesheet" href="css/style.css">
    <style>.container{max-width:700px;margin:3rem auto}</style>
</head>
<body>
<div class="container">
    <h1>Demande d'inscription Professeur</h1>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error"><?php echo implode('<br>', $errors); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group"><label>Nom complet</label>
        <input name="nom" class="form-control" required value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>"></div>

        <div class="form-group"><label>Email</label>
        <input name="email" type="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"></div>

            <div class="form-group"><label>Pays / Indicatif téléphonique</label>
            <select name="country_code" class="form-control" required>
                <option value="">-- Sélectionner un pays --</option>
                <option value="+509" selected>Haïti (+509)</option>
                <option value="+33">France (+33)</option>
                <option value="+41">Suisse (+41)</option>
                <option value="+32">Belgique (+32)</option>
                <option value="+1">États-Unis (+1)</option>
                <option value="+44">Royaume-Uni (+44)</option>
                <option value="+49">Allemagne (+49)</option>
                <option value="+39">Italie (+39)</option>
                <option value="+34">Espagne (+34)</option>
                <option value="+31">Pays-Bas (+31)</option>
                <option value="+43">Autriche (+43)</option>
            </select></div>

        <div class="form-group"><label>Téléphone</label>
        <input name="telephone" class="form-control" placeholder="Ex: 612345678" value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>"></div>

        <div class="form-group"><label>Qualifications / CV (PDF, max 5 Mo)</label>
        <input type="file" name="qualifications" class="form-control" accept=".pdf" required></div>

        <div class="form-group"><label>Qu'est ce qui vous motive?</label>
        <textarea name="message" class="form-control"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea></div>

        <button class="btn btn-primary" type="submit">Envoyer la demande</button>
    </form>
</div>
</body>
</html>
