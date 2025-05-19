<?php
// cart.php

// Inclure les fonctions nécessaires
require_once 'includes/functions.php';

// Définir le titre de la page
$pageTitle = 'Votre panier';

// Inclure l'en-tête
require_once 'includes/header.php';

// Récupérer les articles du panier
$cartItems = getCartItems();
$cartTotal = getCartTotal();

// Suppression d'un article du panier
if (isset($_GET['remove']) && isset($_GET['condition'])) {
    $cardId = (int)$_GET['remove'];
    $condition = sanitizeInput($_GET['condition']);
    removeFromCart($cardId, $condition);

    // Redirection pour éviter de conserver les paramètres dans l'URL
    header('Location: cart.php');
    exit;
}

// Mise à jour des quantités
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $key => $quantity) {
        // Vérifier que la clé a un format valide
        if (strpos($key, '|') === false) {
            continue;
        }

        list($cardId, $condition) = explode('|', $key);
        $cardId = (int)$cardId;
        $quantity = (int)$quantity;

        if ($quantity > 0) {
            updateCartItem($cardId, $condition, $quantity);
        } else {
            removeFromCart($cardId, $condition);
        }
    }

    // Redirection pour éviter la soumission multiple du formulaire
    header('Location: cart.php');
    exit;
}
?>

<div class="bg-white rounded-lg shadow-lg p-6">
    <h1 class="text-3xl font-bold mb-6">Votre panier</h1>

    <?php if (empty($cartItems)): ?>
        <!-- Panier vide -->
        <div class="empty-cart-message p-8 text-center">
            <i class="fas fa-shopping-cart text-4xl text-gray-400 mb-4"></i>
            <h2 class="text-2xl font-bold mb-2">Votre panier est vide</h2>
            <p class="text-gray-600 mb-4">Ajoutez des cartes à votre collection !</p>
            <a href="index.php" class="bg-gray-800 text-white px-6 py-2 rounded-lg hover:bg-gray-900 transition">
                Parcourir les cartes
            </a>
        </div>
    <?php else: ?>
        <!-- Contenu du panier -->
        <form method="POST" action="cart.php" class="cart-container">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b-2 border-gray-200">
                            <th class="py-2 px-4 text-left">Carte</th>
                            <th class="py-2 px-4 text-left">État</th>
                            <th class="py-2 px-4 text-right">Prix unitaire</th>
                            <th class="py-2 px-4 text-center">Quantité</th>
                            <th class="py-2 px-4 text-right">Sous-total</th>
                            <th class="py-2 px-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): ?>
                            <tr class="cart-item border-b border-gray-200" data-card-id="<?php echo $item['id']; ?>" data-condition="<?php echo $item['condition_code']; ?>">
                                <td class="py-4 px-4">
                                    <div class="flex items-center">
                                        <img src="<?php echo $item['image_url'] ?: 'assets/images/card-placeholder.png'; ?>"
                                            alt="<?php echo htmlspecialchars($item['name']); ?>"
                                            class="w-16 h-16 object-contain mr-3">
                                        <div>
                                            <a href="card-details.php?id=<?php echo $item['id']; ?>" class="font-medium hover:text-red-600 transition">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </a>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($item['series_name']); ?> |
                                                N°: <?php echo htmlspecialchars($item['card_number']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="condition-badge condition-<?php echo $item['condition_code']; ?>">
                                        <?php echo CARD_CONDITIONS[$item['condition_code']]; ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <?php echo formatPrice($item['price']); ?>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <div class="quantity-selector mx-auto">
                                        <button type="button" class="quantity-modifier" data-modifier="minus">-</button>
                                        <input type="number" name="quantity[<?php echo $item['id'] . '|' . $item['condition_code']; ?>]"
                                            min="1" max="<?php echo $item['quantity']; ?>" value="<?php echo $item['cart_quantity']; ?>"
                                            class="quantity-input">
                                        <button type="button" class="quantity-modifier" data-modifier="plus">+</button>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-right font-bold subtotal">
                                    <?php echo formatPrice($item['subtotal']); ?>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <a href="cart.php?remove=<?php echo $item['id']; ?>&condition=<?php echo $item['condition_code']; ?>"
                                        class="remove-from-cart text-red-600 hover:text-red-800 transition">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="py-4 px-4 text-right font-bold">Total:</td>
                            <td class="py-4 px-4 text-right font-bold cart-total">
                                <?php echo formatPrice($cartTotal); ?>
                            </td>
                            <td class="py-4 px-4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="flex flex-col md:flex-row justify-between mt-6">
                <a href="index.php" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300 transition mb-4 md:mb-0 text-center">
                    <i class="fas fa-arrow-left mr-1"></i> Continuer mes achats
                </a>

                <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                    <button type="submit" name="update_cart" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300 transition">
                        <i class="fas fa-sync-alt mr-1"></i> Mettre à jour le panier
                    </button>

                    <a href="checkout.php" class="bg-gray-800 text-white py-2 px-4 rounded-md hover:bg-gray-900 transition text-center">
                        <i class="fas fa-shopping-bag mr-1"></i> Passer commande
                    </a>
                </div>
            </div>
        </form>

        <!-- Informations complémentaires -->
        <div class="mt-8 p-4 bg-gray-100 rounded-lg">
            <h3 class="text-lg font-bold mb-2">Informations importantes:</h3>
            <ul class="list-disc list-inside space-y-2 text-gray-700">
                <li>Toutes les cartes sont livrées avec une sleeve de protection.</li>
                <li>Pour les cartes de plus de 2.00 CHF, un toploader est également inclus.</li>
                <li>Frais de port inclus pour toute commande en Suisse.</li>
                <li>La livraison prend généralement entre 3 et 7 jours ouvrables.</li>
                <li>Paiement par virement bancaire, TWINT ou en espèces (remise en main propre).</li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion des boutons de quantité
        document.querySelectorAll('.quantity-modifier').forEach(button => {
            button.addEventListener('click', function() {
                const selector = this.closest('.quantity-selector');
                if (!selector) return;

                const input = selector.querySelector('input');
                if (!input) return;

                const currentValue = parseInt(input.value, 10) || 1;
                const increment = this.dataset.modifier === 'plus' ? 1 : -1;
                const maxValue = parseInt(input.getAttribute('max') || '999', 10);

                // Si on diminue en dessous de 1, supprimer l'article
                if (currentValue + increment < 1) {
                    // Trouver le bouton de suppression associé et le cliquer
                    const cartItem = this.closest('.cart-item');
                    if (cartItem) {
                        const removeButton = cartItem.querySelector('.remove-from-cart');
                        if (removeButton) {
                            if (confirm('Voulez-vous supprimer cet article du panier?')) {
                                window.location.href = removeButton.getAttribute('href');
                            }
                            return;
                        }
                    }
                }

                input.value = Math.min(maxValue, Math.max(1, currentValue + increment));

                // Trigger change event for cart-ajax.js to catch
                const changeEvent = new Event('change', {
                    bubbles: true
                });
                input.dispatchEvent(changeEvent);
            });
        });

        // Validation des inputs de quantité
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const maxValue = parseInt(this.getAttribute('max') || '999', 10);
                let currentValue = parseInt(this.value, 10) || 1;

                if (currentValue > maxValue) {
                    this.value = maxValue;
                    showNotification(`Quantité limitée à ${maxValue} en stock`, "error");
                } else if (currentValue < 1) {
                    this.value = 1;
                }
            });
        });

        // Gestion de la suppression d'articles
        document.querySelectorAll('.remove-from-cart').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Êtes-vous sûr de vouloir supprimer cet article de votre panier?')) {
                    e.preventDefault();
                }
            });
        });

        // Fonction pour afficher des notifications
        function showNotification(message, type) {
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
        }
    });
</script>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>