/*for logging purposes*/
var loggingOn = false;

/*--------------------------global variables--------------------------*/


/*global variable for storing data*/
var globalFieldData = new Object();

/*global variable for storing private data*/
var globalPrivateFieldData = new Object();

/*current page*/
var currentPage ='load-the-basics';//the page the user is on e.g. load-contact-details etc.

/*for friend searching, so we know the type of the ifp we searched for*/
var globalTypeArray = new Array;

/*some variables to show that we've added address details*/
var addedOfficeAddressMarker
var addedHomeAddressMarker;

/*geocoding details so that the various callback functions can access them*/
var addressDetailsToGeoCode = new Array();
var existingAddressDetailsToGeoCode = new Array();
var basedNearDetailsToGeoCode = new Array();

/*Div containing an element for airport autocomple entry*/
var autocomplete_autocompleteDiv;

/*google maps data*/
var mapMarkers = new Array();
var map;

/*variable to hold all the accounts data*/
var allAccounts;


/*--------------------------functions which make ajax calls to control the whole model - load, save, clear, write(TODO: implement this properly)--------------------------*/

/*display/hide the spinner*/
function turnOffLoading(){
	document.getElementById('ajaxLoader').style.display = 'none';
}
function turnOnLoading(){
	document.getElementById('ajaxLoader').style.display = 'inline';	
}

/*loads all the foaf data from the given file (or the session if there is no uri) into the editor.*/
function loadFoaf(name,url){
	
	var foafUrl = null;
	
	/*so we can track which page the person is on*/
	currentPage = name;
	
  	//TODO use jquery event handler to deal with errors on requests
  	if(name != 'load-other'){
  		turnOnLoading();
  		$.post("/ajax/"+name, { uri: url}, function(data){genericObjectsToDisplay(data);turnOffLoading();}, "json");
  	} else {
  		renderOther();
  	}
  	document.getElementById('load-contact-details').style.backgroundImage = 'url(/images/pink_background.gif)';
  	document.getElementById('load-the-basics').style.backgroundImage = 'url(/images/pink_background.gif)';
  	document.getElementById('load-pictures').style.backgroundImage = 'url(/images/pink_background.gif)';
  	document.getElementById('load-accounts').style.backgroundImage = 'url(/images/pink_background.gif)';
  	document.getElementById('load-locations').style.backgroundImage = 'url(/images/pink_background.gif)';
  	document.getElementById('load-friends').style.backgroundImage = 'url(/images/pink_background.gif)';
  	document.getElementById('load-other').style.backgroundImage = 'url(/images/pink_background.gif)'; 	
  	document.getElementById(name).style.backgroundImage='url(/images/blue_background.gif)';
}

/*saves all the foaf data*/
function saveFoaf(){
	displayToObjects(currentPage);
	
	var totalFieldData = new Object();
	totalFieldData.private = globalPrivateFieldData;
	totalFieldData.public = globalFieldData;
	jsonString = JSON.serialize(totalFieldData);

	turnOnLoading();
	
	//TODO use jquery event handler to deal with errors on this request
  	$.post("/ajax/save-Foaf", {model : jsonString}, function(data){turnOffLoading();});
}

/*Clears FOAF model from session*/
function clearFoaf() {
        turnOnLoading();
        $.post("/ajax/clear-Foaf", { }, function(){turnOffLoading();},null);
        
        /*empty all the text inputs*/
        var inputs = document.getElementsByTagName('input'); 
        for(i=0 ; i<inputs.length ; i++){
        	if(inputs[i].type=='text'){
        		document.getElementById(inputs[i].id).value = null;
        	}
        }
}

/*saves all the foaf data*/
function findFriend(uri){
	//TODO use jquery event handler to deal with errors on this request
  	$.get("/friend/find-friend", {uri : uri}, function(data){renderFoundFriend(data);turnOffLoading();},'json');
}

function renderFoundFriend(data){
	
	var containerElement = document.getElementById('addFriends_container');

	var foundFriend = document.getElementById('foundFriend_0');
	
	if(typeof(foundFriend) != 'undefined' && foundFriend){
		foundFriend.parentNode.removeChild(foundFriend);
	}
	
	/*either render the friend just below or render a message apologising*/
	if(typeof(data.ifps) != 'undefined' && typeof(data.ifps[0]) != 'undefined' && data.ifps[0] 
		&& typeof(data.name) != 'undefined' && data.name){
		
		createFriendElement('foundFriend',data,0,containerElement);
		
		/*we need to store the type here for saving*/
		globalTypeArray[data.ifps[0]] = data.ifp_type;
		
		createAddFriendLink(document.getElementById('foundFriend_0'));
	} else {
		var notFound = document.createElement('div');
		notFound.id = 'foundFriend_0';
		notFound.className = 'friend';	
		notFound.appendChild(document.createTextNode("Sorry but we didn't find anything for that uri/ifp"));
		containerElement.appendChild(notFound);
		
	}
}

/*Writes FOAF to screen*/
function renderOther() {
		turnOnLoading();
		
		var url = document.getElementById('writeUri').value;
        $.post("/writer/write-foafn3", {uri: url }, function(data){drawOtherTextarea(data);turnOffLoading();},'json');
        
}

/*writes foaf to oauth server or similar*/
function write(privacy){

	if(typeof(privacy) == 'undefined' || !privacy){
		log('privacy not passed in');
		return;
	} 
	
	var textArea = document.getElementById('otherTextArea'+privacy);

	if(typeof(textArea) == 'undefined' || !textArea){
		log('textarea not found');
		return;
	}
	
	var value = textArea.value;
	
	if(typeof(value) == 'undefined' || !value){
		log('value of text area not defined');
		return;
	}

//TODO MISCHA ... is this how we should do it, with a post request ?	
//    $.post("/writer/write-foafn3-"+privacy, {data: value}, function(data){turnOffLoading();},null);
       if(privacy == 'private'){
		$.post("/writer/write-foaf-"+privacy, {}, function(data){});
	} else {
		window.location = '/writer/write-foafn3-'+privacy;
	}
     
}





/*--------------------------main function to convert globalFieldData objects to HTML elements--------------------------*/

/*sets the globalFieldData object with data and calls all the render functions*/ 
function genericObjectsToDisplay(data){
	
	/*set the global variable which holds the data*/
	globalFieldData = data.public;
	globalPrivateFieldData = data.private;
	
	/*clear out the right hand pane*/
	document.getElementById('personal');
  	document.getElementById('personal').innerHTML = '';	
  	
  	/*remove any existing map*/
	if(document.getElementById('mapDiv')){
		var mapDiv = document.getElementById('mapDiv');
		mapDiv.parentNode.removeChild(mapDiv);
	}
	/*for the new style privacy stuff*/
	simpleFieldsObjectsToDisplay(data);
	mboxFieldsObjectsToDisplay(data);
	phoneFieldsObjectsToDisplay(data);
	addressFieldsObjectsToDisplay(data);
	imgFieldsObjectsToDisplay(data);
	depictionFieldsObjectsToDisplay(data);
	nearestAirportFieldsObjectsToDisplay(data);
	basedNearFieldsObjectsToDisplay(data);
	accountsObjectsToDisplay(data);
	homepageObjectsToDisplay(data);
	birthdayFieldsObjectsToDisplay(data);
	weblogObjectsToDisplay(data);
	//renderHomepageFields(data);
	
	/*friends stuff does not have privacy settings*/
	renderKnowsFields(data);
	
	/*render the various fields*/
	//renderBirthdayFields(data);


}

/*-----------------objects to display elements to convert data to html elements and split public/private pairs up-----------------*/

function nearestAirportFieldsObjectsToDisplay(data){
	if(!data){
		return;
	}
	if(data.private){
		renderNearestAirportFields(data.private,false);
	}
	if(data.public){
		renderNearestAirportFields(data.public,true);
	}
}
function imgFieldsObjectsToDisplay(data){
	if(!data){
		return;
	}
	if(data.private){
		renderImgFields(data.private,false);
	}
	if(data.public){
		renderImgFields(data.public,true);
	}
}
function depictionFieldsObjectsToDisplay(data){
	if(!data){
		return;
	}
	if(data.private){
		renderDepictionFields(data.private,false);
	}
	if(data.public){
		renderDepictionFields(data.public,true);
	}
}
function simpleFieldsObjectsToDisplay(data){
	if(!data){
		return;
	}
	if(data.private){
		renderSimpleFields(data.private,false);
	}
	if(data.public){
		renderSimpleFields(data.public,true);
	}
}

function mboxFieldsObjectsToDisplay(data){
	if(!data){
		return;
	}
	if(data.private){
		renderMboxFields(data.private,false);
	}
	if(data.public){
		renderMboxFields(data.public,true);
	}
}

function phoneFieldsObjectsToDisplay(data){
	if(!data){
		return;
	}
	if(data.private){
		renderPhoneFields(data.private,false);
	}
	if(data.public){
		renderPhoneFields(data.public,true);
	}
}

function addressFieldsObjectsToDisplay(data){
	if(!data){
		return;
	}
	addedOfficeAddressMarker = false;
	addedHomeAddressMarker = false;
	
	/*We only want to render home once and office once (not once for both public and private)*/
	if(data.private){
		renderAddressFields(data.private,false);
	}
	if(data.public){
		renderAddressFields(data.public,true);
	}
}
function basedNearFieldsObjectsToDisplay(data){
	if(!data){
		return;
	}
	if(data.private){
		renderBasedNearFields(data.private,false);
	}
	if(data.public){
		renderBasedNearFields(data.public,true);
	}
}
function accountsObjectsToDisplay(data){
	if(!data){
		return;
	}
	if(data.private){
		renderAccountFields(data.private,false);
	}
	if(data.public){
		renderAccountFields(data.public,true);
	}
	
	//populate all the dropdown boxes
	//populateAllAccountsDropdowns();
}
function homepageObjectsToDisplay(data){
	if(!data){
		return;
	}
	if(data.private){
		renderHomepageFields(data.private,false);
	}
	if(data.public){
		renderHomepageFields(data.public,true);
	}
}
function weblogObjectsToDisplay(data){
	if(!data){
		return;
	}
	if(data.private){
		renderWeblogFields(data.private,false);
	}
	if(data.public){
		renderWeblogFields(data.public,true);
	}
}
function birthdayFieldsObjectsToDisplay(data){
	if(!data){
		return;
	}
	if(data.private){
		renderBirthdayFields(data.private,false);
	}
	if(data.public){
		renderBirthdayFields(data.public,true);
	}
}



/*--------------------------second level functions to convert globalFieldData into HTML elements--------------------------*/
function renderAccountFields(data,isPublic){
	log('rendering accounts');
	
	//if(!data.foafHoldsAccountFields || typeof(data.foafHoldsAccountFields) == 'undefined'){
	if(!data || typeof(data.foafHoldsAccountFields) == 'undefined' || !data.foafHoldsAccountFields){
		log('returning from render accounts');
		return;
	}
	/**/
	if(typeof(allAccounts) == 'undefined' || !allAccounts){
		$.post("/accounts/get-all-account-types", {}, function(response){ getAccountsAndRender(data,isPublic,response);},'json');
	} else {
		getAccountsAndRender(data,isPublic,allAccounts);
	}
}

function getAccountsAndRender(data,isPublic,response){
	
	allAccounts = response;

	/*some details*/
	var name = data.foafHoldsAccountFields.name;
	var label = data.foafHoldsAccountFields.displayLabel;

	/*build the container if it isn't already there*/
	var containerElement = document.getElementById(name+'_container');
	if(!containerElement){
		containerElement = createFieldContainer(name, label);
	}
	
	for(accountBnodeId in data.foafHoldsAccountFields){
		log('In loop accounts:'+accountBnodeId);

		if(accountBnodeId == 'displayLabel' || accountBnodeId == 'name'){
			continue;
		}
		


		var thisAccount = data.foafHoldsAccountFields[accountBnodeId];
		
		createSingleAccount(thisAccount, accountBnodeId, containerElement,isPublic);
		
			
	}
	
	//add a link to add another account. We only want to do this once. XXX this relies on the public bit being rendered second
	if(isPublic){
		createAccountsAddElement(containerElement);
	}
}

//creates one account container, puts it in containerElement, gives it ID accountBnodeId and puts in it the info from thisAccount.
function createSingleAccount(thisAccount,accountBnodeId,containerElement,isPublic){

		//create a remove link
		createGenericInputElementRemoveLink(accountBnodeId,containerElement.id,false);
		
		//create a privacy checkbox
		createGenericInputElementPrivacyBox(accountBnodeId, containerElement.id,!isPublic);
		
		//create a div to hold this account
		var accountDiv = document.createElement('div');
		accountDiv.id = accountBnodeId;
		containerElement.appendChild(accountDiv);
		accountDiv.className = 'holdsAccount';
		
		//create a select element for the account type
		var selectElement = document.createElement("select");
		selectElement.className = 'accountTypeSelect';
		selectElement.setAttribute('onchange','toggleAccountFields("'+accountDiv.id+'",this.value)');
		accountDiv.appendChild(selectElement);		
		
		//whether we'll need to show the extra boxes (if the accountservicehomepage isn't in the dropsown)
		var needExtraBoxes = true;

		selectElement[0] = new Option('Other','',false,false);
		var y=1;                         
                //loop through all online accounts and create options from them
                for(key in allAccounts){
			log('value:'+key);
			log('key:'+allAccounts[key]);

                         if(key != 'dedup'){
                                 selectElement[y] = new Option(allAccounts[key]['name'],allAccounts[key]['page'],false,false);
				 if(thisAccount.foafAccountServiceHomepage == allAccounts[key]['page']
					|| thisAccount.foafAccountServiceHomepage==allAccounts[key]['page']+'/'
					|| thisAccount.foafAccountServiceHomepage+'/'==allAccounts[key]['page']){
					selectElement[y].selected = true;
					needExtraBoxes = false;
				 }
                                 y++;
			}
                }

		
		//create an input element for the username
		var userNameElement = document.createElement('input');
		userNameElement.className = 'accountUsername';
		log('this account name::::'+thisAccount['foafAccountName']);
		if(typeof(thisAccount['foafAccountName']) != 'undefined' && thisAccount['foafAccountName']){
			userNameElement.value = thisAccount['foafAccountName'];
		} else {
			userNameElement.value = 'Username';
			userNameElement.style.color = '#dddddd';
		}
		userNameElement.setAttribute('onfocus',"if(this.value == 'Username'){this.value='';this.style.color='#000000';}");
		accountDiv.appendChild(userNameElement);

		//create an input element for the account service type (for display if it isn't in the dropdown)
		var accountServiceTypeInput = document.createElement('input');
		accountServiceTypeInput.className = 'accountTypeInput';
		accountServiceTypeInput.id = 'accountTypeInput_'+accountDiv.id;
		if(needExtraBoxes){
			accountServiceTypeInput.style.display='inline';
		}
		if(typeof(thisAccount['foafAccountServiceHomepage']) != 'undefined' && thisAccount['foafAccountServiceHomepage']){
                        accountServiceTypeInput.value = thisAccount['foafAccountServiceHomepage'];	
                } else {
			accountServiceTypeInput.value = 'Account service homepage';
			accountServiceTypeInput.style.color = '#dddddd';
		}
		accountServiceTypeInput.setAttribute('onfocus',"if(this.value == 'Account service homepage'){this.value='';this.style.color='#000000';}");
		accountDiv.appendChild(accountServiceTypeInput);
			
		//create an input element for the account profile page (for display if the type isn't in the dropdown)
		var accountProfileElem = document.createElement('input');
		accountProfileElem.className = 'accountProfile';
		accountProfileElem.id = 'accountProfile_'+accountDiv.id;
		if(needExtraBoxes){
			accountProfileElem.style.display='inline';
		}
		if(typeof(thisAccount['foafAccountProfilePage']) != 'undefined' && thisAccount['foafAccountProfilePage']){
                          accountProfileElem.value = thisAccount['foafAccountProfilePage'];
		} else {
			accountProfileElem.value = 'Account profile page';
			accountProfileElem.style.color = '#dddddd';

		}
		accountProfileElem.setAttribute('onfocus',"if(this.value == 'Account profile page'){this.value='';this.style.color='#000000';}");
		accountDiv.appendChild(accountProfileElem);
}

function toggleAccountFields(id,value){
	
	if(!id || typeof(id) == 'undefined'){
		log('id undefined in toggling accounts');
		return;
	}
	
	var field1 = document.getElementById('accountTypeInput_'+id);
	var field2 = document.getElementById('accountProfile_'+id);

	if(typeof(field1) == 'undefined' || !field1 || typeof(field2) == 'undefined' || !field2){
		log('field undefined in toggling accounts');
		return;
	}

	//i.e. other was selected
	if(!value || typeof(value) == 'undefined'){
		field1.style.display = 'inline';
		field1.value = 'Account service homepage';
		field1.style.color = '#dddddd';		

		field2.style.display = 'inline';
		field2.value = 'Account profile page';
                field2.style.color = '#dddddd';
	} else {
		field1.style.display = 'none';
		field2.style.display = 'none';
	}
}




/*renders either the private or the public fields*/
function renderSimpleFields(data,isPublic){

	for(element in data){
		if(element == 'fields'){
			for(fieldType in data[element]){
				var name = data[element][fieldType]['name'];
				var label = data[element][fieldType]['displayLabel'];
				var values = data[element][fieldType]['values'];
				
				//either get the container or create a new one (depends on the private function being called first).
				var containerElement = document.getElementById(name+'_container');
		
				if(!containerElement){
					containerElement = createFieldContainer(name, label);
				} 
				
				var i = containerElement.childNodes.length;
				
				if(values.length != 0){
					//create an input element for each value
					for(item in values){
						createGenericInputElement(name, values[item], containerElement.childNodes.length,false, false, false, !isPublic);	
						i++;	
					}	
				} else {
					if(isPublic){
						//create an empty field passing in the isNew flag.  Only do this once for a public field
						createGenericInputElement(name, 'Enter '+label+' here',0,false,true,false,!isPublic);	
					}
				}
				if(isPublic){
					/*create an add link XXX this means that we always have to do private fields before public ones*/
					createGenericAddElement(containerElement,name,label);
				}
			}
		}
	}	
}

/*Render the image fields*/
function renderImgFields(data, isPublic){
	
	if(typeof(data) == 'undefined' || !data || typeof(isPublic) == 'undefined'){
		log("Returning!!! isPublic: "+isPublic);
		return;
	}
	if(!data.foafImgFields 
		|| typeof(data.foafImgFields) == 'undefined'){
		log('returning img');
		return;
	}
	if(!data.foafImgFields.name
		|| typeof(data.foafImgFields.name) == 'undefined'){
			log('returning img');
		return;	
	}
	if(!data.foafImgFields.displayLabel
		|| typeof(data.foafImgFields.displayLabel) == 'undefined'){
			log('returning img');
		return;	
	}
	
	/*build the container*/
	var name = data.foafImgFields.name;
	var label = data.foafImgFields.displayLabel;
	
	/*create a container if required*/
	var containerElement = document.getElementById(name+'_container');
	if(!containerElement){
		var containerElement = createFieldContainer(name, label);
	}

	/*render each individual image element*/	
	for(image in data.foafImgFields['images']){
		log("rendering image element: "+isPublic);
		renderImgElement(data.foafImgFields['images'][image],image,containerElement,isPublic);
	}
	
	/*render the image menu i.e. upload new, link to an image if one has not been rendered already*/
	//XXX relies on private being done before public
	if(!document.getElementById('menuDiv_'+name) && isPublic){
		renderImageMenu(name, containerElement,isPublic);
	}
}


/*Render the image fields*/
function renderDepictionFields(data, isPublic){
	
	if(typeof(data) == 'undefined' || !data || typeof(isPublic) == 'undefined'){
		log("Returning!!! isPublic: "+isPublic);
		return;
	}
	if(!data.foafDepictionFields 
		|| typeof(data.foafDepictionFields) == 'undefined'){
		log('returning depiction');
		return;
	}
	if(!data.foafDepictionFields.name
		|| typeof(data.foafDepictionFields.name) == 'undefined'){
			log('returning depiction');
		return;	
	}
	if(!data.foafDepictionFields.displayLabel
		|| typeof(data.foafDepictionFields.displayLabel) == 'undefined'){
			log('returning depiction');
		return;	
	}
	
	/*build the container*/
	var name = data.foafDepictionFields.name;
	var label = data.foafDepictionFields.displayLabel;
	
	/*create a container if required*/
	var containerElement = document.getElementById(name+'_container');
	if(!containerElement){
		var containerElement = createFieldContainer(name, label);
	}

	/*render each individual image element*/	
	for(image in data.foafDepictionFields['images']){
		log("rendering image element: "+isPublic);
		renderDepictionElement(data.foafDepictionFields['images'][image],image,containerElement,isPublic);
	}
	
	/*render the image menu i.e. upload new, link to an image if one has not been rendered already*/
	//XXX relies on private being done before public
	if(!document.getElementById('menuDiv_'+name) && isPublic){
		renderImageMenu(name, containerElement,isPublic);
	}
}

/*Render the birthday dropdown (assumes only one birthday)*/
function renderBirthdayFields(data,isPublic){

	log('rendering birthday fields');
	if(!data || !data.foafBirthdayFields || typeof(data.foafBirthdayFields) == 'undefined'){
		log('Couldnt find foaf birthday fields');
		return;
	}
	
	/*build the container if it isn't already there*/
	var name = data.foafBirthdayFields.name;
	var label =	data.foafBirthdayFields.displayLabel;
	var containerElement = document.getElementById(name+'_container');
	
	/*day, month and year*/
	var day = data.foafBirthdayFields['day'];
	var month = data.foafBirthdayFields['month'];
	var year = data.foafBirthdayFields['year'];	
	
	/*if nothing is set and we're not on the public one then do nothing*/
	//XXX this relies on rendering the public part second
	var allUndefined = typeof(day) == 'undefined' && typeof(month) == 'undefined' && typeof(year) == 'undefined';
	var allNull = !day && !month && !year;
	if((allNull || allUndefined) && !isPublic){
		return;	
	}
	
	//only build it if there isn't one already there, since there can't be two birthdays.
	if(!containerElement){
		containerElement = createFieldContainer(name, label);
		log('day: '+day);

		/*create the element which shows the date of birth*/
		createFoafDateOfBirthElement(containerElement, day, month, year);	
		createGenericInputElementPrivacyBox(containerElement.id, containerElement.id,!isPublic);
	}
}

/*Render the HomepageField*/
function renderHomepageFields(data,isPublic){
	
	log('trying to render homepages');
	
	//XXX this is just like renderMboxFields
	if(!data || !data.foafHomepageFields || typeof(data.foafHomepageFields) == 'undefined'){
		return;
	}
	
	var name = data.foafHomepageFields.name;
	var label = data.foafHomepageFields.displayLabel;
	var name = data.foafHomepageFields.name;
	
	/*build the container if it isn't there already*/
	var containerElement = document.getElementById(name+"_container");
	if(!containerElement){
		containerElement = createFieldContainer(name, label);
	}

	/*render each individual homepage element*/	
	var i = containerElement.childNodes.length;
	if(typeof(data.foafHomepageFields.values) != 'undefined' && data.foafHomepageFields.values && typeof(data.foafHomepageFields.values[0])!='undefined'){
		for(homepage in data.foafHomepageFields.values){
			log(data.foafHomepageFields.values[homepage]);
			createGenericInputElement(name, data.foafHomepageFields.values[homepage], i,false,false,false,!isPublic);	
			i++;
			log('in for loop for homepages');
		}
	} else {
		
		/*create an empty field but only if this is the first one XXX this depends on this function being called in the public sense initially*/
		if(isPublic){
			createGenericInputElement(name, 'My Homepage Here', i,false,true,false,!isPublic);
		}
	}	
	
	/*create an add link XXX this means we have to display public fields before private ones*/
	if(isPublic){
		createGenericAddElement(containerElement,name,label);
	}

}


/*Render the HomepageField*/
function renderWeblogFields(data,isPublic){
	
	log('trying to render homepages');
	
	//XXX this is just like renderMboxFields
	if(!data || !data.foafWeblogFields || typeof(data.foafWeblogFields) == 'undefined'){
		return;
	}
	
	var name = data.foafWeblogFields.name;
	var label = data.foafWeblogFields.displayLabel;
	var name = data.foafWeblogFields.name;
	
	/*build the container if it isn't there already*/
	var containerElement = document.getElementById(name+"_container");
	if(!containerElement){
		containerElement = createFieldContainer(name, label);
	}

	/*render each individual homepage element*/	
	var i = containerElement.childNodes.length;
	if(typeof(data.foafWeblogFields.values) != 'undefined' && data.foafWeblogFields.values && typeof(data.foafWeblogFields.values[0])!='undefined'){
		for(homepage in data.foafWeblogFields.values){
			log(data.foafWeblogFields.values[homepage]);
			createGenericInputElement(name, data.foafWeblogFields.values[homepage], i,false,false,false,!isPublic);	
			i++;
			log('in for loop for homepages');
		}
	} else {
		
		/*create an empty field but only if this is the first one XXX this depends on this function being called in the public sense initially*/
		if(isPublic){
			createGenericInputElement(name, 'My Homepage Here', i,false,true,false,!isPublic);
		}
	}	
	
	/*create an add link XXX this means we have to display public fields before private ones*/
	if(isPublic){
		createGenericAddElement(containerElement,name,label);
	}

}


/*renders the appropriate phone fields*/
function renderPhoneFields(data,isPublic){
	//XXX this is just like renderMboxFields
	if(!data || !data.foafPhoneFields || typeof(data.foafPhoneFields) == 'undefined'){
		return;
	}
	
	var name = data.foafPhoneFields.name;
	var label = data.foafPhoneFields.displayLabel;
	var name = data.foafPhoneFields.name;
	
	/*build the container if it isn't there already*/
	var containerElement = document.getElementById(name+"_container");
	if(!containerElement){
		containerElement = createFieldContainer(name, label);
	}
	
	/*render each individual phone element*/	
	var i = containerElement.childNodes.length;
	if(typeof(data.foafPhoneFields.values) != 'undefined' && data.foafPhoneFields.values && typeof(data.foafPhoneFields.values[0])!='undefined'){
		for(phone in data.foafPhoneFields.values){
			log(data.foafPhoneFields.values[phone]);
			createGenericInputElement(name, data.foafPhoneFields.values[phone], i,false,false,false,!isPublic);	
			i++;
		}
	} else {
		/*create an empty field but only if this is the first one XXX this depends on this function being called in the public sense initially*/
		if(isPublic){
			createGenericInputElement(name, '+4402071234567', i,false,true,false,!isPublic);
		}
	}	
	
	/*create an add link XXX this means we have to display public fields before private ones*/
	if(isPublic){
		createGenericAddElement(containerElement,name,label);
	}
}


function renderNearestAirportFields(data,isPublic){
	if(!data || typeof(data.nearestAirportFields) == 'undefined'){
		return;
	}
	if(typeof(data.nearestAirportFields.name) == 'undefined' ||
		typeof(data.nearestAirportFields.displayLabel) == 'undefined' ||
		!data.nearestAirportFields.name ||
		!data.nearestAirportFields.displayLabel){
		return;	
	}
	//don't bother if we've already rendered it
	if(document.getElementById('nearestAirport')){
		return;
	}

	/*create a map element if there isn't one already*/
	var mapElement = createMapElement();	
	
	/*append the mapElement to the main container and change the class to ensure it's right*/
	if(mapElement){
		var containerDiv = document.getElementById('personal');
		containerDiv.appendChild(mapElement);
		mapElement.className = 'embeddedMapDiv';
		map.checkResize();
		
		//change the class of the map inside this one
		mapElement.childNodes[0].className = 'embeddedMapDiv';
	} 
	
	/*build the container element if it doesn't already exist*/
	var name = data.nearestAirportFields.name;
	var label =	data.nearestAirportFields.displayLabel;

	var containerElement = document.getElementById(name+'_container');
	if(!containerElement){
		containerElement = createFieldContainer(name, label);
	}
	
	if(map){
		/*render the markers on the map and add divs containing the information below*/
		addNearestAirportMarker(data.nearestAirportFields['nearestAirport'],containerElement,map,isPublic);			
	}
}


/*renders the appropriate mbox fields*/
function renderMboxFields(data,isPublic){
	if(!data || !data.foafMboxFields || typeof(data.foafMboxFields) == 'undefined'){
		return;
	}
	
	var name = data.foafMboxFields.name;
	var label = data.foafMboxFields.displayLabel;
	
	/*build the container if it isn't there already*/
	var containerElement = document.getElementById(name+"_container");
	if(!containerElement){
		containerElement = createFieldContainer(name, label);
	}
	
	/*render each individual phone element*/	
	var i = containerElement.childNodes.length;
	if(typeof(data.foafMboxFields.values) != 'undefined' && data.foafMboxFields.values && typeof(data.foafMboxFields.values[0])!='undefined'){
		for(mbox in data.foafMboxFields.values){
			createGenericInputElement(name, data.foafMboxFields.values[mbox], i,false,false,false,!isPublic);	
			i++;
		}
	} else {
		/*create an empty field but only if this is the first one XXX this depends on this function being called in the public sense initially*/
		if(isPublic){
			createGenericInputElement(name, 'example@example.com', i,false,true,false,!isPublic);
		}
	}	
	
	/*create an add link XXX this means we have to display public fields before private ones*/
	if(isPublic){
		createGenericAddElement(containerElement,name,label);
	}
}


/*Render the location map*/
function renderAddressFields(data,isPublic){
	
	if(!data || !data.addressFields || typeof(data.addressFields) == 'undefined'){
		return;
	}
	
	var name = data.addressFields.name;
	var label =	data.addressFields.displayLabel;
	
	/*create a map element if there isn't one already*/
	createMapElement();
	
	/*create a container if there isn't one already*/
	var containerElement = document.getElementById(name+"_container");
	if(!containerElement){
		containerElement = createFieldContainer(name, label);
	}
	
	if(map){
		/*render the markers on the map and add divs containing the information below*/
		addAddressMarkers(data.addressFields['office'],data.addressFields['home'],containerElement,map,isPublic);	
	}
}

function renderBasedNearFields(data,isPublic){
	if(!data || !data.basedNearFields || typeof(data.basedNearFields) == 'undefined'){
		return;
	}
	if(typeof(isPublic) == 'undefined'){
		return;
	}
	
	/*create a map element if there isn't one already*/
	var mapElement = createMapElement();	
	
	/*append the mapElement to the main container and change the class to ensure it's the right */
	if(mapElement){
		var containerDiv = document.getElementById('personal');
		containerDiv.appendChild(mapElement);
		mapElement.className = 'embeddedMapDiv';
		map.checkResize();
		
		//change the class of the map inside this one
		mapElement.childNodes[0].className = 'embeddedMapDiv';
	} else {
		//perhaps we should do something here?
	}
	
	/*build the container if it isn't there already*/
	var name = data.basedNearFields.name;
	var label =	data.basedNearFields.displayLabel;
	var containerElement = document.getElementById(name+'_container');
	
	if(!containerElement){
		containerElement = createFieldContainer(name, label);
	}
	if(map){
		/*render the markers on the map and add divs containing the information below*/
		addBasedNearMarkers(data.basedNearFields['basedNear'],containerElement,map,isPublic);		
	}
}

/*render the various relationships of the user*/
function renderKnowsFields(data){
	if(!data || !data.foafKnowsFields || typeof(data.foafKnowsFields) == 'undefined'){
		return;
	}
	globalFieldData = data;
	renderSearchUI();
	renderMutualFriends(data.foafKnowsFields);
	renderKnowsUserFields(data.foafKnowsFields);//like incoming friend requests
	renderUserKnowsFields(data.foafKnowsFields);//like outgoing friend requests
	//TODO: could we add a timestamp to show people that have recently accepted friend requests etc.
	
}



/*--------------------------functions to render various elements for particular field types--------------------------*/

	/*--------------------------Img/Depiction--------------------------*/
	//XXX why is this different to the one below it?
	function renderImgElement(image,count,containerElement,isPublic){
		
		log('in function render image element');
		if(typeof(image) == 'undefined' ||
			!image || 
			typeof(containerElement) == 'undefined' ||
			!containerElement ||
			typeof(isPublic) == 'undefined'){
			
			log('returning fron render image element isPublic = '+isPublic);
			return;
		}		
		
		/*create the image element*/
		var imageElement = document.createElement('img');
		imageElement.setAttribute('src',image['uri']);
		
		if(typeof(image['title']) != 'undefined' && image['title']){
			imageElement.setAttribute('title',image['title']);
		}
		if(typeof(image['description']) != 'undefined' && image['description']){
			imageElement.setAttribute('alt',image['description']);
		}
		
		imageElement.id = 'foafImg_'+count;
		imageElement.className = 'image';
	
		/*create (and append) the remove link*/
		createGenericInputElementPrivacyBox(imageElement.id, containerElement.id,!isPublic);
		createGenericInputElementRemoveLink(imageElement.id, containerElement.id,true);		
			
		/*tack the image element onto the container*/
		containerElement.appendChild(imageElement);
		
		return imageElement;
	}
	
	function renderDepictionElement(image,count,containerElement,isPublic){
		
		log('in function render image element');
		if(typeof(image) == 'undefined' ||
			!image || 
			typeof(containerElement) == 'undefined' ||
			!containerElement ||
			typeof(isPublic) == 'undefined'){
			
			log('returning from render image element isPublic = '+isPublic);
			return;
		}		
		
		/*create the image element*/
		var imageElement = document.createElement('img');
		imageElement.setAttribute('src',image['uri']);
		
		if(typeof(image['title']) != 'undefined' && image['title']){
			imageElement.setAttribute('title',image['title']);
		}
		if(typeof(image['description']) != 'undefined' && image['description']){
			imageElement.setAttribute('alt',image['description']);
		}
		
		imageElement.id = 'foafDepiction_'+count;
		imageElement.className = 'image';
	
		/*create (and append) the remove link and privacy checkbox*/
		createGenericInputElementPrivacyBox(imageElement.id, containerElement.id,!isPublic);
		createGenericInputElementRemoveLink(imageElement.id, containerElement.id,true);		
			
		/*tack the image element onto the container*/
		containerElement.appendChild(imageElement);
		
		return imageElement;
	}
	
	/*renders the menu which allows you to upload images or submit urls*/
	function renderImageMenu(name,containerElement){
	
		/*create a div to hold this stuff*/
		var menuDiv = document.createElement('div');
		menuDiv.id = 'menuDiv_'+name;
		menuDiv.className = 'menuDiv';
		containerElement.appendChild(menuDiv);
		
		/*create a form to do the nifty file upload stuff*/
		var menuForm = document.createElement('form');
		menuDiv.appendChild(menuForm);
		menuForm.id = 'menuForm_'+name;
		
		menuForm.setAttribute('onsubmit',"return AIM.submit(this, {'onStart' : startCallback, 'onComplete' : uploadCallback_"+name+"_public})")
	
		
		/*create a form to do the link to image stuff*/
		var menuFormLink = document.createElement('form');
		menuDiv.appendChild(menuFormLink);
		menuFormLink.id = 'menuForm_'+name;	
		
		/*create and append upload label*/
		var uploadLabel = document.createElement('div');
		uploadLabel.appendChild(document.createTextNode('Upload new'));
		uploadLabel.className = 'uploadLabel';
		menuForm.appendChild(uploadLabel);
		menuForm.setAttribute('method','post');
		menuForm.setAttribute('action','/file/upload-image');
		menuForm.setAttribute('enctype','multipart/form-data');
		
		/*create and append upload input field*/
		var uploadElement = document.createElement('input');
		uploadElement.className = 'uploadElement';
		uploadElement.id = 'uploadElement_'+name;
		uploadElement.name = 'uploadedImage';
		uploadElement.setAttribute('type','file');
		menuForm.appendChild(uploadElement);
		
		/*create and append link to image label*/
		var linkToImageLabel = document.createElement('div');
		linkToImageLabel.appendChild(document.createTextNode('Link to image'));
		linkToImageLabel.className = 'linkToImageLabel';
		menuFormLink.appendChild(linkToImageLabel);
		
		/*create and append link to image field*/
		var linkToImageInput = document.createElement('input');
		linkToImageInput.className = 'linkToImage';
		linkToImageInput.name = 'linkToImage_'+name;
		linkToImageInput.id = 'linkToImage_'+name;
		linkToImageInput.setAttribute('onchange','previewImage("'+containerElement.id+'","'+name+'",this.value,false,true);');
		menuFormLink.appendChild(linkToImageInput);
		
		/*create a submit button and append it*/
		var submitElement = document.createElement('input');
		submitElement.className = 'submitElement';
		submitElement.id = 'submitElement_'+name;
		submitElement.className = 'imageSubmitButton';
		submitElement.setAttribute('type','submit');
		submitElement.setAttribute('value','Add');
		menuForm.appendChild(submitElement);
		
		var submitElementLink = document.createElement('input');
		submitElementLink.className = 'linkSubmitElement';
		submitElementLink.id = 'linkSubmitElement_'+name;
		submitElementLink.className = 'linkImageSubmitButton';
		submitElementLink.setAttribute('type','button');
		submitElementLink.setAttribute('value','Add');
		menuFormLink.appendChild(submitElementLink);
		
	}
	
	
	/*--------------------------Knows/Friends--------------------------*/
	
	/*renders the inputField etc*/
	function renderSearchUI(){
		var containerElement = createFieldContainer('addFriends', 'Add Friends');
		
		var findForm = document.createElement('form');
		findForm.id='findForm';
		containerElement.appendChild(findForm);
		var inputElement = createGenericInputElement('searchInputField', 'Enter search IFP here', 0,findForm.id,true,true);
		
		findForm.setAttribute('action',"javascript:findFriend(document.getElementById('"+inputElement.id+"').value);");
		
		/*create a button to submit the form*/
		var img = document.createElement('img');
		img.id = 'findButton';
		img.setAttribute('onclick',"document.getElementById('findForm').submit();");
		img.setAttribute('src',"/images/go.png");
		containerElement.appendChild(img);
	}
	
	function renderMutualFriends(foafKnowsFields){
		if(!foafKnowsFields || !foafKnowsFields.mutualFriends || typeof(foafKnowsFields.mutualFriends) == 'undefined'){
			return;
		}
		/*build the container*/
		var containerElement = createFieldContainer('mutualFriends', 'Friends');
		
		for(friend in foafKnowsFields.mutualFriends){
			
			var friendDiv = createFriendElement('mutualFriend',foafKnowsFields.mutualFriends[friend],containerElement.childNodes.length,containerElement);
			
			createRemoveFriendsLink(friendDiv.id,friendDiv.id,true);
			
			//TODO: there should be an 'i don't know this person which removes the person and puts them in the 'they know you' place
		}
		
	}

	function renderUserKnowsFields(foafKnowsFields){
		if(!foafKnowsFields || !foafKnowsFields.userKnows|| typeof(foafKnowsFields.userKnows) == 'undefined'){
			return;
		}
		/*build the container*/
		var containerElement = createFieldContainer('userKnows', 'User knows');
		
		for(friend in foafKnowsFields.userKnows){
			
			var friendDiv = createFriendElement('userKnows',foafKnowsFields.userKnows[friend],containerElement.childNodes.length,containerElement);
	
			createRemoveFriendsLink(friendDiv.id,friendDiv.id,false);
		}	
		
	}

	function renderKnowsUserFields(foafKnowsFields){
		if(!foafKnowsFields || !foafKnowsFields.knowsUser || typeof(foafKnowsFields.knowsUser) == 'undefined'){
			return;
		}
		/*build the container*/
		var containerElement = createFieldContainer('knowsUser', 'Knows user');
	
		for(friend in foafKnowsFields.knowsUser){
			
			var friendDiv = createFriendElement('knowsUser',foafKnowsFields.knowsUser[friend],containerElement.childNodes.length,containerElement);
			//TODO: there should be an ignore link (I don't know this person) but not sure how this would be implemented in rdf
			createMakeMutualFriendLink(friendDiv);
		}
		
	}
	
	/*--------------------------Addresses--------------------------*/


	/*adds markers and divs for home and office addresses*/
	function addAddressMarkers(office,home,containerElement,map,isPublic){

		//check if they've both been done
		if(addedOfficeAddressMarker && addedHomeAddressMarker){
			return;
		}
		
		//add the office address if required
		if(!addedOfficeAddressMarker){
			
			for(bNodeKey in office){
				log("Adding Office Marker isPublic? "+isPublic);
				addSingleAddressMarker('Office Address',office[bNodeKey],bNodeKey,containerElement,'office',isPublic);
				addedOfficeAddressMarker = true;
				break;//currently we only want one office + one home address
			}
			
			//we haven't added an office marker and we're on the last call of this function, so add a blank element.
			//XXX this relies on rendering private then public 
			if(!addedOfficeAddressMarker && isPublic){
				log("adding blank office address");
				var bNodeKeyPlacemark = createRandomString(50);
				addSingleAddressMarker('Office Address',bNodeKeyPlacemark,bNodeKeyPlacemark,containerElement,'office',isPublic);
				addedOfficeAddressMarker = true;
			}
		}
		
		//add the home address if required
		if(!addedHomeAddressMarker){

			for(bNodeKey in home){
				log("Adding Home Marker "+isPublic);
				addSingleAddressMarker('Home Address',home[bNodeKey],bNodeKey,containerElement,'home',isPublic);
				addedHomeAddressMarker = true;
				break;//currently we only want one office + one home address
			}
			
			//we haven't added a home marker and we're on the last call of this function, so add a blank element.
			//XXX this relies on rendering private then public 
			if(!addedHomeAddressMarker && isPublic){
				log("adding blank home address");
				var bNodeKeyPlacemark = createRandomString(50);
				addSingleAddressMarker('Home Address',bNodeKeyPlacemark,bNodeKeyPlacemark,containerElement,'home',isPublic);
				addedHomeAddressMarker = true;
			}
		}
	}
	
	/*get either the home or office address element, or nothing*/
	//XXX no longer used
	function getAddressElementByPrefix(prefix){
		
		if(!prefix){
			return;
		}
		var addressContainer = document.getElementById('address_Container');
		
		if(!addressContainer){
			return;
		}
		
		var returnElem;
		
		for(nodeKey in addressContainer.childNodes){
			if(addressContainer.childNodes[nodeKey].className==prefix+"Address"){
				returnElem = addressContainer.childNodes[nodeKey];
				break;
			}
		}	
		
		return returnElem;	
	}
	
	/*adds one address marker*/
	function addSingleAddressMarker(title,address,bNodeKey,containerElement,prefix,isPublic){
		
		var latitude = address['latitude'];
		var longitude = address['longitude'];
		
		//i.e. a new blank address
		if(bNodeKey.length == '50'){
			//TODO: change these values to those of the garlik address
			latitude = '40';
			longitude = '34';
		}
		
		/*there is an address there but the latitude and longitude isn't set*/
		if(!latitude && !longitude){
			var addressArray = getProperties(address);
			
			/*array to pass to the geocoder's callback function*/
			theseDetails = new Array();
			theseDetails['bnode'] = bNodeKey;	
			theseDetails['prefix'] = prefix;
			theseDetails['container'] = containerElement;
			theseDetails['address'] = address;
			theseDetails['title'] = title;	
			theseDetails['isPublic'] = isPublic;	
			addressDetailsToGeoCode.push(theseDetails);
	
			/*do the actual geoCoding*/
			var geocoder = new GClientGeocoder();
			geocoder.getLatLng(addressArray,geoCodeNewAddress);
	   		
		} else{
			var point = new GLatLng(latitude, longitude);
			var marker = new GMarker(point,{title: prefix});	
			
			mapMarkers[bNodeKey] = marker;
		
			map.addOverlay(marker);
			map.setCenter(point);
			
			createAddressDiv(title,address,bNodeKey,containerElement,latitude,longitude, prefix,isPublic);
		}
	}
		
	/*creates divs for addresses*/
	function createAddressDiv(title,address,bNodeKey,containerElement, latitude, longitude, prefix,isPublic){
	
		/*TODO: need to worry about how we pick all of this stuff up when we save and this method can easily be made shorter*/
		var locationDiv = createLocationElement(containerElement, bNodeKey, prefix+'Address',true);
		
		/*create the radiobutton for privacy*/
		createGenericInputElementPrivacyBox(bNodeKey,bNodeKey,!isPublic)
		
		/*link to view on map*/
		var viewOnMapDiv = document.createElement('div');
		viewOnMapDiv.className = 'viewOnMapContainer';
		viewOnMapLink = document.createElement('a');
		viewOnMapLink = makeCursorAPointer(viewOnMapLink);
		viewOnMapLink.className = 'viewOnMapLink';
		viewOnMapLink.appendChild(document.createTextNode('view on map'));
		viewOnMapLink.setAttribute('onclick',"displayMap('"+bNodeKey+"')");
		viewOnMapDiv.appendChild(viewOnMapLink);
		locationDiv.appendChild(viewOnMapDiv);
		
		/*title: e.g. home address, office address etc*/
		var addressTitleDiv = document.createElement('div');
		addressTitleDiv.className = 'addressTitle';
		addressTitleDiv.appendChild(document.createTextNode(title));
		locationDiv.appendChild(addressTitleDiv);
		
		/*latitude and longitude*/
		//TODO: geocoding to get these if necessary
		var latitudeDiv = document.createElement('div');
		var longitudeDiv = document.createElement('div');
		latitudeDiv.appendChild(document.createTextNode('Latitude: '+latitude));
		longitudeDiv.appendChild(document.createTextNode('Longitude: '+longitude));
		locationDiv.appendChild(longitudeDiv);
		locationDiv.appendChild(latitudeDiv);
		latitudeDiv.id = 'latitude_'+bNodeKey;
		longitudeDiv.id = 'longitude_'+bNodeKey;
		latitudeDiv.className='latitude';
		longitudeDiv.className='longitude';
		
		/*street 1*/
		var streetLabelDiv = document.createElement('div');
		streetLabelDiv.appendChild(document.createTextNode('Address line 1:'));
		streetLabelDiv.className='streetLabel';
		locationDiv.appendChild(streetLabelDiv);
		var streetInputElement = document.createElement('input');
		streetInputElement.className='street';
		streetInputElement.id = 'street';
		streetInputElement.setAttribute('onChange',"placeAddressDisplayToObjects('"+prefix+"','"+bNodeKey+"');");
		locationDiv.appendChild(streetInputElement);
		//populate it
		if(address[prefix+'Street']){
			streetInputElement.value = address[prefix+'Street'];
		}
		
		/*street 2*/
		var street2LabelDiv = document.createElement('div');
		street2LabelDiv.appendChild(document.createTextNode('Address line 2:'));
		street2LabelDiv.className='street2Label';
		locationDiv.appendChild(street2LabelDiv);
		var street2InputElement = document.createElement('input');
		street2InputElement.id = 'street2';
		street2InputElement.setAttribute('onChange',"placeAddressDisplayToObjects('"+prefix+"','"+bNodeKey+"');");
		locationDiv.appendChild(street2InputElement);
		
		//populate it
		if(address[prefix+'Street2']){
			street2InputElement.value = address[prefix+'Street2'];
		}
		
		/*street 3*/
		var street3LabelDiv = document.createElement('div');
		street3LabelDiv.appendChild(document.createTextNode('Address line 3:'));
		street3LabelDiv.className='street3Label';
		locationDiv.appendChild(street3LabelDiv);
		var street3InputElement = document.createElement('input');
		street3InputElement.id = 'street3';
		street3InputElement.setAttribute('onChange',"placeAddressDisplayToObjects('"+prefix+"','"+bNodeKey+"');");
		locationDiv.appendChild(street3InputElement);
		//populate it
		if(address[prefix+'Street3']){
			street3InputElement.value = address[prefix+'Street3'];
		}
			
		/*postalCode*/
		var postalCodeLabelDiv = document.createElement('div');
		postalCodeLabelDiv.appendChild(document.createTextNode('Postal Code:'));
		postalCodeLabelDiv.className='postalCodeLabel';
		locationDiv.appendChild(postalCodeLabelDiv);
		var postalCodeInputElement = document.createElement('input');
		postalCodeInputElement.id = 'postalCode';
		postalCodeInputElement.setAttribute('onChange',"placeAddressDisplayToObjects('"+prefix+"','"+bNodeKey+"');");
		locationDiv.appendChild(postalCodeInputElement);
		//populate it
		if(address[prefix+'PostalCode']){
			postalCodeInputElement.value = address[prefix+'PostalCode'];
		}
		
		/*city*/
		var cityLabelDiv = document.createElement('div');
		cityLabelDiv.appendChild(document.createTextNode('City:'));
		cityLabelDiv.className='cityLabel';
		locationDiv.appendChild(cityLabelDiv);
		var cityInputElement = document.createElement('input');
		cityInputElement.id = 'city';
		cityInputElement.setAttribute('onChange',"placeAddressDisplayToObjects('"+prefix+"','"+bNodeKey+"');");
		locationDiv.appendChild(cityInputElement);
		//populate it
		if(address[prefix+'City']){
			cityInputElement.value = address[prefix+'City'];
		}
		
		/*country*/
		/*TODO: possibly make this a dropdown*/
		var countryLabelDiv = document.createElement('div');
		countryLabelDiv.appendChild(document.createTextNode('Country:'));
		countryLabelDiv.className='countryLabel';
		locationDiv.appendChild(countryLabelDiv);
		var countryInputElement = document.createElement('input');
		countryInputElement.className='country';
		countryInputElement.id = 'country';
		countryInputElement.setAttribute('onChange',"placeAddressDisplayToObjects('"+prefix+"','"+bNodeKey+"');");
		locationDiv.appendChild(countryInputElement);
		if(address[prefix+'Country']){
			countryInputElement.value = address[prefix+'Country'];
		}
	
	}
	
	
	
	/*--------------------------nearestAirport--------------------------*/

	function addNearestAirportMarker(nearestAirport,containerElement,map,isPublic){
		
		if(typeof(nearestAirport) == 'undefined' || !nearestAirport){
			return;
		}
		if(typeof(containerElement) == 'undefined' || !containerElement){
			return;
		}
		if(typeof(map) == 'undefined' || !map){
			return;
		}

		var geocoder = new GClientGeocoder();
		var icaoCode = '';
		var iataCode = '';
		
		if(nearestAirport['icaoCode']){
			icaoCode = nearestAirport['icaoCode'];		
		} 
		if(nearestAirport['iataCode']){
			iataCode = nearestAirport['iataCode'];
		}
		
		/*only add it if either there is info there or if this is the last go*/
		//XXX this relies on the method being called with isPublic at the start
		if(icaoCode || iataCode || isPublic){
			createGenericInputElementPrivacyBox('nearestAirport', containerElement.id,!isPublic);
			createAirportDiv(iataCode,icaoCode,containerElement);
		}
	}

	/*gets the already rendered autocomplete div and attaches it to the container given*/
	function displayAndAttachAirportAutocompleteDiv(container){
	
		/*get the already rendered autocomplete div*/
		if(!autocomplete_autocompleteDiv){
			autocomplete_autocompleteDiv = document.getElementById('airport_autocomplete');
			/*remove it from its current location and reattach it to our container*/
			autocomplete_autocompleteDiv.parentNode.removeChild(autocomplete_autocompleteDiv);
		}
		
		container.appendChild(autocomplete_autocompleteDiv);
		
		/*make it display*/
		autocomplete_autocompleteDiv.style.display = 'inline';
	}

	function createAirportDiv(iataCode,icaoCode,containerElement){
		var locationDiv = createLocationElement(containerElement, "nearestAirport",false,true);
		
		/*display the latitude and longitude coords and the codes for the airport*/
		var latitudeDiv = document.createElement('div');
		latitudeDiv.appendChild(document.createTextNode('Latitude: '));
		latitudeDiv.className = 'latitude';
		latitudeDiv.id = 'latitude_nearestAirport';
		locationDiv.appendChild(latitudeDiv);
			
		var longitudeDiv = document.createElement('div');
		longitudeDiv.appendChild(document.createTextNode('Longitude: '));
		longitudeDiv.className = 'longitude';
		longitudeDiv.id = 'longitude_nearestAirport';
		locationDiv.appendChild(longitudeDiv);
		
		/*displays the autocomplete field for the locationdiv*/
		displayAndAttachAirportAutocompleteDiv(locationDiv);
	
		//icao code
		var icaoLabelElement = document.createElement('div');
		icaoLabelElement.appendChild(document.createTextNode('ICAO Code: '+icaoCode));
		icaoLabelElement.className = 'icaoCode';
		icaoLabelElement.id = 'icaoCode';
		locationDiv.appendChild(icaoLabelElement);
		
		//iata code
		var iataLabelElement = document.createElement('div');
		iataLabelElement.appendChild(document.createTextNode('IATA Code: '+iataCode));
		iataLabelElement.className = 'iataCode';
		iataLabelElement.id = 'iataCode';
		locationDiv.appendChild(iataLabelElement);
		
		//draw the marker
		var geocoder = new GClientGeocoder();
		if(icaoCode){
			geocoder.getLatLng(icaoCode,geoCodeNearestAirport);
		} else if(iataCode){
			geocoder.getLatLng(iataCode,geoCodeNearestAirport);
		} 
	}

	/*--------------------------basedNear--------------------------*/

	/*add markers for all the foaf:based_near elements*/
	function addBasedNearMarkers(basedNearArray, containerElement,map,isPublic){
		if(typeof(isPublic) == 'undefined'){
			log('isPublic is undefined');
			return;
		}
		
		/*loop over each based_near instance*/
		for(bNodeKey in basedNearArray){			
			createSingleBasedNearMarker(containerElement.id, bNodeKey, basedNearArray[bNodeKey],isPublic);	
		}
		//XXX we only want to render one of these.  This depends on the public stuff being rendered last.
		if(isPublic){
			log('creating Add element '+isPublic);
			createBasedNearAddElement(containerElement);
		}
	}

	function createSingleBasedNearMarker(containerElementId, bNodeKey, basedNearValue,isPublic){
			
			var containerElement = document.getElementById(containerElementId);
			/*create an element to hold each location*/
			var locationDiv = createLocationElement(containerElement, bNodeKey);
			
			/*create the radiobutton for privacy*/
			createGenericInputElementPrivacyBox(bNodeKey,bNodeKey,!isPublic)
			
			/*title: e.g. home address, office address etc*/
			var basedNearTitleDiv = document.createElement('div');
			var title = 'I\'m Based Near...';
			basedNearTitleDiv.className = 'addressTitle';
			basedNearTitleDiv.appendChild(document.createTextNode(title));
			locationDiv.appendChild(basedNearTitleDiv);
		
			var latitude = basedNearValue['latitude'];
			var longitude = basedNearValue['longitude'];
			
			/*display the latitude and longitude coords*/
			var latitudeDiv = document.createElement('div');
			var longitudeDiv = document.createElement('div');
			latitudeDiv.id = 'latitude_'+locationDiv.id;
			latitudeDiv.className='latitude';
			longitudeDiv.id = 'longitude_'+locationDiv.id;
			longitudeDiv.className='longitude';
			locationDiv.appendChild(latitudeDiv);
			locationDiv.appendChild(longitudeDiv);
			latitudeDiv.appendChild(document.createTextNode('Latitude: '+latitude));
			longitudeDiv.appendChild(document.createTextNode('Longitude: '+longitude));
			
			var holderName = locationDiv.id;
			
			var geocoder = new GClientGeocoder()
			geocoder.getLocations(new GLatLng(latitude,longitude), updateBasedNearAddress)
			
			var point = new GLatLng(latitude,longitude);
			var marker = new GMarker(point,{draggable: true,title:'Near me'});
			map.addOverlay(marker);
			mapMarkers[holderName] = marker
			
		  	/*every time the marker is dropped, save the foaf and rerverse geocode to get the placename*/
		  	//set some variables
		  	var theseDetails = new Array();
		  	theseDetails['containerId'] = holderName;
		  	basedNearDetailsToGeoCode.push(theseDetails);
		  	
			GEvent.addListener(marker, "dragend", function(marker) {
					/*save the foaf*/
			    	saveFoaf();
			    	var theseDetails = new Array();
		  			theseDetails['containerId'] = holderName;
			       	basedNearDetailsToGeoCode.push(theseDetails);
			        geocoder.getLocations(this.getLatLng(), updateBasedNearAddress)
			      });
			      		
			
			/*keep the latitude and longitude updated when the marker is dragged around*/
		    GEvent.addListener(marker, 'drag', function(){
				updateLatLongText(holderName,marker);
		    	
		    });
		    
		    //TODO: have some sort of sensible thing like this
		    map.setCenter(point,1);	
	}
	
	
	
	
		
/*--------------------------main display to objects function, gets info from HTML elements and stuffs it into the globalFieldData--------------------------*/	

/*populates the triples objects with stuff from the actual display (i.e. what the user has changed)*/
//TODO: datatypes/language
function displayToObjects(name){  
	switch(name){
		case 'load-the-basics':
			//XXX: these are only commented out for development purposes
			birthdayDisplayToObjects();
			simpleFieldsDisplayToObjects();
			break;
		case 'load-contact-details':
			addressDisplayToObjects();
			mboxDisplayToObjects();
			phoneDisplayToObjects();
			break;
		case 'load-accounts':
			accountsDisplayToObjects();
			homepageDisplayToObjects();
			blogsDisplayToObjects();
			break;
		case 'load-locations':
			nearestAirportDisplayToObjects();
			basedNearDisplayToObjects();
			break;
		case 'load-pictures':
			depictionDisplayToObjects();
			imgDisplayToObjects();
			break;
		case 'load-friends':
			knowsDisplayToObjects();
			break;
		case 'load-other':
			otherDisplayToObjects();
			break;
		default:
			return null;
			break;
	}
}

/*--------------------------second level display to objects functions--------------------------*/

function birthdayDisplayToObjects(){
	
	var foafBirthdayFields = new Object();
	var privacyCheckbox = document.getElementById('privacycheckbox_foafBirthday_container');	
	if(typeof(privacyCheckbox) == 'undefined' || !privacyCheckbox){
		log('couldnt find birthday fields checkbox');
		return;
	}
	
	if(document.getElementById('yearDropdown').value){
		foafBirthdayFields['year'] = document.getElementById('yearDropdown').value; 
	} 
	if(document.getElementById('monthDropdown').value){
		foafBirthdayFields['month'] = document.getElementById('monthDropdown').value; 
	}
	if(document.getElementById('dayDropdown').value){
		foafBirthdayFields['day'] = document.getElementById('dayDropdown').value; 
	}
	
	if(privacyCheckbox.checked){
		log('saving checked');
		globalPrivateFieldData.foafBirthdayFields = foafBirthdayFields;
	} else {
		log('saving unchecked');
		globalFieldData.foafBirthdayFields = foafBirthdayFields;
	}
	
}

function blogsDisplayToObjects() {
	log('in weblog display to objects');
	var containerElement = document.getElementById('foafWeblog_container');
	
	if(!containerElement){
		return
	}
	if(typeof(globalFieldData.foafWeblogFields) == 'undefined' || !globalFieldData.foafWeblogFields){
		log('homepages - returning');
		return;
	}
	if(typeof(globalPrivateFieldData.foafWeblogFields) == 'undefined' || !globalPrivateFieldData.foafWeblogFields){
		log('homepages - returning');
		return;
	}
	
	/*remove the existing values*/
	globalFieldData.foafWeblogFields.values = new Array();
	globalPrivateFieldData.foafWeblogFields.values = new Array();
		
	/*add the elements that are present in the display again*/
	for(i=0 ; i <containerElement.childNodes.length ; i++){
		
		log('in for loop for homepages');
		
		var element = containerElement.childNodes[i];
					
		//we only want input elements
		if(element.className != 'fieldInput'){	
			continue;
		}
		
		var privacyBox = document.getElementById('privacycheckbox_'+element.id);
		
		/*no privacy checkbox, so skip to next childNode*/
		if(typeof(privacyBox) == 'undefined' || !privacyBox){
			log("Privacy box not found for homepage");
			continue;
		}	
		
		/*put it into the appropriate field data object, private or not private*/
		if(!privacyBox.checked){	
			globalFieldData.foafWeblogFields.values.push(element.value);
		} else {
			globalPrivateFieldData.foafWeblogFields.values.push(element.value);
		}		
	}
}

function homepageDisplayToObjects() {
	log('in homepage display to objects');
	var containerElement = document.getElementById('foafHomepage_container');
	
	if(!containerElement){
		return
	}
	if(typeof(globalFieldData.foafHomepageFields) == 'undefined' || !globalFieldData.foafHomepageFields){
		log('homepages - returning');
		return;
	}
	if(typeof(globalPrivateFieldData.foafHomepageFields) == 'undefined' || !globalPrivateFieldData.foafHomepageFields){
		log('homepages - returning');
		return;
	}
	
	/*remove the existing values*/
	globalFieldData.foafHomepageFields.values = new Array();
	globalPrivateFieldData.foafHomepageFields.values = new Array();
		
	/*add the elements that are present in the display again*/
	for(i=0 ; i <containerElement.childNodes.length ; i++){
		
		log('in for loop for homepages');
		
		var element = containerElement.childNodes[i];
					
		//we only want input elements
		if(element.className != 'fieldInput'){	
			continue;
		}
		
		var privacyBox = document.getElementById('privacycheckbox_'+element.id);
		
		/*no privacy checkbox, so skip to next childNode*/
		if(typeof(privacyBox) == 'undefined' || !privacyBox){
			log("Privacy box not found for homepage");
			continue;
		}	
		
		/*put it into the appropriate field data object, private or not private*/
		if(!privacyBox.checked){	
			globalFieldData.foafHomepageFields.values.push(element.value);
		} else {
			globalPrivateFieldData.foafHomepageFields.values.push(element.value);
		}		
	}
}

function addressDisplayToObjects(){
	
	/*some initial checks*/
	if(typeof(globalFieldData.addressFields) == 'undefined' || !globalFieldData.addressFields){
		return;
	}
	if(typeof(globalPrivateFieldData.addressFields) == 'undefined' || !globalPrivateFieldData.addressFields){
		return;
	}
	if(typeof(globalFieldData) == 'undefined' || !globalFieldData
		||typeof(globalPrivateFieldData) == 'undefined' || !globalPrivateFieldData){		
		return
	}
	
	/*clear out the existing 'home' and 'office' properties*/
	globalFieldData.addressFields['home'] = new Object();
	globalFieldData.addressFields['office'] = new Object();
	globalPrivateFieldData.addressFields['office'] = new Object();
	globalPrivateFieldData.addressFields['home'] = new Object();
	
	/*populate the appropriate objects*/
	placeAddressDisplayToObjects('home');
	placeAddressDisplayToObjects('office');
}


function simpleFieldsDisplayToObjects(){
	
	if(typeof(globalFieldData.fields) == 'undefined' || !globalFieldData.fields){
		return;
	}
	if(typeof(globalPrivateFieldData.fields) == 'undefined' || !globalPrivateFieldData.fields){
		return;
	}
	
	/*loop through the different kinds of fields*/
	for(simpleField in globalFieldData.fields){

		var containerElement = document.getElementById(simpleField + "_container");
		
		if(!containerElement){
			continue;
		}
		
		/*remove all existing elements in globalFieldData and globalPrivateFieldData*/
		globalFieldData.fields[simpleField] = new Object();
		globalFieldData.fields[simpleField].values = new Array();
		
		globalPrivateFieldData.fields[simpleField] = new Object();
		globalPrivateFieldData.fields[simpleField].values = new Array();
			
		/*add the elements that are present in the display to the appropriate object*/
		for(i=0 ; i <containerElement.childNodes.length ; i++){

			var element = containerElement.childNodes[i];	
							
			/*if it isn't an input field then skip it (it could be a remove link or something else)*/
			if(element.className != 'fieldInput' || element.value == null){
				continue;
			} 
			
			var privacyBox = document.getElementById('privacycheckbox_'+element.id);
			
			/*no privacy checkbox, so skip to next childNode*/
			if(typeof(privacyBox) == 'undefined' || !privacyBox){
				continue;
			}
			
			/*put it into the appropriate field data object, private or not private*/
			if(!privacyBox.checked){	
				globalFieldData.fields[simpleField].values.push(element.value);
			} else {
				globalPrivateFieldData.fields[simpleField].values.push(element.value);
			}
			
		}
	}
	
}

function mboxDisplayToObjects(){
	var containerElement = document.getElementById('foafMbox_container');
	
	if(!containerElement){
		return
	}
	if(typeof(globalFieldData.foafMboxFields) == 'undefined' || !globalFieldData.foafMboxFields){
		return;
	}
	if(typeof(globalPrivateFieldData.foafMboxFields) == 'undefined' || !globalPrivateFieldData.foafMboxFields){
		return;
	}
	
	/*remove the existing values*/
	globalFieldData.foafMboxFields.values = new Array();
	globalPrivateFieldData.foafMboxFields.values = new Array();
		
	/*add the elements that are present in the display again*/
	for(i=0 ; i <containerElement.childNodes.length ; i++){
				
		var element = containerElement.childNodes[i];
					
		//we only want input elements
		if(element.className != 'fieldInput'){	
			continue;
		}
		
		var privacyBox = document.getElementById('privacycheckbox_'+element.id);
		
		/*no privacy checkbox, so skip to next childNode*/
		if(typeof(privacyBox) == 'undefined' || !privacyBox){
			continue;
		}	
		
		/*put it into the appropriate field data object, private or not private*/
		if(!privacyBox.checked){	
			globalFieldData.foafMboxFields.values.push(element.value);
		} else {
			globalPrivateFieldData.foafMboxFields.values.push(element.value);
		}		
	}
}

function accountsDisplayToObjects(){

	//XXX we no longer preserve things across models because of the privacy implementation.
	log('in accounts display to objects');	
	
	/*get the countainer of all the accounts*/
	var containerElement = document.getElementById('foafHoldsAccount_container');
	
	if(!containerElement){
		log('[ACCOUNTS] no container elem');
		return
	}

	/*reset the accounts bit of the globalPrivateFieldData/globalFieldData object*/
        globalPrivateFieldData.foafHoldsAccountFields = new Object();
        globalFieldData.foafHoldsAccountFields = new Object();

 	
  	for(i=0; i < containerElement.childNodes.length; i++){
  		
  		var holdsAccountElement = containerElement.childNodes[i];
  		
  		if(typeof(holdsAccountElement.id) == 'undefined' || !holdsAccountElement.id){
  			log('ACCOUNTS no id');
  			continue;
  		}
  		
  		var bNodeId = holdsAccountElement.id;
  		
  		
  		/*ignore all elements that don't contain accounts (such as add/remove links)*/
  		if(holdsAccountElement.className != "holdsAccount"){	
  			log('ACOUNTS wrong classname');
  			continue;
  		}
  		
  		/*get the privacy checkbox*/
  		var privacyBox = document.getElementById('privacycheckbox_'+bNodeId);
  		if(typeof(privacyBox) == 'undefined' || !privacyBox){
  			log('ACCOUNTS no privacy box');
  			continue;
  		}
		/*will hold the data for just this account*/	
		thisAccount = new Object();
		
		/*loop through the child nodes of this box and add to the appropriate place*/	
  		for(k=0; k < containerElement.childNodes[i].childNodes.length; k++){
  			
  			if(typeof(holdsAccountElement) == 'undefined' || 
				!holdsAccountElement.childNodes[k].value || 
				holdsAccountElement.childNodes[k].value == ''){
  				
				log('ACCOUNTS empty value');
  				//don't save if there is no value here
  				continue;
  			}	
		
			if(holdsAccountElement.childNodes[k].style.display == 'none'){
				continue;
			}
  			
	  		//do the right thing for the right element, and miss any elements we don't care about.
	  		if (holdsAccountElement.childNodes[k].className == 'accountUsername'){
	  			
	  			thisAccount['foafAccountName'] = holdsAccountElement.childNodes[k].value;
	  					log('saving account name');
	  		} else if(holdsAccountElement.childNodes[k].className == 'accountProfile'){
	  			
	  			thisAccount['foafAccountProfilePage'] = holdsAccountElement.childNodes[k].value;
	  				log('saving foaf account profile page');
	  		} else if (holdsAccountElement.childNodes[k].className == 'accountTypeSelect' || holdsAccountElement.childNodes[k].className == 'accountTypeInput'){		
	  				log('saving account service homepage');
	  			thisAccount['foafAccountServiceHomepage'] = holdsAccountElement.childNodes[k].value;
	  		} 	
	  	} 	
	  	
	  	/*add to the appropriate global data object*/
  		if(privacyBox.checked){
  			log('SAVING private ACCOUNT INFO');
  			globalPrivateFieldData.foafHoldsAccountFields[bNodeId] = thisAccount;
  		} else {
  			log('SAVING public ACCOUNT INFO');
			globalFieldData.foafHoldsAccountFields[bNodeId] = thisAccount;
		} 
  	} 
}

/*put nearestAirport data into the globalFieldData objects*/
function nearestAirportDisplayToObjects(){
	
	log('started nearest airport display to objects');
	/*get the required elements*/
	var nearestAirport = document.getElementById('nearestAirport');
	var icaoCodeElem = document.getElementById('icaoCode');
	var iataCodeElem = document.getElementById('iataCode');
	var privacyCheckbox = document.getElementById('privacycheckbox_nearestAirport');
	
	/*do some initial checks*/
	if(!nearestAirport || typeof(nearestAirport)=='undefined'){
		return;
	}
	if(typeof(icaoCodeElem) == 'undefined' || !icaoCodeElem){
		return;
	}
	if(typeof(iataCodeElem) == 'undefined' || !iataCodeElem || 
		typeof(iataCodeElem.childNodes[0]) == 'undefined' || 
		!iataCodeElem.childNodes[0] ||
		typeof(icaoCodeElem.childNodes[0].nodeValue) == 'undefined' || 
		!icaoCodeElem.childNodes[0]){
		return;
	}
	if(typeof(icaoCodeElem.childNodes[0].nodeValue) == 'undefined' 
		|| !icaoCodeElem.childNodes[0].nodeValue){
		return;	
	}
	if(typeof(iataCodeElem.childNodes[0].nodeValue) == 'undefined' 
		|| !iataCodeElem.childNodes[0].nodeValue){
		return;	
	}
	if(typeof(privacyCheckbox) == 'undefined' || !privacyCheckbox){
		return;
	}
	log('got past the five billion nearest airport checks');
	/*get the codes from the text*/
	var icaoCode = icaoCodeElem.childNodes[0].nodeValue.replace('ICAO Code: ','');
	var iataCode = iataCodeElem.childNodes[0].nodeValue.replace('IATA Code: ','');
		
	/*create the nearest airport fields object*/
	thisAirport = new Object();
	if(iataCode && iataCode!=''){
		thisAirport['iataCode'] = iataCode;
	}
	if(icaoCode && icaoCode!=''){
		thisAirport['icaoCode'] = icaoCode;
	}
	
	/*add the object to either the public or the private global data object and empty the info from the other one*/
	if(privacyCheckbox.checked){
		globalFieldData.nearestAirportFields.nearestAirport = new Object();
		globalPrivateFieldData.nearestAirportFields.nearestAirport = thisAirport;
	} else {
		globalPrivateFieldData.nearestAirportFields.nearestAirport = new Object();
		globalFieldData.nearestAirportFields.nearestAirport = thisAirport;
	}
}	

/*put basedNear data into the globalFieldData objects*/
function basedNearDisplayToObjects(){

	var containerElement = document.getElementById('basedNear_container');
	
	if(typeof(containerElement) == 'undefined' || !containerElement){
		log('no container for based near');
		return;
	}	

	//clear out the existing data
	globalFieldData.basedNearFields['basedNear'] = new Object();
	globalPrivateFieldData.basedNearFields['basedNear'] = new Object();
	
	
	//go through each of the container elements
	for(i=0; i < containerElement.childNodes.length; i++){
	
		var locationElement = containerElement.childNodes[i];
		
		if(typeof(locationElement) == 'undefined' || !locationElement || locationElement.id =='mapDiv'){
			log('map div found instead of location div');
			continue;
		}
		
		//loop through the elements to make sure we save the right ones
		for(j=0; j < locationElement.childNodes.length; j++){
				
			if(typeof(locationElement.childNodes[j].className)=='undefined' 
				|| !locationElement.childNodes[j].className){
				log('no classname in div');
				continue;
			}
			if(locationElement.childNodes[j].className != 'latitude' 
				&& locationElement.childNodes[j].className != 'longitude'){
				log('div found with className not equal to latitude or longitude');
				continue;	
			}
			if(typeof(locationElement.childNodes[j].childNodes[0]) == 'undefined' 
				|| !locationElement.childNodes[j].childNodes[0] 
				|| typeof(locationElement.childNodes[j].childNodes[0].nodeValue) == 'undefined' 
				|| !locationElement.childNodes[j].childNodes[0].nodeValue){
				log('no node value for this field');
				continue;
			}
			
			//get the coordinates from this element	
			var coordArray = locationElement.childNodes[j].childNodes[0].nodeValue.split(' ');
			
			if(typeof(coordArray) == 'undefined' || !coordArray ||
				typeof(coordArray[1]) == 'undefined' && !coordArray[1]){
				log('coord array is empty');
				continue;
			}
		
			//get the privacy checkbox
			var privacyCheckbox = document.getElementById('privacycheckbox_'+locationElement.id);
			
			if(typeof(privacyCheckbox) == 'undefined' || !privacyCheckbox){
				log('privacy checkbox not found or undefined '+locationElement.id);
				continue
			}
			log("GOT TO HERE "+locationElement.id+" elemclass: "+locationElement.childNodes[j].className);
			
			//add to different global object depending if public or private
			if(privacyCheckbox.checked){
				//create a new object for this bnode (locationElement.id) if required
				if(typeof(globalPrivateFieldData.basedNearFields['basedNear'][locationElement.id]) == 'undefined' 
					|| !globalPrivateFieldData.basedNearFields['basedNear'][locationElement.id]){
					globalPrivateFieldData.basedNearFields['basedNear'][locationElement.id] = new Object();
				}
						
				globalPrivateFieldData.basedNearFields['basedNear'][locationElement.id][locationElement.childNodes[j].className] = coordArray[1];
				globalPrivateFieldData.basedNearFields['basedNear'][locationElement.id][locationElement.childNodes[j].className] = coordArray[1];
			
			} else {
				//create a new object for this bnode (locationElement.id) if required
				if(typeof(globalFieldData.basedNearFields['basedNear'][locationElement.id]) == 'undefined' 
					|| !globalFieldData.basedNearFields['basedNear'][locationElement.id]){
					globalFieldData.basedNearFields['basedNear'][locationElement.id] = new Object();
				}
						
				globalFieldData.basedNearFields['basedNear'][locationElement.id][locationElement.childNodes[j].className] = coordArray[1];
				globalFieldData.basedNearFields['basedNear'][locationElement.id][locationElement.childNodes[j].className] = coordArray[1];
			
			}
		}
	}
}

/*this is more or less identical to imgDisplayToObjects which is possibly not a good thing*/
function depictionDisplayToObjects(){
	log("saving depictions");
	/*the container of the images*/
	var containerElement = document.getElementById('foafDepiction_container');
	
	if(!containerElement){
		log("['depictionDisplayToObjects'] couldn't get container element");
		return;
	}
	if(typeof(globalFieldData.foafDepictionFields) == 'undefined'
		|| !globalFieldData.foafDepictionFields
		|| typeof(globalFieldData.foafDepictionFields.images) == 'undefined'
		|| !globalFieldData.foafDepictionFields.images){
		log("['depictionDisplayToObjects'] globalField data (public) img setup incorrect");
		return;
	}
	if(typeof(globalPrivateFieldData.foafDepictionFields) == 'undefined'
		|| !globalPrivateFieldData.foafDepictionFields
		|| typeof(globalPrivateFieldData.foafDepictionFields.images) == 'undefined'
		|| !globalPrivateFieldData.foafDepictionFields.images){
		log("['depictionDisplayToObjects'] globalField data (private) img setup incorrect");
		return;
	}

	/*remove all existing images in globalFieldData*/		
	globalFieldData.foafDepictionFields.images = new Array();
	globalPrivateFieldData.foafDepictionFields.images = new Array();
			
	/*add the elements that are present in the display again*/
	for(i=0 ; i <containerElement.childNodes.length ; i++){
		
		/*the image element*/
		var element = containerElement.childNodes[i];				
		if(element.className != 'image' || typeof(element.id)=='undefined' || !element.id){
			continue;
		}	
	
		/*the privacy checkbox*/
		var privacyCheckbox = document.getElementById('privacycheckbox_'+element.id);		
		if(typeof(privacyCheckbox) == 'undefined' || !privacyCheckbox){
			continue;
		}
		
		/*take the various attributes of the image tag and add them to the globalFieldData object*/		
		var thisImageArray = new Object();
									
		if(typeof(element.src) != 'undefined' && element.src){
			thisImageArray.uri = element.src;
		}
		if(typeof(element.title) != 'undefined' && element.title){
			thisImageArray.title= element.title;
		}
		if(typeof(element.alt) != 'undefinied' && element.alt){
			thisImageArray.description = element.alt;
		}
		
		/*add to the appropriate global object depending on whether it is private or publi*/
		if(!privacyCheckbox.checked){
			globalFieldData.foafDepictionFields.images.push(thisImageArray);
		} else {
			globalPrivateFieldData.foafDepictionFields.images.push(thisImageArray);
		}
	}
}


function mboxDisplayToObjects(){
	var containerElement = document.getElementById('foafMbox_container');
	
	if(!containerElement){
		return
	}
	if(typeof(globalFieldData.foafMboxFields) == 'undefined' || !globalFieldData.foafMboxFields){
		return;
	}
	if(typeof(globalPrivateFieldData.foafMboxFields) == 'undefined' || !globalPrivateFieldData.foafMboxFields){
		return;
	}
	
	/*remove the existing values*/
	globalFieldData.foafMboxFields.values = new Array();
	globalPrivateFieldData.foafMboxFields.values = new Array();
		
	/*add the elements that are present in the display again*/
	for(i=0 ; i <containerElement.childNodes.length ; i++){
				
		var element = containerElement.childNodes[i];
					
		//we only want input elements
		if(element.className != 'fieldInput'){	
			continue;
		}
		
		var privacyBox = document.getElementById('privacycheckbox_'+element.id);
		
		/*no privacy checkbox, so skip to next childNode*/
		if(typeof(privacyBox) == 'undefined' || !privacyBox){
			continue;
		}	
		
		/*put it into the appropriate field data object, private or not private*/
		if(!privacyBox.checked){	
			globalFieldData.foafMboxFields.values.push(element.value);
		} else {
			globalPrivateFieldData.foafMboxFields.values.push(element.value);
		}		
	}
}

function imgDisplayToObjects(){

	/*the container of the images*/
	var containerElement = document.getElementById('foafImg_container');
	
	if(!containerElement){
		log("['imgDisplayToObjects'] couldn't get container element");
		return;
	}
	if(typeof(globalFieldData.foafImgFields) == 'undefined'
		|| !globalFieldData.foafImgFields
		|| typeof(globalFieldData.foafImgFields.images) == 'undefined'
		|| !globalFieldData.foafImgFields.images){
		log("['imgDisplayToObjects'] globalField data (public) img setup incorrect");
		return;
	}
	if(typeof(globalPrivateFieldData.foafImgFields) == 'undefined'
		|| !globalPrivateFieldData.foafImgFields
		|| typeof(globalPrivateFieldData.foafImgFields.images) == 'undefined'
		|| !globalPrivateFieldData.foafImgFields.images){
		log("['imgDisplayToObjects'] globalField data (private) img setup incorrect");
		return;
	}

	/*remove all existing images in globalFieldData*/		
	globalFieldData.foafImgFields.images = new Array();
	globalPrivateFieldData.foafImgFields.images = new Array();
			
	/*add the elements that are present in the display again*/
	for(i=0 ; i <containerElement.childNodes.length ; i++){
		
		/*the image element*/
		var element = containerElement.childNodes[i];				
		if(element.className != 'image' || typeof(element.id)=='undefined' || !element.id){
			continue;
		}	
	
		/*the privacy checkbox*/
		var privacyCheckbox = document.getElementById('privacycheckbox_'+element.id);		
		if(typeof(privacyCheckbox) == 'undefined' || !privacyCheckbox){
			continue;
		}
		
		/*take the various attributes of the image tag and add them to the globalFieldData object*/		
		var thisImageArray = new Object();
									
		if(typeof(element.src) != 'undefined' && element.src){
			thisImageArray.uri = element.src;
		}
		if(typeof(element.title) != 'undefined' && element.title){
			thisImageArray.title= element.title;
		}
		if(typeof(element.alt) != 'undefinied' && element.alt){
			thisImageArray.description = element.alt;
		}
		
		/*add to the appropriate global object depending on whether it is private or publi*/
		if(!privacyCheckbox.checked){
			globalFieldData.foafImgFields.images.push(thisImageArray);
		} else {
			globalPrivateFieldData.foafImgFields.images.push(thisImageArray);
		}
	}
}

function knowsDisplayToObjects(){

	if(typeof(globalFieldData) == 'undefined' || !globalFieldData){
		return;
	}
	if(typeof(globalFieldData.foafKnowsFields) == 'undefined' || !globalFieldData.foafKnowsFields){
		return;
	}
	
	//for use later in this function	
	var newFoafKnowsFields = new Object();
	newFoafKnowsFields.mutualFriends = new Array();
	newFoafKnowsFields.userKnows = new Array();
	newFoafKnowsFields.knowsUser = globalFieldData.foafKnowsFields.knowsUser;
	
	/*Save all mutual friends*/
	var mutualFriendsContainer = document.getElementById('mutualFriends_container');	
	for(childNode in mutualFriendsContainer.childNodes){
		if(mutualFriendsContainer.childNodes[childNode].className == 'friend'){
			var friendInfo = getFriendInfoFromElement(mutualFriendsContainer.childNodes[childNode].id);
			
			/*do some stuff to make sure the ifp type is there for any new person that we've added*/
			if(typeof(globalTypeArray[friendInfo.ifps[0]]) != 'undefined' && globalTypeArray[friendInfo.ifps[0]]){
				friendInfo.ifp_type = globalTypeArray[friendInfo.ifps[0]];
			} 
			
			//TODO: do some stuff here to get the rest of the ifps from the global data object
			friendInfo.ifps = getIFPsFromGlobalDataObject(friendInfo);
			//alert(friendInfo.ifps);
			newFoafKnowsFields.mutualFriends.push(friendInfo);
		} 
	}
	
	/*Save all user knows*/
	var userKnowsContainer = document.getElementById('userKnows_container');	
	for(childNode in userKnowsContainer.childNodes){
		if(typeof(userKnowsContainer.childNodes[childNode]) != 'undefined' && userKnowsContainer.childNodes[childNode].className == 'friend'){
			var friendInfo = getFriendInfoFromElement(userKnowsContainer.childNodes[childNode].id);
			
			/*do some stuff to make sure the ifp type is there for any new person that we've added*/
			if(typeof(globalTypeArray[friendInfo.ifps[0]]) != 'undefined' && globalTypeArray[friendInfo.ifps[0]]){
				friendInfo.ifp_type = globalTypeArray[friendInfo.ifps[0]];
			} 
			
			//TODO: do some stuff here to get the rest of the ifps from the global data object
			friendInfo.ifps = getIFPsFromGlobalDataObject(friendInfo);
			newFoafKnowsFields.userKnows.push(friendInfo);
		} 
	}	
	
	/*replace the original foaf knows fields stuff*/
	globalFieldData.foafKnowsFields = newFoafKnowsFields;
	
}

/*get all ifps from the single one passed in by friendInfo */
function getIFPsFromGlobalDataObject(friendInfo){
	
	var ifps = friendInfo.ifps;
	
	for(person in globalFieldData.foafKnowsFields.knowsUser){
		for(ifp in globalFieldData.foafKnowsFields.knowsUser[person].ifps){
				if(globalFieldData.foafKnowsFields.knowsUser[person].ifps[ifp] == friendInfo.ifps[0]){			
					ifps = globalFieldData.foafKnowsFields.knowsUser[person].ifps;
					break;
				}
		}
	}
	
	for(person in globalFieldData.foafKnowsFields.userKnows){
		for(ifp in globalFieldData.foafKnowsFields.userKnows[person].ifps){
				if(globalFieldData.foafKnowsFields.userKnows[person].ifps[ifp] == friendInfo.ifps[0]){			
					ifps = globalFieldData.foafKnowsFields.userKnows[person].ifps;
					break;
				}
		}
	}
	
	for(person in globalFieldData.foafKnowsFields.mutualFriends){
		for(ifp in globalFieldData.foafKnowsFields.mutualFriends[person].ifps){
				if(globalFieldData.foafKnowsFields.mutualFriends[person].ifps[ifp] == friendInfo.ifps[0]){			
					ifps = globalFieldData.foafKnowsFields.mutualFriends[person].ifps;
					break;
				}
		}
	}

	return ifps;
}

function otherDisplayToObjects(){
	//TODO: do this
}




//XXX this is vvv similar to Mbox display to objects
function phoneDisplayToObjects(){
	var containerElement = document.getElementById('foafPhone_container');
	
	if(!containerElement){
		return
	}
	if(typeof(globalFieldData.foafPhoneFields) == 'undefined' || !globalFieldData.foafPhoneFields){
		return;
	}
	if(typeof(globalPrivateFieldData.foafPhoneFields) == 'undefined' || !globalPrivateFieldData.foafPhoneFields){
		return;
	}
	
	/*remove the existing values*/
	globalFieldData.foafPhoneFields.values = new Array();
	globalPrivateFieldData.foafPhoneFields.values = new Array();
		
	/*add the elements that are present in the display again*/
	for(i=0 ; i <containerElement.childNodes.length ; i++){
				
		var element = containerElement.childNodes[i];
					
		//we only want input elements
		if(element.className != 'fieldInput'){	
			continue;
		}
		
		var privacyBox = document.getElementById('privacycheckbox_'+element.id);
		
		/*no privacy checkbox, so skip to next childNode*/
		if(typeof(privacyBox) == 'undefined' || !privacyBox){
			continue;
		}	
		
		/*put it into the appropriate field data object, private or not private*/
		if(!privacyBox.checked){	
			globalFieldData.foafPhoneFields.values.push(element.value);
		} else {
			globalPrivateFieldData.foafPhoneFields.values.push(element.value);
		}		
	}
}

/*--------------------------second level display To objects functions --------------------------*/

	/*---------------------------address---------------------------*/
	
	
	/*copies values from display for an address of type prefix (e.g. office, home) into the globalFieldData object
	bNodeToPanTo specifies the bnode that the map should pan to, if any.*/
	function placeAddressDisplayToObjects(prefix,bNodeToPanTo){
		
	   	/*get a new container and do some initial checks*/
	   	var containerElement = document.getElementById('address_container');
	   	
	   	if(!containerElement){
	   		return;
	   	}
	   	if(typeof(prefix) == 'undefined' || !prefix){
	   		return;
	   	} 
	   	if(typeof(globalFieldData.addressFields) == 'undefined' || !globalFieldData.addressFields[prefix]){
	   		return;
	   	}
 		if(typeof(globalPrivateFieldData.addressFields) == 'undefined' || !globalPrivateFieldData.addressFields[prefix]){
	   		return;
	   	}
		
		/*loop through the container looking for a home or address one*/
		for(i=0; i < containerElement.childNodes.length; i++){
			
			/*we only care about certain locations*/
			var locationElement = containerElement.childNodes[i];
			if(locationElement.className != prefix+'Address'){
				continue;
			}
			if(!locationElement.id){
				continue;
			}
			
			/*get the privacy checkbox*/
			var privacyCheckbox = document.getElementById('privacycheckbox_'+locationElement.id);
			if(typeof(privacyCheckbox) == 'undefined' || !privacyCheckbox){
				continue;
			}
			
			var isPrivate = privacyCheckbox.checked;
			var thisAddressObject = new Object;		
			var isAddress = false;//stops us from saving if it is empty
			
			/*loop through the childnodes saving the various ones*/
			for(j=0; j < locationElement.childNodes.length; j++){
			
				/*address*/
				var elemId = locationElement.childNodes[j].id;
				
				if(elemId == 'street'){
					isAddress=true;
					thisAddressObject[prefix+'Street'] = locationElement.childNodes[j].value;
				} 
				if(elemId == 'street2'){
					isAddress=true;
					thisAddressObject[prefix+'Street2'] = locationElement.childNodes[j].value;
				} 
				if(elemId == 'street3'){
					isAddress=true;
					thisAddressObject[prefix+'Street3'] = locationElement.childNodes[j].value;
				} 
				if(elemId == 'postalCode'){
					isAddress=true;
					thisAddressObject[prefix+'PostalCode'] = locationElement.childNodes[j].value;
				} 
				if(elemId == 'city'){
					isAddress=true;
					thisAddressObject[prefix+'City'] = locationElement.childNodes[j].value;
				}	
				if(elemId == 'country'){
					isAddress=true;
					thisAddressObject[prefix+'Country'] = locationElement.childNodes[j].value;
				}
			}
				
			/*only save if we've at least got some information*/		
			if(isAddress){
			
				//put in the right place depending on the privacy checkbox
				if(isPrivate){
					globalPrivateFieldData.addressFields[prefix][locationElement.id] = thisAddressObject;
				} else {
					globalFieldData.addressFields[prefix][locationElement.id] = thisAddressObject;
				}
				
				//only pan the map if a bnode has been passed in to pan to
				var doPan = false;
				if(typeof(bNodeToPanTo) != 'undefined' && bNodeToPanTo && locationElement.id==bNodeToPanTo){
					doPan = true
				}
				//alert(isPrivate);
				//do the geo coding to get lat and long
				geoCodeExistingAddress(locationElement.id,prefix,doPan,isPrivate);
			}
		}
	}
	
	
	/*---------------------------friends---------------------------*/
	
	//XXX: perhaps this isn't the right place to put this?
	/*gets an array with img, name and ifp from a friend div*/
	function getFriendInfoFromElement(friendDivId){
	
		/*getTheExisting friend div, extract the information and add it to the knows you list*/
		///XXX perhaps should have done this by moving stuff around in globalFieldData and then doing displayToObjects or objectsToDisplay
		var friendDiv = document.getElementById(friendDivId);
		var img = null;
		var ifp = null;
		var name = null;
		
		for(childNode in friendDiv.childNodes){
			if(friendDiv.childNodes[childNode].className == 'friendImage' && typeof(friendDiv.childNodes[childNode].src) !='undefined'){
				img = friendDiv.childNodes[childNode].src;
			}
			if(friendDiv.childNodes[childNode].className == 'friendName'){
				for(grandChildNode in friendDiv.childNodes[childNode].childNodes){
	
					if(friendDiv.childNodes[childNode].childNodes[grandChildNode].tagName=='A' 
						&& typeof(friendDiv.childNodes[childNode].childNodes[grandChildNode].childNodes[0]) !='undefined'
						&& typeof(friendDiv.childNodes[childNode].childNodes[grandChildNode].childNodes[0].nodeValue) !='undefined'){
						
						name = friendDiv.childNodes[childNode].childNodes[grandChildNode].childNodes[0].nodeValue;
						
						if(typeof(friendDiv.childNodes[childNode].childNodes[grandChildNode].href) != 'undefined'){
							ifp = friendDiv.childNodes[childNode].childNodes[grandChildNode].href.replace("http://foaf.qdos.com/find/?q=","");
						}
					}
				}
			}
		}
		
		/*create an array with the information about the friend in it*/
		var friend=new Object();
		if(ifp){
			friend.ifps = new Array();
			//FIXME: we need to keep track of all of the ifps somehow
			friend['ifps'][0] = ifp;
		} 
		if(name){
			friend.name = name;
		}
		if(img){
			friend.img = img;
		}
		
		return friend;
		
	}
	

/*---------------------------element generators---------------------------*/

	/*---------------------------generic---------------------------*/

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
	}
	
	function createGenericAddElement(container,name,displayLabel){
	
		/*create add link and attach it to the container*/
		var addDiv = document.createElement("div");
		addDiv.id = name+"_addLinkContainer";
		addDiv.className = "addLinkContainer";
		var addLink = document.createElement('a');
		addLink = makeCursorAPointer(addLink);
		addLink.appendChild(document.createTextNode("+Add another "+displayLabel));
		addLink.className="addLink";
		
		addLink.setAttribute("onclick" , "createGenericInputElementAboveAddLink('"+name+"',this.parentNode.parentNode.childNodes.length,'"+container.id+"',this.parentNode.id,'"+displayLabel+"');");
		
		addDiv.appendChild(addLink);
		container.appendChild(addDiv);
	
	}
	//TODO: can we get rid of thisElementCount?
	function createGenericInputElementAboveAddLink(name,thisElementCount,containerId,addElementId,displayLabel){
		
		/*remove the add element*/
		var addElement = document.getElementById(addElementId);
		addElement.parentNode.removeChild(addElement);
		
		var value = '';
		/*append a child node*/
		if(displayLabel){
			value = 'Enter '+displayLabel+' here';
		}
		
		createGenericInputElement(name,value,thisElementCount,containerId,true);
		
		/*re add the add element*/
		document.getElementById(containerId).appendChild(addElement);
	}
	
	/*creates a remove link with the removeId being the input element to be removed*/
	function createGenericInputElementRemoveLink(removeId,containerId,isImage){
		
		/*create remove link and attach it to the container div*/
		var containerDiv = document.getElementById(containerId);
		if(containerDiv){
			var removeDiv = document.createElement("div");
			removeDiv.id = removeId+"removeLinkContainer";
			removeDiv.className = "removeLinkContainer";
			var removeLink = document.createElement('a');
			removeLink = makeCursorAPointer(removeLink);
			removeLink.appendChild(document.createTextNode("- Remove"));
			removeLink.id="_removeLink";
			removeLink.className="removeLink";
			if(!isImage){
				removeLink.setAttribute("onclick" , "removeGenericInputElement('"+removeId+"','"+removeDiv.id+"')");
			} else {
				removeLink.setAttribute("onclick" , "removeGenericInputElement('"+removeId+"','"+removeDiv.id+"','true')");
			}
			removeDiv.appendChild(removeLink);
			containerDiv.appendChild(removeDiv);
		}
	}
	
	/*creates a privacy checkbox for the particular element passed in, in the container passed in*/
	function createGenericInputElementPrivacyBox(elementId,containerId,isPrivate){
		/*create remove link and attach it to the container div*/
		var containerDiv = document.getElementById(containerId);
		if(containerDiv){
			var privacyDiv = document.createElement("div");
			privacyDiv.id = "privacydiv_"+elementId;
			privacyDiv.className = "privacyContainer";
			
			var privacyText = document.createTextNode('Private?');
			privacyDiv.appendChild(privacyText);
		
			var lineBreak = document.createElement("br");
			privacyDiv.appendChild(lineBreak);
			
			var privacyCheckbox = document.createElement('input');
			privacyCheckbox.setAttribute('onchange','saveFoaf()');
			privacyCheckbox.setAttribute('type','checkbox');
			privacyCheckbox.id = "privacycheckbox_"+elementId;
			privacyCheckbox.checked = isPrivate;
			privacyDiv.appendChild(privacyCheckbox);
			
			containerDiv.appendChild(privacyDiv);
		}
	}
	
	
	/*creates and appends a generic input element to the appropriate field container*/
	function createGenericInputElement(name, value, thisElementCount, contname,isNew,softRemove,isPrivate){
		var newElement = document.createElement('input');
		newElement.id = name+'_'+thisElementCount;
		newElement.setAttribute('value',value);
		newElement.setAttribute('onchange','saveFoaf()');
		newElement.setAttribute('onfocus','saveFoaf()');
		
		//if the contname does not specify the container to put it in
		if(!contname){
			contname = name+'_container';
		} 
		
		/*if it is a new one, we need to remember to make the contents disappear when it is clicked*/
		if(isNew){
			newElement.style.color = '#dddddd';
			newElement.setAttribute("onfocus","if(this.value=='"+value+"'){this.value ='';this.style.color='#000000';}");
		}
		if(!softRemove){
			createGenericInputElementRemoveLink(newElement.id,contname);
		}
		createGenericInputElementPrivacyBox(newElement.id,contname,isPrivate);	
		
		document.getElementById(contname).appendChild(newElement);
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
	
	
	/*---------------------------Accounts---------------------------*/

	/*creates and appends an account input element to the appropriate field container*/
	function createAccountsInputElement(name, value, element){
		
		newElement = document.createElement('input');
		newElement.setAttribute('onchange','saveFoaf()');
		newElement.id = name;
		
		//XXX this is a bit of a hack
		if(value=='' && name=='foafAccountName'){
			value = 'Enter username here';
			newElement.style.color='#dddddd';
			newElement.setAttribute('onfocus',"if(this.value == 'Enter username here'){this.value = '';this.style.color='#000000'}");
		}	
		if(value=='' && name=='foafAccountProfilePage'){
			value = 'Enter profile URL here';
			newElement.style.color='#dddddd';
			newElement.setAttribute('onfocus',"if(this.value == 'Enter profile URL here'){this.value = '';this.style.color='#000000'}");
		}	
		
		newElement.setAttribute('value',value);
		
		/*if there is a specific container we want to put it in*/
		if(!element){
			var element = document.getElementById(name);
		}
	
		element.appendChild(newElement);
		newElement.setAttribute('class','fieldInput');
	
		return newElement;
	}
	
	function createAccountsAddElement(container){
	
		/*create add link and attach it to the container*/
		var addDiv = document.createElement("div");
		addDiv.id = "addLinkContainer";
		addDiv.className = "addLinkContainer";
		var addLink = document.createElement('a');
		addLink = makeCursorAPointer(addLink);
		addLink.appendChild(document.createTextNode("+Add an Account"));
		addLink.className="addLink";
		addLink.setAttribute("onclick" , "createEmptyHoldsAccountElement(this.parentNode.parentNode,null)");
		addDiv.appendChild(addLink);
		container.appendChild(addDiv);
	
	}
	
	function createHoldsAccountElement(attachElement, bnodeId,isPublic){
		
		/*if new, create a random id*/
		if(!bnodeId){
			var bnodeId = createRandomString(50);
		}
		
		/*create holdsAccount div and attach it to the element given*/
		var holdsAccountElement = document.createElement("div");
		holdsAccountElement.className = 'holdsAccount';
		holdsAccountElement.id = bnodeId;
		attachElement.appendChild(holdsAccountElement);
		
		/*create remove link and attach it to the holds account div*/
		var removeDiv = document.createElement("div");
		removeDiv.id = "removeLinkContainer";
		removeDiv.className = "removeLinkContainer";
		var removeLink = document.createElement('a');
		removeLink = makeCursorAPointer(removeLink);
		removeLink.appendChild(document.createTextNode("- Remove this account"));
		removeLink.id="removeLink";
		removeLink.className="removeLink";
		removeLink.setAttribute("onclick" , "this.parentNode.parentNode.parentNode.removeChild(this.parentNode.parentNode);");
		removeDiv.appendChild(removeLink);
		holdsAccountElement.appendChild(removeDiv);
		
		/*create privacy checkbox*/
		createGenericInputElementPrivacyBox(bnodeId, bnodeId,!isPublic);
		
		return holdsAccountElement;
	}

	/*creates a holds account element and fills it with empty fields*/
	function createEmptyHoldsAccountElement(container){
		
		var thisAccount = new Array();
		var accountBnodeId = createRandomString(50);
		createSingleAccount(thisAccount, accountBnodeId, container,true);
		
		/*remove the add element and re add it (to make sure it's at the bottom)*/
		var addElement = document.getElementById('addLinkContainer');
		addElement.parentNode.removeChild(addElement);
		createAccountsAddElement(container);


	}
			
	/*---------------------------Homepage---------------------------*/
	
	/*renders a dropdown box with a list of possible accountServiceHomepages in it (e.g. skype, msn etc)*/
	function createFoafAccountServiceHomepageInputElement(value,container){
		selectElement = document.createElement("select");
		//XXX get this so that it is populated asynchronously
		/*
		var allAccounts = getAllOnlineAccounts();
		
		selectElement[0] = new Option('Other','',false,false);
		var y=1;
					
		//loop through all online accounts and create options from them
		for(key in allAccounts){
			if(key != 'dedup'){
				selectElement[y] = new Option(key,allAccounts[key],false,false);
				y++;
			}
		}
		/*
		selectElement.id = 'foafAccountServiceHomepage';
		selectElement.className = 'fieldInputSelect';
		selectElement.value = value;
		*/
		
		
		/*show the hidden input elements if there is no option matching this id here*/
		selectElement.setAttribute('onchange',"toggleHiddenAccountInputElements(this.value,this.parentNode, '');saveFoaf();");
		
		container.appendChild(selectElement);
	}
	
	/*---------------------------basedNear---------------------------*/

	function createBasedNearAddElement(container){
		/*create add link and attach it to the container*/
		var addDiv = document.createElement("div");
		addDiv.id = "basedNearAddLinkContainer";
		addDiv.className = "addLinkContainer";
		var addLink = document.createElement('a');
		addLink = makeCursorAPointer(addLink);
		addLink.appendChild(document.createTextNode("+Add a point I'm based near"));
		addLink.className="addLink";
		addLink.setAttribute("onclick" , "createBasedNearElementAboveAddLink('"+container.id+"',this.parentNode.id)");
		addDiv.appendChild(addLink);
		container.appendChild(addDiv);
	}

	/*creates a based near element and ensures that it appears below the add link container*/ 
	function createBasedNearElementAboveAddLink(containerId, addLinkContainerId){
	
		//remove the add link
		var addLinkContainer = document.getElementById(addLinkContainerId);
		var container = addLinkContainer.parentNode;
		container.removeChild(addLinkContainer);
		
		//create a big random string as we don't actually know what the bnode for this one is and put the details in the globalFieldData object
		var bNodeKey = createRandomString(50);
		var thisBasedNear = new Object()
		thisBasedNear.latitude = '50';
		thisBasedNear.longitude = '10';
		
		//if this is the first based near
		globalFieldData.basedNearFields.basedNear[bNodeKey] = thisBasedNear;
	
		//create a new based near marker and div
		createSingleBasedNearMarker(containerId,bNodeKey,thisBasedNear,true);
		
		//re-add the add link
		container.appendChild(addLinkContainer);
		
		//display the map and map to this point
		//displayMap(bNodeKey);
	}

	/*---------------------------friends---------------------------*/

	/*creates a remove link with the removeId being the input element to be removed*/
	function createRemoveFriendsLink(removeId,containerId,isMutual){
		
		/*create remove link and attach it to the container div*/
		var containerDiv = document.getElementById(containerId);
		if(containerDiv){
			var removeDiv = document.createElement("div");
			removeDiv.id = removeId+"removeLinkContainer";
			removeDiv.className = "friendRemoveLinkContainer";
			var removeLink = document.createElement('a');
			removeLink = makeCursorAPointer(removeLink);
			removeLink.appendChild(document.createTextNode("- Remove"));
			removeLink.id="_removeLink";
			removeLink.className="removeLink";
	
			if(isMutual){
				removeLink.setAttribute("onclick" , "removeMutualFriendElement('"+removeId+"','"+removeDiv.id+"');");
			} else {
				removeLink.setAttribute("onclick" , "removeUserKnowsElement('"+removeId+"','"+removeDiv.id+"');");
			}
			
			removeDiv.appendChild(removeLink);
			containerDiv.appendChild(removeDiv);
		}
	}

	/*creates and attaches a link that allows a user to convert people who have said they know them into mutual friends*/
	function createMakeMutualFriendLink(friendDiv){
		
		if(friendDiv && friendDiv.id){
			var makeFriendDiv = document.createElement("div");
			//TODO: should rename this class etc a bit more sensibly
			makeFriendDiv.className = "friendRemoveLinkContainer";
			
			var makeFriendLink = document.createElement('a');
			makeFriendLink = makeCursorAPointer(makeFriendLink);
			makeFriendLink.appendChild(document.createTextNode("-Confirm"));
			makeFriendLink.className="removeLink";
	
			makeFriendLink.setAttribute("onclick" , "makeMutualFriend('"+friendDiv.id+"');");
			
			makeFriendDiv.appendChild(makeFriendLink);
			friendDiv.appendChild(makeFriendDiv);
		}
	}
	
	/*creates and attaches a link that allows a user to convert people who have said they know them into mutual friends*/
	function createAddFriendLink(friendDiv){
		
		if(friendDiv && friendDiv.id){
			var makeFriendDiv = document.createElement("div");
			//TODO: should rename this class etc a bit more sensibly
			makeFriendDiv.className = "friendRemoveLinkContainer";
			
			var makeFriendLink = document.createElement('a');
			makeFriendLink = makeCursorAPointer(makeFriendLink);
			makeFriendLink.appendChild(document.createTextNode("-Add"));
			makeFriendLink.className="removeLink";
	
			makeFriendLink.setAttribute("onclick" , "addFriend('"+friendDiv.id+"');");
			
			makeFriendDiv.appendChild(makeFriendLink);
			friendDiv.appendChild(makeFriendDiv);
		}
	}
	
	/*creates a field with a friends image (if available) and name which links to foaf.qdos.com*/
	function createFriendElement(idName,friend,thisElementCount,container){

		var ifp = null;
		var img = null;
		var name = null;
		
		if(friend.ifps && typeof(friend.ifps[0]) != 'undefined'){
			ifp = friend.ifps[0];
		}
		if(typeof(friend.img) != 'undefined' && friend.img){
			img = friend.img;
		} else{
			//set as a default img.  TODO: sort this out
			img = 'http://foaf.qdos.com/images/icn-no-photo-sml.gif';
		}
		if(typeof(friend.name)!='undefined' && friend.name){
			name = friend.name;
		}
		
		//add a div to contain everything
		var friendDiv = document.createElement('div');
		friendDiv.className = 'friend';
		friendDiv.id = idName+"_"+thisElementCount;
		container.appendChild(friendDiv);
		
		//add an image
		var imageElement = document.createElement('img');
		imageElement.src = img;
		imageElement.className = 'friendImage';
		friendDiv.appendChild(imageElement);
		
		//add the name with a link to foaf.qdos.com
		if(name && !ifp){
			var nameDiv = document.createElement('div');
			nameDiv.className = 'friendName';
			nameDiv.appendChild(document.createTextNode(name));
			friendDiv.appendChild(nameDiv);
		} else if(name && ifp){
			
			var nameDiv = document.createElement('div');
			nameDiv.className = 'friendName';
			
			var nameLink = document.createElement('a');
			nameLink = makeCursorAPointer(nameLink);
			nameLink.appendChild(document.createTextNode(name));
			nameLink.href = 'http://foaf.qdos.com/find/?q='+ifp;
			
			nameDiv.appendChild(nameLink);
			friendDiv.appendChild(nameDiv);
		}
		return friendDiv;
	}
		
		
	
	/*---------------------------generic location (based near, address, nearestAirport)---------------------------*/
			
	/*creates an element to hold the information about a particular location*/
	function createLocationElement(attachElement, bnodeId,optionalClassName,softRemove){
	
		/*if new, create a random id*/
		if(!bnodeId){
			var bnodeId = createRandomString(50);
		}
		if(!optionalClassName){
			var optionalClassName = 'location';
		}
		
		/*create div and attach it to the element given*/
		var locationDiv = document.createElement("div");
		locationDiv.setAttribute('class',optionalClassName);
		locationDiv.id = bnodeId;
		if(bnodeId!='nearestAirport'){
			locationDiv.setAttribute("onclick","map.panTo(mapMarkers['"+bnodeId+"'].getLatLng());map.setZoom(0);");
		}
		attachElement.appendChild(locationDiv);
		
		//XXX... lukelukeluke
		
		/*create remove link and attach it to the location div*/
		var removeDiv = document.createElement("div");
		removeDiv.id = "removeLinkContainer";
		removeDiv.className = "removeLinkContainer";
		var removeLink = document.createElement('a');
		removeLink = makeCursorAPointer(removeLink);
	
		if(!softRemove){
			removeLink.appendChild(document.createTextNode("- Remove this location"));
			removeLink.setAttribute("onclick" , "map.removeOverlay(mapMarkers[this.parentNode.parentNode.id]);this.parentNode.parentNode.parentNode.removeChild(this.parentNode.parentNode);");
		} 
		
		removeLink.id="removeLink";
		removeLink.className="removeLink";
		removeDiv.appendChild(removeLink);
		locationDiv.appendChild(removeDiv);
		
		return locationDiv;
	}

	/*---------------------------other (geek view)---------------------------*/
	//renders the geek view
	function drawOtherTextarea(data){
		log('drawing other');
		if(!data || typeof(data) == 'undefined'){
			log('no data for geek view!');
			return;
		}
		log('drawing other1');
		if(typeof(data.private) == 'undefined' && !data.private &&
			typeof(data.public) == 'undefined' && !data.public){
			
			log('no data for geek view');
			return;
		}
		
		log('drawing other2');
		document.getElementById('personal').innerHTML = '';	
		
		/*build the container*/
		var name = 'other';
		var label = 'Geek View';
		var containerElement = createFieldContainer(name, label);
	
		
		log('drawing other3');
		/*build a textarea for private & public*/
		if(typeof(data.private) != 'undefined' && data.private){
			log('creating one container');
			createOtherTextArea(data.private,'Private',containerElement,'private');
		}
		log('drawing other4');
		if(typeof(data.public) != 'undefined' && data.public){
			log('creating another container');
			createOtherTextArea(data.public,'Public',containerElement,'public');
		}
		log('and now here');
		
	}
	
	function createOtherTextArea(data,header,containerElement,privacy){
		
		if(typeof(data) == 'undefined' || !data){
			log('no data');
			return;
		}
		
		/*build another container*/
		var container = document.createElement('div');
		container.id='rdfContainer'+privacy;
		containerElement.appendChild(container);
		
		/*build a form*/
		var rdfForm = document.createElement('form');	
		rdfForm.setAttribute('action','javascript:write("'+privacy+'")');
		rdfForm.id = 'otherForm'+privacy;
		container.appendChild(rdfForm);
		
		/*render the header*/
		var headerDiv = document.createElement('div');
		headerDiv.className = 'otherHeader';
		headerDiv.appendChild(document.createTextNode(header))
		rdfForm.appendChild(headerDiv);
		
		/*render the textarea*/
		var rdfTextArea = document.createElement('textarea');
		rdfTextArea.name = privacy;
		rdfTextArea.id = 'otherTextArea'+privacy;
		rdfTextArea.setAttribute('cols','1000');
		rdfTextArea.setAttribute('rows','50000'); 
		rdfTextArea.className = ('otherTextArea');
		rdfTextArea.appendChild(document.createTextNode(data));
		rdfForm.appendChild(rdfTextArea);
		
		/*add a few submit buttons*/
		//XXX some of these will not eventually be in the 'Geek View'
		var rdfButton = document.createElement('input');

                if(privacy == 'private'){
                        rdfButton.value = 'Write private RDF to our oauth';
                } else {
                        rdfButton.value = 'Download public RDF'

                        var rdfButton2 = document.createElement('input');
                        rdfButton2.setAttribute('onclick','writePublic()');
			rdfButton2.setAttribute('type','button');
                        rdfButton2.value = 'Write public RDF to our server';
                        rdfButton2.className = 'otherButton';
                        rdfForm.appendChild(rdfButton2);
                }
                rdfButton.setAttribute('type','submit');
                rdfButton.className = 'otherButton';
                rdfForm.appendChild(rdfButton);
	}
	
	//write stuff to public oauth server		
	function writePublic(){
		log('doing write public');
		var publicTextArea = document.getElementById('otherTextAreapublic');
		
		if(!publicTextArea || typeof(publicTextArea) == 'undefined'){
			log('public textarea not found');
			return
		}
		if(publicTextArea.childNodes[0] == 'undefined' || publicTextArea.childNodes[0].nodeValue == 'undefined'){
			return;
		}
		
		var data = publicTextArea.childNodes[0].nodeValue;
		$.post("/writer/write-foaf-nodownload", {data :data}, function(data){});
		
	}	

	/*---------------------------other (geek view)---------------------------*/

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
	
	
/*---------------------------Add/remove element handlers---------------------------*/

/*remove the mutual friend whose div is given by the id removeId*/
function removeMutualFriendElement(removeId,removeDivId){
	var friend = getFriendInfoFromElement(removeId);
	
	/*the container that we want to stick it in*/
	var containerElement = document.getElementById('knowsUser_container');
	
	/*create the new element*/
	if(containerElement){
		//TODO: add urls here
		friend.ifps = getIFPsFromGlobalDataObject(friend);
		$.post("/friend/remove-friend", {friend : JSON.serialize(friend)}, function(data){
			insertFriendInRightPlace(containerElement, 'knowsUser', friend);
		});
	}
	
	/*remove the old one*/
	removeGenericInputElement(removeId,removeDivId);

	/*update the global data object but don't save*/
	knowsDisplayToObjects();
	
}

/*remove the user knows friend whose div is given by the id removeId*/
function removeUserKnowsElement(removeId,removeDivId){
	var friend = getFriendInfoFromElement(removeId);
	
	//TODO: add urls here
	friend.ifps = getIFPsFromGlobalDataObject(friend);
	$.post("/friend/remove-friend", {friend : JSON.serialize(friend)}, function(data){});
	
	/*remove the old one*/
	removeGenericInputElement(removeId,removeDivId);

	/*update the global data object*/
	knowsDisplayToObjects();
	
	/*update the global data object but don't save*/
	knowsDisplayToObjects();
	
}

/*adds a new friend that you've search for*/
function addFriend(friendDivId){
	//XXX: perhaps we can/should do the smarts in here in the back end???

	var friend = getFriendInfoFromElement(friendDivId);

	var isMutualFriend = false;
	var isUserKnows = false;
	var isKnowsUser = false;
	
	var removeIfps = new Array;//the ifps of an element we want to remove
	
	/*check whether this person is in the mutual friends bit*/
	for(friendKey in globalFieldData.foafKnowsFields.mutualFriends){
		for(ifpKey in globalFieldData.foafKnowsFields.mutualFriends[friendKey].ifps){		
			//XXX this could this be more efficient
			if(globalFieldData.foafKnowsFields.mutualFriends[friendKey].ifps[ifpKey]==friend.ifps[0] ||
				globalFieldData.foafKnowsFields.mutualFriends[friendKey].ifps[ifpKey]==sha1(friend.ifps[0]) ||
				globalFieldData.foafKnowsFields.mutualFriends[friendKey].ifps[ifpKey]==sha1('mailto:'+friend.ifps[0]) ||
				sha1(globalFieldData.foafKnowsFields.mutualFriends[friendKey].ifps[ifpKey])==friend.ifps[0]){
				
				isMutualFriend = true;
			}
		}
	}
	/*check whether this person is in the user knows bit*/
	for(friendKey in globalFieldData.foafKnowsFields.userKnows){
		for(ifpKey in globalFieldData.foafKnowsFields.userKnows[friendKey].ifps){
			//XXX this could this be more efficient
			if(globalFieldData.foafKnowsFields.userKnows[friendKey].ifps[ifpKey]==friend.ifps[0] ||
				globalFieldData.foafKnowsFields.userKnows[friendKey].ifps[ifpKey]==sha1(friend.ifps[0]) ||
				globalFieldData.foafKnowsFields.userKnows[friendKey].ifps[ifpKey]==sha1('mailto:'+friend.ifps[0]) ||
				sha1(globalFieldData.foafKnowsFields.userKnows[friendKey].ifps[ifpKey])==friend.ifps[0]){
				
				isUserKnows = true;
			}
		}
	}
	/*check whether this person is in the knows user bit*/
	for(friendKey in globalFieldData.foafKnowsFields.knowsUser){
		for(ifpKey in globalFieldData.foafKnowsFields.knowsUser[friendKey].ifps){
			//XXX this could this be more efficient
			if(globalFieldData.foafKnowsFields.knowsUser[friendKey].ifps[ifpKey]==friend.ifps[0] ||
				globalFieldData.foafKnowsFields.knowsUser[friendKey].ifps[ifpKey]==sha1(friend.ifps[0]) ||
				globalFieldData.foafKnowsFields.knowsUser[friendKey].ifps[ifpKey]==sha1('mailto:'+friend.ifps[0]) ||
				sha1(globalFieldData.foafKnowsFields.knowsUser[friendKey].ifps[ifpKey])==friend.ifps[0] ||
				sha1(globalFieldData.foafKnowsFields.knowsUser[friendKey].ifps[ifpKey])==sha1('mailto:'+friend.ifps[0]) ||
				sha1('mailto:'+globalFieldData.foafKnowsFields.knowsUser[friendKey].ifps[ifpKey])==friend.ifps[0] ||
				sha1('mailto:'+globalFieldData.foafKnowsFields.knowsUser[friendKey].ifps[ifpKey])==sha1(friend.ifps[0]) ||
				sha1('mailto:'+globalFieldData.foafKnowsFields.knowsUser[friendKey].ifps[ifpKey])==sha1("mailto:"+friend.ifps[0])){

				removeIfps = globalFieldData.foafKnowsFields.knowsUser[friendKey].ifps;
				isKnowsUser = true;
			}
		}
	}
	
	/*Add to the appropriate box*/
	if(isUserKnows || isMutualFriend){
		alert("Already there!");//TODO: add some sort of scrolling fading thing
		
	} else if(!isKnowsUser){//a non mutual friend
		/*get container element*/
		var containerElement = document.getElementById('userKnows_container');
		
		if(containerElement){
			//actually stick it in the model in the back end
			if(typeof(globalTypeArray[friend.ifps[0]]) != 'undefined' && globalTypeArray[friend.ifps[0]]){
				//TODO: this same technique could be used to preserve the uri
				friend.ifp_type = globalTypeArray[friend.ifps[0]];
			} 
			$.post("/friend/add-friend", {friend : JSON.serialize(friend)}, function(data){
				insertFriendInRightPlace(containerElement, 'userKnows', friend);
			});
		}
	} else {//a mutual friend
		/*get container element*/
		var containerElement = document.getElementById('mutualFriends_container');
		
		//add it as a mutual friend
		if(containerElement){
			//actually stick it in the model in the back end
			if(typeof(globalTypeArray[friend.ifps[0]]) != 'undefined' && globalTypeArray[friend.ifps[0]]){
				//TODO: this same technique could be used to preserve the uri
				friend.ifp_type = globalTypeArray[friend.ifps[0]];
			} 
			$.post("/friend/add-friend", {friend : JSON.serialize(friend)}, function(data){
				insertFriendInRightPlace(containerElement, 'userKnows', friend);
			});
		}
		
		//remove it from knows user using the ifp
		removeKnowsUserUsingIFPs(removeIfps);
	}
	
	removeGenericInputElement(friendDivId,'id');

	//update the global field data object but don't save
	knowsDisplayToObjects();
}

/*remove a friend element which matches the given ifps from the knowsUser given*/
//XXX this is slow and complicated, there ought to be an easier way
function removeKnowsUserUsingIFPs(ifps){
	var containerElement = document.getElementById('knowsUser_container');
	var foundIt = false;
	
	for(elemKey in containerElement.childNodes){
		for(ifpKey in ifps){

			if(typeof(containerElement.childNodes[elemKey]) != 'undefined' 
				&& containerElement.childNodes[elemKey] 
				&& typeof(containerElement.childNodes[elemKey].id) != 'undefined' 
				&& containerElement.childNodes[elemKey].id){
			
				var friendInfo = getFriendInfoFromElement(containerElement.childNodes[elemKey].id); 
						
				if(ifps[ifpKey] == friendInfo.ifps[0]){
					containerElement.removeChild(containerElement.childNodes[elemKey]);
					break
				}	
			}
		}
		if(foundIt){
			break;
		}
	}
}

/*insert a friend in the right place alphabetically. containerElement is the place we're inserting it.  
 *Name is the name of the container e.g. userKnows and friend is the friend data (in array/object form)*/
function insertFriendInRightPlace(containerElement, name, friend){
	var reachedPoint = false;//whether we've got to the point alphabetically where we want to insert
	var toReattachElements = new Array();
	var originalNoOfChildNodes = containerElement.childNodes.length;
	var i = 0;

	/*remove all elements after the insertion point*/
	for(contKey in containerElement.childNodes){
		if(typeof(containerElement.childNodes[contKey]) == 'undefined' || typeof(containerElement.childNodes[contKey].id)=='undefined'){
			continue;
		}
		var thisFriend = getFriendInfoFromElement(containerElement.childNodes[contKey].id);
				
		if(reachedPoint){
			i++;
			//remove this element and stow to reattach later.
			toReattachElements.push(containerElement.childNodes[contKey]);
		} else if(typeof(thisFriend.name) != 'undefined' && thisFriend.name){
			if(thisFriend.name.toLowerCase() > friend.name.toLowerCase()){
				/*remove this element and stow to reattach later.  Set reachedPoint so we remove all subsequent elements*/
				toReattachElements.push(containerElement.childNodes[contKey]);
				reachedPoint = true;
				i++;
			} 
		}
	}
			
	/*remove all those elements that come after our element*/
	for(elemKey in toReattachElements){
		if(typeof(toReattachElements[elemKey]) == 'undefined'){
			continue;
		}
		containerElement.removeChild(toReattachElements[elemKey]);
	}
	
	/*append our new element*/
	var friendDiv = createFriendElement(name,friend,originalNoOfChildNodes,containerElement);
	if(name == 'mutualFriend'){//XXX: this is a bit dirty
		createRemoveFriendsLink(friendDiv.id,friendDiv.id,true);
	} else if(name == 'knowsUser'){
		createMakeMutualFriendLink(friendDiv);
	} else {
		createRemoveFriendsLink(friendDiv.id,friendDiv.id,false);
	}
			
	/*reattach all those elements that we have removed*/
	for(elemKey in toReattachElements){
		if(typeof(toReattachElements[elemKey]) == 'undefined'){
			continue;
		}
		containerElement.appendChild(toReattachElements[elemKey]);
	}

	if(containerElement){
		/*scroll the div to the appropriate place*/
		//TODO: un hardcode the 60 and 150 here
		containerElement.scrollTop = (containerElement.childNodes.length - i-2) * 60;
	}

	/*do some fancy yellow fading*/
	yellowFade(friendDiv);	

	return friendDiv.id;
	
}

/*converts a user that knows you to one that you know*/
function makeMutualFriend(friendDivId){

	var friend = getFriendInfoFromElement(friendDivId);
	
	/*the container that we want to stick it in*/
	var containerElement = document.getElementById('mutualFriends_container');

	/*create the new element*/
	$.post("/friend/add-friend", {friend : JSON.serialize(friend)}, function(data){
				insertFriendInRightPlace(containerElement, 'mutualFriend', friend);
				removeGenericInputElement(friendDivId,'id');
				knowsDisplayToObjects();
			});

	/*remove the old one*/

	/*update the global object but don't save*/
}

/*removes the input element with the given id as well as its corresponding remove element*/
//TODO: this is badly named
function removeGenericInputElement(inputIdForRemoval, removeDivId, isImage){
	/*Get the ids*/
	var inputElement = document.getElementById(inputIdForRemoval);
	var removeElement = document.getElementById(removeDivId);
	var privacyDiv = document.getElementById("privacydiv_"+inputIdForRemoval);
	
	if(isImage){
		var source = inputElement.src;
		$.post("/file/remove-image", {filename: source}, function(){saveFoaf();},null);
	}
	
	/*remove the old element*/
	if(inputElement){
		inputElement.parentNode.removeChild(inputElement);
	}
	if(removeElement){
		removeElement.parentNode.removeChild(removeElement);
	}
	if(privacyDiv){
		privacyDiv.parentNode.removeChild(privacyDiv);
	}
	
	
}




/*----------------------------------functions to perform various changes to the HTML such as updating or toggling-------------------------------*/

/*updates the lat long text, for example, when a marker is dragged*/
function updateLatLongText(holderName,marker){
	var latElement = document.getElementById('latitude_' + holderName);
	var longElement = document.getElementById('longitude_' + holderName);
	    	
	if(latElement.childNodes[0] && longElement.childNodes[0]){
		latElement.removeChild(latElement.childNodes[0]);
	   	longElement.removeChild(longElement.childNodes[0]);
	}
	    	
	var latText = document.createTextNode("Latitude: " +marker.getLatLng().lat().toString().substr(0,8));
	var longText = document.createTextNode("Longitude: " + marker.getLatLng().lng().toString().substr(0,8));
	    	
	latElement.appendChild(latText);
	longElement.appendChild(longText);
}

function log(logline){

	if(loggingOn){	
		var debugDiv = document.getElementById('debugDiv');
		if(!debugDiv){
			debugDiv = document.createElement("div");
			debugDiv.id='debugDiv';
			document.body.appendChild(debugDiv);
			debugDiv.style.position = 'absolute';
			debugDiv.style.top = '0px';
			debugDiv.style.left = '0px';
		}
		debugDiv.appendChild(document.createTextNode(logline));
		debugDiv.appendChild(document.createElement("br"));
	}
}

/*preview an image that has been uploaded or entered as a url and save the page*/
function previewImage(containerElementId,name,source,file,isPublic){
	log('in preview image '+isPublic);
	var image = new Array();
	
	//XXX need to worry about browser compatibility here
	//if file is set then we're uploading, hence the file:// prefix
	if(file){
		image['uri'] = 'file://'+source;
	} else {
		image['uri'] = source;
	}
	/*remove the menu div*/	
	var menuDiv = document.getElementById('menuDiv_'+name);
	menuDiv.parentNode.removeChild(menuDiv);
	var containerElement = document.getElementById(containerElementId);
	
	/*render the new image element*/
	if(name=='foafDepiction'){
		renderDepictionElement(image,containerElement.childNodes.length,document.getElementById(containerElementId),isPublic);
	} else{
		renderImgElement(image,containerElement.childNodes.length,document.getElementById(containerElementId),isPublic);
	}
	
	/*reattach the menu div underneath the existing menu*/
	containerElement.appendChild(menuDiv);

	saveFoaf();	
}	

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

/*gets airport codes from the name of the airport - called in autocomplete.js*/
function updateCodesFromAirportName(value){
	var iataCode = document.getElementById('iataCode');
	var icaoCode = document.getElementById('icaoCode');
	
	//XXX I think that there is a bug where sometimes this is undefined
	if(autocomplete_airportData[value] == 'undefined'){
		log('Airport data is sometimes undefined');
		return;
	}
	
	if(iataCode && typeof(autocomplete_airportData[value]['iata']) != 'undefined'){
		//set the iata value
		iataCode.childNodes[0].nodeValue ='IATA Code: '+autocomplete_airportData[value]['iata'];	
	}
	else{
		alert("iata not found");//TODO: perhaps use more graceful error or nothing at all.
		return;
	}
	
	if(icaoCode && typeof(autocomplete_airportData[value]['icao']) != 'undefined'){
		//set the iata value
		icaoCode.childNodes[0].nodeValue ='ICAO Code: '+autocomplete_airportData[value]['icao'];
	}
	else{
		alert("icao not found");//TODO: perhaps use more graceful error handling or nothing at all.
		return;
	}
	
	//pan the map to it
	if(typeof(autocomplete_airportData[value]['icao'])!='undefined'){
		var geocoder = new GClientGeocoder();
		geocoder.getLatLng(autocomplete_airportData[value]['icao'],geoCodeNearestAirport);
	}
}

/*toggles the pale blue private UI*/
function togglePrivateUI(fieldContainer){

	if(fieldContainer.className!='fieldContainer'){
		fieldContainer.className = 'fieldContainer';
	} else {
		fieldContainer.className = 'fieldContainerPrivate';
	}
}

/*--------------------------accounts name mangling functions--------------------------*/
/*object storing online account urls (e.g. www.skype.com) and keying them against their names (e.g. skype)*/
//TODO this should integrate with QDOS

function populateAllAccountsDropdowns(){

	var accountsContainer = document.getElementById('foafHoldsAccount_container');
	
	if(typeof(accountsContainer) == 'undefined' || !accountsContainer){
		return;
	}
	
	for(accountKey in accountsContainer.childNodes){
	
		log('first id: '+accountsContainer.childNodes[accountKey].id);
				
		for(childKey in accountsContainer.childNodes[accountKey]){
		
			log('second id: '+accountsContainer.childNodes[accountKey].id);
			
			if(typeof(accountsContainer.childNodes[accountKey][childKey]) != 'undefined' &&
				accountsContainer.childNodes[accountKey][childKey].className == 'fieldInputSelect'){				
				
				log('third id');
				alert('found!');
		
			} else {
				log('fourth id');
				log('not found!');
			}
		}
	}
}

function setAllOnlineAccounts(){
	/*set the allAccounts object*/
	$.post("/accounts/get-all-account-types", {}, function(data){ allAccounts = data;});
}


function getAllOnlineAccounts(){

	/*if the variable is already set then return it*/
	if(typeof(allAccounts) != 'undefined' && allAccounts){
		return allAccounts;
	
	} else {
		return;
	}
}
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



/*--------------------------geocoding callbacks--------------------------*/


/*turns a point into an address*/
function geoCodeNewAddress(point){
  /*so we use the right variables for each request*/
  if(typeof(geoCodeNewAddress.count) == 'undefined'){
  	geoCodeNewAddress.count = 0;
  } else{
  	geoCodeNewAddress.count++;
  }
  
     if (!point) {
     	log("No point");
     	//TODO: possibly do something here, maybe do nothing
     } else {
		      	  
      	/*get some variables according to the count*/
		var title = addressDetailsToGeoCode[geoCodeNewAddress.count]['title'];
		var address = addressDetailsToGeoCode[geoCodeNewAddress.count]['address'];
		var bnode = addressDetailsToGeoCode[geoCodeNewAddress.count]['bnode'];
		var container = addressDetailsToGeoCode[geoCodeNewAddress.count]['container'];
		var prefix = addressDetailsToGeoCode[geoCodeNewAddress.count]['prefix'];
		var isPublic = addressDetailsToGeoCode[geoCodeNewAddress.count]['isPublic'];
		
		/*the geocoded coords*/
        latitude = point.lat();
        longitude = point.lng();

		/*put the marker in the right place*/
      	var marker = new GMarker(point,{title: prefix});	
      	
      	/*so we can access the markers in the future*/
		mapMarkers[bnode] = marker;
		map.addOverlay(marker);
		map.setCenter(point);	
		createAddressDiv(title,address,bnode,container,latitude,longitude, prefix,isPublic);
	}			
}

//callback for geocoding nearest airport info.  Updates the lat longs and creates a map marker if there isn't one already.
function geoCodeNearestAirport(point){
	if(!point){
		//TODO: should we do something here?
		log('No Point');
	} else {
		var marker;
		if(typeof(mapMarkers['nearestAirport']) == 'undefined'){
			//the first geocode
			marker = new GMarker(point,{title: 'My Nearest Airport'}); 	
			mapMarkers['nearestAirport'] = marker;
			map.addOverlay(marker);
		} else{
			//a subsequent geocode
			marker = mapMarkers['nearestAirport'];
			marker.setLatLng(point);
			map.panTo(marker.getLatLng());
		}
		
		
		updateLatLongText('nearestAirport',marker);
	}
}


/*callback function for reverse geocode which puts the address information in the based near box*/
function updateBasedNearAddress(placemark){
	
	  /*so we use the right variables for each request*/
	if(typeof(updateBasedNearAddress.count) == 'undefined'){
	  	updateBasedNearAddress.count = 0;
	} else{
		updateBasedNearAddress.count++;
	}
	
	var elem = document.createElement('div');
	elem.className= 'basedNearAddress';
	elem.appendChild(document.createTextNode(placemark.Placemark[0].address));
	var container = document.getElementById(basedNearDetailsToGeoCode[updateBasedNearAddress.count]['containerId']);
	
	//remove existing address
	for(nodeName in container.childNodes){
		if(container.childNodes[nodeName] && container.childNodes[nodeName].className == 'basedNearAddress'){
			container.removeChild(container.childNodes[nodeName]);
		}
	}
	container.appendChild(elem);

}

/*geocodes the address and updates the latitude/longitude fields and sets the appropriate element in the globalFieldData object*/
function geoCodeExistingAddress(bNodeKey,prefix,doPan,isPrivate){
		
		log('In geocode existing address function');
	
	   	if(typeof(bNodeKey) == 'undefined' 
	   		|| typeof(prefix) == 'undefined' 
	   		|| typeof(doPan) == 'undefined'
	   		|| typeof(isPrivate) == 'undefined'){
	   		return;
	   	}	   	
	   	if(typeof(bNodeKey) == 'undefined' || !bNodeKey || !prefix){
	   		return;
	   	}
	   	if(typeof(globalFieldData.addressFields[prefix]) == 'undefined'
	   		|| typeof(globalPrivateFieldData.addressFields[prefix]) == 'undefined'){
	   		return;
	   	}   
	   	var prefixObject = globalFieldData.addressFields[prefix];
	   	
	   	if(typeof(prefixObject[bNodeKey]) == 'undefined'
	   		|| typeof(prefixObject[bNodeKey]) == 'undefined'){
	   		return;
	   	}   		
	   	
	   	/*get the address and convert it to an array*/
	   	var addressArray;
	   	if(!isPrivate){
			addressArray = getProperties(prefixObject[bNodeKey]);
		} else {
			addressArray = getProperties(prefixObject[bNodeKey]);
		}

		/*some details for the callback function*/
		var theseDetails = new Array();
		theseDetails['bnode'] = bNodeKey;	
		theseDetails['doPan'] = doPan;//whether to pan or not
		theseDetails['isPrivate'] = isPrivate;
		existingAddressDetailsToGeoCode[prefix] = theseDetails;
		
		/*do the geocoding*/
		var geocoder = new GClientGeocoder();
		if(prefix=='home'){
			geocoder.getLatLng(addressArray,homeDisplayToObjectsGeoCode);
	 	} else {
	 		geocoder.getLatLng(addressArray,officeDisplayToObjectsGeoCode);
	 	}
}

function homeDisplayToObjectsGeoCode(point) {
		if (!point) {
			log("No point");
			//TODO: possibly do something here, maybe do nothing
	    } else {
	    	anyPrefixDisplayToObjectsGeoCode('home',point);
	  	}			
}

function officeDisplayToObjectsGeoCode(point) {
	    	if (!point) {
	    		log('no point office');
	        	//TODO: possibly do something here, maybe do nothing
	      	} else {
	    		anyPrefixDisplayToObjectsGeoCode('office',point);
	      	}			
}

function anyPrefixDisplayToObjectsGeoCode(prefix,point){
		if(typeof(prefix) == 'undefined' || !prefix){
			log('error');
			return;
		}
		log('geocoding');
		var homeArray = existingAddressDetailsToGeoCode[prefix];
	    var bNodekey = homeArray['bnode'];
	    var doPan = homeArray['doPan'];
	    var isPrivate = homeArray['isPrivate'];
	   	
	   	log('geocoding1');
	   	
	    //move the point and the centre of the map
	    mapMarkers[bNodekey].setLatLng(point);
    	
    	log('geocoding2');
    		
	    //update the display to show the new latitude and longitude	  	
	    updateLatLongText(bNodekey,mapMarkers[bNodekey]);
		
		//set the global data with the appropriate stuff
		if(isPrivate){
			globalPrivateFieldData.addressFields[prefix][bNodekey]['latitude'] = point.lat();
			globalPrivateFieldData.addressFields[prefix][bNodekey]['longitude'] = point.lat();
		} else {
			globalPublicFieldData.addressFields[prefix][bNodekey]['latitude'] = point.lat();
			globalPublicFieldData.addressFields[prefix][bNodekey]['longitude'] = point.lat();
		}
			
		if(doPan){
			map.panTo(point);
	    }
}



/*--------------------------map stuff--------------------------*/

/*displays the map level with the element shown*/
function displayMap(anchorElementId){

	//A container for the pop up map window
	var mapDiv = document.getElementById('mapDiv');
	
	//the actual container of the map
	var innerMapDiv = document.getElementById('innerMapDiv');

	if(mapDiv && typeof(mapDiv) != 'undefined'){
	  	//make the mapdiv visible
	  	mapDiv.style.display = 'inline';
	   	mapDiv.style.position = 'absolute';
	   	
	   	mapDiv.style.left = (parseFloat(findPosX(document.getElementById(anchorElementId)))-442)+'px'; 
	   	mapDiv.style.top = findPosY(document.getElementById(anchorElementId))+'px';
		map.checkResize();
		
		//render a link to close the map if there isnt one already
		if(!document.getElementById('mapCloseLink')){
			var closeLink = document.createElement('div');
			closeLink.setAttribute('onclick',"this.parentNode.style.display='none';this.parentNode.removeChild(this);");
			closeLink.appendChild(document.createTextNode('close [X]'));
			closeLink.id='mapCloseLink';
     		
     		//append the close link at the top of the map container
			mapDiv.removeChild(innerMapDiv);
			mapDiv.appendChild(closeLink);
			mapDiv.appendChild(innerMapDiv);
     	}
	
	}
}

function displayEmbeddedMap(){
	var mapDiv = document.getElementById('mapDiv');
	if(mapDiv && typeof(mapDiv) != 'undefined'){
		document.getElementById('mapDiv').parentNode.removeChild(document.getElementById('mapDiv'));
		map.checkResize();
	}
}

/*creates and returns a google map element if there isn't one already there*/
function createMapElement(container){
	
		if(document.getElementById('mapDiv')){
			return null;
		}
		
  		/*reset any previously drawn map and geoCoding details*/
		var addressDetailsToGeoCode = new Array();
		var existingAddressDetailsToGeoCode = new Array();
		var basedNearDetailsToGeoCode = new Array();
		mapMarkers = new Array();
  		map = null;
  		

     	if (GBrowserIsCompatible()) {
     		
     		var mapDiv = document.createElement('div');
     		mapDiv.id = 'mapDiv'
     		
     		//the default style is hidden
     		mapDiv.className ='hiddenMapDiv';
     		document.body.appendChild(mapDiv);
       
       		var innerMapDiv = document.createElement('div');
       		innerMapDiv.className = 'innerMapDiv';
       		innerMapDiv.id = 'innerMapDiv';
       		mapDiv.appendChild(innerMapDiv);
       		//,{size: new GSize(375,375)}
       		map = new GMap2(innerMapDiv);
       		map.setCenter(new GLatLng(37.4419, -122.1419), 13);
       
       		var mapControl = new GSmallMapControl();
			map.addControl(mapControl);
			
       		return mapDiv;
     	} 
   
}

function makeCursorAPointer(element){

	if(!element || typeof(element) == 'undefined'){
		return;
	}
	
	element.setAttribute('onmouseover','this.style.cursor="pointer";');
	element.setAttribute('onmouseout','this.style.cursor="default";');
	
	return element;
}

	   	
