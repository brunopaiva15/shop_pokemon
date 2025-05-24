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
    <style>
        .random-card {
            transition: all 0.5s ease;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="w-full max-w-[640px] aspect-square relative bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200" id="cardContainer">

    <!-- Cartes Pokémon flottantes -->
    <?php if (count($cards) === 6): ?>
        <?php foreach ($cards as $i => $card): ?>
            <img
                src="<?= $card['image_url'] ?>"
                class="absolute w-20 z-0 pointer-events-none select-none random-card"
                id="card<?= $i ?>"
            >
        <?php endforeach; ?>
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

    <!-- Boutons -->
    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex gap-3">
        <button onclick="randomizeCards()" class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded">Disposition aléatoire</button>
        <button onclick="resetCards()" class="bg-gray-400 hover:bg-gray-500 text-white text-sm px-4 py-2 rounded">Disposition normale</button>
    </div>
</div>

<script>
const presets = [
    { top: '1rem', left: '1rem', rotate: -10 },
    { top: '0.75rem', left: '50%', transform: 'translateX(-50%)', rotate: 8 },
    { top: '1rem', right: '1rem', rotate: 10 },
    { bottom: '1rem', left: '1rem', rotate: 12 },
    { bottom: '0.75rem', left: '50%', transform: 'translateX(-50%)', rotate: -8 },
    { bottom: '1rem', right: '1rem', rotate: -12 },
];

function resetCards() {
    for (let i = 0; i < 6; i++) {
        const card = document.getElementById(`card${i}`);
        card.style.top = '';
        card.style.bottom = '';
        card.style.left = '';
        card.style.right = '';
        card.style.transform = '';
        card.style.rotate = '';

        const preset = presets[i];
        Object.keys(preset).forEach(k => {
            if (k === 'rotate') {
                card.style.rotate = `${preset[k]}deg`;
            } else {
                card.style[k] = preset[k];
            }
        });
    }
}

function randomizeCards() {
    for (let i = 0; i < 6; i++) {
        const card = document.getElementById(`card${i}`);
        card.style.top = Math.floor(Math.random() * 70 + 5) + '%';
        card.style.left = Math.floor(Math.random() * 70 + 5) + '%';
        card.style.bottom = '';
        card.style.right = '';
        card.style.transform = 'translate(-50%, -50%)';
        card.style.rotate = `${Math.floor(Math.random() * 30 - 15)}deg`;
    }
}

// Initialiser en disposition normale
resetCards();
</script>

</body>
</html>