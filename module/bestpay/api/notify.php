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

require_once '../../../core/init.inc.php';
error_reporting(E_ALL);

$bestpay = readdata('bestpay');

$key = $bestpay['key'];
$merchantid = $bestpay['merchantid'];

if(!empty($_POST['UPTRANSEQ'])){
	$UPTRANSEQ = $_POST['UPTRANSEQ'];				//交易流水号
	$TRANDATE = $_POST['TRANDATE'];					//交易日期
	$RETNCODE = $_POST['RETNCODE'];					//处理结果吗，0000为成功，其他为失败
	$RETNINFO = $_POST['RETNINFO'];					//处理结果解释码
	$ORDERREQTRANSEQ = $_POST['ORDERREQTRANSEQ'];	//订单请求交易流水号
	$ORDERSEQ = $_POST['ORDERSEQ'];					//订单号
	$ORDERAMOUNT = $_POST['ORDERAMOUNT'];			//订单总金额，单位：元
	$PRODUCTAMOUNT = $_POST['PRODUCTAMOUNT'];		//产品金额
	$ATTACHAMOUNT = $_POST['ATTACHAMOUNT'];			//附加金额
	$CURTYPE = $_POST['CURTYPE'];					//币种，默认RMB
	$ENCODETYPE = $_POST['ENCODETYPE'];				//加密验证方式，0不加密，1为MD5摘要验证
	$SIGN = $_POST['SIGN'];							//数字签名，作为核查依据

	//compare sign
	$originalsign = "UPTRANSEQ=$UPTRANSEQ&MERCHANTID=$merchantid&ORDERSEQ=$ORDERSEQ&ORDERAMOUNT=$ORDERAMOUNT&RETNCODE=$RETNCODE&RETNINFO=$RETNINFO&TRANDATE=$TRANDATE&KEY=$key";
	$md5_originalsign = strtoupper(md5($originalsign));

	if($SIGN == $md5_originalsign){
		$trade = array(
			$ORDERSEQ,
			$UPTRANSEQ,
			$RETNCODE,
		);
		runhooks('bestpay_notified', $trade);
		echo 'UPTRANSEQ_', $UPTRANSEQ;
		exit;
	}else{
		exit('error');
	}
}

exit('fail');

?>
