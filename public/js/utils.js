/**
*
*  AJAX IFRAME METHOD (AIM)
*  http://www.webtoolkit.info/
*
**/

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

function findPosX(obj)
  {
    var curleft = 0;
    if(obj.offsetParent)
        while(1) 
        {
          curleft += obj.offsetLeft;
          if(!obj.offsetParent)
            break;
          obj = obj.offsetParent;
        }
    else if(obj.x)
        curleft += obj.x;
    return curleft;
  }

  function findPosY(obj)
  {
    var curtop = 0;
    if(obj.offsetParent)
        while(1)
        {
          curtop += obj.offsetTop;
          if(!obj.offsetParent)
            break;
          obj = obj.offsetParent;
        }
    else if(obj.y)
        curtop += obj.y;
    return curtop;
  }


/*converts an object to array*/
function getProperties(obj) {
  var i, v;
  var count = 0;
  var props = [];
  if (typeof(obj) === 'object') {
    for (i in obj) {
      v = obj[i];
      if (v !== undefined && typeof(v) !== 'function') {
        props[count] = v;
        count++;
      }
    }
  }
  return props;
}


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