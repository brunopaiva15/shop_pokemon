<?php
// includes/config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'pokemon_shop'); // À modifier en production
define('DB_PASS', '26Stvi13!'); // À modifier en production
define('DB_NAME', 'pokemon_shop');
define('SITE_URL', 'http://localhost/pokemon'); // À modifier en production
define('ADMIN_EMAIL', 'bvergastapaiva@gmail.com');

// États des cartes
define('CARD_CONDITIONS', [
    'MT' => 'Mint',
    'NM' => 'Near Mint',
    'EX' => 'Excellent',
    'GD' => 'Good',
    'LP' => 'Light Played',
    'PL' => 'Played',
    'PO' => 'Poor'
]);

// Raretés des cartes
define('CARD_RARITIES', [
    'C' => 'Commune (●)',
    'UC' => 'Peu Commune (◆)',
    'R' => 'Rare (★)',
    'DR' => 'Double Rare / EX (★★)',
    'IR' => 'Illustration Rare / AR (★)',
    'UR' => 'Ultra Rare / Full Art (★★)',
    'SAR' => 'Illustration Rare Spéciale / Alternative (★★)',
    'HR' => 'Hyper Rare / Gold (★★★)'
]);

// Variantes des cartes
define('CARD_VARIANTS', [
    'normal'           => 'Normal',
    'holo'             => 'Holo',
    'reverse_holo'     => 'Reverse Holo',
    'pokeball_holo'    => 'Poké Ball Holo',
    'masterball_holo'  => 'Master Ball Holo',
    'cosmos_holo'      => 'Cosmos Holo',
    'special_edition'  => 'Édition spéciale'
]);
