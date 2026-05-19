<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect_to('pages/mon-profil.php');
}

$page_title = 'Inscription';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = trim((string) ($_POST['prenom'] ?? ''));
    $nom = trim((string) ($_POST['nom'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $role = (string) ($_POST['role'] ?? 'participant');

    if ($prenom === '' || $nom === '' || $email === '' || $password === '') {
        $error = 'Tous les champs obligatoires doivent être renseignés.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse e-mail invalide.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (!in_array($role, ['participant', 'organisateur'], true)) {
        $error = 'Rôle invalide.';
    } elseif (fetch_user_by_email($pdo, $email) !== null) {
        $error = 'Un compte existe déjà avec cet e-mail.';
    } else {
        $status = $role === 'organisateur' ? 'en_attente' : 'valide';

        $stmt = $pdo->prepare(
            'INSERT INTO users (prenom, nom, email, password, role, statut_organisateur)
             VALUES (:prenom, :nom, :email, :password, :role, :statut_organisateur)'
        );

        $stmt->execute([
            'prenom' => $prenom,
            'nom' => $nom,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'statut_organisateur' => $status,
        ]);

        $user = fetch_user_by_id($pdo, (int) $pdo->lastInsertId());

        if ($user !== null) {
            login_user($user);
        }

        if ($role === 'organisateur') {
            set_flash('info', 'Compte créé. Un administrateur doit valider votre rôle organisateur.');
        } else {
            set_flash('success', 'Compte créé avec succès.');
        }

        redirect_to('pages/mon-profil.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="mx-auto max-w-2xl px-4 py-16 md:px-0">
    <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
        <h1 class="text-3xl font-bold text-slate-900">Créer un compte</h1>
        <p class="mt-2 text-sm text-slate-600">Choisissez votre rôle pour commencer à utiliser OmnesEvent.</p>

        <?php if ($error !== ''): ?>
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="mt-6 space-y-5">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="prenom" class="mb-2 block text-sm font-semibold text-slate-700">Prénom</label>
                    <input type="text" id="prenom" name="prenom" value="<?= e(old('prenom')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                </div>
                <div>
                    <label for="nom" class="mb-2 block text-sm font-semibold text-slate-700">Nom</label>
                    <input type="text" id="nom" name="nom" value="<?= e(old('nom')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                </div>
            </div>

            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">E-mail</label>
                <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
            </div>

            <div>
                <label for="role" class="mb-2 block text-sm font-semibold text-slate-700">Rôle</label>
                <select id="role" name="role" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="participant" <?= old('role', 'participant') === 'participant' ? 'selected' : '' ?>>Participant</option>
                    <option value="organisateur" <?= old('role') === 'organisateur' ? 'selected' : '' ?>>Organisateur</option>
                </select>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Mot de passe</label>
                    <input type="password" id="password" name="password" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                </div>
                <div>
                    <label for="confirm_password" class="mb-2 block text-sm font-semibold text-slate-700">Confirmation</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                </div>
            </div>

            <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-3 font-semibold text-white hover:bg-blue-700">
                Créer mon compte
            </button>
        </form>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
