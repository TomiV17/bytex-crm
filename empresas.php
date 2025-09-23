<?php
session_start();
include "conexion.php";

// Solo admin global puede acceder
if (!isset($_SESSION['usuario_id']) || !($_SESSION['is_global_admin'] ?? false)) {
    header("Location: index.php");
    exit;
}

// --- Eliminar empresa si se pasa id en GET (opcional) ---
if (!empty($_GET['eliminar_id'])) {
    $id = intval($_GET['eliminar_id']);
    $stmt = $conexion->prepare("DELETE FROM empresas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: empresas.php");
    exit;
}

// --- Listar empresas ---
$result = $conexion->query("SELECT * FROM empresas ORDER BY nombre ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRM - Empresas</title>
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
    <h1>Empresas</h1>
    <a href="crear_empresa.php" class="button">Agregar Empresa</a>
    <section>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Fecha Creación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                        <td><?= $row['fecha_creacion'] ?? '' ?></td>
                        <td>
                            <a href="editar_empresa.php?id=<?= $row['id'] ?>">Editar</a> |
                            <a href="empresas.php?eliminar_id=<?= $row['id'] ?>" onclick="return confirm('¿Eliminar empresa?')">Eliminar</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align:center;">No hay empresas</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
