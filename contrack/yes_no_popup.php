	
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
	Custom Yes/No Popup 
*******************************************************************************************/

$display_msg = isset($_GET['display_msg']) ? $_GET['display_msg'] : (isset($_POST['display_msg']) ? $_POST['display_msg'] : "");	
$trigger = isset($_GET['trigger']) ? $_GET['trigger'] : (isset($_POST['trigger']) ? $_POST['trigger'] : "");	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Contrack - Confirmation</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="contrack.css" rel="stylesheet" type="text/css" media="all" />
</head>
<script src="utilities.js" type="text/javascript"></script>
<script language="JavaScript" type="text/javascript">

function notifyParent(boolSelection, trigger)
{
	window.opener.popupCallback(boolSelection, trigger);
	window.close();
}
</script>
</head>

<body class="body_popup">
<table class="popup" >
<tr><td class="info_block"> <?= $display_msg ?></td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td style="text-align:center;">
<input type="button" class="contrack_button" name="Yes" value="Yes" onclick="notifyParent(true, '<?= $trigger ?>')">
<input type="button" class="contrack_button" name="No" value="No" onclick="notifyParent(false, '<?= $trigger ?>')">
</td></tr>
</table>
</body>
</html>

