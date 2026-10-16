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

function redirectChat(int $clubId): never
{
    header(
        "Location: club-chat.php?club_id=" . $clubId
    );

    exit;
}


function errorChat(
    int $clubId,
    string $message
): never {

    $_SESSION["chat_error"] = $message;

    redirectChat($clubId);
}


function successChat(
    int $clubId,
    string $message
): never {

    $_SESSION["chat_success"] = $message;

    redirectChat($clubId);
}


/*
|--------------------------------------------------------------------------
| UTILISATEUR
|--------------------------------------------------------------------------
*/

$userId = 0;

if (
    isset($_SESSION["user_id"]) &&
    is_numeric($_SESSION["user_id"])
) {
    $userId = (int) $_SESSION["user_id"];
}
elseif (
    isset($_SESSION["id"]) &&
    is_numeric($_SESSION["id"])
) {
    $userId = (int) $_SESSION["id"];
}


if ($userId <= 0) {

    header("Location: login.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| POST UNIQUEMENT
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: index.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| CLUB ID
|--------------------------------------------------------------------------
*/

$clubId = filter_input(
    INPUT_POST,
    "club_id",
    FILTER_VALIDATE_INT
);


if (!$clubId || $clubId <= 0) {

    die("Club invalide.");
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] =
        bin2hex(random_bytes(32));
}


$csrf =
    $_POST["csrf_token"] ?? "";


if (
    !is_string($csrf) ||
    !hash_equals(
        $_SESSION["csrf_token"],
        $csrf
    )
) {

    errorChat(
        $clubId,
        "Session expirée. Rechargez la page."
    );
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
        manager_id
    FROM clubs
    WHERE id = ?
    LIMIT 1
    "
);


if (!$stmt) {

    errorChat(
        $clubId,
        "Erreur lors de la récupération du club."
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

    errorChat(
        $clubId,
        "Club introuvable."
    );
}


/*
|--------------------------------------------------------------------------
| RÉCUPÉRER L'UTILISATEUR
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $con,
    "
    SELECT
        id,
        name,
        role,
        club_id
    FROM users
    WHERE id = ?
    LIMIT 1
    "
);


if (!$stmt) {

    errorChat(
        $clubId,
        "Impossible de vérifier votre compte."
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


$user =
    mysqli_fetch_assoc($result);


mysqli_stmt_close($stmt);


if (!$user) {

    header("Location: login.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| RÔLES
|--------------------------------------------------------------------------
*/

$isAdmin =
    (($user["role"] ?? "") === "admin");


$isManager =
    (
        (int)($club["manager_id"] ?? 0)
        ===
        $userId
    );


/*
|--------------------------------------------------------------------------
| MANAGER VIA users.club_id
|--------------------------------------------------------------------------
*/

if (
    ($user["role"] ?? "") === "club_manager" &&
    (int)($user["club_id"] ?? 0) === $clubId
) {

    $isManager = true;
}


/*
|--------------------------------------------------------------------------
| MEMBRE
|--------------------------------------------------------------------------
|
| IMPORTANT :
| aucune colonne status.
|
*/

$isMember = false;


$stmt = mysqli_prepare(
    $con,
    "
    SELECT id
    FROM club_members
    WHERE
        club_id = ?
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
| MEMBRE VIA users.club_id
|--------------------------------------------------------------------------
*/

if (
    (int)($user["club_id"] ?? 0) === $clubId
) {

    $isMember = true;
}


/*
|--------------------------------------------------------------------------
| AUTORISATION
|--------------------------------------------------------------------------
*/

if (
    !$isMember &&
    !$isManager &&
    !$isAdmin
) {

    errorChat(
        $clubId,
        "Vous n'êtes pas membre de ce club."
    );
}


/*
|--------------------------------------------------------------------------
| TABLE CHAT MESSAGES
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
    COLLATE=utf8mb4_unicode_ci
    "
);


/*
|--------------------------------------------------------------------------
| TABLE SETTINGS
|--------------------------------------------------------------------------
*/

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
    COLLATE=utf8mb4_unicode_ci
    "
);


/*
|--------------------------------------------------------------------------
| TABLE SUSPENSIONS
|--------------------------------------------------------------------------
*/

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
    COLLATE=utf8mb4_unicode_ci
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
| ÉTAT DU CHAT
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


    $isPaused =
        ((int)($settings["is_paused"] ?? 0) === 1);
}


/*
|--------------------------------------------------------------------------
| ENVOYER MESSAGE
|--------------------------------------------------------------------------
*/

if (isset($_POST["send_message"])) {


    /*
    |----------------------------------------------------------------------
    | PAUSE
    |----------------------------------------------------------------------
    */

    if (
        $isPaused &&
        !$isManager &&
        !$isAdmin
    ) {

        errorChat(
            $clubId,
            "Le chat est actuellement en pause."
        );
    }


    /*
    |----------------------------------------------------------------------
    | SUSPENSION
    |----------------------------------------------------------------------
    */

    $isSuspended = false;


    $stmt = mysqli_prepare(
        $con,
        "
        SELECT id
        FROM club_chat_suspensions
        WHERE
            club_id = ?
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


    if (
        $isSuspended &&
        !$isManager &&
        !$isAdmin
    ) {

        errorChat(
            $clubId,
            "Vous êtes suspendu du chat."
        );
    }


    /*
    |----------------------------------------------------------------------
    | MESSAGE
    |----------------------------------------------------------------------
    */

    $message =
        trim(
            (string)(
                $_POST["message"] ?? ""
            )
        );


    if ($message === "") {

        errorChat(
            $clubId,
            "Le message ne peut pas être vide."
        );
    }


    /*
    |----------------------------------------------------------------------
    | Nettoyage
    |----------------------------------------------------------------------
    */

    $cleanMessage =
        preg_replace(
            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
            "",
            $message
        );


    if ($cleanMessage === null) {

        $cleanMessage = "";
    }


    $message =
        trim($cleanMessage);


    if ($message === "") {

        errorChat(
            $clubId,
            "Message invalide."
        );
    }


    /*
    |----------------------------------------------------------------------
    | Limite
    |----------------------------------------------------------------------
    */

    if (
        mb_strlen($message) > 2000
    ) {

        errorChat(
            $clubId,
            "Le message ne peut pas dépasser 2000 caractères."
        );
    }


    /*
    |----------------------------------------------------------------------
    | RÉPONSE
    |----------------------------------------------------------------------
    */

    $replyTo =
        filter_input(
            INPUT_POST,
            "reply_to",
            FILTER_VALIDATE_INT
        );


    if (
        !$replyTo ||
        $replyTo <= 0
    ) {

        $replyTo = null;
    }


    /*
    |----------------------------------------------------------------------
    | Vérifier que le message auquel on répond appartient au club
    |----------------------------------------------------------------------
    */

    if ($replyTo !== null) {

        $stmt = mysqli_prepare(
            $con,
            "
            SELECT id
            FROM club_chat_messages
            WHERE
                id = ?
                AND club_id = ?
                AND deleted_at IS NULL
            LIMIT 1
            "
        );


        if (!$stmt) {

            errorChat(
                $clubId,
                "Impossible de vérifier la réponse."
            );
        }


        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $replyTo,
            $clubId
        );


        mysqli_stmt_execute($stmt);


        $result =
            mysqli_stmt_get_result($stmt);


        $replyExists =
            (
                $result &&
                mysqli_fetch_assoc($result)
            );


        mysqli_stmt_close($stmt);


        if (!$replyExists) {

            $replyTo = null;
        }
    }


    /*
    |----------------------------------------------------------------------
    | ANTI-SPAM
    |----------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $con,
        "
        SELECT COUNT(*) AS total
        FROM club_chat_messages
        WHERE
            club_id = ?
            AND user_id = ?
            AND created_at >=
                DATE_SUB(
                    NOW(),
                    INTERVAL 10 SECOND
                )
            AND deleted_at IS NULL
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

        $rate =
            mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);


        if (
            (int)($rate["total"] ?? 0) >= 5
        ) {

            errorChat(
                $clubId,
                "Trop de messages. Attendez quelques secondes."
            );
        }
    }


    /*
    |----------------------------------------------------------------------
    | INSERT MESSAGE AVEC RÉPONSE
    |----------------------------------------------------------------------
    */

    if ($replyTo !== null) {

        $stmt = mysqli_prepare(
            $con,
            "
            INSERT INTO club_chat_messages
            (
                club_id,
                user_id,
                reply_to,
                message
            )
            VALUES
            (?, ?, ?, ?)
            "
        );


        if (!$stmt) {

            errorChat(
                $clubId,
                "Impossible de préparer le message."
            );
        }


        mysqli_stmt_bind_param(
            $stmt,
            "iiis",
            $clubId,
            $userId,
            $replyTo,
            $message
        );

    }
    else {

        $stmt = mysqli_prepare(
            $con,
            "
            INSERT INTO club_chat_messages
            (
                club_id,
                user_id,
                message
            )
            VALUES
            (?, ?, ?)
            "
        );


        if (!$stmt) {

            errorChat(
                $clubId,
                "Impossible de préparer le message."
            );
        }


        mysqli_stmt_bind_param(
            $stmt,
            "iis",
            $clubId,
            $userId,
            $message
        );
    }


    if (!mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        errorChat(
            $clubId,
            "Impossible d'envoyer le message."
        );
    }


    mysqli_stmt_close($stmt);


    successChat(
        $clubId,
        "Message envoyé."
    );
}


/*
|--------------------------------------------------------------------------
| SUPPRIMER MESSAGE
|--------------------------------------------------------------------------
*/

if (isset($_POST["delete_message"])) {


    if (!$isManager && !$isAdmin) {

        errorChat(
            $clubId,
            "Vous n'avez pas l'autorisation de supprimer les messages."
        );
    }


    $messageId =
        filter_input(
            INPUT_POST,
            "message_id",
            FILTER_VALIDATE_INT
        );


    if (
        !$messageId ||
        $messageId <= 0
    ) {

        errorChat(
            $clubId,
            "Message invalide."
        );
    }


    /*
    |----------------------------------------------------------------------
    | Suppression logique
    |----------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $con,
        "
        UPDATE club_chat_messages
        SET
            deleted_at = NOW()
        WHERE
            id = ?
            AND club_id = ?
            AND deleted_at IS NULL
        "
    );


    if (!$stmt) {

        errorChat(
            $clubId,
            "Erreur lors de la suppression."
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $messageId,
        $clubId
    );


    mysqli_stmt_execute($stmt);


    $affected =
        mysqli_stmt_affected_rows($stmt);


    mysqli_stmt_close($stmt);


    if ($affected <= 0) {

        errorChat(
            $clubId,
            "Message introuvable ou déjà supprimé."
        );
    }


    successChat(
        $clubId,
        "Message supprimé."
    );
}


/*
|--------------------------------------------------------------------------
| PAUSE CHAT
|--------------------------------------------------------------------------
*/

if (isset($_POST["pause_chat"])) {


    if (!$isManager && !$isAdmin) {

        errorChat(
            $clubId,
            "Seul le manager ou l'administrateur peut mettre le chat en pause."
        );
    }


    $stmt = mysqli_prepare(
        $con,
        "
        UPDATE club_chat_settings
        SET
            is_paused = 1
        WHERE club_id = ?
        "
    );


    if (!$stmt) {

        errorChat(
            $clubId,
            "Impossible de modifier le chat."
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $clubId
    );


    mysqli_stmt_execute($stmt);


    mysqli_stmt_close($stmt);


    successChat(
        $clubId,
        "Le chat a été mis en pause."
    );
}


/*
|--------------------------------------------------------------------------
| REPRENDRE CHAT
|--------------------------------------------------------------------------
*/

if (isset($_POST["resume_chat"])) {


    if (!$isManager && !$isAdmin) {

        errorChat(
            $clubId,
            "Seul le manager ou l'administrateur peut reprendre le chat."
        );
    }


    $stmt = mysqli_prepare(
        $con,
        "
        UPDATE club_chat_settings
        SET
            is_paused = 0
        WHERE club_id = ?
        "
    );


    if (!$stmt) {

        errorChat(
            $clubId,
            "Impossible de modifier le chat."
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $clubId
    );


    mysqli_stmt_execute($stmt);


    mysqli_stmt_close($stmt);


    successChat(
        $clubId,
        "Le chat est de nouveau actif."
    );
}


/*
|--------------------------------------------------------------------------
| SUSPENDRE MEMBRE
|--------------------------------------------------------------------------
*/

if (isset($_POST["suspend_member"])) {


    if (!$isManager && !$isAdmin) {

        errorChat(
            $clubId,
            "Vous n'avez pas l'autorisation de suspendre un membre."
        );
    }


    $memberId =
        filter_input(
            INPUT_POST,
            "member_id",
            FILTER_VALIDATE_INT
        );


    if (
        !$memberId ||
        $memberId <= 0
    ) {

        errorChat(
            $clubId,
            "Membre invalide."
        );
    }


    if ($memberId === $userId) {

        errorChat(
            $clubId,
            "Vous ne pouvez pas vous suspendre vous-même."
        );
    }


    /*
    |----------------------------------------------------------------------
    | Ne pas suspendre le manager
    |----------------------------------------------------------------------
    */

    if (
        (int)($club["manager_id"] ?? 0)
        ===
        $memberId
    ) {

        errorChat(
            $clubId,
            "Le manager du club ne peut pas être suspendu."
        );
    }


    /*
    |----------------------------------------------------------------------
    | Vérifier membre
    |----------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $con,
        "
        SELECT id
        FROM club_members
        WHERE
            club_id = ?
            AND user_id = ?
        LIMIT 1
        "
    );


    if (!$stmt) {

        errorChat(
            $clubId,
            "Erreur lors de la vérification du membre."
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $clubId,
        $memberId
    );


    mysqli_stmt_execute($stmt);


    $result =
        mysqli_stmt_get_result($stmt);


    $member =
        mysqli_fetch_assoc($result);


    mysqli_stmt_close($stmt);


    if (!$member) {

        errorChat(
            $clubId,
            "Ce membre n'appartient pas à ce club."
        );
    }


    /*
    |----------------------------------------------------------------------
    | Vérifier suspension existante
    |----------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $con,
        "
        SELECT id
        FROM club_chat_suspensions
        WHERE
            club_id = ?
            AND user_id = ?
            AND unsuspended_at IS NULL
        LIMIT 1
        "
    );


    if (!$stmt) {

        errorChat(
            $clubId,
            "Erreur SQL."
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $clubId,
        $memberId
    );


    mysqli_stmt_execute($stmt);


    $result =
        mysqli_stmt_get_result($stmt);


    $existing =
        mysqli_fetch_assoc($result);


    mysqli_stmt_close($stmt);


    if ($existing) {

        errorChat(
            $clubId,
            "Ce membre est déjà suspendu."
        );
    }


    /*
    |----------------------------------------------------------------------
    | Suspension
    |----------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $con,
        "
        INSERT INTO club_chat_suspensions
        (
            club_id,
            user_id
        )
        VALUES
        (?, ?)
        "
    );


    if (!$stmt) {

        errorChat(
            $clubId,
            "Impossible de suspendre le membre."
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $clubId,
        $memberId
    );


    $success =
        mysqli_stmt_execute($stmt);


    mysqli_stmt_close($stmt);


    if (!$success) {

        errorChat(
            $clubId,
            "Impossible de suspendre le membre."
        );
    }


    successChat(
        $clubId,
        "Membre suspendu du chat."
    );
}


/*
|--------------------------------------------------------------------------
| RÉACTIVER MEMBRE
|--------------------------------------------------------------------------
*/

if (isset($_POST["unsuspend_member"])) {


    if (!$isManager && !$isAdmin) {

        errorChat(
            $clubId,
            "Vous n'avez pas l'autorisation de réactiver un membre."
        );
    }


    $memberId =
        filter_input(
            INPUT_POST,
            "member_id",
            FILTER_VALIDATE_INT
        );


    if (
        !$memberId ||
        $memberId <= 0
    ) {

        errorChat(
            $clubId,
            "Membre invalide."
        );
    }


    $stmt = mysqli_prepare(
        $con,
        "
        UPDATE club_chat_suspensions
        SET
            unsuspended_at = NOW()
        WHERE
            club_id = ?
            AND user_id = ?
            AND unsuspended_at IS NULL
        "
    );


    if (!$stmt) {

        errorChat(
            $clubId,
            "Erreur lors de la réactivation."
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $clubId,
        $memberId
    );


    mysqli_stmt_execute($stmt);


    $affected =
        mysqli_stmt_affected_rows($stmt);


    mysqli_stmt_close($stmt);


    if ($affected <= 0) {

        errorChat(
            $clubId,
            "Ce membre n'est pas suspendu."
        );
    }


    successChat(
        $clubId,
        "Membre réactivé."
    );
}


/*
|--------------------------------------------------------------------------
| ACTION INVALIDE
|--------------------------------------------------------------------------
*/

errorChat(
    $clubId,
    "Action invalide."
);