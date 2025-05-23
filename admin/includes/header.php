<?php
// Afficher les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// admin/includes/header.php
session_start();
require_once __DIR__ . '/../../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est administrateur
if (!isUserLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Administration BDPokéCards</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/custom.css">
    <!-- Favicon -->
    <link rel="icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico" type="image/x-icon">
</head>

<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-gray-800 text-white shadow-md">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <a href="<?php echo SITE_URL; ?>/admin/" class="flex items-center">
                    <i class="fas fa-cog text-2xl mr-2"></i>
                    <span class="text-xl font-bold">Administration</span>
                </a>

                <div>
                    <span class="mr-4">
                        <i class="fas fa-user mr-1"></i> <?php echo $_SESSION['username']; ?>
                    </span>
                    <a href="logout.php" class="text-red-300 hover:text-red-100 transition">
                        <i class="fas fa-sign-out-alt mr-1"></i> Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="flex flex-grow">
        <!-- Sidebar de navigation -->
        <aside class="w-64 bg-gray-900 text-white">
            <nav class="p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="<?php echo SITE_URL; ?>/admin/" class="flex items-center p-3 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-gray-700' : 'hover:bg-gray-800'; ?> transition">
                            <i class="fas fa-tachometer-alt w-6"></i>
                            <span>Tableau de bord</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/admin/cards.php" class="flex items-center p-3 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'cards.php' ? 'bg-gray-700' : 'hover:bg-gray-800'; ?> transition">
                            <i class="fas fa-credit-card w-6"></i>
                            <span>Gestion des cartes</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/admin/series.php" class="flex items-center p-3 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'series.php' ? 'bg-gray-700' : 'hover:bg-gray-800'; ?> transition">
                            <i class="fas fa-layer-group w-6"></i>
                            <span>Gestion des séries</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="flex items-center p-3 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'bg-gray-700' : 'hover:bg-gray-800'; ?> transition">
                            <i class="fas fa-shopping-cart w-6"></i>
                            <span>Commandes</span>
                        </a>
                    </li>
                    <li class="pt-6 border-t border-gray-700">
                        <a href="<?php echo SITE_URL; ?>" class="flex items-center p-3 rounded-md hover:bg-gray-800 transition" target="_blank">
                            <i class="fas fa-store w-6"></i>
                            <span>Voir la boutique</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Contenu principal -->
        <main class="flex-grow p-6">
            <?php if (isset($pageTitle)): ?>
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold"><?php echo $pageTitle; ?></h1>

                    <?php if (isset($actionButton)): ?>
                        <a href="<?php echo $actionButton['url']; ?>" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition">
                            <i class="<?php echo $actionButton['icon']; ?> mr-1"></i>
                            <?php echo $actionButton['text']; ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php
            // Afficher les messages flash
            if (isset($_SESSION['flash_message'])) {
                $type = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'success';
                $colorClass = $type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
                $icon = $type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            ?>
                <div class="<?php echo $colorClass; ?> border px-4 py-3 rounded mb-6">
                    <div class="flex items-center">
                        <i class="fas <?php echo $icon; ?> mr-2"></i>
                        <span><?php echo $_SESSION['flash_message']; ?></span>
                    </div>
                </div>
            <?php
                unset($_SESSION['flash_message']);
                unset($_SESSION['flash_type']);
            }
            ?>