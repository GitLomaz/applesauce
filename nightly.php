<?php
	// Include centralized configuration
	require_once(__DIR__ . '/config.php');
	include_once('common_lib.php');
	app_log("Daily Called: ". $_SERVER['QUERY_STRING']);
	$conn = sql_connect();
	if(strlen($_SERVER['QUERY_STRING']) > 0){
		app_log("====================================================================================================");
		sql_query("update \"character\" set promptdaily = 1", $conn);
		sql_query("update \"character\" set lastdailycomplete = 0 where lastdailycomplete != 0 and lastdailytime < date(CURRENT_DATE - INTERVAL '1 day')", $conn);
		sql_query("delete from inventory where itemid in (123,124,125)", $conn);
		sql_query("delete from questplayerstatus where questid in (27,28,29,30,31)", $conn);
		app_log("Daily Complete");
		sql_query("delete from inventory where \"count\" = 0 and used = 0 and archived = 0 and stored = 0", $conn);
		$stmt_result = sql_query("SELECT 1", $conn); // Get last result for row count
		app_log("    ====> Number of inventory records purged: " . $stmt_result->rowCount());
		sql_query("delete from equipmentinventory where name = 'unarmed' and archived = 1", $conn);
		$stmt_result = sql_query("SELECT 1", $conn);
		app_log("    ====> Number of equipment records purged: " . $stmt_result->rowCount());
		$day = date('Y-m-d', strtotime('-1 day', strtotime("now")));
		$result = sql_query("select * from actions where \"timestamp\" >= '$day' AND \"timestamp\" < '$day'::date + INTERVAL '1 day'", $conn);
		$actions = $result->rowCount();
		app_log("    ====> Number of actions yesterday: " . $actions);
		sql_query("insert into dailyactions(day, total) values ('$day', $actions)", $conn);
		app_log("====================================================================================================");
	}
?>
