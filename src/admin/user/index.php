<?php

    include_once '../../Class/autoload.php';
    include_once '../../config.php';

    $settings = new Gratbrav\Torrentbug\Settings();

include_once '../../functions.php';

    // redirect if no admin
    if (!$_SESSION['is_admin']) {
        $options = [
            'user_id' => $cfg['user'],
            'file' => $_SERVER['PHP_SELF'],
            'action' => $cfg["constants"]["access_denied"]
        ];
        $log = new \Gratbrav\Torrentbug\Log\Service();
        $log->save($options);

        header("location: ../../index.php");
    }

    $subMenu = 'admin';
    include_once '../../header.php';

    $userService = new Gratbrav\Torrentbug\User\Service();

    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
    // remove user
    if ($action == 'deleteuser') {
        $userId = filter_input(INPUT_GET, 'userid', FILTER_VALIDATE_INT);
        $userService->delete($userId);
        header("location: index.php");
        exit;
    }

    $users = $userService->getUsers();
?>

<div class="container">
    <div class="row">

        <div class="card" style="margin-top: 15px;">
            <div class="card-header">
                <i class="fa fa-users" aria-hidden="true"></i>
                <?= _USERDETAILS ?>
                <a href="edit.php" class="btn btn-primary btn-sm text-capitalize pull-right"><?= _NEWUSER_MENU ?></a>
            </div>
            <div class="card-block">

                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th style="width:40px"><?= _ID ?></th>
                        <th><?= _USER ?></th>
                        <th><?= _JOINED ?></th>
                        <th><?= _LASTVISIT ?></th>
                        <th style="width:40px"><i class="fa fa-pencil" aria-hidden="true"></i></th>
                        <th style="width:40px"><i class="fa fa-trash-o" aria-hidden="true"></i></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ((array)$users as $user) {
                        $iconClass = (IsOnline($user->getUserId())) ? 'text-success' : 'text-muted';
                    ?>
                    <tr>
                        <td><?= $user->getUid() ?>
                        <td>
                            <?php if (IsUser($user->getUserId())) { ?><a href="../../message.php?to_user=<?= $user->getUserId() ?>"><?php } ?>
                            <i class="fa fa-user <?= $iconClass ?>" aria-hidden="true"></i>
                            <?= $user->getUserId() ?>
                            <?php if (IsUser($user->getUserId())) { ?></a><?php } ?>
                        </td>
                        <td style="text-align: center"><?= date(_DATEFORMAT, $user->getTimeCreated()) ?></td>
                        <td style="text-align: center"><?= $user->getLastVisit() ?></td>
                        <td>
                            <?php if ($user->getUserLevel() <= 1 || IsSuperAdmin()) { ?>
                                <a href="edit.php?userid=<?= $user->getUid() ?>" title="<?= _EDIT . " " . $user->getUserId() ?>">
                                    <i class="fa fa-pencil" aria-hidden="true"></i>
                                </a>
                            <?php } ?>
                        </td>
                        <td>
                            <?php if ($user->getUserLevel() <= 1) { ?>
                                <a class="remove_user" data-userid="<?= $user->getUid() ?>" data-username="<?= $user->getUserId() ?>" href="#">
                                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                                </a>
                            <?php } ?>
                        </td>
                </tr>
                <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>

$( document ).ready(function() {

    /**
     * Confirm to remove user
     */
   $( ".remove_user" ).on( "click", function () {

       var question = "<?= _WARNING . ": " . _ABOUTTODELETE ?>: " + this.dataset.username;
       if ( confirm( question ) ) {
           location.href = "index.php?action=deleteuser&userid=" + this.dataset.userid;
       }

   } );
 
} );

</script>
