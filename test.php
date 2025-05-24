<?php
require_once 'includes/db.php';
$conn = getDbConnection();
$stmt = $conn->query("SELECT image_url FROM cards WHERE image_url IS NOT NULL ORDER BY RAND() LIMIT 6");
$cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BDPokéCards - Instagram Teaser</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .card-float {
            @apply absolute w-24 sm:w-28 opacity-70 pointer-events-none select-none drop-shadow-lg;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-[640px] aspect-square relative bg-white rounded-xl border border-gray-200 overflow-hidden shadow-lg">

        <!-- Cartes flottantes -->
        <?php if (count($cards) >= 6): ?>
            <img src="<?= $cards[0]['image_url'] ?>" class="card-float rotate-[-15deg] top-4 left-6">
            <img src="<?= $cards[1]['image_url'] ?>" class="card-float rotate-[10deg] top-6 right-4">
            <img src="<?= $cards[2]['image_url'] ?>" class="card-float rotate-[-8deg] bottom-6 left-4">
            <img src="<?= $cards[3]['image_url'] ?>" class="card-float rotate-[12deg] bottom-4 right-6">
            <img src="<?= $cards[4]['image_url'] ?>" class="hidden sm:block card-float rotate-[-6deg] top-1/2 left-[-20px] transform -translate-y-1/2">
            <img src="<?= $cards[5]['image_url'] ?>" class="hidden sm:block card-float rotate-[8deg] top-1/2 right-[-20px] transform -translate-y-1/2">
        <?php endif; ?>

        <!-- Contenu principal -->
        <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-6 z-10">
            <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-800 mb-3 drop-shadow">BDPokéCards</h1>
            <p class="text-base sm:text-lg text-gray-600 mb-5">Ta nouvelle boutique Pokémon ouvre officiellement</p>
            <div class="bg-blue-600 text-white font-semibold text-sm sm:text-base px-5 py-2 rounded-full shadow-md">
                Le 1er juin 2025
            </div>
            <p class="text-sm text-gray-500 mt-6">Ne manque pas le lancement !</p>
        </div>

    </div>

</body>
</html>