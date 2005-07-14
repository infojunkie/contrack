<?php
/**
 * example for patListShare
 */
 	error_reporting( E_ALL );

	// some basic configuration... 
	$configDirs	= array(
							"config"	=> "conf",
							"cache"		=> "cache",
							"templates" => "templates"
						);

	// pat configuration	
	include_once( "include/patConfiguration.php" );
	$conf	=&	new patConfiguration();
	$conf->setConfigDir( $configDirs["config"]);
	$conf->setCacheDir( $configDirs["cache"] );
	$conf->loadCachedConfig( "user.xml" );
	
	$config =	$conf->getConfigValue( );

	// template engine
	include_once( "include/patTemplate.php" );
	$tmpl	=&	new	patTemplate();
	$tmpl->setBaseDir( $configDirs["templates"] );

	// dbc connection	
	include_once( "DB.php" ); 
	$dbc 	=&	DB::connect( $config["userconfig"]["dsn"] );
	if( DB::isError( $dbc ) )
	{
		echo "<b>Database connection failed</b><br>";
		echo "<pre>";
		print_r($dbc);
		echo "</pre>";	
		die();
	}		

	// patUser
	include_once( "include/patUser.php" );
	$user	=&	new patUser();
	$user->setAuthDbc( $dbc );
	$user->setTemplate( $tmpl );
	
	echo "<b>PatUser example</b><br>";
	echo "This is just a BETA release of patUser " . $user->systemVars["appVersion"] . "<br>";
	echo "Please notice that patUser switched to PEAR:DB<br>";
	echo "The last versions supporting patDbc are 2.1.x<br>";
						
	//configure the patUser object:
	/*
	echo "User Configuration:<pre>";
	print_r( $config["userconfig"] );
	echo "</pre>"; 
	*/
		
	$user->setAuthTable( $config["userconfig"]["authtable"] );
	$user->setAuthFields( $config["userconfig"]["authfields"] );
	
	$user->setGroupTable( $config["userconfig"]["grouptable"] );
	$user->setGroupFields( $config["userconfig"]["groupfields"] );
	$user->setGroupRelTable( $config["userconfig"]["groupreltable"] );
	$user->setGroupRelFields( $config["userconfig"]["grouprelfields"] );
	$user->setPermTable( $config["userconfig"]["permtable"] );
	$user->setPermFields( $config["userconfig"]["permfields"] );
	
	if( isset( $config["userconfig"]["statistics"] ) )
	{
		foreach( $config["userconfig"]["statistics"] as $key => $value )
		{
			$user->addStats( $key, $value );
		}
	}

	$vars	=	array_merge( $_GET, $_POST );
	if( isset( $vars["patuser"] ) )
	{
	
		if( $vars["patuser"] == "login" )
		{
			$data	=	array( "username" => $vars["username"], "passwd" => $vars["password"] );
			$user->authenticate( $data );
		}
		else if( $vars["patuser"] == "logout" )
		{
			$user->logOut();
		}
	}
	
	$user->requireAuthentication();
	
	// get some user-data
	if( $user->isAuthenticated() )
	{
		echo "<b>logout:</b><br>";
		echo "<a href=\"". $_SERVER["PHP_SELF"] ."?". SID ."&patuser=logout\">". $_SERVER["PHP_SELF"] ."?". SID ."&patuser=logout</a><br><br>";
	
		echo "<b>primary user data:</b><pre>";
		print_r( $user->getUserData() );
		echo "</pre>";
	}
	
	echo "<br><b>Errors:</b><pre>";
	print_r( $user->getAllErrors() );
	echo "</pre>";
	
	exit;
?>
