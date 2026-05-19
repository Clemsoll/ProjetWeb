<?php
$page_title = "OmnesEvent - Accueil";
include 'includes/header.php';
?>

<section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-16 md:py-24">
    <div class="max-w-6xl mx-auto px-4 md:px-8">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">Découvrez les événements d'Omnes</h1>
        <p class="text-lg md:text-xl text-blue-100">Retrouvez tous les événements de vos associations en un seul endroit.</p>
        <a href="/pages/catalogue.php" class="inline-block mt-6 bg-white text-blue-600 px-6 py-3 rounded-lg font-bold hover:bg-gray-100 transition">
            Explorer le catalogue
        </a>
    </div>
</section>

<section class="max-w-6xl mx-auto px-4 md:px-8 py-12">
    <h2 class="text-3xl font-bold mb-8 text-gray-800">Événements à venir</h2>

    <div id="recherche-rapide" class="bg-white p-6 rounded-lg shadow-md mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Catégorie</label>
                <select id="filtre-categorie" class="w-full border border-gray-300 rounded-lg p-2">
                    <option value="">Toutes les catégories</option>
                    <option value="soiree">Soirée</option>
                    <option value="sport">Sport</option>
                    <option value="culture">Culture</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Date</label>
                <input type="date" id="filtre-date" class="w-full border border-gray-300 rounded-lg p-2">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Association</label>
                <input type="text" id="filtre-association" placeholder="Rechercher une association" class="w-full border border-gray-300 rounded-lg p-2">
            </div>
        </div>

        <button id="btn-rechercher" class="w-full md:w-auto mt-4 bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
            Rechercher
        </button>
    </div>

    <div id="liste-evenements" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php
        $evenements = array(
            array(
                'id' => 1,
                'titre' => 'Soirée du BDE',
                'date' => '2024-06-15',
                'heure' => '20:00',
                'lieu' => 'Campus Principal',
                'association' => 'BDE',
                'categorie' => 'soiree',
                'image' => 'https://via.placeholder.com/300x200?text=Soiree+BDE',
                'inscrits' => 45,
                'capacite' => 100,
                'description' => 'Grande soirée d\'intégration avec tous les membres du BDE.'
            ),
            array(
                'id' => 2,
                'titre' => 'Tournoi de Football',
                'date' => '2024-06-20',
                'heure' => '14:00',
                'lieu' => 'Terrain de sport',
                'association' => 'BDS',
                'categorie' => 'sport',
                'image' => 'https://via.placeholder.com/300x200?text=Foot',
                'inscrits' => 30,
                'capacite' => 50,
                'description' => 'Tournoi inter-classes de football 5 contre 5.'
            ),
            array(
                'id' => 3,
                'titre' => 'Conférence Tech',
                'date' => '2024-06-22',
                'heure' => '18:00',
                'lieu' => 'Amphithéâtre A',
                'association' => 'Junior Entreprise',
                'categorie' => 'culture',
                'image' => 'https://via.placeholder.com/300x200?text=Conference',
                'inscrits' => 120,
                'capacite' => 200,
                'description' => 'Conférence sur l\'intelligence artificielle et ses applications.'
            ),
        );

        foreach($evenements as $evt) {
            $taux_remplissage = ($evt['inscrits'] / $evt['capacite']) * 100;
            $couleur_badge = $taux_remplissage >= 90 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';
            $badge_texte = $taux_remplissage >= 90 ? 'Presque complet' : ($taux_remplissage >= 50 ? 'Places disponibles' : 'Beaucoup de places');
            ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                <img src="<?php echo $evt['image']; ?>" alt="<?php echo $evt['titre']; ?>" class="w-full h-48 object-cover">

                <div class="p-4">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-semibold text-blue-600 bg-blue-100 px-2 py-1 rounded">
                            <?php echo ucfirst($evt['categorie']); ?>
                        </span>
                        <span class="text-xs font-semibold <?php echo $couleur_badge; ?> px-2 py-1 rounded">
                            <?php echo $badge_texte; ?>
                        </span>
                    </div>

                    <h3 class="text-lg font-bold text-gray-800 mb-2"><?php echo $evt['titre']; ?></h3>

                    <p class="text-sm text-gray-600 mb-3"><?php echo $evt['association']; ?></p>

                    <div class="text-sm text-gray-700 mb-4 space-y-1">
                        <p>📅 <?php echo date('d/m/Y', strtotime($evt['date'])); ?> à <?php echo $evt['heure']; ?></p>
                        <p>📍 <?php echo $evt['lieu']; ?></p>
                    </div>

                    <div class="mb-4">
                        <div class="bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $taux_remplissage; ?>%"></div>
                        </div>
                        <p class="text-xs text-gray-600 mt-1"><?php echo $evt['inscrits']; ?>/<?php echo $evt['capacite']; ?> inscrits</p>
                    </div>

                    <a href="/pages/detail-evenement.php?id=<?php echo $evt['id']; ?>" class="block w-full text-center bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                        Voir plus
                    </a>
                </div>
            </div>
        <?php } ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
