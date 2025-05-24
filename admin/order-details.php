<?php
// admin/order-details.php

require_once '../includes/functions.php';
require_once 'includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = 'ID de commande non valide';
    $_SESSION['flash_type'] = 'error';
    header('Location: orders.php');
    exit;
}

$orderId = (int)$_GET['id'];
$order = getOrderById($orderId);

if (!$order) {
    $_SESSION['flash_message'] = 'Commande non trouvée';
    $_SESSION['flash_type'] = 'error';
    header('Location: orders.php');
    exit;
}

$orderItems = getOrderItems($orderId);
$pageTitle = 'Commande #' . $orderId;

$statusClasses = [
    'pending'    => 'bg-yellow-100 text-yellow-800',
    'processing' => 'bg-blue-100 text-blue-800',
    'completed'  => 'bg-green-100 text-green-800',
    'cancelled'  => 'bg-red-100 text-red-800',
    'refunded'   => 'bg-gray-100 text-gray-800'
];

$statusText = [
    'pending'    => 'En attente',
    'processing' => 'En traitement',
    'completed'  => 'Complétée',
    'cancelled'  => 'Annulée',
    'refunded'   => 'Remboursée'
];

$success = false;
$error   = '';
$oldStatus = $order['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $newStatus = sanitizeInput($_POST['status']);

    if (array_key_exists($newStatus, $statusText)) {
        if (updateOrderStatus($orderId, $newStatus)) {
            if ($oldStatus !== 'cancelled' && $newStatus === 'cancelled') {
                $items = getOrderItems($orderId);
                foreach ($items as $item) {
                    updateCardConditionStock($item['card_id'], $item['condition_code'], $item['quantity']);
                }
            }
            $success = true;
            $order['status'] = $newStatus;
        } else {
            $error = 'Erreur lors de la mise à jour du statut';
        }
    } else {
        $error = 'Statut non valide';
    }
}

if (isset($_POST['send_email_notification']) && $_POST['send_email_notification'] == 1) {
    $statusToNotify = $_POST['status_to_notify'];

    $messageTemplates = [
        'processing' => "Nous sommes en train d’emballer soigneusement votre commande. Elle sera bientôt remise à La Poste !",
        'completed'  => "Votre commande a été remise à La Poste ! Elle est désormais en route vers vous. 📦",
        'cancelled'  => "Votre commande a été annulée. Si vous avez des questions, n’hésitez pas à nous contacter.",
        'refunded'   => "Nous avons procédé au remboursement de votre commande. Celui-ci apparaîtra sous peu sur votre moyen de paiement."
    ];

    $mailTitles = [
        'processing' => '🛍 Votre commande est en traitement',
        'completed'  => '📦 Votre commande a été envoyée',
        'cancelled'  => '❌ Votre commande a été annulée',
        'refunded'   => '💸 Votre commande a été remboursée',
    ];

    if (isset($messageTemplates[$statusToNotify]) && !empty($order['customer_email'])) {
        require_once '../vendor/autoload.php';
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->Host = 'bd-pokecards.ch';
            $mail->SMTPAuth = true;
            $mail->Username = 'commandes';
            $mail->Password = 'Musik09x'; // 🔐 à sécuriser
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('commandes@bd-pokecards.ch', 'BDPokécards');
            $mail->addAddress($order['customer_email'], $order['customer_name']);
            $mail->addReplyTo('contact@bd-pokecards.ch', 'BDPokécards');

            $mail->isHTML(true);
            $mail->Subject = 'Mise à jour de votre commande BD' . $orderId . ' - ' . 'BDPokécards';

            ob_start(); ?>
            <div style="font-family: Arial, sans-serif; color: #333; background-color: #f9f9f9; padding: 20px;">
                <table style="max-width: 600px; margin: auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
                    <tr style="background-color: #1f2937; color: #fff;">
                        <td style="padding: 20px; text-align: center;">
                            <img src="https://bd-pokecards.ch/assets/images/logo.png" alt="BDPokécards" style="max-height: 60px; margin-bottom: 10px;">
                            <h1 style="margin: 0; font-size: 22px;">
                                <?= htmlspecialchars($mailTitles[$statusToNotify] ?? 'Mise à jour de votre commande BD' . $orderId) ?>
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px;">
                            <p>Bonjour <?= htmlspecialchars($order['customer_name']) ?>,</p>
                            <p><?= nl2br(htmlspecialchars($messageTemplates[$statusToNotify])) ?></p>
                            <p style="margin-top: 30px;">
                                En cas de souci, n'hésitez pas à nous contacter : <a href="mailto:commandes@bd-pokecards.ch">commandes@bd-pokecards.ch</a>.
                            </p>
                        </td>
                    </tr>
                    <tr style="background-color: #f3f4f6; text-align: center; font-size: 12px;">
                        <td style="padding: 15px;">
                            © <?= date('Y') ?> BDPokécards — Tous droits réservés
                        </td>
                    </tr>
                </table>
            </div>
<?php
            $mail->Body = ob_get_clean();
            $mail->AltBody = "Bonjour {$order['customer_name']},\n\n" . $messageTemplates[$statusToNotify] . "\n\nEn cas de souci : commandes@bd-pokecards.ch";

            $mail->send();
            $_SESSION['flash_message'] = 'Statut mis à jour et e-mail de notification envoyé.';
        } catch (Exception $e) {
            $_SESSION['flash_message'] = "Statut mis à jour mais l’e-mail n’a pas pu être envoyé.";
        }
    }
}

$shippingLabels = [
    'shr_1RSLlAH9F6vNTkOcS0wFavLY' => '🎁 Livraison gratuite',
    'shr_1RRHZ4H9F6vNTkOclAr1ctuZ' => '✉️ Lettre standard',
    'shr_1RSLkqH9F6vNTkOcuCiGXjZg' => '✉️ Lettre suivie',
    'shr_1RSLl0H9F6vNTkOcgpjAaTb7' => '📦 Colis PostPac Economy'
];
$shippingRateId = $order['shipping_rate_id'] ?? null;
$shippingLabel = $shippingLabels[$shippingRateId] ?? 'Méthode inconnue';
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Détails de la commande #<?php echo $orderId; ?></h2>
        <a href="orders.php" class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left mr-1"></i> Retour aux commandes
        </a>
    </div>

    <?php if (!empty($_SESSION['flash_message'])): ?>
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6">
            <p><?= htmlspecialchars($_SESSION['flash_message']) ?></p>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <p>Le statut de la commande a été mis à jour avec succès.</p>
            <?php if ($oldStatus !== 'cancelled' && $order['status'] === 'cancelled'): ?>
                <p class="mt-2">Le stock des cartes a également été réajusté.</p>
                <!-- Afficher les cartes annulées -->
                <div class="mt-4">
                    <h4 class="font-semibold">Cartes remises en stock :</h4>
                    <ul class="list-disc list-inside">
                        <?php foreach ($orderItems as $item): ?>
                            <li><?php echo htmlspecialchars($item['name']); ?> (<?php echo htmlspecialchars($item['quantity']); ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($success && $oldStatus !== $order['status'] && !isset($_POST['send_email_notification'])): ?>
        <form method="post" class="mt-4 mb-4 bg-yellow-50 border border-yellow-300 rounded-md p-4 space-y-3"
            onsubmit="return confirm('Souhaitez-vous envoyer un e-mail au client pour l’informer du changement de statut ?');">
            <input type="hidden" name="send_email_notification" value="1">
            <input type="hidden" name="status_to_notify" value="<?= htmlspecialchars($order['status']) ?>">
            <p class="text-sm text-gray-700">
                Le statut est désormais <strong><?= $statusText[$order['status']] ?></strong>.
                Souhaitez-vous envoyer un e-mail de notification au client ?
            </p>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Envoyer l’e-mail de notification
            </button>
        </form>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <p><?php echo $error; ?></p>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">Informations de la commande</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">ID de commande:</span>
                    <span class="font-medium">#<?php echo $orderId; ?></span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-600">Date:</span>
                    <span><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-600">Montant total:</span>
                    <span class="font-bold"><?php echo formatPrice($order['total_amount']); ?></span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-600">Livraison :</span>
                    <span class="font-medium"><?= htmlspecialchars($shippingLabel) ?></span>
                </div>

                <?php if (!empty($order['stripe_payment_intent'])): ?>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Stripe :</span>
                        <a href="https://dashboard.stripe.com/payments/<?php echo htmlspecialchars($order['stripe_payment_intent']); ?>" target="_blank" class="text-blue-600 underline">Voir le paiement Stripe</a>
                    </div>
                <?php elseif (!empty($order['stripe_link_id'])): ?>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Stripe :</span>
                        <span class="text-gray-500 italic">Paiement non finalisé</span>
                    </div>
                <?php endif; ?>

                <form method="post" class="mt-4">
                    <label for="status" class="block text-sm font-medium text-gray-700">Statut de la commande :</label>
                    <select name="status" id="status" class="mt-1 p-2 border border-gray-300 rounded-md">
                        <?php foreach ($statusText as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $order['status'] === $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="ml-2 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Mettre à jour</button>
                </form>
            </div>
        </div>

        <div>
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">Informations du client</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Nom:</span>
                    <span><?php echo htmlspecialchars($order['customer_name']) ?: '<span class="italic text-gray-500">Non spécifié</span>'; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Email:</span>
                    <?php if (!empty($order['customer_email'])): ?>
                        <a href="mailto:<?php echo htmlspecialchars($order['customer_email']); ?>" class="text-blue-600 hover:underline">
                            <?php echo htmlspecialchars($order['customer_email']); ?>
                        </a>
                    <?php else: ?>
                        <span class="italic text-gray-500">Non spécifié</span>
                    <?php endif; ?>
                </div>
                <div>
                    <span class="text-gray-600">Adresse:</span>
                    <div class="mt-1 border border-gray-200 rounded-md p-3 bg-gray-50">
                        <?php echo nl2br(htmlspecialchars($order['customer_address'] ?? 'Non spécifiée')); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-10">
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">Cartes commandées</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left">Image</th>
                            <th class="px-4 py-2 text-left">Nom</th>
                            <th class="px-4 py-2 text-left">Numéro</th>
                            <th class="px-4 py-2 text-left">Série</th>
                            <th class="px-4 py-2 text-left">Condition</th>
                            <th class="px-4 py-2 text-right">Quantité</th>
                            <th class="px-4 py-2 text-right">Prix unitaire</th>
                            <th class="px-4 py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                            <tr class="border-t">
                                <td class="px-4 py-2">
                                    <img src="<?= htmlspecialchars($item['image_url'] ?? 'assets/images/card-placeholder.png') ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-12 h-12 object-contain">
                                </td>
                                <td class="px-4 py-2"><?= htmlspecialchars($item['name']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($item['card_number']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($item['series_name']) ?> (<?= htmlspecialchars($item['series_code']) ?>)</td>
                                <td class="px-4 py-2"><?= CARD_CONDITIONS[$item['condition_code']] ?? 'Inconnue' ?></td>
                                <td class="px-4 py-2 text-right"><?= (int) $item['quantity'] ?></td>
                                <td class="px-4 py-2 text-right"><?= formatPrice($item['price']) ?></td>
                                <td class="px-4 py-2 text-right"><?= formatPrice($item['quantity'] * $item['price']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>