<?php

$servername = "localhost";

$username = "root";
$password = "nebula";
$db_name = "nebulax_task";

try {
    $connection = new PDO("mysql:host=$servername;db=$db_name", $username, $password);
    // set the PDO error mode to exception
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $error) {
    send_response(['message' => "Failed connection to the DB! , " . $error->getMessage()], 500);
}
