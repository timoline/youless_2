<?php
include "inc/settings.inc.php";
include "classes/curl.class.php";
include "classes/gitupdater.class.php";
include "classes/request.class.php";	
include "classes/database.class.php";
include "classes/generic.class.php";

session_start();

$db = new Database($config);
$gen = new Generic($config);
$updater = new GitUpdater($config);

if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != false)
{

	$settings = $db->getSettings();
	
	$meter_id = (isset($_GET['m']) ? $_GET['m'] : 1);
	$meter = $db->getMeters($meter_id);
	$request = new Request($meter);
	
	// Get all meters
	$meters = $db->getMeters();

	if(isset($_GET['a']) && $_GET['a'] == 'live')
	{
		foreach($meters as $k => $v)
		{
			$request = new Request($v);
			$json_results[$k] = json_decode($request->getLiveData(), true);
			$json_results[$k]['name'] = $v['name'];
		}
		echo json_encode($json_results);
	}
	elseif(isset($_GET['a']) && $_GET['a'] == 'day' && isset($_GET['date']))
	{	
		$sqlDate = $_GET['date'];
		
		// Get data for each meter
		foreach($meters as $k => $v)
		{
			// Get data from specific meter and day
			$rows = $db->getSpecificDay($v['id'], $sqlDate);
			
			// Reset vars for each meter
			$i=0;
			$dataStr = '';		
					
			if(count($rows) == 0)
			{
			
				$json_results[] = '{"ok": 0, "msg":"Geen data beschikbaar op deze datum", "start": '. (strtotime($sqlDate)*1000) .', "val": [ 0 ], "kwh": 0, "name": "'.$v['name'] .'", "price": 0}';
			}
			else
			{
				foreach($rows as $k)
				{
					// Extract data from value db field
					$row = explode(",", $k->value);

					$dataStr .= ($i!=0 ? "," : "").implode(",", $row);
					$i++;
				}
				
				// Create js key for meter
				$json_results[] = '{"ok": 1, "start": '. (strtotime($rows[0]->time)*1000) .', "val": ['. str_replace("\"", "", $dataStr) .'], "name": "'.$v['name'] .'", "unit": "'.$rows[0]->unit .'"}';	
			}
			
		}
		// Output data
		echo '['.implode(",", $json_results).']';	
	}		
	elseif(isset($_GET['a']) && $_GET['a'] == 'calculate_day' && isset($_GET['date']))
	{	
		
		$sqlDate = $_GET['date'];
		
		// Get data for each meter
		foreach($meters as $k => $v)
		{
			// Get data from specific day
			$costs = $gen->calculateDayKwhCosts($v['id'], $sqlDate);	
			$json_results[] = '{"ok": 1, "name": "'.$v['name'] .'", "kwh": "'. number_format($costs->kwh, 3, ',', '') .'", "kwhLow": "'. number_format($costs->kwhLow, 3, ',', '') .'", "price": "'. number_format($costs->price, 2, ',', '') .'", "priceLow": "'. number_format($costs->priceLow, 2, ',', '') .'", "totalPrice": "'. number_format($costs->totalPrice, 2, ',', '') .'", "totalKwh": "'. number_format($costs->totalKwh, 3, ',', '') .'"}';	
		}
			
		// Output data
		echo '['.implode(",", $json_results).']';		
			
	}	
	elseif(isset($_GET['a']) && $_GET['a'] == 'week' && isset($_GET['date']))
	{	
		
		$sqlDate = $_GET['date'];
		
		$week = date('W',strtotime($sqlDate));
		$year = date('o',strtotime($sqlDate));
	
		$begin = date("Y-m-d", strtotime($year."W".$week));
		$end = date("Y-m-d", strtotime($year."W".$week)+(6*86400));		
				
		// Get data for each meter
		foreach($meters as $k => $v)
		{				
			// Get data from specific week
			$rows = $db->getSpecificRange($v['id'], $begin, $end);

			// Reset vars for each meter
			$i=0;
			$dataStr = '';	
				
			if(count($rows) == 0)
			{
			
				$json_results[] = '{"ok": 0, "msg":"Geen data beschikbaar op deze datum", "start": '. (strtotime($begin)*1000) .', "val": [ 0 ], "kwh": 0, "name": "'.$v['name'] .'", "price": 0}';
			
			}
			else
			{	
				foreach($rows as $k)
				{
					// Extract data from value db field
					$row = explode(",", $k->value);

					$dataStr .= ($i!=0 ? "," : "").implode(",", $row);
					$i++;
				}
				
				// Create js key for meter
				$json_results[] = '{"ok": 1, "start": '. (strtotime($rows[0]->time)*1000) .', "val": ['. str_replace("\"", "", $dataStr) .'], "name": "'.$v['name'] .'", "unit": "'.$rows[0]->unit .'"}';	
			}
		}
		// Output data
		echo '['.implode(",", $json_results).']';
	}
	elseif(isset($_GET['a']) && $_GET['a'] == 'calculate_week' && isset($_GET['date']))
	{	
		
		$sqlDate = $_GET['date'];
		
		$week = date('W',strtotime($sqlDate));
		$year = date('o',strtotime($sqlDate));
	
		$start = date("Y-m-d", strtotime($year."W".$week));
		$end = date("Y-m-d", strtotime($year."W".$week)+(6*86400));
		
		// Get data for each meter
		foreach($meters as $k => $v)
		{
			// Calculate totals/costs
			$costs = $gen->calculateTimeRangeKwhCosts($v['id'], $start, $end);
			$json_results[] = '{"ok": 1, "name": "'.$v['name'] .'", "kwh": "'. number_format($costs->kwh, 3, ',', '') .'", "kwhLow": "'. number_format($costs->kwhLow, 3, ',', '') .'", "price": "'. number_format($costs->price, 2, ',', '') .'", "priceLow": "'. number_format($costs->priceLow, 2, ',', '') .'", "totalPrice": "'. number_format($costs->totalPrice, 2, ',', '') .'", "totalKwh": "'. number_format($costs->totalKwh, 3, ',', '').'"}';	
		}
			
		// Output data
		echo '['.implode(",", $json_results).']';	
		
	}	
	elseif(isset($_GET['a']) && $_GET['a'] == 'month' && isset($_GET['date']))
	{	
		
		$sqlDate = $_GET['date'];
		
		$begin = date('Y-m-d', strtotime('first day of this month', strtotime($sqlDate)));
		$end = date('Y-m-d', strtotime('last day of this month', strtotime($sqlDate)));	
				
		// Get data for each meter
		foreach($meters as $k => $v)
		{				
			// Get data from specific week
			$rows = $db->getMonth($v['id'], $begin, $end);

			// Reset vars for each meter
			$i=0;
			$dataStr = '';	
				
			if(count($rows) == 0)
			{
			
				$json_results[] = '{"ok": 0, "msg":"Geen data beschikbaar op deze datum", "start": '. (strtotime($begin)*1000) .', "val": [ 0 ], "kwh": 0, "name": "'.$v['name'] .'", "price": 0}';
			
			}
			else
			{	
				foreach($rows as $k)
				{
					// Extract data from value db field				
					$kwh = $k->kwh ;

					$dataStr .= ($i!=0 ? "," : "").$kwh;
					$i++;
				}
				
				// Create js key for meter
				$json_results[] = '{"ok": 1, "start": '. (strtotime($rows[0]->time)*1000) .', "val": ['. str_replace("\"", "", $dataStr) .'], "name": "'.$v['name'] .'", "unit": "Kwh"}';					
			}
		}
		// Output data
		echo '['.implode(",", $json_results).']';
	}	
	elseif(isset($_GET['a']) && $_GET['a'] == 'calculate_month' && isset($_GET['date']))
	{	
		
		$sqlDate = $_GET['date'];
		
		$month = date('m',strtotime($sqlDate));
		
		$start = date('Y-m-d', strtotime('first day of this month', strtotime($sqlDate)));
		$end = date('Y-m-d', strtotime('last day of this month', strtotime($sqlDate)));	
	
		// Get data for each meter
		foreach($meters as $k => $v)
		{
			// Calculate totals/costs
			$costs = $gen->calculateRangeKwhCosts($v['id'], $start, $end);
			$json_results[] = '{"ok": 1, "name": "'.$v['name'] .'", "kwh": "'. number_format($costs->kwh, 3, ',', '') .'", "kwhLow": "'. number_format($costs->kwhLow, 3, ',', '') .'", "price": "'. number_format($costs->price, 2, ',', '') .'", "priceLow": "'. number_format($costs->priceLow, 2, ',', '') .'", "totalPrice": "'. number_format($costs->totalPrice, 2, ',', '') .'", "totalKwh": "'. number_format($costs->totalKwh, 3, ',', '').'"}';	
		}
			
		// Output data
		echo '['.implode(",", $json_results).']';	
	}	
	elseif(isset($_GET['a']) && $_GET['a'] == 'year' && isset($_GET['date']))
	{	
		
		$sqlDate = $_GET['date'];
		
		$begin = date('Y-m-d', strtotime('-1 year', strtotime($sqlDate)));
		$end = date('Y-m-d', strtotime('+1 day', strtotime($sqlDate)));	
				
		// Get data for each meter
		foreach($meters as $k => $v)
		{				
			// Get data from specific week
			$rows = $db->getYear($v['id'], $begin, $end);

			// Reset vars for each meter
			$i=0;
			$dataStr = '';	
				
			if(count($rows) == 0)
			{
			
				$json_results[] = '{"ok": 0, "msg":"Geen data beschikbaar op deze datum", "start": '. (strtotime($begin)*1000) .', "val": [ 0 ], "kwh": 0, "name": "'.$v['name'] .'", "price": 0}';
			
			}
			else
			{	
				foreach($rows as $k)
				{
					// Extract data from value db field
					//$row = explode(",", $k->value);
					
					$kwh = $k->kwh ;
					
					$dataStr .= ($i!=0 ? "," : "").$kwh;
					$i++;
				}
				
				// Create js key for meter  
				$json_results[] = '{"ok": 1, "start": '. (strtotime($rows[0]->time)*1000) .', "val": ['. str_replace("\"", "", $dataStr) .'], "name": "'.$v['name'] .'", "unit": "Kwh"}';	
			}
		}
		// Output data
		echo '['.implode(",", $json_results).']';
	}	
	elseif(isset($_GET['a']) && $_GET['a'] == 'calculate_year' && isset($_GET['date']))
	{	
		
		$sqlDate = $_GET['date'];
		
		//$year = date('Y',strtotime($sqlDate));
		
		$start = date('Y-m-d', strtotime('-1 year', strtotime($sqlDate)));
		$end = date('Y-m-d', strtotime('+1 day', strtotime($sqlDate)));	
				
	
		// Get data for each meter
		foreach($meters as $k => $v)
		{
			// Calculate totals/costs
			$costs = $gen->calculateRangeKwhCosts($v['id'], $start, $end);
			$json_results[] = '{"ok": 1, "name": "'.$v['name'] .'", "kwh": "'. number_format($costs->kwh, 3, ',', '') .'", "kwhLow": "'. number_format($costs->kwhLow, 3, ',', '') .'", "price": "'. number_format($costs->price, 2, ',', '') .'", "priceLow": "'. number_format($costs->priceLow, 2, ',', '') .'", "totalPrice": "'. number_format($costs->totalPrice, 2, ',', '') .'", "totalKwh": "'. number_format($costs->totalKwh, 3, ',', '').'"}';	
		}
			
		// Output data
		echo '['.implode(",", $json_results).']';	
	}		
	elseif(isset($_GET['a']) && $_GET['a'] == 'saveSettings')
	{
	
		$includedFields = array(
			'autoupdate',
			'notifyupdate',
			'liveinterval',
			'dualcount',
			'cpkwh',
			'cpkwh_low',
			'cleanup'
		);
		
		foreach($_POST as $k => $v)
		{
			$$k = $v;
			if(in_array($k, $includedFields))
			{
				$db->updateSettings($k, $v);
			}
		}
		
		$cpkwhlow_start = $cpkwhlow_start_hour.":".$cpkwhlow_start_min;
		$cpkwhlow_end = $cpkwhlow_end_hour.":".$cpkwhlow_end_min;
		
		$db->updateSettings('cpkwhlow_start', $cpkwhlow_start);
		$db->updateSettings('cpkwhlow_end', $cpkwhlow_end);
	
		if($password != "" && $confirmpassword != "" && $password == $confirmpassword)
		{
			$db->updateLogin(sha1($password));
		}
		
		foreach($meter as $v)
		{
			$db->updateMeters($v['id'], $v['name'], $v['address'], $v['password']);
		}
		
		echo '{"ok": 1, "msg":"Instellingen succesvol opgeslagen"}';	
		
	}
	elseif(isset($_GET['a']) && $_GET['a'] == 'delMeter')
	{
		$meter = $_POST['meter'];
		$db->delMeter($meter);
		echo '{"ok": 1, "msg":"Meter succesvol verwijderd"}';			
	}	
	elseif(isset($_GET['a']) && $_GET['a'] == 'getChangelog')
	{
		$changelog = implode('<br>', $updater->getUpdateComments());	
		
		echo nl2br($changelog);	
		
	}	
	elseif(isset($_GET['a']) && $_GET['a'] == 'getUpdate')
	{
		$success = $updater->getUpdate();	
		
		if($success){
			echo '{"ok": 1, "msg":"Update succesvol!"}';
		}
		else
		{
			echo '{"ok": 0, "msg":"Er is iets fout gegaan"}';			
		}
		
	}	
	else
	{
		echo "Error!";
	}
}
else
{
	echo "Login required!";
}
?>