<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';
/*FIXME: perhaps fields shouldn't do the whole sparql query thing in the constructor.*/

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class DepictionField extends Field {
	
    /*predicateUri is only appropriate for simple ones (one triple only)*/
    public function DepictionField($foafData) {
        /*TODO MISCHA dump test to check if empty */
        if ($foafData->getPrimaryTopic()) {
            $queryString = 
                "PREFIX dc: <http://purl.org/dc/elements/1.1/>
                PREFIX foaf: <http://xmlns.com/foaf/0.1/>
                PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
                PREFIX bio: <http://purl.org/vocab/bio/0.1/>
                PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
                SELECT ?foafImg ?foafDepiction ?dcTitle ?dcDescription
                WHERE{
	                ?z foaf:primaryTopic <".$foafData->getPrimaryTopic().">
	                ?z foaf:primaryTopic ?primaryTopic
		              
		            ?primaryTopic foaf:depiction ?foafDepiction .
		     
			       	OPTIONAL{
			            	?foafDepiction dc:title ?dcTitle .
			        }
			        OPTIONAL{
			               	?foafDepiction dc:description ?dcDescription .
			        }	
                };";

            $results = $foafData->getModel()->SparqlQuery($queryString);		

            $this->data['depictionFields'] = array();
            $this->data['depictionFields']['images'] = array();
            
            /*mangle the results so that they can be easily rendered*/
            foreach ($results as $row) {	
             	if (isset($row['?foafDepiction']) && $this->isImageUrlValid($row['?foafDepiction'])) {
                	$thisImage = array();
                	$thisImage['uri'] = $row['?foafDepiction']->uri;
                	
                	if(isset($row['?dcTitle']) && $row['?dcTitle'] && $row['?dcTitle']->label){
                		$thisImage['title'] = $row['?dcTitle']->label;
                	} 
                	if(isset($row['?dcDescription']) && $row['?dcDescription'] && $row['?dcDescription']->label){
                		$thisImage['description'] = $row['?dcDescription']->label;
                	}
                	array_push($this->data['depictionFields']['images'],$thisImage);
                }
            }	
                   
            //TODO: perhaps it is better to keep all the display stuff in the javascript?
            $this->data['depictionFields']['displayLabel'] = 'Images';
            $this->data['depictionFields']['name'] = 'foafDepiction';
            $this->name = 'birthday';
            $this->label = 'Birthday';
        } else {
            return 0;
        }
    }
	
    /*saves the values created by the editor in value... as encoded in json.  Returns an array of bnodeids and random strings to be replaced by the view.*/
    public function saveToModel(&$foafData, $value) {
		echo("SAVING DEPICTION IMAGES");
    }

    private function isImageUrlValid($url) {
        //FIXME: something should go here to make sure the string makes sense.
        if ($url == null || $url == '') {
            return false;
        } else {
            return true;
        }
    }

    private function objectToArray($value) {
        $ret = array();
        foreach($value as $key => $value) {
            $ret[$key] = $value;
        }
        return $ret;
    }
}
