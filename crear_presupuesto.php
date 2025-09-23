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

// --- Obtener clientes ---
$clientes_query = "SELECT id, nombre FROM clientes";
if (!$is_global) {
    $clientes_query .= " WHERE empresa_id=" . intval($empresa_id);
}
$clientes_query .= " ORDER BY nombre";
$clientes = $conexion->query($clientes_query);

// --- Obtener usuarios ---
$usuarios_query = "SELECT id, nombre FROM usuarios";
if (!$is_global) {
    $usuarios_query .= " WHERE empresa_id=" . intval($empresa_id);
}
$usuarios_query .= " ORDER BY nombre";
$usuarios = $conexion->query($usuarios_query);

// --- Procesar formulario ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = intval($_POST['cliente_id']);
    $usuario_id = intval($_POST['usuario_id']);
    $estado = "pendiente";
    $presupuesto_empresa_id = $is_global && !empty($_POST['empresa_id']) ? intval($_POST['empresa_id']) : $empresa_id;

    // Insertar presupuesto
    $stmt = $conexion->prepare("INSERT INTO presupuestos (cliente_id, usuario_id, estado, empresa_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iisi", $cliente_id, $usuario_id, $estado, $presupuesto_empresa_id);
    $stmt->execute();
    $presupuesto_id = $stmt->insert_id;
    $stmt->close();

    // Insertar items
    if (!empty($_POST['producto'])) {
        foreach ($_POST['producto'] as $i => $producto_id) {
            if (!$producto_id) continue;

            $cantidad = intval($_POST['cantidad'][$i]);
            $precio = floatval($_POST['precio'][$i]);

            $stmt = $conexion->prepare("INSERT INTO presupuesto_items (presupuesto_id, producto_id, cantidad, precio) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $presupuesto_id, $producto_id, $cantidad, $precio);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: presupuestos.php");
    exit;
}

// --- Obtener productos ---
$productos_query = "SELECT id, nombre, precio FROM productos";
if (!$is_global) {
    $productos_query .= " WHERE empresa_id=" . intval($empresa_id);
}
$productos_query .= " ORDER BY nombre";
$productos = $conexion->query($productos_query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Presupuesto</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        .items { margin-top: 15px; }
        .item-row { display: flex; gap: 10px; margin-bottom: 10px; }
        .item-row select, .item-row input { flex: 1; padding: 5px; }
    </style>
    <script>
        function agregarItem() {
            const contenedor = document.getElementById("items");
            const row = document.createElement("div");
            row.classList.add("item-row");
            row.innerHTML = `
                <select name="producto[]">
                    <option value="">-- Seleccione producto --</option>
                    <?php while ($p = $productos->fetch_assoc()): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?> ($<?= number_format($p['precio'],2) ?>)</option>
                    <?php endwhile; ?>
                </select>
                <input type="number" name="cantidad[]" placeholder="Cantidad" min="1" value="1" required>
                <input type="number" step="0.01" name="precio[]" placeholder="Precio" required>
                <button type="button" onclick="this.parentNode.remove()">❌</button>
            `;
            contenedor.appendChild(row);
        }
    </script>
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
    <h1>Nuevo Presupuesto</h1>
    <form method="POST">
        <label>Cliente:</label>
        <select name="cliente_id" required>
            <option value="">-- Seleccione --</option>
            <?php while ($c = $clientes->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>"><?= $c['nombre'] ?></option>
            <?php endwhile; ?>
        </select>

        <label>Usuario responsable:</label>
        <select name="usuario_id" required>
            <?php while ($u = $usuarios->fetch_assoc()): ?>
                <option value="<?= $u['id'] ?>"><?= $u['nombre'] ?></option>
            <?php endwhile; ?>
        </select>

        <?php if ($is_global): ?>
        <label>Empresa:</label>
        <select name="empresa_id">
            <?php
            $res_emp = $conexion->query("SELECT id, nombre FROM empresas ORDER BY nombre");
            while ($e = $res_emp->fetch_assoc()): ?>
                <option value="<?= $e['id'] ?>"><?= $e['nombre'] ?></option>
            <?php endwhile; ?>
        </select>
        <?php endif; ?>

        <h3>Items</h3>
        <div id="items" class="items"></div>
        <button type="button" onclick="agregarItem()">➕ Agregar Producto</button>

        <br><br>
        <button type="submit" class="button">Guardar Presupuesto</button>
        <a href="presupuestos.php" class="button link">Cancelar</a>
    </form>
</main>
</body>
</html>
