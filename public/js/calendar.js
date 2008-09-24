
function populatedropdown(yearfield, monthfield, dayfield, year, month, day){
	
	/*do days*/
	dayfield.options[0] = new Option("Day",'');
	for (var i=1; i<32; i++){
		if(parseFloat(day) == i){
			dayfield.options[i] = new Option(i, i, true, true)
		} else {
			dayfield.options[i] = new Option(i, i)
		}
	}
	
	/*do months*/
	var monthtext=['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sept','Oct','Nov','Dec'];
	monthfield.options[0] = new Option("Month",'');
	for (var m=1; m<13; m++){
		if(parseFloat(month) == m) {
			monthfield.options[m] = new Option(monthtext[m],m,true,true);
		} else {
			monthfield.options[m] = new Option(monthtext[m],m);
		}
	}
	
	/*do years*/
	var today = new Date();
	var thisyear = today.getFullYear();	
	yearfield.options[0] = new Option("Year",'');
	yearfield.options[1] = new Option("Secret",'');
	for (var y=1; y<130; y++){
		if(thisyear == year){
			yearfield.options[y] = new Option(year, year, true, true);
		} else {
			yearfield.options[y] = new Option(thisyear, thisyear);
		}
		thisyear -= 1;
	}
}

