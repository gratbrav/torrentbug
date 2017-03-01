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
        $newLink = getRequestVar('newLink');
        $newSite = getRequestVar('newSite');
        
        if(!empty($newLink)) {
            if(strpos($newLink, "http://" ) !== 0 && strpos($newLink, "https://" ) !== 0 && strpos($newLink, "ftp://" ) !== 0)
            {
                $newLink = "http://".$newLink;
            }
            empty($newSite) && $newSite = $newLink;

            addNewLink($newLink, $newSite);
            AuditAction($cfg["constants"]["admin"], "New "._LINKS_MENU.": ".$newSite." [".$newLink."]");
        }
        
        header("location: links.php");
        exit;
        

    } else if ($action == 'edit') {
        $lid = getRequestVar('lid');
        $newLink = getRequestVar('editLink');
        $newSite = getRequestVar('editSite');
        
        if(!empty($newLink)) {
            if(strpos($newLink, "http://" ) !== 0 && strpos($newLink, "https://" ) !== 0 && strpos($newLink, "ftp://" ) !== 0)
            {
                $newLink = "http://".$newLink;
            }
            empty($newSite) && $newSite = $newLink;
        
            $oldLink = getLink($lid);
            $oldSite = getSite($lid);
            
            $sql = "UPDATE tf_links SET url='".$newLink."',`sitename`='".$newSite."' WHERE `lid`=".$lid;
            $db->Execute($sql);
            showError($db,$sql);
    
            AuditAction($cfg["constants"]["admin"], "Change Link: ".$oldSite." [".$oldLink."] -> ".$newSite." [".$newLink."]");
        }
        header("location: links.php");
        exit;


    } else if ($action == 'delete') {
        $lid = getRequestVar('lid');
        
        AuditAction($cfg["constants"]["admin"], _DELETE." Link: ".getSite($lid)." [".getLink($lid)."]");

        $idx = getLinkSortOrder($lid);
    
        // Fetch all link ids and their sort orders where the sort order is greater
        // than the one we're removing - we need to shuffle each sort order down
        // one:
        $sql = "SELECT sort_order, lid FROM tf_links ";
        $sql .= "WHERE sort_order > ".$idx." ORDER BY sort_order ASC";
        $result = $db->Execute($sql);
        showError($db,$sql);
        $arLinks = $result->GetAssoc();
    
        // Decrement the sort order of each link:
        foreach($arLinks as $sid => $this_lid)
        {
            $sql="UPDATE tf_links SET sort_order=sort_order-1 WHERE lid=".$this_lid;
            $db->Execute($sql);
            showError($db,$sql);
        }
    
        // Finally delete the link:
        $sql = "DELETE FROM tf_links WHERE lid=".$lid;
        $result = $db->Execute($sql);
        showError($db,$sql);
        
        header("location: links.php");
        exit;


    } else if ($action == 'move') {
        $lid = getRequestVar('lid');
        $direction = getRequestVar('direction');
        
        if (!isset($lid) && !isset($direction)&& $direction !== "up" && $direction !== "down" ) {
            header("location: links.php");
        }
        $idx = getLinkSortOrder($lid);
        $position = array("up"=>-1, "down"=>1);
        $new_idx = $idx+$position[$direction];
        $sql = "UPDATE tf_links SET sort_order=".$idx." WHERE sort_order=".$new_idx;
        $db->Execute($sql);
        showError($db, $sql);
        $sql = "UPDATE tf_links SET sort_order=".$new_idx." WHERE lid=".$lid;
        $db->Execute($sql);
        showError($db, $sql);
        
        header("location: links.php");
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
					<th colspan="2">
						<img src="../images/properties.png" alt="">&nbsp;&nbsp;
						<?php echo _ADMINEDITLINKS ?>
					</th>
				</tr>
				<tr>
					<td colspan=2 style="text-align:center">
					    <form action="links.php" method="post">
					    	<input type="hidden" name="action" value="add" />
						    <?php echo _FULLURLLINK ?>:
						    <input type="text" size="30" maxlength="255" name="newLink">
						    Site Name:
						    <input type="text" size="30" maxlength="255" name="newSite">
						    <input type="Submit" value="<?php echo _UPDATE ?>"><br>
					    </form>
					 </td>
				</tr>
				<?php
				    $arLinks = GetLinks();
				
				    if (is_array($arLinks)) {
				        $arLid = Array_Keys($arLinks);
				        $inx = 0;
				        $link_count = count($arLinks);
				
				        foreach($arLinks as $link)
				        {
				            $lid = $arLid[$inx++];
				            $ed = getRequestVar("edit");
				            if (!empty($ed) && $ed == $link['lid']) {
							    ?>
							    	<tr>
				    					<td colspan="2">
											<form action="links.php" method="post">
										    	<input type="hidden" name="action" value="edit" />
											      <?php echo _FULLURLLINK ?>:
											      <input type="Text" size="30" maxlength="255" name="editLink" value="<?php echo $link['url'] ?>">
											      Site Name:
											      <input type="Text" size="30" maxlength="255" name="editSite" value="<?php echo $link['sitename'] ?>">
											      <input type="hidden" name="lid" value="<?php echo $lid ?>">
											      <input type="Submit" value="<?php echo _UPDATE ?>"><br>
										      </form>
										 </td>
									</tr>
							<?php } else { ?>
            					<tr>
            						<td>
						                <a href="links.php?action=delete&lid=<?php echo $lid ?>"><img src="../images/delete_on.gif" alt="" title="<?php echo _DELETE . " " . $lid ?>" ></a>&nbsp;
						                <a href="links.php?action=edits&edit=<?php echo $lid ?>"><img src="../images/properties.png" alt="" title="<?php echo _EDIT . " " . $lid ?>" ></a>&nbsp;
						                <a href="<?php echo $link['url'] ?>" target="_blank"><?php echo $link['sitename'] ?></a>
						            </td>
					                <td style="text-align:center;width:36">

						                <?php if ($inx > 1 ){
						                    // Only put an 'up' arrow if this isn't the first entry:
						                    echo "<a href='links.php?action=move&amp;direction=up&amp;lid=".$lid."'>";
						                    echo "<img src='../images/uparrow.png' title='Move link up' alt='Up'></a>";
						                }
						
						                if ($inx != count($arLinks)) {
						                    // Only put a 'down' arrow if this isn't the last item:
						                    echo "<a href='links.php?action=move&amp;direction=down&amp;lid=".$lid."'>";
						                    echo "<img src='../images/downarrow.png' title='Move link down' alt='Down'></a>";
						                }
						                ?>
            						</td>
            					</tr>
				                <?php
				            }
				        }
				    } 
				    ?>
			</table>		
		</div>
	</div>
</div>
