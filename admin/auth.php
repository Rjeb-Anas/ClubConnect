
<?php

/*
|--------------------------------------------------------------------------
| ClubConnect - Auth Admin
| admin/auth.php
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . "/../config/database.php";


/*
|--------------------------------------------------------------------------
| Récupérer l'ID depuis la session
|--------------------------------------------------------------------------
*/

$userId = $_SESSION['id']
    ?? $_SESSION['user_id']
    ?? null;

if (!$userId || !is_numeric($userId)) {
    header("Location: ../login.php");
    exit;
}

$userId = (int) $userId;


/*
|--------------------------------------------------------------------------
| Récupérer l'utilisateur
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        nom,
        prenom,
        email,
        role
    FROM users
    WHERE id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($con, $sql);

if (!$stmt) {
    die(
        "Erreur SQL : " .
        mysqli_error($con)
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $userId
);

if (!mysqli_stmt_execute($stmt)) {
    die(
        "Erreur SQL : " .
        mysqli_stmt_error($stmt)
    );
}

$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Utilisateur inexistant
|--------------------------------------------------------------------------
*/

if (!$user) {

    $_SESSION = [];

    session_destroy();

    header("Location: ../login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Vérifier le rôle
|--------------------------------------------------------------------------
*/

$role = strtolower(
    trim(
        (string) $user['role']
    )
);

if ($role !== "admin") {

    header("Location: ../index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Nom complet
|--------------------------------------------------------------------------
*/

$fullName = trim(
    $user['prenom'] . " " . $user['nom']
);


/*
|--------------------------------------------------------------------------
| Synchroniser la session
|--------------------------------------------------------------------------
*/

$_SESSION['id'] =
    (int) $user['id'];

$_SESSION['user_id'] =
    (int) $user['id'];

$_SESSION['name'] =
    $fullName;

$_SESSION['email'] =
    $user['email'];

$_SESSION['role'] =
    $role;


/*
|--------------------------------------------------------------------------
| Variables disponibles dans les pages admin
|--------------------------------------------------------------------------
*/

$currentUserId =
    (int) $user['id'];

$currentUserName =
    $fullName;

$currentUserEmail =
    $user['email'];

$currentUserRole =
    $role;


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

