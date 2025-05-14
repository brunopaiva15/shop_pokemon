<?php
// admin/cards.php

// Définir le titre de la page
$pageTitle = 'Gestion des cartes';

// Définir le bouton d'action
$actionButton = [
    'url' => 'add-card.php',
    'icon' => 'fas fa-plus',
    'text' => 'Ajouter une carte'
];

// Récupérer les paramètres de filtrage et de pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20; // Nombre de cartes par page
$offset = ($page - 1) * $perPage;

$seriesId = isset($_GET['series']) ? (int)$_GET['series'] : null;
$condition = isset($_GET['condition']) ? sanitizeInput($_GET['condition']) : null;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Déterminer le tri
$sortOptions = [
    'newest' => ['created_at', 'DESC'],
    'oldest' => ['created_at', 'ASC'],
    'price_low' => ['price', 'ASC'],
    'price_high' => ['price', 'DESC'],
    'name_asc' => ['name', 'ASC'],
    'name_desc' => ['name', 'DESC'],
    'stock_low' => ['quantity', 'ASC'],
    'stock_high' => ['quantity', 'DESC']
];

$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortOptions) ? $_GET['sort'] : 'newest';
list($sortBy, $sortOrder) = $sortOptions[$sort];

// Inclure l'en-tête
require_once 'includes/header.php';

// Récupérer toutes les séries pour les filtres
$allSeries = getAllSeries();

// Récupérer les cartes en fonction des filtres
$conn = getDbConnection();

// Construire la requête de base
$baseQuery = "
    SELECT DISTINCT c.id, c.name, c.card_number, c.rarity, c.variant, c.image_url, c.created_at, c.description, 
           s.name as series_name
    FROM cards c 
    LEFT JOIN series s ON c.series_id = s.id 
";

if ($condition) {
    $baseQuery .= "JOIN card_conditions cc ON c.id = cc.card_id AND cc.condition_code = ? ";
}

$baseQuery .= "WHERE 1=1 ";
$params = [];

if ($condition) {
    $params[] = $condition;
}

if ($seriesId) {
    $baseQuery .= "AND c.series_id = ? ";
    $params[] = $seriesId;
}

if (!empty($search)) {
    $baseQuery .= "AND (c.name LIKE ? OR c.card_number LIKE ? OR c.description LIKE ?) ";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Compter le nombre total de cartes qui correspondent aux filtres
$countQuery = "SELECT COUNT(DISTINCT c.id) as total FROM cards c LEFT JOIN series s ON c.series_id = s.id ";
if ($condition) {
    $countQuery .= "JOIN card_conditions cc ON c.id = cc.card_id AND cc.condition_code = ? ";
}
$countQuery .= "WHERE 1=1 ";

$countStmt = $conn->prepare($countQuery . substr($baseQuery, strpos($baseQuery, "WHERE 1=1") + 9));
$countStmt->execute($params);
$totalCards = $countStmt->fetch()['total'];
$totalPages = ceil($totalCards / $perPage);

// Ajouter le tri et la pagination à la requête principale
$mainQuery = $baseQuery;
// Ajuster le tri pour les colonnes qui sont maintenant dans card_conditions
if ($sortBy == 'price' || $sortBy == 'quantity') {
    $mainQuery = str_replace("LEFT JOIN series", "JOIN card_conditions ON c.id = card_conditions.card_id LEFT JOIN series", $mainQuery);
    $mainQuery .= "GROUP BY c.id ORDER BY MIN(card_conditions." . $sortBy . ") " . $sortOrder;
} else {
    $mainQuery .= "ORDER BY c." . $sortBy . " " . $sortOrder;
}

$mainQuery .= " LIMIT ?, ?";
$params[] = (int)$offset;
$params[] = (int)$perPage;

$stmt = $conn->prepare($mainQuery);
$stmt->execute($params);
$cards = $stmt->fetchAll();

// Pour chaque carte, récupérer ses conditions
foreach ($cards as &$card) {
    // Récupérer toutes les conditions disponibles
    $condQuery = "SELECT * FROM card_conditions WHERE card_id = ?";

    // Filtrer par état spécifique si demandé
    if ($condition) {
        $condQuery .= " AND condition_code = ?";
        $condParams = [$card['id'], $condition];
    } else {
        $condQuery .= " ORDER BY price ASC";
        $condParams = [$card['id']];
    }

    $condStmt = $conn->prepare($condQuery);
    $condStmt->execute($condParams);
    $cardConditions = $condStmt->fetchAll();

    // Compter le nombre d'états disponibles
    $conditionCount = count($cardConditions);

    if ($conditionCount > 0) {
        // Prendre le meilleur prix pour l'affichage
        $bestCondition = $cardConditions[0]; // Déjà trié par prix ASC

        $card['condition_code'] = $conditionCount > 1 ? 'multiple' : $bestCondition['condition_code'];
        $card['price'] = $bestCondition['price'];
        $card['quantity'] = array_sum(array_column($cardConditions, 'quantity'));
        $card['condition_count'] = $conditionCount;
        $card['conditions'] = $cardConditions;
    } else {
        $card['condition_code'] = "";
        $card['price'] = 0;
        $card['quantity'] = 0;
        $card['condition_count'] = 0;
        $card['conditions'] = [];
    }
}

// Générer les paramètres d'URL pour la pagination
$paginationParams = $_GET;
unset($paginationParams['page']); // Supprimer le paramètre de page existant
$paginationUrl = '?' . http_build_query($paginationParams) . '&page=';
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-xl font-bold mb-4">Filtres et recherche</h2>

    <form action="cards.php" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Recherche -->
        <div>
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
            <input type="text" id="search" name="search"
                value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Nom, numéro, description..."
                class="w-full p-2 border border-gray-300 rounded-md">
        </div>

        <!-- Filtre par série -->
        <div>
            <label for="series" class="block text-sm font-medium text-gray-700 mb-1">Série</label>
            <select id="series" name="series" class="w-full p-2 border border-gray-300 rounded-md">
                <option value="">Toutes les séries</option>
                <?php foreach ($allSeries as $series): ?>
                    <option value="<?php echo $series['id']; ?>" <?php echo $seriesId == $series['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($series['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filtre par état -->
        <div>
            <label for="condition" class="block text-sm font-medium text-gray-700 mb-1">État</label>
            <select id="condition" name="condition" class="w-full p-2 border border-gray-300 rounded-md">
                <option value="">Tous les états</option>
                <?php foreach (CARD_CONDITIONS as $code => $name): ?>
                    <option value="<?php echo $code; ?>" <?php echo $condition == $code ? 'selected' : ''; ?>>
                        <?php echo $name; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Tri -->
        <div>
            <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Trier par</label>
            <select id="sort" name="sort" class="w-full p-2 border border-gray-300 rounded-md">
                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Plus récent</option>
                <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Plus ancien</option>
                <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Prix croissant</option>
                <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Prix décroissant</option>
                <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Nom (A-Z)</option>
                <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Nom (Z-A)</option>
                <option value="stock_low" <?php echo $sort == 'stock_low' ? 'selected' : ''; ?>>Stock croissant</option>
                <option value="stock_high" <?php echo $sort == 'stock_high' ? 'selected' : ''; ?>>Stock décroissant</option>
            </select>
        </div>

        <!-- Boutons d'action -->
        <div class="md:col-span-4 flex justify-end">
            <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition mr-2">
                <i class="fas fa-search mr-1"></i> Filtrer
            </button>
            <a href="cards.php" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300 transition">
                <i class="fas fa-redo mr-1"></i> Réinitialiser
            </a>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow-md">
    <div class="p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Liste des cartes</h2>
            <span class="text-gray-500"><?php echo $totalCards; ?> carte<?php echo $totalCards > 1 ? 's' : ''; ?> trouvée<?php echo $totalCards > 1 ? 's' : ''; ?></span>
        </div>

        <?php if (empty($cards)): ?>
            <div class="text-center py-4">
                <p class="text-gray-500">Aucune carte ne correspond à vos critères.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto mb-4">
                <table class="min-w-full divide-y divide-gray-200 table-fixed">
                    <thead>
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">Image</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Nom</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Série</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">N°</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">État</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Rareté</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Variante</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Prix</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">Stock</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($cards as $card): ?>
                            <tr>
                                <td class="px-2 py-2">
                                    <div class="w-10 h-10 bg-gray-100 rounded-md overflow-hidden">
                                        <img src="<?php echo $card['image_url'] ?: '../assets/images/card-placeholder.png'; ?>"
                                            alt="<?php echo htmlspecialchars($card['name']); ?>"
                                            class="w-full h-full object-contain">
                                    </div>
                                </td>
                                <td class="px-2 py-2 text-sm font-medium truncate" title="<?php echo htmlspecialchars($card['name']); ?>">
                                    <?php echo htmlspecialchars($card['name']); ?>
                                </td>
                                <td class="px-2 py-2 text-sm truncate" title="<?php echo htmlspecialchars($card['series_name']); ?>">
                                    <?php echo htmlspecialchars($card['series_name']); ?>
                                </td>
                                <td class="px-2 py-2 text-sm"><?php echo htmlspecialchars($card['card_number']); ?></td>
                                <td class="px-2 py-2 text-sm">
                                    <?php if ($card['condition_count'] > 0): ?>
                                        <?php if ($card['condition_count'] > 1): ?>
                                            <span class="condition-badge condition-multiple text-xs">
                                                Multiple (<?php echo $card['condition_count']; ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="condition-badge condition-<?php echo $card['condition_code']; ?> text-xs">
                                                <?php echo CARD_CONDITIONS[$card['condition_code']]; ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">Non défini</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-2 py-2 text-sm truncate">
                                    <?php echo isset(CARD_RARITIES[$card['rarity']]) ? CARD_RARITIES[$card['rarity']] : htmlspecialchars($card['rarity']); ?>
                                </td>
                                <td class="px-2 py-2 text-sm truncate">
                                    <?php echo isset(CARD_VARIANTS[$card['variant']]) ? CARD_VARIANTS[$card['variant']] : htmlspecialchars($card['variant']); ?>
                                </td>
                                <td class="px-2 py-2 text-sm">
                                    <?php if ($card['condition_count'] > 1): ?>
                                        <span title="À partir de <?php echo formatPrice($card['price']); ?>">
                                            De <?php echo formatPrice($card['price']); ?>
                                        </span>
                                    <?php else: ?>
                                        <?php echo formatPrice($card['price']); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-2 py-2 text-sm">
                                    <span class="<?php echo $card['quantity'] == 0 ? 'text-red-600 font-bold' : ($card['quantity'] < 3 ? 'text-yellow-600 font-bold' : 'text-green-600'); ?>">
                                        <?php echo $card['quantity']; ?>
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-sm">
                                    <div class="flex space-x-2">
                                        <a href="../card-details.php?id=<?php echo $card['id']; ?>" target="_blank" class="action-button view" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit-card.php?id=<?php echo $card['id']; ?>" class="action-button edit" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete-card.php?id=<?php echo $card['id']; ?>" class="action-button delete delete-confirm" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="mt-6 flex justify-center">
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
                                class="px-4 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-blue-600 hover:bg-blue-700' : 'text-gray-700 bg-white hover:bg-gray-100'; ?> border border-gray-300">
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

    /* Pour gérer le texte long dans les cellules du tableau */
    .truncate {
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Réduire la taille du texte dans le tableau pour un meilleur ajustement */
    .text-sm {
        font-size: 0.875rem;
    }

    /* Réduire légèrement le padding des cellules */
    .px-2 {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
</style>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>