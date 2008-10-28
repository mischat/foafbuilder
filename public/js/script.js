/*global variable for storing data*/
var globalFieldData;
var currentPage;//the page the user is on e.g. load-contact-details etc.

/*for use by the displayToObjects function*/
var bnodeToGeoCodeDO;	
var prefixToGeoCodeDO;

/*contains address details for geocoding so that the callback function can access them*/
var addressDetailsToGeoCode = new Array();

/*contains details for geocoding an existing address*/
var existingAddressDetailsToGeoCode = new Array();

/*contains based near details for reverse geocoding so that the callback function can access them*/
var basedNearDetailsToGeoCode = new Array();

/*so we can wait for a geocode request to finish*/
var geoCodeRequestFinished = false;

/*google maps data*/
var mapMarkers = new Array();
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
	
	/*so we can track which page the person is on*/
	currentPage = name;
	
  	//TODO use jquery event handler to deal with errors on requests
  	//TODO perhaps this is bad.  There certainly should be less hardcoding here.
  	if(name != 'load-other'){
  		$.post("/ajax/"+name, { uri: url}, function(data){genericObjectsToDisplay(data);}, "json");
  	} else {
  		renderOther();
  	}
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
	displayToObjects(currentPage);
	jsonstring = JSON.serialize(globalFieldData);

	//TODO use jquery event handler to deal with errors on this request
  	$.post("/ajax/save-Foaf", {model : jsonstring});
}


/*Writes FOAF to screen*/
function renderOther() {
        //$.post("/writer/write-Foaf", { }, function(data){alert(data.name);console.log(data.time);},"json");
		url = document.getElementById('writeUri').value;
        $.post("/writer/write-foafn3", {uri: url }, function(data){drawOtherTextarea(data);},null);
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
	renderSimpleFields(data);
	renderBirthdayFields(data);
	renderHomepageFields(data);
	renderPhoneFields(data);
	renderMboxFields(data);
	renderAddressFields(data);
	renderBasedNearFields(data);
	renderNearestAirportFields(data);
	renderDepictionFields(data);
	renderImgFields(data);
	renderKnowsFields(data);
	
}
//renders the geek view
function drawOtherTextarea(data){
	if(!data || typeof(data) == 'undefined'){
		return;
	}
	document.getElementById('personal').innerHTML = '';	
	
	/*build the container*/
	var name = 'other';
	var label = 'Geek View';
	var containerElement = createFieldContainer(name, label);
	
	/*build another container*/
	var rdfContainerDiv = document.createElement('div');
	rdfContainerDiv.id='rdfContainer';
	containerElement.appendChild(rdfContainerDiv);
	
	/*build a form*/
	var rdfForm = document.createElement('form');
	rdfForm.setAttribute('action','javascript:saveFoaf()');//TODO: need to have a good name for this
	rdfForm.id = 'otherForm';
	rdfContainerDiv.appendChild(rdfForm);
	
	/*build a textarea*/
	var rdfTextArea = document.createElement('textarea');
	rdfTextArea.id = ('otherTextArea');
	rdfTextArea.setAttribute('cols','1000');
	rdfTextArea.setAttribute('rows','50000'); 
	rdfTextArea.className = ('otherTextArea');
	rdfForm.appendChild(rdfTextArea);
	rdfTextArea.appendChild(document.createTextNode(data));
	
	/*add a submit button*/
	var rdfButton = document.createElement('input');
	rdfButton.value = 'save';
	rdfButton.setAttribute('type','submit');
	rdfButton.id = 'otherButton';
	rdfButton.className = 'otherButton';
	rdfForm.appendChild(rdfButton);
}


/*render the various relationships of the user*/
function renderKnowsFields(data){
	if(!data || !data.foafKnowsFields || typeof(data.foafKnowsFields) == 'undefined'){
		return;
	}
	renderMutualFriends(data.foafKnowsFields);
	renderKnowsUserFields(data.foafKnowsFields);//like incoming friend requests
	renderUserKnowsFields(data.foafKnowsFields);//like outgoing friend requests
	//TODO: could we add a timestamp to show people that have recently accepted friend requests etc.
	
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


/*Render the image fields*/
function renderImgFields(data){
	if(!data || !data.foafImgFields || typeof(data.foafImgFields) == 'undefined'){
		return;
	}
	
	/*build the container*/
	var name = data.foafImgFields.name;
	var label = data.foafImgFields.displayLabel;
	var containerElement = createFieldContainer(name, label);

	/*render each individual image element*/	
	for(image in data.foafImgFields['images']){
		renderImgElement(data.foafImgFields['images'][image],image,containerElement);
	}
	
	/*render the image menu i.e. upload new, link to an image*/
	renderImageMenu('foafImg', containerElement);
}

/*Render the image fields*/
function renderDepictionFields(data){
	if(!data || !data.foafDepictionFields || typeof(data.foafDepictionFields) == 'undefined'){
		return;
	}
	
	/*build the container*/
	var name = data.foafDepictionFields.name;
	var label = data.foafDepictionFields.displayLabel;
	var containerElement = createFieldContainer(name, label);

	/*render each individual image element*/	
	for(image in data.foafDepictionFields['images']){
		renderDepictionElement(data.foafDepictionFields['images'][image],image,containerElement);
	}
	
	/*render the image menu i.e. upload new, link to an image*/
	renderImageMenu('foafDepiction', containerElement);
}

/*renders a main image element*/
function renderImgElement(image,count,containerElement){
	
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

	//FIXME: this function is badly named!
	/*create (and append) the remove link*/
	createGenericInputElementRemoveLink(imageElement.id, containerElement.id,true);
		
	/*tack the image element onto the container*/
	containerElement.appendChild(imageElement);
	
	return imageElement;
}

/*renders a depiction element*/
function renderDepictionElement(image,count,containerElement){
	
	/*create the image element*/
	var imageElement = document.createElement(imageElement, containerElement.id);
	
	/*create the image element*/
	var imageElement = document.createElement('img');
	imageElement.setAttribute('src',image['uri']);
	if(typeof(image['title']) != 'undefined' && image['title']){
		imageElement.setAttribute('title',image['title']);
	}
	if(typeof(image['description']) != 'undefined' && image['description']){
		imageElement.setAttribute('alt',image['description']);
	}
	imageElement.id = 'foafDepiction'+count;
	imageElement.className = 'image';
	
	//FIXME: this function is badly named!
	/*create (and append) the remove link*/
	createGenericInputElementRemoveLink(imageElement.id, containerElement.id,true);
		
	/*tack the image element onto the container*/
	containerElement.appendChild(imageElement);
}

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
	menuForm.setAttribute('onsubmit',"return AIM.submit(this, {'onStart' : startCallback, 'onComplete' : uploadCallback_"+name+"})")
	
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
	menuForm.setAttribute('action','file/upload-image');
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
	linkToImageInput.setAttribute('onchange','previewImage("'+containerElement.id+'","'+name+'",this.value);');
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

/*preview an image that has been uploaded or entered as a url and save the page*/
function previewImage(containerElementId,name,source,file){
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
	renderImgElement(image,containerElement.childNodes.length,document.getElementById(containerElementId));
	
	/*reattach the menu div underneath the existing menu*/
	containerElement.appendChild(menuDiv);
	
	/*save the page as is prudent*/
	saveFoaf();	
}


/*Render the location map*/
function renderAddressFields(data){

	if(!data || !data.addressFields || typeof(data.addressFields) == 'undefined'){
		return;
	}
	
	/*build the container*/
	var name = data.addressFields.name;
	var label =	data.addressFields.displayLabel;
	var containerElement = createFieldContainer(name, label);

	/*display a map if there isn't one already*/
	if(!document.getElementById('mapDiv')){
		map = createMapElement(containerElement);
	}
	
	if(map){
		/*render the markers on the map and add divs containing the information below*/
		addAddressMarkers(data.addressFields['office'],data.addressFields['home'],containerElement,map);			
	}
}

/*Render the location map*/
function renderNearestAirportFields(data){
	if(!data || !data.nearestAirportFields || typeof(data.nearestAirportFields) == 'undefined'){
		return;
	}
	
	/*build the container*/
	var name = data.nearestAirportFields.name;
	var label =	data.nearestAirportFields.displayLabel;
	var containerElement = createFieldContainer(name, label);

	/*display a map if there isn't one already*/
	if(!document.getElementById('mapDiv')){
		map = createMapElement(containerElement);
	}
	
	if(map){
		/*render the markers on the map and add divs containing the information below*/
		addNearestAirportMarker(data.nearestAirportFields['nearestAirport'],containerElement,map);			
	}
}

/*Render the location map*/
function renderBasedNearFields(data){
	if(!data || !data.basedNearFields || typeof(data.basedNearFields) == 'undefined'){
		return;
	}
	
	/*build the container*/
	var name = data.basedNearFields.name;
	var label =	data.basedNearFields.displayLabel;
	var containerElement = createFieldContainer(name, label);

	/*display a map if there isn't one already*/
	if(!document.getElementById('mapDiv')){
		map = createMapElement(containerElement);
	}
	
	if(map){
		/*render the markers on the map and add divs containing the information below*/
		addBasedNearMarkers(data.basedNearFields['basedNear'],containerElement,map);			
	}
}

/*adds markers and divs for home and office addresses*/
function addAddressMarkers(office,home,containerElement,map){
	
	var i=0;
	for(bNodeKey in office){
		i++;
		addSingleAddressMarker('Office Address',office[bNodeKey],bNodeKey,containerElement,'office');
	}
	if(i==0){
		var bNodeKeyPlacemark = createRandomString(50);
		addSingleAddressMarker('Office Address',bNodeKeyPlacemark,bNodeKeyPlacemark,containerElement,'office');
	}
	
	var j=0;	
	for(bNodeKey in home){
		j++;
		addSingleAddressMarker('Home Address',home[bNodeKey],bNodeKey,containerElement,'home');
	}
	if(j==0){
		var bNodeKeyPlacemark = createRandomString(50);
		addSingleAddressMarker('Home Address',bNodeKeyPlacemark,bNodeKeyPlacemark,containerElement,'home');
	}
	
	
	
}

/*adds one address marker*/
function addSingleAddressMarker(title,address,bNodeKey,containerElement,prefix){
	var latitude = address['latitude'];
	var longitude = address['longitude'];
	
	//i.e. a new blank address
	if(bNodeKey.length == '50'){
		//TODO: change these values to those of the garlik address
		latitude = '40';
		longitude = '34';
		alert("new empty address");
	}
	
	/*there is an address there but the latitude and longitude isn't set*/
	if(!latitude && !longitude){
		//alert("do geocode"+prefix);
		var addressArray = getProperties(address);
		
		/*array to pass to the geocoder's callback function*/
		theseDetails = new Array();
		theseDetails['bnode'] = bNodeKey;	
		theseDetails['prefix'] = prefix;
		theseDetails['container'] = containerElement;
		theseDetails['address'] = address;
		theseDetails['title'] = title;		
		addressDetailsToGeoCode.push(theseDetails);

		/*do the actual geoCoding*/
		var geocoder = new GClientGeocoder();
		geocoder.getLatLng(addressArray,geoCodeNewAddress);
   		
	} else{
		var point = new GLatLng(latitude, longitude);
		var marker = new GMarker(point,{title: prefix});	
		
		alert('Added marker (no geocode needed): ' +bNodeKey);
		mapMarkers[bNodeKey] = marker;
	
		map.addOverlay(marker);
		map.setCenter(point);
		
		createAddressDiv(title,address,bNodeKey,containerElement,latitude,longitude, prefix);
	}
	

}

/*turns a point into an address*/
function geoCodeNewAddress(point){
	  /*so we use the right variables for each request*/
	  if(typeof(geoCodeNewAddress.count) == 'undefined'){
	  	geoCodeNewAddress.count = 0;
	  } else{
	  	geoCodeNewAddress.count++;
	  }
	  
      if (!point) {
      	alert("no point");
      	//TODO: possibly do something here, maybe do nothing
      } else {
      	
  
      	/*get some variables according to the count*/
		var title = addressDetailsToGeoCode[geoCodeNewAddress.count]['title'];
		var address = addressDetailsToGeoCode[geoCodeNewAddress.count]['address'];
		var bnode = addressDetailsToGeoCode[geoCodeNewAddress.count]['bnode'];
		var container = addressDetailsToGeoCode[geoCodeNewAddress.count]['container'];
		var prefix = addressDetailsToGeoCode[geoCodeNewAddress.count]['prefix'];
		
		
		/*the geocoded coords*/
        latitude = point.lat();
        longitude = point.lng();

		/*put the marker in the right place*/
      	var marker = new GMarker(point,{title: prefix});	
      	
      	/*so we can access the markers in the future*/
      	alert("Setting New Marker from Geocoding: "+bnode);
		mapMarkers[bnode] = marker;
		map.addOverlay(marker);
		map.setCenter(point);	
		createAddressDiv(title,address,bnode,container,latitude,longitude, prefix);
	}			
}

/*creates divs for addresses*/
function createAddressDiv(title,address,bNodeKey,containerElement, latitude, longitude, prefix){

	/*TODO: need to worry about how we pick all of this stuff up when we save and this method can easily be made shorter*/
	var locationDiv = createLocationElement(containerElement, bNodeKey, prefix+'Address',true);
	
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

/*add markers for all the contact:Nearest airports*/
function addNearestAirportMarker(nearestAirport,containerElement,map){
	
	if(!nearestAirport || typeof(nearestAirport) == 'undefined'){
		return;
	}
	
	var geocoder = new GClientGeocoder();
	var icaoCode = null;
	var iataCode = null;
	
	if(nearestAirport['icaoCode']){
		icaoCode = nearestAirport['icaoCode'];		
	} 
	if(nearestAirport['iataCode']){
		iataCode = nearestAirport['iataCode'];
	}
	
	//TODO: possibly render both of these codes
	if(iataCode){
		geocoder.getLatLng(
    	iataCode,
    		function(point) {
      			if (!point) {
        			//TODO: possibly do something here, maybe do nothing
      			} else {
        			var marker = new GMarker(point,{title: "nearestAirport"});
    				mapMarkers["nearestAirport"] = marker;
        			map.addOverlay(marker);
        			
        			createAirportDiv(point.lat(),point.lng(),iataCode,icaoCode,containerElement);
        			
        			//TODO:need to have something that decides sensibly where to centre the map
      				map.setCenter(point, 13);
      			}			
   			}
   		);
	} 
	else if(icaoCode){
		geocoder.getLatLng(
    	icaoCode,
    		function(point) {
      			if (!point) {	
      				//TODO: possibly do something here, maybe do nothing
      			} else {
        			var marker = new GMarker(point,{title: "My Nearest Airport"});
        			mapMarkers["nearestAirport"] = marker;
        			map.addOverlay(marker);
        			createAirportDiv(point.lat(),point.lng(),iataCode,icaoCode,containerElement);
        			
      				map.setCenter(point, 13);
      			}			
   			}
   		);
	}
}

function createAirportDiv(latitude,longitude,iataCode,icaoCode,containerElement){
	var locationDiv = createLocationElement(containerElement, "nearestAirport");
	
	/*display the latitude and longitude coords and the codes for the airport*/
	var latitudeDiv = document.createElement('div');
	latitudeDiv.appendChild(document.createTextNode('Latitude: '+latitude));
	latitudeDiv.className = 'latitude';
	locationDiv.appendChild(latitudeDiv);
		
	var longitudeDiv = document.createElement('div');
	longitudeDiv.appendChild(document.createTextNode('Longitude: '+longitude));
	longitudeDiv.className = 'longitude';
	locationDiv.appendChild(longitudeDiv);
	
	/*actually display the airport code(s)*/
	//TODO: add some sort of geocoding to redraw the pin when one of these is changed
	/*TODO: Need to make airport name lookupperable both ways so that people can edit it... it's not easily editable at the moment
	 * and so that the name and address of teh airport can be shown.*/	
	if(icaoCode){
		//label
		var icaoLabelElement = document.createElement('div');
		icaoLabelElement.appendChild(document.createTextNode('ICAO Code: '));
		icaoLabelElement.className = 'icaoLabel';
				
		//input element
		var icaoInputElement = document.createElement('input');
		icaoInputElement.value = icaoCode;
		icaoInputElement.className = 'icaoCode'
		
		//attach them
		locationDiv.appendChild(icaoLabelElement);
		locationDiv.appendChild(icaoInputElement);
	}
	if(iataCode){
		//label
		var iataLabelElement = document.createElement('div');
		iataLabelElement.appendChild(document.createTextNode('IATA Code: '));
		iataLabelElement.className = 'iataLabel';
		
		//input element
		var iataInputElement = document.createElement('input');
		iataInputElement.value = iataCode;
		iataInputElement.className = 'iataCode';
		
		//attach them
		locationDiv.appendChild(iataLabelElement);
		locationDiv.appendChild(iataInputElement);
	}
}


/*add markers for all the foaf:based_near elements*/
function addBasedNearMarkers(basedNearArray, containerElement){
	
	/*loop over each based_near instance*/
	for(bNodeKey in basedNearArray){
		/*create an element to hold each location*/
		var locationDiv = createLocationElement(containerElement, bNodeKey);
		
		/*title: e.g. home address, office address etc*/
		var basedNearTitleDiv = document.createElement('div');
		var title = 'I\'m Based Near...';
		basedNearTitleDiv.className = 'addressTitle';
		basedNearTitleDiv.appendChild(document.createTextNode(title));
		locationDiv.appendChild(basedNearTitleDiv);
	
		var latitude = basedNearArray[bNodeKey]['latitude'];
		var longitude = basedNearArray[bNodeKey]['longitude'];
		
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
			
		createSingleBasedNearMarker(latitude, longitude, locationDiv.id,map);	
	  }


}

function createSingleBasedNearMarker(latitude, longitude, holderName, map){

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
	    map.setCenter(point);	
}

/*callback function for reverse geocode which puts the address information in the based near box*/
function updateBasedNearAddress(placemark){
	
	  /*so we use the right variables for each request*/
	if(typeof(updateBasedNearAddress.count) == 'undefined'){
	  	updateBasedNearAddress.count = 0;
	} else{
		updateBasedNearAddress.count++;
	}
	//alert(basedNearDetailsToGeoCode[updateBasedNearAddress.count]['containerId']);
	
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
	//alert(placemark.Placemark[0].address);
}

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

/*Render the HomepageField*/
function renderHomepageFields(data){

	if(!data || !data.foafHomepageFields || typeof(data.foafHomepageFields) == 'undefined'){
		return;
	}
	
	/*build the container*/
	var name = data.foafHomepageFields.name;
	var label = data.foafHomepageFields.displayLabel;
	var containerElement = createFieldContainer(name, label);
	
	/*Get the values for the date*/
	//var values = data.foafHomepageFields['values'];

	/*render each individual phone element*/	
	var i =0;
	if(typeof(data.foafHomepageFields.values) != 'undefined' && data.foafHomepageFields.values){
		for(phoneNumber in data.foafHomepageFields.values){
			createGenericInputElement(name, data.foafHomepageFields.values[phoneNumber], i);	
			i++;
		}
	}

	/*create an add link*/
	createGenericAddElement(containerElement,name,label);

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
function renderSimpleFields(data){
	if(!data || !data.fields || typeof(data.fields) == 'undefined'){
		return;
	}
	
	for(element in data){
		if(element == 'fields'){
			for(fieldType in data[element]){
				var containerElement = createFieldContainer(data[element][fieldType]['name'], data[element][fieldType]['displayLabel']);
				var i=0;
				
				if(data[element][fieldType]['values'].length != 0){
					for(item in data[element][fieldType]['values']){
						createGenericInputElement(data[element][fieldType]['name'], data[element][fieldType]['values'][item], i);	
						i++;	
					}	
				} else {
					//create an empty field
					createGenericInputElement(data[element][fieldType]['name'], '', 0);	
				}
				/*create an add link*/
				createGenericAddElement(containerElement,data[element][fieldType]['name'],data[element][fieldType]['displayLabel']);
			}
		}
	}	
}

/*renders the appropriate phone fields*/
function renderPhoneFields(data){
	if(!data || !data.foafPhoneFields || typeof(data.foafPhoneFields) == 'undefined'){
		return;
	}
	
	/*build the container*/
	var name = data.foafPhoneFields.name;
	var label = data.foafPhoneFields.displayLabel;
	var containerElement = createFieldContainer(name, label);

	/*render each individual phone element*/	
	var i =0;
	if(typeof(data.foafPhoneFields.values) != 'undefined' && data.foafPhoneFields.values){
		for(phoneNumber in data.foafPhoneFields.values){
			createGenericInputElement(name, data.foafPhoneFields.values[phoneNumber], i);	
			i++;
		}
	}
	
	/*create an add link*/
	createGenericAddElement(containerElement,name,label);

}

/*renders the appropriate phone fields*/
function renderMboxFields(data){
	if(!data || !data.foafMboxFields || typeof(data.foafMboxFields) == 'undefined'){
		return;
	}
	
	/*build the container*/
	var name = data.foafMboxFields.name;
	var label = data.foafMboxFields.displayLabel;
	var containerElement = createFieldContainer(name, label);

	/*render each individual phone element*/	
	var i =0;
	if(typeof(data.foafMboxFields.values) != 'undefined' && data.foafMboxFields.values){
		for(mbox in data.foafMboxFields.values){
			createGenericInputElement(name, data.foafMboxFields.values[mbox], i);	
			i++;
		}
	}
	
	/*create an add link*/
	createGenericAddElement(containerElement,name,label);

}


/*populates the triples objects with stuff from the actual display (i.e. what the user has changed)*/
//TODO: datatypes/language
function displayToObjects(name){  
	switch(name){
		case 'load-the-basics':
			birthdayDisplayToObjects();
			simpleFieldsDisplayToObjects();
			homepageFieldsDisplayToObjects();
			break;
		case 'load-contact-details':
			addressDisplayToObjects();
			nearestAirportDisplayToObjects();
			basedNearDisplayToObjects();
			mboxDisplayToObjects();
			phoneDisplayToObjects();
			break;
		case 'load-accounts':
			accountsDisplayToObjects();
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
	
	//TODO MISCHA
	//birthdayDisplayToObjects();
	//simpleFieldsDisplayToObjects();
}

function otherDisplayToObjects(){
	//TODO: do this
	alert("Saving rdf text... do this!!!");
}

/*this is more or less identical to imgDisplayToObjects which is possibly not a good thing*/
function knowsDisplayToObjects(){
	var mutualFriendsContainer = document.getElementById('mutualFriends_container')
	var userKnowsContainer = document.getElementById('userKnows_container');
	
	//TODO
	alert("Saving friends... need to implement this Luke");
	
}

/*this is more or less identical to imgDisplayToObjects which is possibly not a good thing*/
function depictionDisplayToObjects(){
	var containerElement = document.getElementById('foafDepiction_container');
	
	if(containerElement && typeof(globalFieldData.foafDepictionFields != 'undefined') && globalFieldData.foafDepictionFields){
		if(typeof(globalFieldData.foafDepictionFields.images) != 'undefined'){
		
			/*remove all existing images in globalFieldData*/		
			globalFieldData.foafDepictionFields.images = new Array();
			
			/*add the elements that are present in the display again*/
			for(i=0 ; i <containerElement.childNodes.length ; i++){
				
				var element = containerElement.childNodes[i];
					
				/*take the various attributes of the image tag and add them to the globalFieldData object*/
				if(element.className == 'image'){	
				
					var thisImageArray = new Object();
										
					if(typeof(element.src) != 'undefined' && element.src){
						thisImageArray.uri = element.src;
					}
					if(typeof(element.title) != 'undefined' && element.title){
						thisImageArray.title= element.title;
					}
					//TODO: need to actually set alt when the image is rendered
					if(typeof(element.alt) != 'undefinied' && element.alt){
						thisImageArray.description = element.alt;
					}
					globalFieldData.foafDepictionFields.images.push(thisImageArray);
				}//end if
			}//end for	
		}//end if	
	}//end if
}

function imgDisplayToObjects(){
	var containerElement = document.getElementById('foafImg_container');
	
	if(containerElement && typeof(globalFieldData.foafImgFields != 'undefined') && globalFieldData.foafImgFields){
		if(typeof(globalFieldData.foafImgFields.images) != 'undefined'){
		
			/*remove all existing images in globalFieldData*/		
			globalFieldData.foafImgFields.images = new Array();
			
			/*add the elements that are present in the display again*/
			for(i=0 ; i <containerElement.childNodes.length ; i++){
				
				var element = containerElement.childNodes[i];
					
				/*take the various attributes of the image tag and add them to the globalFieldData object*/
				if(element.className == 'image'){	
					var thisImageArray = new Object();
										
					if(typeof(element.src) != 'undefined' && element.src){
						thisImageArray.uri = element.src;
					}
					if(typeof(element.title) != 'undefined' && element.title){
						thisImageArray.title= element.title;
					}
					//TODO: need to actually set alt when the image is rendered
					if(typeof(element.alt) != 'undefinied' && element.alt){
						thisImageArray.description = element.alt;
					}
					globalFieldData.foafImgFields.images.push(thisImageArray);
				}//end if
			}//end for	
		}//end if	
	}//end if
}

function simpleFieldsDisplayToObjects(){
	
	if(typeof(globalFieldData.fields) != 'undefined' && globalFieldData.fields){
		for(simpleField in globalFieldData.fields){
			var containerElement = document.getElementById(simpleField + "_container");
			
			if(containerElement){
				/*remove all existing elements in globalFieldData*/
				if(globalFieldData.fields[simpleField].values){
					 globalFieldData.fields[simpleField].values = new Array();
				}
				
				/*add the elements that are present in the display again*/
				for(i=0 ; i <containerElement.childNodes.length ; i++){
					var element = containerElement.childNodes[i];
					if(element.className == 'fieldInput' && element.value != null){
						globalFieldData.fields[simpleField].values.push(element.value);	
					}
				}
			} 
		}
	}	
}

//TODO MISCHA
function homepageFieldsDisplayToObjects() {
	var containerElement = document.getElementById('foafHomepage_container');	

	if(containerElement && typeof(globalFieldData.foafHomepageFields != 'undefined') && globalFieldData.foafHomepageFields) {
		if(typeof(globalFieldData.foafHomepageFields.values) != 'undefined'){
			
			/*remove the existing values*/
			globalFieldData.foafHomepageFields.values = new Array();
			
			/*add the elements that are present in the display again*/
			for(i=0 ; i <containerElement.childNodes.length ; i++){
				
				var element = containerElement.childNodes[i];
					
				if(element.className == 'fieldInput'){	
					globalFieldData.foafHomepageFields.values.push(element.value);
				}//end if
			}//end for	
		}//end if	
	}
}

function mboxDisplayToObjects(){
	var containerElement = document.getElementById('foafMbox_container');
	
	if(containerElement && typeof(globalFieldData.foafMboxFields != 'undefined') && globalFieldData.foafMboxFields){
		if(typeof(globalFieldData.foafMboxFields.values) != 'undefined'){
			
			/*remove the existing values*/
			globalFieldData.foafMboxFields.values = new Array();
			
			/*add the elements that are present in the display again*/
			for(i=0 ; i <containerElement.childNodes.length ; i++){
				
				var element = containerElement.childNodes[i];
					
				/*take the various attributes of the image tag and add them to the globalFieldData object*/
				if(element.className == 'fieldInput'){	
					globalFieldData.foafMboxFields.values.push(element.value);
				}//end if
			}//end for	
		}//end if	
	}//end if	
}

function phoneDisplayToObjects(){
	var containerElement = document.getElementById('foafPhone_container');
	
	if(containerElement && typeof(globalFieldData.foafPhoneFields != 'undefined') && globalFieldData.foafPhoneFields){
		if(typeof(globalFieldData.foafPhoneFields.values) != 'undefined'){
			
			/*remove the existing values*/
			globalFieldData.foafPhoneFields.values = new Array();
			
			/*add the elements that are present in the display again*/
			for(i=0 ; i <containerElement.childNodes.length ; i++){
				
				var element = containerElement.childNodes[i];
					
				/*take the various attributes of the image tag and add them to the globalFieldData object*/
				if(element.className == 'fieldInput'){	
					globalFieldData.foafPhoneFields.values.push(element.value);
				}//end if
			}//end for	
		}//end if	
	}//end if	
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

/*put nearestAirport data into the globalFieldData objects*/
function nearestAirportDisplayToObjects(){

	var containerElement = document.getElementById('nearestAirport_container');
	
	for(i=0; i < containerElement.childNodes.length; i++){
	
		var locationElement = containerElement.childNodes[i];
		
		if(locationElement.id != 'mapDiv'){
			/*loop through the elements to make sure we save the right ones*/
			for(j=0; j < locationElement.childNodes.length; j++){
			
				if((locationElement.childNodes[j].className == 'latitude' || locationElement.childNodes[j].className == 'longitude')
					&& locationElement.childNodes[j].childNodes[0] && locationElement.childNodes[j].childNodes[0].nodeValue){
					
						var coordArray = locationElement.childNodes[j].childNodes[0].nodeValue.split(' ');
						
						if(typeof(coordArray[1]) != 'undefined' && coordArray[1]){
							globalFieldData.nearestAirportFields['nearestAirport'][locationElement.childNodes[j].className] = coordArray[1];
						}
						
				} else if(locationElement.childNodes[j].className == 'iataCode' || locationElement.childNodes[j].className == 'icaoCode'){
						globalFieldData.nearestAirportFields['nearestAirport'][locationElement.childNodes[j].className] = locationElement.childNodes[j].value;	
				}
			}
		}
	}
}

/*put basedNear data into the globalFieldData objects*/
function basedNearDisplayToObjects(locationElement){
	var containerElement = document.getElementById('basedNear_container');
	
	for(i=0; i < containerElement.childNodes.length; i++){
	
		var locationElement = containerElement.childNodes[i];
		
		if(locationElement.id != 'mapDiv'){
			/*loop through the elements to make sure we save the right ones*/
			for(j=0; j < locationElement.childNodes.length; j++){
				if((locationElement.childNodes[j].className == 'latitude' || locationElement.childNodes[j].className == 'longitude')
					&& locationElement.childNodes[j].childNodes[0] && locationElement.childNodes[j].childNodes[0].nodeValue){
						var coordArray = locationElement.childNodes[j].childNodes[0].nodeValue.split(' ');
		
						if(typeof(coordArray[1]) != 'undefined' && coordArray[1]){
							globalFieldData.basedNearFields['basedNear'][locationElement.id][locationElement.childNodes[j].className] = coordArray[1];
						}
				} 
			}
		}
	}
}

/*bNodeToPanTo is the bnode the map should pan to, if any*/
function addressDisplayToObjects(){
	placeAddressDisplayToObjects('home');
	placeAddressDisplayToObjects('office');
}

/*copies values from display for an address of type prefix (e.g. office, home) into the globalFieldData object
bNodeToPanTo specifies the bnode that the map should pan to, if any.*/
function placeAddressDisplayToObjects(prefix,bNodeToPanTo){
   	
   	//clear out the existing 'home' and 'office' properties
	globalFieldData.addressFields[prefix] = new Object();
	
   	var containerElement = document.getElementById('address_container');
	
	for(i=0; i < containerElement.childNodes.length; i++){
	
		var locationElement = containerElement.childNodes[i];
		
		if(locationElement.id != 'mapDiv' && locationElement.className == prefix+'Address'){
			//create a new array for this particular address
			globalFieldData.addressFields[prefix][locationElement.id] = new Object();
			var isAddress = false;
			
			for(j=0; j < locationElement.childNodes.length; j++){
				
					/*address*/
					if(locationElement.childNodes[j].id == 'street'){
							isAddress=true;
							globalFieldData.addressFields[prefix][locationElement.id][prefix+'Street'] = locationElement.childNodes[j].value;
					} 
					if(locationElement.childNodes[j].id == 'street2'){
							isAddress=true;
							globalFieldData.addressFields[prefix][locationElement.id][prefix+'Street2'] = locationElement.childNodes[j].value;
					} 
					if(locationElement.childNodes[j].id == 'street3'){
							isAddress=true;
							globalFieldData.addressFields[prefix][locationElement.id][prefix+'Street3'] = locationElement.childNodes[j].value;
					} 
					if(locationElement.childNodes[j].id == 'postalCode'){
							isAddress=true;
							globalFieldData.addressFields[prefix][locationElement.id][prefix+'PostalCode'] = locationElement.childNodes[j].value;
					} 
					if(locationElement.childNodes[j].id == 'city'){
							isAddress=true;
							globalFieldData.addressFields[prefix][locationElement.id][prefix+'City'] = locationElement.childNodes[j].value;
					}
					if(locationElement.childNodes[j].id == 'country'){
							isAddress=true;
							globalFieldData.addressFields[prefix][locationElement.id][prefix+'Country'] = locationElement.childNodes[j].value;
					}
					if(locationElement.childNodes[j].id == 'postalCode'){
							isAddress=true;
							globalFieldData.addressFields[prefix][locationElement.id][prefix+'Country'] = locationElement.childNodes[j].value;
					}
					
				}
				
				if(isAddress){
					var doPan = false;
					//only pan the map if a bnode has been passed in to pan it
					if(typeof(bNodeToPanTo) != 'undefined' && locationElement.id==bNodeToPanTo){
						//alert("topanto: "+bNodeToPanTo);
						doPan = true
					}
					//do the geo coding to get lat and long
					geoCodeExistingAddress(locationElement.id,prefix,doPan);
				}
			}
		
	}
}
	
/*geocodes the address and updates the latitude/longitude fields and sets the appropriate element in the globalFieldData object*/
function geoCodeExistingAddress(bNodeKey,prefix,doPan){

		/*display the map*/
	   	var mapDiv = document.getElementById('mapDiv');
	   	if(mapDiv && typeof(mapDiv) != 'undefined'){
	   		mapDiv.style.display = 'inline';
	   		mapDiv.style.position = 'absolute';
	   		mapDiv.style.left = (parseFloat(findPosX(document.getElementById('address_container')))-400)+'px'; 
	   		mapDiv.style.top = findPosY(document.getElementById('address_container'))+'px';
	   	}
	   
		var addressArray = getProperties(globalFieldData.addressFields[prefix][bNodeKey]);//get the address
		
		//some variables for the callback function
		var theseDetails = new Array();
		theseDetails['bnode'] = bNodeKey;	
		theseDetails['prefix'] = prefix;
		theseDetails['doPan'] = doPan;//whether to pan or not
		existingAddressDetailsToGeoCode.push(theseDetails);
		
		/*do the geocoding*/
		var geocoder = new GClientGeocoder();
		geocoder.getLatLng(addressArray,displayToObjectsGeoCode);
	 
}

function displayToObjectsGeoCode(point) {
	    	if (!point) {
	        	//TODO: possibly do something here, maybe do nothing
	      	} else {
	      	
	      		/*so we use the right variables for each request*/
	  			if(typeof(displayToObjectsGeoCode.count) == 'undefined'){
	  				displayToObjectsGeoCode.count = 0;
	  			} else{
	  				displayToObjectsGeoCode.count++;
	  			}
	      		//move the point and the centre of the map
	      		//TODO: this should be done in the same way as the other geocode.
	      		mapMarkers[existingAddressDetailsToGeoCode[displayToObjectsGeoCode.count]['bnode']].setLatLng(point);
			
	      		//update the display to show the new latitude and longitude
				updateLatLongText(existingAddressDetailsToGeoCode[displayToObjectsGeoCode.count]['bnode'],mapMarkers[existingAddressDetailsToGeoCode[displayToObjectsGeoCode.count]['bnode']]);

				//set the global data with the appropriate stuff
				globalFieldData.addressFields[existingAddressDetailsToGeoCode[displayToObjectsGeoCode.count]['prefix']][existingAddressDetailsToGeoCode[displayToObjectsGeoCode.count]['bnode']]['latitude'] = point.lat();
				globalFieldData.addressFields[existingAddressDetailsToGeoCode[displayToObjectsGeoCode.count]['prefix']][existingAddressDetailsToGeoCode[displayToObjectsGeoCode.count]['bnode']]['longitude'] = point.lng();
				
				if(existingAddressDetailsToGeoCode[displayToObjectsGeoCode.count]['doPan']){
					map.panTo(point);
					//alert(existingAddressDetailsToGeoCode[displayToObjectsGeoCode.count]['bnode']);
	      		}
	      	}			
}
	   	
function accountsDisplayToObjects(){

	/*TODO This will change when the display is improved + need a bit less hardcoding possibly*/
  	var containerElement = document.getElementById('foafHoldsAccount_container');
  	
  	if(!containerElement){
  		return;
  	}
  	
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
      	document.body.appendChild(mapDiv);
        
        var map = new GMap2(mapDiv);
        map.setCenter(new GLatLng(37.4419, -122.1419), 13);
        
        var mapControl = new GSmallMapControl();
		map.addControl(mapControl);
		
        return map;
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

function createGenericAddElement(container,name,displayLabel,onClick){

	/*create add link and attach it to the container*/
	var addDiv = document.createElement("div");
	addDiv.id = name+"_addLinkContainer";
	addDiv.className = "addLinkContainer";
	var addLink = document.createElement('a');
	addLink.appendChild(document.createTextNode("+Add another "+displayLabel));
	addLink.className="addLink";
	
	if(!onClick){
		addLink.setAttribute("onclick" , "createGenericInputElementAboveAddLink('"+name+"',this.parentNode.parentNode.childNodes.length,'"+container.id+"',this.parentNode.id);");
	} else {
		//TODO: add an option to set the onclick attribute from an argument
		alert("calling an incomplete function");
	}
	addDiv.appendChild(addLink);
	container.appendChild(addDiv);

}
//TODO: can we get rid of thisElementCount?
function createGenericInputElementAboveAddLink(name,thisElementCount,containerId,addElementId){
	
	/*remove the add element*/
	var addElement = document.getElementById(addElementId);
	addElement.parentNode.removeChild(addElement);
	
	/*append a child node*/
	createGenericInputElement(name,'',thisElementCount,containerId);
	
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

/*creates a remove link with the removeId being the input element to be removed*/
function createRemoveFriendsLink(removeId,containerId,isMutual){
	
	/*create remove link and attach it to the container div*/
	var containerDiv = document.getElementById(containerId);
	if(containerDiv){
		var removeDiv = document.createElement("div");
		removeDiv.id = removeId+"removeLinkContainer";
		removeDiv.className = "friendRemoveLinkContainer";
		var removeLink = document.createElement('a');
		removeLink.appendChild(document.createTextNode("- Remove"));
		removeLink.id="_removeLink";
		removeLink.className="removeLink";

		if(isMutual){
			removeLink.setAttribute("onclick" , "removeMutualFriendElement('"+removeId+"','"+removeDiv.id+"');");
		} else {
			removeLink.setAttribute("onclick" , "removeGenericInputElement('"+removeId+"','"+removeDiv.id+"');");
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
		makeFriendLink.appendChild(document.createTextNode("-Confirm"));
		makeFriendLink.className="removeLink";

		makeFriendLink.setAttribute("onclick" , "makeMutualFriend('"+friendDiv.id+"');");
		
		makeFriendDiv.appendChild(makeFriendLink);
		friendDiv.appendChild(makeFriendDiv);
	}
}

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
						ifp = friendDiv.childNodes[childNode].childNodes[grandChildNode].href;
					}
				}
			}
		}
	}
	
	/*create an array with the information about the friend in it*/
	var friend=new Array();
	if(ifp){
		friend['ifps'] = new Array();
		friend['ifps'][0] = ifp;
	} 
	if(name){
		friend['name'] = name;
	}
	if(img){
		friend['img'] = img;
	}
	
	return friend;
	
}

/*remove the mutual friend whose div is given by the id removeId*/
function removeMutualFriendElement(removeId,removeDivId){
	var friend = getFriendInfoFromElement(removeId);
	
	/*the container that we want to stick it in*/
	var containerElement = document.getElementById('knowsUser_container');
	
	/*create the new element*/
	if(containerElement){
		var friendDiv = createFriendElement('knowsUser',friend,containerElement.childNodes.length,containerElement);
		createMakeMutualFriendLink(friendDiv);
	}
	
	/*remove the old one*/
	removeGenericInputElement(removeId,removeDivId);
	
}

/*converts a user that knows you to one that you know*/
function makeMutualFriend(friendDivId){

	var friend = getFriendInfoFromElement(friendDivId);
	
	/*the container that we want to stick it in*/
	var containerElement = document.getElementById('mutualFriends_container');

	/*create the new element*/
	if(containerElement){
		var friendDiv = createFriendElement('mutualFriend',friend,containerElement.childNodes.length,containerElement);
		createRemoveFriendsLink(friendDiv.id,friendDiv.id,true);
	}

	/*remove the old one*/
	removeGenericInputElement(friendDivId,'id');
}

/*removes the input element with the given id as well as its corresponding remove element*/
//TODO: this is badly named
function removeGenericInputElement(inputIdForRemoval, removeDivId, isImage){
	/*Get the ids*/
	var inputElement = document.getElementById(inputIdForRemoval);
	var removeElement = document.getElementById(removeDivId);
	if(isImage){
		var source = inputElement.src;
		$.post("/file/remove-image", {filename: source}, function(){saveFoaf();},null);
	}
	
	/*remove the old element*/
	inputElement.parentNode.removeChild(inputElement);
	removeElement.parentNode.removeChild(removeElement);

}

/*creates an element to hold the information about a particular location*/
function createLocationElement(attachElement, bnodeId,optionalClassName,softRemove){
	
	/*if new, create a random id*/
	if(!bnodeId){
		var bnodeId = createRandomString(50);
	}
	if(!optionalClassName){
		var optionalClassName = 'location';
	}
	
	/*create holdsAccount div and attach it to the element given*/
	var locationDiv = document.createElement("div");
	locationDiv.setAttribute('class',optionalClassName);
	locationDiv.id = bnodeId;
	locationDiv.setAttribute("onclick","map.panTo(mapMarkers['"+bnodeId+"'].getLatLng())");
	attachElement.appendChild(locationDiv);
	
	/*create remove link and attach it to the location div*/
	var removeDiv = document.createElement("div");
	removeDiv.id = "removeLinkContainer";
	removeDiv.className = "removeLinkContainer";
	var removeLink = document.createElement('a');

	if(!softRemove){
		removeLink.appendChild(document.createTextNode("- Remove this location"));
		removeLink.setAttribute("onclick" , "map.removeOverlay(mapMarkers[this.parentNode.parentNode.id]);this.parentNode.parentNode.parentNode.removeChild(this.parentNode.parentNode);");
	} else {
		//XXX don't allow them to remove this.
	}
	removeLink.id="removeLink";
	removeLink.className="removeLink";
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
	
	//if the contname does not choose a  specific container we want to put it in
	if(!contname){
		contname = name+'_container';
	} 

	createGenericInputElementRemoveLink(newElement.id,contname);
	
	document.getElementById(contname).appendChild(newElement);
	newElement.setAttribute('class','fieldInput');
	
	
	return newElement;
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
		nameLink.appendChild(document.createTextNode(name));
		nameLink.href = 'http://foaf.qdos.com/find/?q='+ifp;
		
		nameDiv.appendChild(nameLink);
		friendDiv.appendChild(nameDiv);
	}
	return friendDiv;

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
