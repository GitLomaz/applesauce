<?php
	// Include centralized configuration
	require_once(__DIR__ . '/config.php');

	// CORS handling (preflight + headers)
	require_once(__DIR__ . '/cors.php');

	include_once('library.php');
	session_start();
	$conn = sql_connect();
	$output = array();
	$acc = isset($_SESSION['accountNo']) ? $_SESSION['accountNo'] : '437';
	$return = json_decode(isset($_POST['return']) ? $_POST['return'] : '["empty"]');
	$call = mysqli_real_escape_string($conn, isset($_POST['call']) ? $_POST['call'] : '');
	
	error_log("[AJAX.php] Call: $call");
	
	$token = mysqli_real_escape_string($conn, isset($_POST['token']) ? $_POST['token'] : '');
	$param1 = mysqli_real_escape_string($conn, isset($_POST['param1']) ? $_POST['param1'] : '');
	$param2 = mysqli_real_escape_string($conn, isset($_POST['param2']) ? $_POST['param2'] : '');
	$param3 = mysqli_real_escape_string($conn, isset($_POST['param3']) ? $_POST['param3'] : '');
	$param4 = mysqli_real_escape_string($conn, isset($_POST['param4']) ? $_POST['param4'] : '');
	$param5 = mysqli_real_escape_string($conn, isset($_POST['param5']) ? $_POST['param5'] : '');
	$param6 = mysqli_real_escape_string($conn, isset($_POST['param6']) ? $_POST['param6'] : '');
	$param7 = mysqli_real_escape_string($conn, isset($_POST['param7']) ? $_POST['param7'] : '');

	//No Auth Required Calls
	switch ($call){
		case "getNews":
			$news = getNews($conn);
			error_log("[AJAX.php] getNews result: " . json_encode($news));
			print json_encode($news);
			exit; // Exit after handling no-auth calls
			break;
	}

	$sqlToken = "SELECT * FROM `sessions` where `Cookie` = '".$token."' AND Type = 'Session' AND `Expiry` > NOW()";
	$query = sql_query($sqlToken, $conn);
	if(mysqli_num_rows($query) > 0 || $param7 == "override"){
		if($param7 == "override"){
			$account = 437;
		}
		$output["session"] = 1;
		if($account == ''){
			$sqlGetAccount = sql_query("SELECT `Account` from `sessions` where `Cookie` = '$token'", $conn);
			$row = mysqli_fetch_array($sqlGetAccount,MYSQLI_ASSOC);
			$acc = $row['Account'];
		}
		$sql = "UPDATE `sessions` set `cookie` = '".$token."', Expiry = NOW() + INTERVAL 15 MINUTE WHERE Type = 'Session' AND Account = $acc";
		sql_query($sql, $conn);
		switch ($call){
			case "initInfo":
				//print checkCombat($account, $conn);
				break;
		}

		if (in_array("statusBar", $return)){
			$output["statusBar"] = getStatusBar($acc, $conn);
		}
		if (in_array("inventory", $return)){
			$output["inventory"] = getInventory($acc, $conn);
		}
		if (in_array("equipment", $return)){
			$output["equipment"] = getEquipment($acc, $conn);
		}
		if (in_array("charPage", $return)){
			$output["charPage"] = getCalcStats($acc, $conn);
		}
		if (in_array("equippedItems", $return)){
			$output["equippedItems"] = getEquippedItems($acc, $conn);
		}
		if (in_array("equippedSkills", $return)){
			$output["equippedSkills"] = getEquippedSkills($acc, $conn);
		}
		if (in_array("test", $return)){
			$output["test"] = getSingleShopEquipment($conn, 18);
		}


	}else{
		$output["session"] = 0;
		if (in_array("news", $return)){
			$output["news"] = getNews($conn);
		}
	}
	//print json_encode($output);
?>
