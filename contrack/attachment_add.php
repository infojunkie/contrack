	
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
	
	Attachment Add popup age.
	
	This page will handle adding an attachment to an entity and contains the form to be submitted
	to "do_upload.php".
	
******************************************************************************************/
require_once("db_utils.php");
require_once("general_utils.php");
require_once("patUser-2.2.3/prepend.php" );
require_once("permissions.php");

init_page();

/*
	Initialise page
	- Authenticate
	- Get params passed to the page
*/
function init_page()
{
	global $user;
	
	// Authenticate
	if (! ($user->isAuthenticated())){
		deny_access();
	}
	get_page_params();
}

/*
	Get parameters passed to the page
*/
function get_page_params()
{
	global $table_name, $row_id;
	
	// Get values
	$table_name = isset($_GET['table_name']) ? $_GET['table_name'] :  ""; 
	$row_id		= isset($_GET['row_id']) ? $_GET['row_id'] : -1;
}

// Get max file size from php.ini. currently set to 8 MB
// Strip the trailing 'M' then convert to bytes 
$max_file_size_bytes = substr(ini_get('post_max_size'), 0, -1) * 1024 * 1024;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Contrack - Add Attachment</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="contrack.css" rel="stylesheet" type="text/css" media="all" />
<link rel="stylesheet" href="mktree.css" />
</head>
<body class="body_popup">
<script src="utilities.js" type="text/javascript"></script>
<form name="frm_attach" method="post" action="do_upload.php" enctype="multipart/form-data">
<table class="popup" border ="0">
	<tr><td colspan="2"><h4>Add Attachment</h4></td></tr>
	<tr></tr>
	<input type="hidden" name="MAX_FILE_SIZE" value="<?= $max_file_size_bytes ?>"/>   
	<input type="hidden" name="table_name" value="<?= $table_name ?>"/>   
	<input type="hidden" name="row_id" value="<?= $row_id ?>"/> 
	<tr><td>File:</td><td><input class="filebrowse" type="file" name="file_name"/>
	<!-- Code to hide the standard browse button
	<span style="position: relative; top: -2.5em; left:24.8em;">
	<input type="button" class="button" value=" Browse " name="myButton" onClick="doFileBrowse('file_name');">
	</span>
	-->
	</td></tr>
	<tr><td>Description:</td><td><input class="formtext_longwidth" type="text" name="description"/></td></tr>
	<tr><td colspan="2" align="right">
	<?php
	echo generate_button("submit","Upload","");
	echo "&nbsp;";
	echo generate_button("button","Cancel","javascript:void window.close();");
	?>	
	</td></tr>
</table>
</form>
</body>
</html>