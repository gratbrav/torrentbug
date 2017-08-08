<?php

/*************************************************************
 *  TorrentFlux - PHP Torrent Manager
 *  www.torrentflux.com
 **************************************************************/
/*
 * This file is part of TorrentFlux.
 *
 * TorrentFlux is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * TorrentFlux is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with TorrentFlux; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */
include_once './Class/autoload.php';

// ADODB support.
include_once 'settingsfunctions.php';

$settings = new Gratbrav\Torrentbug\Settings();
$userService = new \Gratbrav\Torrentbug\User\Service();

session_name("Torrentbug");
session_start();

if (isset($_SESSION['user'])) {
    header('location: index.php');
    exit();
}

include_once ("config.php");
include_once 'themes/' . $settings->get('default_theme') . '/index.php';

$userName = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$iamhim = filter_input(INPUT_POST, 'iamhim', FILTER_SANITIZE_STRING);

$create_time = time();

// Check for user
if (! empty($userName) && ! empty($iamhim)) {
    
    $log_msg = "";
    $allow_login = true;
    
    /* First User check */
    $next_loc = "index.php";
    
    if (count($userService->getUsers()) == 0 && $allow_login) {
        // This user is first in DB. Make them super admin.
        // this is The Super USER, add them to the user table
        
        $userData = [
            'user_id' => $userName,
            'password' => md5($iamhim),
            'hits' => 0,
            'last_visit' => (new \DateTime())->format('Y-m-d'),
            'time_created' => time(),
            'user_level' => 2,
            'hide_offline' => 0,
            'theme' => $settings->get('default_theme'),
            'language_file' => $settings->get('default_language')
        ];
        $user = new Gratbrav\Torrentbug\User\User($userData);
        
        $userService->save($user);
        
        // Test and setup some paths for the TF settings
        $pythonCmd = $settings->get('pythonCmd');
        $btphpbin = getcwd() . "/TF_BitTornado/btphptornado.py";
        $tfQManager = getcwd() . "/TF_BitTornado/tfQManager.py";
        $maketorrent = getcwd() . "/TF_BitTornado/btmakemetafile.py";
        $btshowmetainfo = getcwd() . "/TF_BitTornado/btshowmetainfo.py";
        $tfPath = getcwd() . "/downloads/";
        $documentRoot = '//' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        
        if (! isFile($settings->get('pythonCmd'))) {
            $pythonCmd = trim(shell_exec("which python"));
            if ($pythonCmd == "") {
                $pythonCmd = $settings->get('pythonCmd');
            }
        }
        
        $options = array(
            "pythonCmd" => $pythonCmd,
            "btphpbin" => $btphpbin,
            "tfQManager" => $tfQManager,
            "btmakemetafile" => $maketorrent,
            "btshowmetainfo" => $btshowmetainfo,
            "path" => $tfPath,
            'document_root' => $documentRoot
        );
        
        $settings->save($options);
        
        $options = [
            'user_id' => $cfg['user'],
            'file' => 'Initial Settings Updated for first login.',
            'action' => $cfg["constants"]["update"]
        ];
        $log = new \Gratbrav\Torrentbug\Log\Service();
        $log->save($options);
        
        $next_loc = "./admin/admin.php?op=configSettings";
    }
    
    if ($allow_login) {
        $auth = new Gratbrav\Torrentbug\User\Authentication($userName, $iamhim);
        $userId = $auth->checkLogin();
        // showError($db, $sql);

        if ($userId !== 0) {

            $userService = new Gratbrav\Torrentbug\User\Service();
            $user = $userService->getUserById($userId);

            $cfg["hide_offline"] = $user->getHideOffline();
            $cfg["theme"] = $user->getTheme();
            $cfg["language_file"] = $user->getLanguageFile();

            if (! array_key_exists("shutdown", $cfg))
                $cfg['shutdown'] = '';
            if (! array_key_exists("upload_rate", $cfg))
                $cfg['upload_rate'] = '';

            $_SESSION['user'] = $userName;
            $_SESSION['uid'] = $user->getUid();
            $_SESSION['is_admin'] = ($user->getUserLevel() == 2) ? true: false;
            
            header("location: " . $next_loc);
            exit();
        } else {
            $allow_login = false;
            $log_msg = "FAILED AUTH: " . $userName;
        }
    }
    
    if (! $allow_login) {
        $options = [
            'user_id' => $cfg['user'],
            'file' => $log_msg,
            'action' => $cfg["constants"]["access_denied"]
        ];
        $log = new \Gratbrav\Torrentbug\Log\Service();
        $log->save($options);
        
        $loginFailed = 1;
    }
}
?>
<!doctype html>
<html>
<head>
<title><?php echo $cfg["pagetitle"] ?></title>
<meta name=viewport content="width=device-width, initial-scale=1">
<style>
#loginbox {
    margin-top: 70px;
    text-align: center;
}

#form>div {
    margin-bottom: 25px;
}
</style>
</head>
<body>

    <div class="container">
        <div id="loginbox"
            class="col-md-6 offset-md-3 col-sm-6 offset-sm-3">

        <?php if (isset($loginFailed)) { ?>
            <div class="alert alert-danger">Login failed. Please try
                again.</div>
        <?php } ?>

        <div class="card">
                <div class="card-header">Please sign in</div>

                <div class="card-block">

                    <form id="form" method="POST">

                        <div class="input-group">
                            <span class="input-group-addon"><i
                                class="fa fa-user" aria-hidden="true"></i></span>
                            <input id="user" type="text"
                                class="form-control" name="username"
                                value="" placeholder="User" autofocus />
                        </div>

                        <div class="input-group">
                            <span class="input-group-addon"><i
                                class="fa fa-lock" aria-hidden="true"></i></span>
                            <input id="password" type="password"
                                class="form-control" name="iamhim"
                                placeholder="Password" />
                        </div>

                        <button type="submit"
                            class="btn btn-primary btn-block"
                            id="submit">
                            <i class="fa fa-sign-in" aria-hidden="true"></i>
                            Log in
                        </button>

                    </form>

                </div>
            </div>
        </div>
    </div>

    <script src="./plugins/components/jquery/jquery.min.js"></script>
    <script async src="./js/login.js"></script>
</body>
</html>
<link rel="stylesheet" href="./plugins/twitter/bootstrap/dist/css/bootstrap.min.css" type="text/css" />
<link rel="stylesheet" href="./plugins/components/font-awesome/css/font-awesome.min.css" type="text/css" />