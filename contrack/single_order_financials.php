	
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
/***************************************************************************************************
  	Single Order (SO) Financials page

	This page allows the user to:
		1- Enter quantity, unit type, and price for the single order
			Quantity goes to PLANNED_END_QTY field in production_step table
			unit type goes to END_UNIT field in production_step table for this single order's root step
		2- Enter commission payor, type and value of commission, and agent commission
		3- Create/Edit/View Additional Costs
		4- View the SO finances and payment matrix 
	
	It has three  states: VIEW, EDIT,  and PROCESS
	It's in the PROCESS state when an additional cost has been deleted
	
	Payment Matrix Logic is explained on top of the function called 'show_single_order_payment_matrix()'
****************************************************************************************************/
require_once("db_utils.php");
require_once("general_utils.php");
require_once("order_utils.php");
require_once( "patUser-2.2.3/prepend.php" );
require_once("permissions.php");


init_page();
process_state();

/*
	Page Initialization
	- Initialize any global variables
	- Authenticate
	- Get all parameters passed to the page
	- Validate permissions for current user
	- Check for any errors passed to the page	
*/
function init_page()
{
	global $user;
		
	// Authenticate
	if (! ($user->isAuthenticated())){
		deny_access();
	}		
	get_page_params ();
	validate_permissions();
	check_error();
}
/*
	Get all parameters passed to this page and format display string
*/
function get_page_params()
{
	global  $str_entity, $order_id, $state, $entity_id, $entity_op, $entity_type, $currency;
		
	$order_id 	 = isset($_GET['single_order_id']) ? $_GET['single_order_id'] : (isset($_POST['SINGLE_ORDER_ID']) ? $_POST['SINGLE_ORDER_ID'] : -1) ;
	$state	 	 = isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : state_VIEW) ;
	$entity_id 	 = isset($_GET['entity_id']) ? $_GET['entity_id'] : (isset($_POST['entity_id']) ? $_POST['entity_id'] : -1) ;
	$entity_op 	 = isset($_GET['entity_op']) ? $_GET['entity_op'] : (isset($_POST['entity_op']) ? $_POST['entity_op'] : -1) ;
	$entity_type = isset($_GET['entity_type']) ? $_GET['entity_type'] : (isset($_POST['entity_type']) ? $_POST['entity_type'] : -1) ;
	$currency    = get_sin_order_currency($order_id);
	$str_entity  = "single_order_financials";  // for fetching security permissions
}
/*
	Validates that the user has the required permissions to view the page in this state
	- Get User permissions
	- Set global flags
	- Get minimum permissions required
	- Evaluate the user permissions vis-a-vis the required permissions	
*/
function validate_permissions()
{
	global $user, $str_entity;
			
	$user_perms = get_user_permissions ($user, $str_entity);	
	//echo "user_perms = ";
	//var_dump($user_perms);	
	//echo "<br>";	
	set_user_perms($user_perms);
	set_required_perms(is_associated());	
	
	if (! is_authorized_view_own()) {
		do_unauthorized();
	}
}

/*
	Authorize based on which permissions are required to view the page and which permissions 
	the user has.	
*/
function is_authorized_view_own()
{
	global $allow_view_own;;
	
	$authorized = false;
	if ($allow_view_own || $allow_view_all || $allow_modify_all) {
		$authorized = true;
	}		
	return $authorized;
}

/*
	Determine whether the currently logged in user is associated with the entity
	being viewed.
*/
function is_associated()
{
	
}

/*
	This function is responsible for restricting the result set of the SQL query to only show 
	those entities the user is allowed to see
*/
function restrict_sql()
{

}

/*
	
*/
function process_state()
{
	global $state;
	if (empty($state)) { /* if $state is empty go to VIEW state*/
		view_form();
	} elseif ( $state == state_VIEW ) {
		view_form();
	} elseif (  $state == state_EDIT ) {
		edit_form();
	} elseif ( $state == state_PROCESS )  {
		process_form();
	}
	else  { 		// might be redundant but just in case...
		view_form();
	}
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

}

/*
	Process submitted form
*/
function process_form()
{
	global $entity_id, $entity_op, $entity_type, $order_id;
	
	if ( $entity_op == opcode_DELETE ) {		
		if ($entity_type == entity_ADDITIONAL_COST)  delete_additional_cost($entity_id);
		// Go back to same page after deleting
		$location_url = "Location: single_order_financials.php?single_order_id=".$order_id."&state=". state_EDIT;
		header($location_url);	
		exit;					
	}
	post_values();
	
	if ( validate_form() ) {
		// Update entries in single_order table
		update_sin_order_finances();
		// Update entries in production_step table
		update_sin_order_production_step(); 				 													
		// Redirect while setting state back to "VIEW" after having saved successfully
		$location_url = "Location: single_order_financials.php?single_order_id=".$order_id."&state=".state_VIEW;
		header($location_url);	
		exit;		
	} 
	else {  // validation unsuccessful
			$state = state_EDIT;	
	}
}

/*
	Validate form values
*/	
function validate_form() 
{
	global $glb_arr_values, $errors;
		
	if (count($errors) > 0)	{
		$errors['error'] = "There was a problem with the information entered. Please review the form and make necessary corrections.";
	}
	return count($errors) == 0;	
}	

/*
	Get values from submitted form and populate global values array
*/	
function post_values() 
{	
	global $glb_arr_values;
	
	$glb_arr_values['PLANNED_END_QTY']	  = isset($_POST['PLANNED_END_QTY']) ? $_POST['PLANNED_END_QTY'] : 0;
	$glb_arr_values['UNIT_ID'] 			  = isset($_POST['UNIT_ID']) ? $_POST['UNIT_ID'] : 0;
	$glb_arr_values['UNIT_PRICE'] 	 	  = isset($_POST['UNIT_PRICE']) ? $_POST['UNIT_PRICE'] : 0;
	$glb_arr_values['IS_BUYER_COMMISSIONER']  = isset($_POST['IS_BUYER_COMMISSIONER']) ? $_POST['IS_BUYER_COMMISSIONER'] : 0;
	$glb_arr_values['COMMISSION']		  = isset($_POST['COMMISSION']) ? $_POST['COMMISSION'] : 0;
	$glb_arr_values['IS_COMMISSION_VALUE']= isset($_POST['IS_COMMISSION_PCENT']) ? $_POST['IS_COMMISSION_PCENT'] : 0;
	$glb_arr_values['AGENT_COMMISSION']   = isset($_POST['AGENT_COMMISSION']) ? $_POST['AGENT_COMMISSION'] : 0;
	$glb_arr_values['IS_AGENT_COMMISSION_VALUE']= isset($_POST['IS_AGENT_COMMISSION_PCENT']) ? $_POST['IS_AGENT_COMMISSION_PCENT'] : 0;
}

/*
	Get all data items to be used in this page
*/	
function get_record() 
{
	global $glb_arr_values, $order_id;
	
	$sql = generate_select_single_order_financials_sql($order_id);	
	$result = mysql_query($sql) or die(mysql_error());	
	if (!$result) return mysql_error();
	$row = mysql_fetch_array($result);
	
	// Populate values array
	$glb_arr_values['PRODUCT_TYPE'] 		= mysql_decode($row['PRODUCT_TYPE']);
	$glb_arr_values['PLANNED_END_QTY'] 		= $row['PLANNED_END_QTY'];
	$glb_arr_values['UNIT_ID'] 		 		= $row['UNIT_ID'];
	$glb_arr_values['UNIT_NAME'] 			= mysql_decode($row['UNIT_NAME']);
	$glb_arr_values['UNIT_PRICE'] 	 		= $row['UNIT_PRICE'];
	$glb_arr_values['BASE_COST'] 	 		= $row['BASE_COST'];
	$glb_arr_values['BUYER_ID'] 	 		= $row['BUYER_ID'];
	$glb_arr_values['BUYER_NAME'] 	 		= $row['BUYER_NAME'];
	$glb_arr_values['BUYER_TYPE'] 	 		= $row['BUYER_TYPE'];
	$glb_arr_values['SUPPLIER_ID'] 	 		= $row['SUPPLIER_ID'];
	$glb_arr_values['SUPPLIER_NAME'] 		= $row['SUPPLIER_NAME'];
	$glb_arr_values['SUPPLIER_TYPE'] 		= $row['SUPPLIER_TYPE'];
	$glb_arr_values['IS_BUYER_COMMISSIONER']= $row['IS_BUYER_COMMISSIONER'];
	$glb_arr_values['COMMISSION']			= $row['COMMISSION'];
	$glb_arr_values['IS_COMMISSION_VALUE'] 	= $row['IS_COMMISSION_VALUE'];
	$glb_arr_values['AGENT_COMMISSION'] 	= $row['AGENT_COMMISSION'];
	$glb_arr_values['IS_AGENT_COMMISSION_VALUE'] = $row['IS_AGENT_COMMISSION_VALUE'];
}

/*
	Function: generate_select_single_order_financials_sql ( )
	Desc:  
		Select single orders financial snapshot for the general order
 	Params: 
		$order_id - SO ID for which we're getting the financials
		$optional_where_clause - For further filtering
   	Returns: 
		SQL Query string
*/	
function generate_select_single_order_financials_sql($single_order_id, $optional_where_clause='')
{	
	$select_sql = " SELECT single_order.FRIENDLY_NAME, single_order.CODE, single_order.CLIENT_ORDER_ID,
					product_type.NAME AS PRODUCT_TYPE, production_step.PLANNED_END_QTY, 
					production_step.END_UNIT_ID AS UNIT_ID, unit.NAME AS UNIT_NAME, single_order.UNIT_PRICE,
					buyer.PARTY_ID AS BUYER_ID, buyer.NAME AS BUYER_NAME, buyer.TYPE AS BUYER_TYPE,
					supplier.PARTY_ID AS SUPPLIER_ID, supplier.NAME AS SUPPLIER_NAME, supplier.TYPE AS SUPPLIER_TYPE,
					single_order.IS_BUYER_COMMISSIONER, single_order.COMMISSION, single_order.IS_COMMISSION_VALUE, 
					single_order.AGENT_COMMISSION, single_order.IS_AGENT_COMMISSION_VALUE,
					SUM(single_order.UNIT_PRICE * IF(production_step.PLANNED_END_QTY IS NULL, 0, production_step.PLANNED_END_QTY))
					AS BASE_COST					
					FROM single_order 
					INNER JOIN party AS supplier ON single_order.SUPPLIER_ID = supplier.PARTY_ID
					INNER JOIN gen_order ON gen_order.GEN_ORDER_ID = single_order.GEN_ORDER_ID
					INNER JOIN party AS buyer ON gen_order.BUYER_ID = buyer.PARTY_ID
					LEFT JOIN product ON single_order.PRODUCT_ID = product.PRODUCT_ID
					LEFT JOIN product_type ON product.PRODUCT_TYPE_ID = product_type.PRODUCT_TYPE_ID
					LEFT JOIN production_step ON single_order.SINGLE_ORDER_ID = production_step.SINGLE_ORDER_ID
					LEFT JOIN unit ON production_step.END_UNIT_ID = unit.UNIT_ID
					WHERE ";					
	$select_sql .= ($optional_where_clause=='' ? '' : $optional_where_clause);				
	$select_sql .= " (IF(production_step.PARENT_ID IS NULL, -1, production_step.PARENT_ID) = -1)
					AND single_order.SINGLE_ORDER_ID =  " .$single_order_id;
	$select_sql .= 	" GROUP BY single_order.SINGLE_ORDER_ID";
	return $select_sql;
}

/*
	Updates single_order table 
*/
function update_sin_order_finances()
{
	global $order_id, $glb_arr_values;

	$sql = "UPDATE single_order 
			SET IS_BUYER_COMMISSIONER   = ".$glb_arr_values['IS_BUYER_COMMISSIONER'].",
				COMMISSION    			= ".$glb_arr_values['COMMISSION'].",
				IS_COMMISSION_VALUE    	= ".$glb_arr_values['IS_COMMISSION_VALUE'].",
				AGENT_COMMISSION    	= ".$glb_arr_values['AGENT_COMMISSION'].",
				IS_AGENT_COMMISSION_VALUE = ".$glb_arr_values['IS_AGENT_COMMISSION_VALUE'].",
				UNIT_PRICE 				  = " .$glb_arr_values['UNIT_PRICE']."
			WHERE SINGLE_ORDER_ID = " .$order_id;	
	$result = mysql_query($sql) or die(mysql_error());			
	if (!$result) return mysql_error();
}

/*
	Updates production_step table 
*/	
function update_sin_order_production_step()
{
	global $order_id, $glb_arr_values;

	$sql = "UPDATE production_step 
			SET PLANNED_END_QTY  	= ".$glb_arr_values['PLANNED_END_QTY'].",
				END_UNIT_ID    		= ".$glb_arr_values['UNIT_ID']."
			WHERE SINGLE_ORDER_ID	= " .$order_id. 
			" AND  PARENT_ID = -1 ";
	$result = mysql_query($sql) or die(mysql_error());			
	if (!$result) return mysql_error();
}

/*
	Function: show_single_order_payment_matrix ( )
	Desc:  
		Shows the payment matrix for the General Order
 	Params: 
    	None
   	Returns: 
		HTML string
		
	Payments Categories:
		- Base cost of single orders
			Payor = Buyer  
			Payee = Supplier of the SO 	
		- Commission for a single order
			Payor = Buyer if buyer is commissioner, Supplier if supplier is commissioner
			Payee = Organization
		- Commission for all SO (Agent Commission)
			Payor = Organization
			Payee = Agent
		- Additional Costs
			Payor to Payee within the context of this General Order from additonal_cost table
			Could have any Party as the payor except the Agent
			Could have any Party as the payee except the Buyer
				
	Logic flow:
		a- Identify all payors for this GO:
			. Payors currently are: 1. Organization
									2. Buyer of this GO
									3. All Suppliers of this GO
									4. All External Parties that are financially involved
			. Put all those payors in a 2-dimensional array ($array_payors) where each row has the following
		   	  entries: PARTY_ID, TYPE, NAME								
		b- Identify all payees for this GO:  
			. Payees currently are: 1. Organization
									2. Agent of this GO
									3. All Suppliers of this GO
									4. All External Parties that are financially involved
		 	. Put all those payees in a 2-dimensional array ($array_payees) where each row has the following
		   	  entries: PARTY_ID, TYPE, NAME
		c- Create header row with all payors Types and Names as mentioned in (a)
			. Column 1 is always Organization
			. Column 2 is always Buyer
			. Columns 3 to 'n' are Supplier followed by External Parties
		d- Create table rows 
			. Row 1 is always Organization
			. Row 2 is always Agent
			. Rows 3 to 'n' are Supplier followed by External Parties
		e- Iterate through rows and for each cell identify its contents	based on type and ID of payor
			and payees according to the rules defined above in "Payment Categories"
*/
function show_single_order_payment_matrix()
{
	global $order_id, $currency, $total_paid, $total_received;
	
	$array_payors = array();
	$array_payees = array();	
	populate_payors_and_payees_arrays($array_payors, $array_payees, $order_id);
	// Initialize $total_paid Array
	for ($m=0; $m < count($array_payors); $m++)	
		$total_paid[$array_payors[$m]['PARTY_ID']] = 0;
	// Initialize $total_received array
	for ($n=0; $n < count($array_payees); $n++)	
		$total_received[$array_payees[$n]['PARTY_ID']] = 0;
	
	$markup = create_header_row($array_payors);
	// Walk through payees rows
	for ($i=0; $i < count($array_payees); $i++){
		$payee_id   = $array_payees[$i]['PARTY_ID'];
		$payee_name = $array_payees[$i]['NAME'];		
		$payee_type = $array_payees[$i]['TYPE'];
		// Skip Buyer as Payee. Buyer is never Payee according to the current Business Rules
		//if ($payee_type != entity_BUYER) {
			$markup .= '<tr class="con_table_line0"><td style="background-color:#999966;color:#fff;font-weight:bold">
						<strong>'.set_str_entity_type($payee_type).'::'.$payee_name.'</strong></td>';
		//}
		// Traverse grid
		for ($j=0; $j < count($array_payors); $j++) {
			$payor_id   = $array_payors[$j]['PARTY_ID'];
			$payor_name = $array_payors[$j]['NAME'];		
			$payor_type = $array_payors[$j]['TYPE'];
			// Skip Buyer as Payee and Agent as Payor
			//if (($payee_type != entity_BUYER) && ($payor_type != entity_AGENT)){
				$markup .= create_payee_row($order_id, $payee_id, $payee_type, $payor_id, $payor_type);
			//}
		}
		//if ($payee_type != entity_BUYER){
			$markup .='<td style="background-color:#999966;color:#000;font-weight:bold">'.
						$currency.' '.number_format($total_received[$payee_id], NUM_DEC_PLACES, '.',',').'</td></tr>';
		//}
	}	
	$markup .= create_footer_row($array_payors);  // Total Paid
	$markup .= '</table>';
	return $markup;
}

/*
	Based on $payee_type call appropriate functoid to generate row cells
*/
function create_payee_row($order_id, $payee_id, $payee_type, $payor_id, $payor_type)
{
	// Empty cell if they're the same	
	if (($payor_id == $payee_id) && ($payor_type == $payee_type)) {
		$markup = '<td style="text-align:center;">--</td>';
		return $markup;					
	}
	// Create row
	switch ($payee_type){
		case entity_INTERNAL:
		$markup = create_organization_payee_row($order_id, $payee_id, $payor_id, $payor_type);
		break;
		case entity_BUYER:
		$markup = create_buyer_payee_row($order_id, $payee_id, $payor_id, $payor_type);
		break;
		case entity_AGENT:
		$markup = create_agent_payee_row($order_id, $payee_id, $payor_id, $payor_type);
		break;
		case entity_SUPPLIER:
		$markup = create_supplier_payee_row($order_id, $payee_id, $payor_id, $payor_type);
		break;
		case entity_EXTERNAL:
		$markup = create_external_payee_row($order_id, $payee_id, $payor_id, $payor_type);
		break;
		default:
		$markup = '';
	}
	return $markup;
}

/*
	Create row for Organization as Payee 
*/
function create_organization_payee_row($order_id, $payee_id, $payor_id, $payor_type)
{
	global $total_paid, $total_received, $currency;
	
	switch ($payor_type){
		case entity_BUYER:
		// If buyer is commissioner; For each single order, get commission value and single order name 
		$markup = '<td>';
		$sql_where_clause = ' single_order.IS_BUYER_COMMISSIONER = 1 AND ';
		$sql    = generate_select_single_order_financials_sql($order_id, $sql_where_clause);	
		$result = mysql_query($sql) or die(mysql_error());
		while ($row = mysql_fetch_array($result)) {
			$title      = "Commission for Single Order: " .$row['FRIENDLY_NAME'].' ('. $row['CODE'].'/'. $row['CLIENT_ORDER_ID'].')';
			$commission = ($row['IS_COMMISSION_VALUE']==1 ? 
						   $row['COMMISSION'] : 
							get_sin_order_commission_value($row['BASE_COST'], $row['COMMISSION']));
			$markup .= '<span title="'.$title.'">'.$currency.' ' .
						number_format($commission, NUM_DEC_PLACES, '.', ',').'</span><br/>'; 	 
			$total_paid[$payor_id] 		+= $commission;
			$total_received[$payee_id]  += $commission; 	 
		}
		// Get all additional costs having organization as payee and this buyer as payor
		$markup .= create_additional_cost_markup($order_id, entity_SINGLE_ORDER, $payor_id, $payee_id);		
		$markup .= '</td>';
		break;
		case entity_SUPPLIER:
		// If supplier is commissioner; For each single order, get commission value and single order name 
		$markup = '<td>';
		$sql_where_clause = ' single_order.IS_BUYER_COMMISSIONER = 0 AND single_order.SUPPLIER_ID = '.$payor_id.' AND ';
		$sql    = generate_select_single_order_financials_sql($order_id, $sql_where_clause);	
		$result = mysql_query($sql) or die(mysql_error());
		while ($row = mysql_fetch_array($result)) {
			$title      = "Commission for Single Order: " .$row['FRIENDLY_NAME'].' ('. $row['CODE'].'/'. $row['CLIENT_ORDER_ID'].')';
			$commission = ($row['IS_COMMISSION_VALUE']==1 ? 
						   $row['COMMISSION'] : 
							get_sin_order_commission_value($row['BASE_COST'], $row['COMMISSION']));
			$markup .= '<span title="'.$title.'">'.$currency.' ' .
						number_format($commission, NUM_DEC_PLACES, '.', ',').'</span><br/>'; 
			$total_paid[$payor_id] 		+= $commission;
			$total_received[$payee_id]  += $commission; 	 
		}
		// Get all additional costs having organization as payee and this supplier as payor
		$markup .= create_additional_cost_markup($order_id, entity_SINGLE_ORDER, $payor_id, $payee_id);		
		$markup .= '</td>';
		break;
		case entity_AGENT:
		case entity_EXTERNAL:		
		// Get all additional costs having organization as payee and this external party as payor
		$markup  = '<td>';
		$markup .= create_additional_cost_markup($order_id, entity_SINGLE_ORDER, $payor_id, $payee_id);		
		$markup .= '</td>';
		break;
		default:
		$markup = '';	
	}
	return $markup;	
}

/*
	Create row for Buyer as Payee
*/
function create_buyer_payee_row($order_id, $payee_id, $payor_id, $payor_type)
{
	global $total_paid, $total_received;
	
	switch ($payor_type){
		case entity_INTERNAL:
		case entity_AGENT:
		case entity_SUPPLIER:
		case entity_EXTERNAL:
		$markup = '<td>';
		$markup .= create_additional_cost_markup($order_id, entity_SINGLE_ORDER, $payor_id, $payee_id);		
		$markup .= '</td>';		
		break;
		default:
		$markup = '';	
	}
	return $markup;
}

/*
	Create row for Agent as Payee
*/
function create_agent_payee_row($order_id, $payee_id, $payor_id, $payor_type)
{
	global  $total_paid, $total_received, $currency, $agent_commission_value; 

	switch ($payor_type){
		case entity_INTERNAL:
		$markup   = '<td>';
		$title    = "Agent Commission";
		$amount   = $agent_commission_value;
		$markup  .= '<span title="'.$title.'">'.$currency.' ' .
					  number_format($amount, NUM_DEC_PLACES, '.', ',').'</span><br/>'; 
		$total_paid[$payor_id] 		+= $amount;
		$total_received[$payee_id]  += $amount; 	 
		// Get any additional costs paid from Organization to this Agent
		$markup .= create_additional_cost_markup($order_id, entity_SINGLE_ORDER, $payor_id, $payee_id);
		$markup .= '</td>';
		break;
		// Get all additional costs having this Agent as payee and this [Buyer|Supplier|External] as payor
		case entity_BUYER:
		case entity_SUPPLIER:
		case entity_EXTERNAL:		
		$markup  = '<td>';
		$markup .= create_additional_cost_markup($order_id, entity_SINGLE_ORDER, $payor_id, $payee_id);		
		$markup .= '</td>';
		break;
		default:
		$markup = '';	
	}
	return $markup;
}

/*
	Create row for Supplier as Payee
*/
function create_supplier_payee_row($order_id, $payee_id, $payor_id, $payor_type)
{
	global $total_paid, $total_received, $currency;	
	
	switch ($payor_type){
		case entity_BUYER:
		// Get Base Costs for all Single Orders in this General Order bought by this Buyer and Delivered by this Supplier
		$markup = '<td>';
		$sql_where_clause = ' single_order.SUPPLIER_ID = '.$payee_id.' AND ';
		$sql    = generate_select_single_order_financials_sql($order_id, $sql_where_clause);	
		$result = mysql_query($sql) or die(mysql_error());
		while ($row = mysql_fetch_array($result)) {
			$title      = "Base Cost for Single Order: " .$row['FRIENDLY_NAME'].' ('. $row['CODE'].'/'. $row['CLIENT_ORDER_ID'].')';
			$base_cost = $row['BASE_COST'];
			$markup .= '<span title="'.$title.'">'.$currency.' ' .
						number_format($base_cost, NUM_DEC_PLACES, '.', ',').
						'</span><br/>'; 	 
			$total_paid[$payor_id] 		+= $base_cost;
			$total_received[$payee_id]  += $base_cost; 	
		}
		$markup .= create_additional_cost_markup($order_id, entity_SINGLE_ORDER, $payor_id, $payee_id);		
		$markup .= '</td>';
		break;
		// Get all additional costs having this Supplier as payee and [Organization|Supplier|External] as payor
		case entity_INTERNAL:
		case entity_AGENT:
		case entity_SUPPLIER:
		case entity_EXTERNAL:
		$markup = '<td>';
		$markup .= create_additional_cost_markup($order_id, entity_SINGLE_ORDER, $payor_id, $payee_id);		
		$markup .= '</td>';
		break;
		default:
		$markup = '';	
	}
	return $markup;
}

/*
	Create row for External Party as Payee
*/
function create_external_payee_row($order_id, $payee_id, $payor_id, $payor_type)
{
	global $total_paid, $total_received;
	
	switch ($payor_type){
		case entity_INTERNAL:
		case entity_BUYER:
		case entity_AGENT:
		case entity_SUPPLIER:
		case entity_EXTERNAL:
		$markup = '<td>';
		$markup .= create_additional_cost_markup($order_id, entity_SINGLE_ORDER, $payor_id, $payee_id);		
		$markup .= '</td>';		
		break;
		default:
		$markup = '';	
	}
	return $markup;
}
/*
	Populate payors and payees arrays for the passed General Order
*/
function populate_payors_and_payees_arrays(&$array_payors, &$array_payees, $order_id)
{
	// Organization
	$array_payors[0]['PARTY_ID'] = COMPANY_PARTY_ID;
	$array_payors[0]['NAME']     = get_party_name(COMPANY_PARTY_ID);
	$array_payors[0]['TYPE']     = entity_INTERNAL;	
	
	$array_payees[0]['PARTY_ID'] = COMPANY_PARTY_ID;
	$array_payees[0]['NAME']     = get_party_name(COMPANY_PARTY_ID);
	$array_payees[0]['TYPE']     = entity_INTERNAL;	
	// Buyer for Payors array. No Buyer for Payees array
	$buyer_row = get_sin_order_buyer_id_and_name($order_id);
	$array_payors[1]['PARTY_ID'] = $buyer_row['BUYER_ID'];
	$array_payors[1]['NAME']     = $buyer_row['BUYER_NAME'];
	$array_payors[1]['TYPE']     = entity_BUYER;

	$array_payees[1]['PARTY_ID'] = $buyer_row['BUYER_ID'] ;
	$array_payees[1]['NAME']     = $buyer_row['BUYER_NAME'];
	$array_payees[1]['TYPE']     = entity_BUYER;
	
	// Agent 
	$agent_row = get_sin_order_agent_id_and_name($order_id);
	$array_payors[2]['PARTY_ID'] = $agent_row['AGENT_ID'];
	$array_payors[2]['NAME']     = $agent_row['AGENT_NAME'];
	$array_payors[2]['TYPE']     = entity_AGENT;
	
	$array_payees[2]['PARTY_ID'] = $agent_row['AGENT_ID'];
	$array_payees[2]['NAME']     = $agent_row['AGENT_NAME'];
	$array_payees[2]['TYPE']     = entity_AGENT;
	
	// Supplier
	$supplier_row = get_sin_order_supplier_id_and_name($order_id);
	$array_payors[3]['PARTY_ID'] = $supplier_row['SUPPLIER_ID'];
	$array_payors[3]['NAME']     = $supplier_row['SUPPLIER_NAME'];
	$array_payors[3]['TYPE']     = entity_SUPPLIER;
		
	$array_payees[3]['PARTY_ID'] = $supplier_row['SUPPLIER_ID'];
	$array_payees[3]['NAME']     = $supplier_row['SUPPLIER_NAME'];
	$array_payees[3]['TYPE']     = entity_SUPPLIER;

	// External Parties that are financially involved 
	// (i.e. they're in the additional costs table either as payor or payee)
	$i = 4;
	$sql_externals = "SELECT DISTINCT 
					  	party.PARTY_ID AS EXT_PARTY_ID, party.NAME AS EXT_NAME
					  FROM party , additional_cost
					  WHERE party.TYPE = ".entity_EXTERNAL."  
					  	AND party.EMPLOYER_ID = 0 
						AND additional_cost.SINGLE_ORDER_ID = ".$order_id."
						AND ((additional_cost.PAYOR_ID = party.PARTY_ID) 
						  OR (additional_cost.PAYEE_ID = party.PARTY_ID))";
	$result = mysql_query($sql_externals) or die(mysql_error());	
	while ($row = mysql_fetch_array($result)) {
		$array_payors[$i]['PARTY_ID'] = $row['EXT_PARTY_ID'];
		$array_payors[$i]['NAME']     = $row['EXT_NAME'];
		$array_payors[$i]['TYPE']     = entity_EXTERNAL;		
		
		$array_payees[$i]['PARTY_ID'] = $row['EXT_PARTY_ID'];
		$array_payees[$i]['NAME']     = $row['EXT_NAME'];
		$array_payees[$i]['TYPE']     = entity_EXTERNAL;
		++$i;		
	}	
}

/*
	Generates standard form header for invoice
*/
function generate_form_header() 
{
	global $state, $order_id, $entity_id, $entity_op, $entity_type, $glb_arr_values ;
	
	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_financials\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"SINGLE_ORDER_ID\" name=\"SINGLE_ORDER_ID\" value=\"" . $order_id . "\"/>\r\n";			
	$str_frm_header .= "<input type=\"hidden\" id=\"entity_id\" name=\"entity_id\" value=\"" . $entity_id . "\"/>\r\n";			
	$str_frm_header .= "<input type=\"hidden\" id=\"entity_type\" name=\"entity_type\" value=\"" . $entity_type . "\"/>\r\n";			
	$str_frm_header .= "<input type=\"hidden\" id=\"entity_op\" name=\"entity_op\" value=\"" . $entity_op . "\"/>\r\n";			
	$str_frm_header .= "<input type=\"hidden\" id=\"IS_BUYER_COMMISSIONER\" name=\"IS_BUYER_COMMISSIONER\" value=\"" . $glb_arr_values['IS_BUYER_COMMISSIONER'] . "\"/>\r\n";			
	return $str_frm_header;
}

$page_title = format_sin_order_name($order_id) . " :: Financials"; 
include("body_header.php"); 
?>
<script type="text/javascript"><!--
var state = <?= ($state=="") ? 0 : $state ?>;

function submitForm()
{
	var frm = document.frm_financials;
	
	if (!validateNumeric('Quantity',frm.PLANNED_END_QTY.value)) return;
	if (!validateNumeric('Unit Price',frm.UNIT_PRICE.value)) return;
	if (!validateNumeric('Commission Amount',frm.COMMISSION.value)) return;
	if (!validateNumeric('Agent Commission Amount',frm.AGENT_COMMISSION.value)) return;
	
	var dropdownValue 	= frm.COMMISSIONER.options[frm.COMMISSIONER.selectedIndex].value;	
	var arrCommissioner = dropdownValue.split(',');	
	var commissionerID 	= arrCommissioner[0];
	var commissionerType= arrCommissioner[1];	
	frm.IS_BUYER_COMMISSIONER.value = (commissionerType == entityBUYER ? 1 : 0);
	frm.state.value = statePROCESS;
	frm.submit();
}

function entityDelete(entityType, intID) 
{
	if (confirm ('Are you sure you want to delete this additional cost?') ) {
		frm = document.frm_financials;		
		frm.state.value		   = statePROCESS;
		frm.entity_op.value    = opcodeDELETE;
		frm.entity_type.value  = entityType;
		frm.entity_id.value    = intID;		
		frm.submit();	
	}
}

function entityEdit() 
{
	frm = document.frm_financials;		
	frm.state.value = stateEDIT;
	frm.submit();	
}

function entityCancel() 
{	
	history.back();
}

function resubmitForm()
{
	var frm = document.frm_financials;
	frm.state.value = stateVIEW;
	frm.submit();
}
--></script>
<table border="0"  cellpadding="0" cellspacing="0">
<tr>
	<td colspan="3">
		<h4>
		<?php 
		if ($order_id == -1 ) {
			echo "New Single Order" ; 
		}
		else {
			echo "SINGLE ORDER" ." :: ". format_sin_order_name($order_id);  
		}				
		?>
		</h4>
	</td>
</tr>
<tr>  <!-- Tabs -->
	<td class = "tabs" colspan="3">
		<table cellspacing="0"> 
			<tr> 
			<?php 
			/*	Tab Generation 
				Pass the ordinal of the tab to be activated
			*/												
			echo generate_single_order_tabs(6, $order_id);																
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
					echo generate_button("button","Save","submitForm()");
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
	<tr>
	<td width="12%" style="vertical-align:top;"><strong>Base Cost:</strong></td>
		<td colspan = "4">
			<table>								
				<tr class="con_table_header">
					<td>Product Type</td>
					<td>Quantity</td>
					<td>Unit Type</td>
					<td>Unit Price</td>									
					<?php 
					if  ($state == state_VIEW) { 
						echo '<td>Value</td>';
					}
					?>	
				</tr>			
				<tr class="con_table_line0">
					<td><?= $glb_arr_values['PRODUCT_TYPE']?></td>
					<td>
					<?php
					if ($state == state_VIEW ) {
						echo format_qty($glb_arr_values['PLANNED_END_QTY']);
					}
					else{
						echo generate_text_input ("short_width", "PLANNED_END_QTY", $glb_arr_values['PLANNED_END_QTY']);								
					}
					?>
					</td>
					<td>
					<?php 
					if ($state == state_VIEW ) {
						echo $glb_arr_values['UNIT_NAME'];;
					}
					else{		
						echo generate_unit_input('UNIT_ID', $glb_arr_values['UNIT_ID']);						
					}
					?> 
					</td>
					<td>
					<?php 
					if ($state == state_VIEW ) {
						echo $currency.' '.number_format($glb_arr_values['UNIT_PRICE'], NUM_DEC_PLACES, '.', ',');
					}
					else{		
						echo $currency.' '. generate_text_input ("short_width", "UNIT_PRICE", $glb_arr_values['UNIT_PRICE']);
					}
					?> 
					</td>					
					<?php if  ($state == state_VIEW) { 
						echo '<td>';
						echo $currency.' '. number_format($glb_arr_values['BASE_COST'], NUM_DEC_PLACES,'.', ',');
						echo '</td>';
						}
					?>	
				</tr>						
			</table>
		</td>
	</tr>						
	<tr><td valign="top"><strong>Commission:</strong></td>
	<td colspan = "4">
		<table>								
			<tr class="con_table_header">
				<td>Paid By</td>
				<td>Amount</td>
				<td>Agent Commission Amount</td>
			</tr>			
			<tr class="con_table_line0">
				<td>
				<?php
				if($glb_arr_values['IS_BUYER_COMMISSIONER']) {
					$entity_type =  entity_BUYER;
					$entity_id 	 = $glb_arr_values['BUYER_ID'];
					$entity_name = $glb_arr_values['BUYER_NAME'];
				}
				else {
					$entity_type =  entity_SUPPLIER;
					$entity_id 	 = $glb_arr_values['SUPPLIER_ID'];
					$entity_name = $glb_arr_values['SUPPLIER_NAME'];
				}
				if ($state == state_VIEW ) {														
					echo "<a href=\"party.php?party_id=".$entity_id."&type=".$entity_type."&state=".state_VIEW."\">";
					echo set_str_entity_type($entity_type).'::'.$entity_name;
					echo  "</a>";
				}
				else{
					echo generate_dd_commission_payor_input($entity_id, $order_id);		
				}
				?>				
				</td>
				<td>
				<?php 
				if ($glb_arr_values['IS_COMMISSION_VALUE'] == 1) {
					$commission_pcent = get_sin_order_commission_pcent(
										$glb_arr_values['BASE_COST'], $glb_arr_values['COMMISSION']);
					$commission_value = $glb_arr_values['COMMISSION'];
				}
				else { // it's already in percentage
					$commission_pcent = $glb_arr_values['COMMISSION'];
					$commission_value = get_sin_order_commission_value(
									    $glb_arr_values['BASE_COST'], $glb_arr_values['COMMISSION']);
				}
				if ($state == state_VIEW ) {
					echo '<span title="Commission Amount as Percentage of Base Cost">';
					echo $currency.' '.number_format($commission_value, NUM_DEC_PLACES, '.', ','). ' ('.$commission_pcent.'%)';
					echo '</span>';
				}
				else{		
					echo '<span title="Specify Amount as Percentage of Base Cost or as Flat Value">';
					echo  generate_text_input ("short_width", "COMMISSION", $glb_arr_values['COMMISSION']);
					echo '&nbsp;';
					echo '<input type="radio" value="0" id="IS_COMMISSION_PCENT" name="IS_COMMISSION_PCENT" '. 
						  ($glb_arr_values['IS_COMMISSION_VALUE']? '':'checked="checked"') .'>%';
					echo '<input type="radio" value="1" id="IS_COMMISSION_PCENT" name="IS_COMMISSION_PCENT" '. 
						  ($glb_arr_values['IS_COMMISSION_VALUE']? 'checked="checked"':'') .'>'.$currency;	  
					echo '</span>';	  
				}
				?> 
				</td>
				<td>
				<?php 
				if ($glb_arr_values['IS_AGENT_COMMISSION_VALUE'] == 1) {
					$agent_commission_pcent = get_sin_order_commission_pcent(
										$commission_value, $glb_arr_values['AGENT_COMMISSION']);
					$agent_commission_value = $glb_arr_values['AGENT_COMMISSION'];
				}
				else { // it's already in percentage
					$agent_commission_pcent = $glb_arr_values['AGENT_COMMISSION'];
					$agent_commission_value = get_sin_order_commission_value(
									    $commission_value, $glb_arr_values['AGENT_COMMISSION']);
				}
				if ($state == state_VIEW ) {
					echo '<span title="Agent Commission Amount as Percentage of Organization\'s Commission">';
					echo $currency.' '.number_format($agent_commission_value, NUM_DEC_PLACES, '.', ','). ' ('.$agent_commission_pcent.'%)';
					echo '</span>';
				}
				else{		
					echo '<span title="Specify Amount as Percentage of Organization\'s Commission or as Flat Value">';
					echo generate_text_input ("short_width", "AGENT_COMMISSION", $glb_arr_values['AGENT_COMMISSION']);
					echo '&nbsp;';
					echo '<input type="radio" value="0" id="IS_AGENT_COMMISSION_PCENT" name="IS_AGENT_COMMISSION_PCENT" '. 
						  ($glb_arr_values['IS_AGENT_COMMISSION_VALUE'] ? '':'checked="checked"') .'>%';
					echo '<input type="radio" value="1" id="IS_AGENT_COMMISSION_PCENT" name="IS_AGENT_COMMISSION_PCENT" '. 
						  ($glb_arr_values['IS_AGENT_COMMISSION_VALUE']? 'checked="checked"':'') .'>'.$currency;	  
					echo '</span>';	  
				}
				?> 
				</td>
			</tr>						
		</table>
	</td>
	</tr>
	
	<tr>
	<td style="vertical-align:top;"><strong>Additional Costs:</strong></td>
	<td>
	<?php 						
	echo show_order_add_costs($order_id, entity_SINGLE_ORDER, $currency);
	?>
	</td>
	</tr>	

	<tr>
	<td style="vertical-align:top;"><strong>Payment Matrix:</strong></td>
	<td><?= show_single_order_payment_matrix(); ?></td>
	</tr>
</form>
</table></td></tr>
</table>
<?php
	include("body_footer.php"); 
?>
