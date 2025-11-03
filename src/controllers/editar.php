<?php
session_start();

// Si no hay sesión activa, redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
include "../../conexion.php";

$id = $_GET['id'];
$result = $conexion->query("SELECT * FROM clientes WHERE id=$id");
$cliente = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $empresa = $_POST['empresa'];
    $estado = $_POST['estado'];

    $stmt = $conexion->prepare("UPDATE clientes SET nombre=?, apellido=?, email=?, telefono=?, empresa=?, estado=? WHERE id=?");
    $stmt->bind_param("ssssssi", $nombre, $apellido, $email, $telefono, $empresa, $estado, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cliente</title>
    <link rel="stylesheet" href="../../public/css/estilos.css">
</head>
<body>
<aside>
    <h2>Bytex Manager</h2>
    <nav class="menu">
        <button id="btnNotificaciones" class="campana">
            🔔 <span id="contadorNotificaciones">0</span>
        </button>

        <div class="menu-links">
            <a href="../../index.php">Panel</a>

            <!-- Submenu Operaciones -->
            <button class="submenu-btn">Operaciones ▾</button>
            <div class="submenu">
                <a href="../ventas.php">Ventas</a>
                <a href="../productos.php">Productos</a>
                <a href="../presupuestos.php">Presupuestos</a>
            </div>

            <!-- Submenu Gestión -->
            <button class="submenu-btn">Gestión ▾</button>
            <div class="submenu">
                <a href="../clientes.php">Clientes</a>
                <a href="../interacciones.php">Interacciones</a>
                <a href="../tareas.php">Tareas</a>
            </div>

            <a href="../usuarios.php">Usuarios</a>
            <a href="../empresas.php">Empresas</a>
            <a href="../proveedores.php" class="activo">Proveedores</a>
        </div>

        <div class="logout">
            <a href="../../public/logout.php">Cerrar Sesión</a>
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


    <!-- Contenido principal -->
    <main>
        <h1>Editar Cliente</h1>

        <section>
            <form method="POST" class="formulario">
                <label>Nombre:</label>
                <input type="text" name="nombre" value="<?= $cliente['nombre'] ?>" required>

                <label>Apellido:</label>
                <input type="text" name="apellido" value="<?= $cliente['apellido'] ?>">

                <label>Email:</label>
                <input type="email" name="email" value="<?= $cliente['email'] ?>">

                <label>Teléfono:</label>
                <input type="text" name="telefono" value="<?= $cliente['telefono'] ?>">

                <label>Empresa:</label>
                <input type="text" name="empresa" value="<?= $cliente['empresa'] ?>">

                <label>Estado:</label>
                <select name="estado">
                    <option value="nuevo" <?= $cliente['estado']=='nuevo' ? 'selected' : '' ?>>Nuevo</option>
                    <option value="contactado" <?= $cliente['estado']=='contactado' ? 'selected' : '' ?>>Contactado</option>
                    <option value="en seguimiento" <?= $cliente['estado']=='en seguimiento' ? 'selected' : '' ?>>En seguimiento</option>
                    <option value="cliente" <?= $cliente['estado']=='cliente' ? 'selected' : '' ?>>Cliente</option>
                    <option value="perdido" <?= $cliente['estado']=='perdido' ? 'selected' : '' ?>>Perdido</option>
                </select>

                <button type="submit" class="button">Actualizar</button>
            </form>

            <a href="../clientes.php" class="button link">Volver</a>
        </section>
    </main>
</body>
<script>
    document.querySelectorAll('.submenu-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const submenu = btn.nextElementSibling;
        submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
    });
});
</script>
</html>

