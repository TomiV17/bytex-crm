<?php
session_start();
include "conexion.php";

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
    $where[] = "c.empresa_id = ?";
    $params[] = $empresa_id;
    $types .= 'i';
}

$buscar = $_GET['buscar'] ?? '';
if ($buscar) {
    $where[] = "(c.nombre LIKE ? OR c.apellido LIKE ? OR c.email LIKE ?)";
    $like = "%$buscar%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// --- Preparar query con JOIN a empresas ---
$sql = "
SELECT c.*, e.nombre AS empresa_nombre
FROM clientes c
LEFT JOIN empresas e ON c.empresa_id = e.id
$where_sql
ORDER BY c.id DESC
";
$stmt = $conexion->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRM - Clientes</title>
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
    <h1>Clientes</h1>
    <a href="crear_cliente.php" class="button">Agregar Cliente</a>

    <!-- Buscador -->
    <form method="get" style="margin: 15px 0;">
        <input type="text" name="buscar" placeholder="Buscar cliente..." value="<?= htmlspecialchars($buscar) ?>" style="padding:5px; width:200px;">
        <button type="submit">Buscar</button>
        <a href="clientes.php" style="margin-left:10px;">Limpiar</a>
    </form>

    <section>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Empresa</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                        <td><?= htmlspecialchars($row['apellido']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['telefono']) ?></td>
                        <td><?= htmlspecialchars($row['empresa_nombre'] ?? 'Sin empresa') ?></td>
                        <td><?= ucfirst($row['estado']) ?></td>
                        <td>
                            <a href="editar_cliente.php?id=<?= $row['id'] ?>">Editar</a> |
                            <a href="eliminar_cliente.php?id=<?= $row['id'] ?>" onclick="return confirm('¿Eliminar cliente?')">Eliminar</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align:center;">No se encontraron clientes</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
