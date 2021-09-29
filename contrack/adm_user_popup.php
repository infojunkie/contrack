
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
  	User accounts popup. Create/Edit users

	This page will handle creating/editing user accounts.
	The user will have to associate a party with the created user account
	This page can have three states: state_NEW, state_EDIT, state_PROCESS
******************************************************************************************/
require_once("db_utils.php");
require_once("general_utils.php");
require_once( "patUser-2.2.3/prepend.php" );

init_page ();
process_state();

/****************************************************
	Page Initialisation
	- Authenticate
	- Get all parameters passed to the page
	- Check for any errors passed to the page
	- Process state
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


function get_page_params()
{
	global $state, $uid;

	// Get key parameters
	$state  = isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : state_EDIT);
	$uid 	= isset($_GET['uid']) ? $_GET['uid'] : (isset($_POST['UID']) ? $_POST['UID'] : -1);
}

function process_state()
{
	global $state;

	// Check state
	if (empty($state)) { /* if $state is empty go to EDIT state*/
		edit_form();
	} elseif ($state == state_NEW) {
		new_form();
	} elseif ($state == state_EDIT) {
		edit_form();
	} elseif ($state == state_PROCESS) {
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
		Update database
 	Params:
    	None
   	Returns:
		Nothing
*/
function process_form()
{
	global 	$state, $glb_arr_values;

	// Get values from submitted form
	post_values();

	if (validate_form()) {
		if (-1 == $glb_arr_values['UID']) {	 // NEW
			$sql = generate_insert_user_sql();
			$result = mysql_query($sql) or die(mysql_error());
			if (!$result) return mysql_error();
			$glb_arr_values['UID'] = mysql_insert_id();
			$sql = generate_insert_user_rel_group_sql();
			$result = mysql_query($sql) or die(mysql_error());
			if (!$result) return mysql_error();
			$state = state_CLOSEPOPUP;
		}
		else {								// EDIT
			$sql = generate_update_user_sql();
			$result = mysql_query($sql) or die(mysql_error());
			if (!$result) return mysql_error();
			$sql = generate_update_user_rel_group_sql();
			$result = mysql_query($sql) or die(mysql_error());
			if (!$result) return mysql_error();
			$state = state_CLOSEPOPUP;
		}
	}
	else {  // validation unsuccessful
		if ( -1 != $glb_arr_values['UID'] ) {	// Go back to EDIT state
			$state = state_EDIT;
		}
		else {									// Go back to NEW state
			$state = state_NEW;
		}
	}
}
/*
	Intitialize page
*/
function init_form()
{
	global $glb_arr_values, $uid, $state;

	if ( $state != state_NEW ) {
		$glb_arr_values['UID'] =  $uid;
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
	global $glb_arr_values;

	$glb_arr_values['UID'] 			  = -1;
	$glb_arr_values['USERNAME'] 	  = '';
	$glb_arr_values['PASSWORD'] 	  = '';
	$glb_arr_values['PASSWORD_CHECK'] = '';
	$glb_arr_values['PARTY_ID'] 	  = -1;
	$glb_arr_values['PARTY_NAME'] 	  = 'N/A';
	$glb_arr_values['GROUP_ID'] 	  = -1;

}

/*
	Get values from submitted form and populate global values array
*/
function post_values()
{
	global $glb_arr_values, $uid;

	$glb_arr_values['UID'] 	  		  = isset($_POST['UID']) ? $_POST['UID'] : -1;
	$glb_arr_values['USERNAME'] 	  = isset($_POST['USERNAME']) ? $_POST['USERNAME'] : '';
	$glb_arr_values['PASSWORD'] 	  = isset($_POST['PASSWORD']) ? $_POST['PASSWORD'] : '';
	$glb_arr_values['PASSWORD_CHECK'] = isset($_POST['PASSWORD_CHECK']) ? $_POST['PASSWORD_CHECK'] : '';
	$glb_arr_values['PARTY_ID'] 	  = isset($_POST['PARTY_ID']) ? $_POST['PARTY_ID'] : 0;
	$glb_arr_values['GROUP_ID'] 	  = isset($_POST['GROUP_ID']) ? $_POST['GROUP_ID'] : 0;
}

/*
	Get record and populate global values array

*/
function get_record()
{
	global $glb_arr_values;

	$sql = generate_select_user_sql();
	$result = mysql_query($sql) or die(mysql_error());
	if (!$result) return mysql_error();
	$row = mysql_fetch_array($result);

	// Populate values array
	$glb_arr_values['USERNAME'] 	  = mysql_decode($row['USERNAME']);
	$glb_arr_values['PASSWORD']       = mysql_decode($row['PASSWORD']);
	$glb_arr_values['PASSWORD_CHECK'] = mysql_decode($row['PASSWORD']);
	$glb_arr_values['PARTY_ID']       = mysql_decode($row['PARTY_ID']);
	$glb_arr_values['PARTY_NAME']     = mysql_decode($row['PARTY_NAME']);
	$glb_arr_values['GROUP_ID']   	  = mysql_decode($row['GROUP_ID']);
	mysql_free_result($result);
}

/*
	Validate form values
*/
function validate_form()
{
	global $glb_arr_values, $errors;

	// Check that password is more than six characters long and that both entries match
	if (strlen($glb_arr_values['PASSWORD']) < 4 ) {
		$errors['PASSWORD'] = "Password must be at least four characters long";
	}
	// and that both entries match
	if (strcmp($glb_arr_values['PASSWORD'], $glb_arr_values['PASSWORD_CHECK']) != 0 ) {
		$errors['PASSWORD_CHECK'] = "Passwords did not match. Please try again.";
	}

	if (count($errors) > 0)	{
		$errors['error'] = "Invalid values";
	}
	return count($errors) == 0;
}

/****************************************
	SQL Generation functions
*****************************************/
/*
	Generates select SQL for user
*/
function generate_select_user_sql()
{
	global $glb_arr_values;

	$select_sql = " SELECT user.UID, user.USERNAME, user.PASSWD AS PASSWORD, user.PARTY_ID,
						party.NAME AS PARTY_NAME, groupe.gid AS GROUP_ID
					FROM user LEFT JOIN  party on user.PARTY_ID = party.PARTY_ID
						INNER JOIN user_rel_group on user.UID=user_rel_group.UID
						INNER JOIN  groupe ON user_rel_group.gid = groupe.gid
					WHERE user.UID =" . $glb_arr_values['UID'];

	return $select_sql;
}

/*
	Generates insert SQL for user
*/
function generate_insert_user_sql()
{
	global $glb_arr_values;

	$insert_sql = "INSERT INTO user
					(party_id, username, passwd )
					VALUES(
					".$glb_arr_values['PARTY_ID'].",
					'".mysql_encode($glb_arr_values['USERNAME'])."',
					'".mysql_encode($glb_arr_values['PASSWORD'])."' )";
	return $insert_sql;
}

/*
	Generates update SQL for user
*/
function generate_update_user_sql()
{
	global $glb_arr_values;

	$update_sql =  "UPDATE user SET
						party_id = " .$glb_arr_values['PARTY_ID'].",
						username = '" .mysql_encode( $glb_arr_values['USERNAME'])."',
						passwd = '" .mysql_encode( $glb_arr_values['PASSWORD'])."'
					WHERE uid=".$glb_arr_values['UID'];

	return $update_sql;
}

/*
	Generates insert SQL for user_rel_group
*/
function generate_insert_user_rel_group_sql()
{
	global $glb_arr_values;

	$insert_sql = "INSERT INTO user_rel_group
					(uid, gid )
					VALUES(
					".$glb_arr_values['UID'].",
					".$glb_arr_values['GROUP_ID']." )";
	return $insert_sql;
}

/*
	Generates update SQL for user_rel_group
*/
function generate_update_user_rel_group_sql()
{
	global $glb_arr_values;

	$update_sql =  "UPDATE user_rel_group SET
						  gid = " .$glb_arr_values['GROUP_ID']."
					WHERE uid = " .$glb_arr_values['UID'];

	return $update_sql;
}

/*
	Generates standard form header for Password Change screen
*/
function generate_form_header()
{
	global $glb_arr_values, $state;

	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_edit_user\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"UID\" name=\"UID\" value=\"" .  $glb_arr_values['UID'] . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"PARTY_ID\" name=\"PARTY_ID\" value=\"" . $glb_arr_values['PARTY_ID'] . "\"/>\r\n";
	return $str_frm_header;
}

function generate_contact_select_popup_link()
{
	global $glb_arr_values;

	$markup = "javascript:void open_popup('adm_contact_select_popup.php?referrer_id=".$glb_arr_values['PARTY_ID'].
				"&referrer_type=".entity_USER."&state=".state_VIEW.
				"','admContactSelect','menubar=no,toolbar=no,scrollbars=no',310,640);" ;
	return $markup;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Contrack - Add/Edit User</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="contrack.css" rel="stylesheet" type="text/css" media="all" />
</head>
<script src="utilities.js" type="text/javascript"></script>
<script type="text/javascript"><!--

var state = <?= ($state=="") ? 0 : $state ?>;
willIClose(state, window);

function entitySave()
{
	var frm = document.forms["frm_edit_user"];
	if ((frm.USERNAME.value.length < 4 ) || (frm.USERNAME.value.length > 12 )) {
		alert ("Username must be 4-12 characters long");
		frm.USERNAME.focus();
		return;
	}
	if ( ! validateUsername (frm.USERNAME.value) ) {
		alert ("Username contains invalid characters");
		frm.USERNAME.focus();
		return;
	}
	if (frm.PASSWORD.value.length < 4 ) {
		alert ("Password must be at least four characters long");
		frm.PASSWORD.focus();
		return;
	}
	if (frm.PASSWORD.value != frm.PASSWORD_CHECK.value) {
		alert ("Passwords did not match. Please try again");
		frm.PASSWORD.focus();
		return;
	}
	if (frm.PARTY_ID.value <= 0) {
		alert ("You must associate a Contact with this user account");
		return;
	}
	if (frm.GROUP_ID.value <= 0) {
		alert ("You must select a Security Group");
		return;
	}

	// All tests have passed
	frm.state.value = statePROCESS;
	frm.submit();

}

function setAssociatedPartyId(partyID, partyName)
{
	var frm = document.forms["frm_edit_user"];
	frm.PARTY_ID.value = partyID;
	document.getElementById("AssociatedPartyId").childNodes[0].nodeValue = partyName;
}

function resubmitForm()
{
	var frm = document.forms["frm_edit_user"];
	frm.state.value	= stateEDIT;
	frm.submit();

}
--></script>
<body class="body_popup">
<?php echo generate_form_header() ?>
<table class="popup">
	<tr><td colspan="2"><h4>Add/Edit User Account</h4></td></tr>
	<tr><td colspan="2">
	<?php
		if (isset($errors['PASSWORD'])){
			echo '<em class="error">' . $errors['PASSWORD'] . '</em>';
		}
		else if (isset($errors['PASSWORD_CHECK'])){
			echo '<em class="error">' . $errors['PASSWORD_CHECK'] . '</em>';
		}
	?>
	</td></tr>
	<tr><td>User Name:<?= generate_required_superscript();?></td>
	<td align="right">
	<?php
		echo generate_text_input ('formtext_stdwidth', 'USERNAME', $glb_arr_values['USERNAME']);
	?></td>
	</tr>
	<tr><td >Password:<?= generate_required_superscript();?></td>
	<td align="right">
	<?php
		echo generate_pwd_input ('formtext_stdwidth', 'PASSWORD', $glb_arr_values['PASSWORD']);
	?>
	</td></tr>
	<tr><td>Confirm Password:<?= generate_required_superscript();?></td>
	<td align="right">
	<?php
		echo generate_pwd_input ('formtext_stdwidth', 'PASSWORD_CHECK', $glb_arr_values['PASSWORD_CHECK']);
	?>
	</td></tr>
	<tr><td>Contact:<?= generate_required_superscript();?></td>
	<td align="right">
		<table>
			<tr>
				<td id="AssociatedPartyId" align="left"><?= $glb_arr_values['PARTY_NAME']?></td>
				<td align="right">
				<?php
					echo generate_button("button", "Browse...", generate_contact_select_popup_link());
				?>
				</td>

			</tr>
		</table>
	<tr>
	<tr><td>Security Group:<?= generate_required_superscript();?></td>
	<td align="right">
	<?php
		echo generate_dd_sec_group_input ($glb_arr_values['GROUP_ID']);
	?>
	</td></tr>
	<td colspan="2" align="right">
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