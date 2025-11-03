<?php
session_start();
include "../conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$empresa_id = $_SESSION['empresa_id'];
$is_global = $_SESSION['is_global_admin'] ?? false;

// --- Preparar filtros ---
$where = [];
$params = [];
$types = '';

if (!$is_global) {
    $where[] = "empresa_id = ?";
    $params[] = $empresa_id;
    $types .= 'i';
}

$buscar = $_GET['buscar'] ?? '';
if ($buscar) {
    $where[] = "nombre LIKE ?";
    $params[] = "%$buscar%";
    $types .= 's';
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// --- Preparar query ---
$sql = "SELECT * FROM productos $where_sql ORDER BY nombre ASC";
$stmt = $conexion->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Productos</title>
<link rel="stylesheet" href="../public/css/estilos.css">
</head>
<body>
<aside>
    <h2>Bytex Manager</h2>
    <nav class="menu">
        <button id="btnNotificaciones" class="campana">
            🔔 <span id="contadorNotificaciones">0</span>
        </button>

        <div class="menu-links">
            <a href="../index.php">Panel</a>

            <!-- Submenu Operaciones -->
            <button class="submenu-btn">Operaciones ▾</button>
            <div class="submenu">
                <a href="ventas.php">Ventas</a>
                <a href="productos.php">Productos</a>
                <a href="presupuestos.php">Presupuestos</a>
            </div>

            <!-- Submenu Gestión -->
            <button class="submenu-btn">Gestión ▾</button>
            <div class="submenu">
                <a href="clientes.php">Clientes</a>
                <a href="interacciones.php">Interacciones</a>
                <a href="tareas.php">Tareas</a>
                <a href="proveedores.php">Proveedores</a>
                <a href="compras.php" class="activo">Compras</a>
                <a href="gastos.php">Gastos</a>
            </div>

            <a href="usuarios.php">Usuarios</a>
            <a href="empresas.php">Empresas</a>
            <a href="proveedores.php" class="activo">Proveedores</a>
        </div>

        <div class="logout">
            <a href="../public/logout.php">Cerrar Sesión</a>
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
    background: white;
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
    background: white;
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
.submenu { display: none; margin-left: 10px; }
.submenu-btn { 
    background: none; 
    border: none; 
    font-size: 16px; 
    width: 100%; 
    text-align: left; 
    padding: 6px 0; 
    color: #222225; /* <- esto hace que el texto sea negro */
}
.submenu a {
    display: block;
    padding: 3px 0;
    color: #222225; /* texto negro */
    text-decoration: none; /* opcional: quita el subrayado */
}
</style>

<main>
<h1>Productos</h1>
<a href="controllers/crear_producto.php" class="button">Agregar Producto</a>

<!-- Buscador -->
<form method="get" style="margin: 15px 0;">
    <input type="text" name="buscar" placeholder="Buscar producto..." value="<?= htmlspecialchars($buscar) ?>" style="padding:5px; width:200px;">
    <button type="submit">Buscar</button>
    <a href="productos.php" style="margin-left:10px;">Limpiar</a>
</form>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Precio</th>
            <th>Stock</th>
            <?php if($is_global): ?><th>Empresa</th><?php endif; ?>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if($result->num_rows>0): ?>
        <?php while($p=$result->fetch_assoc()): ?>
        <tr>
            <td><?= $p['id'] ?></td>
            <td><?= htmlspecialchars($p['nombre']) ?></td>
            <td><?= htmlspecialchars($p['descripcion']) ?></td>
            <td>$<?= number_format($p['precio'],2) ?></td>
            <td><?= $p['stock'] ?></td>
            <?php if($is_global): ?>
            <td><?= $conexion->query("SELECT nombre FROM empresas WHERE id=".$p['empresa_id'])->fetch_assoc()['nombre'] ?></td>
            <?php endif; ?>
            <td>
                <a href="controllers/editar_producto.php?id=<?= $p['id'] ?>">Editar</a> |
                <a href="controllers/eliminar_producto.php?id=<?= $p['id'] ?>" onclick="return confirm('¿Eliminar producto?')">Eliminar</a>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php else: ?>
        <tr><td colspan="<?= $is_global?7:6 ?>" style="text-align:center;">No hay productos</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</main>
<script>
    document.querySelectorAll('.submenu-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const submenu = btn.nextElementSibling;
        submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
    });
});
</script>
</body>
</html>
