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

class GeTuiConfigModule extends AdminControlPanelModule{

	public function getRequiredPermissions(){
		return array('getui');
	}

	public function defaultAction(){
		$fields = array('app_id', 'app_key', 'master_secret', 'host');

		if($_POST){
			$config = array();
			foreach($fields as $f){
				$config[$f] = isset($_POST['getuiconfig'][$f]) ? trim($_POST['getuiconfig'][$f]) : '';
			}
			writedata('getuiconfig', $config);
			showmsg('edit_succeed', 'refresh');
		}

		$getuiconfig = readdata('getuiconfig');
		foreach($fields as $f){
			isset($getuiconfig[$f]) || $getuiconfig[$f] = '';
		}

		extract($GLOBALS, EXTR_REFS | EXTR_SKIP);
		include view('config');
	}

}

?>
