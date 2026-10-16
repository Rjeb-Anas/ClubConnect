<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClubConnect | Mot de passe oublié</title>
</head>
<body>

<?php

require_once "includes/header.php";
require_once "includes/PopUpMsg.php";

?>

<main class="min-h-[calc(100vh-64px)] bg-slate-50 flex items-center py-12 px-4">

    <div class="w-full max-w-md mx-auto">

        <!-- Header -->
        <div class="text-center mb-8">

            <div
                class="w-14 h-14 mx-auto
                       rounded-2xl
                       bg-cyan-700
                       text-white
                       flex items-center
                       justify-center
                       text-2xl font-bold">
                C
            </div>

            <h1
                class="mt-5 text-3xl
                       font-bold text-slate-900">
                Mot de passe oublié ?
            </h1>

            <p
                class="mt-3 text-slate-500
                       leading-6">
                Entrez votre adresse académique.
                Nous vous enverrons un lien pour
                créer un nouveau mot de passe.
            </p>

        </div>


        <!-- Formulaire -->
        <div
            class="bg-white
                   border border-slate-200
                   rounded-3xl
                   shadow-sm
                   p-6 sm:p-8">

            <form
                action="traitement.php"
                method="POST"
                class="space-y-6">

                <!-- Email -->
                <div>

                    <label
                        for="email"
                        class="block mb-2
                               text-sm font-medium
                               text-slate-700">
                        Email académique
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        autocomplete="email"
                        placeholder="prenom.nom@usmba.ac.ma"
                        pattern="^[A-Za-z0-9._%+-]+@usmba\.ac\.ma$"
                        class="w-full
                               px-4 py-3
                               rounded-xl
                               border border-slate-300
                               outline-none
                               transition
                               focus:border-cyan-600
                               focus:ring-2
                               focus:ring-cyan-100">

                    <p
                        class="mt-2 text-xs
                               text-slate-500">
                        Utilisez votre adresse académique
                        <strong>@usmba.ac.ma</strong>.
                    </p>

                </div>


                <!-- Information -->
                <div
                    class="flex gap-3
                           p-4
                           rounded-xl
                           bg-cyan-50
                           border border-cyan-100">

                    <div
                        class="shrink-0
                               w-8 h-8
                               rounded-lg
                               bg-cyan-100
                               text-cyan-700
                               flex items-center
                               justify-center
                               font-bold">
                        i
                    </div>

                    <p
                        class="text-sm
                               leading-5
                               text-cyan-800">
                        Un lien de réinitialisation sera envoyé
                        à votre adresse académique si elle est
                        associée à un compte.
                    </p>

                </div>


                <!-- Bouton -->
                <button
                    type="submit"
                    name="forgot_password"
                    class="w-full
                           py-3.5
                           rounded-xl
                           bg-cyan-700
                           text-white
                           font-semibold
                           hover:bg-cyan-800
                           active:scale-[0.99]
                           transition">

                    Envoyer le lien

                </button>

            </form>


            <!-- Retour -->
            <div
                class="mt-6
                       text-center
                       border-t border-slate-100
                       pt-6">

                <a
                    href="login.php"
                    class="text-sm
                           font-medium
                           text-cyan-700
                           hover:text-cyan-800">

                    ← Retour à la connexion

                </a>

            </div>

        </div>


        <p
            class="mt-6 text-center
                   text-xs text-slate-400">
            ClubConnect · FST Fès
        </p>

    </div>

</main>


<?php
require_once "includes/footer.php";
?>


</body>
</html>