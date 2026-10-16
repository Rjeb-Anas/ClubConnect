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
    'id',
    FILTER_VALIDATE_INT
);

if (!$clubId || $clubId <= 0) {
    header("Location: dashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| RÉCUPÉRER LE CLUB
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
        manager_id
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
| CLUB INTROUVABLE
|--------------------------------------------------------------------------
*/

if (!$club) {

    header("Location: dashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] =
        bin2hex(
            random_bytes(32)
        );
}


/*
|--------------------------------------------------------------------------
| CATÉGORIES
|--------------------------------------------------------------------------
*/

$categories = [
    'Informatique',
    'Culture',
    'Sport',
    'Art',
    'Entrepreneuriat',
    'Science',
    'Social',
    'Autre'
];

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
        Modifier | <?= htmlspecialchars(
            $club['club_name'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </title>

     <link
    rel="stylesheet"
    href="/assets/css/style.css?v=<?= filemtime(
        $_SERVER['DOCUMENT_ROOT'] . '../assets/css/style.css'
    ) ?>"
>

</head>


<body
    class="min-h-screen
           bg-slate-50
           text-slate-900"
>


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

<main
    class="max-w-3xl
           mx-auto
           px-4 sm:px-6
           py-8"
>


<!-- =========================================================
     TITRE
========================================================= -->

<div
    class="mb-6"
>

    <a
        href="club.php?id=<?= $clubId ?>"
        class="text-sm
               text-cyan-700
               font-medium"
    >
        ← Retour au club
    </a>

    <h1
        class="mt-4
               text-2xl
               sm:text-3xl
               font-bold"
    >
        Modifier le club
    </h1>

    <p
        class="mt-2
               text-sm
               text-slate-500"
    >
        Modifiez les informations de votre club.
    </p>

</div>


<!-- =========================================================
     FORMULAIRE
========================================================= -->

<form
    action="traitement.php"
    method="POST"
    enctype="multipart/form-data"
    class="bg-white
           border
           border-slate-200
           rounded-2xl
           p-6 sm:p-8
           space-y-6"
>


<input
    type="hidden"
    name="csrf_token"
    value="<?= htmlspecialchars(
        $_SESSION['csrf_token'],
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>


<input
    type="hidden"
    name="club_id"
    value="<?= $clubId ?>"
>


<!-- =========================================================
     NOM
========================================================= -->

<div>

    <label
        for="club_name"
        class="block
               text-sm
               font-semibold
               mb-2"
    >
        Nom du club
    </label>

    <input
        type="text"
        id="club_name"
        name="club_name"
        required
        minlength="2"
        maxlength="150"
        value="<?= htmlspecialchars(
            $club['club_name'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
        class="w-full
               px-4
               py-3
               rounded-xl
               border
               border-slate-300
               outline-none
               focus:border-cyan-600
               focus:ring-2
               focus:ring-cyan-100"
    >

</div>


<!-- =========================================================
     CATÉGORIE
========================================================= -->

<div>

    <label
        for="category"
        class="block
               text-sm
               font-semibold
               mb-2"
    >
        Catégorie
    </label>

    <select
        id="category"
        name="category"
        required
        class="w-full
               px-4
               py-3
               rounded-xl
               border
               border-slate-300
               bg-white
               outline-none
               focus:border-cyan-600
               focus:ring-2
               focus:ring-cyan-100"
    >

        <?php foreach ($categories as $category): ?>

            <option
                value="<?= htmlspecialchars(
                    $category,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                <?= $club['category'] === $category
                    ? 'selected'
                    : ''
                ?>
            >
                <?= htmlspecialchars(
                    $category,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </option>

        <?php endforeach; ?>

    </select>

</div>


<!-- =========================================================
     DESCRIPTION
========================================================= -->

<div>

    <label
        for="description"
        class="block
               text-sm
               font-semibold
               mb-2"
    >
        Description
    </label>

    <textarea
        id="description"
        name="description"
        required
        minlength="20"
        maxlength="2000"
        rows="7"
        class="w-full
               px-4
               py-3
               rounded-xl
               border
               border-slate-300
               resize-y
               outline-none
               focus:border-cyan-600
               focus:ring-2
               focus:ring-cyan-100"
    ><?= htmlspecialchars(
        $club['description'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    ) ?></textarea>

    <div
        class="mt-1
               flex
               justify-between
               text-xs
               text-slate-400"
    >

        <span>
            Minimum 20 caractères
        </span>

        <span id="descriptionCount">
            0 / 2000
        </span>

    </div>

</div>


<!-- =========================================================
     NOMBRE DE MEMBRES
========================================================= -->

<div>

    <label
        for="members_max"
        class="block
               text-sm
               font-semibold
               mb-2"
    >
        Nombre maximum de membres
    </label>

    <input
        type="number"
        id="members_max"
        name="members_max"
        required
        min="2"
        max="10000"
        value="<?= (int) $club['members_max'] ?>"
        class="w-full
               px-4
               py-3
               rounded-xl
               border
               border-slate-300
               outline-none
               focus:border-cyan-600
               focus:ring-2
               focus:ring-cyan-100"
    >

</div>


<!-- =========================================================
     LOGO
========================================================= -->

<div>

    <label
        for="logo"
        class="block
               text-sm
               font-semibold
               mb-2"
    >
        Logo du club
    </label>


    <?php if (!empty($club['logo'])): ?>

        <div
            class="mb-4
                   w-32
                   h-32
                   rounded-2xl
                   overflow-hidden
                   bg-slate-100
                   border
                   border-slate-200"
        >

            <img
                src="../<?= htmlspecialchars(
                    ltrim(
                        $club['logo'],
                        '/'
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                alt="Logo du club"
                class="w-full
                       h-full
                       object-cover"
            >

        </div>

    <?php endif; ?>


    <input
        type="file"
        id="logo"
        name="logo"
        accept="image/jpeg,image/png,image/webp"
        class="w-full
               px-4
               py-3
               rounded-xl
               border
               border-slate-300
               bg-white
               text-sm"
    >

    <p
        class="mt-2
               text-xs
               text-slate-500"
    >
        JPG, PNG ou WEBP — maximum 2 Mo.
    </p>

</div>


<!-- =========================================================
     INFORMATIONS
========================================================= -->

<div
    class="p-4
           rounded-xl
           bg-slate-50
           border
           border-slate-200"
>

    <div
        class="flex
               flex-wrap
               gap-4
               text-sm"
    >

        <div>

            <span
                class="text-slate-500"
            >
                ID :
            </span>

            <strong>
                #<?= $clubId ?>
            </strong>

        </div>


        <div>

            <span
                class="text-slate-500"
            >
                Statut :
            </span>

            <strong>
                <?= htmlspecialchars(
                    $club['status'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>

        </div>

    </div>

</div>


<!-- =========================================================
     BOUTONS
========================================================= -->

<div
    class="flex
           flex-col-reverse
           sm:flex-row
           sm:justify-end
           gap-3
           pt-2"
>

    <a
        href="club.php?id=<?= $clubId ?>"
        class="px-5
               py-3
               rounded-xl
               border
               border-slate-300
               text-center
               font-semibold
               hover:bg-slate-50"
    >
        Annuler
    </a>


    <button
        type="submit"
        name="update_club"
        class="px-5
               py-3
               rounded-xl
               bg-cyan-700
               text-white
               font-semibold
               hover:bg-cyan-800"
    >
        Enregistrer les modifications
    </button>

</div>

</form>

</main>


<script>

const description =
    document.getElementById("description");

const counter =
    document.getElementById("descriptionCount");


function updateCounter() {

    counter.textContent =
        description.value.length +
        " / 2000";
}


updateCounter();

description.addEventListener(
    "input",
    updateCounter
);

</script>


</body>

</html>