	
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
	Permissions and security utilities functions
	To Do:
		Create function which checks for existence of individual user's permission (and over rides 
		the group permissions).	It should use patUser's hasPermission() one time while getting group 
		permissions and then using the same function while getting user permissions (if they exists). 
		if they do, override group permissions.
		
	Notes: modify_all is the strongest permission. If a user has modify all, then she has view_all,
		   modify_own, view_own.		   
******************************************************************************************/
require_once( "patUser-2.2.3/prepend.php" );

//  Security Groups -- Has to be synchronized with groups ID in groups table in the DB
define ("contrack_MGMT", 1);
define ("contrack_STAFF", 2);
define ("contrack_BUYER", 3);
define ("contrack_SUPPLIER", 4);
define ("contrack_AGENT", 5);
define ("contrack_SYSADMIN", 6);
define ("contrack_EXTERNAL", 7);

/*
	Check if user has admin rights			
*/
function is_admin($user)
{
	// Get user's permissions for this page
	$user_permissions = get_user_permissions ($user);	
	if (!empty($user_permissions)) {
		if (in_array("All", $user_permissions[0]["perms"])){										   
			$allow_admin = true;		
		}
		else  {
			$allow_admin = false;		
		}
	}
	else {  // if not granted Global Access Level "All"
		$user_permissions = get_user_permissions ($user, "admin");
		if (!empty($user_permissions)) {
			if (in_array("Modify_All", $user_permissions[0]["perms"])){										   
				$allow_admin = true;			
			}
			else {
				$allow_admin = false;
			}
		}
		else {
			$allow_admin = false;
		}			
	}	
	return $allow_admin;
}

/*
	Check if user has create_new rights
*/
function get_allow_create($user)
{
	// Initalise allow_create array	
	$arr_allow_create['agent'] 	      = false;
	$arr_allow_create['buyer'] 	      = false;
	$arr_allow_create['supplier'] 	  = false;
	$arr_allow_create['invoice'] 	  = false;
	$arr_allow_create['gen_order'] 	  = false;
	$arr_allow_create['single_order'] = false;
	$arr_allow_create['external'] 	  = false;
		
	// Get user's permissions for this page
	$user_permissions = get_user_permissions ($user);	
	// Check for Global Access Level "All"
	if (!empty($user_permissions)) {
		if (in_array("All", $user_permissions[0]['perms'])){
			foreach ($arr_allow_create as $key=>$value)
			{
				$arr_allow_create[$key] = true;
			}
		}
	}
	else {  // if not granted Global Access Level "All" check for a "Modify_All" permission for each 
			// of the entities
		$user_permissions = get_user_permissions ($user, "agent");
		if (!empty($user_permissions)) {
			if (in_array("Modify_All", $user_permissions[0]['perms'])){										   
				$arr_allow_create['agent'] = true;			
			}
		}
		$user_permissions = get_user_permissions ($user, "buyer");
		if (!empty($user_permissions)) {
			if (in_array("Modify_All", $user_permissions[0]['perms'])){										   
				$arr_allow_create['buyer'] = true;			
			}
		}
		$user_permissions = get_user_permissions ($user, "supplier");
		if (!empty($user_permissions)) {
			if (in_array("Modify_All", $user_permissions[0]['perms'])){										   
				$arr_allow_create['supplier'] = true;			
			}
		}
		$user_permissions = get_user_permissions ($user, "invoice");
		if (!empty($user_permissions)) {
			if (in_array("Modify_All", $user_permissions[0]['perms'])){										   
				$arr_allow_create['invoice'] = true;			
			}
		}
		$user_permissions = get_user_permissions ($user, "gen_order");
		if (!empty($user_permissions)) {
			if (in_array("Modify_All", $user_permissions[0]['perms'])){										   
				$arr_allow_create['gen_order'] = true;			
			}
		}
		$user_permissions = get_user_permissions ($user, "single_order");
		if (!empty($user_permissions)) {
			if (in_array("Modify_All", $user_permissions[0]['perms'])){										   
				$arr_allow_create['single_order'] = true;			
			}
		}
		$user_permissions = get_user_permissions ($user, "external");
		if (!empty($user_permissions)) {
			if (in_array("Modify_All", $user_permissions[0]['perms'])){										   
				$arr_allow_create['external'] = true;			
			}	
		}	
	}	
	return $arr_allow_create;
}

/*
	Check if the user has permission to at least create one of the entities
*/
function show_new($user)
{
	$arr_allow_create = get_allow_create($user);
	$show_new = false ;
	
	foreach ($arr_allow_create as $value) {
		if ($value) {
			$show_new = true ;
			break;
		}
	}
	return $show_new;
}

/*
	Get user permissions for the passed page
*/
function get_user_permissions($user, $page_name="")
{	
	// Get user's permissions for this page
	return $user->getPermissions( array( "id_type" =>"user", 
										 "id"      => $user->getUid(),
										 "part"    => $page_name )); 
}

/*
	Set access flags given the current user permissions
*/
function set_user_perms($user_permissions)
{
	global $allow_view_own, $allow_view_all, $allow_modify_own, $allow_modify_all;
				
	// Handle the special case when user has permission "All".
	if (in_array("All", $user_permissions[0]["perms"])){										   
		$allow_modify_all = true;
		$allow_modify_own = true;
		$allow_view_all   = true;
		$allow_view_own	  = true;
	}
	
	if (in_array("Modify_All", $user_permissions[0]["perms"])){										   
		$allow_modify_all = true;		
	}
	if (in_array("Modify_Own", $user_permissions[0]["perms"])){										   
		$allow_modify_own = true;		
	}
	if (in_array("View_All", $user_permissions[0]["perms"])){										   
		$allow_view_all = true;		
	}
	if (in_array("View_Own", $user_permissions[0]["perms"])){										   
		$allow_view_own = true;		
	}					   
}
	
/*
	Get minimum permissions required to view this page in this state
	Permission Logic
									Permission Required	  	
	  associated and view     	-->		view_own
  	  not associated and view 	-->		view_all
      associated and edit     	-->		modify_own
      not associated and edit 	-->		modify_all   
  	  New			  			-->		modify_all
*/
function set_required_perms ($associated)
{
	global $state, $req_view_own, $req_view_all, $req_modify_own, $req_modify_all ;
		
	if ( ($associated) and ($state == state_VIEW) ) {		
		$req_view_own = true;			
	}
	elseif ( (!$associated) and ($state == state_VIEW) ) {
		$req_view_all = true;			
	}
	elseif ( ($associated) and ($state == state_EDIT) ) {
		$req_modify_own = true;			
	}
	elseif ( (!$associated) and ($state == state_EDIT) ) {
		$req_modify_all = true;			
	}
	elseif ($state == state_NEW) {
		$req_modify_all = true;			
	}	
}

/*	
	get_user_group_id ($user)
	Desc: Function to get the group ID the user belongs to using patUser's $user object
	Parameters: $user object
	Returns: group_id
*/
function get_user_group_id($user)
{
	$arr_groups   = $user->getJoinedGroups();
	$int_group_id = $arr_groups[0]["gid"];
	return $int_group_id;
}

/*	
	get_user_group_name ($user)
	Desc: Function to get the group name the user belongs to using patUser's $user object
	Parameters: $user object
	Returns: group_name
*/
function get_user_group_name($user)
{
	$arr_groups = $user->getJoinedGroups();
	$group_name = $arr_groups[0]["name"];
	return $group_name;
}

/*	
	Authorize based on which permissions are required to view the page and which permissions 
	the user has
*/
function is_authorized()
{
	global $allow_view_own, $allow_view_all, $allow_modify_own, $allow_modify_all, 
		   $req_view_own, $req_view_all, $req_modify_own, $req_modify_all;
	
	if ($req_modify_all) {
		if ($allow_modify_all) {
			$authorized = true;
		}
		else {
			$authorized = false;
		}
	}
	elseif ($req_modify_own) {
		if ( ($allow_modify_all) || ($allow_modify_own) ) {
			$authorized = true;
		}
		else {
			$authorized = false;
		}
	}
	elseif ($req_view_all) {
		if ( ($allow_modify_all) || ($allow_modify_own)  || ($allow_view_all) ){
			$authorized = true;
		}
		else {
			$authorized = false;
		}
	}
	elseif ($req_view_own) {
		if ( ($allow_modify_all) || ($allow_modify_own)  || ($allow_view_all) || ($allow_view_own) ){
			$authorized = true;
		}
		else {
			$authorized = false;
		}
	}
	else {       //if (strcasecmp("PROCESS",$state)==0) {
		$authorized = true;
	}	
	return $authorized;
}

/*
							
*/
function format_link_new($link, $entity)
{
	global $user;
	
	$arr_allow_create = $user->getSessionValue('arr_allow_create') ;
	
	if ($arr_allow_create[$entity]) {
		$formatted_link = $link;	
	}
	else {
		$formatted_link = "#";
	}
	return $formatted_link;
}

/*
							
*/
function format_new_menu_item($link, $entity, $caption)
{
	global $user;
	
	$arr_allow_create = $user->getSessionValue('arr_allow_create') ;
	
	if ($arr_allow_create[$entity]) {
		$formatted_menu_item = "<li class =\"active_menu_link\"><a href =\"".$link."\">".$caption."</a></li>";			
	}
	else {
		$formatted_menu_item = "<li class =\"inactive_menu_link\">".$caption."</li>";
	}
	return $formatted_menu_item;
}

function format_menu_item($link, $caption)
{
	$formatted_menu_item = "<li class=\"active_menu_link\"><a href=\"". $link. "\">". $caption ."</a></li>";
	
	return $formatted_menu_item;
}

/*
	Function: deny_access ( )
	Desc:  
		Navigate to login screen and pass error message
 	Parms: 
    	none
   	Returns: 
		nothing
*/

function deny_access()
{  		
	header("Location: index.php?error=contrack_NOT_LOGGED_IN");
	exit;
}

/*
	Function: do_unauthorized ( )
	Desc:  
		Navigate to dasboard screen and pass error message
 	Parms: 
    	none
   	Returns: 
		nothing
*/
function do_unauthorized()
{  		
	header("Location: dashboard.php?error=contrack_UNAUTHORIZED_ACCESS");
	exit;
}

?>