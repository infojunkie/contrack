
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
/******************************************************************
	Dashboard page
	
	This page is the main hub of the application and the first page that the user 
	sees after successfully logging in.
	It presents the logged in user with a customized view depending on her 
	permissions and	data.
	Currently, it redirects to the View All General Orders page.
******************************************************************/
require_once("db_utils.php");
require_once("general_utils.php");
require_once("login.php");

// Initialise page
init_page();

function init_page()
{
	global $user;
	
	// Do Login
	contrack_login();
	// If not authenticated, exit
	if (!($user->isAuthenticated())){
		deny_access();
	}	
	check_error();
	$location_url = 'Location: search_order.php?entity='.entity_GEN_ORDER;
	header($location_url);	
	exit;					
}

$page_title = "Dashboard"; 
include("body_header.php"); 
?>



<?php
	include("body_footer.php"); 
?>
