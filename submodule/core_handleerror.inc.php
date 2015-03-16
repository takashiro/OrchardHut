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

	$report = '<?php exit;?>';
	$report.= "\r\nError Level: $errorLevel\r\n";
	$report.= "Error Message: $errorMessage\r\n";
	$report.= "Error File: $errorFile\r\n";
	$report.= "Error Line: $errorLine\r\n";
	$report.= "User ID: {$_G['user']->id}\r\n";
	$report.= "URL: $PHP_SELF";
	if(!empty($_SERVER['QUERY_STRING'])){
		$report.= '?';
		$report.= $_SERVER['QUERY_STRING'];
	}
	$report.= "\r\n";

	$report.= "\r\nPOST: \r\n";
	$report.= file_get_contents('php://input');
	$report.= "\r\n";

	$report.= "\r\nStack Trace:\r\n";

	$traces = debug_backtrace();
	foreach($traces as $trace){
		if(isset($trace['file'])){
			if(strncmp(S_ROOT, $trace['file'], $fileRootLength) == 0){
				$trace['file'] = substr($trace['file'], $fileRootLength + 1);
			}

			$report.= "{$trace['file']}({$trace['line']}): ";
		}
		if(isset($trace['class'])){
			$report.= $trace['class'];
			if(isset($trace['type'])){
				$report.= $trace['type'];
			}else{
				write($fp, '??');
			}
		}
		if(isset($trace['function'])){
			$report.= $trace['function'];
			$report.= '()';
			//@todo: record function arguments here
		}
		$report.= "\r\n";
	}

	file_put_contents($filePath, $report);
}

return false;

?>
