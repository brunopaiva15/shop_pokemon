<?php
// shipping.php
session_start();

require_once 'includes/functions.php';

$pageTitle = 'Paiements et Livraison';
require_once 'includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Bouton pour revenir à la boutique -->
    <div class="mb-6">
        <a href="index.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
            <i class="fas fa-store mr-2"></i> Retour à la boutique
        </a>
    </div>

    <div class="space-y-8 text-gray-700">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">1. Moyens de paiement</h2>
            <p>Tous les paiements sont sécurisés et traités exclusivement via <strong>Stripe</strong>. Les moyens acceptés incluent :</p>
            <ul class="list-disc list-inside mt-2">
                <li>Cartes de crédit et débit : VISA, Mastercard, American Express</li>
                <li>TWINT</li>
                <li>Apple Pay et Google Pay</li>
                <li>Klarna (facture payable sous 30 jours)</li>
            </ul>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">2. Frais de livraison</h2>
            <p>Les frais de livraison sont calculés selon le nombre de cartes commandées et le montant total du panier :</p>

            <h3 class="text-lg font-semibold mt-4">Commandes ≤ 10 cartes</h3>
            <ul class="list-disc list-inside mt-2">
                <li><strong>Lettre standard :</strong> 2.00 CHF</li>
                <li><strong>Lettre avec suivi :</strong> 5.00 CHF</li>
                <li>Le client choisit l'option lors du passage de commande.</li>
            </ul>

            <h3 class="text-lg font-semibold mt-4">Commandes > 10 cartes</h3>
            <ul class="list-disc list-inside mt-2">
                <li><strong>PostPac Economy :</strong> 8.00 CHF (envoi en colis)</li>
            </ul>

            <h3 class="text-lg font-semibold mt-4">Livraison gratuite</h3>
            <p>La livraison est offerte pour toute commande dont le montant total (hors frais de port) atteint ou dépasse <strong>90.00 CHF</strong>.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">3. Emballage</h2>
            <p>Toutes les cartes sont livrées dans une sleeve (pochette en plastique mou) de protection. Les cartes d'une valeur supérieure à CHF 2.00 sont également placées dans un toploader (chargeur en plastique rigide). Les envois sont toujours réalisés dans des enveloppes matelassées pour garantir une protection optimale.</p>
            <div class="flex items-center gap-6 mt-4">
                <div class="text-center">
                    <img src="assets/images/sleeve.png" alt="Toploader" class="h-32 mx-auto">
                    <span class="block mt-2 text-sm text-gray-600">Sleeve</span>
                </div>
                <div class="text-center">
                    <img src="assets/images/toploader.png" alt="Sleeve" class="h-32 mx-auto">
                    <span class="block mt-2 text-sm text-gray-600">Toploader</span>
                </div>
                <div class="text-center">
                    <img src="assets/images/pouch.png" alt="Enveloppe matelassée" class="h-32 mx-auto">
                    <span class="block mt-2 text-sm text-gray-600">Enveloppe matelassée</span>
                </div>
            </div>
            <p class="mt-4 text-sm text-gray-600">Si vous avez des suggestions pour améliorer l'emballage, n'hésitez pas à nous en faire part&nbsp;!</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">4. Délais de livraison</h2>
            <ul class="list-disc list-inside mt-2">
                <li><strong>Lettre standard et suivie :</strong> 1 à 3 jours ouvrables</li>
                <li><strong>PostPac Economy :</strong> 2 à 5 jours ouvrables</li>
            </ul>
            <p>Les délais sont fournis à titre indicatif et peuvent varier selon les conditions de la Poste Suisse.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">5. Suivi de livraison</h2>
            <p>Le suivi est disponible uniquement pour les envois en Lettre suivie ou en colis PostPac Economy. Les envois en lettre standard ne disposent pas de suivi.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">6. Zones desservies</h2>
            <p>La livraison est actuellement disponible <strong>uniquement en Suisse</strong>. Nous travaillons à l'ouverture future des envois vers d'autres pays européens.</p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>