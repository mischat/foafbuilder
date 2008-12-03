/*display/hide the spinner*/
function turnOffLoading(){
	document.getElementById('ajaxLoader').style.display = 'none';
}
function turnOnLoading(){
	document.getElementById('ajaxLoader').style.display = 'inline';	
}

function importFoaf(){
	turnOnLoading();
	
	var flickr = document.getElementById('flickr').value;
	var lastfmUser = document.getElementById('lastfmUser').value;
	var delicious = document.getElementById('delicious').value;
	var uri = document.getElementById('uri').value;

	$.get("/ajax/load-extractor",{flickr: flickr, lastfmUser: lastfmUser, delicious: delicious, uri: uri} , function(data){
			
		if(typeof(data) == 'undefined' || !data){
			return;
		}

		var errors = 0;		
		document.getElementById('flickr_error').style.display = 'none';
		document.getElementById('delicious_error').style.display = 'none';
		document.getElementById('lastfm_error').style.display = 'none';
		document.getElementById('uri_error').style.display = 'none';
		
		if(flickr && (typeof(data.flickrFound)=='undefined' || !data.flickrFound)){
			document.getElementById('flickr_error').style.display = 'inline';			
			errors++;
		} 
		if(delicious != "" && (typeof(data.deliciousFound)=='undefined' || !data.deliciousFound)){
			document.getElementById('delicious_error').style.display = 'inline';
			errors++;
		} 
		if(lastfmUser && (typeof(data.lastfmFound)=='undefined' || !data.lastfmFound)){
			document.getElementById('lastfm_error').style.display = 'inline';
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
