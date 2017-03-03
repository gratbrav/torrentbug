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

    include_once 'Class/autoload.php';
    include_once 'config.php';
    include_once 'functions.php';

    $settings = new Gratbrav\Torrentbug\Settings();

checkUserPath();

// Setup some defaults if they are not set.
$del = getRequestVar('del');
$down = getRequestVar('down');
$tar = getRequestVar('tar');
$dir = stripslashes(urldecode(getRequestVar('dir')));
if (strpos(stripslashes($dir),"../")===false) {} else {echo "Can't go to parent directories!";exit;}

// Are we to delete something?
if ($del != "")
{
    $current = "";
    // The following lines of code were suggested by Jody Steele jmlsteele@stfu.ca
    // this is so only the owner of the file(s) or admin can delete
    if(IsAdmin($cfg["user"]) || preg_match("/^" . $cfg["user"] . "/",$del))
    {
        // Yes, then delete it

        // we need to strip slashes twice in some circumstances
        // Ex.  If we are trying to delete test/tester's file/test.txt
        //    $del will be "test/tester\\\'s file/test.txt"
        //    one strip will give us "test/tester\'s file/test.txt
        //    the second strip will give us the correct
        //        "test/tester's file/test.txt"

        $del = stripslashes(stripslashes($del));

        if (!ereg("(\.\.\/)", $del))
        {
            avddelete($settings->get('path').$del);

            $arTemp = explode("/", $del);
            if (count($arTemp) > 1)
            {
                array_pop($arTemp);
                $current = implode("/", $arTemp);
            }

            $options = [
                'user_id' => $cfg['user'],
                'file' => $del,
                'action' => $cfg["constants"]["fm_delete"],
            ];
            $log = new \Gratbrav\Torrentbug\Log\Service();
            $log->save($options);
        }
        else
        {
            $options = [
                'user_id' => $cfg['user'],
                'file' => 'ILLEGAL DELETE: ' . $cfg['user'] . ' tried to delete ' . $del,
                'action' => $cfg["constants"]["error"],
            ];
            $log = new \Gratbrav\Torrentbug\Log\Service();
            $log->save($options);
        }
    }
    else
    {
        $options = [
            'user_id' => $cfg['user'],
            'file' => 'ILLEGAL DELETE: ' . $cfg['user'] . ' tried to delete ' . $del,
            'action' => $cfg["constants"]["error"],
        ];
        $log = new \Gratbrav\Torrentbug\Log\Service();
        $log->save($options);
    }

    header("Location: dir.php?dir=".urlencode($current));
}

// Are we to download something?
if ($down != "" && $settings->get('enable_file_download')) {
    $current = "";
    // Yes, then download it

    // we need to strip slashes twice in some circumstances
    // Ex.  If we are trying to download test/tester's file/test.txt
    // $down will be "test/tester\\\'s file/test.txt"
    // one strip will give us "test/tester\'s file/test.txt
    // the second strip will give us the correct
    //  "test/tester's file/test.txt"

    $down = stripslashes(stripslashes($down));

    if (!ereg("(\.\.\/)", $down))
    {
        $path = $settings->get('path').$down;

        $p = explode(".", $path);
        $pc = count($p);

        $f = explode("/", $path);
        $file = array_pop($f);
        $arTemp = explode("/", $down);
        if (count($arTemp) > 1)
        {
            array_pop($arTemp);
            $current = implode("/", $arTemp);
        }

        if (file_exists($path))
        {
            header("Content-type: application/octet-stream\n");
            header("Content-disposition: attachment; filename=\"".$file."\"\n");
            header("Content-transfer-encoding: binary\n");
            header("Content-length: " . file_size($path) . "\n");

            // write the session to close so you can continue to browse on the site.
            session_write_close();

            //$fp = fopen($path, "r");
            $fp = popen("cat \"$path\"", "r");
            fpassthru($fp);
            pclose($fp);

            $options = [
                'user_id' => $cfg['user'],
                'file' => $down,
                'action' => $cfg["constants"]["fm_download"],
            ];
            $log = new \Gratbrav\Torrentbug\Log\Service();
            $log->save($options);

            exit();
        }
        else
        {
            $options = [
                'user_id' => $cfg['user'],
                'file' => 'File Not found for download: ' . $cfg['user'] . ' tried to download ' . $down,
                'action' => $cfg["constants"]["error"],
            ];
            $log = new \Gratbrav\Torrentbug\Log\Service();
            $log->save($options);
        }
    }
    else
    {
        $options = [
            'user_id' => $cfg['user'],
            'file' => 'ILLEGAL DOWNLOAD: ' . $cfg['user'] . ' tried to download ' . $down,
            'action' => $cfg["constants"]["error"],
        ];
        $log = new \Gratbrav\Torrentbug\Log\Service();
        $log->save($options);
    }
    header("Location: dir.php?dir=".urlencode($current));
}

// Are we to download something?
if ($tar != "" && $settings->get('enable_file_download')) {
    $current = "";
    // Yes, then tar and download it

    // we need to strip slashes twice in some circumstances
    // Ex.  If we are trying to download test/tester's file/test.txt
    // $down will be "test/tester\\\'s file/test.txt"
    // one strip will give us "test/tester\'s file/test.txt
    // the second strip will give us the correct
    //  "test/tester's file/test.txt"

    $tar = stripslashes(stripslashes($tar));

    if (!ereg("(\.\.\/)", $tar))
    {
        // This prevents the script from getting killed off when running lengthy tar jobs.
        ini_set("max_execution_time", 3600);
        $tar = $settings->get('path').$tar;

        $arTemp = explode("/", $tar);
        if (count($arTemp) > 1)
        {
            array_pop($arTemp);
            $current = implode("/", $arTemp);
        }

        // Find out if we're really trying to access a file within the
        // proper directory structure. Sadly, this way requires that $settings->get('path')
        // is a REAL path, not a symlinked one. Also check if $settings->get('path') is part
        // of the REAL path.
        if (is_dir($tar))
        {
            $sendname = basename($tar);

            switch ($settings->get('package_type'))
            {
                Case "tar":
                    $command = "tar cf - \"".addslashes($sendname)."\"";
                    break;
                Case "zip":
                    $command = "zip -0r - \"".addslashes($sendname)."\"";
                    break;
                default:
                    $settings->get('package_type', 'tar');
                    $command = "tar cf - \"".addslashes($sendname)."\"";
                    break;
            }

            // HTTP/1.0
            header("Pragma: no-cache");
            header("Content-Description: File Transfer");
            header("Content-Type: application/force-download");
            header('Content-Disposition: attachment; filename="'.$sendname.'.'.$settings->get('package_type').'"');

            // write the session to close so you can continue to browse on the site.
            session_write_close();

            // Make it a bit easier for tar/zip.
            chdir(dirname($tar));
            passthru($command);

            $options = [
                'user_id' => $cfg['user'],
                'file' => $sendname . '.' . $settings->get('package_type'),
                'action' => $cfg["constants"]["fm_download"],
            ];
            $log = new \Gratbrav\Torrentbug\Log\Service();
            $log->save($options);

            exit();
        }
        else
        {
            $options = [
                'user_id' => $cfg['user'],
                'file' => 'Illegal download: ' . $cfg['user'] . ' tried to download ' . $tar,
                'action' => $cfg["constants"]["error"],
            ];
            $log = new \Gratbrav\Torrentbug\Log\Service();
            $log->save($options);
        }
    }
    else
    {
        $options = [
            'user_id' => $cfg['user'],
            'file' => 'ILLEGAL TAR DOWNLOAD: ' . $cfg['user'] . ' tried to download ' . $tar,
            'action' => $cfg["constants"]["error"],
        ];
        $log = new \Gratbrav\Torrentbug\Log\Service();
        $log->save($options);
    }
    header("Location: dir.php?dir=".urlencode($current));
}

if ($dir == "")
{
    unset($dir);
}

if (isset($dir))
{
    if (ereg("(\.\.)", $dir))
    {
        unset($dir);
    }
    else
    {
        $dir = $dir."/";
    }
}
?>
<?php include_once 'header.php' ?>

<div class="container">
	<div class="row">
		<div class="col-sm-12 bd-example" style="padding: 10px;">
			<?php displayDriveSpaceBar(getDriveSpace($settings->get('path'))); ?>
		</div>
	</div>

	<div class="row">
		<div class="col-sm-12 bd-example">
			<?php 
				if (!isset($dir)) $dir = "";

				if (!file_exists($settings->get('path').$dir)) {
    				echo "<strong>".htmlentities($dir)."</strong> could not be found or is not valid.";
				} else {
		    
	                if (isset($dir)) {
                        // setup default parent directory URL
                        $parentURL = 'dir.php';

                        //get the real parentURL
                        if (preg_match('/^(.+)\/.+$/', $dir, $matches) == 1) {
                            $parentURL = 'dir.php?dir=' . urlencode($matches[1]);
                        }
                    ?>
                	<form action="multi.php" method="post" name="multidir">
                		<input type="hidden" name="action" value="fileDelete" />
                		<table class="table table-striped">
                		<tr>
                			<td>
                				<a href="<?php echo $parentURL; ?>" title="<?php _BACKTOPARRENT; ?>">
                					<i class="fa fa-folder-open" aria-hidden="true" style="color:orange;margin-right:4px;"></i>[<?php echo _BACKTOPARRENT; ?>]
                				</a>
                			</td>
                			<td class="hidden-sm-down"> </td>
                			<td style="text-align:right">Multi-Delete-&gt;</td>
                			<td style="text-align:right">
                				<a class="delete" href="javascript:document.multidir.submit()" data-file="Multiple Files"><i class="fa fa-trash-o" style="color:red" aria-hidden="true" title="<?php echo _DELETE; ?>"></i></a> 
                				<input class="selectAll" type="checkbox" />
            				</td>
        				</tr>
    			<?php } ?>

    			<?php 
        			$dirName = stripslashes($settings->get('path') . $dir);
        			
        			$entrys = array();
    			    foreach (glob($dirName . '*', GLOB_ONLYDIR) as $dir2) {
    			        $entrys[] = str_replace($dirName, '', $dir2);
    			    }
    			    natsort($entrys);

    			    foreach ($entrys as $entry) {
			        ?>
		                <tr>
		                	<td>
		                		<a href="dir.php?dir=<?php echo urlencode($dir . $entry); ?>" title="<?php echo $entry; ?>">
		                			<i class="fa fa-folder" aria-hidden="true" style="color:orange;margin-right:4px;"></i><?php echo $entry; ?>
		                		</a>
	                		</td>
		                	<td>&nbsp;</td>
		                	<td>&nbsp;</td>
		                	<td style="text-align:right">
    			    
    			            <?php if ($settings->get('enable_maketorrent')) { ?>
			                    <a class="makeTorrent" href="#" data-url="maketorrent.php?path=<?php echo urlencode($dir . $entry); ?>"><i class="fa fa-external-link" style="color:#5CB85C" aria-hidden="true" title="Make Torrent"></i></a> 
    			            <?php } ?>
    			    
			                <?php if ($settings->get('enable_file_download')) { ?>
	                    		<a href="dir.php?tar=<?php echo urlencode($dir . $entry); ?>"><i class="fa fa-download" style="color:#5CB85C" aria-hidden="true" title="Download as '<?php echo $settings->get('package_type'); ?>"></i></a> 
    			            <?php } ?>
    			    
    			            <?php if (IsAdmin($cfg["user"]) || preg_match("/^" . $cfg["user"] . "/",$dir)) { ?>
    			            	<a class="delete" href="dir.php?del=<?php echo urlencode($dir . $entry); ?>" data-file="<?php echo addslashes($entry); ?>"><i class="fa fa-trash-o" style="color:red" aria-hidden="true" title="<?php echo _DELETE; ?>"></i></a>
    			                <input class="selectFile" type="checkbox" name="file[]" value="<?php echo urlencode($dir . $entry); ?>">
							<?php } ?>
    			            </td>
			            </tr>
    			  <?php }
    			    closedir($handle);
                ?>
    			
    			<?php 
        			
        			$entrys = array();
    			    foreach (glob($dirName . '*.*') as $dir2) {
                        $entrys[] = str_replace($dirName, '', $dir2);
    			    }
    			    
    			    foreach($entrys as $entry) {

    			        $arStat = @lstat($dirName.$entry);
		                $arStat[7] = ( $arStat[7] == 0 )? file_size( $dirName . $entry ) : $arStat[7];
		                
		                $timeStamp = '';
		                if (array_key_exists(10,$arStat)) {
		                    $timeStamp = $arStat[10];
		                }
		                
	                   $fileSize = number_format(($arStat[7])/1024);
                    ?>
						<tr>
							<td>
    			    
							<?php if ($settings->get('enable_file_download')) { ?>
								<a href="dir.php?down=<?php echo urlencode($dir.$entry); ?>" >
			                    	<i class="fa fa-file" aria-hidden="true" style="color:orange;margin-right:4px;"></i><?php echo $entry; ?>
    			                </a>
			                <?php } else { ?>
    			                    <i class="fa fa-file" aria-hidden="true"  style="color:orange;margin-right:4px;"></i>
    			                    <?php echo $entry; ?>
							<?php } ?>
    			    
    			            </td>
    			            <td style="text-align:right"><?php echo $fileSize; ?> KB</td>
    			            <td class="hidden-sm-down"><?php echo date('m-d-Y g:i a', $timeStamp); ?></td>
    			            <td style="text-align:right">
    			    
			                <?php if( $settings->get('enable_view_nfo') && (( substr( strtolower($entry), -4 ) == ".nfo" ) || ( substr( strtolower($entry), -4 ) == ".txt" ))  ) { ?>
    			            	<a href="viewnfo.php?path=<?php echo urlencode(addslashes($dir.$entry)); ?>" title="View <?php echo $entry; ?>"><i class="fa fa-info" aria-hidden="true"></i></a>
    			            <?php } ?>
    			    
			                <?php if ($settings->get('enable_maketorrent')) { ?>
			                    <a class="makeTorrent" href="#" data-url="maketorrent.php?path=<?php echo urlencode($dir.$entry); ?>"><i class="fa fa-external-link" style="color:#5CB85C" aria-hidden="true" title="Make Torrent"></i></a>
			                <?php } ?>
    			    
			                <?php if ($settings->get('enable_file_download')) { ?>
    			            	<a href="dir.php?down=<?php echo urlencode($dir.$entry); ?>" ><i class="fa fa-download" style="color:#5CB85C" aria-hidden="true" title="Download"></i></a>
			                <?php } ?>
    			    
			                <?php if (IsAdmin($cfg["user"]) || preg_match("/^" . $cfg["user"] . "/",$dir)) { ?>
    			            	<a class="delete" href="dir.php?del=<?php echo urlencode($dir.$entry); ?>" data-file="<?php echo addslashes($entry); ?>"><i class="fa fa-trash-o" style="color:red" aria-hidden="true" title="<?php echo _DELETE; ?>"></i></a>
    			                <input class="selectFile" type="checkbox" name="file[]" value="<?php echo urlencode($dir.$entry); ?>">
			                <?php } else { ?>
    			            	&nbsp;
    			            <?php } ?>
    			            </td>
			            </tr>
    			    <?php 
    			    }
    			    closedir($handle);
			    ?>
				</table>
    		</form>
		    <?php } ?>
		</div>
	</div>
</div>

<?php

// ***************************************************************************
// ***************************************************************************
// Checks for the location of the users directory
// If it does not exist, then it creates it.
function checkUserPath()
{
    global $cfg, $settings;
    // is there a user dir?
    if (!is_dir($settings->get('path').$cfg["user"]))
    {
        //Then create it
        mkdir($settings->get('path').$cfg["user"], 0777);
    }
}

?>

<script>
$(document).ready(function() {

	$(".makeTorrent").click(function() {
		var specs = 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=600,height=430';
	    window.open ($(this).data('url'), '_blank', specs);

		return false;
	});

	$(".delete").click(function() {
		return confirm("<?php echo _ABOUTTODELETE ?>: " + $(this).data('file'));
	});

	$(".selectAll").click(function() {
		$(".selectFile").prop('checked', $(this).prop("checked"));
	});

});
</script>
