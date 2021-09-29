
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
  	Invoice Payment popup page

	This page will handle editing/creating new payments for an invoice.
	It has three states: NEW, EDIT, and PROCESS. The latter is a transient state

******************************************************************************************/
require_once("db_utils.php");
require_once("general_utils.php");
require_once("invoice_utils.php");
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
	global $state, $payment_id, $invoice_id, $currency;

	// Get key parameters
	$state  	= isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : state_EDIT);
	$payment_id = isset($_GET['payment_id']) ? $_GET['payment_id'] : (isset($_POST['PAYMENT_ID']) ? $_POST['PAYMENT_ID'] : -1);
	$invoice_id	= isset($_GET['invoice_id']) ? $_GET['invoice_id'] : (isset($_POST['INVOICE_ID']) ? $_POST['INVOICE_ID'] : -1);
	$currency   = get_invoice_currency($invoice_id);
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
	global 	$glb_arr_values, $state, $payment_id, $invoice_id;

	// Get values from submitted form
	post_values();

	if (validate_form()) {
		if (-1 == $glb_arr_values['PAYMENT_ID']) { // NEW
			$sql = generate_insert_payment_sql() ;
			//echo "SQL Generated: " . $sql . "<br>";
			$result = mysql_query($sql) or die(mysql_error());
			if (!$result) return mysql_error();
			$glb_arr_values['PAYMENT_ID'] = mysql_insert_id();
			$payment_id = $glb_arr_values['PAYMENT_ID'];
			// Update Paid amount in invoice table
			$sql = generate_update_invoice_paid_amount($invoice_id);
			$result = mysql_query($sql) or die(mysql_error());
			if (!$result) return mysql_error();
			$state = state_CLOSEPOPUP;
		} else {  								// EDIT
			$sql = generate_update_payment_sql();
			//echo "SQL Generated: " . $sql . "<br>";
			$result = mysql_query($sql) or die(mysql_error());
			if (!$result) return mysql_error();
			// Update Paid amount in invoice table
			$sql = generate_update_invoice_paid_amount($invoice_id);
			$result = mysql_query($sql) or die(mysql_error());
			if (!$result) return mysql_error();
			$state = state_CLOSEPOPUP;
		}
	} else {  // validation unsuccessful
		if (-1 != $glb_arr_values['PAYMENT_ID']) {	// Go back to EDIT state
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
	global $state, $glb_arr_values, $payment_id;

	if ( $state != state_NEW ) {  //VIEW
		$glb_arr_values['PAYMENT_ID'] =  $payment_id;
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
	global $glb_arr_values, $invoice_id, $payment_id;

	$glb_arr_values['INVOICE_ID'] 	= $invoice_id ;
	$glb_arr_values['DATE'] 		= '';
	$glb_arr_values['AMOUNT'] 		= '';
	$glb_arr_values['DOCUMENT_REF'] = '';
}

/*
	Get values from submitted form and populate global values array
*/
function post_values()
{
	global $glb_arr_values, $payment_id, $invoice_id;

	$glb_arr_values['PAYMENT_ID']   = $payment_id;
	$glb_arr_values['INVOICE_ID'] 	= $invoice_id;
	$glb_arr_values['DATE'] 		= isset($_POST['DATE']) ? $_POST['DATE'] : '';
	$glb_arr_values['AMOUNT'] 		= isset($_POST['AMOUNT']) ? $_POST['AMOUNT'] : 0;
	$glb_arr_values['DOCUMENT_REF'] = isset($_POST['DOCUMENT_REF']) ? $_POST['DOCUMENT_REF'] : '';
}


/*
	Function: get_record()
	Desc:
		If payment_id < 1 then this is a new record. Populate with defaults.
		If payment_id > 1 get record from database and populate values global array
 	Params:
    	None
   	Returns:
		Nothing
*/
function get_record()
{
	global $glb_arr_values;

	if (1 > $glb_arr_values['PAYMENT_ID']) {
		new_values();
		return;
	}
	$sql = generate_select_payment_sql();
	$result = mysql_query($sql) or die(mysql_error());
	if (!$result) return mysql_error();
	$row = mysql_fetch_array($result);

	// Populate values array
	$glb_arr_values['DATE'] 		= mysql_decode($row['DATE']);
	$glb_arr_values['AMOUNT'] 		= $row['AMOUNT'];
	$glb_arr_values['DOCUMENT_REF'] = mysql_decode($row['DOCUMENT_REF']);

	mysql_free_result($result);
}

/*
	Validate form values
*/
function validate_form()
{
	global $glb_arr_values, $errors;

	if (empty($glb_arr_values['AMOUNT'])) $errors['AMOUNT'] = "Please enter an amount for this payment";

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
	Generates select SQL for payment
*/
function generate_select_payment_sql()
{
	global $glb_arr_values;

	$select_sql = "SELECT * FROM payment WHERE PAYMENT_ID=".$glb_arr_values['PAYMENT_ID'];
	return $select_sql;
}

/*
	Generates insert SQL for payment
*/
function generate_insert_payment_sql()
{
	global $glb_arr_values;

	$insert_sql = "INSERT INTO payment
					(STATE, INVOICE_ID, DATE, AMOUNT, DOCUMENT_REF)
					VALUES(	'ACTIVE',
					".mysql_encode($glb_arr_values['INVOICE_ID']).",
					'".mysql_encode(format_date($glb_arr_values['DATE']))."',
					".mysql_encode($glb_arr_values['AMOUNT']).",
					'".mysql_encode($glb_arr_values['DOCUMENT_REF'])."')";
	return $insert_sql;
}

/*
	Generates update SQL for payment
*/

function generate_update_payment_sql()
{
	global $glb_arr_values;

	$update_sql =  "UPDATE payment SET

					DATE = '" .mysql_encode(format_date($glb_arr_values['DATE']))."',
					AMOUNT = " .mysql_encode( $glb_arr_values['AMOUNT']).",
					DOCUMENT_REF = '" .mysql_encode( $glb_arr_values['DOCUMENT_REF'])."'

					WHERE PAYMENT_ID=".$glb_arr_values['PAYMENT_ID'];

	return $update_sql;
}
/*
	Generates standard form header for payment
*/
function generate_form_header()
{
	global $invoice_id, $state, $payment_id, $currency;

	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_payment\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"INVOICE_ID\" name=\"INVOICE_ID\" value=\"" . $invoice_id . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"PAYMENT_ID\" name=\"PAYMENT_ID\" value=\"" . $payment_id . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"currency\" name=\"currency\" value=\"" . $currency . "\"/>\r\n";
	return $str_frm_header;
}
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Contrack - Edit Invoice Payment</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript" src="calendar/calendar.js"></script>
<script type="text/javascript" src="calendar/lang/calendar-en.js"></script>
<script type="text/javascript" src="calendar/calendar-setup.js"></script>
<link href="contrack_calendar.css" rel="stylesheet" type="text/css" media="all"/>
<link href="contrack.css" rel="stylesheet" type="text/css" media="all" />
<script src="utilities.js" type="text/javascript"></script>
</head>
<script type="text/javascript"><!--
var state = <?= ($state=="") ? 0 : $state ?>;
willIClose(state, window);

function addPayment()
{
	var frm = document.frm_payment;
	if (validateNumeric('Amount',frm.AMOUNT.value)) {
		frm.state.value	= statePROCESS ;
		frm.submit();
	}
}
--></script>
<body class="body_popup">
<?= generate_form_header(); ?>
<table class="popup" border="0">
	<tr><h4>Add/Edit Invoice Payment</h4></tr>

	<tr><td>Date:</td>
	<td>
	<?=	generate_text_input ("date_box", "DATE", format_date_reverse($glb_arr_values['DATE'])); ?>
	</td></tr>

	<tr><td>Document Ref:</td>
	<td>
	<?=	generate_text_input ("formtext_longwidth", "DOCUMENT_REF", $glb_arr_values['DOCUMENT_REF']); ?>
	</td></tr>

	<tr><td>Amount:</td>
	<td>
	<?=	generate_text_input ("formtext_stdwidth", "AMOUNT", $glb_arr_values['AMOUNT']).$currency; ?>
	</td></tr>

	<tr><td colspan="2" align="right">
	<?php
		echo generate_button("button","Save & Close","addPayment();");
		echo "&nbsp;";
		echo generate_button("button","Cancel","javascript:void window.close();");
	?>
	</td></tr>
</table>
</form>
</body>
</html>
