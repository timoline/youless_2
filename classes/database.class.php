<?php

class Database {

    private $_db = null;

    /**
     * Constructor, makes a database connection
     */
    public function __construct($config) {

        try {
            $this->_db = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'], $config['db_user'], $config['db_pass'], array( 
      			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
   			));
            $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->_db->query('SET CHARACTER SET utf8');
        } catch (PDOException $e) {
            exit('Error while connecting to database.'.$e->getMessage());
        }
    }

    private function printErrorMessage($message) {
        echo $message;
    }

    /**
     * Get login 
     */
     public function getLogin($username, $password) {
        try {
            $sth = $this->_db->prepare("SELECT id FROM users WHERE username= ? AND password= ? ");

            $sth->bindValue(1, $username, PDO::PARAM_STR);
            $sth->bindValue(2, $password, PDO::PARAM_STR);
            $sth->execute();
            $row = $sth->fetch(PDO::FETCH_OBJ);
			return $row->id;
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }

    /**
     * Update login 
     */
     public function updateLogin($password) {
        try {
            $sth = $this->_db->prepare("UPDATE users SET password= ? WHERE username='admin'");

            $sth->bindValue(1, $password, PDO::PARAM_STR);
            $sth->execute();

			return $sth->rowCount();
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }
    
	/**
     * Update settings
     */
    public function updateSettings($key, $value) {
        try {
			$sth = $this->_db->prepare("INSERT INTO settings (`value`,`key`) VALUES (:value, :key) ON DUPLICATE KEY UPDATE `value`=:value, `key`=:key");
			
			$sth->bindValue(':value', $value, PDO::PARAM_STR);
			$sth->bindValue(':key', $key, PDO::PARAM_STR);			
            $sth->execute();
            
			return $sth->rowCount();
       } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }     
        
    /**
     * Get settings 
     */
     public function getSettings() {
        try {
            $sth = $this->_db->prepare("SELECT * FROM settings");
            
            $sth->setFetchMode(PDO::FETCH_ASSOC);
            $sth->execute();

            $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
                  
            foreach($rows as $k => $v)
            {
            	$settings[$v['key']] = $v['value'];
            }
            
            return $settings;
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }    
    
	/**
     * Update meters
     */
    public function updateMeters($id, $name, $address, $password) {
        try {
			$sth = $this->_db->prepare("INSERT INTO meters (
				`id`,
				`name`,
				`address`,
				`password`
			) VALUES (
				:id,
				:name, 
				:address,
				:password
			) ON DUPLICATE KEY UPDATE 
				`id`=:id, 
				`name`=:name,
				`address`=:address,
				`password`=:password
				");
			
			$sth->bindValue(':id', $id, PDO::PARAM_INT);
			$sth->bindValue(':name', $name, PDO::PARAM_STR);			
			$sth->bindValue(':address', $address, PDO::PARAM_STR);						
			$sth->bindValue(':password', $password, PDO::PARAM_STR);											
            $sth->execute();
            
            $insert_id = $this->_db->lastInsertId();
            
            if($insert_id)
            {
	            $this->_db->exec("				
					CREATE TABLE IF NOT EXISTS `meter".$insert_id."_data_m` (
					  `id` int(11) NOT NULL AUTO_INCREMENT,
					  `time` datetime NOT NULL,
					  `unit` varchar(20) NOT NULL,
					  `delta` int(11) NOT NULL,
					  `value` text NOT NULL,
					  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
					  `islow` tinyint(1) ,
					  PRIMARY KEY (`id`),
					  KEY `time` (`time`)
					) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;																					
			    ");  
			}          
            
			return $sth->rowCount();
       } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }  
    
    /**
     * Get meters 
     */
     public function getMeters($id = null) {
        try {
        	if($id == null)
        	{
            	$sth = $this->_db->prepare("SELECT * FROM meters");
            
            	$sth->setFetchMode(PDO::FETCH_ASSOC);
            	$sth->execute();

            	$rows = $sth->fetchAll(PDO::FETCH_ASSOC);
            
            	return $rows;
            }
            else
            {
	            $sth = $this->_db->prepare("SELECT * FROM meters WHERE id = :id LIMIT 1;");
	
				$sth->bindValue(':id', $id, PDO::PARAM_INT);           			
	            $sth->setFetchMode(PDO::FETCH_ASSOC);
	            $sth->execute();
	            
	            $row = $sth->fetch(PDO::FETCH_ASSOC);
				return $row;            
            }
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }   
      
    /**
     * Delete meter
     */
     public function delMeter($id = null) {
        try {
        	if($id != null)
        	{       	
            	$sth = $this->_db->prepare("DELETE FROM meters WHERE id = ? ");
            
            	$sth->bindValue(1, $id, PDO::PARAM_INT); 
            	$sth->execute();

            	$this->_db->exec("DROP TABLE `meter".$id."_data_m`");	
            }
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }           

	/**
	* Get specific day
	*/
    public function getSpecificDay($meter, $date) {
        try {
            $sth = $this->_db->prepare("
            SELECT
            	*
            FROM 
            	`meter".$meter."_data_m`
            WHERE
            	DATE_FORMAT(time, '%Y-%m-%d') = ?	
            ORDER BY  
				time ASC");

			$sth->bindValue(1, $date, PDO::PARAM_STR);           			
            $sth->setFetchMode(PDO::FETCH_ASSOC);
            $sth->execute();

            $rows = $sth->fetchAll(PDO::FETCH_OBJ);
			return $rows;
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }

	/**
	* Get specific range
	*/
    public function getSpecificRange($meter, $begin, $end) {
        try {
            $sth = $this->_db->prepare("
            SELECT
            	*
            FROM 
            	`meter".$meter."_data_m`
            WHERE
            	DATE_FORMAT(time, '%Y-%m-%d') BETWEEN ? AND ?	
            ORDER BY  
				time ASC");

			$sth->bindValue(1, $begin, PDO::PARAM_STR);  
			$sth->bindValue(2, $end, PDO::PARAM_STR);  			         			
            $sth->setFetchMode(PDO::FETCH_ASSOC);
            $sth->execute();

            $rows = $sth->fetchAll(PDO::FETCH_OBJ);
			return $rows;
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }  
  
 	/**
	* Get specific timerange
	*/
    public function getSpecificTimeRange($meter, $begin, $end) {
        try {
            $sth = $this->_db->prepare("			
			SELECT 
				sum(value)/60/1000 as kwh,
				time,
				islow
			FROM
				`meter".$meter."_data_m` 
            WHERE
                DATE_FORMAT(time, '%Y-%m-%d H%:i%') BETWEEN ? AND ?	
			GROUP BY
				islow");

			$sth->bindValue(1, $begin, PDO::PARAM_STR);  
			$sth->bindValue(2, $end, PDO::PARAM_STR);  			         			
            $sth->setFetchMode(PDO::FETCH_ASSOC);
            $sth->execute();

            $rows = $sth->fetchAll(PDO::FETCH_OBJ);
			return $rows;
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }  
	
	/**
	* Get month
	*/
    public function getMonth($meter, $begin, $end) {
        try {
            $sth = $this->_db->prepare("
			SELECT     
				sum(value)/60 as kwh, 
				time,
				unit
			FROM 
				`meter".$meter."_data_m`
			WHERE       
				DATE_FORMAT(time, '%Y-%m-%d') BETWEEN ? AND ?
			GROUP BY  
				DATE_FORMAT(time, '%Y-%m-%d')			
            ORDER BY  
				time ASC");

			$sth->bindValue(1, $begin, PDO::PARAM_STR);  
			$sth->bindValue(2, $end, PDO::PARAM_STR);  			         			
            $sth->setFetchMode(PDO::FETCH_ASSOC);
            $sth->execute();

            $rows = $sth->fetchAll(PDO::FETCH_OBJ);
			return $rows;
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }    
   
	/**
	* Get year
	*/
    public function getYear($meter, $begin, $end) {
        try {
            $sth = $this->_db->prepare("
			SELECT     
				sum(value)/60 as kwh, 
				time,
				unit
			FROM 
				`meter".$meter."_data_m`
			WHERE       
				DATE_FORMAT(time, '%Y-%m-%d') BETWEEN ? AND ?
			GROUP BY  
				DATE_FORMAT(time, '%Y-%m')			
            ORDER BY  
				time ASC");

			$sth->bindValue(1, $begin, PDO::PARAM_STR);  
			$sth->bindValue(2, $end, PDO::PARAM_STR);  			         			
            $sth->setFetchMode(PDO::FETCH_ASSOC);
            $sth->execute();

            $rows = $sth->fetchAll(PDO::FETCH_OBJ);
			return $rows;
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }    
	
	/**
	* Get kwh count

    public function getKwhCount($meter, $datetime) {
        try {
            $sth = $this->_db->prepare("
			SELECT 
				value
			FROM 
				`meter".$meter."_data`
			ORDER BY 
				ABS(DATE_FORMAT(`inserted`, '%Y%m%d%H%i%s')	 - :date) ASC
			LIMIT 1;");			

			$sth->bindValue(':date', $datetime, PDO::PARAM_STR);           			
            $sth->setFetchMode(PDO::FETCH_ASSOC);
            $sth->execute();
            
            $row = $sth->fetch(PDO::FETCH_OBJ);
			return $row;
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }     
	*/
   /**
    * Add minute data (cronjob)
    */ 
    public function addMinuteData($meter, $time, $unit, $delta, $values, $islow) {

        try {
            $sth = $this->_db->prepare("INSERT INTO `meter".$meter."_data_m` (
            	time,
				unit,
				delta,
				value,
				islow
            ) VALUES (
            	:time,
				:unit,
				:delta,
				:value,
				:islow
            ) 
	    ON DUPLICATE KEY UPDATE value = :value, delta = :delta, unit = :unit, islow = :islow");

            $sth->bindValue(':time', $time, PDO::PARAM_STR);
			$sth->bindValue(':unit', $unit, PDO::PARAM_STR);
			$sth->bindValue(':delta', $delta, PDO::PARAM_INT);
			$sth->bindValue(':value', $values, PDO::PARAM_INT);
			$sth->bindValue(':islow', $islow, PDO::PARAM_INT);
			
            $sth->execute();
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    } 
	
   /**
    * Add hourly data (cronjob)
  
    public function addHourlyData($meter, $time, $unit, $delta, $values) {
        try {
            $sth = $this->_db->prepare("INSERT INTO `meter".$meter."_data` (
            	time,
				unit,
				delta,
				value
            ) VALUES (
            	?,
				?,
				?,
				?
            )");

            $sth->bindValue(1, $time, PDO::PARAM_STR);
			$sth->bindValue(2, $unit, PDO::PARAM_STR);
	 		$sth->bindValue(3, $delta, PDO::PARAM_INT);
			$sth->bindValue(4, $values, PDO::PARAM_STR);
            $sth->execute();
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    } 	
  */ 
   /**
    * Add missing minute data (cronjob)
    */ 
    public function addMissingMinuteData($meter, $time) {

        try {
            $sth = $this->_db->prepare("INSERT INTO `meter".$meter."_data_m` (
				time,
				unit,
				delta,
				value,
				islow
            ) select :time,
				'',
				'-1',
				'0',
				'0'
              from dual where exists (select * from meter".$meter."_data_m where time <  :time);
	    ");

            $sth->bindValue(':time', $time, PDO::PARAM_STR);
            $sth->execute();
        } catch (PDOException $e) {
        }
    } 
   /**
    * Add hourly count (cronjob)
  
    public function addHourlyCount($meter, $kwh) {
        try {
            $sth = $this->_db->prepare("INSERT INTO `meter".$meter."_counter` (
            	kwh
            ) VALUES (
				?
            )");

            $sth->bindValue(1, $kwh, PDO::PARAM_STR);
            $sth->execute();
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }     
       */
	
	/**
	* Get all data grouped by time for islow update
	*/
    public function data_m($meter) {
        try {
            $sth = $this->_db->prepare("
            SELECT
            	*
            FROM 
            	`meter".$meter."_data_m`
            ORDER BY  
				time ASC");

            $sth->execute();

            $rows = $sth->fetchAll(PDO::FETCH_OBJ);
			return $rows;
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }  	   
}
?>