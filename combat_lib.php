<?php
    // Include centralized configuration
    require_once(__DIR__ . '/config.php');
    include_once ('common_lib.php');

    // -- Function Name : attack
    // -- Params : $acc, $conn
    // -- Purpose : Attacks current enemy

    function attack($acc, $conn, $charStats)
    {
    	$row = getRow($conn, "character", $acc);
    	$hit = $charStats['hit'];
    	if ($row['hitpoints'] < 1) {
    		return 3;
    	}
    	$sql = "SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY combatEnemyID DESC LIMIT 1";
    	$sql_get_enemy = sql_query($sql, $conn);
    	$enemyRow = $sql_get_enemy->fetch();
        if(!isset($enemyRow)){
            return;
        }
    	$enemyID = $enemyRow['enemyID'];
    	$enemyHP = $enemyRow['health'];
    	$monsterName = $enemyRow['prefix'];
    	$element = $charStats['weapElement'];
    	$elementText = "";
    	if (strtolower($element) == 'fire') {
    		$elementText = "<span style=\'color:red\'> Fire</span>";
    	}
    	else
    	if (strtolower($element) == 'earth') {
    		$elementText = "<span style=\'color:brown\'> Earth</span>";
    	}
    	else
    	if (strtolower($element) == 'ice') {
    		$elementText = "<span style=\'color:Blue\'> Ice</span>";
    	}
    	else
    	if (strtolower($element) == 'arcane') {
    		$elementText = "<span style=\'color:purple\'> Arcane</span>";
    	}
    	$flee = $enemyRow['flee'];
    	if ($monsterName != "") {
    		$monsterName = "the ";
    	}

    	$levelMod = $enemyRow['level'] - $row["level"];
    	if ($levelMod < 0) {
    		$levelMod = 0;
    	}
    	$levelMod = pow($levelMod, 2) / 2;
    	$flee = $flee + $levelMod;
    	$monsterName.= $enemyRow['name'];
    	$sql = "SELECT class, name, template FROM equipmentInventory WHERE name != 'unarmed' AND slot = 'weapon' AND equipped = 1 AND playerID = $acc LIMIT 1";
    	$row = sql_query($sql, $conn)->fetch();
    	$itemClass = $row['class'];
    	$text = getString($conn, "0", "melee", "left", $itemClass);
    	if ($itemClass != "") {
    		$text = str_replace('[weapon]', strtolower($row['name']) , $text);
    	}
    	$message = str_replace('[enemy]', $monsterName, $text);
    	$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $message . "')";
    	sql_query($logMessege, $conn);
    	if ($row['template'] == 53 || $row['template'] == 85) {
    		if (in_array($enemyid, [147, 148, 149, 150])) {

    			// CANNOT BE SMASHED

    		}
    		else {
    			$enemyMax = getAttribute($conn, "enemies", "health", $enemyID);
    			$enemyPercent = floor($enemyHP / $enemyMax * 100);
    			if ($enemyPercent < 21 && (rand(0, 100) > 75)) {
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) values (" . $acc . "," . $enemyID . ",'RIGHT','...and SMASHED the $monsterName to smithereens')";
    				sql_query($logMessege, $conn);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    				sql_query($logMessege, $conn);
    				doDamage($conn, $acc, 99999999999, $enemyRow);
    				return;
    			}
    		}
    	}

    	if (($charStats['critRate'] * 100) > rand(0, 100)) {

    		// CRITICAL HIT

    		$damage = getCrit($conn, $acc);
    		$sql_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY combatEnemyID DESC LIMIT 1", $conn);
    		$row = $sql_enemy->fetch();
    		if (isset($element) && $element != "") {
    			$mod = 1;

    			// $mod = (100 - $row[strtolower($element).'Res']) / 100;

    		}
    		else {
    			$mod = 1;
    		}

    		$damage = ($mod * $damage);
    		if ($damage < 1) {
    			$damage = 1;
    		}

    		doDamage($conn, $acc, $damage, $enemyRow);
    		$text = getString($conn, "0", "combat", "right", "");
    		$damage = number_format($damage);
    		$message = str_replace('[damage]', $damage . $elementText, $text);
    		$message = "<strong>" . $message . "!!</strong>";
    		$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $damage . "!!</strong>')";
    		sql_query($logMessege, $conn);
    		dblAttack($acc, $conn, $damage, $enemyRow, $elementText, $enemyid, $hit, $flee, $itemClass);
    		$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    		sql_query($logMessege, $conn);
    	} else {
    		if (attackHit($hit, $flee) == 0) {
    			$text = getString($conn, "0", "combat", "right", "miss");
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $text . "', '#E00101', '<strong></strong>')";
    			sql_query($logMessege, $conn);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    			sql_query($logMessege, $conn);
    		} else {
    			$damage = getDamage($conn, $acc, $charStats);
    			$damage = ceil(resistDamage($conn, $acc, $damage, isset($element) ? $element : 'physical'));
    			doDamage($conn, $acc, $damage, $enemyRow);
    			$text = getString($conn, "0", "combat", "right", "");
    			$damage = number_format($damage);
    			$message = str_replace('[damage]', $damage . $elementText, $text);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $damage . "</strong>')";
    			sql_query($logMessege, $conn);
    			dblAttack($acc, $conn, $damage, $enemyRow, $elementText, $enemyid, $hit, $flee, $itemClass);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    			sql_query($logMessege, $conn);
    		}
    	}
    }

    function dblAttack($acc, $conn, $damage, $enemyRow, $elementText, $enemyid, $hit, $flee, $itemClass)
    {
    	$sql = "select sl.damage as damage, sl.chance as chance from playerBuffs pb
                inner join skillLevels sl on pb.buffID = sl.skillID
                inner join charSkills cs on cs.playerID = pb.playerID
                where pb.playerID = $acc and sl.level = cs.level and cs.skillID = 29 and sl.skillID = 29";
    	$query = sql_query($sql, $conn);
    	if (mysqli_num_rows($query) == 0) {
    		return;
    	}

    	$row = mysqli_fetch_array($query, MYSQLI_ASSOC);
    	$chance = $row['chance'];
    	$mod = $row['damage'];
    	$sql = "SELECT * FROM \"equipmentInventory\" where \"template\" = 74 AND \"equipped\" = 1 AND \"playerid\" = $acc LIMIT 1";
    	$query = sql_query($sql, $conn);
    	if (mysqli_num_rows($query) == 1) {
    		$chance = $chance + 5;
    	}

    	if (rand(0, 100) < $chance) {
    		$text = getString($conn, "29", "skill", "left", "");
    		$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "')";
    		sql_query($logMessege, $conn);
    		if (attackHit($hit + 10, $flee) == 0) {
    			$text = getString($conn, "0", "combat", "right", "miss");
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $text . "', '#E00101', '<strong></strong>')";
    			sql_query($logMessege, $conn);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    			sql_query($logMessege, $conn);
    		}
    		else {
    			$dblDmg = ceil($damage * $mod);
                $damage = ceil(resistDamage($conn, $acc, $dblDmg, isset($element) ? $element : 'physical'));
    			doDamage($conn, $acc, $dblDmg, $enemyRow);
    			$text = getString($conn, "0", "combat", "right", "");
    			$dblDmg = number_format($dblDmg);
    			$message = str_replace('[damage]', $dblDmg . $elementText, $text);
    			$logMessege = 'INSERT INTO "combat" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (' . $acc . ',' . $enemyID . ' ,"RIGHT","' . $message . '", "#E00101", "<strong>' . $dblDmg . '</strong>")';
    			sql_query($logMessege, $conn);
    			cripple($acc, $conn, $enemyRow, $enemyID);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    			sql_query($logMessege, $conn);
    		}
    	}
    }

    function cripple($acc, $conn, $enemyRow, $enemyID)
    {
    	$sql = "select sl.damage as damage, sl.chance as chance from playerBuffs pb
                inner join skillLevels sl on pb.buffID = sl.skillID
                inner join charSkills cs on cs.playerID = pb.playerID
                where pb.playerID = $acc and sl.level = cs.level and cs.skillID = 30 and sl.skillID = 30";
    	$query = sql_query($sql, $conn);
    	if (mysqli_num_rows($query) == 0) {
    		return;
    	}

    	$row = mysqli_fetch_array($query, MYSQLI_ASSOC);
    	$chance = $row['chance'];
    	$mod = $row['damage'];
    	$sql = "SELECT * FROM \"equipmentInventory\" where \"template\" = 74 AND \"equipped\" = 1 AND \"playerid\" = $acc LIMIT 1";
    	$query = sql_query($sql, $conn);
    	if (mysqli_num_rows($query) == 1) {
    		$chance = $chance + 5;
    	}

    	if (rand(0, 100) < $chance) {
    		$text = getString($conn, "30", "skill", "left", null);
    		$text = str_replace('[enemy]', $enemyRow['name'], $text);
    		$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "')";
    		sql_query($logMessege, $conn);
    		if (1 == 0) {

    			// MISS?

    		}
    		else {
    			$text = getString($conn, "30", "skill", "right", "");
    			$message = str_replace('[enemy]', $enemyRow['name'], $text);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>5%</strong>')";
    			sql_query($logMessege, $conn);
    			$attack = "INSERT INTO \"combatenemies\" (\"maxhealth\", \"enemyid\", \"playerid\" , \"prefix\" , \"name\" , \"attack\" , \"mattack\" , \"fireres\" , \"earthres\" , \"iceres\" , \"holyres\" , \"arcaneres\" , \"physicalres\" , \"softdef\" , \"softmdef\" , \"health\" , \"exp\", \"silver\", \"hit\", \"flee\", \"level\", \"stunresist\")
                        SELECT \"maxhealth\", \"enemyid\", \"playerid\" , \"prefix\" , \"name\" , (\"attack\" * .9), (\"mattack\" * .9) , \"fireres\" , \"earthres\" , \"iceres\" , \"holyres\" , \"arcaneres\" , \"physicalres\" , \"softdef\" , \"softmdef\" , \"health\", \"exp\", \"silver\", \"hit\", \"flee\", \"level\", \"stunresist\"
                        FROM \"combatEnemies\" WHERE \"enemyid\" =" . $enemyID . " AND \"playerid\" = " . $acc . "
                        ORDER BY \"combatEnemyID\" DESC LIMIT 1";
    			sql_query($attack, $conn);
    			darkAssault($acc, $conn, $enemyRow, $enemyID);
    		}
    	}
    }

    function darkAssault($acc, $conn, $enemyRow, $enemyID)
    {
    	$sql = "select sl.damage as damage, sl.chance as chance from playerBuffs pb
                inner join skillLevels sl on pb.buffID = sl.skillID
                inner join charSkills cs on cs.playerID = pb.playerID
                where pb.playerID = $acc and sl.level = cs.level and cs.skillID = 31 and sl.skillID = 31";
    	$query = sql_query($sql, $conn);
    	if (mysqli_num_rows($query) == 0) {
    		return;
    	}

    	$row = mysqli_fetch_array($query, MYSQLI_ASSOC);
    	$chance = $row['chance'];
    	$mod = $row['damage'];
    	$sql = "SELECT * FROM \"equipmentInventory\" where \"template\" = 74 AND \"equipped\" = 1 AND \"playerid\" = $acc LIMIT 1";
    	$query = sql_query($sql, $conn);
    	if (mysqli_num_rows($query) == 1) {
    		$chance = $chance + 5;
    	}

    	if (rand(0, 100) < $chance) {
    		$text = getString($conn, "31", "skill", "left", "");
    		$text = str_replace('[enemy]', $enemyRow['name'], $text);
    		$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "')";
    		sql_query($logMessege, $conn);
    		if (1 == 0) {

    			// MISS?

    		}
    		else {
    			$damage = ceil(rand(12, 16) * (($calcValues["spr"] / $mod + 1)));
    			$damage = ceil(resistDamage($conn, $acc, $damage, 'arcane'));
    			doDamage($conn, $acc, $damage, $enemyRow);
    			$text = getString($conn, "0", "combat", "right", "");
    			$dblDmg = number_format($damage);
    			$message = str_replace('[damage]', $dblDmg . " <strong style='color:purple'>arcane</strong>", $text);
    			$logMessege = 'INSERT INTO "combat" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (' . $acc . ',' . $enemyID . ' ,"RIGHT","' . $message . '", "#E00101", "<strong>' . $dblDmg . '</strong>")';
    			sql_query($logMessege, $conn);
    			condem($acc, $conn, $enemyRow, $enemyID);
    		}
    	}
    }

    function condem($acc, $conn, $enemyRow, $enemyID)
    {
    	$sql = "select sl.damage as damage, sl.chance as chance from playerBuffs pb
                inner join skillLevels sl on pb.buffID = sl.skillID
                inner join charSkills cs on cs.playerID = pb.playerID
                where pb.playerID = $acc and sl.level = cs.level and cs.skillID = 32 and sl.skillID = 32";
    	$query = sql_query($sql, $conn);
    	if (mysqli_num_rows($query) == 0) {
    		return;
    	}

    	$row = mysqli_fetch_array($query, MYSQLI_ASSOC);
    	$chance = $row['chance'];
    	$sql = "SELECT * FROM \"equipmentInventory\" where \"template\" = 74 AND \"equipped\" = 1 AND \"playerid\" = $acc LIMIT 1";
    	$query = sql_query($sql, $conn);
    	if (mysqli_num_rows($query) == 1) {
    		$chance = $chance + 5;
    	}

    	if (rand(0, 100) < $chance) {
    		$text = getString($conn, "32", "skill", "left", "");
    		$text = str_replace('[enemy]', $enemyRow['name'], $text);
    		$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "')";
    		sql_query($logMessege, $conn);
    		if (1 == 0) {

    			// MISS?

    		}
    		else {
    			doDamage($conn, $acc, 9999999, $enemyRow);
    			$text = getString($conn, "32", "skill", "right", "");
    			$text = str_replace('[enemy]', $enemyRow['name'], $text);
    			$logMessege = 'INSERT INTO "combat" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (' . $acc . ',' . $enemyID . ' ,"RIGHT","' . $text . '", "#E00101", "<strong></strong>")';
    			sql_query($logMessege, $conn);
    		}
    	}
    }

    // -- Function Name : chooseEnemy
    // -- Params : $acc, $steps, $conn
    // -- Purpose : Finds out if there are enemies in a zone, then rolls for an encounter

    function chooseEnemy($acc, $steps, $conn)
    {
    	$buffmod = 10;
    	$buffmulti = 1;
    	$sql = "select sum(test) as active from (SELECT count(*) as test FROM \"playerBuffs\" a where playerid = $acc and itemID in (86,87,88,93,102,87) union SELECT count(*) as test FROM \"equipmentInventory\" b where playerid = $acc and template in (56, 63, 72) and equipped = 1) as t";
    	$query = sql_query($sql, $conn);
    	$row = mysqli_fetch_array($query, MYSQLI_ASSOC);
    	if ($row["active"] > 0) {
    		$buffmod = 3;
    		$buffmulti = 100;
    	}

			$charRow = getRow($conn, "character", $acc);
    	$zone = $charRow['zone'];
    	if (strpos($zone, 'Kal-Rul Tower') !== false) {

    		if (($charRow['currentTowerLevel'] - 1) % 10 == 0) {
    			return;
    		}

    		$bossDead = $charRow['towerBoss'];
    		if (strpos($zone, 'BOSS') !== false && $charRow['towerBoss'] < $charRow['currentTowerLevel']) {
    			$buffmod = 3;
    			$buffmulti = 100;
    			$e_level = $_SESSION['towerLevel'] + 63;
    			$atk = $charRow['diff'] * (pow($e_level, 2) + 0.3 * $e_level + 4.55) / 5.6 + 5;
    			$def = $charRow['diff'] * (pow($e_level, 2) + 0.3 * $e_level + 4.55) / 10;
    			$hit = (pow($e_level, 2) + 0.3 * $e_level + 4.55) / 9 + 100;
    			$flee = (pow($e_level, 2) + 0.3 * $e_level + 4.55) / 9 + 30;
    			$health = $charRow['diff'] * (pow($e_level, 2) + 0.3 * $e_level + 4.55) * 1.5;
    			$exp = (0.0019 * pow($e_level + 35, 4) - 0.065 * pow($e_level + 35, 3) + 1.12 * pow($e_level + 35, 2) + 4 * $e_level + 35) / 2;
    			$sql = "UPDATE \"character\" SET \"combatModifier\" = 0 WHERE playerID = " . $acc;
    			sql_query($sql, $conn);
    			$combatRow = sql_query("SELECT * FROM \"combat\" WHERE \"playerid\" = " . $acc, $conn);
    			if (mysqli_num_rows($combatRow) > 0) {
    				return;
    			}

    			$initCombat = "INSERT INTO \"combatenemies\" ( \"enemyid\" , \"playerid\" , \"prefix\" , \"name\" , \"attack\" , \"fireres\" , \"earthres\" , \"iceres\" , \"holyres\" , \"arcaneres\" , \"physicalres\" , \"softdef\" , \"softmdef\" , \"hit\" , \"flee\" , \"health\" , \"exp\", \"level\", \"silver\", \"maxhealth\", \"mattack\")
                    SELECT \"enemyid\" , $acc , \"prefix\" , \"name\" , $atk , \"fireres\" , \"earthres\" , \"iceres\" , \"holyres\" , \"arcaneres\" , \"physicalres\" , $def , $def , $hit , $flee , $health , $exp, $e_level, 100, $health, $atk
                    FROM \"enemies\"
                    WHERE \"enemyid\" = 150";
    			sql_query($initCombat, $conn);
    			$initCombat = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) select playerid, enemyid, 'CENTER', concat_ws('', '<strong>You have encountered ', prefix, name, '</strong><br/>') from \"combatenemies\" where playerid = $acc";
    			sql_query($initCombat, $conn);
    			return 5;
    		}
    		else {
    			$e_level = $charRow['currentTowerLevel'] + 60;
    			if ($e_level == null) {
    				return 'wat?';

    				// DEBUG

    			}

    			if ($charRow['combatModifier'] < $buffmod) {
    				return 'modifier: ' . $charRow['combatModifier'];
    			}

    			$combatCoeff = $charRow['combatModifier'] / 15;
    			$combatCoeff = $combatCoeff * $buffmulti;
    			$level = $charRow['level'];
    			$levelDiff = $level - $e_level;
    			$diffFactor = 100;
    			if ($levelDiff > 15) {
    				$levelDiff = 15;
    			}
    			else
    			if ($levelDiff < - 15) {
    				$levelDiff = - 15;
    			}

    			if ($levelDiff < 0) {
    				$diffFactor = 100 + (abs($levelDiff) * 10);
    			}
    			else
    			if ($levelDiff > 0) {
    				$diffFactor = 100 - (abs($levelDiff) * 5);
    			}
    			else {
    				$diffFactor = 100;
    			}

    			$steps = $steps * 2.5;
    			$freq = $steps * ($diffFactor / 100) * $combatCoeff;
    			$roll = rand(0, 100);
    			if ($roll < $freq) {
    				$sql = "UPDATE \"character\" SET \"combatModifier\" = 0 WHERE playerID = " . $acc;
    				sql_query($sql, $conn);
    				$combatRow = sql_query("SELECT * FROM \"combat\" WHERE \"playerid\" = " . $acc, $conn);
    				if (mysqli_num_rows($combatRow) > 0) {
    					return;
    				}

    				$atk = $charRow['diff'] * (pow($e_level, 2) + 0.3 * $e_level + 4.55) / 5.6 + 5;
    				$def = $charRow['diff'] * (pow($e_level, 2) + 0.3 * $e_level + 4.55) / 10;
    				$hit = (pow($e_level, 2) + 0.3 * $e_level + 4.55) / 9 + 100;
    				$flee = (pow($e_level, 2) + 0.3 * $e_level + 4.55) / 9 + 30;
    				$health = $charRow['diff'] * (pow($e_level, 2) + 0.3 * $e_level + 4.55) * 1.5;
                    error_log("level: " . $e_level . " health: " . $health);
    				$rand = mt_rand(1, 3);
    				$enemyID = $rand + 146;
    				if ($rand == 1) { //assassin
    					$atk = $atk * 1.1;
    					$def = $def * .85;
    					$hit = $hit * 1.1;
    				    $flee = $flee * 1.2;
    					$health = $health * .8;
    				}
    				else
    				if ($rand == 2) { //warlock
    					$atk = $atk * 1.5;
    					$def = $def * .85;
    					$hit = $hit * .8;
    					$flee = $flee * .8;
    					$health = $health * .8;
    				}
    				else { //crusader
    					$atk = $atk * .9;
    					$def = $def * 1.4;
    					$hit = $hit * 1.3;
    					$flee = $flee * .9;
    					$health = $health * 1.5;
    				}

    				$exp = (0.0019 * pow($e_level + 30, 4) - 0.065 * pow($e_level + 30, 3) + 1.12 * pow($e_level + 30, 2) + 4 * $e_level + 30) / 2;
    				$sql = "UPDATE \"character\" SET "combatModifier" = 0 WHERE playerID = " . $acc;
    				sql_query($sql, $conn);
    				$combatRow = sql_query("SELECT * FROM "combat" WHERE "playerid" = " . $acc, $conn);
    				if (mysqli_num_rows($combatRow) > 0) {
    					return;
    				}

    				$initCombat = "INSERT INTO \"combatenemies\" ( \"enemyid\" , \"playerid\" , \"prefix\" , \"name\" , \"attack\" , \"fireres\" , \"earthres\" , \"iceres\" , \"holyres\" , \"arcaneres\" , \"physicalres\" , \"softdef\" , \"softmdef\" , \"hit\" , \"flee\" , \"health\" , \"exp\", \"level\", \"silver\", \"maxhealth\", \"mattack\")
                        SELECT \"enemyid\" , $acc , \"prefix\" , \"name\" , $atk , \"fireres\" , \"earthres\" , \"iceres\" , \"holyres\" , \"arcaneres\" , \"physicalres\" , $def , $def , $hit , $flee , $health , $exp, $e_level, 100, $health, $atk
                        FROM \"enemies\"
                        WHERE \"enemyid\" = $enemyID";
    				sql_query($initCombat, $conn);
    				$roll = rand(0, 10000);
    				if ($roll < 200) {
    					$sql = "SELECT * FROM monsterModLookup where enemyID = $enemyID and chance = 1 ORDER BY RAND() LIMIT 1";
    					$sql_result = sql_query($sql, $conn);
    					while ($row = mysqli_fetch_array($sql_result, MYSQLI_ASSOC)) {
    						$sql = "update combatEnemies c, monsterMods m set c.prefix = '', c.name = concat_ws(' ', m.openTag, m.prefix, c.name, m.suffix, m.closeTag)";
    						$sql.= ", hit = hit * hitMod, flee = flee * fleeMod, health = health * healthMod, maxhealth = maxhealth * healthMod, attack = attack * dmgMod, exp = exp * expMod, silver = silver * dropMod where playerid = $acc and modID = " . $row["modID"];
    						sql_query($sql, $conn);
    					}
    				}
    				else
    				if ($roll < 1500) {
    					$sql = "SELECT * FROM monsterModLookup where enemyID = $enemyID and chance = 15 ORDER BY RAND() LIMIT 1";
    					$sql_result = sql_query($sql, $conn);
    					while ($row = mysqli_fetch_array($sql_result, MYSQLI_ASSOC)) {
    						$sql = "update combatEnemies c, monsterMods m set c.name = concat_ws(' ', m.openTag, m.prefix, c.name, m.suffix, m.closeTag)";
    						$sql.= ", hit = hit * hitMod, flee = flee * fleeMod, health = health * healthMod, maxhealth = maxhealth * healthMod, attack = attack * dmgMod, exp = exp * expMod, silver = silver * dropMod where playerid = $acc and modID = " . $row["modID"];
    						sql_query($sql, $conn);
    					}
    				}

    				$initCombat = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) select playerid, enemyid, 'CENTER', concat_ws('', '<strong>You have encountered ', prefix, name, '</strong><br/>') from \"combatenemies\" where playerid = $acc";
    				sql_query($initCombat, $conn);
    				return 5;
    			}
    		}
    	}
    	else {
    		$sql_get_level = "SELECT MIN(e.level) as level, c.map, c.diff FROM \"character\" c Inner join \"enemySpawns\" s on c.zone = s.zone	inner join \"enemies\" e on s.enemyID = e.enemyID where c.playerID = $acc AND s.startDate < NOW() AND s.endDate > NOW()";
    		$sql_result = sql_query($sql_get_level, $conn);
    		$row = mysqli_fetch_array($sql_result, MYSQLI_ASSOC);
    		$map = $row['map'];
    		$diffMod = $row['diff'];
    		$e_level = $row['level'];
    		if ($e_level == null) {
    			return;
    		}

    		$row = getRow($conn, "character", $acc);
    		if ($row['combatModifier'] < $buffmod) {
    			return 'modifier: ' . $row['combatModifier'];
    		}

    		$combatCoeff = $row['combatModifier'] / 15;
    		$combatCoeff = $combatCoeff * $buffmulti;
    		$level = $row['level'];
    		$levelDiff = $level - $e_level;
    		$diffFactor = 100;
    		if ($levelDiff > 15) {
    			$levelDiff = 15;
    		}
    		else
    		if ($levelDiff < - 15) {
    			$levelDiff = - 15;
    		}

    		if ($levelDiff < 0) {
    			$diffFactor = 100 + (abs($levelDiff) * 10);
    		}
    		else
    		if ($levelDiff > 0) {
    			$diffFactor = 100 - (abs($levelDiff) * 5);
    		}
    		else {
    			$diffFactor = 100;
    		}

    		$steps = $steps * 2.5;
    		$freq = $steps * ($diffFactor / 100) * $combatCoeff;
    		$roll = rand(0, 100);
    		if ($roll < $freq) {
    			$sql = "UPDATE \"character\" SET "combatModifier" = 0 WHERE playerID = " . $acc;
    			sql_query($sql, $conn);
    			$totalWeight = 0;
    			$maxSQL = "SELECT SUM(weight) as total FROM \"character\" c Inner join \"enemySpawns\" s on c.zone = s.zone or s.zone = 'all' where c.playerID = $acc AND s.startDate < NOW() AND s.endDate > NOW()";
    			$max = mysqli_fetch_array(sql_query($maxSQL, $conn) , MYSQLI_ASSOC) ['total'];
    			$roll = rand(0, $max);
    			$sql_get_enemies = "SELECT e.enemyid, e.name, s.weight, e.prefix, e.quest, weight FROM \"character\" c Inner join \"enemySpawns\" s on c.zone = s.zone or s.zone = 'all' inner join \"enemies\" e on s.enemyID = e.enemyID where c.playerID = $acc AND s.startDate < NOW() AND s.endDate > NOW()";
    			$sql_result = sql_query($sql_get_enemies, $conn);
    			while ($row = mysqli_fetch_array($sql_result, MYSQLI_ASSOC)) {
    				$totalWeight = $totalWeight + intval($row['weight']);
    				if ($totalWeight > $roll) {
    					$combatRow = sql_query("SELECT * FROM \"combat\" WHERE \"playerid\" = " . $acc, $conn);
    					if (mysqli_num_rows($combatRow) > 0) {
    						return;
    					}

    					if ($row['quest'] > 0) {
    						$sql_quest = 'SELECT * FROM \"enemies\" e, "questPlayerStatus" p, \"quests\" q WHERE (e.enemyID = ' . $row['enemyID'] . ' AND q.req1 = e.quest AND q.questID = p.questID AND p.status = \"working\" AND p.playerID =  ' . $acc . ')';
    						$sqlQuest = sql_query($sql_quest, $conn);
    						$rowQuest = mysqli_fetch_array($sqlQuest, MYSQLI_ASSOC);
    						sql_query($sql_quest, $conn);
    						if ((mysqli_num_rows($sqlQuest) != 0) && (getInventoryItem($conn, $acc, $rowQuest['req1']) != $rowQuest['req1amnt'])) {
    							$initCombat = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) values (" . $acc . "," . $row['enemyID'] . ",'CENTER','<strong>You have encountered " . $row['prefix'] . $row['name'] . "</strong>')";
    							sql_query($initCombat, $conn);
    							$initCombat = "INSERT INTO \"combatenemies\" ( \"enemyid\" , \"playerid\" , \"prefix\" , \"name\" , \"attack\" , \"fireres\" , \"earthres\" , \"iceres\" , \"holyres\" , \"arcaneres\" , \"physicalres\" , \"softdef\" , \"softmdef\" , \"hit\" , \"flee\" , \"health\" , \"exp\", \"level\", \"silver\", \"maxhealth\", \"mattack\")
                                        SELECT \"enemyid\" , " . $acc . ", \"prefix\" , \"name\" , CEIL(\"attack\" * $diffMod) , \"fireres\" , \"earthres\" , \"iceres\" , \"holyres\" , \"arcaneres\" , \"physicalres\" , CEIL(\"softdef\" * $diffMod) , CEIL(\"softmdef\" * $diffMod) , \"hit\" , \"flee\" , CEIL(\"health\" * $diffMod)  , \"exp\", \"level\", \"silver\", CEIL(\"health\" * $diffMod), \"mattack\"
                                        FROM \"enemies\"
                                        WHERE \"enemyid\" =" . $row['enemyID'];
    							sql_query($initCombat, $conn);
    							$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $row['enemyID'] . ",'LEFT','<br/>', '#E00101', ' ')";
    							sql_query($logMessege, $conn);
    							return 5;
    						}
    					}
    					else {
    						$initCombat = "INSERT INTO \"combatenemies\" ( \"enemyid\" , \"playerid\" , \"prefix\" , \"name\" , \"attack\" , \"fireres\" , \"earthres\" , \"iceres\" , \"holyres\" , \"arcaneres\" , \"physicalres\" , \"softdef\" , \"softmdef\" , \"hit\" , \"flee\" , \"health\" , \"exp\", \"level\", \"silver\", \"maxhealth\", \"mattack\")
                                SELECT \"enemyid\" , " . $acc . ", \"prefix\" , \"name\" , CEIL(\"attack\" * $diffMod) , \"fireres\" , \"earthres\" , \"iceres\" , \"holyres\" , \"arcaneres\" , \"physicalres\" , CEIL(\"softdef\" * $diffMod) , CEIL(\"softmdef\" * $diffMod) , \"hit\" , \"flee\" , CEIL(\"health\" * $diffMod)  , \"exp\", \"level\", \"silver\", CEIL(\"health\" * $diffMod), \"mattack\"
                                FROM \"enemies\"
                                WHERE \"enemyid\" =" . $row['enemyID'];
    						sql_query($initCombat, $conn);
    						$roll = rand(0, 10000);
    						if ($roll < 200) {
    							$sql = "SELECT * FROM monsterModLookup where enemyID = " . $row['enemyID'] . " and chance = 1 ORDER BY RAND() LIMIT 1";
    							$sql_result = sql_query($sql, $conn);
    							while ($row = mysqli_fetch_array($sql_result, MYSQLI_ASSOC)) {
    								$sql = "update combatEnemies c, monsterMods m set c.prefix = '', c.name = concat_ws(' ', m.openTag, m.prefix, c.name, m.suffix, m.closeTag)";
    								$sql.= ", hit = hit * hitMod, flee = flee * fleeMod, health = health * healthMod, attack = attack * dmgMod, exp = exp * expMod, silver = silver * dropMod where playerid = $acc and modID = " . $row["modID"];
    								sql_query($sql, $conn);
    							}
    						}
    						else
    						if ($roll < 1500) {
    							$sql = "SELECT * FROM monsterModLookup where enemyID in (-1 , " . $row['enemyID'] . ") and chance = 15 ORDER BY RAND() LIMIT 1";
    							$sql_result = sql_query($sql, $conn);
    							while ($row = mysqli_fetch_array($sql_result, MYSQLI_ASSOC)) {
    								$sql = "update combatEnemies c, monsterMods m set c.name = concat_ws(' ', m.openTag, m.prefix, c.name, m.suffix, m.closeTag)";
    								$sql.= ", hit = hit * hitMod, flee = flee * fleeMod, health = health * healthMod, attack = attack * dmgMod, exp = exp * expMod, silver = silver * dropMod where playerid = $acc and modID = " . $row["modID"];
    								sql_query($sql, $conn);
    							}
    						}

    						$initCombat = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) select playerid, enemyid, 'CENTER', concat_ws('', '<strong>You have encountered ', prefix, name, '</strong><br/>') from \"combatenemies\" where playerid = $acc";
    						sql_query($initCombat, $conn);
    						return 5;
    					}
    				}
    			}
    		}
    	}
    }

    // -- Function Name : combatLoot
    // -- Params : $conn, $acc, $row
    // -- Purpose : Updates kill count, and displays end of combat text

    function combatLoot($conn, $acc, $row)
    {
        if(!isset($row)){
            return;
        }
    	$map = getAttribute($conn, "character", "map", $acc);
    	$level = $row['level'];
    	$calcValues = getRow($conn, "calcValues", $acc);
    	$enemyID = $row['enemyID'];
    	if ($enemyID == 150) {
    		$sql = "update \"character\" set towerBoss = currentTowerLevel where playerid = $acc";
    		sql_query($sql, $conn);
    	}

    	$name = $row['prefix'] . ' ' . $row['name'];
    	$shortName = $row['name'];
    	$exp = floor($row['exp'] * (($calcValues['expDrop'] / 100) + 1));
    	$silver = $row['silver'] * (($calcValues['silverDrop'] / 100) + 1);
    	$variance = floor($silver / 10);
    	$silver = rand($silver - $variance, $silver + $variance);
        if(in_array($enemyid, [147,148,149,150])){
            $exp = floor($exp * (($calcValues['shapelessExpDrop'] / 100) + 1));
        }
    	sql_query("UPDATE charKills set "count" = "count" + 1 WHERE playerID = $acc AND enemyID = $enemyID", $conn);
    	if (mysqli_affected_rows($conn) == 0) {
    		$sql = 'INSERT INTO charkills (playerID, enemyID) VALUES (' . $acc . ', ' . $enemyID . ')';
    		sql_query($sql, $conn);
    	}

    	$sql = "select count from charKills where enemyID = $enemyID and playerID = $acc";
    	$sql_result = sql_query($sql, $conn);
    	$killsRow = mysqli_fetch_array($sql_result, MYSQLI_ASSOC);
    	$kills = $killsRow["count"];
    	$dropMod = 1;
    	if ($kills > 750) {
    		$exp = floor($exp * 1.2);
    		$dropMod = 1.2;
    	}
    	else
    	if ($kills > 500) {
    		$dropMod = 1.2;
    	}
    	logAction($conn, $acc, 'kill', $name, NULL);
    	$insertRow = "$acc, $enemyid, 'CENTER', '<strong>You have defeated $name!</strong>', '#E00101', ' '";
    	$insertRow2 = "$acc, $enemyid, 'CENTER', 'You have gained " . number_format($exp) . " experience!', 'rgb(242,130,31)', '<strong>" . number_format($exp) . "</strong>'";
    	$insertRow3 = "$acc, $enemyid, 'LEFT', '<br/>', '#E00101', ' '";
    	$insertRow4 = "$acc, $enemyid, 'CENTER', '" . number_format($silver) . " Silver dropped to the ground!', 'SILVER', '<strong>" . number_format($silver) . "</strong>'";
    	$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values ($insertRow), ($insertRow2), ($insertRow3), ($insertRow4)";
    	sql_query($logMessege, $conn);
    	$levelup = $calcValues['next'];
    	$current = $calcValues['exp'];
    	$level = $calcValues['level'] + 1;
    	$diff = $current + $exp - $levelup;
    	if ($current + $exp + 1 > $levelup) {
    		if ($level == 50) {
    			$sql = "UPDATE \"equipmentInventory\" SET \"archived\" = 0 WHERE playerID = $acc";
    			sql_query($sql, $conn);
    			$sql = "UPDATE \"inventory\" SET \"count\" = \"count\" + \"archived\" WHERE playerID = $acc";
    			sql_query($sql, $conn);
    			$sql = "UPDATE \"inventory\" SET \"archived\" = 0 WHERE playerID = $acc";
    			sql_query($sql, $conn);
    		}

    		levelUp($acc, $diff, $conn, $enemyid, $level);
    	}
    	else {
    		sql_query("UPDATE \"character\" SET \"exp\" = \"exp\" + $exp WHERE playerID = $acc", $conn);
    	}

    	// quest drops

    	$sql = 'SELECT * FROM "questDrops" WHERE  "monsterID" = -1 or "monsterID" = ' . $enemyID;
    	$sql_drops = sql_query($sql, $conn);
    	while ($dropRow = mysqli_fetch_array($sql_drops, MYSQLI_ASSOC)) {
    		$quest = $dropRow['questID'];
    		$item = $dropRow['dropID'];
    		$sql = "SELECT * FROM \"questPlayerStatus\" WHERE \"playerid\" = " . $acc . " AND \"questID\" = " . $quest;
    		$Row = mysqli_fetch_array(sql_query($sql, $conn) , MYSQLI_ASSOC);
    		$status = $Row['status'];
    		$reqAmnt = 0;
    		if ($status == "working") {
    			$questRow = getRow($conn, "quests", $quest);
    			if ($questRow['req1'] == $item) {
    				$reqAmnt = $questRow['req1amnt'];
    			}
    			else
    			if ($questRow['req2'] == $item) {
    				$reqAmnt = $questRow['req2amnt'];
    			}
    			else
    			if ($questRow['req3'] == $item) {
    				$reqAmnt = $questRow['req3amnt'];
    			}

    			if ($reqAmnt != getInventoryItem($conn, $acc, $item)) {
    				$odds = rand(0, 10000);
    				if ($quest == 27 || $quest == 28 || $quest == 29 || $quest == 30 || $quest == 31) { //DAILY
    					$pos1 = strpos($row["name"], "#AD8247");
    					$pos2 = strpos($row["name"], "#008B8B");
    					if ($pos1 === false && $pos2 === false && $dropRow['dropID'] == 123) {
    						$color = '';
    						$currentItemAmnt = getInventoryItem($conn, $acc, $item);
    						if ($reqAmnt != $currentItemAmnt + 1) {
    							$color = "red";
    						}
    						else {
    							$color = "green";
    						}

    						$message = "Common Enemies Slain";
    						$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','" . $message . "', '" . $color . "', '<strong>" . (getInventoryItem($conn, $acc, $item) + 1) . "/" . $reqAmnt . "</strong>')";
    						sql_query($logMessege, $conn);
    						addItemAmount($conn, $acc, $dropRow['dropID'], 1);
    					}
    					else
    					if ($pos2 > 0 && $dropRow['dropID'] == 124) {
    						$color = '';
    						$currentItemAmnt = getInventoryItem($conn, $acc, $item);
    						if ($reqAmnt != $currentItemAmnt + 1) {
    							$color = "red";
    						}
    						else {
    							$color = "green";
    						}

    						$message = "Magic Enemies Slain";
    						$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','" . $message . "', '" . $color . "', '<strong>" . (getInventoryItem($conn, $acc, $item) + 1) . "/" . $reqAmnt . "</strong>')";
    						sql_query($logMessege, $conn);
    						addItemAmount($conn, $acc, $dropRow['dropID'], 1);
    					}
    					else
    					if ($pos1 > 0 && $dropRow['dropID'] == 125) {
    						$color = '';
    						$currentItemAmnt = getInventoryItem($conn, $acc, $item);
    						if ($reqAmnt != $currentItemAmnt + 1) {
    							$color = "red";
    						}
    						else {
    							$color = "green";
    						}

    						$message = "Rare Enemies Slain";
    						$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','" . $message . "', '" . $color . "', '<strong>" . (getInventoryItem($conn, $acc, $item) + 1) . "/" . $reqAmnt . "</strong>')";
    						sql_query($logMessege, $conn);
    						addItemAmount($conn, $acc, $dropRow['dropID'], 1);
    					}
    				}
    				else { //NOT DAILY
    					$dropRate = ($dropRow['dropRate'] * $dropMod) * (($calcValues['itemDrop'] / 100) + 1);
    					if ($dropRate > $odds) {
    						$questItemRow = getRow($conn, "item", $dropRow['dropID']);
    						$itemName = $questItemRow['name'];
    						$loot = $questItemRow['visible'];
    						$color = '';
    						$currentItemAmnt = getInventoryItem($conn, $acc, $item);
    						if ($reqAmnt != $currentItemAmnt + 1) {
    							$color = "red";
    						}
    						else {
    							$color = "green";
    						}

    						if ($loot == 1) {
    							$message = $shortName . " Dropped: " . $itemName;
    						}
    						else {
    							$message = $itemName . " Slain";
    						}

    						$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','" . $message . "', '" . $color . "', '<strong>" . (getInventoryItem($conn, $acc, $item) + 1) . "/" . $reqAmnt . "</strong>')";
    						sql_query($logMessege, $conn);
    						addItemAmount($conn, $acc, $dropRow['dropID'], 1);
    					}
    				}
    			}
    		}
    	}

    	$enemyID = $row['enemyID'];
    	$name = $row['name'];
    	$sql = "SELECT * FROM \"drops\" WHERE \"enemyid\" = $enemyID OR \"enemyid\" = -1";
    	$sql_drops = sql_query($sql, $conn);
    	while ($dropRow = mysqli_fetch_array($sql_drops, MYSQLI_ASSOC)) {
    		$odds = rand(0, 10000);
    		$dropRate = ($dropRow['dropRate'] * $dropMod) * (($calcValues['itemDrop'] / 100) + 1);
    		if ($dropRate > $odds) {
    			$itemName = getAttribute($conn, "item", "name", $dropRow['itemID']);
    			$message = $name . " Dropped: " . $itemName;
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','" . $message . "', 'WHITE', '<strong></strong>')";
    			sql_query($logMessege, $conn);
    			addItemAmount($conn, $acc, $dropRow['itemID'], 1);
    		}
    	}

    	$enemyID = $row['enemyID'];
    	$name = $row['name'];
    	$sql = 'SELECT * FROM \"equipmentDrops\" WHERE \"enemyid\" = ' . $enemyID;
    	$sql_drops = sql_query($sql, $conn);
    	while ($dropRow = mysqli_fetch_array($sql_drops, MYSQLI_ASSOC)) {
    		$odds = rand(0, 10000);
    		$dropRate = ($dropRow['rate'] * $dropMod) * (($calcValues['itemDrop'] / 100) + 1);
    		if ($dropRate > $odds) {
    			$itemName = generateItem($conn, $acc, $dropRow['dropID'], null, null);
    			$message = $name . " Dropped: " . $itemName;
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','" . $message . "', 'WHITE', '<strong></strong>')";
    			sql_query($logMessege, $conn);
    		}
    	}

    	$sql = "select sl.chance as chance from playerBuffs pb
            inner join skillLevels sl on pb.buffID = sl.skillID
            inner join charSkills cs on cs.playerID = pb.playerID
            where pb.playerID = $acc and sl.level = cs.level and cs.skillID = 34 and sl.skillID = 34";
    	$query = sql_query($sql, $conn);
    	while ($skillRow = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
    		if (intval(rand(0, 100)) < intval(isset($skillRow['chance']) ? $skillRow['chance'] : 0)) {
    			$enemyLevel = getAttribute($conn, "enemies", "level", $enemyID);
    			if ($enemyLevel > 62) {
    				$enemyLevel = 62;
    			}

    			$sql = "select itemID, name from findPotion fp inner join item i on fp.itemID = i.item_id where fp.enemyLevel = $enemyLevel ORDER BY RAND()";
    			$query = sql_query($sql, $conn);
    			$itemRow = mysqli_fetch_array($query, MYSQLI_ASSOC);
    			$message = "You found 1 " . $itemRow['name'] . " on the ground!";
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','" . $message . "', 'WHITE', '<strong></strong>')";
    			sql_query($logMessege, $conn);
    			addItemAmount($conn, $acc, $itemRow['itemID'], 1);
    		}
    	}

    	$sql = "UPDATE \"character\" SET \"silver\" = \"silver\" + $silver, \"combatModifier\" = 0 WHERE playerID = $acc";
    	sql_query($sql, $conn);
    	sql_query("DELETE FROM \"enemySkillCooldown\" WHERE \"playerid\" = " . $acc, $conn);
    }

    // -- Function Name : enemyAttack
    // -- Params : $acc, $conn
    // -- Purpose : Attack of current enemy

    function enemyAttack($acc, $conn, $calcValues)
    {
    	$sql_char_result = sql_query("SELECT * FROM combatEnemies c where c.playerID = $acc ORDER BY \"combatEnemyID\" DESC LIMIT 1", $conn);
    	$row = mysqli_fetch_array($sql_char_result, MYSQLI_ASSOC);
    	$HP = $row['health'];
    	$enemyID = $row['enemyID'];

        $health = getAttribute($conn, "character", "hitpoints", $acc);
        if ($health < 1) {
            $logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','<strong>You have died!</strong>', '#E00101', ' ')";
            sql_query($logMessege, $conn);
            return 3;
        }

    	$hit = $row['hit'];
    	$levelCheck = $row['level'];
    	$monsterName = $row['prefix'];
    	$enemyAttack = $row['attack'];
    	$enemyMAttack = $row['mattack'];
    	if ($monsterName != "") {
    		$monsterName = "The ";
    	}

    	$sql_eff_result = sql_query("SELECT effectDecay FROM enemyEffects where playerid = $acc and effectType = 2", $conn);
    	if (mysqli_num_rows($sql_eff_result) > 0) {
    		$effrow = mysqli_fetch_array($sql_eff_result, MYSQLI_ASSOC);
    		$hit = $hit * (isset($effrow['effectDecay']) ? $effrow['effectDecay'] : 100) / 100;
    	}

    	$monsterName.= $row['name'];
    	if ($HP < 1) {
    		combatLoot($conn, $acc, $row);
    		return 1;
    	}

    	if (enemyEffects($conn, $acc, $monsterName, $enemyID) == 0) {
    		$row = $calcValues;
    		$vit = $row['vit'];
    		$armor = $row['armor'];
    		$flee = $row['flee'];
    		$maxMana = $row['maxMana'];
    		$equippedShield = getAttribute($conn, "equipmentBonus", "blockChance", $acc);
    		$blockRate = floor($row['block'] * 100);
    		if ($equippedShield != 0) {
    			$sql = "SELECT * FROM charSkills s inner join skillLevels l on s.skillID = l.skillID where playerid = $acc and l.level = s.level and s.skillID = 9";
    			$result = sql_query($sql, $conn);
    			$row2 = mysqli_fetch_array($result, MYSQLI_ASSOC);
    			$blockRate = $blockRate + $row2['damage'];
    		}

    		$min = ($enemyAttack * .8) - 3;
    		$max = ($enemyAttack * 1.2) + 3;
    		$mmin = ($enemyMAttack * .8) - 3;
    		$mmax = ($enemyMAttack * 1.2) + 3;
    		$yourLevel = getAttribute($conn, "character", "level", $acc);
    		$levelMod = $levelCheck - $yourLevel;
    		if ($levelMod < 0) {
    			$levelMod = 0;
    		}

    		$levelMod = ((pow($levelMod, 2) / 100) + 1);
    		$damage = (rand($min, $max)) * $levelMod;
    		$mDamage = (rand($mmin, $mmax)) * $levelMod;
    		$skillSQL = "select skillName, mskillID as skillid, damageType, text, damageModifier, s.cooldown as cooldown, element
        				from enemySkill s
        				inner join combatEnemies e on e.enemyID = s.enemyID
        				inner join monsterSkills m on mskillID = s.eSkillID
        				inner join strings st on st.forKey = mSkillID
        				where e.enemyID = $enemyID
        					and eSkillID not in (select distinct skillID from enemySkillCooldown where playerid = $acc)
        					and chance > FLOOR(RAND()*(100))
        					and maxHP >= CEIL($HP / maxhealth * 100)
        					and st.c1 ='enemySkill'
        					and e.playerID = $acc
        				order by rand() limit 1";
    		$skillRow = sql_query($skillSQL, $conn);
    		if (mysqli_num_rows($skillRow) > 0) {
    			$enemySkill = mysqli_fetch_array($skillRow, MYSQLI_ASSOC);
    			$enemySkillID = $enemySkill['skillid'];
    			$cooldown = $enemySkill['cooldown'];
    			sql_query("insert into enemySkillCooldown (enemyid, skillID, cooldown, playerID) values ($enemyid, $enemySkillID, $cooldown, $acc)", $conn);
    			$text = $enemySkill['text'];
    			$message = "<span style=\"color:#A5A5A5\">" . str_replace('[enemy]', $monsterName, $text) . "</span>";
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "')";
    			sql_query($logMessege, $conn);
    			if ($enemySkill['damageType'] == 'power up') {
    				$mod = $enemySkill['damageModifier'];
    				$sql = "update combatEnemies set attack = attack * $mod, hit = hit * $mod where playerid = $acc";
    				sql_query($sql, $conn);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'RIGHT','<span style=\"color:#A5A5A5\">...and it\'s attack and accuracy go up</span>', '#5B00FF', '<strong></strong>')";
    				sql_query($logMessege, $conn);
    			}
    			else
    			if ($enemySkill['damageType'] == 'heal') {
    				$damage = floor($mDamage * $enemySkill['damageModifier']);
    				$sql = "update combatEnemies set health = health + $damage where playerid = $acc";
    				sql_query($sql, $conn);
    				$sql = "update combatEnemies set health = maxhealth where playerid = $acc and health > maxhealth";
    				sql_query($sql, $conn);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','<span style=\"color:#A5A5A5\">...and heals itself for $damage health</span>', '#00ff76', '<strong>$damage</strong>')";
    				sql_query($logMessege, $conn);
    			}
    			else
    			if ($enemySkill['damageType'] == 'mattack') {
    				$damage = floor($mDamage * $enemySkill['damageModifier']);
    				$element = $enemySkill['element'];
    				$elementText = "";
    				if (strtolower($element) == 'fire') {
    					$elementText = "<span style=\'color:red\'> Fire</span>";
    				}
    				else
    				if (strtolower($element) == 'earth') {
    					$elementText = "<span style=\'color:brown\'> Earth</span>";
    				}
    				else
    				if (strtolower($element) == 'ice') {
    					$elementText = "<span style=\'color:Blue\'> Ice</span>";
    				}
    				else
    				if (strtolower($element) == 'arcane') {
    					$elementText = "<span style=\'color:Purple\'> Arcane</span>";
    				}
    				else
    				if (strtolower($element) == 'holy') {
    					$elementText = "<span style=\'color:White\'> Holy</span>";
    				}
                    $res = $calcValues[strtolower($element) . "Res"];
    				$mod = (100 - $res) / 100;
                    if(in_array($enemyid, [147,148,149,150])){
                        $damage = floor($damage * ((100 - $calcValues['shapelessRes']) / 100));
                    }
    				$damage = $damage * $mod - ($calcValues["spr"] / 2);
    				if ($damage < 1) {
    					$damage = 1;
    				}


    				$sql = "select * from playerBuffs where playerid = $acc and buffID = 22";
    				$query = sql_query($sql, $conn);
    				if (mysqli_num_rows($query) > 0) {
    					$currentMana = getAttribute($conn, "character", "mana", $acc);
    					if ($currentMana / $maxMana > .25) {
    						$text = getString($conn, "0", "combat", "right", "damp");
    						$damage = floor($damage * .75);
    						$message = "<span style=\"color:#A5A5A5\">" . str_replace('[damage]', number_format($damage) , $text) . "</span>";
    						$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $message . "', '#5B00FF', '<strong>" . number_format($damage) . "</strong>')";
    						sql_query($logMessege, $conn);
    						blazingAura($conn, $acc);
    						$sql = "UPDATE \"character\" SET \"mana\" = \"mana\" - " . $damage . " WHERE \"playerid\" = " . $acc;
    						sql_query($sql, $conn);
    						$sql = "UPDATE \"character\" SET \"mana\" = 0 WHERE \"mana\" < 0 and \"playerid\" = " . $acc;
    						sql_query($sql, $conn);
    						return;
    					}
    				}

    				$text = getString($conn, "0", "combat", "right", "");
    				$message = "<span style=\"color:#A5A5A5\">" . str_replace('[damage]', number_format($damage) . $elementText, $text) . "</span>";
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $message . "', '#E00101', '<strong>" . number_format($damage) . "</strong>')";
    				sql_query($logMessege, $conn);
    				blazingAura($conn, $acc);
    				$sql = "UPDATE \"character\" SET \"hitpoints\" = \"hitpoints\" - " . $damage . " WHERE \"playerid\" = " . $acc;
    				sql_query($sql, $conn);
    				$hp = getAttribute($conn, "character", "hitpoints", $acc);
    				if ($hp < 1) {
    					$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','<strong>You have died!</strong>', '#E00101', ' ')";
    					sql_query($logMessege, $conn);
    					return 3;
    				}
    			}
    			else
    			if ($enemySkill['damageType'] == 'attack') {
    				$damage = $damage * $enemySkill['damageModifier'];
    				$hard = ((100 - $armor) / 100);
    				$damage = $damage * $hard;
    				$damage = $damage - (ceil($vit / 2));
    				if ($damage < 1) {
    					$damage = 1;
    				}
                    if(in_array($enemyid, [147,148,149,150])){
                        $damage = floor($damage * ((100 - $calcValues['shapelessRes']) / 100));
                    }
    				$damage = ceil($damage);
    				if (attackHit($hit, $flee) == 0) {

    					// Dodge

    					$text = getString($conn, "0", "combat", "left", "miss");
    					$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#E00101', '<strong></strong>')";
    					sql_query($logMessege, $conn);
    					blazingAura($conn, $acc);
    					$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    					sql_query($logMessege, $conn);
    				}
    				else {
    					if ((rand(0, 100)) < $blockRate) {

    						// Block

    						$text = getString($conn, "0", "combat", "left", "block");
    						$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#E00101', '<strong></strong>')";
    						sql_query($logMessege, $conn);
    						$sql = "SELECT * FROM equipmentInventory where playerid = $acc AND equipped = 1 AND template = 54";
    						$query = sql_query($sql, $conn);
    						if (mysqli_num_rows($query) > 0) {
    							$health = floor($calcValues["maxhealth"]) / 20;
    							$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','And your Augmented Round Shield restores $health health!', '#00ff76', '<strong>$health</strong>')";
    							sql_query($logMessege, $conn);
    							heal($health, $acc, $conn);
    						}

    						blazingAura($conn, $acc);
    						$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    						sql_query($logMessege, $conn);
    					}
    					else {
    						$sql = "select * from playerBuffs where playerid = $acc and buffID = 22";
    						$query = sql_query($sql, $conn);
    						if (mysqli_num_rows($query) > 0) {
    							$currentMana = getAttribute($conn, "character", "mana", $acc);
    							if ($currentMana / $maxMana > .25) {
    								$text = getString($conn, "0", "combat", "right", "damp");
    								$damage = floor($damage * .75);
    								$message = "<span style=\"color:#A5A5A5\">" . str_replace('[damage]', number_format($damage) , $text) . "</span>";
    								$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $message . "', '#5B00FF', '<strong>" . number_format($damage) . "</strong>')";
    								sql_query($logMessege, $conn);
    								blazingAura($conn, $acc);
    								$sql = "UPDATE \"character\" SET \"mana\" = \"mana\" - " . $damage . " WHERE \"playerid\" = " . $acc;
    								sql_query($sql, $conn);
    								$sql = "UPDATE \"character\" SET \"mana\" = 0 WHERE \"mana\" < 0 and \"playerid\" = " . $acc;
    								sql_query($sql, $conn);
    								return;
    							}
    						}

    						$text = getString($conn, "0", "combat", "right", "");
    						$message = "<span style=\"color:#A5A5A5\">" . str_replace('[damage]', number_format($damage) , $text) . "</span>";
    						$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $message . "', '#E00101', '<strong>" . number_format($damage) . "</strong>')";
    						sql_query($logMessege, $conn);
    						blazingAura($conn, $acc);
    						$sql = "UPDATE \"character\" SET \"hitpoints\" = \"hitpoints\" - " . $damage . " WHERE \"playerid\" = " . $acc;
    						sql_query($sql, $conn);
    						$hp = getAttribute($conn, "character", "hitpoints", $acc);
    						if ($hp < 1) {
    							$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','<strong>You have died!</strong>', '#E00101', ' ')";
    							sql_query($logMessege, $conn);
    							return 3;
    						}
    					}
    				}
    			}
    		}
    		else {
    			$hard = ((100 - $armor) / 100);
    			$damage = $damage * $hard;
    			$damage = $damage - (ceil($vit / 2));
    			if ($damage < 1) {
    				$damage = 1;
    			}
                if(in_array($enemyid, [147,148,149,150])){
                    $damage = floor($damage * ((100 - $calcValues['shapelessRes']) / 100));
                }
    			$damage = ceil($damage);
    			$text = getString($conn, "0", "enemyAttack", "left", "");
    			$message = "<span style=\"color:#A5A5A5\">" . str_replace('[enemy]', $monsterName, $text) . "</span>";
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "')";
    			sql_query($logMessege, $conn);
    			if (attackHit($hit, $flee) == 0) {

    				// Dodge

    				$text = getString($conn, "0", "combat", "left", "miss");
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#E00101', '<strong></strong>')";
    				sql_query($logMessege, $conn);
    				blazingAura($conn, $acc);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    				sql_query($logMessege, $conn);
    			}
    			else {
    				if ((rand(0, 100)) < $blockRate) {

    					// Block

    					$text = getString($conn, "0", "combat", "left", "block");
    					$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#E00101', '<strong></strong>')";
    					sql_query($logMessege, $conn);
    					$sql = "SELECT * FROM equipmentInventory where playerid = $acc AND equipped = 1 AND template = 54";
    					$query = sql_query($sql, $conn);
    					if (mysqli_num_rows($query) > 0) {
    						$health = floor($calcValues["maxhealth"]) / 20;
    						$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','And your Augmented Round Shield restores $health health!', '#00ff76', '<strong>$health</strong>')";
    						sql_query($logMessege, $conn);
    						heal($health, $acc, $conn);
    					}

    					blazingAura($conn, $acc);
    					$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    					sql_query($logMessege, $conn);
    				}
    				else {
    					$sql = "select * from playerBuffs where playerid = $acc and buffID = 22";
    					$query = sql_query($sql, $conn);
    					if (mysqli_num_rows($query) > 0) {
    						$currentMana = getAttribute($conn, "character", "mana", $acc);
    						if ($currentMana / $maxMana > .25) {
    							$text = getString($conn, "0", "combat", "right", "damp");
    							$damage = floor($damage * .75);
    							$message = "<span style=\"color:#A5A5A5\">" . str_replace('[damage]', number_format($damage) , $text) . "</span>";
    							$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $message . "', '#5B00FF', '<strong>" . number_format($damage) . "</strong>')";
    							sql_query($logMessege, $conn);
    							blazingAura($conn, $acc);
    							$sql = "UPDATE \"character\" SET \"mana\" = \"mana\" - " . $damage . " WHERE \"playerid\" = " . $acc;
    							sql_query($sql, $conn);
    							$sql = "UPDATE \"character\" SET \"mana\" = 0 WHERE \"mana\" < 0 and \"playerid\" = " . $acc;
    							sql_query($sql, $conn);
    							return;
    						}
    					}

    					$text = getString($conn, "0", "combat", "right", "");
    					$message = "<span style=\"color:#A5A5A5\">" . str_replace('[damage]', number_format($damage) , $text) . "</span>";
    					$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $message . "', '#E00101', '<strong>" . number_format($damage) . "</strong>')";
    					sql_query($logMessege, $conn);
    					blazingAura($conn, $acc);
    					$sql = "UPDATE \"character\" SET \"hitpoints\" = \"hitpoints\" - " . $damage . " WHERE \"playerid\" = " . $acc;
    					sql_query($sql, $conn);
    					$hp = getAttribute($conn, "character", "hitpoints", $acc);
    					if ($hp < 1) {
    						$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','<strong>You have died!</strong>', '#E00101', ' ')";
    						sql_query($logMessege, $conn);
    						return 3;
    					}
    				}
    			}
    		}
    	}

    	if (enemyAfterEffects($conn, $acc, $monsterName, $enemyid, $calcValues) == - 1) {
    		$sql_char_result = sql_query("SELECT * FROM combatEnemies c where c.playerID = $acc ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    		$row = mysqli_fetch_array($sql_char_result, MYSQLI_ASSOC);
    		combatLoot($conn, $acc, $row);
    		return 1;
    	}

    	$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) values (" . $acc . "," . $enemyID . ",'CENTER','<br/>')";
    	sql_query($logMessege, $conn);
    }

    function enemyEffects($conn, $acc, $monsterName, $enemyID)
    {
    	$return = 0;
    	$getEffect = sql_query("SELECT * FROM enemyEffects where playerid = $acc", $conn);
    	while ($row = mysqli_fetch_array($getEffect, MYSQLI_ASSOC)) {
    		$renew = $row["effectRenew"] - 100;
    		$expire = false;
    		$ID = $row["effectID"];
    		if ($renew < 1) {
    			$sql = "DELETE FROM enemyEffects where effectID = $ID";
    			$expire = true;
    		}
    		else {
    			$sql = "UPDATE enemyEffects SET effectRenew = effectRenew - 100 where effectID = $ID";
    		}

    		sql_query($sql, $conn);
    		if ($row["effectName"] == 'Entangle') {
    			if ($expire) {
    				$text = getString($conn, "23", "skill", "free", "");
    				$message = "<span style=\"color:#A5A5A5\">" . str_replace('[enemy]', $monsterName, $text) . "</span>";
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "')";
    				sql_query($logMessege, $conn);
    			}
    			else {
    				$text = getString($conn, "23", "skill", "stuck", "");
    				$message = "<span style=\"color:#A5A5A5\">" . str_replace('[enemy]', $monsterName, $text) . "</span>";
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "')";
    				sql_query($logMessege, $conn);
    			}

    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    			sql_query($logMessege, $conn);
    			$return = 1;
    		}
    	}

    	return $return;
    }

    function enemyAfterEffects($conn, $acc, $monsterName, $enemyid, $calcValues)
    {
    	sql_query("update enemySkillCooldown set cooldown = cooldown - 1 where playerid = $acc", $conn);
    	sql_query("delete from enemySkillCooldown where cooldown = 0", $conn);
    	$return = 0;
    	$getEffect = sql_query("SELECT * FROM enemyEffects where playerid = $acc", $conn);
    	while ($row = mysqli_fetch_array($getEffect, MYSQLI_ASSOC)) {
    		if ($row["effectType"] == 3) {
    			$getHP = sql_query("select FLOOR(health * .15) as health from combatenemies where playerid = $acc order by combatEnemyID desc limit 1", $conn);
    			$HPRow = mysqli_fetch_array($getHP, MYSQLI_ASSOC);
    			$damage = $HPRow["health"];
    			$sql_get_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    			$row = mysqli_fetch_array($sql_get_enemy, MYSQLI_ASSOC);
    			doDamage($conn, $acc, $damage, $row);
    			$message = "<span style=\"color:#A5A5A5\">$monsterName takes $damage poison damage.</span>";
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>$damage</strong>')";
    			sql_query($logMessege, $conn);
    			$return = 1;
    		}
    	}

    	$sql = "select sl.chance as chance from skillLevels sl inner join charSkills cs on cs.skillID = sl.skillID where cs.playerID = $acc and sl.level = cs.level and sl.skillID = 38";
    	$sql_rows = sql_query($sql, $conn);
    	if (mysqli_num_rows($sql_rows) > 0) {
    		$chanceRow = mysqli_fetch_array($sql_rows, MYSQLI_ASSOC);
    		if (rand(0, 100) < $chanceRow["chance"]) {
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    			sql_query($logMessege, $conn);
    			$row = getRow($conn, "character", $acc);
    			$charStats = getRow($conn, "calcValues", $acc);
    			$hit = $charStats['hit'];
    			if ($row['hitpoints'] < 1) {
    				return 3;
    			}

    			$sql = "SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY "combatEnemyID" DESC LIMIT 1";
    			$sql_get_enemy = sql_query($sql, $conn);
    			$enemyRow = mysqli_fetch_array($sql_get_enemy, MYSQLI_ASSOC);
    			$enemyID = $enemyRow['enemyID'];
    			$enemyHP = $enemyRow['health'];
    			$monsterName = $enemyRow['prefix'];
    			$element = $charStats['weapElement'];
    			$elementText = "";
    			if ($element == 'Fire') {
    				$elementText = "<span style=\'color:red\'> Fire</span>";
    			}
    			else
    			if ($element == 'Earth') {
    				$elementText = "<span style=\'color:brown\'> Earth</span>";
    			}
    			else
    			if ($element == 'Ice') {
    				$elementText = "<span style=\'color:Blue\'> Ice</span>";
    			}
    			else
    			if ($element == 'Arcane') {
    				$elementText = "<span style=\'color:Purple\'> Ice</span>";
    			}

    			$flee = $enemyRow['flee'];
    			if ($monsterName != "") {
    				$monsterName = "the ";
    			}

    			$levelMod = $enemyRow['level'] - $row["level"];
    			if ($levelMod < 0) {
    				$levelMod = 0;
    			}

    			$levelMod = pow($levelMod, 2) / 2;
    			$flee = $flee + $levelMod;
    			$monsterName.= $enemyRow['name'];
    			$sql = "SELECT \"class\", \"name\", \"template\" FROM "equipmentInventory" WHERE \"name\" != 'unarmed' AND \"slot\" = 'weapon' AND \"equipped\" = 1 AND \"playerid\" = $acc LIMIT 1";
    			$row = mysqli_fetch_array(sql_query($sql, $conn) , MYSQLI_ASSOC);
    			$itemClass = $row['class'];
    			$text = getString($conn, "0", "counter", "left", $itemClass);
    			if ($itemClass != "") {
    				$text = str_replace('[weapon]', strtolower($row['name']) , $text);
    			}

    			$message = str_replace('[enemy]', $monsterName, $text);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $message . "')";
    			sql_query($logMessege, $conn);
    			if (($charStats['critRate'] * 100) > rand(0, 100)) {

    				// CRITICAL HIT

    				$damage = getCrit($conn, $acc);
    				doDamage($conn, $acc, $damage, $enemyRow);
    				$text = getString($conn, "0", "combat", "right", "");
    				$damageDisplay = number_format($damage);
    				$message = str_replace('[damage]', $damageDisplay . $elementText, $text);
    				$message = "<strong>" . $message . "!!</strong>";
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $damage . "!!</strong>')";
    				sql_query($logMessege, $conn);
    				dblAttack($acc, $conn, $damage, $enemyRow, $elementText, $enemyid, $hit, $flee, $itemClass);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    				sql_query($logMessege, $conn);
    			}
    			else {
    				if (attackHit($hit, $flee) == 0) {
    					$text = getString($conn, "0", "combat", "right", "miss");
    					$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $text . "', '#E00101', '<strong></strong>')";
    					sql_query($logMessege, $conn);
    					$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    					sql_query($logMessege, $conn);
    				}
    				else {
    					$damage = getDamage($conn, $acc, $charStats);
    					$damage = ceil(resistDamage($conn, $acc, $damage, isset($element) ? $element : 'physical'));
    					doDamage($conn, $acc, $damage, $enemyRow);
    					$text = getString($conn, "0", "combat", "right", "");
    					$damageDisplay = number_format($damage);
    					$message = str_replace('[damage]', $damageDisplay . $elementText, $text);
    					$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $damage . "</strong>')";
    					sql_query($logMessege, $conn);
    					dblAttack($acc, $conn, $damage, $enemyRow, $elementText, $enemyid, $hit, $flee, $itemClass);
    					$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    					sql_query($logMessege, $conn);
    				}
    			}

    			$sql_char_result = sql_query("SELECT * FROM combatEnemies c where c.playerID = $acc ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    			$row = mysqli_fetch_array($sql_char_result, MYSQLI_ASSOC);
    			$HP = $row['health'];
    			if ($HP < 1) {
    				combatLoot($conn, $acc, $row);
    				return -1;
    			}
    		}
    	}

    	return $return;
    }

    function blazingAura($conn, $acc)
    {
    	$sql = "select damage from \"character\" c inner join playerBuffs b on c.playerID = b.playerID inner join charSkills s on s.playerID = b.playerID inner join skillLevels l on l.\"level\" = s.\"level\" where c.playerID = $acc and l.skillID = 20 and s.skillID = 20 and b.buffID = 20";
    	$sql_rows = sql_query($sql, $conn);
    	if (mysqli_num_rows($sql_rows) > 0) {
    		$row = mysqli_fetch_array($sql_rows, MYSQLI_ASSOC);
    		$effect = $row['damage'];
    		$min = explode('-', $effect) [0];
    		$max = explode('-', $effect) [1];
    		$damage = ceil(rand($min, $max) * (getAttribute($conn, "calcValues", "spr", $acc) / 100 + 1));
    		$damage = ceil(resistDamage($conn, $acc, $damage, isset($element) ? $element : 'physical'));
    		$sql_get_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    		$row = mysqli_fetch_array($sql_get_enemy, MYSQLI_ASSOC);
    		$enemyID = $row['enemyID'];
    		doDamage($conn, $acc, $damage, $row);
    		$text = "Your Burning Aura burns [enemy] for [damage]<strong><span style=color:red> Fire</span></strong> damage";
    		$monsterName = $row['prefix'];
    		if ($monsterName != "") {
    			$monsterName = "the ";
    		}

    		$monsterName.= $row['name'];
    		$text = str_replace('[enemy]', $monsterName, $text);
    		$message = str_replace('[damage]', $damage . (isset($elementText) ? $elementText : '') , $text);
    		$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $damage . "</strong>')";
    		sql_query($logMessege, $conn);
    	}

    	$sql = "select * from playerBuffs where playerid = $acc and buffID = -20";
    	$sql_rows = sql_query($sql, $conn);
    	if (mysqli_num_rows($sql_rows) > 0) {
    		$row = mysqli_fetch_array($sql_rows, MYSQLI_ASSOC);
    		$min = 8;
    		$max = 11;
    		$damage = ceil(rand($min, $max) * (getAttribute($conn, "calcValues", "spr", $acc) / 100 + 1));
    		$damage = ceil(resistDamage($conn, $acc, $damage, isset($element) ? $element : 'physical'));
    		$sql_get_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    		$row = mysqli_fetch_array($sql_get_enemy, MYSQLI_ASSOC);
    		$enemyID = $row['enemyID'];
    		doDamage($conn, $acc, $damage, $row);
    		$text = "Your Tome of the Elements burns [enemy] for [damage]<strong><span style=color:red> Fire</span></strong> damage";
    		$monsterName = $row['prefix'];
    		if ($monsterName != "") {
    			$monsterName = "the ";
    		}

    		$monsterName.= $row['name'];
    		$text = str_replace('[enemy]', $monsterName, $text);
    		$message = str_replace('[damage]', $damage . $elementText, $text);
    		$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $damage . "</strong>')";
    		sql_query($logMessege, $conn);
    	}
    }

    // -- Function Name : combatDeath
    // -- Params : $conn, $acc
    // -- Purpose : Kills char and unloads combat

    function combatDeath($conn, $acc)
    {
    	toSpawn($acc, $conn);
    	sql_query("DELETE FROM "combat" WHERE "playerid" = " . $acc, $conn);
    	sql_query("DELETE FROM "enemyEffects" WHERE "playerid" = " . $acc, $conn);
    	sql_query("DELETE FROM "combatEnemies" WHERE "playerid" = " . $acc, $conn);
    	sql_query("DELETE FROM "enemySkillCooldown" WHERE "playerid" = " . $acc, $conn);
    	$sql = "UPDATE \"character\" set currentTowerLevel = 1 WHERE \"playerid\" = " . $acc;
    	sql_query($sql, $conn);
    	$HP = (getAttribute($conn, "calcValues", "maxhealth", $acc) / 2);
    	$sql = "SELECT * FROM "playerBuffs" where itemID = 116 AND playerID = $acc";
    	$query = sql_query($sql, $conn);
    	logAction($conn, $acc, 'death', NULL, NULL);
    	if (mysqli_num_rows($query) > 0) {
    		$sql = "DELETE FROM "playerBuffs" where itemID = 116 AND playerID = $acc";
    		$query = sql_query($sql, $conn);
    		sql_query("UPDATE "character" set "hitpoints" = " . $HP . " WHERE "playerid" = " . $acc, $conn);
    		sql_query("UPDATE account set "deaths" = "deaths" + 1 WHERE "playerid" = " . $acc, $conn);
    	}
    	else {
    		$sql = "SELECT * FROM "equipmentInventory" where \"template\" in (93, 102, 87) AND \"equipped\" = 1 AND \"playerid\" = $acc LIMIT 1";
    		$query = sql_query($sql, $conn);
    		if (mysqli_num_rows($query) == 1) {


                sql_query("UPDATE "character" set "hitpoints" = " . $HP . " WHERE "playerid" = " . $acc, $conn);
    			sql_query("UPDATE account set "deaths" = "deaths" + 1 WHERE "playerid" = " . $acc, $conn);
    			sql_query("DELETE FROM "playerBuffs" where "remaining" > 0 AND "playerid" = $acc and itemID != -2", $conn);

            }else{

                $sql = "SELECT * FROM "equipmentInventory" where \"template\" in (56, 63, 72) AND \"equipped\" = 1 AND \"playerid\" = $acc LIMIT 1";
                $query = sql_query($sql, $conn);
                if (mysqli_num_rows($query) == 1) {
                    $maxXP = getAttribute($conn, "character", "next", $acc);
                    $XP = getAttribute($conn, "character", "exp", $acc);
                    $loss = floor($maxXP / 20);
                    $XP = $XP - $loss;
                    if ($XP < 1) {
                        $XP = 0;
                    }

                    sql_query("UPDATE "character" set "hitpoints" = " . $HP . ", exp = $XP WHERE "playerid" = " . $acc, $conn);
                    sql_query("UPDATE account set "deaths" = "deaths" + 1 WHERE "playerid" = " . $acc, $conn);
                    sql_query("DELETE FROM "playerBuffs" where "remaining" > 0 AND "playerid" = $acc and itemID != -2", $conn);

                } else {

                    $maxXP = getAttribute($conn, "character", "next", $acc);
        			$XP = getAttribute($conn, "character", "exp", $acc);
        			$loss = floor($maxXP / 10);
        			$XP = $XP - $loss;
        			if ($XP < 1) {
        				$XP = 0;
        			}

        			sql_query("UPDATE "character" set "hitpoints" = " . $HP . ", exp = $XP WHERE "playerid" = " . $acc, $conn);
        			sql_query("UPDATE account set "deaths" = "deaths" + 1 WHERE "playerid" = " . $acc, $conn);
        			sql_query("DELETE FROM "playerBuffs" where "remaining" > 0 AND "playerid" = $acc and itemID != -2", $conn);

                }
            }
    	}
    }

    // 93, 102, 110  56, 63, 72

    // -- Function Name : useSkill
    // -- Params : $conn, $skillID, $acc
    // -- Purpose : Determines if in combat, and uses a skill accordingly

    function useSkill($conn, $skillID, $acc, $times, $calcValues)
    {
    	$spells = array(
    		4,
    		5,
    		7,
    		11,
    		17,
    		21,
    		25,
    		23
    	); //spells.. for mana/damage bonus stuff
    	if (in_array($skillID, $spells)) {
    		$sql = "select *, floor(l.cost -  l.cost * (cv.spellReduction / 100)) as mana, l.damage as skillDamage from \"character\" c ";
    		$sql.= "inner join charSkills cs on cs.playerID = c.playerID ";
    		$sql.= "inner join skillLevels l on l.skillID = cs.skillID ";
    		$sql.= "inner join skills s on l.skillID = s.\"id\" ";
    		$sql.= "inner join calcValues cv on cv.playerID = cs.playerID ";
    		$sql.= "where c.playerID = $acc ";
    		$sql.= "and l.skillID = $skillID ";
    		$sql.= "and c.mana >= floor( l.cost * cv.spellReduction / 100) ";
    		$sql.= "and l.level = cs.level";
    	}
    	else {
    		$sql = "select *, l.cost as mana, l.damage as skillDamage from \"character\" c ";
    		$sql.= "inner join charSkills cs on cs.playerID = c.playerID ";
    		$sql.= "inner join skillLevels l on l.skillID = cs.skillID ";
    		$sql.= "inner join skills s on l.skillID = s.\"id\" ";
    		$sql.= "where c.playerID = $acc and l.skillID = $skillID and c.mana >= l.cost and l.level = cs.level";
    	}

    	$rowset = sql_query($sql, $conn);
    	if (mysqli_num_rows($rowset) == 0) {
    		return "<span style='color:red;'>Skill Failed, Not Enough Mana!</span>";
    	}

    	$skillRow = mysqli_fetch_array($rowset);
    	$image = $skillRow['image'];
    	$effect = $skillRow['skillDamage'];
    	$element = isset($skillRow['weapElement']) ? $skillRow['weapElement'] : '';
    	$duration = $skillRow['duration'];
    	$chance = $skillRow['chance'];
    	$spellElement = $skillRow['element'];
    	$elementText = "";
    	if ($spellElement == 'Fire' || $spellElement == 'fire') {
    		$elementText = "<strong><span style=\'color:red\'> Fire</span></strong>";
    	}
    	else
    	if ($spellElement == 'Earth') {
    		$elementText = "<strong><span style=\'color:brown\'> Earth</span></strong>";
    	}
    	else
    	if ($spellElement == 'Ice') {
    		$elementText = "<strong><span style=\'color:Blue\'> Ice</span></strong>";
    	}

    	$sql = "UPDATE \"character\" set \"mana\" = \"mana\" - " . ($skillRow['mana'] * $times) . " where playerid = " . $acc;
    	sql_query($sql, $conn);
    	if (isInCombat($conn, $acc)) {
    		$sql_get_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = $acc ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    		$row = mysqli_fetch_array($sql_get_enemy, MYSQLI_ASSOC);
    		$enemyID = $row['enemyID'];
    		$enemyHP = $row['health'];
    		if ($skillID == 5) {

    			// heal

    			$min = explode('-', $effect) [0];
    			$max = explode('-', $effect) [1];
    			$heal = ceil(rand($min, $max) * ($calcValues["spr"] / 40 + 1));
    			heal($heal, $acc, $conn);
    			$text = "You cast heal on yourself...";
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#5B00FF', '<strong>" . $skillRow['mana'] . "</strong>')";
    			sql_query($logMessege, $conn);
    			$text = "...and recover $heal health!";
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#00ff76', '<strong>" . $heal . "</strong>')";
    			sql_query($logMessege, $conn);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    			sql_query($logMessege, $conn);
    		}
    		else
    		if ($skillID == 2) {

    			// bash

    			$charStats = $calcValues;
    			$hit = $charStats['hit'];
    			$element = $charStats['weapElement'];
    			$sql_get_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    			$enemyRow = mysqli_fetch_array($sql_get_enemy, MYSQLI_ASSOC);
    			$enemyID = $enemyRow['enemyID'];
    			$monsterName = $enemyRow['prefix'];
    			$flee = $enemyRow['flee'];
    			if ($monsterName != "") {
    				$monsterName = "the ";
    			}

    			if (attackHit(($hit + 10) , $flee) == 0) {
    				$text = getString($conn, $skillID, "skill", "left", "");
    				$monsterName.= $row['name'];
    				$text = str_replace('[enemy]', $monsterName, $text);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#5B00FF', '<strong>" . $skillRow['mana'] . "</strong>')";
    				sql_query($logMessege, $conn);
    				$text = getString($conn, 0, "combat", "right", "miss");
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $text . "', '#E00101', '<strong></strong>')";
    				sql_query($logMessege, $conn);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    				sql_query($logMessege, $conn);
    			}
    			else {
    				$damage = ceil(resistDamage($conn, $acc, floor(getDamage($conn, $acc, $calcValues) * $skillRow['damage']) , isset($element) ? $element : 'physical'));
    				$sql = "SELECT * FROM "equipmentInventory" where \"template\" = 51 AND \"equipped\" = 1 AND \"playerid\" = $acc LIMIT 1";
    				$query = sql_query($sql, $conn);
    				if (mysqli_num_rows($query) == 1) {
    					$damage = floor($damage * 1.75);
    				}
    				else {
    					$sql = "SELECT * FROM "equipmentInventory" where \"template\" = 83 AND \"equipped\" = 1 AND \"playerid\" = $acc LIMIT 1";
    					$query = sql_query($sql, $conn);
    					if (mysqli_num_rows($query) == 1) {
    						$damage = floor($damage * 2.25);
    					}
    				}

    				doDamage($conn, $acc, $damage, $row);
    				$damage = number_format($damage);
    				$text = getString($conn, $skillID, "skill", "left", "");
    				$monsterName.= $row['name'];
    				$text = str_replace('[enemy]', $monsterName, $text);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#5B00FF', '<strong>" . $skillRow['mana'] . "</strong>')";
    				sql_query($logMessege, $conn);
    				$text = getString($conn, $skillID . " OR forKey = 0", "combat", "right", "");
    				$message = str_replace('[damage]', number_format($damage) , $text);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $damage . "</strong>')";
    				sql_query($logMessege, $conn);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    				sql_query($logMessege, $conn);
    			}
    		}
    		else
    		if ($skillID == 26) {

    			// overload

    			$charStats = $calcValues;
    			$hit = $charStats['hit'];
    			$sql_get_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    			$enemyRow = mysqli_fetch_array($sql_get_enemy, MYSQLI_ASSOC);
    			$enemyID = $enemyRow['enemyID'];
    			$monsterName = $enemyRow['prefix'];
    			$flee = $enemyRow['flee'];
    			if ($monsterName != "") {
    				$monsterName = "the ";
    			}

    			if (attackHit(($hit + 100) , $flee) == 0) {
    				$text = getString($conn, $skillID, "skill", "left", "");
    				$monsterName.= $row['name'];
    				$text = str_replace('[enemy]', $monsterName, $text);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#5B00FF', '<strong>" . $skillRow['mana'] . "</strong>')";
    				sql_query($logMessege, $conn);
    				$text = getString($conn, 0, "combat", "right", "miss");
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $text . "', '#E00101', '<strong>" . $damage . "</strong>')";
    				sql_query($logMessege, $conn);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    				sql_query($logMessege, $conn);
    			}
    			else {
    				$base = getDamage($conn, $acc, $calcValues) * $skillRow['damage'];
    				$fireDmg = floor(resistDamage($conn, $acc, $base, 'fire'));
    				$earthDmg = floor(resistDamage($conn, $acc, $base, 'earth'));
    				$iceDmg = floor(resistDamage($conn, $acc, $base, 'ice'));
    				$total = $fireDmg + $earthDmg + $iceDmg;
    				doDamage($conn, $acc, $total, $row);
    				$text = getString($conn, $skillID, "skill", "left", "");

    				// $monsterName .= $row['name'];

    				$text = str_replace('[enemy]', $monsterName, $text);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#5B00FF', '<strong>" . $skillRow['mana'] . "</strong>')";
    				sql_query($logMessege, $conn);
    				$text = getString($conn, $skillID . " OR forKey = 0", "combat", "right", "");
    				$damage = "$fireDmg<strong><span style=\'color:red\'> Fire</span></strong>, $iceDmg<strong><span style=\'color:blue\'> Ice</span></strong> and $earthDmg <strong><span style=\'color:brown\'> Earth</span></strong>";
    				$message = str_replace('[damage]', number_format($damage) , $text);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $total . "</strong>')";
    				sql_query($logMessege, $conn);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    				sql_query($logMessege, $conn);
    			}
    		}
    		else
    		if ($skillID == 17 || $skillID == 3 || $skillID == 18 || $skillID == 21 || $skillID == 27) {

    			// Megic Bolt       Fireball           Ice Spear	      earthquake		Blizzard

    			$min = explode('-', $effect) [0];
    			$max = explode('-', $effect) [1];
    			$damage = ceil(rand($min, $max) * ($calcValues["spr"] / $chance + 1));
                if(in_array($enemyid, [147,148,149,150])){
                    $damage = floor($damage * (($calcValues['shapelessDmg'] / 100) + 1));
                }
    			if ($skillID == 3 || $skillID == 18) {
    				$sql = "SELECT * FROM "equipmentInventory" where \"template\" = 64 AND \"equipped\" = 1 AND \"playerid\" = $acc LIMIT 1";
    				$query = sql_query($sql, $conn);
    				if (mysqli_num_rows($query) > 0) {
    					$damage = floor($damage * 1.5);
    				}
    			}

    			$damage = ceil(resistDamage($conn, $acc, $damage, isset($element) ? $element : 'physical'));
    			$sql_get_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    			$row = mysqli_fetch_array($sql_get_enemy, MYSQLI_ASSOC);
    			$enemyID = $row['enemyID'];
    			doDamage($conn, $acc, $damage, $row);
    			$text = getString($conn, $skillID, "skill", "left", "");
    			$monsterName = $row['prefix'];
    			if ($monsterName != "") {
    				$monsterName = "the ";
    			}

    			$monsterName.= $row['name'];
    			$text = str_replace('[enemy]', $monsterName, $text);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#5B00FF', '<strong>" . $skillRow['mana'] . "</strong>')";
    			sql_query($logMessege, $conn);
    			$text = getString($conn, $skillID . " OR forKey = 0", "combat", "right", "");
    			$damage = number_format($damage);
    			$message = str_replace('[damage]', $damage . $elementText, $text);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $damage . "</strong>')";
    			sql_query($logMessege, $conn);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    			sql_query($logMessege, $conn);
    		}
    		else
    		if ($skillID == 19) {

    			// Siphen Energy

    			$min = explode('-', $effect) [0];
    			$max = explode('-', $effect) [1];
    			$damage = ceil(rand($min, $max) * ($calcValues["spr"] / 50 + 1));
                if(in_array($enemyid, [147,148,149,150])){
                    $damage = floor($damage * (($calcValues['shapelessDmg'] / 100) + 1));
                }
    			mana($damage, $acc, $conn);
    			$sql_get_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    			$row = mysqli_fetch_array($sql_get_enemy, MYSQLI_ASSOC);
    			$enemyID = $row['enemyID'];
    			$text = getString($conn, $skillID, "skill", "left", "cast");
    			$monsterName = $row['prefix'];
    			if ($monsterName != "") {
    				$monsterName = "the ";
    			}

    			$monsterName.= $row['name'];
    			$text = str_replace('[enemy]', $monsterName, $text);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#5B00FF', '')";
    			sql_query($logMessege, $conn);
    			$text = getString($conn, $skillID, "skill", "right", "leech");
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $text . "', '#E00101', '')";
    			sql_query($logMessege, $conn);
    			if ((rand(0, 100)) < $chance) {
    				$text = getString($conn, $skillID, "skill", "right", "harm");
    				$message = str_replace('[atk]', $duration, $text);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '')";
    				sql_query($logMessege, $conn);
    				$duration = (100 - $duration) / 100;
    				$attack = "INSERT INTO "combatenemies" ("maxhealth", "enemyid", "playerid" , "prefix" , "name" , "attack", "mattack"  , "fireres" , "earthres" , "iceres" , "holyres" , "arcaneres" , "physicalres" , "softdef" , "softmdef" , "health" , "exp", "silver", "hit", "flee", "level", "stunresist")
                                    SELECT "maxhealth", "enemyid", "playerid" , "prefix" , "name" , ("attack" * $duration), ("mattack" * $duration) , "fireres" , "earthres" , "iceres" , "holyres" , "arcaneres" , "physicalres" , "softdef" , "softmdef" , "health", "exp", "silver", "hit", "flee", "level", "stunresist"
                                    FROM "combatEnemies" WHERE "enemyid" =" . $row['enemyID'] . " AND "playerid" = " . $acc . "
                                    ORDER BY "combatEnemyID" DESC
                                    LIMIT 1";
    				sql_query($attack, $conn);
    			}

    			$text = getString($conn, $skillID, "skill", "left", "gain");
    			$damage = number_format($damage);
    			$text = str_replace('[mana]', $damage, $text);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#007dff', '<strong>$damage</strong>')";
    			sql_query($logMessege, $conn);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    			sql_query($logMessege, $conn);
    		}
    		else
    		if ($skillID == 23) {

    			// Entangle

    			$sql_get_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    			$row = mysqli_fetch_array($sql_get_enemy, MYSQLI_ASSOC);
    			$enemyID = $row['enemyID'];
    			$stunBlock = $row['stunresist'];
    			$text = getString($conn, $skillID, "skill", "left", "cast");
    			$monsterName = $row['prefix'];
    			if ($monsterName != "") {
    				$monsterName = "the ";
    			}

    			$monsterName.= $row['name'];
    			$text = str_replace('[enemy]', $monsterName, $text);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#5B00FF', '')";
    			sql_query($logMessege, $conn);
    			if ((rand(0, 100)) > $stunBlock) { //entangled
    				$text = getString($conn, $skillID, "skill", "cast", "success");
    				$text = str_replace('[enemy]', $monsterName, $text);

    				// return $text;

    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $text . "', '#5B00FF', '')";
    				sql_query($logMessege, $conn);
    				$sql = "INSERT INTO enemyEffects (effectName, effectRenew, effectType, playerID) values ('Entangle', $chance, 1, $acc)";
    				sql_query($sql, $conn);
    				$attack = "INSERT INTO "combatenemies" ( "enemyid" , "playerid" , "prefix" , "name" , "attack" , "fireres" , "earthres" , "iceres" , "holyres" , "arcaneres" , "physicalres" , "softdef" , "softmdef" , "health" , "exp", "silver", "hit", "flee", "level", "stunresist", maxhealth, "mattack")
                                    SELECT "enemyid" , "playerid" , "prefix" , "name" , "attack" , "fireres" , "earthres" , "iceres" , "holyres" , "arcaneres" , "physicalres" , "softdef" , "softmdef" , "health", "exp", "silver", "hit", "flee", "level", "stunresist" + 50, maxhealth, "mattack"
                                    FROM "combatEnemies" WHERE "enemyid" = $enemyID AND "playerid" = $acc
                                    ORDER BY "combatEnemyID" DESC
                                    LIMIT 1";
    				sql_query($attack, $conn);
    			}
    			else {
    				$text = getString($conn, $skillID, "skill", "cast", "fail");

    				// return $text;

    				$text = str_replace('[enemy]', $monsterName, $text);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $text . "', '#5B00FF', '')";
    				sql_query($logMessege, $conn);
    			}
    		}
    		else
    		if ($skillID == 25) {

    			// Meteor

    			$min = explode('-', $effect) [0];
    			$max = explode('-', $effect) [1];
    			$damage = ceil(rand($min, $max) * ($calcValues["spr"] / $chance + 1));
                if(in_array($enemyid, [147,148,149,150])){
                    $damage = floor($damage * (($calcValues['shapelessDmg'] / 100) + 1));
                }
    			$fireDmg = $damage * .66;
    			$earthDmg = $damage * .33;
    			$totalDmg = $damage;
    			$fireDmg = ceil(resistDamage($conn, $acc, $fireDmg, 'fire'));
    			$earthDmg = ceil(resistDamage($conn, $acc, $earthDmg, 'earth'));
    			$sql_get_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    			$row = mysqli_fetch_array($sql_get_enemy, MYSQLI_ASSOC);
    			$enemyID = $row['enemyID'];
    			doDamage($conn, $acc, $fireDmg, $row);
    			doDamage($conn, $acc, $earthDmg, $row);
    			$text = getString($conn, $skillID, "skill", "left", "");
    			$monsterName = $row['prefix'];
    			if ($monsterName != "") {
    				$monsterName = "the ";
    			}

    			$monsterName.= $row['name'];
    			$text = str_replace('[enemy]', $monsterName, $text);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#5B00FF', '<strong>" . $skillRow['mana'] . "</strong>')";
    			sql_query($logMessege, $conn);
    			$text = getString($conn, $skillID . " OR forKey = 0", "combat", "right", "");
    			$earthDmg = number_format($earthDmg);
    			$fireDmg = number_format($fireDmg);
    			$damage = "$fireDmg<strong><span style=\'color:red\'> Fire</span></strong> and $earthDmg <strong><span style=\'color:brown\'> Earth</span></strong>";
    			$message = str_replace('[damage]', number_format($damage) , $text);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $totalDmg . "</strong>')";
    			sql_query($logMessege, $conn);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    			sql_query($logMessege, $conn);
    		}
    		else
    		if ($skillID == 4) {

    			// Holy Fire

    			$min = explode('-', $effect) [0];
    			$max = explode('-', $effect) [1];
    			$damage = ceil(rand($min, $max) * ($calcValues["spr"] / 25 + 1));
                if(in_array($enemyid, [147,148,149,150])){
                    $damage = floor($damage * (($calcValues['shapelessDmg'] / 100) + 1));
                }
    			$sql = "SELECT * FROM equipmentInventory where playerid = $acc AND equipped = 1 AND template = 58";
    			$query = sql_query($sql, $conn);
    			if (mysqli_num_rows($query) > 0) {
    				$damage = $damage * (mysqli_num_rows($query) * 1.35);
    			}
    			else {
    				$sql = "SELECT * FROM equipmentInventory where playerid = $acc AND equipped = 1 AND template = 89";
    				$query = sql_query($sql, $conn);
    				if (mysqli_num_rows($query) > 0) {
    					$damage = $damage * (mysqli_num_rows($query) * 1.75);
    				}
    			}

    			$damage = ceil(resistDamage($conn, $acc, $damage, 'holy'));
    			$sql_get_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    			$row = mysqli_fetch_array($sql_get_enemy, MYSQLI_ASSOC);
    			$enemyID = $row['enemyID'];
    			doDamage($conn, $acc, $damage, $row);
    			$text = getString($conn, $skillID, "skill", "left", "");
    			$monsterName = $row['prefix'];
    			if ($monsterName != "") {
    				$monsterName = "the ";
    			}

    			$monsterName.= $row['name'];
    			$text = str_replace('[enemy]', $monsterName, $text);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#5B00FF', '<strong>" . $skillRow['mana'] . "</strong>')";
    			sql_query($logMessege, $conn);
    			$text = getString($conn, $skillID . " OR forKey = 0", "combat", "right", "");
    			$damage = number_format($damage);
    			$message = str_replace('[damage]', number_format($damage) , $text);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $damage . "</strong>')";
    			sql_query($logMessege, $conn);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    			sql_query($logMessege, $conn);
    		}
    		else
    		if ($skillID == 28) {

    			// Call of the Void

    			$text = getString($conn, $skillID, "skill", "left", "");
    			$monsterName = $row['prefix'];
    			if ($monsterName != "") {
    				$monsterName = "the ";
    			}

    			$monsterName.= $row['name'];
    			$text = str_replace('[enemy]', $monsterName, $text);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#5B00FF', '<strong>" . $skillRow['mana'] . "</strong>')";
    			sql_query($logMessege, $conn);
    			$enemyMax = getAttribute($conn, "enemies", "health", $enemyID);
    			$enemyPercent = floor($enemyHP / $enemyMax * 100);
    			if ($enemyPercent < 26 && (rand(0, 100) > (100 - $chance))) {
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) values (" . $acc . "," . $enemyID . ",'RIGHT','...and OBLITERATED the $monsterName')";
    				sql_query($logMessege, $conn);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    				sql_query($logMessege, $conn);
    				doDamage($conn, $acc, 99999999999, $row);
    				return;
    			}

    			$damage = floor($effect * $enemyHP);
    			$sql_get_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    			$row = mysqli_fetch_array($sql_get_enemy, MYSQLI_ASSOC);
    			$enemyID = $row['enemyID'];
    			doDamage($conn, $acc, $damage, $row);
    			$text = getString($conn, $skillID . " OR forKey = 0", "combat", "right", "");
    			$damage = number_format($damage);
    			$message = str_replace('[damage]', number_format($damage) , $text);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $damage . "</strong>')";
    			sql_query($logMessege, $conn);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    			sql_query($logMessege, $conn);
    		}
    		else
    		if ($skillID == 8) {

    			// Sacrifice

    			$charStats = getRow($conn, "calcValues", $acc);
    			$hit = $charStats['hit'];
    			$element = $charStats['weapElement'];
    			$sql_get_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    			$row = mysqli_fetch_array($sql_get_enemy, MYSQLI_ASSOC);
    			$enemyID = $row['enemyID'];
    			$flee = $row['flee'];
    			$text = getString($conn, $skillID, "skill", "left", "");
    			$monsterName = $row['prefix'];
    			if ($monsterName != "") {
    				$monsterName = "the ";
    			}

    			$monsterName.= $row['name'];
    			if (attackHit(($hit + 10) , $flee) == 0) {
    				$text = getString($conn, $skillID, "skill", "left", "");
    				$text = str_replace('[enemy]', $monsterName, $text);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#5B00FF', '<strong>" . $skillRow['mana'] . "</strong>')";
    				sql_query($logMessege, $conn);
    				$text = getString($conn, 0, "combat", "right", "miss");
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $text . "', '#E00101', '<strong>" . $damage . "</strong>')";
    				sql_query($logMessege, $conn);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    				sql_query($logMessege, $conn);
    			}
    			else {
    				$damage = floor(getDamage($conn, $acc, $calcValues));
    				$health = getAttribute($conn, "character", "hitpoints", $acc);
    				$health = floor($health / 10);
    				$damage = floor(resistDamage($conn, $acc, ($damage + (($skillRow['damage'] / 2) * $health)) , isset($element) ? $element : 'physical'));
    				$sql = "SELECT * FROM "equipmentInventory" where \"template\" = 51 AND \"equipped\" = 1 AND \"playerid\" = $acc LIMIT 1";
    				$query = sql_query($sql, $conn);
    				if (mysqli_num_rows($query) == 1) {
    					$damage = floor($damage * 1.75);
    					$health = floor($health / 2);
    				}
    				else {
    					$sql = "SELECT * FROM "equipmentInventory" where \"template\" = 83 AND \"equipped\" = 1 AND \"playerid\" = $acc LIMIT 1";
    					$query = sql_query($sql, $conn);
    					if (mysqli_num_rows($query) == 1) {
    						$damage = floor($damage * 2.25);
    						$health = floor($health / 3);
    					}
    				}

    				$sql = "UPDATE \"character\" SET \"hitpoints\" = \"hitpoints\" - " . $health . " WHERE \"playerid\" = " . $acc;
    				sql_query($sql, $conn);
    				$text = str_replace('[enemy]', $monsterName, $text);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#5B00FF', '<strong>" . $skillRow['mana'] . "</strong>')";
    				sql_query($logMessege, $conn);
    				$text = "...draining $health life from you...";
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#E00101', '<strong>" . $health . "</strong>')";
    				sql_query($logMessege, $conn);
    				$text = getString($conn, "0", "combat", "right", "");
    				$message = str_replace('[damage]', number_format($damage) , $text);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $damage . "</strong>')";
    				sql_query($logMessege, $conn);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    				sql_query($logMessege, $conn);
    				doDamage($conn, $acc, $damage, $row);
    			}
    		}
    		else
    		if ($skillID == 36) {

    			// blind

    			$charStats = getRow($conn, "calcValues", $acc);
    			$hit = $charStats['hit'];
    			$element = $charStats['weapElement'];
    			$sql_get_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    			$enemyRow = mysqli_fetch_array($sql_get_enemy, MYSQLI_ASSOC);
    			$enemyID = $enemyRow['enemyID'];
    			$monsterName = $enemyRow['prefix'];
    			$flee = $enemyRow['flee'];
    			if ($monsterName != "") {
    				$monsterName = "the ";
    			}

    			if (attackHit($hit, $flee) == 0) {
    				$text = getString($conn, $skillID, "skill", "left", "");
    				$monsterName.= $row['name'];
    				$text = str_replace('[enemy]', $monsterName, $text);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#5B00FF', '<strong>" . $skillRow['mana'] . "</strong>')";
    				sql_query($logMessege, $conn);
    				$text = getString($conn, 0, "combat", "right", "miss");
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $text . "', '#E00101', '<strong></strong>')";
    				sql_query($logMessege, $conn);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    				sql_query($logMessege, $conn);
    			}
    			else {
    				$damage = ceil(resistDamage($conn, $acc, floor(getDamage($conn, $acc, $calcValues) * $skillRow['damage']) , isset($element) ? $element : 'physical'));
    				doDamage($conn, $acc, $damage, $row);
    				$text = getString($conn, $skillID, "skill", "left", "");
    				$monsterName.= $row['name'];
    				$text = str_replace('[enemy]', $monsterName, $text);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#5B00FF', '<strong>" . $skillRow['mana'] . "</strong>')";
    				sql_query($logMessege, $conn);
    				$sql = "select * from enemyEffects where playerid = $acc and effectType = 2";
    				$query = sql_query($sql, $conn);
    				if (mysqli_num_rows($query) == 0) {
    					$sql = "SELECT * FROM "equipmentInventory" where \"template\" = 73 AND \"equipped\" = 1 AND \"playerid\" = $acc LIMIT 1";
    					$query = sql_query($sql, $conn);
    					if (mysqli_num_rows($query) == 1) {
    						$chance = $chance + 20;
    					}

    					if (rand(0, 100) < $chance) {
    						$text = getString($conn, $skillID, "skill", "right", "blind");
    						$message = str_replace('[damage]', number_format($damage) , $text);
    						$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $damage . "</strong>')";
    						sql_query($logMessege, $conn);
    						$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    						sql_query($logMessege, $conn);
    						$sql = "insert into enemyEffects (effectRenew, effectDecay, effectType, playerID) values (9900, 85, 2, $acc)";
    						sql_query($sql, $conn);
    					}
    					else {
    						$text = getString($conn, $skillID, "skill", "right", "not");
    						$message = str_replace('[damage]', number_format($damage) , $text);
    						$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $damage . "</strong>')";
    						sql_query($logMessege, $conn);
    						$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    						sql_query($logMessege, $conn);
    					}
    				}
    				else {
    					$text = "...And hit for [damage] damage, but it is already blind!";
    					$message = str_replace('[damage]', number_format($damage) , $text);
    					$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $damage . "</strong>')";
    					sql_query($logMessege, $conn);
    					$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    					sql_query($logMessege, $conn);
    				}
    			}
    		}
    		else
    		if ($skillID == 37) {

    			// poison

    			$charStats = getRow($conn, "calcValues", $acc);
    			$hit = $charStats['hit'];
    			$element = $charStats['weapElement'];
    			$sql_get_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    			$enemyRow = mysqli_fetch_array($sql_get_enemy, MYSQLI_ASSOC);
    			$enemyID = $enemyRow['enemyID'];
    			$monsterName = $enemyRow['prefix'];
    			$flee = $enemyRow['flee'];
    			if ($monsterName != "") {
    				$monsterName = "the ";
    			}

    			if (attackHit($hit, $flee) == 0) {
    				$text = getString($conn, $skillID, "skill", "left", "");
    				$monsterName.= $row['name'];
    				$text = str_replace('[enemy]', $monsterName, $text);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#5B00FF', '<strong>" . $skillRow['mana'] . "</strong>')";
    				sql_query($logMessege, $conn);
    				$text = getString($conn, 0, "combat", "right", "miss");
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $text . "', '#E00101', '<strong></strong>')";
    				sql_query($logMessege, $conn);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    				sql_query($logMessege, $conn);
    			}
    			else {
    				$damage = ceil(resistDamage($conn, $acc, floor(getDamage($conn, $acc, $calcValues) * $skillRow['damage']) , isset($element) ? $element : 'physical'));
    				doDamage($conn, $acc, $damage, $row);
    				$text = getString($conn, $skillID, "skill", "left", "");
    				$monsterName.= $row['name'];
    				$text = str_replace('[enemy]', $monsterName, $text);
    				$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#5B00FF', '<strong>" . $skillRow['mana'] . "</strong>')";
    				sql_query($logMessege, $conn);
    				$sql = "select * from enemyEffects where playerid = $acc and effectType = 2";
    				$query = sql_query($sql, $conn);
    				if (mysqli_num_rows($query) == 0) {
    					$sql = "SELECT * FROM "equipmentInventory" where \"template\" = 73 AND \"equipped\" = 1 AND \"playerid\" = $acc LIMIT 1";
    					$query = sql_query($sql, $conn);
    					if (mysqli_num_rows($query) == 1) {
    						$chance = $chance + 20;
    					}

    					if (rand(0, 100) < $chance) {
    						$text = getString($conn, $skillID, "skill", "right", "poison");
    						$message = str_replace('[damage]', number_format($damage) , $text);
    						$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $damage . "</strong>')";
    						sql_query($logMessege, $conn);
    						$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    						sql_query($logMessege, $conn);
    						$sql = "insert into enemyEffects (effectRenew, effectDecay, effectType, playerID) values ($duration, 5, 3, $acc)";
    						sql_query($sql, $conn);
    					}
    					else {
    						$text = getString($conn, $skillID, "skill", "right", "not");
    						$message = str_replace('[damage]', number_format($damage) , $text);
    						$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $damage . "</strong>')";
    						sql_query($logMessege, $conn);
    						$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    						sql_query($logMessege, $conn);
    					}
    				}
    				else {
    					$text = "...And hit for [damage] damage, but it is already poisoned!";
    					$message = str_replace('[damage]', number_format($damage) , $text);
    					$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $damage . "</strong>')";
    					sql_query($logMessege, $conn);
    					$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    					sql_query($logMessege, $conn);
    				}
    			}
    		}
    		else
    		if ($skillID == 10) {

    			// Smite

    			$damage = getAttribute($conn, "equipmentBonus", "blockChance", $acc);
    			if ($damage == 0) {
    				return;
    			}

    			$damage = $damage * $skillRow['damage'];
                if(in_array($enemyid, [147,148,149,150])){
                    $damage = floor($damage * (($calcValues['shapelessDmg'] / 100) + 1));
                }
    			$damage = resistDamage($conn, $acc, $damage, 'physical');
    			$sql_get_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    			$row = mysqli_fetch_array($sql_get_enemy, MYSQLI_ASSOC);
    			$enemyID = $row['enemyID'];
    			doDamage($conn, $acc, $damage, $row);
    			$text = getString($conn, $skillID, "skill", "left", "");
    			$monsterName = $row['prefix'];
    			if ($monsterName != "") {
    				$monsterName = "the ";
    			}

    			$monsterName.= $row['name'];
    			$text = str_replace('[enemy]', $monsterName, $text);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#5B00FF', '<strong>" . $skillRow['mana'] . "</strong>')";
    			sql_query($logMessege, $conn);
    			$text = getString($conn, "0", "combat", "right", "");
    			$message = str_replace('[damage]', number_format($damage) , $text);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $damage . "</strong>')";
    			sql_query($logMessege, $conn);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    			sql_query($logMessege, $conn);
    		}
    		else
    		if ($skillID == 33) {

    			// Bandage Wounds

    			$heal = ceil($effect * ($calcValues["spr"] / 40 + 1));
    			heal($heal, $acc, $conn);
    			$text = "You quickly wrap up your wounds...";
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#5B00FF', '<strong>" . $skillRow['mana'] . "</strong>')";
    			sql_query($logMessege, $conn);
    			$text = "...and regain $heal health!";
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "', '#00ff76', '<strong>" . $heal . "</strong>')";
    			sql_query($logMessege, $conn);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    			sql_query($logMessege, $conn);
    		}
    	}
    	else { //NOT IN COMBAT
    		if ($skillID == 5) {
    			$min = explode('-', $effect) [0] * $times;
    			$max = explode('-', $effect) [1] * $times;
    			$heal = ceil(rand($min, $max) * ($calcValues["spr"] / 40 + 1));
    			heal($heal, $acc, $conn);
    			return "<span style='color:green;'>Heal Used! you restored $heal health!</span>";
    		}
    		else
    		if ($skillID == 33) {
    			$heal = ceil($effect * $times * ($calcValues["spr"] / 40 + 1));
    			heal($heal, $acc, $conn);
    			return "<span style='color:green;'>You wrap up your wounds and restore $heal health!</span>";
    		}

    		if ($skillRow['type'] == 'Buff') {
    			setAchievement($conn, $acc, 8);
    			$sql = "SELECT * FROM playerBuffs where buffID = $skillID and playerID = $acc";
    			$query = sql_query($sql, $conn);
    			if (mysqli_num_rows($query) == 0) {
    				if ($skillID == 7) {
    					$sql = "INSERT INTO playerBuffs (buffID, playerID, name, remaining, armor, image, script) values ($skillID, $acc, 'Holy Armor', 5 * $times, $effect, '" . $image . "', '" . $skillRow['script'] . "')";
    					sql_query($sql, $conn);
    					return "<span style='color:green;'>Holy Armor Used! Armor enchanted for " . 5 * $times . " encounters.</span>";
    				}

    				if ($skillID == 24) {
    					$sql = "INSERT INTO playerBuffs (buffID, playerID, name, remaining, armor, iceres, image, script) values ($skillID, $acc, 'Arctic Armor', $duration * $times , $effect, $chance, '" . $image . "', '" . $skillRow['script'] . "')";
    					sql_query($sql, $conn);
    					return "<span style='color:green;'>Arctic Armor Used! Armor augmented for " . $duration * $times . " encounters.</span>";
    				}

    				if ($skillID == 11) {
    					$sql = "INSERT INTO playerBuffs (buffID, playerID, name, remaining, fireres, iceres, arcaneres, holyres, earthres, image, script) values ($skillID, $acc, 'Divine Protection', 5 * $times, $effect, $effect, $effect, $effect, $effect, '" . $image . "', '" . $skillRow['script'] . "')";
    					sql_query($sql, $conn);
    					return "<span style='color:green;'>Divine Protection Used! All resistences boosted for " . 5 * $times . " encounters.</span>";
    				}

    				if ($skillID == 14 || $skillID == 15 || $skillID == 16 || $skillID == 35) {
    					$sql = "DELETE FROM playerBuffs where buffID in (14,15,16,35) and playerID = $acc";
    					sql_query($sql, $conn);
    					$sql = "INSERT INTO playerBuffs (buffID, playerID, name, remaining, weapElement, image, script) values ($skillID, $acc, '$element Enchantment', $duration * $times, '$spellElement', '" . $image . "', '" . $skillRow['script'] . "')";
    					sql_query($sql, $conn);
    					return "<span style='color:green;'>$element Enchantment Used! Weapon enchanted for " . $duration * $times . " encounters.</span>";
    				}

    				if ($skillID == 20) {
    					$sql = "INSERT INTO playerBuffs (buffID, playerID, name, remaining, image, script) values ($skillID, $acc, 'Burning Aura', $duration * $times, '$image', '" . $skillRow['script'] . "')";
    					sql_query($sql, $conn);
    					return "<span style='color:green;'>Burning Aura Used! Armor ablaze for " . $duration * $times . " encounters!</span>";
    				}

    				if ($skillID == 34) {
    					$sql = "INSERT INTO playerBuffs (buffID, playerID, name, remaining, image, script) values ($skillID, $acc, 'Find Potion', $duration * $times, '$image', '" . $skillRow['script'] . "')";
    					sql_query($sql, $conn);
    					return "<span style='color:green;'>Find Potion Used! You have enhanced potion finding abilities for " . $duration * $times . " encounters!</span>";
    				}
    			}
    			else {
    				if ($skillID == 7 || $skillID == 11 || $skillID == 14 || $skillID == 15 || $skillID == 16 || $skillID == 20 || $skillID == 24 || $skillID == 34 || $skillID == 35) {
    					$sql = "UPDATE playerBuffs set remaining = remaining + " . $skillRow['duration'] * $times . " where playerid = $acc AND buffID = $skillID";
    					sql_query($sql, $conn);
    					if ($skillID == 7) {
    						return "<span style='color:green;'>Holy Armor Used! Armor enchanted for " . 5 * $times . " encounters.</span>";
    					}

    					if ($skillID == 11) {
    						return "<span style='color:green;'>Divine Protection Used! All resistences boosted for " . 5 * $times . " encounters.</span>";
    					}

    					if ($skillID == 14 || $skillID == 15 || $skillID == 16 || $skillID == 35) {
    						return "<span style='color:green;'>$element Enchantment Used! Weapon enchanted for " . $duration * $times . " encounters.</span>";
    					}

    					if ($skillID == 20) {
    						return "<span style='color:green;'>Burning Aura Used! Armor ablaze for " . $duration * $times . " encounters!</span>";
    					}

    					if ($skillID == 24) {
    						return "<span style='color:green;'>Arctic Armor Used! Armor augmented for " . $duration * $times . " encounters!</span>";
    					}

    					if ($skillID == 34) {
    						return "<span style='color:green;'>Find Potion Used! You have enhanced potion finding abilities for " . $duration * $times . " encounters!</span>";
    					}
    				}
    			}
    		}
    	}
    }

    // -- Function Name : giveEXP
    // -- Params : $acc, $amnt, $conn, $enemyID
    // -- Purpose :  Awards a character with exp listed in args

    function giveEXP($acc, $amnt, $conn, $enemyID)
    {
    	$levelup = getAttribute($conn, "character", "next", $acc);
    	$current = getAttribute($conn, "character", "exp", $acc);
    	$level = getAttribute($conn, "character", "level", $acc) + 1;
    	$diff = $current + $amnt - $levelup;
    	if ($current + $amnt + 1 > $levelup) {
    		levelUp($acc, $diff, $conn, $enemyid, $level);
    	}
    	else {
    		$sql = "UPDATE \"character\" SET \"exp\" = \"exp\" + " . $amnt . " WHERE playerID = '" . $acc . "'";
    		sql_query($sql, $conn);
    	}
    }

    // -- Function Name : levelUp
    // -- Params : $acc, $diff, $conn, $enemyid, $level
    // -- Purpose : Combat based level up, writes to combat log and updates stats

    function levelUp($acc, $diff, $conn, $enemyid, $level)
    {
    	if ($level > 49) {
    		setAchievement($conn, $acc, 15);
    	}

    	logAction($conn, $acc, 'levelUp', $level, NULL);
    	$sql = "SELECT exp FROM exp WHERE level = $level";
    	$row = mysqli_fetch_array(sql_query($sql, $conn) , MYSQLI_ASSOC);
    	$nextLevel = $row['exp'];
    	$message = "You have reached level " . $level . "!";
    	$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','" . $message . "', 'rgb(242,130,31)', '')";
    	sql_query($logMessege, $conn);
    	$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    	sql_query($logMessege, $conn);
    	$class = getAttribute($conn, "character", "class", $acc);
    	if ($class == "Paladin") {
    		$str = 2;
    		$dex = 1;
    		$spr = 0;
    		$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','You have gained 2 strength!', 'rgb(242,130,31)', '')";
    		sql_query($logMessege, $conn);
    		$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','You have gained 1 dexterity!', 'rgb(242,130,31)', '')";
    		sql_query($logMessege, $conn);
    	}
    	else
    	if ($class == "Assassin") {
    		$str = 0;
    		$dex = 2;
    		$spr = 1;
    		$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','You have gained 2 dexterity!', 'rgb(242,130,31)', '')";
    		sql_query($logMessege, $conn);
    		$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','You have gained 1 spirit!', 'rgb(242,130,31)', '')";
    		sql_query($logMessege, $conn);
    	}
    	else {
    		$str = 1;
    		$dex = 0;
    		$spr = 2;
    		$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','You have gained 2 spirit!', 'rgb(242,130,31)', '')";
    		sql_query($logMessege, $conn);
    		$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','You have gained 1 strength!', 'rgb(242,130,31)', '')";
    		sql_query($logMessege, $conn);
    	}

    	$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','You have gained 4 free stat points!', 'rgb(242,130,31)', '')";
    	sql_query($logMessege, $conn);
    	$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','You have gained 1 skill point!', 'rgb(242,130,31)', '')";
    	sql_query($logMessege, $conn);
    	$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'CENTER','', 'rgb(242,130,31)', '')";
    	sql_query($logMessege, $conn);
    	$sql = "UPDATE \"character\" SET \"level\" = \"level\" + 1, \"exp\" = " . $diff . ", \"next\" = " . $nextLevel . ", "statPoints" = "statPoints" + 4, "skillPoints" = "skillPoints" + 1, ";
    	$sql.= " \"strength\" = \"strength\" + " . $str . ", \"dexterity\" = \"dexterity\" + " . $dex . ", \"spirit\" = \"spirit\" + " . $spr . " WHERE playerID = " . $acc;
    	sql_query($sql, $conn);
    	fullheal($acc, $conn);
    	fullmana($acc, $conn);
    }

    // -- Function Name : attackHit
    // -- Params : $hit, $flee
    // -- Purpose : rolls to see if attack lands

    function attackHit($hit, $flee)
    {
    	$odds = $hit - $flee;
    	if ($odds > 99) {
    		return 1;
    	}

    	if ($odds < 15) {
    		$odds = 15;
    	}

    	$roll = rand(0, 100);
    	if ($roll < $odds) {
    		return 1;
    	}

    	return 0;
    }

    // -- Function Name : getCrit
    // -- Params : $conn, $acc
    // -- Purpose : gets critical damage

    function getCrit($conn, $acc)
    {
    	$row = getRow($conn, "calcValues", $acc);
    	$atk = $row['damage'];
    	$dex = $row['dex'];
    	$multi = $row['critMulti'];
    	$max = $row['maxDmg'];
    	$element = $row['weapElement'];

        if ($row["weapon"] == "club") {

            // MACE MASTERY

            $sql = "SELECT * FROM charSkills s inner join skillLevels l on s.skillID = l.skillID where playerid = $acc and l.level = s.level and s.skillID = 13 LIMIT 1";
            $result = sql_query($sql, $conn);
            $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
            if ($row['damage'] > 0) {
                $atk = $atk * $row['damage'];
            }
        }
        else
        if ($row["weapon"] == "sword") {

            // SWORD MASTERY

            $sql = "SELECT * FROM charSkills s inner join skillLevels l on s.skillID = l.skillID where playerid = $acc and l.level = s.level and s.skillID = 12 LIMIT 1";
            $result = sql_query($sql, $conn);
            $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
            if ($row['damage'] > 0) {
                $atk = $atk * $row['damage'];
            }
        }
        else
        if ($row["weapon"] == "katar") {

            // KATAR MASTERY

            $sql = "SELECT * FROM charSkills s inner join skillLevels l on s.skillID = l.skillID where playerid = $acc and l.level = s.level and s.skillID = 39	 LIMIT 1";
            $result = sql_query($sql, $conn);
            $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
            if ($row['damage'] > 0) {
                $atk = $atk * $row['damage'];
            }
        }
    	$sql_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    	$row = mysqli_fetch_array($sql_enemy, MYSQLI_ASSOC);
    	$mod = (100 - $row[strtolower($element) . 'Res']) / 100;
    	$max = ceil($max * (($atk + 10) / 80 + 1));
    	$max = ($mod * $max);
    	return floor($max + ($max * $multi));
    }

    // -- Function Name : getDamage
    // -- Params : $conn, $acc
    // -- Purpose :  Calculates and returns damage based on char attacks RANGE comes from the weapon, while ATK comes from stats

    function getDamage($conn, $acc, $calcValues)
    {
    	$atk = $calcValues["damage"];
    	if ($calcValues["weapon"] == "club") {

    		// MACE MASTERY

    		$sql = "SELECT * FROM charSkills s inner join skillLevels l on s.skillID = l.skillID where playerid = $acc and l.level = s.level and s.skillID = 13 LIMIT 1";
    		$result = sql_query($sql, $conn);
    		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    		if ($row['damage'] > 0) {
    			$atk = $atk * $row['damage'];
    		}
    	}
    	else
    	if ($calcValues["weapon"] == "sword") {

    		// SWORD MASTERY

    		$sql = "SELECT * FROM charSkills s inner join skillLevels l on s.skillID = l.skillID where playerid = $acc and l.level = s.level and s.skillID = 12 LIMIT 1";
    		$result = sql_query($sql, $conn);
    		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    		if ($row['damage'] > 0) {
    			$atk = $atk * $row['damage'];
    		}
    	}
    	else
    	if ($calcValues["weapon"] == "katar") {

    		// KATAR MASTERY

    		$sql = "SELECT * FROM charSkills s inner join skillLevels l on s.skillID = l.skillID where playerid = $acc and l.level = s.level and s.skillID = 39	 LIMIT 1";
    		$result = sql_query($sql, $conn);
    		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    		if ($row['damage'] > 0) {
    			$atk = $atk * $row['damage'];
    		}
    	}

    	$dex = $calcValues["dex"];
    	$min = $calcValues["minDmg"];
    	$max = $calcValues["maxDmg"];
    	$min = ceil($min * (($atk + 10) / 80 + 1));
    	$max = ceil($max * (($atk + 10) / 80 + 1));
    	if ($dex > 300) {
    		$dex = 300;
    	}

    	$diff = $max - $min;
    	$x = $dex * $diff;
    	$x = $x / 300;
    	$minPostDex = $min + $x;
    	$damage = rand($minPostDex, $max);
        $sql_get_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = $acc ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
        $row = mysqli_fetch_array($sql_get_enemy, MYSQLI_ASSOC);
        $enemyID = $row['enemyID'];
        if(in_array($enemyid, [147,148,149,150])){
            $damage = floor($damage * (($calcValues['shapelessDmg'] / 100) + 1));
        }
    	return $damage;
    }

    // -- Function Name : resistDamage
    // -- Params : $conn, $acc, $damage, $element
    // -- Purpose : Calculates enemy net damage received based on resistances

    function resistDamage($conn, $acc, $damage, $element)
    {
    	$sql_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    	$row = mysqli_fetch_array($sql_enemy, MYSQLI_ASSOC);
    	if (isset($element) && $element != "") {
    		$mod = (100 - $row[strtolower($element) . 'Res']) / 100;
    	}
    	else {
    		$mod = 1;
    	}

    	if ($element == "physical") {
    		$soft = $row['softdef'];
    	}
    	else {
    		$soft = $row['softmdef'];
    	}

    	$damage = ($mod * $damage) - $soft;
    	if ($damage < 1) {
    		return 1;
    	}

    	return $damage;
    }

    // -- Function Name : run
    // -- Params : $acc, $conn
    // -- Purpose : Returns 2 if account is able to escape from combat

    function run($acc, $conn)
    {
    	$level = getAttribute($conn, "character", "level", $acc);
    	$sql_get_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    	$row = mysqli_fetch_array($sql_get_enemy, MYSQLI_ASSOC);
    	$m_level = $row['level'];
    	$m_health = $row['health'];
    	$m_name = $row['name'];
    	$m_ID = $row['enemyID'];
    	$m_maxhealth = getAttribute($conn, "enemies", "health", $m_ID);
    	$m_health = $m_health / $m_maxhealth;
    	$run = 25 + (($level - $m_level) * 5) + (1 / $m_health) * 3;
    	if ($run > 90) {
    		$run = 90;
    	}
    	else
    	if ($run < 20) {
    		$run = 20;
    	}

    	if ((rand(0, 100)) < $run) {

    		// RUN AWAY

    		logAction($conn, $acc, 'run', $m_name, NULL);
    		sql_query("DELETE FROM "enemySkillCooldown" WHERE "playerid" = " . $acc, $conn);
    		return 2;
    	}
    	else {

    		// DON'T RUN AWAY

    		$monsterName = $row['prefix'];
    		if ($monsterName != "") {
    			$monsterName = "the ";
    		}

    		$monsterName.= $row['name'];
    		$text = getString($conn, "0", "running", "left", "");
    		$message = str_replace('[enemy]', $monsterName, $text);
    		$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) values (" . $acc . "," . $m_ID . ",'LEFT','" . $message . "')";
    		sql_query($logMessege, $conn);
    		$text = getString($conn, "0", "running", "right", "");
    		$damage = rand(1, ($level + 2));
    		$message = str_replace('[damage]', number_format($damage) , $text);
    		$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $m_ID . ",'LEFT','" . $message . "', '#E00101', '<strong>" . $damage . "</strong>')";
    		sql_query($logMessege, $conn);
    		$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $m_ID . ",'LEFT','<br/>', '#E00101', ' ')";
    		sql_query($logMessege, $conn);
            $sql = "UPDATE \"character\" SET \"hitpoints\" = \"hitpoints\" - " . $damage . " WHERE \"playerid\" = " . $acc;
            sql_query($sql, $conn);
    	}
    }

    // -- Function Name : doDamage
    // -- Params : $conn, $acc, $amnt, $row
    // -- Purpose : Simply deals damage to current enemy

    function doDamage($conn, $acc, $amnt, $row)
    {
    	$attack = " INSERT INTO "combatenemies" ( "enemyid" , "playerid" , "prefix" , "name" , "attack" , "fireres" , "earthres" , "iceres" , "holyres" , "arcaneres" , "physicalres" , "softdef" , "softmdef" , "health" , "exp", "silver", "hit", "flee", "level", "stunresist", "maxhealth", "mattack")";
    	$attack.= " SELECT "enemyid" , "playerid" , "prefix" , "name" , "attack" , "fireres" , "earthres" , "iceres" , "holyres" , "arcaneres" , "physicalres" , "softdef" , "softmdef" , ("health" - " . $amnt . "), "exp", "silver", "hit", "flee", "level", "stunresist", "maxhealth", "mattack"";
    	$attack.= " FROM "combatEnemies" WHERE "enemyid" =" . $row['enemyID'] . " AND "playerid" = " . $acc;
    	$attack.= " ORDER BY "combatEnemyID" DESC ";
    	$attack.= "LIMIT 1";
    	sql_query($attack, $conn);
    }

    // -- Function Name : checkCombat
    // -- Params : $acc, $conn
    // -- Purpose : Returns information for Character panel

    function checkCombat($acc, $conn)
    {
    	$output = array();
    	$sql_combat = "SELECT * FROM \"combat\" WHERE \"playerid\" = " . $acc;
    	$sql_rows = sql_query($sql_combat, $conn);
    	$check = false;
    	while ($row = mysqli_fetch_array($sql_rows, MYSQLI_ASSOC)) {
    		$output[] = $row['leftColor'] . "|" . $row['lefttext'] . "|" . $row['middleAlign'] . "|" . $row['middletext'] . "|" . $row['rightColor'] . "|" . $row['righttext'];
    		$check = true;
    	}

    	if ($check) {
    		return json_encode($output);
    	}

    	return json_encode("empty");
    }

    // -- Function Name : useItemCombat
    // -- Params : $acc, $item, $conn
    // -- Purpose : Uses an item in combat, writing it to all needed tables

    function useItemCombat($acc, $item, $conn, $calcValues)
    {
    	$row = getRow($conn, "character", $acc);
    	$str = $row['strength'];
    	$hp = $row['hitpoints'];
    	if ($hp < 1) {
    		return 3;
    	}
    	$itemRow = getRow($conn, "item", $item);
    	if (getInventoryItem($conn, $acc, $item) > 0) {
    		removeItemAmount($conn, $acc, $item, 1, true);
    		$sql_get_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = " . $acc . " ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
    		$row = mysqli_fetch_array($sql_get_enemy, MYSQLI_ASSOC);
    		$enemyID = $row['enemyID'];
    		if ($itemRow['combatType'] == 3) {
    			$min = $itemRow['combatMin'];
    			$max = $itemRow['combatMax'];
    			$damage = (rand($min, $max));
    			$damage = ceil($damage);
    			doDamage($conn, $acc, $damage, $row);
    			$text = getString($conn, $item, "combat", "left", "");
    			$monsterName = $row['prefix'];
    			if ($monsterName != "") {
    				$monsterName = "the ";
    			}

    			$monsterName.= $row['name'];
    			$text = str_replace('[enemy]', $monsterName, $text);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "')";
    			sql_query($logMessege, $conn);
    			$text = getString($conn, $item . " OR forKey = 0", "combat", "right", "");
    			$message = str_replace('[damage]', number_format($damage) , $text);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, rightcolor, righttext) values (" . $acc . "," . $enemyID . ",'RIGHT','" . $message . "', '#E00101', '<strong>" . $damage . "</strong>')";
    			sql_query($logMessege, $conn);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    			sql_query($logMessege, $conn);
    		}
    		else
    		if ($itemRow['combatType'] == 2) {
    			$mana = floor((rand($itemRow['useMin'], $itemRow['useMax'])) * (($calcValues["bonusPotMana"] / 100 + 1)));
    			mana($mana, $acc, $conn);
    			$text = getString($conn, $item, "combat", "left", "");
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "')";
    			sql_query($logMessege, $conn);
    			$text = getString($conn, $item, "combat", "right", "");
    			$message = str_replace('[mana]', $mana, $text);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $message . "', '#007dff', '<strong>" . $mana . "</strong>')";
    			sql_query($logMessege, $conn);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    			sql_query($logMessege, $conn);
    		}
    		else
    		if ($itemRow['combatType'] == 1) {
    			$health = floor((rand($itemRow['useMin'], $itemRow['useMax'])) * (($calcValues["bonusPotHeal"] / 100 + 1)));
    			heal($health, $acc, $conn);
    			$text = getString($conn, $item, "combat", "left", "");
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "')";
    			sql_query($logMessege, $conn);
    			$text = getString($conn, $item, "combat", "right", "");
    			$message = str_replace('[health]', $health, $text);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $message . "', '#00ff76', '<strong>" . $health . "</strong>')";
    			sql_query($logMessege, $conn);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    			sql_query($logMessege, $conn);
    		}
            else
            if ($itemRow['combatType'] == 8) {
                $health = floor((rand($itemRow['useMin'], $itemRow['useMax'])) * (($calcValues["bonusPotHeal"] / 100 + 1)));
                heal($health, $acc, $conn);
                $mana = floor((rand($itemRow['useMin'], $itemRow['useMax'])) * (($calcValues["bonusPotMana"] / 100 + 1)));
    			mana($mana, $acc, $conn);
                $text = getString($conn, $item, "combat", "left", "");
                $logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $text . "')";
                sql_query($logMessege, $conn);
                $text = getString($conn, $item, "combat", "right", "");
                $message = str_replace('[health]', $health, $text);
                $message = str_replace('[mana]', $mana, $message);
                $logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','" . $message . "', '#00ff76', '<strong>" . $health . ' + ' . $mana . "</strong>')";
                sql_query($logMessege, $conn);
                $logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
                sql_query($logMessege, $conn);
            }
    		else
    		if ($itemRow['combatType'] == 7) {
    			$charStats = $calcValues;
    			$amnt = rand($itemRow["useMin"], $itemRow["useMax"]) / 100;
    			$mana = floor($charStats["maxMana"] * $amnt);
    			$health = floor($charStats["maxhealth"] * $amnt);
    			heal($health, $acc, $conn);
    			mana($mana, $acc, $conn);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext) values (" . $acc . "," . $enemyID . ",'LEFT','You chug back the rejuvenation potion')";
    			sql_query($logMessege, $conn);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','And recover $health health and $mana mana', '#00ff76', '<strong style=\'color:#00ff76\'>" . $health . "</strong><br/><strong style=\'color:#007dff\'>" . $mana . "</strong>')";
    			sql_query($logMessege, $conn);
    			$logMessege = "INSERT INTO \"combat\" (playerid, enemyid, middlealign, middletext, leftcolor, lefttext) values (" . $acc . "," . $enemyID . ",'LEFT','<br/>', '#E00101', ' ')";
    			sql_query($logMessege, $conn);
    		}
    	}
    }

    // -- Function Name : isInCombat
    // -- Params : $conn, $acc
    // -- Purpose : checks if user is in combat

    function isInCombat($conn, $acc)
    {
    	$sql = 'select count(*) as count from combat where playerid = ' . $acc;
    	$row = mysqli_fetch_array(sql_query($sql, $conn) , MYSQLI_ASSOC);
    	if ($row['count'] > 0) {
    		return true;
    	}
    	else {
    		return false;
    	}
    }

    // -- Function Name : decreaseBuff
    // -- Params : $conn, $acc
    // -- Purpose : lowers all buffs by 1 turn, removing expired ones

    function decreaseBuff($conn, $acc)
    {
    	$sql = 'delete from "playerBuffs" where "remaining" = 1 and playerID = ' . $acc;
    	sql_query($sql, $conn);
    	$sql = 'update "playerBuffs" set \"remaining\" = \"remaining\" - 1 where \"remaining\" > 0 and \"playerid\" = ' . $acc;
    	sql_query($sql, $conn);
    }

    function populateCombatCard($conn, $acc){
		$playerDiff = getAttribute($conn, "character", "diff", $acc);
		$sql_get_enemy = sql_query("SELECT * FROM combatEnemies WHERE playerID = ".$acc." ORDER BY "combatEnemyID" DESC LIMIT 1", $conn);
		$row2 = mysqli_fetch_array($sql_get_enemy,MYSQLI_ASSOC);
		$id= $row2['enemyID'];
		$sql = "select * from enemies WHERE enemyID = ".$id;
		$sql_rows = sql_query($sql, $conn);
		while($row = mysqli_fetch_array($sql_rows,MYSQLI_ASSOC)){
			$sql = 'SELECT * from charKills WHERE \"playerid\" = '.$acc." AND \"enemyid\" = ".$row["enemyID"];
			$killRow =  mysqli_fetch_array(sql_query($sql, $conn),MYSQLI_ASSOC);
			$color = '';
			$count = $killRow['count'];
            if(in_array($id, [147,148,149,150])){
                $row = $row2;
                $playerDiff = 1;
            }
			if($count > 750){
				$star = '<span class="tooltiptwo tooltipstered" title="20% increased drop-rate and experence gain" style="cursor: pointer;font-size:18px;color:#DAA520">&nbsp;★</span>';
			}else if($count > 500){
				$star = '<span class="tooltiptwo tooltipstered" title="20% increased drop-rate" style="cursor: pointer;font-size:18px;color:#a7a7a7">&nbsp;★</span>';
			}else{
				$star = '';
			}
			if($count > 100){
				$fireresists = '<span style="color:red">Fire: </span>'.$row["fireres"]."%<br/>";
				$earthresists = '<span style="color:brown">Earth: </span>'.$row["earthres"]."%<br/>";
				$iceresists = '<span style="color:blue">Ice: </span>'.$row["iceres"]."%<br/>";
				$holyresists = '<span style="color:white">Holy: </span>'.$row["holyres"]."%<br/>";
				$arcaneresists = '<span style="color:purple">Arcane: </span>'.$row["arcaneres"]."%<br/>";
				$phyresists = '<span style="color:grey">Phys.: </span>'.$row["physicalres"]."%<br/>";
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
				$def = "DEF: ".ceil($row['softdef'] * $playerDiff).'<br/>';
				$mdef = "MDEF: ".ceil($row['softmdef'] * $playerDiff).'<br/>';
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
				$level = "LVL: ".$row['level'];
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
			if (isset($row['chapter'])){
				return $name.'|_'.$row["enemyID"].'|'.$image.'|'.$row['chapter'].'|'.$row['map'].'|'.$row['zones'].'|'.$outputString.'|'.$row['count'].'|'.$color;
			}else{
				return $name.'|_'.$row["enemyID"].'|'.$image.'||||'.$outputString.'||'.$color;
			}

		}
	}
?>
