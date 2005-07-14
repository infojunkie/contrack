	
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
	Search invoices page
	This page is used to display invoices tabs for the different entities and search for invoices
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

	$glb_arr_sort_indicators['NUMBER'] = '';
	$glb_arr_sort_indicators['RECIPIENT'] = '';
	$glb_arr_sort_indicators['STATUS'] = '';
	$glb_arr_sort_indicators['DATE'] = '';
	$glb_arr_sort_indicators['AMOUNT'] = '';
	$glb_arr_sort_indicators['BALANCE'] = '';
}
/*
	Get all parameters passed to this page and format display string
*/
function get_page_params()
{
	global  $str_entity, $state, $sort_by, $prior_sort_by, $sort_order, $referrer, $referrer_id;
		
	$sort_by 		= isset($_POST['sort_by']) ? $_POST['sort_by'] : "NUMBER";
	$prior_sort_by 	= isset($_POST['prior_sort_by']) ? $_POST['prior_sort_by'] : "";
	$sort_order 	= isset($_POST['sort_order']) ? $_POST['sort_order'] : "ASC";	
	$referrer 		= isset($_GET['referrer']) ? $_GET['referrer'] : 
					(isset($_POST['referrer']) ? $_POST['referrer'] : entity_SEARCH) ;
	$referrer_id 	= isset($_GET['referrer_id']) ? $_GET['referrer_id'] : 
					(isset($_POST['referrer_id']) ? $_POST['referrer_id'] : -1) ;	
	$str_entity = "invoice"; // used to get security permissions
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
function generate_select_invoices_sql($sql_where_clause)
{
	global $sort_by, $sort_order;

	$select_sql = "	SELECT INVOICE_ID, NUMBER, PAYOR_PARTY_ID AS RECIPIENT_ID, payor.NAME AS RECIPIENT, 
					payor.TYPE+0 AS TYPE_ID, payor.TYPE AS TYPE, invoice.STATUS AS STATUS, DATE, 
					BILLED_AMOUNT AS AMOUNT, (BILLED_AMOUNT - PAID_AMOUNT) AS BALANCE, currency.CODE AS CURRENCY_NAME
				   	FROM invoice LEFT JOIN party AS payor ON invoice.PAYOR_PARTY_ID = payor.PARTY_ID
						INNER JOIN currency ON invoice.CURRENCY_ID = currency.CURRENCY_ID "; 
	$select_sql .= $sql_where_clause;
	if (!empty($sort_by)) {
		$select_sql .= " ORDER BY ". $sort_by. " ". $sort_order;
	}
	else {
		$select_sql .= " ORDER BY NUMBER ASC";
	}	
	return $select_sql;
}

/*
	Function: generate_select_order_invoices_sql ( )
	Desc:  
		Selects all invoice attributes from invoice table by joining to invoice_rel_order table
		to get order invoices (general and single)
 	Params: 
		$sql_where_clause
   	Returns: 
		SQL Query string
*/	
function generate_select_order_invoices_sql($sql_where_clause)
{
	global $sort_by, $sort_order;

	$select_sql = "	SELECT invoice.INVOICE_ID, NUMBER, PAYOR_PARTY_ID AS RECIPIENT_ID, 
					payor.NAME AS RECIPIENT, payor.TYPE+0 AS TYPE_ID, payor.TYPE AS TYPE, 
					invoice.STATUS AS STATUS, DATE,	BILLED_AMOUNT AS AMOUNT, 
					(BILLED_AMOUNT - PAID_AMOUNT) AS BALANCE, currency.CODE AS CURRENCY_NAME
					FROM invoice LEFT JOIN party AS payor ON invoice.PAYOR_PARTY_ID = payor.PARTY_ID
                   		INNER JOIN invoice_rel_order ON invoice.INVOICE_ID = invoice_rel_order.INVOICE_ID
						INNER JOIN currency ON invoice.CURRENCY_ID = currency.CURRENCY_ID "; 
	$select_sql .= $sql_where_clause;
	if (!empty($sort_by)) {
		$select_sql .= " ORDER BY ". $sort_by. " ". $sort_order;
	}
	else {
		$select_sql .= " ORDER BY NUMBER ASC";
	}	
	return $select_sql;
}

/*
	Generate SQL depending on referrer type and referrer id
*/
function generate_select_invoice_sql()
{
	global $referrer, $referrer_id;
	
	switch ($referrer) {
		case entity_SINGLE_ORDER:		
		$where_sql = " WHERE invoice_rel_order.SINGLE_ORDER_ID = ".$referrer_id." ";
		$sql = generate_select_order_invoices_sql($where_sql);
		break;		
		case entity_GEN_ORDER:
		$where_sql = " WHERE invoice_rel_order.GEN_ORDER_ID = ".$referrer_id." ";
		$sql = generate_select_order_invoices_sql($where_sql);
		break;
		case entity_BUYER:
		case entity_SUPPLIER:				
		$where_sql = " WHERE invoice.PAYOR_PARTY_ID = ".$referrer_id." ";
		$sql = generate_select_invoices_sql($where_sql);
		break;		
		case entity_SEARCH:
		$where_sql = "";
		$sql = generate_select_invoices_sql($where_sql);
		break;
		default:
		$where_sql = "";
		$sql = generate_select_invoices_sql($where_sql);
	}
	return $sql;
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
	global $glb_arr_sort_indicators, $referrer, $sort_by;
	
	$sql = generate_select_invoice_sql();
	$result = mysql_query($sql) or die(mysql_error());	
	
	if (!mysql_num_rows($result)) {
		$markup = '	<table class="info_block">	
					<tr><td>No Matching Records Were Found</td></tr>
					</table></td></tr> ';		
	} 
	else {
		$css_class = ($referrer == entity_SEARCH ? 'search_results_container' : '');
		$markup = '<table class="'.$css_class.'">
				   <tr class="search_results_header"><td><strong><a href="javascript:doSort(document.frm_search_invoice,\'NUMBER\',\''.$sort_by.'\')">Invoice Number</a></strong>&nbsp;'. $glb_arr_sort_indicators['NUMBER'].'</td>';
		if ($referrer == entity_SEARCH) {
			$markup .= '<td><strong><a href="javascript:doSort(document.frm_search_invoice,\'RECIPIENT\',\''.$sort_by.'\')">Recipient</a></strong>&nbsp;'.$glb_arr_sort_indicators['RECIPIENT'].'</td>';
		}
		$markup .= '<td><strong><a href="javascript:doSort(document.frm_search_invoice,\'STATUS\',\''.$sort_by.'\')">Status</a></strong>&nbsp;'.$glb_arr_sort_indicators['STATUS'].'</td>
				    <td><strong><a href="javascript:doSort(document.frm_search_invoice,\'DATE\',\''.$sort_by.'\')">Date</a></strong>&nbsp;'.$glb_arr_sort_indicators['DATE'].'</td>
				    <td><strong><a href="javascript:doSort(document.frm_search_invoice,\'AMOUNT\',\''.$sort_by.'\')">Amount</a></strong>&nbsp;'.$glb_arr_sort_indicators['AMOUNT'].'</td>
				    <td><strong><a href="javascript:doSort(document.frm_search_invoice,\'BALANCE\',\''.$sort_by.'\')">Balance</a></strong>&nbsp;'.$glb_arr_sort_indicators['BALANCE'].'</td>
				    </tr>';				
		$i = 0;
		$currency_sums_array = array();
		while ($row = mysql_fetch_array($result)) {
			$currency = $row['CURRENCY_NAME'];
			if (!array_key_exists($currency, $currency_sums_array)) {
				$currency_sums_array[$currency]['TOTAL_INVOICED'] = 0;
				$currency_sums_array[$currency]['TOTAL_BALANCE'] = 0;
			}
			$markup  .= "<tr class=\"search_results" .($i%2). "\">" ;
			$markup  .= "<td><a href=\"invoice.php?invoice_id=".$row['INVOICE_ID']."&state=".state_VIEW."\">".$row['NUMBER']."</a></td>";
			if ($referrer == entity_SEARCH) {
				$markup .= "<td><a href=\"party.php?party_id=".$row['RECIPIENT_ID']."&type=".$row['TYPE_ID']."&state=".state_VIEW."\">".
							capitalise_first_letter($row['TYPE'])."::".$row['RECIPIENT']."</a></td>";						
			}
			$markup .= "<td>".$row['STATUS']."</td>";
			$markup .= "<td>".format_date_reverse($row['DATE'])."</td>";
			$markup .= "<td>".$currency. " ". number_format($row['AMOUNT'], NUM_DEC_PLACES,'.',',')."</td>";
			$markup .= "<td>".$currency. " ". number_format($row['BALANCE'], NUM_DEC_PLACES, '.', ',')."</td>";			
			$markup .= "</tr>\r\n";					
			$currency_sums_array[$currency]['TOTAL_INVOICED']  += $row['AMOUNT'];
			$currency_sums_array[$currency]['TOTAL_BALANCE']   += $row['BALANCE'];
			++$i;
		}
		$colspan =($referrer==entity_SEARCH ? 4 :3);
		foreach ($currency_sums_array as $currency_name => $value) {
			$markup .= '<tr class="total_amount"><td colspan="'.$colspan.'">Total '.$currency_name.'</td>
						<td>'.$currency_name.' '. 
						number_format($value['TOTAL_INVOICED'], NUM_DEC_PLACES, '.', ',').'</td>';
			$markup .= '<td>'.$currency_name.' '. 
						number_format($value['TOTAL_BALANCE'], NUM_DEC_PLACES, '.', ',').'</td></tr>';		
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
	global $sort_by, $prior_sort_by, $sort_order, $referrer, $referrer_id;
	
	$markup  = '<form action="'. $_SERVER['PHP_SELF'] . '" method="post" name="frm_search_invoice">' ;
	$markup .= '<input type="hidden" id="sort_by" name="sort_by" value="' . $sort_by . '"/>';
	$markup .= '<input type="hidden" id="sort_order" name="sort_order" value="' . $sort_order . '"/>';
	$markup .= '<input type="hidden" id="prior_sort_by" name="prior_sort_by" value="' . $prior_sort_by . '"/>';	
	$markup .= '<input type="hidden" id="referrer" name="referrer" value="' . $referrer . '"/>';	
	$markup .= '<input type="hidden" id="referrer_id" name="referrer_id" value="' . $referrer_id . '"/>';	
	$markup .= '</form>';
	return $markup;
}
/*
	Set page title according to referrer type
*/
function get_page_title()
{
	global $referrer, $referrer_id;
	
	switch ($referrer) {
		case entity_BUYER:
		case entity_SUPPLIER:
		$page_name = get_party_name($referrer_id). " :: Invoices";
		break;		
		case entity_GEN_ORDER:
		$page_name = format_gen_order_name ($referrer_id). " :: Invoices";
		break;
		case entity_SINGLE_ORDER:
		$page_name = format_sin_order_name($referrer_id) . " :: Invoices";
		break;
		case entity_SEARCH:
		$page_name = "Search - Invoices"; 
		break;
		default:
		$page_name = "Invoices";
	}	
	return $page_name;
}

$page_title = get_page_title();
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
	<?php 
	if  (($referrer_id == -1) && ($referrer != entity_SEARCH)) {
		echo "New " . set_str_entity_type($referrer);
	}
	else {
		if ($referrer == entity_GEN_ORDER) {	
			echo "GENERAL ORDER" ." :: ". format_gen_order_name($referrer_id); 
		}
		elseif ($referrer == entity_SINGLE_ORDER) {	
			echo "SINGLE ORDER" ." :: ". format_sin_order_name($referrer_id); 
		}
		elseif ($referrer == entity_SEARCH) {
			echo "SEARCH RESULTS ::  ";
			if (!empty($str_entity)) { 
				echo "Invoices " ;
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
				case entity_BUYER:
				echo generate_buyer_tabs(2, $referrer_id, state_VIEW);
				break;
				case entity_SUPPLIER:
				echo generate_supplier_tabs(2, $referrer_id, state_VIEW);
				break;
				case entity_GEN_ORDER:
				echo generate_gen_order_tabs(2, $referrer_id, state_VIEW);
				break;
				case entity_SINGLE_ORDER:
				echo generate_single_order_tabs(4, $referrer_id, state_VIEW);
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
