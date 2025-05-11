<?php
// admin/edit-series.php

// Inclure les fonctions nécessaires
require_once '../includes/functions.php';

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

    // Si aucune erreur, mettre à jour la série
    if (empty($errors)) {
        if (updateSeries($seriesId, $name, $code, $releaseDate, $logoUrl)) {
            $success = true;
            // Mettre à jour l'objet série
            $series = getSeriesById($seriesId);
            // Message flash
            $_SESSION['flash_message'] = 'La série a été mise à jour avec succès';
            $_SESSION['flash_type']    = 'success';
        } else {
            $errors[] = 'Erreur lors de la mise à jour de la série';
        }
    }
}

// Récupérer le nombre de cartes associées
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
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <p>La série a été mise à jour avec succès !</p>
        </div>
    <?php endif; ?>

    <form method="POST" action="edit-series.php?id=<?= $seriesId ?>" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Infos série -->
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                    <input type="text" id="name" name="name" required
                        value="<?= htmlspecialchars($series['name']) ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Code abrégé *</label>
                    <input type="text" id="code" name="code" required maxlength="10"
                        value="<?= htmlspecialchars($series['code']) ?>"
                        class="w-full p-2 border border-gray-300 rounded-md"
                        placeholder="Ex. PRE, XY">
                    <p class="text-sm text-gray-500 mt-1">1 à 10 caractères alphanumériques</p>
                </div>
                <div>
                    <label for="release_date" class="block text-sm font-medium text-gray-700 mb-1">Date de sortie</label>
                    <input type="date" id="release_date" name="release_date"
                        value="<?= $series['release_date'] ? date('Y-m-d', strtotime($series['release_date'])) : '' ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <p class="text-sm text-gray-700 mb-1">Nombre de cartes : <strong><?= $cardCount ?></strong></p>
                    <a href="cards.php?series=<?= $seriesId ?>" class="text-blue-600 hover:underline">
                        Voir les cartes
                    </a>
                </div>
            </div>

            <!-- Aperçu du logo -->
            <div class="space-y-4">
                <p class="text-sm font-medium text-gray-700 mb-1">Logo automatique :</p>
                <img src="<?= htmlspecialchars($series['code'] ? "https://pokecardex.b-cdn.net/assets/images/logos/{$series['code']}.png" : '#') ?>"
                    alt="Logo <?= htmlspecialchars($series['name']) ?>"
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

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>