<?php

/*
|--------------------------------------------------------------------------
| ClubConnect
| admin/dashboard.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../config/database.php";


/*
|--------------------------------------------------------------------------
| Sécurité
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


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
| CSRF
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));
}


/*
|--------------------------------------------------------------------------
| Section
|--------------------------------------------------------------------------
*/

$section =
    $_GET['section'] ?? 'overview';

$allowedSections = [
    'overview',
    'clubs',
    'pending',
    'users'
];

if (
    !in_array(
        $section,
        $allowedSections,
        true
    )
) {

    $section = 'overview';
}


/*
|--------------------------------------------------------------------------
| Recherche
|--------------------------------------------------------------------------
*/

$search =
    trim(
        $_GET['search'] ?? ''
    );


/*
|--------------------------------------------------------------------------
| Nom administrateur
|--------------------------------------------------------------------------
*/

$adminName =
    $_SESSION['name']
    ?? (
        trim(
            ($_SESSION['prenom'] ?? '') .
            ' ' .
            ($_SESSION['nom'] ?? '')
        )
    )
    ?? 'Administrateur';


if ($adminName === '') {
    $adminName = 'Administrateur';
}


/*
|--------------------------------------------------------------------------
| Statistiques
|--------------------------------------------------------------------------
*/

$stats = [
    'users' => 0,
    'clubs' => 0,
    'active' => 0,
    'pending' => 0
];


/*
|--------------------------------------------------------------------------
| Utilisateurs
|--------------------------------------------------------------------------
*/

$result = mysqli_query(
    $con,
    "
    SELECT COUNT(*) AS total
    FROM users
    "
);

if ($result) {

    $row =
        mysqli_fetch_assoc($result);

    $stats['users'] =
        (int) $row['total'];
}


/*
|--------------------------------------------------------------------------
| Clubs
|--------------------------------------------------------------------------
*/

$result = mysqli_query(
    $con,
    "
    SELECT COUNT(*) AS total
    FROM clubs
    "
);

if ($result) {

    $row =
        mysqli_fetch_assoc($result);

    $stats['clubs'] =
        (int) $row['total'];
}


/*
|--------------------------------------------------------------------------
| Clubs actifs
|--------------------------------------------------------------------------
*/

$result = mysqli_query(
    $con,
    "
    SELECT COUNT(*) AS total
    FROM clubs
    WHERE status = 'active'
    "
);

if ($result) {

    $row =
        mysqli_fetch_assoc($result);

    $stats['active'] =
        (int) $row['total'];
}


/*
|--------------------------------------------------------------------------
| Clubs en attente
|--------------------------------------------------------------------------
*/

$result = mysqli_query(
    $con,
    "
    SELECT COUNT(*) AS total
    FROM clubs
    WHERE status = 'pending'
    "
);

if ($result) {

    $row =
        mysqli_fetch_assoc($result);

    $stats['pending'] =
        (int) $row['total'];
}


/*
|--------------------------------------------------------------------------
| Clubs
|--------------------------------------------------------------------------
*/

$clubs = [];


if (
    $section === 'clubs' ||
    $section === 'pending'
) {

    $sql = "
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
    ";


    if ($section === 'pending') {

        $sql .= "
            WHERE status = 'pending'
        ";
    }


    if ($search !== '') {

        $like =
            '%' . $search . '%';


        if ($section === 'pending') {

            $sql .= "
                AND (
                    club_name LIKE ?
                    OR category LIKE ?
                    OR description LIKE ?
                )
            ";

        } else {

            $sql .= "
                WHERE (
                    club_name LIKE ?
                    OR category LIKE ?
                    OR description LIKE ?
                )
            ";
        }
    }


    $sql .= "
        ORDER BY id DESC
    ";


    $stmt =
        mysqli_prepare(
            $con,
            $sql
        );


    if (!$stmt) {

        die(
            "Erreur SQL clubs : " .
            e(mysqli_error($con))
        );
    }


    if ($search !== '') {

        mysqli_stmt_bind_param(
            $stmt,
            "sss",
            $like,
            $like,
            $like
        );
    }


    mysqli_stmt_execute($stmt);


    $result =
        mysqli_stmt_get_result(
            $stmt
        );


    while (
        $club =
        mysqli_fetch_assoc($result)
    ) {

        $clubs[] = $club;
    }


    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| Utilisateurs
|--------------------------------------------------------------------------
*/

$users = [];


if ($section === 'users') {

    if ($search !== '') {

        $like =
            '%' . $search . '%';


        $sql = "
            SELECT
                id,
                nom,
                prenom,
                email,
                role
            FROM users
            WHERE
                nom LIKE ?
                OR prenom LIKE ?
                OR email LIKE ?
                OR role LIKE ?
            ORDER BY id DESC
        ";


        $stmt =
            mysqli_prepare(
                $con,
                $sql
            );


        if (!$stmt) {

            die(
                "Erreur SQL utilisateurs : " .
                e(mysqli_error($con))
            );
        }


        mysqli_stmt_bind_param(
            $stmt,
            "ssss",
            $like,
            $like,
            $like,
            $like
        );

    } else {

        $sql = "
            SELECT
                id,
                nom,
                prenom,
                email,
                role
            FROM users
            ORDER BY id DESC
        ";


        $stmt =
            mysqli_prepare(
                $con,
                $sql
            );


        if (!$stmt) {

            die(
                "Erreur SQL utilisateurs : " .
                e(mysqli_error($con))
            );
        }
    }


    mysqli_stmt_execute($stmt);


    $result =
        mysqli_stmt_get_result(
            $stmt
        );


    while (
        $user =
        mysqli_fetch_assoc($result)
    ) {

        $users[] = $user;
    }


    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| Message popup
|--------------------------------------------------------------------------
*/

$popup =
    $_SESSION['popup'] ?? null;

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
        ClubConnect | Administration
    </title>


    <link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime(
        $_SERVER['DOCUMENT_ROOT'] . '../assets/css/style.css'
    ) ?>">


    <style>
        .admin-card {
            transition:
                transform .2s ease,
                box-shadow .2s ease,
                border-color .2s ease;
        }

        .admin-card:hover {
            transform: translateY(-2px);
        }
    </style>

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
               z-100
               max-w-sm
               w-[calc(100%-2rem)]">

            <div class="rounded-2xl
                   border
                   bg-white
                   shadow-xl
                   p-4
                   <?= ($popup['type'] ?? '') === 'success'
                       ? 'border-emerald-200'
                       : 'border-red-200'
                       ?>">

                <div class="flex
                       items-start
                       gap-3">

                    <div class="w-9 h-9
                           shrink-0
                           rounded-xl
                           flex
                           items-center
                           justify-center
                           font-bold
                           <?= ($popup['type'] ?? '') === 'success'
                               ? 'bg-emerald-50 text-emerald-600'
                               : 'bg-red-50 text-red-600'
                               ?>">

                        <?= ($popup['type'] ?? '') === 'success'
                            ? '✓'
                            : '!'
                            ?>

                    </div>


                    <div>

                        <p class="text-sm
                               font-semibold
                               text-slate-900">

                            <?= e(
                                $popup['message']
                                ?? ''
                            ) ?>

                        </p>

                    </div>

                </div>

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


        <!-- TITRE -->

        <div class="mb-8">

            <span class="inline-flex
                   px-3 py-1
                   rounded-full
                   bg-cyan-50
                   text-cyan-700
                   text-xs
                   font-bold">

                ADMINISTRATION

            </span>


            <h1 class="mt-3
                   text-3xl
                   sm:text-4xl
                   font-bold
                   tracking-tight">

                Tableau de bord

            </h1>


            <p class="mt-2
                   text-sm
                   sm:text-base
                   text-slate-500">

                Gérez les clubs, les utilisateurs
                et les communications de ClubConnect.

            </p>

        </div>



        <!-- =========================================================
         STATISTIQUES
    ========================================================== -->

        <div class="grid
               grid-cols-2
               lg:grid-cols-4
               gap-4
               mb-8">


            <!-- Users -->

            <a href="dashboard.php?section=users" class="admin-card
                   bg-white
                   border
                   border-slate-200
                   rounded-2xl
                   p-5
                   hover:shadow-md">

                <p class="text-sm
                       text-slate-500">

                    Utilisateurs

                </p>


                <p class="mt-2
                       text-3xl
                       font-bold">

                    <?= $stats['users'] ?>

                </p>


                <p class="mt-1
                       text-xs
                       text-cyan-700">

                    Gérer les comptes

                </p>

            </a>

      


            <!-- Clubs -->

            <a href="dashboard.php?section=clubs" class="admin-card
                   bg-white
                   border
                   border-slate-200
                   rounded-2xl
                   p-5
                   hover:shadow-md">

                <p class="text-sm
                       text-slate-500">

                    Clubs

                </p>


                <p class="mt-2
                       text-3xl
                       font-bold">

                    <?= $stats['clubs'] ?>

                </p>


                <p class="mt-1
                       text-xs
                       text-cyan-700">

                    Voir les clubs

                </p>

            </a>



            <!-- Pending -->

            <a href="dashboard.php?section=pending" class="admin-card
                   bg-white
                   border
                   border-amber-200
                   rounded-2xl
                   p-5
                   hover:shadow-md">

                <p class="text-sm
                       text-amber-600">

                    En attente

                </p>


                <p class="mt-2
                       text-3xl
                       font-bold">

                    <?= $stats['pending'] ?>

                </p>


                <p class="mt-1
                       text-xs
                       text-amber-700">

                    À traiter

                </p>

            </a>



            <!-- Active -->

            <div class="admin-card
                   bg-white
                   border
                   border-emerald-200
                   rounded-2xl
                   p-5
                   hover:shadow-md">

                <p class="text-sm
                       text-emerald-600">

                    Clubs actifs

                </p>


                <p class="mt-2
                       text-3xl
                       font-bold">

                    <?= $stats['active'] ?>

                </p>


                <p class="mt-1
                       text-xs
                       text-emerald-700">

                    Actuellement actifs

                </p>

            </div>

        </div>



        <!-- =========================================================
         VUE GENERALE
    ========================================================== -->

        <?php if (
            $section === 'overview'
        ): ?>


            <div class="grid
                   grid-cols-1
                   lg:grid-cols-2
                   gap-5">


                <!-- Gestion clubs -->

                <div class="bg-white
                       border
                       border-slate-200
                       rounded-2xl
                       p-6">

                    <div class="flex
                           items-center
                           justify-between
                           gap-3">

                        <div>

                            <h2 class="text-lg
                                   font-bold">

                                Gestion des clubs

                            </h2>


                            <p class="mt-1
                                   text-sm
                                   text-slate-500">

                                Gérez les clubs de la FST Fès.

                            </p>

                        </div>


                        <div class="w-11 h-11
                               rounded-xl
                               bg-cyan-50
                               text-cyan-700
                               flex
                               items-center
                               justify-center
                               font-bold">

                            C

                        </div>

                    </div>


                    <div class="mt-6
                           grid
                           grid-cols-2
                           gap-3">

                        <a href="dashboard.php?section=clubs" class="px-4 py-3
                               rounded-xl
                               bg-slate-100
                               text-sm
                               font-semibold
                               text-slate-700
                               hover:bg-slate-200
                               text-center">

                            Tous les clubs

                        </a>


                        <a href="dashboard.php?section=pending" class="px-4 py-3
                               rounded-xl
                               bg-amber-50
                               text-sm
                               font-semibold
                               text-amber-700
                               hover:bg-amber-100
                               text-center">

                            Demandes

                        </a>

                    </div>

                </div>



                <!-- Gestion utilisateurs -->

                <div class="bg-white
                       border
                       border-slate-200
                       rounded-2xl
                       p-6">

                    <div class="flex
                           items-center
                           justify-between
                           gap-3">

                        <div>

                            <h2 class="text-lg
                                   font-bold">

                                Gestion des utilisateurs

                            </h2>


                            <p class="mt-1
                                   text-sm
                                   text-slate-500">

                                Modifiez, supprimez ou contactez
                                plusieurs utilisateurs.

                            </p>

                        </div>


                        <div class="w-11 h-11
                               rounded-xl
                               bg-purple-50
                               text-purple-700
                               flex
                               items-center
                               justify-center
                               font-bold">

                            U

                        </div>

                    </div>


                    <a href="dashboard.php?section=users" class="mt-6
                           block
                           w-full
                           px-4 py-3
                           rounded-xl
                           bg-slate-900
                           text-white
                           text-sm
                           font-semibold
                           text-center
                           hover:bg-slate-800">

                        Gérer les utilisateurs

                    </a>

                </div>


            </div>


            <!-- =========================================================
         CLUBS
    ========================================================== -->

        <?php elseif (
            $section === 'clubs' ||
            $section === 'pending'
        ): ?>


            <section class="bg-white
                   border
                   border-slate-200
                   rounded-2xl
                   overflow-hidden">


                <!-- Header -->

                <div class="p-5
                       border-b
                       border-slate-200">

                    <div class="flex
                           flex-col
                           lg:flex-row
                           lg:items-center
                           lg:justify-between
                           gap-4">

                        <div>

                            <h2 class="text-xl
                                   font-bold">

                                <?= $section === 'pending'
                                    ? 'Demandes de clubs'
                                    : 'Tous les clubs'
                                    ?>

                            </h2>


                            <p class="mt-1
                                   text-sm
                                   text-slate-500">

                                <?= count($clubs) ?>
                                résultat<?= count($clubs) > 1 ? 's' : '' ?>

                            </p>

                        </div>


                        <form method="GET" class="flex
                               gap-2
                               w-full
                               lg:w-auto">

                            <input type="hidden" name="section" value="<?= e($section) ?>">


                            <input type="search" name="search" value="<?= e($search) ?>" placeholder="Rechercher..." class="w-full
                                   lg:w-64
                                   px-4 py-2.5
                                   rounded-xl
                                   border
                                   border-slate-300
                                   text-sm
                                   outline-none
                                   focus:border-cyan-600">


                            <button type="submit" class="px-4 py-2.5
                                   rounded-xl
                                   bg-slate-900
                                   text-white
                                   text-sm
                                   font-semibold">

                                Rechercher

                            </button>

                        </form>

                    </div>

                </div>



                <?php if (
                    count($clubs) > 0
                ): ?>


                    <div class="divide-y
                           divide-slate-100">


                        <?php foreach (
                            $clubs
                            as $club
                        ): ?>


                            <?php

                            $status =
                                strtolower(
                                    trim(
                                        (string) 
                                        $club['status']
                                    )
                                );


                            if (
                                $status === 'active'
                            ) {

                                $statusText =
                                    'Actif';

                                $statusClass =
                                    'bg-emerald-50 text-emerald-700';

                            } elseif (
                                $status === 'rejected'
                            ) {

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

                            ?>


                            <article class="p-5
                                   hover:bg-slate-50">

                                <div class="flex
                                       flex-col
                                       lg:flex-row
                                       lg:items-center
                                       gap-5">


                                    <!-- Logo -->

                                    <div class="w-16 h-16
                                           shrink-0
                                           rounded-2xl
                                           bg-slate-100
                                           overflow-hidden
                                           flex
                                           items-center
                                           justify-center">

                                        <?php if (
                                            !empty(
                                            $club['logo']
                                        )
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

                                            <span class="text-xl
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



                                    <!-- Informations -->

                                    <div class="flex-1
                                           min-w-0">

                                        <div class="flex
                                               flex-wrap
                                               items-center
                                               gap-2">

                                            <h3 class="font-bold">

                                                <?= e(
                                                    $club['club_name']
                                                ) ?>

                                            </h3>


                                            <span class="px-2.5 py-1
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


                                        <p class="mt-2
                                               text-sm
                                               leading-6
                                               text-slate-500
                                               line-clamp-2">

                                            <?= e(
                                                $club['description']
                                            ) ?>

                                        </p>


                                        <div class="mt-3
                                               flex
                                               flex-wrap
                                               gap-4
                                               text-xs
                                               text-slate-400">

                                            <span>

                                                Capacité :
                                                <?= (int) 
                                                    $club['members_max']
                                                    ?>

                                            </span>


                                            <span>

                                                ID :
                                                <?= (int) 
                                                    $club['id']
                                                    ?>

                                            </span>

                                        </div>

                                    </div>



                                    <!-- Actions -->

                                    <div class="flex
                                           flex-wrap
                                           gap-2
                                           shrink-0">


                                        <?php if (
                                            $status === 'pending'
                                        ): ?>


                                            <form action="traitement.php" method="POST">

                                                <input type="hidden" name="csrf_token" value="<?= e(
                                                    $_SESSION['csrf_token']
                                                ) ?>">


                                                <input type="hidden" name="club_id" value="<?= (int) 
                                                    $club['id']
                                                    ?>">


                                                <button type="submit" name="approve_club" class="px-4 py-2.5
                                                       rounded-xl
                                                       bg-emerald-600
                                                       text-white
                                                       text-sm
                                                       font-semibold
                                                       hover:bg-emerald-700">

                                                    Accepter

                                                </button>

                                            </form>


                                            <form action="traitement.php" method="POST" onsubmit="return confirm(
                                                'Refuser ce club ?'
                                            );">

                                                <input type="hidden" name="csrf_token" value="<?= e(
                                                    $_SESSION['csrf_token']
                                                ) ?>">


                                                <input type="hidden" name="club_id" value="<?= (int) 
                                                    $club['id']
                                                    ?>">


                                                <button type="submit" name="reject_club" class="px-4 py-2.5
                                                       rounded-xl
                                                       border
                                                       border-red-200
                                                       text-red-600
                                                       text-sm
                                                       font-semibold
                                                       hover:bg-red-50">

                                                    Refuser

                                                </button>

                                            </form>


                                        <?php elseif (
                                            $status === 'active'
                                        ): ?>


                                            <form action="traitement.php" method="POST" onsubmit="return confirm(
                                                'Désactiver ce club ?'
                                            );">

                                                <input type="hidden" name="csrf_token" value="<?= e(
                                                    $_SESSION['csrf_token']
                                                ) ?>">


                                                <input type="hidden" name="club_id" value="<?= (int) 
                                                    $club['id']
                                                    ?>">


                                                <button type="submit" name="disable_club" class="px-4 py-2.5
                                                       rounded-xl
                                                       border
                                                       border-red-200
                                                       text-red-600
                                                       text-sm
                                                       font-semibold
                                                       hover:bg-red-50">

                                                    Désactiver

                                                </button>

                                            </form>


                                        <?php elseif (
                                            $status === 'rejected'
                                        ): ?>


                                            <form action="traitement.php" method="POST">

                                                <input type="hidden" name="csrf_token" value="<?= e(
                                                    $_SESSION['csrf_token']
                                                ) ?>">


                                                <input type="hidden" name="club_id" value="<?= (int) 
                                                    $club['id']
                                                    ?>">


                                                <button type="submit" name="approve_club" class="px-4 py-2.5
                                                       rounded-xl
                                                       bg-emerald-600
                                                       text-white
                                                       text-sm
                                                       font-semibold">

                                                    Réactiver

                                                </button>

                                            </form>

                                        <?php endif; ?>

                                        <form action="traitement.php" method="POST" onsubmit="return confirm(
        'Supprimer définitivement ce club ? Cette action est irréversible.'
    );">

                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                                            <input type="hidden" name="club_id" value="<?= (int) $club['id'] ?>">

                                            <button type="submit" name="delete_club" class="px-4 py-2.5
               rounded-xl
               bg-red-600
               text-white
               text-sm
               font-semibold
               hover:bg-red-700">
                                                Supprimer
                                            </button>

                                        </form>

                                    </div>

                                </div>

                            </article>


                        <?php endforeach; ?>


                    </div>


                <?php else: ?>


                    <div class="p-12
                           text-center">

                        <h3 class="font-bold">

                            Aucun club trouvé

                        </h3>


                        <p class="mt-2
                               text-sm
                               text-slate-500">

                            Aucun résultat ne correspond
                            à votre recherche.

                        </p>

                    </div>

                <?php endif; ?>


            </section>



            <!-- =========================================================
         UTILISATEURS
    ========================================================== -->

        <?php elseif (
            $section === 'users'
        ): ?>


            <section class="bg-white
                   border
                   border-slate-200
                   rounded-2xl
                   overflow-hidden">


                <!-- Header -->

                <div class="p-5
                       border-b
                       border-slate-200">

                    <div class="flex
                           flex-col
                           lg:flex-row
                           lg:items-center
                           lg:justify-between
                           gap-4">

                        <div>

                            <h2 class="text-xl
                                   font-bold">

                                Gestion des utilisateurs

                            </h2>


                            <p class="mt-1
                                   text-sm
                                   text-slate-500">

                                Modifier, supprimer ou envoyer
                                une notification à plusieurs utilisateurs.

                            </p>

                        </div>


                        <!-- Recherche -->

                        <form method="GET" class="flex
                               gap-2
                               w-full
                               lg:w-auto">

                            <input type="hidden" name="section" value="users">


                            <input type="search" name="search" value="<?= e($search) ?>" placeholder="Nom, prénom, email..."
                                class="w-full
                                   lg:w-72
                                   px-4 py-2.5
                                   rounded-xl
                                   border
                                   border-slate-300
                                   text-sm
                                   outline-none
                                   focus:border-cyan-600">


                            <button type="submit" class="px-4 py-2.5
                                   rounded-xl
                                   bg-slate-900
                                   text-white
                                   text-sm
                                   font-semibold">

                                Rechercher

                            </button>

                        </form>

                    </div>

                </div>



                <!-- =====================================================
                 NOTIFICATION
            ====================================================== -->

                <form action="traitement.php" method="POST" id="notificationForm">

                    <input type="hidden" name="csrf_token" value="<?= e(
                        $_SESSION['csrf_token']
                    ) ?>">


                    <div class="p-5
                           bg-cyan-50
                           border-b
                           border-cyan-100">

                        <div class="flex
                               flex-col
                               gap-4">


                            <div class="flex
                                   items-center
                                   justify-between
                                   gap-3">

                                <div>

                                    <h3 class="font-bold
                                           text-slate-900">

                                        Envoyer une notification

                                    </h3>


                                    <p class="mt-1
                                           text-xs
                                           text-slate-500">

                                        Sélectionnez plusieurs utilisateurs.
                                        Une notification sera créée pour chacun.

                                    </p>

                                </div>


                                <span id="selectedCount" class="shrink-0
                                       px-3 py-1.5
                                       rounded-full
                                       bg-white
                                       text-cyan-700
                                       text-xs
                                       font-bold">

                                    0 sélectionné

                                </span>

                            </div>



                            <div class="grid
                                   grid-cols-1
                                   lg:grid-cols-3
                                   gap-3">


                                <!-- Titre -->

                                <input type="text" name="title" maxlength="255" required
                                    placeholder="Titre de la notification" class="px-4 py-3
                                       rounded-xl
                                       border
                                       border-slate-300
                                       bg-white
                                       text-sm
                                       outline-none
                                       focus:border-cyan-600">


                                <!-- Type -->

                                <select name="type" class="px-4 py-3
                                       rounded-xl
                                       border
                                       border-slate-300
                                       bg-white
                                       text-sm
                                       outline-none
                                       focus:border-cyan-600">

                                    <option value="info">
                                        Information
                                    </option>

                                    <option value="success">
                                        Succès
                                    </option>

                                    <option value="warning">
                                        Avertissement
                                    </option>

                                    <option value="error">
                                        Important
                                    </option>

                                </select>


                                <!-- Bouton -->

                                <button type="submit" name="send_notification" class="px-4 py-3
                                       rounded-xl
                                       bg-cyan-700
                                       text-white
                                       text-sm
                                       font-semibold
                                       hover:bg-cyan-800">

                                    Envoyer

                                </button>

                            </div>



                            <textarea name="message" rows="3" required placeholder="Message de la notification..." class="w-full
                                   px-4 py-3
                                   rounded-xl
                                   border
                                   border-slate-300
                                   bg-white
                                   text-sm
                                   outline-none
                                   resize-y
                                   focus:border-cyan-600"></textarea>

                        </div>

                    </div>



                    <!-- =================================================
                     SELECT ALL
                ================================================== -->

                    <div class="px-5 py-3
                           bg-slate-50
                           border-b
                           border-slate-200
                           flex
                           flex-wrap
                           items-center
                           justify-between
                           gap-3">

                        <label class="flex
                               items-center
                               gap-3
                               cursor-pointer">

                            <input type="checkbox" id="selectAllUsers" class="w-4 h-4
                                   rounded
                                   border-slate-300
                                   text-cyan-700">


                            <span class="text-sm
                                   font-semibold
                                   text-slate-700">

                                Sélectionner tous

                            </span>

                        </label>


                        <button type="button" id="clearSelection" class="text-xs
                               font-semibold
                               text-slate-500
                               hover:text-cyan-700">

                            Effacer la sélection

                        </button>

                    </div>



                    <!-- =================================================
                     USERS
                ================================================== -->

                    <?php if (
                        count($users) > 0
                    ): ?>


                        <div class="divide-y
                               divide-slate-100">


                            <?php foreach (
                                $users
                                as $user
                            ): ?>


                                <?php

                                $fullName =
                                    trim(
                                        $user['prenom'] .
                                        ' ' .
                                        $user['nom']
                                    );


                                $initial =
                                    strtoupper(
                                        mb_substr(
                                            $fullName,
                                            0,
                                            1
                                        )
                                    );


                                $role =
                                    strtolower(
                                        trim(
                                            (string) 
                                            $user['role']
                                        )
                                    );


                                if (
                                    $role === 'admin'
                                ) {

                                    $roleClass =
                                        'bg-red-50 text-red-700';

                                } elseif (
                                    $role === 'manager' ||
                                    $role === 'club_manager'
                                ) {

                                    $roleClass =
                                        'bg-purple-50 text-purple-700';

                                } else {

                                    $roleClass =
                                        'bg-slate-100 text-slate-700';
                                }

                                ?>


                                <div class="p-5
                                       flex
                                       flex-col
                                       lg:flex-row
                                       lg:items-center
                                       gap-4
                                       hover:bg-slate-50">


                                    <!-- Checkbox -->

                                    <input type="checkbox" name="users[]" value="<?= (int) 
                                        $user['id']
                                        ?>" class="user-checkbox
                                           w-4 h-4
                                           shrink-0
                                           rounded
                                           border-slate-300
                                           text-cyan-700">



                                    <!-- Avatar -->

                                    <div class="w-11 h-11
                                           shrink-0
                                           rounded-full
                                           bg-cyan-50
                                           text-cyan-700
                                           flex
                                           items-center
                                           justify-center
                                           font-bold">

                                        <?= e(
                                            $initial
                                        ) ?>

                                    </div>



                                    <!-- Infos -->

                                    <div class="flex-1
                                           min-w-0">

                                        <p class="font-semibold
                                               text-slate-900">

                                            <?= e(
                                                $fullName
                                            ) ?>

                                        </p>


                                        <p class="mt-1
                                               text-sm
                                               text-slate-500
                                               break-all">

                                            <?= e(
                                                $user['email']
                                            ) ?>

                                        </p>

                                    </div>



                                    <!-- Role -->

                                    <span class="px-3 py-1.5
                                           rounded-full
                                           text-xs
                                           font-semibold
                                           <?= $roleClass ?>">

                                        <?= e(
                                            $role
                                        ) ?>

                                    </span>



                                    <!-- ID -->

                                    <span class="text-xs
                                           text-slate-400">

                                        #<?= (int) 
                                            $user['id']
                                            ?>

                                    </span>



                                    <!-- Actions -->

                                    <div class="flex
                                           gap-2">

                                        <a href="edit-user.php?id=<?= (int) 
                                            $user['id']
                                            ?>" class="px-3 py-2
                                               rounded-lg
                                               bg-cyan-50
                                               text-cyan-700
                                               text-xs
                                               font-semibold
                                               hover:bg-cyan-100">

                                            Modifier

                                        </a>


                                        <?php if (
                                            (int) 
                                            $user['id']
                                            !==
                                            (int) 
                                            $_SESSION['id']
                                        ): ?>


                                            <form action="traitement.php" method="POST" onsubmit="return confirm(
                                                'Supprimer cet utilisateur ?'
                                            );">

                                                <input type="hidden" name="csrf_token" value="<?= e(
                                                    $_SESSION['csrf_token']
                                                ) ?>">


                                                <input type="hidden" name="user_id" value="<?= (int) 
                                                    $user['id']
                                                    ?>">


                                                <button type="submit" name="delete_user" class="px-3 py-2
                                                       rounded-lg
                                                       bg-red-50
                                                       text-red-600
                                                       text-xs
                                                       font-semibold
                                                       hover:bg-red-100">

                                                    Supprimer

                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </div>

                                </div>


                            <?php endforeach; ?>


                        </div>


                    <?php else: ?>


                        <div class="p-12
                               text-center">

                            <h3 class="font-bold">

                                Aucun utilisateur trouvé

                            </h3>


                            <p class="mt-2
                                   text-sm
                                   text-slate-500">

                                Aucun utilisateur ne correspond
                                à votre recherche.

                            </p>

                        </div>

                    <?php endif; ?>

                </form>

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
               sm:items-center
               sm:justify-between
               gap-3">

            <p class="text-sm
                   text-slate-500">

                ClubConnect — Administration

            </p>


            <a href="../index.php" class="text-sm
                   font-semibold
                   text-cyan-700
                   hover:text-cyan-800">

                Retour au site

            </a>

        </div>

    </footer>



    <!-- =========================================================
     JAVASCRIPT
========================================================= -->

    <script>

        const selectAll =
            document.getElementById(
                "selectAllUsers"
            );

        const clearSelection =
            document.getElementById(
                "clearSelection"
            );

        const selectedCount =
            document.getElementById(
                "selectedCount"
            );

        const checkboxes =
            document.querySelectorAll(
                ".user-checkbox"
            );


        function updateSelectedCount() {

            const selected =
                document.querySelectorAll(
                    ".user-checkbox:checked"
                ).length;


            if (selectedCount) {

                selectedCount.textContent =
                    selected +
                    (
                        selected > 1
                            ? " sélectionnés"
                            : " sélectionné"
                    );
            }


            if (selectAll) {

                selectAll.checked =
                    checkboxes.length > 0 &&
                    selected === checkboxes.length;

                selectAll.indeterminate =
                    selected > 0 &&
                    selected < checkboxes.length;
            }
        }


        if (selectAll) {

            selectAll.addEventListener(
                "change",
                function () {

                    checkboxes.forEach(
                        function (checkbox) {
                            checkbox.checked =
                                selectAll.checked;
                        }
                    );


                    updateSelectedCount();

                }
            );
        }


        checkboxes.forEach(
            function (checkbox) {

                checkbox.addEventListener(
                    "change",
                    updateSelectedCount
                );

            }
        );


        if (clearSelection) {

            clearSelection.addEventListener(
                "click",
                function () {

                    checkboxes.forEach(
                        function (checkbox) {
                            checkbox.checked = false;
                        }
                    );


                    if (selectAll) {
                        selectAll.checked = false;
                        selectAll.indeterminate = false;
                    }


                    updateSelectedCount();

                }
            );
        }


        const notificationForm =
            document.getElementById(
                "notificationForm"
            );


        if (notificationForm) {

            notificationForm.addEventListener(
                "submit",
                function (event) {

                    const selected =
                        document.querySelectorAll(
                            ".user-checkbox:checked"
                        );


                    if (selected.length === 0) {

                        event.preventDefault();

                        alert(
                            "Sélectionnez au moins un utilisateur."
                        );

                    }

                }
            );
        }


        updateSelectedCount();

    </script>


</body>

</html>