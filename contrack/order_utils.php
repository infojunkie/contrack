
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
  	This file contains general and single order utilities functions
******************************************************************************************/
define ("ROUND_PRECISION", 1);
/*
	Format the general order name
*/
function format_gen_order_name($int_gen_order_id)
{
	if  ( ($int_gen_order_id != 0 ) && (!empty($int_gen_order_id)) ){
		$sql = 'SELECT gen_order.FRIENDLY_NAME, CODE, CLIENT_ORDER_ID
				FROM gen_order
				WHERE gen_order.GEN_ORDER_ID = '. $int_gen_order_id;
		$result = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_array($result);
		if (mysql_num_rows($result)){
			return  $row['FRIENDLY_NAME']." (". $row['CODE']."/". $row['CLIENT_ORDER_ID'].")";
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
	Format the single order name
*/
function format_sin_order_name($int_single_order_id)
{
	if  ( ($int_single_order_id != 0 ) && (!empty($int_single_order_id)) ){
		$sql = 'SELECT single_order.FRIENDLY_NAME, CODE, CLIENT_ORDER_ID
				FROM single_order
				WHERE single_order.SINGLE_ORDER_ID = '. $int_single_order_id;
		$result = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_array($result);
		if (mysql_num_rows($result)){
			return  $row['FRIENDLY_NAME']." (". $row['CODE']."/". $row['CLIENT_ORDER_ID'].")";
		}
		else {
			return "";
		}
	}
	else {
		return "";
	}
}

function format_qty($quantity)
{
	return number_format($quantity, 0, '.', ',');
}
/*
	Function: get_gen_order_buyer_id( )
	Desc:
		Fetches the buyer ID for the given General Order
 	Parms:
    	$int_gen_order_id
   	Returns:
		$int_buyer_id on success, blank on error
*/
function get_gen_order_buyer_id($int_gen_order_id)
{
	if  ($int_gen_order_id != 0 ) {
		$sql = "SELECT BUYER_ID FROM gen_order WHERE gen_order_id=". $int_gen_order_id;
		$result = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_array($result);
		if (mysql_num_rows($result)){
			return $row['BUYER_ID'];
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
	Returns an associative array containg 2 elements; BUYER_ID and BUYER_NAME
*/
function get_gen_order_buyer_id_and_name($gen_order_id)
{
	if  ( ($gen_order_id != 0 ) && (!empty($gen_order_id)) ){
		$sql = "SELECT gen_order.BUYER_ID, party.NAME AS BUYER_NAME
				FROM gen_order INNER JOIN party on gen_order.BUYER_ID = party.PARTY_ID
				WHERE gen_order.GEN_ORDER_ID = ". $gen_order_id;
		$result = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_array($result);
		if (mysql_num_rows($result)){
			return $row;
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
	Returns an associative array containg 2 elements; AGENT_ID and AGENT_NAME
*/
function get_gen_order_agent_id_and_name($gen_order_id)
{
	if  ( ($gen_order_id != 0 ) && (!empty($gen_order_id)) ){
		$sql = "SELECT gen_order.AGENT_ID, party.NAME AS AGENT_NAME
				FROM gen_order INNER JOIN party on gen_order.AGENT_ID = party.PARTY_ID
				WHERE gen_order.GEN_ORDER_ID = ". $gen_order_id;
		$result = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_array($result);
		if (mysql_num_rows($result)){
			return $row;
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
	Returns the currency code for the general order
*/
function get_gen_order_currency($int_gen_order_id)
{
	if  ( ($int_gen_order_id != 0 ) && (!empty($int_gen_order_id)) ){
		$sql = 'SELECT currency.CODE
				FROM gen_order INNER JOIN currency ON currency.CURRENCY_ID = gen_order.CURRENCY_ID
				WHERE gen_order.GEN_ORDER_ID ='. $int_gen_order_id;
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

/*
	Get Percent Complete (i.e. progress) for different entities
*/
function get_percent_complete($entity_id, $entity_type)
{
	switch ($entity_type) {
		case entity_GEN_ORDER:
		$pcent_complete = get_gen_order_pcent_complete($entity_id);
		break;
		case entity_SINGLE_ORDER:
		$pcent_complete = get_sin_order_pcent_complete($entity_id);
		break;
		case entity_PROD_SUMMARY:
		$pcent_complete = get_prod_summary_pcent_complete($entity_id);
		break;
		case entity_PROD_PHASE:
		$pcent_complete = get_prod_phase_pcent_complete($entity_id);
		break;
		case entity_PROD_STEP:
		$pcent_complete = get_prod_step_pcent_complete($entity_id);
		break;
	}
	return $pcent_complete . '%';
}

/*
	Calculate Percent Complete for the General Order
	Formula: sum(SO %) / safe_count(SO)
	- If an SO has a percent complete of 'N/A' it is not included in the calculation
*/

function get_gen_order_pcent_complete($order_id)
{
	$sql = "SELECT single_order.SINGLE_ORDER_ID
			FROM single_order
			WHERE single_order.GEN_ORDER_ID = " . $order_id;
	$result 	  = mysql_query($sql) or die(mysql_error());
	$count_SO	  = 0;
	$sum_SO_pcent = 0;
	while ($row = mysql_fetch_array($result)) {
		$SO_pcent_complete = get_sin_order_pcent_complete($row['SINGLE_ORDER_ID']);
		if (strcasecmp($SO_pcent_complete,'N/A') != 0) {
			$sum_SO_pcent += $SO_pcent_complete;
			++$count_SO;
		}
	}
	$gen_order_pcent_complete = ($count_SO > 0?round(($sum_SO_pcent/$count_SO), ROUND_PRECISION):'N/A');
	return $gen_order_pcent_complete;
}
/*
	Calculate Percent Complete for the Single Order (i.e. the overall production process )
	Formula: sum(phases %) / safe_count(phases)
	- If a Phase has a percent complete of 'N/A' it is not included in the calculation
*/
function get_sin_order_pcent_complete($order_id)
{
	$sql = "SELECT production_step.PRODUCTION_STEP_ID
			FROM production_step
			WHERE production_step.SINGLE_ORDER_ID = " . $order_id . "
			AND production_step.PARENT_ID = 0";
	$result 		  = mysql_query($sql) or die(mysql_error());
	$count_phases	  = 0;
	$sum_phases_pcent = 0;
	while ($row = mysql_fetch_array($result)) {
		$phase_pcent_complete = get_prod_phase_pcent_complete($row['PRODUCTION_STEP_ID']);
		if (strcasecmp($phase_pcent_complete, 'N/A') != 0) {
			$sum_phases_pcent += $phase_pcent_complete;
			++$count_phases;
		}
	}
	$sin_order_pcent_complete = ($count_phases > 0?round(($sum_phases_pcent/$count_phases), ROUND_PRECISION):'N/A');
	return $sin_order_pcent_complete;
}
/*
	Calucate the Production Target Percent Complete
	Formula: min(100, (Actual end quantity/Planned end quantity) * 100)
	Notes:
		- Get PRODUCTION_STEP_ID for production summary step
		- Call get_prod_step_pcent_complete() with that ID to apply the formula above
		  on the production summary step (Root Step)
*/
function get_prod_target_pcent_complete($single_order_id)
{
	$sql = "SELECT production_step.PRODUCTION_STEP_ID
			FROM production_step
			WHERE production_step.SINGLE_ORDER_ID = " . $single_order_id.
		  " AND production_step.PARENT_ID = -1";
	$result = mysql_query($sql) or die(mysql_error());
	$row = mysql_fetch_array($result);
	if (mysql_num_rows($result)) {
		$prod_target_pcent_complete = get_prod_step_pcent_complete($row['PRODUCTION_STEP_ID']);
	}
	else {
		$prod_target_pcent_complete = 'N/A';
	}
	return $prod_target_pcent_complete . '%' ;
}


/*
	Calculate Percent Complete for the overall production process
	Formula: sum(phases %) / safe_count(phases)
	Notes:
		- Get Single Order ID that this Production Summary Entry belongs to
		- Call get_sin_order_pcent_complete()
*/
function get_prod_summary_pcent_complete($prod_entry_id)
{

	$sql = "SELECT production_step.SINGLE_ORDER_ID
			FROM production_step
			WHERE production_step.PRODUCTION_STEP_ID = " . $prod_entry_id;
	$result = mysql_query($sql) or die(mysql_error());
	$row = mysql_fetch_array($result);
	if (mysql_num_rows($result)) {
		$prod_summary_pcent_complete = get_sin_order_pcent_complete($row['SINGLE_ORDER_ID']);
	}
	else {
		$prod_summary_pcent_complete = 'N/A';
	}
	return $prod_summary_pcent_complete ;
}
/*
	Calculate Percent Complete for Production Phase
	Formula: sum(steps %) / safe_count(steps)
	Notes:
		- Get PLANNED_END_QTY and ACTUAL_END_QTY for Steps here to minimize roundtrips to DB
		  As opposed to calling get_prod_step_pcent_complete() multiple times and going to the DB each time
		- If a Step has a PLANNED_END_QTY of ZERO it is not included in the calculation
*/
function get_prod_phase_pcent_complete($prod_entry_id)
{
	$sql = "SELECT production_step.PLANNED_END_QTY, production_step.ACTUAL_END_QTY
			FROM production_step
			WHERE production_step.PARENT_ID = " . $prod_entry_id;
	$result 		 = mysql_query($sql) or die(mysql_error());
	$count_steps 	 = 0;
	$sum_steps_pcent = 0;
	while ($row = mysql_fetch_array($result)) {
		if ($row['PLANNED_END_QTY'] > 0) {
			$step_pcent_complete = min(100, 100*($row['ACTUAL_END_QTY']/$row['PLANNED_END_QTY']));
			$sum_steps_pcent += $step_pcent_complete;
			++$count_steps;
		}
	}
	$phase_pcent_complete = ($count_steps > 0?round(($sum_steps_pcent/$count_steps), ROUND_PRECISION):'N/A');
	return $phase_pcent_complete;
}

/*
	Calculate Percent Complete for Production Step
	Formula : min(100, (Actual end quantity/Planned end quantity) * 100)
			  min is used because quantities could be over-produced.
*/
function get_prod_step_pcent_complete($prod_entry_id)
{
	$sql = "SELECT production_step.PLANNED_END_QTY, production_step.ACTUAL_END_QTY
			FROM production_step
			WHERE production_step.PRODUCTION_STEP_ID = " . $prod_entry_id;
	$result = mysql_query($sql) or die(mysql_error());
	$row = mysql_fetch_array($result);
	if ((mysql_num_rows($result)) && ($row['PLANNED_END_QTY'] > 0)){
		$step_pcent_complete = round(min(100, 100*($row['ACTUAL_END_QTY']/$row['PLANNED_END_QTY'])), ROUND_PRECISION);
	}
	else {
		$step_pcent_complete = 'N/A';
	}
	return $step_pcent_complete;
}

/*
	Returns an associative array containg 2 elements; BUYER_ID and BUYER_NAME
*/
function get_sin_order_buyer_id_and_name($int_single_order_id)
{
	if  ( ($int_single_order_id != 0 ) && (!empty($int_single_order_id)) ){
		$sql = 'SELECT gen_order.BUYER_ID, party.NAME AS BUYER_NAME
				FROM single_order INNER JOIN gen_order on single_order.GEN_ORDER_ID = gen_order.GEN_ORDER_ID
								  INNER JOIN party on gen_order.BUYER_ID = party.PARTY_ID
				WHERE single_order.SINGLE_ORDER_ID = '. $int_single_order_id;
		$result = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_array($result);
		if (mysql_num_rows($result)){
			return $row;
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
	Returns an associative array containg 2 elements; AGENT_ID and AGENT_NAME
*/
function get_sin_order_agent_id_and_name($int_single_order_id)
{
	if  ( ($int_single_order_id != 0 ) && (!empty($int_single_order_id)) ){
		$sql = 'SELECT gen_order.AGENT_ID, party.NAME AS AGENT_NAME
				FROM single_order INNER JOIN gen_order on single_order.GEN_ORDER_ID = gen_order.GEN_ORDER_ID
								  INNER JOIN party on gen_order.AGENT_ID = party.PARTY_ID
				WHERE single_order.SINGLE_ORDER_ID = '. $int_single_order_id;
		$result = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_array($result);
		if (mysql_num_rows($result)){
			return $row;
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
	Returns an associative array containg 2 elements; SUPPLIER_ID and SUPPLIER_NAME
*/
function get_sin_order_supplier_id_and_name($int_single_order_id)
{
	if  ( ($int_single_order_id != 0 ) && (!empty($int_single_order_id)) ){
		$sql = 'SELECT single_order.SUPPLIER_ID, party.NAME AS SUPPLIER_NAME
				FROM single_order INNER JOIN party on single_order.SUPPLIER_ID = party.PARTY_ID
				WHERE single_order.SINGLE_ORDER_ID = '. $int_single_order_id;
		$result = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_array($result);
		if (mysql_num_rows($result)){
			return $row;
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
*/
function get_sin_order_base_cost($int_single_order_id)
{
	if  ( ($int_single_order_id != 0 ) && (!empty($int_single_order_id)) ){
		$sql = 'SELECT single_order.UNIT_PRICE * IF(production_step.PLANNED_END_QTY IS NULL, 0, production_step.PLANNED_END_QTY) AS BASE_COST
				FROM single_order LEFT JOIN production_step on single_order.SINGLE_ORDER_ID  = production_step.SINGLE_ORDER_ID
				WHERE (IF(production_step.PARENT_ID IS NULL, -1, production_step.PARENT_ID) = -1)
					AND single_order.SINGLE_ORDER_ID = '. $int_single_order_id;
		$result = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_array($result);
		if (mysql_num_rows($result)){
			return $row['BASE_COST'];
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
*/
function get_sin_order_commission_pcent($dec_base_cost, $int_commission_value)
{
	if ($dec_base_cost > 0 ) {
		$dec_commision_pcent = ($int_commission_value/$dec_base_cost) * 100;
		return round($dec_commision_pcent, 2);
	}
}

/*
*/
function get_sin_order_commission_value($dec_base_cost, $int_commission_pcent)
{
	if ($dec_base_cost > 0 ) {
		$dec_commision_value = $dec_base_cost * ($int_commission_pcent / 100);
		return round($dec_commision_value, 2);
	}
}

/*
*/
function get_sin_order_delivery_date($int_single_order_id)
{
	if  ( ($int_single_order_id != 0 ) && (!empty($int_single_order_id)) ){
		$sql = 'SELECT production_step.PLANNED_END_DATE
			FROM single_order LEFT JOIN production_step on single_order.SINGLE_ORDER_ID  = production_step.SINGLE_ORDER_ID
			WHERE 	(IF(production_step.PARENT_ID IS NULL, -1, production_step.PARENT_ID) = -1)
				AND single_order.SINGLE_ORDER_ID ='. $int_single_order_id;
		$result = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_array($result);
		if (mysql_num_rows($result)){
			return $row['PLANNED_END_DATE'];
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
*/
function get_sin_order_currency($int_single_order_id)
{
	if  ( ($int_single_order_id != 0 ) && (!empty($int_single_order_id)) ){
		$sql = 'SELECT currency.CODE
				FROM single_order INNER JOIN gen_order ON single_order.GEN_ORDER_ID = gen_order.GEN_ORDER_ID
                                  INNER JOIN currency ON currency.CURRENCY_ID = gen_order.CURRENCY_ID
				WHERE single_order.SINGLE_ORDER_ID ='. $int_single_order_id;
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
/*
*/
function get_sin_order_gen_order_id($int_single_order_id)
{
	if  ( ($int_single_order_id != 0 ) && (!empty($int_single_order_id)) ){
		$sql = 'SELECT single_order.GEN_ORDER_ID
				FROM single_order
				WHERE single_order.SINGLE_ORDER_ID ='. $int_single_order_id;
		$result = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_array($result);
		if (mysql_num_rows($result)){
			return $row['GEN_ORDER_ID'];
		}
		else {
			return -1;
		}
	}
	else {
		return -1;
	}
}
/*
	Get product ID for single order if it exists.
	If no product ID exists, return zero.
*/
function get_sin_order_prod_id($int_single_order_id)
{
	if  ( ($int_single_order_id != 0 ) && (!empty($int_single_order_id)) ) {
		$sql = ' SELECT PRODUCT_ID FROM single_order
					WHERE SINGLE_ORDER_ID = '. $int_single_order_id ;
		$result = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_array($result);
		if (mysql_num_rows($result)){
			return $row['PRODUCT_ID'];
		}
		else {
			return 0;
		}
	}
	else {
		return 0;
	}
}
/*
	Generate SQL statement to select general orders

	Notes:
			Base Cost Query Logic:
			- Get all single orders for that general order
			- Get base cost for each single order by finding the associated production step with that single order
			  which doesn't have a parent_id (root production step) and multiply its planned_end_quantity by the
			  unit_price in the SINGLE_ORDER .
			- Do this for all single orders and sum the base costs to get the base cost for the general order.
*/
function generate_select_gen_orders_sql ($sort_by, $sort_order, $sql_where_clause)
{
	$sql = "SELECT  gen_order.GEN_ORDER_ID, gen_order.FRIENDLY_NAME, gen_order.CODE, gen_order.CLIENT_ORDER_ID,
					buyer.PARTY_ID AS BUYER_ID,  buyer.NAME AS BUYER_NAME,
					agent.PARTY_ID AS AGENT_ID , agent.NAME AS AGENT_NAME, currency.CODE AS CURRENCY,
					SUM(single_order.UNIT_PRICE * IF(production_step.PLANNED_END_QTY IS NULL, 0, production_step.PLANNED_END_QTY))
					AS BASE_COST, gen_order.STATUS, gen_order.DATE_CREATED, gen_order.PLANNED_DELIVERY_DATE AS PLANNED_DELIVERY_DATE
			FROM 	gen_order INNER JOIN party AS buyer ON gen_order.BUYER_ID 	= buyer.PARTY_ID
                           	  INNER JOIN party AS agent ON gen_order.AGENT_ID 	= agent.PARTY_ID
                              LEFT JOIN single_order ON gen_order.GEN_ORDER_ID  = single_order.GEN_ORDER_ID
                              LEFT JOIN production_step on single_order.SINGLE_ORDER_ID  = production_step.SINGLE_ORDER_ID
							  INNER JOIN currency ON gen_order.CURRENCY_ID = currency.CURRENCY_ID
			WHERE 	(IF(production_step.PARENT_ID IS NULL, -1, production_step.PARENT_ID) = -1) ";
	$sql .= $sql_where_clause;
	$sql .=	" GROUP BY gen_order.GEN_ORDER_ID ";
	if (!empty($sort_by)) {
		$sql .= " ORDER BY ". $sort_by. " ". $sort_order;
	}
	else {
		$sql .= " ORDER BY gen_order.FRIENDLY_NAME ASC";
	}
	return $sql;
}

/*
	Generate SQL statement to select single orders
*/
function generate_select_single_orders_sql ($sort_by, $sort_order, $sql_where_clause)
{
	$sql = "SELECT  single_order.SINGLE_ORDER_ID, single_order.FRIENDLY_NAME, single_order.CODE, single_order.CLIENT_ORDER_ID,
			buyer.PARTY_ID AS BUYER_ID, buyer.NAME AS BUYER_NAME,
			supplier.PARTY_ID AS SUPPLIER_ID, supplier.NAME AS SUPPLIER_NAME,
			agent.PARTY_ID AS AGENT_ID, agent.NAME AS AGENT_NAME, currency.CODE AS CURRENCY,
			SUM(single_order.UNIT_PRICE * IF(production_step.PLANNED_END_QTY IS NULL, 0, production_step.PLANNED_END_QTY))
			AS BASE_COST, single_order.STATUS, single_order.DATE_CREATED,
			production_step.PLANNED_END_DATE AS PLANNED_DELIVERY_DATE
			FROM single_order INNER JOIN party AS supplier ON single_order.SUPPLIER_ID = supplier.PARTY_ID
                           	  INNER JOIN gen_order on gen_order.GEN_ORDER_ID   = single_order.GEN_ORDER_ID
                              INNER JOIN party AS buyer ON gen_order.BUYER_ID  = buyer.PARTY_ID
                              INNER JOIN party AS agent ON gen_order.AGENT_ID  = agent.PARTY_ID
                              LEFT JOIN production_step ON single_order.SINGLE_ORDER_ID  = production_step.SINGLE_ORDER_ID
							  INNER JOIN currency ON gen_order.CURRENCY_ID = currency.CURRENCY_ID
			WHERE 	(IF(production_step.PARENT_ID IS NULL, -1, production_step.PARENT_ID) = -1) ";
	$sql .= $sql_where_clause;
	$sql .= " GROUP BY single_order.SINGLE_ORDER_ID ";
	if (!empty($sort_by)) {
		$sql .= " ORDER BY ". $sort_by. " ". $sort_order;
	}
	else {
		$sql .= " ORDER BY single_order.FRIENDLY_NAME ASC";
	}
	return $sql;
}
/*
	Generate SQL statement to select all suppliers for all the general order
	Notes:
		- SELECT DISTINCT to avoid duplicates when we have the same GO linked to multiple
		  SOs that in turn have the same supplier. We don't want to show the same supplier
		  more than once in the supplier cell.
		- ORDER BY GEN_ORDER_ID to have the same General Order ID in successive rows.

*/
function generate_select_gen_order_suppliers_sql($optional_where_clause = '')
{
	$sql = "SELECT 	DISTINCT gen_order.GEN_ORDER_ID, single_order.SUPPLIER_ID, supplier.NAME AS SUPPLIER_NAME
			FROM 	gen_order INNER JOIN single_order on gen_order.GEN_ORDER_ID  =  single_order.GEN_ORDER_ID
                     	      LEFT JOIN party as supplier on single_order.SUPPLIER_ID = supplier.PARTY_ID ";
	$sql .= ($optional_where_clause =='' ? '' : $optional_where_clause);
	$sql .= " ORDER BY GEN_ORDER_ID";
	return $sql;
}


/***************************************************************************************************
	Production Management Utility Functions
***************************************************************************************************/
/*
	Get production step ID of last Phase
*/
function get_last_phase_id($single_order_id)
{
	$phase_id = -1;

	if ( 0 < $single_order_id) {
		$sql = "SELECT production_step.PRODUCTION_STEP_ID
				FROM production_step
				WHERE production_step.SINGLE_ORDER_ID = " . $single_order_id .
				" AND production_step.PARENT_ID = 0
				ORDER BY production_step.SEQ_ID DESC";
		$result = mysql_query($sql) or die(mysql_error());
		if (mysql_num_rows($result) > 0) {
			$row 	= mysql_fetch_array($result);
			$phase_id = $row['PRODUCTION_STEP_ID'];
		}
		mysql_free_result($result);
	}
	return $phase_id;
}

/*
	Get production step ID of last Step for a given Phase
*/
function get_last_step_id($single_order_id, $phase_id)
{
	$step_id = -1;

	if ((0 < $single_order_id) && (0 < $phase_id)) {
		$sql = "SELECT production_step.PRODUCTION_STEP_ID
				FROM production_step
				WHERE production_step.SINGLE_ORDER_ID = " . $single_order_id .
				" AND production_step.PARENT_ID = ".$phase_id .
			  " ORDER BY production_step.SEQ_ID DESC";
		$result = mysql_query($sql) or die(mysql_error());
		if (mysql_num_rows($result) > 0) {
			$row 	= mysql_fetch_array($result);
			$step_id = $row['PRODUCTION_STEP_ID'];
		}
		mysql_free_result($result);
	}
	return $step_id;
}


/*
	Get production step ID for the "Insert After" Dropdown.
	If the entity is a production phase, get the production step ID for the phase preceding it.
	If the entity is a production step, get the production step ID for the step preceding it.
	Parameters:
		$single_order_id - that the production entry belongs to
		$entry_seq_id - SEQ_ID for the production entry
		$parent_id - PARENT_ID for the production entry. Zero if it's a production phase.
*/
function get_preceding_entry_prod_id($single_order_id, $prod_entry_seq_id, $parent_id = 0)
{
	$entry_prod_id = -1;
	// If it's the first entry in the sequence then return zero which is the special code for
	// "At The Beginning" of the list.
	if ($prod_entry_seq_id == 1) return 0;

	if (0 < $single_order_id){
		$sql = "SELECT production_step.PRODUCTION_STEP_ID
				FROM production_step
				WHERE production_step.SINGLE_ORDER_ID = " . $single_order_id .
				" AND production_step.PARENT_ID = ". $parent_id .
				" AND production_step.SEQ_ID = " . $prod_entry_seq_id . " - 1";

		$result = mysql_query($sql) or die(mysql_error());
		if (mysql_num_rows($result) > 0) {
			$row 	= mysql_fetch_array($result);
			$entry_prod_id = $row['PRODUCTION_STEP_ID'];
		}
		mysql_free_result($result);
	}
	return $entry_prod_id;
}

/*
	Get SEQ_ID for production entry (Phase or Step)
	If prod_entry_id passed is zero return zero for SEQ_ID
*/
function get_prod_entry_seq_id($prod_entry_id)
{
	if ($prod_entry_id == 0) return 0;

	$sql = "SELECT production_step.SEQ_ID
			FROM production_step
			WHERE production_step.PRODUCTION_STEP_ID = " . $prod_entry_id ;

	$result = mysql_query($sql) or die(mysql_error());
	if (mysql_num_rows($result) > 0) {
		$row 	= mysql_fetch_array($result);
			$entry_seq_id = $row['SEQ_ID'];
	}
	mysql_free_result($result);
	return $entry_seq_id;
}

/*
	Re-sequence Production Entries (Phases or Steps)
	This function is called in any of the following scenarios:
		- a new Phase is being added or an existing Phase is being deleted
		- a new Step is being added or an existing Step is being deleted
		- a Step is being moved from Phase X to Phase Y. This means removing it from Phase X
		  and adding it to Phase Y and resequencing Steps in both Phases X and Y.
	Parameters:
		$single_order_id - self-explanatory
		$parent_id - zero for Phases and a PHASE_ID for Steps (The Parent ID)
		$prod_entry_seq_id - position at which a production entry has been either added or deleted
		$shift_direction - decides whether to move phases up or down
*/
function resequence_prod_entries($single_order_id, $parent_id, $prod_entry_seq_id, $shift_direction)
{
	$sql = "SELECT production_step.PRODUCTION_STEP_ID
			FROM production_step
			WHERE production_step.SINGLE_ORDER_ID = " . $single_order_id .
			" AND production_step.SEQ_ID  > ". $prod_entry_seq_id.
			" AND production_step.PARENT_ID = " . $parent_id.
		    " ORDER BY production_step.SEQ_ID ASC" ;
	$select_result = mysql_query($sql) or die(mysql_error());
	// Increment or Decrement SEQ_ID ?
	$new_seq_id_sql = ($shift_direction == SHIFT_UP ? 'SEQ_ID - 1' : 'SEQ_ID + 1');
	while ($row = mysql_fetch_array($select_result)) {
		$sql = "UPDATE production_step
				SET SEQ_ID = ".$new_seq_id_sql.
			  " WHERE production_step.PRODUCTION_STEP_ID = ". $row['PRODUCTION_STEP_ID'];
		$update_result = mysql_query($sql) or die(mysql_error());
	}
}

/*
	Re-sequence Phases or Steps within same Phase
	This function is called in any of the following scenarios:
		- an existing Phase is being moved
		- an existing Step is being moved within the same Phase
	Parameters:
		$single_order_id - self-explanatory
		$parent_id - zero for Phases and a PHASE_ID for Steps (The Parent ID)
		$new_seq_id - position to go to
		$current_seq_id - current position
	Logic:
		If $current_seq_id > $new_seq_id (i.e. step is being moved up within same parent)
			Then Shift down all steps below position of insertion up to original position
		If $current_seq_id < $new_seq_id (i.e. steps is being moved down within same parent)
			Then Shift up all steps below original position up to position of insertion
*/
function resequence_prod_entries_for_update($single_order_id, $parent_id, $new_seq_id, $current_seq_id)
{
	$shift_direction = ($current_seq_id > $new_seq_id ? SHIFT_DOWN : SHIFT_UP);
	$lower_boundary  = ($current_seq_id > $new_seq_id ? $new_seq_id : $current_seq_id );
	$upper_boundary  = ($current_seq_id > $new_seq_id ? $current_seq_id : $new_seq_id );

	$sql = "SELECT production_step.PRODUCTION_STEP_ID
			FROM production_step
			WHERE production_step.SINGLE_ORDER_ID = " . $single_order_id .
			" AND production_step.SEQ_ID  > ". $lower_boundary.
			" AND production_step.SEQ_ID  <= ". $upper_boundary.
			" AND production_step.PARENT_ID = " . $parent_id .
		    " ORDER BY production_step.SEQ_ID ASC" ;
	$select_result = mysql_query($sql) or die(mysql_error());
	// Increment or Decrement SEQ_ID ?
	$new_seq_id_sql = ($shift_direction == SHIFT_UP ? 'SEQ_ID - 1' : 'SEQ_ID + 1');
	while ($row = mysql_fetch_array($select_result)) {
		$sql = "UPDATE production_step
				SET SEQ_ID = ".$new_seq_id_sql.
			  " WHERE production_step.PRODUCTION_STEP_ID = ". $row['PRODUCTION_STEP_ID'];
		$update_result = mysql_query($sql) or die(mysql_error());
	}
}

/*
	Sum planned quantities of all steps within that phase
	Parameters:
		$phase_id - Phase ID to which quantities are being tallied
		$summing_scope - Summing *Start* Quantities or *End* Quantities
*/
function get_planned_phase_quantity($phase_id, $summing_scope)
{
	$quantity = 0;
	$db_field = ($summing_scope == PROCESS_START ? 'PLANNED_START_QTY': 'PLANNED_END_QTY');

	if ( 0 < $phase_id) {
		$sql = "SELECT SUM(".$db_field.") AS ".$db_field ."
				FROM production_step
				WHERE production_step.PARENT_ID = " . $phase_id;
		$result = mysql_query($sql) or die(mysql_error());
		if (mysql_num_rows($result) > 0) {
			$row 	= mysql_fetch_array($result);
			$quantity = $row[$db_field];
		}
		mysql_free_result($result);
	}
	return $quantity;
}

/*
	Sum actual quantities of all steps within that phase
	Parameters:
		$phase_id - Phase ID for which quantities are being tallied
		$summing_scope - Summing *Start* Quantities or *End* Quantities
*/
function get_actual_phase_quantity($phase_id, $summing_scope)
{
	$quantity = 0;
	$db_field = ($summing_scope == PROCESS_START ? 'ACTUAL_START_QTY': 'ACTUAL_END_QTY');

	if ( 0 < $phase_id) {
		$sql = "SELECT SUM(".$db_field.") AS ".$db_field."
				FROM production_step
				WHERE production_step.PARENT_ID = ".$phase_id;
		$result = mysql_query($sql) or die(mysql_error());
		if (mysql_num_rows($result) > 0) {
			$row 	  = mysql_fetch_array($result);
			$quantity = $row[$db_field];
		}
		mysql_free_result($result);
	}
	return $quantity;
}

/*
	Get Parent Unit Name for a production Step
	Parameters:
		$prod_entry_id - Parent Phase ID for the step or Production Step ID for the Root Step
		$unit_scope - Start Unit or End Unit
*/
function get_parent_unit_name($prod_entry_id, $unit_scope)
{
	$unit_name = '';
	$sql_field_name = ($unit_scope == PROCESS_START ? 'START_UNIT_ID' : 'END_UNIT_ID');

	if (0 < $prod_entry_id) {
		$sql = "SELECT unit.NAME
				FROM production_step LEFT JOIN unit ON production_step.".$sql_field_name." = unit.UNIT_ID
				WHERE production_step.PRODUCTION_STEP_ID = " . $prod_entry_id ;
		$result = mysql_query($sql) or die(mysql_error());
		if (mysql_num_rows($result) > 0) {
			$row 	   = mysql_fetch_array($result);
			$unit_name = $row['NAME'];
		}
		mysql_free_result($result);
	}
	return $unit_name;
}

/***************************************************************************************************
	Order Financials Utility Functions
***************************************************************************************************/

/*
	Function: get_milestone_invoice_number ()
	Desc:
		Get invoice number where the passed milestone is a line item
 	Params:
		$milestone_id
   	Returns:
		Invoice number or false if no rows were returned (i.e. the passed
		milestone is not a line item in any invoices).
*/
function get_milestone_invoice_number($milestone_id)
{
	if ($milestone_id > 0 ) {
		$sql = "SELECT invoice.NUMBER
				FROM invoice_line_item INNER JOIN  invoice ON invoice_line_item.INVOICE_ID = invoice.INVOICE_ID
				WHERE invoice_line_item.MILESTONE_ID = ".$milestone_id."
					AND invoice_line_item.STATE ='ACTIVE' AND invoice_line_item.TYPE= ".line_item_MILESTONE."
					AND invoice.STATE='ACTIVE'";
		$result = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_array($result);
		if ( mysql_num_rows($result) > 0 ) {
			return $row['NUMBER'];
		}
		else {
			return false;
		}
	}
}
/*
	Function: delete_additional_cost ( )
	Desc:
		Deletes an additional cost entry
 	Params:
		$add_cost_id
   	Returns:
		Nothing. Additional cost is deleted
*/
function delete_additional_cost($add_cost_id)
{
	if ($add_cost_id > 0 ) {
		$sql = "DELETE FROM additional_cost WHERE ADDITIONAL_COST_ID = ". $add_cost_id;
		$result = mysql_query($sql) or die(mysql_error());
	}
}
/*
	Function: generate_select_order_add_costs_sql ( )
	Desc:
		Select additional costs for order
 	Params:
		$order_id - Order ID for which we're getting the additional costs
		$order_type - General or Single ?
		$optional_where_clause - For further filtering
   	Returns:
		SQL Query string
*/
function generate_select_order_add_costs_sql($order_id, $order_type, $optional_where_clause = '')
{
	$select_sql = " SELECT 	additional_cost.ADDITIONAL_COST_ID, additional_cost.DESCRIPTION, additional_cost.AMOUNT,
							additional_cost.PAYOR_ID, payor.TYPE AS PAYOR_TYPE, payor.NAME AS PAYOR_NAME, payor.TYPE+0 AS PAYOR_TYPE_ID,
							additional_cost.PAYEE_ID, payee.TYPE AS PAYEE_TYPE, payee.NAME AS PAYEE_NAME, payee.TYPE+0 AS PAYEE_TYPE_ID
					FROM additional_cost LEFT JOIN party AS payor ON additional_cost.PAYOR_ID = payor.PARTY_ID
					LEFT JOIN party AS payee ON additional_cost.PAYEE_ID = payee.PARTY_ID ";
	if($order_type == entity_GEN_ORDER) {
		$select_sql .= "INNER JOIN gen_order ON additional_cost.GEN_ORDER_ID = gen_order.GEN_ORDER_ID
						WHERE ";
	}
	else {
		$select_sql .= "INNER JOIN single_order ON additional_cost.SINGLE_ORDER_ID = single_order.SINGLE_ORDER_ID
						WHERE ";
	}
	$select_sql .= ($optional_where_clause=='' ? '' : $optional_where_clause);
	$select_sql .= ($order_type == entity_GEN_ORDER ? " additional_cost.GEN_ORDER_ID = ".$order_id:" additional_cost.SINGLE_ORDER_ID = ".$order_id );
	$select_sql .= " AND additional_cost.BELONGS_TO = ". $order_type;
	$select_sql .= " ORDER BY additional_cost.DESCRIPTION ASC";
	return $select_sql;
}

/*
	Function: show_single_order_add_costs ( )
	Desc:
		Shows the additional costs of this single order
 	Params:
    	$order_id - ID of general or single order
		$order_type - Single or General Order
		$currency - Currency Code
   	Returns:
		HTML string
*/
function show_order_add_costs($order_id, $order_type, $currency)
{
	$sql = generate_select_order_add_costs_sql($order_id, $order_type);
	$result = mysql_query($sql) or die(mysql_error());
	$markup = '<table>';
	$markup .= "<tr class=\"con_table_header\">
				<td width=\"5%\"class=\"small_text\">
				<a href=\"javascript:
				void open_popup('add_cost_popup.php?order_id=".$order_id.
				"&order_type=".$order_type."&state=".state_NEW."',
				'additionalCost','menubar=no,toolbar=no,scrollbars=no',220,500);\">
				<img src=\"images/b.add.png\" alt=\"Add\" title=\"Add\"/></a></td>\r\n" ;
	$markup .=	"<td>Description</td>
				<td>Payor</td>
				<td>Payee</td>
				<td>Amount</td>
				</tr>";
	$total_amount = 0;
	$i = 0;
	while ($row = mysql_fetch_array($result)) {
		$pk      = $row['ADDITIONAL_COST_ID'];
		$markup .= "<tr class=\"con_table_line" .($i%2). "\">";
		$markup .= "<td class=\"small_text\">";
		$markup .= "<a href=\"javascript:
					void open_popup('add_cost_popup.php?order_id=".$order_id.
				"&order_type=".$order_type."&state=".state_EDIT."&add_cost_id=".$pk."',
					'additionalCost','menubar=no,toolbar=no,scrollbars=no',220,500);\">
					<img src=\"images/b.edit.png\" alt=\"Edit\" title=\"Edit\"/></a>
					<a href=\"javascript:void entityDelete(entityADDITIONAL_COST,". $pk .");\">
					<img src=\"images/b.delete.png\" alt=\"Delete\" title=\"Delete\"/></a>";
		$markup .= "</td>\r\n";
		$markup .= "<td>". mysql_decode($row['DESCRIPTION']) . "</td>";
		switch ($row['PAYOR_TYPE_ID']) {
			case entity_BUYER:
			case entity_AGENT:
			$ahref = '<td><a href="search_order.php?entity='.entity_GEN_ORDER.'&referrer='.$row['PAYOR_TYPE_ID'].'&referrer_id='.$row['PAYOR_ID'].'">';
			break;
			case entity_SUPPLIER:
			$ahref = '<td><a href="search_order.php?entity='.entity_SINGLE_ORDER.'&referrer='.$row['PAYOR_TYPE_ID'].'&referrer_id='.$row['PAYOR_ID'].'">';
			break;
			case entity_EXTERNAL:
			case entity_INTERNAL:
			$ahref = '<td><a href="party.php?party_id='.$row['PAYOR_ID'].'&type='.$row['PAYOR_TYPE_ID'].'&state='.state_VIEW.'">';
			break;
		}
		$markup .= $ahref;
		$markup .=  mysql_decode($row['PAYOR_TYPE'])."::".mysql_decode($row['PAYOR_NAME']). "</a></td>";
		switch ($row['PAYEE_TYPE_ID']) {
			case entity_BUYER:
			case entity_AGENT:
			$ahref = '<td><a href="search_order.php?entity='.entity_GEN_ORDER.'&referrer='.$row['PAYEE_TYPE_ID'].'&referrer_id='.$row['PAYEE_ID'].'">';
			break;
			case entity_SUPPLIER:
			$ahref = '<td><a href="search_order.php?entity='.entity_SINGLE_ORDER.'&referrer='.$row['PAYEE_TYPE_ID'].'&referrer_id='.$row['PAYEE_ID'].'">';
			break;
			case entity_EXTERNAL:
			case entity_INTERNAL:
			$ahref = '<td><a href="party.php?party_id='.$row['PAYEE_ID'].'&type='.$row['PAYEE_TYPE_ID'].'&state='.state_VIEW.'">';
			break;
		}
		$markup .= $ahref;
		$markup .=  mysql_decode($row['PAYEE_TYPE'])."::".mysql_decode($row['PAYEE_NAME'])  . "</a></td>";
		$markup .= "<td>". $currency. ' '. number_format($row['AMOUNT'], NUM_DEC_PLACES, '.',',') . "</td>";
		$total_amount +=  $row['AMOUNT'];
		++$i;
	}

	if (mysql_num_rows($result)) {
		$markup .= '<tr class="total_amount_financials">
					<td colspan="4">Total</td> ';
		$markup .= '<td>'.$currency.' '.
					number_format($total_amount, NUM_DEC_PLACES, '.', ',').'</td></tr>';
	}
	$markup .= "</table>\r\n";
	mysql_free_result($result);
	return $markup;
}

/*
	Create the additional cost entry for the payment matrix
*/
function create_additional_cost_markup($order_id, $order_type, $payor_id, $payee_id)
{
	global $total_paid, $total_received, $currency;

	$markup = '';
	$sql_where_clause = ' additional_cost.PAYOR_ID = '.$payor_id.' AND additional_cost.PAYEE_ID = '.$payee_id.' AND ';
	$sql = generate_select_order_add_costs_sql($order_id, $order_type, $sql_where_clause );
	$result = mysql_query($sql) or die(mysql_error());
	while ($row = mysql_fetch_array($result)) {
		$title    = "Additional Cost: " .$row['DESCRIPTION'];
		$amount   = $row['AMOUNT'];
		$markup   = '<span title="'.$title.'">'.$currency.' ' .$amount.'</span><br/>';
		$total_paid[$payor_id] 		+= $amount;
		$total_received[$payee_id]  += $amount;
	}
	return $markup;
}
/*
	Create header row for payment matrix
*/
function create_header_row($array_payors)
{
	$markup = '<table>
				<tr class="con_table_header">
					<td>
					<table cellpadding="0" cellspacing="0">
						<tr>
						<td></td>
						<td style="vertical-align:bottom;text-align:right;color:#000;">Payor</td>
						</tr>
						<tr>
						<td style="color:#000;">Payee</td>
						<td></td>
						</tr>
					</table>
					</td>';
	for ($i=0; $i < safe_count($array_payors); $i++){
		// Skip Agent as Payor. Agent is never Payor according to the current Business Rules
		//if ($array_payors[$i]['TYPE'] != entity_AGENT) {
			$markup .= '<td style="vertical-align:top;">'.set_str_entity_type($array_payors[$i]['TYPE']).'::'.$array_payors[$i]['NAME'].'</td>';
		//}
	}
	$markup .= '<td style="vertical-align:top;color:#000">Total Received</td></tr>';
	return $markup;

}
/*
	Create footer row for payment matrix which is the Total Paid row
*/
function create_footer_row($array_payors)
{
	global $currency, $total_paid, $total_received;

	$markup = '<tr class="con_table_header">
			  <td style="color:#000;">Total Paid</td>';
	for ($i=0; $i < safe_count($array_payors); $i++){
		// Skip Agent as Payor. Agent is never Payor according to the current Business Rules
		//if ($array_payors[$i]['TYPE'] != entity_AGENT) {
			$markup .= '<td style="color:#000;">'.$currency.' '.
						number_format($total_paid[$array_payors[$i]['PARTY_ID']], NUM_DEC_PLACES, '.', ',').'</td>';
		//}
	}
	// Display Total Paid/Total Received
	if (array_sum($total_paid) == array_sum($total_received)) {  // Sanity check
		$markup .= '<td style="color:#000;">'.$currency.' '.
						number_format(array_sum($total_paid), NUM_DEC_PLACES, '.', ',').'</td>';
	}
	return $markup;
}

?>