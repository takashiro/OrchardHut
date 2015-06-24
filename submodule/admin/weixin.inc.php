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

$type = !empty($_GET['type']) ? $_GET['type'] : '';

switch($type){
case 'menu':
	$wx = new WeixinAPI;
	if(isset($_POST['button'])){
		$button = str_replace('&quot;', '"', $_POST['button']);
		$button = stripslashes($button);
		$button = json_decode($button);
		$menu = array(
			'button' => $button,
		);

		$wx->setMenu($menu);
	}elseif(!empty($_GET['clear'])){
		$wx->setMenu(NULL);
	}else{
		$menu = $wx->getMenu();
	}
	break;

case 'autoreply':
	$table = $db->select_table('autoreply');

	$action = &$_GET['action'];
	switch($action){
	case 'edit':
		$autoreply = array();

		if(!empty($_POST['keyword'])){
			$autoreply['keyword'] = $_POST['keyword'];
			$autoreply['keyword'] = explode("\n", $autoreply['keyword']);
			foreach ($autoreply['keyword'] as &$word) {
				$word = trim($word);
			}
			unset($word);
			$autoreply['keyword'] = implode("\n", $autoreply['keyword']);
		}

		if(!empty($_POST['reply'])){
			$autoreply['reply'] = trim($_POST['reply']);
		}

		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		if($id > 0){
			$table->update($autoreply, 'id='.$id);
			$autoreply['id'] = $id;
		}else{
			$table->insert($autoreply);
			$autoreply['id'] = $table->insert_id();
		}

		Autoreply::RefreshCache();

		echo json_encode($autoreply);
		exit;

	case 'delete':
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		if($id > 0){
			Autoreply::RefreshCache();

			$table->delete('id='.$id);
			echo $db->affected_rows;
		}else{
			echo 0;
		}
		exit;

	default:
		$autoreply = $table->fetch_all('*');
	}
	break;

default:
	$type = 'config';

	$wxconnect = readdata('wxconnect');
	$wxconnect_fields = array('app_id', 'app_secret', 'account', 'token', 'subscribe_text', 'entershop_keyword', 'bind_keyword', 'bind2_keyword');

	if($_POST){
		foreach($wxconnect_fields as $var){
			$wxconnect[$var] = isset($_POST['wxconnect'][$var]) ? $_POST['wxconnect'][$var] : '';
		}
		writedata('wxconnect', $wxconnect);
		showmsg('successfully_updated_wxconnect_config', 'refresh');
	}

	foreach($wxconnect_fields as $var){
		isset($wxconnect[$var]) || $wxconnect[$var] = '';
	}
}

include view('weixin_'.$type);

?>
