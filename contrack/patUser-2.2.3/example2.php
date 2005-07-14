<?PHP
/**
*	patUser example
*/
	include( "prepend.php" );
	$user->requireAuthentication( "displayLogin" );
	
	echo	"retrieve value from session: ".$user->getSessionValue( "test" )."<br>";

	$PAGE_TITLE		=	"Your properties";
	include( "append.php" );
?>

