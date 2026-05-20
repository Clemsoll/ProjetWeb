<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$page_title = 'Calendrier';

$monthInput = trim((string) ($_GET['mois'] ?? date('Y-m')));
$monthDate = DateTime::createFromFormat('Y-m', $monthInput);

if ($monthDate === false) {
    $monthDate = new DateTime(date('Y-m-01'));
} else {
    $monthDate->setDate((int) $monthDate->format('Y'), (int) $monthDate->format('m'), 1);
}

$monthStart = (clone $monthDate)->setTime(0, 0, 0);
$monthEnd = (clone $monthStart)->modify('last day of this month')->setTime(23, 59, 59);
$calendarStart = (clone $monthStart)->modify('-' . ((int) $monthStart->format('N') - 1) . ' days');
$calendarEnd = (clone $monthEnd)->modify('+' . (7 - (int) $monthEnd->format('N')) . ' days');

$prevMonth = (clone $monthStart)->modify('-1 month')->format('Y-m');
$nextMonth = (clone $monthStart)->modify('+1 month')->format('Y-m');

$stmt = $pdo->prepare(
    'SELECT e.*
     FROM evenements e
     WHERE e.date_evenement BETWEEN :start_date AND :end_date
     ORDER BY e.date_evenement ASC'
);
$stmt->execute([
    'start_date' => $calendarStart->format('Y-m-d H:i:s'),
    'end_date' => $calendarEnd->format('Y-m-d H:i:s'),
]);
$events = $stmt->fetchAll();

$eventsByDay = [];
foreach ($events as $event) {
    $dayKey = (new DateTime($event['date_evenement']))->format('Y-m-d');
    $eventsByDay[$dayKey][] = $event;
}

$days = [];
$cursor = clone $calendarStart;
while ($cursor <= $calendarEnd) {
    $days[] = clone $cursor;
    $cursor->modify('+1 day');
}

$weekdayLabels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
$monthLabels = [
    1 => 'Janvier',
    2 => 'Fevrier',
    3 => 'Mars',
    4 => 'Avril',
    5 => 'Mai',
    6 => 'Juin',
    7 => 'Juillet',
    8 => 'Aout',
    9 => 'Septembre',
    10 => 'Octobre',
    11 => 'Novembre',
    12 => 'Decembre',
];
$calendarTitle = $monthLabels[(int) $monthStart->format('n')] . ' ' . $monthStart->format('Y');

include __DIR__ . '/../includes/header.php';
?>

<section class="mx-auto max-w-6xl px-4 py-12 md:px-8">
    <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-4xl font-bold text-slate-900">Calendrier des evenements</h1>
            <p class="mt-2 text-slate-600">Explorez les evenements Omnes mois par mois avec une vue simple et mobile first.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="<?= e(url('pages/calendrier.php?mois=' . $prevMonth)) ?>" class="rounded-lg bg-white px-4 py-2.5 font-semibold text-slate-800 ring-1 ring-slate-300 hover:bg-slate-100">
                Mois precedent
            </a>
            <a href="<?= e(url('pages/calendrier.php?mois=' . $nextMonth)) ?>" class="rounded-lg bg-white px-4 py-2.5 font-semibold text-slate-800 ring-1 ring-slate-300 hover:bg-slate-100">
                Mois suivant
            </a>
        </div>
    </div>

    <div class="mb-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-900"><?= e($calendarTitle) ?></h2>
                <p class="mt-1 text-sm text-slate-600">Les evenements annules restent visibles pour garder le contexte.</p>
            </div>
            <form method="get" class="flex gap-3">
                <input type="month" name="mois" value="<?= e($monthStart->format('Y-m')) ?>" class="rounded-lg border border-slate-300 px-3 py-2">
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">Aller</button>
            </form>
        </div>
    </div>

    <div class="mb-4 hidden grid-cols-7 gap-4 md:grid">
        <?php foreach ($weekdayLabels as $label): ?>
            <div class="px-3 text-sm font-semibold uppercase tracking-wide text-slate-500"><?= e($label) ?></div>
        <?php endforeach; ?>
    </div>

    <div class="grid gap-4 md:grid-cols-7">
        <?php foreach ($days as $day): ?>
            <?php
            $dayKey = $day->format('Y-m-d');
            $isCurrentMonth = $day->format('m') === $monthStart->format('m');
            $dayEvents = $eventsByDay[$dayKey] ?? [];
            ?>
            <article class="min-h-[180px] rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-200 <?= $isCurrentMonth ? '' : 'opacity-60' ?>">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 md:hidden"><?= e($weekdayLabels[(int) $day->format('N') - 1]) ?></p>
                        <p class="text-2xl font-bold text-slate-900"><?= e($day->format('d')) ?></p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700"><?= e((string) count($dayEvents)) ?></span>
                </div>

                <div class="space-y-3">
                    <?php if ($dayEvents === []): ?>
                        <p class="text-sm text-slate-400">Aucun evenement</p>
                    <?php endif; ?>

                    <?php foreach ($dayEvents as $event): ?>
                        <?php
                        $reserved = event_reserved_places($event, $pdo);
                        [$badgeClass, $badgeLabel] = event_badge($event['statut'], $reserved, (int) $event['capacite_max']);
                        ?>
                        <a href="<?= e(url('pages/detail-evenement.php?id=' . (int) $event['id'])) ?>" class="block rounded-2xl bg-slate-50 p-3 hover:bg-slate-100">
                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700"><?= e($event['categorie']) ?></span>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold <?= e($badgeClass) ?>"><?= e($badgeLabel) ?></span>
                            </div>
                            <p class="font-semibold text-slate-900"><?= e($event['titre']) ?></p>
                            <p class="mt-1 text-xs text-slate-500"><?= e(format_event_date($event['date_evenement'], 'H:i')) ?> - <?= e($event['association']) ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
