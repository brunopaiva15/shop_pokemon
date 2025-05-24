<?php
require_once 'includes/db.php';
$conn = getDbConnection();
$stmt = $conn->query("SELECT image_url FROM cards WHERE image_url IS NOT NULL ORDER BY RAND() LIMIT 4");
$cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BDPokéCards - Instagram</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .card-floating {
            @apply absolute w-24 sm:w-28 opacity-30 pointer-events-none select-none drop-shadow-md;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="w-full max-w-[640px] aspect-square bg-white relative rounded-xl shadow-lg overflow-hidden border border-gray-200">

    <!-- Cartes d'arrière-plan -->
    <?php if (count($cards) === 4): ?>
        <img src="<?= $cards[0]['image_url'] ?>" class="card-floating top-4 left-4 rotate-[-12deg]">
        <img src="<?= $cards[1]['image_url'] ?>" class="card-floating top-4 right-4 rotate-[8deg]">
        <img src="<?= $cards[2]['image_url'] ?>" class="card-floating bottom-4 left-6 rotate-[10deg]">
        <img src="<?= $cards[3]['image_url'] ?>" class="card-floating bottom-4 right-6 rotate-[-6deg]">
    <?php endif; ?>

    <!-- Contenu principal -->
    <div class="absolute inset-0 flex items-center justify-center z-10 px-4">
        <div class="bg-white/80 backdrop-blur-md rounded-lg px-6 py-8 text-center shadow-md max-w-sm w-full">
            <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-800 mb-3">BDPokéCards</h1>
            <p class="text-base text-gray-600 mb-5">Ta nouvelle boutique Pokémon ouvre officiellement</p>
            <div class="inline-block bg-blue-600 text-white font-semibold text-sm px-5 py-2 rounded-full shadow">
                Le 1er juin 2025
            </div>
            <p class="mt-6 text-sm text-gray-500">Ne manque pas le lancement !</p>
        </div>
    </div>
</div>

</body>
</html>