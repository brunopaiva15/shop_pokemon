<?php
// cart.php
session_start();

// Définir le titre de la page
$pageTitle = 'Votre panier';

// Inclure l'en-tête
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Récupérer les articles du panier
$cartItems = getCartItems();
$cartTotal = getCartTotal();

// Calcul de la remise automatique
$remiseCHF = floor($cartTotal / 10);
$totalApresRemise = $cartTotal - $remiseCHF;

// Traitement des actions sur le panier (pour les requêtes non-AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'update') {
            // Mise à jour des quantités
            if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
                foreach ($_POST['quantities'] as $itemId => $quantity) {
                    updateCartItemQuantity($itemId, (int)$quantity);
                }
                // Rediriger pour éviter la re-soumission du formulaire
                header('Location: cart.php?updated=1');
                exit;
            }
        } elseif ($action === 'remove' && isset($_POST['item_id'])) {
            // Suppression d'un article
            removeCartItem($_POST['item_id']);
            // Rediriger pour éviter la re-soumission du formulaire
            header('Location: cart.php?removed=1');
            exit;
        } elseif ($action === 'clear') {
            // Vider le panier
            clearCart();
            // Rediriger pour éviter la re-soumission du formulaire
            header('Location: cart.php?cleared=1');
            exit;
        }
    }
}

// Notification après action
$notification = '';
if (isset($_GET['updated'])) {
    $notification = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        Le panier a été mis à jour avec succès.
                    </div>';
} elseif (isset($_GET['removed'])) {
    $notification = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        L\'article a été retiré du panier.
                    </div>';
} elseif (isset($_GET['cleared'])) {
    $notification = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        Le panier a été vidé.
                    </div>';
} elseif (isset($_GET['added'])) {
    $notification = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        L\'article a été ajouté au panier avec succès.
                    </div>';
}

?>

<div class="bg-white rounded-lg shadow-lg p-6" id="cart-container">

    <?php echo $notification; ?>

    <?php if (!empty($cartItems)): ?>
        <div id="dynamic-promo-message" class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded mb-6 text-sm">
            <!-- Le message sera injecté ici par JS -->
        </div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
        <!-- Panier vide -->
        <div class="text-center py-12" id="empty-cart-message">
            <i class="fas fa-shopping-cart text-gray-300 text-5xl mb-4"></i>
            <h2 class="text-2xl font-bold mb-2">Votre panier est vide</h2>
            <p class="text-gray-600 mb-6">Ajoutez des cartes à votre panier pour commencer vos achats.</p>
            <a href="index.php" class="bg-gray-800 text-white py-3 px-6 rounded-md hover:bg-gray-900 transition">
                Parcourir les cartes
            </a>
        </div>
    <?php else: ?>
        <!-- Contenu du panier -->
        <div id="cart-content">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b-2 border-gray-200">
                            <th class="px-4 py-3 text-left">Carte</th>
                            <th class="px-4 py-3 text-center">État</th>
                            <th class="px-4 py-3 text-center">Prix unitaire</th>
                            <th class="px-4 py-3 text-center">Quantité</th>
                            <th class="px-4 py-3 text-right">Sous-total</th>
                            <th class="px-4 py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="cart-items">
                        <?php foreach ($cartItems as $item): ?>
                            <tr class="border-b border-gray-200 cart-item" data-item-id="<?php echo $item['cart_id']; ?>">
                                <td class="px-4 py-4">
                                    <div class="flex items-center">
                                        <img src="<?php echo $item['image_url'] ?: 'assets/images/card-placeholder.png'; ?>"
                                            alt="<?php echo htmlspecialchars($item['name']); ?>"
                                            class="w-16 h-16 object-contain mr-4">
                                        <div>
                                            <h3 class="font-bold">
                                                <a href="card-details.php?id=<?php echo $item['id']; ?>" class="hover:text-red-600 transition">
                                                    <?php echo htmlspecialchars($item['name']); ?> <?php echo htmlspecialchars($item['card_number']); ?>
                                                </a>
                                            </h3>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($item['series_name']); ?><br>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="condition-badge condition-<?php echo $item['condition_code']; ?>">
                                        <?php echo CARD_CONDITIONS[$item['condition_code']]; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <?php echo formatPrice($item['price']); ?>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <div class="quantity-selector mx-auto">
                                        <button type="button" class="quantity-modifier" data-modifier="minus" data-item-id="<?php echo $item['cart_id']; ?>">-</button>
                                        <input type="number" name="quantities[<?php echo $item['cart_id']; ?>]"
                                            min="1" max="<?php echo min(10, $item['available_quantity']); ?>"
                                            value="<?php echo $item['cart_quantity']; ?>"
                                            class="quantity-input"
                                            data-item-id="<?php echo $item['cart_id']; ?>">
                                        <button type="button" class="quantity-modifier" data-modifier="plus" data-item-id="<?php echo $item['cart_id']; ?>">+</button>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right font-bold item-subtotal">
                                    <?php echo formatPrice($item['subtotal']); ?>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <button type="button" class="text-red-600 hover:text-red-800 remove-item" data-item-id="<?php echo $item['cart_id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-300">
                            <td colspan="4" class="px-4 py-4 text-right font-semibold text-gray-600">Total (avant réduction) :</td>
                            <td class="px-4 py-4 text-right font-semibold text-gray-600" id="cart-total">
                                <?php echo formatPrice($cartTotal); ?>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right text-green-700">Remise :</td>
                            <td class="px-4 py-2 text-right text-green-700" id="cart-remise">- <?php echo formatPrice($remiseCHF); ?></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right font-bold">Total à payer :</td>
                            <td class="px-4 py-2 text-right font-bold text-xl text-red-600" id="cart-total-final">
                                <?php echo formatPrice($totalApresRemise); ?>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <?php if ($cartTotal <= 90): ?>
                                <td colspan="6" class="px-4 pt-2 text-right text-xs text-gray-400 italic">
                                    * Hors frais de livraison
                                </td>
                            <?php endif; ?>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-6 flex flex-wrap gap-4 justify-between">
                <div>
                    <a href="index.php" class="inline-flex items-center text-gray-600 hover:text-red-600 transition">
                        <i class="fas fa-arrow-left mr-2"></i> Continuer les achats
                    </a>
                </div>
                <div class="flex gap-2">
                    <button type="button" id="clear-cart-btn" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300 transition">
                        <i class="fas fa-trash mr-2"></i> Vider le panier
                    </button>
                    <form method="post" action="create-stripe-link.php" id="stripe-form">
                        <button type="submit" id="pay-btn" class="bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition">
                            <i class="fas fa-credit-card mr-2"></i> <span id="pay-btn-text">Payer</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const payBtn = document.getElementById('pay-btn');
        const payBtnText = document.getElementById('pay-btn-text');
        const stripeForm = document.getElementById('stripe-form');
        if (stripeForm && payBtn && payBtnText) {
            stripeForm.addEventListener('submit', function() {
                payBtn.disabled = true;
                payBtnText.textContent = "En attente de Stripe…";
            });
        }

        function updatePromoMessage() {
            const totalCell = document.getElementById('cart-total');
            const totalFinalCell = document.getElementById('cart-total-final');
            const remiseCell = document.getElementById('cart-remise');
            const promoDiv = document.getElementById('dynamic-promo-message');

            if (!totalCell || !promoDiv) return;

            // Récupérer le total brut
            const totalText = totalCell.textContent.replace('CHF', '').replace(',', '.').trim();
            const total = parseFloat(totalText);

            if (isNaN(total)) return;

            // Calcul de la remise et du total après remise
            const remise = Math.floor(total / 10);
            const totalApresRemise = total - remise;
            const pourcentageEconomie = Math.round((remise / total) * 100);

            // ✅ Mise à jour de la cellule "Total à payer"
            if (totalFinalCell) {
                totalFinalCell.textContent = totalApresRemise.toFixed(2).replace('.', ',') + ' CHF';
            }

            // ✅ Mise à jour de la cellule "Remise"
            if (remiseCell) {
                remiseCell.textContent = '- ' + remise.toFixed(2).replace('.', ',') + ' CHF';
            }

            // 💬 Message marketing évolutif
            let message = '';
            if (remise >= 8) {
                message = `🔥 <strong>ÉNORME ! ${remise} CHF d'économies instantanées</strong> sur votre commande ! 
                   <span style="color: #e74c3c; font-weight: bold;">Vous économisez ${pourcentageEconomie}%</span> 
                   grâce à notre programme de fidélité exclusif ! 💎`;
            } else if (remise >= 6) {
                message = `🎉 <strong>BRAVO ! ${remise} CHF offerts automatiquement</strong> 
                   <span style="color: #27ae60; font-weight: bold;">- ${pourcentageEconomie}% d'économies</span> 
                   sur cette commande ! Notre cadeau pour votre fidélité 🎁`;
            } else if (remise >= 4) {
                message = `⚡ <strong>${remise} CHF de réduction appliquée !</strong> 
                   <span style="color: #f39c12; font-weight: bold;">Économisez ${pourcentageEconomie}%</span> 
                   avec notre offre fidélité : <em>1 CHF gratuit tous les 10 CHF</em> 🚀`;
            } else if (remise >= 1) {
                message = `💰 <strong>${remise} CHF offerts sur cette commande !</strong> 
                   Profitez de notre programme : <em>1 CHF gratuit tous les 10 CHF d'achat</em> 
                   <span style="color: #8e44ad;">- Continuez vos achats pour encore plus d'économies !</span> ✨`;
            } else {
                message = `🎯 <strong>Astuce :</strong> À partir de 10 CHF d'achat, bénéficiez de 1 CHF offert ! 
                   <em>Plus vous achetez, plus vous économisez</em> 💡`;
            }

            // 🚚 Livraison gratuite à partir de 90 CHF
            let livraisonMessage = '';
            if (total >= 90) {
                livraisonMessage = `<div class="mt-2 text-green-800 font-semibold">
            ✅ Livraison offerte ! 🎁 Vous dépassez les 90 CHF d'achat.
        </div>`;
            } else {
                const manque = (90 - total).toFixed(2).replace('.', ',');
                livraisonMessage = `<div class="mt-2 text-yellow-800 font-medium">
            🚚 Plus que <strong>${manque} CHF</strong> pour obtenir la livraison <strong>gratuite</strong> !
        </div>`;
            }

            promoDiv.innerHTML = message + livraisonMessage;
        }

        function updateQuantity(itemId, newQuantity) {
            const formData = new FormData();
            formData.append('action', 'update_quantity');
            formData.append('item_id', itemId);
            formData.append('quantity', newQuantity);

            const row = document.querySelector(`.cart-item[data-item-id="${itemId}"]`);
            if (row) row.style.opacity = "0.7";

            fetch('update-cart-ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const input = document.querySelector(`input[data-item-id="${itemId}"]`);
                        if (input) input.value = data.quantity;

                        if (row) {
                            const subtotalCell = row.querySelector('.item-subtotal');
                            if (subtotalCell) subtotalCell.textContent = data.subtotal;
                        }

                        const totalCell = document.getElementById('cart-total');
                        if (totalCell && data.cart_total) {
                            totalCell.textContent = data.cart_total;
                        }

                        updatePromoMessage();

                        if (data.quantity <= 0 && row) {
                            row.remove();
                            if (data.cart_empty) showEmptyCart();
                        }

                        updateCartCounter(data.cart_count);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                })
                .finally(() => {
                    if (row) row.style.opacity = "1";
                });
        }

        function removeItem(itemId) {
            const formData = new FormData();
            formData.append('action', 'remove_item');
            formData.append('item_id', itemId);

            const row = document.querySelector(`.cart-item[data-item-id="${itemId}"]`);
            if (row) row.style.opacity = "0.7";

            fetch('update-cart-ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (row) row.remove();

                        const totalCell = document.getElementById('cart-total');
                        if (totalCell && data.cart_total) {
                            totalCell.textContent = data.cart_total;
                        }

                        updatePromoMessage();

                        updateCartCounter(data.cart_count);
                        if (data.cart_empty) showEmptyCart();
                    } else {
                        if (row) row.style.opacity = "1";
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    if (row) row.style.opacity = "1";
                });
        }

        function clearCart() {
            const formData = new FormData();
            formData.append('action', 'clear_cart');

            const cartContent = document.getElementById('cart-content');
            if (cartContent) cartContent.style.opacity = "0.7";

            fetch('update-cart-ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateCartCounter(0);
                        showEmptyCart();
                    } else {
                        if (cartContent) cartContent.style.opacity = "1";
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    if (cartContent) cartContent.style.opacity = "1";
                });
        }

        function showEmptyCart() {
            const cartContainer = document.getElementById('cart-container');
            if (cartContainer) {
                cartContainer.innerHTML = `
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        Le panier a été vidé.
                    </div>
                    <div class="text-center py-12">
                        <i class="fas fa-shopping-cart text-gray-300 text-5xl mb-4"></i>
                        <h2 class="text-2xl font-bold mb-2">Votre panier est vide</h2>
                        <p class="text-gray-600 mb-6">Ajoutez des cartes à votre panier pour commencer vos achats.</p>
                        <a href="index.php" class="bg-gray-800 text-white py-3 px-6 rounded-md hover:bg-gray-900 transition">
                            Parcourir les cartes
                        </a>
                    </div>
                `;
            }
        }

        function updateCartCounter(count) {
            const cartCounters = document.querySelectorAll(".cart-counter");
            cartCounters.forEach(counter => {
                if (count > 0) {
                    counter.textContent = count;
                    counter.classList.remove("hidden");
                } else {
                    counter.classList.add("hidden");
                }
            });
        }

        document.querySelectorAll('.quantity-modifier').forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.getAttribute('data-item-id');
                const input = document.querySelector(`input[data-item-id="${itemId}"]`);
                if (!input) return;

                const currentValue = parseInt(input.value, 10) || 1;
                const increment = this.dataset.modifier === 'plus' ? 1 : -1;
                const maxValue = parseInt(input.getAttribute('max') || '999', 10);
                const newValue = Math.min(maxValue, Math.max(1, currentValue + increment));
                input.value = newValue;

                updateQuantity(itemId, newValue);
            });
        });

        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.getAttribute('data-item-id');
                if (confirm('Êtes-vous sûr de vouloir supprimer cet article du panier?')) {
                    removeItem(itemId);
                }
            });
        });

        const clearCartBtn = document.getElementById('clear-cart-btn');
        if (clearCartBtn) {
            clearCartBtn.addEventListener('click', function() {
                if (confirm('Êtes-vous sûr de vouloir vider entièrement votre panier?')) {
                    clearCart();
                }
            });
        }

        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const itemId = this.getAttribute('data-item-id');
                const newValue = parseInt(this.value, 10) || 1;
                updateQuantity(itemId, newValue);
            });
        });

        // Appel initial
        updatePromoMessage();
    });

    function showStripeRedirectMessage() {
        const existing = document.querySelector('.notification');
        if (existing) existing.remove();

        const notification = document.createElement('div');
        notification.className = 'notification fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 bg-blue-600 text-white transition-opacity duration-300';
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-spinner fa-spin mr-2"></i>
                <span>Redirection vers Stripe en cours...</span>
            </div>
        `;
        document.body.appendChild(notification);
    }
</script>


<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>