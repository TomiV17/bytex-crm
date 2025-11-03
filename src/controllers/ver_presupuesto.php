<?php
session_start();
include "../../conexion.php";

if (!isset($_GET['id'])) {
    die("ID de presupuesto no especificado");
}

$id = intval($_GET['id']);

// --- Obtener datos del presupuesto ---
$query_presupuesto = "
    SELECT p.id, p.fecha_creacion, c.nombre AS cliente, u.nombre AS usuario, e.nombre AS empresa
    FROM presupuestos p
    JOIN clientes c ON p.cliente_id = c.id
    JOIN usuarios u ON p.usuario_id = u.id
    JOIN empresas e ON p.empresa_id = e.id
    WHERE p.id = $id
";
$presupuesto = $conexion->query($query_presupuesto)->fetch_assoc();

if (!$presupuesto) {
    die("Presupuesto no encontrado");
}

// --- Obtener ítems del presupuesto ---
$query_items = "
    SELECT pi.cantidad, pi.precio, pr.nombre AS producto
    FROM presupuesto_items pi
    JOIN productos pr ON pi.producto_id = pr.id
    WHERE pi.presupuesto_id = $id
";
$items = $conexion->query($query_items);

// Calcular total
$total = 0;
foreach ($items as $it) {
    $total += $it['cantidad'] * $it['precio'];
}
$items->data_seek(0); // Reiniciar puntero
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Presupuesto #<?= $presupuesto['id'] ?></title>
<style>
    body { font-family: Arial, sans-serif; margin: 40px; color: #333; }
    header { text-align: center; margin-bottom: 40px; }
    header h1 { margin: 0; font-size: 28px; }
    header h2 { margin: 5px 0 0; font-weight: normal; font-size: 18px; }
    .datos { display: flex; justify-content: space-between; margin-bottom: 30px; }
    .datos div { width: 48%; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
    th { background: #f5f5f5; }
    .total { text-align: right; font-weight: bold; }
    footer { text-align: center; font-size: 12px; color: #555; margin-top: 40px; }
    button { padding: 10px 15px; margin-right: 10px; cursor: pointer; }
</style>
</head>
<body>
<header>
    <h1><?= $presupuesto['empresa'] ?></h1>
    <h2>Presupuesto #<?= $presupuesto['id'] ?></h2>
</header>

<div class="datos">
    <div>
        <p><strong>Cliente:</strong> <?= $presupuesto['cliente'] ?></p>
        <p><strong>Usuario:</strong> <?= $presupuesto['usuario'] ?></p>
    </div>
    <div style="text-align:right;">
        <p><strong>Fecha:</strong> <?= $presupuesto['fecha_creacion'] ?></p>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Precio Unitario</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $items->fetch_assoc()): ?>
        <tr>
            <td><?= $row['producto'] ?></td>
            <td><?= $row['cantidad'] ?></td>
            <td>$<?= number_format($row['precio'], 2) ?></td>
            <td>$<?= number_format($row['cantidad'] * $row['precio'], 2) ?></td>
        </tr>
        <?php endwhile; ?>
        <tr>
            <td colspan="3" class="total">TOTAL</td>
            <td>$<?= number_format($total, 2) ?></td>
        </tr>
    </tbody>
</table>

<div>
    <button onclick="window.print()">Imprimir</button>
    <a href="../presupuestos.php"><button>Volver</button></a>
</div>

<footer>
    Presupuesto generado por CRM - <?= $presupuesto['empresa'] ?>
</footer>
</body>
</html>
