<?php
include 'config.php';

if (!est_connecte()) {
    header('Location: login.php');
    exit;
}

$utilisateur = obtenir_utilisateur_connecte();

// Récupérer l'historique des conversations
$stmt_historique = $pdo->prepare("
    SELECT * FROM assistant_ia_conversations 
    WHERE utilisateur_id = ? 
    ORDER BY date_dernier_message DESC 
    LIMIT 10
");
$stmt_historique->execute([$_SESSION['utilisateur_id']]);
$conversations = $stmt_historique->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $utilisateur['mode_sombre'] ? 'sombre' : 'clair'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistant IA - ADH</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/assistant-ia.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>

        <main class="dashboard-main">
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>🤖 Assistant IA Pédagogique</h1>
                    <p>Votre compagnon d'apprentissage intelligent 24h/24</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-outline" id="new-chat-btn">➕ Nouvelle conversation</button>
                </div>
            </header>

            <div class="dashboard-content">
                <div class="assistant-container">
                    <!-- Sidebar des conversations -->
                    <div class="conversations-sidebar">
                        <div class="sidebar-header">
                            <h3>Conversations</h3>
                        </div>
                        
                        <div class="conversations-list">
                            <?php if (empty($conversations)): ?>
                                <p style="text-align: center; padding: 1rem; color: #666;">
                                    Aucune conversation
                                </p>
                            <?php else: ?>
                                <?php foreach ($conversations as $conv): ?>
                                    <div class="conversation-item" data-conversation-id="<?php echo $conv['id']; ?>">
                                        <div class="conversation-preview">
                                            <strong><?php echo $conv['titre'] ?: 'Nouvelle conversation'; ?></strong>
                                            <p><?php echo substr($conv['dernier_message'] ?: 'Aucun message', 0, 50) . '...'; ?></p>
                                        </div>
                                        <div class="conversation-date">
                                            <?php echo date('d/m', strtotime($conv['date_dernier_message'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Zone de chat principale -->
                    <div class="chat-main">
                        <div class="chat-header">
                            <div class="chat-title">
                                <h3>Assistant IA ADH</h3>
                                <span class="status-badge">🟢 En ligne</span>
                            </div>
                            <div class="chat-actions">
                                <button class="btn-icon" title="Effacer la conversation">🗑️</button>
                                <button class="btn-icon" title="Paramètres">⚙️</button>
                            </div>
                        </div>

                        <div class="chat-messages" id="chat-messages">
                            <!-- Messages chargés dynamiquement -->
                            <div class="message assistant">
                                <div class="message-avatar">🤖</div>
                                <div class="message-content">
                                    <div class="message-text">
                                        Bonjour <strong><?php echo $utilisateur['nom']; ?></strong> ! 👋<br><br>
                                        Je suis votre assistant IA pédagogique. Je peux vous aider à :
                                        <ul>
                                            <li>Répondre à vos questions sur les cours</li>
                                            <li>Vous recommander des parcours d'apprentissage</li>
                                            <li>Vous aider à résoudre des problèmes techniques</li>
                                            <li>Créer des plans de révision personnalisés</li>
                                            <li>Vous orienter dans votre carrière</li>
                                        </ul>
                                        Comment puis-je vous aider aujourd'hui ?
                                    </div>
                                    <div class="message-time"><?php echo date('H:i'); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Suggestions rapides -->
                        <div class="quick-suggestions">
                            <div class="suggestion" data-prompt="Explique-moi les bases de PHP">
                                💡 Expliquer PHP
                            </div>
                            <div class="suggestion" data-prompt="Propose-moi un plan d'étude pour le développement web">
                                📚 Plan d'étude dev web
                            </div>
                            <div class="suggestion" data-prompt="Aide-moi à déboguer mon code JavaScript">
                                🐛 Aide débogage
                            </div>
                            <div class="suggestion" data-prompt="Quelles compétences pour devenir data scientist ?">
                                🎯 Orientation carrière
                            </div>
                        </div>

                        <!-- Zone de saisie -->
                        <div class="chat-input-container">
                            <form id="chat-form" class="chat-form">
                                <div class="input-group">
                                    <textarea 
                                        id="message-input" 
                                        placeholder="Posez votre question à l'assistant IA..." 
                                        rows="1"
                                        maxlength="2000"
                                    ></textarea>
                                    <button type="submit" class="send-btn" id="send-btn">
                                        <span>📤</span>
                                    </button>
                                </div>
                                <div class="input-actions">
                                    <button type="button" class="btn-action" id="voice-btn" title="Dictée vocale">
                                        🎤
                                    </button>
                                    <button type="button" class="btn-action" id="attach-btn" title="Joindre un fichier">
                                        📎
                                    </button>
                                    <span class="char-count">0/2000</span>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Panneau des fonctionnalités -->
                    <div class="features-sidebar">
                        <div class="feature-section">
                            <h4>🎯 Parcours personnalisés</h4>
                            <p>Obtenez des recommandations basées sur vos objectifs</p>
                            <button class="btn-feature" data-feature="parcours">Créer un parcours</button>
                        </div>

                        <div class="feature-section">
                            <h4>📊 Analyse de compétences</h4>
                            <p>Évaluez vos forces et axes d'amélioration</p>
                            <button class="btn-feature" data-feature="analyse">Analyser</button>
                        </div>

                        <div class="feature-section">
                            <h4>💼 Orientation carrière</h4>
                            <p>Découvrez les métiers qui correspondent à votre profil</p>
                            <button class="btn-feature" data-feature="orientation">Explorer</button>
                        </div>

                        <div class="feature-section">
                            <h4>📝 Aide aux exercices</h4>
                            <p>Obtenez de l'aide sur vos devoirs et projets</p>
                            <button class="btn-feature" data-feature="exercices">Demander de l'aide</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal des paramètres -->
    <div id="settings-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Paramètres de l'Assistant IA</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="setting-group">
                    <label>Niveau de détail des réponses</label>
                    <select id="detail-level">
                        <option value="concise">Concis</option>
                        <option value="normal" selected>Normal</option>
                        <option value="detailled">Détaillé</option>
                    </select>
                </div>
                <div class="setting-group">
                    <label>Domaine d'expertise prioritaire</label>
                    <select id="expertise-domain">
                        <option value="general">Général</option>
                        <option value="web">Développement Web</option>
                        <option value="mobile">Développement Mobile</option>
                        <option value="data">Data Science</option>
                        <option value="design">Design UI/UX</option>
                    </select>
                </div>
                <div class="setting-group">
                    <label>
                        <input type="checkbox" id="auto-save" checked>
                        Sauvegarder automatiquement les conversations
                    </label>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script src="js/dashboard.js"></script>
    <script src="js/assistant-ia.js"></script>
</body>
</html>