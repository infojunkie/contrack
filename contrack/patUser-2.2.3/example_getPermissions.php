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

	$user->requireAuthentication( "displayLogin" );
	
	//	if submit button was pressed, store the value if the input field
	//	in the session
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
<b>The Example: getPermissions</b><br>
Please choose what do you want to see:<br>
Get <a href="example_getPermissions.php?<?php echo	SID; ?>&permtype=all">all</a> permissions stored in database.<br>
Get all permissions defined for this single <a href="example_getPermissions.php?<?php echo	SID; ?>&permtype=user">user</a>.<br>
Get all permissions for all <a href="example_getPermissions.php?<?php echo	SID; ?>&permtype=group">group</a>s the current user is a memmber.<br>
Get all permissions for <a href="example_getPermissions.php?<?php echo	SID; ?>&permtype=both">both</a>, this user and groups the user belongs to.<br>
<br><br>

<b>The Result:</b><br>
<?php
	$type	=	false;
	if( isset( $_GET["permtype"] ) )
		$type	=	$_GET["permtype"];
	elseif( isset( $HTTP_GET_VARS["permtype"] ) ) 
		$type	=	$HTTP_GET_VARS["permtype"];
	
	if( $type )
	{
		$perms	=	$user->getPermissions( array(), $type );
	}
	
	if( isset( $perms ) )
	{
		if( empty( $perms ) )
			echo "There are no permissions stored in database that matches your request<br>";
		else
		{
			echo "<pre>";
			print_r( $perms );
			echo "</pre>";
		}
	}

?>
<br><br>
<a href="example_getPermissions.php?<?php echo	SID; ?>&logout=yes">Logout user</a> and relogin.<br><br>
</body>
</html>

<?php
	$PAGE_TITLE		=	"getPermissions";
	include( "append.php" );
?>
