<?php
session_start();
include "../../conexion.php";

// Solo admin puede acceder
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../../index.php");
    exit;
}

$empresa_id = $_SESSION['empresa_id'];
$is_global = $_SESSION['is_global_admin'] ?? false;

// Obtener empresas solo si es admin global
$empresas = [];
if ($is_global) {
    $res = $conexion->query("SELECT id, nombre FROM empresas ORDER BY nombre ASC");
    while($row = $res->fetch_assoc()) {
        $empresas[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = $_POST['password']; // ⚠️ luego conviene hashear
    $rol = $_POST['rol'];

    // Determinar empresa_id
    if ($is_global && !empty($_POST['empresa_id'])) {
        $user_empresa_id = intval($_POST['empresa_id']);
    } else {
        // Si no es global, siempre se asigna la empresa del admin logueado
        $user_empresa_id = $empresa_id;
    }

    $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, password, rol, empresa_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $nombre, $email, $password, $rol, $user_empresa_id);
    $stmt->execute();
    $stmt->close();

    header("Location: ../usuarios.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Usuario</title>
    <link rel="stylesheet" href="../../public/css/estilos.css">
</head>
<body>
<aside>
    <h2>CRM</h2>
    <nav class="menu">
        <button id="btnNotificaciones" class="campana">
            🔔 <span id="contadorNotificaciones">0</span>
        </button>

        <div class="menu-links">
            <a href="../index.php">Panel</a>

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

<main>
    <h1>Agregar Usuario</h1>
    <form method="POST">
        <label>Nombre:</label><br>
        <input type="text" name="nombre" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Contraseña:</label><br>
        <input type="text" name="password" required><br><br>

        <label>Rol:</label><br>
        <select name="rol" required>
            <option value="vendedor">Vendedor</option>
            <option value="admin">Admin</option>
        </select><br><br>

        <?php if($is_global): ?>
        <label>Empresa:</label><br>
        <select name="empresa_id" required>
            <?php foreach($empresas as $e): ?>
                <option value="<?= $e['id'] ?>"><?= $e['nombre'] ?></option>
            <?php endforeach; ?>
        </select><br><br>
        <?php endif; ?>

        <button type="submit">Guardar</button>
    </form>
    <br>
    <a href="../usuarios.php">Volver</a>
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
