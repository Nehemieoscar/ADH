<?php
// Interface étudiant pour voir les paiements
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Database.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: /login.php');
    exit;
}

$user_id = $_SESSION['utilisateur_id'];
$pdo = getPDO();
$db = new PaymentDatabase($pdo);

// Récupérer les transactions de l'utilisateur
$transactions = $db->getTransactions(['user_id' => $user_id], 50, 0);

// Récupérer les cours payés et non payés
$paid_courses = [];
$unpaid_courses = [];

$sql = "SELECT c.id, c.titre, c.prix, 
               CASE WHEN t.id IS NOT NULL THEN TRUE ELSE FALSE END as paid
        FROM cours c
        LEFT JOIN (
            SELECT DISTINCT course_id 
            FROM transactions 
            WHERE user_id = ? AND status = 'completed'
        ) t ON c.id = t.course_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($courses as $course) {
    if ($course['paid']) {
        $paid_courses[] = $course;
    } else {
        $unpaid_courses[] = $course;
    }
}
?>

<div class="section" id="section-payments" style="display: none;">
    <h2>Mes Paiements et Factures</h2>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
        <!-- Cours payés -->
        <div class="card">
            <h3><i class="fas fa-check-circle" style="color: var(--success-color);"></i> Cours Payés</h3>
            <?php if (empty($paid_courses)): ?>
                <p style="color: var(--text-secondary); text-align: center; padding: 2rem;">
                    Aucun cours payé pour le moment.
                </p>
            <?php else: ?>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <?php foreach ($paid_courses as $course): ?>
                    <li style="padding: 1rem; border-bottom: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong><?php echo htmlspecialchars($course['titre']); ?></strong><br>
                                <small>Prix: <?php echo number_format($course['prix'], 2); ?> €</small>
                            </div>
                            <span class="status-badge status-completed">Payé</span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <!-- Cours non payés -->
        <div class="card">
            <h3><i class="fas fa-clock" style="color: var(--warning-color);"></i> Cours Non Payés</h3>
            <?php if (empty($unpaid_courses)): ?>
                <p style="color: var(--text-secondary); text-align: center; padding: 2rem;">
                    Tous vos cours sont payés.
                </p>
            <?php else: ?>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <?php foreach ($unpaid_courses as $course): ?>
                    <li style="padding: 1rem; border-bottom: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong><?php echo htmlspecialchars($course['titre']); ?></strong><br>
                                <small>Prix: <?php echo number_format($course['prix'], 2); ?> €</small>
                            </div>
                            <button class="btn btn-sm btn-primary" 
                                    onclick="showPaymentModal(<?php echo $course['id']; ?>, <?php echo $course['prix']; ?>)">
                                Payer maintenant
                            </button>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Historique des transactions -->
    <div class="card">
        <h3>Historique des Transactions</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: var(--bg-tertiary);">
                    <th style="padding: 1rem;">ID Transaction</th>
                    <th style="padding: 1rem;">Cours</th>
                    <th style="padding: 1rem;">Montant</th>
                    <th style="padding: 1rem;">Méthode</th>
                    <th style="padding: 1rem;">Statut</th>
                    <th style="padding: 1rem;">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 1rem;">
                        <code><?php echo substr($transaction['transaction_id'], 0, 10); ?>...</code>
                    </td>
                    <td style="padding: 1rem;">
                        <?php echo htmlspecialchars($transaction['course_title'] ?? 'N/A'); ?>
                    </td>
                    <td style="padding: 1rem;">
                        <?php echo number_format($transaction['amount'], 2); ?> <?php echo $transaction['currency']; ?>
                    </td>
                    <td style="padding: 1rem;">
                        <?php echo ucfirst($transaction['payment_method']); ?>
                    </td>
                    <td style="padding: 1rem;">
                        <?php 
                        $status_class = 'status-' . $transaction['status'];
                        ?>
                        <span class="status-badge <?php echo $status_class; ?>">
                            <?php echo ucfirst($transaction['status']); ?>
                        </span>
                    </td>
                    <td style="padding: 1rem;">
                        <?php echo date('d/m/Y', strtotime($transaction['created_at'])); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de paiement -->
<div id="paymentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 500px; width: 90%;">
        <h3>Paiement du cours</h3>
        <div id="paymentModalContent">
            <!-- Le contenu sera chargé dynamiquement -->
        </div>
    </div>
</div>

<script>
// Fonction pour afficher le modal de paiement
function showPaymentModal(courseId, amount) {
    const modal = document.getElementById('paymentModal');
    const content = document.getElementById('paymentModalContent');
    
    content.innerHTML = `
        <p><strong>Montant à payer:</strong> ${amount.toFixed(2)} €</p>
        
        <div style="margin: 1.5rem 0;">
            <label><input type="radio" name="paymentMethod" value="stripe" checked> 
                Carte de crédit (Stripe)</label><br>
            <label><input type="radio" name="paymentMethod" value="moncash"> 
                MonCash (Haïti)</label>
        </div>
        
        <div id="stripePayment" style="margin-top: 1rem;">
            <div id="card-element"></div>
            <div id="card-errors" role="alert" style="color: #c53030; margin-top: 0.5rem;"></div>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button class="btn btn-primary" onclick="processPayment(${courseId}, ${amount})">
                Payer maintenant
            </button>
            <button class="btn btn-secondary" onclick="closePaymentModal()">
                Annuler
            </button>
        </div>
    `;
    
    modal.style.display = 'flex';
    
    // Initialiser Stripe Elements si Stripe est sélectionné
    initializeStripe();
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
}

// Gestionnaire de paiement
async function processPayment(courseId, amount) {
    const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
    
    try {
        const response = await fetch('../api/create-payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                course_id: courseId,
                amount: amount,
                payment_method: paymentMethod
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (paymentMethod === 'stripe') {
                // Confirmer le paiement Stripe
                await confirmStripePayment(result.client_secret);
            } else if (paymentMethod === 'moncash') {
                // Rediriger vers MonCash
                window.location.href = result.redirect_url;
            }
        } else {
            alert('Erreur: ' + result.error);
        }
    } catch (error) {
        alert('Erreur réseau: ' + error.message);
    }
}

// Initialiser Stripe Elements
let stripe, elements, cardElement;

async function initializeStripe() {
    // Charger Stripe.js dynamiquement
    if (!document.querySelector('#stripe-js')) {
        const script = document.createElement('script');
        script.id = 'stripe-js';
        script.src = 'https://js.stripe.com/v3/';
        document.head.appendChild(script);
        
        await new Promise(resolve => {
            script.onload = resolve;
        });
    }
    
    stripe = Stripe('<?php echo StripeConfig::getPublishableKey(); ?>');
    elements = stripe.elements();
    
    const style = {
        base: {
            color: '#32325d',
            fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
            fontSmoothing: 'antialiased',
            fontSize: '16px',
            '::placeholder': {
                color: '#aab7c4'
            }
        },
        invalid: {
            color: '#fa755a',
            iconColor: '#fa755a'
        }
    };
    
    cardElement = elements.create('card', {style: style});
    cardElement.mount('#card-element');
    
    // Gestion des erreurs
    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });
}

// Confirmer le paiement Stripe
async function confirmStripePayment(clientSecret) {
    try {
        const {error, paymentIntent} = await stripe.confirmCardPayment(clientSecret, {
            payment_method: {
                card: cardElement,
                billing_details: {
                    name: '<?php echo $_SESSION["utilisateur_nom"] ?? ""; ?>'
                }
            }
        });
        
        if (error) {
            alert('Erreur de paiement: ' + error.message);
        } else if (paymentIntent.status === 'succeeded') {
            alert('Paiement effectué avec succès!');
            closePaymentModal();
            location.reload();
        }
    } catch (error) {
        alert('Erreur: ' + error.message);
    }
}
</script>