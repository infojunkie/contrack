	
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
  	Production Management Page
	This page will handle production management for the single order
	
	It has the following states: 	a) "VIEW". The default state which shows all the 
									information	related to product management.
									b) "PROCESS". The form is in this state while it's
									being processed after any operation has been performed 
									on the production entries (currently the delete operation).
									The other two operations (Edit Plan, Edit Actuals), launch
									popup windows.
									The page is not viewable in this state (transient)
	
	- The top two buttons, Insert Phase and Insert Step launch pop-up dialogs which allow editing 
	  phase plans and step plans.
	- Icons next to each entry allow deleting, editing the plan and editing the actuals of the entry.
	- The planning dialogs allow changing the position of the entry in the production sequence. 
	- Repositioning an entry involve recomputing the production entries sequencing.
	
	DB Notes: - The PARENT_ID for the root step (total production) is -1 and its SEQ_ID is 0
			  - Phases have SEQ_ID with repsect to the SINGLE_ORDER_ID they belong to. Their PARENT_ID is always 0.
			  - Steps have SEQ_ID with respect to their PARENT_ID. (i.e. the phase they belong to)
******************************************************************************************/
require_once("db_utils.php");
require_once("general_utils.php");
require_once("order_utils.php");
require_once( "patUser-2.2.3/prepend.php" );
require_once("permissions.php");

init_page();
process_state();	

/****************************************************
	Page Initialisation
	- Authenticate
	- Get all parameters passed to the page
	- Validate permissions for current user
	- Check for any errors passed to the page
****************************************************/
function init_page()
{
	global $user, $phases_exist, $last_phase_id, $single_order_id;
	 	
	// Authenticate
	if (! ($user->isAuthenticated())){
		deny_access();
	}	
	get_page_params ();
	validate_permissions();
	check_error();
	$phases_exist = do_phases_exist();	
	if ($phases_exist) {
		$last_phase_id = get_last_phase_id($single_order_id);
	}
}

/*
	Get  parameters passed to this page and format display string
*/
function get_page_params()
{
	global  $state, $single_order_id, $str_entity, $sub_entity_type, $sub_entity_op, $sub_entity_id, 
			$sub_entity_seq_id, $sub_entity_parent_id, $schedule_view;

	// Get key parameters passed to the page
	$state  		   = isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : state_VIEW);
	$single_order_id   = isset($_GET['single_order_id']) ? $_GET['single_order_id'] : (isset($_POST['SINGLE_ORDER_ID']) ? $_POST['SINGLE_ORDER_ID'] : -1); 
	// Current sub entities are production entries 
	$sub_entity_op     = isset($_GET['sub_entity_op']) ? $_GET['sub_entity_op'] : (isset($_POST['sub_entity_op']) ? $_POST['sub_entity_op'] : -1);
	$sub_entity_type   = isset($_GET['sub_entity_type']) ? $_GET['sub_entity_type'] : (isset($_POST['sub_entity_type']) ? $_POST['sub_entity_type'] : -1);
	$sub_entity_id 	   = isset($_GET['sub_entity_id']) ? $_GET['sub_entity_id'] : (isset($_POST['sub_entity_id']) ? $_POST['sub_entity_id'] : -1);
	$sub_entity_seq_id = isset($_GET['sub_entity_seq_id']) ? $_GET['sub_entity_seq_id'] : (isset($_POST['sub_entity_seq_id']) ? $_POST['sub_entity_seq_id'] : -1);
	$sub_entity_parent_id = isset($_GET['sub_entity_parent_id']) ? $_GET['sub_entity_parent_id'] : (isset($_POST['sub_entity_parent_id']) ? $_POST['sub_entity_parent_id'] : -1);
	$schedule_view 	   = isset($_GET['SCHEDULE_VIEW']) ? $_GET['SCHEDULE_VIEW'] : (isset($_POST['SCHEDULE_VIEW']) ? $_POST['SCHEDULE_VIEW'] : schedule_DATES);	
	$str_entity        = 'single_order_production';
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
	//set_required_perms(is_associated());	
	
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


function process_state()
{
	global $state;

	if ( $state == state_PROCESS) {
		process_form();	
	} 
}

function process_form()
{
	global $single_order_id, $sub_entity_op, $sub_entity_id, $sub_entity_type;
	
	if ($sub_entity_op == opcode_DELETE) {		
		if ($sub_entity_type == entity_PROD_PHASE || $sub_entity_type == entity_PROD_STEP){		
			delete_production_entry($sub_entity_type, $sub_entity_id);
		}
		$location_url = "Location: single_order_prod_mgmt.php?single_order_id=".$single_order_id;
		header($location_url);	
		exit;					
	}
}
/*
	Get all production entries for that single order
	Populate one of the values array based on the entity_type parameter 
*/
function  populate_values_production_entry ($entity_type,$parent_id)
{
	global $glb_arr_total_production, $glb_arr_phases, $glb_arr_steps;
	// If $glb_arr_steps is set, empty it from its elements to get it ready for the next phase's steps
	if (isset($glb_arr_steps)) {
		while(array_pop($glb_arr_steps)){}
	}
	// Set array variable name
	switch ($entity_type){
		case entity_PROD_SUMMARY:
		$array_name = 'glb_arr_total_production';
		break;
		case entity_PROD_PHASE:
		$array_name = 'glb_arr_phases';
		break;
		case entity_PROD_STEP:
		$array_name = 'glb_arr_steps';
		break;
		default:
		$array_name = 'glb_arr_steps';
		break;
	}
	// Get and execute sql
	$sql = generate_select_production_entry_sql($parent_id);	
	$result = mysql_query($sql) or die(mysql_error());	
	
	//Populate array
	if (mysql_num_rows($result)) {
		$i = 0;
		while ($row = mysql_fetch_array($result)) {
			${$array_name}[$i]['PRODUCTION_STEP_ID']= $row['PRODUCTION_STEP_ID'];
			${$array_name}[$i]['STATUS'] 			= $row['STATUS'];
			${$array_name}[$i]['SINGLE_ORDER_ID'] 	= $row['SINGLE_ORDER_ID'];
			${$array_name}[$i]['PARENT_ID'] 		= $row['PARENT_ID'];
			${$array_name}[$i]['SEQ_ID'] 			= $row['SEQ_ID'];
			${$array_name}[$i]['PCENT_COMPLETE']	= get_percent_complete(${$array_name}[$i]['PRODUCTION_STEP_ID'], $entity_type);				
			${$array_name}[$i]['NAME'] 				= $row['NAME'];
			${$array_name}[$i]['DESCRIPTION'] 		= $row['DESCRIPTION'];
			${$array_name}[$i]['PLANNED_START_DATE']= format_date_reverse($row['PLANNED_START_DATE']);
			${$array_name}[$i]['ACTUAL_START_DATE'] = format_date_reverse($row['ACTUAL_START_DATE']);			
			${$array_name}[$i]['START_DATE_DIFF'] 	= days_diff($row['PLANNED_START_DATE'], $row['ACTUAL_START_DATE']);
			${$array_name}[$i]['PLANNED_START_QTY'] = $row['PLANNED_START_QTY'];			
			${$array_name}[$i]['ACTUAL_START_QTY'] 	= $row['ACTUAL_START_QTY'];
			$start_qty_diff = $row['ACTUAL_START_QTY'] - $row['PLANNED_START_QTY'];
			${$array_name}[$i]['START_QTY_DIFF'] 	= sign($start_qty_diff) . abs($start_qty_diff);
			${$array_name}[$i]['START_UNIT_ID'] 	= $row['START_UNIT_ID'];	
			${$array_name}[$i]['START_UNIT_NAME'] 	= $row['START_UNIT_NAME'];									
			${$array_name}[$i]['PLANNED_END_DATE'] 	= format_date_reverse($row['PLANNED_END_DATE']);			
			${$array_name}[$i]['ACTUAL_END_DATE'] 	= format_date_reverse($row['ACTUAL_END_DATE']);			
			${$array_name}[$i]['END_DATE_DIFF'] 	= days_diff($row['PLANNED_END_DATE'], $row['ACTUAL_END_DATE']);			
			${$array_name}[$i]['PLANNED_END_QTY'] 	= $row['PLANNED_END_QTY'];			
			${$array_name}[$i]['ACTUAL_END_QTY'] 	= $row['ACTUAL_END_QTY'];			
			$end_qty_diff = $row['ACTUAL_END_QTY'] - $row['PLANNED_END_QTY'];
			${$array_name}[$i]['END_QTY_DIFF'] 		= sign($end_qty_diff) . abs($end_qty_diff);
			${$array_name}[$i]['END_UNIT_ID'] 		= $row['END_UNIT_ID'];			
			${$array_name}[$i]['END_UNIT_NAME'] 	= $row['END_UNIT_NAME'];												
			${$array_name}[$i]['COMMENTS'] 			= $row['COMMENTS'];		
			$i++;
		}
	}
}

/**************************************** 
	SQL Generation functions
*****************************************/
/*
	Generate select SQL for production entries
*/
function generate_select_production_entry_sql ($parent_id)
{
	global $single_order_id;
		
	$select_sql =  "SELECT production_step.PRODUCTION_STEP_ID, production_step.STATUS, 
					production_step.SINGLE_ORDER_ID, single_order.FRIENDLY_NAME, 
					production_step.PARENT_ID, production_step.SEQ_ID, production_step.NAME,
					production_step.DESCRIPTION,production_step.PLANNED_START_DATE,
					production_step.PLANNED_START_QTY, production_step.ACTUAL_START_DATE, 
					production_step.ACTUAL_START_QTY,  production_step.START_UNIT_ID, start_unit.NAME AS START_UNIT_NAME,
					production_step.PLANNED_END_DATE, production_step.PLANNED_END_QTY,
					production_step.ACTUAL_END_DATE, production_step.ACTUAL_END_QTY,
					production_step.END_UNIT_ID, end_unit.NAME AS END_UNIT_NAME, production_step.COMMENTS
				   	FROM production_step INNER JOIN single_order ON 
					production_step.SINGLE_ORDER_ID = single_order.SINGLE_ORDER_ID
						LEFT JOIN unit AS start_unit ON production_step.START_UNIT_ID = start_unit.UNIT_ID
						LEFT JOIN unit AS end_unit ON production_step.END_UNIT_ID = end_unit.UNIT_ID
					WHERE production_step.SINGLE_ORDER_ID = " .$single_order_id. 
			  		"  AND production_step.PARENT_ID = ".$parent_id.
			      " ORDER BY PARENT_ID, SEQ_ID	";	
	return $select_sql;
}

/*
	Delete production management entry from database
*/
function delete_production_entry($entity_type, $entity_id)
{	
	if (0 < $entity_id) {	
		if ($entity_type == entity_PROD_PHASE) {
			delete_production_phase($entity_id);
		}
		else 
		if ($entity_type == entity_PROD_STEP) {
			delete_production_step($entity_id);
		}
	}
}

/*
	Delete production phase
	Delete all steps within that phase
	Re-sequence phases with respect to that single order
*/
function delete_production_phase($phase_id)
{	
	global $single_order_id, $sub_entity_seq_id;
	if (0 < $phase_id) {	
		// Delete all steps in that phase
		$sql = "DELETE FROM production_step WHERE production_step.PARENT_ID = " . $phase_id;
		$result = mysql_query($sql) or die(mysql_error());	
		// Delete phase
		$sql = "DELETE FROM production_step WHERE production_step.PRODUCTION_STEP_ID = " . $phase_id;
		$result = mysql_query($sql) or die(mysql_error());	
		//Re-sequence phases
		resequence_prod_entries($single_order_id, 0, $sub_entity_seq_id, SHIFT_UP);
	}
}

/*
	Delete production step
	Re-sequence steps with respect to containing phase
*/
function delete_production_step($step_id)
{	
	global $single_order_id, $sub_entity_parent_id, $sub_entity_seq_id;
	
	if (0 < $step_id) {	
		// Delete step
		$sql = "DELETE FROM production_step WHERE production_step.PRODUCTION_STEP_ID = " . $step_id;
		$result = mysql_query($sql) or die(mysql_error());	
		//Re-sequence steps
		$shift_up = true;
		resequence_prod_entries($single_order_id, $sub_entity_parent_id, $sub_entity_seq_id, SHIFT_UP);		
	}
}

/*
	Format dates according to schedule view value (Days or Calendar Dates)
*/
function format_prod_date($date)
{
	global $schedule_view, $glb_arr_total_production;
	
	if ($schedule_view == schedule_DATES) {
		$formatted_date = $date;
	}
	else
	if ($schedule_view == schedule_DAYS) {
		$formatted_date = days_diff(format_date($date), format_date($glb_arr_total_production[0]['PLANNED_START_DATE']), false);
	}
	return $formatted_date;
}

/*
	Format dates tooltips according to schedule view value (Days or Calendar Dates)
*/
function format_date_tooltip($date)
{
	global $schedule_view, $glb_arr_total_production;
	
	if ($schedule_view == schedule_DATES) {
		$formatted_date  = 'Days from Planned Start Date: ' ; 
		$formatted_date .= days_diff(format_date($date), format_date($glb_arr_total_production[0]['PLANNED_START_DATE']), false);
	}
	else
	if ($schedule_view == schedule_DAYS) {
		$formatted_date = 'Date: ' .$date;
	}
	return $formatted_date;

}
/*
	Check if any production phases exist for this single order's production
*/
function do_phases_exist()
{
	global $single_order_id;
	
	$sql = "SELECT production_step.PRODUCTION_STEP_ID
			FROM production_step
			WHERE production_step.SINGLE_ORDER_ID = " . $single_order_id . 
			" AND production_step.PARENT_ID = 0 ";	
	$result	   = mysql_query($sql) or die(mysql_error());	
	$existence = mysql_num_rows($result) > 0 ;
	mysql_free_result($result);	
	return $existence;
}
/**************************************** 
	XHTML Generation functions
*****************************************/
/*
	Generate the total production row (production summary)
*/
function generate_total_production_row()
{
	global $glb_arr_total_production;

	$markup = '	<tr class="production_summary">
				<td></td>';
	$markup .= '<td>
				<a href="'.generate_production_plan_popup_link($glb_arr_total_production[0]['PRODUCTION_STEP_ID'], entity_PROD_SUMMARY, -1, state_EDIT).'">
				<img src="images/b.edit.png" alt="Edit Plan" title="Edit Plan"/></a>
				<a href="'.generate_production_actual_popup_link($glb_arr_total_production[0]['PRODUCTION_STEP_ID'], entity_PROD_SUMMARY).'">
				<img src="images/b.edit.actuals.png" alt="Edit Actuals" title="Edit Actuals"/></a>
				</td>';		
	$markup .= '<td>'.$glb_arr_total_production[0]['STATUS'].'</td>
				<td>'.$glb_arr_total_production[0]['NAME'].'</td>
				<td><span title="Overall Process Completion">'.$glb_arr_total_production[0]['PCENT_COMPLETE'].'</span>
				<br/><span title="Target Production Completion">'.
				get_prod_step_pcent_complete($glb_arr_total_production[0]['PRODUCTION_STEP_ID']).'%</span></td>							
				<td title="Always displayed as date">'.$glb_arr_total_production[0]['PLANNED_START_DATE'].'</td>
				<td title="'.format_date_tooltip($glb_arr_total_production[0]['ACTUAL_START_DATE']).'">'.
							 format_prod_date($glb_arr_total_production[0]['ACTUAL_START_DATE']).'</td>
				<td>'.$glb_arr_total_production[0]['START_DATE_DIFF'].'</td>
				<td>'.format_qty($glb_arr_total_production[0]['PLANNED_START_QTY']).'</td>
				<td>'.format_qty($glb_arr_total_production[0]['ACTUAL_START_QTY']).'</td>
				<td>'.$glb_arr_total_production[0]['START_QTY_DIFF'].'</td>
				<td>'.$glb_arr_total_production[0]['START_UNIT_NAME'].'</td>
				<td title="'.format_date_tooltip($glb_arr_total_production[0]['PLANNED_END_DATE']).'">'.
							 format_prod_date($glb_arr_total_production[0]['PLANNED_END_DATE']).'</td>
				<td title="'.format_date_tooltip($glb_arr_total_production[0]['ACTUAL_END_DATE']).'">'.
							 format_prod_date($glb_arr_total_production[0]['ACTUAL_END_DATE']).'</td>
				<td>'.$glb_arr_total_production[0]['END_DATE_DIFF'].'</td>
				<td>'.format_qty($glb_arr_total_production[0]['PLANNED_END_QTY']).'</td>
				<td>'.format_qty($glb_arr_total_production[0]['ACTUAL_END_QTY']).'</td>
				<td>'.$glb_arr_total_production[0]['END_QTY_DIFF'].'</td>
				<td>'.$glb_arr_total_production[0]['END_UNIT_NAME'].'</td>
				<td class="production_note">'.$glb_arr_total_production[0]['COMMENTS'].'</td>
			</tr>';				
	return $markup;
}

/*
	Generate a phase row 
	For each phase bring back all its steps and build <tbody> section with its associated id
*/
function generate_phase_row($phase_num)
{
	global $glb_arr_phases, $glb_arr_steps, $single_order_id;
	
	// Get all steps for this phase (if any)
	populate_values_production_entry(entity_PROD_STEP, $glb_arr_phases[$phase_num]['PRODUCTION_STEP_ID']);
	$num_steps = count($glb_arr_steps);
	if ($num_steps > 0) {
		$tbody_id   = 'Phase_'.$phase_num.'_Steps';
		$td_content = '<td title="Expand/Collapse Steps">
					   <a href="#" onClick="toggle(\''.$tbody_id.'\');">
					   <img id="'.$tbody_id.'_Icon" src="images/minus.gif" alt="Expand/Collapse" title="Expand/Collapse Steps"/></a></td>';
	}
	else {
		$td_content = '<td></td>';
	}
	
	$start_planned_qty 	= format_qty(get_planned_phase_quantity($glb_arr_phases[$phase_num]['PRODUCTION_STEP_ID'], PROCESS_START));
	$start_actual_qty  	= format_qty(get_actual_phase_quantity($glb_arr_phases[$phase_num]['PRODUCTION_STEP_ID'], PROCESS_START));
	$end_planned_qty   	= format_qty(get_planned_phase_quantity($glb_arr_phases[$phase_num]['PRODUCTION_STEP_ID'], PROCESS_END));
	$end_actual_qty    	= format_qty(get_actual_phase_quantity($glb_arr_phases[$phase_num]['PRODUCTION_STEP_ID'], PROCESS_END));	
	$start_qty_diff    	= $start_actual_qty - $start_planned_qty;							
	$end_qty_diff      	= $end_actual_qty - $end_planned_qty;
	
	$markup  = '<tr class="production_phase">';
	$markup .= $td_content;
	$markup .= '<td>
				<a href="javascript:void subEntityDelete('.entity_PROD_PHASE.', '.$glb_arr_phases[$phase_num]['PRODUCTION_STEP_ID'].', '.
				$glb_arr_phases[$phase_num]['SEQ_ID'].', '.$glb_arr_phases[$phase_num]['PARENT_ID'].', \'Production Phase\');">
				<img src="images/b.delete.png" alt="Delete" title="Delete Phase"/></a>
				<a href="'.generate_production_plan_popup_link($glb_arr_phases[$phase_num]['PRODUCTION_STEP_ID'], entity_PROD_PHASE, 0, state_EDIT).'">
				<img src="images/b.edit.png" alt="Edit Plan" title="Edit Plan"/></a>
				<a href="'.generate_production_actual_popup_link($glb_arr_phases[$phase_num]['PRODUCTION_STEP_ID'], entity_PROD_PHASE).'">
				<img src="images/b.edit.actuals.png" alt="Edit Actuals" title="Edit Actuals"/></a>
				</td>';		
	$markup .= '<td>'.$glb_arr_phases[$phase_num]['STATUS'].'</td>
				<td>'.$glb_arr_phases[$phase_num]['NAME'].'</td>
				<td>'.$glb_arr_phases[$phase_num]['PCENT_COMPLETE'].'</td>							
				<td title="'.format_date_tooltip($glb_arr_phases[$phase_num]['PLANNED_START_DATE']).'">'.
							 format_prod_date($glb_arr_phases[$phase_num]['PLANNED_START_DATE']).'</td>
				<td title="'.format_date_tooltip($glb_arr_phases[$phase_num]['ACTUAL_START_DATE']).'">'.
							 format_prod_date($glb_arr_phases[$phase_num]['ACTUAL_START_DATE']).'</td>
				<td>'.$glb_arr_phases[$phase_num]['START_DATE_DIFF'].'</td>
				<td>'.$start_planned_qty.'</td>
				<td>'.$start_actual_qty.'</td>
				<td>'.sign($start_qty_diff) . abs($start_qty_diff)	.'</td>
				<td>'.$glb_arr_phases[$phase_num]['START_UNIT_NAME'].'</td>
				<td title="'.format_date_tooltip($glb_arr_phases[$phase_num]['PLANNED_END_DATE']).'">'.
							 format_prod_date($glb_arr_phases[$phase_num]['PLANNED_END_DATE']).'</td>
				<td title="'.format_date_tooltip($glb_arr_phases[$phase_num]['ACTUAL_END_DATE']).'">'.
							 format_prod_date($glb_arr_phases[$phase_num]['ACTUAL_END_DATE']).'</td>
				<td>'.$glb_arr_phases[$phase_num]['END_DATE_DIFF'].'</td>
				<td>'.$end_planned_qty.'</td>
				<td>'.$end_actual_qty.'</td>
				<td>'.sign($end_qty_diff) . abs($end_qty_diff)	.'</td>
				<td>'.$glb_arr_phases[$phase_num]['END_UNIT_NAME'].'</td>
				<td class="production_note">'.$glb_arr_phases[$phase_num]['COMMENTS'].'</td>
			    </tr>';
	// Generate steps rows within that phase (if any)
	if ($num_steps > 0 ) {
		$markup .= '<tbody id="'.$tbody_id.'">';
		for ($i=0; $i < $num_steps; $i++) {
			$markup .= generate_step_row($i, $phase_num);
		}
		$markup .= '</tbody>';
	}	
	return $markup;
}

/*
	Generate a step row 
*/
function generate_step_row($step_num, $phase_num)
{
	global $glb_arr_phases, $glb_arr_steps;
	
	$markup = '<tr class="production_leaf'.($step_num%2).'">';
	$markup .= '<td></td>';
	$markup .= '<td>
				<a href="javascript:void subEntityDelete('.entity_PROD_STEP.', '.$glb_arr_steps[$step_num]['PRODUCTION_STEP_ID'].', '.
				$glb_arr_steps[$step_num]['SEQ_ID'].', '.$glb_arr_steps[$step_num]['PARENT_ID'].', \'Production Step\');">
				<img src="images/b.delete.png" alt="Delete" title="Delete Step"/></a>
				<a href="'.generate_production_plan_popup_link(	$glb_arr_steps[$step_num]['PRODUCTION_STEP_ID'], entity_PROD_STEP, 
																$glb_arr_steps[$step_num]['PARENT_ID'], state_EDIT).'">
				<img src="images/b.edit.png" alt="Edit Plan" title="Edit Plan"/></a>
				<a href="'.generate_production_actual_popup_link($glb_arr_steps[$step_num]['PRODUCTION_STEP_ID'], entity_PROD_STEP).'">
				<img src="images/b.edit.actuals.png" alt="Edit Actuals" title="Edit Actuals"/></a>
				</td>';		
	$markup .= '<td>'.$glb_arr_steps[$step_num]['STATUS'].'</td>
				<td>'.$glb_arr_steps[$step_num]['NAME'].'</td>
				<td>'.$glb_arr_steps[$step_num]['PCENT_COMPLETE'].'</td>							
				<td title="'.format_date_tooltip($glb_arr_steps[$step_num]['PLANNED_START_DATE']).'">'.
							 format_prod_date($glb_arr_steps[$step_num]['PLANNED_START_DATE']).'</td>
				<td title="'.format_date_tooltip($glb_arr_steps[$step_num]['ACTUAL_START_DATE']).'">'.
							 format_prod_date($glb_arr_steps[$step_num]['ACTUAL_START_DATE']).'</td>
				<td>'.$glb_arr_steps[$step_num]['START_DATE_DIFF'].'</td>
				<td>'.format_qty($glb_arr_steps[$step_num]['PLANNED_START_QTY']).'</td>
				<td>'.format_qty($glb_arr_steps[$step_num]['ACTUAL_START_QTY']).'</td>
				<td>'.$glb_arr_steps[$step_num]['START_QTY_DIFF'].'</td>				
				<td>'.$glb_arr_phases[$phase_num]['START_UNIT_NAME'].'</td>				
				<td title="'.format_date_tooltip($glb_arr_steps[$step_num]['PLANNED_END_DATE']).'">'.
							 format_prod_date($glb_arr_steps[$step_num]['PLANNED_END_DATE']).'</td>
				<td title="'.format_date_tooltip($glb_arr_steps[$step_num]['ACTUAL_END_DATE']).'">'.
							 format_prod_date($glb_arr_steps[$step_num]['ACTUAL_END_DATE']).'</td>
				<td>'.$glb_arr_steps[$step_num]['END_DATE_DIFF'].'</td>
				<td>'.format_qty($glb_arr_steps[$step_num]['PLANNED_END_QTY']).'</td>
				<td>'.format_qty($glb_arr_steps[$step_num]['ACTUAL_END_QTY']).'</td>
				<td>'.$glb_arr_steps[$step_num]['END_QTY_DIFF'].'</td>
				<td>'.$glb_arr_phases[$phase_num]['END_UNIT_NAME'].'</td>				
				<td class="production_note">'.$glb_arr_steps[$step_num]['COMMENTS'].'</td>
			    </tr>';
	return $markup;
}
/*
	Function: show_records_production ( )
	Desc:  
		Gets records from database and outputs them in the correct format
 	Params: 
    	None
   	Returns: 
		HTML markup string
*/	
function show_records_production ( )
{
	global $glb_arr_phases;
		
	// Populate values array for the total production row (production summary and parent_id = -1)
	populate_values_production_entry(entity_PROD_SUMMARY,-1);	
	$markup = generate_total_production_row();
	// Get all phases (Steps in phases will be fetched from within generate_phase_row())
	populate_values_production_entry(entity_PROD_PHASE, 0);
	$num_phases = count($glb_arr_phases);
	for ($i=0; $i < $num_phases; $i++) {
		$markup .= generate_phase_row($i);
	}		
	return $markup;	
}

function generate_production_plan_popup_link($prod_entry_id, $prod_entry_type, $parent_id, $state)
{
	global $single_order_id;
	
	$markup = "javascript:void open_popup('sin_order_prod_plan_popup.php?single_order_id=".$single_order_id.
				"&entity_id=".$prod_entry_id."&entity_type=".$prod_entry_type."&parent_id=".$parent_id.
				"&state=".$state.
				"','productionPlan','menubar=no,toolbar=no,scrollbars=no',400,640);" ;
	return $markup;
}
function generate_production_actual_popup_link($prod_entry_id, $prod_entry_type)
{
	global $single_order_id;
	
	$markup = "javascript:void open_popup('sin_order_prod_actual_popup.php?entity_id=".$prod_entry_id.
				"&entity_type=".$prod_entry_type."&state=".state_EDIT.
				"','productionPlan','menubar=no,toolbar=no,scrollbars=no',340,640);" ;
	return $markup;
}


function generate_form_header() 
{
	global $state, $single_order_id, $sub_entity_op, $sub_entity_type, $sub_entity_id, 
	$sub_entity_seq_id, $sub_entity_parent_id;
	
	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_product_mgmt\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"SINGLE_ORDER_ID\" name=\"SINGLE_ORDER_ID\" value=\"" . $single_order_id . "\"/>\r\n";		
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_op\" name=\"sub_entity_op\" value=\"" . $sub_entity_op . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_type\" name=\"sub_entity_type\" value=\"" . $sub_entity_type . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_id\" name=\"sub_entity_id\" value=\"" . $sub_entity_id . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_seq_id\" name=\"sub_entity_seq_id\" value=\"" . $sub_entity_seq_id . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_parent_id\" name=\"sub_entity_parent_id\" value=\"" . $sub_entity_parent_id . "\"/>\r\n";		
	return $str_frm_header;
}

$page_title = format_sin_order_name($single_order_id) . " :: Production Management"; 
require_once("body_header.php"); 
?>
<script type="text/javascript">
var phasesExist = <?= $phases_exist ? 1 : 0 ?>;
var phasesExpanded = true;

function adjustControls()
{
	if (!phasesExist) {
		document.getElementById("INSERT_STEP").disabled = true;
		document.getElementById("INSERT_STEP").style.color = '#BBA';
	}
}

function toggle(target) 
{ 	
	var obj = (document.all) ? document.all[target] : document.getElementById(target);
	obj.style.display = (obj.style.display=='none') ? '' : 'none';	
	toggleIcon(target+'_Icon', (obj.style.display=='none'));
}

function toggleIcon(target, showPlusIcon)
{
	var img = (document.all) ? document.all[target] : document.getElementById(target);
	img.src = (showPlusIcon) ? 'images/plus.gif' : 'images/minus.gif';
}

function toggleAll()
{
	var idPattern = "Phase_.*Steps";
	var regex = new RegExp(idPattern);
	var tbodyList = document.getElementsByTagName("TBODY");
	var wa7edTbody = false;
	
	for (var i=0; i<tbodyList.length; i++) {
		var id = tbodyList[i].getAttribute("id");
		if (regex.test(id)) {
			tbodyList[i].style.display = (phasesExpanded ? 'none':'');
			toggleIcon(id+'_Icon', (tbodyList[i].style.display=='none'));
			wa7edTbody = true;
		}
	}
	if (wa7edTbody) {
		phasesExpanded = (!phasesExpanded);	
		toggleIcon('All_Phases_Icon', (!phasesExpanded));
	}	
}

function resubmitForm()
{
	frm = document.frm_product_mgmt;		
	frm.submit();
}
function subEntityDelete(entityType, intID, intSeqID, intParentID, strName) 
{
	if (confirm ('Are you sure you want to delete this ' + strName + '?') ) {
		frm = document.frm_product_mgmt;		
		frm.state.value			  		= statePROCESS;
		frm.sub_entity_op.value   		= opcodeDELETE;
		frm.sub_entity_type.value 		= entityType;
		frm.sub_entity_id.value   		= intID;	
		frm.sub_entity_seq_id.value		= intSeqID;
		frm.sub_entity_parent_id.value 	= intParentID;
		frm.submit();	
	}
}
window.onload = function() { adjustControls(); }
</script>
<table border="0"  cellpadding="0" cellspacing="0">
	<tr>
		<td colspan="3">
			<h4>
			<?= "SINGLE ORDER" ." :: ". format_sin_order_name($single_order_id); ?>
			</h4>
		</td>
	</tr>
	<tr>  <!-- Tabs -->
		<td class="tabs" colspan="3">
			<table border="0" cellspacing="0"> 
				<tr> 
				<?php 
				/*	Tab Generation 
					Pass the ordinal of the tab to be activated
				*/												
				echo generate_single_order_tabs(1, $single_order_id);																
				?>						
				</tr>
			</table> 					
		</td>
	</tr> <!-- End of Tabs -->			
	<tr>
		<td colspan = "3"> 
			<?= generate_form_header(); ?>
			<table border="0" class="tab_content">
			<tr><td>
				<table>	
					<tr class="contacts_header" >
						<td width="10%">Schedule View</td>
						<td>Actions</td>
						<td>Legend</td>
						<td style="background:#D4D0C8;"></td>
					</tr>
					<tr>
						<td>
						<?= generate_dd_schedule_view_input($schedule_view,"resubmitForm();"); ?>
						</td>
						<td width="20%">
						<?php
						echo generate_production_button('INSERT_PHASE','button','Insert Phase',generate_production_plan_popup_link(-1, entity_PROD_PHASE, 0, state_NEW));
						echo '&nbsp;';
						echo generate_production_button('INSERT_STEP','button','Insert Step',generate_production_plan_popup_link(-1, entity_PROD_STEP, $last_phase_id, state_NEW));
						?>
						</td>
						<td width="15%">
							<table cellspacing="0"><tr>
								<td width="5%" class="production_summary" style="text-align:center;"><strong>Summary</strong></td>
								<td width="5%" class="production_phase" style="text-align:center;"><strong>Phase</strong></td>
								<td width="5%" class="production_leaf0" style="text-align:center;"><strong>Step</strong></td>
							</tr></table>
						</td>
						<td></td>
					</tr>
				</table>
			</td></tr>
			<tr><td colspan="2">
				<table>
				<tr>
					<td colspan="5"></td>
					<td colspan="7" class="production_header">Production Process Starts</td>
					<td colspan="7" class="production_header">Production Process Ends</td>
					<td></td>
				</tr>
				<tr class="production_header">
					<td rowspan="2"><a href="javascript:void toggleAll();">
					<img id="All_Phases_Icon" src="images/minus.gif" alt="Expand/Collapse All " title="Expand/Collapse All Phases"/>
					</a></td>
					<td rowspan="2">Actions</td>
					<td rowspan="2">State</td>
					<td rowspan="2">Name</td>
					<td rowspan="2">%</td>							
					<td colspan="3">Day</td>							
					<td colspan="4">Quantity</td>							
					<td colspan="3">Day</td>							
					<td colspan="4">Quantity</td>							
					<td rowspan="2">Notes</td>
				</tr>
				<tr class="production_header">
					<td>Planned</td>
					<td>Actual</td>
					<td>Difference</td>
					<td>Planned</td>
					<td>Actual</td>
					<td>Difference</td>
					<td>Unit</td>
					<td>Planned</td>
					<td>Actual</td>
					<td>Difference</td>
					<td>Planned</td>
					<td>Actual</td>
					<td>Difference</td>
					<td>Unit</td> 
				</tr> 
				<?= show_records_production();?>				
			  </table>
			</td>
			</tr>
			</table>					
			</form>
	</tr>
</table>
<?php
	include("body_footer.php"); 
?>
