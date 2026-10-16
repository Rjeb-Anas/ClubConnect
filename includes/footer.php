<footer class="bg-slate-900 text-slate-300 mt-16">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <div class="grid grid-cols-1 md:grid-cols-4 gap-10">

            <!-- ClubConnect -->
            <div class="md:col-span-2">

                <div class="flex items-center gap-3 mb-4">

                    <div class="w-10 h-10 rounded-xl bg-cyan-700
                                flex items-center justify-center
                                text-white font-bold text-lg">
                        C
                    </div>

                    <div>
                        <h2 class="text-xl font-bold text-white">
                            ClubConnect
                        </h2>

                        <p class="text-xs text-slate-400">
                            FST Fès
                        </p>
                    </div>

                </div>

                <p class="max-w-md text-sm leading-6 text-slate-400">
                    ClubConnect est une plateforme dédiée à la gestion
                    et à la découverte des clubs de la Faculté des
                    Sciences et Techniques de Fès.
                </p>

            </div>


            <!-- Navigation -->
            <div>

                <h3 class="text-sm font-semibold text-white uppercase
                           tracking-wider mb-4">
                    Navigation
                </h3>

                <ul class="space-y-3 text-sm">

                    <li>
                        <a href="/index.php"
                           class="hover:text-cyan-400 transition">
                            Accueil
                        </a>
                    </li>

                    <li>
                        <a href="/clubs.php"
                           class="hover:text-cyan-400 transition">
                            Clubs
                        </a>
                    </li>

                    <li>
                        <a href="/about.php"
                           class="hover:text-cyan-400 transition">
                            À propos
                        </a>
                    </li>

                    <li>
                        <a href="/contact.php"
                           class="hover:text-cyan-400 transition">
                            Contact
                        </a>
                    </li>

                </ul>

            </div>


            <!-- Compte -->
            <div>

                <h3 class="text-sm font-semibold text-white uppercase
                           tracking-wider mb-4">
                    Compte
                </h3>

                <ul class="space-y-3 text-sm">

                    <li>
                        <a href="/profile.php"
                           class="hover:text-cyan-400 transition">
                            Mon profil
                        </a>
                    </li>

                    <li>
                        <a href="/register.php"
                           class="hover:text-cyan-400 transition">
                            Inscription
                        </a>
                    </li>

                    <li>
                        <a href="/register.php"
                           class="hover:text-cyan-400 transition">
                            Connexion
                        </a>
                    </li>

                </ul>

            </div>

        </div>


        <!-- Séparation -->
        <div class="border-t border-slate-800 mt-10 pt-6
                    flex flex-col md:flex-row
                    items-center justify-between gap-4">

            <p class="text-sm text-slate-500">
                © <?= date('Y') ?> ClubConnect. Tous droits réservés.
            </p>

            <p class="text-sm text-slate-500">
                FST Fès · Université Sidi Mohamed Ben Abdellah
            </p>

        </div>

    </div>

</footer>

</body>
</html>

