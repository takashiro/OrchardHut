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

class CpModule extends AdminControlPanelModule{

	public function getAlias(){
		return 'public';
	}

	public function editAction(){
		if($_POST){
			global $_G;

			if(isset($_POST['nickname'])){
				$_G['admin']->nickname = $_POST['nickname'];
			}

			if(isset($_POST['realname'])){
				$_G['admin']->realname = $_POST['realname'];
			}

			if(isset($_POST['mobile'])){
				$_G['admin']->mobile = $_POST['mobile'];
			}

			if(!empty($_POST['password'])){
				if(empty($_POST['password2']) || $_POST['password'] != $_POST['password2']){
					showmsg('two_different_passwords', 'back');
				}

				if(!isset($_POST['old_password'])){
					showmsg('password_modifying_require_old_password', 'back');
				}

				$result = $_G['admin']->changePassword($_POST['old_password'], $_POST['password']);
				if($result === -1){
					showmsg('incorrect_old_password', 'back');
				}
			}

			showmsg('successfully_update_profile', 'refresh');
		}

		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);
		$_ADMIN = $_G['admin']->toArray();
		include view('memcp_edit');
	}

	public function logoutAction(){
		global $_G;

		$_G['admin']->logout();
		redirect('admin.php');
	}

}

?>
