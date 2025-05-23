</main>

<!-- Bannière informative visible et contrastée -->
<div class="bg-gray-100 text-gray-800 py-4 border-y border-gray-300 shadow-sm">
    <div class="container mx-auto flex flex-col md:flex-row justify-between items-center gap-4 text-base md:text-lg font-medium px-4 text-center md:text-left">

        <div class="flex items-center gap-2">
            <i class="fas fa-map-marker-alt text-gray-600 text-xl"></i>
            <span class="flex items-center gap-2">
                Basé en Suisse dans le Jura Bernois
                <img src="<?php echo SITE_URL; ?>/assets/images/switzerland.png" alt="Drapeau suisse" class="w-5 h-5 inline-block">
            </span>
        </div>

        <div class="flex items-center gap-2">
            <i class="fas fa-headset text-gray-600 text-xl"></i>
            <span>Support client qualifié – réponse sous 24h</span>
        </div>

        <div class="flex items-center gap-2">
            <i class="fas fa-shield-alt text-gray-600 text-xl"></i>
            <span>Paiement sécurisé par Stripe</span>
        </div>

        <div class="flex items-center gap-2">
            <i class="fas fa-truck text-gray-600 text-xl"></i>
            <a href="https://service.post.ch/ekp-web/ui/list" target="_blank" class="underline hover:text-gray-900 transition">
                Suivi de commande
            </a>
        </div>

        <!-- FAQ -->
        <div class="flex items-center gap-2">
            <i class="fas fa-question-circle text-gray-600 text-xl"></i>
            <a href="<?php echo SITE_URL; ?>/faq.php" class="underline hover:text-gray-900 transition">
                FAQ
            </a>
        </div>

    </div>
</div>

<footer class="bg-gray-800 text-white py-6">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h3 class="text-xl font-bold mb-4">BDPokéCards</h3>
                <p class="text-gray-300">Votre boutique spécialisée en cartes Pokémon d’occasion en excellent état, directement sorties du booster et protégées sous sleeve dès l’ouverture.</p>
            </div>

            <div>
                <h3 class="text-xl font-bold mb-4">Liens rapides</h3>
                <ul class="space-y-2">
                    <li><a href="<?php echo SITE_URL; ?>" class="text-gray-300 hover:text-white transition">Accueil</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/cart.php" class="text-gray-300 hover:text-white transition">Panier</a></li>
                    <li><a href="https://service.post.ch/ekp-web/ui/list" target="_blank" class="text-gray-300 hover:text-white transition">Suivi de commande</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/faq.php" class="text-gray-300 hover:text-white transition">FAQ</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-xl font-bold mb-4">Contact</h3>
                <p class="text-gray-300">Pour toute question, n'hésitez pas à nous contacter :</p>
                <a href="mailto:<?php echo ADMIN_EMAIL; ?>" class="text-yellow-400 hover:text-yellow-300 transition">
                    <?php echo ADMIN_EMAIL; ?>
                </a>
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-700 text-center text-gray-400">
            <p>&copy; <?php echo date('Y'); ?> BDPokéCards. Tous droits réservés.</p>
            <p class="mt-2 text-sm">Pokémon est une marque déposée de Nintendo/Creatures Inc./GAME FREAK inc. Ce site n'est pas affilié à Pokémon Company.</p>
        </div>
    </div>
</footer>

<!-- Scripts JS -->
<?php if (isset($includeFiltersScript) && $includeFiltersScript): ?>
    <script src="<?php echo SITE_URL; ?>/assets/js/filters.js"></script>
<?php endif; ?>
</body>

</html>