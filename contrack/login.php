	
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
	login page

	This page is responsible for authenticating the user credentials.
	
	A user management class called patUser(v. 2.2.3) is used to help with authentication, groups 
	and permissions management. (check system documentation at: http://www.php-tools.de). 
	
	Note: if $_POST['userid'] and $_POST['password'] are not set, this page does nothing.
******************************************************************************************/
// Require necessary files
require_once("db_utils.php");
require_once("general_utils.php");
require_once( "patUser-2.2.3/prepend.php" );
require_once("permissions.php");

function contrack_login()
{
	global $user;
	
	if (isset($_POST['userid']) && isset($_POST['password'])){
		// if the user has just tried to log in
		$username 	 = $_POST['userid'];
		$password 	 = $_POST['password'];						
		$credentials = array( "username" => $username, "passwd" =>  $password);	
		if ($user->authenticate($credentials))	{		
			// if authenticated, store username in session var  
			$user->storeSessionValue('current_user', $username);
			// store user_id in session var
			$user->storeSessionValue('userid', $user->getUid());
			// store group_id in session var
			$user->storeSessionValue('group_id', get_user_group_id($user));			
			// store group_name in session var
			$user->storeSessionValue('group_name', get_user_group_name($user));			
			// store allow_admin session var
			$user->storeSessionValue('allow_admin', is_admin($user));		
			// store show_new in session var
			$user->storeSessionValue('show_menu_item_new', show_new($user));
			// get array of permissions to create new entities and store them in session vars			
			$user->storeSessionValue('arr_allow_create', get_allow_create($user));						
		}  
		else { // unsuccessful login	  
			// Navigate back to login screen while passing an error code
			header("Location: index.php?error=contrack_LOGIN_FAILED");	
			exit;  	  	
		}      
	}
}
?>
