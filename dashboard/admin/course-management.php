<?php
// ========================================
// Example: Course Management Page
// Shows how to integrate module content system
// ========================================

require_once('../../config.php');
securite_admin(); // Restrict to admins only

$courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 1;

// Fetch course and modules
try {
    $stmt = $pdo->prepare("SELECT id, titre FROM formations WHERE id = ?");
    $stmt->execute([$courseId]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        die('Cours non trouvé');
    }

    // Fetch modules with content
    $stmt = $pdo->prepare("
        SELECT m.id, m.titre, m.ordre
        FROM modules m
        WHERE m.formation_id = ?
        ORDER BY m.ordre ASC
    ");
    $stmt->execute([$courseId]);
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch lessons, assignments, and quizzes for each module
    foreach ($modules as &$module) {
        // Lessons
        $stmt = $pdo->prepare("SELECT id, titre FROM lecons WHERE module_id = ? ORDER BY ordre");
        $stmt->execute([$module['id']]);
        $module['lessons'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Assignments
        $stmt = $pdo->prepare("SELECT id, titre, date_limite FROM devoirs WHERE module_id = ?");
        $stmt->execute([$module['id']]);
        $module['assignments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Quizzes
        $stmt = $pdo->prepare("SELECT id, titre, points_max FROM quiz WHERE module_id = ?");
        $stmt->execute([$module['id']]);
        $module['quizzes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    die('Erreur: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['titre']); ?> - Gestion de Contenu</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background: #f5f5f5; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        
        h1 { color: #333; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #007bff; }
        
        .header { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        
        #modules-list { margin-bottom: 2rem; }
        
        .module-item {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            cursor: grab;
            transition: all 0.3s ease;
        }
        
        .module-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-color: #007bff;
        }
        
        .module-item:active {
            cursor: grabbing;
        }
        
        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .module-header h2 {
            color: #333;
            font-size: 1.3rem;
            margin: 0;
        }
        
        .module-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
            text-transform: uppercase;
        }
        
        .btn-lesson {
            background: #007bff;
            color: white;
        }
        .btn-lesson:hover { background: #0056b3; }
        
        .btn-assignment {
            background: #28a745;
            color: white;
        }
        .btn-assignment:hover { background: #218838; }
        
        .btn-quiz {
            background: #dc3545;
            color: white;
        }
        .btn-quiz:hover { background: #c82333; }
        
        .content-list {
            background: #f9f9f9;
            border-radius: 6px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .content-item {
            background: white;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            border-radius: 4px;
            border-left: 3px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .content-item.lesson { border-left-color: #007bff; }
        .content-item.assignment { border-left-color: #28a745; }
        .content-item.quiz { border-left-color: #dc3545; }
        
        .content-item-info {
            flex: 1;
        }
        
        .content-item-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }
        
        .content-item-meta {
            font-size: 0.8rem;
            color: #999;
        }
        
        .content-item-actions {
            display: flex;
            gap: 0.5rem;
            margin-left: 1rem;
        }
        
        .btn-small {
            padding: 0.4rem 0.8rem;
            font-size: 0.75rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background: #e9ecef;
            color: #333;
            transition: all 0.2s;
        }
        
        .btn-small:hover {
            background: #dee2e6;
        }
        
        .btn-delete {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn-delete:hover {
            background: #f5c6cb;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #999;
            font-size: 0.9rem;
        }
        
        .help-box {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 2rem;
            color: #0056b3;
        }
        
        .drag-handle {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            margin-right: 0.5rem;
            background: #e9ecef;
            border-radius: 4px;
            cursor: grab;
            color: #666;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 <?php echo htmlspecialchars($course['titre']); ?></h1>
            <p style="color: #666;">Gérez le contenu de votre cours: leçons, devoirs et quiz</p>
        </div>

        <div class="help-box">
            💡 <strong>Conseil:</strong> Vous pouvez glisser-déposer les modules pour les réorganiser. L'ordre sera sauvegardé automatiquement.
        </div>

        <div id="modules-list">
            <?php if (empty($modules)): ?>
                <div class="module-item" style="text-align: center; padding: 2rem; color: #999;">
                    Aucun module trouvé. Créez un module avant d'ajouter du contenu.
                </div>
            <?php else: ?>
                <?php foreach ($modules as $module): ?>
                    <div class="module-item" data-module-id="<?php echo $module['id']; ?>">
                        <div class="module-header">
                            <h2>
                                <span class="drag-handle">⋮⋮</span>
                                <?php echo htmlspecialchars($module['titre']); ?>
                            </h2>
                            <div class="module-actions">
                                <button class="btn btn-lesson btn-add-lesson" data-module-id="<?php echo $module['id']; ?>">
                                    📝 Leçon
                                </button>
                                <button class="btn btn-assignment btn-add-assignment" data-module-id="<?php echo $module['id']; ?>">
                                    📋 Devoir
                                </button>
                                <button class="btn btn-quiz btn-add-quiz" data-module-id="<?php echo $module['id']; ?>">
                                    ✓ Quiz
                                </button>
                            </div>
                        </div>

                        <!-- Content Lists -->
                        <div class="content-list">
                            <!-- Lessons -->
                            <?php if (!empty($module['lessons'])): ?>
                                <div style="margin-bottom: 1rem;">
                                    <h3 style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem; text-transform: uppercase;">Leçons (<?php echo count($module['lessons']); ?>)</h3>
                                    <?php foreach ($module['lessons'] as $lesson): ?>
                                        <div class="content-item lesson">
                                            <div class="content-item-info">
                                                <div class="content-item-title">
                                                    📖 <?php echo htmlspecialchars($lesson['titre']); ?>
                                                </div>
                                            </div>
                                            <div class="content-item-actions">
                                                <button class="btn-small">Éditer</button>
                                                <button class="btn-small btn-delete">Supprimer</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Assignments -->
                            <?php if (!empty($module['assignments'])): ?>
                                <div style="margin-bottom: 1rem;">
                                    <h3 style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem; text-transform: uppercase;">Devoirs (<?php echo count($module['assignments']); ?>)</h3>
                                    <?php foreach ($module['assignments'] as $assignment): ?>
                                        <div class="content-item assignment">
                                            <div class="content-item-info">
                                                <div class="content-item-title">
                                                    ✏️ <?php echo htmlspecialchars($assignment['titre']); ?>
                                                </div>
                                                <div class="content-item-meta">
                                                    Limite: <?php echo date('d/m/Y H:i', strtotime($assignment['date_limite'])); ?>
                                                </div>
                                            </div>
                                            <div class="content-item-actions">
                                                <button class="btn-small">Éditer</button>
                                                <button class="btn-small btn-delete">Supprimer</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Quizzes -->
                            <?php if (!empty($module['quizzes'])): ?>
                                <div>
                                    <h3 style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem; text-transform: uppercase;">Quiz (<?php echo count($module['quizzes']); ?>)</h3>
                                    <?php foreach ($module['quizzes'] as $quiz): ?>
                                        <div class="content-item quiz">
                                            <div class="content-item-info">
                                                <div class="content-item-title">
                                                    ❓ <?php echo htmlspecialchars($quiz['titre']); ?>
                                                </div>
                                                <div class="content-item-meta">
                                                    Points: <?php echo $quiz['points_max']; ?>
                                                </div>
                                            </div>
                                            <div class="content-item-actions">
                                                <button class="btn-small">Résultats</button>
                                                <button class="btn-small">Éditer</button>
                                                <button class="btn-small btn-delete">Supprimer</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Empty state -->
                            <?php if (empty($module['lessons']) && empty($module['assignments']) && empty($module['quizzes'])): ?>
                                <div class="empty-state">
                                    👆 Cliquez sur un bouton ci-dessus pour ajouter du contenu
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Include the module content management script -->
    <script src="/ADH/dashboard/js/module-content.js"></script>
</body>
</html>
