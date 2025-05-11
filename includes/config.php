<?php
// includes/config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'pokemon_shop'); // À modifier en production
define('DB_PASS', '26Stvi13!'); // À modifier en production
define('DB_NAME', 'pokemon_shop');
define('SITE_URL', 'http://localhost/pokemon'); // À modifier en production
define('ADMIN_EMAIL', 'mail@mail.com');

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
