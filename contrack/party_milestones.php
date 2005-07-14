	
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
  	Party Milestones page
	
	This page is responsible for viewing the party milestones 
	
	Agent    ==> Commission Milestones
		- Commission Milestones are all the milestones belonging to single orders belonging to 
		  general orders featuring that agent and have Agent as recipient (i.e. the milestone is 
		  of type "Agent commission payment")
	
	Buyer 	 ==> Organization Milestones, Supplier Milestones
		- Organization milestones are all the milestones belonging to single orders belonging to general 
		  orders featuring that buyer and have the organization as recipient (i.e. the milestone is of 
		  type "Incoming payment").
		- Supplier milestones are the milestones belonging to single orders belonging to general 
		  orders featuring that buyer and have supplier as recipient (i.e. the milestone is of type 
		  "B2S payment")
	
	Supplier ==> Organization Milestones, Buyer Milestones
		- Organization Milestones are all the milestones belonging to single orders featuring that 
		  supplier have the organization as recipient (i.e. the milestone is of type "Incoming payment")
		- Buyer milestones are the milestones belonging to single orders featuring that supplier 
		  and have supplier as recipient (i.e. the milestone is of type "B2S  payment")
		
******************************************************************************************/
require_once("db_utils.php");
require_once("general_utils.php");
require_once("order_utils.php");
require_once( "patUser-2.2.3/prepend.php" );
require_once("permissions.php");


init_page();
//process_state();

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
	global  $page_name, $sort_by, $prior_sort_by, $sort_order, $referrer, $referrer_id, $milestone_type;
		
	// Get sort parameters
	$sort_by 	= isset($_POST['sort_by']) ? $_POST['sort_by'] : 'DATE';
	$prior_sort_by 	= isset($_POST['prior_sort_by']) ? $_POST['prior_sort_by'] : "";
	$sort_order = isset($_POST['sort_order']) ? $_POST['sort_order'] : "ASC";
	
	$referrer = isset($_GET['referrer']) ? $_GET['referrer'] : 
			   (isset($_POST['referrer']) ? $_POST['referrer'] : entity_BUYER) ;
	$referrer_id = isset($_GET['referrer_id']) ? $_GET['referrer_id'] : 
				  (isset($_POST['referrer_id']) ? $_POST['referrer_id'] : -1) ;
	$milestone_type = isset($_GET['milestone_type']) ? $_GET['milestone_type'] : 
				  (isset($_POST['milestone_type']) ? $_POST['milestone_type'] : milestone_INCOMING_PAYMENT) ;			
	
	// Based on referrer, get $page_name to fetch security permissions
	$page_name = get_page_name($referrer, $milestone_type);  // for fetching security permissions
}

function init_sort_indicators()
{
	global $glb_arr_sort_indicators;
	
	$glb_arr_sort_indicators['GO_FRIENDLY_NAME'] = '';
	$glb_arr_sort_indicators['SO_FRIENDLY_NAME'] = '';
	$glb_arr_sort_indicators['NAME'] = '';
	$glb_arr_sort_indicators['AMOUNT'] = '';
	$glb_arr_sort_indicators['DATE'] = '';
	$glb_arr_sort_indicators['MILESTONE_STATUS'] = '';
}
/*
	Based on referrer, get $page_name to fetch security permissions
	$part has to be synchronized with PART field in the PERMISSIONS table 
	in the database
*/
function get_page_name($referrer, $milestone_type)
{
	switch ($referrer) {
		case entity_AGENT:
		$part = 'agent_commissions';
		break;
		case entity_SUPPLIER:
		$part = ($milestone_type == milestone_INCOMING_PAYMENT ? 'buyer_to_organization_milestones':'buyer_supplier_milestones' );
		break;
		case entity_BUYER:
		$part = ($milestone_type == milestone_INCOMING_PAYMENT ? 'supplier_to_organization_milestones':'supplier_buyer_milestones' );
		break;
		default:
		$part = '';
	}
	return $part;
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
	global $user, $page_name;
			
	$user_perms = get_user_permissions ($user, $page_name);	
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
	
*/
function process_form()
{
	
}
/*
	Function: generate_select_milestones_sql  ( )
	Desc:  
		Selects all milestones for the given params
 	Params: 
		$referrer, $referrer_id, $milestone_type
   	Returns: 
		SQL Query string
*/	
function generate_select_milestones_sql($referrer, $referrer_id, $milestone_type)
{	
	global $sort_by, $sort_order;
	
	$select_sql = " SELECT  milestone.MILESTONE_ID, gen_order.GEN_ORDER_ID AS GO_ORDER_ID,  
							gen_order.FRIENDLY_NAME AS GO_FRIENDLY_NAME,
							gen_order.CODE AS GO_CODE, gen_order.CLIENT_ORDER_ID AS GO_CLIENT_ID, 
							single_order.SINGLE_ORDER_ID AS SO_ORDER_ID,  single_order.FRIENDLY_NAME AS SO_FRIENDLY_NAME,
							single_order.CODE AS SO_CODE, single_order.CLIENT_ORDER_ID AS SO_CLIENT_ID,  
							milestone.MILESTONE_ID, milestone.NAME, milestone.MILESTONE_TYPE, currency.CODE AS CURRENCY,
							milestone.AMOUNT, milestone.DATE, milestone.MILESTONE_STATUS
					FROM 
							milestone INNER JOIN single_order ON milestone.SINGLE_ORDER_ID = single_order.SINGLE_ORDER_ID
							INNER JOIN gen_order ON gen_order.GEN_ORDER_ID = single_order.GEN_ORDER_ID
							INNER JOIN currency ON gen_order.CURRENCY_ID = currency.CURRENCY_ID
					WHERE 
							milestone.STATE = 'ACTIVE' ";
	$select_sql .= generate_extra_where_condition($referrer, $referrer_id);
	$select_sql .= " AND milestone.MILESTONE_TYPE = ". $milestone_type." ";
	if (!empty($sort_by)) {
		$select_sql .= " ORDER BY ". $sort_by. " ". $sort_order;
	}
	else {
		$select_sql .= " ORDER BY DATE ASC";
	}	
	return $select_sql;
}
/*
	Generate the additional condition in the SQL where clause depending on the referrer type and 
	referrer id
*/
function generate_extra_where_condition($referrer, $referrer_id)
{
	switch ($referrer) {		
		case entity_BUYER:
		$extra_where_condition = " AND gen_order.BUYER_ID = ".$referrer_id;
		break;		
		case entity_SUPPLIER:				
		$extra_where_condition = " AND single_order.SUPPLIER_ID = ".$referrer_id;
		break;		
		case entity_AGENT:
		$extra_where_condition = " AND gen_order.AGENT_ID = ".$referrer_id;
		break;
		default:
		$extra_where_condition = "";
	}	
	return $extra_where_condition;
}

/*
	Function: show_milestones ( )
	Desc:  
		Gets records from database and outputs them in the correct format
 	Params: 
    	None
   	Returns: 
		HTML markup string
*/	
function show_milestones()
{
	global $referrer, $referrer_id, $milestone_type, $glb_arr_sort_indicators, $sort_by;
	
	$sql = generate_select_milestones_sql($referrer, $referrer_id, $milestone_type);	
	$result = mysql_query($sql) or die(mysql_error());	
	
	if (!mysql_num_rows($result)) {
		$markup = '	<table class="info_block">	
					<tr><td>No Matching Records Were Found</td></tr>
					</table>';		
	} 
	else {
		$markup = '';
		$markup .= '<table>';
		$markup .= "<tr class=\"search_results_header\">";
		if ($referrer != entity_SUPPLIER) {
			$markup .= '<td width="20%"><a href="javascript:doSort(document.frm_party_milestones,\'GO_FRIENDLY_NAME\',\''.$sort_by.'\')">General Order ID</a>&nbsp;'.$glb_arr_sort_indicators['GO_FRIENDLY_NAME'].'</td>';
		}
		$markup .= '<td width="20%"><a href="javascript:doSort(document.frm_party_milestones,\'SO_FRIENDLY_NAME\',\''.$sort_by.'\')">Single Order ID</a>&nbsp;'.$glb_arr_sort_indicators['SO_FRIENDLY_NAME'].'</td>';
		$markup .= '<td><a href="javascript:doSort(document.frm_party_milestones,\'NAME\',\''.$sort_by.'\')">Name</a>&nbsp;'.$glb_arr_sort_indicators['NAME'].'</td>
					<td><a href="javascript:doSort(document.frm_party_milestones,\'AMOUNT\',\''.$sort_by.'\')">Amount</a>&nbsp;'.$glb_arr_sort_indicators['AMOUNT'].'</td>
					<td><a href="javascript:doSort(document.frm_party_milestones,\'DATE\',\''.$sort_by.'\')">Date</a>&nbsp;'.$glb_arr_sort_indicators['DATE'].'</td>
					<td><a href="javascript:doSort(document.frm_party_milestones,\'MILESTONE_STATUS\',\''.$sort_by.'\')">Status</a>&nbsp;'.$glb_arr_sort_indicators['MILESTONE_STATUS'].'</td>
					</tr>';
		$i = 0;
		$currency_sums_array = array();
		while ($row = mysql_fetch_array($result)) {
			$currency = $row['CURRENCY'];
			if (!array_key_exists($currency, $currency_sums_array)) {
				$currency_sums_array[$currency]['TOTAL'] = 0;
			}
			$invoice_info = '';
			$invoice_number = get_milestone_invoice_number($row['MILESTONE_ID']);
			if ( $invoice_number) {
				$invoice_info = 'This milestone belongs to Invoice#: '.$invoice_number;	
			}				
			$markup .= "<tr title=\"".$invoice_info."\"class=\"milestones_line" .($i%2). "\">"; 					
			if ($referrer != entity_SUPPLIER) {
				$markup .= "<td>"."<a href=\"search_order.php?entity=".entity_SINGLE_ORDER."&referrer=".entity_GEN_ORDER."&referrer_id=".$row['GO_ORDER_ID']."\">". mysql_decode($row['GO_FRIENDLY_NAME'])."(".mysql_decode($row['GO_CODE']).
								"/".mysql_decode($row['GO_CLIENT_ID']).")". "</a></td>";											
			}
			$markup .= "<td>"."<a href=\"single_order_milestones.php?single_order_id=".$row['SO_ORDER_ID']."&state=".state_VIEW."\">" .mysql_decode($row['SO_FRIENDLY_NAME'])."(".mysql_decode($row['SO_CODE']).
								"/".mysql_decode($row['SO_CLIENT_ID']).")". "</a></td>";											
			$markup .= "<td>". mysql_decode($row['NAME'])  . "</td>";											
			$markup .= "<td>". $currency . ' '. number_format($row['AMOUNT'], NUM_DEC_PLACES, '.',',') . "</td>";											
			$markup .= "<td>". format_date_reverse($row['DATE'])  . "</td>";								
			$markup .= "<td>". mysql_decode($row['MILESTONE_STATUS'])  . "</td>\r\n";									
			$currency_sums_array[$currency]['TOTAL'] += $row['AMOUNT'];
			++$i;
		}
		$colspan = ($referrer==entity_SUPPLIER ? 2 : 3);
		foreach ($currency_sums_array as $currency_name => $value) {
			$markup .= '<tr class="total_amount"><td colspan="'.$colspan.'">Total '.$currency_name.'</td>
						<td>'.$currency_name.' '. 
						number_format($value['TOTAL'], NUM_DEC_PLACES, '.', ',').'</td></tr>';
		}	
		$markup .= "</table>\r\n";
	}
	mysql_free_result($result);
	return $markup;	
}

/*
	Generates search form header for search party page
*/
function generate_form() 
{
	global $sort_by, $prior_sort_by, $sort_order, $referrer, $referrer_id, $milestone_type;
	
	$markup  = '<form action="'. $_SERVER['PHP_SELF'] . '" method="post" name="frm_party_milestones">' ;
	$markup .= '<input type="hidden" id="sort_by" name="sort_by" value="' . $sort_by . '"/>';
	$markup .= '<input type="hidden" id="sort_order" name="sort_order" value="' . $sort_order . '"/>';
	$markup .= '<input type="hidden" id="prior_sort_by" name="prior_sort_by" value="' . $prior_sort_by . '"/>';	
	$markup .= '<input type="hidden" id="referrer" name="referrer" value="' . $referrer . '"/>';	
	$markup .= '<input type="hidden" id="referrer_id" name="referrer_id" value="' . $referrer_id . '"/>';	
	$markup .= '<input type="hidden" id="milestone_type" name="milestone_type" value="' . $milestone_type . '"/>';		
	$markup .= '</form>';
	return $markup;
}
function get_page_title()
{
	global $referrer, $referrer_id, $milestone_type;
	
	switch ($referrer) {
		case entity_BUYER:
		$str_milestone_type = ($milestone_type == milestone_INCOMING_PAYMENT ? 'Organization Milestones' :'Supplier Milestones');
		$page_name = get_party_name($referrer_id). " :: ".$str_milestone_type;
		break;
		case entity_SUPPLIER:
		$str_milestone_type = ($milestone_type == milestone_INCOMING_PAYMENT ? 'Organization Milestones' :'Buyer Milestones');
		$page_name = get_party_name($referrer_id). " :: ".$str_milestone_type;
		break;		
		case entity_AGENT:
		$page_name = get_party_name($referrer_id). " :: Commission Milestones";
		break;		
		default:
		$page_name = "Milestones";
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
			if ($referrer_id == -1 ) {
				echo "New " . set_str_entity_type($referrer) ;
			}
			else {
				echo strtoupper (set_str_entity_type($referrer)) ." :: ". get_party_name($referrer_id); 
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
				switch ($referrer) {				
					case entity_BUYER:
					$tab_ordinal = ($milestone_type == milestone_INCOMING_PAYMENT ? 3 : 4);
					echo generate_buyer_tabs($tab_ordinal, $referrer_id, state_VIEW);
					break;
					case entity_SUPPLIER:
					$tab_ordinal = ($milestone_type == milestone_INCOMING_PAYMENT ? 3 : 4);
					echo generate_supplier_tabs($tab_ordinal, $referrer_id, state_VIEW);
					break;
					case entity_AGENT:
					echo generate_agent_tabs(2, $referrer_id, state_VIEW);
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
					<tr><td colspan="8">
					<?= generate_form(); ?>
					<?=	generate_filters_headers(entity_MILESTONE); ?>						
					</td></tr>
				<tr><td>
				<?php 						
				echo show_milestones(); 
				?>
				</td></tr>						
	</table>
	</td>
	</tr>
</table>
<?php
	include("body_footer.php"); 
?>
