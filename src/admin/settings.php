<?php

    include_once '../Class/autoload.php';
    include_once '../config.php';
    
    $settings = new Class_Settings();
    
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
        header("Location: settings.php");
        exit;
    }
    
    $subMenu = 'admin';
    include_once '../header.php';
    include_once '../AliasFile.php';
    include_once '../RunningTorrent.php';


    //****************************************************************************
    // validatePath -- Validates TF Path and Permissions
    //****************************************************************************
    function validatePath($path)
    {
        $msg = "<img src=\"../images/red.gif\" alt=\"\" title=\"Path is not Valid\"><br><font color=\"#ff0000\">Path is not Valid</font>";
        if (is_dir($path))
        {
            if (is_writable($path))
            {
                $msg = "<img src=\"../images/green.gif\" alt=\"\" title=\"Valid\">";
            }
            else
            {
                $msg = "<img src=\"../images/red.gif\" alt=\"\" title=\"Path is not Writable\"><br><font color=\"#ff0000\">Path is not Writable -- make sure you chmod +w this path</font>";
            }
        }
        return $msg;
    }
    
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
			<form name="theForm" action="settings.php" method="post" onsubmit="return validateSettings()">
				<input type="hidden" name="action" value="update_settings">
		
	    			<table class="table table-striped">
	    				<tr>
	    					<th colspan="2">
	    						<img src="../images/properties.png" alt="">&nbsp;&nbsp;
	    						TorrentFlux Settings
	    					</th>
	    				</tr>
				        <tr>
				            <td style="width:50%"><strong>Path</strong><br>
				            Define the PATH where the downloads will go <br>(make sure it ends with a / [slash]).
				            It must be chmod'd to 777:
				            </td>
				            <td>
				                <input name="path" type="Text" maxlength="254" value="<?php echo($settings->get('path')); ?>" class="form-control"><?php echo validatePath($settings->get('path')); ?>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Python Path</strong><br>
				            Specify the path to the Python binary (usually /usr/bin/python or /usr/local/bin/python):
				            </td>
				            <td>
				                <input name="pythonCmd" type="Text" maxlength="254" value="<?php echo($settings->get('pythonCmd')); ?>" class="form-control"><?php echo validateFile($settings->get('pythonCmd')); ?>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>btphptornado Path</strong><br>
				            Specify the path to the btphptornado python script:
				            </td>
				            <td>
				                <input name="btphpbin" type="Text" maxlength="254" value="<?php echo($settings->get('btphpbin')); ?>" class="form-control"><?php echo validateFile($settings->get('btphpbin')); ?>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>btshowmetainfo Path</strong><br>
				            Specify the path to the btshowmetainfo python script:
				            </td>
				            <td>
				                <input name="btshowmetainfo" type="Text" maxlength="254" value="<?php echo($settings->get('btshowmetainfo')); ?>" class="form-control"><?php echo validateFile($settings->get('btshowmetainfo')); ?>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Use Advanced Start Dialog</strong><br>
				            When enabled, users will be given the advanced start dialog popup when starting a torrent:
				            </td>
				            <td>
				                <select name="advanced_start" class="form-control">
				                    <option value="1">true</option>
				                    <option value="0" <?php echo (!$settings->get('advanced_start')) ? 'selected': ''; ?>>false</option>
				                </select>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Enable File Priority</strong><br>
				            When enabled, users will be allowed to select particular files from the torrent to download:
				            </td>
				            <td>
				                <select name="enable_file_priority" class="form-control">
			                        <option value="1">true</option>
			                        <option value="0" <?php echo (!$settings->get('enable_file_priority')) ? 'selected' : '' ?>>false</option>
				                </select>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Max Upload Rate</strong><br>
				            Set the default value for the max upload rate per torrent:
				            </td>
				            <td>
				                <input name="max_upload_rate" type="Text" maxlength="5" value="<?php echo($settings->get('max_upload_rate')); ?>" class="form-control"> KB/second
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Max Download Rate</strong><br>
				            Set the default value for the max download rate per torrent (0 for no limit):
				            </td>
				            <td>
				                <input name="max_download_rate" type="Text" maxlength="5" value="<?php    echo($settings->get('max_download_rate')); ?>" class="form-control"> KB/second
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Max Upload Connections</strong><br>
				            Set the default value for the max number of upload connections per torrent:
				            </td>
				            <td>
				                <input name="max_uploads" type="Text" maxlength="5" value="<?php    echo($settings->get('max_uploads')); ?>" class="form-control">
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Port Range</strong><br>
				            Set the default values for the for port range (Min - Max):
				            </td>
				            <td>
				                <input name="minport" type="Text" maxlength="5" value="<?php    echo($settings->get('minport')); ?>" class="form-control"> -
				                <input name="maxport" type="Text" maxlength="5" value="<?php    echo($settings->get('maxport')); ?>" class="form-control">
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Rerequest Interval</strong><br>
				            Set the default value for the rerequest interval to the tracker (default 1800 seconds):
				            </td>
				            <td>
				                <input name="rerequest_interval" type="Text" maxlength="5" value="<?php    echo($settings->get('rerequest_interval')); ?>" class="form-control">
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Allow encrypted connections</strong><br>
				            Check to allow the client to accept encrypted connections.
				            </td>
				            <td>
				                <select name="crypto_allowed" class="form-control">
			                        <option value="1">true</option>
			                        <option value="0" <?php echo (!$settings->get('crypto_allowed')) ? 'selected' : '' ?>>false</option>
				                </select>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Only allow encrypted connections</strong><br>
				            Check to force the client to only create and accept encrypted connections.
				            </td>
				            <td>
				                <select name="crypto_only" class="form-control">
			                        <option value="1">true</option>
			                        <option value="0" <?php echo (!$settings->get('crypto_only')) ? 'selected' : '' ?>>false</option>
				                </select>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Stealth crypto</strong><br>
					    	Prevent all non-encrypted connection attempts.  (Note: will result in an effectively firewalled state on older trackers.)
				            <td>
				                <select name="crypto_stealth" class="form-control">
			                        <option value="1">true</option>
			                        <option value="0" <?php echo (!$settings->get('crypto_stealth')) ? 'selected' : '' ?>>false</option>
				                </select>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Extra BitTornado Commandline Options</strong><br>
				            DO NOT include --max_upload_rate, --minport, --maxport, --max_uploads, --crypto_allowed, --crypto_only, --crypto_stealth here as they are included by TorrentFlux settings above:
				            </td>
				            <td>
				                <input name="cmd_options" type="Text" maxlength="254" value="<?php echo($settings->get('cmd_options')); ?>" class="form-control">
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Enable Torrent Search</strong><br>
				            When enabled, users will be allowed to perform torrent searches from the home page:
				            </td>
				            <td>
				                <select name="enable_search" class="form-control">
				                        <option value="1">true</option>
				                        <option value="0" <?php
				                        if (!$settings->get('enable_search'))
				                        {
				                            echo "selected";
				                        }
				                        ?>>false</option>
				                </select>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Default Torrent Search Engine</strong><br>
				            Select the default search engine for torrent searches:
				            </td>
				            <td>
								<?php echo buildSearchEngineDDL($settings->get('searchEngine')); ?>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Enable Make Torrent</strong><br>
				            When enabled, users will be allowed make torrent files from the directory view:
				            </td>
				            <td>
				                <select name="enable_maketorrent" class="form-control">
				                        <option value="1">true</option>
				                        <option value="0" <?php
				                        if (!$settings->get('enable_maketorrent'))
				                        {
				                            echo "selected";
				                        }
				                        ?>>false</option>
				                </select>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>btmakemetafile.py Path</strong><br>
				            Specify the path to the btmakemetafile.py python script (used for making torrents):
				            </td>
				            <td>
				                <input name="btmakemetafile" type="Text" maxlength="254" value="<?php echo($settings->get('btmakemetafile')); ?>" class="form-control"><?php echo validateFile($settings->get('btmakemetafile')); ?>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Enable Torrent File Download</strong><br>
				            When enabled, users will be allowed download the torrent meta file from the torrent list view:
				            </td>
				            <td>
				                <select name="enable_torrent_download" class="form-control">
				                        <option value="1">true</option>
				                        <option value="0" <?php
				                        if (!$settings->get('enable_torrent_download'))
				                        {
				                            echo "selected";
				                        }
				                        ?>>false</option>
				                </select>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Enable File Download</strong><br>
				            When enabled, users will be allowed download from the directory view:
				            </td>
				            <td>
				                <select name="enable_file_download" class="form-control">
				                        <option value="1">true</option>
				                        <option value="0" <?php
				                        if (!$settings->get('enable_file_download'))
				                        {
				                            echo "selected";
				                        }
				                        ?>>false</option>
				                </select>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Enable Text/NFO Viewer</strong><br>
				            When enabled, users will be allowed to view Text/NFO files from the directory listing:
				            </td>
				            <td>
				                <select name="enable_view_nfo" class="form-control">
				                        <option value="1">true</option>
				                        <option value="0" <?php
				                        if (!$settings->get('enable_view_nfo'))
				                        {
				                            echo "selected";
				                        }
				                        ?>>false</option>
				                </select>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Download Package Type</strong><br>
				            When File Download is enabled, users will be allowed download from the directory view using
				            a packaging system.  Make sure your server supports the package type you select:
				            </td>
				            <td>
				                <select name="package_type" class="form-control">
				                    <option value="tar" <?php echo ($settings->get('package_type') == "tar") ? 'selected' : '' ?>>tar</option>
				                    <option value="zip" <?php echo ($settings->get('package_type') == "zip") ? 'selected' : '' ?>>zip</option>
				                </select>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Show Server Load</strong><br>
				            Enable showing the average server load over the last 15 minutes from <?php echo $settings->get('loadavg_path') ?> file:
				            </td>
				            <td>
				                <select name="show_server_load" class="form-control">
				                        <option value="1">true</option>
				                        <option value="0" <?php
				                        if (!$settings->get('show_server_load'))
				                        {
				                            echo "selected";
				                        }
				                        ?>>false</option>
				                </select>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>loadavg Path</strong><br>
				            Path to the loadavg file:
				            </td>
				            <td>
				                <input name="loadavg_path" type="Text" maxlength="254" value="<?php echo($settings->get('loadavg_path')); ?>" class="form-control"><?php echo validateFile($settings->get('loadavg_path')) ?>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Days to keep Audit Actions in the Log</strong><br>
				            Number of days that audit actions will be held in the database:
				            </td>
				            <td>
				                <input name="days_to_keep" type="Text" maxlength="3" value="<?php echo($settings->get('days_to_keep')); ?>" class="form-control">
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Minutes to Keep User Online Status</strong><br>
				            Number of minutes before a user status changes to offline after leaving TorrentFlux:
				            </td>
				            <td>
				                <input name="minutes_to_keep" type="Text" maxlength="2" value="<?php echo($settings->get('minutes_to_keep')); ?>" class="form-control">
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Minutes to Cache RSS Feeds</strong><br>
				            Number of minutes to cache the RSS XML feed on server (speeds up reload):
				            </td>
				            <td>
				                <input name="rss_cache_min" type="Text" maxlength="3" value="<?php echo($settings->get('rss_cache_min')); ?>" class="form-control">
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Page Refresh (in seconds)</strong><br>
				            Number of seconds before the torrent list page refreshes:
				            </td>
				            <td>
				                <input name="page_refresh" type="Text" maxlength="3" value="<?php echo($settings->get('page_refresh')); ?>" class="form-control">
				            </td>
				        </tr>
						<?php
						    if (!defined("IMG_JPG")) define("IMG_JPG", 2);
						    // Check gd is loaded AND that jpeg image type is supported:
						    if (extension_loaded('gd') && (imagetypes() & IMG_JPG)) {
						?>
						        <tr>
						            <td><strong>Enable Security Code Login</strong><br>
						            Requires users to enter a security code from a generated graphic to login (if enabled automated logins will NOT work):
						            </td>
						            <td>
						                <select name="security_code" disabled class="form-control">
						                        <option value="1">true</option>
						                        <option value="0" <?php
						                            if (!$settings->get('security_code'))
						                            {
						                                echo "selected";
						                            }
						                        ?>>false</option>
						                </select>
						            </td>
						        </tr>
						<?php } ?>
						
				        <tr>
				            <td ><strong>Default Theme</strong><br>
				            Select the default theme that users will have (including login screen):
				            </td>
				            <td>
				                <select name="default_theme" class="form-control">
									<?php
									    $arThemes = GetThemes();
									    for($inx = 0; $inx < sizeof($arThemes); $inx++)
									    {
									        $selected = "";
									        if ($settings->get('default_theme') == $arThemes[$inx])
									        {
									            $selected = "selected";
									        }
									        echo "<option value=\"".$arThemes[$inx]."\" ".$selected.">".$arThemes[$inx]."</option>";
									    }
									?>
				                </select>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Default Language</strong><br>
				            Select the default language that users will have:
				            </td>
				            <td>
				                <select name="default_language" class="form-control">
									<?php
									    $arLanguage = GetLanguages();
									    for($inx = 0; $inx < sizeof($arLanguage); $inx++)
									    {
									        $selected = "";
									        if ($settings->get('default_language') == $arLanguage[$inx])
									        {
									            $selected = "selected";
									        }
									        echo "<option value=\"".$arLanguage[$inx]."\" ".$selected.">".GetLanguageFromFile($arLanguage[$inx])."</option>";
									    }
									?>
				            </select>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Show SQL Debug Statements</strong><br>
				            SQL Errors will always be displayed but when this feature is enabled the SQL Statement
				            that caused the error will be displayed as well:
				            </td>
				            <td>
				                <select name="debug_sql" class="form-control">
				                        <option value="1">true</option>
				                        <option value="0" <?php
				                        if (!$settings->get('debug_sql'))
				                        {
				                            echo "selected";
				                        }
				                        ?>>false</option>
				                </select>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Default Torrent Completion Activity</strong><br>
				            Select whether or not a torrent should keep seeding when download is complete
				            (please seed your torrents):
				            </td>
				            <td>
				                <select name="torrent_dies_when_done" class="form-control">
				                        <option value="True">Die When Done</option>
				                        <option value="False" <?php
				                        if ($settings->get('torrent_dies_when_done') == "False")
				                        {
				                            echo "selected";
				                        }
				                        ?>>Keep Seeding</option>
				                </select>
				            </td>
				        </tr>
				        <tr>
				            <td><strong>Default Percentage When Seeding should Stop</strong><br>
				            Set the default share pecentage where torrents will shutoff
				            when running torrents that do not die when done.
				            Value '0' will seed forever.
				            </td>
				            <td>
				                <input name="sharekill" type="Text" maxlength="3" value="<?php    echo($settings->get('sharekill')); ?>" class="form-control">%
				            </td>
				        </tr>
				    </table>
    
    			<div style="text-align:center">
        			<input type="Submit" value="Update Settings" class="btn btn-primary">
        		</div>
        	</form>
		</div>
	</div>
</div>

<script>
    function validateSettings()
    {
        var rtnValue = true;
        var msg = "";
        if (isNumber(document.theForm.max_upload_rate.value) == false)
        {
            msg = msg + "* Max Upload Rate must be a valid number.\n";
            document.theForm.max_upload_rate.focus();
        }
        if (isNumber(document.theForm.max_download_rate.value) == false)
        {
            msg = msg + "* Max Download Rate must be a valid number.\n";
            document.theForm.max_download_rate.focus();
        }
        if (isNumber(document.theForm.max_uploads.value) == false)
        {
            msg = msg + "* Max # Uploads must be a valid number.\n";
            document.theForm.max_uploads.focus();
        }
        if ((isNumber(document.theForm.minport.value) == false) || (isNumber(document.theForm.maxport.value) == false))
        {
            msg = msg + "* Port Range must have valid numbers.\n";
            document.theForm.minport.focus();
        }
        if (isNumber(document.theForm.rerequest_interval.value) == false)
        {
            msg = msg + "* Rerequest Interval must have a valid number.\n";
            document.theForm.rerequest_interval.focus();
        }
        if (document.theForm.rerequest_interval.value < 10)
        {
            msg = msg + "* Rerequest Interval must 10 or greater.\n";
            document.theForm.rerequest_interval.focus();
        }
        if (isNumber(document.theForm.days_to_keep.value) == false)
        {
            msg = msg + "* Days to keep Audit Actions must be a valid number.\n";
            document.theForm.days_to_keep.focus();
        }
        if (isNumber(document.theForm.minutes_to_keep.value) == false)
        {
            msg = msg + "* Minutes to keep user online must be a valid number.\n";
            document.theForm.minutes_to_keep.focus();
        }
        if (isNumber(document.theForm.rss_cache_min.value) == false)
        {
            msg = msg + "* Minutes to Cache RSS Feeds must be a valid number.\n";
            document.theForm.rss_cache_min.focus();
        }
        if (isNumber(document.theForm.page_refresh.value) == false)
        {
            msg = msg + "* Page Refresh must be a valid number.\n";
            document.theForm.page_refresh.focus();
        }
        if (isNumber(document.theForm.sharekill.value) == false)
        {
            msg = msg + "* Keep seeding until Sharing % must be a valid number.\n";
            document.theForm.sharekill.focus();
        }
        if ((document.theForm.maxport.value > 65535) || (document.theForm.minport.value > 65535))
        {
            msg = msg + "* Port can not be higher than 65535.\n";
            document.theForm.minport.focus();
        }
        if ((document.theForm.maxport.value < 0) || (document.theForm.minport.value < 0))
        {
            msg = msg + "* Can not have a negative number for port value.\n";
            document.theForm.minport.focus();
        }
        if (document.theForm.maxport.value < document.theForm.minport.value)
        {
            msg = msg + "* Port Range is not valid.\n";
            document.theForm.minport.focus();
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
        var ValidChars = "0123456789";
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
