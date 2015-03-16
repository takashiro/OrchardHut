<?php

if(!defined('S_ROOT')) exit('access denied');

$errorID = md5($errorLevel.$errorMessage.$errorFile.$errorLine);
$filePath = S_ROOT.'data/error/'.$errorID.'.inc.php';

$fileRootLength = strlen(S_ROOT) - 1;
if(strncmp(S_ROOT, $errorFile, $fileRootLength) == 0){
	$errorFile = substr($errorFile, $fileRootLength + 1);
}

if(!file_exists($filePath)){
	global $PHP_SELF, $_G;

	@$fp = fopen($filePath, 'w');
	if($fp){
		fwrite($fp, '<?php exit;?>');
		fwrite($fp, "\r\nError Level: $errorLevel\r\n");
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

		fwrite($fp, "\r\nPOST: \r\n");
		fwrite($fp, file_get_contents('php://input'));
		fwrite($fp, "\r\n");

		fwrite($fp, "\r\nStack Trace:\r\n");

		$traces = debug_backtrace();
		$tracenum = count($traces);
		for($i = 2; $i < $tracenum; $i++){
			$trace = &$traces[$i];
			if(strncmp(S_ROOT, $trace['file'], $fileRootLength) == 0){
				$trace['file'] = substr($trace['file'], $fileRootLength + 1);
			}

			fwrite($fp, "{$trace['file']}({$trace['line']}): ");
			if(isset($trace['class'])){
				fwrite($fp, $trace['class']);
				if(isset($trace['type'])){
					fwrite($fp, $trace['type']);
				}else{
					write($fp, '??');
				}
			}
			if(isset($trace['function'])){
				fwrite($fp, $trace['function']);
				fwrite($fp, '()');
				//@todo: record function arguments here
			}
			fwrite($fp, "\r\n");
		}

		@fclose($fp);
	}
}

return false;

?>
