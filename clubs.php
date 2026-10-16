<?php

session_start();

require_once __DIR__ . "/config/database.php";

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

$isLoggedIn = isset($_SESSION["id"]);
$userId = $isLoggedIn ? (int) $_SESSION["id"] : 0;

$search = trim($_GET["search"] ?? "");
$category = trim($_GET["category"] ?? "");


/*
|--------------------------------------------------------------------------
| Catégories
|--------------------------------------------------------------------------
*/

$categories = [];

$categoryResult = mysqli_query(
    $con,
    "
    SELECT DISTINCT category
    FROM clubs
    WHERE status = 'active'
    AND category IS NOT NULL
    AND category != ''
    ORDER BY category ASC
    "
);

if ($categoryResult) {

    while ($row = mysqli_fetch_assoc($categoryResult)) {
        $categories[] = $row["category"];
    }

    mysqli_free_result($categoryResult);
}


/*
|--------------------------------------------------------------------------
| Clubs
|--------------------------------------------------------------------------
*/

$clubs = [];

$sql = "
    SELECT
        id,
        club_name,
        category,
        description,
        logo,
        members_max,
        status,
        creation_date
    FROM clubs
    WHERE status = 'active'
";

$params = [];
$types = "";


if ($search !== "") {

    $sql .= "
        AND (
            club_name LIKE ?
            OR category LIKE ?
            OR description LIKE ?
        )
    ";

    $like = "%" . $search . "%";

    $params[] = $like;
    $params[] = $like;
    $params[] = $like;

    $types .= "sss";
}


if ($category !== "") {

    $sql .= " AND category = ? ";

    $params[] = $category;
    $types .= "s";
}


$sql .= "
    ORDER BY creation_date DESC, id DESC
";


$stmt = mysqli_prepare($con, $sql);

if (!$stmt) {
    die("Erreur SQL : " . e(mysqli_error($con)));
}


if (!empty($params)) {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );
}


if (!mysqli_stmt_execute($stmt)) {

    die(
        "Erreur SQL : " .
        e(mysqli_stmt_error($stmt))
    );
}


$result = mysqli_stmt_get_result($stmt);

while ($club = mysqli_fetch_assoc($result)) {
    $clubs[] = $club;
}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Clubs déjà rejoints
|--------------------------------------------------------------------------
*/

$joinedClubs = [];

if ($isLoggedIn) {

    $stmt = mysqli_prepare(
        $con,
        "
        SELECT club_id
        FROM club_members
        WHERE user_id = ?
        "
    );

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $userId
        );

        mysqli_stmt_execute($stmt);

        mysqli_stmt_bind_result(
            $stmt,
            $joinedClubId
        );

        while (mysqli_stmt_fetch($stmt)) {
            $joinedClubs[(int) $joinedClubId] = true;
        }

        mysqli_stmt_close($stmt);
    }
}

$clubCount = count($clubs);

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Clubs | ClubConnect</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body class="min-h-screen bg-slate-50 text-slate-900">


<?php
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/PopUpMsg.php";
?>


<main>

    <!-- HERO -->

    <section class="bg-white border-b border-slate-200">

        <div
            class="
                max-w-7xl
                mx-auto
                px-4
                sm:px-6
                lg:px-8
                py-12
                sm:py-16
            "
        >

            <div
                class="
                    flex
                    flex-col
                    lg:flex-row
                    lg:items-end
                    lg:justify-between
                    gap-8
                "
            >

                <div class="max-w-3xl">

                    <span
                        class="
                            inline-flex
                            items-center
                            px-3
                            py-1.5
                            rounded-full
                            bg-cyan-50
                            text-cyan-700
                            text-sm
                            font-medium
                        "
                    >
                        FST Fès
                    </span>


                    <h1
                        class="
                            mt-5
                            text-4xl
                            sm:text-5xl
                            font-bold
                            tracking-tight
                            text-slate-900
                        "
                    >
                        Découvrez les clubs

                        <span class="text-cyan-700">
                            de la FST.
                        </span>
                    </h1>


                    <p
                        class="
                            mt-5
                            text-base
                            sm:text-lg
                            leading-8
                            text-slate-600
                            max-w-2xl
                        "
                    >
                        Explorez les clubs actifs,
                        découvrez leurs activités et
                        rejoignez une communauté
                        qui correspond à vos intérêts.
                    </p>

                </div>


                <div
                    class="
                        shrink-0
                        rounded-2xl
                        border
                        border-slate-200
                        bg-slate-50
                        px-5
                        py-4
                    "
                >

                    <p
                        class="
                            text-xs
                            font-semibold
                            uppercase
                            tracking-wider
                            text-slate-500
                        "
                    >
                        Clubs disponibles
                    </p>

                    <p
                        class="
                            mt-1
                            text-3xl
                            font-bold
                            text-cyan-700
                        "
                    >
                        <?= $clubCount ?>
                    </p>

                </div>

            </div>

        </div>

    </section>


    <!-- FILTRES -->

    <section class="bg-white border-b border-slate-200">

        <div
            class="
                max-w-7xl
                mx-auto
                px-4
                sm:px-6
                lg:px-8
                py-5
            "
        >

            <form
                method="GET"
                action="clubs.php"
                class="
                    grid
                    grid-cols-1
                    md:grid-cols-[1fr_220px_auto]
                    gap-3
                "
            >

                <input
                    type="search"
                    name="search"
                    value="<?= e($search) ?>"
                    placeholder="Rechercher un club..."
                    class="
                        w-full
                        rounded-xl
                        border
                        border-slate-300
                        bg-white
                        px-4
                        py-3
                        text-sm
                        outline-none
                        transition
                        focus:border-cyan-600
                        focus:ring-2
                        focus:ring-cyan-100
                    "
                >


                <select
                    name="category"
                    class="
                        rounded-xl
                        border
                        border-slate-300
                        bg-white
                        px-4
                        py-3
                        text-sm
                        outline-none
                        focus:border-cyan-600
                        focus:ring-2
                        focus:ring-cyan-100
                    "
                >

                    <option value="">
                        Toutes les catégories
                    </option>

                    <?php foreach ($categories as $cat): ?>

                        <option
                            value="<?= e($cat) ?>"
                            <?= $category === $cat
                                ? "selected"
                                : ""
                            ?>
                        >
                            <?= e($cat) ?>
                        </option>

                    <?php endforeach; ?>

                </select>


                <button
                    type="submit"
                    class="
                        rounded-xl
                        bg-cyan-700
                        px-6
                        py-3
                        text-sm
                        font-semibold
                        text-white
                        transition
                        hover:bg-cyan-800
                    "
                >
                    Rechercher
                </button>

            </form>

        </div>

    </section>


    <!-- CLUBS -->

    <section>

        <div
            class="
                max-w-7xl
                mx-auto
                px-4
                sm:px-6
                lg:px-8
                py-10
                sm:py-14
            "
        >

            <?php if (empty($clubs)): ?>

                <div
                    class="
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        p-10
                        sm:p-14
                        text-center
                        shadow-sm
                    "
                >

                    <div
                        class="
                            mx-auto
                            flex
                            h-16
                            w-16
                            items-center
                            justify-center
                            rounded-2xl
                            bg-cyan-50
                            text-cyan-700
                            text-2xl
                            font-bold
                        "
                    >
                        C
                    </div>


                    <h2
                        class="
                            mt-5
                            text-xl
                            font-bold
                            text-slate-900
                        "
                    >
                        Aucun club trouvé
                    </h2>


                    <p
                        class="
                            mt-2
                            text-sm
                            text-slate-500
                        "
                    >
                        Aucun club ne correspond
                        à votre recherche.
                    </p>


                    <?php if (
                        $search !== "" ||
                        $category !== ""
                    ): ?>

                        <a
                            href="clubs.php"
                            class="
                                mt-6
                                inline-flex
                                rounded-xl
                                border
                                border-slate-300
                                px-5
                                py-2.5
                                text-sm
                                font-semibold
                                text-slate-700
                                hover:bg-slate-100
                            "
                        >
                            Réinitialiser
                        </a>

                    <?php endif; ?>

                </div>


            <?php else: ?>


                <div
                    class="
                        grid
                        grid-cols-1
                        sm:grid-cols-2
                        lg:grid-cols-3
                        gap-6
                    "
                >

                    <?php foreach ($clubs as $club): ?>

                        <?php

                        $clubId =
                            (int) $club["id"];

                        $clubName =
                            (string) $club["club_name"];

                        $logo =
                            trim(
                                (string)(
                                    $club["logo"] ?? ""
                                )
                            );

                        $isJoined =
                            isset(
                                $joinedClubs[$clubId]
                            );

                        ?>


                        <article
                            class="
                                group
                                flex
                                flex-col
                                overflow-hidden
                                rounded-3xl
                                border
                                border-slate-200
                                bg-white
                                shadow-sm
                                transition
                                duration-200
                                hover:-translate-y-1
                                hover:shadow-lg
                            "
                        >

                            <!-- LOGO -->

                            <div
                                class="
                                    relative
                                    flex
                                    h-48
                                    items-center
                                    justify-center
                                    overflow-hidden
                                    bg-gradient-to-br
                                    from-cyan-700
                                    to-slate-900
                                "
                            >

                                <?php if ($logo !== ""): ?>

                                    <img
                                        src="<?= e($logo) ?>"
                                        alt="<?= e($clubName) ?>"
                                        class="
                                            relative
                                            z-10
                                            h-28
                                            w-28
                                            rounded-3xl
                                            object-cover
                                            border-4
                                            border-white/20
                                            shadow-xl
                                        "
                                    >

                                <?php else: ?>

                                    <div
                                        class="
                                            relative
                                            z-10
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
                                            border
                                            border-white/10
                                        "
                                    >
                                        <?= e(
                                            mb_substr(
                                                $clubName,
                                                0,
                                                1,
                                                "UTF-8"
                                            )
                                        ) ?>
                                    </div>

                                <?php endif; ?>


                                <?php if ($isJoined): ?>

                                    <span
                                        class="
                                            absolute
                                            right-4
                                            top-4
                                            z-20
                                            rounded-full
                                            bg-white
                                            px-3
                                            py-1
                                            text-xs
                                            font-bold
                                            text-green-700
                                            shadow-sm
                                        "
                                    >
                                        Membre
                                    </span>

                                <?php endif; ?>

                            </div>


                            <!-- CONTENU -->

                            <div
                                class="
                                    flex
                                    flex-1
                                    flex-col
                                    p-6
                                "
                            >

                                <span
                                    class="
                                        w-fit
                                        rounded-full
                                        bg-cyan-50
                                        px-3
                                        py-1
                                        text-xs
                                        font-semibold
                                        text-cyan-700
                                    "
                                >
                                    <?= e(
                                        $club["category"]
                                    ) ?>
                                </span>


                                <h2
                                    class="
                                        mt-4
                                        text-xl
                                        font-bold
                                        text-slate-900
                                    "
                                >
                                    <?= e($clubName) ?>
                                </h2>


                                <p
                                    class="
                                        mt-3
                                        line-clamp-3
                                        text-sm
                                        leading-6
                                        text-slate-500
                                    "
                                >
                                    <?= e(
                                        $club["description"]
                                    ) ?>
                                </p>


                                <div
                                    class="
                                        mt-5
                                        flex
                                        items-center
                                        justify-between
                                        border-t
                                        border-slate-100
                                        pt-4
                                        text-sm
                                    "
                                >

                                    <span class="text-slate-500">
                                        Capacité
                                    </span>

                                    <strong class="text-slate-700">
                                        <?= (int) $club["members_max"] ?>
                                        membres
                                    </strong>

                                </div>


                                <div class="mt-5">

                                    <?php if (!$isLoggedIn): ?>

                                        <a
                                            href="login.php"
                                            class="
                                                flex
                                                w-full
                                                items-center
                                                justify-center
                                                rounded-xl
                                                bg-cyan-700
                                                px-4
                                                py-3
                                                text-sm
                                                font-semibold
                                                text-white
                                                transition
                                                hover:bg-cyan-800
                                            "
                                        >
                                            Se connecter pour rejoindre
                                        </a>


                                    <?php elseif ($isJoined): ?>

                                        <a
                                            href="club-messages.php?club_id=<?= $clubId ?>"
                                            class="
                                                flex
                                                w-full
                                                items-center
                                                justify-center
                                                rounded-xl
                                                border
                                                border-slate-300
                                                bg-white
                                                px-4
                                                py-3
                                                text-sm
                                                font-semibold
                                                text-slate-700
                                                transition
                                                hover:bg-slate-50
                                            "
                                        >
                                            Ouvrir le chat
                                        </a>


                                    <?php else: ?>

                                        <form
                                            action="student/traitement.php"
                                            method="POST"
                                        >

                                            <input
                                                type="hidden"
                                                name="club_id"
                                                value="<?= $clubId ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="join_club"
                                                class="
                                                    flex
                                                    w-full
                                                    items-center
                                                    justify-center
                                                    rounded-xl
                                                    bg-cyan-700
                                                    px-4
                                                    py-3
                                                    text-sm
                                                    font-semibold
                                                    text-white
                                                    transition
                                                    hover:bg-cyan-800
                                                "
                                            >
                                                Rejoindre le club
                                            </button>

                                        </form>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

</body>
</html>