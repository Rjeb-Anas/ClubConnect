<?php

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../config/database.php";

$userId = (int) $_SESSION['id'];
$userName = $_SESSION['name'] ?? 'Manager';

/*
|--------------------------------------------------------------------------
| Récupérer les clubs gérés par le manager
|--------------------------------------------------------------------------
*/

$clubs = [];

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
        creation_date
    FROM clubs
    WHERE manager_id = ?
    ORDER BY creation_date DESC
    "
);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $userId
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $clubs[] = $row;
    }

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| Statistiques
|--------------------------------------------------------------------------
*/

$totalClubs = count($clubs);

$activeClubs = 0;
$pendingClubs = 0;
$totalMembers = 0;

foreach ($clubs as $club) {

    if ($club['status'] === 'active') {
        $activeClubs++;
    }

    if ($club['status'] === 'pending') {
        $pendingClubs++;
    }


    /*
    | Compter les membres
    */

    $clubId = (int) $club['id'];

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

        $totalMembers +=
            (int) ($data['total'] ?? 0);

        mysqli_stmt_close($stmt);
    }
}

?>
<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
         ClubConnect | Manager
    </title>

     <link
    rel="stylesheet"
    href="../assets/css/style.css?v=<?= filemtime(
        $_SERVER['DOCUMENT_ROOT'] . '../assets/css/style.css'
    ) ?>"
>

</head>


<body
    class="min-h-screen
           bg-slate-50
           text-slate-800"
>


<!-- =========================================================
     NAVBAR
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

<main
    class="max-w-7xl
           mx-auto
           px-4 sm:px-6 lg:px-8
           py-8"
>


<!-- =========================================================
     WELCOME
========================================================= -->

<section
    class="mb-8"
>

    <div
        class="bg-cyan-900
               rounded-2xl
               p-6 sm:p-8
               text-white"
    >

        <p
            class="text-cyan-200
                   text-sm
                   font-medium"
        >
            Tableau de bord
        </p>

        <h2
            class="mt-1
                   text-2xl sm:text-3xl
                   font-bold"
        >
            Bonjour <?= htmlspecialchars($userName) ?>
        </h2>

        <p
            class="mt-2
                   text-cyan-100
                   max-w-2xl"
        >
            Gérez vos clubs, consultez vos membres
            et suivez l'état de vos demandes.
        </p>

    </div>

</section>


<!-- =========================================================
     STATISTIQUES
========================================================= -->

<section
    class="grid
           grid-cols-1
           sm:grid-cols-2
           lg:grid-cols-4
           gap-4
           mb-8"
>


    <!-- Clubs -->

    <div
        class="bg-white
               border
               border-slate-200
               rounded-2xl
               p-5"
    >

        <p
            class="text-sm
                   text-slate-500"
        >
            Mes clubs
        </p>

        <p
            class="mt-2
                   text-3xl
                   font-bold
                   text-slate-900"
        >
            <?= $totalClubs ?>
        </p>

    </div>


    <!-- Actifs -->

    <div
        class="bg-white
               border
               border-slate-200
               rounded-2xl
               p-5"
    >

        <p
            class="text-sm
                   text-slate-500"
        >
            Clubs actifs
        </p>

        <p
            class="mt-2
                   text-3xl
                   font-bold
                   text-emerald-600"
        >
            <?= $activeClubs ?>
        </p>

    </div>


    <!-- En attente -->

    <div
        class="bg-white
               border
               border-slate-200
               rounded-2xl
               p-5"
    >

        <p
            class="text-sm
                   text-slate-500"
        >
            En attente
        </p>

        <p
            class="mt-2
                   text-3xl
                   font-bold
                   text-amber-600"
        >
            <?= $pendingClubs ?>
        </p>

    </div>


    <!-- Membres -->

    <div
        class="bg-white
               border
               border-slate-200
               rounded-2xl
               p-5"
    >

        <p
            class="text-sm
                   text-slate-500"
        >
            Total membres
        </p>

        <p
            class="mt-2
                   text-3xl
                   font-bold
                   text-cyan-700"
        >
            <?= $totalMembers ?>
        </p>

    </div>

</section>


<!-- =========================================================
     ACTIONS
========================================================= -->

<section
    class="flex
           flex-wrap
           gap-3
           mb-8"
>

    <a
        href="../clubs.php"
        class="px-5 py-3
               rounded-xl
               bg-cyan-700
               text-white
               font-semibold
               hover:bg-cyan-800"
    >
        Voir les clubs
    </a>

    <a
        href="../profile.php"
        class="px-5 py-3
               rounded-xl
               bg-white
               border
               border-slate-200
               font-semibold
               hover:bg-slate-50"
    >
        Mon profil
    </a>

</section>


<!-- =========================================================
     MES CLUBS
========================================================= -->

<section>

    <div
        class="flex
               items-center
               justify-between
               mb-5"
    >

        <div>

            <h2
                class="text-xl
                       font-bold
                       text-slate-900"
            >
                Mes clubs
            </h2>

            <p
                class="text-sm
                       text-slate-500
                       mt-1"
            >
                Les clubs dont vous êtes responsable.
            </p>

        </div>

    </div>


    <?php if (empty($clubs)): ?>

        <div
            class="bg-white
                   border
                   border-slate-200
                   rounded-2xl
                   p-10
                   text-center"
        >

            <div
                class="w-14 h-14
                       mx-auto
                       rounded-2xl
                       bg-slate-100
                       flex
                       items-center
                       justify-center
                       text-2xl"
            >
                +
            </div>

            <h3
                class="mt-4
                       font-bold
                       text-slate-900"
            >
                Aucun club
            </h3>

            <p
                class="mt-2
                       text-sm
                       text-slate-500"
            >
                Aucun club ne vous est actuellement attribué.
            </p>

            <a
                href="../clubs.php"
                class="inline-block
                       mt-5
                       px-5 py-3
                       rounded-xl
                       bg-cyan-700
                       text-white
                       font-semibold
                       hover:bg-cyan-800"
            >
                Voir les clubs
            </a>

        </div>

    <?php else: ?>


        <div
            class="grid
                   grid-cols-1
                   md:grid-cols-2
                   xl:grid-cols-3
                   gap-5"
        >

            <?php foreach ($clubs as $club): ?>

                <?php

                $clubId =
                    (int) $club['id'];

                $clubName =
                    htmlspecialchars(
                        $club['club_name'],
                        ENT_QUOTES,
                        'UTF-8'
                    );

                $category =
                    htmlspecialchars(
                        $club['category'],
                        ENT_QUOTES,
                        'UTF-8'
                    );

                $description =
                    htmlspecialchars(
                        $club['description'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    );

                $status =
                    $club['status'];

                $statusClass =
                    match ($status) {

                        'active' =>
                            'bg-emerald-50 text-emerald-700',

                        'pending' =>
                            'bg-amber-50 text-amber-700',

                        'rejected' =>
                            'bg-red-50 text-red-700',

                        default =>
                            'bg-slate-100 text-slate-600'
                    };

                ?>

                <article
                    class="bg-white
                           border
                           border-slate-200
                           rounded-2xl
                           overflow-hidden
                           hover:shadow-md
                           transition"
                >


                    <!-- Logo -->

                    <?php if (
                        !empty($club['logo'])
                    ): ?>

                        <div
                            class="h-40
                                   bg-slate-100
                                   overflow-hidden"
                        >

                            <img
                                src="../<?= htmlspecialchars(
                                    $club['logo'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                alt="<?= $clubName ?>"
                                class="w-full
                                       h-full
                                       object-cover"
                                onerror="this.style.display='none';"
                            >

                        </div>

                    <?php else: ?>

                        <div
                            class="h-40
                                   bg-slate-100
                                   flex
                                   items-center
                                   justify-center"
                        >

                            <span
                                class="text-4xl
                                       font-bold
                                       text-slate-300"
                            >
                                C
                            </span>

                        </div>

                    <?php endif; ?>


                    <!-- Content -->

                    <div
                        class="p-5"
                    >

                        <div
                            class="flex
                                   items-start
                                   justify-between
                                   gap-3"
                        >

                            <div>

                                <h3
                                    class="font-bold
                                           text-lg
                                           text-slate-900"
                                >
                                    <?= $clubName ?>
                                </h3>

                                <p
                                    class="text-sm
                                           text-cyan-700
                                           mt-1"
                                >
                                    <?= $category ?>
                                </p>

                            </div>


                            <span
                                class="shrink-0
                                       px-2.5 py-1
                                       rounded-full
                                       text-xs
                                       font-semibold
                                       <?= $statusClass ?>"
                            >
                                <?= htmlspecialchars(
                                    ucfirst($status)
                                ) ?>
                            </span>

                        </div>


                        <p
                            class="mt-4
                                   text-sm
                                   text-slate-500
                                   leading-6
                                   line-clamp-3"
                        >
                            <?= $description !== ''
                                ? $description
                                : 'Aucune description.'
                            ?>
                        </p>


                        <div
                            class="mt-5
                                   pt-4
                                   border-t
                                   border-slate-100
                                   flex
                                   items-center
                                   justify-between"
                        >

                            <span
                                class="text-sm
                                       text-slate-500"
                            >
                                Membres max :
                                <strong
                                    class="text-slate-700"
                                >
                                    <?= (int) $club['members_max'] ?>
                                </strong>
                            </span>

                            <a
                                href="club.php?id=<?= $clubId ?>"
                                class="text-sm
                                       font-semibold
                                       text-cyan-700
                                       hover:text-cyan-900"
                            >
                                Gérer
                            </a>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

</main>

</body>

</html>