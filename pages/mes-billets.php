<?php
$page_title = "Mes billets - OmnesEvent";
include '../includes/header.php';
?>

<section class="max-w-6xl mx-auto px-4 md:px-8 py-12">
    <h1 class="text-4xl font-bold text-gray-800 mb-2">Mes billets et inscriptions</h1>
    <p class="text-gray-600 mb-8">Gérez vos réservations et consultez l'historique de vos événements</p>

    <div class="flex gap-4 mb-8 border-b border-gray-200">
        <button class="onglet-btn active pb-4 font-semibold text-blue-600 border-b-2 border-blue-600" data-onglet="actifs">
            📅 Événements à venir
        </button>
        <button class="onglet-btn pb-4 font-semibold text-gray-600 hover:text-blue-600" data-onglet="passes">
            ✓ Événements passés
        </button>
    </div>

    <div id="onglet-actifs" class="onglet-contenu">
        <div class="space-y-6">
            <?php
            $billets_actifs = array(
                array(
                    'id' => 1,
                    'titre' => 'Soirée du BDE',
                    'date' => '2024-06-15',
                    'heure' => '20:00',
                    'lieu' => 'Campus Principal',
                    'association' => 'BDE',
                    'categorie' => 'soiree',
                    'image' => 'https://via.placeholder.com/150x100?text=Soiree',
                    'numero_billet' => 'OMNEV-001-BDE-2024',
                    'qrcode' => 'https://via.placeholder.com/100x100?text=QR'
                ),
                array(
                    'id' => 2,
                    'titre' => 'Tournoi de Football',
                    'date' => '2024-06-20',
                    'heure' => '14:00',
                    'lieu' => 'Terrain de sport',
                    'association' => 'BDS',
                    'categorie' => 'sport',
                    'image' => 'https://via.placeholder.com/150x100?text=Football',
                    'numero_billet' => 'OMNEV-002-BDS-2024',
                    'qrcode' => 'https://via.placeholder.com/100x100?text=QR'
                ),
            );

            if(count($billets_actifs) > 0) {
                foreach($billets_actifs as $billet) {
                    $date_obj = new DateTime($billet['date']);
                    $jours_restants = $date_obj->diff(new DateTime())->days;
                    ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-6">
                            <div>
                                <img src="<?php echo $billet['image']; ?>" alt="<?php echo $billet['titre']; ?>" class="w-full h-24 object-cover rounded-lg">
                            </div>

                            <div class="md:col-span-2">
                                <h3 class="text-lg font-bold text-gray-800 mb-1"><?php echo $billet['titre']; ?></h3>
                                <p class="text-sm text-gray-600 mb-3"><?php echo $billet['association']; ?></p>

                                <div class="text-sm text-gray-700 space-y-1">
                                    <p>📅 <?php echo date('d/m/Y', strtotime($billet['date'])); ?> à <?php echo $billet['heure']; ?></p>
                                    <p>📍 <?php echo $billet['lieu']; ?></p>
                                    <p class="text-blue-600 font-semibold">N° : <?php echo $billet['numero_billet']; ?></p>
                                </div>
                            </div>

                            <div class="flex flex-col justify-between">
                                <div class="text-center">
                                    <p class="text-xs text-gray-600 mb-2">Code QR</p>
                                    <img src="<?php echo $billet['qrcode']; ?>" alt="QR Code" class="w-20 h-20 mx-auto">
                                </div>

                                <div class="flex gap-2 flex-col">
                                    <button class="btn-afficher-billet bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                                        Voir le billet
                                    </button>
                                    <button class="btn-annuler-billet bg-red-100 text-red-700 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-red-200 transition">
                                        Annuler
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php }
            } else {
                echo '<div class="text-center py-12 bg-gray-50 rounded-lg"><p class="text-gray-600">Aucun billet actif pour le moment</p><a href="/pages/catalogue.php" class="text-blue-600 hover:text-blue-800 font-semibold mt-2 inline-block">Découvrir les événements →</a></div>';
            }
            ?>
        </div>
    </div>

    <div id="onglet-passes" class="onglet-contenu hidden">
        <div class="space-y-6">
            <?php
            $billets_passes = array(
                array(
                    'id' => 10,
                    'titre' => 'Conférence Entrepreneurship',
                    'date' => '2024-05-20',
                    'heure' => '18:00',
                    'lieu' => 'Amphithéâtre B',
                    'association' => 'Junior Entreprise',
                    'image' => 'https://via.placeholder.com/150x100?text=Conference',
                ),
                array(
                    'id' => 11,
                    'titre' => 'Match de Basket',
                    'date' => '2024-05-15',
                    'heure' => '19:30',
                    'lieu' => 'Gymnase',
                    'association' => 'BDS',
                    'image' => 'https://via.placeholder.com/150x100?text=Basket',
                ),
            );

            foreach($billets_passes as $billet) {
                ?>
                <div class="bg-gray-50 rounded-lg shadow-md overflow-hidden border-l-4 border-gray-400 opacity-75">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-6">
                        <div>
                            <img src="<?php echo $billet['image']; ?>" alt="<?php echo $billet['titre']; ?>" class="w-full h-24 object-cover rounded-lg opacity-60">
                        </div>

                        <div class="md:col-span-3">
                            <h3 class="text-lg font-bold text-gray-600 mb-1"><?php echo $billet['titre']; ?></h3>
                            <p class="text-sm text-gray-500 mb-2"><?php echo $billet['association']; ?></p>

                            <div class="text-sm text-gray-600 space-y-1">
                                <p>📅 <?php echo date('d/m/Y', strtotime($billet['date'])); ?> à <?php echo $billet['heure']; ?></p>
                                <p>📍 <?php echo $billet['lieu']; ?></p>
                            </div>

                            <span class="inline-block mt-3 text-xs font-semibold text-gray-600 bg-gray-200 px-3 py-1 rounded">
                                Événement passé
                            </span>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <div id="modal-billet-complet" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg p-8 max-w-2xl w-full">
            <button id="btn-fermer-modal-billet" class="float-right text-2xl text-gray-600 hover:text-gray-800">×</button>

            <h2 class="text-3xl font-bold text-gray-800 mb-6">Votre billet</h2>

            <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg p-8 text-center">
                <p class="text-sm opacity-90 mb-2">N° de billet</p>
                <p class="text-2xl font-bold mb-8" id="billet-numero">OMNEV-001-BDE-2024</p>

                <div id="billet-qrcode" class="bg-white p-6 inline-block rounded-lg mb-8">
                    <img src="https://via.placeholder.com/200x200?text=QR" alt="QR Code" class="w-48 h-48">
                </div>

                <div class="bg-blue-700 p-4 rounded-lg text-left space-y-2">
                    <div class="flex justify-between">
                        <span>Événement:</span>
                        <span id="billet-titre" class="font-semibold">Soirée du BDE</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Date:</span>
                        <span id="billet-date" class="font-semibold">15/06/2024 à 20:00</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Lieu:</span>
                        <span id="billet-lieu" class="font-semibold">Campus Principal</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex gap-4">
                <button id="btn-telecharger-billet" class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                    Télécharger le billet
                </button>
                <button id="btn-fermer-modal-billet" class="flex-1 bg-gray-300 text-gray-800 py-2 rounded-lg font-semibold hover:bg-gray-400 transition">
                    Fermer
                </button>
            </div>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    $('.onglet-btn').click(function() {
        var onglet = $(this).data('onglet');

        $('.onglet-btn').removeClass('active text-blue-600 border-b-2 border-blue-600').addClass('text-gray-600');
        $(this).addClass('active text-blue-600 border-b-2 border-blue-600');

        $('.onglet-contenu').addClass('hidden');
        $('#onglet-' + onglet).removeClass('hidden');
    });

    $('.btn-afficher-billet').click(function() {
        $('#modal-billet-complet').removeClass('hidden');
    });

    $('#btn-fermer-modal-billet').click(function() {
        $('#modal-billet-complet').addClass('hidden');
    });

    $('.btn-annuler-billet').click(function() {
        if(confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
            $(this).closest('.bg-white').fadeOut();
            alert('Réservation annulée. Vous recevrez un email de confirmation.');
        }
    });

    $('#btn-telecharger-billet').click(function() {
        alert('Téléchargement du billet en cours...');
    });

    $(document).click(function(e) {
        if($(e.target).is('#modal-billet-complet')) {
            $('#modal-billet-complet').addClass('hidden');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
