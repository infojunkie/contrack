
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
	- Order Association logic

	Single Order page

	This page will handle creating/editing/viewing Single orders.
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
	global $user, $single_order_id, $has_milestones_as_line_items;

	// Authenticate
	if (! ($user->isAuthenticated())){
		deny_access();
	}
	get_page_params ();
	validate_permissions();
	check_error();
	$has_milestones_as_line_items = is_any_milestone_lineitem($single_order_id);
}

/*
	Get key parameters passed to this page and format display string
*/
function get_page_params()
{
	global  $state, $single_order_id, $sub_entity_op, $sub_entity_name, $sub_entity_id, $page_name;

	// Get key parameters passed to the page
	$state  	     = isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : "");
	$single_order_id = isset($_GET['single_order_id']) ? $_GET['single_order_id'] : (isset($_POST['SINGLE_ORDER_ID']) ? $_POST['SINGLE_ORDER_ID'] : -1);

	if (($single_order_id == -1) && ($state != state_PROCESS)) {
		$state = state_NEW;
	}

	// Current sub entities are Attachments and Contacts
	$sub_entity_op   = isset($_GET['sub_entity_op']) ? $_GET['sub_entity_op'] : (isset($_POST['sub_entity_op']) ? $_POST['sub_entity_op'] : -1);
	$sub_entity_name = isset($_GET['sub_entity_name']) ? $_GET['sub_entity_name'] : (isset($_POST['sub_entity_name']) ? $_POST['sub_entity_name'] : -1);
	$sub_entity_id 	 = isset($_GET['sub_entity_id']) ? $_GET['sub_entity_id'] : (isset($_POST['sub_entity_id']) ? $_POST['sub_entity_id'] : -1);

	// Set page name for use in fetching permissions from DB
	$page_name = "single_order";
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
	Determine whether the currently logged in user is associated with the entity
	being viewed.

	Association logic:
		Agent: SO belongs to GO featuring Agent |
		Buyer: SO belongs to GO featuring Buyer |
		Supplier: SO features supplier
*/
function is_associated()
{
	global $user, $single_order_id;

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
	global $glb_arr_values, $state, $single_order_id;

	if ( $state != state_NEW ) {
		$glb_arr_values['SINGLE_ORDER_ID'] =  $single_order_id;
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
	global 	$glb_arr_values, $state, $single_order_id,
			$sub_entity_op, $sub_entity_name, $sub_entity_id;

	// Get values from submitted form
	post_values();

	// If we're deleting a sub-entity
	if ( $sub_entity_op == opcode_DELETE ) {
		if ( strcasecmp ('CONTACT', $sub_entity_name ) == 0) {
			delete_single_order_contact($single_order_id, $sub_entity_id);
		} elseif ( strcasecmp ('ATTACHMENT', $sub_entity_name ) == 0) {
			delete_attachment($sub_entity_id);
		}
		// Go back to edit mode
		$location_url = "Location: single_order.php?single_order_id=".$glb_arr_values['SINGLE_ORDER_ID']."&state=".state_EDIT;
		header($location_url);
		exit;
	}
	else {
		if ( validate_form() ) {
			if ( -1 == $glb_arr_values['SINGLE_ORDER_ID'] ) { // NEW
				$sql = generate_insert_single_order_sql() ;
				$result = mysql_query($sql) or die(mysql_error());
				if (!$result) return mysql_error();
				$glb_arr_values['SINGLE_ORDER_ID'] = mysql_insert_id();
				$single_order_id = $glb_arr_values['SINGLE_ORDER_ID'];
				// Insert PLANNED_DELIVERY_DATE into production_step table
				insert_sin_order_delivery_date($single_order_id, $glb_arr_values['PLANNED_DELIVERY_DATE'] );
			} else {  								     	// EDIT
				$sql = generate_update_single_order_sql();
				$result = mysql_query($sql) or die(mysql_error());
				if (!$result) return mysql_error();
				// Update PLANNED_END_DATE in production_step table
				update_sin_order_delivery_date($single_order_id, $glb_arr_values['PLANNED_DELIVERY_DATE'] );
				// Update GEN_ORDER_ID in invoice_rel_order table
				update_invoice_rel_order($single_order_id, $glb_arr_values['GEN_ORDER_ID']);
			}
			// Redirect while setting state back to "VIEW" after having saved successfully
			$location_url = "Location: single_order.php?single_order_id=".$glb_arr_values['SINGLE_ORDER_ID']."&state=".state_VIEW;
			header($location_url);
			exit;
		} else {  // validation unsuccessful
			if ( -1 != $glb_arr_values['SINGLE_ORDER_ID'] ) {	// Go back to EDIT state
				$state = state_EDIT;
				$glb_arr_values['DATE_CREATED'] = get_date_created_for_entity(entity_SINGLE_ORDER, $glb_arr_values['SINGLE_ORDER_ID']);
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

	if (empty($glb_arr_values['NAME']))
		$errors['NAME'] = "Please enter a name for this Single Order";
	if ($glb_arr_values['GEN_ORDER_ID'] == 0)
		$errors['GEN_ORDER_ID'] = "Please associate a General Order with this Single Order";
	if ($glb_arr_values['SUPPLIER_ID'] == 0)
		$errors['SUPPLIER_ID'] = "Please choose a Supplier for this Single Order";
	if (safe_count($errors) > 0)
		$errors['error'] = "There was a problem with the information entered. Please review the form and make necessary corrections.";

	return safe_count($errors) == 0;
}

/*
	Populate global values array with defaults when the page is in 'NEW' state
	Notes:
		PLANNED_DELIVERY_DATE  is fetched from PRODUCT_STEP table. PLANNED_END_DATE for the root step of
		that order
		BASE_COST is calculated based upon the information entered in the Financials tab
		AGENT_COMMISSION and ORG_COMMISSION are fetched based upon the information entered in the
		Financials tab
*/
function new_values()
{
	global $glb_arr_values;

	$glb_arr_values['SINGLE_ORDER_ID'] 		= -1;
	$glb_arr_values['NAME'] 				= '';
	$glb_arr_values['CODE']					= '';
	$glb_arr_values['STATUS']				= 'OPEN';
	$glb_arr_values['GEN_ORDER_ID']			= -1;
	$glb_arr_values['SUPPLIER_ID'] 			= -1;
	$glb_arr_values['PRODUCT_ID']   		= -1;
	$glb_arr_values['CLIENT_ORDER_ID']  	= '';
	$glb_arr_values['CLIENT_PRODUCT_CODE'] 	= '';
	$glb_arr_values['PLANNED_DELIVERY_DATE']= '';
	$glb_arr_values['PO_DATE']				= '';
	$glb_arr_values['PO_NUMBER']			= '';
	$glb_arr_values['PAYMENT_INSTRUCTIONS'] = '';
	$glb_arr_values['COMMENTS'] 			= '';
}

/*
	Get values from submitted form and populate global values array
*/
function post_values()
{
	global $glb_arr_values, $single_order_id;

	$glb_arr_values['SINGLE_ORDER_ID'] 		= $single_order_id ;
	$glb_arr_values['NAME']					= isset($_POST['NAME']) ? $_POST['NAME'] : "";
	$glb_arr_values['CODE'] 				= isset($_POST['CODE']) ? $_POST['CODE'] : "";
	$glb_arr_values['STATUS']				= isset($_POST['STATUS']) ? $_POST['STATUS'] : "";
	$glb_arr_values['GEN_ORDER_ID'] 		= isset($_POST['GEN_ORDER_ID']) ? $_POST['GEN_ORDER_ID'] : 0;
	$glb_arr_values['SUPPLIER_ID'] 			= isset($_POST['SUPPLIER_ID']) ? $_POST['SUPPLIER_ID'] : 0;
	$glb_arr_values['CLIENT_ORDER_ID'] 	    = isset($_POST['CLIENT_ORDER_ID']) ? $_POST['CLIENT_ORDER_ID'] : "";
	$glb_arr_values['CLIENT_PRODUCT_CODE'] 	= isset($_POST['CLIENT_PRODUCT_CODE']) ? $_POST['CLIENT_PRODUCT_CODE'] : "";
	$glb_arr_values['PLANNED_DELIVERY_DATE']= isset($_POST['PLANNED_DELIVERY_DATE']) ? $_POST['PLANNED_DELIVERY_DATE'] : "";
	$glb_arr_values['PO_DATE'] 				= isset($_POST['PO_DATE']) ? $_POST['PO_DATE'] : "";
	$glb_arr_values['PO_NUMBER'] 			= isset($_POST['PO_NUMBER']) ? $_POST['PO_NUMBER'] : "";
	$glb_arr_values['PAYMENT_INSTRUCTIONS'] = isset($_POST['PAYMENT_INSTRUCTIONS']) ? $_POST['PAYMENT_INSTRUCTIONS'] : "";
	$glb_arr_values['COMMENTS'] 			= isset($_POST['COMMENTS']) ? $_POST['COMMENTS'] : "";
}

/*
	Function: get_record()
	Desc:
		If single_order_id < 1 then this is a new record. Populate with defaults.
		If single_order_id > 1 get record from database and populate values global array
 	Params:
    	None
   	Returns:
		Nothing
*/
function get_record()
{
	global $glb_arr_values, $single_order_id;

	if (1 > $glb_arr_values['SINGLE_ORDER_ID']) {
		new_values();
		return;
	}

	$sql = generate_select_single_order_sql();
	$result = mysql_query($sql) or die(mysql_error());
	if (!$result) return mysql_error();
	$row = mysql_fetch_array($result);

	// Populate values array
	$glb_arr_values['NAME'] 				= mysql_decode($row['FRIENDLY_NAME']);
	$glb_arr_values['CODE'] 				= mysql_decode($row['CODE']);
	$glb_arr_values['STATUS']				= mysql_decode($row['STATUS']);
	$glb_arr_values['BUYER_ID'] 			= $row['BUYER_ID'];
	$glb_arr_values['BUYER_NAME'] 			= mysql_decode($row['BUYER_NAME']);
	$glb_arr_values['SUPPLIER_ID'] 			= $row['SUPPLIER_ID'];
	$glb_arr_values['SUPPLIER_NAME'] 		= mysql_decode($row['SUPPLIER_NAME']);
	$glb_arr_values['GEN_ORDER_ID']			= $row['GEN_ORDER_ID'];
	$glb_arr_values['PRODUCT_ID'] 			= mysql_decode($row['PRODUCT_ID']);
	$glb_arr_values['CLIENT_ORDER_ID'] 		= mysql_decode($row['CLIENT_ORDER_ID']);
	$glb_arr_values['CLIENT_PRODUCT_CODE'] 	= mysql_decode($row['CLIENT_PRODUCT_CODE']);
	$glb_arr_values['PLANNED_DELIVERY_DATE'] = mysql_decode($row['PLANNED_DELIVERY_DATE']);
	$glb_arr_values['PO_DATE']				 = mysql_decode($row['PO_DATE']);
	$glb_arr_values['PO_NUMBER'] 			 = mysql_decode($row['PO_NUMBER']);
	$glb_arr_values['PAYMENT_INSTRUCTIONS']  = mysql_decode($row['PAYMENT_INSTRUCTIONS']);
	$glb_arr_values['COMMENTS'] 			 = mysql_decode($row['COMMENTS']);
	$glb_arr_values['COMMISSION'] 			 = mysql_decode($row['COMMISSION']);
	$glb_arr_values['IS_COMMISSION_VALUE'] 	 = mysql_decode($row['IS_COMMISSION_VALUE']);
	$glb_arr_values['AGENT_COMMISSION'] 	 = mysql_decode($row['AGENT_COMMISSION']);
	$glb_arr_values['IS_AGENT_COMMISSION_VALUE'] = mysql_decode($row['IS_AGENT_COMMISSION_VALUE']);
	$glb_arr_values['BASE_COST']			= mysql_decode($row['BASE_COST']);
	$glb_arr_values['CURRENCY_NAME']		= mysql_decode($row['CURRENCY_NAME']);
	$glb_arr_values['DATE_CREATED'] 		= mysql_decode($row['DATE_CREATED']);

	mysql_free_result($result);

	// Get Organization comission percentage
	if ($glb_arr_values['IS_COMMISSION_VALUE'] == 1) {
		$glb_arr_values['ORG_COMMISSION_PCENT'] = get_sin_order_commission_pcent(
												   $glb_arr_values['BASE_COST'], $glb_arr_values['COMMISSION']);
		$glb_arr_values['ORG_COMMISSION_VALUE'] = $glb_arr_values['COMMISSION'];
	}
	else { // it's already in percentage
		$glb_arr_values['ORG_COMMISSION_PCENT'] = $glb_arr_values['COMMISSION'];
		$glb_arr_values['ORG_COMMISSION_VALUE'] = get_sin_order_commission_value(
												   $glb_arr_values['BASE_COST'], $glb_arr_values['COMMISSION']);
	}
	// Get Agent comission percentage
	if ($glb_arr_values['IS_AGENT_COMMISSION_VALUE'] == 1) {
		$glb_arr_values['AGENT_COMMISSION_PCENT_OF_ORG'] = get_sin_order_commission_pcent(
													       $glb_arr_values['ORG_COMMISSION_VALUE'], $glb_arr_values['AGENT_COMMISSION']);
		$glb_arr_values['AGENT_COMMISSION_VALUE']		  = $glb_arr_values['AGENT_COMMISSION'];
	}
	else { // it's already in percentage
		$glb_arr_values['AGENT_COMMISSION_PCENT_OF_ORG'] = $glb_arr_values['AGENT_COMMISSION'];
		$glb_arr_values['AGENT_COMMISSION_VALUE']         = get_sin_order_commission_value(
												           $glb_arr_values['ORG_COMMISSION_VALUE'], $glb_arr_values['AGENT_COMMISSION']);
	}
	// Calculate Agent Commission Value as a percent of the whole base cost
	$glb_arr_values['AGENT_COMMISSION_PCENT_OF_WHOLE'] = get_sin_order_commission_pcent(
													     $glb_arr_values['BASE_COST'], $glb_arr_values['AGENT_COMMISSION_VALUE']);
}

/****************************************
	SQL Generation functions
*****************************************/
/*
	Generates select SQL for Single order
*/
function generate_select_single_order_sql()
{
	global $glb_arr_values;

	$select_sql = "SELECT single_order.SINGLE_ORDER_ID AS SINGLE_ORDER_ID , single_order.CODE AS CODE ,
						  single_order.STATUS AS STATUS , single_order.FRIENDLY_NAME AS FRIENDLY_NAME ,
						  single_order.GEN_ORDER_ID  AS GEN_ORDER_ID, gen_order.BUYER_ID AS BUYER_ID,
						  buyer.NAME AS BUYER_NAME, single_order.SUPPLIER_ID AS SUPPLIER_ID ,
						  supplier.NAME AS SUPPLIER_NAME, single_order.PRODUCT_ID AS PRODUCT_ID ,
						  single_order.CLIENT_ORDER_ID AS CLIENT_ORDER_ID , single_order.CLIENT_PRODUCT_CODE AS CLIENT_PRODUCT_CODE ,
						  currency.CODE AS CURRENCY_NAME, production_step.PLANNED_END_DATE AS PLANNED_DELIVERY_DATE,
						  SUM(single_order.UNIT_PRICE * IF(production_step.PLANNED_END_QTY IS NULL, 0, production_step.PLANNED_END_QTY))
						  AS BASE_COST, single_order.PO_DATE AS PO_DATE , single_order.PO_NUMBER AS PO_NUMBER,
               			  single_order.PAYMENT_INSTRUCTIONS AS PAYMENT_INSTRUCTIONS,  single_order.COMMENTS AS COMMENTS,
						  single_order.COMMISSION, single_order.IS_COMMISSION_VALUE, single_order.AGENT_COMMISSION,
						  single_order.IS_AGENT_COMMISSION_VALUE, single_order.DATE_CREATED AS DATE_CREATED
					FROM single_order  INNER JOIN gen_order ON single_order.GEN_ORDER_ID = gen_order.GEN_ORDER_ID
                                       INNER JOIN party AS buyer ON gen_order.BUYER_ID = buyer.PARTY_ID
                                       INNER JOIN party AS supplier ON single_order.SUPPLIER_ID = supplier.PARTY_ID
                                       LEFT JOIN currency ON currency.CURRENCY_ID = gen_order.CURRENCY_ID
                                       LEFT JOIN production_step on single_order.SINGLE_ORDER_ID  = production_step.SINGLE_ORDER_ID
 					WHERE (IF(production_step.PARENT_ID IS NULL, -1, production_step.PARENT_ID) = -1)	AND
                          (single_order.SINGLE_ORDER_ID=".$glb_arr_values['SINGLE_ORDER_ID'].")  AND (single_order.STATE='ACTIVE' )
				    GROUP BY single_order.SINGLE_ORDER_ID  " ;
	return $select_sql;
}

/*
	Generates insert SQL for Single Order

	Note: Product related fields are inserted from the Product page
		  Finance related fields are inserted from the Financials page
		  Delivery Date is inserted as a row in the PRODUCTION_STEP table for the root step belonging
		  to this order
		  '".format_date($glb_arr_values['PLANNED_DELIVERY_DATE'])."',
*/
function generate_insert_single_order_sql()
{
	global $glb_arr_values;

	$insert_sql = "INSERT INTO single_order
				  (STATE, CODE, STATUS, FRIENDLY_NAME, GEN_ORDER_ID, SUPPLIER_ID, CLIENT_ORDER_ID,
				   CLIENT_PRODUCT_CODE, PO_DATE, PO_NUMBER, PAYMENT_INSTRUCTIONS, COMMENTS, DATE_CREATED)
				   VALUES (	'ACTIVE',
					'".mysql_encode(get_new_code('SINGLE_ORDER'))."',
					".$glb_arr_values['STATUS'].",
					'".mysql_encode($glb_arr_values['NAME'])."',
					".$glb_arr_values['GEN_ORDER_ID'].",
					".$glb_arr_values['SUPPLIER_ID'].",
					'".mysql_encode($glb_arr_values['CLIENT_ORDER_ID'])."',
					'".mysql_encode($glb_arr_values['CLIENT_PRODUCT_CODE'])."',
					'".format_date($glb_arr_values['PO_DATE'])."',
					'".mysql_encode($glb_arr_values['PO_NUMBER'])."',
					'".mysql_encode($glb_arr_values['PAYMENT_INSTRUCTIONS'])."',
					'".mysql_encode($glb_arr_values['COMMENTS'])."',
					   NOW())";

	return $insert_sql;
}

/*
	Generates update SQL for Single Prder
*/

function generate_update_single_order_sql()
{
	global $glb_arr_values;

	$update_sql =  "UPDATE single_order SET

					STATUS = " .mysql_encode( $glb_arr_values['STATUS']).",
					FRIENDLY_NAME = '" .mysql_encode( $glb_arr_values['NAME'])."',
					GEN_ORDER_ID = " .$glb_arr_values['GEN_ORDER_ID'].",
					SUPPLIER_ID = ".$glb_arr_values['SUPPLIER_ID'].",
					CLIENT_ORDER_ID = '" .mysql_encode( $glb_arr_values['CLIENT_ORDER_ID'])."',
					CLIENT_PRODUCT_CODE = '" .mysql_encode( $glb_arr_values['CLIENT_PRODUCT_CODE'])."',
					PO_DATE = '". format_date($glb_arr_values['PO_DATE'])."',
					PO_NUMBER = '" .mysql_encode( $glb_arr_values['PO_NUMBER'])."',
					PAYMENT_INSTRUCTIONS = '" .mysql_encode( $glb_arr_values['PAYMENT_INSTRUCTIONS'])."',
					COMMENTS = '" .mysql_encode( $glb_arr_values['COMMENTS'])."'

					WHERE SINGLE_ORDER_ID=".$glb_arr_values['SINGLE_ORDER_ID'];
	return $update_sql;
}

/*
	Generates select contacts SQL
*/
function generate_select_single_order_contacts_sql ()
{
	global $single_order_id;

	$select_sql =  "SELECT employee.PARTY_ID, employee.NAME AS CONTACT_NAME, employee.POSITION,
					employer.Name AS EMPLOYER_NAME, employee.ADDRESS1, employee.ADDRESS2, employee.CITY,
					employee.PROVINCE, employee.POSTAL_CODE, employee.COUNTRY_ID, employee.TEL,
					employee.MOBILE, employee.EMAIL
				   	FROM single_order_rel_party INNER JOIN party AS employee
				   				ON single_order_rel_party.party_id = employee.party_id
             			LEFT JOIN party AS employer ON employee.employer_id = employer.party_id
				   	WHERE single_order_rel_party.SINGLE_ORDER_ID = ". $single_order_id;
	return $select_sql;
}

function delete_single_order_contact($single_order_id, $contact_id)
{
	if ($contact_id > 0 ) {
		$sql = "DELETE FROM single_order_rel_party WHERE SINGLE_ORDER_ID =".$single_order_id.
													 " AND PARTY_ID =". $contact_id;
		$result = mysql_query($sql) or die(mysql_error());
	}
}
/*
	Insert single order delivery date into PRODUCTION_STEP table
	Insert the single order ID, the planned_end_date, and -1 for the parent ID since
	this is the production summary for the single order
*/
function insert_sin_order_delivery_date ($int_single_order_id, $str_delivery_date)
{
	if ($int_single_order_id > 0) {
		$sql = "INSERT INTO production_step (SINGLE_ORDER_ID, PLANNED_END_DATE, PARENT_ID, NAME)
              	VALUES (" .$int_single_order_id. ",'" .format_date($str_delivery_date)."', -1, 'Production Summary')";
		$result = mysql_query($sql) or die(mysql_error());
		if (!$result) return mysql_error();
	}
}
/*
	Update single order delivery date
*/
function update_sin_order_delivery_date ($int_single_order_id, $str_delivery_date)
{
	if ($int_single_order_id > 0) {
		$sql = "UPDATE production_step
				SET PLANNED_END_DATE    = '".format_date($str_delivery_date)."'
				WHERE SINGLE_ORDER_ID = " .$int_single_order_id.
				" AND  PARENT_ID = -1 ";
		$result = mysql_query($sql) or die(mysql_error());
		if (!$result) return mysql_error();
	}
}

/*
	Update the General Order ID that this Single Order belongs to in invoice_rel_order table
	if there are any entries for this SO.
	There will be entries if there are any milestones selected as invoice line items in this SO.
	This is done for the case of changing the GO that the SO belongs to. This is done to
	to maintain data integerity.
*/
function update_invoice_rel_order ($int_single_order_id, $int_gen_order_id)
{
	if (($int_single_order_id > 0) && ($int_gen_order_id > 0)) {
		$sql = "UPDATE invoice_rel_order
				SET GEN_ORDER_ID      = " .$int_gen_order_id. "
				WHERE SINGLE_ORDER_ID = " .$int_single_order_id;
		$result = mysql_query($sql) or die(mysql_error());
		if (!$result) return mysql_error();
	}
}

/*
	Check if there are any milestones belonging to this order selected as line items in an invoice
	A quick lookup in the invoice_rel_order table is enough to determine that.
*/
function is_any_milestone_lineitem($single_order_id)
{
	global $invoice_number;

	$existence = false;
	$sql 	   = "SELECT invoice.NUMBER
				  FROM invoice_rel_order INNER JOIN invoice ON invoice_rel_order.INVOICE_ID = invoice.INVOICE_ID
				  WHERE invoice_rel_order.SINGLE_ORDER_ID = ".$single_order_id;
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
	Check if supplier is in additional costs of this single order or the general order it belongs to
*/
function is_supplier_in_add_costs($single_order_id)
{
	global $glb_arr_values;

	$existence    = false;
	$gen_order_id = get_sin_order_gen_order_id($single_order_id);
	$sql 	   = "SELECT additional_cost.ADDITIONAL_COST_ID
				  FROM additional_cost
				  WHERE (additional_cost.SINGLE_ORDER_ID= ".$single_order_id."
				  OR additional_cost.GEN_ORDER_ID= ".$gen_order_id.")
				  AND (additional_cost.PAYOR_ID = ".$glb_arr_values['SUPPLIER_ID']."
				  OR additional_cost.PAYEE_ID = ".$glb_arr_values['SUPPLIER_ID'].") ";
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
	global $single_order_id, $supplier_in_add_costs;

	$supplier_in_add_costs = is_supplier_in_add_costs($single_order_id);
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
	$sql = generate_select_single_order_contacts_sql();
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
					referrer_id=".$glb_arr_values["SINGLE_ORDER_ID"]."&referrer_type=".entity_SINGLE_ORDER.
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
			$name  		= htmlspecialchars(mysql_decode($row['CONTACT_NAME']));
			$country	= get_country_name($row['COUNTRY_ID']) ;

			$markup .= "<tr class=\"contacts_line" .($i%2). "\">";

			if ($state == state_EDIT ) {
			$markup .= "<td class=\"small_text\">
						<a href=\"javascript:void subEntityDelete('CONTACT',". $pk .");\">
				    	<img src=\"images/b.remove.png\" alt=\"Remove\" title=\"Remove\"/></a></td>\r\n";
			}
			$markup .= "<td>". $name . "</td>";
			$markup .= "<td>"	.$row['POSITION']."</td>";
			$markup .= "<td>"	.$row['EMPLOYER_NAME']."</td>";
			$markup .= "<td>"	.$row['ADDRESS1']. " " .$row['ADDRESS2']. " "
								.$row['CITY']. " " .    $row['PROVINCE'] . " "
								.$row['POSTAL_CODE'] . " ". $country ."</td>";
			$markup .= "<td>"	.$row['TEL']."</td>";
			$markup .= "<td>"	.$row['MOBILE']."</td>";
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
	Generates standard form header for single order
*/
function generate_form_header()
{
	global $glb_arr_values, $state, $sub_entity_op, $sub_entity_name, $sub_entity_id;

	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_single_order\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"SINGLE_ORDER_ID\" name=\"SINGLE_ORDER_ID\" value=\"" . $glb_arr_values["SINGLE_ORDER_ID"] . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"CODE\" name=\"CODE\" value=\"" . $glb_arr_values["CODE"] . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_op\" name=\"sub_entity_op\" value=\"" . $sub_entity_op . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_name\" name=\"sub_entity_name\" value=\"" . $sub_entity_name . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_id\" name=\"sub_entity_id\" value=\"" . $sub_entity_id . "\"/>\r\n";
	return $str_frm_header;
}


// Set page title and include body header
if ($state != state_NEW ) {
	$page_title = format_sin_order_name($glb_arr_values["SINGLE_ORDER_ID"]) . " :: Profile";
}
else {
	$page_title = "Single Order :: New";
}

$table_name = "single_order";
require_once("body_header.php");
?>

<!-- JavaScript Section -->
<script type="text/javascript"><!--

var hasMilestonesAsLineItems = <?= $has_milestones_as_line_items ? 1 : 0 ?>;
var invoiceNumber = '<?= $invoice_number ?>';
var supplierInAddCosts = <?= $supplier_in_add_costs ? 1 : 0 ?>;

function ctlChange(ctl, originalCtlValue, ctlName)
{
	if (hasMilestonesAsLineItems) {
		alert ("This order contains milestones that are selected as line items in Invoice #: " + invoiceNumber + ". Please remove all milestones selected as line items before changing the " + ctlName + ".");
		ctl.value = originalCtlValue;
		return;
	}
	if (supplierInAddCosts){
		alert ("This Supplier is involved in the additional costs for this Single Order and/or the General Order it belongs to. Please remove all additional costs entries involving this Supplier before changing it.");
		ctl.value = originalCtlValue;
		return;
	}
}

function entitySave()
{
	frm = document.frm_single_order;
	if (frm.NAME.value.length < 1 ) {
		alert ("You must enter a name for this Single Order");
		return;
	}
	if (frm.GEN_ORDER_ID.value <= 0 ) {
		alert ("You must associate a General Order with this Single Order");
		return;
	}
	if (frm.SUPPLIER_ID.value <= 0 ) {
	alert ("You must associate a Supplier with this Single Order");
	return;
	}
	frm.state.value = statePROCESS;
	frm.submit();
}

function entityEdit()
{
	frm = document.frm_single_order;
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
	frm = document.frm_single_order;
	if (frm.SINGLE_ORDER_ID.value == -1) {
		frm.state.value = stateNEW;
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
		frm = document.frm_single_order;
		frm.state.value			  = statePROCESS;
		frm.sub_entity_op.value   = opcodeDELETE;
		frm.sub_entity_name.value = strName;
		frm.sub_entity_id.value   = intID;
		frm.submit();
	}
}

function resubmitForm()
{
	var frm = document.frm_single_order;
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
				echo "SINGLE ORDER :: ". format_sin_order_name($glb_arr_values["SINGLE_ORDER_ID"]);
			}
			else {
				echo "SINGLE ORDER :: New";
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
				echo generate_single_order_tabs(7, $glb_arr_values['SINGLE_ORDER_ID'], $state);
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
						<?php if (isset($errors['error'])) echo "<em class=\"error\">" . $errors['error'] . "</em>"; ?>
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
				</td>
				</tr>

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
				<td width="20%"><b>Status:</b></td>
				<td>
				<?php
					if ($state == state_VIEW ) {
						echo $glb_arr_values['STATUS'];
						echo "<a href=\"single_order_prod_mgmt.php?single_order_id=".$glb_arr_values['SINGLE_ORDER_ID']."\">";
						echo "<br/>Overall Process: ".get_percent_complete($glb_arr_values['SINGLE_ORDER_ID'], entity_SINGLE_ORDER). " complete".
							 "<br/>Production Target: ".get_prod_target_pcent_complete($glb_arr_values['SINGLE_ORDER_ID']). " complete</a>";
					}
					else{
						echo generate_dd_order_status_input ($glb_arr_values['STATUS']);
					}
				?>
				</td>
				</tr>
				<?php if ($state != state_NEW ) { ?>
				<tr>
					<td><b>Single Order ID:</b></td>
					<td><?= $glb_arr_values['CODE'];?></td>
					<td><b>Creation Date:</b></td>
					<td><?= format_date_reverse($glb_arr_values['DATE_CREATED']);?></td>
				</tr>
				<?php } ?>
				<tr>
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
				<?php if ($state != state_NEW ) { ?>
				<td><b>Base Cost & Commissions:</b></td>
				<td>
				<?php
						if ($state == state_VIEW) {
							echo "<a href=\"single_order_financials.php?single_order_id=".$glb_arr_values['SINGLE_ORDER_ID']."&state=".state_VIEW."\">";
							echo $glb_arr_values['CURRENCY_NAME']."&nbsp;".$glb_arr_values['BASE_COST']." (".
								 $glb_arr_values['ORG_COMMISSION_PCENT']."% Organization,".
								 $glb_arr_values['AGENT_COMMISSION_PCENT_OF_ORG']. "% Agent)";
								 //$glb_arr_values['AGENT_COMMISSION_PCENT_OF_WHOLE']. "% Agent)";
							echo "</a>";
						}
						else {
							echo $glb_arr_values['CURRENCY_NAME']."&nbsp;".$glb_arr_values['BASE_COST']."(".
								 $glb_arr_values['ORG_COMMISSION_PCENT']."% Organization,".
								 $glb_arr_values['AGENT_COMMISSION_PCENT_OF_ORG']. "% Agent)";
						}
				?>
				</td>
				<?php } ?>
				</tr>
				<tr>

				<td><b>Client Product Code:</b></td>
				<td>
				<?php
					if ($state == state_VIEW ) {
						echo $glb_arr_values['CLIENT_PRODUCT_CODE'];
					}
					else{
						echo generate_text_input ("formtext_stdwidth", "CLIENT_PRODUCT_CODE", $glb_arr_values['CLIENT_PRODUCT_CODE']);
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
				<td><b>General Order ID:<?= generate_required_superscript($state);?></b></td>
				<td colspan="3">
				<?php
					if ($state == state_VIEW ) {
						echo "<a href=\"search_order.php?entity=".entity_SINGLE_ORDER."&referrer=".entity_GEN_ORDER."&referrer_id=".$glb_arr_values['GEN_ORDER_ID']."\">";
						echo format_gen_order_name($glb_arr_values['GEN_ORDER_ID']);
						echo  "</a>";
					}
					else{
						echo generate_dd_gen_order_input($glb_arr_values['GEN_ORDER_ID']);
					}
				?>
				<?php if (isset($errors['GEN_ORDER_ID'])) echo "<br/><em class=\"error\">" . $errors['GEN_ORDER_ID'] . "</em>"; ?>
				</td>
				</tr>
				<tr>
				<td><b>Supplier Name:<?= generate_required_superscript($state);?></b></td>
				<td>
				<?php
					if ($state == state_VIEW ) {
						echo "<a href=\"search_order.php?entity=".entity_SINGLE_ORDER."&referrer=".entity_SUPPLIER."&referrer_id=".$glb_arr_values['SUPPLIER_ID']."\">";
						echo $glb_arr_values['SUPPLIER_NAME'];
						echo  "</a>";
					}
					else{
						echo generate_dd_supplier_input($glb_arr_values['SUPPLIER_ID'],
						'ctlChange(document.frm_single_order.SUPPLIER_ID, '.$glb_arr_values['SUPPLIER_ID'].', \'Supplier\')');
					}
				?>
				<?php if (isset($errors['SUPPLIER_ID'])) echo "<br/><em class=\"error\">" . $errors['SUPPLIER_ID'] . "</em>"; ?>
				</td>
				</tr>
				<?php if ($state != state_NEW ) { ?>
				<tr>
					<td><b>Buyer Name:</b></td>
					<td><?php
						if ($state == state_VIEW ) {
							echo "<a href=\"search_order.php?entity=".entity_GEN_ORDER."&referrer=".entity_BUYER."&referrer_id=".$glb_arr_values['BUYER_ID']."\">";
							echo $glb_arr_values['BUYER_NAME'];
							echo  "</a>";
						}
						else {
							echo $glb_arr_values['BUYER_NAME'];
						}
						?>
					</td>
				</tr>
				<?php } ?>
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
				<tr><td valign="top"><b>Payment Instructions:</b></td>
				<td colspan="3">
				<?php if ( $state == state_VIEW ) {
						echo '<span class="contrack_notes">';
						echo $glb_arr_values['PAYMENT_INSTRUCTIONS'];
						echo '</span>';
						}
						else{
						echo generate_textarea_input ("PAYMENT_INSTRUCTIONS", 5, 70, $glb_arr_values['PAYMENT_INSTRUCTIONS']);
						}
				?>
				</td></tr>
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
					echo show_attachments($table_name, $single_order_id);
					echo show_contacts();
				?>
			</table>
	</tr>

</table>
<?php
include("body_footer.php");
?>