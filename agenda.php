<?php
include 'config.php';

if (!est_connecte()) {
    header('Location: login.php');
    exit;
}

$utilisateur = obtenir_utilisateur_connecte();

// Récupérer les événements de l'utilisateur
$stmt_evenements = $pdo->prepare("
    SELECT e.*, c.titre as cours_titre 
    FROM evenements e
    LEFT JOIN cours c ON e.cours_id = c.id
    WHERE e.utilisateur_id = ? OR e.cours_id IN (SELECT cours_id FROM inscriptions WHERE utilisateur_id = ?)
    ORDER BY e.date_debut
");
$stmt_evenements->execute([$_SESSION['utilisateur_id'], $_SESSION['utilisateur_id']]);
$evenements = $stmt_evenements->fetchAll();

// Récupérer les cours de l'utilisateur pour les ajouter à l'agenda
$stmt_cours = $pdo->prepare("
    SELECT c.*, i.progression
    FROM inscriptions i
    JOIN cours c ON i.cours_id = c.id
    WHERE i.utilisateur_id = ?
");
$stmt_cours->execute([$_SESSION['utilisateur_id']]);
$cours = $stmt_cours->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $utilisateur['mode_sombre'] ? 'sombre' : 'clair'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Intelligent - ADH</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/agenda.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>

        <main class="dashboard-main">
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>📅 Agenda Intelligent</h1>
                    <p>Organisez votre apprentissage et vos événements</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-primary" id="add-event-btn">➕ Ajouter un événement</button>
                </div>
            </header>

            <div class="dashboard-content">
                <div class="agenda-container">
                    <!-- Sidebar de l'agenda -->
                    <div class="agenda-sidebar">
                        <!-- Mini calendrier -->
                        <div class="card">
                            <h3>Calendrier</h3>
                            <div id="mini-calendar"></div>
                        </div>

                        <!-- Liste des cours -->
                        <div class="card">
                            <h3>Mes cours</h3>
                            <div class="cours-list">
                                <?php foreach ($cours as $c): ?>
                                    <div class="cours-item" data-cours-id="<?php echo $c['id']; ?>">
                                        <div class="cours-color" style="background: <?php echo $c['couleur'] ?? '#0052b4'; ?>"></div>
                                        <div class="cours-info">
                                            <strong><?php echo $c['titre']; ?></strong>
                                            <span><?php echo $c['progression']; ?>% complété</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Filtres -->
                        <div class="card">
                            <h3>Filtres</h3>
                            <div class="filters-list">
                                <label>
                                    <input type="checkbox" name="filter" value="cours" checked> Cours
                                </label>
                                <label>
                                    <input type="checkbox" name="filter" value="evenements" checked> Événements
                                </label>
                                <label>
                                    <input type="checkbox" name="filter" value="personnel" checked> Personnel
                                </label>
                                <label>
                                    <input type="checkbox" name="filter" value="examens" checked> Examens
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Calendrier principal -->
                    <div class="agenda-main">
                        <div class="card">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal pour ajouter/modifier un événement -->
    <div id="event-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ajouter un événement</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="event-form">
                    <input type="hidden" id="event-id">
                    
                    <div class="form-group">
                        <label for="event-title">Titre *</label>
                        <input type="text" id="event-title" class="form-control" required>
                    </div>

                    <div class="grid grid-2">
                        <div class="form-group">
                            <label for="event-start">Début *</label>
                            <input type="datetime-local" id="event-start" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="event-end">Fin *</label>
                            <input type="datetime-local" id="event-end" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="event-description">Description</label>
                        <textarea id="event-description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="event-type">Type d'événement</label>
                        <select id="event-type" class="form-control">
                            <option value="personnel">Personnel</option>
                            <option value="cours">Cours</option>
                            <option value="examen">Examen</option>
                            <option value="revision">Révision</option>
                            <option value="projet">Projet</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="event-cours">Associer à un cours (optionnel)</label>
                        <select id="event-cours" class="form-control">
                            <option value="">Aucun</option>
                            <?php foreach ($cours as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['titre']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="event-color">Couleur</label>
                        <input type="color" id="event-color" class="form-control" value="#0052b4">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <button type="button" class="btn btn-outline" id="delete-event-btn" style="display: none;">Supprimer</button>
                        <button type="button" class="btn btn-outline close-modal">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/locales/fr.js"></script>
    <script src="js/script.js"></script>
    <script src="js/dashboard.js"></script>
    <script src="js/agenda.js"></script>
</body>
</html>