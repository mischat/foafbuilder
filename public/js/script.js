/*loads all the foaf data from the given file into the editor.*/
function loadFoaf(){

	//use json data and use addElement etc. stuff rather than using innerHTML
	url = document.getElementById('foafUri').value;
	if(url){
  		$.post("/ajax/load-foaf", { uri: url}, function(data){populate(data);}, "json");
	}
}

function populate(data){
  	//TODO: add for loops to populate multiple fields here 
  	for(i = 0 ; i < data.length ; i++){
	  	if(data[i].name.label){
	  		if(i == 0){
				document.getElementById('name').value = data[i].name.label;
			} else {
				//document.getElementById('name_container');
			}
		}
		if(data[i].homepage.uri){
			if(i == 0){
				document.getElementById('homepage').value = data[i].homepage.uri;
			} else {
			
			}
		}
		if(data[i].nick.label){
			if(i == 0){
				document.getElementById('nick').value = data[i].nick.label;
			} else {
			
			}
		}
	}
}

/*saves all the foaf data TODO: it might be a challenge making this quick*/
function saveFoaf(){
	url = document.getElementById('foafUri').value;
	if(url){
  		$.post("/ajax/save-foaf", { uri: url},
  			function(data){
    			alert(data.name); // John
    			console.log(data.time); //  2pm
		  }, 
		"json");
	}
}
