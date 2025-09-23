<?php
session_start();
include "conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$empresa_id = $_SESSION['empresa_id'];
$asignador_id = $_SESSION['usuario_id'];

// Obtener usuarios de la misma empresa
$sql = "SELECT id, nombre FROM usuarios WHERE empresa_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$usuarios = $stmt->get_result();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asignado_id = intval($_POST['asignado_id']);
    $descripcion = $conexion->real_escape_string($_POST['descripcion']);
    $prioridad = $_POST['prioridad'];

    // Insertar tarea
    $stmt = $conexion->prepare("INSERT INTO tareas (empresa_id, asignador_id, asignado_id, descripcion, prioridad) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiss", $empresa_id, $asignador_id, $asignado_id, $descripcion, $prioridad);
    $stmt->execute();
    $stmt->close();

    // Crear notificación
    $mensaje = "Nakos te ha asignado una tarea ($prioridad)";
    $stmt = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje, leida) VALUES (?, ?, 0)");
    $stmt->bind_param("is", $asignado_id, $mensaje);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php"); // o donde quieras volver
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Asignar Tarea</title>
<link rel="stylesheet" href="estilos.css">
</head>
<body>
<aside>
    <h2>CRM</h2>
    <nav class="menu">
        <button id="btnNotificaciones" class="campana">
            🔔 <span id="contadorNotificaciones">0</span>
        </button>

        <div class="menu-links">
            <a href="index.php">Panel</a>
            <a href="clientes.php">Clientes</a>
            <a href="ventas.php">Ventas</a>
            <a href="presupuestos.php">Presupuestos</a>
            <a href="interacciones.php">Interacciones</a>
            <a href="tareas.php">Tareas</a>
            <a href="usuarios.php">Usuarios</a>
            <a href="empresas.php">Empresas</a>
        </div>
        <div class="logout">
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </nav>
</aside>

<!-- Drawer de notificaciones FUERA del aside -->
<div id="modalNotificaciones" class="modal">
    <div class="modal-contenido">
        <h3>Notificaciones</h3>
        <ul id="listaNotificaciones">
            <li>Cargando...</li>
        </ul>
    </div>
</div>

<style>
.campana {
    position: relative;
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
}
.campana span {
    position: absolute;
    top: -8px;
    right: -8px;
    background: red;
    color: white;
    font-size: 12px;
    padding: 2px 6px;
    border-radius: 50%;
}
.modal {
    display: block;
    position: fixed;
    top: 50px;
    right: 0;
    width: 300px;
    max-height: 80vh;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #ccc;
    box-shadow: -2px 0 8px rgba(0,0,0,0.2);
    border-radius: 8px 0 0 8px;
    z-index: 1000;
    transform: translateX(100%);
    transition: transform 0.3s ease;
}
.modal.abierto {
    transform: translateX(0);
}


.modal-contenido {
    padding: 10px;
}
.modal-contenido ul {
    list-style: none;
    margin: 0;
    padding: 0;
}
.modal-contenido li {
    padding: 8px;
    border-bottom: 1px solid #eee;
}
.modal-contenido li.no-leida {
    font-weight: bold;
    background: #f9f9f9;
}
</style>
<main>
<h1>Asignar nueva tarea</h1>
<form method="POST" class="formulario">
    <label>Asignar a:</label>
    <select name="asignado_id" required>
        <?php while ($u = $usuarios->fetch_assoc()): ?>
            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
        <?php endwhile; ?>
    </select>

    <label>Descripción:</label>
    <textarea name="descripcion" required></textarea>

    <label>Prioridad:</label>
    <select name="prioridad" required>
        <option value="baja">Baja</option>
        <option value="media" selected>Media</option>
        <option value="alta">Alta</option>
        <option value="urgente">Urgente</option>
    </select>

    <button type="submit" class="button">Asignar</button>
</form>
<a href="index.php" class="button link">Volver</a>
</main>
</body>
</html>
