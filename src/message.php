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

include_once("config.php");
include_once("functions.php");
    
    $settings = new Class_Settings();

$to_user = getRequestVar('to_user');
$message = getRequestVar('message');

if (!empty($message) && !empty($to_user) && !empty($cfg['user']))
{
    $to_all = getRequestVar('to_all');
    if(!empty($to_all))
    {
        $to_all = 1;
    }
    else
    {
        $to_all = 0;
    }

    $force_read = getRequestVar('force_read');
    if(!empty($force_read) && IsAdmin())
    {
        $force_read = 1;
    }
    else
    {
        $force_read = 0;
    }


    $message = check_html($message, "nohtml");
    SaveMessage($to_user, $cfg['user'], $message, $to_all, $force_read);

    header("location: readmsg.php");
    exit;

} else {

    $rmid = getRequestVar('rmid');
    if (!empty($rmid)) {
        list($from_user, $message, $ip, $time) = GetMessage($rmid);
        $message = _DATE.": ".date(_DATETIMEFORMAT, $time)."\n".$from_user." "._WROTE.":\n\n".$message;
        $message = ">".str_replace("\n", "\n>", $message);
        $message = "\n\n\n".$message;
    }

    include_once 'header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-sm-12 bd-example" style="padding:16px;">

            <form name="theForm" method="post" action="message.php">
            <div class="form-group row">
                <label for="to_user" class="col-sm-2 col-form-label"><?=_TO?></label>
                <div class="col-sm-10">
                    <select name="to_user" id="to_user" class="form-control">
                    <?php
                        $users = GetUsers();
                        foreach ((array)$users as $user) {
                            $selected = ($user == $to_user) ? 'selected' : '';
                            echo '<option ' . $selected .'>' . htmlentities($user, ENT_QUOTES) . '</option>';
                        }
                    ?>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label for="message" class="col-sm-2 col-form-label"><?=_YOURMESSAGE?></label>
                <div class="col-sm-10">
                    <textarea rows="10" name="message" id="message" class="form-control" wrap="hard" autofocus><?=$message?></textarea>
                    <input type="Checkbox" name="to_all" value="1"><?php echo _SENDTOALLUSERS ?>
                    <?php if (IsAdmin()) { ?>
                        <input type="Checkbox" name="force_read" value="1"><?=_FORCEUSERSTOREAD?>
                    <?php } ?>
                </div>
            </div>
    
            <div class="form-group row">
                <div class="col-sm-10 offset-sm-2">
                    <button type="submit" class="btn btn-primary"><?=_SEND?></button>
                </div>
            </div>
            </form>

        </div>
    </div>
</div>

<div style="text-align:center">[<a href="index.php"><?php echo _RETURNTOTORRENTS ?></a>]</div>

<?php } ?>
