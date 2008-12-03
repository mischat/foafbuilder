<?php
require_once 'Zend/Controller/Action.php';
require_once("helpers/JSON.php");

class FileController extends Zend_Controller_Action {
	
    public function init() {
        $this->view->baseUrl = $this->_request->getBaseUrl();
    }
	
    public function uploadImageAction() {	
    	//FIXME: make sensible paths that will work for all environments.
    	//TODO: possibly use Zends file transfer thing for validation etc here (need to download the relevant bit)
    	/*
    	 * We absolutely must have some validation here and we ought to store paths etc in some sensible place.
    	 * which will depend on the oauth server.
    	 */
    	$this->view->isSuccess = 0;
	$maximumSizeInBytes = 500000;
        $foafData = FoafData::getFromSession();	
        $allowedMimeTypesArray = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png');
        		
    	if($foafData){
    		
    	   $dirname = $_SERVER['DOCUMENT_ROOT']."/images/".substr($foafData->getPrimaryTopic(),-32,-5);

    	   /*create a new directory for this person if necessary*/
    	   if(!file_exists($dirname)){
    	   		mkdir($dirname);			
    	   } 
    	
    	   /*check that the uploaded file has a name*/
    	   if(!isset($_FILES['uploadedImage']['name']) || !$_FILES['uploadedImage']['name']){
    	   		return;
    	   }
    	    
    	   /*check that there wasn't an error uploading the file*/
    	   if($_FILES["uploadedImage"]["error"]){
    	   		return;
    	   }
    	   
    	   /*check that the file doesn't exceed the maximum size*/
    	   if($_FILES["uploadedImage"]["size"] > $maximumSizeInBytes){
    	   		return;   	
    	   }
    	   
    	   /*check that a mime type is set*/
    	   if(!$_FILES["uploadedImage"]["type"]){
    	   		return;
    	   }
    	   
    	   $existingFilename = $_FILES['uploadedImage']['name'];
    	   $fileExtension = substr($existingFilename, strrpos($existingFilename, '.') + 1);
    	   
    	   /*check that the file has an allowed mimetype*/
    	   if (!$fileExtension || !isset($allowedMimeTypesArray[$fileExtension]) 
    	   		|| $allowedMimeTypesArray[$fileExtension] != $_FILES["uploadedImage"]["type"]){
    	   		return;
    	   }
    	   
    	   $new_filename = sha1(microtime()."_".rand(0,99999)).".gif";   
    	   $new_name = $dirname."/".$new_filename;
    	   $url = "/images/".substr($foafData->getPrimaryTopic(),-32,-5)."/".$new_filename;
    	   
    	   if(move_uploaded_file($_FILES['uploadedImage']['tmp_name'], $new_name)){
    			$this->view->isSuccess  = $url;
		   } 
        }
	}
	/*either does nothing and returns 0 or acts and returns success*/
    public function removeImageAction() {	    	
    	/*
    	 * TODO The functionality of this depends on the oauth server and should be more secure.
    	 */
		$this->view->isSuccess = 0;
        $foafData = FoafData::getFromSession();	
        
    	if($foafData){
    	    if(!substr($foafData->getPrimaryTopic(),-32)){
    			return;
    	   }
    	   if(!isset($_POST['filename'])){
    	   		return;
    	   }
    	   
    	   $filename = $_POST['filename'];
    	   
    	   //(unstringently) check that it is one of ours, otherwise return.
    	   if(!strpos($filename,substr($foafData->getPrimaryTopic(),-32))){
    	   	   return;
    	   }
    	   
    	   //do some mangling to get the filename
    	   $filename_array = explode('/',$filename);
    	   $dirname = $_SERVER['DOCUMENT_ROOT']."/images/".substr($foafData->getPrimaryTopic(),-32);
    	
       	   $filePath = $dirname."/".$filename_array[count($filename_array)-1];
       	   
    	   if(!file_exists($dirname) || !file_exists($filePath)){
    	   		return;			
    	   }
    	  
    	   if(unlink($filePath)){
    	   		$this->view->isSuccess = 1; 
    	   } 
        }
	}
}
