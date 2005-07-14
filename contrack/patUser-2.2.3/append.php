<?PHP
/**
*	patUser append
*/
	//	use this if you want to update the time online or visited pages
	//	if you just want to keep track of the logins you do NOT need
	//	to call update stats as it is done automatically
	$user->updateStats();

	//	use this if you want to use the history functions of
	//	patUser. The title of the page is optional
	//$user->keepHistory( 5, $PAGE_TITLE );
?>