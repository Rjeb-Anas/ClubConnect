<?php

/*
|--------------------------------------------------------------------------
| ClubConnect - Profile
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/config/database.php";


/*
|--------------------------------------------------------------------------
| Vérification connexion
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['id']) ||
    !is_numeric($_SESSION['id'])
) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION['id'];


/*
|--------------------------------------------------------------------------
| Fonction sécurité
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
| Récupérer utilisateur
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $con,
    "
    SELECT
        id,
        nom,
        prenom,
        sexe,
        tel,
        email,
        date_naissance,
        role,
        email_verified,
        created_at
    FROM users
    WHERE id = ?
    LIMIT 1
    "
);

if (!$stmt) {
    die(
        "Erreur SQL : " .
        e(mysqli_error($con))
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


/*
|--------------------------------------------------------------------------
| Utilisateur introuvable
|--------------------------------------------------------------------------
*/

if (!$user) {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Informations
|--------------------------------------------------------------------------
*/

$nom =
    $user['nom'] ?? '';

$prenom =
    $user['prenom'] ?? '';

$email =
    $user['email'] ?? '';

$tel =
    $user['tel'] ?? '';

$sexe =
    $user['sexe'] ?? '';

$dateNaissance =
    $user['date_naissance'] ?? '';

$role =
    $user['role'] ?? 'user';


$fullName =
    trim(
        $prenom . " " . $nom
    );

if ($fullName === '') {
    $fullName = "Utilisateur";
}


/*
|--------------------------------------------------------------------------
| Rôles
|--------------------------------------------------------------------------
*/

$roleNames = [

    'user' =>
        'Étudiant',

    'member' =>
        'Membre',

    'club_manager' =>
        'Responsable de club',

    'admin' =>
        'Administrateur'

];

$roleName =
    $roleNames[$role]
    ?? 'Utilisateur';


/*
|--------------------------------------------------------------------------
| Liens selon le rôle
|--------------------------------------------------------------------------
*/

$roleLinks = [

    'user' => [
        'url' => 'student/index.php',
        'title' => 'Espace étudiant',
        'description' =>
            'Accéder à votre espace étudiant.'
    ],

    'member' => [
        'url' => 'student/index.php',
        'title' => 'Espace membre',
        'description' =>
            'Accéder à votre espace étudiant et vos clubs.'
    ],

    'club_manager' => [
        'url' => 'manager/index.php',
        'title' => 'Espace manager',
        'description' =>
            'Gérer vos clubs et leurs membres.'
    ],

    'admin' => [
        'url' => 'admin/index.php',
        'title' => 'Administration',
        'description' =>
            'Gérer les utilisateurs, clubs et notifications.'
    ]

];

$currentRole =
    $roleLinks[$role]
    ?? $roleLinks['user'];


/*
|--------------------------------------------------------------------------
| Initiales
|--------------------------------------------------------------------------
*/

$firstInitial =
    mb_substr(
        $prenom ?: $nom,
        0,
        1
    );

$secondInitial =
    mb_substr(
        $nom,
        0,
        1
    );

$initials =
    strtoupper(
        $firstInitial .
        $secondInitial
    );


/*
|--------------------------------------------------------------------------
| Nombre de clubs
|--------------------------------------------------------------------------
|
| On utilise UNION pour éviter de compter deux fois
| un club si le manager est également dans club_members.
|--------------------------------------------------------------------------
*/

$clubsCount = 0;

$stmt = mysqli_prepare(
    $con,
    "
    SELECT COUNT(*) AS total
    FROM
    (
        SELECT club_id
        FROM club_members
        WHERE user_id = ?

        UNION

        SELECT id AS club_id
        FROM clubs
        WHERE
            created_by = ?
            OR manager_id = ?
    ) AS user_clubs
    "
);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "iii",
        $userId,
        $userId,
        $userId
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $data =
        mysqli_fetch_assoc($result);

    $clubsCount =
        (int) (
            $data['total']
            ?? 0
        );

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| Date inscription
|--------------------------------------------------------------------------
*/

$createdAt = '';

if (!empty($user['created_at'])) {

    $timestamp =
        strtotime(
            $user['created_at']
        );

    if ($timestamp !== false) {

        $createdAt =
            date(
                'd/m/Y',
                $timestamp
            );
    }
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (
    empty(
    $_SESSION['csrf_token']
)
) {

    $_SESSION['csrf_token'] =
        bin2hex(
            random_bytes(32)
        );
}

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Mon profil | ClubConnect
    </title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>


<body class="min-h-screen
           bg-slate-50
           text-slate-900">


    <!-- =========================================================
     POPUP MESSAGE
========================================================= -->

    <?php

    require_once __DIR__ .
        "/includes/PopUpMsg.php";

    ?>


    <!-- =========================================================
     HEADER
========================================================= -->

    <?php

    require_once __DIR__ .
        "/includes/header.php";

    ?>


    <!-- =========================================================
     MAIN
========================================================= -->

    <main class="max-w-6xl
           mx-auto
           px-4 sm:px-6 lg:px-8
           py-8 sm:py-10">


        <!-- =========================================================
     TITRE
========================================================= -->

        <div class="mb-8">

            <a href="index.php" class="inline-flex
               items-center
               text-sm
               font-semibold
               text-cyan-700
               hover:text-cyan-800">
                ← Retour à l'accueil
            </a>


            <h1 class="mt-5
               text-3xl
               sm:text-4xl
               font-bold
               text-slate-900">
                Mon profil
            </h1>


            <p class="mt-2
               text-slate-500">
                Gérez vos informations personnelles et la sécurité
                de votre compte.
            </p>

        </div>


        <!-- =========================================================
     PROFIL + SIDEBAR
========================================================= -->

        <div class="grid
           grid-cols-1
           lg:grid-cols-3
           gap-6">


            <!-- =========================================================
     COLONNE PRINCIPALE
========================================================= -->

            <div class="lg:col-span-2
           space-y-6">


                <!-- =========================================================
     IDENTITÉ
========================================================= -->

                <section class="bg-slate-900
           rounded-3xl
           p-6 sm:p-8
           text-white">

                    <div class="flex
               flex-col
               sm:flex-row
               sm:items-center
               gap-5">


                        <!-- Avatar -->

                        <div class="w-24
                   h-24
                   shrink-0
                   rounded-3xl
                   bg-cyan-700
                   flex
                   items-center
                   justify-center
                   text-2xl
                   font-bold
                   border
                   border-cyan-500">
                            <?= e($initials) ?>
                        </div>


                        <!-- Informations -->

                        <div class="min-w-0
                   flex-1">

                            <div class="flex
                       flex-wrap
                       items-center
                       gap-3">

                                <h2 class="text-2xl
                           font-bold">
                                    <?= e($fullName) ?>
                                </h2>


                                <span class="px-3
                           py-1
                           rounded-full
                           bg-white/10
                           text-cyan-200
                           text-xs
                           font-semibold">
                                    <?= e($roleName) ?>
                                </span>

                            </div>


                            <p class="mt-2
                       text-sm
                       text-slate-300
                       break-all">
                                <?= e($email) ?>
                            </p>


                            <div class="mt-4
                       flex
                       flex-wrap
                       gap-3">

                                <?php if (
                                    (int) 
                                    $user['email_verified']
                                    === 1
                                ): ?>

                                    <span class="px-3
                               py-1.5
                               rounded-lg
                               bg-emerald-500/10
                               text-emerald-300
                               text-xs
                               font-semibold">
                                        Email vérifié
                                    </span>

                                <?php else: ?>

                                    <span class="px-3
                               py-1.5
                               rounded-lg
                               bg-amber-500/10
                               text-amber-300
                               text-xs
                               font-semibold">
                                        Email non vérifié
                                    </span>

                                <?php endif; ?>


                                <span class="px-3
                           py-1.5
                           rounded-lg
                           bg-white/10
                           text-slate-300
                           text-xs
                           font-semibold">
                                    <?= $clubsCount ?>
                                    club<?= $clubsCount > 1 ? 's' : '' ?>
                                </span>

                            </div>

                        </div>

                    </div>

                </section>


                <!-- =========================================================
     INFORMATIONS PERSONNELLES
========================================================= -->

                <section class="bg-white
           border
           border-slate-200
           rounded-3xl
           p-6 sm:p-8">

                    <div class="mb-7">

                        <h2 class="text-xl
                   font-bold">
                            Informations personnelles
                        </h2>

                        <p class="mt-1
                   text-sm
                   text-slate-500">
                            Modifiez les informations de votre compte.
                        </p>

                    </div>


                    <form action="traitement.php" method="POST" class="space-y-5">

                        <input type="hidden" name="csrf_token" value="<?= e(
                            $_SESSION['csrf_token']
                        ) ?>">


                        <!-- Nom / Prénom -->

                        <div class="grid
                   grid-cols-1
                   sm:grid-cols-2
                   gap-5">

                            <div>

                                <label for="nom" class="block
                           mb-2
                           text-sm
                           font-semibold">
                                    Nom
                                </label>

                                <input type="text" id="nom" name="nom" required maxlength="50" value="<?= e($nom) ?>"
                                    class="w-full
                           px-4
                           py-3
                           rounded-xl
                           border
                           border-slate-300
                           outline-none
                           focus:border-cyan-600
                           focus:ring-2
                           focus:ring-cyan-100">

                            </div>


                            <div>

                                <label for="prenom" class="block
                           mb-2
                           text-sm
                           font-semibold">
                                    Prénom
                                </label>

                                <input type="text" id="prenom" name="prenom" required maxlength="50"
                                    value="<?= e($prenom) ?>" class="w-full
                           px-4
                           py-3
                           rounded-xl
                           border
                           border-slate-300
                           outline-none
                           focus:border-cyan-600
                           focus:ring-2
                           focus:ring-cyan-100">

                            </div>

                        </div>


                        <!-- Téléphone -->

                        <div>

                            <label for="tel" class="block
                       mb-2
                       text-sm
                       font-semibold">
                                Téléphone
                            </label>

                            <input type="tel" id="tel" name="tel" required maxlength="10" pattern="^(06|07)[0-9]{8}$"
                                value="<?= e($tel) ?>" placeholder="06XXXXXXXX" class="w-full
                       px-4
                       py-3
                       rounded-xl
                       border
                       border-slate-300
                       outline-none
                       focus:border-cyan-600
                       focus:ring-2
                       focus:ring-cyan-100">

                        </div>


                        <!-- Date naissance -->

                        <div>

                            <label for="date_naissance" class="block
                       mb-2
                       text-sm
                       font-semibold">
                                Date de naissance
                            </label>

                            <input type="date" id="date_naissance" name="date_naissance" value="<?= e(
                                $dateNaissance
                            ) ?>" max="<?= date('Y-m-d') ?>" class="w-full
                       px-4
                       py-3
                       rounded-xl
                       border
                       border-slate-300
                       outline-none
                       focus:border-cyan-600
                       focus:ring-2
                       focus:ring-cyan-100">

                        </div>


                        <!-- Sexe -->

                        <div>

                            <label for="sexe" class="block
                       mb-2
                       text-sm
                       font-semibold">
                                Sexe
                            </label>

                            <?php
                            $sexe = trim((string) ($user['sexe'] ?? ''));
                            ?>

                            <select name="sexe" id="sexe"
                                class="w-full px-4 py-3 rounded-xl border border-slate-300 bg-white">

                                <option value="">Sélectionner</option>

                                <option value="Homme" <?= strcasecmp($sexe, 'Homme') === 0 ? 'selected' : '' ?>>
                                    Homme
                                </option>

                                <option value="Femme" <?= strcasecmp($sexe, 'Femme') === 0 ? 'selected' : '' ?>>
                                    Femme
                                </option>

                            </select>

                        </div>


                        <!-- Email -->

                        <div>

                            <label class="block
                       mb-2
                       text-sm
                       font-semibold">
                                Email
                            </label>

                            <div class="w-full
                       px-4
                       py-3
                       rounded-xl
                       bg-slate-50
                       border
                       border-slate-200
                       text-slate-500
                       break-all">
                                <?= e($email) ?>
                            </div>

                            <p class="mt-2
                       text-xs
                       text-slate-400">
                                L'adresse email ne peut pas être modifiée ici.
                            </p>

                        </div>


                        <!-- Bouton -->

                        <div class="flex
                   justify-end
                   pt-2">

                            <button type="submit" name="update_profile" class="w-full
                       sm:w-auto
                       px-6
                       py-3
                       rounded-xl
                       bg-cyan-700
                       text-white
                       font-semibold
                       hover:bg-cyan-800
                       transition">
                                Enregistrer
                            </button>

                        </div>

                    </form>

                </section>


                <!-- =========================================================
     MOT DE PASSE
========================================================= -->

                <section class="bg-white
           border
           border-slate-200
           rounded-3xl
           p-6 sm:p-8">

                    <div class="mb-7">

                        <h2 class="text-xl
                   font-bold">
                            Modifier le mot de passe
                        </h2>

                        <p class="mt-1
                   text-sm
                   text-slate-500">
                            Utilisez un mot de passe d'au moins 8 caractères.
                        </p>

                    </div>


                    <form action="traitement.php" method="POST" id="passwordForm" class="space-y-5">

                        <input type="hidden" name="csrf_token" value="<?= e(
                            $_SESSION['csrf_token']
                        ) ?>">


                        <!-- Ancien -->

                        <div>

                            <label for="current_password" class="block
                       mb-2
                       text-sm
                       font-semibold">
                                Mot de passe actuel
                            </label>

                            <input type="password" id="current_password" name="current_password" required
                                autocomplete="current-password" class="w-full
                       px-4
                       py-3
                       rounded-xl
                       border
                       border-slate-300
                       outline-none
                       focus:border-cyan-600
                       focus:ring-2
                       focus:ring-cyan-100">

                        </div>


                        <!-- Nouveau -->

                        <div>

                            <label for="new_password" class="block
                       mb-2
                       text-sm
                       font-semibold">
                                Nouveau mot de passe
                            </label>

                            <input type="password" id="new_password" name="new_password" required minlength="8"
                                autocomplete="new-password" class="w-full
                       px-4
                       py-3
                       rounded-xl
                       border
                       border-slate-300
                       outline-none
                       focus:border-cyan-600
                       focus:ring-2
                       focus:ring-cyan-100">

                            <p class="mt-2
                       text-xs
                       text-slate-400">
                                Au moins 8 caractères,
                                une majuscule,
                                une minuscule et un chiffre.
                            </p>

                        </div>


                        <!-- Confirmation -->

                        <div>

                            <label for="confirm_password" class="block
                       mb-2
                       text-sm
                       font-semibold">
                                Confirmer le nouveau mot de passe
                            </label>

                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8"
                                autocomplete="new-password" class="w-full
                       px-4
                       py-3
                       rounded-xl
                       border
                       border-slate-300
                       outline-none
                       focus:border-cyan-600
                       focus:ring-2
                       focus:ring-cyan-100">

                        </div>


                        <div class="flex
                   justify-end
                   pt-2">

                            <button type="submit" name="change_password" class="w-full
                       sm:w-auto
                       px-6
                       py-3
                       rounded-xl
                       bg-slate-900
                       text-white
                       font-semibold
                       hover:bg-slate-800
                       transition">
                                Modifier le mot de passe
                            </button>

                        </div>

                    </form>

                </section>

            </div>


            <!-- =========================================================
     SIDEBAR
========================================================= -->

            <aside class="space-y-6">


                <!-- =========================================================
     ESPACE DU RÔLE
========================================================= -->

                <section class="bg-white
           border
           border-slate-200
           rounded-3xl
           p-6">

                    <p class="text-xs
               uppercase
               tracking-wider
               font-bold
               text-slate-400">
                        Mon espace
                    </p>


                    <div class="mt-4
               p-4
               rounded-2xl
               bg-cyan-50
               border
               border-cyan-100">

                        <div class="w-11
                   h-11
                   rounded-xl
                   bg-cyan-100
                   text-cyan-700
                   flex
                   items-center
                   justify-center
                   font-bold">
                            <?= e(
                                strtoupper(
                                    mb_substr(
                                        $roleName,
                                        0,
                                        1
                                    )
                                )
                            ) ?>
                        </div>


                        <h3 class="mt-4
                   font-bold
                   text-slate-900">
                            <?= e(
                                $currentRole['title']
                            ) ?>
                        </h3>


                        <p class="mt-1
                   text-sm
                   leading-6
                   text-slate-500">
                            <?= e(
                                $currentRole['description']
                            ) ?>
                        </p>


                        <a href="<?= e(
                            $currentRole['url']
                        ) ?>" class="inline-flex
                   w-full
                   mt-4
                   justify-center
                   px-4
                   py-3
                   rounded-xl
                   bg-cyan-700
                   text-white
                   text-sm
                   font-semibold
                   hover:bg-cyan-800
                   transition">
                            Accéder à mon espace
                        </a>

                    </div>

                </section>


                <!-- =========================================================
     STATISTIQUES
========================================================= -->

                <section class="bg-white
           border
           border-slate-200
           rounded-3xl
           p-6">

                    <h2 class="font-bold">
                        Mon compte
                    </h2>


                    <div class="mt-5
               space-y-4">

                        <div class="flex
                   justify-between
                   gap-4">

                            <span class="text-sm
                       text-slate-500">
                                Rôle
                            </span>

                            <span class="text-sm
                       font-semibold
                       text-right">
                                <?= e($roleName) ?>
                            </span>

                        </div>


                        <div class="flex
                   justify-between
                   gap-4">

                            <span class="text-sm
                       text-slate-500">
                                Clubs
                            </span>

                            <span class="font-bold">
                                <?= $clubsCount ?>
                            </span>

                        </div>


                        <div class="flex
                   justify-between
                   gap-4">

                            <span class="text-sm
                       text-slate-500">
                                Email
                            </span>

                            <?php if (
                                (int) 
                                $user['email_verified']
                                === 1
                            ): ?>

                                <span class="text-sm
                           font-semibold
                           text-emerald-600">
                                    Vérifié
                                </span>

                            <?php else: ?>

                                <span class="text-sm
                           font-semibold
                           text-amber-600">
                                    Non vérifié
                                </span>

                            <?php endif; ?>

                        </div>


                        <?php if ($createdAt): ?>

                            <div class="flex
                       justify-between
                       gap-4">

                                <span class="text-sm
                           text-slate-500">
                                    Inscription
                                </span>

                                <span class="text-sm
                           font-semibold">
                                    <?= e($createdAt) ?>
                                </span>

                            </div>

                        <?php endif; ?>

                    </div>

                </section>


                <!-- =========================================================
     NAVIGATION RAPIDE
========================================================= -->

                <section class="bg-white
           border
           border-slate-200
           rounded-3xl
           p-6">

                    <h2 class="font-bold">
                        Navigation
                    </h2>


                    <div class="mt-4
               space-y-2">

                        <a href="clubs.php" class="flex
                   items-center
                   justify-between
                   p-3
                   rounded-xl
                   hover:bg-slate-50
                   transition">

                            <span class="text-sm
                       font-medium">
                                Tous les clubs
                            </span>

                            <span>
                                →
                            </span>

                        </a>


                        <a href="student/MyClub.php" class="flex
                   items-center
                   justify-between
                   p-3
                   rounded-xl
                   hover:bg-slate-50
                   transition">

                            <span class="text-sm
                       font-medium">
                                Mes clubs
                            </span>

                            <span class="px-2
                       py-1
                       rounded-full
                       bg-slate-100
                       text-xs
                       font-bold">
                                <?= $clubsCount ?>
                            </span>

                        </a>


                        <a href="logout.php" class="flex
                   items-center
                   justify-center
                   p-3
                   mt-3
                   rounded-xl
                   bg-red-50
                   text-red-600
                   font-semibold
                   text-sm
                   hover:bg-red-100
                   transition">
                            Déconnexion
                        </a>

                    </div>

                </section>

            </aside>

        </div>

    </main>


    <!-- =========================================================
     JAVASCRIPT
========================================================= -->

    <script>

        /*
        |--------------------------------------------------------------------------
        | Vérification mot de passe
        |--------------------------------------------------------------------------
        */

        const passwordForm =
            document.getElementById(
                "passwordForm"
            );

        if (passwordForm) {

            passwordForm.addEventListener(
                "submit",
                function (event) {

                    const current =
                        document.getElementById(
                            "current_password"
                        ).value;

                    const password =
                        document.getElementById(
                            "new_password"
                        ).value;

                    const confirmation =
                        document.getElementById(
                            "confirm_password"
                        ).value;


                    if (!current) {

                        event.preventDefault();

                        alert(
                            "Entrez votre mot de passe actuel."
                        );

                        return;
                    }


                    if (password !== confirmation) {

                        event.preventDefault();

                        alert(
                            "Les deux nouveaux mots de passe ne correspondent pas."
                        );

                        return;
                    }


                    const strongPassword =
                        /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;


                    if (
                        !strongPassword.test(
                            password
                        )
                    ) {

                        event.preventDefault();

                        alert(
                            "Le nouveau mot de passe doit contenir au minimum 8 caractères, une majuscule, une minuscule et un chiffre."
                        );
                    }

                }
            );
        }

    </script>

</body>

</html>