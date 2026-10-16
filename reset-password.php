<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config/database.php';

mysqli_set_charset($con, 'utf8mb4');


/*
|--------------------------------------------------------------------------
| Fonction sécurité
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| TOKEN
|--------------------------------------------------------------------------
*/

$token = trim(
    $_GET['token']
    ?? $_POST['token']
    ?? ''
);


/*
|--------------------------------------------------------------------------
| Variables
|--------------------------------------------------------------------------
*/

$error = '';
$success = '';

$user = null;


/*
|--------------------------------------------------------------------------
| Vérification token
|--------------------------------------------------------------------------
*/

if ($token === '') {

    $error = "Aucun lien de réinitialisation fourni.";

} else {

    $stmt = mysqli_prepare(
        $con,
        "
        SELECT
    id,
    email,
    password,
    reset_token,
    reset_token_expires
FROM users
        WHERE reset_token = ?
        LIMIT 1
        "
    );


    if (!$stmt) {

        $error =
            "Erreur SQL : " .
            mysqli_error($con);

    } else {

        mysqli_stmt_bind_param(
            $stmt,
            's',
            $token
        );


        mysqli_stmt_execute($stmt);


        $result =
            mysqli_stmt_get_result($stmt);


        $user =
            mysqli_fetch_assoc($result);


        mysqli_stmt_close($stmt);


        if (!$user) {

            $error =
                "Le lien de réinitialisation est invalide.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Vérifier expiration
            |--------------------------------------------------------------------------
            */

            if (
                empty(
                $user['reset_token_expires']
            )
            ) {

                $error =
                    "Le lien de réinitialisation n'a pas de date d'expiration.";

            } else {

                $expiration =
                    strtotime(
                        $user['reset_token_expires']
                    );


                if (
                    $expiration === false ||
                    $expiration < time()
                ) {

                    $error =
                        "Le lien de réinitialisation a expiré.";
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| CHANGEMENT DU MOT DE PASSE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $user &&
    $error === ''
) {

    $password =
        (string) (
            $_POST['password']
            ?? ''
        );


    $confirmPassword =
        (string) (
            $_POST['confirm_password']
            ?? ''
        );


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        $password === ''
    ) {

        $error =
            "Veuillez entrer un nouveau mot de passe.";

    } elseif (
        strlen($password) < 8
    ) {

        $error =
            "Le mot de passe doit contenir au moins 8 caractères.";

    } elseif (
        !preg_match(
            '/[A-Z]/',
            $password
        )
    ) {

        $error =
            "Le mot de passe doit contenir une majuscule.";

    } elseif (
        !preg_match(
            '/[a-z]/',
            $password
        )
    ) {

        $error =
            "Le mot de passe doit contenir une minuscule.";

    } elseif (
        !preg_match(
            '/[0-9]/',
            $password
        )
    ) {

        $error =
            "Le mot de passe doit contenir un chiffre.";

    } elseif (
        $password !== $confirmPassword
    ) {

        $error =
            "Les mots de passe ne correspondent pas.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | HASH
        |--------------------------------------------------------------------------
        */

        $passwordHash =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );


        if (!$passwordHash) {

            $error =
                "Impossible de sécuriser le mot de passe.";

        } else {

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
    password = ?,
    reset_token = NULL,
    reset_token_expires = NULL
                WHERE
                    id = ?
                    AND reset_token = ?
                LIMIT 1
                "
            );


            if (!$stmt) {

                $error =
                    "Erreur SQL : " .
                    mysqli_error($con);

            } else {

                $userId =
                    (int) $user['id'];


                mysqli_stmt_bind_param(
                    $stmt,
                    'sis',
                    $passwordHash,
                    $userId,
                    $token
                );


                if (
                    mysqli_stmt_execute($stmt)
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Vérifier modification
                    |--------------------------------------------------------------------------
                    */

                    if (
                        mysqli_stmt_affected_rows($stmt) > 0
                    ) {

                        $success =
                            "Mot de passe modifié avec succès.";

                    } else {

                        $error =
                            "Le mot de passe n'a pas été modifié.";
                    }

                } else {

                    $error =
                        "Erreur pendant la modification : "
                        . mysqli_stmt_error($stmt);
                }


                mysqli_stmt_close($stmt);
            }
        }
    }
}

?>


<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Nouveau mot de passe - ClubConnect
    </title>


    <link rel="stylesheet" href="assets/css/style.css">

</head>


<body class="
        min-h-screen
        bg-slate-100
        flex
        items-center
        justify-center
        p-4
    ">


    <div class="
        w-full
        max-w-md
        bg-white
        rounded-3xl
        shadow-xl
        border
        border-slate-200
        p-6
        sm:p-8
    ">


        <!-- =====================================================
         HEADER
    ====================================================== -->

        <div class="text-center mb-8">

            <div class="
                w-16
                h-16
                mx-auto
                rounded-2xl
                bg-cyan-700
                flex
                items-center
                justify-center
                text-white
                text-xl
                font-black
            ">
                CC
            </div>


            <h1 class="
                mt-5
                text-2xl
                font-black
                text-slate-900
            ">
                Nouveau mot de passe
            </h1>


            <p class="
                mt-2
                text-sm
                text-slate-500
            ">
                Entrez votre nouveau mot de passe.
            </p>

        </div>


        <!-- =====================================================
         SUCCÈS
    ====================================================== -->

        <?php if ($success !== ''): ?>

            <div class="
                rounded-2xl
                border
                border-emerald-200
                bg-emerald-50
                p-5
            ">

                <div class="
                    text-emerald-600
                    text-3xl
                    font-black
                ">
                    ✓
                </div>


                <h2 class="
                    mt-3
                    font-black
                    text-emerald-800
                ">
                    Mot de passe modifié
                </h2>


                <p class="
                    mt-2
                    text-sm
                    text-emerald-700
                ">
                    <?= e($success) ?>
                </p>


                <a href="login.php" class="
                    block
                    mt-5
                    text-center
                    rounded-xl
                    bg-emerald-600
                    text-white
                    py-3
                    font-bold
                    hover:bg-emerald-700
                ">
                    Se connecter
                </a>

            </div>


            <!-- =====================================================
         ERREUR TOKEN
    ====================================================== -->

        <?php elseif (!$user || $error !== ''): ?>

            <div class="
                rounded-2xl
                border
                border-red-200
                bg-red-50
                p-5
            ">

                <div class="
                    text-red-600
                    text-3xl
                    font-black
                ">
                    !
                </div>


                <h2 class="
                    mt-3
                    font-black
                    text-red-800
                ">
                    Lien invalide
                </h2>


                <p class="
                    mt-2
                    text-sm
                    leading-6
                    text-red-700
                ">
                    <?= e($error) ?>
                </p>


                <a href="forgot-password.php" class="
                    block
                    mt-5
                    text-center
                    rounded-xl
                    bg-cyan-700
                    text-white
                    py-3
                    font-bold
                    hover:bg-cyan-800
                ">
                    Demander un nouveau lien
                </a>

            </div>


            <!-- =====================================================
         FORMULAIRE
    ====================================================== -->

        <?php else: ?>


            <?php if ($error !== ''): ?>

                <div class="
                    mb-5
                    rounded-xl
                    bg-red-50
                    border
                    border-red-200
                    p-4
                    text-sm
                    text-red-700
                ">
                    <?= e($error) ?>
                </div>

            <?php endif; ?>


            <div class="
                mb-6
                rounded-xl
                bg-slate-50
                border
                border-slate-200
                p-4
            ">

                <p class="
                    text-xs
                    text-slate-400
                ">
                    Compte
                </p>


                <p class="
                    mt-1
                    text-sm
                    font-bold
                    text-slate-700
                    break-all
                ">
                    <?= e($user['email']) ?>
                </p>

            </div>


            <form method="POST" action="" class="space-y-5">


                <!-- TOKEN -->

                <input type="hidden" name="token" value="<?= e($token) ?>">


                <!-- PASSWORD -->

                <div>

                    <label for="password" class="
                        block
                        mb-2
                        text-sm
                        font-bold
                        text-slate-700
                    ">
                        Nouveau mot de passe
                    </label>


                    <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password"
                        placeholder="Minimum 8 caractères" class="
                        w-full
                        rounded-xl
                        border
                        border-slate-200
                        px-4
                        py-3
                        outline-none
                        focus:border-cyan-500
                        focus:ring-4
                        focus:ring-cyan-100
                    ">

                </div>


                <!-- CONFIRMATION -->

                <div>

                    <label for="confirm_password" class="
                        block
                        mb-2
                        text-sm
                        font-bold
                        text-slate-700
                    ">
                        Confirmer le mot de passe
                    </label>


                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8"
                        autocomplete="new-password" placeholder="Répétez le mot de passe" class="
                        w-full
                        rounded-xl
                        border
                        border-slate-200
                        px-4
                        py-3
                        outline-none
                        focus:border-cyan-500
                        focus:ring-4
                        focus:ring-cyan-100
                    ">

                </div>


                <!-- RÈGLES -->

                <div class="
                    rounded-xl
                    bg-slate-50
                    border
                    border-slate-200
                    p-4
                ">

                    <p class="
                        text-xs
                        font-black
                        text-slate-700
                        mb-3
                    ">
                        Le mot de passe doit contenir :
                    </p>


                    <ul class="
                        text-xs
                        text-slate-500
                        space-y-2
                    ">

                        <li>
                            Au moins 8 caractères
                        </li>

                        <li>
                            Une majuscule
                        </li>

                        <li>
                            Une minuscule
                        </li>

                        <li>
                            Un chiffre
                        </li>

                    </ul>

                </div>


                <!-- BOUTON -->

                <button type="submit" class="
                    w-full
                    rounded-xl
                    bg-gradient-to-r
                    from-cyan-600
                    to-blue-700
                    text-white
                    py-3.5
                    font-black
                    hover:from-cyan-700
                    hover:to-blue-800
                    transition
                ">
                    Modifier le mot de passe
                </button>


            </form>


            <a href="login.php" class="
                block
                mt-6
                text-center
                text-sm
                font-bold
                text-slate-500
                hover:text-cyan-700
            ">
                Retour à la connexion
            </a>


        <?php endif; ?>


    </div>


    <script>

        const password =
            document.getElementById('password');

        const confirmPassword =
            document.getElementById('confirm_password');


        if (
            password &&
            confirmPassword
        ) {

            function checkPasswords() {

                if (
                    confirmPassword.value !==
                    password.value
                ) {

                    confirmPassword.setCustomValidity(
                        'Les mots de passe ne correspondent pas.'
                    );

                } else {

                    confirmPassword.setCustomValidity('');
                }
            }


            password.addEventListener(
                'input',
                checkPasswords
            );


            confirmPassword.addEventListener(
                'input',
                checkPasswords
            );
        }

    </script>


</body>

</html>