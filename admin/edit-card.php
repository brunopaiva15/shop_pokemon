<?php
// admin/edit-card.php

// Inclure les fonctions nécessaires
require_once '../includes/functions.php';

// Vérifier si l'ID de la carte est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = 'ID de carte non valide';
    $_SESSION['flash_type'] = 'error';
    header('Location: cards.php');
    exit;
}

$cardId = (int)$_GET['id'];

// Récupérer les informations de la carte
$card = getCardById($cardId);

// Si la carte n'existe pas, rediriger vers la liste des cartes
if (!$card) {
    $_SESSION['flash_message'] = 'Carte non trouvée';
    $_SESSION['flash_type'] = 'error';
    header('Location: cards.php');
    exit;
}

// Récupérer les conditions existantes de la carte
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT * FROM card_conditions WHERE card_id = ? ORDER BY condition_code");
$stmt->execute([$cardId]);
$cardConditions = $stmt->fetchAll();

// Définir le titre de la page
$pageTitle = 'Modifier la carte : ' . htmlspecialchars($card['name']);

// Inclure l'en-tête
require_once 'includes/header.php';

// Récupérer toutes les séries
$allSeries = getAllSeries();

$errors = [];
$success = false;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et valider les données de base de la carte
    $seriesId    = isset($_POST['series_id'])   ? (int) sanitizeInput($_POST['series_id'])   : null;
    $name        = isset($_POST['name'])        ? sanitizeInput($_POST['name'])             : '';
    $cardNumber  = isset($_POST['card_number']) ? sanitizeInput($_POST['card_number'])      : '';
    $rarity      = isset($_POST['rarity'])      ? sanitizeInput($_POST['rarity'])           : '';
    $variant     = isset($_POST['variant'])     ? sanitizeInput($_POST['variant'])          : '';
    $description = isset($_POST['description']) ? sanitizeInput($_POST['description'])      : '';

    // Récupérer les états, prix et quantités
    $conditionIds = isset($_POST['condition_ids']) ? $_POST['condition_ids'] : [];
    $conditions = isset($_POST['conditions']) ? $_POST['conditions'] : [];
    $prices = isset($_POST['prices']) ? $_POST['prices'] : [];
    $quantities = isset($_POST['quantities']) ? $_POST['quantities'] : [];

    // Validation des données de base
    if (empty($name)) {
        $errors[] = 'Le nom de la carte est obligatoire';
    }
    if (empty($cardNumber)) {
        $errors[] = 'Le numéro de la carte est obligatoire';
    }
    if (empty($rarity) || !array_key_exists($rarity, CARD_RARITIES)) {
        $errors[] = 'La rareté de la carte est obligatoire et doit être valide';
    }
    if (empty($variant) || !array_key_exists($variant, CARD_VARIANTS)) {
        $errors[] = 'La variante de la carte est obligatoire et doit être valide';
    }
    if (empty($conditions)) {
        $errors[] = 'Au moins un état est obligatoire';
    }

    // Validation des conditions
    $uniqueConditions = [];
    for ($i = 0; $i < count($conditions); $i++) {
        $condition = sanitizeInput($conditions[$i]);
        $price = isset($prices[$i]) ? (float) $prices[$i] : 0;
        $quantity = isset($quantities[$i]) ? (int) $quantities[$i] : 0;

        if (empty($condition) || !array_key_exists($condition, CARD_CONDITIONS)) {
            $errors[] = 'L\'état #' . ($i + 1) . ' est obligatoire et doit être valide';
        } elseif (in_array($condition, $uniqueConditions)) {
            $errors[] = 'L\'état ' . CARD_CONDITIONS[$condition] . ' est en double';
        } else {
            $uniqueConditions[] = $condition;
        }

        if ($price <= 0) {
            $errors[] = 'Le prix pour l\'état #' . ($i + 1) . ' doit être supérieur à 0';
        }

        if ($quantity < 0) {
            $errors[] = 'La quantité pour l\'état #' . ($i + 1) . ' ne peut pas être négative';
        }
    }

    // Construction automatique de l'image
    $imageUrl = null;
    if (empty($errors)) {
        $series = getSeriesById($seriesId);
        if (!$series) {
            $errors[] = 'Série invalide';
        } else {
            $seriesCode   = $series['code'];                   // ex. "PRE"
            $cardNumClean = ltrim($cardNumber, '0');           // "053" → "53"
            $imageUrl     = "https://pokecardex.b-cdn.net/assets/images/sets/"
                . "{$seriesCode}/HD/{$cardNumClean}.jpg";

            // Vérifier que l'URL existe
            $headers = @get_headers($imageUrl);
            if (!$headers || strpos($headers[0], '200') === false) {
                $errors[] = "Image introuvable pour {$seriesCode} #{$cardNumClean}";
            }
        }
    }

    // Si aucune erreur, mettre à jour la carte
    if (empty($errors)) {
        // Démarrer une transaction
        $conn = getDbConnection();
        $conn->beginTransaction();

        try {
            // Mettre à jour les informations de base de la carte
            $stmt = $conn->prepare("
                UPDATE cards 
                SET series_id = ?, name = ?, card_number = ?, rarity = ?, variant = ?, description = ?, image_url = ?
                WHERE id = ?
            ");
            $stmt->execute([$seriesId, $name, $cardNumber, $rarity, $variant, $description, $imageUrl, $cardId]);

            // Récupérer les conditions actuelles pour comparaison
            $existingConditions = [];
            $existingConditionCodes = [];
            foreach ($cardConditions as $condition) {
                $existingConditions[$condition['id']] = $condition;
                $existingConditionCodes[$condition['condition_code']] = $condition['id'];
            }

            // Traiter les conditions
            $processedConditionIds = [];
            $processedConditionCodes = [];

            for ($i = 0; $i < count($conditions); $i++) {
                $conditionId = isset($conditionIds[$i]) && $conditionIds[$i] ? (int) $conditionIds[$i] : null;
                $condition = sanitizeInput($conditions[$i]);
                $price = (float) $prices[$i];
                $quantity = (int) $quantities[$i];

                // Éviter les doublons dans la mise à jour
                if (in_array($condition, $processedConditionCodes)) {
                    continue;
                }

                $processedConditionCodes[] = $condition;

                // Vérifier si on essaie de changer un code de condition
                // Si l'ID existe mais qu'on change le code, vérifier si le nouveau code existe déjà
                if (
                    $conditionId && isset($existingConditions[$conditionId])
                    && $existingConditions[$conditionId]['condition_code'] != $condition
                    && isset($existingConditionCodes[$condition])
                ) {
                    // On essaie de changer l'état pour un qui existe déjà
                    // Supprimer l'ancien enregistrement
                    $stmt = $conn->prepare("DELETE FROM card_conditions WHERE id = ?");
                    $stmt->execute([$conditionId]);

                    // Mettre à jour l'enregistrement existant avec le nouveau code
                    $existingId = $existingConditionCodes[$condition];
                    $stmt = $conn->prepare("
                        UPDATE card_conditions
                        SET price = ?, quantity = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$price, $quantity, $existingId]);

                    $processedConditionIds[] = $existingId;
                    continue;
                }

                if ($conditionId && isset($existingConditions[$conditionId])) {
                    // Mettre à jour une condition existante
                    $stmt = $conn->prepare("
                        UPDATE card_conditions
                        SET condition_code = ?, price = ?, quantity = ?
                        WHERE id = ? AND card_id = ?
                    ");
                    $stmt->execute([$condition, $price, $quantity, $conditionId, $cardId]);

                    // Marquer comme traité
                    $processedConditionIds[] = $conditionId;
                } else {
                    // Vérifier si ce code existe déjà pour cette carte
                    if (isset($existingConditionCodes[$condition])) {
                        // Mettre à jour l'enregistrement existant
                        $existingId = $existingConditionCodes[$condition];
                        $stmt = $conn->prepare("
                            UPDATE card_conditions
                            SET price = ?, quantity = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$price, $quantity, $existingId]);
                        $processedConditionIds[] = $existingId;
                    } else {
                        // Ajouter une nouvelle condition
                        $stmt = $conn->prepare("
                            INSERT INTO card_conditions (card_id, condition_code, price, quantity)
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$cardId, $condition, $price, $quantity]);
                    }
                }
            }

            // Supprimer les conditions qui n'ont pas été mentionnées
            foreach ($existingConditions as $id => $conditionData) {
                if (!in_array($id, $processedConditionIds)) {
                    $stmt = $conn->prepare("DELETE FROM card_conditions WHERE id = ?");
                    $stmt->execute([$id]);
                }
            }

            $conn->commit();
            $success = true;

            // Récupérer les données mises à jour
            $card = getCardById($cardId);
            $stmt = $conn->prepare("SELECT * FROM card_conditions WHERE card_id = ? ORDER BY condition_code");
            $stmt->execute([$cardId]);
            $cardConditions = $stmt->fetchAll();

            // Message flash
            $_SESSION['flash_message'] = 'La carte a été mise à jour avec succès';
            $_SESSION['flash_type']    = 'success';
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = 'Erreur lors de la mise à jour de la carte: ' . $e->getMessage();
        }
    }
}
?>

<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Modifier la carte</h2>
        <a href="<?php echo SITE_URL; ?>/card-details.php?id=<?php echo $cardId; ?>" target="_blank" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-external-link-alt mr-1"></i> Voir sur le site
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <p>La carte a été mise à jour avec succès !</p>
        </div>
    <?php endif; ?>

    <form method="POST" action="edit-card.php?id=<?= $cardId ?>" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Informations principales -->
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom de la carte *</label>
                    <input type="text" id="name" name="name" required
                        value="<?= htmlspecialchars($card['name']) ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="series_id" class="block text-sm font-medium text-gray-700 mb-1">Série *</label>
                    <select id="series_id" name="series_id" required class="w-full p-2 border border-gray-300 rounded-md">
                        <option value="">-- Sélectionner une série --</option>
                        <?php foreach ($allSeries as $series): ?>
                            <option value="<?= $series['id'] ?>"
                                data-code="<?= htmlspecialchars($series['code']) ?>"
                                <?= ($card['series_id'] == $series['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($series['name']) ?> (<?= htmlspecialchars($series['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="card_number" class="block text-sm font-medium text-gray-700 mb-1">Numéro de carte *</label>
                    <input type="text" id="card_number" name="card_number" required
                        value="<?= htmlspecialchars($card['card_number']) ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="rarity" class="block text-sm font-medium text-gray-700 mb-1">Rareté *</label>
                    <select id="rarity" name="rarity" required class="w-full p-2 border border-gray-300 rounded-md">
                        <option value="">-- Sélectionner une rareté --</option>
                        <?php foreach (CARD_RARITIES as $code => $name): ?>
                            <option value="<?= $code ?>" <?= ($card['rarity'] == $code) ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="variant" class="block text-sm font-medium text-gray-700 mb-1">Variante *</label>
                    <select id="variant" name="variant" required class="w-full p-2 border border-gray-300 rounded-md">
                        <option value="">-- Sélectionner une variante --</option>
                        <?php foreach (CARD_VARIANTS as $code => $name): ?>
                            <option value="<?= $code ?>" <?= ($card['variant'] == $code) ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Aperçu image -->
            <div class="space-y-4">
                <div id="auto_image_preview" class="mt-4">
                    <p class="text-sm font-medium text-gray-700 mb-1">Aperçu automatique :</p>
                    <img id="preview_img" src="<?= $card['image_url'] ?>" alt="Aperçu auto" class="w-48 h-48 object-contain border border-gray-300 rounded-md">
                </div>
            </div>
        </div>

        <!-- États, prix et quantités -->
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-4">États, prix et quantités</h3>

            <div id="condition-items" class="space-y-4">
                <?php if (empty($cardConditions)): ?>
                    <div class="condition-item border border-gray-300 rounded-md p-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">État *</label>
                                <select name="conditions[]" required class="w-full p-2 border border-gray-300 rounded-md">
                                    <option value="">-- Sélectionner un état --</option>
                                    <?php foreach (CARD_CONDITIONS as $code => $name): ?>
                                        <option value="<?= $code ?>"><?= htmlspecialchars($name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="condition_ids[]" value="">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Prix *</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 right-2 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">CHF</span>
                                    </div>
                                    <input type="number" name="prices[]" step="0.01" min="0.01" required
                                        class="w-full p-2 border border-gray-300 rounded-md">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Quantité *</label>
                                <input type="number" name="quantities[]" min="0" value="1" required
                                    class="w-full p-2 border border-gray-300 rounded-md">
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($cardConditions as $index => $condition): ?>
                        <div class="condition-item border border-gray-300 rounded-md p-4">
                            <?php if ($index > 0): ?>
                                <div class="flex justify-between items-center mb-2">
                                    <h4 class="font-medium">État <?= $index + 1 ?></h4>
                                    <button type="button" class="remove-condition text-red-600 hover:text-red-800 transition">
                                        <i class="fas fa-times"></i> Supprimer
                                    </button>
                                </div>
                            <?php endif; ?>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">État *</label>
                                    <select name="conditions[]" required class="w-full p-2 border border-gray-300 rounded-md">
                                        <option value="">-- Sélectionner un état --</option>
                                        <?php foreach (CARD_CONDITIONS as $code => $name): ?>
                                            <option value="<?= $code ?>" <?= ($condition['condition_code'] == $code) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="condition_ids[]" value="<?= $condition['id'] ?>">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix *</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 right-2 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">CHF</span>
                                        </div>
                                        <input type="number" name="prices[]" step="0.01" min="0.01" required
                                            value="<?= htmlspecialchars($condition['price']) ?>"
                                            class="w-full p-2 border border-gray-300 rounded-md">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantité *</label>
                                    <input type="number" name="quantities[]" min="0" required
                                        value="<?= htmlspecialchars($condition['quantity']) ?>"
                                        class="w-full p-2 border border-gray-300 rounded-md">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="button" id="add-condition" class="mt-4 bg-blue-100 text-blue-800 py-2 px-4 rounded-md hover:bg-blue-200 transition">
                <i class="fas fa-plus mr-1"></i> Ajouter un autre état
            </button>
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea id="description" name="description" rows="4" class="w-full p-2 border border-gray-300 rounded-md"><?= htmlspecialchars($card['description']) ?></textarea>
        </div>

        <!-- Boutons d'action -->
        <div class="flex justify-end space-x-2">
            <a href="cards.php" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300 transition">Annuler</a>
            <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition">
                <i class="fas fa-save mr-1"></i> Enregistrer
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Prévisualisation de l'image
        const serieSelect = document.getElementById('series_id');
        const cardNumInput = document.getElementById('card_number');
        const previewImg = document.getElementById('preview_img');

        function updatePreview() {
            const opt = serieSelect.selectedOptions[0];
            const code = opt ? opt.dataset.code : '';
            let num = cardNumInput.value.replace(/^0+/, '');
            if (code && num) {
                previewImg.src = `https://pokecardex.b-cdn.net/assets/images/sets/${code}/HD/${num}.jpg`;
            } else {
                previewImg.src = '#';
            }
        }

        serieSelect.addEventListener('change', updatePreview);
        cardNumInput.addEventListener('input', updatePreview);

        // Gestion des états multiples
        const addConditionBtn = document.getElementById('add-condition');
        const conditionItems = document.getElementById('condition-items');

        // Ajouter des écouteurs pour les boutons de suppression existants
        document.querySelectorAll('.remove-condition').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.condition-item').remove();
            });
        });

        addConditionBtn.addEventListener('click', function() {
            const newItem = document.createElement('div');
            newItem.className = 'condition-item border border-gray-300 rounded-md p-4';
            newItem.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <h4 class="font-medium">Nouvel état</h4>
                    <button type="button" class="remove-condition text-red-600 hover:text-red-800 transition">
                        <i class="fas fa-times"></i> Supprimer
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">État *</label>
                        <select name="conditions[]" required class="w-full p-2 border border-gray-300 rounded-md">
                            <option value="">-- Sélectionner un état --</option>
                            <?php foreach (CARD_CONDITIONS as $code => $name): ?>
                                <option value="<?= $code ?>"><?= htmlspecialchars($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="condition_ids[]" value="">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prix *</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 right-2 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">CHF</span>
                            </div>
                            <input type="number" name="prices[]" step="0.01" min="0.01" required
                                class="w-full p-2 border border-gray-300 rounded-md">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantité *</label>
                        <input type="number" name="quantities[]" min="0" value="1" required
                            class="w-full p-2 border border-gray-300 rounded-md">
                    </div>
                </div>
            `;
            conditionItems.appendChild(newItem);

            // Ajouter l'écouteur pour le bouton supprimer
            newItem.querySelector('.remove-condition').addEventListener('click', function() {
                newItem.remove();
            });
        });
    });
</script>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>