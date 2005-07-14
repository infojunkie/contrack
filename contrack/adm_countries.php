	
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
	Countries Definitions page
	This page is used to view/add/edit countries in the system
*******************************************************************************************/ 
require_once("db_utils.php");
require_once("general_utils.php");
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
	- Sort 
*/
function init_page()
{
	global $user, $glb_arr_sort_indicators, $sort_by, $prior_sort_by, $sort_order;
	
	// Authenticate
	if (! ($user->isAuthenticated())){
		deny_access();
	}		
	get_page_params ();
	validate_permissions();
	check_error();
	process_state();
	init_sort_indicators();
	do_sort($sort_by, $prior_sort_by, $sort_order, $glb_arr_sort_indicators);
}

/*
	Initialize sort indicators array (up and down arrows)
*/
function init_sort_indicators()
{	
	global $glb_arr_sort_indicators;

	$glb_arr_sort_indicators['NAME'] = '';
	$glb_arr_sort_indicators['CODE'] = '';
}
/*
	Get all parameters passed to this page and format display string
*/
function get_page_params()
{
	global  $str_entity, $state, $adm_entity_op, $adm_entity_id, $state, 
			$sort_by, $prior_sort_by, $sort_order;
		
	$sort_by 		= isset($_POST['sort_by']) ? $_POST['sort_by'] : "NAME";
	$prior_sort_by 	= isset($_POST['prior_sort_by']) ? $_POST['prior_sort_by'] : "";
	$sort_order 	= isset($_POST['sort_order']) ? $_POST['sort_order'] : "ASC";
	
	$state  	    = isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : state_VIEW);
	$adm_entity_op  = isset($_GET['adm_entity_op']) ? $_GET['adm_entity_op'] : (isset($_POST['adm_entity_op']) ? $_POST['adm_entity_op'] : -1);
	$adm_entity_id 	= isset($_GET['adm_entity_id']) ? $_GET['adm_entity_id'] : (isset($_POST['adm_entity_id']) ? $_POST['adm_entity_id'] : -1);	
	$str_entity 	= 'admin';     
}

/*
	Validates that the user has the required permissions to view the page in this state	
*/
function validate_permissions()
{
	global $user;

	if (! $user->getSessionValue('allow_admin')){
		do_unauthorized();
	}
}

function process_state()
{
	global $state;
	
	// Check state
	if ( $state == state_PROCESS) {
		process_form();	
	} 
}

function process_form()
{
	global $adm_entity_op, $adm_entity_id ;
	
	if ( $adm_entity_op == opcode_DELETE ) {		
		delete_country($adm_entity_id);
		$location_url = 'Location: adm_countries.php';
		header($location_url);	
		exit;					
	}
}

function delete_country($country_id)
{
	if ($country_id > 0 ) {
		$sql = "DELETE FROM country WHERE COUNTRY_ID = ". $country_id; 
		$result = mysql_query($sql) or die(mysql_error());	
		if (!$result) return mysql_error();
	}
}

/*
	Function: generate_select_countries_sql ( )
	Desc:  
		Selects all countries
 	Params: 
    	None
   	Returns: 
		SQL Query string
*/	
function generate_select_countries_sql()
{
	global $sort_by, $sort_order;
	
	$select_sql = "	SELECT COUNTRY_ID, CODE, NAME FROM country"; 
	if (!empty($sort_by)) {
		$select_sql .= " ORDER BY ". $sort_by. " ". $sort_order;
	}
	else {
		$select_sql .= " ORDER BY NAME ASC";
	}	
	return $select_sql;
}

/*
	Function: show_records ( )
	Desc:  
		Gets records from database and outputs them in the correct format
 	Params: 
    	None
   	Returns: 
		HTML markup string
*/	
function show_records()
{
	global $glb_arr_sort_indicators, $sort_by;
	
	$sql = generate_select_countries_sql();
	$result = mysql_query($sql) or die(mysql_error());	
	
	$markup = "<table class=\"search_results_container\">					
			   <tr class=\"search_results_header\">
			   <td class=\"small_text\" width=\"3%\"><a href=\"javascript: 
				void open_popup('adm_country_popup.php?state=".state_NEW."','countryEdit',
				'menubar=no,toolbar=no,scrollbars=no',150,450);\">
				<img src=\"images/b.add.png\" alt=\"Add\" title=\"Add\"/></a></td>
			   <td><strong><a href=\"javascript:doSort(document.frm_countries,'NAME', '".$sort_by."')\">Country Name</a></strong>&nbsp;". $glb_arr_sort_indicators['NAME']."</td>
			   <td><strong><a href=\"javascript:doSort(document.frm_countries,'CODE', '".$sort_by."')\">Code</a></strong>&nbsp;". $glb_arr_sort_indicators['CODE']."</td>
			   </tr>" ;
	if ( mysql_num_rows($result) ) {
		$i=0;
		while ($row = mysql_fetch_array($result)) {
			$pk    	 = $row['COUNTRY_ID'];
			$markup .= "<tr class=\"search_results" .($i%2). "\">
						<td class=\"small_text\">
						<a href=\"javascript:void open_popup('adm_country_popup.php?country_id=".$pk ."&state=".state_EDIT. "',
						'countryEdit','menubar=no,toolbar=no,scrollbars=no',150,450);\">
						<img src=\"images/b.edit.png\" alt=\"Edit\" title=\"Edit\"/></a>
						</td>";			
			$markup .= "<td>".mysql_decode($row['NAME'])."</td>";
			$markup .= "<td>".mysql_decode($row['CODE'])."</td>";
			$markup .= "</tr>";
			++$i;
		}
		mysql_free_result($result);
	}
	$markup .= '</table>';
	return $markup;	
}

/*
	Generates search form header for search party page
*/
function generate_form() 
{
	global $sort_by, $prior_sort_by, $sort_order, $state, $adm_entity_op, $adm_entity_id;
	
	$markup  = '<form action="'. $_SERVER['PHP_SELF'] . '" method="post" name="frm_countries">' ;
	$markup .= '<input type="hidden" id="state" name="state" value="' . $state . '"/>';	
	$markup .= '<input type="hidden" id="adm_entity_op" name="adm_entity_op" value="' . $adm_entity_op . '"/>';
	$markup .= '<input type="hidden" id="adm_entity_id" name="adm_entity_id" value="' . $adm_entity_id . '"/>';	
	$markup .= '<input type="hidden" id="sort_by" name="sort_by" value="' . $sort_by . '"/>';
	$markup .= '<input type="hidden" id="sort_order" name="sort_order" value="' . $sort_order . '"/>';
	$markup .= '<input type="hidden" id="prior_sort_by" name="prior_sort_by" value="' . $prior_sort_by . '"/>';	
	$markup .= '</form>';
	return $markup;
}

$page_title = 'Country Definitions'; 
require_once("body_header.php"); 
?>
<script type="text/javascript"><!--

function resubmitForm()
{
	var frm = document.frm_countries;		
	frm.sort_by.value = 'NAME';
	frm.prior_sort_by.value = '';
	frm.submit();	
}

--></script>
	<h4>COUNTRY DEFINITIONS</h4>
						
<?php
	echo generate_form();
	echo show_records();
?>
					
<?php
	include("body_footer.php"); 
?>
