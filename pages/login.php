<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect_to('pages/mon-profil.php');
}

$page_title = 'Connexion';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Merci de renseigner votre e-mail et votre mot de passe.';
    } else {
        $user = fetch_user_by_email($pdo, $email);

        if ($user === null || !password_verify($password, $user['password'])) {
            $error = 'Identifiants incorrects.';
        } else {
            login_user($user);
            set_flash('success', 'Connexion réussie.');

            if ($user['role'] === 'administrateur') {
                redirect_to('pages/admin.php');
            }

            if ($user['role'] === 'organisateur' && organizer_is_approved($user)) {
                redirect_to('pages/dashboard-organisateur.php');
            }

            redirect_to('pages/mon-profil.php');
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="mx-auto max-w-md px-4 py-16 md:px-0">
    <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
        <h1 class="text-3xl font-bold text-slate-900">Connexion</h1>
        <p class="mt-2 text-sm text-slate-600">Accédez à votre espace OmnesEvent.</p>

        <?php if ($error !== ''): ?>
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="mt-6 space-y-5">
            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">E-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e(old('email')) ?>"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2"
                    required
                >
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Mot de passe</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2"
                    required
                >
            </div>

            <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-3 font-semibold text-white hover:bg-blue-700">
                Se connecter
            </button>
        </form>

        <div class="mt-6 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">
            <p class="font-semibold text-slate-800">Comptes de test</p>
            <p class="mt-2">participant@omnes.fr / password</p>
            <p>organisateur@omnes.fr / password</p>
            <p>admin@omnes.fr / password</p>
        </div>

        <p class="mt-6 text-center text-sm text-slate-600">
            Pas encore de compte ?
            <a href="<?= e(url('pages/register.php')) ?>" class="font-semibold text-blue-600 hover:text-blue-700">Inscrivez-vous</a>
        </p>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
