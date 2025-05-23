<?php
// afficher les erreurs PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../vendor/autoload.php';
require_once '../includes/functions.php';

use OTPHP\TOTP;

session_start();
$user = getUserById($_SESSION['user_id']);
if (!$user || !$user['is_admin']) {
    header('Location: login.php');
    exit;
}

// Génère un secret TOTP si pas déjà fait
if (empty($user['totp_secret'])) {
    $totp = TOTP::create();
    $totp->setLabel($user['username']);
    $totp->setIssuer('BDPokéCards');
    $secret = $totp->getSecret();
    saveUserTOTPSecret($user['id'], $secret);
} else {
    $secret = $user['totp_secret'];
    $totp = TOTP::create($secret);
    $totp->setLabel($user['username']);
    $totp->setIssuer('BDPokéCards');
}

$qrUri = $totp->getProvisioningUri();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activer la double authentification - Administration BDPokéCards</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center">

    <main class="flex flex-col w-full max-w-md mx-auto">
        <div class="bg-white rounded-lg shadow-md p-8 flex flex-col items-center">
            <div class="mb-4 w-full text-center">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Activer la double authentification</h1>
                <p class="text-gray-600">Pour continuer, configure la double authentification sur ton compte admin.<br>
                    <span class="text-blue-700 font-semibold">Scanne le QR code ci-dessous avec ton application 2FA (Google Authenticator, Dashlane, etc.).</span>
                </p>
            </div>
            <div class="flex flex-col items-center w-full">
                <div class="mb-3 p-2 rounded-lg border border-gray-200 bg-gray-50">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?data=<?php echo urlencode($qrUri); ?>&size=200x200"
                        alt="QR Code 2FA" class="w-44 h-44">
                </div>
                <div class="text-sm text-gray-500 mb-2">
                    Si tu ne peux pas scanner, copie ce code :
                </div>
                <div class="bg-gray-100 text-gray-800 font-mono px-4 py-2 rounded text-lg mb-4 select-all border border-gray-200">
                    <?php echo htmlspecialchars($secret); ?>
                </div>
            </div>
            <div class="w-full bg-red-100 border-l-4 border-red-500 text-red-800 px-4 py-3 mb-4 rounded text-sm flex items-center" role="alert">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <div>
                    <b>Attention&nbsp;:</b> Si tu ne sauvegardes pas ce secret ou ce QR code maintenant, tu ne pourras plus l'afficher plus tard.<br>
                    Garde-le précieusement avant de continuer.
                </div>
            </div>
            <form action="index.php" method="get" class="w-full">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-md text-lg shadow transition">
                    Continuer <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </form>
        </div>
        <div class="mt-8 text-center text-gray-400 text-sm">
            <i class="fas fa-lock mr-1"></i> Cette étape est essentielle pour sécuriser ton compte.
        </div>
    </main>
</body>

</html>