
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
/***********************************************************************
	This file contains DB-related functions, utilities, and parameters
************************************************************************/
error_reporting( E_ALL );
require_once("mysql.php");

/* Connect to DB */
$contrack_connection = mysql_pconnect($_ENV['MYSQL_HOST'], $_ENV['MYSQL_USER'], $_ENV['MYSQL_PASSWORD']) or die(mysql_error());
mysql_select_db($_ENV['MYSQL_DATABASE']) or die(mysql_error());

/*
	Function: mysql_decode ( )
	Desc:
		Converts strings returned from DB to their applicable HTML characters (if any)
		and strips any slashes
 	Parms:
    	$str_x - string returned from DB that needs to be converted
   	Returns:
		Decoded string
*/
function mysql_decode($str_x)
{
	return stripslashes(html_entity_decode($str_x));
}

/*
	Function: mysql_encode ( )
	Desc:
		Makes strings SQL safe by escaping special characters (i.e. ')
 	Parms:
    	$str_x - string to be sent to the MySQL DB
   	Returns:
		Encoded SQL-safe string
*/
function mysql_encode($str_x)
{
	return mysql_real_escape_string($str_x);
}

/*
	Function: mysql_encode_w_quotes ( )
	Desc:
		Makes strings SQL safe by escaping special characters (i.e. ') and adding the single
		quotes around the passed value
 	Parms:
    	$str_x - string to be sent to the MySQL DB
   	Returns:
		Encoded SQL-safe string
*/
function mysql_encode_w_quotes($str_x)
{
	return "'". mysql_real_escape_string($str_x) . "'";
}
/*
	Function: get_country_name ( )
	Desc:
		Gets the country name from the COUNTRY table in the DB given the $country_id
 	Parms:
    	$int_country_id - country ID
   	Returns:
		Country name if it exists, empty string otherwise.
*/
function get_country_name($int_country_id)
{

	if  ( ($int_country_id != 0 ) && (!empty($int_country_id)) ){
		$sql = "SELECT NAME FROM country WHERE COUNTRY_ID=". $int_country_id;
		$result = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_array($result);
		if (mysql_num_rows($result)){
			return $row['NAME'];
		}
		else {
			return "";
		}
	}
	else {
		return "";
	}
}

/*
	Function: get_party_name ( )
	Desc:
		Gets the party name from the PARTY table in the DB given the $int_party_id
 	Parms:
    	$int_party_id - Party ID
   	Returns:
		Party name if it exists, empty string otherwise.
*/
function get_party_name($int_party_id)
{

	if  ( ($int_party_id != 0 ) && (!empty($int_party_id)) ){
		$sql = "SELECT NAME FROM party WHERE PARTY_ID=". $int_party_id;
		$result = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_array($result);
		if (mysql_num_rows($result)){
			return $row['NAME'];
		}
		else {
			return "";
		}
	}
	else {
		return "";
	}
}
/*
	Function: get_party_id_for_user ( )
	Desc:
		Fetches associated party id for given user
 	Parms:
    	$int_uid
   	Returns:
		party_id on success, blank on error
*/
function get_party_id_for_user($int_uid)
{
	if  ($int_uid != 0 ) {
		$sql = "SELECT PARTY_ID FROM user WHERE uid=". $int_uid;
		$result = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_array($result);
		if (mysql_num_rows($result)){
			return $row['PARTY_ID'];
		}
		else {
			return "";
		}
	}
	else {
		return "";
	}
}
/*
	Function: get_currency_desc ( )
	Desc:
		Gets the currency desc from the CURRENCY table in the DB given the $int_currency_id
 	Parms:
    	$int_currency_id - Currency ID
   	Returns:
		Currency desc if it exists, empty string otherwise.
*/
function get_currency_desc($int_currency_id)
{

	if  ( ($int_currency_id != 0 ) && (!empty($int_currency_id)) ){
		$sql = "SELECT NAME FROM currency WHERE CURRENCY_ID=". $int_currency_id;
		$result = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_array($result);
		if (mysql_num_rows($result)){
			return $row['NAME'];
		}
		else {
			return "";
		}
	}
	else {
		return "";
	}
}
/*
	Function: get_date_created_for_entity ( )
	Desc:
		Fetches the date_created field for the given entity id
 	Parms:
    	$int_entity_type_id
		$int_entity_id
   	Returns:
		date_created on success, blank on error
*/
function get_date_created_for_entity($int_entity_type_id, $int_entity_id)
{
	if  (($int_entity_id != 0 ) && ($int_entity_type_id != 0)) {
		switch ($int_entity_type_id) {
			case entity_AGENT:
			case entity_BUYER:
			case entity_SUPPLIER:
			case entity_EXTERNAL:
			$table_name = 'party';
			$pk_name    = 'party_id';
			break;
			case entity_INVOICE :
			$table_name = 'invoice';
			$pk_name    = 'invoice_id';
			break ;
			case entity_GEN_ORDER:
			$table_name = 'gen_order';
			$pk_name    = 'gen_order_id';
			break;
			case entity_SINGLE_ORDER:
			$table_name = 'single_order';
			$pk_name    = 'single_order_id';
			break;
			default:   //what do i do here?
		}
		$sql = "SELECT DATE_CREATED FROM ". $table_name. " WHERE ". $pk_name." = ". $int_entity_id;
		$result = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_array($result);
		if (mysql_num_rows($result)){
			return $row['DATE_CREATED'];
		}
		else {
			return "";
		}
	}
	else {
		return "";
	}
}

/*
	Generates select attachments SQL
*/
function generate_select_attachments_sql ($table_name, $entity_id, $additional_where_clause = '')
{
	$select_sql = "	SELECT	ATTACHMENT_ID, FILENAME, DESCRIPTION, SIZE, DATE_CREATED
					FROM attachment
					WHERE TABLE_NAME = '".$table_name."' AND ROW_ID = ". $entity_id .
					$additional_where_clause;
	return $select_sql;
}

/*
	Delete attachment
*/

function delete_attachment($attachment_id)
{
	if ($attachment_id > 0 ) {
		$sql = "DELETE FROM attachment WHERE ATTACHMENT_ID =". $attachment_id;
		$result = mysql_query($sql) or die(mysql_error());
	}
}
?>
