<?
require_once 'FoafData.php';

class Utils{
	
	/*RAP doesn't seem to be very good at generating unique bnode ids so here's some jiggery pokery to do just that*/
	static function GenerateUniqueBnode($model){
		
		$bNodePrefix = 'bNode';
		$i=1;

		for(; Utils::bNodeIsFound($i,$model,$bNodePrefix); $i++){
		}	
		
		return new BlankNode($bNodePrefix.$i);
	}
	
	static function bNodeIsFound($i,$model,$bNodePrefix){
		$foundModel1 = $model->find(new BlankNode($bNodePrefix.$i), NULL, NULL);
		$foundModel2 = $model->find(NULL, new BlankNode($bNodePrefix.$i), NULL);
		$foundModel3 = $model->find(NULL, NULL, new BlankNode($bNodePrefix.$i));		
		
		if(isset($foundModel1->triples[0]) 
			|| isset($foundModel2->triples[0]) 
			|| isset($foundModel3->triples[0])){
			
			return true;
		} else{
			return false;
		}
	}
}

?>