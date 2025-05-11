<?php
// admin/update-card-stock.php

// Inclure les fonctions nécessaires
require_once '../includes/functions.php';

// Vérifier si les paramètres nécessaires sont fournis
if (!isset($_POST['card_id']) || !isset($_POST['add_quantity']) || !is_numeric($_POST['card_id']) || !is_numeric($_POST['add_quantity'])) {
    $_SESSION['flash_message'] = 'Paramètres invalides';
    $_SESSION['flash_type'] = 'error';
    header('Location: cards.php');
    exit;
}

$cardId = (int)$_POST['card_id'];
$addQuantity = (int)$_POST['add_quantity'];

// Vérifier que la quantité à ajouter est positive
if ($addQuantity <= 0) {
    $_SESSION['flash_message'] = 'La quantité à ajouter doit être positive';
    $_SESSION['flash_type'] = 'error';
    header('Location: cards.php');
    exit;
}

// Récupérer la carte existante
$card = getCardById($cardId);

// Vérifier que la carte existe
if (!$card) {
    $_SESSION['flash_message'] = 'Carte non trouvée';
    $_SESSION['flash_type'] = 'error';
    header('Location: cards.php');
    exit;
}

// Calculer la nouvelle quantité
$newQuantity = $card['quantity'] + $addQuantity;

// Mettre à jour le stock
if (updateCardStock($cardId, $newQuantity)) {
    $_SESSION['flash_message'] = 'Le stock de la carte a été mis à jour avec succès';
    $_SESSION['flash_type'] = 'success';
} else {
    $_SESSION['flash_message'] = 'Erreur lors de la mise à jour du stock';
    $_SESSION['flash_type'] = 'error';
}

// Rediriger vers la liste des cartes
header('Location: cards.php');
exit;
