	
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
  	Invoice Line Items popup page

	This page is responsible for adding line items to the invoice.
	There are three types of invoice line items:
		1. Milestone
		2. Free-form
		3. Write-off
	If the line item selected is of 
	It has two states: VIEW and PROCESS
	It contains:
		- a drop-down for line item types
		- a table within a DIV showing milestones related to the invoice 
		  (enabled only for line item type == milestone)
		- two fields for amount and description (enabled only for line item type != milestone)
	
	Milestones are related to the invoice when the following conditions are met:
		- Of Type Incoming Payment.
        - Belonging to single orders where:
			. The payor selected in the invoice is the commission payor of that single order
			. Currency in the general order that the single order belongs to is the same as 
			  the currency selected for that invoice
			. General Order and Single Orders related to the milestone have status = OPEN			  
	
	When a line item is added or deleted, the value in BILLED_AMOUNT field in the INVOICE table 
	is re-calculated.  
******************************************************************************************/
require_once("db_utils.php");
require_once("general_utils.php");
require_once("invoice_utils.php");
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
	global $user, $milestones_exist;
	// Authenticate
	if (! ($user->isAuthenticated())){
		deny_access();
	}	
	get_page_params ();
	check_error();	
	$milestones_exist = do_milestones_exist();
}
/*
	Get key parameters
*/
function get_page_params()
{
	global $state, $invoice_id, $line_item_type, $line_item_id, $payor_id, $payor_name,
				   $currency_id, $currency_desc;

	// Get key parameters
	$state  	   = isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : "");	
	$invoice_id    = isset($_GET['invoice_id']) ? $_GET['invoice_id'] : (isset($_POST['invoice_id']) ? $_POST['invoice_id'] : -1); 
	$line_item_type= isset($_GET['line_item_type']) ? $_GET['line_item_type'] : (isset($_POST['LINE_ITEM_TYPE']) ? $_POST['LINE_ITEM_TYPE'] : -1); 
	$line_item_id  = isset($_GET['line_item_id']) ? $_GET['line_item_id'] : (isset($_POST['line_item_id']) ? $_POST['line_item_id'] : -1); 
	$payor_id      = isset($_GET['payor_id']) ? $_GET['payor_id'] : (isset($_POST['payor_id']) ? $_POST['payor_id'] : -1); 
	$currency_id   = isset($_GET['currency_id']) ? $_GET['currency_id'] : (isset($_POST['currency_id']) ? $_POST['currency_id'] : -1); 
	$payor_name    = get_party_name ($payor_id);
	$currency_desc = get_currency_desc($currency_id);
}

function process_state()
{
	global $state;
	
	if ($state == state_NEW) {
		new_form();
	}
	else if ($state == state_EDIT) {
		edit_form();
	}
	else if ($state == state_PROCESS) {
		process_form();	
	} 
}

function new_form()
{
	new_values();
}

function edit_form()
{
	get_record();
}

/*
	Get values of milestones checkboxes from posted form and amount and description			
	For each milestone checkbox, parse the MILESTONE_ID, GEN_ORDER_ID, SINGLE_ORDER_ID comma seperated list
	to be able to insert into invoice_rel_order 
	
	Notes: 
		Had to break up the SQL batch (for inserting multiple milestones) into multiple statements 
		because this is not	supported in MySQL 4.0
		For further information check: http://bugs.mysql.com/bug.php?id=3745
*/
function process_form()
{
	global $glb_arr_values, $state, $invoice_id, $line_item_id, $line_item_type;

	post_values();

	if ($line_item_type == line_item_MILESTONE) {
		if (isset($_POST['milestone'])) {
			if (count($_POST['milestone']) > 0){
				$i = 0;
				foreach ($_POST['milestone'] as $chk_box_value) {						
					$milestone_id 	 = strtok($chk_box_value,",") ;
					$gen_order_id 	 = strtok(",");
					$single_order_id = strtok(",");
					$amount 		 = strtok(",");
					// Insert into invoice_line_item table
					$sql = generate_insert_invoice_line_item_sql($invoice_id, $milestone_id, $line_item_type);	
					$result = mysql_query($sql) or die(mysql_error());	
					if (!$result) return mysql_error();
					$new_line_item_id = mysql_insert_id();
					// Insert into invoice_rel_order table
					$sql = generate_insert_invoice_rel_order($invoice_id, $gen_order_id, 
														     $single_order_id, $new_line_item_id);	
					$result = mysql_query($sql) or die(mysql_error());	
					if (!$result) return mysql_error();	
					// Update Billed amount in invoice table
					$sql = generate_update_invoice_billed_amount($invoice_id);	
					$result = mysql_query($sql) or die(mysql_error());	
					if (!$result) return mysql_error();
				}
			}
		}		
	}
	else {
		if ($line_item_id == -1 ) { // new line item
			$milestone_id = NULL;
			$sql = generate_insert_invoice_line_item_sql($invoice_id, $milestone_id, $line_item_type);	
			$result = mysql_query($sql) or die(mysql_error());	
			if (!$result) return mysql_error();
		}
		else { // update line item
			$milestone_id = NULL;
			$sql = generate_update_invoice_line_item_sql($line_item_id);	
			$result = mysql_query($sql) or die(mysql_error());	
			if (!$result) return mysql_error();
		}
		$sql = generate_update_invoice_billed_amount($invoice_id);	
		$result = mysql_query($sql) or die(mysql_error());	
		if (!$result) return mysql_error();
	}	
	$state = state_CLOSEPOPUP;		
}
/*
	Get values from submitted form
*/
function post_values()
{
	global $glb_arr_values;

	$glb_arr_values['DESCRIPTION'] 	= isset($_POST['DESCRIPTION']) ? $_POST['DESCRIPTION'] : '';		
	$glb_arr_values['AMOUNT'] 	    = isset($_POST['AMOUNT']) ? $_POST['AMOUNT'] : '';	
	$glb_arr_values['DATE'] 	    = isset($_POST['DATE']) ? $_POST['DATE'] : '';			
}
/*
	Initialize form values
*/
function new_values()
{
	global $glb_arr_values;

	$glb_arr_values['DESCRIPTION'] 	= '';		
	$glb_arr_values['AMOUNT'] 	    = '';	
	$glb_arr_values['DATE'] 	    = '';			
}

/*
	Get record from database and populate global array of values
*/
function get_record()
{	
	global $glb_arr_values, $line_item_id;
	$sql = generate_select_invoice_line_item_sql($line_item_id);	
	$result = mysql_query($sql) or die(mysql_error());	
	if (!$result) return mysql_error();
	$row = mysql_fetch_array($result);
	
	// Populate values array
	$glb_arr_values['AMOUNT'] 		= mysql_decode($row['AMOUNT']);
	$glb_arr_values['DESCRIPTION'] 	= mysql_decode($row['DESCRIPTION']);
	$glb_arr_values['DATE'] 	    = mysql_decode($row['DATE']);
}
/*
	SQL for milestones 
*/
function generate_select_invoice_milestones_sql($invoice_id, $payor_id, $currency_id)
{
	$select_sql = "	SELECT DISTINCT 
					milestone.MILESTONE_ID, single_order.SINGLE_ORDER_ID, single_order.FRIENDLY_NAME, 
					single_order.CODE, single_order.CLIENT_ORDER_ID, gen_order.GEN_ORDER_ID,
					milestone.NAME, milestone.AMOUNT, milestone.DATE,
					IF (invoice_line_item.MILESTONE_ID IS NULL, 0, 1) AS IS_SELECTED					
					FROM 
					milestone INNER JOIN single_order ON milestone.SINGLE_ORDER_ID = single_order.SINGLE_ORDER_ID
							  INNER JOIN gen_order ON single_order.GEN_ORDER_ID = gen_order.GEN_ORDER_ID
							  INNER JOIN invoice ON invoice.PAYOR_PARTY_ID = 
							  IF(single_order.IS_BUYER_COMMISSIONER,  gen_order.BUYER_ID, single_order.SUPPLIER_ID)
							  LEFT JOIN invoice_line_item ON milestone.MILESTONE_ID = invoice_line_item.MILESTONE_ID
					WHERE 
					milestone.MILESTONE_TYPE = ".milestone_INCOMING_PAYMENT."  AND  invoice.PAYOR_PARTY_ID = ".$payor_id.
				  " AND invoice.INVOICE_ID = ".$invoice_id." AND gen_order.CURRENCY_ID = ".$currency_id." 
					AND invoice.STATE = 'ACTIVE' AND gen_order.STATE = 'ACTIVE' 
					AND single_order.STATE='ACTIVE' AND milestone.STATE='ACTIVE'
					AND gen_order.STATUS = 'Open' AND single_order.STATUS = 'Open' ";
	return $select_sql;
}
/*
	SQL to insert into invoice_line_item table
*/
function generate_insert_invoice_line_item_sql($invoice_id, $milestone_id, $line_item_type)
{
	global $glb_arr_values;
	$insert_sql = "INSERT INTO invoice_line_item (INVOICE_ID, MILESTONE_ID, TYPE, DESCRIPTION, AMOUNT, DATE   ) 
					VALUES  ( ".$invoice_id.", ".
								($milestone_id == NULL ? 'NULL' : $milestone_id).", ".
								$line_item_type.", '".
								mysql_encode($glb_arr_values['DESCRIPTION'])."', '".
								mysql_encode($glb_arr_values['AMOUNT'])."', '".
								mysql_encode(format_date($glb_arr_values['DATE']))."' ); ";
					
	return $insert_sql;	
}
/*
	SQL to insert into invoice_rel_order table based on the milestones selected
*/
function generate_insert_invoice_rel_order($invoice_id, $gen_order_id, 
										   $single_order_id, $invoice_line_item_id)
{
	$insert_sql = "INSERT INTO invoice_rel_order (INVOICE_ID, SINGLE_ORDER_ID, GEN_ORDER_ID, INVOICE_LINE_ITEM_ID ) 
					VALUES ( ".$invoice_id.", ".$single_order_id.", ".$gen_order_id.", ".$invoice_line_item_id." ); ";
	return $insert_sql;	
}
/*
*/
function generate_update_invoice_line_item_sql($line_item_id)
{
	global $glb_arr_values;
	$update_sql = " UPDATE invoice_line_item 
					SET
					DESCRIPTION = '" .mysql_encode( $glb_arr_values['DESCRIPTION'])."',
					AMOUNT = '" .mysql_encode( $glb_arr_values['AMOUNT'])."',
					DATE = '" .mysql_encode(format_date($glb_arr_values['DATE']))."'
					WHERE INVOICE_LINE_ITEM_ID = ".$line_item_id;
								
	return $update_sql;	
}
/*
*/
function generate_select_invoice_line_item_sql($invoice_line_item_id)
{
	$select_sql = " SELECT * 
					FROM invoice_line_item 
					WHERE INVOICE_LINE_ITEM_ID = ".$invoice_line_item_id . 
						 " AND TYPE <> 'Milestone';" ;
	return $select_sql;
}

/*
	Generate milestones block
*/
function show_milestones()
{
	global $glb_arr_values, $state, $invoice_id, $payor_id, $currency_id, $no_milestones;
 
 	// Get milestones from DB
	$sql = generate_select_invoice_milestones_sql($invoice_id, $payor_id, $currency_id);	
	$result = mysql_query($sql) or die(mysql_error());	

	if (mysql_num_rows($result) == 0 ){
		$markup =  '';		
	}
	else {
		$markup =  '<table>								
					<tr class="milestones_header"> 		
		            <td><input type="checkbox" id="checkAll" name="checkAll" onclick="setupCheckboxes(this.checked, this.id);"></td>															
					<td>Single Order</td>
					<td>Name</td>									
					<td>Amount</td>														
					<td>Date</td>';	
		$i=0;
		//	For each milestone checkbox, put in its value MILESTONE_ID, GEN_ORDER_ID, SINGLE_ORDER_ID comma seperated
		while ($row = mysql_fetch_array($result)) {
			$order_id    = $row['SINGLE_ORDER_ID'];
			$order_name = ($order_id == NULL) ? '':$row['FRIENDLY_NAME']."(".$row['CODE']."/".$row['CLIENT_ORDER_ID'].")";
			$name  		 = mysql_decode($row['NAME']);
			$amount  	 = mysql_decode($row['AMOUNT']) ;
			$date		 = format_date_reverse($row['DATE']);
			$chkboxValue = $row['MILESTONE_ID'].",".$row['GEN_ORDER_ID'].",".$row['SINGLE_ORDER_ID'].",".$amount;			
			$checked	 = ($row['IS_SELECTED']) ? ' checked="checked" disabled="disabled" ' : '';
			
			$invoice_info = '';
			$invoice_number = get_milestone_invoice_number($row['MILESTONE_ID']);
			if ( $invoice_number) {
				$invoice_info = 'This milestone belongs to Invoice#: '.$invoice_number;	
			}						
			$markup .= '<tr title="'.$invoice_info.'" class="milestones_line' .($i%2). '">'; 
			$markup .= '<td><input type="checkbox" value="'.$chkboxValue.'" id="milestone" name="milestone[]" onclick="setupCheckboxes(this.checked, this.id);"'. $checked .'></td>';						
			$markup .= '<td>'. $order_name . '</td>';						
			$markup .= '<td>'. $name . '</td>';						
			$markup .= '<td>'.$amount.'</td>';		
			$markup .= '<td>'.$date.'</td>';		
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
	Check if any milestones exist in this popup
*/
function do_milestones_exist()
{
	global $invoice_id, $payor_id, $currency_id;
	
	$sql 	   = generate_select_invoice_milestones_sql($invoice_id, $payor_id, $currency_id);	
	$result	   = mysql_query($sql) or die(mysql_error());	
	$existence = mysql_num_rows($result) > 0;
	mysql_free_result($result);	
	return $existence;
}
/*
	Generates standard form header for party
*/
function generate_form_header() 
{
	global $state, $invoice_id, $line_item_id;
	
	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_line_item\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"invoice_id\" name=\"invoice_id\" value=\"" . $invoice_id . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"line_item_id\" name=\"line_item_id\" value=\"" . $line_item_id . "\"/>\r\n";
	return $str_frm_header;
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Contrack - Edit Invoice Line Items</title>
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

function adjust_controls() {
	var LineItemType = document.getElementById("LINE_ITEM_TYPE");
	
	document.getElementById("LINE_ITEM_TYPE").disabled    = (state == stateEDIT); 	
	document.getElementById("Milestones").disabled        = (LineItemType.value  > 1);
	document.getElementById("AMOUNT").disabled 		  	  = (LineItemType.value == 1);
	document.getElementById("DESCRIPTION").disabled 	  = (LineItemType.value == 1);
	document.getElementById("DATE").disabled 	  		  = (LineItemType.value == 1);
	document.getElementById("TypeLabel").style.color   	    = (document.getElementById("LINE_ITEM_TYPE").disabled) 
															? "#666" : "#000" ;
	document.getElementById("TypeLabel").disabled   	    = (document.getElementById("LINE_ITEM_TYPE").disabled) 
															? "#666" : "#000" ;
															
	document.getElementById("MilestonesLabel").style.color  = (document.getElementById("Milestones").disabled) 
															? "#666" : "#000" ;
	document.getElementById("AmountLabel").style.color      = (document.getElementById("AMOUNT").disabled) 
															? "#666" : "#000" ;
	document.getElementById("DescriptionLabel").style.color = (document.getElementById("DESCRIPTION").disabled) 
															? "#666" : "#000" ;
	document.getElementById("DateLabel").style.color        = (document.getElementById("DATE").disabled) 
															? "#666" : "#000" ;
	
}

window.onload = function() { adjust_controls(); }


function addLineItem()
{
	var frm = document.forms["frm_line_item"];
	var LineItemType = document.getElementById("LINE_ITEM_TYPE");

	if (LineItemType.value  > 1) {
		if (validateNumeric('Amount',frm.AMOUNT.value)) {
			frm.state.value = statePROCESS;
			frm.submit();
		}
	}
	else {
		frm.state.value = statePROCESS;
		frm.submit();
	}	
}
/*
	Setup checkboxes on each click on any of the checkboxes including the main one.
	Note: Disabled checkboxes are for those contacts already selected for that order
*/
function setupCheckboxes (boolIsChecked, strCheckboxName) 
{   
	var milestonesChkboxes = document.getElementById("milestone");
	var mainChkbox 	       = document.getElementById("checkAll");
	
	// Is this the main checkbox?
	if (strCheckboxName == 'checkAll') {
		// Go through them and check/uncheck all
		if (milestonesChkboxes.length) {  // If length is defined that means we have more than 2 rows
			for (i=0; i < milestonesChkboxes.length; i++) {	
				if (!milestonesChkboxes[i].disabled) { 		// don't touch the disabled ones!
					milestonesChkboxes[i].checked = boolIsChecked;  
				}
			}
		}
		else {  // if length is undefined, we have only one row and one checkbox so set/unset it.
			if (!milestonesChkboxes.disabled) {   // only if it's *not* disabled
				milestonesChkboxes.checked = boolIsChecked;
			}
		}
	} // A regular checkbox has been checked?
	else if (strCheckboxName == 'milestone') {	
		// Has it just been unchecked?
		if(boolIsChecked == false) {
			mainChkbox.checked = false; 
		}
		else {
			// Are all of them checked?
			boolAllChecked = true;
			for (i=0; i < milestonesChkboxes.length; i++) {	
				if (milestonesChkboxes[i].checked == false) {
					boolAllChecked = false; 
					break;
				}				
			}
			mainChkbox.checked = boolAllChecked; 
		}
	}
}
</script>
<body class="body_popup">
<?php echo generate_form_header() ?>
<table class="popup">
	<tr><td colspan="2"><h4>Add/Edit Invoice Line Items</h4></td></tr>
	<tr><td><label id="TypeLabel" for="TypeLabel">Type:</label></td>
	<td><select name="LINE_ITEM_TYPE" id="LINE_ITEM_TYPE" onchange="adjust_controls()">
		<?=	populate_line_item_type_dropdown($line_item_type);	?>
		</select></td></tr>
	<tr><td><label id="MilestonesLabel" for="Milestones">Milestones:</label></td>
	<td>
	<span class="small_text">
	<?php
	if ($milestones_exist) {
		echo "The following milestones are those involving ".$payor_name." as payor, 
	    	  Organization as payee, and whose currency is ".$currency_desc;	
	}
	else {
		echo "No milestones available for ".$payor_name. " as payor, Organization as payee, 
			  and whose currency is ".$currency_desc;			
	}
	?>
	</span>
	</td>
	<tr><td colspan="2">
	<div id="Milestones" name="Milestones" class="milestones_select_div">
	<?= show_milestones(); ?>				
	</div>
	</td></tr>
	<tr><td><span id="AmountLabel">Amount:</span></td>
	<td>
	<?=	generate_text_input ("formtext_stdwidth", "AMOUNT", $glb_arr_values['AMOUNT']);	?>
	</td></tr>
	<tr><td><span id="DescriptionLabel">Description:</span></td>
	<td>
	<?=	generate_text_input ("formtext_longwidth", "DESCRIPTION", $glb_arr_values['DESCRIPTION']); ?>
	</td></tr>
	<tr><td><span id="DateLabel">Date:</span></td>
	<td>
	<?=	generate_text_input ("date_box", "DATE", format_date_reverse($glb_arr_values['DATE']));?>
	</td></tr>
	<tr><td colspan="2" align="right">
	<?php
		echo generate_button("button","Save & Close","addLineItem();");
		echo "&nbsp;";
		echo generate_button("button","Cancel","javascript:void window.close();");
	?>		  
	</td></tr>
</table>
</form>
</body>
</html>

