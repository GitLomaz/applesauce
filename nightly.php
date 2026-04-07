<?php
	// Include centralized configuration
	require_once(__DIR__ . '/config.php');
	include_once('common_lib.php');
	app_log("Daily Called: ". $_SERVER['QUERY_STRING']);
	$conn = sql_connect();
	if(strlen($_SERVER['QUERY_STRING']) > 0){
		app_log("====================================================================================================");
		sql_query("update `character` set promptDaily = 1", $conn);
		sql_query("update `character` set lastDailyComplete = 0 where lastDailyComplete != 0 and lastDailyTime < date(subdate(SYSDATE(), 1))", $conn);
		sql_query("delete from inventory where itemID in (123,124,125)", $conn);
		sql_query("delete from questPlayerStatus where questID in (27,28,29,30,31)", $conn);
		app_log("Daily Complete");
		sql_query("delete from inventory where `count` = 0 and used = 0 and archived = 0 and stored = 0", $conn);
		app_log("    ====> Number of inventory records purged: " . mysqli_affected_rows($conn));
		sql_query("delete from equipmentInventory where name = 'unarmed' and archived = 1", $conn);
		app_log("    ====> Number of equipment records purged: " . mysqli_affected_rows($conn));
		$day = date('Y-m-d', strtotime('-1 day', strtotime("now")));
		sql_query("select * from `actions` where timestamp like '%$day%'", $conn);
		$actions = mysqli_affected_rows($conn);
		app_log("    ====> Number of actions yesterday: " . $actions);
		sql_query("insert into dailyActions(day, total) values ('$day', $actions)", $conn);
		app_log("====================================================================================================");
	}
?>
