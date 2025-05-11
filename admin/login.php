<?php
// admin/login.php
session_start();
require_once '../includes/functions.php';

// Si l'utilisateur est déjà connecté et est admin, rediriger vers le tableau de bord
if (isUserLoggedIn() && isAdmin()) {
    header('Location: index.php');
    exit;
}

$error = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        $user = loginUser($username, $password);

        if ($user && $user['is_admin']) {
            // Connexion réussie, initialiser la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = true;

            // Rediriger vers le tableau de bord
            header('Location: index.php');
            exit;
        } else {
            $error = 'Identifiants incorrects ou vous n\'avez pas les droits d\'administration';
        }
    }
}

// Titre de la page
$pageTitle = 'Connexion';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Administration Pokemon Shop</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-gray-800 text-white py-4 px-6">
            <div class="flex items-center justify-center">
                <i class="fas fa-cog text-2xl mr-2"></i>
                <h2 class="text-xl font-bold">Administration Pokemon Shop</h2>
            </div>
        </div>

        <div class="p-6">
            <h1 class="text-2xl font-bold mb-6 text-center"><?php echo $pageTitle; ?></h1>

            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" required
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                    <input type="password" id="password" name="password" required
                        class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-md hover:bg-blue-700 transition">
                        <i class="fas fa-sign-in-alt mr-1"></i> Se connecter
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center">
                <a href="../index.php" class="text-blue-600 hover:text-blue-800 transition">
                    <i class="fas fa-arrow-left mr-1"></i> Retour à la boutique
                </a>
            </div>
        </div>
    </div>
</body>

</html>