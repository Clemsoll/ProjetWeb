<?php
$page_title = "Créer un événement - OmnesEvent";
include '../includes/header.php';
?>

<section class="max-w-2xl mx-auto px-4 md:px-8 py-12">
    <h1 class="text-4xl font-bold text-gray-800 mb-8">Créer un nouvel événement</h1>

    <form id="form-creation-evenement" class="bg-white rounded-lg shadow-md p-8">
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Titre de l'événement *</label>
            <input type="text" name="titre" required class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>

        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Association organisatrice *</label>
            <select name="association" required class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Sélectionnez une association</option>
                <option value="BDE">BDE</option>
                <option value="BDS">BDS</option>
                <option value="Junior Entreprise">Junior Entreprise</option>
                <option value="Autres">Autres</option>
            </select>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Catégorie *</label>
            <select name="categorie" required class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Sélectionnez une catégorie</option>
                <option value="soiree">Soirée</option>
                <option value="sport">Sport</option>
                <option value="culture">Culture</option>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Date *</label>
                <input type="date" name="date" required class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Heure *</label>
                <input type="time" name="heure" required class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Lieu *</label>
            <input type="text" name="lieu" required placeholder="ex: Campus Principal, Gymnase..." class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>

        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Description courte *</label>
            <textarea name="description" required rows="3" placeholder="Décrivez rapidement votre événement" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Description détaillée</label>
            <textarea name="description_complete" rows="5" placeholder="Donnez plus de détails, informations pratiques..." class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Affiche (image) *</label>
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center bg-gray-50">
                <input type="file" name="affiche" id="input-affiche" required accept="image/*" class="hidden">
                <label for="input-affiche" class="cursor-pointer">
                    <p class="text-gray-600 mb-2">Cliquez pour télécharger ou glissez-déposez une image</p>
                    <p class="text-sm text-gray-500">PNG, JPG, GIF jusqu'à 5MB</p>
                </label>
                <div id="preview-affiche" class="mt-4 hidden">
                    <img id="img-apercu" src="" alt="Aperçu" class="max-h-40 mx-auto rounded-lg">
                </div>
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Capacité maximale *</label>
            <input type="number" name="capacite" min="1" required class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <p class="text-xs text-gray-500 mt-1">Nombre maximum de participants</p>
        </div>

        <div class="mb-8">
            <label class="flex items-center">
                <input type="checkbox" name="conditions" required class="mr-3">
                <span class="text-sm text-gray-700">J'accepte les conditions d'utilisation et je confirme que j'ai l'autorisation pour créer cet événement</span>
            </label>
        </div>

        <div class="flex gap-4">
            <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition">
                Créer l'événement
            </button>
            <a href="/index.php" class="flex-1 text-center bg-gray-300 text-gray-800 py-3 rounded-lg font-bold hover:bg-gray-400 transition">
                Annuler
            </a>
        </div>
    </form>

    <div id="modal-succes" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg p-8 max-w-md w-full text-center">
            <h2 class="text-2xl font-bold text-green-600 mb-4">✓ Événement créé avec succès</h2>
            <p class="text-gray-700 mb-6">Votre événement a été ajouté au catalogue et est maintenant visible par tous les utilisateurs.</p>
            <a href="/index.php" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                Retour à l'accueil
            </a>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    $('#input-affiche').change(function(e) {
        var file = e.target.files[0];
        if(file) {
            var reader = new FileReader();
            reader.onload = function(event) {
                $('#img-apercu').attr('src', event.target.result);
                $('#preview-affiche').removeClass('hidden');
            };
            reader.readAsDataURL(file);
        }
    });

    $('#form-creation-evenement').submit(function(e) {
        e.preventDefault();
        var titre = $('input[name="titre"]').val();
        $('#modal-succes').removeClass('hidden');

        setTimeout(function() {
            window.location.href = '/index.php';
        }, 2000);
    });
});
</script>

<?php include '../includes/footer.php'; ?>
