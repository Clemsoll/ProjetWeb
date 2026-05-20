<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_role(['organisateur', 'administrateur']);

$page_title = 'Vérification billet';
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$eventFilterId = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;
$error = '';
$ticket = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'validate_presence') {
    $reservationId = (int) ($_POST['reservation_id'] ?? 0);

    $ticketStmt = $pdo->prepare(
        'SELECT r.*,
                e.titre,
                e.date_evenement,
                e.organisateur_id,
                u.prenom,
                u.nom,
                u.email
         FROM reservations r
         INNER JOIN evenements e ON e.id = r.evenement_id
         INNER JOIN users u ON u.id = r.user_id
         WHERE r.id = :reservation_id
         LIMIT 1'
    );
    $ticketStmt->execute(['reservation_id' => $reservationId]);
    $ticketToValidate = $ticketStmt->fetch();

    if ($ticketToValidate === false || !can_manage_event($ticketToValidate)) {
        set_flash('error', 'Billet introuvable ou non autorisé.');
        redirect_to('pages/verification-billet.php');
    }

    $updateStmt = $pdo->prepare('UPDATE reservations SET presence_validee = 1 WHERE id = :id AND statut = "reserve"');
    $updateStmt->execute(['id' => $reservationId]);

    set_flash('success', 'Présence validée avec succès.');
    redirect_to('pages/verification-billet.php?token=' . urlencode((string) $ticketToValidate['qr_token']));
}

if ($token !== '') {
    $sql = 'SELECT r.*,
                   e.titre,
                   e.date_evenement,
                   e.lieu,
                   e.association,
                   e.organisateur_id,
                   u.prenom,
                   u.nom,
                   u.email
            FROM reservations r
            INNER JOIN evenements e ON e.id = r.evenement_id
            INNER JOIN users u ON u.id = r.user_id
            WHERE r.qr_token = :token';
    $params = ['token' => $token];

    if ($eventFilterId > 0) {
        $sql .= ' AND r.evenement_id = :event_id';
        $params['event_id'] = $eventFilterId;
    }

    $sql .= ' LIMIT 1';

    $ticketStmt = $pdo->prepare($sql);
    $ticketStmt->execute($params);
    $ticket = $ticketStmt->fetch() ?: null;

    if ($ticket === null) {
        $error = 'Aucun billet ne correspond à ce QR token.';
    } elseif (!can_manage_event($ticket)) {
        $ticket = null;
        $error = 'Vous ne pouvez pas vérifier ce billet.';
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="mx-auto max-w-4xl px-4 py-12 md:px-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-slate-900">Vérification de billet</h1>
        <p class="mt-2 text-slate-600">Utilisez le QR token d’un billet pour vérifier rapidement une réservation et valider la présence.</p>
    </div>

    <div class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
        <?php if ($error !== ''): ?>
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="get" class="grid gap-4 md:grid-cols-[1fr,200px]">
            <?php if ($eventFilterId > 0): ?>
                <input type="hidden" name="event_id" value="<?= e((string) $eventFilterId) ?>">
            <?php endif; ?>
            <div>
                <label for="token" class="mb-2 block text-sm font-semibold text-slate-700">QR token</label>
                <input type="text" id="token" name="token" value="<?= e($token) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Collez le token ou ouvrez le lien du QR">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 font-semibold text-white hover:bg-blue-700">
                    Rechercher
                </button>
            </div>
        </form>

        <?php if ($ticket !== null): ?>
            <div class="mt-8 rounded-3xl bg-slate-50 p-6 ring-1 ring-slate-200">
                <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Billet</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900"><?= e($ticket['titre']) ?></h2>
                        <div class="mt-4 space-y-2 text-sm text-slate-700">
                            <p>Participant : <span class="font-semibold"><?= e($ticket['prenom'] . ' ' . $ticket['nom']) ?></span></p>
                            <p>E-mail : <?= e($ticket['email']) ?></p>
                            <p>Date : <?= e(format_event_date($ticket['date_evenement'])) ?></p>
                            <p>Lieu : <?= e($ticket['lieu']) ?></p>
                            <p>Référence : <span class="font-semibold"><?= e(reservation_reference((int) $ticket['id'], (int) $ticket['evenement_id'])) ?></span></p>
                            <p>QR token : <span class="font-semibold"><?= e((string) $ticket['qr_token']) ?></span></p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <span class="block rounded-full px-3 py-1 text-center text-xs font-semibold <?= $ticket['statut'] === 'reserve' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' ?>">
                            <?= e($ticket['statut'] === 'reserve' ? 'Réservation active' : 'Réservation annulée') ?>
                        </span>
                        <span class="block rounded-full px-3 py-1 text-center text-xs font-semibold <?= ($ticket['payment_status'] ?? 'non_requis') === 'paye' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-700' ?>">
                            <?= e(($ticket['payment_status'] ?? 'non_requis') === 'paye' ? 'Paiement payé' : 'Paiement non requis') ?>
                        </span>
                        <span class="block rounded-full px-3 py-1 text-center text-xs font-semibold <?= (int) $ticket['presence_validee'] === 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
                            <?= e((int) $ticket['presence_validee'] === 1 ? 'Présence déjà validée' : 'Présence non validée') ?>
                        </span>
                    </div>
                </div>

                <?php if ($ticket['statut'] === 'reserve' && (int) $ticket['presence_validee'] === 0): ?>
                    <form method="post" class="mt-6">
                        <input type="hidden" name="action" value="validate_presence">
                        <input type="hidden" name="reservation_id" value="<?= e((string) $ticket['id']) ?>">
                        <input type="hidden" name="token" value="<?= e((string) $ticket['qr_token']) ?>">
                        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700">
                            Valider la présence
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
