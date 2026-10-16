<?php

/*
|--------------------------------------------------------------------------
| ClubConnect
| student/MyClub.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../config/database.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($con) || !($con instanceof mysqli)) {
    die("Erreur de connexion à la base de données.");
}

mysqli_set_charset($con, "utf8mb4");


/*
|--------------------------------------------------------------------------
| Fonction sécurité
|--------------------------------------------------------------------------
*/

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/*
|--------------------------------------------------------------------------
| Utilisateur
|--------------------------------------------------------------------------
*/

$userId = (int) (
    $_SESSION["id"]
    ?? $_SESSION["user_id"]
    ?? 0
);

if ($userId <= 0) {
    header("Location: ../login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Popup
|--------------------------------------------------------------------------
*/

$popup = $_SESSION["popup"] ?? null;

unset($_SESSION["popup"]);


/*
|--------------------------------------------------------------------------
| Club sélectionné pour afficher les membres
|--------------------------------------------------------------------------
*/

$selectedClubId = (int) (
    $_GET["members"] ?? 0
);


/*
|--------------------------------------------------------------------------
| Récupérer les clubs de l'utilisateur
|--------------------------------------------------------------------------
|
| L'utilisateur peut être :
|
| - membre dans club_members
| - manager
| - créateur
|
*/

$myClubs = [];

$stmt = mysqli_prepare(
    $con,
    "
    SELECT DISTINCT

        c.id,
        c.club_name,
        c.category,
        c.description,
        c.logo,
        c.members_max,
        c.status,
        c.manager_id,
        c.created_by,
        c.creation_date,

        (
            SELECT COUNT(*)
            FROM club_members cm2
            WHERE cm2.club_id = c.id
        ) AS member_count

    FROM clubs c

    LEFT JOIN club_members cm
        ON cm.club_id = c.id
        AND cm.user_id = ?

    WHERE
        cm.user_id IS NOT NULL
        OR c.manager_id = ?
        OR c.created_by = ?

    ORDER BY c.id DESC
    "
);

if (!$stmt) {
    die(
        "Erreur SQL : " .
        e(mysqli_error($con))
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "iii",
    $userId,
    $userId,
    $userId
);

if (!mysqli_stmt_execute($stmt)) {

    die(
        "Impossible de récupérer vos clubs : " .
        e(mysqli_stmt_error($stmt))
    );
}

$result = mysqli_stmt_get_result($stmt);

while ($club = mysqli_fetch_assoc($result)) {
    $myClubs[] = $club;
}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Vérifier que le club sélectionné appartient bien à l'utilisateur
|--------------------------------------------------------------------------
*/

$selectedClub = null;

if ($selectedClubId > 0) {

    foreach ($myClubs as $club) {

        if ((int) $club["id"] === $selectedClubId) {

            $selectedClub = $club;

            break;
        }
    }
}


/*
|--------------------------------------------------------------------------
| Membres du club sélectionné
|--------------------------------------------------------------------------
*/

$members = [];

if ($selectedClub !== null) {

    $stmt = mysqli_prepare(
        $con,
        "
        SELECT
            u.id,
            u.nom,
            u.prenom,
            u.email,
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

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $selectedClubId
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        while ($member = mysqli_fetch_assoc($result)) {
            $members[] = $member;
        }

        mysqli_stmt_close($stmt);
    }
}


/*
|--------------------------------------------------------------------------
| Nombre de clubs
|--------------------------------------------------------------------------
*/

$clubCount = count($myClubs);

?>


<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
       ClubConnect | Mes clubs
    </title>

    <link
    rel="stylesheet"
    href="../assets/css/style.css?v=<?= filemtime(
        $_SERVER['DOCUMENT_ROOT'] . '../assets/css/style.css'
    ) ?>"
>

</head>


<body class="
        min-h-screen
        bg-slate-50
        text-slate-900
    ">


    <?php

    /*
    |--------------------------------------------------------------------------
    | Header du site
    |--------------------------------------------------------------------------
    */

    require_once __DIR__ .
        "/../includes/header.php";


    /*
    |--------------------------------------------------------------------------
    | Popup
    |--------------------------------------------------------------------------
    */

    require_once __DIR__ .
        "/../includes/PopUpMsg.php";

    ?>


    <main class="
        max-w-7xl
        mx-auto
        px-4
        sm:px-6
        lg:px-8
        py-8
    ">


        <!-- =========================================================
     TITRE
========================================================= -->

        <section class="mb-8">

            <div class="
            flex
            flex-col
            sm:flex-row
            sm:items-end
            sm:justify-between
            gap-5
        ">

                <div>

                    <span class="
                    inline-flex
                    rounded-full
                    bg-cyan-50
                    px-3
                    py-1
                    text-xs
                    font-bold
                    text-cyan-700
                ">
                        MON ESPACE
                    </span>


                    <h1 class="
                    mt-3
                    text-3xl
                    sm:text-4xl
                    font-bold
                    tracking-tight
                ">
                        Mes clubs
                    </h1>


                    <p class="
                    mt-2
                    text-sm
                    sm:text-base
                    text-slate-500
                ">
                        Retrouvez les clubs auxquels vous
                        appartenez ou que vous gérez.
                    </p>

                </div>


                <a href="create-club.php" class="
                inline-flex
                items-center
                justify-center
                rounded-xl
                bg-cyan-700
                px-5
                py-3
                text-sm
                font-semibold
                text-white
                transition
                hover:bg-cyan-800
            ">
                    + Créer un club
                </a>

            </div>

        </section>


        <!-- =========================================================
     STATISTIQUE
========================================================= -->

        <div class="
        mb-8
        inline-flex
        items-center
        gap-3
        rounded-2xl
        border
        border-slate-200
        bg-white
        px-4
        py-3
        shadow-sm
    ">

            <div class="
            flex
            h-10
            w-10
            items-center
            justify-center
            rounded-xl
            bg-cyan-50
            font-bold
            text-cyan-700
        ">
                <?= $clubCount ?>
            </div>


            <div>

                <p class="
                text-sm
                font-semibold
            ">
                    <?= $clubCount === 1
                        ? "Club"
                        : "Clubs"
                        ?>
                </p>

                <p class="
                text-xs
                text-slate-500
            ">
                    dans votre espace
                </p>

            </div>

        </div>


        <?php if ($selectedClub !== null): ?>

            <!-- =========================================================
     MEMBRES
========================================================= -->

            <section class="
        mb-8
        overflow-hidden
        rounded-3xl
        border
        border-slate-200
        bg-white
        shadow-sm
    ">

                <div class="
            flex
            flex-col
            sm:flex-row
            sm:items-center
            sm:justify-between
            gap-4
            border-b
            border-slate-100
            p-5
            sm:p-6
        ">

                    <div>

                        <p class="
                    text-xs
                    font-bold
                    uppercase
                    tracking-wider
                    text-cyan-700
                ">
                            Membres
                        </p>

                        <h2 class="
                    mt-1
                    text-xl
                    font-bold
                ">
                            <?= e(
                                $selectedClub["club_name"]
                            ) ?>
                        </h2>

                        <p class="
                    mt-1
                    text-sm
                    text-slate-500
                ">
                            <?= count($members) ?>
                            membre<?= count($members) > 1 ? "s" : "" ?>
                        </p>

                    </div>


                    <a href="MyClub.php" class="
                rounded-xl
                border
                border-slate-300
                px-4
                py-2.5
                text-sm
                font-semibold
                text-slate-700
                transition
                hover:bg-slate-50
            ">
                        Fermer
                    </a>

                </div>


                <?php if (empty($members)): ?>

                    <div class="
                p-8
                text-center
                text-sm
                text-slate-500
            ">
                        Aucun membre trouvé.
                    </div>

                <?php else: ?>

                    <div class="
                grid
                grid-cols-1
                sm:grid-cols-2
                lg:grid-cols-3
                gap-3
                p-5
                sm:p-6
            ">

                        <?php foreach ($members as $member): ?>

                            <?php

                            $memberName =
                                trim(
                                    ($member["prenom"] ?? "")
                                    . " "
                                    . ($member["nom"] ?? "")
                                );

                            if ($memberName === "") {
                                $memberName =
                                    $member["email"] ?? "Membre";
                            }

                            $initial =
                                mb_strtoupper(
                                    mb_substr(
                                        $memberName,
                                        0,
                                        1,
                                        "UTF-8"
                                    ),
                                    "UTF-8"
                                );

                            ?>

                            <div class="
                        flex
                        items-center
                        gap-3
                        rounded-2xl
                        border
                        border-slate-200
                        bg-slate-50
                        p-4
                    ">

                                <div class="
                            flex
                            h-11
                            w-11
                            shrink-0
                            items-center
                            justify-center
                            rounded-xl
                            bg-cyan-700
                            font-bold
                            text-white
                        ">
                                    <?= e($initial) ?>
                                </div>


                                <div class="min-w-0">

                                    <p class="
                                truncate
                                text-sm
                                font-semibold
                                text-slate-800
                            ">
                                        <?= e($memberName) ?>
                                    </p>


                                    <p class="
                                truncate
                                text-xs
                                text-slate-500
                            ">
                                        <?= e(
                                            $member["email"]
                                        ) ?>
                                    </p>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

        <?php endif; ?>


        <!-- =========================================================
     AUCUN CLUB
========================================================= -->

        <?php if (empty($myClubs)): ?>

            <section class="
        rounded-3xl
        border
        border-slate-200
        bg-white
        p-10
        text-center
        shadow-sm
    ">

                <div class="
            mx-auto
            flex
            h-16
            w-16
            items-center
            justify-center
            rounded-2xl
            bg-cyan-50
            text-2xl
            font-bold
            text-cyan-700
        ">
                    C
                </div>


                <h2 class="
            mt-5
            text-xl
            font-bold
        ">
                    Vous n'avez aucun club
                </h2>


                <p class="
            mx-auto
            mt-2
            max-w-md
            text-sm
            leading-6
            text-slate-500
        ">
                    Vous pouvez rejoindre un club existant
                    ou créer votre propre club.
                </p>


                <div class="
            mt-6
            flex
            flex-col
            sm:flex-row
            justify-center
            gap-3
        ">

                    <a href="../clubs.php" class="
                rounded-xl
                bg-cyan-700
                px-5
                py-3
                text-sm
                font-semibold
                text-white
                hover:bg-cyan-800
            ">
                        Explorer les clubs
                    </a>


                    <a href="create-club.php" class="
                rounded-xl
                border
                border-slate-300
                px-5
                py-3
                text-sm
                font-semibold
                text-slate-700
                hover:bg-slate-50
            ">
                        Créer un club
                    </a>

                </div>

            </section>


        <?php else: ?>


            <!-- =========================================================
     LISTE DES CLUBS
========================================================= -->

            <section class="
        grid
        grid-cols-1
        md:grid-cols-2
        xl:grid-cols-3
        gap-6
    ">


                <?php foreach ($myClubs as $club): ?>

                    <?php

                    $clubId =
                        (int) $club["id"];

                    $status =
                        strtolower(
                            trim(
                                (string) $club["status"]
                            )
                        );


                    $isManager =
                        (int) $club["manager_id"]
                        === $userId;


                    $isCreator =
                        (int) $club["created_by"]
                        === $userId;


                    $isOwner =
                        $isManager ||
                        $isCreator;


                    $memberCount =
                        (int) (
                            $club["member_count"]
                            ?? 0
                        );


                    if ($status === "active") {

                        $statusText = "Actif";

                        $statusClass =
                            "bg-emerald-50 text-emerald-700";

                    } elseif ($status === "pending") {

                        $statusText = "En attente";

                        $statusClass =
                            "bg-amber-50 text-amber-700";

                    } elseif ($status === "disabled") {

                        $statusText = "Désactivé";

                        $statusClass =
                            "bg-slate-100 text-slate-700";

                    } elseif ($status === "rejected") {

                        $statusText = "Refusé";

                        $statusClass =
                            "bg-red-50 text-red-700";

                    } else {

                        $statusText =
                            ucfirst($status);

                        $statusClass =
                            "bg-slate-100 text-slate-700";
                    }


                    $logo =
                        trim(
                            (string) (
                                $club["logo"] ?? ""
                            )
                        );

                    ?>


                    <article class="
            overflow-hidden
            rounded-3xl
            border
            border-slate-200
            bg-white
            shadow-sm
            transition
            hover:-translate-y-1
            hover:shadow-lg
        ">


                        <!-- LOGO -->

                        <div class="
                relative
                flex
                h-48
                items-center
                justify-center
                overflow-hidden
                bg-gradient-to-br
                from-cyan-700
                to-slate-900
            ">

                            <?php if ($logo !== ""): ?>

                                <img src="<?= e(
                                    "../" .
                                    ltrim(
                                        $logo,
                                        "/"
                                    )
                                ) ?>" alt="<?= e(
                                     $club["club_name"]
                                 ) ?>" class="
                        h-full
                        w-full
                        object-cover
                    ">

                            <?php else: ?>

                                <div class="
                        flex
                        h-28
                        w-28
                        items-center
                        justify-center
                        rounded-3xl
                        bg-white/10
                        text-4xl
                        font-bold
                        text-white
                    ">

                                    <?= e(
                                        mb_strtoupper(
                                            mb_substr(
                                                $club["club_name"],
                                                0,
                                                1,
                                                "UTF-8"
                                            ),
                                            "UTF-8"
                                        )
                                    ) ?>

                                </div>

                            <?php endif; ?>


                            <span class="
                    absolute
                    right-4
                    top-4
                    rounded-full
                    px-3
                    py-1
                    text-xs
                    font-bold
                    <?= $statusClass ?>
                ">
                                <?= e($statusText) ?>
                            </span>

                        </div>


                        <!-- CONTENU -->

                        <div class="p-6">

                            <div class="
                    flex
                    items-start
                    justify-between
                    gap-3
                ">

                                <div class="min-w-0">

                                    <h2 class="
                            truncate
                            text-xl
                            font-bold
                        ">
                                        <?= e(
                                            $club["club_name"]
                                        ) ?>
                                    </h2>


                                    <p class="
                            mt-1
                            text-sm
                            font-semibold
                            text-cyan-700
                        ">
                                        <?= e(
                                            $club["category"]
                                        ) ?>
                                    </p>

                                </div>

                            </div>


                            <p class="
                    mt-4
                    line-clamp-3
                    text-sm
                    leading-6
                    text-slate-500
                ">
                                <?= e(
                                    $club["description"]
                                ) ?>
                            </p>


                            <!-- INFORMATIONS -->

                            <div class="
                    mt-5
                    grid
                    grid-cols-2
                    gap-3
                ">

                                <div class="
                        rounded-2xl
                        bg-slate-50
                        p-3
                    ">

                                    <p class="
                            text-xs
                            text-slate-400
                        ">
                                        Membres
                                    </p>

                                    <p class="
                            mt-1
                            font-bold
                            text-slate-800
                        ">
                                        <?= $memberCount ?>
                                        /
                                        <?= (int) $club["members_max"] ?>
                                    </p>

                                </div>


                                <div class="
                        rounded-2xl
                        bg-slate-50
                        p-3
                    ">

                                    <p class="
                            text-xs
                            text-slate-400
                        ">
                                        Votre rôle
                                    </p>

                                    <p class="
                            mt-1
                            font-bold
                            text-slate-800
                        ">

                                        <?php if ($isManager): ?>

                                            Manager

                                        <?php elseif ($isCreator): ?>

                                            Créateur

                                        <?php else: ?>

                                            Membre

                                        <?php endif; ?>

                                    </p>

                                </div>

                            </div>


                            <!-- BADGES -->

                            <div class="
                    mt-4
                    flex
                    flex-wrap
                    gap-2
                ">

                                <?php if ($isManager): ?>

                                    <span class="
                            rounded-full
                            bg-purple-50
                            px-3
                            py-1
                            text-xs
                            font-semibold
                            text-purple-700
                        ">
                                        Manager
                                    </span>

                                <?php endif; ?>


                                <?php if ($isCreator): ?>

                                    <span class="
                            rounded-full
                            bg-cyan-50
                            px-3
                            py-1
                            text-xs
                            font-semibold
                            text-cyan-700
                        ">
                                        Créateur
                                    </span>

                                <?php endif; ?>


                                <?php if (!$isOwner): ?>

                                    <span class="
                            rounded-full
                            bg-slate-100
                            px-3
                            py-1
                            text-xs
                            font-semibold
                            text-slate-600
                        ">
                                        Membre
                                    </span>

                                <?php endif; ?>

                            </div>


                            <!-- ACTIONS -->

                            <div class="
                    mt-6
                    grid
                    grid-cols-2
                    gap-2
                ">

                                <!-- CHAT -->

                                <a href="../club-messages.php?club_id=<?= $clubId ?>" class="
                        flex
                        items-center
                        justify-center
                        rounded-xl
                        bg-cyan-700
                        px-3
                        py-3
                        text-xs
                        font-semibold
                        text-white
                        transition
                        hover:bg-cyan-800
                    ">
                                    Chat
                                </a>


                                <!-- MEMBRES -->

                                <a href="MyClub.php?members=<?= $clubId ?>" class="
                        flex
                        items-center
                        justify-center
                        rounded-xl
                        border
                        border-slate-300
                        bg-white
                        px-3
                        py-3
                        text-xs
                        font-semibold
                        text-slate-700
                        transition
                        hover:bg-slate-50
                    ">
                                    Membres
                                </a>

                            </div>


                            <!-- QUITTER -->

                            <?php if (
                                !$isOwner &&
                                $status === "active"
                            ): ?>

                                <form action="traitement.php" method="POST" class="mt-2" onsubmit="
                        return confirm(
                            'Voulez-vous vraiment quitter ce club ?'
                        );
                    ">

                                    <input type="hidden" name="club_id" value="<?= $clubId ?>">

                                    <button type="submit" name="leave_club" class="
                            w-full
                            rounded-xl
                            border
                            border-red-200
                            px-4
                            py-2.5
                            text-xs
                            font-semibold
                            text-red-600
                            transition
                            hover:bg-red-50
                        ">
                                        Quitter le club
                                    </button>

                                </form>

                            <?php elseif ($isOwner): ?>

                                <div class="
                        mt-2
                        rounded-xl
                        bg-slate-50
                        px-4
                        py-2.5
                        text-center
                        text-xs
                        text-slate-500
                    ">
                                    Vous êtes responsable de ce club
                                </div>

                            <?php endif; ?>

                        </div>

                    </article>


                <?php endforeach; ?>


            </section>

        <?php endif; ?>


    </main>


    <script>

        const popup =
            document.getElementById("popup");

        if (popup) {

            setTimeout(
                () => {

                    popup.style.opacity = "0";

                    popup.style.transform =
                        "translateY(-10px)";

                    popup.style.transition =
                        "opacity .3s ease, transform .3s ease";

                    setTimeout(
                        () => popup.remove(),
                        300
                    );

                },
                4000
            );
        }

    </script>


</body>

</html>