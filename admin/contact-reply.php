<?php

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../config/database.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| PHPMailer
|--------------------------------------------------------------------------
*/

$autoload = __DIR__ . "/../vendor/autoload.php";

if (!file_exists($autoload)) {
    die("PHPMailer n'est pas installé.");
}

require_once $autoload;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


/*
|--------------------------------------------------------------------------
| Fonction sécurité
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
| ID du message
|--------------------------------------------------------------------------
*/

$messageId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$messageId || $messageId <= 0) {

    header("Location: contact-messages.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Récupérer le message
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
    die("Erreur SQL.");
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $messageId
);


if (!mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    die("Impossible de récupérer le message.");
}


$result = mysqli_stmt_get_result($stmt);

$messageData = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$messageData) {

    header("Location: contact-messages.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Destinataire
|--------------------------------------------------------------------------
*/

$recipientName = trim(
    ($messageData['prenom'] ?? '') .
    ' ' .
    ($messageData['nom'] ?? '')
);


if ($recipientName === '') {
    $recipientName = "Utilisateur";
}


$recipientEmail = trim(
    (string)($messageData['email'] ?? '')
);


$originalSubject = trim(
    (string)($messageData['subject'] ?? '')
);


/*
|--------------------------------------------------------------------------
| Variables
|--------------------------------------------------------------------------
*/

$error = '';

$success = '';

$reply = '';

$emailSubject =
    "Re: " . $originalSubject;


/*
|--------------------------------------------------------------------------
| ENVOYER
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $reply = trim(
        $_POST['reply'] ?? ''
    );

    $emailSubject = trim(
        $_POST['email_subject']
        ?? ("Re: " . $originalSubject)
    );


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($reply === '') {

        $error =
            "Veuillez écrire une réponse.";

    } elseif (mb_strlen($reply) < 2) {

        $error =
            "La réponse est trop courte.";

    } elseif (mb_strlen($reply) > 10000) {

        $error =
            "La réponse est trop longue.";

    } elseif (
        !$recipientEmail ||
        !filter_var(
            $recipientEmail,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "L'adresse email du destinataire est invalide.";

    } elseif ($emailSubject === '') {

        $error =
            "Veuillez saisir un sujet.";

    } else {


        /*
        |--------------------------------------------------------------------------
        | PHPMailer
        |--------------------------------------------------------------------------
        */

        $mail = new PHPMailer(true);


        try {

            /*
            |--------------------------------------------------------------------------
            | SMTP GMAIL
            |--------------------------------------------------------------------------
            |
            | REMPLACE ces deux valeurs.
            |
            */

            $mail->isSMTP();

            $mail->Host =
                'smtp.gmail.com';

            $mail->SMTPAuth =
                true;

            $mail->Username =
                'TON_EMAIL@gmail.com';

            $mail->Password =
                'TON_MOT_DE_PASSE_APPLICATION';

            $mail->SMTPSecure =
                PHPMailer::ENCRYPTION_STARTTLS;

            $mail->Port =
                587;


            /*
            |--------------------------------------------------------------------------
            | Encodage
            |--------------------------------------------------------------------------
            */

            $mail->CharSet =
                'UTF-8';


            /*
            |--------------------------------------------------------------------------
            | Expéditeur
            |--------------------------------------------------------------------------
            */

            $mail->setFrom(
                'TON_EMAIL@gmail.com',
                'ClubConnect'
            );


            /*
            |--------------------------------------------------------------------------
            | Destinataire
            |--------------------------------------------------------------------------
            */

            $mail->addAddress(
                $recipientEmail,
                $recipientName
            );


            /*
            |--------------------------------------------------------------------------
            | Reply-To
            |--------------------------------------------------------------------------
            */

            $mail->addReplyTo(
                'TON_EMAIL@gmail.com',
                'ClubConnect'
            );


            /*
            |--------------------------------------------------------------------------
            | Sujet
            |--------------------------------------------------------------------------
            */

            $mail->Subject =
                $emailSubject;


            /*
            |--------------------------------------------------------------------------
            | Version HTML
            |--------------------------------------------------------------------------
            */

            $mail->isHTML(true);


            $safeName =
                e($recipientName);

            $safeReply =
                nl2br(e($reply));


            $mail->Body = "

                <!DOCTYPE html>

                <html lang='fr'>

                <head>

                    <meta charset='UTF-8'>

                </head>

                <body
                    style='
                        margin:0;
                        padding:0;
                        background:#f8fafc;
                        font-family:Arial,Helvetica,sans-serif;
                        color:#0f172a;
                    '
                >

                    <div
                        style='
                            max-width:600px;
                            margin:30px auto;
                            padding:20px;
                        '
                    >

                        <div
                            style='
                                background:#0891b2;
                                color:white;
                                padding:20px;
                                border-radius:16px 16px 0 0;
                            '
                        >

                            <h2
                                style='
                                    margin:0;
                                    font-size:20px;
                                '
                            >
                                ClubConnect
                            </h2>

                            <p
                                style='
                                    margin:6px 0 0;
                                    opacity:.9;
                                    font-size:13px;
                                '
                            >
                                Réponse de l'administration
                            </p>

                        </div>


                        <div
                            style='
                                background:white;
                                padding:25px;
                                border:1px solid #e2e8f0;
                                border-top:0;
                                border-radius:0 0 16px 16px;
                            '
                        >

                            <p
                                style='
                                    font-size:15px;
                                    margin-top:0;
                                '
                            >
                                Bonjour {$safeName},
                            </p>


                            <div
                                style='
                                    margin-top:20px;
                                    padding:18px;
                                    background:#f8fafc;
                                    border:1px solid #e2e8f0;
                                    border-radius:12px;
                                    font-size:14px;
                                    line-height:1.7;
                                '
                            >
                                {$safeReply}
                            </div>


                            <div
                                style='
                                    margin-top:25px;
                                    padding-top:15px;
                                    border-top:1px solid #e2e8f0;
                                    color:#64748b;
                                    font-size:12px;
                                '
                            >
                                <strong>ClubConnect</strong><br>
                                FST Fès
                            </div>

                        </div>

                    </div>

                </body>

                </html>
            ";


            /*
            |--------------------------------------------------------------------------
            | Version texte
            |--------------------------------------------------------------------------
            */

            $mail->AltBody =
                "Bonjour " .
                $recipientName .
                ",\n\n" .
                $reply .
                "\n\n" .
                "ClubConnect\n" .
                "FST Fès";


            /*
            |--------------------------------------------------------------------------
            | ENVOI
            |--------------------------------------------------------------------------
            */

            $mail->send();


            $success =
                "Email envoyé avec succès à " .
                $recipientEmail;


            $reply = '';


        } catch (Exception $ex) {

            $error =
                "Erreur lors de l'envoi : " .
                $mail->ErrorInfo;
        }
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

    <meta
        name="theme-color"
        content="#0891b2"
    >

    <title>
        Répondre par email | ClubConnect
    </title>


    <!-- Tailwind -->

    <script
        src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"
    ></script>

</head>


<body
    class="
        min-h-screen
        bg-slate-50
        text-slate-900
    "
>


<div
    class="
        min-h-screen
        px-3
        py-5
        sm:px-6
        sm:py-8
    "
>


    <div
        class="
            mx-auto
            w-full
            max-w-4xl
        "
    >


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


            <a
                href="contact-messages.php"
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


            <a
                href="contact-messages.php"
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
                "
            >
                ← Messages
            </a>


        </header>



        <!-- =====================================================
             TITLE
        ====================================================== -->

        <div class="mb-5">


            <span
                class="
                    inline-flex
                    rounded-full
                    bg-emerald-50
                    px-2.5
                    py-1
                    text-[10px]
                    font-bold
                    uppercase
                    tracking-wider
                    text-emerald-700
                "
            >
                Email
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
                Répondre par email
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
                Envoyez directement une réponse à l'utilisateur.
            </p>


        </div>



        <!-- =====================================================
             ERROR
        ====================================================== -->

        <?php if ($error !== ''): ?>

            <div
                class="
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
                "
            >

                <?= e($error) ?>

            </div>

        <?php endif; ?>



        <!-- =====================================================
             SUCCESS
        ====================================================== -->

        <?php if ($success !== ''): ?>

            <div
                class="
                    mb-4
                    rounded-xl
                    border
                    border-emerald-200
                    bg-emerald-50
                    px-4
                    py-3
                    text-xs
                    leading-5
                    text-emerald-700
                "
            >

                <?= e($success) ?>

            </div>

        <?php endif; ?>



        <!-- =====================================================
             CARD
        ====================================================== -->

        <div
            class="
                overflow-hidden
                rounded-2xl
                border
                border-slate-200
                bg-white
                shadow-sm
            "
        >


            <!-- =================================================
                 DESTINATAIRE
            ================================================== -->

            <div
                class="
                    flex
                    items-center
                    gap-3
                    border-b
                    border-slate-100
                    px-4
                    py-4
                    sm:px-5
                "
            >


                <!-- AVATAR -->

                <div
                    class="
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
                    "
                >

                    <?= e(
                        mb_strtoupper(
                            mb_substr(
                                $recipientName,
                                0,
                                1,
                                'UTF-8'
                            ),
                            'UTF-8'
                        )
                    ) ?>

                </div>


                <div class="min-w-0">


                    <div
                        class="
                            truncate
                            text-sm
                            font-bold
                            text-slate-900
                        "
                    >

                        <?= e(
                            $recipientName
                        ) ?>

                    </div>


                    <div
                        class="
                            truncate
                            text-xs
                            text-cyan-600
                        "
                    >

                        <?= e(
                            $recipientEmail
                        ) ?>

                    </div>


                </div>


            </div>



            <!-- =================================================
                 ORIGINAL MESSAGE
            ================================================== -->

            <div
                class="
                    border-b
                    border-slate-100
                    px-4
                    py-4
                    sm:px-5
                "
            >


                <div
                    class="
                        mb-1
                        text-[9px]
                        font-bold
                        uppercase
                        tracking-wider
                        text-slate-400
                    "
                >
                    Message original
                </div>


                <h2
                    class="
                        text-sm
                        font-bold
                        leading-5
                        text-slate-800
                    "
                >

                    <?= e(
                        $originalSubject
                    ) ?>

                </h2>


                <div
                    class="
                        mt-2
                        max-h-36
                        overflow-y-auto
                        whitespace-pre-line
                        break-words
                        rounded-xl
                        bg-slate-50
                        px-3
                        py-3
                        text-xs
                        leading-5
                        text-slate-500
                    "
                >

                    <?= e(
                        $messageData['message']
                    ) ?>

                </div>


            </div>



            <!-- =================================================
                 FORM
            ================================================== -->

            <form
                method="POST"
                action=""
                class="px-4 py-5 sm:px-5"
            >


                <!-- SUJET -->

                <div class="mb-4">


                    <label
                        for="email_subject"
                        class="
                            mb-1.5
                            block
                            text-xs
                            font-bold
                            text-slate-700
                        "
                    >
                        Sujet
                    </label>


                    <input
                        type="text"
                        id="email_subject"
                        name="email_subject"
                        value="<?= e(
                            $emailSubject
                        ) ?>"
                        maxlength="255"
                        required
                        class="
                            w-full
                            rounded-xl
                            border
                            border-slate-300
                            bg-white
                            px-3
                            py-2.5
                            text-sm
                            text-slate-800
                            outline-none
                            transition
                            focus:border-cyan-600
                            focus:ring-2
                            focus:ring-cyan-100
                        "
                    >


                    <p
                        class="
                            mt-1
                            text-[10px]
                            text-slate-400
                        "
                    >
                        Le sujet « Re: ... » est généré automatiquement.
                    </p>


                </div>



                <!-- RÉPONSE -->

                <div>


                    <label
                        for="reply"
                        class="
                            mb-1.5
                            block
                            text-xs
                            font-bold
                            text-slate-700
                        "
                    >
                        Votre réponse
                    </label>


                    <textarea
                        id="reply"
                        name="reply"
                        rows="9"
                        maxlength="10000"
                        required
                        placeholder="Écrivez votre réponse..."
                        class="
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
                            focus:border-cyan-600
                            focus:ring-2
                            focus:ring-cyan-100
                        "
                    ><?= e($reply) ?></textarea>


                    <div
                        class="
                            mt-1
                            flex
                            justify-between
                            text-[10px]
                            text-slate-400
                        "
                    >

                        <span>
                            Maximum 10 000 caractères
                        </span>

                        <span>
                            Email HTML
                        </span>

                    </div>


                </div>



                <!-- =================================================
                     BUTTONS
                ================================================== -->

                <div
                    class="
                        mt-5
                        flex
                        flex-col-reverse
                        gap-2
                        sm:flex-row
                    "
                >


                    <a
                        href="contact-messages.php"
                        class="
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
                        "
                    >
                        Annuler
                    </a>


                    <button
                        type="submit"
                        class="
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
                        "
                    >
                        Envoyer l'email
                    </button>


                </div>


            </form>


        </div>


    </div>

</div>


</body>

</html>