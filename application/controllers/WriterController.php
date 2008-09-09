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
    
        if ($foafData) {
            $this->view->model = $foafData->getModel();
            $this->view->uri = $foafData->getURI();
            $this->view->graphset= $foafData->getGraphset();
            $this->view->primaryTopic = $foafData->getPrimaryTopic();

            $newDocUri = @$_POST['uri'];
            if (!$newDocUri) {
                $newDocUri = $this->view->uri;
            }

            //TODO must make sure that we handle having a non "#me" foaf:Person URI
            $newDocUriRes = new Resource($newDocUri);
            $newPersonUriRes = new Resource($newDocUri."#me");
            $oldPersonUriRes = new Resource($this->view->primaryTopic);
            $oldDocUriRes = new Resource($this->view->uri);

            $this->view->model->replace($oldDocUriRes,new Resource("<http://xmlns.com/foaf/0.1/primaryTopic>"),NULL,$newDocUriRes);
            $this->view->model->replace($oldPersonUriRes,NULL,NULL,$newPersonUriRes);
            $this->view->model->replace(NULL,NULL,$oldPersonUriRes,$newPersonUriRes);

            $result = $this->view->model->find(NULL, NULL, NULL);
            echo($result->writeRdfToString());
        } else {
            echo("Nothing to Write, Session is empty");
        }
    }

}
/* vi:set expandtab sts=4 sw=4: */
