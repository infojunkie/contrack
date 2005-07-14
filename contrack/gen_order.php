	
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
	- Order Association Logic
	
	General Order page

	This page will handle creating/editing/viewing general orders.
	It has the following states: 	a) "NEW" to create a new general order
									b) "EDIT" to edit an existing general order
									c) "VIEW" to view an existing general order
									d) "PROCESS". The form is in this state while it's
									being processed after a Save operation. The page
									will never be viewable in this state. (Transient state)
							
	State transitions are as follows:
		Initial State		Action						New State
		----------			------						---------
		VIEW				Edit						EDIT
		EDIT/NEW			Save						PROCESS 
		PROCESS				Validation successful		VIEW (With changes)
		PROCESS				Validation not successful	EDIT/NEW (With validation results)			
		EDIT/NEW			Cancel						history.back()

	Notes: 	- EDIT and NEW are similar states except that in EDIT the controls
			  are pre-populated with the attributes of the entity being edited
			  whereas in NEW, the controls are blank.			
			- In EDIT and VIEW state, the primary key exists (GEN_ORDER_ID), in NEW state 
			  it has the value -1.			
			- Deleting sub-entities (contact or attachment) works by resubmitting the form 
			  with the relevant opcode and sub-entity name
******************************************************************************************/

require_once("db_utils.php");
require_once("general_utils.php");
require_once("order_utils.php");
require_once("patUser-2.2.3/prepend.php" );
require_once("permissions.php");

init_page();
process_state();
set_js_vars();

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
	global $user, $gen_order_id, $has_milestones_as_line_items;	
	// Authenticate
	if (! ($user->isAuthenticated())){
		deny_access();
	}	
	get_page_params ();
	validate_permissions();
	check_error();
	$has_milestones_as_line_items = is_any_milestone_lineitem($gen_order_id);
}

/*
	Get all parameters passed to this page and format display string
*/
function get_page_params()
{
	global  $state, $gen_order_id, $sub_entity_op, $sub_entity_name, $sub_entity_id, $page_name;

	// Get key parameters passed to the page
	$state  	  = isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : state_VIEW );
	$gen_order_id = isset($_GET['gen_order_id']) ? $_GET['gen_order_id'] : (isset($_POST['GEN_ORDER_ID']) ? $_POST['GEN_ORDER_ID'] : -1);
	
	if (($gen_order_id == -1) && ($state != state_PROCESS)) {
		$state = state_NEW;
	}
	
	// Current sub entities are Attachments and Contacts
	$sub_entity_op   = isset($_GET['sub_entity_op']) ? $_GET['sub_entity_op'] : (isset($_POST['sub_entity_op']) ? $_POST['sub_entity_op'] : -1);
	$sub_entity_name = isset($_GET['sub_entity_name']) ? $_GET['sub_entity_name'] : (isset($_POST['sub_entity_name']) ? $_POST['sub_entity_name'] : -1);
	$sub_entity_id 	 = isset($_GET['sub_entity_id']) ? $_GET['sub_entity_id'] : (isset($_POST['sub_entity_id']) ? $_POST['sub_entity_id'] : -1);
		
	// Set page name for use in fetching permissions from DB
	$page_name = "gen_order";
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
	Determine whether the currently logged in user is associated with the entity
	being viewed.
	
	Association logic:
		Agent is allowed to view all GO
		Buyer is associated with GO directly (if he's the buyer!)
		Supplier should not view GO	
*/
function is_associated()
{
	global $user, $gen_order_id;
	
	// Get associated party_id
	$logged_in_party_id = get_party_id_for_user($user->getSessionValue('userid'));
	
	$buyer_id = get_gen_order_buyer_id($gen_order_id);
	
	if ($logged_in_party_id == $buyer_id) {
		$is_associated = true;	
	}
	else {
		$is_associated = false;
	}
	//return $is_associated;
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
	global $glb_arr_values, $state, $gen_order_id;
	
	if ( $state != state_NEW ) {
		$glb_arr_values['GEN_ORDER_ID'] =  $gen_order_id;
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
	global 	$glb_arr_values, $state, $gen_order_id, 
			$sub_entity_op, $sub_entity_name, $sub_entity_id;
	
	// Get values from submitted form	
	post_values();
	
	// If we're deleting a sub-entity
	if ( $sub_entity_op == opcode_DELETE ) {		
		if ( strcasecmp ('CONTACT', $sub_entity_name ) == 0) {		
			delete_gen_order_contact($gen_order_id, $sub_entity_id);
		} elseif ( strcasecmp ('ATTACHMENT', $sub_entity_name ) == 0) {		
			delete_attachment($sub_entity_id);
		}
		// Go back to edit mode
		$location_url = "Location: gen_order.php?gen_order_id=".$glb_arr_values['GEN_ORDER_ID']."&state=".state_EDIT;
		header($location_url);	
		exit;				
	}
	else {
		if ( validate_form() ) {
			if ( -1 == $glb_arr_values['GEN_ORDER_ID'] ) { // NEW
				$sql = generate_insert_gen_order_sql() ;
				//echo "SQL Generated: " . $sql . "<br>";			
				$result = mysql_query($sql) or die(mysql_error());
				if (!$result) return mysql_error();
				$glb_arr_values['GEN_ORDER_ID'] = mysql_insert_id();
				$gen_order_id = $glb_arr_values['GEN_ORDER_ID'];													
			} else {  								     // EDIT
				$sql = generate_update_gen_order_sql();	
				//echo "SQL Generated: " . $sql . "<br>";	
				$result = mysql_query($sql) or die(mysql_error());
				if (!$result) return mysql_error();						
			}
			// Redirect while setting state back to "VIEW" after having saved successfully
			$location_url = "Location: gen_order.php?gen_order_id=".$glb_arr_values['GEN_ORDER_ID']."&state=".state_VIEW;
			header($location_url);	
			exit;		
		} else {  // validation unsuccessful
			if ( -1 != $glb_arr_values['GEN_ORDER_ID'] ) {	// Go back to EDIT state			
				$state = state_EDIT;			
				$glb_arr_values['DATE_CREATED'] = get_date_created_for_entity(entity_GEN_ORDER, $glb_arr_values['GEN_ORDER_ID']);
			} else {										// Go back to NEW state
				$state = state_NEW;
			}
		}
	} // end big else
}

/*
	Validate form values
*/	
function validate_form() 
{
	global $glb_arr_values, $errors;
		
	// Verify that at least a name exists for the order
	if (empty($glb_arr_values['NAME'])) $errors['NAME'] = "Please enter a name for this General Order";	
	
	// Verify that a product type has been selected
	if (empty($glb_arr_values['CURRENCY_ID'])) $errors['CURRENCY_ID'] = 'Please enter a currency for this General Order' ;	
	
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
	
	$glb_arr_values['GEN_ORDER_ID'] = -1;
	$glb_arr_values['NAME'] 		= '';
	$glb_arr_values['CODE']			= '';
	$glb_arr_values['STATUS']		= 'OPEN';
	$glb_arr_values['BUYER_ID'] 	= -1;
	$glb_arr_values['AGENT_ID'] 	= -1;
	$glb_arr_values['CLIENT_ORDER_ID']  = '';
	$glb_arr_values['RECEIVE_DATE'] 	= '';
	$glb_arr_values['PLANNED_DELIVERY_DATE'] = '';	
	$glb_arr_values['PO_DATE']		= '';
	$glb_arr_values['PO_NUMBER']	= '';
	$glb_arr_values['COMMENTS'] 	= '';
	$glb_arr_values['CURRENCY_ID'] 	= -1;
	$glb_arr_values['CURRENCY_NAME'] = '';	
}

/*
	Get values from submitted form and populate global values array
*/	
function post_values() 
{	
	global $glb_arr_values, $gen_order_id;
	
	$glb_arr_values['GEN_ORDER_ID'] = $gen_order_id ;	
	$glb_arr_values['NAME']			= isset($_POST['NAME']) ? $_POST['NAME'] : "";
	$glb_arr_values['CODE'] 		= isset($_POST['CODE']) ? $_POST['CODE'] : "";
	$glb_arr_values['BUYER_ID'] 	= isset($_POST['BUYER_ID']) ? $_POST['BUYER_ID'] : 0;
	$glb_arr_values['AGENT_ID'] 	= isset($_POST['AGENT_ID']) ? $_POST['AGENT_ID'] : 0;
	$glb_arr_values['CLIENT_ORDER_ID'] 	= isset($_POST['CLIENT_ORDER_ID']) ? $_POST['CLIENT_ORDER_ID'] : "";
	$glb_arr_values['STATUS']			= isset($_POST['STATUS']) ? $_POST['STATUS'] : "";
	$glb_arr_values['RECEIVE_DATE'] 	= isset($_POST['RECEIVE_DATE']) ? $_POST['RECEIVE_DATE'] : "";
	$glb_arr_values['PLANNED_DELIVERY_DATE'] = isset($_POST['PLANNED_DELIVERY_DATE']) ? $_POST['PLANNED_DELIVERY_DATE'] : "";
	$glb_arr_values['CURRENCY_ID']  = isset($_POST['CURRENCY_ID']) ? $_POST['CURRENCY_ID'] : 1;
	$glb_arr_values['PO_DATE'] 		= isset($_POST['PO_DATE']) ? $_POST['PO_DATE'] : "";
	$glb_arr_values['PO_NUMBER'] 	= isset($_POST['PO_NUMBER']) ? $_POST['PO_NUMBER'] : "";
	$glb_arr_values['COMMENTS'] 	= isset($_POST['COMMENTS']) ? $_POST['COMMENTS'] : "";	
}

/*
	Function: get_record()
	Desc:  
		If gen_order_id < 1 then this is a new record. Populate with defaults.
		If gen_order_id > 1 get record from database and populate values global array
 	Params: 
    	None
   	Returns: 
		Nothing
*/
function get_record()
{
	global $glb_arr_values, $gen_order_id;
	
	if (1 > $glb_arr_values['GEN_ORDER_ID']) {
		new_values();
		return;
	}
	
	$sql = generate_select_gen_order_sql();	
	$result = mysql_query($sql) or die(mysql_error());	
	if (!$result) return mysql_error();
	$row = mysql_fetch_array($result);
	
	// Populate values array
	$glb_arr_values['NAME'] 			= mysql_decode($row['FRIENDLY_NAME']);
	$glb_arr_values['CODE'] 			= mysql_decode($row['CODE']);
	$glb_arr_values['BUYER_ID'] 		= mysql_decode($row['BUYER_ID']);
	$glb_arr_values['AGENT_ID'] 		= mysql_decode($row['AGENT_ID']);
	$glb_arr_values['CLIENT_ORDER_ID'] 	= mysql_decode($row['CLIENT_ORDER_ID']);
	$glb_arr_values['STATUS']			= mysql_decode($row['STATUS']);
	$glb_arr_values['RECEIVE_DATE'] 	= mysql_decode($row['RECEIVE_DATE']);
	$glb_arr_values['PLANNED_DELIVERY_DATE'] = mysql_decode($row['PLANNED_DELIVERY_DATE']);
	$glb_arr_values['CURRENCY_ID'] 		= mysql_decode($row['CURRENCY_ID']);
	$glb_arr_values['CURRENCY_NAME'] 	= mysql_decode($row['CURRENCY_NAME']);
	$glb_arr_values['PO_DATE']			= mysql_decode($row['PO_DATE']);
	$glb_arr_values['PO_NUMBER'] 		= mysql_decode($row['PO_NUMBER']);		
	$glb_arr_values['COMMENTS'] 		= mysql_decode($row['COMMENTS']);
	$glb_arr_values['DATE_CREATED'] 	= mysql_decode($row['DATE_CREATED']);
	$glb_arr_values['PCENT_COMPLETE'] 	= get_percent_complete($glb_arr_values['GEN_ORDER_ID'], entity_GEN_ORDER);

	mysql_free_result($result);	
}


/**************************************** 
	SQL Generation functions
*****************************************/
/*
	Generates select SQL for general order
*/	
function generate_select_gen_order_sql()
{
	global $glb_arr_values;
	
	$select_sql = "	SELECT gen_order.FRIENDLY_NAME, gen_order.CODE, gen_order.BUYER_ID, gen_order.AGENT_ID,
					gen_order.CLIENT_ORDER_ID, gen_order.STATUS, gen_order.RECEIVE_DATE, 
					gen_order.PLANNED_DELIVERY_DATE, gen_order.CURRENCY_ID, gen_order.PO_DATE,
					gen_order.PO_NUMBER, gen_order.COMMENTS, gen_order.DATE_CREATED, 
					currency.NAME AS CURRENCY_NAME 
					FROM gen_order LEFT JOIN currency on gen_order.CURRENCY_ID = currency.CURRENCY_ID 
					WHERE gen_order.GEN_ORDER_ID=".$glb_arr_values['GEN_ORDER_ID'].
				  " AND gen_order.STATE='ACTIVE'" ;	
	return $select_sql;
}

/*
	Generates insert SQL for General Order
*/	
function generate_insert_gen_order_sql()
{
	global $glb_arr_values;
	
	$insert_sql = "INSERT INTO gen_order 
					(STATE, BUYER_ID, AGENT_ID, CODE, STATUS, CLIENT_ORDER_ID, RECEIVE_DATE, 
					PLANNED_DELIVERY_DATE, CURRENCY_ID, FRIENDLY_NAME, PO_DATE, PO_NUMBER, COMMENTS,
					DATE_CREATED)
					VALUES(	'ACTIVE',
					".$glb_arr_values['BUYER_ID'].",
					".$glb_arr_values['AGENT_ID'].",
					'".mysql_encode(get_new_code('GEN_ORDER'))."',
					".$glb_arr_values['STATUS'].",
					'".mysql_encode($glb_arr_values['CLIENT_ORDER_ID'])."',
					'".format_date($glb_arr_values['RECEIVE_DATE'])."',					
					'".format_date($glb_arr_values['PLANNED_DELIVERY_DATE'])."',
					".$glb_arr_values['CURRENCY_ID'].",
					'".mysql_encode($glb_arr_values['NAME'])."',
					'".format_date($glb_arr_values['PO_DATE'])."',
					'".mysql_encode($glb_arr_values['PO_NUMBER'])."',
					'".mysql_encode($glb_arr_values['COMMENTS'])."',
					   NOW())";	   
	return $insert_sql;
}

/*
	Generates update SQL for General
*/	
	
function generate_update_gen_order_sql()
{
	global $glb_arr_values;
	
	$update_sql =  "UPDATE gen_order SET														
					
					BUYER_ID = " .$glb_arr_values['BUYER_ID'].",
					AGENT_ID = ".$glb_arr_values['AGENT_ID'].",
					STATUS = " .mysql_encode( $glb_arr_values['STATUS']).",
					CLIENT_ORDER_ID = '" .mysql_encode( $glb_arr_values['CLIENT_ORDER_ID'])."',
					RECEIVE_DATE = '" .format_date( $glb_arr_values['RECEIVE_DATE'])."',
					PLANNED_DELIVERY_DATE = '" .format_date( $glb_arr_values['PLANNED_DELIVERY_DATE'])."',
					CURRENCY_ID = " .mysql_encode( $glb_arr_values['CURRENCY_ID']).",
					FRIENDLY_NAME = '" .mysql_encode( $glb_arr_values['NAME'])."',
					PO_DATE = '". format_date($glb_arr_values['PO_DATE'])."',
					PO_NUMBER = '" .mysql_encode( $glb_arr_values['PO_NUMBER'])."',
					COMMENTS = '" .mysql_encode( $glb_arr_values['COMMENTS'])."'					
					
					WHERE GEN_ORDER_ID=".$glb_arr_values['GEN_ORDER_ID'];				
	return $update_sql;
}

/*
	Generates select contacts SQL
*/	
function generate_select_gen_order_contacts_sql () 
{
	global $gen_order_id;
	
	$select_sql =  "SELECT employee.PARTY_ID, employee.NAME AS CONTACT_NAME, employee.POSITION,  
					employer.Name AS EMPLOYER_NAME, employee.ADDRESS1, employee.ADDRESS2, employee.CITY, 
					employee.PROVINCE, employee.POSTAL_CODE, employee.COUNTRY_ID, employee.TEL, 
					employee.MOBILE, employee.EMAIL 
				   	FROM gen_order_rel_party INNER JOIN party AS employee 
				   				ON gen_order_rel_party.party_id = employee.party_id
             			LEFT JOIN party AS employer ON employee.employer_id = employer.party_id
				   	WHERE gen_order_rel_party.GEN_ORDER_ID = ". $gen_order_id;
	return $select_sql;
}

function delete_gen_order_contact($gen_order_id, $contact_id)
{
	if ($contact_id > 0 ) {
		$sql = "DELETE FROM gen_order_rel_party WHERE GEN_ORDER_ID =".$gen_order_id. 
													 " AND PARTY_ID =". $contact_id; 													 
		$result = mysql_query($sql) or die(mysql_error());	
	}
}

/*
	Check if there are any milestones belonging to this order selected as line items in an invoice
	A quick lookup in the invoice_rel_order table is enough to determine that.
*/
function is_any_milestone_lineitem($gen_order_id)
{
	global $invoice_number;
	
	$existence = false;
	$sql 	   = "SELECT invoice.NUMBER
				  FROM invoice_rel_order INNER JOIN invoice ON invoice_rel_order.INVOICE_ID = invoice.INVOICE_ID
				  WHERE invoice_rel_order.GEN_ORDER_ID = ".$gen_order_id;	
	$result	   = mysql_query($sql) or die(mysql_error());	
	$existence = mysql_num_rows($result) > 0 ;
	if ($existence) {
		$row = mysql_fetch_array($result);
		$invoice_number = $row['NUMBER'];
	}
	mysql_free_result($result);	
	return $existence;
}

/*
	Check if buyer is in additional costs of this general order or the single orders beneath it
*/
function is_buyer_in_add_costs($gen_order_id)
{
	global $glb_arr_values;
	
	$existence = false;
	$sql 	   = "SELECT additional_cost.ADDITIONAL_COST_ID
				  FROM additional_cost
				  WHERE (additional_cost.GEN_ORDER_ID = ".$gen_order_id.") 
				  AND (additional_cost.PAYOR_ID = ".$glb_arr_values['BUYER_ID']." 
				  OR additional_cost.PAYEE_ID = ".$glb_arr_values['BUYER_ID'].") ";	
	$result	   = mysql_query($sql) or die(mysql_error());	
	$existence = mysql_num_rows($result) > 0 ;	
	mysql_free_result($result);	
	return $existence;
}

/*
	Check if agent is in additional costs of this general order or the single orders beneath it
*/
function is_agent_in_add_costs($gen_order_id)
{
	global $glb_arr_values;
	
	$existence = false;
	$sql 	   = "SELECT additional_cost.ADDITIONAL_COST_ID
				  FROM additional_cost
				  WHERE (additional_cost.GEN_ORDER_ID = ".$gen_order_id.") 
				  AND (additional_cost.PAYOR_ID = ".$glb_arr_values['AGENT_ID']." 
				  OR additional_cost.PAYEE_ID = ".$glb_arr_values['AGENT_ID'].") ";
	$result	   = mysql_query($sql) or die(mysql_error());	
	$existence = mysql_num_rows($result) > 0 ;	
	mysql_free_result($result);	
	return $existence;
}

/*
	Set variables to be used in JavaScript for business rules
*/
function set_js_vars()
{ 
	global $gen_order_id, $buyer_in_add_costs, $agent_in_add_costs;

	$buyer_in_add_costs = is_buyer_in_add_costs($gen_order_id);
	$agent_in_add_costs = is_agent_in_add_costs($gen_order_id);
}	

/**************************************** 
	XHTML Generation functions
*****************************************/
/*
	Generate contacts block
*/
function show_contacts()
{

	global $glb_arr_values, $state;
 
 	// Get contacts from DB
	$sql = generate_select_gen_order_contacts_sql();	
	$result = mysql_query($sql) or die(mysql_error());	
	
	// Contacts Header
	$markup = "<tr>
			   	<td width=\"15%\" valign=\"top\"><b>Contacts:</b></td>";									
	
	// If there are no contacts AND we're not in EDIT state
	if ( (! mysql_num_rows($result)) && ($state != state_EDIT) )  {
		$markup .= "<td colspan =\"3\"><i>No Contacts </td></i></tr>\r\n";			
	} else	{				
		$markup .= "<td colspan =\"3\">
					<table>								
					<tr class=\"contacts_header\"> ";
		
		// Are we in EDIT ?			
		if ($state == state_EDIT) {       
		$markup .= "<td class=\"small_text\" width=\"5%\"><a href=\"javascript:void open_popup('contact_select_popup.php?
					referrer_id=".$glb_arr_values["GEN_ORDER_ID"].	"&referrer_type=".entity_GEN_ORDER.
					"&state=".state_VIEW."','contactSelect',
					'menubar=no,toolbar=no,scrollbars=no',310,640);\">
					<img src=\"images/b.add.png\" alt=\"Add\" title=\"Add\"/></a></td>\r\n" ;
		}
		$markup .=		"<td>Name</td>
						<td>Position</td>		
						<td>Employer</td>							
						<td>Address</td>
						<td>Office Phone</td>
						<td>Mobile Phone</td>
						<td>Email</td>
						</tr>" ;	
		$i=0;
		while ($row = mysql_fetch_array($result)) {
			$pk    		= $row['PARTY_ID'];
			$name  		= mysql_decode($row['CONTACT_NAME']);
			$country	= get_country_name($row['COUNTRY_ID']) ;
			
			$markup .= "<tr class=\"contacts_line" .($i%2). "\">"; 
			
			if ($state == state_EDIT ) {  
			$markup .= "<td class=\"small_text\">
						<a href=\"javascript:void subEntityDelete('CONTACT',". $pk .");\">
				    	<img src=\"images/b.remove.png\" alt=\"Remove\" title=\"Remove\"/></a></td>\r\n";
			}
			$markup .= "<td>". $name . "</td>";						
			$markup .= "<td>"	.mysql_decode($row['POSITION'])."</td>";
			$markup .= "<td>"	.mysql_decode($row['EMPLOYER_NAME'])."</td>";
			$markup .= "<td>"	.mysql_decode($row['ADDRESS1']). " " .mysql_decode($row['ADDRESS2']). " " 
								.mysql_decode($row['CITY']). " " . mysql_decode($row['PROVINCE']) . " " 
								.mysql_decode($row['POSTAL_CODE']) . " ". mysql_decode($country) ."</td>";	
			$markup .= "<td>"	.mysql_decode($row['TEL'])."</td>";
			$markup .= "<td>"	.mysql_decode($row['MOBILE'])."</td>";
			$markup .= "<td><a href=\"mailto:"	.$row['EMAIL'] ."\">" . $row['EMAIL'] . "</a></td>";
			$markup .= "</td></tr>\r\n";								
			++$i;
		}  // end while
		mysql_free_result($result);
		// Contacts table footer
		$markup .= "</table></td></tr>\r\n" ;
	} // end else
	return $markup;
}

/*
	Generates standard form header for party
*/
function generate_form_header() 
{
	global $glb_arr_values, $state, $sub_entity_op, $sub_entity_name, $sub_entity_id;
	
	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_gen_order\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"GEN_ORDER_ID\" name=\"GEN_ORDER_ID\" value=\"" . $glb_arr_values["GEN_ORDER_ID"] . "\"/>\r\n";	
	$str_frm_header .= "<input type=\"hidden\" id=\"CODE\" name=\"CODE\" value=\"" . $glb_arr_values["CODE"] . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_op\" name=\"sub_entity_op\" value=\"" . $sub_entity_op . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_name\" name=\"sub_entity_name\" value=\"" . $sub_entity_name . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_id\" name=\"sub_entity_id\" value=\"" . $sub_entity_id . "\"/>\r\n";
	return $str_frm_header;
}

// Set page title and include body header
if ($state != state_NEW ) { 	
	$page_title = format_gen_order_name($glb_arr_values["GEN_ORDER_ID"]) . " :: Profile";
}
else {
	$page_title = "General Order :: New";
}
$table_name = "gen_order";
require_once("body_header.php"); 
?>
<!-- JavaScript Section -->
<script type="text/javascript"><!--
var hasMilestonesAsLineItems = <?= $has_milestones_as_line_items ? 1 : 0 ?>;
var buyerInAddCosts = <?= $buyer_in_add_costs ? 1 : 0 ?>;
var agentInAddCosts = <?= $agent_in_add_costs ? 1 : 0 ?>;
var invoiceNumber = '<?= $invoice_number ?>';

function ctlChange(ctl, originalCtlValue, ctlName, entity)
{
	if ((hasMilestonesAsLineItems) && (entity==entityBUYER || entity==entityCURRENCY)) {		
		alert ("This order contains milestones that are selected as line items in Invoice #: " + invoiceNumber + ". Please remove all milestones selected as line items before changing the " + ctlName + ".");
		ctl.value = originalCtlValue;
		return;
	}
	if (entity == entityBUYER) {
		if (buyerInAddCosts){
			alert ("This Buyer is involved in the additional costs for this General Order and/or the Single Orders under it. Please remove all additional costs entries involving this Buyer before changing it.");
			ctl.value = originalCtlValue;
			return;
		}
	}
	if (entity == entityAGENT) {
		if (agentInAddCosts){
			alert ("This Agent is involved in the additional costs for this General Order and/or the Single Orders under it. Please remove all additional costs entries involving this Agent before changing it.");
			ctl.value = originalCtlValue;
			return;		
		}
	}
}

function entitySave() 
{
	var frm = document.frm_gen_order;	
	if (frm.NAME.value.length < 1 ) {
		alert ("You must enter a name for this General Order");
		return;
	} 
	if (frm.CURRENCY_ID.value <= 0) {
		alert ("You must associate a currency with this General Order");
		return;
	}
	if (frm.BUYER_ID.value <= 0 ) {
	alert ("You must associate a Buyer with this General Order");
	return;
	}
	frm = document.frm_gen_order;		
	frm.state.value = statePROCESS;
	frm.submit();	
}

function entityEdit() 
{
	frm = document.frm_gen_order;		
	frm.state.value = stateEDIT;
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
	frm = document.frm_gen_order;	
	if (frm.GEN_ORDER_ID.value == -1) {
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
		frm = document.frm_gen_order;		
		frm.state.value			  = statePROCESS;
		frm.sub_entity_op.value   = opcodeDELETE;
		frm.sub_entity_name.value = strName;
		frm.sub_entity_id.value   = intID;		
		frm.submit();	
	}
}

function resubmitForm() 
{
	var frm = document.frm_gen_order;		
	frm.state.value	= stateEDIT;	
	frm.submit();
}
--></script>
<table border="0"  cellpadding="0" cellspacing="0">
	<tr>
		<td colspan="3">
			<h4>
			<?php 
			 if ($state != state_NEW ) { 					
				echo "GENERAL ORDER :: ". format_gen_order_name($glb_arr_values['GEN_ORDER_ID']); 
			} 
			else {
				echo "GENERAL ORDER :: New";
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
				echo generate_gen_order_tabs(5, $glb_arr_values['GEN_ORDER_ID'], $state);																
				?>
				</tr>
			</table> 					
		</td>
	</tr> <!-- End of Tabs -->			
	<tr>
		<td colspan = "3"> 
			<table border="0" class="tab_content">	
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
							
							// Show action butons depending on current state
							if ( $state == state_VIEW) { 																	
								if($allow_modify_all || $allow_modify_own) {
									echo generate_button("button","Edit","entityEdit()"); 
									}
								}						
							else{ //Edit or New
								echo generate_button("button","Save","entitySave()");
								echo "&nbsp";
								echo generate_button("button","Cancel","entityCancel()");										
							}	
							?>									
							</td>
						</tr>
						<tr></tr>
					</table>							
				</td></tr>
			
				<tr><td width="17%"><b>Name:<?= generate_required_superscript($state);?></b></td>
				<td width="35%">
				<?php if ($state == state_VIEW ) {
						echo $glb_arr_values['NAME'];
						}
					  else{
						echo generate_text_input ("formtext_longwidth", "NAME", $glb_arr_values['NAME']);								
						}
				?> 
				<?php if (isset($errors['NAME'])) echo "<em class=\"error\">" . $errors['NAME'] . "</em>"; ?>
				</td>
				<td width="13%"><b>Status:</b></td> 
				<td>
				<?php 
					if ($state == state_VIEW ) {
						echo $glb_arr_values['STATUS'].", ";
						echo "<a href=\"search_order.php?entity=".entity_SINGLE_ORDER.
														"&referrer=".entity_GEN_ORDER.
														"&referrer_id=".$glb_arr_values['GEN_ORDER_ID']."\">";
						echo $glb_arr_values['PCENT_COMPLETE']. " complete"."</a>";
					}
					else{
						echo generate_dd_order_status_input ($glb_arr_values['STATUS']);								
					}
				?> 
				</td>
				</tr>
				<?php if ($state != state_NEW ) { ?>
				<tr>
					<td><b>General Order ID:</b></td>
					<td><?= $glb_arr_values["CODE"];?></td>
					<td><b>Creation Date:</b></td>
					<td><?= format_date_reverse($glb_arr_values["DATE_CREATED"]);?></td>
				</tr>	
				<?php } ?>												
				<tr>
				<tr>
				<td><b>Currency:<?= generate_required_superscript($state);?></b></td>
				<td>
				<?php
					if ($state == state_VIEW ) {								
						echo $glb_arr_values['CURRENCY_NAME'];							
					}
					else{						
						echo generate_dd_currency_input($glb_arr_values['CURRENCY_ID'],
						'ctlChange(document.frm_gen_order.CURRENCY_ID,'.$glb_arr_values['CURRENCY_ID'].' , \'Currency\', entityCURRENCY)');		
					}
				?>
				<?php if (isset($errors['CURRENCY_ID'])) echo "<em class=\"error\">" . $errors['CURRENCY_ID'] . "</em>"; ?>
				</td>
				<td><b>Receive Date:</b></td>
				<td>
				<?php
					if ($state == state_VIEW ) {
						echo format_date_reverse($glb_arr_values['RECEIVE_DATE']);
						}
					  else{
						echo generate_text_input ("date_box", "RECEIVE_DATE", format_date_reverse($glb_arr_values['RECEIVE_DATE']));						
						}
				?>
				</td>
				</tr>																
				<td><b>Client Order ID:</b></td>
				<td>
				<?php if ($state == state_VIEW ) {
						echo $glb_arr_values['CLIENT_ORDER_ID'];
						}
					  else{
						echo generate_text_input ("formtext_stdwidth", "CLIENT_ORDER_ID", $glb_arr_values['CLIENT_ORDER_ID']);								
						}
				?>
				</td>
				<td><b>Delivery Date:</b></td>
				<td>
				<?php
					if ($state == state_VIEW ) {			
						echo format_date_reverse($glb_arr_values['PLANNED_DELIVERY_DATE']);						
					}
					else {
						echo generate_text_input ("date_box", "PLANNED_DELIVERY_DATE", format_date_reverse($glb_arr_values['PLANNED_DELIVERY_DATE']));								
					}
				?>
				</td>
				</tr>						
				<tr>
				<td><b>Buyer Name:<?= generate_required_superscript($state);?></b></td>
				<td>
				<?php
					if ($state == state_VIEW ) {								
						echo "<a href=\"search_order.php?entity=".entity_GEN_ORDER."&referrer=".entity_BUYER."&referrer_id=".$glb_arr_values['BUYER_ID']."\">";
						echo get_party_name($glb_arr_values['BUYER_ID']);
						echo "</a>";
					}
					else{
						echo generate_dd_buyer_input($glb_arr_values['BUYER_ID'],
							'ctlChange(document.frm_gen_order.BUYER_ID,'.$glb_arr_values['BUYER_ID'].' , \'Buyer\', entityBUYER)');		
					}
				?>
				</td>				
				</tr>	
				<tr>
				<td><b>Agent Name:</b></td>
				<td>
				<?php
					if ($state == state_VIEW ) {								
						echo "<a href=\"search_order.php?entity=".entity_GEN_ORDER."&referrer=".entity_AGENT."&referrer_id=".$glb_arr_values['AGENT_ID']."\">";
						echo get_party_name($glb_arr_values['AGENT_ID']);
						echo  "</a>";
					}
					else{
						echo generate_dd_agent_input($glb_arr_values['AGENT_ID'],
						'ctlChange(document.frm_gen_order.AGENT_ID,'.$glb_arr_values['AGENT_ID'].' , \'Agent\', entityAGENT)');				
					}
				?>
				</td>
				</tr>
				<tr>
				<td><b>PO Number:</b></td>
				<td>
				<?php if ($state == state_VIEW ) {
						echo $glb_arr_values['PO_NUMBER'];
						}
					  else{
						echo generate_text_input ('formtext_stdwidth', 'PO_NUMBER', $glb_arr_values['PO_NUMBER']);								
						}
				?>
				</td>
				</tr>
				<tr>
				<td><b>PO Date:</b></td>
				<td>
				<?php if ($state == state_VIEW ) {
						echo format_date_reverse($glb_arr_values['PO_DATE']);
						}
					  else{
						echo generate_text_input ('date_box', 'PO_DATE', format_date_reverse($glb_arr_values['PO_DATE']));								
						}
				?>
				</td>
				</tr>
																						
				<tr><td valign="top"><b>Comments:</b></td>
				<td colspan="3">						
				<?php if ( $state == state_VIEW ) {
						echo '<span class="contrack_notes">';
						echo $glb_arr_values['COMMENTS'];
						echo '</span>';
						}
						else{
						echo generate_textarea_input ("COMMENTS", 5, 70, $glb_arr_values['COMMENTS']);								
						}
				?> 		
				</td></tr>		
				</form>
				<?php 	
					echo show_attachments($table_name, $gen_order_id);													
					echo show_contacts();														
				?>
			</table>					
	</tr>	
					
</table>
<?php
include("body_footer.php"); 
?>