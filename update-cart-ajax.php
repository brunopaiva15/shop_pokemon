<?php
// update-cart-ajax.php
session_start();

// Inclure les fonctions nécessaires
require_once 'includes/functions.php';

// Initialiser la réponse
$response = [
    'success' => false,
    'message' => 'Action non valide'
];

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les paramètres
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'update_quantity' && isset($_POST['item_id']) && isset($_POST['quantity'])) {
        $itemId = $_POST['item_id'];
        $quantity = (int)$_POST['quantity'];

        // Mettre à jour la quantité
        $result = updateCartItemQuantity($itemId, $quantity);

        if ($result) {
            // Récupérer les informations mises à jour
            $cartItems = getCartItems();
            $cartTotal = getCartTotal();
            $cartEmpty = empty($cartItems);

            // Trouver l'article mis à jour
            $updatedItem = null;
            foreach ($cartItems as $item) {
                if ($item['cart_id'] == $itemId) {
                    $updatedItem = $item;
                    break;
                }
            }

            $response = [
                'success' => true,
                'message' => 'Quantité mise à jour avec succès',
                'quantity' => $updatedItem ? $updatedItem['cart_quantity'] : 0,
                'subtotal' => $updatedItem ? formatPrice($updatedItem['subtotal']) : '0.00 CHF',
                'cart_total' => formatPrice($cartTotal),
                'cart_count' => getCartItemCount(),
                'cart_empty' => $cartEmpty
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la quantité'
            ];
        }
    } elseif ($action === 'clear_cart') {
        // Vider le panier
        clearCart();

        $response = [
            'success' => true,
            'message' => 'Panier vidé avec succès',
            'cart_count' => 0,
            'cart_total' => formatPrice(0),
            'cart_empty' => true
        ];
    } elseif ($action === 'remove_item' && isset($_POST['item_id'])) {
        // Supprimer un article spécifique
        $itemId = $_POST['item_id'];
        $result = removeCartItem($itemId);

        if ($result) {
            $cartItems = getCartItems();
            $cartTotal = getCartTotal();
            $cartCount = getCartItemCount();
            $cartEmpty = empty($cartItems);

            $response = [
                'success' => true,
                'message' => 'Article supprimé avec succès',
                'cart_total' => formatPrice($cartTotal),
                'cart_count' => $cartCount,
                'cart_empty' => $cartEmpty
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'article'
            ];
        }
    }
}

// Envoyer la réponse au format JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
