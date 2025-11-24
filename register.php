<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'db.php'; 

require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';


$nombre = $correo = $usuario = $pwd = "";
$nombre_err = $correo_err = $usuario_err = $pwd_err = $register_err = ""; 
$register_success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST["nombre"]);
    if (empty($nombre)) {
        $nombre_err = "Por favor, ingresa tu nombre.";
    }

    $correo = trim($_POST["correo"]);
    if (empty($correo)) {
        $correo_err = "Por favor, ingresa tu correo.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $correo_err = "El formato del correo electrónico no es válido.";
    } else {
        $sql = "SELECT id FROM usuarios WHERE correo = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_correo);
            $param_correo = $correo;
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $correo_err = "Este correo electrónico ya está registrado.";
                }
            } else {
                $register_err = "Error al verificar el correo. Intenta más tarde.";
            }
            $stmt->close();
        }
    }

    $usuario = trim($_POST["usuario"]);
    if (empty($usuario)) {
        $usuario_err = "Por favor, ingresa un nombre de usuario.";
    } else {
        $sql = "SELECT id FROM usuarios WHERE usuario = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_usuario);
            $param_usuario = $usuario;
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $usuario_err = "Este usuario ya existe.";
                }
            } else {
                $register_err = "Error al verificar el usuario. Intenta más tarde.";
            }
            $stmt->close();
        }
    }

    $pwd = trim($_POST["pwd"]);
    if (empty($pwd)) {
        $pwd_err = "Por favor, ingresa una contraseña.";
    } elseif (strlen($pwd) < 3) {
        $pwd_err = "La contraseña debe tener al menos 3 caracteres.";
    }


    if (empty($nombre_err) && empty($correo_err) && empty($usuario_err) && empty($pwd_err) && empty($register_err)) {
        $sql = "INSERT INTO usuarios (nombre, correo, usuario, pwd) VALUES (?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssss", $nombre, $correo, $usuario, $pwd);
            
            if ($stmt->execute()) {
                
                $_SESSION['registration_success'] = "¡Registro exitoso! Ya puedes iniciar sesión.";
                header("location: login.php");
                exit;
                
            } else {
                $register_err = "Error al registrar el usuario en la base de datos. Intenta de nuevo.";
            }
            $stmt->close();
        } else {
            $register_err = "Error al preparar la consulta de inserción. Intenta de nuevo. Detalle: " . $conn->error;
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-image: url('assets/img/header-bg.jpg');
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: white;
        }

        .login-container {
            background-color: #212529;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.95);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-container h2 {
            margin-bottom: 25px;
        }

        .login-container label {
            display: block;
            margin-bottom: 8px;
            text-align: left;
            font-weight: bold;
        }

        .login-container input[type="text"],
        .login-container input[type="password"],
        .login-container input[type="email"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            color: black;
        }

        .login-container button[type="submit"],
        .login-container .button-secondary {
            background-color: #ffc800;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background-color 0.3s ease;
            margin-bottom: 10px;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        .login-container button[type="submit"]:hover,
        .login-container .button-secondary:hover {
            background-color: #e0b000;
        }

        .error-message {
            color: #ff6b6b;
            margin-top: -10px;
            margin-bottom: 15px;
            font-size: 0.9em;
        }
        
        .success-message {
            color: #25d366; 
            margin-bottom: 15px;
            font-size: 1em;
            font-weight: bold;
        }
        /* 4. ELIMINADO: Todo el CSS para el overlay #captcha-overlay */
    </style>

    <script>
        window.addEventListener('mouseover', initLandbot, { once: true });
        window.addEventListener('touchstart', initLandbot, { once: true });
        var myLandbot;
        function initLandbot() {
            if (!myLandbot) {
                var s = document.createElement('script');
                s.type = "module"
                s.async = true;
                s.addEventListener('load', function() {
                    var myLandbot = new Landbot.Livechat({
                        configUrl: 'https://storage.googleapis.com/landbot.online/v3/H-3012245-8I6LDS9A7NY1FM5I/index.json',
                    });
                });
                s.src = 'https://cdn.landbot.io/landbot-3/landbot-3.0.0.mjs';
                var x = document.getElementsByTagName('script')[0];
                x.parentNode.insertBefore(s, x);
            }
        }
    </script>
</head>
<body>
    <div class="login-container">
        <h2>Crear cuenta</h2>
        <?php 
        if (!empty($register_err)) {
            echo '<div class="error-message">' . $register_err . '</div>';
        }
        if (!empty($register_success_message)) {
            echo '<div class="success-message">' . $register_success_message . '</div>';
        }
        ?>
        <form id="registerForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <label for="nombre">Nombre completo</label>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>
            <?php if (!empty($nombre_err)) echo '<div class="error-message">' . $nombre_err . '</div>'; ?>

            <label for="correo">Correo</label>
            <input type="email" name="correo" value="<?php echo htmlspecialchars($correo); ?>" required>
            <?php if (!empty($correo_err)) echo '<div class="error-message">' . $correo_err . '</div>'; ?>

            <label for="usuario">Usuario</label>
            <input type="text" name="usuario" value="<?php echo htmlspecialchars($usuario); ?>" required>
            <?php if (!empty($usuario_err)) echo '<div class="error-message">' . $usuario_err . '</div>'; ?>

            <label for="pwd">Contraseña</label>
            <input type="password" name="pwd" required>
            <?php if (!empty($pwd_err)) echo '<div class="error-message">' . $pwd_err . '</div>'; ?>

            <button type="submit">Registrarme</button>
            <p style="margin-top: 10px;">¿Ya tienes una cuenta?  
                <button type="button" onclick="location.href='login.php'" class="button-secondary">
                Inicia sesión aqui! 
            </button></p>
        </form>
    </div>

    </body>
</html>