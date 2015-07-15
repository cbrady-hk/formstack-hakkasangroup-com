<?php

include("mysql_connection.php");

$query = "SELECT * FROM Requests";

$result = $conn->query($query);
echo "<html>";

echo "<head><style>";

echo "div.cell {display: table-cell;}";
echo "div.row,div.header {display: table-row;}";

echo "</style></head>";
echo "<body><div id='content'>";
echo "<div class='header'>";
	echo "<div class='cell'>ID</div>";
	echo "<div class='cell'>Time/Date</div>";
	echo "<div class='cell'>FormstackRequest</div>";
	echo "<div class='cell'>SentRequest</div>";
	echo "<div class='cell'>Response</div>";
	echo "<div class='cell'>Status</div>";

echo "</div>";
while($row = $result->fetch_assoc()) {
	echo "<div class='row'>";
	echo "<div class='cell'><textarea>" . $row['RequestID'] . "</textarea></div>";
	echo "<div class='cell'><textarea>" . $row['Timestamp'] . "</textarea></div>";
	echo "<div class='cell'><textarea>" . $row['FormstackRequest'] . "</textarea></div>";
	echo "<div class='cell'><textarea>" . $row['SentRequest'] . "</textarea></div>";
	echo "<div class='cell'><textarea>" . $row['Response'] . "</textarea></div>";
	echo "<div class='cell'><textarea>" . $row['Status'] . "</textarea></div>";
	echo "</div>";
	
}
echo "</div></body><html>";


?>