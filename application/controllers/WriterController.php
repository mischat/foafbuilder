<?php

require_once 'Zend/Controller/Action.php';

class WriterController extends Zend_Controller_Action
{
    public function init() {
        $this->view->baseUrl = $this->_request->getBaseUrl();
    }

    public function writeFoafAction() {
        require_once 'FoafData.php';
        //NOTE: keep this change after mischa's alterations.
        $foafData = FoafData::getFromSession();

        if($foafData) {
            $this->view->model = $foafData->getModel();
            $this->view->uri = $foafData->getURI();
            $this->view->graphset= $foafData->getGraphset();
            $result = $this->view->model->find(NULL, NULL, NULL);
            print $result->writeRdfToString();
        }
    }

}
/* vi:set expandtab sts=4 sw=4: */
