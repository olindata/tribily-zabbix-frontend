<?php
/*
** ZABBIX
** Copyright (C) 2000-2005 SIA Zabbix
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

	function	customertypeid2str($type)
	{
                $str_type= array();
		$str_type[CUSTOMER_TYPE_FREE]	= S_FREE;
		$str_type[CUSTOMER_TYPE_PREPAID]= S_PREPAID;
		$str_type[CUSTOMER_TYPE_SUBSCRIPTION]	= S_SUBSCRIPTION;
		
		if(isset($str_type[$type]))
			return $str_type[$type];

		return S_UNKNOWN;
	}

	function get_customer_by_customerid($customerid){
		$sql="select * from customers where customerid=$customerid";
		$result=DBselect($sql);
		$row=DBfetch($result);
		if($row){
			return	$row;
		}
		else{
			error(S_NO_CUSTOMER_WITH.SPACE."customerid=[$customerid]");
		}
	}

// Update customer
	function update_customer($customerid,$name,$email,$customertypeid,$saldo){
		$ret = 0;

                $customer_old = get_customer_by_customerid($customerid);

                $sql='UPDATE customers SET '.
                                'name='.zbx_dbstr($name).','.
                                'email='.zbx_dbstr($email).','.
                                'customertypeid='.zbx_dbstr($customertypeid).','.
                                'saldo='.zbx_dbstr($saldo).
                        ' WHERE customerid='.$customerid;
                $ret = DBexecute($sql);
                if($ret){
                        $customer_new = get_customer_by_customerid($customerid);
                        add_audit_ext(AUDIT_ACTION_UPDATE,
                                                        AUDIT_RESOURCE_CUSTOMER,
                                                        $customerid,
                                                        $customer_old['name'],
                                                        'customer',
                                                        $customer_old,
                                                        $customer_new);
                }
	return $ret;
	}

// Add Customer

	function add_customer($name,$email,$customertypeid,$saldo){
		$ret = 0;

                $customerid=get_dbid("customers","customerid");
                $sql='INSERT INTO customers (customerid, name, email, customertypeid, saldo) '.
                        " VALUES ($customerid,".zbx_dbstr($name).",".zbx_dbstr($email).",".
                                                zbx_dbstr($customertypeid).",".zbx_dbstr($saldo).")";
                $ret = DBexecute($sql);
                if($ret){
                        $ret = $customerid;
                        add_audit_ext(AUDIT_ACTION_ADD, AUDIT_RESOURCE_CUSTOMER, $customerid, $name, NULL, NULL, NULL);
                }
		return $ret;
	}

        // Delete customer
	function delete_customer($customerid){
		// TODO: delete related stuff?
//              delete_media_by_mediatypeid($customerid);
//		delete_alerts_by_mediatypeid($customerid);
		$customer = get_customer_by_customerid($customerid);

		$sql='DELETE FROM customers WHERE customerid='.$customerid;

                $ret = DBexecute($sql);
		if($ret){
			add_audit_ext(AUDIT_ACTION_DELETE, AUDIT_RESOURCE_CUSTOMER, $customerid, $customer['name'], NULL, NULL, NULL);
		}
	return $ret;
	}

?>
