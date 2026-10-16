<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClubConnect | Accueil</title>
</head>
<body>
<?php

/*
|--------------------------------------------------------------------------
| ClubConnect - Espace étudiant
|--------------------------------------------------------------------------
*/

require_once "auth.php";
require_once "../includes/header.php";
require_once "../includes/PopUpMsg.php";

require_once "../config/database.php";


$userId = $currentUserId;


/*
|--------------------------------------------------------------------------
| Clubs de l'étudiant
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $con,
    "SELECT
        c.id,
        c.club_name,
        c.category,
        c.description,
        c.logo
     FROM club_members cm
     INNER JOIN clubs c
        ON c.id = cm.club_id
     WHERE cm.user_id = ?
       AND c.status = 'active'
     ORDER BY cm.joined_at DESC"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $userId
);

mysqli_stmt_execute($stmt);

$myClubsResult =
    mysqli_stmt_get_result($stmt);

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Nombre de clubs
|--------------------------------------------------------------------------
*/

$myClubsCount =
    mysqli_num_rows($myClubsResult);


/*
|--------------------------------------------------------------------------
| Événements à venir
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $con,
    "SELECT
        e.id,
        e.title,
        e.location,
        e.event_date,
        c.club_name
     FROM events e
     INNER JOIN clubs c
        ON c.id = e.club_id
     INNER JOIN club_members cm
        ON cm.club_id = c.id
     WHERE cm.user_id = ?
       AND e.event_date >= NOW()
     ORDER BY e.event_date ASC
     LIMIT 5"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $userId
);

mysqli_stmt_execute($stmt);

$eventsResult =
    mysqli_stmt_get_result($stmt);

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Demandes en attente
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $con,
    "SELECT
        cr.id,
        cr.status,
        cr.created_at,
        c.club_name
     FROM club_requests cr
     INNER JOIN clubs c
        ON c.id = cr.club_id
     WHERE cr.user_id = ?
       AND cr.status = 'pending'
     ORDER BY cr.created_at DESC"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $userId
);

mysqli_stmt_execute($stmt);

$requestsResult =
    mysqli_stmt_get_result($stmt);

mysqli_stmt_close($stmt);

$requestsCount =
    mysqli_num_rows($requestsResult);


/*
|--------------------------------------------------------------------------
| Notifications non lues
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $con,
    "SELECT COUNT(*) AS total
     FROM notifications
     WHERE user_id = ?
       AND is_read = 0"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $userId
);

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$notificationData =
    mysqli_fetch_assoc($result);

$notificationsCount =
    (int) $notificationData['total'];

mysqli_stmt_close($stmt);

?>

<main class="min-h-screen bg-slate-50">


    <!-- =========================================================
         HEADER DASHBOARD
    ========================================================== -->

    <section class="bg-white border-b border-slate-200">

        <div
            class="max-w-7xl mx-auto
                   px-4 sm:px-6 lg:px-8
                   py-10">

            <div
                class="flex flex-col
                       md:flex-row
                       md:items-center
                       md:justify-between
                       gap-6">

                <div>

                    <p
                        class="text-sm
                               font-medium
                               text-cyan-700">

                        Espace étudiant

                    </p>


                    <h1
                        class="mt-2
                               text-3xl
                               sm:text-4xl
                               font-bold
                               text-slate-900">

                        Bonjour,
                        <?= htmlspecialchars(
                            $currentUser['prenom'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </h1>


                    <p
                        class="mt-2
                               text-slate-500">

                        Bienvenue sur votre espace ClubConnect.

                    </p>

                </div>


                <!-- Notifications -->

                <a
                    href="notifications.php"
                    class="relative
                           inline-flex
                           items-center
                           justify-center
                           w-12 h-12
                           rounded-xl
                           border border-slate-200
                           bg-white
                           text-slate-600
                           hover:bg-slate-50
                           transition">

                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 17h5l-1.5-2
                               V10a6.5 6.5 0
                               00-13 0v5L4 17h5m6
                               0a3 3 0
                               11-6 0"/>

                    </svg>


                    <?php if ($notificationsCount > 0): ?>

                        <span
                            class="absolute
                                   -top-1
                                   -right-1
                                   min-w-5
                                   h-5
                                   px-1
                                   rounded-full
                                   bg-red-500
                                   text-white
                                   text-[10px]
                                   font-bold
                                   flex items-center
                                   justify-center">

                            <?= $notificationsCount > 99
                                ? '99+'
                                : $notificationsCount ?>

                        </span>

                    <?php endif; ?>

                </a>

            </div>

        </div>

    </section>



    <!-- =========================================================
         CONTENU
    ========================================================== -->

    <section>

        <div
            class="max-w-7xl mx-auto
                   px-4 sm:px-6 lg:px-8
                   py-10">


            <!-- =================================================
                 STATISTIQUES
            ================================================== -->

            <div
                class="grid
                       grid-cols-1
                       sm:grid-cols-2
                       lg:grid-cols-3
                       gap-5">


                <!-- Clubs -->
                <div
                    class="bg-white
                           border border-slate-200
                           rounded-2xl
                           p-6">

                    <div
                        class="flex
                               items-center
                               justify-between">

                        <div>

                            <p
                                class="text-sm
                                       text-slate-500">

                                Mes clubs

                            </p>

                            <p
                                class="mt-2
                                       text-3xl
                                       font-bold
                                       text-slate-900">

                                <?= $myClubsCount ?>

                            </p>

                        </div>


                        <div
                            class="w-11 h-11
                                   rounded-xl
                                   bg-cyan-50
                                   text-cyan-700
                                   flex items-center
                                   justify-center">

                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 20h5v-2a4 4 0
                                       00-4-4h-1M9 20H4v-2a4
                                       4 0 014-4h1m4-10a4
                                       4 0 110 8 4 4 0
                                       000-8z"/>

                            </svg>

                        </div>

                    </div>

                </div>


                <!-- Demandes -->
                <div
                    class="bg-white
                           border border-slate-200
                           rounded-2xl
                           p-6">

                    <div
                        class="flex
                               items-center
                               justify-between">

                        <div>

                            <p
                                class="text-sm
                                       text-slate-500">

                                Demandes en attente

                            </p>

                            <p
                                class="mt-2
                                       text-3xl
                                       font-bold
                                       text-slate-900">

                                <?= $requestsCount ?>

                            </p>

                        </div>


                        <div
                            class="w-11 h-11
                                   rounded-xl
                                   bg-amber-50
                                   text-amber-700
                                   flex items-center
                                   justify-center">

                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 2m6-2a9
                                       9 0 11-18 0 9 9
                                       0 0118 0z"/>

                            </svg>

                        </div>

                    </div>

                </div>


                <!-- Notifications -->
                <div
                    class="bg-white
                           border border-slate-200
                           rounded-2xl
                           p-6">

                    <div
                        class="flex
                               items-center
                               justify-between">

                        <div>

                            <p
                                class="text-sm
                                       text-slate-500">

                                Notifications

                            </p>

                            <p
                                class="mt-2
                                       text-3xl
                                       font-bold
                                       text-slate-900">

                                <?= $notificationsCount ?>

                            </p>

                        </div>


                        <div
                            class="w-11 h-11
                                   rounded-xl
                                   bg-slate-100
                                   text-slate-600
                                   flex items-center
                                   justify-center">

                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 17h5l-1.5-2V10
                                       a6.5 6.5 0
                                       00-13 0v5L4 17h5m6
                                       0a3 3 0 11-6 0"/>

                            </svg>

                        </div>

                    </div>

                </div>

            </div>



            <!-- =================================================
                 MES CLUBS
            ================================================== -->

            <div class="mt-10">

                <div
                    class="flex
                           items-center
                           justify-between
                           gap-4
                           mb-5">

                    <div>

                        <h2
                            class="text-xl
                                   font-bold
                                   text-slate-900">

                            Mes clubs

                        </h2>

                        <p
                            class="mt-1
                                   text-sm
                                   text-slate-500">

                            Les clubs auxquels vous appartenez.

                        </p>

                    </div>


                    <a
                        href="../clubs.php"
                        class="text-sm
                               font-medium
                               text-cyan-700
                               hover:text-cyan-800">

                        Voir tous les clubs

                    </a>

                </div>


                <div
                    class="grid
                           grid-cols-1
                           sm:grid-cols-2
                           lg:grid-cols-3
                           gap-5">


                    <?php if ($myClubsCount > 0): ?>


                        <?php while (
                            $club = mysqli_fetch_assoc(
                                $myClubsResult
                            )
                        ): ?>

                            <div
                                class="bg-white
                                       border border-slate-200
                                       rounded-2xl
                                       overflow-hidden
                                       hover:shadow-md
                                       transition">


                                <!-- Logo -->
                                <div
                                    class="h-32
                                           bg-slate-100
                                           flex items-center
                                           justify-center">

                                    <?php if (
                                        !empty($club['logo'])
                                    ): ?>

                                        <img
                                            src="<?= htmlspecialchars(
                                                $club['logo'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                            alt=""
                                            class="w-full
                                                   h-full
                                                   object-cover">

                                    <?php else: ?>

                                        <div
                                            class="w-16 h-16
                                                   rounded-2xl
                                                   bg-cyan-100
                                                   text-cyan-700
                                                   flex items-center
                                                   justify-center
                                                   text-2xl
                                                   font-bold">

                                            <?= strtoupper(
                                                mb_substr(
                                                    $club['club_name'],
                                                    0,
                                                    1
                                                )
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                </div>


                                <div class="p-5">

                                    <span
                                        class="text-xs
                                               font-medium
                                               text-cyan-700">

                                        <?= htmlspecialchars(
                                            $club['category'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </span>


                                    <h3
                                        class="mt-2
                                               font-bold
                                               text-slate-900">

                                        <?= htmlspecialchars(
                                            $club['club_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </h3>


                                    <p
                                        class="mt-2
                                               text-sm
                                               text-slate-500
                                               line-clamp-2">

                                        <?= htmlspecialchars(
                                            $club['description'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </p>


                                    <a
                                        href="MyClub.php?club_id=<?= (int) $club['id'] ?>"
                                        class="block
                                               mt-5
                                               w-full
                                               text-center
                                               py-2.5
                                               rounded-xl
                                               bg-cyan-700
                                               text-white
                                               text-sm
                                               font-semibold
                                               hover:bg-cyan-800
                                               transition">

                                        Ouvrir le club

                                    </a>

                                </div>

                            </div>

                        <?php endwhile; ?>


                    <?php else: ?>


                        <div
                            class="col-span-full
                                   bg-white
                                   border border-slate-200
                                   rounded-2xl
                                   p-8
                                   text-center">

                            <h3
                                class="font-semibold
                                       text-slate-900">

                                Vous n'avez encore rejoint
                                aucun club.

                            </h3>


                            <p
                                class="mt-2
                                       text-sm
                                       text-slate-500">

                                Découvrez les clubs de la FST Fès.

                            </p>


                            <a
                                href="../clubs.php"
                                class="inline-flex
                                       mt-5
                                       px-5 py-2.5
                                       rounded-xl
                                       bg-cyan-700
                                       text-white
                                       text-sm
                                       font-semibold
                                       hover:bg-cyan-800
                                       transition">

                                Découvrir les clubs

                            </a>

                        </div>

                    <?php endif; ?>

                </div>

            </div>



            <!-- =================================================
                 EVENEMENTS
            ================================================== -->

            <div class="mt-10">

                <div class="mb-5">

                    <h2
                        class="text-xl
                               font-bold
                               text-slate-900">

                        Prochains événements

                    </h2>

                    <p
                        class="mt-1
                               text-sm
                               text-slate-500">

                        Les prochains événements de vos clubs.

                    </p>

                </div>


                <div
                    class="bg-white
                           border border-slate-200
                           rounded-2xl
                           overflow-hidden">


                    <?php if (
                        mysqli_num_rows($eventsResult) > 0
                    ): ?>


                        <?php while (
                            $event =
                            mysqli_fetch_assoc(
                                $eventsResult
                            )
                        ): ?>

                            <div
                                class="flex
                                       flex-col
                                       sm:flex-row
                                       sm:items-center
                                       gap-4
                                       p-5
                                       border-b
                                       border-slate-100
                                       last:border-0">


                                <!-- Date -->
                                <div
                                    class="shrink-0
                                           w-14 h-14
                                           rounded-xl
                                           bg-cyan-50
                                           text-cyan-700
                                           flex flex-col
                                           items-center
                                           justify-center">

                                    <?php

                                    $timestamp =
                                        strtotime(
                                            $event['event_date']
                                        );

                                    ?>

                                    <span
                                        class="text-lg
                                               font-bold">

                                        <?= date(
                                            'd',
                                            $timestamp
                                        ) ?>

                                    </span>

                                    <span
                                        class="text-[10px]
                                               uppercase">

                                        <?= date(
                                            'M',
                                            $timestamp
                                        ) ?>

                                    </span>

                                </div>


                                <!-- Infos -->
                                <div class="flex-1">

                                    <p
                                        class="text-xs
                                               font-medium
                                               text-cyan-700">

                                        <?= htmlspecialchars(
                                            $event['club_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </p>


                                    <h3
                                        class="mt-1
                                               font-semibold
                                               text-slate-900">

                                        <?= htmlspecialchars(
                                            $event['title'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </h3>


                                    <div
                                        class="mt-1
                                               flex flex-wrap
                                               gap-x-4
                                               gap-y-1
                                               text-xs
                                               text-slate-500">

                                        <span>

                                            <?= date(
                                                'd/m/Y à H:i',
                                                $timestamp
                                            ) ?>

                                        </span>


                                        <?php if (
                                            !empty(
                                                $event['location']
                                            )
                                        ): ?>

                                            <span>

                                                <?= htmlspecialchars(
                                                    $event['location'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </div>

                        <?php endwhile; ?>


                    <?php else: ?>

                        <div class="p-8 text-center">

                            <p
                                class="text-sm
                                       text-slate-500">

                                Aucun événement à venir.

                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </div>



            <!-- =================================================
                 DEMANDES
            ================================================== -->

            <?php if ($requestsCount > 0): ?>

                <div class="mt-10">

                    <h2
                        class="text-xl
                               font-bold
                               text-slate-900">

                        Mes demandes

                    </h2>


                    <div
                        class="mt-5
                               bg-white
                               border border-slate-200
                               rounded-2xl
                               overflow-hidden">

                        <?php while (
                            $request =
                            mysqli_fetch_assoc(
                                $requestsResult
                            )
                        ): ?>

                            <div
                                class="flex
                                       items-center
                                       justify-between
                                       gap-4
                                       p-5
                                       border-b
                                       border-slate-100
                                       last:border-0">

                                <div>

                                    <p
                                        class="font-semibold
                                               text-slate-900">

                                        <?= htmlspecialchars(
                                            $request['club_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </p>

                                    <p
                                        class="mt-1
                                               text-xs
                                               text-slate-500">

                                        Demande envoyée le
                                        <?= date(
                                            'd/m/Y',
                                            strtotime(
                                                $request['created_at']
                                            )
                                        ) ?>

                                    </p>

                                </div>


                                <span
                                    class="px-3 py-1.5
                                           rounded-full
                                           bg-amber-50
                                           text-amber-700
                                           text-xs
                                           font-medium">

                                    En attente

                                </span>

                            </div>

                        <?php endwhile; ?>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </section>

</main>


<?php

require_once "../includes/footer.php";

?>

</body>
</html>