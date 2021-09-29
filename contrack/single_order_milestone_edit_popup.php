
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
  	Milestone Edit popup page

	This page will handle editing/creating new milestones for a single order.
	It has three states: NEW, EDIT, and PROCESS. The latter is a transient state

******************************************************************************************/
require_once("db_utils.php");
require_once("general_utils.php");
require_once("order_utils.php");
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
	global $user, $b2s_payments_exist;
	// Authenticate
	if (! ($user->isAuthenticated())){
		deny_access();
	}
	get_page_params ();
	check_error();
	$b2s_payments_exist = do_b2s_payments_exist();
}

/*
	Get key parameters
*/
function get_page_params()
{
	global $state, $milestone_id, $order_id, $currency, $sub_entity_op, $sub_entity_name, $sub_entity_id;

	// Get key parameters
	$state  	= isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : state_EDIT);
	$milestone_id = isset($_GET['milestone_id']) ? $_GET['milestone_id'] : (isset($_POST['MILESTONE_ID']) ? $_POST['MILESTONE_ID'] : -1);
	$order_id	= isset($_GET['single_order_id']) ? $_GET['single_order_id'] : (isset($_POST['SINGLE_ORDER_ID']) ? $_POST['SINGLE_ORDER_ID'] : -1);
	$currency   = isset($_GET['currency']) ? $_GET['currency'] : (isset($_POST['currency']) ? $_POST['currency'] : -1);
	$sub_entity_op   = isset($_GET['sub_entity_op']) ? $_GET['sub_entity_op'] : (isset($_POST['sub_entity_op']) ? $_POST['sub_entity_op'] : -1);
	$sub_entity_name = isset($_GET['sub_entity_name']) ? $_GET['sub_entity_name'] : (isset($_POST['sub_entity_name']) ? $_POST['sub_entity_name'] : -1);
	$sub_entity_id 	 = isset($_GET['sub_entity_id']) ? $_GET['sub_entity_id'] : (isset($_POST['sub_entity_id']) ? $_POST['sub_entity_id'] : -1);
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
	global 	$glb_arr_values, $state, $milestone_id, $order_id, $currency, $sub_entity_op, $sub_entity_name, $sub_entity_id;

	// Get values from submitted form
	post_values();
	if ( $sub_entity_op == opcode_DELETE ) {
		if ( strcasecmp ('B2S_PAYMENT', $sub_entity_name ) == 0) delete_b2s_payment($sub_entity_id);
		// Go back to edit mode
		$location_url = "Location: single_order_milestone_edit_popup.php?single_order_id=".$order_id."&milestone_id=".$milestone_id.
						"&state=".state_EDIT."&currency=".$currency;
		header($location_url);
		exit;
	}

	if (validate_form()) {
		if (-1 == $glb_arr_values['MILESTONE_ID']) { // NEW
			$sql = generate_insert_milestone_sql() ;
			//echo "SQL Generated: " . $sql . "<br>";
			$result = mysql_query($sql) or die(mysql_error());
			if (!$result) return mysql_error();
			$glb_arr_values['MILESTONE_ID'] = mysql_insert_id();
			$milestone_id = $glb_arr_values['MILESTONE_ID'];
			$state = state_CLOSEPOPUP;
		}
		else {  								// EDIT
			$sql = generate_update_milestone_sql();
			//echo "SQL Generated: " . $sql . "<br>";
			$result = mysql_query($sql) or die(mysql_error());
			if (!$result) return mysql_error();
			if ( $sub_entity_op == opcode_ADDXPAYMENT ) {
				$location_url = "Location: single_order_milestone_edit_popup.php?single_order_id=".$order_id."&milestone_id=".$milestone_id.
						"&state=".state_EDIT."&currency=".$currency;
				header($location_url);
				exit;
			}
			$state = state_CLOSEPOPUP;
		}
	} else {  // validation unsuccessful
		if (-1 != $glb_arr_values['MILESTONE_ID']) {	// Go back to EDIT state
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
	global $state, $glb_arr_values, $milestone_id;

	if ( $state != state_NEW ) {  //VIEW
		$glb_arr_values['MILESTONE_ID'] =  $milestone_id;
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
	global $glb_arr_values, $order_id, $milestone_id;

	$glb_arr_values['SINGLE_ORDER_ID'] 	= $order_id ;
	$glb_arr_values['NAME'] 			= '';
	$glb_arr_values['DESCRIPTION']  	= '';
	$glb_arr_values['MILESTONE_TYPE']   = -1;
	$glb_arr_values['AMOUNT']  			= '';
	$glb_arr_values['DATE']    			= '';
	$glb_arr_values['MILESTONE_STATUS'] = milestone_FUTURE;
	$glb_arr_values['RECIPIENT_ID'] 	= COMPANY_PARTY_ID;
	$row = get_sin_order_agent_id_and_name($order_id);
	if (!empty ($row) ) {
		$glb_arr_values['AGENT_ID']   = $row['AGENT_ID'] ;
		$glb_arr_values['AGENT_NAME'] = mysql_decode($row['AGENT_NAME']) ;
	}
	else {
		$glb_arr_values['AGENT_ID']   = -1 ;
		$glb_arr_values['AGENT_NAME'] = '' ;
	}
	$row = get_sin_order_supplier_id_and_name($order_id);
	if (!empty ($row) ) {
		$glb_arr_values['SUPPLIER_ID']   = $row['SUPPLIER_ID'] ;
		$glb_arr_values['SUPPLIER_NAME'] = mysql_decode($row['SUPPLIER_NAME'] );
	}
	else {
		$glb_arr_values['SUPPLIER_ID']   = -1 ;
		$glb_arr_values['SUPPLIER_NAME'] = '' ;
	}
}

/*
	Get values from submitted form and populate global values array
*/
function post_values()
{
	global $glb_arr_values, $order_id, $milestone_id;

	$glb_arr_values['MILESTONE_ID']   	= $milestone_id;
	$glb_arr_values['SINGLE_ORDER_ID'] 	= $order_id;
	$glb_arr_values['AMOUNT']  			= isset($_POST['AMOUNT']) ? $_POST['AMOUNT'] : '';
	$glb_arr_values['NAME']  			= isset($_POST['NAME']) ? $_POST['NAME'] : '';
	$glb_arr_values['DESCRIPTION']  	= isset($_POST['DESCRIPTION']) ? $_POST['DESCRIPTION'] : '';
	$glb_arr_values['MILESTONE_TYPE']   = isset($_POST['MILESTONE_TYPE']) ? $_POST['MILESTONE_TYPE'] : milestone_INCOMING_PAYMENT;
	$glb_arr_values['RECIPIENT_ID'] 	= isset($_POST['RECIPIENT_ID']) ? $_POST['RECIPIENT_ID'] : COMPANY_PARTY_ID;
	$glb_arr_values['MILESTONE_STATUS'] = isset($_POST['MILESTONE_STATUS']) ? $_POST['MILESTONE_STATUS'] : milestone_FUTURE;
	$glb_arr_values['DATE']    			= isset($_POST['DATE']) ? $_POST['DATE'] : '';
}


/*
	Function: get_record()
	Desc:
		If milestone_id < 1 then this is a new record. Populate with defaults.
		If milestone_id > 1 get record from database and populate values global array
 	Params:
    	None
   	Returns:
		Nothing
*/
function get_record()
{
	global $glb_arr_values;

	if (1 > $glb_arr_values['MILESTONE_ID']) {
		new_values();
		return;
	}
	$sql = generate_select_milestone_sql();
	$result = mysql_query($sql) or die(mysql_error());
	if (!$result) return mysql_error();
	$row = mysql_fetch_array($result);

	// Populate values array
	$glb_arr_values['SINGLE_ORDER_ID'] 	= $row['SINGLE_ORDER_ID'];
	$glb_arr_values['NAME'] 			= mysql_decode($row['NAME']);
	$glb_arr_values['DESCRIPTION']  	= mysql_decode($row['DESCRIPTION']);
	$glb_arr_values['MILESTONE_TYPE']   = mysql_decode($row['MILESTONE_TYPE']);
	$glb_arr_values['AMOUNT']  			= mysql_decode($row['AMOUNT']);
	$glb_arr_values['DATE']    			= mysql_decode($row['DATE']);
	$glb_arr_values['MILESTONE_STATUS'] = mysql_decode($row['MILESTONE_STATUS']);
	$glb_arr_values['RECIPIENT_ID'] 	= mysql_decode($row['RECIPIENT_ID']);
	$glb_arr_values['AGENT_ID']   		= $row['AGENT_ID'] ;
	$glb_arr_values['AGENT_NAME'] 		= mysql_decode($row['AGENT_NAME']) ;
	$glb_arr_values['SUPPLIER_ID']  	= $row['SUPPLIER_ID'] ;
	$glb_arr_values['SUPPLIER_NAME'] 	= mysql_decode($row['SUPPLIER_NAME']) ;

	mysql_free_result($result);
}

/*
	Validate form values
*/
function validate_form()
{
	global $glb_arr_values, $errors;

	// Verify that at least a name exists for the party
	//if (empty($glb_arr_values['AMOUNT'])) $errors['AMOUNT'] = "Please enter an amount for this milestone";

	if (safe_count($errors) > 0)	{
		$errors['error'] = "There was a problem with the information entered. Please review the form and make necessary corrections.";
	}
	return safe_count($errors) == 0;
}

/****************************************
	SQL Generation functions
*****************************************/
/*
	Generates select SQL for milestone
*/
function generate_select_milestone_sql()
{
	global $glb_arr_values;

	$select_sql = " SELECT
					milestone.MILESTONE_ID, milestone.SINGLE_ORDER_ID, milestone.NAME, milestone.DESCRIPTION,
					milestone.MILESTONE_TYPE+0 AS MILESTONE_TYPE, milestone.AMOUNT, milestone.RECIPIENT_ID, milestone.DATE,
	 				milestone.MILESTONE_STATUS+0 AS MILESTONE_STATUS, single_order.SUPPLIER_ID,
					suppliers.NAME AS SUPPLIER_NAME, gen_order.AGENT_ID, agents.NAME AS AGENT_NAME
					FROM
					milestone INNER JOIN single_order on milestone.SINGLE_ORDER_ID = single_order.SINGLE_ORDER_ID
							  INNER JOIN party AS suppliers ON single_order.SUPPLIER_ID = suppliers.PARTY_ID
						      INNER JOIN gen_order ON single_order.GEN_ORDER_ID = gen_order.GEN_ORDER_ID
						      INNER JOIN party AS agents ON gen_order.AGENT_ID = agents.PARTY_ID
					WHERE
					milestone.MILESTONE_ID = ".$glb_arr_values['MILESTONE_ID'];
	return $select_sql;
}

/*
	Generates insert SQL for milestone
*/
function generate_insert_milestone_sql()
{
	global $glb_arr_values;

	$insert_sql = "INSERT INTO milestone
					(STATE, SINGLE_ORDER_ID, AMOUNT, NAME, DESCRIPTION, RECIPIENT_ID, MILESTONE_TYPE,
					 MILESTONE_STATUS, DATE)
					VALUES(	'ACTIVE',
					".mysql_encode($glb_arr_values['SINGLE_ORDER_ID']).",
					'".mysql_encode($glb_arr_values['AMOUNT'])."',
					'".mysql_encode($glb_arr_values['NAME'])."',
					'".mysql_encode($glb_arr_values['DESCRIPTION'])."',
					".mysql_encode($glb_arr_values['RECIPIENT_ID']).",
					".mysql_encode($glb_arr_values['MILESTONE_TYPE']).",
					".mysql_encode($glb_arr_values['MILESTONE_STATUS']).",
					'".mysql_encode(format_date($glb_arr_values['DATE']))."')";
	return $insert_sql;
}

/*
	Generates update SQL for milestone
*/

function generate_update_milestone_sql()
{
	global $glb_arr_values;

	$update_sql =  "UPDATE milestone SET

					AMOUNT = '" .mysql_encode( $glb_arr_values['AMOUNT'])."',
					NAME = '".mysql_encode( $glb_arr_values['NAME'])."',
					DESCRIPTION = '".mysql_encode( $glb_arr_values['DESCRIPTION'])."',
					RECIPIENT_ID = ".mysql_encode( $glb_arr_values['RECIPIENT_ID']).",
					MILESTONE_TYPE = ".mysql_encode( $glb_arr_values['MILESTONE_TYPE']).",
					MILESTONE_STATUS = ".mysql_encode( $glb_arr_values['MILESTONE_STATUS']).",
					DATE = '" .mysql_encode(format_date($glb_arr_values['DATE']))."'

					WHERE MILESTONE_ID = ".$glb_arr_values['MILESTONE_ID'];

	return $update_sql;
}
/*
	Generates select SQL for buyer-2-supplier payments
*/

function generate_select_b2s_payments_sql($milestone_id)
{
	$select_sql = "	SELECT *
					FROM payment_b2s
					WHERE MILESTONE_ID = ".$milestone_id ." AND STATE = 'ACTIVE'";
	return $select_sql;
}

function delete_b2s_payment($b2s_payment_id)
{
	if ($b2s_payment_id > 0 ) {
		$sql = "DELETE FROM payment_b2s WHERE PAYMENT_B2S_ID = ". $b2s_payment_id;
		$result = mysql_query($sql) or die(mysql_error());
	}
}

/*
	Check if any external payments exist for this milestone
*/
function do_b2s_payments_exist()
{
	global $milestone_id;

	$sql 	   = generate_select_b2s_payments_sql($milestone_id);
	$result	   = mysql_query($sql) or die(mysql_error());
	$existence = mysql_num_rows($result) > 0 ;
	mysql_free_result($result);
	return $existence;
}

function show_b2s_payments()
{
	global $milestone_id, $currency, $state;
 	// Get buyer-so-supplier payments from DB
	$sql = generate_select_b2s_payments_sql($milestone_id);
	$result = mysql_query($sql) or die(mysql_error());

	if ( (mysql_num_rows($result) == 0)  && ($state ==  state_NEW) ){
		$markup =  '';
	}
	else {
		$markup =  '<table>
					<tr class="con_table_header">';
		$markup .= "<td class=\"small_text\" width=\"10%\"><a href=\"javascript:
				void open_popup('b2s_payment_edit_popup.php?milestone_id=".$milestone_id.
								"&state=".state_NEW."',
								'b2sPayment','menubar=no,toolbar=no,scrollbars=no',170,435);\">
								<img src=\"images/b.add.png\" alt=\"Add\" title=\"Add\"/></a></td>\r\n" ;
		$markup .=	'<td>Ext. Ref. #</td>
					<td>Date</td>
					<td>Amount</td></tr>';
		$i=0;
		$total_amount = 0;
		while ($row = mysql_fetch_array($result)) {
		$pk      = $row['PAYMENT_B2S_ID'];
		$markup .= "<tr class=\"con_table_line" .($i%2). "\">";
		$markup .= "<td class=\"small_text\">";
		$markup .= "<a href=\"javascript:
						void open_popup('b2s_payment_edit_popup.php?milestone_id=".$milestone_id."&payment_b2s_id=".$pk.
						"&state=".state_EDIT."',
						'b2sPayment','menubar=no,toolbar=no,scrollbars=no',170,435);\">
						<img src=\"images/b.edit.png\" alt=\"Edit\" title=\"Edit\"/></a>
						<a href=\"javascript:void EntityDelete('B2S_PAYMENT',". $pk .");\">
						<img src=\"images/b.delete.png\" alt=\"Delete\" title=\"Delete\"/></a>";
		$markup .= "</td>\r\n";
		$markup .= "<td>". mysql_decode($row['INVOICE_REF'])  . "</td>";
		$markup .= "<td>". format_date_reverse($row['DATE'])  . "</td>";
		$markup .= "<td>". $currency. ' '. number_format($row['AMOUNT'], NUM_DEC_PLACES, '.',',') . "</td>";
		$total_amount += $row['AMOUNT'];
		$i++;
		}  // end while
		mysql_free_result($result);
		$markup .= '<tr class="total_amount"><td colspan="3">Total </td>
						<td>'.$currency.' '.
						number_format($total_amount, NUM_DEC_PLACES, '.', ',').'</td></tr>';
		// B2S payments table footer
		$markup .= '</table>'."\r\n" ;
	}
	return $markup;
}
/*
	Generates standard form header for milestone
*/
function generate_form_header()
{
	global $glb_arr_values, $order_id, $state, $milestone_id, $currency,
		   $sub_entity_op, $sub_entity_name, $sub_entity_id;

	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_milestone\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"currency\" name=\"currency\" value=\"" . $currency . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"SINGLE_ORDER_ID\" name=\"SINGLE_ORDER_ID\" value=\"" . $order_id . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"MILESTONE_ID\" name=\"MILESTONE_ID\" value=\"" . $milestone_id . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"RECIPIENT_ID\" name=\"RECIPIENT_ID\" value=\"" . $glb_arr_values['RECIPIENT_ID'] . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_op\" name=\"sub_entity_op\" value=\"" . $sub_entity_op . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_name\" name=\"sub_entity_name\" value=\"" . $sub_entity_name . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_id\" name=\"sub_entity_id\" value=\"" . $sub_entity_id . "\"/>\r\n";
	return $str_frm_header;
}
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Contrack - Add/Edit Milestone</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript" src="calendar/calendar.js"></script>
<script type="text/javascript" src="calendar/lang/calendar-en.js"></script>
<script type="text/javascript" src="calendar/calendar-setup.js"></script>
<link href="contrack_calendar.css" rel="stylesheet" type="text/css" media="all"/>
<link href="contrack.css" rel="stylesheet" type="text/css" media="all" />
</head>
<script src="utilities.js" type="text/javascript"></script>
<script type="text/javascript"><!--
var state = <?= ($state=="") ? 0 : $state ?>;
var supplierID   = <?= isset($glb_arr_values['SUPPLIER_ID'])? $glb_arr_values['SUPPLIER_ID']: -1 ?>;
var supplierName = "<?= isset($glb_arr_values['SUPPLIER_NAME'])? $glb_arr_values['SUPPLIER_NAME'] : '' ?>";
var agentID      = <?= isset($glb_arr_values['AGENT_ID'])? $glb_arr_values['AGENT_ID']: -1 ?>;
var agentName    = "<?= isset($glb_arr_values['AGENT_NAME'])? $glb_arr_values['AGENT_NAME']: '' ?>";
var b2sPaymentsExist = <?= $b2s_payments_exist ? 1 : 0 ?>;
var originalMilestoneType = <?=  isset($glb_arr_values['MILESTONE_TYPE']) ? $glb_arr_values['MILESTONE_TYPE'] : -1 ?>;

willIClose(state, window);

function adjustControls(onLoad)
{
	var MilestoneType = document.getElementById("MILESTONE_TYPE");
	var Payments      = document.getElementById("B2SPayments");
	var frm           = document.forms["frm_milestone"];
	var Recipient     = document.getElementById("RecipientName");

	if (!onLoad) {
		if (doPaymentsExist()) return;
	}

	if (MilestoneType.value == milestoneINCOMING_PAYMENT) {
		Payments.style.display = "none";
		window.resizeTo("500","310");
		frm.RECIPIENT_ID.value = companyPartyID;
		Recipient.childNodes[0].nodeValue = "Inbound";
	}
	else if (MilestoneType.value == milestoneAGENT_COMMISSION) {
		Payments.style.display = "none";
		window.resizeTo("500","310");
		frm.RECIPIENT_ID.value = agentID;
		Recipient.childNodes[0].nodeValue = agentName;
	}
	else if (MilestoneType.value == milestoneB2S_PAYMENT) {
		if (state == stateNEW) {
			Payments.style.display = "none";
			window.resizeTo("500","310");
		}
		else {
			Payments.style.display = "block";
			window.resizeTo("500","515");
		}
		frm.RECIPIENT_ID.value = supplierID;
		Recipient.childNodes[0].nodeValue = supplierName;
	}
	else {
		Payments.style.display = "none";
		window.resizeTo("500","310");
		frm.RECIPIENT_ID.value = 0;
		Recipient.childNodes[0].nodeValue = "N/A";
	}
}

function doPaymentsExist()
{
	if (b2sPaymentsExist) {
		alert ("This milestone has payments associated with it.\nPlease remove all payments before changing the milestone type.");
		document.getElementById("MILESTONE_TYPE").value = originalMilestoneType;
		return true;
	}
	else {
		return false;
	}
}
/*
	Rewrite validation in this function
*/
function addMilestone()
{
	var frm = document.forms["frm_milestone"];

	if (frm.NAME.value.length == 0) {
		alert ("Please enter a name for this milestone");
		return;
	}
	if (frm.MILESTONE_TYPE.value == 0) {
		alert ("Please select a milestone type");
		return;
	}
	if (validateNumeric('Amount',frm.AMOUNT.value)) {
		frm.state.value	= statePROCESS ;
		frm.submit();
	}
}

function EntityDelete(strName, intID)
{
	if (confirm ('Are you sure you want to delete this B2S Payment?') ) {
		frm = document.forms["frm_milestone"];
		frm.state.value		       = statePROCESS;
		frm.sub_entity_op.value    = opcodeDELETE;
		frm.sub_entity_name.value  = strName;
		frm.sub_entity_id.value    = intID;
		frm.submit();
	}
}
function resubmitForm()
{
	var frm = document.forms["frm_milestone"];
	frm.state.value = statePROCESS;
	frm.sub_entity_op.value = opcodeADDXPAYMENT;
	frm.submit();
}

</script>
<body class="body_popup" onload="adjustControls(true);">
<?= generate_form_header(); ?>
<table class="popup" border ="0">
	<tr><h4>Add/Edit Milestone</h4></tr>

	<tr><td>Name:<?= generate_required_superscript();?></td>
	<td>
	<?=	generate_text_input ("formtext_stdwidth", "NAME", $glb_arr_values['NAME']);?>
	</td></tr>
		<tr><td>Description:</td>
	<td>
	<?=	generate_text_input ("formtext_longwidth", "DESCRIPTION", $glb_arr_values['DESCRIPTION']); ?>
	</td></tr>
	<tr><td>Type:<?= generate_required_superscript();?></td><td>
	<select name="MILESTONE_TYPE" id="MILESTONE_TYPE" onchange="adjustControls(false);">
	<?php
	echo populate_milestone_type_dropdown($glb_arr_values['MILESTONE_TYPE']);
	?>
	</select></td>
	</tr>
	<tr><td>Recipient:</td>
	<td><span id="RecipientName">N/A</span></td>
	</tr>
	<tr><td>Amount:<?= generate_required_superscript();?></td>
	<td>
	<?=	generate_text_input ("formtext_stdwidth", "AMOUNT", $glb_arr_values['AMOUNT']). ' '. $currency;	?>
	</td></tr>
	<tr><td>Date:</td>
	<td>
	<?=	generate_text_input ("date_box", "DATE", format_date_reverse($glb_arr_values['DATE']));?>
	</td></tr>
	<tr><td>Status:</td>
	<td>
	<select id="MILESTONE_STATUS" name="MILESTONE_STATUS">
	<?= populate_milestones_status_dropdown($glb_arr_values['MILESTONE_STATUS']);?>
	</select>
	</td></tr>
	<tr><td colspan="2">
	<div id="B2SPayments" name="B2SPayments" class="b2s_payments_div">
		<?= show_b2s_payments();?>
	</div>
	</td></tr>
	<tr><td colspan="2" align="right">
		<?php
		echo generate_button("button","Save & Close","addMilestone();");
		echo "&nbsp;";
		echo generate_button("button","Cancel","javascript:void window.close();");
	?>
	</td></tr>
</table>
</form>
</body>
</html>
