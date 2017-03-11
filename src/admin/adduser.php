<?php
include_once '../Class/autoload.php';
include_once '../config.php';

$settings = new Gratbrav\Torrentbug\Settings();

include_once '../functions.php';

if (! IsAdmin()) {
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

$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
if (empty($action)) {
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
}

if ($action == 'add') {
    
    $newUser = getRequestVar('newUser');
    $pass1 = getRequestVar('pass1');
    $userType = getRequestVar('userType');
    
    $newUser = strtolower($newUser);
    if (IsUser($newUser)) {
        echo "<br><div style=\"text-align:center\">" . _TRYDIFFERENTUSERID . "<br><strong>" . $newUser . "</strong> " . _HASBEENUSED . "</div><br><br><br>";
    } else {
        $create_time = time();
        
        $userData = [
            'user_id' => strtolower($newUser),
            'password' => md5($pass1),
            'hits' => 0,
            'last_visit' => (new \DateTime())->format('Y-m-d'),
            'time_created' => $create_time,
            'user_level' => $userType,
            'hide_offline' => "0",
            'theme' => $settings->get('default_theme'),
            'language_file' => $settings->get('default_language')
        ];
        $user = new Gratbrav\Torrentbug\User\User($userData);
        
        $userService = new Gratbrav\Torrentbug\User\Service();
        $userService->save($user);
        
        $options = [
            'user_id' => $cfg['user'],
            'file' => _NEWUSER . ': ' . $newUser,
            'action' => $cfg["constants"]["admin"]
        ];
        $log = new \Gratbrav\Torrentbug\Log\Service();
        $log->save($options);
    }
    
    header("location: adduser.php");
    exit();
} else 
    if ($action == 'delete') {
        $uid = filter_input(INPUT_GET, 'uid', FILTER_VALIDATE_INT);
        
        $userService = new Gratbrav\Torrentbug\User\Service();
        $user = $userService->getUserById($uid);
        
        if ($user->getUid() && ! IsSuperAdmin($user->getUserId())) {
            
            // delete any cookies this user may have had
            $sql = "DELETE FROM tf_cookies WHERE uid=" . $user->getUid();
            $result = $db->Execute($sql);
            showError($db, $sql);
            
            // Now cleanup any message this person may have had
            $sql = "DELETE FROM tf_messages WHERE to_user=" . $db->qstr($user->getUserId());
            $result = $db->Execute($sql);
            showError($db, $sql);
            
            // now delete the user from the table
            $userService->delete($uid);
            
            $options = [
                'user_id' => $cfg['user'],
                'file' => _DELETE . ' ' . _USER . ': ' . $user_id,
                'action' => $cfg["constants"]["admin"]
            ];
            $log = new \Gratbrav\Torrentbug\Log\Service();
            $log->save($options);
        }
        header("location: adduser.php");
        exit();
    }

$subMenu = 'admin';
include_once '../header.php';

?>

<div class="container">
    <div class="row">
        <div class="col-sm-12 bd-example">
            <form name="theForm" action="adduser.php" method="post"
                onsubmit="return validateProfile()">
                <input type="hidden" name="action" value="add" />
                <table class="table table-striped">
                    <tr>
                        <th colspan="2"><img src="../images/user.gif"
                            alt="">&nbsp;&nbsp;&nbsp;
    						<?php echo _NEWUSER?>
    					</th>
                    </tr>
                    <tr>
                        <td style="text-align: right"><?php echo _USER ?>:</td>
                        <td><input name="newUser" type="Text" value=""
                            size="15"></td>
                    </tr>
                    <tr>
                        <td style="text-align: right"><?php echo _PASSWORD ?>:</td>
                        <td><input name="pass1" type="Password" value=""
                            size="15"></td>
                    </tr>
                    <tr>
                        <td style="text-align: right"><?php echo _CONFIRMPASSWORD ?>:</td>
                        <td><input name="pass2" type="Password" value=""
                            size="15"></td>
                    </tr>
                    <tr>
                        <td style="text-align: right"><?php echo _USERTYPE ?>:</td>
                        <td><select name="userType">
                                <option value="0"><?php echo _NORMALUSER ?></option>
                                <option value="1"><?php echo _ADMINISTRATOR ?></option>
                        </select></td>
                    </tr>
                    <tr>
                        <td style="text-align: center" colspan="2"><input
                            type="Submit" value="<?php echo _CREATE ?>"
                            class="btn btn-primary"></td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
</div>

<script>
    function validateProfile()
    {
        var msg = ""
        if (theForm.newUser.value == "")
        {
            msg = msg + "* <?php echo _USERIDREQUIRED ?>\n";
            theForm.newUser.focus();
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
        else
        {
            msg = msg + "* <?php echo _PASSWORDLENGTH ?>\n";
            theForm.pass1.focus();
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

<?php include 'user_section.php'; ?>
