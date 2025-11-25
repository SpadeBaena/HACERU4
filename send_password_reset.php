<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'db.php';


require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

$email = "";
$email_err = "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);

    if (empty($email)) {
        $email_err = "Por favor, ingresa tu dirección de correo electrónico.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_err = "El formato del correo electrónico no es válido.";
    }

    if (empty($email_err)) {
        
        $sql = "SELECT id, nombre, activo FROM usuarios WHERE correo = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = $email;

            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($user_id, $user_name, $user_active);
                    $stmt->fetch();

                    if ($user_active == 0) {
                        $message = "Tu cuenta no está activa. Por favor, revisa tu correo para activarla.";
                    } else {
                        $reset_token = bin2hex(random_bytes(32)); 
                        $expire_time = date("Y-m-d H:i:s", strtotime('+1 hour'));

                        $update_sql = "UPDATE usuarios SET reset_token = ?, reset_token_expire_at = ? WHERE id = ?";
                        if ($update_stmt = $conn->prepare($update_sql)) {
                            $update_stmt->bind_param("ssi", $reset_token, $expire_time, $user_id);
                            if ($update_stmt->execute()) {
                                $mail = new PHPMailer(true);
                                try {
                                    // Configuración SMTP (TU INFORMACIÓN)
                                    $mail->isSMTP();
                                    $mail->Host = 'smtp.gmail.com'; 
                                    $mail->SMTPAuth = true;
                                    $mail->Username = 'baenaspoti@gmail.com'; // <-- TU CORREO DE ENVÍO
                                    $mail->Password = 'qlnm qgtj ifwa nbqi'; // <-- TU CONTRASEÑA DE APLICACIÓN DE GMAIL (la generada en Google)
                                    $mail->SMTPSecure = 'tls';
                                    $mail->Port = 587;
                                    $mail->CharSet = 'UTF-8';

                                    $mail->setFrom('baenaspoti@gmail.com', 'Protect-U'); 
                                    $mail->addAddress($email);

                                    $mail->isHTML(true);
                                    $mail->Subject = 'Restablecer tu contraseña de Protect-U';
                                    
                                  
                                    $reset_link = "http://localhost:8080/SH1/reset_password.php?token=" . $reset_token; 

                                    $mail->Body = "
                                        <h3>Hola, {$user_name}!</h3>
                                        <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en Protect-U.</p>
                                        <p>Para restablecer tu contraseña, haz clic en el siguiente enlace:</p>
                                        <p><a href='{$reset_link}'>Restablecer mi Contraseña</a></p>
                                        <p>Este enlace expirará en 1 hora.</p>
                                        <p>Si no solicitaste este restablecimiento, puedes ignorar este correo.</p>
                                        <br>
                                        <strong>Atentamente,<br>El equipo de Portect-U</strong>
                                    ";

                                    $mail->send();
                                    $message = "Recibirás un enlace para restablecer tu contraseña en breve. Por favor, revisa tu bandeja de entrada y la carpeta de spam.";
                                } catch (Exception $e) {
                                    $message = "Error al enviar el correo de restablecimiento. Por favor, intenta de nuevo más tarde o contacta a soporte.";
                                    
                                }
                            } else {
                                $message = "Error al generar el token de restablecimiento. Por favor, intenta de nuevo.";
                            }
                            $update_stmt->close();
                        } else {
                            $message = "ERROR: No se pudo preparar la consulta para actualizar el token.";
                        }
                    }
                } else {
                    // Por seguridad, no decimos si el correo existe o no
                    $message = "Si tu dirección de correo electrónico está registrada con nosotros, recibirás un enlace para restablecer tu contraseña en breve. Por favor, revisa tu bandeja de entrada y la carpeta de spam.";
                }
            } else {
                $message = "¡Ups! Algo salió mal al verificar el correo.";
            }
            $stmt->close();
        } else {
            $message = "ERROR: No se pudo preparar la consulta SQL para verificar el correo.";
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
    <title>Olvidé mi Contraseña</title>
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
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Restablecer Contraseña</h2>
        <?php 
        if (!empty($email_err)) {
            echo '<div class="error-message">' . $email_err . '</div>';
        }
        if (!empty($message)) {
            if (strpos($message, "Si tu dirección de correo") !== false || strpos($message, "Tu cuenta no está activa") !== false) {
                 echo '<div class="success-message">' . $message . '</div>';
            } else {
                 echo '<div class="error-message">' . $message . '</div>';
            }
           
        }
        ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <label for="email">Ingresa tu correo electrónico</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            <button type="submit">Enviar enlace de restablecimiento</button>
             <button type="button" onclick="location.href='login.php'" class="button-secondary">
                volver 
            </button>
        </form>
    </div>
</body>
</html>