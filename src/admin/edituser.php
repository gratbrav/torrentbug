<?php
include_once '../Class/autoload.php';
include_once '../config.php';

$settings = new Gratbrav\Torrentbug\Settings();

include_once '../functions.php';

if (!$_SESSION['is_admin']) {
    // the user probably hit this page direct
    $options = [
        'user_id' => $cfg['user'],
        'file' => $_SERVER['PHP_SELF'],
        'action' => $cfg["constants"]["access_denied"]
    ];
    $log = new \Gratbrav\Torrentbug\Log\Service();
    $log->save($options);
    
    header("location: ../index.php");
}

$user_id = getRequestVar('user_id');
$uid = getRequestVar('uid');

$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
if (empty($action)) {
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
}

if ($action == 'update') {
    $org_user_id = getRequestVar('org_user_id');
    $pass1 = getRequestVar('pass1');
    $userType = getRequestVar('userType');
    $hideOffline = getRequestVar('hideOffline');
    // updateUser($user_id, $org_user_id, $pass1, $userType, $hideOffline);
    
    $user_id = strtolower($user_id);
    if (IsUser($user_id) && ($user_id != $org_user_id)) {
        echo "<br><div style=\"text-align:center\">" . _TRYDIFFERENTUSERID . "<br><strong>" . $user_id . "</strong> " . _HASBEENUSED . "<br><br><br>";
        
        echo "[<a href=\"edituser.php?uid=" . $uid . "\">" . _RETURNTOEDIT . " " . $org_user_id . "</a>]</div><br><br><br>";
    } else {
        // Admin is changing id or password through edit screen
        if (($user_id == $cfg["user"] || $cfg["user"] == $org_user_id) && $pass1 != "") {
            // this will expire the user
            $_SESSION['user'] = md5($cfg["pagetitle"]);
        }
        updateThisUser($user_id, $org_user_id, $pass1, $userType, $hideOffline);
        
        $options = [
            'user_id' => $cfg['user'],
            'file' => _EDITUSER . ': ' . $user_id,
            'action' => $cfg["constants"]["admin"]
        ];
        $log = new \Gratbrav\Torrentbug\Log\Service();
        $log->save($options);
        
        header("location: admin.php");
    }
}

$editUserImage = "../images/user.gif";
$selected_n = "selected";
$selected_a = "";

$hide_checked = "";

$total_activity = GetActivityCount();

$userService = new Gratbrav\Torrentbug\User\Service();
$user = $userService->getUserById($uid);

$user_type = _NORMALUSER;
if ($user->getUserLevel() == 1) {
    $user_type = _ADMINISTRATOR;
    $selected_n = "";
    $selected_a = "selected";
    $editUserImage = "../images/admin_user.gif";
}

if ($user->getUserLevel() >= 2) {
    $user_type = _SUPERADMIN;
    $editUserImage = "../images/superadmin.gif";
}

if ($user->getHideOffline() == 1) {
    $hide_checked = "checked";
}

$user_activity = GetActivityCount($user->getUserId());

if ($user_activity == 0) {
    $user_percent = 0;
} else {
    $user_percent = number_format(($user_activity / $total_activity) * 100);
}

$subMenu = 'admin';
include_once '../header.php';
?>
<div class="container">
    <div class="row">
        <div class="col-sm-12 bd-example">
            <table class="table table-striped">
                <tr>
                    <thd colspan=6> <img
                        src="<?php echo $editUserImage ?>" alt="" />&nbsp;&nbsp;&nbsp;
    					<?php echo _EDITUSER . ": " . $user->getUserId()?>
    				</th>
                
                </tr>
                <tr>
                    <td style="text-align: center">

                        <table style="width: 100%" border="0"
                            cellpadding="3" cellspacing="0">
                            <tr>
                                <td style="width: 50%"
                                    bgcolor="<?php echo $cfg["table_data_bg"]?>">

                                    <div style="text-align: center">
                                        <table border="0"
                                            cellpadding="0"
                                            cellspacing="0">
                                            <tr>
                                                <td
                                                    style="text-align: right"><?php echo $user->getUserId()." "._JOINED ?>:&nbsp;</td>
                                                <td><strong><?= date(_DATETIMEFORMAT, $user->getTimeCreated()) ?></strong></td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="text-align: right"><?php echo _LASTVISIT ?>:&nbsp;</td>
                                                <td><strong><?= date(_DATETIMEFORMAT, $user->getLastVisit()) ?></strong></td>
                                            </tr>
                                            <tr>
                                                <td colspan="2"
                                                    style="text-align: center">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="text-align: right"><?php echo _UPLOADPARTICIPATION ?>:&nbsp;</td>
                                                <td>
                                                    <table
                                                        style="width: 200"
                                                        border="0"
                                                        cellpadding="0"
                                                        cellspacing="0">
                                                        <tr>
                                                            <td
                                                                background="../themes/<?php echo $cfg["theme"] ?>/images/proglass.gif"
                                                                style="width:"<?php echo $user_percent*2 ?>"><img
                                                                src="../images/blank.gif"
                                                                alt=""></td>
                                                            <td
                                                                background="../themes/<?php echo $cfg["theme"] ?>/images/noglass.gif"
                                                                style="width:"<?php echo (200 - ($user_percent*2)) ?>"><img
                                                                src="../images/blank.gif"
                                                                alt=""></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="text-align: right"><?php echo _UPLOADS ?>:&nbsp;</td>
                                                <td><strong><?php echo $user_activity ?></strong></td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="text-align: right"><?php echo _PERCENTPARTICIPATION ?>:&nbsp;</td>
                                                <td><strong><?php echo $user_percent ?>%</strong></td>
                                            </tr>
                                            <tr>
                                                <td colspan="2"
                                                    style="text-align: center"><div
                                                        style="text-align: center"
                                                        class="tiny">(<?php echo _PARTICIPATIONSTATEMENT. " ".$settings->get('days_to_keep')." "._DAYS ?>)</div>
                                                    <br></td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="text-align: right"><?php echo _TOTALPAGEVIEWS ?>:&nbsp;</td>
                                                <td><strong><?= $user->getHits() ?></strong></td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="text-align: right"><?php echo _THEME ?>:&nbsp;</td>
                                                <td><strong><?= $user->getTheme() ?></strong><br></td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="text-align: right"><?php echo _LANGUAGE ?>:&nbsp;</td>
                                                <td><strong><?= GetLanguageFromFile($user->getLanguageFile()) ?></strong><br>
                                                <br></td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="text-align: right"><?php echo _USERTYPE ?>:&nbsp;</td>
                                                <td><strong><?php echo $user_type ?></strong><br></td>
                                            </tr>
                                            <tr>
                                                <td colspan="2"
                                                    style="text-align: center"><div
                                                        style="text-align: center">
                                                        [<a
                                                            href="activity.php?user_id=<?php echo $user->getUserId() ?>"><?php echo _USERSACTIVITY ?></a>]
                                                    </div></td>
                                            </tr>
                                        </table>
                                    </div>

                                </td>
                                <td
                                    bgcolor="<?php echo $cfg["body_data_bg"] ?>">
                                    <div style="text-align: center">
                                        <form name="theForm"
                                            action="edituser.php"
                                            method="post"
                                            onsubmit="return validateUser()">
                                            <input type="hidden"
                                                name="action"
                                                value="update" /> <input
                                                type="hidden" name="uid"
                                                value="<?=$uid?>" />

                                            <table cellpadding="5"
                                                cellspacing="0"
                                                border="0">

                                                <tr>
                                                    <td
                                                        style="text-align: right"><?php echo _USER ?>:</td>
                                                    <td><input
                                                        name="user_id"
                                                        type="Text"
                                                        value="<?php echo $user->getUserId() ?>"
                                                        size="15"> <input
                                                        name="org_user_id"
                                                        type="Hidden"
                                                        value="<?php echo $user->getUserId() ?>">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td
                                                        style="text-align: right"><?php echo _NEWPASSWORD ?>:</td>
                                                    <td><input
                                                        name="pass1"
                                                        type="Password"
                                                        value=""
                                                        size="15"></td>
                                                </tr>
                                                <tr>
                                                    <td
                                                        style="text-align: right"><?php echo _CONFIRMPASSWORD ?>:</td>
                                                    <td><input
                                                        name="pass2"
                                                        type="Password"
                                                        value=""
                                                        size="15"></td>
                                                </tr>
                                                <tr>
                                                    <td
                                                        style="text-align: right"><?php echo _USERTYPE ?>:</td>
                                                    <td>
            <?php if ($user->getUserLevel() <= 1) { ?>
                <select name="userType">
                                                            <option
                                                                value="0"
                                                                <?php echo $selected_n ?>><?php echo _NORMALUSER ?></option>
                                                            <option
                                                                value="1"
                                                                <?php echo $selected_a ?>><?php echo _ADMINISTRATOR ?></option>
                                                    </select>
            <?php } else { ?>
                <strong><?php echo _SUPERADMIN ?></strong> <input
                                                        type="Hidden"
                                                        name="userType"
                                                        value="2">
            <?php } ?>
            </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2"><input
                                                        name="hideOffline"
                                                        type="Checkbox"
                                                        value="1"
                                                        <?php echo $hide_checked ?>> <?php echo _HIDEOFFLINEUSERS ?><br>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td
                                                        style="text-align: center"
                                                        colspan="2"><input
                                                        type="Submit"
                                                        value="<?php echo _UPDATE ?>">
                                                    </td>
                                                </tr>
                                            </table>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<script>
    function validateUser()
    {
        var msg = ""
        if (theForm.user_id.value == "")
        {
            msg = msg + "* <?php echo _USERIDREQUIRED ?>\n";
            theForm.user_id.focus();
        }

        if (theForm.pass1.value != "" || theForm.pass2.value != "")
        {
            if (theForm.pass1.value.length <= 5 || theForm.pass2.value.length <= 5)
            {
                msg = msg + "* <?php echo _PASSWORDLENGTH ?>\n";
                theForm.pass1.focus();
            }
            if (theForm.pass1.value != theForm.pass2.value)
            {
                msg = msg + "* <?php echo _PASSWORDNOTMATCH ?>\n";
                theForm.pass1.value = "";
                theForm.pass2.value = "";
                theForm.pass1.focus();
            }
        }

        if (msg != "")
        {
            alert("<?php echo _PLEASECHECKFOLLOWING ?>:\n\n" + msg);
            return false;
        }
        else
        {
            return true;
        }
    }
</script>

<?php

    // Show User Section
    // displayUserSection();
    include './user_section.php';