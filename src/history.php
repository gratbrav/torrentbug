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
include_once 'Class/autoload.php';
include_once 'config.php';
include_once 'functions.php';

    $settings = new Gratbrav\Torrentbug\Settings();

    include_once 'header.php';

    $filter = [
        'action' => [$cfg["constants"]["url_upload"], $cfg["constants"]["file_upload"]],
    ];

    $log = new \Gratbrav\Torrentbug\Log\Service();
    $log->setFilter($filter);
?>
<script src="<?= $settings->get('base_url') ?>/plugins/datatables/datatables/media/js/jquery.dataTables.min.js"></script>
<script src="<?= $settings->get('base_url') ?>/plugins/datatables/datatables/media/js/dataTables.bootstrap4.min.js"></script>

<link href="<?= $settings->get('base_url') ?>/plugins/datatables/datatables/media/css/dataTables.bootstrap4.min.css" type="text/css" rel="stylesheet" />
<link href="<?= $settings->get('base_url') ?>/plugins/twitter/bootstrap/dist/css/bootstrap.min.css" type="text/css" rel="stylesheet" />

<div class="container">
    <div class="row">

        <div class="card">
            <div class="card-header">
                <i class="fa fa-users" aria-hidden="true"></i>
                <?= _USERDETAILS ?>
            </div>
            <div class="card-block">

                <table id="historylist" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th><?= _USER ?></th>
                        <th><?= _FILE ?></th>
                        <th><?= _TIMESTAMP ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($log->getLogs() as $log) { ?>
                        <?php $iconClass = (IsOnline($log->getUserId())) ? 'text-success' : 'text-muted'; ?>
                        <tr>
                            <td>
                                <a href="message.php?to_user=<?= $user_id; ?>">
                                    <i class="fa fa-user <?= $iconClass ?>" aria-hidden="true"></i>
                                    <?= $log->getUserId() ?>
                                </a>
                           </td>
                           <td><?= $log->getFile() ?></td>
                           <td><?= date(_DATETIMEFORMAT, $log->getTime()); ?></td>
                       </tr>
                    <?php } ?>
                </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<link rel="stylesheet" href="/src/plugins/datatables/datatables/media/css/dataTables.bootstrap4.min.css" />
<script src="/src/plugins/datatables/datatables/media/js/jquery.dataTables.min.js"></script>
<script src="/src/plugins/datatables/datatables/media/js/dataTables.bootstrap4.min.js"></script>

<script>
$( document ).ready( function() {
    $( "#historylist" ).dataTable( {
        lengthChange: false,
    } );
} );
</script>
