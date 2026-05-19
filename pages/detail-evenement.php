<?php
$page_title = "Détail de l'événement - OmnesEvent";
include '../includes/header.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$evenements_data = array(
    1 => array(
        'titre' => 'Soirée du BDE',
        'date' => '2024-06-15',
        'heure' => '20:00',
        'lieu' => 'Campus Principal',
        'association' => 'BDE',
        'categorie' => 'soiree',
        'image' => '/images/Soiree.webp',
        'description' => 'Grande soirée d\'intégration avec tous les membres du BDE. Au programme : danses, jeux, et surprises !',
        'details_complets' => 'Cette soirée est organisée pour accueillir les nouveaux étudiants. Vous pourrez rencontrer les membres du BDE et participer à de nombreuses activités. Les boissons et snacks seront fournis.',
        'inscrits' => 45,
        'capacite' => 100,
    ),
    2 => array(
        'titre' => 'Tournoi de Football',
        'date' => '2024-06-20',
        'heure' => '14:00',
        'lieu' => 'Terrain de sport',
        'association' => 'BDS',
        'categorie' => 'sport',
        'image' => '/images/foot.webp',
        'description' => 'Tournoi inter-classes de football 5 contre 5.',
        'details_complets' => 'Venez affronter d\'autres équipes dans ce tournoi convivial. Les règles seront expliquées sur place. Il est conseillé de venir en tenue de sport.',
        'inscrits' => 30,
        'capacite' => 50,
    ),
    3 => array(
        'titre' => 'Conférence Tech',
        'date' => '2024-06-22',
        'heure' => '18:00',
        'lieu' => 'Amphithéâtre A',
        'association' => 'Junior Entreprise',
        'categorie' => 'culture',
        'image' => '/images/reunion.webp',
        'description' => 'Conférence sur l\'intelligence artificielle et ses applications.',
        'details_complets' => 'Un conférencier expert discutera des dernières avancées en IA. Durée : 1h30 + questions. Inscription conseillée pour éviter de rester dehors.',
        'inscrits' => 120,
        'capacite' => 200,
    ),
);

if(!isset($evenements_data[$id])) {
    echo '<div class="max-w-6xl mx-auto px-4 md:px-8 py-12"><p class="text-center text-gray-600">Événement non trouvé</p></div>';
    include '../includes/footer.php';
    exit;
}

$evt = $evenements_data[$id];
$taux = ($evt['inscrits'] / $evt['capacite']) * 100;
$places_restantes = $evt['capacite'] - $evt['inscrits'];
$complet = $places_restantes <= 0;
?>

<section class="max-w-4xl mx-auto px-4 md:px-8 py-12">
    <div class="mb-6">
        <a href="/pages/catalogue.php" class="text-blue-600 hover:text-blue-800">← Retour au catalogue</a>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <img src="<?php echo $evt['image']; ?>" alt="<?php echo $evt['titre']; ?>" class="w-full h-96 object-cover">

        <div class="p-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                <div>
                    <span class="text-sm font-semibold text-blue-600 bg-blue-100 px-3 py-1 rounded">
                        <?php echo ucfirst($evt['categorie']); ?>
                    </span>
                    <h1 class="text-4xl font-bold text-gray-800 mt-4 mb-2"><?php echo $evt['titre']; ?></h1>
                    <p class="text-lg text-gray-600"><?php echo $evt['association']; ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 uppercase">Date et heure</h3>
                        <p class="text-lg text-gray-800">
                            📅 <?php echo date('d/m/Y', strtotime($evt['date'])); ?> à <?php echo $evt['heure']; ?>
                        </p>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 uppercase">Lieu</h3>
                        <p class="text-lg text-gray-800">📍 <?php echo $evt['lieu']; ?></p>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 uppercase">Places disponibles</h3>
                        <p class="text-lg text-gray-800">
                            <?php if($complet): ?>
                                <span class="text-red-600 font-bold">Événement complet</span>
                            <?php else: ?>
                                <span class="text-green-600 font-bold"><?php echo $places_restantes; ?> place<?php echo $places_restantes > 1 ? 's' : ''; ?> disponible<?php echo $places_restantes > 1 ? 's' : ''; ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="font-bold text-gray-800 mb-4">Inscription à l'événement</h3>

                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-2">Taux de remplissage</p>
                        <div class="bg-gray-200 rounded-full h-3">
                            <div class="bg-blue-600 h-3 rounded-full" style="width: <?php echo $taux; ?>%"></div>
                        </div>
                        <p class="text-sm text-gray-600 mt-2"><?php echo $evt['inscrits']; ?>/<?php echo $evt['capacite']; ?> inscrits</p>
                    </div>

                    <?php if($complet): ?>
                        <button disabled class="w-full bg-gray-400 text-white py-3 rounded-lg font-bold cursor-not-allowed">
                            Événement complet
                        </button>
                    <?php else: ?>
                        <button id="btn-inscrire" class="w-full bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition">
                            S'inscrire
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="border-t pt-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">À propos de cet événement</h2>
                <p class="text-gray-700 leading-relaxed mb-4"><?php echo $evt['description']; ?></p>
                <p class="text-gray-700 leading-relaxed"><?php echo $evt['details_complets']; ?></p>
            </div>
        </div>
    </div>

    <div id="modal-inscription" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Confirmer votre inscription</h2>

            <form id="form-inscription">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nom complet</label>
                    <input type="text" name="nom" required class="w-full border border-gray-300 rounded-lg p-2">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" required class="w-full border border-gray-300 rounded-lg p-2">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Téléphone (optionnel)</label>
                    <input type="tel" name="tel" class="w-full border border-gray-300 rounded-lg p-2">
                </div>

                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                        Confirmer
                    </button>
                    <button type="button" id="btn-fermer-modal" class="flex-1 bg-gray-300 text-gray-800 py-2 rounded-lg font-semibold hover:bg-gray-400 transition">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    $('#btn-inscrire').click(function() {
        $('#modal-inscription').removeClass('hidden');
    });

    $('#btn-fermer-modal').click(function() {
        $('#modal-inscription').addClass('hidden');
    });

    $('#form-inscription').submit(function(e) {
        e.preventDefault();
        alert('Inscription confirmée ! Vous recevrez un email de confirmation.');
        $('#modal-inscription').addClass('hidden');
        this.reset();
    });
});
</script>

<?php include '../includes/footer.php'; ?>
