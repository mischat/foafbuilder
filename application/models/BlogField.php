<?php
//XXX this is just like homepage field
require_once 'Field.php';
require_once 'helpers/Utils.php';
/*FIXME: perhaps fields shouldn't do the whole sparql query thing in the constructor.*/

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class BlogField extends Field {
	
    /*predicateUri is only appropriate for simple ones (one triple only)*/
    public function BlogField($foafDataPublic,$foafDataPrivate,$fullInstantiation = true) {
    	
        $this->name = 'foafWeblog';
        $this->label = 'Blog';

        $this->data['public']['foafWeblogFields'] = array();
        $this->data['public']['foafWeblogFields']['values'] = array();
        $this->data['public']['foafWeblogFields']['displayLabel'] = $this->label;
        $this->data['public']['foafWeblogFields']['name'] = $this->name;

        $this->data['private']['foafWeblogFields'] = array();
        $this->data['private']['foafWeblogFields']['values'] = array();
        $this->data['private']['foafWeblogFields']['displayLabel'] = $this->label;
        $this->data['private']['foafWeblogFields']['name'] = $this->name;

        /*don't sparql query the model etc if a full instantiation is not required*/
        if (!$fullInstantiation){
                    return;
        }

        if ($foafDataPublic) {
                $this->doFullLoad($foafDataPublic);
        }
        if ($foafDataPrivate) {
                $this->doFullLoad($foafDataPrivate);
        }
    }

       
    private function doFullLoad(&$foafData) {
            /*TODO MISCHA dump test to check if empty */
            if ($foafData->getPrimaryTopic()) {
                $queryString = 
                    "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
                    PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
                    PREFIX bio: <http://purl.org/vocab/bio/0.1/>
                    PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
                    SELECT ?foafWeblog
                    WHERE{
                            <".$foafData->getPrimaryTopic()."> foaf:weblog ?foafWeblog                  
                    };";
    
                $results = $foafData->getModel()->SparqlQuery($queryString);

                $privacy;
                //decide whether we put it in the public or private bit
                if ($foafData->isPublic) {
                        $privacy = 'public';
                } else {
                        $privacy = 'private';
                }

                //Check if results are not empty
                if (!(empty($results))) {
                    /*mangle the results so that they can be easily rendered*/
                    foreach ($results as $row) {	
                        //TODO MISCHA ... checking if this is a literal foaf:weblog which starts with http :)
                        if (isset($row['?foafWeblog']->uri) && $this->isHomepageValid($row['?foafWeblog']->uri)) {
                            array_push($this->data[$privacy]['foafWeblogFields']['values'],$row['?foafWeblog']->uri);
                            /*Over here to put in the weblog title*/
                            $title = $this->getHomepageTitle($row['?foafWeblog']->uri);
                            if ($title) {
                                error_log("[foaf_editor] Homepage title returned");
                                $new_statement = new Statement(new Resource($row['?foafWeblog']->uri),new Resource("http://purl.org/dc/elements/1.1/title"),new Literal($title));
                                $foafData->getModel()->addWithoutDuplicates($new_statement);
                            } 
                        } else if (isset($row['?foafWeblog']->label) && $this->isHomepageValid($row['?foafWeblog']->label)) {
                            if (preg_match('/^https?:\/\//',$row['?foafWeblog']->label)) {
                                array_push($this->data[$privacy]['foafWeblogFields']['values'],$row['?foafWeblog']->label);
                                /*Over here to put in the homepage title*/
                                $title = $this->getHomepageTitle($row['?foafWeblog']->label);
                                if ($title) {
                                    error_log("[foaf_editor] weblog title returned");
                                    $new_statement = new Statement(new Resource($row['foafWeblog']->label),new Resource("http://purl.org/dc/elements/1.1/title"),new Literal($title));
                                    $foafData->getModel()->addWithoutDuplicates($new_statement);
                                } 
                                //TODO MISCHA ... mangle the name to put something in here!
                            }
                        }
                    }	
                } 
            }
        } //end doLoad thing

	
    /*saves the values created by the editor in value... as encoded in json. */
    public function saveToModel(&$foafData, $value) {

            require_once 'FieldNames.php';
            
            $predicate_resource = new Resource('http://xmlns.com/foaf/0.1/weblog');
            $primary_topic_resource = new Resource($foafData->getPrimaryTopic());
            
            //find existing triples
            $foundModel = $foafData->getModel()->find($primary_topic_resource,$predicate_resource,NULL);
            
            //remove existing triples
            foreach($foundModel->triples as $triple){
                    //echo("KKKKKKKKKKKKKKKKKKKKKKKK\n".var_dump($triple)."\n");
                    $foafData->getModel()->remove($triple);
            }
            
            //add new triples
            $valueArray = get_object_vars($value);

            foreach($valueArray['values'] as $thisValue){
                    if ($this->isHomepageValid($thisValue)) {
                        $new_statement = new Statement($primary_topic_resource,$predicate_resource,new Resource($thisValue));	
                        $foafData->getModel()->addWithoutDuplicates($new_statement);
                        /*Try and get the Hompage title from the HTML*/
                        $title = $this->getHomepageTitle($thisValue);
                        if ($title) {
                            error_log("[foaf_editor] Homepage title returned");
                            $new_statement = new Statement(new Resource($thisValue),new Resource("http://purl.org/dc/elements/1.1/title"),new Literal($title));
                            $foafData->getModel()->addWithoutDuplicates($new_statement);
                        } 
                        //TODO MISCHA ... mangle the name to put something in here!
                    } else {
                        error_log("[foaf_editor] Homepage not added");
                    }
            }
    }


    /* Check if the HomepageValid */
    private function isHomepageValid($value) {
        //if(preg_match('/^https?:\/\/(?:[a-z\-]+\.)+[a-z]{2,6}(?:\/[^\/#?]+)*/',$value))
        //if(preg_match('/^https?:\/\//',$value)) 
        if (preg_match('/^https?:\/\/(?:[a-z\-]+\.)+[a-z]{2,6}(?:\/[^\/#?]+)*/',$value)) {
		error_log('[foaf_editor] weblog is valid');
        	return true;
        } else {
		error_log('[foaf_editor] weblog is not-valid');
        	return false;
        }
    }

    /* This function needs to be hacked to work properly, needs to grab the html and need to try to extract*/
    function getHomepageTitle($url) {
            ini_set('default_socket_timeout', 2);  
            $input = @fopen($url, 'r');
            if ($input != false) { 
                $text = fread($input, 1024);
                fclose($input); 
                if (preg_match('/<title>([^<]*?)<\/title>/',$text,$matches)) {
                        error_log('[foaf_editor] Grabbed the users title');
                        return $matches[1];
                } 
            }
            return 0;
    }
}
/* vi:set expandtab sts=4 sw=4: */