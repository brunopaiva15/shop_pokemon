<?php
// admin/add-card.php

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
    $imageUrl = null;
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
                $imageUrl = 'uploads/cards/' . $fileName;
            } else {
                $errors[] = 'Erreur lors de l\'upload de l\'image';
            }
        }
    }

    // Si aucune erreur, ajouter la carte
    if (empty($errors)) {
        if (addCard($seriesId, $name, $cardNumber, $rarity, $condition, $price, $quantity, $imageUrl, $description)) {
            $success = true;

            // Message flash
            $_SESSION['flash_message'] = 'La carte a été ajoutée avec succès';
            $_SESSION['flash_type'] = 'success';

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
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <p>La carte a été ajoutée avec succès !</p>
        </div>
    <?php endif; ?>

    <form method="POST" action="add-card.php" enctype="multipart/form-data" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Informations principales -->
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom de la carte *</label>
                    <input type="text" id="name" name="name" required
                        value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="series_id" class="block text-sm font-medium text-gray-700 mb-1">Série</label>
                    <select id="series_id" name="series_id" class="w-full p-2 border border-gray-300 rounded-md">
                        <option value="">-- Sélectionner une série --</option>
                        <?php foreach ($allSeries as $series): ?>
                            <option value="<?php echo $series['id']; ?>" <?php echo (isset($_POST['series_id']) && $_POST['series_id'] == $series['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($series['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="card_number" class="block text-sm font-medium text-gray-700 mb-1">Numéro de carte *</label>
                    <input type="text" id="card_number" name="card_number" required
                        value="<?php echo isset($_POST['card_number']) ? htmlspecialchars($_POST['card_number']) : ''; ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="rarity" class="block text-sm font-medium text-gray-700 mb-1">Rareté</label>
                    <input type="text" id="rarity" name="rarity"
                        value="<?php echo isset($_POST['rarity']) ? htmlspecialchars($_POST['rarity']) : ''; ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="condition" class="block text-sm font-medium text-gray-700 mb-1">État *</label>
                    <select id="condition" name="condition" required class="w-full p-2 border border-gray-300 rounded-md">
                        <option value="">-- Sélectionner un état --</option>
                        <?php foreach (CARD_CONDITIONS as $code => $name): ?>
                            <option value="<?php echo $code; ?>" <?php echo (isset($_POST['condition']) && $_POST['condition'] == $code) ? 'selected' : ''; ?>>
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
                            <span class="text-gray-500 sm:text-sm">€</span>
                        </div>
                        <input type="number" id="price" name="price" step="0.01" min="0" required
                            value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>"
                            class="w-full pl-7 p-2 border border-gray-300 rounded-md">
                    </div>
                </div>

                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantité en stock *</label>
                    <input type="number" id="quantity" name="quantity" min="0" required
                        value="<?php echo isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : '1'; ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                    <input type="file" id="image" name="image" accept="image/*"
                        class="w-full p-2 border border-gray-300 rounded-md">
                    <p class="text-sm text-gray-500 mt-1">Formats acceptés : JPG, JPEG, PNG, GIF</p>
                </div>

                <div class="mt-2">
                    <div id="image_preview_container" class="mt-2 hidden">
                        <p class="text-sm font-medium text-gray-700 mb-1">Aperçu</p>
                        <img id="image_preview" src="#" alt="Aperçu de l'image" class="w-48 h-48 object-contain border border-gray-300 rounded-md">
                    </div>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea id="description" name="description" rows="4"
                class="w-full p-2 border border-gray-300 rounded-md"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
        </div>

        <!-- Boutons d'action -->
        <div class="flex justify-end space-x-2">
            <a href="cards.php" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300 transition">
                Annuler
            </a>
            <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition">
                <i class="fas fa-save mr-1"></i> Enregistrer
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('image_preview');
        const previewContainer = document.getElementById('image_preview_container');

        imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                };

                reader.readAsDataURL(this.files[0]);
            } else {
                previewContainer.classList.add('hidden');
            }
        });
    });
</script>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>