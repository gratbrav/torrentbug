<?php 
    $total_activity = GetActivityCount();

    $userService = new Gratbrav\Torrentbug\User\Service();
    $users = $userService->getUsers();
?>
<div class="container">
    <div class="row">

        <div class="card">
            <div class="card-header">
                <i class="fa fa-users" aria-hidden="true"></i>
                <?= _USERDETAILS ?>
            </div>
            <div class="card-block">

                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th><?= _USER ?></th>
                        <th><?= _HITS ?></th>
                        <th><?= _UPLOADACTIVITY . ' (' . $settings->get('days_to_keep') . ' ' . _DAYS . ')' ?></th>
                        <th></th>
                        <th><?= _JOINED ?></th>
                        <th><?= _LASTVISIT ?></th>
                        <th><i class="fa fa-pencil" aria-hidden="true"></i></th>
                        <th><i class="fa fa-trash-o" aria-hidden="true"></i></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ((array)$users as $user) {
                        $user_activity = GetActivityCount($user->getUserId());
 
                        if ($user_activity == 0) {
                            $user_percent = 0;
                        } else {
                            $user_percent = number_format(($user_activity / $total_activity) * 100);
                        }

                        $iconClass = (IsOnline($user->getUserId())) ? 'text-success' : 'text-muted';
                    ?>
                    <tr>
                        <td>
                            <?php if (IsUser($user->getUserId())) { ?><a href="../message.php?to_user=<?= $user->getUserId() ?>"><?php } ?>
                            <i class="fa fa-user <?= $iconClass ?>" aria-hidden="true"></i>
                            <?= $user->getUserId() ?>
                            <?php if (IsUser($user->getUserId())) { ?></a><?php } ?>
                        </td>
                        <td>
                            <?= $user->getHits() ?>
                        </td>
                        <td>
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $user_percent ?>%;" aria-valuenow="<?= $user_percent ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?= $user_percent ?>%
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="activity.php?user_id=<?= $user->getUserId() ?>">
                                <i class="fa fa-line-chart" aria-hidden="true" title="<?= $user->getUserId() . "'s "._USERSACTIVITY ?>"></i>
                            </a>
                        </td>
                        <td style="text-align: center"><?php echo date(_DATEFORMAT, $user->getTimeCreated()) ?></td>
                        <td style="text-align: center"><?php echo date(_DATETIMEFORMAT, $user->getLastVisit()) ?></td>
                        <td>
                            <?php if ($user->getUserLevel() <= 1 || $user->getUserLevel() == 2) { ?>
                                <a href="./user/edit.php?userid=<?= $user->getUid() ?>" title="<?= _EDIT . " " . $user->getUserId() ?>">
                                    <i class="fa fa-pencil" aria-hidden="true"></i>
                                </a>
                            <?php } ?>
                        </td>
                        <td>
                            <?php if ($user->getUserLevel() <= 1) { ?>
                                <a href="./user/index.php?action=deleteuser&userid=<?= $user->getUid() ?>" onclick="return ConfirmDeleteUser('<?= $user->getUserId() ?>')">
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

    function ConfirmDeleteUser( user ) {
        return confirm( "<?= _WARNING . ": " . _ABOUTTODELETE ?>: " + user );
    }

</script>