/*global variables containing all the data on the page*/
var foafNameValueArray;
var foafHomepageValueArray;
var foafNickValueArray;
var foafPrimaryTopic;

/*object to hold global variables in */
//TODO: this object is a bit 'inside out'
function PageDataObject(){
	this.foafPrimaryTopic = foafPrimaryTopic;
	this.foafNameValueArray = foafNameValueArray;
	this.foafHomepageValueArray = foafHomepageValueArray;
	this.foafNickValueArray = foafNickValueArray;
}


/*for uniquing an array*/
Array.prototype.dedup = function () {
  var newArray = new Array ();
  var seen = new Object ();
  for ( var i = 0; i < this.length; i++ ) {
    if ( seen[ this[i] ] ) continue;
    newArray.push( this[i] );
    seen[ this[i] ] = 1;
  }
  return newArray;
}


/*loads all the foaf data from the given file (or the session if there is no uri) into the editor.*/
function loadFoaf(){

	//TODO use jquery event handler to deal with errors on this request
	url = document.getElementById('foafUri').value;
  	$.post("/ajax/load-foaf", { uri: url}, function(data){objectsToDisplay(data);}, "json");
}

/*populates form fields etc from javascript objects (from json) and fills out the global arrays */ 
function objectsToDisplay(data){
  	
 	/*create an array for each type of predicate and populate them using the object data*/
 	foafPrimaryTopic = data[0].primaryTopic.uri;
 	foafNameValueArray = new Array();
 	foafHomepageValueArray = new Array();
 	foafNickValueArray = new Array();
 	
 	foafNameCount = 0;
  	foafHomepageCount = 0;
  	foafNickCount = 0;
 
 	for(i = 0 ; i < data.length ; i++){
 		if(data[i].foafName.label){
 			foafNameValueArray[foafNameCount] = data[i].foafName.label;
 			foafNameCount++;
 		}
 		if(data[i].foafHomepage.uri){
 			foafHomepageValueArray[foafHomepageCount] = data[i].foafHomepage.uri;
 			foafHomepageCount++;
 		}
 		if(data[i].foafNick.label){
 			foafNickValueArray[foafNickCount] = data[i].foafNick.label;
 			foafNickCount++;
 		}
 	}
 	
 	/*unique each array and then render the appropriate elements*/
 	foafNameValueArray = foafNameValueArray.dedup();
 	foafHomepageValueArray = foafHomepageValueArray.dedup();
 	foafNickValueArray = foafNickValueArray.dedup();
 	
  	for(i = 0 ; i < foafNameValueArray.length; i++){
  		foafNameElement = document.getElementById('foafName_'+i); 
  		/*either create a new element or fill in the old one*/
  		if(!foafNameElement){ 
  			newFoafNameElement = document.createElement('input');
  			newFoafNameElement.id = 'foafName_'+i;
  			newFoafNameElement.setAttribute('value',foafNameValueArray[i]);
  			document.getElementById('foafName_container').appendChild(newFoafNameElement);
		} else {
			foafNameElement.value = foafNameValueArray[i]
		}
	}	
	for(i = 0 ; i < foafHomepageValueArray.length; i++){
		/*either create a new element or fill in the old one*/
  		foafHomepageElement = document.getElementById('foafHomepage_'+i); 
  		if(!foafHomepageElement){ 
  			newFoafHomepageElement = document.createElement('input');
  			newFoafHomepageElement.id = 'foafHomepage_'+i;
  			newFoafHomepageElement.setAttribute('value',foafHomepageValueArray[i]);
  			document.getElementById('foafHomepage_container').appendChild(newFoafHomepageElement);
		} else {
			foafHomepageElement.value = foafHomepageValueArray[i];
		}
	}	
	for(i = 0 ; i < foafNickValueArray.length; i++){
		/*either create a new element or fill in the old one*/
  		foafNickElement = document.getElementById('foafNick_'+i); 
  		if(!foafNickElement){ 
  			newFoafNickElement = document.createElement('input');
  			newFoafNickElement.id = 'foafNick_'+i;
  			newFoafNickElement.setAttribute('value',foafNickValueArray[i]);
  			document.getElementById('foafNick_container').appendChild(newFoafNickElement);
		} else {
			foafNickElement.value = foafNickValueArray[i];
		}
	} 
	
	/*Clean up any extra unfilled fields*/
	j = foafNameValueArray.length;
	while(document.getElementById('foafName_'+j)){
		elementToEmptyOrRemove = document.getElementById('foafName_'+j);
		/*if it is the first element then simply empty it*/
		if(j==0){
			elementToEmptyOrRemove.value="";
		} else {
			elementToEmptyOrRemove.parentNode.removeChild(elementToEmptyOrRemove);
		}
		j++;
	}
	j = foafHomepageValueArray.length;
	while(document.getElementById('foafHomepage_'+j)){
		elementToEmptyOrRemove = document.getElementById('foafHomepage_'+j);
		/*if it is the first element then simply empty it*/
		if(j==0){
			elementToEmptyOrRemove.value="";
		} else {
			elementToEmptyOrRemove.parentNode.removeChild(elementToEmptyOrRemove);
		}
		j++;
	}
	j = foafNickValueArray.length;
	while(document.getElementById('foafNick_'+j)){
		elementToEmptyOrRemove = document.getElementById('foafNick_'+j);
		/*if it is the first element then simply empty it*/
		if(j==0){
			elementToEmptyOrRemove.value="";
		} else {
			elementToEmptyOrRemove.parentNode.removeChild(elementToEmptyOrRemove);
		}
		j++;
	}
}


/*populates the triples objects with stuff from the actual display (i.e. what the user has changed)*/
//TODO: needs to cope with added and deleted triples
//TODO: datatypes/languages

function displayToObjects(){

  	foafNameCount = 0;
  	foafHomepageCount = 0;
  	foafNickCount = 0;

	for(i = 0 ; i < foafNameValueArray.length ; i++){
		if(document.getElementById('foafName_'+i)){
			foafNameValueArray[i] = document.getElementById('foafName_'+i).value;
		}
	}
	for(i = 0 ; i < foafHomepageValueArray.length ; i++){
		if(document.getElementById('foafHomepage_'+i)){
			foafHomepageValueArray[i] = document.getElementById('foafHomepage_'+i).value;
		}
	}
	for(i = 0 ; i < foafNickValueArray.length ; i++){
		if(document.getElementById('foafNick_'+i)){
			foafNickValueArray[i] = document.getElementById('foafNick_'+i).value;
		}
	}
}

/*saves all the foaf data TODO: it might be a challenge making this quick*/
function saveFoaf(){
	displayToObjects();
	jsonstring = JSON.serialize(new PageDataObject());
	//TODO use jquery event handler to deal with errors on this request
  	$.post("/ajax/save-foaf", {model : jsonstring}, function(){}, "json");
  		
}

/*Writes FOAF to screen*/
function writeFoaf() {
        //$.post("/writer/write-Foaf", { }, function(data){alert(data.name);console.log(data.time);},"json");
	url = document.getElementById('writeUri').value;
        $.post("/writer/write-Foaf", {uri: url }, function(){},null);
}

/*Clears FOAF model from session*/
function clearFoaf() {
        //$.post("/ajax/clear-Foaf", { }, function(data){alert(data.name);console.log(data.time);},"json");
        $.post("/ajax/clear-Foaf", { }, function(){},null);
        
        /*empty all the text inputs*/
        var inputs = document.getElementsByTagName('input'); 
        for(i=0 ; i<inputs.length ; i++){
        	if(inputs[i].type=='text'){
        		document.getElementById(inputs[i].id).value = null;
        	}
        }
}



