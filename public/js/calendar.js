
function populatedropdown(dayfield, monthfield, yearfield, year, month, day){
	
	var monthtext=['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sept','Oct','Nov','Dec'];
	var today=new Date()

	for (var i=0; i<31; i++){
		dayfield.options[i] = new Option(i+1, i+1)
	}
	
	/*set the day as per the argument, if the argument isn't null*/
	if(day) {
		dayfield.options[parseFloat(day)] = new Option(parseFloat(day),parseFloat(day), true, true);
	} else {
		dayfield.options[today.getDate()]=new Option(today.getDate(), today.getDate(), true, true);
	}
	
	for (var m=1; m<13; m++){
		monthfield.options[m] = new Option(monthtext[m],m)
	}
	
	/*set the month as per the argument, if the argument isn't null*/
	if(month) {
		monthfield.options[parseFloat(month)] = new Option(monthtext[parseFloat(month)],parseFloat(month),  true, true);
	} else {
		monthfield.options[today.getMonth()] = new Option(monthtext[today.getMonth()], today.getMonth(),  true, true);
	}
	
	var thisyear = today.getFullYear()
	
	for (var y=0; y<130; y++){
		yearfield.options[y] = new Option(thisyear, thisyear)
		thisyear -= 1
	}
	
	/*set the year as per the argument if the argument isn't null*/
	if(year) {
		yearfield.options[0] = new Option(year, year, true, true);
	} else {
		yearfield.options[0] = new Option(0, "Secret", true, true);
	}
}

