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

	$settings = new Class_Settings();

	include_once 'header.php'; 

?>

<div class="container">
	<div class="row">
		<div class="col-sm-12 bd-example" style="padding:6px">

    		<table id="historylist" class="table table-striped table-bordered">
				<thead>
    				<tr>
    					<th><?php echo _USER; ?></th>
    					<th><?php echo _FILE; ?></th>
    					<th><?php echo _TIMESTAMP; ?></th>
    				</tr>  
				</thead> 
				<tbody>     		
    			<?php
            		$sql = "SELECT user_id, file, time FROM tf_log WHERE action=".$db->qstr($cfg["constants"]["url_upload"])." OR action=".$db->qstr($cfg["constants"]["file_upload"])." ORDER BY time desc";
            		
            		$result = $db->SelectLimit($sql);
            		while (list($user_id, $file, $time) = $result->FetchRow()) {
            		    $iconColor = "#D9534F";
            		    if (IsOnline($user_id)) {
            		        $iconColor = '#5CB85C';
            		    }
            		?>
        			<tr>
        		    	<td><a href="message.php?to_user=<?php echo $user_id; ?>"><i class="fa fa-user" aria-hidden="true" style="color:<?php echo $iconColor; ?>;margin-right:4px;"></i><?php echo $user_id; ?></a>&nbsp;&nbsp;</td>
        		    	<td><?php echo $file; ?></td>
        		    	<td style="text-align:center;"><?php echo date(_DATETIMEFORMAT, $time); ?></td>
        		    </tr>
            		<?php }	?>
                </tbody>    
		    </table>
    
		</div>
	</div>
</div>

<link rel="stylesheet" href="/src/plugins/datatables/datatables/media/css/dataTables.bootstrap4.min.css" />
<script src="/src/plugins/datatables/datatables/media/js/jquery.dataTables.min.js"></script>
<script src="/src/plugins/datatables/datatables/media/js/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    $('#historylist').dataTable( {
        lengthChange: false,
    });
});
</script>
