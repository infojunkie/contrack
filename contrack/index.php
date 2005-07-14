	
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
/*
	Login page.
	
	Checks for any errors passed to it.
	Presents the user with a login form.

*/
require_once ("general_utils.php");

// Check for errors passed to the page
check_error();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Contrack - Login</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="contrack.css" rel="stylesheet" type="text/css" media="all" />
<link rel="shortcut icon" href="images/favicon.ico">
</head>
<body>

<!-- JavaScript Section -->
<script type="text/javascript"><!--
--></script>
<br>
<table cellspacing="1" cellpadding="5%" width="100%">
<tr>
	<td width="30%"></td>
	<td width="40%" class="logo"><a href="javascript: void window.open 
			('http://www.open-craft.com/','windowWebsite','toolbar=yes, location=yes,
			status=yes,menubar=yes,scrollbars=yes, resizable=yes');"><img src="images/logo.gif" alt="Organization logo"/></a></td>
	<td></td>
</tr>
<tr>
<tr>
	<td width="30%"></td>
	<td width="40%">
  		<table border="0" cellpadding="0" cellspacing="2" bgcolor="#ffffff">
    	<tr> 
      		<td style="border: 1px solid #000"> 
			<table width="50%" border="0" cellspacing="1" cellpadding="4" bgcolor="#D4D0C8">
          		<?php if (isset($error_msg)) echo "<em class=\"error_block\">" . $error_msg . "</em>" ?>
				<tr bgcolor="#000000"> 
            		<td valign="top"><font color="#D4D0C8"><b>Sign In</b></font></td>            
          		</tr>
          		<tr valign="top"> 
            	<td>
					<form name="frm_login" method="POST" action= "dashboard.php">
					<b>User Name</b><br> 
              		<input class="formtext_stdwidth" type="text" name="userid" id="userid" maxlength="25" size="25"> 
					<!-- <span class="footer">Username=admin </span> -->
					<p><b>Password</b><br>
                	<input class="formtext_stdwidth" type="password" name ="password" id="password" maxlength="25" size="25">
                	<!-- <span class="footer">Password=admin </span> -->
              		<p/> 
                	<input class="contrack_button" type="submit" name="Submit" value="Sign In" >					
            		</form>
				</td>
          		</tr>
        	</table>
			</td>
		</tr>
		<tr>
			<td class="header" style="border: 1px solid #000">
			<table class="footer"><tr><td width="50%"></td></tr>
			<tr><td>Copyright &copy; 2005, <a href="javascript: void window.open 
			('http://www.open-craft.com/','windowWebsite','toolbar=yes, location=yes,
			status=yes,menubar=yes,scrollbars=yes, resizable=yes');">OpenCraft</a></td></tr>
			<tr><td>All rights reserved.</td><td></td></tr>
			</table>
			</td>
		</tr>
		</table>
	</td>
	<td></td>
</tr>
</table>
</body>
</html>
