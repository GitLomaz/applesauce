<html>
<head>
<style>
table { 
color: #333; /* Lighten up font color */
font-family: Helvetica, Arial, sans-serif; /* Nicer font */
width: 640px; 
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

echo "<table border='1'>
<tr>
<th>TIMESTAMP</th>
<th>ACTION COUNT</th>
</tr>";

$sql = "SELECT * FROM dailyactions";
$sql_rows = sql_query($sql, $conn);
while($row = mysqli_fetch_array($sql_rows,MYSQLI_ASSOC)){
	$day = $row["Day"];
	$total = $row["Total"];
	echo "<tr>";
	echo "<td>$day</td>";
	echo "<td>$total</td>";
	echo "</tr>";
}

echo "</table>";

mysqli_close($conn);
?>
</body>
</html>
