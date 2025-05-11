<?php
// card-details.php

// Inclure les fonctions nécessaires
require_once 'includes/functions.php';

// Vérifier que l'ID est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$cardId = (int)$_GET['id'];
$card = getCardById($cardId);

if (!$card) {
    header('Location: index.php');
    exit;
}

// Mise à jour de l'activité
$ip = $_SERVER['REMOTE_ADDR'];
$now = date('Y-m-d H:i:s');

$conn = getDbConnection();

// Supprimer les vues expirées (> 2 min)
$stmt = $conn->prepare("DELETE FROM page_views WHERE last_active < DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
$stmt->execute();

// Vérifier si l'IP est déjà enregistrée
$stmt = $conn->prepare("SELECT id FROM page_views WHERE ip_address = ? AND card_id = ?");
$stmt->execute([$ip, $cardId]);
$existing = $stmt->fetch();

if ($existing) {
    $stmt = $conn->prepare("UPDATE page_views SET last_active = ? WHERE id = ?");
    $stmt->execute([$now, $existing['id']]);
} else {
    $stmt = $conn->prepare("INSERT INTO page_views (card_id, ip_address, last_active) VALUES (?, ?, ?)");
    $stmt->execute([$cardId, $ip, $now]);
}

// Nombre de visiteurs actifs
$stmt = $conn->prepare("SELECT COUNT(*) FROM page_views WHERE card_id = ?");
$stmt->execute([$cardId]);
$activeUsers = (int)$stmt->fetchColumn();

// Titre de la page
$pageTitle = htmlspecialchars($card['name']);
require_once 'includes/header.php';
?>

<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="flex flex-col md:flex-row">
        <!-- Image -->
        <div class="md:w-1/2 mb-6 md:mb-0 md:pr-6">
            <div class="bg-gray-100 p-6 rounded-lg flex items-center justify-center card-image-zoom relative">
                <?php
                $createdAt = strtotime($card['created_at']);
                $twoWeeksAgo = strtotime('-14 days');
                if ($createdAt !== false && $createdAt > $twoWeeksAgo):
                ?>
                    <div class="absolute top-2 left-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full shadow">
                        Nouveau
                    </div>
                <?php endif; ?>
                <img src="<?php echo $card['image_url'] ?: 'assets/images/card-placeholder.png'; ?>"
                    alt="<?php echo htmlspecialchars($card['name']); ?>"
                    class="max-h-96 object-contain">
            </div>
        </div>

        <!-- Détails -->
        <div class="md:w-1/2">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($card['name']); ?></h1>
                </div>
                <span class="condition-badge condition-<?php echo $card['card_condition']; ?> text-base">
                    <?php echo CARD_CONDITIONS[$card['card_condition']]; ?>
                </span>
            </div>

            <div class="mb-6">
                <p class="text-gray-600">Série: <strong><?php echo htmlspecialchars($card['series_name']); ?></strong></p>
                <p class="text-gray-600">Numéro: <strong><?php echo htmlspecialchars($card['card_number']); ?></strong></p>
                <p class="text-gray-600">Rareté: <strong><?php echo isset(CARD_RARITIES[$card['rarity']]) ? CARD_RARITIES[$card['rarity']] : htmlspecialchars($card['rarity']); ?></strong></p>
                <p class="text-gray-600">Variante: <strong><?php echo isset(CARD_VARIANTS[$card['variant']]) ? CARD_VARIANTS[$card['variant']] : htmlspecialchars($card['variant']); ?></strong></p>
                <?php if (!empty($card['description'])): ?>
                    <div class="mt-4">
                        <h3 class="font-semibold mb-2">Description:</h3>
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($card['description'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-6">
                <div class="flex items-baseline">
                    <h2 class="text-3xl font-bold text-red-600 mr-2"><?php echo formatPrice($card['price']); ?></h2>
                    <?php if ($card['quantity'] > 0): ?>
                        <p class="text-green-600">En stock (<?php echo $card['quantity']; ?> disponible<?php echo $card['quantity'] > 1 ? 's' : ''; ?>)</p>
                    <?php else: ?>
                        <p class="text-red-600">Indisponible</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($card['quantity'] > 0): ?>
                <div class="mb-6">
                    <div class="flex items-center">
                        <div class="quantity-selector mr-4">
                            <button type="button" class="quantity-modifier" data-modifier="minus">-</button>
                            <input type="number" min="1" max="<?php echo $card['quantity']; ?>" value="1" class="quantity-input">
                            <button type="button" class="quantity-modifier" data-modifier="plus">+</button>
                        </div>

                        <button data-card-id="<?php echo $card['id']; ?>" class="add-to-cart bg-gray-800 text-white py-3 px-6 rounded-md hover:bg-gray-900 transition flex-grow">
                            <i class="fas fa-shopping-cart mr-2"></i> Ajouter au panier
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <a href="javascript:history.back()" class="inline-block mt-4 text-gray-600 hover:text-red-600 transition">
                <i class="fas fa-arrow-left mr-1"></i> Retour
            </a>
        </div>
    </div>
</div>

<!-- Recommandations -->
<div class="mt-10">
    <h2 class="text-2xl font-bold mb-6">Autres cartes de la même série</h2>

    <?php
    $stmt = $conn->prepare("SELECT * FROM cards WHERE series_id = ? AND id != ? AND quantity > 0 ORDER BY RAND() LIMIT 3");
    $stmt->execute([$card['series_id'], $card['id']]);
    $relatedCards = $stmt->fetchAll();

    if (!empty($relatedCards)):
    ?>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <?php foreach ($relatedCards as $relatedCard): ?>
                <div class="card-item bg-white rounded-lg shadow-md overflow-hidden card-hover">
                    <div class="card-image-zoom p-4 bg-gray-100 relative">
                        <?php
                        $createdAt = strtotime($relatedCard['created_at']);
                        if ($createdAt !== false && $createdAt > strtotime('-14 days')):
                        ?>
                            <div class="absolute top-2 left-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full shadow">
                                Nouveau
                            </div>
                        <?php endif; ?>

                        <a href="card-details.php?id=<?php echo $relatedCard['id']; ?>">
                            <img src="<?php echo $relatedCard['image_url'] ?: 'assets/images/card-placeholder.png'; ?>"
                                alt="<?php echo htmlspecialchars($relatedCard['name']); ?>"
                                class="mx-auto h-44 object-contain">
                        </a>
                    </div>

                    <div class="p-4">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-bold text-lg truncate">
                                <a href="card-details.php?id=<?php echo $relatedCard['id']; ?>" class="hover:text-red-600 transition">
                                    <?php echo htmlspecialchars($relatedCard['name']); ?>
                                </a>
                            </h3>
                            <span class="condition-badge condition-<?php echo $relatedCard['card_condition']; ?>">
                                <?php echo CARD_CONDITIONS[$relatedCard['card_condition']]; ?>
                            </span>
                        </div>

                        <div class="text-sm text-gray-500 mb-3">
                            <div>Rareté: <?php echo isset(CARD_RARITIES[$relatedCard['rarity']]) ? CARD_RARITIES[$relatedCard['rarity']] : htmlspecialchars($relatedCard['rarity']); ?></div>
                        </div>

                        <div class="flex justify-between items-center">
                            <div class="font-bold text-xl text-red-600"><?php echo formatPrice($relatedCard['price']); ?></div>

                            <button data-card-id="<?php echo $relatedCard['id']; ?>" class="add-to-cart bg-gray-800 text-white py-2 px-4 rounded-md hover:bg-gray-900 transition">
                                <i class="fas fa-shopping-cart mr-1"></i> Ajouter
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-gray-600">Aucune autre carte disponible dans cette série.</p>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>