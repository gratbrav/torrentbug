<?php

/*************************************************************
*  TorrentFlux - PHP Torrent Manager
*  www.torrentflux.com
**************************************************************/
/*
    This file is part of TorrentFlux.

    TorrentFlux is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    TorrentFlux is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with TorrentFlux; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// ADODB support.
include_once('db.php');
include_once("settingsfunctions.php");

// Create Connection.
$db = getdb();

loadSettings();

if (!defined("IMG_JPG")) define("IMG_JPG", 2);
    

if (in_array("gfx", $_REQUEST) && $_REQUEST["gfx"] == "gfx" && file_exists("images/code_bg.jpg"))
{
    // Check gd is loaded AND that jpeg image type is supported:
    if (extension_loaded('gd') && (imagetypes() & IMG_JPG))
    {
        $code = getCode($_REQUEST["rnd"]);

        $image = ImageCreateFromJPEG("images/code_bg.jpg");
        $text_color = ImageColorAllocate($image, 80, 80, 80);
        
        Header("Content-type: image/jpeg");
        ImageString ($image, 5, 12, 2, $code, $text_color);
        ImageJPEG($image, '', 75);
        ImageDestroy($image);
        die();
    }
    else
    {
        header("Content-type: application/octet-stream\n");
        header("Content-transfer-encoding: binary\n");

        $fp = popen("cat images/red.gif", "r");
        fpassthru($fp);
        pclose($fp);

        die();
    }
}

session_name("TorrentFlux");
session_start();
include_once("config.php");
include("themes/".$cfg["default_theme"]."/index.php");
global $cfg;
if(isset($_SESSION['user']))
{
    header("location: index.php");
    exit;
}
ob_start();
?>

<?php
	$loginFailed = 0;
 
$user = strtolower(getRequestVar('username'));

$iamhim = addslashes(getRequestVar('iamhim'));

$create_time = time();

// Check for user
if(!empty($user) && !empty($iamhim))
{
    $sec_code = getRequestVar('security');
    $rnd_number = getRequestVar('rnd_chk');
    $log_msg = "";
    $allow_login = true;
        
    if (extension_loaded('gd') && (imagetypes() & IMG_JPG) && array_key_exists("security_code",$cfg) && $cfg["security_code"])
    {
        if ($sec_code == getCode($rnd_number))
        {
            $allow_login = true;
        }
        else
        {
            $allow_login = false;
            $log_msg = "Invalid Security Code: ". $sec_code . " for " . $user;
        }
    }
    
    /* First User check */
    $next_loc = "index.php";
    $sql = "SELECT count(*) FROM tf_users";
    $user_count = $db->GetOne($sql);
    if($user_count == 0 && $allow_login)
    {
        // This user is first in DB.  Make them super admin.
        // this is The Super USER, add them to the user table

        $record = array(
                        'user_id'=>$user,
                        'password'=>md5($iamhim),
                        'hits'=>1,
                        'last_visit'=>$create_time,
                        'time_created'=>$create_time,
                        'user_level'=>2,
                        'hide_offline'=>0,
                        'theme'=>$cfg["default_theme"],
                        'language_file'=>$cfg["default_language"]
                        );
        $sTable = 'tf_users';
        $sql = $db->GetInsertSql($sTable, $record);

        $result = $db->Execute($sql);
        showError($db,$sql);

        // Test and setup some paths for the TF settings
        $pythonCmd = $cfg["pythonCmd"];
        $btphpbin = getcwd() . "/TF_BitTornado/btphptornado.py";
        $tfQManager = getcwd() . "/TF_BitTornado/tfQManager.py";
        $maketorrent = getcwd() . "/TF_BitTornado/btmakemetafile.py";
        $btshowmetainfo = getcwd() . "/TF_BitTornado/btshowmetainfo.py";
        $tfPath = getcwd() . "/downloads/";

        if (!isFile($cfg["pythonCmd"]))
        {
            $pythonCmd = trim(shell_exec("which python"));
            if ($pythonCmd == "")
            {
                $pythonCmd = $cfg["pythonCmd"];
            }
        }

        $settings = array(
                            "pythonCmd" => $pythonCmd,
                            "btphpbin" => $btphpbin,
                            "tfQManager" => $tfQManager,
                            "btmakemetafile" => $maketorrent,
                            "btshowmetainfo" => $btshowmetainfo,
                            "path" => $tfPath
                        );

        saveSettings($settings);
        AuditAction($cfg["constants"]["update"], "Initial Settings Updated for first login.");
        $next_loc = "admin.php?op=configSettings";
    }
        
    if ($allow_login)
    {
        $sql = "SELECT uid, hits, hide_offline, theme, language_file FROM tf_users WHERE user_id=".$db->qstr($user)." AND password=".$db->qstr(md5($iamhim));
        $result = $db->Execute($sql);
        showError($db,$sql);
    
        list(
        $uid,
        $hits,
        $cfg["hide_offline"],
        $cfg["theme"],
        $cfg["language_file"]) = $result->FetchRow();
    
        if(!array_key_exists("shutdown",$cfg))
            $cfg['shutdown'] = '';
        if(!array_key_exists("upload_rate",$cfg))
            $cfg['upload_rate'] = '';
    
        if($result->RecordCount()==1)
        {
            // Add a hit to the user
            $hits++;
    
            $sql = 'select * from tf_users where uid = '.$uid;
            $rs = $db->Execute($sql);
            showError($db, $sql);
    
            $rec = array(
                            'hits'=>$hits,
                            'last_visit'=>$db->DBDate($create_time),
                            'theme'=>$cfg['theme'],
                            'language_file'=>$cfg['language_file'],
                            'shutdown'=>$cfg['shutdown'],
                            'upload_rate'=>$cfg['upload_rate']
                        );
            $sql = $db->GetUpdateSQL($rs, $rec);
    
            $result = $db->Execute($sql);
            showError($db, $sql);
    
            $_SESSION['user'] = $user;
            session_write_close();
    
            header("location: ".$next_loc);
            exit();
        }
        else
        {
            $allow_login = false;
            $log_msg = "FAILED AUTH: ".$user;
        }
    }
    
    if (!$allow_login)
    {
        AuditAction($cfg["constants"]["access_denied"], $log_msg);
        $loginFailed = 1;
    }
}
?>

<!doctype html>
<html>
<head>
	<title><?php echo $cfg["pagetitle"] ?></title>
	<link rel="stylesheet" href="./plugins/twitter/bootstrap/dist/css/bootstrap.min.css" type="text/css" />
	<style>
	
	.form-signin {
	    background-color: #fff;
	    border: 1px solid #e5e5e5;
	    border-radius: 5px;
	    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
	    margin: 0 auto 20px;
	    max-width: 300px;
	    padding: 19px 29px 29px;
	}
	.form-signin input[type="text"], .form-signin input[type="password"] {
	    font-size: 16px;
	    height: auto;
	    margin-bottom: 15px;
	    padding: 7px 9px;
	}
	body {
	    background-color: #f5f5f5;
	    padding-bottom: 40px;
	    padding-top: 40px;
	}
	</style>
    <meta http-equiv="pragma" content="no-cache" />
    <meta content="charset=iso-8859-1" />
    <meta name=viewport content="width=device-width, initial-scale=1">
</head>
<body>

<div class="container">

	<form class="form-signin" method="post" action="login.php">
		<?php if ($loginFailed) { ?><div class="alert alert-danger" role="alert">Login failed. Please try again.</div><?php } ?>
		<h2 class="form-signin-heading">Please sign in</h2>
        <input type="text" placeholder="Username" class="form-control" name="username" autofocus>
        <input type="password" placeholder="Password" class="form-control" name="iamhim">
        <button type="submit" class="btn btn-large btn-primary" id="submit">Sign in</button>
	</form>

</div>

<script src="./plugins/components/jquery/jquery.min.js"></script>
<script language="JavaScript">
$(document).ready(function() {

	$("form #submit").click(function() {
	    var msg = "";
	    var pass = $("form input[name='iamhim']").val();
	    var user = $("form input[name='username']").val();

	    if (user.length < 1) {
	        msg = msg + "* Username is required\n";
	        $("form input[name='username']").focus();
	    }
	    
	    if (pass.length<1) {
	        msg = msg + "* Password is required\n";
	        if (user.length > 0) {
	        	$("form input[name='iamhim']").focus();
	        }
	    }

	    if (msg != "") {
	        alert("Check the following:\n\n" + msg);
	        return false;
	    }
	});
	
});
</script>
</body>
</html>


<?php
ob_end_flush();
?>

