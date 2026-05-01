<?php

	// Include centralized configuration
	require_once(__DIR__ . '/config.php');
	include_once('common_lib.php');
	function activateAccount($conn, $login, $token){
	//  --------------------------------------------------------------------------------------------------
	//                            Activates an account, so you can log onto it
	//  --------------------------------------------------------------------------------------------------
		$sqlToken = "SELECT * FROM \"sessions\" where \"cookie\" = '".$token."' AND \"type\" = 'Activation' AND \"expiry\" > NOW()";
		$query = sql_query($sqlToken, $conn);
		if(mysqli_num_rows($query) > 0){
			$sqlDelete = "DELETE FROM \"sessions\" where \"cookie\" = '".$token."' AND \"type\" = 'Activation'";
			sql_query($sqlDelete, $conn);
			$sqlActivate = "UPDATE \"account\" SET \"confirmed\" = 1 where \"playerid\" = ".$login;
			sql_query($sqlActivate, $conn);
			return 1;
		}else{
			return 0;
		}
	}
	function getEnemyList($conn, $acc){
		$playerDiff = getAttribute($conn, "character", "diff", $acc);
		$sql = "SELECT * from \"charKills\" c , \"enemies\" e inner join \"enemyspawns\" s on e.enemyID = s.enemyID WHERE (e.enemyID = c.enemyID) AND playerid = $acc";
		$sql_rows = sql_query($sql, $conn);
		$zones = '';
		$enemies = '';
		while($row = mysqli_fetch_array($sql_rows,MYSQLI_ASSOC)){
			if( $zones != ''){
				$zones .= ',';
				$enemies .= ',';
			}
			$color = '';
			$count = $row['count'];
			if($count > 750){
				$star = '<span style="font-size:18px;color:#DAA520">&nbsp;★</span>';
			}else if($count > 500){
				$star = '<span style="font-size:18px;color:#a7a7a7">&nbsp;★</span>';
			}else{
				$star = '';
			}
			if($count > 100){
				$fireresists = '<span style="color:red">Fire: </span>'.$row["fireRes"]."%<br/>";
				$earthresists = '<span style="color:brown">Earth: </span>'.$row["earthRes"]."%<br/>";
				$iceresists = '<span style="color:blue">Ice: </span>'.$row["iceRes"]."%<br/>";
				$holyresists = '<span style="color:white">Holy: </span>'.$row["holyRes"]."%<br/>";
				$arcaneresists = '<span style="color:purple">Arcane: </span>'.$row["arcaneRes"]."%<br/>";
				$phyresists = '<span style="color:grey">Phys.: </span>'.$row["physicalRes"]."%<br/>";
				$color = "#a2b0b6";
			}else{
				$fireresists = '<span style="color:red">Fire: </span>??<br/>';
				$earthresists = '<span style="color:brown">Earth: </span>??<br/>';
				$iceresists = '<span style="color:blue">Ice: </span>??<br/>';
				$holyresists = '<span style="color:white">Holy: </span>??<br/>';
				$arcaneresists = '<span style="color:purple">Arcane: </span>??<br/>';
				$phyresists = '<span style="color:grey">Phys.: </span>??<br/>';
			}
			if($count > 50){
				$atk = "ATK: ".ceil($row['attack'] * $playerDiff).'<br/>';
				$def = "DEF: ".ceil($row['softDef'] * $playerDiff).'<br/>';
				$mdef = "MDEF: ".ceil($row['softMDef'] * $playerDiff).'<br/>';
				if($color == ''){
					$color = "#a68140";
				}
			}else{
				$atk = "ATK: ".'??'.'<br/>';
				$def = "DEF: ".'??'.'<br/>';
				$mdef = "MDEF: ".'??'.'<br/>';
			}
			if($count > 20){
				$health = "Max HP: ".ceil($row['health'] * $playerDiff).'<br/>';
				if($color == ''){
					$color = "#5f4b37";
				}
			}else{
				$health = "Max HP: ".'??'.'<br/>';
			}
			if($count > 10){
				$exp = $row['exp'].' / <span style="color:#80919A">'.$row['silver'].'</span>';
				if($color == ''){
					$color = "#654841";
				}
			}else{
				$exp = '?? / <span style="color:#80919A">??</span>';
			}
			if($count > 0){
				$name = $row['name'] . $star;
				$level = "lvl:".$row['level'];
				$image = "foundMonster";
				$killCount = $count." Killed";
				if($color == ''){
					$color = "#3d284e";
				}
			}else{
				$name = '??';
				$level = "lvl:??";
				$image = "genericMonster";
				$killCount = "0 Killed";
			}
			$outputString = '<div style="width:100%;font-size:14px;font-family:Tahoma,Verdana,Segoe,sans-serif;text-align: center;">'.ucwords($name).'</div>';
			$outputString .= '<table style="font-size:14px;margin-bottom: 10px;width: 100%;">';
			$outputString .= '<tr><td colspan=2 style="height:25px;text-align: center;">'.$health.'</td></tr><tr><td style="width:50%;">'.$level.'</td><td>'.$def.'</td></tr>';
			$outputString .= '<tr><td>'.$atk.'</td><td>'.$mdef.'</td></tr></table>';
			$outputString .= '<div style="width:100%; text-decoration: underline;font-family:Tahoma,Verdana,Segoe,sans-serif; text-align:center;font-size:14px;">Resistances</div>';
			$outputString .= '<div style="width:100%; font-size:14px;"><table style="font-size:14px; width:100%">';
			$outputString .= '<tr><td style="width:50%">'.$phyresists.'</td><td style="width:50%">'.$iceresists.'</td></tr>';
			$outputString .= '<tr><td style="width:50%">'.$fireresists.'</td><td style="width:50%">'.$earthresists.'</td></tr>';
			$outputString .= '<tr><td style="width:50%">'.$holyresists.'</td><td style="width:50%">'.$arcaneresists.'</td></tr>';
			$outputString .= '</table><div style="padding-top: 5%;">Experience / <span style="color:#80919A">Silver:<br/></span> '.$exp.'<br/><br/>'.$killCount.'</div></div>';
			$output[] = $name.'|'.$row["enemyID"].'|'.$image.'|'.$row['Chapter'].'|'.$row['Map'].'|'.$row['Zone'].'|'.$outputString.'|'.$row['count'].'|'.$color;
			$zones .= "'".$row['Zone']."'";
			$enemies .= $row['enemyID'];
		}
		if ($zones != ''){
			$sql = "select e.*, s.* from enemyspawns s inner join enemies e on e.enemyID = s.enemyID where zone in ($zones) and e.enemyID not in ($enemies)";
			$sql_rows = sql_query($sql, $conn);
			while($row = mysqli_fetch_array($sql_rows,MYSQLI_ASSOC)){
				$name = $row['name'];
				$level = "lvl:??";
				$image = "genericMonster";
				$killCount = "0 Killed";
				$exp = '?? / <span style="color:#80919A">??</span>';
				$health = "Max Health: ".'??'.'<br/>';
				$atk = "Attack: ".'??'.'<br/>';
				$def = "Defence: ".'??'.'<br/>';
				$mdef = "Magic Defence: ".'??'.'<br/>';
				$fireresists = '<span style="color:red">Fire: </span>??<br/>';
				$earthresists = '<span style="color:brown">Earth: </span>??<br/>';
				$iceresists = '<span style="color:blue">Ice: </span>??<br/>';
				$holyresists = '<span style="color:white">Holy: </span>??<br/>';
				$arcaneresists = '<span style="color:purple">Arcane: </span>??<br/>';
				$phyresists = '<span style="color:grey">Physical: </span>??<br/>';
				$outputString = '<div style="width:100%;font-size:14px;font-family:Tahoma,Verdana,Segoe,sans-serif;text-align: center;">'.ucwords($name).'</div>';
				$outputString .= '<table style="font-size:14px;margin-bottom: 10px;width: 100%;">';
				$outputString .= '<tr><td colspan=2 style="height:25px;text-align: center;">'.$health.'</td></tr><tr><td style="width:50%;">'.$level.'</td><td>'.$def.'</td></tr>';
				$outputString .= '<tr><td>'.$atk.'</td><td>'.$mdef.'</td></tr></table>';
				$outputString .= '<div style="width:100%; text-decoration: underline;font-family:Tahoma,Verdana,Segoe,sans-serif; text-align:center;font-size:14px;">Resistances</div>';
				$outputString .= '<div style="width:100%; font-size:14px;"><table style="font-size:14px; width:100%">';
				$outputString .= '<tr><td style="width:50%">'.$phyresists.'</td><td style="width:50%">'.$iceresists.'</td></tr>';
				$outputString .= '<tr><td style="width:50%">'.$fireresists.'</td><td style="width:50%">'.$earthresists.'</td></tr>';
				$outputString .= '<tr><td style="width:50%">'.$holyresists.'</td><td style="width:50%">'.$arcaneresists.'</td></tr>';
				$outputString .= '</table><div style="padding-top: 5%;">Experience / <span style="color:#80919A">Silver:<br/></span> '.$exp.'<br/><br/>'.$killCount.'</div></div>';
				$output[] = $name.'|_'.$row["enemyID"].'|'.$image.'|'.$row['Chapter'].'|'.$row['Map'].'|'.$row['Zone'].'|'.$outputString.'|0|'.$color;
			}
			sort($output);
			foreach ($output as $row) {
				$exploded = explode("|", $row);
				if ($exploded[1][0] == "_"){
					$exploded[0] = '??';
					$exploded[1] = substr($exploded[1], 1);
				}
				$output2[] = implode("|", $exploded);
			}
		}
		return isset($output2) ? $output2 : null;
	}
	function kongLogin($conn, $login, $pass){
		$sqlLookUp = "SELECT * FROM account WHERE password = '".strtolower($pass)."'";
		$sqlAccount = sql_query($sqlLookUp, $conn);
		if(mysqli_num_rows($sqlAccount) == 0){
			$sqlCreateAccount = "INSERT INTO account(account, password, salt, email) VALUES ('".$login."','".$pass."','N/A','N/A')";
			sql_query($sqlCreateAccount, $conn);
			$str = 5;
			$dex = 3;
			$spr = 1;

			$sqlLookUp = "SELECT * FROM account WHERE account = '".$login."'";
			$sqlAccount = sql_query($sqlLookUp, $conn);

			while($row = mysqli_fetch_array($sqlAccount,MYSQLI_ASSOC)){
				$LookUpAccountNo = $row['playerid'];
			}
				$token = '';
				for ($x = 0; $x<16; $x++){
					$saltdec = mt_rand(0,15);
					$salthex = dechex($saltdec);
					$token = $token.$salthex;
				}
				$sql = "INSERT INTO \"sessions\" set \"cookie\" = '".$token."', Expiry = NOW() + INTERVAL 1500 MINUTE, Type = 'Session', Account = ".$LookUpAccountNo;
				sql_query($sql, $conn);
				kongSubmitInitStats($conn, $LookUpAccountNo);
				return $token;
		}else{
			while($row = mysqli_fetch_array($sqlAccount,MYSQLI_ASSOC)){
				$sqlLookUp = "SELECT * FROM account WHERE account = '".$login."'";
				$sqlAccount = sql_query($sqlLookUp, $conn);
				while($row = mysqli_fetch_array($sqlAccount,MYSQLI_ASSOC)){
					$_SESSION['accountNo'] = $row['playerid'];
				}
				$token = '';
				for ($x = 0; $x<16; $x++){
					$saltdec = mt_rand(0,15);
					$salthex = dechex($saltdec);
					$token = $token.$salthex;
				}
				try{
					$sql = "DELETE FROM \"sessions\" WHERE Type = 'Session' AND Account = ".$_SESSION['accountNo'];
					sql_query($sql, $conn);
				}catch (Exception $e) {

				}
				try{
					$sql = "INSERT INTO \"sessions\" set \"cookie\" = '".$token."', Expiry = NOW() + INTERVAL 1500 MINUTE, Type = 'Session', Account = ".$_SESSION['accountNo'];
					sql_query($sql, $conn);
					error_log(kongSubmitInitStats($conn, $_SESSION['accountNo']));
				}catch (Exception $e) {

				}
				return $token;
			}
		}
	}
	function kongBuff($conn, $acc){
		$sql = "DELETE from \"playerbuffs\" where itemID = -1 AND playerid = $acc";
		sql_query($sql, $conn);
		$level = getAttribute($conn, "character", "level", $acc);
		if($level < 8){
			$level = 8;
		}
		$bonus = floor($level/3);
		$string = "<strong>Kongregate Ad Support</strong><br/><br/>Thanks for your ad support! here is a bonus!<br/><br/><strong>Primary Stats: </strong>$bonus";
		$sql = "insert into \"playerbuffs\" (str, vit, dex, spr, itemID, playerid, name, image, remaining, script) values ($bonus,$bonus,$bonus,$bonus,-1,$acc,'Kongregate Premium Buff','kongBuff',50,'$string')";
		sql_query($sql, $conn);
	}
	function kongSubmitInitStats($conn, $acc){
		return true; // Ignore all!
		$url = 'https://api.kongregate.com/api/submit_statistics.json';
		$userID = getAttribute($conn, "account", "password", $acc);
		$deaths = getAttribute($conn, "account", "deaths", $acc);
		$tower = getAttribute($conn, "character", "towerLevel", $acc);
		$sql = "SELECT count(*) as count FROM kalrul.resets where level > 49 and playerid = $acc";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);
		$resets = $row['count'];
		$level = getAttribute($conn, "character", "level", $acc);
		$sql = "SELECT COALESCE(sum(count), 0) as kills FROM charKills where playerid = $acc;";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);
		$kills = $row['kills'];
		$steps = getAttribute($conn, "account", "stepstaken", $acc);
		$quests = count(getCompleteQuests($conn, $acc));
		$myvars = "user_id=$userID&api_key=df4d3c9f-3665-4e4c-9d66-0d039b7314ad&enemiesKilled=$kills&initialized=1&QuestsCompleted=$quests&StepsTaken=$steps&totalResets=$resets&highestLevel=$level&towerLevel=$tower&totalDeaths=$deaths";
		error_log($myvars);
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_POST, 1);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $myvars);
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt( $ch, CURLOPT_HEADER, 0);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
		curl_exec( $ch );
	}
	function updateStoreInv($acc, $conn, $ID, $token){
		$url = 'https://www.kongregate.com/api/user_items.json';
		$myvars = "user_id=$ID&api_key=df4d3c9f-3665-4e4c-9d66-0d039b7314ad";
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_POST, 1);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $myvars);
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt( $ch, CURLOPT_HEADER, 0);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
		$json = json_decode(curl_exec( $ch ), true);
		foreach ($json["items"] as &$storeItem) {
			if(isset($storeItem["remaining_uses"])){
				switch($storeItem["identifier"]){
					case 1:
						addItemAmount($conn, $acc, 85, 1); // TIP JAR
						$sql = "UPDATE \"account\" set kredsSpent = kredsSpent + 20 where playerid = $acc";
						sql_query($sql, $conn);
						$sql = "INSERT INTO \"kredsTransactions\" (playerid, storeID, Kreds) values ($acc, 1, 20)";
						sql_query($sql, $conn);
						break;
					case 2:
						addItemAmount($conn, $acc, 108, 3); // JEWELRY BOX
						$sql = "UPDATE \"account\" set kredsSpent = kredsSpent + 100 where playerid = $acc";
						sql_query($sql, $conn);
						$sql = "INSERT INTO \"kredsTransactions\" (playerid, storeID, Kreds) values ($acc, 2, 100)";
						sql_query($sql, $conn);
						break;
					case 3:
						addItemAmount($conn, $acc, 111, 10); // 10x MED REJEUV
						$sql = "UPDATE \"account\" set kredsSpent = kredsSpent + 15 where playerid = $acc";
						sql_query($sql, $conn);
						$sql = "INSERT INTO \"kredsTransactions\" (playerid, storeID, Kreds) values ($acc, 3, 15)";
						sql_query($sql, $conn);
						break;
					case 4:
						addItemAmount($conn, $acc, 111, 20); // 20x MED REJEUV
						$sql = "UPDATE \"account\" set kredsSpent = kredsSpent + 25 where playerid = $acc";
						sql_query($sql, $conn);
						$sql = "INSERT INTO \"kredsTransactions\" (playerid, storeID, Kreds) values ($acc, 4, 25)";
						sql_query($sql, $conn);
						break;
					case 5:
						addItemAmount($conn, $acc, 112, 10); // 10x LARGE REJEW
						$sql = "UPDATE \"account\" set kredsSpent = kredsSpent + 20 where playerid = $acc";
						sql_query($sql, $conn);
						$sql = "INSERT INTO \"kredsTransactions\" (playerid, storeID, Kreds) values ($acc, 5, 20)";
						sql_query($sql, $conn);
						break;
					case 6:
						addItemAmount($conn, $acc, 112, 20); // 20x LARGE REJEW
						$sql = "UPDATE \"account\" set kredsSpent = kredsSpent + 35 where playerid = $acc";
						sql_query($sql, $conn);
						$sql = "INSERT INTO \"kredsTransactions\" (playerid, storeID, Kreds) values ($acc, 6, 35)";
						sql_query($sql, $conn);
						break;
					case 7:
						addItemAmount($conn, $acc, 90, 3); // REINFORCED WOODEN CHEST
						$sql = "UPDATE \"account\" set kredsSpent = kredsSpent + 65 where playerid = $acc";
						sql_query($sql, $conn);
						$sql = "INSERT INTO \"kredsTransactions\" (playerid, storeID, Kreds) values ($acc, 7, 65)";
						sql_query($sql, $conn);
						break;
					case 8:
						addItemAmount($conn, $acc, 113, 1);  // STAT RESET
						$sql = "UPDATE \"account\" set kredsSpent = kredsSpent + 50 where playerid = $acc";
						sql_query($sql, $conn);
						$sql = "INSERT INTO \"kredsTransactions\" (playerid, storeID, Kreds) values ($acc, 8, 50)";
						sql_query($sql, $conn);
						break;
					case 9:
						addItemAmount($conn, $acc, 114, 1); // SKILL RESET
						$sql = "UPDATE \"account\" set kredsSpent = kredsSpent + 50 where playerid = $acc";
						sql_query($sql, $conn);
						$sql = "INSERT INTO \"kredsTransactions\" (playerid, storeID, Kreds) values ($acc, 9, 50)";
						sql_query($sql, $conn);
						break;
					case 10:
						addItemAmount($conn, $acc, 115, 1); // STAT AND KILL RESET
						$sql = "UPDATE \"account\" set kredsSpent = kredsSpent + 90 where playerid = $acc";
						sql_query($sql, $conn);
						$sql = "INSERT INTO \"kredsTransactions\" (playerid, storeID, Kreds) values ($acc, 10, 90)";
						sql_query($sql, $conn);
						break;
					case 11:
						addItemAmount($conn, $acc, 116, 10); // 10x LIFE INSURANCE
						$sql = "UPDATE \"account\" set kredsSpent = kredsSpent + 50 where playerid = $acc";
						sql_query($sql, $conn);
						$sql = "INSERT INTO \"kredsTransactions\" (playerid, storeID, Kreds) values ($acc, 11, 50)";
						sql_query($sql, $conn);
						break;
					case 12:
						addItemAmount($conn, $acc, 117, 10);  // 10X EXPERENCE SCROLL
						$sql = "UPDATE \"account\" set kredsSpent = kredsSpent + 100 where playerid = $acc";
						sql_query($sql, $conn);
						$sql = "INSERT INTO \"kredsTransactions\" (playerid, storeID, Kreds) values ($acc, 12, 100)";
						sql_query($sql, $conn);
						break;
					case 13:
						addItemAmount($conn, $acc, 223, 20);  // 20X RHENIUM INGOT
						$sql = "UPDATE \"account\" set kredsSpent = kredsSpent + 200 where playerid = $acc";
						sql_query($sql, $conn);
						$sql = "INSERT INTO \"kredsTransactions\" (playerid, storeID, Kreds) values ($acc, 13, 200)";
						sql_query($sql, $conn);
						break;
					case 14:
						addItemAmount($conn, $acc, 222, 20);  // 20X TERBIUM INGOT
						$sql = "UPDATE \"account\" set kredsSpent = kredsSpent + 150 where playerid = $acc";
						sql_query($sql, $conn);
						$sql = "INSERT INTO \"kredsTransactions\" (playerid, storeID, Kreds) values ($acc, 14, 150)";
						sql_query($sql, $conn);
						break;
					case 15:
						addItemAmount($conn, $acc, 117, 10);  // 10X EXP SCROLL
						addItemAmount($conn, $acc, 112, 5); // 5x LARGE REJEW
						addItemAmount($conn, $acc, 108, 1); // JEWELRY BOX
						addItemAmount($conn, $acc, 116, 20); // 10x LIFE INSURANCE
						generateItem($conn, $acc, 24, 0, 0); // BRONZE DAGGER
						$sql = "UPDATE \"account\" set kredsSpent = kredsSpent + 110 where playerid = $acc";
						sql_query($sql, $conn);
						$sql = "INSERT INTO \"kredsTransactions\" (playerid, storeID, Kreds) values ($acc, 15, 110)";
						sql_query($sql, $conn);
						break;
				}
				$inventoryID = $storeItem["id"];
				$url = 'https://www.kongregate.com/api/use_item.json';
				$myvars = "user_id=$ID&api_key=df4d3c9f-3665-4e4c-9d66-0d039b7314ad&game_auth_token=$token&id=$inventoryID";
				$ch = curl_init( $url );
				curl_setopt( $ch, CURLOPT_POST, 1);
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $myvars);
				curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt( $ch, CURLOPT_HEADER, 0);
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
				curl_exec( $ch );
			}
		}
	}
?>
