<?php
// admin/index.php

// Définir le titre de la page
$pageTitle = 'Tableau de bord';

// Inclure l'en-tête
require_once 'includes/header.php';

// Récupérer les statistiques
$conn = getDbConnection();

// Statistiques des ventes par mois
$stmt = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as total 
                      FROM orders 
                      WHERE status != 'cancelled' 
                      GROUP BY month 
                      ORDER BY month DESC 
                      LIMIT 6");
$salesByMonth = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

// Carte les plus chères en stock
$stmt = $conn->query("SELECT c.*, s.name as series_name, cc.price, cc.condition_code, cc.quantity
                      FROM cards c 
                      JOIN card_conditions cc ON c.id = cc.card_id 
                      LEFT JOIN series s ON c.series_id = s.id 
                      WHERE cc.quantity > 0 
                      ORDER BY cc.price DESC 
                      LIMIT 3");
$topExpensiveCards = $stmt->fetchAll();

// Nombre total de cartes
$stmt = $conn->query("SELECT COUNT(*) as total FROM cards");
$totalCards = $stmt->fetch()['total'];

// Nombre total de cartes en stock (avec au moins une condition avec quantité > 0)
$stmt = $conn->query("SELECT COUNT(DISTINCT c.id) as total 
                     FROM cards c 
                     JOIN card_conditions cc ON c.id = cc.card_id 
                     WHERE cc.quantity > 0");
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
$stmt = $conn->query("SELECT c.*, s.name as series_name 
                     FROM cards c 
                     LEFT JOIN series s ON c.series_id = s.id 
                     ORDER BY c.created_at DESC LIMIT 5");
$recentCards = $stmt->fetchAll();

// Pour chaque carte récente, récupérer sa meilleure condition
foreach ($recentCards as &$card) {
    $stmt = $conn->prepare("SELECT * FROM card_conditions 
                           WHERE card_id = ? AND quantity > 0
                           ORDER BY price ASC
                           LIMIT 1");
    $stmt->execute([$card['id']]);
    $bestCondition = $stmt->fetch();

    if ($bestCondition) {
        $card['price'] = $bestCondition['price'];
        $card['quantity'] = $bestCondition['quantity'];
        $card['condition_code'] = $bestCondition['condition_code'];
    } else {
        $card['price'] = 0;
        $card['quantity'] = 0;
        $card['condition_code'] = '';
    }
}

// Cartes les plus vendues - version compatible avant migration
$stmt = $conn->query("SELECT c.id, c.name, c.card_number, c.image_url, 
                     MIN(cc.price) as price, SUM(cc.quantity) as quantity,
                     s.name as series_name, 
                     SUM(oi.quantity) as sold_quantity 
                     FROM order_items oi 
                     JOIN cards c ON oi.card_id = c.id 
                     JOIN card_conditions cc ON c.id = cc.card_id
                     LEFT JOIN series s ON c.series_id = s.id 
                     JOIN orders o ON oi.order_id = o.id 
                     WHERE o.status != 'cancelled' 
                     GROUP BY c.id
                     ORDER BY sold_quantity DESC 
                     LIMIT 5");
$topSellingCards = $stmt->fetchAll();

// Pour chaque carte vendue, récupérer sa meilleure condition
foreach ($topSellingCards as &$card) {
    $stmt = $conn->prepare("SELECT * FROM card_conditions 
                           WHERE card_id = ? AND quantity > 0
                           ORDER BY price ASC
                           LIMIT 1");
    $stmt->execute([$card['id']]);
    $bestCondition = $stmt->fetch();

    if ($bestCondition) {
        $card['condition_code'] = $bestCondition['condition_code'];
    } else {
        $card['condition_code'] = '';
    }
}

// Cartes à faible stock (moins de 3 exemplaires)
$stmt = $conn->query("SELECT c.*, s.name as series_name, cc.condition_code, cc.price, cc.quantity
                     FROM cards c 
                     JOIN card_conditions cc ON c.id = cc.card_id
                     LEFT JOIN series s ON c.series_id = s.id 
                     WHERE cc.quantity > 0 AND cc.quantity < 3 
                     ORDER BY cc.quantity ASC 
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

<?php if (!empty($topExpensiveCards)): ?>
    <div class="bg-white rounded-lg shadow-md p-6 mt-8 mb-8">
        <h2 class="text-xl font-bold mb-4">Top 3 des cartes les plus chères en stock</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($topExpensiveCards as $index => $card): ?>
                <div class="relative bg-gray-50 border rounded-lg p-4 flex items-start shadow-sm">
                    <div class="absolute top-2 left-2 bg-yellow-500 text-white text-xs px-2 py-1 rounded-full shadow">
                        TOP <?php echo $index + 1; ?>
                    </div>
                    <div class="flex-shrink-0 w-20 h-20 bg-gray-100 rounded-md overflow-hidden mr-4">
                        <img src="<?php echo $card['image_url'] ?: '../assets/images/card-placeholder.png'; ?>"
                            alt="<?php echo htmlspecialchars($card['name']); ?>"
                            class="w-full h-full object-contain">
                    </div>
                    <div class="flex-grow">
                        <h3 class="text-base font-semibold"><?php echo htmlspecialchars($card['name']); ?></h3>
                        <p class="text-sm text-gray-600">
                            <?php echo htmlspecialchars($card['series_name']); ?> – État : <?php echo CARD_CONDITIONS[$card['condition_code']]; ?>
                        </p>
                        <p class="text-lg font-bold text-yellow-600 mt-1"><?php echo formatPrice($card['price']); ?></p>
                        <p class="text-sm text-gray-500">Stock : <?php echo $card['quantity']; ?></p>
                    </div>
                    <a href="edit-card.php?id=<?php echo $card['id']; ?>" class="ml-4 text-blue-600 hover:text-blue-800">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-2 gap-8">
    <!-- Dernières commandes -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">Dernières commandes</h2>
        <?php if (empty($recentOrders)): ?>
            <p class="text-gray-500">Aucune commande trouvée.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm divide-y divide-gray-200">
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
                                'cancelled' => 'bg-red-100 text-red-800',
                                'refunded' => 'bg-gray-100 text-gray-800'
                            ];
                            $statusText = [
                                'pending' => 'En attente',
                                'processing' => 'En traitement',
                                'completed' => 'Complétée',
                                'cancelled' => 'Annulée',
                                'refunded' => 'Remboursée'
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
                                <?php if ($card['quantity'] > 0): ?>
                                    <span class="text-green-600 font-medium"><?php echo formatPrice($card['price']); ?></span> |
                                    Stock: <?php echo $card['quantity']; ?>
                                    <?php if (!empty($card['condition_code'])): ?> |
                                        État: <?php echo CARD_CONDITIONS[$card['condition_code']]; ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-red-600">Indisponible</span>
                                <?php endif; ?>
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

<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
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
                                <?php if (!empty($card['condition_code'])): ?> |
                                    État: <?php echo CARD_CONDITIONS[$card['condition_code']]; ?>
                                <?php endif; ?>
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
                                Série: <?php echo htmlspecialchars($card['series_name']); ?> |
                                État: <?php echo CARD_CONDITIONS[$card['condition_code']]; ?>
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

<div class="bg-white rounded-lg shadow-md p-6 mt-8">
    <h2 class="text-xl font-bold mb-4">Évolution des ventes (6 mois)</h2>
    <canvas id="salesChart" class="w-full h-64 sm:h-80"></canvas>
</div>

<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($salesByMonth, 'month')); ?>,
            datasets: [{
                label: 'CHF',
                data: <?php echo json_encode(array_map(fn($row) => round($row['total'], 2), $salesByMonth)); ?>,
                fill: true,
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.3
            }]
        }
    });
</script>
<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>