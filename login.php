<?php
session_start();

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: bienvenido.php");
    exit;
}

require_once 'db.php';

$username_err = $password_err = $login_err = $captcha_err = "";
$username = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["username"]))) {
        $username_err = "Por favor, ingrese su nombre de usuario.";
    } else {
        $username = trim($_POST["username"]);
    }

    if (empty(trim($_POST["password"]))) {
        $password_err = "Por favor, ingrese su contraseña.";
    } else {
        $password = trim($_POST["password"]);
    }

    if (empty($_POST['g-recaptcha-response'])) {
        $captcha_err = "Captcha no detectado.";
    } else {
        $captcha_response = $_POST['g-recaptcha-response'];
        $secret_key = "6LeQ-morAAAAAEMu2oSPX4OUhxkcKEgVqGQGOxvL"; 

        $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret_key}&response={$captcha_response}");
        $captcha_success = json_decode($verify);

        if (!$captcha_success->success || $captcha_success->score < 0.5) {
            $captcha_err = "Verificación de captcha fallida.";
        }
    }
    if (empty($username_err) && empty($password_err) && empty($captcha_err)) {
        $sql = "SELECT usuario, pwd FROM usuarios WHERE usuario = ?"; 

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = $username;

            if ($stmt->execute()) {
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($db_usuario, $db_password_plain); 
                    if ($stmt->fetch()) {
                        if ($password === $db_password_plain) { 
                            $_SESSION["loggedin"] = true;
                            $_SESSION["username"] = $db_usuario;

                            header("location: bienvenido.php");
                            exit;
                        } else {
                            $login_err = "Usuario o contraseña incorrectos.";
                        }
                    }
                } else {
                    $login_err = "Usuario o contraseña incorrectos.";
                }
            } else {
                echo "¡Ups! Algo salió mal con la ejecución de la consulta.";
            }
            $stmt->close();
        } else {
            echo "ERROR: No se pudo preparar la consulta SQL. " . $conn->error;
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
    <title>Acceso</title>
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
            display: flex;
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
</head>
<body>
    <div class="login-container">
        <h2>Inicia Sesión</h2>
        <?php
        if (!empty($login_err)) {
            echo '<div class="error-message">' . $login_err . '</div>';
        }
        if (isset($_SESSION['reset_message'])) {
            echo '<div class="success-message">' . $_SESSION['reset_message'] . '</div>';
            unset($_SESSION['reset_message']); 
        } 
        if (isset($_SESSION['registration_success'])) {
            echo '<div class="success-message">' . $_SESSION['registration_success'] . '</div>';
            unset($_SESSION['registration_success']); 
        }
        ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <label for="username">Usuario</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            <?php if (!empty($username_err)) echo '<div class="error-message">' . $username_err . '</div>'; ?>

            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>
            <?php if (!empty($password_err)) echo '<div class="error-message">' . $password_err . '</div>'; ?>

            <?php if (!empty($captcha_err)) echo '<div class="error-message">' . $captcha_err . '</div>'; ?>

            <button type="submit">Acceder</button>
            <button type="button" onclick="location.href='register.php'" class="button-secondary">
                Crear una Cuenta
            </button>

            <button type="button" onclick="location.href='send_password_reset.php'" class="button-secondary">
                Olvidé Mi contraseña
            </button>
        </form>
    </div>

    <div id="captcha-overlay">
        <div>🧠 Verificando que no eres un robot...</div>
    </div>

    <script>
    grecaptcha.ready(function() {
        document.getElementById("captcha-overlay").style.display = "flex";
        grecaptcha.execute('6LeQ-morAAAAACu5_QUwv5RFb7qRfXtuq-RKGB-X', {action: 'login'}).then(function(token) {
            const recaptchaResponse = document.createElement('input');
            recaptchaResponse.setAttribute('type', 'hidden');
            recaptchaResponse.setAttribute('name', 'g-recaptcha-response');
            recaptchaResponse.setAttribute('value', token);
            document.forms[0].appendChild(recaptchaResponse);
            document.getElementById("captcha-overlay").style.display = "none";
        });
    });
    </script>
</body>
</html>
```