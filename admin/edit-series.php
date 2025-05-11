<?php
// admin/edit-series.php

// Vérifier si l'ID de la série est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = 'ID de série non valide';
    $_SESSION['flash_type'] = 'error';
    header('Location: series.php');
    exit;
}

$seriesId = (int)$_GET['id'];

// Récupérer les informations de la série
$series = getSeriesById($seriesId);

// Si la série n'existe pas, rediriger vers la liste des séries
if (!$series) {
    $_SESSION['flash_message'] = 'Série non trouvée';
    $_SESSION['flash_type'] = 'error';
    header('Location: series.php');
    exit;
}

// Définir le titre de la page
$pageTitle = 'Modifier la série : ' . htmlspecialchars($series['name']);

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
    $logoUrl = $series['logo_url']; // Garder le logo existant par défaut

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
                // Supprimer l'ancien logo si il existe
                if ($series['logo_url'] && file_exists('../' . $series['logo_url'])) {
                    unlink('../' . $series['logo_url']);
                }

                $logoUrl = 'uploads/series/' . $fileName;
            } else {
                $errors[] = 'Erreur lors de l\'upload du logo';
            }
        }
    }

    // Supprimer le logo si demandé
    if (isset($_POST['delete_logo']) && $_POST['delete_logo'] === '1') {
        if ($series['logo_url'] && file_exists('../' . $series['logo_url'])) {
            unlink('../' . $series['logo_url']);
        }
        $logoUrl = null;
    }

    // Si aucune erreur, mettre à jour la série
    if (empty($errors)) {
        if (updateSeries($seriesId, $name, $releaseDate, $logoUrl)) {
            $success = true;

            // Mettre à jour l'objet série avec les nouvelles valeurs
            $series = getSeriesById($seriesId);

            // Message flash
            $_SESSION['flash_message'] = 'La série a été mise à jour avec succès';
            $_SESSION['flash_type'] = 'success';
        } else {
            $errors[] = 'Erreur lors de la mise à jour de la série';
        }
    }
}

// Récupérer le nombre de cartes associées à cette série
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT COUNT(*) as card_count FROM cards WHERE series_id = ?");
$stmt->execute([$seriesId]);
$cardCount = $stmt->fetch()['card_count'];
?>

<div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-xl font-bold mb-6">Modifier la série</h2>

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
            <p>La série a été mise à jour avec succès !</p>
        </div>
    <?php endif; ?>

    <form method="POST" action="edit-series.php?id=<?php echo $seriesId; ?>" enctype="multipart/form-data" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Informations de la série -->
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom de la série *</label>
                    <input type="text" id="name" name="name" required
                        value="<?php echo htmlspecialchars($series['name']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="release_date" class="block text-sm font-medium text-gray-700 mb-1">Date de sortie</label>
                    <input type="date" id="release_date" name="release_date"
                        value="<?php echo $series['release_date'] ? date('Y-m-d', strtotime($series['release_date'])) : ''; ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div>
                    <p class="text-sm text-gray-700 mb-1">Nombre de cartes : <strong><?php echo $cardCount; ?></strong></p>
                    <a href="cards.php?series=<?php echo $seriesId; ?>" class="text-blue-600 hover:underline">
                        <i class="fas fa-arrow-right mr-1"></i> Voir les cartes de cette série
                    </a>
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

                <?php if ($series['logo_url']): ?>
                    <div class="mt-2">
                        <div id="current_logo_container">
                            <p class="text-sm font-medium text-gray-700 mb-1">Logo actuel</p>
                            <div class="relative">
                                <img id="current_logo" src="<?php echo SITE_URL . '/' . $series['logo_url']; ?>"
                                    alt="<?php echo htmlspecialchars($series['name']); ?>"
                                    class="w-48 h-48 object-contain border border-gray-300 rounded-md">
                                <div class="mt-2">
                                    <label class="flex items-center text-sm text-red-600">
                                        <input type="checkbox" name="delete_logo" value="1" class="mr-2">
                                        Supprimer ce logo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mt-2">
                    <div id="logo_preview_container" class="mt-2 hidden">
                        <p class="text-sm font-medium text-gray-700 mb-1">Aperçu du nouveau logo</p>
                        <img id="logo_preview" src="#" alt="Aperçu du logo" class="w-48 h-48 object-contain border border-gray-300 rounded-md">
                    </div>
                </div>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="flex justify-between">
            <?php if ($cardCount == 0): ?>
                <a href="delete-series.php?id=<?php echo $seriesId; ?>" class="bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 transition delete-confirm">
                    <i class="fas fa-trash mr-1"></i> Supprimer
                </a>
            <?php else: ?>
                <button type="button" class="bg-red-600 text-white py-2 px-4 rounded-md opacity-50 cursor-not-allowed" disabled title="Impossible de supprimer une série contenant des cartes">
                    <i class="fas fa-trash mr-1"></i> Supprimer
                </button>
            <?php endif; ?>

            <div class="space-x-2">
                <a href="series.php" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300 transition">
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
        const logoInput = document.getElementById('logo');
        const logoPreview = document.getElementById('logo_preview');
        const previewContainer = document.getElementById('logo_preview_container');
        const currentLogo = document.getElementById('current_logo');
        const currentLogoContainer = document.getElementById('current_logo_container');
        const deleteLogoCheckbox = document.querySelector('input[name="delete_logo"]');

        logoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    logoPreview.src = e.target.result;
                    previewContainer.classList.remove('hidden');

                    // Décocher la case à cocher de suppression si elle est cochée
                    if (deleteLogoCheckbox) {
                        deleteLogoCheckbox.checked = false;
                    }
                };

                reader.readAsDataURL(this.files[0]);
            } else {
                previewContainer.classList.add('hidden');
            }
        });

        // Si la case à cocher pour supprimer le logo est cochée, masquer le logo actuel
        if (deleteLogoCheckbox) {
            deleteLogoCheckbox.addEventListener('change', function() {
                if (this.checked && currentLogoContainer) {
                    currentLogo.style.opacity = '0.3';
                } else if (currentLogoContainer) {
                    currentLogo.style.opacity = '1';
                }
            });
        }
    });
</script>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>