
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
  	Contact Edit popup page

	This page will handle editing/creating new contacts for a party.
	It has three states: NEW, EDIT, and PROCESS. The latter is a transient state

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
	global $state, $employer_id, $party_id, $party_type;

	// Get key parameters
	$state  	 = isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : "");
	$employer_id = isset($_GET['employer_id']) ? $_GET['employer_id'] : (isset($_POST['EMPLOYER_ID']) ? $_POST['EMPLOYER_ID'] : 0);
	$party_id	 = isset($_GET['party_id']) ? $_GET['party_id'] : (isset($_POST['PARTY_ID']) ? $_POST['PARTY_ID'] : -1);
	$party_type	 = isset($_GET['type']) ? $_GET['type'] : (isset($_POST['TYPE']) ? $_POST['TYPE'] : -1);
}

function process_state()
{
	global $state;

	// Check state
	if (empty($state)) { /* if $state is empty go to EDIT state*/
		edit_form();
	} elseif ( $state == state_NEW ) {
		new_form();
	} elseif ( $state == state_EDIT) {
		edit_form();
	} elseif ( $state == state_PROCESS) {
		process_form();
	} else { 		// might be redundant but just in case...
		edit_form();
	}
}

/****************************************
	Root functions
*****************************************/
function new_form()
{
	init_form();
}

function edit_form()
{
	init_form();
	get_record();
}

/*

	Function: process_form()
	Desc:
		This function is called upon submission of the form.
		Validates values posted.
		Checks whether this is a NEW state or an EDIT state and acts accordingly.
 	Params:
    	None
   	Returns:
		Nothing
*/
function process_form()
{
	global 	$glb_arr_values, $state, $party_id;

	// Get values from submitted form
	post_values();

	if (validate_form()) {
		if (-1 == $glb_arr_values['PARTY_ID']) { // NEW
			$sql = generate_insert_contact_sql() ;
			$result = mysql_query($sql) or die(mysql_error());
			if (!$result) return mysql_error();
			$glb_arr_values['PARTY_ID'] = mysql_insert_id();
			$party_id = $glb_arr_values['PARTY_ID'];
			$state = state_CLOSEPOPUP;
		} else {  								// EDIT
			$sql = generate_update_contact_sql();
			$result = mysql_query($sql) or die(mysql_error());
			if (!$result) return mysql_error();
			$state = state_CLOSEPOPUP;
		}
	} else {  // validation unsuccessful
		if (-1 != $glb_arr_values['PARTY_ID']) {	// Go back to EDIT state
			$state = state_EDIT;
		} else {									// Go back to NEW state
			$state = state_NEW;
		}
	}
}
/*
	Intitialize page
*/
function init_form()
{
	global $glb_arr_values, $state, $party_id, $party_type;

	if ( $state != state_NEW ) {  //VIEW
		$glb_arr_values["PARTY_ID"] =  $party_id;
		$glb_arr_values['TYPE']     =  $party_type;
	}
	else {   //NEW
		new_values();
	}
}
/*
	Populate global values array with defaults when the page is in 'NEW' state
*/
function new_values()
{
	global $glb_arr_values, $party_type, $employer_id;

	$glb_arr_values["PARTY_ID"] 	= -1;
	$glb_arr_values["CODE"]			= '';
	$glb_arr_values["NAME"] 		= '';
	$glb_arr_values["TYPE"] 		= $party_type;
	$glb_arr_values["ADDRESS1"] 	= '';
	$glb_arr_values["ADDRESS2"] 	= '';
	$glb_arr_values["CITY"] 		= '';
	$glb_arr_values["PROVINCE"] 	= '';
	$glb_arr_values["POSTAL_CODE"] 	= '';
	$glb_arr_values["COUNTRY_ID"] 	= 0;
	$glb_arr_values["TEL"] 			= '';
	$glb_arr_values["MOBILE"] 		= '';
	$glb_arr_values["FAX"] 			= '';
	$glb_arr_values["EMAIL"] 		= '';
	$glb_arr_values["WEBSITE"] 		= '';
	$glb_arr_values["AGENT_ID"] = 0;
	$glb_arr_values["EMPLOYER_ID"] 	= $employer_id;
	$glb_arr_values["POSITION"] 	= '';
	$glb_arr_values["NOTES"] 		= '';
}

/*
	Get values from submitted form and populate global values array
*/
function post_values()
{
	global $glb_arr_values, $party_id, $party_type, $employer_id;

	$glb_arr_values["PARTY_ID"] 	= $party_id ;
	$glb_arr_values["NAME"] 		= isset($_POST["NAME"]) ? $_POST["NAME"] : "";
	$glb_arr_values["TYPE"] 		= $party_type ;
	$glb_arr_values["ADDRESS1"] 	= isset($_POST["ADDRESS1"]) ? $_POST["ADDRESS1"] : "";
	$glb_arr_values["ADDRESS2"] 	= isset($_POST["ADDRESS2"]) ? $_POST["ADDRESS2"] : "";
	$glb_arr_values["CITY"] 		= isset($_POST["CITY"]) ? $_POST["CITY"] : "";
	$glb_arr_values["PROVINCE"] 	= isset($_POST["PROVINCE"]) ? $_POST["PROVINCE"] : "";
	$glb_arr_values["POSTAL_CODE"] 	= isset($_POST["POSTAL_CODE"]) ? $_POST["POSTAL_CODE"] : "";
	$glb_arr_values["COUNTRY_ID"] 	= isset($_POST["COUNTRY_ID"]) ? $_POST["COUNTRY_ID"] : "";
	$glb_arr_values["TEL"] 			= isset($_POST["TEL"]) ? $_POST["TEL"] : "";
	$glb_arr_values["MOBILE"] 		= isset($_POST["MOBILE"]) ? $_POST["MOBILE"] : "";
	$glb_arr_values["FAX"] 			= isset($_POST["FAX"]) ? $_POST["FAX"] : "";
	$glb_arr_values["EMAIL"] 		= isset($_POST["EMAIL"]) ? $_POST["EMAIL"] : "";
	$glb_arr_values["WEBSITE"] 		= isset($_POST["WEBSITE"]) ? $_POST["WEBSITE"] : "";
	$glb_arr_values["AGENT_ID"] = isset($_POST["AGENT_ID"]) ? $_POST["AGENT_ID"] : "" ;
	$glb_arr_values["EMPLOYER_ID"] 	= $employer_id; ;
	$glb_arr_values["POSITION"] 	= isset($_POST["POSITION"]) ? $_POST["POSITION"] : "";
	$glb_arr_values["NOTES"] 		= isset($_POST["NOTES"]) ? $_POST["NOTES"] : "";
}


/*
	Function: get_record()
	Desc:
		If party_id < 1 then this is a new record. Populate with defaults.
		If party_id > 1 get record from database and populate values global array
 	Params:
    	None
   	Returns:
		Nothing
*/
function get_record()
{
	global $glb_arr_values, $party_id;

	if (1 > $glb_arr_values['PARTY_ID']) {
		new_values();
		return;
	}
	$sql = generate_select_contact_sql();
	$result = mysql_query($sql) or die(mysql_error());
	if (!$result) return mysql_error();
	$row = mysql_fetch_array($result);

	// Populate values array
	$glb_arr_values["NAME"] 		= mysql_decode($row["NAME"]);
	$glb_arr_values["TYPE"] 		= capitalise_first_letter( mysql_decode($row["TYPE"]) );
	$glb_arr_values["ADDRESS1"] 	= mysql_decode($row["ADDRESS1"]);
	$glb_arr_values["ADDRESS2"] 	= mysql_decode($row["ADDRESS2"]);
	$glb_arr_values["CITY"] 		= mysql_decode($row["CITY"]);
	$glb_arr_values["PROVINCE"] 	= mysql_decode($row["PROVINCE"]);
	$glb_arr_values["POSTAL_CODE"] 	= mysql_decode($row["POSTAL_CODE"]);
	$glb_arr_values["COUNTRY_ID"] 	= mysql_decode($row["COUNTRY_ID"]);
	$glb_arr_values["TEL"] 			= mysql_decode($row["TEL"]);
	$glb_arr_values["MOBILE"] 		= mysql_decode($row["MOBILE"]);
	$glb_arr_values["FAX"] 			= mysql_decode($row["FAX"]);
	$glb_arr_values["EMAIL"] 		= mysql_decode($row["EMAIL"]);
	$glb_arr_values["WEBSITE"] 		= mysql_decode($row["WEBSITE"]);
	$glb_arr_values["EMPLOYER_ID"] 	= mysql_decode($row["EMPLOYER_ID"]);
	$glb_arr_values["POSITION"] 	= mysql_decode($row["POSITION"]);
	$glb_arr_values["NOTES"] 		= mysql_decode($row["NOTES"]);
	$glb_arr_values["DATE_CREATED"] = mysql_decode($row["DATE_CREATED"]);

	mysql_free_result($result);
}

/*
	Validate form values
*/
function validate_form()
{
	global $glb_arr_values, $errors;

	// Verify that at least a name exists for the party
	if (empty($glb_arr_values['NAME'])) $errors['NAME'] = "Please enter a name for this contact";

	// Email Check
	if ( (! empty ($glb_arr_values['EMAIL'])) &&  ( ! validate_email ($glb_arr_values['EMAIL'])) ) {
	 	$errors['EMAIL'] = "Please enter a valid email address" ;
	}

	// URL Check

	if (safe_count($errors) > 0)	{
		$errors['error'] = "There was a problem with the information entered. Please review the form and make necessary corrections.";
	}
	return safe_count($errors) == 0;
}

/****************************************
	SQL Generation functions
*****************************************/
/*
	Generates select SQL for contact
*/
function generate_select_contact_sql()
{
	global $glb_arr_values;

	$select_sql = "SELECT * FROM party WHERE PARTY_ID=".$glb_arr_values['PARTY_ID'];
	return $select_sql;
}

/*
	Generates insert SQL for contact
*/
function generate_insert_contact_sql()
{
	global $glb_arr_values;

	$insert_sql = "INSERT INTO party
					(STATE, NAME, TYPE, ADDRESS1, ADDRESS2, CITY, PROVINCE,
                     POSTAL_CODE, COUNTRY_ID, TEL, MOBILE, FAX, EMAIL, WEBSITE, EMPLOYER_ID,
                     POSITION, NOTES, DATE_CREATED)
					VALUES(	'ACTIVE',
					'".mysql_encode($glb_arr_values['NAME'])."',
					".$glb_arr_values['TYPE'].",
					'".mysql_encode($glb_arr_values['ADDRESS1'])."',
					'".mysql_encode($glb_arr_values['ADDRESS2'])."',
					'".mysql_encode($glb_arr_values['CITY'])."',
					'".mysql_encode($glb_arr_values['PROVINCE'])."',
					'".mysql_encode($glb_arr_values['POSTAL_CODE'])."',
					".$glb_arr_values['COUNTRY_ID'].",
					'".mysql_encode($glb_arr_values['TEL'])."',
					'".mysql_encode($glb_arr_values['MOBILE'])."',
					'".mysql_encode($glb_arr_values['FAX'])."',
					'".mysql_encode($glb_arr_values['EMAIL'])."',
					'".mysql_encode($glb_arr_values['WEBSITE'])."',
					".$glb_arr_values['EMPLOYER_ID'].",
					'".mysql_encode($glb_arr_values['POSITION'])."',
					'".mysql_encode($glb_arr_values['NOTES'])."',
					   NOW())";
	return $insert_sql;
}

/*
	Generates update SQL for party
*/

function generate_update_contact_sql()
{
	global $glb_arr_values;

	$update_sql =  "UPDATE party SET

					NAME = '" .mysql_encode( $glb_arr_values["NAME"])."',
					TYPE = '" .mysql_encode( $glb_arr_values["TYPE"])."',
					ADDRESS1 = '" .mysql_encode( $glb_arr_values["ADDRESS1"])."',
					ADDRESS2 = '" .mysql_encode( $glb_arr_values["ADDRESS2"])."',
					CITY = '" .mysql_encode( $glb_arr_values["CITY"])."',
					PROVINCE = '" .mysql_encode( $glb_arr_values["PROVINCE"])."',
					POSTAL_CODE = '" .mysql_encode( $glb_arr_values["POSTAL_CODE"])."',
					COUNTRY_ID =". $glb_arr_values["COUNTRY_ID"]. ",
					TEL = '" .mysql_encode( $glb_arr_values["TEL"])."',
					MOBILE = '" .mysql_encode( $glb_arr_values["MOBILE"])."',
					FAX = '" .mysql_encode( $glb_arr_values["FAX"])."',
					EMAIL = '" .mysql_encode( $glb_arr_values["EMAIL"])."',
					WEBSITE = '" .mysql_encode( $glb_arr_values["WEBSITE"])."',
					EMPLOYER_ID =". $glb_arr_values["EMPLOYER_ID"].",
					POSITION = '" .mysql_encode( $glb_arr_values["POSITION"])."',
					NOTES = '" .mysql_encode( $glb_arr_values["NOTES"])."'

					WHERE PARTY_ID=".$glb_arr_values["PARTY_ID"];

	return $update_sql;
}
/*
	Generates standard form header for party
*/
function generate_form_header()
{
	global $glb_arr_values, $state, $party_type, $sub_entity_op, $sub_entity_name, $sub_entity_id;

	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_contact\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"PARTY_ID\" name=\"PARTY_ID\" value=\"" . $glb_arr_values["PARTY_ID"] . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"TYPE\" name=\"TYPE\" value=\"" . $party_type . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"EMPLOYER_ID\" name=\"EMPLOYER_ID\" value=\"" . $glb_arr_values["EMPLOYER_ID"] . "\"/>\r\n";
	return $str_frm_header;
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Contrack - Contact</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="contrack.css" rel="stylesheet" type="text/css" media="all" />
</head>
<script src="utilities.js" type="text/javascript"></script>
<script type="text/javascript"><!--

var state = <?= ($state=="") ? 0 : $state ?>;
willIClose(state, window);

function entitySave()
{
	var frm = document.frm_contact;

	if (frm.NAME.value.length < 1 ) {
		alert ("You must enter a name for this Contact");
		return;
	}
	if (!validateEmail(frm.EMAIL.value)) {
		alert ("Please Enter a Valid Email Address");
		return;
	}
	frm.state.value = statePROCESS;
	frm.submit();
}
--></script>

<body class="body_popup">
<?php echo generate_form_header() ?>
<table class="popup" >
	<tr><td colspan="2"><h4>
	<?php if ($state == state_NEW){
		echo "Add Contact";
	} else {
		echo "Edit Contact";
	}
	?>
	</h4></td></tr>
	<tr><td width="25%">Name:<?= generate_required_superscript();?></td>
	<td>
	<?php
		echo generate_text_input ("formtext_longwidth", "NAME", $glb_arr_values["NAME"]);
	?>
	<?php if (isset($errors['NAME'])) echo "<em class=\"error\">" . $errors['NAME'] . "</em>"; ?>
	</td></tr>

	<tr><td>Position:</td>
	<td>
	<?php
		echo generate_text_input ("formtext_stdwidth", "POSITION", $glb_arr_values["POSITION"]);
	?>
	</td></tr>

	<tr><td>Employer: </td>
	<td><?= get_party_name($glb_arr_values["EMPLOYER_ID"]);?></td></tr>

	<tr><td>Street Address 1:</td>
	<td>
	<?php
			echo generate_text_input ("formtext_longwidth", "ADDRESS1", $glb_arr_values["ADDRESS1"]);
	?>
	</td></tr>

	<tr><td>Street Address 2:</td>
	<td>
	<?php
			echo generate_text_input ("formtext_longwidth", "ADDRESS2", $glb_arr_values["ADDRESS2"]);
	?>
	</td></tr>

	<tr><td>City:</td>
	<td>
	<?php
			echo generate_text_input ("formtext_stdwidth", "CITY", $glb_arr_values["CITY"]);
	?>
	</td></tr>

	<tr><td>State/Province:</td>
	<td>
	<?php
			echo generate_text_input ("formtext_stdwidth", "PROVINCE", $glb_arr_values["PROVINCE"]);
	?>
	</td></tr>

	<tr><td>Postal Code:</td>
	<td>
	<?php
			echo generate_text_input ("formtext_stdwidth", "POSTAL_CODE", $glb_arr_values["POSTAL_CODE"]);
	?>
	</td></tr>

	<tr><td>Country:</td>
	<td>
	<?php
			echo generate_dd_country_input ($glb_arr_values["COUNTRY_ID"]);
	?>
	</td></tr>

	<tr><td>Office Phone:</td>
	<td>
	<?php
			echo generate_text_input ("formtext_stdwidth", "TEL", $glb_arr_values["TEL"]);
	?>
	</td></tr>

	<tr><td>Mobile:</td>
	<td>
	<?php
			echo generate_text_input ("formtext_stdwidth", "MOBILE", $glb_arr_values["MOBILE"]);
	?>
	</td></tr>

	<tr><td>Fax:</td>
	<td>
	<?php
			echo generate_text_input ("formtext_stdwidth", "FAX", $glb_arr_values["FAX"]);
	?>
	</td></tr>

	<tr><td>Email:</td>
	<td>
	<?php
			echo generate_text_input ("formtext_longwidth", "EMAIL", $glb_arr_values["EMAIL"]);

	?>
	<?php if (isset($errors['EMAIL'])) echo "<em class=\"error\">" . $errors['EMAIL'] . "</em>"; ?>
	</td></tr>

	<tr><td>Web:</td>
	<td>
	<?php
			echo generate_text_input ("formtext_stdwidth", "WEBSITE", $glb_arr_values["WEBSITE"]);
	?>
	</td></tr>

	<tr><td valign="top">Notes:</td>
	<td>
	<?php echo generate_textarea_input ("NOTES", 3, 50, $glb_arr_values["NOTES"]);	?>
	</td></tr>

	<tr><td colspan="2" align="right">
		  <?php
		  echo generate_button("button","Save","entitySave();");
		  echo "&nbsp;";
		  echo generate_button("button","Cancel","javascript:void window.close();");
		  ?>
	</td></tr>
	</table>
</form>
</body>
</html>
