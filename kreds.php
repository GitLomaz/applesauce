<html>
<head>
<style>
table {
color: #333; /* Lighten up font color */
font-family: Helvetica, Arial, sans-serif; /* Nicer font */
width: 840px;
border-collapse:
collapse; border-spacing: 0;
}

td, th { border: 1px solid #CCC; height: 30px; } /* Make cells a bit taller */

th {
background: #F3F3F3; /* Light grey background */
font-weight: bold; /* Make sure they're bold */
}

td {
background: #FAFAFA; /* Lighter grey background */
text-align: center; /* Center our text */
}
table{
margin: 0 auto;
}
</style>
</head>
<body>
<?php
        include_once('common_lib.php');
        $conn = sql_connect();

        if($_SERVER['QUERY_STRING'] == "applesauce"){
             $sql .= "SELECT ";
             $sql .= "    a.account AS account, t.*, i.itemName, 'Kal-Rul' AS game ";
             $sql .= "FROM ";
             $sql .= "    kalrul.kredsTransactions t";
             $sql .= "        INNER JOIN";
             $sql .= "    kalrul.kredsItems i ON i.itemID = t.storeID";
             $sql .= "        INNER JOIN";
             $sql .= "    kalrul.account a ON a.playerID = t.playerID ";
             $sql .= "UNION (SELECT ";
             $sql .= "    a.username AS account,";
             $sql .= "    t.*,";
             $sql .= "    i.itemName,";
             $sql .= "    'Periodically Idle' AS game ";
             $sql .= "FROM ";
             $sql .= "    periodicClicker.kredsTransactions t";
             $sql .= "        INNER JOIN";
             $sql .= "    periodicClicker.kredsItems i ON i.itemID = t.storeID";
             $sql .= "        INNER JOIN";
             $sql .= "    periodicClicker.users a ON a.userID = t.playerID)";
             $sql .= "UNION (SELECT ";
             $sql .= "    a.account,";
             $sql .= "    t.*,";
             $sql .= "    i.itemName,";
             $sql .= "    'Badge Emperor' AS game ";
             $sql .= "FROM ";
             $sql .= "    badge.kredsTransactions t";
             $sql .= "        INNER JOIN";
             $sql .= "    badge.kredsItems i ON i.itemID = t.storeID";
             $sql .= "        INNER JOIN";
             $sql .= "    badge.users a ON a.userID = t.playerID)  ";
             $sql .= "    ORDER BY lastUpdated DESC";
             $result = sql_query($sql, $conn);

             echo "<table border='1'>
             <tr>
             <th>USER</th>
             <th>ITEM</th>
             <th>KREDS</th>
             <th>TIMESTAMP</th>
             <th>GAME</th>
             </tr>";

             while($row = mysqli_fetch_array($result))
             {
             echo "<tr>";
             echo "<td>" . $row['account'] . "</td>";
             echo "<td>" . $row['itemName'] .  "</td>";
             echo "<td>" . $row['Kreds'] .  "</td>";
             echo "<td>" . $row['lastUpdated'] .  "</td>";
             echo "<td>" . $row['game'] .  "</td>";
             echo "</tr>";
             }
             echo "</table>";

     }

     mysqli_close($conn);
?>
</body>
</html>
