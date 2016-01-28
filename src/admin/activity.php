<?php

    include_once '../Class/autoload.php';
    include_once '../config.php';
    
    $settings = new Class_Settings();

    include_once '../functions.php';
    
    if (!IsAdmin()) {
        // the user probably hit this page direct
        AuditAction($cfg["constants"]["access_denied"], $_SERVER['PHP_SELF']);
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
