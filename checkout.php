<?php
// checkout.php

// Définir le titre de la page
$pageTitle = 'Finaliser votre commande';

// Inclure l'en-tête
require_once 'includes/header.php';

// Récupérer les articles du panier
$cartItems = getCartItems();
$cartTotal = getCartTotal();

// Rediriger vers la page du panier si le panier est vide
if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

// Traitement du formulaire de commande
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et valider les données du formulaire
    $customerName = isset($_POST['customer_name']) ? sanitizeInput($_POST['customer_name']) : '';
    $customerEmail = isset($_POST['customer_email']) ? sanitizeInput($_POST['customer_email']) : '';
    $customerAddress = isset($_POST['customer_address']) ? sanitizeInput($_POST['customer_address']) : '';
    $paymentMethod = isset($_POST['payment_method']) ? sanitizeInput($_POST['payment_method']) : '';

    // Validation
    if (empty($customerName)) {
        $errors[] = 'Le nom est obligatoire';
    }

    if (empty($customerEmail)) {
        $errors[] = 'L\'email est obligatoire';
    } elseif (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'email n\'est pas valide';
    }

    if (empty($customerAddress)) {
        $errors[] = 'L\'adresse est obligatoire';
    }

    if (empty($paymentMethod)) {
        $errors[] = 'Veuillez sélectionner un mode de paiement';
    }

    // Si aucune erreur, créer la commande
    if (empty($errors)) {
        $conn = getDbConnection();

        try {
            // Démarrer une transaction
            $conn->beginTransaction();

            // Créer la commande
            $stmt = $conn->prepare("
                INSERT INTO orders (customer_name, customer_email, customer_address, payment_method, total_amount) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$customerName, $customerEmail, $customerAddress, $paymentMethod, $cartTotal]);
            $orderId = $conn->lastInsertId();

            if ($orderId) {
                // Ajouter les articles à la commande
                foreach ($cartItems as $item) {
                    // Récupérer les informations spécifiques à l'état de la carte
                    $stmt = $conn->prepare("
                        SELECT price, quantity FROM card_conditions 
                        WHERE card_id = ? AND condition_code = ?
                    ");
                    $stmt->execute([$item['id'], $item['condition_code']]);
                    $condition = $stmt->fetch();

                    if (!$condition) {
                        throw new Exception('Condition de carte introuvable');
                    }

                    // Vérifier que le stock est suffisant
                    if ($condition['quantity'] < $item['cart_quantity']) {
                        throw new Exception('Stock insuffisant pour ' . $item['name'] . ' [' . CARD_CONDITIONS[$item['condition_code']] . ']');
                    }

                    // Ajouter l'article à la commande
                    $stmt = $conn->prepare("
                        INSERT INTO order_items (order_id, card_id, condition_code, quantity, price) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $orderId,
                        $item['id'],
                        $item['condition_code'],
                        $item['cart_quantity'],
                        $item['price']
                    ]);

                    // Mettre à jour le stock
                    $newQuantity = $condition['quantity'] - $item['cart_quantity'];
                    $stmt = $conn->prepare("
                        UPDATE card_conditions 
                        SET quantity = ? 
                        WHERE card_id = ? AND condition_code = ?
                    ");
                    $stmt->execute([$newQuantity, $item['id'], $item['condition_code']]);
                }

                // Envoyer l'email de confirmation
                sendOrderEmail($orderId);

                // Vider le panier
                clearCart();

                // Valider la transaction
                $conn->commit();

                $success = true;
            } else {
                throw new Exception('Erreur lors de la création de la commande');
            }
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $conn->rollBack();
            $errors[] = 'Une erreur est survenue : ' . $e->getMessage();
        }
    }
}
?>

<div class="bg-white rounded-lg shadow-lg p-6">
    <?php if ($success): ?>
        <!-- Confirmation de commande -->
        <div class="text-center py-8">
            <i class="fas fa-check-circle text-green-500 text-5xl mb-4"></i>
            <h2 class="text-3xl font-bold mb-4">Merci pour votre commande !</h2>
            <p class="text-gray-600 mb-6">
                Votre commande a été enregistrée avec succès. Un email de confirmation a été envoyé à l'adresse indiquée.<br>
                Nous vous contacterons prochainement avec les instructions de paiement.
            </p>
            <a href="index.php" class="bg-gray-800 text-white py-3 px-6 rounded-md hover:bg-gray-900 transition">
                Retour à la boutique
            </a>
        </div>
    <?php else: ?>
        <h1 class="text-3xl font-bold mb-6">Finaliser votre commande</h1>

        <!-- Affichage des erreurs -->
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Formulaire de commande -->
            <div>
                <h2 class="text-xl font-bold mb-4">Informations de contact</h2>

                <form method="POST" action="checkout.php" class="space-y-4">
                    <div>
                        <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-1">Nom complet *</label>
                        <input type="text" id="customer_name" name="customer_name" required
                            value="<?php echo isset($_POST['customer_name']) ? htmlspecialchars($_POST['customer_name']) : ''; ?>"
                            class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    <div>
                        <label for="customer_email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" id="customer_email" name="customer_email" required
                            value="<?php echo isset($_POST['customer_email']) ? htmlspecialchars($_POST['customer_email']) : ''; ?>"
                            class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    <div>
                        <label for="customer_address" class="block text-sm font-medium text-gray-700 mb-1">Adresse complète *</label>
                        <textarea id="customer_address" name="customer_address" required
                            class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 h-24"><?php echo isset($_POST['customer_address']) ? htmlspecialchars($_POST['customer_address']) : ''; ?></textarea>
                        <p class="text-sm text-gray-500 mt-1">Indiquez votre adresse postale complète pour l'envoi des cartes.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mode de paiement *</label>

                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="payment_method" value="bank_transfer"
                                    <?php echo (!isset($_POST['payment_method']) || $_POST['payment_method'] === 'bank_transfer') ? 'checked' : ''; ?>
                                    class="mr-2">
                                <span>Virement bancaire</span>
                            </label>

                            <label class="flex items-center">
                                <input type="radio" name="payment_method" value="twint"
                                    <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'twint') ? 'checked' : ''; ?>
                                    class="mr-2">
                                <span>TWINT</span>
                            </label>

                            <label class="flex items-center">
                                <input type="radio" name="payment_method" value="cash"
                                    <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'cash') ? 'checked' : ''; ?>
                                    class="mr-2">
                                <span>Espèces (remise en main propre)</span>
                            </label>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">Les instructions de paiement vous seront envoyées par email.</p>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full bg-gray-800 text-white py-3 px-6 rounded-md hover:bg-gray-900 transition">
                            Confirmer la commande
                        </button>
                    </div>
                </form>
            </div>

            <!-- Récapitulatif de la commande -->
            <div>
                <h2 class="text-xl font-bold mb-4">Votre commande</h2>

                <div class="bg-gray-100 rounded-lg p-6">
                    <div class="mb-6">
                        <h3 class="font-semibold mb-3 pb-3 border-b border-gray-300">Récapitulatif</h3>

                        <div class="space-y-4 max-h-96 overflow-y-auto">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="flex items-center">
                                    <img src="<?php echo $item['image_url'] ?: 'assets/images/card-placeholder.png'; ?>"
                                        alt="<?php echo htmlspecialchars($item['name']); ?>"
                                        class="w-12 h-12 object-contain mr-3">

                                    <div class="flex-grow">
                                        <div class="font-medium"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="text-sm text-gray-500">
                                            <span class="condition-badge condition-<?php echo $item['condition_code']; ?> mr-1">
                                                <?php echo isset(CARD_CONDITIONS[$item['condition_code']]) ? CARD_CONDITIONS[$item['condition_code']] : 'Non spécifié'; ?>
                                            </span>
                                            <?php echo htmlspecialchars($item['card_number']); ?>
                                        </div>
                                    </div>

                                    <div class="ml-4 text-right">
                                        <div><?php echo $item['cart_quantity']; ?> × <?php echo formatPrice($item['price']); ?></div>
                                        <div class="font-bold"><?php echo formatPrice($item['subtotal']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="border-t border-gray-300 pt-4">
                        <div class="flex justify-between items-center font-bold text-lg">
                            <span>Total</span>
                            <span><?php echo formatPrice($cartTotal); ?></span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <a href="cart.php" class="text-gray-600 hover:text-red-600 transition">
                            <i class="fas fa-arrow-left mr-1"></i> Retour au panier
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>