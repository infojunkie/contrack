<?PHP

/**

 *	patUser prepend

 */

	error_reporting( E_ALL );



	// change the data-source name to fit your needs - see documentation at http://pear.php.net
	$dsn	=	"mysqli://contrack:contrack@db/contrack";

	//	patTemplate is used for login screen

	include_once( "include/patTemplate.php" );

	$tmpl		=	new	patTemplate();

	$tmpl->setBasedir( "templates" );





	//	user management class

	include_once( "include/patUser.php" );

	//	Work with session, use global var $patUserData

	$user	=	new	patUser( true, "patUserData" );



	//	set access to main database





	//	database that contains auth table with all important user data

	include_once( "DB.php" );





	// either set db-object...

	$authDbc 	=	DB::connect( $dsn );

	if( DB::isError( $authDbc ) )

	{

		echo "<b>Database connection failed</b><br>";

		echo "<pre>";

		print_r( $authDbc );

		echo "</pre>";

		die( "Database connection failed" );

	}

	$user->setAuthDbc( $authDbc );



/*

	// or setup authdbc by dsn

	$authDbc	=	$user->setAuthDbc( $dsn );

	if( DB::isError( $authDbc ) )

	{

		echo "<b>Database connection failed</b><br>";

		echo "<pre>";

		print_r( $authDbc );

		echo "</pre>";

		die( "Database connection failed" );

	}

*/





	//	this table stores all users

	$user->setAuthTable( "user" );



	//	set required fieldnames

	$user->setAuthFields( array(	"primary"	=>	"uid",

									"username"	=>	"username",

									"passwd"	=>	"passwd" ) );



	//	patTemplate object for Login screen

	//	can be left out if you want to use HTTP authentication

	$user->setTemplate( $tmpl );



	//	maximum login attempts

	$user->setMaxLoginAttempts( 20 );



	//	if working with several databases, define them here

/*

	$newDsn	=	"mysql://pat:pat123@localhost/pat";

	$newDbc =&	DB::connect( $newDsn );

	$user->addDbc( "new", $newDbc );

	$user->addTable( "userBoomkarks", array(	"dbc"		=>	"forum"

												"table"		=>	"bkmarks",

												"foreign"	=>	"uid" ) );

*/



	//	this table stores group data

	$user->setGroupTable( "groupe" );

	//	set fieldnames in the grouptable

	$user->setGroupFields( array(	"primary"	=>	"gid",

									"name"		=>	"name" ) );



	//	this table stores group data

	$user->setGroupRelTable( "user_rel_group" );

	//	set fieldnames in the user - group relation table

	$user->setGroupRelFields( array(	"uid"	=>	"uid",

										"gid"	=>	"gid" ) );



	//	set tabel which stores permissions

	$user->setPermTable( "permissions" );

	//	set names of required fields and add an additional field "part"

	$user->setPermFields( array(	"id"		=>	"id",

									"id_type"	=>	"id_type",

									"part"		=>	"part",

									"perms"		=>	"perms" ) );



	//	use statistic functions

	$user->addStats( "first_login", "first_login" );

	$user->addStats( "last_login", "last_login" );

	$user->addStats( "count_logins", "count_logins" );

	$user->addStats( "count_pages", "count_pages" );

	$user->addStats( "time_online", "time_online" );



	//	Now patUser is fully configured

?>