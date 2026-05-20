<?php
declare(strict_types=1);

$pageTitle = isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME;
$user = current_user();
$flashMessages = pull_flash_messages();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= e(url('assets/styles.css')) ?>">
</head>
<body class="bg-slate-50 text-slate-800">
    <header class="sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 md:px-8">
            <a href="<?= e(url('index.php')) ?>" class="flex items-center gap-3">
                <img src="<?= e(url('images/logoOE.webp')) ?>" alt="Logo OmnesEvent" class="h-14 w-auto md:h-20">
                <span class="text-xl font-bold text-slate-800">OmnesEvent</span>
            </a>

            <button
                type="button"
                id="menu-toggle"
                class="inline-flex rounded-lg border border-slate-200 px-3 py-2 text-slate-700 md:hidden"
                aria-expanded="false"
                aria-controls="mobile-menu"
            >
                Menu
            </button>

            <nav class="hidden items-center gap-5 md:flex">
                <a href="<?= e(url('index.php')) ?>" class="nav-link">Accueil</a>
                <a href="<?= e(url('pages/catalogue.php')) ?>" class="nav-link">Catalogue</a>
                <a href="<?= e(url('pages/calendrier.php')) ?>" class="nav-link">Calendrier</a>

                <?php if ($user !== null && organizer_is_approved($user)): ?>
                    <a href="<?= e(url('pages/dashboard-organisateur.php')) ?>" class="nav-link">Dashboard organisateur</a>
                    <a href="<?= e(url('pages/creer-evenement.php')) ?>" class="nav-link">Créer</a>
                <?php endif; ?>

                <?php if ($user !== null && $user['role'] === 'participant'): ?>
                    <a href="<?= e(url('pages/mes-billets.php')) ?>" class="nav-link">Mes billets</a>
                <?php endif; ?>

                <?php if ($user !== null && $user['role'] === 'administrateur'): ?>
                    <a href="<?= e(url('pages/admin.php')) ?>" class="nav-link">Admin</a>
                <?php endif; ?>

                <?php if ($user !== null): ?>
                    <a href="<?= e(url('pages/mon-profil.php')) ?>" class="nav-link">Profil</a>
                    <a href="<?= e(url('pages/logout.php')) ?>" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Déconnexion</a>
                <?php else: ?>
                    <a href="<?= e(url('pages/login.php')) ?>" class="nav-link">Connexion</a>
                    <a href="<?= e(url('pages/register.php')) ?>" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Inscription</a>
                <?php endif; ?>
            </nav>
        </div>

        <nav id="mobile-menu" class="hidden border-t border-slate-200 bg-white px-4 py-4 md:hidden">
            <div class="flex flex-col gap-3">
                <a href="<?= e(url('index.php')) ?>" class="mobile-link">Accueil</a>
                <a href="<?= e(url('pages/catalogue.php')) ?>" class="mobile-link">Catalogue</a>
                <a href="<?= e(url('pages/calendrier.php')) ?>" class="mobile-link">Calendrier</a>

                <?php if ($user !== null && organizer_is_approved($user)): ?>
                    <a href="<?= e(url('pages/dashboard-organisateur.php')) ?>" class="mobile-link">Dashboard organisateur</a>
                    <a href="<?= e(url('pages/creer-evenement.php')) ?>" class="mobile-link">Créer un événement</a>
                <?php endif; ?>

                <?php if ($user !== null && $user['role'] === 'participant'): ?>
                    <a href="<?= e(url('pages/mes-billets.php')) ?>" class="mobile-link">Mes billets</a>
                <?php endif; ?>

                <?php if ($user !== null && $user['role'] === 'administrateur'): ?>
                    <a href="<?= e(url('pages/admin.php')) ?>" class="mobile-link">Administration</a>
                <?php endif; ?>

                <?php if ($user !== null): ?>
                    <a href="<?= e(url('pages/mon-profil.php')) ?>" class="mobile-link">Mon profil</a>
                    <a href="<?= e(url('pages/logout.php')) ?>" class="mobile-link">Déconnexion</a>
                <?php else: ?>
                    <a href="<?= e(url('pages/login.php')) ?>" class="mobile-link">Connexion</a>
                    <a href="<?= e(url('pages/register.php')) ?>" class="mobile-link">Inscription</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main class="min-h-screen">
        <?php if ($flashMessages !== []): ?>
            <section class="mx-auto max-w-6xl px-4 pt-6 md:px-8">
                <div class="space-y-3">
                    <?php foreach ($flashMessages as $message): ?>
                        <?php
                        $type = $message['type'] ?? 'info';
                        $classes = match ($type) {
                            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                            'error' => 'border-red-200 bg-red-50 text-red-700',
                            default => 'border-blue-200 bg-blue-50 text-blue-700',
                        };
                        ?>
                        <div class="flash-message rounded-xl border px-4 py-3 text-sm <?= e($classes) ?>">
                            <?= e($message['message'] ?? '') ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
