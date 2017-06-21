<?php

    use Gratbrav\Torrentbug\User\User;

    include_once '../../Class/autoload.php';
    include_once '../../config.php';

    $settings = new Gratbrav\Torrentbug\Settings();

include_once '../../functions.php';

if (!IsAdmin()) {
    // the user probably hit this page direct
    $options = [
        'user_id' => $cfg['user'],
        'file' => $_SERVER['PHP_SELF'],
        'action' => $cfg["constants"]["access_denied"]
    ];
    $log = new \Gratbrav\Torrentbug\Log\Service();
    $log->save($options);

    header("location: ../../index.php");
}

    $userService = new Gratbrav\Torrentbug\User\Service();

    $userId = filter_input(INPUT_GET, 'userid', FILTER_VALIDATE_INT);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $userId = filter_input(INPUT_POST, 'userid', FILTER_VALIDATE_INT);
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
        $login = filter_input(INPUT_POST, 'login', FILTER_SANITIZE_STRING);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
        $passwordConfirm = filter_input(INPUT_POST, 'passwordconfirm', FILTER_SANITIZE_STRING);
        $userType= filter_input(INPUT_POST, 'usertype', FILTER_VALIDATE_INT);

        // check login
        if ($userId != 0 || ($userId == 0 && !$userService->isLoginInUse($login))) {
            // check password
            if (($password == '' && $passwordConfirm == '') || ($password != '' && $password == $passwordConfirm)) {
                // save login
                $userData= [
                    'uid' => $userId,
                    'user_id' => $login,
                    'password' => $password,
                    'user_level' => $userType,
                    'theme' => 'matrix',
                    'language_file' => 'lang-english.php',
                ];
                $user = new User($userData);

                $userService->save($user);
                header('location: ./');
                exit;
            }
        }
    }

    $user = $userService->getUserById($userId);

    $subMenu = 'admin';
    include_once '../../header.php';
?>

<form method="POST" id="user-form">
    <input name="userid" type="hidden" value="<?= $user->getUid() ?>" />

<div class="container">
    <div class="row">

        <div class="card" style="margin-top: 15px;">
            <div class="card-header">
                <i class="fa fa-user" aria-hidden="true"></i>
                <?= _NEWUSER?>
            </div>
            <div class="card-block">

                <div class="row">
                    <div class="col-sm-2 form-group">
                        <label class="control-label"><?= _USER ?></label>
                    </div>
                    <div class="col-sm-10 form-group">
                        <input name="login" type="text" value="<?= $user->getUserId() ?>" class="form-control" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-2 form-group">
                        <label class="control-label"><?= _PASSWORD ?></label>
                    </div>
                    <div class="col-sm-10 form-group">
                        <input name="password" type="Password" value="" class="form-control" <?= (is_null($userId) || ($userId == 0) ? 'required' : '') ?>>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-2 form-group">
                        <label class="control-label"><?= _CONFIRMPASSWORD ?></label>
                    </div>
                    <div class="col-sm-10 form-group">
                        <input name="passwordconfirm" type="Password" value="" class="form-control" <?= (is_null($userId) || ($userId == 0) ? 'required' : '') ?>>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-2 form-group">
                        <label class="control-label"><?= _USERTYPE ?></label>
                    </div>
                    <div class="col-sm-10 form-group">
                        <select name="usertype" class="form-control" <?= ($user->getUid() == 1) ? 'disabled' : '' ?>>
                            <option value="0" <?= ($user->getUserLevel() == 0) ? 'selected' : '' ?>><?= _NORMALUSER ?></option>
                            <option value="1" <?= ($user->getUserLevel() != 0) ? 'selected' : '' ?>><?= _ADMINISTRATOR ?></option>
                        </select>
                    </div>
                </div>
    
            </div>
        </div>

        <button type="submit" class="btn btn-success text-capitalize pull-right" style="margin-left:10px;"><?= _CREATE ?></button>
        <a href="index.php" class="btn btn-secondary text-capitalize pull-right"><?= _CANCEL ?></a>

    </div>
</div>

</form>

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
 