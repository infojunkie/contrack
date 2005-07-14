
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
* $Id: configuration.php,v 1.1 2005/07/14 14:31:41 amardini Exp $
*
* This software is distributed under the GNU General Public License.
* Read the entire license text here: http://www.gnu.org/licenses/gpl.html
*/
?>
<?php
/*
	System-wide configuration parameters
*/

// DB connection parameters. 
$hostname_contrack = "yourhostname";
$database_contrack = "contrack";
$username_contrack = "username";
$password_contrack = "password";

// Company profile party_id. This should by synched up with party table in DB
define ("COMPANY_PARTY_ID", 1);

//  Set Uploads directory and converts backwards slashes to forward slashes
$upload_dir = str_replace("\\","/", getcwd().'\\'.'uploads'.'\\');

?>