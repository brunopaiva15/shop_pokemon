<?php
// includes/functions.php
require_once 'db.php';

// Fonctions pour les séries
function getAllSeries()
{
    $conn = getDbConnection();
    $stmt = $conn->query("SELECT * FROM series ORDER BY release_date DESC");
    return $stmt->fetchAll();
}

function getSeriesById($id)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM series WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getSeriesWithCards()
{
    $conn = getDbConnection();
    $stmt = $conn->query("
        SELECT DISTINCT s.* 
        FROM series s
        INNER JOIN cards c ON s.id = c.series_id
        WHERE c.quantity > 0
        ORDER BY s.name ASC
    ");
    return $stmt->fetchAll();
}

function addSeries($name, $code, $releaseDate, $logoUrl = null)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        INSERT INTO series (name, code, release_date, logo_url)
        VALUES (?,    ?,    ?,            ?)
    ");
    return $stmt->execute([$name, $code, $releaseDate, $logoUrl]);
}

function updateSeries($id, $name, $code, $releaseDate, $logoUrl = null)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        UPDATE series
           SET name        = ?,
               code        = ?,
               release_date= ?,
               logo_url    = ?
         WHERE id          = ?
    ");
    return $stmt->execute([$name, $code, $releaseDate, $logoUrl, $id]);
}

function deleteSeries($id)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare("DELETE FROM series WHERE id = ?");
    return $stmt->execute([$id]);
}

// Fonctions pour les cartes - Mise à jour avec card_condition au lieu de condition
function getAllCards($limit = null, $offset = 0, $seriesId = null, $condition = null, $sortBy = 'created_at', $sortOrder = 'DESC')
{
    $conn = getDbConnection();

    $query = "SELECT c.*, s.name as series_name FROM cards c 
              LEFT JOIN series s ON c.series_id = s.id 
              WHERE c.quantity > 0"; // Ajout de cette condition pour filtrer les cartes en stock
    $params = [];

    if ($seriesId) {
        $query .= " AND c.series_id = ?";
        $params[] = $seriesId;
    }

    if ($condition) {
        $query .= " AND c.card_condition = ?";
        $params[] = $condition;
    }

    $query .= " ORDER BY c.$sortBy $sortOrder";

    if ($limit) {
        $query .= " LIMIT ?, ?";
        $params[] = (int)$offset;
        $params[] = (int)$limit;
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function countAllCards($seriesId = null, $condition = null) {
    $conn = getDbConnection();
    
    // Construire la requête de base
    $query = "SELECT COUNT(*) as total FROM cards c WHERE 1=1";
    $params = [];
    
    // Ajouter les conditions de filtrage
    if ($seriesId) {
        $query .= " AND c.series_id = ?";
        $params[] = $seriesId;
    }
    
    if ($condition) {
        $query .= " AND c.card_condition = ?";
        $params[] = $condition;
    }
    
    // Exécuter la requête
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    return (int)$result['total'];
}

function getAllCardsWithoutPagination($seriesId = null, $condition = null, $sortBy = 'created_at', $sortOrder = 'DESC') {
    $conn = getDbConnection();
    
    // Construire la requête de base
    $query = "SELECT c.*, s.name as series_name 
              FROM cards c 
              LEFT JOIN series s ON c.series_id = s.id 
              WHERE 1=1";
    $params = [];
    
    // Ajouter les conditions de filtrage
    if ($seriesId) {
        $query .= " AND c.series_id = ?";
        $params[] = $seriesId;
    }
    
    if ($condition) {
        $query .= " AND c.card_condition = ?";
        $params[] = $condition;
    }
    
    // Ajouter le tri
    $query .= " ORDER BY c.$sortBy $sortOrder";
    
    // Exécuter la requête
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

function cardExists($seriesId, $cardNumber, $variant)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        SELECT * FROM cards 
        WHERE series_id = ? AND card_number = ? AND variant = ?
    ");
    $stmt->execute([$seriesId, $cardNumber, $variant]);
    return $stmt->fetch();
}

function getCardById($id)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT c.*, s.name as series_name FROM cards c 
                           LEFT JOIN series s ON c.series_id = s.id 
                           WHERE c.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function addCard($seriesId, $name, $cardNumber, $rarity, $condition, $price, $quantity, $imageUrl = null, $variant = 'normal', $description = null)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO cards (series_id, name, card_number, rarity, card_condition, price, quantity, image_url, variant, description) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$seriesId, $name, $cardNumber, $rarity, $condition, $price, $quantity, $imageUrl, $variant, $description]);
}

function updateCard($id, $seriesId, $name, $cardNumber, $rarity, $condition, $price, $quantity, $imageUrl = null, $variant = 'normal', $description = null)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE cards SET series_id = ?, name = ?, card_number = ?, rarity = ?, 
                           card_condition = ?, price = ?, quantity = ?, image_url = ?, variant = ?, description = ? 
                           WHERE id = ?");
    return $stmt->execute([$seriesId, $name, $cardNumber, $rarity, $condition, $price, $quantity, $imageUrl, $variant, $description, $id]);
}

function deleteCard($id)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare("DELETE FROM cards WHERE id = ?");
    return $stmt->execute([$id]);
}

function updateCardStock($id, $quantity)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE cards SET quantity = ? WHERE id = ?");
    return $stmt->execute([$quantity, $id]);
}

function searchCards($term, $seriesId = null)
{
    $conn = getDbConnection();

    $query = "SELECT c.*, s.name as series_name FROM cards c 
              LEFT JOIN series s ON c.series_id = s.id 
              WHERE (c.name LIKE ? OR c.card_number LIKE ? OR c.description LIKE ?)";
    $params = ["%$term%", "%$term%", "%$term%"];

    if ($seriesId) {
        $query .= " AND c.series_id = ?";
        $params[] = $seriesId;
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function countCards($seriesId = null, $condition = null)
{
    $conn = getDbConnection();

    $query = "SELECT COUNT(*) as count FROM cards WHERE 1=1";
    $params = [];

    if ($seriesId) {
        $query .= " AND series_id = ?";
        $params[] = $seriesId;
    }

    if ($condition) {
        $query .= " AND card_condition = ?";
        $params[] = $condition;
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return $result['count'];
}

// Fonctions pour les commandes
function createOrder(
    $customerName,
    $customerEmail,
    $customerAddress,
    $paymentMethod,
    $totalAmount
) {
    $conn = getDbConnection();

    // Vérifier s'il y a déjà une transaction active
    try {
        $transactionActive = false;

        // Tenter de démarrer une transaction
        try {
            $conn->beginTransaction();
        } catch (PDOException $e) {
            // Si une exception est levée, c'est qu'une transaction est déjà active
            $transactionActive = true;
        }

        $stmt = $conn->prepare("INSERT INTO orders (customer_name, customer_email, customer_address, payment_method, total_amount) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$customerName, $customerEmail, $customerAddress, $paymentMethod, $totalAmount]);
        $orderId = $conn->lastInsertId();

        // Ne faire un commit que si nous avons démarré la transaction
        if (!$transactionActive) {
            $conn->commit();
        }

        return $orderId;
    } catch (Exception $e) {
        // Ne faire un rollback que si nous avons démarré la transaction
        if (!$transactionActive) {
            $conn->rollBack();
        }
        throw $e;
    }
}

function addOrderItem($orderId, $cardId, $quantity, $price)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, card_id, quantity, price) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$orderId, $cardId, $quantity, $price]);
}

function getOrderById($id)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getOrderItems($orderId)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT oi.*, c.name, c.card_number, c.image_url 
                           FROM order_items oi 
                           JOIN cards c ON oi.card_id = c.id 
                           WHERE oi.order_id = ?");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

function getAllOrders($limit = null, $offset = 0)
{
    $conn = getDbConnection();

    $query = "SELECT * FROM orders ORDER BY created_at DESC";

    if ($limit) {
        $query .= " LIMIT ?, ?";
    }

    $stmt = $conn->prepare($query);

    if ($limit) {
        $stmt->execute([(int)$offset, (int)$limit]);
    } else {
        $stmt->execute();
    }

    return $stmt->fetchAll();
}

function updateOrderStatus($id, $status)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $id]);
}

// Fonctions pour l'authentification
function loginUser($username, $password)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }

    return false;
}

function createUser($username, $password, $isAdmin = false)
{
    $conn = getDbConnection();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, is_admin) VALUES (?, ?, ?)");
    return $stmt->execute([$username, $hashedPassword, $isAdmin ? 1 : 0]);
}

// Fonctions pour le panier (utilisant les sessions)
function initCart()
{
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

function addToCart($cardId, $quantity = 1)
{
    initCart();

    if (isset($_SESSION['cart'][$cardId])) {
        $_SESSION['cart'][$cardId] += $quantity;
    } else {
        $_SESSION['cart'][$cardId] = $quantity;
    }
}

function updateCartItem($cardId, $quantity)
{
    initCart();

    if ($quantity <= 0) {
        unset($_SESSION['cart'][$cardId]);
    } else {
        $_SESSION['cart'][$cardId] = $quantity;
    }
}

function removeFromCart($cardId)
{
    initCart();
    unset($_SESSION['cart'][$cardId]);
}

function clearCart()
{
    $_SESSION['cart'] = [];
}

function getCartItems()
{
    initCart();

    if (empty($_SESSION['cart'])) {
        return [];
    }

    $conn = getDbConnection();
    $cardIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($cardIds), '?'));

    // Modifié pour récupérer également les informations de série
    $stmt = $conn->prepare("SELECT c.*, s.name as series_name 
                           FROM cards c 
                           LEFT JOIN series s ON c.series_id = s.id 
                           WHERE c.id IN ($placeholders)");
    $stmt->execute($cardIds);
    $cards = $stmt->fetchAll();

    $cartItems = [];
    foreach ($cards as $card) {
        $card['cart_quantity'] = $_SESSION['cart'][$card['id']];
        $card['subtotal'] = $card['price'] * $card['cart_quantity'];
        // Assurez-vous que l'état est correctement mappé (card_condition au lieu de condition)
        $card['condition'] = $card['card_condition'];
        $cartItems[] = $card;
    }

    return $cartItems;
}

function getCartTotal()
{
    $cartItems = getCartItems();
    $total = 0;

    foreach ($cartItems as $item) {
        $total += $item['subtotal'];
    }

    return $total;
}

function getCartItemCount()
{
    initCart();

    $count = 0;
    foreach ($_SESSION['cart'] as $quantity) {
        $count += $quantity;
    }

    return $count;
}

// Fonctions d'aide
function sanitizeInput($input)
{
    return trim($input);
}

function formatPrice($price)
{
    return number_format($price, 2, '.', ' ') . ' CHF';
}

function generatePageUrl($page, $params = [])
{
    $url = SITE_URL . '/' . $page;

    if (!empty($params)) {
        $url .= '?' . http_urlencode($params);
    }

    return $url;
}

function http_urlencode($params)
{
    return http_build_query($params);
}

function redirectTo($url)
{
    header("Location: $url");
    exit;
}

function sendOrderEmail($orderId)
{
    $order = getOrderById($orderId);
    $items = getOrderItems($orderId);

    $subject = "Nouvelle commande #$orderId - BDPokéCards";

    $message = "<html><body>";
    $message .= "<h1>Nouvelle commande #$orderId</h1>";
    $message .= "<p><strong>Client:</strong> {$order['customer_name']}</p>";
    $message .= "<p><strong>Email:</strong> {$order['customer_email']}</p>";
    $message .= "<p><strong>Adresse:</strong> {$order['customer_address']}</p>";
    $message .= "<p><strong>Méthode de paiement:</strong> {$order['payment_method']}</p>";

    $message .= "<h2>Articles commandés:</h2>";
    $message .= "<table border='1' cellpadding='5' cellspacing='0'>";
    $message .= "<tr><th>Carte</th><th>Numéro</th><th>Quantité</th><th>Prix unitaire</th><th>Sous-total</th></tr>";

    foreach ($items as $item) {
        $subtotal = $item['quantity'] * $item['price'];
        $message .= "<tr>";
        $message .= "<td>{$item['name']}</td>";
        $message .= "<td>{$item['card_number']}</td>";
        $message .= "<td>{$item['quantity']}</td>";
        $message .= "<td>" . formatPrice($item['price']) . "</td>";
        $message .= "<td>" . formatPrice($subtotal) . "</td>";
        $message .= "</tr>";
    }

    $message .= "<tr><td colspan='4' align='right'><strong>Total:</strong></td><td>" . formatPrice($order['total_amount']) . "</td></tr>";
    $message .= "</table>";
    $message .= "</body></html>";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: {$order['customer_email']}" . "\r\n";

    return mail(ADMIN_EMAIL, $subject, $message, $headers);
}

function isUserLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function isAdmin()
{
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function ensureAdminAuthenticated()
{
    if (!isUserLoggedIn() || !isAdmin()) {
        redirectTo(SITE_URL . '/admin/login.php');
    }
}
