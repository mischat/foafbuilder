<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';
/*FIXME: perhaps fields shouldn't do the whole sparql query thing in the constructor.*/

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class BirthdayField extends Field {
	
    /*predicateUri is only appropriate for simple ones (one triple only)*/
    public function BirthdayField($foafDataPublic,$foafDataPrivate,$fullInstantiation = true) {
 	    
        $this->name = 'foafBirthday';
        $this->label = 'Birthday';
/*
    	$this->data['birthdayFields'] = array();  	
    	$this->data['birthdayFields']['displayLabel'] = 'Birthday';
        $this->data['birthdayFields']['name'] = 'birthday';
*/
        $this->data['public']['foafBirthdayFields'] = array();
        $this->data['public']['foafBirthdayFields']['values'] = array();
        $this->data['public']['foafBirthdayFields']['displayLabel'] = $this->label;
        $this->data['public']['foafBirthdayFields']['name'] = $this->name;

        $this->data['private']['foafBirthdayFields'] = array();
        $this->data['private']['foafBirthdayFields']['values'] = array();
        $this->data['private']['foafBirthdayFields']['displayLabel'] = $this->label;
        $this->data['private']['foafBirthdayFields']['name'] = $this->name;

        /*don't sparql query the model etc if a full instantiation is not required*/
        if (!$fullInstantiation) {
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

            $privacy;
            //decide whether we put it in the public or private bit
            if($foafData->isPublic) {
                    $privacy = 'public';
            } else {
                    $privacy = 'private';
            }

            /*Check results !empty */
            if (!(empty($results))) {
            /*mangle the results so that they can be easily rendered*/
                foreach ($results as $row) {	
                    error_log("[foaf_editor] For a birthday checking type...");
                    if (isset($row['?foafDateOfBirth']->label) && $this->isLongDateValid($row['?foafDateOfBirth']->label)) {
                        error_log("[foaf_editor] found complete dateOfBirth");
                        /* spliting with 3 different values : / - */
                        $birthdayArray = split("-",$row['?foafDateOfBirth']->label);
                        if (empty($birthdayArray)) {
                            $birthdayArray = split("/",$row['?foafDateOfBirth']->label);
                        } 
                        if (empty($birthdayArray)) {
                            $birthdayArray = split(":",$row['?foafDateOfBirth']->label);
                        }
                        /*If the birthdayArray is nice and easy to parse!*/
                        if (count($birthdayArray == 3)) {
                            $this->data[$privacy]['foafBirthdayFields']['day']= $birthdayArray[2];
                            $this->data[$privacy]['foafBirthdayFields']['month']= $birthdayArray[1];
                            $this->data[$privacy]['foafBirthdayFields']['year']= $birthdayArray[0];
                        } else {
                            error_log("[foaf_editor] couldn't parse date");
                        } 
                    } else if (isset($row['?foafBirthday']->label) && $this->isShortDateValid($row['?foafBirthday']->label)) {
                        error_log("['foaf_editor] found short date");
                        $birthdayArray = split("-",$row['?foafBirthday']->label);
                        /* spliting with 3 different values : / - */
                        if (empty($birthdayArray)) {
                            $birthdayArray = split("/",$row['?foafDateOfBirth']->label);
                        } 
                        if (empty($birthdayArray)) {
                            $birthdayArray = split(":",$row['?foafDateOfBirth']->label);
                        }
                        /*normalise to \d{2}*/
                        if (strlen($birthdayArray[1]) == 1) {
                            $this->data[$privacy]['foafBirthdayFields']['day']= "0".$birthdayArray[1];
                        } else {
                            $this->data[$privacy]['foafBirthdayFields']['day']= $birthdayArray[1];
                        }
                        if (strlen($birthdayArray[0]) == 1) {
                            $this->data[$privacy]['foafBirthdayFields']['month']= "0".$birthdayArray[0];
                        } else {
                            $this->data[$privacy]['foafBirthdayFields']['month']= $birthdayArray[0];
                        }
                    } else if (isset($row['?bioBirthday']->label) && $this->isLongDateValid($row['?bioBirthday']->label)) {
                        /* This one is actually well specified */
                        $birthdayArray = split("-",$row['?bioBirthday']->label);
                        $this->data[$privacy]['foafBirthdayFields']['day']= $birthdayArray[2];
                        $this->data[$privacy]['foafBirthdayFields']['month']= $birthdayArray[1];
                        $this->data[$privacy]['foafBirthdayFields']['year']= $birthdayArray[0];
                    }
                }	
            }
        }    
    } 
	
    /*saves the values created by the editor in value... as encoded in json.  Returns an array of bnodeids and random strings to be replaced by the view.*/
    public function saveToModel(&$foafData, $value) {
        $valueArray = $this->objectToArray($value);

        //First to make sure that at least one has been selected*/
        if (isset($valueArray['month']) || isset($valueArray['day']) || isset($valueArray['year'])) {
            /*find existing triples for foafBirthday and foafDateOfBirth*/
            $foundModel1 = $foafData->getModel()->find(NULL,new Resource("http://xmlns.com/foaf/0.1/birthday"),NULL);
            /*If foaf:birthday remove it */
            if (!$foundModel1->isEmpty()) {
                error_log('[foaf_editor] So here we have foaf:birthday');
                foreach($foundModel1->triples as $triple) {
                    $foafData->getModel()->remove($triple);
                }
            } 
            /*If foaf:dateOfBirth remove it*/
            $foundModel2 = $foafData->getModel()->find(NULL,new Resource("http://xmlns.com/foaf/0.1/dateOfBirth"),NULL);
            if (!$foundModel2->isEmpty()) {
                error_log('[foaf_editor] So here we have foaf:dateOfBirth');
                foreach($foundModel2->triples as $triple) {
                    $foafData->getModel()->remove($triple);
                }
            } 
            /* Now to check for bio: and if exists replace */
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
                $bioDateResource = new Resource("http://purl.org/vocab/bio/0.1/date");
                $bioEventResource = new Resource("http://purl.org/vocab/bio/0.1/event");
                $bioBirthResource = new Resource("http://purl.org/vocab/bio/0.1/Birth");
                $rdfTypeResource = new Resource("http://www.w3.org/1999/02/22-rdf-syntax-ns#type");

                $existingStatement0 = new Statement($results[0]['?e'], $bioDateResource, $results[0]['?bioBirthday']);
                $existingStatement1 = new Statement(new Resource($foafData->getPrimaryTopic()), $bioEventResource, $results[0]['?e']);
                $existingStatement2 = new Statement($results[0]['?e'], $rdfTypeResource, $bioBirthResource);

                $foafData->getModel()->remove($existingStatement0);
                $foafData->getModel()->remove($existingStatement1);
                $foafData->getModel()->remove($existingStatement2);
        
                /*There is no need to create these triples here*/
            }

            /*Now to write out triples after cleaning them*/
            /*If NO year presented then fit the most appropriate foaf:birthday*/
            if ($valueArray['year'] == '' && ($valueArray['month'] != '' || $valueArray['day'] != '')) {
                 /*Now we use the foaf:birthday*/
                $foafBirthdayResource = new Resource("http://xmlns.com/foaf/0.1/birthday");
                if (strlen($valueArray['month']) == 1) {
                    $month = "0".$valueArray['month'];
                } else if (strlen($valueArray['month']) == 0) {
                    $month = "00";
                } else {
                    $month = $valueArray['month'];
                }
                if (strlen($valueArray['day']) == 1) {
                    $day = "0".$valueArray['day'];
                } else if (strlen($valueArray['day']) == 0) {
                    $month = "00";
                } else {
                    $day = $valueArray['day'];
                }

                $newFoafBirthday = new Statement(new Resource($foafData->getPrimaryTopic()),$foafBirthdayResource,new Literal($month."-".$day));
                $foafData->getModel()->addWithoutDuplicates($newFoafBirthday);
            /* Catch all for writing out the bio:event */
            } elseif ($valueArray['year'] != '') {
                /*At this point we have at least a year, which is enough for writing the bio:event way*/
                error_log("[foaf_editor] Here we are writing out a bio:event birth");
           
                //Build up the literal value for bio:date 
                $dateString = $valueArray['year'];
                if ($valueArray['month'] != '') {
                    $dateString .= "-";
                    if (strlen($valueArray['month']) == 1) {
                        $dateString .= "0";
                    }
                    $dateString .= $valueArray['month'];
                    if ($valueArray['day'] != '') {
                        $dateString .= "-";
                        if (strlen($valueArray['month']) == 1) {
                            $dateString .= "0";
                        }
                        $dateString .= $valueArray['day'];
                    }
                
                }

                $dateLiteral = new Literal($dateString);
                $new_bnode = new BlankNode($foafData->getModel());
                $add_statement = new Statement(new Resource($foafData->getPrimaryTopic()), new Resource("http://purl.org/vocab/bio/0.1/event"),$new_bnode);
                $foafData->getModel()->add($add_statement);
                $add_statement = new Statement($new_bnode, new Resource("http://purl.org/vocab/bio/0.1/date"),$dateLiteral);
                $foafData->getModel()->add($add_statement);
                $add_statement = new Statement($new_bnode, new Resource("http://www.w3.org/1999/02/22-rdf-syntax-ns#type"), new Resource("http://purl.org/vocab/bio/0.1/Birth"));
                $foafData->getModel()->add($add_statement);
            } else {
                error_log('[foaf_editor] The user selected a useless combination of date information');
            }        
        } else {
            error_log('[foaf_editor] There is no birthday to process');
        }
    }

    /*try and parse a short date for Event:Bio or dateOfBirth*/
    private function isLongDateValid($date) {
        if (preg_match('/(\d{4}?)[-|:|\/](\d{1,2}?)[-|:|\/](\d{1,2}?)$/',$date,$matches)) {
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

    /*try and parse a short date for foaf:birthday */
    private function isShortDateValid($date) {
        if (preg_match('/(\d{1,2}?)[-|:|\/](\d{1,2}?)$/',$date,$matches)) {
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

}
/* vi:set expandtab sts=4 sw=4: */
