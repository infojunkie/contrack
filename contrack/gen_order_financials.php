
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
  	General Order (GO) Financials page

	This page will handle viewing the GO finances and creating/editing additonal costs

	It has two states: VIEW and PROCESS
	It's in the PROCESS state when an additional cost has been deleted

	Payment Matrix Logic is explained on top of the function called 'show_gen_order_payment_matrix()'
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

	$order_id 	 = isset($_GET['gen_order_id']) ? $_GET['gen_order_id'] : (isset($_POST['GEN_ORDER_ID']) ? $_POST['GEN_ORDER_ID'] : -1) ;
	$state	 	 = isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : state_VIEW) ;
	$entity_id 	 = isset($_GET['entity_id']) ? $_GET['entity_id'] : (isset($_POST['entity_id']) ? $_POST['entity_id'] : -1) ;
	$entity_op 	 = isset($_GET['entity_op']) ? $_GET['entity_op'] : (isset($_POST['entity_op']) ? $_POST['entity_op'] : -1) ;
	$entity_type = isset($_GET['entity_type']) ? $_GET['entity_type'] : (isset($_POST['entity_type']) ? $_POST['entity_type'] : -1) ;
	$currency    = get_gen_order_currency($order_id);
	$str_entity  = "gen_order_financials";  // for fetching security permissions
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
	Process form only if state = PROCESS
*/
function process_state()
{
	global $state;
	if ( $state == state_PROCESS) {
		process_form();
	}
}
/*
	This function currently deals with additional costs deletion only
*/
function process_form()
{
	global $entity_id, $entity_op, $entity_type, $order_id;

	if ( $entity_op == opcode_DELETE ) {
		if ($entity_type == entity_ADDITIONAL_COST)  delete_additional_cost($entity_id);
		// Go back to same page after deleting
		$location_url = "Location: gen_order_financials.php?gen_order_id=".$order_id."&state=".state_VIEW;
		header($location_url);
		exit;
	}
}
/*
	Function: generate_select_single_orders_financials_sql ( )
	Desc:
		Select single orders financial snapshot for the general order
 	Params:
		$gen_order_id - GO ID for which we're getting the financials
		$optional_where_clause - For further filtering
   	Returns:
		SQL Query string
*/
function generate_select_single_orders_financials_sql($gen_order_id, $optional_where_clause = '')
{
	$select_sql = " SELECT  single_order.SINGLE_ORDER_ID, single_order.FRIENDLY_NAME, single_order.CODE,
					single_order.CLIENT_ORDER_ID, supplier.PARTY_ID AS SUPPLIER_ID,
					supplier.NAME AS SUPPLIER_NAME,	buyer.NAME AS BUYER_NAME,
					product_type.NAME AS PRODUCT_TYPE,
					SUM(single_order.UNIT_PRICE * IF(production_step.PLANNED_END_QTY IS NULL, 0, production_step.PLANNED_END_QTY))
					AS BASE_COST, single_order.IS_COMMISSION_VALUE, single_order.COMMISSION,
					single_order.IS_BUYER_COMMISSIONER,
					single_order.IS_AGENT_COMMISSION_VALUE, single_order.AGENT_COMMISSION
					FROM gen_order INNER JOIN single_order ON gen_order.GEN_ORDER_ID = single_order.GEN_ORDER_ID
					INNER JOIN party AS supplier ON single_order.SUPPLIER_ID = supplier.PARTY_ID
					INNER JOIN party AS buyer ON gen_order.BUYER_ID = buyer.PARTY_ID
					LEFT JOIN production_step ON single_order.SINGLE_ORDER_ID  = production_step.SINGLE_ORDER_ID
					LEFT JOIN product ON single_order.PRODUCT_ID = product.PRODUCT_ID
					LEFT JOIN product_type ON product.PRODUCT_TYPE_ID = product_type.PRODUCT_TYPE_ID
					WHERE ";
	$select_sql .= ($optional_where_clause=='' ? '' : $optional_where_clause);
	$select_sql .= " (IF(production_step.PARENT_ID IS NULL, -1, production_step.PARENT_ID) = -1)
					AND single_order.GEN_ORDER_ID = " .$gen_order_id."
					GROUP BY single_order.SINGLE_ORDER_ID
					ORDER BY single_order.FRIENDLY_NAME ASC";
	return $select_sql;
}

/*
	Function: show_single_order_financials ( )
	Desc:
		Shows financial snapshots for single orders belonging to this general order
 	Params:
    	None
   	Returns:
		HTML string
*/
function show_single_order_financials()
{
	global $order_id, $total_agent_commission_value, $currency;

	$sql = generate_select_single_orders_financials_sql($order_id);

	$result = mysql_query($sql) or die(mysql_error());

	if (mysql_num_rows($result) <= 0) {
	$markup = '	<table class="info_block">
				<tr><td>No Single Orders Exist for this General Order</td></tr>
				</table>';
	}
	else {
		$markup  = "<table>
					<tr class=\"con_table_header\">";
		$markup .=	"<td>Single Order</td>
					<td>Supplier</td>
					<td>Product Type</td>
					<td>Base Cost</td>
					<td>Organization Commission</td>
					<td>Paid By</td>
					<td>Agent Commission</td>
					</tr>";
		$total_base_cost 				= 0;
		$total_org_commission_value 	= 0;
		$total_agent_commission_value 	= 0;
		$i = 0;
		while ($row = mysql_fetch_array($result)) {
			// Get Commissioner
			$commissioner 		= ($row['IS_BUYER_COMMISSIONER']? 'Buyer': 'Supplier' );
			$commissioner_name 	= ($row['IS_BUYER_COMMISSIONER']? $row['BUYER_NAME']: $row['SUPPLIER_NAME'] );
			// Get Organization comission percentage
			if ($row['IS_COMMISSION_VALUE'] == 1) {
				$org_commission_pcent = get_sin_order_commission_pcent($row['BASE_COST'], $row['COMMISSION']);
				$org_commission_value = $row['COMMISSION'];
			}
			else { // it's already in percentage
				$org_commission_pcent = $row['COMMISSION'];
				$org_commission_value = get_sin_order_commission_value($row['BASE_COST'], $row['COMMISSION']);
			}
			// Get Agent comission percentage
			if ($row['IS_AGENT_COMMISSION_VALUE'] == 1) {
				$agent_commission_pcent = get_sin_order_commission_pcent($org_commission_value, $row['AGENT_COMMISSION']);
				$agent_commission_value = $row['AGENT_COMMISSION'];
			}
			else { // it's already in percentage
				$agent_commission_pcent = $row['AGENT_COMMISSION'];
				$agent_commission_value = get_sin_order_commission_value($org_commission_value, $row['AGENT_COMMISSION']);
			}
			$markup .= "<tr class=\"con_table_line" .($i%2). "\">";
			$markup .= "<td>";
			$markup .="<a href=\"single_order_financials.php?single_order_id=".$row['SINGLE_ORDER_ID']."&state=".state_VIEW."\">";
			$markup .= $row['FRIENDLY_NAME']." (". $row['CODE']."/". $row['CLIENT_ORDER_ID'].")"  . "</a></td>";
			$markup .= "<td>";
			$markup .="<a href=\"search_order.php?entity=".entity_SINGLE_ORDER."&referrer=".entity_SUPPLIER."&referrer_id=".$row['SUPPLIER_ID']."\">";
			$markup .= $row['SUPPLIER_NAME'] . "</a></td>";
			$markup .= "<td>". $row['PRODUCT_TYPE'] ."</td>";
			$markup .= "<td>";
			$markup .= "<a href=\"single_order_financials.php?single_order_id=".$row['SINGLE_ORDER_ID']."&state=".state_VIEW."\">";
			$markup .= $currency. ' '. number_format($row['BASE_COST'], NUM_DEC_PLACES, '.',',') . "</a></td>";
			$markup .= "<td>". $currency. ' '. number_format($org_commission_value, NUM_DEC_PLACES, '.',',') .
						" (".$org_commission_pcent."%)</td>";
			$markup .= "<td title=\"".$commissioner_name."\">".$commissioner ."</td>";
			$markup .= "<td>". $currency. ' '. number_format($agent_commission_value, NUM_DEC_PLACES, '.',',') .
						" (".$agent_commission_pcent."%)</td>";
			$total_base_cost 			  +=  $row['BASE_COST'];
			$total_org_commission_value  += $org_commission_value;
			$total_agent_commission_value += $agent_commission_value;
			++$i;
		}
		if (mysql_num_rows($result)) {
			$markup .= '<tr class="total_amount_financials">
						<td colspan="3">Total</td> ';
			$markup .= '<td>'.$currency.' '.
						number_format($total_base_cost, NUM_DEC_PLACES, '.', ',').'</td>';
			$markup .= '<td>'.$currency.' '.
						number_format($total_org_commission_value, NUM_DEC_PLACES, '.', ',').'</td>';
			$markup .= '<td></td>';
			$markup .= '<td>'.$currency.' '.
						number_format($total_agent_commission_value, NUM_DEC_PLACES, '.', ',').'</td></tr>';
		}
		$markup .= "</table>\r\n";
		mysql_free_result($result);
	}
	return $markup;
}

/*
	Function: show_gen_order_payment_matrix ( )
	Desc:
		Shows the payment matrix for the General Order
 	Params:
    	None
   	Returns:
		HTML string

	Payments Categories:
	--------------------
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
	-----------
		a- Identify all payors for this GO:
			. Possible Payors  are: 1. Organization
									2. Buyer of this GO
									3. Agent of this GO
									4. All Suppliers of  Single Orders belonging to this GO
									5. All External Parties that are financially involved
			. Put all those payors in a 2-dimensional array ($array_payors) where each row has the following
		   	  entries: PARTY_ID, TYPE, NAME
		b- Identify all payees for this GO:
			. Possible Payees are: 1. Organization
								   2. Buyer of this GO
								   2. Agent of this GO
								   3. All Suppliers of this GO
								   4. All External Parties that are financially involved
		 	. Put all those payees in a 2-dimensional array ($array_payees) where each row has the following
		   	  entries: PARTY_ID, TYPE, NAME
		c- Create header row with all payors Types and Names as mentioned in (a)
			. Column 1 is always Organization
			. Column 2 is always Buyer
			. Column 3 is always Agent
			. Columns 4 to 'n' are Suppliers followed by External Parties
		d- Create table rows
			. Row 1 is always Organization
			. Row 2 is always Buyer
			. Row 3 is always Agent
			. Rows 4 to 'n' are Suppliers followed by External Parties
		e- Iterate through rows and for each cell identify its contents	based on type and ID of payor
			and payees according to the rules defined above in "Payment Categories"

	Notes:
		- Originally, Buyer could not be Payee, and Agent could not be Payor; thus the need for
		  two different arrays (array_payors and array_payees). Now that this rule is cancelled
		  the two arrays could be collapsed into once since they're identical. However, I'll leave
		  them for now in case this business rule is re-instated.
		  They should be collapsed anyway when code re-factoring is performed.
*/
function show_gen_order_payment_matrix()
{
	global $order_id, $currency, $total_paid, $total_received;

	$array_payors = array();
	$array_payees = array();
	populate_payors_and_payees_arrays($array_payors, $array_payees, $order_id);
	//  Initialize $total_paid Array
	for ($m=0; $m < safe_count($array_payors); $m++)
		$total_paid[$array_payors[$m]['PARTY_ID']]     = 0;
	// Initialize $total_received array
	for ($n=0; $n < safe_count($array_payees); $n++)
		$total_received[$array_payees[$n]['PARTY_ID']] = 0;

	$markup = create_header_row($array_payors);
	// Walk through payees rows
	for ($i=0; $i < safe_count($array_payees); $i++){
		$payee_id   = $array_payees[$i]['PARTY_ID'];
		$payee_name = $array_payees[$i]['NAME'];
		$payee_type = $array_payees[$i]['TYPE'];
		// Skip Buyer as Payee. Buyer is never Payee according to the current Business Rules
		//if ($payee_type != entity_BUYER) {
			$markup .= '<tr class="con_table_line0"><td style="background-color:#999966;color:#fff;font-weight:bold">
						<strong>'.set_str_entity_type($payee_type).'::'.$payee_name.'</strong></td>';
		//}
		// Traverse grid
		for ($j=0; $j < safe_count($array_payors); $j++) {
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
	if ($payor_id == $payee_id) {
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
		$sql    = generate_select_single_orders_financials_sql($order_id, $sql_where_clause);
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
		$markup .= create_additional_cost_markup($order_id, entity_GEN_ORDER, $payor_id, $payee_id);
		$markup .= '</td>';
		break;
		case entity_SUPPLIER:
		// If supplier is commissioner; For each single order, get commission value and single order name
		$markup = '<td>';
		$sql_where_clause = ' single_order.IS_BUYER_COMMISSIONER = 0 AND single_order.SUPPLIER_ID = '.$payor_id.' AND ';
		$sql    = generate_select_single_orders_financials_sql($order_id, $sql_where_clause);
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
		$markup .= create_additional_cost_markup($order_id, entity_GEN_ORDER, $payor_id, $payee_id);
		$markup .= '</td>';
		break;
		case entity_AGENT:
		case entity_EXTERNAL:
		// Get all additional costs having organization as payee and this external party as payor
		$markup  = '<td>';
		$markup .= create_additional_cost_markup($order_id, entity_GEN_ORDER, $payor_id, $payee_id);
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
		$markup .= create_additional_cost_markup($order_id, entity_GEN_ORDER, $payor_id, $payee_id);
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
	global  $total_paid, $total_received, $currency,
			$total_agent_commission_value; // This value has been set in show_single_order_financials()

	switch ($payor_type){
		// Get all Agent Commissions paid from Organization to Agent from single orders financials snapshot ;)
		case entity_INTERNAL:
		$markup   = '<td>';
		$title    = "Commission for All Single Orders ";
		$amount   = $total_agent_commission_value;
		$markup  .= '<span title="'.$title.'">'.$currency.' ' .
					  number_format($amount, NUM_DEC_PLACES, '.', ',').'</span><br/>';
		$total_paid[$payor_id] 		+= $amount;
		$total_received[$payee_id]  += $amount;
		// Get any additional costs paid from Organization to this Agent
		$markup .= create_additional_cost_markup($order_id, entity_GEN_ORDER, $payor_id, $payee_id);
		$markup .= '</td>';
		break;
		// Get all additional costs having this Agent as payee and this [Buyer|Supplier|External] as payor
		case entity_BUYER:
		case entity_SUPPLIER:
		case entity_EXTERNAL:
		$markup  = '<td>';
		$markup .= create_additional_cost_markup($order_id, entity_GEN_ORDER, $payor_id, $payee_id);
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
		$sql    = generate_select_single_orders_financials_sql($order_id, $sql_where_clause);
		//echo $sql.'<br/>';
		$result = mysql_query($sql) or die(mysql_error());
		while ($row = mysql_fetch_array($result)) {
			$title     = "Base Cost for Single Order: " .$row['FRIENDLY_NAME'].' ('. $row['CODE'].'/'. $row['CLIENT_ORDER_ID'].')';
			$base_cost = $row['BASE_COST'];
			$markup .= '<span title="'.$title.'">'.$currency.' ' .number_format($base_cost, NUM_DEC_PLACES, '.',',').'</span><br/>';
			$total_paid[$payor_id] 		+= $base_cost;
			$total_received[$payee_id]  += $base_cost;
		}
		$markup .= create_additional_cost_markup($order_id, entity_GEN_ORDER, $payor_id, $payee_id);
		$markup .= '</td>';
		break;
		// Get all additional costs having this Supplier as payee and [Organization|Supplier|External] as payor
		case entity_INTERNAL:
		case entity_AGENT:
		case entity_SUPPLIER:
		case entity_EXTERNAL:
		$markup = '<td>';
		$markup .= create_additional_cost_markup($order_id, entity_GEN_ORDER, $payor_id, $payee_id);
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
		$markup .= create_additional_cost_markup($order_id, entity_GEN_ORDER, $payor_id, $payee_id);
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
	// Buyer
	$buyer_row = get_gen_order_buyer_id_and_name($order_id);
	$array_payors[1]['PARTY_ID'] = $buyer_row['BUYER_ID'];
	$array_payors[1]['NAME']     = $buyer_row['BUYER_NAME'];
	$array_payors[1]['TYPE']     = entity_BUYER;

	$array_payees[1]['PARTY_ID'] = $buyer_row['BUYER_ID'] ;
	$array_payees[1]['NAME']     = $buyer_row['BUYER_NAME'];
	$array_payees[1]['TYPE']     = entity_BUYER;

	// Agent
	$agent_row = get_gen_order_agent_id_and_name($order_id);
	$array_payors[2]['PARTY_ID'] = $agent_row['AGENT_ID'];
	$array_payors[2]['NAME']     = $agent_row['AGENT_NAME'];
	$array_payors[2]['TYPE']     = entity_AGENT;

	$array_payees[2]['PARTY_ID'] = $agent_row['AGENT_ID'];
	$array_payees[2]['NAME']     = $agent_row['AGENT_NAME'];
	$array_payees[2]['TYPE']     = entity_AGENT;

	// Suppliers
	$sql_suppliers = generate_select_gen_order_suppliers_sql(" WHERE gen_order.GEN_ORDER_ID = " .$order_id );
	$result = mysql_query($sql_suppliers) or die(mysql_error());
	$i = 3;
	while ($row = mysql_fetch_array($result)) {
		$array_payors[$i]['PARTY_ID'] = $row['SUPPLIER_ID'];
		$array_payors[$i]['NAME']     = $row['SUPPLIER_NAME'];
		$array_payors[$i]['TYPE']     = entity_SUPPLIER;

		$array_payees[$i]['PARTY_ID'] = $row['SUPPLIER_ID'];
		$array_payees[$i]['NAME']     = $row['SUPPLIER_NAME'];
		$array_payees[$i]['TYPE']     = entity_SUPPLIER;
		++$i;
	}
	// External Parties that are financially involved
	// (i.e. they're in the additional costs table either as payor or payee)
	$sql_externals = "SELECT DISTINCT
					  	party.PARTY_ID AS EXT_PARTY_ID, party.NAME AS EXT_NAME
					  FROM party , additional_cost
					  WHERE party.TYPE = ".entity_EXTERNAL."
					  	AND party.EMPLOYER_ID = 0
						AND additional_cost.GEN_ORDER_ID = ".$order_id."
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
function generate_form()
{
	global $state, $order_id, $entity_id, $entity_op, $entity_type ;

	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_financials\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"GEN_ORDER_ID\" name=\"GEN_ORDER_ID\" value=\"" . $order_id . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"entity_id\" name=\"entity_id\" value=\"" . $entity_id . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"entity_type\" name=\"entity_type\" value=\"" . $entity_type . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"entity_op\" name=\"entity_op\" value=\"" . $entity_op . "\"/>\r\n";
	$str_frm_header .= "</form>";
	return $str_frm_header;
}

$page_title = format_gen_order_name($order_id) . " :: Financials";
include("body_header.php");
?>
<script type="text/javascript"><!--
var state = <?= ($state=="") ? 0 : $state ?>;

function submitForm()
{
	var frm = document.frm_financials;
	frm.state.value = state;
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
				echo "New General Order" ;
			}
			else {
				echo "GENERAL ORDER" ." :: ". format_gen_order_name($order_id);
			}
			?>
			</h4>
		</td>
	</tr>
	<tr>  <!-- Tabs -->
		<td class = "tabs" colspan="3">
			<table border="0" cellspacing="0">
				<tr>
				<?php
				/*	Tab Generation
					Pass the ordinal of the tab to be activated
				*/
				echo generate_gen_order_tabs(4, $order_id);
				?>
				</tr>
			</table>
		</td>
	</tr> <!-- End of Tabs -->
	<tr>
		<td colspan="3">
			<table class="tab_content">
				<?= generate_form(); ?>
				<tr><td>&nbsp;</td><td>&nbsp;</td></tr>

				<tr>
				<td style="vertical-align:top;"><strong>Additional Costs:</strong></td>
				<td>
				<?php
				echo show_order_add_costs($order_id, entity_GEN_ORDER, $currency);
				?>
				</td>
				</tr>

				<tr>
				<td style="vertical-align:top;"><strong>Single Orders:</strong></td>
				<td>
				<?php
				echo show_single_order_financials();
				?>
				</td>
				</tr>

				<tr>
				<td style="vertical-align:top;"><strong>Payment Matrix:</strong></td>
				<td><?= show_gen_order_payment_matrix(); ?></td>
				</tr>
	</table>
	</td>
	</tr>
</table>
<?php
	include("body_footer.php");
?>
