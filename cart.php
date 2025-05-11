<?php
// cart.php

// Définir le titre de la page
$pageTitle = 'Votre panier';

// Inclure l'en-tête
require_once 'includes/header.php';

// Récupérer les articles du panier
$cartItems = getCartItems();
$cartTotal = getCartTotal();
?>

<div class="cart-container bg-white rounded-lg shadow-lg p-6">
    <h1 class="text-3xl font-bold mb-6">Votre panier</h1>

    <?php if (empty($cartItems)): ?>
        <!-- Panier vide -->
        <div class="empty-cart-message p-8 text-center">
            <i class="fas fa-shopping-cart text-4xl text-gray-400 mb-4"></i>
            <h2 class="text-2xl font-bold mb-2">Votre panier est vide</h2>
            <p class="text-gray-600 mb-4">Ajoutez des cartes à votre collection !</p>
            <a href="index.php" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition">
                Parcourir les cartes
            </a>
        </div>
    <?php else: ?>
        <!-- Panier avec articles -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">Carte</th>
                        <th class="py-3 px-6 text-center">État</th>
                        <th class="py-3 px-6 text-center">Prix</th>
                        <th class="py-3 px-6 text-center">Quantité</th>
                        <th class="py-3 px-6 text-center">Sous-total</th>
                        <th class="py-3 px-6 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm">
                    <?php foreach ($cartItems as $item): ?>
                        <tr class="cart-item border-b border-gray-200 hover:bg-gray-50" data-card-id="<?php echo $item['id']; ?>">
                            <td class="py-4 px-6 text-left">
                                <div class="flex items-center">
                                    <div class="mr-4">
                                        <img src="<?php echo $item['image_url'] ?: 'assets/images/card-placeholder.png'; ?>"
                                            alt="<?php echo htmlspecialchars($item['name']); ?>"
                                            class="w-16 h-16 object-contain">
                                    </div>
                                    <div>
                                        <a href="card-details.php?id=<?php echo $item['id']; ?>" class="font-medium hover:text-red-600 transition">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </a>
                                        <div class="text-xs text-gray-500">
                                            Série: <?php echo isset($item['series_name']) ? htmlspecialchars($item['series_name']) : 'Non spécifiée'; ?><br>
                                            N°: <?php echo htmlspecialchars($item['card_number']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="condition-badge condition-<?php echo $item['card_condition']; ?>">
                                    <?php echo isset(CARD_CONDITIONS[$item['card_condition']]) ? CARD_CONDITIONS[$item['card_condition']] : 'Non spécifié'; ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <?php echo formatPrice($item['price']); ?>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <div class="quantity-selector mx-auto">
                                    <button type="button" class="quantity-modifier" data-modifier="minus">-</button>
                                    <input type="number" min="1" max="<?php echo $item['quantity']; ?>" value="<?php echo $item['cart_quantity']; ?>" class="quantity-input">
                                    <button type="button" class="quantity-modifier" data-modifier="plus">+</button>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-center font-bold subtotal">
                                <?php echo formatPrice($item['subtotal']); ?>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <button class="remove-from-cart text-red-600 hover:text-red-800 transition">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Résumé du panier -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div></div>
            <div class="bg-gray-100 p-6 rounded-lg">
                <h3 class="text-xl font-bold mb-4">Résumé de la commande</h3>

                <div class="flex justify-between border-b border-gray-300 pb-4 mb-4">
                    <span>Total (<?php echo count($cartItems); ?> article<?php echo count($cartItems) > 1 ? 's' : ''; ?>)</span>
                    <span class="font-bold cart-total"><?php echo formatPrice($cartTotal); ?></span>
                </div>

                <div class="flex justify-between items-center mb-4">
                    <a href="index.php" class="text-gray-600 hover:text-red-600 transition">
                        <i class="fas fa-arrow-left mr-1"></i> Continuer les achats
                    </a>

                    <a href="checkout.php" class="bg-red-600 text-white py-3 px-6 rounded-md hover:bg-red-700 transition">
                        Procéder au paiement <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>