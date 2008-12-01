/*display/hide the spinner*/
function turnOffLoading(){
	document.getElementById('ajaxLoader').style.display = 'none';
}
function turnOnLoading(){
	document.getElementById('ajaxLoader').style.display = 'inline';	
}

function doOpenid(){
//	turnOnLoading();

	//TODO MISCHA	
	var openid_identifier = document.getElementById('openid_identifier').value;
	var openid_action = document.getElementById('openid_action').value;
//	var errorConsole = document.getElementById('openid').value;
			
	//$.post("/ajax/do-openid",{openid_identifier : openid_identifier, openid_action : openid_action} , function(data){
	$.post("/logon/do-openid",{openid_identifier : openid_identifier, openid_action : openid_action} , function(data){
			
			
		//	document.getElementById('openid_error').style.display = 'none';
			
		//	if(typeof(data.uriFound)=='undefined' || !data.uriFound){
		//		document.getElementById('openid_error').style.display = 'inline';
		//	} 
			
		//	turnOffLoading();
			
		}, 'json');			
}
