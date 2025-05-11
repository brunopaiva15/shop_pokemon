<?php
// admin/add-card.php

// Inclure les fonctions nécessaires
require_once '../includes/functions.php';

// Définir le titre de la page
$pageTitle = 'Ajouter une carte';

// Inclure l'en-tête
require_once 'includes/header.php';

// Récupérer toutes les séries
$allSeries = getAllSeries();

$errors = [];
$success = false;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et valider les données du formulaire
    $seriesId    = isset($_POST['series_id'])   ? (int) sanitizeInput($_POST['series_id'])   : null;
    $name        = isset($_POST['name'])        ? sanitizeInput($_POST['name'])             : '';
    $cardNumber  = isset($_POST['card_number']) ? sanitizeInput($_POST['card_number'])      : '';
    $rarity      = isset($_POST['rarity'])      ? sanitizeInput($_POST['rarity'])           : '';
    $variant     = isset($_POST['variant'])     ? sanitizeInput($_POST['variant'])          : '';
    $condition   = isset($_POST['condition'])   ? sanitizeInput($_POST['condition'])        : '';
    $price       = isset($_POST['price'])       ? (float) $_POST['price']                   : 0;
    $quantity    = isset($_POST['quantity'])    ? (int) $_POST['quantity']                  : 0;
    $description = isset($_POST['description']) ? sanitizeInput($_POST['description'])      : '';

    // Validation
    if (empty($name)) {
        $errors[] = 'Le nom de la carte est obligatoire';
    }
    if (empty($cardNumber)) {
        $errors[] = 'Le numéro de la carte est obligatoire';
    }
    if (empty($condition) || !array_key_exists($condition, CARD_CONDITIONS)) {
        $errors[] = 'L\'état de la carte est obligatoire et doit être valide';
    }
    if (empty($rarity) || !array_key_exists($rarity, CARD_RARITIES)) {
        $errors[] = 'La rareté de la carte est obligatoire et doit être valide';
    }
    if (empty($variant) || !array_key_exists($variant, CARD_VARIANTS)) {
        $errors[] = 'La variante de la carte est obligatoire et doit être valide';
    }
    if ($price <= 0) {
        $errors[] = 'Le prix doit être supérieur à 0';
    }
    if ($quantity < 0) {
        $errors[] = 'La quantité ne peut pas être négative';
    }

    // Construction automatique de l'image
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

    // Si aucune erreur, ajouter la carte
    if (empty($errors)) {
        if (addCard($seriesId, $name, $cardNumber, $rarity, $condition, $price, $quantity, $imageUrl, $variant, $description)) {
            $success = true;
            // Message flash
            $_SESSION['flash_message'] = 'La carte a été ajoutée avec succès';
            $_SESSION['flash_type']    = 'success';
            // Rediriger vers la liste des cartes
            header('Location: cards.php');
            exit;
        } else {
            $errors[] = 'Erreur lors de l\'ajout de la carte';
        }
    }
}
?>

<div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-xl font-bold mb-6">Ajouter une nouvelle carte</h2>

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
            <p>La carte a été ajoutée avec succès !</p>
        </div>
    <?php endif; ?>

    <form method="POST" action="add-card.php" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Informations principales -->
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom de la carte *</label>
                    <input type="text" id="name" name="name" required
                        value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="series_id" class="block text-sm font-medium text-gray-700 mb-1">Série *</label>
                    <select id="series_id" name="series_id" required
                        class="w-full p-2 border border-gray-300 rounded-md">
                        <option value="">-- Sélectionner une série --</option>
                        <?php foreach ($allSeries as $series): ?>
                            <option value="<?= $series['id'] ?>"
                                data-code="<?= htmlspecialchars($series['code']) ?>"
                                <?= (isset($_POST['series_id']) && $_POST['series_id'] == $series['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($series['name']) ?> (<?= htmlspecialchars($series['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="card_number" class="block text-sm font-medium text-gray-700 mb-1">Numéro de carte *</label>
                    <input type="text" id="card_number" name="card_number" required
                        value="<?= isset($_POST['card_number']) ? htmlspecialchars($_POST['card_number']) : '' ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="rarity" class="block text-sm font-medium text-gray-700 mb-1">Rareté *</label>
                    <select id="rarity" name="rarity" required
                        class="w-full p-2 border border-gray-300 rounded-md">
                        <option value="">-- Sélectionner une rareté --</option>
                        <?php foreach (CARD_RARITIES as $code => $name): ?>
                            <option value="<?= $code ?>"
                                <?= (isset($_POST['rarity']) && $_POST['rarity'] == $code) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="variant" class="block text-sm font-medium text-gray-700 mb-1">Variante *</label>
                    <select id="variant" name="variant" required
                        class="w-full p-2 border border-gray-300 rounded-md">
                        <option value="">-- Sélectionner une variante --</option>
                        <?php foreach (CARD_VARIANTS as $code => $name): ?>
                            <option value="<?= $code ?>"
                                <?= (isset($_POST['variant']) && $_POST['variant'] == $code) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="condition" class="block text-sm font-medium text-gray-700 mb-1">État *</label>
                    <select id="condition" name="condition" required
                        class="w-full p-2 border border-gray-300 rounded-md">
                        <option value="">-- Sélectionner un état --</option>
                        <?php foreach (CARD_CONDITIONS as $code => $name): ?>
                            <option value="<?= $code ?>"
                                <?= (isset($_POST['condition']) && $_POST['condition'] == $code) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Prix, stock et aperçu image -->
            <div class="space-y-4">
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Prix *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">CHF</span>
                        </div>
                        <input type="number" id="price" name="price" step="0.01" min="0" required
                            value="<?= isset($_POST['price']) ? htmlspecialchars($_POST['price']) : '' ?>"
                            class="w-full pl-7 p-2 border border-gray-300 rounded-md">
                    </div>
                </div>

                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantité en stock *</label>
                    <input type="number" id="quantity" name="quantity" min="0" required
                        value="<?= isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : '1' ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div id="auto_image_preview" class="mt-4">
                    <p class="text-sm font-medium text-gray-700 mb-1">Aperçu automatique :</p>
                    <img id="preview_img" src="#" alt="Aperçu auto" class="w-48 h-48 object-contain border border-gray-300 rounded-md">
                </div>
            </div>
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea id="description" name="description" rows="4"
                class="w-full p-2 border border-gray-300 rounded-md"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
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
    });
</script>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>