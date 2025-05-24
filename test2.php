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
    <title>BDPokéCards - Bannière Facebook finale</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen px-4 py-8">

    <div class="relative w-full max-w-[1200px] aspect-[1200/628] bg-white shadow-xl overflow-hidden rounded-xl">

        <!-- Cartes à gauche -->
        <img src="<?= $cards[0]['image_url'] ?>" class="absolute w-28 pointer-events-none select-none z-0" style="top: 40px; left: 20px; rotate: -12deg;">
        <img src="<?= $cards[1]['image_url'] ?>" class="absolute w-32 pointer-events-none select-none z-0" style="top: 250px; left: 80px; rotate: -2deg;">
        <img src="<?= $cards[2]['image_url'] ?>" class="absolute w-28 pointer-events-none select-none z-0" style="bottom: 40px; left: 20px; rotate: 10deg;">

        <!-- Cartes à droite -->
        <img src="<?= $cards[3]['image_url'] ?>" class="absolute w-28 pointer-events-none select-none z-0" style="top: 40px; right: 20px; rotate: 12deg;">
        <img src="<?= $cards[4]['image_url'] ?>" class="absolute w-32 pointer-events-none select-none z-0" style="top: 200px; right: 80px; rotate: 2deg;">
        <img src="<?= $cards[5]['image_url'] ?>" class="absolute w-28 pointer-events-none select-none z-0" style="bottom: 40px; right: 20px; rotate: -10deg;">

        <!-- Bloc central -->
        <div class="absolute inset-0 flex items-center justify-center z-10 px-6">
            <div class="bg-white/90 backdrop-blur-lg rounded-xl px-10 py-8 text-center shadow-xl max-w-xl w-full border border-gray-200">
                <div class="flex items-center justify-center gap-4 mb-5">
                    <img src="assets/images/logo_noir.png" alt="Logo BDPokéCards" class="h-12 w-12 object-contain">
                    <h1 class="text-4xl font-extrabold text-gray-900">BDPokéCards</h1>
                </div>
                <p class="text-lg text-gray-700 mb-4">La nouvelle boutique en ligne Pokémon TCG ouvre officiellement</p>
                <div class="inline-block bg-blue-600 text-white font-semibold text-base px-6 py-2 rounded-full shadow">
                    Le 1er juin 2025
                </div>
                <p class="mt-5 text-sm text-gray-500">
                    Une boutique 100% suisse
                    <img src="assets/images/switzerland.png" alt="Drapeau suisse" class="inline-block w-4 h-4 align-middle">
                    pour les vrais dresseurs !
                </p>
            </div>
        </div>

    </div>

</body>

</html>