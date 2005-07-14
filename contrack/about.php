	
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
require_once("db_utils.php");
require_once("general_utils.php");
require_once( "patUser-2.2.3/prepend.php" );
require_once("permissions.php");

init_page();

function init_page()
{
	global $user;	
	// Authenticate
	if (! ($user->isAuthenticated())){
		deny_access();
	}	
	check_error();
}
$page_title = " About"; 
include("body_header.php"); 
?>
		<table border="0"  cellpadding="0" cellspacing="0">
			<tr>
				<td>
					<h4>ABOUT :: Contrack - OpenCraft Order Tracking System</h4>
				</td>
			</tr>
			<tr>
				<td>
				<div class="about">
					<table border="0" class="tab_content" style="border-top:1px solid #000">	
					<tr><td>Version:</td><td><strong>Contrack-0.5</strong></td></tr>
					<tr><td>Last update:</td><td><strong>14/07/2005</strong></td></tr>
					<tr><td>Developed By:</td>
					<td>
					<strong><a target="_blank" href="http://www.open-craft.com/">OpenCraft</a></strong> 
					<!-- <img src="images/logo.oc.jpg" style="background-color:#;"> -->
					</td></tr>
					<tr>
						<td width="15%">Technical team:</td>
						<td><strong><a href="mailto:karim.ratib@open-craft.com">Karim Ratib</a></strong> (Product management and software design)</td>
					</tr>
					<tr>
						<td></td>
						<td><strong><a href="mailto:mardini@open-craft.com">Abdel-Karim Mardini</a></strong> (Project management, software design and development)</td>
					</tr>
					<tr><td>Tools:</td>
					<td><strong><a target="_blank" href="http://www.php.net/">PHP <?= phpversion() ?></a></strong></td></tr>
					<tr><td></td><td><strong><a target="_blank" href="http://www.mysql.com/">MySQL <?= mysql_get_server_info() ?></a></strong></td>
					<tr><td></td><td><strong><a target="_blank" href="http://www.apache.org/"><?= $_SERVER['SERVER_SOFTWARE'] ?></a></strong></td></tr>
					<tr><td>Libraries and Modules:</td>
					<td><strong><a target="_blank" href="http://www.php-tools.net/site.php?&file=/patUser/overview.xml">patUser 2.2.3</a></strong></td></tr>
					<tr><td></td>
					<td><strong><a target="_blank" href="http://www.htmldog.com/articles/suckerfish/dropdowns/">Suckerfish Dropdowns</a></strong></td></tr>
					<tr><td></td>
					<td><strong><a target="_blank" href="http://www.dynarch.com/projects/calendar/">dynarch DHTML Calendar</a></strong></td></tr>
					<tr>
					<td>Icons:</td>
					<td><a target="_blank" href="http://www.egroupware.org/"><strong>eGroupWare</strong></a> 
					and <a target="_blank" href="http://www.phpmyadmin.net/"><strong>phpMyAdmin</strong></a></td>
					</tr>
					</table>
				</div> 						
			</tr>								
		</table>
<?php
	include("body_footer.php"); 
?>
