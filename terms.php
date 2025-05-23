<?php
// terms.php
session_start();

require_once 'includes/functions.php';

$pageTitle = 'Conditions Générales de Vente';
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
            <h2 class="text-2xl font-bold text-gray-900">1. Champ d'application</h2>
            <p>Les présentes conditions générales de vente s'appliquent à toutes les transactions conclues entre BDPokéCards, ci-après dénommé "le Vendeur", et toute personne effectuant un achat via le site <strong>bd-pokecards.ch</strong>, ci-après dénommée "le Client".</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">2. Commandes</h2>
            <p>Le Client passe commande via le site internet. Toute commande constitue une offre d'achat ferme des produits sélectionnés. Le Vendeur se réserve le droit de refuser toute commande en cas d'erreur, d'indisponibilité ou de violation des présentes conditions.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">3. Prix et paiement</h2>
            <p>Les prix sont indiqués en francs suisses (CHF) et ne sont pas soumis à la TVA, le Vendeur n'étant pas assujetti (chiffre d'affaires < 100'000 CHF/an). Le paiement s'effectue via les moyens acceptés sur le site et doit être réalisé avant expédition.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">4. Livraison</h2>
            <p>Les produits sont expédiés via La Poste Suisse. Les délais de livraison sont indicatifs (1 à 3 jours ouvrables). Le Vendeur ne saurait être tenu responsable des retards dus au transporteur ou à des circonstances externes.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">5. Retour et remboursement</h2>
            <p>Toutes les informations concernant les retours et remboursements sont spécifiées sur la page <a href="retours.php" class="text-blue-600 underline">Politique de retours</a>.
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">6. Garantie</h2>
            <p>Toutes les cartes sont vérifiées et protégées. Le Client est invité à signaler tout problème dans un délai de 7 jours après réception.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">7. Responsabilité</h2>
            <p>Le Vendeur décline toute responsabilité pour des dommages indirects ou consécutifs liés à l'utilisation ou l'indisponibilité temporaire du site ou à des erreurs dans les descriptions des produits.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">8. Droit applicable et for juridique</h2>
            <p>Les présentes conditions sont régies par le droit suisse. Le for juridique est fixé au domicile du Vendeur, en Suisse.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">9. Protection des données</h2>
            <p>Les informations personnelles sont traitées de manière confidentielle. Une politique de confidentialité détaillée est disponible.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">10. Modifications</h2>
            <p>Le Vendeur se réserve le droit de modifier les présentes CGV à tout moment. Les nouvelles conditions s'appliqueront dès leur publication sur le site.</p>
        </div>

        <hr>

        <p>En passant commande, le Client reconnaît avoir lu, compris et accepté les présentes conditions générales de vente.</p>
        <p>
            Pour toute question, veuillez contacter
            <a href="mailto:commandes@bd-pokecards.ch" class="text-blue-600 underline">commandes@bd-pokecards.ch</a>
            ou par téléphone au <a href="tel:0792898907" class="text-blue-600 underline">079 289 89 07</a>.
        </p>
        <p>Rédigé le 23.05.2025.</p>
        <p>BDPokéCards</p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>