<?php
session_start();
include "conexion.php";

$empresa_id = $_SESSION['empresa_id'];
$is_global = $_SESSION['is_global_admin'] ?? false;

// --- Obtener datos para selects ---
$cliente_query = "SELECT id, nombre FROM clientes " . (!$is_global ? "WHERE empresa_id = $empresa_id" : "") . " ORDER BY nombre";
$clientes = $conexion->query($cliente_query);

$usuario_query = "SELECT id, nombre FROM usuarios " . (!$is_global ? "WHERE empresa_id = $empresa_id" : "") . " ORDER BY nombre";
$usuarios = $conexion->query($usuario_query);

// --- Productos disponibles ---
$productos_query = "SELECT id, nombre, precio FROM productos " . (!$is_global ? "WHERE empresa_id = $empresa_id" : "") . " ORDER BY nombre";
$productos = $conexion->query($productos_query);

// --- Procesar formulario ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = intval($_POST['cliente_id']);
    $usuario_id = intval($_POST['usuario_id']);
    $producto_id = intval($_POST['producto_id']);
    $monto = floatval($_POST['monto']);
    $estado = $conexion->real_escape_string($_POST['estado']);

    $venta_empresa_id = $is_global && !empty($_POST['empresa_id']) ? intval($_POST['empresa_id']) : $empresa_id;

    // Obtener nombre del producto
    $prod_res = $conexion->query("SELECT nombre FROM productos WHERE id=$producto_id LIMIT 1");
    $producto_nombre = $prod_res->fetch_assoc()['nombre'] ?? '';

    $stmt = $conexion->prepare("INSERT INTO ventas (cliente_id, usuario_id, producto, monto, estado, empresa_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisdsd", $cliente_id, $usuario_id, $producto_nombre, $monto, $estado, $venta_empresa_id);
    $stmt->execute();
    $stmt->close();

    header("Location: ventas.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Venta</title>
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
    <h1>Agregar Venta</h1>

    <section>
        <form method="POST" class="formulario">
            <label>Cliente:</label>
            <select name="cliente_id" required>
                <?php while ($c = $clientes->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>"><?= $c['nombre'] ?></option>
                <?php endwhile; ?>
            </select>

            <label>Usuario:</label>
            <select name="usuario_id" required>
                <?php while ($u = $usuarios->fetch_assoc()): ?>
                    <option value="<?= $u['id'] ?>"><?= $u['nombre'] ?></option>
                <?php endwhile; ?>
            </select>

            <label>Producto:</label>
            <select name="producto_id" id="producto_id" required>
                <option value="">-- Seleccionar producto --</option>
                <?php while($p = $productos->fetch_assoc()): ?>
                    <option value="<?= $p['id'] ?>" data-precio="<?= $p['precio'] ?>">
                        <?= $p['nombre'] ?> - $<?= number_format($p['precio'],2) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Monto:</label>
            <input type="number" step="0.01" name="monto" id="monto" required>

            <label>Estado:</label>
            <select name="estado">
                <option value="pendiente">Pendiente</option>
                <option value="cerrada">Cerrada</option>
                <option value="perdida">Perdida</option>
            </select>

            <?php if($is_global): ?>
            <label>Empresa:</label>
            <select name="empresa_id">
                <?php
                $res_emp = $conexion->query("SELECT id, nombre FROM empresas ORDER BY nombre");
                while($e = $res_emp->fetch_assoc()): ?>
                    <option value="<?= $e['id'] ?>"><?= $e['nombre'] ?></option>
                <?php endwhile; ?>
            </select>
            <?php endif; ?>

            <button type="submit" class="button">Guardar</button>
        </form>

        <a href="ventas.php" class="button link">Volver</a>
    </section>
</main>

<script>
// Auto-llenar monto según producto
const productoSelect = document.getElementById('producto_id');
const montoInput = document.getElementById('monto');

productoSelect.addEventListener('change', () => {
    const precio = productoSelect.selectedOptions[0].dataset.precio;
    montoInput.value = precio || '';
});
</script>
</body>
</html>
