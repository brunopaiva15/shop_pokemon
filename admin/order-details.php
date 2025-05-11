<?php
// admin/order-details.php

// Vérifier si l'ID de la commande est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = 'ID de commande non valide';
    $_SESSION['flash_type'] = 'error';
    header('Location: orders.php');
    exit;
}

$orderId = (int)$_GET['id'];

// Récupérer les informations de la commande
$order = getOrderById($orderId);

// Si la commande n'existe pas, rediriger vers la liste des commandes
if (!$order) {
    $_SESSION['flash_message'] = 'Commande non trouvée';
    $_SESSION['flash_type'] = 'error';
    header('Location: orders.php');
    exit;
}

// Récupérer les articles de la commande
$orderItems = getOrderItems($orderId);

// Définir le titre de la page
$pageTitle = 'Commande #' . $orderId;

// Inclure l'en-tête
require_once 'includes/header.php';

// Définir les couleurs et textes des statuts
$statusClasses = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'processing' => 'bg-blue-100 text-blue-800',
    'completed' => 'bg-green-100 text-green-800',
    'cancelled' => 'bg-red-100 text-red-800'
];

$statusText = [
    'pending' => 'En attente',
    'processing' => 'En traitement',
    'completed' => 'Complétée',
    'cancelled' => 'Annulée'
];

// Traitement du formulaire pour mettre à jour le statut
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $newStatus = sanitizeInput($_POST['status']);

    // Vérifier que le statut est valide
    if (array_key_exists($newStatus, $statusText)) {
        if (updateOrderStatus($orderId, $newStatus)) {
            $success = true;
            $order['status'] = $newStatus; // Mettre à jour le statut dans l'objet de commande
        } else {
            $error = 'Erreur lors de la mise à jour du statut';
        }
    } else {
        $error = 'Statut non valide';
    }
}
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Détails de la commande #<?php echo $orderId; ?></h2>
        <a href="orders.php" class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left mr-1"></i> Retour aux commandes
        </a>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <p>Le statut de la commande a été mis à jour avec succès.</p>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <p><?php echo $error; ?></p>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Informations de la commande -->
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
                    <span class="text-gray-600">Méthode de paiement:</span>
                    <span><?php echo htmlspecialchars($order['payment_method']); ?></span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Statut:</span>
                    <form method="POST" action="order-details.php?id=<?php echo $orderId; ?>" class="flex items-center space-x-2">
                        <select name="status" class="p-1 border border-gray-300 rounded-md text-sm">
                            <?php foreach ($statusText as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo $order['status'] == $key ? 'selected' : ''; ?>>
                                    <?php echo $value; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="bg-blue-600 text-white p-1 rounded-md text-sm">
                            <i class="fas fa-save"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Informations du client -->
        <div>
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">Informations du client</h3>

            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Nom:</span>
                    <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-600">Email:</span>
                    <a href="mailto:<?php echo htmlspecialchars($order['customer_email']); ?>" class="text-blue-600 hover:underline">
                        <?php echo htmlspecialchars($order['customer_email']); ?>
                    </a>
                </div>

                <div>
                    <span class="text-gray-600">Adresse:</span>
                    <div class="mt-1 border border-gray-200 rounded-md p-3 bg-gray-50">
                        <?php echo nl2br(htmlspecialchars($order['customer_address'])); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Articles de la commande -->
<div class="bg-white rounded-lg shadow-md p-6">
    <h3 class="text-lg font-semibold mb-4 border-b pb-2">Articles commandés</h3>

    <?php if (empty($orderItems)): ?>
        <p class="text-gray-500">Aucun article dans cette commande.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Carte</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Numéro</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix unitaire</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantité</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sous-total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="w-12 h-12 bg-gray-100 rounded-md overflow-hidden">
                                    <img src="<?php echo SITE_URL . '/' . ($item['image_url'] ?: 'assets/images/card-placeholder.png'); ?>"
                                        alt="<?php echo htmlspecialchars($item['name']); ?>"
                                        class="w-full h-full object-contain">
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap font-medium">
                                <a href="../card-details.php?id=<?php echo $item['card_id']; ?>" target="_blank" class="text-blue-600 hover:underline">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </a>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($item['card_number']); ?></td>
                            <td class="px-4 py-2 whitespace-nowrap"><?php echo formatPrice($item['price']); ?></td>
                            <td class="px-4 py-2 whitespace-nowrap"><?php echo $item['quantity']; ?></td>
                            <td class="px-4 py-2 whitespace-nowrap font-medium"><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50">
                        <td colspan="5" class="px-4 py-2 text-right font-bold">Total:</td>
                        <td class="px-4 py-2 font-bold"><?php echo formatPrice($order['total_amount']); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>

    <!-- Boutons d'action -->
    <div class="mt-6 flex justify-end space-x-2">
        <button type="button" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300 transition" onclick="window.print();">
            <i class="fas fa-print mr-1"></i> Imprimer
        </button>

        <a href="mailto:<?php echo htmlspecialchars($order['customer_email']); ?>" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition">
            <i class="fas fa-envelope mr-1"></i> Contacter le client
        </a>
    </div>
</div>

<style>
    @media print {

        header,
        footer,
        .no-print,
        button,
        a {
            display: none !important;
        }

        body {
            background-color: white !important;
        }

        .bg-white {
            box-shadow: none !important;
            border: none !important;
        }
    }
</style>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>