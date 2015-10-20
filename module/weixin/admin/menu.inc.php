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

class WeixinMenuModule extends AdminControlPanelModule{

	public function getRequiredPermissions(){
		return array('weixin');
	}

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$wx = new WeixinAPI;

		$post_data = file_get_contents('php://input');
		if(!empty($post_data)){
			$button = json_decode($post_data);
			if($button){
				$menu = array(
					'button' => $button,
				);

				$wx->setMenu($menu);

				if($wx->hasError()){
					showmsg($wx->getErrorMessage(), 'back');
				}else{
					showmsg('edit_succeed', 'refresh');
				}
			}else{
				$wx->setMenu(NULL);
				showmsg('edit_succeed', 'refresh');
			}
		}else{
			$menu = $wx->getMenu();
		}

		$item_types = array();
		$types = array(
			'view',
			'click',
			'scancode_push',
			'scancode_waitmsg',
			'pic_sysphoto',
			'pic_photo_or_album',
			'pic_weixin',
			'location_select',
		);
		foreach($types as $type){
			$item_types[$type] = lang('weixin', 'weixin_menu_type_'.$type);
		}

		include view('menu');
	}

}

?>
