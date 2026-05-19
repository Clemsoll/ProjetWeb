<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_approved_organizer();

$page_title = 'Dashboard organisateur';
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $eventId = (int) ($_POST['event_id'] ?? 0);
    $event = fetch_event_by_id($pdo, $eventId);

    if ($event === null || !can_manage_event($event, $user)) {
        set_flash('error', 'Vous ne pouvez pas modifier cet événement.');
        redirect_to('pages/dashboard-organisateur.php');
    }

    if ($action === 'toggle_event_status') {
        $newStatus = $event['statut'] === 'actif' ? 'annule' : 'actif';
        $stmt = $pdo->prepare('UPDATE evenements SET statut = :statut WHERE id = :id');
        $stmt->execute([
            'statut' => $newStatus,
            'id' => $eventId,
        ]);

        set_flash('success', $newStatus === 'annule' ? 'Événement annulé.' : 'Événement réactivé.');
        redirect_to('pages/dashboard-organisateur.php');
    }

    if ($action === 'delete_event') {
        $stmt = $pdo->prepare('DELETE FROM evenements WHERE id = :id');
        $stmt->execute(['id' => $eventId]);

        set_flash('success', 'Événement supprimé définitivement.');
        redirect_to('pages/dashboard-organisateur.php');
    }
}

$eventsStmt = $pdo->prepare(
    'SELECT e.*
     FROM evenements e
     WHERE e.organisateur_id = :organisateur_id
     ORDER BY e.date_evenement DESC'
);
$eventsStmt->execute(['organisateur_id' => $user['id']]);
$events = $eventsStmt->fetchAll();

$stats = [
    'total' => count($events),
    'actifs' => 0,
    'annules' => 0,
    'inscriptions' => 0,
];

foreach ($events as $event) {
    $stats['inscriptions'] += (int) $event['places_reservees'];

    if ($event['statut'] === 'actif') {
        $stats['actifs']++;
    } else {
        $stats['annules']++;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="mx-auto max-w-6xl px-4 py-12 md:px-8">
    <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-4xl font-bold text-slate-900">Dashboard organisateur</h1>
            <p class="mt-2 text-slate-600">Gérez vos événements, consultez les inscrits et validez leur présence.</p>
        </div>
        <a href="<?= e(url('pages/creer-evenement.php')) ?>" class="rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700">
            Créer un nouvel événement
        </a>
    </div>

    <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm text-slate-500">Événements créés</p>
            <p class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) $stats['total']) ?></p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm text-slate-500">Événements actifs</p>
            <p class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) $stats['actifs']) ?></p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm text-slate-500">Événements annulés</p>
            <p class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) $stats['annules']) ?></p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm text-slate-500">Inscriptions totales</p>
            <p class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) $stats['inscriptions']) ?></p>
        </div>
    </div>

    <div class="space-y-5">
        <?php if ($events === []): ?>
            <div class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200">
                <p class="text-slate-600">Vous n'avez pas encore créé d'événement.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($events as $event): ?>
            <?php
            $reserved = (int) $event['places_reservees'];
            $capacity = (int) $event['capacite_max'];
            $remaining = max(0, $capacity - $reserved);
            ?>
            <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start">
                    <img src="<?= e(event_image($event['image'], $event['categorie'])) ?>" alt="<?= e($event['titre']) ?>" class="h-36 w-full rounded-2xl object-cover lg:w-56">

                    <div class="flex-1">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"><?= e($event['categorie']) ?></span>
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $event['statut'] === 'actif' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' ?>">
                                        <?= e($event['statut'] === 'actif' ? 'Actif' : 'Annulé') ?>
                                    </span>
                                </div>
                                <h2 class="mt-3 text-2xl font-bold text-slate-900"><?= e($event['titre']) ?></h2>
                                <p class="mt-1 text-sm text-slate-500"><?= e($event['association']) ?></p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                <p><?= e(format_event_date($event['date_evenement'])) ?></p>
                                <p><?= e($event['lieu']) ?></p>
                                <p><?= e($reserved) ?> / <?= e($capacity) ?> inscrits</p>
                                <p><?= e($remaining) ?> place(s) restante(s)</p>
                            </div>
                        </div>

                        <p class="mt-4 text-slate-600"><?= e($event['description']) ?></p>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <a href="<?= e(url('pages/detail-evenement.php?id=' . (int) $event['id'])) ?>" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">
                                Voir l'événement
                            </a>
                            <a href="<?= e(url('pages/modifier-evenement.php?id=' . (int) $event['id'])) ?>" class="rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 ring-1 ring-slate-300 hover:bg-slate-100">
                                Modifier
                            </a>
                            <a href="<?= e(url('pages/inscrits-evenement.php?id=' . (int) $event['id'])) ?>" class="rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 ring-1 ring-slate-300 hover:bg-slate-100">
                                Voir les inscrits
                            </a>
                            <form method="post">
                                <input type="hidden" name="action" value="toggle_event_status">
                                <input type="hidden" name="event_id" value="<?= e((string) $event['id']) ?>">
                                <button type="submit" class="rounded-lg <?= $event['statut'] === 'actif' ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' ?> px-4 py-2.5 text-sm font-semibold">
                                    <?= e($event['statut'] === 'actif' ? 'Annuler' : 'Réactiver') ?>
                                </button>
                            </form>
                            <form method="post" onsubmit="return confirm('Supprimer définitivement cet événement ?');">
                                <input type="hidden" name="action" value="delete_event">
                                <input type="hidden" name="event_id" value="<?= e((string) $event['id']) ?>">
                                <button type="submit" class="rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800">
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
