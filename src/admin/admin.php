<?php
include_once '../Class/autoload.php';
include_once '../config.php';
include_once '../functions.php';

if (! IsAdmin()) {
    // the user probably hit this page direct
    $options = [
        'user_id' => $cfg['user'],
        'file' => $_SERVER['PHP_SELF'],
        'action' => $cfg["constants"]["access_denied"]
    ];
    $log = new \Gratbrav\Torrentbug\Log\Service();
    $log->save($options);
    
    header("location: ../index.php");
}

$settings = new Gratbrav\Torrentbug\Settings();

$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
if (empty($action)) {
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
}

if ($action == 'backupDatabase') {
    $file = $cfg["db_name"] . "_" . date("Ymd") . ".tar.gz";
    $back_file = $settings->get('torrent_file_path') . $file;
    $sql_file = $settings->get('torrent_file_path') . $cfg["db_name"] . ".sql";
    
    $sCommand = "";
    switch ($cfg["db_type"]) {
        case "mysql":
            $sCommand = "mysqldump -h " . $cfg["db_host"] . " -u " . $cfg["db_user"] . " --password=" . $cfg["db_pass"] . " --all -f " . $cfg["db_name"] . " > " . $sql_file;
            break;
        default:
            // no support for backup-on-demand.
            $sCommand = "";
            break;
    }
    
    if ($sCommand != "") {
        shell_exec($sCommand);
        shell_exec("tar -czvf " . $back_file . " " . $sql_file);
        
        // Get the file size
        $file_size = filesize($back_file);
        
        // open the file to read
        $fo = fopen($back_file, 'r');
        $fr = fread($fo, $file_size);
        fclose($fo);
        
        // Set the headers
        header("Content-type: APPLICATION/OCTET-STREAM");
        header("Content-Length: " . $file_size . ";");
        header("Content-Disposition: attachement; filename=" . $file);
        
        // send the tar baby
        echo $fr;
        
        // Cleanup
        shell_exec("rm " . $sql_file);
        shell_exec("rm " . $back_file);
        
        $options = [
            'user_id' => $cfg['user'],
            'file' => _BACKUP_MENU . ': ' . $file,
            'action' => $cfg["constants"]["admin"]
        ];
        $log = new \Gratbrav\Torrentbug\Log\Service();
        $log->save($options);
    }
    
    exit();
}

$subMenu = 'admin';
include_once '../header.php';

include 'user_section.php';

$user = $srchFile = $srchAction = "";
include 'display_activity.php';

?>

<div style="text-align: center">
    [<a href="../index.php"><?php echo _RETURNTOTORRENTS ?></a>]
</div>
