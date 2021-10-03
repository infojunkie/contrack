
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
require_once("errors.php");
require_once("db_utils.php");


/******************************************************************************************
  	This file contains general utilities functions and constants
******************************************************************************************/

//  Set Uploads directory and converts backwards slashes to forward slashes
$upload_dir = str_replace("\\","/", getcwd().'\\'.'uploads'.'\\');

/****** These constants have to be syncrhonized with the database enums ******/

// Company profile party_id. This should by synched up with party table in DB
define ("COMPANY_PARTY_ID", 1);

// Entity Types
define ("entity_BUYER", 1);
define ("entity_SUPPLIER", 2);
define ("entity_AGENT", 3);
define ("entity_EXTERNAL", 4);
define ("entity_INTERNAL", 5);
define ("entity_INVOICE", 6);
define ("entity_GEN_ORDER", 7);
define ("entity_SINGLE_ORDER", 8);
define ("entity_PRODUCT", 9);
define ("entity_MILESTONE", 10);
define ("entity_PROD_SUMMARY", 11);
define ("entity_PROD_PHASE", 12);
define ("entity_PROD_STEP", 13);
define ("entity_PAYMENT", 14);
define ("entity_USER", 15);
define ("entity_ADDITIONAL_COST", 16);
define ("entity_CURRENCY", 98);
define ("entity_SEARCH", 99);

//State Names
define ("state_VIEW", 1);
define ("state_EDIT", 2);
define ("state_NEW", 3);
define ("state_PROCESS", 4);
define ("state_CLOSEPOPUP", 5);

//Operation Codes (For invoices mostly)
define ("opcode_DELETE", 1);
define ("opcode_SAVE", 2);
define ("opcode_SEND", 3);
define ("opcode_CREDIT", 4);
define ("opcode_CLOSE", 5);
define ("opcode_PRINT", 6);
define ("opcode_REMOVE", 7);
define ("opcode_ADDXPAYMENT", 8);

//Invoice Statuses
define ("invoice_CLOSED", 1);
define ("invoice_CREDITED", 2);
define ("invoice_DRAFT", 3);
define ("invoice_PENDING", 4);
// Invoice Line Item Types
define ("line_item_MILESTONE", 1);
define ("line_item_FREEFORM", 2);
define ("line_item_WRITEOFF", 3);

// Milestone Types
define ("milestone_INCOMING_PAYMENT", 1);
define ("milestone_AGENT_COMMISSION", 2);
define ("milestone_B2S_PAYMENT", 3);
// Milestone States
define ("milestone_FUTURE", 1);
define ("milestone_DUE", 2);
define ("milestone_INVOICED", 3);
define ("milestone_CLOSED", 4);

// Production management entries states
define("prod_state_DRAFT", 1);
define("prod_state_OPEN", 2);
define("prod_state_CLOSED", 3);
// Shift direction for production management entries
define("SHIFT_DOWN", 1);
define("SHIFT_UP", -1);
// Summing scope for production management entries
define("PROCESS_START", 1);
define("PROCESS_END", 2);
// Schedule View for production management screen
define("schedule_DATES", 1);
define("schedule_DAYS", 2);
// Invalid Parent ID for production management entries
define("PROD_INVALID_PARENT_ID", -2);

/****** Utility constants ******/
// Number of decimal places for money amounts
define("NUM_DEC_PLACES", 2);

/*
	Generate a code for the new instance for the given entity name

	Call DB to get the prefix and the last ID generated for the given entity name.
	Increment ID by 1.
	Update DB table.
	Return formatted generated ID.
	Notes:
		- Current possible entity names: BUYER, SUPPLIER, AGENT, GEN_ORDER, SINGLE_ORDER, INVOICE, PRODUCT,
									     EXT_INVOICE, External (for EXT_PARTY)
		- Current length of the digits part of the code is 5 digits.
*/
function get_new_code($entity_name)
{
	$prefix_fld  = $entity_name ."_PREFIX";
	$last_id_fld = $entity_name."_LAST_ID";

	$select_sql	 = "SELECT " .$prefix_fld. ", " .$last_id_fld. " FROM globals";
	$result  	 = mysql_query($select_sql) or die(mysql_error());
	$row     	 = mysql_fetch_array($result);

	$prefix  = $row["$prefix_fld"];
	$last_id = $row["$last_id_fld"];

	$new_id   = $last_id + 1;
	$new_code = $prefix . str_pad($new_id, 5, "0", STR_PAD_LEFT);

	// Update DB table with last generated ID
	$update_sql	 = "UPDATE globals  SET " . $last_id_fld . " = ".$new_id ;
	$result  	 = mysql_query($update_sql) or die(mysql_error());
	return $new_code;
}
/*
	Converts date from D/M/Y format to Y-M-D format
*/
function format_date($date)
{
	if (!$date) return "";
	//Check to see if it's already in the Y-M-D format
	$year  = strtok($date, "-");
	$month = strtok("-");
	$day   = strtok("- ");
	if ($year && $month && $day)  {
	 	return $date;
	}
	$day   = strtok($date, "/");
	$month = strtok("/");
	$year  = strtok("/");
	$new_date = $year . "-" . $month . "-" . $day;
	return $new_date;
}

/*
	Converts date from Y-M-D to D/M/Y
*/
function format_date_reverse($date)
{
	if (!$date) return "";
	//Check to see if it's already in the D/M/Y format
	$day  = strtok($date, "/");
	$month = strtok("/");
	$year   = strtok("/ ");
	if ($year && $month && $day)  {
	 	return $date;
	}
	if ("0000-00-00" == $date) return "";
	$year  = strtok($date, "-");
	$month = strtok("-");
	$day   = strtok("- ");
	$new_date = $day . "/" . $month . "/" . $year;
	return $new_date;
}

/*
	Calculates difference in days between two dates
	If one of the dates is NULL or invalid return nothing
	Returns a positive or negative number depending on values of date1 and date2
*/
function days_diff($date1, $date2, $show_sign = true)
{
	if (!empty($date1) && !empty($date2)) {
		if ( ('0000-00-00' != $date1) && ('0000-00-00' != $date2) ) {;
			$diff = round((strtotime($date1) - strtotime($date2)) / 86400); // 86400=60x60x24=one day
			return ($show_sign ? sign($diff).abs($diff) : abs($diff));
		}
	}
}
/*
	Sign it
*/
function sign($number)
{
	return ($number < 0 ? '(-)' : ($number > 0 ? '(+)' : ''));
}
/*
	Format file size and add the appropriate suffix.
	Assume file size passed is in bytes.
	If size is less than 1024 don't divide else, keep dividing until
	we're in the right range. Access suffix array through loop counter.
*/
function format_file_size($filesize_in_bytes, $precision = 0)
{
  $arr_suffix = array(" Bytes", " Kb", " MB");
  $value 	  = round($filesize_in_bytes, $precision);
  $i 		  = 0;
  while ($value > 1024) {
     $value /= 1024;
     $i++;
  }
  $formatted_size = round($value, $precision) . $arr_suffix[$i];
  return $formatted_size;
}

/*
	Capitalise first letter of a given string
*/
function capitalise_first_letter($str)
{
	return strtoupper(substr($str, 0, 1)) . strtolower(substr($str, 1));
}

/*
	Returns true if valid email, false if not
*/
function validate_email($str)
{
	//returns true if valid email, false if not
	return preg_match("/^.+@.+\\..+$/", $str);
}

/*
	Returns true if valid URL, false if not
	Notes: Not implemented yet
*/
function validate_URL($str)
{
	return true;
}

/*
	Set entity type string
*/
function set_str_entity_type($entity_type_id)
{
	switch ($entity_type_id) {
		case entity_BUYER:
		$str_entity_type = "Buyer";
		break;
		case entity_SUPPLIER:
		$str_entity_type = "Supplier";
		break;
		case entity_AGENT:
		$str_entity_type = "Agent";
		break;
		case entity_EXTERNAL:
		$str_entity_type = "External";
		break;
		case entity_INTERNAL:
		$str_entity_type = "Internal";
		break;
		case entity_GEN_ORDER:
		$str_entity_type = "General Order";
		break;
		case entity_SINGLE_ORDER:
		$str_entity_type = "Single Order";
		break;
		case entity_PROD_SUMMARY:
		$str_entity_type = "Production Summary";
		break;
		case entity_PROD_PHASE:
		$str_entity_type = "Production Phase";
		break;
		case entity_PROD_STEP:
		$str_entity_type = "Production Step";
		break;

	}
	return $str_entity_type;
}
/*
	Function: populate_country_dropdown ( )
	Desc:
		Get country list from DB and populate drop down accordingly
		If no country is selected the drop down will default to value=0
 	Parms:
    	$selected_country_id - Country ID of the currently selected item (to be used when in Edit Mode)
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .
*/
function populate_country_dropdown($selected_country_id)
{
	$sql = "SELECT COUNTRY_ID, NAME FROM country ORDER BY NAME";
	$result = mysql_query($sql) or die(mysql_error());

	$options = "<option value=\"0\">Select...</option>\r\n";
	while ($row = mysql_fetch_array($result)) {
		$options .= "<option value=\"" . $row['COUNTRY_ID'] . "\"";
		if ($row['COUNTRY_ID'] == $selected_country_id) {
			$options .= " selected=\"selected\"";
		}
		$options .= ">" . $row['NAME'] . "</option>\r\n";
	}
	mysql_free_result($result);
	return $options;
}

/*
	Function: populate_employer_types_dropdown ( )
	Desc:
		Get list of available employers types from DB and populate drop down accordingly
 	Parms:
		None
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select>
	Notes:
		The field EMPLOYER_TYPE in the table "groupe" is an enum. It has to be the same order
		of enums the TYPE field in the "party" table for the employer type and employers
		dropdown to work in harmony. Like Ebony and Ivory.
		$show_agent determines whether the Agent type is to be shown or not
*/
function populate_employer_types_dropdown($selected_employer_type_id, $show_agent = false)
{

	$sql = "SELECT DISTINCT employer_type+0 AS EMPLOYER_TYPE_ID, employer_type AS EMPLOYER_TYPE
			FROM groupe ";
	$sql .= ($show_agent ? " " : " WHERE groupe.employer_type <> ". entity_AGENT);
	$sql .= " ORDER BY EMPLOYER_TYPE";
	$result = mysql_query($sql) or die(mysql_error());

	$options = "<option value=\"0\">Select Employer Type</option>\r\n";
	while ($row = mysql_fetch_array($result)) {
		$options .= "<option value=\"" . $row['EMPLOYER_TYPE_ID'] . "\"";
		if ($row['EMPLOYER_TYPE_ID'] == $selected_employer_type_id) {
			$options .= " selected=\"selected\"";
		}
		$options .= ">" . capitalise_first_letter($row['EMPLOYER_TYPE']) . "</option>\r\n";
	}
	mysql_free_result($result);
	return $options;
}
/*
	Function: populate_employers_dropdown ( )
	Desc:
		Get list of available employers based on passed employer type from DB and
		populate drop down accordingly
 	Parms:
		$employer_type_id - Type of employers to be fetched
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select>
*/

function populate_employers_dropdown($selected_employer_id, $employer_type_id)
{
	$sql = "SELECT DISTINCT PARTY_ID, TYPE+0  AS TYPE, NAME FROM party
					WHERE (EMPLOYER_ID = 0) ";
	// If employer_type_id is zero then get all employers of all types
	if ($employer_type_id != 0 ) {
		$sql .= "AND (TYPE = ". $employer_type_id. " ) " ;
	}
	$sql .= " ORDER BY TYPE, NAME";
	$result = mysql_query($sql) or die(mysql_error());

	$options = "<option value=\"0\">Select Employer</option>\r\n";
	while ($row = mysql_fetch_array($result)) {
		$options .= "<option value=\"" . $row['PARTY_ID'] . ",".$row['TYPE']."\"";
		if ($row['PARTY_ID'] == $selected_employer_id) {
			$options .= " selected=\"selected\"";
		}
		$options .= ">" .$row['NAME'] . "</option>\r\n";
	}
	mysql_free_result($result);
	return $options;
}

/*
	Function: populate_party_dropdown ( )
	Desc:
		Get list of available parties  from DB and populate drop down accordingly
		If no party is selected the drop down will default to value=0
 	Parms:
    	$selected_party_id - Party ID of the currently selected item (to be used when in Edit Mode)
		$party_type_id - Type of parties to be fetched (entity_AGENT, entity_BUYER, entity_SUPPLIER, entity_EXTERNAL)
		$employer_id - ID of the employer in case we need to get all the contacts for a particular employer
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .
*/
function populate_party_dropdown($selected_party_id, $party_type_id, $employer_id = 0)
{

	$sql = "SELECT PARTY_ID, NAME FROM party
			WHERE TYPE=".$party_type_id." AND EMPLOYER_ID = ".$employer_id.
				" AND STATE = 'ACTIVE'
			ORDER BY NAME";
	$result = mysql_query($sql) or die(mysql_error());

	$options = "<option value=\"0\">Select...</option>\r\n";
	while ($row = mysql_fetch_array($result)) {
		$options .= "<option value=\"" . $row['PARTY_ID'] . "\"";
		if ($row['PARTY_ID'] == $selected_party_id) {
			$options .= " selected=\"selected\"";
		}
		$options .= ">" . $row['NAME'] . "</option>\r\n";
	}
	mysql_free_result($result);
	return $options;
}
/*
	Function: populate_payor_dropdown ( )
	Desc:
		Get list of available buyers and suppliers from DB and populate drop down accordingly
		If no party is selected the drop down will default to value=0
 	Parms:
    	$selected_payor_id - Party ID of the currently selected item (to be used when in Edit Mode)
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .
*/
function populate_payor_dropdown($selected_payor_id)
{
	$sql = "SELECT PARTY_ID, NAME, TYPE FROM party WHERE (TYPE=".entity_BUYER." OR TYPE=".entity_SUPPLIER.")
			AND (EMPLOYER_ID = 0)  AND STATE = 'ACTIVE'
			ORDER BY TYPE, NAME";
	$result = mysql_query($sql) or die(mysql_error());

	$options = "<option value=\"0\">Select...</option>\r\n";
	while ($row = mysql_fetch_array($result)) {
		$options .= "<option value=\"" . $row['PARTY_ID'] . "\"";
		if ($row['PARTY_ID'] == $selected_payor_id) {
			$options .= " selected=\"selected\"";
		}
		$options .= ">" .$row['TYPE']."::".$row['NAME'] . "</option>\r\n";
	}
	mysql_free_result($result);
	return $options;
}

/*
	Function: populate_payor_contact_dropdown ( )
	Desc:
		Get list of available buyer or supplier contacts from DB and populate drop down accordingly
		If no party is selected the drop down will default to value=0
 	Parms:
    	$selected_payor_contact_id - Party ID of the selected payor contact
		$selected_payor_id - Party ID of the currently selected payor(employer)
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .
*/
function populate_payor_contact_dropdown($selected_payor_contact_id, $selected_payor_id)
{
	$sql = "SELECT PARTY_ID, NAME FROM party WHERE EMPLOYER_ID = ".$selected_payor_id ."
			 AND STATE = 'ACTIVE' ORDER BY NAME";
	$result = mysql_query($sql) or die(mysql_error());

	$options = "<option value=\"0\">Select...</option>\r\n";
	while ($row = mysql_fetch_array($result)) {
		$options .= "<option value=\"" . $row['PARTY_ID'] . "\"";
		if ($row['PARTY_ID'] == $selected_payor_contact_id) {
			$options .= " selected=\"selected\"";
		}
		$options .= ">" .$row['NAME'] . "</option>\r\n";
	}
	mysql_free_result($result);
	return $options;
}

/*
	Function: populate_add_cost_payor_dropdown ( )
	Desc:
		Populate Payor drop down box in Additional Cost popup
		Populate it with the following entries:
			- Buyer of the GO
			- Agent of the GO
			- Supplier (or all suppliers in case of General Order)
			- Internal
			- All External Parties

 	Parms:
    	$selected_payor_id - Party ID of the currently selected item (to be used when in Edit Mode)
		$order_id - Single Order ID or General Order ID
		$order_type - General Order or Single Order
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .
*/
function populate_add_cost_payor_dropdown($selected_payor_id, $order_id, $order_type)
{
	if ($order_type == entity_GEN_ORDER) {
		$buyer_row 	   = get_gen_order_buyer_id_and_name($order_id);
		$agent_row     = get_gen_order_agent_id_and_name($order_id);
		$sql_suppliers = generate_select_gen_order_suppliers_sql(" WHERE gen_order.GEN_ORDER_ID = " .$order_id );
	}
	else {
		$buyer_row 	   = get_sin_order_buyer_id_and_name($order_id);
		$agent_row     = get_sin_order_agent_id_and_name($order_id);
	}
	$options  = "<option value=\"0\">Select...</option>\r\n";
	// Get Internal
	$sql_organization  = "SELECT DISTINCT
					 party.PARTY_ID AS ORG_PARTY_ID, party.NAME AS ORG_NAME
				  	 FROM party
				  	 WHERE party.PARTY_ID = ".COMPANY_PARTY_ID;
	$result   = mysql_query($sql_organization) or die(mysql_error());
	$org_row = mysql_fetch_array($result);
	$options .= "<option value=\"" . $org_row['ORG_PARTY_ID'] . "\"";
	if ($org_row['ORG_PARTY_ID'] == $selected_payor_id) {
			$options .= " selected=\"selected\"";
		}
	$options .= ">".$org_row['ORG_NAME'] . "</option>\r\n";

	// Get Buyer
	$options .= "<option value=\"" . $buyer_row['BUYER_ID'] . "\"";
	if ($buyer_row['BUYER_ID'] == $selected_payor_id) {
			$options .= " selected=\"selected\"";
		}
	$options .= ">Buyer::".$buyer_row['BUYER_NAME'] . "</option>\r\n";

	// Get Agent
	$options .= "<option value=\"" . $agent_row['AGENT_ID'] . "\"";
	if ($agent_row['AGENT_ID'] == $selected_payor_id) {
			$options .= " selected=\"selected\"";
		}
	$options .= ">Agent::".$agent_row['AGENT_NAME'] . "</option>\r\n";

	// Get Suppliers
	if ($order_type == entity_GEN_ORDER) {
		$result   	= mysql_query($sql_suppliers) or die(mysql_error());
		while ($row = mysql_fetch_array($result)) {
			$options .= "<option value=\"" . $row['SUPPLIER_ID'] . "\"";
			if ($row['SUPPLIER_ID'] == $selected_payor_id) {
				$options .= " selected=\"selected\"";
			}
			$options .= ">Supplier::".$row['SUPPLIER_NAME'] . "</option>\r\n";
		}
	}
	else {
		$supplier_row = get_sin_order_supplier_id_and_name($order_id);
		$options .= "<option value=\"" . $supplier_row['SUPPLIER_ID'] . "\"";
		if ($supplier_row['SUPPLIER_ID'] == $selected_payor_id) {
			$options .= " selected=\"selected\"";
		}
	$options .= ">Supplier::".$supplier_row['SUPPLIER_NAME'] . "</option>\r\n";
	}

	// Get External Parties
	$sql_externals = "SELECT DISTINCT
					  party.PARTY_ID AS EXT_PARTY_ID, party.NAME AS EXT_NAME
				      FROM party
				      WHERE party.TYPE = ".entity_EXTERNAL."  AND party.EMPLOYER_ID = 0";
	$result = mysql_query($sql_externals) or die(mysql_error());
	while ($row = mysql_fetch_array($result)) {
		$options .= "<option value=\"" . $row['EXT_PARTY_ID'] . "\"";
		if ($row['EXT_PARTY_ID'] == $selected_payor_id) {
			$options .= " selected=\"selected\"";
		}
		$options .= ">External::".$row['EXT_NAME'] . "</option>\r\n";
	}
	mysql_free_result($result);
	return $options;
}

/*
	Function: populate_add_cost_payee_dropdown ( )
	Desc:
		Populate Payee drop down box in Additional Cost popup
		Populate it with the following entries:
			- Agent
			- Supplier (or all suppliers in case of General Order)
			- Internal
			- All External Parties

 	Parms:
    	$selected_payee_id - Party ID of the currently selected item (to be used when in Edit Mode)
		$order_id - Single Order ID or General Order ID
		$order_type -  General Order or Single Order
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .
*/
function populate_add_cost_payee_dropdown($selected_payee_id, $order_id, $order_type)
{
	if ($order_type == entity_GEN_ORDER) {
		$sql_suppliers = generate_select_gen_order_suppliers_sql(" WHERE gen_order.GEN_ORDER_ID = " .$order_id );
		$buyer_row 	   = get_gen_order_buyer_id_and_name($order_id);
		$agent_row     = get_gen_order_agent_id_and_name($order_id);
	}
	else {
		$buyer_row 	   = get_sin_order_buyer_id_and_name($order_id);
		$agent_row     = get_sin_order_agent_id_and_name($order_id);
	}
	$options  = "<option value=\"0\">Select...</option>\r\n";
	// Get Internal
	$sql_organization  = "SELECT DISTINCT
					 party.PARTY_ID AS ORG_PARTY_ID, party.NAME AS ORG_NAME
				  	 FROM party
				  	 WHERE party.PARTY_ID = ".COMPANY_PARTY_ID;
	$result   = mysql_query($sql_organization) or die(mysql_error());
	$org_row = mysql_fetch_array($result);
	$options .= "<option value=\"" . $org_row['ORG_PARTY_ID'] . "\"";
	if ($org_row['ORG_PARTY_ID'] == $selected_payee_id) {
			$options .= " selected=\"selected\"";
		}
	$options .= ">".$org_row['ORG_NAME'] . "</option>\r\n";

	// Get Buyer
	$options .= "<option value=\"" . $buyer_row['BUYER_ID'] . "\"";
	if ($buyer_row['BUYER_ID'] == $selected_payee_id) {
			$options .= " selected=\"selected\"";
		}
	$options .= ">Buyer::".$buyer_row['BUYER_NAME'] . "</option>\r\n";

	// Get Agent
	$options .= "<option value=\"" . $agent_row['AGENT_ID'] . "\"";
	if ($agent_row['AGENT_ID'] == $selected_payee_id) {
			$options .= " selected=\"selected\"";
		}
	$options .= ">Agent::".$agent_row['AGENT_NAME'] . "</option>\r\n";

	// Get Suppliers
	if ($order_type == entity_GEN_ORDER) {
		$result   	= mysql_query($sql_suppliers) or die(mysql_error());
		while ($row = mysql_fetch_array($result)) {
			$options .= "<option value=\"" . $row['SUPPLIER_ID'] . "\"";
			if ($row['SUPPLIER_ID'] == $selected_payee_id) {
				$options .= " selected=\"selected\"";
			}
			$options .= ">Supplier::".$row['SUPPLIER_NAME'] . "</option>\r\n";
		}
	}
	else {
		$supplier_row = get_sin_order_supplier_id_and_name($order_id);
		$options .= "<option value=\"" . $supplier_row['SUPPLIER_ID'] . "\"";
		if ($supplier_row['SUPPLIER_ID'] == $selected_payee_id) {
			$options .= " selected=\"selected\"";
		}
	$options .= ">Supplier::".$supplier_row['SUPPLIER_NAME'] . "</option>\r\n";
	}

	// Get External Parties
	$sql_externals = "SELECT DISTINCT
					party.PARTY_ID AS EXT_PARTY_ID, party.TYPE AS EXT_TYPE, party.NAME AS EXT_NAME
				  FROM party
				  WHERE party.TYPE = ".entity_EXTERNAL."  AND party.EMPLOYER_ID = 0";
	$result = mysql_query($sql_externals) or die(mysql_error());
	while ($row = mysql_fetch_array($result)) {
		$options .= "<option value=\"" . $row['EXT_PARTY_ID'] . "\"";
		if ($row['EXT_PARTY_ID'] == $selected_payee_id) {
			$options .= " selected=\"selected\"";
		}
		$options .= ">" .$row['EXT_TYPE']."::".$row['EXT_NAME'] . "</option>\r\n";
	}
	mysql_free_result($result);
	return $options;
}
/*
	Desc:
		Get buyer and supplier of single order to populate dropdown in SO financials
 	Parms:
    	$selected_party_id - Party ID of the selected commission payor
		$single_order_id - Single Order ID for which we're getting the commission payors
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .

*/
function populate_sin_order_commission_payors_dropdown($selected_party_id, $single_order_id)
{
	$sql = "SELECT
				buyer.PARTY_ID AS BUYER_ID, buyer.NAME AS BUYER_NAME, buyer.TYPE+0 AS BUYER_TYPE,
				supplier.PARTY_ID AS SUPPLIER_ID, supplier.NAME AS SUPPLIER_NAME,
				supplier.TYPE+0 AS SUPPLIER_TYPE
			FROM single_order
				INNER JOIN party AS supplier ON single_order.SUPPLIER_ID = supplier.PARTY_ID
				INNER JOIN gen_order ON gen_order.GEN_ORDER_ID = single_order.GEN_ORDER_ID
				INNER JOIN party AS buyer ON gen_order.BUYER_ID = buyer.PARTY_ID
			WHERE single_order.SINGLE_ORDER_ID = " . $single_order_id;
	$result = mysql_query($sql) or die(mysql_error());
	$row = mysql_fetch_array($result);
	// Buyer
	$options  = "<option value=\"" . $row['BUYER_ID'] .",".$row['BUYER_TYPE']. "\"";
	if ($row['BUYER_ID'] == $selected_party_id) {
		$options .= " selected=\"selected\"";
	}
	$options .= ">Buyer::" .$row['BUYER_NAME'] . "</option>\r\n";
	// Supplier
	$options .= "<option value=\"" . $row['SUPPLIER_ID'] .",".$row['SUPPLIER_TYPE']. "\"";
	if ($row['SUPPLIER_ID'] == $selected_party_id) {
		$options .= " selected=\"selected\"";
	}
	$options .= ">Supplier::" .$row['SUPPLIER_NAME'] . "</option>\r\n";

	mysql_free_result($result);
	return $options;

}

/*
	Function: populate_prod_phase_dropdown ( )
	Desc:
		Get list of available production phases for a given single order.
		Does not show "At the beginning" option if entity_type is entiry_PROD_STEP
 	Parms:
    	$single_order_id - Single Order ID we're fetching the phases for
		$selected_prod_phase_id - The currently selected phase ID (if any)
		$entity_type - entity_PROD_STEP or entity_PROD_PHASE
		$entity_id - PRODUCTION_STEP_ID of phase being edited
		$state - state_NEW or state_EDIT to decide whether to show all phases or all phases
				 except the one being edited
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .
*/
function populate_prod_phase_dropdown($single_order_id, $selected_prod_phase_id, $entity_type, $entity_id, $state)
{
	$options = '';
	$sql = "SELECT PRODUCTION_STEP_ID, NAME FROM production_step WHERE SINGLE_ORDER_ID = ".$single_order_id ."
			 AND PARENT_ID = 0 ";
	if($entity_type == entity_PROD_PHASE && $state == state_EDIT) {
		$sql .= " AND PRODUCTION_STEP_ID <> " . $entity_id ;
	}
	$sql .= " ORDER BY SEQ_ID";
	$result = mysql_query($sql) or die(mysql_error());

	if ($entity_type == entity_PROD_PHASE) {
		$options .= "<option value=\"0\">At The Beginning...</option>\r\n";
	}
	while ($row = mysql_fetch_array($result)) {
		$options .= "<option value=\"" . $row['PRODUCTION_STEP_ID'] . "\"";
		if ($row['PRODUCTION_STEP_ID'] == $selected_prod_phase_id) {
			$options .= " selected=\"selected\"";
		}
		$options .= ">" .$row['NAME'] . "</option>\r\n";
	}
	mysql_free_result($result);
	return $options;
}
/*
	Function: populate_prod_step_dropdown ( )
	Desc:
		Get list of available production steps  for a given single order and phase ID
 	Parms:
    	$single_order_id - Single Order ID we're fetching the steps for
		$selected_prod_step_id - The curretnly selected step ID (if any)
		$containing_phase_id - The phase ID containing the steps
		$entity_id - PRODUCTION_STEP_ID of step being edited
		$state - state_NEW or state_EDIT to decide whether to show all steps or all steps
				 except the one being viewed
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .
*/

function populate_prod_step_dropdown($single_order_id, $selected_prod_step_id, $containing_phase_id, $entity_id, $state)
{
	$sql = "SELECT PRODUCTION_STEP_ID, NAME FROM production_step WHERE SINGLE_ORDER_ID = ".$single_order_id ."
			 AND PARENT_ID = ".$containing_phase_id;
	if($state == state_EDIT) {
		$sql .= " AND PRODUCTION_STEP_ID <> " . $entity_id ;
	}
	$sql .= " ORDER BY SEQ_ID";
	$result = mysql_query($sql) or die(mysql_error());

	$options = "<option value=\"0\">At The Beginning...</option>\r\n";
	while ($row = mysql_fetch_array($result)) {
		$options .= "<option value=\"" . $row['PRODUCTION_STEP_ID'] . "\"";
		if ($row['PRODUCTION_STEP_ID'] == $selected_prod_step_id) {
			$options .= " selected=\"selected\"";
		}
		$options .= ">" .$row['NAME'] . "</option>\r\n";
	}
	mysql_free_result($result);
	return $options;
}
/*
	Function: populate_gen_order_dropdown ( )
	Desc:
		Get list of available general orders from DB and populate drop down accordingly
		If no general order is selected the drop down will default to value=0
		Buyers are displayed next to General order ID for reference
 	Parms:
    	$selected_gen_order_id - General order ID for the currently selected general order (to be used when in Edit Mode)
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .
*/
function populate_gen_order_dropdown($selected_gen_order_id)
{

	$sql = "SELECT gen_order.GEN_ORDER_ID, gen_order.FRIENDLY_NAME, gen_order.CODE,
				   gen_order.CLIENT_ORDER_ID, party.NAME
			FROM gen_order LEFT JOIN party ON gen_order.BUYER_ID = party.PARTY_ID
			WHERE gen_order.STATE='ACTIVE'  AND party.STATE='ACTIVE'
			ORDER BY gen_order.FRIENDLY_NAME";
	$result = mysql_query($sql) or die(mysql_error());

	$options = "<option value=\"0\">Select...</option>\r\n";
	while ($row = mysql_fetch_array($result)) {
		$options .= "<option value=\"" . $row['GEN_ORDER_ID'] . "\"";
		if ($row['GEN_ORDER_ID'] == $selected_gen_order_id) {
			$options .= " selected=\"selected\"";
		}
		$options .= ">" . $row['FRIENDLY_NAME']."(". $row['CODE'] ."/". $row['CLIENT_ORDER_ID'].")--Buyer:".
						$row['NAME']."</option>\r\n";
	}
	mysql_free_result($result);
	return $options;
}

/*
	Function: populate_product_type_dropdown ( )
	Desc:
		Get product type list from DB and populate drop down accordingly
		If no product type is selected the drop down will default to value=0
 	Parms:
    	$selected_product_type_id - Product type ID of the currently selected item (to be used when in Edit Mode)
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .
*/
function populate_product_type_dropdown($selected_product_type_id)
{
	$sql = "SELECT PRODUCT_TYPE_ID, NAME FROM product_type ORDER BY NAME";
	$result = mysql_query($sql) or die(mysql_error());

	$options = "<option value=\"0\">Select...</option>\r\n";
	while ($row = mysql_fetch_array($result)) {
		$options .= "<option value=\"" . $row['PRODUCT_TYPE_ID'] . "\"";
		if ($row['PRODUCT_TYPE_ID'] == $selected_product_type_id) {
			$options .= " selected=\"selected\"";
		}
		$options .= ">" . $row['NAME'] . "</option>\r\n";
	}
	mysql_free_result($result);
	return $options;
}

/*
	Function: populate_unit_dropdown ( )
	Desc:
		Get units list from DB and populate drop down accordingly
		If no unit type is selected the drop down will default to value=0
 	Parms:
    	$selected_unit_id - Unit ID of the currently selected item (to be used when in Edit Mode)
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .
*/
function populate_unit_dropdown($selected_unit_id)
{
	$sql = "SELECT UNIT_ID, NAME FROM unit ORDER BY NAME";
	$result = mysql_query($sql) or die(mysql_error());

	$options = "<option value=\"0\">Select...</option>\r\n";
	while ($row = mysql_fetch_array($result)) {
		$options .= "<option value=\"" . $row['UNIT_ID'] . "\"";
		if ($row['UNIT_ID'] == $selected_unit_id) {
			$options .= " selected=\"selected\"";
		}
		$options .= ">" . $row['NAME'] . "</option>\r\n";
	}
	mysql_free_result($result);
	return $options;
}


/*
	Function: populate_order_status_dropdown ( )
	Desc:
		Populate the options part of the order status drop down.
 	Parms:
    	$str_order_status - Order Status as a string. Possible values are OPEN and CLOSED
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .
*/
function populate_order_status_dropdown($order_status)
{
	$options  = '<option value="1"'.((strcasecmp('Closed', $order_status) == 0) ? 'selected="selected"' : '' ).'>Closed</option>';
	$options .= '<option value="2"'.((strcasecmp('Open', $order_status) == 0) ? 'selected="selected"' : '').'>Open</option>';
	return $options;
}

/*
	Function: populate_milestones_status_dropdown ( )
	Desc:
		Populate the options part of the milesteones status drop down.
 	Parms:
		$milestone_status
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .
*/
function populate_milestones_status_dropdown($milestone_status)
{
	$options = '<option value="'.milestone_FUTURE .'" '.($milestone_status == milestone_FUTURE ? 'selected="selected"': '').'>Future</option>
				<option value="'.milestone_DUE .'" '.($milestone_status == milestone_DUE ? 'selected="selected"': '').'>Due</option>
				<option value="'.milestone_INVOICED .'" '.($milestone_status == milestone_INVOICED ? 'selected="selected"': '').'>Invoiced</option>
				<option value="'.milestone_CLOSED .'" '.($milestone_status == milestone_CLOSED ? 'selected="selected"': '').'>Closed</option>';
	return $options;
}

/*
	Function: populate_milestone_type_dropdown ( )
	Desc:
		Populate the options part of the milesteones status drop down.
 	Parms:
		$milestone_type
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .
*/
function populate_milestone_type_dropdown($milestone_type)
{
	$options = '<option value="0">Select...</option>
				<option value="'.milestone_INCOMING_PAYMENT .'" '.($milestone_type == milestone_INCOMING_PAYMENT ? 'selected="selected"': '').'>Incoming Payment</option>
				<option value="'.milestone_AGENT_COMMISSION .'" '.($milestone_type == milestone_AGENT_COMMISSION ? 'selected="selected"': '').'>Agent Commission Payment</option>
				<option value="'.milestone_B2S_PAYMENT .'" '.($milestone_type == milestone_B2S_PAYMENT ? 'selected="selected"': '').'>B2S Payment</option>';
	return $options;
}

/*
	Function: populate_line_item_type_dropdown ( )
	Desc:
		Populate the options part of line items type drop down
 	Parms:
    	$line_item_type - Enum value of line item type (see invoice_line_item table definition in DB)
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .
*/
function populate_line_item_type_dropdown($line_item_type)
{
	$options = '<option value="1"'.($line_item_type == 1 ? 'selected="selected"' : '').'>Milestone</option>';
	$options .='<option value="2"'.($line_item_type == 2 ? 'selected="selected"' : '' ).'>Free-Form</option>';
	//$options .='<option value="3"'.($line_item_type == 3 ? 'selected="selected"' : '' ).'>Write-Off</option>';
	return $options;
}

/*
	Function: populate_invoice_status_dropdown ( )
	Desc:
		Populate the options part of the invoice status drop down.
 	Parms:
		$invoice_status
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .
*/
function populate_invoice_status_dropdown($invoice_status)
{
	$options = '<option value="0">All</option>
				<option value="'.invoice_CLOSED .'" '.($invoice_status == invoice_CLOSED ? 'selected="selected"': '').'>Closed</option>
				<option value="'.invoice_CREDITED .'" '.($invoice_status == invoice_CREDITED ? 'selected="selected"': '').'>Credited</option>
				<option value="'.invoice_DRAFT .'" '.($invoice_status == invoice_DRAFT ? 'selected="selected"': '').'>Draft</option>
				<option value="'.invoice_PENDING .'" '.($invoice_status == invoice_PENDING ? 'selected="selected"': '').'>Pending</option>';
	return $options;
}

/*
	Function: populate_schedule_view_dropdown ( )
	Desc:
		Populate the options part of the schedule view drop down.
 	Parms:
    	$selected_view - Schedule View as constant (schedule_DATES or schedule_DAYS)
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .
*/
function populate_schedule_view_dropdown($selected_view)
{
	$options = '<option value="'.schedule_DATES.'"'.(($selected_view == schedule_DATES ) ? 'selected="selected"' : '').'>Dates</option>';
	$options .='<option value="'.schedule_DAYS.'"'.(($selected_view == schedule_DAYS) ? 'selected="selected"' : '' ).'>Days</option>';
	return $options;
}

/*
	Function: populate_prod_entry_status_dropdown ( )
	Desc:
		Populate the options part of the product entry status dropdown
 	Parms:
    	$prod_entry_status - Status  of production entry
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .
*/
function populate_prod_entry_status_dropdown($prod_entry_status)
{
	$options = '<option value="'.prod_state_DRAFT.'"'.(($prod_entry_status == prod_state_DRAFT ) ? 'selected="selected"' : '').'>Draft</option>';
	$options .='<option value="'.prod_state_OPEN.'"'.(($prod_entry_status == prod_state_OPEN) ? 'selected="selected"' : '' ).'>Open</option>';
	$options .='<option value="'.prod_state_CLOSED.'"'.(($prod_entry_status == prod_state_CLOSED) ? 'selected="selected"' : '' ).'>Closed</option>';
	return $options;

}

/*
	Function: populate_currency_dropdown ( )
	Desc:
		Populate the options part of the currency drop down.
 	Parms:
    	$selected_currency_id - Currency ID of the currently selected item (to be used when in Edit Mode)
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .
*/
function populate_currency_dropdown($selected_currency_id)
{
	$sql = "SELECT CURRENCY_ID, CODE, NAME FROM currency ORDER BY CODE";
	$result = mysql_query($sql) or die(mysql_error());

	$options = "<option value=\"0\">Select...</option>\r\n";
	while ($row = mysql_fetch_array($result)) {
		$options .= "<option value=\"" . $row['CURRENCY_ID'] . "\"";
		if ($row['CURRENCY_ID'] == $selected_currency_id) {
			$options .= " selected=\"selected\"";
		}
		$options .= ">".$row['NAME']  ."(".$row['CODE'].")" . "</option>\r\n";
	}
	mysql_free_result($result);
	return $options;
}

/*
	Function: populate_sec_group_dropdown ( )
	Desc:
		Populate the options part of the security groups drop down.
 	Parms:
    	$selected_sec_group_id - Group ID of the currently selected item (to be used when in Edit Mode)
   	Returns:
		$options - An XHTML fragment containing the query results for use in the <select> .
*/
function populate_sec_group_dropdown($selected_sec_group_id)
{
	$sql = "SELECT gid AS GROUP_ID , name AS GROUP_NAME
			FROM groupe
			WHERE gid <> 7
			ORDER BY gid";
	$result = mysql_query($sql) or die(mysql_error());

	$options = "<option value=\"0\">Select...</option>\r\n";
	while ($row = mysql_fetch_array($result)) {
		$options .= "<option value=\"" . $row['GROUP_ID'] . "\"";
		if ($row['GROUP_ID'] == $selected_sec_group_id) {
			$options .= " selected=\"selected\"";
		}
		$options .= ">".$row['GROUP_NAME']. "</option>\r\n";
	}
	mysql_free_result($result);
	return $options;
}

/*
	Check for any errors passed to the page
*/
function check_error()
{
	global $error_msg;
	// Check for any errors passed to the page
	if ( (isset($_GET['error'])) && (defined($_GET['error'])) ) {
		$error_msg = translate_error_code(constant($_GET['error']));
	}
}

/******************************************
	XHTML Generation Functions
*******************************************/

/*
	Generate a textbox control to be used in a form. $max_length is optional.
*/
function generate_text_input ($class, $name, $value, $max_length=100)
{
	if (strcasecmp($class,"date_box") != 0 ) {
		$title  = 	'title="Max Length is '.$max_length.' characters"';
		$markup =  	"<input class=\"". $class ."\" id=\"". $name ."\" name=\"". $name. "\" type=\"text\" value= \"". $value.
					"\" maxlength=\"". $max_length ."\"".$title."/>\r\n" ;
	}
	else {
		$title  = 	' title="Date in DD/MM/YYYY Format"';
		$markup =  	"<input class=\"". $class ."\" id=\"". $name ."\" name=\"". $name. "\" type=\"text\" value= \"". $value.
					"\" maxlength=\"10\" ".$title." readonly=\"1\"/>\r\n" ;
		$markup .= generate_calendar_control($name);
	}
	return $markup ;
}

/*
	Generate a password control to be used in a form. $max_length is optional.
*/
function generate_pwd_input ($class, $name, $value, $max_length=16)
{
	$markup =  "<input class=\"". $class ."\" id=\"". $name ."\" name=\"". $name. "\" type=\"password\" value= \"". $value.
				"\" maxlength=\"". $max_length ."\" title=\"Max Length is \"".$max_length." characters\"/>\r\n" ;
	return $markup ;
}

/*
	Generate a textarea control to be used in a form.
*/
function generate_textarea_input ($name, $rows, $cols, $value)
{
	$markup =  "<textarea  class=\"formtextarea\" id=\"".$name."\" name=\"".$name."\" rows=\"".$rows."\" cols=\"".$cols ."\" wrap=\"hard\">".
				$value."</textarea>\r\n" ;
	return $markup ;
}
/*
	Generate the countries drop down control to be used in a form and default to
	$selected_country_id (if any)
*/
function generate_dd_country_input ($selected_country_id)
{
	$markup ="<select name=\"COUNTRY_ID\">" . populate_country_dropdown($selected_country_id) . "</select>\r\n" ;
	return $markup;
}

/*
	Generate the  agent drop down control to be used in a form and default to
	$selected_agent_id (if any)
*/
function generate_dd_agent_input ($selected_agent_id, $onchange_action='')
{
	$markup = "<select id=\"AGENT_ID\" name=\"AGENT_ID\"  onchange=\"".$onchange_action."\">" . populate_party_dropdown($selected_agent_id, entity_AGENT) . "</select>\r\n" ;
	return $markup;
}
/*
	Generate the buyer drop down control to be used in a form and default to
	$selected_buyer_id (if any)
*/
function generate_dd_buyer_input ($selected_buyer_id, $onchange_action='')
{
	$markup = "<select id=\"BUYER_ID\" name=\"BUYER_ID\" onchange=\"".$onchange_action."\">" . populate_party_dropdown($selected_buyer_id, entity_BUYER) . "</select>\r\n" ;
	return $markup;
}
/*
	Generate the supplier drop down control to be used in a form and default to
	$selected_supplier_id (if any)
*/
function generate_dd_supplier_input ($selected_supplier_id, $onchange_action='')
{
	$markup = "<select id=\"SUPPLIER_ID\" name=\"SUPPLIER_ID\" onchange=\"".$onchange_action."\">" . populate_party_dropdown($selected_supplier_id, entity_SUPPLIER) . "</select>\r\n" ;
	return $markup;
}
/*
	Generate the internal contacts drop down control to be used in a form and default to
	$selected_contact_id (if any)
*/
function generate_dd_org_contacts_input ($selected_contact_id)
{
	$markup = "<select id=\"PAYEE_CONTACT_ID\" name=\"PAYEE_CONTACT_ID\">" . populate_party_dropdown($selected_contact_id, entity_INTERNAL, COMPANY_PARTY_ID) . "</select>\r\n" ;
	return $markup;
}
/*
	Generate the payors drop down control to be used in a form and default to
	$selected_payor_id (if any)
*/
function generate_dd_payor_input($selected_payor_id)
{
	$markup = "<select id=\"PAYOR_ID\" name=\"PAYOR_ID\">" . populate_payor_dropdown($selected_payor_id) . "</select>\r\n" ;
	return $markup;
}

function generate_dd_payor_contacts_input($selected_payor_contact_id, $selected_payor_id)
{
	$markup = "<select id=\"PAYOR_CONTACT_ID\" name=\"PAYOR_CONTACT_ID\">" . populate_payor_contact_dropdown($selected_payor_contact_id, $selected_payor_id) . "</select>\r\n" ;
	return $markup;
}

function generate_dd_add_cost_payor_input($selected_payor_id, $order_id, $order_type)
{
	$markup = "<select id=\"PAYOR_ID\" name=\"PAYOR_ID\">" . populate_add_cost_payor_dropdown($selected_payor_id, $order_id, $order_type) . "</select>\r\n" ;
	return $markup;
}

function generate_dd_add_cost_payee_input($selected_payee_id, $order_id, $order_type)
{
	$markup = "<select id=\"PAYEE_ID\" name=\"PAYEE_ID\">" . populate_add_cost_payee_dropdown($selected_payee_id, $order_id, $order_type) . "</select>\r\n" ;
	return $markup;
}

function generate_dd_commission_payor_input($selected_party_id, $order_id)
{
	$markup = "<select id=\"COMMISSIONER\" name=\"COMMISSIONER\">" . populate_sin_order_commission_payors_dropdown($selected_party_id, $order_id) . "</select>\r\n" ;
	return $markup;
}


function generate_dd_gen_order_input($selected_gen_order_id)
{
	$markup = "<select id=\"GEN_ORDER_ID\" name=\"GEN_ORDER_ID\">" . populate_gen_order_dropdown($selected_gen_order_id, entity_GEN_ORDER) . "</select>\r\n" ;
	return $markup;
}

function generate_milestones_status_input ($name, $milestone_status)
{
	$markup = '<select id="'.$name.'" name="'.$name.'">'.populate_milestones_status_dropdown($milestone_status).'</select>'."\r\n";
	return $markup;
}

function generate_dd_order_status_input ($str_order_status)
{
	$markup = '<select id="STATUS" name="STATUS">'.populate_order_status_dropdown($str_order_status).'</select>'."\r\n";
	return $markup;
}

function generate_dd_prod_entry_status_input ($prod_entry_status)
{
	$markup = '<select id="STATUS" name="STATUS">'.populate_prod_entry_status_dropdown($prod_entry_status).'</select>'."\r\n";
	return $markup;
}


function generate_dd_schedule_view_input ($selected_view, $onchange_action='')
{
	$markup = '<select id="SCHEDULE_VIEW" name="SCHEDULE_VIEW"
				title="Change Production Dates View"
			    onchange="'.$onchange_action.'">'.populate_schedule_view_dropdown($selected_view).'</select>'."\r\n";
	return $markup;
}

function generate_dd_line_item_input ($line_item_type)
{
	$markup = '<select id="LINE_ITEM_TYPE" name="LINE_ITEM_TYPE">'.populate_line_item_type_dropdown($line_item_type).'</select>'."\r\n";
	return $markup;
}

function generate_dd_currency_input ($selected_currency_id, $onchange_action='')
{
	$markup = '<select id="CURRENCY_ID" name="CURRENCY_ID" onchange="'.$onchange_action.'">'.populate_currency_dropdown($selected_currency_id).'</select>'."\r\n";
	return $markup;
}

function generate_dd_prod_type_input ($selected_product_type_id, $onchange_action='')
{
	$markup = '<select id="PRODUCT_TYPE_ID" name="PRODUCT_TYPE_ID" onchange="'.$onchange_action.'">'.populate_product_type_dropdown($selected_product_type_id).'</select>'."\r\n";
	return $markup;
}

function generate_dd_prod_phase_input ($single_order_id, $selected_prod_phase_id, $entity_type, $entity_id, $state, $onchange_action='')
{
	$markup = '<select id="PHASE_DD" name="PHASE_DD" onchange="'.$onchange_action.'">'.populate_prod_phase_dropdown($single_order_id, $selected_prod_phase_id, $entity_type, $entity_id, $state).'</select>'."\r\n";
	return $markup;
}

function generate_dd_prod_step_input ($single_order_id, $selected_prod_step_id, $containing_phase_id, $entity_id, $state, $onchange_action='')
{
	$markup = '<select id="STEP_DD" name="STEP_DD" onchange="'.$onchange_action.'">'.populate_prod_step_dropdown($single_order_id, $selected_prod_step_id, $containing_phase_id, $entity_id, $state).'</select>'."\r\n";
	return $markup;
}


function generate_unit_input ($unit_name, $selected_unit_id, $onchange_action='')
{
	$markup = '<select id="'.$unit_name.'" name="'.$unit_name.'" onchange="'.$onchange_action.'">'.populate_unit_dropdown($selected_unit_id).'</select>'."\r\n";
	return $markup;
}

function generate_dd_sec_group_input ($selected_sec_group_id, $onchange_action='')
{
	$markup = '<select id="GROUP_ID" name="GROUP_ID" onchange="'.$onchange_action.'">'.populate_sec_group_dropdown($selected_sec_group_id).'</select>'."\r\n";
	return $markup;
}

function generate_button($str_type, $str_caption, $str_onclk_action)
{
	$markup = '<input class="contrack_button" type="'.$str_type.'" value="'.$str_caption.'" onClick="'.$str_onclk_action.'"/>';
	return $markup;
}

function generate_production_button($str_id, $str_type, $str_caption, $str_onclk_action)
{
	$markup = '<input class="contrack_button" id="'.$str_id.'" name="'.$str_id.'" type="'.$str_type.'" value="'.$str_caption.'" onClick="'.$str_onclk_action.'"/>';
	return $markup;
}

function generate_calendar_control($ctlName)
{
	$btnName = $ctlName.'btn';
	$markup  ='<img src="calendar/img.gif" id="'.$btnName .'" class="calendar_button" title="Date selector"
      			onmouseover="this.style.background=\'#996\';" onmouseout="this.style.background=\'\'" />';
	$markup .= '<script type="text/javascript">createCalendar(\''.$ctlName.'\',\''.$btnName.'\');</script>';
	return $markup;
}

/*
	Generate one tab
*/
function generate_tab($str_tab_caption, $str_title, $str_link, $active = false, $enabled = false)
{
	$tab_type = ($enabled  ? ($active ? "active_tab" : "inactive_tab") : ($active ? 'active_tab':'disabled_tab'));

	$href     = ($enabled ? "<a href=\"" . $str_link . "\">". $str_tab_caption."</a></td>" : $str_tab_caption);
	$tab 	  = "<td width=\"10%\" class=\"".$tab_type."\" title=\"" . $str_title . "\">". $href;
	return $tab;
}

/*
 Tab Generation functions
*/
function generate_agent_tabs($active_tab_ordinal, $party_id, $state = state_VIEW)
{
	$tabs  = generate_tab("Orders","View All Orders", "search_order.php?entity=".entity_GEN_ORDER."&referrer=".entity_AGENT."&referrer_id=".$party_id, $active_tab_ordinal == 1, $party_id > 0);
	$tabs .= generate_tab("Commission Milestones","View All Commissions", "party_milestones.php?referrer=".entity_AGENT."&referrer_id=".$party_id."&milestone_type=".milestone_AGENT_COMMISSION, $active_tab_ordinal==2, $party_id > 0);
	$tabs .= generate_tab("Profile","View & Edit Profile", "party.php?party_id=".$party_id . "&state=" .$state . "&type=" .entity_AGENT, $active_tab_ordinal==3, $party_id > 0);
	$tabs .= "<td class=\"blank_tab\">&nbsp;</td>" ;
	return $tabs;
}

/*
*/
function generate_buyer_tabs($active_tab_ordinal, $party_id, $state = state_VIEW)
{
	$tabs  = generate_tab("Orders","View All Orders", "search_order.php?entity=".entity_GEN_ORDER."&referrer=".entity_BUYER."&referrer_id=".$party_id, $active_tab_ordinal==1, $party_id > 0);
	$tabs .= generate_tab("Invoices","View All Invoices", "search_invoice.php?referrer=".entity_BUYER."&referrer_id=".$party_id, $active_tab_ordinal==2, $party_id > 0);
	$tabs .= generate_tab("Buyer to Org. Milestones","View All Buyer to Organization Milestones", "party_milestones.php?referrer=".entity_BUYER."&referrer_id=".$party_id."&milestone_type=".milestone_INCOMING_PAYMENT, $active_tab_ordinal==3, $party_id > 0);
	$tabs .= generate_tab("Supplier Milestones","View All Supplier Milestones", "party_milestones.php?referrer=".entity_BUYER."&referrer_id=".$party_id."&milestone_type=".milestone_B2S_PAYMENT,$active_tab_ordinal==4, $party_id > 0);
	$tabs .= generate_tab("Profile","View & Edit Profile", "party.php?party_id=".$party_id . "&state=" .$state . "&type=" . entity_BUYER, $active_tab_ordinal==5, $party_id > 0);
	$tabs .= "<td class=\"blank_tab\">&nbsp;</td>" ;
	return $tabs;
}
/*
*/
function generate_supplier_tabs($active_tab_ordinal, $party_id, $state = state_VIEW)
{
	$tabs  = generate_tab("Orders","View All Orders", "search_order.php?entity=".entity_SINGLE_ORDER."&referrer=".entity_SUPPLIER."&referrer_id=".$party_id, $active_tab_ordinal==1, $party_id > 0);
	$tabs .= generate_tab("Invoices","View All Invoices", "search_invoice.php?referrer=".entity_SUPPLIER."&referrer_id=".$party_id, $active_tab_ordinal==2, $party_id > 0);
	$tabs .= generate_tab("Supplier to Org. Milestones","View All Supplier to Organization Milestones", "party_milestones.php?referrer=".entity_SUPPLIER."&referrer_id=".$party_id."&milestone_type=".milestone_INCOMING_PAYMENT, $active_tab_ordinal==3, $party_id > 0);
	$tabs .= generate_tab("Buyer Milestones","View All Buyer Milestones","party_milestones.php?referrer=".entity_SUPPLIER."&referrer_id=".$party_id."&milestone_type=".milestone_B2S_PAYMENT, $active_tab_ordinal==4, $party_id > 0);
	$tabs .= generate_tab("Profile","View & Edit Profile", "party.php?party_id=".$party_id . "&state=" .$state . "&type=" . entity_SUPPLIER, $active_tab_ordinal==5, $party_id > 0);
	$tabs .= "<td class=\"blank_tab\">&nbsp;</td>" ;
	return $tabs;
}

/*
*/
function generate_external_tabs($active_tab_ordinal, $party_id, $state = state_VIEW)
{
	$tabs  = generate_tab("Profile","View & Edit Profile", "party.php?party_id=".$party_id . "&state=" .$state . "&type=" . entity_EXTERNAL, $active_tab_ordinal==1, $party_id > 0);
	$tabs .= "<td class=\"blank_tab\">&nbsp;</td>" ;
	return $tabs;
}

/*
*/
function generate_organization_tabs($active_tab_ordinal, $party_id, $state = state_VIEW)
{
	$tabs  = generate_tab("Profile","Organization's Profile", "party.php?party_id=".$party_id . "&state=" .$state . "&type=" . entity_INTERNAL, $active_tab_ordinal==1, $party_id > 0);
	$tabs .= "<td class=\"blank_tab\">&nbsp;</td>" ;
	return $tabs;
}
/*
*/
function generate_gen_order_tabs($active_tab_ordinal, $order_id, $state = state_VIEW)
{
	$tabs  = generate_tab("Single Orders","View Single Orders","search_order.php?entity=".entity_SINGLE_ORDER."&referrer=".entity_GEN_ORDER."&referrer_id=".$order_id, $active_tab_ordinal==1, $order_id > 0);
	$tabs .= generate_tab("Invoices","View Invoices","search_invoice.php?referrer=".entity_GEN_ORDER."&referrer_id=".$order_id, $active_tab_ordinal==2, $order_id > 0);
	//$tabs .= generate_tab("Audit Trail","View Audit Trail","uc.php", $active_tab_ordinal==3, $order_id > 0);
	$tabs .= generate_tab("Financials","View Financials","gen_order_financials.php?gen_order_id=".$order_id, $active_tab_ordinal==4, $order_id > 0);
	$tabs .= generate_tab("Profile","View & Edit Order Profile","gen_order.php?gen_order_id=".$order_id."&state=".$state, $active_tab_ordinal==5, $order_id > 0);
	$tabs .= "<td class=\"blank_tab\">&nbsp;</td>" ;
	return $tabs;
}

/*
*/
function generate_single_order_tabs($active_tab_ordinal, $order_id, $state = state_VIEW)
{
	$product_id = get_sin_order_prod_id($order_id);
	$prod_state = ( ($product_id <= 0) ? state_NEW : $state) ;
	$tabs  = generate_tab("Production Management","Plan and Track Production","single_order_prod_mgmt.php?single_order_id=".$order_id, $active_tab_ordinal==1, $order_id > 0 );
	$tabs .= generate_tab("Product","View & Edit Product Information","single_order_product.php?single_order_id=".$order_id."&product_id=".$product_id."&state=".$prod_state , $active_tab_ordinal==2, $order_id > 0);
	$tabs .= generate_tab("Milestones","View & Edit Milestones","single_order_milestones.php?single_order_id=".$order_id, $active_tab_ordinal==3, $order_id > 0);
	$tabs .= generate_tab("Invoices","View Invoices","search_invoice.php?referrer=".entity_SINGLE_ORDER."&referrer_id=".$order_id, $active_tab_ordinal==4, $order_id > 0);
	//$tabs .= generate_tab("Audit Trail","View Audit Trail","single_order_audit_trail.php?single_order_id=".$order_id, $active_tab_ordinal==5, $order_id > 0);
	$tabs .= generate_tab("Financials","View & Edit Financials","single_order_financials.php?single_order_id=".$order_id."&state=".state_VIEW, $active_tab_ordinal==6, $order_id > 0);
	$tabs .= generate_tab("Profile","View & Edit Order Profile","single_order.php?single_order_id=".$order_id."&state=".$state, $active_tab_ordinal==7, $order_id > 0);
	$tabs .= "<td class=\"blank_tab\">&nbsp;</td>" ;
	return $tabs;
}

/*
*/
function generate_invoice_tabs($active_tab_ordinal, $invoice_id, $state = state_VIEW)
{
	$tabs  = generate_tab("Invoice","View & Edit Invoice", "invoice.php?invoice_id=".$invoice_id."&state=".$state, $active_tab_ordinal==1, $invoice_id > 0);
	$tabs .= generate_tab("Payments Balance","View & Edit Payments", "invoice_payments.php?invoice_id=".$invoice_id."&state=".$state, $active_tab_ordinal==2, $invoice_id > 0);
	//$tabs .= generate_tab("Audit Trail","View Audit Trail", "invoice_audit_trail.php?invoice_id=".$invoice_id, $active_tab_ordinal==3, $invoice_id > 0);
	$tabs .= "<td class=\"blank_tab\">&nbsp;</td>" ;
	return $tabs;
}

/*
	Generate attachments block
*/
function show_attachments($table_name, $entity_id, $additional_where_clause = '')
{
	global $glb_arr_values, $state;

	// Get attachments from DB
	$sql = generate_select_attachments_sql($table_name,$entity_id, $additional_where_clause);
	$result = mysql_query($sql) or die(mysql_error());

	$markup = " <tr>
				<td valign=\"top\"><b>Attachments:</b></td>";

	// If there are no attachments AND we're not in EDIT state
	if ( (! mysql_num_rows($result)) && ($state != state_EDIT ) )  {
		$markup .= "<td colspan =\"3\"><i>No Attachments</i></td></tr>\r\n";
	}
	else {
		$markup .= "<td colspan = \"3\">
					<table>
					<tr class=\"attachments_header\">";
		if ($state == state_EDIT ) {
			$markup .= "<td class=\"small_text\" width=\"5%\"><a href=\"javascript:void open_popup('attachment_add.php?table_name=".$table_name."&row_id=".$entity_id."',
						'attachmentAdd','menubar=no,toolbar=no,scrollbars=no',155,425);\">
						<img src=\"images/b.add.png\" alt=\"Add\" title=\"Add\"/></a></td>\r\n";
		}
		$markup .= "	<td><strong>Description</strong></td>
						<td><strong>File Name</strong></td>
						<td><strong>Size</strong></td>
						<td><strong>Date Uploaded</strong></td>
						</tr>";
		$i = 0;
		while ($row = mysql_fetch_array($result)) {
			$attch_id	 = $row['ATTACHMENT_ID'];
			$description = htmlspecialchars(mysql_decode($row['DESCRIPTION']));
			$filename  	 = htmlspecialchars(mysql_decode($row['FILENAME']));
			$filesize	 = htmlspecialchars(mysql_decode($row['SIZE']));   // in bytes
			$date		 = htmlspecialchars(mysql_decode($row['DATE_CREATED']));

			$markup .= "<tr class=\"attachments_line" .($i%2). "\">";
			if ($state != state_VIEW ) {
				$markup .= "<td class=\"small_text\"><a href=\"javascript:void subEntityDelete('ATTACHMENT',". $attch_id .");\">
				    		<img src=\"images/b.delete.png\" alt=\"Delete\" title=\"Delete\"/></a></td>";
			}
			$markup .= "<td>". $description . "</td> ";
			$markup .= "<td><a href=\"do_download.php?file_id=". $attch_id ."\">". $filename . "</a></td>";
			$markup .= "<td>". format_file_size($filesize, 2) . "</td> ";
			$markup .= "<td>". format_date_reverse($date) . "</td> ";
			$markup .= "</tr>";
			$i++;
		} // end while
		mysql_free_result($result);
		// Attachments table footer
		$markup .= "</table></td></tr>\r\n" ;
	} // end else

	return $markup;
}

function generate_required_superscript ($state = state_EDIT)
{
	if (($state == state_EDIT) || ($state == state_NEW)) {
		$markup = '<sup title="Required Field" style="color:red;">*</sup>';
	}
	else {
		$markup = '';
	}
	return $markup;
}
/*
	Generate the XHTML fragment for the filters section of the page

*/
function generate_filters_headers($entity_id)
{
/*
	global $glb_arr_values;
	$markup = '	<table>
					<tr class="actions_header" >
						<td width="10%"><input type="checkbox" id="STATUS_CHKBOX" name="STATUS_CHKBOX">Status</td>
						<td width="35%"><input type="checkbox" id="DATE_RANGE_CHKBOX" name="DATE_RANGE_CHKBOX">Date Range</td>
						<td colspan ="5">Actions</td>
					</tr>
					<tr>
						<td>
						<select id="STATUS_FILTER" name="STATUS_FILTER">
						<option value = "0">All</option>';

	switch ($entity_id) {
		case entity_MILESTONE:
		$markup .= populate_milestones_status_dropdown(0);
		break;
		case entity_INVOICE:
		$markup .= populate_invoice_status_dropdown(0);
		break;
		case entity_SINGLE_ORDER:
		$markup .= populate_order_status_dropdown(0);
		break;
		case entity_GEN_ORDER:
		$markup .= populate_order_status_dropdown(0);
		break;
	}

	$markup .='</select>
				</td>
				<td>
				Start Date:&nbsp;';
	$markup .= generate_text_input ("date_box", "START_DATE", $glb_arr_values['START_DATE']);
	$markup .= '&nbsp;End Date:&nbsp;';
	$markup .= generate_text_input ("date_box", "END_DATE", $glb_arr_values['END_DATE']);
	$markup .= '</td><td>';
	$markup .= generate_button("button","Apply Filter","alert('Under Construction');");
	$markup .= '</td>
				</tr>
				</table>	';
	return $markup;
*/
}

/*
	Setup sort variables used to sort and display arrows
	Variables are passed by reference since they're accessed in the caller
*/
function do_sort(&$sort_by, &$prior_sort_by, &$sort_order, &$glb_arr_sort_indicators)
{
	// Toggle
	if ($sort_by === $prior_sort_by) {
		$sort_order = ($sort_order==='ASC') ? 'DESC' : 'ASC';
		$prior_sort_by = "";
		$glb_arr_sort_indicators[$sort_by] = ($sort_order==='ASC') ?
											'<img src="images/up.gif" alt="Ascending order"/>':
											'<img src="images/down.gif" alt="Descending order"/>';
	}
	else {        // First click on this column
		$sort_order = "ASC";
		$prior_sort_by = $sort_by;
		$glb_arr_sort_indicators[$sort_by] = '<img src="images/up.gif" alt="Ascending order"/>';
	}
}

/*
	Version of count() that does not break PHP 8.0
	https://stackoverflow.com/a/67971911/209184
*/
function safe_count($var) {
	if (is_countable($var)) return count($var);
	return 0;
}

?>
