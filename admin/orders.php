<?php
// admin/orders.php

// Définir le titre de la page
$pageTitle = 'Gestion des commandes';

// Inclure l'en-tête
require_once 'includes/header.php';

// Récupérer les paramètres de filtrage et de pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20; // Nombre de commandes par page
$offset = ($page - 1) * $perPage;

$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : null;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Récupérer les commandes en fonction des filtres
$conn = getDbConnection();

// Construire la requête
$query = "SELECT * FROM orders WHERE 1=1";
$params = [];

if ($status) {
    $query .= " AND status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $query .= " AND (customer_name LIKE ? OR customer_email LIKE ? OR id LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Compter le nombre total de commandes qui correspondent aux filtres
$countQuery = str_replace("*", "COUNT(*) as total", $query);
$countStmt = $conn->prepare($countQuery);
$countStmt->execute($params);
$totalOrders = $countStmt->fetch()['total'];
$totalPages = ceil($totalOrders / $perPage);

// Ajouter le tri et la pagination
$query .= " ORDER BY created_at DESC LIMIT ?, ?";
$params[] = (int)$offset;
$params[] = (int)$perPage;

$stmt = $conn->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Définir les couleurs et textes des statuts
$statusClasses = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'processing' => 'bg-blue-100 text-blue-800',
    'completed' => 'bg-green-100 text-green-800',
    'cancelled' => 'bg-red-100 text-red-800'
];

$statusText = [
    'pending' => 'En attente',
    'processing' => 'En traitement',
    'completed' => 'Complétée',
    'cancelled' => 'Annulée'
];

// Générer les paramètres d'URL pour la pagination
$paginationParams = $_GET;
unset($paginationParams['page']); // Supprimer le paramètre de page existant
$paginationUrl = '?' . http_build_query($paginationParams) . '&page=';
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-xl font-bold mb-4">Filtres et recherche</h2>

    <form action="orders.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Recherche -->
        <div>
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
            <input type="text" id="search" name="search"
                value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Nom, email, ID..."
                class="w-full p-2 border border-gray-300 rounded-md">
        </div>

        <!-- Filtre par statut -->
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
            <select id="status" name="status" class="w-full p-2 border border-gray-300 rounded-md">
                <option value="">Tous les statuts</option>
                <?php foreach ($statusText as $key => $value): ?>
                    <option value="<?php echo $key; ?>" <?php echo $status == $key ? 'selected' : ''; ?>>
                        <?php echo $value; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Boutons d'action -->
        <div class="flex items-end">
            <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition mr-2">
                <i class="fas fa-search mr-1"></i> Filtrer
            </button>
            <a href="orders.php" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300 transition">
                <i class="fas fa-redo mr-1"></i> Réinitialiser
            </a>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow-md">
    <div class="p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Liste des commandes</h2>
            <span class="text-gray-500"><?php echo $totalOrders; ?> commande<?php echo $totalOrders > 1 ? 's' : ''; ?> trouvée<?php echo $totalOrders > 1 ? 's' : ''; ?></span>
        </div>

        <?php if (empty($orders)): ?>
            <div class="text-center py-4">
                <p class="text-gray-500">Aucune commande ne correspond à vos critères.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                            <tr class="<?php echo $order['status'] == 'pending' ? 'bg-yellow-50' : ($order['status'] == 'processing' ? 'bg-blue-50' : ''); ?>">
                                <td class="px-4 py-2 whitespace-nowrap">#<?php echo $order['id']; ?></td>
                                <td class="px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap font-medium"><?php echo formatPrice($order['total_amount']); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClasses[$order['status']]; ?>">
                                        <?php echo $statusText[$order['status']]; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex space-x-2">
                                        <a href="order-details.php?id=<?php echo $order['id']; ?>" class="action-button view" title="Voir">
                                            <i class="fas fa-eye"></i>
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