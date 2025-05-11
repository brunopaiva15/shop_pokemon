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

// Construire la requête
$query = "SELECT c.*, s.name as series_name FROM cards c 
          LEFT JOIN series s ON c.series_id = s.id 
          WHERE 1=1";
$params = [];

if ($seriesId) {
    $query .= " AND c.series_id = ?";
    $params[] = $seriesId;
}

if ($condition) {
    $query .= " AND c.card_condition = ?";
    $params[] = $condition;
}

if (!empty($search)) {
    $query .= " AND (c.name LIKE ? OR c.card_number LIKE ? OR c.description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Compter le nombre total de cartes qui correspondent aux filtres
$countQuery = str_replace("c.*, s.name as series_name", "COUNT(*) as total", $query);
$countStmt = $conn->prepare($countQuery);
$countStmt->execute($params);
$totalCards = $countStmt->fetch()['total'];
$totalPages = ceil($totalCards / $perPage);

// Ajouter le tri et la pagination
$query .= " ORDER BY c.$sortBy $sortOrder LIMIT ?, ?";
$params[] = (int)$offset;
$params[] = (int)$perPage;

$stmt = $conn->prepare($query);
$stmt->execute($params);
$cards = $stmt->fetchAll();

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
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Série</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Numéro</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">État</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rareté</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variante</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($cards as $card): ?>
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="w-12 h-12 bg-gray-100 rounded-md overflow-hidden">
                                        <img src="<?php echo $card['image_url'] ?: '../assets/images/card-placeholder.png'; ?>"
                                            alt="<?php echo htmlspecialchars($card['name']); ?>"
                                            class="w-full h-full object-contain">
                                    </div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap font-medium"><?php echo htmlspecialchars($card['name']); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($card['series_name']); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($card['card_number']); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <span class="condition-badge condition-<?php echo $card['card_condition']; ?>">
                                        <?php echo CARD_CONDITIONS[$card['card_condition']]; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap"><?php echo isset(CARD_RARITIES[$card['rarity']]) ? CARD_RARITIES[$card['rarity']] : htmlspecialchars($card['rarity']); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap"><?php echo isset(CARD_VARIANTS[$card['variant']]) ? CARD_VARIANTS[$card['variant']] : htmlspecialchars($card['variant']); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap"><?php echo formatPrice($card['price']); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <span class="<?php echo $card['quantity'] == 0 ? 'text-red-600 font-bold' : ($card['quantity'] < 3 ? 'text-yellow-600 font-bold' : 'text-green-600'); ?>">
                                        <?php echo $card['quantity']; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
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

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>