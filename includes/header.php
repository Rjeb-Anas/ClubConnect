<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);

$isLoggedIn = isset($_SESSION['id']);
$userName   = $_SESSION['name'] ?? null;
$userRole   = $_SESSION['role'] ?? null;
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


    <link
    rel="stylesheet"
    href="/assets/css/style.css?v=<?= filemtime(
        $_SERVER['DOCUMENT_ROOT'] . '/assets/css/style.css'
    ) ?>"
>
</head>

<body class="bg-slate-50 text-slate-800">

<header class="sticky top-0 z-50 bg-white border-b border-slate-200 shadow-sm">

    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="h-16 flex items-center justify-between">

            <!-- Logo -->
            <a href="/index.php"
               class="flex items-center gap-2">

                <div class="w-10 h-10 rounded-xl bg-cyan-700
                            flex items-center justify-center
                            text-white font-bold text-lg">
                    C
                </div>

                <div class="leading-tight">
                    <span class="block text-lg font-bold text-slate-900">
                        ClubConnect
                    </span>

                    <span class="block text-xs text-slate-500">
                        FST Fès
                    </span>
                </div>

            </a>


            <!-- Navigation desktop -->
            <div class="hidden md:flex items-center gap-7">

                <a href="/index.php"
                   class="<?= $currentPage === 'index.php'
                        ? 'text-cyan-700 font-semibold'
                        : 'text-slate-600 hover:text-cyan-700' ?>
                        transition">
                    Accueil
                </a>

                <a href="/clubs.php"
                   class="<?= $currentPage === 'clubs.php'
                        ? 'text-cyan-700 font-semibold'
                        : 'text-slate-600 hover:text-cyan-700' ?>
                        transition">
                    Clubs
                </a>

                <a href="/about.php"
                   class="<?= $currentPage === 'about.php'
                        ? 'text-cyan-700 font-semibold'
                        : 'text-slate-600 hover:text-cyan-700' ?>
                        transition">
                    À propos
                </a>

                <a href="/contact.php"
                   class="<?= $currentPage === 'contact.php'
                        ? 'text-cyan-700 font-semibold'
                        : 'text-slate-600 hover:text-cyan-700' ?>
                        transition">
                    Contact
                </a>

            </div>


            <!-- Partie utilisateur -->
            <div class="hidden md:flex items-center gap-3">

                <?php if ($isLoggedIn): ?>

                    <a href="/profile.php"
                       class="flex items-center gap-2 px-3 py-2
                              rounded-lg hover:bg-slate-100 transition">

                        <div class="w-8 h-8 rounded-full bg-cyan-700
                                    text-white flex items-center
                                    justify-center text-sm font-semibold">
                            <?= strtoupper(substr($userName, 0, 1)) ?>
                        </div>

                        <span class="text-sm font-medium">
                            <?= htmlspecialchars($userName) ?>
                        </span>

                    </a>

                <?php else: ?>

                    <a href="/login.php"
                       class="px-4 py-2 text-sm font-medium
                              text-slate-700 hover:text-cyan-700">
                        Connexion
                    </a>

                    <a href="/register.php"
                       class="px-4 py-2 rounded-lg
                              bg-cyan-700 text-white text-sm
                              font-medium hover:bg-cyan-800
                              transition">
                        Inscription
                    </a>

                <?php endif; ?>

            </div>


            <!-- Bouton mobile -->
            <button
                id="menuButton"
                type="button"
                class="md:hidden p-2 rounded-lg
                       hover:bg-slate-100"
                aria-label="Menu">

                <svg class="w-6 h-6"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M4 6h16M4 12h16M4 18h16"/>

                </svg>

            </button>

        </div>


        <!-- Menu mobile -->
        <div id="mobileMenu"
             class="hidden md:hidden pb-4">

            <div class="flex flex-col gap-1">

                <a href="/index.php"
                   class="px-4 py-3 rounded-lg hover:bg-slate-100">
                    Accueil
                </a>

                <a href="/clubs.php"
                   class="px-4 py-3 rounded-lg hover:bg-slate-100">
                    Clubs
                </a>

                <a href="/about.php"
                   class="px-4 py-3 rounded-lg hover:bg-slate-100">
                    À propos
                </a>

                <a href="/contact.php"
                   class="px-4 py-3 rounded-lg hover:bg-slate-100">
                    Contact
                </a>

                <div class="border-t border-slate-200 my-2"></div>

                <?php if ($isLoggedIn): ?>

                    <a href="/profile.php"
                       class="px-4 py-3 rounded-lg hover:bg-slate-100">
                        Mon profil
                    </a>

                <?php else: ?>

                    <a href="/login.php"
                       class="px-4 py-3 rounded-lg hover:bg-slate-100">
                        Connexion
                    </a>

                    <a href="/register.php"
                       class="px-4 py-3 rounded-lg
                              bg-cyan-700 text-white text-center">
                        Inscription
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </nav>

</header>


<script>
const menuButton = document.getElementById('menuButton');
const mobileMenu = document.getElementById('mobileMenu');

menuButton.addEventListener('click', () => {
    mobileMenu.classList.toggle('hidden');
});
</script>

