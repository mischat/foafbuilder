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
			
	$.post("/continue/do-openid",{openid_identifier : openid_identifier, openid_action : openid_action} , function(data) {}, 'json');			
}
