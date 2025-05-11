<?php
// admin/delete-series.php

// Inclure les fonctions
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est administrateur
session_start();
if (!isUserLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

// Vérifier si l'ID de la série est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = 'ID de série non valide';
    $_SESSION['flash_type'] = 'error';
    header('Location: series.php');
    exit;
}

$seriesId = (int)$_GET['id'];

// Récupérer les informations de la série
$series = getSeriesById($seriesId);

// Si la série n'existe pas, rediriger vers la liste des séries
if (!$series) {
    $_SESSION['flash_message'] = 'Série non trouvée';
    $_SESSION['flash_type'] = 'error';
    header('Location: series.php');
    exit;
}

// Vérifier si la série contient des cartes
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT COUNT(*) as card_count FROM cards WHERE series_id = ?");
$stmt->execute([$seriesId]);
$cardCount = $stmt->fetch()['card_count'];

if ($cardCount > 0) {
    $_SESSION['flash_message'] = 'Impossible de supprimer une série contenant des cartes';
    $_SESSION['flash_type'] = 'error';
    header('Location: series.php');
    exit;
}

// Supprimer le logo associé si il existe
if ($series['logo_url'] && file_exists('../' . $series['logo_url'])) {
    unlink('../' . $series['logo_url']);
}

// Supprimer la série
if (deleteSeries($seriesId)) {
    $_SESSION['flash_message'] = 'La série a été supprimée avec succès';
    $_SESSION['flash_type'] = 'success';
} else {
    $_SESSION['flash_message'] = 'Erreur lors de la suppression de la série';
    $_SESSION['flash_type'] = 'error';
}

// Rediriger vers la liste des séries
header('Location: series.php');
exit;
