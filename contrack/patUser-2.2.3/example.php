<?PHP
/**
*	patUser example
*/
	include( "prepend.php" );
	
	// handle logout button...
	$logout	=	"no";
	if( isset( $_GET["logout"] ) )
		$logout	=	$_GET["logout"];
	elseif( isset( $HTTP_GET_VARS["logout"] ) )
		$logout	=	$HTTP_GET_VARS["logout"];
		
	if( $logout == "yes" )
	{
		$user->logOut();
	}
		
	$credentials = array("gerd", "gerd123");
	$result = $user->authenticate($credentials);
	echo "authenticate returned: " . $user->authenticate($credentials)  . "<br>";
	
	//$user->requireAuthentication( "displayLogin" );
	
	//	if submit button was pressed, store the value if the input field
	//	in the session
	$test	=	false;
	if( isset( $_POST["test"] ) )
		$test	=	$_POST["test"];
	elseif( isset( $HTTP_POST_VARS["test"] ) )
		$test	=	$HTTP_POST_VARS["test"];
	if( $test )
		$user->storeSessionValue( "test", $test );

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
	<title>patUser Example</title>
</head>

<body>

<b>Information</b><br>
This is patUser version <?php echo $user->systemVars["appVersion"] ?><br>
Please notice that patUser has switched to PEAR:DB since version 2.2.0<br>
The last versions supporting patDbc are 2.1.x - if you still need patDbc-support, download the <br>
<br>
<br>
<b>The Example</b><br>
<form method="post" action="example.php?<?=SID?>">
<table border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>Store in Session:</td>
		<td><input type="text" name="test"></td>
	</tr>
</table>
<br>
<input type="Submit">
</form>

<a href="example.php?<?php echo	SID; ?>&logout=yes">Logout user</a> and relogin.<br><br>

Go to the <a href="example2.php?<?php echo	SID; ?>">next example</a>.<br>
</body>
</html>

<?php
	$PAGE_TITLE		=	"Login";
	include( "append.php" );
?>
