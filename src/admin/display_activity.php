<?php
$sqlForSearch = '';

$userdisplay = $user;

if ($user != "") {
    $sqlForSearch .= "user_id='" . $user . "' AND ";
} else {
    $userdisplay = _ALLUSERS;
}

if ($srchFile != "") {
    $sqlForSearch .= "file like '%" . $srchFile . "%' AND ";
}

if ($srchAction != "") {
    $sqlForSearch .= "action like '%" . $srchAction . "%' AND ";
}

$offset = 50;
$inx = 0;
if (! isset($min))
    $min = 0;
$max = $min + $offset;
$output = "";
$morelink = "";

$sql = "SELECT user_id, file, action, ip, ip_resolved, user_agent, time FROM tf_log WHERE " . $sqlForSearch . "action!=" . $db->qstr($cfg["constants"]["hit"]) . " ORDER BY time desc";

$result = $db->SelectLimit($sql, $offset, $min);
while (list ($user_id, $file, $action, $ip, $ip_resolved, $user_agent, $time) = $result->FetchRow()) {
    $user_icon = $settings->get('base_url') . '/images/user_offline.gif';
    if (IsOnline($user_id)) {
        $user_icon = $settings->get('base_url') . '/images/user.gif';
    }
    
    $ip_info = htmlentities($ip_resolved, ENT_QUOTES) . "<br>" . htmlentities($user_agent, ENT_QUOTES);
    
    $output .= "<tr>";
    if (IsUser($user_id)) {
        $output .= "<td><a href=\"" . $settings->get('base_url') . "/message.php?to_user=" . $user_id . "\"><img src=\"" . $user_icon . "\" alt=\"\" title=\"" . _SENDMESSAGETO . " " . $user_id . "\" />" . $user_id . "</a>&nbsp;&nbsp;</td>";
    } else {
        $output .= "<td><img src=\"" . $user_icon . "\" alt=\"\" title=\"n/a\" />" . $user_id . "&nbsp;&nbsp;</td>";
    }
    $output .= "<td><div class=\"tiny\">" . htmlentities($action, ENT_QUOTES) . "</div></td>";
    $output .= "<td><div class=\"tiny\" style=\"text-align:left\">";
    $output .= htmlentities($file, ENT_QUOTES);
    $output .= "</div></td>";
    $output .= "<td><div class=\"tiny\" style=\"text-align:left\"><a href=\"javascript:void(0)\" onclick=\"return overlib('" . $ip_info . "<br>', STICKY, CSSCLASS);\" onmouseover=\"return overlib('" . $ip_info . "<br>', CSSCLASS);\" onmouseout=\"return nd();\" class=tiny><img src=\"" . $settings->get('base_url') . "/images/properties.png\" alt=\"\" />" . htmlentities($ip, ENT_QUOTES) . "</a></div></td>";
    $output .= "<td><div class=\"tiny\" style=\"text-align:center\">" . date(_DATETIMEFORMAT, $time) . "</div></td>";
    $output .= "</tr>";
    
    $inx ++;
}

if ($inx == 0) {
    $output = "<tr><td colspan=6><center><strong>-- " . _NORECORDSFOUND . " --</strong></center></td></tr>";
}

$prev = ($min - $offset);
if ($prev >= 0) {
    $prevlink = "<a href=\"activity.php?min=" . $prev . "&user_id=" . $user . "&srchFile=" . $srchFile . "&srchAction=" . $srchAction . "\">";
    $prevlink .= "&lt;&lt;" . $min . " " . _SHOWPREVIOUS . "]</a> &nbsp;";
}
if ($inx >= $offset) {
    $morelink = "<a href=\"activity.php?min=" . $max . "&user_id=" . $user . "&srchFile=" . $srchFile . "&srchAction=" . $srchAction . "\">";
    $morelink .= "[" . _SHOWMORE . "&gt;&gt;</a>";
}
?>
<div class="container">
    <div class="row">
        <div class="col-sm-12 bd-example">
            <form action="activity.php" name="searchForm" method="post">
                <table class="table table-striped">
                    <tr>
                        <td><strong><?php echo _ACTIVITYSEARCH ?></strong>&nbsp;&nbsp;&nbsp;
       						<?php echo _FILE ?>:
       						<input type="Text" name="srchFile"
                            value="<?php echo $srchFile ?>"
                            style="width: 30"> &nbsp;&nbsp;
       						<?php echo _ACTION ?>:
       						<select name="srchAction">
                                <option value="">-- <?php echo _ALL ?> --</option>
								<?php
        $selected = "";
        if (is_array($cfg["constants"])) {
            foreach ($cfg["constants"] as $action) {
                $selected = "";
                if ($action != $cfg["constants"]["hit"]) {
                    if ($srchAction == $action) {
                        $selected = "selected";
                    }
                    echo "<option value=\"" . htmlentities($action, ENT_QUOTES) . "\" " . $selected . ">" . htmlentities($action, ENT_QUOTES) . "</option>";
                }
            }
        }
        ?>
								</select>&nbsp;&nbsp;
								
        						<?php echo _USER ?>:
        						<select name="user_id">
                                <option value="">-- <?php echo _ALL ?> --</option>
								<?php
        $users = GetUsers();
        $selected = "";
        for ($inx = 0; $inx < sizeof($users); $inx ++) {
            $selected = "";
            if ($user == $users[$inx]) {
                $selected = "selected";
            }
            echo "<option value=\"" . htmlentities($users[$inx], ENT_QUOTES) . "\" " . $selected . ">" . htmlentities($users[$inx], ENT_QUOTES) . "</option>";
        }
        ?>
							  	</select> <input type="Submit" value="<?php echo _SEARCH ?>">

                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-sm-12 bd-example">
            <table class="table table-striped">
                <tr>
                    <th colspan="4"><img
                        src="<?php echo $settings->get('base_url') ?>/images/properties.png"
                        alt="" />&nbsp;&nbsp;
   						<?php echo _ACTIVITYLOG . " " . $settings->get('days_to_keep') . " " . _DAYS . " (" . $userdisplay . ")"?>
   					</th>
                    <th style="text-align: right">
					    <?php
        if (! empty($prevlink) && ! empty($morelink)) {
            echo $prevlink . $morelink;
        } else 
            if (! empty($prevlink)) {
                echo $prevlink;
            } else 
                if (! empty($prevlink)) {
                    echo $morelink;
                } else {}
        ?>
   					</th>
                </tr>
                <tr>
                    <th><?php echo _USER ?></th>
                    <th><?php echo _ACTION ?></th>
                    <th><?php echo _FILE ?></th>
                    <th style="width: 13%"><?php echo _IP ?></th>
                    <th style="width: 15%"><?php echo _TIMESTAMP ?></th>
                </tr>
				<?php
    echo $output;
    
    if (! empty($prevlink) || ! empty($morelink)) {
        echo "<tr>";
        echo "<td colspan=3 style=\"text-align:left\">";
        if (! empty($prevlink))
            echo $prevlink;
        echo "</td><td colspan=3 style=\"text-align:right\">";
        if (! empty($morelink))
            echo $morelink;
        echo "</td>";
        echo "</tr>";
    }
    ?>
		    </table>
        </div>
    </div>
</div>

<div id="overDiv"
    style="position: absolute; visibility: hidden; z-index: 1000;"></div>
<script>
    var ol_closeclick = "1";
    var ol_close = "<font color=#ffffff><b>X</b></font>";
    var ol_fgclass = "fg";
    var ol_bgclass = "bg";
    var ol_captionfontclass = "overCaption";
    var ol_closefontclass = "overClose";
    var ol_textfontclass = "overBody";
    var ol_cap = "&nbsp;IP Info";
</script>
<script src="<?php echo $settings->get('base_url') ?>/overlib.js"></script>
