	
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
  	Incomplete:
	~~~~~~~~~~~
		Printing the invoice
		Invoice association logic

	
	Invoice page

	This page will handle creating/editing/sending/closing/crediting of invoices.
	The *page* has the following states: 	
		a) "NEW" to create a new invoice
		b) "EDIT" to edit an existing invoice
		c) "VIEW" to view an existing invoice
		d) "PROCESS". The form is in this state while it's being processed after any of the 
		following actions has been performed:
			- Edit   (Only allowed when invoice is in DRAFT status)
			- Save   (Only allowed when invoice is in DRAFT status) 
			- Send   (Only allowed when invoice is in DRAFT status)
			- Close  (Only allowed when invoice is in PENDING status)
			- Credit (Only allowed when invoice is in PENDING status)
			- Delete (Only allowed when invoice is in DRAFT status)
			- Print									
		The page will never be viewable in the "PROCESS" state. (Transient state)

	Page states are different yet linked to Invoice statuses. 
	An Invoice has one of the following states:
		- DRAFT
		- PENDING
		- CLOSED
		- CREDITED
	The following table illustrates the	different actions that are allowed on the invoice 							
	
	State transitions are as follows:
		Page State		Invoice Status		Allowed Actions ==> Resulting Invoice Status, Page State
		----------		-------------		-------------------------------------------------------
		NEW				NONE				SAVE   ==> DRAFT, VIEW
		NEW				NONE				CANCEL ==> NONE, history.back()
		VIEW			DRAFT				EDIT   ==> DRAFT, EDIT
		VIEW			DRAFT				SEND   ==> PENDING, VIEW		
		VIEW			DRAFT				DELETE ==> Invoice is deleted from the system. 
													   User is redirected to View All Invoices page	
		EDIT			DRAFT				SAVE   ==> DRAFT, VIEW
		EDIT			DRAFT				CANCEL ==> DRAFT, VIEW
		VIEW			PENDING				CREDIT ==> CREDITED, VIEW
		VIEW			PENDING				CLOSE  ==> CLOSED, VIEW
		VIEW			ANY STATE			PRINT  ==> ANY STATE, VIEW. Open new browser window with print format
		

	Notes: 	- EDIT and NEW are similar states except that in EDIT the controls
			  are pre-populated with the attributes of the entity being edited
			  whereas in NEW the controls are blank.			
			- In EDIT and VIEW state, the primary key exists (PARTY_ID), in NEW state 
			  it has the value -1.			
			- Deleting sub-entities (line items) works by resubmitting the form 
			  with the relevant opcode and sub-entity name
 			- "Print" and "Credit" Buttons are currently not shown until associated
		       business rules are defined.			  
******************************************************************************************/

require_once("db_utils.php");
require_once("general_utils.php");
require_once("invoice_utils.php");
require_once("patUser-2.2.3/prepend.php" );
require_once("permissions.php");

init_page();
process_state();	

/****************************************************
	Page Initialisation
	- Authenticate
	- Get all parameters passed to the page
	- Validate permissions for current user
	- Check for any errors passed to the page
	- Check page state and branch accordingly
****************************************************/
function init_page()
{
	global $user, $line_items_exist, $line_items_have_milestone;
	
	// Authenticate
	if (! ($user->isAuthenticated())){
		deny_access();
	}	
	get_page_params ();
	validate_permissions();
	check_error();
	$line_items_exist = do_line_items_exist();
	$line_items_have_milestone = is_any_line_item_milestone();	
}

/*
	Get key parameters passed to this page and format display string
*/
function get_page_params()
{
	global  $state, $invoice_status, $invoice_id, $invoice_action, $sub_entity_op, 
			$sub_entity_name, $sub_entity_id, $page_name;

	// Get key parameters passed to the page
	$state  	     = isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : state_VIEW);
	$invoice_id 	 = isset($_GET['invoice_id']) ? $_GET['invoice_id'] : (isset($_POST['INVOICE_ID']) ? $_POST['INVOICE_ID'] : -1);
	$invoice_action  = isset($_POST['invoice_action']) ? $_POST['invoice_action'] : -1;			
	if (($invoice_id == -1) && ($state != state_PROCESS)) {
		$state = state_NEW;
	}
	// Current sub entity is line items
	$sub_entity_op   = isset($_GET['sub_entity_op']) ? $_GET['sub_entity_op'] : (isset($_POST['sub_entity_op']) ? $_POST['sub_entity_op'] : -1);
	$sub_entity_name = isset($_GET['sub_entity_name']) ? $_GET['sub_entity_name'] : (isset($_POST['sub_entity_name']) ? $_POST['sub_entity_name'] : -1);
	$sub_entity_id 	 = isset($_GET['sub_entity_id']) ? $_GET['sub_entity_id'] : (isset($_POST['sub_entity_id']) ? $_POST['sub_entity_id'] : -1);
	// This could be the general way to redirect all pages that incorrect or missing params
	if ( ($invoice_id == -1) &&  ( ($state != state_NEW) && ($state != state_PROCESS) ) ){
		$location_url = "Location: search_invoice.php";  //redirect to view all invoices
		header($location_url);	
		exit;		
	}				
	// Set page name for use in fetching permissions from DB
	$page_name = "invoice";
}
/****************************************************
	Permissions and security functions
****************************************************/
/*
	~~
	~~ Move to permissions.php?
	~~ pass $page name and $user paramaters
	
	Validates that the user has the required permissions to view the page in this state
	- Get User permissions
	- Set global flags
	- Get minimum permissions required
	- Evaluate the user permissions vis-a-vis the required permissions	
*/
function validate_permissions()
{
	global $user,$page_name;
		
	$user_perms = get_user_permissions ($user, $page_name);
	//var_dump ($user_perms);
	//echo "<br>";
	set_user_perms($user_perms);	
	set_required_perms(is_associated());	
	
	if (! is_authorized()) {
		do_unauthorized();				
	}
}

/*
	~~~ INCOMPLETE ~~~
	Determine whether the currently logged in user is associated with the invoice
	being viewed.
	
	Association logic:
		Agent: Invoice belongs to a SO belonging to a GO featuring Agent |
		Buyer: Buyer is payor, or payor contact (or invoice belongs to a GO featuring this buyer?) |
		Supplier: Supplier is payor, or payor contact (or invoice belongs to a SO featuring this supplier?)
*/
function is_associated()
{
	global $user, $invoice_id;
	
	// Get associated party_id
	$logged_in_party_id = get_party_id_for_user($user->getSessionValue('userid'));
	
	/*	Get group id and based on it determine which function to call to get the 
		needed party id value to determine association.
	*/
	//$buyer_id = get_buyer_id_for_single_order($single_order_id);
	
	return true;
}

/*
	Based on state of page, go to appropriate function
*/
function process_state()
{
	global $state;
	// Check state
	if (empty($state)) { /* if $state is empty go to VIEW state*/
		view_form();
	} elseif ( $state == state_VIEW ) {
		view_form();
	} elseif (  $state == state_EDIT ) {
		edit_form();
	} elseif ( $state == state_PROCESS )  {
		process_form();
	} elseif ( $state == state_NEW )  {
		new_form();
	} else  { 		// might be redundant but just in case...
		view_form();
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

function view_form()
{
	init_form();
	get_record();
}

/*
	Intitializes form
*/	
function init_form() 
{
	global $glb_arr_values, $state, $invoice_id;
	
	if ( $state != state_NEW ) {
		$glb_arr_values['INVOICE_ID']   =  $invoice_id;
	} 
	else {   //NEW		
		new_values();
	}
}

/*
	
	Function: process_form()
	Desc:  
		This function is called upon submission of the form.
		If this is a sub entity operation (contact or attachment delete) perform it otherwise:
		Validates values posted.
		Checks whether this is a NEW state or an EDIT state and acts accordingly.
 	Params: 
    	None
   	Returns: 
		Nothing
*/	
function process_form() 
{
	global 	$glb_arr_values, $state, $invoice_id, $invoice_action, 
			$sub_entity_op, $sub_entity_name, $sub_entity_id;
		
	// Are we deleting the invoice
	if ($invoice_action == opcode_DELETE) {
		delete_invoice();
		$location_url = "Location: search_invoice.php";  //redirect to view all invoices
		header($location_url);	
		exit;		
	}	
	// Get values from submitted form	
	if ($invoice_action == opcode_SAVE) post_values();

	// If we're deleting a sub-entity
	if ( $sub_entity_op == opcode_DELETE ) {		
		if ( strcasecmp ('LINE_ITEM', $sub_entity_name ) == 0) {		
			delete_invoice_line_item($invoice_id, $sub_entity_id);
			// Update Billed amount in invoice table
			$sql = generate_update_invoice_billed_amount($invoice_id);	
			$result = mysql_query($sql) or die(mysql_error());	
			if (!$result) return mysql_error();
		} 
		// Redirect while setting state back to "EDIT" after having deleted successfully
		$location_url = "Location: invoice.php?invoice_id=".$invoice_id."&state=".state_EDIT;
		header($location_url);	
		exit;		
	}
	else {
		if ($invoice_action == opcode_SAVE) {  // Are we saving
			if ( validate_form() ) {		   // and validated	
				if ( -1 == $glb_arr_values['INVOICE_ID'] ) { // Creating New 
					$sql = generate_insert_invoice_sql() ;
					//echo "SQL Generated: " . $sql . "<br>";			
					$result = mysql_query($sql) or die(mysql_error());
					if (!$result) return mysql_error();
					$glb_arr_values['INVOICE_ID'] = mysql_insert_id();
					$invoice_id = $glb_arr_values['INVOICE_ID'];				
				} else {  								     // Or just Editing 
					$sql = generate_update_invoice_sql();	
					//echo "SQL Generated: " . $sql . "<br>";	
					$result = mysql_query($sql) or die(mysql_error());
					if (!$result) return mysql_error();						
				}
				// Redirect while setting state back to "VIEW" after having saved successfully
				$location_url = "Location: invoice.php?invoice_id=".$glb_arr_values['INVOICE_ID']."&state=".state_VIEW;
				header($location_url);	
				exit;		
			} 
			else {  // validation unsuccessful
				if ( -1 != $glb_arr_values['INVOICE_ID'] ) {	// Go back to EDIT state			
					$state = state_EDIT;	
					$glb_arr_values['DATE_CREATED'] = get_date_created_for_entity(entity_INVOICE, $glb_arr_values['INVOICE_ID']);		
				} else {										// Go back to NEW state
					$state = state_NEW;
				}
			}
		}
		else {  // opcode != Save
			$sql = generate_update_invoice_status_sql($invoice_id);
			$result = mysql_query($sql) or die(mysql_error());
			if (!$result) return mysql_error();	
			// Redirect while setting state back to "VIEW" after having updated the status successfully
			$location_url = "Location: invoice.php?invoice_id=".$invoice_id."&state=".state_VIEW;
			header($location_url);	
			exit;							
		}		
	} // end big else
}

/*
	Validate form values
*/	
function validate_form() 
{
	global $glb_arr_values, $errors;
		
	// Verify that currency had been selected
	//if ($glb_arr_values['CURRENCY_ID'] == 0) $errors['CURRENCY_ID'] = 'Please select a currency for this invoice';	
	
	//if ($glb_arr_values['PAYOR_PARTY_ID'] == 0) $errors['PAYOR_PARTY_ID'] = 'Please select a payor for this invoice';	

	if (count($errors) > 0)	{
		$errors['error'] = "There was a problem with the information entered. Please review the form and make necessary corrections.";
	}
	return count($errors) == 0;	
}	

/*
	Populate global values array with defaults when the page is in 'NEW' state
*/
function new_values()
{
	global $glb_arr_values;
			
	$glb_arr_values['INVOICE_ID']		= -1;
	$glb_arr_values['NUMBER'] 			= 'New';
	$glb_arr_values['STATUS_ID']		= 3;
	$glb_arr_values['STATUS']			= 'Draft';
	$glb_arr_values['CURRENCY_ID']		= -1;
	$glb_arr_values['DATE']				='';
	$glb_arr_values['MESSAGE'] 			= '';
	$glb_arr_values['DESCRIPTION']   	= '';
	$glb_arr_values['PAYOR_PARTY_ID'] 	= -1;
	$glb_arr_values['PAYOR_CONTACT_ID'] = -1;	
	$glb_arr_values['PAYEE_CONTACT_ID']	= -1;  
	$glb_arr_values['PAYMENT_TERMS']	= '';		
}

/*
	Get values from submitted form and populate global values array
*/	
function post_values() 
{	
	global $glb_arr_values, $invoice_id;
	
	$glb_arr_values['INVOICE_ID'] 		= $invoice_id ;	
	$glb_arr_values['NUMBER']			= isset($_POST['NUMBER']) ? $_POST['NUMBER'] : "";		
	$glb_arr_values['STATUS_ID']		= isset($_POST['STATUS_ID']) ? $_POST['STATUS_ID'] : 3;
	$glb_arr_values['STATUS']			= isset($_POST['STATUS']) ? $_POST['STATUS'] : 'Draft';
	$glb_arr_values['CURRENCY_ID'] 		= isset($_POST['CURRENCY_ID']) ? $_POST['CURRENCY_ID'] : '';
	$glb_arr_values['DATE'] 			= isset($_POST['DATE']) ? $_POST['DATE'] : '';
	$glb_arr_values['MESSAGE'] 	    	= isset($_POST['MESSAGE']) ? $_POST['MESSAGE'] : '';	
	$glb_arr_values['DESCRIPTION'] 		= isset($_POST['DESCRIPTION']) ? $_POST['DESCRIPTION'] : '';
	$glb_arr_values['PAYOR_PARTY_ID']	= isset($_POST['PAYOR_PARTY_ID']) ? $_POST['PAYOR_PARTY_ID'] : '';	
	$glb_arr_values['PAYOR_CONTACT_ID'] = isset($_POST['PAYOR_CONTACT_ID']) ? $_POST['PAYOR_CONTACT_ID'] : '';
	$glb_arr_values['PAYEE_CONTACT_ID'] = isset($_POST['PAYEE_CONTACT_ID']) ? $_POST['PAYEE_CONTACT_ID'] : '';
	$glb_arr_values['PAYMENT_TERMS'] 	= isset($_POST['PAYMENT_TERMS']) ? $_POST['PAYMENT_TERMS'] : '';
}

/*
	Function: get_record()
	Desc:  
		If invoice_id < 1 then this is a new record. Populate with defaults.
		If invoice_id > 1 get record from database and populate values global array
 	Params: 
    	None
   	Returns: 
		Nothing	
*/
function get_record()
{
	global $glb_arr_values, $invoice_id;
	
	if (1 > $glb_arr_values['INVOICE_ID']) {
		new_values();
		return;
	}
	
	$sql = generate_select_invoice_sql($invoice_id);	
	$result = mysql_query($sql) or die(mysql_error());	
	if (!$result) return mysql_error();
	$row = mysql_fetch_array($result);
			
	// Populate values array
	$glb_arr_values['NUMBER'] 			  = mysql_decode($row['NUMBER']);
	$glb_arr_values['STATUS_ID']		  = mysql_decode($row['STATUS_ID']);
	$glb_arr_values['STATUS']			  = mysql_decode($row['STATUS']);
	$glb_arr_values['CURRENCY_ID'] 		  = mysql_decode($row['CURRENCY_ID']);
	$glb_arr_values['CURRENCY_NAME'] 	  = mysql_decode($row['CURRENCY_NAME']);
	$glb_arr_values['CURRENCY_DESC'] 	  = mysql_decode($row['CURRENCY_DESC']);
	$glb_arr_values['DATE'] 			  = mysql_decode($row['DATE'] );
	$glb_arr_values['MESSAGE'] 			  = mysql_decode($row['MESSAGE']);
	$glb_arr_values['DESCRIPTION'] 		  = mysql_decode($row['DESCRIPTION'] );
	$glb_arr_values['PAYOR_PARTY_ID']	  = mysql_decode($row['PAYOR_PARTY_ID']);
	$glb_arr_values['PAYOR_NAME']	 	  = mysql_decode($row['PAYOR_NAME']);	
	$glb_arr_values['PAYOR_TYPE']	 	  = mysql_decode($row['PAYOR_TYPE']);	
	$glb_arr_values['PAYOR_CONTACT_ID']   = mysql_decode($row['PAYOR_CONTACT_ID']);
	$glb_arr_values['PAYOR_CONTACT_NAME'] = mysql_decode($row['PAYOR_CONTACT_NAME']);
	$glb_arr_values['PAYEE_CONTACT_ID']   = mysql_decode($row['PAYEE_CONTACT_ID']);	
	$glb_arr_values['PAYEE_CONTACT_NAME'] = mysql_decode($row['PAYEE_CONTACT_NAME']);
	$glb_arr_values['PAYMENT_TERMS'] 	  = mysql_decode($row['PAYMENT_TERMS']);
	
	mysql_free_result($result);			
}

/**************************************** 
	SQL Generation functions
*****************************************/

/*
	Generates insert SQL for Invoice
*/	
function generate_insert_invoice_sql()
{
	global $glb_arr_values;
  
	$insert_sql = "INSERT INTO invoice 
				 ( STATE , NUMBER , STATUS , DATE , MESSAGE , DESCRIPTION , PAYOR_PARTY_ID , 
				  PAYOR_CONTACT_ID , PAYEE_CONTACT_ID , PAYMENT_TERMS , CURRENCY_ID , DATE_CREATED ) 
				  VALUES 
				  ('ACTIVE', 
				  '".mysql_encode(get_new_code('INVOICE'))."',
				  'Draft',
				 '".format_date($glb_arr_values['DATE'])."',
				 '".mysql_encode($glb_arr_values['MESSAGE'])."',
				 '".mysql_encode($glb_arr_values['DESCRIPTION'])."',
				  ".$glb_arr_values['PAYOR_PARTY_ID'].",
				  ".$glb_arr_values['PAYOR_CONTACT_ID'].",										
				  ".$glb_arr_values['PAYEE_CONTACT_ID'].",
				  '".mysql_encode($glb_arr_values['PAYMENT_TERMS'])."',
				  ".$glb_arr_values['CURRENCY_ID'].",				 				
				   NOW())";	   
					   
	return $insert_sql;
}

/*
	Generates update SQL for Invoice
	No need to update the STATUS column since it's updated based on the actions only
*/	
function generate_update_invoice_sql()
{
	global $glb_arr_values;
	//STATUS = " .mysql_encode( $glb_arr_values['STATUS']).",
	$update_sql =  "UPDATE invoice SET														
					
					DATE = '". format_date($glb_arr_values['DATE'])."',
					MESSAGE = '" .mysql_encode( $glb_arr_values['MESSAGE'])."',					
					DESCRIPTION = '" .mysql_encode( $glb_arr_values['DESCRIPTION'])."',										
					PAYOR_PARTY_ID = " .$glb_arr_values['PAYOR_PARTY_ID'].",
					PAYOR_CONTACT_ID = ".$glb_arr_values['PAYOR_CONTACT_ID'].",					
					PAYEE_CONTACT_ID = " .$glb_arr_values['PAYEE_CONTACT_ID'].",
					PAYMENT_TERMS = '" .mysql_encode( $glb_arr_values['PAYMENT_TERMS'])."',					
					CURRENCY_ID = " .$glb_arr_values['CURRENCY_ID']."

					WHERE INVOICE_ID=".$glb_arr_values['INVOICE_ID'];				
	return $update_sql;
}

/*
	Generates update status SQL for the Invoice based on action performed
*/
function generate_update_invoice_status_sql ($invoice_id)
{
	global $invoice_action, $glb_arr_values;

	switch ($invoice_action){
		case opcode_SEND:
		$new_status = "Pending";		
		break;
		case opcode_CREDIT:
		$new_status = "Credited";		
		break;
		case opcode_CLOSE:
		$new_status = "Closed";		
		break;
		default:
		$new_status =  $glb_arr_values['STATUS']; // New Status = Old status 
	}
	$update_sql =  "UPDATE invoice SET															
					STATUS = '".$new_status."'
					WHERE INVOICE_ID=".$invoice_id;				
	return $update_sql;					
}

/*
	Cannot send multiple SQL statements in one batch to MySQL.
	This is a documented PHP/MySQL bug
*/
function delete_invoice()
{
	global $invoice_id;
	
	$sql = "DELETE FROM invoice WHERE INVOICE_ID =". $invoice_id . "; "; 
	$result = mysql_query($sql) or die(mysql_error());		
	$sql = "DELETE FROM invoice_line_item WHERE INVOICE_ID =". $invoice_id . "; "; 
	$result = mysql_query($sql) or die(mysql_error());		
	$sql = "DELETE FROM invoice_rel_order WHERE INVOICE_ID =". $invoice_id . "; "; 
	$result = mysql_query($sql) or die(mysql_error());		
	$sql = "DELETE FROM invoice_term WHERE INVOICE_ID =". $invoice_id . "; "; 
	$result = mysql_query($sql) or die(mysql_error());		
	$sql = "DELETE FROM invoice_history WHERE INVOICE_ID =". $invoice_id . "; "; 
	$result = mysql_query($sql) or die(mysql_error());		
	$sql = "DELETE FROM payment WHERE INVOICE_ID =". $invoice_id . "; "; 
	$result = mysql_query($sql) or die(mysql_error());		
}

/*
	Generates select invoice line items
*/	
function generate_select_invoice_line_items_sql () 
{
	global $invoice_id;
	$select_sql =  "SELECT lineitem.INVOICE_LINE_ITEM_ID, lineitem.INVOICE_ID, lineitem.TYPE,
					lineitem.TYPE+0 AS TYPE_ID, 
					single_order.SINGLE_ORDER_ID, single_order.FRIENDLY_NAME, single_order.CODE, 
					single_order.CLIENT_ORDER_ID, 
					IF( lineitem.TYPE='Milestone', milestone.DESCRIPTION, lineitem.DESCRIPTION) AS DESCRIPTION,					
					IF( lineitem.TYPE='Milestone', milestone.AMOUNT, lineitem.AMOUNT) AS AMOUNT,
					IF( lineitem.TYPE='Milestone', milestone.DATE, lineitem.DATE) AS DATE					
					FROM invoice_line_item AS lineitem 
						LEFT JOIN milestone ON lineitem.MILESTONE_ID = milestone.MILESTONE_ID
						LEFT JOIN single_order ON milestone.SINGLE_ORDER_ID = single_order.SINGLE_ORDER_ID					
					WHERE INVOICE_ID = ".$invoice_id." AND lineitem.STATE='ACTIVE' 
					ORDER BY DATE";
	return $select_sql;
}

/*
	Delete invoice line item from inoice_line_item table and from invoice_rel_order table
*/
function delete_invoice_line_item($invoice_id, $line_item_id)
{
	if ($line_item_id > 0 ) {
		$sql = "DELETE FROM invoice_line_item 
				WHERE INVOICE_LINE_ITEM_ID =". $line_item_id." AND INVOICE_ID = ".$invoice_id; 
		$result = mysql_query($sql) or die(mysql_error());	

		$sql = "DELETE FROM invoice_rel_order
				WHERE INVOICE_LINE_ITEM_ID =". $line_item_id." AND INVOICE_ID = ".$invoice_id; 
		$result = mysql_query($sql) or die(mysql_error());	
	}
}

/*
	Generates select milestones line items SQL
*/	
function generate_select_milestones_line_items_sql () 
{
	global $invoice_id;
	$select_sql =  "SELECT lineitem.INVOICE_LINE_ITEM_ID				
					FROM invoice_line_item AS lineitem 											
					WHERE lineitem.INVOICE_ID = ".$invoice_id." AND lineitem.STATE='ACTIVE' AND 
						  lineitem.TYPE = 'Milestone' ";
	return $select_sql;
}

/*
	Get all contacts for all buyers and suppliers from database and populate array
	to be used to populate dropdown values
*/
function populate_payor_contacts_array()
{
	global $arr_payors_contacts;
	
	$sql = "SELECT contact.PARTY_ID, contact.NAME, contact.EMPLOYER_ID 
			FROM party as contact INNER JOIN party AS employer ON contact.EMPLOYER_ID = employer.PARTY_ID
			WHERE (contact.EMPLOYER_ID <> 0) AND (employer.TYPE = 'BUYER' OR employer.TYPE='SUPPLIER')
			ORDER BY contact.EMPLOYER_ID, contact.NAME";
	$result = mysql_query($sql) or die(mysql_error());
	if (mysql_num_rows($result)) {
		$i = 0;
		while ($row = mysql_fetch_array($result)) {
			$arr_payors_contacts[$i]['PARTY_ID'] 	= $row['PARTY_ID'];
			$arr_payors_contacts[$i]['NAME'] 		= $row['NAME'];
			$arr_payors_contacts[$i]['EMPLOYER_ID'] = $row['EMPLOYER_ID'];
			$i++;
		}
	}	
	mysql_free_result($result);		
}
/**************************************** 
	XHTML Generation functions
*****************************************/
/*
	Based on page state and invoice status, show appropriate buttons
	Notes: "Print" and "Credit" Buttons are currently not shown until associated
	business rules are defined.
*/
function show_invoice_buttons()
{
	global $glb_arr_values, $state, $allow_modify_all, $allow_modify_own;
	
	$int_invoice_status = $glb_arr_values['STATUS_ID'];

	if ( ($state == state_VIEW) && ($int_invoice_status == invoice_DRAFT) )	 {
		if ($allow_modify_all || $allow_modify_own) {
			echo generate_button('button','Edit','entityEdit()'); 
			echo '&nbsp';
			echo generate_button('button','Send','entityDoAction('.opcode_SEND.')'); 
			echo '&nbsp';
			echo generate_button('button','Delete','entityDoAction('.opcode_DELETE.')'); 
			//echo '&nbsp';
			//echo generate_button('button','Print','entityDoAction('.opcode_PRINT.')'); 						
		}
	}						
	elseif ((($state == state_EDIT) && ($int_invoice_status == invoice_DRAFT)) || ($state == state_NEW))  { 																	
		if ($allow_modify_all ) {
			echo generate_button('button','Save','entityDoAction('.opcode_SAVE.')');
			echo '&nbsp';
			echo generate_button('button','Cancel','entityCancel()');										
		}
	}
	elseif ( ($state == state_VIEW) && ($int_invoice_status == invoice_PENDING) ) {
		if ($allow_modify_all || $allow_modify_own) {
			//echo generate_button('button','Credit','entityDoAction('.opcode_CREDIT.')'); 
			//echo '&nbsp';
			echo generate_button('button','Close','entityDoAction('.opcode_CLOSE.')'); 
			//echo '&nbsp';
			//echo generate_button('button','Print','entityDoAction('.opcode_PRINT.')'); 	
		}
	}
	else {
		//echo generate_button('button','Print','entityDoAction('.opcode_PRINT.')'); 
	}
}
/*
	Generate line items block
*/
function show_line_items()
{
	global $glb_arr_values, $state;
 
 	// Get line items from DB
	$sql = generate_select_invoice_line_items_sql();	
	$result = mysql_query($sql) or die(mysql_error());	
	
	// Contacts Header
	$markup = "<tr><td width=\"15%\" valign=\"top\"><b>Invoice Details:</b></td>";									
	
	// If there are no line items AND we're not in EDIT state
	if ( (! mysql_num_rows($result)) && ($state != state_EDIT) )  {
		$markup .= "<td colspan =\"3\"><i>No Line Items</td></i></tr>\r\n";	
	} else	{				
		$markup .= "<td colspan =\"3\">
					<table>								
					<tr class=\"lineitems_header\"> ";
		// Are we in EDIT ?			
		if ($state == state_EDIT) {       
		$markup .= "<td class=\"small_text\" width=\"5%\" align=\"center\"><a href=\"javascript:
							void open_popup('invoice_line_item_popup.php?invoice_id=".$glb_arr_values['INVOICE_ID'].
							"&state=".state_NEW."&line_item_type=".line_item_MILESTONE.									 
							"&payor_id=".$glb_arr_values['PAYOR_PARTY_ID'].
							"&currency_id=".$glb_arr_values['CURRENCY_ID']."',									 
							'invoiceLineItem','menubar=no,toolbar=no,scrollbars=no',360,640);\">
							<img src=\"images/b.add.png\" alt=\"Add\" title=\"Add\"/></a></td>\r\n" ;
		}
		$markup .=	"<td>Type</td>
					<td>Single Order</td>
					<td>Description</td>																		
					<td>Amount</td>
					<td>Date</td>" ;	
		$total_amount = 0.00;
		$i=0;
		while ($row = mysql_fetch_array($result)) {
			$pk    		       = $row['INVOICE_LINE_ITEM_ID'];
			$line_item_type_id = $row['TYPE_ID'];
			$order_id          = $row['SINGLE_ORDER_ID'];
			$order_name = ($order_id == NULL) ? '' : $row['FRIENDLY_NAME']."(".$row['CODE']."/".$row['CLIENT_ORDER_ID'].")";			
			$markup .= "<tr class=\"lineitems_line" .($i%2). "\">"; 			
			if ($state == state_EDIT ) {
				$markup .= "<td class=\"small_text\" align=\"center\">";
				if ($line_item_type_id != line_item_MILESTONE) { 
					$markup .= "<a href=\"javascript:
								void open_popup('invoice_line_item_popup.php?invoice_id=".
								$glb_arr_values['INVOICE_ID']."&state=".state_EDIT."&line_item_id=".$pk.
								"&line_item_type=".$line_item_type_id."&payor_id=".$glb_arr_values['PAYOR_PARTY_ID'].
								"&currency_id=".$glb_arr_values['CURRENCY_ID'] ."',
								'invoiceLineItem','menubar=no,toolbar=no,scrollbars=no',360,640);\">
								<img src=\"images/b.edit.png\" alt=\"Edit\" title=\"Edit\"/></a>";
				}
				$markup .= "<a href=\"javascript:void subEntityDelete('LINE_ITEM',". $pk .");\">
							<img src=\"images/b.delete.png\" alt=\"Delete\" title=\"Delete\"/></a></td>\r\n";	
			}
			$markup .= "<td>". $row['TYPE'] . "</td>";	
			$markup .= "<td>";
			$markup .= ($order_id == NULL) ? "N/A" : "<a href=\"single_order_milestones.php?single_order_id=".$order_id."\">".$order_name."</a>";								
			$markup .= "</td>";
			$markup .= "<td>". $row['DESCRIPTION']  . "</td>";									
			$markup .= "<td>". $glb_arr_values['CURRENCY_NAME']. ' '. number_format($row['AMOUNT'], NUM_DEC_PLACES, '.',',') . "</td>";									
			$markup .= "<td>". format_date_reverse($row['DATE'])  . "</td></tr>";
			$total_amount +=  $row['AMOUNT'];									
			++$i;
		}  // end while
		if (mysql_num_rows($result)) {  
			$colspan = ($state == state_VIEW ? 3 : 4 );
			$markup .= '<tr><td colspan="'.$colspan.'"></td><td class="total_amount">'.$glb_arr_values['CURRENCY_NAME'].' '. 
						number_format($total_amount, NUM_DEC_PLACES, '.', ',').'</td></tr>';
		}
		// Lineitems table footer
		$markup .= "</table></td></tr>\r\n" ;
		mysql_free_result($result);		
	} // end else
	
	return $markup;
}

/*
	Check if any line items exist for this invoice
*/
function do_line_items_exist()
{
	$sql 	   = generate_select_invoice_line_items_sql();	
	$result	   = mysql_query($sql) or die(mysql_error());	
	$existence = mysql_num_rows($result) > 0 ;
	mysql_free_result($result);	
	return $existence;
}


/*
	Check if any line items of this invoice are of type milestones
*/
function is_any_line_item_milestone()
{
	$existence = false;
	if (do_line_items_exist()) {
		$sql 	   = generate_select_milestones_line_items_sql();	
		$result	   = mysql_query($sql) or die(mysql_error());	
		$existence = mysql_num_rows($result) > 0 ;
		mysql_free_result($result);	
	}
	return $existence;
}
/*
	Generates standard form header for invoice
*/
function generate_form_header() 
{
	global $glb_arr_values, $state, $invoice_action, $sub_entity_op, $sub_entity_name, $sub_entity_id;
	
	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_invoice\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"INVOICE_ID\" name=\"INVOICE_ID\" value=\"" . $glb_arr_values["INVOICE_ID"] . "\"/>\r\n";			
	$str_frm_header .= "<input type=\"hidden\" id=\"invoice_action\" name=\"invoice_action\" value=\"".$invoice_action."\"/>\r\n";			
	$str_frm_header .= "<input type=\"hidden\" id=\"NUMBER\" name=\"NUMBER\" value=\"" . $glb_arr_values["NUMBER"] . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_op\" name=\"sub_entity_op\" value=\"" . $sub_entity_op . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_name\" name=\"sub_entity_name\" value=\"" . $sub_entity_name . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_id\" name=\"sub_entity_id\" value=\"" . $sub_entity_id . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"CURRENCY_ID_HIDDEN\" name=\"CURRENCY_ID_HIDDEN\" value=\"" . $glb_arr_values["CURRENCY_ID"] . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"PAYOR_PARTY_ID_HIDDEN\" name=\"PAYOR_PARTY_ID_HIDDEN\" value=\"" . $glb_arr_values["PAYOR_PARTY_ID"] . "\"/>\r\n";
	return $str_frm_header;
}
/*
	Generate the javascript code fragment which declares and populates the payors' contacts array
*/
function generate_js_payors_contacts_arr()
{
	global $arr_payors_contacts;
	
	populate_payor_contacts_array();
	
	$js_arr = '';
	if (!empty($arr_payors_contacts)) {
		foreach ($arr_payors_contacts as $row_id => $party_values) {		
			$js_arr .= 'arr_payors_contacts['.$row_id.'] = new Array();'."\r\n" ;
			foreach ($party_values as $column_id => $value) {			
				$js_arr .= 'arr_payors_contacts['.$row_id.']["'.$column_id.'"] =';
				if ($column_id == 'NAME'){
					$js_arr .= '"'.$value.'";'."\r\n" ;
				}
				else {
					$js_arr .= $value.';'."\r\n" ;
				}
			}
		}
	}	
	return $js_arr;
}
// Set page title and include body header
if ($state != state_NEW ) { 	
	$page_title = $glb_arr_values['NUMBER'] . " :: Profile"; 

}
else {
	$page_title = "Invoice :: New";
}

require_once("body_header.php"); 
?>

<!-- JavaScript Section -->
<script type="text/javascript"><!--

var lineItemsExist = <?= $line_items_exist ? 1 : 0 ?>;
var lineItemsHaveMilestone = <?= $line_items_have_milestone ? 1 : 0 ?>;
var invoiceCurrencyID = <?= $glb_arr_values['CURRENCY_ID'] ?>;
var payorPartyID = <?= $glb_arr_values['PAYOR_PARTY_ID'] ?>;
var arr_payors_contacts = new Array();
// Initialize and populate array
<?= generate_js_payors_contacts_arr(); ?>

function currencyChange()
{
	if (lineItemsExist) {		
		alert ("This invoice contains line items using this currency.\nPlease remove the line items before changing the currency.");
		document.frm_invoice.CURRENCY_ID.value = invoiceCurrencyID;
		return;
	}	
}

function hasMilestones()
{
	if (lineItemsHaveMilestone) {		
		alert ("This invoice contains line items as milestones associated with this payor.\nPlease remove those line items before changing the payor.");
		document.frm_invoice.PAYOR_PARTY_ID.value = payorPartyID;
		return true;
	}
	else {
		return false;
	}	
}
/*
	Note: 	There is hack in this function to add options to the contacts dropdowns.		  	
			The method, HTMLSelectElement.add(), is a Document Object Model (DOM) 
			method. It expects two arguments. The first is a reference to the new 
			HTMLOptionElement to insert, while the second is where to insert it.
			According to the DOM 1 HTML Specification, the second argument should 
			either be a reference to the element that the new OPTION is inserted 
			before, or null for the end of the list. According to Microsoft, it is the
			*index* of the element that the new OPTION will be inserted before. The two 
			are completely incompatible (one is an object, the other is a number).
			The only way around it -I think- is to catch the exception that will be thrown 
			when the method is called with the wrong type of argument and act accordingly. 
*/
function populateDropDown(rootDropdown, dropdownToPopulate, selectedPayorContactID)
{
	var payorContactOption;	

	if (hasMilestones()) return;
	// Empty the dropdown to be populated of any choices
	for (var i = dropdownToPopulate.options.length; i >= 0; i--) {
		dropdownToPopulate.options[i] = null;
	}
	// Add Default Choice in Payor Contacts dropdown 
  	payorContactOption = document.createElement("option") ;
	if ( payorContactOption ) {
		// Once the option has been created, set the display text for it
		payorContactOption.text = 'Select...';
		payorContactOption.value = 0 ;		
		// Attempt to use Microsoft's incorrect method to add the
		// element to the end of the list
		try {
			dropdownToPopulate.add( payorContactOption, 1 );
		} 
		catch( e ) { // If it fails, use the correct DOM approach
			dropdownToPopulate.add( payorContactOption, null );
		}       
     }
	// Now loop through the array of contacts
	// Any contact containing the same employer ID as the party ID of the root DD is added to the second dropdown box	
	for (i = 0 ; i < arr_payors_contacts.length  ; i++ ) {
		if ( arr_payors_contacts[i]["EMPLOYER_ID"] == rootDropdown.value ) {
			payorContactOption 		 = document.createElement("option") ;
			payorContactOption.value = arr_payors_contacts[i]["PARTY_ID"] ;
			payorContactOption.text  = arr_payors_contacts[i]["NAME"] ;
			try {
				dropdownToPopulate.add( payorContactOption, i );
			} catch( e ) { // If it fails, use the correct DOM approach
				dropdownToPopulate.add( payorContactOption, null );
			}       
		}
	}
	// Set the default value if any
	for (i = 0; i < dropdownToPopulate.options.length; i++) {
		if (dropdownToPopulate.options[i].value == selectedPayorContactID) {
			dropdownToPopulate.options[i].selected = true;
		}
	}
}

function entityDoAction(intOpcode) 
{
	var frm = document.frm_invoice;		
	
	switch (intOpcode) {
		case opcodeDELETE:
		strAction = "Delete";
		break;
		case opcodeSAVE:
		strAction = "Save";		
		if (frm.DATE.value.length < 1) {
			alert ("You must enter a date for this Invoice");
			return;
		}
		if (frm.PAYOR_PARTY_ID.value <= 0 ) {
			alert ("You must associate a payor with this Invoice");
			return;
		}
		break;
		case opcodeSEND:
		strAction = "Send";	
		if (frm.CURRENCY_ID_HIDDEN.value <= 0) {
			alert ("You must associate a currency with this Invoice");
			return;
		}
		if (frm.PAYOR_PARTY_ID_HIDDEN.value <= 0 ) {
			alert ("You must associate a payor with this Invoice");
			return;
		}
		if (!lineItemsExist) {
			alert ("Please associate a value with this invoice by filling out the Invoice Details");
			return;
		}
		break;
		case opcodeCREDIT:
		strAction = "Credit";
		break;
		case opcodeCLOSE:
		strAction = "Close";
		break;
		case opcodePRINT:
		strAction = "Print";
		break;
		default:   // do nothing
		return;
	}	
	if (confirm("Are you sure you want to " + strAction + " this invoice?")) {		
		frm.state.value = statePROCESS;
		frm.invoice_action.value = intOpcode; 
		frm.submit();	
	}
}

function entityEdit() 
{
	frm = document.frm_invoice;		
	frm.state.value = stateEDIT;//stateEDIT
	frm.submit();	
}
/*
	history.go(-) and history.back() don't work in the following sequence:
	1- Save record -> you're back in View mode
	2- Click Edit -> you're in Edit mode
	3- Click Cancel to go back to View mode. This doesn't work because all the form 
	   information is lost. We need to resubmit.
*/
function entityCancel() 
{	
	frm = document.frm_invoice;	
	if (frm.INVOICE_ID.value == -1) {
		//frm.state.value = stateNEW;
		history.back();
	}
	else {
		frm.state.value = stateVIEW;
		frm.submit();
	}	
}

function subEntityDelete(strName, intID) 
{
	if (confirm ('Are you sure you want to delete this record?') ) {
		frm = document.frm_invoice;		
		frm.state.value			  = statePROCESS;
		frm.sub_entity_op.value   = opcodeDELETE;
		frm.sub_entity_name.value = strName;
		frm.sub_entity_id.value   = intID;		
		frm.submit();	
	}
}

function resubmitForm() 
{
	var frm = document.frm_invoice;		
	frm.state.value	= stateEDIT;	
	setTimeout("submitForm();",1000);	
}

function submitForm()
{
	frm = document.frm_invoice;		
	frm.submit();
}
--></script>
<table border="0"  cellpadding="0" cellspacing="0">
	<tr>
		<td colspan="3">
			<h4>
			<?php 
			if ($state != state_NEW ) { 					
				echo "INVOICE :: ". $glb_arr_values["NUMBER"]; 
			} 
			else {
				echo "INVOICE :: New"; 
			}
			?>
			</h4>
		</td>
	</tr>
	
	<!-- code to display informational/error message goes here -->
	
	<tr>  <!-- Tabs -->
		<td class = "tabs" colspan="3">
			<table border="0" cellspacing="0"> 
				<tr> 
				<?php 
				/*	Tab Generation 
					Pass the ordinal of the tab to be activated
				*/												
				echo generate_invoice_tabs(1, $invoice_id, $state);																
				?>
				</tr>
			</table> 					
		</td>
	</tr> <!-- End of Tabs -->			
	<tr>
		<td colspan = "3"> 
			<table border="0" class="tab_content">	
				<tr>
				<td colspan="4">
					<table border="0">
						<tr><td>
						<?php if (isset($errors['error'])) echo "<em class=\"error_block\">" . $errors['error'] . "</em>"; ?>
						</td></tr>
						<tr class="actions_header" >
							<td>Actions</td>
						</tr>
						<tr>
							<td>
							<?php 
							echo generate_form_header();																				
							echo show_invoice_buttons();									
							?>									
							</td>
						</tr>
						<tr></tr>
					</table>							
				</td>
			    </tr>
				<tr><td width="15%"><b>Status:</b></td>
				<td >
				<?php 
					echo $glb_arr_values['STATUS'];								
				?> 
				</td>						
				</tr>
				<tr>
				<td ><b>Date:</b><?= generate_required_superscript($state);?></td> 
				<td>
				<?php 
					if ($state == state_VIEW ) {
						echo format_date_reverse($glb_arr_values['DATE']);
					}
					else{
						echo generate_text_input ("date_box", "DATE", format_date_reverse($glb_arr_values['DATE']));							
					}
				?> 
				</td>
				</tr>																		
				<tr>
				<td><b>Currency:</b></td>
				<td>
				<?php
					if ($state == state_VIEW ) {								
						echo $glb_arr_values['CURRENCY_DESC']. "(". $glb_arr_values['CURRENCY_NAME'].")";							
					}
					else{
						
						echo generate_dd_currency_input($glb_arr_values['CURRENCY_ID'],'currencyChange()');		
					}
				?>
				<?php if (isset($errors['CURRENCY_ID'])) echo "<em class=\"error\">" . $errors['CURRENCY_ID'] . "</em>"; ?>
				</td>
				</tr>																					
				<tr><td valign="top"><b>Description:</b></td>
				<td colspan="1">						
				<?php 
					if ( $state == state_VIEW ) {
						echo '<span class="contrack_notes">';
						echo $glb_arr_values['DESCRIPTION'];
						echo '</span>';						
						}
						else{
						echo generate_textarea_input ("DESCRIPTION", 5, 70, $glb_arr_values['DESCRIPTION']);								
						}
				?> 		
				</td></tr>
				<tr><td valign="top"><b>Message:</b></td>
				<td colspan="1">						
				<?php 
					if ( $state == state_VIEW ) {
						echo '<span class="contrack_notes">';
						echo $glb_arr_values['MESSAGE'];
						echo '</span>';
						}
						else{
						echo generate_textarea_input ("MESSAGE", 5, 70, $glb_arr_values['MESSAGE']);								
						}
				?> 		
				</td>
				</tr>		
				<tr>
				
				<td><b>Payor :</b><?= generate_required_superscript($state);?></td>
				<td>
				<?php
					if ($state == state_VIEW ) {
						$entity = ($glb_arr_values['PAYOR_TYPE'] == entity_BUYER ? entity_GEN_ORDER : entity_SINGLE_ORDER);								
						echo "<a href=\"search_order.php?entity=".$entity."&referrer=".$glb_arr_values['PAYOR_TYPE']."&referrer_id=".$glb_arr_values['PAYOR_PARTY_ID']."\">";
						echo $glb_arr_values['PAYOR_NAME'];
						echo  "</a>";
					}
					else{ 
						echo '<select id="PAYOR_PARTY_ID" name="PAYOR_PARTY_ID" onChange="populateDropDown(this,document.getElementById(\'PAYOR_CONTACT_ID\'),'.$glb_arr_values['PAYOR_CONTACT_ID'].');">';
						echo populate_payor_dropdown($glb_arr_values['PAYOR_PARTY_ID']); 
						echo '</select>';
					}
				?>
				<?php if (isset($errors['PAYOR_PARTY_ID'])) echo "<em class=\"error\">" . $errors['PAYOR_PARTY_ID'] . "</em>"; ?>
				</td>
				</tr>
				
				<tr>
				<td><b>Payor Contact:</b></td>
				<td>
				<?php
					if ($state == state_VIEW ) {								
						echo $glb_arr_values['PAYOR_CONTACT_NAME'];							
					}
					else{ 
						echo generate_dd_payor_contacts_input($glb_arr_values['PAYOR_CONTACT_ID'], $glb_arr_values['PAYOR_PARTY_ID']);
					}
				?>
				</td>
				</tr>
				
				<tr>
				<td><b>Internal Contact:</b></td>
				<td>
				<?php
					if ($state == state_VIEW ) {								
						echo $glb_arr_values['PAYEE_CONTACT_NAME'];							
					}
					else{
						echo generate_dd_org_contacts_input($glb_arr_values['PAYEE_CONTACT_ID']);		
					}
				?>
				</td>
				</tr>
				<tr>
				<td ><b>Payment Terms:</b></td> 
				<td>
				<?php 
					if ($state == state_VIEW ) {
						echo $glb_arr_values['PAYMENT_TERMS'];
					}
					else{
						echo generate_text_input ("formtext_longwidth", "PAYMENT_TERMS", $glb_arr_values['PAYMENT_TERMS']);
					}
				?> 
				</td>
				</tr>										
				</form>
				<?php 								
					echo show_line_items();														
				?>
				
			</table>					
	</tr>	
					
</table>
<?php
include("body_footer.php"); 
?>