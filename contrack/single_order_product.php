	
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
  	Product Details page

	This page will handle creating/editing/viewing the product in the single order and
	all the associated technical details.
	It has the following states: 	a) "NEW" to create a new product
									b) "EDIT" to edit an existing product
									c) "VIEW" to view an existing product
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
			- In EDIT and VIEW state, the primary key exists (PRODUCT_ID), in NEW state 
			  it has the value 0.			  			
			- Deleting sub-entities (attachment) works by resubmitting the form 
			  with the relevant opcode and sub-entity name
******************************************************************************************/
require_once("db_utils.php");
require_once("general_utils.php");
require_once("order_utils.php");
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
	global $user,  $table_name;
	 	
	// Authenticate
	if (! ($user->isAuthenticated())){
		deny_access();
	}	
	get_page_params ();
	validate_permissions();
	check_error();	
	$table_name = 'product'; // use for attachment table transactions
}

/*
	Get  parameters passed to this page and format display string
*/
function get_page_params()
{
	global  $state, $str_entity, $single_order_id, $product_id, $sub_entity_op, $sub_entity_name, $sub_entity_id;

	// Get key parameters passed to the page
	$state  		 = isset($_GET['state']) ? $_GET['state'] : (isset($_POST['state']) ? $_POST['state'] : '');
	$single_order_id = isset($_GET['single_order_id']) ? $_GET['single_order_id'] : (isset($_POST['SINGLE_ORDER_ID']) ? $_POST['SINGLE_ORDER_ID'] : -1); 
	$product_id		 = isset($_GET['product_id']) ? $_GET['product_id'] : (isset($_POST['PRODUCT_ID']) ? $_POST['PRODUCT_ID'] : get_sin_order_prod_id($single_order_id));			

	// Current sub entities are Attachments
	$sub_entity_op   = isset($_GET['sub_entity_op']) ? $_GET['sub_entity_op'] : (isset($_POST['sub_entity_op']) ? $_POST['sub_entity_op'] : -1);
	$sub_entity_name = isset($_GET['sub_entity_name']) ? $_GET['sub_entity_name'] : (isset($_POST['sub_entity_name']) ? $_POST['sub_entity_name'] : -1);
	$sub_entity_id 	 = isset($_GET['sub_entity_id']) ? $_GET['sub_entity_id'] : (isset($_POST['sub_entity_id']) ? $_POST['sub_entity_id'] : -1);				
	$str_entity 	 = 'single_order_product';
}
/*
	Validates that the user has the required permissions to view the page in this state
	- Get User permissions
	- Set global flags
	- Get minimum permissions required
	- Evaluate the user permissions vis-a-vis the required permissions	
*/
function validate_permissions()
{
	global $user, $str_entity;
			
	$user_perms = get_user_permissions ($user, $str_entity);	
	//echo "user_perms = ";
	//var_dump($user_perms);	
	//echo "<br>";	
	set_user_perms($user_perms);
	set_required_perms(is_associated());	
	
	if (! is_authorized_view_own()) {
		do_unauthorized();
	}
}

/*
	Authorize based on which permissions are required to view the page and which permissions 
	the user has.	
*/
function is_authorized_view_own()
{
	global $allow_view_own;;
	
	$authorized = false;
	if ($allow_view_own || $allow_view_all || $allow_modify_all) {
		$authorized = true;
	}		
	return $authorized;
}
/*
	Determine whether the currently logged in user is associated with the entity
	being viewed.
*/
function is_associated()
{
	
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
	global $glb_arr_values, $state, $single_order_id, $product_id;
	
	if ( $state != state_NEW ) {
		$glb_arr_values['PRODUCT_ID'] =  $product_id;
	} 
	else {   //NEW		
		new_values();
	}
}

/*
	
	Function: process_form()
	Desc:  
		This function is called upon submission of the form.
		If this is a sub entity operation (attachment delete) perform it otherwise:
		Validates values posted.
		Checks whether this is a NEW state or an EDIT state and acts accordingly.
 	Params: 
    	None
   	Returns: 
		Nothing
*/	
function process_form() 
{
	global 	$glb_arr_values, $state, $single_order_id, $product_id,
			$sub_entity_op, $sub_entity_name, $sub_entity_id;
	
	// Get values from submitted form	
	post_values();
	
	// If we're deleting a sub-entity
	if ( $sub_entity_op == opcode_DELETE ) {		
		if ( strcasecmp ('ATTACHMENT', $sub_entity_name ) == 0) {		
			delete_attachment($sub_entity_id);
		}
		// Go back to edit mode
		$location_url = "Location: single_order_product.php?single_order_id=".$glb_arr_values['SINGLE_ORDER_ID']."&product_id=".$product_id."&state=". state_EDIT;
		header($location_url);	
		exit;					
	}
	else {
		if ( validate_form() ) {
			if ( 0 == $glb_arr_values['PRODUCT_ID'] ) { // NEW
				$sql = generate_insert_product_sql($product_id) ;
				$result = mysql_query($sql) or die(mysql_error());
				if (!$result) return mysql_error();
				$product_id = mysql_insert_id();
				// Insert product id into single_order table			
				$sql = generate_insert_product_into_sin_order ($glb_arr_values['SINGLE_ORDER_ID'], $product_id);											
				$result = mysql_query($sql) or die(mysql_error());
				if (!$result) return mysql_error();				
			} 
			else {  								// EDIT
				$sql = generate_update_product_sql($product_id);	
				$result = mysql_query($sql) or die(mysql_error());
				if (!$result) return mysql_error();
			}
			// Upload Product Pic
			$pic_attach_id = upload_prod_pic();				
			if ($pic_attach_id != 0 ) {
				$sql = generate_insert_pic_attach_id($product_id, $pic_attach_id);
			}
			$result = mysql_query($sql) or die(mysql_error());
			if (!$result) return mysql_error();
			// Redirect while setting state back to "VIEW" after having saved successfully
			$location_url = "Location: single_order_product.php?single_order_id=".$glb_arr_values['SINGLE_ORDER_ID']."&product_id=".$product_id."&state=". state_VIEW;
			header($location_url);	
			exit;			
		} else {  // validation unsuccessful
			if ( 0 != $glb_arr_values['PRODUCT_ID'] ) {	// Go back to EDIT state			
				$state = state_EDIT;			
			} 
			else {									    // Go back to NEW state
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
	global $glb_arr_values, $errors;
		
	// Verify that a product type has been selected
	if (empty($glb_arr_values['PRODUCT_TYPE_ID'])) $errors['PRODUCT_TYPE_ID'] = 'Please enter a type for this product' ;	
	
	// Verify that at least a name exists for the product
	if (empty($glb_arr_values['NAME'])) $errors['NAME'] = 'Please enter a name for this product' ;	
	
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
	global $glb_arr_values, $single_order_id, $product_id;
	
	$glb_arr_values['PRODUCT_ID'] 		= -1;
	$glb_arr_values['SINGLE_ORDER_ID']	= $single_order_id;
	$glb_arr_values['PRODUCT_TYPE_ID'] 	= -1;
	$glb_arr_values['PRODUCT_TYPE_NAME']= '';
	$glb_arr_values['PRODUCT_CODE'] 	= '';
	$glb_arr_values['NAME'] 			= '';
	$glb_arr_values['LABEL_HANG_TAGS'] 	= '';
	$glb_arr_values['PACKING_INSTRUCTIONS'] = '';
	$glb_arr_values['SHIPPING_INSTRUCTIONS'] = '';
	$glb_arr_values['COMMENTS'] 		= '';
	$glb_arr_values['PIC_ATTACH_ID'] 	= -1;
	$glb_arr_values['FABRIC_WEIGHT'] 	= '';
	$glb_arr_values['FW_UNIT_ID'] 		= -1;
	$glb_arr_values['DIMENSIONS'] 		= '';
	$glb_arr_values['DIM_UNIT_ID'] 		= -1;
	$glb_arr_values['PRODUCT_WEIGHT'] 	= '';
	$glb_arr_values['PW_UNIT_ID'] 		= -1;
	$glb_arr_values['COLOURS'] 			= '';
	$glb_arr_values['PRINT_EMBROIDERY'] = '';
	$glb_arr_values['CUSTOM_1'] 		= '';
	$glb_arr_values['CUSTOM_2'] 		= '';
	$glb_arr_values['CUSTOM_3'] 		= '';
	$glb_arr_values['CUSTOM_4'] 		= '';
	$glb_arr_values['CUSTOM_5'] 		= '';
	$glb_arr_values['CUSTOM_6'] 		= '';	
}

/*
	Get values from submitted form and populate global values array
*/	
function post_values() 
{	
	global $glb_arr_values, $single_order_id, $product_id;
	
	$glb_arr_values['PRODUCT_ID'] 		= $product_id ;	
	$glb_arr_values['SINGLE_ORDER_ID']	= $single_order_id;
	$glb_arr_values['PRODUCT_TYPE_ID'] 	= isset($_POST['PRODUCT_TYPE_ID']) ? $_POST['PRODUCT_TYPE_ID'] : 0;
	$glb_arr_values['PRODUCT_CODE'] 	= isset($_POST['PRODUCT_CODE']) ? $_POST['PRODUCT_CODE'] : '';
	$glb_arr_values['NAME'] 			= isset($_POST['NAME']) ? $_POST['NAME'] : '';
	$glb_arr_values['LABEL_HANG_TAGS'] 	= isset($_POST['LABEL_HANG_TAGS']) ? $_POST['LABEL_HANG_TAGS'] : '';
	$glb_arr_values['PACKING_INSTRUCTIONS']  = isset($_POST['PACKING_INSTRUCTIONS']) ? $_POST['PACKING_INSTRUCTIONS'] : '';
	$glb_arr_values['SHIPPING_INSTRUCTIONS'] = isset($_POST['SHIPPING_INSTRUCTIONS']) ? $_POST['SHIPPING_INSTRUCTIONS'] : '';
	$glb_arr_values['COMMENTS'] 		= isset($_POST['COMMENTS']) ? $_POST['COMMENTS'] : '';
	$glb_arr_values['PIC_ATTACH_ID'] 	= isset($_POST['PIC_ATTACH_ID']) ? $_POST['PIC_ATTACH_ID'] : 0;
	$glb_arr_values['FABRIC_WEIGHT'] 	= isset($_POST['FABRIC_WEIGHT']) ? $_POST['FABRIC_WEIGHT'] : '';
	$glb_arr_values['FW_UNIT_ID'] 		= isset($_POST['FW_UNIT_ID']) ? $_POST['FW_UNIT_ID'] : 0;
	$glb_arr_values['DIMENSIONS'] 		= isset($_POST['DIMENSIONS']) ? $_POST['DIMENSIONS'] : '';
	$glb_arr_values['DIM_UNIT_ID'] 		= isset($_POST['DIM_UNIT_ID']) ? $_POST['DIM_UNIT_ID'] : 0;
	$glb_arr_values['PRODUCT_WEIGHT'] 	= isset($_POST['PRODUCT_WEIGHT']) ? $_POST['PRODUCT_WEIGHT'] : '';
	$glb_arr_values['PW_UNIT_ID'] 		= isset($_POST['PW_UNIT_ID']) ? $_POST['PW_UNIT_ID'] : 0;
	$glb_arr_values['COLOURS'] 			= isset($_POST['COLOURS']) ? $_POST['COLOURS'] : '';
	$glb_arr_values['PRINT_EMBROIDERY'] = isset($_POST['PRINT_EMBROIDERY']) ? $_POST['PRINT_EMBROIDERY'] : '';
	$glb_arr_values['CUSTOM_1'] 		= isset($_POST['CUSTOM_1']) ? $_POST['CUSTOM_1'] : '';
	$glb_arr_values['CUSTOM_2'] 		= isset($_POST['CUSTOM_2']) ? $_POST['CUSTOM_2'] : '';
	$glb_arr_values['CUSTOM_3'] 		= isset($_POST['CUSTOM_3']) ? $_POST['CUSTOM_3'] : '';
	$glb_arr_values['CUSTOM_4'] 		= isset($_POST['CUSTOM_4']) ? $_POST['CUSTOM_4'] : '';
	$glb_arr_values['CUSTOM_5'] 		= isset($_POST['CUSTOM_5']) ? $_POST['CUSTOM_5'] : '';
	$glb_arr_values['CUSTOM_6'] 		= isset($_POST['CUSTOM_6']) ? $_POST['CUSTOM_6'] : '';
}
/*
	Get product pic file attributes and return true if a file was uploaded. False otherwise.
*/
function post_values_product_pic()
{
	global $upload_dir, $full_path_name, $size, $type, $uploaded_file, $tmp_file_name;
	// Get uploaded file attributes 
	$uploaded_file 	= $_FILES['file_name']['name'];
	$full_path_name	= $upload_dir . $uploaded_file  ; 
	$size 			= $_FILES['file_name']['size'];
	$type 			= $_FILES['file_name']['type'];
	$tmp_file_name	= $_FILES['file_name']['tmp_name'];
	
	return (!empty($uploaded_file));
}

/*
	Put product pic in db and return $pic_attach_id
*/
function upload_prod_pic()
{
	global $glb_arr_values, $product_id, $full_path_name, $tmp_file_name;	
	
	$pic_attach_id = 0;	
	if	( post_values_product_pic() ) {		
		// Check to see if file was uploaded correctly and via php	
		if(move_uploaded_file($tmp_file_name, $full_path_name)) {	
			chmod($full_path_name, 0777);
			if ($glb_arr_values['PIC_ATTACH_ID'] <= 0) {    // New 
				$sql 			= generate_insert_product_pic_sql ($product_id);
				$result 		= mysql_query($sql) or die(mysql_error());	
				$pic_attach_id 	= mysql_insert_id();
			}
			else {											// Edit
				$sql 			= generate_update_product_pic_sql ($glb_arr_values['PIC_ATTACH_ID']);
				$result 		= mysql_query($sql) or die(mysql_error());	
				$pic_attach_id 	= $glb_arr_values['PIC_ATTACH_ID'] ;
			}
			unlink($full_path_name);
		}
	}
	return $pic_attach_id;
}

/*
	
*/
function download_prod_pic()
{
	global  $glb_arr_values;
	
	$sql      = "SELECT TYPE, DATA FROM attachment WHERE ATTACHMENT_ID = " . $glb_arr_values['PIC_ATTACH_ID'];
	$result   = mysql_query($sql) or die(mysql_error());	
	$row      = mysql_fetch_array($result);
	$type	  =	$row['TYPE'];
	$prod_pic = $row['DATA'];
	header ("Content-type: $type");	
	echo $prod_pic;
}

/*
	Function: get_record()
	Desc:  
		If product_id < 1 then this is a new record. Populate with defaults.
		If product_id > 1 get record from database and populate values global array
 	Params: 
    	None
   	Returns: 
		Nothing
*/
function get_record()
{
	global $glb_arr_values, $product_id;
	
	if (1 > $glb_arr_values['PRODUCT_ID']) {
		new_values();
		return;
	}
	
	$sql = generate_select_product_sql($product_id);	
	$result = mysql_query($sql) or die(mysql_error());	
	if (!$result) return mysql_error();
	$row = mysql_fetch_array($result);
	
	// Populate values array	
	$glb_arr_values['PRODUCT_TYPE_ID'] 	= mysql_decode($row['PRODUCT_TYPE_ID']);
	$glb_arr_values['PRODUCT_TYPE_NAME']= mysql_decode($row['PRODUCT_TYPE_NAME']);
	$glb_arr_values['PRODUCT_CODE'] 	= mysql_decode($row['PRODUCT_CODE']);
	$glb_arr_values['NAME'] 			= mysql_decode($row['NAME']);
	$glb_arr_values['LABEL_HANG_TAGS'] 	= mysql_decode($row['LABEL_HANG_TAGS']);
	$glb_arr_values['PACKING_INSTRUCTIONS']  = mysql_decode($row['PACKING_INSTRUCTIONS']);
	$glb_arr_values['SHIPPING_INSTRUCTIONS'] = mysql_decode($row['SHIPPING_INSTRUCTIONS']);
	$glb_arr_values['COMMENTS'] 		= mysql_decode($row['COMMENTS']);
	$glb_arr_values['PIC_ATTACH_ID'] 	= mysql_decode($row['PIC_ATTACH_ID']);
	$glb_arr_values['FABRIC_WEIGHT'] 	= mysql_decode($row['FABRIC_WEIGHT']);
	$glb_arr_values['FW_UNIT_ID'] 		= mysql_decode($row['FW_UNIT_ID']);
	$glb_arr_values['FW_UNIT_NAME'] 	= mysql_decode($row['FW_UNIT_NAME']);
	$glb_arr_values['DIMENSIONS'] 		= mysql_decode($row['DIMENSIONS']);
	$glb_arr_values['DIM_UNIT_ID'] 		= mysql_decode($row['DIM_UNIT_ID']);
	$glb_arr_values['DIM_UNIT_NAME'] 	= mysql_decode($row['DIM_UNIT_NAME']);
	$glb_arr_values['PRODUCT_WEIGHT'] 	= mysql_decode($row['PRODUCT_WEIGHT']);
	$glb_arr_values['PW_UNIT_ID'] 		= mysql_decode($row['PW_UNIT_ID']);
	$glb_arr_values['PW_UNIT_NAME'] 	= mysql_decode($row['PW_UNIT_NAME']);
	$glb_arr_values['COLOURS'] 			= mysql_decode($row['COLOURS']);
	$glb_arr_values['PRINT_EMBROIDERY'] = mysql_decode($row['PRINT_EMBROIDERY']);
	$glb_arr_values['CUSTOM_1'] 		= mysql_decode($row['CUSTOM_1']);
	$glb_arr_values['CUSTOM_2'] 		= mysql_decode($row['CUSTOM_2']);
	$glb_arr_values['CUSTOM_3'] 		= mysql_decode($row['CUSTOM_3']);
	$glb_arr_values['CUSTOM_4'] 		= mysql_decode($row['CUSTOM_4']);
	$glb_arr_values['CUSTOM_5'] 		= mysql_decode($row['CUSTOM_5']);
	$glb_arr_values['CUSTOM_6'] 		= mysql_decode($row['CUSTOM_6']);
	mysql_free_result($result);	
}

/**************************************** 
	SQL Generation functions
*****************************************/
/*
	Generates select SQL for product
*/	
function generate_select_product_sql($product_id)
{
	$select_sql = " SELECT product.PRODUCT_ID, product.PRODUCT_TYPE_ID, 
					product_type.NAME AS PRODUCT_TYPE_NAME, 
					product.PRODUCT_CODE, product.NAME, product.LABEL_HANG_TAGS, 
					product.PACKING_INSTRUCTIONS, product.SHIPPING_INSTRUCTIONS, product.COMMENTS, 
					product.PIC_ATTACH_ID,
					product.FABRIC_WEIGHT, product.FW_UNIT_ID, fw_unit.ABBREV AS FW_UNIT_NAME,
					product.DIMENSIONS, product.DIM_UNIT_ID, dim_unit.ABBREV AS DIM_UNIT_NAME,
					product.PRODUCT_WEIGHT, product.PW_UNIT_ID, pw_unit.ABBREV  AS PW_UNIT_NAME,
					product.COLOURS,  product.PRINT_EMBROIDERY, 
					product.CUSTOM_1, product.CUSTOM_2, product.CUSTOM_3,
					product.CUSTOM_4,  product.CUSTOM_5,  product.CUSTOM_6
					FROM product 
					LEFT JOIN product_type ON product.PRODUCT_TYPE_ID = product_type.PRODUCT_TYPE_ID 
					LEFT JOIN unit AS fw_unit ON fw_unit.UNIT_ID    = product.FW_UNIT_ID
					LEFT JOIN unit AS dim_unit ON dim_unit.UNIT_ID = product.DIM_UNIT_ID
					LEFT JOIN unit AS pw_unit ON pw_unit.UNIT_ID   = product.PW_UNIT_ID 
					WHERE product.PRODUCT_ID = ".$product_id . " AND product.STATE = 'ACTIVE' " ;	
	return $select_sql;
}

/*
	Generates insert SQL for product
*/	
function generate_insert_product_sql()
{
	global $glb_arr_values;

	$insert_sql = "INSERT INTO product 
					(STATE, PRODUCT_TYPE_ID, PRODUCT_CODE, NAME, LABEL_HANG_TAGS,
					PACKING_INSTRUCTIONS, SHIPPING_INSTRUCTIONS, COMMENTS, FABRIC_WEIGHT,
					FW_UNIT_ID, DIMENSIONS, DIM_UNIT_ID, PRODUCT_WEIGHT, PW_UNIT_ID,
					COLOURS, PRINT_EMBROIDERY, CUSTOM_1,
					CUSTOM_2, CUSTOM_3, CUSTOM_4, CUSTOM_5, CUSTOM_6 )
					VALUES 
					('ACTIVE',
					".$glb_arr_values['PRODUCT_TYPE_ID'].",
					'".mysql_encode(get_new_code("PRODUCT"))."',
					'".mysql_encode($glb_arr_values['NAME'])."',					
					'".mysql_encode($glb_arr_values['LABEL_HANG_TAGS'])."',
					'".mysql_encode($glb_arr_values['PACKING_INSTRUCTIONS'])."',
					'".mysql_encode($glb_arr_values['SHIPPING_INSTRUCTIONS'])."',					
					'".mysql_encode($glb_arr_values['COMMENTS'])."',
					'".mysql_encode($glb_arr_values['FABRIC_WEIGHT'])."',
					".$glb_arr_values['FW_UNIT_ID'].",
					'".mysql_encode($glb_arr_values['DIMENSIONS'])."',
					".$glb_arr_values['DIM_UNIT_ID'].",
					'".mysql_encode($glb_arr_values['PRODUCT_WEIGHT'])."',
					".$glb_arr_values['PW_UNIT_ID'].",
					'".mysql_encode($glb_arr_values['COLOURS'])."',
					'".mysql_encode($glb_arr_values['PRINT_EMBROIDERY'])."',
					'".mysql_encode($glb_arr_values['CUSTOM_1'])."',
					'".mysql_encode($glb_arr_values['CUSTOM_2'])."',
					'".mysql_encode($glb_arr_values['CUSTOM_3'])."',
					'".mysql_encode($glb_arr_values['CUSTOM_4'])."',
					'".mysql_encode($glb_arr_values['CUSTOM_5'])."',					
					'".mysql_encode($glb_arr_values['CUSTOM_6'])."')";
	return $insert_sql;
}

/*
*/
function generate_insert_product_into_sin_order($single_order_id, $product_id)
{
	$update_sql =  "UPDATE single_order SET
					PRODUCT_ID = ". $product_id . "
					WHERE SINGLE_ORDER_ID = " . $single_order_id;		
	return $update_sql;				
}
/*
*/
function generate_insert_pic_attach_id($product_id, $pic_attach_id)
{
	$update_sql =  "UPDATE product SET
					PIC_ATTACH_ID = ". $pic_attach_id . "
					WHERE PRODUCT_ID = " . $product_id;		
	return $update_sql;				
}
/*
	Generate SQL needed to insert product pic into attachment table in DB
	Note: MySQL's LOAD_FILE() was being used to load the content of the file but found
	      that when the system is deployed at an ISP/ASP, MySQL's File_privilege might 
		  not be granted.	
*/
function generate_insert_product_pic_sql($product_id)
{
	global $table_name, $uploaded_file, $type, $size, $full_path_name ;
	
	$obj_handle    = fopen($full_path_name, "rb");
	$file_content  = fread($obj_handle , filesize( $full_path_name));
	fclose($obj_handle);
	
	$sql = "INSERT INTO attachment (state, table_name, row_id, filename, description, type, 
									size, date_created, data) 
			VALUES ("; 
	$sql .= mysql_encode_w_quotes ('ACTIVE') . ", " ;
	$sql .= mysql_encode_w_quotes ($table_name) . ", " ;
	$sql .= $product_id . "," ;
	$sql .= mysql_encode_w_quotes ($uploaded_file) . ", " ;
	$sql .= "'Product Picture', " ;
	$sql .= mysql_encode_w_quotes ($type) . ", " ;
	$sql .= $size .", ";
	$sql .= "NOW(), ";
	$sql .= mysql_encode_w_quotes($file_content) .")";	
	return $sql;
}
/*
*/
function generate_update_product_pic_sql ($pic_attach_id)
{
	global $table_name, $uploaded_file, $type, $size, $full_path_name ;
	
	$sql = "UPDATE attachment SET 	
			FILENAME = ".mysql_encode_w_quotes ($uploaded_file) .", 
			TYPE = " .mysql_encode_w_quotes ($type) . ",
			SIZE = ". $size . ",
			DATE_CREATED = NOW() ,
			DATA = "."load_file(\"".$full_path_name ."\")			
			WHERE ATTACHMENT_ID = ". $pic_attach_id;			
	return $sql;
}
/*
	Generates update SQL for product
*/	
	
function generate_update_product_sql($product_id)
{
	global $glb_arr_values;
	
	$update_sql =  "UPDATE product SET
					
					PRODUCT_TYPE_ID = ". $glb_arr_values['PRODUCT_TYPE_ID']. ",
					NAME = '" .mysql_encode( $glb_arr_values['NAME'])."',
					LABEL_HANG_TAGS = '" .mysql_encode( $glb_arr_values['LABEL_HANG_TAGS'])."',
					PACKING_INSTRUCTIONS = '" .mysql_encode( $glb_arr_values['PACKING_INSTRUCTIONS'])."',
					SHIPPING_INSTRUCTIONS = '" .mysql_encode( $glb_arr_values['SHIPPING_INSTRUCTIONS'])."',
					COMMENTS = '" .mysql_encode( $glb_arr_values['COMMENTS'])."',
					FABRIC_WEIGHT = '" .mysql_encode( $glb_arr_values['FABRIC_WEIGHT'])."',
					FW_UNIT_ID = ". $glb_arr_values['FW_UNIT_ID']. ",					
					DIMENSIONS = '" .mysql_encode( $glb_arr_values['DIMENSIONS'])."',
					DIM_UNIT_ID = ". $glb_arr_values['DIM_UNIT_ID']. ",										
					PRODUCT_WEIGHT = '" .mysql_encode( $glb_arr_values['PRODUCT_WEIGHT'])."',
					PW_UNIT_ID = ". $glb_arr_values['PW_UNIT_ID']. ",										
					COLOURS = '" .mysql_encode( $glb_arr_values['COLOURS'])."',
					PRINT_EMBROIDERY = '" .mysql_encode( $glb_arr_values['PRINT_EMBROIDERY'])."',
					CUSTOM_1 = '" .mysql_encode( $glb_arr_values['CUSTOM_1'])."',
					CUSTOM_2 = '" .mysql_encode( $glb_arr_values['CUSTOM_2'])."',
					CUSTOM_3 = '" .mysql_encode( $glb_arr_values['CUSTOM_3'])."',
					CUSTOM_4 = '" .mysql_encode( $glb_arr_values['CUSTOM_4'])."',
					CUSTOM_5 = '" .mysql_encode( $glb_arr_values['CUSTOM_5'])."',
					CUSTOM_6 = '" .mysql_encode( $glb_arr_values['CUSTOM_6'])."' 
				
					WHERE PRODUCT_ID=".$product_id;
	
	return $update_sql;
}

/**************************************** 
	XHTML Generation functions
*****************************************/
/*
	Generates form header for product
*/
function generate_form_header() 
{
	global $glb_arr_values, $state, $single_order_id, $product_id, $sub_entity_op, $sub_entity_name, $sub_entity_id;
	
	// Get max file size from php.ini. currently set to 8 MB
	// Strip the trailing 'M' then convert to bytes 
	$max_file_size_bytes = substr(ini_get('post_max_size'), 0, -1) * 1024 * 1024;
	
	$str_frm_header  = "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" name=\"frm_product\" enctype=\"multipart/form-data\">\r\n" ;
	$str_frm_header .= "<input type=\"hidden\" id=\"state\" name=\"state\" value=\"" . $state . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"PRODUCT_ID\" name=\"PRODUCT_ID\" value=\"" . $product_id . "\"/>\r\n";	
	$str_frm_header .= "<input type=\"hidden\" id=\"PRODUCT_CODE\" name=\"PRODUCT_CODE\" value=\"" . $glb_arr_values['PRODUCT_CODE'] . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"SINGLE_ORDER_ID\" name=\"SINGLE_ORDER_ID\" value=\"" . $single_order_id . "\"/>\r\n";		
	$str_frm_header .= "<input type=\"hidden\" id=\"PIC_ATTACH_ID\" name=\"PIC_ATTACH_ID\" value=\"" . $glb_arr_values['PIC_ATTACH_ID'] . "\"/>\r\n";		
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_op\" name=\"sub_entity_op\" value=\"" . $sub_entity_op . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_name\" name=\"sub_entity_name\" value=\"" . $sub_entity_name . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"sub_entity_id\" name=\"sub_entity_id\" value=\"" . $sub_entity_id . "\"/>\r\n";
	$str_frm_header .= "<input type=\"hidden\" id=\"MAX_FILE_SIZE\" name=\"MAX_FILE_SIZE\" value=\"".$max_file_size_bytes."\"/>"; 
	return $str_frm_header;
}

$page_title = format_sin_order_name($single_order_id) . " :: Product"; 
require_once("body_header.php"); 
?>
<!-- JavaScript Section -->
<script type="text/javascript"><!--

function entitySave() 
{
	frm = document.frm_product;		
	if (frm.NAME.value.length < 1 ) {
		alert ("You must enter a name for this product");
		return;
	} 
	if (frm.PRODUCT_TYPE_ID.value <= 0) {
		alert ("You must choose a product type for this product");
		return;
	}
	frm.state.value = statePROCESS;
	frm.submit();	
}

function entityEdit() 
{
	frm = document.frm_product;		
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
	frm = document.frm_product;	
	if (frm.PRODUCT_ID.value == 0) {
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
	if (confirm ('Are you sure you want to delete this attachment?') ) {
		frm = document.frm_product;		
		frm.state.value			  = statePROCESS;
		frm.sub_entity_op.value   = opcodeDELETE;
		frm.sub_entity_name.value = strName;
		frm.sub_entity_id.value   = intID;		
		frm.submit();	
	}
}

function resubmitForm() 
{	
	frm = document.frm_product;	
	frm.state.value = stateEDIT;
	frm.submit();	
}
--></script>
<table border="0"  cellpadding="0" cellspacing="0">
	<tr>
		<td colspan="3">
			<h4>
			<?= "SINGLE ORDER" ." :: ". format_sin_order_name($single_order_id); ?>
			</h4>
		</td>
	</tr>
	<tr>  <!-- Tabs -->
		<td class = "tabs" colspan="3">
			<table border="0" cellspacing="0"> 
				<tr>
				<?php 
				/*	Tab Generation 
					Pass the ordinal of the tab to be activated
				*/												
				echo generate_single_order_tabs(2, $single_order_id);																
				?>						
				</tr>
			</table> 					
		</td>
	</tr> <!-- End of Tabs -->			
	<tr>
		<td colspan = "3"> 
			<table border="0" class="tab_content">	
				<tr><td colspan="3">
					<table border="0">
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
							if ( $state == state_VIEW) { 																	
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
						<tr></tr>
					</table>							
				</td></tr>
				<?php
				if ($state != state_NEW) {
				?>
				<tr><td><strong>Product Code:</strong></td>
				<td>
				<?php 				
					echo $glb_arr_values['PRODUCT_CODE'];
				?>				
				</td></tr>				
				<?php 
				}
				?>
				<tr><td width="20%"><strong>Product Type:</strong><?= generate_required_superscript($state);?></td>
				<td width="30%">
				<?php 
				if ($state == state_VIEW ) {
					echo $glb_arr_values["PRODUCT_TYPE_NAME"];
				}
				else{								
					echo generate_dd_prod_type_input ($glb_arr_values["PRODUCT_TYPE_ID"]);								
				}
				?> 
				<?php if (isset($errors['PRODUCT_TYPE_ID'])) echo "<br><em class=\"error\">" . $errors['PRODUCT_TYPE_ID'] . "</em>"; ?>				
				</td>
				<td align="left" rowspan="7">
					<table border="0">
					<tr>
					<td></td>
					<td>
					<?php 
					if ($glb_arr_values['PIC_ATTACH_ID'] > 0 ) {
					?>
					<a href="javascript:void window.open('img_popup.php?pic_id=<?=$glb_arr_values['PIC_ATTACH_ID']?>','productPic',
					'menubar=no,toolbar=no,scrollbars=yes,width=150,height=150,resizable=yes');"> 
					 <img src="get_pic.php?file_id=<?=$glb_arr_values['PIC_ATTACH_ID']?>" align="left" valign="top" alt="Product Picture" class="picture_small" ></a>								
					<?php }  
					else { ?>							
					<img src="images/no_image.jpg" alt="No Product Pic Selected" class="picture_small" >							
					<?php } ?> 
					</td>
					</tr>
					<?php 
					if ($state != state_VIEW ) { ?>
					<tr>					
					<td style="text-align:right;"><strong>Product Pic:</strong></td>
					<td>
					<input class="filebrowse" type="file" name="file_name"/>						
					</td>
					</tr>
					<?php } ?>
				</table>
				</td>
				</tr>
				<tr><td><strong>Product Name:</strong><?= generate_required_superscript($state);?></td>
				<td>
				<?php 
				if ($state == state_VIEW ) {
					echo $glb_arr_values['NAME'];
				}
				else{		
					echo generate_text_input ('formtext_stdwidth', 'NAME', $glb_arr_values['NAME']);	
				}
				?> 
				<?php if (isset($errors['NAME'])) echo "<br><em class=\"error\">" . $errors['NAME'] . "</em>"; ?>
				</td></tr>		
				<tr><td><strong>Fabric Weight:</strong></td>
				<td>
				<?php 
				if ($state == state_VIEW ) {
					echo $glb_arr_values['FABRIC_WEIGHT'] . " " .$glb_arr_values['FW_UNIT_NAME'];
				}
				else{		
					echo generate_text_input ('formtext_stdwidth', 'FABRIC_WEIGHT', $glb_arr_values['FABRIC_WEIGHT']);	
					echo generate_unit_input('FW_UNIT_ID',$glb_arr_values['FW_UNIT_ID']);						
				}
				
				?> 
				</td></tr>				
				<tr><td><strong>Product Weight:</strong></td>
				<td>
				<?php 
				if ($state == state_VIEW ) {
					echo $glb_arr_values['PRODUCT_WEIGHT']. "&nbsp;" .$glb_arr_values['PW_UNIT_NAME'];;
				}
				else{		
					echo generate_text_input ('formtext_stdwidth', 'PRODUCT_WEIGHT', $glb_arr_values['PRODUCT_WEIGHT']);	
					echo generate_unit_input('PW_UNIT_ID', $glb_arr_values['PW_UNIT_ID']);						
				}
				?>
				</td></tr>	
				<tr><td><strong>Dimensions:</strong></td>
				<td>
				<?php 
				if ($state == state_VIEW ) {
					echo $glb_arr_values['DIMENSIONS'] . " " .$glb_arr_values['DIM_UNIT_NAME'];;
				}
				else{		
					echo generate_text_input ('formtext_stdwidth', 'DIMENSIONS', $glb_arr_values['DIMENSIONS']);
					echo generate_unit_input('DIM_UNIT_ID', $glb_arr_values['DIM_UNIT_ID']);						
				}
				?> 
				</td></tr>	
				<tr><td><strong>Colours:</strong></td>
				<td>
				<?php 
				if ($state == state_VIEW ) {
					echo $glb_arr_values['COLOURS'];
				}
				else{		
					echo generate_text_input ('formtext_longwidth', 'COLOURS', $glb_arr_values['COLOURS']);	
				}
				?>
				</td></tr>													
				<tr><td><strong>Print/Embroidery:</strong></td>
				<td>
				<?php 
				if ($state == state_VIEW ) {
					echo $glb_arr_values['PRINT_EMBROIDERY'];
				}
				else{		
					echo generate_text_input ('formtext_longwidth', 'PRINT_EMBROIDERY', $glb_arr_values['PRINT_EMBROIDERY']);	
				}
				?>
				</td>				
				</tr>	
				<tr><td><strong>Labels and Hang Tags:</strong></td>
				<td>
				<?php 
				if ($state == state_VIEW ) {
					echo $glb_arr_values['LABEL_HANG_TAGS'];
				}
				else{		
					echo generate_text_input ('formtext_longwidth', 'LABEL_HANG_TAGS', $glb_arr_values['LABEL_HANG_TAGS']);	
				}
				?>
				</td></tr>													
				<tr><td valign="top"><strong>Packing Instructions:</strong></td>
				<td colspan="2">
				<?php 
				if ($state == state_VIEW ) {
					echo '<span class="contrack_notes">';
					echo $glb_arr_values['PACKING_INSTRUCTIONS'];
					echo '</span>';
				}
				else{		
					echo generate_textarea_input ('PACKING_INSTRUCTIONS', 5, 70, $glb_arr_values['PACKING_INSTRUCTIONS']);	
				}
				?>
				</td></tr>																			
				<tr><td valign="top"><strong>Shipping Instructions:</strong></td>
				<td colspan="2">
				<?php 
				if ($state == state_VIEW ) {
					echo '<span class="contrack_notes">';
					echo $glb_arr_values['SHIPPING_INSTRUCTIONS'];
					echo '</span>';
				}
				else{		
					echo generate_textarea_input ('SHIPPING_INSTRUCTIONS', 5, 70, $glb_arr_values['SHIPPING_INSTRUCTIONS']);	
				}
				?>
				</td></tr>																															
				<tr><td valign="top"><strong>Comments:</strong></td>
				<td colspan="2" >
				<?php 
				if ($state == state_VIEW ) {
					echo '<span class="contrack_notes">';
					echo $glb_arr_values['COMMENTS'];
					echo '</span>';
				}
				else{		
					echo generate_textarea_input ('COMMENTS', 5, 70, $glb_arr_values['COMMENTS']);	
				}
				?>
				</td></tr>						
				<tr><td><strong>Custom 1:</strong></td>
				<td>
				<?php 
				if ($state == state_VIEW ) {
					echo $glb_arr_values['CUSTOM_1'];
				}
				else{		
					echo generate_text_input ('formtext_longwidth', 'CUSTOM_1', $glb_arr_values['CUSTOM_1']);	
				}
				?></td>
				</tr>
				<tr><td><strong>Custom 2:</strong></td>
				<td>
				<?php 
				if ($state == state_VIEW ) {
					echo $glb_arr_values['CUSTOM_2'];
				}
				else{		
					echo generate_text_input ('formtext_longwidth', 'CUSTOM_2', $glb_arr_values['CUSTOM_2']);	
				}
				?></td>
				</tr>		
				<tr><td><strong>Custom 3:</strong></td>
				<td>
				<?php 
				if ($state == state_VIEW ) {
					echo $glb_arr_values['CUSTOM_3'];
				}
				else{		
					echo generate_text_input ('formtext_longwidth', 'CUSTOM_3', $glb_arr_values['CUSTOM_3']);	
				}
				?></td>
				</tr>		
				<tr><td><strong>Custom 4:</strong></td>
				<td>
				<?php 
				if ($state == state_VIEW ) {
					echo $glb_arr_values['CUSTOM_4'];
				}
				else{		
					echo generate_text_input ('formtext_longwidth', 'CUSTOM_4', $glb_arr_values['CUSTOM_4']);	
				}
				?></td>
				</tr>		
				<tr><td><strong>Custom 5:</strong></td>
				<td>
				<?php 
				if ($state == state_VIEW ) {
					echo $glb_arr_values['CUSTOM_5'];
				}
				else{		
					echo generate_text_input ('formtext_longwidth', 'CUSTOM_5', $glb_arr_values['CUSTOM_5']);	
				}
				?></td>
				</tr>		
				<tr><td><strong>Custom 6:</strong></td>
				<td>
				<?php 
				if ($state == state_VIEW ) {
					echo $glb_arr_values['CUSTOM_6'];
				}
				else{		
					echo generate_text_input ('formtext_longwidth', 'CUSTOM_6', $glb_arr_values['CUSTOM_6']);	
				}
				?></td>
				</tr>
				<?php							
					echo show_attachments($table_name, $product_id, ' AND ATTACHMENT_ID <> '.$glb_arr_values['PIC_ATTACH_ID'] );
				?>
				</form>
			</table>					
	</tr>								
</table>
<?php
	include("body_footer.php"); 
?>
