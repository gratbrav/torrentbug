<!doctype html>
<html>
<head>
	<title><?php echo $cfg["pagetitle"] ?></title>
	<link rel="icon" href="images/favicon.ico" type="image/x-icon" />
	<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
	<link rel="stylesheet" href="./plugins/twitter/bootstrap/dist/css/bootstrap.min.css" type="text/css" />
    <link rel="StyleSheet" href="themes/<?php echo $cfg["theme"] ?>/style.css" type="text/css" />
    <script src="./plugins/components/jquery/jquery.min.js"></script>
</head>
<body>

<div class="container">
	<div class="row">
		<nav class="navbar navbar-light" style="background-color: #e3f2fd;">
			<div class="col-sm-12 nav navbar-nav">
				<a class="nav-item nav-link active" href="index.php"><?php echo _TORRENTS ?> <span class="sr-only">(current)</span></a>
				<a class="nav-item nav-link" href="dir.php"><?php echo _DIRECTORYLIST ?></a>
				<a class="nav-item nav-link" href="history.php"><?php echo _UPLOADHISTORY ?></a>
				<a class="nav-item nav-link" href="profile.php"><?php echo _MYPROFILE ?></a>
				<a class="nav-item nav-link" href="readmsg.php"><?php echo _MESSAGES ?><?php echo $countMessages ?></a>
				<?php if (IsAdmin()) { ?>
					<a class="nav-item nav-link" href="admin.php"><?php echo _ADMINISTRATION ?></a>
				<?php } ?>
				<a class="nav-item nav-link pull-sm-right" href="logout.php">Logout</a>
			</div>
			
			<?php if (isset($subMenu) && $subMenu == 'index') { ?>
				<div class="col-sm-12 nav navbar-nav" style="margin-left:16px;">
					<a class="nav-item nav-link" href="readrss.php"><small>RSS Torrents</small></a>
					<a class="nav-item nav-link" href="drivespace.php"><small><?php echo _DRIVESPACE ?></small></a>
					<a class="nav-item nav-link" href="who.php"><small><?php echo _SERVERSTATS ?></small></a>
					<a class="nav-item nav-link" href="all_services.php"><small><?php echo _ALL ?></small></a>
					<a class="nav-item nav-link" href="dir.php"><small><?php echo _DIRECTORYLIST ?></small></a>
					<a class="nav-item nav-link" href="dir.php?dir=<?php echo $cfg["user"] ?>"><small>My Directory</small></a>
				</div>
			<?php } ?>
			
			<?php if (isset($subMenu) && $subMenu == 'admin') { ?>
				<div class="col-sm-12 nav navbar-nav" style="margin-left:16px;">
					<a class="nav-item nav-link" href="admin.php"><small><?php echo _ADMIN_MENU ?></small></a> 
	    			<a class="nav-item nav-link" href="admin.php?op=configSettings"><small><?php echo _SETTINGS_MENU ?></small></a> 
	    			<a class="nav-item nav-link" href="admin.php?op=queueSettings"><small><?php echo _QMANAGER_MENU ?></small></a> 
	    			<a class="nav-item nav-link" href="admin.php?op=searchSettings"><small><?php echo _SEARCHSETTINGS_MENU ?></small></a> 
	    			<a class="nav-item nav-link" href="admin.php?op=showUserActivity"><small><?php echo _ACTIVITY_MENU ?></small></a>
	    			<a class="nav-item nav-link" href="admin.php?op=editLinks"><small><?php echo _LINKS_MENU ?></small></a>
	    			<a class="nav-item nav-link" href="admin.php?op=editRSS"><small>rss</small></a>
	    			<a class="nav-item nav-link" href="admin.php?op=CreateUser"><small><?php echo _NEWUSER_MENU ?></small></a> 
	    			<a class="nav-item nav-link" href="admin.php?op=backupDatabase"><small><?php echo _BACKUP_MENU ?></small></a>
				</div>
			<?php } ?>
		</nav>
	</div>
</div>