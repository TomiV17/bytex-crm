<?php
session_start();
include "conexion.php";

// Si no hay sesión activa, redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Empresa y rol global
$empresa_id = $_SESSION['empresa_id'];
$is_global = $_SESSION['is_global_admin'] ?? false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $estado = $_POST['estado'];

    // Si es admin global, puede asignar otra empresa
    if ($is_global && !empty($_POST['empresa_id'])) {
        $empresa_id = $_POST['empresa_id'];
    }

    $stmt = $conexion->prepare("
        INSERT INTO clientes (nombre, apellido, email, telefono, estado, empresa_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssssi", $nombre, $apellido, $email, $telefono, $estado, $empresa_id);
    $stmt->execute();
    $stmt->close();

    header("Location: clientes.php");
    exit;
}

// Solo admin global puede ver select de empresas
$empresas = [];
if ($is_global) {
    $res = $conexion->query("SELECT id, nombre FROM empresas ORDER BY nombre");
    while($row = $res->fetch_assoc()) $empresas[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Cliente</title>
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
    <h1>Agregar Cliente</h1>
    <section>
        <form method="POST" class="formulario">
            <label>Nombre:</label>
            <input type="text" name="nombre" required>

            <label>Apellido:</label>
            <input type="text" name="apellido">

            <label>Email:</label>
            <input type="email" name="email">

            <label>Teléfono:</label>
            <input type="text" name="telefono">

            <?php if($is_global): ?>
                <label>Empresa:</label>
                <select name="empresa_id">
                    <?php foreach($empresas as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <label>Estado:</label>
            <select name="estado">
                <option value="nuevo">Nuevo</option>
                <option value="contactado">Contactado</option>
                <option value="en seguimiento">En seguimiento</option>
                <option value="cliente">Cliente</option>
                <option value="perdido">Perdido</option>
            </select>

            <button type="submit" class="button">Guardar</button>
        </form>

        <a href="clientes.php" class="button link">Volver</a>
    </section>
</main>
</body>
</html>
