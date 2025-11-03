<?php
session_start();
include "../../conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

$empresa_id = $_SESSION['empresa_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $monto = $_POST['monto'] ?? 0;
    $categoria = $_POST['categoria'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $estado = $_POST['estado'] ?? 'pendiente';
    $creado_por = $_SESSION['usuario_id'];

    $stmt = $conexion->prepare("INSERT INTO gastos (empresa_id, fecha, monto, categoria, descripcion, estado, creado_por) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isdsssi", $empresa_id, $fecha, $monto, $categoria, $descripcion, $estado, $creado_por);
    $stmt->execute();

    header("Location: ../gastos.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registrar Gasto</title>
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
                <a href="../proveedores.php">Proveedores</a>
                <a href="../compras.php" class="activo">Compras</a>
                <a href="../gastos.php">Gastos</a>
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
<h1>Registrar Gasto</h1>

<form method="post">
    <label>Fecha:</label>
    <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required><br><br>

    <label>Monto:</label>
    <input type="number" step="0.01" name="monto" required><br><br>

    <label>Categoria:</label>
    <input type="text" name="categoria" required><br><br>

    <label>Descripción:</label>
    <textarea name="descripcion"></textarea><br><br>

    <label>Estado:</label>
    <select name="estado">
        <option value="pendiente">Pendiente</option>
        <option value="pagado">Pagado</option>
    </select><br><br>

    <button type="submit">Guardar Gasto</button>
    <a href="../gastos.php" style="margin-left:10px;">Cancelar</a>
</form>
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
