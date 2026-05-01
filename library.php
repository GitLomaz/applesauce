<?php

// Include centralized configuration
require_once(__DIR__ . '/config.php');

/*
UUUUUUUU     UUUUUUUUTTTTTTTTTTTTTTTTTTTTTTTIIIIIIIIIILLLLLLLLLLL
U::::::U     U::::::UT:::::::::::::::::::::TI::::::::IL:::::::::L
U::::::U     U::::::UT:::::::::::::::::::::TI::::::::IL:::::::::L
UU:::::U     U:::::UUT:::::TT:::::::TT:::::TII::::::IILL:::::::LL
 U:::::U     U:::::U TTTTTT  T:::::T  TTTTTT  I::::I    L:::::L
 U:::::D     D:::::U         T:::::T          I::::I    L:::::L
 U:::::D     D:::::U         T:::::T          I::::I    L:::::L
 U:::::D     D:::::U         T:::::T          I::::I    L:::::L
 U:::::D     D:::::U         T:::::T          I::::I    L:::::L
 U:::::D     D:::::U         T:::::T          I::::I    L:::::L
 U:::::D     D:::::U         T:::::T          I::::I    L:::::L
 U::::::U   U::::::U         T:::::T          I::::I    L:::::L         LLLLLL
 U:::::::UUU:::::::U       TT:::::::TT      II::::::IILL:::::::LLLLLLLLL:::::L
  UU:::::::::::::UU        T:::::::::T      I::::::::IL::::::::::::::::::::::L
    UU:::::::::UU          T:::::::::T      I::::::::IL::::::::::::::::::::::L
      UUUUUUUUU            TTTTTTTTTTT      IIIIIIIIIILLLLLLLLLLLLLLLLLLLLLLLL
*/

// Database connection and query functions now in config.php

// -- Function Name : getRow
// -- Params : $conn, $table, $index
// -- Purpose : Returns full row of table at index in args
function getRow($conn, $table, $index){
    $sql_q = "select * from ".$table." where ".getKey($table)." =".$index." LIMIT 1";
    $sql = sql_query($sql_q, $conn);
    return mysqli_fetch_array($sql,MYSQLI_ASSOC);
}

// -- Function Name : getKey
// -- Params : $table
// -- Purpose : Returns table index name of table provided
function getKey($table){
    switch ($table){
        case "account":
        case "character":
        case "calcValues":
            $table = "playerid";
            break;
        case "combat":
            $table = "combatID";
            break;
        case "combatEnemies":
            $table = "combatEnemyID";
            break;
        case "drops":
            $table = "dropID";
            break;
        case "enemies":
            $table = "enemyID";
            break;
        case "equippedStuff":
            $table = "equipIndex";
            break;
        case "item":
            $table = "item_ID";
            break;
        case "news":
            $table = "newsIndex";
            break;
        case "shops":
            $table = "shop_index";
            break;
        case "quests":
            $table = "questID";
            break;
        case "equipmentbonus":
            $table = "playerid";
            break;
        case "spawnPoints":
            $table = "spawnID";
            break;
        default:
            $table = "index";
            break;
    }
    return $table;
}

// -- Function Name : "getSingleEquipment"
// -- Params : $conn, $id
// -- Purpose : gets description text for a single equipment
function getSingleShopEquipment($conn, $id){

    if($id == -1){
        return;
    }

    $keys['str'] = "Strength: ";
    $keys['dex'] = "Dexterity: ";
    $keys['spr'] = "Spirit: ";
    $keys['vit'] = "Vitality: ";
    $keys['minDmg'] = "Bonus Min Damage: ";
    $keys['maxDmg'] = "Bonus Max Damage: ";
    $keys['armor'] = "Bonus Armor: ";
    $keys['fireRes'] = "Fire Resistance: ";
    $keys['iceRes'] = "Ice Resistance: ";
    $keys['arcaneRes'] = "Arcane Resistance: ";
    $keys['earthRes'] = "Earth Resistance: ";
    $keys['holyRes'] = "Holy Resistance: ";
    $keys['maxHP'] = "Maximum HP: ";
    $keys['maxMP'] = "Maximum MP: ";
    $keys['evasion'] = "Evasion: ";
    $keys['itemDrop'] = "Item Drop Increase: ";
    $keys['silverDrop'] = "Silver Drop Increase: ";
    $keys['critChance'] = "Increased Critical Rate ";
    $keys['critDamage'] = "Bonus Crit Modifier: ";
    $keys['blockChance'] = "Block Chance: ";

    $sql = "SELECT * FROM equipmentTemplate WHERE \"index\" = ".$id;
    $sql_rows = sql_query($sql, $conn);
    while($row = mysqli_fetch_array($sql_rows,MYSQLI_ASSOC)){
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

        foreach ($keys as $key => $value) {
            if($row[$key] > 0){
                $script .= "<strong>" . $value . "</strong>".$row[$key];
                if(in_array($key, ['fireRes', 'iceRes', 'arcaneRes', 'earthRes', 'holyRes',
                    'itemDrop', 'silverDrop', 'critChance', 'critDamage', 'blockChance'])){
                        $script .= "%";
                    }
                $script .= "<br/>";
            }
        }

        $row['script'] = $script;
        return $row;
    }
}

/*
DDDDDDDDDDDDD        MMMMMMMM               MMMMMMMMLLLLLLLLLLL
D::::::::::::DDD     M:::::::M             M:::::::ML:::::::::L
D:::::::::::::::DD   M::::::::M           M::::::::ML:::::::::L
DDD:::::DDDDD:::::D  M:::::::::M         M:::::::::MLL:::::::LL
  D:::::D    D:::::D M::::::::::M       M::::::::::M  L:::::L
  D:::::D     D:::::DM:::::::::::M     M:::::::::::M  L:::::L
  D:::::D     D:::::DM:::::::M::::M   M::::M:::::::M  L:::::L
  D:::::D     D:::::DM::::::M M::::M M::::M M::::::M  L:::::L
  D:::::D     D:::::DM::::::M  M::::M::::M  M::::::M  L:::::L
  D:::::D     D:::::DM::::::M   M:::::::M   M::::::M  L:::::L
  D:::::D     D:::::DM::::::M    M:::::M    M::::::M  L:::::L
  D:::::D    D:::::D M::::::M     MMMMM     M::::::M  L:::::L         LLLLLL
DDD:::::DDDDD:::::D  M::::::M               M::::::MLL:::::::LLLLLLLLL:::::L
D:::::::::::::::DD   M::::::M               M::::::ML::::::::::::::::::::::L
D::::::::::::DDD     M::::::M               M::::::ML::::::::::::::::::::::L
DDDDDDDDDDDDD        MMMMMMMM               MMMMMMMMLLLLLLLLLLLLLLLLLLLLLLLL
*/

// -- Function Name : cancelQuest
// -- Params : $conn, $acc, $quest
// -- Purpose : Cancels a quest based on ID
function cancelQuest($conn, $acc, $quest){
    $sql = "DELETE FROM questPlayerStatus WHERE \"playerid\" = ".$acc." AND \"questID\" = ".$quest;
    sql_query($sql, $conn);
    $questRow = getRow($conn, "quests", $quest);
    $sql = "DELETE FROM \"inventory\" WHERE \"playerid\" = ".$acc." AND \"itemID\" in (".$questRow['req1'].",".$questRow['req2'].",".$questRow['req3'].")";
    sql_query($sql, $conn);
}

// -- Function Name : startQuest
// -- Params : $conn, $acc, $quest
// -- Purpose : Starts a quest based on ID
function startQuest($conn, $acc, $quest){
    $sql = "UPDATE questPlayerStatus set \"status\" = 'working' WHERE \"playerid\" = ".$acc." AND \"questID\" = ".$quest;
    sql_query($sql, $conn);

    if(mysqli_affected_rows($conn) == 0){
        $sql = "INSERT INTO \"questplayerstatus\" (\"playerid\", \"questid\", \"status\") VALUES (".$acc.",".$quest.",'working')";
        sql_query($sql, $conn);
    }
    $questName = getAttribute($conn, 'quests', 'name', $quest);
    logAction($conn, $acc, 'startQuest', $questName, NULL);
}


/*
RRRRRRRRRRRRRRRRR   EEEEEEEEEEEEEEEEEEEEEE               AAA               DDDDDDDDDDDDD
R::::::::::::::::R  E::::::::::::::::::::E              A:::A              D::::::::::::DDD
R::::::RRRRRR:::::R E::::::::::::::::::::E             A:::::A             D:::::::::::::::DD
RR:::::R     R:::::REE::::::EEEEEEEEE::::E            A:::::::A            DDD:::::DDDDD:::::D
  R::::R     R:::::R  E:::::E       EEEEEE           A:::::::::A             D:::::D    D:::::D
  R::::R     R:::::R  E:::::E                       A:::::A:::::A            D:::::D     D:::::D
  R::::RRRRRR:::::R   E::::::EEEEEEEEEE            A:::::A A:::::A           D:::::D     D:::::D
  R:::::::::::::RR    E:::::::::::::::E           A:::::A   A:::::A          D:::::D     D:::::D
  R::::RRRRRR:::::R   E:::::::::::::::E          A:::::A     A:::::A         D:::::D     D:::::D
  R::::R     R:::::R  E::::::EEEEEEEEEE         A:::::AAAAAAAAA:::::A        D:::::D     D:::::D
  R::::R     R:::::R  E:::::E                  A:::::::::::::::::::::A       D:::::D     D:::::D
  R::::R     R:::::R  E:::::E       EEEEEE    A:::::AAAAAAAAAAAAA:::::A      D:::::D    D:::::D
RR:::::R     R:::::REE::::::EEEEEEEE:::::E   A:::::A             A:::::A   DDD:::::DDDDD:::::D
R::::::R     R:::::RE::::::::::::::::::::E  A:::::A               A:::::A  D:::::::::::::::DD
R::::::R     R:::::RE::::::::::::::::::::E A:::::A                 A:::::A D::::::::::::DDD
RRRRRRRR     RRRRRRREEEEEEEEEEEEEEEEEEEEEEAAAAAAA                   AAAAAAADDDDDDDDDDDDD
*/

// -- Function Name : getAttribute
// -- Params : $conn, $table, $attribute, $index
// -- Purpose : Returns arg from row of table at index in args
function getAttribute($conn, $table, $attribute, $index){
    $sql_q = "select ".$attribute." from ".$table." where ".getKey($table)." =".$index." LIMIT 1";
    $sql = sql_query($sql_q, $conn);
    $row = mysqli_fetch_array($sql,MYSQLI_ASSOC);
    return $row[$attribute];
}

// -- Function Name : getNews
// -- Params : $conn
// -- Purpose : Returns an object holding all news posts
function getNews($conn){
    $output = array(); // Initialize output array
    $sqlGetNews = 'SELECT imageOffset, image, title, "date", "update" FROM news ORDER BY newsIndex DESC';
    error_log("[getNews] Executing query: $sqlGetNews");
    $sqlNewsResult = sql_query($sqlGetNews, $conn);
    error_log("[getNews] Query executed, result: " . ($sqlNewsResult ? "success" : "failed"));
    if ($sqlNewsResult) {
        $count = 0;
        while(($row = mysqli_fetch_array($sqlNewsResult,MYSQLI_ASSOC))){
            $output[] = $row;
            $count++;
        }
        error_log("[getNews] Fetched $count news items");
    }
    return $output;
}

// -- Function Name : getStatus
// -- Params : $acc, $conn
// -- Purpose : Returns an array containing all display information needed for char
function getStatusBar($acc, $conn){
    $charRow = getRow($conn, "character", $acc);
    $calcRow = getRow($conn, "calcValues", $acc);
    $userName = getAttribute($conn, "account", "account", $acc);

    $output["level"] = $charRow['level'];
    $output["class"] = $charRow['class'];
    $output["headString"] = $userName.", The level ".$output["level"]." ".$output["class"];

    $output["mana"] = $charRow['mana'];
    $output["manaString"] =  number_format($charRow['mana'])."/".number_format($calcRow["maxMana"]);
    if($charRow['mana'] > 0){
        $output["manaOffset"] = (($calcRow["maxMana"] - $charRow['mana']) / $maxMana) * 234 + 24;
    }else{
        $output["manaOffset"] = "-265px";
    }

    $output["HP"] = $charRow['hitpoints'];
    $output["HPString"] =  number_format($charRow['hitpoints'])."/".number_format($calcRow["maxHealth"]);
    if($charRow['hitpoints'] > 0){
        $output["HPOffset"] = (($calcRow["maxHealth"] - $charRow['hitpoints']) / $maxMana) * 234 + 24;
    }else{
        $output["HPOffset"] = "-265px";
    }

    $output["experience"] = $charRow['exp'];
    $output["experienceString"] =  number_format($charRow['exp'])."/".number_format($calcRow["next"]);
    if($charRow['exp'] > 0){
        $output["experienceOffset"] = (($calcRow["next"] - $charRow['exp']) / $maxMana) * 797 + 14;
    }else{
        $output["experienceOffset"] = "-815px";
    }

    $output["silverString"] = "Silver: ".$charRow["silver"];
    $output["silver"] = $charRow["silver"];
    $output["expString"] = $charRow['exp'].' / '.$charRow['next'];

    $sql = "select script, \"image\", remaining from playerbuffs where name != 'empty' and playerid in ($acc, -1) order by itemID";
    $rowset = sql_query($sql, $conn);
    $output["buffs"] = null;

    while($row = mysqli_fetch_array($rowset,MYSQLI_ASSOC)){
        $output["buffs"][] = $row;
    }
    return $output;
}

// -- Function Name : getInventory
// -- Params : $acc, $conn
// -- Purpose : Returns a json object containing all inventory items for a char
function getInventory($acc, $conn){
    $output = array();
    $sql = "SELECT item_id as itemid, COALESCE(count,0) as count, COALESCE(used,0) as used, COALESCE(archived,0) as archived, name, \"image\", value, usable, combat, quest, equipment, value, description, visible from item t inner join inventory i on t.item_id = i.itemid and playerid = $acc order by name";
    $result = sql_query($sql, $conn);
    while($row = mysqli_fetch_array($result,MYSQLI_ASSOC)){
        $output[] = $row;
    }
    return $output;
}

// -- Function Name : getEquipment
// -- Params : $acc, $conn
// -- Purpose : Returns a json object containing all equipment not archived
function getEquipment($acc, $conn){

    $output = array();
    $keys['str'] = "Strength: ";
    $keys['dex'] = "Dexterity: ";
    $keys['spr'] = "Spirit: ";
    $keys['vit'] = "Vitality: ";
    $keys['minDmg'] = "Bonus Min Damage: ";
    $keys['maxDmg'] = "Bonus Max Damage: ";
    $keys['armor'] = "Bonus Armor: ";
    $keys['fireRes'] = "Fire Resistance: ";
    $keys['iceRes'] = "Ice Resistance: ";
    $keys['arcaneRes'] = "Arcane Resistance: ";
    $keys['earthRes'] = "Earth Resistance: ";
    $keys['holyRes'] = "Holy Resistance: ";
    $keys['maxHP'] = "Maximum HP: ";
    $keys['maxMP'] = "Maximum MP: ";
    $keys['evasion'] = "Evasion: ";
    $keys['itemDrop'] = "Item Drop Increase: ";
    $keys['silverDrop'] = "Silver Drop Increase: ";
    $keys['critChance'] = "Increased Critical Rate ";
    $keys['critDamage'] = "Bonus Crit Modifier: ";
    $keys['blockChance'] = "Block Chance: ";

    $sql = "SELECT * FROM equipmentinventory where playerid = $acc and archived = 0 and name != 'unarmed' order by name;";
    $result = sql_query($sql, $conn);
    while($row = mysqli_fetch_array($result,MYSQLI_ASSOC)){
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
        foreach ($keys as $key => $value) {

            if($row[$key] != "0"){
                $script .= "<strong>" . $value . "</strong>".$row[$key];
                if(in_array($key, ['fireRes', 'iceRes', 'arcaneRes', 'earthRes', 'holyRes',
                    'itemDrop', 'silverDrop', 'critChance', 'critDamage', 'blockChance'])){
                        $script .= "%";
                    }
                $script .= "<br/>";
            }else{
                unset($row[$key]);
            }
        }

        $row['script'] = $script;
        $output[] = $row;
    }
    return $output;
}

// -- Function Name : getCalcStats
// -- Params : $acc, $conn
// -- Purpose : Returns information for Character panel
function getCalcStats($acc, $conn){
    $user = getAttribute($conn, "account", "account", $acc);
    $charRow = getRow($conn, "character", $acc);
    $calcRow = getRow($conn, "calcvalues", $acc);

    $calcRow["strength"] = $charRow["strength"];
    $calcRow["vitality"] = $charRow["vitality"];
    $calcRow["dexterity"] = $charRow["dexterity"];
    $calcRow["spirit"] = $charRow["spirit"];

    $calcRow["strengthBonus"] = $calcRow["str"] - $charRow["strength"];
    $calcRow["vitalityBonus"] = $calcRow["vit"] - $charRow["vitality"];
    $calcRow["dexterityBonus"] = $calcRow["dex"] - $charRow["dexterity"];
    $calcRow["spiritBonus"] = $calcRow["spr"] - $charRow["spirit"];

    $calcRow["class"] = $charRow["class"];
    $calcRow["account"] = $user;
    $calcRow["reset"] = $charRow["resetScript"];

    $block = floor($calcRow["block"]) * 100;
    if($equippedShield != 0){
        $sql = "SELECT * FROM charSkills s inner join skillLevels l on s.skillID = l.skillID where playerid = $acc and l.level = s.level and s.skillID = 9";
        $result = sql_query($sql, $conn);
        $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
        $block = $block + ($row['damage']);
    }
    $calcRow["block"] = $block;

    $calcRow['critRate'] = floor($calcRow['critRate'] * 100);
    $calcRow['critMulti'] = floor($calcRow['critMulti'] * 100);

    $calcRow['combatItems'] = getEquippedItems($acc, $conn);

    unset($calcRow["playerid"]);
    unset($calcRow["damage"]);
    unset($calcRow["maxMana"]);
    unset($calcRow["maxHealth"]);
    unset($calcRow["spr"]);
    unset($calcRow["str"]);
    unset($calcRow["vit"]);
    unset($calcRow["dex"]);
    unset($calcRow["minDmg"]);
    unset($calcRow["maxDmg"]);
    unset($calcRow["weapon"]);
    unset($calcRow["itemDrop"]);
    unset($calcRow["silverDrop"]);
    unset($calcRow["bonusPotHeal"]);
    unset($calcRow["bonusPotMana"]);
    unset($calcRow["expDrop"]);
    unset($calcRow["healthPerc"]);
    unset($calcRow["manaPerc"]);
    unset($calcRow["strPerc"]);
    unset($calcRow["dexPerc"]);
    unset($calcRow["sprPerc"]);
    unset($calcRow["vitPerc"]);
    unset($calcRow["spellReduction"]);
    unset($calcRow["weapElement"]);

    return $calcRow;
}

// -- Function Name : getEquippedItems
// -- Params : $conn, $acc
// -- Purpose : Returns the items relating to provided account
function getEquippedItems($acc, $conn){
    //  --------------------------------------------------------------------------------------------------
    //
    //  --------------------------------------------------------------------------------------------------
    $output = array();
    $row = getRow($conn, "equippedStuff", $acc);
    $counter = 1;
    while($counter != 5){
        $item = $row['item_'.$counter];
        if($item != -1){
            $sql = "SELECT item_id as itemid, COALESCE(count,0) as count, COALESCE(used,0) as used, COALESCE(archived,0) as archived, name, image, value, usable, combat, quest, equipment, value, description, visible from item t inner join inventory i on t.item_id = i.itemid and playerid = $acc and i.itemid = $item";
            $result = sql_query($sql, $conn);
            $output[] = mysqli_fetch_array($result,MYSQLI_ASSOC);
        } else {
            $output[]["itemid"] = -1;
        }
        $counter++;
    }
    return $output;
}

// -- Function Name : getEquippedSkills
// -- Params : $conn, $acc
// -- Purpose : Returns the items relating to provided account
function getEquippedSkills($acc, $conn){
    //  --------------------------------------------------------------------------------------------------
    //
    //  --------------------------------------------------------------------------------------------------
    $output = array();
    $row = getRow($conn, "equippedStuff", $acc);
    $counter = 1;
    while($counter != 5){
        $skill = $row['skill_'.$counter];
        if($skill != -1){
            $sql = "select c.skillID, c.level, s.name, s.image, s.type, l.cost, l.script from charSkills c inner join skills s on c.skillID = s.\"id\" inner join skillLevels l on c.skillID = l.skillID and c.level = l.level where playerid = $acc and c.skillID = $skill";
            $result = sql_query($sql, $conn);
            $output[] = mysqli_fetch_array($result,MYSQLI_ASSOC);
        }else{
            $output[]["skillID"] = -1;
        }
        $counter++;
    }
    return $output;
}
?>
