<?php
session_start();
include "conexion.php";

// --- Verificar sesión ---
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// --- Multiempresa ---
$empresa_id = $_SESSION['empresa_id'];
$is_global = $_SESSION['is_global_admin'] ?? false;

// --- Filtros para ventas ---
$where = [];
if (!$is_global) {
    $where[] = "v.empresa_id = " . intval($empresa_id);
}

if (!empty($_GET['fecha_inicio']) && !empty($_GET['fecha_fin'])) {
    $fecha_inicio = $conexion->real_escape_string($_GET['fecha_inicio']);
    $fecha_fin = $conexion->real_escape_string($_GET['fecha_fin']);
    $where[] = "DATE(v.fecha_creacion) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}

if (!empty($_GET['cliente_id'])) {
    $cliente_id = intval($_GET['cliente_id']);
    $where[] = "v.cliente_id = $cliente_id";
}

if (!empty($_GET['estado'])) {
    $estado = $conexion->real_escape_string($_GET['estado']);
    $where[] = "v.estado = '$estado'";
}

// --- Generar WHERE ---
$where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// --- Últimas 5 ventas ---
$ventas_ultimas_query = "
SELECT v.id, c.nombre AS cliente_nombre, u.nombre AS usuario_nombre, v.producto, v.monto, v.estado, v.fecha_creacion
FROM ventas v
JOIN clientes c ON v.cliente_id = c.id
LEFT JOIN usuarios u ON v.usuario_id = u.id
$where_sql
ORDER BY v.fecha_creacion DESC
LIMIT 5
";
$ventas_ultimas = $conexion->query($ventas_ultimas_query);

// --- Ventas totales ---
$total_ventas_query = "SELECT COUNT(*) AS total FROM ventas v $where_sql";
$total_ventas = $conexion->query($total_ventas_query)->fetch_assoc()['total'];

// --- Ventas por estado ---
$estado_query = "
SELECT estado, COUNT(*) AS total
FROM ventas v
$where_sql
GROUP BY estado
";
$estado_result = $conexion->query($estado_query);
$ventas_estado = ['pendiente'=>0,'cerrada'=>0,'perdida'=>0];
while($row = $estado_result->fetch_assoc()) {
    $ventas_estado[$row['estado']] = $row['total'];
}

// --- Ventas por usuario ---
$usuario_query = "
SELECT u.nombre, COUNT(*) AS total
FROM ventas v
LEFT JOIN usuarios u ON v.usuario_id=u.id
$where_sql
GROUP BY u.id
";
$usuario_result = $conexion->query($usuario_query);
$ventas_usuario = [];
while($row = $usuario_result->fetch_assoc()) {
    $ventas_usuario[] = ['nombre'=>$row['nombre'] ?? 'Sin Asignar', 'total'=>$row['total']];
}

// --- Clientes para filtro ---
$clientes_query = "SELECT id, nombre FROM clientes " . (!$is_global ? "WHERE empresa_id=" . intval($empresa_id) : "") . " ORDER BY nombre ASC";
$clientes = $conexion->query($clientes_query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRM - Ventas</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        .graficos { display: flex; flex-wrap: wrap; gap: 20px; }
        .graficos canvas { flex: 1 1 300px; max-width: 500px; height: 300px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    </style>
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
    <h1>Ventas</h1>
    <a href="crear_venta.php" class="button">Agregar Venta</a>

    <!-- Filtros -->
    <section>
        <h2>Filtrar Ventas</h2>
        <form method="GET">
            <label>Desde:</label>
            <input type="date" name="fecha_inicio" value="<?= $_GET['fecha_inicio'] ?? '' ?>">
            <label>Hasta:</label>
            <input type="date" name="fecha_fin" value="<?= $_GET['fecha_fin'] ?? '' ?>">

            <label>Cliente:</label>
            <select name="cliente_id">
                <option value="">-- Todos --</option>
                <?php while($c = $clientes->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>" <?= (($_GET['cliente_id'] ?? '') == $c['id']) ? 'selected' : '' ?>><?= $c['nombre'] ?></option>
                <?php endwhile; ?>
            </select>

            <label>Estado:</label>
            <select name="estado">
                <option value="">-- Todos --</option>
                <option value="pendiente" <?= ($_GET['estado'] ?? '')=='pendiente'?'selected':'' ?>>Pendiente</option>
                <option value="cerrada" <?= ($_GET['estado'] ?? '')=='cerrada'?'selected':'' ?>>Cerrada</option>
                <option value="perdida" <?= ($_GET['estado'] ?? '')=='perdida'?'selected':'' ?>>Perdida</option>
            </select>
            <button type="submit">Aplicar</button>
        </form>
    </section>

    <!-- Gráficos -->
    <section class="graficos">
        <canvas id="ventasEstadoChart"></canvas>
        <canvas id="ventasUsuarioChart"></canvas>
    </section>

    <!-- Tabla de últimas ventas -->
    <section>
        <h2>Últimas 5 Ventas</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Usuario</th>
                    <th>Producto</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if($ventas_ultimas && $ventas_ultimas->num_rows > 0): ?>
                    <?php while($row = $ventas_ultimas->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= $row['cliente_nombre'] ?></td>
                            <td><?= $row['usuario_nombre'] ?></td>
                            <td><?= $row['producto'] ?></td>
                            <td>$<?= number_format($row['monto'],2) ?></td>
                            <td><?= ucfirst($row['estado']) ?></td>
                            <td><?= $row['fecha_creacion'] ?></td>
                            <td>
                                <a href="editar_venta.php?id=<?= $row['id'] ?>">Editar</a> |
                                <a href="eliminar_venta.php?id=<?= $row['id'] ?>" onclick="return confirm('¿Eliminar venta?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center;">No hay ventas</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctxEstado = document.getElementById('ventasEstadoChart').getContext('2d');
new Chart(ctxEstado, {
    type: 'pie',
    data: {
        labels: ['Pendiente', 'Cerrada', 'Perdida'],
        datasets: [{
            data: [<?= $ventas_estado['pendiente'] ?>, <?= $ventas_estado['cerrada'] ?>, <?= $ventas_estado['perdida'] ?>],
            backgroundColor: ['#f39c12','#27ae60','#c0392b']
        }]
    },
    options: { responsive:true, plugins:{legend:{position:'bottom'}} }
});

const ctxUsuario = document.getElementById('ventasUsuarioChart').getContext('2d');
new Chart(ctxUsuario, {
    type: 'bar',
    data: {
        labels: [<?= implode(',', array_map(fn($u)=>"'".$u['nombre']."'", $ventas_usuario)) ?>],
        datasets: [{
            label: 'Cantidad de ventas',
            data: [<?= implode(',', array_map(fn($u)=>$u['total'], $ventas_usuario)) ?>],
            backgroundColor: '#0078d4'
        }]
    },
    options: { responsive:true, scales:{y:{beginAtZero:true}}, plugins:{legend:{display:false}} }
});
</script>
</body>
</html>
