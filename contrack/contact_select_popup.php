
<?php
/**
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
?>

<?php
/******************************************************************************************
  	Contact Select popup page

	This page is responsible for displaying the contacts selection popup.
	It has two states: VIEW and PROCESS
	It contains:
		- a drop-down of available employer types,
		- a drop-down populated based on the employer type selected,
		- and a table within a DIV showing the contacts for the employer selected with checkboxes
******************************************************************************************/
require_once("db_utils.php");
require_once("general_utils.php");
require_once("patUser-2.2.3/prepend.php" );
require_once("permissions.php");

init_page ();
process_state();

/****************************************************
	Page Initialisation
	- Authenticate
	- Get all parameters passed to the page
	- Check for any errors passed to the page
****************************************************/
function init_page()
{
	global $user;
	// Authenticate
	if (! ($user->isAuthenticated())){
		deny_access();
	}
	get_page_params ();
	check_error();
}
/*
	Get key parameters
*/
function get_page_params()
{
	global $state, $referrer_id, $referrer_type, $employer_id, $employer_type;

	// Get key parameters
	$state  	   = isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : "");
	$referrer_id   = isset($_GET['referrer_id']) ? $_GET['referrer_id'] : (isset($_POST['referrer_id']) ? $_POST['referrer_id'] : -1);
	$referrer_type = isset($_GET['referrer_type']) ? $_GET['referrer_type'] : (isset($_POST['referrer_type']) ? $_POST['referrer_type'] : -1);
	$employer_id   = isset($_GET['employer_id']) ? $_GET['employer_id'] : (isset($_POST['EMPLOYER_ID']) ? $_POST['EMPLOYER_ID'] : 0);
	$employer_type = isset($_GET['employer_type']) ? $_GET['employer_type'] : (isset($_POST['EMPLOYER_TYPE']) ? $_POST['EMPLOYER_TYPE'] : 0);
}

function process_state()
{
	global $state;

	// Check state
	if ( $state == state_PROCESS) {
		process_form();
	}
}

/*
	Get values of contacts checkboxes from posted form
	Notes:
		Had to break up the SQL batch into multiple statements because this is not
		supported in MySQL 4.0.
		For further information check: http://bugs.mysql.com/bug.php?id=3745
*/
function process_form()
{
	global $referrer_id;

	if (isset($_POST['contact'])) {
		if (safe_count($_POST['contact']) > 0){
			$i = 0;
			foreach ($_POST['contact'] as $contact_id) {
				$sql = generate_insert_contacts_sql($referrer_id, $contact_id);
				$result = mysql_query($sql) or die(mysql_error());
				if (!$result) return mysql_error();
			}
		}
	}
}
/*
	Generate the SQL code to select the contacts for a given employer
*/
function generate_select_contacts_sql($referrer_id, $referrer_type, $employer_id)
{
	if ($referrer_type == entity_GEN_ORDER) {
		$select_sql = generate_gen_order_select_contacts_sql($referrer_id, $employer_id);
	}
	else if ($referrer_type == entity_SINGLE_ORDER) {
		$select_sql = generate_single_order_select_contacts_sql($referrer_id, $employer_id);
	}
	else if ($referrer_type == entity_USER) {
		$select_sql = generate_generic_select_contacts_sql($referrer_id, $employer_id);
	}
	return $select_sql;
}
/*
	SQL for general order contacts
	SQL Logic:
		Select all contacts for that particular employer while determining if any of them
		is already selected for that order
*/
function generate_gen_order_select_contacts_sql($referrer_id, $employer_id)
{
	$select_sql = " SELECT	party.PARTY_ID, party.NAME AS NAME, party.POSITION AS POSITION,
					party.MOBILE AS MOBILE, employer.TYPE AS EMPLOYER_TYPE,
					IF (gen_order_rel_party.GEN_ORDER_ID IS NULL, 0, 1) AS IS_SELECTED
					FROM  party
						INNER JOIN party AS employer ON party.EMPLOYER_ID = employer.PARTY_ID
						LEFT JOIN  gen_order_rel_party ON party.PARTY_ID = gen_order_rel_party.PARTY_ID
							   AND gen_order_rel_party.GEN_ORDER_ID = ".$referrer_id.
				  " WHERE party.EMPLOYER_ID= ".$employer_id." AND party.EMPLOYER_ID <> 0";
	return $select_sql;
}
/*
	SQL for single order contacts
	SQL Logic:
		Same as above
*/
function generate_single_order_select_contacts_sql($referrer_id, $employer_id)
{
	$select_sql = " SELECT	party.PARTY_ID, party.NAME AS NAME, party.POSITION AS POSITION,
							party.MOBILE AS MOBILE, employer.TYPE AS EMPLOYER_TYPE,
							IF (single_order_rel_party.SINGLE_ORDER_ID IS NULL, 0, 1) AS IS_SELECTED
					FROM  party
						INNER JOIN party AS employer ON party.EMPLOYER_ID = employer.PARTY_ID
						LEFT JOIN  single_order_rel_party ON party.PARTY_ID = single_order_rel_party.PARTY_ID
							   AND single_order_rel_party.SINGLE_ORDER_ID = ".$referrer_id.
				  " WHERE party.EMPLOYER_ID= ".$employer_id." AND party.EMPLOYER_ID <> 0";
	return $select_sql;
}

/*
	SQL for generic select contacts
	SQL Logic:
		Select all contacts for that particular employer
*/
function generate_generic_select_contacts_sql($referrer_id, $employer_id)
{
	$select_sql = " SELECT DISTINCT party.PARTY_ID, party.NAME AS NAME, party.POSITION AS POSITION,
					party.MOBILE AS MOBILE, employer.TYPE AS EMPLOYER_TYPE,
					IF (party.party_id <> ".$referrer_id.", 0,  1) AS IS_SELECTED
					FROM  party
					INNER JOIN party AS employer ON party.EMPLOYER_ID = employer.PARTY_ID
					LEFT JOIN user ON user.party_id = party.PARTY_ID
					WHERE party.EMPLOYER_ID= ".$employer_id."  AND party.EMPLOYER_ID <> 0";
	return $select_sql;
}

/*
	SQL to insert into order_rel_party relationships tables
*/
function generate_insert_contacts_sql($referrer_id, $contact_id)
{
	global $referrer_type ;
	if ($referrer_type == entity_GEN_ORDER) {
		$insert_sql = generate_gen_order_insert_contacts_sql($referrer_id, $contact_id);
	}
	else {
		$insert_sql = generate_single_order_insert_contacts_sql($referrer_id, $contact_id);
	}
	return $insert_sql;
}
/*
	SQL to insert into gen_order_rel_party table
*/
function generate_gen_order_insert_contacts_sql($referrer_id, $contact_id)
{
	$insert_sql = "INSERT INTO gen_order_rel_party (GEN_ORDER_ID, PARTY_ID)
					VALUES ( ".$referrer_id.", ".$contact_id." ); ";
	return $insert_sql;
}
/*
	SQL to insert into single_order_rel_party table
*/
function generate_single_order_insert_contacts_sql($referrer_id, $contact_id)
{
	$insert_sql = "INSERT INTO single_order_rel_party (SINGLE_ORDER_ID, PARTY_ID)
					VALUES (".$referrer_id.", ".$contact_id."); ";
	return $insert_sql;
}

/*
	Generate contacts block
*/
function show_contacts()
{
	global $glb_arr_values, $state, $employer_type, $employer_id, $referrer_type, $referrer_id;

 	// Get contacts from DB
	$sql = generate_select_contacts_sql($referrer_id, $referrer_type, $employer_id);
	$result = mysql_query($sql) or die(mysql_error());

	if (mysql_num_rows($result) == 0 ){
		if (($employer_type > 0) && ($employer_id > 0 )) {
			$markup =  '<table>
						<tr class="contacts_header">
						<td class="small_text"><a href="javascript:
								void open_popup(\'contact_edit_popup.php?employer_id='.$employer_id.'&type='.
								$employer_type .'&state='.state_NEW.'\',\'contactEdit\',\'menubar=no,toolbar=no,scrollbars=no\',520,580);">
								<img src="images/b.add.png" alt="Add" title="Add"/></a></td>'."\r\n" ;
			$markup .=	'<td><input type="checkbox" id="checkAll" name="checkAll" onclick="setupCheckboxes(this.checked, this.id);"></td>
						<td>Name</td>
						<td>Position</td>
						<td>Mobile</td></tr></table>';
		}
		else {
			$markup = '';
		}
	}
	else {
		$markup =  '<table>
					<tr class="contacts_header">
					<td class="small_text"><a href="javascript:
							void open_popup(\'contact_edit_popup.php?employer_id='.$employer_id.'&type='.
							$employer_type .'&state='.state_NEW.'\',\'contactEdit\',\'menubar=no,toolbar=no,scrollbars=no\',520,580);">
							<img src="images/b.add.png" alt="Add" title="Add"/></a></td>'."\r\n" ;
		$markup .=	'<td><input type="checkbox" id="checkAll" name="checkAll" onclick="setupCheckboxes(this.checked, this.id);"></td>
					<td>Name</td>
					<td>Position</td>
					<td>Mobile</td></tr>';
		$i=0;
		while ($row = mysql_fetch_array($result)) {
			$pk    		 = $row['PARTY_ID'];
			$name  		 = mysql_decode($row['NAME']);
			$position	 = mysql_decode($row['POSITION']) ;
			$mobile		 = mysql_decode($row['MOBILE']);
			$checked	 = ($row['IS_SELECTED']) ? ' checked="checked" disabled="disabled" ' : '';
			$markup .= "<tr class=\"contacts_line" .($i%2). "\">";
			$markup .= '<td class="small_text"><a href="javascript:
							void open_popup(\'contact_edit_popup.php?employer_id='.$employer_id.'&type='.
							$employer_type .'&party_id='.$pk.'&state='.state_EDIT.'\',\'contactEdit\',\'menubar=no,toolbar=no,scrollbars=no\',520,580);">
							<img src="images/b.edit.png" alt="Edit" title="Edit"/></a></td>'."\r\n" ;
			$markup .= '<td><input type="checkbox" value="'.$pk.'" id="contact" name="contact[]" onclick="setupCheckboxes(this.checked, this.id);"'. $checked .'></td>';
			$markup .= '<td>'. $name . '</td>';
			$markup .= '<td>'.$position.'</td>';
			$markup .= '<td>'.$mobile.'</td>';
			$markup .= '</td></tr>'."\r\n";
			++$i;
		}  // end while
		mysql_free_result($result);
		// Contacts table footer
		$markup .= '</table>'."\r\n" ;
	}
	return $markup;
}
/*
	Generates standard form header for contact
*/
function generate_form_header()
{
	global $state, $referrer_type, $referrer_id, $employer_type, $employer_id;

	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_select_contact\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"referrer_id\" name=\"referrer_id\" value=\"" . $referrer_id . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"referrer_type\" name=\"referrer_type\" value=\"" . $referrer_type . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"EMPLOYER_ID\" name=\"EMPLOYER_ID\" value=\"" . $employer_id . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"EMPLOYER_TYPE\" name=\"EMPLOYER_TYPE\" value=\"" . $employer_type . "\"/>\r\n";
	return $str_frm_header;
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Contrack - Select Contact</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="contrack.css" rel="stylesheet" type="text/css" media="all" />
</head>
<script src="utilities.js" type="text/javascript"></script>
<script type="text/javascript"><!--

/*
	Setup main chexkbox on loading of the window
*/
window.onload = function() { setupMainCheckbox(); }

function setupMainCheckbox()
{
	// Go through all the contacts checkboxes. If all of them are disabled(and of course checked)
	// then disable and check the Main checkbox
	if (document.frm_select_contact != null) {
		var contactsCheckboxes = document.frm_select_contact.contact;
		var mainCheckbox 	   = document.frm_select_contact.checkAll;
		var boolCheckAndDisable = true;

		if (contactsCheckboxes != null) {
			if (contactsCheckboxes.length) {  // If length is defined that means we have more than 1 row of contacts
				for (i=0; i < contactsCheckboxes.length; i++) {
					if (!contactsCheckboxes[i].disabled)  {   //If any is not disabled, set to false and break out of loop
						boolCheckAndDisable = false;
						break;
					}
				}
			}
			else {  // if length is undefined, we have only one contact
				if (!contactsCheckboxes.disabled)  {
					boolCheckAndDisable = false;
				}
			}
			mainCheckbox.checked  = boolCheckAndDisable;
			mainCheckbox.disabled = boolCheckAndDisable;
		}
	}
	else {
	return;
	}
}
/*
	Popup stock yes/no msgbox
*/
function popupYesNo(strMsg, trigger)
{
	winHeight = 30;
	winWidth  = 320;
	winFeatures = 'menubar=no,toolbar=no,titlebar=no,resizable=no,scrollbars=no';
	open_popup('yes_no_popup.php?display_msg='+strMsg+'&trigger='+trigger ,'YesNoPopup', winFeatures, winHeight, winWidth);
}

/*
	This function is called from the popup to pass back the value (Yes or No)
*/
function popupCallback(yes, trigger)
{
	if (yes) {
		submitDDValues = true;
		addContact(submitDDValues, trigger);
	}
	else {
		if (trigger=='EmployerType') {
			submitEmployerType();
		}
		else {
			submitEmployer();
		}
	}
}

/*
	Called on the onchange event in any of the two dropdowns
	If any checkbox is selected and is enabled that means it hasn't been added to the order yet.
	Ask user whether she wants to add it to order before proceeding with the change
*/
function ddChange(ddName)
{
	// Are any contacts selected? If so, popup MsgBox and return
	if (checkContactSelected()) {
		popupYesNo("You have not saved the contacts you selected.\n Would you like to save them?", ddName);
		return;
	}
	// No contacts selected so proceed normally
	if (ddName=='EmployerType') {
		submitEmployerType();
	}
	else {
		submitEmployer();
	}
}

function submitEmployerType()
{
	var frm = document.frm_select_contact;
	employerType = frm.EMPLOYER_TYPE_DD;
	frm.state.value = stateVIEW;
	frm.EMPLOYER_TYPE.value = employerType.options[employerType.selectedIndex].value;
	frm.EMPLOYER_ID.value = 0;
	frm.submit();
}

function submitEmployer()
{
	var frm = document.frm_select_contact;
	employer = frm.EMPLOYER_DD;
	var dropdownValue = employer.options[employer.selectedIndex].value;
	frm.state.value = stateVIEW;
	arrEmployerIDType = dropdownValue.split(',');
	frm.EMPLOYER_ID.value   = arrEmployerIDType[0];
	frm.EMPLOYER_TYPE.value = arrEmployerIDType[1];
	frm.submit();

}

/*
	Setup checkboxes on each click on any of the checkboxes including the main one.
	Note: Disabled checkboxes are for those contacts already selected for that order
*/
function setupCheckboxes (boolIsChecked, strCheckboxName)
{
	var contactsCheckboxes = document.frm_select_contact.contact;
	var mainCheckbox 	   = document.frm_select_contact.checkAll;

	// Is this the main checkbox?
	if (strCheckboxName == 'checkAll') {
		// Go through them and check/uncheck all
		if (contactsCheckboxes.length) {  // If length is defined that means we have more than 2 rows
			for (i=0; i < contactsCheckboxes.length; i++) {
				if (!contactsCheckboxes[i].disabled) { 		// don't touch the disabled ones!
					contactsCheckboxes[i].checked = boolIsChecked;
				}
			}
		}
		else {  // if length is undefined, we have only one row and one checkbox so set/unset it.
			if (!contactsCheckboxes.disabled) {   // only if it's *not* disabled
				contactsCheckboxes.checked = boolIsChecked;
			}
		}
	} // A regular checkbox has been checked?
	else if (strCheckboxName == 'contact') {
		// Has it just been unchecked?
		if(boolIsChecked == false) {
			mainCheckbox.checked = false;
		}
		else {
			// Are all of them checked?
			boolAllChecked = true;
			for (i=0; i < contactsCheckboxes.length; i++) {
				if (contactsCheckboxes[i].checked == false) {
					boolAllChecked = false;
					break;
				}
			}
			mainCheckbox.checked = boolAllChecked;
		}
	}
}

/*
	Checks if any contact(s) is|are selected
*/
function checkContactSelected()
{
	var contactsCheckboxes = document.frm_select_contact.contact;
	var boolChecked = false;
	// Are any checkboxes selectes and enabled?
	if (contactsCheckboxes != null) {
		if (contactsCheckboxes.length) {  // If length is defined that means we have more than 2 rows
			for (i=0; i < contactsCheckboxes.length; i++) {
				if ( (contactsCheckboxes[i].checked ) && (!contactsCheckboxes[i].disabled) ) {
					boolChecked = true;
					break;
				}
			}
		}
		else {  // if length is undefined, we have only one row so set/unset it.
			if ( (contactsCheckboxes.checked) && (!contactsCheckboxes.disabled) ) {
				boolChecked = true;
			}
		}
	}
	return boolChecked;
}

/*
	Add the contact(s) selected to the order
*/
function addContact(boolSubmitDDValues, trigger)
{
	var frm = document.frm_select_contact;
	frm.state.value = statePROCESS;
	if (boolSubmitDDValues) {
		if (trigger=='EmployerType') {
			employerType = frm.EMPLOYER_TYPE_DD;
			frm.EMPLOYER_TYPE.value = employerType.options[employerType.selectedIndex].value;
			frm.EMPLOYER_ID.value = 0;
		}
		else {
			employer = frm.EMPLOYER_DD;
			var dropdownValue = employer.options[employer.selectedIndex].value;
			arrEmployerIDType = dropdownValue.split(',');
			frm.EMPLOYER_ID.value   = arrEmployerIDType[0];
			frm.EMPLOYER_TYPE.value = arrEmployerIDType[1];
		}
	}
	frm.submit();
}

function refreshOpenerAndClose()
{
	window.opener.resubmitForm();
	window.close();
}

function resubmitForm()
{
	frm = document.frm_select_contact;
	frm.submit();
}
--></script>
<body class="body_popup">
<?php echo generate_form_header() ?>
<table class="popup" >
	<tr><td colspan="2"><h4>Select Contact</h4></td></tr>
	<tr><td colspan="2">
		<table>
			<tr class="contacts_header" >
				<td width="20%">Employer Type</td>
				<td>Employer</td>
			</tr>
			<tr>
				<td>
				<?php
				echo '<select id="EMPLOYER_TYPE_DD" name="EMPLOYER_TYPE_DD" onChange="ddChange(\'EmployerType\');">';
				echo populate_employer_types_dropdown($employer_type, ($referrer_type == entity_USER));
				echo '</select>';
				?>
				</td>
				<td>
				<?php
				echo '<select id="EMPLOYER_DD" name="EMPLOYER_DD" onChange="ddChange(\'Employer\');">';
				echo populate_employers_dropdown($employer_id, $employer_type);
				echo '</select>';
				?>
				</td>
			</tr>
		</table>
	</td></tr>
	<tr><td colspan="2">
	<div class="contact_select_div">
	<?= show_contacts(); ?>
	</div>
	</td></tr>
	<tr><td colspan="2" align="right">
	<?php
	echo generate_button("button","Add","addContact(false,'');");
	echo "&nbsp;";
	echo generate_button("button","Close","refreshOpenerAndClose();");
	?>
	</td></tr>
	</table>
</form>
</body>
</html>
