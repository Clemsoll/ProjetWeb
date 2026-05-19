<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$page_title = 'Catalogue';

$categoryFilter = normalize_category($_GET['categorie'] ?? null);
$dateFilter = trim((string) ($_GET['date'] ?? ''));
$associationFilter = trim((string) ($_GET['association'] ?? ''));

$conditions = ['e.date_evenement >= NOW()'];
$params = [];

if ($categoryFilter !== null) {
    $conditions[] = 'e.categorie = :categorie';
    $params['categorie'] = $categoryFilter;
}

if ($dateFilter !== '') {
    $conditions[] = 'DATE(e.date_evenement) = :date_evenement';
    $params['date_evenement'] = $dateFilter;
}

if ($associationFilter !== '') {
    $conditions[] = 'e.association LIKE :association';
    $params['association'] = '%' . $associationFilter . '%';
}

$sql = 'SELECT e.*,
               CONCAT(u.prenom, " ", u.nom) AS organisateur_nom
        FROM evenements e
        LEFT JOIN users u ON u.id = e.organisateur_id
        WHERE ' . implode(' AND ', $conditions) . '
        ORDER BY e.date_evenement ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

$associations = $pdo->query('SELECT DISTINCT association FROM evenements ORDER BY association ASC')->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/../includes/header.php';
?>

<section class="mx-auto max-w-6xl px-4 py-12 md:px-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-slate-900">Catalogue des événements</h1>
        <p class="mt-2 text-slate-600">Filtrez les événements à venir par date, catégorie ou association.</p>
    </div>

    <div class="grid gap-8 lg:grid-cols-[280px,1fr]">
        <aside>
            <form method="get" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-5 text-lg font-bold text-slate-900">Filtres</h2>

                <div class="mb-5">
                    <label for="categorie" class="mb-2 block text-sm font-semibold text-slate-700">Catégorie</label>
                    <select id="categorie" name="categorie" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">Toutes</option>
                        <option value="soiree" <?= $categoryFilter === 'Soirée' ? 'selected' : '' ?>>Soirée</option>
                        <option value="sport" <?= $categoryFilter === 'Sport' ? 'selected' : '' ?>>Sport</option>
                        <option value="culture" <?= $categoryFilter === 'Culture' ? 'selected' : '' ?>>Culture</option>
                    </select>
                </div>

                <div class="mb-5">
                    <label for="date" class="mb-2 block text-sm font-semibold text-slate-700">Date</label>
                    <input type="date" id="date" name="date" value="<?= e($dateFilter) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div class="mb-5">
                    <label for="association" class="mb-2 block text-sm font-semibold text-slate-700">Association</label>
                    <input list="associations-list" type="text" id="association" name="association" value="<?= e($associationFilter) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="BDE, BDS...">
                    <datalist id="associations-list">
                        <?php foreach ($associations as $association): ?>
                            <option value="<?= e($association) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="space-y-3">
                    <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 font-semibold text-white hover:bg-blue-700">
                        Appliquer
                    </button>
                    <a href="<?= e(url('pages/catalogue.php')) ?>" class="block rounded-lg bg-slate-200 px-4 py-2.5 text-center font-semibold text-slate-800 hover:bg-slate-300">
                        Réinitialiser
                    </a>
                </div>
            </form>
        </aside>

        <div>
            <?php if ($events === []): ?>
                <div class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200">
                    <p class="text-lg font-semibold text-slate-800">Aucun événement ne correspond à votre recherche.</p>
                    <p class="mt-2 text-sm text-slate-600">Essayez d'élargir vos filtres ou revenez plus tard.</p>
                </div>
            <?php else: ?>
                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <?php foreach ($events as $event): ?>
                        <?php
                        $reserved = (int) $event['places_reservees'];
                        $capacity = (int) $event['capacite_max'];
                        $remaining = max(0, $capacity - $reserved);
                        [$badgeClass, $badgeLabel] = event_badge($event['statut'], $reserved, $capacity);
                        ?>
                        <article class="event-card overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                            <img src="<?= e(event_image($event['image'], $event['categorie'])) ?>" alt="<?= e($event['titre']) ?>" class="h-48 w-full object-cover">
                            <div class="p-5">
                                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                        <?= e($event['categorie']) ?>
                                    </span>
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold <?= e($badgeClass) ?>">
                                        <?= e($badgeLabel) ?>
                                    </span>
                                </div>

                                <h2 class="text-xl font-bold text-slate-900"><?= e($event['titre']) ?></h2>
                                <p class="mt-1 text-sm text-slate-500"><?= e($event['association']) ?></p>
                                <p class="mt-3 line-clamp-3 text-sm text-slate-600"><?= e($event['description']) ?></p>

                                <div class="mt-4 space-y-1 text-sm text-slate-700">
                                    <p><?= e(format_event_date($event['date_evenement'])) ?></p>
                                    <p><?= e($event['lieu']) ?></p>
                                    <p><?= e($remaining) ?> place(s) restante(s)</p>
                                </div>

                                <a
                                    href="<?= e(url('pages/detail-evenement.php?id=' . (int) $event['id'])) ?>"
                                    class="mt-5 block rounded-lg bg-slate-900 px-4 py-2.5 text-center font-semibold text-white hover:bg-slate-700"
                                >
                                    Détail de l'événement
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
