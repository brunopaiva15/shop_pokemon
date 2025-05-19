<?php
// add-to-cart.php - Traite les requêtes AJAX pour ajouter des articles au panier
session_start();

// Inclure les fichiers nécessaires
require_once 'includes/functions.php';

// S'assurer que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données du formulaire
$cardId = isset($_POST['card_id']) ? (int)$_POST['card_id'] : 0;
$condition = isset($_POST['condition']) ? $_POST['condition'] : '';
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

// Validation des données
if ($cardId <= 0 || empty($condition) || $quantity <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

// Ajouter au panier
$success = addToCart($cardId, $condition, $quantity);

if ($success) {
    // Récupérer le nombre d'articles dans le panier pour la mise à jour de l'interface
    $cartCount = getCartItemCount();

    echo json_encode([
        'success' => true,
        'message' => 'Article ajouté au panier',
        'cart_count' => $cartCount
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Impossible d\'ajouter l\'article au panier. Vérifiez la disponibilité.'
    ]);
}
