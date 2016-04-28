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

    include_once './Class/autoload.php';

	// ADODB support.
	include_once 'db.php';
	include_once 'settingsfunctions.php';

	$settings = new Class_Settings();
// Create Connection.
$db = getdb();

	

	session_name("TorrentFlux");
	session_start();
	include_once("config.php");
	include_once 'themes/' . $settings->get('default_theme') . '/index.php';

	if (isset($_SESSION['user'])) {
	    header("location: index.php");
	    exit;
	}
	
	ob_start();


	$loginFailed = 0;
 	$user = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
 	$iamhim = filter_input(INPUT_POST, 'iamhim', FILTER_SANITIZE_STRING);

	$create_time = time();

	// Check for user
	if(!empty($user) && !empty($iamhim)) {
		
		$log_msg = "";
		$allow_login = true;

    
    /* First User check */
    $next_loc = "index.php";
    $sql = "SELECT count(*) FROM tf_users";
    $user_count = $db->GetOne($sql);
    if($user_count == 0 && $allow_login)
    {
        // This user is first in DB.  Make them super admin.
        // this is The Super USER, add them to the user table

        $record = array(
        	'user_id'		=> $user,
            'password'		=> md5($iamhim),
            'hits'			=> 1,
            'last_visit'	=> $create_time,
            'time_created'	=> $create_time,
            'user_level'	=> 2,
            'hide_offline'	=> 0,
            'theme'			=> $settings->get('default_theme'),
            'language_file'	=> $settings->get('default_language')
        );
        $sTable = 'tf_users';
        $sql = $db->GetInsertSql($sTable, $record);

        $result = $db->Execute($sql);
        showError($db,$sql);

        // Test and setup some paths for the TF settings
        $pythonCmd = $settings->get('pythonCmd');
        $btphpbin = getcwd() . "/TF_BitTornado/btphptornado.py";
        $tfQManager = getcwd() . "/TF_BitTornado/tfQManager.py";
        $maketorrent = getcwd() . "/TF_BitTornado/btmakemetafile.py";
        $btshowmetainfo = getcwd() . "/TF_BitTornado/btshowmetainfo.py";
        $tfPath = getcwd() . "/downloads/";
        $documentRoot = '//' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);

        if (!isFile($settings->get('pythonCmd'))) {
            $pythonCmd = trim(shell_exec("which python"));
            if ($pythonCmd == "") {
                $pythonCmd = $settings->get('pythonCmd');
            }
        }

        $options = array(
        	"pythonCmd"			=> $pythonCmd,
            "btphpbin"			=> $btphpbin,
            "tfQManager"		=> $tfQManager,
            "btmakemetafile"	=> $maketorrent,
            "btshowmetainfo"	=> $btshowmetainfo,
            "path"				=> $tfPath,
            'document_root'     => $documentRoot,
        );

        $settings->save($options);
        AuditAction($cfg["constants"]["update"], "Initial Settings Updated for first login.");
        $next_loc = "admin.php?op=configSettings";
    }
        
    if ($allow_login)
    {
    	$auth = new Class_User_Authentication($user, $iamhim);
    	$result = $auth->checkLogin();
    	showError($db,$sql);
    	
        list($uid, $hits, $cfg["hide_offline"], $cfg["theme"], $cfg["language_file"]) = $result->FetchRow();
    
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
    
    if (!$allow_login) {
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
    <link rel="stylesheet" href="<?php echo $settings->get('base_url') ?>/plugins/components/font-awesome/css/font-awesome.min.css" type="text/css" />
    <meta name=viewport content="width=device-width, initial-scale=1">
    <style>
    #loginbox { margin-top: 70px; }
    #form > div { margin-bottom: 25px; }
    </style>
</head>
<body>

<div class="container">    
        
    <div id="loginbox" class="col-md-6 col-md-offset-3 col-sm-6 col-sm-offset-3"> 
		
		<?php if ($loginFailed) { ?><div class="alert alert-danger" role="alert">Login failed. Please try again.</div><?php } ?>
        
        <div class="card">
            <div class="card-header text-sm-center">
                Please sign in
            </div>     

            <div class="card-block">

                <form name="form" id="form" class="form-horizontal" enctype="multipart/form-data" method="POST">
                   
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-user" aria-hidden="true"></i></span>
                        <input id="user" type="text" class="form-control" name="username" value="" placeholder="User" autofocus>                                        
                    </div>

                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-lock" aria-hidden="true"></i></span>
                        <input id="password" type="password" class="form-control" name="iamhim" placeholder="Password">
                    </div>
                                                                                      
                    <button type="submit" class="btn btn-primary btn-block" id="submit">
                    	<i class="fa fa-sign-in" aria-hidden="true"></i> 
                    	Log in
                    </button>                          

                </form>     

            </div>                     
        </div>  
    </div>
</div>

<script src="./plugins/components/jquery/jquery.min.js"></script>
<script src="./js/login.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>