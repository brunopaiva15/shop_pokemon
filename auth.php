<?php
session_start();

// Mot de passe à protéger (à personnaliser)
const PASSWORD = '031523';

// Si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['password']) && $_POST['password'] === PASSWORD) {
        $_SESSION['authenticated'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = "Mot de passe incorrect.";
    }
}

// Si déjà connecté, rediriger
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Authentification</title>
    <link rel="stylesheet" href="assets/css/style.css"> <!-- si tu as un fichier CSS -->
    <style>
        body {
            font-family: sans-serif;
            background: #f3f4f6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        form {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        input[type="password"] {
            width: 100%;
            padding: .5rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            padding: .5rem 1rem;
            background: #1f2937;
            color: white;
            border: none;
            border-radius: 5px;
        }

        .error {
            color: red;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <form method="post">
        <h2>Accès protégé</h2>
        <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <button type="submit">Entrer</button>
    </form>
</body>

</html>