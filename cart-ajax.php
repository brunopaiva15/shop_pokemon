<?php
// cart-ajax.php
session_start();
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Vérifier si la requête est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer l'action demandée
$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'add':
        // Ajouter un article au panier
        if (!isset($_POST['card_id']) || !isset($_POST['quantity'])) {
            echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
            exit;
        }

        $cardId = (int)$_POST['card_id'];
        $quantity = (int)$_POST['quantity'];

        if ($quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'La quantité doit être supérieure à 0']);
            exit;
        }

        // Vérifier que la carte existe et a un stock suffisant
        $card = getCardById($cardId);
        if (!$card) {
            echo json_encode(['success' => false, 'message' => 'Carte non trouvée']);
            exit;
        }

        // Vérifier si le panier contient déjà cette carte
        initCart();
        $currentQty = isset($_SESSION['cart'][$cardId]) ? $_SESSION['cart'][$cardId] : 0;
        $totalQty = $currentQty + $quantity;

        // Vérifier la quantité en stock (en tenant compte de la quantité déjà dans le panier)
        if ($card['quantity'] < $totalQty) {
            // Si demande excessive, ajuster à la quantité maximale disponible
            $availableQty = $card['quantity'] - $currentQty;

            if ($availableQty <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Vous avez déjà le maximum disponible dans votre panier'
                ]);
                exit;
            }

            // Ajouter seulement la quantité disponible
            addToCart($cardId, $availableQty);

            echo json_encode([
                'success' => true,
                'cart_count' => getCartItemCount(),
                'message' => 'Stock limité: ' . $availableQty . ' ajouté(s) au panier'
            ]);
            exit;
        }

        // Ajouter au panier normalement
        addToCart($cardId, $quantity);

        echo json_encode([
            'success' => true,
            'cart_count' => getCartItemCount(),
            'message' => 'Carte ajoutée au panier'
        ]);
        break;

    case 'update':
        // Mettre à jour la quantité d'un article
        if (!isset($_POST['card_id']) || !isset($_POST['quantity'])) {
            echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
            exit;
        }

        $cardId = (int)$_POST['card_id'];
        $quantity = (int)$_POST['quantity'];

        if ($quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'La quantité doit être supérieure à 0']);
            exit;
        }

        // Vérifier que la carte existe et a un stock suffisant
        $card = getCardById($cardId);
        if (!$card) {
            echo json_encode(['success' => false, 'message' => 'Carte non trouvée']);
            exit;
        }

        // Vérifier la quantité en stock
        if ($card['quantity'] < $quantity) {
            // Si demande excessive, ajuster à la quantité maximale disponible
            $quantity = $card['quantity'];

            updateCartItem($cardId, $quantity);

            // Récalculer le sous-total de l'article
            $itemSubtotal = formatPrice($card['price'] * $quantity);

            echo json_encode([
                'success' => true,
                'cart_count' => getCartItemCount(),
                'cart_total' => formatPrice(getCartTotal()),
                'item_subtotal' => $itemSubtotal,
                'message' => 'Quantité ajustée au maximum disponible: ' . $quantity
            ]);
            exit;
        }

        // Mettre à jour le panier
        updateCartItem($cardId, $quantity);

        // Récalculer le sous-total de l'article
        $itemSubtotal = formatPrice($card['price'] * $quantity);

        echo json_encode([
            'success' => true,
            'cart_count' => getCartItemCount(),
            'cart_total' => formatPrice(getCartTotal()),
            'item_subtotal' => $itemSubtotal,
            'message' => 'Panier mis à jour'
        ]);
        break;

    case 'remove':
        // Supprimer un article du panier
        if (!isset($_POST['card_id'])) {
            echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
            exit;
        }

        $cardId = (int)$_POST['card_id'];

        // Supprimer du panier
        removeFromCart($cardId);

        echo json_encode([
            'success' => true,
            'cart_count' => getCartItemCount(),
            'cart_total' => formatPrice(getCartTotal()),
            'message' => 'Article supprimé du panier'
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        break;
}
