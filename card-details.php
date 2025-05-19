<?php
// card-details.php
session_start();

// Inclure les fonctions nécessaires
require_once 'includes/functions.php';

// Vérifier que l'ID est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$cardId = (int)$_GET['id'];
$card = getCardById($cardId);

if (!$card) {
    header('Location: index.php');
    exit;
}

// Récupérer les conditions disponibles pour cette carte
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT * FROM card_conditions WHERE card_id = ? ORDER BY condition_code");
$stmt->execute([$cardId]);
$cardConditions = $stmt->fetchAll();

// Mise à jour de l'activité
$ip = $_SERVER['REMOTE_ADDR'];
$now = date('Y-m-d H:i:s');

// Supprimer les vues expirées (> 2 min)
$stmt = $conn->prepare("DELETE FROM page_views WHERE last_active < DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
$stmt->execute();

// Vérifier si l'IP est déjà enregistrée
$stmt = $conn->prepare("SELECT id FROM page_views WHERE ip_address = ? AND card_id = ?");
$stmt->execute([$ip, $cardId]);
$existing = $stmt->fetch();

if ($existing) {
    $stmt = $conn->prepare("UPDATE page_views SET last_active = ? WHERE id = ?");
    $stmt->execute([$now, $existing['id']]);
} else {
    $stmt = $conn->prepare("INSERT INTO page_views (card_id, ip_address, last_active) VALUES (?, ?, ?)");
    $stmt->execute([$cardId, $ip, $now]);
}

// Nombre de visiteurs actifs
$stmt = $conn->prepare("SELECT COUNT(*) FROM page_views WHERE card_id = ?");
$stmt->execute([$cardId]);
$activeUsers = (int)$stmt->fetchColumn();

// Titre de la page
$pageTitle = htmlspecialchars($card['name']);
require_once 'includes/header.php';
?>

<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="flex flex-col md:flex-row">
        <!-- Image -->
        <div class="md:w-1/2 mb-6 md:mb-0 md:pr-6">
            <div class="bg-gray-100 p-6 rounded-lg flex items-center justify-center card-image-zoom relative">
                <?php
                $createdAt = strtotime($card['created_at']);
                $twoWeeksAgo = strtotime('-14 days');
                if ($createdAt !== false && $createdAt > $twoWeeksAgo):
                ?>
                    <div class="absolute top-2 left-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full shadow">
                        Nouveau
                    </div>
                <?php endif; ?>
                <img src="<?php echo $card['image_url'] ?: 'assets/images/card-placeholder.png'; ?>"
                    alt="<?php echo htmlspecialchars($card['name']); ?>"
                    class="max-h-96 object-contain">
            </div>
        </div>

        <!-- Détails -->
        <div class="md:w-1/2">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($card['name']); ?></h1>
                </div>
            </div>

            <div class="mb-6">
                <p class="text-gray-600">Série: <strong><?php echo htmlspecialchars($card['series_name']); ?></strong></p>
                <p class="text-gray-600">Numéro: <strong><?php echo htmlspecialchars($card['card_number']); ?></strong></p>
                <p class="text-gray-600">Rareté: <strong><?php echo isset(CARD_RARITIES[$card['rarity']]) ? CARD_RARITIES[$card['rarity']] : htmlspecialchars($card['rarity']); ?></strong></p>
                <p class="text-gray-600">Variante: <strong><?php echo isset(CARD_VARIANTS[$card['variant']]) ? CARD_VARIANTS[$card['variant']] : htmlspecialchars($card['variant']); ?></strong></p>
                <?php if (!empty($card['description'])): ?>
                    <div class="mt-4">
                        <h3 class="font-semibold mb-2">Description:</h3>
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($card['description'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($cardConditions)): ?>
                <div class="mb-6">
                    <h3 class="font-semibold mb-4">États disponibles:</h3>
                    <div class="space-y-4">
                        <?php foreach ($cardConditions as $index => $condition): ?>
                            <div class="border border-gray-200 rounded-md p-4 hover:bg-gray-50 transition <?php echo $condition['quantity'] <= 0 ? 'opacity-50' : ''; ?>">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center space-x-3">
                                        <span class="condition-badge condition-<?php echo $condition['condition_code']; ?>">
                                            <?php echo CARD_CONDITIONS[$condition['condition_code']]; ?>
                                        </span>
                                        <span class="font-bold text-xl text-red-600"><?php echo formatPrice($condition['price']); ?></span>
                                    </div>
                                    <div>
                                        <?php if ($condition['quantity'] > 0): ?>
                                            <p class="text-green-600">En stock (<?php echo $condition['quantity']; ?> disponible<?php echo $condition['quantity'] > 1 ? 's' : ''; ?>)</p>
                                        <?php else: ?>
                                            <p class="text-red-600">Indisponible</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($condition['quantity'] > 0): ?>
                                    <div class="mt-3 flex items-center">
                                        <div class="quantity-selector mr-4">
                                            <button type="button" class="quantity-modifier" data-modifier="minus">-</button>
                                            <input type="number" min="1" max="<?php echo $condition['quantity']; ?>" value="1" class="quantity-input" data-condition="<?php echo $condition['condition_code']; ?>">
                                            <button type="button" class="quantity-modifier" data-modifier="plus">+</button>
                                        </div>

                                        <button
                                            data-card-id="<?php echo $card['id']; ?>"
                                            data-condition="<?php echo $condition['condition_code']; ?>"
                                            class="add-to-cart bg-gray-800 text-white py-3 px-6 rounded-md hover:bg-gray-900 transition flex-grow">
                                            <i class="fas fa-shopping-cart mr-2"></i> Ajouter au panier
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="mb-6">
                    <p class="text-red-600">Aucun état disponible pour cette carte.</p>
                </div>
            <?php endif; ?>

            <a href="javascript:history.back()" class="inline-block mt-4 text-gray-600 hover:text-red-600 transition">
                <i class="fas fa-arrow-left mr-1"></i> Retour
            </a>
        </div>
    </div>
</div>

<!-- Recommandations -->
<div class="mt-10">
    <h2 class="text-2xl font-bold mb-6">Autres cartes de la même série</h2>

    <?php
    $stmt = $conn->prepare("
        SELECT c.*, s.name as series_name 
        FROM cards c
        LEFT JOIN series s ON c.series_id = s.id
        WHERE c.series_id = ? AND c.id != ? 
        ORDER BY RAND() 
        LIMIT 3
    ");
    $stmt->execute([$card['series_id'], $card['id']]);
    $relatedCards = $stmt->fetchAll();

    // Pour chaque carte reliée, récupérer au moins une condition disponible
    foreach ($relatedCards as &$relatedCard) {
        $stmt = $conn->prepare("
            SELECT * FROM card_conditions 
            WHERE card_id = ? AND quantity > 0 
            ORDER BY price ASC 
            LIMIT 1
        ");
        $stmt->execute([$relatedCard['id']]);
        $bestCondition = $stmt->fetch();

        if ($bestCondition) {
            $relatedCard['best_condition'] = $bestCondition;
            $relatedCard['has_stock'] = true;
        } else {
            $stmt = $conn->prepare("
                SELECT * FROM card_conditions 
                WHERE card_id = ? 
                ORDER BY price ASC 
                LIMIT 1
            ");
            $stmt->execute([$relatedCard['id']]);
            $bestCondition = $stmt->fetch();

            if ($bestCondition) {
                $relatedCard['best_condition'] = $bestCondition;
                $relatedCard['has_stock'] = false;
            } else {
                $relatedCard['has_stock'] = false;
            }
        }
    }
    ?>

    <?php if (!empty($relatedCards)): ?>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <?php foreach ($relatedCards as $relatedCard): ?>
                <div class="card-item bg-white rounded-lg shadow-md overflow-hidden card-hover">
                    <div class="card-image-zoom p-4 bg-gray-100 relative">
                        <?php
                        $createdAt = strtotime($relatedCard['created_at']);
                        if ($createdAt !== false && $createdAt > strtotime('-14 days')):
                        ?>
                            <div class="absolute top-2 left-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full shadow">
                                Nouveau
                            </div>
                        <?php endif; ?>

                        <a href="card-details.php?id=<?php echo $relatedCard['id']; ?>">
                            <img src="<?php echo $relatedCard['image_url'] ?: 'assets/images/card-placeholder.png'; ?>"
                                alt="<?php echo htmlspecialchars($relatedCard['name']); ?>"
                                class="mx-auto h-44 object-contain">
                        </a>
                    </div>

                    <div class="p-4">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-bold text-lg truncate">
                                <a href="card-details.php?id=<?php echo $relatedCard['id']; ?>" class="hover:text-red-600 transition">
                                    <?php echo htmlspecialchars($relatedCard['name']); ?>
                                </a>
                            </h3>
                            <?php if (isset($relatedCard['best_condition'])): ?>
                                <span class="condition-badge condition-<?php echo $relatedCard['best_condition']['condition_code']; ?>">
                                    <?php echo CARD_CONDITIONS[$relatedCard['best_condition']['condition_code']]; ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="text-sm text-gray-500 mb-3">
                            <div>Rareté: <?php echo isset(CARD_RARITIES[$relatedCard['rarity']]) ? CARD_RARITIES[$relatedCard['rarity']] : htmlspecialchars($relatedCard['rarity']); ?></div>
                            <div>Variante: <?php echo isset(CARD_VARIANTS[$relatedCard['variant']]) ? CARD_VARIANTS[$relatedCard['variant']] : htmlspecialchars($relatedCard['variant']); ?></div>
                            <?php if (isset($relatedCard['best_condition'])): ?>
                                <div>À partir de: <span class="font-bold text-red-600"><?php echo formatPrice($relatedCard['best_condition']['price']); ?></span></div>
                            <?php endif; ?>
                        </div>

                        <div class="flex justify-between items-center">
                            <?php if (isset($relatedCard['best_condition'])): ?>
                                <div class="font-bold text-xl text-red-600"><?php echo formatPrice($relatedCard['best_condition']['price']); ?></div>
                            <?php else: ?>
                                <div class="font-bold text-xl text-red-600">Prix non disponible</div>
                            <?php endif; ?>

                            <?php if ($relatedCard['has_stock']): ?>
                                <button
                                    data-card-id="<?php echo $relatedCard['id']; ?>"
                                    data-condition="<?php echo $relatedCard['best_condition']['condition_code']; ?>"
                                    class="add-to-cart bg-gray-800 text-white py-2 px-4 rounded-md hover:bg-gray-900 transition">
                                    <i class="fas fa-shopping-cart mr-1"></i> Ajouter
                                </button>
                            <?php else: ?>
                                <span class="bg-gray-300 text-gray-600 py-2 px-4 rounded-md">
                                    Indisponible
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-gray-600">Aucune autre carte disponible dans cette série.</p>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion des boutons de quantité (+ / -)
        document.querySelectorAll('.quantity-modifier').forEach(button => {
            button.addEventListener('click', function() {
                const selector = this.closest('.quantity-selector');
                if (!selector) return;

                const input = selector.querySelector('input');
                if (!input) return;

                const currentValue = parseInt(input.value, 10) || 1;
                const increment = this.dataset.modifier === 'plus' ? 1 : -1;
                const maxValue = parseInt(input.getAttribute('max') || '999', 10);

                input.value = Math.min(maxValue, Math.max(1, currentValue + increment));
            });
        });

        // Gestion des boutons "Ajouter au panier"
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();

                const cardId = this.dataset.cardId;
                const condition = this.dataset.condition;

                // Récupérer la quantité si disponible
                let quantity = 1;
                const quantityInput = document.querySelector(`.quantity-input[data-condition="${condition}"]`);
                if (quantityInput) {
                    quantity = parseInt(quantityInput.value, 10) || 1;
                }

                // Appel AJAX pour ajouter au panier
                addToCart(cardId, condition, quantity);
            });
        });

        // Fonction pour ajouter au panier via AJAX
        function addToCart(cardId, condition, quantity) {
            const formData = new FormData();
            formData.append('card_id', cardId);
            formData.append('condition', condition);
            formData.append('quantity', quantity);

            fetch('add-to-cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mettre à jour le compteur du panier
                        updateCartCounter(data.cart_count);

                        // Afficher une notification de succès
                        showNotification(data.message, 'success');
                    } else {
                        // Afficher une notification d'erreur
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showNotification('Une erreur est survenue', 'error');
                });
        }

        // Fonction pour mettre à jour le compteur du panier
        function updateCartCounter(count) {
            const cartCounters = document.querySelectorAll('.cart-counter');
            cartCounters.forEach(counter => {
                if (count > 0) {
                    counter.textContent = count;
                    counter.classList.remove('hidden');
                } else {
                    counter.classList.add('hidden');
                }
            });
        }

        // Fonction pour afficher des notifications
        window.showNotification = function(message, type) {
            const existing = document.querySelector('.notification');
            if (existing) existing.remove();

            const notification = document.createElement('div');
            notification.className = `notification fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            } text-white`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${
                        type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'
                    } mr-2"></i>
                    <span>${message}</span>
                </div>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.classList.add('opacity-0');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        };
    });
</script>

<?php
require_once 'includes/footer.php';
?>