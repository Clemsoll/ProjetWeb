<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$eventId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$event = fetch_event_by_id($pdo, $eventId);

if ($event === null) {
    set_flash('error', 'Événement introuvable.');
    redirect_to('pages/catalogue.php');
}

$page_title = $event['titre'];
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reserve') {
    if ($user === null) {
        set_flash('error', 'Connectez-vous pour réserver cet événement.');
        redirect_to('pages/login.php');
    }

    if ($user['role'] !== 'participant') {
        set_flash('error', 'Seuls les participants peuvent réserver un événement.');
        redirect_to('pages/detail-evenement.php?id=' . $eventId);
    }

    try {
        $pdo->beginTransaction();

        $reservationStmt = $pdo->prepare(
            'SELECT id, statut
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
            throw new RuntimeException('Vous êtes déjà inscrit à cet événement.');
        }

        $eventLockStmt = $pdo->prepare(
            'SELECT id, statut
             FROM evenements
             WHERE id = :id
             LIMIT 1
             FOR UPDATE'
        );
        $eventLockStmt->execute(['id' => $eventId]);
        $lockedEvent = $eventLockStmt->fetch();

        if ($lockedEvent === false) {
            throw new RuntimeException('Événement introuvable.');
        }

        if ($lockedEvent['statut'] === 'annule') {
            throw new RuntimeException('Impossible de réserver un événement annulé.');
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
            throw new RuntimeException('Cet événement est complet.');
        }

        if ($existingReservation !== false && $existingReservation['statut'] === 'annule') {
            $updateReservation = $pdo->prepare(
                'UPDATE reservations
                 SET statut = "reserve", presence_validee = 0, created_at = NOW()
                 WHERE id = :id'
            );
            $updateReservation->execute(['id' => $existingReservation['id']]);
        } else {
            $insertReservation = $pdo->prepare(
                'INSERT INTO reservations (user_id, evenement_id, statut, presence_validee)
                 VALUES (:user_id, :evenement_id, "reserve", 0)'
            );
            $insertReservation->execute([
                'user_id' => $user['id'],
                'evenement_id' => $eventId,
            ]);
        }

        $pdo->commit();
        set_flash('success', 'Votre réservation a bien été enregistrée.');
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        set_flash('error', $exception->getMessage());
    }

    redirect_to('pages/detail-evenement.php?id=' . $eventId);
}

$reserved = (int) $event['places_reservees'];
$capacity = (int) $event['capacite_max'];
$remaining = max(0, $capacity - $reserved);
$isComplete = $remaining <= 0;
$isCancelled = $event['statut'] === 'annule';

$userReservation = null;

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
}

include __DIR__ . '/../includes/header.php';
?>

<section class="mx-auto max-w-5xl px-4 py-12 md:px-8">
    <div class="mb-6">
        <a href="<?= e(url('pages/catalogue.php')) ?>" class="text-sm font-semibold text-blue-600 hover:text-blue-700">
            ← Retour au catalogue
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
                        <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Organisateur</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900"><?= e($event['organisateur_nom'] ?: 'Non renseigné') ?></p>
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
            </div>

            <aside class="rounded-3xl bg-slate-50 p-6">
                <h2 class="text-xl font-bold text-slate-900">Réservation</h2>
                <p class="mt-2 text-sm text-slate-600">
                    <?= e($reserved) ?> personne(s) inscrite(s) sur <?= e($capacity) ?> place(s).
                </p>

                <div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-200">
                    <div class="h-full rounded-full bg-blue-600" style="width: <?= e((string) min(100, (int) round(($reserved / max(1, $capacity)) * 100))) ?>%"></div>
                </div>

                <?php if ($userReservation !== null && $userReservation['statut'] === 'reserve'): ?>
                    <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                        Vous êtes déjà inscrit à cet événement.
                    </div>
                <?php elseif ($isCancelled): ?>
                    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-100 p-4 text-sm text-slate-700">
                        Cet événement a été annulé.
                    </div>
                <?php elseif ($isComplete): ?>
                    <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        Toutes les places ont déjà été réservées.
                    </div>
                <?php elseif ($user === null): ?>
                    <a href="<?= e(url('pages/login.php')) ?>" class="mt-6 block rounded-lg bg-blue-600 px-4 py-3 text-center font-semibold text-white hover:bg-blue-700">
                        Se connecter pour réserver
                    </a>
                <?php elseif ($user['role'] !== 'participant'): ?>
                    <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
                        Seuls les comptes participants peuvent réserver.
                    </div>
                <?php else: ?>
                    <form method="post" class="mt-6">
                        <input type="hidden" name="action" value="reserve">
                        <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-3 font-semibold text-white hover:bg-blue-700">
                            Réserver ma place
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($user !== null && can_manage_event($event, $user)): ?>
                    <div class="mt-8 space-y-3 border-t border-slate-200 pt-6">
                        <a href="<?= e(url('pages/modifier-evenement.php?id=' . $eventId)) ?>" class="block rounded-lg bg-slate-900 px-4 py-3 text-center font-semibold text-white hover:bg-slate-700">
                            Modifier l'événement
                        </a>
                        <a href="<?= e(url('pages/inscrits-evenement.php?id=' . $eventId)) ?>" class="block rounded-lg bg-white px-4 py-3 text-center font-semibold text-slate-800 ring-1 ring-slate-300 hover:bg-slate-100">
                            Voir les inscrits
                        </a>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
