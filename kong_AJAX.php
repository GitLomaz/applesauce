<?php
	//What's the difference between destroy and empty combat?  can they be combined? think so...
	set_time_limit(128);
	// Include centralized configuration
	require_once(__DIR__ . '/config.php');

	// CORS handling (preflight + headers)
	require_once(__DIR__ . '/cors.php');

	include_once('kong_library.php');
	include_once('common_lib.php');
	include_once('combat_lib.php');
	//include_once('steam/auth.php');
	session_start();
	$conn = sql_connect();
	$output = array();
	$account = isset($_SESSION['accountNo']) ? $_SESSION['accountNo'] : '';
	// error_log("START");
	$time_start = microtime(true);

	// isset($element) ? $element : 'physical'

	$call = mysqli_real_escape_string($conn, isset($_POST['call']) ? $_POST['call'] : '');
	$token = mysqli_real_escape_string($conn, isset($_POST['token']) ? $_POST['token'] : '');
	$param1 = mysqli_real_escape_string($conn, isset($_POST['param1']) ? $_POST['param1'] : '');
	$param2 = mysqli_real_escape_string($conn, isset($_POST['param2']) ? $_POST['param2'] : '');
	$param3 = mysqli_real_escape_string($conn, isset($_POST['param3']) ? $_POST['param3'] : '');
	$param4 = mysqli_real_escape_string($conn, isset($_POST['param4']) ? $_POST['param4'] : '');
	$param5 = mysqli_real_escape_string($conn, isset($_POST['param5']) ? $_POST['param5'] : '');
	$param6 = mysqli_real_escape_string($conn, isset($_POST['param6']) ? $_POST['param6'] : '');
	$param7 = mysqli_real_escape_string($conn, isset($_POST['param7']) ? $_POST['param7'] : '');
	if ($call == "logError"){
		$acc = isset($acc) ? $acc : -1;
		error_log("JS ERROR FOR $acc: " . $_POST['text']);
	}
	if(strLen($token)>5){
		$sqlToken = "SELECT * FROM \"sessions\" where \"cookie\" = '$token'";
		$query = sql_query($sqlToken, $conn);
		if(mysqli_num_rows($query) > 0){
			if($call != 'getChat'){
				if($account == ''){
					$sqlGetAccount = sql_query("SELECT \"account\" from \"sessions\" where \"cookie\" = '$token'", $conn);
					$row = mysqli_fetch_array($sqlGetAccount,MYSQLI_ASSOC);
					$account = $row['account'] ?? null;
				}
			
			// Handle NULL account (sessions without character) - skip operations requiring account
			if($account === null || $account === '') {
				error_log("Session has no associated account. Cookie: $token");
				// Don't update session expiry if no account exists
			} else {
				// Update session expiry by cookie
			$sql = "UPDATE \"sessions\" SET \"cookie\" = '" . $token . "', \"expiry\" = NOW() + INTERVAL '". SESSION_TIMEOUT_MINUTES ." MINUTE' WHERE \"type\" = 'Session' AND \"cookie\" = '" . $token . "'";
				sql_query($sql, $conn);
			}
			}
			if($account != 437){
				//return 0;
			}
			switch ($call){
				case "createLight":
					if($account == 437){
						sql_query("insert into lightSources (map, radius, x, y) select map, $param1, $param2, $param3 FROM \"character\" where \"playerID\" = 437", $conn);
					}
					break;
				case "checkCombat":
					print checkCombat($account, $conn);
					break;




				case "combatAction":
					$output["combatStatus2"] = "";
					$output["combatStatus1"] = "";

					$output["timer"][] = (1 . " --- " . (microtime(true) - $time_start));
					$calcValues = getRow($conn, "calcValues", $account);
					$output["timer"][] = (1.1 . " --- " . (microtime(true) - $time_start));
					$continue = true;
					if($param1 == "attack"){
						$output["combatStatus1"] = attack($account, $conn, $calcValues);
					}else if ($param1 == "run"){
						$output["combatStatus1"] = run($account, $conn);
						if($output["combatStatus1"] == 2){
							$continue = false;
						}
					}else if ($param1 == "item"){
						useItemCombat($account, $param2, $conn, $calcValues);
					}else if ($param1 == "skill"){
						useSkill($conn, $param2, $account, 1, $calcValues);
					}
					$output["timer"][] = (2 . " --- " . (microtime(true) - $time_start));

					if($continue){
						$output["timer"][] = (3 . " --- " . (microtime(true) - $time_start));
						$output["combatText1"] = checkCombat($account, $conn);
						$output["timer"][] = (4 . " --- " . (microtime(true) - $time_start));
						$output["charStatus1"] = getStatus($account, $conn, $calcValues);
						$output["timer"][] = (5 . " --- " . (microtime(true) - $time_start));
						$output["combatStatus2"] = enemyAttack($account, $conn, $calcValues);
						$output["timer"][] = (6 . " --- " . (microtime(true) - $time_start));
						$output["combatText2"] = checkCombat($account, $conn);
						$output["timer"][] = (7 . " --- " . (microtime(true) - $time_start));
						$output["charStatus2"] = getStatus($account, $conn, $calcValues);
						$output["timer"][] = (8 . " --- " . (microtime(true) - $time_start));
					}

					$output["inventory"] = getInventoryJSON($account, $conn);
					print json_encode($output);
					if($output["combatStatus2"] == 1 || $output["combatStatus1"] == 2){
						decreaseBuff($conn, $account);
						sql_query("DELETE FROM \"combat\" WHERE \"playerID\" = ".$account, $conn);
						sql_query("DELETE FROM \"combatEnemies\" WHERE \"playerID\" = ".$account, $conn);
						sql_query("DELETE FROM \"enemyEffects\" WHERE \"playerID\" = ".$account, $conn);
					}else if($output["combatStatus2"] == 3){
						sql_query("DELETE FROM \"combat\" WHERE \"playerID\" = ".$account, $conn);
						sql_query("DELETE FROM \"combatEnemies\" WHERE \"playerID\" = ".$account, $conn);
						sql_query("DELETE FROM \"enemyEffects\" WHERE \"playerID\" = ".$account, $conn);
						combatDeath($conn, $account);
					}
					$output["timer"][] = (11 . " --- " . (microtime(true) - $time_start));
					break;



				case "updateZone":
					print updateZone($conn, $account, $param1);
					break;
				case "getChar":
					print json_encode(getCalcStats($account, $conn));
					break;
				case "getEquipment":
					print json_encode(getEquipment($conn, $account));
					break;
				case "startDailyQuest":
					if($param1 == 'true' || $param1 == 1){
						finishDailyQuest($account, $conn);
					}else{
						startDailyQuest($account, $conn);
					}
					break;
				case "enemyAttack":
					print json_encode(enemyAttack($account, $conn));
					break;
				case "getStatus":
					$calcValues = getRow($conn, "calcValues", $account);
					print getStatus($account, $conn, $calcValues);
					break;
				case "getLocation":
					$row = getRow($conn, "character", $account);
					print json_encode($row);
					if($row && is_array($row)){
						print (($row["locationx"] ?? 0).'-'.($row["locationy"] ?? 0).'-'.($row["map"] ?? '').'-'.($row["class"] ?? ''));
					} else {
						print "0-0--";
					}
					break;
				case "updateLocation":
					print locationPing($conn, $account, $_POST['X'], $_POST['Y'], $_POST['async'], $_POST['map']);
					break;
				case "sendChat":
					setAchievement($conn, $account, 11);
					sql_query('INSERT INTO "chat" (name, chat) VALUES ("' . getAttribute($conn, "account", "account", $account) . '","'. htmlspecialchars($param1) .'")', $conn);
					break;
				case "getChat":
					print getChatArray($conn);
					break;
				case "getEquippedItems":
					print getEquippedItems($conn, $account);
					break;
				case "getEquippedSkills":
					print getEquippedSkills($conn, $account);
					break;
				case "useItem":
					$output["message"] = useItemNonCombat($account, $param1, $conn, $param2);
					$output["status"] = getStatus($account, $conn);
					$output["equipment"] = getEquipment($conn, $account);
					$output["equippedItems"] = getEquippedItems($conn, $account);
					$output["char"] = getCalcStats($account, $conn);
					$output["equippedSkills"] = getEquippedSkills($conn, $account);
					$output["inventoryJSON"] = getInventoryJSON($account, $conn);
					print json_encode($output);
					break;
				case "unequipEquipment":
					unequipEquipmentItem($conn, $account, $param1);
					$calcValues = getRow($conn, "calcValues", $account);
					print json_encode(getCalcStats($account, $conn, $calcValues)).'~'.getStatus($account, $conn, $calcValues);
					break;
				case "equipEquipment":
					equipEquipmentItem($conn, $account, $param1);
					$calcValues = getRow($conn, "calcValues", $account);
					print json_encode(getCalcStats($account, $conn, $calcValues)).'~'.getStatus($account, $conn, $calcValues);
					break;
				case "getCombatInv":
					print getInventoryJSON($account, $conn);
					break;
				case "getShopInv":
					print getInventoryJSON($account, $conn);
					break;
				case "getEndlessTopFloor":
					$output["floor"] = getAttribute($conn, "character", "towerLevel", $account);
					$sql = "select \"count\" from \"inventory\" where \"itemID\" = 235 and \"playerID\" = $account";
					$blag = sql_query($sql, $conn);
					$stones = 0;
					while($row = mysqli_fetch_array($blag,MYSQLI_ASSOC)){
						$stones = $row["count"];
					}
					$output["stones"] = $stones;
					print json_encode($output);
					break;
				case "getInventory":
					print getInventoryJSON($account, $conn);
					break;
				case "updateInventory":
					print getInventoryJSON($account, $conn);
					break;
				case "populateCombatCard":
					print populateCombatCard($conn, $account);
					break;
				case "getQuest":
					print getQuest($conn, $account, $param1);
					break;
				case "getDailyQuest":
					print getDailyQuest($conn, $account, $param1);
					break;
				case "addStat":
					allocateStat($conn, $account, $param1);
					$calcValues = getRow($conn, "calcValues", $account);
					print json_encode(getCalcStats($account, $conn, $calcValues)).'~'.getStatus($account, $conn, $calcValues);
					break;
				case "useSkill":
					$calcValues = getRow($conn, "calcValues", $account);
					$message = useSkill($conn, $param1, $account, $param2, $calcValues);
					print $message.'~'.json_encode(getCalcStats($account, $conn, $calcValues)).'~'.getStatus($account, $conn, $calcValues);
					break;
				case "equipItem":
					if($param2 == 't'){
						print equipItem($account, $param1, $conn);
					}else{
						print unequipItem($account, $param1, $conn);
					}
				case "equipSkill":
					$calcValues = getRow($conn, "calcValues", $account);
					if($param2 == 't'){
						equipSkill($account, $param1, $conn);
					}else{
						unequipSkill($account, $param1, $conn);
					}
					print json_encode(getCalcStats($account, $conn, $calcValues)).'~'.getStatus($account, $conn, $calcValues);
					break;
				case "getShopInfo":
					print getShopInfo($conn, $param1);
					break;
				case "getEquipShopInfo":
					print getEquipmentShopInfo($conn, $param1);
					break;
				case "shopTransaction":
					if($param2 == 'buy'){
						print buyItems($conn, $param1, $account).'~'.getAttribute($conn, "character", "silver", $account);
					}
					if($param2 == 'sell'){
						print sellItems($conn, $param1, $account).'~'.getAttribute($conn, "character", "silver", $account);
					}
					break;
				case "equipShopTransaction":
					if($param2 == 'buy'){
						print buyEquipment($conn, $param1, $account, true).'~'.getAttribute($conn, "character", "silver", $account).'~'.json_encode(getEquipment($conn, $account));
					}
					if($param2 == 'sell'){
						print sellEquipment($conn, $param1, $account).'~'.getAttribute($conn, "character", "silver", $account).'~'.json_encode(getEquipment($conn, $account));
					}
					break;
				case "acceptQuest":
					startQuest($conn, $account, $param1);
					break;
				case "cancelQuest":
					cancelQuest($conn, $account, $param1);
					print json_encode(getInventoryJSON($account, $conn));
					break;
				case "completeQuest":
					$calcValues = getRow($conn, "calcValues", $account);
					completeQuest($conn, $account, $param1);
					print json_encode(getCalcStats($account, $conn, $calcValues)).'~'.getStatus($account, $conn, $calcValues).'~'.getInventoryJSON($account, $conn);
					break;
				case "getEnemyList":
					print json_encode(getEnemyList($conn, $account));
					break;
				case "getRespawnInfo":
					print getSpawn($conn, $account);
					break;
				case "getSkillLevels":
					print json_encode(getCharSkillLevels($conn, $account));
					break;
				case "allocateSkills":
					allocateSkills($conn, $account, $param1);
					print getStatus($account, $conn).'~'.json_encode(getCharSkillLevels($conn, $account)).'~'.json_encode(getCalcStats($account, $conn));
					break;
				case "getWall":
					print getWall($conn);
					break;
				case "getAchievements":
					print json_encode(getAchievementList($conn));
					break;
				case "getAchievementProgress":
					print json_encode(getAchievementProgress($conn, $account));
					break;
				case "sendSuggestion":
					$emailString = '<html><body>';
					$emailString.= '<br/>'.$param1;
					$emailString.= "</body></html>";
					$headers = "From: tortuga.outgoing@gmail.com\r\n";
					$headers .= "MIME-Version: 1.0\r\n";
					$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
					$to = 'lomasia@hotmail.ca';
					mail($to, "Suggestion!", $emailString, $headers);
				$sql = "INSERT INTO \"suggestions\" (playerid, text) values ($account, '$param1')";
					sql_query($sql, $conn);
					break;
				case "getQuestLog":
					print json_encode(getCompleteQuests($conn, $account))."~".json_encode(getIncompleteQuests($conn, $account));
					break;
				case "getQuestLogDetail":
					print getQuestForLog($conn, $account, $param1, $param2);
					break;
				case "getTeleInfo":
					print json_encode(getWarpPoints($conn, $account, $param1)) ."~". getSpawn($conn, $account);
					break;
				case "setSpawn":
					print setSpawn($conn, $account);
					break;
				case "teleport":
					print teleport($conn, $account, $param1);
					break;
				case "towerTeleport":
					print towerTeleport($conn, $account, $param1);
					break;
				case "getShadow":
					print json_encode(getShadows($conn, $account));
					break;
				// DEPRECATED: Kongregate ad buff system
				// case "kongBuff":
				// 	kongBuff($conn, $account);
				// 	break;
				case "softReset":
					print softReset($conn, $account);
					break;
				case "restore":
					$calcValues = getRow($conn, "calcValues", $account);
					$message = restoreFromNPC($conn, $param1, $account);
					print $message.'~'.json_encode(getCalcStats($account, $conn, $calcValues)).'~'.getStatus($account, $conn, $calcValues);
					break;
				case "stashEquipment":
					$sql = "UPDATE \"equipmentInventory\" set stored = $param2 where \"index\" = $param1 and \"playerID\" = $account";
					sql_query($sql, $conn);
					break;
				case "stashItem":
					$sql = "UPDATE \"inventory\" set stored = count + stored, count = 0 where \"itemID\" = $param1 and \"playerID\" = $account";
					sql_query($sql, $conn);
					break;
				case "unstashItem":
					$sql = "UPDATE \"inventory\" set count = count + stored, stored = 0 where \"itemID\" = $param1 and \"playerID\" = $account";
					print $sql;
					sql_query($sql, $conn);
					break;
				case "getSkillTree":
					print getSkillTree($conn, $account);
					break;
				case "getInventoryJSON":
					print getInventoryJSON($account, $conn);
					break;
				case "getClass":
					print getAttribute($conn, "character", "class", $account);
					break;
				case "getRecipe":
					print json_encode(getRecipe(json_decode(stripslashes($param1)), $conn));
					break;
				case "craft":
					print json_encode(craft($account, json_decode(stripslashes($param1)), $conn));
					break;
				case "charExist":
					$sql = "SELECT * FROM \"character\" where playerID = $account";
					$query = sql_query($sql, $conn);
					print mysqli_num_rows($query);
					break;
				case "rebirth":
					$unlockSQL;
					$spr = 0;
					$dex = 0;
					$str = 0;
					$vit = 0;
					$pala = 0;
					$sin = 0;
					$warlock = 0;
					$skillPoints = 0;
					$reset = false;
					$sql = "SELECT SUM(vit) as vit, SUM(str) as str, SUM(spr) as spr, SUM(dex) as dex, SUM(pala) as pala, SUM(sin) as sin, sum(warlock) as warlock
						FROM resets WHERE playerID = $account";
					$sql_rows = sql_query($sql, $conn);
					while($row = mysqli_fetch_array($sql_rows,MYSQLI_ASSOC)){
						$spr = $row["spr"];
						$dex = $row["dex"];
						$str = $row["str"];
						$vit = $row["vit"];
						if($vit + $dex + $str + $spr != 0){
							$reset = true;
						}
						$pala = $pala + $row["pala"];
						$sin = $sin + $row["sin"];
						$warlock = $warlock + $row["warlock"];
					}
					if($param1 == 'Warlock'){
						$spr = $spr + 3;
						$str = $str + 2;
						$dex = $dex + 1;
						$vit = $vit + 3;
						$respawn = 100;
						$unlockSQL = "UPDATE \"equipmentInventory\" set \"archived\" = 0 where \"playerID\" = $account and \"template\" in (60,61,62,63,64,65,66,67,90,91,92,93,94,95,96,97)";
						$skillPoints = $warlock;
					}
					if($param1 == 'Paladin'){
						$spr = $spr + 1;
						$str = $str + 3;
						$dex = $dex + 2;
						$vit = $vit + 3;
						$respawn = 100;
						$unlockSQL = "UPDATE \"equipmentInventory\" set \"archived\" = 0 where \"playerID\" = $account and \"template\" in (51,52,53,54,55,56,57,58,83,84,85,86,87,88,89)";
						$skillPoints = $pala;
					}
					if($param1 == 'Assassin'){
						$spr = $spr + 2;
						$str = $str + 1;
						$dex = $dex + 3;
						$vit = $vit + 3;
						$respawn = 202;
						$unlockSQL = "UPDATE \"equipmentInventory\" set \"archived\" = 0 where \"playerID\" = $account and \"template\" in (68,69,70,71,72,73,74,98,99,100,101,102,103,104)";
						$skillPoints = $sin;
					}
					$total = $pala + $sin + $warlock;

					
				$sqlCreateChar = "INSERT INTO \"character\"(playerid, class, strength, dexterity, spirit, vitality, respawn, skillpoints, diff, neverlogged, resetscript,  map, locationx, locationy, combatmodifier) VALUES ($account, '$param1',$str,$dex,$spr,$vit, $respawn, $skillPoints, $param2, 0, '$param4', 'VanaheimrNE', 4726, 5121, 100)";
				sql_query($sqlCreateChar, $conn);
				$sql_insert = "INSERT INTO \"equippedstuff\"(\"equipindex\") VALUES ($account)";
				sql_query($sql_insert, $conn);
				$sql_insert = "INSERT INTO \"equipmentinventory\"(\"playerid\", \"equipped\", \"basedmgmin\", \"basedmgmax\") VALUES (".$account.", 1, 1, 2)";
					sql_query('INSERT INTO "playerbuffs" (playerid) values ('.$account.")", $conn);
					sql_query($sql_insert, $conn);
					fullheal($account, $conn);
					fullmana($account, $conn);
					addItemAmount($conn, $account, 1, 5);
					addItemAmount($conn, $account, 5, 20);
					equipItem($account, 5, $conn);
					buyEquipment($conn, "17|", $account, false);

					if($param1 == 'Assassin' || $reset){
						toSpawn($account, $conn);
					}

					$sql = "UPDATE inventory SET \"count\" = \"archived\", \"archived\" = 0 where playerID = $account and itemID in (93,119,120,108,90,237,238,239)";
					sql_query($unlockSQL, $conn);
					sql_query($sql, $conn);
					if($param2 == .5){
						$sql = "UPDATE inventory SET \"count\" = \"archived\", \"archived\" = 0 where playerID = $account";
						sql_query($sql, $conn);
						$sql = "UPDATE equipmentInventory  SET \"archived\" = 0 where playerID = $account";
						sql_query($sql, $conn);
					}
					print 1;
					break;
			}
		}else{
			print "error! Invalid Session";
		}
	}else{  //NO TOKEN CALLS
		switch ($call){
			case "createAccount":
				print createAccount($conn, mysqli_real_escape_string($conn, $_POST['account']), mysqli_real_escape_string($conn, $_POST['email']),
								mysqli_real_escape_string($conn, $_POST['password']), mysqli_real_escape_string($conn, $_POST['class']));
				break;
			case "activateAccount":
				print activateAccount($conn, mysqli_real_escape_string($conn, $_POST['account']), mysqli_real_escape_string($conn, $_POST['actToken']));
				break;
			case "getNews":
				print json_encode(getNews($conn));
				break;
			case "getItemList":
				print json_encode(getItemInfo($conn));
				break;
			case "getSkillList":
				print json_encode(getSkillInfo($conn));
				break;
			case "getSkillLevels":
				print json_encode(getSkillLevels($conn));
				break;
			case "clearPHPSession":
				session_unset();
				break;
			case "login":
				print trim(kongLogin($conn, mysqli_real_escape_string($conn, $_POST['login']), mysqli_real_escape_string($conn, $_POST['pass'])));
				break;
			case "test":
				echo "testing..";
				echo getAttribute($conn, "calcValues", "maxHealth", 437);
				break;
			case "sendErrorEmail":
				sendBetaEmail($conn, 2);
			case "sendEmail":
				$emailString = '<html><body>';
				$emailString.= '<br><br/><br/>'.$_POST['message'];
				$emailString.= "</body></html>";
				$headers = "From: tortuga.outgoing@gmail.com\r\n";
				$headers .= "MIME-Version: 1.0\r\n";
				$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
				$to = $email;
				mail($_POST['to'], $_POST['subject'], $emailString, $headers);
				$sql = "INSERT INTO \"tortuga_debug\" (message) values ('".$_POST['message']."')";
				sql_query($sql, $conn);
				break;
			case "getBrowser":
				$blag = sql_query('SELECT * FROM "tortuga_debug" where "browser" != ""', $conn);
				$IE = 0;
				$FF = 0;
				$CH = 0;
				$SF = 0;
				$ED = 0;
				$OT = 0;
				while($row = mysqli_fetch_array($blag,MYSQLI_ASSOC)){
					if (strpos($row['browser'],'Explorer') !== false) {
						$IE++;
					}else if (strpos($row['browser'],'hrome') !== false) {
						$CH++;
					}else if (strpos($row['browser'],'fox') !== false) {
						$FF++;
					}else if (strpos($row['browser'],'afari') !== false) {
						$SF++;
					}else if (strpos($row['browser'],'pera') !== false) {
						$OP++;
					}else{
						$OT++;
					}
				}
				print '['.$IE.','.$FF.','.$CH.','.$SF.','.$OP.','.$OT.']';
				break;
			case "counter":
				$sql = "UPDATE variables set value = value + 1 where \"index\" = 2";
				sql_query($sql, $conn);
				break;
			case "getHost":
				$blag = sql_query('SELECT * FROM "tortuga_debug" where "host" != ""', $conn);
				$IE = 0;
				$FF = 0;
				$CH = 0;
				$SF = 0;
				$ED = 0;
				$OT = 0;
				while($row = mysqli_fetch_array($blag,MYSQLI_ASSOC)){
					if (strpos($row['host'],'142.55.49.127') !== false) {
						$IE++;
					}else if (strpos($row['host'],'142.55.49.246') !== false) {
						$CH++;
					}else if (strpos($row['host'],'localhost') !== false) {
						$FF++;
					}else{
						$OT++;
					}
				}
				print '['.$IE.','.$FF.','.$CH.','.$OT.']';
				break;
			case "getErrorLog":
				$blag = sql_query('SELECT * FROM "tortuga_debug" where "message" != "" order by "index" desc', $conn);
				while($row = mysqli_fetch_array($blag,MYSQLI_ASSOC)){
					$output[] = $row['index']."|".$row['timestamp']."|".$row['host']."|".$row['browser']."|".$row['user']."|".str_replace('"', "'", $row['message']);
				}
				print json_encode($output);
				break;
			case "getUsername":
				//print $param1;
				$sql = sql_query("SELECT * FROM \"account\" where salt = '$param1'", $conn);
				$Row = mysqli_fetch_array($sql,MYSQLI_ASSOC);
				print	 $Row['account'];
				break;
			case "resetPass":
				//param1:code, param2:email, param3:pass}
				$sql = "SELECT * FROM \"account\" where email = '$param2' AND salt = '$param1'";
				$query = sql_query($sql, $conn);
				if(mysqli_num_rows($query) == 0){
					print "Email not valid or token mismatch";
				}else{
					$salt = '';
					for ($x = 0; $x<16; $x++){
						$saltdec = mt_rand(0,15);
						$salthex = dechex($saltdec);
						$salt = $salt.$salthex;
					}
					$pass = md5(sha1($salt.$param3));
					$sql = "UPDATE \"account\" set salt = '$salt', password = '$pass' where email = '$param2' AND salt = '$param1'";
					sql_query($sql, $conn);
					print "Password Changed!";
				}
				break;
			case "createLocalSession":
				// New local session authentication
				$sessionId = mysqli_real_escape_string($conn, $_POST['sessionId'] ?? '');
				
				if (empty($sessionId)) {
					echo "Error: Invalid session ID";
					exit;
				}
				// Check if session already exists
				$sql = "SELECT \"cookie\", \"account\" FROM \"sessions\" WHERE \"sessionid\" = ?";
				$stmt = mysqli_prepare($conn, $sql);
				mysqli_stmt_bind_param($stmt, 's', $sessionId);
				mysqli_stmt_execute($stmt);
				$result = mysqli_stmt_get_result($stmt);
				
				if ($row = mysqli_fetch_assoc($result)) {
					// Existing session - return cookie
					echo $row['cookie'];
				} else {
					// New session - create account and session
					$cookie = bin2hex(random_bytes(8)); // Generate 16-char cookie
					
				// Create new Paladin character with proper stats (matching rebirth code)
				// Paladin stats: str=3, dex=2, spr=1, vit=3, respawn=100, skillPoints=0
				// Location: VanaheimrNE at 4726, 5121
				$sql = "INSERT INTO \"character\" (class, strength, dexterity, spirit, vitality, respawn, skillpoints, diff, neverlogged, resetscript, map, zone, locationx, locationy, combatmodifier) VALUES ('Paladin', 3, 2, 1, 3, 100, 0, 1, 0, '', 'VanaheimrNE', 'East Vanaheimr Village', 4726, 5121, 100)";
				$stmt = mysqli_prepare($conn, $sql);
				mysqli_stmt_execute($stmt);
				$playerId = mysqli_insert_id($conn);
				
				// Create account row with random account name
				$randomAccount = "player_" . $playerId . "_" . bin2hex(random_bytes(4));
				$sql = "INSERT INTO \"account\" (\"playerid\", \"account\", \"password\", \"salt\", \"email\") VALUES (?, ?, 'N/A', 'N/A', 'N/A')";
				$stmt = mysqli_prepare($conn, $sql);
				mysqli_stmt_bind_param($stmt, "is", $playerId, $randomAccount);
				mysqli_stmt_execute($stmt);
				
				// Create equippedStuff entry
				$sql = "INSERT INTO \"equippedstuff\" (\"equipindex\") VALUES (?)";
				$stmt = mysqli_prepare($conn, $sql);
				mysqli_stmt_bind_param($stmt, "i", $playerId);
				mysqli_stmt_execute($stmt);
				
				// Create starting equipment (fists)
				$sql = "INSERT INTO \"equipmentinventory\" (\"playerid\", \"equipped\", \"basedmgmin\", \"basedmgmax\", \"name\", \"template\", \"image\", \"script\", \"class\") VALUES (?, 1, 1, 2, 'unarmed', 0, '', '', '')";
				$stmt = mysqli_prepare($conn, $sql);
				mysqli_stmt_bind_param($stmt, "i", $playerId);
				mysqli_stmt_execute($stmt);
				
				// Create playerBuffs entry
				$sql = "INSERT INTO \"playerbuffs\" (playerid) VALUES (?)";
				$stmt = mysqli_prepare($conn, $sql);
				mysqli_stmt_bind_param($stmt, "i", $playerId);
				mysqli_stmt_execute($stmt);
				
				// Initialize character with full HP/Mana and starting items (matching rebirth code)
				fullheal($playerId, $conn);
				fullmana($playerId, $conn);
				addItemAmount($conn, $playerId, 1, 5);   // Add 5 health potions
				addItemAmount($conn, $playerId, 5, 20);  // Add 20 of item 5
				equipItem($playerId, 5, $conn);          // Equip item 5
				buyEquipment($conn, "17|", $playerId, false);  // Buy starting equipment
				
				// Create session (Type defaults to 'Session') and set expiry 1 year from now
			$sql = "INSERT INTO \"sessions\" (\"cookie\", \"account\", \"sessionid\", \"type\", \"lastactive\", \"expiry\") VALUES (?, ?, ?, 'Session', NOW(), NOW() + INTERVAL '1 YEAR')";
				$stmt = mysqli_prepare($conn, $sql);
				mysqli_stmt_bind_param($stmt, 'sis', $cookie, $playerId, $sessionId);
				
				if (mysqli_stmt_execute($stmt)) {
					echo $cookie;
				} else {
					echo "Error creating session";
				}
				}
				break;
			case "leader":
					print json_encode(craft(437, json_decode('["59288","1","150","0","150","0"]'), $conn));
				break;
		}
	}

