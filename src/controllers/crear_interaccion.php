<?php
session_start();
include "../../conexion.php";

// Si no hay sesión activa, redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Capturar empresa y rol global
$empresa_id = isset($_SESSION['empresa_id']) ? intval($_SESSION['empresa_id']) : 0;
$is_global = $_SESSION['is_global_admin'] ?? false;

// --- Obtener clientes y usuarios según empresa ---
$cliente_query = "SELECT id, nombre FROM clientes";
if (!$is_global && $empresa_id > 0) {
    $cliente_query .= " WHERE empresa_id = $empresa_id";
}
$cliente_query .= " ORDER BY nombre";
$clientes = $conexion->query($cliente_query);

$usuario_query = "SELECT id, nombre FROM usuarios";
if (!$is_global && $empresa_id > 0) {
    $usuario_query .= " WHERE empresa_id = $empresa_id";
}
$usuario_query .= " ORDER BY nombre";
$usuarios = $conexion->query($usuario_query);

// --- Procesar formulario ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cliente_id = intval($_POST['cliente_id']);
    $usuario_id = !empty($_POST['usuario_id']) ? intval($_POST['usuario_id']) : NULL;
    $tipo = $_POST['tipo'];
    $detalle = $_POST['detalle'];

    $stmt = $conexion->prepare("
        INSERT INTO interacciones (cliente_id, usuario_id, tipo, detalle, empresa_id) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iissi", $cliente_id, $usuario_id, $tipo, $detalle, $empresa_id);
    $stmt->execute();
    $stmt->close();

    header("Location: interacciones.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Interacción</title>
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
        <h1>Agregar Interacción</h1>

        <section>
            <form method="POST" class="formulario">
                <label>Cliente:</label>
                <select name="cliente_id" required>
                    <?php while($c = $clientes->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endwhile; ?>
                </select>

                <label>Usuario:</label>
                <select name="usuario_id">
                    <option value="">-- Ninguno --</option>
                    <?php while($u = $usuarios->fetch_assoc()): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                    <?php endwhile; ?>
                </select>

                <label>Tipo:</label>
                <select name="tipo" required>
                    <option value="llamada">Llamada</option>
                    <option value="email">Email</option>
                    <option value="reunion">Reunión</option>
                    <option value="nota">Nota</option>
                </select>

                <label>Detalle:</label>
                <textarea name="detalle" required></textarea>

                <button type="submit" class="button">Guardar</button>
            </form>

            <a href="../interacciones.php" class="button link">Volver</a>
        </section>
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
