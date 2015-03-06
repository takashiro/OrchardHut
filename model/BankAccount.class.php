<?php

class BankAccount extends DBObject{
	const TABLE_NAME = 'bankaccount';

	const ERROR_INVALID_ARGUMENT = -1;
	const ERROR_INVALID_INSUFFICIENT_AMOUNT = -2;
	const ERROR_TARGET_NOT_EXIST = -3;

	public function __construct($id = 0){
		if($id = intval($id)){
			$this->fetchAttributesFromDB('*', 'id='.$id);
		}
	}

	public function toArray(){
		if($this->id > 0){
			return parent::toArray();
		}else{
			return array(
				'id' => 0,
				'remark' => '',
				'amount' => 0,
				'addressrange' => 0,
			);
		}
	}

	protected function addLog($log){
		if(!$this->id || $this->id <= 0)
			return 0;

		global $db;
		$db->select_table('bankaccountlog');
		$log['accountid'] = $this->id;
		$log['dateline'] = TIMESTAMP;
		$db->INSERT($log);
		return $db->insert_id();
	}

	public function transferTo($target, $delta, $reason = ''){
		$delta = floatval($delta);

		if($target instanceof BankAccount)
			$target = $target->id;
		else
			$target = intval($target);

		$error = self::ERROR_INVALID_ARGUMENT;
		if($delta > 0 && $target > 0){
			global $_G, $db, $tpre;
			//@todo: Begin Transaction
			$db->query("UPDATE {$tpre}bankaccount SET amount=amount-$delta WHERE id={$this->id} AND amount>=$delta");
			if($db->affected_rows() > 0){
				$db->query("UPDATE {$tpre}bankaccount SET amount=amount+$delta WHERE id=$target");
				if($db->affected_rows() > 0){
					$log = array(
						'accountid' => $this->id,
						'delta' => -$delta,
						'reason' => $reason,
						'operatorid' => $_G['admin']->id,
						'targetaccountid' => $target,
					);
					$db->select_table('bankaccountlog');
					$db->INSERT($log);

					//@todo: commit
					return true;
				}else{
					$error = ERROR_TARGET_NOT_EXIST;
				}
			}else{
				$error = ERROR_INVALID_INSUFFICIENT_AMOUNT;
			}
			//@todo: Roll back transaction
		}

		return $error;
	}
}

?>
