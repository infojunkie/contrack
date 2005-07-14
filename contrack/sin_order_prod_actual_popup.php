	
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
  	Production Phase/Step Actuals popup page 

	This page will handle editing/creating production actual values
	
	It has two states: EDIT and PROCESS. The latter is a transient state
	
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
	global $state, $entity_id, $entity_type;

	// Get key parameters
	$state     	 = isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : state_EDIT);	
	$entity_id 	 = isset($_GET['entity_id']) ? $_GET['entity_id'] : (isset($_POST['entity_id']) ? $_POST['entity_id'] : -1); 
	$entity_type = isset($_GET['entity_type']) ? $_GET['entity_type'] : (isset($_POST['entity_type']) ? $_POST['entity_type'] : entity_PROD_PHASE); 
}

function process_state()
{
	global $state;
	
	// Check state
	if (empty($state)) { /* if $state is empty go to EDIT state*/
		edit_form();
	} elseif ( $state == state_EDIT) {
		edit_form();
	} elseif ( $state == state_PROCESS) {
		process_form();	
	} else { 		// might be redundant but just in case... :-)
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
		Update production entries in DB
 	Params: 
    	None
   	Returns: 
		Nothing
*/	
function process_form() 
{
	global 	$state;
	
	// Get values from submitted form	
	post_values();		
	if (validate_form()) {
		$sql = generate_update_prod_entry_actuals_sql();	
		$result = mysql_query($sql) or die(mysql_error());
		if (!$result) return mysql_error();	
		$state = state_CLOSEPOPUP;		
	} else {  // validation unsuccessful
		$state = state_EDIT;			
	}
}
/*
	Intitialize page
*/	
function init_form() 
{
	global $glb_arr_values, $state, $entity_id;
	
	if ( $state != state_NEW ) {  
		$glb_arr_values['PRODUCTION_STEP_ID'] =  $entity_id;
	} 
}

/*
	Get values from submitted form and populate global values array
	SEQ_ID will be computed on insert and update from the values of PHASE_DD and STEP_DD
*/	
function post_values() 
{	
	global $glb_arr_values, $entity_id;
	
	$glb_arr_values['PRODUCTION_STEP_ID']= $entity_id;
	$glb_arr_values['STATUS']  			 = isset($_POST['STATUS']) ? $_POST['STATUS'] : prod_state_DRAFT;	
	$glb_arr_values['NAME']  			 = isset($_POST['NAME']) ? $_POST['NAME'] : '';	
	$glb_arr_values['ACTUAL_START_DATE'] = isset($_POST['ACTUAL_START_DATE']) ? $_POST['ACTUAL_START_DATE'] : '';	
	$glb_arr_values['ACTUAL_START_QTY']  = isset($_POST['ACTUAL_START_QTY']) ? $_POST['ACTUAL_START_QTY'] : 0;	
	$glb_arr_values['ACTUAL_END_DATE']   = isset($_POST['ACTUAL_END_DATE']) ? $_POST['ACTUAL_END_DATE'] : '';	
	$glb_arr_values['ACTUAL_END_QTY'] 	 = isset($_POST['ACTUAL_END_QTY']) ? $_POST['ACTUAL_END_QTY'] : 0;	
	$glb_arr_values['COMMENTS'] 		 = isset($_POST['COMMENTS']) ? $_POST['COMMENTS'] : '';	
}

/*
	Function: get_record()
	Desc:  
		Get record from DB associated with current entity
 	Params: 
    	None
   	Returns: 
		Nothing
*/
function get_record()
{
	global $glb_arr_values;

	$sql = generate_select_prod_entry_actuals_sql();	
	$result = mysql_query($sql) or die(mysql_error());	
	if (!$result) return mysql_error();
	$row = mysql_fetch_array($result);

	// Populate values array
	$glb_arr_values['PARENT_ID'] 		 = mysql_decode($row['PARENT_ID']);
	$glb_arr_values['STATUS']  			 = mysql_decode($row['STATUS']);
	$glb_arr_values['NAME'] 			 = mysql_decode($row['NAME']);
	$glb_arr_values['ACTUAL_START_DATE'] = format_date_reverse($row['ACTUAL_START_DATE']);
	$glb_arr_values['ACTUAL_START_QTY']  = mysql_decode($row['ACTUAL_START_QTY']);
	$glb_arr_values['ACTUAL_END_DATE']   = format_date_reverse($row['ACTUAL_END_DATE']);
	$glb_arr_values['ACTUAL_END_QTY'] 	 = mysql_decode($row['ACTUAL_END_QTY']);
	$glb_arr_values['COMMENTS'] 		 = mysql_decode($row['COMMENTS']);

	mysql_free_result($result);	
}

/*
	Validate form values
*/	
function validate_form() 
{
	global $glb_arr_values, $errors;
			
	return count($errors) == 0;	
}	

/**************************************** 
	SQL Generation functions
*****************************************/
/*
	Generates select SQL for  production entry
*/	
function generate_select_prod_entry_actuals_sql()
{
	global $glb_arr_values;
	
	$select_sql = " SELECT 	PARENT_ID, STATUS+0 AS STATUS, NAME, ACTUAL_START_DATE, ACTUAL_START_QTY,
							ACTUAL_END_DATE, ACTUAL_END_QTY, COMMENTS
					FROM 	production_step
					WHERE	PRODUCTION_STEP_ID= ".$glb_arr_values['PRODUCTION_STEP_ID'];
	return $select_sql;
}

/*
	Generates update SQL for production entry
*/	
	
function generate_update_prod_entry_actuals_sql()
{
	global $glb_arr_values;
	
	$update_sql =  "UPDATE production_step SET
					STATUS = " .$glb_arr_values['STATUS'].",
					NAME = '" .mysql_encode( $glb_arr_values['NAME'])."',
					ACTUAL_START_DATE = '" .format_date( $glb_arr_values['ACTUAL_START_DATE'])."',
					ACTUAL_START_QTY = " .$glb_arr_values['ACTUAL_START_QTY'].",
					ACTUAL_END_DATE = '" .format_date( $glb_arr_values['ACTUAL_END_DATE'])."',
					ACTUAL_END_QTY = " .$glb_arr_values['ACTUAL_END_QTY'].",
					COMMENTS = '".mysql_encode( $glb_arr_values['COMMENTS'])."' 
					WHERE PRODUCTION_STEP_ID = ".$glb_arr_values['PRODUCTION_STEP_ID'];
				
	return $update_sql;
}
/*
	Generates standard form header for prodcution actuals
*/
function generate_form_header() 
{
	global $state, $entity_id;
	
	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_prod_plan\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"entity_id\" name=\"entity_id\" value=\"" . $entity_id . "\"/>\r\n";
	return $str_frm_header;
}

function format_title()
{
	global $entity_type;
	
	$title = 'Edit ' . set_str_entity_type($entity_type) . ' Actual Values'; 
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

function entitySave() 
{	
	var frm = document.frm_prod_plan;		
		
	if (!validateNumeric('Actual Start Quantity',frm.ACTUAL_START_QTY.value)) return;
	if (!validateNumeric('Actual End Quantity',frm.ACTUAL_END_QTY.value)) return;	
	frm.state.value = statePROCESS;
	frm.submit();			
}

function adjustControls()
{
 	if (entityType == entityPROD_PHASE) {
		document.getElementById("StartQty").style.display = 'none';
		document.getElementById("EndQty").style.display   = 'none';
		window.resizeTo("640","340");
	}
}

window.onload = function() { adjustControls(); }
</script>
<body class="body_popup">
<?= generate_form_header() ;?>
<table class="popup">
	<tr><td colspan="2"><h4><?= format_title(); ?></h4></td></tr>
	
	<tr><td>Status:</td>
	<td><?= generate_dd_prod_entry_status_input($glb_arr_values['STATUS']);?></td></tr>
	
	<tr><td>Name:</td>
	<td>
	<?= generate_text_input ("formtext_stdwidth", "NAME", $glb_arr_values['NAME']);	?>
	</td></tr>	
	
	<tr><td>Actual Start Date:</td>
	<td>
	<?= generate_text_input ("date_box", "ACTUAL_START_DATE", format_date_reverse($glb_arr_values['ACTUAL_START_DATE'])); ?>
	</td></tr>	
	
	<tr id="StartQty"><td>Actual Start Quantity:</td>
	<td>
	<?php 
	echo generate_text_input ("short_width", "ACTUAL_START_QTY", $glb_arr_values['ACTUAL_START_QTY']);		
	echo get_parent_unit_name(($entity_type == entity_PROD_SUMMARY ? $entity_id : $glb_arr_values['PARENT_ID']), 
							  PROCESS_START);
	?>
	</td></tr>		
	
	<tr><td>Actual End Date:</td>
	<td>
	<?= generate_text_input ("date_box", "ACTUAL_END_DATE", format_date_reverse($glb_arr_values['ACTUAL_END_DATE'])); ?>
	</td></tr>	
	
	<tr id="EndQty"><td>Actual End Quantity:</td>
	<td>
	<?php
	echo generate_text_input ("short_width", "ACTUAL_END_QTY", $glb_arr_values['ACTUAL_END_QTY']);	
	echo get_parent_unit_name(($entity_type == entity_PROD_SUMMARY ? $entity_id : $glb_arr_values['PARENT_ID']),
							  PROCESS_END);;
	?>
	</td></tr>		
	
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