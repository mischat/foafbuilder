/*global variable for storing data*/
var globalFieldData;

/*---------------------------------------utils---------------------------------------*/

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

/*------------------------------------------------------------------------------*/

/*---------------------------------------load, save, clear, write (ajax functions)---------------------------------------*/

/*loads all the foaf data from the given file (or the session if there is no uri) into the editor.*/
function loadFoaf(name){

	url = document.getElementById('foafUri').value;
  	//we're now generating everything from javascript so we don't need to do this.
  	//$.post("/index/"+name, { uri: url}, function(data2){document.getElementById('personal').innerHTML=data2;});
  	
  	//TODO use jquery event handler to deal with errors on requests
  	//TODO perhaps this is bad.  There certainly should be less hardcoding here.
  	//if(name == 'load-accounts'){
  	//	$.post("/ajax/"+name, { uri: url}, function(data){genericObjectsToDisplay(data);}, "json");
  //	} else {
  		$.post("/ajax/"+name, { uri: url}, function(data){genericObjectsToDisplay(data);}, "json");
  	//}
  	
  	//FIXME: this is broken
  	document.getElementById('load-contact-details').style.backgroundImage = 'url(/images/pink_background.gif)';
  	document.getElementById('load-the-basics').style.backgroundImage = 'url(/images/pink_background.gif)';
  	document.getElementById('load-pictures').style.backgroundImage = 'url(/images/pink_background.gif)';
  	document.getElementById('load-accounts').style.backgroundImage = 'url(/images/pink_background.gif)';
  	document.getElementById('load-friends').style.backgroundImage = 'url(/images/pink_background.gif)';
  	document.getElementById('load-blogs').style.backgroundImage = 'url(/images/pink_background.gif)';
  	document.getElementById('load-interests').style.backgroundImage = 'url(/images/pink_background.gif)';
  	document.getElementById('load-other').style.backgroundImage = 'url(/images/pink_background.gif)';
  	
  	document.getElementById(name).style.backgroundImage='url(/images/blue_background.gif)';
}

/*saves all the foaf data*/
function saveFoaf(){
	displayToObjects();
	jsonstring = JSON.serialize(globalFieldData);
	
	//updateFoafDateOfBirthElements();
	
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

/*------------------------------------------------------------------------------*/

/*--------------------------------------inputs to objects---------------------------------------*/


/*populates form fields etc from javascript objects (from json) and fills out the global arrays */ 
function genericObjectsToDisplay(data){

	globalFieldData = data;
	  	
	document.getElementById('personal');

	//TODO: perhaps some fading here further down the line
  	document.getElementById('personal').innerHTML = '';	
  	
  	//TODO: perhaps don't need this loop
	for(i=0 ; i < data.length; i++){	
		var name = data[i].name;
		var containerElement = createFieldContainer(name, data[i].displayLabel);
		
		/*loop through all the simple fields and render them*/	
		if(data[i].fields){
			
			renderSimpleFields(i, name, data);

		/*render an account field*/
		} else if(data[i].foafHoldsAccountFields){
		
			renderAccountFields(i, data, containerElement);
		}
	}
}

function renderAccountFields(i, data, containerElement){
	//TODO: make this shiny i.e. use dropdowns and icons etc.
	
	//createAccountsInputElement(name, '', k, holdsAccountElement);	
	for(accountBnodeId in data[i].foafHoldsAccountFields){
		
		/*create a container for this account. E.g. a Skype account represented by accountBnodeId=bNode3*/
		var holdsAccountElement = createHoldsAccountElement(containerElement,accountBnodeId);
		
		/*create an element for the foafAccountProfilePage*/
		if(data[i].foafHoldsAccountFields[accountBnodeId].foafAccountProfilePage[0]){
			createAccountsInputElement('foafAccountProfilePage', data[i].foafHoldsAccountFields[accountBnodeId].foafAccountProfilePage[0].uri, holdsAccountElement);	
		} else {
			/*create an empty element*/
			createAccountsInputElement('foafAccountProfilePage', '', holdsAccountElement);	
		}
		
		/*create an element for the foafAccountName*/
		if(data[i].foafHoldsAccountFields[accountBnodeId].foafAccountName[0]){
			createAccountsInputElement('foafAccountName', data[i].foafHoldsAccountFields[accountBnodeId].foafAccountName[0].label, holdsAccountElement);	
		} else {
			/*create an empty element*/
			createAccountsInputElement('foafAccountName', '', holdsAccountElement);	
		}
		
		/*create an element for the foafAccountProfilePage*/
		if(data[i].foafHoldsAccountFields[accountBnodeId].foafAccountServiceHomepage[0]){
			createAccountsInputElement('foafAccountServiceHomepage', data[i].foafHoldsAccountFields[accountBnodeId].foafAccountServiceHomepage[0].uri, holdsAccountElement);	
		} else {
			/*create an empty element*/
			createAccountsInputElement('foafAccountServiceHomepage', '', holdsAccountElement);	
		}
	}
}

/*renders the appropriate simple fields for the index i in the json data, data with the name name*/
function renderSimpleFields(i, name, data){
	for(k=0 ; k < data[i].fields.length; k++){
		if(data[i].fields[k][name].label){
			createGenericInputElement(name, data[i].fields[k][name].label, k);
		} else if(data[i].fields[k][name].uri){
			createGenericInputElement(name, data[i].fields[k][name].uri, k);
		} 
	}
	if(data[i].fields.length == 0){
		createGenericInputElement(name, "", k);
	}
}

/*populates the triples objects with stuff from the actual display (i.e. what the user has changed)*/
//TODO: needs to cope with added and deleted triples and scary random stuff like combining different ways of describing a birthday
//TODO: datatypes/languages

function displayToObjects(){  
	
	/*first do accounts stuff*/	
	/*TODO This will change when the display is improved + need a bit less hardcoding possibly*/
  	var containerElement = document.getElementById('foafHoldsAccount_container');
 	
 	alert('display To objects called');
  	for(i=0; i < containerElement.childNodes.length; i++){
  		/*TODO check that the container is the one we want*/
  	//	if(holdsAccountNodes.className == "holdsAccount"){
  			//globalFieldData[i].foafHoldsAccountFields[containerElement.childNodes[i].id] = new Array();
  			
  			var holdsAccountElement = containerElement.childNodes[i];
  			var bNodeId = containerElement.childNodes[i].id;
  			
  			for(k=0; k < containerElement.childNodes[i].childNodes.length; k++){
  			
  				//do the right thing for the right element, and miss any elements we don't care about.
  				if(holdsAccountElement.childNodes[k].id == 'foafAccountProfilePage'){
  					globalFieldData[0].foafHoldsAccountFields[bNodeId]['foafAccountProfilePage'] = [{uri : holdsAccountElement.childNodes[k].value}];
  				} else if (holdsAccountElement.childNodes[k].id == 'foafAccountName'){
  					globalFieldData[0].foafHoldsAccountFields[bNodeId]['foafAccountName'] = [{label : holdsAccountElement.childNodes[k].value}];
  				} else if (holdsAccountElement.childNodes[k].id == 'foafAccountServiceHomepage'){		
  					globalFieldData[0].foafHoldsAccountFields[bNodeId]['foafAccountServiceHomepage'] = [{uri : holdsAccountElement.childNodes[k].value}];				
  				} 
  			}
  		//} 
  		
  	}
  	
  	//TODO: sort this out.  This used to use the arrays that were defined in main.phtml but they aren't there anymore.
  	/*loop through all the arrays (for foafName, foafHomepage etc) defined in the pageData object*/
	/*
	for(arrayName in pageData){
		if(arrayName != "foafPrimaryTopic"){
			//chop off the ArrayValue bit at the end.
			var name = arrayName.substring(0,arrayName.length-10);
			
			for(i=0; document.getElementById(name+'_'+i); i++){
				//TODO: what about validation.  Where's it to go?
				if(document.getElementById(name+'_'+i) != ""){
					pageData[arrayName][i] = document.getElementById(name+'_'+i).value;
				}
			}
		}//end if
	}//end for
	*/
}

/*------------------------------------------------------------------------------*/

/*--------------------------------------element generators---------------------------------------*/

/*creates an element for a given field, denoted by name and populates it with the appropriate value*/
function createElement(name,value,thisElementCount){
	//TODO: put some sort of big switch statement

	/*create the containing div and label, if it hasn't already been made*/
	//TODO: need a more sensible way to decide whether to render these.
	if(name == 'bioBirthday' || name == 'foafBirthday' || name == 'foafDateOfBirth'){
		/*We only want one birthday field, so create a container called foafDateOfBirth *
		 * and act like that's what we're dealing with now.*/
		createFirstFieldContainer('foafDateOfBirth');
		createFoafDateOfBirthElement(name, value, thisElementCount);
		
	} else if(name=='foafDepiction'){
		createFirstFieldContainer(name);
		createFoafDepictionElement(name, value, thisElementCount);
		
	} else {
	
		createFirstFieldContainer(name);
		createGenericInputElement(name, value, thisElementCount);
		
	}			
}

/*creates and appends a field container for the given name if it is not already there*/
function createFieldContainer(name,label){
	//if(!document.getElementById(name+'_container')){
		/*label*/
		newFieldLabelContainer = document.createElement('div');
		newFieldLabelContainer.setAttribute("class","fieldLabelContainer");
		textNode = document.createTextNode(label);
		newFieldLabelContainer.appendChild(textNode);

		/*value*/
		newFieldValueContainer = document.createElement('div');
		newFieldValueContainer.id = name+'_container';
		newFieldValueContainer.setAttribute("class","fieldValueContainer");

		/*container*/
		newFieldContainer = document.createElement('div');
		newFieldContainer.setAttribute("class","fieldContainer");

		/*append them*/
		container = document.getElementById('personal');
		container.appendChild(newFieldContainer);
		newFieldContainer.appendChild(newFieldLabelContainer);
		newFieldContainer.appendChild(newFieldValueContainer);
		
		return newFieldValueContainer;
	//}
}

/*creates and appends an account input element to the appropriate field container*/
/*TODO: need one of these for each different type of account element*/
function createAccountsInputElement(name, value, element){
	newElement = document.createElement('input');
	newElement.id = name;
	newElement.setAttribute('value',value);
	
	/*if there is a specific container we want to put it in*/
	if(element){
		name = element.id;
	}
	
	document.getElementById(name).appendChild(newElement);
	newElement.setAttribute('class','fieldInput');
	
	return newElement;
}


function createHoldsAccountElement(attachElement, bnodeId){
	var holdsAccountElement = document.createElement("div");
	holdsAccountElement.setAttribute('class','holdsAccount');
	holdsAccountElement.id = bnodeId;
	attachElement.appendChild(holdsAccountElement);
	
	return holdsAccountElement;
}


/*creates and appends a generic input element to the appropriate field container*/
function createGenericInputElement(name, value, thisElementCount, contname){
	newElement = document.createElement('input');
	newElement.id = name+'_'+thisElementCount;
	newElement.setAttribute('value',value);
	
	/*if there is a specific container we want to put it in*/
	if(contname){
		name = contname;
	}
	
	document.getElementById(name+'_container').appendChild(newElement);
	newElement.setAttribute('class','fieldInput');
	
	return newElement;
}


/*creates and appends a generic hidden element and appends it to the appropriate field container*/
function createGenericHiddenElement(name, value, thisElementCount, contname){
	newElement = document.createElement('input');
	newElement.id = name+'_'+thisElementCount;
	newElement.setAttribute('value',value);
	
	/*if there is a specific container we want to put it in*/
	if(contname){
		name = contname;
	}
	
	newElement.setAttribute('type','hidden');
	document.getElementById(name+'_container').appendChild(newElement);
//	newElement.setAttribute('class','fieldInput');
	
	return newElement;
}

//TODO: think about being more strict about variable scoping
/*creates an element for foaf depiction*/
function createFoafDepictionElement(name, value, thisElementCount){
	//create imgElement
	imgElement = document.createElement('img');
	imgElement.setAttribute('class','fieldImage');
	imgElement.src = value;

	//create input Element
	newElement = document.createElement('input');
	newElement.id = name+'_'+thisElementCount;
	newElement.setAttribute('value',value);
	newElement.setAttribute('class','fieldInput');

	//appendElements as necessary
	document.getElementById(name+'_container').appendChild(newElement);
	document.getElementById(name+'_container').appendChild(imgElement);
}

function createFoafDateOfBirthElement(name, value, thisElementCount){
	/*if we have rendered one of the alternative birthday things already then hide the other one*/
	//TODO: need to add some onchange functionality to ensure this saves properly.
	if(document.getElementById(name+'_container')){
		var hiddenElement = createGenericHiddenElement(name, value, thisElementCount,'foafDateOfBirth');		

  		var dayDropDownElement = document.createElement('select');
  		var monthDropDownElement = document.createElement('select');
  		var yearDropDownElement =document.createElement('select');
		dayDropDownElement.setAttribute('onchange', 'updateFoafDateOfBirthElements()');
		monthDropDownElement.setAttribute('onchange', 'updateFoafDateOfBirthElements()');
		yearDropDownElement.setAttribute('onchange', 'updateFoafDateOfBirthElements()');
			
  		dayDropDownElement.setAttribute('class','dateSelector');
  		dayDropDownElement.id = 'dayDropdown';
  		monthDropDownElement.setAttribute('class','dateSelector');
  		monthDropDownElement.id = 'monthDropdown';
  		yearDropDownElement.setAttribute('class','dateSelector');
  		yearDropDownElement.id = 'yearDropdown';
  		
  		document.getElementById(name+'_container').appendChild(dayDropDownElement);
  		document.getElementById(name+'_container').appendChild(monthDropDownElement);
  		document.getElementById(name+'_container').appendChild(yearDropDownElement);
  		
  		/*populate dropdowns with appropriate values*/
  		var dateArray = value.split("-");

  		if(dateArray.length == 2){
  			populatedropdown(dayDropDownElement,monthDropDownElement,yearDropDownElement,dateArray[0],dateArray[1],null);
		} else if(dateArray.length == 3){
  			populatedropdown(dayDropDownElement,monthDropDownElement,yearDropDownElement,dateArray[0],dateArray[1],dateArray[2]);
		} else {
			//FIXME: need to have a cleverer way of dealing with this.
  			alert("Date string invalid");
		}
	} else {
		createGenericHiddenElement(name, value, thisElementCount,'foafDateOfBirth');
	}
}

/*---------------------------------- functions to ensure hidden fields are up to date... TODO: poss to be done at save?-------------------------------*/

/*ensures that all the hidden date of birth fields are up to date*/
function updateFoafDateOfBirthElements(){
	var i=0
	
	for(i=0; i < document.getElementById('foafDateOfBirth_container').childNodes.length; i++){

		var element = document.getElementById('foafDateOfBirth_container').childNodes[i];
		
		//FIXME: ensure that this updates things in the right way i.e. only month and year for foaf:birthday
		var dayValue = document.getElementById('dayDropdown').value;
		if(dayValue.length==1){
			dayValue = '0'+dayValue;
		}

		var monthValue =document.getElementById('monthDropdown').value;
		if(monthValue.length==1){
			monthValue = '0'+monthValue;
		}
		var yearValue = document.getElementById('yearDropdown').value;
		
		var value;

		if(parseFloat(yearValue) == 0){
			/*we only want to set foafBirthday if this is the case*/
			if(element.id.substr(0,12) == 'foafBirthday'){
				element.value = monthValue+'-'+dayValue;
			} else {
				/*TODO: deal with deleting elements*/
				element.value = '';
			}
		} else {
			if(element.id.substr(0,12) == 'foafBirthday'){
				element.value = monthValue+'-'+dayValue;
			} else {
				element.value = yearValue+'-'+monthValue+'-'+dayValue;
			}
		}
	}																																
	document.getElementById('foafDateOfBirth_container').childNodes[i];
}


