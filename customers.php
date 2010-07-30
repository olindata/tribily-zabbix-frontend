<?php
/*
** ZABBIX
** Copyright (C) 2000-2010 SIA Zabbix
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
**/
?>
<?php
include_once('include/config.inc.php');
require_once('include/customer.inc.php');
require_once('include/forms.inc.php');
require_once('include/users.inc.php');

$page['title'] = 'S_CUSTOMERS';
$page['file'] = 'customers.php';
$page['hist_arg'] = array('form', 'customerid');

include_once('include/page_header.php');

?>
<?php

//---------------------------------- CHECKS ------------------------------------
//		VAR                                         TYPE	OPTIONAL FLAGS  VALIDATION	EXCEPTION
$fields=array(
// media form
		'customers'=>		array(T_ZBX_INT,    O_OPT,	P_SYS,          DB_ID,          NULL),
		'customerid'=>		array(T_ZBX_INT,    O_NO,	P_SYS,          DB_ID,          '(isset({form})&&({form}=="update"))'),
		'customertypeid'=>	array(T_ZBX_INT,    O_OPT,	NULL,           IN(implode(',',array(CUSTOMER_TYPE_FREE,CUSTOMER_TYPE_PREPAID,CUSTOMER_TYPE_SUBSCRIPTION))),'(isset({save}))'),
		'name'=>		array(T_ZBX_STR,    O_OPT,	NULL,           NOT_EMPTY,      'isset({save})'),
		'saldo'=>		array(T_ZBX_DBL,    O_OPT,	NULL,           NOT_EMPTY,      'isset({save})'),
		'email'=>		array(T_ZBX_STR,    O_OPT,	NULL,           NOT_EMPTY,      'isset({save})'),
/* actions */
		'save'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'delete'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'cancel'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'go'=>					array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, NULL, NULL),
/* other */
		'form'=>		array(T_ZBX_STR, O_OPT, P_SYS,	NULL,	NULL),
		'form_refresh'=>	array(T_ZBX_INT, O_OPT,	NULL,	NULL,	NULL)
);

check_fields($fields);

$_REQUEST['go'] = get_request('go','none');

validate_sort_and_sortorder('name',ZBX_SORT_UP);
?>
<?php

/* CUSTOMER ACTIONS */
	$_REQUEST['go'] = get_request('go', 'none');

	$result = 0;
	if(isset($_REQUEST['save'])){
		if(isset($_REQUEST['customerid'])){
/* UPDATE */
			$result=update_customer($_REQUEST['customerid'], $_REQUEST['name'],$_REQUEST['email'],get_request('customertypeid'),
				get_request('saldo'));

			show_messages($result, S_CUSTOMER_UPDATED, S_CUSTOMER_WAS_NOT_UPDATED);
		}
		else{
/* ADD */
			$result=add_customer(
				$_REQUEST['name'],$_REQUEST['email'],get_request('customertypeid'),
				get_request('saldo'));

			show_messages($result, S_ADDED_NEW_CUSTOMER, S_NEW_CUSTOMER_WAS_NOT_ADDED);
		}
		if($result){
			unset($_REQUEST['form']);
		}
	}
	elseif(isset($_REQUEST['delete'])&&isset($_REQUEST['customerid'])){
		$result=delete_customer($_REQUEST['customerid']);
		show_messages($result, S_CUSTOMER_DELETED, S_CUSTOMER_WAS_NOT_DELETED);
		if($result)
		{
			unset($_REQUEST['form']);
		}
	}

	else if($_REQUEST['go'] == 'delete'){
		$go_result = true;
		$customers = get_request('customers', array());

		DBstart();
		foreach($customers as $customerid){
			$go_result &= delete_customer($customerid);
			if(!$go_result) break;
		}
		$go_result = DBend($go_result);

		if($go_result){
			unset($_REQUEST['form']);
		}

		show_messages($go_result, S_CUSTOMER_DELETED, S_CUSTOMER_WAS_NOT_DELETED);
	}

	if(($_REQUEST['go'] != 'none') && isset($go_result) && $go_result){
		$url = new CUrl();
		$path = $url->getPath();
		insert_js('cookie.eraseArray("'.$path.'")');
	}

?>

<?php
	$customers_wdgt = new CWidget();

	$form = new CForm();
	$form->setMethod('get');

	$form->addItem(new CButton('form',S_CREATE_CUSTOMER));

	$customers_wdgt->addPageHeader(S_CUSTOMERS_BIG, $form);

?>

<?php
	if(isset($_REQUEST['form'])){

		$customers_wdgt->addItem(insert_customer_form());
	}
	else{
		$numrows = new CDiv();
		$numrows->setAttribute('name','numrows');

		$customers_wdgt->addHeader(S_CUSTOMERS_BIG);
//		$customers_wdgt->addHeader($numrows);

		$form = new CForm();
		$form->setName('frm_customers');

		$table=new CTableInfo(S_NO_CUSTOMERS_DEFINED);
		$table->setHeader(array(
			new CCheckBox('all_customers',NULL,"checkAll('".$form->getName()."','all_customers','customers');"),
			make_sorting_link(S_TYPE,'c.customertypeid'),
			make_sorting_link(S_NAME,'c.name'),
			make_sorting_link(S_EMAIL,'c.email'),
			make_sorting_link(S_SALDO,'c.saldo'),
		));

// sorting
//		order_page_result($proxies, 'description');

// PAGING UPPER
		$paging = BR();
//		$paging = getPagingLine($proxies);
		$customers_wdgt->addItem($paging);
//---------

		$sql = 'SELECT c.customerid, c.name, c.email, c.saldo, c.customertypeid, c.createddate'.
				' FROM customers c'.
				' WHERE '.DBin_node('c.customerid').
				order_by('c.customertypeid,c.name');
		$result=DBselect($sql);
		while($row=DBfetch($result)){
			$table->addRow(array(
				new CCheckBox('customers['.$row['customerid'].']',NULL,NULL,$row['customerid']),
				customertypeid2str($row['customertypeid']),
				new CLink($row['name'],'?&form=update&customerid='.$row['customerid']),
                                new CLink($row['email'],'?&form=update&customerid='.$row['customerid']),
				new CLink($row['saldo'],'?&form=update&customerid='.$row['customerid'])
				));
		}

// PAGING FOOTER
		$table->addRow(new CCol($paging));
//		$items_wdgt->addItem($paging);
//---------

//----- GO ------
		$goBox = new CComboBox('go');
		$goBox->addItem('delete', S_DELETE_SELECTED);

// goButton name is necessary!!!
		$goButton = new CButton('goButton',S_GO.' (0)');
		$goButton->setAttribute('id','goButton');

		$jsLocale = array(
			'S_CLOSE',
			'S_NO_ELEMENTS_SELECTED'
		);

		zbx_addJSLocale($jsLocale);

		zbx_add_post_js('chkbxRange.pageGoName = "customers";');

		$table->setFooter(new CCol(array($goBox, $goButton)));

		$form->addItem($table);

		$customers_wdgt->addItem($form);
	}

	$customers_wdgt->show();
?>

<?php

include_once('include/page_footer.php');
?>