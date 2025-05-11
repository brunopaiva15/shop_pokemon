<?php
// includes/header.php
session_start();
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Pokemon Shop</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/custom.css">
</head>

<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-red-600 text-white shadow-md">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <a href="<?php echo SITE_URL; ?>" class="flex items-center">
                    <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="Pokemon Shop" class="h-10 mr-3">
                    <span class="text-2xl font-bold">Pokemon Shop</span>
                </a>

                <div class="flex items-center space-x-4">
                    <form action="<?php echo SITE_URL; ?>/search.php" method="GET" class="hidden md:flex">
                        <input type="text" name="q" placeholder="Rechercher une carte..."
                            class="px-4 py-2 rounded-l-lg text-gray-800 focus:outline-none">
                        <button type="submit" class="bg-yellow-500 text-white px-4 py-2 rounded-r-lg hover:bg-yellow-600 transition">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>

                    <a href="<?php echo SITE_URL; ?>/cart.php" class="relative">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <?php if (getCartItemCount() > 0): ?>
                            <span class="absolute -top-2 -right-2 bg-yellow-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">
                                <?php echo getCartItemCount(); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-grow container mx-auto px-4 py-6">
        <?php if (isset($pageTitle)): ?>
            <h1 class="text-3xl font-bold mb-6"><?php echo $pageTitle; ?></h1>
        <?php endif; ?>