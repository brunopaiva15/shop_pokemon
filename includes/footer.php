</main>

<footer class="bg-gray-800 text-white py-6">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h3 class="text-xl font-bold mb-4">BDPokéCards</h3>
                <p class="text-gray-300">Votre boutique spécialisée en cartes Pokémon d’occasion en excellent état, directement sorties du booster et protégées sous sleeve dès l’ouverture.</p>
                <p class="mt-2 text-gray-300">Basé en Suisse.</p>
            </div>

            <div>
                <h3 class="text-xl font-bold mb-4">Liens rapides</h3>
                <ul class="space-y-2">
                    <li><a href="<?php echo SITE_URL; ?>" class="text-gray-300 hover:text-white transition">Accueil</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/cart.php" class="text-gray-300 hover:text-white transition">Panier</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-xl font-bold mb-4">Contact</h3>
                <p class="text-gray-300">Pour toute question, n'hésitez pas à nous contacter par email.</p>
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