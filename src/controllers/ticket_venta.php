<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    exit("No autorizado");
}

include "../../conexion.php";

$venta_id = $_GET['id'] ?? 0;
if(!$venta_id) exit("Venta no especificada");

// Traer datos de la venta
$sql = "SELECT v.id, v.metodo_pago, v.fecha, v.estado, c.nombre AS cliente, u.nombre AS usuario
        FROM ventas v
        JOIN clientes c ON v.cliente_id = c.id
        LEFT JOIN usuarios u ON v.usuario_id = u.id
        WHERE v.id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $venta_id);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();

// Traer productos de la venta
$sql = "SELECT vd.cantidad, vd.precio_unitario, p.nombre
        FROM ventas_detalle vd
        JOIN productos p ON vd.producto_id = p.id
        WHERE vd.venta_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $venta_id);
$stmt->execute();
$productos = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ticket Venta #<?= $venta_id ?></title>
<style>
body { font-family: monospace; padding: 20px; }
h2 { text-align:center; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { border-bottom: 1px dashed #000; padding: 5px; text-align:left; }
.total { font-weight:bold; }
</style>
</head>
<body>
<h2>Ticket Venta #<?= $venta_id ?></h2>
<p>Cliente: <?= $venta['cliente'] ?><br>
Vendedor: <?= $venta['usuario'] ?><br>
Método de pago: <?= $venta['metodo_pago'] ?><br>
Fecha: <?= $venta['fecha'] ?><br>

<table>
<tr><th>Producto</th><th>Cant</th><th>Precio</th><th>Subtotal</th></tr>
<?php 
$total = 0;
while($p = $productos->fetch_assoc()):
    $subtotal = $p['cantidad'] * $p['precio_unitario'];
    $total += $subtotal;
?>
<tr>
    <td><?= $p['nombre'] ?></td>
    <td><?= $p['cantidad'] ?></td>
    <td>$<?= number_format($p['precio_unitario'],2) ?></td>
    <td>$<?= number_format($subtotal,2) ?></td>
</tr>
<?php endwhile; ?>
<tr class="total"><td colspan="3">Total</td><td>$<?= number_format($total,2) ?></td></tr>
</table>

<button onclick="window.print()">Imprimir Ticket</button>

<script>


</script>
</body>
</html>
