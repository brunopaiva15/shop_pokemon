<?php
// index.php
session_start();

require_once 'includes/functions.php';

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: auth.php');
    exit;
}

$pageTitle = 'Accueil';

$searchTerm = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
if (!empty($searchTerm)) {
    $pageTitle = 'Recherche: ' . htmlspecialchars($searchTerm);
}

$includeFiltersScript = true;

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Filtres (multi)
$seriesIds   = isset($_GET['serie']) ? array_map('intval', (array)$_GET['serie']) : [];
$rarities    = isset($_GET['rarity']) ? array_map('sanitizeInput', (array)$_GET['rarity']) : [];
$variants    = isset($_GET['variant']) ? array_map('sanitizeInput', (array)$_GET['variant']) : [];
$conditions  = isset($_GET['condition']) ? array_map('sanitizeInput', (array)$_GET['condition']) : [];

$priceMin = isset($_GET['price_min']) && is_numeric($_GET['price_min']) ? (float)$_GET['price_min'] : null;
$priceMax = isset($_GET['price_max']) && is_numeric($_GET['price_max']) ? (float)$_GET['price_max'] : null;

// Tri
$sortOptions = [
    'number_asc'  => ['card_number', 'ASC'],
    'number_desc' => ['card_number', 'DESC'],
    'newest'      => ['created_at',   'DESC'],
    'oldest'      => ['created_at',   'ASC'],
    'price_low'   => ['price',        'ASC'],
    'price_high'  => ['price',        'DESC'],
    'name_asc'    => ['name',         'ASC'],
    'name_desc'   => ['name',         'DESC']
];
$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortOptions) ? $_GET['sort'] : 'number_asc';
list($sortBy, $sortOrder) = $sortOptions[$sort];

// Header
require_once 'includes/header.php';

// Séries disponibles
$allSeries = getSeriesWithCards();

// Requête principale
function getAllFilteredCards($searchTerm, $seriesIds, $conditions, $rarities, $variants, $priceMin, $priceMax, $sortBy, $sortOrder)
{
    $conn = getDbConnection();
    $query = "
        SELECT c.*, s.name as series_name, MIN(cc.price) as price
        FROM cards c
        LEFT JOIN series s ON c.series_id = s.id
        JOIN card_conditions cc ON c.id = cc.card_id
        WHERE cc.quantity > 0";

    $params = [];

    // Recherche textuelle
    if (!empty($searchTerm)) {
        $query .= " AND (c.name LIKE ? OR c.card_number LIKE ? OR c.description LIKE ? OR s.name LIKE ?)";
        $searchParam = '%' . $searchTerm . '%';
        $params = array_merge($params, array_fill(0, 4, $searchParam));
    }

    // Filtres multiples
    if (!empty($seriesIds)) {
        $query .= " AND c.series_id IN (" . implode(',', array_fill(0, count($seriesIds), '?')) . ")";
        $params = array_merge($params, $seriesIds);
    }

    if (!empty($rarities)) {
        $query .= " AND c.rarity IN (" . implode(',', array_fill(0, count($rarities), '?')) . ")";
        $params = array_merge($params, $rarities);
    }

    if (!empty($variants)) {
        $query .= " AND c.variant IN (" . implode(',', array_fill(0, count($variants), '?')) . ")";
        $params = array_merge($params, $variants);
    }

    if (!empty($conditions)) {
        $query .= " AND cc.condition_code IN (" . implode(',', array_fill(0, count($conditions), '?')) . ")";
        $params = array_merge($params, $conditions);
    }

    // Groupement
    $query .= " GROUP BY c.id";

    // Filtres de prix
    if ($priceMin !== null) {
        $query .= " HAVING MIN(cc.price) >= ?";
        $params[] = $priceMin;
    }

    if ($priceMax !== null) {
        $query .= $priceMin !== null
            ? " AND MIN(cc.price) <= ?"
            : " HAVING MIN(cc.price) <= ?";
        $params[] = $priceMax;
    }

    // Tri
    $query .= $sortBy === 'price'
        ? " ORDER BY price $sortOrder"
        : " ORDER BY c.$sortBy $sortOrder";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Récupérer les cartes filtrées
$allFilteredCards = getAllFilteredCards($searchTerm, $seriesIds, $conditions, $rarities, $variants, $priceMin, $priceMax, $sortBy, $sortOrder);

// Pagination
$totalCards = count($allFilteredCards);
$cards = array_slice($allFilteredCards, $offset, $perPage);
$totalPages = ceil($totalCards / $perPage);

// Conditions disponibles pour chaque carte
$conn = getDbConnection();
foreach ($cards as &$card) {
    $stmt = $conn->prepare("
        SELECT * FROM card_conditions
        WHERE card_id = ? AND quantity > 0
        ORDER BY price ASC
    ");
    $stmt->execute([$card['id']]);
    $card['available_conditions'] = $stmt->fetchAll();
}
unset($card);

// Pagination URL
$paginationParams = $_GET;
unset($paginationParams['page']);
$paginationUrl = '?' . http_build_query($paginationParams) . '&page=';
?>

<!-- Titre de la page et bandeau d'information -->
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-4">
        <h1 class="text-3xl font-extrabold text-gray-700 drop-shadow-lg tracking-tight">
            Accueil
        </h1>
        <div class="flex flex-wrap gap-2 mt-2 sm:mt-0">
            <span class="inline-flex items-center px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">
                <i class="fas fa-shield-alt mr-1"></i> Cartes 100% officielles
            </span>
            <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                <i class="fas fa-truck mr-1"></i> Livraison rapide & protégée
            </span>
            <span class="inline-flex items-center px-3 py-1 bg-purple-100 text-purple-800 text-xs font-semibold rounded-full">
                <i class="fas fa-star mr-1"></i> Nouveautés chaque mois
            </span>
        </div>
    </div>

    <!-- Bandeau de protection garantie -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border-2 border-blue-500 p-4">
        <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-500 text-white shadow-sm">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-semibold text-gray-800 text-base">Protection garantie</span>
                    <span class="inline-flex items-center px-2 py-0.5 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">
                        Inclus
                    </span>
                </div>
                <span class="text-gray-600 text-sm">
                    Toutes les cartes sont livrées dans une <span class="font-semibold">sleeve</span> de protection.
                    <span class="font-semibold">Toploader</span> inclus pour les cartes de plus de 2.00&nbsp;CHF.
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Modal guide des états -->
<div id="condition-guide-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg p-4 max-w-3xl mx-4 relative">
        <button id="close-condition-guide" class="absolute top-2 right-2 text-gray-600 hover:text-gray-900">
            <i class="fas fa-times text-xl"></i>
        </button>
        <h3 class="text-xl font-bold mb-4">Guide des états des cartes</h3>
        <img src="assets/images/Card_Condition_Table_FR.png" alt="Guide des états des cartes" class="w-full">
    </div>
</div>

<!-- Message de recherche -->
<?php if (!empty($searchTerm)): ?>
    <div class="bg-white p-4 rounded-lg shadow-md mb-6">
        <p class="text-gray-600">
            <span class="font-semibold"><?php echo $totalCards; ?></span> résultat<?php echo $totalCards > 1 ? 's' : ''; ?> pour la recherche "<span class="font-semibold"><?php echo htmlspecialchars($searchTerm); ?></span>"
        </p>
    </div>
<?php endif; ?>

<!-- Bouton pour afficher les filtres sur mobile -->
<div class="lg:hidden mb-4">
    <button id="mobile-filter-toggle" class="w-full bg-blue-600 text-white px-4 py-3 rounded-md shadow-md hover:bg-blue-700 transition flex items-center justify-center font-semibold">
        <i class="fas fa-filter mr-2"></i> Afficher les filtres
    </button>
</div>

<!-- Filtres -->
<div id="filter-section" class="w-full bg-white px-6 py-6 rounded-md shadow-md mb-8 border border-gray-200 hidden lg:block">
    <form id="filter-form" method="GET" action="index.php" class="flex flex-wrap gap-4 items-start">

        <!-- SÉRIE -->
        <div class="relative w-full sm:w-52">
            <label class="block text-sm font-medium text-gray-700 mb-1">Série</label>
            <button type="button" class="dropdown-toggle flex justify-between items-center w-full px-4 py-2 border border-gray-300 bg-white rounded-md shadow-sm text-sm font-medium text-gray-700 hover:border-gray-400">
                <span>Sélectionner</span>
                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div class="dropdown-menu absolute hidden bg-white mt-1 w-full border border-gray-300 rounded-md shadow-lg z-50 flex flex-col max-h-64">
                <div class="overflow-y-auto flex-grow p-2 space-y-1">
                    <?php foreach ($allSeries as $series): ?>
                        <label class="flex items-center space-x-2 px-2 py-2 hover:bg-gray-100 rounded text-sm">
                            <input type="checkbox" name="serie[]" value="<?= $series['id']; ?>"
                                <?= isset($_GET['serie']) && in_array($series['id'], (array)$_GET['serie']) ? 'checked' : '' ?>
                                class="h-5 w-5 text-blue-600 border-gray-300 rounded">
                            <span class="text-gray-800"><?= htmlspecialchars($series['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="border-t px-2 py-3 bg-white sticky bottom-0">
                    <button type="submit" class="w-full text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-md transition">
                        Montrer
                    </button>
                </div>
            </div>
        </div>

        <!-- Rareté -->
        <div class="relative w-full sm:w-52">
            <label class="block text-sm font-medium text-gray-700 mb-1">Rareté</label>
            <button type="button" class="dropdown-toggle flex justify-between items-center w-full px-4 py-2 border border-gray-300 bg-white rounded-md shadow-sm text-sm font-medium text-gray-700 hover:border-gray-400">
                <span>Sélectionner</span>
                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div class="dropdown-menu absolute hidden bg-white mt-1 w-full border border-gray-300 rounded-md shadow-lg z-50 flex flex-col max-h-64">
                <div class="overflow-y-auto flex-grow p-2 space-y-1">
                    <?php foreach (CARD_RARITIES as $code => $name): ?>
                        <label class="flex items-center space-x-2 px-2 py-2 hover:bg-gray-100 rounded text-sm">
                            <input type="checkbox" name="rarity[]" value="<?= $code; ?>"
                                <?= isset($_GET['rarity']) && in_array($code, (array)$_GET['rarity']) ? 'checked' : '' ?>
                                class="h-5 w-5 text-blue-600 border-gray-300 rounded">
                            <span class="text-gray-800"><?= $name; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="border-t px-2 py-3 bg-white sticky bottom-0">
                    <button type="submit" class="w-full text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-md transition">
                        Montrer
                    </button>
                </div>
            </div>
        </div>

        <!-- Variante -->
        <div class="relative w-full sm:w-52">
            <label class="block text-sm font-medium text-gray-700 mb-1">Variante</label>
            <button type="button" class="dropdown-toggle flex justify-between items-center w-full px-4 py-2 border border-gray-300 bg-white rounded-md shadow-sm text-sm font-medium text-gray-700 hover:border-gray-400">
                <span>Sélectionner</span>
                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div class="dropdown-menu absolute hidden bg-white mt-1 w-full border border-gray-300 rounded-md shadow-lg z-50 flex flex-col max-h-64">
                <div class="overflow-y-auto flex-grow p-2 space-y-1">
                    <?php foreach (CARD_VARIANTS as $code => $name): ?>
                        <label class="flex items-center space-x-2 px-2 py-2 hover:bg-gray-100 rounded text-sm">
                            <input type="checkbox" name="variant[]" value="<?= $code; ?>"
                                <?= isset($_GET['variant']) && in_array($code, (array)$_GET['variant']) ? 'checked' : '' ?>
                                class="h-5 w-5 text-blue-600 border-gray-300 rounded">
                            <span class="text-gray-800"><?= $name; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="border-t px-2 py-3 bg-white sticky bottom-0">
                    <button type="submit" class="w-full text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-md transition">
                        Montrer
                    </button>
                </div>
            </div>
        </div>

        <!-- État -->
        <div class="relative w-full sm:w-52">
            <label class="block text-sm font-medium text-gray-700 mb-1 flex items-center">
                État
                <button id="show-condition-guide" class="ml-2 text-blue-600 hover:text-blue-800 underline flex items-center text-xs">
                    <i class="fas fa-question-circle mr-1"></i> Aide
                </button>
            </label>
            <button type="button" class="dropdown-toggle flex justify-between items-center w-full px-4 py-2 border border-gray-300 bg-white rounded-md shadow-sm text-sm font-medium text-gray-700 hover:border-gray-400">
                <span>Sélectionner</span>
                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div class="dropdown-menu absolute hidden bg-white mt-1 w-full border border-gray-300 rounded-md shadow-lg z-50 flex flex-col max-h-64">
                <div class="overflow-y-auto flex-grow p-2 space-y-1">
                    <?php foreach (CARD_CONDITIONS as $code => $name): ?>
                        <label class="flex items-center space-x-2 px-2 py-2 hover:bg-gray-100 rounded text-sm">
                            <input type="checkbox" name="condition[]" value="<?= $code; ?>"
                                <?= isset($_GET['condition']) && in_array($code, (array)$_GET['condition']) ? 'checked' : '' ?>
                                class="h-5 w-5 text-blue-600 border-gray-300 rounded">
                            <span class="text-gray-800"><?= $name; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="border-t px-2 py-3 bg-white sticky bottom-0">
                    <button type="submit" class="w-full text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-md transition">
                        Montrer
                    </button>
                </div>
            </div>
        </div>

        <!-- Prix min -->
        <div class="flex flex-col w-full sm:w-40">
            <label for="price-min" class="text-sm font-medium text-gray-700 mb-1">Prix min (CHF)</label>
            <input type="number" min="0" step="0.01" placeholder="ex. 1.00" id="price-min" name="price_min"
                value="<?= $priceMin; ?>" class="p-2 border border-gray-300 rounded-md w-full">
        </div>

        <!-- Prix max -->
        <div class="flex flex-col w-full sm:w-40">
            <label for="price-max" class="text-sm font-medium text-gray-700 mb-1">Prix max (CHF)</label>
            <input type="number" min="0" step="0.01" placeholder="ex. 50.00" id="price-max" name="price_max"
                value="<?= $priceMax; ?>" class="p-2 border border-gray-300 rounded-md w-full">
        </div>

        <!-- Trier par -->
        <div class="flex flex-col w-full sm:w-48">
            <label for="sort-filter" class="text-sm font-medium text-gray-700 mb-1">Trier par</label>
            <select id="sort-filter" name="sort" class="p-2 border border-gray-300 rounded-md w-full">
                <option value="number_asc" <?= $sort == 'number_asc' ? 'selected' : ''; ?>>N° croissant</option>
                <option value="number_desc" <?= $sort == 'number_desc' ? 'selected' : ''; ?>>N° décroissant</option>
                <option value="newest" <?= $sort == 'newest' ? 'selected' : ''; ?>>Plus récent</option>
                <option value="oldest" <?= $sort == 'oldest' ? 'selected' : ''; ?>>Plus ancien</option>
                <option value="price_low" <?= $sort == 'price_low' ? 'selected' : ''; ?>>Prix croissant</option>
                <option value="price_high" <?= $sort == 'price_high' ? 'selected' : ''; ?>>Prix décroissant</option>
                <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : ''; ?>>Nom (A-Z)</option>
                <option value="name_desc" <?= $sort == 'name_desc' ? 'selected' : ''; ?>>Nom (Z-A)</option>
            </select>
        </div>
    </form>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between w-full mt-4 gap-2">
        <!-- Petit texte explicatif des filtres -->
        <div class="text-sm text-gray-500">
            <p>Utilisez les filtres ci-dessus pour affiner votre recherche.</p>
        </div>

        <!-- Bouton Réinitialiser -->
        <div class="flex-shrink-0">
            <button type="button" onclick="resetFilters()" class="reset-button px-6 py-2 rounded-md bg-red-600 text-white font-semibold shadow-md hover:bg-red-700 transition transform hover:scale-105">
                Réinitialiser les filtres
            </button>
        </div>
    </div>
</div>

<!-- Grille des cartes -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <?php foreach ($cards as $card): ?>
        <div class="card-item bg-white rounded-lg shadow-md overflow-hidden card-hover transition-transform transform hover:scale-105">
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
                        class="mx-auto h-60 object-contain transition-transform duration-200 hover:scale-105">
                </a>
            </div>

            <div class="p-4">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="font-bold text-lg truncate">
                        <a href="card-details.php?id=<?php echo $card['id']; ?>" class="hover:text-red-600 transition">
                            <?php echo htmlspecialchars($card['name']); ?>
                        </a>
                    </h3>
                </div>

                <div class="text-sm text-gray-500 mb-3">
                    <div>Série: <?php echo htmlspecialchars($card['series_name']); ?></div>
                    <div>N°: <?php echo htmlspecialchars($card['card_number']); ?></div>
                    <div>Rareté: <?php echo isset(CARD_RARITIES[$card['rarity']]) ? CARD_RARITIES[$card['rarity']] : htmlspecialchars($card['rarity']); ?></div>
                    <div>Variante: <?php echo isset(CARD_VARIANTS[$card['variant']]) ? CARD_VARIANTS[$card['variant']] : htmlspecialchars($card['variant']); ?></div>
                </div>

                <?php if (!empty($card['available_conditions'])): ?>
                    <div class="mb-3">
                        <p class="text-sm font-medium text-gray-700 mb-1">États disponibles:</p>
                        <div class="flex flex-wrap gap-1">
                            <?php foreach ($card['available_conditions'] as $condition): ?>
                                <div class="text-xs condition-badge condition-<?php echo $condition['condition_code']; ?>">
                                    <?php echo CARD_CONDITIONS[$condition['condition_code']]; ?>
                                    <span class="font-semibold"><?php echo formatPrice($condition['price']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="flex justify-between items-center">
                    <div class="font-bold text-xl text-red-600">
                        <?php if (count($card['available_conditions']) > 1): ?>
                            À partir de <?php echo formatPrice($card['price']); ?>
                        <?php else: ?>
                            <?php echo formatPrice($card['price']); ?>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($card['available_conditions']) && count($card['available_conditions']) === 1): ?>
                        <button
                            data-card-id="<?php echo $card['id']; ?>"
                            data-condition="<?php echo $card['available_conditions'][0]['condition_code']; ?>"
                            class="add-to-cart bg-gray-800 text-white py-2 px-4 rounded-md hover:bg-gray-900 transition">
                            <i class="fas fa-shopping-cart mr-1"></i> Ajouter
                        </button>
                    <?php elseif (!empty($card['available_conditions']) && count($card['available_conditions']) > 1): ?>
                        <a href="card-details.php?id=<?php echo $card['id']; ?>" class="bg-gray-800 text-white py-2 px-4 rounded-md hover:bg-gray-900 transition">
                            <i class="fas fa-eye mr-1"></i> Voir
                        </a>
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

<!-- Pagination (inchangée) -->
<?php if ($totalPages > 1): ?>
    <div class="mt-8 flex justify-center">
        <div class="inline-flex rounded-md shadow-sm">
            <?php if ($page > 1): ?>
                <a href="<?= $paginationUrl . ($page - 1); ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-100">Précédent</a>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="<?= $paginationUrl . $i; ?>" class="px-4 py-2 text-sm font-medium <?= $i == $page ? 'text-white bg-gray-800 hover:bg-gray-900' : 'text-gray-700 bg-white hover:bg-gray-100'; ?> border border-gray-300">
                    <?= $i; ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="<?= $paginationUrl . ($page + 1); ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-100">Suivant</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
    document.querySelectorAll('.dropdown-toggle').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = this.nextElementSibling;
            document.querySelectorAll('.dropdown-menu').forEach(m => {
                if (m !== menu) m.classList.add('hidden');
            });
            menu.classList.toggle('hidden');
        });
    });

    // Ne ferme pas quand on clique dans le menu
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.addEventListener('click', e => {
            e.stopPropagation();
        });
    });

    // Fermer tous les menus en cliquant ailleurs
    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.add('hidden');
        });
    });

    // Mettre à jour le fichier JavaScript pour inclure le filtre de rareté
    document.addEventListener('DOMContentLoaded', function() {

        const form = document.getElementById('filter-form');
        const inputs = form.querySelectorAll('select, input[type="number"]');

        inputs.forEach(input => {
            input.addEventListener('change', () => {
                form.submit();
            });
        });

        // Récupérer le terme de recherche de l'URL
        const urlParams = new URLSearchParams(window.location.search);
        const searchTerm = urlParams.get('q');

        // Ajout du filtre de rareté
        const rarityFilter = document.getElementById('rarity-filter');
        const variantFilter = document.getElementById('variant-filter');

        // Mise à jour de la fonction applyFilters pour inclure le filtre de rareté
        window.applyFilters = function() {
            const params = new URLSearchParams(window.location.search);

            // Conserver le terme de recherche
            if (searchTerm) {
                params.set('q', searchTerm);
            }

            updateUrlParam(params, 'series', document.getElementById('series-filter').value);
            updateUrlParam(params, 'condition', document.getElementById('condition-filter').value);
            updateUrlParam(params, 'rarity', rarityFilter.value);
            updateUrlParam(params, 'variant', variantFilter.value);
            updateUrlParam(params, 'sort', document.getElementById('sort-filter').value);
            updateUrlParam(params, 'price_min', document.getElementById('price-min').value);
            updateUrlParam(params, 'price_max', document.getElementById('price-max').value);

            // Rediriger vers la nouvelle URL
            window.location.href = window.location.pathname + '?' + params.toString();
        };

        // Mise à jour de la fonction resetFilters pour réinitialiser le filtre de rareté
        window.resetFilters = function() {
            // Si une recherche est présente, la garder
            const urlParams = new URLSearchParams(window.location.search);
            const searchTerm = urlParams.get('q');
            if (searchTerm) {
                window.location.href = window.location.pathname + '?q=' + encodeURIComponent(searchTerm);
            } else {
                window.location.href = window.location.pathname;
            }
        };

        // Fonction pour mettre à jour un paramètre d'URL
        function updateUrlParam(params, key, value) {
            if (value) {
                params.set(key, value);
            } else {
                params.delete(key);
            }
        }

        // Gestion de la fenêtre modale pour le guide des états des cartes
        const showButton = document.getElementById('show-condition-guide');
        const closeButton = document.getElementById('close-condition-guide');
        const modal = document.getElementById('condition-guide-modal');

        if (showButton && modal && closeButton) {
            showButton.addEventListener('click', function(e) {
                e.preventDefault();
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden'; // Empêcher le défilement du fond
            });

            closeButton.addEventListener('click', function() {
                modal.classList.add('hidden');
                document.body.style.overflow = ''; // Réactiver le défilement
            });

            // Fermer également en cliquant en dehors de l'image
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                    document.body.style.overflow = '';
                }
            });

            // Fermer avec la touche Echap
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    modal.classList.add('hidden');
                    document.body.style.overflow = '';
                }
            });
        }

        // Gestion du bouton mobile pour afficher/masquer les filtres
        const mobileFilterToggle = document.getElementById('mobile-filter-toggle');
        const filterSection = document.getElementById('filter-section');

        if (mobileFilterToggle && filterSection) {
            mobileFilterToggle.addEventListener('click', function() {
                const isHidden = filterSection.classList.contains('hidden');
                filterSection.classList.toggle('hidden');
                // Change le texte et l’icône du bouton
                mobileFilterToggle.innerHTML = isHidden ?
                    '<i class="fas fa-times mr-2"></i> Masquer les filtres' :
                    '<i class="fas fa-filter mr-2"></i> Afficher les filtres';
            });
        }
    });
</script>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>