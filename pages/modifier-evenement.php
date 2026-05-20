<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_role(['organisateur', 'administrateur']);

$eventId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$event = fetch_event_by_id($pdo, $eventId);

if ($event === null) {
    set_flash('error', 'Événement introuvable.');
    redirect_to('pages/dashboard-organisateur.php');
}

if (!can_manage_event($event)) {
    set_flash('error', 'Vous ne pouvez pas modifier cet événement.');
    redirect_to('pages/catalogue.php');
}

$page_title = 'Modifier un événement';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim((string) ($_POST['titre'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $details = trim((string) ($_POST['details_complets'] ?? ''));
    $dateInput = trim((string) ($_POST['date_evenement'] ?? ''));
    $lieu = trim((string) ($_POST['lieu'] ?? ''));
    $adresseComplete = trim((string) ($_POST['adresse_complete'] ?? ''));
    $categorie = normalize_category($_POST['categorie'] ?? null);
    $association = trim((string) ($_POST['association'] ?? ''));
    $capaciteMax = (int) ($_POST['capacite_max'] ?? 0);
    $prix = round((float) str_replace(',', '.', (string) ($_POST['prix'] ?? '0')), 2);
    $dateObject = DateTime::createFromFormat('Y-m-d\TH:i', $dateInput);

    if ($titre === '' || $description === '' || $dateInput === '' || $lieu === '' || $association === '' || $capaciteMax <= 0 || $categorie === null) {
        $error = 'Merci de remplir tous les champs obligatoires.';
    } elseif ($dateObject === false) {
        $error = 'La date et l heure saisies sont invalides.';
    } elseif ($capaciteMax < event_reserved_places($event, $pdo)) {
        $error = 'La capacité maximale ne peut pas être inférieure au nombre de réservations actives.';
    } elseif ($prix < 0) {
        $error = 'Le prix ne peut pas être négatif.';
    } else {
        try {
            $newImagePath = upload_event_image($_FILES['image'] ?? []);
            $imagePath = $newImagePath ?? $event['image'];

            $stmt = $pdo->prepare(
                'UPDATE evenements
                 SET titre = :titre,
                     description = :description,
                     details_complets = :details_complets,
                     date_evenement = :date_evenement,
                     lieu = :lieu,
                     adresse_complete = :adresse_complete,
                     categorie = :categorie,
                     association = :association,
                     image = :image,
                     capacite_max = :capacite_max,
                     prix = :prix
                 WHERE id = :id'
            );

            $stmt->execute([
                'titre' => $titre,
                'description' => $description,
                'details_complets' => $details !== '' ? $details : null,
                'date_evenement' => $dateObject->format('Y-m-d H:i:s'),
                'lieu' => $lieu,
                'adresse_complete' => $adresseComplete !== '' ? $adresseComplete : $lieu,
                'categorie' => $categorie,
                'association' => $association,
                'image' => $imagePath,
                'capacite_max' => $capaciteMax,
                'prix' => $prix,
                'id' => $eventId,
            ]);

            set_flash('success', 'Événement mis à jour avec succès.');
            redirect_to('pages/detail-evenement.php?id=' . $eventId);
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="mx-auto max-w-3xl px-4 py-12 md:px-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-slate-900">Modifier l événement</h1>
        <p class="mt-2 text-slate-600">Mettez à jour les informations de votre événement, y compris le prix et l adresse utilisée pour la carte.</p>
    </div>

    <div class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
        <?php if ($error !== ''): ?>
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label for="titre" class="mb-2 block text-sm font-semibold text-slate-700">Titre</label>
                <input type="text" id="titre" name="titre" value="<?= e(old('titre', $event['titre'])) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="association" class="mb-2 block text-sm font-semibold text-slate-700">Association</label>
                    <input type="text" id="association" name="association" value="<?= e(old('association', $event['association'])) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                </div>
                <div>
                    <label for="categorie" class="mb-2 block text-sm font-semibold text-slate-700">Catégorie</label>
                    <?php $currentCategory = old('categorie', category_slug($event['categorie'])); ?>
                    <select id="categorie" name="categorie" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                        <option value="">Choisir</option>
                        <option value="soiree" <?= $currentCategory === 'soiree' ? 'selected' : '' ?>>Soirée</option>
                        <option value="sport" <?= $currentCategory === 'sport' ? 'selected' : '' ?>>Sport</option>
                        <option value="culture" <?= $currentCategory === 'culture' ? 'selected' : '' ?>>Culture</option>
                    </select>
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-3">
                <div>
                    <label for="date_evenement" class="mb-2 block text-sm font-semibold text-slate-700">Date et heure</label>
                    <input type="datetime-local" id="date_evenement" name="date_evenement" value="<?= e(old('date_evenement', (new DateTime($event['date_evenement']))->format('Y-m-d\TH:i'))) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                </div>
                <div>
                    <label for="capacite_max" class="mb-2 block text-sm font-semibold text-slate-700">Capacité maximale</label>
                    <input type="number" min="<?= e((string) max(1, event_reserved_places($event, $pdo))) ?>" id="capacite_max" name="capacite_max" value="<?= e(old('capacite_max', (string) $event['capacite_max'])) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                </div>
                <div>
                    <label for="prix" class="mb-2 block text-sm font-semibold text-slate-700">Prix en EUR</label>
                    <input type="number" min="0" step="0.01" id="prix" name="prix" value="<?= e(old('prix', (string) event_price($event))) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                </div>
            </div>

            <div>
                <label for="lieu" class="mb-2 block text-sm font-semibold text-slate-700">Lieu</label>
                <input type="text" id="lieu" name="lieu" value="<?= e(old('lieu', $event['lieu'])) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
            </div>

            <div>
                <label for="adresse_complete" class="mb-2 block text-sm font-semibold text-slate-700">Adresse complète pour la carte</label>
                <input type="text" id="adresse_complete" name="adresse_complete" value="<?= e(old('adresse_complete', (string) ($event['adresse_complete'] ?? ''))) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div>
                <label for="description" class="mb-2 block text-sm font-semibold text-slate-700">Description courte</label>
                <textarea id="description" name="description" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2" required><?= e(old('description', $event['description'])) ?></textarea>
            </div>

            <div>
                <label for="details_complets" class="mb-2 block text-sm font-semibold text-slate-700">Informations complémentaires</label>
                <textarea id="details_complets" name="details_complets" rows="5" class="w-full rounded-lg border border-slate-300 px-3 py-2"><?= e(old('details_complets', (string) $event['details_complets'])) ?></textarea>
            </div>

            <div class="rounded-2xl bg-slate-50 p-5">
                <p class="mb-3 text-sm font-semibold text-slate-700">Image actuelle</p>
                <img src="<?= e(event_image($event['image'], $event['categorie'])) ?>" alt="<?= e($event['titre']) ?>" class="h-40 w-full rounded-2xl object-cover md:w-72">

                <label for="image" class="mb-2 mt-5 block text-sm font-semibold text-slate-700">Remplacer l’image</label>
                <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp,.gif" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700">
                    Enregistrer les modifications
                </button>
                <a href="<?= e(url('pages/detail-evenement.php?id=' . $eventId)) ?>" class="rounded-lg bg-slate-200 px-6 py-3 font-semibold text-slate-800 hover:bg-slate-300">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
