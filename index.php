<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClubConnect | Accueil</title>
</head>
<body>
<?php
require_once "includes/header.php";
?>

<?php
require_once "includes/PopUpMsg.php";
?>


<main>

    <!-- HERO -->
    <section class="min-h-[calc(100vh-64px)] flex items-center">

        <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8">

            <div class="grid lg:grid-cols-2 gap-12 items-center">

                <!-- Texte -->
                <div>

                    <span
                        class="inline-flex items-center gap-2
                               px-3 py-1.5 rounded-full
                               bg-cyan-50 text-cyan-700
                               text-sm font-medium mb-6">

                        <span class="w-2 h-2 rounded-full bg-cyan-600"></span>

                        FST Fès
                    </span>


                    <h1
                        class="text-4xl sm:text-5xl lg:text-6xl
                               font-bold tracking-tight
                               text-slate-900 leading-tight">

                        Tous les clubs de la FST,
                        <span class="text-cyan-700">
                            au même endroit.
                        </span>

                    </h1>


                    <p
                        class="mt-6 max-w-xl
                               text-lg leading-8
                               text-slate-600">

                        ClubConnect facilite la découverte des clubs,
                        l'adhésion aux activités et la gestion de la
                        vie associative de la FST Fès.

                    </p>


                    <!-- Boutons -->
                    <div class="mt-8 flex flex-col sm:flex-row gap-3">

                        <a
                            href="clubs.php"
                            class="inline-flex items-center justify-center
                                   px-6 py-3 rounded-xl
                                   bg-cyan-700 text-white
                                   font-medium
                                   hover:bg-cyan-800
                                   transition shadow-sm">

                            Découvrir les clubs

                            <svg
                                class="w-5 h-5 ml-2"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 8l4 4m0 0l-4 4m4-4H3"/>

                            </svg>

                        </a>


                        <a
                            href="register.php"
                            class="inline-flex items-center justify-center
                                   px-6 py-3 rounded-xl
                                   border border-slate-300
                                   text-slate-700
                                   font-medium
                                   hover:bg-slate-100
                                   transition">

                            Rejoindre ClubConnect

                        </a>

                    </div>

                </div>


             

            </div>

        </div>

    </section>


    <!-- FEATURES -->
    <section class="bg-white border-y border-slate-100">

        <div
            class="max-w-7xl mx-auto
                   px-4 sm:px-6 lg:px-8
                   py-20">

            <div class="max-w-2xl mb-12">

                <p
                    class="text-sm font-semibold
                           text-cyan-700 uppercase
                           tracking-wider">
                    Pourquoi ClubConnect ?
                </p>

                <h2
                    class="mt-3 text-3xl
                           font-bold
                           text-slate-900">
                    Une plateforme pensée pour les étudiants
                </h2>

                <p
                    class="mt-4 text-slate-600">
                    Tout ce dont les étudiants et les responsables
                    de clubs ont besoin, dans une seule plateforme.
                </p>

            </div>


            <div
                class="grid grid-cols-1
                       md:grid-cols-3
                       gap-6">

                <!-- Card 1 -->
                <div
                    class="p-6 rounded-2xl
                           border border-slate-200
                           hover:border-cyan-300
                           hover:shadow-md
                           transition">

                    <div
                        class="w-11 h-11
                               rounded-xl
                               bg-cyan-50
                               text-cyan-700
                               flex items-center
                               justify-center
                               mb-5">

                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-10a4 4 0 110 8 4 4 0 000-8z"/>

                        </svg>

                    </div>

                    <h3
                        class="text-lg font-semibold
                               text-slate-900">
                        Découvrir
                    </h3>

                    <p
                        class="mt-2 text-sm
                               leading-6 text-slate-600">
                        Explore facilement les clubs disponibles
                        à la FST et découvre leurs activités.
                    </p>

                </div>


                <!-- Card 2 -->
                <div
                    class="p-6 rounded-2xl
                           border border-slate-200
                           hover:border-cyan-300
                           hover:shadow-md
                           transition">

                    <div
                        class="w-11 h-11
                               rounded-xl
                               bg-cyan-50
                               text-cyan-700
                               flex items-center
                               justify-center
                               mb-5">

                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"/>

                        </svg>

                    </div>

                    <h3
                        class="text-lg font-semibold
                               text-slate-900">
                        Rejoindre
                    </h3>

                    <p
                        class="mt-2 text-sm
                               leading-6 text-slate-600">
                        Envoie une demande pour rejoindre le club
                        qui correspond à tes intérêts.
                    </p>

                </div>


                <!-- Card 3 -->
                <div
                    class="p-6 rounded-2xl
                           border border-slate-200
                           hover:border-cyan-300
                           hover:shadow-md
                           transition">

                    <div
                        class="w-11 h-11
                               rounded-xl
                               bg-cyan-50
                               text-cyan-700
                               flex items-center
                               justify-center
                               mb-5">

                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>

                        </svg>

                    </div>

                    <h3
                        class="text-lg font-semibold
                               text-slate-900">
                        Participer
                    </h3>

                    <p
                        class="mt-2 text-sm
                               leading-6 text-slate-600">
                        Reste informé des événements et participe
                        à la vie associative de la FST.
                    </p>

                </div>

            </div>

        </div>

    </section>


    <!-- CTA -->
    <section>

        <div
            class="max-w-7xl mx-auto
                   px-4 sm:px-6 lg:px-8
                   py-20">

            <div
                class="rounded-3xl
                       bg-cyan-700
                       px-6 py-12
                       sm:px-12
                       text-center">

                <h2
                    class="text-3xl
                           font-bold
                           text-white">
                    Trouve ton club
                </h2>

                <p
                    class="mt-3 max-w-xl
                           mx-auto
                           text-cyan-100">
                    Explore les clubs de la FST Fès et participe
                    à des activités qui correspondent à tes passions.
                </p>

                <a
                    href="clubs.php"
                    class="inline-flex
                           mt-7
                           px-6 py-3
                           rounded-xl
                           bg-white
                           text-cyan-700
                           font-semibold
                           hover:bg-cyan-50
                           transition">

                    Explorer les clubs

                </a>

            </div>

        </div>

    </section>

</main>


<?php
require_once "includes/footer.php";
?>

</body>
</html>