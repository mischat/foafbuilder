<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';
/*FIXME: perhaps fields shouldn't do the whole sparql query thing in the constructor.*/

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class ImgField extends Field {
	
    /*predicateUri is only appropriate for simple ones (one triple only)*/
    public function ImgField($foafData) {
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
		            
	                ?primaryTopic foaf:img ?foafImg .
	                	
		            OPTIONAL{
		            	?foafImg dc:title ?dcTitle .
		            }
		            OPTIONAL{
		                ?foafImg dc:description ?dcDescription .
		            }	
                };";

            $results = $foafData->getModel()->SparqlQuery($queryString);		

            $this->data['foafImgFields'] = array();
            $this->data['foafImgFields']['images'] = array();
            
            /*mangle the results so that they can be easily rendered*/
            foreach ($results as $row) {	
                if (isset($row['?foafImg']) && $this->isImageUrlValid($row['?foafImg'])) {
                	$thisImage = array();
                	$thisImage['uri'] = $row['?foafImg']->uri;
                	array_push($this->data['foafImgFields']['images'],$thisImage);
                }
            }	
                   
            //TODO: perhaps it is better to keep all the display stuff in the javascript?
            $this->data['foafImgFields']['displayLabel'] = 'Secondary Images';
            $this->data['foafImgFields']['name'] = 'foafImg';
            $this->name = 'foafImg';
            $this->label = 'Secondary Images';
        } else {
            return 0;
        }
    }
	
    /*saves the values created by the editor in value... as encoded in json.  Returns an array of bnodeids and random strings to be replaced by the view.*/
    public function saveToModel(&$foafData, $value) {
		echo("SAVING SECONDARY IMAGES");
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
/* vi:set expandtab sts=4 sw=4: */
