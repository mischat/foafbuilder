<?php 

// Step 1: Check to see if the applicaiton environment is already setup 
if (isset($bootstrap) && $bootstrap) { 

	// Step 1a: Add our {{library}} directory to the include path so that PHP can find the Zend Framework classes. 
    /*Set variables for rap/rdfapi-php/api*/
	define("RDFAPI_INCLUDE_DIR", "");


	/** Setting paths */
	$rootPath = dirname(dirname(__FILE__));
	set_include_path(get_include_path() . PATH_SEPARATOR . 
                 $rootPath . '/application/config' . PATH_SEPARATOR . 
                 $rootPath . '/application/models' . PATH_SEPARATOR . 
                 $rootPath . '/library' . PATH_SEPARATOR . 
                 $rootPath . '/public' . PATH_SEPARATOR . 
                 $rootPath . '/rdfapi-php/api/' . PATH_SEPARATOR . 
                 $rootPath . '/rdfapi-php/sparql/');
 
    // Step 1b: Set up autoload. 
    // This is a nifty trick that allows ZF to load classes automatically so that you don't have to litter your 
    // code with 'include' or 'require' statements. 
    require_once "Zend/Loader.php"; 
    Zend_Loader::registerAutoload(); 
    
    /** Load application configuration ini file */ 
	require_once 'Zend/Config/Ini.php'; 
	$config = new Zend_Config_Ini('foafeditor.ini', 'default'); 
	
	/** Setup layout */ 
	require_once 'Zend/Layout.php'; 
	Zend_Layout::startMvc($config->appearance); 
 
	// Step 2: Get the front controller. 
	// The Zend_Front_Controller class implements the Singleton pattern, which is a 
	// design pattern used to ensure there is only one instance of 
	// Zend_Front_Controller created on each request. 
	$frontController = Zend_Controller_Front::getInstance(); 
	 
	// Step 3: Point the front controller to your action controller directory. 
	$frontController->setControllerDirectory('../application/controllers'); 
	 
	Zend_Session::setSaveHandler(new FoafSessionHandler());
	Zend_Session::start();

	// Step 4: Set the current environment 
	// Set a variable in the front controller indicating the current environment -- 
	// commonly one of development, staging, testing, production, but wholly 
	// dependent on your organization and site's needs. 
	$frontController->setParam('env', 'development'); 
}

?>
