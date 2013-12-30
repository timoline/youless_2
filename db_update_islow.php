<?php
	include "inc/settings.inc.php";	
	include "classes/curl.class.php";
	include "classes/database.class.php";
	include "classes/generic.class.php";	
	include "classes/gitupdater.class.php";	
	include "inc/session.inc.php";
	include "classes/request.class.php";	
		
	$db = new Database($config);
	$gen = new Generic($config);
	$updater = new GitUpdater($config);
		
	$settings = $db->getSettings();
	$meters = $db->getMeters();

?>	
<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>YouLess - Energy Monitor</title>
		<link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico" />
		<link type="text/css" href="css/style.css" rel="stylesheet" />
		<link type="text/css" href="css/jquery-ui-1.8.18.custom.css" rel="stylesheet" />
		<script type="text/javascript" src="js/jquery.min.js"></script>
		<script type="text/javascript" src="js/jquery-ui-1.8.18.custom.min.js"></script>
		<script type="text/javascript" src="js/highstock.js"></script>
		<script type="text/javascript" src="js/modules/exporting.js"></script>
		<script type="text/javascript" src="js/script.js"></script>
	</head>
	<body>
	<?php
	$meter = 1;
	$gen->updateDatabaseIslow($meter);
	?>
	</body>
</html>