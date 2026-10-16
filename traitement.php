<?php

/*
|--------------------------------------------------------------------------
| ClubConnect - traitement.php
|--------------------------------------------------------------------------
| Gestion :
| - Inscription
| - Connexion
| - Vérification email
| - Renvoyer le code
| - Mot de passe oublié
| - Contact
|--------------------------------------------------------------------------
*/

session_start();

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/PopUpMsg.php";
require_once __DIR__ . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


/*
|--------------------------------------------------------------------------
| CONFIGURATION SMTP
|--------------------------------------------------------------------------
*/

const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 587;

const SMTP_USERNAME = 'your_email@gmail.com';
const SMTP_PASSWORD = 'your_gmail_app_password';

const MAIL_FROM = 'your_email@gmail.com';
const MAIL_FROM_NAME = 'ClubConnect - FST Fès';


/*
|--------------------------------------------------------------------------
| URL DU SITE
|--------------------------------------------------------------------------
|
| En production :
| https://clubconnect.site.je
|
*/

const SITE_URL = 'https://clubconnect.site.je';


/*
|--------------------------------------------------------------------------
| FONCTIONS
|--------------------------------------------------------------------------
*/


/**
 * Nettoyer une entrée texte.
 */
function cleanInput(?string $value): string
{
    return trim($value ?? '');
}


/**
 * Vérifier une adresse email académique.
 */
function isAcademicEmail(string $email): bool
{
    return preg_match(
        '/^[A-Za-z0-9._%+-]+@usmba\.ac\.ma$/i',
        $email
    ) === 1;
}


/**
 * Redirection.
 */
function redirect(string $location): never
{
    header("Location: " . $location);
    exit;
}


/**
 * Redirection vers la vérification email.
 */
function redirectToVerification(string $email): never
{
    redirect(
        "verification.php?email=" .
        urlencode($email)
    );
}


/**
 * Envoi email avec PHPMailer.
 */
function sendEmail(
    string $to,
    string $subject,
    string $html
): bool {

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();

        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;

        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;

        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = SMTP_PORT;

        $mail->CharSet = 'UTF-8';

        $mail->setFrom(
            MAIL_FROM,
            MAIL_FROM_NAME
        );

        $mail->addAddress($to);

        $mail->isHTML(true);

        $mail->Subject = $subject;

        $mail->Body = $html;

        $mail->AltBody =
            strip_tags($html);

        return $mail->send();

    } catch (Exception $e) {

        error_log(
            "ClubConnect PHPMailer Error: " .
            $mail->ErrorInfo
        );

        return false;
    }
}


/**
 * Générer un code de vérification.
 */
function generateVerificationCode(): string
{
    return (string) random_int(
        100000,
        999999
    );
}


/**
 * Date d'expiration du code.
 */
function verificationExpiration(): string
{
    return date(
        'Y-m-d H:i:s',
        time() + 900
    );
}


/*
|--------------------------------------------------------------------------
| 1. INSCRIPTION
|--------------------------------------------------------------------------
*/

if (isset($_POST['register'])) {

    $nom = cleanInput(
        $_POST['nom'] ?? ''
    );

    $prenom = cleanInput(
        $_POST['prenom'] ?? ''
    );

    $sexe = cleanInput(
        $_POST['sexe'] ?? ''
    );

    $tel = cleanInput(
        $_POST['tel'] ?? ''
    );

    $email = strtolower(
        cleanInput($_POST['email'] ?? '')
    );

    $date_naissance = cleanInput(
        $_POST['date_naissance'] ?? ''
    );

    $password =
        $_POST['password'] ?? '';

    $password_confirm =
        $_POST['password_confirm'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | Validation nom
    |--------------------------------------------------------------------------
    */

    if (
        mb_strlen($nom) < 2 ||
        mb_strlen($nom) > 50 ||
        !preg_match(
            "/^[\p{L}\s'-]+$/u",
            $nom
        )
    ) {

        errorMessage(
            "Le nom est invalide."
        );

        redirect("register.php");
    }


    /*
    |--------------------------------------------------------------------------
    | Validation prénom
    |--------------------------------------------------------------------------
    */

    if (
        mb_strlen($prenom) < 2 ||
        mb_strlen($prenom) > 50 ||
        !preg_match(
            "/^[\p{L}\s'-]+$/u",
            $prenom
        )
    ) {

        errorMessage(
            "Le prénom est invalide."
        );

        redirect("register.php");
    }


    /*
    |--------------------------------------------------------------------------
    | Validation sexe
    |--------------------------------------------------------------------------
    */

    if (
        !in_array(
            $sexe,
            ['homme', 'femme'],
            true
        )
    ) {

        errorMessage(
            "Le sexe sélectionné est invalide."
        );

        redirect("register.php");
    }


    /*
    |--------------------------------------------------------------------------
    | Validation téléphone
    |--------------------------------------------------------------------------
    */

    if (
        !preg_match(
            '/^(06|07)[0-9]{8}$/',
            $tel
        )
    ) {

        errorMessage(
            "Le numéro de téléphone est invalide."
        );

        redirect("register.php");
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
        ) ||
        !isAcademicEmail($email)
    ) {

        errorMessage(
            "Utilisez une adresse académique @usmba.ac.ma."
        );

        redirect("register.php");
    }


    /*
    |--------------------------------------------------------------------------
    | Validation date
    |--------------------------------------------------------------------------
    */

    $dateObject =
        DateTime::createFromFormat(
            'Y-m-d',
            $date_naissance
        );

    if (
        !$dateObject ||
        $dateObject->format('Y-m-d')
        !== $date_naissance
    ) {

        errorMessage(
            "La date de naissance est invalide."
        );

        redirect("register.php");
    }


    if (
        $date_naissance > date('Y-m-d')
    ) {

        errorMessage(
            "La date de naissance est invalide."
        );

        redirect("register.php");
    }


    /*
    |--------------------------------------------------------------------------
    | Validation mot de passe
    |--------------------------------------------------------------------------
    */

    if (strlen($password) < 8) {

        errorMessage(
            "Le mot de passe doit contenir au moins 8 caractères."
        );

        redirect("register.php");
    }


    if (!preg_match('/[A-Z]/', $password)) {

        errorMessage(
            "Le mot de passe doit contenir une majuscule."
        );

        redirect("register.php");
    }


    if (!preg_match('/[a-z]/', $password)) {

        errorMessage(
            "Le mot de passe doit contenir une minuscule."
        );

        redirect("register.php");
    }


    if (!preg_match('/[0-9]/', $password)) {

        errorMessage(
            "Le mot de passe doit contenir un chiffre."
        );

        redirect("register.php");
    }


    if (
        $password !== $password_confirm
    ) {

        errorMessage(
            "Les deux mots de passe ne correspondent pas."
        );

        redirect("register.php");
    }


    /*
    |--------------------------------------------------------------------------
    | Vérifier email existant
    |--------------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $con,
        "
        SELECT id
        FROM users
        WHERE email = ?
        LIMIT 1
        "
    );

    if (!$stmt) {

        errorMessage(
            "Erreur lors de la vérification."
        );

        redirect("register.php");
    }


    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $email
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $existingUser =
        mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);


    if ($existingUser) {

        errorMessage(
            "Cette adresse email est déjà utilisée."
        );

        redirect("register.php");
    }


    /*
    |--------------------------------------------------------------------------
    | Génération sécurité
    |--------------------------------------------------------------------------
    */

    $passwordHash =
        password_hash(
            $password,
            PASSWORD_DEFAULT
        );

    $verificationCode =
        generateVerificationCode();

    $verificationExpires =
        verificationExpiration();


    /*
    |--------------------------------------------------------------------------
    | Création utilisateur
    |--------------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $con,
        "
        INSERT INTO users
        (
            nom,
            prenom,
            sexe,
            tel,
            email,
            date_naissance,
            password,
            role,
            email_verified,
            verification_code,
            verification_expires
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            'user',
            0,
            ?,
            ?
        )
        "
    );


    if (!$stmt) {

        error_log(
            mysqli_error($con)
        );

        errorMessage(
            "Impossible de créer le compte."
        );

        redirect("register.php");
    }


    mysqli_stmt_bind_param(
        $stmt,
        "sssssssss",
        $nom,
        $prenom,
        $sexe,
        $tel,
        $email,
        $date_naissance,
        $passwordHash,
        $verificationCode,
        $verificationExpires
    );


    if (
        !mysqli_stmt_execute($stmt)
    ) {

        error_log(
            mysqli_stmt_error($stmt)
        );

        mysqli_stmt_close($stmt);

        errorMessage(
            "Impossible de créer le compte."
        );

        redirect("register.php");
    }


    mysqli_stmt_close($stmt);


    /*
    |--------------------------------------------------------------------------
    | Email de vérification
    |--------------------------------------------------------------------------
    */

    $safeName =
        htmlspecialchars(
            $prenom,
            ENT_QUOTES,
            'UTF-8'
        );


    $emailBody = "
    <div style=\"
        font-family:Arial,sans-serif;
        max-width:600px;
        margin:auto;
        padding:30px;
        color:#334155;
    \">

        <h2 style=\"color:#0e7490;\">
            Bienvenue sur ClubConnect
        </h2>

        <p>
            Bonjour {$safeName},
        </p>

        <p>
            Merci pour votre inscription à ClubConnect.
        </p>

        <p>
            Votre code de vérification est :
        </p>

        <div style=\"
            background:#ecfeff;
            color:#0e7490;
            font-size:32px;
            font-weight:bold;
            letter-spacing:8px;
            text-align:center;
            padding:20px;
            border-radius:12px;
        \">
            {$verificationCode}
        </div>

        <p>
            Ce code expire dans 15 minutes.
        </p>

        <p>
            Si vous n'êtes pas à l'origine de cette inscription,
            ignorez cet email.
        </p>

        <p>
            ClubConnect - FST Fès
        </p>

    </div>
    ";


    if (
        !sendEmail(
            $email,
            "Vérification de votre compte ClubConnect",
            $emailBody
        )
    ) {

        errorMessage(
            "Compte créé, mais l'email de vérification n'a pas pu être envoyé."
        );

        redirect("login.php");
    }


    successMessage(
        "Compte créé. Consultez votre email pour obtenir le code."
    );


    redirectToVerification($email);
}


/*
|--------------------------------------------------------------------------
| 2. CONNEXION
|--------------------------------------------------------------------------
*/

if (isset($_POST['login'])) {

    $email = strtolower(
        cleanInput($_POST['email'] ?? '')
    );

    $password =
        $_POST['password'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        ) ||
        !isAcademicEmail($email) ||
        $password === ''
    ) {

        errorMessage(
            "Email ou mot de passe incorrect."
        );

        redirect("login.php");
    }


    /*
    |--------------------------------------------------------------------------
    | Recherche utilisateur
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
            password,
            role,
            email_verified
        FROM users
        WHERE email = ?
        LIMIT 1
        "
    );


    if (!$stmt) {

        errorMessage(
            "Erreur de connexion."
        );

        redirect("login.php");
    }


    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $email
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $user =
        mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);


    if (!$user) {

        errorMessage(
            "Email ou mot de passe incorrect."
        );

        redirect("login.php");
    }


    /*
    |--------------------------------------------------------------------------
    | Password
    |--------------------------------------------------------------------------
    */

    if (
        !password_verify(
            $password,
            $user['password']
        )
    ) {

        errorMessage(
            "Email ou mot de passe incorrect."
        );

        redirect("login.php");
    }


    /*
    |--------------------------------------------------------------------------
    | Vérification email
    |--------------------------------------------------------------------------
    */

    if (
        (int) $user['email_verified'] !== 1
    ) {

        redirect(
            "login.php?verify=1&email=" .
            urlencode($user['email'])
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Session
    |--------------------------------------------------------------------------
    */

    session_regenerate_id(true);

    $_SESSION['id'] =
        (int) $user['id'];

    $_SESSION['name'] =
        $user['prenom'] .
        ' ' .
        $user['nom'];

    $_SESSION['email'] =
        $user['email'];

    $_SESSION['role'] =
        $user['role'];


    /*
    |--------------------------------------------------------------------------
    | Redirection rôle
    |--------------------------------------------------------------------------
    */

    switch ($user['role']) {

        case 'admin':

            redirect(
                "admin/dashboard.php"
            );

        case 'club_manager':

            redirect(
                "manager/dashboard.php"
            );

        case 'member':
        case 'user':
        default:

            redirect(
                "student/index.php"
            );
    }
}


/*
|--------------------------------------------------------------------------
| 3. VÉRIFICATION EMAIL
|--------------------------------------------------------------------------
*/

if (
    isset($_POST['verify_email'])
) {

    $email = strtolower(
        cleanInput($_POST['email'] ?? '')
    );

    $code = cleanInput(
        $_POST['verification_code'] ?? ''
    );


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

        errorMessage(
            "Adresse email invalide."
        );

        redirectToVerification($email);
    }


    /*
    |--------------------------------------------------------------------------
    | Validation code
    |--------------------------------------------------------------------------
    */

    if (
        !preg_match(
            '/^[0-9]{6}$/',
            $code
        )
    ) {

        errorMessage(
            "Le code doit contenir exactement 6 chiffres."
        );

        redirectToVerification($email);
    }


    /*
    |--------------------------------------------------------------------------
    | Recherche utilisateur
    |--------------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $con,
        "
        SELECT
            id,
            email,
            email_verified,
            verification_code,
            verification_expires
        FROM users
        WHERE email = ?
        LIMIT 1
        "
    );


    if (!$stmt) {

        errorMessage(
            "Erreur lors de la vérification."
        );

        redirectToVerification($email);
    }


    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $email
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $user =
        mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);


    if (!$user) {

        errorMessage(
            "Aucun compte associé à cette adresse."
        );

        redirect("register.php");
    }


    /*
    |--------------------------------------------------------------------------
    | Déjà vérifié
    |--------------------------------------------------------------------------
    */

    if (
        (int) $user['email_verified'] === 1
    ) {

        successMessage(
            "Votre email est déjà vérifié."
        );

        redirect("login.php");
    }


    /*
    |--------------------------------------------------------------------------
    | Vérification code
    |--------------------------------------------------------------------------
    */

    if (
        empty($user['verification_code']) ||
        !hash_equals(
            (string) $user['verification_code'],
            (string) $code
        )
    ) {

        errorMessage(
            "Code de vérification incorrect."
        );

        redirectToVerification($email);
    }


    /*
    |--------------------------------------------------------------------------
    | Vérification expiration
    |--------------------------------------------------------------------------
    */

    if (
        empty($user['verification_expires']) ||
        strtotime(
            $user['verification_expires']
        ) < time()
    ) {

        errorMessage(
            "Le code a expiré. Demandez un nouveau code."
        );

        redirectToVerification($email);
    }


    /*
    |--------------------------------------------------------------------------
    | Validation définitive
    |--------------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $con,
        "
        UPDATE users
        SET
            email_verified = 1,
            verification_code = NULL,
            verification_expires = NULL
        WHERE id = ?
        "
    );


    if (!$stmt) {

        errorMessage(
            "Impossible de vérifier votre email."
        );

        redirectToVerification($email);
    }


    $userId =
        (int) $user['id'];


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $userId
    );


    if (
        !mysqli_stmt_execute($stmt)
    ) {

        mysqli_stmt_close($stmt);

        errorMessage(
            "Impossible de vérifier votre email."
        );

        redirectToVerification($email);
    }


    mysqli_stmt_close($stmt);


    /*
    |--------------------------------------------------------------------------
    | Succès
    |--------------------------------------------------------------------------
    */

    successMessage(
        "Votre adresse email a été vérifiée. Vous pouvez maintenant vous connecter."
    );


    redirect("login.php");
}


/*
|--------------------------------------------------------------------------
| 4. RENVOYER LE CODE
|--------------------------------------------------------------------------
|
| Accepte :
| - resend_verification
| - resend_code
|--------------------------------------------------------------------------
*/

if (
    isset($_POST['resend_verification']) ||
    isset($_POST['resend_code'])
) {

    $email = strtolower(
        cleanInput($_POST['email'] ?? '')
    );


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        ) ||
        !isAcademicEmail($email)
    ) {

        errorMessage(
            "Adresse email invalide."
        );

        redirect("login.php");
    }


    /*
    |--------------------------------------------------------------------------
    | Recherche
    |--------------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $con,
        "
        SELECT
            id,
            prenom,
            email_verified
        FROM users
        WHERE email = ?
        LIMIT 1
        "
    );


    if (!$stmt) {

        errorMessage(
            "Erreur lors de la vérification."
        );

        redirect("login.php");
    }


    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $email
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $user =
        mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);


    if (!$user) {

        errorMessage(
            "Aucun compte associé à cette adresse."
        );

        redirect("login.php");
    }


    /*
    |--------------------------------------------------------------------------
    | Déjà vérifié
    |--------------------------------------------------------------------------
    */

    if (
        (int) $user['email_verified'] === 1
    ) {

        infoMessage(
            "Cette adresse email est déjà vérifiée."
        );

        redirect("login.php");
    }


    /*
    |--------------------------------------------------------------------------
    | Nouveau code
    |--------------------------------------------------------------------------
    */

    $verificationCode =
        generateVerificationCode();

    $verificationExpires =
        verificationExpiration();


    /*
    |--------------------------------------------------------------------------
    | Mise à jour
    |--------------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $con,
        "
        UPDATE users
        SET
            verification_code = ?,
            verification_expires = ?
        WHERE id = ?
        "
    );


    if (!$stmt) {

        errorMessage(
            "Impossible de générer un nouveau code."
        );

        redirectToVerification($email);
    }


    $userId =
        (int) $user['id'];


    mysqli_stmt_bind_param(
        $stmt,
        "ssi",
        $verificationCode,
        $verificationExpires,
        $userId
    );


    if (
        !mysqli_stmt_execute($stmt)
    ) {

        mysqli_stmt_close($stmt);

        errorMessage(
            "Impossible de générer un nouveau code."
        );

        redirectToVerification($email);
    }


    mysqli_stmt_close($stmt);


    /*
    |--------------------------------------------------------------------------
    | Email
    |--------------------------------------------------------------------------
    */

    $safeName =
        htmlspecialchars(
            $user['prenom'],
            ENT_QUOTES,
            'UTF-8'
        );


    $emailBody = "
    <div style=\"
        font-family:Arial,sans-serif;
        max-width:600px;
        margin:auto;
        padding:30px;
        color:#334155;
    \">

        <h2 style=\"color:#0e7490;\">
            Nouveau code de vérification
        </h2>

        <p>
            Bonjour {$safeName},
        </p>

        <p>
            Voici votre nouveau code :
        </p>

        <div style=\"
            background:#ecfeff;
            color:#0e7490;
            font-size:32px;
            font-weight:bold;
            letter-spacing:8px;
            text-align:center;
            padding:20px;
            border-radius:12px;
        \">
            {$verificationCode}
        </div>

        <p>
            Ce code expire dans 15 minutes.
        </p>

        <p>
            ClubConnect - FST Fès
        </p>

    </div>
    ";


    if (
        !sendEmail(
            $email,
            "Nouveau code de vérification - ClubConnect",
            $emailBody
        )
    ) {

        errorMessage(
            "Impossible d'envoyer le code de vérification."
        );

        redirectToVerification($email);
    }


    successMessage(
        "Un nouveau code de vérification a été envoyé."
    );


    redirectToVerification($email);
}

if (isset($_POST['send_contact'])) {

    if (
        empty($_SESSION['id']) &&
        empty($_SESSION['user_id'])
    ) {

        $_SESSION['popup'] = [
            'type' => 'error',
            'message' => 'Vous devez être connecté pour envoyer un message.'
        ];

        header("Location: contact.php");
        exit;
    }

    $userId = (int)(
        $_SESSION['id']
        ?? $_SESSION['user_id']
    );

    $stmt = mysqli_prepare(
        $con,
        "
        SELECT
            nom,
            prenom,
            email
        FROM users
        WHERE id = ?
        LIMIT 1
        "
    );

    if (!$stmt) {
        die("Erreur SQL.");
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $userId
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $account = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$account) {

        $_SESSION['popup'] = [
            'type' => 'error',
            'message' => 'Compte introuvable.'
        ];

        header("Location: contact.php");
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Les informations viennent de la BDD
    |--------------------------------------------------------------------------
    */

    $nom = trim($account['nom']);
    $prenom = trim($account['prenom']);
    $email = trim($account['email']);

    /*
    |--------------------------------------------------------------------------
    | Seuls ces champs viennent du formulaire
    |--------------------------------------------------------------------------
    */

    $subject = trim(
        $_POST['subject'] ?? ''
    );

    $message = trim(
        $_POST['message'] ?? ''
    );

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($subject === '') {

        $_SESSION['popup'] = [
            'type' => 'error',
            'message' => 'Veuillez sélectionner un sujet.'
        ];

        header("Location: contact.php");
        exit;
    }

    if (
        mb_strlen($message) < 10 ||
        mb_strlen($message) > 2000
    ) {

        $_SESSION['popup'] = [
            'type' => 'error',
            'message' => 'Le message doit contenir entre 10 et 2000 caractères.'
        ];

        header("Location: contact.php");
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | INSERT
    |--------------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $con,
        "
        INSERT INTO contact_messages
        (
            user_id,
            nom,
            prenom,
            email,
            subject,
            message
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
        "
    );

    if (!$stmt) {
        die("Erreur SQL.");
    }

    mysqli_stmt_bind_param(
        $stmt,
        "isssss",
        $userId,
        $nom,
        $prenom,
        $email,
        $subject,
        $message
    );

    if (!mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        $_SESSION['popup'] = [
            'type' => 'error',
            'message' => 'Impossible d’envoyer votre message.'
        ];

        header("Location: contact.php");
        exit;
    }

    mysqli_stmt_close($stmt);

    $_SESSION['popup'] = [
        'type' => 'success',
        'message' => 'Votre message a été envoyé à l’administration.'
    ];

    header("Location: contact.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| 5. MOT DE PASSE OUBLIÉ
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| 6. MODIFICATION DU PROFIL
|--------------------------------------------------------------------------
*/

if (isset($_POST['update_profile'])) {

    $userId = (int)($_SESSION['id'] ?? 0);

    if ($userId <= 0) {
        errorMessage("Session invalide.");
        redirect("login.php");
    }

    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $sexe = trim($_POST['sexe'] ?? '');
    $tel = trim($_POST['tel'] ?? '');
    $date_naissance = trim(
        $_POST['date_naissance'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | Nom
    |--------------------------------------------------------------------------
    */

    if (
        mb_strlen($nom) < 2 ||
        mb_strlen($nom) > 50 ||
        !preg_match(
            "/^[\p{L}\s'-]+$/u",
            $nom
        )
    ) {
        errorMessage("Le nom est invalide.");
        redirect("profile.php");
    }


    /*
    |--------------------------------------------------------------------------
    | Prénom
    |--------------------------------------------------------------------------
    */

    if (
        mb_strlen($prenom) < 2 ||
        mb_strlen($prenom) > 50 ||
        !preg_match(
            "/^[\p{L}\s'-]+$/u",
            $prenom
        )
    ) {
        errorMessage("Le prénom est invalide.");
        redirect("profile.php");
    }


    /*
    |--------------------------------------------------------------------------
    | Sexe
    |--------------------------------------------------------------------------
    */

    if (
        !in_array(
            $sexe,
            ['Homme', 'Femme'],
            true
        )
    ) {
        errorMessage(
            "Veuillez sélectionner un sexe valide."
        );

        redirect("profile.php");
    }


    /*
    |--------------------------------------------------------------------------
    | Téléphone
    |--------------------------------------------------------------------------
    */

    if (
        !preg_match(
            '/^(06|07)[0-9]{8}$/',
            $tel
        )
    ) {
        errorMessage(
            "Le numéro de téléphone est invalide."
        );

        redirect("profile.php");
    }


    /*
    |--------------------------------------------------------------------------
    | Date de naissance
    |--------------------------------------------------------------------------
    */

    $dateObject =
        DateTime::createFromFormat(
            'Y-m-d',
            $date_naissance
        );

    if (
        !$dateObject ||
        $dateObject->format('Y-m-d')
        !== $date_naissance
    ) {
        errorMessage(
            "La date de naissance est invalide."
        );

        redirect("profile.php");
    }


    if (
        $dateObject > new DateTime('today')
    ) {
        errorMessage(
            "La date de naissance ne peut pas être dans le futur."
        );

        redirect("profile.php");
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $con,
        "
        UPDATE users
        SET
            nom = ?,
            prenom = ?,
            sexe = ?,
            tel = ?,
            date_naissance = ?
        WHERE id = ?
        LIMIT 1
        "
    );


    if (!$stmt) {
        errorMessage(
            "Erreur lors de la préparation de la mise à jour."
        );

        redirect("profile.php");
    }


    mysqli_stmt_bind_param(
        $stmt,
        "sssssi",
        $nom,
        $prenom,
        $sexe,
        $tel,
        $date_naissance,
        $userId
    );


    if (!mysqli_stmt_execute($stmt)) {

        error_log(
            "Profile update error: " .
            mysqli_stmt_error($stmt)
        );

        mysqli_stmt_close($stmt);

        errorMessage(
            "Impossible de modifier le profil."
        );

        redirect("profile.php");
    }


    mysqli_stmt_close($stmt);


    /*
    |--------------------------------------------------------------------------
    | Mettre aussi à jour la session
    |--------------------------------------------------------------------------
    */

    $_SESSION['nom'] = $nom;
    $_SESSION['prenom'] = $prenom;
    $_SESSION['sexe'] = $sexe;
    $_SESSION['tel'] = $tel;


    successMessage(
        "Votre profil a été mis à jour."
    );

    redirect("profile.php");
}


/*
|--------------------------------------------------------------------------
| 7. CHANGEMENT DU MOT DE PASSE
|--------------------------------------------------------------------------
*/

if (isset($_POST['change_password'])) {

    if (
        !isset($_SESSION['id']) ||
        !is_numeric($_SESSION['id'])
    ) {
        redirect("login.php");
    }

    $userId = (int) $_SESSION['id'];

    $currentPassword =
        $_POST['current_password'] ?? '';

    $newPassword =
        $_POST['new_password'] ?? '';

    $confirmPassword =
        $_POST['confirm_password'] ?? '';

    if (
        $currentPassword === '' ||
        $newPassword === '' ||
        $confirmPassword === ''
    ) {
        errorMessage(
            "Tous les champs du mot de passe sont obligatoires."
        );

        redirect("profile.php");
    }

    $stmt = mysqli_prepare(
        $con,
        "
        SELECT password
        FROM users
        WHERE id = ?
        LIMIT 1
        "
    );

    if (!$stmt) {
        errorMessage(
            "Impossible de vérifier votre mot de passe."
        );

        redirect("profile.php");
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

    if (
        !$user ||
        !password_verify(
            $currentPassword,
            $user['password']
        )
    ) {
        errorMessage(
            "Votre mot de passe actuel est incorrect."
        );

        redirect("profile.php");
    }

    if (strlen($newPassword) < 8) {
        errorMessage(
            "Le nouveau mot de passe doit contenir au moins 8 caractères."
        );

        redirect("profile.php");
    }

    if (!preg_match('/[A-Z]/', $newPassword)) {
        errorMessage(
            "Le nouveau mot de passe doit contenir une majuscule."
        );

        redirect("profile.php");
    }

    if (!preg_match('/[a-z]/', $newPassword)) {
        errorMessage(
            "Le nouveau mot de passe doit contenir une minuscule."
        );

        redirect("profile.php");
    }

    if (!preg_match('/[0-9]/', $newPassword)) {
        errorMessage(
            "Le nouveau mot de passe doit contenir un chiffre."
        );

        redirect("profile.php");
    }

    if ($newPassword !== $confirmPassword) {
        errorMessage(
            "Les deux mots de passe ne correspondent pas."
        );

        redirect("profile.php");
    }

    if ($newPassword === $currentPassword) {
        errorMessage(
            "Le nouveau mot de passe doit être différent de l'ancien."
        );

        redirect("profile.php");
    }

    $passwordHash =
        password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );

    $stmt = mysqli_prepare(
        $con,
        "
        UPDATE users
        SET password = ?
        WHERE id = ?
        "
    );

    if (!$stmt) {
        errorMessage(
            "Impossible de modifier le mot de passe."
        );

        redirect("profile.php");
    }

    mysqli_stmt_bind_param(
        $stmt,
        "si",
        $passwordHash,
        $userId
    );

    if (!mysqli_stmt_execute($stmt)) {
        error_log(mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);

        errorMessage(
            "Impossible de modifier le mot de passe."
        );

        redirect("profile.php");
    }

    mysqli_stmt_close($stmt);

    successMessage(
        "Votre mot de passe a été modifié avec succès."
    );

    redirect("profile.php");
}


/*
|--------------------------------------------------------------------------
| 8. CRÉATION D'UN CLUB
|--------------------------------------------------------------------------
|
| create-club.php est dans /student/ et utilise :
| action="traitement.php"
|
*/

if (isset($_POST['create_club'])) {

    if (
        !isset($_SESSION['id']) ||
        !is_numeric($_SESSION['id'])
    ) {
        redirect("login.php");
    }

    $userId = (int) $_SESSION['id'];

    $clubName =
        cleanInput($_POST['club_name'] ?? '');

    $category =
        cleanInput($_POST['category'] ?? '');

    $description =
        cleanInput($_POST['description'] ?? '');

    $membersMax =
        (int) ($_POST['members_max'] ?? 0);

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
        mb_strlen($clubName) < 2 ||
        mb_strlen($clubName) > 150
    ) {
        errorMessage(
            "Le nom du club est invalide."
        );

        redirect("student/create-club.php");
    }

    if (
        !in_array(
            $category,
            $allowedCategories,
            true
        )
    ) {
        errorMessage(
            "La catégorie du club est invalide."
        );

        redirect("student/create-club.php");
    }

    if (
        mb_strlen($description) < 10 ||
        mb_strlen($description) > 5000
    ) {
        errorMessage(
            "La description doit contenir entre 10 et 5000 caractères."
        );

        redirect("student/create-club.php");
    }

    if (
        $membersMax < 2 ||
        $membersMax > 10000
    ) {
        errorMessage(
            "Le nombre maximum de membres est invalide."
        );

        redirect("student/create-club.php");
    }

    $logoPath = null;

    if (
        isset($_FILES['logo']) &&
        $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if (
            $_FILES['logo']['error'] !== UPLOAD_ERR_OK
        ) {
            errorMessage(
                "Erreur lors de l'envoi du logo."
            );

            redirect("student/create-club.php");
        }

        if (
            $_FILES['logo']['size'] > 2 * 1024 * 1024
        ) {
            errorMessage(
                "Le logo ne doit pas dépasser 2 Mo."
            );

            redirect("student/create-club.php");
        }

        $tmpName =
            $_FILES['logo']['tmp_name'];

        $mime =
            mime_content_type($tmpName);

        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        if (!isset($allowedMimes[$mime])) {
            errorMessage(
                "Le logo doit être une image JPG, PNG ou WEBP."
            );

            redirect("student/create-club.php");
        }

        $uploadDir =
            __DIR__ . "/uploads/clubs/";

        if (
            !is_dir($uploadDir) &&
            !mkdir($uploadDir, 0755, true)
        ) {
            errorMessage(
                "Impossible de créer le dossier des logos."
            );

            redirect("student/create-club.php");
        }

        $fileName =
            bin2hex(random_bytes(16)) .
            '.' .
            $allowedMimes[$mime];

        $destination =
            $uploadDir . $fileName;

        if (
            !move_uploaded_file(
                $tmpName,
                $destination
            )
        ) {
            errorMessage(
                "Impossible d'enregistrer le logo."
            );

            redirect("student/create-club.php");
        }

        $logoPath =
            "uploads/clubs/" . $fileName;
    }

    $stmt = mysqli_prepare(
        $con,
        "
        INSERT INTO clubs
        (
            club_name,
            category,
            description,
            logo,
            members_max,
            status,
            manager_id,
            created_by
        )
        VALUES
        (
            ?, ?, ?, ?, ?,
            'pending',
            ?,
            ?
        )
        "
    );

    if (!$stmt) {

        if ($logoPath) {
            @unlink(
                __DIR__ . "/" . $logoPath
            );
        }

        error_log(mysqli_error($con));

        errorMessage(
            "Impossible de créer le club."
        );

        redirect("student/create-club.php");
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ssssiii",
        $clubName,
        $category,
        $description,
        $logoPath,
        $membersMax,
        $userId,
        $userId
    );

    if (!mysqli_stmt_execute($stmt)) {

        if ($logoPath) {
            @unlink(
                __DIR__ . "/" . $logoPath
            );
        }

        error_log(
            mysqli_stmt_error($stmt)
        );

        mysqli_stmt_close($stmt);

        errorMessage(
            "Impossible de créer le club."
        );

        redirect("student/create-club.php");
    }

    mysqli_stmt_close($stmt);

    successMessage(
        "Votre demande de création de club a été envoyée à l'administration."
    );

    redirect("clubs.php");
}



/*
|--------------------------------------------------------------------------
| 9. CONTACT
|--------------------------------------------------------------------------
*/




if (isset($_POST["forgot_password"])) {

    /*
    |--------------------------------------------------------------------------
    | 1. EMAIL
    |--------------------------------------------------------------------------
    */

    $email = trim(
        (string)($_POST["email"] ?? "")
    );


    /*
    |--------------------------------------------------------------------------
    | 2. VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $email === "" ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $_SESSION["forgot_error"] =
            "Veuillez entrer une adresse email valide.";

        header("Location: forgot-password.php");
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | 3. RECHERCHER LE COMPTE
    |--------------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $con,
        "
        SELECT
            id,
            email
        FROM users
        WHERE email = ?
        LIMIT 1
        "
    );


    if (!$stmt) {

        $_SESSION["forgot_error"] =
            "Erreur lors de la recherche du compte.";

        header("Location: forgot-password.php");
        exit;
    }


    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $email
    );


    mysqli_stmt_execute($stmt);


    $result =
        mysqli_stmt_get_result($stmt);


    $user =
        mysqli_fetch_assoc($result);


    mysqli_stmt_close($stmt);


    /*
    |--------------------------------------------------------------------------
    | 4. COMPTE INTROUVABLE
    |--------------------------------------------------------------------------
    */

    if (!$user) {

        /*
         * On ne révèle pas si l'adresse existe.
         */

        $_SESSION["forgot_success"] =
            "Si cette adresse possède un compte, "
            . "un lien de réinitialisation sera envoyé.";

        header("Location: forgot-password.php");
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | 5. GÉNÉRER LE TOKEN
    |--------------------------------------------------------------------------
    */

    try {

        $token =
            bin2hex(
                random_bytes(32)
            );

    } catch (Throwable $e) {

        $_SESSION["forgot_error"] =
            "Impossible de générer le lien.";

        header("Location: forgot-password.php");
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | 6. EXPIRATION : 1 HEURE
    |--------------------------------------------------------------------------
    */

    $expires =
        date(
            "Y-m-d H:i:s",
            time() + 3600
        );


    /*
    |--------------------------------------------------------------------------
    | 7. ENREGISTRER LE TOKEN
    |--------------------------------------------------------------------------
    */

    $userId =
        (int)$user["id"];


    $stmt = mysqli_prepare(
        $con,
        "
        UPDATE users
        SET
            reset_token = ?,
            reset_token_expires = ?
        WHERE id = ?
        LIMIT 1
        "
    );


    if (!$stmt) {

        $_SESSION["forgot_error"] =
            "Erreur lors de la création du lien.";

        header("Location: forgot-password.php");
        exit;
    }


    mysqli_stmt_bind_param(
        $stmt,
        "ssi",
        $token,
        $expires,
        $userId
    );


    if (!mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        $_SESSION["forgot_error"] =
            "Impossible d'enregistrer le lien.";

        header("Location: forgot-password.php");
        exit;
    }


    mysqli_stmt_close($stmt);


    /*
    |--------------------------------------------------------------------------
    | 8. CRÉER LE LIEN
    |--------------------------------------------------------------------------
    */

    $link =
        "https://clubconnect.site.je/reset-password.php?token="
        . urlencode($token);


    /*
    |--------------------------------------------------------------------------
    | 9. CHARGER PHPMailer
    |--------------------------------------------------------------------------
    */

    $autoload =
        __DIR__ . "/vendor/autoload.php";


    if (!file_exists($autoload)) {

        /*
         * Supprimer le token si PHPMailer
         * n'est pas installé.
         */

        $stmt = mysqli_prepare(
            $con,
            "
            UPDATE users
            SET
                reset_token = NULL,
                reset_token_expires = NULL
            WHERE id = ?
            LIMIT 1
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


        $_SESSION["forgot_error"] =
            "PHPMailer est introuvable sur le serveur.";

        header("Location: forgot-password.php");
        exit;
    }


    require_once $autoload;


    /*
    |--------------------------------------------------------------------------
    | 10. CRÉER PHPMailer
    |--------------------------------------------------------------------------
    */

    try {

        $mail =
            new \PHPMailer\PHPMailer\PHPMailer(true);


        /*
        |--------------------------------------------------------------------------
        | SMTP
        |--------------------------------------------------------------------------
        */

        $mail->isSMTP();

        $mail->Host =
            SMTP_HOST;

        $mail->SMTPAuth =
            true;

        $mail->Username =
            SMTP_USERNAME;

        $mail->Password =
            SMTP_PASSWORD;

        $mail->SMTPSecure =
            \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port =
            SMTP_PORT;

        $mail->CharSet =
            "UTF-8";


        /*
        |--------------------------------------------------------------------------
        | EXPÉDITEUR
        |--------------------------------------------------------------------------
        */

        $mail->setFrom(
            MAIL_FROM,
            MAIL_FROM_NAME
        );


        /*
        |--------------------------------------------------------------------------
        | DESTINATAIRE
        |--------------------------------------------------------------------------
        */

        $mail->addAddress(
            $email
        );


        /*
        |--------------------------------------------------------------------------
        | EMAIL HTML
        |--------------------------------------------------------------------------
        */

        $mail->isHTML(true);


        $mail->Subject =
            "Réinitialisation du mot de passe - ClubConnect";


        $safeLink =
            htmlspecialchars(
                $link,
                ENT_QUOTES,
                "UTF-8"
            );


        /*
        |--------------------------------------------------------------------------
        | CORPS HTML
        |--------------------------------------------------------------------------
        */

        $mail->Body = <<<HTML

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Réinitialisation du mot de passe
    </title>

</head>


<body
    style="
        margin:0;
        padding:30px 15px;
        background:#f1f5f9;
        font-family:Arial,Helvetica,sans-serif;
    "
>


<div
    style="
        max-width:600px;
        margin:0 auto;
        background:#ffffff;
        border-radius:20px;
        padding:35px;
        box-sizing:border-box;
    "
>


    <!-- LOGO -->

    <div
        style="
            width:60px;
            height:60px;
            background:#0891b2;
            color:#ffffff;
            border-radius:16px;
            text-align:center;
            line-height:60px;
            font-size:20px;
            font-weight:bold;
        "
    >
        CC
    </div>


    <!-- TITRE -->

    <h1
        style="
            color:#0f172a;
            font-size:25px;
            margin:25px 0 10px;
        "
    >
        Réinitialisation du mot de passe
    </h1>


    <p
        style="
            color:#475569;
            font-size:15px;
            line-height:1.7;
        "
    >
        Vous avez demandé la réinitialisation
        de votre mot de passe ClubConnect.
    </p>


    <p
        style="
            color:#475569;
            font-size:15px;
            line-height:1.7;
        "
    >
        Cliquez sur le bouton ci-dessous pour
        créer un nouveau mot de passe.
    </p>


    <!-- BOUTON -->

    <div
        style="
            text-align:center;
            margin:35px 0;
        "
    >

        <a
            href="{$safeLink}"
            style="
                display:inline-block;
                background:#0891b2;
                color:#ffffff;
                padding:15px 25px;
                border-radius:10px;
                text-decoration:none;
                font-size:15px;
                font-weight:bold;
            "
        >
            Réinitialiser mon mot de passe
        </a>

    </div>


    <!-- EXPIRATION -->

    <div
        style="
            background:#f8fafc;
            border:1px solid #e2e8f0;
            border-radius:12px;
            padding:16px;
        "
    >

        <p
            style="
                margin:0;
                color:#475569;
                font-size:14px;
                line-height:1.6;
            "
        >
            Ce lien est valable pendant
            <strong>1 heure</strong>.
        </p>

    </div>


    <!-- SÉCURITÉ -->

    <p
        style="
            color:#94a3b8;
            font-size:13px;
            line-height:1.6;
            margin-top:25px;
        "
    >
        Si vous n'êtes pas à l'origine de cette demande,
        vous pouvez simplement ignorer cet email.
    </p>


    <hr
        style="
            border:0;
            border-top:1px solid #e2e8f0;
            margin:30px 0;
        "
    >


    <!-- FOOTER -->

    <p
        style="
            color:#94a3b8;
            font-size:12px;
            margin:0;
        "
    >
        ClubConnect - FST Fès
    </p>


</div>


</body>

</html>

HTML;


        /*
        |--------------------------------------------------------------------------
        | VERSION TEXTE
        |--------------------------------------------------------------------------
        */

        $mail->AltBody =
            "Réinitialisation du mot de passe - ClubConnect"
            . "\n\n"
            . "Cliquez sur ce lien :"
            . "\n"
            . $link
            . "\n\n"
            . "Ce lien est valable pendant 1 heure."
            . "\n\n"
            . "Si vous n'êtes pas à l'origine de cette demande,"
            . " ignorez simplement cet email.";


        /*
        |--------------------------------------------------------------------------
        | ENVOYER
        |--------------------------------------------------------------------------
        */

        $mail->send();


        /*
        |--------------------------------------------------------------------------
        | SUCCÈS
        |--------------------------------------------------------------------------
        */

        $_SESSION["forgot_success"] =
            "Un lien de réinitialisation a été envoyé à votre adresse email.";


    } catch (\Throwable $e) {

        /*
        |--------------------------------------------------------------------------
        | SUPPRIMER LE TOKEN SI L'EMAIL ÉCHOUE
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $con,
            "
            UPDATE users
            SET
                reset_token = NULL,
                reset_token_expires = NULL
            WHERE id = ?
            LIMIT 1
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
        | Message
        |--------------------------------------------------------------------------
        */

        $_SESSION["forgot_error"] =
            "Impossible d'envoyer l'email de réinitialisation.";

        /*
         * En développement uniquement :
         *
         * $_SESSION["forgot_error"] =
         *     $e->getMessage();
         */
    }


    /*
    |--------------------------------------------------------------------------
    | RETOUR
    |--------------------------------------------------------------------------
    */

    header(
        "Location: forgot-password.php"
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| ACTION INVALIDE
|--------------------------------------------------------------------------
*/

errorMessage(
    "Action invalide."
);

redirect("index.php");