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
<body class="bg-gray-100 flex flex-col items-center justify-center min-h-screen space-y-6 px-4">

<!-- Carré parfaitement carré et arrondi -->
<div id="captureZone"
     class="w-full max-w-[640px] aspect-square relative bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">

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
</div>

<!-- Boutons -->
<div class="flex flex-wrap justify-center gap-4">
    <button onclick="shuffleCards()" class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded">Disposition aléatoire</button>
    <button onclick="resetCards()" class="bg-gray-500 hover:bg-gray-600 text-white text-sm px-4 py-2 rounded">Disposition normale</button>
</div>

<script>
const positions = [
    { top: '1rem', left: '1rem', rotate: -10 },
    { top: '0.75rem', left: '50%', transform: 'translateX(-50%)', rotate: 8 },
    { top: '1rem', right: '1rem', rotate: 10 },
    { bottom: '1rem', left: '1rem', rotate: 12 },
    { bottom: '0.75rem', left: '50%', transform: 'translateX(-50%)', rotate: -8 },
    { bottom: '1rem', right: '1rem', rotate: -12 },
];

function applyPositions(order) {
    for (let i = 0; i < 6; i++) {
        const card = document.getElementById(`card${order[i]}`);
        const pos = positions[i];

        card.style.top = '';
        card.style.bottom = '';
        card.style.left = '';
        card.style.right = '';
        card.style.transform = '';
        card.style.rotate = '';

        for (const key in pos) {
            if (key === 'rotate') {
                card.style.rotate = `${pos[key]}deg`;
            } else {
                card.style[key] = pos[key];
            }
        }
    }
}

function resetCards() {
    applyPositions([0, 1, 2, 3, 4, 5]);
}

function shuffleCards() {
    const order = [0, 1, 2, 3, 4, 5];
    for (let i = order.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [order[i], order[j]] = [order[j], order[i]];
    }
    applyPositions(order);
}

// Initialisation
resetCards();
</script>

</body>
</html>