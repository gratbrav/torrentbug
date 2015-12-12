<div class="col-sm-12 nav navbar-nav">
	<a class="nav-item nav-link active" href="index.php"><?php echo _TORRENTS ?> <span class="sr-only">(current)</span></a>
	<a class="nav-item nav-link" href="dir.php"><?php echo _DIRECTORYLIST ?></a>
	<a class="nav-item nav-link" href="history.php"><?php echo _UPLOADHISTORY ?></a>
	<a class="nav-item nav-link" href="profile.php"><?php echo _MYPROFILE ?></a>
	<a class="nav-item nav-link" href="readmsg.php"><?php echo _MESSAGES ?><?php echo $countMessages ?></a>
	<?php if (IsAdmin()) { ?>
		<a class="nav-item nav-link" href="admin.php"><?php echo _ADMINISTRATION ?></a>
	<?php } ?>
	<a class="nav-item nav-link pull-right" href="logout.php">Logout</a>
</div>