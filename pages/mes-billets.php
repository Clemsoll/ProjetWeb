<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_role('participant');

$page_title = 'Mes billets';
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'cancel_reservation') {
        $reservationId = (int) ($_POST['reservation_id'] ?? 0);

        try {
            $pdo->beginTransaction();

            $lockStmt = $pdo->prepare(
                'SELECT r.id, r.evenement_id
                 FROM reservations r
                 INNER JOIN evenements e ON e.id = r.evenement_id
                 WHERE r.id = :reservation_id
                   AND r.user_id = :user_id
                   AND r.statut = "reserve"
                   AND e.date_evenement >= NOW()
                 LIMIT 1
                 FOR UPDATE'
            );
            $lockStmt->execute([
                'reservation_id' => $reservationId,
                'user_id' => $user['id'],
            ]);
            $lockedReservation = $lockStmt->fetch();

            if ($lockedReservation === false) {
                throw new RuntimeException('Réservation introuvable ou déjà terminée.');
            }

            $updateStmt = $pdo->prepare('UPDATE reservations SET statut = "annule", presence_validee = 0 WHERE id = :id');
            $updateStmt->execute(['id' => $reservationId]);

            $eventStmt = $pdo->prepare(
                'UPDATE evenements
                 SET places_reservees = CASE
                     WHEN places_reservees > 0 THEN places_reservees - 1
                     ELSE 0
                 END
                 WHERE id = :id'
            );
            $eventStmt->execute(['id' => $lockedReservation['evenement_id']]);

            promote_waitlist($pdo, (int) $lockedReservation['evenement_id']);

            $pdo->commit();
            set_flash('success', 'Votre réservation a bien été annulée.');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            set_flash('error', $exception->getMessage());
        }

        redirect_to('pages/mes-billets.php');
    }

    if ($action === 'cancel_waitlist') {
        $waitlistId = (int) ($_POST['waitlist_id'] ?? 0);

        $stmt = $pdo->prepare(
            'UPDATE listes_attente
             SET statut = "annulee"
             WHERE id = :id
               AND user_id = :user_id
               AND statut = "en_attente"'
        );
        $stmt->execute([
            'id' => $waitlistId,
            'user_id' => $user['id'],
        ]);

        set_flash($stmt->rowCount() === 1 ? 'success' : 'error', $stmt->rowCount() === 1 ? 'Vous avez quitté la liste d’attente.' : 'Inscription en liste d’attente introuvable.');
        redirect_to('pages/mes-billets.php');
    }
}

$reservationsStmt = $pdo->prepare(
    'SELECT r.*,
            e.titre,
            e.date_evenement,
            e.lieu,
            e.adresse_complete,
            e.association,
            e.categorie,
            e.image,
            e.prix,
            e.statut AS evenement_statut
     FROM reservations r
     INNER JOIN evenements e ON e.id = r.evenement_id
     WHERE r.user_id = :user_id
     ORDER BY e.date_evenement ASC'
);
$reservationsStmt->execute(['user_id' => $user['id']]);
$reservations = $reservationsStmt->fetchAll();

$waitlistStmt = $pdo->prepare(
    'SELECT l.*,
            e.titre,
            e.date_evenement,
            e.lieu,
            e.association,
            e.categorie,
            e.image,
            e.prix,
            e.statut AS evenement_statut
     FROM listes_attente l
     INNER JOIN evenements e ON e.id = l.evenement_id
     WHERE l.user_id = :user_id
       AND l.statut = "en_attente"
     ORDER BY e.date_evenement ASC, l.position_attente ASC'
);
$waitlistStmt->execute(['user_id' => $user['id']]);
$waitlistEntries = $waitlistStmt->fetchAll();

$upcomingReservations = [];
$pastReservations = [];
$now = new DateTime();

foreach ($reservations as $reservation) {
    $eventDate = new DateTime($reservation['date_evenement']);

    if ($eventDate >= $now) {
        $upcomingReservations[] = $reservation;
    } else {
        $pastReservations[] = $reservation;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="mx-auto max-w-6xl px-4 py-12 md:px-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-slate-900">Mes billets</h1>
        <p class="mt-2 text-slate-600">Retrouvez vos réservations à venir, vos anciens billets et vos listes d’attente.</p>
    </div>

    <?php if ($waitlistEntries !== []): ?>
        <div class="mb-10 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="mb-5 flex items-center justify-between gap-3">
                <h2 class="text-2xl font-bold text-slate-900">Mes listes d’attente</h2>
                <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-700"><?= e((string) count($waitlistEntries)) ?></span>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <?php foreach ($waitlistEntries as $entry): ?>
                    <article class="rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200">
                        <div class="flex flex-col gap-4 sm:flex-row">
                            <img src="<?= e(event_image($entry['image'], $entry['categorie'])) ?>" alt="<?= e($entry['titre']) ?>" class="h-24 w-full rounded-2xl object-cover sm:w-36">
                            <div class="flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-900"><?= e($entry['titre']) ?></h3>
                                        <p class="text-sm text-slate-500"><?= e($entry['association']) ?></p>
                                    </div>
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                        Position <?= e((string) $entry['position_attente']) ?>
                                    </span>
                                </div>
                                <div class="mt-3 space-y-1 text-sm text-slate-700">
                                    <p><?= e(format_event_date($entry['date_evenement'])) ?></p>
                                    <p><?= e($entry['lieu']) ?></p>
                                    <p><?= e(format_price((float) $entry['prix'])) ?></p>
                                </div>
                                <div class="mt-4 flex flex-wrap gap-3">
                                    <a href="<?= e(url('pages/detail-evenement.php?id=' . (int) $entry['evenement_id'])) ?>" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                                        Voir l’événement
                                    </a>
                                    <form method="post">
                                        <input type="hidden" name="action" value="cancel_waitlist">
                                        <input type="hidden" name="waitlist_id" value="<?= e((string) $entry['id']) ?>">
                                        <button type="submit" class="rounded-lg bg-red-100 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-200">
                                            Quitter la liste
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid gap-10 lg:grid-cols-2">
        <div>
            <div class="mb-5 flex items-center justify-between gap-3">
                <h2 class="text-2xl font-bold text-slate-900">Événements à venir</h2>
                <span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700"><?= e((string) count($upcomingReservations)) ?></span>
            </div>

            <div class="space-y-5">
                <?php if ($upcomingReservations === []): ?>
                    <div class="rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200">
                        <p class="text-slate-600">Aucune réservation à venir.</p>
                        <a href="<?= e(url('pages/catalogue.php')) ?>" class="mt-4 inline-block font-semibold text-blue-600 hover:text-blue-700">
                            Découvrir les événements
                        </a>
                    </div>
                <?php endif; ?>

                <?php foreach ($upcomingReservations as $reservation): ?>
                    <?php
                    $reservationStatus = $reservation['statut'] === 'reserve' ? 'Réservé' : 'Annulé';
                    $reservationClass = $reservation['statut'] === 'reserve'
                        ? 'bg-emerald-100 text-emerald-700'
                        : 'bg-slate-200 text-slate-700';
                    $qrToken = ensure_reservation_qr_token($pdo, $reservation);
                    ?>
                    <article class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <div class="flex flex-col gap-5 md:flex-row md:items-start">
                            <img src="<?= e(event_image($reservation['image'], $reservation['categorie'])) ?>" alt="<?= e($reservation['titre']) ?>" class="h-28 w-full rounded-2xl object-cover md:w-40">

                            <div class="flex-1">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <h3 class="text-xl font-bold text-slate-900"><?= e($reservation['titre']) ?></h3>
                                        <p class="text-sm text-slate-500"><?= e($reservation['association']) ?></p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold <?= e($reservationClass) ?>">
                                            <?= e($reservationStatus) ?>
                                        </span>
                                        <?php if ($reservation['evenement_statut'] === 'annule'): ?>
                                            <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">Événement annulé</span>
                                        <?php endif; ?>
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700"><?= e(format_price((float) $reservation['prix'])) ?></span>
                                    </div>
                                </div>

                                <div class="mt-4 space-y-1 text-sm text-slate-700">
                                    <p><?= e(format_event_date($reservation['date_evenement'])) ?></p>
                                    <p><?= e($reservation['lieu']) ?></p>
                                    <p>Référence billet : <span class="font-semibold"><?= e(reservation_reference((int) $reservation['id'], (int) $reservation['evenement_id'])) ?></span></p>
                                    <p>Statut paiement : <span class="font-semibold"><?= e($reservation['payment_status'] === 'paye' ? 'Payé' : 'Non requis') ?></span></p>
                                </div>

                                <div class="mt-4 flex flex-wrap items-center gap-4">
                                    <img src="<?= e(ticket_qr_image_url($qrToken)) ?>" alt="QR code du billet" class="h-24 w-24 rounded-2xl bg-white p-1 ring-1 ring-slate-200">
                                    <p class="text-xs text-slate-500">QR token : <?= e($qrToken) ?></p>
                                </div>

                                <div class="mt-5 flex flex-wrap gap-3">
                                    <a href="<?= e(url('pages/detail-evenement.php?id=' . (int) $reservation['evenement_id'])) ?>" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">
                                        Voir l’événement
                                    </a>

                                    <?php if ($reservation['statut'] === 'reserve' && $reservation['evenement_statut'] === 'actif'): ?>
                                        <form method="post">
                                            <input type="hidden" name="action" value="cancel_reservation">
                                            <input type="hidden" name="reservation_id" value="<?= e((string) $reservation['id']) ?>">
                                            <button type="submit" class="rounded-lg bg-red-100 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-200">
                                                Annuler la réservation
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <div class="mb-5 flex items-center justify-between gap-3">
                <h2 class="text-2xl font-bold text-slate-900">Événements passés</h2>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700"><?= e((string) count($pastReservations)) ?></span>
            </div>

            <div class="space-y-5">
                <?php if ($pastReservations === []): ?>
                    <div class="rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200">
                        <p class="text-slate-600">Aucun historique disponible pour le moment.</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($pastReservations as $reservation): ?>
                    <?php $qrToken = ensure_reservation_qr_token($pdo, $reservation); ?>
                    <article class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <div class="flex flex-col gap-5 md:flex-row md:items-start">
                            <img src="<?= e(event_image($reservation['image'], $reservation['categorie'])) ?>" alt="<?= e($reservation['titre']) ?>" class="h-28 w-full rounded-2xl object-cover opacity-80 md:w-40">

                            <div class="flex-1">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <h3 class="text-xl font-bold text-slate-900"><?= e($reservation['titre']) ?></h3>
                                        <p class="text-sm text-slate-500"><?= e($reservation['association']) ?></p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">Passe</span>
                                        <?php if ((int) $reservation['presence_validee'] === 1): ?>
                                            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">Présence validée</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mt-4 space-y-1 text-sm text-slate-700">
                                    <p><?= e(format_event_date($reservation['date_evenement'])) ?></p>
                                    <p><?= e($reservation['lieu']) ?></p>
                                    <p>Référence billet : <span class="font-semibold"><?= e(reservation_reference((int) $reservation['id'], (int) $reservation['evenement_id'])) ?></span></p>
                                    <p class="text-xs text-slate-500">QR token : <?= e($qrToken) ?></p>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
