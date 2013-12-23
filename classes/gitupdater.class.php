<?php

class GitUpdater
{
    const API_URL = 'https://api.bitbucket.org/1.0/repositories/';
    const GITHUB_URL = 'https://bitbucket.org/';
    const CONFIG_FILE = 'inc/settings.inc.php';
    
    private $config;

    public function __construct($config)
    {
	    $this->config = $config;
    }

    /**
     * Checks if the current version is up to date
     *
     * @return bool true if there is an update and false otherwise
     */
    public function hasUpdate()
    {
        $tags = json_decode($this->_connect(self::API_URL.$this->config['bitbucket_user'].'/'.$this->config['bitbucket_repo'].'/tags'));
		foreach($tags as $tag => $obj){
			if(version_compare($tag, $this->config['current_tag'], '>')){
				return true;
			}
		}
		return false;
    }

    /**
     * If there is an update available get an array of all of the
     * commit messages between the versions
     *
     * @return array of the messages or false if no update
     */
    public function getUpdateComments()
    {
        $tags = json_decode($this->_connect(self::API_URL.$this->config['bitbucket_user'].'/'.$this->config['bitbucket_repo'].'/tags'));
		
		$current_tag = $this->config['current_tag'];
		$latest_tag = $current_tag;
		
		foreach($tags as $tag => $obj){
			if(version_compare($tag, $latest_tag, '>')){
				$latest_tag = $tag;
			}		
		}
		
		$current_hash = $tags->{$current_tag}->raw_node;
		$latest_hash = $tags->{$latest_tag}->raw_node;

        if($current_hash !== $latest_hash)
        {
            $messages = array();
            $response = json_decode($this->_connect(self::API_URL.$this->config['bitbucket_user'].'/'.$this->config['bitbucket_repo'].'/changesets?limit=50&start='.$latest_hash));
            $commits = array_reverse($response->changesets);

            foreach($commits as $commit)
            {
            	if($commit->raw_node == $current_hash)
            	{	
					break;
                }
                else
                {
            		if($commit->branch == $this->config['bitbucket_branch'] || $commit->branch == null)
            		{
 		               	$messages[] = $commit->message;
 		            }
                }
            }
            return $messages;
        }
        return false;
    }

    /**
     * Performs an update if one is available.
     *
     * @return bool true on success, false on failure
     */
    public function getUpdate()
    {
        $tags = json_decode($this->_connect(self::API_URL.$this->config['bitbucket_user'].'/'.$this->config['bitbucket_repo'].'/tags'));
		
		$current_tag = $this->config['current_tag'];
		$latest_tag = $current_tag;
		
		foreach($tags as $tag => $obj){
			if(version_compare($tag, $latest_tag, '>')){
				$latest_tag = $tag;
			}		
		}
		
		$current_hash = $tags->{$current_tag}->raw_node;
		$latest_hash = $tags->{$latest_tag}->raw_node;
		
        if($current_hash !== $latest_hash)
        {
            $commits = json_decode($this->_connect(self::API_URL.$this->config['bitbucket_user'].'/'.$this->config['bitbucket_repo'].'/compare/'.$latest_hash.'..'.$current_hash));
            $files = $commits->files;
            if($dir = $this->_get_and_extract($latest_tag))
            {
                //Loop through the list of changed files for this commit
                foreach($files as $file)
                {
                    //If the file isn't in the ignored list then perform the update
                    if(!$this->_is_ignored($file->path))
                    {
                        //If the status is removed then delete the file
                        if($file->type === 'removed')unlink($file->path);
                        //Otherwise copy the file from the update.
                        else copy($dir.'/'.$file->path, $file->path);
                    }
                }
                //Clean up
                if($this->config['clean_update_files'])
                {
                    //shell_exec("rm -rf {$dir}");
                    $this->_del_dir($dir);
                    unlink("updates/{$hash}.zip");
                }
                //Update the current commit hash
                $this->_set_config_tag($latest_tag);

                return true;
            }
        }
        return false;
    }
    
	private function _del_dir($directory, $empty=FALSE)
	{
		if(substr($directory,-1) == '/')
		{
			$directory = substr($directory,0,-1);
		}
		if(!file_exists($directory) || !is_dir($directory))
		{
			return FALSE;
		}elseif(is_readable($directory))
		{
			$handle = opendir($directory);
			while (FALSE !== ($item = readdir($handle)))
			{
				if($item != '.' && $item != '..')
				{
					$path = $directory.'/'.$item;
					if(is_dir($path)) 
					{
						$this->_del_dir($path);
					}else{
						unlink($path);
					}
				}
			}
			closedir($handle);
			if($empty == FALSE)
			{
				if(!rmdir($directory))
				{
					return FALSE;
				}
			}
		}
		return TRUE;
	}    

    private function _is_ignored($filename)
    {
        $ignored = $this->config['ignored_files'];
        foreach($ignored as $ignore)
            if(strpos($filename, $ignore) !== false)return true;

        return false;
    }

    private function _set_config_tag($tag)
    {
        $lines = file(self::CONFIG_FILE, FILE_IGNORE_NEW_LINES);
        $count = count($lines);
        for($i=0; $i < $count; $i++)
        {
            $configline = '$config[\'current_tag\']';
            if(strstr($lines[$i], $configline))
            {
                $lines[$i] = $configline.' = \''.$tag.'\';';
                $file = implode(PHP_EOL, $lines);
                $handle = @fopen(self::CONFIG_FILE, 'w');
                fwrite($handle, $file);
                fclose($handle);
                return true;
            }
        }
        return false;
    }

    private function _get_and_extract($latest_tag)
    {
        $data = $this->_connect(self::GITHUB_URL.$this->config['bitbucket_user'].'/'.$this->config['bitbucket_repo'].'/get/'.$latest_tag.'.zip');
        $file = "updates/{$latest_tag}.zip";
        file_put_contents($file, $data);
        
		// get the absolute path to $file
		$path = pathinfo(realpath($file), PATHINFO_DIRNAME);
		
		$zip = new ZipArchive;
		$res = $zip->open($file);
		if ($res === TRUE) 
		{
		  	// extract it to the path we determined above
		  	$zip->extractTo($path);
		  	$zip->close();
		} 
		else 
		{
		  	return false;
		}
        $files = scandir('updates');
        foreach($files as $file)
            if(strpos($file, $this->config['bitbucket_user'].'-'.$this->config['bitbucket_repo']) !== FALSE)return 'updates/'.$file;

        return false;
    }

    
	private function _connect($url)
	{
		$curl = new Curl();
		
		$opts = array( 
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_SSLVERSION => 3,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => 2,			
			CURLOPT_RETURNTRANSFER => true, 
			CURLINFO_HEADER_OUT => true
		);			
		
		$curl->addSession( $url, $opts );

		$result = $curl->exec();
		$curl->clear();		
		
		return $result;
	}     
}
?>