<?php
// search.php

// Inclure le fichier de fonctions explicitement
require_once 'includes/functions.php';

session_start();

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: auth.php');
    exit;
}

// Récupérer le terme de recherche
$searchTerm = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';

if (empty($searchTerm)) {
    header('Location: index.php');
    exit;
}

// Définir le titre de la page
$pageTitle = 'Recherche: ' . htmlspecialchars($searchTerm);

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
    'number_asc'  => ['card_number', 'ASC'],
    'number_desc' => ['card_number', 'DESC'],
    'newest'      => ['created_at',   'DESC'],
    'oldest'      => ['created_at',   'ASC'],
    'price_low'   => ['price',        'ASC'],
    'price_high'  => ['price',        'DESC'],
    'name_asc'    => ['name',         'ASC'],
    'name_desc'   => ['name',         'DESC']
];

$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortOptions)
    ? $_GET['sort']
    : 'number_asc';

list($sortBy, $sortOrder) = $sortOptions[$sort];

// Inclure l'en-tête
require_once 'includes/header.php';

// Récupérer toutes les séries pour les filtres
$allSeries = getSeriesWithCards();

// Fonction pour rechercher les cartes selon les critères
function searchFilteredCards($searchTerm, $seriesId, $condition, $rarity, $variant, $priceMin, $priceMax, $sortBy, $sortOrder)
{
    $conn = getDbConnection();

    // Construire la requête de base
    $query = "
        SELECT c.*, s.name as series_name, MIN(cc.price) as price
        FROM cards c 
        LEFT JOIN series s ON c.series_id = s.id 
        JOIN card_conditions cc ON c.id = cc.card_id
        WHERE cc.quantity > 0
        AND (c.name LIKE ? OR c.card_number LIKE ? OR c.description LIKE ? OR s.name LIKE ?)";

    $params = ["%$searchTerm%", "%$searchTerm%", "%$searchTerm%", "%$searchTerm%"];

    // Ajouter les conditions de filtrage
    if ($seriesId) {
        $query .= " AND c.series_id = ?";
        $params[] = $seriesId;
    }

    if ($condition) {
        $query .= " AND cc.condition_code = ?";
        $params[] = $condition;
    }

    if ($rarity) {
        $query .= " AND c.rarity = ?";
        $params[] = $rarity;
    }

    if ($variant) {
        $query .= " AND c.variant = ?";
        $params[] = $variant;
    }

    // Regrouper par carte pour éviter les doublons
    $query .= " GROUP BY c.id";

    // Ajouter les filtres de prix après GROUP BY avec HAVING
    if ($priceMin !== null) {
        $query .= " HAVING MIN(cc.price) >= ?";
        $params[] = $priceMin;
    }

    if ($priceMax !== null) {
        if ($priceMin !== null) {
            $query .= " AND MIN(cc.price) <= ?";
        } else {
            $query .= " HAVING MIN(cc.price) <= ?";
        }
        $params[] = $priceMax;
    }

    // Ajouter le tri
    if ($sortBy == 'price') {
        // Pour le tri par prix, on utilise le prix minimum de chaque carte
        $query .= " ORDER BY price " . $sortOrder;
    } else {
        $query .= " ORDER BY c." . $sortBy . " " . $sortOrder;
    }

    // Exécuter la requête
    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

// Récupérer toutes les cartes filtrées en une seule requête
$allFilteredCards = searchFilteredCards($searchTerm, $seriesId, $condition, $rarity, $variant, $priceMin, $priceMax, $sortBy, $sortOrder);

// Compter le nombre total après tous les filtres
$totalCards = count($allFilteredCards);

// Paginer les résultats
$cards = array_slice($allFilteredCards, $offset, $perPage);

// Pour chaque carte, récupérer tous ses états disponibles
$conn = getDbConnection();
foreach ($cards as &$card) {
    // Récupérer tous les états disponibles pour cette carte
    $stmt = $conn->prepare("
        SELECT * FROM card_conditions 
        WHERE card_id = ? AND quantity > 0
        ORDER BY price ASC
    ");
    $stmt->execute([$card['id']]);
    $card['available_conditions'] = $stmt->fetchAll();
}
// Libérer la référence pour éviter les doublons
unset($card);

$totalPages = ceil($totalCards / $perPage);

// Générer les paramètres d'URL pour la pagination
$paginationParams = $_GET;
unset($paginationParams['page']); // Supprimer le paramètre de page existant
$paginationUrl = '?' . http_build_query($paginationParams) . '&page=';
?>

<!-- Résultats de recherche -->
<div class="bg-white p-4 rounded-lg shadow-md mb-6">
    <p class="text-gray-600">
        <span class="font-semibold"><?php echo $totalCards; ?></span> résultat<?php echo $totalCards > 1 ? 's' : ''; ?> pour la recherche "<span class="font-semibold"><?php echo htmlspecialchars($searchTerm); ?></span>"
    </p>
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
                Résultats de recherche
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

                                <?php if (isset($card['available_conditions']) && count($card['available_conditions']) > 1): ?>
                                    <span class="condition-badge condition-multiple">
                                        Multiple
                                    </span>
                                <?php elseif (isset($card['available_conditions']) && count($card['available_conditions']) == 1): ?>
                                    <span class="condition-badge condition-<?php echo $card['available_conditions'][0]['condition_code']; ?>">
                                        <?php echo CARD_CONDITIONS[$card['available_conditions'][0]['condition_code']]; ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="text-sm text-gray-500 mb-3">
                                <div>Série: <?php echo htmlspecialchars($card['series_name']); ?></div>
                                <div>N°: <?php echo htmlspecialchars($card['card_number']); ?></div>
                                <div>Rareté: <?php echo isset(CARD_RARITIES[$card['rarity']]) ? CARD_RARITIES[$card['rarity']] : htmlspecialchars($card['rarity']); ?></div>
                                <div>Variante: <?php echo isset(CARD_VARIANTS[$card['variant']]) ? CARD_VARIANTS[$card['variant']] : htmlspecialchars($card['variant']); ?></div>
                            </div>

                            <?php if (!empty($card['available_conditions'])): ?>
                                <!-- États disponibles -->
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
                                    <?php if (isset($card['available_conditions']) && count($card['available_conditions']) > 1): ?>
                                        À partir de <?php echo formatPrice($card['price']); ?>
                                    <?php else: ?>
                                        <?php echo formatPrice($card['price']); ?>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($card['available_conditions']) && count($card['available_conditions']) === 1): ?>
                                    <!-- Un seul état disponible -->
                                    <button
                                        data-card-id="<?php echo $card['id']; ?>"
                                        data-condition="<?php echo $card['available_conditions'][0]['condition_code']; ?>"
                                        class="add-to-cart bg-gray-800 text-white py-2 px-4 rounded-md hover:bg-gray-900 transition">
                                        <i class="fas fa-shopping-cart mr-1"></i> Ajouter
                                    </button>
                                <?php elseif (!empty($card['available_conditions']) && count($card['available_conditions']) > 1): ?>
                                    <!-- Plusieurs états disponibles -->
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

<style>
    .condition-badge.condition-multiple {
        background-color: #9333ea;
        color: white;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Récupérer le terme de recherche de l'URL
        const urlParams = new URLSearchParams(window.location.search);
        const searchTerm = urlParams.get('q');

        // Mise à jour de la fonction applyFilters pour inclure le terme de recherche
        window.applyFilters = function() {
            const params = new URLSearchParams(window.location.search);

            // Conserver le terme de recherche
            if (searchTerm) {
                params.set('q', searchTerm);
            }

            updateUrlParam(params, 'series', document.getElementById('series-filter').value);
            updateUrlParam(params, 'condition', document.getElementById('condition-filter').value);
            updateUrlParam(params, 'rarity', document.getElementById('rarity-filter').value);
            updateUrlParam(params, 'variant', document.getElementById('variant-filter').value);
            updateUrlParam(params, 'sort', document.getElementById('sort-filter').value);
            updateUrlParam(params, 'price_min', document.getElementById('price-min').value);
            updateUrlParam(params, 'price_max', document.getElementById('price-max').value);

            // Rediriger vers la nouvelle URL
            window.location.href = window.location.pathname + '?' + params.toString();
        };

        // Mise à jour de la fonction resetFilters pour revenir à la recherche simple
        window.resetFilters = function() {
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

        // Gestion du bouton mobile pour afficher/masquer les filtres
        const mobileFilterToggle = document.getElementById('mobile-filter-toggle');
        const filterSidebar = document.getElementById('filter-sidebar');

        if (mobileFilterToggle && filterSidebar) {
            mobileFilterToggle.addEventListener('click', function() {
                filterSidebar.classList.toggle('hidden');
                mobileFilterToggle.innerHTML = filterSidebar.classList.contains('hidden') ?
                    '<i class="fas fa-filter mr-1"></i> Filtres' :
                    '<i class="fas fa-times mr-1"></i> Masquer';
            });
        }

        // Appliquer les filtres lors du clic sur le bouton
        const applyFiltersBtn = document.getElementById('apply-filters');
        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', function() {
                applyFilters();
            });
        }

        // Réinitialiser les filtres lors du clic sur le bouton
        const resetFiltersBtn = document.getElementById('reset-filters');
        if (resetFiltersBtn) {
            resetFiltersBtn.addEventListener('click', function() {
                resetFilters();
            });
        }

        // Gestion des boutons d'ajout au panier
        const addToCartButtons = document.querySelectorAll('.add-to-cart');

        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                const cardId = this.dataset.cardId;
                const condition = this.dataset.condition;

                // Animation
                this.classList.add('add-to-cart-pulse');
                setTimeout(() => {
                    this.classList.remove('add-to-cart-pulse');
                }, 500);

                // Requête AJAX pour ajouter au panier
                fetch('cart-ajax.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=add&card_id=${cardId}&condition=${condition}&quantity=1`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Mettre à jour l'icône du panier
                            const cartCountElement = document.querySelector('.fa-shopping-cart')?.nextElementSibling;
                            if (cartCountElement) {
                                cartCountElement.textContent = data.cart_count;
                            } else {
                                const cartIcon = document.querySelector('.fa-shopping-cart');
                                if (cartIcon?.parentNode) {
                                    const countSpan = document.createElement('span');
                                    countSpan.className = 'absolute -top-2 -right-2 bg-yellow-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs';
                                    countSpan.textContent = data.cart_count;
                                    cartIcon.parentNode.appendChild(countSpan);
                                }
                            }

                            // Afficher une notification
                            showNotification('Carte ajoutée au panier !', 'success');
                        } else {
                            showNotification('Erreur: ' + data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Une erreur est survenue', 'error');
                    });
            });
        });

        // Fonction pour afficher des notifications
        function showNotification(message, type) {
            const existing = document.querySelector('.notification');
            if (existing) existing.remove();

            const notification = document.createElement('div');
            notification.className = `notification fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            } text-white`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${
                        type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'
                    } mr-2"></i>
                    <span>${message}</span>
                </div>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.classList.add('opacity-0');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    });
</script>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>