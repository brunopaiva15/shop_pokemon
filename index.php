<?php
// index.php

// Inclure le fichier de fonctions explicitement
require_once 'includes/functions.php';

session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: auth.php');
    exit;
}

// Définir le titre de la page
$pageTitle = 'Accueil';

// Inclure les filtres
$includeFiltersScript = true;

// Récupérer les paramètres de filtrage et de pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12; // Nombre de cartes par page
$offset = ($page - 1) * $perPage;

$seriesId = isset($_GET['series']) ? (int)$_GET['series'] : null;
$condition = isset($_GET['condition']) ? $_GET['condition'] : null;
$rarity = isset($_GET['rarity']) ? $_GET['rarity'] : null;
$variant = isset($_GET['variant']) ? $_GET['variant'] : null;
$priceMin = isset($_GET['price_min']) && is_numeric($_GET['price_min']) ? (float)$_GET['price_min'] : null;
$priceMax = isset($_GET['price_max']) && is_numeric($_GET['price_max']) ? (float)$_GET['price_max'] : null;

// Déterminer le tri
$sortOptions = [
    'number_asc'  => ['card_number', 'ASC'],   // ← nouveau
    'number_desc' => ['card_number', 'DESC'],  // ← nouveau
    'newest'      => ['created_at',   'DESC'],
    'oldest'      => ['created_at',   'ASC'],
    'price_low'   => ['price',        'ASC'],
    'price_high'  => ['price',        'DESC'],
    'name_asc'    => ['name',         'ASC'],
    'name_desc'   => ['name',         'DESC']
];

// Définir 'number_asc' comme tri par défaut
$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortOptions)
    ? $_GET['sort']
    : 'number_asc';

list($sortBy, $sortOrder) = $sortOptions[$sort];

// Inclure l'en-tête
require_once 'includes/header.php';

// Récupérer toutes les séries pour les filtres
$allSeries = getSeriesWithCards();

// Récupérer la série actuelle si elle est spécifiée
$currentSeries = $seriesId ? getSeriesById($seriesId) : null;
if ($currentSeries) {
    $pageTitle = 'Série : ' . htmlspecialchars($currentSeries['name']);
}

// Récupérer les cartes filtrées
$cards = getAllCards($perPage, $offset, $seriesId, $condition, $sortBy, $sortOrder);

// Filtrer par rareté si spécifiée
if ($rarity) {
    $filteredCards = [];
    foreach ($cards as $card) {
        if ($card['rarity'] === $rarity) {
            $filteredCards[] = $card;
        }
    }
    $cards = $filteredCards;
}

// Filtrer par variante si spécifiée
if ($variant) {
    $filteredCards = [];
    foreach ($cards as $card) {
        if ($card['variant'] === $variant) {
            $filteredCards[] = $card;
        }
    }
    $cards = $filteredCards;
}

// Filtrer par prix si spécifié
if ($priceMin !== null || $priceMax !== null) {
    $filteredCards = [];
    foreach ($cards as $card) {
        $price = (float)$card['price'];
        $priceOk = true;

        if ($priceMin !== null && $price < $priceMin) {
            $priceOk = false;
        }

        if ($priceMax !== null && $price > $priceMax) {
            $priceOk = false;
        }

        if ($priceOk) {
            $filteredCards[] = $card;
        }
    }
    $cards = $filteredCards;
}

// Compter le nombre total de cartes qui correspondent aux filtres
$totalCards = count($cards);

// Paginer les résultats
$cards = array_slice($cards, $offset, $perPage);

$totalPages = ceil($totalCards / $perPage);

// Générer les paramètres d'URL pour la pagination
$paginationParams = $_GET;
unset($paginationParams['page']); // Supprimer le paramètre de page existant
$paginationUrl = '?' . http_build_query($paginationParams) . '&page=';
?>

<!-- Bandeau pour dire que toutes les cartes sont livrées avec un sleeve -->
<div class="bg-yellow-100 text-yellow-800 p-4 rounded-lg mb-6">
    <i class="fas fa-info-circle mr-2"></i>
    Toutes les cartes sont livrées dans une sleeve de protection ! Pour les cartes de plus de 2.00 CHF, un toploader est également inclus.
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <!-- Sidebar avec filtres -->
    <div id="filter-sidebar" class="md:col-span-1 hidden md:block">
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
            <h3 class="filter-title">Filtrer par rareté</h3>
            <div class="filter-content">
                <select id="rarity-filter" class="w-full p-2 border border-gray-300 rounded-md">
                    <option value="">Toutes les raretés</option>
                    <?php foreach (CARD_RARITIES as $code => $name): ?>
                        <option value="<?php echo $code; ?>" <?php echo $rarity == $code ? 'selected' : ''; ?>>
                            <?php echo $name; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="filter-container mb-6">
            <h3 class="filter-title">Filtrer par variante</h3>
            <div class="filter-content">
                <select id="variant-filter" class="w-full p-2 border border-gray-300 rounded-md">
                    <option value="">Toutes les variantes</option>
                    <?php foreach (CARD_VARIANTS as $code => $name): ?>
                        <option value="<?php echo $code; ?>" <?php echo isset($_GET['variant']) && $_GET['variant'] == $code ? 'selected' : ''; ?>>
                            <?php echo $name; ?>
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
                    <option value="number_asc" <?= $sort == 'number_asc'  ? 'selected' : '' ?>>N° croissant</option>
                    <option value="number_desc" <?= $sort == 'number_desc' ? 'selected' : '' ?>>N° décroissant</option>
                    <option value="newest" <?= $sort == 'newest'      ? 'selected' : '' ?>>Plus récent</option>
                    <option value="oldest" <?= $sort == 'oldest'      ? 'selected' : '' ?>>Plus ancien</option>
                    <option value="price_low" <?= $sort == 'price_low'   ? 'selected' : '' ?>>Prix croissant</option>
                    <option value="price_high" <?= $sort == 'price_high'  ? 'selected' : '' ?>>Prix décroissant</option>
                    <option value="name_asc" <?= $sort == 'name_asc'    ? 'selected' : '' ?>>Nom (A-Z)</option>
                    <option value="name_desc" <?= $sort == 'name_desc'   ? 'selected' : '' ?>>Nom (Z-A)</option>
                </select>
            </div>
        </div>

        <div class="space-y-2">
            <button id="apply-filters" class="w-full bg-gray-800 text-white py-2 px-4 rounded-md hover:bg-gray-900 transition">
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
                <?php if ($currentSeries): ?>
                    Série : <?php echo htmlspecialchars($currentSeries['name']); ?>
                <?php else: ?>
                    Toutes les cartes
                <?php endif; ?>
                <span class="text-sm font-normal text-gray-500">(<?php echo $totalCards; ?> cartes)</span>
            </h2>

            <button id="mobile-filter-toggle" class="md:hidden bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300 transition">
                <i class="fas fa-filter mr-1"></i> Filtres
            </button>
        </div>

        <?php if (empty($cards)): ?>
            <!-- Aucune carte trouvée -->
            <div class="bg-white p-8 rounded-lg shadow-md text-center">
                <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-2xl font-bold mb-2">Aucune carte trouvée</h3>
                <p class="text-gray-600 mb-4">Aucune carte ne correspond à vos critères de recherche.</p>
                <a href="index.php" class="bg-gray-800 text-white px-6 py-2 rounded-lg hover:bg-gray-900 transition">
                    Voir toutes les cartes
                </a>
            </div>
        <?php else: ?>
            <!-- Grille de cartes -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($cards as $card): ?>
                    <div class="card-item bg-white rounded-lg shadow-md overflow-hidden card-hover">
                        <div class="card-image-zoom p-4 bg-gray-100 relative">
                            <?php
                            $createdAt = strtotime($card['created_at']);
                            $twoWeeksAgo = strtotime('-14 days');
                            if ($createdAt !== false && $createdAt > $twoWeeksAgo):
                            ?>
                                <div class="absolute top-2 left-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full shadow">
                                    Nouveau
                                </div>
                            <?php endif; ?>

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
                                <span class="condition-badge condition-<?php echo $card['card_condition']; ?>">
                                    <?php echo CARD_CONDITIONS[$card['card_condition']]; ?>
                                </span>
                            </div>

                            <div class="text-sm text-gray-500 mb-3">
                                <div>Série: <?php echo htmlspecialchars($card['series_name']); ?></div>
                                <div>N°: <?php echo htmlspecialchars($card['card_number']); ?></div>
                                <div>Rareté: <?php echo isset(CARD_RARITIES[$card['rarity']]) ? CARD_RARITIES[$card['rarity']] : htmlspecialchars($card['rarity']); ?></div>
                                <div>Variante: <?php echo isset(CARD_VARIANTS[$card['variant']]) ? CARD_VARIANTS[$card['variant']] : htmlspecialchars($card['variant']); ?></div>
                            </div>

                            <div class="flex justify-between items-center">
                                <div class="font-bold text-xl text-red-600"><?php echo formatPrice($card['price']); ?></div>
                                <button data-card-id="<?php echo $card['id']; ?>" class="add-to-cart bg-gray-800 text-white py-2 px-4 rounded-md hover:bg-gray-900 transition">
                                    <i class="fas fa-shopping-cart mr-1"></i> Ajouter
                                </button>
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
                                class="px-4 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-gray-800 hover:bg-gray-900' : 'text-gray-700 bg-white hover:bg-gray-100'; ?> border border-gray-300">
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

<script>
    // Mettre à jour le fichier JavaScript pour inclure le filtre de rareté
    document.addEventListener('DOMContentLoaded', function() {
        // Ajout du filtre de rareté
        const rarityFilter = document.getElementById('rarity-filter');

        // Mise à jour de la fonction applyFilters pour inclure le filtre de rareté
        window.applyFilters = function() {
            const params = new URLSearchParams(window.location.search);

            updateUrlParam(params, 'series', document.getElementById('series-filter').value);
            updateUrlParam(params, 'condition', document.getElementById('condition-filter').value);
            updateUrlParam(params, 'rarity', rarityFilter.value);
            updateUrlParam(params, 'sort', document.getElementById('sort-filter').value);
            updateUrlParam(params, 'price_min', document.getElementById('price-min').value);
            updateUrlParam(params, 'price_max', document.getElementById('price-max').value);

            // Conserver le paramètre de recherche s'il existe
            const searchQuery = params.get('q');
            if (searchQuery) {
                params.set('q', searchQuery);
            }

            // Rediriger vers la nouvelle URL
            window.location.href = window.location.pathname + '?' + params.toString();
        };

        // Mise à jour de la fonction resetFilters pour réinitialiser le filtre de rareté
        window.resetFilters = function() {
            document.getElementById('series-filter').value = '';
            document.getElementById('condition-filter').value = '';
            rarityFilter.value = '';
            document.getElementById('sort-filter').value = 'newest';
            document.getElementById('price-min').value = '';
            document.getElementById('price-max').value = '';

            // Conserver uniquement le paramètre de recherche s'il existe
            const params = new URLSearchParams(window.location.search);
            const searchQuery = params.get('q');

            if (searchQuery) {
                window.location.href = window.location.pathname + '?q=' + encodeURIComponent(searchQuery);
            } else {
                window.location.href = window.location.pathname;
            }
        };
    });
</script>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>