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
			
	var errorConsole = document.getElementById('uri').value;
			alert(errorConsole);
	$.get("/ajax/load-extractor",{flickr: flickr, lastfmUser: lastfmUser, delicious: delicious, uri: uri} , function(data){
			
			if(typeof(data) == 'undefined' || !data){
				return;
			}
			
			document.getElementById('flickr_error').style.display = 'none';
			document.getElementById('delicious_error').style.display = 'none';
			document.getElementById('lastfm_error').style.display = 'none';
			document.getElementById('uri_error').style.display = 'none';
			
			if(typeof(data.flickrFound)=='undefined' || !data.flickrFound){
				document.getElementById('flickr_error').style.display = 'inline';			
			} 
			if(typeof(data.deliciousFound)=='undefined' || !data.deliciousFound){
				document.getElementById('delicious_error').style.display = 'inline';
			} 
			if(typeof(data.lastfmFound)=='undefined' || !data.lastfmFound){
				document.getElementById('lastfm_error').style.display = 'inline';
			} 
			if(typeof(data.uriFound)=='undefined' || !data.uriFound){
				document.getElementById('uri_error').style.display = 'inline';
			} 
			
			turnOffLoading();
			
		}, 'json');			
}