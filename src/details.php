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
require_once("metaInfo.php");

global $cfg;

$torrent = SecurityClean(getRequestVar('torrent'));
?>
<!doctype html>
<html>
<head>
	<TITLE><?php echo $percentdone.$cfg["pagetitle"] ?></TITLE>
	<link rel="icon" href="images/favicon.ico" type="image/x-icon" />
	<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
	<link rel="stylesheet" href="./plugins/twitter/bootstrap/dist/css/bootstrap.min.css" type="text/css" />
    <LINK REL="StyleSheet" HREF="themes/<?php echo $cfg["theme"] ?>/style.css" TYPE="text/css">
	<META HTTP-EQUIV="Pragma" CONTENT="no-cache" charset="<?php echo _CHARSET ?>">
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
</head>
<body topmargin="8" bgcolor="<?php echo $cfg["main_bgcolor"] ?>">

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
		</nav>
	</div>
</div>

<div class="container">
	<div class="row">
		<div class="col-sm-12">
			<?php displayDriveSpaceBar(getDriveSpace($cfg["path"])); ?>
		</div>
	</div>
</div>

<div class="container">
	<div class="row">
		<div class="col-sm-12">
			<fieldset class="form-group bd-example">
				<?php 
					$als = getRequestVar('als');
					if($als == "false") {
       					showMetaInfo($torrent,false);
					} else {
    					showMetaInfo($torrent,true);
					}
				?>
			</fieldset>
		</div>
	</div>
</div>

<div style="text-align:center">[<a href="index.php"><?php echo _RETURNTOTORRENTS ?></a>]</div>

<?php echo DisplayTorrentFluxLink(); ?>
