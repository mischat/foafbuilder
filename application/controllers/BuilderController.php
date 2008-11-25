<?php
require_once 'helpers/settings.php';
require_once 'helpers/sparql.php';
require_once 'Zend/Controller/Action.php';

class BuilderController extends Zend_Controller_Action
{
    public function init() {
       $this->view->baseUrl = $this->_request->getBaseUrl();
    }
	public static function getForm()
    {
    	
    }
	public function indexAction(){	
    	$url = @$_GET['url'];
    	
    	if($url){
    		$this->view->url = $url;
    	}
    	
    	$query = "PREFIX gs: <http://qdos.com/schema#> 
    			PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> 
    			SELECT ?serv ?patt WHERE 
    			{ ?serv rdfs:subPropertyOf gs:serviceProperty ; gs:uriPattern ?patt }";
		$res = sparql_query(QDOS_EP,$query);
    	
		var_dump($res);
    	
	}
}

