<?php
// ========================================
// TEST PAGE - Vérifier l'intégration
// ========================================

require_once('../config.php');
$isAdmin = est_connecte() && est_admin();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Système de Contenu</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background: #f5f5f5; padding: 2rem; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #333; margin-bottom: 1.5rem; }
        .test-section { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .test-item { padding: 1rem; margin-bottom: 1rem; border-left: 4px solid #ddd; }
        .test-item.pass { border-left-color: #28a745; background: #f0f8f5; }
        .test-item.fail { border-left-color: #dc3545; background: #fdf8f8; }
        .test-item.warning { border-left-color: #ffc107; background: #fffbf0; }
        .status { font-weight: 600; margin-bottom: 0.5rem; }
        .status-pass { color: #28a745; }
        .status-fail { color: #dc3545; }
        .status-warning { color: #ffc107; }
        .code { background: #f4f4f4; padding: 0.75rem; border-radius: 4px; font-family: monospace; font-size: 0.85rem; overflow-x: auto; }
        button { padding: 0.75rem 1.5rem; background: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer; margin-right: 0.5rem; }
        button:hover { background: #0056b3; }
        .test-buttons { margin-bottom: 1.5rem; }
        .result { padding: 1rem; border-radius: 6px; margin-top: 1rem; display: none; }
        .result.success { display: block; background: #d4edda; color: #155724; }
        .result.error { display: block; background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Tests d'Intégration - Système de Contenu</h1>

        <div class="test-section">
            <h2>1. Vérification de l'Environnement</h2>
            
            <?php
            // Test 1: PHP Version
            $phpVersion = phpversion();
            $phpOk = version_compare($phpVersion, '7.4.0', '>=');
            echo $phpOk ? 
                '<div class="test-item pass"><div class="status status-pass">✓ PHP ' . $phpVersion . '</div></div>' :
                '<div class="test-item fail"><div class="status status-fail">✗ PHP ' . $phpVersion . ' (minimum: 7.4)</div></div>';

            // Test 2: PDO MySQL
            try {
                $stmt = $pdo->prepare("SELECT 1");
                $stmt->execute();
                echo '<div class="test-item pass"><div class="status status-pass">✓ Connexion PDO MySQL OK</div></div>';
            } catch (Exception $e) {
                echo '<div class="test-item fail"><div class="status status-fail">✗ Connexion PDO échouée: ' . $e->getMessage() . '</div></div>';
            }

            // Test 3: Uploads Directory
            $uploadsDir = __DIR__ . '/../../uploads/assignments';
            if (is_dir($uploadsDir) && is_writable($uploadsDir)) {
                echo '<div class="test-item pass"><div class="status status-pass">✓ Dossier uploads/assignments/ existe et est accessible</div></div>';
            } else {
                if (!is_dir($uploadsDir)) {
                    echo '<div class="test-item warning"><div class="status status-warning">⚠ Dossier uploads/assignments/ n\'existe pas</div><div>Créez-le avec: mkdir -p ' . $uploadsDir . '</div></div>';
                } else {
                    echo '<div class="test-item warning"><div class="status status-warning">⚠ Dossier uploads/assignments/ existe mais n\'est pas accessible en écriture</div></div>';
                }
            }

            // Test 4: Database Tables
            $tables = ['lecons', 'devoirs', 'quiz', 'quiz_questions', 'quiz_reponses_options', 'quiz_soumissions'];
            foreach ($tables as $table) {
                try {
                    $stmt = $pdo->prepare("SELECT 1 FROM $table LIMIT 1");
                    $stmt->execute();
                    echo '<div class="test-item pass"><div class="status status-pass">✓ Table `' . $table . '` existe</div></div>';
                } catch (Exception $e) {
                    echo '<div class="test-item fail"><div class="status status-fail">✗ Table `' . $table . '` n\'existe pas - Exécutez SQL_SCHEMA.sql</div></div>';
                }
            }

            // Test 5: Authentication
            if (est_connecte()) {
                $role = $_SESSION['utilisateur_role'] ?? 'unknown';
                echo '<div class="test-item pass"><div class="status status-pass">✓ Utilisateur connecté (rôle: ' . $role . ')</div></div>';
            } else {
                echo '<div class="test-item warning"><div class="status status-warning">⚠ Vous n\'êtes pas connecté - Certains tests nécessitent l\'authentification</div></div>';
            }
            ?>
        </div>

        <?php if ($isAdmin): ?>
        <div class="test-section">
            <h2>2. Tests des APIs (Admin Only)</h2>
            <p style="margin-bottom: 1rem; color: #666;">Cliquez sur les boutons pour tester chaque endpoint</p>

            <div class="test-buttons">
                <button onclick="testApi('add_lesson.php', {module_id: 1, titre: 'Leçon Test', contenu: 'Ceci est un test'})">Test: Add Lesson</button>
                <button onclick="testApi('add_assignment.php', {module_id: 1, titre: 'Devoir Test'})">Test: Add Assignment</button>
                <button onclick="testApi('get_quiz_results.php', {course_id: 1})">Test: Get Quiz Results</button>
                <button onclick="testApi('update_module_order.php', {modules: [1, 2, 3]})">Test: Update Module Order</button>
            </div>

            <div id="test-result" class="result"></div>

            <script>
                async function testApi(endpoint, data) {
                    const resultDiv = document.getElementById('test-result');
                    resultDiv.textContent = 'Envoi en cours...';
                    resultDiv.className = 'result';

                    try {
                        const response = await fetch(`/ADH/dashboard/api/${endpoint}`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(data)
                        });

                        const result = await response.json();
                        resultDiv.className = result.success ? 'result success' : 'result error';
                        resultDiv.innerHTML = `
                            <strong>${endpoint}</strong><br>
                            Status: ${response.status}<br>
                            <pre style="margin-top: 1rem; overflow-x: auto;">${JSON.stringify(result, null, 2)}</pre>
                        `;
                    } catch (error) {
                        resultDiv.className = 'result error';
                        resultDiv.innerHTML = `
                            <strong>Erreur lors du test de ${endpoint}</strong><br>
                            ${error.message}
                        `;
                    }
                }
            </script>
        </div>

        <div class="test-section">
            <h2>3. Test d'Intégration Frontend</h2>
            <p style="margin-bottom: 1rem; color: #666;">Testez les modales et le drag-and-drop</p>

            <div id="modules-list" style="background: #f9f9f9; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                <h3 style="margin-bottom: 1rem;">Modules (Drag & Drop)</h3>
                <div class="module-item" data-module-id="1" style="background: white; padding: 1rem; margin-bottom: 0.75rem; border: 1px dashed #ccc; cursor: move; border-radius: 4px;">
                    Module 1
                    <div style="margin-top: 0.75rem;">
                        <button class="btn-add-lesson" data-module-id="1" style="font-size: 0.85rem; padding: 0.5rem 1rem;">+ Leçon</button>
                        <button class="btn-add-assignment" data-module-id="1" style="font-size: 0.85rem; padding: 0.5rem 1rem; background: #28a745;">+ Devoir</button>
                        <button class="btn-add-quiz" data-module-id="1" style="font-size: 0.85rem; padding: 0.5rem 1rem; background: #dc3545;">+ Quiz</button>
                    </div>
                </div>
                <div class="module-item" data-module-id="2" style="background: white; padding: 1rem; margin-bottom: 0.75rem; border: 1px dashed #ccc; cursor: move; border-radius: 4px;">
                    Module 2
                    <div style="margin-top: 0.75rem;">
                        <button class="btn-add-lesson" data-module-id="2" style="font-size: 0.85rem; padding: 0.5rem 1rem;">+ Leçon</button>
                        <button class="btn-add-assignment" data-module-id="2" style="font-size: 0.85rem; padding: 0.5rem 1rem; background: #28a745;">+ Devoir</button>
                        <button class="btn-add-quiz" data-module-id="2" style="font-size: 0.85rem; padding: 0.5rem 1rem; background: #dc3545;">+ Quiz</button>
                    </div>
                </div>
            </div>
            <p style="color: #666; font-size: 0.9rem;">💡 Essayez de glisser-déposer les modules (le drag-drop nécessite les fichiers JS)</p>
        </div>

        <?php endif; ?>

        <div class="test-section">
            <h2>4. Checklist d'Installation</h2>
            <div style="background: #f9f9f9; padding: 1rem; border-radius: 6px;">
                <p style="margin-bottom: 1rem;"><input type="checkbox"> Exécuter SQL_SCHEMA.sql pour créer les tables</p>
                <p style="margin-bottom: 1rem;"><input type="checkbox"> Créer le dossier uploads/assignments/ avec permissions d'écriture</p>
                <p style="margin-bottom: 1rem;"><input type="checkbox"> Inclure dashboard/js/module-content.js dans les pages d'administration</p>
                <p style="margin-bottom: 1rem;"><input type="checkbox"> Inclure dashboard/js/quiz-interface.js dans les pages de quiz étudiants</p>
                <p style="margin-bottom: 1rem;"><input type="checkbox"> Vérifier que les boutons utilisent les classes CSS correctes (btn-add-lesson, btn-add-assignment, btn-add-quiz)</p>
                <p style="margin-bottom: 1rem;"><input type="checkbox"> Vérifier que modules-list a l'ID correct et les attributs data-module-id</p>
                <p style="margin-bottom: 1rem;"><input type="checkbox"> Tester au moins un quiz complet (création → soumission → résultats)</p>
            </div>
        </div>

        <div class="test-section" style="background: #e7f3ff; border-left: 4px solid #007bff;">
            <h2 style="color: #0056b3;">📚 Ressources</h2>
            <ul style="margin-left: 2rem; color: #333;">
                <li><strong>Guide complet:</strong> <code>INTEGRATION_GUIDE.md</code></li>
                <li><strong>Schéma DB:</strong> <code>SQL_SCHEMA.sql</code></li>
                <li><strong>Module Management:</strong> <code>dashboard/js/module-content.js</code></li>
                <li><strong>Quiz Interface:</strong> <code>dashboard/js/quiz-interface.js</code></li>
                <li><strong>Admin Results:</strong> <code>dashboard/admin/quiz_results.php</code></li>
            </ul>
        </div>
    </div>

    <script src="/ADH/dashboard/js/module-content.js"></script>
</body>
</html>
