<?php

session_start();

require_once __DIR__ . "/config/database.php";

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$email = strtolower(
    trim($_GET['email'] ?? '')
);

if (
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {
    header("Location: register.php");
    exit;
}

$message = $_SESSION['verification_message'] ?? '';
$type = $_SESSION['verification_type'] ?? '';

unset($_SESSION['verification_message']);
unset($_SESSION['verification_type']);

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Vérification email | ClubConnect
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body
    class="min-h-screen
           bg-slate-50
           flex
           items-center
           justify-center
           px-4"
>

    <main
        class="w-full
               max-w-md"
    >

        <div
            class="bg-white
                   border
                   border-slate-200
                   rounded-2xl
                   shadow-sm
                   p-6 sm:p-8"
        >

            <!-- Logo -->

            <div
                class="flex
                       justify-center
                       mb-6"
            >

                <div
                    class="w-14 h-14
                           rounded-2xl
                           bg-cyan-700
                           text-white
                           flex
                           items-center
                           justify-center
                           text-xl
                           font-bold"
                >
                    C
                </div>

            </div>


            <!-- Titre -->

            <h1
                class="text-2xl
                       font-bold
                       text-center
                       text-slate-900"
            >

                Vérifier votre email

            </h1>


            <p
                class="mt-2
                       text-center
                       text-sm
                       leading-6
                       text-slate-500"
            >

                Un code de vérification a été envoyé à :

            </p>


            <p
                class="mt-2
                       text-center
                       font-semibold
                       text-cyan-700
                       break-all"
            >

                <?= e($email) ?>

            </p>


            <?php if ($message !== ''): ?>

                <div
                    class="mt-5
                           p-3
                           rounded-xl
                           text-sm
                           <?= $type === 'success'
                               ? 'bg-emerald-50 text-emerald-700'
                               : 'bg-red-50 text-red-700'
                           ?>"
                >

                    <?= e($message) ?>

                </div>

            <?php endif; ?>


            <!-- Formulaire -->

            <form
                action="traitement.php"
                method="POST"
                class="mt-6"
            >

                <input
                    type="hidden"
                    name="email"
                    value="<?= e($email) ?>"
                >


                <label
                    for="verification_code"
                    class="block
                           text-sm
                           font-semibold
                           text-slate-700
                           mb-2"
                >

                    Code de vérification

                </label>


                <input
                    type="text"
                    id="verification_code"
                    name="verification_code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    required
                    placeholder="123456"
                    class="w-full
                           px-4
                           py-3
                           rounded-xl
                           border
                           border-slate-300
                           text-center
                           text-xl
                           tracking-[0.4em]
                           outline-none
                           focus:border-cyan-600
                           focus:ring-2
                           focus:ring-cyan-100"
                >


                <button
                    type="submit"
                    name="verify_email"
                    class="w-full
                           mt-4
                           px-5
                           py-3
                           rounded-xl
                           bg-cyan-700
                           text-white
                           font-semibold
                           hover:bg-cyan-800
                           transition"
                >

                    Vérifier mon email

                </button>

            </form>


            <!-- Renvoyer -->

            <form
                action="traitement.php"
                method="POST"
                class="mt-3"
            >

                <input
                    type="hidden"
                    name="email"
                    value="<?= e($email) ?>"
                >

                <button
                    type="submit"
                    name="resend_code"
                    class="w-full
                           px-5
                           py-3
                           rounded-xl
                           border
                           border-slate-300
                           text-slate-700
                           font-semibold
                           hover:bg-slate-50"
                >

                    Renvoyer le code

                </button>

            </form>


            <div
                class="mt-6
                       text-center"
            >

                <a
                    href="login.php"
                    class="text-sm
                           font-semibold
                           text-cyan-700"
                >

                    Retour à la connexion

                </a>

            </div>

        </div>

    </main>

</body>

</html>