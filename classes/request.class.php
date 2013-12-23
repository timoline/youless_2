<?php

class Request {

	private $source;
	private $password;
	private $data;
	private $opts;
	private $optsSetSes;
	private $cookie;
	private $timeout = 35;
    
	public function __construct($meter_data) {
		$this->cookie = BASE_PATH.'/tmp/cookie_'.$meter_data['id'].'.txt';
		$this->source = 'http://'.$meter_data['address'].'/';
		$this->password = $meter_data['password'];
		$this->opts = array( 
			CURLOPT_RETURNTRANSFER => true, 
			CURLOPT_FOLLOWLOCATION => false  
		);	
		
		if($this->password != '')
		{
			$this->opts[CURLOPT_COOKIEFILE] = $this->cookie;
		}
	}   
	
    /**
     * Do request, check for cookies
     */	
	public function request($sessions){
		$code = 0;
		$retry = 2;
		while(($code == 0 || $code == 403) && $retry != 0){
			$curl = new Curl();
		
			foreach($sessions as $session){
				$curl->addSession( $session, $this->opts );
			}

			$result = $curl->exec();
			$resultCode = $curl->info((int)0, CURLINFO_HTTP_CODE);
			$code = $resultCode[0];
			
			$curl->clear();	
			
			if($code == 0 || $code == 403){
				// If current cookie gives forbidden, try new login
				$this->setCookie();
			}
			$retry--;
		}	
		
		return $result;
	}	   
	
    /**
     * Set cookie
     */
	public function setCookie() {
	
		set_time_limit($this->timeout*3);
		
		// Cleanup old cookie
		if(file_exists($this->cookie)){
			unlink($this->cookie);
		}

		$curl = new Curl();
		$curl->retry = 2;
		sleep($this->timeout);

		$optsSet = array( 
			CURLOPT_RETURNTRANSFER => true, 
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_COOKIEJAR => $this->cookie
		);
		
		$curl->addSession( $this->source.'L?w='.$this->password, $optsSet );

		$curl->exec();
		$result = $curl->info();		
		$curl->clear();	
		
	} 	 

    /**
     * Get live data
     */
	public function getLiveData() {
	
		$sessions = array(
			 $this->source.'a?f=j'
		);
		$result = $this->request($sessions);	
		
		return $result;
	} 
		
    /**
     * Get last hour
     */
	public function getLastHour() {
		
		$sessions = array(
			$this->source.'V?h=1&f=j',
			$this->source.'V?h=2&f=j'
		);
		$result = $this->request($sessions);
		
		$part1 = json_decode($result[0], true);
		$part2 = json_decode($result[1], true);
	
		$values = array_merge($part2['val'], $part1['val']);
		
		foreach($values as $k => $v){
			if($v == NULL){
				unset($values[$k]);
			}
			elseif($v == '*')
			{
				$values[$k] = '0';
			}
		}
		$val = implode('","', $values);
		
		$data['un'] = $part2['un'];
		$data['tm'] = $part2['tm'];
		$data['dt'] = $part2['dt'];
		$data['val'] = $val;
		
		return $data;
	} 		 
	
    /**
     * Get specific month
     */
	public function getSpecificMonth($month) {

		// Check for password and create cookie
		$this->setCurlSession();

		$curl = new Curl();
		$curl->retry = 2;
		
		$curl->addSession( $this->source.'V?m='.$month.'&f=j', $this->opts );

		$result = $curl->exec();
		$curl->clear();	
		
		// Check for password and delete cookie
		//$this->delCookie();	

		$json = json_decode($result, true);
		
		$values = $json['val'];
		foreach($values as $k => $v){
			if($v == NULL){
				unset($values[$k]);
			}
			elseif($v == '*')
			{
				$values[$k] = '0';
			}
		}
		$val = implode('","', $values);
		
		$data['un'] = $json['un'];
		$data['tm'] = $json['tm'];
		$data['dt'] = $json['dt'];
		$data['val'] = $val;

		return $data;
	} 	
}
?>