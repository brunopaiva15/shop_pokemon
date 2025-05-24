<?php
require_once 'includes/db.php';
$conn = getDbConnection();

$stmt = $conn->query("
    SELECT c.image_url
    FROM cards c
    JOIN card_conditions cc ON cc.card_id = c.id
    WHERE c.image_url IS NOT NULL
    ORDER BY cc.price DESC
    LIMIT 6
");
$cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BDPokéCards - Instagram</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="w-full max-w-[640px] aspect-square relative bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">

    <!-- Cartes Pokémon flottantes -->
    <?php if (count($cards) === 6): ?>
        <!-- Haut -->
        <img src="<?= $cards[0]['image_url'] ?>" class="absolute w-20 top-4 left-4 rotate-[-10deg] z-0 pointer-events-none select-none">
        <img src="<?= $cards[1]['image_url'] ?>" class="absolute w-20 top-3 left-1/2 -translate-x-1/2 rotate-[8deg] z-0 pointer-events-none select-none">
        <img src="<?= $cards[2]['image_url'] ?>" class="absolute w-20 top-4 right-4 rotate-[10deg] z-0 pointer-events-none select-none">

        <!-- Bas -->
        <img src="<?= $cards[3]['image_url'] ?>" class="absolute w-20 bottom-4 left-4 rotate-[12deg] z-0 pointer-events-none select-none">
        <img src="<?= $cards[4]['image_url'] ?>" class="absolute w-20 bottom-3 left-1/2 -translate-x-1/2 rotate-[-8deg] z-0 pointer-events-none select-none">
        <img src="<?= $cards[5]['image_url'] ?>" class="absolute w-20 bottom-4 right-4 rotate-[-12deg] z-0 pointer-events-none select-none">
    <?php endif; ?>

    <!-- Bloc central -->
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