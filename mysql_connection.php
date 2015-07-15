<?php
error_reporting(E_ALL);
$servername = "mysql51-011.wc1.dfw1.stabletransit.com";
$mysql_username = "651898_formstk";
$dbname = "651898_formstack2";
$mysql_password = "2C+=44XhET29uwHe";

$conn = new mysqli($servername, $mysql_username, $mysql_password, $dbname);
if ($conn->connect_error) 
{
	die("Connection failed: " . $conn->connect_error);
}
?>
