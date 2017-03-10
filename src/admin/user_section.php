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

                    $userService = new Gratbrav\Torrentbug\User\Service();
                    $users = $userService->getUsers();

                    foreach ($users as $user) {
				        $user_activity = GetActivityCount($user->getUserId());
				
				        if ($user_activity == 0) {
				            $user_percent = 0;
				        } else {
				            $user_percent = number_format(($user_activity/$total_activity)*100);
				        }
				        
				        $user_icon = "../images/user_offline.gif";
				        if (IsOnline($user->getUserId())) {
				            $user_icon = "../images/user.gif";
				        }
				
				        echo "<tr>";
				        if (IsUser($user->getUserId())) {
				            echo "<td><a href=\"../message.php?to_user=" . $user->getUserId() . "\"><img src=\"" . $user_icon . "\" alt=\"\" title=\""._SENDMESSAGETO." " . $user->getUserId() . "\" />" . $user->getUserId() . "</a></td>";
				        } else {
				            echo "<td><img src=\"".$user_icon."\" alt=\"\" title=\"n/a\" />".$user->getUserId()."</td>";
				        }
				        
				        echo "<td><div class=\"tiny\" style=\"text-align:right\">" . $user->getHits() . "</div></td>";
				        echo "<td>";
				       ?>
					        <table class="table table-striped">
						        <tr>
							        <td style="width:200">
							        	<progress class="progress progress-success" value="<?php echo $user_percent ?>" max="100" style="margin-bottom:0px"><?php echo $user_percent ?>%</progress>
							        </td>
							        <td style="text-align:right;width:40"><div class="tiny" style="text-align:right"><?php echo $user_activity ?></div></td>
							        <td style="text-align:right;width:40"><div class="tiny" style="text-align:right"><?php echo $user_percent ?>%</div></td>
							        <td style="text-align:right"><a href="activity.php?user_id=<?= $user->getUserId() ?>"><img src="../images/properties.png" alt="" title="<?= $user->getUserId() . "'s "._USERSACTIVITY ?>" /></a></td>
							        </tr>
						        </table>
					        
					        
					        </td>
        					<td class="tiny" style="text-align:center"><?php echo date(_DATEFORMAT, $user->getTimeCreated()) ?></td>
        					<td class="tiny" style="text-align:center"><?php echo date(_DATETIMEFORMAT, $user->getLastVisit()) ?></td>
        					<td><div style="text-align:right" class="tiny">
							<?php
					        $user_image = "../images/user.gif";
					        $type_user = _NORMALUSER;
					        if ($user->getUserLevel() == 1) {
					            $user_image = "../images/admin_user.gif";
					            $type_user = _ADMINISTRATOR;
					        }
					        
					        if ($user->getUserLevel() == 2) {
					            $user_image = "../images/superadmin.gif";
					            $type_user = _SUPERADMIN;
					        }
					        
					        if ($user->getUserLevel() <= 1 || IsSuperAdmin()) {
					            echo "<a href=\"edituser.php?uid=" . $user->getUid() . "\"><img src=\"../images/edit.png\" alt=\"\" title=\""._EDIT." ".$user->getUserId()."\" /></a>";
					        }
					        
					        echo "<img src=\"".$user_image."\" title=\"".$user->getUserId()." - ".$type_user."\" alt=\"\" />";
					        if ($user->getUserLevel() <= 1) {
					            echo "<a href=\"adduser.php?action=deleteUser&uid=" .$user->getUid() . "\"><img src=\"../images/delete_on.gif\" alt=\"\" title=\""._DELETE." " . $user->getUserId() . "\" onclick=\"return ConfirmDeleteUser('" . $user->getUserId() . "')\" /></a>";
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