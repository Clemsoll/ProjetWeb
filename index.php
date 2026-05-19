<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$page_title = 'Accueil';

$sql = 'SELECT e.*,
               CONCAT(u.prenom, " ", u.nom) AS organisateur_nom
        FROM evenements e
        LEFT JOIN users u ON u.id = e.organisateur_id
        WHERE e.date_evenement >= NOW()
          AND e.statut = "actif"
        ORDER BY e.date_evenement ASC
        LIMIT 6';

$events = $pdo->query($sql)->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<section class="bg-gradient-to-r from-blue-700 to-slate-900 text-white">
    <div class="mx-auto max-w-6xl px-4 py-16 md:px-8 md:py-24">
        <div class="max-w-3xl">
            <p class="mb-3 text-sm font-semibold uppercase tracking-[0.3em] text-blue-200">Billetterie étudiante Omnes</p>
            <h1 class="mb-5 text-4xl font-bold leading-tight md:text-5xl">
                Découvrez les événements Omnes et réservez vos places en quelques clics.
            </h1>
            <p class="text-lg text-blue-100">
                OmnesEvent centralise les soirées, événements sportifs et rendez-vous culturels des associations étudiantes.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="<?= e(url('pages/catalogue.php')) ?>" class="rounded-lg bg-white px-6 py-3 font-semibold text-blue-700 hover:bg-blue-50">
                    Explorer le catalogue
                </a>
                <?php if (!is_logged_in()): ?>
                    <a href="<?= e(url('pages/register.php')) ?>" class="rounded-lg border border-blue-200 px-6 py-3 font-semibold text-white hover:bg-white/10">
                        Créer un compte
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="mx-auto max-w-6xl px-4 py-10 md:px-8">
    <form method="get" action="<?= e(url('pages/catalogue.php')) ?>" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="grid gap-4 md:grid-cols-4">
            <div>
                <label for="categorie" class="mb-2 block text-sm font-semibold text-slate-700">Catégorie</label>
                <select id="categorie" name="categorie" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">Toutes</option>
                    <option value="soiree">Soirée</option>
                    <option value="sport">Sport</option>
                    <option value="culture">Culture</option>
                </select>
            </div>
            <div>
                <label for="date" class="mb-2 block text-sm font-semibold text-slate-700">Date</label>
                <input type="date" id="date" name="date" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label for="association" class="mb-2 block text-sm font-semibold text-slate-700">Association</label>
                <input type="text" id="association" name="association" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="BDE, BDS, Junior...">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 font-semibold text-white hover:bg-blue-700">
                    Rechercher
                </button>
            </div>
        </div>
    </form>
</section>

<section class="mx-auto max-w-6xl px-4 pb-16 md:px-8">
    <div class="mb-8 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold text-slate-900">Événements à venir</h2>
            <p class="mt-2 text-slate-600">Les prochains rendez-vous ouverts à la réservation.</p>
        </div>
        <a href="<?= e(url('pages/catalogue.php')) ?>" class="text-sm font-semibold text-blue-600 hover:text-blue-700">
            Voir tout le catalogue →
        </a>
    </div>

    <?php if ($events === []): ?>
        <div class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200">
            <p class="text-slate-600">Aucun événement à venir pour le moment.</p>
        </div>
    <?php else: ?>
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($events as $event): ?>
                <?php
                $reserved = (int) $event['places_reservees'];
                $capacity = (int) $event['capacite_max'];
                [$badgeClass, $badgeLabel] = event_badge($event['statut'], $reserved, $capacity);
                $remaining = max(0, $capacity - $reserved);
                ?>
                <article class="event-card overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <img
                        src="<?= e(event_image($event['image'], $event['categorie'])) ?>"
                        alt="<?= e($event['titre']) ?>"
                        class="h-52 w-full object-cover"
                    >
                    <div class="p-5">
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                <?= e($event['categorie']) ?>
                            </span>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold <?= e($badgeClass) ?>">
                                <?= e($badgeLabel) ?>
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900"><?= e($event['titre']) ?></h3>
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
                            Voir le détail
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
