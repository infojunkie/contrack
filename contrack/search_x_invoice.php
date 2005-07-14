	
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
	Search External invoices page
	This page is used to display/search for external invoices 
*******************************************************************************************/ 
require_once("db_utils.php");
require_once("general_utils.php");
require_once("order_utils.php");
require_once( "patUser-2.2.3/prepend.php" );
require_once("permissions.php");

init_page();

/*
	Page Initialization
	- Initialize any global variables
	- Authenticate
	- Get all parameters passed to the page
	- Validate permissions for current user
	- Check for any errors passed to the page	
	- Sort 
*/
function init_page()
{
	global $user, $glb_arr_sort_indicators, $sort_by, $prior_sort_by, $sort_order;
		
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
	Initialize sort indicators array (up and down arrows)
*/
function init_sort_indicators()
{	
	global $glb_arr_sort_indicators;

	$glb_arr_sort_indicators['INVOICE_REF'] = '';
	$glb_arr_sort_indicators['GEN_ORDER_NAME'] = '';
	$glb_arr_sort_indicators['SIN_ORDER_NAME'] = '';
	$glb_arr_sort_indicators['BUYER_NAME'] = '';
	$glb_arr_sort_indicators['SUPPLIER_NAME'] = '';
	$glb_arr_sort_indicators['INV_AMOUNT'] = '';
	$glb_arr_sort_indicators['INV_DATE'] = '';
}
/*
	Get all parameters passed to this page and format display string
*/
function get_page_params()
{
	global  $str_entity, $state, $sort_by, $prior_sort_by, $sort_order, $referrer, $referrer_id;
		
	$sort_by 		= isset($_POST['sort_by']) ? $_POST['sort_by'] : "INVOICE_REF";
	$prior_sort_by 	= isset($_POST['prior_sort_by']) ? $_POST['prior_sort_by'] : "";
	$sort_order 	= isset($_POST['sort_order']) ? $_POST['sort_order'] : "ASC";	
	$str_entity = "invoice"; // used to get security permissions. For now xinvoice is the same as invoice
	$str_search_term = "";
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
	global $allow_view_own, $allow_view_all, $allow_modify_own, $allow_modify_all, $user;
	
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
	Function: generate_select_invoices_sql ( )
	Desc:  
		Selects all invoice attributes from invoice table 
 	Params: 
		$sql_where_clause
   	Returns: 
		SQL Query string
*/	
function generate_select_x_invoices_sql()
{
	global $sort_by, $sort_order;

	$select_sql = "	SELECT payment_b2s.PAYMENT_B2S_ID, payment_b2s.INVOICE_REF,  
					gen_order.GEN_ORDER_ID AS GEN_ORDER_ID, gen_order.FRIENDLY_NAME AS GEN_ORDER_NAME, 
					gen_order.CODE AS GEN_ORDER_CODE, gen_order.CLIENT_ORDER_ID AS GEN_ORDER_CLIENT_ID,
					single_order.SINGLE_ORDER_ID AS SIN_ORDER_ID, single_order.FRIENDLY_NAME AS SIN_ORDER_NAME, 
					single_order.CODE AS SIN_ORDER_CODE, single_order.CLIENT_ORDER_ID AS SIN_ORDER_CLIENT_ID, 
					buyer.PARTY_ID AS BUYER_ID, buyer.NAME AS BUYER_NAME, 
					supplier.PARTY_ID AS SUPPLIER_ID, supplier.NAME AS SUPPLIER_NAME,
					payment_b2s.AMOUNT AS INV_AMOUNT, payment_b2s.DATE AS INV_DATE,
					currency.CODE AS CURRENCY_CODE				
					FROM payment_b2s INNER JOIN milestone ON payment_b2s.MILESTONE_ID = milestone.MILESTONE_ID
					INNER JOIN single_order ON single_order.SINGLE_ORDER_ID = milestone.SINGLE_ORDER_ID
					INNER JOIN gen_order ON gen_order.GEN_ORDER_ID = single_order.GEN_ORDER_ID
					LEFT JOIN currency ON currency.CURRENCY_ID = gen_order.CURRENCY_ID
					LEFT JOIN party AS buyer ON buyer.PARTY_ID = gen_order.BUYER_ID
					LEFT JOIN party AS supplier on supplier.PARTY_ID = single_order.SUPPLIER_ID "; 
	if (!empty($sort_by)) {
		$select_sql .= " ORDER BY ". $sort_by. " ". $sort_order;
	}
	else {
		$select_sql .= " ORDER BY INVOICE_REF ASC";
	}	
	return $select_sql;
}
/*
	Function: show_records ( )
	Desc:  
		Gets records from database and outputs them in the correct format
 	Params: 
    	None
   	Returns: 
		HTML markup string
*/	
function show_records()
{
	global $glb_arr_sort_indicators, $sort_by;
	
	$sql = generate_select_x_invoices_sql();
	$result = mysql_query($sql) or die(mysql_error());	
	
	if (!mysql_num_rows($result)) {
		$markup = '	<table class="info_block">	
					<tr><td>No Matching Records Were Found</td></tr>
					</table></td></tr> ';		
	} 
	else {
		$markup = '<table class="search_results_container">
				   <tr class="search_results_header">
				    <td><strong><a href="javascript:doSort(document.frm_search_x_invoice,\'INVOICE_REF\',\''.$sort_by.'\')">Ext. Ref. #</a></strong>&nbsp;'. $glb_arr_sort_indicators['INVOICE_REF'].'</td>';
		$markup .= '<td><strong><a href="javascript:doSort(document.frm_search_x_invoice,\'GEN_ORDER_NAME\',\''.$sort_by.'\')">General Order ID</a></strong>&nbsp;'.$glb_arr_sort_indicators['GEN_ORDER_NAME'].'</td>';		
		$markup .= '<td><strong><a href="javascript:doSort(document.frm_search_x_invoice,\'SIN_ORDER_NAME\',\''.$sort_by.'\')">Single Order ID</a></strong>&nbsp;'.$glb_arr_sort_indicators['SIN_ORDER_NAME'].'</td>
				    <td><strong><a href="javascript:doSort(document.frm_search_x_invoice,\'BUYER_NAME\',\''.$sort_by.'\')">Buyer</a></strong>&nbsp;'.$glb_arr_sort_indicators['BUYER_NAME'].'</td>
				    <td><strong><a href="javascript:doSort(document.frm_search_x_invoice,\'SUPPLIER_NAME\',\''.$sort_by.'\')">Supplier</a></strong>&nbsp;'.$glb_arr_sort_indicators['SUPPLIER_NAME'].'</td>
				    <td><strong><a href="javascript:doSort(document.frm_search_x_invoice,\'INV_AMOUNT\',\''.$sort_by.'\')">Amount</a></strong>&nbsp;'.$glb_arr_sort_indicators['INV_AMOUNT'].'</td>
				    <td><strong><a href="javascript:doSort(document.frm_search_x_invoice,\'INV_DATE\',\''.$sort_by.'\')">Date</a></strong>&nbsp;'.$glb_arr_sort_indicators['INV_DATE'].'</td>
				    </tr>';				   
		$i=0;
		$currency_sums_array = array();
		while ($row = mysql_fetch_array($result)) {
			$currency = $row['CURRENCY_CODE'];
			if (!array_key_exists($currency, $currency_sums_array)) {
				$currency_sums_array[$currency]['TOTAL_INVOICED'] = 0;
			}
			$markup .= "<tr class=\"search_results" .($i%2). "\">" ;
			$markup .= "<td>".$row['INVOICE_REF']."</td>";
			$markup .= "<td><a href=\"search_order.php?entity=".entity_SINGLE_ORDER."&referrer=".entity_GEN_ORDER."&referrer_id=".$row['GEN_ORDER_ID']."\">".
						$row['GEN_ORDER_NAME']." (".$row['GEN_ORDER_CODE']."/".$row['GEN_ORDER_CLIENT_ID'].")"."</a></td>";				
			$markup .= "<td><a href=\"single_order_milestones.php?single_order_id=".$row['SIN_ORDER_ID']."\">".
						$row['SIN_ORDER_NAME']." (".$row['SIN_ORDER_CODE']."/".$row['SIN_ORDER_CLIENT_ID'].")"."</a></td>";				
			$markup .= "<td><a href=\"search_order.php?entity=".entity_GEN_ORDER."&referrer=".entity_BUYER."&referrer_id=".$row['BUYER_ID']."\">".
						$row['BUYER_NAME']."</a></td>";						
			$markup .= "<td><a href=\"search_order.php?entity=".entity_SINGLE_ORDER."&referrer=".entity_SUPPLIER."&referrer_id=".$row['SUPPLIER_ID']."\">".
						$row['SUPPLIER_NAME']."</a></td>";						
			$markup .= "<td>".$currency. " ". number_format($row['INV_AMOUNT'], NUM_DEC_PLACES,'.',',')."</td>";
			$markup .= "<td>".format_date_reverse($row['INV_DATE'])."</td>";
			$markup .= "</tr>\r\n";		
			$currency_sums_array[$currency]['TOTAL_INVOICED']  += $row['INV_AMOUNT'];
			++$i;
		}
		$colspan = 5;
		foreach ($currency_sums_array as $currency_name => $value) {
			$markup .= '<tr class="total_amount"><td colspan="'.$colspan.'">Total '.$currency_name.'</td>
						<td>'.$currency_name.' '. 
						number_format($value['TOTAL_INVOICED'], NUM_DEC_PLACES, '.', ',').'</td>';
		}
		$markup .= '</table>';
	}
	mysql_free_result($result);
	return $markup;	
}

/*
	Generates search form header for search party page
*/
function generate_form() 
{
	global $sort_by, $prior_sort_by, $sort_order;
	
	$markup  = '<form action="'. $_SERVER['PHP_SELF'] . '" method="post" name="frm_search_x_invoice">' ;
	$markup .= '<input type="hidden" id="sort_by" name="sort_by" value="' . $sort_by . '"/>';
	$markup .= '<input type="hidden" id="sort_order" name="sort_order" value="' . $sort_order . '"/>';
	$markup .= '<input type="hidden" id="prior_sort_by" name="prior_sort_by" value="' . $prior_sort_by . '"/>';	
	$markup .= '</form>';
	return $markup;
}

$page_title = "Search - External Invoices";
require_once("body_header.php"); 
?>
<script type="text/javascript"><!--
// Future enhancement to apply search filters
function submitForm()
{
	//var frm = document.frm_search_invoice;
	//frm.state.value = state;
	//frm.submit();
}
--></script>
<table border="0"  cellpadding="0" cellspacing="0">
<tr>
  <td colspan="3">
	<h4>
	SEARCH RESULTS :: External Invoices	
	</h4>
</td>
</tr>	
<?php
	echo generate_form();
	echo show_records();
?>	

<?php
	include("body_footer.php"); 
?>
