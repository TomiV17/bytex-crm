<?php
session_start();
include "conexion.php";

// --- Verificar sesión ---
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$empresa_id = $_SESSION['empresa_id'];
$is_global = $_SESSION['is_global_admin'] ?? false;

// --- Obtener empresas solo si es admin global ---
$empresas = [];
if ($is_global) {
    $res = $conexion->query("SELECT id, nombre FROM empresas ORDER BY nombre ASC");
    while($row = $res->fetch_assoc()) $empresas[] = $row;
}

// --- Guardar producto ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'] ?? '';
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock'] ?? 0);

    // Determinar empresa_id
    $prod_empresa_id = $is_global && !empty($_POST['empresa_id']) ? intval($_POST['empresa_id']) : $empresa_id;

    $stmt = $conexion->prepare("INSERT INTO productos (nombre, descripcion, precio, stock, empresa_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdii", $nombre, $descripcion, $precio, $stock, $prod_empresa_id);
    $stmt->execute();
    $stmt->close();

    header("Location: productos.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Agregar Producto</title>
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
<h1>Agregar Producto</h1>
<form method="POST">
    <label>Nombre:</label><br>
    <input type="text" name="nombre" required><br><br>

    <label>Descripción:</label><br>
    <textarea name="descripcion" rows="3"></textarea><br><br>

    <label>Precio:</label><br>
    <input type="number" step="0.01" name="precio" required><br><br>

    <label>Stock:</label><br>
    <input type="number" name="stock" value="0"><br><br>

    <?php if($is_global): ?>
    <label>Empresa:</label><br>
    <select name="empresa_id" required>
        <?php foreach($empresas as $e): ?>
        <option value="<?= $e['id'] ?>"><?= $e['nombre'] ?></option>
        <?php endforeach; ?>
    </select><br><br>
    <?php endif; ?>

    <button type="submit">Guardar</button>
</form>
<br>
<a href="productos.php">Volver</a>
</main>
</body>
</html>
