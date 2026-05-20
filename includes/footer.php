    </main>

    <footer class="mt-16 bg-slate-900 text-white">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 md:grid-cols-3 md:px-8">
            <div>
                <h3 class="mb-3 text-lg font-bold">OmnesEvent</h3>
                <p class="text-sm text-slate-300">
                    La plateforme simple d'Omnes Education pour découvrir, réserver et gérer des événements étudiants.
                </p>
            </div>

            <div>
                <h4 class="mb-3 text-lg font-bold">Catégories</h4>
                <ul class="space-y-2 text-sm text-slate-300">
                    <li><a href="<?= e(url('pages/catalogue.php?categorie=soiree')) ?>" class="hover:text-white">Soirée</a></li>
                    <li><a href="<?= e(url('pages/catalogue.php?categorie=sport')) ?>" class="hover:text-white">Sport</a></li>
                    <li><a href="<?= e(url('pages/catalogue.php?categorie=culture')) ?>" class="hover:text-white">Culture</a></li>
                </ul>
            </div>

            <div>
                <h4 class="mb-3 text-lg font-bold">Accès rapide</h4>
                <ul class="space-y-2 text-sm text-slate-300">
                    <li><a href="<?= e(url('pages/catalogue.php')) ?>" class="hover:text-white">Catalogue</a></li>
                    <li><a href="<?= e(url('pages/calendrier.php')) ?>" class="hover:text-white">Calendrier</a></li>
                    <li><a href="<?= e(url('pages/login.php')) ?>" class="hover:text-white">Connexion</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-slate-800 px-4 py-4 text-center text-sm text-slate-400">
            &copy; <?= e(date('Y')) ?> OmnesEvent. Projet étudiant PHP/MySQL.
        </div>
    </footer>

    <script src="<?= e(url('assets/script.js')) ?>" defer></script>
</body>
</html>
