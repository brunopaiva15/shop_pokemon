<?php
// initialize.php
// Script pour initialiser la base de données et créer un compte administrateur

require_once 'includes/functions.php';

// Vérifier si un compte administrateur existe déjà
$conn = getDbConnection();
$stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
$adminCount = $stmt->fetch()['count'];

if ($adminCount > 0) {
    echo "Un compte administrateur existe déjà. Pour créer un nouvel administrateur, utilisez phpMyAdmin ou modifiez ce script.";
    exit;
}

// Création d'un compte administrateur par défaut
$adminUsername = 'admin';
$adminPassword = 'admin123'; // Changer ce mot de passe en production!

// Créer l'administrateur
if (createUser($adminUsername, $adminPassword, true)) {
    echo "Compte administrateur créé avec succès!<br>";
    echo "Nom d'utilisateur: $adminUsername<br>";
    echo "Mot de passe: $adminPassword<br>";
    echo "<strong>IMPORTANT: Changez ce mot de passe immédiatement après la première connexion!</strong><br>";
} else {
    echo "Erreur lors de la création du compte administrateur.";
}

// Ajouter quelques séries d'exemple
$sampleSeries = [
    ['name' => 'Épée et Bouclier', 'release_date' => '2019-11-15'],
    ['name' => 'Soleil et Lune', 'release_date' => '2016-12-02'],
    ['name' => 'XY', 'release_date' => '2014-02-05'],
    ['name' => 'Noir et Blanc', 'release_date' => '2011-04-06']
];

$seriesAdded = 0;
foreach ($sampleSeries as $series) {
    if (addSeries($series['name'], $series['release_date'])) {
        $seriesAdded++;
    }
}

echo "<br>$seriesAdded séries d'exemple ajoutées.<br>";
echo "<br>Initialisation terminée. Supprimez ce fichier pour des raisons de sécurité.";
