<?php

/*
|--------------------------------------------------------------------------
| ClubConnect
| student/notifications.php
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../config/database.php";


/*
|--------------------------------------------------------------------------
| ID utilisateur
|--------------------------------------------------------------------------
*/

$userId = (int) (
    $_SESSION['id']
    ?? $_SESSION['user_id']
    ?? 0
);


if ($userId <= 0) {
    header("Location: ../login.php");
    exit;
}


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
| CSRF
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));
}


/*
|--------------------------------------------------------------------------
| Marquer une notification comme lue
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['mark_read'])
) {

    $notificationId =
        (int) ($_POST['notification_id'] ?? 0);

    $csrf =
        $_POST['csrf_token'] ?? '';


    if (
        !hash_equals(
            $_SESSION['csrf_token'],
            $csrf
        )
    ) {
        die("Requête invalide.");
    }


    if ($notificationId > 0) {

        $stmt = mysqli_prepare(
            $con,
            "
            UPDATE notifications
            SET is_read = 1
            WHERE id = ?
              AND user_id = ?
            "
        );


        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "ii",
                $notificationId,
                $userId
            );

            mysqli_stmt_execute($stmt);

            mysqli_stmt_close($stmt);
        }
    }


    header("Location: notifications.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Marquer toutes les notifications comme lues
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['mark_all_read'])
) {

    $csrf =
        $_POST['csrf_token'] ?? '';


    if (
        !hash_equals(
            $_SESSION['csrf_token'],
            $csrf
        )
    ) {
        die("Requête invalide.");
    }


    $stmt = mysqli_prepare(
        $con,
        "
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = ?
          AND is_read = 0
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


    header("Location: notifications.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Récupérer les notifications
|--------------------------------------------------------------------------
*/

$notifications = [];


$stmt = mysqli_prepare(
    $con,
    "
    SELECT
        id,
        title,
        message,
        type,
        is_read,
        created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC, id DESC
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


if (!mysqli_stmt_execute($stmt)) {

    die(
        "Erreur SQL : " .
        e(mysqli_stmt_error($stmt))
    );
}


$result =
    mysqli_stmt_get_result($stmt);


while (
    $notification =
    mysqli_fetch_assoc($result)
) {

    $notifications[] =
        $notification;
}


mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Nombre de notifications non lues
|--------------------------------------------------------------------------
*/

$unreadCount = 0;


$stmt = mysqli_prepare(
    $con,
    "
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE user_id = ?
      AND is_read = 0
    "
);


if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $userId
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $row =
        mysqli_fetch_assoc($result);

    $unreadCount =
        (int) ($row['total'] ?? 0);

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| Informations utilisateur
|--------------------------------------------------------------------------
*/

$userName =
    $_SESSION['name'] ?? 'Étudiant';

?>


<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
       ClubConnect | Notifications  
    </title>


</head>


<body class="min-h-screen bg-slate-50">


    <!-- =========================================================
     HEADER
========================================================= -->

  <?php
require_once "../includes/header.php";
?>

<?php
require_once "../includes/PopUpMsg.php";
?>



    <!-- =========================================================
     MAIN
========================================================= -->

    <main class="max-w-4xl
           mx-auto
           px-4 sm:px-6
           py-8">


        <!-- Titre -->

        <div class="flex
               flex-col
               sm:flex-row
               sm:items-center
               sm:justify-between
               gap-4
               mb-8">

            <div>

                <p class="text-sm
                       font-semibold
                       text-cyan-700">

                    CLUBCONNECT

                </p>


                <h1 class="mt-1
                       text-3xl
                       font-bold
                       text-slate-900">

                    Notifications

                </h1>


                <p class="mt-2
                       text-sm
                       text-slate-500">

                    Consultez les dernières informations
                    concernant vos clubs.

                </p>

            </div>


            <?php if ($unreadCount > 0): ?>

                <form method="POST">

                    <input type="hidden" name="csrf_token" value="<?= e(
                        $_SESSION['csrf_token']
                    ) ?>">


                    <button type="submit" name="mark_all_read" class="px-4 py-2.5
                           rounded-xl
                           border
                           border-slate-300
                           bg-white
                           text-sm
                           font-semibold
                           text-slate-700
                           hover:bg-slate-50">

                        Tout marquer comme lu

                    </button>

                </form>

            <?php endif; ?>

        </div>



        <!-- =====================================================
         NOTIFICATIONS
    ====================================================== -->

        <?php if (
            count($notifications) > 0
        ): ?>


            <div class="space-y-3">


                <?php foreach (
                    $notifications
                    as $notification
                ): ?>


                    <?php

                    $isRead =
                        (int) 
                        $notification['is_read'] === 1;


                    $type =
                        strtolower(
                            trim(
                                (string) 
                                $notification['type']
                            )
                        );


                    switch ($type) {

                        case 'success':

                            $iconClass =
                                'bg-emerald-50 text-emerald-600';

                            $dotClass =
                                'bg-emerald-500';

                            break;


                        case 'warning':

                            $iconClass =
                                'bg-amber-50 text-amber-600';

                            $dotClass =
                                'bg-amber-500';

                            break;


                        case 'error':

                            $iconClass =
                                'bg-red-50 text-red-600';

                            $dotClass =
                                'bg-red-500';

                            break;


                        default:

                            $iconClass =
                                'bg-cyan-50 text-cyan-600';

                            $dotClass =
                                'bg-cyan-500';

                            break;
                    }

                    ?>


                    <article class="relative
                           bg-white
                           border
                           rounded-2xl
                           p-5
                           transition
                           <?= $isRead
                               ? 'border-slate-200'
                               : 'border-cyan-200 bg-cyan-50/30'
                               ?>">


                        <div class="flex
                               gap-4">


                            <!-- Icône -->

                            <div class="w-11 h-11
                                   shrink-0
                                   rounded-xl
                                   flex
                                   items-center
                                   justify-center
                                   <?= $iconClass ?>">

                                <?php if (
                                    $type === 'success'
                                ): ?>

                                    ✓

                                <?php elseif (
                                    $type === 'warning'
                                ): ?>

                                    !

                                <?php elseif (
                                    $type === 'error'
                                ): ?>

                                    ×

                                <?php else: ?>

                                    i

                                <?php endif; ?>

                            </div>



                            <!-- Contenu -->

                            <div class="flex-1
                                   min-w-0">

                                <div class="flex
                                       items-start
                                       justify-between
                                       gap-3">

                                    <div>

                                        <div class="flex
                                               items-center
                                               gap-2">

                                            <h2 class="font-bold
                                                   text-slate-900">

                                                <?= e(
                                                    $notification['title']
                                                ) ?>

                                            </h2>


                                            <?php if (
                                                !$isRead
                                            ): ?>

                                                <span class="w-2
                                                       h-2
                                                       rounded-full
                                                       <?= $dotClass ?>"></span>

                                            <?php endif; ?>

                                        </div>


                                        <p class="mt-2
                                               text-sm
                                               leading-6
                                               text-slate-600">

                                            <?= nl2br(
                                                e(
                                                    $notification['message']
                                                )
                                            ) ?>

                                        </p>

                                    </div>


                                    <?php if (
                                        !$isRead
                                    ): ?>

                                        <form method="POST" class="shrink-0">

                                            <input type="hidden" name="csrf_token" value="<?= e(
                                                $_SESSION['csrf_token']
                                            ) ?>">


                                            <input type="hidden" name="notification_id" value="<?= (int) 
                                                $notification['id']
                                                ?>">


                                            <button type="submit" name="mark_read" class="text-xs
                                                   font-semibold
                                                   text-cyan-700
                                                   hover:underline">

                                                Marquer lu

                                            </button>

                                        </form>

                                    <?php endif; ?>

                                </div>



                                <!-- Date -->

                                <p class="mt-3
                                       text-xs
                                       text-slate-400">

                                    <?php if (
                                        !empty(
                                        $notification['created_at']
                                    )
                                    ): ?>

                                        <?= e(
                                            date(
                                                'd/m/Y à H:i',
                                                strtotime(
                                                    $notification[
                                                        'created_at'
                                                    ]
                                                )
                                            )
                                        ) ?>

                                    <?php endif; ?>

                                </p>

                            </div>

                        </div>

                    </article>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <!-- Aucune notification -->

            <div class="bg-white
                   border
                   border-slate-200
                   rounded-2xl
                   p-12
                   text-center">

                <div class="mx-auto
                       w-16 h-16
                       rounded-2xl
                       bg-slate-100
                       text-slate-400
                       flex
                       items-center
                       justify-center
                       text-2xl">

                    -

                </div>


                <h2 class="mt-5
                       text-lg
                       font-bold
                       text-slate-900">

                    Aucune notification

                </h2>


                <p class="mt-2
                       text-sm
                       text-slate-500">

                    Vous n'avez aucune nouvelle notification.

                </p>


                <a href="index.php" class="inline-flex
                       mt-6
                       px-5 py-2.5
                       rounded-xl
                       bg-cyan-700
                       text-white
                       text-sm
                       font-semibold
                       hover:bg-cyan-800">

                    Retour à l'accueil

                </a>

            </div>

        <?php endif; ?>


    </main>


    <!-- =========================================================
     FOOTER
========================================================= -->

    <?php
    require_once __DIR__ . "/../includes/footer.php";
    ?>


</body>

</html>