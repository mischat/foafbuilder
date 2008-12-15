<?php
/*Renders test foaf files*/
require_once 'Zend/Controller/Action.php';
require_once("helpers/settings.php");
require_once("helpers/write-utils.php");

class ImagesController extends Zend_Controller_Action
{
	//This overrides all actions
	public function dispatch($action) {
		// 3. Get all request parameters
		$this->uri = $_SERVER['SCRIPT_URI'];
		$this->url = $_SERVER['SCRIPT_URL'];
		
		 if (preg_match('/^\/images\/([A-Za-z0-9\.]+?)$/',$this->url,$matches)) {
			$cachename = cache_filename($this->uri);
			if (file_exists(IMAGE_DATA_DIR.$cachename)) {
				if (preg_match('/\.jpe{0,1}g$/i',$this->uri)) {
					header('Content-Type: image/jpg');
				} else if (preg_match('/\.png$/i',$this->uri)) {
					header('Content-Type: image/png');
				} else if (preg_match('/\.gif$/i',$this->uri)) {
					header('Content-Type: image/gif');
				} 
				print file_get_contents(IMAGE_DATA_DIR.$cachename);   
				exit(0);
			} 
		} 
		header('HTTP/1.1 404 Internal Server Error');
		header('Content-Type: text/plain');
		echo "Error 404: this file does not exist ".$this->uri."\n";
		exit(0);
	}
}
