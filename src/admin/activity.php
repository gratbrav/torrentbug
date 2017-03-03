<?php

    include_once '../Class/autoload.php';
    include_once '../config.php';
    
    $settings = new Gratbrav\Torrentbug\Settings();

    include_once '../functions.php';
    
    if (!IsAdmin()) {
        // the user probably hit this page direct
        $options = [
            'user_id' => $cfg['user'],
            'file' => $_SERVER['PHP_SELF'],
            'action' => $cfg["constants"]["access_denied"],
        ];
        $log = new \Gratbrav\Torrentbug\Log\Service();
        $log->save($options);

        header("location: ../index.php");
    }

    $subMenu = 'admin';
    include_once '../header.php';
    
    include_once '../AliasFile.php';
    include_once '../RunningTorrent.php';
    include_once '../searchEngines/SearchEngineBase.php';
    
    $min = getRequestVar('min');
    if (empty($min)) $min=0;
    $user = getRequestVar('user_id');
    $srchFile = getRequestVar('srchFile');
    $srchAction = getRequestVar('srchAction');

    include 'display_activity.php';
