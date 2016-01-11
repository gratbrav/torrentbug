<?php

/*************************************************************
*  TorrentFlux PHP Torrent Manager
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

//*************************************************************
// This file contains methods used by both the login.php and the
// main application
//*************************************************************
function getRequestVar($varName)
{
    if (array_key_exists($varName,$_REQUEST))
    {
        if (is_array($_REQUEST[$varName]))
        {
            $tmpArr = $_REQUEST[$varName];
            foreach($tmpArr as $key => $value);
            {
                $tmpArr[$key] = htmlentities(trim($value), ENT_QUOTES);
            }

            return $tmpArr;

        } else {
            return htmlentities(trim($_REQUEST[$varName]), ENT_QUOTES);
        }
    }
    else
    {
        return '';
    }
}


//*********************************************************
// AuditAction
function AuditAction($action, $file="")
{
    global $_SERVER, $cfg, $db;

    $host_resolved = $cfg['ip'];
    $create_time = time();

    $rec = array(
                    'user_id' => $cfg['user'],
                    'file' => $file,
                    'action' => $action,
                    'ip' => htmlentities($cfg['ip'], ENT_QUOTES),
                    'ip_resolved' => htmlentities($host_resolved, ENT_QUOTES),
                    'user_agent' => htmlentities($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES),
                    'time' => $create_time
                );

    $sTable = 'tf_log';
    $sql = $db->GetInsertSql($sTable, $rec);

    // add record to the log
    $result = $db->Execute($sql);
    showError($db,$sql);
}

//*********************************************************
function isFile($file)
{
    $rtnValue = False;

    if (is_file($file))
    {
        $rtnValue = True;
    }
    else
    {
        if ($file == trim(shell_exec("ls ".escapeshellarg($file))))
        {
            $rtnValue = True;
        }
    }
    return $rtnValue;
}

//*********************************************************
function getCode($rnd)
{
    global $db, $cfg;
    
    $datekey = date("F j");
    $rcode = hexdec(md5($_SERVER['HTTP_USER_AGENT'] . $cfg["db_user"] . $rnd . $datekey));
    return substr($rcode, 3, 6);
}

?>