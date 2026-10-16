<?php

/*
|--------------------------------------------------------------------------
| ClubConnect
| delete-contact-message.php
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Authentification
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/auth.php";


/*
|--------------------------------------------------------------------------
| Connexion BDD
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../config/database.php";


/*
|--------------------------------------------------------------------------
| Vérifier la méthode
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: contact-messages.php"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Récupérer l'ID
|--------------------------------------------------------------------------
*/

$id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);


/*
|--------------------------------------------------------------------------
| Vérifier l'ID
|--------------------------------------------------------------------------
*/

if (
    $id === false ||
    $id === null ||
    $id <= 0
) {

    $_SESSION['popup'] = [
        'type' => 'error',
        'message' => 'Message invalide.'
    ];

    header(
        "Location: contact-messages.php"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Vérifier que le message existe
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $con,
    "
    SELECT id
    FROM contact_messages
    WHERE id = ?
    LIMIT 1
    "
);


if (!$stmt) {

    $_SESSION['popup'] = [
        'type' => 'error',
        'message' => 'Erreur lors de la vérification du message.'
    ];

    header(
        "Location: contact-messages.php"
    );

    exit;
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);


if (!mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    $_SESSION['popup'] = [
        'type' => 'error',
        'message' => 'Impossible de vérifier le message.'
    ];

    header(
        "Location: contact-messages.php"
    );

    exit;
}


$result = mysqli_stmt_get_result($stmt);

$messageExists =
    $result &&
    mysqli_num_rows($result) > 0;


mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Message inexistant
|--------------------------------------------------------------------------
*/

if (!$messageExists) {

    $_SESSION['popup'] = [
        'type' => 'error',
        'message' => 'Ce message n’existe pas ou a déjà été supprimé.'
    ];

    header(
        "Location: contact-messages.php"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| SUPPRESSION
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $con,
    "
    DELETE FROM contact_messages
    WHERE id = ?
    LIMIT 1
    "
);


if (!$stmt) {

    $_SESSION['popup'] = [
        'type' => 'error',
        'message' => 'Impossible de préparer la suppression.'
    ];

    header(
        "Location: contact-messages.php"
    );

    exit;
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);


if (mysqli_stmt_execute($stmt)) {

    if (mysqli_stmt_affected_rows($stmt) > 0) {

        $_SESSION['popup'] = [
            'type' => 'success',
            'message' => 'Le message a été supprimé.'
        ];

    } else {

        $_SESSION['popup'] = [
            'type' => 'error',
            'message' => 'Le message n’a pas pu être supprimé.'
        ];
    }

} else {

    $_SESSION['popup'] = [
        'type' => 'error',
        'message' => 'Une erreur est survenue pendant la suppression.'
    ];
}


mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Retour
|--------------------------------------------------------------------------
*/

header(
    "Location: contact-messages.php"
);

exit;