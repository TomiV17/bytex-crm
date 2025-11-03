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
$params = [];
$types = '';

if (!$is_global) {
    $where[] = "g.empresa_id = ?";
    $params[] = $empresa_id;
    $types .= 'i';
}

$buscar = $_GET['buscar'] ?? '';
if ($buscar) {
    $where[] = "(g.descripcion LIKE ? OR g.categoria LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $types .= 'ss';
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// --- Query gastos ---
$sql = "SELECT g.id, g.fecha, g.monto, g.categoria, g.descripcion, g.estado, u.nombre AS usuario_nombre
        FROM gastos g
        LEFT JOIN usuarios u ON g.creado_por = u.id
        $where_sql
        ORDER BY g.fecha DESC";

$stmt = $conexion->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>CGastos</title>
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
<style>.submenu { display: none; margin-left: 10px; }
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
}</style>

<main>
<h1>Gastos</h1>
<a href="controllers/crear_gasto.php" class="button">Registrar Gasto</a>

<form method="get" style="margin: 15px 0;">
    <input type="text" name="buscar" placeholder="Buscar..." value="<?= htmlspecialchars($buscar) ?>" style="padding:5px; width:200px;">
    <button type="submit">Buscar</button>
    <a href="gastos.php" style="margin-left:10px;">Limpiar</a>
</form>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Monto</th>
            <th>Categoria</th>
            <th>Descripción</th>
            <th>Estado</th>
            <th>Registrado por</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if($result->num_rows>0): ?>
            <?php while($g=$result->fetch_assoc()): ?>
            <tr>
                <td><?= $g['id'] ?></td>
                <td><?= $g['fecha'] ?></td>
                <td>$<?= number_format($g['monto'],2) ?></td>
                <td><?= htmlspecialchars($g['categoria']) ?></td>
                <td><?= htmlspecialchars($g['descripcion']) ?></td>
                <td><?= ucfirst($g['estado']) ?></td>
                <td><?= htmlspecialchars($g['usuario_nombre']) ?></td>
                <td>
                    <a href="controllers/editar_gasto.php?id=<?= $g['id'] ?>">Editar</a> |
                    <a href="controllers/eliminar_gasto.php?id=<?= $g['id'] ?>" onclick="return confirm('¿Eliminar gasto?')">Eliminar</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8" style="text-align:center;">No hay gastos registrados</td></tr>
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
