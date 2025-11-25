<?php
// Obtener variables de entorno
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$dsn_prod = getenv('DB_DSN');

if ($dsn_prod) {
    // ESTAMOS EN PRODUCCIÓN (usamos el DSN completo definido en env_variables.yaml)
    $dsn = $dsn_prod;
} else {
    // ESTAMOS EN DESARROLLO LOCAL
    $db_host_local = '127.0.0.1'; 
    $db_user_local = 'root'; 
    $db_pass_local = ''; 
    $db_name_local = 'protect';

    $dsn = "mysql:host=$db_host_local;dbname=$db_name_local";
    
    // Usamos las credenciales locales
    $db_user = $db_user_local;
    $db_pass = $db_pass_local;
}

// Establecer la conexión usando PDO
try {
    $conn = new PDO($dsn, $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("set names utf8mb4"); 
    
} catch (PDOException $e) {
    // Es mejor registrar el error que mostrarlo directamente al usuario
    error_log("Database connection error: " . $e->getMessage()); 
    die("Error interno del servidor. Por favor, inténtelo de nuevo más tarde.");
}
?>