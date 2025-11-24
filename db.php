<?php

define('DB_SERVER', 'db'); 
define('DB_USERNAME', 'admin'); 
define('DB_PASSWORD', 'molotov'); 
define('DB_NAME', 'hacer1u1_db'); 

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("ERROR: No se pudo conectar a la base de datos. " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

?>