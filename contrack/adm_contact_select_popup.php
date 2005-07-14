	
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
  	Contact association popup page to associate a contact with a username

	This page is responsible for displaying the contacts selection popup.
	It has two states: VIEW and PROCESS
	It contains:
		- a drop-down of available employer types, 
		- a drop-down populated based on the employer type selected, 
		- and a table within a DIV showing the contacts for the employer selected with radio buttons	
******************************************************************************************/
require_once("db_utils.php");
require_once("general_utils.php");
require_once("patUser-2.2.3/prepend.php" );
require_once("permissions.php");

init_page ();
/****************************************************
	Page Initialisation
	- Authenticate
	- Get all parameters passed to the page
	- Check for any errors passed to the page	
****************************************************/
function init_page()
{
	global $user;
	// Authenticate
	if (! ($user->isAuthenticated())){
		deny_access();
	}	
	get_page_params ();
	check_error();	
}
/*
	Get key parameters
*/
function get_page_params()
{
	global $state, $referrer_id, $referrer_type, $employer_id, $employer_type;
	
	// Get key parameters
	$state  	   = isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : "");	
	$referrer_id   = isset($_GET['referrer_id']) ? $_GET['referrer_id'] : (isset($_POST['referrer_id']) ? $_POST['referrer_id'] : -1); 
	$referrer_type = isset($_GET['referrer_type']) ? $_GET['referrer_type'] : (isset($_POST['referrer_type']) ? $_POST['referrer_type'] : -1); 
	$employer_id   = isset($_GET['employer_id']) ? $_GET['employer_id'] : (isset($_POST['EMPLOYER_ID']) ? $_POST['EMPLOYER_ID'] : 0);
	$employer_type = isset($_GET['employer_type']) ? $_GET['employer_type'] : (isset($_POST['EMPLOYER_TYPE']) ? $_POST['EMPLOYER_TYPE'] : 0);
}
/*
	SQL for select contacts
	SQL Logic:
		Select all contacts for that particular employer + the employer himself 
*/
function generate_select_contacts_sql($referrer_id, $employer_type, $employer_id)
{	
	$select_sql = " SELECT DISTINCT party.PARTY_ID, party.NAME AS NAME, party.POSITION AS POSITION, 
					party.MOBILE AS MOBILE,
					IF (party.party_id <> ".$referrer_id.", 0,  1) AS IS_SELECTED
					FROM  party 
					LEFT JOIN party AS employer ON party.EMPLOYER_ID = employer.PARTY_ID 
					LEFT JOIN user ON user.party_id = party.PARTY_ID
					WHERE (party.EMPLOYER_ID= ".$employer_id." AND party.EMPLOYER_ID <> 0) OR party.PARTY_ID = ".$employer_id.
					" ORDER BY party.EMPLOYER_ID";
	return $select_sql;
}
/*
	Generate contacts block
*/
function show_contacts()
{
	global $glb_arr_values, $state, $employer_type, $employer_id, $referrer_type, $referrer_id;
 
 	// Get contacts from DB
	$sql = generate_select_contacts_sql($referrer_id, $employer_type, $employer_id);	
	$result = mysql_query($sql) or die(mysql_error());	

	if (mysql_num_rows($result) == 0 ){
		if (($employer_type > 0) && ($employer_id > 0 )) {
			$markup =  '<table>								
						<tr class="contacts_header"> 		
						<td class="small_text"><a href="javascript: 
							void open_popup(\'contact_edit_popup.php?employer_id='.$employer_id.'&type='.
							$employer_type .'&state='.state_NEW.'\',\'contactEdit\',\'menubar=no,toolbar=no,scrollbars=no\',520,580);">
							<img src="images/b.add.png" alt="Add" title="Add"/></a></td>'."\r\n" ;
			$markup .=	'<td></td>															
						<td>Name</td>
						<td>Position</td>									
						<td>Mobile</td></tr></table>';	
		}
		else {
			$markup = '';
		}			
	}
	else {
		$markup =  '<table>								
					<tr class="contacts_header"> 		
					<td class="small_text"><a href="javascript: 
							void open_popup(\'contact_edit_popup.php?employer_id='.$employer_id.'&type='.
							$employer_type .'&state='.state_NEW.'\',\'contactEdit\',\'menubar=no,toolbar=no,scrollbars=no\',520,580);">
							<img src="images/b.add.png" alt="Add" title="Add"/></a></td>'."\r\n" ;
		$markup .=	'<td></td>															
					<td>Name</td>
					<td>Position</td>									
					<td>Mobile</td></tr>';	
		$i=0;
		while ($row = mysql_fetch_array($result)) {
			$pk    		 = $row['PARTY_ID'];
			$name  		 = mysql_decode($row['NAME']);
			$position	 = mysql_decode($row['POSITION']) ;
			$mobile		 = mysql_decode($row['MOBILE']);
			$checked	 = ($row['IS_SELECTED']) ? ' checked="checked" ' : '';
			$markup .= "<tr class=\"contacts_line" .($i%2). "\">"; 
			$markup .= '<td class="small_text"><a href="javascript: 
							void open_popup(\'contact_edit_popup.php?employer_id='.$employer_id.'&type='.
							$employer_type .'&party_id='.$pk.'&state='.state_EDIT.'\',\'contactEdit\',\'menubar=no,toolbar=no,scrollbars=no\',520,580);">
							<img src="images/b.edit.png" alt="Edit" title="Edit"/></a></td>'."\r\n" ;
			$markup .= '<td><input type="radio" value="'.$pk.','.$name.'" id="contact" name="contact"'. $checked .'></td>';						
			$markup .= '<td>'. $name . '</td>';						
			$markup .= '<td>'.$position.'</td>';		
			$markup .= '<td>'.$mobile.'</td>';		
			$markup .= '</td></tr>'."\r\n";								
			++$i;
		}  // end while
		mysql_free_result($result);
		// Contacts table footer
		$markup .= '</table>'."\r\n" ;
	}
	return $markup;
}
/*
	Generates standard form header for party
*/
function generate_form_header() 
{
	global $state, $referrer_type, $referrer_id, $employer_type, $employer_id;
	
	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_select_contact\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"referrer_id\" name=\"referrer_id\" value=\"" . $referrer_id . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"referrer_type\" name=\"referrer_type\" value=\"" . $referrer_type . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"EMPLOYER_ID\" name=\"EMPLOYER_ID\" value=\"" . $employer_id . "\"/>\r\n";	
	$str_frm_header .= "<input type=\"hidden\" id=\"EMPLOYER_TYPE\" name=\"EMPLOYER_TYPE\" value=\"" . $employer_type . "\"/>\r\n";
	return $str_frm_header;
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Contrack - Select Contact</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="contrack.css" rel="stylesheet" type="text/css" media="all" />
</head>
<script src="utilities.js" type="text/javascript"></script>
<script type="text/javascript"><!--

/*
	Called on the onchange event in any of the two dropdowns
*/
function ddChange(ddName) 
{
	if (ddName=='EmployerType') {
		submitEmployerType();
	}
	else {
		submitEmployer();
	}
}
function submitEmployerType()
{
	var frm = document.frm_select_contact;	
	employerType = frm.EMPLOYER_TYPE_DD;
	frm.state.value = stateVIEW;
	frm.EMPLOYER_TYPE.value = employerType.options[employerType.selectedIndex].value;
	frm.EMPLOYER_ID.value = 0;
	frm.submit();
}

function submitEmployer()
{
	var frm = document.frm_select_contact;	
	employer = frm.EMPLOYER_DD;
	var dropdownValue = employer.options[employer.selectedIndex].value;	
	frm.state.value = stateVIEW;	
	arrEmployerIDType = dropdownValue.split(',');	
	frm.EMPLOYER_ID.value   = arrEmployerIDType[0];
	frm.EMPLOYER_TYPE.value = arrEmployerIDType[1];
	frm.submit();
	
}
/*
	Associate party with user.
*/
function associateParty()
{
	var frm = document.frm_select_contact;	
	if (frm.contact.length) {
		for (var i = 0; i < frm.contact.length; i++)  { 
			if (frm.contact[i].checked)  {
				arrParty  = frm.contact[i].value.split(',');
				partyId   = arrParty[0];
				partyName = arrParty[1];
				break;
			} 
		}
	}
	else {
		if (frm.contact.checked)  {
			arrParty  = frm.contact.value.split(',');
			partyId   = arrParty[0];
			partyName = arrParty[1];
		} 
	}
	window.opener.setAssociatedPartyId(partyId, partyName );
	window.close();
}

function refreshOpenerAndClose()
{
	window.opener.resubmitForm();
	window.close();
}

function resubmitForm()
{
	frm = document.frm_select_contact;		
	frm.submit();
}
--></script>
<body class="body_popup">
<?php echo generate_form_header() ?>
<table class="popup" >
	<tr><td colspan="2"><h4>Select Contact</h4></td></tr>
	<tr><td colspan="2">
		<table>	
			<tr class="contacts_header" >
				<td width="20%">Employer Type</td>
				<td>Employer</td>
			</tr>
			<tr>
				<td>								
				<?php 
				echo '<select id="EMPLOYER_TYPE_DD" name="EMPLOYER_TYPE_DD" onChange="ddChange(\'EmployerType\');">';
				echo populate_employer_types_dropdown($employer_type, ($referrer_type == entity_USER)); 
				echo '</select>';
				?>				
				</td>
				<td>
				<?php 
				echo '<select id="EMPLOYER_DD" name="EMPLOYER_DD" onChange="ddChange(\'Employer\');">';
				echo populate_employers_dropdown($employer_id, $employer_type); 
				echo '</select>';
				?>				
				</td>
			</tr>
		</table>
	</td></tr>
	<tr><td colspan="2">
	<div class="contact_select_div">
	<?= show_contacts(); ?>
	</div>
	</td></tr>
	<tr><td colspan="2" align="right">
	<?php
	echo generate_button("button","Add","associateParty();");
	echo "&nbsp;";
	echo generate_button("button","Cancel","javascript: window.close();");
	?>		  
	</td></tr>
	</table>
</form>
</body>
</html>
