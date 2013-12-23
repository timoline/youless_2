<?php
	include "inc/settings.inc.php";	
	include "classes/curl.class.php";
	include "classes/database.class.php";
	include "classes/generic.class.php";	
	include "classes/gitupdater.class.php";	
	include "inc/session.inc.php";
		
	$db = new Database($config);
	$gen = new Generic($config);
	$updater = new GitUpdater($config);
		
	$settings = $db->getSettings();
	$meters = $db->getMeters();
	
	$startTime = explode(":", $settings['cpkwhlow_start']);
	$endTime = explode (":", $settings['cpkwhlow_end']);
	
	$startSelect = $gen->timeSelector($startTime[0], $startTime[1], 'cpkwhlow_start');
	$endSelect = $gen->timeSelector($endTime[0], $endTime[1], 'cpkwhlow_end');

	$intervalOptions = array(
		'500' => '500',
		'1000' => '1000',
		'2000' => '2000',
		'5000' => '5000'
	);
	$intervalSelect = $gen->selector('liveinterval', $settings['liveinterval'], $intervalOptions);

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
		if($updater->hasUpdate() && $settings['notifyupdate'])
		{
			echo '<div id="update_notification">Er is een nieuwe update beschikbaar!<br><br><a href="#" class="viewChangelog smallBtn">Changelog</a></div>';
		}
		?>
		<div id="overlay">
			<div id="dialog" class="default">
				<div id="message"></div>
				<input type="button" id="closeDialog" value="Sluit"/>
			</div>
			<div id="overlayBack"></div>
		</div>
		<div id="settingsOverlay" data-dualcount="<?php echo $settings['dualcount']; ?>" data-liveinterval="<?php echo $settings['liveinterval']; ?>">

			<div id="settingsMenu">
				<ul class="btn">
					<li class="selected"><a href="#" data-settingstab='settingsAlgemeen'>Algemeen</a></li>
					<li><a href="#" data-settingstab='settingsMeters'>Meters</a></li>						
					<li><a href="#" data-settingstab='settingsUpdates'>Updates</a></li>				
				</ul>
			</div>

			<form>
				<table id="settingsAlgemeen" class="settingsTab">
					<tr>
						<td style="width:200px;">Meter type:</td><td>Enkel<input type="radio" name="dualcount" value="0" <?php echo ($settings['dualcount'] == 0 ? 'checked=checked' : '') ?>/> Dubbel<input type="radio" name="dualcount" value="1" <?php echo ($settings['dualcount'] == 1 ? 'checked=checked' : '') ?>/></td>
					</tr>				
					<tr>
						<td>Prijs per kWh:</td><td><input type="text" name="cpkwh" value="<?php echo $settings['cpkwh']; ?>"/></td>
					</tr>
					<tr class="cpkwhlow" <?php echo ($settings['dualcount'] == 1 ? '' : 'style="display:none;"') ?>;>
						<td>Prijs per kWh (laagtarief):</td><td><input type="text" name="cpkwh_low" value="<?php echo $settings['cpkwh_low']; ?>"/></td>
					</tr>	
					<tr class="cpkwhlow" <?php echo ($settings['dualcount'] == 1 ? '' : 'style="display:none;"') ?>;>
						<td>Tijd laagtarief:</td><td><?php echo $startSelect; ?> tot <?php echo $endSelect; ?></td>
					</tr>
					<tr>
						<td>Update interval live weergave:</td><td><?php echo $intervalSelect; ?> ms</td>
					</tr>															
					<tr>
						<td>Admin wachtwoord:</td><td><input type="password" name="password" value=""/></td>
					</tr>
					<tr>
						<td>Bevestig admin wachtwoord:</td><td><input type="password" name="confirmpassword" value=""/></td>
					</tr>										
				</table>

				<table id="settingsUpdates" class="settingsTab">
					<tr>
						<td style="width:360px;">Autoupdate:</td><td>ja<input type="radio" name="autoupdate" value="1" <?php echo ($settings['autoupdate'] == 1 ? 'checked=checked' : '') ?>/> nee<input type="radio" name="autoupdate" value="0" <?php echo ($settings['autoupdate'] == 0 ? 'checked=checked' : '') ?>/></td>
					</tr>				
					<tr>
						<td>Berichtgeving bij nieuwe updates:</td><td>ja<input type="radio" name="notifyupdate" value="1" <?php echo ($settings['notifyupdate'] == 1 ? 'checked=checked' : '') ?>/> nee<input type="radio" name="notifyupdate" value="0" <?php echo ($settings['notifyupdate'] == 0 ? 'checked=checked' : '') ?>/></td>
					</tr>
					<tr>
						<td>Verwijder tijdelijk gedownloade bestanden na update:</td><td>ja<input type="radio" name="cleanup" value="1" <?php echo ($settings['cleanup'] == 1 ? 'checked=checked' : '') ?>/> nee<input type="radio" name="cleanup" value="0" <?php echo ($settings['cleanup'] == 0 ? 'checked=checked' : '') ?>/></td>
					</tr>						
					<tr>
						<td>Update beschikbaar:</td><td><?php echo ($updater->hasUpdate() == true ? 'ja <a href="#" class="viewChangelog smallBtn">Changelog</a> <a href="#" class="runUpdate smallBtn delBtn">Voer update uit</a>' : 'nee') ?></td>
					</tr>					
				</table>
				
				<table id="settingsMeters" class="settingsTab">
					<tr>
						<td>Naam</td><td>Adres</td><td>Wachtwoord</td><td></td>
					</tr>	
					
					<?php foreach($meters as $k => $v) { ?>
					<tr class="meter_row">
						<td><input type="hidden" name="meter[<?php echo $k; ?>][id]" value="<?php echo $v['id']; ?>"/><input type="hidden" name="meter_key" value="<?php echo $k; ?>"/><input type="text" name="meter[<?php echo $k; ?>][name]" value="<?php echo $v['name']; ?>"/></td>
						<td><input type="text" name="meter[<?php echo $k; ?>][address]" value="<?php echo $v['address']; ?>"/></td>
						<td><input type="text" name="meter[<?php echo $k; ?>][password]" value="<?php echo $v['password']; ?>"/></td>
						<td><a href="#" class="delMeter smallBtn delBtn" data-meter="<?php echo $v['id']; ?>">Verwijder</a></td>
					</tr>						
					<?php } ?>			
					<tr>
						<td colspan="3">&nbsp;</td>
						<td><a href="#" class="addMeter smallBtn">Voeg toe</a></td>
					</tr>										
				</table>				
				
				<input type="submit" id="saveSettings" value="Opslaan"/><input type="button" id="hideSettings" value="Sluit"/>
			</form>				

			<div id="version"><?php echo $config['current_tag']; ?></div>
		</div>
		
		<div id="topHeader">
			<div id="settings"><a href="#" id="showSettings">Instellingen</a></div>
			<div id="logout"><a href="?logout=1">Logout</a></div>
		</div>
		<div id="header">
			<div id="logo"></div>
		
			<div id="menu">
				<ul class="btn">
					<li class="selected"><a href="#" data-chart="live" class="showChart">Live</a></li>
					<li><a href="#" data-chart="day" class="showChart">Dag</a></li>
					<li><a href="#" data-chart="week" class="showChart">Week</a></li>
					<li><a href="#" data-chart="month" class="showChart">Maand</a></li>
					<li><a href="#" data-chart="year" class="showChart">Jaar</a></li>
				</ul>
			</div>
			
			
		</div>
		<div id="container">
			<div id="datepickContainer" class="chart day week month year">
				<input type="text" id="datepicker" value="<?php echo date("Y-m-d"); ?>">			
			</div>
			<div id="history" class="chart day week month year"></div>
			<div id="live" class="chart live" style="height: 500px; min-width: 500px;"></div>
		</div>
		<div id="sidebar">
			<div class="chart live">
				<h2><img src="img/kwh.png"/> Liveweergave</h2>
				<div id="wattCounter" class="counter"></div>
			</div>
			<div class="chart day" style="display:none;">
				<h2><img src="img/price.png"/> Dagweergave</h2>
				<div class="kwhCounter"></div>
			</div>			
			<div class="chart week" style="display:none;">
				<h2><img src="img/price.png"/> Weekweergave</h2>
				<div class="kwhCounter"></div>
			</div>			
			<div class="chart month" style="display:none;">
				<h2><img src="img/price.png"/> Maandweergave</h2>
				<div class="kwhCounter"></div>
			</div>
			<div class="chart year" style="display:none;">
				<h2><img src="img/price.png"/> Jaarweergave</h2>
				<div class="kwhCounter"></div>
			</div>			
		</div>
	</body>
</html>