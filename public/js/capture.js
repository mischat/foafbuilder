/*display/hide the spinner*/
function turnOffLoading(){
	document.getElementById('ajaxLoader').style.display = 'none';
}
function turnOnLoading(){
	document.getElementById('ajaxLoader').style.display = 'inline';	
}

function get_cookie_id() {
	var returnvalue = '';
	if (document.cookie.length > 0) {
		offset = document.cookie.indexOf('PHPSESSID=');
		if (offset != -1) { // if cookie exists
			offset += 'PHPSESSID='.length;
			// set index of beginning of value
			end = document.cookie.length;
			returnvalue=unescape(document.cookie.substring(offset, end))
		}
	}
	return returnvalue;
} 

function importFoaf(){
	turnOnLoading();
	
	var flickr = document.getElementById('flickr').value;
	var lastfmUser = document.getElementById('lastfmUser').value;
	var lj = document.getElementById('lj').value;
	var uri = document.getElementById('uri').value;

	$.get("/ajax/load-extractor",{key : get_cookie_id(), flickr: flickr, lastfmUser: lastfmUser, lj: lj, uri: uri} , function(data){
			
		if(typeof(data) == 'undefined' || !data){
			return;
		}

		var errors = 0;		
		document.getElementById('flickr_error').style.display = 'none';
		document.getElementById('lastfm_error').style.display = 'none';
		document.getElementById('lj_error').style.display = 'none';
		document.getElementById('uri_error').style.display = 'none';
		
		if(flickr && (typeof(data.flickrFound)=='undefined' || !data.flickrFound)){
			document.getElementById('flickr_error').style.display = 'inline';			
			errors++;
		} 
		if(lastfmUser && (typeof(data.lastfmFound)=='undefined' || !data.lastfmFound)){
			document.getElementById('lastfm_error').style.display = 'inline';
			errors++;
		} 
		if(lj && (typeof(data.ljFound)=='undefined' || !data.ljFound)){
			document.getElementById('lj_error').style.display = 'inline';
			errors++;
		} 
		if(uri && (typeof(data.uriFound)=='undefined' || !data.uriFound)){
			document.getElementById('uri_error').style.display = 'inline';
			errors++;
		} 
		turnOffLoading();
		if (errors == 0) {
			window.location = '/builder/';
		}
	}, 'json');			
}
