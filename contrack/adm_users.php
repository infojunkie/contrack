
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
	User Accounts Managment page
	This page is used to view/add/edit all user accounts
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

	$glb_arr_sort_indicators['USER_NAME'] 	  = '';
	$glb_arr_sort_indicators['REAL_NAME'] 	  = '';
	$glb_arr_sort_indicators['EMPLOYER_NAME'] = '';
	$glb_arr_sort_indicators['CONTACT_TYPE']  = '';
	$glb_arr_sort_indicators['GROUP_NAME'] 	  = '';
	$glb_arr_sort_indicators['EMAIL'] 		  = '';
}
/*
	Get all parameters passed to this page and format display string
*/
function get_page_params()
{
	global  $str_entity, $state, $adm_entity_op, $adm_entity_id, $state,
			$sort_by, $prior_sort_by, $sort_order;

	$sort_by 		= isset($_POST['sort_by']) ? $_POST['sort_by'] : "USER_NAME";
	$prior_sort_by 	= isset($_POST['prior_sort_by']) ? $_POST['prior_sort_by'] : "";
	$sort_order 	= isset($_POST['sort_order']) ? $_POST['sort_order'] : "ASC";

	$state   		= isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : state_VIEW);
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
		delete_user($adm_entity_id);
		delete_user_rel_group($adm_entity_id);
		$location_url = 'Location: adm_users.php';
		header($location_url);
		exit;
	}
}

function delete_user($user_id)
{
	if ($user_id > 0 ) {
		$sql = "DELETE FROM user WHERE uid = ". $user_id;
		$result = mysql_query($sql) or die(mysql_error());
		if (!$result) return mysql_error();
	}
}

function delete_user_rel_group($user_id)
{
	if ($user_id > 0 ) {
		$sql = "DELETE FROM user_rel_group WHERE uid = ". $user_id;
		$result = mysql_query($sql) or die(mysql_error());
		if (!$result) return mysql_error();
	}
}
/*
	Function: generate_select_users_sql ( )
	Desc:
		Selects all party attributes from party table for a given party type and employer ID
 	Params:
    	None
   	Returns:
		SQL Query string
*/
function generate_select_users_sql()
{
	global $sort_by, $sort_order;

	$select_sql = "	SELECT user.uid AS USER_ID,  user.username AS USER_NAME, user.party_id AS PARTY_ID,
					employee.NAME AS REAL_NAME, employee.EMPLOYER_ID, employer.NAME AS EMPLOYER_NAME,
					employee.TYPE AS CONTACT_TYPE,
					IF(employee.EMPLOYER_ID=0, employee.TYPE+0, employer.TYPE+0) AS PARTY_TYPE,
					user_rel_group.gid AS GROUP_ID, groupe.name AS GROUP_NAME, employee.EMAIL AS EMAIL
					FROM user INNER JOIN user_rel_group ON user.uid = user_rel_group.uid
					INNER JOIN groupe ON groupe.gid = user_rel_group.gid
					INNER JOIN party AS employee ON user.party_id = employee .PARTY_ID
					LEFT JOIN party AS employer ON employee.EMPLOYER_ID = employer.PARTY_ID";
	if (!empty($sort_by)) {
		$select_sql .= " ORDER BY ". $sort_by. " ". $sort_order;
	}
	else {
		$select_sql .= " ORDER BY USER_NAME ASC";
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

	$sql = generate_select_users_sql();
	$result = mysql_query($sql) or die(mysql_error());

	if (!mysql_num_rows($result)) {
		$markup = '<table><tr><td align="middle"><strong>No match found</strong></td></tr></table>';
	} else {
		$markup = "<table class=\"search_results_container\">
				   <tr class=\"search_results_header\">
				   <td class=\"small_text\" width=\"5%\"><a href=\"javascript:
					void open_popup('adm_user_popup.php?state=".state_NEW."','userEdit',
					'menubar=no,toolbar=no,scrollbars=no',265,375);\">
					<img src=\"images/b.add.png\" alt=\"Add\" title=\"Add\"/></a></td>
				   <td><strong><a href=\"javascript:doSort(document.frm_user_accounts,'USER_NAME', '".$sort_by."');\">User Name</a></strong>&nbsp;". $glb_arr_sort_indicators['USER_NAME']."</td>
				   <td><strong><a href=\"javascript:doSort(document.frm_user_accounts,'REAL_NAME', '".$sort_by."');\">Contact Name</a></strong>&nbsp;". $glb_arr_sort_indicators['REAL_NAME']."</td>
				   <td><strong><a href=\"javascript:doSort(document.frm_user_accounts,'CONTACT_TYPE', '".$sort_by."');\">Contact Type</a></strong>&nbsp;". $glb_arr_sort_indicators['CONTACT_TYPE']."</td>
				   <td><strong><a href=\"javascript:doSort(document.frm_user_accounts,'EMPLOYER_NAME', '".$sort_by."');\">Employer</a></strong>&nbsp;". $glb_arr_sort_indicators['EMPLOYER_NAME']."</td>
				   <td><strong><a href=\"javascript:doSort(document.frm_user_accounts,'GROUP_NAME', '".$sort_by."');\">Security Group</a></strong>&nbsp;". $glb_arr_sort_indicators['GROUP_NAME']."</td>
				   <td><strong><a href=\"javascript:doSort(document.frm_user_accounts,'EMAIL', '".$sort_by."');\">Email</a></strong>&nbsp;". $glb_arr_sort_indicators['EMAIL']."</td>
				   </tr>";
		$i=0;
		while ($row = mysql_fetch_array($result)) {
			$pk    		 = $row['USER_ID'];
			$employer_id = $row['EMPLOYER_ID'];
			$type_id     = $row['PARTY_TYPE'];

			$markup .= "<tr class=\"search_results" .($i%2). "\">
						<td class=\"small_text\">
						<a href=\"javascript:void open_popup('adm_user_popup.php?uid=".$pk ."&state=".state_EDIT. "',
						'userEdit','menubar=no,toolbar=no,scrollbars=no',265,375);\">
						<img src=\"images/b.edit.png\" alt=\"Edit\" title=\"Edit\"/></a>
						<a href=\"javascript:void admEntityDelete(document.frm_user_accounts, 'User Account',". $pk .");\">
				    	<img src=\"images/b.delete.png\" alt=\"Delete\" title=\"Delete\"/></a></td>";
			$markup .= "<td>".mysql_decode($row['USER_NAME'])."</td>";
			$markup .= "<td>".mysql_decode($row['REAL_NAME'])."</td>";
			$markup .= "<td>".capitalise_first_letter(mysql_decode($row['CONTACT_TYPE']))."</td>";
			$markup .= "<td><a href=\"party.php?party_id=".$employer_id."&type=".$type_id."&state=".state_VIEW."\">".mysql_decode($row['EMPLOYER_NAME'])."</a></td>";
			$markup .= "<td>".mysql_decode($row['GROUP_NAME'])."</td>";
			$markup .= "<td><a href=\"mailto:"	.$row['EMAIL'] ."\">".mysql_decode($row['EMAIL'])."</a></td>";
			$markup .= "</tr>";
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
	global $sort_by, $prior_sort_by, $sort_order, $state, $adm_entity_op, $adm_entity_id;

	$markup  = '<form action="'. $_SERVER['PHP_SELF'] . '" method="post" name="frm_user_accounts">' ;
	$markup .= '<input type="hidden" id="state" name="state" value="' . $state . '"/>';
	$markup .= '<input type="hidden" id="adm_entity_op" name="adm_entity_op" value="' . $adm_entity_op . '"/>';
	$markup .= '<input type="hidden" id="adm_entity_id" name="adm_entity_id" value="' . $adm_entity_id . '"/>';
	$markup .= '<input type="hidden" id="sort_by" name="sort_by" value="' . $sort_by . '"/>';
	$markup .= '<input type="hidden" id="sort_order" name="sort_order" value="' . $sort_order . '"/>';
	$markup .= '<input type="hidden" id="prior_sort_by" name="prior_sort_by" value="' . $prior_sort_by . '"/>';
	$markup .= '</form>';
	return $markup;
}

$page_title = 'Accounts Management';
require_once("body_header.php");
?>
<script type="text/javascript"><!--

function resubmitForm()
{
	var frm = document.frm_user_accounts;
	frm.sort_by.value = 'USER_NAME';
	frm.prior_sort_by.value = '';
	frm.submit();
}
--></script>
	<h4>ACCOUNTS MANAGEMENT</h4>

<?php
	echo generate_form();
	echo show_records();
?>

<?php
	include("body_footer.php");
?>
