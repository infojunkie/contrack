
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
  	Currency Edit popup page

	This page will handle editing/creating new currencies
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
	global $state, $currency_id;

	// Get key parameters
	$state  	= isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : state_EDIT);
	$currency_id = isset($_GET['currency_id']) ? $_GET['currency_id'] : (isset($_POST['currency_id']) ? $_POST['currency_id'] : -1);
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
	global 	$glb_arr_values, $state, $currency_id;

	// Get values from submitted form
	post_values();
	if (validate_form()) {
		if (-1 == $glb_arr_values['CURRENCY_ID']) { // NEW
			$sql = generate_insert_currency_sql() ;
			$result = mysql_query($sql) or die(mysql_error());
			if (!$result) return mysql_error();
			$glb_arr_values['CURRENCY_ID'] = mysql_insert_id();
			$currency_id = $glb_arr_values['CURRENCY_ID'];
			$state = state_CLOSEPOPUP;
		} else {  								// EDIT
			$sql = generate_update_currency_sql();
			$result = mysql_query($sql) or die(mysql_error());
			if (!$result) return mysql_error();
			$state = state_CLOSEPOPUP;
		}
	} else {  // validation unsuccessful
		if (-1 != $glb_arr_values['CURRENCY_ID']) {	// Go back to EDIT state
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
	global $state, $glb_arr_values, $currency_id;

	if ( $state != state_NEW ) {  //VIEW
		$glb_arr_values['CURRENCY_ID'] =  $currency_id;
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
	global $glb_arr_values, $currency_id;

	$glb_arr_values['CURRENCY_ID'] 	= $currency_id ;
	$glb_arr_values['NAME'] 		= '';
	$glb_arr_values['CODE']  	    = '';
}

/*
	Get values from submitted form and populate global values array
*/
function post_values()
{
	global $glb_arr_values, $currency_id;

	$glb_arr_values['CURRENCY_ID']  = $currency_id;
	$glb_arr_values['NAME']  		= isset($_POST['NAME']) ? $_POST['NAME'] : '';
	$glb_arr_values['CODE']  	    = isset($_POST['CODE']) ? $_POST['CODE'] : '';
}


/*
	Function: get_record()
	Desc:
		If currency_id < 1 then this is a new record. Populate with defaults.
		If currency_id > 1 get record from database and populate values global array
 	Params:
    	None
   	Returns:
		Nothing
*/
function get_record()
{
	global $glb_arr_values;

	if (1 > $glb_arr_values['CURRENCY_ID']) {
		new_values();
		return;
	}
	$sql = generate_select_currency_sql();
	$result = mysql_query($sql) or die(mysql_error());
	if (!$result) return mysql_error();
	$row = mysql_fetch_array($result);

	// Populate values array
	$glb_arr_values['NAME'] = mysql_decode($row['NAME']);
	$glb_arr_values['CODE'] = mysql_decode($row['CODE']);

	mysql_free_result($result);
}

/*
	Validate form values
*/
function validate_form()
{
	global $glb_arr_values, $errors;

	return safe_count($errors) == 0;
}

/****************************************
	SQL Generation functions
*****************************************/
/*
	Generates select SQL for currency
*/
function generate_select_currency_sql()
{
	global $glb_arr_values;

	$select_sql = " SELECT CURRENCY_ID, CODE, NAME FROM currency
					WHERE
					currency.CURRENCY_ID= ".$glb_arr_values['CURRENCY_ID'];
	return $select_sql;
}

/*
	Generates insert SQL for currency
*/
function generate_insert_currency_sql()
{
	global $glb_arr_values;

	$insert_sql = "INSERT INTO currency
					(CODE, NAME)
					VALUES(
					'".mysql_encode($glb_arr_values['CODE'])."',
					'".mysql_encode($glb_arr_values['NAME'])."')";
	return $insert_sql;
}

/*
	Generates update SQL for currency
*/

function generate_update_currency_sql()
{
	global $glb_arr_values;

	$update_sql =  "UPDATE currency SET
					CODE = '" .mysql_encode( $glb_arr_values['CODE'])."',
					NAME = '".mysql_encode( $glb_arr_values['NAME'])."'
					WHERE CURRENCY_ID = ".$glb_arr_values['CURRENCY_ID'];

	return $update_sql;
}
/*
	Generates standard form header for party
*/
function generate_form_header()
{
	global $glb_arr_values, $state, $currency_id;

	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_currency\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"currency_id\" name=\"currency_id\" value=\"" . $currency_id . "\"/>\r\n";
	return $str_frm_header;
}
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Contrack - Add/Edit Currency</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="contrack.css" rel="stylesheet" type="text/css" media="all" />
</head>
<script src="utilities.js" type="text/javascript"></script>
<script type="text/javascript"><!--
var state = <?= ($state=="") ? 0 : $state ?>;
willIClose(state, window);
/*
	Rewrite validation in this function
*/
function addCurrency()
{
	var frm = document.frm_currency;

	if (frm.NAME.value.length == 0) {
		alert ("Please enter a name for this currency");
		return;
	}
	if (frm.CODE.value.length == 0) {
		alert ("Please select a code for this currency");
		return;
	}
	frm.state.value	= statePROCESS ;
	frm.submit();
}

</script>
<body class="body_popup">
<?= generate_form_header(); ?>
<table class="popup" border ="0">
	<tr><h4>Add/Edit Currency</h4></tr>

	<tr><td>Currency Name:<?= generate_required_superscript();?></td>
	<td>
	<?=	generate_text_input ("formtext_stdwidth", "NAME", $glb_arr_values['NAME']);?>
	</td></tr>
	<tr><td>Currency Code:<?= generate_required_superscript();?></td>
	<td>
	<?=	generate_text_input ("formtext_stdwidth", "CODE", $glb_arr_values['CODE']); ?>
	</td></tr>
	<tr><td colspan="2" align="right">
		<?php
		echo generate_button("button","Save","addCurrency();");
		echo "&nbsp;";
		echo generate_button("button","Cancel","javascript:void window.close();");
	?>
	</td></tr>
</table>
</form>
</body>
</html>
