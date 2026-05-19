<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_role('administrateur');

$page_title = 'Administration';
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'validate_organizer') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $stmt = $pdo->prepare(
            'UPDATE users
             SET statut_organisateur = "valide"
             WHERE id = :id
               AND role = "organisateur"'
        );
        $stmt->execute(['id' => $userId]);
        set_flash('success', 'Compte organisateur validé.');
    }

    if ($action === 'delete_user') {
        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($userId === (int) $user['id']) {
            set_flash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        } else {
            $checkStmt = $pdo->prepare(
                'SELECT
                    (SELECT COUNT(*) FROM evenements WHERE organisateur_id = :id) AS nb_evenements,
                    (SELECT COUNT(*) FROM reservations WHERE user_id = :id) AS nb_reservations'
            );
            $checkStmt->execute(['id' => $userId]);
            $usage = $checkStmt->fetch();

            if ($usage !== false && (((int) $usage['nb_evenements']) > 0 || ((int) $usage['nb_reservations']) > 0)) {
                set_flash('error', 'Impossible de supprimer ce compte tant que des événements ou réservations y sont liés.');
            } else {
                $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
                $stmt->execute(['id' => $userId]);
                set_flash('success', 'Compte utilisateur supprimé.');
            }
        }
    }

    if ($action === 'toggle_event_status') {
        $eventId = (int) ($_POST['event_id'] ?? 0);
        $event = fetch_event_by_id($pdo, $eventId);

        if ($event !== null) {
            $newStatus = $event['statut'] === 'actif' ? 'annule' : 'actif';
            $stmt = $pdo->prepare('UPDATE evenements SET statut = :statut WHERE id = :id');
            $stmt->execute([
                'statut' => $newStatus,
                'id' => $eventId,
            ]);
            set_flash('success', $newStatus === 'annule' ? 'Événement annulé.' : 'Événement réactivé.');
        }
    }

    if ($action === 'delete_event') {
        $eventId = (int) ($_POST['event_id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM evenements WHERE id = :id');
        $stmt->execute(['id' => $eventId]);
        set_flash('success', 'Événement supprimé définitivement.');
    }

    redirect_to('pages/admin.php');
}

$pendingOrganizers = $pdo->query(
    'SELECT * FROM users
     WHERE role = "organisateur"
       AND statut_organisateur = "en_attente"
     ORDER BY created_at ASC'
)->fetchAll();

$users = $pdo->query(
    'SELECT u.*,
            (SELECT COUNT(*) FROM evenements e WHERE e.organisateur_id = u.id) AS nb_evenements,
            (SELECT COUNT(*) FROM reservations r WHERE r.user_id = u.id AND r.statut = "reserve") AS nb_reservations
     FROM users u
     ORDER BY FIELD(u.role, "administrateur", "organisateur", "participant"), u.created_at DESC'
)->fetchAll();

$events = $pdo->query(
    'SELECT e.*,
            CONCAT(u.prenom, " ", u.nom) AS organisateur_nom
     FROM evenements e
     LEFT JOIN users u ON u.id = e.organisateur_id
     ORDER BY e.date_evenement DESC'
)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<section class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-slate-900">Administration</h1>
        <p class="mt-2 text-slate-600">Supervisez les comptes, validez les organisateurs et modérez les événements.</p>
    </div>

    <div class="mb-10 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm text-slate-500">Utilisateurs</p>
            <p class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) count($users)) ?></p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm text-slate-500">Organisateurs en attente</p>
            <p class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) count($pendingOrganizers)) ?></p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm text-slate-500">Événements</p>
            <p class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) count($events)) ?></p>
        </div>
    </div>

    <div class="mb-10 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 class="text-2xl font-bold text-slate-900">Organisateurs à valider</h2>

        <div class="mt-5 space-y-4">
            <?php if ($pendingOrganizers === []): ?>
                <p class="text-slate-600">Aucun compte organisateur en attente.</p>
            <?php endif; ?>

            <?php foreach ($pendingOrganizers as $pendingOrganizer): ?>
                <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 p-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900"><?= e(user_full_name($pendingOrganizer)) ?></h3>
                        <p class="text-sm text-slate-600"><?= e($pendingOrganizer['email']) ?></p>
                    </div>
                    <form method="post">
                        <input type="hidden" name="action" value="validate_organizer">
                        <input type="hidden" name="user_id" value="<?= e((string) $pendingOrganizer['id']) ?>">
                        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700">
                            Valider le compte
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="mb-10 rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-200 px-6 py-5">
            <h2 class="text-2xl font-bold text-slate-900">Utilisateurs</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Nom</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">E-mail</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Rôle</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Statut</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Activité</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php foreach ($users as $listedUser): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900"><?= e(user_full_name($listedUser)) ?></p>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?= e($listedUser['email']) ?></td>
                            <td class="px-6 py-4 text-sm text-slate-700"><?= e($listedUser['role']) ?></td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $listedUser['statut_organisateur'] === 'valide' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
                                    <?= e($listedUser['statut_organisateur']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <p><?= e((string) $listedUser['nb_evenements']) ?> événement(s)</p>
                                <p><?= e((string) $listedUser['nb_reservations']) ?> réservation(s)</p>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ((int) $listedUser['id'] !== (int) $user['id']): ?>
                                    <form method="post" onsubmit="return confirm('Supprimer ce compte ?');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= e((string) $listedUser['id']) ?>">
                                        <button type="submit" class="rounded-lg bg-red-100 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-200">
                                            Supprimer
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-sm text-slate-500">Compte actuel</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-200 px-6 py-5">
            <h2 class="text-2xl font-bold text-slate-900">Événements</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Titre</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Organisateur</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Date</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Statut</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Inscriptions</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <a href="<?= e(url('pages/detail-evenement.php?id=' . (int) $event['id'])) ?>" class="font-semibold text-slate-900 hover:text-blue-600">
                                    <?= e($event['titre']) ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?= e($event['organisateur_nom'] ?: 'Non renseigné') ?></td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?= e(format_event_date($event['date_evenement'])) ?></td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $event['statut'] === 'actif' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' ?>">
                                    <?= e($event['statut']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?= e((string) $event['places_reservees']) ?></td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <form method="post">
                                        <input type="hidden" name="action" value="toggle_event_status">
                                        <input type="hidden" name="event_id" value="<?= e((string) $event['id']) ?>">
                                        <button type="submit" class="rounded-lg <?= $event['statut'] === 'actif' ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' ?> px-4 py-2 text-sm font-semibold">
                                            <?= e($event['statut'] === 'actif' ? 'Annuler' : 'Réactiver') ?>
                                        </button>
                                    </form>
                                    <form method="post" onsubmit="return confirm('Supprimer définitivement cet événement ?');">
                                        <input type="hidden" name="action" value="delete_event">
                                        <input type="hidden" name="event_id" value="<?= e((string) $event['id']) ?>">
                                        <button type="submit" class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">
                                            Supprimer
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
