<?php

/*
|--------------------------------------------------------------------------
| ClubConnect - manager/traitement.php
|--------------------------------------------------------------------------
| Actions :
| - Modifier son club
| - Supprimer son club
| - Accepter un membre
| - Refuser un membre
| - Retirer un membre
| - Quitter un club
|--------------------------------------------------------------------------
*/

session_start();

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/PopUpMsg.php";


/*
|--------------------------------------------------------------------------
| Fonctions
|--------------------------------------------------------------------------
*/

function cleanInput(?string $value): string
{
    return trim($value ?? '');
}

function redirectManager(string $url = "dashboard.php"): never
{
    header("Location: " . $url);
    exit;
}

function getManagerId(): int
{
    return (int) (
        $_SESSION['id']
        ?? $_SESSION['user_id']
        ?? 0
    );
}

function requireManager(): int
{
    $id = getManagerId();

    if ($id <= 0) {
        header("Location: ../login.php");
        exit;
    }

    $role = $_SESSION['role'] ?? '';

    if (
        $role !== 'club_manager' &&
        $role !== 'admin'
    ) {
        errorMessage(
            "Vous n'avez pas accès à cet espace."
        );

        header("Location: ../student/index.php");
        exit;
    }

    return $id;
}

function getClubForManager(
    mysqli $con,
    int $clubId,
    int $managerId
): ?array {

    $stmt = mysqli_prepare(
        $con,
        "
        SELECT
            id,
            club_name,
            category,
            description,
            logo,
            members_max,
            status,
            manager_id,
            created_by
        FROM clubs
        WHERE
            id = ?
            AND manager_id = ?
        LIMIT 1
        "
    );

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $clubId,
        $managerId
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $club =
        mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $club ?: null;
}


/*
|--------------------------------------------------------------------------
| Vérification utilisateur
|--------------------------------------------------------------------------
*/

$managerId = requireManager();


/*
|--------------------------------------------------------------------------
| 1. MODIFIER UN CLUB
|--------------------------------------------------------------------------
*/

if (isset($_POST['update_club'])) {

    $clubId =
        (int) (
            $_POST['club_id'] ?? 0
        );

    $clubName =
        cleanInput(
            $_POST['club_name'] ?? ''
        );

    $category =
        cleanInput(
            $_POST['category'] ?? ''
        );

    $description =
        cleanInput(
            $_POST['description'] ?? ''
        );

    $membersMax =
        (int) (
            $_POST['members_max'] ?? 0
        );


    /*
    |--------------------------------------------------------------------------
    | Vérifier le club
    |--------------------------------------------------------------------------
    */

    $club =
        getClubForManager(
            $con,
            $clubId,
            $managerId
        );


    if (!$club) {

        errorMessage(
            "Club introuvable ou accès refusé."
        );

        redirectManager();
    }


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        mb_strlen($clubName) < 2 ||
        mb_strlen($clubName) > 150
    ) {

        errorMessage(
            "Le nom du club doit contenir entre 2 et 150 caractères."
        );

        redirectManager(
            "club.php?id=" . $clubId
        );
    }


    if (
        mb_strlen($description) < 20 ||
        mb_strlen($description) > 2000
    ) {

        errorMessage(
            "La description doit contenir entre 20 et 2000 caractères."
        );

        redirectManager(
            "club.php?id=" . $clubId
        );
    }


    if (
        $membersMax < 2 ||
        $membersMax > 10000
    ) {

        errorMessage(
            "Le nombre maximum de membres est invalide."
        );

        redirectManager(
            "club.php?id=" . $clubId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Catégories
    |--------------------------------------------------------------------------
    */

    $allowedCategories = [
        'Informatique',
        'Culture',
        'Sport',
        'Art',
        'Entrepreneuriat',
        'Science',
        'Social',
        'Autre'
    ];


    if (
        !in_array(
            $category,
            $allowedCategories,
            true
        )
    ) {

        errorMessage(
            "La catégorie sélectionnée est invalide."
        );

        redirectManager(
            "club.php?id=" . $clubId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    */

    $logoPath =
        $club['logo'];


    if (
        isset($_FILES['logo']) &&
        $_FILES['logo']['error']
        !== UPLOAD_ERR_NO_FILE
    ) {

        if (
            $_FILES['logo']['error']
            !== UPLOAD_ERR_OK
        ) {

            errorMessage(
                "Erreur lors de l'envoi du logo."
            );

            redirectManager(
                "club.php?id=" . $clubId
            );
        }


        if (
            $_FILES['logo']['size']
            > 2 * 1024 * 1024
        ) {

            errorMessage(
                "Le logo ne doit pas dépasser 2 Mo."
            );

            redirectManager(
                "club.php?id=" . $clubId
            );
        }


        $tmpName =
            $_FILES['logo']['tmp_name'];

        $mime =
            mime_content_type(
                $tmpName
            );


        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];


        if (
            !isset(
            $allowedMimes[$mime]
        )
        ) {

            errorMessage(
                "Le logo doit être au format JPG, PNG ou WEBP."
            );

            redirectManager(
                "club.php?id=" . $clubId
            );
        }


        $uploadDir =
            __DIR__ .
            "/../uploads/clubs/";


        if (
            !is_dir($uploadDir) &&
            !mkdir(
                $uploadDir,
                0755,
                true
            )
        ) {

            errorMessage(
                "Impossible de créer le dossier des logos."
            );

            redirectManager(
                "club.php?id=" . $clubId
            );
        }


        $fileName =
            bin2hex(
                random_bytes(16)
            ) .
            "." .
            $allowedMimes[$mime];


        $destination =
            $uploadDir .
            $fileName;


        if (
            !move_uploaded_file(
                $tmpName,
                $destination
            )
        ) {

            errorMessage(
                "Impossible d'enregistrer le logo."
            );

            redirectManager(
                "club.php?id=" . $clubId
            );
        }


        $logoPath =
            "uploads/clubs/" .
            $fileName;


        /*
        | Supprimer ancien logo
        */

        if (
            !empty($club['logo'])
        ) {

            $oldLogo =
                __DIR__ .
                "/../" .
                $club['logo'];

            if (
                is_file($oldLogo)
            ) {
                @unlink($oldLogo);
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $con,
        "
        UPDATE clubs
        SET
            club_name = ?,
            category = ?,
            description = ?,
            logo = ?,
            members_max = ?,
            updated_at = NOW()
        WHERE
            id = ?
            AND manager_id = ?
        "
    );


    if (!$stmt) {

        error_log(
            mysqli_error($con)
        );

        errorMessage(
            "Impossible de modifier le club."
        );

        redirectManager(
            "club.php?id=" . $clubId
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        "ssssiii",
        $clubName,
        $category,
        $description,
        $logoPath,
        $membersMax,
        $clubId,
        $managerId
    );


    if (
        !mysqli_stmt_execute($stmt)
    ) {

        error_log(
            mysqli_stmt_error($stmt)
        );

        mysqli_stmt_close($stmt);

        errorMessage(
            "Impossible de modifier le club."
        );

        redirectManager(
            "club.php?id=" . $clubId
        );
    }


    mysqli_stmt_close($stmt);


    successMessage(
        "Le club a été modifié avec succès."
    );

    redirectManager(
        "club.php?id=" . $clubId
    );
}


/*
|--------------------------------------------------------------------------
| 2. SUPPRIMER UN CLUB
|--------------------------------------------------------------------------
*/

if (isset($_POST['delete_club'])) {

    $clubId = filter_input(
        INPUT_POST,
        'club_id',
        FILTER_VALIDATE_INT
    );

    $managerId = (int) ($_SESSION['id'] ?? 0);

    if (!$clubId || $clubId <= 0 || $managerId <= 0) {
        errorMessage("Informations invalides.");
        redirectManager();
    }

    mysqli_begin_transaction($con);

    try {

        /*
        |--------------------------------------------------------------------------
        | Vérifier que le club appartient bien au manager
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $con,
            "
            SELECT
                id,
                club_name,
                manager_id,
                created_by,
                logo
            FROM clubs
            WHERE
                id = ?
                AND manager_id = ?
            LIMIT 1
            "
        );

        if (!$stmt) {
            throw new Exception(mysqli_error($con));
        }

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $clubId,
            $managerId
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $club = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        if (!$club) {
            throw new Exception(
                "Club introuvable ou vous n'êtes pas son manager."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Supprimer les membres du club
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $con,
            "
            DELETE FROM club_members
            WHERE club_id = ?
            "
        );

        if ($stmt) {

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

            mysqli_stmt_close($stmt);
        }


        /*
        |--------------------------------------------------------------------------
        | Supprimer le club
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $con,
            "
            DELETE FROM clubs
            WHERE
                id = ?
                AND manager_id = ?
            LIMIT 1
            "
        );

        if (!$stmt) {
            throw new Exception(mysqli_error($con));
        }

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $clubId,
            $managerId
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(
                mysqli_stmt_error($stmt)
            );
        }

        if (mysqli_stmt_affected_rows($stmt) <= 0) {
            throw new Exception(
                "Le club n'a pas été supprimé."
            );
        }

        mysqli_stmt_close($stmt);


        /*
        |--------------------------------------------------------------------------
        | Vérifier si le manager possède encore un club
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $con,
            "
            SELECT COUNT(*) AS total
            FROM clubs
            WHERE manager_id = ?
            "
        );

        if (!$stmt) {
            throw new Exception(mysqli_error($con));
        }

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $managerId
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $data = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        $remainingClubs =
            (int) ($data['total'] ?? 0);


        /*
        |--------------------------------------------------------------------------
        | Aucun club restant
        |--------------------------------------------------------------------------
        */

        if ($remainingClubs === 0) {

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
                throw new Exception(mysqli_error($con));
            }

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $managerId
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(
                    mysqli_stmt_error($stmt)
                );
            }

            mysqli_stmt_close($stmt);


            /*
            | Mettre également la session à jour
            */

            $_SESSION['role'] = 'user';
        }


        /*
        |--------------------------------------------------------------------------
        | Valider toutes les opérations
        |--------------------------------------------------------------------------
        */

        mysqli_commit($con);


        /*
        |--------------------------------------------------------------------------
        | Supprimer l'ancien logo
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


        /*
        |--------------------------------------------------------------------------
        | Message
        |--------------------------------------------------------------------------
        */

        if ($remainingClubs === 0) {

            successMessage(
                "Club supprimé. Vous n'avez plus de club : votre rôle est maintenant utilisateur."
            );

        } else {

            successMessage(
                "Club supprimé avec succès."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Redirection
        |--------------------------------------------------------------------------
        */

        header(
            "Location: dashboard.php"
        );

        exit;


    } catch (Throwable $e) {

        mysqli_rollback($con);

        error_log(
            "MANAGER DELETE CLUB ERROR: " .
            $e->getMessage()
        );

        errorMessage(
            "Impossible de supprimer le club."
        );

        redirectManager();
    }
}


/*
|--------------------------------------------------------------------------
| 3. RETIRER UN MEMBRE
|--------------------------------------------------------------------------
*/

if (isset($_POST['remove_member'])) {

    $clubId =
        (int) (
            $_POST['club_id'] ?? 0
        );

    $memberId =
        (int) (
            $_POST['member_id'] ?? 0
        );


    if (
        $clubId <= 0 ||
        $memberId <= 0
    ) {

        errorMessage(
            "Informations invalides."
        );

        redirectManager();
    }


    /*
    | Vérifier que le manager possède le club
    */

    $club =
        getClubForManager(
            $con,
            $clubId,
            $managerId
        );


    if (!$club) {

        errorMessage(
            "Accès refusé."
        );

        redirectManager();
    }


    /*
    | Ne pas permettre au manager de se supprimer
    */

    if (
        $memberId === $managerId
    ) {

        errorMessage(
            "Le manager ne peut pas se retirer lui-même de son club."
        );

        redirectManager(
            "members.php?club_id=" .
            $clubId
        );
    }


    $stmt = mysqli_prepare(
        $con,
        "
        DELETE FROM club_members
        WHERE
            club_id = ?
            AND user_id = ?
        "
    );


    if (!$stmt) {

        errorMessage(
            "Impossible de retirer le membre."
        );

        redirectManager(
            "members.php?club_id=" .
            $clubId
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $clubId,
        $memberId
    );


    if (
        !mysqli_stmt_execute($stmt)
    ) {

        mysqli_stmt_close($stmt);

        errorMessage(
            "Impossible de retirer le membre."
        );

        redirectManager(
            "members.php?club_id=" .
            $clubId
        );
    }


    mysqli_stmt_close($stmt);


    /*
    | Si l'utilisateur n'a plus aucun club,
    | repasser son rôle à user.
    */

    $stmt = mysqli_prepare(
        $con,
        "
        SELECT COUNT(*) AS total
        FROM club_members
        WHERE user_id = ?
        "
    );


    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $memberId
        );

        mysqli_stmt_execute($stmt);

        $result =
            mysqli_stmt_get_result($stmt);

        $data =
            mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);


        if (
            (int) $data['total'] === 0
        ) {

            $stmt = mysqli_prepare(
                $con,
                "
                UPDATE users
                SET role = 'user'
                WHERE
                    id = ?
                    AND role = 'member'
                "
            );

            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $memberId
                );

                mysqli_stmt_execute($stmt);

                mysqli_stmt_close($stmt);
            }
        }
    }


    successMessage(
        "Le membre a été retiré du club."
    );

    redirectManager(
        "members.php?club_id=" .
        $clubId
    );
}


/*
|--------------------------------------------------------------------------
| 4. QUITTER UN CLUB
|--------------------------------------------------------------------------
*/

if (isset($_POST['leave_club'])) {

    $clubId =
        (int) (
            $_POST['club_id'] ?? 0
        );


    if ($clubId <= 0) {

        errorMessage(
            "Club invalide."
        );

        redirectManager();
    }


    /*
    | Vérifier membre
    */

    $stmt = mysqli_prepare(
        $con,
        "
        SELECT 1
        FROM club_members
        WHERE
            club_id = ?
            AND user_id = ?
        LIMIT 1
        "
    );


    if (!$stmt) {

        errorMessage(
            "Erreur."
        );

        redirectManager();
    }


    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $clubId,
        $managerId
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $member =
        mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);


    if (!$member) {

        errorMessage(
            "Vous n'êtes pas membre de ce club."
        );

        redirectManager();
    }


    /*
    | Un manager ne peut pas quitter son propre club
    */

    $club =
        getClubForManager(
            $con,
            $clubId,
            $managerId
        );


    if ($club) {

        errorMessage(
            "Le manager doit transférer la gestion du club avant de le quitter."
        );

        redirectManager(
            "club.php?id=" . $clubId
        );
    }


    $stmt = mysqli_prepare(
        $con,
        "
        DELETE FROM club_members
        WHERE
            club_id = ?
            AND user_id = ?
        "
    );


    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $clubId,
            $managerId
        );

        mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);
    }


    /*
    | Vérifier s'il reste des clubs
    */

    $stmt = mysqli_prepare(
        $con,
        "
        SELECT COUNT(*) AS total
        FROM club_members
        WHERE user_id = ?
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

        $data =
            mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);


        if (
            (int) $data['total'] === 0 &&
            ($_SESSION['role'] ?? '') === 'member'
        ) {

            $_SESSION['role'] = 'user';


            $stmt = mysqli_prepare(
                $con,
                "
                UPDATE users
                SET role = 'user'
                WHERE id = ?
                "
            );


            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $managerId
                );

                mysqli_stmt_execute($stmt);

                mysqli_stmt_close($stmt);
            }
        }
    }


    successMessage(
        "Vous avez quitté le club."
    );

    redirectManager();
}


/*
|--------------------------------------------------------------------------
| 5. ACTION INVALIDE
|--------------------------------------------------------------------------
*/

errorMessage(
    "Action invalide."
);

redirectManager();

?>