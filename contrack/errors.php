	
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
/**
*	error messages
*	array	$error_messages
**/
		$error_messages	=	array(
									1		=>	"No user found.",
									2		=>	"No unique user found.",
									10		=>	"Username is required.",
									11		=>	"Password is required.",
									12		=>	"User already exists.",
									13		=>	"Passwords do not match.",
									14		=>	"You are not allowed to login.",
									15		=>  "You could not be logged in because you supplied an invalid User Name/Password combination.",
									16		=>  "You are not logged in. Please login.",
									17		=>  "You are not authorized to view the page you just requested.",									
									20		=>	"User Id is required.",
									30		=>	"Data is needed.",
									100		=>	"No primary key value found.",
									101		=>	"No unique primary key value found.",
									110		=>	"Dataset could not be identified.",
									120		=>	"Insert is not allowed.",
									121		=>	"Delete is not allowed.",
									130		=>	"Data was not changed.",
									140		=>	"Table does not exist.",									
									1010	=>	"Name of group is required.",
									1012	=>	"Group already exists.",
									1020	=>	"Group Id is required.",
									1050	=>	"User already is in group.",
									1051	=>	"User is not in group.",
									1060	=>	"Need user or group id.",
									1061	=>	"No id type specified.",
									2000	=>	"No database result id returned."
								);


/**
 *	error code: no user matched the query
 */
define( "contrack_NO_USER_FOUND", 1 );

/**
 *	error code: more than one user matror  the query
 */
define( "contrack_NO_UNIQUE_USER_FOUND", 2 );

/**
 *	error code: function requires a user name
 */
define( "contrack_NEED_USERNAME", 10 );

/**
 *	error code: function requires a password
 */
define( "contrack_NEED_PASSWD", 11 );

/**
 *	error code: user already exists
 */
define( "contrack_USER_ALREADY_EXISTS", 12 );

/**
 *	error code: password incorrect
 */
define( "contrack_PASSWD_MISMATCH", 13 );

/**
 *	error code: login for this user was diabled
 */
define( "contrack_LOGIN_DISABLED", 14 );

/**
 *	error code: login failed
 */
define( "contrack_LOGIN_FAILED", 15 );

/**
 *	error code: Not Logged in
 */
define( "contrack_NOT_LOGGED_IN", 16 );

/**
 *	error code: Unauthorized access
 */
define( "contrack_UNAUTHORIZED_ACCESS", 17 );

/**
 *	error code: function requires a user id
 */
define( "contrack_NEED_UID", 20 );

/**
 *	error code: function requires data
 */
define( "contrack_NO_DATA_GIVEN", 30 );

/**
 *	error code: no primary key was found
 */
define( "contrack_NO_PRIMARY_FOUND", 100 );

/**
 *	error code: data matched more than one row
 */
define( "contrack_NO_UNIQUE_PRIMARY_FOUND", 101 );

/**
 *	error code: insert is not allowed
 */
define( "contrack_INSERT_NOT_ALLOWED", 120 );

/**
 *	error code: delete is not allowed
 */
define( "contrack_DELETE_NOT_ALLOWED", 121 );

/**
*	error code: no data was changed (affected rows = 0)
*/
define( "contrack_NO_DATA_CHANGED", 130 );

/**
 *	error code: table does not exist
 */
define( "contrack_TABLE_DOES_NOT_EXIST", 140 );

/**
 *	error code: function requires a group name
 */
define( "contrack_NEED_GROUPNAME", 1010 );

/**
 *	error code: group already exists (when adding a group)
 */
define( "contrack_GROUP_ALREADY_EXISTS", 1012 );

/**
 *	error code: function requires group id
 */
define( "contrack_NEED_GID", 1020 );

/**
 *	error code: user already is in group
 */
define( "contrack_ALREADY_JOINED_GROUP", 1050 );

/**
 *	error code: user is not in group
 */
define( "contrack_NOT_IN_GROUP", 1051 );

/**
 *	error code: function requires user or group id
 */
define( "contrack_NEED_ID", 1060 );

/**
 *	error code: function requires type of supplied id (user or group)
 */
define( "contrack_NEED_ID_TYPE", 1061 );

/**
 *	error code: query had no result
 */
define( "contrack_NO_DB_RESULT", 2000 );

/*
	Desc:  
		Translates an error code
 	Params: 
    	int		$code		error code
   	Returns: 	
*/
function	translate_error_code( $code )
{
	global $error_messages;
	return	$error_messages[$code];
}								
?>