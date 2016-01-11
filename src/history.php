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

	include_once './Class/autoload.php';

include_once("config.php");
include_once("functions.php");

	$settings = new Class_Settings();
//****************************************************************************
// showIndex -- default view
//****************************************************************************
function showIndex($min)
{
    DisplayHead(_UPLOADHISTORY);

    // Display Activity
    displayActivity($min);

    DisplayFoot();
}


//****************************************************************************
// displayActivity -- displays History
//****************************************************************************
function displayActivity($min=0)
{
    global $cfg, $db, $settings;

    $offset = 50;
    $inx = 0;
    $max = $min+$offset;
    $output = "";
    $morelink = "";

    $sql = "SELECT user_id, file, time FROM tf_log WHERE action=".$db->qstr($cfg["constants"]["url_upload"])." OR action=".$db->qstr($cfg["constants"]["file_upload"])." ORDER BY time desc";

    $result = $db->SelectLimit($sql, $offset, $min);
    while(list($user_id, $file, $time) = $result->FetchRow())
    {
        $user_icon = "images/user_offline.gif";
        if (IsOnline($user_id))
        {
            $user_icon = "images/user.gif";
        }

        $output .= "<tr>";
        $output .= "<td><a href=\"message.php?to_user=".$user_id."\"><img src=\"".$user_icon."\" title=\"".$user_id."\" alt=\"\">".$user_id."</a>&nbsp;&nbsp;</td>";
        $output .= "<td>";
        $output .= $file;
        $output .= "</td>";
        $output .= "<td style=\"text-align:center;\">".date(_DATETIMEFORMAT, $time)."</td>";
        $output .= "</tr>";

        $inx++;
    }

    if($inx == 0)
    {
        $output = "<tr><td colspan=6><center><strong>-- "._NORECORDSFOUND." --</strong></center></td></tr>";
    }

    $prev = ($min-$offset);
    if ($prev>=0)
    {
        $prevlink = "<a href=\"history.php?min=".$prev."\">";
        $prevlink .= "<font class=\"TinyWhite\">&lt;&lt;".$min." "._SHOWPREVIOUS."]</font></a> &nbsp;";
    }
    $next=$min+$offset;
    if ($inx>=$offset)
    {
        $morelink = "<a href=\"history.php?min=".$max."\">";
        $morelink .= "<font class=\"TinyWhite\">["._SHOWMORE."&gt;&gt;</font></a>";
    }

    echo "<table class=\"table table-striped\">";
    echo "<tr><th colspan=\"2\">";
    echo "<img src=\"images/properties.png\" alt=\"\">&nbsp;&nbsp;" . _UPLOADACTIVITY . " (" . $settings->get('days_to_keep') . " " . _DAYS . ")";
	echo "</th><th style=\"text-align:right\">";

    if(!empty($prevlink) && !empty($morelink))
    echo $prevlink.$morelink;
    elseif(!empty($prevlink))
        echo $prevlink;
    elseif(!empty($morelink))
        echo $morelink;
    else
        echo "";

    echo "</th></tr>";
    echo "<tr>";
    echo "<th>"._USER."</th>";
    echo "<th>"._FILE."</th>";
    echo "<th>"._TIMESTAMP."</th>";
    echo "</tr>";

    echo $output;

    if(!empty($prevlink) || !empty($morelink))
    {
        echo "<tr><td colspan=6 bgcolor=\"".$cfg["table_header_bg"]."\">";
        echo "<table width=\"100%\" cellpadding=0 cellspacing=0 border=0><tr><td align=\"left\">";
        echo $prevlink;
        echo "</td><td align=\"right\">";
        echo $morelink;
        echo "</td></tr></table>";
        echo "</td></tr>";
    }

    echo "</table>";
}

?>
<?php include_once 'header.php' ?>

<div class="container">
	<div class="row">
		<div class="col-sm-12 bd-example">
			<?php displayActivity($min); ?>
		</div>
	</div>
</div>
    
<?php echo DisplayTorrentFluxLink(); ?>
