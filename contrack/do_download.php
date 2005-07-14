	
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
  	Process file download 

	This page will handle file download requests
	
	Notes: 	
******************************************************************************************/
require_once("db_utils.php");
require_once("general_utils.php");
require( "patUser-2.2.3/prepend.php" );

// Authenticate
if (! ($user->isAuthenticated())){
	deny_access();
}
// Get file attributes
$file_id	= isset($_GET['file_id']) ? $_GET['file_id'] : -1;

function get_record_from_db()
{
	global $file_id, $filename, $type, $size, $data ;
	
	$sql = "SELECT filename, type, size, data FROM attachment WHERE attachment_id=" . $file_id;
	$result = mysql_query($sql) or die(mysql_error());	
	$row = mysql_fetch_array($result);
	$filename = $row['filename'];
	$type	  =	$row['type'];
	$size	  =	$row['size'];
	$data 	  = $row['data'];
}

function do_download()
{
	global $filename, $type, $size, $data ;
	
	get_record_from_db();
	
	// Send headers
	header('Cache-Control: no-store, no-cache, must-revalidate'); 
	header('Content-type: '.$type ); 
   	header('Content-length: '.$size); 
   	header('Content-Disposition: attachment; filename= ' .$filename); 
   	header('Content-Description: Contrade -- Saved Attachment'); 
	header('Cache-Control: private'); 
	echo $data;
}

?>
<?php
	do_download() 	
?>



