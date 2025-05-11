<?php
// admin/index.php

// Définir le titre de la page
$pageTitle = 'Tableau de bord';

// Inclure l'en-tête
require_once 'includes/header.php';

// Récupérer les statistiques
$conn = getDbConnection();

// Nombre total de cartes
$stmt = $conn->query("SELECT COUNT(*) as total FROM cards");
$totalCards = $stmt->fetch()['total'];

// Nombre total de cartes en stock
$stmt = $conn->query("SELECT COUNT(*) as total FROM cards WHERE quantity > 0");
$totalCardsInStock = $stmt->fetch()['total'];

// Nombre total de séries
$stmt = $conn->query("SELECT COUNT(*) as total FROM series");
$totalSeries = $stmt->fetch()['total'];

// Nombre total de commandes
$stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
$totalOrders = $stmt->fetch()['total'];

// Nombre de commandes en attente
$stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
$pendingOrders = $stmt->fetch()['total'];

// Montant total des ventes
$stmt = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'");
$totalSales = $stmt->fetch()['total'] ?: 0;

// Récupérer les dernières commandes
$stmt = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
$recentOrders = $stmt->fetchAll();

// Récupérer les dernières cartes ajoutées
$stmt = $conn->query("SELECT c.*, s.name as series_name FROM cards c 
                     LEFT JOIN series s ON c.series_id = s.id 
                     ORDER BY c.created_at DESC LIMIT 5");
$recentCards = $stmt->fetchAll();

// Cartes les plus vendues
$stmt = $conn->query("SELECT c.id, c.name, c.card_number, c.image_url, c.price, c.quantity, s.name as series_name, 
                     SUM(oi.quantity) as sold_quantity 
                     FROM order_items oi 
                     JOIN cards c ON oi.card_id = c.id 
                     LEFT JOIN series s ON c.series_id = s.id 
                     JOIN orders o ON oi.order_id = o.id 
                     WHERE o.status != 'cancelled' 
                     GROUP BY c.id 
                     ORDER BY sold_quantity DESC 
                     LIMIT 5");
$topSellingCards = $stmt->fetchAll();

// Cartes à faible stock (moins de 3 exemplaires)
$stmt = $conn->query("SELECT c.*, s.name as series_name 
                     FROM cards c 
                     LEFT JOIN series s ON c.series_id = s.id 
                     WHERE c.quantity > 0 AND c.quantity < 3 
                     ORDER BY c.quantity ASC 
                     LIMIT 5");
$lowStockCards = $stmt->fetchAll();
?>

<!-- Statistiques -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                <i class="fas fa-credit-card text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500">Total des cartes</p>
                <p class="text-2xl font-bold"><?php echo number_format($totalCards); ?></p>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-sm text-gray-500">En stock: <?php echo number_format($totalCardsInStock); ?></span>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-500 mr-4">
                <i class="fas fa-layer-group text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500">Séries</p>
                <p class="text-2xl font-bold"><?php echo number_format($totalSeries); ?></p>
            </div>
        </div>
        <div class="mt-4">
            <a href="series.php" class="text-sm text-blue-600 hover:underline">Gérer les séries</a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-500 mr-4">
                <i class="fas fa-shopping-cart text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500">Commandes</p>
                <p class="text-2xl font-bold"><?php echo number_format($totalOrders); ?></p>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-sm text-yellow-500"><?php echo $pendingOrders; ?> en attente</span>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                <i class="fas fa-euro-sign text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500">Ventes</p>
                <p class="text-2xl font-bold"><?php echo formatPrice($totalSales); ?></p>
            </div>
        </div>
        <div class="mt-4">
            <a href="orders.php" class="text-sm text-blue-600 hover:underline">Voir les commandes</a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Dernières commandes -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">Dernières commandes</h2>

        <?php if (empty($recentOrders)): ?>
            <p class="text-gray-500">Aucune commande trouvée.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recentOrders as $order):
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
                        ?>
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <a href="order-details.php?id=<?php echo $order['id']; ?>" class="text-blue-600 hover:underline">
                                        #<?php echo $order['id']; ?>
                                    </a>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap"><?php echo formatPrice($order['total_amount']); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClasses[$order['status']]; ?>">
                                        <?php echo $statusText[$order['status']]; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-center">
                <a href="orders.php" class="text-blue-600 hover:underline">Voir toutes les commandes</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Dernières cartes ajoutées -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">Cartes récemment ajoutées</h2>

        <?php if (empty($recentCards)): ?>
            <p class="text-gray-500">Aucune carte trouvée.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($recentCards as $card): ?>
                    <div class="flex items-center border-b border-gray-200 pb-4">
                        <div class="flex-shrink-0 w-16 h-16 bg-gray-100 rounded-md overflow-hidden">
                            <img src="<?php echo $card['image_url'] ?: '../assets/images/card-placeholder.png'; ?>"
                                alt="<?php echo htmlspecialchars($card['name']); ?>"
                                class="w-full h-full object-contain">
                        </div>
                        <div class="ml-4 flex-grow">
                            <h3 class="font-medium"><?php echo htmlspecialchars($card['name']); ?></h3>
                            <div class="text-sm text-gray-500">
                                Série: <?php echo htmlspecialchars($card['series_name']); ?> |
                                N°: <?php echo htmlspecialchars($card['card_number']); ?>
                            </div>
                            <div class="text-sm">
                                <span class="text-green-600 font-medium"><?php echo formatPrice($card['price']); ?></span> |
                                Stock: <?php echo $card['quantity']; ?>
                            </div>
                        </div>
                        <div>
                            <a href="edit-card.php?id=<?php echo $card['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-4 text-center">
                <a href="cards.php" class="text-blue-600 hover:underline">Gérer toutes les cartes</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
    <!-- Cartes les plus vendues -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">Cartes les plus vendues</h2>

        <?php if (empty($topSellingCards)): ?>
            <p class="text-gray-500">Aucune donnée de vente disponible.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($topSellingCards as $card): ?>
                    <div class="flex items-center border-b border-gray-200 pb-4">
                        <div class="flex-shrink-0 w-16 h-16 bg-gray-100 rounded-md overflow-hidden">
                            <img src="<?php echo $card['image_url'] ?: '../assets/images/card-placeholder.png'; ?>"
                                alt="<?php echo htmlspecialchars($card['name']); ?>"
                                class="w-full h-full object-contain">
                        </div>
                        <div class="ml-4 flex-grow">
                            <h3 class="font-medium"><?php echo htmlspecialchars($card['name']); ?></h3>
                            <div class="text-sm text-gray-500">
                                Série: <?php echo htmlspecialchars($card['series_name']); ?>
                            </div>
                            <div class="text-sm">
                                <span class="text-green-600 font-medium"><?php echo formatPrice($card['price']); ?></span> |
                                Vendus: <strong><?php echo $card['sold_quantity']; ?></strong>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cartes à faible stock -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">Cartes à faible stock</h2>

        <?php if (empty($lowStockCards)): ?>
            <p class="text-gray-500">Aucune carte à faible stock.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($lowStockCards as $card): ?>
                    <div class="flex items-center border-b border-gray-200 pb-4">
                        <div class="flex-shrink-0 w-16 h-16 bg-gray-100 rounded-md overflow-hidden">
                            <img src="<?php echo $card['image_url'] ?: '../assets/images/card-placeholder.png'; ?>"
                                alt="<?php echo htmlspecialchars($card['name']); ?>"
                                class="w-full h-full object-contain">
                        </div>
                        <div class="ml-4 flex-grow">
                            <h3 class="font-medium"><?php echo htmlspecialchars($card['name']); ?></h3>
                            <div class="text-sm text-gray-500">
                                Série: <?php echo htmlspecialchars($card['series_name']); ?>
                            </div>
                            <div class="text-sm">
                                <span class="text-green-600 font-medium"><?php echo formatPrice($card['price']); ?></span> |
                                <span class="text-red-600 font-bold">Stock: <?php echo $card['quantity']; ?></span>
                            </div>
                        </div>
                        <div>
                            <a href="edit-card.php?id=<?php echo $card['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>