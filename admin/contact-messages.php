<?php

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../config/database.php";

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
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| Récupérer les messages
|--------------------------------------------------------------------------
*/

$messages = [];
$error = '';

$sql = "
    SELECT
        id,
        user_id,
        nom,
        prenom,
        email,
        subject,
        message
    FROM contact_messages
    ORDER BY id DESC
";

$stmt = mysqli_prepare($con, $sql);

if (!$stmt) {

    $error = "Impossible de préparer la requête.";

} elseif (!mysqli_stmt_execute($stmt)) {

    $error = "Impossible de récupérer les messages.";

} else {

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        while ($row = mysqli_fetch_assoc($result)) {
            $messages[] = $row;
        }

    } else {

        $error = "Impossible de lire les messages.";
    }
}

if ($stmt) {
    mysqli_stmt_close($stmt);
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

    <meta
        name="theme-color"
        content="#0891b2"
    >

    <title>
        Messages de contact | ClubConnect
    </title>


    <!-- Tailwind -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

</head>


<body class="min-h-screen bg-slate-50 text-slate-900">


<!-- =========================================================
     PAGE
========================================================= -->

<div class="min-h-screen px-3 py-5 sm:px-6 lg:px-8">


    <div class="mx-auto w-full max-w-6xl">


        <!-- =====================================================
             HEADER
        ====================================================== -->

        <header
            class="
                mb-6
                flex
                items-center
                justify-between
                gap-4
            "
        >


            <!-- LOGO -->

            <a
                href="dashboard.php"
                class="
                    flex
                    items-center
                    gap-3
                    no-underline
                "
            >

                <div
                    class="
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
                    "
                >
                    C
                </div>


                <div class="hidden sm:block">

                    <div
                        class="
                            text-sm
                            font-bold
                            text-slate-900
                        "
                    >
                        ClubConnect
                    </div>


                    <div
                        class="
                            text-[10px]
                            text-slate-400
                        "
                    >
                        Administration
                    </div>

                </div>

            </a>


            <!-- DASHBOARD -->

            <a
                href="dashboard.php"
                class="
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
                "
            >
                ← Dashboard
            </a>

        </header>



        <!-- =====================================================
             TITLE
        ====================================================== -->

        <section
            class="
                mb-6
                flex
                flex-col
                gap-4
                sm:flex-row
                sm:items-end
                sm:justify-between
            "
        >


            <div>

                <span
                    class="
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
                    "
                >
                    Contact
                </span>


                <h1
                    class="
                        mt-2
                        text-2xl
                        font-bold
                        tracking-tight
                        text-slate-900
                        sm:text-3xl
                    "
                >
                    Messages reçus
                </h1>


                <p
                    class="
                        mt-1
                        text-xs
                        leading-5
                        text-slate-500
                        sm:text-sm
                    "
                >
                    Gérez les messages envoyés par les utilisateurs.
                </p>

            </div>


            <!-- COMPTEUR -->

            <div
                class="
                    flex
                    w-fit
                    items-center
                    gap-2.5
                    rounded-xl
                    border
                    border-slate-200
                    bg-white
                    px-3
                    py-2
                    shadow-sm
                "
            >

                <div
                    class="
                        flex
                        h-8
                        w-8
                        items-center
                        justify-center
                        rounded-lg
                        bg-cyan-50
                        text-xs
                        font-bold
                        text-cyan-700
                    "
                >
                    <?= count($messages) ?>
                </div>


                <span
                    class="
                        text-xs
                        font-medium
                        text-slate-500
                    "
                >
                    message<?= count($messages) !== 1 ? 's' : '' ?>
                </span>

            </div>

        </section>



        <!-- =====================================================
             ERREUR
        ====================================================== -->

        <?php if ($error !== ''): ?>

            <div
                class="
                    mb-5
                    rounded-xl
                    border
                    border-red-200
                    bg-red-50
                    px-4
                    py-3
                    text-xs
                    text-red-700
                "
            >

                <?= e($error) ?>

            </div>

        <?php endif; ?>



        <!-- =====================================================
             AUCUN MESSAGE
        ====================================================== -->

        <?php if (
            empty($messages) &&
            $error === ''
        ): ?>


            <div
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    px-5
                    py-14
                    text-center
                    shadow-sm
                "
            >

                <div
                    class="
                        mx-auto
                        flex
                        h-12
                        w-12
                        items-center
                        justify-center
                        rounded-xl
                        bg-slate-100
                        text-sm
                        font-bold
                        text-slate-500
                    "
                >
                    M
                </div>


                <h2
                    class="
                        mt-4
                        text-lg
                        font-bold
                        text-slate-900
                    "
                >
                    Aucun message
                </h2>


                <p
                    class="
                        mx-auto
                        mt-1
                        max-w-sm
                        text-xs
                        leading-5
                        text-slate-500
                    "
                >
                    Vous n'avez reçu aucun message pour le moment.
                </p>

            </div>


        <?php elseif (!empty($messages)): ?>


            <!-- =================================================
                 LISTE
            ================================================== -->

            <div class="space-y-3">


                <?php foreach ($messages as $msg): ?>


                    <?php

                    $fullName = trim(
                        ($msg['prenom'] ?? '') .
                        ' ' .
                        ($msg['nom'] ?? '')
                    );


                    if ($fullName === '') {
                        $fullName = 'Utilisateur';
                    }


                    $initial = mb_strtoupper(
                        mb_substr(
                            $fullName,
                            0,
                            1,
                            'UTF-8'
                        ),
                        'UTF-8'
                    );


                    $email = trim(
                        (string)(
                            $msg['email'] ?? ''
                        )
                    );


                    $subject = trim(
                        (string)(
                            $msg['subject'] ?? ''
                        )
                    );

                    ?>


                    <!-- =================================================
                         MESSAGE CARD
                    ================================================== -->

                    <article
                        class="
                            overflow-hidden
                            rounded-2xl
                            border
                            border-slate-200
                            bg-white
                            shadow-sm
                            transition
                            hover:border-slate-300
                            hover:shadow-md
                        "
                    >


                        <!-- =============================================
                             TOP
                        ============================================== -->

                        <div
                            class="
                                flex
                                flex-col
                                gap-3
                                border-b
                                border-slate-100
                                px-4
                                py-3
                                sm:flex-row
                                sm:items-center
                                sm:justify-between
                            "
                        >


                            <!-- UTILISATEUR -->

                            <div
                                class="
                                    flex
                                    min-w-0
                                    items-center
                                    gap-3
                                "
                            >

                                <!-- AVATAR -->

                                <div
                                    class="
                                        flex
                                        h-9
                                        w-9
                                        shrink-0
                                        items-center
                                        justify-center
                                        rounded-xl
                                        bg-cyan-50
                                        text-xs
                                        font-bold
                                        text-cyan-700
                                    "
                                >

                                    <?= e($initial) ?>

                                </div>


                                <!-- INFOS -->

                                <div class="min-w-0">

                                    <div
                                        class="
                                            truncate
                                            text-xs
                                            font-bold
                                            text-slate-900
                                        "
                                    >

                                        <?= e($fullName) ?>

                                    </div>


                                    <a
                                        href="mailto:<?= e($email) ?>"
                                        class="
                                            block
                                            max-w-[260px]
                                            truncate
                                            text-[10px]
                                            text-cyan-600
                                            hover:underline
                                            sm:max-w-[400px]
                                        "
                                    >

                                        <?= e($email) ?>

                                    </a>

                                </div>

                            </div>


                            <!-- ID -->

                            <span
                                class="
                                    w-fit
                                    rounded-lg
                                    bg-slate-100
                                    px-2
                                    py-1
                                    text-[9px]
                                    font-bold
                                    text-slate-500
                                "
                            >
                                #<?= (int)$msg['id'] ?>
                            </span>


                        </div>



                        <!-- =============================================
                             CONTENT
                        ============================================== -->

                        <div class="px-4 py-3">


                            <!-- SUJET -->

                            <h2
                                class="
                                    text-sm
                                    font-bold
                                    leading-5
                                    text-slate-800
                                "
                            >

                                <?= e($subject) ?>

                            </h2>


                            <!-- MESSAGE -->

                            <p
                                class="
                                    mt-1.5
                                    line-clamp-2
                                    whitespace-pre-line
                                    break-words
                                    text-xs
                                    leading-5
                                    text-slate-500
                                "
                            >

                                <?= e(
                                    $msg['message']
                                ) ?>

                            </p>



                            <!-- =========================================
                                 FOOTER
                            ========================================== -->

                            <div
                                class="
                                    mt-3
                                    flex
                                    flex-col
                                    gap-3
                                    border-t
                                    border-slate-100
                                    pt-3
                                    sm:flex-row
                                    sm:items-center
                                    sm:justify-between
                                "
                            >


                                <!-- USER ID -->

                                <span
                                    class="
                                        text-[9px]
                                        text-slate-400
                                    "
                                >

                                    Utilisateur
                                    #<?= (int)$msg['user_id'] ?>

                                </span>


                                <!-- BUTTONS -->

                                <div
                                    class="
                                        grid
                                        w-full
                                        grid-cols-1
                                        gap-2
                                        sm:w-auto
                                        sm:grid-cols-3
                                    "
                                >


                                    <!-- =================================
                                         RÉPONDRE SITE
                                    ================================== -->

                                    <a
                                        href="
                                            reply-message.php?id=<?= (int)$msg['id'] ?>
                                        "
                                        class="
                                            inline-flex
                                            min-h-9
                                            items-center
                                            justify-center
                                            rounded-lg
                                            bg-cyan-600
                                            px-3
                                            py-2
                                            text-[10px]
                                            font-bold
                                            text-white
                                            transition
                                            hover:bg-cyan-700
                                        "
                                    >

                                        Répondre sur le site

                                    </a>


                                    <!-- =================================
                                         RÉPONDRE EMAIL
                                    ================================== -->

                                    <a
                                        href="
                                            contact-reply.php?id=<?= (int)$msg['id'] ?>
                                        "
                                        class="
                                            inline-flex
                                            min-h-9
                                            items-center
                                            justify-center
                                            rounded-lg
                                            border
                                            border-emerald-200
                                            bg-emerald-50
                                            px-3
                                            py-2
                                            text-[10px]
                                            font-bold
                                            text-emerald-700
                                            transition
                                            hover:bg-emerald-100
                                        "
                                    >

                                        Répondre par email

                                    </a>


                                    <!-- =================================
                                         SUPPRIMER
                                    ================================== -->

                                    <form
                                        method="POST"
                                        action="delete-contact-message.php"
                                        class="m-0"
                                        onsubmit="
                                            return confirm(
                                                'Voulez-vous vraiment supprimer ce message ?'
                                            );
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$msg['id'] ?>"
                                        >


                                        <button
                                            type="submit"
                                            class="
                                                inline-flex
                                                min-h-9
                                                w-full
                                                items-center
                                                justify-center
                                                rounded-lg
                                                border
                                                border-red-200
                                                bg-red-50
                                                px-3
                                                py-2
                                                text-[10px]
                                                font-bold
                                                text-red-600
                                                transition
                                                hover:bg-red-100
                                            "
                                        >

                                            Supprimer

                                        </button>

                                    </form>


                                </div>


                            </div>


                        </div>


                    </article>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </div>

</div>


</body>

</html>