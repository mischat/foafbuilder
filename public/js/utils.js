/**
*
*  AJAX IFRAME METHOD (AIM)
*  http://www.webtoolkit.info/
*
**/
function include_dom(script_filename) {
    var html_doc = document.getElementsByTagName('head').item(0);
    var js = document.createElement('script');
    js.setAttribute('language', 'javascript');
    js.setAttribute('type', 'text/javascript');
    js.setAttribute('src', '/js/loadData.js');
    html_doc.appendChild(js);
    return false;
}

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
function uploadCallback_foafDepiction_private(response) {
	if(response && response != '0'){
		previewImage('foafDepiction_container','foafDepiction',response,false,false);
		
	} else {
		//TODO: do jquery error stuff here
		alert("Sorry, something went wrong uploading the image.");
	}
	resize();
}

/*this callback is currently only used for the image upload*/
function uploadCallback_foafImg_private(response) {
	if(response && response != '0'){
		log('Upload callback');
		previewImage('foafImg_container','foafImg',response,false,false);
		
	} else {
		//TODO: do jquery error stuff here
		alert("Sorry, something went wrong uploading the image.");
	}
	resize();
}

/*this callback is currently only used for the image upload*/
function uploadCallback_foafDepiction_public(response) {
	if(response && response != '0'){
		log('Upload callback');
		previewImage('foafDepiction_container','foafDepiction',response,false,true);
		
	} else {
		//TODO: do jquery error stuff here
		alert("Sorry, something went wrong uploading the image.");
	}
	resize();
}

/*this callback is currently only used for the image upload*/
function uploadCallback_foafImg_public(response) {
	if(response && response != '0'){
		previewImage('foafImg_container','foafImg',response,false,true);
		
	} else {
		//TODO: do jquery error stuff here
		alert("Sorry, something went wrong uploading the image.");
	}
	resize();
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

//TODO: put this into utils
function utf8_encode ( string ) {
    // http://kevin.vanzonneveld.net
    // +   original by: Webtoolkit.info (http://www.webtoolkit.info/)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: sowberry
    // +    tweaked by: Jack
    // +   bugfixed by: Onno Marsman
    // +   improved by: Yves Sucaet
    // *     example 1: utf8_encode('Kevin van Zonneveld');
    // *     returns 1: 'Kevin van Zonneveld'
 
    string = (string+'').replace(/\r\n/g, "\n");
    string = (string+'').replace(/\r/g, "\n");
    var utftext = "";
    var start, end;
    var stringl = 0;
 
    start = end = 0;
    stringl = string.length;
    for (var n = 0; n < stringl; n++) {
        var c1 = string.charCodeAt(n);
        var enc = null;
 
        if (c1 < 128) {
            end++;
        } else if((c1 > 127) && (c1 < 2048)) {
            enc = String.fromCharCode((c1 >> 6) | 192) + String.fromCharCode((c1 & 63) | 128);
        } else {
            enc = String.fromCharCode((c1 >> 12) | 224) + String.fromCharCode(((c1 >> 6) & 63) | 128) + String.fromCharCode((c1 & 63) | 128);
        }
        if (enc != null) {
            if (end > start) {
                utftext += string.substring(start, end);
            }
            utftext += enc;
            start = end = n+1;
        }
    }
 
    if (end > start) {
        utftext += string.substring(start, string.length);
    }
 
    return utftext;
}

//TODO: put this in utils
function sha1 ( str ) {
    // http://kevin.vanzonneveld.net
    // +   original by: Webtoolkit.info (http://www.webtoolkit.info/)
    // + namespaced by: Michael White (http://getsprink.com)
    // -    depends on: utf8_encode
    // *     example 1: sha1('Kevin van Zonneveld');
    // *     returns 1: '54916d2e62f65b3afa6e192e6a601cdbe5cb5897'
 
    var rotate_left = function(n,s) {
        var t4 = ( n<<s ) | (n>>>(32-s));
        return t4;
    };
 
    var lsb_hex = function(val) {
        var str="";
        var i;
        var vh;
        var vl;
 
        for( i=0; i<=6; i+=2 ) {
            vh = (val>>>(i*4+4))&0x0f;
            vl = (val>>>(i*4))&0x0f;
            str += vh.toString(16) + vl.toString(16);
        }
        return str;
    };
 
    var cvt_hex = function(val) {
        var str="";
        var i;
        var v;
 
        for( i=7; i>=0; i-- ) {
            v = (val>>>(i*4))&0x0f;
            str += v.toString(16);
        }
        return str;
    };
 
    var blockstart;
    var i, j;
    var W = new Array(80);
    var H0 = 0x67452301;
    var H1 = 0xEFCDAB89;
    var H2 = 0x98BADCFE;
    var H3 = 0x10325476;
    var H4 = 0xC3D2E1F0;
    var A, B, C, D, E;
    var temp;
 
    str = utf8_encode(str);
    var str_len = str.length;
 
    var word_array = new Array();
    for( i=0; i<str_len-3; i+=4 ) {
        j = str.charCodeAt(i)<<24 | str.charCodeAt(i+1)<<16 |
        str.charCodeAt(i+2)<<8 | str.charCodeAt(i+3);
        word_array.push( j );
    }
 
    switch( str_len % 4 ) {
        case 0:
            i = 0x080000000;
        break;
        case 1:
            i = str.charCodeAt(str_len-1)<<24 | 0x0800000;
        break;
        case 2:
            i = str.charCodeAt(str_len-2)<<24 | str.charCodeAt(str_len-1)<<16 | 0x08000;
        break;
        case 3:
            i = str.charCodeAt(str_len-3)<<24 | str.charCodeAt(str_len-2)<<16 | str.charCodeAt(str_len-1)<<8    | 0x80;
        break;
    }
 
    word_array.push( i );
 
    while( (word_array.length % 16) != 14 ) word_array.push( 0 );
 
    word_array.push( str_len>>>29 );
    word_array.push( (str_len<<3)&0x0ffffffff );
 
    for ( blockstart=0; blockstart<word_array.length; blockstart+=16 ) {
        for( i=0; i<16; i++ ) W[i] = word_array[blockstart+i];
        for( i=16; i<=79; i++ ) W[i] = rotate_left(W[i-3] ^ W[i-8] ^ W[i-14] ^ W[i-16], 1);
 
        A = H0;
        B = H1;
        C = H2;
        D = H3;
        E = H4;
 
        for( i= 0; i<=19; i++ ) {
            temp = (rotate_left(A,5) + ((B&C) | (~B&D)) + E + W[i] + 0x5A827999) & 0x0ffffffff;
            E = D;
            D = C;
            C = rotate_left(B,30);
            B = A;
            A = temp;
        }
 
        for( i=20; i<=39; i++ ) {
            temp = (rotate_left(A,5) + (B ^ C ^ D) + E + W[i] + 0x6ED9EBA1) & 0x0ffffffff;
            E = D;
            D = C;
            C = rotate_left(B,30);
            B = A;
            A = temp;
        }
 
        for( i=40; i<=59; i++ ) {
            temp = (rotate_left(A,5) + ((B&C) | (B&D) | (C&D)) + E + W[i] + 0x8F1BBCDC) & 0x0ffffffff;
            E = D;
            D = C;
            C = rotate_left(B,30);
            B = A;
            A = temp;
        }
 
        for( i=60; i<=79; i++ ) {
            temp = (rotate_left(A,5) + (B ^ C ^ D) + E + W[i] + 0xCA62C1D6) & 0x0ffffffff;
            E = D;
            D = C;
            C = rotate_left(B,30);
            B = A;
            A = temp;
        }
 
        H0 = (H0 + A) & 0x0ffffffff;
        H1 = (H1 + B) & 0x0ffffffff;
        H2 = (H2 + C) & 0x0ffffffff;
        H3 = (H3 + D) & 0x0ffffffff;
        H4 = (H4 + E) & 0x0ffffffff;
    }
 
    var temp = cvt_hex(H0) + cvt_hex(H1) + cvt_hex(H2) + cvt_hex(H3) + cvt_hex(H4);
    return temp.toLowerCase();
}

function yellowFade(el) {
  var b = 155;
  function f() {
    el.style.background = 'rgb(255,255,'+ (b+=4) +')';
    if (b < 255) {
      setTimeout(f, 40);
    }
  };
  f();
}

function clone(myObj)
{
	if(typeof(myObj) != 'object') return myObj;
	if(myObj == null) return myObj;

	var myNewObj = new Object();

	for(var i in myObj)
		myNewObj[i] = clone(myObj[i]);

	return myNewObj;
}
