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

include_once("functions.php");

    $settings = new Gratbrav\Torrentbug\Settings();
    $msgService = new Gratbrav\Torrentbug\Message\Service($cfg['user']);

    $to_user = filter_input(INPUT_POST, 'to_user', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

if (!empty($message) && !empty($to_user) && !empty($cfg['user'])) {

    $to_all = filter_input(INPUT_POST, 'to_all', FILTER_VALIDATE_INT);
    $force_read = filter_input(INPUT_POST, 'force_read', FILTER_VALIDATE_INT);

    $message = check_html($message, "nohtml");

    if ($to_all == 1) {
        SaveMessage($to_user, $cfg['user'], $message, $to_all, $force_read);
    } else {
        $settings = [
            'message' => $message,
            'from_user' => $cfg['user'],
            'to_user' => $to_user,
            'is_new' => 1,
            'force_read' => $force_read,
        ];
        $msgService->save($settings);
    }

    header('location: readmsg.php');
    exit;

} else {

    $to_user = filter_input(INPUT_GET, 'to_user', FILTER_SANITIZE_STRING);
    $rmid = filter_input(INPUT_GET, 'rmid', FILTER_VALIDATE_INT);

    if (!empty($rmid)) {
        $message = $msgService->getMessageById($rmid);

        $msgContent = _DATE. ': ' . date(_DATETIMEFORMAT, $message->getTime()) . "\n";
        $msgContent .= $message->getSender() . ' ' . _WROTE . ":\n\n";
        $msgContent .= $message->getMessage();

        $msgContent = '>' . str_replace("\n", "\n>", $msgContent);
        $msgContent = "\n\n\n" . $msgContent;
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
                    <textarea rows="10" name="message" id="message" class="form-control" wrap="hard" autofocus><?=$msgContent?></textarea>

                    <input type="hidden" name="to_all" value="0">
                    <input type="checkbox" name="to_all" id="to_all" value="1">
                    <label for="to_all"><?=_SENDTOALLUSERS?></label>

                    <input type="hidden" name="force_read" value="0">
                    <?php if (IsAdmin()) { ?>
                        <input type="checkbox" name="force_read" id="force_read" value="1">
                        <label for="force_read"><?=_FORCEUSERSTOREAD?></label>
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

<div style="text-align:center">[<a href="index.php"><?=_RETURNTOTORRENTS?></a>]</div>

<?php } ?>
