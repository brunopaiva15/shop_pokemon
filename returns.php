<?php
// returns.php
session_start();

require_once 'includes/functions.php';

$pageTitle = 'Politique de Retours';
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
            <h2 class="text-2xl font-bold text-gray-900">1. Acceptation des retours</h2>
            <p>BDPokéCards accepte les retours uniquement en cas d'erreur manifeste dans la commande (ex. produit ne correspondant pas à la description). Le client doit fournir une preuve claire (photo, vidéo ou autre) attestant du problème constaté.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">2. Délai pour signaler un problème</h2>
            <p>Le client dispose d'un délai de 7 jours calendaires à compter de la réception du colis pour signaler un problème à l'adresse suivante : <a href="mailto:commandes@bd-pokecards.ch" class="text-blue-600 underline">commandes@bd-pokecards.ch</a>.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">3. Conditions de retour</h2>
            <p>La carte doit être retournée dans son état et emballage d'origine (sleeve, toploader, etc.). Tout retour dégradé, incomplet ou manipulé de manière abusive (ex. déchirure volontaire) sera refusé.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">4. Frais de retour et frais d'annulation</h2>
            <p>Les frais de retour sont à la charge exclusive du client. En cas d'annulation validée, des frais de traitement peuvent être appliqués, pouvant aller jusqu'à 20% du montant total de la commande selon les cas.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">5. Procédure de retour</h2>
            <p>Le client doit envoyer sa demande à <a href="mailto:commandes@bd-pokecards.ch" class="text-blue-600 underline">commandes@bd-pokecards.ch</a> en joignant toute preuve utile. En cas d'accord, le retour se fait par envoi postal à ses frais, sans étiquette de retour fournie par le vendeur.</p>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-900">6. Motifs de refus</h2>
            <p>Les retours sont systématiquement refusés en cas de simple changement d'avis, d'endommagement post-réception non signalé dans les délais, ou d'usure due à la manipulation du client.</p>
        </div>

        <p class="mt-8 text-sm text-gray-500">En commandant sur notre site, le client reconnaît avoir pris connaissance de la présente politique de retours et en accepter les conditions.</p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>