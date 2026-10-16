<?php

/*
|--------------------------------------------------------------------------
| ClubConnect - reply-message.php
|--------------------------------------------------------------------------
| Réponse interne à un message de contact.
| La réponse est envoyée dans les notifications de l'utilisateur.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| AUTH + DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../config/database.php";


/*
|--------------------------------------------------------------------------
| SÉCURITÉ HTML
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
| ID DU MESSAGE
|--------------------------------------------------------------------------
*/

$messageId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (
    !$messageId ||
    $messageId <= 0
) {

    header(
        "Location: contact-messages.php"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| RÉCUPÉRER LE MESSAGE
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $con,
    "
    SELECT
        id,
        user_id,
        nom,
        prenom,
        email,
        subject,
        message
    FROM contact_messages
    WHERE id = ?
    LIMIT 1
    "
);


if (!$stmt) {

    die(
        "Erreur SQL lors de la récupération du message."
    );
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $messageId
);


if (!mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    die(
        "Impossible de récupérer le message."
    );
}


$result =
    mysqli_stmt_get_result($stmt);


$messageData =
    mysqli_fetch_assoc($result);


mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| MESSAGE INTROUVABLE
|--------------------------------------------------------------------------
*/

if (!$messageData) {

    header(
        "Location: contact-messages.php"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| INFORMATIONS UTILISATEUR
|--------------------------------------------------------------------------
*/

$userId = (int) (
    $messageData['user_id'] ?? 0
);


$fullName = trim(
    ($messageData['prenom'] ?? '') .
    ' ' .
    ($messageData['nom'] ?? '')
);


if ($fullName === '') {

    $fullName =
        'Utilisateur';
}


$email = trim(
    (string) (
        $messageData['email'] ?? ''
    )
);


$subject = trim(
    (string) (
        $messageData['subject'] ?? ''
    )
);


$originalMessage = trim(
    (string) (
        $messageData['message'] ?? ''
    )
);


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$error = '';

$success = '';

$reply = '';


/*
|--------------------------------------------------------------------------
| TRAITER LA RÉPONSE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /*
    |--------------------------------------------------------------------------
    | RÉCUPÉRER LA RÉPONSE
    |--------------------------------------------------------------------------
    */

    $reply = trim(
        $_POST['reply'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($userId <= 0) {

        $error =
            "Utilisateur invalide.";

    } elseif ($reply === '') {

        $error =
            "Veuillez écrire une réponse.";

    } elseif (mb_strlen($reply) < 2) {

        $error =
            "La réponse est trop courte.";

    } elseif (mb_strlen($reply) > 5000) {

        $error =
            "La réponse ne peut pas dépasser 5000 caractères.";

    } else {


        /*
        |--------------------------------------------------------------------------
        | TITRE DE LA NOTIFICATION
        |--------------------------------------------------------------------------
        */

        $notificationTitle =
            "Réponse à votre message";


        /*
        |--------------------------------------------------------------------------
        | TEXTE DE LA NOTIFICATION
        |--------------------------------------------------------------------------
        */

        $notificationMessage =
            "Concernant : " .
            $subject .
            "\n\n" .
            $reply;


        /*
        |--------------------------------------------------------------------------
        | INSÉRER LA NOTIFICATION
        |--------------------------------------------------------------------------
        |
        | Cette version suppose une table :
        |
        | notifications
        |
        | avec :
        |
        | id
        | user_id
        | title
        | message
        | is_read
        | created_at
        |
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $con,
            "
            INSERT INTO notifications
            (
                user_id,
                title,
                message,
                is_read,
                created_at
            )
            VALUES
            (
                ?,
                ?,
                ?,
                0,
                NOW()
            )
            "
        );


        if (!$stmt) {

            $error =
                "Impossible de préparer la notification.";

        } else {


            mysqli_stmt_bind_param(
                $stmt,
                "iss",
                $userId,
                $notificationTitle,
                $notificationMessage
            );


            if (
                mysqli_stmt_execute($stmt)
            ) {

                $success =
                    "La réponse a été envoyée dans les notifications de " .
                    $fullName .
                    ".";

                $reply = '';

            } else {

                $error =
                    "Impossible d'envoyer la notification.";
            }


            mysqli_stmt_close($stmt);
        }
    }
}

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="theme-color" content="#0891b2">

    <title>
        Répondre | ClubConnect
    </title>


    <!-- Tailwind CSS -->

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

</head>


<body class="
        min-h-screen
        bg-slate-50
        text-slate-900
    ">


    <div class="
        min-h-screen
        px-3
        py-5
        sm:px-6
        sm:py-8
    ">


        <div class="
            mx-auto
            w-full
            max-w-4xl
        ">


            <!-- =====================================================
             HEADER
        ====================================================== -->

            <header class="
                mb-6
                flex
                items-center
                justify-between
                gap-4
            ">


                <!-- LOGO -->

                <a href="contact-messages.php" class="
                    flex
                    items-center
                    gap-3
                    no-underline
                ">


                    <div class="
                        flex
                        h-10
                        w-10
                        shrink-0
                        items-center
                        justify-center
                        rounded-xl
                        bg-cyan-600
                        text-sm
                        font-bold
                        text-white
                        shadow-sm
                    ">
                        C
                    </div>


                    <div class="hidden sm:block">


                        <div class="
                            text-sm
                            font-bold
                            text-slate-900
                        ">
                            ClubConnect
                        </div>


                        <div class="
                            text-[10px]
                            text-slate-400
                        ">
                            Administration
                        </div>


                    </div>


                </a>


                <!-- RETOUR -->

                <a href="contact-messages.php" class="
                    inline-flex
                    items-center
                    justify-center
                    rounded-xl
                    border
                    border-slate-200
                    bg-white
                    px-3
                    py-2
                    text-xs
                    font-semibold
                    text-slate-600
                    shadow-sm
                    transition
                    hover:bg-slate-50
                    hover:text-slate-900
                ">
                    ← Messages
                </a>


            </header>



            <!-- =====================================================
             TITLE
        ====================================================== -->

            <section class="mb-5">


                <span class="
                    inline-flex
                    rounded-full
                    bg-cyan-50
                    px-2.5
                    py-1
                    text-[10px]
                    font-bold
                    uppercase
                    tracking-wider
                    text-cyan-700
                ">
                    Notification
                </span>


                <h1 class="
                    mt-2
                    text-2xl
                    font-bold
                    tracking-tight
                    text-slate-900
                    sm:text-3xl
                ">
                    Répondre sur ClubConnect
                </h1>


                <p class="
                    mt-1
                    text-xs
                    leading-5
                    text-slate-500
                    sm:text-sm
                ">
                    La réponse sera envoyée dans les notifications
                    de l'utilisateur.
                </p>


            </section>



            <!-- =====================================================
             ERREUR
        ====================================================== -->

            <?php if ($error !== ''): ?>


                <div class="
                    mb-4
                    rounded-xl
                    border
                    border-red-200
                    bg-red-50
                    px-4
                    py-3
                    text-xs
                    leading-5
                    text-red-700
                ">

                    <?= e($error) ?>

                </div>


            <?php endif; ?>



            <!-- =====================================================
             SUCCÈS
        ====================================================== -->

            <?php if ($success !== ''): ?>


                <div class="
                    mb-4
                    flex
                    flex-col
                    gap-3
                    rounded-xl
                    border
                    border-emerald-200
                    bg-emerald-50
                    px-4
                    py-3
                    sm:flex-row
                    sm:items-center
                    sm:justify-between
                ">


                    <span class="
                        text-xs
                        leading-5
                        text-emerald-700
                    ">

                        <?= e($success) ?>

                    </span>


                    <a href="contact-messages.php" class="
                        inline-flex
                        w-fit
                        items-center
                        justify-center
                        rounded-lg
                        bg-emerald-600
                        px-3
                        py-2
                        text-[10px]
                        font-bold
                        text-white
                        transition
                        hover:bg-emerald-700
                    ">
                        Retour aux messages
                    </a>


                </div>


            <?php endif; ?>



            <!-- =====================================================
             CARD
        ====================================================== -->

            <section class="
                overflow-hidden
                rounded-2xl
                border
                border-slate-200
                bg-white
                shadow-sm
            ">


                <!-- =================================================
                 UTILISATEUR
            ================================================== -->

                <div class="
                    flex
                    items-center
                    justify-between
                    gap-4
                    border-b
                    border-slate-100
                    px-4
                    py-4
                    sm:px-5
                ">


                    <div class="
                        flex
                        min-w-0
                        items-center
                        gap-3
                    ">


                        <!-- AVATAR -->

                        <div class="
                            flex
                            h-10
                            w-10
                            shrink-0
                            items-center
                            justify-center
                            rounded-xl
                            bg-cyan-50
                            text-xs
                            font-bold
                            text-cyan-700
                        ">

                            <?= e(
                                mb_strtoupper(
                                    mb_substr(
                                        $fullName,
                                        0,
                                        1,
                                        'UTF-8'
                                    ),
                                    'UTF-8'
                                )
                            ) ?>

                        </div>


                        <!-- INFOS -->

                        <div class="min-w-0">


                            <div class="
                                truncate
                                text-sm
                                font-bold
                                text-slate-900
                            ">

                                <?= e($fullName) ?>

                            </div>


                            <div class="
                                truncate
                                text-[10px]
                                text-slate-400
                            ">

                                Utilisateur #<?= $userId ?>

                            </div>


                            <?php if ($email !== ''): ?>

                                <div class="
                                    mt-0.5
                                    truncate
                                    text-[10px]
                                    text-cyan-600
                                ">

                                    <?= e($email) ?>

                                </div>

                            <?php endif; ?>


                        </div>


                    </div>


                    <!-- ID MESSAGE -->

                    <span class="
                        shrink-0
                        rounded-lg
                        bg-slate-100
                        px-2
                        py-1
                        text-[9px]
                        font-bold
                        text-slate-500
                    ">

                        #<?= (int) $messageData['id'] ?>

                    </span>


                </div>



                <!-- =================================================
                 MESSAGE ORIGINAL
            ================================================== -->

                <div class="
                    border-b
                    border-slate-100
                    px-4
                    py-4
                    sm:px-5
                ">


                    <div class="
                        text-[9px]
                        font-bold
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                        Sujet
                    </div>


                    <h2 class="
                        mt-1
                        text-sm
                        font-bold
                        leading-5
                        text-slate-800
                    ">

                        <?= e($subject) ?>

                    </h2>


                    <div class="
                        mt-3
                        max-h-44
                        overflow-y-auto
                        whitespace-pre-line
                        wrap-break-word
                        rounded-xl
                        border
                        border-slate-100
                        bg-slate-50
                        px-3
                        py-3
                        text-xs
                        leading-2
                        text-slate-500
                    ">

                        <?= e($originalMessage) ?>

                    </div>


                </div>



                <!-- =================================================
                 FORMULAIRE
            ================================================== -->

                <form method="POST" action="reply-message.php?id=<?= (int) $messageId ?>" class="
                    px-4
                    py-5
                    sm:px-5
                ">


                    <div>


                        <div class="
                            mb-2
                            flex
                            items-center
                            justify-between
                            gap-3
                        ">


                            <label for="reply" class="
                                text-xs
                                font-bold
                                text-slate-700
                            ">
                                Votre réponse
                            </label>


                            <span class="
                                text-[9px]
                                text-slate-400
                            ">
                                Notification ClubConnect
                            </span>


                        </div>


                        <textarea id="reply" name="reply" rows="8" minlength="2" maxlength="5000" required
                            placeholder="Écrivez votre réponse..." class="
                            w-full
                            resize-y
                            rounded-xl
                            border
                            border-slate-300
                            bg-white
                            px-3
                            py-3
                            text-sm
                            leading-6
                            text-slate-800
                            outline-none
                            transition
                            placeholder:text-slate-400
                            focus:border-cyan-600
                            focus:ring-2
                            focus:ring-cyan-100
                        "><?= e($reply) ?></textarea>


                        <div class="
                            mt-1
                            flex
                            items-center
                            justify-between
                            gap-3
                            text-[10px]
                            text-slate-400
                        ">

                            <span>
                                2 caractères minimum
                            </span>


                            <span id="characterCount">
                                <?= mb_strlen($reply) ?> / 5000
                            </span>


                        </div>


                    </div>



                    <!-- =================================================
                     APERÇU NOTIFICATION
                ================================================== -->

                    <div class="
                        mt-5
                        rounded-xl
                        border
                        border-cyan-100
                        bg-cyan-50/50
                        p-3
                    ">


                        <div class="
                            flex
                            items-start
                            gap-3
                        ">


                            <div class="
                                flex
                                h-8
                                w-8
                                shrink-0
                                items-center
                                justify-center
                                rounded-lg
                                bg-cyan-100
                                text-[10px]
                                font-bold
                                text-cyan-700
                            ">
                                C
                            </div>


                            <div>


                                <div class="
                                    text-[11px]
                                    font-bold
                                    text-slate-800
                                ">
                                    Réponse à votre message
                                </div>


                                <div class="
                                    mt-1
                                    text-[10px]
                                    leading-4
                                    text-slate-500
                                ">

                                    Concernant :
                                    <?= e($subject) ?>

                                </div>


                            </div>


                        </div>


                    </div>



                    <!-- =================================================
                     ACTIONS
                ================================================== -->

                    <div class="
                        mt-5
                        flex
                        flex-col-reverse
                        gap-2
                        sm:flex-row
                        sm:items-center
                    ">


                        <!-- ANNULER -->

                        <a href="contact-messages.php" class="
                            inline-flex
                            min-h-10
                            items-center
                            justify-center
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            px-5
                            py-2.5
                            text-xs
                            font-bold
                            text-slate-600
                            transition
                            hover:bg-slate-50
                        ">
                            Annuler
                        </a>


                        <!-- ENVOYER -->

                        <button type="submit" class="
                            inline-flex
                            min-h-10
                            items-center
                            justify-center
                            rounded-xl
                            bg-cyan-600
                            px-5
                            py-2.5
                            text-xs
                            font-bold
                            text-white
                            shadow-sm
                            transition
                            hover:bg-cyan-700
                            focus:outline-none
                            focus:ring-2
                            focus:ring-cyan-200
                        ">
                            Envoyer la réponse
                        </button>


                    </div>


                </form>


            </section>


        </div>


    </div>



    <!-- =========================================================
     COMPTEUR CARACTÈRES
========================================================= -->

    <script>

        const replyInput =
            document.getElementById('reply');

        const characterCount =
            document.getElementById('characterCount');


        if (
            replyInput &&
            characterCount
        ) {

            function updateCharacterCount() {

                characterCount.textContent =
                    replyInput.value.length +
                    ' / 5000';

            }


            replyInput.addEventListener(
                'input',
                updateCharacterCount
            );


            updateCharacterCount();

        }

    </script>


</body>

</html>