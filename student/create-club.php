<?php

/*
|--------------------------------------------------------------------------
| ClubConnect
| student/create-club.php
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
| Utilisateur
|--------------------------------------------------------------------------
*/

$userId = (int)(
    $_SESSION['id']
    ?? $_SESSION['user_id']
    ?? 0
);


if ($userId <= 0) {

    header("Location: ../login.php");
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
| Afficher le formulaire
|--------------------------------------------------------------------------
*/

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
       ClubConnect | Créer un club
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
           bg-slate-50"
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
    class="max-w-2xl
           mx-auto
           px-4 sm:px-6
           py-10"
>


    <!-- TITRE -->

    <div
        class="mb-7"
    >

        <span
            class="inline-flex
                   px-3 py-1
                   rounded-full
                   bg-cyan-50
                   text-cyan-700
                   text-xs
                   font-bold"
        >

            CLUBCONNECT

        </span>


        <h1
            class="mt-3
                   text-3xl
                   font-bold
                   text-slate-900"
        >

            Créer un club

        </h1>


        <p
            class="mt-2
                   text-sm
                   leading-6
                   text-slate-500"
        >

            Remplissez les informations de votre club.
            Votre demande sera envoyée à l'administration
            pour validation.

        </p>

    </div>



    <!-- =====================================================
         FORMULAIRE
    ====================================================== -->

    <form
        action="../traitement.php"
        method="POST"
        enctype="multipart/form-data"
        class="bg-white
               border
               border-slate-200
               rounded-2xl
               p-6
               shadow-sm"
    >


        <!-- CSRF -->

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(
                $_SESSION['csrf_token']
            ) ?>"
        >


        <!-- ACTION -->

        <input
            type="hidden"
            name="create_club"
            value="1"
        >



        <!-- =================================================
             NOM
        ================================================== -->

        <div class="mb-5">

            <label
                for="club_name"
                class="block
                       mb-2
                       text-sm
                       font-semibold
                       text-slate-700"
            >

                Nom du club

            </label>


            <input
                type="text"
                id="club_name"
                name="club_name"
                maxlength="150"
                required
                placeholder="Ex : Club Informatique FST"
                class="w-full
                       px-4 py-3
                       rounded-xl
                       border
                       border-slate-300
                       outline-none
                       focus:border-cyan-600
                       focus:ring-2
                       focus:ring-cyan-100"
            >

        </div>



        <!-- =================================================
             CATEGORIE
        ================================================== -->

        <div class="mb-5">

            <label
                for="category"
                class="block
                       mb-2
                       text-sm
                       font-semibold
                       text-slate-700"
            >

                Catégorie

            </label>


            <select
                id="category"
                name="category"
                required
                class="w-full
                       px-4 py-3
                       rounded-xl
                       border
                       border-slate-300
                       bg-white
                       outline-none
                       focus:border-cyan-600"
            >

                <option value="">
                    Choisir une catégorie
                </option>

                <option value="Informatique">
                    Informatique
                </option>

                <option value="Culture">
                    Culture
                </option>

                <option value="Sport">
                    Sport
                </option>

                <option value="Art">
                    Art
                </option>

                <option value="Entrepreneuriat">
                    Entrepreneuriat
                </option>

                <option value="Science">
                    Science
                </option>

                <option value="Social">
                    Social
                </option>

                <option value="Autre">
                    Autre
                </option>

            </select>

        </div>



        <!-- =================================================
             DESCRIPTION
        ================================================== -->

        <div class="mb-5">

            <label
                for="description"
                class="block
                       mb-2
                       text-sm
                       font-semibold
                       text-slate-700"
            >

                Description

            </label>


            <textarea
                id="description"
                name="description"
                rows="5"
                required
                placeholder="Présentez votre club, ses objectifs et ses activités..."
                class="w-full
                       px-4 py-3
                       rounded-xl
                       border
                       border-slate-300
                       outline-none
                       resize-y
                       focus:border-cyan-600
                       focus:ring-2
                       focus:ring-cyan-100"
            ></textarea>

        </div>



        <!-- =================================================
             MAX MEMBRES
        ================================================== -->

        <div class="mb-5">

            <label
                for="members_max"
                class="block
                       mb-2
                       text-sm
                       font-semibold
                       text-slate-700"
            >

                Nombre maximum de membres

            </label>


            <input
                type="number"
                id="members_max"
                name="members_max"
                min="2"
                max="10000"
                value="50"
                required
                class="w-full
                       px-4 py-3
                       rounded-xl
                       border
                       border-slate-300
                       outline-none
                       focus:border-cyan-600
                       focus:ring-2
                       focus:ring-cyan-100"
            >

        </div>



        <!-- =================================================
             LOGO
        ================================================== -->

        <div class="mb-6">

            <label
                for="logo"
                class="block
                       mb-2
                       text-sm
                       font-semibold
                       text-slate-700"
            >

                Logo du club
                <span
                    class="font-normal
                           text-slate-400"
                >
                    (optionnel)
                </span>

            </label>


            <input
                type="file"
                id="logo"
                name="logo"
                accept="image/jpeg,image/png,image/webp"
                class="w-full
                       px-4 py-3
                       rounded-xl
                       border
                       border-slate-300
                       bg-white
                       text-sm"
            >


            <p
                class="mt-2
                       text-xs
                       text-slate-400"
            >

                PNG, JPG ou WEBP — maximum 2 Mo.

            </p>

        </div>



        <!-- =================================================
             INFORMATION
        ================================================== -->

        <div
            class="mb-6
                   p-4
                   rounded-xl
                   bg-amber-50
                   border
                   border-amber-100"
        >

            <p
                class="text-sm
                       leading-6
                       text-amber-800"
            >

                Votre club sera créé avec le statut
                <strong>en attente</strong>.
                Un administrateur devra valider votre
                demande avant que le club soit visible.

            </p>

        </div>



        <!-- =================================================
             BOUTONS
        ================================================== -->

        <div
            class="flex
                   flex-col-reverse
                   sm:flex-row
                   gap-3"
        >

            <a
                href="../clubs.php"
                class="flex-1
                       px-5 py-3
                       rounded-xl
                       border
                       border-slate-300
                       text-center
                       text-sm
                       font-semibold
                       text-slate-700
                       hover:bg-slate-50"
            >

                Annuler

            </a>


            <button
                type="submit"
                class="flex-1
                       px-5 py-3
                       rounded-xl
                       bg-cyan-700
                       text-white
                       text-sm
                       font-semibold
                       hover:bg-cyan-800
                       transition"
            >

                Envoyer la demande

            </button>

        </div>

    </form>


</main>


</body>

</html>
