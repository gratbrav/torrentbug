<html>
<head>
	<title><?php echo $cfg["pagetitle"] ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="icon" href="<?php echo $settings->get('base_url') ?>/images/favicon.ico" type="image/x-icon" />
	<link rel="shortcut icon" href="<?php echo $settings->get('base_url') ?>/images/favicon.ico" type="image/x-icon" />
	<link rel="stylesheet" href="<?php echo $settings->get('base_url') ?>/plugins/twitter/bootstrap/dist/css/bootstrap.min.css" type="text/css" />
	<link rel="stylesheet" href="<?php echo $settings->get('base_url') ?>/plugins/components/font-awesome/css/font-awesome.min.css" type="text/css" />
    <link rel="StyleSheet" href="<?php echo $settings->get('base_url') ?>/themes/<?php echo $cfg["theme"] ?>/style.css" type="text/css" />
    <script src="<?php echo $settings->get('base_url') ?>/plugins/components/jquery/jquery.min.js"></script>
</head>
<body>

<div class="container">
	<div class="row">
		<nav class="navbar navbar-light" style="background-color: #e3f2fd;">
			<div class="col-sm-12 nav navbar-nav">
				<a class="nav-item nav-link active" href="<?php echo $settings->get('base_url') ?>/index.php"><?php echo _TORRENTS ?> <span class="sr-only">(current)</span></a>
				<a class="nav-item nav-link" href="<?php echo $settings->get('base_url') ?>/dir.php"><?php echo _DIRECTORYLIST ?></a>
				<a class="nav-item nav-link" href="<?php echo $settings->get('base_url') ?>/history.php"><?php echo _UPLOADHISTORY ?></a>
				<a class="nav-item nav-link" href="<?php echo $settings->get('base_url') ?>/profile/edit.php"><?php echo _MYPROFILE ?></a>
				<a class="nav-item nav-link" href="<?php echo $settings->get('base_url') ?>/readmsg.php"><?php echo _MESSAGES ?><?php echo $countMessages ?></a>
				<?php if (IsAdmin()) { ?>
					<a class="nav-item nav-link" href="<?php echo $settings->get('base_url') ?>/admin/admin.php"><?php echo _ADMINISTRATION ?></a>
				<?php } ?>
				<a class="nav-item nav-link pull-sm-right" href="<?php echo $settings->get('base_url') ?>/logout.php">Logout</a>
			</div>
			
			<?php if (isset($subMenu) && $subMenu == 'index') { ?>
				<div class="col-sm-12 nav navbar-nav" style="margin-left:16px;">
					<a class="nav-item nav-link" href="<?php echo $settings->get('base_url') ?>/readrss.php"><small>RSS Torrents</small></a>
					<a class="nav-item nav-link" href="<?php echo $settings->get('base_url') ?>/drivespace.php"><small><?php echo _DRIVESPACE ?></small></a>
					<a class="nav-item nav-link" href="<?php echo $settings->get('base_url') ?>/who.php"><small><?php echo _SERVERSTATS ?></small></a>
					<a class="nav-item nav-link" href="<?php echo $settings->get('base_url') ?>/all_services.php"><small><?php echo _ALL ?></small></a>
					<a class="nav-item nav-link" href="<?php echo $settings->get('base_url') ?>/dir.php"><small><?php echo _DIRECTORYLIST ?></small></a>
					<a class="nav-item nav-link" href="<?php echo $settings->get('base_url') ?>/dir.php?dir=<?php echo $cfg["user"] ?>"><small>My Directory</small></a>
				</div>
			<?php } ?>
			
			<?php if (isset($subMenu) && $subMenu == 'admin') { ?>
				<div class="col-sm-12 nav navbar-nav" style="margin-left:16px;">
					<a class="nav-item nav-link" href="<?php echo $settings->get('base_url') ?>/admin/admin.php"><small><?php echo _ADMIN_MENU ?></small></a> 
	    			<a class="nav-item nav-link" href="<?php echo $settings->get('base_url') ?>/admin/settings.php"><small><?php echo _SETTINGS_MENU ?></small></a> 
	    			<a class="nav-item nav-link" href="<?php echo $settings->get('base_url') ?>/admin/queue.php"><small><?php echo _QMANAGER_MENU ?></small></a> 
	    			<a class="nav-item nav-link" href="<?php echo $settings->get('base_url') ?>/admin/search.php"><small><?php echo _SEARCHSETTINGS_MENU ?></small></a> 
	    			<a class="nav-item nav-link" href="<?php echo $settings->get('base_url') ?>/admin/activity.php"><small><?php echo _ACTIVITY_MENU ?></small></a>
	    			<a class="nav-item nav-link" href="<?php echo $settings->get('base_url') ?>/admin/links.php"><small><?php echo _LINKS_MENU ?></small></a>
	    			<a class="nav-item nav-link" href="<?php echo $settings->get('base_url') ?>/admin/rss.php"><small>rss</small></a>
	    			<a class="nav-item nav-link" href="<?php echo $settings->get('base_url') ?>/admin/adduser.php"><small><?php echo _NEWUSER_MENU ?></small></a> 
	    			<a class="nav-item nav-link" href="<?php echo $settings->get('base_url') ?>/admin/admin.php?action=backupDatabase"><small><?php echo _BACKUP_MENU ?></small></a>
				</div>
			<?php } ?>
		</nav>
	</div>
</div>