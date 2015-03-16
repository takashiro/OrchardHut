<?php

/*
	DeliveryTime here means the time when orders reach customers (misused perhaps =.=)
	It's frequently read (without joint tables) but seldom modified. So we declare a class
	to cache its data intoa single file, and thus reduce MySQL queries.
*/

class DeliveryTime{
	public static function FetchAll(){
		$timespans = readcache('deliverytime');
		if(!$timespans){
			global $db;
			$db->select_table('deliverytime');
			$timespans = $db->MFETCH('*');
			writecache('deliverytime', $timespans);
		}

		return $timespans;
	}

	public static function FetchAllEffective(){
		$timespans = array();

		foreach(self::FetchAll() as $span){
			if(empty($span['hidden']) && $span['effective_time'] <= TIMESTAMP && TIMESTAMP <= $span['expiry_time']){
				$timespans[] = $span;
			}
		}

		return $timespans;
	}

	public static function UpdateCache(){
		writecache('deliverytime', false);
	}

	public static function SortByTimeFrom(&$timespans){
		usort($timespans, 'DeliveryTime::__sort_by_time_from');
	}

	private static function __sort_by_time_from($s1, $s2){
		return $s1['time_from'] > $s2['time_from'];
	}
}

?>
