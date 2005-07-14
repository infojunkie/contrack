	
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
  	Single Order Milestones page
	
	This page is responsible for viewing/adding/editing the single order milestones
	
	It has two states: VIEW and PROCESS
	It's in the PROCESS state when a milestone has been deleted
******************************************************************************************/
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
	global  $str_entity, $order_id, $state, $entity_id, $entity_op, $entity_type;
		
	$order_id = isset($_GET['single_order_id']) ? $_GET['single_order_id'] : (isset($_POST['SINGLE_ORDER_ID']) ? $_POST['SINGLE_ORDER_ID'] : -1) ;
	$state	  = isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : state_VIEW) ;
	set_global_vars($order_id);
	$entity_id = isset($_GET['entity_id']) ? $_GET['entity_id'] : (isset($_POST['entity_id']) ? $_POST['entity_id'] : -1) ;
	$entity_op = isset($_GET['entity_op']) ? $_GET['entity_op'] : (isset($_POST['entity_op']) ? $_POST['entity_op'] : -1) ;
	$entity_type = isset($_GET['entity_type']) ? $_GET['entity_type'] : (isset($_POST['entity_type']) ? $_POST['entity_type'] : -1) ;

	$str_entity = "single_order_milestones";  // for fetching security permissions
}
/*
	Get the rest of variables needed for this page
*/
function set_global_vars($order_id)
{
	global	$currency, $order_name;
	$currency = get_sin_order_currency($order_id);
	$order_name = format_sin_order_name($order_id);
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
	This function currently deals with milestone deletion only
*/
function process_form()
{
	global $entity_id, $entity_op, $entity_type, $order_id;
	
	if ( $entity_op == opcode_DELETE ) {		
		if ($entity_type  == entity_MILESTONE) 	delete_milestone($entity_id);
		// Go back to same page after deleting
		$location_url = "Location: single_order_milestones.php?single_order_id=".$order_id."&state=". state_VIEW;
		header($location_url);	
		exit;					
	}
}
/*
	Function: generate_select_milestones_sql ( )
	Desc:  
		Selects all milestones for this single order
 	Params: 
		$single_order_id
   	Returns: 
		SQL Query string
*/	
function generate_select_milestones_sql($single_order_id)
{	
	$select_sql = " SELECT invoice.NUMBER AS INVOICE_NUMBER, milestone.MILESTONE_ID, milestone.NAME, 
					milestone.DESCRIPTION, milestone.MILESTONE_TYPE, 
					milestone.MILESTONE_TYPE+0 AS TYPE_ID, milestone.AMOUNT, milestone.DATE, 
					milestone.MILESTONE_STATUS
					FROM milestone LEFT JOIN invoice_line_item 
						ON invoice_line_item.MILESTONE_ID = milestone.MILESTONE_ID
						LEFT JOIN invoice 
						ON invoice.INVOICE_ID = invoice_line_item.INVOICE_ID
					WHERE milestone.SINGLE_ORDER_ID = ".$single_order_id ." AND milestone.STATE = 'ACTIVE'
					ORDER BY milestone.DATE ASC";
	return $select_sql;
}
/*
	Function: delete_milestone ( )
	Desc:  
		Deletes milestone from milestone table
		Deletes all payments associated with this milestone from payment_b2s  table
 	Params: 
		$milestone_id
   	Returns: 
		Nothing. Milestone and associated payments (if any) are deleted
*/	
function delete_milestone($milestone_id)
{
	if ($milestone_id > 0 ) {
		$sql = "DELETE FROM milestone WHERE MILESTONE_ID = ". $milestone_id; 
		$result = mysql_query($sql) or die(mysql_error());	
		$sql = "DELETE FROM payment_b2s WHERE MILESTONE_ID = ". $milestone_id; 
		$result = mysql_query($sql) or die(mysql_error());	
	}
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
	global $order_id, $currency;
	
	if ($order_id == -1) {
		$markup = '	<table class="info_block">	
					<tr><td>No Matching Records Were Found</td></tr>
					</table>';		
	}
	else {
		$sql = generate_select_milestones_sql($order_id);	
		$result = mysql_query($sql) or die(mysql_error());
		$markup = '<table>';
		$markup .= "<tr class=\"milestones_header\"><td class=\"small_text\" width=\"5%\"><a href=\"javascript:
					void open_popup('single_order_milestone_edit_popup.php?single_order_id=".$order_id.
					"&state=".state_NEW."&currency=".$currency."',									 
					'milestone','menubar=no,toolbar=no,scrollbars=no',310,500);\">
					<img src=\"images/b.add.png\" alt=\"Add\" title=\"Add\"/></a></td>\r\n" ;
		$markup .=	"<td width=\"20%\">Name</td>
					<td width=\"35%\">Description</td>
					<td>Type</td>
					<td>Amount</td>
					<td>Date</td>
					<td>Status</td>				
					</tr>";
		$total_milestones = 0;
		$i = 0;
		while ($row = mysql_fetch_array($result)) {
			$pk      = $row['MILESTONE_ID'];
			$actions = '';
			$invoice_info = '';
			//$invoice_number = get_milestone_invoice_number($pk);
			$invoice_number = $row['INVOICE_NUMBER'];
			if ( $invoice_number == false) {
				$actions = "<a href=\"javascript:
							void open_popup('single_order_milestone_edit_popup.php?single_order_id=".$order_id."&milestone_id=".$pk.
							"&state=".state_EDIT."&currency=".$currency."',									 
							'milestone','menubar=no,toolbar=no,scrollbars=no',310,500);\">
							<img src=\"images/b.edit.png\" alt=\"Edit\" title=\"Edit\"/></a>
							<a href=\"javascript:void entityDelete(entityMILESTONE,". $pk .");\">
							<img src=\"images/b.delete.png\" alt=\"Delete\" title=\"Delete\"/></a>";
			}
			else {
				$invoice_info = 'This milestone belongs to Invoice #: '.$invoice_number;	
				//$actions = $invoice_number;
			}					
			$markup .= "<tr title=\"".$invoice_info."\"class=\"milestones_line" .($i%2). "\">"; 			
			$markup .= "<td class=\"small_text\">";
			$markup .= $actions;
			$markup .= "</td>\r\n";		
			$markup .= "<td title=\"".$invoice_info."\">". mysql_decode($row['NAME'])  . "</td>";											
			$markup .= "<td>". mysql_decode($row['DESCRIPTION'])  . "</td>";											
			$markup .= "<td>". mysql_decode($row['MILESTONE_TYPE'])  . "</td>";											
			$markup .= "<td>". $currency. ' '. number_format($row['AMOUNT'], NUM_DEC_PLACES, '.',',') . "</td>";											
			$markup .= "<td>". format_date_reverse($row['DATE'])  . "</td>";
			$markup .= "<td>". mysql_decode($row['MILESTONE_STATUS'])  . "</td></tr>\r\n";									
			$total_milestones +=  $row['AMOUNT'];									
			++$i;	
		}
		
		if (mysql_num_rows($result)) {  
			$markup .= '<tr class="total_amount">
						<td colspan="4"></td> ';
			$markup .= '<td colspan="3">'.$currency.' '. 
						number_format($total_milestones, NUM_DEC_PLACES, '.', ',').'</td></tr>';		
		}
		$markup .= '<tr><td class="footnote" colspan="7">
					<br>
					You can only edit and/or delete milestones that have not been selected
					as invoice line items.<br>
					Place the cursor over those milestones which do not have Edit/Delete actions to know which invoice they belong to.
					</td></tr>';
		$markup .= "</table>\r\n";	
		mysql_free_result($result);
	}
	return $markup;	
}

/*
	Generates standard form header for invoice
*/
function generate_form() 
{
	global $state, $order_id, $entity_id, $entity_op, $entity_type ;
	
	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_milestones\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"SINGLE_ORDER_ID\" name=\"SINGLE_ORDER_ID\" value=\"" . $order_id . "\"/>\r\n";			
	$str_frm_header .= "<input type=\"hidden\" id=\"entity_id\" name=\"entity_id\" value=\"" . $entity_id . "\"/>\r\n";			
	$str_frm_header .= "<input type=\"hidden\" id=\"entity_type\" name=\"entity_type\" value=\"" . $entity_type . "\"/>\r\n";			
	$str_frm_header .= "<input type=\"hidden\" id=\"entity_op\" name=\"entity_op\" value=\"" . $entity_op . "\"/>\r\n";			
	$str_frm_header .= "</form>";
	return $str_frm_header;
}

$page_title = format_sin_order_name($order_id) . " :: Milestones"; 
include("body_header.php"); 
?>
<script type="text/javascript"><!--
var state = <?= ($state=="") ? 0 : $state ?>;

function submitForm()
{
	var frm = document.frm_milestones;
	frm.state.value = state;
	frm.submit();
}

function entityDelete(entityType, intID) 
{
	if (confirm ('Are you sure you want to delete this milestone?') ) {
		frm = document.frm_milestones;		
		frm.state.value		   = statePROCESS;
		frm.entity_op.value    = opcodeDELETE;
		frm.entity_type.value  = entityType;
		frm.entity_id.value    = intID;		
		frm.submit();	
	}
}
function resubmitForm()
{
	var frm = document.frm_milestones;
	frm.state.value = state;
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
			<table border="0" cellspacing="0"> 
				<tr> 
				<?php 
				/*	Tab Generation 
					Pass the ordinal of the tab to be activated
				*/												
				echo generate_single_order_tabs(3, $order_id);																
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
