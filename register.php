<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClubConnect | Inscription</title>
</head>

<body>
    <?php
    require_once "includes/header.php";
    require_once "includes/PopUpMsg.php";

    ?>

    <main class="min-h-[calc(100vh-64px)] bg-slate-50 py-12 px-4">

        <div class="max-w-5xl mx-auto">

            <!-- En-tête -->
            <div class="text-center mb-10">

                <span class="inline-flex px-3 py-1.5 rounded-full
                       bg-cyan-50 text-cyan-700
                       text-sm font-medium">
                    ClubConnect
                </span>

                <h1 class="mt-4 text-3xl sm:text-4xl
                       font-bold text-slate-900">
                    Créer votre compte
                </h1>

                <p class="mt-3 text-slate-600">
                    Rejoignez les clubs et la vie étudiante de la FST Fès.
                </p>

            </div>


            <!-- Formulaire -->
            <div class="bg-white border border-slate-200
                   rounded-3xl shadow-sm overflow-hidden">

                <div class="p-6 sm:p-10">

                    <form action="traitement.php" method="POST" class="space-y-8" autocomplete="off">

                        <!-- Informations personnelles -->
                        <div>

                            <h2 class="text-lg font-semibold
                                   text-slate-900">
                                Informations personnelles
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Renseignez vos informations personnelles.
                            </p>

                        </div>


                        <div class="grid grid-cols-1
                               md:grid-cols-2
                               gap-5">

                            <!-- Nom -->
                            <div>

                                <label for="nom" class="block text-sm
                                       font-medium text-slate-700 mb-2">
                                    Nom
                                </label>

                                <input type="text" id="nom" name="nom" required minlength="2" maxlength="50"
                                    placeholder="Votre nom" class="w-full px-4 py-3
                                       rounded-xl
                                       border border-slate-300
                                       outline-none
                                       focus:border-cyan-600
                                       focus:ring-2
                                       focus:ring-cyan-100
                                       transition">

                            </div>


                            <!-- Prénom -->
                            <div>

                                <label for="prenom" class="block text-sm
                                       font-medium text-slate-700 mb-2">
                                    Prénom
                                </label>

                                <input type="text" id="prenom" name="prenom" required minlength="2" maxlength="50"
                                    placeholder="Votre prénom" class="w-full px-4 py-3
                                       rounded-xl
                                       border border-slate-300
                                       outline-none
                                       focus:border-cyan-600
                                       focus:ring-2
                                       focus:ring-cyan-100
                                       transition">

                            </div>


                            <!-- Sexe -->
                            <div>

                                <label for="sexe" class="block text-sm
                                       font-medium text-slate-700 mb-2">
                                    Sexe
                                </label>

                                <select id="sexe" name="sexe" required class="w-full px-4 py-3
                                       rounded-xl
                                       border border-slate-300
                                       bg-white
                                       outline-none
                                       focus:border-cyan-600
                                       focus:ring-2
                                       focus:ring-cyan-100">

                                    <option value="">
                                        Sélectionner
                                    </option>

                                    <option value="homme">
                                        Homme
                                    </option>

                                    <option value="femme">
                                        Femme
                                    </option>

                                </select>

                            </div>


                            <!-- Téléphone -->
                            <div>

                                <label for="tel" class="block text-sm
                                       font-medium text-slate-700 mb-2">
                                    Téléphone
                                </label>

                                <input type="tel" id="tel" name="tel" required pattern="^(06|07)[0-9]{8}$"
                                    maxlength="10" placeholder="06XXXXXXXX" class="w-full px-4 py-3
                                       rounded-xl
                                       border border-slate-300
                                       outline-none
                                       focus:border-cyan-600
                                       focus:ring-2
                                       focus:ring-cyan-100">

                            </div>


                            <!-- Date naissance -->
                            <div class="md:col-span-2">

                                <label for="date_naissance" class="block text-sm
                                       font-medium text-slate-700 mb-2">
                                    Date de naissance
                                </label>

                                <input type="date" id="date_naissance" name="date_naissance" required class="w-full px-4 py-3
                                       rounded-xl
                                       border border-slate-300
                                       outline-none
                                       focus:border-cyan-600
                                       focus:ring-2
                                       focus:ring-cyan-100">

                            </div>

                        </div>


                        <!-- Compte -->
                        <div class="pt-2">

                            <h2 class="text-lg font-semibold
                                   text-slate-900">
                                Informations du compte
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Utilisez votre adresse académique USMBA.
                            </p>

                        </div>


                        <div class="space-y-5">

                            <!-- Email -->
                            <div>

                                <label for="email" class="block text-sm
                                       font-medium text-slate-700 mb-2">
                                    Email académique
                                </label>

                                <div class="relative">

                                    <input type="email" id="email" name="email" required
                                        placeholder="prenom.nom@usmba.ac.ma" pattern="^[A-Za-z0-9._%+-]+@usmba\.ac\.ma$"
                                        class="w-full px-4 py-3
                                           rounded-xl
                                           border border-slate-300
                                           outline-none
                                           focus:border-cyan-600
                                           focus:ring-2
                                           focus:ring-cyan-100">

                                </div>

                                <p class="mt-2 text-xs text-slate-500">
                                    Seules les adresses
                                    <strong>@usmba.ac.ma</strong>
                                    sont acceptées.
                                </p>

                            </div>


                            <div class="grid grid-cols-1
                                   md:grid-cols-2 gap-5">

                                <!-- Mot de passe -->
                                <div>

                                    <label for="password" class="block text-sm
                                           font-medium text-slate-700 mb-2">
                                        Mot de passe
                                    </label>

                                    <input type="password" id="password" name="password" required minlength="8"
                                        placeholder="••••••••" class="w-full px-4 py-3
                                           rounded-xl
                                           border border-slate-300
                                           outline-none
                                           focus:border-cyan-600
                                           focus:ring-2
                                           focus:ring-cyan-100">

                                    <p class="mt-2 text-xs text-slate-500">
                                        Minimum 8 caractères.
                                    </p>

                                </div>


                                <!-- Confirmation -->
                                <div>

                                    <label for="password_confirm" class="block text-sm
                                           font-medium text-slate-700 mb-2">
                                        Confirmer le mot de passe
                                    </label>

                                    <input type="password" id="password_confirm" name="password_confirm" required
                                        minlength="8" placeholder="••••••••" class="w-full px-4 py-3
                                           rounded-xl
                                           border border-slate-300
                                           outline-none
                                           focus:border-cyan-600
                                           focus:ring-2
                                           focus:ring-cyan-100">

                                </div>

                            </div>

                        </div>


                        <!-- Information vérification -->
                        <div class="flex gap-3
                               p-4 rounded-xl
                               bg-cyan-50
                               border border-cyan-100">

                            <div class="shrink-0 w-8 h-8
                                   rounded-lg
                                   bg-cyan-100
                                   text-cyan-700
                                   flex items-center
                                   justify-center
                                   font-bold">
                                i
                            </div>

                            <div>

                                <p class="text-sm font-medium
                                       text-cyan-900">
                                    Vérification de votre adresse email
                                </p>

                                <p class="mt-1 text-sm
                                       text-cyan-700 leading-5">
                                    Après votre inscription, un code de
                                    vérification sera envoyé à votre adresse
                                    académique. Vous devrez confirmer ce code
                                    avant d'utiliser votre compte.
                                </p>

                            </div>

                        </div>


                        <!-- Bouton -->
                        <button type="submit" name="register" class="w-full py-3.5
                               rounded-xl
                               bg-cyan-700
                               text-white
                               font-semibold
                               hover:bg-cyan-800
                               active:scale-[0.99]
                               transition">

                            Créer mon compte

                        </button>


                        <!-- Connexion -->
                        <p class="text-center text-sm
                               text-slate-500">

                            Vous avez déjà un compte ?

                            <a href="login.php" class="font-semibold
                                   text-cyan-700
                                   hover:text-cyan-800">
                                Se connecter
                            </a>

                        </p>

                    </form>

                </div>

            </div>

        </div>

    </main>


    <?php
    require_once "includes/footer.php";
    ?>
</body>

</html>