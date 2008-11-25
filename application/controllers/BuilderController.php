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
    		$url = @$_GET['uri'];
 		$flickr = @$_GET['flickr'];
		$delicious = @$_GET['delicious'];
		$lastfm = @$_GET['lastfmUser'];
		
		if($flickr){
			$flickr = file_get_contents('http://foaf.qdos.com/flickr/people/'.$flickr);
		}
		if($delicious){
			$delicious = file_get_contents('http://foaf.qdos.com/delicious/people/'.$delicious);
		}
		if($lastfm){
			$lastfm = file_get_contents('http://foaf.qdos.com/lastfm/people/'.$lastfm);
		}
		
		echo("\n".$lastfm."\n");
		echo("\n".$delicious."\n");
		echo("\n".$flickr."\n");
		echo("hmm");
		//$this->view->results = $uris;
	}
}

