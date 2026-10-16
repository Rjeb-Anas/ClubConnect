
<?php

/*
|--------------------------------------------------------------------------
| ClubConnect - Authentification étudiant
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| Configuration sécurité session
|--------------------------------------------------------------------------
|
| Ces paramètres doivent être définis AVANT session_start()
|
*/

if (session_status() === PHP_SESSION_NONE) {

    ini_set(
        'session.use_only_cookies',
        '1'
    );

    ini_set(
        'session.use_strict_mode',
        '1'
    );

    ini_set(
        'session.cookie_httponly',
        '1'
    );

    /*
     * En production avec HTTPS :
     *
     * ini_set('session.cookie_secure', '1');
     *
     * En localhost HTTP, ne pas l'activer.
     */

    session_start();
}


/*
|--------------------------------------------------------------------------
| Vérifier la session
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['id']) ||
    !is_numeric($_SESSION['id'])
) {

    header("Location: ../login.php");

    exit;
}


$userId = (int) $_SESSION['id'];


/*
|--------------------------------------------------------------------------
| Connexion database
|--------------------------------------------------------------------------
*/

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| Vérifier l'utilisateur en base
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $con,
    "SELECT
        id,
        nom,
        prenom,
        email,
        role,
        email_verified
     FROM users
     WHERE id = ?
     LIMIT 1"
);


if (!$stmt) {

    error_log(
        "ClubConnect Auth Prepare Error: " .
        mysqli_error($con)
    );

    session_unset();
    session_destroy();

    header("Location: ../login.php");

    exit;
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


/*
|--------------------------------------------------------------------------
| Utilisateur inexistant
|--------------------------------------------------------------------------
*/

if (!$user) {

    session_unset();
    session_destroy();

    header("Location: ../login.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| Email non vérifié
|--------------------------------------------------------------------------
*/

if ((int) $user['email_verified'] !== 1) {

    session_unset();
    session_destroy();

    header(
        "Location: ../login.php?verify=1&email=" .
        urlencode($user['email'])
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Vérification du rôle
|--------------------------------------------------------------------------
*/

$allowedRoles = [
    'user',
    'member',
    'club_manager'
];


if (!in_array(
    $user['role'],
    $allowedRoles,
    true
)) {

    if ($user['role'] === 'admin') {

        header(
            "Location: ../admin/dashboard.php"
        );

    } else {

        header(
            "Location: ../login.php"
        );
    }

    exit;
}


/*
|--------------------------------------------------------------------------
| Synchroniser la session
|--------------------------------------------------------------------------
*/

$_SESSION['id'] =
    (int) $user['id'];

$_SESSION['name'] =
    $user['prenom'] . ' ' . $user['nom'];

$_SESSION['email'] =
    $user['email'];

$_SESSION['role'] =
    $user['role'];


/*
|--------------------------------------------------------------------------
| Variables disponibles dans les pages
|--------------------------------------------------------------------------
*/

$currentUser =
    $user;

$currentUserId =
    (int) $user['id'];

$currentUserName =
    $user['prenom'] . ' ' . $user['nom'];

$currentUserEmail =
    $user['email'];

$currentUserRole =
    $user['role'];

?>
