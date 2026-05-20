<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_role(['organisateur', 'administrateur']);

$eventId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$event = fetch_event_by_id($pdo, $eventId);

if ($event === null) {
    set_flash('error', 'Evenement introuvable.');
    redirect_to('pages/dashboard-organisateur.php');
}

if (!can_manage_event($event)) {
    set_flash('error', 'Vous ne pouvez pas voir les inscrits de cet evenement.');
    redirect_to('pages/catalogue.php');
}

$page_title = 'Inscrits - ' . $event['titre'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_presence') {
    $reservationId = (int) ($_POST['reservation_id'] ?? 0);

    $reservationStmt = $pdo->prepare(
        'SELECT r.id, r.presence_validee, r.statut
         FROM reservations r
         WHERE r.id = :reservation_id
           AND r.evenement_id = :event_id
         LIMIT 1'
    );
    $reservationStmt->execute([
        'reservation_id' => $reservationId,
        'event_id' => $eventId,
    ]);
    $reservation = $reservationStmt->fetch();

    if ($reservation === false || $reservation['statut'] !== 'reserve') {
        set_flash('error', 'Reservation introuvable ou non active.');
    } else {
        $newValue = (int) $reservation['presence_validee'] === 1 ? 0 : 1;
        $updateStmt = $pdo->prepare('UPDATE reservations SET presence_validee = :presence_validee WHERE id = :id');
        $updateStmt->execute([
            'presence_validee' => $newValue,
            'id' => $reservationId,
        ]);
        set_flash('success', $newValue === 1 ? 'Presence validee.' : 'Validation de presence retiree.');
    }

    redirect_to('pages/inscrits-evenement.php?id=' . $eventId);
}

$registrationsStmt = $pdo->prepare(
    'SELECT r.*,
            u.prenom,
            u.nom,
            u.email
     FROM reservations r
     INNER JOIN users u ON u.id = r.user_id
     WHERE r.evenement_id = :event_id
     ORDER BY FIELD(r.statut, "reserve", "annule"), u.nom ASC, u.prenom ASC'
);
$registrationsStmt->execute(['event_id' => $eventId]);
$registrations = $registrationsStmt->fetchAll();

$waitlistStmt = $pdo->prepare(
    'SELECT l.*,
            u.prenom,
            u.nom,
            u.email
     FROM listes_attente l
     INNER JOIN users u ON u.id = l.user_id
     WHERE l.evenement_id = :event_id
       AND l.statut = "en_attente"
     ORDER BY l.position_attente ASC, l.created_at ASC'
);
$waitlistStmt->execute(['event_id' => $eventId]);
$waitlistEntries = $waitlistStmt->fetchAll();

$reservedCount = 0;
$cancelledCount = 0;
$validatedCount = 0;

foreach ($registrations as $registration) {
    if ($registration['statut'] === 'reserve') {
        $reservedCount++;
    } else {
        $cancelledCount++;
    }

    if ((int) $registration['presence_validee'] === 1) {
        $validatedCount++;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="mx-auto max-w-6xl px-4 py-12 md:px-8">
    <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <a href="<?= e(url('pages/dashboard-organisateur.php')) ?>" class="text-sm font-semibold text-blue-600 hover:text-blue-700">&larr; Retour au dashboard</a>
            <h1 class="mt-3 text-4xl font-bold text-slate-900">Liste des inscrits</h1>
            <p class="mt-2 text-slate-600"><?= e($event['titre']) ?> - <?= e(format_event_date($event['date_evenement'])) ?></p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="<?= e(url('pages/detail-evenement.php?id=' . $eventId)) ?>" class="rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-700">
                Voir la page publique
            </a>
            <a href="<?= e(url('pages/verification-billet.php?event_id=' . $eventId)) ?>" class="rounded-lg bg-white px-5 py-3 font-semibold text-slate-800 ring-1 ring-slate-300 hover:bg-slate-100">
                Verifier un billet
            </a>
        </div>
    </div>

    <div class="mb-8 grid gap-4 sm:grid-cols-4">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm text-slate-500">Reservations actives</p>
            <p class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) $reservedCount) ?></p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm text-slate-500">Presences validees</p>
            <p class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) $validatedCount) ?></p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm text-slate-500">Reservations annulees</p>
            <p class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) $cancelledCount) ?></p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm text-slate-500">Liste d attente</p>
            <p class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) count($waitlistEntries)) ?></p>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Participant</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">E-mail</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Statut</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Paiement</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Presence</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">QR token</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if ($registrations === []): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-600">Aucun inscrit pour le moment.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($registrations as $registration): ?>
                        <?php $qrToken = ensure_reservation_qr_token($pdo, $registration); ?>
                        <tr>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900"><?= e($registration['prenom'] . ' ' . $registration['nom']) ?></p>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?= e($registration['email']) ?></td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $registration['statut'] === 'reserve' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' ?>">
                                    <?= e($registration['statut'] === 'reserve' ? 'Reserve' : 'Annule') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold <?= ($registration['payment_status'] ?? 'non_requis') === 'paye' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-700' ?>">
                                    <?= e(($registration['payment_status'] ?? 'non_requis') === 'paye' ? 'Paye' : 'Non requis') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold <?= (int) $registration['presence_validee'] === 1 ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-700' ?>">
                                    <?= e((int) $registration['presence_validee'] === 1 ? 'Validee' : 'Non validee') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500"><?= e($qrToken) ?></td>
                            <td class="px-6 py-4">
                                <?php if ($registration['statut'] === 'reserve'): ?>
                                    <form method="post">
                                        <input type="hidden" name="action" value="toggle_presence">
                                        <input type="hidden" name="reservation_id" value="<?= e((string) $registration['id']) ?>">
                                        <button type="submit" class="rounded-lg <?= (int) $registration['presence_validee'] === 1 ? 'bg-slate-200 text-slate-800 hover:bg-slate-300' : 'bg-blue-600 text-white hover:bg-blue-700' ?> px-4 py-2 text-sm font-semibold">
                                            <?= e((int) $registration['presence_validee'] === 1 ? 'Retirer la validation' : 'Valider la presence') ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-sm text-slate-500">Aucune action</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-10 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="mb-5 flex items-center justify-between gap-3">
            <h2 class="text-2xl font-bold text-slate-900">Liste d attente</h2>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-700"><?= e((string) count($waitlistEntries)) ?></span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Position</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Participant</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">E-mail</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Date d inscription</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if ($waitlistEntries === []): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-600">Personne n attend actuellement une place.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($waitlistEntries as $entry): ?>
                        <tr>
                            <td class="px-6 py-4 font-semibold text-slate-900"><?= e((string) $entry['position_attente']) ?></td>
                            <td class="px-6 py-4 text-slate-700"><?= e($entry['prenom'] . ' ' . $entry['nom']) ?></td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?= e($entry['email']) ?></td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?= e(format_event_date((string) $entry['created_at'], 'd/m/Y H:i')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
