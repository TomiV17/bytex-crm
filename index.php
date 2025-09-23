<?php
session_start();
include "conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// --- Multiempresa ---
$empresa_id = $_SESSION['empresa_id'];
$is_global = $_SESSION['is_global_admin'] ?? false;

// --- Función para construir WHERE y params ---
function buildWhere($is_global, $empresa_id) {
    $where = [];
    $params = [];
    $types = '';

    if (!$is_global) {
        $where[] = "empresa_id = ?";
        $params[] = $empresa_id;
        $types .= 'i';
    }

    $where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";
    return [$where_sql, $params, $types];
}

// --- Total Clientes ---
list($where_sql, $params, $types) = buildWhere($is_global, $empresa_id);
$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM clientes $where_sql");
if (!$is_global) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total_clientes = $stmt->get_result()->fetch_assoc()['total'];

// --- Total Ventas Cerradas ---
list($where_sql, $params, $types) = buildWhere($is_global, $empresa_id);
$sql = "SELECT COUNT(*) AS total FROM ventas WHERE estado='cerrada'";
if (!$is_global) $sql .= " AND empresa_id = ?";
$stmt = $conexion->prepare($sql);
if (!$is_global) $stmt->bind_param('i', $empresa_id);
$stmt->execute();
$total_ventas = $stmt->get_result()->fetch_assoc()['total'];

// --- Total Interacciones ---
list($where_sql, $params, $types) = buildWhere($is_global, $empresa_id);
$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM interacciones $where_sql");
if (!$is_global) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total_interacciones = $stmt->get_result()->fetch_assoc()['total'];

// --- Ganancia última semana ---
$sql = "SELECT COALESCE(SUM(monto),0) AS total FROM ventas WHERE estado='cerrada' AND fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
if (!$is_global) $sql .= " AND empresa_id = ?";
$stmt = $conexion->prepare($sql);
if (!$is_global) $stmt->bind_param('i', $empresa_id);
$stmt->execute();
$ganancia_semana = $stmt->get_result()->fetch_assoc()['total'];

// --- Últimas 5 Ventas ---
$sql = "SELECT v.id, v.producto, v.monto, c.nombre, c.apellido
        FROM ventas v
        JOIN clientes c ON v.cliente_id=c.id
        WHERE v.estado='cerrada'";
if (!$is_global) $sql .= " AND v.empresa_id = ?";
$sql .= " ORDER BY v.fecha_creacion DESC LIMIT 5";
$stmt = $conexion->prepare($sql);
if (!$is_global) $stmt->bind_param('i', $empresa_id);
$stmt->execute();
$ultimas_ventas = $stmt->get_result();

// --- Ventas por estado ---
$sql = "SELECT estado, COUNT(*) as total FROM ventas";
if (!$is_global) $sql .= " WHERE empresa_id = ?";
$sql .= " GROUP BY estado";
$stmt = $conexion->prepare($sql);
if (!$is_global) $stmt->bind_param('i', $empresa_id);
$stmt->execute();
$result = $stmt->get_result();
$ventas_estado_data = [];
while($row = $result->fetch_assoc()) {
    $ventas_estado_data[$row['estado']] = $row['total'];
}

// --- Interacciones por tipo ---
$sql = "SELECT tipo, COUNT(*) as total FROM interacciones";
if (!$is_global) $sql .= " WHERE empresa_id = ?";
$sql .= " GROUP BY tipo";
$stmt = $conexion->prepare($sql);
if (!$is_global) $stmt->bind_param('i', $empresa_id);
$stmt->execute();
$result = $stmt->get_result();
$interacciones_tipo_data = [];
while($row = $result->fetch_assoc()) {
    $interacciones_tipo_data[$row['tipo']] = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="estilos.css">
    <title>CRM - Panel Principal</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .cards { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .card { flex: 1; min-width: 200px; background: #f4f4f4; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        .charts { display: flex; gap: 20px; flex-wrap: wrap; justify-content: space-around; }
        .chart-container { flex: 1; max-width: 400px; min-width: 250px; background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        .chart-container canvas { max-height: 250px; }
        table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        table th, table td { padding: 8px; border: 1px solid #ccc; }
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
    <h1>Dashboard</h1>

    <div class="cards">
        <div class="card"><h3>Total Clientes</h3><p><?= $total_clientes ?></p></div>
        <div class="card"><h3>Total Ventas</h3><p><?= $total_ventas ?></p></div>
        <div class="card"><h3>Interacciones</h3><p><?= $total_interacciones ?></p></div>
        <div class="card"><h3>Ganancias (7 días)</h3><p>$<?= number_format($ganancia_semana,2) ?></p></div>
    </div>

    <section>
        <h2>Últimas 5 Ventas</h2>
        <table>
            <tr><th>ID</th><th>Producto</th><th>Monto</th><th>Cliente</th></tr>
            <?php while($row = $ultimas_ventas->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['producto'] ?></td>
                <td>$<?= number_format($row['monto'],2) ?></td>
                <td><?= $row['nombre'].' '.$row['apellido'] ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </section>

    <div class="charts">
        <div class="chart-container">
            <h3>Ventas por Estado</h3>
            <canvas id="ventasEstado"></canvas>
        </div>
        <div class="chart-container">
            <h3>Interacciones por Tipo</h3>
            <canvas id="interaccionesTipo"></canvas>
        </div>
    </div>

    <script>
    new Chart(document.getElementById('ventasEstado'), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_keys($ventas_estado_data)) ?>,
            datasets: [{ data: <?= json_encode(array_values($ventas_estado_data)) ?>, backgroundColor: ['#36a2eb','#4bc0c0','#ff6384','#ffcd56'] }]
        }
    });

    new Chart(document.getElementById('interaccionesTipo'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($interacciones_tipo_data)) ?>,
            datasets: [{ data: <?= json_encode(array_values($interacciones_tipo_data)) ?>, backgroundColor: ['#9966ff','#ff9f40','#ff6384','#4bc0c0','#36a2eb'] }]
        }
    });
    </script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const btn = document.getElementById("btnNotificaciones");
    const modal = document.getElementById("modalNotificaciones");
    const lista = document.getElementById("listaNotificaciones");
    const contador = document.getElementById("contadorNotificaciones");

    // Abrir/cerrar drawer al hacer clic en la campanita
    btn.addEventListener("click", () => {
        const abierto = modal.classList.toggle("abierto"); // toggle y guardamos estado
        if(abierto) {
            cargarNotificaciones();
            marcarLeidas();
        }
    });

    // Cerrar drawer al hacer clic fuera
    document.addEventListener("click", (e) => {
        if (!modal.contains(e.target) && !btn.contains(e.target)) {
            modal.classList.remove("abierto");
        }
    });

    // Cargar notificaciones por AJAX
    function cargarNotificaciones() {
        fetch("ajax_notificaciones.php")
            .then(res => res.json())
            .then(data => {
                lista.innerHTML = "";
                let totalNoLeidas = 0;

                if (!data.notificaciones || data.notificaciones.length === 0) {
                    lista.innerHTML = "<li>No hay notificaciones</li>";
                } else {
                    data.notificaciones.forEach(n => {
                        const li = document.createElement("li");
                        li.textContent = n.mensaje + " (" + n.fecha + ")";
                        if (n.leida == 0) {
                            li.classList.add("no-leida");
                            totalNoLeidas++;
                        }
                        lista.appendChild(li);
                    });
                }

                // Actualizar contador
                contador.textContent = totalNoLeidas;
                contador.style.display = totalNoLeidas > 0 ? "inline-block" : "none";
            })
            .catch(err => console.error("Error cargando notificaciones:", err));
    }

    // Marcar todas como leídas
    function marcarLeidas() {
        fetch("marcar_notificaciones.php")
            .then(() => cargarNotificaciones())
            .catch(err => console.error("Error marcando notificaciones como leídas:", err));
    }

    // Actualizar automáticamente cada 30 segundos
    setInterval(cargarNotificaciones, 30000);

    // Carga inicial
    cargarNotificaciones();
});
</script>
</main>
</body>
</html>
