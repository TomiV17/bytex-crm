<?php
session_start();
include "../conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$empresa_id = $_SESSION['empresa_id'];
$is_global = $_SESSION['is_global_admin'] ?? false;

// --- Filtros ---
$where = [];
if (!$is_global) {
    $where[] = "c.empresa_id = " . intval($empresa_id);
}
if (!empty($_GET['fecha_inicio']) && !empty($_GET['fecha_fin'])) {
    $fecha_inicio = $conexion->real_escape_string($_GET['fecha_inicio']);
    $fecha_fin = $conexion->real_escape_string($_GET['fecha_fin']);
    $where[] = "DATE(i.fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}
if (!empty($_GET['tipo'])) {
    $tipo = $conexion->real_escape_string($_GET['tipo']);
    $where[] = "i.tipo = '$tipo'";
}
if (!empty($_GET['buscar'])) {
    $buscar = $conexion->real_escape_string($_GET['buscar']);
    $where[] = "(c.nombre LIKE '%$buscar%' OR u.nombre LIKE '%$buscar%' OR i.tipo LIKE '%$buscar%')";
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// --- Consulta principal ---
$query = "
SELECT i.id, c.nombre AS cliente_nombre, u.nombre AS usuario_nombre, i.tipo, i.detalle, i.fecha
FROM interacciones i
JOIN clientes c ON i.cliente_id = c.id
LEFT JOIN usuarios u ON i.usuario_id = u.id
$where_sql
ORDER BY i.fecha DESC
";
$result = $conexion->query($query);

// --- Tipos de interacción ---
$tipos_result = $conexion->query("SELECT DISTINCT tipo FROM interacciones ORDER BY tipo ASC");

// --- Interacciones por tipo ---
$tipo_result = $conexion->query("
SELECT i.tipo, COUNT(*) AS total
FROM interacciones i
JOIN clientes c ON i.cliente_id = c.id
" . ($where_sql ? $where_sql : "") . "
GROUP BY i.tipo
");
$interacciones_tipo = [];
while($row = $tipo_result->fetch_assoc()) {
    $interacciones_tipo[$row['tipo']] = $row['total'];
}

// --- Interacciones por usuario ---
$usuario_result = $conexion->query("
SELECT u.nombre, COUNT(*) AS total
FROM interacciones i
LEFT JOIN usuarios u ON i.usuario_id = u.id
JOIN clientes c ON i.cliente_id = c.id
" . ($where_sql ? $where_sql : "") . "
GROUP BY u.id
");
$interacciones_usuario = [];
while($row = $usuario_result->fetch_assoc()) {
    $interacciones_usuario[] = ['nombre'=>$row['nombre'] ?? 'Sin Asignar', 'total'=>$row['total']];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Interacciones</title>
<link rel="stylesheet" href="../public/css/estilos.css">
<style>
.graficos { display:flex; flex-wrap:wrap; gap:20px; }
.graficos canvas { flex:1 1 300px; max-width:500px; height:300px; }
@media(max-width:768px){
    .graficos::before { content:"Gráficos no recomendados en móviles o tablets"; display:block; width:100%; margin-bottom:10px; font-weight:bold; color:#c0392b; }
}
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { border:1px solid #ccc; padding:8px; text-align:left; }
</style>
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
<h1>Interacciones</h1>
<a href="controllers/crear_interaccion.php" class="button">Agregar Interacción</a>

<!-- Filtros -->
<section>
<form method="get" style="margin:15px 0;">
    <label>Desde:</label>
    <input type="date" name="fecha_inicio" value="<?= $_GET['fecha_inicio'] ?? '' ?>">
    <label>Hasta:</label>
    <input type="date" name="fecha_fin" value="<?= $_GET['fecha_fin'] ?? '' ?>">
    <label>Tipo:</label>
    <select name="tipo">
        <option value="">-- Todos --</option>
        <?php while($t = $tipos_result->fetch_assoc()): ?>
            <option value="<?= $t['tipo'] ?>" <?= (($_GET['tipo'] ?? '')==$t['tipo'])?'selected':'' ?>><?= $t['tipo'] ?></option>
        <?php endwhile; ?>
    </select>
    <input type="text" name="buscar" placeholder="Buscar cliente, usuario o tipo..." value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
    <button type="submit">Aplicar</button>
    <a href="interacciones.php" style="margin-left:10px;">Limpiar</a>
</form>
</section>

<!-- Gráficos -->
<section class="graficos">
    <canvas id="interaccionesTipoChart"></canvas>
    <canvas id="interaccionesUsuarioChart"></canvas>
</section>

<!-- Tabla -->
<section>
<table>
<thead>
<tr>
<th>ID</th>
<th>Cliente</th>
<th>Usuario</th>
<th>Tipo</th>
<th>Detalle</th>
<th>Fecha</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php if($result->num_rows>0): ?>
<?php while($row=$result->fetch_assoc()): ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= $row['cliente_nombre'] ?></td>
<td><?= $row['usuario_nombre'] ?></td>
<td><?= ucfirst($row['tipo']) ?></td>
<td><?= $row['detalle'] ?></td>
<td><?= $row['fecha'] ?></td>
<td>
<a href="controllers/editar_interaccion.php?id=<?= $row['id'] ?>">Editar</a> |
<a href="controllers/eliminar_interaccion.php?id=<?= $row['id'] ?>" onclick="return confirm('¿Eliminar interacción?')">Eliminar</a>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="7" style="text-align:center;">No se encontraron interacciones</td></tr>
<?php endif; ?>
</tbody>
</table>
</section>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Interacciones por tipo
const ctxTipo = document.getElementById('interaccionesTipoChart').getContext('2d');
new Chart(ctxTipo, {
    type:'pie',
    data:{
        labels:[<?= implode(',', array_map(fn($k)=>"'$k'", array_keys($interacciones_tipo))) ?>],
        datasets:[{ data:[<?= implode(',', array_values($interacciones_tipo)) ?>], backgroundColor:['#f39c12','#27ae60','#c0392b','#8e44ad','#3498db'] }]
    },
    options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}} }
});

// Interacciones por usuario
const ctxUsuario = document.getElementById('interaccionesUsuarioChart').getContext('2d');
new Chart(ctxUsuario,{
    type:'bar',
    data:{
        labels:[<?= implode(',', array_map(fn($u)=>"'".$u['nombre']."'", $interacciones_usuario)) ?>],
        datasets:[{ label:'Cantidad de interacciones', data:[<?= implode(',', array_map(fn($u)=>$u['total'], $interacciones_usuario)) ?>], backgroundColor:'#0078d4' }]
    },
    options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
});
document.querySelectorAll('.submenu-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const submenu = btn.nextElementSibling;
        submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
    });
});
</script>
</body>
</html>
