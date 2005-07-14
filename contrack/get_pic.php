	
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
  	Process pic download 
	This page will handle pic download requests
******************************************************************************************/
require_once("db_utils.php");
// Get file attributes
$file_id  = isset($_GET['file_id']) ? $_GET['file_id'] : -1;
$sql      = "SELECT TYPE, DATA FROM attachment WHERE ATTACHMENT_ID = " . $file_id;
$result   = mysql_query($sql) or die(mysql_error());	
$row      = mysql_fetch_array($result);
$type	  =	$row['TYPE'];
$prod_pic = $row['DATA'];
header('Cache-Control: no-store, no-cache, must-revalidate'); 
header('Content-type: '. $type);
echo $prod_pic;
?>