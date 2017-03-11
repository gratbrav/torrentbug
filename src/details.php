<?php

/*************************************************************
 *  TorrentFlux - PHP Torrent Manager
 *  www.torrentflux.com
 **************************************************************/
/*
 * This file is part of TorrentFlux.
 *
 * TorrentFlux is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * TorrentFlux is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with TorrentFlux; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */
include_once './Class/autoload.php';

include_once ("config.php");
include_once ("functions.php");
require_once ("metaInfo.php");

$settings = new Gratbrav\Torrentbug\Settings();

global $cfg;

$torrent = SecurityClean(getRequestVar('torrent'));

include_once 'header.php';

// Does the user have messages?
$sql = "select count(*) from tf_messages where to_user='" . $cfg['user'] . "' and IsNew=1";

$number_messages = $db->GetOne($sql);
showError($db, $sql);
$countMessages = '';
if ($number_messages > 0) {
    $countMessages = ' (' . $number_messages . ')';
}
?>

<div class="container">
    <div class="row">
        <div class="col-sm-12">
			<?php displayDriveSpaceBar(getDriveSpace($settings->get('path'))); ?>
		</div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-sm-12">
            <fieldset class="form-group bd-example">
				<?php
    $als = getRequestVar('als');
    if ($als == "false") {
        showMetaInfo($torrent, false);
    } else {
        showMetaInfo($torrent, true);
    }
    ?>
			</fieldset>
        </div>
    </div>
</div>

<div style="text-align: center">
    [<a href="index.php"><?php echo _RETURNTOTORRENTS ?></a>]
</div>
