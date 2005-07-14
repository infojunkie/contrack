<?PHP
/**
 *	class authHandler::testHandler
 */
class	testHandler
{

/**
 *	patUserGetAuthData
 *
 *	this function is required. 
 */
function	patUserGetAuthData()
{
	$pwd	=	"";
	if( isset( $_GET["passwd"] ) )
		$pwd	=	$_GET["passwd"];

	$authData	=	array( "username" => "gerd", "passwd" => $pwd );
	return $authData;
}

/**
 *	patUserRealm
 *
 *	This function is optional.
 */
function	patUserSetRealm( $realm )
{
	echo "setRealm: $realm<br>";
}

/**
 *	patUserSetUid
 *
 *	this function is optional
 */
function	patUserSetUid( $uid )
{
	echo "setUid: $uid<br>";
}

/**
 *	patUserSetErrors
 *
 *	this function is optional
 */
function	patUserSetErrors( $errors )
{
	echo "Some Errors occoured during authentication - use get-parameter \"passwd=secret\" to login";

	echo "<pre>";
	print_r(	$errors);
	echo "</pre>";
}

}


/**
*	patUser example
*/
	include( "prepend.php" );
	
	$logout	=	"no";
	if( isset( $_GET["logout"] ) )
		$logout	=	$_GET["logout"];
	elseif( isset( $HTTP_GET_VARS["logout"] ) )
		$logout	=	$HTTP_GET_VARS["logout"];
		
	if( $logout == "yes" )
	{
		$user->logOut();
	}
	
	$authHandler	=&	new testHandler();
	$user->setAuthHandler( $authHandler );

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
<b>The Example: authHandler</b><br>
This example uses the new feature of callback-object that provide authentication data. 
In this example the authHandler is very simple - have a look a the source code.<br>

<pre>
<?php 

	$user->requireAuthentication( "callAuthHandler" );

	print_r( $user->getUserData() );
?>
</pre>

<br>
<a href="example_authHandler.php?<?php echo	SID; ?>&logout=yes">Logout user</a> and relogin.<br><br>

Idea and implementation adopted from Paul Baranowski &lt;paul@paulbaranowski.org&gt;<br>
</body>
</html>
<?php
	$PAGE_TITLE		=	"Login";
	include( "append.php" );
?>
