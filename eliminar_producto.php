<?php
session_start();
include "conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$empresa_id = $_SESSION['empresa_id'];
$is_global = $_SESSION['is_global_admin'] ?? false;

if (!isset($_GET['id'])) {
    header("Location: productos.php");
    exit;
}

$id = intval($_GET['id']);

// Verificar producto y permisos
$stmt = $conexion->prepare("SELECT empresa_id FROM productos WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();
$stmt->close();

if (!$producto || (!$is_global && $producto['empresa_id'] != $empresa_id)) {
    header("Location: productos.php");
    exit;
}

// Eliminar producto
$stmt = $conexion->prepare("DELETE FROM productos WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: productos.php");
exit;
?>
