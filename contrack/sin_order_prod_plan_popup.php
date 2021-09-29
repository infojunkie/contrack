
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
  	Production Phase/Step Plan popup page for adding and editing production plans

	This page will handle editing/creating  production phases and steps as well as
	re-ordering them.

	It has three states: NEW, EDIT, and PROCESS. The latter is a transient state

******************************************************************************************/
require_once("db_utils.php");
require_once("general_utils.php");
require_once("order_utils.php");
require_once("patUser-2.2.3/prepend.php" );
require_once("permissions.php");

init_page ();
process_state();

/****************************************************
	Page Initialisation
	- Authenticate
	- Get all parameters passed to the page
	- Check for any errors passed to the page
****************************************************/
function init_page()
{
	global $user;
	// Authenticate
	if (! ($user->isAuthenticated())){
		deny_access();
	}
	get_page_params ();
	check_error();
}

/*
	Get key parameters

*/
function get_page_params()
{
	global $state, $single_order_id, $entity_id, $entity_type, $parent_id, $phase_dd_value;

	// Get key parameters
	$state  		 = isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : state_EDIT);
	$single_order_id = isset($_GET['single_order_id']) ? $_GET['single_order_id'] : (isset($_POST['single_order_id']) ? $_POST['single_order_id'] : -1);
	$entity_id 		 = isset($_GET['entity_id']) ? $_GET['entity_id'] : (isset($_POST['entity_id']) ? $_POST['entity_id'] : -1);
	$entity_type     = isset($_GET['entity_type']) ? $_GET['entity_type'] : (isset($_POST['entity_type']) ? $_POST['entity_type'] : entity_PROD_PHASE);
	$parent_id 		 = isset($_GET['parent_id']) ? $_GET['parent_id'] : (isset($_POST['parent_id']) ? $_POST['parent_id'] : PROD_INVALID_PARENT_ID);
	$phase_dd_value  = isset($_GET['phase_dd_value']) ? $_GET['phase_dd_value'] : (isset($_POST['phase_dd_value']) ? $_POST['phase_dd_value'] : $parent_id);
}

function process_state()
{
	global $state;

	// Check state
	if (empty($state)) { /* if $state is empty go to EDIT state*/
		edit_form();
	} elseif ( $state == state_NEW ) {
		new_form();
	} elseif ( $state == state_EDIT) {
		edit_form();
	} elseif ( $state == state_PROCESS) {
		process_form();
	} else { 		// might be redundant but just in case...
		edit_form();
	}
}

/****************************************
	Root functions
*****************************************/
function new_form()
{
	init_form();
}

function edit_form()
{
	init_form();
	get_record();
}

/*

	Function: process_form()
	Desc:
		This function is called upon submission of the form.
		Validates values posted.
		Checks whether this is a NEW state or an EDIT state and acts accordingly.
 	Params:
    	None
   	Returns:
		Nothing
*/
function process_form()
{
	global 	$glb_arr_values, $state, $entity_type;

	// Get values from submitted form
	post_values();
	if (validate_form()) {
		if (-1 == $glb_arr_values['PRODUCTION_STEP_ID']) { 	// NEW
			insert_production_entry($entity_type);
			$state = state_CLOSEPOPUP;
		} else {  											// EDIT
			update_production_entry($entity_type);
			$state = state_CLOSEPOPUP;
		}
	} else {  // validation unsuccessful
		if (-1 != $glb_arr_values['PRODUCTION_STEP_ID']) {	// Go back to EDIT state
			$state = state_EDIT;
		} else {											// Go back to NEW state
			$state = state_NEW;
		}
	}
}
/*
	Intitialize page
*/
function init_form()
{
	global $state, $glb_arr_values, $entity_id;

	if ( $state != state_NEW ) {
		$glb_arr_values['PRODUCTION_STEP_ID'] =  $entity_id;
	}
	else {
		new_values();
	}
}
/*
	Populate global values array with defaults when the page is in 'NEW' state
*/
function new_values()
{
	global $glb_arr_values, $single_order_id, $entity_id, $entity_type, $parent_id, $phase_dd_value;

	$glb_arr_values['PRODUCTION_STEP_ID'] 	= $entity_id ;
	$glb_arr_values['SINGLE_ORDER_ID'] 		= $single_order_id;
	$glb_arr_values['PARENT_ID'] 			= $parent_id;
	$glb_arr_values['SEQ_ID'] 				= 0;
	$glb_arr_values['STATUS'] 				= prod_state_DRAFT;
	$glb_arr_values['NAME'] 				= '';
	$glb_arr_values['PLANNED_START_DATE'] 	= '';
	$glb_arr_values['PLANNED_START_QTY'] 	= 0;
	$glb_arr_values['START_UNIT_ID'] 		= 0;
	$glb_arr_values['PLANNED_END_DATE'] 	= '';
	$glb_arr_values['PLANNED_END_QTY'] 		= 0;
	$glb_arr_values['END_UNIT_ID'] 			= 0;
	$glb_arr_values['COMMENTS'] 			= '';
}

/*
	Get values from submitted form and populate global values array
	SEQ_ID will be computed on insert and update from the values of PHASE_DD and STEP_DD
*/
function post_values()
{
	global $glb_arr_values, $entity_id, $single_order_id, $parent_id;

	$glb_arr_values['PRODUCTION_STEP_ID'] = $entity_id;
	$glb_arr_values['SINGLE_ORDER_ID']    = $single_order_id;
	$glb_arr_values['PARENT_ID']  		  = $parent_id;
	$glb_arr_values['PHASE_DD']  		  = isset($_POST['PHASE_DD']) ? $_POST['PHASE_DD'] : 0;
	$glb_arr_values['STEP_DD']  		  = isset($_POST['STEP_DD']) ? $_POST['STEP_DD'] : 0;
	$glb_arr_values['STATUS']  			  = isset($_POST['STATUS']) ? $_POST['STATUS'] : prod_state_DRAFT;
	$glb_arr_values['NAME']  			  = isset($_POST['NAME']) ? $_POST['NAME'] : '';
	$glb_arr_values['PLANNED_START_DATE'] = isset($_POST['PLANNED_START_DATE']) ? $_POST['PLANNED_START_DATE'] : '';
	$glb_arr_values['PLANNED_START_QTY']  = isset($_POST['PLANNED_START_QTY']) ? $_POST['PLANNED_START_QTY'] : 0;
	$glb_arr_values['START_UNIT_ID'] 	  = isset($_POST['START_UNIT_ID']) ? $_POST['START_UNIT_ID'] : 0;
	$glb_arr_values['PLANNED_END_DATE']   = isset($_POST['PLANNED_END_DATE']) ? $_POST['PLANNED_END_DATE'] : '';
	$glb_arr_values['PLANNED_END_QTY'] 	  = isset($_POST['PLANNED_END_QTY']) ? $_POST['PLANNED_END_QTY'] : 0;
	$glb_arr_values['END_UNIT_ID'] 		  = isset($_POST['END_UNIT_ID']) ? $_POST['END_UNIT_ID'] : 0;
	$glb_arr_values['COMMENTS'] 		  = isset($_POST['COMMENTS']) ? $_POST['COMMENTS'] : '';
}


/*
	Function: get_record()
	Desc:
		If $glb_arr_values['PRODUCTION_STEP_ID'] < 1 then this is a new record. Populate with defaults.
		If $glb_arr_values['PRODUCTION_STEP_ID'] > 1 get record from database and populate values global array
 	Params:
    	None
   	Returns:
		Nothing
*/
function get_record()
{
	global $glb_arr_values, $entity_type;

	if (1 > $glb_arr_values['PRODUCTION_STEP_ID']) {
		new_values();
		return;
	}
	if ($entity_type == entity_PROD_STEP) {
		get_record_step();
	}
	else {
		get_record_production_entry();
	}
}

function get_record_production_entry()
{
	global $glb_arr_values;

	$sql = generate_select_prod_entry_sql();
	$result = mysql_query($sql) or die(mysql_error());
	if (!$result) return mysql_error();
	$row = mysql_fetch_array($result);

	// Populate values array
	$glb_arr_values['PARENT_ID']  		  = mysql_decode($row['PARENT_ID']);
	$glb_arr_values['SEQ_ID']  			  = mysql_decode($row['SEQ_ID']);
	$glb_arr_values['STATUS']  			  = mysql_decode($row['STATUS']);
	$glb_arr_values['NAME'] 			  = mysql_decode($row['NAME']);
	$glb_arr_values['PLANNED_START_DATE'] = format_date_reverse($row['PLANNED_START_DATE']);
	$glb_arr_values['PLANNED_START_QTY']  = mysql_decode($row['PLANNED_START_QTY']);
	$glb_arr_values['START_UNIT_ID'] 	  = mysql_decode($row['START_UNIT_ID']);
	$glb_arr_values['PLANNED_END_DATE']   = format_date_reverse($row['PLANNED_END_DATE']);
	$glb_arr_values['PLANNED_END_QTY'] 	  = mysql_decode($row['PLANNED_END_QTY']);
	$glb_arr_values['END_UNIT_ID'] 		  = mysql_decode($row['END_UNIT_ID']);
	$glb_arr_values['COMMENTS'] 		  = mysql_decode($row['COMMENTS']);

	mysql_free_result($result);
}
function get_record_step()
{
	global $glb_arr_values;

	$sql = generate_select_step_sql();
	$result = mysql_query($sql) or die(mysql_error());
	if (!$result) return mysql_error();
	$row = mysql_fetch_array($result);

	// Populate values array
	$glb_arr_values['PARENT_ID']  		  = mysql_decode($row['PARENT_ID']);
	$glb_arr_values['SEQ_ID']  			  = mysql_decode($row['SEQ_ID']);
	$glb_arr_values['STATUS']  			  = mysql_decode($row['STATUS']);
	$glb_arr_values['NAME'] 			  = mysql_decode($row['NAME']);
	$glb_arr_values['PLANNED_START_DATE'] = format_date_reverse($row['PLANNED_START_DATE']);
	$glb_arr_values['PLANNED_START_QTY']  = mysql_decode($row['PLANNED_START_QTY']);
	$glb_arr_values['START_UNIT'] 	 	  = mysql_decode($row['START_UNIT']);
	$glb_arr_values['PLANNED_END_DATE']   = format_date_reverse($row['PLANNED_END_DATE']);
	$glb_arr_values['PLANNED_END_QTY'] 	  = mysql_decode($row['PLANNED_END_QTY']);
	$glb_arr_values['END_UNIT'] 		  = mysql_decode($row['END_UNIT']);
	$glb_arr_values['COMMENTS'] 		  = mysql_decode($row['COMMENTS']);

	mysql_free_result($result);

}
/*
	Validate form values
*/
function validate_form()
{
	global $glb_arr_values, $errors;

	return safe_count($errors) == 0;
}


/*
	Insert a new production entry (phase or step)
*/
function insert_production_entry($entity_type)
{
	if ($entity_type == entity_PROD_PHASE) {
		insert_production_phase();
	}
	else
	if ($entity_type == entity_PROD_STEP) {
		insert_production_step();
	}
}
/*
	Insert a new production phase after $phase_seq_id
	Resequence phases then insert new phase AFTER sequence ID of passed Phase
*/
function insert_production_phase()
{
	global $glb_arr_values, $single_order_id, $entity_id;

	$dropdown_phase_seq_id = get_prod_entry_seq_id($glb_arr_values['PHASE_DD']);
	resequence_prod_entries($single_order_id, 0, $dropdown_phase_seq_id, SHIFT_DOWN);

	$sql    = generate_insert_prod_entry_sql(0, ++$dropdown_phase_seq_id);
	$result = mysql_query($sql) or die(mysql_error());
	if (!$result) return mysql_error();
	$glb_arr_values['PRODUCTION_STEP_ID'] = mysql_insert_id();
	$entity_id = $glb_arr_values['PRODUCTION_STEP_ID'];
}

/*
	Insert a new production step after $step_seq_id
	Resequence steps then insert new step AFTER sequence ID of passed step
*/
function insert_production_step()
{
	global $glb_arr_values, $single_order_id, $parent_id, $entity_id;

	$dropdown_step_seq_id = get_prod_entry_seq_id($glb_arr_values['STEP_DD']);
	resequence_prod_entries($single_order_id, $glb_arr_values['PHASE_DD'], $dropdown_step_seq_id, SHIFT_DOWN);

	$sql    = generate_insert_prod_entry_sql($glb_arr_values['PHASE_DD'], ++$dropdown_step_seq_id);
	$result = mysql_query($sql) or die(mysql_error());
	if (!$result) return mysql_error();
	$glb_arr_values['PRODUCTION_STEP_ID'] = mysql_insert_id();
	$entity_id = $glb_arr_values['PRODUCTION_STEP_ID'];
}

/*
	Update production entry (Phase or Step or Summary)
*/
function update_production_entry($entity_type)
{
	if ($entity_type == entity_PROD_PHASE) {
		update_production_phase();
	}
	else
	if ($entity_type == entity_PROD_STEP) {
		update_production_step();
	}
	else
	if ($entity_type == entity_PROD_SUMMARY) {
		update_production_summary();
	}
}
/*
	Update a  production phase.
	If Sequence ID has changed then resequence other phases then update this phase.
*/
function update_production_phase()
{
	global $glb_arr_values, $single_order_id, $entity_id;

	$dropdown_phase_seq_id = get_prod_entry_seq_id($glb_arr_values['PHASE_DD']);
	$current_phase_seq_id = get_prod_entry_seq_id($entity_id);

	if ($current_phase_seq_id - 1 != $dropdown_phase_seq_id){
		// $dropdown_phase_seq_id is the position of insertion, $current_phase_seq_id is the current position
		resequence_prod_entries_for_update($single_order_id, 0, $dropdown_phase_seq_id, $current_phase_seq_id);
		$recomputed_new_position = get_prod_entry_seq_id($glb_arr_values['PHASE_DD']) + 1 ;
		$sql = generate_update_prod_entry_sql(0, $recomputed_new_position);
	}
	else {
		$sql = generate_update_prod_entry_sql(0, $current_phase_seq_id);
	}
	$result = mysql_query($sql) or die(mysql_error());
	if (!$result) return mysql_error();
}

/*
	Update a  production step.
	If PARENT_ID or SEQ_ID has changed then resequence other steps then update this step.
*/
function update_production_step()
{
	global $glb_arr_values, $single_order_id, $parent_id, $entity_id;

	$dropdown_parent_id   = $glb_arr_values['PHASE_DD'];
	$dropdown_step_seq_id = get_prod_entry_seq_id($glb_arr_values['STEP_DD']);
	$current_step_seq_id  = get_prod_entry_seq_id($entity_id);

	// If Parent ID has changed then  shift steps up for that Parent ID and insert the step
	// withing the new new Parent ID in its new position
	if ($parent_id != $dropdown_parent_id) {
		resequence_prod_entries($single_order_id, $parent_id, $current_step_seq_id, SHIFT_UP);
		resequence_prod_entries($single_order_id, $dropdown_parent_id, $dropdown_step_seq_id, SHIFT_DOWN );
		$recomputed_new_position = get_prod_entry_seq_id($glb_arr_values['STEP_DD']) + 1;
		$sql = generate_update_prod_entry_sql($dropdown_parent_id, $recomputed_new_position);
	}
	else  // If Step Sequence ID has changed, resequence within the same Parent ID ($parent_id == $dropdown_parent_id in this case)
	if ($current_step_seq_id - 1 != $dropdown_step_seq_id){
		resequence_prod_entries_for_update($single_order_id, $parent_id, $dropdown_step_seq_id, $current_step_seq_id);
		$recomputed_new_position = get_prod_entry_seq_id($glb_arr_values['STEP_DD']) + 1;
		$sql = generate_update_prod_entry_sql($parent_id, $recomputed_new_position);
	}
	else { // Neither Phase(Parent) nor Step position has changed. Update normally
		$sql = generate_update_prod_entry_sql($parent_id, $current_step_seq_id);
	}
	$result = mysql_query($sql) or die(mysql_error());
	if (!$result) return mysql_error();
}

/*
	Update production summary
	Parent ID for production summary is always -1.
	Sequence ID for production summary is always 0.
*/
function update_production_summary()
{
	$sql = generate_update_prod_entry_sql(-1, 0);
	$result = mysql_query($sql) or die(mysql_error());
	if (!$result) return mysql_error();
}
/****************************************
	SQL Generation functions
*****************************************/
/*
	Generates select SQL for  production entry
*/
function generate_select_prod_entry_sql()
{
	global $glb_arr_values;

	$select_sql = " SELECT PARENT_ID, SEQ_ID, STATUS+0 AS STATUS, NAME, PLANNED_START_DATE,PLANNED_START_QTY,
					START_UNIT_ID, PLANNED_END_DATE, PLANNED_END_QTY, END_UNIT_ID, COMMENTS
					FROM production_step
					WHERE
					PRODUCTION_STEP_ID= ".$glb_arr_values['PRODUCTION_STEP_ID'];
	return $select_sql;
}

/*
	Generates select SQL for step
*/

function generate_select_step_sql()
{
	global $glb_arr_values;

	$select_sql = " SELECT step.PARENT_ID, step.SEQ_ID, step.STATUS+0 AS STATUS, step.NAME,
					step.PLANNED_START_DATE,step.PLANNED_START_QTY,
					start_unit.NAME AS START_UNIT, step.PLANNED_END_DATE,step. PLANNED_END_QTY,
					end_unit.name AS END_UNIT, step.COMMENTS
					FROM
					production_step AS step INNER JOIN production_step AS parent ON step.PARENT_ID = parent.PRODUCTION_STEP_ID
					LEFT JOIN unit AS start_unit ON parent.START_UNIT_ID = start_unit.UNIT_ID
					LEFT JOIN unit AS end_unit ON parent.END_UNIT_ID = end_unit.UNIT_ID
					WHERE
					step.PRODUCTION_STEP_ID= ".$glb_arr_values['PRODUCTION_STEP_ID'];
	return $select_sql;
}
/*
	Generates insert SQL for  production entry
*/
function generate_insert_prod_entry_sql($parent_id, $seq_id)
{
	global $glb_arr_values;

	$insert_sql = "INSERT INTO production_step
					(STATUS,PARENT_ID,SEQ_ID, SINGLE_ORDER_ID,NAME,PLANNED_START_DATE,PLANNED_START_QTY,
					START_UNIT_ID, PLANNED_END_DATE, PLANNED_END_QTY, END_UNIT_ID, COMMENTS )
					VALUES(
					".$glb_arr_values['STATUS'].",
					".$parent_id.",
					".$seq_id.",
					".$glb_arr_values['SINGLE_ORDER_ID'].",
					'".mysql_encode($glb_arr_values['NAME'])."',
					'".format_date($glb_arr_values['PLANNED_START_DATE'])."',
					".$glb_arr_values['PLANNED_START_QTY'].",
					".$glb_arr_values['START_UNIT_ID'].",
					'".format_date($glb_arr_values['PLANNED_END_DATE'])."',
					".$glb_arr_values['PLANNED_END_QTY'].",
					".$glb_arr_values['END_UNIT_ID'].",
					'".mysql_encode($glb_arr_values['COMMENTS'])."')";
	return $insert_sql;
}

/*
	Generates update SQL for production entry
*/

function generate_update_prod_entry_sql($parent_id, $seq_id)
{
	global $glb_arr_values;

	$update_sql =  "UPDATE production_step SET
					STATUS = " .$glb_arr_values['STATUS'].",
					PARENT_ID = " .$parent_id.",
					SEQ_ID = " .$seq_id.",
					SINGLE_ORDER_ID = " .$glb_arr_values['SINGLE_ORDER_ID'].",
					NAME = '" .mysql_encode( $glb_arr_values['NAME'])."',
					PLANNED_START_DATE = '" .format_date( $glb_arr_values['PLANNED_START_DATE'])."',
					PLANNED_START_QTY = " .$glb_arr_values['PLANNED_START_QTY'].",
					START_UNIT_ID = " .$glb_arr_values['START_UNIT_ID'].",
					PLANNED_END_DATE = '" .format_date( $glb_arr_values['PLANNED_END_DATE'])."',
					PLANNED_END_QTY = " .$glb_arr_values['PLANNED_END_QTY'].",
					END_UNIT_ID = " .$glb_arr_values['END_UNIT_ID'].",
					COMMENTS = '".mysql_encode( $glb_arr_values['COMMENTS'])."'
					WHERE PRODUCTION_STEP_ID = ".$glb_arr_values['PRODUCTION_STEP_ID'];

	return $update_sql;
}
/*
	Generates standard form header for prodcution plan
*/
function generate_form_header()
{
	global $state, $single_order_id, $entity_id, $entity_type, $parent_id, $phase_dd_value;

	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_prod_plan\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"single_order_id\" name=\"single_order_id\" value=\"" . $single_order_id . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"entity_id\" name=\"entity_id\" value=\"" . $entity_id . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"entity_type\" name=\"entity_type\" value=\"" . $entity_type . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"parent_id\" name=\"parent_id\" value=\"" . $parent_id . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"phase_dd_value\" name=\"phase_dd_value\" value=\"" . $phase_dd_value . "\"/>\r\n";
	return $str_frm_header;
}

function format_title()
{
	global $entity_type, $state;

	$title = ($state == state_NEW ? 'Insert ' : 'Edit ');
	$title .= set_str_entity_type($entity_type);
	return $title;
}
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Contrack - <?= format_title(); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript" src="calendar/calendar.js"></script>
<script type="text/javascript" src="calendar/lang/calendar-en.js"></script>
<script type="text/javascript" src="calendar/calendar-setup.js"></script>
<link href="contrack_calendar.css" rel="stylesheet" type="text/css" media="all"/>
<link href="contrack.css" rel="stylesheet" type="text/css" media="all" />
</head>
<script src="utilities.js" type="text/javascript"></script>
<script type="text/javascript"><!--
var state = <?= ($state=="") ? 0 : $state ?>;
var entityType  = <?= isset($entity_type)? $entity_type: entity_PROD_PHASE?>;


willIClose(state, window);

function adjustControls()
{
	switch (entityType) {
		case entityPROD_SUMMARY:
		document.getElementById("phases").style.display = 'none';
		document.getElementById("steps").style.display  = 'none';
		window.resizeTo("640","460");
		break;
		case entityPROD_PHASE:
		document.getElementById("steps").style.display 	  = 'none';
		document.getElementById("StartQty").style.display = 'none';
		document.getElementById("EndQty").style.display   = 'none';
		window.resizeTo("640","430");
		break;
		case entityPROD_STEP:
		document.getElementById("StartUnit").style.display = 'none';
		document.getElementById("EndUnit").style.display   = 'none';
		window.resizeTo("640","460");
		break
	}
}

function phaseChange()
{
	if (entityType == entityPROD_STEP) {
		var frm = document.forms["frm_prod_plan"];
		var Phase = frm.PHASE_DD;
		frm.phase_dd_value.value = Phase.options[Phase.selectedIndex].value;
		frm.submit();
	}
}

function entitySave()
{
	var frm = document.frm_prod_plan;

	if (!validateNumeric('Planned Start Quantity',frm.PLANNED_START_QTY.value)) return;
	if (!validateNumeric('Planned End Quantity',frm.PLANNED_END_QTY.value)) return;
	frm.state.value = statePROCESS;
	frm.submit();
}

window.onload = function() { adjustControls(); }

</script>
<body class="body_popup">
<?= generate_form_header() ;?>
<table class="popup">
	<tr><td colspan="2"><h4><?= format_title(); ?></h4></td></tr>

	<tr id="phases">
	<td><span id="PhaseDDLabel"><?= (($entity_type==entity_PROD_PHASE) ? 'Insert After Phase:' : 'Insert in Phase:');?></span></td>
	<td>
	<?php
 	if ($entity_type==entity_PROD_PHASE) {
		$phase_id = ($state==state_NEW ?
					get_last_phase_id($single_order_id) :
					get_preceding_entry_prod_id($single_order_id, $glb_arr_values['SEQ_ID'], 0));
		echo generate_dd_prod_phase_input($single_order_id, $phase_id, $entity_type, $entity_id, $state, "phaseChange();");
	}
	else {
		echo generate_dd_prod_phase_input($single_order_id, $phase_dd_value, $entity_type, $entity_id, $state, "phaseChange();");
	}

	?>
	</td></tr>

	<tr id="steps">
	<td><span id="StepDDLabel">Insert After Step:</span></td>
	<td>
	<?
	$step_id = ($state==state_NEW ?
				get_last_step_id($single_order_id, $parent_id) :
				get_preceding_entry_prod_id($single_order_id, $glb_arr_values['SEQ_ID'], $glb_arr_values['PARENT_ID']));
	echo generate_dd_prod_step_input($single_order_id, $step_id, $phase_dd_value, $entity_id, $state);
	?></td></tr>

	<tr><td>Status:</td>
	<td><?= generate_dd_prod_entry_status_input($glb_arr_values['STATUS']);?></td></tr>

	<tr><td>Name:</td>
	<td>
	<?= generate_text_input ("formtext_stdwidth", "NAME", $glb_arr_values['NAME']);	?>
	</td></tr>

	<tr><td>Planned Start Date:</td>
	<td>
	<?= generate_text_input ("date_box", "PLANNED_START_DATE", format_date_reverse($glb_arr_values['PLANNED_START_DATE'])); ?>
	</td></tr>

	<tr id="StartQty"><td>Planned Start Quantity:</td>
	<td>
	<?php
	echo generate_text_input ("short_width", "PLANNED_START_QTY", $glb_arr_values['PLANNED_START_QTY']);
	echo get_parent_unit_name($phase_dd_value, PROCESS_START);
	?>
	</td></tr>
	<tr id="StartUnit"><td>Start Unit:</td><td>
	<?=  generate_unit_input('START_UNIT_ID', $glb_arr_values['START_UNIT_ID']); ?>	</td></tr>

	<tr><td>Planned End Date:</td>
	<td>
	<?= generate_text_input ("date_box", "PLANNED_END_DATE", format_date_reverse($glb_arr_values['PLANNED_END_DATE'])); ?>
	</td></tr>

	<tr id="EndQty"><td>Planned End Quantity:</td>
	<td>
	<?php
	echo generate_text_input ("short_width", "PLANNED_END_QTY", $glb_arr_values['PLANNED_END_QTY']);
	echo get_parent_unit_name($phase_dd_value, PROCESS_END);
	?>
	</td></tr>
	<tr id="EndUnit"><td>End Unit:</td><td>
	<?=  generate_unit_input('END_UNIT_ID', $glb_arr_values['END_UNIT_ID']); ?>	</td></tr>
	<tr><td valign="top">Notes:</td>
	<td>
	<?php echo generate_textarea_input ("COMMENTS", 3, 50, $glb_arr_values["COMMENTS"]);	?>
	</td></tr>

	<tr><td colspan="2" align="right">
		  <?php
		  echo generate_button("button","Save","entitySave();");
		  echo "&nbsp;";
		  echo generate_button("button","Cancel","javascript:void window.close();");
		  ?>
	</td></tr>
</table>
</form>
</body>
</html>