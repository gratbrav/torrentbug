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


include_once("config.php");
include_once("functions.php");

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
    if ($cfg["AllowQueing"])
    {
        if (is_dir($cfg["path"]) && is_writable($cfg["path"]))
        {
            AuditAction($cfg["constants"]["QManager"], "QManager Not Running");
            sleep(2);
            startQManager($cfg["maxServerThreads"],$cfg["maxUserThreads"],$cfg["sleepInterval"]);
            sleep(2);
        }
        else
        {
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

    if ($cfg["enable_file_priority"])
    {
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
        if (!is_file($cfg["btphpbin"]))
        {
            AuditAction($cfg["constants"]["error"], "Error  Path for ".$cfg["btphpbin"]." is not valid");
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
        if (empty($rate))
        {
            if ($rate != "0")
            {
                $rate = $cfg["max_upload_rate"];
            }
        }
        $drate = getRequestVar('drate');
        if (empty($drate))
        {
            if ($drate != "0")
            {
                $drate = $cfg["max_download_rate"];
            }
        }
        $superseeder = getRequestVar('superseeder');
        if (empty($superseeder))
        {
            $superseeder = "0"; // should be 0 in most cases
        }
        $runtime = getRequestVar('runtime');
        if (empty($runtime))
        {
            $runtime = $cfg["torrent_dies_when_done"];
        }

        $maxuploads = getRequestVar('maxuploads');
        if (empty($maxuploads))
        {
            if ($maxuploads != "0")
            {
                $maxuploads = $cfg["max_uploads"];
            }
        }
        $minport = getRequestVar('minport');
        if (empty($minport))
        {
            $minport = $cfg["minport"];
        }
        $maxport = getRequestVar('maxport');
        if (empty($maxport))
        {
            $maxport = $cfg["maxport"];
        }
        $rerequest = getRequestVar("rerequest");
        if (empty($rerequest))
        {
            $rerequest = $cfg["rerequest_interval"];
        }
        $sharekill = getRequestVar('sharekill');

        if ($runtime == "True" )
        {
            $sharekill = "-1";
        }

        if (empty($sharekill))
        {
            if ($sharekill != "0")
            {
                $sharekill = $cfg["sharekill"];
            }
        }
        if ($cfg["AllowQueing"])
        {
            if(IsAdmin())
            {
                $queue = getRequestVar('queue');
                if($queue == 'on')
                {
                    $queue = "1";
                }else
                {
                    $queue = "0";
                }
            }
            else
            {
                $queue = "1";
            }
        }

        $crypto_allowed = getRequestVar('crypto_allowed');
        if (empty($crypto_allowed))
        {
            $crypto_allowed = $cfg["crypto_allowed"];
        }

        $crypto_only = getRequestVar('crypto_only');
        if (empty($crypto_only))
        {
            $crypto_only = $cfg["crypto_only"];
        }

        $crypto_stealth = getRequestVar('crypto_stealth');
        if (empty($crypto_stealth))
        {
            $crypto_stealth = $cfg["crypto_stealth"];
        }

        //$torrent = urldecode($torrent);
        $alias = getAliasName($torrent);
        $owner = getOwner($torrent);

        // The following lines of code were suggested by Jody Steele jmlsteele@stfu.ca
        // This is to help manage user downloads by their user names
        //if the user's path doesnt exist, create it
        if (!is_dir($cfg["path"]."/".$owner))
        {
            if (is_writable($cfg["path"]))
            {
                mkdir($cfg["path"]."/".$owner, 0777);
            }
            else
            {
                AuditAction($cfg["constants"]["error"], "Error -- " . $cfg["path"] . " is not writable.");
                if (IsAdmin())
                {
                    header("location: admin.php?op=configSettings");
                    exit();
                }
                else
                {
                    $messages .= "<strong>Error</strong> TorrentFlux settings are not correct (path is not writable) -- please contact an admin.<br>";
                }
            }
        }

        // create AliasFile object and write out the stat file
        $af = new AliasFile($cfg["torrent_file_path"].$alias.".stat", $owner);

        if ($cfg["AllowQueing"])
        {
            if($queue == "1")
            {
                $af->QueueTorrentFile();  // this only writes out the stat file (does not start torrent)
            }
            else
            {
                $af->StartTorrentFile();  // this only writes out the stat file (does not start torrent)
            }
        }
        else
        {
            $af->StartTorrentFile();  // this only writes out the stat file (does not start torrent)
        }

        if (usingTornado())
        {
            $command = escapeshellarg($runtime)." ".escapeshellarg($sharekill)." '".$cfg["torrent_file_path"].$alias.".stat' ".$owner." --responsefile '".$cfg["torrent_file_path"].$torrent."' --display_interval 5 --max_download_rate ". escapeshellarg($drate) ." --max_upload_rate ".escapeshellarg($rate)." --max_uploads ".escapeshellarg($maxuploads)." --minport ".escapeshellarg($minport)." --maxport ".escapeshellarg($maxport)." --rerequest_interval ".escapeshellarg($rerequest)." --super_seeder ".escapeshellarg($superseeder)." --crypto_allowed ".escapeshellarg($crypto_allowed)." --crypto_only ".escapeshellarg($crypto_only)." --crypto_stealth ".escapeshellarg($crypto_stealth);

            if(file_exists($cfg["torrent_file_path"].$alias.".prio")) {
                $priolist = explode(',',file_get_contents($cfg["torrent_file_path"].$alias.".prio"));
                $priolist = implode(',',array_slice($priolist,1,$priolist[0]));
                $command .= " --priority ".escapeshellarg($priolist);
            }
            if ($cfg["cmd_options"])
            	$command .= " ".escapeshellarg($cfg["cmd_options"]);
            
            $command .= " > /dev/null &";

            if ($cfg["AllowQueing"] && $queue == "1")
            {
                //  This file is being queued.
            }
            else
            {
                // This flie is being started manually.

                if (! array_key_exists("pythonCmd", $cfg))
                {
                    insertSetting("pythonCmd","/usr/bin/python");
                }

                if (! array_key_exists("debugTorrents", $cfg))
                {
                    insertSetting("debugTorrents", "0");
                }

                if (!$cfg["debugTorrents"])
                {
                    $pyCmd = escapeshellarg($cfg["pythonCmd"]) . " -OO";
                }else{
                    $pyCmd = escapeshellarg($cfg["pythonCmd"]);
                }

                $command = "cd " . $cfg["path"] . $owner . "; HOME=".$cfg["path"]."; export HOME; nohup " . $pyCmd . " " .escapeshellarg($cfg["btphpbin"]) . " " . $command;
            }

        }
        else
        {
            // Must be using the Original BitTorrent Client
            // This is now being required to allow Queing functionality
            //$command = "cd " . $cfg["path"] . $owner . "; nohup " . $cfg["btphpbin"] . " ".$runtime." ".$sharekill." ".$cfg["torrent_file_path"].$alias.".stat ".$owner." --responsefile \"".$cfg["torrent_file_path"].$torrent."\" --display_interval 5 --max_download_rate ". $drate ." --max_upload_rate ".$rate." --max_uploads ".$maxuploads." --minport ".$minport." --maxport ".$maxport." --rerequest_interval ".$rerequest." ".$cfg["cmd_options"]." > /dev/null &";
            $messages .= "<strong>Error</strong> BitTornado is only supported Client at this time.<br>";
        }

        // write the session to close so older version of PHP will not hang
        session_write_close();

        if($af->running == "3")
        {
            writeQinfo($cfg["torrent_file_path"]."queue/".$alias.".stat",$command);
            AuditAction($cfg["constants"]["queued_torrent"], $torrent."<br>Die:".$runtime.", Sharekill:".$sharekill.", MaxUploads:".$maxuploads.", DownRate:".$drate.", UploadRate:".$rate.", Ports:".$minport."-".$maxport.", SuperSeed:".$superseeder.", Rerequest Interval:".$rerequest);
            AuditAction($cfg["constants"]["queued_torrent"], $command);
        }
        else
        {
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
                <script  language="JavaScript">
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
        if (is_file($cfg["torrent_file_path"].$file_name))
        {
            // Error
            $messages .= "<strong>Error</strong> with (<b>".htmlentities($file_name)."</b>), the file already exists on the server.<br><center><a href=\"".$_SERVER['PHP_SELF']."\">[Refresh]</a></center>";
            $ext_msg = "DUPLICATE :: ";
        }
        else
        {
            // open a file to write to
            $fw = fopen($cfg["torrent_file_path"].$file_name,'w');
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
            if (is_file($cfg["torrent_file_path"].$file_name))
            {
                // Error
                $messages .= "<strong>Error</strong> with (<b>".htmlentities($file_name)."</b>), the file already exists on the server.<br><center><a href=\"".$_SERVER['PHP_SELF']."\">[Refresh]</a></center>";
                $ext_msg = "DUPLICATE :: ";
            }
            else
            {
                if(move_uploaded_file($_FILES['upload_file']['tmp_name'], $cfg["torrent_file_path"].$file_name))
                {
                    chmod($cfg["torrent_file_path"].$file_name, 0644);

                    AuditAction($cfg["constants"]["file_upload"], $file_name);

                    header("location: index.php");
                }
                else
                {
                    $messages .= "<font color=\"#ff0000\" size=3>ERROR: File not uploaded, file could not be found or could not be moved:<br>".$cfg["torrent_file_path"] . htmlentities($file_name)."</font><br>";
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
        @unlink($cfg["torrent_file_path"].$delfile);
        @unlink($cfg["torrent_file_path"].$alias_file);
        // try to remove the QInfo if in case it was queued.
        @unlink($cfg["torrent_file_path"]."queue/".$alias_file.".Qinfo");

        // try to remove the pid file
        @unlink($cfg["torrent_file_path"].$alias_file.".pid");
        @unlink($cfg["torrent_file_path"].getAliasName($delfile).".prio");

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
    $af = new AliasFile($cfg["torrent_file_path"].$alias_file, $the_user);
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
    if (!is_file($cfg["torrent_file_path"].$alias_file.".pid"))
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
        @unlink($cfg["torrent_file_path"].$alias_file.".pid");
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
    if (file_exists($cfg["torrent_file_path"]."queue/".$alias_file.".Qinfo"))
    {
        // Yes, then delete it and update the stat file.
        include_once("AliasFile.php");
        // We are going to write a '2' on the front of the stat file so that
        // it will be set back to New Status
        $the_user = getOwner($QEntry);
        // read the alias file
        // create AliasFile object
        $af = new AliasFile($cfg["torrent_file_path"].$alias_file, $the_user);

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
        @unlink($cfg["torrent_file_path"]."queue/".$alias_file.".Qinfo");

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

$drivespace = getDriveSpace($cfg["path"]);


/************************************************************

************************************************************/
?>
<!doctype html>
<html>
<head>
    <title><?php echo $cfg["pagetitle"] ?></title>
    <link rel="icon" href="images/favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
	<link rel="stylesheet" href="./plugins/twitter/bootstrap/dist/css/bootstrap.min.css" type="text/css" />
	<link rel="styleSheet" href="themes/<?php echo $cfg["theme"] ?>/style.css" type="text/css">
	
	<script src="./plugins/components/jquery/jquery.min.js"></script>
	<script src="./plugins/twitter/bootstrap/dist/js/bootstrap.min.js"></script>   
    
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <META HTTP-EQUIV="Pragma" CONTENT="no-cache" charset="<?php echo _CHARSET ?>">
<?php
    if(!isset($_SESSION['prefresh']) || ($_SESSION['prefresh'] == true))
    {
        echo "<meta http-equiv=\"REFRESH\" content=\"".$cfg["page_refresh"].";URL=index.php\">";
?>
<script language="JavaScript">
    var var_refresh = <?php echo $cfg["page_refresh"] ?>;
    function UpdateRefresh() {
        span_refresh.innerHTML = String(var_refresh--);
        setTimeout("UpdateRefresh();", 1000);
    }
</script>
<?php
    }
?>
<div id="overDiv" style="position:absolute;visibility:hidden;z-index:1000;"></div>
<script language="JavaScript">
    var ol_closeclick = "1";
    var ol_close = "<font color=#ffffff><b>X</b></font>";
    var ol_fgclass = "fg";
    var ol_bgclass = "bg";
    var ol_captionfontclass = "overCaption";
    var ol_closefontclass = "overClose";
    var ol_textfontclass = "overBody";
    var ol_cap = "&nbsp;Torrent Status";
</script>
<script src="overlib.js" type="text/javascript"></script>
<script language="JavaScript">
function ShowDetails(name_file)
{
  window.open (name_file,'_blank','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=430,height=225')
}
function StartTorrent(name_file)
{
    myWindow = window.open (name_file,'_blank','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=700,height=530')
}
function ConfirmDelete(file)
{
    return confirm("<?php echo _ABOUTTODELETE ?>: " + file)
}
</script>
</head>
<style>
.bd-example {
	margin-left: 0;
	margin-right: 0;
	margin-bottom: 0;
	padding: 1.5rem;
	border-width: .2rem;
	position: relative;
	padding: 1rem;
	margin: 1rem -1rem;
	border: solid white;
}
</style>
<body onLoad="UpdateRefresh();" topmargin="8" bgcolor="<?php echo $cfg["main_bgcolor"] ?>">

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

<div class="container">
	<div class="row">
		<nav class="navbar navbar-light " style="background-color: #e3f2fd;">
			<?php include_once 'menu.php' ?>
			
			<div class="col-sm-12 nav navbar-nav" style="margin-left:16px;">
				<a class="nav-item nav-link" href="readrss.php"><small>RSS Torrents</small></a>
				<a class="nav-item nav-link" href="drivespace.php"><small><?php echo _DRIVESPACE ?></small></a>
				<a class="nav-item nav-link" href="who.php"><small><?php echo _SERVERSTATS ?></small></a>
				<a class="nav-item nav-link" href="all_services.php"><small><?php echo _ALL ?></small></a>
				<a class="nav-item nav-link" href="dir.php"><small><?php echo _DIRECTORYLIST ?></small></a>
				<a class="nav-item nav-link" href="dir.php?dir=<?php echo $cfg["user"] ?>"><small>My Directory</small></a>
			</div>
		</nav>
	</div>
</div>

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
		<div class="col-sm-6">
		 
			<fieldset class="form-group bd-example" style="margin-right:-10px">
				<form name="form_file" action="index.php" method="post" enctype="multipart/form-data">
	    			<label for="upload_file"><?php echo _SELECTFILE ?></label>
   	 				<input type="file" name="upload_file" id="upload_file" class="form-control" />
   		 			<input type="submit" value="<?php echo _UPLOAD ?>" class="btn btn-primary pull-right" style="margin-top:6px;" />
    			</form>
  			</fieldset>
  			
  			<fieldset class="form-group bd-example" style="margin-right:-10px">
  				<form name="form_url" action="index.php" method="post">
    				<label for="url_upload"><?php echo _URLFILE ?></label>
    				<input type="text" name="url_upload" id="url_upload" class="form-control" />
    				<input type="submit" value="<?php echo _UPLOAD ?>" class="btn btn-primary pull-right" style="margin-top:6px;" />
    			</form>
  			</fieldset>
  			
  			<?php if ($cfg["enable_search"]) { ?>
			<fieldset class="form-group bd-example" style="margin-right:-10px">
				<form name="form_search" action="torrentSearch.php" method="get">
    				<label for="searchterm">Torrent <?php echo _SEARCH ?></label>
    				<input type="text" name="searchterm" id="searchterm" class="form-control" />
    				<?php echo buildSearchEngineDDL($cfg["searchEngine"]) ?>
    				<input type="submit" value="<?php echo _SEARCH ?>" class="btn btn-primary pull-right" style="margin-top:6px;" />
    			</form>
  			</fieldset>
  			<?php } ?>

		</div>
		<div class="col-sm-6">
		<?php 
			$arUsers = GetUsers();
			$arOnlineUsers = array();
			$arOfflineUsers = array();

        for($inx = 0; $inx < count($arUsers); $inx++)
        {
            if(IsOnline($arUsers[$inx]))
            {
                array_push($arOnlineUsers, $arUsers[$inx]);
            }
            else
            {
                array_push($arOfflineUsers, $arUsers[$inx]);
            }
        }
		
		?>
  			<fieldset class="form-group bd-example" style="margin-left:-10px">
  				<table class="table table-striped">
  				<thead>
    				<tr>
      					<th style="color:#5CB85C"><?php echo _ONLINE ?></th>
				    </tr>
  				</thead>
  				<tbody>
  				<?php for($inx = 0; $inx < count($arOnlineUsers); $inx++) { ?>
	                <tr>
						<td scope="row">
							<a href="message.php?to_user=<?php echo $arOnlineUsers[$inx] ?>">
								<?php echo $arOnlineUsers[$inx] ?>
							</a>
						</td>
					</tr>
    
    			<?php } ?>
				</tbody>
				</table>
				
				<table class="table table-striped">
  				<thead>
    				<tr>
      					<th style="color:#D9534F"><?php echo _OFFLINE ?></th>
				    </tr>
  				</thead>
  				<tbody>
  				<?php for($inx = 0; $inx < count($arOfflineUsers); $inx++) { ?>
	                <tr>
						<td scope="row">
							<a href="message.php?to_user=<?php echo $arOfflineUsers[$inx] ?>">
								<?php echo $arOfflineUsers[$inx] ?>
							</a>
						</td>
					</tr>
    
    			<?php } ?>
				</tbody>
				</table>
			</fieldset>
			
  			<fieldset class="form-group bd-example" style="margin-left:-10px">
  				<table class="table table-striped">
  				<thead>
    				<tr>
      					<th><?php echo _TORRENTLINKS ?></th>
				    </tr>
  				</thead>
  				<tbody>
  				<?php
  					$arLinks = GetLinks();
        			if (is_array($arLinks)) {
            			foreach($arLinks as $link) { 
            		?>
					<tr>
						<td scope="row">
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
		<div class="col-sm-12">
			<?php displayDriveSpaceBar($drivespace); ?>
		</div>
	</div>
</div>

<div class="container">
	<div class="row">
		<div class="col-sm-12">
  			<fieldset class="form-group bd-example">
				<?php getDirList($cfg["torrent_file_path"]); ?>
				
			    <table class="table table-striped">
   					<tr>
       					<td><img src="images/properties.png" width="18" height="13" title="<?php echo _TORRENTDETAILS ?>"></td>
       					<td class="tiny"><?php echo _TORRENTDETAILS ?>&nbsp;&nbsp;&nbsp;</td>
       					<td><img src="images/run_on.gif" width="16" height="16" title="<?php echo _RUNTORRENT ?>"></td>
       					<td class="tiny"><?php echo _RUNTORRENT ?>&nbsp;&nbsp;&nbsp;</td>
       					<td><img src="images/kill.gif" width="16" height="16" title="<?php echo _STOPDOWNLOAD ?>"></td>
       					<td class="tiny"><?php echo _STOPDOWNLOAD ?>&nbsp;&nbsp;&nbsp;</td>
       					<?php if ($cfg["AllowQueing"]) { ?>
       						<td><img src="images/queued.gif" width="16" height="16" title="<?php echo _DELQUEUE ?>"></td>
       						<td class="tiny"><?php echo _DELQUEUE ?>&nbsp;&nbsp;&nbsp;</td>
       					<?php } ?>
       					<td><img src="images/seed_on.gif" width="16" height="16" title="<?php echo _SEEDTORRENT ?>"></td>
       					<td class="tiny"><?php echo _SEEDTORRENT ?>&nbsp;&nbsp;&nbsp;</td>
       					<td><img src="images/delete_on.gif" width="16" height="16" title="<?php echo _DELETE ?>"></td>
       					<td class="tiny"><?php echo _DELETE ?></td>
       					<?php if ($cfg["enable_torrent_download"]) { ?>
       						<td>&nbsp;&nbsp;&nbsp;<img src="images/down.gif" width="9" height="9" title="Download Torrent meta file"></td>
       						<td class="tiny">Download Torrent</td>
       					<?php } ?>
   					</tr>
   				</table>
    
				<table class="table table-striped">
    				<tr>
        				<td width="33%">
            				<div class="tiny">
								<?php
    							if(checkQManager() > 0) {
         							echo "<img src=\"images/green.gif\" align=\"absmiddle\" title=\"Queue Manager Running\" align=\"absmiddle\"> Queue Manager Running<br>";
         							echo "<strong>".strval(getRunningTorrentCount())."</strong> torrent(s) running and <strong>".strval(getNumberOfQueuedTorrents())."</strong> queued.<br>";
         							echo "Total torrents server will run: <strong>".$cfg["maxServerThreads"]."</strong><br>";
         							echo "Total torrents a user may run: <strong>".$cfg["maxUserThreads"]."</strong><br>";
         							echo "* Torrents are queued when limits are met.<br>";
    							} else {
        							echo "<img src=\"images/black.gif\" title=\"Queue Manager Off\" align=\"absmiddle\"> Queue Manager Off<br><br>";
    							}
								?>
            				</div>
        				</td>
        				<td width="33%" valign="bottom">
            				<div align="center" class="tiny">
								<?php
							    if(!isset($_SESSION['prefresh']) || ($_SESSION['prefresh'] == true)) {
        							echo "*** "._PAGEWILLREFRESH." <span id='span_refresh'>".$cfg["page_refresh"]."</span> "._SECONDS." ***<br>";
        							echo "<a href=\"".$_SERVER['PHP_SELF']."?pagerefresh=false\"><font class=\"tiny\">"._TURNOFFREFRESH."</font></a>";
    							} else {
        							echo "<a href=\"".$_SERVER['PHP_SELF']."?pagerefresh=true\"><font class=\"tiny\">"._TURNONREFRESH."</font></a>";
    							}

    							if($drivespace >= 98) {
							        echo "\n\n<script  language=\"JavaScript\">\n alert(\""._WARNING.": ".$drivespace."% "._DRIVESPACEUSED."\")\n </script>";
    							}

    							if (!array_key_exists("total_download",$cfg)) $cfg["total_download"] = 0;
    							if (!array_key_exists("total_upload",$cfg)) $cfg["total_upload"] = 0;
								?>
            				</div>
        				</td>
        				<td valign="top" width="33%" align="right">

            			<table>
            				<tr>
                				<td class="tiny" align="right"><?php echo _CURRENTDOWNLOAD ?>:</td>
                				<td class="tiny"><strong><?php echo number_format($cfg["total_download"], 2); ?></strong> kB/s</td>
            				</tr>
            				<tr>
                				<td class="tiny" align="right"><?php echo _CURRENTUPLOAD ?>:</td>
                				<td class="tiny"><strong><?php echo number_format($cfg["total_upload"], 2); ?></strong> kB/s</td>
            				</tr>
            				<tr>
                				<td class="tiny" align="right"><?php echo _FREESPACE ?>:</td>
                				<td class="tiny"><strong><?php echo formatFreeSpace($cfg["free_space"]) ?></strong></td>
            				</tr>
            				<tr>
                				<td class="tiny" align="right"><?php echo _SERVERLOAD ?>:</td>
                				<td class="tiny">
        							<?php
            						if ($cfg["show_server_load"] && @isFile($cfg["loadavg_path"])) {
                						$loadavg_array = explode(" ", exec("cat ".escapeshellarg($cfg["loadavg_path"])));
                						$loadavg = $loadavg_array[2];
                						echo "<strong>".$loadavg."</strong>";
            						} else {
            	    					echo "<strong>n/a</strong>";
            						}
        							?>
                				</td>
            				</tr>
            			</table>

        				</td>
    				</tr>
    			</table>
			</fieldset>
		</div>
	</div>
</div>
 
<?php
    echo DisplayTorrentFluxLink();
    // At this point Any User actions should have taken place
    // Check to see if the user has a force_read message from an admin
    if (IsForceReadMsg())
    {
        // Yes, then warn them
?>
        <script  language="JavaScript">
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