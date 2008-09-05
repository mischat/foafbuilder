<?php 
// Step 1: Set a flag indicating setup is necessary 
$bootstrap = true; 
 
// Step 2: Setup PHP environment 
// In this case, we will setup error reporting, but any ini_set or 
// environment-related directives should go here. This way, you can create a 
// separate PHP environment for running tests. 
error_reporting(E_ALL);  
ini_set('display_startup_errors', 1);  
ini_set('display_errors', 1); 
 
// Step 3: Perform application-specific setup 
// This allows you to setup the MVC environment to utilize. Later you can re-use this file for testing your applications 
require '../application/bootstrap.php';  

// Step 4:  Dispatch the request using the front controller. 
// The front controller is a singleton, and should be setup by now. We will grab 
// an instance and dispatch it, which dispatches your application. 
Zend_Controller_Front::getInstance()->dispatch(); 
