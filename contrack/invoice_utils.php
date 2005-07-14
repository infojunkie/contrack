	
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
  	This file contains invoice utilities functions 
******************************************************************************************/
/*
	Calculate billed amount for this invoice
	Returns current billed amount for the given invoice
*/
function calculate_billed_amount($invoice_id)
{
	$total_billed_amount = 0;
	$sql = "SELECT 
			SUM(IF (lineitem.TYPE =  'Milestone',  milestone.AMOUNT ,lineitem.AMOUNT )) AS TOTAL_BILLED
			FROM invoice_line_item AS lineitem
				LEFT  JOIN milestone ON lineitem.MILESTONE_ID = milestone.MILESTONE_ID
			WHERE INVOICE_ID = ".$invoice_id."  AND lineitem.STATE = 'ACTIVE'
			GROUP  BY invoice_id";
	$result = mysql_query($sql) or die(mysql_error());	
	if (!$result) return mysql_error();
	if (mysql_num_rows($result)) {
		if ($row = mysql_fetch_array($result)) {
			$total_billed_amount = $row['TOTAL_BILLED'];
		}
	}	
	return $total_billed_amount ;
}
/*
	Calculate paid amount for this invoice
	Returns paid amount for the given invoice
*/
function calculate_paid_amount($invoice_id)
{
	$total_paid_amount = 0;
	$sql = "SELECT SUM(amount) AS TOTAL_PAID
			FROM payment
			WHERE INVOICE_ID = ".$invoice_id." 
			GROUP BY  INVOICE_ID";
	$result = mysql_query($sql) or die(mysql_error());	
	if (!$result) return mysql_error();
	if (mysql_num_rows($result)) {
		if ($row = mysql_fetch_array($result)) {
			$total_paid_amount = $row['TOTAL_PAID'];
		}
	}	
	return $total_paid_amount ;
}

/*
	Generate SQL to update BILLED_AMOUNT field in invoice table with amount of line item that has 
	just been added/deleted
*/
function generate_update_invoice_billed_amount($invoice_id)
{
	// Get current billed amount then set it in invoice table
	$current_billed_amount = calculate_billed_amount ($invoice_id);
	$update_sql = " UPDATE invoice
					SET BILLED_AMOUNT = " .$current_billed_amount.
				  " WHERE INVOICE_ID = ". $invoice_id ;
	return $update_sql;
}

/*
	Generate SQL to update PAID_AMOUNT field in invoice table with amount payment that has just been
	added/deleted
*/
function generate_update_invoice_paid_amount($invoice_id)
{
	// Get current paid amount then set it in invoice table
	$current_paid_amount = calculate_paid_amount ($invoice_id);
	$update_sql = " UPDATE invoice
					SET PAID_AMOUNT = " .$current_paid_amount.
				  " WHERE INVOICE_ID = ". $invoice_id ;
	return $update_sql;
}

/*
	Generates select SQL for Invoice
	Notes:
		invoice.STATUS+0 is to retrieve the ENUM in a numerical context to get the index
		i.e. return 1 instead of 'DRAFT'. 
		Same for payor.TYPE+0
*/	
function generate_select_invoice_sql($invoice_id)
{
	$select_sql = "	SELECT INVOICE_ID, NUMBER, invoice.STATUS+0 AS STATUS_ID, invoice.STATUS AS STATUS, 
						   DATE, MESSAGE, invoice.DESCRIPTION, PAYOR_PARTY_ID, payor.NAME AS PAYOR_NAME,
						   payor.TYPE+0 AS PAYOR_TYPE, 
						   PAYOR_CONTACT_ID, payor_contact.NAME  AS PAYOR_CONTACT_NAME, 
						   PAYEE_CONTACT_ID, payee_contact.NAME AS PAYEE_CONTACT_NAME, 
						   invoice.CURRENCY_ID AS CURRENCY_ID, currency.CODE AS CURRENCY_NAME, 
						   currency.NAME AS CURRENCY_DESC, invoice.PAYMENT_TERMS AS PAYMENT_TERMS
				   	FROM invoice LEFT JOIN party AS payor ON invoice.PAYOR_PARTY_ID = payor.PARTY_ID
					LEFT JOIN party AS payor_contact ON invoice.PAYOR_CONTACT_ID = payor_contact.PARTY_ID
					LEFT JOIN party AS payee_contact ON invoice.PAYEE_CONTACT_ID = payee_contact.PARTY_ID
					LEFT JOIN currency ON invoice.CURRENCY_ID = currency.CURRENCY_ID
				  	WHERE INVOICE_ID=".$invoice_id;	
	return $select_sql;
}

/*
	Format balance  
*/
function format_balance($billed_amount, $total_payments)
{
	$balance = $billed_amount - $total_payments;
	return ($balance < 0 ? '('.number_format(abs($balance),NUM_DEC_PLACES, '.', ',').')' : 
							   number_format($balance, NUM_DEC_PLACES, '.', ','));
}

/*
	Returns the currency code for the invoice
*/
function get_invoice_currency($invoice_id)
{	
	if  ( ($invoice_id != 0 ) && (!empty($invoice_id)) ){
		$sql = 'SELECT currency.CODE
				FROM invoice INNER JOIN currency ON currency.CURRENCY_ID = invoice.CURRENCY_ID
				WHERE invoice.INVOICE_ID ='. $invoice_id;
		$result = mysql_query($sql) or die(mysql_error());	
		$row = mysql_fetch_array($result);
		if (mysql_num_rows($result)){
			return $row['CODE'];
		}
		else {
			return "";
		}
	}
	else {
		return "";
	}	
}

?>