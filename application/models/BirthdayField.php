<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';
/*FIXME: perhaps fields shouldn't do the whole sparql query thing in the constructor.*/

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class BirthdayField extends Field {
	
    /*predicateUri is only appropriate for simple ones (one triple only)*/
    public function BirthdayField($foafData) {
        /*TODO MISCHA dump test to check if empty */
        if ($foafData->getPrimaryTopic()) {
            $queryString = 
                "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
                PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
                PREFIX bio: <http://purl.org/vocab/bio/0.1/>
                PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
                SELECT ?bioBirthday ?foafBirthday ?foafDateOfBirth
                WHERE{
                ?z foaf:primaryTopic <".$foafData->getPrimaryTopic().">
                ?z foaf:primaryTopic ?primaryTopic
                OPTIONAL{
                ?primaryTopic foaf:birthday ?foafBirthday .
                } .
                OPTIONAL{
                ?primaryTopic foaf:dateOfBirth ?foafDateOfBirth .
                }
                OPTIONAL{
                ?primaryTopic bio:event ?e .
                ?e rdf:type bio:Birth .
                ?e bio:date ?bioBirthday .
                } 
                };";

            $results = $foafData->getModel()->SparqlQuery($queryString);		

            $this->data['birthdayFields'] = array();
            $this->data['birthdayFields'] = array();
            
            /*mangle the results so that they can be easily rendered*/
            foreach ($results as $row) {	
                if (isset($row['?foafDateOfBirth']) && $this->isLongDateValid($row['?foafDateOfBirth'])) {
                	$birthdayArray = split("-",$row['?foafDateOfBirth']->label);
                    $this->data['birthdayFields']['day']= $birthdayArray[2];
                    $this->data['birthdayFields']['month']= $birthdayArray[1];
                    $this->data['birthdayFields']['year']= $birthdayArray[0];
                }
                if (isset($row['?foafBirthday']) && $this->isShortDateValid($row['?foafBirthday'])) {
                    $birthdayArray = split("-",$row['?foafBirthday']->label);
                    $this->data['birthdayFields']['day']= $birthdayArray[1];
                    $this->data['birthdayFields']['month']= $birthdayArray[0];
                }
                if (isset($row['?bioBirthday']) && $this->isLongDateValid($row['?bioBirthday'])) {
                    $birthdayArray = split("-",$row['?bioBirthday']->label);
                    $this->data['birthdayFields']['day']= $birthdayArray[2];
                    $this->data['birthdayFields']['month']= $birthdayArray[1];
                    $this->data['birthdayFields']['year']= $birthdayArray[0];
                }
            }	

            //TODO: perhaps it is better to keep all the display stuff in the javascript?
            $this->data['birthdayFields']['displayLabel'] = 'Birthday';
            $this->data['birthdayFields']['name'] = 'birthday';
            $this->name = 'birthday';
            $this->label = 'Birthday';
        } else {
            return 0;
        }
    }
	
    /*saves the values created by the editor in value... as encoded in json.  Returns an array of bnodeids and random strings to be replaced by the view.*/
    public function saveToModel(&$foafData, $value) {
        print "THis is the value $value\n";
        $valueArray = $this->objectToArray($value);

        /*find existing triples for foafBirthday and foafDateOfBirth*/
        $foundModel1 = $foafData->getModel()->find(NULL,new Resource("http://xmlns.com/foaf/0.1/birthday"),NULL);
        $foundModel2 = $foafData->getModel()->find(NULL,new Resource("http://xmlns.com/foaf/0.1/dateOfBirth"),NULL);

        /*remove any existing triples*/
        foreach($foundModel1->triples as $triple) {
            print "ANYTHING HERE !\n";
            $foafData->getModel()->remove($triple);
        }

        foreach($foundModel2->triples as $triple) {
            print "ANYTHING HERE !\n";
            $foafData->getModel()->remove($triple);
        }

        /*re-add them (if they exist)*/
        if ($valueArray['month'] && $valueArray['month'] != '' && $valueArray['day'] && $valueArray['day'] != '') {
            /*add FoafBirthday element*/
            $foafBirthdayResource = new Resource("http://xmlns.com/foaf/0.1/birthday");
            $newFoafBirthday = new Statement(new Resource($foafData->getPrimaryTopic()),$foafBirthdayResource,new Literal($valueArray['month']."-".$valueArray['day']));
            $foafData->getModel()->add($newFoafBirthday);

            if($valueArray['year'] && $valueArray['year'] != '') {
                /*add foafDateOfBirth element*/
                $dateLiteral = new Literal($valueArray['year']."-".$valueArray['month']."-".$valueArray['day']);
                $foafDateOfBirthResource = new Resource("http://xmlns.com/foaf/0.1/dateOfBirth");
                $newFoafDateOfBirth= new Statement(new Resource($foafData->getPrimaryTopic()),$foafDateOfBirthResource,$dateLiteral);
                $foafData->getModel()->add($newFoafDateOfBirth);
                echo("year: ".$valueArray['year']);
                /*if bio style birthday exists already then edit it but if not, don't*/
                $this->editBioBirthdayIfItExists($foafData,$valueArray['year'],$valueArray['month'],$valueArray['day']);
            }
        }
        $this->editBioBirthdayIfItExists($foafData,NULL,NULL,NULL);
    }

    private function isLongDateValid($date) {
        //FIXME: something should go here to make sure the string makes sense.
        if ($date == null || $date == '') {
            return false;
        } else {
            return true;
        }
    }

    private function isShortDateValid($date) {
    //FIXME: something should go here to make sure the string makes sense.
    if ($date == null || $date == '') {
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

    private function editBioBirthdayIfItExists(&$foafData,$year,$month,$day){

        $query = 'PREFIX foaf: <http://xmlns.com/foaf/0.1/>
            PREFIX bio: <http://purl.org/vocab/bio/0.1/>
            PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

            SELECT ?e ?bioBirthday WHERE{
            ?z foaf:primaryTopic ?primaryTopic
            ?primaryTopic bio:event ?e .
            ?e rdf:type bio:Birth .
            ?e bio:date ?bioBirthday .
            }';

        $results = $foafData->getModel()->sparqlQuery($query);
        if (isset($results[0]['?e']) && isset($results[0]['?bioBirthday'])) {
            //$eventBnode = new BlankNode($results[0]['?e']->uri);
            /*remove the existing triple*/
            $bioDateResource = new Resource("http://purl.org/vocab/bio/0.1/date");
            $existingStatement = new Statement($results[0]['?e'], $bioDateResource, $results[0]['?bioBirthday']);
            $foafData->getModel()->remove($existingStatement);

            /*create a new triple if the date has been passed in*/
            if($day && $month && $year) {
                $dateLiteral = new Literal($year."-".$month."-".$day);
                $newStatement = new Statement($results[0]['?e'], new Resource("http://purl.org/vocab/bio/0.1/birthday"), $dateLiteral);
                $foafData->getModel()->add($newStatement);
            }
        }
    }
}
/* vi:set expandtab sts=4 sw=4: */
