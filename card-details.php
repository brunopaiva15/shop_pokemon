<?php
// card-details.php

// Vérifier si l'ID de la carte est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$cardId = (int)$_GET['id'];

// Récupérer les détails de la carte
$card = getCardById($cardId);

// Si la carte n'existe pas, rediriger vers la page d'accueil
if (!$card) {
    header('Location: index.php');
    exit;
}

// Définir le titre de la page
$pageTitle = htmlspecialchars($card['name']);

// Inclure l'en-tête
require_once 'includes/header.php';
?>

<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="flex flex-col md:flex-row">
        <!-- Image de la carte -->
        <div class="md:w-1/2 mb-6 md:mb-0 md:pr-6">
            <div class="bg-gray-100 p-6 rounded-lg flex items-center justify-center card-image-zoom">
                <img src="<?php echo $card['image_url'] ?: 'assets/images/card-placeholder.png'; ?>"
                    alt="<?php echo htmlspecialchars($card['name']); ?>"
                    class="max-h-96 object-contain">
            </div>
        </div>

        <!-- Détails de la carte -->
        <div class="md:w-1/2">
            <div class="flex justify-between items-start mb-4">
                <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($card['name']); ?></h1>
                <span class="condition-badge condition-<?php echo $card['card_condition']; ?> text-base">
                    <?php echo CARD_CONDITIONS[$card['card_condition']]; ?>
                </span>
            </div>

            <div class="mb-6">
                <p class="text-gray-600">Série: <strong><?php echo htmlspecialchars($card['series_name']); ?></strong></p>
                <p class="text-gray-600">Numéro: <strong><?php echo htmlspecialchars($card['card_number']); ?></strong></p>
                <p class="text-gray-600">Rareté: <strong><?php echo htmlspecialchars($card['rarity']); ?></strong></p>
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

                        <button data-card-id="<?php echo $card['id']; ?>" class="add-to-cart bg-red-600 text-white py-3 px-6 rounded-md hover:bg-red-700 transition flex-grow">
                            <i class="fas fa-shopping-cart mr-2"></i> Ajouter au panier
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Bouton retour -->
            <a href="javascript:history.back()" class="inline-block mt-4 text-gray-600 hover:text-red-600 transition">
                <i class="fas fa-arrow-left mr-1"></i> Retour
            </a>
        </div>
    </div>
</div>

<!-- Recommandations - Autres cartes de la même série -->
<div class="mt-10">
    <h2 class="text-2xl font-bold mb-6">Autres cartes de la même série</h2>

    <?php
    // Récupérer d'autres cartes de la même série
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM cards WHERE series_id = ? AND id != ? AND quantity > 0 ORDER BY RAND() LIMIT 3");
    $stmt->execute([$card['series_id'], $card['id']]);
    $relatedCards = $stmt->fetchAll();

    if (!empty($relatedCards)):
    ?>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <?php foreach ($relatedCards as $relatedCard): ?>
                <div class="card-item bg-white rounded-lg shadow-md overflow-hidden card-hover">
                    <div class="card-image-zoom p-4 bg-gray-100">
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

                        <div class="flex justify-between items-center">
                            <div class="font-bold text-xl text-red-600"><?php echo formatPrice($relatedCard['price']); ?></div>

                            <button data-card-id="<?php echo $relatedCard['id']; ?>" class="add-to-cart bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 transition">
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
// Inclure le pied de page
require_once 'includes/footer.php';
?>