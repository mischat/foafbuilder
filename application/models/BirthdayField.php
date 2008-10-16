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
                    ?z foaf:primaryTopic <".$foafData->getPrimaryTopic()."> .
                    ?z foaf:primaryTopic ?primaryTopic .
                    OPTIONAL{
                        ?primaryTopic foaf:birthday ?foafBirthday
                    } .
                    OPTIONAL{
                        ?primaryTopic foaf:dateOfBirth ?foafDateOfBirth
                    } .
                    OPTIONAL{
                        ?primaryTopic bio:event ?e .
                        ?e rdf:type bio:Birth .
                        ?e bio:date ?bioBirthday .
                    } 
                };";
            $results = $foafData->getModel()->SparqlQuery($queryString);		
    
            $this->data['birthdayFields'] = array();
            $this->data['birthdayFields'] = array();

              /*Check results !empty */
              if (!(empty($results))) {
                /*mangle the results so that they can be easily rendered*/

                foreach ($results as $row) {	
                error_log("[foaf_editor] Checking a birthday");
                    //if (isset($row['?foafDateOfBirth']) && $this->isLongDateValid($row['?foafDateOfBirth'])) {
                    //if (isset($row['?foafDateOfBirth']->label) && $this->isLongDateValid($row['?foafDateOfBirth']->label)) {
                    if (isset($row['?foafDateOfBirth']->label) && $this->isLongDateValid($row['?foafDateOfBirth']->label)) {
                    //if ($this->isLongDateValid($row['?foafDateOfBirth']->label)) {
            error_log("***********************************");
                        $birthdayArray = split("-",$row['?foafDateOfBirth']->label);
                        if (empty($birthdayArray)) {
                            $birthdayArray = split("/",$row['?foafDateOfBirth']->label);
                        } 
                        if (empty($birthdayArray)) {
                            $birthdayArray = split(":",$row['?foafDateOfBirth']->label);
                        }
                        /*If the birthdayArray is nice and easy to parse!*/
                        if (count($birthdayArray == 3)) {
                            $this->data['birthdayFields']['day']= $birthdayArray[2];
                            $this->data['birthdayFields']['month']= $birthdayArray[1];
                            $this->data['birthdayFields']['year']= $birthdayArray[0];
                        } 
                    } else if (isset($row['?foafBirthday']->label) && $this->isShortDateValid($row['?foafBirthday']->label)) {
            error_log("UUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUU");
                        $birthdayArray = split("-",$row['?foafBirthday']->label);
                        if (empty($birthdayArray)) {
                            $birthdayArray = split("/",$row['?foafDateOfBirth']->label);
                        } 
                        if (empty($birthdayArray)) {
                            $birthdayArray = split(":",$row['?foafDateOfBirth']->label);
                        }
                        if (strlen($birthdayArray[1]) == 1) {
                            $this->data['birthdayFields']['day']= "0".$birthdayArray[1];
                        } else {
                            $this->data['birthdayFields']['day']= $birthdayArray[1];
                        }
                        if (strlen($birthdayArray[0]) == 1) {
                            $this->data['birthdayFields']['month']= "0".$birthdayArray[0];
                        } else {
                            $this->data['birthdayFields']['month']= $birthdayArray[0];
                        }
                    } else if (isset($row['?bioBirthday']->label) && $this->isLongDateValid($row['?bioBirthday']->label)) {
            error_log("XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");
                        $birthdayArray = split("-",$row['?bioBirthday']->label);
                        $this->data['birthdayFields']['day']= $birthdayArray[2];
                        $this->data['birthdayFields']['month']= $birthdayArray[1];
                        $this->data['birthdayFields']['year']= $birthdayArray[0];
                    }
                }	
            //Perhaps this should be one level lower 
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
        $valueArray = $this->objectToArray($value);
        /*Test to see if we have any dateOfBirth info*/
        if (isset($valueArray['month']) || isset($valueArray['day']) || isset($valueArray['year'])) {
            /*find existing triples for foafBirthday and foafDateOfBirth*/
            $foundModel1 = $foafData->getModel()->find(NULL,new Resource("http://xmlns.com/foaf/0.1/birthday"),NULL);
            $foundModel2 = $foafData->getModel()->find(NULL,new Resource("http://xmlns.com/foaf/0.1/dateOfBirth"),NULL);

            var_dump($foundModel1);
            if (!$foundModel1->isEmpty()) {
                error_log('[foaf_editor] So here we have foaf:birthday');
error_log('OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO');
                foreach($foundModel1->triples as $triple) {
                    $foafData->getModel()->remove($triple);
                }
                /*So if foaf:birth existed and there is no value for year then add the birthday triples*/
                //if (!$valueArray['year'] || $valueArray['year'] == '') {
                error_log('[foaf_editor] Added foaf:birthday triple');
                $foafBirthdayResource = new Resource("http://xmlns.com/foaf/0.1/birthday");
                if (!isset($valueArray['month'])) {
                    $month = "00"; 
                } else if (strlen($valueArray['month']) == 1) {
                    $month = "0".$valueArray['month'];
                } else {
                    $month = $valueArray['month'];
                }
                if (!isset($valueArray['day'])) {
                    $day = "00"; 
                } else if (strlen($valueArray['day']) == 1) {
                    $day = "0".$valueArray['day'];
                } else {
                    $day = $valueArray['day'];
                }
                //$newFoafBirthday = new Statement(new Resource($foafData->getPrimaryTopic()),$foafBirthdayResource,new Literal($valueArray['month']."-".$valueArray['day']));
                $newFoafBirthday = new Statement(new Resource($foafData->getPrimaryTopic()),$foafBirthdayResource,new Literal($month."-".$day));
                $foafData->getModel()->add($newFoafBirthday);
               // }
            } 
            if (!$foundModel2->isEmpty()) {
                error_log('[foaf_editor] So here we have foaf:dateOfBirth');
                foreach($foundModel2->triples as $triple) {
                    $foafData->getModel()->remove($triple);
                }
                if (isset($valueArray['month']) && $valueArray['month'] != '' && isset($valueArray['day']) && $valueArray['day'] != '' && isset($valueArray['year']) && $valueArray['year'] != '') {
                    $dateLiteral = new Literal($valueArray['year']."-".$valueArray['month']."-".$valueArray['day']);
                    $foafDateOfBirthResource = new Resource("http://xmlns.com/foaf/0.1/dateOfBirth");
                    $newFoafDateOfBirth= new Statement(new Resource($foafData->getPrimaryTopic()),$foafDateOfBirthResource,$dateLiteral);
                    $foafData->getModel()->add($newFoafDateOfBirth);
                }
            } 
            // < 3 fields 
            /*Adds in the correct triples without duplicates*/
            var_dump($valueArray);
            if (isset($valueArray['month']) && $valueArray['month'] != '' && isset($valueArray['day']) && $valueArray['day'] != '' && !isset($valueArray['year'])) {
                    /*foaf:birthday*/
                    error_log('[foaf_editor] Added foaf:birthday triple');
                    $foafBirthdayResource = new Resource("http://xmlns.com/foaf/0.1/birthday");
                    $newFoafBirthday = new Statement(new Resource($foafData->getPrimaryTopic()),$foafBirthdayResource,new Literal($valueArray['month']."-".$valueArray['day']));
                    $foafData->getModel()->addWithoutDuplicates($newFoafBirthday);
            //} else if (isset($valueArray['month']) && $valueArray['month'] != '' && $valueArray['day'] == '' && $valueArray['year'] == '') {
            } else if (isset($valueArray['month']) && $valueArray['month'] != '' && !isset($valueArray['day'])  && !isset($valueArray['year'])) {
                    error_log('[foaf_editor] Added foaf:birthday triple');
                    $foafBirthdayResource = new Resource("http://xmlns.com/foaf/0.1/birthday");
                    $newFoafBirthday = new Statement(new Resource($foafData->getPrimaryTopic()),$foafBirthdayResource,new Literal($valueArray['month']."-00"));
                    $foafData->getModel()->addWithoutDuplicates($newFoafBirthday);
            //} else if ($valueArray['day'] && $valueArray['day'] != '' && $valueArray['month'] == '' && $valueArray['year'] == '') {
            } else if (isset($valueArray['day']) && $valueArray['day'] != '' && !isset($valueArray['month']) && !isset($valueArray['year'])) {
                    error_log('[foaf_editor] Added foaf:birthday triple');
                    $foafBirthdayResource = new Resource("http://xmlns.com/foaf/0.1/birthday");
                    $newFoafBirthday = new Statement(new Resource($foafData->getPrimaryTopic()),$foafBirthdayResource,new Literal("00-".$valueArray['month']));
                    $foafData->getModel()->addWithoutDuplicates($newFoafBirthday);
            } 
            $this->editBioBirthdayIfItExists($foafData,$valueArray['year'],$valueArray['month'],$valueArray['day']);

            /*re-add them (if they exist)
            if ($valueArray['month'] && $valueArray['month'] != '' && $valueArray['day'] && $valueArray['day'] != '') {
                /*add FoafBirthday element
                $foafBirthdayResource = new Resource("http://xmlns.com/foaf/0.1/birthday");
                $newFoafBirthday = new Statement(new Resource($foafData->getPrimaryTopic()),$foafBirthdayResource,new Literal($valueArray['month']."-".$valueArray['day']));
                $foafData->getModel()->add($newFoafBirthday);

                if($valueArray['year'] && $valueArray['year'] != '') {
                    /*add foafDateOfBirth
                    $dateLiteral = new Literal($valueArray['year']."-".$valueArray['month']."-".$valueArray['day']);
                    $foafDateOfBirthResource = new Resource("http://xmlns.com/foaf/0.1/dateOfBirth");
                    $newFoafDateOfBirth= new Statement(new Resource($foafData->getPrimaryTopic()),$foafDateOfBirthResource,$dateLiteral);
                    $foafData->getModel()->add($newFoafDateOfBirth);
                    /*if bio style birthday exists already then edit it but if not, don't

                }
            }
            $this->editBioBirthdayIfItExists($foafData,NULL,NULL,NULL);
            */
        } else {
            error_log('[foaf_editor] There is no birthday to process');
        }
    }

    private function isLongDateValid($date) {
        //TODO MISCHA make this parse date into a format we understand
        //if (!($date == null) && !($date == '')) {
error_log("SOMETHIGNTHOGKGHDKDKHGKHDHG");
        if (preg_match('/(\d{4}?)[-|:|\/](\d{2}?)[-|:|\/](\d{2}?)/',$date,$matches)) {
            if (((int) $matches[2] <= 12) && ((int) $matches[2] > 0)) {
                if (((int) $matches[3] <= 31) && ((int) $matches[3] > 0)) {
                    error_log("[foaf_editor] long date valid");
                    return true;
                }  
            }
        }
        error_log("[foaf_editor] long date invalid");
        return false;
    }

    private function isShortDateValid($date) {
        //TODO MISCHA Could do with checking if the date entered is valid i.e. if Feb then only 29 days! 
        //if ($date == null || $date == '') {
        if (preg_match('/(\d{2}?)[-|:|\/](\d{2}?)/',$date,$matches)) {
            if (((int) $matches[1] <= 12) && ((int) $matches[1] > 0)) {
                if (((int) $matches[2] <= 31) && ((int) $matches[2] > 0)) {
                    error_log("[foaf_editor] short date valid");
                    return true;
                }  
            }
        }
        error_log("[foaf_editor] short date invalid");
        return false;
    }

    private function objectToArray($value) {
        $ret = array();
        foreach($value as $key => $value) {
            $ret[$key] = $value;
        }
        return $ret;
    }

    private function editBioBirthdayIfItExists(&$foafData,$year,$month,$day) {
        /*TODO MISCHA optimise*/
        $query = 
            "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
            PREFIX bio: <http://purl.org/vocab/bio/0.1/>
            PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

            SELECT ?e ?bioBirthday 
            WHERE{
                <".$foafData->getPrimaryTopic()."> bio:event ?e .
                ?e rdf:type bio:Birth .
                ?e bio:date ?bioBirthday .
            }";

        $results = $foafData->getModel()->sparqlQuery($query);
        /*If it does exist*/
        if (isset($results[0]['?e']) && isset($results[0]['?bioBirthday'])) { 
            //$eventBnode = new BlankNode($results[0]['?e']->uri);
            error_log("THIS SHOULD HAPPEN !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");
            /*remove the existing triple*/
            $bioDateResource = new Resource("http://purl.org/vocab/bio/0.1/date");
            $existingStatement = new Statement($results[0]['?e'], $bioDateResource, $results[0]['?bioBirthday']);
            $foafData->getModel()->remove($existingStatement);

            /*create a new triple if the date has been passed in*/
            if($day && $month && $year) {
                $dateLiteral = new Literal($year."-".$month."-".$day);
                //$newStatement = new Statement($results[0]['?e'], new Resource("http://purl.org/vocab/bio/0.1/birthday"), $dateLiteral);
                $newStatement = new Statement($results[0]['?e'], new Resource("http://purl.org/vocab/bio/0.1/date"), $dateLiteral);
                $foafData->getModel()->add($newStatement);
            }
        } else {
            /*So here I am setting a new blanknode*/
            if($day && $month && $year) {
                error_log("[foaf_editor] Here is where we have to set a correct 3 pronged date");
                $dateLiteral = new Literal($year."-".$month."-".$day);
                $new_bnode = new BlankNode($foafData->getModel());
                $add_statement = new Statement(new Resource($foafData->getPrimaryTopic()), new Resource("http://purl.org/vocab/bio/0.1/event"),$new_bnode);
                $foafData->getModel()->add($add_statement);
                $add_statement = new Statement($new_bnode, new Resource("http://purl.org/vocab/bio/0.1/date"),$dateLiteral);
                $foafData->getModel()->add($add_statement);
                $add_statement = new Statement($new_bnode, new Resource("http://www.w3.org/1999/02/22-rdf-syntax-ns#type"), new Resource("http://purl.org/vocab/bio/0.1/Birth"));
                $foafData->getModel()->add($add_statement);
            }
        }
    }
}
/* vi:set expandtab sts=4 sw=4: */
