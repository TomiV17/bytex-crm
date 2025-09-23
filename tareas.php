<?php
session_start();
include "conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$empresa_id = $_SESSION['empresa_id'];
$is_global = $_SESSION['is_global_admin'] ?? false;
$usuario_id = $_SESSION['usuario_id'];

// --- Filtrar tareas ---
if ($is_global) {
    $sql = "SELECT t.*, u1.nombre AS asignador, u2.nombre AS asignado, e.nombre AS empresa
            FROM tareas t
            JOIN usuarios u1 ON t.asignador_id = u1.id
            JOIN usuarios u2 ON t.asignado_id = u2.id
            JOIN empresas e ON t.empresa_id = e.id
            ORDER BY t.fecha_asignacion DESC";
    $stmt = $conexion->prepare($sql);
} else {
    $sql = "SELECT t.*, u1.nombre AS asignador, u2.nombre AS asignado
            FROM tareas t
            JOIN usuarios u1 ON t.asignador_id = u1.id
            JOIN usuarios u2 ON t.asignado_id = u2.id
            WHERE t.empresa_id = ?
            ORDER BY t.fecha_asignacion DESC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $empresa_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tareas</title>
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
    <h1>Tareas</h1>
    <a href="crear_tarea.php" class="button">Asignar nueva tarea</a>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Asignador</th>
                <th>Asignado</th>
                <th>Descripción</th>
                <th>Prioridad</th>
                <th>Estado</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
        <?php while($t = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $t['id'] ?></td>
                <td><?= htmlspecialchars($t['asignador']) ?></td>
                <td><?= htmlspecialchars($t['asignado']) ?></td>
                <td><?= htmlspecialchars($t['descripcion']) ?></td>
                <td style="color: 
                    <?= $t['prioridad']=='urgente'?'red':
                        ($t['prioridad']=='alta'?'orange':
                        ($t['prioridad']=='media'?'blue':'green')) ?>">
                    <?= ucfirst($t['prioridad']) ?>
                </td>
                <td><?= ucfirst($t['estado']) ?></td>
                <td><?= $t['fecha_asignacion'] ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</main>
</body>
</html>
