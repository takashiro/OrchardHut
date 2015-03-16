<?php

$errorID = md5($errorLevel.$errorMessage.$errorFile.$errorLine);
$filePath = S_ROOT.'data/log/'.$errorID.'_error.log.php';

if(!file_exists($filePath)){
	global $PHP_SELF, $_G;

	@$fp = fopen($filePath, 'w');
	if($fp){
		fwrite($fp, "Error Level: $errorLevel\r\n");
		fwrite($fp, "Error Message: $errorMessage\r\n");
		fwrite($fp, "Error File: $errorFile\r\n");
		fwrite($fp, "Error Line: $errorLine\r\n");
		fwrite($fp, "User ID: {$_G['user']->id}\r\n");
		fwrite($fp, "URL: $PHP_SELF");
		if(!empty($_SERVER['QUERY_STRING'])){
			fwrite($fp, '?');
			fwrite($fp, $_SERVER['QUERY_STRING']);
		}
		fwrite($fp, "\r\n");

		if($_GET){
			fwrite($fp, "GET: \r\n");
			fwrite($fp, var_export($_GET, true));
			fwrite($fp, "\r\n");
		}

		if($_POST){
			fwrite($fp, "POST: \r\n");
			fwrite($fp, var_export($_POST, true));
			fwrite($fp, "\r\n");
		}

		@fclose($fp);
	}
}

return false;

?>
