<?php

/***********************************************************************
Elf Web App
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

require_once '../../../../core/init.inc.php';

if(empty($_POST['sign'])){
	exit('signature is required');
}

$data = $_POST;
$sign = $_POST['sign'];
unset($data['sign']);

ksort($data);
$strs = array();
foreach($data as $key => $value){
	$strs[] = $key.'='.$value;
}
$strs = implode('&', $strs);

$config = readdata('download');
if(md5($strs.'&key='.$config['deploy_key']) != $sign){
	exit('signature is invalid');
}

if(isset($data['version'])){
	$data = array(
		'version' => $data['version'],
		'changelog' => $data['changelog'],
		'timestamp' => TIMESTAMP,
	);
	writedata('download_release', $data);
	echo 'succeed';
}else{
	echo 'fail';
}
