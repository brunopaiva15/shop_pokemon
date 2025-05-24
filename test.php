<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BDPokéCards - Publication Instagram</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Fredoka+One:wght@400&family=Inter:wght@400;500;600;700;800&display=swap');
        
        .fredoka { font-family: 'Fredoka One', cursive; }
        .inter { font-family: 'Inter', sans-serif; }
        
        .card-shadow {
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.15));
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .float-animation {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-10px) rotate(2deg); }
            66% { transform: translateY(5px) rotate(-1deg); }
        }
        
        .sparkle {
            position: absolute;
            color: #fbbf24;
            animation: sparkle 2s infinite;
        }
        
        @keyframes sparkle {
            0%, 100% { opacity: 0; transform: scale(0.5); }
            50% { opacity: 1; transform: scale(1.2); }
        }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    
    <!-- Format carré Instagram 1080x1080 -->
    <div class="w-[600px] h-[600px] bg-white relative overflow-hidden rounded-lg shadow-2xl">
        
        <!-- Background gradient -->
        <div class="absolute inset-0 gradient-bg opacity-90"></div>
        
        <!-- Decorative cards background -->
        <div class="absolute inset-0 pointer-events-none">
            <!-- Card 1 -->
            <div class="absolute top-8 left-8 w-16 h-20 bg-gradient-to-br from-yellow-300 to-orange-400 rounded-lg card-shadow float-animation opacity-20" style="animation-delay: 0s;"></div>
            <!-- Card 2 -->
            <div class="absolute top-16 right-12 w-14 h-18 bg-gradient-to-br from-blue-300 to-purple-400 rounded-lg card-shadow float-animation opacity-25" style="animation-delay: 1s;"></div>
            <!-- Card 3 -->
            <div class="absolute bottom-12 left-12 w-12 h-16 bg-gradient-to-br from-green-300 to-blue-400 rounded-lg card-shadow float-animation opacity-20" style="animation-delay: 2s;"></div>
            <!-- Card 4 -->
            <div class="absolute bottom-20 right-8 w-15 h-19 bg-gradient-to-br from-red-300 to-pink-400 rounded-lg card-shadow float-animation opacity-25" style="animation-delay: 0.5s;"></div>
            <!-- Card 5 -->
            <div class="absolute top-1/2 left-4 w-10 h-14 bg-gradient-to-br from-purple-300 to-indigo-400 rounded-lg card-shadow float-animation opacity-15" style="animation-delay: 1.5s;"></div>
        </div>
        
        <!-- Sparkles -->
        <div class="sparkle top-20 left-20" style="animation-delay: 0s;"><i class="fas fa-star text-xs"></i></div>
        <div class="sparkle top-32 right-24" style="animation-delay: 0.7s;"><i class="fas fa-star text-sm"></i></div>
        <div class="sparkle bottom-32 left-16" style="animation-delay: 1.4s;"><i class="fas fa-star text-xs"></i></div>
        <div class="sparkle bottom-20 right-20" style="animation-delay: 2.1s;"><i class="fas fa-star text-sm"></i></div>
        <div class="sparkle top-1/2 right-8" style="animation-delay: 0.3s;"><i class="fas fa-star text-xs"></i></div>
        
        <!-- Main content -->
        <div class="relative z-10 h-full flex flex-col items-center justify-center text-center px-8">
            
            <!-- Logo/Brand -->
            <div class="mb-6">
                <h1 class="fredoka text-5xl text-white mb-2 drop-shadow-lg">BDPokéCards</h1>
                <p class="inter text-white/90 text-lg font-medium">Ta boutique Pokémon préférée</p>
            </div>
            
            <!-- Announcement badge -->
            <div class="bg-white/20 backdrop-blur-sm border border-white/30 rounded-full px-6 py-3 mb-8">
                <p class="inter text-white font-semibold text-lg">
                    <i class="fas fa-rocket mr-2 text-yellow-300"></i>
                    LANCEMENT OFFICIEL
                </p>
            </div>
            
            <!-- Date -->
            <div class="bg-white rounded-2xl p-6 mb-8 pulse-animation shadow-2xl">
                <div class="text-center">
                    <p class="inter text-gray-600 text-sm font-medium mb-1">Rendez-vous le</p>
                    <p class="fredoka text-4xl text-gray-800 mb-1">1er JUIN</p>
                    <p class="inter text-2xl font-bold text-gray-800">2025</p>
                </div>
            </div>
            
            <!-- Features -->
            <div class="flex space-x-4 mb-8">
                <div class="bg-white/20 backdrop-blur-sm rounded-lg p-3 border border-white/30">
                    <i class="fas fa-shipping-fast text-white text-xl mb-1"></i>
                    <p class="inter text-white text-xs font-medium">Livraison<br>rapide</p>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-lg p-3 border border-white/30">
                    <i class="fas fa-gem text-white text-xl mb-1"></i>
                    <p class="inter text-white text-xs font-medium">Cartes<br>authentiques</p>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-lg p-3 border border-white/30">
                    <i class="fas fa-heart text-white text-xl mb-1"></i>
                    <p class="inter text-white text-xs font-medium">Service<br>premium</p>
                </div>
            </div>
            
            <!-- Call to action -->
            <div class="text-center">
                <p class="inter text-white/90 text-lg font-medium mb-2">Prépare-toi, jeune dresseur !</p>
                <p class="inter text-white/80 text-sm">#BDPokéCards #Pokémon #Suisse #ComingSoon</p>
            </div>
            
        </div>
        
        <!-- Bottom decoration -->
        <div class="absolute bottom-0 left-0 right-0 h-2 bg-gradient-to-r from-yellow-400 via-red-400 to-blue-400"></div>
        
    </div>
    
</body>
</html>