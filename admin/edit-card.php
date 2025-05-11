<?php
// admin/edit-card.php

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
    // Récupérer et valider les données du formulaire
    $seriesId = isset($_POST['series_id']) ? (int)$_POST['series_id'] : null;
    $name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : '';
    $cardNumber = isset($_POST['card_number']) ? sanitizeInput($_POST['card_number']) : '';
    $rarity = isset($_POST['rarity']) ? sanitizeInput($_POST['rarity']) : '';
    $condition = isset($_POST['condition']) ? sanitizeInput($_POST['condition']) : '';
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : '';

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

    if ($price <= 0) {
        $errors[] = 'Le prix doit être supérieur à 0';
    }

    if ($quantity < 0) {
        $errors[] = 'La quantité ne peut pas être négative';
    }

    // Traitement de l'image
    $imageUrl = $card['image_url']; // Garder l'image existante par défaut

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/cards/';

        // Créer le dossier s'il n'existe pas
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            $errors[] = 'Le format de l\'image n\'est pas autorisé. Formats acceptés : ' . implode(', ', $allowedExtensions);
        } else {
            $fileName = uniqid('card_') . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                // Supprimer l'ancienne image si elle existe
                if ($card['image_url'] && file_exists('../' . $card['image_url'])) {
                    unlink('../' . $card['image_url']);
                }

                $imageUrl = 'uploads/cards/' . $fileName;
            } else {
                $errors[] = 'Erreur lors de l\'upload de l\'image';
            }
        }
    }

    // Supprimer l'image si demandé
    if (isset($_POST['delete_image']) && $_POST['delete_image'] === '1') {
        if ($card['image_url'] && file_exists('../' . $card['image_url'])) {
            unlink('../' . $card['image_url']);
        }
        $imageUrl = null;
    }

    // Si aucune erreur, mettre à jour la carte
    if (empty($errors)) {
        if (updateCard($cardId, $seriesId, $name, $cardNumber, $rarity, $condition, $price, $quantity, $imageUrl, $description)) {
            $success = true;

            // Mettre à jour l'objet carte avec les nouvelles valeurs
            $card = getCardById($cardId);

            // Message flash
            $_SESSION['flash_message'] = 'La carte a été mise à jour avec succès';
            $_SESSION['flash_type'] = 'success';
        } else {
            $errors[] = 'Erreur lors de la mise à jour de la carte';
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
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <p>La carte a été mise à jour avec succès !</p>
        </div>
    <?php endif; ?>

    <form method="POST" action="edit-card.php?id=<?php echo $cardId; ?>" enctype="multipart/form-data" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Informations principales -->
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom de la carte *</label>
                    <input type="text" id="name" name="name" required
                        value="<?php echo htmlspecialchars($card['name']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="series_id" class="block text-sm font-medium text-gray-700 mb-1">Série</label>
                    <select id="series_id" name="series_id" class="w-full p-2 border border-gray-300 rounded-md">
                        <option value="">-- Sélectionner une série --</option>
                        <?php foreach ($allSeries as $series): ?>
                            <option value="<?php echo $series['id']; ?>" <?php echo $card['series_id'] == $series['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($series['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="card_number" class="block text-sm font-medium text-gray-700 mb-1">Numéro de carte *</label>
                    <input type="text" id="card_number" name="card_number" required
                        value="<?php echo htmlspecialchars($card['card_number']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="rarity" class="block text-sm font-medium text-gray-700 mb-1">Rareté</label>
                    <input type="text" id="rarity" name="rarity"
                        value="<?php echo htmlspecialchars($card['rarity']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="condition" class="block text-sm font-medium text-gray-700 mb-1">État *</label>
                    <select id="condition" name="condition" required class="w-full p-2 border border-gray-300 rounded-md">
                        <option value="">-- Sélectionner un état --</option>
                        <?php foreach (CARD_CONDITIONS as $code => $name): ?>
                            <option value="<?php echo $code; ?>" <?php echo $card['card_condition'] == $code ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Prix, stock et image -->
            <div class="space-y-4">
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Prix *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">CHF</span>
                        </div>
                        <input type="number" id="price" name="price" step="0.01" min="0" required
                            value="<?php echo htmlspecialchars($card['price']); ?>"
                            class="w-full pl-7 p-2 border border-gray-300 rounded-md">
                    </div>
                </div>

                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantité en stock *</label>
                    <input type="number" id="quantity" name="quantity" min="0" required
                        value="<?php echo htmlspecialchars($card['quantity']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                    <input type="file" id="image" name="image" accept="image/*"
                        class="w-full p-2 border border-gray-300 rounded-md">
                    <p class="text-sm text-gray-500 mt-1">Formats acceptés : JPG, JPEG, PNG, GIF</p>
                </div>

                <?php if ($card['image_url']): ?>
                    <div class="mt-2">
                        <div id="current_image_container">
                            <p class="text-sm font-medium text-gray-700 mb-1">Image actuelle</p>
                            <div class="relative">
                                <img id="current_image" src="<?php echo SITE_URL . '/' . $card['image_url']; ?>"
                                    alt="<?php echo htmlspecialchars($card['name']); ?>"
                                    class="w-48 h-48 object-contain border border-gray-300 rounded-md">
                                <div class="mt-2">
                                    <label class="flex items-center text-sm text-red-600">
                                        <input type="checkbox" name="delete_image" value="1" class="mr-2">
                                        Supprimer cette image
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mt-2">
                    <div id="image_preview_container" class="mt-2 hidden">
                        <p class="text-sm font-medium text-gray-700 mb-1">Aperçu de la nouvelle image</p>
                        <img id="image_preview" src="#" alt="Aperçu de l'image" class="w-48 h-48 object-contain border border-gray-300 rounded-md">
                    </div>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea id="description" name="description" rows="4"
                class="w-full p-2 border border-gray-300 rounded-md"><?php echo htmlspecialchars($card['description']); ?></textarea>
        </div>

        <!-- Boutons d'action -->
        <div class="flex justify-between">
            <a href="delete-card.php?id=<?php echo $cardId; ?>" class="bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 transition delete-confirm">
                <i class="fas fa-trash mr-1"></i> Supprimer
            </a>

            <div class="space-x-2">
                <a href="cards.php" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300 transition">
                    Annuler
                </a>
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition">
                    <i class="fas fa-save mr-1"></i> Enregistrer
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('image_preview');
        const previewContainer = document.getElementById('image_preview_container');
        const currentImage = document.getElementById('current_image');
        const currentImageContainer = document.getElementById('current_image_container');
        const deleteImageCheckbox = document.querySelector('input[name="delete_image"]');

        imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    previewContainer.classList.remove('hidden');

                    // Masquer l'image actuelle si une case à cocher de suppression est cochée
                    if (deleteImageCheckbox) {
                        deleteImageCheckbox.checked = false;
                    }
                };

                reader.readAsDataURL(this.files[0]);
            } else {
                previewContainer.classList.add('hidden');
            }
        });

        // Si la case à cocher pour supprimer l'image est cochée, masquer l'image actuelle
        if (deleteImageCheckbox) {
            deleteImageCheckbox.addEventListener('change', function() {
                if (this.checked && currentImageContainer) {
                    currentImage.style.opacity = '0.3';
                } else if (currentImageContainer) {
                    currentImage.style.opacity = '1';
                }
            });
        }
    });
</script>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>