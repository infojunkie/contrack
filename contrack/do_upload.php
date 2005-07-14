	
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
  	Process file uploads page

	This page will handle uploading files
	
	Note: MySQL's LOAD_FILE() was being used to load the content of the file but found
	      that when the system is deployed at an ISP/ASP, MySQL's File_privilege might 
		  not be granted.
		
******************************************************************************************/
require_once("db_utils.php");
require_once("general_utils.php");
require_once("patUser-2.2.3/prepend.php" );
require_once("permissions.php");

// Authenticate
if (! ($user->isAuthenticated())){
	deny_access();
}

/*
	Generate SQL needed to insert file into attachment table in DB
	Uses MySQL's load_file function. DB user needs to have file privilege.
*/
function generate_insert_sql()
{
	global $table_name, $row_id, $uploaded_file, $uploaddir, $desc, $type, $size, $full_path_name ;

	$obj_handle    = fopen( $full_path_name, "rb");
	$file_content  = fread( $obj_handle , filesize( $full_path_name));
	fclose($obj_handle);
	
	$sql = "INSERT INTO attachment (STATE, TABLE_NAME, ROW_ID, FILENAME, DESCRIPTION, TYPE, 
									SIZE, DATE_CREATED, DATA) 
			VALUES ("; 
	$sql .= mysql_encode_w_quotes ('ACTIVE') . ", " ;
	$sql .= mysql_encode_w_quotes ($table_name) . ", " ;
	$sql .= $row_id . "," ;
	$sql .= mysql_encode_w_quotes ($uploaded_file) . ", " ;
	$sql .= mysql_encode_w_quotes ($desc) . ", " ;
	$sql .= mysql_encode_w_quotes ($type) . ", " ;
	$sql .= $size .", ";
	$sql .= "NOW(), ";
	$sql .= mysql_encode_w_quotes($file_content) .")";	
	return $sql;
}

/*
	Do file upload
*/
function upload_file()
{
	global $upload_dir, $table_name, $row_id, $uploaded_file, $desc, $type, $size, $full_path_name, 
		   $tmp_name, $upload_error;	
	// Get uploaded file attributes and posted form values
	$uploaded_file 	= $_FILES['file_name']['name'];
	$full_path_name	=  $upload_dir . $uploaded_file  ; 
	$size 			= $_FILES['file_name']['size'];
	$type 			= $_FILES['file_name']['type'];
	$tmp_name 		= $_FILES['file_name']['tmp_name'];	
	$desc			= isset($_POST ['description']) ? $_POST ['description'] : "";
	$table_name		= isset($_POST ['table_name']) ? $_POST ['table_name'] : "";
	$row_id			= isset($_POST ['row_id']) ? $_POST ['row_id'] : -1;
	
	// Check to see if file was uploaded correctly and via php	
	if(move_uploaded_file($tmp_name, $full_path_name)) {	
		chmod($full_path_name, 0777);
		$sql = generate_insert_sql ();
		$result = mysql_query($sql) or die(mysql_error());	
		unlink($full_path_name);
	}
	else {
		switch ($_FILES['file_name']['error'])   {        
		   // They must specify a document
		   case (UPLOAD_ERR_NO_FILE):		   
		   $upload_error = 'You must select a document to upload. Please try again. ';
		   break;    			   
		   /// Now check the error messages
		   case (UPLOAD_ERR_INI_SIZE):
		   case (UPLOAD_ERR_FORM_SIZE): 
		   $max_upload_size = substr(ini_get('post_max_size'), 0, -1);    
		   $upload_error = 'The document you have attempted to upload is too large. 
						    Current maximum file size for uploading is ' . $max_upload_size . ' MB.';
		   break;                        		   
		   case (UPLOAD_ERR_PARTIAL):    
		   $upload_error = 'An error occured while trying to recieve the file. Please try again.';
		   default:
		   $upload_error = 'File upload failed. Please contact System Administrator.';
		   break;                                        
	   }
	}
	return $upload_error;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>File Upload</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link href="contrack.css" rel="stylesheet" type="text/css" media="all" />
</head>

<body class="body_popup">
<script type="text/javascript"><!--

function closeAndRefreshOpener() 
{
	// Refresh opener window	
	window.opener.resubmitForm();
	window.close()
}

--></script> 
<?php
	$error_msg = upload_file();
	if (! isset($error_msg)) {
?>
<table class="popup">
	<tr><td><h4>File Uploaded Successfully</h4></td></tr>
	<tr><td>&nbsp;</td></tr>	
	<tr><td>&nbsp;</td></tr>	
	<tr><td style="text-align:center;">	
	<?php
	echo generate_button("button","Done","closeAndRefreshOpener();");
	?>
	</td></tr>
</table>
<?php } else { ?>
<table class="popup">
	<tr><td><h4>File Upload Failed</h4></td></tr>	
	<tr><td><?= $error_msg ?>
	</td></tr>
	<tr><td>&nbsp;</td></tr>		
	<tr><td style="text-align:center;">	
	<?php
	echo generate_button("button","Done","javascript:window.close();");
	?>
	</td></tr>
</table>
<?php } ?>
</body>
</html>