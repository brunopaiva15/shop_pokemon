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

// Récupérer le nombre total de cartes d'abord (sans pagination)
$totalCardsBeforeFilters = countAllCards($seriesId, $condition);

// Récupérer les cartes filtrées (sans limite pour appliquer les autres filtres)
$allFilteredCards = getAllCardsWithoutPagination($seriesId, $condition, $sortBy, $sortOrder);

// Appliquer les filtres supplémentaires
// Filtrer par rareté si spécifiée
if ($rarity) {
    $filteredCards = [];
    foreach ($allFilteredCards as $card) {
        if ($card['rarity'] === $rarity) {
            $filteredCards[] = $card;
        }
    }
    $allFilteredCards = $filteredCards;
}

// Filtrer par variante si spécifiée
if ($variant) {
    $filteredCards = [];
    foreach ($allFilteredCards as $card) {
        if ($card['variant'] === $variant) {
            $filteredCards[] = $card;
        }
    }
    $allFilteredCards = $filteredCards;
}

// Filtrer par prix si spécifié
if ($priceMin !== null || $priceMax !== null) {
    $filteredCards = [];
    foreach ($allFilteredCards as $card) {
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
    $allFilteredCards = $filteredCards;
}

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
unset($card); // Libérer la référence

$totalPages = ceil($totalCards / $perPage);

// Générer les paramètres d'URL pour la pagination
$paginationParams = $_GET;
unset($paginationParams['page']); // Supprimer le paramètre de page existant
$paginationUrl = '?' . http_build_query($paginationParams) . '&page=';
?>

<!-- Bandeau pour dire que toutes les cartes sont livrées avec un sleeve -->
<div class="bg-yellow-100 text-yellow-800 p-4 rounded-lg mb-6">
    <div class="flex flex-wrap justify-between items-center">
        <div>
            <i class="fas fa-info-circle mr-2"></i>
            Toutes les cartes sont livrées dans une sleeve de protection ! Pour les cartes de plus de 2.00 CHF, un toploader est également inclus.
        </div>
        <button id="show-condition-guide" class="mt-2 sm:mt-0 text-blue-600 hover:text-blue-800 underline">
            <i class="fas fa-question-circle mr-1"></i> Guide des états de cartes (<small>MT, NM...</small>)
        </button>
    </div>
</div>

<!-- Modal pour afficher le guide des états des cartes -->
<div id="condition-guide-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg p-4 max-w-3xl mx-4 relative">
        <button id="close-condition-guide" class="absolute top-2 right-2 text-gray-600 hover:text-gray-900">
            <i class="fas fa-times text-xl"></i>
        </button>
        <h3 class="text-xl font-bold mb-4">Guide des états des cartes</h3>
        <img src="assets/images/Card_Condition_Table_FR.png" alt="Guide des états des cartes" class="w-full">
    </div>
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

                                <?php if (count($card['available_conditions']) > 1): ?>
                                    <span class="condition-badge condition-multiple">
                                        Multiple
                                    </span>
                                <?php elseif (isset($card['card_condition']) && !empty($card['card_condition'])): ?>
                                    <span class="condition-badge condition-<?php echo $card['card_condition']; ?>">
                                        <?php echo CARD_CONDITIONS[$card['card_condition']]; ?>
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
                                    <?php if (count($card['available_conditions']) > 1): ?>
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
    // Mettre à jour le fichier JavaScript pour inclure le filtre de rareté
    document.addEventListener('DOMContentLoaded', function() {
        // Ajout du filtre de rareté
        const rarityFilter = document.getElementById('rarity-filter');
        const variantFilter = document.getElementById('variant-filter');

        // Mise à jour de la fonction applyFilters pour inclure le filtre de rareté
        window.applyFilters = function() {
            const params = new URLSearchParams(window.location.search);

            updateUrlParam(params, 'series', document.getElementById('series-filter').value);
            updateUrlParam(params, 'condition', document.getElementById('condition-filter').value);
            updateUrlParam(params, 'rarity', rarityFilter.value);
            updateUrlParam(params, 'variant', variantFilter.value);
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
            variantFilter.value = '';
            document.getElementById('sort-filter').value = 'number_asc';
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
        const filterSidebar = document.getElementById('filter-sidebar');

        if (mobileFilterToggle && filterSidebar) {
            mobileFilterToggle.addEventListener('click', function() {
                filterSidebar.classList.toggle('hidden');
                mobileFilterToggle.innerHTML = filterSidebar.classList.contains('hidden') ?
                    '<i class="fas fa-filter mr-1"></i> Filtres' :
                    '<i class="fas fa-times mr-1"></i> Masquer';
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