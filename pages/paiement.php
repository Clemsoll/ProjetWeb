<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_role('participant');

$eventId = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;
$event = fetch_event_by_id($pdo, $eventId);

if ($event === null) {
    set_flash('error', 'Evenement introuvable.');
    redirect_to('pages/catalogue.php');
}

if (!event_is_paid($event)) {
    set_flash('info', 'Cet evenement est gratuit. Vous pouvez reserver directement.');
    redirect_to('pages/detail-evenement.php?id=' . $eventId);
}

$page_title = 'Paiement simule';
$user = current_user();
$error = '';

$reservationStmt = $pdo->prepare(
    'SELECT *
     FROM reservations
     WHERE user_id = :user_id
       AND evenement_id = :event_id
     LIMIT 1'
);
$reservationStmt->execute([
    'user_id' => $user['id'],
    'event_id' => $eventId,
]);
$existingReservation = $reservationStmt->fetch() ?: null;

if ($existingReservation !== null && $existingReservation['statut'] === 'reserve') {
    set_flash('info', 'Vous etes deja inscrit a cet evenement.');
    redirect_to('pages/detail-evenement.php?id=' . $eventId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulaire = trim((string) ($_POST['titulaire'] ?? ''));
    $numero = preg_replace('/\D+/', '', (string) ($_POST['numero_carte'] ?? ''));
    $expiration = trim((string) ($_POST['expiration'] ?? ''));
    $crypto = preg_replace('/\D+/', '', (string) ($_POST['crypto'] ?? ''));

    if ($titulaire === '' || strlen($numero) < 12 || $expiration === '' || strlen($crypto) < 3) {
        $error = 'Merci de renseigner des informations de paiement valides pour la simulation.';
    } else {
        try {
            $pdo->beginTransaction();

            $reservationLockStmt = $pdo->prepare(
                'SELECT *
                 FROM reservations
                 WHERE user_id = :user_id
                   AND evenement_id = :event_id
                 LIMIT 1
                 FOR UPDATE'
            );
            $reservationLockStmt->execute([
                'user_id' => $user['id'],
                'event_id' => $eventId,
            ]);
            $lockedReservation = $reservationLockStmt->fetch();

            if ($lockedReservation !== false && $lockedReservation['statut'] === 'reserve') {
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
                throw new RuntimeException('Impossible de payer un evenement annule.');
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
                throw new RuntimeException('Cet evenement est complet. Vous pouvez rejoindre la liste d attente depuis la page detail.');
            }

            $paymentReference = 'PAY-' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 12));
            $qrToken = generate_qr_token();

            if ($lockedReservation !== false) {
                $updateStmt = $pdo->prepare(
                    'UPDATE reservations
                     SET statut = "reserve",
                         presence_validee = 0,
                         created_at = NOW(),
                         qr_token = :qr_token,
                         payment_status = "paye",
                         payment_reference = :payment_reference
                     WHERE id = :id'
                );
                $updateStmt->execute([
                    'qr_token' => $qrToken,
                    'payment_reference' => $paymentReference,
                    'id' => (int) $lockedReservation['id'],
                ]);
            } else {
                $insertStmt = $pdo->prepare(
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
                        :event_id,
                        "reserve",
                        0,
                        :qr_token,
                        "paye",
                        :payment_reference
                     )'
                );
                $insertStmt->execute([
                    'user_id' => $user['id'],
                    'event_id' => $eventId,
                    'qr_token' => $qrToken,
                    'payment_reference' => $paymentReference,
                ]);
            }

            $waitlistStmt = $pdo->prepare(
                'UPDATE listes_attente
                 SET statut = "convertie"
                 WHERE user_id = :user_id
                   AND evenement_id = :event_id
                   AND statut = "en_attente"'
            );
            $waitlistStmt->execute([
                'user_id' => $user['id'],
                'event_id' => $eventId,
            ]);

            $pdo->commit();
            set_flash('success', 'Paiement simule valide. Votre billet est confirme.');
            redirect_to('pages/mes-billets.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $exception->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="mx-auto max-w-4xl px-4 py-12 md:px-8">
    <div class="mb-8">
        <a href="<?= e(url('pages/detail-evenement.php?id=' . $eventId)) ?>" class="text-sm font-semibold text-blue-600 hover:text-blue-700">&larr; Retour a l evenement</a>
        <h1 class="mt-3 text-4xl font-bold text-slate-900">Paiement simule</h1>
        <p class="mt-2 text-slate-600">Cette page simule un paiement en ligne pour valider votre billet.</p>
    </div>

    <div class="grid gap-8 lg:grid-cols-[1fr,340px]">
        <div class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
            <?php if ($error !== ''): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-6">
                <div>
                    <label for="titulaire" class="mb-2 block text-sm font-semibold text-slate-700">Nom du titulaire</label>
                    <input type="text" id="titulaire" name="titulaire" value="<?= e(old('titulaire', user_full_name($user))) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                </div>

                <div>
                    <label for="numero_carte" class="mb-2 block text-sm font-semibold text-slate-700">Numero de carte</label>
                    <input type="text" id="numero_carte" name="numero_carte" value="<?= e(old('numero_carte', '4242 4242 4242 4242')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="expiration" class="mb-2 block text-sm font-semibold text-slate-700">Expiration</label>
                        <input type="text" id="expiration" name="expiration" value="<?= e(old('expiration', '12/28')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                    </div>
                    <div>
                        <label for="crypto" class="mb-2 block text-sm font-semibold text-slate-700">Cryptogramme</label>
                        <input type="text" id="crypto" name="crypto" value="<?= e(old('crypto', '123')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                    </div>
                </div>

                <button type="submit" class="w-full rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700">
                    Payer <?= e(format_price(event_price($event))) ?>
                </button>
            </form>
        </div>

        <aside class="rounded-3xl bg-slate-900 p-6 text-white">
            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-blue-200">Recapitulatif</p>
            <h2 class="mt-4 text-2xl font-bold"><?= e($event['titre']) ?></h2>
            <div class="mt-5 space-y-2 text-sm text-slate-200">
                <p><?= e(format_event_date($event['date_evenement'])) ?></p>
                <p><?= e($event['lieu']) ?></p>
                <p><?= e($event['association']) ?></p>
            </div>

            <div class="mt-8 rounded-2xl bg-white/10 p-4">
                <p class="text-sm text-blue-100">Montant</p>
                <p class="mt-2 text-3xl font-bold"><?= e(format_price(event_price($event))) ?></p>
            </div>

            <p class="mt-6 text-sm text-slate-300">
                Paiement 100 % simule pour le projet. Aucune transaction bancaire reelle n est effectuee.
            </p>
        </aside>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
