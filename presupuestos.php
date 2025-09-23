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

// --- Filtro multiempresa ---
$where = "";
if (!$is_global) {
    $where = "WHERE p.empresa_id = " . intval($empresa_id);
}

// --- Consulta ---
$sql = "
SELECT 
    p.id,
    c.nombre AS cliente_nombre,
    u.nombre AS usuario_nombre,
    p.estado,
    p.fecha_creacion,
    IFNULL(SUM(i.cantidad * i.precio),0) AS total
FROM presupuestos p
JOIN clientes c ON p.cliente_id = c.id
LEFT JOIN usuarios u ON p.usuario_id = u.id
LEFT JOIN presupuesto_items i ON p.id = i.presupuesto_id
$where
GROUP BY p.id
ORDER BY p.fecha_creacion DESC
";
$result = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Presupuestos</title>
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
    <h1>Presupuestos</h1>
    <a href="crear_presupuesto.php" class="button">Nuevo Presupuesto</a>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Usuario</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th>Total</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['cliente_nombre']) ?></td>
                        <td><?= htmlspecialchars($row['usuario_nombre']) ?></td>
                        <td><?= ucfirst($row['estado']) ?></td>
                        <td><?= $row['fecha_creacion'] ?></td>
                        <td>$<?= number_format($row['total'], 2) ?></td>
                        <td>
                            <a href="ver_presupuesto.php?id=<?= $row['id'] ?>">Ver</a> |
                            <a href="editar_presupuesto.php?id=<?= $row['id'] ?>">Editar</a> |
                            <a href="eliminar_presupuesto.php?id=<?= $row['id'] ?>" onclick="return confirm('¿Eliminar presupuesto?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align:center;">No hay presupuestos</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</main>
</body>
</html>
