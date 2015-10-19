<?php

/***********************************************************************
Elf Web App Framework
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

if(empty($_GET['mod'])){
	header('Location: market.php');
	exit;
}else{
	$module = explode(':', $_GET['mod']);
	if(empty($module[0]) || !preg_match('/^\w+$/', $module[0])){
		exit('illegal module id');
	}

	if(empty($module[1]) || !preg_match('/^\w+$/', $module[1])){
		$module[1] = 'main';
	}

	$module_path = 'module/'.$module[0].'/'.$module[1].'.inc.php';
	if(file_exists($module_path)){
		require_once './core/init.inc.php';
		define('MOD_NAME', $module[0]);
		define('MOD_ROOT', S_ROOT.'module/'.$module[0].'/');
		require $module_path;
	}else{
		exit('illegal module id');
	}
}

?>
