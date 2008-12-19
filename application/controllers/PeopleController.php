<?php
/*Renders test foaf files*/
require_once 'Zend/Controller/Action.php';
require_once("helpers/settings.php");
require_once("helpers/write-utils.php");

class PeopleController extends Zend_Controller_Action
{
	//This overrides all actions
	public function dispatch($action) {
		// 3. Get all request parameters
		$this->uri = $_SERVER['SCRIPT_URI'];
		$this->url = $_SERVER['SCRIPT_URL'];
		$this->uri = preg_replace('/#/','%23',$this->uri);
		
		 if (preg_match('/^\/people\/(.+?)$/',$this->url,$matches)) {
			$cachename = cache_filename($this->uri);
			if (file_exists(PUBLIC_DATA_DIR.$cachename)) {
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
				echo file_get_contents(PUBLIC_DATA_DIR.$cachename);   
				exit(0);
			} 
		} 
		header('HTTP/1.1 404 Internal Server Error');
		header('Content-Type: text/plain');
		echo "Error 404: this file does not exist ".$this->uri."\n";
		exit(0);
	}
}
