<?php

if(!defined('S_ROOT')) exit('access denied');

$logfile = S_ROOT.'./data/log/'.rdate(TIMESTAMP, 'Ymd').'_'.$logfile.'.log.php';
$need_prefix = !file_exists($logfile);
$fp = fopen($logfile, 'a');
flock($fp, LOCK_EX);
if($need_prefix){
	fwrite($fp, '<?php exit;?>');
}

$prefix = "\r\n".User::ip()."\t".rdate(TIMESTAMP)."\t";
if(is_array($data)){
	foreach($data as $v){
		fwrite($fp, $prefix.$v);
	}
}else{
	fwrite($fp, $prefix.$data);
}

fclose($fp);

?>
