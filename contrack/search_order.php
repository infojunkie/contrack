
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
	Search Orders page
	This page is used to display search results for orders; General and Single
	Parameters passed: Entity Name and/or Search Term
	Current Possible values: entity_GEN_ORDER, entity_SINGLE_ORDER
*******************************************************************************************/
require_once("db_utils.php");
require_once("general_utils.php");
require_once("order_utils.php");
require_once( "patUser-2.2.3/prepend.php" );
require_once("permissions.php");

init_page();

/*
	Page Initialisation
	- Authenticate
	- Get all parameters passed to the page
	- Validate permissions for current user
	- Check for any errors passed to the page
*/
function init_page()
{
	global $user, $sort_by, $prior_sort_by, $sort_order, $glb_arr_sort_indicators;

	// Authenticate
	if (! ($user->isAuthenticated())){
		deny_access();
	}
	get_page_params ();
	validate_permissions();
	check_error();
	init_sort_indicators();
	do_sort($sort_by, $prior_sort_by, $sort_order, $glb_arr_sort_indicators);
}
/*
	Get all parameters passed to this page and format display string
*/
function get_page_params()
{
	global  $str_entity, $entity_id, $str_page_name, $str_search_term,
		    $sort_by, $prior_sort_by, $sort_order, $referrer, $referrer_id;

	// Get entity
	$entity_id =  isset($_GET['entity']) ? $_GET['entity'] :
				  (isset($_POST['entity']) ? $_POST['entity'] : entity_GEN_ORDER) ;
	switch ($entity_id) {
		case entity_GEN_ORDER:
		$str_entity 	= "General Order";
		$str_page_name  = "gen_order";  // to fetch permissions
		break;
		case entity_SINGLE_ORDER:
		$str_entity 	= "Single Order";
		$str_page_name  = "single_order";  // to fetch permissions
		break;
		default:
		$str_entity 	= "General Order";
		$str_page_name  = "gen_order";  // to fetch permissions
	}

	$default_sort_by = $str_page_name.".FRIENDLY_NAME";
	// Get sort parameters
	$sort_by 	= isset($_POST['sort_by']) ? $_POST['sort_by'] : $default_sort_by;
	$prior_sort_by 	= isset($_POST['prior_sort_by']) ? $_POST['prior_sort_by'] : "";
	$sort_order = isset($_POST['sort_order']) ? $_POST['sort_order'] : "ASC";

	$referrer = isset($_GET['referrer']) ? $_GET['referrer'] :
				(isset($_POST['referrer']) ? $_POST['referrer'] : entity_SEARCH) ;
	$referrer_id = isset($_GET['referrer_id']) ? $_GET['referrer_id'] :
				(isset($_POST['referrer_id']) ? $_POST['referrer_id'] : -1) ;
	// ~~TO DO~~ This will have the search term
	$str_search_term = "";
}

function init_sort_indicators()
{
	global $glb_arr_sort_indicators, $entity_id;

	if ($entity_id == entity_GEN_ORDER) {
		$glb_arr_sort_indicators['gen_order.FRIENDLY_NAME'] = '';
		$glb_arr_sort_indicators['BUYER_NAME'] = '';
		$glb_arr_sort_indicators['AGENT_NAME'] = '';
		$glb_arr_sort_indicators['BASE_COST'] = '';
		$glb_arr_sort_indicators['gen_order.STATUS'] = '';
		$glb_arr_sort_indicators['gen_order.DATE_CREATED'] = '';
		$glb_arr_sort_indicators['PLANNED_DELIVERY_DATE'] = '';
		$glb_arr_sort_indicators['PCENT_COMPLETE'] = '';
	}
	else {
		$glb_arr_sort_indicators['single_order.FRIENDLY_NAME'] = '';
		$glb_arr_sort_indicators['BUYER_NAME'] = '';
		$glb_arr_sort_indicators['SUPPLIER_NAME'] = '';
		$glb_arr_sort_indicators['AGENT_NAME'] = '';
		$glb_arr_sort_indicators['BASE_COST'] = '';
		$glb_arr_sort_indicators['single_order.STATUS'] = '';
		$glb_arr_sort_indicators['single_order.DATE_CREATED'] = '';
		$glb_arr_sort_indicators['PLANNED_DELIVERY_DATE'] = '';
		$glb_arr_sort_indicators['PCENT_COMPLETE'] = '';
	}
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
	global $user, $str_page_name;

	$user_perms = get_user_permissions ($user, $str_page_name);
	//echo "user_perms = ";
	//var_dump($user_perms);
	//echo "<br>";
	set_user_perms($user_perms);
	//set_required_perms(is_associated());

	if (! is_authorized_view_all()) {
		do_unauthorized();
	}
}

/*
	Authorize based on which permissions are required to view the page and which permissions
	the user has.
*/
function is_authorized_view_all()
{
	global $allow_view_own, $allow_view_all, $allow_modify_own, $allow_modify_all, $user, $entity_id;

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
	Generate the additional condition in the SQL where clause depending on the referrer type and referrer id
*/
function generate_where_clause()
{
	global $referrer, $referrer_id;

	switch ($referrer) {
		case entity_BUYER:
		$where_sql = " AND gen_order.BUYER_ID = ".$referrer_id." ";
		break;
		case entity_AGENT:
		$where_sql = " AND gen_order.AGENT_ID = ".$referrer_id." ";
		break;
		case entity_SUPPLIER:
		$where_sql = " AND single_order.SUPPLIER_ID = ".$referrer_id." ";
		break;
		case entity_GEN_ORDER:
		$where_sql = " AND single_order.GEN_ORDER_ID = ".$referrer_id." ";
		break;
		case entity_SEARCH:
		$where_sql = "";
		break;
		default:
		$where_sql = "";
	}
	return $where_sql;
}
/*
	Get general orders.
	Get all suppliers for each general order.
	Populate values arrays
*/
function  populate_values_gen_order ()
{
	global $glb_arr_values, $glb_arr_single_order_suppliers, $sort_by, $sort_order;

	$sql_where_clause = generate_where_clause();
	$sql = generate_select_gen_orders_sql($sort_by, $sort_order, $sql_where_clause);
	$result = mysql_query($sql) or die(mysql_error());
	if (mysql_num_rows($result)) {
		$i = 0;
		while ($row = mysql_fetch_array($result)) {
			$glb_arr_values[$i]['GEN_ORDER_ID'] 	= $row['GEN_ORDER_ID'];
			$glb_arr_values[$i]['FRIENDLY_NAME'] 	= $row['FRIENDLY_NAME'];
			$glb_arr_values[$i]['CODE'] 			= $row['CODE'];
			$glb_arr_values[$i]['CLIENT_ORDER_ID'] 	= $row['CLIENT_ORDER_ID'];
			$glb_arr_values[$i]['BUYER_ID'] 		= $row['BUYER_ID'];
			$glb_arr_values[$i]['BUYER_NAME'] 		= $row['BUYER_NAME'];
			$glb_arr_values[$i]['AGENT_ID'] 		= $row['AGENT_ID'];
			$glb_arr_values[$i]['AGENT_NAME'] 		= $row['AGENT_NAME'];
			$glb_arr_values[$i]['CURRENCY'] 		= $row['CURRENCY'];
			$glb_arr_values[$i]['BASE_COST'] 		= $row['BASE_COST'];
			$glb_arr_values[$i]['STATUS'] 			= $row['STATUS'];
			$glb_arr_values[$i]['DATE_CREATED'] 	= format_date_reverse($row['DATE_CREATED']);
			$glb_arr_values[$i]['DELIVERY_DATE'] 	= format_date_reverse($row['PLANNED_DELIVERY_DATE']);
			$glb_arr_values[$i]['PCENT_COMPLETE'] 	= get_percent_complete($glb_arr_values[$i]['GEN_ORDER_ID'], entity_GEN_ORDER);
			$i++;
		}
	}
	// For each general order, get all suppliers
	$sql = generate_select_gen_order_suppliers_sql();
	$single_orders = mysql_query($sql) or die(mysql_error());
	if (mysql_num_rows($single_orders)) {
		$j = 0;
		while ($row = mysql_fetch_array($single_orders)) {
			$glb_arr_single_order_suppliers[$j]['GEN_ORDER_ID']  = $row['GEN_ORDER_ID'];
			$glb_arr_single_order_suppliers[$j]['SUPPLIER_ID'] 	 = $row['SUPPLIER_ID'];
			$glb_arr_single_order_suppliers[$j]['SUPPLIER_NAME'] = $row['SUPPLIER_NAME'];
			$j++;
		}
	}
}

/*
	Format the suppliers cell.
	For each GEN_ORDER_ID, get all its suppliers from the $glb_arr_single_order_suppliers array
*/
function format_gen_order_suppliers($int_row)
{
	global $glb_arr_values, $glb_arr_single_order_suppliers;

	$markup = "";
	$num_rows = safe_safe_count($glb_arr_single_order_suppliers);
	for ($i=0; $i < $num_rows; $i++) {
		if ($glb_arr_single_order_suppliers[$i]['SUPPLIER_ID'] != NULL) {
			if ($glb_arr_single_order_suppliers[$i]['GEN_ORDER_ID'] == $glb_arr_values[$int_row]['GEN_ORDER_ID']) {
				$markup .= "<a href=\"search_order.php?entity=".entity_SINGLE_ORDER.
											   "&referrer=".entity_SUPPLIER.
											   "&referrer_id=".$glb_arr_single_order_suppliers[$i]['SUPPLIER_ID']."\">".
											   $glb_arr_single_order_suppliers[$i]['SUPPLIER_NAME']."</a> ";
				// Determine whether to add a comma or not
				if ($i < safe_safe_count($glb_arr_single_order_suppliers) - 1 ){
					if ($glb_arr_single_order_suppliers[$i]['GEN_ORDER_ID'] == $glb_arr_single_order_suppliers[$i+1]['GEN_ORDER_ID']) {
							$markup .= ", ";
					}
				}
			}
		}
	}
	return $markup;
}

/*
	Format the order id cell
*/
function format_order_id($int_row)
{
	global $glb_arr_values;

	return $glb_arr_values[$int_row]['FRIENDLY_NAME']." (". $glb_arr_values[$int_row]['CODE']."/".
		   $glb_arr_values[$int_row]['CLIENT_ORDER_ID'].")";
}

/*
	Get single orders.
	Populate values arrays
*/
function  populate_values_single_order ()
{
	global $glb_arr_values, $sort_by, $sort_order;;

	$sql_where_clause = generate_where_clause();
	$sql = generate_select_single_orders_sql($sort_by, $sort_order, $sql_where_clause);
	$result = mysql_query($sql) or die(mysql_error());

	if (mysql_num_rows($result)) {
		$i = 0;
		while ($row = mysql_fetch_array($result)) {
			$glb_arr_values[$i]['SINGLE_ORDER_ID'] 	= $row['SINGLE_ORDER_ID'];
			$glb_arr_values[$i]['FRIENDLY_NAME'] 	= mysql_decode($row['FRIENDLY_NAME']);
			$glb_arr_values[$i]['CODE'] 			= mysql_decode($row['CODE']);
			$glb_arr_values[$i]['CLIENT_ORDER_ID'] 	= mysql_decode($row['CLIENT_ORDER_ID']);
			$glb_arr_values[$i]['BUYER_ID'] 		= $row['BUYER_ID'];
			$glb_arr_values[$i]['BUYER_NAME'] 		= mysql_decode($row['BUYER_NAME']);
			$glb_arr_values[$i]['SUPPLIER_ID'] 		= $row['SUPPLIER_ID'];
			$glb_arr_values[$i]['SUPPLIER_NAME'] 	= mysql_decode($row['SUPPLIER_NAME']);
			$glb_arr_values[$i]['AGENT_ID'] 		= $row['AGENT_ID'];
			$glb_arr_values[$i]['AGENT_NAME'] 		= mysql_decode($row['AGENT_NAME']);
			$glb_arr_values[$i]['CURRENCY'] 		= $row['CURRENCY'];
			$glb_arr_values[$i]['BASE_COST'] 		= $row['BASE_COST'];
			$glb_arr_values[$i]['STATUS'] 			= $row['STATUS'];
			$glb_arr_values[$i]['DATE_CREATED'] 	= format_date_reverse($row['DATE_CREATED']);
			$glb_arr_values[$i]['DELIVERY_DATE'] 	= format_date_reverse($row['PLANNED_DELIVERY_DATE']);
			$glb_arr_values[$i]['PCENT_COMPLETE'] 	= get_percent_complete($glb_arr_values[$i]['SINGLE_ORDER_ID'], entity_SINGLE_ORDER);
			$i++;
		}
	}
}

/*
	Function: show_records_gen_order ( )
	Desc:
		Gets records from database and outputs them in the correct format
 	Params:
    	None
   	Returns:
		HTML markup string
*/
function show_records_gen_order ( )
{
	global $glb_arr_values, $glb_arr_sort_indicators, $sort_by, $referrer;

	// Populate values array
	populate_values_gen_order();

	if (safe_safe_count($glb_arr_values) < 1){					// no rows
		$markup = '	<table class="info_block">
					<tr><td> No Matching Records Were Found</td></tr>
					</table></td></tr> ';
	}
	else {
		if ($referrer == entity_SEARCH)	 {
	 		$css_class = 'search_results_container';
	 	}
		else {
			$css_class = '';
		}
		$markup = '<table class="'.$css_class.'">
				   <tr class="search_results_header">
				   <td width="15%"><strong><a href="javascript:doSort(document.frm_search_order, \'gen_order.FRIENDLY_NAME\',\''.$sort_by.'\')">General Order ID</a></strong>&nbsp;'. $glb_arr_sort_indicators['gen_order.FRIENDLY_NAME'].'</td>';
		if ($referrer != entity_BUYER) {
			$markup .= '<td width="10%"><strong><a href="javascript:doSort(document.frm_search_order, \'BUYER_NAME\',\''.$sort_by.'\')">Buyer</a></strong>&nbsp;'. $glb_arr_sort_indicators['BUYER_NAME'].'</td>';
		}
		$markup .= '<td width="20%"><strong>Supplier(s)</strong></td>';
		if ($referrer != entity_AGENT) {
			$markup .= '<td width="12%"><strong><a href="javascript:doSort(document.frm_search_order, \'AGENT_NAME\',\''.$sort_by.'\')">Agent</a></strong>&nbsp;'. $glb_arr_sort_indicators['AGENT_NAME'].'</td>';
		}
		$markup .= '<td width="10%"><strong><a href="javascript:doSort(document.frm_search_order, \'BASE_COST\',\''.$sort_by.'\')">Base Cost</a></strong>&nbsp;'. $glb_arr_sort_indicators['BASE_COST'].'</td>
				   <td><strong><a href="javascript:doSort(document.frm_search_order, \'gen_order.STATUS\',\''.$sort_by.'\')">Status</a></strong>&nbsp;'. $glb_arr_sort_indicators['gen_order.STATUS'].'</td>
				   <td width="12%"><strong><a href="javascript:doSort(document.frm_search_order, \'gen_order.DATE_CREATED\',\''.$sort_by.'\')">Creation Date</a></strong>&nbsp;'. $glb_arr_sort_indicators['gen_order.DATE_CREATED'].'</td>
				   <td width="10%"><strong><a href="javascript:doSort(document.frm_search_order, \'PLANNED_DELIVERY_DATE\',\''.$sort_by.'\')">Delivery Date</a></strong>&nbsp;'. $glb_arr_sort_indicators['PLANNED_DELIVERY_DATE'].'</td>
				   <td width="10%"><strong>% Complete</strong></td>
				   </tr>';
		$num_rows = safe_safe_count($glb_arr_values);
		$currency_sums_array = array();
		for ($i=0; $i < $num_rows; $i++) {
			$currency = $glb_arr_values[$i]['CURRENCY'];
			if (!array_key_exists($currency, $currency_sums_array)) {
				$currency_sums_array[$currency]['TOTAL_BASE_COST'] = 0;
			}
			$markup .= "<tr class=\"search_results" .($i%2). "\">" ;
			$markup .= "<td><a href=\"search_order.php?entity=".entity_SINGLE_ORDER."&referrer=".entity_GEN_ORDER."&referrer_id=".$glb_arr_values[$i]['GEN_ORDER_ID']."\">".format_order_id($i)."</a></td>";
			if ($referrer != entity_BUYER) {
				$markup .= "<td><a href=\"search_order.php?entity=".entity_GEN_ORDER."&referrer=".entity_BUYER."&referrer_id=".$glb_arr_values[$i]['BUYER_ID']."\">".$glb_arr_values[$i]['BUYER_NAME']."</a></td>";
			}
			$markup .= "<td>".format_gen_order_suppliers($i)."</td>";
			if ($referrer != entity_AGENT) {
				$markup .= "<td><a href=\"search_order.php?entity=".entity_GEN_ORDER."&referrer=".entity_AGENT."&referrer_id=".$glb_arr_values[$i]['AGENT_ID']."\">".$glb_arr_values[$i]['AGENT_NAME']."</a></td>";
			}
			$markup .= "<td>";
			$markup .= "<a href=\"gen_order_financials.php?gen_order_id=".$glb_arr_values[$i]['GEN_ORDER_ID']."&state=".state_VIEW."\">";
			$markup .= $glb_arr_values[$i]['CURRENCY']. " ". number_format($glb_arr_values[$i]['BASE_COST'], NUM_DEC_PLACES, '.', ',')."</a></td>";
			$markup .= "<td>".$glb_arr_values[$i]['STATUS']."</td>";
			$markup .= "<td>".$glb_arr_values[$i]['DATE_CREATED']."</td>";
			$markup .= "<td>".$glb_arr_values[$i]['DELIVERY_DATE']."</td>";
			$markup .= "<td title=\"Overall Process Completion\">
						<a href=\"search_order.php?entity=".entity_SINGLE_ORDER."&referrer=".entity_GEN_ORDER."&referrer_id=".$glb_arr_values[$i]['GEN_ORDER_ID']."\">"
						.$glb_arr_values[$i]['PCENT_COMPLETE']."</a></td>";
			$markup .= "</tr>\r\n";
			$currency_sums_array[$currency]['TOTAL_BASE_COST'] += $glb_arr_values[$i]['BASE_COST'];
		}
		if ($referrer==entity_SEARCH || $referrer==entity_GEN_ORDER) {
			$colspan = 4;
		}
		else {
			$colspan = 3;
		}
		foreach ($currency_sums_array as $currency_name => $value) {
			$markup .= '<tr class="total_amount"><td colspan="'.$colspan.'">Total '.$currency_name.'</td>
						<td>'.$currency_name.' '.
						number_format($value['TOTAL_BASE_COST'], NUM_DEC_PLACES, '.', ',').'</td></tr>';
		}
		$markup .= "</table></td></tr>\r\n";
	}
	return $markup;
}
/*
	Function: show_records_single_order ( )
	Desc:
		Gets records from database and outputs them in the correct format
 	Params:
    	None
   	Returns:
		HTML markup string
*/
function show_records_single_order ( )
{
	global $glb_arr_values, $glb_arr_sort_indicators, $sort_by, $referrer;

	// Populate values array
	populate_values_single_order();

	if (safe_safe_count($glb_arr_values) < 1){					// no rows
		$markup = '	<table class="info_block">
					<tr><td>No Matching Records Were Found</td></tr>
					</table></td></tr> ';
	}
	else {
		if ($referrer == entity_SEARCH)	 {
	 		$css_class = 'search_results_container';
	 	}
		else {
			$css_class = '';
		}
		$markup = '<table class="'.$css_class.'">
				   <tr class="search_results_header">
				   <td width="15%"><strong><a href="javascript:doSort(document.frm_search_order, \'single_order.FRIENDLY_NAME\',\''.$sort_by.'\')">Single Order ID</a></strong>&nbsp;'. $glb_arr_sort_indicators['single_order.FRIENDLY_NAME'].'</td>
				   <td width="10%"><strong><a href="javascript:doSort(document.frm_search_order, \'BUYER_NAME\',\''.$sort_by.'\')">Buyer</a></strong>&nbsp;'. $glb_arr_sort_indicators['BUYER_NAME'].'</td>';
		if ($referrer != entity_SUPPLIER) {
			$markup .= '<td width="10%"><strong><a href="javascript:doSort(document.frm_search_order, \'SUPPLIER_NAME\',\''.$sort_by.'\')">Supplier</a></strong>&nbsp;'. $glb_arr_sort_indicators['SUPPLIER_NAME'].'</td>';
		}
		$markup .= '<td width="12%"><strong><a href="javascript:doSort(document.frm_search_order, \'AGENT_NAME\',\''.$sort_by.'\')">Agent</a></strong>&nbsp;'. $glb_arr_sort_indicators['AGENT_NAME'].'</td>
				   <td width="10%"><strong><a href="javascript:doSort(document.frm_search_order, \'BASE_COST\',\''.$sort_by.'\')">Base Cost</a></strong>&nbsp;'. $glb_arr_sort_indicators['BASE_COST'].'</td>
				   <td><strong><a href="javascript:doSort(document.frm_search_order, \'single_order.STATUS\',\''.$sort_by.'\')">Status</a></strong>&nbsp;'. $glb_arr_sort_indicators['single_order.STATUS'].'</td>
				   <td width="12%"><strong><a href="javascript:doSort(document.frm_search_order, \'single_order.DATE_CREATED\',\''.$sort_by.'\')">Creation Date</a></strong>&nbsp;'. $glb_arr_sort_indicators['single_order.DATE_CREATED'].'</td>
				   <td width="10%"><strong><a href="javascript:doSort(document.frm_search_order, \'PLANNED_DELIVERY_DATE\',\''.$sort_by.'\')">Delivery Date</a></strong>&nbsp;'. $glb_arr_sort_indicators['PLANNED_DELIVERY_DATE'].'</td>
				   <td width="10%"><strong>% Complete</strong></td>
				   </tr>';
		$num_rows = safe_safe_count($glb_arr_values);
		$currency_sums_array = array();
		for ($i=0; $i < $num_rows; $i++) {
			$currency = $glb_arr_values[$i]['CURRENCY'];
			if (!array_key_exists($currency, $currency_sums_array)) {
				$currency_sums_array[$currency]['TOTAL_BASE_COST'] = 0;
			}
			$markup .= "<tr class=\"search_results" .($i%2). "\">" ;
			$markup .= "<td><a href=\"single_order_prod_mgmt.php?single_order_id=".$glb_arr_values[$i]['SINGLE_ORDER_ID']."\">".format_order_id($i)."</a></td>";
			$markup .= "<td><a href=\"search_order.php?entity=".entity_GEN_ORDER."&referrer=".entity_BUYER."&referrer_id=".$glb_arr_values[$i]['BUYER_ID']."\">".$glb_arr_values[$i]['BUYER_NAME']."</a></td>";
			if ($referrer != entity_SUPPLIER) {
				$markup .= "<td><a href=\"search_order.php?entity=".entity_SINGLE_ORDER."&referrer=".entity_SUPPLIER."&referrer_id=".$glb_arr_values[$i]['SUPPLIER_ID']."\">".$glb_arr_values[$i]['SUPPLIER_NAME']."</a></td>";
			}
			$markup .= "<td><a href=\"search_order.php?entity=".entity_GEN_ORDER."&referrer=".entity_AGENT."&referrer_id=".$glb_arr_values[$i]['AGENT_ID']."\">".$glb_arr_values[$i]['AGENT_NAME']."</a></td>";
			$markup .= "<td>";
			$markup .= "<a href=\"single_order_financials.php?single_order_id=".$glb_arr_values[$i]['SINGLE_ORDER_ID']."&state=".state_VIEW."\">";
			$markup .= $currency. " ". number_format($glb_arr_values[$i]['BASE_COST'], NUM_DEC_PLACES, '.', ',')."</a></td>";
			$markup .= "<td>".$glb_arr_values[$i]['STATUS']."</td>";
			$markup .= "<td>".$glb_arr_values[$i]['DATE_CREATED']."</td>";
			$markup .= "<td>".$glb_arr_values[$i]['DELIVERY_DATE']."</td>";
			$markup .= "<td title=\"Overall Process Completion\">
						<a href=\"single_order_prod_mgmt.php?single_order_id=".$glb_arr_values[$i]['SINGLE_ORDER_ID']."\">"
						.$glb_arr_values[$i]['PCENT_COMPLETE']."</a></td>";
			$markup .= "</tr>\r\n";
			$currency_sums_array[$currency]['TOTAL_BASE_COST'] += $glb_arr_values[$i]['BASE_COST'];
		}
		if ($referrer==entity_SEARCH || $referrer==entity_GEN_ORDER) {
			$colspan = 4;
		}
		else {
			$colspan = 3;
		}
		foreach ($currency_sums_array as $currency_name => $value) {
			$markup .= '<tr class="total_amount"><td colspan="'.$colspan.'">Total '.$currency_name.'</td>
						<td>'.$currency_name.' '.
						number_format($value['TOTAL_BASE_COST'], NUM_DEC_PLACES, '.', ',').'</td></tr>';
		}
		$markup .= "</table>\r\n";
	}
	return $markup;
}
/*
	Generates search form header
*/
function generate_form()
{
	global $entity_id, $sort_by, $prior_sort_by, $sort_order, $referrer, $referrer_id;

	$markup  = '<form action="'. $_SERVER['PHP_SELF'] . '" method="post" name="frm_search_order">' ;
	$markup .= '<input type="hidden" id="entity" name="entity" value="' . $entity_id . '"/>';
	$markup .= '<input type="hidden" id="sort_by" name="sort_by" value="' . $sort_by . '"/>';
	$markup .= '<input type="hidden" id="sort_order" name="sort_order" value="' . $sort_order . '"/>';
	$markup .= '<input type="hidden" id="prior_sort_by" name="prior_sort_by" value="' . $prior_sort_by . '"/>';
	$markup .= '<input type="hidden" id="referrer" name="referrer" value="' . $referrer . '"/>';
	$markup .= '<input type="hidden" id="referrer_id" name="referrer_id" value="' . $referrer_id . '"/>';
	$markup .= '</form>';
	return $markup;
}
/*
	Root function to show records based on entity type
*/
function show_records()
{
	global $entity_id;

	if ($entity_id == entity_GEN_ORDER) {
		$markup = show_records_gen_order();
	}
	else {
		$markup = show_records_single_order();
	}
	return $markup;
}
/*
	Set page title according to referrer type
*/
function get_page_title()
{
	global $referrer, $referrer_id, $str_entity;

	switch ($referrer) {
		case entity_BUYER:
		case entity_AGENT:
		case entity_SUPPLIER:
		$page_name = get_party_name($referrer_id). " :: Orders";
		break;
		case entity_GEN_ORDER:
		$page_name = format_gen_order_name ($referrer_id). " :: Single Orders";
		break;
		case entity_SEARCH:
		$page_name = "Search - " . capitalise_first_letter($str_entity) . "s";
		break;
		default:
		$page_name = "";
	}
	return $page_name;
}

$page_title = get_page_title();
include("body_header.php");
?>
<script type="text/javascript"><!--
// Future enhancement to apply search filters
function submitForm()
{
	//var frm = document.frm_search_order;
	//frm.state.value = state;
	//frm.submit();
}
--></script>
<table border="0"  cellpadding="0" cellspacing="0">
<tr>
  <td colspan="3">
	<h4>
	<?php
	if  (($referrer_id == -1) && ($referrer != entity_SEARCH)) {
		echo "New " . set_str_entity_type($referrer);
	}
	else {
		if ($referrer == entity_GEN_ORDER) {
			echo "GENERAL ORDER" ." :: ". format_gen_order_name($referrer_id);
		}
		elseif ($referrer == entity_SEARCH) {
			echo "SEARCH RESULTS ::  ";
			if (!empty($str_entity)) {
				echo $str_entity  . "s " ;
				if (!empty($str_search_term)) {
					echo "containing " . $str_search_term;
				}
			}
		}
		else {
			echo strtoupper (set_str_entity_type($referrer)) ." :: ". get_party_name($referrer_id);
		}
	}
	?>
	</h4>
  </td>
</tr>
	<?php
	if ($referrer == entity_SEARCH) {
		echo generate_form();
		echo show_records();
	}
	else { ?>
	<tr>  <!-- Tabs -->
	<td class = "tabs" colspan="3">
		<table border="0" cellspacing="0">
			<tr>
			<?php
			/*	Tab Generation
				Pass the ordinal of the tab to be activated
			*/
			switch ($referrer) {
				case entity_AGENT:
				echo generate_agent_tabs(1, $referrer_id, state_VIEW);
				break;
				case entity_BUYER:
				echo generate_buyer_tabs(1, $referrer_id, state_VIEW);
				break;
				case entity_SUPPLIER:
				echo generate_supplier_tabs(1, $referrer_id, state_VIEW);
				break;
				case entity_GEN_ORDER:
				echo generate_gen_order_tabs(1, $referrer_id, state_VIEW);
				break;
			}
			?>
			</tr>
		</table>
	</td>
	</tr> <!-- End of Tabs -->
	<tr>
		<td colspan="3">
			<table class="tab_content">
				<tr><td>
				<?= generate_form(); ?>
				<?=	generate_filters_headers($referrer); ?>
				</td></tr>
				<tr><td>
				<?=	show_records(); ?>
				</td></tr>
		<!-- </form> -->
			</table>
		</td>
	</tr>
</table>
	<?php } // End else?>
<?php
	include("body_footer.php");
?>
