<?php
// search.php

// Vérifier si un terme de recherche est fourni
if (!isset($_GET['q']) || empty($_GET['q'])) {
    header('Location: index.php');
    exit;
}

$searchTerm = sanitizeInput($_GET['q']);

// Définir le titre de la page
$pageTitle = 'Recherche : ' . htmlspecialchars($searchTerm);

// Inclure les filtres
$includeFiltersScript = true;

// Récupérer les paramètres de filtrage et de pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12; // Nombre de cartes par page
$offset = ($page - 1) * $perPage;

$seriesId = isset($_GET['series']) ? (int)$_GET['series'] : null;
$condition = isset($_GET['condition']) ? $_GET['condition'] : null;
$priceMin = isset($_GET['price_min']) && is_numeric($_GET['price_min']) ? (float)$_GET['price_min'] : null;
$priceMax = isset($_GET['price_max']) && is_numeric($_GET['price_max']) ? (float)$_GET['price_max'] : null;

// Inclure l'en-tête
require_once 'includes/header.php';

// Récupérer les résultats de recherche
$searchResults = searchCards($searchTerm, $seriesId);

// Filtrer les résultats en fonction des autres critères
if ($priceMin !== null || $priceMax !== null || $condition !== null) {
    $filteredResults = [];

    foreach ($searchResults as $card) {
        $priceOk = true;
        $conditionOk = true;

        if ($priceMin !== null && $card['price'] < $priceMin) {
            $priceOk = false;
        }

        if ($priceMax !== null && $card['price'] > $priceMax) {
            $priceOk = false;
        }

        if ($condition !== null && $card['condition'] !== $condition) {
            $conditionOk = false;
        }

        if ($priceOk && $conditionOk) {
            $filteredResults[] = $card;
        }
    }

    $searchResults = $filteredResults;
}

// Déterminer le tri
$sortOptions = [
    'newest' => function ($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    },
    'oldest' => function ($a, $b) {
        return strtotime($a['created_at']) - strtotime($b['created_at']);
    },
    'price_low' => function ($a, $b) {
        return $a['price'] - $b['price'];
    },
    'price_high' => function ($a, $b) {
        return $b['price'] - $a['price'];
    },
    'name_asc' => function ($a, $b) {
        return strcmp($a['name'], $b['name']);
    },
    'name_desc' => function ($a, $b) {
        return strcmp($b['name'], $a['name']);
    }
];

$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortOptions) ? $_GET['sort'] : 'newest';

// Trier les résultats
usort($searchResults, $sortOptions[$sort]);

// Paginer les résultats
$totalResults = count($searchResults);
$totalPages = ceil($totalResults / $perPage);
$paginatedResults = array_slice($searchResults, $offset, $perPage);

// Récupérer toutes les séries pour les filtres
$allSeries = getAllSeries();

// Générer les paramètres d'URL pour la pagination
$paginationParams = $_GET;
unset($paginationParams['page']); // Supprimer le paramètre de page existant
$paginationUrl = '?' . http_build_query($paginationParams) . '&page=';
?>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <!-- Sidebar avec filtres -->
    <div id="filter-sidebar" class="md:col-span-1 hidden md:block">
        <div class="filter-container mb-6">
            <h3 class="filter-title">Recherche</h3>
            <div class="filter-content">
                <form action="search.php" method="GET" class="flex">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($searchTerm); ?>"
                        class="flex-grow p-2 border border-gray-300 rounded-l-md">
                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-r-md hover:bg-red-700 transition">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="filter-container mb-6">
            <h3 class="filter-title">Filtrer par série</h3>
            <div class="filter-content">
                <select id="series-filter" class="w-full p-2 border border-gray-300 rounded-md">
                    <option value="">Toutes les séries</option>
                    <?php foreach ($allSeries as $series): ?>
                        <option value="<?php echo $series['id']; ?>" <?php echo $seriesId == $series['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($series['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="filter-container mb-6">
            <h3 class="filter-title">Filtrer par état</h3>
            <div class="filter-content">
                <select id="condition-filter" class="w-full p-2 border border-gray-300 rounded-md">
                    <option value="">Tous les états</option>
                    <?php foreach (CARD_CONDITIONS as $code => $name): ?>
                        <option value="<?php echo $code; ?>" <?php echo $condition == $code ? 'selected' : ''; ?>>
                            <?php echo $name; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="filter-container mb-6">
            <h3 class="filter-title">Filtrer par prix</h3>
            <div class="filter-content space-y-3">
                <div>
                    <label for="price-min" class="block text-sm font-medium text-gray-700">Prix minimum</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">CHF</span>
                        </div>
                        <input type="number" min="0" step="0.01" id="price-min" name="price_min"
                            value="<?php echo $priceMin; ?>"
                            class="pl-7 p-2 block w-full border border-gray-300 rounded-md">
                    </div>
                </div>
                <div>
                    <label for="price-max" class="block text-sm font-medium text-gray-700">Prix maximum</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">CHF</span>
                        </div>
                        <input type="number" min="0" step="0.01" id="price-max" name="price_max"
                            value="<?php echo $priceMax; ?>"
                            class="pl-7 p-2 block w-full border border-gray-300 rounded-md">
                    </div>
                </div>
            </div>
        </div>

        <div class="filter-container mb-6">
            <h3 class="filter-title">Trier par</h3>
            <div class="filter-content">
                <select id="sort-filter" class="w-full p-2 border border-gray-300 rounded-md">
                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Plus récent</option>
                    <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Plus ancien</option>
                    <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Prix croissant</option>
                    <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Prix décroissant</option>
                    <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Nom (A-Z)</option>
                    <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Nom (Z-A)</option>
                </select>
            </div>
        </div>

        <div class="space-y-2">
            <button id="apply-filters" class="w-full bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 transition">
                Appliquer les filtres
            </button>
            <button id="reset-filters" class="w-full bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300 transition">
                Réinitialiser les filtres
            </button>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="md:col-span-3">
        <!-- Titre et barre d'outils mobile -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">
                Résultats pour "<?php echo htmlspecialchars($searchTerm); ?>"
                <span class="text-sm font-normal text-gray-500">(<?php echo $totalResults; ?> cartes)</span>
            </h2>

            <button id="mobile-filter-toggle" class="md:hidden bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300 transition">
                <i class="fas fa-filter mr-1"></i> Filtres
            </button>
        </div>

        <?php if (empty($paginatedResults)): ?>
            <!-- Aucun résultat trouvé -->
            <div class="bg-white p-8 rounded-lg shadow-md text-center">
                <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-2xl font-bold mb-2">Aucun résultat trouvé</h3>
                <p class="text-gray-600 mb-4">Aucune carte ne correspond à votre recherche "<?php echo htmlspecialchars($searchTerm); ?>".</p>
                <a href="index.php" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition">
                    Voir toutes les cartes
                </a>
            </div>
        <?php else: ?>
            <!-- Grille de cartes -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($paginatedResults as $card): ?>
                    <div class="card-item bg-white rounded-lg shadow-md overflow-hidden card-hover">
                        <div class="card-image-zoom p-4 bg-gray-100">
                            <a href="card-details.php?id=<?php echo $card['id']; ?>">
                                <img src="<?php echo $card['image_url'] ?: 'assets/images/card-placeholder.png'; ?>"
                                    alt="<?php echo htmlspecialchars($card['name']); ?>"
                                    class="mx-auto h-60 object-contain">
                            </a>
                        </div>

                        <div class="p-4">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-bold text-lg truncate">
                                    <a href="card-details.php?id=<?php echo $card['id']; ?>" class="hover:text-red-600 transition">
                                        <?php echo htmlspecialchars($card['name']); ?>
                                    </a>
                                </h3>
                                <span class="condition-badge condition-<?php echo $card['condition']; ?>">
                                    <?php echo CARD_CONDITIONS[$card['condition']]; ?>
                                </span>
                            </div>

                            <div class="text-sm text-gray-500 mb-3">
                                <div>Série: <?php echo htmlspecialchars($card['series_name']); ?></div>
                                <div>N°: <?php echo htmlspecialchars($card['card_number']); ?></div>
                                <div>Rareté: <?php echo htmlspecialchars($card['rarity']); ?></div>
                            </div>

                            <div class="flex justify-between items-center">
                                <div class="font-bold text-xl text-red-600"><?php echo formatPrice($card['price']); ?></div>

                                <?php if ($card['quantity'] > 0): ?>
                                    <button data-card-id="<?php echo $card['id']; ?>" class="add-to-cart bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 transition">
                                        <i class="fas fa-shopping-cart mr-1"></i> Ajouter
                                    </button>
                                <?php else: ?>
                                    <span class="bg-gray-300 text-gray-600 py-2 px-4 rounded-md">
                                        Indisponible
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="mt-8 flex justify-center">
                    <div class="inline-flex rounded-md shadow-sm">
                        <?php if ($page > 1): ?>
                            <a href="<?php echo $paginationUrl . ($page - 1); ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-100">
                                Précédent
                            </a>
                        <?php endif; ?>

                        <?php
                        // Déterminer les pages à afficher
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);

                        // Afficher les liens de pagination
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <a href="<?php echo $paginationUrl . $i; ?>"
                                class="px-4 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-red-600 hover:bg-red-700' : 'text-gray-700 bg-white hover:bg-gray-100'; ?> border border-gray-300">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="<?php echo $paginationUrl . ($page + 1); ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-100">
                                Suivant
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>