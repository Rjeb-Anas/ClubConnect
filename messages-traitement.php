<?php

session_start();

require_once __DIR__ . "/config/database.php";


/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
*/

mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($con) || !($con instanceof mysqli)) {
    die("Erreur : connexion à la base de données impossible.");
}

mysqli_set_charset($con, "utf8mb4");


/*
|--------------------------------------------------------------------------
| Fonctions
|--------------------------------------------------------------------------
*/

function failMessage(string $message, int $code = 400): never
{
    http_response_code($code);

    echo '<!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Erreur - ClubConnect</title>
        <style>
            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                background: #f8fafc;
                font-family: Arial, sans-serif;
            }

            .box {
                width: 100%;
                max-width: 500px;
                padding: 30px;
                background: white;
                border: 1px solid #e2e8f0;
                border-radius: 20px;
                box-shadow: 0 10px 30px rgba(0,0,0,.08);
            }

            h1 {
                margin: 0 0 12px;
                color: #dc2626;
                font-size: 22px;
            }

            p {
                color: #475569;
                line-height: 1.6;
            }

            a {
                display: inline-block;
                margin-top: 15px;
                padding: 11px 18px;
                background: #0891b2;
                color: white;
                text-decoration: none;
                border-radius: 10px;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="box">
            <h1>Une erreur est survenue</h1>
            <p>' .
        htmlspecialchars(
            $message,
            ENT_QUOTES,
            "UTF-8"
        ) .
        '</p>
            <a href="club-messages.php">Retour au chat</a>
        </div>
    </body>
    </html>';

    exit;
}


function redirectChat(int $clubId): never
{
    header(
        "Location: club-messages.php?club_id=" .
        $clubId
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| POST uniquement
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: clubs.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Utilisateur connecté
|--------------------------------------------------------------------------
*/

$userId = (int) (
    $_SESSION["id"]
    ?? $_SESSION["user_id"]
    ?? 0
);

if ($userId <= 0) {

    header("Location: login.php");
    exit;
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

$sessionToken =
    (string) $_SESSION["csrf_token"];

$postToken =
    (string) ($_POST["csrf_token"] ?? "");


/*
| Certains anciens formulaires peuvent ne pas
| encore avoir le token.
|
| Si le token est présent, on le vérifie.
*/

if (
    $postToken !== "" &&
    !hash_equals(
        $sessionToken,
        $postToken
    )
) {

    failMessage(
        "Session de sécurité invalide. Rechargez la page puis réessayez.",
        403
    );
}


/*
|--------------------------------------------------------------------------
| Club ID
|--------------------------------------------------------------------------
*/

$clubId = filter_input(
    INPUT_POST,
    "club_id",
    FILTER_VALIDATE_INT
);

if (!$clubId || $clubId <= 0) {

    failMessage(
        "Identifiant du club invalide.",
        400
    );
}

$clubId = (int) $clubId;


/*
|--------------------------------------------------------------------------
| Récupérer l'utilisateur
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

    failMessage(
        "Erreur SQL utilisateur : " .
        mysqli_error($con),
        500
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $userId
);

if (!mysqli_stmt_execute($stmt)) {

    $error =
        mysqli_stmt_error($stmt);

    mysqli_stmt_close($stmt);

    failMessage(
        "Erreur utilisateur : " . $error,
        500
    );
}

mysqli_stmt_bind_result(
    $stmt,
    $dbUserId,
    $userNom,
    $userPrenom,
    $userEmail,
    $userRole,
    $userClubId
);

$userExists =
    mysqli_stmt_fetch($stmt);

mysqli_stmt_close($stmt);


if (!$userExists) {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Récupérer le club
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $con,
    "
    SELECT
        id,
        club_name,
        manager_id,
        status
    FROM clubs
    WHERE id = ?
    LIMIT 1
    "
);

if (!$stmt) {

    failMessage(
        "Erreur SQL club : " .
        mysqli_error($con),
        500
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $clubId
);

if (!mysqli_stmt_execute($stmt)) {

    $error =
        mysqli_stmt_error($stmt);

    mysqli_stmt_close($stmt);

    failMessage(
        "Erreur club : " . $error,
        500
    );
}

mysqli_stmt_bind_result(
    $stmt,
    $dbClubId,
    $clubName,
    $managerId,
    $clubStatus
);

$clubExists =
    mysqli_stmt_fetch($stmt);

mysqli_stmt_close($stmt);


if (!$clubExists) {

    failMessage(
        "Club introuvable.",
        404
    );
}


/*
|--------------------------------------------------------------------------
| Vérifier manager
|--------------------------------------------------------------------------
*/

$isAdmin =
    strtolower(
        (string) $userRole
    ) === "admin";


$isManager =
    ((int) $managerId === $userId);


/*
|--------------------------------------------------------------------------
| Vérifier membre
|--------------------------------------------------------------------------
*/

$isMember = false;

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
        (bool) mysqli_stmt_fetch($stmt);

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| Compatibilité users.club_id
|--------------------------------------------------------------------------
*/

if (
    (int) $userClubId === $clubId
) {
    $isMember = true;
}


/*
|--------------------------------------------------------------------------
| Admin accès partout
|--------------------------------------------------------------------------
*/

if ($isAdmin) {
    $isMember = true;
}


if (
    !$isAdmin &&
    !$isManager &&
    !$isMember
) {

    failMessage(
        "Vous n'avez pas accès à ce club.",
        403
    );
}


/*
|--------------------------------------------------------------------------
| Déterminer l'action
|--------------------------------------------------------------------------
|
| Compatible avec :
|
| send_message
| action=send
| delete_message
| pause_chat
| resume_chat
| suspend_member
| unsuspend_member
|--------------------------------------------------------------------------
*/

$action = "";


/*
| Envoi
*/

if (
    isset($_POST["send_message"]) ||
    ($_POST["action"] ?? "") === "send"
) {

    $action = "send";
}


/*
| Suppression
*/ elseif (
    isset($_POST["delete_message"]) ||
    ($_POST["action"] ?? "") === "delete"
) {

    $action = "delete";
}


/*
| Pause
*/ elseif (
    isset($_POST["pause_chat"]) ||
    ($_POST["action"] ?? "") === "pause"
) {

    $action = "pause";
}


/*
| Reprendre
*/ elseif (
    isset($_POST["resume_chat"]) ||
    ($_POST["action"] ?? "") === "resume"
) {

    $action = "resume";
}


/*
| Suspendre
*/ elseif (
    isset($_POST["suspend_member"]) ||
    ($_POST["action"] ?? "") === "suspend"
) {

    $action = "suspend";
}


/*
| Désuspendre
*/ elseif (
    isset($_POST["unsuspend_member"]) ||
    ($_POST["action"] ?? "") === "unsuspend"
) {

    $action = "unsuspend";
}


if ($action === "") {

    failMessage(
        "Action inconnue.",
        400
    );
}


/*
|--------------------------------------------------------------------------
| Vérifier les tables nécessaires
|--------------------------------------------------------------------------
*/

$tables = [
    "club_chat_messages",
    "club_chat_settings",
    "club_chat_suspensions"
];

foreach ($tables as $table) {

    $check = mysqli_query(
        $con,
        "SHOW TABLES LIKE '" .
        mysqli_real_escape_string(
            $con,
            $table
        ) .
        "'"
    );

    if (
        !$check ||
        mysqli_num_rows($check) === 0
    ) {

        failMessage(
            "La table '" .
            $table .
            "' n'existe pas dans la base de données.",
            500
        );
    }

    mysqli_free_result($check);
}


/*
|--------------------------------------------------------------------------
| ENVOYER MESSAGE
|--------------------------------------------------------------------------
*/

if ($action === "send") {

    /*
    |--------------------------------------------------------------------------
    | Vérifier suspension
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
          AND (
              expires_at IS NULL
              OR expires_at > NOW()
          )
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
            (bool) mysqli_stmt_fetch($stmt);

        mysqli_stmt_close($stmt);
    }


    if (
        $isSuspended &&
        !$isManager &&
        !$isAdmin
    ) {

        failMessage(
            "Vous êtes suspendu de cette discussion.",
            403
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Vérifier pause
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
                (bool) $pausedValue;
        }

        mysqli_stmt_close($stmt);
    }


    if (
        $isPaused &&
        !$isManager &&
        !$isAdmin
    ) {

        failMessage(
            "Le manager a temporairement désactivé les messages.",
            403
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Message
    |--------------------------------------------------------------------------
    */

    $message =
        trim(
            (string) (
                $_POST["message"]
                ?? ""
            )
        );


    if ($message === "") {

        failMessage(
            "Le message ne peut pas être vide.",
            400
        );
    }


    if (
        mb_strlen(
            $message,
            "UTF-8"
        ) > 2000
    ) {

        failMessage(
            "Le message est trop long. Maximum : 2000 caractères.",
            400
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Nettoyage
    |--------------------------------------------------------------------------
    */

    $message =
        trim(
            strip_tags($message)
        );


    if ($message === "") {

        failMessage(
            "Le message ne contient aucun texte valide.",
            400
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Réponse à un message
    |--------------------------------------------------------------------------
    */

    $replyTo =
        (int) (
            $_POST["reply_to"]
            ?? 0
        );


    if ($replyTo < 0) {
        $replyTo = 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Vérifier message parent
    |--------------------------------------------------------------------------
    */

    if ($replyTo > 0) {

        $stmt = mysqli_prepare(
            $con,
            "
            SELECT id
            FROM club_chat_messages
            WHERE id = ?
              AND club_id = ?
            LIMIT 1
            "
        );

        if (!$stmt) {

            failMessage(
                "Erreur SQL réponse : " .
                mysqli_error($con),
                500
            );
        }

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $replyTo,
            $clubId
        );

        mysqli_stmt_execute($stmt);

        mysqli_stmt_bind_result(
            $stmt,
            $parentId
        );

        $parentExists =
            mysqli_stmt_fetch($stmt);

        mysqli_stmt_close($stmt);


        if (!$parentExists) {
            $replyTo = 0;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | INSERT MESSAGE
    |--------------------------------------------------------------------------
    */

    if ($replyTo > 0) {

        $stmt = mysqli_prepare(
            $con,
            "
            INSERT INTO club_chat_messages
            (
                club_id,
                user_id,
                message,
                reply_to
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?
            )
            "
        );

        if (!$stmt) {

            failMessage(
                "Erreur SQL message : " .
                mysqli_error($con),
                500
            );
        }

        mysqli_stmt_bind_param(
            $stmt,
            "iisi",
            $clubId,
            $userId,
            $message,
            $replyTo
        );

    } else {

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
            (
                ?,
                ?,
                ?
            )
            "
        );

        if (!$stmt) {

            failMessage(
                "Erreur SQL message : " .
                mysqli_error($con),
                500
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

        $error =
            mysqli_stmt_error($stmt);

        mysqli_stmt_close($stmt);

        /*
        | Si reply_to n'existe pas dans la table,
        | essayer sans réponse.
        */

        if (
            $replyTo > 0 &&
            (
                stripos(
                    $error,
                    "reply_to"
                ) !== false
            )
        ) {

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
                (
                    ?,
                    ?,
                    ?
                )
                "
            );

            if (!$stmt) {

                failMessage(
                    "Erreur SQL message : " .
                    mysqli_error($con),
                    500
                );
            }

            mysqli_stmt_bind_param(
                $stmt,
                "iis",
                $clubId,
                $userId,
                $message
            );

            if (!mysqli_stmt_execute($stmt)) {

                $error =
                    mysqli_stmt_error($stmt);

                mysqli_stmt_close($stmt);

                failMessage(
                    "Erreur MySQL : " . $error,
                    500
                );
            }

        } else {

            failMessage(
                "Erreur MySQL : " . $error,
                500
            );
        }

    } else {

        mysqli_stmt_close($stmt);
    }


    /*
    |--------------------------------------------------------------------------
    | Retour
    |--------------------------------------------------------------------------
    */

    redirectChat($clubId);
}


/*
|--------------------------------------------------------------------------
| SUPPRIMER MESSAGE
|--------------------------------------------------------------------------
*/

if ($action === "delete") {

    if (
        !$isManager &&
        !$isAdmin
    ) {

        failMessage(
            "Vous n'avez pas le droit de supprimer les messages.",
            403
        );
    }


    $messageId =
        (int) (
            $_POST["message_id"]
            ?? 0
        );


    if ($messageId <= 0) {

        failMessage(
            "Message invalide.",
            400
        );
    }


    $stmt = mysqli_prepare(
        $con,
        "
        DELETE FROM club_chat_messages
        WHERE id = ?
          AND club_id = ?
        LIMIT 1
        "
    );

    if (!$stmt) {

        failMessage(
            "Erreur SQL suppression : " .
            mysqli_error($con),
            500
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $messageId,
        $clubId
    );

    if (!mysqli_stmt_execute($stmt)) {

        $error =
            mysqli_stmt_error($stmt);

        mysqli_stmt_close($stmt);

        failMessage(
            "Erreur MySQL : " . $error,
            500
        );
    }

    mysqli_stmt_close($stmt);

    redirectChat($clubId);
}


/*
|--------------------------------------------------------------------------
| PAUSE CHAT
|--------------------------------------------------------------------------
*/

if ($action === "pause") {

    if (
        !$isManager &&
        !$isAdmin
    ) {

        failMessage(
            "Vous n'avez pas le droit de désactiver le chat.",
            403
        );
    }


    $stmt = mysqli_prepare(
        $con,
        "
        INSERT INTO club_chat_settings
        (
            club_id,
            is_paused
        )
        VALUES
        (
            ?,
            1
        )
        ON DUPLICATE KEY UPDATE
            is_paused = 1
        "
    );

    if (!$stmt) {

        failMessage(
            "Erreur SQL pause : " .
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

    mysqli_stmt_close($stmt);

    redirectChat($clubId);
}


/*
|--------------------------------------------------------------------------
| RESUME CHAT
|--------------------------------------------------------------------------
*/

if ($action === "resume") {

    if (
        !$isManager &&
        !$isAdmin
    ) {

        failMessage(
            "Vous n'avez pas le droit de réactiver le chat.",
            403
        );
    }


    $stmt = mysqli_prepare(
        $con,
        "
        INSERT INTO club_chat_settings
        (
            club_id,
            is_paused
        )
        VALUES
        (
            ?,
            0
        )
        ON DUPLICATE KEY UPDATE
            is_paused = 0
        "
    );

    if (!$stmt) {

        failMessage(
            "Erreur SQL reprise : " .
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

    mysqli_stmt_close($stmt);

    redirectChat($clubId);
}


/*
|--------------------------------------------------------------------------
| SUSPENDRE MEMBRE
|--------------------------------------------------------------------------
*/

if ($action === "suspend") {

    if (
        !$isManager &&
        !$isAdmin
    ) {

        failMessage(
            "Vous n'avez pas le droit de suspendre un membre.",
            403
        );
    }


    $targetUserId =
        (int) (
            $_POST["user_id"]
            ?? 0
        );


    if ($targetUserId <= 0) {

        failMessage(
            "Utilisateur invalide.",
            400
        );
    }


    if ($targetUserId === $userId) {

        failMessage(
            "Vous ne pouvez pas vous suspendre vous-même.",
            400
        );
    }


    $stmt = mysqli_prepare(
        $con,
        "
        INSERT INTO club_chat_suspensions
        (
            club_id,
            user_id
        )
        VALUES
        (
            ?,
            ?
        )
        ON DUPLICATE KEY UPDATE
            id = id
        "
    );

    if (!$stmt) {

        failMessage(
            "Erreur SQL suspension : " .
            mysqli_error($con),
            500
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $clubId,
        $targetUserId
    );

    if (!mysqli_stmt_execute($stmt)) {

        $error =
            mysqli_stmt_error($stmt);

        mysqli_stmt_close($stmt);

        failMessage(
            "Erreur MySQL : " . $error,
            500
        );
    }

    mysqli_stmt_close($stmt);

    redirectChat($clubId);
}


/*
|--------------------------------------------------------------------------
| DÉ-SUSPENDRE MEMBRE
|--------------------------------------------------------------------------
*/

if ($action === "unsuspend") {

    if (
        !$isManager &&
        !$isAdmin
    ) {

        failMessage(
            "Vous n'avez pas le droit de modifier les suspensions.",
            403
        );
    }


    $targetUserId =
        (int) (
            $_POST["user_id"]
            ?? 0
        );


    if ($targetUserId <= 0) {

        failMessage(
            "Utilisateur invalide.",
            400
        );
    }


    $stmt = mysqli_prepare(
        $con,
        "
        DELETE FROM club_chat_suspensions
        WHERE club_id = ?
          AND user_id = ?
        LIMIT 1
        "
    );

    if (!$stmt) {

        failMessage(
            "Erreur SQL : " .
            mysqli_error($con),
            500
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $clubId,
        $targetUserId
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);

    redirectChat($clubId);
}


/*
|--------------------------------------------------------------------------
| FIN
|--------------------------------------------------------------------------
*/

failMessage(
    "Action non traitée.",
    400
);
?>