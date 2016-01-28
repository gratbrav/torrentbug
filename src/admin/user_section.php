<div class="container">
	<div class="row">
		<div class="col-sm-12 bd-example">
			<table class="table table-striped">
    			<tr>
    				<th colspan="6">
    					<img src="../images/user_group.gif" alt="" />&nbsp;
    					<?php echo _USERDETAILS ?>
    				</th>
    			</tr>
    			<tr>
    				<th style="width:15%"><?php echo _USER ?></th>
				    <th style="width:6%"><?php echo _HITS ?></th>
				    <th><?php echo _UPLOADACTIVITY . ' (' . $settings->get('days_to_keep') . ' ' . _DAYS . ')' ?></th>
				    <th style="width:6%"><?php echo _JOINED ?></th>
				    <th style="width:15%"><?php echo _LASTVISIT ?></th>
				    <th style="width:8%"><?php echo _ADMIN ?></th>
				</tr>
				<?php 
				    $total_activity = GetActivityCount();

				    $sql= "SELECT user_id, hits, last_visit, time_created, user_level FROM tf_users ORDER BY user_id";
				    $result = $db->Execute($sql);
				    while(list($user_id, $hits, $last_visit, $time_created, $user_level) = $result->FetchRow())
				    {
				        $user_activity = GetActivityCount($user_id);
				
				        if ($user_activity == 0) {
				            $user_percent = 0;
				        } else {
				            $user_percent = number_format(($user_activity/$total_activity)*100);
				        }
				        
				        $user_icon = "../images/user_offline.gif";
				        if (IsOnline($user_id)) {
				            $user_icon = "../images/user.gif";
				        }
				
				        echo "<tr>";
				        if (IsUser($user_id)) {
				            echo "<td><a href=\"../message.php?to_user=" . $user_id . "\"><img src=\"" . $user_icon . "\" alt=\"\" title=\""._SENDMESSAGETO." ".$user_id."\" />".$user_id."</a></td>";
				        } else {
				            echo "<td><img src=\"".$user_icon."\" alt=\"\" title=\"n/a\" />".$user_id."</td>";
				        }
				        
				        echo "<td><div class=\"tiny\" style=\"text-align:right\">".$hits."</div></td>";
				        echo "<td>";
				       ?>
					        <table class="table table-striped">
						        <tr>
							        <td style="width:200">
							        	<progress class="progress progress-success" value="<?php echo $user_percent ?>" max="100" style="margin-bottom:0px"><?php echo $user_percent ?>%</progress>
							        </td>
							        <td style="text-align:right;width:40"><div class="tiny" style="text-align:right"><?php echo $user_activity ?></div></td>
							        <td style="text-align:right;width:40"><div class="tiny" style="text-align:right"><?php echo $user_percent ?>%</div></td>
							        <td style="text-align:right"><a href="activity.php?user_id=<?php echo $user_id ?>"><img src="../images/properties.png" alt="" title="<?php echo $user_id."'s "._USERSACTIVITY ?>" /></a></td>
							        </tr>
						        </table>
					        
					        
					        </td>
        					<td class="tiny" style="text-align:center"><?php echo date(_DATEFORMAT, $time_created) ?></td>
        					<td class="tiny" style="text-align:center"><?php echo date(_DATETIMEFORMAT, $last_visit) ?></td>
        					<td><div style="text-align:right" class="tiny">
							<?php
					        $user_image = "../images/user.gif";
					        $type_user = _NORMALUSER;
					        if ($user_level == 1) {
					            $user_image = "../images/admin_user.gif";
					            $type_user = _ADMINISTRATOR;
					        }
					        
					        if ($user_level == 2) {
					            $user_image = "../images/superadmin.gif";
					            $type_user = _SUPERADMIN;
					        }
					        
					        if ($user_level <= 1 || IsSuperAdmin()) {
					            echo "<a href=\"edituser.php?user_id=".$user_id."\"><img src=\"../images/edit.png\" alt=\"\" title=\""._EDIT." ".$user_id."\" /></a>";
					        }
					        
					        echo "<img src=\"".$user_image."\" title=\"".$user_id." - ".$type_user."\" alt=\"\" />";
					        if ($user_level <= 1) {
					            echo "<a href=\"adduser.php?action=deleteUser&user_id=".$user_id."\"><img src=\"../images/delete_on.gif\" alt=\"\" title=\""._DELETE." ".$user_id."\" onclick=\"return ConfirmDeleteUser('".$user_id."')\" /></a>";
					        } else {
					            echo "<img src=\"../images/delete_off.gif\" alt=\"\" title=\"n/a\" />";
					        }
						?>
       					</div></td>
       				</tr>
       			<?php } ?>
			</table>
		</div>
	</div>
</div>

<script>

    function ConfirmDeleteUser(user)
    {
        return confirm("<?php echo _WARNING . ": " . _ABOUTTODELETE ?>: " + user)
    }
 
</script>