<?php

/*
|--------------------------------------------------------------------------
| ClubConnect - manager/auth.php
|--------------------------------------------------------------------------
| Protection des pages manager.
| Seuls les utilisateurs :
|   - club_manager
|   - admin
| peuvent accéder à l'espace manager.
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Vérifier la connexion
|--------------------------------------------------------------------------
*/

if (
    empty($_SESSION['id']) ||
    !is_numeric($_SESSION['id'])
) {
    header("Location: ../login.php");
    exit;
}

$userId = (int) $_SESSION['id'];


/*
|--------------------------------------------------------------------------
| Vérifier le rôle
|--------------------------------------------------------------------------
*/

$role = $_SESSION['role'] ?? '';

if (
    $role !== 'club_manager' &&
    $role !== 'admin'
) {
    header("Location: ../student/index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Sécurité session
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_agent'])
) {
    $_SESSION['user_agent'] =
        $_SERVER['HTTP_USER_AGENT'] ?? '';
}

if (
    !hash_equals(
        (string) $_SESSION['user_agent'],
        (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')
    )
) {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    header("Location: ../login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Protection supplémentaire
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();
}


/*
|--------------------------------------------------------------------------
| Régénération périodique de l'ID de session
|--------------------------------------------------------------------------
*/

if (
    time() - (int) $_SESSION['login_time'] > 900
) {
    session_regenerate_id(true);
    $_SESSION['login_time'] = time();
}


/*
|--------------------------------------------------------------------------
| Variables disponibles dans les pages manager
|--------------------------------------------------------------------------
*/

$isManager =
    $role === 'club_manager';

$isAdmin =
    $role === 'admin';

$isManagerArea =
    $isManager || $isAdmin;


/*
|--------------------------------------------------------------------------
| Fonctions utiles
|--------------------------------------------------------------------------
*/

function managerRedirect(string $page): never
{
    header("Location: " . $page);
    exit;
}

function managerRequireManager(): void
{
    if (
        !isset($_SESSION['role']) ||
        $_SESSION['role'] !== 'club_manager'
    ) {
        if (
            ($_SESSION['role'] ?? '') === 'admin'
        ) {
            return;
        }

        header("Location: ../student/index.php");
        exit;
    }
}