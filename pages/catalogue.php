<?php
$page_title = "Catalogue - OmnesEvent";
include '../includes/header.php';
?>

<section class="max-w-6xl mx-auto px-4 md:px-8 py-12">
    <h1 class="text-4xl font-bold mb-8 text-gray-800">Catalogue des événements</h1>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <aside class="lg:col-span-1">
            <div class="bg-white p-6 rounded-lg shadow-md sticky top-20">
                <h2 class="text-lg font-bold mb-4 text-gray-800">Filtres</h2>

                <div class="mb-6">
                    <h3 class="font-semibold text-gray-700 mb-3">Catégorie</h3>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" class="mr-2 filtre-checkbox" value="soiree">
                            <span>Soirée</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="mr-2 filtre-checkbox" value="sport">
                            <span>Sport</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="mr-2 filtre-checkbox" value="culture">
                            <span>Culture</span>
                        </label>
                    </div>
                </div>

                <div class="mb-6">
                    <h3 class="font-semibold text-gray-700 mb-3">Date</h3>
                    <input type="date" id="filtre-date-catalogue" class="w-full border border-gray-300 rounded-lg p-2">
                </div>

                <div class="mb-6">
                    <h3 class="font-semibold text-gray-700 mb-3">Association</h3>
                    <input type="text" id="filtre-association-catalogue" placeholder="Rechercher" class="w-full border border-gray-300 rounded-lg p-2">
                </div>

                <button id="btn-appliquer-filtres" class="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                    Appliquer
                </button>
                <button id="btn-reinitialiser-filtres" class="w-full mt-2 bg-gray-300 text-gray-800 py-2 rounded-lg font-semibold hover:bg-gray-400 transition">
                    Réinitialiser
                </button>
            </div>
        </aside>

        <div class="lg:col-span-3">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="grille-evenements">
                <?php
                $tous_les_evenements = array(
                    array('id' => 1, 'titre' => 'Soirée du BDE', 'date' => '2024-06-15', 'heure' => '20:00', 'lieu' => 'Campus Principal', 'association' => 'BDE', 'categorie' => 'soiree', 'image' => 'https://via.placeholder.com/400x250?text=Soiree', 'inscrits' => 45, 'capacite' => 100),
                    array('id' => 2, 'titre' => 'Tournoi de Football', 'date' => '2024-06-20', 'heure' => '14:00', 'lieu' => 'Terrain de sport', 'association' => 'BDS', 'categorie' => 'sport', 'image' => 'https://via.placeholder.com/400x250?text=Football', 'inscrits' => 30, 'capacite' => 50),
                    array('id' => 3, 'titre' => 'Conférence Tech', 'date' => '2024-06-22', 'heure' => '18:00', 'lieu' => 'Amphithéâtre A', 'association' => 'Junior Entreprise', 'categorie' => 'culture', 'image' => 'https://via.placeholder.com/400x250?text=Conference', 'inscrits' => 120, 'capacite' => 200),
                    array('id' => 4, 'titre' => 'Match de Volleyball', 'date' => '2024-06-25', 'heure' => '19:00', 'lieu' => 'Gymnase', 'association' => 'BDS', 'categorie' => 'sport', 'image' => 'https://via.placeholder.com/400x250?text=Volleyball', 'inscrits' => 25, 'capacite' => 40),
                    array('id' => 5, 'titre' => 'Festival Musique', 'date' => '2024-07-05', 'heure' => '18:00', 'lieu' => 'Cour principale', 'association' => 'BDE', 'categorie' => 'culture', 'image' => 'https://via.placeholder.com/400x250?text=Festival', 'inscrits' => 200, 'capacite' => 300),
                    array('id' => 6, 'titre' => 'Soirée Casino', 'date' => '2024-07-10', 'heure' => '21:00', 'lieu' => 'Salle des fêtes', 'association' => 'BDE', 'categorie' => 'soiree', 'image' => 'https://via.placeholder.com/400x250?text=Casino', 'inscrits' => 80, 'capacite' => 120),
                );

                foreach($tous_les_evenements as $evt) {
                    $taux = ($evt['inscrits'] / $evt['capacite']) * 100;
                    $couleur = $taux >= 90 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';
                    ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition carte-evenement" data-categorie="<?php echo $evt['categorie']; ?>" data-association="<?php echo strtolower($evt['association']); ?>" data-date="<?php echo $evt['date']; ?>">
                        <img src="<?php echo $evt['image']; ?>" alt="<?php echo $evt['titre']; ?>" class="w-full h-40 object-cover">

                        <div class="p-4">
                            <span class="text-xs font-semibold text-blue-600 bg-blue-100 px-2 py-1 rounded">
                                <?php echo ucfirst($evt['categorie']); ?>
                            </span>
                            <h3 class="text-lg font-bold text-gray-800 mt-2 mb-1"><?php echo $evt['titre']; ?></h3>
                            <p class="text-sm text-gray-600 mb-2"><?php echo $evt['association']; ?></p>

                            <div class="text-sm text-gray-700 mb-3 space-y-1">
                                <p>📅 <?php echo date('d/m', strtotime($evt['date'])); ?></p>
                                <p>📍 <?php echo $evt['lieu']; ?></p>
                            </div>

                            <div class="mb-3">
                                <div class="bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $taux; ?>%"></div>
                                </div>
                                <p class="text-xs text-gray-600 mt-1"><?php echo $evt['inscrits']; ?>/<?php echo $evt['capacite']; ?></p>
                            </div>

                            <a href="detail-evenement.php?id=<?php echo $evt['id']; ?>" class="block w-full text-center bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                                Détails
                            </a>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    $('#btn-appliquer-filtres').click(function() {
        var categoriesSelectionnees = [];
        $('.filtre-checkbox:checked').each(function() {
            categoriesSelectionnees.push($(this).val());
        });
        var dateSelectionnee = $('#filtre-date-catalogue').val();
        var associationSelectionnee = $('#filtre-association-catalogue').val().toLowerCase();

        $('.carte-evenement').each(function() {
            var afficher = true;

            if(categoriesSelectionnees.length > 0) {
                if(!categoriesSelectionnees.includes($(this).data('categorie'))) {
                    afficher = false;
                }
            }

            if(dateSelectionnee && $(this).data('date') !== dateSelectionnee) {
                afficher = false;
            }

            if(associationSelectionnee && !$(this).data('association').includes(associationSelectionnee)) {
                afficher = false;
            }

            $(this).toggle(afficher);
        });
    });

    $('#btn-reinitialiser-filtres').click(function() {
        $('.filtre-checkbox').prop('checked', false);
        $('#filtre-date-catalogue').val('');
        $('#filtre-association-catalogue').val('');
        $('.carte-evenement').show();
    });
});
</script>

<?php include '../includes/footer.php'; ?>
