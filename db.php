<?php
// ==========================================================
// 1. OBTENCIÓN DE VARIABLES DE ENTORNO
// Estas variables son inyectadas por Google App Engine (GAE)
// ==========================================================
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_name = getenv('DB_NAME');
$socket_path = getenv('CLOUDSQL_SOCKET_PATH');

// ==========================================================
// 2. CONSTRUCCIÓN DE LA CADENA DE CONEXIÓN (DSN)
// La conexión varía si estamos en GAE o en un entorno local
// ==========================================================
if ($socket_path) {
    // ESTAMOS EN EL ENTORNO DE PRODUCCIÓN (GOOGLE APP ENGINE)
    // GAE requiere la conexión mediante un socket Unix.
    // El formato del DSN es: mysql:unix_socket=/cloudsql/NOMBRE_CONEXION;dbname=NOMBRE_DB
    $dsn = "mysql:unix_socket=/cloudsql/$socket_path;dbname=$db_name";

} else {
    // ESTAMOS EN UN ENTORNO DE DESARROLLO LOCAL (XAMPP, WAMP, etc.)
    // La conexión es normal, usando el host y puerto.

    // *** IMPORTANTE: AJUSTA ESTAS LÍNEAS CON TUS CREDENCIALES LOCALES ***
    $db_host_local = '127.0.0.1'; 
    $db_user_local = 'root'; 
    $db_pass_local = ''; // Contraseña vacía por defecto en XAMPP/WAMP
    $db_name_local = 'protect';

    $dsn = "mysql:host=$db_host_local;dbname=$db_name_local";
    
    // Si estás en local, usamos las credenciales locales
    $db_user = $db_user_local;
    $db_pass = $db_pass_local;
}

// ==========================================================
// 3. ESTABLECER LA CONEXIÓN USANDO PDO
// ==========================================================
try {
    $conn = new PDO($dsn, $db_user, $db_pass);
    
    // Configuración para que PDO lance excepciones en caso de error
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Configuración de caracteres para soportar UTF8 (como tu DB)
    $conn->exec("set names utf8mb4"); 
    
} catch (PDOException $e) {
    // Si la conexión falla, se detiene la ejecución y se muestra un error.
    // NUNCA muestres $e->getMessage() directamente al usuario final en producción!
    die("Error de conexión a la base de datos.");
    // Para depuración local, puedes usar: die("Error de conexión: " . $e->getMessage());
}

// Ahora, cualquier archivo PHP puede incluir db.php y usar la variable $conn
// ==========================================================
?>