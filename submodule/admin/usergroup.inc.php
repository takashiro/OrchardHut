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

class UserGroupModule extends AdminControlPanelModule{

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$table = $db->select_table('usergroup');
		$usergroups = $table->fetch_all('*', '1 ORDER BY type,minordernum,maxordernum');

		include view('usergroup_list');
	}

	public function editAction(){
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		$attrs = array('name', 'type', 'minordernum', 'maxordernum');
		$usergroup = new UserGroup($id);
		foreach($attrs as $var){
			if(isset($_POST[$var])){
				$usergroup->$var = trim($_POST[$var]);
			}
		}
		if(!$usergroup->exists())
			$usergroup->insert();
		echo json_encode($usergroup->toReadable());
		exit;
	}

	public function deleteAction(){
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		if($id <= 0)
			exit;

		UserGroup::Delete($id);
		global $db;
		echo $db->affected_rows;
		exit;
	}

	public function refreshAllAction(){
		global $db, $page, $mod_url;

		$limit = 100;
		$offset = ($page - 1) * $limit;

		$table = $db->select_table('user');
		$user = $table->fetch_all('id', "1 LIMIT $offset,$limit");
		if($user){
			foreach($user as $u){
				UserGroup::RefreshUser($u['id']);
			}

			$page++;
			showmsg('processing_all_usergroups', $mod_url.'&action=refreshAll&page='.$page);
		}

		showmsg('succesfully_refresh_all_usergroup', $mod_url);
	}

}

?>
