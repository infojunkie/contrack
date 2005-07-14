	
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
	Under Construction Page
*******************************************************************************************/ 
require_once("db_utils.php");
require_once("general_utils.php");
require_once("order_utils.php");
require_once( "patUser-2.2.3/prepend.php" );
require_once("permissions.php");

$page_title = 'Coming Soon...';
include("body_header.php"); 
?>
<link href="contrack.css" rel="stylesheet" type="text/css">

<table border="0"  cellpadding="0" cellspacing="0" style="margin-left: auto; margin-right: auto;">
	<tr>
		<td> 		
			<table border="0" class="search_results_container">				
				<tr><td style="text-align:center; ">
				<strong>Under construction</strong> <br>
				<a href="javascript:void history.back()">[Back]</a>							
				</td></tr>				
			</table>
		</td>
	</tr>
</table>		
<?php
	include("body_footer.php"); 
?>
