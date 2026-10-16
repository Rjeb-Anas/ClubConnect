<?php

/*
|--------------------------------------------------------------------------
| ClubConnect - contact.php
|--------------------------------------------------------------------------
| Page accessible à tout le monde.
|
| Visiteur non connecté :
|   - peut voir la page
|   - ne peut pas envoyer de message
|
| Utilisateur connecté :
|   - nom récupéré depuis users
|   - prénom récupéré depuis users
|   - email récupéré depuis users
|   - ces informations sont non modifiables
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/config/database.php";


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
| VÉRIFIER LA SESSION
|--------------------------------------------------------------------------
*/

$userId = $_SESSION['id']
    ?? $_SESSION['user_id']
    ?? null;

$isLoggedIn = (
    $userId !== null &&
    is_numeric($userId) &&
    (int)$userId > 0
);

$user = null;


/*
|--------------------------------------------------------------------------
| RÉCUPÉRER LES INFORMATIONS DU COMPTE
|--------------------------------------------------------------------------
*/

if ($isLoggedIn) {

    $userId = (int)$userId;


    $stmt = mysqli_prepare(
        $con,
        "
        SELECT
            id,
            nom,
            prenom,
            email
        FROM users
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


        if (mysqli_stmt_execute($stmt)) {

            $result =
                mysqli_stmt_get_result($stmt);

            if ($result) {

                $user =
                    mysqli_fetch_assoc($result);
            }
        }


        mysqli_stmt_close($stmt);
    }


    /*
    |--------------------------------------------------------------------------
    | SI LE COMPTE N'EXISTE PLUS
    |--------------------------------------------------------------------------
    */

    if (!$user) {

        $isLoggedIn = false;

        $userId = null;
    }
}


/*
|--------------------------------------------------------------------------
| INFORMATIONS UTILISATEUR
|--------------------------------------------------------------------------
*/

$nom = '';
$prenom = '';
$email = '';

if ($isLoggedIn && $user) {

    $nom = trim(
        (string)($user['nom'] ?? '')
    );

    $prenom = trim(
        (string)($user['prenom'] ?? '')
    );

    $email = trim(
        (string)($user['email'] ?? '')
    );
}


/*
|--------------------------------------------------------------------------
| POPUP
|--------------------------------------------------------------------------
*/

$popup = $_SESSION['popup'] ?? null;

unset($_SESSION['popup']);

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="theme-color"
        content="#0e7490"
    >

    <title>
        Contact | ClubConnect - FST Fès
    </title>

    <link
        rel="stylesheet"
        href="/assets/css/style.css"
    >

</head>


<body
    class="
        min-h-screen
        bg-slate-50
        text-slate-900
    "
>


<!-- =========================================================
     HEADER
========================================================= -->

<?php

require_once __DIR__ . "/includes/header.php";

?>


<!-- =========================================================
     POPUP
========================================================= -->

<?php if ($popup): ?>

    <div
        class="
            fixed
            top-5
            right-5
            z-[100]
            w-[calc(100%-2rem)]
            max-w-sm
        "
    >

        <div
            class="
                rounded-2xl
                border
                bg-white
                shadow-xl
                p-4
                <?= ($popup['type'] ?? '') === 'success'
                    ? 'border-emerald-200'
                    : 'border-red-200'
                ?>
            "
        >

            <div
                class="
                    flex
                    items-start
                    gap-3
                "
            >

                <div
                    class="
                        w-9
                        h-9
                        shrink-0
                        rounded-xl
                        flex
                        items-center
                        justify-center
                        font-bold
                        <?= ($popup['type'] ?? '') === 'success'
                            ? 'bg-emerald-50 text-emerald-600'
                            : 'bg-red-50 text-red-600'
                        ?>
                    "
                >

                    <?= ($popup['type'] ?? '') === 'success'
                        ? '✓'
                        : '!'
                    ?>

                </div>


                <div class="min-w-0">

                    <p
                        class="
                            text-sm
                            font-semibold
                            text-slate-900
                        "
                    >

                        <?= e(
                            $popup['message'] ?? ''
                        ) ?>

                    </p>

                </div>

            </div>

        </div>

    </div>

<?php endif; ?>


<!-- =========================================================
     MAIN
========================================================= -->

<main>


    <!-- =====================================================
         HERO
    ====================================================== -->

    <section
        class="
            bg-white
            border-b
            border-slate-100
        "
    >

        <div
            class="
                max-w-7xl
                mx-auto
                px-4
                sm:px-6
                lg:px-8
                py-14
                sm:py-16
            "
        >

            <div class="max-w-3xl">

                <span
                    class="
                        inline-flex
                        px-3
                        py-1.5
                        rounded-full
                        bg-cyan-50
                        text-cyan-700
                        text-xs
                        sm:text-sm
                        font-semibold
                    "
                >
                    CONTACT
                </span>


                <h1
                    class="
                        mt-5
                        text-4xl
                        sm:text-5xl
                        font-bold
                        tracking-tight
                        text-slate-900
                    "
                >
                    Contactez-nous
                </h1>


                <p
                    class="
                        mt-5
                        max-w-2xl
                        text-base
                        sm:text-lg
                        leading-8
                        text-slate-600
                    "
                >
                    Une question concernant votre compte,
                    un club ou le fonctionnement de ClubConnect ?
                    Contactez l'administration.
                </p>

            </div>

        </div>

    </section>


    <!-- =====================================================
         CONTENT
    ====================================================== -->

    <section>

        <div
            class="
                max-w-7xl
                mx-auto
                px-4
                sm:px-6
                lg:px-8
                py-10
                sm:py-14
            "
        >

            <div
                class="
                    grid
                    grid-cols-1
                    lg:grid-cols-3
                    gap-8
                "
            >


                <!-- =============================================
                     INFORMATIONS
                ============================================== -->

                <aside>

                    <div
                        class="
                            bg-white
                            border
                            border-slate-200
                            rounded-3xl
                            p-6
                            sm:p-7
                            shadow-sm
                        "
                    >

                        <h2
                            class="
                                text-xl
                                font-bold
                                text-slate-900
                            "
                        >
                            Contact
                        </h2>


                        <p
                            class="
                                mt-2
                                text-sm
                                leading-6
                                text-slate-500
                            "
                        >
                            Utilisez le formulaire pour
                            contacter l'administration
                            de ClubConnect.
                        </p>


                        <!-- FST -->

                        <div
                            class="
                                mt-8
                                flex
                                items-start
                                gap-4
                            "
                        >

                            <div
                                class="
                                    w-11
                                    h-11
                                    shrink-0
                                    rounded-xl
                                    bg-cyan-50
                                    text-cyan-700
                                    flex
                                    items-center
                                    justify-center
                                    font-bold
                                "
                            >
                                F
                            </div>


                            <div>

                                <p
                                    class="
                                        text-xs
                                        uppercase
                                        tracking-wide
                                        font-semibold
                                        text-slate-400
                                    "
                                >
                                    Établissement
                                </p>

                                <p
                                    class="
                                        mt-1
                                        text-sm
                                        font-semibold
                                        text-slate-900
                                    "
                                >
                                    FST Fès
                                </p>

                            </div>

                        </div>


                        <!-- PLATEFORME -->

                        <div
                            class="
                                mt-6
                                flex
                                items-start
                                gap-4
                            "
                        >

                            <div
                                class="
                                    w-11
                                    h-11
                                    shrink-0
                                    rounded-xl
                                    bg-cyan-50
                                    text-cyan-700
                                    flex
                                    items-center
                                    justify-center
                                    font-bold
                                "
                            >
                                C
                            </div>


                            <div>

                                <p
                                    class="
                                        text-xs
                                        uppercase
                                        tracking-wide
                                        font-semibold
                                        text-slate-400
                                    "
                                >
                                    Plateforme
                                </p>

                                <p
                                    class="
                                        mt-1
                                        text-sm
                                        font-semibold
                                        text-slate-900
                                    "
                                >
                                    ClubConnect
                                </p>

                            </div>

                        </div>


                        <!-- COMPTE -->

                        <div
                            class="
                                mt-6
                                flex
                                items-start
                                gap-4
                            "
                        >

                            <div
                                class="
                                    w-11
                                    h-11
                                    shrink-0
                                    rounded-xl
                                    bg-cyan-50
                                    text-cyan-700
                                    flex
                                    items-center
                                    justify-center
                                    font-bold
                                "
                            >
                                <?= $isLoggedIn ? '✓' : '!' ?>
                            </div>


                            <div>

                                <p
                                    class="
                                        text-xs
                                        uppercase
                                        tracking-wide
                                        font-semibold
                                        text-slate-400
                                    "
                                >
                                    Statut
                                </p>


                                <?php if ($isLoggedIn): ?>

                                    <p
                                        class="
                                            mt-1
                                            text-sm
                                            font-semibold
                                            text-emerald-600
                                        "
                                    >
                                        Connecté
                                    </p>

                                <?php else: ?>

                                    <p
                                        class="
                                            mt-1
                                            text-sm
                                            font-semibold
                                            text-amber-600
                                        "
                                    >
                                        Non connecté
                                    </p>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>


                    <!-- MESSAGE POUR NON CONNECTÉ -->

                    <?php if (!$isLoggedIn): ?>

                        <div
                            class="
                                mt-5
                                rounded-2xl
                                border
                                border-amber-200
                                bg-amber-50
                                p-6
                            "
                        >

                            <h3
                                class="
                                    font-bold
                                    text-slate-900
                                "
                            >
                                Compte nécessaire
                            </h3>


                            <p
                                class="
                                    mt-2
                                    text-sm
                                    leading-6
                                    text-slate-600
                                "
                            >
                                Vous pouvez consulter cette page
                                sans compte. Pour envoyer un message,
                                vous devez être connecté.
                            </p>


                            <div
                                class="
                                    mt-5
                                    flex
                                    flex-col
                                    gap-2
                                "
                            >

                                <a
                                    href="/login.php"
                                    class="
                                        inline-flex
                                        items-center
                                        justify-center
                                        rounded-xl
                                        bg-cyan-700
                                        px-5
                                        py-3
                                        text-sm
                                        font-semibold
                                        text-white
                                        hover:bg-cyan-800
                                        transition
                                    "
                                >
                                    Se connecter
                                </a>


                                <a
                                    href="/register.php"
                                    class="
                                        inline-flex
                                        items-center
                                        justify-center
                                        rounded-xl
                                        border
                                        border-slate-300
                                        bg-white
                                        px-5
                                        py-3
                                        text-sm
                                        font-semibold
                                        text-slate-700
                                        hover:bg-slate-50
                                        transition
                                    "
                                >
                                    Créer un compte
                                </a>

                            </div>

                        </div>

                    <?php endif; ?>

                </aside>


                <!-- =============================================
                     FORMULAIRE
                ============================================== -->

                <div
                    class="
                        lg:col-span-2
                    "
                >

                    <div
                        class="
                            bg-white
                            border
                            border-slate-200
                            rounded-3xl
                            shadow-sm
                            overflow-hidden
                        "
                    >

                        <!-- TITRE -->

                        <div
                            class="
                                p-6
                                sm:p-8
                                border-b
                                border-slate-100
                            "
                        >

                            <h2
                                class="
                                    text-xl
                                    sm:text-2xl
                                    font-bold
                                    text-slate-900
                                "
                            >
                                Envoyer un message
                            </h2>


                            <?php if ($isLoggedIn): ?>

                                <p
                                    class="
                                        mt-2
                                        text-sm
                                        text-slate-500
                                    "
                                >
                                    Votre identité est automatiquement
                                    récupérée depuis votre compte.
                                </p>

                            <?php else: ?>

                                <p
                                    class="
                                        mt-2
                                        text-sm
                                        text-slate-500
                                    "
                                >
                                    Connectez-vous pour envoyer
                                    un message.
                                </p>

                            <?php endif; ?>

                        </div>


                        <!-- FORMULAIRE -->

                        <div
                            class="
                                p-6
                                sm:p-8
                            "
                        >

                            <?php if ($isLoggedIn): ?>


                                <!-- =================================
                                     CONNECTÉ
                                ================================== -->

                                <form
                                    action="/traitement.php"
                                    method="POST"
                                    class="space-y-6"
                                >


                                    <!-- NOM + PRENOM -->

                                    <div
                                        class="
                                            grid
                                            grid-cols-1
                                            sm:grid-cols-2
                                            gap-5
                                        "
                                    >

                                        <!-- NOM -->

                                        <div>

                                            <label
                                                for="contact_nom"
                                                class="
                                                    block
                                                    mb-2
                                                    text-sm
                                                    font-medium
                                                    text-slate-700
                                                "
                                            >
                                                Nom
                                            </label>


                                            <input
                                                type="text"
                                                id="contact_nom"
                                                name="contact_nom"
                                                value="<?= e($nom) ?>"
                                                readonly
                                                tabindex="-1"
                                                class="
                                                    w-full
                                                    px-4
                                                    py-3
                                                    rounded-xl
                                                    border
                                                    border-slate-200
                                                    bg-slate-100
                                                    text-slate-600
                                                    outline-none
                                                    cursor-not-allowed
                                                "
                                            >


                                            <p
                                                class="
                                                    mt-1.5
                                                    text-xs
                                                    text-slate-400
                                                "
                                            >
                                                Depuis votre compte
                                            </p>

                                        </div>


                                        <!-- PRENOM -->

                                        <div>

                                            <label
                                                for="contact_prenom"
                                                class="
                                                    block
                                                    mb-2
                                                    text-sm
                                                    font-medium
                                                    text-slate-700
                                                "
                                            >
                                                Prénom
                                            </label>


                                            <input
                                                type="text"
                                                id="contact_prenom"
                                                name="contact_prenom"
                                                value="<?= e($prenom) ?>"
                                                readonly
                                                tabindex="-1"
                                                class="
                                                    w-full
                                                    px-4
                                                    py-3
                                                    rounded-xl
                                                    border
                                                    border-slate-200
                                                    bg-slate-100
                                                    text-slate-600
                                                    outline-none
                                                    cursor-not-allowed
                                                "
                                            >


                                            <p
                                                class="
                                                    mt-1.5
                                                    text-xs
                                                    text-slate-400
                                                "
                                            >
                                                Depuis votre compte
                                            </p>

                                        </div>

                                    </div>


                                    <!-- EMAIL -->

                                    <div>

                                        <label
                                            for="contact_email"
                                            class="
                                                block
                                                mb-2
                                                text-sm
                                                font-medium
                                                text-slate-700
                                            "
                                        >
                                            Adresse email
                                        </label>


                                        <input
                                            type="email"
                                            id="contact_email"
                                            name="contact_email"
                                            value="<?= e($email) ?>"
                                            readonly
                                            tabindex="-1"
                                            class="
                                                w-full
                                                px-4
                                                py-3
                                                rounded-xl
                                                border
                                                border-slate-200
                                                bg-slate-100
                                                text-slate-600
                                                outline-none
                                                cursor-not-allowed
                                            "
                                        >


                                        <p
                                            class="
                                                mt-1.5
                                                text-xs
                                                text-slate-400
                                            "
                                        >
                                            Cet email est associé
                                            à votre compte.
                                        </p>

                                    </div>


                                    <!-- SUJET -->

                                    <div>

                                        <label
                                            for="subject"
                                            class="
                                                block
                                                mb-2
                                                text-sm
                                                font-medium
                                                text-slate-700
                                            "
                                        >
                                            Sujet
                                        </label>


                                        <select
                                            id="subject"
                                            name="subject"
                                            required
                                            class="
                                                w-full
                                                px-4
                                                py-3
                                                rounded-xl
                                                border
                                                border-slate-300
                                                bg-white
                                                text-slate-800
                                                outline-none
                                                focus:border-cyan-600
                                                focus:ring-2
                                                focus:ring-cyan-100
                                            "
                                        >

                                            <option value="">
                                                Sélectionner un sujet
                                            </option>


                                            <option value="compte">
                                                Problème avec mon compte
                                            </option>


                                            <option value="club">
                                                Question concernant un club
                                            </option>


                                            <option value="adhesion">
                                                Demande d'adhésion
                                            </option>


                                            <option value="technique">
                                                Problème technique
                                            </option>


                                            <option value="suggestion">
                                                Suggestion
                                            </option>


                                            <option value="autre">
                                                Autre
                                            </option>

                                        </select>

                                    </div>


                                    <!-- MESSAGE -->

                                    <div>

                                        <label
                                            for="message"
                                            class="
                                                block
                                                mb-2
                                                text-sm
                                                font-medium
                                                text-slate-700
                                            "
                                        >
                                            Message
                                        </label>


                                        <textarea
                                            id="message"
                                            name="message"
                                            required
                                            minlength="10"
                                            maxlength="2000"
                                            rows="8"
                                            placeholder="Écrivez votre message..."
                                            class="
                                                w-full
                                                px-4
                                                py-3
                                                rounded-xl
                                                border
                                                border-slate-300
                                                bg-white
                                                text-slate-800
                                                outline-none
                                                resize-y
                                                focus:border-cyan-600
                                                focus:ring-2
                                                focus:ring-cyan-100
                                            "
                                        ></textarea>


                                        <div
                                            class="
                                                mt-2
                                                flex
                                                justify-between
                                                gap-3
                                                text-xs
                                                text-slate-400
                                            "
                                        >

                                            <span>
                                                Minimum 10 caractères
                                            </span>

                                            <span>
                                                Maximum 2000
                                            </span>

                                        </div>

                                    </div>


                                    <!-- BOUTON -->

                                    <button
                                        type="submit"
                                        name="send_contact"
                                        class="
                                            w-full
                                            sm:w-auto
                                            inline-flex
                                            items-center
                                            justify-center
                                            rounded-xl
                                            bg-cyan-700
                                            px-7
                                            py-3.5
                                            text-sm
                                            font-semibold
                                            text-white
                                            shadow-sm
                                            hover:bg-cyan-800
                                            active:scale-[0.99]
                                            transition
                                        "
                                    >
                                        Envoyer le message
                                    </button>


                                    <p
                                        class="
                                            text-xs
                                            leading-5
                                            text-slate-400
                                        "
                                    >
                                        Votre message sera transmis
                                        à l'administration de ClubConnect.
                                        La réponse apparaîtra dans vos
                                        notifications.
                                    </p>

                                </form>


                            <?php else: ?>


                                <!-- =================================
                                     NON CONNECTÉ
                                ================================== -->

                                <div
                                    class="
                                        min-h-[420px]
                                        flex
                                        flex-col
                                        items-center
                                        justify-center
                                        text-center
                                    "
                                >

                                    <div
                                        class="
                                            w-16
                                            h-16
                                            rounded-2xl
                                            bg-amber-50
                                            text-amber-600
                                            flex
                                            items-center
                                            justify-center
                                            text-2xl
                                            font-bold
                                        "
                                    >
                                        !
                                    </div>


                                    <h3
                                        class="
                                            mt-5
                                            text-xl
                                            font-bold
                                            text-slate-900
                                        "
                                    >
                                        Connexion nécessaire
                                    </h3>


                                    <p
                                        class="
                                            mt-3
                                            max-w-md
                                            text-sm
                                            leading-6
                                            text-slate-500
                                        "
                                    >
                                        Vous pouvez consulter la page
                                        Contact sans compte, mais
                                        l'envoi de messages est réservé
                                        aux utilisateurs connectés.
                                    </p>


                                    <div
                                        class="
                                            mt-6
                                            flex
                                            flex-col
                                            sm:flex-row
                                            gap-3
                                        "
                                    >

                                        <a
                                            href="/login.php"
                                            class="
                                                inline-flex
                                                items-center
                                                justify-center
                                                rounded-xl
                                                bg-cyan-700
                                                px-6
                                                py-3
                                                text-sm
                                                font-semibold
                                                text-white
                                                hover:bg-cyan-800
                                                transition
                                            "
                                        >
                                            Se connecter
                                        </a>


                                        <a
                                            href="/register.php"
                                            class="
                                                inline-flex
                                                items-center
                                                justify-center
                                                rounded-xl
                                                border
                                                border-slate-300
                                                bg-white
                                                px-6
                                                py-3
                                                text-sm
                                                font-semibold
                                                text-slate-700
                                                hover:bg-slate-50
                                                transition
                                            "
                                        >
                                            Créer un compte
                                        </a>

                                    </div>

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- =====================================================
         BOTTOM
    ====================================================== -->

    <section
        class="
            bg-white
            border-t
            border-slate-100
        "
    >

        <div
            class="
                max-w-7xl
                mx-auto
                px-4
                sm:px-6
                lg:px-8
                py-10
            "
        >

            <div
                class="
                    flex
                    flex-col
                    sm:flex-row
                    sm:items-center
                    sm:justify-between
                    gap-5
                "
            >

                <div>

                    <h2
                        class="
                            text-lg
                            font-bold
                        "
                    >
                        Découvrez les clubs
                    </h2>


                    <p
                        class="
                            mt-1
                            text-sm
                            text-slate-500
                        "
                    >
                        Consultez les clubs disponibles
                        à la FST Fès.
                    </p>

                </div>


                <a
                    href="/clubs.php"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        rounded-xl
                        bg-cyan-700
                        px-6
                        py-3
                        text-sm
                        font-semibold
                        text-white
                        hover:bg-cyan-800
                        transition
                    "
                >
                    Voir les clubs
                </a>

            </div>

        </div>

    </section>

</main>


<!-- =========================================================
     FOOTER
========================================================= -->

<?php

$footerPath = __DIR__ . "/includes/footer.php";

if (file_exists($footerPath)) {
    require_once $footerPath;
}

?>


</body>

</html>