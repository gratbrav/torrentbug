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

// Start Session and grab user
session_name("Torrentbug");
session_start();

if (isset($_SESSION['user'])) {
    $cfg["user"] = strtolower($_SESSION['user']);
} else {
    $cfg["user"] = "";
}

include_once ('db.php');
include_once ("settingsfunctions.php");

// Create Connection.
$db = getdb();

// Free space in MB
$cfg["free_space"] = @disk_free_space($settings->get('path')) / (1024 * 1024);

Authenticate();

include_once ("language/" . $cfg["language_file"]);
include_once ("themes/" . $cfg["theme"] . "/index.php");

$options = [
    'user_id' => $cfg['user'],
    'file' => $_SERVER['PHP_SELF'],
    'action' => $cfg["constants"]["hit"]
];
$log = new \Gratbrav\Torrentbug\Log\Service();
$log->save($options);

PruneDB();

// is there a stat and torrent dir? If not then it will create it.
checkTorrentPath();

// **********************************************************************************
// START FUNCTIONS HERE
// **********************************************************************************

// *********************************************************
function getLinkSortOrder($lid)
{
    global $db;
    
    // Get Current sort order index of link with this link id:
    $sql = "SELECT sort_order FROM tf_links WHERE lid=$lid";
    $rtnValue = $db->GetOne($sql);
    showError($db, $sql);
    
    return $rtnValue;
}

// *********************************************************
// avddelete()
function avddelete($file)
{
    $file = html_entity_decode($file, ENT_QUOTES);
    chmod($file, 0777);
    if (@is_dir($file)) {
        $handle = @opendir($file);
        while ($filename = readdir($handle)) {
            if ($filename != "." && $filename != "..") {
                avddelete($file . "/" . $filename);
            }
        }
        closedir($handle);
        @rmdir($file);
    } else {
        @unlink($file);
    }
}

// *********************************************************
// Authenticate()
function Authenticate()
{
    global $cfg, $settings;
    
    $create_time = time();
    
    if (! isset($_SESSION['user'])) {
        header('location: ' . $settings->get('base_url') . '/login.php');
        exit();
    }
    
    if ($_SESSION['user'] == md5($cfg["pagetitle"])) {
        // user changed password and needs to login again
        header('location: ' . $settings->get('base_url') . '/logout.php');
        exit();
    }
    
    $userService = new Gratbrav\Torrentbug\User\Service();
    $user = $userService->getUserById($_SESSION['uid']);
    
    if (!$user->getUid()) {
        $options = [
            'user_id' => $user->getUserId(),
            'file' => 'FAILED AUTH: ' . $user->getUserId(),
            'action' => $cfg["constants"]["error"]
        ];
        $log = new \Gratbrav\Torrentbug\Log\Service();
        $log->save($options);
        
        session_destroy();
        header('location: ' . $settings->get('base_url') . '/login.php');
        exit();
    }
    
    // Check for valid theme
    if (! ereg('^[^./][^/]*$', $user->getTheme())) {
        $options = [
            'user_id' => $user->getUserId(),
            'file' => 'THEME VARIABLE CHANGE ATTEMPT: ' . $user->getTheme() . ' from ' . $user->getUserId(),
            'action' => $cfg["constants"]["error"]
        ];
        $log = new \Gratbrav\Torrentbug\Log\Service();
        $log->save($options);
        
        $user->setTheme($settings->get('default_theme'));
    }
    
    // Check for valid language file
    if (! ereg('^[^./][^/]*$', $user->getLanguageFile())) {
        
        $options = [
            'user_id' => $user->getUserId(),
            'file' => 'LANGUAGE VARIABLE CHANGE ATTEMPT: ' . $user->getLanguageFile() . ' from ' . $user->getUserId(),
            'action' => $cfg["constants"]["error"]
        ];
        $log = new \Gratbrav\Torrentbug\Log\Service();
        $log->save($options);
        
        $user->setLanguageFile($settings->get('default_language'));
    }
    
    if (! is_dir("themes/" . $user->getTheme())) {
        $user->setTheme($settings->get('default_theme'));
    }
    
    // Check for valid language file
    if (! is_file("language/" . $user->getLanguageFile())) {
        $user->setLanguageFile($settings->get('default_language'));
    }
    
    $user->setHits($user->getHits() + 1);
    $user->setLastVisit((new \DateTime())->format('Y-m-d'));
    
    $userService->save($user);
    
    $cfg['theme'] = $user->getTheme();
    $cfg['language_file'] = $user->getLanguageFile();
}

// *********************************************************
// SaveMessage
function SaveMessage($to_user, $from_user, $message, $to_all = 0, $force_read = 0)
{
    global $cfg, $db;
    
    $message = str_replace(array(
        "'"
    ), "", $message);
    
    $message .= "\n\n__________________________________\n*** " . _MESSAGETOALL . " ***";
    $sql = 'select user_id from tf_users';
    $result = $db->Execute($sql);
    showError($db, $sql);
    
    while ($row = $result->FetchRow()) {
        $rec = array(
            'to_user' => $row['user_id'],
            'from_user' => $from_user,
            'message' => $message,
            'IsNew' => 1,
            'ip' => $cfg['ip'],
            'time' => time(),
            'force_read' => $force_read
        );
        
        $sql = $db->GetInsertSql('tf_messages', $rec);
        
        $result2 = $db->Execute($sql);
        showError($db, $sql);
    }
}

// *********************************************************
function PruneDB()
{
    global $cfg, $db, $settings;
    
    // Prune LOG
    $testTime = time() - ($settings->get('days_to_keep') * 86400); // 86400 is one day in seconds
    $sql = "delete from tf_log where time < " . $db->qstr($testTime);
    $result = $db->Execute($sql);
    showError($db, $sql);
    unset($result);
    
    $testTime = time() - ($settings->get('minutes_to_keep') * 60);
    $sql = "delete from tf_log where time < " . $db->qstr($testTime) . " and action=" . $db->qstr($cfg["constants"]["hit"]);
    $result = $db->Execute($sql);
    showError($db, $sql);
    unset($result);
}

// *********************************************************
function IsOnline($user)
{
    global $cfg, $db;
    
    $online = false;
    
    $sql = "SELECT count(*) FROM tf_log WHERE user_id=" . $db->qstr($user) . " AND action=" . $db->qstr($cfg["constants"]["hit"]);
    
    $number_hits = $db->GetOne($sql);
    showError($db, $sql);
    
    if ($number_hits > 0) {
        $online = true;
    }
    
    return $online;
}

// *********************************************************
function IsUser($user)
{
    global $cfg, $db;
    
    $isUser = false;
    
    $sql = "SELECT count(*) FROM tf_users WHERE user_id=" . $db->qstr($user);
    $number_users = $db->GetOne($sql);
    
    if ($number_users > 0) {
        $isUser = true;
    }
    
    return $isUser;
}

// *********************************************************
function getOwner($file)
{
    global $cfg, $db;
    
    $rtnValue = "n/a";
    
    // Check log to see what user has a history with this file
    $sql = "SELECT user_id FROM tf_log WHERE file=" . $db->qstr($file) . " AND (action=" . $db->qstr($cfg["constants"]["file_upload"]) . " OR action=" . $db->qstr($cfg["constants"]["url_upload"]) . " OR action=" . $db->qstr($cfg["constants"]["reset_owner"]) . ") ORDER  BY time DESC";
    $user_id = $db->GetOne($sql);
    
    if ($user_id != "") {
        $rtnValue = $user_id;
    } else {
        // try and get the owner from the stat file
        $rtnValue = resetOwner($file);
    }
    
    return $rtnValue;
}

// *********************************************************
function resetOwner($file)
{
    global $cfg, $db, $settings;
    include_once ("AliasFile.php");
    
    // log entry has expired so we must renew it
    $rtnValue = "";
    
    $alias = getAliasName($file) . ".stat";
    
    if (file_exists($settings->get('torrent_file_path') . $alias)) {
        $af = new AliasFile($settings->get('torrent_file_path') . $alias);
        
        if (IsUser($af->torrentowner)) {
            // We have an owner!
            $rtnValue = $af->torrentowner;
        } else {
            // no owner found, so the super admin will now own it
            $rtnValue = GetSuperAdmin();
        }
        
        $host_resolved = $cfg['ip'];
        $create_time = time();
        
        $rec = array(
            'user_id' => $rtnValue,
            'file' => $file,
            'action' => $cfg["constants"]["reset_owner"],
            'ip' => $cfg['ip'],
            'ip_resolved' => $host_resolved,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'time' => $create_time
        );
        
        $sTable = 'tf_log';
        $sql = $db->GetInsertSql($sTable, $rec);
        
        // add record to the log
        $result = $db->Execute($sql);
        showError($db, $sql);
    }
    
    return $rtnValue;
}

// *********************************************************
function getCookie($cid)
{
    global $cfg, $db;
    
    $rtnValue = "";
    
    $sql = "SELECT host, data FROM tf_cookies WHERE cid=" . $cid;
    $rtnValue = $db->GetAll($sql);
    
    return $rtnValue[0];
}

// *********************************************************
function getAllCookies($uid)
{
    global $cfg, $db;
    
    $rtnValue = "";
    
    $sql = "SELECT c.cid, c.host, c.data FROM tf_cookies AS c, tf_users AS u WHERE u.uid=c.uid AND u.user_id='" . $uid . "' order by host";
    $rtnValue = $db->GetAll($sql);
    
    return $rtnValue;
}

// ***************************************************************************
// Delete Cookie Host Information
function deleteCookieInfo($cid)
{
    global $db;
    $sql = "delete from tf_cookies where cid=" . $cid;
    $result = $db->Execute($sql);
    showError($db, $sql);
}

// ***************************************************************************
// addCookieInfo - Add New Cookie Host Information
function addCookieInfo($newCookie)
{
    global $db, $cfg;
    // Get uid of user
    $sql = "SELECT uid FROM tf_users WHERE user_id = '" . $cfg["user"] . "'";
    $uid = $db->GetOne($sql);
    $sql = "INSERT INTO tf_cookies ( uid, host, data ) VALUES ( " . $uid . ", " . $db->qstr($newCookie["host"]) . ", " . $db->qstr($newCookie["data"]) . " )";
    $db->Execute($sql);
    showError($db, $sql);
}

// ***************************************************************************
// modCookieInfo - Modify Cookie Host Information
function modCookieInfo($cid, $newCookie)
{
    global $db;
    $sql = "UPDATE tf_cookies SET host='" . $newCookie["host"] . "', data='" . $newCookie["data"] . "' WHERE cid=" . $cid;
    $db->Execute($sql);
    showError($db, $sql);
}

// *********************************************************
function getSite($lid)
{
    global $cfg, $db;
    
    $rtnValue = "";
    
    $sql = "SELECT sitename FROM tf_links WHERE lid=" . $lid;
    $rtnValue = $db->GetOne($sql);
    
    return $rtnValue;
}

// *********************************************************
function getLink($lid)
{
    global $cfg, $db;
    
    $rtnValue = "";
    
    $sql = "SELECT url FROM tf_links WHERE lid=" . $lid;
    $rtnValue = $db->GetOne($sql);
    
    return $rtnValue;
}

// *********************************************************
function getRSS($rid)
{
    global $cfg, $db;
    
    $rtnValue = "";
    
    $sql = "SELECT url FROM tf_rss WHERE rid=" . $rid;
    $rtnValue = $db->GetOne($sql);
    
    return $rtnValue;
}

// *********************************************************
function IsOwner($user, $owner)
{
    $rtnValue = false;
    
    if (strtolower($user) == strtolower($owner)) {
        $rtnValue = true;
    }
    
    return $rtnValue;
}

// *********************************************************
function GetActivityCount($user = "")
{
    global $cfg, $db;
    
    $count = 0;
    $for_user = "";
    
    if ($user != "") {
        $for_user = "user_id=" . $db->qstr($user) . " AND ";
    }
    
    $sql = "SELECT count(*) FROM tf_log WHERE " . $for_user . "(action=" . $db->qstr($cfg["constants"]["file_upload"]) . " OR action=" . $db->qstr($cfg["constants"]["url_upload"]) . ")";
    $count = $db->GetOne($sql);
    
    return $count;
}

// *********************************************************
function GetSpeedValue($inValue)
{
    $rtnValue = 0;
    $arTemp = split(" ", trim($inValue));
    
    if (is_numeric($arTemp[0])) {
        $rtnValue = $arTemp[0];
    }
    return $rtnValue;
}

// ***************************************************************************
// Is User SUPER Admin
// user is Super Admin if level is higher than 1
function IsSuperAdmin($user = "")
{
    global $cfg, $db;
    
    $isAdmin = false;
    
    if ($user == "") {
        $user = $cfg["user"];
    }
    
    $sql = "SELECT user_level FROM tf_users WHERE user_id=" . $db->qstr($user);
    $user_level = $db->GetOne($sql);
    
    if ($user_level > 1) {
        $isAdmin = true;
    }
    return $isAdmin;
}

// ***************************************************************************
// Get Themes data in an array
function GetThemes()
{
    global $settings;
    
    $arThemes = array();
    $dir = $settings->get('document_root') . "/themes/";
    
    $handle = opendir($dir);
    while ($entry = readdir($handle)) {
        if (is_dir($dir . $entry) && ($entry != "." && $entry != "..")) {
            array_push($arThemes, $entry);
        }
    }
    closedir($handle);
    
    sort($arThemes);
    
    return $arThemes;
}

// ***************************************************************************
// Get Languages in an array
function GetLanguages()
{
    global $settings;
    
    $arLanguages = array();
    $dir = $settings->get('document_root') . "/language/";
    
    $handle = opendir($dir);
    while ($entry = readdir($handle)) {
        if (is_file($dir . $entry) && (strcmp(strtolower(substr($entry, strlen($entry) - 4, 4)), ".php") == 0)) {
            array_push($arLanguages, $entry);
        }
    }
    closedir($handle);
    
    sort($arLanguages);
    
    return $arLanguages;
}

// ***************************************************************************
// Get Language name from file name
function GetLanguageFromFile($inFile)
{
    $rtnValue = "";
    
    $rtnValue = str_replace("lang-", "", $inFile);
    $rtnValue = str_replace(".php", "", $rtnValue);
    
    return $rtnValue;
}

// ***************************************************************************
// Update User -- used by admin
function updateThisUser($user_id, $org_user_id, $pass1, $userType, $hideOffline)
{
    global $db;
    
    if ($hideOffline == "") {
        $hideOffline = 0;
    }
    
    $sql = 'select * from tf_users where user_id = ' . $db->qstr($org_user_id);
    $rs = $db->Execute($sql);
    showError($db, $sql);
    
    $rec = array();
    $rec['user_id'] = $user_id;
    $rec['user_level'] = $userType;
    $rec['hide_offline'] = $hideOffline;
    
    if ($pass1 != "") {
        $rec['password'] = md5($pass1);
    }
    
    $sql = $db->GetUpdateSQL($rs, $rec);
    
    if ($sql != "") {
        $result = $db->Execute($sql);
        showError($db, $sql);
    }
    
    // if the original user id and the new id do not match, we need to update messages and log
    if ($user_id != $org_user_id) {
        $sql = "UPDATE tf_messages SET to_user=" . $db->qstr($user_id) . " WHERE to_user=" . $db->qstr($org_user_id);
        $result = $db->Execute($sql);
        showError($db, $sql);
        
        $sql = "UPDATE tf_messages SET from_user=" . $db->qstr($user_id) . " WHERE from_user=" . $db->qstr($org_user_id);
        $result = $db->Execute($sql);
        showError($db, $sql);
        
        $sql = "UPDATE tf_log SET user_id=" . $db->qstr($user_id) . " WHERE user_id=" . $db->qstr($org_user_id);
        $result = $db->Execute($sql);
        showError($db, $sql);
    }
}

// ***************************************************************************
// changeUserLevel Changes the Users Level
function changeUserLevel($user_id, $level)
{
    global $db;
    
    $sql = 'select * from tf_users where user_id = ' . $db->qstr($user_id);
    $rs = $db->Execute($sql);
    showError($db, $sql);
    
    $rec = array(
        'user_level' => $level
    );
    $sql = $db->GetUpdateSQL($rs, $rec);
    $result = $db->Execute($sql);
    showError($db, $sql);
}

// ***************************************************************************
// addNewLink - Add New Link
function addNewLink($newLink, $newSite)
{
    global $db;
    // Link sort order index:
    $idx = - 1;
    
    // Get current highest link index:
    $sql = "SELECT sort_order FROM tf_links ORDER BY sort_order DESC";
    $result = $db->SelectLimit($sql, 1);
    showError($db, $sql);
    
    if ($result->fields === false) {
        // No links currently in db:
        $idx = 0;
    } else {
        $idx = $result->fields["sort_order"] + 1;
    }
    
    $rec = array(
        'url' => $newLink,
        'sitename' => $newSite,
        'sort_order' => $idx
    );
    $sTable = 'tf_links';
    $sql = $db->GetInsertSql($sTable, $rec);
    $db->Execute($sql);
    showError($db, $sql);
}

// ***************************************************************************
// addNewRSS - Add New RSS Link
function addNewRSS($newRSS)
{
    global $db;
    $rec = array(
        'url' => $newRSS
    );
    $sTable = 'tf_rss';
    $sql = $db->GetInsertSql($sTable, $rec);
    $db->Execute($sql);
    showError($db, $sql);
}

// ***************************************************************************
// UpdateUserProfile
function UpdateUserProfile($user_id, $pass1, $hideOffline, $theme, $language)
{
    global $cfg, $db;
    
    if (empty($hideOffline) || $hideOffline == "" || ! isset($hideOffline)) {
        $hideOffline = "0";
    }
    
    // update values
    $rec = array();
    
    if ($pass1 != "") {
        $rec['password'] = md5($pass1);
        
        $options = [
            'user_id' => $cfg['user'],
            'file' => _PASSWORD,
            'action' => $cfg["constants"]["update"]
        ];
        $log = new \Gratbrav\Torrentbug\Log\Service();
        $log->save($options);
    }
    
    $sql = 'select * from tf_users where user_id = ' . $db->qstr($user_id);
    $rs = $db->Execute($sql);
    showError($db, $sql);
    
    $rec['hide_offline'] = $hideOffline;
    $rec['theme'] = $theme;
    $rec['language_file'] = $language;
    
    $sql = $db->GetUpdateSQL($rs, $rec);
    
    $result = $db->Execute($sql);
    showError($db, $sql);
}

// ***************************************************************************
// Get Users in an array
function GetUsers()
{
    global $cfg, $db;
    
    $user_array = array();
    
    $sql = "select user_id from tf_users order by user_id";
    $user_array = $db->GetCol($sql);
    showError($db, $sql);
    return $user_array;
}

// ***************************************************************************
// Get Super Admin User ID as a String
function GetSuperAdmin()
{
    global $cfg, $db;
    
    $rtnValue = "";
    
    $sql = "select user_id from tf_users WHERE user_level=2";
    $rtnValue = $db->GetOne($sql);
    showError($db, $sql);
    return $rtnValue;
}

// ***************************************************************************
// Get Links in an array
function GetLinks()
{
    global $cfg, $db;
    
    $link_array = array();
    
    $link_array = $db->GetAssoc("SELECT lid, url, sitename, sort_order FROM tf_links ORDER BY sort_order");
    return $link_array;
}

// ***************************************************************************
// Get RSS Links in an array
function GetRSSLinks()
{
    global $cfg, $db;
    
    $link_array = array();
    
    $sql = "SELECT rid, url FROM tf_rss ORDER BY rid";
    $link_array = $db->GetAssoc($sql);
    showError($db, $sql);
    
    return $link_array;
}

// ***************************************************************************
// Build Search Engine Drop Down List
function buildSearchEngineDDL($selectedEngine = 'PirateBay', $autoSubmit = false)
{
    global $settings;
    $output = "<select name=\"searchEngine\" ";
    if ($autoSubmit) {
        $output .= "onchange=\"this.form.submit();\" ";
    }
    // $output .= " STYLE=\"width: 125px\">";
    $output .= " STYLE=\"margin-top:6px;\" ";
    $output .= " class=\"form-control\" >";
    $handle = opendir($settings->get('document_root') . "/searchEngines");
    while ($entry = readdir($handle)) {
        $entrys[] = $entry;
    }
    natcasesort($entrys);
    
    foreach ($entrys as $entry) {
        if ($entry != "." && $entry != ".." && substr($entry, 0, 1) != ".")
            if (strpos($entry, "Engine.php")) {
                $tmpEngine = str_replace("Engine", '', substr($entry, 0, strpos($entry, ".")));
                $output .= "<option";
                if ($selectedEngine == $tmpEngine) {
                    $output .= " selected";
                }
                $output .= ">" . str_replace("Engine", '', substr($entry, 0, strpos($entry, "."))) . "</option>";
            }
    }
    $output .= "</select>\n";
    
    return $output;
}

// ***************************************************************************
// Build Search Engine Links
function buildSearchEngineLinks($selectedEngine = 'PirateBay')
{
    global $cfg, $settings;
    
    $settingsNeedsSaving = false;
    $options['searchEngineLinks'] = Array();
    
    $output = '';
    
    if ($settings->get('searchEngineLinks') == null || ! is_array($settings->get('searchEngineLinks'))) {
        $settings->save($options);
    }
    
    $handle = opendir("./searchEngines");
    while ($entry = readdir($handle)) {
        $entrys[] = $entry;
    }
    natcasesort($entrys);
    
    foreach ($entrys as $entry) {
        if ($entry != "." && $entry != ".." && substr($entry, 0, 1) != ".")
            if (strpos($entry, "Engine.php")) {
                $tmpEngine = str_replace("Engine", '', substr($entry, 0, strpos($entry, ".")));
                
                if (array_key_exists($tmpEngine, $settings->get('searchEngineLinks'))) {
                    $hreflink = $settings->get('searchEngineLinks')[$tmpEngine];
                    $options['searchEngineLinks'][$tmpEngine] = $hreflink;
                } else {
                    $hreflink = getEngineLink($tmpEngine);
                    $options['searchEngineLinks'][$tmpEngine] = $hreflink;
                    $settingsNeedsSaving = true;
                }
                
                if (strlen($hreflink) > 0) {
                    $output .= "<a href=\"http://" . $hreflink . "/\" target=\"_blank\">";
                    if ($selectedEngine == $tmpEngine) {
                        $output .= "<b>" . $hreflink . "</b>";
                    } else {
                        $output .= $hreflink;
                    }
                    $output .= "</a><br>\n";
                }
            }
    }
    
    if (count($options['searchEngineLinks'], COUNT_RECURSIVE) != count($settings->get('searchEngineLinks'), COUNT_RECURSIVE)) {
        $settingsNeedsSaving = true;
    }
    
    if ($settingsNeedsSaving) {
        natcasesort($options['searchEngineLinks']);
        
        $settings->save($options);
    }
    
    return $output;
}

function getEngineLink($searchEngine)
{
    $tmpLink = '';
    $engineFile = 'searchEngines/' . $searchEngine . 'Engine.php';
    if (is_file($engineFile)) {
        $fp = @fopen($engineFile, 'r');
        if ($fp) {
            $tmp = fread($fp, filesize($engineFile));
            @fclose($fp);
            
            $tmp = substr($tmp, strpos($tmp, '$this->mainURL'), 100);
            $tmp = substr($tmp, strpos($tmp, "=") + 1);
            $tmp = substr($tmp, 0, strpos($tmp, ";"));
            $tmpLink = trim(str_replace(array(
                "'",
                "\""
            ), "", $tmp));
        }
    }
    return $tmpLink;
}

// ***************************************************************************
// ***************************************************************************
// Display Functions

// ***************************************************************************
// ***************************************************************************
// Display the header portion of admin views
function DisplayHead($subTopic, $showButtons = true, $refresh = "", $percentdone = "")
{
    global $cfg;
    ?>

<html>
<HEAD>
<TITLE><?php echo $percentdone.$cfg["pagetitle"] ?></TITLE>
<link rel="icon" href="images/favicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
<LINK REL="StyleSheet"
    HREF="themes/<?php echo $cfg["theme"] ?>/style.css" TYPE="text/css">
<META HTTP-EQUIV="Pragma" CONTENT="no-cache"
    charset="<?php echo _CHARSET ?>">

    <?php
    if ($refresh != "") {
        echo "<meta http-equiv=\"REFRESH\" content=\"" . $refresh . "\">";
    }
    ?>
    </HEAD>

<body topmargin="8" leftmargin="5"
    bgcolor="<?php echo $cfg["main_bgcolor"] ?>">

    <div align="center">
        <table border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td>

                    <table border="1"
                        bordercolor="<?php echo $cfg["table_border_dk"] ?>"
                        cellpadding="4" cellspacing="0">
                        <tr>
                            <td
                                bgcolor="<?php echo $cfg["main_bgcolor"] ?>"
                                background="themes/<?php echo $cfg["theme"] ?>/images/bar.gif">
        <?php DisplayTitleBar($cfg["pagetitle"]." - ".$subTopic, $showButtons); ?>
        </td>
                        </tr>
                        <tr>
                            <td
                                bgcolor="<?php echo $cfg["table_header_bg"] ?>">
                                <div align="center">

                                    <table width="100%"
                                        bgcolor="<?php echo $cfg["body_data_bg"] ?>">
                                        <tr>
                                            <td>
<?php
}

// ***************************************************************************
// ***************************************************************************
// Display the footer portion
function DisplayFoot($showReturn = true)
{
    global $cfg;
    ?>
     </td>
                                        </tr>
                                    </table>
<?php
    if ($showReturn) {
        echo "[<a href=\"index.php\">" . _RETURNTOTORRENTS . "</a>]";
        echo "</form>";
    }
    ?>
    </div>
                            </td>
                        </tr>
                    </table>

                </td>
            </tr>
        </table>
    </div>

</body>
</html>

<?php
}

// ***************************************************************************
// ***************************************************************************
// Dipslay Title Bar
// 2004-12-09 PFM: now using adodb.
function DisplayTitleBar($pageTitleText, $showButtons = true)
{
    global $cfg, $db;
    ?>
<table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td align="left"><font class="title"><?php echo $pageTitleText ?></font></td>

    <?php
    if ($showButtons) {
        echo "<td align=right>";
        // Top Buttons
        echo "&nbsp;&nbsp;";
        
        echo "<a href=\"index.php\"><img src=\"themes/" . $cfg["theme"] . "/images/home.gif\" width=49 height=13 title=\"" . _TORRENTS . "\"></a>&nbsp;";
        echo "<a href=\"dir.php\"><img src=\"themes/" . $cfg["theme"] . "/images/directory.gif\" width=49 height=13 title=\"" . _DIRECTORYLIST . "\"></a>&nbsp;";
        echo "<a href=\"history.php\"><img src=\"themes/" . $cfg["theme"] . "/images/history.gif\" width=49 height=13 title=\"" . _UPLOADHISTORY . "\"></a>&nbsp;";
        echo "<a href=\"profile.php\"><img src=\"themes/" . $cfg["theme"] . "/images/profile.gif\" width=49 height=13 title=\"" . _MYPROFILE . "\"></a>&nbsp;";
        
        // Does the user have messages?
        $sql = "select count(*) from tf_messages where to_user='" . $cfg['user'] . "' and IsNew=1";
        
        $number_messages = $db->GetOne($sql);
        showError($db, $sql);
        if ($number_messages > 0) {
            // We have messages
            $message_image = "themes/" . $cfg["theme"] . "/images/messages_on.gif";
        } else {
            // No messages
            $message_image = "themes/" . $cfg["theme"] . "/images/messages_off.gif";
        }
        
        echo "<a href=\"readmsg.php\"><img src=\"" . $message_image . "\" width=49 height=13 title=\"" . _MESSAGES . "\"></a>";
        
        if ($_SESSION['is_admin']) {
            echo "&nbsp;<a href=\"admin.php\"><img src=\"themes/" . $cfg["theme"] . "/images/admin.gif\" width=49 height=13 title=\"" . _ADMINISTRATION . "\"></a>";
        }
        
        echo "&nbsp;<a href=\"logout.php\"><img src=\"images/logout.gif\" width=13 height=12 title=\"Logout\"></a>";
    }
    ?>
            </td>
    </tr>
</table>
<?php
}

// ***************************************************************************
// ***************************************************************************
// Removes HTML from Messages
function check_html($str, $strip = "")
{
    /* The core of this code has been lifted from phpslash */
    /* which is licenced under the GPL. */
    if ($strip == "nohtml") {
        $AllowableHTML = array(
            ''
        );
    }
    $str = stripslashes($str);
    $str = eregi_replace("<[[:space:]]*([^>]*)[[:space:]]*>", '<\\1>', $str);
    // Delete all spaces from html tags .
    $str = eregi_replace("<a[^>]*href[[:space:]]*=[[:space:]]*\"?[[:space:]]*([^\" >]*)[[:space:]]*\"?[^>]*>", '<a href="\\1">', $str);
    // Delete all attribs from Anchor, except an href, double quoted.
    $str = eregi_replace("<[[:space:]]* img[[:space:]]*([^>]*)[[:space:]]*>", '', $str);
    // Delete all img tags
    $str = eregi_replace("<a[^>]*href[[:space:]]*=[[:space:]]*\"?javascript[[:punct:]]*\"?[^>]*>", '', $str);
    // Delete javascript code from a href tags -- Zhen-Xjell @ http://nukecops.com
    $tmp = "";
    
    while (ereg("<(/?[[:alpha:]]*)[[:space:]]*([^>]*)>", $str, $reg)) {
        $i = strpos($str, $reg[0]);
        $l = strlen($reg[0]);
        if ($reg[1][0] == "/") {
            $tag = strtolower(substr($reg[1], 1));
        } else {
            $tag = strtolower($reg[1]);
        }
        if ($a = $AllowableHTML[$tag]) {
            if ($reg[1][0] == "/") {
                $tag = "</$tag>";
            } elseif (($a == 1) || ($reg[2] == "")) {
                $tag = "<$tag>";
            } else {
                // Place here the double quote fix function.
                $attrb_list = delQuotes($reg[2]);
                // A VER
                $attrb_list = ereg_replace("&", "&amp;", $attrb_list);
                $tag = "<$tag" . $attrb_list . ">";
            } // Attribs in tag allowed
        } else {
            $tag = "";
        }
        $tmp .= substr($str, 0, $i) . $tag;
        $str = substr($str, $i + $l);
    }
    $str = $tmp . $str;
    return $str;
}

// ***************************************************************************
// ***************************************************************************
// Checks for the location of the torrents
// If it does not exist, then it creates it.
function checkTorrentPath()
{
    global $settings;
    // is there a stat and torrent dir?
    if (! @is_dir($settings->get('torrent_file_path')) && is_writable($settings->get('path'))) {
        // Then create it
        @mkdir($settings->get('torrent_file_path'), 0777);
    }
}

// ***************************************************************************
// ***************************************************************************
// Returns the drive space used as a percentage i.e 85 or 95
function getDriveSpace($drive)
{
    $percent = 0;
    
    if (is_dir($drive)) {
        $dt = disk_total_space($drive);
        $df = disk_free_space($drive);
        
        $percent = round((($dt - $df) / $dt) * 100);
    }
    return $percent;
}

// ***************************************************************************
// ***************************************************************************
// Display the Drive Space Graphical Bar
function displayDriveSpaceBar($drivespace)
{
    global $cfg;
    $freeSpace = "";
    $barClass = 'progress-success';
    
    if ($drivespace > 20) {
        $freeSpace = " (" . formatFreeSpace($cfg["free_space"]) . " Free)";
    } else 
        if ($drivespace >= 98) {
            $barClass = 'progress-danger';
        }
    ?>
<label><?php echo _STORAGE ?>: <?php echo $drivespace ?>% <?php echo $freeSpace ?></label>
<progress class="progress <?php echo $barClass; ?>"
    value="<?php echo $drivespace ?>" max="100"
    style="margin-bottom: 0px"><?php echo $drivespace ?>%</progress>
<?php
}

// ***************************************************************************
// ***************************************************************************
// Convert free space to GB or MB depending on size
function formatFreeSpace($freeSpace)
{
    $rtnValue = "";
    if ($freeSpace > 1024) {
        $rtnValue = number_format($freeSpace / 1024, 2) . " GB";
    } else {
        $rtnValue = number_format($freeSpace, 2) . " MB";
    }
    
    return $rtnValue;
}

// **************************************************************************
// getFileFilter()
// Returns a string used as a file filter.
// Takes in an array of file types.
function getFileFilter($inArray)
{
    $filter = "(\." . strtolower($inArray[0]) . ")|"; // used to hold the file type filter
    $filter .= "(\." . strtoupper($inArray[0]) . ")";
    // Build the file filter
    for ($inx = 1; $inx < sizeof($inArray); $inx ++) {
        $filter .= "|(\." . strtolower($inArray[$inx]) . ")";
        $filter .= "|(\." . strtoupper($inArray[$inx]) . ")";
    }
    $filter .= "$";
    return $filter;
}

// **************************************************************************
// getAliasName()
// Create Alias name for Text file and Screen Alias
function getAliasName($inName)
{
    $replaceItems = array(
        " ",
        ".",
        "-",
        "[",
        "]",
        "(",
        ")",
        "#",
        "&",
        "@"
    );
    $alias = str_replace($replaceItems, "_", $inName);
    $alias = strtolower($alias);
    $alias = str_replace("_torrent", "", $alias);
    
    return $alias;
}

// **************************************************************************
// cleanFileName()
// Remove bad characters that cause problems
function cleanFileName($inName)
{
    $replaceItems = array(
        "?",
        "&",
        "'",
        "\"",
        "+",
        "@"
    );
    $cleanName = str_replace($replaceItems, "", $inName);
    $cleanName = ltrim($cleanName, "-");
    $cleanName = preg_replace("/[^0-9a-z.]+/i", '_', $cleanName);
    return $cleanName;
}

// **************************************************************************
// usingTornado()
// returns true if client is tornado
function usingTornado()
{
    return true;
}

// **************************************************************************
// cleanURL()
// split on the "*" coming from Varchar URL
function cleanURL($url)
{
    $rtnValue = $url;
    $arURL = explode("*", $url);
    
    if (sizeof($arURL) > 1) {
        $rtnValue = $arURL[1];
    }
    
    return $rtnValue;
}

// -------------------------------------------------------------------
// FetchTorrent() method to get data from URL
// Has support for specific sites
// -------------------------------------------------------------------
function FetchTorrent($url)
{
    global $cfg, $db;
    ini_set("allow_url_fopen", "1");
    ini_set("user_agent", $_SERVER["HTTP_USER_AGENT"]);
    
    $rtnValue = "";
    
    $domain = parse_url($url);
    
    if (strtolower(substr($domain["path"], - 8)) != ".torrent") {
        // Check know domain types
        if (strpos(strtolower($domain["host"]), "mininova") !== false) {
            // Sample (http://www.mininova.org/rss.xml):
            // http://www.mininova.org/tor/2254847
            // <a href="/get/2281554">FreeLinux.ISO.iso.torrent</a>
            
            // If received a /tor/ get the required information
            if (strpos($url, "/tor/") !== false) {
                // Get the contents of the /tor/ to find the real torrent name
                $html = FetchHTML($url);
                
                // Check for the tag used on mininova.org
                if (preg_match("/<a href=\"\/get\/[0-9].[^\"]+\">(.[^<]+)<\/a>/i", $html, $html_preg_match)) {
                    // This is the real torrent filename
                    $cfg["save_torrent_name"] = $html_preg_match[1];
                }
                
                // Change to GET torrent url
                $url = str_replace("/tor/", "/get/", $url);
            }
            
            // Now fetch the torrent file
            $html = FetchHTML($url);
            
            // This usually gets triggered if the original URL was /get/ instead of /tor/
            if (strlen($cfg["save_torrent_name"]) == 0) {
                // Get the name of the torrent, and make it the filename
                if (preg_match("/name([0-9][^:]):(.[^:]+)/i", $html, $html_preg_match)) {
                    $filelength = $html_preg_match[1];
                    $filename = $html_preg_match[2];
                    $cfg["save_torrent_name"] = substr($filename, 0, $filelength) . ".torrent";
                }
            }
            
            // Make sure we have a torrent file
            if (strpos($html, "d8:") === false) {
                // We don't have a Torrent File... it is something else
                $options = [
                    'user_id' => $cfg['user'],
                    'file' => 'BAD TORRENT for: ' . $url . '\n' . $html,
                    'action' => $cfg["constants"]["error"]
                ];
                $log = new \Gratbrav\Torrentbug\Log\Service();
                $log->save($options);
                
                $html = "";
            }
            
            return $html;
        } elseif (strpos(strtolower($domain["host"]), "isohunt") !== false) {
            // Sample (http://isohunt.com/js/rss.php):
            // http://isohunt.com/download.php?mode=bt&id=8837938
            // http://isohunt.com/btDetails.php?ihq=&id=8464972
            $referer = "http://" . $domain["host"] . "/btDetails.php?id=";
            
            // If the url points to the details page, change it to the download url
            if (strpos(strtolower($url), "/btdetails.php?") !== false) {
                $url = str_replace("/btDetails.php?", "/download.php?", $url) . "&mode=bt"; // Need to make it grab the torrent
            }
            
            // Grab contents of details page
            $html = FetchHTML($url, $referer);
            
            // Get the name of the torrent, and make it the filename
            if (preg_match("/name([0-9]+):[^:]+/i", $html, $html_preg_match)) {
                $filelength = $html_preg_match[1];
                $filename = $html_preg_match[0];
                $cfg["save_torrent_name"] = substr($filename, 5 + strlen($filelength), $filelength) . ".torrent";
            }
            
            // Make sure we have a torrent file
            if (strpos($html, "d8:") === false) {
                // We don't have a Torrent File... it is something else
                $options = [
                    'user_id' => $cfg['user'],
                    'file' => 'BAD TORRENT for: ' . $url . '\n' . $html,
                    'action' => $cfg["constants"]["error"]
                ];
                $log = new \Gratbrav\Torrentbug\Log\Service();
                $log->save($options);
                
                $html = "";
            }
            
            return $html;
        } elseif (strpos(strtolower($url), "details.php?") !== false) {
            // Sample (http://www.bitmetv.org/rss.php?passkey=123456):
            // http://www.bitmetv.org/details.php?id=18435&hit=1
            $referer = "http://" . $domain["host"] . "/details.php?id=";
            
            $html = FetchHTML($url, $referer);
            
            // Sample (http://www.bitmetv.org/details.php?id=18435)
            // download.php/18435/SpiderMan%20Season%204.torrent
            if (preg_match("/(download.php.[^\"]+)/i", $html, $html_preg_match)) {
                $torrent = str_replace(" ", "%20", substr($html_preg_match[0], 0, - 1));
                $url2 = "http://" . $domain["host"] . "/" . $torrent;
                $html2 = FetchHTML($url2);
                
                // Make sure we have a torrent file
                if (strpos($html2, "d8:") === false) {
                    // We don't have a Torrent File... it is something else
                    $options = [
                        'user_id' => $cfg['user'],
                        'file' => 'BAD TORRENT for: ' . $url . '\n' . $html2,
                        'action' => $cfg["constants"]["error"]
                    ];
                    $log = new \Gratbrav\Torrentbug\Log\Service();
                    $log->save($options);
                    
                    $html2 = "";
                }
                return $html2;
            } else {
                return "";
            }
        } elseif (strpos(strtolower($url), "download.asp?") !== false) {
            // Sample (TF's TorrenySpy Search):
            // http://www.torrentspy.com/download.asp?id=519793
            $referer = "http://" . $domain["host"] . "/download.asp?id=";
            
            $html = FetchHTML($url, $referer);
            
            // Get the name of the torrent, and make it the filename
            if (preg_match("/name([0-9]+):[^:]+/i", $html, $html_preg_match)) {
                $filelength = $html_preg_match[1];
                $filename = $html_preg_match[0];
                $cfg["save_torrent_name"] = substr($filename, 5 + strlen($filelength), $filelength) . ".torrent";
            }
            
            if (! empty($html)) {
                // Make sure we have a torrent file
                if (strpos($html, "d8:") === false) {
                    // We don't have a Torrent File... it is something else
                    $options = [
                        'user_id' => $cfg['user'],
                        'file' => 'BAD TORRENT for: ' . $url . '\n' . $html,
                        'action' => $cfg["constants"]["error"]
                    ];
                    $log = new \Gratbrav\Torrentbug\Log\Service();
                    $log->save($options);
                    
                    $html = "";
                }
                return $html;
            } else {
                return "";
            }
        }
    }
    
    $html = FetchHTML($url);
    // Make sure we have a torrent file
    if (strpos($html, "d8:") === false) {
        // We don't have a Torrent File... it is something else
        $options = [
            'user_id' => $cfg['user'],
            'file' => 'BAD TORRENT for: ' . $url . '\n' . $html,
            'action' => $cfg["constants"]["error"]
        ];
        $log = new \Gratbrav\Torrentbug\Log\Service();
        $log->save($options);
        
        $html = "";
    } else {
        $html = substr($html, strpos($html, "d8:"));
        // Get the name of the torrent, and make it the filename
        if (preg_match("/name([0-9]+):[^:]+/i", $html, $html_preg_match)) {
            $filelength = $html_preg_match[1];
            $filename = $html_preg_match[0];
            $cfg["save_torrent_name"] = substr($filename, 5 + strlen($filelength), $filelength) . ".torrent";
        }
    }
    
    return $html;
}

// -------------------------------------------------------------------
// FetchHTML() method to get data from URL -- uses timeout and user agent
// -------------------------------------------------------------------
function FetchHTML($url, $referer = "")
{
    global $cfg, $db;
    ini_set("allow_url_fopen", "1");
    ini_set("user_agent", $_SERVER["HTTP_USER_AGENT"]);
    
    // $url = cleanURL( $url );
    $domain = parse_url($url);
    $getcmd = $domain["path"];
    
    if (! array_key_exists("query", $domain)) {
        $domain["query"] = "";
    }
    
    $getcmd .= (! empty($domain["query"])) ? "?" . $domain["query"] : "";
    
    $cookie = "";
    $rtnValue = "";
    
    // If the url already doesn't contain a passkey, then check
    // to see if it has cookies set to the domain name.
    if ((strpos($domain["query"], "passkey=")) === false) {
        $sql = "SELECT c.data FROM tf_cookies AS c LEFT JOIN tf_users AS u ON ( u.uid = c.uid ) WHERE u.user_id = '" . $cfg["user"] . "' AND c.host = '" . $domain['host'] . "'";
        $cookie = $db->GetOne($sql);
        showError($db, $sql);
    }
    
    if (! array_key_exists("port", $domain)) {
        $domain["port"] = 80;
    }
    
    // Check to see if this site requires the use of cookies
    if (! empty($cookie)) {
        $socket = @fsockopen($domain["host"], $domain["port"], $errno, $errstr, 30); // connect to server
        
        if (! empty($socket)) {
            // Write the outgoing header packet
            // Using required cookie information
            $packet = "GET " . $url . " HTTP/1.0\r\n";
            $packet .= (! empty($referer)) ? "Referer: " . $referer . "\r\n" : "";
            $packet .= "Accept: */*\r\n";
            $packet .= "Accept-Language: en-us\r\n";
            $packet .= "User-Agent: " . $_SERVER["HTTP_USER_AGENT"] . "\r\n";
            $packet .= "Host: " . $domain["host"] . "\r\n";
            $packet .= "Connection: Close\r\n";
            $packet .= "Cookie: " . $cookie . "\r\n\r\n";
            
            // Send header packet information to server
            @fputs($socket, $packet);
            
            // Initialize variable, make sure null until we add too it.
            $rtnValue = null;
            
            // If http 1.0 just take it all as 1 chunk (Much easier, but for old servers)
            while (! @feof($socket)) {
                $rtnValue .= @fgets($socket, 500000);
            }
            
            @fclose($socket); // Close our connection
        }
    } else {
        if ($fp = @fopen($url, 'r')) {
            $rtnValue = "";
            while (! @feof($fp)) {
                $rtnValue .= @fgets($fp, 4096);
            }
            @fclose($fp);
        }
    }
    
    // If the HTML is still empty, then try CURL
    if (($rtnValue == "" && function_exists("curl_init")) || (strpos($rtnValue, "HTTP/1.0 302") > 0 && function_exists("curl_init")) || (strpos($rtnValue, "HTTP/1.1 302") > 0 && function_exists("curl_init"))) {
        // Give CURL a Try
        $ch = curl_init();
        if ($cookie != "") {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        curl_setopt($ch, CURLOPT_PORT, $domain["port"]);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_VERBOSE, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        
        $response = curl_exec($ch);
        
        curl_close($ch);
        
        $rtnValue = substr($response, strpos($response, "d8:"));
        $rtnValue = rtrim($rtnValue, "\r\n");
    }
    
    return $rtnValue;
}

// **************************************************************************
// getDownloadSize()
// Grab the full size of the download from the torrent metafile
function getDownloadSize($torrent)
{
    $rtnValue = "";
    if (file_exists($torrent)) {
        include_once ("BDecode.php");
        $fd = fopen($torrent, "rd");
        $alltorrent = fread($fd, filesize($torrent));
        $array = BDecode($alltorrent);
        fclose($fd);
        $rtnValue = $array["info"]["piece length"] * (strlen($array["info"]["pieces"]) / 20);
    }
    return $rtnValue;
}

// **************************************************************************
// formatBytesToKBMGGB()
// Returns a string in format of GB, MB, or KB depending on the size for display
function formatBytesToKBMGGB($inBytes)
{
    $rsize = "";
    if ($inBytes > (1024 * 1024 * 1024)) {
        $rsize = round($inBytes / (1024 * 1024 * 1024), 2) . " GB";
    } elseif ($inBytes < 1024 * 1024) {
        $rsize = round($inBytes / 1024, 1) . " KB";
    } else {
        $rsize = round($inBytes / (1024 * 1024), 1) . " MB";
    }
    return $rsize;
}

// **************************************************************************
// HealthData
// Stores the image and title of for the health of a file.
class HealthData
{

    var $image = "";

    var $title = "";
}

// **************************************************************************
// getStatusImage() Takes in an AliasFile object
// Returns a string "file name" of the status image icon
function getStatusImage($af)
{
    $hd = new HealthData();
    $hd->image = "black.gif";
    $hd->title = "";
    
    if ($af->running == "1") {
        // torrent is running
        if ($af->seeds < 2) {
            $hd->image = "yellow.gif";
        }
        if ($af->seeds == 0) {
            $hd->image = "red.gif";
        }
        if ($af->seeds >= 2) {
            $hd->image = "green.gif";
        }
    }
    if ($af->percent_done >= 100) {
        if (trim($af->up_speed) != "" && $af->running == "1") {
            // is seeding
            $hd->image = "green.gif";
        } else {
            // the torrent is finished
            $hd->image = "black.gif";
        }
    }
    
    if ($hd->image != "black.gif") {
        $hd->title = "S:" . $af->seeds . " P:" . $af->peers . " ";
    }
    
    if ($af->running == "3") {
        // torrent is queued
        $hd->image = "black.gif";
    }
    
    return $hd;
}

// **************************************************************************
function writeQinfo($fileName, $command)
{
    $fp = fopen($fileName . ".Qinfo", "w");
    fwrite($fp, $command);
    fflush($fp);
    fclose($fp);
}

// **************************************************************************
class ProcessInfo
{

    var $pid = "";

    var $ppid = "";

    var $cmdline = "";

    function ProcessInfo($psLine)
    {
        $psLine = trim($psLine);
        if (strlen($psLine) > 12) {
            $this->pid = trim(substr($psLine, 0, 5));
            $this->ppid = trim(substr($psLine, 5, 6));
            $this->cmdline = trim(substr($psLine, 12));
        }
    }
}

// **************************************************************************
function runPS()
{
    global $cfg, $settings;
    
    $btphpbin = basename($settings->get('btphpbin'));
    $filePath = $settings->get('torrent_file_path');
    
    return shell_exec("ps x -o pid='' -o ppid='' -o command='' -ww | grep " . $btphpbin . " | grep " . $filePath . " | grep -v grep");
}

// **************************************************************************
function RunningProcessInfo()
{
    global $settings;
    
    if ($_SESSION['is_admin']) {
        include_once ("RunningTorrent.php");
        
        $screenStatus = runPS();
        
        $arScreen = array();
        $tok = strtok($screenStatus, "\n");
        while ($tok) {
            array_push($arScreen, $tok);
            $tok = strtok("\n");
        }
        
        $cProcess = array();
        $cpProcess = array();
        $pProcess = array();
        $ProcessCmd = array();
        
        $QLine = "";
        for ($i = 0; $i < sizeof($arScreen); $i ++) {
            if (strpos($arScreen[$i], $settings->get('tfQManager')) > 0) {
                $pinfo = new ProcessInfo($arScreen[$i]);
                $QLine = $pinfo->pid;
            } else {
                if (strpos($arScreen[$i], basename($settings->get('btphpbin'))) !== false) {
                    $pinfo = new ProcessInfo($arScreen[$i]);
                    
                    if (intval($pinfo->ppid) == 1) {
                        if (! strpos($pinfo->cmdline, "rep python") > 0) {
                            if (! strpos($pinfo->cmdline, "ps x") > 0) {
                                array_push($pProcess, $pinfo->pid);
                                $rt = new RunningTorrent($pinfo->pid . " " . $pinfo->cmdline);
                                // array_push($ProcessCmd,$pinfo->cmdline);
                                array_push($ProcessCmd, $rt->torrentOwner . "\t" . str_replace(array(
                                    ".stat"
                                ), "", $rt->statFile));
                            }
                        }
                    } else {
                        if (! strpos($pinfo->cmdline, "rep python") > 0) {
                            if (! strpos($pinfo->cmdline, "ps x") > 0) {
                                array_push($cProcess, $pinfo->pid);
                                array_push($cpProcess, $pinfo->ppid);
                            }
                        }
                    }
                }
            }
        }
        echo " --- Running Processes ---\n";
        echo " Parents  : " . count($pProcess) . "\n";
        echo " Children : " . count($cProcess) . "\n";
        echo "\n";
        
        echo " PID \tOwner\tTorrent File\n";
        foreach ($pProcess as $key => $value) {
            echo " " . $value . "\t" . $ProcessCmd[$key] . "\n";
            foreach ($cpProcess as $cKey => $cValue)
                if (intval($value) == intval($cValue))
                    echo "\t" . $cProcess[$cKey] . "\n";
        }
        echo "\n";
        echo " --- QManager --- \n";
        echo " PID : ";
        echo " " . $QLine;
    }
}

// **************************************************************************
function getNumberOfQueuedTorrents()
{
    global $settings;
    
    $rtnValue = 0;
    
    $dirName = $settings->get('torrent_file_path') . "queue/";
    
    $handle = @opendir($dirName);
    
    if ($handle) {
        while ($entry = readdir($handle)) {
            if ($entry != "." && $entry != "..") {
                if (! (@is_dir($dirName . $entry)) && (substr($entry, - 6) == ".Qinfo")) {
                    $rtnValue = $rtnValue + 1;
                }
            }
        }
    }
    
    return $rtnValue;
}

// **************************************************************************
function getRunningTorrentCount()
{
    return count(getRunningTorrents());
}

// **************************************************************************
function getRunningTorrents()
{
    global $settings;
    
    $screenStatus = runPS();
    
    $arScreen = array();
    $tok = strtok($screenStatus, "\n");
    while ($tok) {
        array_push($arScreen, $tok);
        $tok = strtok("\n");
    }
    
    $artorrent = array();
    
    for ($i = 0; $i < sizeof($arScreen); $i ++) {
        if (! strpos($arScreen[$i], $settings->get('tfQManager')) > 0) {
            if (strpos($arScreen[$i], basename($settings->get('btphpbin'))) !== false) {
                $pinfo = new ProcessInfo($arScreen[$i]);
                
                if (intval($pinfo->ppid) == 1) {
                    if (! strpos($pinfo->cmdline, "rep python") > 0) {
                        if (! strpos($pinfo->cmdline, "ps x") > 0) {
                            array_push($artorrent, $pinfo->pid . " " . $pinfo->cmdline);
                        }
                    }
                }
            }
        }
    }
    
    return $artorrent;
}

// **************************************************************************
function checkQManager()
{
    $x = getQManagerPID();
    if (strlen($x) > 0) {
        $y = $x;
        $arScreen = array();
        $tok = strtok(shell_exec("ps -p " . $x . " | grep " . $y), "\n");
        
        while ($tok) {
            array_push($arScreen, $tok);
            $tok = strtok("\n");
        }
        
        $QMgrCount = sizeOf($arScreen);
    } else {
        $QMgrCount = 0;
    }
    
    return $QMgrCount;
}

// **************************************************************************
function getQManagerPID()
{
    global $settings;
    
    $rtnValue = "";
    
    $pidFile = $settings->get('torrent_file_path') . "queue/tfQManager.pid";
    
    if (file_exists($pidFile)) {
        $fp = fopen($pidFile, "r");
        if ($fp) {
            while (! feof($fp)) {
                $tmpValue = fread($fp, 1);
                if ($tmpValue != "\n")
                    $rtnValue .= $tmpValue;
            }
            fclose($fp);
        }
    }
    return $rtnValue;
}

// **************************************************************************
function startQManager($maxServerThreads = 5, $maxUserThreads = 2, $sleepInterval = 10)
{
    global $cfg, $settings;
    
    // is there a stat and torrent dir?
    if (is_dir($settings->get('torrent_file_path'))) {
        if (is_writable($settings->get('torrent_file_path')) && ! is_dir($settings->get('torrent_file_path') . "queue/")) {
            // Then create it
            mkdir($settings->get('torrent_file_path') . "queue/", 0777);
        }
    }
    
    if (checkQManager() == 0) {
        $cmd1 = "cd " . $settings->get('path') . "TFQUSERNAME";
        
        if ($settings->get('pythonCmd') === null) {
            $settings->save(array(
                'pythonCmd' => '/usr/bin/python'
            ));
        }
        
        if ($settings->get('debugTorrents') === null) {
            $settings->save(array(
                'debugTorrents' => false
            ));
        }
        
        if (! $settings->get('debugTorrents')) {
            $pyCmd = $settings->get('pythonCmd') . " -OO";
        } else {
            $pyCmd = $settings->get('pythonCmd');
        }
        
        $btphp = "'" . $cmd1 . "; HOME=" . $settings->get('path') . "; export HOME; nohup " . $pyCmd . " " . $settings->get('btphpbin') . " '";
        $command = $pyCmd . " " . $settings->get('tfQManager') . " " . $settings->get('torrent_file_path') . "queue/ " . escapeshellarg($maxServerThreads) . " " . escapeshellarg($maxUserThreads) . " " . escapeshellarg($sleepInterval) . " " . $btphp . " > /dev/null &";
        // $command = $pyCmd . " " . $settings->get('tfQManager') . " ".$settings->get('torrent_file_path')."queue/ ".$maxServerThreads." ".$maxUserThreads." ".$sleepInterval." ".$btphp." > /dev/null2>&1 & &";
        
        $result = exec($command);
        
        sleep(2); // wait for it to start prior to getting pid
        
        $options = [
            'user_id' => $cfg['user'],
            'file' => 'Started PID:' . getQManagerPID(),
            'action' => $cfg["constants"]["QManager"]
        ];
        $log = new \Gratbrav\Torrentbug\Log\Service();
        $log->save($options);
    } else {
        $options = [
            'user_id' => $cfg['user'],
            'file' => 'QManager Already Started  PID:' . getQManagerPID(),
            'action' => $cfg["constants"]["QManager"]
        ];
        $log = new \Gratbrav\Torrentbug\Log\Service();
        $log->save($options);
    }
}

// **************************************************************************
function stopQManager()
{
    global $cfg, $settings;
    
    $QmgrPID = getQManagerPID();
    if ($QmgrPID != "") {
        $options = [
            'user_id' => $cfg['user'],
            'file' => 'Stopping PID:' . $QmgrPID,
            'action' => $cfg["constants"]["QManager"]
        ];
        $log = new \Gratbrav\Torrentbug\Log\Service();
        $log->save($options);
        
        $result = exec("kill " . escapeshellarg($QmgrPID));
        unlink($settings->get('torrent_file_path') . "queue/tfQManager.pid");
    }
}

// **************************************************************************
// file_size()
// Returns file size... overcomes PHP limit of 2.0GB
function file_size($file)
{
    $size = @filesize($file);
    if ($size == 0) {
        $size = exec("ls -l \"" . escapeshellarg($file) . "\" | awk '{print $5}'");
    }
    return $size;
}

// **************************************************************************
// SecurityClean()
// Cleans the file name for delete and alias file creation
function SecurityClean($string)
{
    global $cfg;
    
    if (empty($string)) {
        return $string;
    }
    
    $array = array(
        "<",
        ">",
        "\\",
        "//",
        "..",
        "'",
        "/"
    );
    foreach ($array as $char) {
        $string = str_replace($char, NULL, $string);
    }
        
    if( (strtolower( substr( $string, -8 ) ) == ".torrent") || (strtolower( substr( $string, -5 ) ) == ".stat") )
    {
        // we are good
    }
    else
    {
        $options = [
            'user_id' => $cfg['user'],
            'file' => 'Not a stat or torrent: ' . $string,
            'action' => $cfg["constants"]["error"],
        ];
        $log = new \Gratbrav\Torrentbug\Log\Service();
        $log->save($options);

        die("Invalid file specified.  Action has been logged.");
    }
    return $string;
}

