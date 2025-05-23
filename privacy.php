<?php
// privacy.php
session_start();

require_once 'includes/functions.php';

$pageTitle = 'Politique de Confidentialité';
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
            <h2 class="text-2xl font-bold text-gray-900">1. Données collectées</h2>
            <p>Lors du passage de commande sur notre site, nous collectons les informations suivantes : nom, prénom, adresse e-mail, numéro de téléphone, adresse postale, ainsi que les informations de paiement nécessaires (type de carte, 4 derniers chiffres, date d'expiration, type et émetteur), uniquement via Stripe.</p>
            <p>Un cookie de session PHP est créé automatiquement pour maintenir la session active. D'autres cookies peuvent être utilisés par Stripe lors du chargement du lien de paiement, mais ils ne sont pas générés ni gérés par notre site.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">2. Finalité de la collecte</h2>
            <p>Les données collectées sont exclusivement utilisées pour le traitement des commandes et la communication avec le client. Des campagnes d'information ou de marketing peuvent être envisagées dans le futur, uniquement après obtention du consentement explicite du client.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">3. Partage des données</h2>
            <p>Les données peuvent être partagées uniquement avec des prestataires tiers impliqués dans le traitement de la commande, notamment Stripe pour les paiements et La Poste Suisse pour la livraison. Ces prestataires n'ont accès qu'aux informations strictement nécessaires à leurs prestations.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">4. Sécurité des données</h2>
            <p>Les données sont stockées de manière sécurisée sur un serveur hébergé chez Infomaniak (Genève, Suisse), avec accès restreint et communication chiffrée via HTTPS. Les paiements sont gérés par Stripe, qui applique ses propres standards de sécurité. Pour plus d'informations, vous pouvez consulter <a href="https://stripe.com/fr-ch/privacy" class="text-blue-600 underline" target="_blank">leur politique de confidentialité</a>.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">5. Durée de conservation</h2>
            <p>Les données liées aux commandes sont conservées pendant une durée de 10 ans conformément aux obligations comptables suisses. Passé ce délai, elles sont supprimées ou anonymisées.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">6. Droits des utilisateurs</h2>
            <p>Conformément à la nouvelle Loi fédérale sur la protection des données (nLPD), vous disposez d'un droit d'accès, de rectification, d'opposition et de suppression de vos données personnelles. Pour exercer ces droits, vous pouvez nous contacter à l'adresse suivante : <a href="mailto:commandes@bd-pokecards.ch" class="text-blue-600 underline">commandes@bd-pokecards.ch</a>.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">7. Contact et réclamations</h2>
            <p>Pour toute question ou réclamation relative à la présente politique de confidentialité ou au traitement de vos données, veuillez nous contacter à <a href="mailto:commandes@bd-pokecards.ch" class="text-blue-600 underline">commandes@bd-pokecards.ch</a>.</p>
            <p>Vous pouvez également vous adresser au Préposé fédéral à la protection des données et à la transparence (PFPDT) en cas de litige.</p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>