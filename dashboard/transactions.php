<?php
// Tableau de bord des transactions - Admin
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Database.php';

// Vérifier les permissions
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['utilisateur_role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

$pdo = getPDO();
$db = new PaymentDatabase($pdo);

// Récupérer les filtres
$filters = [];
if (!empty($_GET['user_id'])) $filters['user_id'] = intval($_GET['user_id']);
if (!empty($_GET['teacher_id'])) $filters['teacher_id'] = intval($_GET['teacher_id']);
if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
if (!empty($_GET['start_date'])) $filters['start_date'] = $_GET['start_date'];
if (!empty($_GET['end_date'])) $filters['end_date'] = $_GET['end_date'];
if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Récupérer les transactions
$transactions = $db->getTransactions($filters, $limit, $offset);
$total_count = count($db->getTransactions($filters, 1000, 0)); // Estimation
$total_pages = ceil($total_count / $limit);

// Statistiques
$stats = $db->getPaymentStats();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Transactions</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .transaction-table th, .transaction-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .transaction-table th {
            background-color: var(--bg-tertiary);
            font-weight: 600;
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-completed { background-color: #d1f7c4; color: #0e6245; }
        .status-pending { background-color: #fff6b3; color: #7d6608; }
        .status-failed { background-color: #fad4d4; color: #c53030; }
        .status-refunded { background-color: #e2e8f0; color: #4a5568; }
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--bg-secondary);
            border-radius: 8px;
        }
        .filter-form input, .filter-form select {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            width: 100%;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        .pagination {
            display: flex;
            gap: 0.5rem;
            margin-top: 2rem;
            justify-content: center;
        }
        .pagination a, .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            text-decoration: none;
        }
        .pagination a:hover {
            background-color: var(--primary-color);
            color: white;
        }
        .pagination .current {
            background-color: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="dashboard-container">
        <div class="sidebar"><?php include '../includes/admin-sidebar.php'; ?></div>
        
        <div class="main-content">
            <header class="header">
                <h1>Gestion des Transactions</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="exportTransactions()">
                        <i class="fas fa-download"></i> Exporter CSV
                    </button>
                </div>
            </header>
            
            <main class="content">
                <!-- Statistiques -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Revenu Total</h3>
                        <div class="stat-value"><?php echo number_format($stats['total_revenue'] ?? 0, 2); ?> €</div>
                        <p>Transactions: <?php echo $stats['total_transactions'] ?? 0; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Commission Plateforme</h3>
                        <div class="stat-value"><?php echo number_format($stats['total_commission'] ?? 0, 2); ?> €</div>
                        <p>20% sur chaque transaction</p>
                    </div>
                    <div class="stat-card">
                        <h3>Paiements Professeurs</h3>
                        <div class="stat-value"><?php echo number_format($stats['total_teacher_payments'] ?? 0, 2); ?> €</div>
                        <p>Payeurs uniques: <?php echo $stats['unique_payers'] ?? 0; ?></p>
                    </div>
                </div>
                
                <!-- Filtres -->
                <form method="GET" class="filter-form">
                    <div>
                        <label>ID Utilisateur</label>
                        <input type="number" name="user_id" value="<?php echo $_GET['user_id'] ?? ''; ?>">
                    </div>
                    <div>
                        <label>ID Professeur</label>
                        <input type="number" name="teacher_id" value="<?php echo $_GET['teacher_id'] ?? ''; ?>">
                    </div>
                    <div>
                        <label>Statut</label>
                        <select name="status">
                            <option value="">Tous</option>
                            <option value="completed" <?php echo ($_GET['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Complété</option>
                            <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>En attente</option>
                            <option value="failed" <?php echo ($_GET['status'] ?? '') === 'failed' ? 'selected' : ''; ?>>Échoué</option>
                            <option value="refunded" <?php echo ($_GET['status'] ?? '') === 'refunded' ? 'selected' : ''; ?>>Remboursé</option>
                        </select>
                    </div>
                    <div>
                        <label>Date de début</label>
                        <input type="date" name="start_date" value="<?php echo $_GET['start_date'] ?? ''; ?>">
                    </div>
                    <div>
                        <label>Date de fin</label>
                        <input type="date" name="end_date" value="<?php echo $_GET['end_date'] ?? ''; ?>">
                    </div>
                    <div>
                        <label>Recherche</label>
                        <input type="text" name="search" placeholder="ID transaction, nom, email..." 
                               value="<?php echo $_GET['search'] ?? ''; ?>">
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrer
                        </button>
                        <a href="transactions.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Réinitialiser
                        </a>
                    </div>
                </form>
                
                <!-- Tableau des transactions -->
                <div class="card">
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th>ID Transaction</th>
                                <th>Utilisateur</th>
                                <th>Montant</th>
                                <th>Méthode</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <code><?php echo htmlspecialchars(substr($transaction['transaction_id'], 0, 12) . '...'); ?></code>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($transaction['user_name'] ?? 'N/A'); ?></div>
                                    <small><?php echo htmlspecialchars($transaction['user_email'] ?? ''); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo number_format($transaction['amount'], 2); ?> <?php echo $transaction['currency']; ?></strong><br>
                                    <small>Prof: <?php echo number_format($transaction['teacher_amount'], 2); ?> €</small>
                                </td>
                                <td>
                                    <span class="status-badge">
                                        <?php echo ucfirst($transaction['payment_method']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $status_class = 'status-' . $transaction['status'];
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" 
                                            onclick="viewTransaction('<?php echo $transaction['transaction_id']; ?>')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($transaction['status'] === 'completed'): ?>
                                    <button class="btn btn-sm btn-warning" 
                                            onclick="showRefundModal('<?php echo $transaction['transaction_id']; ?>', <?php echo $transaction['amount']; ?>)">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal de remboursement -->
    <div id="refundModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%;">
            <h3>Effectuer un remboursement</h3>
            <form id="refundForm" onsubmit="processRefund(event)">
                <input type="hidden" id="refundTransactionId">
                <div style="margin-bottom: 1rem;">
                    <label>Montant à rembourser</label>
                    <input type="number" id="refundAmount" step="0.01" min="0" required 
                           style="width: 100%; padding: 0.75rem; margin-top: 0.5rem;">
                    <small>Montant maximum: <span id="maxAmount"></span> €</small>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label>Raison</label>
                    <select id="refundReason" style="width: 100%; padding: 0.75rem; margin-top: 0.5rem;">
                        <option value="customer_request">Demande du client</option>
                        <option value="duplicate_charge">Double facturation</option>
                        <option value="fraudulent">Transaction frauduleuse</option>
                        <option value="product_unsatisfactory">Produit non satisfaisant</option>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">Confirmer</button>
                    <button type="button" class="btn btn-secondary" onclick="closeRefundModal()">Annuler</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function exportTransactions() {
            // Construire l'URL avec les filtres actuels
            const params = new URLSearchParams(window.location.search);
            window.location.href = 'api/export-transactions.php?' + params.toString();
        }
        
        function viewTransaction(transactionId) {
            window.location.href = 'transaction-details.php?id=' + transactionId;
        }
        
        function showRefundModal(transactionId, maxAmount) {
            document.getElementById('refundTransactionId').value = transactionId;
            document.getElementById('refundAmount').value = maxAmount;
            document.getElementById('refundAmount').max = maxAmount;
            document.getElementById('maxAmount').textContent = maxAmount;
            document.getElementById('refundModal').style.display = 'flex';
        }
        
        function closeRefundModal() {
            document.getElementById('refundModal').style.display = 'none';
            document.getElementById('refundForm').reset();
        }
        
        async function processRefund(event) {
            event.preventDefault();
            
            const transactionId = document.getElementById('refundTransactionId').value;
            const amount = document.getElementById('refundAmount').value;
            const reason = document.getElementById('refundReason').value;
            
            try {
                const response = await fetch('../api/refund-payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        transaction_id: transactionId,
                        amount: amount,
                        reason: reason
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Remboursement effectué avec succès');
                    closeRefundModal();
                    location.reload();
                } else {
                    alert('Erreur: ' + result.error);
                }
            } catch (error) {
                alert('Erreur réseau: ' + error.message);
            }
        }
    </script>
</body>
</html>