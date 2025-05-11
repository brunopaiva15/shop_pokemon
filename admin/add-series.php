<?php
// admin/add-series.php

// Définir le titre de la page
$pageTitle = 'Ajouter une série';

// Inclure l'en-tête
require_once 'includes/header.php';

$errors = [];
$success = false;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et valider les données du formulaire
    $name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : '';
    $releaseDate = isset($_POST['release_date']) && !empty($_POST['release_date']) ? $_POST['release_date'] : null;

    // Validation
    if (empty($name)) {
        $errors[] = 'Le nom de la série est obligatoire';
    }

    // Traitement du logo
    $logoUrl = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/series/';

        // Créer le dossier s'il n'existe pas
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExtension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            $errors[] = 'Le format du logo n\'est pas autorisé. Formats acceptés : ' . implode(', ', $allowedExtensions);
        } else {
            $fileName = uniqid('series_') . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
                $logoUrl = 'uploads/series/' . $fileName;
            } else {
                $errors[] = 'Erreur lors de l\'upload du logo';
            }
        }
    }

    // Si aucune erreur, ajouter la série
    if (empty($errors)) {
        if (addSeries($name, $releaseDate, $logoUrl)) {
            $success = true;

            // Message flash
            $_SESSION['flash_message'] = 'La série a été ajoutée avec succès';
            $_SESSION['flash_type'] = 'success';

            // Rediriger vers la liste des séries
            header('Location: series.php');
            exit;
        } else {
            $errors[] = 'Erreur lors de l\'ajout de la série';
        }
    }
}
?>

<div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-xl font-bold mb-6">Ajouter une nouvelle série</h2>

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
            <p>La série a été ajoutée avec succès !</p>
        </div>
    <?php endif; ?>

    <form method="POST" action="add-series.php" enctype="multipart/form-data" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Informations de la série -->
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom de la série *</label>
                    <input type="text" id="name" name="name" required
                        value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="release_date" class="block text-sm font-medium text-gray-700 mb-1">Date de sortie</label>
                    <input type="date" id="release_date" name="release_date"
                        value="<?php echo isset($_POST['release_date']) ? htmlspecialchars($_POST['release_date']) : ''; ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>
            </div>

            <!-- Logo -->
            <div class="space-y-4">
                <div>
                    <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">Logo de la série</label>
                    <input type="file" id="logo" name="logo" accept="image/*"
                        class="w-full p-2 border border-gray-300 rounded-md">
                    <p class="text-sm text-gray-500 mt-1">Formats acceptés : JPG, JPEG, PNG, GIF</p>
                </div>

                <div class="mt-2">
                    <div id="logo_preview_container" class="mt-2 hidden">
                        <p class="text-sm font-medium text-gray-700 mb-1">Aperçu</p>
                        <img id="logo_preview" src="#" alt="Aperçu du logo" class="w-48 h-48 object-contain border border-gray-300 rounded-md">
                    </div>
                </div>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="flex justify-end space-x-2">
            <a href="series.php" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300 transition">
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
        const logoInput = document.getElementById('logo');
        const logoPreview = document.getElementById('logo_preview');
        const previewContainer = document.getElementById('logo_preview_container');

        logoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    logoPreview.src = e.target.result;
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