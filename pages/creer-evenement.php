<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_approved_organizer();

$page_title = 'Créer un événement';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim((string) ($_POST['titre'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $details = trim((string) ($_POST['details_complets'] ?? ''));
    $dateInput = trim((string) ($_POST['date_evenement'] ?? ''));
    $lieu = trim((string) ($_POST['lieu'] ?? ''));
    $categorie = normalize_category($_POST['categorie'] ?? null);
    $association = trim((string) ($_POST['association'] ?? ''));
    $capaciteMax = (int) ($_POST['capacite_max'] ?? 0);

    $dateObject = DateTime::createFromFormat('Y-m-d\TH:i', $dateInput);

    if ($titre === '' || $description === '' || $dateInput === '' || $lieu === '' || $association === '' || $capaciteMax <= 0 || $categorie === null) {
        $error = 'Merci de remplir tous les champs obligatoires.';
    } elseif ($dateObject === false) {
        $error = 'La date et l\'heure de l\'événement sont invalides.';
    } else {
        try {
            $imagePath = upload_event_image($_FILES['image'] ?? []);

            $stmt = $pdo->prepare(
                'INSERT INTO evenements (
                    titre,
                    description,
                    details_complets,
                    date_evenement,
                    lieu,
                    categorie,
                    association,
                    image,
                    capacite_max,
                    statut,
                    organisateur_id
                ) VALUES (
                    :titre,
                    :description,
                    :details_complets,
                    :date_evenement,
                    :lieu,
                    :categorie,
                    :association,
                    :image,
                    :capacite_max,
                    "actif",
                    :organisateur_id
                )'
            );

            $stmt->execute([
                'titre' => $titre,
                'description' => $description,
                'details_complets' => $details !== '' ? $details : null,
                'date_evenement' => $dateObject->format('Y-m-d H:i:s'),
                'lieu' => $lieu,
                'categorie' => $categorie,
                'association' => $association,
                'image' => $imagePath,
                'capacite_max' => $capaciteMax,
                'organisateur_id' => current_user()['id'],
            ]);

            set_flash('success', 'Événement créé avec succès.');
            redirect_to('pages/dashboard-organisateur.php');
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="mx-auto max-w-3xl px-4 py-12 md:px-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-slate-900">Créer un événement</h1>
        <p class="mt-2 text-slate-600">Publiez un nouvel événement et ouvrez les réservations pour les participants.</p>
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
                <input type="text" id="titre" name="titre" value="<?= e(old('titre')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="association" class="mb-2 block text-sm font-semibold text-slate-700">Association</label>
                    <input type="text" id="association" name="association" value="<?= e(old('association')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                </div>
                <div>
                    <label for="categorie" class="mb-2 block text-sm font-semibold text-slate-700">Catégorie</label>
                    <select id="categorie" name="categorie" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                        <option value="">Choisir</option>
                        <option value="soiree" <?= old('categorie') === 'soiree' ? 'selected' : '' ?>>Soirée</option>
                        <option value="sport" <?= old('categorie') === 'sport' ? 'selected' : '' ?>>Sport</option>
                        <option value="culture" <?= old('categorie') === 'culture' ? 'selected' : '' ?>>Culture</option>
                    </select>
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="date_evenement" class="mb-2 block text-sm font-semibold text-slate-700">Date et heure</label>
                    <input type="datetime-local" id="date_evenement" name="date_evenement" value="<?= e(old('date_evenement')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                </div>
                <div>
                    <label for="capacite_max" class="mb-2 block text-sm font-semibold text-slate-700">Capacité maximale</label>
                    <input type="number" min="1" id="capacite_max" name="capacite_max" value="<?= e(old('capacite_max')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                </div>
            </div>

            <div>
                <label for="lieu" class="mb-2 block text-sm font-semibold text-slate-700">Lieu</label>
                <input type="text" id="lieu" name="lieu" value="<?= e(old('lieu')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
            </div>

            <div>
                <label for="description" class="mb-2 block text-sm font-semibold text-slate-700">Description courte</label>
                <textarea id="description" name="description" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2" required><?= e(old('description')) ?></textarea>
            </div>

            <div>
                <label for="details_complets" class="mb-2 block text-sm font-semibold text-slate-700">Informations complémentaires</label>
                <textarea id="details_complets" name="details_complets" rows="5" class="w-full rounded-lg border border-slate-300 px-3 py-2"><?= e(old('details_complets')) ?></textarea>
            </div>

            <div>
                <label for="image" class="mb-2 block text-sm font-semibold text-slate-700">Affiche (optionnel)</label>
                <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp,.gif" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <p class="mt-2 text-xs text-slate-500">Formats acceptés : JPG, PNG, WEBP, GIF. Taille max : 5 Mo.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700">
                    Créer l'événement
                </button>
                <a href="<?= e(url('pages/dashboard-organisateur.php')) ?>" class="rounded-lg bg-slate-200 px-6 py-3 font-semibold text-slate-800 hover:bg-slate-300">
                    Retour au dashboard
                </a>
            </div>
        </form>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
