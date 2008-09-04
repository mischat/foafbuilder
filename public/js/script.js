/*loads all the foaf data from the given file (or the session if there is no uri) into the editor.*/
function loadFoaf(){

	//use json data and use addElement etc. stuff rather than using innerHTML
	url = document.getElementById('foafUri').value;
  	$.post("/ajax/load-foaf", { uri: url}, function(data){populate(data);}, "json");
}

function populate(data){

	triples = data;
	
  	//TODO: add for loops to populate multiple fields here 
  	for(i = 0 ; i < data.length ; i++){
	  	if(data[i].name.label){
	  		if(i == 0){
				document.getElementById('name').value = data[i].name.label;
			} else {
				//document.getElementById('name_container');
			}
		}
		if(data[i].homepage.uri){
			if(i == 0){
				document.getElementById('homepage').value = data[i].homepage.uri;
			} else {
			
			}
		}
		if(data[i].nick.label){
			if(i == 0){
				document.getElementById('nick').value = data[i].nick.label;
			} else {
				
			}
		}
	}
}

/*saves all the foaf data TODO: it might be a challenge making this quick*/
function saveFoaf(){
	jsonstring = JSON.serialize(triples);
  	$.post("/ajax/save-foaf", {model : jsonstring.substring(1,jsonstring.length-1)}, function(data){populate(data);}, "json");
  		
}
