	
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
	body_header page
	This page is included in all screens except popups. 
	It encapsulates the upper tool bar containing the search control, help links, 
	and the navigation drop down menus.
******************************************************************************************/

// Force Browser Cache Expiry to always fetch from server
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); 
header('Cache-Control: no-store, no-cache, must-revalidate'); 
header('Cache-Control: post-check=0, pre-check=0', FALSE); 
header('Pragma: no-cache'); 

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Contrack - <?= $page_title ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<script type="text/javascript" src="calendar/calendar.js"></script>
<script type="text/javascript" src="calendar/lang/calendar-en.js"></script>
<script type="text/javascript" src="calendar/calendar-setup.js"></script>
<link href="contrack_calendar.css" rel="stylesheet" type="text/css" media="all"/>
<link href="menu.css" rel="stylesheet" type="text/css" media="all"/>
<link href="contrack.css" rel="stylesheet" type="text/css" media="all"/>
<script src="utilities.js" type="text/javascript"></script>
<link rel="shortcut icon" href="images/favicon.ico">
</head>
<body>
<script type="text/javascript"><!--//--><![CDATA[//><!--

var sfHover = function() {
	var sfEls = document.getElementById("nav").getElementsByTagName("LI");
	for (var i=0; i<sfEls.length; i++) {
		sfEls[i].onmouseover=function() {
			this.className+=" sfhover";			
		}
		sfEls[i].onmouseout=function() {
			this.className=this.className.replace(new RegExp(" sfhover\\b"), "");			
		}
	}
}
if (window.attachEvent) window.attachEvent("onload", sfHover);

function keypress_search(evt) {
    evt = (evt) ? evt : ((window.event) ? event : null);
    if (evt) {
       var elem = (evt.target) ? evt.target : 
          ((evt.srcElement) ? evt.srcElement : null);
       if (elem) {
           var charCode = (evt.charCode) ? evt.charCode : 
               ((evt.which) ? evt.which : evt.keyCode);
		   if (13==charCode) {
		   		//location.href = document.getElementById("search_filter").value;
				alert ("Under Construction");
		   }
       }
    }
	return true;   
}

function select_search_view() {
	//location.href = document.getElementById("search_filter").value;
	alert ("Under Construction");
}

function open_help(page) {
	window.open(page,'contrackHelp','menubar=no,toolbar=no,scrollbars=yes,height=500,width=400');
}

//--><!]]></script>
<table cellspacing="1" cellpadding="5%" width="100%">
<tr>
	<td colspan="2" class="logo"><a href="dashboard.php"><img align="left" src="images/logo.gif" alt="Organization logo"/></a></td>
</tr>

<tr> 
	<td colspan="2" class="header">
		<table cellpadding="1">
			<td width="7%" style="border-right:#888 dashed 1px;"><?= date("d/m/Y"); ?></td>
			<td title="Change Password"><a href="javascript:void open_popup('pwd_change_popup.php?uid=<?= $user->getSessionValue('userid') ?>','pwdChange','menubar=no,toolbar=no,scrollbars=no',211,400);">
			<?= $user->getSessionValue('current_user') . " (". $user->getSessionValue('group_name'). ")" ?></a></td>
			<td style="border-right:#888 dashed 1px;border-left:#888 dashed 1px;text-align: center">
			Search for 
				<select name="search_filter" id="search_filter">
					<option value="search_gen_order.php">General Order</option>
					<option value="search_single_order.php">Single Order</option>
					<option value="search_product.php">Product</option>
					<option value="search_invoice.php">Invoice</option>
					<?php if ($user->getSessionValue('group_id') != contrack_BUYER) { ?>	
					<option value="search_buyer.php">Buyer</option>
					<?php } ?>
					<?php if ($user->getSessionValue('group_id') != contrack_SUPPLIER) { ?>	
					<option value="search_supplier.php">Supplier</option>
					<?php } ?>
					<?php if ($user->getSessionValue('group_id') != contrack_AGENT) { ?>	
					<option value="search_agent.php">Agent</option>
					<?php } ?>
					<option value="search_ext_invoice.php">External Invoice</option>
					<option value="search_contact.php">Contact</option>
				</select>
				<input class="formtext_stdwidth" type="text" onkeypress="return keypress_search(event);"/>
				<input type="button" class="contrack_button" value="Go" onclick="select_search_view()"/></td>
			<td width="5%" style="border-right:#888 dashed 1px;text-align:center"><a href="dashboard.php">Home</a></td>
			<td width="5%" style="border-right:#888 dashed 1px;text-align:center;">
			<a href="javascript:void open_help('help.php');" style="cursor:help;">Help</a></td>
			<td width="5%" style="border-right:#888 dashed 1px;text-align:center"><a href="about.php">About</a></td>
			<td width="5%" style="text-align:center;"><a href="logout.php">Logout</a></td>
		</table>
	</td>
</tr>

<tr>
	<td colspan="2" class="header">
	<ul id="nav">
	<?php if ($user->getSessionValue('show_menu_item_new'))  { ?> 
	<li><a href="#">New</a>
		<ul>
		<?php
			echo format_menu_item ("gen_order.php?state=".state_NEW,"New General Order");
			echo format_menu_item ("single_order.php?state=".state_NEW,"New Single Order");
			echo format_menu_item ("invoice.php?state=".state_NEW,"New Invoice");
			echo format_menu_item ("party.php?state=".state_NEW."&type=".entity_BUYER,"New Buyer");
			echo format_menu_item ("party.php?state=".state_NEW."&type=".entity_SUPPLIER,"New Supplier");
			echo format_menu_item ("party.php?state=".state_NEW."&type=".entity_AGENT,"New Agent");
			echo format_menu_item ("party.php?state=".state_NEW."&type=".entity_EXTERNAL,"New External Party");
		?>
		</ul>
	</li>
	<?php } ?>
	<li><a href="#">View</a>
		<ul>
		<?php
			if ($user->getSessionValue('group_id') != contrack_SUPPLIER) { 	
				echo format_menu_item ("search_order.php?entity=".entity_GEN_ORDER,"General Orders");
			}
			echo format_menu_item ("search_order.php?entity=".entity_SINGLE_ORDER,"Single Orders");
			echo format_menu_item ("search_invoice.php","Invoices");
			//echo format_menu_item ("uc.php","Products");						
			if ($user->getSessionValue('group_id') != contrack_BUYER) { 					
				echo format_menu_item ("search_party.php?entity=".entity_BUYER."\"","Buyers");
			 } 
			 if ($user->getSessionValue('group_id') != contrack_SUPPLIER) { 											
				echo format_menu_item ("search_party.php?entity=".entity_SUPPLIER."\"","Suppliers");
			 } 
			 if ($user->getSessionValue('group_id') != contrack_AGENT) { 											
				echo format_menu_item ("search_party.php?entity=".entity_AGENT."\"","Agents");
			 } 
			 if ( ($user->getSessionValue('group_id') == contrack_MGMT) || ($user->getSessionValue('group_id') == contrack_STAFF) ) { 		
				echo format_menu_item ("search_x_invoice.php","External Invoices");
				echo format_menu_item ("search_party.php?entity=".entity_EXTERNAL."\"","External Parties");						
			}
		?>
			<!--
			<li><a href="#">View All Entities >></a>
				<ul>
					
				</ul>
			</li>			
			<li><a href="#">Financial Reports</a></li>
			<li><a href="#">Operational Reports</a></li>
			<li><a href="#">Production Reports</a></li>
			<li><a href="#">HR Reports</a></li>
			-->
		</ul>
	</li>
	<?php if ($user->getSessionValue('allow_admin'))  { ?> 
	<li><a href="#">Administration</a>
		<ul>
			<li class=""><a href="adm_users.php">Account Management</a></li>
			<!-- <li class="active_menu_link"><a href="uc.php">Audit Trail</a></li> -->
			<li class=""><a href="adm_countries.php">Country Definitions</a></li>
			<li class=""><a href="adm_currencies.php">Currency Definitions</a></li>				
			<li class=""><a href="adm_prod_types.php">Product Type Definitions</a></li>
			<li class=""><a href="adm_units.php">Unit Definitions</a></li>
			<?php
			echo format_menu_item ("party.php?party_id=".COMPANY_PARTY_ID."&type=".entity_INTERNAL."&state=".state_VIEW,"Organization Profile");
			?>				
		</ul>
	</li>
	<?php } ?>
</ul>				
	</td>
</tr>
<tr>
	<td colspan="2">
