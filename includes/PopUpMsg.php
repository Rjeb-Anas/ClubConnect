<?php

/*
|--------------------------------------------------------------------------
| ClubConnect - Système de messages
|--------------------------------------------------------------------------
| Types disponibles :
| success
| error
| warning
| info
|--------------------------------------------------------------------------
*/


// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Ajouter un message
|--------------------------------------------------------------------------
*/

function setMessage(string $type, string $message): void
{
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}


/*
|--------------------------------------------------------------------------
| Raccourcis
|--------------------------------------------------------------------------
*/

function successMessage(string $message): void
{
    setMessage('success', $message);
}

function errorMessage(string $message): void
{
    setMessage('error', $message);
}

function warningMessage(string $message): void
{
    setMessage('warning', $message);
}

function infoMessage(string $message): void
{
    setMessage('info', $message);
}


/*
|--------------------------------------------------------------------------
| Afficher le message
|--------------------------------------------------------------------------
*/

function displayMessage(): void
{
    if (!isset($_SESSION['flash_message'])) {
        return;
    }

    $messageData = $_SESSION['flash_message'];

    // Supprimer immédiatement le message
    unset($_SESSION['flash_message']);

    $type = $messageData['type'] ?? 'info';
    $message = $messageData['message'] ?? '';

    // Sécurité
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');


    /*
    |--------------------------------------------------------------------------
    | Configuration selon le type
    |--------------------------------------------------------------------------
    */

    switch ($type) {

        case 'success':
            $icon = '✓';
            $title = 'Succès';
            $bg = 'bg-emerald-50';
            $border = 'border-emerald-200';
            $iconBg = 'bg-emerald-100';
            $iconText = 'text-emerald-600';
            $titleText = 'text-emerald-800';
            $messageText = 'text-emerald-700';
            break;


        case 'error':
            $icon = '!';
            $title = 'Erreur';
            $bg = 'bg-red-50';
            $border = 'border-red-200';
            $iconBg = 'bg-red-100';
            $iconText = 'text-red-600';
            $titleText = 'text-red-800';
            $messageText = 'text-red-700';
            break;


        case 'warning':
            $icon = '!';
            $title = 'Attention';
            $bg = 'bg-amber-50';
            $border = 'border-amber-200';
            $iconBg = 'bg-amber-100';
            $iconText = 'text-amber-600';
            $titleText = 'text-amber-800';
            $messageText = 'text-amber-700';
            break;


        default:
            $icon = 'i';
            $title = 'Information';
            $bg = 'bg-cyan-50';
            $border = 'border-cyan-200';
            $iconBg = 'bg-cyan-100';
            $iconText = 'text-cyan-600';
            $titleText = 'text-cyan-800';
            $messageText = 'text-cyan-700';
            break;
    }

    ?>

    <!-- Popup -->
    <div
        id="clubConnectMessage"
        class="fixed top-5 right-5 z-9999
               w-[calc(100%-2rem)] max-w-md
               <?= $bg ?> <?= $border ?>
               border rounded-2xl shadow-xl
               overflow-hidden
               animate-message"
    >

        <div class="p-4 flex items-start gap-3">

            <!-- Icône -->
            <div
                class="shrink-0 w-10 h-10 rounded-full
                       <?= $iconBg ?> <?= $iconText ?>
                       flex items-center justify-center
                       font-bold text-lg"
            >
                <?= $icon ?>
            </div>


            <!-- Contenu -->
            <div class="flex-1 min-w-0">

                <h3 class="font-semibold <?= $titleText ?>">
                    <?= $title ?>
                </h3>

                <p class="mt-1 text-sm leading-5 <?= $messageText ?>">
                    <?= $message ?>
                </p>

            </div>


            <!-- Fermer -->
            <button
                type="button"
                onclick="closeClubConnectMessage()"
                class="shrink-0 w-7 h-7 rounded-lg
                       flex items-center justify-center
                       text-slate-400 hover:text-slate-700
                       hover:bg-black/5 transition"
                aria-label="Fermer"
            >
                ×
            </button>

        </div>


        <!-- Barre de progression -->
        <div class="h-1 bg-black/5">

            <div
                id="messageProgress"
                class="h-full bg-current <?= $iconText ?>"
            ></div>

        </div>

    </div>


    <style>
        @keyframes messageIn {
            from {
                opacity: 0;
                transform: translateX(100%);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes messageOut {
            from {
                opacity: 1;
                transform: translateX(0);
            }

            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }

        .animate-message {
            animation: messageIn 0.35s ease-out;
        }

        .animate-message-close {
            animation: messageOut 0.3s ease-in forwards;
        }

        #messageProgress {
            width: 100%;
            animation: messageProgress 5s linear forwards;
        }

        @keyframes messageProgress {
            from {
                width: 100%;
            }

            to {
                width: 0%;
            }
        }

        @media (max-width: 640px) {
            #clubConnectMessage {
                top: 1rem;
                left: 1rem;
                right: 1rem;
                width: auto;
                max-width: none;
            }
        }
    </style>


    <script>
        function closeClubConnectMessage() {

            const popup = document.getElementById(
                'clubConnectMessage'
            );

            if (!popup) return;

            popup.classList.remove('animate-message');
            popup.classList.add('animate-message-close');

            setTimeout(() => {
                popup.remove();
            }, 300);
        }


        // Fermeture automatique après 5 secondes
        setTimeout(() => {
            closeClubConnectMessage();
        }, 5000);
    </script>

    <?php
}


/*
|--------------------------------------------------------------------------
| Afficher automatiquement le message
|--------------------------------------------------------------------------
*/

displayMessage();

