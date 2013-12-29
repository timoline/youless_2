#!/usr/bin/php
<?php
if (PHP_SAPI == "cli")
{
	include "inc/settings.inc.php";
	include "classes/curl.class.php";
	include "classes/request.class.php";
	include "classes/database.class.php";
	include "classes/generic.class.php";	
	
	$db = new Database($config);
	$gen = new Generic($config);
	
	// Retrieve all available meters
	$meters = $db->getMeters();
	
	// Loop trough meters, add data to DB
	foreach($meters as $v)
	{
		$request = new Request($v);
				
		// Update data table
		$data = $request->getLastHour();		

		$row = explode(",", $data['val']);
		$total = count($row);
		$time = strtotime($data['tm']);
		for($t=1;$t<$total;$t++)
		{
			$mtime = $time + ( $t * $data['dt'] );
			$low = $gen->IsLowKwh(date('Y-m-d H:i:00',$mtime));

			$db->addMinuteData( $v['id'], date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], str_replace("\"", "",$row[$t]), $low );
			
			echo $v['id'] . " - ". $data['tm']." - ". $low."\n";		  
		}		
		
		// Update counter table
		$liveData = json_decode($request->getLiveData(), true);
	
		$time = time()-(86400*2);
		$nu = time();
		for ($i = $time; $i < $nu ;$i = $i + 60 ) {
			$db->addMissingMinuteData( $v['id'], date('Y-m-d H:i:00',$i));
		}
		
		
	}
	exit;
}
else
{
	echo "No direct access allowed!";
}
?>