<?php
session_start();
include "../../conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

// Traer proveedores
$empresa_id = $_SESSION['empresa_id'];
$result_proveedores = $conexion->query("SELECT * FROM proveedores WHERE empresa_id = $empresa_id ORDER BY nombre ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Agregar Compra</title>
<link rel="stylesheet" href="../../public/css/estilos.css">
</head>
<body>
<main>
<h1>Agregar Compra</h1>
<form method="post" action="guardar_compra.php">
    <label for="proveedor_id">Proveedor:</label>
    <select name="proveedor_id" id="proveedor_id" required>
        <option value="">Seleccione...</option>
        <?php while($p=$result_proveedores->fetch_assoc()): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
        <?php endwhile; ?>
    </select><br><br>

    <label for="fecha">Fecha:</label>
    <input type="date" name="fecha" id="fecha" value="<?= date('Y-m-d') ?>" required><br><br>

    <label for="total">Total:</label>
    <input type="number" name="total" id="total" step="0.01" required><br><br>

    <label for="estado">Estado:</label>
    <select name="estado" id="estado" required>
        <option value="pendiente">Pendiente</option>
        <option value="pagada">Pagada</option>
    </select><br><br>

    <button type="submit">Guardar Compra</button>
</form>
<a href="../compras.php">Volver</a>
</main>
</body>
</html>
