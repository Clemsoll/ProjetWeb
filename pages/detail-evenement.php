<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$eventId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$event = fetch_event_by_id($pdo, $eventId);

if ($event === null) {
    set_flash('error', 'Evenement introuvable.');
    redirect_to('pages/catalogue.php');
}

$page_title = $event['titre'];
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'reserve') {
        if ($user === null) {
            set_flash('error', 'Connectez-vous pour reserver cet evenement.');
            redirect_to('pages/login.php');
        }

        if ($user['role'] !== 'participant') {
            set_flash('error', 'Seuls les participants peuvent reserver un evenement.');
            redirect_to('pages/detail-evenement.php?id=' . $eventId);
        }

        try {
            $pdo->beginTransaction();

            $reservationStmt = $pdo->prepare(
                'SELECT *
                 FROM reservations
                 WHERE user_id = :user_id
                   AND evenement_id = :evenement_id
                 LIMIT 1
                 FOR UPDATE'
            );
            $reservationStmt->execute([
                'user_id' => $user['id'],
                'evenement_id' => $eventId,
            ]);
            $existingReservation = $reservationStmt->fetch();

            if ($existingReservation !== false && $existingReservation['statut'] === 'reserve') {
                throw new RuntimeException('Vous etes deja inscrit a cet evenement.');
            }

            $eventLockStmt = $pdo->prepare(
                'SELECT *
                 FROM evenements
                 WHERE id = :id
                 LIMIT 1
                 FOR UPDATE'
            );
            $eventLockStmt->execute(['id' => $eventId]);
            $lockedEvent = $eventLockStmt->fetch();

            if ($lockedEvent === false) {
                throw new RuntimeException('Evenement introuvable.');
            }

            if ($lockedEvent['statut'] === 'annule') {
                throw new RuntimeException('Impossible de reserver un evenement annule.');
            }

            if (event_is_paid($lockedEvent)) {
                throw new RuntimeException('Cet evenement est payant. Utilisez la page de paiement pour finaliser votre billet.');
            }

            $capacityStmt = $pdo->prepare(
                'UPDATE evenements
                 SET places_reservees = places_reservees + 1
                 WHERE id = :id
                   AND statut = "actif"
                   AND places_reservees < capacite_max'
            );
            $capacityStmt->execute(['id' => $eventId]);

            if ($capacityStmt->rowCount() !== 1) {
                throw new RuntimeException('Cet evenement est complet. Vous pouvez rejoindre la liste d attente.');
            }

            $waitlistStmt = $pdo->prepare(
                'SELECT id
                 FROM listes_attente
                 WHERE user_id = :user_id
                   AND evenement_id = :evenement_id
                   AND statut = "en_attente"
                 LIMIT 1
                 FOR UPDATE'
            );
            $waitlistStmt->execute([
                'user_id' => $user['id'],
                'evenement_id' => $eventId,
            ]);
            $waitlistEntry = $waitlistStmt->fetch();

            $qrToken = generate_qr_token();

            if ($existingReservation !== false && $existingReservation['statut'] === 'annule') {
                $updateReservation = $pdo->prepare(
                    'UPDATE reservations
                     SET statut = "reserve",
                         presence_validee = 0,
                         created_at = NOW(),
                         qr_token = :qr_token,
                         payment_status = "non_requis",
                         payment_reference = NULL
                     WHERE id = :id'
                );
                $updateReservation->execute([
                    'qr_token' => $qrToken,
                    'id' => $existingReservation['id'],
                ]);
            } else {
                $insertReservation = $pdo->prepare(
                    'INSERT INTO reservations (
                        user_id,
                        evenement_id,
                        statut,
                        presence_validee,
                        qr_token,
                        payment_status,
                        payment_reference
                    ) VALUES (
                        :user_id,
                        :evenement_id,
                        "reserve",
                        0,
                        :qr_token,
                        "non_requis",
                        NULL
                    )'
                );
                $insertReservation->execute([
                    'user_id' => $user['id'],
                    'evenement_id' => $eventId,
                    'qr_token' => $qrToken,
                ]);
            }

            if ($waitlistEntry !== false) {
                $cancelWaitlist = $pdo->prepare('UPDATE listes_attente SET statut = "convertie" WHERE id = :id');
                $cancelWaitlist->execute(['id' => $waitlistEntry['id']]);
            }

            $pdo->commit();
            set_flash('success', 'Votre reservation a bien ete enregistree.');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            set_flash('error', $exception->getMessage());
        }

        redirect_to('pages/detail-evenement.php?id=' . $eventId);
    }

    if ($action === 'join_waitlist') {
        if ($user === null) {
            set_flash('error', 'Connectez-vous pour rejoindre la liste d attente.');
            redirect_to('pages/login.php');
        }

        if ($user['role'] !== 'participant') {
            set_flash('error', 'Seuls les participants peuvent rejoindre la liste d attente.');
            redirect_to('pages/detail-evenement.php?id=' . $eventId);
        }

        try {
            $pdo->beginTransaction();

            $eventLockStmt = $pdo->prepare(
                'SELECT *
                 FROM evenements
                 WHERE id = :id
                 LIMIT 1
                 FOR UPDATE'
            );
            $eventLockStmt->execute(['id' => $eventId]);
            $lockedEvent = $eventLockStmt->fetch();

            if ($lockedEvent === false) {
                throw new RuntimeException('Evenement introuvable.');
            }

            if ($lockedEvent['statut'] === 'annule') {
                throw new RuntimeException('Impossible de rejoindre la liste d attente d un evenement annule.');
            }

            if ((int) $lockedEvent['places_reservees'] < (int) $lockedEvent['capacite_max']) {
                throw new RuntimeException('Il reste encore des places disponibles. Vous pouvez reserver directement.');
            }

            $reservationStmt = $pdo->prepare(
                'SELECT *
                 FROM reservations
                 WHERE user_id = :user_id
                   AND evenement_id = :event_id
                 LIMIT 1
                 FOR UPDATE'
            );
            $reservationStmt->execute([
                'user_id' => $user['id'],
                'event_id' => $eventId,
            ]);
            $existingReservation = $reservationStmt->fetch();

            if ($existingReservation !== false && $existingReservation['statut'] === 'reserve') {
                throw new RuntimeException('Vous etes deja inscrit a cet evenement.');
            }

            $waitlistStmt = $pdo->prepare(
                'SELECT *
                 FROM listes_attente
                 WHERE user_id = :user_id
                   AND evenement_id = :event_id
                 LIMIT 1
                 FOR UPDATE'
            );
            $waitlistStmt->execute([
                'user_id' => $user['id'],
                'event_id' => $eventId,
            ]);
            $existingWaitlist = $waitlistStmt->fetch();

            if ($existingWaitlist !== false && $existingWaitlist['statut'] === 'en_attente') {
                throw new RuntimeException('Vous etes deja sur la liste d attente.');
            }

            $positionStmt = $pdo->prepare(
                'SELECT COALESCE(MAX(position_attente), 0) + 1
                 FROM listes_attente
                 WHERE evenement_id = :event_id
                   AND statut = "en_attente"'
            );
            $positionStmt->execute(['event_id' => $eventId]);
            $position = (int) $positionStmt->fetchColumn();

            if ($existingWaitlist !== false) {
                $updateWaitlist = $pdo->prepare(
                    'UPDATE listes_attente
                     SET position_attente = :position_attente,
                         statut = "en_attente",
                         created_at = NOW()
                     WHERE id = :id'
                );
                $updateWaitlist->execute([
                    'position_attente' => $position,
                    'id' => $existingWaitlist['id'],
                ]);
            } else {
                $insertWaitlist = $pdo->prepare(
                    'INSERT INTO listes_attente (user_id, evenement_id, position_attente, statut)
                     VALUES (:user_id, :event_id, :position_attente, "en_attente")'
                );
                $insertWaitlist->execute([
                    'user_id' => $user['id'],
                    'event_id' => $eventId,
                    'position_attente' => $position,
                ]);
            }

            $pdo->commit();
            set_flash('success', 'Vous avez rejoint la liste d attente. Position actuelle : ' . $position . '.');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            set_flash('error', $exception->getMessage());
        }

        redirect_to('pages/detail-evenement.php?id=' . $eventId);
    }
}

$event = fetch_event_by_id($pdo, $eventId);
$reserved = event_reserved_places($event, $pdo);
$capacity = (int) $event['capacite_max'];
$remaining = max(0, $capacity - $reserved);
$isComplete = $remaining <= 0;
$isCancelled = $event['statut'] === 'annule';
$waitlistCount = event_waitlist_count($pdo, $eventId);

$userReservation = null;
$userWaitlist = null;

if ($user !== null) {
    $reservationStmt = $pdo->prepare(
        'SELECT *
         FROM reservations
         WHERE user_id = :user_id
           AND evenement_id = :evenement_id
         LIMIT 1'
    );
    $reservationStmt->execute([
        'user_id' => $user['id'],
        'evenement_id' => $eventId,
    ]);
    $userReservation = $reservationStmt->fetch() ?: null;

    if ($user['role'] === 'participant') {
        $userWaitlist = fetch_waitlist_entry($pdo, $eventId, (int) $user['id']);
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="mx-auto max-w-5xl px-4 py-12 md:px-8">
    <div class="mb-6">
        <a href="<?= e(url('pages/catalogue.php')) ?>" class="text-sm font-semibold text-blue-600 hover:text-blue-700">
            &larr; Retour au catalogue
        </a>
    </div>

    <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
        <img src="<?= e(event_image($event['image'], $event['categorie'])) ?>" alt="<?= e($event['titre']) ?>" class="h-72 w-full object-cover md:h-96">

        <div class="grid gap-10 p-6 md:grid-cols-[1.6fr,0.9fr] md:p-10">
            <div>
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                        <?= e($event['categorie']) ?>
                    </span>
                    <?php [$badgeClass, $badgeLabel] = event_badge($event['statut'], $reserved, $capacity); ?>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold <?= e($badgeClass) ?>">
                        <?= e($badgeLabel) ?>
                    </span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        <?= e(format_price(event_price($event))) ?>
                    </span>
                </div>

                <h1 class="text-4xl font-bold text-slate-900"><?= e($event['titre']) ?></h1>
                <p class="mt-2 text-lg text-slate-600"><?= e($event['association']) ?></p>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Date</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900"><?= e(format_event_date($event['date_evenement'])) ?></p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Lieu</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900"><?= e($event['lieu']) ?></p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Places restantes</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900"><?= e($remaining) ?> / <?= e($capacity) ?></p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Liste d attente</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900"><?= e((string) $waitlistCount) ?> personne(s)</p>
                    </div>
                </div>

                <div class="mt-8">
                    <h2 class="text-2xl font-bold text-slate-900">Description</h2>
                    <p class="mt-4 text-slate-700"><?= nl2br(e($event['description'])) ?></p>

                    <?php if (!empty($event['details_complets'])): ?>
                        <div class="mt-5 rounded-2xl bg-slate-50 p-5 text-slate-700">
                            <?= nl2br(e($event['details_complets'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-8">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900">Lieu sur la carte</h2>
                            <p class="mt-1 text-sm text-slate-600"><?= e(event_map_query($event)) ?></p>
                        </div>
                        <a href="<?= e(event_map_link($event)) ?>" target="_blank" rel="noopener noreferrer" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-800 ring-1 ring-slate-300 hover:bg-slate-100">
                            Ouvrir la carte
                        </a>
                    </div>
                    <div class="overflow-hidden rounded-3xl ring-1 ring-slate-200">
                        <iframe src="<?= e(event_map_embed_url($event)) ?>" class="h-80 w-full border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>

            <aside class="rounded-3xl bg-slate-50 p-6">
                <h2 class="text-xl font-bold text-slate-900">Reservation</h2>
                <p class="mt-2 text-sm text-slate-600">
                    <?= e($reserved) ?> personne(s) inscrite(s) sur <?= e($capacity) ?> place(s).
                </p>

                <div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-200">
                    <div class="h-full rounded-full bg-blue-600" style="width: <?= e((string) min(100, (int) round(($reserved / max(1, $capacity)) * 100))) ?>%"></div>
                </div>

                <div class="mt-6 rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Tarif</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900"><?= e(format_price(event_price($event))) ?></p>
                    <?php if (event_is_paid($event)): ?>
                        <p class="mt-2 text-sm text-slate-600">Le paiement est simule avant la validation du billet.</p>
                    <?php endif; ?>
                </div>

                <?php if ($userReservation !== null && $userReservation['statut'] === 'reserve'): ?>
                    <?php $qrToken = ensure_reservation_qr_token($pdo, $userReservation); ?>
                    <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                        Vous etes deja inscrit a cet evenement.
                    </div>
                    <div class="mt-4 rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                        <p class="text-sm text-slate-500">Billet</p>
                        <p class="mt-2 font-semibold text-slate-900"><?= e(reservation_reference((int) $userReservation['id'], $eventId)) ?></p>
                        <p class="mt-1 text-xs text-slate-500">QR token : <?= e($qrToken) ?></p>
                        <div class="mt-4 flex justify-center">
                            <img src="<?= e(ticket_qr_image_url($qrToken)) ?>" alt="QR code du billet" class="h-40 w-40 rounded-2xl bg-white p-2 ring-1 ring-slate-200">
                        </div>
                    </div>
                <?php elseif ($isCancelled): ?>
                    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-100 p-4 text-sm text-slate-700">
                        Cet evenement a ete annule.
                    </div>
                <?php elseif ($userWaitlist !== null): ?>
                    <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
                        Vous etes deja sur la liste d attente. Position : <?= e((string) $userWaitlist['position_attente']) ?>.
                    </div>
                <?php elseif ($user === null): ?>
                    <a href="<?= e(url('pages/login.php')) ?>" class="mt-6 block rounded-lg bg-blue-600 px-4 py-3 text-center font-semibold text-white hover:bg-blue-700">
                        Se connecter pour reserver
                    </a>
                <?php elseif ($user['role'] !== 'participant'): ?>
                    <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
                        Seuls les comptes participants peuvent reserver.
                    </div>
                <?php elseif ($isComplete): ?>
                    <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        Toutes les places sont deja reservees.
                    </div>
                    <form method="post" class="mt-4">
                        <input type="hidden" name="action" value="join_waitlist">
                        <button type="submit" class="w-full rounded-lg bg-amber-500 px-4 py-3 font-semibold text-white hover:bg-amber-600">
                            Rejoindre la liste d attente
                        </button>
                    </form>
                <?php elseif (event_is_paid($event)): ?>
                    <a href="<?= e(url('pages/paiement.php?event_id=' . $eventId)) ?>" class="mt-6 block rounded-lg bg-blue-600 px-4 py-3 text-center font-semibold text-white hover:bg-blue-700">
                        Payer et reserver
                    </a>
                <?php else: ?>
                    <form method="post" class="mt-6">
                        <input type="hidden" name="action" value="reserve">
                        <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-3 font-semibold text-white hover:bg-blue-700">
                            Reserver ma place
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($user !== null && can_manage_event($event, $user)): ?>
                    <div class="mt-8 space-y-3 border-t border-slate-200 pt-6">
                        <a href="<?= e(url('pages/modifier-evenement.php?id=' . $eventId)) ?>" class="block rounded-lg bg-slate-900 px-4 py-3 text-center font-semibold text-white hover:bg-slate-700">
                            Modifier l evenement
                        </a>
                        <a href="<?= e(url('pages/inscrits-evenement.php?id=' . $eventId)) ?>" class="block rounded-lg bg-white px-4 py-3 text-center font-semibold text-slate-800 ring-1 ring-slate-300 hover:bg-slate-100">
                            Voir les inscrits
                        </a>
                        <a href="<?= e(url('pages/verification-billet.php?event_id=' . $eventId)) ?>" class="block rounded-lg bg-white px-4 py-3 text-center font-semibold text-slate-800 ring-1 ring-slate-300 hover:bg-slate-100">
                            Verifier un billet
                        </a>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
