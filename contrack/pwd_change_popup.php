
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
  	Own Contact Edit popup page

	This page will handle primarily changing the password for the currently logged in user.
	The user can also click on Edit and change his own contact details
	This page can have two states: state_EDIT, state_PROCESS

******************************************************************************************/
require_once("db_utils.php");
require_once("general_utils.php");
require_once( "patUser-2.2.3/prepend.php" );
require_once("permissions.php");

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
	$state  = isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : "");
	$uid 	= isset($_GET['uid']) ? $_GET['uid'] : (isset($_POST['UID']) ? $_POST['UID'] : -1);
}

function process_state()
{
	global $state;

	// Check state
	if (empty($state)) { /* if $state is empty go to EDIT state*/
		edit_form();
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
	global 	$state;

	// Get values from submitted form
	post_values();

	if (validate_form()) {
		$sql = generate_update_pwd_sql();
		//echo "SQL Generated: " . $sql . "<br>";
		$result = mysql_query($sql) or die(mysql_error());
		if (!$result) return mysql_error();
		$state = state_CLOSEPOPUP;
		//$state = state_EDIT;   // set state to Edit to go back to edit mode after saving successfully
		//get_record();
	}
	else {  // validation unsuccessful
		$state = state_EDIT;
	}
}
/*
	Intitialize page
*/
function init_form()
{
	global $glb_arr_values, $uid;

	$glb_arr_values['UID'] = $uid  ;
}
/*
	Get values from submitted form and populate global values array
*/
function post_values()
{
	global $glb_arr_values, $uid;

	$glb_arr_values['UID'] 	    	  = $uid ;
	/*
	foreach ($_POST as $name=>$value) {
		$glb_arr_values[$name] = $value;
	}
	*/
	$glb_arr_values['USERNAME'] 	  = isset($_POST['USERNAME']) ? $_POST['USERNAME'] : "";
	$glb_arr_values['PASSWORD'] 	  = isset($_POST['PASSWORD']) ? $_POST['PASSWORD'] : "";
	$glb_arr_values['PASSWORD_CHECK'] = isset($_POST['PASSWORD_CHECK']) ? $_POST['PASSWORD_CHECK'] : "";
	$glb_arr_values['PARTY_ID'] 	  = isset($_POST['PARTY_ID']) ? $_POST['PARTY_ID'] : 0;
	$glb_arr_values['PARTY_NAME']     = isset($_POST['PARTY_NAME']) ? $_POST['PARTY_NAME'] : "";
	$glb_arr_values['PARTY_TYPE']     = isset($_POST['PARTY_TYPE']) ? $_POST['PARTY_TYPE'] : "";
	$glb_arr_values['EMPLOYER_ID']    = isset($_POST['EMPLOYER_ID']) ? $_POST['EMPLOYER_ID'] : 0;
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
	$glb_arr_values['PARTY_TYPE']     = mysql_decode($row['PARTY_TYPE']);
	$glb_arr_values['EMPLOYER_ID']    = mysql_decode($row['EMPLOYER_ID']);

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

	if (safe_count($errors) > 0)	{
		$errors['error'] = "Invalid password";
	}
	return safe_count($errors) == 0;
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
						   party.NAME AS PARTY_NAME, party.TYPE AS PARTY_TYPE, party.EMPLOYER_ID
					FROM user LEFT JOIN  party on user.PARTY_ID = party.PARTY_ID
                     		  LEFT JOIN user_rel_group on user.UID=user_rel_group.UID
					WHERE user.UID =" . $glb_arr_values['UID'];

	return $select_sql;
}

/*
	Generates update SQL for user password
*/
function generate_update_pwd_sql()
{
	global $glb_arr_values;

	$update_sql =  "UPDATE user SET
					PASSWD = '" .mysql_encode( $glb_arr_values['PASSWORD'])."'
					WHERE UID=".$glb_arr_values['UID'];

	return $update_sql;
}
/*
	Generates standard form header for Password Change screen
*/
function generate_form_header()
{
	global $glb_arr_values, $state;

	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_change_pwd\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"UID\" name=\"UID\" value=\"" .  $glb_arr_values['UID'] . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"USERNAME\" name=\"USERNAME\" value=\"" . $glb_arr_values['USERNAME'] . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"PARTY_ID\" name=\"PARTY_ID\" value=\"" . $glb_arr_values['PARTY_ID'] . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"PARTY_NAME\" name=\"PARTY_NAME\" value=\"" . $glb_arr_values['PARTY_NAME'] . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"PARTY_TYPE\" name=\"PARTY_TYPE\" value=\"" . $glb_arr_values['PARTY_TYPE'] . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"EMPLOYER_ID\" name=\"EMPLOYER_ID\" value=\"" . $glb_arr_values['EMPLOYER_ID'] . "\"/>\r\n";
	return $str_frm_header;
}

function generate_contact_edit_popup_link()
{
	global $glb_arr_values;

	$markup = "javascript: open_popup('contact_edit_popup.php?employer_id=".$glb_arr_values['EMPLOYER_ID'].
			   "&type=".$glb_arr_values['PARTY_TYPE']."&party_id=" .$glb_arr_values['PARTY_ID']."&state=".state_EDIT."',
				'contactEdit','menubar=no,toolbar=no,scrollbars=no',520,580);" ;
	return $markup;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Contrack - Change Password</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="contrack.css" rel="stylesheet" type="text/css" media="all" />
</head>
<script src="utilities.js" type="text/javascript"></script>
<script type="text/javascript"><!--

var state = <?= ($state=="") ? 0 : $state ?>;
if (state == stateCLOSEPOPUP ) {
	window.close();
}

function entitySave()
{
	var frm = document.frm_change_pwd;
	if (frm.PASSWORD.value.length < 4 ) {
		alert ("Password must be at least four characters long");
	}
	else if (frm.PASSWORD.value != frm.PASSWORD_CHECK.value) {
		alert ("Passwords did not match. Please try again");
		frm.PASSWORD.focus();
	}
	else {
		frm.state.value = statePROCESS;
		frm.submit();
	}
}

function resubmitForm()
{
	var frm = document.frm_change_pwd;
	frm.state.value	= stateEDIT;
	frm.submit();

}
--></script>
<body class="body_popup">
<?php echo generate_form_header() ?>
<table class="popup">
	<tr><td colspan="2"><h4>Change Password</h4></td></tr>
	<tr><td colspan="2">
	<?php
		if (isset($errors['PASSWORD'])){
			echo "<em class=\"error\">" . $errors['PASSWORD'] . "</em>";
		} else if (isset($errors['PASSWORD_CHECK'])){
			echo "<em class=\"error\">" . $errors['PASSWORD_CHECK'] . "</em>";
		}
	?>
	</td></tr>
	<tr><td>User Name:</td>
	<td><?= $glb_arr_values['USERNAME']?></td>
	</tr>
	<tr><td>Password:</td>
	<td>
	<?php
		echo generate_pwd_input ('formtext_stdwidth', 'PASSWORD', $glb_arr_values['PASSWORD']);
	?>
	</td></tr>
	<tr><td>Confirm Password:</td>
	<td>
	<?php
		echo generate_pwd_input ('formtext_stdwidth', 'PASSWORD_CHECK', $glb_arr_values['PASSWORD_CHECK']);
	?>
	</td></tr>
	<tr><td>Contact:</td><td>
		<table>
			<tr>
				<td><?= $glb_arr_values['PARTY_NAME']?></td>
				<td align="right">
				<?php
					echo generate_button("button", "Edit...", generate_contact_edit_popup_link());
				?>
				</td>

			</tr>
		</table>
	<tr>
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