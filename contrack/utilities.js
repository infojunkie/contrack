/*
* Copyright OpenCraft |  http://www.open-craft.com
* ------------------------------------------------------------------
*
* Contrack Order Management System
*
* Details and latest version at:
* http://contrack.sourceforge.net
*
* $Id: $
*
* This software is distributed under the GNU General Public License.
* Read the entire license text here: http://www.gnu.org/licenses/gpl.html
*/

/****************************************************************
	Javascript Constants and Utilities functions
*****************************************************************/

// State constants
// These have to be synched up with the PHP contants in general_utils.php
var stateVIEW    	= 1;
var stateEDIT    	= 2;
var stateNEW     	= 3;
var statePROCESS 	= 4;
var stateCLOSEPOPUP = 5;
// Operation Codes (mostly for invoices)
var opcodeDELETE 	= 1;
var opcodeSAVE 	 	= 2;
var opcodeSEND 	 	= 3;
var opcodeCREDIT 	= 4;
var opcodeCLOSE  	= 5;
var opcodePRINT  	= 6;
var opCodeREMOVE 	= 7;
var opcodeADDXPAYMENT = 8;

//Milestone Types
var milestoneINCOMING_PAYMENT  = 1;
var milestoneAGENT_COMMISSION = 2;
var milestoneB2S_PAYMENT      = 3;

//Entity Types
var entityBUYER 		  = 1;
var entitySUPPLIER 		  = 2;
var entityAGENT 		  = 3;
var entityEXTERNAL 		  = 4;
var entityINTERNAL 		  = 5;
var entityINVOICE 		  = 6;
var entityGEN_ORDER 	  = 7;
var entitySINGLE_ORDER 	  = 8;
var entityPRODUCT 		  = 9;
var entityMILESTONE  	  = 10;
var entityPROD_SUMMARY 	  = 11;
var entityPROD_PHASE 	  = 12;
var entityPROD_STEP 	  = 13;
var entityPAYMENT 		  = 14;
var entityUSER 			  = 15;
var entityADDITIONAL_COST = 16;
var entityCURRENCY 		  = 98;
var entitySEARCH 		  = 99;

//Organization's Party Id
var companyPartyID = 1;

/*
	Function to emulate opening a modal popup window by shifting the focus to the
	newly opened window and discarding all events occuring in the parent (opener)
	window.
*/
function open_popup(URL, windowName, windowFeatures, winHeight, winWidth)
{
	// Centre the popup
	var winLeft = (screen.width - winWidth) / 2;
	var winTop  = ((screen.height - winHeight) / 2) - 100; // raise it a bit to the top
	// Set windows features
	windowFeatures = windowFeatures + ',screenX='+winLeft+',screenY='+winTop+',top='+winTop+',left='+winLeft+',height='+winHeight+',width='+winWidth;
	
	var w = window.open(URL, windowName, windowFeatures);
	
	window.onactivate                      =
	window.onfocus                         =
	window.document.onfocus                =
	window.document.onactive               =
	window.document.onclick                =
	window.document.onmousedown            =
	window.document.body.onclick           =
	window.document.body.onmousedown       =
	window.document.body.onresizestart     =
	window.document.body.onbeforeactivate  =
	window.document.body.onfocusin         =
	window.document.body.onactivate        =
	window.document.body.onbeforeeditfocus =
	window.document.body.ondrag            =
	window.document.body.ondragstart       =
	window.document.body.onscroll          =
	window.document.body.onfocus           =
	window.document.body.onclick           =
	window.document.body.oncontextmenu     =
	window.document.body.ondblclick        =
	window.document.body.onselectstart     =

	function(e){
	
		if (!w.closed){
	
			//cross browser event killing
			if (e && e.cancelable){
				e.preventDefault();
				e.stopPropagation();
			}
			else if (event){
				event.returnValue = false;
				event.cancelBubble = true;
			}
	
			w.focus();
		}
	}
	return w;
}

// Depending on state of popup resubmit opener window and close popup
function willIClose(state, windowRef)
{	
	if (state == stateCLOSEPOPUP ) {
		windowRef.opener.resubmitForm();
		windowRef.close();
	}
}

// Set sort_by and prior_sort_by in the form and submit to do the sorting
function doSort(frm, columnName, priorSortBy) 
{
	frm.sort_by.value = columnName;
	frm.prior_sort_by.value = priorSortBy;	
	frm.submit();
}

// Delete Admin entity
function admEntityDelete(frm, strName, intID) 
{
	if (confirm ('Are you sure you want to delete this ' + strName + '?') ) {
		frm.state.value		      = statePROCESS;
		frm.adm_entity_op.value   = opcodeDELETE;
		frm.adm_entity_id.value   = intID;		
		frm.submit();	
	}
}

//Tell the file element that it received a click
function doFileBrowse(ctlName)
{	
	document.getElementById(ctlName).click();
}

function validateNumeric(fieldName, value)
{	
	var re = /^[0-9][0-9]*\.?[0-9]*$/;
	if(!re.test(value))	{
		alert("Please enter a valid numeric value for " + fieldName);
		return false;
	}	
	else {
		return true;
	}
}

function validateUsername(userName) 
{
	/*
	var userNameRegex = "\w{4,12}";
	var regex = new RegExp(userNameRegex);
	return regex.test(userName);
	*/
	// Quick fix until the regex comes !
	if (!userName) return false;
   	var illegalChars = "*|,\":<>[]{}`\';()@&$#%";
   	for (var i = 0; i < userName.length; i++) {
    	if (illegalChars.indexOf(userName.charAt(i)) != -1)	return false;
   	}
	return true;
}

function validateEmail(email) 
{
	if (email.length != 0 ) {
		var emailRegex = "^.+@.+\\..+$";
		var regex = new RegExp(emailRegex);
		return regex.test(email);
	}
	else {
		return true;
	}
}

function validateDate(date, name)
{
	if (date.value == "") return true;
	var error = 'Invalid '+ name +'. Please choose a valid date.';
	var re = /^((((31(0?[13578]|1[02]))|((29|30)(0?[1,3-9]|1[0-2])))(1[6-9]|[2-9]\d)?\d{2})|(290?2(((1[6-9]|[2-9]\d)?(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00))))|(0?[1-9]|1\d|2[0-8])((0?[1-9])|(1[0-2]))((1[6-9]|[2-9]\d)?\d{2}))$/;
	if (re.test(date.value)) {
		var str = date.value;
		if (8 != str.length) {
			alert(error);
			return false;
		}
		str = str.substr(0,2) + "/" + str.substr(2,2) + "/" + str.substr(4);
		date.value = str;
		return true;
	}
	re = /^((((31\/(0?[13578]|1[02]))|((29|30)\/(0?[1,3-9]|1[0-2])))\/(1[6-9]|[2-9]\d)?\d{2})|(29\/0?2\/(((1[6-9]|[2-9]\d)?(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00))))|(0?[1-9]|1\d|2[0-8])\/((0?[1-9])|(1[0-2]))\/((1[6-9]|[2-9]\d)?\d{2}))$/;
	if (re.test(date.value)) {
		str = date.value;
		if (10 != str.length) {
			alert(error);
			return false;
		}		
		return true;
	}
	alert(error);
	return false;
}


function createCalendar(ctlName, btnName)
{
	//alert ("in createCalendar. ctlName = " + ctlName);
	Calendar.setup(
    {
	  inputField     :    ctlName,          
      ifFormat       :    "%d/%m/%Y",       
      button         :    btnName,         
      singleClick    :    true,
	  firstDay       :    0,
	  weekNumbers    :    false,
	  align          :   "cR"
    }
  );
}