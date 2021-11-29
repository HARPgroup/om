// cookies.js
// Derived from the Bill Dortch code at http://www.hidaho.com/cookies/cookie.txt

var today = new Date();
var expiry = new Date(today.getTime() + 730 * 24 * 60 * 60 * 1000);

function getCookieVal (offset) {
    var endstr = document.cookie.indexOf (";", offset);
    if (endstr == -1) { endstr = document.cookie.length; }
    return unescape(document.cookie.substring(offset, endstr));
    }

function GetCookie (name) {
    var arg = name + "=";
    var alen = arg.length;
    var clen = document.cookie.length;
    var i = 0;
    while (i < clen) {
        var j = i + alen;
        if (document.cookie.substring(i, j) == arg) {
            return getCookieVal (j);
            }
        i = document.cookie.indexOf(" ", i) + 1;
    if (i == 0) break;
        }
    return null;
    }

function DeleteCookie (name,path,domain) {
    if (GetCookie(name)) {
        document.cookie = name + "=" +
        ((path) ? "; path=" + path : "") +
        ((domain) ? "; domain=" + domain : "") +
        "; expires=Thu, 01-Jan-70 00:00:01 GMT";
        }
    }

function SetCookie (name,value,expires,path,domain,secure) {
  document.cookie = name + "=" + escape (value) +
    ((expires) ? "; expires=" + expires.toGMTString() : "") +
    ((path) ? "; path=" + path : "") +
    ((domain) ? "; domain=" + domain : "") +
    ((secure) ? "; secure" : "");
}


function LtoF(listname,fieldname) { //SETTING A FIELD WITH THE VALUE SELECTED IN A PULLDOWN
   document.forms['adminform'].elements[fieldname].value =
document.forms['adminform'].elements[listname].options[document.adminform.elements[listname].selectedIndex].text;
}

function openWin(thisURL,w,h, window_name) { // open a window to a certain url with specified height and width
   if (window_name == null) {
        window_name = 'Data Window';
   }
   paramstring = "height=" + h + ",innerheight=" + h + ",width=" + w + ",innerwidth=" + w + ",toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes";
   w1 = window.open(thisURL,window_name,paramstring);
   w1.focus();
   return false;
}

function guestlog(logpath) {
   var thislog = GetCookie("soilslog");
   if ( (thislog == "later") || !(thislog) ) {
      openWin(logpath,300,400);
   }
}

function openWindow(spage,w,h)

{
   openWin(spage,w,h);
}



function hex(nmbr, base) {
    var hexStr = "ABCDEF";
    var hexVal = Math.round(nmbr * 255 / base)
    dig1 = hexVal % 16;
    dig2 = (hexVal - dig1)/16;
    if (dig1 > 9) {dig1 = hexStr.charAt(dig1 - 10)}
    if (dig2 > 9) {dig2 = hexStr.charAt(dig2 - 10)}
    hexVal = dig2.toString() + dig1.toString()
    return hexVal;
}
function colorVal(color, ang, vect, xPos, yPos) {
    with (Math) {
        var angCorr = 0;    // Rotation angle = 0 if Red
        if (color == "green") {angCorr = 2*PI/3};
        if (color == "blue") {angCorr = 4*PI/3};
        var angVal = ang - angCorr; // Apply rotation
        if (angVal < 0) {angVal += 2*Math.PI}   // If angle negative add 360 degrees
        var xVal = xPos;    // If red, set x-value
        var yVal = yPos;    // If red, set y-value
        if (color != "red") {
            yVal = abs(vect * sin(angVal)); // Calculate new x and y
            xVal = abs(vect * cos(angVal));
            if (angVal > PI/2 && angVal < 3*PI/2) { // Get the sign right
                xVal = -1 * xVal;
            }
        }
        var colval = 0; //Initialize the color value
        if (angVal <= 2*PI/6 || angVal >= 10*PI/6) {    // If inside the quadrant
            colVal = 65535;
        }
        else {  // If outside the quadrant,
            var x1 = sqrt(100*100 - yVal*yVal) + xVal;
            var x2 = abs(yVal)/tan(PI/3) - xVal;
            colVal = 65535 * x1/(x1 + x2);
        }
        colVal = round(colVal);
        return colVal;
    }
}
cursX = 0;
cursY = 0;
red = 0;
green = 0;
blue = 0;
function getXY() {
    cursX = window.event.x;
    cursY = window.event.y;
}
function showXY() {
        if(document.colorVals.bright.value < 100) {
            document.colorVals.bright.value = 100;
        }
        cursX = cursX - 54-5;
        cursY = -(cursY - 54)+5;
        var r = Math.sqrt(cursX*cursX + cursY*cursY);
        if (r <= 100) {
            with (Math) {
                var theta = asin(abs(cursY/r));
                if (cursX < 0 && cursY  > 0) {theta = PI - theta};
                if (cursX < 0 && cursY < 0) {theta += PI};
                if (cursX >= 0 && cursY < 0) {theta = 2*PI - theta};
                var theta_deg = theta*360/2/PI;
            }
            red = colorVal("red", theta, r, cursX, cursY);
            green = colorVal("green", theta, r, cursX, cursY);
            blue = colorVal("blue", theta, r, cursX, cursY);
            red = hex(red, 65535);
            green = hex(green, 65535);
            blue=hex(blue, 65535);
            document.colorVals.red.value = red;
            document.colorVals.green.value = green;
            document.colorVals.blue.value = blue;
            //document.getElementById('red').value= red;
            //document.getElementById('green').value= green;
            //document.getElementById('blue').value= blue;
            document.bgColor = "#" + red + green + blue;
            //document.table.chip.bgcolor = "#" + red + green + blue;
        }
        else {
            alert("Please click inside the circle")
        }
}
function cBright() {
    bVal = document.colorVals.bright.value;
    if (bVal <= 100 && bVal >= 0) {
        with (Math) {
            var newRed = round(eval("0x" + red) * bVal/100);
            var newGreen = round(eval("0x" + green) * bVal/100);
            var newBlue = round(eval("0x" + blue) * bVal/100);
        }
        newGreen = hex(newGreen, 255);
        newRed = hex(newRed, 255);
        newBlue = hex(newBlue, 255);
        document.colorVals.red.value = newRed;
        document.colorVals.green.value = newGreen;
        document.colorVals.blue.value = newBlue;
        document.bgColor = "#" + newRed + newGreen + newBlue;

    }
    else {
        alert("Brightness values must be 0 - 100");
    }
}
function stopIt() {
    alert("\nDon't Mess With That!");
    document.colorVals.bright.focus();
}

function toggleMenu(objID) {
if (!document.getElementById) return;
var ob = document.getElementById(objID).style;
ob.display = (ob.display == 'block')?'none': 'block';
}

function toggle_button(objID) {
   e = document.getElementById(objID);
//   ITEM  = '\u00A0';
//   OPEN  = '\u25B7';
//   CLOSE = '\u25BD';
//   CLOSE  = '&#9658;';
//   OPEN = '&#9660;';
   ITEM  = '\u00A0';
   CLOSE  = String.fromCharCode(9658);
   OPEN = String.fromCharCode(9660);

   e.innerHTML=(e.innerHTML==OPEN) ? CLOSE : OPEN;
   for (var c, p=e.parentNode, i=0; c=p.childNodes[i]; i++)
   if (c.tagName=='UL') c.style.display=(c.style.display=='none') ? 'block' : 'none';
}

function confirmation(formName,confText) {
    var answer = confirm(confText)
    if (answer){
        document.forms[formName].submit();
    }
}

// tabbed browsing script
// Gleaned from
// http://www.aliroman.com/article/how-to-create-web-tabs-with-javascript-show-hide-layers-34-1.html ";
// MOdified by Robert Burgholzer to accept multiple tabbed browsers per page
last_tab = new Array()
last_button = new Array();
function show(layerName) {
   document.getElementById(layerName).style.display = '';
}

function hide(layerName) {
   document.getElementById(layerName).style.display = 'none';
}

function show_next(tab_name, button_name, group_name) {
    if (group_name == null) {
        group_name = 'modelout';
    }
   //if (last_tab[group_name] != '') {
       //alert("Last Tab Name: " + group_name + " Last Tab Value: " + last_tab[group_name]);
   if (last_tab[group_name]) {
      //alert("Last Tab Name: " + group_name + " Last Tab Value: " + last_tab[group_name]);
      document.getElementById(last_tab[group_name]).className = 'tab';
      hide(last_tab[group_name]);
   }
   //if (last_button[group_name] != '') {
   if (last_button[group_name]) {
      document.getElementById(last_button[group_name]).className = '';
   }
   var curr = document.getElementById(tab_name);
   curr.className='tab_hover';
   var butt = document.getElementById(button_name);
   butt.className='active';
   show(tab_name);
   last_tab[group_name]=tab_name;
   last_button[group_name]=button_name;
   //alert("Setting Last Tab Name for group: " + group_name + " to: " + tab_name + " result: " + last_tab[group_name]);
}


function refreshimage(imgname){
   var rand = 10000 * Math.random();
   document.getElementById(imgname).src = document.getElementById(imgname).src + '?rand=' + rand;
}

function formRowPlus(parentid, rowid, thiselement) {
   var thisParent = document.getElementById(parentid);
   var thisRow = document.getElementById(rowid);
   //alert(thisRow.type);
   var newRow = thisRow.cloneNode(true);
   //k = thisParent.childNodes
   for (i = 0; i < thisRow.childNodes.length; i++) {
      //'button,'checkbox','file','hidden',image','password','radio','reset','select-one','select-multiple','submit','text','textarea'
      //alert(thisRow.childNodes[i].id + ' ' + thisRow.childNodes[i].index);
   }
   thisParent.appendChild(newRow);
}


function formRowMinus(parentid, rowNode) {
   var thisParent = document.getElementById(parentid);
   for (i = 0; i < thisParent.childNodes.length; i++) {
      if (thisParent.childNodes[i] == rowNode) {
         thisParent.removeChild(thisParent.childNodes[i]);
      }
   }
}

function addColumn(formid, grid_id,cellHTML){
   for (i = 0; i < document.getElementById(grid_id).rows.length; i++) {
       var x=document.getElementById(grid_id).rows[i]
       var y=x.insertCell(x.cells.length)
       y.innerHTML=cellHTML
   }
}


function deleteColumn(formid, grid_id, colnum){
   // column comes in as a 1-based index, instead of 0-based
   for (i = 0; i < document.getElementById(grid_id).rows.length; i++) {
       var x=document.getElementById(grid_id).rows[i];
       var y=x.deleteCell(colnum - 1);
   }
}

function addRow(formid, grid_id,cellHTML){
   var thisTable = document.getElementById(grid_id);
   // append new row to the end of the table
   var newRow = thisTable.insertRow(thisTable.rows.length);

   for (i = 0; i < thisTable.rows[0].cells.length; i++) {
       var y=newRow.insertCell(newRow.cells.length);
       y.innerHTML=cellHTML;
   }

}

function cloneLastRow(tblID){
   var thisTable = document.getElementById(tblID);
   var lastrow = thisTable.rows.length;
   // append new row to the end of the table
   var newRow = thisTable.insertRow(lastrow);
   for (i = 0; i < thisTable.rows[lastrow - 1].cells.length; i++) {
       var y=newRow.insertCell(newRow.cells.length);
       y.innerHTML=thisTable.rows[lastrow - 1].cells[i].innerHTML;
   }
}


function deleteRow(grid_id, rownum){
   var thisParent = document.getElementById(grid_id);
   // column comes in as a 1-based index, instead of 0-based
   for (i = 0; i < thisParent.childNodes.length; i++) {
      if (thisParent.childNodes[i] == (rownum - 1) ) {
         thisParent.removeChild(thisParent.childNodes[i]);
      }
   }
}

function getColumn(e) {
   var ColNum=1;
   if(navigator.userAgent.indexOf("MSIE")!=-1) {
      ColNum+=event.srcElement.cellIndex;
   } else {
     ColNum+=e.target.cellIndex;
   }
   return ColNum;
}

function _getCellIndex(cell) {
   var rtrn = cell.cellIndex || 0;
   if (rtrn == 0) {
       do{
           if (cell.nodeType == 1) rtrn++;
           cell = cell.previousSibling;
       } while (cell);
       --rtrn;
   }
   return rtrn;
}//eof getCellIndex

function incrementFormField(formid, varname, increment) {
   x=parseInt(document.forms[formid].elements[varname].value);
   y=parseInt(increment);
   document.forms[formid].elements[varname].value = (x + y);

}

function showIt(elID) {
   var el = document.getElementById(elID);
   el.scrollIntoView(true);

}

function showAnchor(aName) {
   window.location.hash=aName;
}



function getFormVar(f,v) {
   var currEl, currGrp, e = 0;
   while (currEl = f.elements[e++]) {
      if (currEl.name == v) {
         return currEl;
      }
   }
   return false;
}


function clearForm(formobject, fieldstoclear) {
   var thisform = document.getElementById(formobject);
   for (i=0; i< fieldstoclear.length; i++) {

      thisEl = getFormVar(thisform, fieldstoclear[i]);
      field_type = thisEl.type.toLowerCase();

      switch(field_type) {

         case "text":
         case "password":
         case "textarea":
         case "hidden":
            thisEl.value = "";
         break;

         case "radio":
         case "checkbox":
         if (thisEl.checked) {
            thisEl.checked = false;
         }
         break;

         case "select-one":
         case "select-multi":
            thisEl.selectedIndex = -1;
         break;

         default:
         break;

      }

   }
}

function setCheckedValue(radioObj, newValue) {
   if(!radioObj)
      return;
   var radioLength = radioObj.length;
   if(radioLength == undefined) {
      radioObj.checked = (radioObj.value == newValue.toString());
      return;
   }
   //alert("This radio button has " + radioLength);
   for(var i = 0; i < radioLength; i++) {
      radioObj[i].checked = false;
      if(radioObj[i].value == newValue.toString()) {
         radioObj[i].checked = true;
      }
   }
}

function getSelectedRadio(buttonGroup) {
   // returns the array number of the selected radio button or -1 if no button is selected
   if (buttonGroup[0]) { // if the button group is an array (one button is not an array)
      for (var i=0; i<buttonGroup.length; i++) {
         if (buttonGroup[i].checked) {
            return i
         }
      }
   } else {
      if (buttonGroup.checked) { return 0; } // if the one button is checked, return zero
   }
   // if we get to this point, no radio button is selected
   return -1;
} // Ends the "getSelectedRadio" function

function getSelectedRadioValue(buttonGroup) {
   // returns the value of the selected radio button or "" if no button is selected
   var i = getSelectedRadio(buttonGroup);
   if (i == -1) {
      return "";
   } else {
      if (buttonGroup[i]) { // Make sure the button group is an array (not just one button)
         return buttonGroup[i].value;
      } else { // The button group is just the one button, and it is checked
         return buttonGroup.value;
      }
   }
} // Ends the "getSelectedRadioValue" function
