jQuery.autocomplete = function(input, options) {
	// Create a link to self
	var me = this;

	// Create jQuery object for input element
	var $input = $(input).attr("autocomplete", "off");

	// Apply inputClass if necessary
	if (options.inputClass) $input.addClass(options.inputClass);

	// Create results
	var results = document.createElement("div");
	// Create jQuery object for results
	var $results = $(results);
	$results.hide().addClass(options.resultsClass).css("position", "absolute");
	if( options.width > 0 ) $results.css("width", options.width);

	// Add to body element
	$("body").append(results);

	input.autocompleter = me;

	var timeout = null;
	var prev = "";
	var active = -1;
	var cache = {};
	var keyb = false;
	var hasFocus = false;
	var lastKeyPressCode = null;

	// flush cache
	function flushCache(){
		cache = {};
		cache.data = {};
		cache.length = 0;
	};

	// flush cache
	flushCache();

	// if there is a data array supplied
	if( options.data != null ){
		var sFirstChar = "", stMatchSets = {}, row = [];

		// no url was specified, we need to adjust the cache length to make sure it fits the local data store
		if( typeof options.url != "string" ) options.cacheLength = 1;

		// loop through the array and create a lookup structure
		for( var i=0; i < options.data.length; i++ ){
			// if row is a string, make an array otherwise just reference the array
			row = ((typeof options.data[i] == "string") ? [options.data[i]] : options.data[i]);

			// if the length is zero, don't add to list
			if( row[0].length > 0 ){
				// get the first character
				sFirstChar = row[0].substring(0, 1).toLowerCase();
				// if no lookup array for this character exists, look it up now
				if( !stMatchSets[sFirstChar] ) stMatchSets[sFirstChar] = [];
				// if the match is a string
				stMatchSets[sFirstChar].push(row);
			}
		}

		// add the data items to the cache
		for( var k in stMatchSets ){
			// increase the cache size
			options.cacheLength++;
			// add to the cache
			addToCache(k, stMatchSets[k]);
		}
	}

	$input
	.keydown(function(e) {
		// track last key pressed
		lastKeyPressCode = e.keyCode;
		switch(e.keyCode) {
			case 38: // up
				e.preventDefault();
				moveSelect(-1);
				break;
			case 40: // down
				e.preventDefault();
				moveSelect(1);
				break;
			case 9:  // tab
			case 13: // return
				if( selectCurrent() ){
					// make sure to blur off the current field
					$input.get(0).blur();
					e.preventDefault();
				}
				break;
			default:
				active = -1;
				if (timeout) clearTimeout(timeout);
				timeout = setTimeout(function(){onChange();}, options.delay);
				break;
		}
	})
	.focus(function(){
		// track whether the field has focus, we shouldn't process any results if the field no longer has focus
		hasFocus = true;
	})
	.blur(function() {
		// track whether the field has focus
		hasFocus = false;
		hideResults();
	});

	hideResultsNow();

	function onChange() {
		// ignore if the following keys are pressed: [del] [shift] [capslock]
		if( lastKeyPressCode == 46 || (lastKeyPressCode > 8 && lastKeyPressCode < 32) ) return $results.hide();
		var v = $input.val();
		if (v == prev) return;
		prev = v;
		if (v.length >= options.minChars) {
			$input.addClass(options.loadingClass);
			requestData(v);
		} else {
			$input.removeClass(options.loadingClass);
			$results.hide();
		}
	};

 	function moveSelect(step) {

		var lis = $("li", results);
		if (!lis) return;

		active += step;

		if (active < 0) {
			active = 0;
		} else if (active >= lis.size()) {
			active = lis.size() - 1;
		}

		lis.removeClass("ac_over");

		$(lis[active]).addClass("ac_over");

		// Weird behaviour in IE
		// if (lis[active] && lis[active].scrollIntoView) {
		// 	lis[active].scrollIntoView(false);
		// }

	};

	function selectCurrent() {
		var li = $("li.ac_over", results)[0];
		if (!li) {
			var $li = $("li", results);
			if (options.selectOnly) {
				if ($li.length == 1) li = $li[0];
			} else if (options.selectFirst) {
				li = $li[0];
			}
		}
		if (li) {
			selectItem(li);
			return true;
		} else {
			return false;
		}
	};

	function selectItem(li) {
		if (!li) {
			li = document.createElement("li");
			li.extra = [];
			li.selectValue = "";
		}
		var v = $.trim(li.selectValue ? li.selectValue : li.innerHTML);
		input.lastSelected = v;
		prev = v;
		$results.html("");
		$input.val(v);
		hideResultsNow();
		if (options.onItemSelect) setTimeout(function() { options.onItemSelect(li) }, 1);
	};

	// selects a portion of the input string
	function createSelection(start, end){
		// get a reference to the input element
		var field = $input.get(0);
		if( field.createTextRange ){
			var selRange = field.createTextRange();
			selRange.collapse(true);
			selRange.moveStart("character", start);
			selRange.moveEnd("character", end);
			selRange.select();
		} else if( field.setSelectionRange ){
			field.setSelectionRange(start, end);
		} else {
			if( field.selectionStart ){
				field.selectionStart = start;
				field.selectionEnd = end;
			}
		}
		field.focus();
	};

	// fills in the input box w/the first match (assumed to be the best match)
	function autoFill(sValue){
		// if the last user key pressed was backspace, don't autofill
		if( lastKeyPressCode != 8 ){
			// fill in the value (keep the case the user has typed)
			$input.val($input.val() + sValue.substring(prev.length));
			// select the portion of the value not typed by the user (so the next character will erase)
			createSelection(prev.length, sValue.length);
		}
	};

	function showResults() {
		// get the position of the input field right now (in case the DOM is shifted)
		var pos = findPos(input);
		// either use the specified width, or autocalculate based on form element
		var iWidth = (options.width > 0) ? options.width : $input.width();
		// reposition
		$results.css({
			width: parseInt(iWidth) + "px",
			top: (pos.y + input.offsetHeight) + "px",
			left: pos.x + "px"
		}).show();
	};

	function hideResults() {
		if (timeout) clearTimeout(timeout);
		timeout = setTimeout(hideResultsNow, 200);
	};

	function hideResultsNow() {
		if (timeout) clearTimeout(timeout);
		$input.removeClass(options.loadingClass);
		if ($results.is(":visible")) {
			$results.hide();
		}
		if (options.mustMatch) {
			var v = $input.val();
			if (v != input.lastSelected) {
				selectItem(null);
			}
		}
	};

	function receiveData(q, data) {
		if (data) {
			$input.removeClass(options.loadingClass);
			results.innerHTML = "";

			// if the field no longer has focus or if there are no matches, do not display the drop down
			if( !hasFocus || data.length == 0 ) return hideResultsNow();

			if ($.browser.msie) {
				// we put a styled iframe behind the calendar so HTML SELECT elements don't show through
				$results.append(document.createElement('iframe'));
			}
			results.appendChild(dataToDom(data));
			// autofill in the complete box w/the first match as long as the user hasn't entered in more data
			if( options.autoFill && ($input.val().toLowerCase() == q.toLowerCase()) ) autoFill(data[0][0]);
			showResults();
		} else {
			hideResultsNow();
		}
	};

	function parseData(data) {
		if (!data) return null;
		var parsed = [];
		var rows = data.split(options.lineSeparator);
		for (var i=0; i < rows.length; i++) {
			var row = $.trim(rows[i]);
			if (row) {
				parsed[parsed.length] = row.split(options.cellSeparator);
			}
		}
		return parsed;
	};

	function dataToDom(data) {
		var ul = document.createElement("ul");
		var num = data.length;

		// limited results to a max number
		if( (options.maxItemsToShow > 0) && (options.maxItemsToShow < num) ) num = options.maxItemsToShow;

		for (var i=0; i < num; i++) {
			var row = data[i];
			if (!row) continue;
			var li = document.createElement("li");
			if (options.formatItem) {
				li.innerHTML = options.formatItem(row, i, num);
				li.selectValue = row[0];
			} else {
				li.innerHTML = row[0];
				li.selectValue = row[0];
			}
			var extra = null;
			if (row.length > 1) {
				extra = [];
				for (var j=1; j < row.length; j++) {
					extra[extra.length] = row[j];
				}
			}
			li.extra = extra;
			ul.appendChild(li);
			$(li).hover(
				function() { $("li", ul).removeClass("ac_over"); $(this).addClass("ac_over"); active = $("li", ul).indexOf($(this).get(0)); },
				function() { $(this).removeClass("ac_over"); }
			).click(function(e) { e.preventDefault(); e.stopPropagation(); selectItem(this) });
		}
		return ul;
	};

	function requestData(q) {
		if (!options.matchCase) q = q.toLowerCase();
		var data = options.cacheLength ? loadFromCache(q) : null;
		// recieve the cached data
		if (data) {
			receiveData(q, data);
		// if an AJAX url has been supplied, try loading the data now
		} else if( (typeof options.url == "string") && (options.url.length > 0) ){
			$.get(makeUrl(q), function(data) {
				data = parseData(data);
				addToCache(q, data);
				receiveData(q, data);
			});
		// if there's been no data found, remove the loading class
		} else {
			$input.removeClass(options.loadingClass);
		}
	};

	function makeUrl(q) {
		var url = options.url + "?q=" + encodeURI(q);
		for (var i in options.extraParams) {
			url += "&" + i + "=" + encodeURI(options.extraParams[i]);
		}
		return url;
	};

	function loadFromCache(q) {
		if (!q) return null;
		if (cache.data[q]) return cache.data[q];
		if (options.matchSubset) {
			for (var i = q.length - 1; i >= options.minChars; i--) {
				var qs = q.substr(0, i);
				var c = cache.data[qs];
				if (c) {
					var csub = [];
					for (var j = 0; j < c.length; j++) {
						var x = c[j];
						var x0 = x[0];
						if (matchSubset(x0, q)) {
							csub[csub.length] = x;
						}
					}
					return csub;
				}
			}
		}
		return null;
	};

	function matchSubset(s, sub) {
		if (!options.matchCase) s = s.toLowerCase();
		var i = s.indexOf(sub);
		if (i == -1) return false;
		return i == 0 || options.matchContains;
	};

	this.flushCache = function() {
		flushCache();
	};

	this.setExtraParams = function(p) {
		options.extraParams = p;
	};

	this.findValue = function(){
		var q = $input.val();

		if (!options.matchCase) q = q.toLowerCase();
		var data = options.cacheLength ? loadFromCache(q) : null;
		if (data) {
			findValueCallback(q, data);
		} else if( (typeof options.url == "string") && (options.url.length > 0) ){
			$.get(makeUrl(q), function(data) {
				data = parseData(data)
				addToCache(q, data);
				findValueCallback(q, data);
			});
		} else {
			// no matches
			findValueCallback(q, null);
		}
	}

	function findValueCallback(q, data){
		if (data) $input.removeClass(options.loadingClass);

		var num = (data) ? data.length : 0;
		var li = null;

		for (var i=0; i < num; i++) {
			var row = data[i];

			if( row[0].toLowerCase() == q.toLowerCase() ){
				li = document.createElement("li");
				if (options.formatItem) {
					li.innerHTML = options.formatItem(row, i, num);
					li.selectValue = row[0];
				} else {
					li.innerHTML = row[0];
					li.selectValue = row[0];
				}
				var extra = null;
				if( row.length > 1 ){
					extra = [];
					for (var j=1; j < row.length; j++) {
						extra[extra.length] = row[j];
					}
				}
				li.extra = extra;
			}
		}

		if( options.onFindValue ) setTimeout(function() { options.onFindValue(li) }, 1);
	}

	function addToCache(q, data) {
		if (!data || !q || !options.cacheLength) return;
		if (!cache.length || cache.length > options.cacheLength) {
			flushCache();
			cache.length++;
		} else if (!cache[q]) {
			cache.length++;
		}
		cache.data[q] = data;
	};

	function findPos(obj) {
		var curleft = obj.offsetLeft || 0;
		var curtop = obj.offsetTop || 0;
		while (obj = obj.offsetParent) {
			curleft += obj.offsetLeft
			curtop += obj.offsetTop
		}
		return {x:curleft,y:curtop};
	}
}

jQuery.fn.autocomplete = function(url, options, data) {
	// Make sure options exists
	options = options || {};
	// Set url as option
	options.url = url;
	// set some bulk local data
	options.data = ((typeof data == "object") && (data.constructor == Array)) ? data : null;

	// Set default values for required options
	options.inputClass = options.inputClass || "ac_input";
	options.resultsClass = options.resultsClass || "ac_results";
	options.lineSeparator = options.lineSeparator || "\n";
	options.cellSeparator = options.cellSeparator || "|";
	options.minChars = options.minChars || 1;
	options.delay = options.delay || 400;
	options.matchCase = options.matchCase || 0;
	options.matchSubset = options.matchSubset || 1;
	options.matchContains = options.matchContains || 0;
	options.cacheLength = options.cacheLength || 1;
	options.mustMatch = options.mustMatch || 0;
	options.extraParams = options.extraParams || {};
	options.loadingClass = options.loadingClass || "ac_loading";
	options.selectFirst = options.selectFirst || false;
	options.selectOnly = options.selectOnly || false;
	options.maxItemsToShow = options.maxItemsToShow || -1;
	options.autoFill = options.autoFill || false;
	options.width = parseInt(options.width, 10) || 0;

	this.each(function() {
		var input = this;
		new jQuery.autocomplete(input, options);
	});

	// Don't break the chain
	return this;
}

jQuery.fn.autocompleteArray = function(data, options) {
	return this.autocomplete(null, options, data);
}

jQuery.fn.indexOf = function(e){
	for( var i=0; i<this.length; i++ ){
		if( this[i] == e ) return i;
	}
	return -1;
};


function findValue(li) {
	if( li == null ) return alert("No match!");

	// if coming from an AJAX call, let's use the CityId as the value
	if( !!li.extra ) var sValue = li.extra[0];

	// otherwise, let's just display the value in the text box
	else var sValue = li.selectValue;

	alert("The value you selected was: " + sValue);
}

function selectItem(li) {
	findValue(li);
}

function formatItem(row) {
	return row[0] + " (id: " + row[1] + ")";
}

function lookupAjax(){
	var oSuggest = $("#CityAjax")[0].autocompleter;

	oSuggest.findValue();

	return false;
}

function lookupLocal(){
	var oSuggest = $("#CityLocal")[0].autocompleter;

	oSuggest.findValue();

	return false;
}

$(document).ready(function() {
	$("#CityAjax").autocomplete(
		"autocomplete_ajax.cfm",
		{
			delay:10,
			minChars:2,
			matchSubset:1,
			matchContains:1,
			cacheLength:10,
			onItemSelect:selectItem,
			onFindValue:findValue,
			formatItem:formatItem,
			autoFill:true
		}
	);
	
	//TODO: fill with airport data
	$("#CityLocal").autocompleteArray(
		[
			"Bristol Airport, BRS, EGGD, thingy","Nottingham Airport, NEM, Nottingham","Aberdeen", "Ada", "Adamsville", "Addyston", "Adelphi", "Adena", "Adrian", "Akron",
			"Albany", "Alexandria", "Alger", "Alledonia", "Alliance", "Alpha", "Alvada",
			"Alvordton", "Amanda", "Amelia", "Amesville", "Amherst", "Amlin", "Amsden",
			"Amsterdam", "Andover", "Anna", "Ansonia", "Antwerp", "Apple Creek", "Arcadia",
			"Arcanum", "Archbold", "Arlington", "Ashland", "Ashley", "Ashtabula", "Ashville",
			"Athens", "Attica", "Atwater", "Augusta", "Aurora", "Austinburg", "Ava", "Avon",
			"Avon Lake", "Bainbridge", "Bakersville", "Baltic", "Baltimore", "Bannock",
			"Barberton", "Barlow", "Barnesville", "Bartlett", "Barton", "Bascom", "Batavia",
			"Bath", "Bay Village", "Beach City", "Beachwood", "Beallsville", "Beaver",
			"Beaverdam", "Bedford", "Bellaire", "Bellbrook", "Belle Center", "Belle Valley",
			"Bellefontaine", "Bellevue", "Bellville", "Belmont", "Belmore", "Beloit", "Belpre",
			"Benton Ridge", "Bentonville", "Berea", "Bergholz", "Berkey", "Berlin",
			"Berlin Center", "Berlin Heights", "Bethel", "Bethesda", "Bettsville", "Beverly",
			"Bidwell", "Big Prairie", "Birmingham", "Blacklick", "Bladensburg", "Blaine",
			"Blakeslee", "Blanchester", "Blissfield", "Bloomdale", "Bloomingburg",
			"Bloomingdale", "Bloomville", "Blue Creek", "Blue Rock", "Bluffton",
			"Bolivar", "Botkins", "Bourneville", "Bowerston", "Bowersville",
			"Bowling Green", "Bradford", "Bradner", "Brady Lake", "Brecksville",
			"Bremen", "Brewster", "Brice", "Bridgeport", "Brilliant", "Brinkhaven",
			"Bristolville", "Broadview Heights", "Broadway", "Brookfield", "Brookpark",
			"Brookville", "Brownsville", "Brunswick", "Bryan", "Buchtel", "Buckeye Lake",
			"Buckland", "Bucyrus", "Buffalo", "Buford", "Burbank", "Burghill", "Burgoon",
			"Burkettsville", "Burton", "Butler", "Byesville", "Cable", "Cadiz", "Cairo",
			"Caldwell", "Caledonia", "Cambridge", "Camden", "Cameron", "Camp Dennison",
			"Campbell", "Canal Fulton", "Canal Winchester", "Canfield", "Canton", "Carbon Hill",
			"Carbondale", "Cardington", "Carey", "Carroll", "Carrollton", "Casstown",
			"Castalia", "Catawba", "Cecil", "Cedarville", "Celina", "Centerburg",
			"Chagrin Falls", "Chandlersville", "Chardon", "Charm", "Chatfield", "Chauncey",
			"Cherry Fork", "Chesapeake", "Cheshire", "Chester", "Chesterhill", "Chesterland",
			"Chesterville", "Chickasaw", "Chillicothe", "Chilo", "Chippewa Lake",
			"Christiansburg", "Cincinnati", "Circleville", "Clarington", "Clarksburg",
			"Clarksville", "Clay Center", "Clayton", "Cleveland", "Cleves", "Clifton",
			"Clinton", "Cloverdale", "Clyde", "Coal Run", "Coalton", "Coldwater", "Colerain",
			"College Corner", "Collins", "Collinsville", "Colton", "Columbia Station",
			"Columbiana", "Columbus", "Columbus Grove", "Commercial Point", "Conesville",
			"Conneaut", "Conover", "Continental", "Convoy", "Coolville", "Corning", "Cortland",
			"Coshocton", "Covington", "Creola", "Crestline", "Creston", "Crooksville",
			"Croton", "Crown City", "Cuba", "Cumberland", "Curtice", "Custar", "Cutler",
			"Cuyahoga Falls", "Cygnet", "Cynthiana", "Dalton", "Damascus", "Danville",
			"Dayton", "De Graff", "Decatur", "Deerfield", "Deersville", "Defiance",
			"Delaware", "Dellroy", "Delphos", "Delta", "Dennison", "Derby", "Derwent",
			"Deshler", "Dexter City", "Diamond", "Dillonvale", "Dola", "Donnelsville",
			"Dorset", "Dover", "Doylestown", "Dresden", "Dublin", "Dunbridge", "Duncan Falls",
			"Dundee", "Dunkirk", "Dupont", "East Claridon", "East Fultonham",
			"East Liberty", "East Liverpool", "East Palestine", "East Rochester",
			"East Sparta", "East Springfield", "Eastlake", "Eaton", "Edgerton", "Edison",
			"Edon", "Eldorado", "Elgin", "Elkton", "Ellsworth", "Elmore", "Elyria",
			"Empire", "Englewood", "Enon", "Etna", "Euclid", "Evansport", "Fairborn",
			"Fairfield", "Fairpoint", "Fairview", "Farmdale", "Farmer", "Farmersville",
			"Fayette", "Fayetteville", "Feesburg", "Felicity", "Findlay", "Flat Rock",
			"Fleming", "Fletcher", "Flushing", "Forest", "Fort Jennings", "Fort Loramie",
			"Fort Recovery", "Fostoria", "Fowler", "Frankfort", "Franklin",
			"Franklin Furnace", "Frazeysburg", "Fredericksburg", "Fredericktown",
			"Freeport", "Fremont", "Fresno", "Friendship", "Fulton", "Fultonham",
			"Galena", "Galion", "Gallipolis", "Galloway", "Gambier", "Garrettsville",
			"Gates Mills", "Geneva", "Genoa", "Georgetown", "Germantown", "Gettysburg",
			"Gibsonburg", "Girard", "Glandorf", "Glencoe", "Glenford", "Glenmont",
			"Glouster", "Gnadenhutten", "Gomer", "Goshen", "Grafton", "Grand Rapids",
			"Grand River", "Granville", "Gratiot", "Gratis", "Graysville", "Graytown",
			"Green", "Green Camp", "Green Springs", "Greenfield", "Greenford",
			"Greentown", "Greenville", "Greenwich", "Grelton", "Grove City",
			"Groveport", "Grover Hill", "Guysville", "Gypsum", "Hallsville",
			"Hamden", "Hamersville", "Hamilton", "Hamler", "Hammondsville",
			"Hannibal", "Hanoverton", "Harbor View", "Harlem Springs", "Harpster",
			"Harrisburg", "Harrison", "Harrisville", "Harrod", "Hartford", "Hartville",
			"Harveysburg", "Haskins", "Haverhill", "Haviland", "Haydenville", "Hayesville",
			"Heath", "Hebron", "Helena", "Hicksville", "Higginsport", "Highland", "Hilliard",
			"Hillsboro", "Hinckley", "Hiram", "Hockingport", "Holgate", "Holland",
			"Hollansburg", "Holloway", "Holmesville", "Homer", "Homerville", "Homeworth",
			"Hooven", "Hopedale", "Hopewell", "Houston", "Howard", "Hoytville", "Hubbard",
			"Hudson", "Huntsburg", "Huntsville", "Huron", "Iberia", "Independence",
			"Irondale", "Ironton", "Irwin", "Isle Saint George", "Jackson", "Jackson Center",
			"Jacksontown", "Jacksonville", "Jacobsburg", "Jamestown", "Jasper",
			"Jefferson", "Jeffersonville", "Jenera", "Jeromesville", "Jerry City",
			"Jerusalem", "Jewell", "Jewett", "Johnstown", "Junction City", "Kalida",
			"Kansas", "Keene", "Kelleys Island", "Kensington", "Kent", "Kenton",
			"Kerr", "Kettlersville", "Kidron", "Kilbourne", "Killbuck", "Kimbolton",
			"Kings Mills", "Kingston", "Kingsville", "Kinsman", "Kipling", "Kipton",
			"Kirby", "Kirkersville", "Kitts Hill", "Kunkle", "La Rue", "Lacarne",
			"Lafayette", "Lafferty", "Lagrange", "Laings", "Lake Milton", "Lakemore",
			"Lakeside Marblehead", "Lakeview", "Lakeville", "Lakewood", "Lancaster",
			"Langsville", "Lansing", "Latham", "Latty", "Laura", "Laurelville",
			"Leavittsburg", "Lebanon", "Lees Creek", "Leesburg", "Leesville",
			"Leetonia", "Leipsic", "Lemoyne", "Lewis Center", "Lewisburg",
			"Lewistown", "Lewisville", "Liberty Center", "Lima", "Limaville",
			"Lindsey", "Lisbon", "Litchfield", "Lithopolis", "Little Hocking",
			"Lockbourne", "Lodi", "Logan", "London", "Londonderry",
			"Long Bottom", "Lorain", "Lore City", "Loudonville", "Louisville",
			"Loveland", "Lowell", "Lowellville", "Lower Salem", "Lucas",
			"Lucasville", "Luckey", "Ludlow Falls", "Lynchburg", "Lynx",
			"Lyons", "Macedonia", "Macksburg", "Madison", "Magnetic Springs",
			"Magnolia", "Maineville", "Malaga", "Malinta", "Malta", "Malvern",
			"Manchester", "Mansfield", "Mantua", "Maple Heights", "Maplewood",
			"Marathon", "Marengo", "Maria Stein", "Marietta", "Marion",
			"Mark Center", "Marshallville", "Martel", "Martin", "Martins Ferry",
			"Martinsburg", "Martinsville", "Marysville", "Mason", "Massillon",
			"Masury", "Maumee", "Maximo", "Maynard", "Mc Arthur", "Mc Clure",
			"Mc Comb", "Mc Connelsville", "Mc Cutchenville", "Mc Dermott",
			"Mc Donald", "Mc Guffey", "Mechanicsburg", "Mechanicstown",
			"Medina", "Medway", "Melmore", "Melrose", "Mendon", "Mentor",
			"Mesopotamia", "Metamora", "Miamisburg", "Miamitown", "Miamiville",
			"Middle Bass", "Middle Point", "Middlebranch", "Middleburg",
			"Middlefield", "Middleport", "Middletown", "Midland", "Midvale",
			"Milan", "Milford", "Milford Center", "Millbury", "Milledgeville",
			"Miller City", "Millersburg", "Millersport", "Millfield",
			"Milton Center", "Mineral City", "Mineral Ridge", "Minerva",
			"Minford", "Mingo", "Mingo Junction", "Minster", "Mogadore",
			"Monclova", "Monroe", "Monroeville", "Montezuma", "Montpelier",
			"Montville", "Morral", "Morristown", "Morrow", "Moscow",
			"Mount Blanchard", "Mount Cory", "Mount Eaton", "Mount Gilead",
			"Mount Hope", "Mount Liberty", "Mount Orab", "Mount Perry",
			"Mount Pleasant", "Mount Saint Joseph", "Mount Sterling",
			"Mount Vernon", "Mount Victory", "Mowrystown", "Moxahala",
			"Munroe Falls", "Murray City", "Nankin", "Napoleon", "Nashport",
			"Nashville", "Navarre", "Neapolis", "Neffs", "Negley",
			"Nelsonville", "Nevada", "Neville", "New Albany", "New Athens",
			"New Bavaria", "New Bloomington", "New Bremen", "New Carlisle",
			"New Concord", "New Hampshire", "New Haven", "New Holland",
			"New Knoxville", "New Lebanon", "New Lexington", "New London",
			"New Madison", "New Marshfield", "New Matamoras", "New Middletown",
			"New Paris", "New Philadelphia", "New Plymouth", "New Richmond",
			"New Riegel", "New Rumley", "New Springfield", "New Straitsville",
			"New Vienna", "New Washington", "New Waterford", "New Weston",
			"Newark", "Newbury", "Newcomerstown", "Newport", "Newton Falls",
			"Newtonsville", "Ney", "Niles", "North Baltimore", "North Bend",
			"North Benton", "North Bloomfield", "North Fairfield",
			"North Georgetown", "North Hampton", "North Jackson",
			"North Kingsville", "North Lawrence", "North Lewisburg",
			"North Lima", "North Olmsted", "North Ridgeville", "North Robinson",
			"North Royalton", "North Star", "Northfield", "Northwood", "Norwalk",
			"Norwich", "Nova", "Novelty", "Oak Harbor", "Oak Hill", "Oakwood",
			"Oberlin", "Oceola", "Ohio City", "Okeana", "Okolona", "Old Fort",
			"Old Washington", "Olmsted Falls", "Ontario", "Orangeville",
			"Oregon", "Oregonia", "Orient", "Orrville", "Orwell", "Osgood",
			"Ostrander", "Ottawa", "Ottoville", "Otway", "Overpeck",
			"Owensville", "Oxford", "Painesville", "Palestine", "Pandora",
			"Paris", "Parkman", "Pataskala", "Patriot", "Paulding", "Payne",
			"Pedro", "Peebles", "Pemberton", "Pemberville", "Peninsula",
			"Perry", "Perrysburg", "Perrysville", "Petersburg", "Pettisville",
			"Phillipsburg", "Philo", "Pickerington", "Piedmont", "Pierpont",
			"Piketon", "Piney Fork", "Pioneer", "Piqua", "Pitsburg",
			"Plain City", "Plainfield", "Pleasant City", "Pleasant Hill",
			"Pleasant Plain", "Pleasantville", "Plymouth", "Polk",
			"Pomeroy", "Port Clinton", "Port Jefferson", "Port Washington",
			"Port William", "Portage", "Portland", "Portsmouth", "Potsdam",
			"Powell", "Powhatan Point", "Proctorville", "Prospect", "Put in Bay",
			"Quaker City", "Quincy", "Racine", "Radnor", "Randolph", "Rarden",
			"Ravenna", "Rawson", "Ray", "Rayland", "Raymond", "Reedsville",
			"Reesville", "Reno", "Republic", "Reynoldsburg", "Richfield",
			"Richmond", "Richmond Dale", "Richwood", "Ridgeville Corners",
			"Ridgeway", "Rio Grande", "Ripley", "Risingsun", "Rittman",
			"Robertsville", "Rock Camp", "Rock Creek", "Rockbridge", "Rockford",
			"Rocky Ridge", "Rocky River", "Rogers", "Rome", "Rootstown", "Roseville",
			"Rosewood", "Ross", "Rossburg", "Rossford", "Roundhead", "Rudolph",
			"Rushsylvania", "Rushville", "Russells Point", "Russellville", "Russia",
			"Rutland", "Sabina", "Saint Clairsville", "Saint Henry", "Saint Johns",
			"Saint Louisville", "Saint Marys", "Saint Paris", "Salem", "Salesville",
			"Salineville", "Sandusky", "Sandyville", "Sarahsville", "Sardinia",
			"Sardis", "Savannah", "Scio", "Scioto Furnace", "Scott", "Scottown",
			"Seaman", "Sebring", "Sedalia", "Senecaville", "Seven Mile", "Seville",
			"Shade", "Shadyside", "Shandon", "Sharon Center", "Sharpsburg",
			"Shauck", "Shawnee", "Sheffield Lake", "Shelby", "Sherrodsville",
			"Sherwood", "Shiloh", "Short Creek", "Shreve", "Sidney", "Sinking Spring",
			"Smithfield", "Smithville", "Solon", "Somerdale", "Somerset",
			"Somerville", "South Bloomingville", "South Charleston", "South Lebanon",
			"South Point", "South Salem", "South Solon", "South Vienna",
			"South Webster", "Southington", "Sparta", "Spencer", "Spencerville",
			"Spring Valley", "Springboro", "Springfield", "Stafford", "Sterling",
			"Steubenville", "Stewart", "Stillwater", "Stockdale", "Stockport",
			"Stone Creek", "Stony Ridge", "Stout", "Stoutsville", "Stow", "Strasburg",
			"Stratton", "Streetsboro", "Strongsville", "Struthers", "Stryker",
			"Sugar Grove", "Sugarcreek", "Sullivan", "Sulphur Springs", "Summerfield",
			"Summit Station", "Summitville", "Sunbury", "Swanton", "Sycamore",
			"Sycamore Valley", "Sylvania", "Syracuse", "Tallmadge", "Tarlton",
			"Terrace Park", "The Plains", "Thompson", "Thornville", "Thurman",
			"Thurston", "Tiffin", "Tiltonsville", "Tipp City", "Tippecanoe", "Tiro",
			"Toledo", "Tontogany", "Torch", "Toronto", "Tremont City", "Trenton",
			"Trimble", "Trinway", "Troy", "Tuppers Plains", "Tuscarawas", "Twinsburg",
			"Uhrichsville", "Union City", "Union Furnace", "Unionport", "Uniontown",
			"Unionville", "Unionville Center", "Uniopolis", "Upper Sandusky", "Urbana",
			"Utica", "Valley City", "Van Buren", "Van Wert", "Vandalia", "Vanlue",
			"Vaughnsville", "Venedocia", "Vermilion", "Verona", "Versailles",
			"Vickery", "Vienna", "Vincent", "Vinton", "Wadsworth", "Wakefield",
			"Wakeman", "Walbridge", "Waldo", "Walhonding", "Walnut Creek", "Wapakoneta",
			"Warnock", "Warren", "Warsaw", "Washington Court House",
			"Washingtonville", "Waterford", "Waterloo", "Watertown", "Waterville",
			"Wauseon", "Waverly", "Wayland", "Wayne", "Waynesburg", "Waynesfield",
			"Waynesville", "Wellington", "Wellston", "Wellsville", "West Alexandria",
			"West Chester", "West Elkton", "West Farmington", "West Jefferson",
			"West Lafayette", "West Liberty", "West Manchester", "West Mansfield",
			"West Millgrove", "West Milton", "West Point", "West Portsmouth",
			"West Rushville", "West Salem", "West Union", "West Unity", "Westerville",
			"Westfield Center", "Westlake", "Weston", "Westville", "Wharton",
			"Wheelersburg", "Whipple", "White Cottage", "Whitehouse", "Wickliffe",
			"Wilberforce", "Wilkesville", "Willard", "Williamsburg", "Williamsfield",
			"Williamsport", "Williamstown", "Williston", "Willoughby", "Willow Wood",
			"Willshire", "Wilmington", "Wilmot", "Winchester", "Windham", "Windsor",
			"Winesburg", "Wingett Run", "Winona", "Wolf Run", "Woodsfield",
			"Woodstock", "Woodville", "Wooster", "Wren", "Xenia", "Yellow Springs",
			"Yorkshire", "Yorkville", "Youngstown", "Zaleski", "Zanesfield", "Zanesville",
			"Zoar"
		],
		{
			delay:10,
			minChars:1,
			matchSubset:1,
			onItemSelect:selectItem,
			onFindValue:findValue,
			autoFill:true,
			maxItemsToShow:10
		}
	);
});
