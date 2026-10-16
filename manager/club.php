<?php

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../config/database.php";


/*
|--------------------------------------------------------------------------
| ID du manager connecté
|--------------------------------------------------------------------------
*/

$managerId = (int) ($_SESSION['id'] ?? 0);

if ($managerId <= 0) {
    header("Location: ../login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| ID du club
|--------------------------------------------------------------------------
*/

$clubId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$clubId || $clubId <= 0) {
    header("Location: dashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Récupérer le club
|--------------------------------------------------------------------------
|
| IMPORTANT :
| manager_id garantit que le manager ne peut gérer
| que son propre club.
|--------------------------------------------------------------------------
*/

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
    WHERE
        id = ?
        AND manager_id = ?
    LIMIT 1
    "
);

if (!$stmt) {
    die(
        "Erreur SQL : " .
        htmlspecialchars(mysqli_error($con))
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $clubId,
    $managerId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$club = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Club introuvable
|--------------------------------------------------------------------------
*/

if (!$club) {
    ?>
    <!DOCTYPE html>

    <html lang="fr">

    <head>

        <meta charset="UTF-8">

        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Club introuvable</title>

        <link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime(
            $_SERVER['DOCUMENT_ROOT'] . '../assets/css/style.css'
        ) ?>">

    </head>

    <body class="min-h-screen
               bg-slate-50
               flex
               items-center
               justify-center
               p-5">

        <div class="max-w-md
                   w-full
                   bg-white
                   rounded-2xl
                   border
                   border-slate-200
                   p-8
                   text-center">

            <div class="w-16
                       h-16
                       mx-auto
                       rounded-2xl
                       bg-red-50
                       text-red-600
                       flex
                       items-center
                       justify-center
                       text-2xl
                       font-bold">
                !
            </div>

            <h1 class="mt-5
                       text-xl
                       font-bold
                       text-slate-900">
                Club introuvable
            </h1>

            <p class="mt-2
                       text-sm
                       text-slate-500">
                Ce club n'existe pas ou vous n'êtes pas
                autorisé à le gérer.
            </p>

            <a href="dashboard.php" class="inline-block
                       mt-6
                       px-5
                       py-3
                       rounded-xl
                       bg-cyan-700
                       text-white
                       font-semibold
                       hover:bg-cyan-800">
                Retour au dashboard
            </a>

        </div>

    </body>

    </html>

    <?php
    exit;
}


/*
|--------------------------------------------------------------------------
| Données sécurisées
|--------------------------------------------------------------------------
*/

$clubName = htmlspecialchars(
    $club['club_name'],
    ENT_QUOTES,
    'UTF-8'
);

$category = htmlspecialchars(
    $club['category'],
    ENT_QUOTES,
    'UTF-8'
);

$description = htmlspecialchars(
    $club['description'] ?? '',
    ENT_QUOTES,
    'UTF-8'
);

$logo = $club['logo'] ?? '';

$status = $club['status'] ?? 'pending';

$membersMax =
    (int) $club['members_max'];


/*
|--------------------------------------------------------------------------
| Statut
|--------------------------------------------------------------------------
*/

$statusText = match ($status) {

    'active' =>
    'Actif',

    'pending' =>
    'En attente',

    'rejected' =>
    'Refusé',

    'disabled' =>
    'Désactivé',

    default =>
    ucfirst($status)
};


$statusClass = match ($status) {

    'active' =>
    'bg-emerald-50 text-emerald-700',

    'pending' =>
    'bg-amber-50 text-amber-700',

    'rejected' =>
    'bg-red-50 text-red-700',

    'disabled' =>
    'bg-slate-100 text-slate-600',

    default =>
    'bg-slate-100 text-slate-600'
};


/*
|--------------------------------------------------------------------------
| Nombre de membres
|--------------------------------------------------------------------------
*/

$totalMembers = 0;

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

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $data =
        mysqli_fetch_assoc($result);

    $totalMembers =
        (int) ($data['total'] ?? 0);

    mysqli_stmt_close($stmt);
}

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        ClubConnect | <?= $clubName ?>
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
     MAIN
========================================================= -->

    <main class="max-w-5xl
           mx-auto
           px-4 sm:px-6
           py-8">


        <!-- =========================================================
     TITLE
========================================================= -->

        <div class="mb-6">

            <a href="dashboard.php" class="text-sm
               text-cyan-700
               hover:text-cyan-900">
                ← Retour au dashboard
            </a>

            <div class="mt-4
               flex
               flex-col
               sm:flex-row
               sm:items-center
               sm:justify-between
               gap-4">

                <div>

                    <h1 class="text-2xl
                       sm:text-3xl
                       font-bold">
                        <?= $clubName ?>
                    </h1>

                    <p class="mt-1
                       text-sm
                       text-slate-500">
                        Gestion de votre club
                    </p>

                </div>


                <span class="self-start
                   px-3
                   py-1.5
                   rounded-full
                   text-sm
                   font-semibold
                   <?= $statusClass ?>">
                    <?= $statusText ?>
                </span>

            </div>

        </div>


        <!-- =========================================================
     INFORMATIONS
========================================================= -->

        <div class="bg-white
           rounded-2xl
           border
           border-slate-200
           overflow-hidden">


            <!-- Logo -->

            <div class="h-56
               bg-slate-100
               flex
               items-center
               justify-center
               overflow-hidden">

                <?php if (!empty($logo)): ?>

                    <img src="../<?= htmlspecialchars(
                        ltrim($logo, '/'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>" alt="<?= $clubName ?>" class="w-full
                       h-full
                       object-cover" onerror="this.style.display='none';">

                <?php else: ?>

                    <span class="text-6xl
                       font-bold
                       text-cyan-700">
                        <?= strtoupper(
                            mb_substr(
                                $club['club_name'],
                                0,
                                1
                            )
                        ) ?>
                    </span>

                <?php endif; ?>

            </div>


            <!-- Content -->

            <div class="p-6">

                <div class="grid
                   grid-cols-1
                   sm:grid-cols-3
                   gap-4
                   mb-7">

                    <div class="p-4
                       rounded-xl
                       bg-slate-50">

                        <p class="text-xs
                           text-slate-500">
                            Catégorie
                        </p>

                        <p class="mt-1
                           font-semibold">
                            <?= $category ?>
                        </p>

                    </div>


                    <div class="p-4
                       rounded-xl
                       bg-slate-50">

                        <p class="text-xs
                           text-slate-500">
                            Membres
                        </p>

                        <p class="mt-1
                           font-semibold">
                            <?= $totalMembers ?>
                            /
                            <?= $membersMax ?>
                        </p>

                    </div>


                    <div class="p-4
                       rounded-xl
                       bg-slate-50">

                        <p class="text-xs
                           text-slate-500">
                            ID du club
                        </p>

                        <p class="mt-1
                           font-semibold">
                            #<?= $clubId ?>
                        </p>

                    </div>

                </div>


                <!-- Description -->

                <div class="mb-8">

                    <h2 class="text-lg
                       font-bold">
                        Description
                    </h2>

                    <p class="mt-3
                       text-slate-600
                       leading-7
                       whitespace-pre-line">
                        <?= $description !== ''
                            ? $description
                            : 'Aucune description.'
                            ?>
                    </p>

                </div>


                <!-- =================================================
             ACTIONS
        ================================================== -->

                <div class="flex
                   flex-wrap
                   gap-3">

                    <a href="../club-messages.php?club_id=<?= $clubId ?>" class="px-5
           py-3
           rounded-xl
           bg-slate-900
           text-white
           font-semibold
           hover:bg-slate-800
           transition">
                        Ouvrir le chat
                    </a>

                    <a href="edit-club.php?id=<?= $clubId ?>" class="px-5
                       py-3
                       rounded-xl
                       bg-cyan-700
                       text-white
                       font-semibold
                       hover:bg-cyan-800">
                        Modifier le club
                    </a>


                    <a href="members.php?club_id=<?= $clubId ?>" class="px-5
                       py-3
                       rounded-xl
                       bg-white
                       border
                       border-slate-300
                       font-semibold
                       hover:bg-slate-50">
                        Gérer les membres
                    </a>


                    <form action="traitement.php" method="POST" onsubmit="return confirm(
                    'Voulez-vous vraiment supprimer ce club ?'
                );">

                        <?php

                        if (
                            empty(
                            $_SESSION['csrf_token']
                        )
                        ) {
                            $_SESSION['csrf_token'] =
                                bin2hex(
                                    random_bytes(32)
                                );
                        }

                        ?>

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(
                            $_SESSION['csrf_token'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>">

                        <input type="hidden" name="club_id" value="<?= $clubId ?>">

                        <button type="submit" name="delete_club" class="px-5
                           py-3
                           rounded-xl
                           bg-red-600
                           text-white
                           font-semibold
                           hover:bg-red-700">
                            Supprimer le club
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </main>

</body>

</html>