<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'OmnesEvent'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body class="bg-gray-50">
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="flex items-center justify-between p-4 md:px-8">
            <a href="/index.php" class="flex items-center">
                <img src="/images/logoOE.webp" alt="OmnesEvent Logo" class="h-32 w-auto">
            </a>

            <button id="menu-toggle" class="md:hidden text-3xl text-gray-700">
                ☰
            </button>

            <nav id="navbar" class="hidden md:flex gap-8 items-center">
                <a href="/index.php" class="text-gray-800 hover:text-blue-600 transition">Accueil</a>
                <a href="/pages/catalogue.php" class="text-gray-800 hover:text-blue-600 transition">Catalogue</a>
                <a href="/pages/creer-evenement.php" class="text-gray-800 hover:text-blue-600 transition">Créer</a>
                <a href="/pages/mon-profil.php" class="text-gray-800 hover:text-blue-600 transition">Profil</a>
                <a href="/pages/mes-billets.php" class="text-gray-800 hover:text-blue-600 transition">Mes billets</a>
            </nav>
        </div>

        <nav id="mobile-menu" class="hidden md:hidden bg-white border-t p-4 flex flex-col gap-3">
            <a href="/index.php" class="py-2 text-gray-800 hover:text-blue-600">Accueil</a>
            <a href="/pages/catalogue.php" class="py-2 text-gray-800 hover:text-blue-600">Catalogue</a>
            <a href="/pages/creer-evenement.php" class="py-2 text-gray-800 hover:text-blue-600">Créer un événement</a>
            <a href="/pages/mon-profil.php" class="py-2 text-gray-800 hover:text-blue-600">Mon profil</a>
            <a href="/pages/mes-billets.php" class="py-2 text-gray-800 hover:text-blue-600">Mes billets</a>
        </nav>
    </header>

    <main class="min-h-screen">
