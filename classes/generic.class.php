<?php

class Generic {

    private $config;

    public function __construct($config)
    {
	    $this->config = $config;
    }

    /**
     * Create selector
     */
	public function selector($name, $selected, $options){
		$html = "<select name='".$name."'>\n";
		
		foreach($options as $k => $v) 
		{
			$html .= "<option value='" . $k . "'" . ($k==$selected?" selected":"") . ">$v</option>\n";
		}
		
		$html .= "</select>\n";
		
		return $html;
	}

    /**
     * Create time selector
     */
	public function timeSelector($selectedHour, $selectedMin, $prefix){
		$html = "<select name='".$prefix."_hour'>\n";
		for ($i=0;$i<24;$i++) 
		{
			$html .= "<option value='" . sprintf("%02d", $i) . "'" . ($i==$selectedHour?" selected":"") . ">$i</option>\n";
		}
		$html .= "</select>:<select name='".$prefix."_min'>\n";
		for ($i=0;$i<60;$i+=5) 
		{
			$html .= "<option value='" . sprintf("%02d", $i) . "'" . ($i==$selectedMin?" selected":"") . ">" . sprintf("%02d", $i) . "</option>\n";
		}
		$html .= "</select>";
		
		return $html;
	}

    /**
     * Calculate kwhs and costs for a range of days
     */	
     public function calculateRangeKwhCosts($meter, $beginDate, $endDate){
     	 
		$start = (date("Y-m-d 00:00:00", strtotime($beginDate)));
		$end = (date("Y-m-d 00:00:00", strtotime("+1 day", strtotime($endDate))));
		
		return $this->calculateTimeRangeKwhCosts($meter, $start, $end);
     }
     
    /**
     * Calculate kwhs and costs for specific day
     */	
     public function calculateDayKwhCosts($meter, $checkDate){
     	
		$start = (date( "Y-m-d 00:00:00", strtotime($checkDate)));
		$end = (date("Y-m-d 00:00:00", strtotime("+1 day", strtotime($checkDate))));
		
		return $this->calculateTimeRangeKwhCosts($meter, $start, $end);
     }    	
	 
	 public function calculateTimeRangeKwhCosts($meter, $beginDate, $endDate){

		$this->db = new Database($this->config);
     	$settings = $this->db->getSettings();
		
		$data->kwh = 0;
		$data->kwhLow = 0;
		$data->price = 0;
		$data->priceLow = 0;
		$data->totalKwh = 0;
		$data->totalPrice = 0;		
	
		$rows = $this->db->getSpecificTimeRange($meter, $beginDate, $endDate);
//			file_put_contents('php://stderr', print_r($rows, TRUE));
	
		foreach($rows as $k) {
			if ( $this->isLowKwh($k->time) == 0) {
				$data->kwh += $k->kwh;						
			} else {
				$data->kwhLow += $k->kwh;				
			}
		}
				
		$data->price = ($data->kwh * (float)$settings['cpkwh']);
		$data->priceLow = ($data->kwhLow * (float)$settings['cpkwh_low']);
		$data->totalKwh = $data->kwh + $data->kwhLow;
		$data->totalPrice = $data->price + $data->priceLow;
                      	 		
		return $data;	
		  
     }	 

	 /**
     * Determine low/high rate
     */	
     public function isLowKwh($checkDate){
     	
     	$this->db = new Database($this->config);
     	$settings = $this->db->getSettings();
		
		$kwhLow = 0;

		if($settings['dualcount'] == 1)
		{

			$getDay = date('N', strtotime($checkDate));
			$rtime = date('Hi',strtotime($checkDate));	
			$ttime = date('Y-m-d',strtotime($checkDate)); 
			
			$timeStart = (int)str_replace(":","", $settings['cpkwhlow_start']);
			$timeEnd = (int)str_replace(":","", $settings['cpkwhlow_end']);
			
			
			$holiday = $this->calculateHoliday(substr($checkDate,0,10));		
			if ($getDay == '6' || $getDay == '7' || $holiday == true || $rtime >= $timeStart || $rtime < $timeEnd ){
				$kwhLow = 1;
			}
		}
			
		return $kwhLow;		  
     }   
	 
     /**
     * Calculate if a specific day is a holiday
     */	
     public function calculateHoliday($checkDate){
		$jaar = date('Y');
		$feestdag = array();
	    $a = $jaar % 19;
	    $b = intval($jaar/100);
	    $c = $jaar % 100;
	    $d = intval($b/4);
	    $e = $b % 4;
	    $g = intval((8 *  $b + 13) / 25);
	    $theta = intval((11 * ($b - $d - $g) - 4) / 30);
	    $phi = intval((7 * $a + $theta + 6) / 11);
	    $psi = (19 * $a + ($b - $d - $g) + 15 -$phi) % 29;
	    $i = intval($c / 4);
	    $k = $c % 4;
	    $lamda = ((32 + 2 * $e) + 2 * $i - $k - $psi) % 7;
	    $maand = intval((90 + ($psi + $lamda)) / 25);
	    $dag = (19 + ($psi + $lamda) + $maand) % 32;    
	 
	    $feestdag[] = date('Y-m-d', mktime (1,1,1,1,1,$jaar));           // Nieuwjaarsdag
	    $feestdag[] = date('Y-m-d',mktime (0,0,0,$maand,$dag-2,$jaar));  // Goede Vrijdag
	    $feestdag[] = date('Y-m-d',mktime (0,0,0,$maand,$dag,$jaar));    // 1e Paasdag
	    $feestdag[] = date('Y-m-d',mktime (0,0,0,$maand,$dag+1,$jaar));  // 2e Paasdag
	    if ($jaar < '2014'){
			$feestdag[] = date('Y-m-d',mktime (0,0,0,4,30,$jaar));       // Koninginnedag    
	    }
	    else{
			$feestdag[] = date('Y-m-d',mktime (0,0,0,4,26,$jaar));       // Koningsdag    
	    }
	    $feestdag[] = date('Y-m-d',mktime (0,0,0,5,5,$jaar));            // Bevrijdingsdag
	    $feestdag[] = date('Y-m-d',mktime (0,0,0,$maand,$dag+39,$jaar)); // Hemelvaart
	    $feestdag[] = date('Y-m-d',mktime (0,0,0,$maand,$dag+49,$jaar)); // 1e Pinksterdag
	    $feestdag[] = date('Y-m-d',mktime (0,0,0,$maand,$dag+50,$jaar)); // 2e Pinksterdag
	    $feestdag[] = date('Y-m-d',mktime (0,0,0,12,25,$jaar));          // 1e Kerstdag
	    $feestdag[] = date('Y-m-d',mktime (0,0,0,12,26,$jaar));          // 2e Kerstdag
	    return in_array($checkDate, $feestdag) ? true : false;
	}   	
	
   /**
     * Edit config file
     */		 
	public function changeConfig($key, $value){
        $lines = file(BASE_PATH.'/inc/settings.inc.php', FILE_IGNORE_NEW_LINES);
        $count = count($lines);
        for($i=0; $i < $count; $i++)
        {
            $configline = '$config[\''.$key.'\']';
            if(strstr($lines[$i], $configline))
            {
                $lines[$i] = $configline.' = \''.$value.'\';';
                $file = implode(PHP_EOL, $lines);
                $handle = @fopen(BASE_PATH.'/inc/settings.inc.php', 'w');
                fwrite($handle, $file);
                fclose($handle);
                return true;
            }
        }	
	}	 
     
}

?>