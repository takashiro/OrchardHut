<?php

/***********************************************************************
Orchard Hut Online Shop
Copyright (C) 2013-2015  Kazuichi Takashiro

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.

takashiro@qq.com
************************************************************************/

if(!defined('IN_ADMINCP')) exit('access denied');

class OrderCommentModule extends AdminControlPanelModule{

	public function getRequiredPermissions(){
		return array('order');
	}

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP);

		$limit = 20;
		$offset = ($page - 1) * $limit;

		$condition = array();
		$query_string = array();

		$levelcondition = array();
		for($i = 1; $i <= 3; $i++){
			$var = 'minlevel'.$i;
			if(!empty($_REQUEST['minlevel'.$i])){
				$$var = intval($_REQUEST[$var]);
			}else{
				$$var = 1;
			}
			$levelsql = 'c.level'.$i.'>='.$$var;
			$query_string[$var] = $$var;

			$var = 'maxlevel'.$i;
			if(!empty($_REQUEST['maxlevel'.$i])){
				$$var = intval($_REQUEST[$var]);
			}else{
				$$var = 3;
			}
			$levelsql.= ' AND c.level'.$i.'<='.$$var;
			$query_string[$var] = $$var;

			$levelcondition[] = $levelsql;
		}
		$levelcondition = '('.implode(') OR (', $levelcondition).')';
		$condition[] = '('.$levelcondition.')';

		$condition = $condition ? implode(' AND ', $condition) : '1';

		$pagenum = $db->result_first("SELECT COUNT(*)
			FROM {$tpre}ordercomment c
				LEFT JOIN {$tpre}order o ON o.id=c.orderid
			WHERE $condition");

		$comments = $db->fetch_all("SELECT c.*,o.dateline AS orderdateline,o.mobile,o.addressee,o.addressid,o.extaddress
			FROM {$tpre}ordercomment c
				LEFT JOIN {$tpre}order o ON o.id=c.orderid
			WHERE $condition
			ORDER BY orderid DESC
			LIMIT $offset,$limit");

		$query_string = http_build_query($query_string);

		include view('comment');
	}

}
