<?php

    include_once '../Class/autoload.php';
    include_once '../config.php';
    
    $settings = new Gratbrav\Torrentbug\Settings();

    include_once '../functions.php';
    
    if (!IsAdmin()) {
        // the user probably hit this page direct
        AuditAction($cfg["constants"]["access_denied"], $_SERVER['PHP_SELF']);
        header("location: ../index.php");
    }
    
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    
    if ($action == 'update_settings') {
        if (!array_key_exists("debugTorrents", $_REQUEST)) {
            $_REQUEST["debugTorrents"] = false;
        }
    
        $tmpPath = getRequestVar("path");
    
        if (!empty($tmpPath) && substr( $tmpPath, -1 )  != "/") {
            // path requires a / on the end
            $_POST["path"] = $_POST["path"] . "/";
        }
    
        if ((array_key_exists("AllowQueing",$_POST) && $_POST["AllowQueing"] != $settings->get('AllowQueing')) ||
            (array_key_exists("maxServerThreads",$_POST) && $_POST["maxServerThreads"] != $settings->get('maxServerThreads')) ||
            (array_key_exists("maxUserThreads",$_POST) && $_POST["maxUserThreads"] != $settings->get('maxUserThreads')) ||
            (array_key_exists("sleepInterval",$_POST) && $_POST["sleepInterval"] != $settings->get('sleepInterval')) ||
            (array_key_exists("debugTorrents",$_POST) && $_POST["debugTorrents"] != $settings->get('debugTorrents')) ||
            (array_key_exists("tfQManager",$_POST) && $_POST["tfQManager"] != $settings->get('tfQManager')) ||
            (array_key_exists("btphpbin",$_POST) && $_POST["btphpbin"] != $settings->get('btphpbin'))
        ) {
            // kill QManager process;
            if(getQManagerPID() != "") {
                stopQManager();
            }

            $options = $_POST;
            unset($options['action']);

            $settings->save($options);
            AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Settings");

            // if enabling Start QManager
            if($settings->get('AllowQueing')) {
                sleep(2);
                startQManager($settings->get('maxServerThreads'), $settings->get('maxUserThreads'), $settings->get('sleepInterval'));
                sleep(1);
            }
        } else {
            $options = $_POST;
            unset($options['action']);

            $settings->save($options);
            AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Settings");
        }

        $continue = getRequestVar('continue');
        header("Location: queue.php");
        exit;
    }
    
    $subMenu = 'admin';
    include_once '../header.php';
    
    include_once '../AliasFile.php';
    include_once '../RunningTorrent.php';
    
    //****************************************************************************
    // validateFile -- Validates the existance of a file and returns the status image
    //****************************************************************************
    function validateFile($file)
    {
        $msg = '<img src="../images/red.gif" alt="" title="Path is not Valid"><br><font color="#ff0000">Path is not Valid</font>';
        if (isFile($file)) {
            $msg = '<img src="../images/green.gif" alt="" title="Valid">';
        }
        return $msg;
    }
?>
<div class="container">
	<div class="row">
		<div class="col-sm-12 bd-example">
            <form name="theForm" action="queue.php" method="post" onsubmit="return validateSettings()">
	            <input type="hidden" name="action" value="update_settings">
		
		        <table class="table table-striped">
    				<tr>
    					<th colspan="2">
    						<?php
                                if (checkQManager() > 0) {
                                    echo "&nbsp;&nbsp;<img src=\"../images/green.gif\" alt=\"\"> Queue Manager Running [PID=".getQManagerPID()." with ".strval(getRunningTorrentCount())." torrent(s)]";
                                } else {
                                    echo "&nbsp;&nbsp;<img src=\"../images/black.gif\" alt=\"\"> Queue Manager Off";
                                }
                             ?>
    					</th>
    				</tr>
                <tr>
                    <td style="text-align:left;width:50%"><strong>Enable Queue Manager</strong><br>
                    Enable the Queue Manager to allow users to queue torrents:
                    </td>
                    <td>
                        <select name="AllowQueing" class="form-control">
                            <option value="1">true</option>
                            <option value="0" <?php echo (!$settings->get('AllowQueing')) ? 'selected' : '' ?>>false</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="text-align:left;width:350"><strong>tfQManager Path</strong><br>
                    Specify the path to the tfQManager python script:
                    </td>
                    <td>
                        <input name="tfQManager" type="Text" maxlength="254" value="<?php echo ($settings->get('tfQManager')); ?>" class="form-control"><?php echo validateFile($settings->get('tfQManager')) ?>
                    </td>
                </tr>
    <?php /* Only used for develpment or if you really really know what you are doing
                <tr>
                    <td style="text-align:left;width:350"><strong>Enable Queue Manager Debugging</strong><br>
                    Creates huge log files only for debugging.  DO NOT KEEP THIS MODE ON:
                    </td>
                    <td>
                        <select name="debugTorrents" class="form-control">
                            <option value="1">true</option>
                            <option value="0" <?php
                            if ($settings->get('debugTorrents') !== null) {
    			                if (!$settings->get('debugTorrents')) {
                        			echo "selected";
                    			}
                			} else {
                				$settings->save(array('debugTorrents' => false));
    			                echo "selected";
                			}
                            ?>>false</option>
                        </select>
                    </td>
                </tr>
    */ ?>
                <tr>
                    <td style="text-align:left;width:350"><strong>Max Server Threads</strong><br>
                    Specify the maximum number of torrents the server will allow to run at
                    one time (admins may override this):
                    </td>
                    <td>
                        <input name="maxServerThreads" type="Text" maxlength="3" value="<?php echo $settings->get('maxServerThreads') ?>" class="form-control">
                    </td>
                </tr>
                <tr>
                    <td style="text-align:left;width:350"><strong>Max User Threads</strong><br>
                    Specify the maximum number of torrents a single user may run at
                    one time:
                    </td>
                    <td>
                        <input name="maxUserThreads" type="Text" maxlength="3" value="<?php echo $settings->get('maxUserThreads') ?>" class="form-control">
                    </td>
                </tr>
                <tr>
                    <td style="text-align:left;width:350"><strong>Polling Interval</strong><br>
                    Number of seconds the Queue Manager will sleep before checking for new torrents to run:
                    </td>
                    <td>
                        <input name="sleepInterval" type="Text" maxlength="3" value="<?php echo $settings->get('sleepInterval') ?>" class="form-control">
                    </td>
                </tr>
                <tr>
                    <td style="text-align:center" colspan="2">
                    <input type="Submit" value="Update Settings" class="btn btn-primary">
                    </td>
                </tr>
            	</table>
			</form>
		</div>
	</div>
</div>

<?php
    $displayQueue = true;
    $displayRunningTorrents = true;

    // Its a timming thing.
    if ($displayRunningTorrents) {
          // get Running Torrents.
        $runningTorrents = getRunningTorrents();
    }

    if ($displayQueue) {
?>
<div class="container">
	<div class="row">
		<div class="col-sm-12 bd-example">
			<table class="table table-striped">
          		<tr>
          			<th colspan="3">
          				<img src="../images/properties.png" alt="">&nbsp;&nbsp;
          				Queued Items
          			</th>
        		</tr>
        		<tr>
        			<th style="width:15%"><?php echo _USER ?></th>
        			<th><?php echo _FILE ?></th>
        			<th style="width:15%"><?php echo _TIMESTAMP ?></th>
        		</tr>
				<?php
                    $qDir = $settings->get('torrent_file_path') . "queue/";
                    $output = "";
                    if (is_dir($settings->get('torrent_file_path'))) {
                        if (is_writable($settings->get('torrent_file_path')) && !is_dir($qDir)) {
                            @mkdir($qDir, 0777);
                        }
            
                        // get Queued Items and List them out.
                        
                        $handle = @opendir($qDir);
                        while($filename = readdir($handle))
                        {
                            if ($filename != "tfQManager.log")
                            {
                                if ($filename != "." && $filename != ".." && strpos($filename,".pid") == 0)
                                {
                                $output .= "<tr>";
                                $output .= "<td><div class=\"tiny\">";
                                $af = new AliasFile(str_replace("queue/","",$qDir).str_replace(".Qinfo","",$filename), "");
                                $output .= $af->torrentowner;
                                $output .= "</div></td>";
                                $output .= "<td><div style=\"text-align:center\"><div class=\"tiny\" style=\"text-align:left\">".str_replace(array(".Qinfo",".stat"),"",$filename)."</div></td>";
                                $output .= "<td><div class=\"tiny\" style=\"text-align:center\">".date(_DATETIMEFORMAT, strval(filectime($qDir.$filename)))."</div></td>";
                                $output .= "</tr>";
                                $output .= "\n";
                                }
                            }
                        }
                        closedir($handle);
                    }
            
                    if ( strlen($output) == 0 ) {
                        $output = "<tr><td colspan=3><div class=\"tiny\" style=\"text-align:center\">Queue is Empty</div></td></tr>";
                    }
                    echo $output;
                ?>
        	</table>
		</div>
	</div>
</div>
<?php } ?>

<?php
    if ($displayRunningTorrents) {
?>
<div class="container">
	<div class="row">
		<div class="col-sm-12 bd-example">
            <table class="table table-striped">
            	<tr>
            		<th colspan="3">
            			<img src="../images/properties.png" alt="">&nbsp;&nbsp;
            			Running Items
            		</th>
    			<tr>
            		<th style="width:15%"><?php echo _USER ?></td>
            		<th><?php echo _FILE ?></td>
            		<th style="width:1%"><?php echo str_replace(" ","<br>",_FORCESTOP) ?></td>
            	</tr>
    			<?php
                    // get running torrents and List them out.
                    $runningTorrents = getRunningTorrents();
                    if (is_array($runningTorrents)) {
                        foreach ($runningTorrents as $key => $value) {
                            $rt = new RunningTorrent($value);
                            $output .= $rt->BuildAdminOutput();
                        }
                    }
                    
                    if (strlen($output) == 0) {
                        $output = "<tr><td colspan=3><div class=\"tiny\" style=\"text-align:center\">No Running Torrents</div></td></tr>";
                    }
                    echo $output;
                ?>
        	</table>		
		</div>
	</div>
</div>
<?php } ?>


<script>
    function validateSettings()
    {
        var rtnValue = true;
        var msg = "";
        if (isNumber(document.theForm.maxServerThreads.value) == false)
        {
            msg = msg + "* Max Server Threads must be a valid number.\n";
            document.theForm.maxServerThreads.focus();
        }
        if (isNumber(document.theForm.maxUserThreads.value) == false)
        {
            msg = msg + "* Max User Threads must be a valid number.\n";
            document.theForm.maxUserThreads.focus();
        }
        if (isNumber(document.theForm.sleepInterval.value) == false)
        {
            msg = msg + "* Sleep Interval must be a valid number.\n";
            document.theForm.sleepInterval.focus();
        }

        if (msg != "")
        {
            rtnValue = false;
            alert("Please check the following:\n\n" + msg);
        }

        return rtnValue;
    }

    function isNumber(sText)
    {
        var ValidChars = "0123456789.";
        var IsNumber = true;
        var Char;

        for (i = 0; i < sText.length && IsNumber == true; i++)
        {
            Char = sText.charAt(i);
            if (ValidChars.indexOf(Char) == -1)
            {
                IsNumber = false;
            }
        }

        return IsNumber;
    }
</script>
