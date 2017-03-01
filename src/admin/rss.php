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
    if (empty($action)) {
        $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
    }
    
    
    if ($action == 'add') {
        $newRSS = getRequestVar('newRSS');
        
        if (!empty($newRSS)){
            addNewRSS($newRSS);
            AuditAction($cfg["constants"]["admin"], "New RSS: ".$newRSS);
        }
        header("location: rss.php");
        exit;


    } else if ($action == 'delete') {
        $rid = getRequestVar('rid');

        AuditAction($cfg["constants"]["admin"], _DELETE." RSS: ".getRSS($rid));

        $sql = "delete from tf_rss where rid=".$rid;
        $result = $db->Execute($sql);
        showError($db,$sql);
        
        header("location: rss.php");
        exit;
    }

    $subMenu = 'admin';
    include_once '../header.php';
    
?>

<div class="container">
	<div class="row">
		<div class="col-sm-12 bd-example">
			<table class="table table-striped">
    			<tr>
    				<th>
    					<img src="../images/properties.png" alt="">&nbsp;&nbsp;
    					RSS Feeds
    				</th>
    			</tr>
    			<tr>
    				<td style="text-align:center">
    				    <form action="rss.php" method="post">
    				    	<input type="hidden" name="action" value="add" />
						    <?php echo _FULLURLLINK ?>:
						    <input type="text" size="50" maxlength="255" name="newRSS">
						    <input type="Submit" value="<?php echo _UPDATE ?>">
					    </form>
					</td>
				</tr>
				<?php
				    $arLinks = GetRSSLinks();
				    $arRid = Array_Keys($arLinks);
				    $inx = 0;
				    if(is_array($arLinks)) {
				        foreach($arLinks as $link) {
				            $rid = $arRid[$inx++];
				            echo "<tr><td><a href=\"rss.php?action=delete&rid=".$rid."\"><img src=\"../images/delete_on.gif\" alt=\"\" title=\""._DELETE." ".$rid."\" ></a>&nbsp;";
				            echo "<a href=\"".$link."\" target=\"_blank\">".$link."</a></td></tr>";
				        }
				    } 
				?>
    			</table>
		</div>
	</div>
</div>