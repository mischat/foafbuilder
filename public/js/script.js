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
  	
  	pageData = new PageDataObject();

	for(arrayName in pageData){
		/*populate all the arrays in the pageData object (one for each field)*/		 	
	  	if(arrayName != 'foafPrimaryTopic'){
		
			var name = arrayName.substring(0,arrayName.length-10);
			for(k=0 ; k < data.length; k++){
			  	if(data[k][name].label){
			 		pageData[arrayName][pageData[arrayName].length] = data[k][name].label;
			 	} else if(data[k][name].uri){
			 		pageData[arrayName][pageData[arrayName].length] = data[k][name].uri;
			 	} 
		 	}
		 	pageData[arrayName] = pageData[arrayName].dedup();
	
		 	/*to keep track of which element we're on*/
		  	thisElementCount = 0;
		  		
			/*either create a new element or fill in the old one for all fields of a given type 
			* (e.g. there may be many foaf:nicks)*/
			for(i=0 ; i < pageData[arrayName].length; i++){
				element = document.getElementById(name+'_'+thisElementCount); 
			 	/*either create a new element or fill in the old one*/
			 	if(!element){ 
			  		newElement = document.createElement('input');
			  		newElement.id = name+'_'+thisElementCount;
			  		newElement.setAttribute('value',pageData[arrayName][i]);
			  		document.getElementById(name+'_container').appendChild(newElement);
				} else {
					element.value = pageData[arrayName][i];
				}
				thisElementCount++;
			}		
		
			 /*Clean up any extra unfilled fields*/
			var j = pageData[arrayName].length;
			while(document.getElementById(name+'_'+j)){
				elementToEmptyOrRemove = document.getElementById(name+'_'+j);
				/*if it is the first element then simply empty it*/
				if(j==0){
					elementToEmptyOrRemove.value="";
				} else {
					elementToEmptyOrRemove.parentNode.removeChild(elementToEmptyOrRemove);
				}
				j++;
			}//end while
	  	}//end if
	}//end for
}


/*populates the triples objects with stuff from the actual display (i.e. what the user has changed)*/
//TODO: needs to cope with added and deleted triples
//TODO: datatypes/languages

function displayToObjects(){

  	foafNameCount = 0;
  	foafHomepageCount = 0;
  	foafNickCount = 0;
  	
  	/*loop through all the arrays (for foafName, foafHomepage etc) defined in the pageData object*/
	for(arrayName in pageData){
		if(arrayName != "foafPrimaryTopic"){
			//chop off the ArrayValue bit at the end.
			var name = arrayName.substring(0,arrayName.length-10);
			for(i = 0 ; i < pageData[arrayName].length ; i++){
				if(document.getElementById(name+'_'+i)){
					pageData[arrayName][i] = document.getElementById(name+'_'+i).value;
				}
			}
		}//end if
	}//end for
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



