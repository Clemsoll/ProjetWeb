<?php
$page_title = "Mon profil - OmnesEvent";
include '../includes/header.php';
?>

<section class="max-w-4xl mx-auto px-4 md:px-8 py-12">
    <h1 class="text-4xl font-bold text-gray-800 mb-8">Mon profil</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <aside class="md:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-8 text-center sticky top-20">
                <div class="mb-4">
                    <div class="w-24 h-24 bg-blue-600 rounded-full mx-auto flex items-center justify-center text-white text-3xl font-bold">
                        JD
                    </div>
                </div>

                <h2 class="text-2xl font-bold text-gray-800 mb-2">Jean Dupont</h2>
                <p class="text-gray-600 mb-6">jean.dupont@omnes.com</p>

                <div class="border-t pt-6">
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">Rôle</p>
                        <p class="font-semibold text-gray-800">Participant</p>
                    </div>

                    <button id="btn-editer-profil" class="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition mb-2">
                        Modifier mon profil
                    </button>

                    <button class="w-full bg-gray-300 text-gray-800 py-2 rounded-lg font-semibold hover:bg-gray-400 transition">
                        Se déconnecter
                    </button>
                </div>
            </div>
        </aside>

        <main class="md:col-span-2 space-y-8">
            <div class="bg-white rounded-lg shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Informations personnelles</h2>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Prénom</p>
                            <p class="font-semibold text-gray-800">Jean</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Nom</p>
                            <p class="font-semibold text-gray-800">Dupont</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600">Email</p>
                        <p class="font-semibold text-gray-800">jean.dupont@omnes.com</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600">Téléphone</p>
                        <p class="font-semibold text-gray-800">06 12 34 56 78</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600">Promotion</p>
                        <p class="font-semibold text-gray-800">ING2 - Année 2024-2025</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Statistiques</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <p class="text-3xl font-bold text-blue-600">8</p>
                        <p class="text-gray-600 text-sm">Événements</p>
                    </div>

                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <p class="text-3xl font-bold text-green-600">5</p>
                        <p class="text-gray-600 text-sm">Billets actifs</p>
                    </div>

                    <div class="text-center p-4 bg-purple-50 rounded-lg">
                        <p class="text-3xl font-bold text-purple-600">12</p>
                        <p class="text-gray-600 text-sm">Événements passés</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Associations</h2>

                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                        <div>
                            <p class="font-semibold text-gray-800">BDE</p>
                            <p class="text-sm text-gray-600">Bureau Des Étudiants</p>
                        </div>
                        <span class="text-xs font-semibold text-blue-600 bg-blue-100 px-3 py-1 rounded">Membre</span>
                    </div>

                    <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                        <div>
                            <p class="font-semibold text-gray-800">BDS</p>
                            <p class="text-sm text-gray-600">Bureau Des Sports</p>
                        </div>
                        <span class="text-xs font-semibold text-green-600 bg-green-100 px-3 py-1 rounded">Suiveur</span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div id="modal-editer-profil" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg p-8 max-w-2xl w-full max-h-screen overflow-y-auto">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Modifier mon profil</h2>

            <form id="form-modifier-profil">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Prénom</label>
                        <input type="text" name="prenom" value="Jean" class="w-full border border-gray-300 rounded-lg p-2">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nom</label>
                        <input type="text" name="nom" value="Dupont" class="w-full border border-gray-300 rounded-lg p-2">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" value="jean.dupont@omnes.com" class="w-full border border-gray-300 rounded-lg p-2">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Téléphone</label>
                    <input type="tel" name="tel" value="06 12 34 56 78" class="w-full border border-gray-300 rounded-lg p-2">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Promotion</label>
                    <select name="promotion" class="w-full border border-gray-300 rounded-lg p-2">
                        <option selected>ING2 - Année 2024-2025</option>
                        <option>ING1 - Année 2024-2025</option>
                        <option>ING3 - Année 2024-2025</option>
                    </select>
                </div>

                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                        Enregistrer
                    </button>
                    <button type="button" id="btn-fermer-modal-profil" class="flex-1 bg-gray-300 text-gray-800 py-2 rounded-lg font-semibold hover:bg-gray-400 transition">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    $('#btn-editer-profil').click(function() {
        $('#modal-editer-profil').removeClass('hidden');
    });

    $('#btn-fermer-modal-profil').click(function() {
        $('#modal-editer-profil').addClass('hidden');
    });

    $('#form-modifier-profil').submit(function(e) {
        e.preventDefault();
        alert('Profil mis à jour avec succès');
        $('#modal-editer-profil').addClass('hidden');
    });

    $(document).click(function(e) {
        if($(e.target).is('#modal-editer-profil')) {
            $('#modal-editer-profil').addClass('hidden');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
