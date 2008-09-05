/*loads all the foaf data from the given file (or the session if there is no uri) into the editor.*/
function loadFoaf(){

	//TODO use jquery event handler to deal with errors on this request
	url = document.getElementById('foafUri').value;
  	$.post("/ajax/load-foaf", { uri: url}, function(data){objectsToDisplay(data);}, "json");
}

/*populates form fields etc from javascript objects (from json) */ 
function objectsToDisplay(data){

	triples = data;
	
  	//TODO: add for loops to populate/create multiple fields here 
  	for(i = 0 ; i < data.length ; i++){
	  	if(data[i].name.label){
	  		if(i == 0){
				document.getElementById('name').value = data[i].name.label;
			} 
		}
		if(data[i].homepage.uri){
			if(i == 0){
				document.getElementById('homepage').value = data[i].homepage.uri;
			} 
		}
		if(data[i].nick.label){
			if(i == 0){
				document.getElementById('nick').value = data[i].nick.label;
			} 
		}
	}
}

/*populates the triples objects with stuff from the actual display (i.e. what the user has changed)*/
//TODO: needs to cope with added, deleted and multiple triples
//TODO: datatypes/languages

//XXX We can't simply send the json back like this to be automatically decoded unfortunately.
function displayToObjects(){
	
  	for(i = 0 ; i < triples.length ; i++){
	  	if(document.getElementById('name').value){
	  		if(i == 0){
				triples[i].name.label = document.getElementById('name').value;
			} 
		}
		if(document.getElementById('homepage').value){
			if(i == 0){
				 triples[i].homepage.uri = document.getElementById('homepage').value;
			} 
		}
		if(document.getElementById('nick').value){
			if(i == 0){
				 triples[i].nick.label = document.getElementById('nick').value;
			} 
		}
	}
	
}



/*saves all the foaf data TODO: it might be a challenge making this quick*/
function saveFoaf(){
	displayToObjects();
	jsonstring = JSON.serialize(triples);
	//TODO use jquery event handler to deal with errors on this request
  	$.post("/ajax/save-foaf", {model : jsonstring}, function(){}, "json");
  		
}
