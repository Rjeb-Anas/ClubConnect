<?php

/*
|--------------------------------------------------------------------------
| ClubConnect
| manager/index.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../config/database.php";


/*
|--------------------------------------------------------------------------
| Session
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Sécurité
|--------------------------------------------------------------------------
*/

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| ID du manager
|--------------------------------------------------------------------------
*/

$managerId = (int) (
    $_SESSION['id']
    ?? $_SESSION['user_id']
    ?? 0
);


if ($managerId <= 0) {

    header("Location: ../login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Informations utilisateur
|--------------------------------------------------------------------------
*/

$prenom =
    $_SESSION['prenom']
    ?? '';

$nom =
    $_SESSION['nom']
    ?? '';

$fullName =
    trim(
        $prenom . ' ' . $nom
    );


if ($fullName === '') {

    $fullName =
        $_SESSION['name']
        ?? 'Manager';
}


/*
|--------------------------------------------------------------------------
| Récupérer le club du manager
|--------------------------------------------------------------------------
|
| La structure actuelle utilise :
|
| clubs.manager_id
|
*/

$club = null;


$stmt = mysqli_prepare(
    $con,
    "
    SELECT
        id,
        club_name,
        category,
        description,
        logo,
        members_max,
        status,
        manager_id,
        created_by,
        creation_date,
        updated_at
    FROM clubs
    WHERE manager_id = ?
    LIMIT 1
    "
);


if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $managerId
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $club =
        mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| Statistiques
|--------------------------------------------------------------------------
*/

$memberCount = 0;
$pendingRequests = 0;


/*
|--------------------------------------------------------------------------
| Membres
|--------------------------------------------------------------------------
|
| On essaie d'utiliser club_members.
| Si cette table n'existe pas encore, les statistiques
| restent à zéro.
|
*/

if ($club) {

    $clubId =
        (int) $club['id'];


    $stmt = mysqli_prepare(
        $con,
        "
        SELECT COUNT(*) AS total
        FROM club_members
        WHERE club_id = ?
        "
    );


    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $clubId
        );

        if (
            mysqli_stmt_execute($stmt)
        ) {

            $result =
                mysqli_stmt_get_result($stmt);

            $row =
                mysqli_fetch_assoc($result);

            if ($row) {

                $memberCount =
                    (int) $row['total'];
            }
        }

        mysqli_stmt_close($stmt);
    }
}


/*
|--------------------------------------------------------------------------
| Demandes d'adhésion
|--------------------------------------------------------------------------
|
| Compatible avec une table club_requests si elle existe.
|
*/

if ($club) {

    $stmt = mysqli_prepare(
        $con,
        "
        SELECT COUNT(*) AS total
        FROM club_requests
        WHERE club_id = ?
        AND status = 'pending'
        "
    );


    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $clubId
        );

        if (
            mysqli_stmt_execute($stmt)
        ) {

            $result =
                mysqli_stmt_get_result($stmt);

            $row =
                mysqli_fetch_assoc($result);

            if ($row) {

                $pendingRequests =
                    (int) $row['total'];
            }
        }

        mysqli_stmt_close($stmt);
    }
}


/*
|--------------------------------------------------------------------------
| Statut
|--------------------------------------------------------------------------
*/

$status =
    strtolower(
        trim(
            (string) (
                $club['status']
                ?? ''
            )
        )
    );


if ($status === 'active') {

    $statusText =
        'Actif';

    $statusClass =
        'bg-emerald-50 text-emerald-700';

} elseif ($status === 'rejected') {

    $statusText =
        'Refusé';

    $statusClass =
        'bg-red-50 text-red-700';

} else {

    $statusText =
        'En attente';

    $statusClass =
        'bg-amber-50 text-amber-700';
}


/*
|--------------------------------------------------------------------------
| Popup
|--------------------------------------------------------------------------
*/

$popup =
    $_SESSION['popup']
    ?? null;

unset(
    $_SESSION['popup']
);

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        ClubConnect | Espace Manager
    </title>


    <link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime(
        $_SERVER['DOCUMENT_ROOT'] . '../assets/css/style.css'
    ) ?>">

</head>


<body class="min-h-screen
           bg-slate-50
           text-slate-900">


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
     POPUP
========================================================= -->

    <?php if ($popup): ?>

        <div class="fixed
               top-5
               right-5
               z-[100]
               max-w-sm
               w-[calc(100%-2rem)]">

            <div class="bg-white
                   rounded-2xl
                   border
                   border-slate-200
                   shadow-xl
                   p-4">

                <p class="text-sm
                       font-semibold
                       <?= ($popup['type'] ?? '') === 'success'
                           ? 'text-emerald-700'
                           : 'text-red-700'
                           ?>">

                    <?= e(
                        $popup['message']
                        ?? ''
                    ) ?>

                </p>

            </div>

        </div>

    <?php endif; ?>



    <!-- =========================================================
     MAIN
========================================================= -->

    <main class="max-w-7xl
           mx-auto
           px-4 sm:px-6 lg:px-8
           py-8">


        <!-- Welcome -->

        <section class="mb-8">

            <span class="inline-flex
                   px-3 py-1
                   rounded-full
                   bg-cyan-50
                   text-cyan-700
                   text-xs
                   font-bold">

                ESPACE MANAGER

            </span>


            <h1 class="mt-3
                   text-3xl
                   sm:text-4xl
                   font-bold
                   tracking-tight">

                Bonjour <?= e($prenom ?: $fullName) ?>

            </h1>


            <p class="mt-2
                   text-slate-500">

                Gérez votre club depuis votre espace
                d'administration.

            </p>

        </section>



        <?php if (!$club): ?>


            <!-- =================================================
             AUCUN CLUB
        ================================================== -->

            <section class="bg-white
                   border
                   border-slate-200
                   rounded-2xl
                   p-8
                   text-center">

                <div class="mx-auto
                       w-16 h-16
                       rounded-2xl
                       bg-cyan-50
                       text-cyan-700
                       flex
                       items-center
                       justify-center
                       text-2xl
                       font-bold">

                    C

                </div>


                <h2 class="mt-5
                       text-xl
                       font-bold">

                    Aucun club associé

                </h2>


                <p class="mt-2
                       text-sm
                       leading-6
                       text-slate-500
                       max-w-md
                       mx-auto">

                    Votre compte manager n'est actuellement
                    associé à aucun club.

                </p>


                <a href="../clubs.php" class="inline-flex
                       mt-6
                       px-5 py-3
                       rounded-xl
                       bg-cyan-700
                       text-white
                       text-sm
                       font-semibold
                       hover:bg-cyan-800">

                    Voir les clubs

                </a>

            </section>


        <?php else: ?>


            <!-- =================================================
             CLUB
        ================================================== -->

            <section class="bg-white
                   border
                   border-slate-200
                   rounded-2xl
                   p-6
                   mb-6">

                <div class="flex
                       flex-col
                       md:flex-row
                       md:items-center
                       gap-5">

                    <!-- Logo -->

                    <div class="w-20 h-20
                           shrink-0
                           rounded-2xl
                           bg-slate-100
                           overflow-hidden
                           flex
                           items-center
                           justify-center">

                        <?php if (
                            !empty($club['logo'])
                        ): ?>

                            <img src="<?= e(
                                '../' .
                                ltrim(
                                    $club['logo'],
                                    '/'
                                )
                            ) ?>" alt="<?= e(
                                 $club['club_name']
                             ) ?>" class="w-full
                                   h-full
                                   object-cover">

                        <?php else: ?>

                            <span class="text-2xl
                                   font-bold
                                   text-cyan-700">

                                <?= e(
                                    strtoupper(
                                        mb_substr(
                                            $club['club_name'],
                                            0,
                                            1
                                        )
                                    )
                                ) ?>

                            </span>

                        <?php endif; ?>

                    </div>


                    <!-- Club info -->

                    <div class="flex-1
                           min-w-0">

                        <div class="flex
                               flex-wrap
                               items-center
                               gap-2">

                            <h2 class="text-2xl
                                   font-bold">

                                <?= e(
                                    $club['club_name']
                                ) ?>

                            </h2>


                            <span class="px-3 py-1
                                   rounded-full
                                   text-xs
                                   font-semibold
                                   <?= $statusClass ?>">

                                <?= $statusText ?>

                            </span>

                        </div>


                        <p class="mt-1
                               text-sm
                               font-semibold
                               text-cyan-700">

                            <?= e(
                                $club['category']
                            ) ?>

                        </p>


                        <p class="mt-3
                               text-sm
                               leading-6
                               text-slate-500
                               max-w-3xl">

                            <?= e(
                                $club['description']
                            ) ?>

                        </p>

                    </div>


                    <!-- Action -->

                    <a href="dashboard.php" class="shrink-0
                           px-4 py-3
                           rounded-xl
                           bg-slate-900
                           text-white
                           text-sm
                           font-semibold
                           hover:bg-slate-800">

                        Gérer le club

                    </a>

                </div>

            </section>



            <!-- =================================================
             STATISTIQUES
        ================================================== -->

            <section class="grid
                   grid-cols-1
                   sm:grid-cols-3
                   gap-4
                   mb-6">

                <!-- Membres -->

                <div class="bg-white
                       border
                       border-slate-200
                       rounded-2xl
                       p-5">

                    <p class="text-sm
                           text-slate-500">

                        Membres

                    </p>


                    <p class="mt-2
                           text-3xl
                           font-bold">

                        <?= $memberCount ?>

                    </p>


                    <p class="mt-1
                           text-xs
                           text-slate-400">

                        /
                        <?= (int) 
                            $club['members_max']
                            ?>
                        maximum

                    </p>

                </div>

                <!-- =================================================
     CHAT DU CLUB
================================================== -->

                <a href="../club-messages.php?club_id=<?= (int) $club['id'] ?>" class="bg-white
           border
           border-slate-200
           rounded-2xl
           p-5
           hover:border-cyan-300
           hover:shadow-sm
           transition">

                    <div class="w-10
               h-10
               rounded-xl
               bg-cyan-50
               text-cyan-700
               flex
               items-center
               justify-center
               font-bold">
                        #
                    </div>


                    <h3 class="mt-4
               font-bold">
                        Chat du club
                    </h3>


                    <p class="mt-1
               text-sm
               text-slate-500">
                        Discuter avec les membres et gérer
                        la modération du chat.
                    </p>

                </a>


                <!-- Demandes -->

                <div class="bg-white
                       border
                       border-amber-200
                       rounded-2xl
                       p-5">

                    <p class="text-sm
                           text-amber-600">

                        Demandes

                    </p>


                    <p class="mt-2
                           text-3xl
                           font-bold">

                        <?= $pendingRequests ?>

                    </p>


                    <p class="mt-1
                           text-xs
                           text-amber-600">

                        En attente

                    </p>

                </div>


                <!-- Capacité -->

                <div class="bg-white
                       border
                       border-emerald-200
                       rounded-2xl
                       p-5">

                    <p class="text-sm
                           text-emerald-600">

                        Capacité

                    </p>


                    <p class="mt-2
                           text-3xl
                           font-bold">

                        <?= (int) 
                            $club['members_max']
                            ?>

                    </p>


                    <p class="mt-1
                           text-xs
                           text-emerald-600">

                        Membres maximum

                    </p>

                </div>

            </section>



            <!-- =================================================
             ACTIONS RAPIDES
        ================================================== -->

            <section>

                <h2 class="text-xl
                       font-bold
                       mb-4">

                    Actions rapides

                </h2>


                <div class="grid
                       grid-cols-1
                       sm:grid-cols-2
                       lg:grid-cols-3
                       gap-4">


                    <a href="dashboard.php" class="bg-white
                           border
                           border-slate-200
                           rounded-2xl
                           p-5
                           hover:border-cyan-300
                           hover:shadow-sm
                           transition">

                        <div class="w-10 h-10
                               rounded-xl
                               bg-cyan-50
                               text-cyan-700
                               flex
                               items-center
                               justify-center
                               font-bold">

                            C

                        </div>


                        <h3 class="mt-4
                               font-bold">

                            Gérer le club

                        </h3>


                        <p class="mt-1
                               text-sm
                               text-slate-500">

                            Modifier les informations
                            de votre club.

                        </p>

                    </a>



                    <a href="dashboard.php?section=members" class="bg-white
                           border
                           border-slate-200
                           rounded-2xl
                           p-5
                           hover:border-cyan-300
                           hover:shadow-sm
                           transition">

                        <div class="w-10 h-10
                               rounded-xl
                               bg-purple-50
                               text-purple-700
                               flex
                               items-center
                               justify-center
                               font-bold">

                            M

                        </div>


                        <h3 class="mt-4
                               font-bold">

                            Membres

                        </h3>


                        <p class="mt-1
                               text-sm
                               text-slate-500">

                            Gérer les membres
                            du club.

                        </p>

                    </a>



                    <a href="dashboard.php?section=requests" class="bg-white
                           border
                           border-slate-200
                           rounded-2xl
                           p-5
                           hover:border-cyan-300
                           hover:shadow-sm
                           transition">

                        <div class="w-10 h-10
                               rounded-xl
                               bg-amber-50
                               text-amber-700
                               flex
                               items-center
                               justify-center
                               font-bold">

                            R

                        </div>


                        <h3 class="mt-4
                               font-bold">

                            Demandes

                        </h3>


                        <p class="mt-1
                               text-sm
                               text-slate-500">

                            Voir les demandes
                            d'adhésion.

                        </p>

                    </a>

                </div>

            </section>


        <?php endif; ?>


    </main>



    <!-- =========================================================
     FOOTER
========================================================= -->

    <footer class="mt-10
           border-t
           border-slate-200
           bg-white">

        <div class="max-w-7xl
               mx-auto
               px-4 sm:px-6 lg:px-8
               py-6
               flex
               flex-col
               sm:flex-row
               sm:justify-between
               gap-3">

            <p class="text-sm
                   text-slate-500">

                ClubConnect — Espace Manager

            </p>


            <a href="../index.php" class="text-sm
                   font-semibold
                   text-cyan-700">

                Retour au site

            </a>

        </div>

    </footer>


</body>

</html>