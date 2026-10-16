<?php

/*
|--------------------------------------------------------------------------
| ClubConnect
| config/database.php
|--------------------------------------------------------------------------
| Connexion centralisée à MySQL
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
*/

$host = getenv("DB_HOST") ?: "localhost";

$username = getenv("DB_USER") ?: "your_db_user";

$password = getenv("DB_PASSWORD") ?: "";

/*
 * Mets ici le nom EXACT de ta base de données.
 */
$database = getenv("DB_NAME") ?: "your_database_name";


/*
|--------------------------------------------------------------------------
| Connexion
|--------------------------------------------------------------------------
*/

$con = mysqli_connect(
    $host,
    $username,
    $password,
    $database
);


/*
|--------------------------------------------------------------------------
| Vérifier la connexion
|--------------------------------------------------------------------------
*/

if (!$con) {

    die(
        "Erreur de connexion à la base de données : " .
        mysqli_connect_error()
    );
}


/*
|--------------------------------------------------------------------------
| Encodage
|--------------------------------------------------------------------------
*/

if (
    !mysqli_set_charset(
        $con,
        "utf8mb4"
    )
) {

    die(
        "Erreur lors de la configuration de l'encodage."
    );
}

?>

