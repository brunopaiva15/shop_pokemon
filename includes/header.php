<?php
// includes/header.php
session_start();
require_once __DIR__ . '/functions.php'; // Utilisation d'un chemin absolu
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

            <nav class="mt-4">
                <ul class="flex flex-wrap space-x-6">
                    <li><a href="<?php echo SITE_URL; ?>" class="hover:text-yellow-300 transition">Accueil</a></li>
                    <?php
                    // Afficher les séries dans le menu
                    $series = getAllSeries();
                    $seriesCount = count($series);
                    $maxDisplay = 5; // Nombre maximum de séries à afficher directement

                    for ($i = 0; $i < min($seriesCount, $maxDisplay); $i++):
                    ?>
                        <li><a href="<?php echo SITE_URL . '/?series=' . $series[$i]['id']; ?>" class="hover:text-yellow-300 transition">
                                <?php echo htmlspecialchars($series[$i]['name']); ?>
                            </a></li>
                    <?php endfor; ?>

                    <?php if ($seriesCount > $maxDisplay): ?>
                        <li class="relative group">
                            <a href="#" class="hover:text-yellow-300 transition">
                                Plus de séries <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </a>
                            <div class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 hidden group-hover:block">
                                <div class="py-1">
                                    <?php for ($i = $maxDisplay; $i < $seriesCount; $i++): ?>
                                        <a href="<?php echo SITE_URL . '/?series=' . $series[$i]['id']; ?>"
                                            class="block px-4 py-2 text-gray-800 hover:bg-red-500 hover:text-white transition">
                                            <?php echo htmlspecialchars($series[$i]['name']); ?>
                                        </a>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="flex-grow container mx-auto px-4 py-6">
        <?php if (isset($pageTitle)): ?>
            <h1 class="text-3xl font-bold mb-6"><?php echo $pageTitle; ?></h1>
        <?php endif; ?>