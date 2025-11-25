<?php
// ==========================================================
// 1. OBTENCIÓN DE VARIABLES DE ENTORNO usando $_SERVER o $_ENV
// Esto es más confiable que getenv() en algunos entornos de GAE.
// ==========================================================
$cloud_sql_conn_name = $_SERVER['CLOUD_SQL_CONNECTION_NAME'] ?? $_ENV['CLOUD_SQL_CONNECTION_NAME'] ?? null;
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? 'root';
$db_pass = $_SERVER['DB_PASS'] ?? $_ENV['DB_PASS'] ?? '';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? 'protect';


if ($cloud_sql_conn_name) {
    $dsn = "mysql:unix_socket=/cloudsql/$cloud_sql_conn_name;dbname=$db_name";
    
    
} else {
  
    $db_host_local = '127.0.0.1'; 
    $db_user_local = 'root'; 
    $db_pass_local = ''; 
    $db_name_local = 'protect';

    $dsn = "mysql:host=$db_host_local;dbname=$db_name_local";
    $db_user = $db_user_local;
    $db_pass = $db_pass_local;
}

try {
    $conn = new PDO($dsn, $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("set names utf8mb4"); 
    
} catch (PDOException $e) {
    if (!$cloud_sql_conn_name) {
        die("Error de conexión local: " . $e->getMessage()); 
    }
    error_log("Falla en la conexión de producción Cloud SQL: " . $e->getMessage());
    die("Error interno del servidor. Por favor, inténtelo de nuevo más tarde.");
}
?>