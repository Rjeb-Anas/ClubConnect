<?php

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../config/database.php";

$managerId = (int) ($_SESSION['id'] ?? 0);

if ($managerId <= 0) {
    header("Location: ../login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| CLUB ID
|--------------------------------------------------------------------------
*/

$clubId = filter_input(
    INPUT_GET,
    'club_id',
    FILTER_VALIDATE_INT
);

if (!$clubId || $clubId <= 0) {
    header("Location: dashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| VÉRIFIER QUE LE MANAGER POSSÈDE LE CLUB
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $con,
    "
    SELECT
        id,
        club_name,
        members_max,
        status
    FROM clubs
    WHERE
        id = ?
        AND manager_id = ?
    LIMIT 1
    "
);

if (!$stmt) {
    die("Erreur SQL : " . mysqli_error($con));
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


if (!$club) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">

    <head>
        <meta charset="UTF-8">

        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Accès refusé</title>

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
                   border
                   border-slate-200
                   rounded-2xl
                   p-8
                   text-center">

            <div class="w-14
                       h-14
                       mx-auto
                       rounded-full
                       bg-red-50
                       text-red-600
                       flex
                       items-center
                       justify-center
                       text-xl
                       font-bold">
                !
            </div>

            <h1 class="mt-5
                       text-xl
                       font-bold">
                Accès refusé
            </h1>

            <p class="mt-2
                       text-sm
                       text-slate-500">
                Vous n'êtes pas le manager de ce club.
            </p>

            <a href="dashboard.php" class="inline-block
                       mt-6
                       px-5
                       py-3
                       rounded-xl
                       bg-cyan-700
                       text-white
                       font-semibold">
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
| RÉCUPÉRER LES MEMBRES
|--------------------------------------------------------------------------
*/

$members = [];

$stmt = mysqli_prepare(
    $con,
    "
    SELECT
        u.id,
        u.nom,
        u.prenom,
        u.email,
        u.role,
        cm.club_id
    FROM club_members cm

    INNER JOIN users u
        ON u.id = cm.user_id

    WHERE cm.club_id = ?

    ORDER BY
        u.prenom ASC,
        u.nom ASC
    "
);

if (!$stmt) {
    die("Erreur SQL : " . mysqli_error($con));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $clubId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $members[] = $row;
}

mysqli_stmt_close($stmt);


$totalMembers = count($members);

$membersMax = (int) $club['members_max'];

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Membres | <?= htmlspecialchars(
            $club['club_name'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>
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

    <main class="max-w-7xl
           mx-auto
           px-4 sm:px-6 lg:px-8
           py-8">


        <!-- =========================================================
     TITLE
========================================================= -->

        <div class="mb-7">

            <a href="club.php?id=<?= $clubId ?>" class="text-sm
               text-cyan-700
               font-medium">
                ← Retour au club
            </a>

            <div class="mt-4
               flex
               flex-col
               sm:flex-row
               sm:items-end
               sm:justify-between
               gap-4">

                <div>

                    <h1 class="text-2xl
                       sm:text-3xl
                       font-bold">
                        Membres
                    </h1>

                    <p class="mt-1
                       text-slate-500">
                        <?= htmlspecialchars(
                            $club['club_name'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                </div>


                <div class="px-4
                   py-3
                   rounded-xl
                   bg-white
                   border
                   border-slate-200">

                    <span class="text-sm
                       text-slate-500">
                        Membres
                    </span>

                    <strong class="ml-2">
                        <?= $totalMembers ?>
                        /
                        <?= $membersMax ?>
                    </strong>

                </div>

            </div>

        </div>


        <!-- =========================================================
     LISTE
========================================================= -->

        <div class="bg-white
           border
           border-slate-200
           rounded-2xl
           overflow-hidden">


            <?php if (empty($members)): ?>

                <div class="p-12
               text-center">

                    <div class="w-16
                   h-16
                   mx-auto
                   rounded-2xl
                   bg-slate-100
                   flex
                   items-center
                   justify-center
                   text-2xl">
                        +
                    </div>

                    <h2 class="mt-5
                   font-bold
                   text-lg">
                        Aucun membre
                    </h2>

                    <p class="mt-2
                   text-sm
                   text-slate-500">
                        Aucun étudiant n'a encore rejoint ce club.
                    </p>

                </div>


            <?php else: ?>


                <!-- Desktop -->

                <div class="hidden
               md:block
               overflow-x-auto">

                    <table class="w-full
                   text-left">

                        <thead class="bg-slate-50
                       border-b
                       border-slate-200">

                            <tr>

                                <th class="px-6
                               py-4
                               text-xs
                               uppercase
                               text-slate-500">
                                    Membre
                                </th>

                                <th class="px-6
                               py-4
                               text-xs
                               uppercase
                               text-slate-500">
                                    Email
                                </th>

                                <th class="px-6
                               py-4
                               text-xs
                               uppercase
                               text-slate-500">
                                    Rôle
                                </th>

                                <th class="px-6
                               py-4
                               text-xs
                               uppercase
                               text-slate-500
                               text-right">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y
                       divide-slate-100">

                            <?php foreach ($members as $member): ?>

                                <?php

                                $memberId =
                                    (int) $member['id'];

                                $fullName =
                                    trim(
                                        ($member['prenom'] ?? '') .
                                        ' ' .
                                        ($member['nom'] ?? '')
                                    );

                                if ($fullName === '') {
                                    $fullName = 'Utilisateur';
                                }

                                ?>

                                <tr class="hover:bg-slate-50">

                                    <td class="px-6
                               py-4">

                                        <div class="flex
                                   items-center
                                   gap-3">

                                            <div class="w-10
                                       h-10
                                       rounded-full
                                       bg-cyan-100
                                       text-cyan-700
                                       flex
                                       items-center
                                       justify-center
                                       font-bold">
                                                <?= strtoupper(
                                                    mb_substr(
                                                        $fullName,
                                                        0,
                                                        1
                                                    )
                                                ) ?>
                                            </div>

                                            <span class="font-semibold">
                                                <?= htmlspecialchars(
                                                    $fullName,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </span>

                                        </div>

                                    </td>


                                    <td class="px-6
                               py-4
                               text-sm
                               text-slate-500">

                                        <?= htmlspecialchars(
                                            $member['email'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </td>


                                    <td class="px-6
                               py-4">

                                        <span class="px-3
                                   py-1
                                   rounded-full
                                   bg-slate-100
                                   text-xs
                                   font-semibold">
                                            <?= htmlspecialchars(
                                                $member['role'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                    </td>


                                    <td class="px-6
                               py-4
                               text-right">

                                        <?php if ($memberId !== $managerId): ?>

                                            <form action="traitement.php" method="POST" class="inline" onsubmit="return confirm(
                                    'Retirer ce membre du club ?'
                                );">

                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(
                                                    $_SESSION['csrf_token'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>">

                                                <input type="hidden" name="club_id" value="<?= $clubId ?>">

                                                <input type="hidden" name="member_id" value="<?= $memberId ?>">

                                                <button type="submit" name="remove_member" class="px-3
                                           py-2
                                           rounded-lg
                                           bg-red-50
                                           text-red-600
                                           text-sm
                                           font-semibold
                                           hover:bg-red-100">
                                                    Retirer
                                                </button>

                                            </form>

                                        <?php else: ?>

                                            <span class="text-xs
                                       text-slate-400">
                                                Manager
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


                <!-- Mobile -->

                <div class="md:hidden
               divide-y
               divide-slate-100">

                    <?php foreach ($members as $member): ?>

                        <?php

                        $memberId =
                            (int) $member['id'];

                        $fullName =
                            trim(
                                ($member['prenom'] ?? '') .
                                ' ' .
                                ($member['nom'] ?? '')
                            );

                        if ($fullName === '') {
                            $fullName = 'Utilisateur';
                        }

                        ?>

                        <div class="p-5">

                            <div class="flex
                           items-start
                           justify-between
                           gap-3">

                                <div class="flex
                               items-center
                               gap-3">

                                    <div class="w-11
                                   h-11
                                   shrink-0
                                   rounded-full
                                   bg-cyan-100
                                   text-cyan-700
                                   flex
                                   items-center
                                   justify-center
                                   font-bold">
                                        <?= strtoupper(
                                            mb_substr(
                                                $fullName,
                                                0,
                                                1
                                            )
                                        ) ?>
                                    </div>

                                    <div>

                                        <p class="font-semibold">
                                            <?= htmlspecialchars(
                                                $fullName,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </p>

                                        <p class="text-sm
                                       text-slate-500
                                       break-all">
                                            <?= htmlspecialchars(
                                                $member['email'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </p>

                                    </div>

                                </div>


                                <span class="shrink-0
                               px-2.5
                               py-1
                               rounded-full
                               bg-slate-100
                               text-xs
                               font-semibold">
                                    <?= htmlspecialchars(
                                        $member['role'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>

                            </div>


                            <?php if ($memberId !== $managerId): ?>

                                <form action="traitement.php" method="POST" class="mt-4" onsubmit="return confirm(
                            'Retirer ce membre du club ?'
                        );">

                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(
                                        $_SESSION['csrf_token'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>">

                                    <input type="hidden" name="club_id" value="<?= $clubId ?>">

                                    <input type="hidden" name="member_id" value="<?= $memberId ?>">

                                    <button type="submit" name="remove_member" class="w-full
                                   px-4
                                   py-2.5
                                   rounded-xl
                                   bg-red-50
                                   text-red-600
                                   font-semibold
                                   hover:bg-red-100">
                                        Retirer du club
                                    </button>

                                </form>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </main>

</body>

</html>