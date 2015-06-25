<?php

/********************************************************************
 Copyright (c) 2013-2015 - Kazuichi Takashiro

 This file is part of Orchard Hut.

 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.

 takashiro@qq.com
*********************************************************************/

if(!defined('IN_ADMINCP')) exit('access denied');

class MailModule extends AdminControlPanelModule{

	public function getAlias(){
		return 'system';
	}

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$mailconfig = Mail::$Config;

		if($_POST){
			foreach($mailconfig as $var => $oldvalue){
				if(isset($_POST[$var])){
					$mailconfig[$var] = trim($_POST[$var]);
				}
			}

			writedata('mailconfig', $mailconfig);

			showmsg('successfully_updated_system_config', 'back');
		}

		include view('mail');
	}

}

?>
