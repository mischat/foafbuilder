<?
require_once 'FoafData.php';

class Utils{
	
	/*RAP doesn't seem to be very good at generating unique bnode ids so here's some jiggery pokery to do just that*/
	static function GenerateUniqueBnode($model){
		
		$bNodePrefix = 'bNode';
		
		return new BlankNode($bNodePrefix.'_'.uniqid());
	}
	
	static function bNodeIsFound($i,$model,$bNodePrefix){
		$foundModel1 = $model->find(new BlankNode($bNodePrefix.$i), NULL, NULL);
//		$foundModel2 = $model->find(NULL, new BlankNode($bNodePrefix.$i), NULL);
		$foundModel3 = $model->find(NULL, NULL, new BlankNode($bNodePrefix.$i));		
		
		if(isset($foundModel1->triples[0]) 
			//|| isset($foundModel2->triples[0]) 
			|| isset($foundModel3->triples[0])){
			
			return true;
		} else{
			return false;
		}
	}
	
   /* This function needs to be hacked to work properly, needs to grab the html and need to try to extract*/
   function getFlickrNsid($username) {
           $url = "http://www.flickr.com/photos/$username";
           //ini_set('default_socket_timeout', 2);
           $input = @fopen($url, 'r');
           $text = "";
           while ( ($buf=fread( $input, 8192 )) != '' ) {
               $text .= $buf;
           }
           if($buf===FALSE) {
               error_log("THERE WAS AN ERROR READING\n");
           }            
           if (preg_match('/<link rel="alternate"\s*type="application\/atom\+xml"\s*title="[^"]+"\s*href="http:\/\/api\.flickr\.com\/services\/feeds\/photos_public\.gne\?id=(\d{8,}\@\w{3,}?)/',$text,$matches)) {
                   return $matches[1];
           }
          // ini_set('default_socket_timeout', 20);
           
           return 0;
   }


}

?>
