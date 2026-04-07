<?php
	// Include centralized configuration
	require_once(__DIR__ . '/config.php');

	class player{
		var $playerID;
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

		function __construct($acc, $itemID) {

			$this->itemID = $itemID;
			$this->playerID = $acc;

			$mysqli = get_db_connection();
			$items = array();

			if ($result = $mysqli->query("SELECT * from account a inner join `character` c on c.playerID = a.playerID where a.playerID = 437")) {
				while ($row = $result->fetch_object()){
					//$this = $row;
				}
			}

			/*
			if ($result = $mysqli->query("SELECT i.itemID, i.count, i.used, i.archived, i.stored, name, image, value, `description`, usable, combat, quest, visible FROM inventory i inner join item t on i.itemID = t.item_ID where playerID = $acc")) {
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
