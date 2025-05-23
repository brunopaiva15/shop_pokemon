<?php
// faq.php
session_start();

require_once 'includes/functions.php';

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: auth.php');
    exit;
}

$pageTitle = 'Foire aux questions';
require_once 'includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Bouton pour revenir à la boutique -->
    <div class="mb-6">
        <a href="index.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
            <i class="fas fa-store mr-2"></i> Retour à la boutique
        </a>
    </div>

    <div class="space-y-10">
        <!-- Livraison -->
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Livraison</h2>

            <div class="space-y-6">
                <div>
                    <h3 class="text-xl font-semibold text-blue-700">Où livrez-vous ?</h3>
                    <p class="text-gray-700 mt-1">Pour le moment uniquement en Suisse, mais nous travaillons à étendre notre service à l'Europe prochainement.</p>
                </div>

                <div>
                    <h3 class="text-xl font-semibold text-blue-700">Combien de temps faut-il pour recevoir ma commande ?</h3>
                    <p class="text-gray-700 mt-1">Cela dépend du mode de livraison choisi, mais généralement le délai est de 1 à 3 jours ouvrables.</p>
                </div>

                <div>
                    <h3 class="text-xl font-semibold text-blue-700">Quels transporteurs utilisez-vous ?</h3>
                    <p class="text-gray-700 mt-1">Nous expédions exclusivement via La Poste Suisse.</p>
                </div>

                <div>
                    <h3 class="text-xl font-semibold text-blue-700">Est-ce que mes articles arriveront en un seul morceau ?</h3>
                    <p class="text-gray-700 mt-1">Oui, absolument. Chaque carte est protégée dans une <strong>sleeve</strong> et les cartes de plus de 2.00 CHF sont également envoyées dans un <strong>toploader</strong>. Nous utilisons des enveloppes ou colis spécialement conçus pour éviter toute détérioration.</p>
                </div>
            </div>
        </div>

        <!-- Produits et retours -->
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Produits et retours</h2>

            <div class="space-y-6">
                <div>
                    <h3 class="text-xl font-semibold text-blue-700">Puis-je retourner mon produit ?</h3>
                    <p class="text-gray-700 mt-1">Les retours ne sont pas acceptés, sauf cas exceptionnel. Chaque situation est étudiée au cas par cas, sous réserve d'acceptation.</p>
                </div>

                <div>
                    <h3 class="text-xl font-semibold text-blue-700">Est-ce que tous les produits sont authentiques ?</h3>
                    <p class="text-gray-700 mt-1">Oui, toutes les cartes proposées sont authentiques. Notez que Pokémon est une marque déposée de Nintendo/Creatures Inc./GAME FREAK inc. Ce site n'est pas affilié à la Pokémon Company.</p>
                </div>

                <div>
                    <h3 class="text-xl font-semibold text-blue-700">Comment savoir si une carte est en bon état ?</h3>
                    <p class="text-gray-700 mt-1">Nous indiquons l'état précis de chaque carte (NM, EX, MP, etc.), avec un guide visible en ligne. Toutes les cartes sont contrôlées manuellement par nos soins avant expédition.</p>
                </div>

                <div>
                    <h3 class="text-xl font-semibold text-blue-700">Comment sont déterminés les prix ?</h3>
                    <p class="text-gray-700 mt-1">Nos prix sont calculés selon la rareté, l'état, la demande actuelle et les tendances du marché suisse.</p>
                </div>
            </div>
        </div>

        <!-- Paiement et contact -->
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Paiement et contact</h2>

            <div class="space-y-6">
                <div>
                    <h3 class="text-xl font-semibold text-blue-700">Quels sont les moyens de paiement acceptés ?</h3>
                    <p class="text-gray-700 mt-1">Nous acceptons les paiements par VISA, Mastercard, American Express, TWINT, Apple Pay, Google Pay et Klarna.</p>
                </div>

                <div>
                    <h3 class="text-xl font-semibold text-blue-700">Le paiement est-il sécurisé ?</h3>
                    <p class="text-gray-700 mt-1">Oui, toutes les transactions sont traitées via Stripe, une plateforme de paiement certifiée et sécurisée.</p>
                </div>

                <div>
                    <h3 class="text-xl font-semibold text-blue-700">Vous avez besoin de plus d'informations ?</h3>
                    <p class="text-gray-700 mt-1">N'hésitez pas à nous contacter à l'adresse <a href="mailto:commandes@bd-pokecards.ch" class="text-blue-600 underline">commandes@bd-pokecards.ch</a>.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>