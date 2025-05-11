<?php
// includes/db.php
require_once 'config.php';

function getDbConnection()
{
    static $conn = null;

    if ($conn === null) {
        try {
            $conn = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            die('Erreur de connexion à la base de données : ' . $e->getMessage());
        }
    }

    return $conn;
}

// Créer les tables si elles n'existent pas
function initDatabase()
{
    $conn = getDbConnection();

    // Table des séries Pokémon
    $conn->exec("CREATE TABLE IF NOT EXISTS series (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        release_date DATE,
        logo_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Table des cartes
    $conn->exec("CREATE TABLE IF NOT EXISTS cards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        series_id INT,
        name VARCHAR(100) NOT NULL,
        card_number VARCHAR(20) NOT NULL,
        rarity VARCHAR(50),
        condition VARCHAR(10) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        image_url VARCHAR(255),
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE SET NULL
    )");

    // Table des commandes
    $conn->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(100) NOT NULL,
        customer_email VARCHAR(100) NOT NULL,
        customer_address TEXT NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Table des détails de commande
    $conn->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        card_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE
    )");

    // Table utilisateurs pour l'admin
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        is_admin BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// Initialiser la base de données
initDatabase();
