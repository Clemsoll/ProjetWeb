<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$dbUser = fetch_user_by_id($pdo, (int) current_user()['id']);

if ($dbUser === null) {
    logout_user();
    set_flash('error', 'Votre session a expiré. Merci de vous reconnecter.');
    redirect_to('pages/login.php');
}

$page_title = 'Mon profil';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = trim((string) ($_POST['prenom'] ?? ''));
    $nom = trim((string) ($_POST['nom'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($prenom === '' || $nom === '' || $email === '') {
        $error = 'Le prénom, le nom et l\'e-mail sont obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse e-mail invalide.';
    } else {
        $emailStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
        $emailStmt->execute([
            'email' => $email,
            'id' => $dbUser['id'],
        ]);

        if ($emailStmt->fetch() !== false) {
            $error = 'Cet e-mail est déjà utilisé par un autre compte.';
        }
    }

    if ($error === '' && ($currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '')) {
        if (!password_verify($currentPassword, $dbUser['password'])) {
            $error = 'Le mot de passe actuel est incorrect.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'La confirmation du nouveau mot de passe ne correspond pas.';
        }
    }

    if ($error === '') {
        $sql = 'UPDATE users SET prenom = :prenom, nom = :nom, email = :email';
        $params = [
            'prenom' => $prenom,
            'nom' => $nom,
            'email' => $email,
            'id' => $dbUser['id'],
        ];

        if ($newPassword !== '') {
            $sql .= ', password = :password';
            $params['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $sql .= ' WHERE id = :id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $dbUser = fetch_user_by_id($pdo, (int) $dbUser['id']);

        if ($dbUser !== null) {
            login_user($dbUser);
        }

        set_flash('success', 'Profil mis à jour avec succès.');
        redirect_to('pages/mon-profil.php');
    }
}

$statsStmt = $pdo->prepare(
    'SELECT
        SUM(CASE WHEN r.statut = "reserve" AND e.date_evenement >= NOW() THEN 1 ELSE 0 END) AS billets_a_venir,
        SUM(CASE WHEN r.statut = "reserve" AND e.date_evenement < NOW() THEN 1 ELSE 0 END) AS billets_passes
     FROM reservations r
     INNER JOIN evenements e ON e.id = r.evenement_id
     WHERE r.user_id = :user_id'
);
$statsStmt->execute(['user_id' => $dbUser['id']]);
$ticketStats = $statsStmt->fetch() ?: ['billets_a_venir' => 0, 'billets_passes' => 0];

$eventCountStmt = $pdo->prepare('SELECT COUNT(*) FROM evenements WHERE organisateur_id = :organisateur_id');
$eventCountStmt->execute(['organisateur_id' => $dbUser['id']]);
$createdEvents = (int) $eventCountStmt->fetchColumn();

$roleLabel = match ($dbUser['role']) {
    'administrateur' => 'Administrateur',
    'organisateur' => 'Organisateur',
    default => 'Participant',
};

include __DIR__ . '/../includes/header.php';
?>

<section class="mx-auto max-w-5xl px-4 py-12 md:px-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-slate-900">Mon profil</h1>
        <p class="mt-2 text-slate-600">Consultez vos informations personnelles et mettez votre compte à jour.</p>
    </div>

    <div class="grid gap-8 lg:grid-cols-[320px,1fr]">
        <aside class="space-y-6">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-blue-600 text-2xl font-bold text-white">
                    <?= e(strtoupper(substr($dbUser['prenom'], 0, 1) . substr($dbUser['nom'], 0, 1))) ?>
                </div>

                <h2 class="mt-5 text-2xl font-bold text-slate-900"><?= e(user_full_name($dbUser)) ?></h2>
                <p class="mt-1 text-sm text-slate-600"><?= e($dbUser['email']) ?></p>

                <div class="mt-6 space-y-3 border-t border-slate-200 pt-6 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Rôle</span>
                        <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700"><?= e($roleLabel) ?></span>
                    </div>

                    <?php if ($dbUser['role'] === 'organisateur'): ?>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500">Statut</span>
                            <span class="rounded-full px-3 py-1 font-semibold <?= $dbUser['statut_organisateur'] === 'valide' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
                                <?= e($dbUser['statut_organisateur'] === 'valide' ? 'Validé' : 'En attente') ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Inscrit depuis</span>
                        <span class="font-semibold text-slate-700"><?= e((new DateTime($dbUser['created_at']))->format('d/m/Y')) ?></span>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    <?php if ($dbUser['role'] === 'participant'): ?>
                        <a href="<?= e(url('pages/mes-billets.php')) ?>" class="block rounded-lg bg-blue-600 px-4 py-3 text-center font-semibold text-white hover:bg-blue-700">
                            Voir mes billets
                        </a>
                    <?php endif; ?>

                    <?php if (organizer_is_approved($dbUser)): ?>
                        <a href="<?= e(url('pages/dashboard-organisateur.php')) ?>" class="block rounded-lg bg-slate-900 px-4 py-3 text-center font-semibold text-white hover:bg-slate-700">
                            Dashboard organisateur
                        </a>
                    <?php endif; ?>

                    <?php if ($dbUser['role'] === 'administrateur'): ?>
                        <a href="<?= e(url('pages/admin.php')) ?>" class="block rounded-lg bg-slate-900 px-4 py-3 text-center font-semibold text-white hover:bg-slate-700">
                            Administration
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Billets à venir</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) ((int) ($ticketStats['billets_a_venir'] ?? 0))) ?></p>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Billets passés</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) ((int) ($ticketStats['billets_passes'] ?? 0))) ?></p>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Événements créés</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) $createdEvents) ?></p>
                </div>
            </div>
        </aside>

        <div class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-2xl font-bold text-slate-900">Modifier mes informations</h2>
            <p class="mt-2 text-sm text-slate-600">Le mot de passe n'est modifié que si vous renseignez les champs dédiés.</p>

            <?php if ($error !== ''): ?>
                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="mt-6 space-y-6">
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="prenom" class="mb-2 block text-sm font-semibold text-slate-700">Prénom</label>
                        <input type="text" id="prenom" name="prenom" value="<?= e(old('prenom', $dbUser['prenom'])) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                    </div>
                    <div>
                        <label for="nom" class="mb-2 block text-sm font-semibold text-slate-700">Nom</label>
                        <input type="text" id="nom" name="nom" value="<?= e(old('nom', $dbUser['nom'])) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                    </div>
                </div>

                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">E-mail</label>
                    <input type="email" id="email" name="email" value="<?= e(old('email', $dbUser['email'])) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                </div>

                <div class="rounded-2xl bg-slate-50 p-5">
                    <h3 class="text-lg font-bold text-slate-900">Changer le mot de passe</h3>
                    <div class="mt-4 grid gap-5 md:grid-cols-3">
                        <div>
                            <label for="current_password" class="mb-2 block text-sm font-semibold text-slate-700">Mot de passe actuel</label>
                            <input type="password" id="current_password" name="current_password" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label for="new_password" class="mb-2 block text-sm font-semibold text-slate-700">Nouveau mot de passe</label>
                            <input type="password" id="new_password" name="new_password" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label for="confirm_password" class="mb-2 block text-sm font-semibold text-slate-700">Confirmation</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                    </div>
                </div>

                <button type="submit" class="rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700">
                    Enregistrer les modifications
                </button>
            </form>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
