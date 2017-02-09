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
	
include_once("config.php");
include_once("functions.php");

	$settings = new Class_Settings();

$messages = "";

// set refresh option into the session cookie
if(array_key_exists("pagerefresh", $_GET))
{
    if(getRequestVar("pagerefresh") == "false")
    {
        $_SESSION['prefresh'] = false;
        header("location: index.php");
        exit();
    }

    if(getRequestVar("pagerefresh") == "true")
    {
        $_SESSION["prefresh"] = true;
        header("location: index.php");
        exit();
    }
}
// Check to see if QManager is running if not Start it.
if (checkQManager() == 0 )
{
    if ($settings->get('AllowQueing')) {
        if (is_dir($settings->get('path')) && is_writable($settings->get('path'))) {
            AuditAction($cfg["constants"]["QManager"], "QManager Not Running");
            sleep(2);
            startQManager($settings->get('maxServerThreads'), $settings->get('maxUserThreads'), $settings->get('sleepInterval'));
            sleep(2);
        } else {
            AuditAction($cfg["constants"]["error"], "Error starting Queue Manager -- TorrentFlux settings are not correct (path is not valid)");
            if (IsAdmin())
            {
                header("location: admin.php?op=configSettings");
                exit();
            }
            else
            {
                $messages .= "<strong>Error</strong> TorrentFlux settings are not correct (path is not valid) -- please contact an admin.<br>";
            }
        }
    }
}

$torrent = getRequestVar('torrent');

if(!empty($torrent))
{
    include_once("AliasFile.php");

    if ($settings->get('enable_file_priority')) {
        include_once("setpriority.php");
        // Process setPriority Request.
        setPriority($torrent);
    }

    $spo = getRequestVar('setPriorityOnly');
    if (!empty($spo)){
        // This is a setPriortiyOnly Request.

    }else
    {
        // if we are to start a torrent then do so

        // check to see if the path to the python script is valid
        if (!is_file($settings->get('btphpbin'))) {
            AuditAction($cfg["constants"]["error"], "Error  Path for " . $settings->get('btphpbin') . " is not valid");
            if (IsAdmin())
            {
                header("location: admin.php?op=configSettings");
                exit();
            }
            else
            {
                $messages .= "<strong>Error</strong> TorrentFlux settings are not correct (path to python script is not valid) -- please contact an admin.<br>";
            }
        }

        $command = "";

        $rate = getRequestVar('rate');
        if (empty($rate)) {
            if ($rate != "0") {
                $rate = $settings->get('max_upload_rate');
            }
        }

        $drate = getRequestVar('drate');
        if (empty($drate)) {
            if ($drate != "0") {
                $drate = $settings->get('max_download_rate');
            }
        }

        $superseeder = getRequestVar('superseeder');
        if (empty($superseeder))
        {
            $superseeder = "0"; // should be 0 in most cases
        }
        $runtime = getRequestVar('runtime');
        if (empty($runtime)) {
            $runtime = $settings->get('torrent_dies_when_done');
        }

        $maxuploads = getRequestVar('maxuploads');
        if (empty($maxuploads)) {
            if ($maxuploads != "0") {
                $maxuploads = $settings->get('max_uploads');
            }
        }

        $minport = getRequestVar('minport');
        if (empty($minport)) {
            $minport = $settings->get('minport');
        }

        $maxport = getRequestVar('maxport');
        if (empty($maxport)) {
            $maxport = $settings->get('maxport');
        }

        $rerequest = getRequestVar("rerequest");
        if (empty($rerequest)) {
            $rerequest = $settings->get('rerequest_interval');
        }

        $sharekill = getRequestVar('sharekill');

        if ($runtime == "True" )
        {
            $sharekill = "-1";
        }

        if (empty($sharekill)) {
            if ($sharekill != "0") {
                $sharekill = $settings->get('sharekill');
            }
        }

        if ($settings->get('AllowQueing')) {
            if(IsAdmin()) {
                $queue = getRequestVar('queue');
                if($queue == 'on') {
                    $queue = "1";
                } else {
                    $queue = "0";
                }
            } else {
                $queue = "1";
            }
        }

        $crypto_allowed = getRequestVar('crypto_allowed');
        if (empty($crypto_allowed)) {
            $crypto_allowed = $settings->get('crypto_allowed');
        }

        $crypto_only = getRequestVar('crypto_only');
        if (empty($crypto_only)) {
            $crypto_only = $settings->get('crypto_only');
        }

        $crypto_stealth = getRequestVar('crypto_stealth');
        if (empty($crypto_stealth)) {
            $crypto_stealth = $settings->get('crypto_stealth');
        }

        //$torrent = urldecode($torrent);
        $alias = getAliasName($torrent);
        $owner = getOwner($torrent);

        // The following lines of code were suggested by Jody Steele jmlsteele@stfu.ca
        // This is to help manage user downloads by their user names
        //if the user's path doesnt exist, create it
        if (!is_dir($settings->get('path') . "/".$owner)) {
            if (is_writable($settings->get('path'))) {
                mkdir($settings->get('path') . "/".$owner, 0777);
            } else {
                AuditAction($cfg["constants"]["error"], "Error -- " . $settings->get('path') . " is not writable.");
                if (IsAdmin()) {
                    header("location: admin.php?op=configSettings");
                    exit();
                } else {
                    $messages .= "<strong>Error</strong> TorrentFlux settings are not correct (path is not writable) -- please contact an admin.<br>";
                }
            }
        }

        // create AliasFile object and write out the stat file
        $af = new AliasFile($settings->get('torrent_file_path') . $alias . ".stat", $owner);

        if ($settings->get('AllowQueing')) {
            if ($queue == "1") {
                $af->QueueTorrentFile();  // this only writes out the stat file (does not start torrent)
            } else {
                $af->StartTorrentFile();  // this only writes out the stat file (does not start torrent)
            }
        } else {
            $af->StartTorrentFile();  // this only writes out the stat file (does not start torrent)
        }

        if (usingTornado()) {
            $command = escapeshellarg($runtime)." ".escapeshellarg($sharekill)." '" . $settings->get('torrent_file_path') . $alias . ".stat' ".$owner." --responsefile '" . $settings->get('torrent_file_path') . $torrent . "' --display_interval 5 --max_download_rate ". escapeshellarg($drate) ." --max_upload_rate ".escapeshellarg($rate)." --max_uploads ".escapeshellarg($maxuploads)." --minport ".escapeshellarg($minport)." --maxport ".escapeshellarg($maxport)." --rerequest_interval ".escapeshellarg($rerequest)." --super_seeder ".escapeshellarg($superseeder)." --crypto_allowed ".escapeshellarg($crypto_allowed)." --crypto_only ".escapeshellarg($crypto_only)." --crypto_stealth ".escapeshellarg($crypto_stealth);

            if (file_exists($settings->get('torrent_file_path') . $alias . ".prio")) {
                $priolist = explode(',',file_get_contents($settings->get('torrent_file_path') . $alias . ".prio"));
                $priolist = implode(',',array_slice($priolist,1,$priolist[0]));
                $command .= " --priority ".escapeshellarg($priolist);
            }

            if ($settings->get('cmd_options')) {
            	$command .= " ".escapeshellarg($settings->get('cmd_options'));
            }
            
            $command .= " > /dev/null &";

            if ($settings->get('AllowQueing') && $queue == "1") {
                //  This file is being queued.
            } else {
                // This flie is being started manually.
				if ($settings->get('pythonCmd') == null) {
					$settings->save(array('pythonCmd' => '/usr/bin/python'));
                }

               if ($settings->get('debugTorrents') == null) {
                    $settings->save(array('debugTorrents' => '0'));
                }

                if (!$settings->get('debugTorrents')) {
                    $pyCmd = escapeshellarg($settings->get('pythonCmd')) . " -OO";
                }else{
                    $pyCmd = escapeshellarg($settings->get('pythonCmd'));
                }

                $command = "cd " . $settings->get('path') . $owner . "; HOME=".$settings->get('path')."; export HOME; nohup " . $pyCmd . " " .escapeshellarg($settings->get('btphpbin')) . " " . $command;
            }

        }
        else
        {
            // Must be using the Original BitTorrent Client
            // This is now being required to allow Queing functionality
            //$command = "cd " . $settings->get('path') . $owner . "; nohup " . $settings->get('btphpbin') . " ".$runtime." ".$sharekill." ".$settings->get('torrent_file_path').$alias.".stat ".$owner." --responsefile \"".$settings->get('torrent_file_path').$torrent."\" --display_interval 5 --max_download_rate ". $drate ." --max_upload_rate ".$rate." --max_uploads ".$maxuploads." --minport ".$minport." --maxport ".$maxport." --rerequest_interval ".$rerequest." ".$settings->get('cmd_options')." > /dev/null &";
            $messages .= "<strong>Error</strong> BitTornado is only supported Client at this time.<br>";
        }

        // write the session to close so older version of PHP will not hang
        session_write_close();

        if($af->running == "3") {
            writeQinfo($settings->get('torrent_file_path')."queue/".$alias.".stat",$command);
            AuditAction($cfg["constants"]["queued_torrent"], $torrent."<br>Die:".$runtime.", Sharekill:".$sharekill.", MaxUploads:".$maxuploads.", DownRate:".$drate.", UploadRate:".$rate.", Ports:".$minport."-".$maxport.", SuperSeed:".$superseeder.", Rerequest Interval:".$rerequest);
            AuditAction($cfg["constants"]["queued_torrent"], $command);
        } else {
            // The following command starts the torrent running! w00t!
            passthru($command);

            AuditAction($cfg["constants"]["start_torrent"], $torrent."<br>Die:".$runtime.", Sharekill:".$sharekill.", MaxUploads:".$maxuploads.", DownRate:".$drate.", UploadRate:".$rate.", Ports:".$minport."-".$maxport.", SuperSeed:".$superseeder.", Rerequest Interval:".$rerequest);

            // slow down and wait for thread to kick off.
            // otherwise on fast servers it will kill stop it before it gets a chance to run.
            sleep(1);
        }

        if ($messages == "")
        {
            if (array_key_exists("closeme",$_POST))
            {
?>
                <script>
                    window.opener.location.reload(true);
                    window.close();
                </script>
<?php
               exit();
            }
            else
            {
                header("location: index.php");
                exit();
            }
        }
        else
        {
            AuditAction($cfg["constants"]["error"], $messages);
        }
    }
}


// Do they want us to get a torrent via a URL?
$url_upload = getRequestVar('url_upload');

if(! $url_upload == '')
{
    $arURL = explode("/", $url_upload);
    $file_name = urldecode($arURL[count($arURL)-1]); // get the file name
    $file_name = str_replace(array("'",","), "", $file_name);
    $file_name = stripslashes($file_name);
    $ext_msg = "";

    // Check to see if url has something like ?passkey=12345
    // If so remove it.
    if( ( $point = strrpos( $file_name, "?" ) ) !== false )
    {
        $file_name = substr( $file_name, 0, $point );
    }

    $ret = strrpos($file_name,".");
    if ($ret === false)
    {
        $file_name .= ".torrent";
    }
    else
    {
        if(!strcmp(strtolower(substr($file_name, strlen($file_name)-8, 8)), ".torrent") == 0)
        {
            $file_name .= ".torrent";
        }
    }

    $url_upload = str_replace(" ", "%20", $url_upload);

    // This is to support Sites that pass an id along with the url for torrent downloads.
    $tmpId = getRequestVar("id");
    if(!empty($tmpId))
    {
        $url_upload .= "&id=".$tmpId;
    }

    // Call fetchtorrent to retrieve the torrent file
    $output = FetchTorrent( $url_upload );

    if (array_key_exists("save_torrent_name",$cfg))
    {
        if ($cfg["save_torrent_name"] != "")
        {
            $file_name = $cfg["save_torrent_name"];
        }
    }

    $file_name = cleanFileName($file_name);

    // if the output had data then write it to a file
    if ((strlen($output) > 0) && (strpos($output, "<br />") === false))
    {
        if (is_file($settings->get('torrent_file_path').$file_name)) {
            // Error
            $messages .= "<strong>Error</strong> with (<b>".htmlentities($file_name)."</b>), the file already exists on the server.<br><center><a href=\"".$_SERVER['PHP_SELF']."\">[Refresh]</a></center>";
            $ext_msg = "DUPLICATE :: ";
        }
        else
        {
            // open a file to write to
            $fw = fopen($settings->get('torrent_file_path').$file_name,'w');
            fwrite($fw, $output);
            fclose($fw);
        }
    }
    else
    {
        $messages .= "<strong>Error</strong> Getting the File (<b>".htmlentities($file_name)."</b>), Could be a Dead URL.<br><center><a href=\"".$_SERVER['PHP_SELF']."\">[Refresh]</a></center>";
    }

    if ($messages == "")
    {
        AuditAction($cfg["constants"]["url_upload"], $file_name);
        header("location: index.php");
        exit();
    }
    else
    {
        // there was an error
        AuditAction($cfg["constants"]["error"], $cfg["constants"]["url_upload"]." :: ".$ext_msg.$file_name);
    }
}

// Handle the file upload if there is one
if(!empty($_FILES['upload_file']['name']))
{
    $file_name = stripslashes($_FILES['upload_file']['name']);
    $file_name = str_replace(array("'",","), "", $file_name);
    $file_name = cleanFileName($file_name);
    $ext_msg = "";

    if($_FILES['upload_file']['size'] <= 1000000 &&
       $_FILES['upload_file']['size'] > 0)
    {
        if (ereg(getFileFilter($cfg["file_types_array"]), $file_name))
        {
            //FILE IS BEING UPLOADED
            if (is_file($settings->get('torrent_file_path').$file_name)) {
                // Error
                $messages .= "<strong>Error</strong> with (<b>".htmlentities($file_name)."</b>), the file already exists on the server.<br><center><a href=\"".$_SERVER['PHP_SELF']."\">[Refresh]</a></center>";
                $ext_msg = "DUPLICATE :: ";
            } else {
                if(move_uploaded_file($_FILES['upload_file']['tmp_name'], $settings->get('torrent_file_path').$file_name))
                {
                    chmod($settings->get('torrent_file_path').$file_name, 0644);

                    AuditAction($cfg["constants"]["file_upload"], $file_name);

                    header("location: index.php");
                }
                else
                {
                    $messages .= "<font color=\"#ff0000\" size=3>ERROR: File not uploaded, file could not be found or could not be moved:<br>".$settings->get('torrent_file_path') . htmlentities($file_name)."</font><br>";
                }
            }
        }
        else
        {
            $messages .= "<font color=\"#ff0000\" size=3>ERROR: The type of file you are uploading is not allowed.</font><br>";
        }
    }
    else
    {
        $messages .= "<font color=\"#ff0000\" size=3>ERROR: File not uploaded, check file size limit.</font><br>";
    }

    if($messages != "")
    {
        // there was an error
        AuditAction($cfg["constants"]["error"], $cfg["constants"]["file_upload"]." :: ".$ext_msg.$file_name);
    }
}  // End File Upload


// if a file was set to be deleted then delete it
$delfile = SecurityClean(getRequestVar('delfile'));
if(! $delfile == '')
{
    $alias_file = SecurityClean(getRequestVar('alias_file'));
    if (($cfg["user"] == getOwner($delfile)) || IsAdmin())
    {
        @unlink($settings->get('torrent_file_path').$delfile);
        @unlink($settings->get('torrent_file_path').$alias_file);
        // try to remove the QInfo if in case it was queued.
        @unlink($settings->get('torrent_file_path')."queue/".$alias_file.".Qinfo");

        // try to remove the pid file
        @unlink($settings->get('torrent_file_path').$alias_file.".pid");
        @unlink($settings->get('torrent_file_path').getAliasName($delfile).".prio");

        AuditAction($cfg["constants"]["delete_torrent"], $delfile);

        header("location: index.php");
        exit();
    }
    else
    {
        AuditAction($cfg["constants"]["error"], $cfg["user"]." attempted to delete ".$delfile);
    }
}

// Did the user select the option to kill a running torrent?
$kill = getRequestVar('kill');
if(! $kill == '' && is_numeric($kill) )
{
    include_once("AliasFile.php");
    include_once("RunningTorrent.php");

    $kill_torrent = getRequestVar('kill_torrent');
    $alias_file = SecurityClean(getRequestVar('alias_file'));
    // We are going to write a '0' on the front of the stat file so that
    // the BT client will no to stop -- this will report stats when it dies
    $the_user = getOwner($kill_torrent);
    // read the alias file
    // create AliasFile object
    $af = new AliasFile($settings->get('torrent_file_path') . $alias_file, $the_user);
    if($af->percent_done < 100)
    {
        // The torrent is being stopped but is not completed dowloading
        $af->percent_done = ($af->percent_done + 100)*-1;
        $af->running = "0";
        $af->time_left = "Torrent Stopped";
    }
    else
    {
        // Torrent was seeding and is now being stopped
        $af->percent_done = 100;
        $af->running = "0";
        $af->time_left = "Download Succeeded!";
    }

    // see if the torrent process is hung.
    if (!is_file($settings->get('torrent_file_path') . $alias_file.".pid"))
    {
        $runningTorrents = getRunningTorrents();
        foreach ($runningTorrents as $key => $value)
        {
            $rt = new RunningTorrent($value);
            if ($rt->statFile == $alias_file) {
                AuditAction($cfg["constants"]["error"], "Posible Hung Process " . $rt->processId);
            //    $result = exec("kill ".$rt->processId);
            }
        }
    }

    // Write out the new Stat File
    $af->WriteFile();

    AuditAction($cfg["constants"]["kill_torrent"], $kill_torrent);
    $return = getRequestVar('return');
    if (!empty($return))
    {
        sleep(3);
        passthru("kill ".$kill);
        // try to remove the pid file
        @unlink($settings->get('torrent_file_path') . $alias_file.".pid");
        header("location: ".$return.".php?op=queueSettings");
        exit();
    }
    else
    {
        header("location: index.php");
        exit();
    }
}

// Did the user select the option to remove a torrent from the Queue?
if(isset($_REQUEST["dQueue"]))
{
    $alias_file = SecurityClean(getRequestVar('alias_file'));
    $QEntry = getRequestVar('QEntry');

    // Is the Qinfo file still there?
    if (file_exists($settings->get('torrent_file_path') . "queue/".$alias_file.".Qinfo"))
    {
        // Yes, then delete it and update the stat file.
        include_once("AliasFile.php");
        // We are going to write a '2' on the front of the stat file so that
        // it will be set back to New Status
        $the_user = getOwner($QEntry);
        // read the alias file
        // create AliasFile object
        $af = new AliasFile($settings->get('torrent_file_path') . $alias_file, $the_user);

        if($af->percent_done > 0 && $af->percent_done < 100)
        {
            // has downloaded something at some point, mark it is incomplete
            $af->running = "0";
            $af->time_left = "Torrent Stopped";
        }

        if ($af->percent_done == 0 || $af->percent_done == "")
        {
            $af->running = "2";
            $af->time_left = "";
        }

        if ($af->percent_done == 100)
        {
            // Torrent was seeding and is now being stopped
            $af->running = "0";
            $af->time_left = "Download Succeeded!";
        }

        // Write out the new Stat File
        $af->WriteFile();

        // Remove Qinfo file.
        @unlink($settings->get('torrent_file_path') . "queue/".$alias_file.".Qinfo");

        AuditAction($cfg["constants"]["unqueued_torrent"], $QEntry);
    }
    else
    {
        // torrent has been started... try and kill it.
        AuditAction($cfg["constants"]["unqueued_torrent"], $QEntry . "has been started -- TRY TO KILL IT");
        header("location: index.php?alias_file=".$alias_file."&kill=true&kill_torrent=".urlencode($QEntry));
        exit();
    }

    header("location: index.php");
    exit();
}

$drivespace = getDriveSpace($settings->get('path'));


/************************************************************

************************************************************/
?>

<?php 
	$subMenu = 'index';
	include_once 'header.php' 
?>
<link rel="stylesheet" href="<?=$settings->get('base_url')?>/plugins/arboshiki/lobibox/dist/css/lobibox.min.css"/>
<script src="<?=$settings->get('base_url')?>/plugins/arboshiki/lobibox/dist/js/lobibox.min.js"></script>
<script src="<?=$settings->get('base_url')?>/js/index.js"></script>
<script>
<?php if (!isset($_SESSION['prefresh']) || ($_SESSION['prefresh'] == true)) { ?>

	var refreshTime = <?php echo $settings->get('page_refresh') ?>;
	
	function updateRefresh() {
		$('#span_refresh').html(refreshTime--);
	
		if (refreshTime < 0) {
	    	location.href = 'index.php';
		} else {
			setTimeout("updateRefresh();", 1000);
	  	}
	}

$(document).ready(function() {
    updateRefresh();
});

<?php } ?>
    var confirmDeleteTorrent = "<?php echo _ABOUTTODELETE ?>: ";
    var ol_closeclick = "1";
    var ol_close = "<b style=\"color:#ffffff\">X</b>";
    var ol_fgclass = "fg";
    var ol_bgclass = "bg";
    var ol_captionfontclass = "overCaption";
    var ol_closefontclass = "overClose";
    var ol_textfontclass = "overBody";
    var ol_cap = "&nbsp;Torrent Status";
</script>
<script src="overlib.js" type="text/javascript"></script>

<div id="overDiv" style="position:absolute;visibility:hidden;z-index:1000;"></div>

<?php
	// Does the user have messages?
	$sql = "select count(*) from tf_messages where to_user='".$cfg['user']."' and IsNew=1";
	
	$number_messages = $db->GetOne($sql);
	showError($db,$sql);
	$countMessages = '';
	if ($number_messages > 0) {
		$countMessages = ' (' . $number_messages . ')';
	}
?>

<?php if ($messages != '') { ?>
<div class="container">
	<div class="row">
		<div class="alert alert-danger alert-dismissible fade in" role="alert" style="margin-bottom:0px;margin-top:16px;">
			<button type="button" class="close" data-dismiss="alert" aria-label="Close">
			<span aria-hidden="true">&times;</span>
			</button>
			<?php echo $messages ?>
		</div>
	</div>
</div>
<?php } ?>

<div class="container">
    <div class="row">

        <div class="hidden-sm-down col-sm-12 bd-example" style="border:none;padding-right:0px;">
            <button class="display_upload_form btn btn-primary" type="button">
                <span class="btn-label icon fa fa-upload"></span>
                <?=_SELECTFILE?>
            </button>
            <button class="display_url_form btn btn-primary" type="button">
                <span class="btn-label icon fa fa-plus"></span>
                <?=_URLFILE?>
            </button>
            <button class="display_search_form btn btn-primary" type="button">
                <span class="btn-label icon fa fa-search"></span>
                Torrent <?=_SEARCH?>
            </button>
        </div>

        <div class="hidden-md-up col-sm-12 bd-example" style="border:none;padding-right:0px;">
            <button class="display_upload_form btn btn-primary btn-block" type="button">
                <span class="btn-label icon fa fa-upload"></span>
                <?=_SELECTFILE?>
            </button>

            <button class="display_url_form btn btn-primary btn-block" type="button">
                <span class="btn-label icon fa fa-plus"></span>
                <?=_URLFILE?>
            </button>
            <button class="display_search_form btn btn-primary btn-block" type="button">
                <span class="btn-label icon fa fa-search"></span>
                Torrent <?=_SEARCH?>
            </button>
        </div>

        <div class="col-sm-12">

            <fieldset class="form-group bd-example" id="form_upload" style="display:none;margin-right:-12px;margin-left:-15px;padding: 10px;">
                <form name="form_file" action="index.php" method="post" enctype="multipart/form-data">
                    <label for="upload_file"><?php echo _SELECTFILE ?></label>
                    <button type="button" class="close_form btn btn-danger btn-sm pull-right"><span class="btn-label icon fa fa-times"></span></button>
                    
                    <div class="input-group">
                        <input type="file" class="form-control" name="upload_file" id="upload_file" style="height:38px" />
                        <span class="input-group-btn">
                            <button class="btn btn-secondary" id="upload_torrent" type="submit">
                                <i class="fa fa-upload" aria-hidden="true" style="font-size:20px"></i>
                            </button>
                        </span>
                    </div>
                </form>
            </fieldset>

            <fieldset class="form-group bd-example" id="form_url" style="display:none;margin-right:-12px;margin-left:-15px;padding: 10px;">
                <form name="form_url" action="index.php" method="post">
                    <label for="url_upload"><?php echo _URLFILE ?></label>
                    <button type="button" class="close_form btn btn-danger btn-sm pull-right"><span class="btn-label icon fa fa-times"></span></button>
                    <div class="input-group">
                        <input type="text" class="form-control" name="url_upload" id="url_upload" />
                        <span class="input-group-btn">
                            <button class="btn btn-secondary" type="submit">
                                <i class="fa fa-upload" aria-hidden="true" style="font-size:24px"></i>
                            </button>
                        </span>
                    </div>
                </form>
            </fieldset>

            <?php if ($settings->get('enable_search')) { ?>
            <fieldset class="form-group bd-example" id="form_search" style="display:none;margin-right:-12px;margin-left:-15px;padding: 10px;">
                <form name="form_search" action="torrentSearch.php" method="get">
                    <label for="searchterm">Torrent <?php echo _SEARCH ?></label>
                    <button type="button" class="close_form btn btn-danger btn-sm pull-right"><span class="btn-label icon fa fa-times"></span></button>
                    <?php echo buildSearchEngineDDL($settings->get('searchEngine')); ?>
                    <div class="input-group">
                        <input type="text" class="form-control" name="searchterm" id="searchterm" />
                        <span class="input-group-btn">
                            <button class="btn btn-secondary" type="submit">
                                <i class="fa fa-search" aria-hidden="true" style="font-size:24px"></i>
                            </button>
                        </span>
                    </div>
                </form>
            </fieldset>
            <?php } ?>

			<?php 
				$users = GetUsers();
				$onlineUsers = $offlineUsers = array();
	
		        foreach ($users AS $user) {
		            if(IsOnline($user)) {
		                array_push($onlineUsers, $user);
		            } else {
		                array_push($offlineUsers, $user);
		            }
		        }
			?>
  			<fieldset class="form-group bd-example" style="display:none;margin-left:-12px;margin-right:-15px">
  				<table class="table table-striped">
  					<!-- ONLINE -->
    				<tr><th	>User</th></tr>
	  				<?php foreach ($onlineUsers AS $user) { ?>
		                <tr>
							<td>
								<a href="message.php?to_user=<?php echo $user ?>">
									<i class="fa fa-user" aria-hidden="true" style="color:#5CB85C;margin-right:4px;"></i><?php echo $user ?>
								</a>
							</td>
						</tr>
	    			<?php } ?>
					
					<!-- OFFLINE -->
	  				<?php foreach ($offlineUsers AS $user) { ?>
		                <tr>
							<td>
								<a href="message.php?to_user=<?php echo $user ?>">
									<i class="fa fa-user" aria-hidden="true" style="color:#D9534F;margin-right:4px;"></i><?php echo $user ?>
								</a>
							</td>
						</tr>
	    			<?php } ?>
				</table>
			</fieldset>
			
  			<fieldset class="form-group bd-example" style="display:none;margin-left:-12px;margin-right:-15px">
  				<table class="table table-striped">
  				<thead>
    				<tr><th><?php echo _TORRENTLINKS ?></th></tr>
  				</thead>
  				<tbody>
  				<?php
  					$links = GetLinks();
        			if (is_array($links)) {
            			foreach($links as $link) { 
            		?>
					<tr>
						<td>
							<a href="<?php echo $link['url'] ?>" target="_blank" title="<?php echo $link['url'] ?>">
								<?php echo $link['sitename'] ?>
							</a>
						</td>
					</tr>
           			<?php
            				}
        				}
  					?>
				</tbody>
				</table>
			</fieldset>
		</div>
	</div>
</div>

<div class="container">
	<div class="row">
		<div class="col-sm-12 bd-example" style="padding: 10px;">
			<?php displayDriveSpaceBar($drivespace); ?>
		</div>
	</div>
</div>

<div class="container">
	<div class="row">
		<div class="col-sm-12 bd-example">
			<?php
                $dirName = $settings->get('torrent_file_path');
    			include_once 'AliasFile.php';
    			include_once 'RunningTorrent.php';
    			
    			$runningTorrents = getRunningTorrents();
			
    			$arList = array();
    			$file_filter = getFileFilter($cfg["file_types_array"]);
    			
    			if (is_dir($dirName)) {
    			    $handle = opendir($dirName);
    			} else {
    			    // nothing to read
    			    if (IsAdmin()) {
    			        echo "<b>ERROR:</b> ".$dirName." Path is not valid. Please edit <a href='admin.php?op=configSettings'>settings</a><br>";
    			    } else {
    			        echo "<b>ERROR:</b> Contact an admin the Path is not valid.<br>";
    			    }
    			    exit;
    			}
    			
    			$lastUser = '';
    			$arUserTorrent = $arListTorrent = array();
    			
    			while ($entry = readdir($handle)) {
    			    if ($entry != "." && $entry != "..") {
    			        if (!is_dir($dirName."/".$entry)) {
    			            if (ereg($file_filter, $entry)) {
    			                $key = filemtime($dirName."/".$entry).md5($entry);
    			                $arList[$key] = $entry;
    			            }
    			        }
    			    }
    			}
    			
    			// sort the files by date
    			krsort($arList);
    			
    			foreach ($arList as $entry) {
    			    $output = $kill_id = $timeStarted = $torrentfilelink = '';
    			    $displayname = $entry;
    			    $show_run = true;
    			    $torrentowner = getOwner($entry);
    			    $owner = IsOwner($cfg["user"], $torrentowner);
    			    $estTime = "&nbsp;";
    			    $alias = getAliasName($entry).".stat";
    			    $af = new AliasFile($dirName.$alias, $torrentowner);
    			
    			    if (!file_exists($dirName.$alias)) {
    			        $af->running = "2"; // file is new
    			        $af->size = getDownloadSize($dirName.$entry);
    			        $af->WriteFile();
    			    }
    			
    			    if (strlen($entry) >= 47) {
    			        // needs to be trimmed
    			        $displayname = substr($entry, 0, 44);
    			        $displayname .= "...";
    			    }
    			
    			    // find out if any screens are running and take their PID and make a KILL option
    			    foreach ($runningTorrents as $key => $value) {
    			        $rt = new RunningTorrent($value);
    			        if ($rt->statFile == $alias) {
    			            if ($kill_id == "") {
    			                $kill_id = $rt->processId;
    			            } else {
    			                // there is more than one PID for this torrent
    			                // Add it so it can be killed as well.
    			                $kill_id .= "|".$rt->processId;
    			            }
    			        }
    			    }
    			
    			    // Check to see if we have a pid without a process.
    			    if (is_file($settings->get('torrent_file_path').$alias.".pid") && empty($kill_id)) {
    			        // died outside of tf and pid still exists.
    			        @unlink($settings->get('torrent_file_path').$alias.".pid");
    			
    			        if(($af->percent_done < 100) && ($af->percent_done >= 0)) {
    			            // The file is not running and the percent done needs to be changed
    			            $af->percent_done = ($af->percent_done+100)*-1;
    			        }
    			
    			        $af->running = "0";
    			        $af->time_left = "Torrent Died";
    			        $af->up_speed = "";
    			        $af->down_speed = "";
    			        // write over the status file so that we can display a new status
    			        $af->WriteFile();
    			    }
    			
    			    if ($settings->get('enable_torrent_download')) {
    			        $torrentLinkIcon = "<i class=\"fa fa-download\" style='color:#5CB85C' aria-hidden=\"true\" title=\"Download Torrent File\"></i>";
    			        $torrentfilelink = "<a href=\"maketorrent.php?download=".urlencode($entry)."\">$torrentLinkIcon</a> ";
    			    }
    			
    			    $hd = getStatusImage($af);
    			
    			    $circleColor = 'black';
    			    $circleColor = ($af->running && $af->seeds < 2) ? 'orange' : $circleColor;
    			    $circleColor = ($af->running && $af->seeds == 0) ? 'red' : $circleColor;
    			    $circleColor = ($af->running && $af->seeds >= 2) ? '#5CB85C' : $circleColor;
    			    
    			    $output .= "<tr><td><i class=\"fa fa-circle\" style='color:$circleColor' aria-hidden=\"true\"></i> ".$torrentfilelink.$displayname."</td>";
    			    $output .= "<td>".formatBytesToKBMGGB($af->size)."</td>";
    			    $output .= "<td><a href=\"message.php?to_user=".$torrentowner."\">".$torrentowner."</a></td>";
    			    $output .= "<td>";
    			
    			    if ($af->running == "2") {
    			        $output .= "<i style=\"color:#32cd32\">" . _NEW . "</i>";
    			
    			    } elseif ($af->running == "3" ) {
    			        $estTime = "Waiting...";
    			        $qDateTime = '';
    			        if(is_file($dirName."queue/".$alias.".Qinfo")) {
    			            $qDateTime = date("m/d/Y H:i:s", strval(filectime($dirName."queue/".$alias.".Qinfo")));
    			        }
    			
    			        $output .= "<i stlye=\"color:#000000\" onmouseover=\"return overlib('"._QUEUED.": ".$qDateTime."<br>', CSSCLASS);\" onmouseout=\"return nd();\">"._QUEUED."</i>";
    			
    			    } else {
    			        if ($af->time_left != "" && $af->time_left != "0") {
    			            $estTime = $af->time_left;
    			        }
    			
    			        $sql_search_time = "Select time from tf_log where action like '%Upload' and file like '".$entry."%'";
    			        $result_search_time = $db->Execute($sql_search_time);
    			        list($uploaddate) = $result_search_time->FetchRow();
    			
    			        $lastUser = $torrentowner;
    			        $sharing = $af->sharing."%";
    			        $graph_width = 1;
    			        $progress_color = "#00ff00";
    			        $background = "#000000";
    			        $bar_width = "4";
    			        $popup_msg = _ESTIMATEDTIME.": ".$af->time_left;
    			        $popup_msg .= "<br>"._DOWNLOADSPEED.": ".$af->down_speed;
    			        $popup_msg .= "<br>"._UPLOADSPEED.": ".$af->up_speed;
    			        $popup_msg .= "<br>"._SHARING.": ".$sharing;
    			        $popup_msg .= "<br>Seeds: ".$af->seeds;
    			        $popup_msg .= "<br>Peers: ".$af->peers;
    			        $popup_msg .= "<br>"._USER.": ".$torrentowner;
    			
    			        $eCount = 0;
    			        foreach ($af->errors as $key => $value)
    			        {
    			            if(strpos($value," (x"))
    			            {
    			                $curEMsg = substr($value,strpos($value," (x")+3);
    			                $eCount += substr($curEMsg,0,strpos($curEMsg,")"));
    			            }
    			            else
    			            {
    			                $eCount += 1;
    			            }
    			        }
    			        $popup_msg .= "<br>"._ERRORSREPORTED.": ".strval($eCount);
    			
    			        $popup_msg .= "<br>"._UPLOADED.": ".date("m/d/Y H:i:s", $uploaddate);
    			
    			        if (is_file($dirName.$alias.".pid"))
    			        {
    			            $timeStarted = "<br>"._STARTED.": ".date("m/d/Y H:i:s",  strval(filectime($dirName.$alias.".pid")));
    			        }
    			
    			        // incriment the totals
    			        if(!isset($cfg["total_upload"])) $cfg["total_upload"] = 0;
    			        if(!isset($cfg["total_download"])) $cfg["total_download"] = 0;
    			
    			        $cfg["total_upload"] = $cfg["total_upload"] + GetSpeedValue($af->up_speed);
    			        $cfg["total_download"] = $cfg["total_download"] + GetSpeedValue($af->down_speed);
    			
    			        if($af->percent_done >= 100)
    			        {
    			            if(trim($af->up_speed) != "" && $af->running == "1")
    			            {
    			                $popup_msg .= $timeStarted;
    			                $output .= "<a class=\"downloaddetails\" href=\"downloaddetails.php?alias=".$alias."&torrent=".urlencode($entry)."\" onmouseover=\"return overlib('".$popup_msg."<br>', CSSCLASS);\" onmouseout=\"return nd();\">seeding (".$af->up_speed.") ".$sharing."</a>";
    			            }
    			            else
    			            {
    			                $popup_msg .= "<br>"._ENDED.": ".date("m/d/Y H:i:s",  strval(filemtime($dirName.$alias)));
    			                $output .= "<a class=\"downloaddetails\" href=\"downloaddetails.php?alias=".$alias."&torrent=".urlencode($entry)."\" onmouseover=\"return overlib('".$popup_msg."<br>', CSSCLASS);\" onmouseout=\"return nd();\"><i><font color=red>"._DONE."</font></i></a>";
    			            }
    			            $show_run = false;
    			        }
    			        else if ($af->percent_done < 0)
    			        {
    			            $popup_msg .= $timeStarted;
    			            $output .= "<a class=\"downloaddetails\" href=\"downloaddetails.php?alias=".$alias."&torrent=".urlencode($entry)."\" onmouseover=\"return overlib('".$popup_msg."<br>', CSSCLASS);\" onmouseout=\"return nd();\"><i><font color=\"#989898\">"._INCOMPLETE."</font></i></a>";
    			            $show_run = true;
    			        }
    			        else
    			        {
    			            $popup_msg .= $timeStarted;
    			
    			            if ($af->percent_done > 1) {
    			                $graph_width = $af->percent_done;
    			            }
    			
    			            if ($graph_width == 100) {
    			                $background = $progress_color;
    			            }
    			
    			            $output .= "<a class=\"downloaddetails\" href=\"downloaddetails.php?alias=".$alias."&torrent=".urlencode($entry)."\" onmouseover=\"return overlib('".$popup_msg."<br>', CSSCLASS);\" onmouseout=\"return nd();\">";
    			            $output .= "<font class=\"tiny\"><strong>".$af->percent_done."%</strong> @ ".$af->down_speed."</font></a><br>";
    			
    			            $output .= "<progress class=\"progress progress-success\" value=\"{$af->percent_done}\" max=\"100\" style=\"margin-bottom:0px\">{$af->percent_done}%</progress>";
    			        }
    			
    			    }
    			
    			    $output .= "</td>";
    			    $output .= "<td>".$estTime."</td>";
    			    $output .= "<td>";
    			
    			    $torrentDetails = _TORRENTDETAILS;
    			    if ($lastUser != "")
    			    {
    			        $torrentDetails .= "\n"._USER.": ".$lastUser;
    			    }
    			
    			    $output .= "<a href=\"details.php?torrent=".urlencode($entry);
    			    if($af->running == 1)
    			    {
    			        $output .= "&als=false";
    			    }
    			    $output .= "\">";
                    $output .= "<i class=\"fa fa-info\" aria-hidden=\"true\" title=\"$torrentDetails\"></i></a> ";
    			    
    			    if ($owner || IsAdmin($cfg["user"]))
    			    {
    			        if($kill_id != "" && $af->percent_done >= 0 && $af->running == 1)
    			        {
    			            $output .= " <a href=\"index.php?alias_file=".$alias."&kill=".$kill_id."&kill_torrent=".urlencode($entry)."\">";
    			            $output .= "<i class=\"fa fa-arrow-circle-o-down\" style=\"color:red\" aria-hidden=\"true\" title=\"" . _STOPDOWNLOAD . "\"></i>";
    			            $output .= "</a> ";
    			            $output .= "<i class=\"fa fa-trash\" style=\"color:red\" aria-hidden=\"true\"></i>";
    			             
    			        }
    			        else
    			        {
    			            if($torrentowner == "n/a")
    			            {
    			                $output .= "<i class=\"fa fa-arrow-circle-down\" style=\"color: red;\" aria-hidden=\"true\" title=\""._NOTOWNER."\" /> ";
    			            }
    			            else
    			            {
    			                if ($af->running == "3")
    			                {
    			                    $output .= "<a href=\"index.php?alias_file=".$alias."&dQueue=".$kill_id."&QEntry=".urlencode($entry)."\">";
    			                    $output .= "<i class=\"fa fa-arrow-circle-o-down\" style=\"color:orange\" title=\""._DELQUEUE."\" aria-hidden=\"true\"></i>";
    			                    $output .= "</a> ";
    			                }
    			                else
    			                {
    			                    if (!is_file($settings->get('torrent_file_path').$alias.".pid"))
    			                    {
    			                        // Allow Avanced start popup?
    			                        if ($settings->get('advanced_start')) {
    			                            if($show_run)
    			                            {
    			                                $output .= "<a class=\"startTorrent\" href=\"startpop.php?torrent=".urlencode($entry)."\">";
    			                                $output .= "<i class=\"fa fa-arrow-circle-o-down\" style=\"color:#5CB85C\" title=\""._RUNTORRENT."\" aria-hidden=\"true\"></i>";
    			                                $output .= "</a> ";
    			                            }
    			                            else
    			                            {
    			                                $output .= "<a class=\"startTorrent\" href=\"startpop.php?torrent=".urlencode($entry)."\">";
    			                                $output .= "<i class=\"fa fa-arrow-circle-o-down\" style=\"color:blue\" title=\""._SEEDTORRENT."\" aria-hidden=\"true\"></i>";
    			                                $output .= "</a> ";
    			                            }
    			                        }
    			                        else
    			                        {
    			                            // Quick Start
    			                            if($show_run)
    			                            {
    			                                $output .= "<a href=\"".$_SERVER['PHP_SELF']."?torrent=".urlencode($entry)."\"><img src=\"images/run_on.gif\" title=\""._RUNTORRENT."\" alt=\"\"></a>";
    			                            }
    			                            else
    			                            {
    			                                $output .= "<a href=\"".$_SERVER['PHP_SELF']."?torrent=".urlencode($entry)."\">";
    			                                $output .= "<i class=\"fa fa-arrow-circle-o-down\" style=\"color:blue\" title=\""._SEEDTORRENT."\" aria-hidden=\"true\"></i>";
    			                                $output .= "</a> ";
    			                            }
    			                        }
    			                    }
    			                    else
    			                    {
    			                        // pid file exists so this may still be running or dieing.
    			                        $output .= "<i class=\"fa fa-arrow-circle-down\" style=\"color: red;\" aria-hidden=\"true\" title=\""._STOPPING."\" /> ";
    			                    }
    			                }
    			            }
    			
    			            if (!is_file($settings->get('torrent_file_path').$alias.".pid"))
    			            {
    			                $deletelink = $_SERVER['PHP_SELF']."?alias_file=".$alias."&delfile=".urlencode($entry);
    			                $output .= "<a class=\"deleteTorrent\" href=\"".$deletelink."\" data-torrent=\"" . urlencode($entry) . "\">";
    			                $output .= "<i class=\"fa fa-trash-o\" style=\"color:red\" aria-hidden=\"true\" title=\""._DELETE."\"></i>";
    			                $output .= "</a>";
    			            }
    			            else
    			            {
    			                // pid file present so process may be still running. don't allow deletion.
    			                $output .= "<i class=\"fa fa-trash\" style=\"color: red;\" aria-hidden=\"true\" title=\""._STOPPING."\" />";
    			            }
    			        }
    			    }
    			    else
    			    {
    			        $output .= "<img src=\"images/locked.gif\" title=\""._NOTOWNER."\">";
    			        $output .= "<img src=\"images/locked.gif\" title=\""._NOTOWNER."\">";
    			    }
    			
    			    $output .= "</td>";
    			    $output .= "</tr>\n";
    			
    			    // Is this torrent for the user list or the general list?
    			    if ($cfg["user"] == getOwner($entry))
    			    {
    			        array_push($arUserTorrent, $output);
    			    }
    			    else
    			    {
    			        array_push($arListTorrent, $output);
    			    }
    			}
    			closedir($handle);
    			
    			// Now spit out the junk
    			echo "<table class=\"table table-striped\" id=\"torrentList\">";
    			
    			if (sizeof($arUserTorrent) > 0)
    			{
    			    echo "<tr><th>".$cfg["user"].": "._TORRENTFILE."</th>";
    			    echo "<th>Size</th>";
    			    echo "<th>"._USER."</th>";
    			    echo "<th>"._STATUS."</th>";
    			    echo "<th>"._ESTIMATEDTIME."</th>";
    			    echo "<th>"._ADMIN."</th>";
    			    echo "</tr>\n";
    			    foreach($arUserTorrent as $torrentrow)
    			    {
    			        echo $torrentrow;
    			    }
    			}
    			
    			if (sizeof($arListTorrent) > 0)
    			{
    			    echo "<tr><td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\"><div align=center class=\"title\">"._TORRENTFILE."</div></td>";
    			    echo "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\"><div align=center class=\"title\">Size</div></td>";
    			    echo "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\"><div align=center class=\"title\">"._USER."</div></td>";
    			    echo "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\"><div align=center class=\"title\">"._STATUS."</div></td>";
    			    echo "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\"><div align=center class=\"title\">"._ESTIMATEDTIME."</div></td>";
    			    echo "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\"><div align=center class=\"title\">"._ADMIN."</div></td>";
    			    echo "</tr>\n";
    			    foreach($arListTorrent as $torrentrow)
    			    {
    			        echo $torrentrow;
    			    }
    			}
    			
			?>
			<table class="table table-striped">
   				<tr>
       				<td>
       					<i class="fa fa-info" aria-hidden="true" title="<?php echo _TORRENTDETAILS; ?>"></i>
						<?php echo _TORRENTDETAILS ?>
					</td>
       				<td>
       					<i class="fa fa-arrow-circle-o-down" style="color:#5CB85C" aria-hidden="true" title="<?php echo _RUNTORRENT; ?>"></i>
       					<?php echo _RUNTORRENT ?>
       				</td>
       				<td>
       					<i class="fa fa-arrow-circle-o-down" style="color:red" aria-hidden="true" title="<?php echo _STOPDOWNLOAD; ?>"></i>
       					<?php echo _STOPDOWNLOAD ?>
       				</td>
       				<?php if ($settings->get('AllowQueing')) { ?>
       					<td>
       						<i class="fa fa-arrow-circle-o-down" style="color:orange" aria-hidden="true" title="<?php echo _DELQUEUE; ?>"></i>
       						<?php echo _DELQUEUE ?>
       					</td>
       				<?php } ?>
       				<td>
       					<i class="fa fa-arrow-circle-o-down" style="color:blue" aria-hidden="true" title="<?php echo _SEEDTORRENT; ?>"></i>
       					<?php echo _SEEDTORRENT ?>
       				</td>
       				<td>
       					<i class="fa fa-trash-o" style="color:red" aria-hidden="true" title="<?php echo _DELETE; ?>"></i>
       					<?php echo _DELETE ?>
       				</td>
       				<?php if ($settings->get('enable_torrent_download')) { ?>
       					<td>
       						<i class="fa fa-download" style='color:#5CB85C' aria-hidden="true" title="Download Torrent meta file"></i>
       						Download Torrent
       					</td>
       				<?php } ?>
   				</tr>
   			</table>
   				
	    	<div class="row">
				<div class="col-sm-4 tiny" style="padding:15px 30px;">
					<?php
	    				if(checkQManager() > 0) {
	    				    echo "<i class=\"fa fa-circle\" style='color:#5CB85C;font-size:11px;' aria-hidden=\"true\" title=\"Queue Manager Running\"></i>";
	         				echo " Queue Manager Running<br>";
	         				echo "<strong>".strval(getRunningTorrentCount())."</strong> torrent(s) running and <strong>".strval(getNumberOfQueuedTorrents())."</strong> queued.<br>";
	         				echo "Total torrents server will run: <strong>".$settings->get('maxServerThreads')."</strong><br>";
	         				echo "Total torrents a user may run: <strong>".$settings->get('maxUserThreads')."</strong><br>";
	         				echo "* Torrents are queued when limits are met.<br>";
	    				} else {
	    				    echo "<i class=\"fa fa-circle\" style='color:black;font-size:11px;' aria-hidden=\"true\" title=\"Queue Manager Off\"></i>";
	        				echo " Queue Manager Off";
	    				}
					?>
				</div>
				<div class="col-sm-4 tiny" style="padding:15px 30px;text-align:center;">
					<?php
						if(!isset($_SESSION['prefresh']) || ($_SESSION['prefresh'] == true)) {
	        				echo "*** "._PAGEWILLREFRESH." <span id='span_refresh'>".$settings->get('page_refresh')."</span> "._SECONDS." ***<br>";
	        				echo "<a href=\"".$_SERVER['PHP_SELF']."?pagerefresh=false\" class=\"tiny\">"._TURNOFFREFRESH."</a>";
	    				} else {
	        				echo "<a href=\"".$_SERVER['PHP_SELF']."?pagerefresh=true\" class=\"tiny\">"._TURNONREFRESH."</a>";
	    				}
	
	    				if ($drivespace >= 98) {
							echo "<script>Lobibox.notify('error', { size: 'mini', icon: false, sound: false, msg: '"._WARNING.": ".$drivespace."% "._DRIVESPACEUSED."'}); </script>";
	    				}
	
	    				if (!array_key_exists("total_download",$cfg)) $cfg["total_download"] = 0;
	    				if (!array_key_exists("total_upload",$cfg)) $cfg["total_upload"] = 0;
					?>
				</div>
				<div class="col-sm-4">
			    	<table class="table table-striped" id="smallDownloadStats">
	        			<tr>
	           				<td><?php echo _CURRENTDOWNLOAD ?>:</td>
	           				<td><?php echo number_format($cfg["total_download"], 2); ?> kB/s</td>
	        			</tr>
	        			<tr>
	           				<td><?php echo _CURRENTUPLOAD ?>:</td>
	           				<td><?php echo number_format($cfg["total_upload"], 2); ?> kB/s</td>
	        			</tr>
	        			<tr>
	           				<td><?php echo _FREESPACE ?>:</td>
	           				<td><?php echo formatFreeSpace($cfg["free_space"]) ?></td>
	        			</tr>
	        			<tr>
	           				<td><?php echo _SERVERLOAD ?>:</td>
	           				<td>
	        					<?php
	            					if ($settings->get('show_server_load') && @isFile($settings->get('loadavg_path'))) {
	                					$loadavg_array = explode(" ", exec("cat ".escapeshellarg($settings->get('loadavg_path'))));
	                					$loadavg = $loadavg_array[2];
	                					echo $loadavg;
	            					} else {
	            	   					echo "n/a";
	            					}
	        					?>
	                		</td>
	            		</tr>
	            	</table>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
    // At this point Any User actions should have taken place
    // Check to see if the user has a force_read message from an admin
    if (IsForceReadMsg())
    {
        // Yes, then warn them
?>
        <script>
        if (confirm("<?php echo _ADMINMESSAGE ?>"))
        {
            document.location = "readmsg.php";
        }
        </script>
<?php
    }
?>
</body>
</html>