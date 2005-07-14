	
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
/*  do_logout page
	Logs out the current user and destroys session
*/ 
require_once("db_utils.php");
require_once("general_utils.php");
require( "patUser-2.2.3/prepend.php" );

// Do Logout
contrack_logout();

function contrack_logout()
{
	global $user;
	
	$old_user = $user->getSessionValue('current_user'); // store  to test if they *were* logged in	
	destroy_contrack_session($user);
	$user->logOut(true);
	require("patUser-2.2.3/append.php");
	session_destroy();
	
	if (!empty($old_user)){
		// Navigate back to login page
		header("Location: index.php");
	}
	else {
		// if they weren't logged in but came to this page somehow
		header("Location: index.php?error=contrack_NOT_LOGGED_IN");
	}
}

function destroy_contrack_session($user)
{
	$user->clearSessionValue('current_user');
	$user->clearSessionValue('userid');
	$user->clearSessionValue('group_id');	
	$user->clearSessionValue('allow_admin');
	$user->clearSessionValue('show_menu_item_new');
	$user->clearSessionValue('arr_allow_create');	
}
?>