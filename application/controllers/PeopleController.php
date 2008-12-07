<?php
/*Renders test foaf files*/
require_once 'Zend/Controller/Action.php';

class PeopleController extends Zend_Controller_Action
{
	//This overrides all actions
	public function dispatch($action) {
		// 3. Get all request parameters
		$this->data_dir = '/usr/local/data/public/';
		$this->uri = $_SERVER['SCRIPT_URI'];
		$this->url = $_SERVER['SCRIPT_URL'];
		
		 if (preg_match('/^\/people\/(.+?)$/',$this->url,$matches)) {
			$cachename = $this->cache_filename($this->uri);
			if (file_exists($this->data_dir.$cachename)) {
				if (preg_match('/\.rdf$/',$this->uri)) {
					header('Content-Type: application/rdf+xml');
				} else if (preg_match('/\.nt$/',$this->uri)) {
					header('Content-Type: text/plain');
				} else if (preg_match('/\.n3$/',$this->uri)) {
					header('Content-Type: text/rdf+n3');
				} else if (preg_match('/\.ttl$/',$this->uri)) {
					header('Content-Type: application/x-turtle');
				} else {
					header('Content-Type: text/plain');
				}
				echo file_get_contents($this->data_dir.$cachename);   
				exit(0);
			} 
		} 
		header('HTTP/1.1 404 Internal Server Error');
		header('Content-Type: text/plain');
		echo "Error 404: this file does not exist ".$this->uri."\n";
		exit(0);
	}

	//Create the filename used for the hashing of rdf
	function cache_filename($uri) {
	    $hash = md5($uri);
	    preg_match('/(..)(..)(.*)/', $hash, $matches);
	    return '/'.$matches[1].'/'.$matches[2].'/'.$matches[3];
	} //end cache filename

	//Create the cache file directory structure needed
	function create_cache($filename,$datadir) {
		if (preg_match('/\/(..)\/(..)\/(.*)/',$filename,$matches)) {
			if (!(file_exists("$datadir/$matches[1]"))) {
				mkdir("$datadir/$matches[1]");
			}
			if (!(file_exists("$datadir/$matches[1]/$matches[2]"))) {
				mkdir("$datadir/$matches[1]/$matches[2]");
			}
			return true;
		} else {
			//Incorrect cache filestructure passed
			return false;
		}
	}

}
