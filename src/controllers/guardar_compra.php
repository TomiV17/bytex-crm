<?php
session_start();
include "../../conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

$proveedor_id = $_POST['proveedor_id'] ?? 0;
$fecha = $_POST['fecha'] ?? date('Y-m-d');
$total = $_POST['total'] ?? 0;
$estado = $_POST['estado'] ?? 'pendiente';
$empresa_id = $_SESSION['empresa_id'];

if (!$proveedor_id || $total <= 0) {
    die("Datos incompletos");
}

$stmt = $conexion->prepare("INSERT INTO compras (proveedor_id, fecha, total, estado, empresa_id) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("isdsi", $proveedor_id, $fecha, $total, $estado, $empresa_id);
$stmt->execute();

header("Location: ../compras.php");
