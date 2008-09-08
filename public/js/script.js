/*loads all the foaf data from the given file (or the session if there is no uri) into the editor.*/
function loadFoaf(){

	//TODO use jquery event handler to deal with errors on this request
	url = document.getElementById('foafUri').value;
  	$.post("/ajax/load-foaf", { uri: url}, function(data){objectsToDisplay(data);}, "json");
}

/*populates form fields etc from javascript objects (from json) */ 
function objectsToDisplay(data){

	triples = data;
	
  	//TODO: add stuff to create elements if they don't already exist here.
  	foafNameCount = 0;
  	foafHomepageCount = 0;
  	foafNickCount = 0;
  	
  	for(i = 0 ; i < data.length ; i++){
	  	if(data[i].foafName.label){
	  		foafNameElement = document.getElementById('foafName_'+foafNameCount);
	  		
	  		if(foafNameElement){ 
				foafNameElement.value = data[i].foafName.label;
			} else {
				//put create element code here
			}
			foafNameCount++; 
		}
		if(data[i].foafHomepage.uri){
			foafHomepageElement = document.getElementById('foafHomepage_'+foafHomepageCount);
			if(foafHomepageElement){
				foafHomepageElement.value = data[i].foafHomepage.uri;
			} else {
				//put create element code here
			}
			foafHomepageCount++;
		}
		if(data[i].foafNick.label){
			foafNickElement = document.getElementById('foafNick_'+foafNickCount);
			if(foafNickElement){
				foafNickElement.value = data[i].foafNick.label;
			} else {
				//put create element code here
			}
			foafNickCount++; 
		}
	}
}

/*populates the triples objects with stuff from the actual display (i.e. what the user has changed)*/
//TODO: needs to cope with added, deleted and multiple triples
//TODO: datatypes/languages

function displayToObjects(){

  	foafNameCount = 0;
  	foafHomepageCount = 0;
  	foafNickCount = 0;
	
  	for(i = 0 ; i < triples.length ; i++){
  		alert('foafName_'.foafNameCount);
  		foafNameElement = document.getElementById('foafName_'+foafNameCount);
	  	if(foafNameElement){
			triples[i].foafName.label = foafNameElement.value;
			alert(foafNameElement.value);
			foafNameCount++;
		}
		
		foafHomepageElement = document.getElementById('foafHomepage_'+foafHomepageCount);
		if(foafHomepageElement){
			triples[i].foafHomepage.uri = foafHomepageElement.value;
			foafHomepageCount++;	 
		}
		
		foafNickElement = document.getElementById('foafNick_'+foafNickCount);
		if(foafNickElement){
			triples[i].foafNick.label = foafNickElement.value;
			foafNickCount++;
		}
	}
	
}

//TODO THis is well dirty
function clearObjects(){
	
	if(document.getElementById('foafName').value){
		document.getElementById('foafName').value = null;
	}
	if(document.getElementById('foafHomepage').value){
		document.getElementById('foafHomepage').value = null;
	} 
	if(document.getElementById('foafNick').value){
		document.getElementById('foafNick').value = null;
	}
}


/*saves all the foaf data TODO: it might be a challenge making this quick*/
function saveFoaf(){
	displayToObjects();
	jsonstring = JSON.serialize(triples);
	//TODO use jquery event handler to deal with errors on this request
  	$.post("/ajax/save-foaf", {model : jsonstring}, function(){}, "json");
  		
}

/*Writes FOAF to screen*/
function writeFoaf() {
        //$.post("/writer/write-Foaf", { }, function(data){alert(data.name);console.log(data.time);},"json");
        $.post("/writer/write-Foaf", { }, function(){},null);
}

/*Clears FOAF model from session*/
function clearFoaf() {
	clearObjects();
        //$.post("/ajax/clear-Foaf", { }, function(data){alert(data.name);console.log(data.time);},"json");
        $.post("/ajax/clear-Foaf", { }, function(){},null);
}
