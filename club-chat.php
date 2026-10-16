<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . "/config/database.php";

if (!isset($con) || !($con instanceof mysqli)) {
    die("Connexion à la base de données impossible.");
}

mysqli_set_charset($con, "utf8mb4");


/*
|--------------------------------------------------------------------------
| FONCTIONS
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


function redirectChat(int $clubId): never
{
    header(
        "Location: club-chat.php?club_id=" . $clubId
    );

    exit;
}


function showError(
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
                bg-white
                border
                border-slate-200
                rounded-3xl
                shadow-xl
                p-8
                text-center
            "
        >

            <div
                class="
                    mx-auto
                    w-20
                    h-20
                    rounded-3xl
                    bg-red-50
                    text-red-600
                    flex
                    items-center
                    justify-center
                    text-2xl
                    font-black
                "
            >
                <?= $code ?>
            </div>


            <h1
                class="
                    mt-6
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
                    inline-flex
                    mt-6
                    rounded-xl
                    bg-cyan-700
                    px-5
                    py-3
                    text-sm
                    font-black
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
| UTILISATEUR CONNECTÉ
|--------------------------------------------------------------------------
*/

$userId = 0;


/*
| Compatible avec plusieurs sessions de ton projet
*/

if (
    isset($_SESSION["user_id"]) &&
    is_numeric($_SESSION["user_id"])
) {

    $userId =
        (int)$_SESSION["user_id"];
}


if (
    $userId <= 0 &&
    isset($_SESSION["id"]) &&
    is_numeric($_SESSION["id"])
) {

    $userId =
        (int)$_SESSION["id"];
}


if ($userId <= 0) {

    header("Location: login.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| CLUB ID
|--------------------------------------------------------------------------
|
| Accepte :
|
| club-chat.php?club_id=2
|
| ou :
|
| club-chat.php?id=2
|
|--------------------------------------------------------------------------
*/

$clubId = null;


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


if (
    !$clubId ||
    $clubId <= 0
) {

    showError(
        "Club invalide",
        "L'identifiant du club est incorrect.",
        400
    );
}


/*
|--------------------------------------------------------------------------
| UTILISATEUR
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $con,
    "
    SELECT
        id,
        name,
        email,
        role,
        club_id
    FROM users
    WHERE id = ?
    LIMIT 1
    "
);


if (!$stmt) {

    showError(
        "Erreur",
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
| CLUB
|--------------------------------------------------------------------------
|
| IMPORTANT :
| On utilise uniquement les colonnes nécessaires.
| Pas de description.
| Pas de logo.
|
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

    showError(
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

    showError(
        "Club introuvable",
        "Ce club n'existe pas.",
        404
    );
}


/*
|--------------------------------------------------------------------------
| RÔLES
|--------------------------------------------------------------------------
*/

$userRole =
    (string)($currentUser["role"] ?? "");


$isAdmin =
    ($userRole === "admin");


$isManager = false;


/*
|--------------------------------------------------------------------------
| MANAGER VIA clubs.manager_id
|--------------------------------------------------------------------------
*/

if (
    isset($club["manager_id"]) &&
    (int)$club["manager_id"] === $userId
) {

    $isManager = true;
}


/*
|--------------------------------------------------------------------------
| MANAGER VIA users.club_id
|--------------------------------------------------------------------------
*/

if (
    $userRole === "club_manager" &&
    isset($currentUser["club_id"]) &&
    (int)$currentUser["club_id"] === $clubId
) {

    $isManager = true;
}


/*
|--------------------------------------------------------------------------
| MEMBRE
|--------------------------------------------------------------------------
*/

$isMember = false;


/*
|--------------------------------------------------------------------------
| Vérification club_members
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


    $result =
        mysqli_stmt_get_result($stmt);


    if (
        $result &&
        mysqli_fetch_assoc($result)
    ) {

        $isMember = true;
    }


    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| Vérification users.club_id
|--------------------------------------------------------------------------
*/

if (
    isset($currentUser["club_id"]) &&
    (int)$currentUser["club_id"] === $clubId
) {

    $isMember = true;
}


/*
|--------------------------------------------------------------------------
| AUTORISATION
|--------------------------------------------------------------------------
*/

if (
    !$isAdmin &&
    !$isManager &&
    !$isMember
) {

    showError(
        "403 - Accès interdit",
        "Vous n'êtes pas membre de ce club.",
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
| TABLES DU CHAT
|--------------------------------------------------------------------------
|
| Elles sont créées uniquement si elles n'existent pas.
|
|--------------------------------------------------------------------------
*/

mysqli_query(
    $con,
    "
    CREATE TABLE IF NOT EXISTS club_chat_messages (

        id INT UNSIGNED NOT NULL AUTO_INCREMENT,

        club_id INT UNSIGNED NOT NULL,

        user_id INT UNSIGNED NOT NULL,

        reply_to INT UNSIGNED NULL,

        message TEXT NOT NULL,

        created_at DATETIME NOT NULL
            DEFAULT CURRENT_TIMESTAMP,

        deleted_at DATETIME NULL,

        PRIMARY KEY (id),

        INDEX idx_chat_club (club_id),

        INDEX idx_chat_user (user_id),

        INDEX idx_chat_reply (reply_to)

    )
    ENGINE=InnoDB
    DEFAULT CHARSET=utf8mb4
    "
);


mysqli_query(
    $con,
    "
    CREATE TABLE IF NOT EXISTS club_chat_settings (

        id INT UNSIGNED NOT NULL AUTO_INCREMENT,

        club_id INT UNSIGNED NOT NULL,

        is_paused TINYINT(1) NOT NULL DEFAULT 0,

        PRIMARY KEY (id),

        UNIQUE KEY unique_chat_club (club_id)

    )
    ENGINE=InnoDB
    DEFAULT CHARSET=utf8mb4
    "
);


mysqli_query(
    $con,
    "
    CREATE TABLE IF NOT EXISTS club_chat_suspensions (

        id INT UNSIGNED NOT NULL AUTO_INCREMENT,

        club_id INT UNSIGNED NOT NULL,

        user_id INT UNSIGNED NOT NULL,

        created_at DATETIME NOT NULL
            DEFAULT CURRENT_TIMESTAMP,

        unsuspended_at DATETIME NULL,

        PRIMARY KEY (id),

        INDEX idx_suspend_club (club_id),

        INDEX idx_suspend_user (user_id)

    )
    ENGINE=InnoDB
    DEFAULT CHARSET=utf8mb4
    "
);


/*
|--------------------------------------------------------------------------
| INITIALISER SETTINGS
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $con,
    "
    INSERT IGNORE INTO club_chat_settings
    (
        club_id,
        is_paused
    )
    VALUES
    (?, 0)
    "
);


if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $clubId
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| STATUT CHAT
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


    $result =
        mysqli_stmt_get_result($stmt);


    $settings =
        mysqli_fetch_assoc($result);


    mysqli_stmt_close($stmt);


    if ($settings) {

        $isPaused =
            ((int)$settings["is_paused"] === 1);
    }
}


/*
|--------------------------------------------------------------------------
| UTILISATEUR SUSPENDU
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


    $result =
        mysqli_stmt_get_result($stmt);


    if (
        $result &&
        mysqli_fetch_assoc($result)
    ) {

        $isSuspended = true;
    }


    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| MESSAGES
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

        u.name AS sender_name,

        u.role AS sender_role,

        r.message AS reply_message,

        ru.name AS reply_sender

    FROM club_chat_messages m

    INNER JOIN users u
        ON u.id = m.user_id

    LEFT JOIN club_chat_messages r
        ON r.id = m.reply_to

    LEFT JOIN users ru
        ON ru.id = r.user_id

    WHERE m.club_id = ?

      AND m.deleted_at IS NULL

    ORDER BY m.created_at ASC

    LIMIT 300
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

        $messages[] =
            $row;
    }


    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| MEMBRES
|--------------------------------------------------------------------------
*/

$members = [];


$stmt = mysqli_prepare(
    $con,
    "
    SELECT

        cm.user_id,

        u.name,

        u.email,

        u.role

    FROM club_members cm

    INNER JOIN users u
        ON u.id = cm.user_id

    WHERE cm.club_id = ?

    ORDER BY u.name ASC
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

        $members[] =
            $row;
    }


    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| AJOUTER LE MANAGER À LA LISTE
|--------------------------------------------------------------------------
*/

$managerId =
    (int)($club["manager_id"] ?? 0);


$managerListed = false;


foreach ($members as $member) {

    if (
        (int)$member["user_id"] ===
        $managerId
    ) {

        $managerListed = true;

        break;
    }
}


if (
    $managerId > 0 &&
    !$managerListed
) {

    $stmt = mysqli_prepare(
        $con,
        "
        SELECT
            id AS user_id,
            name,
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

            $members[] =
                $manager;
        }
    }
}


$memberCount =
    count($members);


/*
|--------------------------------------------------------------------------
| NOM UTILISATEUR
|--------------------------------------------------------------------------
*/

$currentUserName =
    trim(
        (string)(
            $currentUser["name"]
            ?? "Utilisateur"
        )
    );


if ($currentUserName === "") {

    $currentUserName =
        "Utilisateur";
}


$userInitial =
    mb_strtoupper(
        mb_substr(
            $currentUserName,
            0,
            1
        )
    );


/*
|--------------------------------------------------------------------------
| NOM DU CLUB
|--------------------------------------------------------------------------
*/

$clubName =
    trim(
        (string)(
            $club["club_name"]
            ?? "Club"
        )
    );


$clubInitial =
    mb_strtoupper(
        mb_substr(
            $clubName,
            0,
            1
        )
    );

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
        bg-slate-100
    "
>


<?php

/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

if (
    file_exists(
        __DIR__ . "/includes/header.php"
    )
) {

    require_once
        __DIR__ . "/includes/header.php";
}

?>


<main
    class="
        max-w-7xl
        mx-auto
        px-3
        sm:px-6
        py-4
        sm:py-8
    "
>


    <!-- =====================================================
         CLUB HEADER
    ====================================================== -->

    <section
        class="
            bg-white
            border
            border-slate-200
            rounded-3xl
            shadow-sm
            p-4
            sm:p-6
            mb-5
        "
    >

        <div
            class="
                flex
                items-center
                justify-between
                gap-4
            "
        >

            <div
                class="
                    flex
                    items-center
                    gap-3
                    min-w-0
                "
            >

                <div
                    class="
                        w-14
                        h-14
                        shrink-0
                        rounded-2xl
                        bg-gradient-to-br
                        from-cyan-600
                        to-blue-700
                        text-white
                        flex
                        items-center
                        justify-center
                        text-xl
                        font-black
                    "
                >
                    <?= e($clubInitial) ?>
                </div>


                <div class="min-w-0">

                    <h1
                        class="
                            text-xl
                            sm:text-2xl
                            font-black
                            text-slate-900
                            truncate
                        "
                    >
                        <?= e($clubName) ?>
                    </h1>


                    <div
                        class="
                            flex
                            items-center
                            gap-2
                            mt-1
                        "
                    >

                        <span
                            class="
                                text-xs
                                text-slate-500
                            "
                        >
                            <?= e(
                                $club["category"]
                                ?? "Club"
                            ) ?>
                        </span>


                        <span
                            class="
                                w-1
                                h-1
                                rounded-full
                                bg-slate-300
                            "
                        ></span>


                        <span
                            class="
                                text-xs
                                font-bold
                                <?= $isPaused
                                    ? "text-amber-600"
                                    : "text-emerald-600"
                                ?>"
                        >
                            <?= $isPaused
                                ? "Chat en pause"
                                : "Chat actif"
                            ?>
                        </span>

                    </div>

                </div>

            </div>


            <a
                href="clubs.php"
                class="
                    hidden
                    sm:inline-flex
                    rounded-xl
                    border
                    border-slate-200
                    px-4
                    py-2.5
                    text-sm
                    font-bold
                    text-slate-700
                    hover:bg-slate-50
                "
            >
                Retour
            </a>

        </div>

    </section>


    <!-- =====================================================
         GRID
    ====================================================== -->

    <div
        class="
            grid
            grid-cols-1
            lg:grid-cols-4
            gap-5
        "
    >


        <!-- =================================================
             CHAT
        ================================================== -->

        <section
            class="
                lg:col-span-3
                bg-white
                border
                border-slate-200
                rounded-3xl
                shadow-sm
                overflow-hidden
                flex
                flex-col
            "
            style="
                height:min(720px,calc(100vh - 180px));
                min-height:520px;
            "
        >


            <!-- HEADER CHAT -->

            <div
                class="
                    px-4
                    sm:px-5
                    py-4
                    border-b
                    border-slate-100
                    flex
                    items-center
                    justify-between
                "
            >

                <div>

                    <h2
                        class="
                            font-black
                            text-slate-900
                        "
                    >
                        Discussion
                    </h2>


                    <p
                        class="
                            mt-1
                            text-xs
                            text-slate-400
                        "
                    >
                        <?= $memberCount ?>
                        membre<?= $memberCount > 1 ? "s" : "" ?>
                    </p>

                </div>


                <div
                    class="
                        w-3
                        h-3
                        rounded-full
                        <?= $isPaused
                            ? "bg-amber-400"
                            : "bg-emerald-500"
                        ?>"
                ></div>

            </div>


            <!-- MESSAGES -->

            <div
                id="messages"
                class="
                    flex-1
                    overflow-y-auto
                    bg-slate-50
                    p-4
                    sm:p-6
                    space-y-4
                "
            >


                <?php if (empty($messages)): ?>

                    <div
                        class="
                            h-full
                            flex
                            items-center
                            justify-center
                            text-center
                        "
                    >

                        <div>

                            <div
                                class="
                                    mx-auto
                                    w-16
                                    h-16
                                    rounded-2xl
                                    bg-white
                                    border
                                    border-slate-200
                                    flex
                                    items-center
                                    justify-center
                                    text-cyan-600
                                    text-2xl
                                    font-black
                                "
                            >
                                #
                            </div>


                            <h3
                                class="
                                    mt-4
                                    font-black
                                    text-slate-800
                                "
                            >
                                Aucun message
                            </h3>


                            <p
                                class="
                                    mt-1
                                    text-sm
                                    text-slate-400
                                "
                            >
                                Commencez la conversation.
                            </p>

                        </div>

                    </div>

                <?php endif; ?>


                <?php foreach ($messages as $msg): ?>

                    <?php

                    $messageId =
                        (int)$msg["id"];

                    $senderId =
                        (int)$msg["user_id"];

                    $mine =
                        ($senderId === $userId);


                    $sender =
                        trim(
                            (string)(
                                $msg["sender_name"]
                                ?? "Utilisateur"
                            )
                        );


                    if ($sender === "") {
                        $sender = "Utilisateur";
                    }


                    $senderInitial =
                        mb_strtoupper(
                            mb_substr(
                                $sender,
                                0,
                                1
                            )
                        );


                    $messageText =
                        (string)(
                            $msg["message"]
                            ?? ""
                        );

                    ?>

                    <div
                        id="message-<?= $messageId ?>"
                        class="
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
                                items-end
                                gap-2
                                max-w-[94%]
                                sm:max-w-[80%]
                            "
                        >


                            <?php if (!$mine): ?>

                                <div
                                    class="
                                        w-9
                                        h-9
                                        shrink-0
                                        rounded-full
                                        bg-slate-800
                                        text-white
                                        flex
                                        items-center
                                        justify-center
                                        text-xs
                                        font-black
                                    "
                                >
                                    <?= e($senderInitial) ?>
                                </div>

                            <?php endif; ?>


                            <div class="min-w-0">


                                <!-- NOM -->

                                <div
                                    class="
                                        flex
                                        items-center
                                        gap-2
                                        mb-1
                                        px-1
                                    "
                                >

                                    <span
                                        class="
                                            text-xs
                                            font-bold
                                            text-slate-500
                                        "
                                    >
                                        <?= $mine
                                            ? "Vous"
                                            : e($sender)
                                        ?>
                                    </span>


                                    <?php if (
                                        ($msg["sender_role"] ?? "")
                                        === "club_manager"
                                    ): ?>

                                        <span
                                            class="
                                                px-2
                                                py-0.5
                                                rounded-full
                                                bg-cyan-100
                                                text-cyan-700
                                                text-[9px]
                                                font-black
                                            "
                                        >
                                            MANAGER
                                        </span>

                                    <?php endif; ?>

                                </div>


                                <!-- BULLE -->

                                <div
                                    class="
                                        px-4
                                        py-3
                                        rounded-2xl
                                        shadow-sm
                                        <?= $mine
                                            ? "bg-gradient-to-br from-cyan-600 to-blue-700 text-white rounded-br-md"
                                            : "bg-white border border-slate-200 text-slate-800 rounded-bl-md"
                                        ?>
                                    "
                                >


                                    <!-- RÉPONSE -->

                                    <?php if (
                                        !empty($msg["reply_to"]) &&
                                        !empty($msg["reply_message"])
                                    ): ?>

                                        <button
                                            type="button"
                                            onclick="goToMessage(
                                                <?= (int)$msg["reply_to"] ?>
                                            )"
                                            class="
                                                w-full
                                                mb-3
                                                text-left
                                                rounded-xl
                                                overflow-hidden
                                                <?= $mine
                                                    ? "bg-white/15"
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
                                                        bg-cyan-400
                                                        shrink-0
                                                    "
                                                ></div>


                                                <div
                                                    class="
                                                        px-3
                                                        py-2
                                                        min-w-0
                                                    "
                                                >

                                                    <p
                                                        class="
                                                            text-[10px]
                                                            font-black
                                                            <?= $mine
                                                                ? "text-cyan-100"
                                                                : "text-cyan-700"
                                                            ?>"
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
                                                            text-xs
                                                            truncate
                                                            <?= $mine
                                                                ? "text-cyan-100"
                                                                : "text-slate-500"
                                                            ?>"
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
                                            whitespace-pre-wrap
                                            break-words
                                            text-sm
                                            leading-6
                                        "
                                    >
                                        <?= e($messageText) ?>
                                    </p>


                                    <!-- ACTIONS -->

                                    <div
                                        class="
                                            mt-2
                                            flex
                                            items-center
                                            gap-3
                                            <?= $mine
                                                ? "justify-end"
                                                : ""
                                            ?>"
                                    >

                                        <span
                                            class="
                                                text-[10px]
                                                <?= $mine
                                                    ? "text-cyan-100"
                                                    : "text-slate-400"
                                                ?>"
                                        >
                                            <?= date(
                                                "H:i",
                                                strtotime(
                                                    $msg["created_at"]
                                                )
                                            ) ?>
                                        </span>


                                        <button
                                            type="button"
                                            onclick='replyMessage(
                                                <?= $messageId ?>,
                                                <?= json_encode(
                                                    $sender,
                                                    JSON_UNESCAPED_UNICODE |
                                                    JSON_HEX_TAG |
                                                    JSON_HEX_APOS |
                                                    JSON_HEX_QUOT |
                                                    JSON_HEX_AMP
                                                ) ?>,
                                                <?= json_encode(
                                                    $messageText,
                                                    JSON_UNESCAPED_UNICODE |
                                                    JSON_HEX_TAG |
                                                    JSON_HEX_APOS |
                                                    JSON_HEX_QUOT |
                                                    JSON_HEX_AMP
                                                ) ?>
                                            )'
                                            class="
                                                text-[10px]
                                                font-bold
                                                <?= $mine
                                                    ? "text-white"
                                                    : "text-cyan-700"
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
                                                action="chat-traitement.php"
                                                method="POST"
                                                class="inline"
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
                                                        <?= $mine
                                                            ? "text-white"
                                                            : "text-red-500"
                                                        ?>
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

                <?php endforeach; ?>


            </div>


            <!-- RÉPONSE ACTIVE -->

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
                        flex
                        items-center
                        gap-3
                    "
                >

                    <div
                        class="
                            w-1
                            self-stretch
                            rounded-full
                            bg-cyan-600
                        "
                    ></div>


                    <div
                        class="
                            flex-1
                            min-w-0
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
                                text-xs
                                text-slate-500
                                truncate
                            "
                        ></p>

                    </div>


                    <button
                        type="button"
                        onclick="cancelReply()"
                        class="
                            w-8
                            h-8
                            rounded-lg
                            text-slate-400
                            hover:bg-slate-100
                        "
                    >
                        ×
                    </button>

                </div>

            </div>


            <!-- INPUT -->

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
                            bg-red-50
                            border
                            border-red-200
                            px-4
                            py-3
                            text-sm
                            font-semibold
                            text-red-700
                        "
                    >
                        Vous êtes suspendu du chat.
                    </div>


                <?php elseif (
                    $isPaused &&
                    !$isManager &&
                    !$isAdmin
                ): ?>

                    <div
                        class="
                            rounded-2xl
                            bg-amber-50
                            border
                            border-amber-200
                            px-4
                            py-3
                            text-sm
                            font-semibold
                            text-amber-700
                        "
                    >
                        Le manager a mis le chat en pause.
                    </div>


                <?php else: ?>

                    <form
                        action="chat-traitement.php"
                        method="POST"
                        id="chatForm"
                        class="
                            flex
                            items-end
                            gap-2
                        "
                    >

                        <input
                            type="hidden"
                            name="club_id"
                            value="<?= $clubId ?>"
                        >


                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($csrfToken) ?>"
                        >


                        <input
                            type="hidden"
                            name="reply_to"
                            id="replyTo"
                            value=""
                        >


                        <textarea
                            name="message"
                            id="messageInput"
                            rows="1"
                            maxlength="2000"
                            required
                            placeholder="Écrire un message..."
                            class="
                                flex-1
                                resize-none
                                max-h-32
                                rounded-2xl
                                bg-slate-100
                                px-4
                                py-3
                                text-sm
                                outline-none
                                border
                                border-transparent
                                focus:bg-white
                                focus:border-cyan-500
                                focus:ring-4
                                focus:ring-cyan-100
                            "
                        ></textarea>


                        <button
                            type="submit"
                            name="send_message"
                            class="
                                w-12
                                h-12
                                shrink-0
                                rounded-2xl
                                bg-gradient-to-br
                                from-cyan-600
                                to-blue-700
                                text-white
                                flex
                                items-center
                                justify-center
                                font-black
                                shadow-md
                                hover:scale-105
                                active:scale-95
                                transition
                            "
                        >
                            →
                        </button>

                    </form>

                <?php endif; ?>

            </div>


        </section>


        <!-- =================================================
             SIDEBAR
        ================================================== -->

        <aside
            class="
                space-y-4
            "
        >


            <!-- UTILISATEUR -->

            <section
                class="
                    bg-white
                    border
                    border-slate-200
                    rounded-3xl
                    shadow-sm
                    p-5
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
                            w-11
                            h-11
                            rounded-full
                            bg-gradient-to-br
                            from-cyan-600
                            to-blue-700
                            text-white
                            flex
                            items-center
                            justify-center
                            font-black
                        "
                    >
                        <?= e($userInitial) ?>
                    </div>


                    <div class="min-w-0">

                        <p
                            class="
                                font-black
                                text-slate-800
                                truncate
                            "
                        >
                            <?= e($currentUserName) ?>
                        </p>


                        <p
                            class="
                                mt-1
                                text-xs
                                text-slate-400
                            "
                        >
                            <?=
                                $isAdmin
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
                    bg-white
                    border
                    border-slate-200
                    rounded-3xl
                    shadow-sm
                    p-5
                "
            >

                <div
                    class="
                        flex
                        items-center
                        justify-between
                    "
                >

                    <h2
                        class="
                            font-black
                            text-slate-800
                        "
                    >
                        Membres
                    </h2>


                    <span
                        class="
                            px-2.5
                            py-1
                            rounded-full
                            bg-slate-100
                            text-slate-600
                            text-xs
                            font-black
                        "
                    >
                        <?= $memberCount ?>
                    </span>

                </div>


                <div
                    class="
                        mt-4
                        max-h-64
                        overflow-y-auto
                        space-y-2
                    "
                >

                    <?php foreach (
                        $members
                        as $member
                    ): ?>

                        <?php

                        $memberName =
                            trim(
                                (string)(
                                    $member["name"]
                                    ?? "Utilisateur"
                                )
                            );


                        if (
                            $memberName === ""
                        ) {

                            $memberName =
                                "Utilisateur";
                        }


                        $memberInitial =
                            mb_strtoupper(
                                mb_substr(
                                    $memberName,
                                    0,
                                    1
                                )
                            );

                        ?>

                        <div
                            class="
                                flex
                                items-center
                                gap-3
                                rounded-xl
                                p-2
                                hover:bg-slate-50
                            "
                        >

                            <div
                                class="
                                    w-9
                                    h-9
                                    shrink-0
                                    rounded-full
                                    bg-slate-800
                                    text-white
                                    flex
                                    items-center
                                    justify-center
                                    text-xs
                                    font-black
                                "
                            >
                                <?= e($memberInitial) ?>
                            </div>


                            <div
                                class="
                                    flex-1
                                    min-w-0
                                "
                            >

                                <p
                                    class="
                                        text-sm
                                        font-bold
                                        text-slate-700
                                        truncate
                                    "
                                >
                                    <?= e($memberName) ?>
                                </p>


                                <p
                                    class="
                                        mt-0.5
                                        text-[10px]
                                        text-slate-400
                                    "
                                >
                                    <?=
                                        ($member["role"] ?? "")
                                        === "club_manager"
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
                        bg-white
                        border
                        border-slate-200
                        rounded-3xl
                        shadow-sm
                        p-5
                    "
                >

                    <h2
                        class="
                            font-black
                            text-slate-800
                        "
                    >
                        Gestion du chat
                    </h2>


                    <p
                        class="
                            mt-1
                            text-xs
                            leading-5
                            text-slate-400
                        "
                    >
                        Contrôlez l'activité du chat.
                    </p>


                    <form
                        action="chat-traitement.php"
                        method="POST"
                        class="mt-4"
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
                                    hover:bg-emerald-700
                                    text-white
                                    py-3
                                    text-sm
                                    font-black
                                "
                            >
                                Reprendre le chat
                            </button>

                        <?php else: ?>

                            <button
                                type="submit"
                                name="pause_chat"
                                onclick="return confirm('Mettre le chat en pause ?')"
                                class="
                                    w-full
                                    rounded-xl
                                    bg-amber-500
                                    hover:bg-amber-600
                                    text-white
                                    py-3
                                    text-sm
                                    font-black
                                "
                            >
                                Mettre le chat en pause
                            </button>

                        <?php endif; ?>

                    </form>


                    <div
                        class="
                            mt-4
                            rounded-xl
                            bg-slate-50
                            p-3
                        "
                    >

                        <p
                            class="
                                text-xs
                                text-slate-400
                            "
                        >
                            État
                        </p>


                        <p
                            class="
                                mt-1
                                text-sm
                                font-black
                                <?= $isPaused
                                    ? "text-amber-600"
                                    : "text-emerald-600"
                                ?>"
                        >
                            <?= $isPaused
                                ? "Chat en pause"
                                : "Chat actif"
                            ?>
                        </p>

                    </div>

                </section>

            <?php endif; ?>


            <!-- INFORMATIONS -->

            <section
                class="
                    bg-white
                    border
                    border-slate-200
                    rounded-3xl
                    shadow-sm
                    p-5
                "
            >

                <h2
                    class="
                        font-black
                        text-slate-800
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
                            justify-between
                            text-sm
                        "
                    >

                        <span
                            class="text-slate-500"
                        >
                            Membres
                        </span>


                        <strong
                            class="text-slate-800"
                        >
                            <?= $memberCount ?>
                        </strong>

                    </div>


                    <div
                        class="
                            flex
                            justify-between
                            text-sm
                        "
                    >

                        <span
                            class="text-slate-500"
                        >
                            Messages
                        </span>


                        <strong
                            class="text-slate-800"
                        >
                            <?= count($messages) ?>
                        </strong>

                    </div>


                    <div
                        class="
                            flex
                            justify-between
                            text-sm
                        "
                    >

                        <span
                            class="text-slate-500"
                        >
                            Chat
                        </span>


                        <strong
                            class="
                                <?= $isPaused
                                    ? "text-amber-600"
                                    : "text-emerald-600"
                                ?>
                            "
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
| SCROLL
|--------------------------------------------------------------------------
*/

const messages =
    document.getElementById("messages");

if (messages) {

    messages.scrollTop =
        messages.scrollHeight;
}


/*
|--------------------------------------------------------------------------
| RÉPONDRE
|--------------------------------------------------------------------------
*/

function replyMessage(
    id,
    author,
    text
) {

    const replyTo =
        document.getElementById("replyTo");

    const replyBox =
        document.getElementById("replyBox");

    const replyAuthor =
        document.getElementById("replyAuthor");

    const replyText =
        document.getElementById("replyText");

    const input =
        document.getElementById("messageInput");


    if (!replyTo || !replyBox) {
        return;
    }


    replyTo.value =
        id;


    if (replyAuthor) {

        replyAuthor.textContent =
            "Réponse à " + author;
    }


    if (replyText) {

        replyText.textContent =
            text;
    }


    replyBox.classList.remove(
        "hidden"
    );


    if (input) {

        input.focus();
    }
}


/*
|--------------------------------------------------------------------------
| ANNULER RÉPONSE
|--------------------------------------------------------------------------
*/

function cancelReply() {

    const replyTo =
        document.getElementById("replyTo");

    const replyBox =
        document.getElementById("replyBox");


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
| ALLER À UN MESSAGE
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
| TEXTAREA AUTO RESIZE
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


    messageInput.addEventListener(
        "keydown",
        function (event) {

            /*
            | Ctrl + Enter = envoyer
            */

            if (
                event.ctrlKey &&
                event.key === "Enter"
            ) {

                event.preventDefault();


                const form =
                    document.getElementById(
                        "chatForm"
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
| ÉVITER DOUBLE ENVOI
|--------------------------------------------------------------------------
*/

const chatForm =
    document.getElementById(
        "chatForm"
    );


if (chatForm) {

    chatForm.addEventListener(
        "submit",
        function () {

            const button =
                chatForm.querySelector(
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