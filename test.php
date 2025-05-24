<?php
// publication-instagram.php
require_once 'includes/db.php';

// Récupération de 6 cartes aléatoires avec image
$conn = getDbConnection();
$stmt = $conn->query("SELECT image_url FROM cards WHERE image_url IS NOT NULL ORDER BY RAND() LIMIT 6");
$cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>BDPokéCards - Instagram</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
        }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center">

    <div class="w-full max-w-[640px] aspect-square relative bg-white overflow-hidden rounded-lg shadow-lg border border-gray-200">

        <!-- Cartes Pokémon dispersées -->
        <?php if (count($cards) >= 6): ?>
            <!-- Desktop -->
            <img src="<?= $cards[0]['image_url'] ?>" class="hidden sm:block w-20 absolute top-4 left-4 rotate-[-15deg] opacity-60 pointer-events-none select-none">
            <img src="<?= $cards[1]['image_url'] ?>" class="hidden sm:block w-20 absolute top-4 right-4 rotate-[12deg] opacity-60 pointer-events-none select-none">
            <img src="<?= $cards[2]['image_url'] ?>" class="hidden sm:block w-20 absolute bottom-4 left-4 rotate-[10deg] opacity-60 pointer-events-none select-none">
            <img src="<?= $cards[3]['image_url'] ?>" class="hidden sm:block w-20 absolute bottom-4 right-4 rotate-[-10deg] opacity-60 pointer-events-none select-none">
            <img src="<?= $cards[4]['image_url'] ?>" class="hidden sm:block w-20 absolute top-1/2 left-[-20px] transform -translate-y-1/2 rotate-[6deg] opacity-60 pointer-events-none select-none">
            <img src="<?= $cards[5]['image_url'] ?>" class="hidden sm:block w-20 absolute top-1/2 right-[-20px] transform -translate-y-1/2 rotate-[-6deg] opacity-60 pointer-events-none select-none">

            <!-- Mobile -->
            <img src="<?= $cards[0]['image_url'] ?>" class="block sm:hidden w-20 absolute top-3 left-1/2 transform -translate-x-1/2 rotate-[-10deg] opacity-60 pointer-events-none select-none">
            <img src="<?= $cards[1]['image_url'] ?>" class="block sm:hidden w-20 absolute bottom-3 left-1/2 transform -translate-x-1/2 rotate-[12deg] opacity-60 pointer-events-none select-none">
        <?php endif; ?>

        <!-- Contenu principal -->
        <div class="absolute inset-0 flex flex-col items-center justify-center px-6 text-center z-10">
            <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-800 mb-2 drop-shadow">BDPokéCards</h1>
            <p class="text-base sm:text-lg text-gray-600 mb-4">Ta nouvelle boutique Pokémon ouvre officiellement</p>
            <div class="inline-block bg-blue-600 text-white text-sm sm:text-base font-semibold px-4 py-2 rounded-full shadow-lg mb-6">
                Le 1er juin 2025
            </div>
            <p class="text-sm text-gray-500">Suis-nous pour ne pas rater le lancement !</p>
            <div class="mt-4 flex justify-center space-x-4 text-blue-500 text-xl">
                <i class="fab fa-instagram"></i>
                <i class="fab fa-tiktok"></i>
                <i class="fab fa-discord"></i>
            </div>
        </div>
    </div>

</body>
</html>