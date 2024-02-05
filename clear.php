<?php

include 'database.php';
include 'password.php';
$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error)
    die('Could not connect: ' . $conn->connect_error);

$sql = "DELETE FROM www_index;";
echo ($sql . "\n");
if ($conn->query($sql) == TRUE)
    echo "Table www_index deleted successfully\n\n";
else
    echo "Error deleting table: " . $conn->error;

$sql = "DELETE FROM keywords;";
echo ($sql . "\n");
if ($conn->query($sql) == TRUE)
    echo "Table keywords deleted successfully\n\n";
else
    echo "Error deleting table: " . $conn->error;

$sql = "DELETE FROM url_title;";
echo ($sql . "\n");
if ($conn->query($sql) == TRUE)
    echo "Table url_title deleted successfully";
else
    echo "Error deleting table: " . $conn->error;

$conn->close();

?>