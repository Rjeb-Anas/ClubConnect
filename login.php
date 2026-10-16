<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClubConnect | Connexion</title>
</head>

<body>

    <?php

    $pageTitle = "Connexion - ClubConnect";

    require_once "includes/header.php";
    require_once "includes/PopUpMsg.php";


    // Email récupéré depuis la redirection
    $email = '';

    if (isset($_GET['email'])) {
        $email = filter_var(
            $_GET['email'],
            FILTER_SANITIZE_EMAIL
        );
    }


    // Afficher le bloc de vérification
    $showVerification =
        isset($_GET['verify']) &&
        $_GET['verify'] === '1';

    ?>

    <main class="min-h-[calc(100vh-64px)] bg-slate-50">

        <div class="min-h-[calc(100vh-64px)]
                flex items-center justify-center
                px-4 py-12">

            <div class="w-full max-w-md">

                <!-- Logo -->
                <div class="text-center mb-8">

                    <div class="mx-auto w-14 h-14
                           rounded-2xl
                           bg-cyan-700
                           text-white
                           flex items-center
                           justify-center
                           text-2xl font-bold
                           shadow-sm">

                        C

                    </div>

                    <h1 class="mt-5 text-3xl
                           font-bold tracking-tight
                           text-slate-900">

                        Connexion

                    </h1>

                    <p class="mt-2 text-slate-500">

                        Connectez-vous à votre compte ClubConnect.

                    </p>

                </div>


                <!-- =====================================================
                 EMAIL NON VÉRIFIÉ
            ====================================================== -->

                <?php if ($showVerification): ?>

                    <div class="mb-5
                           rounded-2xl
                           border border-amber-200
                           bg-amber-50
                           p-5">

                        <div class="flex gap-3">

                            <!-- Icône -->
                            <div class="shrink-0
                                   w-10 h-10
                                   rounded-full
                                   bg-amber-100
                                   text-amber-700
                                   flex items-center
                                   justify-center
                                   font-bold">

                                !

                            </div>


                            <div class="flex-1 min-w-0">

                                <h2 class="font-semibold
                                       text-amber-900">

                                    Email non vérifié

                                </h2>

                                <p class="mt-1 text-sm
                                       leading-5
                                       text-amber-700">

                                    Vous devez vérifier votre adresse
                                    académique avant de vous connecter.

                                </p>


                                <?php if ($email): ?>

                                    <p class="mt-2 text-xs
                                           text-amber-700
                                           break-all">

                                        <?= htmlspecialchars(
                                            $email,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </p>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- Bouton vérifier -->
                        <form action="traitement.php" method="POST" class="mt-4">

                            <input type="hidden" name="email" value="<?= htmlspecialchars(
                                $email,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">


                            <button type="submit" name="resend_verification" class="w-full
                                   py-3
                                   rounded-xl
                                   bg-amber-600
                                   text-white
                                   text-sm
                                   font-semibold
                                   hover:bg-amber-700
                                   transition">

                                Vérifier mon email

                            </button>

                        </form>

                    </div>

                <?php endif; ?>


                <!-- =====================================================
                 FORMULAIRE LOGIN
            ====================================================== -->

                <div class="bg-white
                       border border-slate-200
                       rounded-3xl
                       shadow-sm
                       p-6 sm:p-8">

                    <form action="traitement.php" method="POST" class="space-y-5">


                        <!-- Email -->
                        <div>

                            <label for="email" class="block mb-2
                                   text-sm
                                   font-medium
                                   text-slate-700">

                                Email académique

                            </label>


                            <input type="email" id="email" name="email" required autocomplete="email"
                                placeholder="prenom.nom@usmba.ac.ma" pattern="^[A-Za-z0-9._%+-]+@usmba\.ac\.ma$" value="<?= htmlspecialchars(
                                    $email,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>" class="w-full
                                   px-4 py-3
                                   rounded-xl
                                   border border-slate-300
                                   bg-white
                                   outline-none
                                   transition
                                   focus:border-cyan-600
                                   focus:ring-2
                                   focus:ring-cyan-100">

                        </div>


                        <!-- Mot de passe -->
                        <div>

                            <div class="flex
                                   items-center
                                   justify-between
                                   mb-2
                                   gap-3">

                                <label for="password" class="text-sm
                                       font-medium
                                       text-slate-700">

                                    Mot de passe

                                </label>


                                <a href="forgot-password.php" class="text-sm
                                       font-medium
                                       text-cyan-700
                                       hover:text-cyan-800
                                       transition">

                                    Mot de passe oublié ?

                                </a>

                            </div>


                            <div class="relative">

                                <input type="password" id="password" name="password" required
                                    autocomplete="current-password" placeholder="••••••••" class="w-full
                                       px-4 py-3
                                       pr-12
                                       rounded-xl
                                       border border-slate-300
                                       outline-none
                                       transition
                                       focus:border-cyan-600
                                       focus:ring-2
                                       focus:ring-cyan-100">


                                <button type="button" id="togglePassword" class="absolute
                                       right-3
                                       top-1/2
                                       -translate-y-1/2
                                       p-2
                                       rounded-lg
                                       text-slate-400
                                       hover:text-slate-700
                                       hover:bg-slate-100
                                       transition">

                                    <svg id="eyeOpen" class="w-5 h-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">

                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943
                                           7.523 5 12 5
                                           c4.478 0 8.268 2.943
                                           9.542 7
                                           -1.274 4.057
                                           -5.064 7
                                           -9.542 7
                                           -4.477 0
                                           -8.268-2.943
                                           -9.542-7z" />

                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0
                                           11-6 0 3 3 0
                                           016 0z" />

                                    </svg>

                                    <svg id="eyeClosed" class="hidden w-5 h-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">

                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 3l18 18" />

                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.584 10.587
                                           A2 2 0
                                           0113.414 13.417" />

                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.88 5.09
                                           A9.8 9.8 0
                                           0112 5
                                           c4.478 0
                                           8.268 2.943
                                           9.542 7
                                           a10.97 10.97
                                           0 01-4.04 5.17" />

                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.61 6.61
                                           A10.97 10.97
                                           002.458 12
                                           C3.732 16.057
                                           7.523 19
                                           12 19
                                           a9.8 9.8 0
                                           004.07-.83" />

                                    </svg>

                                </button>

                            </div>

                        </div>


                        <!-- Se souvenir -->
                        <div class="flex items-center gap-2">

                            <input type="checkbox" id="remember" name="remember" value="1"
                                class="w-4 h-4 accent-cyan-700">

                            <label for="remember" class="text-sm
                                   text-slate-600
                                   cursor-pointer">

                                Se souvenir de moi

                            </label>

                        </div>


                        <!-- Connexion -->
                        <button type="submit" name="login" class="w-full
                               py-3.5
                               rounded-xl
                               bg-cyan-700
                               text-white
                               font-semibold
                               hover:bg-cyan-800
                               active:scale-[0.99]
                               transition">

                            Se connecter

                        </button>

                    </form>


                    <!-- Séparation -->
                    <div class="flex items-center
                           gap-4 my-7">

                        <div class="flex-1 h-px
                               bg-slate-200">
                        </div>

                        <span class="text-xs
                               text-slate-400">
                            OU
                        </span>

                        <div class="flex-1 h-px
                               bg-slate-200">
                        </div>

                    </div>


                    <!-- Inscription -->
                    <a href="register.php" class="flex
                           items-center
                           justify-center
                           w-full
                           py-3
                           rounded-xl
                           border border-slate-300
                           text-slate-700
                           font-medium
                           hover:bg-slate-50
                           transition">

                        Créer un compte

                    </a>

                </div>


                <p class="mt-6
                       text-center
                       text-xs
                       leading-5
                       text-slate-400">

                    Utilisez votre adresse académique USMBA.

                </p>

            </div>

        </div>

    </main>


    <script>

        const togglePassword =
            document.getElementById("togglePassword");

        const passwordInput =
            document.getElementById("password");

        const eyeOpen =
            document.getElementById("eyeOpen");

        const eyeClosed =
            document.getElementById("eyeClosed");


        togglePassword.addEventListener("click", function () {

            if (passwordInput.type === "password") {

                passwordInput.type = "text";

                eyeOpen.classList.add("hidden");
                eyeClosed.classList.remove("hidden");

            } else {

                passwordInput.type = "password";

                eyeOpen.classList.remove("hidden");
                eyeClosed.classList.add("hidden");

            }

        });

    </script>


    <?php
    require_once "includes/footer.php";
    ?>


</body>

</html>