<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BDPokéCards - Publication Instagram</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    
    <!-- Format carré Instagram 1080x1080 -->
    <div class="w-[600px] h-[600px] bg-gray-100 relative overflow-hidden">
        
        <!-- Cartes Pokémon dispersées en fond (comme sur votre page) -->
        <div class="absolute inset-0 pointer-events-none select-none">
            <!-- Carte en haut à gauche -->
            <img src="https://images.pokemontcg.io/swsh1/25_hires.png" alt="Pikachu"
                class="w-20 absolute top-8 left-4 rotate-[-15deg] opacity-80">

            <!-- Carte en haut à droite -->
            <img src="https://images.pokemontcg.io/base1/4_hires.png" alt="Charizard"
                class="w-20 absolute top-12 right-8 rotate-[12deg] opacity-80">

            <!-- Carte au milieu gauche -->
            <img src="https://images.pokemontcg.io/base1/7_hires.png" alt="Blastoise"
                class="w-18 absolute top-1/2 left-2 transform -translate-y-1/2 rotate-[-8deg] opacity-80">

            <!-- Carte au milieu droite -->
            <img src="https://images.pokemontcg.io/base1/15_hires.png" alt="Venusaur"
                class="w-18 absolute top-1/2 right-4 transform -translate-y-1/2 rotate-[18deg] opacity-80">

            <!-- Carte en bas à gauche -->
            <img src="https://images.pokemontcg.io/swsh1/53_hires.png" alt="Mewtwo"
                class="w-20 absolute bottom-16 left-6 rotate-[10deg] opacity-80">

            <!-- Carte en bas à droite -->
            <img src="https://images.pokemontcg.io/base1/16_hires.png" alt="Nidoking"
                class="w-20 absolute bottom-8 right-2 rotate-[-20deg] opacity-80">
        </div>

        <!-- Contenu principal centré (comme votre formulaire) -->
        <div class="absolute inset-0 flex items-center justify-center px-4">
            <div class="bg-white border border-gray-200 shadow-lg rounded-md p-8 max-w-md w-full relative z-10 text-center">
                
                <!-- Titre principal -->
                <h1 class="text-3xl font-extrabold text-gray-700 mb-2">BDPokéCards</h1>
                <p class="text-sm text-gray-500 mb-6">Ta boutique en ligne préférée de cartes Pokémon</p>

                <!-- Badge d'annonce -->
                <div class="mb-6">
                    <span class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 text-sm font-medium rounded-full">
                        <i class="fas fa-rocket mr-2 text-xs"></i> LANCEMENT OFFICIEL
                    </span>
                </div>

                <!-- Date de lancement -->
                <div class="bg-gray-50 border-2 border-gray-200 rounded-lg p-6 mb-6">
                    <p class="text-sm text-gray-500 mb-1">Rendez-vous le</p>
                    <p class="text-4xl font-extrabold text-gray-800 mb-1">1er JUIN</p>
                    <p class="text-xl font-bold text-gray-700">2025</p>
                </div>

                <!-- Points clés -->
                <div class="grid grid-cols-3 gap-2 mb-6 text-xs">
                    <div class="bg-blue-50 p-2 rounded">
                        <i class="fas fa-shipping-fast text-blue-600 mb-1"></i>
                        <p class="text-blue-800 font-medium">Livraison rapide</p>
                    </div>
                    <div class="bg-purple-50 p-2 rounded">
                        <i class="fas fa-certificate text-purple-600 mb-1"></i>
                        <p class="text-purple-800 font-medium">100% authentique</p>
                    </div>
                    <div class="bg-orange-50 p-2 rounded">
                        <i class="fas fa-star text-orange-600 mb-1"></i>
                        <p class="text-orange-800 font-medium">Prix compétitifs</p>
                    </div>
                </div>

                <!-- Call to action -->
                <div class="border-t pt-4">
                    <p class="text-lg font-semibold text-gray-700 mb-1">Prépare-toi, jeune dresseur !</p>
                    <p class="text-xs text-gray-400">L'aventure commence bientôt...</p>
                </div>

                <!-- Hashtags -->
                <div class="mt-4 pt-2 border-t border-gray-100">
                    <p class="text-xs text-gray-400">#BDPokéCards #Pokémon #TCG #Suisse #ComingSoon</p>
                </div>
            </div>
        </div>

        <!-- Logo/watermark discret -->
        <div class="absolute bottom-4 left-4 text-xs text-gray-400 z-20">
            bd-pokecards.ch
        </div>

    </div>
    
</body>
</html>