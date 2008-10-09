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
		
        $foafData = FoafData::getFromSession();	
        
    	if($foafData){
    	   $dirname = "/projects/foafeditor-dev/public/images/".substr($foafData->getPrimaryTopic(),-32);
       
    	   if(!file_exists($dirname)){
    	   		mkdir($dirname);			
    	   } 
    	   $new_filename = sha1(microtime()."_".rand(0,99999)).".gif";
    	   $new_name = $dirname."/".$new_filename;
    	   $url = "/images/".substr($foafData->getPrimaryTopic(),-32)."/".$new_filename;
    	   
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
    	   $dirname = "/projects/foafeditor-dev/public/images/".substr($foafData->getPrimaryTopic(),-32);
    	
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