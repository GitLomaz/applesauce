<?php

	// Include centralized configuration
	require_once(__DIR__ . '/config.php');
	include_once('combat_lib.php');


	function logAction($conn, $acc, $action, $param1, $param2){
		$sql = 'select "timestamp" as "time" from actions ORDER BY actionID DESC LIMIT 1';
		$sqlR = sql_query($sql, $conn);
		$row = $sqlR->fetch();
		$timestamp = $row['time'];
		$message = '';
		$accountName =  getAttribute($conn, "account", "account", $acc);
		if($action == 'kill'){
			$message = "$accountName has killed $param1";
		}
		if($action == 'death'){
			$message = "$accountName has been slain :(";
		}
		if($action == 'startQuest'){
			$message = "$accountName has started the quest: $param1";
		}
		if($action == 'finishQuest'){
			$message = "$accountName has completed the quest: $param1";
		}
		if($action == 'levelUp'){
			$message = "$accountName leveled up! now level $param1";
		}
		if($action == 'run'){
			$message = "$accountName ran away from $param1";
		}
		if($action == 'Crafting (Success)'){
			$message = "$accountName Was able to create $param1";
		}
		if($action == 'Crafting (Failure)'){
			$message = "$accountName Was unable to create $param1";
		}
		if($action == 'reset'){
			$message = "$accountName reset their level $param1 $param2";
		}
		$sql = "insert into actions (\"text\", \"action\", \"diff\") values ('$message', '$action', EXTRACT(EPOCH FROM (TIMESTAMP '$timestamp' - CURRENT_TIMESTAMP))::int)";
		sql_query($sql, $conn);
	}

	// -- Function Name : restoreFromNPC
	// -- Params : $conn, $option, $acc
	// -- Purpose : Restores health/mana from the priest NPC
	function restoreFromNPC($conn, $option, $acc){
		$silver = getAttribute($conn, "character", "silver", $acc);
		$cost = 0;
		$restore = '';
		switch ($option){
			case "heal-10":

				if($silver > 3){
					heal (10, $acc, $conn);
					$cost = 4;
					$restore = "10 Health Restored!";
				}

				break;
			case "heal-100":

				if($silver > 34){
					heal (100, $acc, $conn);
					$cost = 35;
					$restore = "100 Health Restored!";
				}

				break;
			case "heal-1000":

				if($silver > 300){
					heal (1000, $acc, $conn);
					$cost = 300;
					$restore = "1000 Health Restored!";
				}

				break;
			case "rest-10":

				if($silver > 6){
					mana (10, $acc, $conn);
					$cost = 7;
					$restore = "10 Mana Restored!";
				}

				break;
			case "rest-100":

				if($silver > 64){
					mana (100, $acc, $conn);
					$cost = 65;
					$restore = "100 Mana Restored!";
				}

				break;
			case "rest-1000":

				if($silver > 619){
					mana (1000, $acc, $conn);
					$cost = 620;
					$restore = "1000 Mana Restored!";
				}

				break;
			}

			$sql = "UPDATE \"character\" set \"silver\" = \"silver\" - $cost where \"playerid\" = $acc";
			sql_query($sql, $conn);
			return $restore;
		}

	// -- Function Name : softReset
	// -- Params : $conn, $acc
	// -- Purpose : Resets character, setting everything back to level one
	function softReset($conn, $acc){
		$level = getAttribute($conn, "character", "level", $acc);
		$class = getAttribute($conn, "character", "class", $acc);
		$diff = getAttribute($conn, "character", "diff", $acc);
		$core = getAttribute($conn, "character", "hardcore", $acc);
		$stat;
		$classShort;
		$statVal = 0;
		$classVal = 0;
		$vit = 0;
		$chest;
		$cursedChest;
		if($class == 'Warlock'){
			$stat = "spr";
			$classShort = "warlock";
			$chest = 119;
			$cursedChest = 238;
		}else if($class == 'Assassin'){
			$stat = "dex";
			$classShort = "sin";
			$chest = 120;
			$cursedChest = 239;
		}else if($class == 'Paladin'){
			$stat = "str";
			$classShort = "pala";
			$chest = 93;
			$cursedChest = 237;
		}
		if($level > 74){
			if($diff == .75){
				$statVal = 2;
				addItemAmount($conn, $acc, $cursedChest, 1);
			}
			if($diff == 1){
				$statVal = 4;
				$vit = 1;
				addItemAmount($conn, $acc, $cursedChest, 1);
			}
			if($diff == 1.5){
				$statVal = 5;
				$vit = 2;
				$classVal = 1;
				addItemAmount($conn, $acc, $cursedChest, 1);
			}

		}else if($level > 49){
			if($diff == .75){
				$statVal = 1;
				addItemAmount($conn, $acc, $chest, 1);
			}
			if($diff == 1){
				$statVal = 2;
				addItemAmount($conn, $acc, $chest, 1);
			}
			if($diff == 1.5){
				$statVal = 2;
				$classVal = 1;
				addItemAmount($conn, $acc, $chest, 1);
			}
		}else if ($level > 24){
			if($diff == 1){
				$statVal = 1;
			}
			if($diff == 1.5){
				$statVal = 1;
			}
		}
		if($acc == 501){  //DOUBLE DARK'S RESETS
			$statVal = $statVal * 2;
			$classVal = $classVal * 2;
			if($level > 74){
				addItemAmount($conn, $acc, $cursedChest, 1);
			}else if ($level > 49){
				addItemAmount($conn, $acc, $chest, 1);
			}
		}
		logAction($conn, $acc, "reset", $level, $class);
		$sql = "insert into resets (playerid, class, level, hardcore, $stat, $classShort, vit) values ($acc, '$class', $level, $core, $statVal, $classVal, $vit)";
		sql_query($sql, $conn);
		$sql = "DELETE FROM character where playerid = $acc";
		sql_query($sql, $conn);
		$sql = "DELETE FROM charskills where playerid = $acc";
		sql_query($sql, $conn);
		$sql = "DELETE FROM combat where playerid = $acc";
		sql_query($sql, $conn);
		$sql = "DELETE FROM combatenemies where playerid = $acc";
		sql_query($sql, $conn);
		$sql = "DELETE FROM completequests where playerid = $acc";
		sql_query($sql, $conn);
		$sql = "UPDATE equipmentinventory SET \"archived\" = 1, \"equipped\" = 0, \"stored\" = 0 where playerid = $acc";
		sql_query($sql, $conn);
		$sql = "DELETE FROM equippedstuff where equipindex = $acc";
		sql_query($sql, $conn);
		$sql = "UPDATE inventory SET \"archived\" = \"archived\" + \"count\" + \"stored\", \"count\" = 0, \"stored\" = 0 where playerid = $acc";
		sql_query($sql, $conn);
		$sql = "DELETE FROM playerbuffs where playerid = $acc";
		sql_query($sql, $conn);
		$sql = "DELETE FROM playerwaypoints where playerid = $acc";
		sql_query($sql, $conn);
		$sql = "DELETE FROM questplayerstatus where playerid = $acc";
		sql_query($sql, $conn);
		$sql = "DELETE FROM inventory where playerid = $acc and itemid in (select item_id from item where quest = 1)";
		sql_query($sql, $conn);

		return 1;
	}

	// Database connection and query functions now in config.php

	// -- Function Name : generateItem
	// -- Params : $conn, $acc, $id, $levelOverride, $rollMods
	// -- Purpose : Creates an item based on item type, and level, returns name
	function generateItem($conn, $acc, $id, $levelOverride, $rollMods){
		$row = getRow($conn, "equipmenttemplate", $id);
		$class = $row['class'];

		if(isset($levelOverride)){
			$row['level'] = $levelOverride;
		}

		$row['price'] = $row['price'] / 3;

		if($row['script'] == "0"){

			if($class == "club"){
				$array[] = "minDmg|".(ceil(((rand(ceil($row['level']/10),ceil(($row['level']+20)/8)))/100) * $row['baseDmgMin']) + 1);
				$array[] = "maxDmg|".(ceil(((rand(ceil($row['level']/10),ceil(($row['level']+20)/8)))/100) * $row['baseDmgMin']) + 1);
				$array[] = "maxDmg|".(ceil(((rand(ceil($row['level']/10),ceil(($row['level']+20)/8)))/100) * $row['baseDmgMin']) + 1);
				$array[] = "str|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "str|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "dex|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "critDamage|".((rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1)*5);
			}


			if($class == "sword"){
				$array[] = "minDmg|".(ceil(((rand(ceil($row['level']/10),ceil(($row['level']+20)/8)))/100) * $row['baseDmgMin']) + 1);
				$array[] = "maxDmg|".(ceil(((rand(ceil($row['level']/10),ceil(($row['level']+20)/8)))/100) * $row['baseDmgMin']) + 1);
				$array[] = "critchance|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "critchance|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "str|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "dex|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "dex|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
			}


			if($class == "magicArmor"){
				$array[] = "str|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "str|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "str|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "vit|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "vit|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "vit|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "vit|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "vit|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "spr|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "spr|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "spr|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "spr|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "spr|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "spr|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "dex|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "dex|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "maxHP|".(rand(ceil(pow($row['level'], 1.4)),ceil(pow($row['level'] + 5, 1.5))) + 1);
				$array[] = "maxHP|".(rand(ceil(pow($row['level'], 1.4)),ceil(pow($row['level'] + 5, 1.5))) + 1);
				$array[] = "maxMP|".(rand(ceil(pow($row['level'], 1.1)),ceil(pow($row['level'] + 5, 1.2))) + 1);
				$array[] = "maxMP|".(rand(ceil(pow($row['level'], 1.1)),ceil(pow($row['level'] + 5, 1.2))) + 1);
				$array[] = "maxMP|".(rand(ceil(pow($row['level'], 1.1)),ceil(pow($row['level'] + 5, 1.2))) + 1);
				$array[] = "fireRes|".(rand(ceil(($row['level'] + 20) / 8 ),ceil(($row['level'] + 20) / 4 )));
				$array[] = "iceRes|".(rand(ceil(($row['level'] + 20) / 8 ),ceil(($row['level'] + 20) / 4 )));
				$array[] = "earthRes|".(rand(ceil(($row['level'] + 20) / 8 ),ceil(($row['level'] + 20) / 4 )));
				$array[] = "arcaneRes|".(rand(ceil(($row['level'] + 20) / 16 ),ceil(($row['level'] + 20) / 8 )));
				$array[] = "holyRes|".(rand(ceil(($row['level'] + 20) / 16 ),ceil(($row['level'] + 20) / 8 )));
				$array[] = "fireRes|".(rand(ceil(($row['level'] + 20) / 8 ),ceil(($row['level'] + 20) / 4 )));
				$array[] = "iceRes|".(rand(ceil(($row['level'] + 20) / 8 ),ceil(($row['level'] + 20) / 4 )));
				$array[] = "earthRes|".(rand(ceil(($row['level'] + 20) / 8 ),ceil(($row['level'] + 20) / 4 )));
				$array[] = "arcaneRes|".(rand(ceil(($row['level'] + 20) / 16 ),ceil(($row['level'] + 20) / 8 )));
				$array[] = "holyRes|".(rand(ceil(($row['level'] + 20) / 16 ),ceil(($row['level'] + 20) / 8 )));
			}


			if($class == "lightArmor"){
				$array[] = "str|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "str|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "str|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "str|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "vit|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "vit|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "vit|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "vit|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "vit|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "spr|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "spr|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "dex|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "dex|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "dex|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "dex|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "dex|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "dex|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "maxHP|".(rand(ceil(pow($row['level'], 1.4)),ceil(pow($row['level'] + 5, 1.5))) + 1);
				$array[] = "maxHP|".(rand(ceil(pow($row['level'], 1.4)),ceil(pow($row['level'] + 5, 1.5))) + 1);
				$array[] = "maxMP|".(rand(ceil(pow($row['level'], 1.1)),ceil(pow($row['level'] + 5, 1.2))) + 1);
				$array[] = "maxMP|".(rand(ceil(pow($row['level'], 1.1)),ceil(pow($row['level'] + 5, 1.2))) + 1);
				$array[] = "fireRes|".(rand(ceil(($row['level'] + 20) / 8 ),ceil(($row['level'] + 20) / 4 )));
				$array[] = "iceRes|".(rand(ceil(($row['level'] + 20) / 8 ),ceil(($row['level'] + 20) / 4 )));
				$array[] = "earthRes|".(rand(ceil(($row['level'] + 20) / 8 ),ceil(($row['level'] + 20) / 4 )));
				$array[] = "arcaneRes|".(rand(ceil(($row['level'] + 20) / 16 ),ceil(($row['level'] + 20) / 8 )));
				$array[] = "holyRes|".(rand(ceil(($row['level'] + 20) / 16 ),ceil(($row['level'] + 20) / 8 )));
				$array[] = "fireRes|".(rand(ceil(($row['level'] + 20) / 8 ),ceil(($row['level'] + 20) / 4 )));
				$array[] = "iceRes|".(rand(ceil(($row['level'] + 20) / 8 ),ceil(($row['level'] + 20) / 4 )));
				$array[] = "earthRes|".(rand(ceil(($row['level'] + 20) / 8 ),ceil(($row['level'] + 20) / 4 )));
				$array[] = "arcaneRes|".(rand(ceil(($row['level'] + 20) / 16 ),ceil(($row['level'] + 20) / 8 )));
				$array[] = "holyRes|".(rand(ceil(($row['level'] + 20) / 16 ),ceil(($row['level'] + 20) / 8 )));
			}


			if($class == "heavyArmor"){
				$array[] = "str|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "str|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "str|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "str|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "str|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "str|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "vit|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "vit|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "vit|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "vit|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "vit|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "vit|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "spr|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "spr|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "dex|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "dex|".(rand(ceil($row['level']/10),ceil(($row['level']+20)/8)) + 1);
				$array[] = "maxHP|".(rand(ceil(pow($row['level'], 1.4)),ceil(pow($row['level'] + 5, 1.5))) + 1);
				$array[] = "maxHP|".(rand(ceil(pow($row['level'], 1.4)),ceil(pow($row['level'] + 5, 1.5))) + 1);
				$array[] = "maxHP|".(rand(ceil(pow($row['level'], 1.4)),ceil(pow($row['level'] + 5, 1.5))) + 1);
				$array[] = "maxMP|".(rand(ceil(pow($row['level'], 1.1)),ceil(pow($row['level'] + 5, 1.2))) + 1);
				$array[] = "fireRes|".(rand(ceil(($row['level'] + 20) / 8 ),ceil(($row['level'] + 20) / 4 )));
				$array[] = "iceRes|".(rand(ceil(($row['level'] + 20) / 8 ),ceil(($row['level'] + 20) / 4 )));
				$array[] = "earthRes|".(rand(ceil(($row['level'] + 20) / 8 ),ceil(($row['level'] + 20) / 4 )));
				$array[] = "arcaneRes|".(rand(ceil(($row['level'] + 20) / 16 ),ceil(($row['level'] + 20) / 8 )));
				$array[] = "holyRes|".(rand(ceil(($row['level'] + 20) / 16 ),ceil(($row['level'] + 20) / 8 )));
			}

			$chance = 50;

			if(isset($rollMods)){
				$chance = $rollMods;
			}

			$mod = 0;
			$odds = rand(0,100);

			if(count($array) > 0){
				shuffle($array);
				for ($x = 0; $x < count($array); $x++){
					$stat = $array[$x];

					if($chance > $odds){
						$row['price'] = $row['price'] * 1.2;
						$mod++;
						$chance = $chance / 2;
						$number = explode("|", $stat)[1];
						$attr = explode("|", $stat)[0];
						$row[$attr] = $row[$attr] + $number;
						$name = $attr;

						if($name == 'minDmg' || $name == 'maxDmg'){
							$name = 'damage';
						}


						if($mod == 1){
							$text = getString($conn, 0, "prefix", $name, "");
							$row['name'] = $text." ".$row['name'];
						}


						if($mod == 2){
							$text = getString($conn, 0, "suffix", $name, "");
							$row['name'] = $row['name']." ".$text;
						}

					} else {
						$x = count($array);
					}

				}

			}
			$sql = "INSERT INTO \"equipmentinventory\" (\"script\", \"playerid\", \"price\", \"image\", \"name\", \"slot\", \"class\", \"basedmgmin\", \"basedmgmax\", ";
			$sql .= "\"basearmor\", \"level\", \"str\", \"dex\", \"spr\", \"vit\", \"mindmg\", \"maxdmg\", \"armor\", \"fireres\", \"earthres\", ";
			$sql .= "\"iceres\", \"arcaneres\", \"holyres\", \"maxhp\", \"maxmp\", \"regenhp\", \"regenmp\", \"evasion\", \"itemdrop\", \"silverdrop\", ";
			$sql .= "\"critchance\", \"critDamage\", \"blockChance\", \"template\", \"statString\", \"bonusPotHeal\", \"bonusPotMana\", \"expDrop\", \"healthPerc\", \"manaPerc\", \"strPerc\", \"vitPerc\", \"dexPerc\", \"sprPerc\", \"spellReduction\") VALUES ('".$row['script']."', ".$acc.", ".$row['price'].", '".$row['image']."', '".$row['name']."', '".$row['slot']."', '".$row['class']."', ";
			$sql .= $row['baseDmgMin'].", ".$row['baseDmgMax'].", ".$row['baseArmor'].", ".$row['level'].", ".$row['str'].", ".$row['dex'].", ".$row['spr'].", ";
			$sql .= $row['vit'].", ".$row['minDmg'].", ".$row['maxDmg'].", ".$row['armor'].", ".$row['fireRes'].", ".$row['earthRes'].", ".$row['iceRes'].", ";
			$sql .= $row['arcaneRes'].", ".$row['holyRes'].", ".$row['maxHP'].", ".$row['maxMP'].", ".$row['regenHP'].", ".$row['regenMP'].", ".$row['evasion'].", ";
			$sql .= $row['itemDrop'].", ".$row['silverDrop'].", ".$row['critchance'].", ".$row['critDamage'].", ".$row['blockChance'].", $id, '".$row['statString']."', ".$row['bonusPotHeal'].", ".$row['bonusPotMana'].", ".$row['expDrop'].", ".$row['healthPerc'].", ".$row['manaPerc'].", ".$row['strPerc'].", ".$row['vitPerc'].", ".$row['dexPerc'].", ".$row['sprPerc'].", ".$row['spellReduction'].")";
			sql_query($sql, $conn);

			if($row['script'] != "0"){
				$row['name'] = "<font color=\'gold\'>".$row['name']."</font>" ;
			}

			return $row['name'];
		}else{
			return generateUnique($conn, $acc, $id);
		}

	}

	// -- Function Name : generateItem
	// -- Params : $conn, $acc, $id, $levelOverride, $rollMods
	// -- Purpose : Creates an item based on item type, and level, returns name
	function generateUnique($conn, $acc, $id){
		$row = getRow($conn, "equipmenttemplate", $id);
		$class = $row['class'];

		$row['baseDmgMin'] = floor(rand($row['baseDmgMin'] * .9, $row['baseDmgMin'] * 1.1));
		$row['baseDmgMax'] = floor(rand($row['baseDmgMax'] * .9, $row['baseDmgMax'] * 1.1));
		$row['str'] = floor(rand($row['str'] * .7, $row['str'] * 1.3));
		$row['dex'] = floor(rand($row['dex'] * .7, $row['dex'] * 1.3));
		$row['spr'] = floor(rand($row['spr'] * .7, $row['spr'] * 1.3));
		$row['vit'] = floor(rand($row['vit'] * .7, $row['vit'] * 1.3));
		$row['minDmg'] = floor(rand($row['minDmg'] * .7, $row['minDmg'] * 1.3));
		$row['maxDmg'] = floor(rand($row['maxDmg'] * .7, $row['maxDmg'] * 1.3));
		$row['maxHP'] = floor(rand($row['maxHP'] * .7, $row['maxHP'] * 1.3));
		$row['maxMP'] = floor(rand($row['maxMP'] * .7, $row['maxMP'] * 1.3));
		$row['evasion'] = floor(rand($row['evasion'] * .7, $row['evasion'] * 1.3));
		$row['itemDrop'] = floor(rand($row['itemDrop'] * .7, $row['itemDrop'] * 1.3));
		$row['silverDrop'] = floor(rand($row['silverDrop'] * .7, $row['silverDrop'] * 1.3));
		$row['critchance'] = floor(rand($row['critchance'] * .7, $row['critchance'] * 1.3));
		$row['critDamage'] = floor(rand($row['critDamage'] * .7, $row['critDamage'] * 1.3));
		$row['bonusPotHeal'] = floor(rand($row['bonusPotHeal'] * .7, $row['bonusPotHeal'] * 1.3));
		$row['bonusPotMana'] = floor(rand($row['bonusPotMana'] * .7, $row['bonusPotMana'] * 1.3));
		$row['expDrop'] = floor(rand($row['expDrop'] * .7, $row['expDrop'] * 1.3));
		$row['healthPerc'] = floor(rand($row['healthPerc'] * .7, $row['healthPerc'] * 1.3));
		$row['manaPerc'] = floor(rand($row['manaPerc'] * .7, $row['manaPerc'] * 1.3));
		$row['strPerc'] = floor(rand($row['strPerc'] * .7, $row['strPerc'] * 1.3));
		$row['vitPerc'] = floor(rand($row['vitPerc'] * .7, $row['vitPerc'] * 1.3));
		$row['dexPerc'] = floor(rand($row['dexPerc'] * .7, $row['dexPerc'] * 1.3));
		$row['sprPerc'] = floor(rand($row['sprPerc'] * .7, $row['sprPerc'] * 1.3));
		$row['spellReduction'] = floor(rand($row['spellReduction'] * .7, $row['spellReduction'] * 1.3));
		$row['shapelessRes'] = floor(rand($row['shapelessRes'] * .7, $row['shapelessRes'] * 1.3));
		$row['shapelessExpDrop'] = floor(rand($row['shapelessExpDrop'] * .7, $row['shapelessExpDrop'] * 1.3));
		$row['shapelessDmg'] = floor(rand($row['shapelessDmg'] * .7, $row['shapelessDmg'] * 1.3));

		$row['statString'] = str_replace('[healthPerc]',$row['healthPerc'],$row['statString']);
		$row['statString'] = str_replace('[manaPerc]',$row['manaPerc'],$row['statString']);
		$row['statString'] = str_replace('[bonusPotHeal]',$row['bonusPotHeal'],$row['statString']);
		$row['statString'] = str_replace('[spellReduction]',$row['spellReduction'],$row['statString']);
		$row['statString'] = str_replace('[vitPerc]',$row['vitPerc'],$row['statString']);
		$row['statString'] = str_replace('[dexPerc]',$row['dexPerc'],$row['statString']);
		$row['statString'] = str_replace('[sprPerc]',$row['sprPerc'],$row['statString']);
		$row['statString'] = str_replace('[silverDrop]',$row['silverDrop'],$row['statString']);
		$row['statString'] = str_replace('[itemDrop]',$row['itemDrop'],$row['statString']);
		$row['statString'] = str_replace('[bonusPotMana]',$row['bonusPotMana'],$row['statString']);
		$row['statString'] = str_replace('[expDrop]',$row['expDrop'],$row['statString']);
		$row['statString'] = str_replace('[critchance]',$row['critchance'],$row['statString']);
		$row['statString'] = str_replace('[critDamage]',$row['critDamage'],$row['statString']);
		$row['statString'] = str_replace('[shapelessRes]',$row['shapelessRes'],$row['statString']);
		$row['statString'] = str_replace('[shapelessExpDrop]',$row['shapelessExpDrop'],$row['statString']);
		$row['statString'] = str_replace('[shapelessDmg]',$row['shapelessDmg'],$row['statString']);

		$sql = "INSERT INTO \"equipmentinventory\" (\"script\", \"playerid\", \"price\", \"image\", \"name\", \"slot\", \"class\", \"basedmgmin\", \"basedmgmax\", ";
		$sql .= "\"basearmor\", \"level\", \"str\", \"dex\", \"spr\", \"vit\", \"mindmg\", \"maxdmg\", \"armor\", \"fireres\", \"earthres\", ";
		$sql .= "\"iceres\", \"arcaneres\", \"holyres\", \"maxhp\", \"maxmp\", \"regenhp\", \"regenmp\", \"evasion\", \"itemdrop\", \"silverdrop\", ";
		$sql .= "\"critchance\", \"critDamage\", \"blockChance\", \"template\", \"statString\", \"bonusPotHeal\", \"bonusPotMana\", \"expDrop\", \"healthPerc\", ";
		$sql .= "\"manaPerc\", \"strPerc\", \"vitPerc\", \"dexPerc\", \"sprPerc\", \"spellReduction\", \"shapelessRes\", \"shapelessExpDrop\", \"shapelessDmg\") VALUES ('";
		$sql .= $row['script']."', ".$acc.", ".$row['price'].", '".$row['image']."', '".$row['name']."', '".$row['slot']."', '".$row['class']."', ";
		$sql .= $row['baseDmgMin'].", ".$row['baseDmgMax'].", ".$row['baseArmor'].", ".$row['level'].", ".$row['str'].", ".$row['dex'].", ".$row['spr'].", ";
		$sql .= $row['vit'].", ".$row['minDmg'].", ".$row['maxDmg'].", ".$row['armor'].", ".$row['fireRes'].", ".$row['earthRes'].", ".$row['iceRes'].", ";
		$sql .= $row['arcaneRes'].", ".$row['holyRes'].", ".$row['maxHP'].", ".$row['maxMP'].", ".$row['regenHP'].", ".$row['regenMP'].", ".$row['evasion'].", ";
		$sql .= $row['itemDrop'].", ".$row['silverDrop'].", ".$row['critchance'].", ".$row['critDamage'].", ".$row['blockChance'].", $id, '".$row['statString']."', ";
		$sql .= $row['bonusPotHeal'].", ".$row['bonusPotMana'].", ".$row['expDrop'].", ".$row['healthPerc'].", ".$row['manaPerc'].", ".$row['strPerc'].", ";
		$sql .= $row['vitPerc'].", ".$row['dexPerc'].", ".$row['sprPerc'].", ".$row['spellReduction'].", ".$row['shapelessRes'].", ".$row['shapelessExpDrop'].", ".$row['shapelessDmg'].")";
		sql_query($sql, $conn);

		if(in_array($id, [51,52,53,54,55,56,57,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74])){
			$row['name'] = "<font color=\'#ff6a01\'>".$row['name']."</font>" ;
		}else if(in_array($id, [83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104])){
			$row['name'] = "<font color=\'#cc00ff\'>".$row['name']."</font>" ;
		}else if($row['script'] != "0"){
			$row['name'] = "<font color=\'gold\'>".$row['name']."</font>" ;
		}

		return $row['name'];
	}

	// -- Function Name : buyEquipment
	// -- Params : $conn, $itemList, $acc, $payReqd
	// -- Purpose : Processes a buy transaction, taking a list of items as args, returns success or fail
	function buyEquipment($conn, $itemList, $acc, $payReqd){
		$items = explode("|", $itemList);
		$counter = 0;
		while ($counter != (count($items) - 1)){
			$item = $items[$counter];
			$row = getRow($conn, "equipmenttemplate", $item);
			$cost = $row['price'];
			$silver = getAttribute($conn, "character", "silver", $acc);

			if($cost > $silver && $payReqd){
				return '<span style="color:red;">Transaction Failed</span>';
			} else {
				sql_query("UPDATE \"character\" set \"silver\"=\"silver\" - ".$cost." where \"playerid\"=".$acc, $conn);
				$row['price'] = $row['price'] / 3;
				// Use null coalescing operator to provide defaults for missing/NULL columns
				$sql = "INSERT INTO \"equipmentinventory\" (\"script\", \"playerid\", \"price\", \"image\", \"name\", \"slot\", \"class\", \"basedmgmin\", \"basedmgmax\", ";
				$sql .= "\"basearmor\", \"level\", \"str\", \"dex\", \"spr\", \"vit\", \"mindmg\", \"maxdmg\", \"armor\", \"fireres\", \"earthres\", ";
				$sql .= "\"iceres\", \"arcaneres\", \"holyres\", \"maxhp\", \"maxmp\", \"regenhp\", \"regenmp\", \"evasion\", \"itemdrop\", \"silverdrop\", ";
				$sql .= "\"critchance\", \"critDamage\", \"blockChance\", \"template\", \"statString\", \"bonusPotHeal\",  \"bonusPotMana\", \"expDrop\", \"healthPerc\", \"manaPerc\", \"strPerc\", \"vitPerc\", \"dexPerc\", \"sprPerc\", \"spellReduction\") VALUES ('".(($row['script'] ?? ''))."', ".$acc.", ".$row['price'].", '".(($row['image'] ?? ''))."', '".(($row['name'] ?? ''))."', '".(($row['slot'] ?? ''))."', '".(($row['class'] ?? ''))."', ";
				$sql .= ($row['basedmgmin'] ?? 0).", ".($row['basedmgmax'] ?? 0).", ".($row['basearmor'] ?? 0).", ".($row['level'] ?? 0).", ".($row['str'] ?? 0).", ".($row['dex'] ?? 0).", ".($row['spr'] ?? 0).", ";
				$sql .= ($row['vit'] ?? 0).", ".($row['mindmg'] ?? 0).", ".($row['maxdmg'] ?? 0).", ".($row['armor'] ?? 0).", ".($row['fireres'] ?? 0).", ".($row['earthres'] ?? 0).", ".($row['iceres'] ?? 0).", ";
				$sql .= ($row['arcaneres'] ?? 0).", ".($row['holyres'] ?? 0).", ".($row['maxhp'] ?? 0).", ".($row['maxmp'] ?? 0).", ".($row['regenhp'] ?? 0).", ".($row['regenmp'] ?? 0).", ".($row['evasion'] ?? 0).", ";
				$sql .= ($row['itemdrop'] ?? 0).", ".($row['silverdrop'] ?? 0).", ".($row['critchance'] ?? 0).", ".($row['critdamage'] ?? 0).", ".($row['blockchance'] ?? 0).", ".($row['index'] ?? 0)." ,'".($row['statstring'] ?? '')."', ".($row['bonuspotheal'] ?? 0).", ".($row['bonuspotmana'] ?? 0).", ".($row['expdrop'] ?? 0).", ".($row['healthperc'] ?? 0).", ".($row['manaperc'] ?? 0).", ".($row['strperc'] ?? 0).", ".($row['vitperc'] ?? 0).", ".($row['dexperc'] ?? 0).", ".($row['sprperc'] ?? 0).", ".($row['spellreduction'] ?? 0).")";
				sql_query($sql, $conn);

				if(($row['script'] ?? '0') != "0"){
					$row['name'] = "<font color=\'gold\'>".(($row['name'] ?? ''))."</font>" ;  //SKIP
				}

				$counter++;
			}

		}

		return '<span style="color:green;">Transaction Successful</span>';
	}

	// -- Function Name : sellEquipment
	// -- Params : $conn, $itemList, $acc
	// -- Purpose : Processes a buy transaction, taking a list of items as args, returns success or fail
	function sellEquipment($conn, $itemList, $acc){
		$items = explode("|", $itemList);
		$counter = 0;
		while ($counter != (count($items) - 1)){
			$item = $items[$counter];
			$row = getRow($conn, "equipmentinventory", $item);
			$cost = $row['price'];

			if(($acc ==  $row['playerid']) && ($row['equipped'] == 0)){
				$sql = "DELETE FROM \"equipmentinventory\" WHERE \"index\" = ".$item;
				sql_query($sql, $conn);
				sql_query("UPDATE \"character\" set \"silver\"=\"silver\" + ".$cost." where \"playerid\"=".$acc, $conn);
			}

			$counter++;
		}

		return '<span style="color:green;">Transaction Successful</span>';
	}

	// -- Function Name : "getSingleEquipment"
	// -- Params : $conn, $id
	// -- Purpose : gets description text for a single equipment
	function getSingleEquipment($conn, $id){
		if($id == -1){
			return -1;
		}

		$sql = "SELECT * FROM equipmenttemplate WHERE \"index\" = ".$id;
		$sql_rows = sql_query($sql, $conn);
		while($row = $sql_rows->fetch()){
			$script = "<strong>Item Name: </strong>".$row['name']."<br/><br/><strong>Item Class: </strong>";

			if($row['slot'] == "weapon" || $row['slot'] == "2hweapon"){
				$script .= "Weapon - ".ucfirst($row['class'])."<br/><br/>";
				$script .= "<strong>Damage: </strong>".$row['baseDmgMin']." - ".$row['baseDmgMax']."<br/><br/>";
			} else {
				$script .= ucfirst($row['slot'])."<br/><br/>";

				if($row['baseArmor'] > 0){
					$script .= "<strong>Armor: </strong>".$row['baseArmor']."<br/><br/>";
				}

			}


			if($row['str'] > 0){
				$script .= "<strong>Strength: </strong>".$row['str']."<br/>";
			}


			if($row['dex'] > 0){
				$script .= "<strong>Dexterity: </strong>".$row['dex']."<br/>";
			}


			if($row['spr'] > 0){
				$script .= "<strong>Spirit: </strong>".$row['spr']."<br/>";
			}


			if($row['vit'] > 0){
				$script .= "<strong>Vitality: </strong>".$row['vit']."<br/>";
			}


			if($row['minDmg'] > 0){
				$script .= "<strong>Bonus Min Damage: </strong>".$row['minDmg']."<br/>";
			}


			if($row['maxDmg'] > 0){
				$script .= "<strong>Bonus Max Damage: </strong>".$row['maxDmg']."<br/>";
			}


			if($row['armor'] > 0){
				$script .= "<strong>Bonus Armor: </strong>".$row['armor']."<br/>";
			}


			if($row['fireRes'] > 0){
				$script .= "<strong>Fire Resistance: </strong>".$row['fireRes']."%<br/>";
			}


			if($row['iceRes'] > 0){
				$script .= "<strong>Ice Resistance: </strong>".$row['iceRes']."%<br/>";
			}


			if($row['arcaneRes'] > 0){
				$script .= "<strong>Arcane Resistance: </strong>".$row['arcaneRes']."%<br/>";
			}


			if($row['earthRes'] > 0){
				$script .= "<strong>Earth Resistance: </strong>".$row['earthRes']."%<br/>";
			}


			if($row['holyRes'] > 0){
				$script .= "<strong>Holy Resistance: </strong>".$row['holyRes']."%<br/>";
			}


			if($row['maxHP'] > 0){
				$script .= "<strong>Maximum HP: </strong>".$row['maxHP']."<br/>";
			}


			if($row['maxMP'] > 0){
				$script .= "<strong>Maximum MP: </strong>".$row['maxMP']."<br/>";
			}


			if($row['regenHP'] > 0){
				$script .= "<strong>HP Regen: </strong>".$row['regenHP']."<br/>";
			}


			if($row['regenMP'] > 0){
				$script .= "<strong>MP Regen: </strong>".$row['regenMP']."<br/>";
			}


			if($row['evasion'] > 0){
				$script .= "<strong>Evasion: </strong>".$row['evasion']."<br/>";
			}


			if($row['itemDrop'] > 0){
				$script .= "<strong>Item Drop Increase: </strong>".$row['itemDrop']."%<br/>";
			}


			if($row['silverDrop'] > 0){
				$script .= "<strong>Silver Drop Increase: </strong>".$row['silverDrop']."%<br/>";
			}


			if($row['critchance'] > 0){
				$script .= "<strong>Bonus Crit Chance: </strong>".$row['critchance']."%<br/>";
			}


			if($row['critDamage'] > 0){
				$script .= "<strong>Bonus Crit Modifier: </strong>".$row['critDamage']."%<br/>";
			}


			if($row['blockChance'] > 0){
				$script .= "<strong>Block Rate: </strong>".$row['blockChance']."%<br/>";
			}


			if($row['statString'] != ''){
				$script .= "<br/><strong style=\"color:cyan\">".$row['statString']."</strong>";
			}

			$output = $row['index']."|".$row['name']."|".$row['image']."|".$row['slot']."|".$script."|".$row['price'];
			return $output;
		}

	}

	// -- Function Name : getEquipment
	// -- Params : $conn, $acc
	// -- Purpose : Gets the description text for all equipment
	function getEquipment($conn, $acc){
		$output = array();
		$sql = "SELECT * FROM equipmentinventory WHERE name != 'unarmed' AND archived != 1 AND playerid = ".$acc;
		$sql_rows = sql_query($sql, $conn);
		$check = false;
		while($row = $sql_rows->fetch()){
			if($row['upgrade'] > 0){
				$row["name"] = "+" . $row['upgrade'] . " " . $row["name"];
			}
			$check = true;

			if(in_array((int)$row['template'], [51,52,53,54,55,56,57,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74])){
				$script = "<strong>Item Name: </strong><font color=\"#ff6a01\">".$row['name']."</font><br/><br/>";
				$script .= "<font style=\"font-style: italic;\">".$row['script']."</font>";
				$script .= "<br/><br/><strong>Item Class: </strong>";
			}else if(in_array((int)$row['template'], [83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104])){
				$script = "<strong>Item Name: </strong><font color=\"#cc00ff\">".$row['name']."</font><br/><br/>";
				$script .= "<font style=\"font-style: italic;\">".$row['script']."</font>";
				$script .= "<br/><br/><strong>Item Class: </strong>";
			}else if($row['script'] != "0"){
				$script = "<strong>Item Name: </strong><font color=\"gold\">".$row['name']."</font><br/><br/>";
				$script .= "<font style=\"font-style: italic;\">".$row['script']."</font>";
				$script .= "<br/><br/><strong>Item Class: </strong>";
			}else{
				$script = "<strong>Item Name: </strong>".$row['name']."<br/><br/><strong>Item Class: </strong>";
			}


			if($row['slot'] == "weapon" || $row['slot'] == "2hweapon"){
				$script .= "Weapon - ".ucfirst($row['class'])."<br/><br/>";
				$script .= "<strong>Damage: </strong>".($row['basedmgmin'] ?? 0)." - ".($row['basedmgmax'] ?? 0)."<br/><br/>";
			} else {
				$script .= ucfirst($row['slot'])."<br/><br/>";

				if(($row['basearmor'] ?? 0) > 0){
					$script .= "<strong>Armor: </strong>".$row['basearmor']."<br/><br/>";
				}

			}


			if(($row['str'] ?? 0) > 0){
				$script .= "<strong>Strength: </strong>".$row['str']."<br/>";
			}


			if(($row['dex'] ?? 0) > 0){
				$script .= "<strong>Dexterity: </strong>".$row['dex']."<br/>";
			}


			if(($row['spr'] ?? 0) > 0){
				$script .= "<strong>Spirit: </strong>".$row['spr']."<br/>";
			}


			if(($row['vit'] ?? 0) > 0){
				$script .= "<strong>Vitality: </strong>".$row['vit']."<br/>";
			}


			if(($row['mindmg'] ?? 0) > 0){
				$script .= "<strong>Bonus Min Damage: </strong>".$row['mindmg']."<br/>";
			}


			if(($row['maxdmg'] ?? 0) > 0){
				$script .= "<strong>Bonus Max Damage: </strong>".$row['maxdmg']."<br/>";
			}


			if(($row['armor'] ?? 0) > 0){
				$script .= "<strong>Bonus Armor: </strong>".$row['armor']."<br/>";
			}


			if(($row['fireres'] ?? 0) > 0){
				$script .= "<strong>Fire Resistance: </strong>".$row['fireres']."%<br/>";
			}


			if(($row['iceres'] ?? 0) > 0){
				$script .= "<strong>Ice Resistance: </strong>".$row['iceres']."%<br/>";
			}


			if(($row['arcaneres'] ?? 0) > 0){
				$script .= "<strong>Arcane Resistance: </strong>".$row['arcaneres']."%<br/>";
			}


			if(($row['earthres'] ?? 0) > 0){
				$script .= "<strong>Earth Resistance: </strong>".$row['earthres']."%<br/>";
			}


			if(($row['holyres'] ?? 0) > 0){
				$script .= "<strong>Holy Resistance: </strong>".$row['holyres']."%<br/>";
			}


			if(($row['maxhp'] ?? 0) > 0){
				$script .= "<strong>Maximum HP: </strong>".$row['maxhp']."<br/>";
			}


			if(($row['maxmp'] ?? 0) > 0){
				$script .= "<strong>Maximum MP: </strong>".$row['maxmp']."<br/>";
			}


			if(($row['regenhp'] ?? 0) > 0){
				$script .= "<strong>HP Regen: </strong>".$row['regenhp']."<br/>";
			}


			if(($row['regenmp'] ?? 0) > 0){
				$script .= "<strong>MP Regen: </strong>".$row['regenmp']."<br/>";
			}


			if(($row['evasion'] ?? 0) > 0){
				$script .= "<strong>Evasion: </strong>".$row['evasion']."<br/>";
			}


			if(($row['itemdrop'] ?? 0) > 0){
				$script .= "<strong>Item Drop Increase: </strong>".$row['itemdrop']."%<br/>";
			}


			if(($row['silverdrop'] ?? 0) > 0){
				$script .= "<strong>Silver Drop Increase: </strong>".$row['silverdrop']."%<br/>";
			}


			if(($row['critchance'] ?? 0) > 0){
				$script .= "<strong>Bonus Crit Chance: </strong>".$row['critchance']."%<br/>";
			}


			if(($row['critdamage'] ?? 0) > 0){
				$script .= "<strong>Bonus Crit Modifier: </strong>".$row['critdamage']."%<br/>";
			}


			if(($row['blockchance'] ?? 0) > 0){
				$script .= "<strong>Block Rate: </strong>".$row['blockchance']."%<br/>";
			}


			if(($row['statstring'] ?? '') != ''){
				$script .= "<br/><strong style=\"color:cyan\">".$row['statstring']."</strong>";
			}

			$output[] = ($row['index'] ?? 0)."|".($row['name'] ?? '')."|".($row['image'] ?? '')."|".($row['slot'] ?? '')."|".($script ?? '')."|".($row['script'] ?? '')."|".($row['price'] ?? 0)."|".($row['equipped'] ?? 0)."|".($row['stored'] ?? 0)."|".($row['template'] ?? 0);
		}


		if($check){
			return $output;
		} else {
			return "";
		}

	}

	function getEquipmentItem($conn, $item, $crafting){
		$output = array();
		$sql = "SELECT * FROM equipmentinventory WHERE \"index\" = $item";
		$sql_rows = sql_query($sql, $conn);
		$check = false;
		while($row = $sql_rows->fetch()){
			$check = true;
			if($crafting){
				$row['name'] = "+".($row['upgrade'] + 1)." ".$row['name'];
			}
			if(in_array($row['template'], [51,52,53,54,55,56,57,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74])){
				$script = "<strong>Item Name: </strong><font color=\"#ff6a01\">".$row['name']."</font><br/><br/>";
				$script .= "<font style=\"font-style: italic;\">".$row['script']."</font>";
				$script .= "<br/><br/><strong>Item Class: </strong>";
			}else if(in_array($row['template'], [83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104])){
				$script = "<strong>Item Name: </strong><font color=\"#cc00ff\">".$row['name']."</font><br/><br/>";
				$script .= "<font style=\"font-style: italic;\">".$row['script']."</font>";
				$script .= "<br/><br/><strong>Item Class: </strong>";
			}else if($row['script'] != "0"){
				$script = "<strong>Item Name: </strong><font color=\"gold\">".$row['name']."</font><br/><br/>";
				$script .= "<font style=\"font-style: italic;\">".$row['script']."</font>";
				$script .= "<br/><br/><strong>Item Class: </strong>";
			}else{
				$script = "<strong>Item Name: </strong>".$row['name']."<br/><br/><strong>Item Class: </strong>";
			}


			if($row['slot'] == "weapon" || $row['slot'] == "2hweapon"){
				$script .= "Weapon - ".ucfirst($row['class'])."<br/><br/>";
				$script .= "<strong>Damage: </strong>".$row['baseDmgMin']." - ".$row['baseDmgMax']."<br/><br/>";
			} else {
				$script .= ucfirst($row['slot'])."<br/><br/>";

				if($row['baseArmor'] > 0){
					$script .= "<strong>Armor: </strong>".$row['baseArmor']."<br/><br/>";
				}

			}


			if($row['str'] > 0){
				$script .= "<strong>Strength: </strong>".$row['str']."<br/>";
			}


			if($row['dex'] > 0){
				$script .= "<strong>Dexterity: </strong>".$row['dex']."<br/>";
			}


			if($row['spr'] > 0){
				$script .= "<strong>Spirit: </strong>".$row['spr']."<br/>";
			}


			if($row['vit'] > 0){
				$script .= "<strong>Vitality: </strong>".$row['vit']."<br/>";
			}


			if($row['minDmg'] > 0){
				$script .= "<strong>Bonus Min Damage: </strong>".$row['minDmg']."<br/>";
			}


			if($row['maxDmg'] > 0){
				$script .= "<strong>Bonus Max Damage: </strong>".$row['maxDmg']."<br/>";
			}


			if($row['armor'] > 0){
				$script .= "<strong>Bonus Armor: </strong>".$row['armor']."<br/>";
			}


			if($row['fireRes'] > 0){
				$script .= "<strong>Fire Resistance: </strong>".$row['fireRes']."%<br/>";
			}


			if($row['iceRes'] > 0){
				$script .= "<strong>Ice Resistance: </strong>".$row['iceRes']."%<br/>";
			}


			if($row['arcaneRes'] > 0){
				$script .= "<strong>Arcane Resistance: </strong>".$row['arcaneRes']."%<br/>";
			}


			if($row['earthRes'] > 0){
				$script .= "<strong>Earth Resistance: </strong>".$row['earthRes']."%<br/>";
			}


			if($row['holyRes'] > 0){
				$script .= "<strong>Holy Resistance: </strong>".$row['holyRes']."%<br/>";
			}


			if($row['maxHP'] > 0){
				$script .= "<strong>Maximum HP: </strong>".$row['maxHP']."<br/>";
			}


			if($row['maxMP'] > 0){
				$script .= "<strong>Maximum MP: </strong>".$row['maxMP']."<br/>";
			}


			if($row['regenHP'] > 0){
				$script .= "<strong>HP Regen: </strong>".$row['regenHP']."<br/>";
			}


			if($row['regenMP'] > 0){
				$script .= "<strong>MP Regen: </strong>".$row['regenMP']."<br/>";
			}


			if($row['evasion'] > 0){
				$script .= "<strong>Evasion: </strong>".$row['evasion']."<br/>";
			}


			if($row['itemDrop'] > 0){
				$script .= "<strong>Item Drop Increase: </strong>".$row['itemDrop']."%<br/>";
			}


			if($row['silverDrop'] > 0){
				$script .= "<strong>Silver Drop Increase: </strong>".$row['silverDrop']."%<br/>";
			}


			if($row['critchance'] > 0){
				$script .= "<strong>Bonus Crit Chance: </strong>".$row['critchance']."%<br/>";
			}


			if($row['critDamage'] > 0){
				$script .= "<strong>Bonus Crit Modifier: </strong>".$row['critDamage']."%<br/>";
			}


			if($row['blockChance'] > 0){
				$script .= "<strong>Block Rate: </strong>".$row['blockChance']."%<br/>";
			}


			if($row['statString'] != ''){
				$script .= "<br/><strong style=\"color:cyan\">".$row['statString']."</strong>";
			}

			$output[] = $row['index']."|".$row['name']."|".$row['image']."|".$row['slot']."|".$script."|".$row['script']."|".$row['price']."|".$row['equipped']."|".$row['stored'];
		}


		if($check){
			return $output;
		} else {
			return "";
		}

	}



	// -- Function Name : useItemNonCombat
	// -- Params : $acc, $item, $conn
	// -- Purpose : Uses an item, applying all needed effects to player outside combat
	function useItemNonCombat($acc, $item, $conn, $amnt){

			if(getInventoryItem($conn, $acc, $item) > 0){
				$sql_get_item = "select * from item where item_id =".$item;
				$sql_item_result = sql_query($sql_get_item, $conn);
				while($row = $sql_item_result->fetch()){

				if($row['usable'] == 1){
					$minVal1 = $row['useMin'] * $amnt;
					$maxVal1 = $row['useMax'] * $amnt;
					$minVal2 = $row['combatMin'] * $amnt;
					$maxVal2 = $row['combatMax'] * $amnt;
					$Action1 = $row['useType'];
					$used = false;
					$name = $row['name'];
					$return = $amnt . "x " . $row['name']."<span> Used!<br>";
					switch($Action1){
						case 0:
							//DO NOTHING
							break;
						case 1:
							//HEAL
							$value =  floor(rand($minVal1,$maxVal1) * ((getAttribute($conn, "calcValues", "bonusPotHeal", $acc) / 100 + 1)));
							heal($value, $acc, $conn);
							$return .= $value.' Hitpoints Restored';
							$used = true;
							break;
						case 2:
							//Restore mana
							$value =  floor(rand($minVal1,$maxVal1) * ((getAttribute($conn, "calcValues", "bonusPotMana", $acc) / 100 + 1)));
							mana($value, $acc, $conn);
							$return .= $value.' Mana Restored';
							$used = true;
							break;
						case 8:
								//Restore mana AND health
								$value =  floor(rand($minVal1,$maxVal1) * ((getAttribute($conn, "calcValues", "bonusPotHeal", $acc) / 100 + 1)));
								heal($value, $acc, $conn);
								$return .= $value.' Hitpoints Restored And ';
								$value =  floor(rand($minVal1,$maxVal1) * ((getAttribute($conn, "calcValues", "bonusPotMana", $acc) / 100 + 1)));
								mana($value, $acc, $conn);
								$return .= $value.' Mana Restored';
								$used = true;
								break;
						case 3:
							//CHEST (RESET?)
							$maxSQL = "SELECT SUM(rate) as total FROM \"chests\" where chestID = $item";
							$max = mysqli_fetch_array(sql_query($maxSQL, $conn),MYSQLI_ASSOC)['total'];
							$roll = rand (0,($max - 1)) + 1;
							$sql_get_items = "SELECT * FROM \"chests\" where chestID = $item";
							$sql_result = sql_query($sql_get_items, $conn);
							$totalWeight = 0;
							while($row = mysqli_fetch_array($sql_result,MYSQLI_ASSOC)){
								$totalWeight =  intval($totalWeight) + intval($row['rate']);

								if($totalWeight >= $roll && $used == false){

									if($row['itemid'] > 0){
										$count = $row['amnt'];
										$return = "<span>$name opened<br/>".$count."x ".getAttribute($conn, "item", "name", $row['itemid'])." was inside!<br/>";
										addItemAmount($conn, $acc, $row['itemid'], $count);
									} else {
										$return = "<span>$name opened<br/>".generateUnique($conn, $acc, ($row['itemid'] * -1))." was inside!<br/>";
									}

									$used = true;
								}

							}

							break;
						case 4:

							if($minVal1 == 0 && $maxVal1 == 0){
								toSpawn($acc, $conn);
								$return = "telestone";
								$used = true;
							}

							break;
						case 5:
							//USE BUFFS HERE
							$newItem = 0;
							if($item == 86 || $item == 87 || $item == 88){
								$newItem = 87;
							}else{
								$newItem = $item;
							}

							$sql = 'select * from playerbuffs where itemid = '.$newItem.' and playerid = '.$acc;
							$rowset = sql_query($sql, $conn);

							if(mysqli_num_rows($rowset) == 0){
								$bonus = explode('|', $row['shortScript']);
								$columns = '';
								$values = '';
								forEach($bonus as &$value) {
									$NVP = explode('-', $value);
									$columns .= $NVP[0].', ';
									$values .= $NVP[1].', ';
								}

								$sql = 'insert into playerbuffs ('.$columns.' itemid, playerid, name, image, remaining, script) values ('.$values.$newItem.', '.$acc.', "';
								$sql .= $row['name'].'", "'.$row['secondImage'].'", '.$row['duration'] * $amnt.', "'.$row['description'].'")';
							} else {
								$sql = 'update playerbuffs set remaining = remaining + '.$row['duration'] * $amnt.' where itemid = '.$newItem.' and playerid = '.$acc;
							}
							sql_query($sql, $conn);
							$return = $row['duration'] * $amnt.' turns of buff recieved';
							$used = true;
							break;
						case 6:
							//CHEST
							$maxSQL = "SELECT SUM(rate) as total FROM \"chests\" where chestID = $item";
							$max = mysqli_fetch_array(sql_query($maxSQL, $conn),MYSQLI_ASSOC)['total'];
							$roll = rand (0,($max - 1)) + 1;
							$sql_get_items = "SELECT * FROM \"chests\" where chestID = $item";
							$sql_result = sql_query($sql_get_items, $conn);
							$totalWeight = 0;
							while($row = mysqli_fetch_array($sql_result,MYSQLI_ASSOC)){
								$totalWeight =  intval($totalWeight) + intval($row['rate']);

								if($totalWeight >= $roll && $used == false){

									if($row['itemid'] > 0){
										$count = $row['amnt'];
										$return = "<span>$name opened<br/>".$count."x ".getAttribute($conn, "item", "name", $row['itemid'])." was inside!<br/>";
										addItemAmount($conn, $acc, $row['itemid'], $count);
									} else {
										$level = rand($minVal1,$maxVal1);
										$rollMod = sqrt(rand($minVal2,$maxVal2));
										$return = "<span>$name opened<br/>".generateItem($conn, $acc, ($row['itemid'] * -1), $level, $rollMod)." was inside!<br/>";
									}

									$used = true;
								}

							}

							break;
						case 7:
							//RESTORE
							$charStats = getRow($conn, "calcValues", $acc);
							$rAmnt = rand($minVal1,$maxVal1) / 100;
							floor($mana = ($charStats["maxMana"] * $rAmnt));
							floor($health = ($charStats["maxHealth"] * $rAmnt));
							heal($health, $acc, $conn);
							mana($mana, $acc, $conn);
							$used = true;
							$return .= "$health Hitpoints Restored, $mana Mana Restored";
							break;
						case 100:
							//STAT RESET
							$resets = 0; //getAttribute($conn, "account", "paleResets", $acc); FIX
							$row = getRow($conn, "character", $acc);
							$points = ($row["strength"] - (5 + $resets * 2)) + ($row["dexterity"] - 3) + ($row["vitality"] - 3)+ ($row["spirit"] - 1);
							$sql = "update \"character\" set \"strength\" = (5 + $resets * 2), \"dexterity\" = 3, \"vitality\" = 3, \"spirit\" = 1, statPoints = statPoints + $points where playerid = $acc";
							sql_query($sql, $conn);
							$used = true;
							$return .= "All stat points have now been reset";
							break;
						case 101:
							//SKILL RESET
							$sql = "update \"character\" set skillPoints = skillPoints + (select sum(level) from charSkills where playerid = $acc) where playerid = $acc";
							sql_query($sql, $conn);
							$sql = "delete from charSkills where playerid = $acc";
							sql_query($sql, $conn);
							sql_query('UPDATE equippedStuff SET "skill_1"= -1, "skill_2"= -1, "skill_3"= -1, "skill_4"= -1 WHERE equipIndex='.$acc, $conn);
							$used = true;
							$return .= "All skill points have now been reset";
							break;
						case 102:
							//BOTH RESET
							$resets = 1; // FIX getAttribute($conn, "account", "paleResets", $acc);
							$row = getRow($conn, "character", $acc);
							$points = ($row["strength"] - (5 + $resets * 2)) + ($row["dexterity"] - 3) + ($row["vitality"] - 3)+ ($row["spirit"] - 1);
							$sql = "update \"character\" set \"strength\" = (5 + $resets * 2), \"dexterity\" = 3, \"vitality\" = 3, \"spirit\" = 1, statPoints = statPoints + $points where playerid = $acc";
							sql_query($sql, $conn);
							$sql = "update \"character\" set skillPoints = skillPoints + (select sum(level) from charSkills where playerid = $acc) where playerid = $acc";
							sql_query($sql, $conn);
							$sql = "delete from charSkills where playerid = $acc";
							sql_query($sql, $conn);
							sql_query('UPDATE equippedStuff SET "skill_1"= -1, "skill_2"= -1, "skill_3"= -1, "skill_4"= -1 WHERE equipIndex='.$acc, $conn);
							$used = true;
							$return .= "All stat and skill points have now been reset";
							break;
				}


				if($used == true){
					removeItemAmount($conn, $acc, $item, $amnt, true);
					return $return;
				} else {
					return "error!";
				}

			}

		}

	}

	}

	// -- Function Name : giveEXPnoncombat
	// -- Params : $acc, $amnt, $conn
	// -- Purpose : Awards a character with exp listed in args outside combat
	function giveEXPnoncombat($acc, $amnt, $conn){
		$levelup = getAttribute($conn, "character", "next", $acc);
		$current = getAttribute($conn, "character", "exp", $acc);
		$level = getAttribute($conn, "character", "level", $acc) + 1;
		$diff = $current + $amnt - $levelup;

		if($current + $amnt + 1 > $levelup){
			levelUpNoncombat($acc, $diff, $conn, $level);

				if($level == 50){
					$sql = "UPDATE \"equipmentinventory\" SET \"archived\" = 0 WHERE playerid = $acc";
					sql_query($sql, $conn);
					$sql = "UPDATE \"inventory\" SET \"count\" = \"count\" + \"archived\" WHERE playerid = $acc";
					sql_query($sql, $conn);
					$sql = "UPDATE \"inventory\" SET \"archived\" = 0 WHERE playerid = $acc";
					sql_query($sql, $conn);
				}

		} else {
			$sql = "UPDATE \"character\" SET \"exp\" = \"exp\" + ".$amnt." WHERE playerid = '".$acc."'";
			sql_query($sql, $conn);
		}

	}

	// -- Function Name : levelUpNoncombat
	// -- Params : $acc, $diff, $conn, $level
	// -- Purpose : Quest based level up, updates stats
	function levelUpNoncombat($acc, $diff, $conn, $level){
		if($level > 49){
			setAchievement($conn, $acc, 15);
		}
		logAction($conn, $acc, 'levelUp', $level, NULL);
		$sql = "SELECT exp FROM exp WHERE level = $level";
		$row = sql_query($sql, $conn)->fetch();
		$nextLevel = $row['exp'];
		$class = getAttribute($conn, "character", "class", $acc);

		if($class == "Paladin"){
			$str = 2;
			$dex = 1;
			$spr = 0;
		} else
		if($class == "Assassin"){
			$str = 0;
			$dex = 2;
			$spr = 1;
		} else {
			$str = 1;
			$dex = 0;
			$spr = 2;
		}

		$sql = "UPDATE \"character\" SET \"level\" = \"level\" + 1, \"exp\" = ".$diff.", \"next\" = ".$nextLevel.", \"statpoints\" = \"statpoints\" + 4, \"skillpoints\" = \"skillpoints\" + 1,";
		$sql .= " \"strength\" = \"strength\" + ".$str.", \"dexterity\" = \"dexterity\" + ".$dex.", \"spirit\" = \"spirit\" + ".$spr." WHERE playerid = ".$acc;
		sql_query($sql, $conn);
		fullheal ($acc, $conn);
		fullmana ($acc, $conn);
	}

	// -- Function Name : allocateStat
	// -- Params : $conn, $acc, $stat
	// -- Purpose : Allocates a single stat point
	function allocateStat($conn, $acc, $stat){
		$sql = "UPDATE \"character\" SET ".$stat." = ".$stat." + 1, \"statPoints\" = \"statPoints\" - 1 WHERE \"statPoints\" > 0 AND \"playerid\" = ".$acc;
		sql_query($sql, $conn);
	}

	// -- Function Name : buyItem
	// -- Params : $item, $quan, $acc, $conn
	// -- Purpose : Verifies, and buys a single item provided in args
	function buyItem($item, $quan, $acc, $conn){
		$cost = getAttribute($conn, "item", "value", $item) * $quan;
		$money = getAttribute($conn, "character", "silver", $acc);

		if($cost > $money){
			return '<span style="color:red;">Transaction Failed</span>';
		} else {
			addItemAmount($conn, $acc, $item, $quan);
			sql_query("UPDATE \"character\" set \"silver\"=\"silver\" - ".$cost." where \"playerid\"=".$acc, $conn);
			return '<span style="color:green;">Transaction Successful</span>';
		}

	}

	// -- Function Name : buyItems
	// -- Params : $conn, $itemList, $acc
	// -- Purpose : Processes a buy transaction, taking a list of items as args, returns success or fail
	function buyItems($conn, $itemList, $acc){
		$items = explode("-", $itemList);
		$counter = 0;
		while ($counter != (count($items) - 1)){
			$item = explode("|", $items[$counter]);
			$message = buyItem($item[0], $item[1], $acc, $conn);
			$counter++;
		}

		return $message;
	}

	// -- Function Name : equipItem
	// -- Params : $acc, $item, $conn
	// -- Purpose : Attempts to equip item to account listed in args in first available inventory slot
	function equipItem($acc, $item, $conn){

		if(getInventoryItem($conn, $acc, $item) > 0){
			$equipped = json_decode(getEquippedItems($conn, $acc));

			if(array_search($item, $equipped) > -1){
				return '<span style="color:red;">Item already equipped!</span>';
			}

			$equipTo = array_search("-1", $equipped);

			if($equipTo > -1){
				sql_query('UPDATE equippedStuff SET "item_'.($equipTo + 1).'"= '.$item.' WHERE equipIndex='.$acc, $conn);
				return '<span style="color:green;">Item added to battle ready list!</span>';
			}

			return '<span style="color:red;">You must remove something before equipping this!</span>';
		}

		return '<span style="color:red;">You do not have this item!</span>';
	}

	// -- Function Name : equipSkill
	// -- Params : $acc, $skill, $conn
	// -- Purpose : Attempts to equip item to account listed in args in first available inventory slot
	function equipSkill($acc, $skill, $conn){
		$skillRow = getRow($conn, "skills", $skill);
		$type = $skillRow['type'];
		$image = $skillRow['image'];
		$name = $skillRow['name'];

		if($type == 'Passive' || $type == 'Buff' || $type == 'Non-Combat'){
			return $type;
		}

		if($type == 'Aura'){

			$sql = "select * from playerbuffs where buffid = $skill and playerid = $acc";
			$query = sql_query($sql, $conn);
			if($query->rowCount() == 0){
				$sql = "INSERT INTO playerbuffs (buffid, playerid, remaining, image, name, script) select $skill, $acc, 0, '$image', '$name', script from skilllevels sl inner join charskills cs on cs.level = sl.level where sl.skillid = $skill and cs.skillid = $skill and playerid = $acc";
				sql_query($sql, $conn);
			}
		}

		$sql = "select * from charskills where playerid = $acc AND skillid = $skill";
		$query = sql_query($sql, $conn);

		if($query->rowCount() > 0){
			$equipped = json_decode(getEquippedSkills($conn, $acc));

			if(array_search($skill, $equipped) > -1){
				return '<span style="color:red;">Item already equipped!</span>';
			}

			$equipTo = array_search("-1", $equipped);

			if($equipTo > -1){
				sql_query('UPDATE equippedstuff SET skill_'.($equipTo + 1).'= '.$skill.' WHERE equipindex='.$acc, $conn);
				return '<span style="color:green;">Item added to battle ready list!</span>';
			}

			return '<span style="color:red;">You must remove something before equipping this!</span>';
		}

		return '<span style="color:red;">You do not have this item!</span>';
	}

	// -- Function Name : getAttribute
	// -- Params : $conn, $table, $attribute, $index
	// -- Purpose : Returns arg from row of table at index in args
	function getAttribute($conn, $table, $attribute, $index){
		if($table != 'calcValues'){
			$sql_q = "select \"".$attribute."\" from \"".$table."\" where \"".getKey($table)."\" =".$index." LIMIT 1";
			$sql = sql_query($sql_q, $conn);
			$row = mysqli_fetch_array($sql,MYSQLI_ASSOC);
			if(!$row || !is_array($row)){
				return null;
			}
			// PostgreSQL returns lowercase column names
			$attr_lower = strtolower($attribute);
			return $row[$attr_lower] ?? $row[$attribute] ?? null;
		}else{
			$sql_q = 'SELECT
				"character"."playerid" AS "playerid",
				(((((("character"."strength" + "equipmentbonus"."str") + "buffsbonus"."str") + FLOOR((("character"."strength" * "equipmentbonus"."strPerc") / 100))) * 4) + ((("character"."dexterity" + "equipmentbonus"."dex") + FLOOR((("character"."dexterity" * "equipmentbonus"."dexPerc") / 100))) + "buffsbonus"."dex")) + ("character"."level" / 2)) AS "damage",
				FLOOR(((((((10 + "equipmentbonus"."maxMP") + "buffsbonus"."maxMP") + ("character"."level" * 10)) + (((("character"."spirit" + "equipmentbonus"."spr") + FLOOR((("character"."spirit" * "equipmentbonus"."sprPerc") / 100))) + "buffsbonus"."spr") * 30)) + (((("character"."vitality" + "equipmentbonus"."vit") + FLOOR((("character"."vitality" * "equipmentbonus"."vitPerc") / 100))) + "buffsbonus"."vit") * 10)) * (1 + ("equipmentbonus"."manaPerc" / 100)))) AS "maxMana",
				FLOOR(((((((150 + "equipmentbonus"."maxHP") + "buffsbonus"."maxHP") + ("character"."level" * 20)) + (((("character"."strength" + "equipmentbonus"."str") + "buffsbonus"."str") + FLOOR((("character"."strength" * "equipmentbonus"."strPerc") / 100))) * 15)) + (((("character"."vitality" + "equipmentbonus"."vit") + FLOOR((("character"."vitality" * "equipmentbonus"."vitPerc") / 100))) + "buffsbonus"."vit") * 25)) * (1 + ("equipmentbonus"."healthPerc" / 100)))) AS "maxHealth",
				((("character"."spirit" + "equipmentbonus"."spr") + FLOOR((("character"."spirit" * "equipmentbonus"."sprPerc") / 100))) + "buffsbonus"."spr") AS "spr",
				((("character"."strength" + "equipmentbonus"."str") + "buffsbonus"."str") + FLOOR((("character"."strength" * "equipmentbonus"."strPerc") / 100))) AS "str",
				((("character"."dexterity" + "equipmentbonus"."dex") + FLOOR((("character"."dexterity" * "equipmentbonus"."dexPerc") / 100))) + "buffsbonus"."dex") AS "dex",
				((("character"."vitality" + "equipmentbonus"."vit") + FLOOR((("character"."vitality" * "equipmentbonus"."vitPerc") / 100))) + "buffsbonus"."vit") AS "vit",
				(("equipmentbonus"."baseDmgMin" + "equipmentbonus"."minDmg") + "buffsbonus"."minDmg") AS "minDmg",
				(("equipmentbonus"."baseDmgMax" + "equipmentbonus"."maxDmg") + "buffsbonus"."maxDmg") AS "maxDmg",
				(((((("character"."dexterity" + "equipmentbonus"."dex") + FLOOR((("character"."dexterity" * "equipmentbonus"."dexPerc") / 100))) + "buffsbonus"."dex") * 1.5) + "character"."level") + "buffsbonus"."evasion") AS "flee",
				((((((("character"."dexterity" + "equipmentbonus"."dex") + FLOOR((("character"."dexterity" * "equipmentbonus"."dexPerc") / 100))) + "buffsbonus"."dex") * 1.5) + (((("character"."strength" + "equipmentbonus"."str") + FLOOR((("character"."strength" * "equipmentbonus"."strPerc") / 100))) + "buffsbonus"."str") * 0.75)) + "character"."level") + 100) AS "hit",
				((1 / ((1 / (((("character"."spirit" + "equipmentbonus"."spr") + FLOOR((("character"."spirit" * "equipmentbonus"."sprPerc") / 100))) + "buffsbonus"."spr") / 150)) + 0.9)) + (("equipmentbonus"."critchance" + "buffsbonus"."critchance") / 100)) AS "critRate",
				(((1 / ((1 / (((("character"."strength" + "equipmentbonus"."str") + FLOOR((("character"."strength" * "equipmentbonus"."strPerc") / 100))) + "buffsbonus"."str") / 150)) + 0.9)) + 0.1) + (("equipmentbonus"."critDamage" + "buffsbonus"."critDamage") / 100)) AS "critMulti",
				((1 / ((1 / (((("character"."strength" + "equipmentbonus"."str") + FLOOR((("character"."strength" * "equipmentbonus"."strPerc") / 100))) + "buffsbonus"."str") / 750)) + 0.5)) + ("equipmentbonus"."blockChance" / 100)) AS "block",
				("equipmentbonus"."armor" + "buffsbonus"."armor") AS "armor",
				("equipmentbonus"."fireRes" + "buffsbonus"."fireRes") AS "fireRes",
				("equipmentbonus"."iceRes" + "buffsbonus"."iceRes") AS "iceRes",
				("equipmentbonus"."earthRes" + "buffsbonus"."earthRes") AS "earthRes",
				("equipmentbonus"."arcaneRes" + "buffsbonus"."arcaneRes") AS "arcaneRes",
				("equipmentbonus"."holyRes" + "buffsbonus"."holyRes") AS "holyRes",
				-- "equipmentinventory"."class" AS "weapon",
				"character"."exp" AS "exp",
				"character"."next" AS "next",
				"character"."level" AS "level",
				"equipmentbonus"."itemDrop" AS "itemDrop",
				"equipmentbonus"."silverDrop" AS "silverDrop",
				("equipmentbonus"."bonusPotHeal" + ((("character"."vitality" + "equipmentbonus"."vit") + FLOOR((("character"."vitality" * "equipmentbonus"."vitPerc") / 100))) + "buffsbonus"."vit")) AS "bonusPotHeal",
				("equipmentbonus"."bonusPotMana" + ((("character"."spirit" + "equipmentbonus"."spr") + FLOOR((("character"."spirit" * "equipmentbonus"."sprPerc") / 100))) + "buffsbonus"."spr")) AS "bonusPotMana",
				("equipmentbonus"."expDrop" + "buffsbonus"."expDrop") AS "expDrop",
				"equipmentbonus"."healthPerc" AS "healthPerc",
				"equipmentbonus"."manaPerc" AS "manaPerc",
				"equipmentbonus"."strPerc" AS "strPerc",
				"equipmentbonus"."dexPerc" AS "dexPerc",
				"equipmentbonus"."sprPerc" AS "sprPerc",
				"equipmentbonus"."vitPerc" AS "vitPerc",
				"equipmentbonus"."spellReduction" AS "spellReduction",
				COALESCE("buffsbonus"."weapElement", \'physical\') AS "weapElement",
				"equipmentbonus"."shapelessRes" AS "shapelessRes",
				"equipmentbonus"."shapelessExpDrop" AS "shapelessExpDrop",
				"equipmentbonus"."shapelessDmg" AS "shapelessDmg",
				(SELECT class FROM "equipmentinventory" WHERE "playerid" = '.$index.' AND "equipped" = 1 AND ("slot" = \'weapon\' OR "slot" = \'2hweapon\') LIMIT 1) AS "weapon"
			FROM
				"character","equipmentbonus","buffsbonus"
			WHERE
				(("equipmentbonus"."playerid" = '.$index.')
					AND ("buffsbonus"."playerid" = '.$index.')
					AND ("character"."playerid" = '.$index.'))';
			$sql = sql_query(strtolower($sql_q), $conn);
			$row = mysqli_fetch_array($sql,MYSQLI_ASSOC);
			error_log(print_r($row, true));
			error_log(print_r($attribute, true));
			return $row[$attribute];
		}
	}

	// -- Function Name : getRow
	// -- Params : $conn, $table, $index
	// -- Purpose : Returns full row of table at index in args
	function getRow($conn, $table, $index){
		if($table != "calcValues"){
			// Validate index is not empty/null
			if($index === null || $index === '' || $index === false){
				return null;
			}
			$sql_q = "select * from \"".$table."\" where \"".getKey($table)."\" =".$index." LIMIT 1";
			$sql = sql_query($sql_q, $conn);
			return mysqli_fetch_array($sql,MYSQLI_ASSOC);
		}else{
			$sql_q = 'SELECT
		        "character"."playerid" AS "playerid",
		        (((((("character"."strength" + "equipmentbonus"."str") + "buffsbonus"."str") + FLOOR((("character"."strength" * "equipmentbonus"."strPerc") / 100))) * 4) + ((("character"."dexterity" + "equipmentbonus"."dex") + FLOOR((("character"."dexterity" * "equipmentbonus"."dexPerc") / 100))) + "buffsbonus"."dex")) + ("character"."level" / 2)) AS "damage",
		        FLOOR(((((((10 + "equipmentbonus"."maxMP") + "buffsbonus"."maxMP") + ("character"."level" * 10)) + (((("character"."spirit" + "equipmentbonus"."spr") + FLOOR((("character"."spirit" * "equipmentbonus"."sprPerc") / 100))) + "buffsbonus"."spr") * 30)) + (((("character"."vitality" + "equipmentbonus"."vit") + FLOOR((("character"."vitality" * "equipmentbonus"."vitPerc") / 100))) + "buffsbonus"."vit") * 10)) * (1 + ("equipmentbonus"."manaPerc" / 100)))) AS "maxMana",
		        FLOOR(((((((150 + "equipmentbonus"."maxHP") + "buffsbonus"."maxHP") + ("character"."level" * 20)) + (((("character"."strength" + "equipmentbonus"."str") + "buffsbonus"."str") + FLOOR((("character"."strength" * "equipmentbonus"."strPerc") / 100))) * 15)) + (((("character"."vitality" + "equipmentbonus"."vit") + FLOOR((("character"."vitality" * "equipmentbonus"."vitPerc") / 100))) + "buffsbonus"."vit") * 25)) * (1 + ("equipmentbonus"."healthPerc" / 100)))) AS "maxHealth",
		        ((("character"."spirit" + "equipmentbonus"."spr") + FLOOR((("character"."spirit" * "equipmentbonus"."sprPerc") / 100))) + "buffsbonus"."spr") AS "spr",
		        ((("character"."strength" + "equipmentbonus"."str") + "buffsbonus"."str") + FLOOR((("character"."strength" * "equipmentbonus"."strPerc") / 100))) AS "str",
		        ((("character"."dexterity" + "equipmentbonus"."dex") + FLOOR((("character"."dexterity" * "equipmentbonus"."dexPerc") / 100))) + "buffsbonus"."dex") AS "dex",
		        ((("character"."vitality" + "equipmentbonus"."vit") + FLOOR((("character"."vitality" * "equipmentbonus"."vitPerc") / 100))) + "buffsbonus"."vit") AS "vit",
		        (("equipmentbonus"."baseDmgMin" + "equipmentbonus"."minDmg") + "buffsbonus"."minDmg") AS "minDmg",
		        (("equipmentbonus"."baseDmgMax" + "equipmentbonus"."maxDmg") + "buffsbonus"."maxDmg") AS "maxDmg",
		        (((((("character"."dexterity" + "equipmentbonus"."dex") + FLOOR((("character"."dexterity" * "equipmentbonus"."dexPerc") / 100))) + "buffsbonus"."dex") * 1.5) + "character"."level") + "buffsbonus"."evasion") AS "flee",
		        ((((((("character"."dexterity" + "equipmentbonus"."dex") + FLOOR((("character"."dexterity" * "equipmentbonus"."dexPerc") / 100))) + "buffsbonus"."dex") * 1.5) + (((("character"."strength" + "equipmentbonus"."str") + FLOOR((("character"."strength" * "equipmentbonus"."strPerc") / 100))) + "buffsbonus"."str") * 0.75)) + "character"."level") + 100) AS "hit",
		        ((1 / ((1 / (((("character"."spirit" + "equipmentbonus"."spr") + FLOOR((("character"."spirit" * "equipmentbonus"."sprPerc") / 100))) + "buffsbonus"."spr") / 150)) + 0.9)) + (("equipmentbonus"."critchance" + "buffsbonus"."critchance") / 100)) AS "critRate",
		        (((1 / ((1 / (((("character"."strength" + "equipmentbonus"."str") + FLOOR((("character"."strength" * "equipmentbonus"."strPerc") / 100))) + "buffsbonus"."str") / 150)) + 0.9)) + 0.1) + (("equipmentbonus"."critDamage" + "buffsbonus"."critDamage") / 100)) AS "critMulti",
		        ((1 / ((1 / (((("character"."strength" + "equipmentbonus"."str") + FLOOR((("character"."strength" * "equipmentbonus"."strPerc") / 100))) + "buffsbonus"."str") / 750)) + 0.5)) + ("equipmentbonus"."blockChance" / 100)) AS "block",
		        ("equipmentbonus"."armor" + "buffsbonus"."armor") AS "armor",
		        ("equipmentbonus"."fireRes" + "buffsbonus"."fireRes") AS "fireRes",
		        ("equipmentbonus"."iceRes" + "buffsbonus"."iceRes") AS "iceRes",
		        ("equipmentbonus"."earthRes" + "buffsbonus"."earthRes") AS "earthRes",
		        ("equipmentbonus"."arcaneRes" + "buffsbonus"."arcaneRes") AS "arcaneRes",
		        ("equipmentbonus"."holyRes" + "buffsbonus"."holyRes") AS "holyRes",
		        (SELECT class FROM "equipmentinventory" WHERE "playerid" = '.$index.' AND "equipped" = 1 AND ("slot" = \'weapon\' OR "slot" = \'2hweapon\') LIMIT 1) AS "weapon",
		        "character"."exp" AS "exp",
		        "character"."next" AS "next",
		        "character"."level" AS "level",
		        "equipmentbonus"."itemDrop" AS "itemDrop",
		        "equipmentbonus"."silverDrop" AS "silverDrop",
		        ("equipmentbonus"."bonusPotHeal" + ((("character"."vitality" + "equipmentbonus"."vit") + FLOOR((("character"."vitality" * "equipmentbonus"."vitPerc") / 100))) + "buffsbonus"."vit")) AS "bonusPotHeal",
		        ("equipmentbonus"."bonusPotMana" + ((("character"."spirit" + "equipmentbonus"."spr") + FLOOR((("character"."spirit" * "equipmentbonus"."sprPerc") / 100))) + "buffsbonus"."spr")) AS "bonusPotMana",
		        ("equipmentbonus"."expDrop" + "buffsbonus"."expDrop") AS "expDrop",
		        "equipmentbonus"."healthPerc" AS "healthPerc",
		        "equipmentbonus"."manaPerc" AS "manaPerc",
		        "equipmentbonus"."strPerc" AS "strPerc",
		        "equipmentbonus"."dexPerc" AS "dexPerc",
		        "equipmentbonus"."sprPerc" AS "sprPerc",
		        "equipmentbonus"."vitPerc" AS "vitPerc",
		        "equipmentbonus"."spellReduction" AS "spellReduction",
		        COALESCE("buffsbonus"."weapElement", \'physical\') AS "weapElement",
		        "equipmentbonus"."shapelessRes" AS "shapelessRes",
		        "equipmentbonus"."shapelessExpDrop" AS "shapelessExpDrop",
		        "equipmentbonus"."shapelessDmg" AS "shapelessDmg"
		    FROM
		        "character","equipmentbonus","buffsbonus"
		    WHERE
		        (("equipmentbonus"."playerid" = '.$index.')
		            AND ("buffsbonus"."playerid" = '.$index.')
		            AND ("character"."playerid" = '.$index.'))';
			$sql_q = strtolower($sql_q);
			$sql = sql_query($sql_q, $conn);
			
			$row = mysqli_fetch_array($sql,MYSQLI_ASSOC);
			error_log(print_r($row, true));
			error_log(print_r($attribute, true));
			return $row;
		}

	}

	// -- Function Name : getCalcStats
	// -- Params : $acc, $conn
	// -- Purpose : Returns information for Character panel
	function getCalcStats($acc, $conn, $calcValues = null){
		$user = getAttribute($conn, "account", "account", $acc);
		$row = getRow($conn, "character", $acc);
		if(!$row || !is_array($row)){
			error_log("[getCalcStats] Failed to get character row for account $acc");
			return json_encode(array());
		}
		$logged = $row['neverlogged'] ?? 0;
		if(isset($calcValues)){
			$calcStats = $calcValues;
		}else{
			$calcStats = getRow($conn, "calcValues", $acc);
		}
		if(!$calcStats || !is_array($calcStats)){
			error_log("[getCalcStats] Failed to get calcValues for account $acc");
			return json_encode(array());
		}
		$sql = "SELECT COALESCE(SUM(\"count\"), 0) AS count FROM charKills WHERE playerid = ".$acc;
		$sql_row = sql_query($sql, $conn);
		if($sql_row && $killRow = $sql_row->fetch()){
			$kills = $killRow['count'] ?? 0;
		} else {
			$kills = 0;
		}
		$level = $row['level'] ?? 1;
		$str = $row['strength'] ?? 1;
		$dex = $row['dexterity'] ?? 1;
		$spr = $row['spirit'] ?? 1;
		$vit = $row['vitality'] ?? 1;
		if($str != $calcStats['str']){
			$str .= "  (" . $calcStats['str'] . ")";
		}
		if($dex != $calcStats['dex']){
			$dex .= "  (" . $calcStats['dex'] . ")";
		}
		if($spr != $calcStats['spr']){
			$spr .= "  (" . $calcStats['spr'] . ")";
		}
		if($vit != $calcStats['vit']){
			$vit .= "  (" . $calcStats['vit'] . ")";
		}
		$steps = getAttribute($conn, "account", "stepstaken", $acc) ?? 0;
		$gold = $row['silver'] ?? 0;
		$class = $row['class'] ?? 'Paladin';
		$level = $row['level'] ?? 1;
		$promptDaily = $row['promptdaily'] ?? 0;
		$resetString = $row['resetscript'] ?? '';
		$freeStats = $row['statpoints'] ?? 0;
		$freeSkills = $row['skillpoints'] ?? 0;
		$armor = $calcStats['armor'] ?? 0;
		$deaths = $row['deaths'] ?? 0;
		$hp = $calcStats['maxhealth'] ?? 100;
		$mp = $calcStats['maxmana'] ?? 50;
		$atk = $calcStats["damage"] ?? 1;
		$hit = floor($calcStats['hit'] ?? 0);
		$dodge = floor($calcStats['flee'] ?? 0);
		$equippedShield = getAttribute($conn, "equipmentbonus", "blockChance", $acc);
		$block = $calcStats["block"];

		if($equippedShield != 0){
			$sql = "SELECT * FROM charskills s inner join skilllevels l on s.skillid = l.skillid where playerid = $acc and l.level = s.level and s.skillid = 9";
			$result = sql_query($sql, $conn);
			if($result && $row = $result->fetch()){
				$block = $block + ($row['damage'] ?? 0);
			}
		}

		$block = $block * 100;

		$crit = floor(($calcStats['critrate'] ?? 0) * 100);
		$critMod = floor(($calcStats['critmulti'] ?? 0) * 100);
		$fire = floor($calcStats['fireres'] ?? 0);
		$ice = floor($calcStats['iceres'] ?? 0);
		$earth = floor($calcStats['earthres'] ?? 0);
		$arcane = floor($calcStats['arcaneres'] ?? 0);
		$holy = floor($calcStats['holyRes']);
		$stats[] = $atk;
		// 0 = attack
		$stats[] = $hit;
		// 1 = hit
		$stats[] = $dodge;
		// 2 = dodge
		$stats[] = $steps;
		// 3 = steps Taken
		$stats[] = $gold;
		// 4 = currency
		$stats[] = $class;
		// 5 = class
		$stats[] = $str;
		// 6 = str
		$stats[] = $dex;
		// 7 = dex
		$stats[] = $spr;
		// 8 = spirit
		$stats[] = $level;
		// 9 = level
		$stats[] = $freeStats;
		// 10 = free points
		$stats[] = $armor;
		// 11 = armor
		$stats[] = $user;
		// 12 = username
		$stats[] = $deaths;
		// 13 = deaths
		$stats[] = $kills;
		// 14 = fights Won
		$stats[] = $hp;
		// 15 = HP
		$stats[] = $mp;
		// 16 = MP
		$stats[] = 0;
		// 17 = mana regen
		$stats[] = $vit;
		// 18 = vit
		$stats[] = $block;
		// 19 = Block Chance
		$stats[] = $crit;
		// 20 = Crit Chance
		$stats[] = $critMod;
		// 21 = Crit Modifier
		$stats[] = $fire;
		$stats[] = $ice;
		$stats[] = $earth;
		$stats[] = $arcane;
		$stats[] = $holy;
		$stats[] = $freeSkills;
		// 10 = free skill points
		$stats[] = 0; //$logged;
		$stats[] = $resetString;
		$stats[] = $promptDaily;
		if($logged == 1){
			$sql = "UPDATE \"character\" set \"neverLogged\" = 2 WHERE \"playerid\" = ".$acc;
		}


		if($logged == 2){
			$sql = "UPDATE \"character\" set \"neverLogged\" = 0 WHERE \"playerid\" = ".$acc;
		}

		sql_query($sql, $conn);
		$sql = "update \"character\" set promptDaily = 0 where playerid = $acc";
		sql_query($sql, $conn);
		return $stats;
	}

	// -- Function Name : getEquippedItems
	// -- Params : $conn, $acc
	// -- Purpose : Returns the items relating to provided account
	function getEquippedItems($conn, $acc){
		//  --------------------------------------------------------------------------------------------------
		//
		//  --------------------------------------------------------------------------------------------------
		$output = array();
		$row = getRow($conn, "equippedStuff", $acc);
		$counter = 0;
		while($counter != 4){
			$counter++;
			$output[] = $row['skill_'.$counter];
		}

		return json_encode($output);
	}

	// -- Function Name : getEquippedSkills
	// -- Params : $conn, $acc
	// -- Purpose : Returns the items relating to provided account
	function getEquippedSkills($conn, $acc){
		//  --------------------------------------------------------------------------------------------------
		//
		//  --------------------------------------------------------------------------------------------------
		$output = array();
		$row = getRow($conn, "equippedStuff", $acc);
		$counter = 0;
		while($counter != 4){
			$counter++;
			$output[] = $row['skill_'.$counter];
		}

		return json_encode($output);
	}

	// -- Function Name : getItemInfo
	// -- Params : $conn
	// -- Purpose : Returns information about all items
	function getItemInfo($conn){
		//  --------------------------------------------------------------------------------------------------
		//
		//  --------------------------------------------------------------------------------------------------
		$output = array();
		$sql_get_items = "SELECT item_id as itemid, name, image, usable, combat, quest, equipment, value, description, visible FROM item";
		// where item_id = 78";
		$sql_item_result = sql_query($sql_get_items, $conn);
		while($row = mysqli_fetch_array($sql_item_result,MYSQLI_ASSOC)){
			$type = "";

			if($row['quest'] == 1){
				$type = 'Quest<br/><br/><strong>Description: </strong>';
			} else
			if($row['equipment'] == 1){
				$type = 'Equipment<br/><br/><strong>Description: </strong>';
			} else
			if($row['usable'] == 0 && $row['combat'] == 1){
				$type = "Combat<br/><br/><strong>Effect: </strong>";
			} else
			if($row['usable'] == 1 && $row['combat'] == 1){
				$type = "Non-combat/Combat<br/><br/><strong>Effect: </strong>";
			} else
			if($row['usable'] == 1 && $row['combat'] == 0){
				$type = "Non-combat<br/><br/><strong>Effect: </strong>";
			} else {
				$type = "Misc.<br/><br/><strong>Description: </strong>";
			}


			if($row['quest'] == 1 && $row['visible'] == 0){
				$quest = 11;
			} else
			if ($row['quest'] == 1 && $row['visible'] == 1){
				$quest = 1;
			} else {
				$quest = 0;
			}

			$desc = "<strong>Item Name: </strong>".$row['name']."<br/><br/><strong>Item Type: </strong>".str_replace('&gt;', '>', str_replace('&lt;', '<', $type.htmlspecialchars($row['description'])));
			$string = $row['itemid'].'|'.$row['name'].'|'.$row['image'].'|'.$row['usable'].'|'.$row['combat'].'|'.$quest.'|'.$row['equipment'].'|'.$row['value'].'|'.$desc;
			$output[$row['itemid'] - 1] = $string;
		}

		return $output;
	}

	// -- Function Name : getItemInfo
	// -- Params : $conn
	// -- Purpose : Returns information about all items
	function getSingleItemCard($item, $conn){
		//  --------------------------------------------------------------------------------------------------
		//
		//  --------------------------------------------------------------------------------------------------
		$sql_get_items = "SELECT * FROM \"item\" where item_ID = $item";
		$sql_item_result = sql_query($sql_get_items, $conn);
		while($row = mysqli_fetch_array($sql_item_result,MYSQLI_ASSOC)){
			$type = "";

			if($row['quest'] == 1){
				$type = 'Quest<br/><br/><strong>Description: </strong>';
			} else
			if($row['equipment'] == 1){
				$type = 'Equipment<br/><br/><strong>Description: </strong>';
			} else
			if($row['usable'] == 0 && $row['combat'] == 1){
				$type = "Combat<br/><br/><strong>Effect: </strong>";
			} else
			if($row['usable'] == 1 && $row['combat'] == 1){
				$type = "Non-combat/Combat<br/><br/><strong>Effect: </strong>";
			} else
			if($row['usable'] == 1 && $row['combat'] == 0){
				$type = "Non-combat<br/><br/><strong>Effect: </strong>";
			} else {
				$type = "Misc.<br/><br/><strong>Description: </strong>";
			}


			if($row['quest'] == 1 && $row['visible'] == 0){
				$quest = 11;
			} else
			if ($row['quest'] == 1 && $row['visible'] == 1){
				$quest = 1;
			} else {
				$quest = 0;
			}

			$desc = "<strong>Item Name: </strong>".$row['name']."<br/><br/><strong>Item Type: </strong>".str_replace('&gt;', '>', str_replace('&lt;', '<', $type.htmlspecialchars($row['description'])));
		}

		return $desc;
	}

	// -- Function Name : getSkillInfo
	// -- Params : $conn
	// -- Purpose : Returns information about all skills  -- key: combat:1 non:2 combat/non:3 buff:4 passive:5
	function getSkillInfo($conn){
		$output = array();
		$sql_get_skills = "SELECT \"id\" as skillindex, \"name\", \"image\", \"prereq\", \"requiredlevel\", \"maxlevel\", \"type\", \"prereq2\", \"prereq3\" FROM \"skills\"";
		$sql_skills_result = sql_query($sql_get_skills, $conn);
		if(!$sql_skills_result){
			error_log("[getSkillInfo] Query failed");
			return array();
		}
		while($row = mysqli_fetch_array($sql_skills_result,MYSQLI_ASSOC)){
			$type = '';
			switch ($row['type']){
				case "Combat":
					$type = 1;
					break;
				case "Non-Combat":
					$type = 2;
					break;
				case "Combat/Non-Combat":
					$type = 3;
					break;
				case "Buff":
					$type = 4;
					break;
				case "Passive":
					$type = 5;
					break;
				case "Aura":
					$type = 6;
					break;
			}
			$string = ($row['skillindex'] ?? 0).'|'.($row['name'] ?? '').'|'.($row['image'] ?? '').'|'.($row['prereq'] ?? 0).'|'.($row['requiredlevel'] ?? 0).'|'.($row['maxlevel'] ?? 0).'|'.$type.'|'.($row['prereq2'] ?? 0).'|'.($row['prereq3'] ?? 0);
			$output[] = $string;
		}

		return $output;
	}

	// -- Function Name : getSkillLevels
	// -- Params : $conn
	// -- Purpose : Gets all levels of all skills
	function getSkillLevels($conn){
		$output = array();
		$sql_get_skills = "SELECT l.*, s.\"name\", s.\"type\", s.\"id\" as skillindex FROM \"skilllevels\" l inner join \"skills\" s on s.\"id\" = l.\"skillid\"";
		$sql_skills_result = sql_query($sql_get_skills, $conn);
		if(!$sql_skills_result){
			error_log("[getSkillLevels] Query failed");
			return array();
		}
		while($row = mysqli_fetch_array($sql_skills_result,MYSQLI_ASSOC)){

			if(($row['cost'] ?? 0) == 0){
				$cost = "N/A";
			} else {
				$cost = $row['cost'];
			}

			$name = $row['name'] ?? '';
			$type = $row['type'] ?? '';
			$level = $row['level'] ?? 1;
			$skillid = $row['skillid'] ?? 0;
			$script = $row['script'] ?? '';
			$skillindex = $row['skillindex'] ?? 0;
			$element = $row['element'] ?? '';
			
			$description = "<strong>Skill Name: </strong>".$name."<br/><br/><strong>Skill Type: </strong>".$type."<br/><br/><strong>Skill Level: </strong>".$level."<br/><strong>Base Mana Cost: </strong><span id='manaCost-".$skillid."'>$cost</span><br/><br/><strong>Skill Description: </strong>".$script;
			$string = $skillindex.'|skill-'.$skillid.'|'.$element.'|'.$cost.'|'.$description.'|level-'.$level.'|'.$skillid;
			$output[] = $string;
		}

		return $output;
	}

	// -- Function Name : getCharSkillLevels
	// -- Params : $conn, $acc
	// -- Purpose : Returns current skill level of all skills
	function getCharSkillLevels($conn, $acc){
		$output = array();
		$sql_get_skills = "SELECT * FROM \"charSkills\" where \"playerid\" = ".$acc;
		$sql_skills_result = sql_query($sql_get_skills, $conn);
		while($row = mysqli_fetch_array($sql_skills_result,MYSQLI_ASSOC)){
			$string = $row['skillID'].'|'.$row['level'];
			$output[] = $string;
		}

		return $output;
	}

	// -- Function Name : allocateSkills
	// -- Params : $conn, $acc, $skills
	// -- Purpose : Allocates all skill points passed in params
	function allocateSkills($conn, $acc, $skills){
		$count = 0;
		$skillArray = explode("-", $skills);
		unset($skillArray[count($skillArray)-1]);
		while(count($skillArray) != 0 && $count != 10){
			foreach ($skillArray as &$value) {

				if(strlen($value) > 0){

					if(tryToAllocateSkill($conn, $acc, explode("|", $value)[0],explode("|", $value)[1])){
						$key = array_search($value, $skillArray);
						unset($skillArray[$key]);
					}

				}

			}

			$count++;
		}

	}

	// -- Function Name : tryToAllocateSkill
	// -- Params : $conn, $acc, $skillID, $points
	// -- Purpose : attempts to allocate skills, checking prereq
	function tryToAllocateSkill($conn, $acc, $skillID, $points){
		$prereq = getAttribute($conn, 'skills', 'prereq', $skillID);

		if($prereq != 0){
			$sql = "select * from charSkills where playerid = ".$acc." AND skillID = ".$prereq;
			$query = sql_query($sql, $conn);

			if(mysqli_num_rows($query) == 0){
				return false;
			}

		}

	$existingSQL = "SELECT * FROM charSkills c inner join skills s on s.id = c.skillID where c.playerid = ".$acc." AND c.skillID = ".$skillID;
	$query = sql_query($existingSQL, $conn);

	if(mysqli_num_rows($query) > 0){
		$row = mysqli_fetch_array($query,MYSQLI_ASSOC);

		if(getAttribute($conn, 'character', 'level', $acc) >= $row['requiredlevel']){
			$maxLevel = $row['maxlevel'];
			$currentLevel = $row['level'];

			if($currentLevel + $points == $maxLevel){
				setAchievement($conn, $acc, 16);
			}


			if($currentLevel + $points <= $maxLevel && $points <= getAttribute($conn, 'character', 'skillPoints', $acc)){
				$sql = "UPDATE \"character\" set \"skillPoints\" = \"skillPoints\" - ".$points." where \"playerid\" = ".$acc;
				sql_query($sql, $conn);
				$sql = "UPDATE \"charSkills\" set \"level\" = \"level\" + ".$points." where \"playerid\" = ".$acc." AND \"skillID\" = ".$skillID;
				sql_query($sql, $conn);
				$level = $currentLevel + $points;
				applyPassives($conn, $acc, $skillID, $level);
				return true;
			} else {
				return false;
			}

		} else {
			return false;
		}

	} else {
		$sql = "select * from skills where \"id\" = ".$skillID;
		$query = sql_query($sql, $conn);
		$row = mysqli_fetch_array($query,MYSQLI_ASSOC);
		$maxLevel = $row['maxlevel'];

		if(getAttribute($conn, 'character', 'level', $acc) >= $row['requiredlevel']){

			if($points <= $maxLevel && $points <= getAttribute($conn, 'character', 'skillPoints', $acc)){
				$sql = "UPDATE \"character\" set \"skillPoints\" = \"skillPoints\" - ".$points." where \"playerid\" = ".$acc;
				sql_query($sql, $conn);
				$sql = "INSERT INTO \"charskills\" (\"level\", \"playerid\", \"skillid\") values (".$points.",".$acc.", ".$skillID.")";
				sql_query($sql, $conn);
				$level = $points;
				applyPassives($conn, $acc, $skillID, $level);
				return true;
			} else {
				return false;
			}

		} else {
			return false;
		}

	}

}

	// -- Function Name : applyPassives
	// -- Params : $conn, $acc, $skillID, $level
	// -- Purpose : applies all passives obtained
	function applyPassives($conn, $acc, $skillID, $level){
		$passiveSkills = array(6, 9, 12, 13, 40, 41);

		if(in_array($skillID, $passiveSkills)){
			$sql = "SELECT damage FROM skillLevels WHERE skillID = $skillID AND level = $level";
			$query = sql_query($sql, $conn);
			$row = mysqli_fetch_array($query,MYSQLI_ASSOC);
			$effect = $row["damage"];
			$sql = "SELECT * FROM playerbuffs where passiveID = $skillID and playerid = $acc";
			$query = sql_query($sql, $conn);

			if(mysqli_num_rows($query) == 0){
				//didn't exist before

				if($skillID == 6){
					$sql = "INSERT INTO playerbuffs (passiveid, playerid, remaining, maxhp) values ($skillID, $acc, 0, $effect)";
					sql_query($sql, $conn);
				}
				if($skillID == 40){
					$sql = "INSERT INTO playerbuffs (passiveid, playerid, remaining, evasion) values ($skillID, $acc, 0, $effect)";
					sql_query($sql, $conn);
				}
				if($skillID == 41){
					$sql = "INSERT INTO playerbuffs (passiveid, playerid, remaining, critchance, critdamage) values ($skillID, $acc, 0, $effect, $effect * 2)";
					sql_query($sql, $conn);
				}

			} else {

				if($skillID == 6){
					$sql = "UPDATE playerbuffs set maxHP = $effect where playerid = $acc AND passiveID = $skillID";
					sql_query($sql, $conn);
				}
				if($skillID == 40){
					$sql = "UPDATE playerbuffs set evasion = $effect where playerid = $acc AND passiveID = $skillID";
					sql_query($sql, $conn);
				}
				if($skillID == 41){
					$sql = "UPDATE playerbuffs set critchance = $effect, critDamage = $effect * 2 where playerid = $acc AND passiveID = $skillID";
					sql_query($sql, $conn);
				}

			}

		}

	}

	// -- Function Name : getKey
	// -- Params : $table
	// -- Purpose : Returns table index name of table provided
	function getKey($table){
		switch ($table){
			case "account":
				$table = "playerid";
				break;
			case "character":
				$table = "playerid";
				break;
			case "calcvalues":
				$table = "playerid";
				break;
			case "chat":
				$table = "c_index";
				break;
			case "combat":
				$table = "combatid";
				break;
			case "combatenemies":
				$table = "combatenemyid";
				break;
			case "drops":
				$table = "dropid";
				break;
			case "enemies":
				$table = "enemyid";
				break;
			case "equippedstuff":
				$table = "equipindex";
				break;
			case "item":
				$table = "item_id";
				break;
			case "news":
				$table = "newsindex";
				break;
			case "shops":
				$table = "shop_index";
				break;
			case "quests":
				$table = "questid";
				break;
			case "skills":
				$table = "index";
				break;
			case "equipmentbonus":
				$table = "playerid";
				break;
			case "spawnpoints":
				$table = "spawnid";
				break;
			default:
				$table = "index";
				break;
	}

	return $table;
	}

	// -- Function Name : getNews
	// -- Params : $conn
	// -- Purpose : Returns an object holding all news posts
	function getNews($conn){
		$output = array(); // Initialize output array
		$sqlGetNews = 'SELECT * FROM news ORDER BY newsIndex DESC';
		$sqlNewsResult = sql_query($sqlGetNews, $conn);
		if ($sqlNewsResult) {
			while(($row = mysqli_fetch_array($sqlNewsResult,MYSQLI_ASSOC))){
				$output[] = $row['imageoffset'];
				$output[] = $row['image'];
				$output[] = $row['title'];
				$output[] = $row['date'];
				$output[] = $row['update'];
			}
		}
		return $output;
	}

	// -- Function Name : getShopInfo
	// -- Params : $conn, $shop
	// -- Purpose : Returns shop info based on a shop name
	function getShopInfo($conn, $shop){
		$sql_shop_result = sql_query("SELECT * FROM \"shops\" WHERE \"shop_index\" = $shop", $conn);
		while($row = mysqli_fetch_array($sql_shop_result,MYSQLI_ASSOC)){
			$sql = "select item_id as itemid, name, image, usable, combat, quest, equipment, value, description, visible from item where item_id in (".
				$row['item_1'].','.$row['item_2'].','.$row['item_3'].','.$row['item_4'].','.$row['item_5'].','.$row['item_6'].','.$row['item_7'].','.$row['item_8'].")";
				$sql_item_result = sql_query($sql, $conn);
				$counter = 0;
				while($item = mysqli_fetch_array($sql_item_result,MYSQLI_ASSOC)){
					$output["items"][] = $item;
				}


			$output['shopName'] = $row['shopName'];
			$output['welcome'] = $row['welcome'];
			}

		return json_encode($output);
	}

	// -- Function Name : getEquipmentShopInfo
	// -- Params : $conn, $shop
	// -- Purpose : Returns shop info based on a shop name
	function getEquipmentShopInfo($conn, $shop){
		//  --------------------------------------------------------------------------------------------------
		//
		//  --------------------------------------------------------------------------------------------------
		$output = array();
		$sql_shop_result = sql_query("SELECT * FROM \"equipmentShops\" WHERE shop_index = $shop", $conn);
		while($row = mysqli_fetch_array($sql_shop_result,MYSQLI_ASSOC)){
			$output[] = getSingleEquipment($conn, $row['item_1']);
			$output[] = getSingleEquipment($conn, $row['item_2']);
			$output[] = getSingleEquipment($conn, $row['item_3']);
			$output[] = getSingleEquipment($conn, $row['item_4']);
			$output[] = getSingleEquipment($conn, $row['item_5']);
			$output[] = getSingleEquipment($conn, $row['item_6']);
			$output[] = getSingleEquipment($conn, $row['item_7']);
			$output[] = getSingleEquipment($conn, $row['item_8']);
			$output[] = getSingleEquipment($conn, $row['item_9']);
			$output[] = getSingleEquipment($conn, $row['item_10']);
			$output[] = $row['shopName'];
			$output[] = $row['welcome'];
		}

		return json_encode($output);
	}

	// -- Function Name : getStatus
	// -- Params : $acc, $conn
	// -- Purpose : Returns an array containing all display information needed for char
	function getStatus($acc, $conn, $calcValues = null){
		$charRow = getRow($conn, "character", $acc);
		if (!$charRow || !is_array($charRow)){
			error_log("[getStatus] Failed to get character row for account $acc");
			return json_encode(array());
		}
		$accRow = getRow($conn, "account", $acc);
		if(isset($calcValues)){
			$calcRow = $calcValues;
		}else{
			$calcRow = getRow($conn, "calcValues", $acc);  //BOTTLENECK
		}
		if(!$calcRow || !is_array($calcRow)){
			error_log("[getStatus] Failed to get calcValues for account $acc");
			return json_encode(array());
		}

		error_log(print_r($charRow, true));
		error_log(print_r($accRow, true));
		error_log(print_r($calcRow, true));

		$level = $charRow['level'] ?? 1;
		$class = $charRow['class'] ?? 'Paladin';
		$user = $accRow['account'] ?? 'Unknown';
		$output[] = "Level ".$level." ".$class;
		$currentMana = $charRow['mana'] ?? 0;
		$maxMana = $calcRow['maxmana'] ?? 50;
		$output[] = number_format($currentMana)."/".number_format($maxMana);
		$currentHP = $charRow['hitpoints'] ?? 0;
		$maxHP = $calcRow['maxhealth'] ?? 100;
		$output[] = number_format($currentHP)."/".number_format($maxHP);
		$missingMana = $maxMana - $currentMana;
		$offset = (($maxMana != 0) ? ($missingMana / $maxMana) : 0) * 234 + 24;

		if($currentMana == 0){
			$output[] = "-265px";
		} else {
			$output[] = "-".$offset."px";
		}

		$missingHP = $maxHP - $currentHP;
		$offset = (($maxHP != 0) ? ($missingHP / $maxHP) : 0) * 234 + 24;
		if($currentHP == 0){
			$output[] = "270px";
		} else {
			$output[] = $offset."px";
		}

		$currentEXP = $charRow['exp'];
		$maxEXP = $charRow['next'];
		$missingEXP = $maxEXP - $currentEXP;
		$offset = (($maxEXP != 0) ? ($missingEXP / $maxEXP) : 0) * 797 + 14;

		if($currentEXP == 0){
			$output[] = "-815px";
		} else {
			$output[] = "-".$offset."px";
		}

		$output[] = "Silver: ".$charRow["silver"];
		$output[] = $currentEXP.' / '.$maxEXP;
		$sql = "select * from playerbuffs where name != 'empty' and playerid in ($acc, -1) order by itemid";
		$rowset = sql_query($sql, $conn);
		$statsRow = '';

		if(mysqli_num_rows($rowset) != 0){
			while($row = mysqli_fetch_array($rowset,MYSQLI_ASSOC)){
				$statsRow .= $row['script']."|".$row['image']."|".$row['remaining']."*";
			}

		}
		$output[] = $statsRow;
		return json_encode($output);
	}

	// -- Function Name : locationPing
	// -- Params : $conn, $acc, $x, $y, $async, $map
	// -- Purpose : Preforms location updates, as well as a combat check
	function locationPing($conn, $acc, $x, $y, $async, $map){
			$debug = array();
			$debug[] = "locationPing start: acc=$acc x=$x y=$y async=$async map=$map";

			if(getRow($conn, "combat", $acc) == 0){
			$x = floor($x);
			$y = floor($y);

				$debug[] = "floored: x=$x y=$y";

				if($x > 0 && $y > 0){

				if ($async == 'no'){
					if(getAttribute($conn, "character", "map", $acc) == "endless.php?"){
						if($map == "endless.php?"){
							$level = getAttribute($conn, "character", "currentTowerLevel", $acc);
							$level++;
							$_SESSION['towerLevel'] = $level;
							$sql = "UPDATE \"character\" SET towerLevel = towerLevel + 1 WHERE playerid = $acc and towerLevel < $level LIMIT 1";
							sql_query($sql, $conn);
							$sql = "UPDATE \"character\" SET currentTowerLevel = $level WHERE playerid = $acc LIMIT 1";
							sql_query($sql, $conn);
						}else{
							$sql = "UPDATE \"character\" SET currentTowerLevel = 1 WHERE playerid = $acc LIMIT 1";
							sql_query($sql, $conn);
						}
					}else{
						if($map == "endless.php?"){
							$sql = "UPDATE \"character\" SET currentTowerLevel = 1 WHERE playerid = $acc LIMIT 1";
							sql_query($sql, $conn);
						}
					}
					$sql = "UPDATE \"character\" SET locationX = ".$x.", locationY = ".$y.", map = '".$map."', \"combatmodifier\" = 0 WHERE playerid = $acc LIMIT 1";
				} else {
					$oldX = isset($_SESSION['oldX']) ? floatval($_SESSION['oldX']) : null;
					$oldY = isset($_SESSION['oldY']) ? floatval($_SESSION['oldY']) : null;

					if ($oldX === null || $oldY === null) {
						// Session missing previous coords — fallback to DB-stored character location
						$charRow = getRow($conn, "character", $acc);
						$oldX = isset($charRow['locationX']) ? floatval($charRow['locationX']) : null;
						$oldY = isset($charRow['locationY']) ? floatval($charRow['locationY']) : null;
						$debug[] = "fallback_old_from_db: oldX=" . var_export($oldX, true) . " oldY=" . var_export($oldY, true);
					}

					if ($oldX !== null && $oldY !== null) {
						$moveX = abs($x - $oldX);
						$moveY = abs($y - $oldY);
					} else {
						$moveX = 0;
						$moveY = 0;
					}

					$steps = sqrt(($moveX * $moveX) + ($moveY * $moveY));
					$debug[] = "session_keys=" . var_export(array_keys($_SESSION), true);
					$debug[] = "oldX=" . var_export($oldX, true) . " oldY=" . var_export($oldY, true);
					$debug[] = "moveX=" . $moveX . " moveY=" . $moveY;
					$debug[] = "raw_steps=" . $steps . " floored_steps=" . floor($steps);
					$_SESSION['oldX'] = $x;
					$_SESSION['oldY'] = $y;
					$sql = "UPDATE \"account\" set \"stepstaken\" = \"stepstaken\" + ".floor($steps)." where \"playerid\" = $acc";
					sql_query($sql, $conn);
					$sql = "UPDATE \"character\" SET locationX = ".$x.", locationY = ".$y.", \"combatmodifier\" = \"combatmodifier\" + 1 WHERE map = '".$map."' AND playerid = ".$acc;
				}

				sql_query($sql, $conn);
				$debug[] = "executed SQL: " . $sql;
				$affected = mysqli_affected_rows($conn);
				$debug[] = "mysqli_affected_rows=$affected";

				if (isset($steps)){
					$debug[] = "steps=".floor($steps);
				}

				if ($affected == 1 && $async != 'no'){
					return chooseEnemy($acc, $steps, $conn);
				}

				kongSubmitInitStats($conn, $acc);
			}


		}

		// No combat triggered - return debug info
		// return json_encode(array('debug' => $debug, 'enemy' => null));

	}

	// -- Function Name : sellItem
	// -- Params : $item, $quan, $acc, $conn
	// -- Purpose : Verifies, and sells a single item provided in args
	function sellItem($item, $quan, $acc, $conn){

		if(getInventoryItem($conn, $acc, $item) > ($quan - 1)){
			$total = $quan * floor(getAttribute($conn, "item", "value", $item)/3);
			removeItemAmount($conn, $acc, $item, $quan, false);
			sql_query("UPDATE \"character\" set \"silver\"=\"silver\" + ".$total." where \"playerid\"=".$acc, $conn);
			return '<span style="color:green;">Transaction Successful</span>';
		}

	}

	// -- Function Name : sellItems
	// -- Params : $conn, $itemList, $acc
	// -- Purpose : Processes a sell transaction, taking a list of items as args, returns success or fail
	function sellItems($conn, $itemList, $acc){
		$items = explode("-", $itemList);
		$counter = 0;
		while ($counter != (count($items) - 1)){
			$item = explode("|", $items[$counter]);
			$message = sellItem($item[0], $item[1], $acc, $conn);
			$counter++;
		}

		return $message;
	}

	// -- Function Name : toSpawn
	// -- Params : $acc, $conn
	// -- Purpose : Returns character to spawn point
	function toSpawn($acc, $conn){
		$row = getRow($conn, "spawnpoints", getAttribute($conn, "character", "respawn", $acc));
		//if($row["mapName"] == "endless.php?"){
			$sql = "UPDATE \"character\" set currentTowerLevel = 1, \"map\" = '".$row["mapName"]."', \"locationX\" = ".$row["telestoneX"].", \"locationY\" = ".$row["telestoneY"]." WHERE \"playerid\" = ".$acc;
		//}else{
			//$sql = "UPDATE \"character\" set currentTowerLevel = 0, \"map\" = '".$row["mapName"]."', \"locationX\" = ".$row["telestoneX"].", \"locationY\" = ".$row["telestoneY"]." WHERE \"playerid\" = ".$acc;
		//}
		sql_query($sql, $conn);
	}

	// -- Function Name : unequipItem
	// -- Params : $acc, $slot, $conn
	// -- Purpose : Removed item from slot provided on account provided in args
	function unequipItem($acc, $slot, $conn){
		sql_query('UPDATE equippedStuff SET "item_'.$slot.'"= -1 WHERE equipIndex='.$acc, $conn);
		return '<span style="color:green;">Item removed from battle ready list!</span>';
	}

	// -- Function Name : unequipSkill
	// -- Params : $acc, $slot, $conn
	// -- Purpose : Removed skill from slot provided on account provided in args
	function unequipSkill($acc, $slot, $conn){
		$sql = "select skill_".$slot." as skill from equippedStuff WHERE equipIndex=$acc";
		$result = sql_query($sql, $conn);
		$row = mysqli_fetch_array($result,MYSQLI_ASSOC);
		$skill = $row["skill"];
		$skillRow = getRow($conn, "skills", $skill);
		$type = $skillRow['type'];
		if($type == 'Aura'){
			$sql = "delete from playerbuffs where buffID = $skill and playerid = $acc";
			$query = sql_query($sql, $conn);
		}
		sql_query('UPDATE equippedStuff SET "skill_'.$slot.'"= -1 WHERE equipIndex='.$acc, $conn);
		return '<span style="color:green;">Skill removed from battle ready list!</span>';
	}



	// -- Function Name : getString
	// -- Params : $conn, $item, $c1, $c2, $c3
	// -- Purpose : Returns a custom string based on passed conditions
	function getString($conn, $item, $c1, $c2, $c3){
		$sql = 'SELECT * FROM "strings" WHERE (forKey = '.$item.') AND c1 = \''.$c1.'\' AND c2 = \''.$c2.'\' AND c3 = \''.$c3.'\' ORDER BY RANDOM() LIMIT 1;';
		$res = sql_query($sql, $conn);
		$row = mysqli_fetch_array($res, MYSQLI_ASSOC);
		return isset($row['text']) ? $row['text'] : "";
	}

	// -- Function Name : fullheal
	// -- Params : $acc, $conn
	// -- Purpose : Regenerates a player's health fully
	function fullheal ($acc, $conn){
		$maxHealth = getAttribute($conn, "calcValues", "maxHealth", $acc);
		$sql_char_update = "UPDATE \"character\" set hitpoints = ".$maxHealth." where playerid=".$acc;
		sql_query($sql_char_update, $conn);
		return $sql_char_update;
	}

	// -- Function Name : heal
	// -- Params : $amount, $acc, $conn
	// -- Purpose : Regenerates a player's health a spastic amount
	function heal ($amount, $acc, $conn){
		$HP = getAttribute($conn, 'character', 'hitpoints', $acc);
		$max = getAttribute($conn, "calcValues", "maxHealth", $acc);
		$HP = $HP + $amount;

		if ($HP > $max){
			$HP = $max;
		}

		$sql_char_update = "UPDATE \"character\" set hitpoints=".$HP." where playerid=".$acc;
		$sql_char_updated = sql_query($sql_char_update, $conn);
	}

	// -- Function Name : fullmana
	// -- Params : $acc, $conn
	// -- Purpose : Regenerates a player's mana fully
	function fullmana ($acc, $conn){
		$maxMana = getAttribute($conn, "calcValues", "maxMana", $acc);
		$sql_char_update = "UPDATE \"character\" set mana = ".$maxMana." where playerid=".$acc;
		sql_query($sql_char_update, $conn);
	}

	// -- Function Name : mana
	// -- Params : $amount, $acc, $conn
	// -- Purpose : Regenerates a player's mana a spastic amount
	function mana ($amount, $acc, $conn){
		$MP = getAttribute($conn, 'character', 'mana', $acc);
		$max = getAttribute($conn, "calcValues", "maxMana", $acc);
		$MP = $MP + $amount;

		if ($MP > $max){
			$MP = $max;
		}

		$sql_char_update = "UPDATE \"character\" set mana=".$MP." where playerid=".$acc;
		$sql_char_updated = sql_query($sql_char_update, $conn);
	}

	// -- Function Name : getInventoryItem
	// -- Params : $conn, $acc, $item
	// -- Purpose : Returns the current count of an item on an an account
	function getInventoryItem($conn, $acc, $item){
		$sql = "SELECT * FROM \"inventory\" WHERE \"playerid\" = ".$acc." AND \"itemid\" = ".$item;
		$itemRow = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($itemRow['count'] == ''){
			$itemRow['count'] = 0;
		}

		return $itemRow['count'];
	}

	// -- Function Name : getStoredItem
	// -- Params : $conn, $acc, $item
	// -- Purpose : Returns the current count of an item on an an account
	function getStoredItem($conn, $acc, $item){
		$sql = "SELECT * FROM \"inventory\" WHERE \"playerid\" = ".$acc." AND \"itemid\" = ".$item;
		$itemRow = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($itemRow['stored'] == ''){
			$itemRow['stored'] = 0;
		}

		return $itemRow['stored'];
	}

	// -- Function Name : removeItemAmount
	// -- Params : $conn, $acc, $item, $amnt, $use
	// -- Purpose : Removes an amount of an item to user's backpack
	function removeItemAmount($conn, $acc, $item, $amnt, $use){

		if($use == true){
			$sql = "UPDATE \"inventory\" set \"count\" = \"count\" - ".$amnt.", \"used\" = \"used\" + ".$amnt." WHERE \"playerid\" = ".$acc." AND \"itemid\" = ".$item;
		} else {
			$sql = "UPDATE \"inventory\" set \"count\" = \"count\" - ".$amnt." WHERE \"playerid\" = ".$acc." AND \"itemid\" = ".$item;
		}

		sql_query($sql, $conn);
	}

	// -- Function Name : generateItemArray
	// -- Params : $conn, $acc
	// -- Purpose : Generates the item array for client for account in args
	function generateItemArray($conn, $acc){
		$sql = "SELECT COUNT(*) FROM \"item\"";
		$itemRow = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);
		$itemCount = $itemRow['COUNT(*)'];
		$counter = 0;
		while($counter != $itemCount){
			$counter++;
			$item = getInventoryItem($conn, $acc, $counter);
			$stored = getStoredItem($conn, $acc, $counter);

			if ($item == ''){
				$item = 0;
			}

			$output[] = $counter."|".$item."|".$stored;
		}

		return json_encode($output);
	}

	// -- Function Name : addItemAmount
	// -- Params : $conn, $acc, $item, $amnt
	// -- Purpose : Adds an amount of an item to user's backpack
	function addItemAmount($conn, $acc, $item, $amnt){
		if($item == 0){
			return;
		}
		$sql = "UPDATE \"inventory\" set \"count\" = \"count\" + ".$amnt." WHERE \"playerid\" = ".$acc." AND \"itemid\" = ".$item;
		sql_query($sql, $conn);

		if(mysqli_affected_rows($conn) == 0){
			$sql = "INSERT INTO \"inventory\" (\"playerid\", \"itemid\", \"count\") VALUES (".$acc.",".$item.",".$amnt.")";
			sql_query($sql, $conn);
		}

	}

	// -- Function Name : getQuest
	// -- Params : $conn, $acc, $npc
	// -- Purpose : Grabs current quest from NPC
	function getQuest($conn, $acc, $npc){

		$sql = "SELECT * FROM \"quests\" WHERE  \"npcid\" = $npc AND \"startdate\" < NOW() AND \"enddate\" > NOW()";
		$sql_rows = sql_query($sql, $conn);
		$counter = 1;
		$quests = false;
		while($questList = mysqli_fetch_array($sql_rows,MYSQLI_ASSOC)){
			$quests = true;
			//Cycles through each row tel one incomplete
			$quest = $questList['questID'];
			$sql = "SELECT * FROM \"questPlayerStatus\" WHERE \"playerid\" = ".$acc." AND \"questID\" = ".$quest;
			//Grabs the quest status from table
			$Row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);
			$questTitle = getAttribute($conn, "quests", "name", $quest);

			if($Row['status'] == "" || $Row['status'] == "working"){
				$output["questID"] = $quest;
				$output["status"] = $Row['status'];
				//If quest does not exist/in progress..  generate start reply
				$output["startText"] = "<div class='questTitle'>$questTitle</div>".getString($conn, $quest, "quest", "start", "");
				$output["finishText"] = "<div class='questTitle'>$questTitle</div>".getString($conn, $quest, "quest", "finish", "");
				$output["completeText"] = "<div class='questTitle'>$questTitle</div>".getString($conn, $quest, "quest", "complete", "");
				$questRow = getRow($conn, "quests", $quest);
				$counter = 0;
				while($counter != 3){
					//enter all 3 requirements
					$counter++;
					$output["req-" . $counter] = $questRow['req'.$counter];
					$output["amnt-" . $counter] = $questRow['req'.$counter.'amnt'];
				}

				$counter = 0;
				while($counter != 3){
					$counter++;
					$output["reward-".$counter] = $questRow['reward'.$counter];
					//enter all 3 rewards
					$output["rAmnt-".$counter] = $questRow['reward'.$counter.'amnt'];
				}

				$output["expAmnt"] = $questRow['expAmnt'];
				//enter exp reward
				$output["silverAmnt"] = $questRow['silverAmnt'];
				//enter silver reward															//quest ID
				return json_encode($output);
			} else if(($Row['status'] == "complete" && (mysqli_num_rows($sql_rows) == $counter))){
				$output["questID"] = $quest;
				$output["status"] = $Row['status'];
				$output["completeText"] = "<div class='questTitle'>$questTitle</div>".getString($conn, $quest, "quest", "complete", "");
				return json_encode($output);
			}

			$counter++;
		}
		if($quests == false){
				$output["questID"] = -1;
				$output["status"] = 'complete';
				$output["completeText"] = "<div class='questTitle'></div>".getString($conn, $npc, "quest", "noquest", "");
				return json_encode($output);
		}

	}

	function getDailyQuest($conn, $acc, $npc){

		if($npc == -1){
			$sql = "SELECT lastDailyComplete + 26 as questID, lastDailyTime as timestamp FROM \"character\" where playerid = $acc";
			$charRow = mysqli_fetch_array(sql_query($sql, $conn), MYSQLI_ASSOC);
			$quest = $charRow["questID"];
			$timestamp = $charRow["timestamp"];
			date_default_timezone_set("UTC");
			if($timestamp == date("Y-m-d")){
				$output["questID"] = $quest;
				$output["status"] = 'complete';
				$output["completeText"] = "<div class='questTitle'></div>";
				return json_encode($output);
			}else if($timestamp < date("Y-m-d",(strtotime ( '-1 day' , strtotime ( date("Y-m-d")) ) ))){
				$quest = 27;
			}else{
				$quest++;
			}
			if($quest == 32){
				$quest = 27;
			}
			$sql = "SELECT * FROM \"questPlayerStatus\" WHERE \"playerid\" = ".$acc." AND \"questID\" = ".$quest;
			//Grabs the quest status from table
			$Row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);
			$questTitle = getAttribute($conn, "quests", "name", $quest);
			if($Row['status'] == "" || $Row['status'] == "working"){
				$output["questID"] = $quest;
				$output["status"] = $Row['status'];
				//If quest does not exist/in progress..  generate start reply
				$output["startText"] = "<div class='questTitle'>$questTitle</div>".getString($conn, $quest, "quest", "start", "");
				$output["finishText"] = "<div class='questTitle'>$questTitle</div>".getString($conn, $quest, "quest", "finish", "");
				$output["completeText"] = "<div class='questTitle'>$questTitle</div>".getString($conn, $quest, "quest", "complete", "");
				$questRow = getRow($conn, "quests", $quest);
				$counter = 0;
				while($counter != 3){
					//enter all 3 requirements
					$counter++;
					$output["req-" . $counter] = $questRow['req'.$counter];
					$output["amnt-" . $counter] = $questRow['req'.$counter.'amnt'];
				}
				$counter = 0;
				while($counter != 3){
					$counter++;
					$output["reward-" . $counter] = $questRow['reward'.$counter];
					//enter all 3 rewards
					$output["rAmnt-" .$counter] = $questRow['reward'.$counter.'amnt'];
				}
				$output["expAmnt"] = $questRow['expAmnt'];
				//enter exp reward
				$output["silverAmnt"] = $questRow['silverAmnt'];
				//enter silver reward															//quest ID
				return json_encode($output);
			}
		}
	}







	// -- Function Name : startQuest
	// -- Params : $conn, $acc, $quest
	// -- Purpose : Starts a quest based on ID
	function startQuest($conn, $acc, $quest){
		$sql = "UPDATE \"questPlayerStatus\" set \"status\" = 'working' WHERE \"playerid\" = ".$acc." AND \"questID\" = ".$quest;
		sql_query($sql, $conn);

		if(mysqli_affected_rows($conn) == 0){
			$sql = "INSERT INTO \"questPlayerStatus\" (\"playerid\", \"questID\", \"status\") VALUES (".$acc.",".$quest.",'working')";
			sql_query($sql, $conn);
		}
		$questName = getAttribute($conn, 'quests', 'name', $quest);
		logAction($conn, $acc, 'startQuest', $questName, NULL);
	}

	// -- Function Name : cancelQuest
	// -- Params : $conn, $acc, $quest
	// -- Purpose : Cancels a quest based on ID
	function cancelQuest($conn, $acc, $quest){
		$sql = "DELETE FROM \"questPlayerStatus\" WHERE \"playerid\" = ".$acc." AND \"questID\" = ".$quest;
		sql_query($sql, $conn);
		$questRow = getRow($conn, "quests", $quest);
		$counter = 0;
		while($counter != 3){
			$counter++;
			$item = $questRow['req'.$counter];

			if($item != 0){
				$sql = "DELETE FROM \"inventory\" WHERE \"playerid\" = ".$acc." AND \"itemid\" = ".$item;
				sql_query($sql, $conn);
			}

		}

	}

	// -- Function Name : completeQuest
	// -- Params : $conn, $acc, $quest
	// -- Purpose : completes a quest based on ID
	function completeQuest($conn, $acc, $quest){
		$counter = 0;
		$questRow = getRow($conn, "quests", $quest);
		logAction($conn, $acc, 'finishQuest', $questRow['name'], NULL);
		while($counter != 3){
			$counter++;
			$item = $questRow['req'.$counter];

			if($item != 0){
				$amnt = $questRow['req'.$counter."amnt"];
				$playerAmnt = getInventoryItem($conn, $acc, $item);

				if($amnt != $playerAmnt){
					return 0;
				}

			}

		}

		$repeat = getAttribute($conn, "quests", "repeatable", $quest);

		if($repeat == 1){
			$sql = "DELETE FROM \"questPlayerStatus\" WHERE \"playerid\" = ".$acc." AND \"questID\" = ".$quest;
			sql_query($sql, $conn);
		} else {
			$sql = "UPDATE \"questPlayerStatus\" set \"status\" = 'complete' WHERE \"playerid\" = ".$acc." AND \"questID\" = ".$quest;
			sql_query($sql, $conn);
		}

		$sql = "INSERT INTO \"completequests\" (playerid, questid) values ($acc, $quest)";
		sql_query($sql, $conn);
		$counter = 0;
		while($counter != 3){
			$questRow = getRow($conn, "quests", $quest);
			$counter++;
			$item = $questRow['req'.$counter];
			$amnt = $questRow['req'.$counter."amnt"];

			if($item != 0){
				$sql = "DELETE FROM \"inventory\" WHERE \"playerid\" = ".$acc." AND \"itemid\" = ".$item;
				sql_query($sql, $conn);
			}

			$item = $questRow['reward'.$counter];
			$amnt = $questRow['reward'.$counter."amnt"];
			addItemAmount($conn, $acc, $item, $amnt);
		}

		$exp = $questRow['expAmnt'];
		$silver = $questRow['silverAmnt'];
		giveEXPnoncombat($acc, $exp, $conn);
		$sql = "UPDATE \"character\" SET \"silver\" = \"silver\" + ".$silver." WHERE playerid = ".$acc;
		sql_query($sql, $conn);

		if($quest == 5){
			setAchievement($conn, $acc, 9);
		} else
		if($quest == 14){
			setAchievement($conn, $acc, 22);
		}

	}

	// -- Function Name : equipEquipmentItem
	// -- Params : $conn, $acc, $item
	// -- Purpose : equips an equipment item
	function equipEquipmentItem($conn, $acc, $item){
		setAchievement($conn, $acc, 5);
		$slot = getAttribute($conn, "equipmentinventory", "slot", $item);

		if(getAttribute($conn, "equipmentinventory", "script", $item) != "0"){
			setAchievement($conn, $acc, 6);
		}

		if($slot != "accessory"){
			$sql = 'UPDATE "equipmentinventory" SET \"equipped\" = 0 WHERE \"slot\" = "'.$slot.'" AND \"equipped\" = 1 AND "playerid" = '.$acc. ' limit 1';
			sql_query($sql, $conn);
			if($slot == "2hweapon"){
				$sql = 'UPDATE "equipmentinventory" SET "equipped" = 0 WHERE "slot" in ("offhand", "weapon", "2hweapon") AND "equipped" = 1 AND "playerid" = '.$acc. ' limit 3';
				sql_query($sql, $conn);
			}
			if($slot == "offhand"){
				$sql = 'UPDATE "equipmentinventory" SET \"equipped\" = 0 WHERE \"slot\" = "2hweapon" AND \"equipped\" = 1 AND "playerid" = '.$acc. ' limit 1';
				sql_query($sql, $conn);
			}
			if($slot == "weapon"){
				$sql = 'UPDATE "equipmentinventory" SET \"equipped\" = 0 WHERE \"slot\" = "2hweapon" AND \"equipped\" = 1 AND "playerid" = '.$acc. ' limit 1';
				sql_query($sql, $conn);
			}
		} else {
			$sql = 'SELECT COUNT(*) FROM "equipmentinventory" WHERE \"slot\" = "'.$slot.'" AND \"equipped\" = 1 AND "playerid" = '.$acc;
			$Row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);
			$itemCount = $Row['COUNT(*)'];

			if($itemCount == 2){
				$sql = 'UPDATE "equipmentinventory" SET \"equipped\" = 0 WHERE \"slot\" = "'.$slot.'" AND \"equipped\" = 1 AND "playerid" = '.$acc. ' ORDER BY \"index\" DESC limit 1';
				sql_query($sql, $conn);
			}

		}

		$sql = 'UPDATE "equipmentinventory" SET "equipped" = 1 WHERE "index" = '.$item.' AND "playerid" = '.$acc;
		sql_query($sql, $conn);

		if($slot == "weapon" || $slot == "2hweapon"){
			$sql = 'UPDATE "equipmentinventory" SET \"equipped\" = 0 WHERE \"name\" = \"unarmed\" AND "playerid" = '.$acc;
			sql_query($sql, $conn);
		}

		$max = getAttribute($conn, "calcValues", "maxHealth", $acc);
		$hp = getAttribute($conn, "character", "hitpoints", $acc);

		if($hp > $max){
			fullheal($acc, $conn);
		}

		$sql = "SELECT * FROM equipmentinventory where equipped = 1 and template in (67, 97) and playerid = $acc";
		$sql_rows = sql_query($sql, $conn);
		if(mysqli_num_rows($sql_rows) > 0){
			$sql = "select * from playerbuffs where buffID = -20 and playerid = $acc";
			$query = sql_query($sql, $conn);
			if(mysqli_num_rows($query) == 0){
				$sql = "INSERT INTO playerbuffs (buffid, playerid, remaining, image, name, script) values (-20, $acc, 0, 'burningAura', 'Tome of the Elements', 'Surrounds you with a flaming aura that damage all that come near you, burns for 8-11 <strong> fire</strong> damage')";
				sql_query($sql, $conn);
			}
		}else{
			$sql = "DELETE FROM playerbuffs where buffID = -20 and playerid = $acc";
			sql_query($sql, $conn);
		}

	}

	// -- Function Name : unequipEquipmentItem
	// -- Params : $conn, $acc, $item
	// -- Purpose : removes an equipped item
	function unequipEquipmentItem($conn, $acc, $item){
		$slot = getAttribute($conn, "equipmentinventory", "slot", $item);
		$sql = 'UPDATE "equipmentinventory" SET "equipped" = 0 WHERE "index" = '.$item.' AND "playerid" = '.$acc;
		sql_query($sql, $conn);

		if($slot == "weapon" || $slot == "2hweapon"){
			$sql = 'UPDATE "equipmentinventory" SET \"equipped\" = 1 WHERE \"name\" = \"unarmed\" AND "playerid" = '.$acc;
			sql_query($sql, $conn);
		}

		$max = getAttribute($conn, "calcValues", "maxHealth", $acc);
		$hp = getAttribute($conn, "character", "hitpoints", $acc);

		if($hp > $max){
			fullheal($acc, $conn);
		}

		$sql = "SELECT * FROM equipmentinventory where equipped = 1 and template in (67, 97) and playerid = $acc";
		$sql_rows = sql_query($sql, $conn);
		if(mysqli_num_rows($sql_rows) > 0){
			$sql = "select * from playerbuffs where buffID = -20 and playerid = $acc";
			$query = sql_query($sql, $conn);
			if(mysqli_num_rows($query) == 0){
				$sql = "INSERT INTO playerbuffs (buffid, playerid, remaining, image, name, script) values (-20, $acc, 0, 'burningAura', 'Tome of the Elements', 'Surrounds you with a flaming aura that damage all that come near you, burns for 8-11 <strong> fire</strong> damage')";
				sql_query($sql, $conn);
			}
		}else{
			$sql = "DELETE FROM playerbuffs where buffID = -20 and playerid = $acc";
			sql_query($sql, $conn);
		}

	}



	// -- Function Name : getAchievementList
	// -- Params : $conn
	// -- Purpose : Gets all achievements
	function getAchievementList($conn){
		$output = array();
		$sql = 'SELECT * FROM \"achievements\" order by \"order\"';
		$result = sql_query($sql, $conn);
		while($row = mysqli_fetch_array($result,MYSQLI_ASSOC)){
			$script = "<strong>".($row['name'] ?? '')." </strong><br><br>".($row['description'] ?? '')."<br><br>[complete]";
			$string = ($row['index'] ?? 0).'|'.($row['name'] ?? '').'|'.($row['image'] ?? '').'|'.$script;
			$output[] = $string;
		}

		return $output;
	}

	// -- Function Name : getAchievementProgress
	// -- Params : $conn, $acc
	// -- Purpose : gets progrss of all achievements
	function getAchievementProgress($conn, $acc){
		// ===================================================== The Traveler =====================================================
		$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = 1";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($row['timestamp'] == ''){
			$steps = getAttribute($conn, "account", "stepstaken", $acc);

			if($steps > 49000){
				sql_query("INSERT INTO charachievements (playerid, achievementid) values ($acc, 1)", $conn);
			}

		}

		// ===================================================== The Adventurer ===================================================
		$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = 2";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($row['timestamp'] == ''){
			$steps = getAttribute($conn, "account", "stepstaken", $acc);

			if($steps > 999999){
				sql_query("INSERT INTO charachievements (playerid, achievementid) values ($acc, 2)", $conn);
			}

		}

		// ======================================================= Killer =========================================================
		$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = 3";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($row['timestamp'] == ''){
			$sql = "SELECT COALESCE(sum(count), 0) as kills FROM charKills where playerid = $acc;";
			$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);
			$kills = $row['kills'];

			if($kills > 9){
				sql_query("INSERT INTO charachievements (playerid, achievementid) values ($acc, 3)", $conn);
			}

		}

		// =================================================== Seasoned Figher ====================================================
		$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = 4";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($row['timestamp'] == ''){
			$sql = "SELECT COALESCE(sum(count), 0) as kills FROM charKills where playerid = $acc;";
			$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);
			$kills = $row['kills'];

			if($kills > 249){
				sql_query("INSERT INTO charachievements (playerid, achievementid) values ($acc, 4)", $conn);
			}

		}

		// ======================================================= Novice =========================================================
		$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = 12";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($row['timestamp'] == ''){

			if(getAttribute($conn, "character", "level", $acc) > 1){
				sql_query("INSERT INTO charachievements (playerid, achievementid) values ($acc, 12)", $conn);
			}

		}

		// ====================================================== Trainee =========================================================
		$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = 13";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($row['timestamp'] == ''){

			if(getAttribute($conn, "character", "level", $acc) > 9){
				sql_query("INSERT INTO charachievements (playerid, achievementid) values ($acc, 13)", $conn);
			}

		}

		// ====================================================== Well Trined ======================================================
		$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = 14";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($row['timestamp'] == ''){

			if(getAttribute($conn, "character", "level", $acc) > 24){
				sql_query("INSERT INTO charachievements (playerid, achievementid) values ($acc, 14)", $conn);
			}

		}

		// ======================================================== Master ==========================================================
		$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = 15";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($row['timestamp'] == ''){

			if(getAttribute($conn, "character", "level", $acc) > 49){
				sql_query("INSERT INTO charachievements (playerid, achievementid) values ($acc, 15)", $conn);
			}

		}

		// ======================================================= Potaholic ========================================================
		$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = 18";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($row['timestamp'] == ''){
			$sql = "SELECT COALESCE(sum(used), 0) as used FROM inventory where playerid = $acc and itemid in (2,3,4,5,6,7,8,9,10);";
			$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);
			$used = $row['used'];

			if($used > 99){
				sql_query("INSERT INTO charachievements (playerid, achievementid) values ($acc, 18)", $conn);
			}

		}

		// ==================================================== Public Service ======================================================
		$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = 19";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($row['timestamp'] == ''){

			if(count(getCompleteQuests($conn, $acc)) > 19){
				sql_query("INSERT INTO charachievements (playerid, achievementid) values ($acc, 19)", $conn);
			}

		}

		// ======================================================== Rocking =========================================================
		$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = 24";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($row['timestamp'] == ''){
			$sql = "SELECT COALESCE(sum(used), 0) as used FROM inventory where playerid = $acc and itemid in (11,12);";
			$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);
			$used = $row['used'];

			if($used > 24){
				sql_query("INSERT INTO charachievements (playerid, achievementid) values ($acc, 24)", $conn);
			}

		}

		// ===================================================== Homeward Bound =====================================================
		$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = 23";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($row['timestamp'] == ''){
			$sql = "SELECT COALESCE(sum(used), 0) as used FROM inventory where playerid = $acc and itemid in (1);";
			$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);
			$used = $row['used'];

			if($used > 9){
				sql_query("INSERT INTO charachievements (playerid, achievementid) values ($acc, 23)", $conn);
			}

		}

		// ======================================================= Massacre =========================================================
		$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = 25";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($row['timestamp'] == ''){
			$sql = "SELECT COALESCE(sum(count), 0) as kills FROM charKills where playerid = $acc;";
			$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);
			$kills = $row['kills'];

			if($kills > 2999){
				sql_query("INSERT INTO charachievements (playerid, achievementid) values ($acc, 25)", $conn);
			}

		}

		// =================================================== The Collector I =======================================================
		$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = 20";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($row['timestamp'] == ''){
			$sql = "select * from completeQuests where playerid = $acc and questID in (11,13,15,16,20)";
			$result = sql_query($sql, $conn);

			if(mysqli_num_rows($result) > 2){
				sql_query("INSERT INTO charachievements (playerid, achievementid) values ($acc, 20)", $conn);
			}

		}

		// =================================================== The Collector II =======================================================
		$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = 21";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($row['timestamp'] == ''){
			$sql = "select questID, count(questID) from completeQuests where playerid = $acc and questID in (11,13,15,16,20) group by questID";
			$result = sql_query($sql, $conn);

			if(mysqli_num_rows($result) > 4){
				sql_query("INSERT INTO charachievements (playerid, achievementid) values ($acc, 21)", $conn);
			}

		}

		// ================================================== Nothing Better To Do ====================================================
		$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = 26";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($row['timestamp'] == ''){
			$sql = "SELECT questID, COUNT( questID ) AS counter FROM completeQuests WHERE playerid = $acc GROUP BY questID ORDER BY counter DESC";
			$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);
			$kills = $row['counter'];

			if($kills > 4){
				sql_query("INSERT INTO charachievements (playerid, achievementid) values ($acc, 26)", $conn);
			}

		}

		// ==================================================== Lending a Hand ========================================================
		$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = 17";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($row['timestamp'] == ''){
			$sql = "select * from completeQuests where playerid = $acc";
			$result = sql_query($sql, $conn);

			if(mysqli_num_rows($result) > 2){
				sql_query("INSERT INTO charachievements (playerid, achievementid) values ($acc, 17)", $conn);
			}

		}

		// ====================================================== Game Saved ==========================================================
		$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = 27";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($row['timestamp'] == ''){
			$sql = "select * from playerwaypoints where spawnID = 101 and playerid = $acc";
			$result = sql_query($sql, $conn);

			if(mysqli_num_rows($result) > 0){
				sql_query("INSERT INTO charachievements (playerid, achievementid) values ($acc, 27)", $conn);
			}

		}

		// ====================================================== Maxed Out ==========================================================
		$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = 16";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($row['timestamp'] == ''){
		$sql = "SELECT * FROM charSkills c inner join skills s on c.skillID = s.\"id\" where level = maxLevel and playerid = $acc";

			if(mysqli_num_rows($result) > 0){
				sql_query("INSERT INTO charachievements (playerid, achievementid) values ($acc, 16)", $conn);
			}

		}

		// ================================================== Get All Achievements ===================================================
		$sql = 'SELECT * FROM \"achievements\" order by \"order\"';
		$result = sql_query($sql, $conn);
		while($row = mysqli_fetch_array($result,MYSQLI_ASSOC)){
			$counter = $row['index'];
			$success = 0;
			$time = '';
			$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = $counter";
			$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

			if($row['timestamp'] != ''){
				$success = 1;
				$time = explode(" ", $row['timestamp'])[0];
			}


			if($success == 1){
				$pass = "<span style='color:green';><strong>Complete!</strong></span><br>($time)";
			} else {
				$pass = "<span style='color:red';><strong>Incomplete</strong></span>";
			}

			$string = "$counter|$pass";
			$output[] = $string;
		}

		return isset($output) ? $output : array();
	}

	// -- Function Name : setAchievement
	// -- Params : $conn, $acc, $ach
	// -- Purpose : sets an achievement to complete
	function setAchievement($conn, $acc, $ach){
		$sql = "SELECT * FROM charAchievements WHERE playerid = $acc AND achievementID = $ach";
		$row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);

		if($row['timestamp'] == ''){
			sql_query("INSERT INTO charachievements (playerid, achievementid) values ($acc, $ach)", $conn);
		}

	}

	// -- Function Name : getCompleteQuests
	// -- Params : $conn, $acc
	// -- Purpose : gets all complete quests for log
	function getCompleteQuests($conn, $acc){
		$sql = "SELECT DISTINCT(q.questID) as questID, count(*) as 'count', repeatable, name FROM \"completeQuests\" c inner join \"quests\" q on c.questID = q.questID where playerid = $acc group by q.questID, repeatable, name";
		$result = sql_query($sql, $conn);
		while($row = mysqli_fetch_array($result,MYSQLI_ASSOC)){
			$string = $row['questID']."|".$row['repeatable']."|".$row['count']."|".$row['name'];
			$output[] = $string;
		}

		return isset($output) ? $output : array();
	}

	// -- Function Name : getIncompleteQuests
	// -- Params : $conn, $acc
	// -- Purpose : gets all incomplete quests for log
	function getIncompleteQuests($conn, $acc){
		$sql = "SELECT DISTINCT(q.questID) as questID, name FROM \"questPlayerStatus\" c inner join \"quests\" q on c.questID = q.questID where playerid = $acc and status = 'working'";
		$result = sql_query($sql, $conn);
		while($row = mysqli_fetch_array($result,MYSQLI_ASSOC)){
			$string = $row['questID'].'|'.$row['name'];
			$output[] = $string;
		}

		return isset($output) ? $output : array();
	}

	// -- Function Name : getQuestForLog
	// -- Params : $conn, $acc, $questID, $complete
	// -- Purpose : gets quest details for log
	function getQuestForLog($conn, $acc, $questID, $complete){

		$sql = "SELECT * FROM \"quests\" WHERE  \"questID\" = ".$questID;
		$sql_rows = sql_query($sql, $conn);
		while($questList = mysqli_fetch_array($sql_rows,MYSQLI_ASSOC)){
			//Cycles through each row tel one incomplete
			$quest = $questID;
			$sql = "SELECT * FROM \"questPlayerStatus\" s inner join \"quests\" q on q.\"questID\" = s.\"questID\" WHERE \"playerid\" = ".$acc." AND q.\"questID\" = ".$quest;
			//Grabs the quest status from table
			$Row = mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);
			//if($Row['status'] == "" || $Row['status'] == "working"){
			$questTitle = $Row['name'];
			$output["questID"] = $quest;
			$output["status"] = $Row['status'];
			//If quest does not exist/in progress..  generate start reply
			$output["startText"] = "<div class='questTitle'>$questTitle</div>".getString($conn, $quest, "quest", "start", "");
			$output["finishText"] = "<div class='questTitle'>$questTitle</div>".getString($conn, $quest, "quest", "finish", "");
			$output["completeText"] = "<div class='questTitle'>$questTitle</div>".getString($conn, $quest, "quest", "complete", "");
			$questRow = getRow($conn, "quests", $quest);
			$counter = 0;
			while($counter != 3){
				//enter all 3 requirements
				$counter++;
				$output["req-" . $counter] = $questRow['req'.$counter];
				$output["amnt-" . $counter] = $questRow['req'.$counter.'amnt'];
			}

			$counter = 0;
			while($counter != 3){
				$counter++;
				$output["reward-".$counter] = $questRow['reward'.$counter];
				//enter all 3 rewards
				$output["rAmnt-".$counter] = $questRow['reward'.$counter.'amnt'];
			}

			$output["expAmnt"] = $questRow['expAmnt'];
			//enter exp reward
			$output["silverAmnt"] = $questRow['silverAmnt'];
			//enter silver reward

			return json_encode($output);
			//}
		}

	}

	// -- Function Name : getWarpPoints
	// -- Params : $conn, $acc, $pointID
	// -- Purpose : gets all warp points and prices
	function getWarpPoints($conn, $acc, $pointID){
		$row = getRow($conn, "spawnpoints", $pointID);
		$x = $row['posX'];
		$y = $row['posY'];
		$z = $row['posZ'];
		$sql = "SELECT * FROM playerwaypoints p inner join spawnpoints s on s.spawnID = p.spawnID where p.playerid = $acc or p.playerid = -1 order by p.index desc";
		$sql_rows = sql_query($sql, $conn);
		while($waypoints = mysqli_fetch_array($sql_rows,MYSQLI_ASSOC)){
			$distence = sqrt((($x - $waypoints['posX'])*($x - $waypoints['posX'])) + (($y - $waypoints['posY']) * ($y - $waypoints['posY'])) + (($z - $waypoints['posZ'])*($z - $waypoints['posZ'])));
			$distence = $distence * 15;
			$distence = floor($distence /100) * 100;
			$output[] = $distence."|".$waypoints['displayName']."|".$waypoints['spawnID'];
		}

		return isset($output) ? $output : null;
	}

	// -- Function Name : getSpawn
	// -- Params : $conn, $acc
	// -- Purpose : sends player to spawn point
	function getSpawn($conn, $acc){
		return getAttribute($conn, "spawnpoints", "displayName", getAttribute($conn, "character", "respawn", $acc));
	}

	// -- Function Name : setSpawn
	// -- Params : $conn, $acc
	// -- Purpose : sets player spawn point
	function setSpawn($conn, $acc){
		$sql = "select s.spawnID as spawn from spawnpoints s inner join \"character\" c on c.zone = s.displayName where c.playerid = $acc";
		$rows = sql_query($sql, $conn);
		$row = mysqli_fetch_array($rows,MYSQLI_ASSOC);
		$spawn = $row['spawn'];
		sql_query("UPDATE \"character\" set respawn = $spawn where playerid = $acc", $conn);
		return getSpawn($conn, $acc);
	}

	// -- Function Name : teleport
	// -- Params : $conn, $acc, $loc
	// -- Purpose : teleports user to spawn point
	function teleport($conn, $acc, $loc){
		$sql = "select * from spawnpoints s inner join \"character\" c on c.zone = s.displayName where c.playerid = $acc";
		$sql_rows = sql_query($sql, $conn);
		while($row = mysqli_fetch_array($sql_rows,MYSQLI_ASSOC)){
			$x = $row['posX'];
			$y = $row['posY'];
			$z = $row['posZ'];
			$sql = "SELECT * FROM playerwaypoints p inner join spawnpoints s on s.spawnID = p.spawnID where (p.playerid = $acc or p.playerid = -1) and s.spawnID = $loc order by p.index desc";
			$sql_rows = sql_query($sql, $conn);
			while($waypoints = mysqli_fetch_array($sql_rows,MYSQLI_ASSOC)){
				$distence = sqrt((($x - $waypoints['posX'])*($x - $waypoints['posX'])) + (($y - $waypoints['posY']) * ($y - $waypoints['posY'])) + (($z - $waypoints['posZ'])*($z - $waypoints['posZ'])));
				$distence = $distence * 15;
				$distence = floor($distence /100) * 100;
				$index = $waypoints['spawnID'];
				$sql = "UPDATE \"character\" set \"map\" = '".$waypoints["mapName"]."', \"locationX\" = ".$waypoints["telestoneX"].", \"locationY\" = ".$waypoints["telestoneY"].", \"silver\" = \"silver\" - $distence WHERE \"playerid\" = $acc and \"silver\" + 1 > $distence LIMIT 1";
				sql_query($sql, $conn);
				return "0";
			}

		}

		return "nope!";
	}

	// -- Function Name : towerTeleport
	// -- Params : $conn, $acc, $loc
	// -- Purpose : teleports user specific tower level
	function towerTeleport($conn, $acc, $floor){

		$costs[11] = 1;
	    $costs[21] = 2;
	    $costs[31] = 4;
	    $costs[41] = 6;
	    $costs[51] = 10;
	    $costs[61] = 14;
	    $costs[71] = 20;
	    $costs[81] = 26;
	    $costs[91] = 34;

		$sql = "select * from  \"character\" c inner join inventory i on i.playerid = c.playerid
			where c.playerid = $acc and towerLevel >= $floor and itemid = 235 and \"count\" >= " . $costs[$floor];
		$sql_rows = sql_query($sql, $conn);
		while($row = mysqli_fetch_array($sql_rows,MYSQLI_ASSOC)){
			$sql = "update inventory set \"count\" = \"count\" - " . $costs[$floor] . " where itemid = 235 and playerid = $acc";
			sql_query($sql, $conn);
			$sql = "update \"character\" set currentTowerLevel = $floor where playerid = $acc";
			sql_query($sql, $conn);
			return "0";
		}

		return "nope!";
	}

	// -- Function Name : updateZone
	// -- Params : $conn, $acc, $loc
	// -- Purpose : updates zone info
	function updateZone($conn, $acc, $loc){
		sql_query("UPDATE \"character\" SET \"zone\" = '$loc' WHERE \"playerid\" = ".$acc, $conn);
		$sql = "SELECT * FROM playerwaypoints p inner join spawnpoints s on s.spawnID = p.spawnID where p.playerid = $acc and s.displayName = '$loc' order by p.index desc";
		$query = sql_query($sql, $conn);

		if(mysqli_num_rows($query) == 0){
			$sql = "SELECT * FROM \"spawnpoints\" where displayName = '$loc'";
			$query = sql_query($sql, $conn);

				if(mysqli_num_rows($query) > 0){
				$sql = "INSERT INTO playerwaypoints (playerid, spawnid) values ($acc, (SELECT spawnid from spawnpoints WHERE displayname = '$loc'))";
				sql_query($sql, $conn);
				return 1;
			}

		}

	}

	// -- Function Name : getShadows
	// -- Params : $conn, $acc
	// -- Purpose : gets all lighting effects for current map
	function getShadows($conn, $acc){
		$map = getAttribute($conn, "character", "map", $acc);
		if($map != 'endless.php?'){
			$sql = "SELECT l.* FROM \"character\" c INNER JOIN \"lightSources\" l on c.map = l.map inner join \"account\" a on a.playerid = c.playerid where c.playerid = $acc and a.light != 0";
		}else{  //endless tower lights
			$floor = getAttribute($conn, "character", "currentTowerLevel", $acc);
			if($floor == 1){
				$sql = "SELECT l.* FROM \"character\" c INNER JOIN \"lightSources\" l on 'endless.php?1' = l.map inner join \"account\" a on a.playerid = c.playerid where c.playerid = $acc and a.light != 0";
			}else if (($floor - 1) % 10 == 0){
				$sql = "SELECT l.* FROM \"character\" c INNER JOIN \"lightSources\" l on 'endless.php?0' = l.map inner join \"account\" a on a.playerid = c.playerid where c.playerid = $acc and a.light != 0";
			}else{
				$sql = "SELECT l.* FROM \"character\" c INNER JOIN \"lightSources\" l on 'endless.php?x' = l.map inner join \"account\" a on a.playerid = c.playerid where c.playerid = $acc and a.light != 0";
			}
		}
		$result = sql_query($sql, $conn);
		while($row = mysqli_fetch_array($result,MYSQLI_ASSOC)){
			$output[] = $row['x'] . '|' .$row['y'] . '|' .$row['radius'] . '|' .$row['fade'] . '|' .$row['flicker'] . '|' .$row['flicker'];
		}
		return isset($output) ? $output : null;
	}

	// -- Function Name : updateZone
	// -- Params : $conn, $acc, $loc
	// -- Purpose : gets skill tree based on char class
	function getSkillTree($conn, $acc){
		$sql = "select * from skillTrees where class = (select class from \"character\" where playerid = $acc)";
		return mysqli_fetch_array(sql_query($sql, $conn), MYSQLI_ASSOC)["imagelist"];
	}

	// -- Function Name : startDailyQuest
	// -- Params : $conn, $acc
	// -- Purpose : Starts whatever quest is due to start for a character.
	function startDailyQuest($acc, $conn){
		$sql = "SELECT lastDailyComplete + 27 as questID FROM \"character\" where playerid = $acc;";
		//error_log($sql);
		$quest = mysqli_fetch_array(sql_query($sql, $conn), MYSQLI_ASSOC)["questID"];
		if(isset($quest)){
			if ($quest == 32){
				$quest = 27;
			}
			startQuest($conn, $acc, $quest);
		}
	}

	// -- Function Name : finishDailyQuest
	// -- Params : $conn, $acc
	// -- Purpose : Completes the daily quest
	function finishDailyQuest($acc, $conn){
		$sql = "SELECT lastDailyComplete + 27 as questID FROM \"character\" where playerid = $acc;";
		$quest = mysqli_fetch_array(sql_query($sql, $conn), MYSQLI_ASSOC)["questID"];
		if(isset($quest)){
			if($quest != 31){
				completeQuest($conn, $acc, $quest);
				$sql = "update \"character\" set lastDailyComplete = lastDailyComplete + 1, lastDailyTime = date(SYSDATE()) where playerid = $acc";
				sql_query($sql, $conn);
			}else{
				completeQuest($conn, $acc, $quest);
				$sql = "update \"character\" set lastDailyComplete = 0, lastDailyTime = date(SYSDATE()) where playerid = $acc";
				sql_query($sql, $conn);
			}
		}
	}
	function getInventoryJSON($acc, $conn){
		$sql = "select item_id as itemid, COALESCE(count,0) as count, COALESCE(stored,0) as stored, COALESCE(used,0) as used, COALESCE(archived,0) as archived, name, image, value, usable, combat, quest, equipment, value, description, visible from item t left join inventory i on t.item_id = i.itemid and playerid = $acc order by name";
		$result = sql_query($sql, $conn);
		while($row = mysqli_fetch_array($result,MYSQLI_ASSOC)){
			$type = "";

			if($row['quest'] == 1){
				$type = 'Quest<br/><br/><strong>Description: </strong>';
			} else
			if($row['equipment'] == 1){
				$type = 'Equipment<br/><br/><strong>Description: </strong>';
			} else
			if($row['usable'] == 0 && $row['combat'] == 1){
				$type = "Combat<br/><br/><strong>Effect: </strong>";
			} else
			if($row['usable'] == 1 && $row['combat'] == 1){
				$type = "Non-combat/Combat<br/><br/><strong>Effect: </strong>";
			} else
			if($row['usable'] == 1 && $row['combat'] == 0){
				$type = "Non-combat<br/><br/><strong>Effect: </strong>";
			} else {
				$type = "Misc.<br/><br/><strong>Description: </strong>";
			}

			$row["count"] = (int)$row["count"];
			$row["stored"] = (int)$row["stored"];
			$row["archived"] = (int)$row["archived"];


			$row['description'] = "<strong>Item Name: </strong>".$row['name']."<br/><br/><strong>Item Type: </strong>".str_replace('&gt;', '>', str_replace('&lt;', '<', $type.htmlspecialchars($row['description'])));

			$output[] = $row;
		}
		$sql = "SELECT \"index\" as itemid, 1 as \"count\", \"template\", null as \"used\", \"script\" as \"description\", \"archived\", \"upgrade\", \"name\", \"equipped\", \"image\", \"price\" as \"value\", 0 as usable, 0 as combat, 0 as quest, 1 as equipment, 1 as visible FROM \"equipmentinventory\" where \"playerid\" = $acc and \"archived\" = 0 and \"name\" != 'unarmed' order by \"name\";";
		$result = sql_query($sql, $conn);
		while($row = mysqli_fetch_array($result,MYSQLI_ASSOC)){
			if($row['upgrade'] > 0){
				$row["name"] = "+" . $row['upgrade'] . " " . $row["name"];
			}
			if(in_array($row["template"], [51,52,53,54,55,56,57,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74])){
				$row['name'] = "<span style=\"color:#ff6a01; font-weight:bold;\">".$row['name']."</span>" ;
			}else if(in_array($row["template"], [83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104])){
				$row['name'] = "<span style=\"color:#cc00ff; font-weight:bold;\">".$row['name']."</span>" ;
			}else if($row['description'] != "0"){
				$row['name'] = "<span style=\"color:gold; font-weight:bold;\">".$row['name']."</span>" ;
			}
			$output[] = $row;
		}

		return json_encode($output);
	}
	function getRecipe($ing, $conn){
		if($ing[1] + $ing[3] + $ing[5] > 0){ //RECIPE CONTAINS EQUIPMENT
			if($ing[3] == 1){
				$ing[7] = $ing[1];
				$ing[6] = $ing[0];

				$ing[0] = $ing[2];
				$ing[1] = $ing[3];

				$ing[3] = $ing[7];
				$ing[2] = $ing[6];
			}

			if($ing[5] == 1){
				$ing[7] = $ing[1];
				$ing[6] = $ing[0];

				$ing[0] = $ing[4];
				$ing[1] = $ing[5];

				$ing[5] = $ing[7];
				$ing[4] = $ing[6];
			}
			if($ing[1] == 1){
				$sql = "SELECT * FROM equipmentinventory where \"index\" = " . $ing[0];
				$result = sql_query($sql, $conn);
				while($row = mysqli_fetch_array($result,MYSQLI_ASSOC)){
					$currentUp = $row["upgrade"];
					$slot = $row["slot"];
					if(($slot == 'weapon' || $slot == '2hweapon') && $ing[2] == 150 && $ing[4] == 150){
						$rate = (10 - $currentUp) * 12.5;
						if($rate > 100){$rate = 100;}
						$output["rate"] = $rate;
						$output["name"] = "+" . ($currentUp + 1) . " " . $row["name"];
						$output["rateText"] = $rate . "%";
						$output["cost"] = floor($row["price"] * 1.5 + 100);
						$output["costText"] = floor($row["price"] * 1.5 + 100) . " Silver";
						$output["image"] = $row["image"];
						$output["equipment"] = true;
						$output["upgrade"] = "weapon";
						$card = explode("|", getEquipmentItem($conn, $ing[0], true)[0])[4] . "<br/><br/><span style='color:green'> + Bonus Min Damage: 1<br/> + Bonus Max Damage: 2</span>";
						$output["card"] = $card;
						return json_encode($output);
					}else if (($slot == 'helm' || $slot == 'armor' || $slot == 'belt' || $slot == 'boots' || $slot == 'offhand') && $ing[2] == 152 && $ing[4] == 152){
						$rate = (10 - $currentUp) * 10;
						if($rate > 100){$rate = 100;}
						$output["rate"] = $rate;
						$output["name"] = "+" . ($currentUp + 1) . " " . $row["name"];
						$output["rateText"] = $rate . "%";
						$output["cost"] = floor($row["price"] * 1.5 + 100);
						$output["costText"] = floor($row["price"] * 1.5 + 100) . " Silver";
						$output["image"] = $row["image"];
						$output["equipment"] = true;
						$output["upgrade"] = "armor";
						$card = explode("|", getEquipmentItem($conn, $ing[0], true)[0])[4] . "<br/><br/><span style='color:green'> + Bonus Armor: 1</span>";
						$output["card"] = $card;
						return json_encode($output);
					}else if (($slot == 'helm' || $slot == 'armor' || $slot == 'belt' || $slot == 'boots' || $slot == 'offhand') && $ing[2] == 223 && $ing[4] == 223){
						$rate = (10 - $currentUp) * 17.5;
						if($rate > 100){$rate = 100;}
						$output["rate"] = $rate;
						$output["name"] = "+" . ($currentUp + 1) . " " . $row["name"];
						$output["rateText"] = $rate . "%";
						$output["cost"] = floor($row["price"] * 1.25 + 100);
						$output["costText"] = floor($row["price"] * 1.25 + 100) . " Silver";
						$output["image"] = $row["image"];
						$output["equipment"] = true;
						$output["upgrade"] = "armor";
						$card = explode("|", getEquipmentItem($conn, $ing[0], true)[0])[4] . "<br/><br/><span style='color:green'> + Bonus Armor: 1</span>";
						$output["card"] = $card;
						return json_encode($output);
					}else if(($slot == 'weapon' || $slot == '2hweapon') && $ing[2] == 222 && $ing[4] == 222){
						$rate = (10 - $currentUp) * 20;
						if($rate > 100){$rate = 100;}
						$output["rate"] = $rate;
						$output["name"] = "+" . ($currentUp + 1) . " " . $row["name"];
						$output["rateText"] = $rate . "%";
						$output["cost"] = floor($row["price"] * 1.25 + 100);
						$output["costText"] = floor($row["price"] * 1.25 + 100) . " Silver";
						$output["image"] = $row["image"];
						$output["equipment"] = true;
						$output["upgrade"] = "weapon";
						$card = explode("|", getEquipmentItem($conn, $ing[0], true)[0])[4] . "<br/><br/><span style='color:green'> + Bonus Min Damage: 1<br/> + Bonus Max Damage: 2</span>";
						$output["card"] = $card;
						return json_encode($output);
					}
				}
			}
		}else{
			$sql = "select * from Recipe where item1 = ".$ing[0]." AND equip1 = ".$ing[1]." AND item2 = ".$ing[2]." AND equip2 = ".$ing[3]." AND item3 = ".$ing[4]." AND equip3 = ".$ing[5];
			$result = sql_query($sql, $conn);
			while($row = mysqli_fetch_array($result,MYSQLI_ASSOC)){
				$output["cost"] = $row["cost"];
				$output["costText"] = $row["cost"] . " Silver";
				$output["rate"] = $row["rate"];
				$output["rateText"] = $row["rate"] . "%";
				$output["recipeID"] = $row["recipeID"];
				if($row["outcomeType"] == 0){
					$sql = "select * from item where item_ID = ".$row["outcome"];
					$result = sql_query($sql, $conn);
					$row = mysqli_fetch_array($result,MYSQLI_ASSOC);
					$output["itemid"] = $row["item_ID"];
					$output["image"] = $row["image"];
					$output["card"] = getSingleItemCard($row["item_ID"], $conn);
					$output["equipment"] = false;
					return json_encode($output);
				}else{  //OUTCOME IS EQUIPMENT

				}
			}
		}
		return json_encode(false);
	}
	function craft($acc, $ing, $conn){
		if($ing[3] == 1){
			$ing[7] = $ing[1];
			$ing[6] = $ing[0];

			$ing[0] = $ing[2];
			$ing[1] = $ing[3];

			$ing[3] = $ing[7];
			$ing[2] = $ing[6];
		}

		if($ing[5] == 1){
			$ing[7] = $ing[1];
			$ing[6] = $ing[0];

			$ing[0] = $ing[4];
			$ing[1] = $ing[5];

			$ing[5] = $ing[7];
			$ing[4] = $ing[6];
		}
		$recipe = json_decode(getRecipe($ing, $conn), true);
		if($recipe["cost"] > getAttribute($conn, "character", "silver", $acc)){
			return "Crafting Failed, Not Enough Silver";
		}else{
			$sql = "update \"character\" set silver = silver - ".$recipe["cost"]." where playerid = $acc";
			$query = sql_query($sql, $conn);
		}
		if($recipe["equipment"]){
			$sql = "update inventory set count = count - 1 where playerid = $acc and count > 0 and itemid = " . $ing[2];
			$query = sql_query($sql, $conn);
			if (mysqli_affected_rows($conn) == 0) {
				return "Crafting Failed!";
			}
			$sql = "update inventory set count = count - 1 where playerid = $acc and count > 0 and itemid = " . $ing[4];
			$query = sql_query($sql, $conn);
			if (mysqli_affected_rows($conn) == 0) {
				return "Crafting Failed!";
			}
			if($recipe["upgrade"] == "weapon"){
				$success = true;
				if (rand(0, 100) > $recipe["rate"]){
					$sql = "delete from equipmentinventory where \"index\" = " . $ing[0];
					sql_query($sql, $conn);
					$itemName = $recipe["name"];
					logAction($conn, $acc, "Crafting (Failure)", $itemName, null);
					return "Crafting Failed: Item Destroyed!";
				}else{
					$sql = "update equipmentinventory set minDmg = minDmg + 1, maxDmg = maxDmg+ 2, upgrade = upgrade + 1, price = ". $recipe["cost"] ." where \"index\" = " . $ing[0];
					sql_query($sql, $conn);
					$itemName = $recipe["name"];
					logAction($conn, $acc, "Crafting (Success)", $itemName, null);
					return "Crafting Successful: $itemName Created!";
				}
			}else if($recipe["upgrade"] == "armor"){
				$success = true;
				if (rand(0, 100) > $recipe["rate"]){
					$sql = "delete from equipmentinventory where \"index\" = " . $ing[0];
					sql_query($sql, $conn);
					$itemName = $recipe["name"];
					logAction($conn, $acc, "Crafting (Failure)", $itemName, null);
					return "Crafting Failed: Item Destroyed!";
				}else{
					$sql = "update equipmentinventory set armor = armor + 1, upgrade = upgrade + 1, price = ". $recipe["cost"] ." where \"index\" = " . $ing[0];
					sql_query($sql, $conn);
					$itemName = $recipe["name"];
					logAction($conn, $acc, "Crafting (Success)", $itemName, null);
					return "Crafting Successful: $itemName Created!";
				}
			}

		}else{
			$sql = "select * from Recipe where recipeID = ". $recipe["recipeID"];
			$result = sql_query($sql, $conn);
			$row = mysqli_fetch_array($result,MYSQLI_ASSOC);
			$success = true;
			$sql = "update inventory set count = count - 1 where playerid = $acc and count > 0 and itemid = " . $row["item1"];
			$query = sql_query($sql, $conn);
			if (mysqli_affected_rows($conn) == 0) {
				$success = false;
			}
			$sql = "update inventory set count = count - 1 where playerid = $acc and count > 0 and itemid = " . $row["item2"];
			$query = sql_query($sql, $conn);
			if (mysqli_affected_rows($conn) == 0) {
				$success = false;
			}
			$sql = "update inventory set count = count - 1 where playerid = $acc and count > 0 and itemid = " . $row["item3"];
			$query = sql_query($sql, $conn);
			if (mysqli_affected_rows($conn) == 0) {
				$success = false;
			}
			if($success && rand(0, 100) < $row["rate"]){
				if($row["outcomeType"] == 0){
					addItemAmount($conn, $acc, $row["outcome"], 1);
					$itemName = getAttribute($conn, "item", "name", $row["outcome"]);
					logAction($conn, $acc, "Crafting (Success)", $itemName, null);
					return "Crafting Successful: $itemName Created!";

				}
			}else{
				$itemName = getAttribute($conn, "item", "name", $row["outcome"]);
				logAction($conn, $acc, "Crafting (Failure)", $itemName, null);
				return "Crafting Failed!";

			}
		}
	}

?>
