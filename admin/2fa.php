<?php
session_start();
require_once '../vendor/autoload.php';
require_once '../includes/functions.php';

use OTPHP\TOTP;

if (empty($_SESSION['2fa_user_id'])) {
    header('Location: login.php');
    exit;
}

$user = getUserById($_SESSION['2fa_user_id']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    $totp = TOTP::create($user['totp_secret']);
    if ($totp->verify($code)) {
        // Succès : login finalisé
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = true;
        unset($_SESSION['2fa_user_id']);
        header('Location: index.php');
        exit;
    } else {
        $error = 'Code 2FA invalide';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>2FA - Administration BDPokéCards</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold mb-4 text-center">Validation 2FA</h1>
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="post" class="space-y-4">
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Code d’authentification</label>
                <input type="text" name="code" id="code" required class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-md hover:bg-blue-700 transition">Valider</button>
        </form>
    </div>
</body>

</html>