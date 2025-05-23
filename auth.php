<?php
session_start();
require_once 'includes/db.php';

const PASSWORD = '031523';

// Authentification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['password']) && $_POST['password'] === PASSWORD) {
        $_SESSION['authenticated'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = "Mot de passe incorrect.";
    }
}

if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: index.php');
    exit;
}

// Récupération de 6 cartes aléatoires avec image pour une meilleure dispersion
$conn = getDbConnection();
$stmt = $conn->query("SELECT name, image_url FROM cards WHERE image_url IS NOT NULL ORDER BY RAND() LIMIT 6");
$teaserCards = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>BDPokéCards - Ouverture prochaine</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen flex items-center justify-center px-4 relative overflow-hidden">

    <!-- Cartes teaser dispersées en fond -->
    <?php if (count($teaserCards) >= 6): ?>
        <div class="absolute inset-0 pointer-events-none select-none">
            <!-- Cartes desktop : dispersion complète -->
            <!-- Carte en haut à gauche -->
            <img src="<?= htmlspecialchars($teaserCards[0]['image_url']) ?>" alt=""
                class="w-20 sm:w-24 absolute top-8 left-4 rotate-[-15deg] hidden sm:block">

            <!-- Carte en haut à droite -->
            <img src="<?= htmlspecialchars($teaserCards[1]['image_url']) ?>" alt=""
                class="w-20 sm:w-24 absolute top-12 right-8 rotate-[12deg] hidden sm:block">

            <!-- Carte au milieu gauche -->
            <img src="<?= htmlspecialchars($teaserCards[2]['image_url']) ?>" alt=""
                class="w-18 sm:w-20 absolute top-1/2 left-2 transform -translate-y-1/2 rotate-[-8deg] hidden sm:block">

            <!-- Carte au milieu droite -->
            <img src="<?= htmlspecialchars($teaserCards[3]['image_url']) ?>" alt=""
                class="w-18 sm:w-20 absolute top-1/2 right-4 transform -translate-y-1/2 rotate-[18deg] hidden sm:block">

            <!-- Carte en bas à gauche -->
            <img src="<?= htmlspecialchars($teaserCards[4]['image_url']) ?>" alt=""
                class="w-20 sm:w-24 absolute bottom-16 left-6 rotate-[10deg] hidden sm:block">

            <!-- Carte en bas à droite -->
            <img src="<?= htmlspecialchars($teaserCards[5]['image_url']) ?>" alt=""
                class="w-20 sm:w-24 absolute bottom-8 right-2 rotate-[-20deg] hidden sm:block">

            <!-- Cartes mobile : en haut et en bas du formulaire -->
            <!-- Carte en haut mobile -->
            <img src="<?= htmlspecialchars($teaserCards[0]['image_url']) ?>" alt=""
                class="w-16 absolute top-4 left-1/2 transform -translate-x-1/2 rotate-[-10deg] block sm:hidden">

            <!-- Carte en bas mobile -->
            <img src="<?= htmlspecialchars($teaserCards[1]['image_url']) ?>" alt=""
                class="w-16 absolute bottom-4 left-1/2 transform -translate-x-1/2 rotate-[12deg] block sm:hidden">
        </div>
    <?php endif; ?>

    <div class="bg-white border border-gray-200 shadow-md rounded-md p-6 max-w-xl w-full relative z-10">
        <h1 class="text-2xl font-extrabold text-gray-700 mb-2 text-center">BDPokéCards</h1>
        <p class="text-sm text-gray-500 text-center mb-4">Ta boutique en ligne préférée de cartes Pokémon arrive bientôt.</p>

        <div class="mb-4 text-center">
            <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full">
                <i class="fas fa-lock mr-2 text-xs"></i> Accès réservé
            </span>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-200 text-red-800 px-4 py-2 rounded mb-4 text-sm">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <button
                type="submit"
                class="w-full py-2 px-4 bg-gray-800 text-white font-semibold rounded-md hover:bg-gray-900 transition">
                Entrer
            </button>
        </form>

        <p class="mt-6 text-xs text-gray-400 text-center">Merci pour ta patience, jeune dresseur !</p>
    </div>

</body>

</html>