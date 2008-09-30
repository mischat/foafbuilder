/*global variable for storing data*/
var globalFieldData;

/*google maps data*/
var mapMarkers = new Array;
var map;

/*--------------------------permanent data functions---------------------------*/
/*variable storing online account urls (e.g. www.skype.com) and keying them against their names (e.g. skype)*/

//TODO this should be a global array and integrate with QDOS
function getAllOnlineAccounts(){
	//TODO: need to increase this list.  See allAccountServiceurls file.
	var oA = new Array();
	oA['Skype'] = 'http://www.skype.com/';
	oA['Yahoo'] = 'http://messenger.yahoo.com/';
	oA['MSN'] = 'http://messenger.msn.com/';
	oA['Delicious'] = 'http://del.icio.us';
	oA['Flickr'] = 'http://www.flickr.com/';
	oA['Livejournal'] = 'http://www.livejournal.com/';
	
	return oA;
}

//XXX not really a data function but it seems to fit best here.  Turns a username into a profile page url, returns null if it can't.
//TODO: increase this list and arrange in a more sensible way. Possibly use QDOS here?
function getUrlFromOnlineAccounts(username,type){
	
	var allAccountsArray = getAllOnlineAccounts();

	if(typeof(allAccountsArray[type]) == 'undefined'){
		return null;
	} else {
		switch(type){
			case 'Skype':
				return null;
				break;
			case 'MSN':
				return null;
				break;
			case 'Yahoo':
				return null;
				break;
			case 'Delicious':
				return 'http://del.icio.us/'+username+'/';
				break;
			case 'Flickr':
				return 'http://www.flickr.com/people/'+username+'/';
				break;
			case 'Livejournal':
				return 'http://'+username+'.livejournal.com/';
				break;
			default:
				return null;
				break;
		}
	}
	
}


/*------------------------------------------------------------------------------*/

/*---------------------------------------load, save, clear, write (ajax functions)---------------------------------------*/

/*loads all the foaf data from the given file (or the session if there is no uri) into the editor.*/
function loadFoaf(name){

	url = document.getElementById('foafUri').value;

  	//TODO use jquery event handler to deal with errors on requests
  	//TODO perhaps this is bad.  There certainly should be less hardcoding here.
  	$.post("/ajax/"+name, { uri: url}, function(data){genericObjectsToDisplay(data);}, "json");
  	
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
function saveFoaf(name){
	displayToObjects(name);
	jsonstring = JSON.serialize(globalFieldData);
	//updateFoafDateOfBirthElements();
	//TODO use jquery event handler to deal with errors on this request
  	$.post("/ajax/save-Foaf", {model : jsonstring});
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


/*populates form fields etc from javascript objects (from json) */ 
function genericObjectsToDisplay(data){
	
	/*set the global variable which holds the data*/
	globalFieldData = data;
	  	
	/*clear out the right hand pane*/
	document.getElementById('personal');
  	document.getElementById('personal').innerHTML = '';	
	
	/*render the various fields*/
	renderAccountFields(data);
	renderBirthdayFields(data);
	renderLocationFields(data);
}

/*Render the location map*/
function renderLocationFields(data){

	if(!data || !data.locationFields || typeof(data.locationFields) == 'undefined'){
		return;
	}
	
	/*build the container*/
	var name = data.locationFields.name;
	var label =	data.locationFields.displayLabel;
	var containerElement = createFieldContainer(name, label);

	/*display a map*/
	map = createMapElement(containerElement);
	
	if(map){
		for(key in data.locationFields){
			if(key == 'basedNear'){
				addBasedNearMarkers(data.locationFields['basedNear'],containerElement,map);		
			}
		}
	}
}

function addBasedNearMarkers(basedNearArray, containerElement){

	/*loop over each based_near instance*/
	for(bNodeKey in basedNearArray){
		/*create an element to hold each location*/
		var locationDiv = createLocationElement(containerElement, bNodeKey);
		
		var latitude = basedNearArray[bNodeKey]['latitude'];
		var longitude = basedNearArray[bNodeKey]['longitude'];
		
		/*display the latitude and longitude coords*/
		var latitudeDiv = document.createElement('div');
		var longitudeDiv = document.createElement('div');
		latitudeDiv.id = 'latitude_'+locationDiv.id;
		longitudeDiv.id = 'longitude_'+locationDiv.id;
		locationDiv.appendChild(latitudeDiv);
		locationDiv.appendChild(longitudeDiv);
		latitudeDiv.appendChild(document.createTextNode('Latitude: '+latitude));
		longitudeDiv.appendChild(document.createTextNode('Longitude: '+longitude));
			
		createSingleBasedNearMarker(latitude, longitude, locationDiv.id,map);	
	  }


}

function createSingleBasedNearMarker(latitude, longitude, holderName, map){
		
		var point = new GLatLng(latitude,longitude);
		var marker = new GMarker(point,{draggable: true,title:'Near me'});
		map.addOverlay(marker);
		mapMarkers[holderName] = marker;
		
	  	/*everytime the marker is dropped, save the foaf*/
		GEvent.addListener(marker, "dragend", function(holderName) {
		        saveFoaf();
		      });	
		
		/*keep the latitude and longitude updated when the marker is dragged around*/
	    GEvent.addListener(marker, 'drag', function(){
			
	    	var latElement = document.getElementById('latitude_' + holderName);
	    	latElement.className='latitude';
	    	var longElement = document.getElementById('longitude_' + holderName);
	    	longElement.className='longitude';
	    	
	    	if(latElement.childNodes[0] && longElement.childNodes[0]){
	    		latElement.removeChild(latElement.childNodes[0]);
	    		longElement.removeChild(longElement.childNodes[0]);
	    	}
	    	
	    	var latText = document.createTextNode("Latitude: " +marker.getLatLng().lat().toString().substr(0,8));
	    	var longText = document.createTextNode("Longitude: " + marker.getLatLng().lng().toString().substr(0,8));
	    	
	    	latElement.appendChild(latText);
	    	longElement.appendChild(longText);
	    });
	    
	    //TODO: have some sort of sensible thing like this
	    map.setCenter(point);	
}

/*Render the birthday dropdown (assumes only one birthday)*/
function renderBirthdayFields(data){

	if(!data || !data.birthdayFields || typeof(data.birthdayFields) == 'undefined'){
		return;
	}
	
	/*build the container*/
	var name = data.birthdayFields.name;
	var label =	data.birthdayFields.displayLabel;
	var containerElement = createFieldContainer(name, label);
	
	/*build the date selector dropdown*/
	var day = data.birthdayFields['day'];
	var month = data.birthdayFields['month'];
	var year = data.birthdayFields['year'];	
	createFoafDateOfBirthElement(containerElement, day, month, year);
}

function renderAccountFields(data){
	
	//if(!data.foafHoldsAccountFields || typeof(data.foafHoldsAccountFields) == 'undefined'){
	if(!data || !data.foafHoldsAccountFields || typeof(data.foafHoldsAccountFields) == 'undefined'){
		return;
	}
	
	/*build the container*/
	var name = data.foafHoldsAccountFields.name;
	var label =	data.foafHoldsAccountFields.displayLabel;
	var containerElement = createFieldContainer(name, label);
	
	/*fill it up with accounts*/
	for(accountBnodeId in data.foafHoldsAccountFields){
		if(accountBnodeId != "displayLabel" && accountBnodeId != "name"){
		
			/*create a container for this account. E.g. a Skype account represented by accountBnodeId=bNode3*/
			var holdsAccountElement = createHoldsAccountElement(containerElement,accountBnodeId);
			
			/*create an element for the foafAccountServiceHomepage*/
			if(data.foafHoldsAccountFields[accountBnodeId].foafAccountServiceHomepage[0]){
				createFoafAccountServiceHomepageInputElement(data.foafHoldsAccountFields[accountBnodeId].foafAccountServiceHomepage[0].uri, holdsAccountElement);	
			} else {
				/*create an empty element*/
				createFoafAccountServiceHomepageInputElement('', holdsAccountElement);	
			}
			/*create an element for the foafAccountName*/
			if(data.foafHoldsAccountFields[accountBnodeId].foafAccountName[0]){
				createAccountsInputElement('foafAccountName', data.foafHoldsAccountFields[accountBnodeId].foafAccountName[0].label, holdsAccountElement);	
			} else {
				/*create an empty element*/
				createAccountsInputElement('foafAccountName', '', holdsAccountElement);	
			}
			/*create an element for the foafAccountProfilePage*/
			if(data.foafHoldsAccountFields[accountBnodeId].foafAccountProfilePage[0]){
				createAccountsInputElement('foafAccountProfilePage', data.foafHoldsAccountFields[accountBnodeId].foafAccountProfilePage[0].uri, holdsAccountElement);	
			} else {
				/*create an empty element*/
				createAccountsInputElement('foafAccountProfilePage', '', holdsAccountElement);	
			}
			/*hide/show the profilePage url as appropriate*/	
			if(data.foafHoldsAccountFields[accountBnodeId].foafAccountServiceHomepage[0].uri){
				toggleHiddenAccountInputElements(data.foafHoldsAccountFields[accountBnodeId].foafAccountServiceHomepage[0].uri,holdsAccountElement,'');
			}
		}//end if
	}//end for
	/*a link to add another account*/	
	createAccountsAddElement(containerElement);
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
//TODO: datatypes/language
function displayToObjects(name){  
	//TODO: we shouldn't have to put these numbers in
	switch(name){
		case 'load-the-basics':
			birthdayDisplayToObjects();
			break;
		case 'load-contact-details':
			return null;
			break;
		case 'load-accounts':
			accountsDisplayToObjects();
			break;
		default:
			return null;
			break;
	}
	//TODO MISCHA
//	birthdayDisplayToObjects();
	
//	simpleFieldsDisplayToObjects();
	
}

function simpleFieldsDisplayToObjects(){
	var containerElement = document.getElementById('foafHoldsAccount_container');
  	
  	//FIXME: do this if we actually have any simple fields left!!!
  	
  	//The code below used to use the arrays that were defined in main.phtml but they aren't there anymore.
  	//loop through all the arrays (for foafName, foafHomepage etc) defined in the pageData object
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
	}//end for	*/
}

function birthdayDisplayToObjects(){
	
	if(document.getElementById('yearDropdown').value){
		globalFieldData.birthdayFields['year'] = document.getElementById('yearDropdown').value; 
	} 
	if(document.getElementById('monthDropdown').value){
		globalFieldData.birthdayFields['month'] = document.getElementById('monthDropdown').value; 
	}
	if(document.getElementById('dayDropdown').value){
		globalFieldData.birthdayFields['day'] = document.getElementById('dayDropdown').value; 
	}

}

function accountsDisplayToObjects(){
	/*first do accounts stuff*/	
	/*TODO This will change when the display is improved + need a bit less hardcoding possibly*/
  	var containerElement = document.getElementById('foafHoldsAccount_container');
  	
  	/*an array of keys that have not been removed from the dom tree*/
 	var doNotCleanArray = new Array();
 	
  	for(i=0; i < containerElement.childNodes.length; i++){
  		
  		var holdsAccountElement = containerElement.childNodes[i];
  		var bNodeId = containerElement.childNodes[i].id;
  		
		/*some mangling to autogenerate profilePage urils */
  		updateProfilePageUrl(holdsAccountElement);
  		
  		/*we don't want to clean this from the globalFieldData*/
  		doNotCleanArray[bNodeId] = bNodeId;
  		
  		/*ignore all elements that don't don't contain accounts (such as add/remove links)*/
  		if(holdsAccountElement.className == "holdsAccount"){
  			//globalFieldData[i].foafHoldsAccountFields[containerElement.childNodes[i].id] = new Array();
		
			
  			for(k=0; k < containerElement.childNodes[i].childNodes.length; k++){
  				
  				if(holdsAccountElement.childNodes[k].value != ''){
  				
	  				//do the right thing for the right element, and miss any elements we don't care about.
	  				if (holdsAccountElement.childNodes[k].id == 'foafAccountName'){
	  					/*create a new element if this account is new*/
	  					if(!globalFieldData.foafHoldsAccountFields[bNodeId]){
	  						globalFieldData.foafHoldsAccountFields[bNodeId] = new Object;
	  					}
	  					if(globalFieldData.foafHoldsAccountFields[bNodeId]){
	  						globalFieldData.foafHoldsAccountFields[bNodeId]['foafAccountName'] = [{label : holdsAccountElement.childNodes[k].value}];
	  					}
	  				} else if(holdsAccountElement.childNodes[k].id == 'foafAccountProfilePage'){
	  					/*create a new element if this account is new*/
	  					if(!globalFieldData.foafHoldsAccountFields[bNodeId]){
	  						globalFieldData.foafHoldsAccountFields[bNodeId] = new Object;
	  					}
	  					globalFieldData.foafHoldsAccountFields[bNodeId]['foafAccountProfilePage'] = [{uri : holdsAccountElement.childNodes[k].value}];
	  				} else if (holdsAccountElement.childNodes[k].id == 'foafAccountServiceHomepage'){		
	  					/*create a new element if this account is new*/
	  					if(!globalFieldData.foafHoldsAccountFields[bNodeId]){
	  						globalFieldData.foafHoldsAccountFields[bNodeId] = new Object;
	  					}
	  					if(globalFieldData.foafHoldsAccountFields[bNodeId]){
	  						globalFieldData.foafHoldsAccountFields[bNodeId]['foafAccountServiceHomepage'] = [{uri : holdsAccountElement.childNodes[k].value}];				
	  					}
	  				} 	
	  			} 
  			}
  		} 
  	}
  	
  	/*remove all elements (accounts) from the globalFieldData object that have been removed from the dom tree*/
  	for(key in globalFieldData.foafHoldsAccountFields){
  		if(!doNotCleanArray[key]){
  			delete globalFieldData.foafHoldsAccountFields[key];
  		}
  	}
}
/*------------------------------------------------------------------------------*/

/*--------------------------------------element generators---------------------------------------*/

/*creates and returns a google map*/
function createMapElement(container){
      if (GBrowserIsCompatible()) {
      	var mapDiv = document.createElement('div');
      	mapDiv.id = 'mapDiv'
      	container.appendChild(mapDiv);
        
        var map = new GMap2(mapDiv);
        map.setCenter(new GLatLng(37.4419, -122.1419), 13);
        
        var mapControl = new GSmallMapControl();
		map.addControl(mapControl);
		
        return map;
      }
}

//FIXME: part of the old rendering for simple fields
/*creates an element for a given field, denoted by name and populates it with the appropriate value*/

/*function createElement(name,value,thisElementCount){
	//TODO: put some sort of big switch statement

	//create the containing div and label, if it hasn't already been made
	//TODO: need a more sensible way to decide whether to render these.
	if(name == 'bioBirthday' || name == 'foafBirthday' || name == 'foafDateOfBirth'){
		//We only want one birthday field, so create a container called foafDateOfBirth
		// and act like that's what we're dealing with now.
		createFirstFieldContainer('foafDateOfBirth');
		createFoafDateOfBirthElement(name, value, thisElementCount);
		
	} else if(name=='foafDepiction'){
		createFirstFieldContainer(name);
		createFoafDepictionElement(name, value, thisElementCount);
		
	} else {
		createFirstFieldContainer(name);
		createGenericInputElement(name, value, thisElementCount);
		
	}			
}*/

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
	newElement.setAttribute('onchange','saveFoaf()');
	newElement.id = name;
	newElement.setAttribute('value',value);
	
	/*if there is a specific container we want to put it in*/
	if(!element){
		var element = document.getElementById(name);
	}

	element.appendChild(newElement);
	newElement.setAttribute('class','fieldInput');

	return newElement;
}

/*renders a dropdown box with a list of possible accountServiceHomepages in it (e.g. skype, msn etc)*/
function createFoafAccountServiceHomepageInputElement(value,container){
	selectElement = document.createElement("select");

	var allAccounts = getAllOnlineAccounts();
	
	selectElement[0] = new Option('Other','',false,false);
	var y=1;
				
	/*loop through all online accounts and create options from them*/
	for(key in allAccounts){
		if(key != 'dedup'){
			selectElement[y] = new Option(key,allAccounts[key],false,false);
			y++;
		}
	}
	selectElement.id = 'foafAccountServiceHomepage';
	selectElement.className = 'fieldInput';
	selectElement.value = value;
	
	/*show the hidden input elements if there is no option matching this id here*/
	selectElement.setAttribute('onchange',"toggleHiddenAccountInputElements(this.value,this.parentNode, '');saveFoaf();");
	
	container.appendChild(selectElement);
}

function createAccountsAddElement(container){

	/*create add link and attach it to the container*/
	var addDiv = document.createElement("div");
	addDiv.id = "addLinkContainer";
	addDiv.className = "addLinkContainer";
	var addLink = document.createElement('a');
	addLink.appendChild(document.createTextNode("+Add another Account"));
	addLink.className="addLink";
	addLink.setAttribute("onclick" , "createEmptyHoldsAccountElement(this.parentNode.parentNode,null)");
	addDiv.appendChild(addLink);
	container.appendChild(addDiv);

}
/*creates an element to hold the information about a particular location*/
function createLocationElement(attachElement, bnodeId){
	
	/*if new, create a random id*/
	if(!bnodeId){
		var bnodeId = createRandomString(50);
	}
	
	/*create holdsAccount div and attach it to the element given*/
	var locationDiv = document.createElement("div");
	locationDiv.setAttribute('class','location');
	locationDiv.id = bnodeId;
	attachElement.appendChild(locationDiv);
	
	/*create remove link and attach it to the holds account div*/
	var removeDiv = document.createElement("div");
	removeDiv.id = "removeLinkContainer";
	removeDiv.className = "removeLinkContainer";
	var removeLink = document.createElement('a');
	removeLink.appendChild(document.createTextNode("- Remove this location"));
	removeLink.id="removeLink";
	removeLink.className="removeLink";
	removeLink.setAttribute("onclick" , "map.removeOverlay(mapMarkers[this.parentNode.parentNode.id]);this.parentNode.parentNode.parentNode.removeChild(this.parentNode.parentNode);");
	removeDiv.appendChild(removeLink);
	locationDiv.appendChild(removeDiv);
	
	return locationDiv;
}

function createHoldsAccountElement(attachElement, bnodeId){
	
	/*if new, create a random id*/
	if(!bnodeId){
		var bnodeId = createRandomString(50);
	}
	
	/*create holdsAccount div and attach it to the element given*/
	var holdsAccountElement = document.createElement("div");
	holdsAccountElement.setAttribute('class','holdsAccount');
	holdsAccountElement.id = bnodeId;
	attachElement.appendChild(holdsAccountElement);
	
	/*create remove link and attach it to the holds account div*/
	var removeDiv = document.createElement("div");
	removeDiv.id = "removeLinkContainer";
	removeDiv.className = "removeLinkContainer";
	var removeLink = document.createElement('a');
	removeLink.appendChild(document.createTextNode("- Remove this account"));
	removeLink.id="removeLink";
	removeLink.className="removeLink";
	removeLink.setAttribute("onclick" , "this.parentNode.parentNode.parentNode.removeChild(this.parentNode.parentNode);");
	removeDiv.appendChild(removeLink);
	holdsAccountElement.appendChild(removeDiv);
	
	return holdsAccountElement;
}

/*creates a holds account element and fills it with empty fields*/
function createEmptyHoldsAccountElement(container){
	
	/*create a new holdsaccount div*/
	var holdsAccountElement = createHoldsAccountElement(container, '');
	
	/*generate fields to fill it up*/
	createFoafAccountServiceHomepageInputElement('', holdsAccountElement);
	createAccountsInputElement('foafAccountName', '', holdsAccountElement);
	createAccountsInputElement('foafAccountProfilePage', '', holdsAccountElement);
	
	/*remove the add element and re add it (to make sure it's at the bottom)*/
	var addElement = document.getElementById('addLinkContainer');
	addElement.parentNode.removeChild(addElement);
	createAccountsAddElement(container);
}


/*creates and appends a generic input element to the appropriate field container*/
function createGenericInputElement(name, value, thisElementCount, contname){
	var newElement = document.createElement('input');
	newElement.id = name+'_'+thisElementCount;
	newElement.setAttribute('value',value);
	newElement.setAttribute('onchange','saveFoaf()');
	
	
	//if there is a specific container we want to put it in
	if(contname){
		name = contname;
	}
	
	document.getElementById(name+'_container').appendChild(newElement);
	newElement.setAttribute('class','fieldInput');
	
	return newElement;
}


/*creates and appends a generic hidden element and appends it to the appropriate field container*/
function createGenericHiddenElement(name, value, thisElementCount, contname){
	var newElement = document.createElement('input');
	newElement.id = name+'_'+thisElementCount;
	newElement.setAttribute('value',value);
	
	/*if there is a specific container we want to put it in*/
	if(contname){
		name = contname;
	}
	
	newElement.setAttribute('type','hidden');
	document.getElementById(name+'_container').appendChild(newElement);
	//newElement.setAttribute('class','fieldInput');
	
	return newElement;
}

//TODO: no longer used (part of old design) but may be handy when reimplementing foafDepiction stuff*/
/*creates an element for foaf depiction*/
/*
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
}*/

/*renders and attaches a date selector*/
function createFoafDateOfBirthElement(container, day, month, year){
	/*if we have rendered one of the alternative birthday things already then hide the other one*/
	//TODO: need to add some onchange functionality to ensure this saves properly.
	
  		var dayDropDownElement = document.createElement('select');
  		var monthDropDownElement = document.createElement('select');
  		var yearDropDownElement =document.createElement('select');
			
  		dayDropDownElement.setAttribute('class','dateSelector');
  		dayDropDownElement.id = 'dayDropdown';
  		dayDropDownElement.setAttribute('onchange','saveFoaf()');
  		monthDropDownElement.setAttribute('class','dateSelector');
  		monthDropDownElement.id = 'monthDropdown';
  		monthDropDownElement.setAttribute('onchange','saveFoaf()');
  		yearDropDownElement.setAttribute('class','dateSelector');
  		yearDropDownElement.id = 'yearDropdown';
  		yearDropDownElement.setAttribute('onchange','saveFoaf()');
  		
  		container.appendChild(dayDropDownElement);
  		container.appendChild(monthDropDownElement);
  		container.appendChild(yearDropDownElement);
  		
  		/*put the appropriate dates in the dropdown*/
  		populatedropdown(yearDropDownElement,monthDropDownElement,dayDropDownElement,year,month,day);	
}

/*---------------------------------- functions to ensure hidden fields are up to date-------------------------------*/


/*when an account dropdown is changed, this renders the appropriate hidden or showing fields 
for the users profile page and/or the account provider box*/
function toggleHiddenAccountInputElements(selectedValue,container,prePopulateValue){
	
	var allArrayNames = getAllOnlineAccounts();
	var allArrayNamesInverted = new Array();
	
	/*swap keys and values for convenience*/
	for(key in allArrayNames){
		allArrayNamesInverted[allArrayNames[key]] = key;
	}
	
	/*loop through the elements in the container in question and replace things as required*/
	for(var u=0; u<container.childNodes.length; u++){
	
		/*if Other has been selected in the dropdown (so selectedValue is '')*/ 
		if(typeof(allArrayNamesInverted[selectedValue]) == 'undefined'){
			if(container.childNodes[u].id == 'foafAccountProfilePage'){
				//TODO possibly add some nice onclick functionality here
				container.childNodes[u].value = 'Add URL of account service provider here';
				container.childNodes[u].style.display = 'inline';
			}
		} else {
			if(container.childNodes[u].id == 'foafAccountProfilePage'){
				/*hide the profile page box and set its value to blank.  On saving we'll fill it in.*/
				container.childNodes[u].value = '';
				container.childNodes[u].style.display = 'none';
			}
		}
	}
}

/*generates the profilePAgeURl from the username*/
function updateProfilePageUrl(container){
	//get username value to generate the uri for this account
	var allArrayNames = getAllOnlineAccounts();
	/*get all the usernames and invert them.  FIXME: this is going to become very slow if the inverted array names is not a global variable*/
	var allArrayNamesInverted = new Array();
	for(key in allArrayNames){
		allArrayNamesInverted[allArrayNames[key]] = key;
	}
	
	var accountServicePageElement = null;
	var username = null;
	var profilePageElement = null;
	
	/*find out what the username is and which element we need to set*/
	for(var z=0; z<container.childNodes.length; z++){
		if(container.childNodes[z].id == 'foafAccountName'){
			username = container.childNodes[z].value;
		}
		else if(container.childNodes[z].id == 'foafAccountProfilePage'){
			profilePageElement = container.childNodes[z];
		}
		else if(container.childNodes[z].id == 'foafAccountServiceHomepage'){
			accountServicePageElement = container.childNodes[z];
		}
	}
	
	if(username != null && accountServicePageElement && profilePageElement){
		var pageValue = getUrlFromOnlineAccounts(username,allArrayNamesInverted[accountServicePageElement.value]);
		if(pageValue){
			profilePageElement.value = pageValue;
		}
	} 
}

/*------------------------------miscellaneous utils-------------------------------*/

/*generates a random string*/
function createRandomString(varLength) {
	var sourceArr = new Array("a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z");
	var randomKey;
	var randomCode = "";

	for (i=0; i<varLength; i++) {
		randomKey = Math.floor(Math.random()*sourceArr.length);
		randomCode = randomCode + sourceArr[randomKey];
	}
	return randomCode;
}
