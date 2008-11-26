function importFoaf(){
	
	var flickr = document.getElementById('flickr').value;
	var lastfmUser = document.getElementById('lastfmUser').value;
	var delicious = document.getElementById('delicious').value
			
	$.get("/ajax/load-extractor",{flickr: flickr, lastfmUser: lastfmUser, delicious: delicious} , function(data){
			
			if(typeof(data) == 'undefined' || !data){
				return;
			}
			
			document.getElementById('flickr_error').style.display = 'none';
			document.getElementById('delicious_error').style.display = 'none';
			document.getElementById('lastfm_error').style.display = 'none';
			
			if(typeof(data.flickrFound)!='undefined' && data.flickrFound){
				document.getElementById('flickr_error').style.display = 'inline';			
			} 
			if(typeof(data.flickrFound)!='undefined' && data.deliciousFound){
				document.getElementById('delicious_error').style.display = 'inline';
			} 
			if(typeof(data.flickrFound)!='undefined' && data.lastfmFound){
				document.getElementById('lastfm_error').style.display = 'inline';
			} 
			
			alert(data);
			
		}, null);			
}