<?php

/*
|--------------------------------------------------------------------------
| ClubConnect
| admin/index.php
|--------------------------------------------------------------------------
*/

require_once "auth.php";

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ClubConnect | Administration</title>

    <link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime(
        $_SERVER['DOCUMENT_ROOT'] . '../assets/css/style.css'
    ) ?>">

</head>


<body class="min-h-screen bg-slate-50">


    <!-- =========================================================
     HEADER
========================================================= -->

    <?php
    require_once "../includes/header.php";
    ?>

    <?php
    require_once "../includes/PopUpMsg.php";
    ?>



    <!-- =========================================================
     MOBILE NAVIGATION
========================================================= -->

    <div class="md:hidden
           bg-white
           border-b
           border-slate-200">

        <div class="max-w-7xl
               mx-auto
               px-4
               py-3
               flex
               gap-2
               overflow-x-auto">

            <a href="index.php" class="shrink-0
                   px-3 py-2
                   rounded-lg
                   bg-cyan-50
                   text-cyan-700
                   text-sm
                   font-semibold">

                Administration

            </a>


            <a href="dashboard.php" class="shrink-0
                   px-3 py-2
                   rounded-lg
                   text-slate-600
                   text-sm
                   font-medium
                   hover:bg-slate-50">

                Gestion

            </a>


            <a href="../clubs.php" class="shrink-0
                   px-3 py-2
                   rounded-lg
                   text-slate-600
                   text-sm
                   font-medium
                   hover:bg-slate-50">

                Clubs

            </a>

        </div>

    </div>



    <!-- =========================================================
     MAIN
========================================================= -->

    <main class="max-w-7xl
           mx-auto
           px-4 sm:px-6 lg:px-8
           py-10">


        <!-- Welcome -->

        <section class="mb-8">

            <span class="inline-flex
                   px-3 py-1
                   rounded-full
                   bg-cyan-50
                   text-cyan-700
                   text-xs
                   font-bold">

                ESPACE ADMINISTRATEUR

            </span>


            <h1 class="mt-4
                   text-3xl
                   sm:text-4xl
                   font-bold
                   text-slate-900">

                Tableau de bord

            </h1>


            <p class="mt-2
                   max-w-2xl
                   text-slate-500">

                Bienvenue,
                <?= htmlspecialchars(
                    $currentUserName,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>.
                Gérez les clubs et les utilisateurs de ClubConnect.

            </p>

        </section>



        <!-- =========================================================
         ACTIONS
    ========================================================== -->

        <section>

            <div class="grid
                   grid-cols-1
                   md:grid-cols-2
                   lg:grid-cols-3
                   gap-5">


                <!-- Gestion clubs -->

                <a href="dashboard.php?section=clubs" class="group
                       bg-white
                       border
                       border-slate-200
                       rounded-2xl
                       p-6
                       shadow-sm
                       hover:border-cyan-300
                       hover:shadow-md
                       transition">

                    <div class="w-12 h-12
                           rounded-xl
                           bg-cyan-50
                           text-cyan-700
                           flex
                           items-center
                           justify-center">

                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14-4H5m14 8H5m14 4H5" />

                        </svg>

                    </div>


                    <div class="mt-5
                           flex
                           items-center
                           justify-between
                           gap-4">

                        <div>

                            <h2 class="text-lg
                                   font-bold
                                   text-slate-900">

                                Gestion des clubs

                            </h2>


                            <p class="mt-2
                                   text-sm
                                   leading-6
                                   text-slate-500">

                                Consulter, valider, refuser ou
                                désactiver les clubs.

                            </p>

                        </div>


                        <span class="text-xl
                               text-cyan-700
                               group-hover:translate-x-1
                               transition">

                            →

                        </span>

                    </div>

                </a>



                <!-- Demandes -->

                <a href="dashboard.php?section=pending" class="group
                       bg-white
                       border
                       border-slate-200
                       rounded-2xl
                       p-6
                       shadow-sm
                       hover:border-amber-300
                       hover:shadow-md
                       transition">

                    <div class="w-12 h-12
                           rounded-xl
                           bg-amber-50
                           text-amber-700
                           flex
                           items-center
                           justify-center">

                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />

                        </svg>

                    </div>


                    <div class="mt-5
                           flex
                           items-center
                           justify-between
                           gap-4">

                        <div>

                            <h2 class="text-lg
                                   font-bold
                                   text-slate-900">

                                Demandes

                            </h2>


                            <p class="mt-2
                                   text-sm
                                   leading-6
                                   text-slate-500">

                                Examiner les demandes de création
                                de clubs.

                            </p>

                        </div>


                        <span class="text-xl
                               text-amber-600
                               group-hover:translate-x-1
                               transition">

                            →

                        </span>

                    </div>

                </a>



                <!-- Utilisateurs -->

                <a href="dashboard.php?section=users" class="group
                       bg-white
                       border
                       border-slate-200
                       rounded-2xl
                       p-6
                       shadow-sm
                       hover:border-blue-300
                       hover:shadow-md
                       transition">

                    <div class="w-12 h-12
                           rounded-xl
                           bg-blue-50
                           text-blue-700
                           flex
                           items-center
                           justify-center">

                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-10a4 4 0 110 8 4 4 0 000-8z" />

                        </svg>

                    </div>


                    <div class="mt-5
                           flex
                           items-center
                           justify-between
                           gap-4">

                        <div>

                            <h2 class="text-lg
                                   font-bold
                                   text-slate-900">

                                Utilisateurs

                            </h2>


                            <p class="mt-2
                                   text-sm
                                   leading-6
                                   text-slate-500">

                                Consulter les comptes et leurs rôles.

                            </p>

                        </div>


                        <span class="text-xl
                               text-blue-700
                               group-hover:translate-x-1
                               transition">

                            →

                        </span>

                    </div>

                </a>



                <!-- Site -->

                <a href="../index.php" class="group
                       bg-white
                       border
                       border-slate-200
                       rounded-2xl
                       p-6
                       shadow-sm
                       hover:border-slate-400
                       hover:shadow-md
                       transition">

                    <div class="w-12 h-12
                           rounded-xl
                           bg-slate-100
                           text-slate-700
                           flex
                           items-center
                           justify-center">

                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l9-9 9 9M5 10v10h14V10" />

                        </svg>

                    </div>


                    <div class="mt-5
                           flex
                           items-center
                           justify-between
                           gap-4">

                        <div>

                            <h2 class="text-lg
                                   font-bold
                                   text-slate-900">

                                Voir le site

                            </h2>


                            <p class="mt-2
                                   text-sm
                                   leading-6
                                   text-slate-500">

                                Retourner sur le site public
                                de ClubConnect.

                            </p>

                        </div>


                        <span class="text-xl
                               text-slate-700
                               group-hover:translate-x-1
                               transition">

                            →

                        </span>

                    </div>

                </a>



                <!-- Profil -->

                <a href="../profile.php" class="group
                       bg-white
                       border
                       border-slate-200
                       rounded-2xl
                       p-6
                       shadow-sm
                       hover:border-purple-300
                       hover:shadow-md
                       transition">

                    <div class="w-12 h-12
                           rounded-xl
                           bg-purple-50
                           text-purple-700
                           flex
                           items-center
                           justify-center">

                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />

                        </svg>

                    </div>


                    <div class="mt-5
                           flex
                           items-center
                           justify-between
                           gap-4">

                        <div>

                            <h2 class="text-lg
                                   font-bold
                                   text-slate-900">

                                Mon profil

                            </h2>


                            <p class="mt-2
                                   text-sm
                                   leading-6
                                   text-slate-500">

                                Consulter vos informations
                                administrateur.

                            </p>

                        </div>


                        <span class="text-xl
                               text-purple-700
                               group-hover:translate-x-1
                               transition">

                            →

                        </span>

                    </div>

                </a>



                <!-- contact-messages -->

                <a href="contact-messages.php" class="block bg-white
           border border-slate-200
           rounded-2xl
           p-6
           hover:shadow-md
           transition">

                    <div class="w-11 h-11
               rounded-xl
               bg-cyan-50
               text-cyan-700
               flex
               items-center
               justify-center
               font-bold">
                        M
                    </div>

                    <h2 class="mt-4 text-lg font-bold">
                        Messages reçus
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Lire les messages envoyés depuis la page Contact
                        et répondre par email.
                    </p>

                    <span class="inline-block
               mt-4
               text-sm
               font-semibold
               text-cyan-700">
                        Voir les messages →
                    </span>

                </a>


            </div>

        </section>



        <!-- =========================================================
         INFORMATIONS COMPTE
    ========================================================== -->

        <section class="mt-8
               bg-white
               border
               border-slate-200
               rounded-2xl
               p-6">

            <h2 class="text-lg
                   font-bold
                   text-slate-900">

                Compte administrateur

            </h2>


            <div class="mt-5
                   grid
                   grid-cols-1
                   sm:grid-cols-3
                   gap-5">


                <div>

                    <p class="text-xs
                           font-medium
                           uppercase
                           tracking-wide
                           text-slate-400">

                        Nom

                    </p>


                    <p class="mt-1
                           text-sm
                           font-semibold
                           text-slate-800">

                        <?= htmlspecialchars(
                            $currentUserName,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </p>

                </div>


                <div>

                    <p class="text-xs
                           font-medium
                           uppercase
                           tracking-wide
                           text-slate-400">

                        Email

                    </p>


                    <p class="mt-1
                           text-sm
                           font-semibold
                           text-slate-800
                           break-all">

                        <?= htmlspecialchars(
                            $currentUserEmail,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </p>

                </div>


                <div>

                    <p class="text-xs
                           font-medium
                           uppercase
                           tracking-wide
                           text-slate-400">

                        Rôle

                    </p>


                    <p class="mt-1
                           text-sm
                           font-semibold
                           text-cyan-700">

                        <?= htmlspecialchars(
                            $currentUserRole,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </p>

                </div>

            </div>

        </section>


    </main>


</body>

</html>