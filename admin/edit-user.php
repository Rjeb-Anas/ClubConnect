<?php

/*
|--------------------------------------------------------------------------
| ClubConnect
| admin/edit-user.php
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
| ID utilisateur
|--------------------------------------------------------------------------
*/

$userId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (
    !$userId ||
    $userId <= 0
) {

    $_SESSION['popup'] = [
        'type' => 'error',
        'message' => 'Utilisateur invalide.'
    ];

    header(
        "Location: dashboard.php?section=users"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Récupérer utilisateur
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $con,
    "
    SELECT
        id,
        nom,
        prenom,
        email,
        role
    FROM users
    WHERE id = ?
    LIMIT 1
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
    "i",
    $userId
);


if (!mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    die("Impossible de récupérer l'utilisateur.");
}


$result =
    mysqli_stmt_get_result($stmt);


$user =
    mysqli_fetch_assoc($result);


mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Utilisateur inexistant
|--------------------------------------------------------------------------
*/

if (!$user) {

    $_SESSION['popup'] = [
        'type' => 'error',
        'message' => 'Utilisateur introuvable.'
    ];

    header(
        "Location: dashboard.php?section=users"
    );

    exit;
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
       ClubConnect | Modifier utilisateur
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
           px-4
           py-10"
>


    <!-- Titre -->

    <div
        class="mb-6"
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

            ADMINISTRATION

        </span>


        <h1
            class="mt-3
                   text-3xl
                   font-bold
                   text-slate-900"
        >

            Modifier l'utilisateur

        </h1>


        <p
            class="mt-2
                   text-sm
                   text-slate-500"
        >

            Modifiez les informations du compte
            utilisateur.

        </p>

    </div>



    <!-- =====================================================
         FORMULAIRE
    ====================================================== -->

    <form
        action="traitement.php"
        method="POST"
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


        <!-- ID -->

        <input
            type="hidden"
            name="user_id"
            value="<?= (int)
                $user['id']
            ?>"
        >



        <!-- =================================================
             NOM
        ================================================== -->

        <div
            class="mb-5"
        >

            <label
                for="nom"
                class="block
                       mb-2
                       text-sm
                       font-semibold
                       text-slate-700"
            >

                Nom

            </label>


            <input
                type="text"
                id="nom"
                name="nom"
                value="<?= e(
                    $user['nom']
                ) ?>"
                required
                maxlength="100"
                class="w-full
                       px-4 py-3
                       rounded-xl
                       border
                       border-slate-300
                       bg-white
                       text-sm
                       text-slate-900
                       outline-none
                       transition
                       focus:border-cyan-600
                       focus:ring-2
                       focus:ring-cyan-100"
            >

        </div>



        <!-- =================================================
             PRENOM
        ================================================== -->

        <div
            class="mb-5"
        >

            <label
                for="prenom"
                class="block
                       mb-2
                       text-sm
                       font-semibold
                       text-slate-700"
            >

                Prénom

            </label>


            <input
                type="text"
                id="prenom"
                name="prenom"
                value="<?= e(
                    $user['prenom']
                ) ?>"
                required
                maxlength="100"
                class="w-full
                       px-4 py-3
                       rounded-xl
                       border
                       border-slate-300
                       bg-white
                       text-sm
                       text-slate-900
                       outline-none
                       transition
                       focus:border-cyan-600
                       focus:ring-2
                       focus:ring-cyan-100"
            >

        </div>



        <!-- =================================================
             EMAIL
        ================================================== -->

        <div
            class="mb-5"
        >

            <label
                for="email"
                class="block
                       mb-2
                       text-sm
                       font-semibold
                       text-slate-700"
            >

                Email

            </label>


            <input
                type="email"
                id="email"
                name="email"
                value="<?= e(
                    $user['email']
                ) ?>"
                required
                maxlength="255"
                class="w-full
                       px-4 py-3
                       rounded-xl
                       border
                       border-slate-300
                       bg-white
                       text-sm
                       text-slate-900
                       outline-none
                       transition
                       focus:border-cyan-600
                       focus:ring-2
                       focus:ring-cyan-100"
            >

        </div>



        <!-- =================================================
             ROLE
        ================================================== -->

        <div
            class="mb-6"
        >

            <label
                for="role"
                class="block
                       mb-2
                       text-sm
                       font-semibold
                       text-slate-700"
            >

                Rôle

            </label>


            <select
                id="role"
                name="role"
                required
                class="w-full
                       px-4 py-3
                       rounded-xl
                       border
                       border-slate-300
                       bg-white
                       text-sm
                       text-slate-900
                       outline-none
                       focus:border-cyan-600
                       focus:ring-2
                       focus:ring-cyan-100"
            >

                <option
                    value="user"
                    <?= $user['role'] === 'user'
                        ? 'selected'
                        : ''
                    ?>
                >

                    Utilisateur

                </option>


                <option
                    value="member"
                    <?= $user['role'] === 'member'
                        ? 'selected'
                        : ''
                    ?>
                >

                    Membre

                </option>


                <option
                    value="manager"
                    <?= $user['role'] === 'manager'
                        ? 'selected'
                        : ''
                    ?>
                >

                    Manager

                </option>


                <option
                    value="club_manager"
                    <?= $user['role'] === 'club_manager'
                        ? 'selected'
                        : ''
                    ?>
                >

                    Club Manager

                </option>


                <option
                    value="admin"
                    <?= $user['role'] === 'admin'
                        ? 'selected'
                        : ''
                    ?>
                >

                    Administrateur

                </option>

            </select>

        </div>



        <!-- =================================================
             INFORMATIONS
        ================================================== -->

        <div
            class="mb-6
                   p-4
                   rounded-xl
                   bg-slate-50
                   border
                   border-slate-200"
        >

            <div
                class="flex
                       items-center
                       justify-between"
            >

                <span
                    class="text-sm
                           text-slate-500"
                >

                    ID utilisateur

                </span>


                <span
                    class="text-sm
                           font-bold
                           text-slate-700"
                >

                    #<?= (int)
                        $user['id']
                    ?>

                </span>

            </div>

        </div>



        <!-- =================================================
             ACTIONS
        ================================================== -->

        <div
            class="flex
                   flex-col-reverse
                   sm:flex-row
                   gap-3"
        >

            <a
                href="dashboard.php?section=users"
                class="flex-1
                       px-5 py-3
                       rounded-xl
                       border
                       border-slate-300
                       text-center
                       text-sm
                       font-semibold
                       text-slate-700
                       hover:bg-slate-50
                       transition"
            >

                Annuler

            </a>


            <button
                type="submit"
                name="update_user"
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

                Enregistrer les modifications

            </button>

        </div>

    </form>


</main>


</body>

</html>

