<?php
session_start();
include "conexion.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password']; // ojo, en producción debería estar hasheada

    // Buscamos usuario y empresa
    $stmt = $conexion->prepare("SELECT id, nombre, rol, empresa_id, is_global_admin FROM usuarios WHERE email=? AND password=?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($usuario = $result->fetch_assoc()) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['rol'] = $usuario['rol'];
        $_SESSION['empresa_id'] = $usuario['empresa_id'];
        $_SESSION['is_global_admin'] = (bool)$usuario['is_global_admin']; // true/false

        header("Location: index.php");
        exit;
    } else {
        $error = "Email o contraseña incorrectos";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRM - Login</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f3f3f3;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-box {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            width: 350px;
        }
        .login-box h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #0078d4;
        }
        .login-box input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }
        .login-box button {
            width: 100%;
            background-color: #0078d4;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.2s ease;
        }
        .login-box button:hover {
            background-color: #005a9e;
        }
        .error {
            color: #d8000c;
            background-color: #ffd2d2;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>Iniciar Sesión</h1>
        <?php if(isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>
