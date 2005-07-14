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

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
	<title>patUser Example</title>
</head>

<body>

<b>Information</b><br>
This is patUser version <?php echo $user->systemVars["appVersion"] ?><br>
Please notice that patUser now switches to PEAR:DB<br>
The last versions supporting patDbc are 2.1.x<br>
<br>
<br>
<b>The Example: modify User</b><br>
<pre>
<?php 

	// modify some data in user-table
	$result	= $user->modifyUser( array( "count_logins" => 20 ), array( "mode" => "update" ) );
	if( $result )
	{
		echo "Modify User suceeded: $result\n";
		print_r( $user->getUserData() );
	}
	
	// modify password
	if( $result )
	{	
		$result	=	$user->modifyUser( array( "passwd" => "very special secret" ), array( "mode" => "update" ) );
	}

	if( $result )
	{
		echo "Modify User suceeded: $result\n";
		print_r( $user->getUserData() );
	}
	
	// modify password with confirm-string
	if( $result )
	{	
		$result	=	$user->modifyUser( array( "passwd" => array( "secret", "secret" ) ), array( "mode" => "update" ) );
	}

	if( $result )
	{
		echo "Modify User suceeded: $result\n";
		print_r( $user->getUserData() );
	}
	
	if( !$result )
	{
		echo "some Error occured:\n";
		print_r( $user->getAllErrors() );
	}
	
?>
</pre>

<a href="example_modifyUser.php?<?php echo	SID; ?>&logout=yes">Logout user</a> and relogin.<br><br>
</body>
</html>

<?php
	$PAGE_TITLE		=	"Login";
	include( "append.php" );
?>
