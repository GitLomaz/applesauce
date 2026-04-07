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
$result = sql_query("SELECT * from `kills` order by `kills`.`deaths` desc", $conn);

echo "<table border='1'>
<tr>
<th>ENEMY</th>
<th>KILLS</th>
</tr>";

while($row = mysqli_fetch_array($result))
{
echo "<tr>";
echo "<td>" . $row['name'] . "</td>";
echo "<td>" .   number_format($row['deaths']) .  "</td>";
echo "</tr>";
}
echo "</table>";

mysqli_close($conn);
?>
</body>
</html>