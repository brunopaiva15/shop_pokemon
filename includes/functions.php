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
        INNER JOIN card_conditions cc ON c.id = cc.card_id
        WHERE cc.quantity > 0
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
    // Utiliser la même logique que getAllCardsWithoutPagination mais avec LIMIT
    $conn = getDbConnection();

    // Construire la requête de base
    $query = "
        SELECT c.*, s.name as series_name, MIN(cc.price) as min_price 
        FROM cards c 
        LEFT JOIN series s ON c.series_id = s.id 
        JOIN card_conditions cc ON c.id = cc.card_id
        WHERE cc.quantity > 0";
    $params = [];

    // Ajouter les conditions de filtrage
    if ($seriesId) {
        $query .= " AND c.series_id = ?";
        $params[] = $seriesId;
    }

    if ($condition) {
        $query .= " AND cc.condition_code = ?";
        $params[] = $condition;
    }

    // Regrouper par carte pour éviter les doublons
    $query .= " GROUP BY c.id";

    // Ajouter le tri
    if ($sortBy == 'price') {
        // Pour le tri par prix, on utilise le prix minimum de chaque carte
        $query .= " ORDER BY min_price " . $sortOrder;
    } else {
        $query .= " ORDER BY c." . $sortBy . " " . $sortOrder;
    }

    // Ajouter la pagination
    if ($limit !== null) {
        $query .= " LIMIT ?, ?";
        $params[] = (int)$offset;
        $params[] = (int)$limit;
    }

    // Exécuter la requête
    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    // Récupérer les cartes
    $cards = $stmt->fetchAll();

    // Pour chaque carte, récupérer son état optimal (meilleur prix ou meilleur état selon le tri)
    foreach ($cards as &$card) {
        // Récupérer tous les états disponibles pour cette carte
        $stmt = $conn->prepare("
            SELECT * FROM card_conditions 
            WHERE card_id = ? AND quantity > 0
            ORDER BY " . ($sortBy == 'price' ? "price " . $sortOrder : "condition_code ASC") . "
            LIMIT 1
        ");
        $stmt->execute([$card['id']]);
        $bestCondition = $stmt->fetch();

        if ($bestCondition) {
            // Ajouter les informations de l'état optimal à la carte
            $card['condition_code'] = $bestCondition['condition_code'];
            $card['card_condition'] = $bestCondition['condition_code']; // Pour compatibilité
            $card['price'] = $bestCondition['price'];
            $card['quantity'] = $bestCondition['quantity'];
        }
    }

    return $cards;
}

function countAllCards($seriesId = null, $condition = null)
{
    $conn = getDbConnection();

    // Construire la requête de base
    $query = "
        SELECT COUNT(DISTINCT c.id) as total 
        FROM cards c 
        JOIN card_conditions cc ON c.id = cc.card_id
        WHERE cc.quantity > 0";
    $params = [];

    // Ajouter les conditions de filtrage
    if ($seriesId) {
        $query .= " AND c.series_id = ?";
        $params[] = $seriesId;
    }

    if ($condition) {
        $query .= " AND cc.condition_code = ?";
        $params[] = $condition;
    }

    // Exécuter la requête
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $result = $stmt->fetch();

    return (int)$result['total'];
}

function getAllCardsWithoutPagination($seriesId = null, $condition = null, $rarity = null, $variant = null, $priceMin = null, $priceMax = null, $sortBy = 'created_at', $sortOrder = 'DESC')
{
    $conn = getDbConnection();

    // Construire la requête de base
    $query = "
        SELECT c.*, s.name as series_name, MIN(cc.price) as price
        FROM cards c 
        LEFT JOIN series s ON c.series_id = s.id 
        JOIN card_conditions cc ON c.id = cc.card_id
        WHERE cc.quantity > 0";
    $params = [];

    // Ajouter les conditions de filtrage
    if ($seriesId) {
        $query .= " AND c.series_id = ?";
        $params[] = $seriesId;
    }

    if ($condition) {
        $query .= " AND cc.condition_code = ?";
        $params[] = $condition;
    }

    if ($rarity) {
        $query .= " AND c.rarity = ?";
        $params[] = $rarity;
    }

    if ($variant) {
        $query .= " AND c.variant = ?";
        $params[] = $variant;
    }

    if ($priceMin !== null) {
        $query .= " AND cc.price >= ?";
        $params[] = $priceMin;
    }

    if ($priceMax !== null) {
        $query .= " AND cc.price <= ?";
        $params[] = $priceMax;
    }

    // Regrouper par carte pour éviter les doublons
    $query .= " GROUP BY c.id";

    // Ajouter le tri
    if ($sortBy == 'price') {
        // Pour le tri par prix, on utilise le prix minimum de chaque carte
        $query .= " ORDER BY price " . $sortOrder;
    } else {
        $query .= " ORDER BY c." . $sortBy . " " . $sortOrder;
    }

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
    $stmt = $conn->prepare("
        SELECT c.*, s.name as series_name 
        FROM cards c 
        LEFT JOIN series s ON c.series_id = s.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$id]);
    $card = $stmt->fetch();

    if ($card) {
        // Récupérer les conditions disponibles pour cette carte
        $stmt = $conn->prepare("
            SELECT * FROM card_conditions 
            WHERE card_id = ? 
            ORDER BY condition_code
        ");
        $stmt->execute([$id]);
        $card['conditions'] = $stmt->fetchAll();
    }

    return $card;
}

function addCard($seriesId, $name, $cardNumber, $rarity, $variant = 'normal', $description = null, $imageUrl = null)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        INSERT INTO cards (series_id, name, card_number, rarity, variant, description, image_url) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $success = $stmt->execute([$seriesId, $name, $cardNumber, $rarity, $variant, $description, $imageUrl]);

    if ($success) {
        return $conn->lastInsertId();
    }
    return false;
}

function addCardCondition($cardId, $condition, $price, $quantity)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        INSERT INTO card_conditions (card_id, condition_code, price, quantity) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE price = ?, quantity = quantity + ?
    ");
    return $stmt->execute([$cardId, $condition, $price, $quantity, $price, $quantity]);
}

function updateCardCondition($cardId, $condition, $price, $quantity)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        UPDATE card_conditions 
        SET price = ?, quantity = ? 
        WHERE card_id = ? AND condition_code = ?
    ");
    return $stmt->execute([$price, $quantity, $cardId, $condition]);
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
    $stmt = $conn->prepare("
        SELECT 
            oi.*, 
            c.name, 
            c.card_number, 
            c.image_url, 
            s.name AS series_name,
            s.code AS series_code
        FROM order_items oi
        JOIN cards c ON oi.card_id = c.id
        JOIN series s ON c.series_id = s.id
        WHERE oi.order_id = ?
    ");
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

// Fonctions pour le panier
function initCart()
{
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

function addToCart($cardId, $conditionCode, $quantity = 1)
{
    initCart();

    // Vérifier que la carte existe et est disponible
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        SELECT c.*, s.name as series_name, cc.price, cc.quantity as available_quantity 
        FROM cards c 
        JOIN series s ON c.series_id = s.id
        JOIN card_conditions cc ON c.id = cc.card_id
        WHERE c.id = ? AND cc.condition_code = ?
    ");
    $stmt->execute([$cardId, $conditionCode]);
    $card = $stmt->fetch();

    if (!$card || $card['available_quantity'] <= 0) {
        return false;
    }

    // Limiter la quantité au stock disponible
    $quantity = min($quantity, $card['available_quantity']);

    // Générer un ID unique pour cet élément du panier
    $cartItemId = uniqid();

    // Vérifier si cette carte avec cet état est déjà dans le panier
    $existingItemIndex = null;
    foreach ($_SESSION['cart'] as $index => $item) {
        if ($item['card_id'] == $cardId && $item['condition_code'] == $conditionCode) {
            $existingItemIndex = $index;
            $cartItemId = $item['id']; // Réutiliser l'ID existant
            break;
        }
    }

    if ($existingItemIndex !== null) {
        // Mettre à jour la quantité
        $newQuantity = $_SESSION['cart'][$existingItemIndex]['quantity'] + $quantity;
        $newQuantity = min($newQuantity, $card['available_quantity']); // Limiter au stock disponible
        $_SESSION['cart'][$existingItemIndex]['quantity'] = $newQuantity;
    } else {
        // Ajouter au panier
        $_SESSION['cart'][] = [
            'id' => $cartItemId,
            'card_id' => $cardId,
            'condition_code' => $conditionCode,
            'quantity' => $quantity,
            'price' => $card['price']
        ];
    }

    return true;
}

function getCartItems()
{
    initCart();

    if (empty($_SESSION['cart'])) {
        return [];
    }

    $items = [];
    $conn = getDbConnection();

    foreach ($_SESSION['cart'] as $cartItem) {
        $stmt = $conn->prepare("
            SELECT c.*, s.name as series_name, cc.price, cc.quantity as available_quantity 
            FROM cards c 
            JOIN series s ON c.series_id = s.id
            JOIN card_conditions cc ON c.id = cc.card_id
            WHERE c.id = ? AND cc.condition_code = ?
        ");
        $stmt->execute([$cartItem['card_id'], $cartItem['condition_code']]);
        $card = $stmt->fetch();

        if ($card) {
            // Vérifier si la quantité en stock a changé
            $availableQuantity = (int)$card['available_quantity'];
            $cartQuantity = min($cartItem['quantity'], $availableQuantity);

            // Si la quantité a changé, mettre à jour le panier
            if ($cartQuantity != $cartItem['quantity']) {
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['id'] == $cartItem['id']) {
                        $item['quantity'] = $cartQuantity;
                        break;
                    }
                }
            }

            // Ajouter les informations nécessaires
            $items[] = [
                'cart_id' => $cartItem['id'],
                'id' => $card['id'],
                'name' => $card['name'],
                'card_number' => $card['card_number'],
                'series_name' => $card['series_name'],
                'image_url' => $card['image_url'],
                'condition_code' => $cartItem['condition_code'],
                'price' => $card['price'],
                'cart_quantity' => $cartQuantity,
                'available_quantity' => $availableQuantity,
                'subtotal' => $card['price'] * $cartQuantity
            ];
        }
    }

    return $items;
}

function getCartItemsWithStripeData()
{
    initCart();

    if (empty($_SESSION['cart'])) {
        return [];
    }

    $items = [];
    $conn = getDbConnection();

    foreach ($_SESSION['cart'] as $cartItem) {
        $stmt = $conn->prepare("
            SELECT c.*, s.name as series_name, cc.price, cc.quantity as available_quantity 
            FROM cards c 
            JOIN series s ON c.series_id = s.id
            JOIN card_conditions cc ON c.id = cc.card_id
            WHERE c.id = ? AND cc.condition_code = ?
        ");
        $stmt->execute([$cartItem['card_id'], $cartItem['condition_code']]);
        $card = $stmt->fetch();

        if ($card) {
            $availableQuantity = (int)$card['available_quantity'];
            $cartQuantity = min($cartItem['quantity'], $availableQuantity);

            $items[] = [
                'cart_id' => $cartItem['id'],
                'id' => $card['id'],
                'name' => $card['name'],
                'card_number' => $card['card_number'],
                'series_name' => $card['series_name'],
                'series_id' => $card['series_id'],
                'image_url' => $card['image_url'],
                'condition_code' => $cartItem['condition_code'],
                'price' => $card['price'],
                'cart_quantity' => $cartQuantity,
                'available_quantity' => $availableQuantity,
                'stripe_product_id' => $card['stripe_product_id'],
                'subtotal' => $card['price'] * $cartQuantity
            ];
        }
    }

    return $items;
}

function getCartTotal()
{
    $items = getCartItems();
    $total = 0;

    foreach ($items as $item) {
        $total += $item['subtotal'];
    }

    return $total;
}

function getCartItemCount()
{
    initCart();

    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += $item['quantity'];
    }

    return $count;
}

function updateCartItemQuantity($cartItemId, $quantity)
{
    initCart();

    if ($quantity <= 0) {
        removeCartItem($cartItemId);
        return true;
    }

    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $cartItemId) {
            // Vérifier le stock disponible
            $conn = getDbConnection();
            $stmt = $conn->prepare("
                SELECT cc.quantity as available_quantity 
                FROM card_conditions cc
                WHERE cc.card_id = ? AND cc.condition_code = ?
            ");
            $stmt->execute([$item['card_id'], $item['condition_code']]);
            $result = $stmt->fetch();

            if ($result) {
                $availableQuantity = (int)$result['available_quantity'];
                $item['quantity'] = min($quantity, $availableQuantity);
            } else {
                $item['quantity'] = $quantity;
            }
            return true;
        }
    }

    return false;
}

function removeCartItem($cartItemId)
{
    initCart();

    foreach ($_SESSION['cart'] as $index => $item) {
        if ($item['id'] == $cartItemId) {
            unset($_SESSION['cart'][$index]);
            $_SESSION['cart'] = array_values($_SESSION['cart']); // Réindexer le tableau
            return true;
        }
    }

    return false;
}

function clearCart()
{
    $_SESSION['cart'] = [];
}

// Vérifier la disponibilité des articles du panier
function checkCartItemsAvailability()
{
    $cartItems = getCartItems();
    $unavailableItems = [];

    foreach ($cartItems as $item) {
        if ($item['cart_quantity'] > $item['available_quantity']) {
            $unavailableItems[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'condition_code' => $item['condition_code'],
                'requested' => $item['cart_quantity'],
                'available' => $item['available_quantity']
            ];
        }
    }

    return $unavailableItems;
}

// Mettre à jour le stock après une commande
function updateStockAfterOrder($orderItems)
{
    $conn = getDbConnection();
    $success = true;

    foreach ($orderItems as $item) {
        $stmt = $conn->prepare("
            UPDATE card_conditions
            SET quantity = quantity - ?
            WHERE card_id = ? AND condition_code = ?
        ");
        $result = $stmt->execute([$item['quantity'], $item['card_id'], $item['condition_code']]);

        if (!$result) {
            $success = false;
        }
    }

    return $success;
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
