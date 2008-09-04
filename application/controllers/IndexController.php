<?php

require_once 'Zend/Controller/Action.php';

class IndexController extends Zend_Controller_Action
{
    public function init() {
        $this->view->baseUrl = $this->_request->getBaseUrl();
    }

	public static function getForm()
    {
/*        $form = new Zend_Form(array(
            'method'   => 'post',
        	'action'   => "javascript:loadFoafData($(foaf_uri));",
            'elements' => array(
                	'foafUri' => array('text', array(
    					'required' => true,
                    	'label' => 'Paste FOAF uri in here.'
            		)    
        	),
            'submit' => array('submit', array(
                    'label' => 'Load'
             ))
            ),
        ));
*/

        return $form;
    }
    
    public function indexAction(){

    	/* $this->view->form = $this->getForm();*/
    }
	
    public function displayfoafAction()
    {
        require_once 'FoafData.php';
        $form = $this->getForm();
		
        if ($this->getRequest()->isPost()) {
            if (!$form->isValid($_POST)) {
                //FIXME: there ought to be a Zendy way of doing this and keeping validation errors etc.
            	header("Location: /");
            } else {
                $values = $form->getValues();
                    
                //One from the uri
                $foafData = new FoafData($values['foafUri']);

//TODO Am not sure here: foafData2 ? and model2? and model one ?                    
                //One from the session
//                $foafData2 = new FoafData();
                    
                $this->view->model = $foafData->getModel();
 //               $this->view->model2 = $foafData->getModel();
                    
                //do some sparql queries on the one in the session
//                $querystring = "SELECT * WHERE {?x ?y ?z};";
                $querystring = "SELECT * WHERE {?g {?x ?y ?z}};";
//                $result = $this->view->model2->sparqlQuery($querystring);
                $result = $this->view->model->sparqlQuery($querystring);
                var_dump($result);
            }
   	}
    }
	
}
/* vi:set expandtab sts=4 sw=4: */
