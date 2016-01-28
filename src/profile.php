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
	
//****************************************************************************
// showIndex -- main view
function showIndex()
{
    global $cfg, $db, $settings;

    $hideChecked = ($cfg['hide_offline'] == 1) ? 'checked' : '';
?>

<div class="container">
	<div class="row">
		<div class="col-sm-6">
			<?php 
	   			$total_activity = GetActivityCount();
				$sql= "SELECT user_id, hits, last_visit, time_created, user_level FROM tf_users WHERE user_id=".$db->qstr($cfg["user"]);
	    		list($user_id, $hits, $last_visit, $time_created, $user_level) = $db->GetRow($sql);
	
	    		$user_type = _NORMALUSER;
				if (IsSuperAdmin()) {
	        		$user_type = _SUPERADMIN;
	    		} else if (IsAdmin()) {
	        		$user_type = _ADMINISTRATOR;
	    		}
	
	    		$user_activity = GetActivityCount($cfg["user"]);
	    		if ($user_activity == 0) {
	        		$user_percent = 0;
	    		} else {
	        		$user_percent = number_format(($user_activity/$total_activity)*100);
	    		}
			?>
			<fieldset class="form-group bd-example" style="margin-right:-12px;margin-left:-15px;">
		        <table class="table table-striped">
		        <tr>
		            <td style="text-align:right"><?php echo $cfg["user"] ?> <?php echo _JOINED ?>:&nbsp;</td>
		            <td><strong><?php echo date(_DATETIMEFORMAT, $time_created) ?></strong></td>
		        </tr>
		        <tr>
		            <td style="text-align:right"><?php echo _UPLOADPARTICIPATION ?>:&nbsp;</td>
		            <td><progress class="progress progress-success" value="<?php echo $user_percent ?>" max="100" style="margin-bottom:0px"><?php echo $user_percent*2 ?>%</progress></td>
		        </tr>
		        <tr>
		            <td style="text-align:right"><?php echo _UPLOADS ?>:&nbsp;</td>
		            <td><strong><?php echo $user_activity ?></strong></td>
		        </tr>
		        <tr>
		            <td style="text-align:right"><?php echo _PERCENTPARTICIPATION ?>:&nbsp;</td>
		            <td><strong><?php echo $user_percent ?>%</strong></td>
		        </tr>
		        <tr>
		            <td colspan="2" style="text-align:center"><div style="text-align:center" class="tiny">(<?php echo _PARTICIPATIONSTATEMENT. " " . $settings->get('days_to_keep') . " "._DAYS ?>)</div></td>
		        </tr>
		        <tr>
		            <td style="text-align:right"><?php echo _TOTALPAGEVIEWS ?>:&nbsp;</td>
		            <td><strong><?php echo $hits ?></strong></td>
		        </tr>
		        <tr>
		            <td style="text-align:right"><?php echo _USERTYPE ?>:&nbsp;</td>
		            <td><strong><?php echo $user_type ?></strong></td>
		        </tr>
		        <tr>
		            <td colspan="2" style="text-align:center">[ <a href="?op=showCookies">Cookie Management</a> ]</td>
		        </tr>
		        </table>
  			</fieldset>
		</div>
		
		<div class="col-sm-6">
			<fieldset class="form-group bd-example" style="margin-left:-12px;margin-right:-15px">
				<form id="formProfile" name="theForm" action="profile.php?op=updateProfile" method="post">
			        <table class="table table-striped">
				        <tr>
				            <td style="text-align:right"><?php echo _USER ?>:</td>
				            <td><input disabled type="Text" value="<?php echo $cfg["user"] ?>" class="form-control"></td>
				        </tr>
				        <tr>
				            <td style="text-align:right"><?php echo _NEWPASSWORD ?>:</td>
				            <td><input name="pass1" id="pass1" type="Password" value="" class="form-control"></td>
				        </tr>
				        <tr>
				            <td style="text-align:right"><?php echo _CONFIRMPASSWORD ?>:</td>
				            <td><input name="pass2" id="pass2" type="Password" value="" class="form-control"></td>
				        </tr>
				        <tr>
				            <td style="text-align:right"><?php echo _THEME ?>:</td>
				            <td>
				            	<select name="theme" class="form-control">
									<?php
									    $themes = GetThemes();
									    foreach ($themes AS $theme) {
									        $selected = ($cfg['theme'] == $theme) ? 'selected' : '';
									        echo "<option value=\"".$theme."\" ".$selected.">".$theme."</option>";
									    }
									?>
				            	</select>
				            </td>
				        </tr>
				        <tr>
				            <td style="text-align:right"><?php echo _LANGUAGE ?>:</td>
				            <td>
				        	    <select name="language" class="form-control">
									<?php
									    $languages = GetLanguages();
								    	foreach ($languages AS $language) {
									        $selected = ($cfg['language_file'] == $language) ? 'selected' : '';
									        echo "<option value=\"".$language."\" ".$selected.">".GetLanguageFromFile($language)."</option>";
									    }
									?>
				            	</select>
				            </td>
				        </tr>
				        <tr>
				            <td colspan="2"><input name="hideOffline" type="Checkbox" value="1" <?php echo $hideChecked ?> /> <?php echo _HIDEOFFLINEUSERS ?></td>
				        </tr>
				        <tr>
				            <td style="text-align:center" colspan="2"><input id="submitProfile" type="button" value="<?php echo _UPDATE ?>" class="btn btn-primary" /></td>
				        </tr>
				    </table>
        		</form>
			</fieldset>
		</div>
	</div>
</div>

<script> 

$(document).ready(function() {

	$("#submitProfile").click(function() {
   		var msg = ""
        if ($('#pass1').val() != "" || $('#pass2').val() != "") {
             if ($('#pass1').val().length <= 5 || $('#pass2').val().length <= 5) {
                 msg = msg + "* <?php echo _PASSWORDLENGTH ?>\n";
                 $('#pass1').focus();
             }
             
             if ($('#pass1').val() != $('#pass2').val()) {
                 msg = msg + "* <?php echo _PASSWORDNOTMATCH ?>\n";
                 $('#pass1').val("");
                 $('#pass2').val("");
                 $('#pass1').focus();
             }
         }

         if (msg != "") {
             alert("<?php echo _PLEASECHECKFOLLOWING ?>:\n\n" + msg);
             return false;
         } else {
             return true;
             $('#formProfile').submit();
         }
    });

});
</script>

<?php

}


//****************************************************************************
// updateProfile -- update profile
function updateProfile($pass1, $pass2, $hideOffline, $theme, $language)
{
    Global $cfg;

    if ($pass1 != "") {
        $_SESSION['user'] = md5($cfg["pagetitle"]);
    }

    UpdateUserProfile($cfg["user"], $pass1, $hideOffline, $theme, $language);
?>
<div class="container">
	<div class="row">
		<div class="col-sm-12">
			<fieldset class="form-group bd-example" style="text-align:center">
				<?php echo _PROFILEUPDATEDFOR . " " . $cfg["user"] ?>
			</fieldset>
		</div>
	</div>
</div>

<?php

}


//****************************************************************************
// ShowCookies -- show cookies for user
function ShowCookies()
{
    global $cfg, $db;

    $cid = getRequestVar("cid"); // Cookie ID

    // Used for when editing a cookie
    $hostvalue = $datavalue = "";
    if (!empty($cid)) {
        // Get cookie information from database
        $cookie = getCookie( $cid );
        $hostvalue = " value=\"" . $cookie['host'] . "\"";
        $datavalue = " value=\"" . $cookie['data'] . "\"";
    }

?>
<script>
    <!--
    function popUp(name_file)
    {
        window.open (name_file,'help','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=800,height=600')
    }
    // -->
</script>

<div style="text-align:center">[<a href="?">Return to Profile</a>]</div>

<div class="container">
	<div class="row">
		<div class="col-sm-12 bd-example">
			    <form action="?op=<?php echo ( !empty( $cid ) ) ? "modCookie" : "addCookie"; ?>"" method="post">
			    <input type="hidden" name="cid" value="<?php echo $cid;?>" />
			    <table class="table table-striped">
			        <tr>
			            <th colspan="3">
			                <img src="images/properties.png" alt=\"\">&nbsp;Cookie Management
			            </th>
			        </tr>
			        <tr>
			            <td width="80" align="right">&nbsp;Host:</td>
			            <td><input type="Text" class="form-control" maxlength="255" name="host" <?php echo $hostvalue;?> /></td>
			            <td>www.host.com</td>
			        </tr>
			        <tr>
			            <td width="80" align="right">&nbsp;Data:</td>
			            <td><input type="Text" class="form-control" maxlength="255" name="data" <?php echo $datavalue;?> /></td>
			            <td>uid=123456;pass=a1b2c3d4e5f6g7h8i9j1</td>
			        </tr>
			        <tr>
			            <td>&nbsp;</td>
			            <td colspan="2"><input type="Submit" value="<?php echo ( !empty( $cid ) ) ? _UPDATE : "Add"; ?>" class="btn btn-primary" /></td>
			        </tr>
					<?php
					    // We are editing a cookie, so have a link back to cookie list
					    if( !empty( $cid ) ) {
					?>
				        <tr>
				            <td colspan="3">
				                <center>[ <a href="?op=editCookies">back</a> ]</center>
				            </td>
				        </tr>
					<?php } else { ?>
			        <tr>
			            <td colspan="3">
			                <table class="table table-striped">
			                    <tr>
			                        <td style="font-weight: bold; padding-left: 3px;" width="50">Action</td>
			                        <td style="font-weight: bold; padding-left: 3px;">Host</td>
			                        <td style="font-weight: bold; padding-left: 3px;">Data</td>
			                    </tr>
								<?php
							        // Output the list of cookies in the database
							        $dat = getAllCookies($cfg["user"]);
							        if( empty( $dat ) )
							        {
								?>
			                	<tr>
			                    	<td colspan="3">No cookie entries exist.</td>
			                	</tr>
								<?php
							        } else {
							        	
							            foreach ( $dat as $cookie ) {
									?>
					                    <tr>
					                        <td>
					                            <a href="?op=deleteCookie&cid=<?php echo $cookie["cid"];?>"><img src="images/delete_on.gif" width=16 height=16 border=0 title="<?php echo _DELETE . " " . $cookie["host"]; ?>" align="absmiddle"></a>
					                            <a href="?op=editCookies&cid=<?php echo $cookie["cid"];?>"><img src="images/properties.png" width=18 height=13 border=0 title="<?php echo _EDIT . " " . $cookie["host"]; ?>" align="absmiddle"></a>
					                        </td>
					                        <td><?php echo $cookie["host"];?></td>
					                        <td><?php echo $cookie["data"];?></td>
					                    </tr>
									<?php
								            }
								        }
									?>
			                </table>
			            </td>
			        </tr>
				<?php
				    }
				?>
			        <tr>
			            <td colspan="3">
			                <div align="center">
			                	<a href="javascript:popUp('cookiehelp.php')">How to get cookie information....</a>
			                </div>
			            </td>
			        </tr>
		        </table>
		        </form>
		</div>
	</div>
</div>

<?php

}


//****************************************************************************
// addCookie -- adding a Cookie Host Information
//****************************************************************************
function addCookie( $newCookie )
{
    if( !empty( $newCookie ) )
    {
        global $cfg;
        AddCookieInfo( $newCookie );
        AuditAction( $cfg["constants"]["admin"], "New Cookie: " . $newCookie["host"] . " | " . $newCookie["data"] );
    }
    header( "location: profile.php?op=showCookies" );
}

//****************************************************************************
// deleteCookie -- delete a Cookie Host Information
//****************************************************************************
function deleteCookie($cid)
{
    global $cfg;
    $cookie = getCookie( $cid );
    deleteCookieInfo( $cid );
    AuditAction( $cfg["constants"]["admin"], _DELETE . " Cookie: " . $cookie["host"] );
    header( "location: profile.php?op=showCookies" );
}

//****************************************************************************
// modCookie -- edit a Cookie Host Information
//****************************************************************************
function modCookie($cid,$newCookie)
{
    global $cfg;
    modCookieInfo($cid,$newCookie);
    AuditAction($cfg["constants"]["admin"], "Modified Cookie: ".$newCookie["host"]." | ".$newCookie["data"]);
    header("location: profile.php?op=showCookies");
}

?>
<?php include_once 'header.php' ?>

<?php
//****************************************************************************
//****************************************************************************
//****************************************************************************
//****************************************************************************
// TRAFFIC CONTROLER
$op = getRequestVar('op');

switch ($op)
{

    default:
        showIndex();
        exit;
    break;

    case "updateProfile":
        $pass1 = getRequestVar('pass1');
        $pass2 = getRequestVar('pass2');
        $hideOffline = getRequestVar('hideOffline');
        $theme = getRequestVar('theme');
        $language = getRequestVar('language');

        updateProfile($pass1, $pass2, $hideOffline, $theme, $language);
    break;

    // Show main Cookie Management
    case "showCookies":
    case "editCookies":
        showCookies();
    break;

    // Add a new cookie to user
    case "addCookie":
        $newCookie["host"] = getRequestVar('host');
        $newCookie["data"] = getRequestVar('data');
        addCookie( $newCookie );
    break;

    // Modify an existing cookie from user
    case "modCookie":
        $newCookie["host"] = getRequestVar( 'host' );
        $newCookie["data"] = getRequestVar( 'data' );
        $cid = getRequestVar( 'cid' );
        modCookie( $cid, $newCookie );
    break;

    // Delete selected cookie from user
    case "deleteCookie":
        $cid = getRequestVar("cid");
        deleteCookie( $cid );
    break;

}
//****************************************************************************
//****************************************************************************
//****************************************************************************
//****************************************************************************

?>

<div style="text-align:center">[<a href="index.php"><?php echo _RETURNTOTORRENTS ?></a>]</div>

<?php echo DisplayTorrentFluxLink(); ?>
