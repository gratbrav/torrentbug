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


$result = shell_exec("df -h ".$cfg["path"]);
$result2 = shell_exec("du -sh ".$cfg["path"]."*");


?>
<!doctype html>
<html>
<head>
	<title><?php echo $cfg["pagetitle"] ?></title>
	<link rel="icon" href="images/favicon.ico" type="image/x-icon" />
	<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
	<link rel="stylesheet" href="./plugins/twitter/bootstrap/dist/css/bootstrap.min.css" type="text/css" />
    <link rel="styleSheet" HREF="themes/<?php echo $cfg["theme"] ?>/style.css" type="text/css" />
	<meta http-equiv="Pragma" content="no-cache" charset="<?php echo _CHARSET ?>">
</head>
<body>

<div class="container">
	<div class="row">
		<nav class="navbar navbar-light" style="background-color:#e3f2fd;">
			<?php include_once 'menu.php' ?>
		</nav>
	</div>
</div>

<div class="container">
	<div class="row">
		<div class="col-sm-12">
			<?php echo displayDriveSpaceBar(getDriveSpace($cfg["path"])); ?>
		</div>
	</div>
</div>
	
<div class="container">
	<div class="row">
		<div class="col-sm-12">
			<fieldset class="form-group bd-example">
				<pre><?php echo $result; ?><hr><?php echo $result2; ?></pre>
			</fieldset>
		</div>
	</div>
</div>

<div style="text-align:center">[<a href="index.php"><?php echo _RETURNTOTORRENTS ?></a>]</div>

<?php echo DisplayTorrentFluxLink(); ?>
