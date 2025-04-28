function sortTable(event, nameTable, n) {
    var table,
    rows,
    switching,
    i,
    x,
    y,
    shouldSwitch,
    dir,
    switchcount = 0;
    //console.log(nameTable);
    table = document.getElementById(nameTable);
    //console.log(table);
    if(table){
        filterArrow(event);
    }
    switching = true;
    //Set the sorting direction to ascending:
    dir = "asc";
    /*Make a loop that will continue until
    no switching has been done:*/
    while (switching) {
        //start by saying: no switching is done:
        switching = false;
        rows = table.rows;
        /*Loop through all table rows (except the
        first, which contains table headers):*/
        for (i = 1; i < rows.length - 1; i++) {
            //start by saying there should be no switching:
            shouldSwitch = false;
            /*Get the two elements you want to compare,
              one from current row and one from the next:*/
            x = rows[i].getElementsByTagName("TD")[n];
            y = rows[i + 1].getElementsByTagName("TD")[n];
            /*check if the two rows should switch place,
            based on the direction, asc or desc:*/
            if (dir == "asc") {
              	// check if x is a date
            	if (x.innerHTML.match(/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/)) {
            	  //console.log("type date");
            	  // convert x.innerHTML to unix timestamp
					      var xDate = moment(x.innerHTML, "DD/MM/YYYY").unix();
					      var yDate = moment(y.innerHTML, "DD/MM/YYYY").unix();
                //console.log(xDate+" - "+yDate);
					      if (xDate > yDate) {
				  	      //if so, mark as a switch and break the loop:
				  	      shouldSwitch = true;
				  	      break;
				  	    }
            	}else{
            	  if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                  //console.log('type different de date');
                  //console.log(x.innerHTML+" - "+y.innerHTML);
            	    //if so, mark as a switch and break the loop:
            	    shouldSwitch = true;
            	    break;
            	  }
              }
            } else if (dir == "desc") {
            	// check if x is a date
            	if (x.innerHTML.match(/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/)) {
					      var xDate = moment(x.innerHTML, "DD/MM/YYYY").unix();
					      var yDate = moment(y.innerHTML, "DD/MM/YYYY").unix();
                //console.log(xDate+" - "+yDate);
					      if (xDate < yDate) {
				        	//if so, mark as a switch and break the loop:
				        	shouldSwitch = true;
				        	break;
				        }
            	} else {
            	  if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
            	  	//if so, mark as a switch and break the loop:
            	  	shouldSwitch = true;
            	  	break;
                }  
            	}
            }
        }
        if (shouldSwitch) {
            /*If a switch has been marked, make the switch
              and mark that a switch has been done:*/
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            //Each time a switch is done, increase this count by 1:
            switchcount++;
        } else {
            /*If no switching has been done AND the direction is "asc",
              set the direction to "desc" and run the while loop again.*/
            if (switchcount == 0 && dir == "asc") {
              dir = "desc";
              switching = true;
            }
        }
    }
}
  
function filterArrow(e){
    e = e || window.event;
    var target = e.target || e.srcElement;
    //console.log(target.childNodes[1]);
    if(target.childNodes[1] != undefined){
        target = target.childNodes[1];
        if(target.classList.contains("bi-arrow-up")){
            target.classList.remove("bi-arrow-up");
            target.classList.add("bi-arrow-down");
        }else{
            target.classList.remove("bi-arrow-down");
            target.classList.add("bi-arrow-up");
        }
    }
}