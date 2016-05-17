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
    
    $settings = new Class_Settings();
    
    include_once 'functions.php';


    $delete = getRequestVar('delete');
    if (!empty($delete)) {
        DeleteMessage($delete);
        header("location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    include_once 'header.php';
?>

<?php
$mid = getRequestVar('mid');
if (!empty($mid) && is_numeric($mid)) {
    list($from_user, $message, $ip, $time, $isnew, $force_read) = GetMessage($mid);

    if (!empty($from_user) && $isnew == 1) { 
        // We have a Message that is being seen
        // Mark it as NOT new.
        MarkMessageRead($mid);
    }
?>

<div style="text-align:center">[<a href="?"><?php echo _RETURNTOMESSAGES ?></a>]</div>


<div class="container">
	<div class="row">
		<div class="col-sm-12 bd-example">
			<?php
				$message = check_html($message, "nohtml");
    			$message = str_replace("\n", "<br>", $message);
    		?>
    		<table class="table table-striped">
    			<tr>
					<td>
						<?php echo _FROM ?>: 
						<strong><?php echo $from_user ?></strong>
					</td>
					<td style="text-align:right">
					    <?php if (IsUser($from_user)) { ?>
					        <a href="message.php?to_user=<?php echo $from_user ?>&rmid=<?php echo $mid ?>">
								<img src="images/reply.gif" title="<?php echo _REPLY ?>" alt="" />
					        </a>
					    <?php } ?>
    					<a href="<?php echo $_SERVER['PHP_SELF'] ?>?delete=<?php echo $mid ?>">
   							<img src="images/delete_on.gif" title="<?php echo _DELETE ?>" alt="" />
   						</a>
   					</td>
   				</tr>
   				<tr>
   					<td colspan=2>
   						<?php echo _DATE ?>:  
   						<strong><?php echo date(_DATETIMEFORMAT, $time) ?></strong>
   					</td>
   				</tr>
   				<tr>
   					<td colspan=2>
   						<?php echo _MESSAGE ?>:
   						<blockquote><strong><?php echo $message ?></strong></blockquote>
   					</td>
   				</tr>
   			</table>
		</div>
	</div>
</div>

<?php } else { ?>
	
<div class="container">
	<div class="row">
		<div class="col-sm-12 bd-example">
			<form name="formMessage" action="message.php" method="post">
				<table class="table table-striped">
    				<tr>
    					<td style="vertical-align: middle;"><?php echo _SENDMESSAGETO ?></td>
    					<td style="vertical-align: middle">
    						<select name="to_user" class="form-control">
    							<?php
    								$users = GetUsers();
        							foreach ($users AS $user) {
        								echo '<option>'.htmlentities($user, ENT_QUOTES).'</option>';
        							}
        						?>
    						</select>
    					</td>
    					<td><input type="Submit" value="<?php echo _COMPOSE ?>" class="btn btn-primary"></td>
    				</tr>
    			</table>
    		</form>
		</div>
	</div>
</div>

<div class="container">
	<div class="row">
		<div class="col-sm-12 bd-example">
			<?php $inx = 0; ?>
   			<table class="table table-striped">
   				<tr>
   					<th><?php echo _FROM ?></th>
   					<th><?php echo _MESSAGE ?></th>
   					<th><?php echo _DATE ?></th>
   					<th><?php echo _ADMIN ?></th>
   				</tr>
				<?php
   					$sql = "SELECT mid, from_user, message, IsNew, ip, time, force_read FROM tf_messages WHERE to_user=".$db->qstr($cfg['user'])." ORDER BY time";
   					$result = $db->Execute($sql);
   					showError($db,$sql);

   					while (list($mid, $from_user, $message, $new, $ip, $time, $force_read) = $result->FetchRow()) {

   						if($new == 1) {
           					$mail_image = "images/new_message.gif";
       					} else {
           					$mail_image = "images/old_message.gif";
       					}

       					$display_message = check_html($message, "nohtml");
       					if (strlen($display_message) >= 40) { // needs to be trimmed
           					$display_message = substr($display_message, 0, 39);
           					$display_message .= "...";
       					}

       					$link = $_SERVER['PHP_SELF']."?mid=".$mid;
					?>
        			<tr>
        				<td>
        					&nbsp;&nbsp;
        					<a href="<?php echo $link ?>">
        						<img src="<?php echo $mail_image ?>" title="" alt="" />
        					</a>
        					&nbsp;&nbsp; 
        					<a href="<?php echo $link ?>"><?php echo $from_user ?></a>
        				</td>
        				<td><a href="<?php echo $link ?>"><?php echo $display_message ?></a></td>
        				<td style="text-align:center"><a href="<?php echo $link ?>"><?php echo date(_DATETIMEFORMAT, $time) ?></a></td>
        				<td style="text-align:center">
							<?php
						        // Is this a force_read from an admin?
        						if ($force_read == 1) {
						            // Yes, then don't let them delete the message yet
            						echo "<img src=\"images/delete_off.gif\" alt=\"\" title=\"\" />";
        						} else {
						            // No, let them reply or delete it
						            if (IsUser($from_user)) {
                						echo "<a href=\"message.php?to_user=".$from_user."&rmid=".$mid."\"><img src=\"images/reply.gif\" alt=\"\" title=\""._REPLY."\" /></a>";
            						}
            						echo "<a href=\"".$_SERVER['PHP_SELF']."?delete=".$mid."\"><img src=\"images/delete_on.gif\" alt=\"\" title=\""._DELETE."\" /></a></td></tr>";
        						}
        				$inx++;
    				} // End While
    				?>
    		</table>

    		<?php
			    if($inx == 0) {
       				echo "<div style=\"text-align:center\"><strong>-- "._NORECORDSFOUND." --</strong></div>";
   				} 
   			?>
		</div>
	</div>
</div>

<?php } ?>

<div style="text-align:center">[<a href="index.php"><?php echo _RETURNTOTORRENTS ?></a>]</div>
