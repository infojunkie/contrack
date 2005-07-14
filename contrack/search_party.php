	
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
	Search parties page
	This page is used to view all different parties (Buyer, Supplier, Agent, External).
	Parameters passed: Entity ID or search term
	Possible values: entity_BUYER, entity_SUPPLIER, entity_AGENT, entity_EXTERNAL
	Notes: Only top-level parties are shown on this page(i.e. Employers). Party Contacts are 
		   not shown.
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
	global  $str_entity, $entity_id, $state, $sort_by, $prior_sort_by, $sort_order;
		
	$sort_by 	= isset($_POST['sort_by']) ? $_POST['sort_by'] : "NAME";
	$prior_sort_by 	= isset($_POST['prior_sort_by']) ? $_POST['prior_sort_by'] : "";
	$sort_order = isset($_POST['sort_order']) ? $_POST['sort_order'] : "ASC";
	
	// Get entity
	$entity_id = isset($_GET['entity']) ? $_GET['entity'] : (isset($_POST['entity']) ? $_POST['entity'] : entity_BUYER) ;
	switch ($entity_id) {
	case entity_BUYER:
	$str_entity = "Buyer";
	break;	
	case entity_SUPPLIER:
	$str_entity = "Supplier";
	break;	
	case entity_AGENT:
	$str_entity = "Agent";
	break;	
	case entity_EXTERNAL:
	$str_entity = "External";
	break;					
	}	
	$str_search_term = "";
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
	
	if (! is_authorized_view_all()) {
		do_unauthorized();
	}
}

/*
	Authorize based on which permissions are required to view the page and which permissions 
	the user has.
	A Supplier is not allowed to view other suppliers. 
	A Buyer is not allowed to view other buyers.
	An Agent is not allowed to view other agents.
*/
function is_authorized_view_all()
{
	global $allow_view_own, $allow_view_all, $allow_modify_own, $allow_modify_all, $user, $entity_id;
	
	$authorized = false;
	if ($allow_view_own || $allow_view_all || $allow_modify_all) {
		$authorized = true;
	}	
	if ( ($user->getSessionValue('group_id') == contrack_BUYER) && ( $entity_id == entity_BUYER ) ){
		$authorized = false;
	}
	if ( ($user->getSessionValue('group_id') == contrack_SUPPLIER) && ( $entity_id == entity_SUPPLIER ) ){
		$authorized = false;
	}
	if ( ($user->getSessionValue('group_id') == contrack_AGENT) && ( $entity_id == entity_AGENT ) ){
		$authorized = false;
	}
	return $authorized;
}

/*
	Determine whether the currently logged in user is associated with the entity
	being viewed.
*/
function is_associated()
{
	global $uid, $user, $party_id, $is_self ;
	
	// Get User ID
	$uid = $user->getUid();
	
	// Get associated party_id
	$assoc_party_id = get_party_id_for_user($uid);
		
	if ($assoc_party_id == $party_id) {
		$is_self = true;	
	}
	else {
		$is_self = false;
	}
	return $is_self;
}

/*
	This function is responsible for restricting the result set of the SQL query to only show 
	those entities the user is allowed to see
*/
function restrict_sql()
{

}
/*
	Function: generate_select_party_sql ( )
	Desc:  
		Selects all party attributes from party table for a given party type and employer ID
 	Params: 
    	$int_entity_id  - party type (entity_BUYER, entity_SUPPLIER, entity_AGENT, entity_EXTERNAL)
		$int_employer_id - employer ID  
   	Returns: 
		SQL Query string
	Notes: If $int_employer_id = 0 that means we're only looking for top-level parties not their contacts.
*/	
function generate_select_party_sql($int_entity_id, $int_employer_id = 0 )
{
	global $sort_by, $sort_order;
	
	$select_sql = "SELECT * FROM party WHERE TYPE=". $int_entity_id . " AND EMPLOYER_ID=" .$int_employer_id; 
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
	global $entity_id, $glb_arr_sort_indicators, $sort_by;
	
	// Passing zero for employer_id since I only want top-level parties without their contacts
	$sql = generate_select_party_sql($entity_id, 0);
	
	$result = mysql_query($sql) or die(mysql_error());	
	
	if (!mysql_num_rows($result)) {
		$markup = '	<table class="info_block">	
					<tr><td>No Matching Records Were Found</td></tr>
					</table></td></tr>';
	} else {
		$markup = '<table class="search_results_container">
				   <tr class="search_results_header"><td><strong><a href="javascript:doSort(document.frm_search_party, \'NAME\',\''.$sort_by.'\')">Name</a></strong>&nbsp;'. $glb_arr_sort_indicators['NAME'].'</td>
				   <td><strong><a href="javascript:doSort(document.frm_search_party, \'CODE\',\''.$sort_by.'\')">Code</a></strong>&nbsp;'.$glb_arr_sort_indicators['CODE'].'</td>
				   <td><strong>Contact Info</strong></td></tr>';				   
		$i=0;
		while ($row = mysql_fetch_array($result)) {
			$pk    		= $row['PARTY_ID'];
			$name  		= mysql_decode($row['NAME']);
			$country	= get_country_name($row['COUNTRY_ID']) ;
			
			$markup .= '<tr class="search_results' .($i%2). '"> <td><a href="party.php?party_id='.$pk.'&type='.$entity_id.'&state='.state_VIEW.'">'.$name.'</a></td>';						
			$markup .= '<td>'	.mysql_decode($row['CODE']).'</td>';
			$markup .= '<td>'	.mysql_decode($row['ADDRESS1']). ' ' .mysql_decode($row['ADDRESS2']). ' ' 
								.mysql_decode($row['CITY']). ' ' .    mysql_decode($row['PROVINCE']) . ' ' 
								.mysql_decode($row['POSTAL_CODE']) . ' '. $country .'</td>';						
			$markup .= '</td></tr>';
			++$i;
		}
		$markup .= '</table>';
	}
	mysql_free_result($result);
	return $markup;	
}
/*
	Generates search form header for search party page
*/
function generate_form() 
{
	global $entity_id, $sort_by, $prior_sort_by, $sort_order;
	
	$markup  = '<form action="'. $_SERVER['PHP_SELF'] . '" method="post" name="frm_search_party">' ;
	$markup .= '<input type="hidden" id="entity" name="entity" value="' . $entity_id . '"/>';
	$markup .= '<input type="hidden" id="sort_by" name="sort_by" value="' . $sort_by . '"/>';
	$markup .= '<input type="hidden" id="sort_order" name="sort_order" value="' . $sort_order . '"/>';
	$markup .= '<input type="hidden" id="prior_sort_by" name="prior_sort_by" value="' . $prior_sort_by . '"/>';	
	$markup .= '</form>';
	return $markup;
}



$page_title = "Search - " . capitalise_first_letter($str_entity) . "s"; 
require_once("body_header.php"); 
?>
<script type="text/javascript"><!--

--></script>
	<h4>SEARCH RESULTS :: 	
	<?php if (!empty($str_entity)) { 
			echo $str_entity  . "s " ;
			if (!empty($str_search_term)) { 
				echo "containing " . $str_search_term; 
			}
		  }	
	?>
	</h4>
						
<?php
	echo generate_form();
	echo show_records();
?>
					
<?php
	include("body_footer.php"); 
?>
