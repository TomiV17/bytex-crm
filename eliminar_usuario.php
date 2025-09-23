<?php
session_start();
include "conexion.php";

// Solo admin puede acceder
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: index.php");
    exit;
}

// Si no viene ID => volver
if (!isset($_GET['id'])) {
    header("Location: usuarios.php");
    exit;
}

$id = intval($_GET['id']);

// Si el admin intenta borrarse a sí mismo, bloquear
if ($id == $_SESSION['usuario_id']) {
    echo "<p>No podés eliminar tu propia cuenta.</p>";
    echo "<a href='usuarios.php'>Volver</a>";
    exit;
}

// Buscar usuario
$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$usuario = $res->fetch_assoc();
$stmt->close();

if (!$usuario) {
    header("Location: usuarios.php");
    exit;
}

// Confirmación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirmar'])) {
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        header("Location: usuarios.php");
        exit;
    } else {
        header("Location: usuarios.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Usuario</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<aside>
    <h2>CRM</h2>
    <nav>
        <div class="menu-links">
            <a href="index.php">Panel</a>
            <a href="clientes.php">Clientes</a>
            <a href="ventas.php">Ventas</a>
            <a href="interacciones.php">Interacciones</a>
            <a href="usuarios.php">Usuarios</a>
        </div>
        <div class="logout">
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </nav>
</aside>

<main>
    <h1>Eliminar Usuario</h1>
    <p>¿Seguro que querés eliminar al usuario <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>?</p>
    <form method="POST">
        <button type="submit" name="confirmar">Sí, eliminar</button>
        <button type="submit" name="cancelar">Cancelar</button>
    </form>
</main>
</body>
</html>
