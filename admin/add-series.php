<?php
// admin/add-series.php

// Inclure les fonctions nécessaires
require_once '../includes/functions.php';

// Définir le titre de la page
$pageTitle = 'Ajouter une série';

// Inclure l'en-tête
require_once 'includes/header.php';

$errors = [];
$success = false;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et valider les données du formulaire
    $name        = isset($_POST['name'])         ? sanitizeInput($_POST['name'])         : '';
    $code        = isset($_POST['code'])         ? strtoupper(sanitizeInput($_POST['code'])) : '';
    $releaseDate = isset($_POST['release_date']) && !empty($_POST['release_date'])
        ? $_POST['release_date']
        : null;

    // Validation
    if (empty($name)) {
        $errors[] = 'Le nom de la série est obligatoire';
    }
    if (empty($code)) {
        $errors[] = 'Le code abrégé de la série est obligatoire';
    } elseif (!preg_match('/^[A-Z0-9]{1,10}$/', $code)) {
        $errors[] = 'Le code doit comporter entre 1 et 10 caractères alphanumériques (A–Z, 0–9)';
    }

    // Construction automatique du logo
    if (empty($errors)) {
        $logoUrl = "https://pokecardex.b-cdn.net/assets/images/logos/{$code}.png";
        $headers = @get_headers($logoUrl);
        if (!$headers || strpos($headers[0], '200') === false) {
            $errors[] = "Logo introuvable pour le code {$code}";
        }
    }

    // Si aucune erreur, ajouter la série
    if (empty($errors)) {
        if (addSeries($name, $code, $releaseDate, $logoUrl)) {
            $success = true;
            $_SESSION['flash_message'] = 'La série a été ajoutée avec succès';
            $_SESSION['flash_type']    = 'success';
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
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <p>La série a été ajoutée avec succès !</p>
        </div>
    <?php endif; ?>

    <form method="POST" action="add-series.php" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Informations de la série -->
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom de la série *</label>
                    <input type="text" id="name" name="name" required
                        value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Code abrégé *</label>
                    <input type="text" id="code" name="code" required maxlength="10"
                        value="<?= isset($_POST['code']) ? htmlspecialchars($_POST['code']) : '' ?>"
                        class="w-full p-2 border border-gray-300 rounded-md"
                        placeholder="Ex. PRE, XY, SM">
                    <p class="text-sm text-gray-500 mt-1">1 à 10 caractères alphanumériques (A–Z, 0–9)</p>
                </div>
                <div>
                    <label for="release_date" class="block text-sm font-medium text-gray-700 mb-1">Date de sortie</label>
                    <input type="date" id="release_date" name="release_date"
                        value="<?= isset($_POST['release_date']) ? htmlspecialchars($_POST['release_date']) : '' ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>
            </div>

            <!-- Aperçu du logo -->
            <div class="space-y-4">
                <p class="text-sm font-medium text-gray-700 mb-1">Logo automatique :</p>
                <img id="preview_logo" src="<?= isset($_POST['code']) ?
                                                htmlspecialchars("https://pokecardex.b-cdn.net/assets/images/logos/" . strtoupper($_POST['code']) . ".png")
                                                : '#' ?>"
                    alt="Aperçu du logo"
                    class="w-48 h-48 object-contain border border-gray-300 rounded-md">
            </div>
        </div>

        <div class="flex justify-end">
            <a href="series.php" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300 transition">Annuler</a>
            <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition ml-2">
                Enregistrer
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const codeInput = document.getElementById('code');
        const previewLogo = document.getElementById('preview_logo');

        codeInput.addEventListener('input', () => {
            const code = codeInput.value.trim().toUpperCase();
            if (/^[A-Z0-9]{1,10}$/.test(code)) {
                previewLogo.src = `https://pokecardex.b-cdn.net/assets/images/logos/${code}.png`;
            } else {
                previewLogo.src = '#';
            }
        });
    });
</script>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>