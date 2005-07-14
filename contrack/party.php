	
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
/******************************************************************************************
  	Party page

	This page will handle creating/editing/viewing parties.
	It has the following states: 	a) "NEW" to create a new party
									b) "EDIT" to edit an existing party
									c) "VIEW" to view an existing party
									d) "PROCESS". The form is in this state while it's
									being processed after a Save operation. The page
									will never be viewable in this state. (Transient state)
							
	State transitions are as follows:
		Initial State		Action						New State
		----------			------						---------
		VIEW				Edit						EDIT
		EDIT/NEW			Save						PROCESS 
		PROCESS				Validation successful		VIEW (With changes)
		PROCESS				Validation not successful	EDIT/NEW (With validation results)			
		EDIT/NEW			Cancel						history.back()

	Notes: 	- EDIT and NEW are similar states except that in EDIT the controls
			  are pre-populated with the attributes of the entity being edited
			  whereas in NEW, the controls are blank.			
			- In EDIT and VIEW state, the primary key exists (PARTY_ID), in NEW state 
			  it has the value -1.			
			- Deleting sub-entities (contact or attachment) works by resubmitting the form 
			  with the relevant opcode and sub-entity name
******************************************************************************************/

require_once("db_utils.php");
require_once("general_utils.php");
require_once( "patUser-2.2.3/prepend.php" );
require_once("permissions.php");

init_page();
process_state();	

/****************************************************
	Page Initialisation
	- Authenticate
	- Get all parameters passed to the page
	- Validate permissions for current user
	- Check for any errors passed to the page
****************************************************/
function init_page()
{
	global $user, $table_name;
	$table_name = 'party';
	
	// Authenticate
	if (! ($user->isAuthenticated())){
		deny_access();
	}	
	get_page_params ();
	validate_permissions();
	check_error();	
}

/*
	Get  parameters passed to this page and format display string
*/
function get_page_params()
{
	global  $state, $party_type_id, $party_id, $sub_entity_op, $sub_entity_name, $sub_entity_id, 
			$party_type_display, $str_party_type;

	// Get key parameters passed to the page
	$state  	= isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : '');
	$party_type_id = isset($_GET['type']) ? $_GET['type'] : (isset($_POST['TYPE']) ? $_POST['TYPE'] : ''); 
	$party_id	= isset($_GET['party_id']) ? $_GET['party_id'] : (isset($_POST['PARTY_ID']) ? $_POST['PARTY_ID'] : -1);	
	
	if (($party_id == -1) && ($state != state_PROCESS)) {
		$state = state_NEW;
	}		
	
	// Current sub entities are Attachments and Contacts
	$sub_entity_op   = isset($_GET['sub_entity_op']) ? $_GET['sub_entity_op'] : (isset($_POST['sub_entity_op']) ? $_POST['sub_entity_op'] : -1);
	$sub_entity_name = isset($_GET['sub_entity_name']) ? $_GET['sub_entity_name'] : (isset($_POST['sub_entity_name']) ? $_POST['sub_entity_name'] : -1);
	$sub_entity_id 	 = isset($_GET['sub_entity_id']) ? $_GET['sub_entity_id'] : (isset($_POST['sub_entity_id']) ? $_POST['sub_entity_id'] : -1);	
	
	$str_party_type = set_str_entity_type($party_type_id);	
	//Check for type and format display string
	if ($party_type_id == entity_EXTERNAL) {
		$party_type_display = 'External Party';
	}
	else {
		$party_type_display = capitalise_first_letter($str_party_type);
	}	
}
/****************************************************
	Permissions and security functions
****************************************************/
/*
	Validates that the user has the required permissions to view the page in this state
	- Get user permissions. "$str_party_type" is the page name to get the permissions for.
	- Set global flags
	- Get minimum permissions required
	- Evaluate the user permissions vis-a-vis the required permissions	
*/
function validate_permissions()
{
	global $user,$str_party_type;
		
	$user_perms = get_user_permissions ($user, $str_party_type);
	set_user_perms($user_perms);	
	set_required_perms( is_self());	
	
	if (! is_authorized()) {
		do_unauthorized();				
	}
}

/*
	Determine whether the currently logged in user is associated with the entity
	being viewed.
	Association Logic:
	- compare associated party id with entity id being viewed.
	- if equal, set is_self flag to true
*/
function is_self()
{
	global $user, $party_id ;
	
	$is_self = false;		
	
	// Get associated party_id
	$assoc_party_id = get_party_id_for_user($user->getSessionValue('userid'));
		
	if ($assoc_party_id == $party_id) {
		$is_self = true;	
	}
	else {
		$is_self = false;
	}
	return $is_self;
}

/*
	Based on state of page, go to appropriate function
*/
function process_state()
{
	global $state;
	// Check state
	if (empty($state)) { /* if $state is empty go to VIEW state*/
		view_form();
	} elseif ( $state == state_VIEW ) {
		view_form();
	} elseif ( $state == state_EDIT ) {
		edit_form();
	} elseif ( $state == state_PROCESS)  {
		process_form();
	} elseif ( $state == state_NEW )  {
		new_form();
	} else  { 		// might be redundant but just in case...
		view_form();
	}
}
/**************************************** 
	Root functions
*****************************************/
function new_form()
{
	init_form();
}

function edit_form()
{	
	init_form();
	get_record();
}	

function view_form()
{
	init_form();
	get_record();
}

/*
	Intitializes form
*/	
function init_form() 
{
	global $glb_arr_values, $state, $party_id, $party_type_id;
	
	if ( $state != state_NEW ) {
		$glb_arr_values['PARTY_ID'] =  $party_id;
		$glb_arr_values['TYPE']     =  $party_type_id;
	} 
	else {   //NEW		
		new_values();
	}
}

/*
	
	Function: process_form()
	Desc:  
		This function is called upon submission of the form.
		If this is a sub entity operation (contact or attachment delete) perform it otherwise:
		Validates values posted.
		Checks whether this is a NEW state or an EDIT state and acts accordingly.
 	Params: 
    	None
   	Returns: 
		Nothing
*/	
function process_form() 
{
	global 	$glb_arr_values, $state, $party_type_id, $party_id, 
			$sub_entity_op, $sub_entity_name, $sub_entity_id;
	
	// Get values from submitted form	
	post_values();
	
	// If we're deleting a sub-entity
	if ( $sub_entity_op == opcode_DELETE ) {		
		if ( strcasecmp ('CONTACT', $sub_entity_name ) == 0) {		
			delete_contact($sub_entity_id);
		} elseif ( strcasecmp ('ATTACHMENT', $sub_entity_name ) == 0) {		
			delete_attachment($sub_entity_id);
		}
		// Go back to edit mode
		$location_url = "Location: party.php?party_id=".$glb_arr_values['PARTY_ID']."&type=".$party_type_id."&state=". state_EDIT;
		header($location_url);	
		exit;					
	}
	else {
		if ( validate_form() ) {
			if ( -1 == $glb_arr_values['PARTY_ID'] ) { // NEW
				$sql = generate_insert_party_sql() ;
				//echo "SQL Generated: " . $sql . "<br>";			
				$result = mysql_query($sql) or die(mysql_error());
				if (!$result) return mysql_error();
				$glb_arr_values['PARTY_ID'] = mysql_insert_id();
				$party_id = $glb_arr_values['PARTY_ID'];			
				// Insert default agent for buyer only
				if ($party_type_id == entity_BUYER) {
					insert_default_agent_for_buyer($glb_arr_values['PARTY_ID'], $glb_arr_values['AGENT_ID'] ); 
				}											
			} else {  								// EDIT
				$sql = generate_update_party_sql();	
				//echo "SQL Generated: " . $sql . "<br>";	
				$result = mysql_query($sql) or die(mysql_error());
				if (!$result) return mysql_error();
				// Update default agent for buyer only
				if ($party_type_id == entity_BUYER) {
					// If Buyer already has a default agent
					$default_agent = get_default_agent_for_buyer($glb_arr_values['PARTY_ID']);
					if ( !empty ($default_agent) ) {
						update_default_agent_for_buyer($glb_arr_values['PARTY_ID'], $glb_arr_values['AGENT_ID'] ); 
					} else {
						insert_default_agent_for_buyer($glb_arr_values['PARTY_ID'], $glb_arr_values['AGENT_ID'] ); 
					}				
				}		
			}
			// Redirect while setting state back to "VIEW" after having saved successfully
			$location_url = "Location: party.php?party_id=".$glb_arr_values['PARTY_ID']."&type=".$party_type_id."&state=". state_VIEW;
			header($location_url);	
			exit;			
		} else {  // validation unsuccessful
			if ( -1 != $glb_arr_values['PARTY_ID'] ) {	// Go back to EDIT state			
				$state = state_EDIT;			
				$glb_arr_values['DATE_CREATED'] = get_date_created_for_entity($party_type_id, $glb_arr_values['PARTY_ID']);
			} 
			else {									// Go back to NEW state
				$state = state_NEW;
			}
		}
	} // end big else
}


/*
	Validate form values
*/	
function validate_form() 
{
	global $glb_arr_values, $errors, $party_type_display;
		
	// Verify that at least a name exists for the party
	if (empty($glb_arr_values['NAME'])) $errors['NAME'] = 'Please enter a name for this ' . $party_type_display;	
	
	// Email Check
	if ( (! empty ($glb_arr_values['EMAIL'])) &&  ( ! validate_email ($glb_arr_values['EMAIL'])) ) {
	 	$errors['EMAIL'] = 'Please enter a valid email address' ;
	}
	
	// URL Check
		
	if (count($errors) > 0)	{
		$errors['error'] = 'There was a problem with the information entered. Please review the form and make necessary corrections.';
	}
	return count($errors) == 0;	
}	

/*
	Populate global values array with defaults when the page is in 'NEW' state
*/
function new_values()
{
	global $glb_arr_values, $party_type_id;
	
	$glb_arr_values['PARTY_ID'] 	= -1;
	$glb_arr_values['CODE']			= '';
	$glb_arr_values['NAME'] 		= '';
	$glb_arr_values['TYPE'] 		= $party_type_id;
	$glb_arr_values['ADDRESS1'] 	= '';
	$glb_arr_values['ADDRESS2'] 	= '';
	$glb_arr_values['CITY'] 		= '';
	$glb_arr_values['PROVINCE'] 	= '';
	$glb_arr_values['POSTAL_CODE'] 	= '';
	$glb_arr_values['COUNTRY_ID'] 	= 0;
	$glb_arr_values['TEL'] 			= '';
	$glb_arr_values['MOBILE'] 		= '';
	$glb_arr_values['FAX'] 			= '';
	$glb_arr_values['EMAIL'] 		= '';
	$glb_arr_values['WEBSITE'] 		= '';
	$glb_arr_values['AGENT_ID'] = 0;	
	$glb_arr_values['EMPLOYER_ID'] 	=  0;    //Employer ID is zero for top level parties (employers)
	$glb_arr_values['POSITION'] 	= '';
	$glb_arr_values['NOTES'] 		= '';
}

/*
	Get values from submitted form and populate global values array
*/	
function post_values() 
{	
	global $glb_arr_values, $party_id, $party_type_id;
	
	$glb_arr_values['PARTY_ID'] 	= $party_id ;	
	$glb_arr_values['CODE']			= isset($_POST['CODE']) ? $_POST['CODE'] : '';
	$glb_arr_values['NAME'] 		= isset($_POST['NAME']) ? $_POST['NAME'] : '';
	$glb_arr_values['TYPE'] 		= $party_type_id ;
	$glb_arr_values['ADDRESS1'] 	= isset($_POST['ADDRESS1']) ? $_POST['ADDRESS1'] : '';
	$glb_arr_values['ADDRESS2'] 	= isset($_POST['ADDRESS2']) ? $_POST['ADDRESS2'] : '';
	$glb_arr_values['CITY'] 		= isset($_POST['CITY']) ? $_POST['CITY'] : '';
	$glb_arr_values['PROVINCE'] 	= isset($_POST['PROVINCE']) ? $_POST['PROVINCE'] : '';
	$glb_arr_values['POSTAL_CODE'] 	= isset($_POST['POSTAL_CODE']) ? $_POST['POSTAL_CODE'] : '';
	$glb_arr_values['COUNTRY_ID'] 	= isset($_POST['COUNTRY_ID']) ? $_POST['COUNTRY_ID'] : '';
	$glb_arr_values['TEL'] 			= isset($_POST['TEL']) ? $_POST['TEL'] : '';
	$glb_arr_values['MOBILE'] 		= isset($_POST['MOBILE']) ? $_POST['MOBILE'] : '';
	$glb_arr_values['FAX'] 			= isset($_POST['FAX']) ? $_POST['FAX'] : '';
	$glb_arr_values['EMAIL'] 		= isset($_POST['EMAIL']) ? $_POST['EMAIL'] : '';
	$glb_arr_values['WEBSITE'] 		= isset($_POST['WEBSITE']) ? $_POST['WEBSITE'] : '';
	$glb_arr_values['AGENT_ID'] = isset($_POST['AGENT_ID']) ? $_POST['AGENT_ID'] : 0 ;
	$glb_arr_values['EMPLOYER_ID'] 	= 0; // Zero when it's a top level party, hidden when saving a contact
	$glb_arr_values['POSITION'] 	= isset($_POST['POSITION']) ? $_POST['POSITION'] : '';
	$glb_arr_values['NOTES'] 		= isset($_POST['NOTES']) ? $_POST['NOTES'] : '';		
}

/*
	Function: get_record()
	Desc:  
		If party_id < 1 then this is a new record. Populate with defaults.
		If party_id > 1 get record from database and populate values global array
 	Params: 
    	None
   	Returns: 
		Nothing
*/
function get_record()
{
	global $glb_arr_values, $party_type_id, $party_id;
	
	if (1 > $glb_arr_values['PARTY_ID']) {
		new_values();
		return;
	}
	
	$sql = generate_select_party_sql();	
	//echo "SQL Generated: " . $sql . "<br>";
	$result = mysql_query($sql) or die(mysql_error());	
	if (!$result) return mysql_error();
	$row = mysql_fetch_array($result);
	
	// Populate values array
	$glb_arr_values['CODE'] 		= mysql_decode($row['CODE']);
	$glb_arr_values['NAME'] 		= mysql_decode($row['NAME']);
	$glb_arr_values['TYPE'] 		= capitalise_first_letter( mysql_decode($row['TYPE']));	
	$glb_arr_values['ADDRESS1'] 	= mysql_decode($row['ADDRESS1']);
	$glb_arr_values['ADDRESS2'] 	= mysql_decode($row['ADDRESS2']);
	$glb_arr_values['CITY'] 		= mysql_decode($row['CITY']);
	$glb_arr_values['PROVINCE'] 	= mysql_decode($row['PROVINCE']);
	$glb_arr_values['POSTAL_CODE'] 	= mysql_decode($row['POSTAL_CODE']);
	$glb_arr_values['COUNTRY_ID'] 	= mysql_decode($row['COUNTRY_ID']);		
	$glb_arr_values['TEL'] 			= mysql_decode($row['TEL']);
	$glb_arr_values['MOBILE'] 		= mysql_decode($row['MOBILE']);
	$glb_arr_values['FAX'] 			= mysql_decode($row['FAX']);
	$glb_arr_values['EMAIL'] 		= mysql_decode($row['EMAIL']);
	$glb_arr_values['WEBSITE'] 		= mysql_decode($row['WEBSITE']);
	$glb_arr_values['EMPLOYER_ID'] 	= mysql_decode($row['EMPLOYER_ID']);
	$glb_arr_values['POSITION'] 	= mysql_decode($row['POSITION']);
	$glb_arr_values['NOTES'] 		= mysql_decode($row['NOTES']);
	$glb_arr_values['DATE_CREATED'] = mysql_decode($row['DATE_CREATED']);
	
	// Get default agent for Buyer only
	if ($party_type_id == entity_BUYER) {
		$default_agent_row = get_default_agent_for_buyer($party_id);
		if (!empty( $default_agent_row )){
			$glb_arr_values['DEFAULT_AGENT_NAME'] = $default_agent_row['agent_name'];
			$glb_arr_values['AGENT_ID']   = $default_agent_row['agent_id'];
		}
		else{
			$glb_arr_values['DEFAULT_AGENT_NAME'] = '';
			$glb_arr_values['AGENT_ID']   = 0;
		}
	}	
	mysql_free_result($result);	
}

/**************************************** 
	SQL Generation functions
*****************************************/
/*
	Generates select SQL for party
*/	
function generate_select_party_sql()
{
	global $glb_arr_values;
	
	$select_sql = "SELECT * FROM party WHERE PARTY_ID=".$glb_arr_values['PARTY_ID'].
				  " AND TYPE=".$glb_arr_values['TYPE'] ;
	
	return $select_sql;
}

/*
	Generates select contacts SQL
*/	
function generate_select_contacts_sql () 
{
	global $party_id;
	
	$select_sql = 	"SELECT	PARTY_ID, NAME, POSITION, ADDRESS1, ADDRESS2, CITY, PROVINCE, POSTAL_CODE, 
							COUNTRY_ID, TEL, MOBILE, EMAIL 
					FROM party
					WHERE EMPLOYER_ID=". $party_id ;
	return $select_sql;
}

/*
	Generates insert SQL for party
*/	
function generate_insert_party_sql()
{
	global $glb_arr_values, $str_party_type, $party_type_id;
	
	$insert_sql = "INSERT INTO party 
					(STATE, CODE, NAME, TYPE, ADDRESS1, ADDRESS2, CITY, PROVINCE,
                     POSTAL_CODE, COUNTRY_ID, TEL, MOBILE, FAX, EMAIL, WEBSITE, EMPLOYER_ID,
                     POSITION, NOTES, DATE_CREATED)
					VALUES(	'ACTIVE',
					'".mysql_encode(get_new_code((($party_type_id == entity_EXTERNAL) ? 'EXT_PARTY' : strtoupper($str_party_type))))."',
					'".mysql_encode($glb_arr_values['NAME'])."',
					".$glb_arr_values['TYPE'].",
					'".mysql_encode($glb_arr_values['ADDRESS1'])."',
					'".mysql_encode($glb_arr_values['ADDRESS2'])."',
					'".mysql_encode($glb_arr_values['CITY'])."',					
					'".mysql_encode($glb_arr_values['PROVINCE'])."',
					'".mysql_encode($glb_arr_values['POSTAL_CODE'])."',
					".$glb_arr_values['COUNTRY_ID'].",
					'".mysql_encode($glb_arr_values['TEL'])."',
					'".mysql_encode($glb_arr_values['MOBILE'])."',
					'".mysql_encode($glb_arr_values['FAX'])."',
					'".mysql_encode($glb_arr_values['EMAIL'])."',
					'".mysql_encode($glb_arr_values['WEBSITE'])."',
					".$glb_arr_values['EMPLOYER_ID'].",
					'".mysql_encode($glb_arr_values['POSITION'])."',
					'".mysql_encode($glb_arr_values['NOTES'])."',					
					   NOW())";	   
	return $insert_sql;
}

/*
	Generates update SQL for party
*/	
	
function generate_update_party_sql()
{
	global $glb_arr_values;
	
	$update_sql =  "UPDATE party SET
																
					NAME = '" .mysql_encode( $glb_arr_values["NAME"])."',
					TYPE = '" .mysql_encode( $glb_arr_values["TYPE"])."',
					ADDRESS1 = '" .mysql_encode( $glb_arr_values["ADDRESS1"])."',
					ADDRESS2 = '" .mysql_encode( $glb_arr_values["ADDRESS2"])."',
					CITY = '" .mysql_encode( $glb_arr_values["CITY"])."',
					PROVINCE = '" .mysql_encode( $glb_arr_values["PROVINCE"])."',
					POSTAL_CODE = '" .mysql_encode( $glb_arr_values["POSTAL_CODE"])."',
					COUNTRY_ID =". $glb_arr_values["COUNTRY_ID"]. ",
					TEL = '" .mysql_encode( $glb_arr_values["TEL"])."',
					MOBILE = '" .mysql_encode( $glb_arr_values["MOBILE"])."',
					FAX = '" .mysql_encode( $glb_arr_values["FAX"])."',
					EMAIL = '" .mysql_encode( $glb_arr_values["EMAIL"])."',
					WEBSITE = '" .mysql_encode( $glb_arr_values["WEBSITE"])."',					
					EMPLOYER_ID =". $glb_arr_values["EMPLOYER_ID"].",
					POSITION = '" .mysql_encode( $glb_arr_values["POSITION"])."',
					NOTES = '" .mysql_encode( $glb_arr_values["NOTES"])."'
					
					WHERE PARTY_ID=".$glb_arr_values["PARTY_ID"];
	
	return $update_sql;
}

/*
	Get default agent for buyer from DB
	Returns $row if buyer has default agent. Empty otherwise.
*/
function get_default_agent_for_buyer ($int_buyer_id) 
{
	if  ($int_buyer_id > 0) {
	
		$sql = "SELECT party.NAME AS agent_name, party.PARTY_ID AS agent_id
			    FROM party INNER JOIN buyer_rel_agent 
 				ON party.PARTY_ID = buyer_rel_agent.AGENT_ID
				WHERE   buyer_rel_agent.BUYER_ID =". $int_buyer_id;		
		$result = mysql_query($sql) or die(mysql_error());	
		$row 	= mysql_fetch_array($result);
		if (mysql_num_rows($result)){			
			return $row;
		}
		else {
			return "";
		}
	}
	else {
		return "";
	}
}

/*
	Insert default agent for buyer 
*/
function insert_default_agent_for_buyer ($int_buyer_id, $int_agent_id) 
{
	if  ( ($int_buyer_id > 0) && ($int_agent_id > 0) ){
	
		$sql = "INSERT INTO buyer_rel_agent (BUYER_ID, AGENT_ID) 
              	VALUES (" .$int_buyer_id. "," .$int_agent_id. ")";
		$result = mysql_query($sql) or die(mysql_error());			
		if (!$result) return mysql_error();				
	}	
}
/*
	Update default agent for buyer 
*/
function update_default_agent_for_buyer ($int_buyer_id, $int_agent_id) 
{
	// Delete link between buyer and agent if agent_id = 0
	if ($int_agent_id == 0) {
		delete_default_agent_for_buyer($int_buyer_id);
	}
	else // Update 
	if  ( ($int_buyer_id > 0) && ($int_agent_id > 0) ){
	
		$sql = "UPDATE buyer_rel_agent 
				SET BUYER_ID = ". $int_buyer_id.  
				", AGENT_ID    = ". $int_agent_id. 
				" WHERE BUYER_ID = " . $int_buyer_id;
		$result = mysql_query($sql) or die(mysql_error());			
		if (!$result) return mysql_error();
	}				
}

/*
	Delete default agent for buyer
*/
function delete_default_agent_for_buyer($int_buyer_id)
{
	if ($int_buyer_id > 0 ) {
		$sql = "DELETE FROM buyer_rel_agent WHERE buyer_id =". $int_buyer_id; 
		$result = mysql_query($sql) or die(mysql_error());	
	}
}

function delete_contact($contact_id)
{
	if ($contact_id > 0 ) {
		// Delete all General Order relationships
		$sql = "DELETE FROM gen_order_rel_party WHERE PARTY_ID =".$contact_id ; 													 
		$result = mysql_query($sql) or die(mysql_error());			
		// Delete all Single Order relationships
		$sql = "DELETE FROM single_order_rel_party WHERE PARTY_ID =".$contact_id ; 													 
		$result = mysql_query($sql) or die(mysql_error());	
		// Delete username relationships
		$sql = "DELETE FROM user_rel_group 
				USING  user_rel_group INNER JOIN user ON user.uid = user_rel_group.uid 
				WHERE user.party_id = ".$contact_id ; 													 
		$result = mysql_query($sql) or die(mysql_error());	
		$sql = "DELETE FROM user WHERE user.party_id = ". $contact_id; 
		$result = mysql_query($sql) or die(mysql_error());	
		// Delete Contact
		$sql = "DELETE FROM party WHERE PARTY_ID =". $contact_id; 
		$result = mysql_query($sql) or die(mysql_error());	
	}
}
/**************************************** 
	XHTML Generation functions
*****************************************/
/*
	Generate contacts block
*/
function show_contacts()
{
	global $glb_arr_values, $state, $party_type_id;
 
 	// Get contacts from DB
	$sql = generate_select_contacts_sql();	
	$result = mysql_query($sql) or die(mysql_error());	
	
	// Contacts Header
	$markup = "<tr>
			   	<td valign=\"top\"><b>Contacts:</b></td>";									
	
	// If there are no contacts AND we're not in EDIT state
	if ( (! mysql_num_rows($result)) && ( $state != state_EDIT ) )  {
		$markup .= "<td colspan =\"3\"><i>No Contacts </td></i></tr>\r\n";			
	} else	{				
		$markup .= "<td colspan =\"3\">
					<table>								
					<tr class=\"contacts_header\"> ";
		
		// Are we in EDIT ?			
		if ( $state == state_EDIT ) {       
			$markup .= "<td class=\"small_text\" width=\"5%\"><a href=\"javascript: 
						void open_popup('contact_edit_popup.php?employer_id=".$glb_arr_values["PARTY_ID"]."&type=".
						$party_type_id ."&state=".state_NEW."','contactEdit','menubar=no,toolbar=no,scrollbars=no',520,580);\">
						<img src=\"images/b.add.png\" alt=\"Add\" title=\"Add\"/></a></td>\r\n" ;
		}
		$markup .=	   "<td>Name</td>
						<td>Position</td>									
						<td>Address</td>
						<td>Office Phone</td>
						<td>Mobile Phone</td>
						<td>Email</td>
						</tr>" ;	
		$i=0;
		while ($row = mysql_fetch_array($result)) {
			$pk    		= $row['PARTY_ID'];
			$name  		= mysql_decode($row['NAME']);
			$country	= get_country_name($row['COUNTRY_ID']) ;
			
			$markup .= "<tr class=\"contacts_line" .($i%2). "\">"; 
			
			if ( $state == state_EDIT ) {  
			$markup .= "<td class=\"small_text\"><a href=\"javascript:
						void open_popup('contact_edit_popup.php?employer_id=".$glb_arr_values["PARTY_ID"].
						"&type=".$party_type_id."&party_id=" .$pk."&state=".state_EDIT."',
						'contactEdit','menubar=no,toolbar=no,scrollbars=no',520,580);\">
						<img src=\"images/b.edit.png\" alt=\"Edit\" title=\"Edit\"/></a>
						<a href=\"javascript:void subEntityDelete('CONTACT',". $pk .");\">
				    	<img src=\"images/b.delete.png\" alt=\"Delete\" title=\"Delete\"/></a></td>\r\n";
			}
			$markup .= "<td>". $name . "</td>";						
			$markup .= "<td>"	.mysql_decode($row['POSITION'])."</td>";
			$markup .= "<td>"	.mysql_decode($row['ADDRESS1']). " " .mysql_decode($row['ADDRESS2']). " " 
								.mysql_decode($row['CITY']). " " . mysql_decode($row['PROVINCE']) . " " 
								.mysql_decode($row['POSTAL_CODE']) . " ". mysql_decode($country) ."</td>";	
			$markup .= "<td>"	.mysql_decode($row['TEL'])."</td>";
			$markup .= "<td>"	.mysql_decode($row['MOBILE'])."</td>";
			$markup .= "<td><a href=\"mailto:"	.$row['EMAIL'] ."\">" . $row['EMAIL'] . "</a></td>";
			$markup .= "</td></tr>\r\n";								
			++$i;
		}  // end while
		mysql_free_result($result);
		// Contacts table footer
		$markup .= "</table></td></tr>\r\n" ;
	} // end else
	return $markup;
}

/*
	Generates standard form header for party
*/
function generate_form_header() 
{
	global $glb_arr_values, $state, $party_type_id, $sub_entity_op, $sub_entity_name, $sub_entity_id;
	
	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_party\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"PARTY_ID\" name=\"PARTY_ID\" value=\"" . $glb_arr_values["PARTY_ID"] . "\"/>\r\n";	
	$str_frm_header .= "<input type=\"hidden\" id=\"TYPE\" name=\"TYPE\" value=\"" . $party_type_id . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"CODE\" name=\"CODE\" value=\"" . $glb_arr_values["CODE"] . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_op\" name=\"sub_entity_op\" value=\"" . $sub_entity_op . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_name\" name=\"sub_entity_name\" value=\"" . $sub_entity_name . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_id\" name=\"sub_entity_id\" value=\"" . $sub_entity_id . "\"/>\r\n";
	return $str_frm_header;
}


// Set page title and include body header
if ($party_type_id != entity_INTERNAL) {
	if ($state != state_NEW ) {
		$page_title = $glb_arr_values["NAME"] . " :: Profile";
	}
	else {
		$page_title = $party_type_display. " :: New";
	}
}
else {
	$page_title = "Organization Profile";
}
require_once("body_header.php"); 
?>

<!-- JavaScript Section -->
<script type="text/javascript"><!--

function entitySave() 
{
	frm = document.frm_party;		
	if (frm.NAME.value.length < 1 ) {
		alert ("You must enter a name for this party");
		return;
	} 
	frm.state.value = statePROCESS;
	frm.submit();	
}

function entityEdit() 
{
	frm = document.frm_party;		
	frm.state.value = stateEDIT;
	frm.submit();	
}
/*
	history.go(-1) and history.back() don't work in the following sequence:
	1- Save record -> you're back in View mode
	2- Click Edit -> you're in Edit mode
	3- Click Cancel to go back to View mode. This doesn't work because all the form 
	   information is lost. We need to resubmit.
*/
function entityCancel() 
{	
	frm = document.frm_party;	
	if (frm.PARTY_ID.value == -1) {
		//frm.state.value = stateNEW;
		history.back();
	}
	else {
		frm.state.value = stateVIEW;
		frm.submit();
	}	
}

function subEntityDelete(strName, intID) 
{
	if (confirm ('Are you sure you want to delete this record?') ) {
		frm = document.frm_party;		
		frm.state.value			  = statePROCESS;
		frm.sub_entity_op.value   = opcodeDELETE;
		frm.sub_entity_name.value = strName;
		frm.sub_entity_id.value   = intID;		
		frm.submit();	
	}
}

function resubmitForm() 
{
	var frm = document.frm_party;		
	frm.state.value	= stateEDIT;		
	frm.submit();
}
--></script>

<table border="0"  cellpadding="0" cellspacing="0">
	<tr>
		<td colspan="3">
			<h4>
			<?php 
			if ($party_type_id != entity_INTERNAL) {
				if ( $state != state_NEW ) { 					
					echo strtoupper ($party_type_display) ." :: ". $glb_arr_values["NAME"]; 
				} 
				else {
					echo strtoupper ($party_type_display) ." :: New " ;
				}
			}
			else {
				echo "ORGANIZATION";
			}
			?>
			</h4>
		</td>
	</tr>
	
	<!-- code to display informational/error message goes here -->
	
	<tr>  <!-- Tabs -->
		<td class = "tabs" colspan="3">
			<table border="0" cellspacing="0"> 
				<tr> 
				<?php 
				/*	Tab Generation 
					Pass the ordinal of the tab to be activated
				*/												
				switch ($party_type_id) {
					case entity_AGENT:
					echo generate_agent_tabs(3, $party_id, $state);
					break;
					case entity_BUYER:
					echo generate_buyer_tabs(5, $party_id, $state);
					break;
					case entity_SUPPLIER:
					echo generate_supplier_tabs(5, $party_id, $state);
					break;
					case entity_EXTERNAL:
					echo generate_external_tabs(1, $party_id, $state);
					break;		
					case entity_INTERNAL:
					echo generate_organization_tabs(1, $party_id, $state);
					break;		
				}																		
				?>						
				</tr>
			</table> 					
		</td>
	</tr> <!-- End of Tabs -->			
	<tr>
		<td colspan = "3"> 
			<table class="tab_content">	
				<td colspan="2">
					<table>
						<tr><td>
						<?php if (isset($errors['error'])) echo "<em class=\"error\">" . $errors['error'] . "</em>"; ?>
						</td></tr>
						<tr class="actions_header" >
							<td>Actions</td>
						</tr>
						<tr>
							<td>
							<?php 
							echo generate_form_header();		
							
							// Show action butons depending on current state
							if ( $state == state_VIEW ) { 																	
								if($allow_modify_all || $allow_modify_own) {
									echo generate_button("button","Edit","entityEdit()"); 
									}
								}						
							else{ //Edit or New
								echo generate_button("button","Save","entitySave()");
								echo "&nbsp";
								echo generate_button("button","Cancel","entityCancel()");										
							}	
							?>									
							</td>
						</tr>
						<tr>									
						</tr>
					</table>							
				</td></tr>
				<tr><td width="17%"><b>
				<?php						
				echo ($party_type_id != entity_INTERNAL) ? $party_type_display. " Name:": "Name:";
				echo generate_required_superscript($state);
				?>
				</b></td>
				<td>
				<?php if ($state == state_VIEW ) {
						echo $glb_arr_values["NAME"];
						}
					  else{
						echo generate_text_input ("formtext_longwidth", "NAME", $glb_arr_values["NAME"]);								
						}
				?> 
				<?php if (isset($errors['NAME'])) echo "<em class=\"error\">" . $errors['NAME'] . "</em>"; ?>
				</td></tr>						
				
				<?php if (( $state != state_NEW ) && ( $party_type_id != entity_INTERNAL )) { ?>
					<tr><td><b><?= $party_type_display. " Code:" ?> </b></td>
					<td><?= $glb_arr_values["CODE"];?></td></tr>	
				<?php } ?>
				
				<?php if (( $state != state_NEW )  && ( $party_type_id != entity_INTERNAL )) { ?>
					<tr><td><b><?= $party_type_display. " Since:" ?> </b></td>
					<td><?= format_date_reverse($glb_arr_values["DATE_CREATED"]);?></td></tr>	
				<?php } ?>
				
				<tr><td><b>Street Address 1:</b></td>
				<td>
				<?php if ($state == state_VIEW  ) {
						echo $glb_arr_values["ADDRESS1"];
						}
					  else{
						echo generate_text_input ("formtext_longwidth", "ADDRESS1", $glb_arr_values["ADDRESS1"]);								
						}
				?> 
				</td></tr>
				
				<tr><td><b>Street Address 2:</b></td>
				<td>
				<?php if ($state == state_VIEW ) {
						echo $glb_arr_values["ADDRESS2"];
						}
					  else{
						echo generate_text_input ("formtext_longwidth", "ADDRESS2", $glb_arr_values["ADDRESS2"]);								
						}
				?> 
				</td></tr>
				
				<tr><td><b>City:</b></td>
				<td>
				<?php if ($state == state_VIEW ) {
						echo $glb_arr_values["CITY"];
						}
					  else{
						echo generate_text_input ("formtext_stdwidth", "CITY", $glb_arr_values["CITY"]);								
						}
				?> 
				</td></tr>
				
				<tr><td><b>State/Province:</b></td>
				<td>
				<?php if ($state == state_VIEW ) {
						echo $glb_arr_values["PROVINCE"];
						}
					  else{
						echo generate_text_input ("formtext_stdwidth", "PROVINCE", $glb_arr_values["PROVINCE"]);								
						}
				?> 
				</td></tr>
				
				<tr><td><b>Postal Code:</b></td>
				<td>
				<?php if ($state == state_VIEW ) {
						echo $glb_arr_values["POSTAL_CODE"];
						}
					  else{
						echo generate_text_input ("formtext_stdwidth", "POSTAL_CODE", $glb_arr_values["POSTAL_CODE"]);								
						}
				?> 
				</td></tr>						
				
				<tr><td><b>Country:</b></td>
				<td>
				<?php if ($state == state_VIEW ) {
						echo get_country_name ($glb_arr_values["COUNTRY_ID"]);
						}
					  else{								
						echo generate_dd_country_input ($glb_arr_values["COUNTRY_ID"]);								
						}
				?> 
				</td></tr>
				
				<tr><td><b>Office Phone:</b></td>
				<td>
				<?php if ($state == state_VIEW ) {
						echo $glb_arr_values["TEL"];
						}
					  else{
						echo generate_text_input ("formtext_stdwidth", "TEL", $glb_arr_values["TEL"]);								
						}
				?> 
				</td></tr>
				
				<tr><td><b>Fax:</b></td>
				<td>
				<?php if ($state == state_VIEW ) {
						echo $glb_arr_values["FAX"];
						}
					  else{
						echo generate_text_input ("formtext_stdwidth", "FAX", $glb_arr_values["FAX"]);								
						}
				?> 
				</td></tr>
				
				<tr><td><b>Email:</b></td>
				<td>						
				<?php if ($state == state_VIEW ) {
						echo "<a href=\"mailto:" .$glb_arr_values["EMAIL"] . "\">" .$glb_arr_values["EMAIL"]. "</a>";
						}
					  else{ 
						echo generate_text_input ("formtext_longwidth", "EMAIL", $glb_arr_values["EMAIL"]);								
						 }
				?> 	
				<?php if (isset($errors['EMAIL'])) echo "<em class=\"error\">" . $errors['EMAIL'] . "</em>"; ?>					
				</td></tr> 
				
				<tr><td><b>Web:</b></td>
				<td>						
				<?php if ($state == state_VIEW ) {
						echo "<a href=\"javascript: void window.open('".$glb_arr_values["WEBSITE"]."','windowWebsite','toolbar=yes, location=yes,directories=yes,status=yes,menubar=yes,scrollbars=yes,copyhistory=yes, resizable=yes');\">" .$glb_arr_values["WEBSITE"]. "</a>";
						}
						else{
						echo generate_text_input ("formtext_longwidth", "WEBSITE", $glb_arr_values["WEBSITE"]);								
						}
				?> 		
				</td></tr>
				
				<?php 
					if ($party_type_id == entity_BUYER ) { ?>
						<tr><td><b>Default Agent:</b></td>
						<td>						
						<?php
						if ($state == state_VIEW ) {
							echo "<a href=\"search_order.php?entity=".entity_GEN_ORDER. "&referrer=".entity_AGENT."&referrer_id=".$glb_arr_values["AGENT_ID"]."\">".$glb_arr_values["DEFAULT_AGENT_NAME"]."</a>";
							}
						else {
							echo generate_dd_agent_input($glb_arr_values["AGENT_ID"]);
						} ?>
						</td></tr>
				<?php } ?>		
				
				<tr><td valign="top"><b>Notes:</b></td>
				<td>
				<?php if ($state == state_VIEW ) {
						echo '<span class="contrack_notes">';
						echo $glb_arr_values["NOTES"] ;
						echo '</span>';
						}
						else{
						echo generate_textarea_input ("NOTES", 5, 70, $glb_arr_values["NOTES"]);								
						}
				?> 	
										
				</td></tr>		
				</form>
				<?php 							
					if ($party_type_id != entity_AGENT) {
						echo show_contacts();														
					}
				?>						
				<?php							
					echo show_attachments($table_name, $party_id);
				?>
			</table>					
	</tr>	
					
</table>
<?php
include("body_footer.php"); 
?>