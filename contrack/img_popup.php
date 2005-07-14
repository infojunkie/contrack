	
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
// Get file attributes
$pic_id  = isset($_GET['pic_id']) ? $_GET['pic_id'] : -1;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Contrack - Product Pic</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="contrack.css" rel="stylesheet" type="text/css" media="all" />
</head>

<script language='javascript'>

function fitPic() 
{	
	newWidth  = window.document.images[0].width  + 10;
	newHeight = window.document.images[0].height + 30;		
	window.resizeTo( newWidth, newHeight);	
	window.moveTo(5, 5);
	self.focus();
}
</script>

<body bgcolor="#FFFFFF" onload='fitPic();' topmargin="0" marginheight="0" leftmargin="0" marginwidth="0">

<img id="prod_pic" src="get_pic.php?file_id=<?=$pic_id?>">

</body>

</html>