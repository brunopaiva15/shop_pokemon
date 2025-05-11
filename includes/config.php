<?php
// includes/config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'pokemon_shop'); // À modifier en production
define('DB_PASS', '26Stvi13!'); // À modifier en production
define('DB_NAME', 'pokemon_shop');
define('SITE_URL', 'http://localhost/pokemon-shop'); // À modifier en production
define('ADMIN_EMAIL', 'mail@mail.com');

// États des cartes
define('CARD_CONDITIONS', [
    'MT' => 'Mint',
    'NM' => 'Near Mint',
    'EX' => 'Excellent',
    'GD' => 'Good',
    'LP' => 'Light Played',
    'MP' => 'Moderately Played',
    'HP' => 'Heavily Played'
]);
