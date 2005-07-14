	
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
  	Invoice Payments page
	
	This page is responsible for diplaying the invoice's payments.

******************************************************************************************/
require_once("db_utils.php");
require_once("general_utils.php");
require_once("invoice_utils.php");
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
	global  $str_entity, $invoice_id, $state;
		
	$invoice_id	= isset($_GET['invoice_id']) ? $_GET['invoice_id'] : (isset($_POST['INVOICE_ID']) ? $_POST['INVOICE_ID'] : -1) ;
	$state	= isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : state_VIEW) ;
	set_global_vars($invoice_id);
	$str_entity = "invoice_payments";  // for fetching security permissions
}
/*
	Get the rest of variables needed for this page
*/
function set_global_vars($invoice_id)
{
	global	$currency, $billed_amount, $invoice_number;
		
	$sql = generate_select_invoice_sql($invoice_id);	
	$result = mysql_query($sql) or die(mysql_error());	
	if (!$result) return mysql_error();
	$row = mysql_fetch_array($result);	
	
	$currency 		= mysql_decode($row['CURRENCY_NAME']);
	$invoice_number = mysql_decode($row['NUMBER']);
	$billed_amount 	= calculate_billed_amount($invoice_id);
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
	Function: generate_select_payments_sql ( )
	Desc:  
		Selects all invoice payments for this invoice from payments table 
 	Params: 
		$invoice_id
   	Returns: 
		SQL Query string
*/	
function generate_select_payments_sql($invoice_id)
{	
	$select_sql = " SELECT PAYMENT_ID, DATE, AMOUNT, DOCUMENT_REF
					FROM payment
					WHERE INVOICE_ID = " .$invoice_id." AND STATE = 'ACTIVE'
					ORDER BY DATE ASC";
	return $select_sql;
}

/*
	Function: show_payments ( )
	Desc:  
		Gets records from database and outputs them in the correct format
 	Params: 
    	None
   	Returns: 
		HTML markup string
*/	
function show_payments()
{
	global $invoice_id, $billed_amount, $currency;
	
	if ($invoice_id == -1) {
		$markup = '<table class="info_block">	
					<tr><td> No Matching Records Were Found</td></tr>
					</table>';		
	}
	else {
		$sql = generate_select_payments_sql($invoice_id);	
		$result = mysql_query($sql) or die(mysql_error());			
		$markup = " <table>
					<tr class=\"payments_header\"><td class=\"small_text\" width=\"5%\"><a href=\"javascript:
					void open_popup('invoice_payment_edit_popup.php?invoice_id=".$invoice_id.
					"&state=".state_NEW."',									 
					'invoicePayment','menubar=no,toolbar=no,scrollbars=no',180,465);\">
					<img src=\"images/b.add.png\" alt=\"Add\" title=\"Add\"/></a></td>\r\n" ;
		$markup .=	"<td>Date</td>
					<td>Document Ref</td>
					<td>Amount</td></tr>";
		$total_payments = 0;
		$i = 0;
		while ($row = mysql_fetch_array($result)) {
			$pk      = $row['PAYMENT_ID'];			
			$markup .= "<tr class=\"payments_line" .($i%2). "\">"; 			
			$markup .= "<td class=\"small_text\">";
			$markup .= "<a href=\"javascript:
						void open_popup('invoice_payment_edit_popup.php?invoice_id=".$invoice_id."&payment_id=".$pk.
						"&state=".state_EDIT."',									 
						'invoicePayment','menubar=no,toolbar=no,scrollbars=no',180,465);\">
						<img src=\"images/b.edit.png\" alt=\"Edit\" title=\"Edit\"/></a>";		
			$markup .= "<td>". format_date_reverse($row['DATE'])  . "</td>";
			$markup .= "<td>". mysql_decode($row['DOCUMENT_REF'])  . "</td>";									
			$markup .= "<td>". $currency. ' '. number_format($row['AMOUNT'], 2, '.',',') . "</td></tr>";									
			$total_payments +=  $row['AMOUNT'];									
			++$i;	
		}
		
		$markup .= '<tr class="total_amount"><td colspan="3">Total Payments</td>
					<td>'.$currency.' '. 
					number_format($total_payments, NUM_DEC_PLACES, '.', ',').'</td></tr>';
		$markup .= '<tr class="total_amount"><td colspan="3">Total Invoice Amount</td>
					<td>'.$currency.' '. 
					number_format($billed_amount, NUM_DEC_PLACES, '.', ',').'</td></tr>';
		$markup .= '<tr class="total_amount"><td colspan="3">Invoice Balance</td>
					<td>'.$currency.' '. 
					format_balance($billed_amount, $total_payments).'</td></tr>';		
		mysql_free_result($result);
	}
	$markup .= '</table>';
	return $markup;	
}

/*
	Generates standard form header for invoice payments page
*/
function generate_form() 
{
	global $state, $invoice_id ;
	
	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_invoice_payments\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"INVOICE_ID\" name=\"INVOICE_ID\" value=\"" . $invoice_id . "\"/>\r\n";			
	$str_frm_header .= "</form>";
	return $str_frm_header;
}

$page_title = $invoice_number ." :: Payments"; 
require_once("body_header.php"); 
?>
<script type="text/javascript"><!--
var state = <?= ($state=="") ? 0 : $state ?>;

function resubmitForm()
{
	var frm = document.frm_invoice_payments;
	frm.state.value = state;
	frm.submit();
}
--></script>
<table border="0"  cellpadding="0" cellspacing="0">
	<tr>
		<td colspan="3">
			<h4>
			<?php 
			if ($invoice_id != -1 ) { 					
				echo "INVOICE" ." :: ". $invoice_number; 
			} 
			else {
				echo "New Invoice" ; 
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
				echo generate_invoice_tabs(2, $invoice_id);																
				?>
				</tr>
			</table> 					
		</td>
	</tr> <!-- End of Tabs -->			

<tr>
	<td colspan="3"> 		
		<table class="tab_content">				
					<tr><td colspan="4">
						<?= generate_form(); ?>						
					</td></tr>
				<tr><td>
				<?php 						
				echo show_payments(); 
				?>
				</td></tr>								
		</table>
	</td>
</tr>
</table>
<?php
	include("body_footer.php"); 
?>
