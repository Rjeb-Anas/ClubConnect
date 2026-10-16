<?php
session_start();

require_once __DIR__ . "/../config/database.php";

if (!isset($con) || !($con instanceof mysqli)) {
    die("Erreur de connexion à la base de données.");
}

mysqli_set_charset($con, "utf8mb4");

function retour($type, $message) {
    $_SESSION["popup"] = [
        "type" => $type,
        "message" => $message
    ];

    header("Location: ../clubs.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../clubs.php");
    exit;
}

$userId = (int)($_SESSION["id"] ?? 0);

if ($userId <= 0) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_POST["join_club"])) {
    retour("error", "Action invalide.");
}

$clubId = (int)($_POST["club_id"] ?? 0);

if ($clubId <= 0) {
    retour("error", "Identifiant du club invalide.");
}

/*
|--------------------------------------------------------------------------
| Vérifier le club
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare($con, "
    SELECT id, club_name, status, members_max
    FROM clubs
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Erreur SQL clubs : " . htmlspecialchars(mysqli_error($con)));
}

mysqli_stmt_bind_param($stmt, "i", $clubId);
mysqli_stmt_execute($stmt);

mysqli_stmt_bind_result(
    $stmt,
    $dbClubId,
    $clubName,
    $clubStatus,
    $membersMax
);

$exists = mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$exists) {
    retour("error", "Ce club n'existe pas.");
}

if (strtolower((string)$clubStatus) !== "active") {
    retour("error", "Ce club n'est pas actif.");
}

/*
|--------------------------------------------------------------------------
| Vérifier si déjà membre
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare($con, "
    SELECT id
    FROM club_members
    WHERE user_id = ?
      AND club_id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Erreur SQL club_members : " . htmlspecialchars(mysqli_error($con)));
}

mysqli_stmt_bind_param($stmt, "ii", $userId, $clubId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $existingId);

$alreadyMember = mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($alreadyMember) {
    retour("error", "Vous êtes déjà membre de ce club.");
}

/*
|--------------------------------------------------------------------------
| Vérifier la capacité
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare($con, "
    SELECT COUNT(*)
    FROM club_members
    WHERE club_id = ?
");

if (!$stmt) {
    die("Erreur SQL compteur : " . htmlspecialchars(mysqli_error($con)));
}

mysqli_stmt_bind_param($stmt, "i", $clubId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $memberCount);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$memberCount = (int)$memberCount;
$membersMax = (int)$membersMax;

if ($membersMax > 0 && $memberCount >= $membersMax) {
    retour("error", "Ce club a atteint sa capacité maximale.");
}

/*
|--------------------------------------------------------------------------
| Ajouter le membre
|--------------------------------------------------------------------------
|
| Structure actuelle utilisée :
| club_members(user_id, club_id)
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare($con, "
    INSERT INTO club_members
    (user_id, club_id)
    VALUES
    (?, ?)
");

if (!$stmt) {
    die(
        "Erreur SQL INSERT : " .
        htmlspecialchars(mysqli_error($con))
    );
}

mysqli_stmt_bind_param($stmt, "ii", $userId, $clubId);

if (!mysqli_stmt_execute($stmt)) {

    $error = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);

    die(
        "Erreur lors de l'adhésion : " .
        htmlspecialchars($error)
    );
}

mysqli_stmt_close($stmt);

retour(
    "success",
    "Vous avez rejoint le club « " . $clubName . " »."
);
?>