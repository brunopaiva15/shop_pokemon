<?php
// admin/delete-card.php

// Inclure les fonctions
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est administrateur
session_start();
if (!isUserLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

// Vérifier si l'ID de la carte est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = 'ID de carte non valide';
    $_SESSION['flash_type'] = 'error';
    header('Location: cards.php');
    exit;
}

$cardId = (int)$_GET['id'];

// Récupérer les informations de la carte
$card = getCardById($cardId);

// Si la carte n'existe pas, rediriger vers la liste des cartes
if (!$card) {
    $_SESSION['flash_message'] = 'Carte non trouvée';
    $_SESSION['flash_type'] = 'error';
    header('Location: cards.php');
    exit;
}

// Supprimer l'image associée si elle existe
if ($card['image_url'] && file_exists('../' . $card['image_url'])) {
    unlink('../' . $card['image_url']);
}

// Supprimer la carte
if (deleteCard($cardId)) {
    $_SESSION['flash_message'] = 'La carte a été supprimée avec succès';
    $_SESSION['flash_type'] = 'success';
} else {
    $_SESSION['flash_message'] = 'Erreur lors de la suppression de la carte';
    $_SESSION['flash_type'] = 'error';
}

// Rediriger vers la liste des cartes
header('Location: cards.php');
exit;
