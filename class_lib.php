<?php
	// Include centralized configuration
	require_once(__DIR__ . '/config.php');

	class player{
		var $playerid;
		var $locationX;
		var $locationY;
		var $conn;
		var $name;
		var $type;
		var $price;
		var $slot;
		var $equipped;
		var $amount;
		var $amountArchived;
		var $amountStored;
		var $items;

		function __construct($acc, $itemid) {

			$this->itemid = $itemid;
			$this->playerid = $acc;

			$mysqli = get_db_connection();
			$items = array();

			if ($result = $mysqli->query("SELECT * from account a inner join `character` c on c.playerid = a.playerid where a.playerid = 437")) {
				while ($row = $result->fetch_object()){
					//$this = $row;
				}
			}

			/*
			if ($result = $mysqli->query("SELECT i.itemid, i.count, i.used, i.archived, i.stored, name, image, value, `description`, usable, combat, quest, visible FROM inventory i inner join item t on i.itemid = t.item_ID where playerid = $acc")) {
				while ($row = $result->fetch_object()){
					$items[] = $row;
				}
			}
			$this->items = $items;
			*/
		}
	}
	// Database connection function now in config.php
?>
