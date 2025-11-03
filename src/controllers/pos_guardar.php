<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(["ok" => false, "error" => "No autorizado"]);
    exit;
}

include "../../conexion.php";

$data = json_decode(file_get_contents("php://input"), true);
$carrito = $data['carrito'] ?? [];
$metodo = $data['metodo'] ?? "";

if (empty($carrito)) {
    echo json_encode(["ok" => false, "error" => "Carrito vacío"]);
    exit;
}

$conexion->begin_transaction();

try {
    $cliente_id = $data['cliente_id'] ?? 0;
    $empresa_id = $data['empresa_id'] ?? $_SESSION['empresa_id'];

    if (!$cliente_id) {
        throw new Exception("Cliente no especificado");
    }

    // **Todas las ventas se crean como confirmadas**
    $estado = 'cerrada';

    // Insertar venta principal
    $stmt = $conexion->prepare("
        INSERT INTO ventas (cliente_id, usuario_id, metodo_pago, empresa_id, estado, fecha)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iiiss", $cliente_id, $_SESSION['usuario_id'], $metodo, $empresa_id, $estado);
    $stmt->execute();
    $venta_id = $stmt->insert_id;

    // Preparar detalle de venta y actualización de stock
    $stmtItem = $conexion->prepare("
        INSERT INTO ventas_detalle (venta_id, producto_id, cantidad, precio_unitario)
        VALUES (?, ?, ?, ?)
    ");
    $stmtStock = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");

    foreach ($carrito as $item) {
        $stmtItem->bind_param("iiid", $venta_id, $item['id'], $item['cantidad'], $item['precio']);
        $stmtItem->execute();

        $stmtStock->bind_param("ii", $item['cantidad'], $item['id']);
        $stmtStock->execute();
    }

    $conexion->commit();
    echo json_encode(["ok" => true, "venta_id" => $venta_id]);

} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}

