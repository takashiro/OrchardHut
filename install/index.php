<?php

define('S_ROOT', dirname(dirname(__FILE__)).'/');//网站根目录常量
error_reporting(0);//Debug

if(PHP_VERSION < '5.3'){
	exit('本系统要求PHP版本至少5.3');
}

function __autoload($classname){
	require_once S_ROOT.'./model/'.$classname.'.class.php';
}

function writeconfig($config, $value){
	file_put_contents(S_ROOT.'./data/'.$config.'.inc.php', '<?php return '.var_export($value, true).';?>');
}

require_once S_ROOT.'./core/global.func.php';

$_G = array();
@$_G['config'] = array_merge(@include S_ROOT.'./data/config.inc.php', @include S_ROOT.'./data/stconfig.inc.php');
@$_G['config']['db'] = include S_ROOT.'./data/dbconfig.inc.php';
$_G['style'] = 'admin';

if(file_exists(S_ROOT.'./data/install.lock')){
	rheader('Content-Type:text/html; charset=utf-8');
	exit('已经安装过本系统，要重新安装请删除./data/install.lock文件。');
}

if(!ini_get('short_open_tag')){  //ini_get — 获取一个配置选项的值
	exit('请先开启short open tag.');
}

if($_POST){
	$config = array(
		'timezone' => intval($_POST['config']['timezone']),
		'timefix' => 0,
		'cookiepre' => randomstr(3).'_',
		'charset' => 'utf-8',
		'sitename' => $_POST['config']['sitename'],
		'style' => 'default',
	);
	writeconfig('config', $config);
	writeconfig('config_bak_'.randomstr(3), $config);

	$stconfig = array(
		'salt' => randomstr(32),
	);
	writeconfig('stconfig', $stconfig);
	writeconfig('stconfig_bak_'.randomstr(3), $stconfig);

	$dbconfig = array(
		'type' => $_POST['db']['type'],
		'charset' => 'utf8',
		'host' => $_POST['db']['host'],
		'user' => $_POST['db']['user'],
		'tpre' => $_POST['db']['tpre'],
		'pw' => $_POST['db']['pw'],
		'name' => $_POST['db']['name'],
		'pconnect' => intval($_POST['db']['pconnect']),
	);
	writeconfig('dbconfig', $dbconfig);
	writeconfig('dbconfig_bak_'.randomstr(3), $dbconfig);

	$_G['config'] = array_merge($config, $stconfig);
	$_G['config']['db'] = $dbconfig;

	$db = new Mysql();
	$db->connect($dbconfig['host'], $dbconfig['user'], $dbconfig['pw'], '', $dbconfig['pconnect']);
	$db->set_tablepre($dbconfig['tpre']);

	$databases = $db->fetch_all('SHOW DATABASES');
	$database_exists = false;
	foreach($databases as $d){
		if($d['Database'] == $dbconfig['name']){
			$database_exists = true;
			break;
		}
	}
	if(!$database_exists){
		exit('您指定的数据库'.$dbconfig['name'].'不存在。');
	}
	$db->select_db($dbconfig['name']);

	$line = file('install.sql');
	$line_max = count($line);

	//Clear utf-8 BOM
	if(ord($line[0]{0}) == 0xEF && ord($line[0]{1}) == 0xBB && ord($line[0]{2}) == 0xBF){
		$line[0] = substr($line[0], 3);
	}

	$in_sql = false;
	$sql = '';
	for($i = 0; $i < $line_max; $i++){
		if((!$in_sql && substr_compare($line[$i], '--', 0, 2) == 0)){
			continue;
		}

		$line[$i] = trim($line[$i]);
		if(empty($line[$i])){
			continue;
		}

		$in_sql = true;
		$sql.= $line[$i];

		if(substr($line[$i], -1) == ';'){
			$in_sql = false;
			if($dbconfig['tpre'] != 'hut_'){
				$sql = preg_replace('/`hut\_(.*?)`/is', "`{$dbconfig['tpre']}\\1`", $sql);
			}
			$db->query($sql);
			$sql = '';
		}
	}

	Administrator::Register($_POST['admin']);
	$admin = new Administrator;
	$admin->login($_POST['admin']['account'], $_POST['admin']['password']);
	$admin->attr('permission', -1);
	unset($admin);

	//安装标记
	touch(S_ROOT.'./data/install.lock');

	showmsg('安装成功！请手动删除网站根目录下install目录，防止重复安装以及其他可能出现的问题。', '../');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>OrchardHut 安装程序</title>
<link href="../image/admin/common.css" rel="stylesheet" type="text/css" />
</head>

<body<?php if(defined('CURSCRIPT')) echo ' class="'.CURSCRIPT.'"';?>">

<div class="container">

	<div class="nav">
		<div class="left"></div>
		<ul id="navlist" class="middle" style="width:960px;color:white;">
        	Installing...
		</ul>
		<div class="right"></div>
	</div>

	<div class="content">
		<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
		<h1>数据库配置</h1>
		<table>
			<tr><th><label>数据库类型：</label></th><td><input type="text" id="db[type]" name="db[type]" value="<?php echo $_G['config']['db']['type']?>" /></td></tr>
			<tr><th><label>数据库字符集：</label></th><td><input type="text" id="db[charset]" name="db[charset]" value="<?php echo $_G['config']['db']['charset']?>" /></td></tr>
			<tr><th><label>数据库服务器地址：</label></th><td><input type="text" id="db[host]" name="db[host]" value="<?php echo $_G['config']['db']['host']?>" /></td></tr>
			<tr><th><label>数据库账号：</label></th><td><input type="text" id="db[user]" name="db[user]" value="<?php echo $_G['config']['db']['user']?>" /></td></tr>
			<tr><th><label>数据库密码：</label></th><td><input type="text" id="db[pw]" name="db[pw]" value="<?php echo $_G['config']['db']['pw']?>" /></td></tr>
			<tr><th><label>数据库表前缀：</label></th><td><input type="text" id="db[tpre]" name="db[tpre]" value="<?php echo $_G['config']['db']['tpre']?>" /></td></tr>
			<tr><th><label>数据库名：</label></th><td><input type="text" id="db[name]" name="db[name]" value="<?php echo $_G['config']['db']['name']?>" /></td></tr>
			<tr><th><label>是否持续链接：</label></th><td><input type="radio" id="db[pconnect]" name="db[pconnect]" value="0" checked="checked" />否 <input type="radio" id="db[pconnect]" name="db[pconnect]" value="0" />是</td></tr>
		</table>

		<h1>系统设置</h1>
		<table>
			<tr><th><label>站点名称：</label></th><td><input type="text" id="config[sitename]" name="config[sitename]" /></td></tr>
			<tr><th><label>时区设置：</label></th><td><input type="text" id="config[timezone]" name="config[timezone]" value="<?php echo $_G['config']['timezone']?>" /></td></tr>
			<tr><th><label>初始管理员账号：</label></th><td><input type="text" id="admin[account]" name="admin[account]" /></td></tr>
			<tr><th><label>初始管理员密码：</label></th><td><input type="text" id="admin[password]" name="admin[password]" /></td></tr>
		</table>

		<button type="submit">开始安装</button>

		</form>
	</div>

	<div class="footer">
		<div class="mark"></div>
		<div class="split"></div>
		<div class="copyright">
			<p><a href="###" target="_blank">OrchardHut</a>, Powered By <a href="http://inu.3-a.net/?1" target="_blank">Kazuichi Takashiro</a></p>
		</div>
	</div>

</div>

</body>
</html>
