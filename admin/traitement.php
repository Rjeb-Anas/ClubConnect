<?php

/*
|--------------------------------------------------------------------------
| ClubConnect
| admin/traitement.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../config/database.php";


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
| SÉCURITÉ
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
| REDIRECTION AVEC POPUP
|--------------------------------------------------------------------------
*/

function adminRedirect(
    string $type,
    string $message
): never {

    $_SESSION['popup'] = [
        'type' => $type,
        'message' => $message
    ];

    header("Location: dashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| VÉRIFICATION MÉTHODE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    adminRedirect(
        'error',
        'Requête invalide.'
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


$csrf =
    $_POST['csrf_token'] ?? '';


if (
    empty($csrf) ||
    !hash_equals(
        $_SESSION['csrf_token'],
        $csrf
    )
) {

    adminRedirect(
        'error',
        'Requête de sécurité invalide.'
    );
}


/*
|--------------------------------------------------------------------------
| =========================================================
| CLUBS
| =========================================================
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| ACCEPTER UN CLUB
|--------------------------------------------------------------------------
|
| Lorsque l'admin accepte un club :
|
| clubs.status     = active
| clubs.manager_id = created_by
| users.role       = club_manager
|
|--------------------------------------------------------------------------
*/

if (isset($_POST['approve_club'])) {

    $clubId = filter_input(
        INPUT_POST,
        'club_id',
        FILTER_VALIDATE_INT
    );


    if (!$clubId || $clubId <= 0) {

        adminRedirect(
            'error',
            'Club invalide.'
        );
    }


    mysqli_begin_transaction($con);


    try {

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
                created_by,
                manager_id,
                status
            FROM clubs
            WHERE id = ?
            LIMIT 1
            "
        );


        if (!$stmt) {
            throw new Exception(
                mysqli_error($con)
            );
        }


        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $clubId
        );


        if (!mysqli_stmt_execute($stmt)) {

            throw new Exception(
                mysqli_stmt_error($stmt)
            );
        }


        $result =
            mysqli_stmt_get_result($stmt);


        $club =
            mysqli_fetch_assoc($result);


        mysqli_stmt_close($stmt);


        if (!$club) {

            throw new Exception(
                'Club introuvable.'
            );
        }


        $creatorId =
            (int) $club['created_by'];


        if ($creatorId <= 0) {

            throw new Exception(
                'Le créateur de ce club est introuvable.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Vérifier le créateur
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $con,
            "
            SELECT
                id,
                role
            FROM users
            WHERE id = ?
            LIMIT 1
            "
        );


        if (!$stmt) {
            throw new Exception(
                mysqli_error($con)
            );
        }


        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $creatorId
        );


        if (!mysqli_stmt_execute($stmt)) {

            throw new Exception(
                mysqli_stmt_error($stmt)
            );
        }


        $result =
            mysqli_stmt_get_result($stmt);


        $creator =
            mysqli_fetch_assoc($result);


        mysqli_stmt_close($stmt);


        if (!$creator) {

            throw new Exception(
                "Le créateur du club n'existe plus."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Activer le club
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $con,
            "
            UPDATE clubs
            SET
                status = 'active',
                manager_id = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            "
        );


        if (!$stmt) {
            throw new Exception(
                mysqli_error($con)
            );
        }


        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $creatorId,
            $clubId
        );


        if (!mysqli_stmt_execute($stmt)) {

            throw new Exception(
                mysqli_stmt_error($stmt)
            );
        }


        mysqli_stmt_close($stmt);


        /*
        |--------------------------------------------------------------------------
        | Donner le rôle club_manager
        |--------------------------------------------------------------------------
        |
        | Un admin reste admin.
        |
        */

        if ($creator['role'] !== 'admin') {

            $stmt = mysqli_prepare(
                $con,
                "
                UPDATE users
                SET role = 'club_manager'
                WHERE id = ?
                "
            );


            if (!$stmt) {
                throw new Exception(
                    mysqli_error($con)
                );
            }


            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $creatorId
            );


            if (!mysqli_stmt_execute($stmt)) {

                throw new Exception(
                    mysqli_stmt_error($stmt)
                );
            }


            mysqli_stmt_close($stmt);
        }


        /*
        |--------------------------------------------------------------------------
        | Valider transaction
        |--------------------------------------------------------------------------
        */

        mysqli_commit($con);


        adminRedirect(
            'success',
            'Club accepté. Le créateur est maintenant Club Manager.'
        );


    } catch (Throwable $e) {

        mysqli_rollback($con);


        error_log(
            "APPROVE CLUB ERROR: " .
            $e->getMessage()
        );


        adminRedirect(
            'error',
            "Impossible d'accepter ce club."
        );
    }
}


/*
|--------------------------------------------------------------------------
| REFUSER UN CLUB
|--------------------------------------------------------------------------
*/

if (isset($_POST['reject_club'])) {

    $clubId = filter_input(
        INPUT_POST,
        'club_id',
        FILTER_VALIDATE_INT
    );


    if (!$clubId || $clubId <= 0) {

        adminRedirect(
            'error',
            'Club invalide.'
        );
    }


    $stmt = mysqli_prepare(
        $con,
        "
        UPDATE clubs
        SET
            status = 'rejected',
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
        "
    );


    if (!$stmt) {

        adminRedirect(
            'error',
            'Erreur SQL.'
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $clubId
    );


    if (!mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        adminRedirect(
            'error',
            'Impossible de refuser le club.'
        );
    }


    mysqli_stmt_close($stmt);


    adminRedirect(
        'success',
        'Club refusé.'
    );
}


/*
|--------------------------------------------------------------------------
| DÉSACTIVER UN CLUB
|--------------------------------------------------------------------------
*/

if (isset($_POST['disable_club'])) {

    $clubId = filter_input(
        INPUT_POST,
        'club_id',
        FILTER_VALIDATE_INT
    );


    if (!$clubId || $clubId <= 0) {

        adminRedirect(
            'error',
            'Club invalide.'
        );
    }


    $stmt = mysqli_prepare(
        $con,
        "
        UPDATE clubs
        SET
            status = 'disabled',
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
        "
    );


    if (!$stmt) {

        adminRedirect(
            'error',
            'Erreur SQL.'
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $clubId
    );


    if (!mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        adminRedirect(
            'error',
            'Impossible de désactiver le club.'
        );
    }


    mysqli_stmt_close($stmt);


    adminRedirect(
        'success',
        'Club désactivé.'
    );
}


/*
|--------------------------------------------------------------------------
| SUPPRIMER UN CLUB
|--------------------------------------------------------------------------
|
| 1. Récupérer created_by
| 2. Supprimer le club
| 3. Vérifier si le créateur possède encore un club
| 4. S'il n'en possède plus :
|
|       role = user
|
|--------------------------------------------------------------------------
*/

if (isset($_POST['delete_club'])) {

    $clubId = filter_input(
        INPUT_POST,
        'club_id',
        FILTER_VALIDATE_INT
    );


    if (!$clubId || $clubId <= 0) {

        adminRedirect(
            'error',
            'Club invalide.'
        );
    }


    mysqli_begin_transaction($con);


    try {

        /*
        |--------------------------------------------------------------------------
        | Récupérer les informations du club
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $con,
            "
            SELECT
                id,
                created_by,
                manager_id,
                logo
            FROM clubs
            WHERE id = ?
            LIMIT 1
            "
        );


        if (!$stmt) {
            throw new Exception(
                mysqli_error($con)
            );
        }


        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $clubId
        );


        if (!mysqli_stmt_execute($stmt)) {

            throw new Exception(
                mysqli_stmt_error($stmt)
            );
        }


        $result =
            mysqli_stmt_get_result($stmt);


        $club =
            mysqli_fetch_assoc($result);


        mysqli_stmt_close($stmt);


        if (!$club) {

            throw new Exception(
                'Club introuvable.'
            );
        }


        $creatorId =
            (int) $club['created_by'];


        /*
        |--------------------------------------------------------------------------
        | Supprimer le club
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $con,
            "
            DELETE FROM clubs
            WHERE id = ?
            LIMIT 1
            "
        );


        if (!$stmt) {

            throw new Exception(
                mysqli_error($con)
            );
        }


        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $clubId
        );


        if (!mysqli_stmt_execute($stmt)) {

            throw new Exception(
                mysqli_stmt_error($stmt)
            );
        }


        $affected =
            mysqli_stmt_affected_rows($stmt);


        mysqli_stmt_close($stmt);


        if ($affected <= 0) {

            throw new Exception(
                'Le club n’a pas été supprimé.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Si le créateur existe
        |--------------------------------------------------------------------------
        */

        if ($creatorId > 0) {

            /*
            |--------------------------------------------------------------------------
            | Vérifier s'il possède encore un club
            |--------------------------------------------------------------------------
            */

            $stmt = mysqli_prepare(
                $con,
                "
                SELECT COUNT(*) AS total
                FROM clubs
                WHERE
                    created_by = ?
                    OR manager_id = ?
                "
            );


            if (!$stmt) {

                throw new Exception(
                    mysqli_error($con)
                );
            }


            mysqli_stmt_bind_param(
                $stmt,
                "ii",
                $creatorId,
                $creatorId
            );


            if (!mysqli_stmt_execute($stmt)) {

                throw new Exception(
                    mysqli_stmt_error($stmt)
                );
            }


            $result =
                mysqli_stmt_get_result($stmt);


            $data =
                mysqli_fetch_assoc($result);


            mysqli_stmt_close($stmt);


            $remainingClubs =
                (int) ($data['total'] ?? 0);


            /*
            |--------------------------------------------------------------------------
            | Aucun autre club
            |--------------------------------------------------------------------------
            */

            if ($remainingClubs === 0) {

                /*
                |--------------------------------------------------------------------------
                | Revenir au rôle user
                |--------------------------------------------------------------------------
                |
                | Seulement si la personne est actuellement
                | club_manager.
                |
                | Un admin reste admin.
                |
                */

                $stmt = mysqli_prepare(
                    $con,
                    "
                    UPDATE users
                    SET role = 'user'
                    WHERE
                        id = ?
                        AND role = 'club_manager'
                    "
                );


                if (!$stmt) {

                    throw new Exception(
                        mysqli_error($con)
                    );
                }


                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $creatorId
                );


                if (!mysqli_stmt_execute($stmt)) {

                    throw new Exception(
                        mysqli_stmt_error($stmt)
                    );
                }


                mysqli_stmt_close($stmt);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Valider
        |--------------------------------------------------------------------------
        */

        mysqli_commit($con);


        /*
        |--------------------------------------------------------------------------
        | Supprimer le logo
        |--------------------------------------------------------------------------
        */

        if (!empty($club['logo'])) {

            $logoPath =
                __DIR__ .
                "/../" .
                ltrim(
                    $club['logo'],
                    "/"
                );


            if (is_file($logoPath)) {

                @unlink($logoPath);
            }
        }


        adminRedirect(
            'success',
            'Club supprimé avec succès.'
        );


    } catch (Throwable $e) {

        mysqli_rollback($con);


        error_log(
            "DELETE CLUB ERROR: " .
            $e->getMessage()
        );


        adminRedirect(
            'error',
            'Impossible de supprimer ce club.'
        );
    }
}


/*
|--------------------------------------------------------------------------
| =========================================================
| UTILISATEURS
| =========================================================
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| SUPPRIMER UN UTILISATEUR
|--------------------------------------------------------------------------
*/

if (isset($_POST['delete_user'])) {

    $userId = filter_input(
        INPUT_POST,
        'user_id',
        FILTER_VALIDATE_INT
    );


    if (!$userId || $userId <= 0) {

        adminRedirect(
            'error',
            'Utilisateur invalide.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Empêcher l'admin de supprimer son propre compte
    |--------------------------------------------------------------------------
    */

    if (
        $userId ===
        (int)($_SESSION['id'] ?? 0)
    ) {

        adminRedirect(
            'error',
            'Vous ne pouvez pas supprimer votre propre compte.'
        );
    }


    mysqli_begin_transaction($con);


    try {

        /*
        |--------------------------------------------------------------------------
        | Vérifier l'utilisateur
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $con,
            "
            SELECT
                id,
                role
            FROM users
            WHERE id = ?
            LIMIT 1
            "
        );


        if (!$stmt) {

            throw new Exception(
                mysqli_error($con)
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

            throw new Exception(
                'Utilisateur introuvable.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Supprimer notifications
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $con,
            "
            DELETE FROM notifications
            WHERE user_id = ?
            "
        );


        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $userId
            );


            mysqli_stmt_execute($stmt);

            mysqli_stmt_close($stmt);
        }


        /*
        |--------------------------------------------------------------------------
        | Supprimer l'utilisateur
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $con,
            "
            DELETE FROM users
            WHERE id = ?
            LIMIT 1
            "
        );


        if (!$stmt) {

            throw new Exception(
                'Erreur lors de la préparation de la suppression.'
            );
        }


        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $userId
        );


        if (!mysqli_stmt_execute($stmt)) {

            throw new Exception(
                mysqli_stmt_error($stmt)
            );
        }


        $affected =
            mysqli_stmt_affected_rows($stmt);


        mysqli_stmt_close($stmt);


        if ($affected <= 0) {

            throw new Exception(
                'Utilisateur introuvable.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Commit
        |--------------------------------------------------------------------------
        */

        mysqli_commit($con);


        adminRedirect(
            'success',
            'Utilisateur supprimé avec succès.'
        );


    } catch (Throwable $e) {

        mysqli_rollback($con);


        error_log(
            "DELETE USER ERROR: " .
            $e->getMessage()
        );


        adminRedirect(
            'error',
            'Impossible de supprimer cet utilisateur.'
        );
    }
}


/*
|--------------------------------------------------------------------------
| MODIFIER UN UTILISATEUR
|--------------------------------------------------------------------------
*/

if (isset($_POST['update_user'])) {

    $userId = filter_input(
        INPUT_POST,
        'user_id',
        FILTER_VALIDATE_INT
    );


    $nom =
        trim(
            $_POST['nom'] ?? ''
        );


    $prenom =
        trim(
            $_POST['prenom'] ?? ''
        );


    $email =
        strtolower(
            trim(
                $_POST['email'] ?? ''
            )
        );


    $role =
        strtolower(
            trim(
                $_POST['role'] ?? 'user'
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Validation ID
    |--------------------------------------------------------------------------
    */

    if (!$userId || $userId <= 0) {

        adminRedirect(
            'error',
            'Utilisateur invalide.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Validation nom/prénom
    |--------------------------------------------------------------------------
    */

    if (
        $nom === '' ||
        $prenom === ''
    ) {

        adminRedirect(
            'error',
            'Le nom et le prénom sont obligatoires.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Validation email
    |--------------------------------------------------------------------------
    */

    if (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        adminRedirect(
            'error',
            'Adresse email invalide.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Rôles autorisés
    |--------------------------------------------------------------------------
    */

    $allowedRoles = [
        'user',
        'member',
        'manager',
        'club_manager',
        'admin'
    ];


    if (
        !in_array(
            $role,
            $allowedRoles,
            true
        )
    ) {

        adminRedirect(
            'error',
            'Rôle invalide.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Empêcher de supprimer son propre rôle admin
    |--------------------------------------------------------------------------
    */

    if (
        $userId ===
        (int)($_SESSION['id'] ?? 0)
        &&
        $role !== 'admin'
    ) {

        adminRedirect(
            'error',
            'Vous ne pouvez pas modifier votre propre rôle administrateur.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Vérifier email unique
    |--------------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $con,
        "
        SELECT id
        FROM users
        WHERE
            email = ?
            AND id != ?
        LIMIT 1
        "
    );


    if (!$stmt) {

        adminRedirect(
            'error',
            'Erreur SQL.'
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        "si",
        $email,
        $userId
    );


    mysqli_stmt_execute($stmt);


    $result =
        mysqli_stmt_get_result($stmt);


    $emailExists =
        mysqli_num_rows($result) > 0;


    mysqli_stmt_close($stmt);


    if ($emailExists) {

        adminRedirect(
            'error',
            'Cette adresse email est déjà utilisée.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Mise à jour utilisateur
    |--------------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $con,
        "
        UPDATE users
        SET
            nom = ?,
            prenom = ?,
            email = ?,
            role = ?
        WHERE id = ?
        "
    );


    if (!$stmt) {

        adminRedirect(
            'error',
            'Impossible de préparer la modification.'
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        "ssssi",
        $nom,
        $prenom,
        $email,
        $role,
        $userId
    );


    if (!mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);


        adminRedirect(
            'error',
            'Impossible de modifier l’utilisateur.'
        );
    }


    mysqli_stmt_close($stmt);


    /*
    |--------------------------------------------------------------------------
    | Mettre à jour session
    |--------------------------------------------------------------------------
    */

    if (
        $userId ===
        (int)($_SESSION['id'] ?? 0)
    ) {

        $_SESSION['nom'] =
            $nom;

        $_SESSION['prenom'] =
            $prenom;

        $_SESSION['name'] =
            $prenom .
            ' ' .
            $nom;

        $_SESSION['email'] =
            $email;

        $_SESSION['role'] =
            $role;
    }


    adminRedirect(
        'success',
        'Utilisateur modifié avec succès.'
    );
}


/*
|--------------------------------------------------------------------------
| =========================================================
| NOTIFICATIONS
| =========================================================
|--------------------------------------------------------------------------
*/

if (isset($_POST['send_notification'])) {

    /*
    |--------------------------------------------------------------------------
    | Utilisateurs sélectionnés
    |--------------------------------------------------------------------------
    */

    $selectedUsers =
        $_POST['users'] ?? [];


    if (!is_array($selectedUsers)) {

        adminRedirect(
            'error',
            'Sélection invalide.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Nettoyage des IDs
    |--------------------------------------------------------------------------
    */

    $userIds = [];


    foreach (
        $selectedUsers
        as $selectedUser
    ) {

        $id =
            filter_var(
                $selectedUser,
                FILTER_VALIDATE_INT
            );


        if (
            $id !== false &&
            $id > 0
        ) {

            $userIds[] =
                (int)$id;
        }
    }


    $userIds =
        array_values(
            array_unique(
                $userIds
            )
        );


    if (
        count($userIds) === 0
    ) {

        adminRedirect(
            'error',
            'Sélectionnez au moins un utilisateur.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Données notification
    |--------------------------------------------------------------------------
    */

    $title =
        trim(
            $_POST['title'] ?? ''
        );


    $message =
        trim(
            $_POST['message'] ?? ''
        );


    $type =
        strtolower(
            trim(
                $_POST['type'] ?? 'info'
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Validation titre
    |--------------------------------------------------------------------------
    */

    if ($title === '') {

        adminRedirect(
            'error',
            'Le titre est obligatoire.'
        );
    }


    if (
        mb_strlen($title) > 255
    ) {

        adminRedirect(
            'error',
            'Le titre ne peut pas dépasser 255 caractères.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Validation message
    |--------------------------------------------------------------------------
    */

    if ($message === '') {

        adminRedirect(
            'error',
            'Le message est obligatoire.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Types autorisés
    |--------------------------------------------------------------------------
    */

    $allowedTypes = [
        'info',
        'success',
        'warning',
        'error'
    ];


    if (
        !in_array(
            $type,
            $allowedTypes,
            true
        )
    ) {

        $type = 'info';
    }


    /*
    |--------------------------------------------------------------------------
    | Vérifier table notifications
    |--------------------------------------------------------------------------
    */

    $tableCheck =
        mysqli_query(
            $con,
            "SHOW TABLES LIKE 'notifications'"
        );


    if (
        !$tableCheck ||
        mysqli_num_rows($tableCheck) === 0
    ) {

        adminRedirect(
            'error',
            'La table notifications n’existe pas.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Transaction
    |--------------------------------------------------------------------------
    */

    mysqli_begin_transaction($con);


    try {

        /*
        |--------------------------------------------------------------------------
        | Vérification utilisateur
        |--------------------------------------------------------------------------
        */

        $checkStmt =
            mysqli_prepare(
                $con,
                "
                SELECT id
                FROM users
                WHERE id = ?
                LIMIT 1
                "
            );


        if (!$checkStmt) {

            throw new Exception(
                'Erreur SQL.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Préparer insertion
        |--------------------------------------------------------------------------
        */

        $insertStmt =
            mysqli_prepare(
                $con,
                "
                INSERT INTO notifications
                (
                    user_id,
                    title,
                    message,
                    type,
                    is_read
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    0
                )
                "
            );


        if (!$insertStmt) {

            mysqli_stmt_close(
                $checkStmt
            );


            throw new Exception(
                'Erreur lors de la préparation de la notification.'
            );
        }


        $sentCount = 0;


        /*
        |--------------------------------------------------------------------------
        | Envoyer à chaque utilisateur
        |--------------------------------------------------------------------------
        */

        foreach (
            $userIds
            as $recipientId
        ) {

            /*
            |--------------------------------------------------------------------------
            | Vérifier utilisateur
            |--------------------------------------------------------------------------
            */

            mysqli_stmt_bind_param(
                $checkStmt,
                "i",
                $recipientId
            );


            if (
                !mysqli_stmt_execute(
                    $checkStmt
                )
            ) {

                throw new Exception(
                    'Impossible de vérifier l’utilisateur.'
                );
            }


            $result =
                mysqli_stmt_get_result(
                    $checkStmt
                );


            if (
                mysqli_num_rows($result) === 0
            ) {

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Créer notification
            |--------------------------------------------------------------------------
            */

            mysqli_stmt_bind_param(
                $insertStmt,
                "isss",
                $recipientId,
                $title,
                $message,
                $type
            );


            if (
                !mysqli_stmt_execute(
                    $insertStmt
                )
            ) {

                throw new Exception(
                    mysqli_stmt_error(
                        $insertStmt
                    )
                );
            }


            $sentCount++;
        }


        mysqli_stmt_close(
            $checkStmt
        );


        mysqli_stmt_close(
            $insertStmt
        );


        /*
        |--------------------------------------------------------------------------
        | Aucun utilisateur valide
        |--------------------------------------------------------------------------
        */

        if (
            $sentCount === 0
        ) {

            throw new Exception(
                'Aucun utilisateur valide.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Valider
        |--------------------------------------------------------------------------
        */

        mysqli_commit($con);


        adminRedirect(
            'success',
            "Notification envoyée à {$sentCount} utilisateur(s)."
        );


    } catch (Throwable $e) {

        mysqli_rollback($con);


        error_log(
            "SEND NOTIFICATION ERROR: " .
            $e->getMessage()
        );


        adminRedirect(
            'error',
            'Impossible d’envoyer la notification.'
        );
    }
}


/*
|--------------------------------------------------------------------------
| AUCUNE ACTION
|--------------------------------------------------------------------------
*/

adminRedirect(
    'error',
    'Aucune action valide.'
);