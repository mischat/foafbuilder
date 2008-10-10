/**
*
*  AJAX IFRAME METHOD (AIM)
*  http://www.webtoolkit.info/
*
**/
/*this callback is currently only used for the image upload*/
function startCallback() {
	// make something useful before submit (onStart)
    return true;
}
/*this callback is currently only used for the image upload*/
function uploadCallback_foafDepiction(response) {
	if(response && response != '0'){
		previewImage('foafDepiction_container','foafDepiction',response);
		
	} else {
		//TODO: do jquery error stuff here
		alert("Sorry, something went wrong uploading the image.");
	}
}

/*this callback is currently only used for the image upload*/
function uploadCallback_foafImg(response) {
	if(response){
		previewImage('foafImg_container','foafImg',response);
		
	} else {
		//TODO: do jquery error stuff here
		alert("Sorry, something went wrong uploading the image.");
	}
}

/*this callback is currently only used for the image upload*/
function removeCallback(response) {
	if(response){
		alert('image removed');
		
	} else {
		//TODO: do jquery error stuff here
		alert("Sorry, something went wrong removing the image.");
	}
}
     
AIM = {

    frame : function(c) {

        var n = 'f' + Math.floor(Math.random() * 99999);
        var d = document.createElement('DIV');
        d.innerHTML = '<iframe style="display:none" src="about:blank" id="'+n+'" name="'+n+'" onload="AIM.loaded(\''+n+'\')"></iframe>';
        document.body.appendChild(d);

        var i = document.getElementById(n);
        if (c && typeof(c.onComplete) == 'function') {
            i.onComplete = c.onComplete;
        }

        return n;
    },

    form : function(f, name) {
        f.setAttribute('target', name);
    },

    submit : function(f, c) {
        AIM.form(f, AIM.frame(c));
        if (c && typeof(c.onStart) == 'function') {
            return c.onStart();
        } else {
            return true;
        }
    },

    loaded : function(id) {
        var i = document.getElementById(id);
        if (i.contentDocument) {
            var d = i.contentDocument;
        } else if (i.contentWindow) {
            var d = i.contentWindow.document;
        } else {
            var d = window.frames[id].document;
        }
        if (d.location.href == "about:blank") {
            return;
        }

        if (typeof(i.onComplete) == 'function') {
            i.onComplete(d.body.innerHTML);
        }
    }

}