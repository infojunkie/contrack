	
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
  	Additional Costs popup page

	This page will handle editing/creating new additional costs for single and general orders
	It has three states: NEW, EDIT, and PROCESS. The latter is a transient state	
******************************************************************************************/
require_once ("db_utils.php");
require_once ("general_utils.php");
require_once ("order_utils.php");
require_once ("patUser-2.2.3/prepend.php");
require_once ("permissions.php");

init_page();
process_state();

/****************************************************
	Page Initialisation
	- Authenticate
	- Get all parameters passed to the page
	- Check for any errors passed to the page	
****************************************************/
function init_page() {
	global $user;
	// Authenticate
	if (!($user->isAuthenticated())) {
		deny_access();
	}
	get_page_params();
	check_error();
}

/*
	Get key parameters
*/
function get_page_params() {
	global $state, $order_id, $order_type, $add_cost_id, $currency;

	// Get key parameters
	$state = isset ($_GET['state']) ? $_GET['state'] : (isset ($_POST['state']) ? $_POST['state'] : state_EDIT);
	$order_type = isset ($_GET['order_type']) ? $_GET['order_type'] : (isset ($_POST['order_type']) ? $_POST['order_type'] : entity_GEN_ORDER);
	$order_id = isset ($_GET['order_id']) ? $_GET['order_id'] : (isset ($_POST['order_id']) ? $_POST['order_id'] : -1);
	$add_cost_id = isset ($_GET['add_cost_id']) ? $_GET['add_cost_id'] : (isset ($_POST['ADDITIONAL_COST_ID']) ? $_POST['ADDITIONAL_COST_ID'] : -1);
	$currency = ($order_type == entity_GEN_ORDER ? get_gen_order_currency($order_id) : get_sin_order_currency($order_id));
}

function process_state() {
	global $state;

	// Check state
	if (empty ($state)) { /* if $state is empty go to EDIT state*/
		edit_form();
	}
	elseif ($state == state_NEW) {
		new_form();
	}
	elseif ($state == state_EDIT) {
		edit_form();
	}
	elseif ($state == state_PROCESS) {
		process_form();
	} else { // might be redundant but just in case...
		edit_form();
	}
}

/**************************************** 
	Root functions
*****************************************/
function new_form() {
	init_form();
}

function edit_form() {
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
function process_form() {
	global $glb_arr_values, $state, $add_cost_id, $order_id, $order_type;

	// Get values from submitted form	
	post_values();

	if (validate_form()) {
		if (-1 == $glb_arr_values['ADDITIONAL_COST_ID']) { // NEW
			$sql = generate_insert_add_cost_sql();
			$result = mysql_query($sql) or die(mysql_error());
			if (!$result)
				return mysql_error();
			$glb_arr_values['ADDITIONAL_COST_ID'] = mysql_insert_id();
			$add_cost_id = $glb_arr_values['ADDITIONAL_COST_ID'];
			$state = state_CLOSEPOPUP;
		} else { // EDIT
			$sql = generate_update_add_cost_sql();
			$result = mysql_query($sql) or die(mysql_error());
			if (!$result)
				return mysql_error();
			$state = state_CLOSEPOPUP;
		}
	} else { // validation unsuccessful
		if (-1 != $glb_arr_values['ADDITIONAL_COST_ID']) { // Go back to EDIT state			
			$state = state_EDIT;
		} else { // Go back to NEW state
			$state = state_NEW;
		}
	}
}
/*
	Intitialize page
*/
function init_form() {
	global $state, $glb_arr_values, $add_cost_id;

	if ($state != state_NEW) { //VIEW
		$glb_arr_values['ADDITIONAL_COST_ID'] = $add_cost_id;
	} else { //NEW		
		new_values();
	}
}
/*
	Populate global values array with defaults when the page is in 'NEW' state
*/
function new_values() {
	global $glb_arr_values, $order_id, $add_cost_id, $order_type;

	$glb_arr_values['ADDITIONAL_COST_ID'] = $add_cost_id;
	$glb_arr_values['DESCRIPTION'] = '';
	$glb_arr_values['AMOUNT'] = '';
	$glb_arr_values['PAYOR_ID'] = 0;
	$glb_arr_values['PAYEE_ID'] = 0;
	$glb_arr_values['GEN_ORDER_ID'] = ($order_type == entity_GEN_ORDER ? $order_id : get_sin_order_gen_order_id($order_id));
	$glb_arr_values['SINGLE_ORDER_ID'] = ($order_type == entity_SINGLE_ORDER ? $order_id : 0);
}

/*
	Get values from submitted form and populate global values array
*/
function post_values() {
	global $glb_arr_values, $add_cost_id, $order_id, $order_type;

	$glb_arr_values['ADDITIONAL_COST_ID'] = $add_cost_id;
	$glb_arr_values['DESCRIPTION'] = isset ($_POST['DESCRIPTION']) ? $_POST['DESCRIPTION'] : '';
	$glb_arr_values['AMOUNT'] = isset ($_POST['AMOUNT']) ? $_POST['AMOUNT'] : 0;
	$glb_arr_values['PAYOR_ID'] = isset ($_POST['PAYOR_ID']) ? $_POST['PAYOR_ID'] : 0;
	$glb_arr_values['PAYEE_ID'] = isset ($_POST['PAYEE_ID']) ? $_POST['PAYEE_ID'] : 0;
	$glb_arr_values['GEN_ORDER_ID'] = ($order_type == entity_GEN_ORDER ? $order_id : get_sin_order_gen_order_id($order_id));
	$glb_arr_values['SINGLE_ORDER_ID'] = ($order_type == entity_SINGLE_ORDER ? $order_id : 0);
}

/*
	Function: get_record()
	Desc:  
		If add_cost_id < 1 then this is a new record. Populate with defaults.
		If add_cost_id > 1 get record from database and populate values global array
 	Params: 
    	None
   	Returns: 
		Nothing
*/
function get_record() {
	global $glb_arr_values;

	if (1 > $glb_arr_values['ADDITIONAL_COST_ID']) {
		new_values();
		return;
	}
	$sql = generate_select_add_cost_sql();
	$result = mysql_query($sql) or die(mysql_error());
	if (!$result)
		return mysql_error();
	$row = mysql_fetch_array($result);

	// Populate values array
	$glb_arr_values['DESCRIPTION'] = mysql_decode($row['DESCRIPTION']);
	$glb_arr_values['PAYOR_ID'] = $row['PAYOR_ID'];
	$glb_arr_values['PAYEE_ID'] = $row['PAYEE_ID'];
	$glb_arr_values['AMOUNT'] = $row['AMOUNT'];
	$glb_arr_values['GEN_ORDER_ID'] = $row['GEN_ORDER_ID'];
	$glb_arr_values['SINGLE_ORDER_ID'] = $row['SINGLE_ORDER_ID'];

	mysql_free_result($result);
}

/*
	Validate form values
*/
function validate_form() {
	global $glb_arr_values, $errors;

	// Verify that at least a name exists for the party
	if (empty ($glb_arr_values['AMOUNT']))
		$errors['AMOUNT'] = "Please enter an amount for this additional cost";

	// URL Check

	if (count($errors) > 0) {
		$errors['error'] = "There was a problem with the information entered. Please review the form and make necessary corrections.";
	}
	return count($errors) == 0;
}

/**************************************** 
	SQL Generation functions
*****************************************/
/*
	Generates select SQL for additional cost
*/
function generate_select_add_cost_sql() {
	global $glb_arr_values;

	$select_sql = "	SELECT * 
						FROM additional_cost 
						WHERE ADDITIONAL_COST_ID =".$glb_arr_values['ADDITIONAL_COST_ID'];
	return $select_sql;
}

/*
	Generates insert SQL for additional cost
*/
function generate_insert_add_cost_sql() {
	global $glb_arr_values, $order_type;

	$insert_sql = "INSERT INTO additional_cost 
						(DESCRIPTION, AMOUNT, PAYOR_ID, PAYEE_ID, GEN_ORDER_ID, SINGLE_ORDER_ID, BELONGS_TO)
						VALUES(		
						'".mysql_encode($glb_arr_values['DESCRIPTION'])."',
						".$glb_arr_values['AMOUNT'].",
						".$glb_arr_values['PAYOR_ID'].",
						".$glb_arr_values['PAYEE_ID'].",
						".$glb_arr_values['GEN_ORDER_ID'].",
						".$glb_arr_values['SINGLE_ORDER_ID'].",
						".$order_type.")";
	return $insert_sql;
}

/*
	Generates update SQL for additional cost
*/

function generate_update_add_cost_sql() {
	global $glb_arr_values, $order_type;

	$update_sql = "UPDATE additional_cost SET																
						DESCRIPTION 	= '".mysql_encode($glb_arr_values['DESCRIPTION'])."',
						AMOUNT 			= ".$glb_arr_values['AMOUNT'].",
						PAYOR_ID 		= ".$glb_arr_values['PAYOR_ID'].",
						PAYEE_ID 		= ".$glb_arr_values['PAYEE_ID'].",
						GEN_ORDER_ID 	= ".$glb_arr_values['GEN_ORDER_ID'].",
						SINGLE_ORDER_ID = ".$glb_arr_values['SINGLE_ORDER_ID'].",
						BELONGS_TO 		= ".$order_type."
						WHERE ADDITIONAL_COST_ID = ".$glb_arr_values['ADDITIONAL_COST_ID'];
	return $update_sql;
}
/*
	Generates standard form header for additional cost
*/
function generate_form_header() {
	global $state, $order_id, $order_type, $add_cost_id, $currency;

	$str_frm_header = "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" name=\"frm_add_cost\">\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"".$state."\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"order_id\" name=\"order_id\" value=\"".$order_id."\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"order_type\" name=\"order_type\" value=\"".$order_type."\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"ADDITIONAL_COST_ID\" name=\"ADDITIONAL_COST_ID\" value=\"".$add_cost_id."\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"currency\" name=\"currency\" value=\"".$currency."\"/>\r\n";
	return $str_frm_header;
}
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Contrack - Edit Additional Cost</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="contrack.css" rel="stylesheet" type="text/css" media="all" />
</head>
<script src="utilities.js" type="text/javascript"></script>
<script type="text/javascript"><!--
var state = <?= ($state=="") ? 0 : $state ?>;
willIClose(state, window);

function addCost()
{
	var frm = document.forms["frm_add_cost"];
	if (frm.PAYOR_ID.value <= 0) {
		alert ("You must associate a Payor with this additional cost");
		return;
	}
	if (frm.PAYEE_ID.value <= 0) {
		alert ("You must associate a Payee with this additional cost");
		return;
	}
	if (!validateNumeric('Amount',frm.AMOUNT.value)) return;

	// All tests passed
	frm.state.value	= statePROCESS ;		
	frm.submit();	
}
--></script>
<body class="body_popup">
<?= generate_form_header(); ?>
<table class="popup" border="0">
	<tr><h4>Add/Edit Additional Cost</h4></tr>
	
	<tr><td>Description:</td>
	<td>
	<?=	generate_text_input ("formtext_longwidth", "DESCRIPTION", $glb_arr_values['DESCRIPTION']);?>
	</td></tr>

	<tr>
	<td>Payor:<?= generate_required_superscript();?></td>
	<td>
	<?=	generate_dd_add_cost_payor_input ($glb_arr_values['PAYOR_ID'], $order_id, $order_type);?>
	</td></tr>
	
	<tr><td>Payee:<?= generate_required_superscript();?></td>
	<td>
	<?=	generate_dd_add_cost_payee_input ($glb_arr_values['PAYEE_ID'], $order_id, $order_type);?>
	</td></tr>
	
	<tr><td>Amount:<?= generate_required_superscript();?></td>
	<td>
	<?=	
		generate_text_input ("formtext_stdwidth", "AMOUNT", $glb_arr_values['AMOUNT']).$currency;	
	?>
	</td></tr>
		
	<tr><td colspan="2" align="right">
	<?php

echo generate_button("button", "Save & Close", "addCost();");
echo "&nbsp;";
echo generate_button("button", "Cancel", "javascript:void window.close();");
?>		  
	</td></tr>
</table>
</form>
</body>
</html>

