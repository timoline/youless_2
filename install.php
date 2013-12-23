<?php
	include "inc/settings.inc.php";	
	include "classes/database.class.php";
	include "classes/generic.class.php";	

	$gen = new Generic($config);
?>

<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>YouLess - Energy Monitor</title>
		<link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico" />
		<link type="text/css" href="css/style.css" rel="stylesheet" />
	</head>
	<body class="install">	
		<div id="topHeader"></div>
		<div id="header">	
			<div id="logo"></div>
		</div>
		<div id="container">
			<div id="installForm">
		
<?php

	

	if(isset($_GET['step']) && $_GET['step'] == 2){
		if(empty($_POST['db_host']) || empty($_POST['db_user']) || empty($_POST['db_name'])){
			echo "<p class='error'><b>db_host</b>, <b>db_user</b> en <b>db_name</b>, zijn vereist!</p>";
		}
		else
		{
			$ok = true;
			foreach($_POST as $key => $val){
				$$key = $val;
				$ok = $gen->changeConfig($key, $val);
			}
			if($ok){
				
				try {
					$db = new PDO("mysql:host=".$db_host, $db_user, $db_pass);
					$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
					
					$succes = $db->exec("CREATE DATABASE IF NOT EXISTS`".$db_name."`;	

						CREATE TABLE IF NOT EXISTS `".$db_name."`. `settings` (
						  `key` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
						  `value` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
						  UNIQUE KEY `key` (`key`)
						) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;						

						INSERT INTO `".$db_name."`. `settings` (`key`, `value`) VALUES
						('cpkwh', '0.22'),
						('cpkwh_low', '0.10'),
						('dualcount', '0'),
						('cpkwhlow_start', '21:00'),
						('cpkwhlow_end', '07:00'),
						('liveinterval', '1000'),
						('autoupdate', '0'),
						('notifyupdate', '1'),
						('cleanup', '1');	
						
						CREATE TABLE IF NOT EXISTS `".$db_name."`. `users` (
						  `id` int(11) NOT NULL AUTO_INCREMENT,
						  `username` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
						  `password` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
						  PRIMARY KEY (`id`)
						) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1;
						
						INSERT INTO `".$db_name."`. `users` (`id`, `username`, `password`) VALUES
						(1, 'admin', 'd033e22ae348aeb5660fc2140aec35850c4da997');

						CREATE TABLE IF NOT EXISTS `".$db_name."`. `meters` (
						  `id` int(11) NOT NULL AUTO_INCREMENT,
						  `name` varchar(40) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
						  `address` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
						  `password` varchar(40) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
						  PRIMARY KEY (`id`)
						) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;							
					");
					if($succes == 1)
					{
						echo "<p style='color:green;'>Installatie succesvol. Verwijder <b>install.php</b> en <b>update.php</b></p>";
						echo "<p style='color:green;'>Default gebruikersnaam/wachtwoord is <b>admin</b>/<b>admin</b></p>";
					}					

				} catch (PDOException $e) {
					die(print("<p class='error'>Database error: ". $e->getMessage() ."</p>"));
				}		
	
			}
		}
	}
	else
	{
		$errorMsg = '';
		$ok = true;

		if (version_compare(PHP_VERSION, '5.2.0') <= 0) 
		{
			$errorMsg .= '<b>PHP 5.2.0</b> is vereist';
			$ok = false;
		}	
		if(!is_writable('inc/settings.inc.php'))
		{
			$errorMsg .= '<b>settings.inc.php</b> is niet schrijfbaar!';
			$ok = false;
		}
		if(!extension_loaded('pdo_mysql'))
		{
			$errorMsg .= '<b>PDO Mysql</b> extension ontbreekt!';
			$ok = false;
		}
		if(!extension_loaded('curl'))
		{
			$errorMsg .= '<b>CURL extension</b> ontbreekt!</p>';
			$ok = false;
		}
		
		if($ok){
			echo "<form action='install.php?step=2' method='POST'/>
			<p style='color:green;'>Check succesvol.</p>
			<table>
				<tr>
					<td>Database host:</td><td><input type='text' name='db_host' value='localhost'/></td>
				</tr>			
				<tr>
					<td>Database user:</td><td><input type='text' name='db_user' value=''/></td>
				</tr>			
				<tr>
					<td>Database password:</td><td><input type='text' name='db_pass' value=''/></td>
				</tr>			
				<tr>
					<td>Database name:</td><td><input type='text' name='db_name' value='youless'/></td>
				</tr>
			</table>
			<input type='submit' value='Verder'/>
			"; 
		}
		else
		{
			echo "<p class='error'>" . $errorMsg ."</p>";
		}
	}


?>
			</div>
		</div>
	</body>
</html>
