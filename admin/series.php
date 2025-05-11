<?php
// admin/series.php

// Définir le titre de la page
$pageTitle = 'Gestion des séries';

// Définir le bouton d'action
$actionButton = [
    'url'  => 'add-series.php',
    'icon' => 'fas fa-plus',
    'text' => 'Ajouter une série'
];

// Inclure l'en-tête
require_once 'includes/header.php';

// Récupérer les séries avec le nombre de cartes associées
$conn  = getDbConnection();
$query = "SELECT s.*, COUNT(c.id) as card_count
          FROM series s
          LEFT JOIN cards c ON s.id = c.series_id
          GROUP BY s.id
          ORDER BY s.name ASC";
$stmt  = $conn->query($query);
$series = $stmt->fetchAll();
?>

<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Liste des séries</h2>
        <span class="text-gray-500"><?php echo count($series); ?> série<?php echo count($series) > 1 ? 's' : ''; ?></span>
    </div>

    <?php if (empty($series)): ?>
        <div class="text-center py-4">
            <p class="text-gray-500">Aucune série n'a été créée.</p>
            <a href="add-series.php" class="inline-block mt-4 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition">
                <i class="fas fa-plus mr-1"></i> Ajouter une série
            </a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Logo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date de sortie</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre de cartes</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($series as $s): ?>
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <?php
                                if (!empty($s['logo_url'])) {
                                    // Si c'est une URL complète (http:// ou https://), on l'utilise telle quelle
                                    if (preg_match('#^https?://#i', $s['logo_url'])) {
                                        $imgSrc = $s['logo_url'];
                                    } else {
                                        // sinon, on concatène avec SITE_URL
                                        $imgSrc = SITE_URL . '/' . ltrim($s['logo_url'], '/');
                                    }
                                ?>
                                    <div class="w-12 h-12 bg-gray-100 rounded-md overflow-hidden">
                                        <img src="<?php echo htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8'); ?>"
                                            alt="Logo de <?php echo htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                            class="w-full h-full object-contain">
                                    </div>
                                <?php } else { ?>
                                    <div class="w-12 h-12 bg-gray-100 rounded-md flex items-center justify-center">
                                        <i class="fas fa-layer-group text-gray-400"></i>
                                    </div>
                                <?php } ?>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap font-medium"><?php echo htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 py-2 whitespace-nowrap"><?php echo $s['release_date'] ? date('d/m/Y', strtotime($s['release_date'])) : '-'; ?></td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <a href="cards.php?series=<?php echo $s['id']; ?>" class="text-blue-600 hover:underline">
                                    <?php echo $s['card_count']; ?> carte<?php echo $s['card_count'] > 1 ? 's' : ''; ?>
                                </a>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="flex space-x-2">
                                    <a href="edit-series.php?id=<?php echo $s['id']; ?>" class="action-button edit" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($s['card_count'] == 0): ?>
                                        <a href="delete-series.php?id=<?php echo $s['id']; ?>" class="action-button delete delete-confirm" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="action-button delete opacity-50 cursor-not-allowed" title="Impossible de supprimer une série contenant des cartes">
                                            <i class="fas fa-trash"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>