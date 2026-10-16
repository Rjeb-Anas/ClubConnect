<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . "/config/database.php";

if (!isset($con) || !($con instanceof mysqli)) {
    die("Erreur de connexion à la base de données.");
}

mysqli_set_charset($con, "utf8mb4");


/*
|--------------------------------------------------------------------------
| Fonctions
|--------------------------------------------------------------------------
*/

function e($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}


function fullName(array $user): string
{
    $prenom = trim(
        (string)($user["prenom"] ?? "")
    );

    $nom = trim(
        (string)($user["nom"] ?? "")
    );

    $name = trim(
        $prenom . " " . $nom
    );

    return $name !== ""
        ? $name
        : "Utilisateur";
}


function initials(string $name): string
{
    $name = trim($name);

    if ($name === "") {
        return "U";
    }

    return mb_strtoupper(
        mb_substr(
            $name,
            0,
            1,
            "UTF-8"
        ),
        "UTF-8"
    );
}


function errorPage(
    string $title,
    string $message,
    int $code = 400
): never {

    http_response_code($code);

    ?>
    <!DOCTYPE html>

    <html lang="fr">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title><?= e($title) ?></title>

        <link
            rel="stylesheet"
            href="assets/css/style.css"
        >

    </head>

    <body
        class="
            min-h-screen
            bg-slate-100
            flex
            items-center
            justify-center
            p-5
        "
    >

        <div
            class="
                w-full
                max-w-md
                rounded-3xl
                border
                border-slate-200
                bg-white
                p-8
                text-center
                shadow-xl
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
                    bg-red-50
                    text-xl
                    font-black
                    text-red-600
                "
            >
                <?= $code ?>
            </div>

            <h1
                class="
                    mt-5
                    text-2xl
                    font-black
                    text-slate-900
                "
            >
                <?= e($title) ?>
            </h1>

            <p
                class="
                    mt-3
                    text-sm
                    leading-6
                    text-slate-500
                "
            >
                <?= e($message) ?>
            </p>

            <a
                href="clubs.php"
                class="
                    mt-6
                    inline-flex
                    rounded-xl
                    bg-cyan-700
                    px-5
                    py-3
                    text-sm
                    font-bold
                    text-white
                    hover:bg-cyan-800
                "
            >
                Retour aux clubs
            </a>

        </div>

    </body>

    </html>
    <?php

    exit;
}


/*
|--------------------------------------------------------------------------
| Utilisateur connecté
|--------------------------------------------------------------------------
*/

$userId = 0;

if (
    isset($_SESSION["id"]) &&
    is_numeric($_SESSION["id"])
) {
    $userId = (int)$_SESSION["id"];
}

if (
    $userId <= 0 &&
    isset($_SESSION["user_id"]) &&
    is_numeric($_SESSION["user_id"])
) {
    $userId = (int)$_SESSION["user_id"];
}

if ($userId <= 0) {
    header("Location: login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Club ID
|--------------------------------------------------------------------------
*/

$clubId = 0;

if (
    isset($_GET["club_id"]) &&
    filter_var(
        $_GET["club_id"],
        FILTER_VALIDATE_INT
    )
) {

    $clubId =
        (int)$_GET["club_id"];

} elseif (
    isset($_GET["id"]) &&
    filter_var(
        $_GET["id"],
        FILTER_VALIDATE_INT
    )
) {

    $clubId =
        (int)$_GET["id"];
}


if ($clubId <= 0) {

    errorPage(
        "Club invalide",
        "L'identifiant du club est incorrect.",
        400
    );
}


/*
|--------------------------------------------------------------------------
| Utilisateur
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
        role,
        club_id
    FROM users
    WHERE id = ?
    LIMIT 1
    "
);

if (!$stmt) {

    errorPage(
        "Erreur SQL",
        mysqli_error($con),
        500
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $userId
);

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$currentUser =
    mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$currentUser) {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Club
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $con,
    "
    SELECT
        id,
        club_name,
        category,
        manager_id,
        status
    FROM clubs
    WHERE id = ?
    LIMIT 1
    "
);

if (!$stmt) {

    errorPage(
        "Erreur SQL",
        mysqli_error($con),
        500
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $clubId
);

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$club =
    mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$club) {

    errorPage(
        "Club introuvable",
        "Ce club n'existe pas.",
        404
    );
}


/*
|--------------------------------------------------------------------------
| Rôles
|--------------------------------------------------------------------------
*/

$userRole =
    (string)(
        $currentUser["role"]
        ?? ""
    );

$isAdmin =
    $userRole === "admin";

$isManager = false;

$isMember = false;


/*
|--------------------------------------------------------------------------
| Manager
|--------------------------------------------------------------------------
*/

if (
    (int)(
        $club["manager_id"]
        ?? 0
    ) === $userId
) {

    $isManager = true;
}


if (
    $userRole === "club_manager" &&
    (int)(
        $currentUser["club_id"]
        ?? 0
    ) === $clubId
) {

    $isManager = true;
}


/*
|--------------------------------------------------------------------------
| Membre
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $con,
    "
    SELECT id
    FROM club_members
    WHERE club_id = ?
      AND user_id = ?
    LIMIT 1
    "
);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $clubId,
        $userId
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_bind_result(
        $stmt,
        $membershipId
    );

    $isMember =
        (bool)mysqli_stmt_fetch($stmt);

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| Compatibilité users.club_id
|--------------------------------------------------------------------------
*/

if (
    (int)(
        $currentUser["club_id"]
        ?? 0
    ) === $clubId
) {

    $isMember = true;
}


if ($isAdmin) {
    $isMember = true;
}


if (
    !$isAdmin &&
    !$isManager &&
    !$isMember
) {

    errorPage(
        "403 - Accès interdit",
        "Vous n'avez pas accès à la discussion de ce club.",
        403
    );
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (
    empty($_SESSION["csrf_token"])
) {

    $_SESSION["csrf_token"] =
        bin2hex(
            random_bytes(32)
        );
}

$csrfToken =
    $_SESSION["csrf_token"];


/*
|--------------------------------------------------------------------------
| Vérifier tables
|--------------------------------------------------------------------------
*/

$tablesRequired = [
    "club_chat_messages",
    "club_chat_settings",
    "club_chat_suspensions"
];

foreach ($tablesRequired as $table) {

    $safeTable =
        mysqli_real_escape_string(
            $con,
            $table
        );

    $check =
        mysqli_query(
            $con,
            "SHOW TABLES LIKE '$safeTable'"
        );

    if (
        !$check ||
        mysqli_num_rows($check) === 0
    ) {

        errorPage(
            "Table manquante",
            "La table " .
            $table .
            " n'existe pas dans la base de données.",
            500
        );
    }

    mysqli_free_result($check);
}


/*
|--------------------------------------------------------------------------
| Chat en pause
|--------------------------------------------------------------------------
*/

$isPaused = false;

$stmt = mysqli_prepare(
    $con,
    "
    SELECT is_paused
    FROM club_chat_settings
    WHERE club_id = ?
    LIMIT 1
    "
);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $clubId
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_bind_result(
        $stmt,
        $pausedValue
    );

    if (mysqli_stmt_fetch($stmt)) {

        $isPaused =
            (int)$pausedValue === 1;
    }

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| Suspension
|--------------------------------------------------------------------------
*/

$isSuspended = false;

$stmt = mysqli_prepare(
    $con,
    "
    SELECT id
    FROM club_chat_suspensions
    WHERE club_id = ?
      AND user_id = ?
      AND unsuspended_at IS NULL
    LIMIT 1
    "
);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $clubId,
        $userId
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_bind_result(
        $stmt,
        $suspensionId
    );

    $isSuspended =
        (bool)mysqli_stmt_fetch($stmt);

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

$messages = [];

$stmt = mysqli_prepare(
    $con,
    "
    SELECT

        m.id,
        m.user_id,
        m.reply_to,
        m.message,
        m.created_at,

        CONCAT(
            u.prenom,
            ' ',
            u.nom
        ) AS sender_name,

        u.role AS sender_role,

        r.message AS reply_message,

        CONCAT(
            ru.prenom,
            ' ',
            ru.nom
        ) AS reply_sender

    FROM club_chat_messages m

    INNER JOIN users u
        ON u.id = m.user_id

    LEFT JOIN club_chat_messages r
        ON r.id = m.reply_to

    LEFT JOIN users ru
        ON ru.id = r.user_id

    WHERE
        m.club_id = ?

        AND (
            m.deleted_at IS NULL
            OR m.deleted_at = '0000-00-00 00:00:00'
        )

    ORDER BY
        m.created_at ASC

    LIMIT 300
    "
);

if (!$stmt) {

    errorPage(
        "Erreur SQL",
        mysqli_error($con),
        500
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $clubId
);

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

while (
    $row =
    mysqli_fetch_assoc($result)
) {

    $messages[] = $row;
}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Membres
|--------------------------------------------------------------------------
*/

$members = [];

$stmt = mysqli_prepare(
    $con,
    "
    SELECT
        cm.user_id,
        u.nom,
        u.prenom,
        u.email,
        u.role
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
        $clubId
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    while (
        $row =
        mysqli_fetch_assoc($result)
    ) {

        $members[] = $row;
    }

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| Ajouter manager s'il n'est pas dans club_members
|--------------------------------------------------------------------------
*/

$managerId =
    (int)(
        $club["manager_id"]
        ?? 0
    );

$managerExists = false;

foreach ($members as $member) {

    if (
        (int)$member["user_id"]
        === $managerId
    ) {

        $managerExists = true;
        break;
    }
}


if (
    $managerId > 0 &&
    !$managerExists
) {

    $stmt = mysqli_prepare(
        $con,
        "
        SELECT
            id AS user_id,
            nom,
            prenom,
            email,
            role
        FROM users
        WHERE id = ?
        LIMIT 1
        "
    );

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $managerId
        );

        mysqli_stmt_execute($stmt);

        $result =
            mysqli_stmt_get_result($stmt);

        $manager =
            mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        if ($manager) {
            $members[] = $manager;
        }
    }
}


/*
|--------------------------------------------------------------------------
| Variables
|--------------------------------------------------------------------------
*/

$clubName =
    trim(
        (string)(
            $club["club_name"]
            ?? "Club"
        )
    );

$currentUserName =
    fullName($currentUser);

$currentInitial =
    initials($currentUserName);

$clubInitial =
    initials($clubName);

$memberCount =
    count($members);

$messageCount =
    count($messages);

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
        <?= e($clubName) ?> - Chat
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body
    class="
        min-h-screen
        bg-[#eef3f8]
        text-slate-900
    "
>


<?php

if (
    file_exists(
        __DIR__ . "/includes/header.php"
    )
) {

    require_once __DIR__ .
        "/includes/header.php";

} elseif (
    file_exists(
        __DIR__ . "/header.php"
    )
) {

    require_once __DIR__ .
        "/header.php";
}

?>


<main
    class="
        mx-auto
        max-w-7xl
        px-3
        sm:px-5
        lg:px-8
        py-4
        sm:py-6
    "
>


<!-- =========================================================
     BARRE DU CLUB
========================================================= -->

<section
    class="
        mb-4
        overflow-hidden
        rounded-3xl
        bg-slate-900
        shadow-xl
    "
>

    <div
        class="
            relative
            overflow-hidden
            px-5
            py-5
            sm:px-7
            sm:py-6
        "
    >

        <div
            class="
                absolute
                -right-20
                -top-24
                h-60
                w-60
                rounded-full
                bg-cyan-500/20
                blur-3xl
            "
        ></div>


        <div
            class="
                relative
                flex
                flex-col
                gap-5
                sm:flex-row
                sm:items-center
                sm:justify-between
            "
        >

            <div
                class="
                    flex
                    min-w-0
                    items-center
                    gap-4
                "
            >

                <div
                    class="
                        flex
                        h-14
                        w-14
                        shrink-0
                        items-center
                        justify-center
                        rounded-2xl
                        bg-cyan-500
                        text-xl
                        font-black
                        text-white
                        shadow-lg
                    "
                >
                    <?= e($clubInitial) ?>
                </div>


                <div class="min-w-0">

                    <div
                        class="
                            flex
                            flex-wrap
                            items-center
                            gap-2
                        "
                    >

                        <h1
                            class="
                                truncate
                                text-xl
                                font-black
                                text-white
                                sm:text-2xl
                            "
                        >
                            <?= e($clubName) ?>
                        </h1>


                        <span
                            class="
                                rounded-full
                                px-2.5
                                py-1
                                text-[10px]
                                font-black
                                <?= $isPaused
                                    ? "bg-amber-400/15 text-amber-300"
                                    : "bg-emerald-400/15 text-emerald-300"
                                ?>
                            "
                        >
                            <?= $isPaused
                                ? "PAUSE"
                                : "EN LIGNE"
                            ?>
                        </span>

                    </div>


                    <p
                        class="
                            mt-1
                            text-sm
                            text-slate-400
                        "
                    >
                        <?= e(
                            $club["category"]
                            ?? "Discussion du club"
                        ) ?>
                    </p>

                </div>

            </div>


            <div
                class="
                    flex
                    items-center
                    gap-2
                "
            >

                <a
                    href="clubs.php"
                    class="
                        rounded-xl
                        border
                        border-white/10
                        bg-white/5
                        px-4
                        py-2.5
                        text-sm
                        font-semibold
                        text-white
                        transition
                        hover:bg-white/10
                    "
                >
                    Retour
                </a>

            </div>

        </div>

    </div>

</section>


<!-- =========================================================
     LAYOUT
========================================================= -->

<div
    class="
        grid
        grid-cols-1
        gap-4
        lg:grid-cols-[minmax(0,1fr)_300px]
    "
>


<!-- =========================================================
     CHAT
========================================================= -->

<section
    class="
        flex
        min-h-[650px]
        flex-col
        overflow-hidden
        rounded-3xl
        border
        border-slate-200
        bg-white
        shadow-sm
    "
>


    <!-- HEADER -->

    <div
        class="
            flex
            items-center
            justify-between
            border-b
            border-slate-100
            px-4
            py-4
            sm:px-6
        "
    >

        <div>

            <h2
                class="
                    text-lg
                    font-black
                "
            >
                Discussion
            </h2>

            <p
                class="
                    mt-0.5
                    text-xs
                    text-slate-400
                "
            >
                <?= $messageCount ?>
                message<?= $messageCount > 1 ? "s" : "" ?>
            </p>

        </div>


        <div
            class="
                flex
                items-center
                gap-2
                rounded-full
                bg-slate-100
                px-3
                py-1.5
            "
        >

            <span
                class="
                    h-2
                    w-2
                    rounded-full
                    <?= $isPaused
                        ? "bg-amber-400"
                        : "bg-emerald-500"
                    ?>
                "
            ></span>

            <span
                class="
                    text-xs
                    font-bold
                    text-slate-500
                "
            >
                <?= $isPaused
                    ? "Pause"
                    : "Actif"
                ?>
            </span>

        </div>

    </div>


    <!-- MESSAGES -->

    <div
        id="messagesContainer"
        class="
            flex-1
            overflow-y-auto
            bg-[#f5f7fa]
            px-3
            py-5
            sm:px-6
            sm:py-6
        "
    >

        <?php if (empty($messages)): ?>

            <div
                class="
                    flex
                    min-h-[500px]
                    items-center
                    justify-center
                    text-center
                "
            >

                <div>

                    <div
                        class="
                            mx-auto
                            flex
                            h-20
                            w-20
                            items-center
                            justify-center
                            rounded-3xl
                            bg-white
                            text-2xl
                            font-black
                            text-cyan-600
                            shadow-sm
                        "
                    >
                        #
                    </div>


                    <h3
                        class="
                            mt-5
                            text-lg
                            font-black
                        "
                    >
                        Aucun message
                    </h3>


                    <p
                        class="
                            mt-2
                            text-sm
                            text-slate-400
                        "
                    >
                        Soyez le premier à écrire.
                    </p>

                </div>

            </div>

        <?php endif; ?>


        <div
            class="
                mx-auto
                max-w-4xl
                space-y-5
            "
        >

        <?php foreach ($messages as $msg): ?>

            <?php

            $messageId =
                (int)$msg["id"];

            $senderId =
                (int)$msg["user_id"];

            $mine =
                $senderId === $userId;

            $senderName =
                trim(
                    (string)(
                        $msg["sender_name"]
                        ?? "Utilisateur"
                    )
                );

            if ($senderName === "") {
                $senderName = "Utilisateur";
            }

            $senderInitial =
                initials($senderName);

            $messageText =
                (string)(
                    $msg["message"]
                    ?? ""
                );

            ?>

            <div
                id="message-<?= $messageId ?>"
                class="
                    group
                    flex
                    <?= $mine
                        ? "justify-end"
                        : "justify-start"
                    ?>
                "
            >

                <div
                    class="
                        flex
                        max-w-[92%]
                        items-end
                        gap-2.5
                        sm:max-w-[75%]
                    "
                >

                    <?php if (!$mine): ?>

                        <div
                            class="
                                flex
                                h-9
                                w-9
                                shrink-0
                                items-center
                                justify-center
                                rounded-full
                                bg-slate-800
                                text-xs
                                font-black
                                text-white
                            "
                        >
                            <?= e(
                                $senderInitial
                            ) ?>
                        </div>

                    <?php endif; ?>


                    <div
                        class="
                            min-w-0
                        "
                    >

                        <!-- NOM -->

                        <div
                            class="
                                mb-1.5
                                flex
                                items-center
                                gap-2
                                px-1
                            "
                        >

                            <span
                                class="
                                    text-[11px]
                                    font-bold
                                    text-slate-500
                                "
                            >
                                <?= $mine
                                    ? "Vous"
                                    : e($senderName)
                                ?>
                            </span>


                            <?php if (
                                ($msg["sender_role"] ?? "")
                                === "club_manager"
                                ||
                                (
                                    $managerId > 0 &&
                                    $senderId === $managerId
                                )
                            ): ?>

                                <span
                                    class="
                                        rounded-full
                                        bg-cyan-100
                                        px-2
                                        py-0.5
                                        text-[9px]
                                        font-black
                                        text-cyan-700
                                    "
                                >
                                    MANAGER
                                </span>

                            <?php endif; ?>

                        </div>


                        <!-- BULLE -->

                        <div
                            class="
                                rounded-3xl
                                px-4
                                py-3
                                shadow-sm
                                <?= $mine
                                    ? "rounded-br-md bg-slate-900 text-white"
                                    : "rounded-bl-md border border-slate-200 bg-white text-slate-800"
                                ?>
                            "
                        >


                            <!-- MESSAGE RÉPONDU -->

                            <?php if (
                                !empty($msg["reply_to"]) &&
                                !empty($msg["reply_message"])
                            ): ?>

                                <button
                                    type="button"
                                    onclick="goToMessage(<?= (int)$msg["reply_to"] ?>)"
                                    class="
                                        mb-3
                                        block
                                        w-full
                                        overflow-hidden
                                        rounded-2xl
                                        text-left
                                        <?= $mine
                                            ? "bg-white/10"
                                            : "bg-slate-100"
                                        ?>
                                    "
                                >

                                    <div
                                        class="
                                            flex
                                        "
                                    >

                                        <div
                                            class="
                                                w-1
                                                shrink-0
                                                bg-cyan-500
                                            "
                                        ></div>


                                        <div
                                            class="
                                                min-w-0
                                                px-3
                                                py-2.5
                                            "
                                        >

                                            <p
                                                class="
                                                    text-[10px]
                                                    font-black
                                                    <?= $mine
                                                        ? "text-cyan-300"
                                                        : "text-cyan-700"
                                                    ?>
                                                "
                                            >
                                                Réponse à
                                                <?= e(
                                                    $msg["reply_sender"]
                                                    ?? "Utilisateur"
                                                ) ?>
                                            </p>


                                            <p
                                                class="
                                                    mt-1
                                                    truncate
                                                    text-xs
                                                    <?= $mine
                                                        ? "text-slate-300"
                                                        : "text-slate-500"
                                                    ?>
                                                "
                                            >
                                                <?= e(
                                                    $msg["reply_message"]
                                                ) ?>
                                            </p>

                                        </div>

                                    </div>

                                </button>

                            <?php endif; ?>


                            <!-- TEXTE -->

                            <p
                                class="
                                   px-2
                                   py-3
                                    break-words
                                    text-sm
                                    text-left
                                    leading-4
                                "
                            >
                                <?= e($messageText) ?>
                            </p>


                            <!-- FOOTER MESSAGE -->

                            <div
                                class="
                                    mt-2
                                    flex
                                    items-center
                                    justify-between
                                    gap-4
                                "
                            >

                                <span
                                    class="
                                        text-[10px]
                                        <?= $mine
                                            ? "text-slate-400"
                                            : "text-slate-400"
                                        ?>
                                    "
                                >
                                    <?= e(
                                        date(
                                            "H:i",
                                            strtotime(
                                                $msg["created_at"]
                                            )
                                        )
                                    ) ?>
                                </span>


                                <div
                                    class="
                                        flex
                                        items-center
                                        gap-2
                                    "
                                >

                                    <button
                                        type="button"
                                        onclick='setReply(
                                            <?= $messageId ?>,
                                            <?= json_encode(
                                                $senderName,
                                                JSON_UNESCAPED_UNICODE
                                            ) ?>,
                                            <?= json_encode(
                                                $messageText,
                                                JSON_UNESCAPED_UNICODE
                                            ) ?>
                                        )'
                                        class="
                                            text-[10px]
                                            font-bold
                                            <?= $mine
                                                ? "text-cyan-300 hover:text-white"
                                                : "text-cyan-700 hover:text-cyan-900"
                                            ?>
                                        "
                                    >
                                        Répondre
                                    </button>


                                    <?php if (
                                        $isManager ||
                                        $isAdmin
                                    ): ?>

                                        <form
                                            method="POST"
                                            action="messages-traitement.php"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($csrfToken) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="club_id"
                                                value="<?= $clubId ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="message_id"
                                                value="<?= $messageId ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="delete_message"
                                                onclick="return confirm('Supprimer ce message ?')"
                                                class="
                                                    text-[10px]
                                                    font-bold
                                                    text-red-400
                                                    hover:text-red-600
                                                "
                                            >
                                                Supprimer
                                            </button>

                                        </form>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

        </div>

    </div>


    <!-- RÉPONSE -->

    <div
        id="replyBox"
        class="
            hidden
            border-t
            border-slate-100
            bg-white
            px-4
            py-3
        "
    >

        <div
            class="
                mx-auto
                flex
                max-w-4xl
                items-center
                gap-3
            "
        >

            <div
                class="
                    h-10
                    w-1
                    shrink-0
                    rounded-full
                    bg-cyan-600
                "
            ></div>


            <div
                class="
                    min-w-0
                    flex-1
                "
            >

                <p
                    id="replyAuthor"
                    class="
                        text-xs
                        font-black
                        text-cyan-700
                    "
                ></p>


                <p
                    id="replyText"
                    class="
                        mt-1
                        truncate
                        text-xs
                        text-slate-500
                    "
                ></p>

            </div>


            <button
                type="button"
                onclick="cancelReply()"
                class="
                    flex
                    h-8
                    w-8
                    shrink-0
                    items-center
                    justify-center
                    rounded-lg
                    text-xl
                    text-slate-400
                    hover:bg-slate-100
                "
            >
                ×
            </button>

        </div>

    </div>


    <!-- COMPOSER -->

    <div
        class="
            border-t
            border-slate-100
            bg-white
            p-3
            sm:p-4
        "
    >

        <?php if (
            $isSuspended &&
            !$isManager &&
            !$isAdmin
        ): ?>

            <div
                class="
                    rounded-2xl
                    border
                    border-red-200
                    bg-red-50
                    px-4
                    py-3
                    text-sm
                    font-bold
                    text-red-700
                "
            >
                Vous êtes suspendu de cette discussion.
            </div>


        <?php elseif (
            $isPaused &&
            !$isManager &&
            !$isAdmin
        ): ?>

            <div
                class="
                    rounded-2xl
                    border
                    border-amber-200
                    bg-amber-50
                    px-4
                    py-3
                    text-sm
                    font-bold
                    text-amber-700
                "
            >
                Le manager a temporairement désactivé les messages.
            </div>


        <?php else: ?>

            <form
                id="messageForm"
                action="messages-traitement.php"
                method="POST"
                class="
                    mx-auto
                    flex
                    max-w-4xl
                    items-end
                    gap-2
                "
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($csrfToken) ?>"
                >

                <input
                    type="hidden"
                    name="club_id"
                    value="<?= $clubId ?>"
                >

                <input
                    type="hidden"
                    name="reply_to"
                    id="replyTo"
                    value=""
                >

                <input
                    type="hidden"
                    name="action"
                    value="send"
                >


                <div
                    class="
                        flex
                        flex-1
                        items-end
                        rounded-2xl
                        border
                        border-slate-200
                        bg-slate-50
                        p-1.5
                        transition
                        focus-within:border-cyan-500
                        focus-within:bg-white
                        focus-within:ring-4
                        focus-within:ring-cyan-100
                    "
                >

                    <textarea
                        id="messageInput"
                        name="message"
                        maxlength="2000"
                        rows="1"
                        required
                        placeholder="Écrire un message..."
                        class="
                            min-h-[46px]
                            max-h-32
                            flex-1
                            resize-none
                            bg-transparent
                            px-3
                            py-2.5
                            text-sm
                            outline-none
                        "
                    ></textarea>

                </div>


                <button
                    type="submit"
                    name="send_message"
                    class="
                        flex
                        h-12
                        w-12
                        shrink-0
                        items-center
                        justify-center
                        rounded-2xl
                        bg-cyan-700
                        text-lg
                        font-black
                        text-white
                        shadow-md
                        transition
                        hover:bg-cyan-800
                        active:scale-95
                    "
                    title="Envoyer"
                >
                    ↑
                </button>

            </form>

            <p
                class="
                    mx-auto
                    mt-2
                    max-w-4xl
                    px-1
                    text-[10px]
                    text-slate-400
                "
            >
                Ctrl + Entrée pour envoyer
            </p>

        <?php endif; ?>

    </div>

</section>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside
    class="
        space-y-4
    "
>


    <!-- MON PROFIL -->

    <section
        class="
            rounded-3xl
            border
            border-slate-200
            bg-white
            p-5
            shadow-sm
        "
    >

        <div
            class="
                flex
                items-center
                gap-3
            "
        >

            <div
                class="
                    flex
                    h-12
                    w-12
                    shrink-0
                    items-center
                    justify-center
                    rounded-2xl
                    bg-slate-900
                    font-black
                    text-white
                "
            >
                <?= e($currentInitial) ?>
            </div>


            <div class="min-w-0">

                <p
                    class="
                        truncate
                        text-sm
                        font-black
                    "
                >
                    <?= e(
                        $currentUserName
                    ) ?>
                </p>


                <p
                    class="
                        mt-1
                        text-xs
                        text-slate-400
                    "
                >
                    <?= $isAdmin
                        ? "Administrateur"
                        : (
                            $isManager
                            ? "Manager"
                            : "Membre"
                        )
                    ?>
                </p>

            </div>

        </div>

    </section>


    <!-- MEMBRES -->

    <section
        class="
            rounded-3xl
            border
            border-slate-200
            bg-white
            p-5
            shadow-sm
        "
    >

        <div
            class="
                flex
                items-center
                justify-between
            "
        >

            <div>

                <h2
                    class="
                        font-black
                    "
                >
                    Membres
                </h2>

                <p
                    class="
                        mt-1
                        text-xs
                        text-slate-400
                    "
                >
                    Membres du club
                </p>

            </div>


            <span
                class="
                    rounded-full
                    bg-slate-100
                    px-3
                    py-1
                    text-xs
                    font-black
                    text-slate-600
                "
            >
                <?= $memberCount ?>
            </span>

        </div>


        <div
            class="
                mt-5
                max-h-[420px]
                space-y-2
                overflow-y-auto
            "
        >

            <?php foreach ($members as $member): ?>

                <?php

                $memberName =
                    fullName($member);

                $memberInitial =
                    initials(
                        $memberName
                    );

                $memberId =
                    (int)(
                        $member["user_id"]
                        ?? 0
                    );

                $memberIsManager =
                    $memberId === $managerId;

                $memberIsMe =
                    $memberId === $userId;

                ?>

                <div
                    class="
                        flex
                        items-center
                        gap-3
                        rounded-2xl
                        p-2
                        transition
                        hover:bg-slate-50
                    "
                >

                    <div
                        class="
                            relative
                            flex
                            h-10
                            w-10
                            shrink-0
                            items-center
                            justify-center
                            rounded-xl
                            bg-slate-800
                            text-xs
                            font-black
                            text-white
                        "
                    >
                        <?= e(
                            $memberInitial
                        ) ?>


                        <span
                            class="
                                absolute
                                -bottom-0.5
                                -right-0.5
                                h-3
                                w-3
                                rounded-full
                                border-2
                                border-white
                                bg-emerald-500
                            "
                        ></span>

                    </div>


                    <div class="min-w-0 flex-1">

                        <p
                            class="
                                truncate
                                text-sm
                                font-bold
                                text-slate-800
                            "
                        >
                            <?= e(
                                $memberName
                            ) ?>

                            <?php if ($memberIsMe): ?>

                                <span
                                    class="
                                        text-[10px]
                                        font-normal
                                        text-slate-400
                                    "
                                >
                                    (vous)
                                </span>

                            <?php endif; ?>

                        </p>


                        <p
                            class="
                                mt-0.5
                                text-[10px]
                                font-semibold
                                <?= $memberIsManager
                                    ? "text-cyan-700"
                                    : "text-slate-400"
                                ?>
                            "
                        >
                            <?= $memberIsManager
                                ? "Manager"
                                : "Membre"
                            ?>
                        </p>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </section>


    <!-- GESTION -->

    <?php if (
        $isManager ||
        $isAdmin
    ): ?>

        <section
            class="
                rounded-3xl
                border
                border-slate-200
                bg-white
                p-5
                shadow-sm
            "
        >

            <div
                class="
                    flex
                    items-center
                    gap-3
                "
            >

                <div
                    class="
                        flex
                        h-10
                        w-10
                        items-center
                        justify-center
                        rounded-xl
                        bg-cyan-50
                        text-cyan-700
                    "
                >
                    ⚙
                </div>


                <div>

                    <h2
                        class="
                            font-black
                        "
                    >
                        Gestion
                    </h2>

                    <p
                        class="
                            mt-0.5
                            text-xs
                            text-slate-400
                        "
                    >
                        Administration du chat
                    </p>

                </div>

            </div>


            <form
                action="messages-traitement.php"
                method="POST"
                class="mt-5"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($csrfToken) ?>"
                >

                <input
                    type="hidden"
                    name="club_id"
                    value="<?= $clubId ?>"
                >


                <?php if ($isPaused): ?>

                    <button
                        type="submit"
                        name="resume_chat"
                        class="
                            w-full
                            rounded-xl
                            bg-emerald-600
                            px-4
                            py-3
                            text-sm
                            font-bold
                            text-white
                            transition
                            hover:bg-emerald-700
                        "
                    >
                        Reprendre le chat
                    </button>

                <?php else: ?>

                    <button
                        type="submit"
                        name="pause_chat"
                        onclick="
                            return confirm(
                                'Mettre le chat en pause ?'
                            );
                        "
                        class="
                            w-full
                            rounded-xl
                            bg-amber-500
                            px-4
                            py-3
                            text-sm
                            font-bold
                            text-white
                            transition
                            hover:bg-amber-600
                        "
                    >
                        Mettre en pause
                    </button>

                <?php endif; ?>

            </form>

        </section>

    <?php endif; ?>


    <!-- INFOS -->

    <section
        class="
            rounded-3xl
            border
            border-slate-200
            bg-white
            p-5
            shadow-sm
        "
    >

        <h2
            class="
                font-black
            "
        >
            Informations
        </h2>


        <div
            class="
                mt-4
                space-y-3
            "
        >

            <div
                class="
                    flex
                    items-center
                    justify-between
                    text-sm
                "
            >

                <span class="text-slate-500">
                    Membres
                </span>

                <strong>
                    <?= $memberCount ?>
                </strong>

            </div>


            <div
                class="
                    flex
                    items-center
                    justify-between
                    text-sm
                "
            >

                <span class="text-slate-500">
                    Messages
                </span>

                <strong>
                    <?= $messageCount ?>
                </strong>

            </div>


            <div
                class="
                    flex
                    items-center
                    justify-between
                    text-sm
                "
            >

                <span class="text-slate-500">
                    État
                </span>

                <strong
                    class="<?= $isPaused
                        ? "text-amber-600"
                        : "text-emerald-600"
                    ?>"
                >
                    <?= $isPaused
                        ? "Pause"
                        : "Actif"
                    ?>
                </strong>

            </div>

        </div>

    </section>

</aside>

</div>

</main>


<script>

/*
|--------------------------------------------------------------------------
| Scroll vers le dernier message
|--------------------------------------------------------------------------
*/

const messagesContainer =
    document.getElementById(
        "messagesContainer"
    );

if (messagesContainer) {

    messagesContainer.scrollTop =
        messagesContainer.scrollHeight;
}


/*
|--------------------------------------------------------------------------
| Réponse
|--------------------------------------------------------------------------
*/

function setReply(
    id,
    author,
    text
) {

    const replyTo =
        document.getElementById(
            "replyTo"
        );

    const replyBox =
        document.getElementById(
            "replyBox"
        );

    const replyAuthor =
        document.getElementById(
            "replyAuthor"
        );

    const replyText =
        document.getElementById(
            "replyText"
        );

    const input =
        document.getElementById(
            "messageInput"
        );


    if (!replyTo) {
        return;
    }


    replyTo.value =
        id;


    replyAuthor.textContent =
        "Réponse à " + author;


    replyText.textContent =
        text;


    replyBox.classList.remove(
        "hidden"
    );


    if (input) {

        input.focus();

        input.scrollIntoView({
            behavior: "smooth",
            block: "center"
        });
    }
}


/*
|--------------------------------------------------------------------------
| Annuler réponse
|--------------------------------------------------------------------------
*/

function cancelReply() {

    const replyTo =
        document.getElementById(
            "replyTo"
        );

    const replyBox =
        document.getElementById(
            "replyBox"
        );


    if (replyTo) {
        replyTo.value = "";
    }


    if (replyBox) {

        replyBox.classList.add(
            "hidden"
        );
    }
}


/*
|--------------------------------------------------------------------------
| Aller à un message
|--------------------------------------------------------------------------
*/

function goToMessage(id) {

    const element =
        document.getElementById(
            "message-" + id
        );


    if (!element) {
        return;
    }


    element.scrollIntoView({
        behavior: "smooth",
        block: "center"
    });


    element.classList.add(
        "ring-4",
        "ring-cyan-300"
    );


    setTimeout(
        function () {

            element.classList.remove(
                "ring-4",
                "ring-cyan-300"
            );

        },
        1200
    );
}


/*
|--------------------------------------------------------------------------
| Auto resize textarea
|--------------------------------------------------------------------------
*/

const messageInput =
    document.getElementById(
        "messageInput"
    );

if (messageInput) {

    messageInput.addEventListener(
        "input",
        function () {

            this.style.height =
                "auto";

            this.style.height =
                Math.min(
                    this.scrollHeight,
                    128
                ) + "px";
        }
    );


    /*
    | Ctrl + Enter
    */

    messageInput.addEventListener(
        "keydown",
        function(event) {

            if (
                event.ctrlKey &&
                event.key === "Enter"
            ) {

                event.preventDefault();

                const form =
                    document.getElementById(
                        "messageForm"
                    );

                if (form) {
                    form.requestSubmit();
                }
            }
        }
    );
}


/*
|--------------------------------------------------------------------------
| Éviter double envoi
|--------------------------------------------------------------------------
*/

const messageForm =
    document.getElementById(
        "messageForm"
    );

if (messageForm) {

    messageForm.addEventListener(
        "submit",
        function() {

            const button =
                messageForm.querySelector(
                    'button[type="submit"]'
                );

            if (button) {

                button.disabled =
                    true;

                button.style.opacity =
                    "0.6";
            }
        }
    );
}

</script>


</body>

</html>