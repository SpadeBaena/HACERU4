<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'db.php'; 

require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';


$nombre = $correo = $usuario = $pwd = "";
$nombre_err = $correo_err = $usuario_err = $pwd_err = $register_err = $captcha_err = "";
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

    if (empty($_POST['g-recaptcha-response'])) {
        $captcha_err = "Captcha no detectado.";
    } else {
        $captcha_response = $_POST['g-recaptcha-response'];
        $secret_key = "6LeQ-morAAAAAEMu2oSPX4OUhxkcKEgVqGQGOxvL"; 
        $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret_key}&response={$captcha_response}");
        $captcha_success = json_decode($verify);

        if (!$captcha_success->success || $captcha_success->score < 0.5) {
            $captcha_err = "Verificación de captcha fallida. Por favor, intenta de nuevo.";
        }
    }

    if (empty($nombre_err) && empty($correo_err) && empty($usuario_err) && empty($pwd_err) && empty($captcha_err) && empty($register_err)) {
        $token_activacion = bin2hex(random_bytes(32));

        $sql = "INSERT INTO usuarios (nombre, correo, usuario, pwd, activo, token_activacion) VALUES (?, ?, ?, ?, 0, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssss", $nombre, $correo, $usuario, $pwd, $token_activacion);
            
            if ($stmt->execute()) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com'; 
                    $mail->SMTPAuth = true;
                    $mail->Username = 'baenaspoti@gmail.com';
                    $mail->Password = 'qlnm qgtj ifwa nbqi'; 
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;
                    $mail->CharSet = 'UTF-8';

                    $mail->setFrom('baenaspoti@gmail.com', 'Protect-U');
                    $mail->addAddress($correo);

                    $mail->isHTML(true);
                    $mail->Subject = 'Activa tu cuenta en Portect-U';
                    
                    $activation_link = "http://localhost:8080/SH1/activate_account.php?token=" . $token_activacion; 

                    $mail->Body = "
                        <h3>¡Bienvenido a Protect-U, {$nombre}!</h3>
                        <p>Gracias por registrarte. Para activar tu cuenta y cotizar con nosotros, por favor haz clic en el siguiente enlace:</p>
                        <p><a href='{$activation_link}'>Activar mi Cuenta</a></p>
                        <p>Si no te registraste en nuestro sitio, puedes ignorar este correo.</p>
                        <br>
                        <strong>Atentamente,<br>El equipo de Protect-U</strong>
                    ";

                    $mail->send();
                    $register_success_message = "¡Registro exitoso! Se ha enviado un enlace de activación a tu correo electrónico ({$correo}). Por favor, revisa tu bandeja de entrada y activa tu cuenta!";
                } catch (Exception $e) {
                    $register_err = "Error al enviar el correo de activación. Tu cuenta ha sido creada, pero necesitas activarla. Por favor, contacta a soporte o intenta registrarte de nuevo si el problema persiste. Error: " . $e->getMessage();
                }
            } else {
                $register_err = "Error al registrar el usuario en la base de datos. Intenta de nuevo.";
            }
            $stmt->close();
        } else {
            $register_err = "Error al preparar la consulta de inserción. Intenta de nuevo.";
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

        #captcha-overlay {
            display: none; 
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.65);
            z-index: 9999;
            justify-content: center; 
            align-items: center;    
            backdrop-filter: blur(4px);
        }

        #captcha-overlay div {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 20px 30px;
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            font-weight: 500;
            text-align: center;
            animation: fadeIn 0.4s ease-in-out;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.1);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>

    <script src="https://www.google.com/recaptcha/api.js?render=6LeQ-morAAAAACu5_QUwv5RFb7qRfXtuq-RKGB-X"></script>

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
        if (!empty($captcha_err)) {
            echo '<div class="error-message">' . $captcha_err . '</div>';
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

            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

            <button type="submit">Registrarme</button>
            <p style="margin-top: 10px;">¿Ya tienes una cuenta?  
                  <button type="button" onclick="location.href='login.php'" class="button-secondary">
                Inicia sesión aqui! 
            </button></p>
    </div>

    <div id="captcha-overlay">
        <div>🧠 Verificando que no eres un robot...</div>
    </div>

    <script>
        function onRecaptchaLoad() {
            console.log("reCAPTCHA script loaded and ready.");
            document.getElementById("captcha-overlay").style.display = "flex"
            grecaptcha.execute('6LeQ-morAAAAACu5_QUwv5RFb7qRfXtuq-RKGB-X', {action: 'register'})
                .then(function(token) {
                    console.log("reCAPTCHA token obtained:", token);
                    document.getElementById('g-recaptcha-response').value = token;
                    document.getElementById("captcha-overlay").style.display = "none"; 
                })
                .catch(function(error) {
                    console.error("Error obtaining reCAPTCHA token:", error);
                    document.getElementById("captcha-overlay").style.display = "none"; 
                    alert("No se pudo completar la verificación de seguridad. Por favor, intenta de nuevo.");
                });
        }

       
        grecaptcha.ready(function() {
            document.getElementById("captcha-overlay").style.display = "flex";
            grecaptcha.execute('6LeQ-morAAAAACu5_QUwv5RFb7qRfXtuq-RKGB-X', {action: 'register'}).then(function(token) {
                document.getElementById('g-recaptcha-response').value = token;
                document.getElementById("captcha-overlay").style.display = "none";
            }).catch(function(error) {
                console.error("Error en reCAPTCHA.ready:", error);
                document.getElementById("captcha-overlay").style.display = "none";
                alert("Hubo un problema con la verificación de seguridad. Por favor, recarga la página e intenta de nuevo.");
            });
        });
        
       </script>
       
    </script>
</body>
</html>