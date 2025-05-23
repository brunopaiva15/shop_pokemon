<?php
// admin/order-status-ajax.php
session_start();
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté et est administrateur
if (!isUserLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Vérifier si la requête est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les paramètres
if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$orderId = (int)$_POST['order_id'];
$status = $_POST['status'];

// Valider le statut
$validStatuses = ['pending', 'processing', 'completed', 'cancelled', 'refunded'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Statut non valide']);
    exit;
}

// Mettre à jour le statut
if (updateOrderStatus($orderId, $status)) {
    echo json_encode(['success' => true, 'message' => 'Statut mis à jour']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du statut']);
}
