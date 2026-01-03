<?php
require_once __DIR__ . '/../../config.php';

if (!est_admin()) {
    header('Location: ../login.php');
    exit;
}

// Récupérer les candidatures
try {
    $stmt = $pdo->query("SELECT * FROM teacher_applications ORDER BY created_at DESC");
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    $applications = [];
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Applications Professeurs - Admin</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>.container{max-width:1000px;margin:2rem auto}</style>
</head>
<body>
<div class="container">
    <h1>Demandes d'inscription Professeur</h1>
    <?php if (empty($applications)): ?>
        <p>Aucune demande pour le moment.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Id</th><th>Nom</th><th>Email</th><th>Qualifications</th><th>Message</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($applications as $app): ?>
                <tr>
                    <td><?php echo $app['id']; ?></td>
                    <td><?php echo htmlspecialchars($app['nom']); ?></td>
                    <td><?php echo htmlspecialchars($app['email']); ?></td>
                    <td style="max-width:250px;"><?php echo nl2br(htmlspecialchars($app['qualifications'])); ?></td>
                    <td style="max-width:250px;"><?php echo nl2br(htmlspecialchars($app['message'])); ?></td>
                    <td><?php echo $app['statut']; ?></td>
                    <td>
                        <?php if ($app['statut'] === 'pending'): ?>
                            <input type="password" id="pw-<?php echo $app['id']; ?>" placeholder="Mot de passe prof" style="width:140px;" />
                            <button onclick="approve(<?php echo $app['id']; ?>)">Approuver</button>
                            <button onclick="reject(<?php echo $app['id']; ?>)">Rejeter</button>
                        <?php else: ?>
                            <em><?php echo $app['statut']; ?></em>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
async function approve(id){
    const pw = document.getElementById('pw-'+id).value;
    if(!pw){ alert('Entrez un mot de passe pour le professeur'); return; }
    const res = await fetch('../api/approve_teacher.php', { method: 'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({application_id:id, password: pw}) });
    const j = await res.json();
    if(j.success){ alert('Professeur créé'); location.reload(); } else { alert('Erreur: '+(j.message||'')); }
}

async function reject(id){
    if(!confirm('Rejeter cette candidature ?')) return;
    const res = await fetch('../api/approve_teacher.php', { method: 'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({application_id:id, action:'reject'}) });
    const j = await res.json();
    if(j.success){ alert('Candidature rejetée'); location.reload(); } else { alert('Erreur: '+(j.message||'')); }
}
</script>
</body>
</html>
