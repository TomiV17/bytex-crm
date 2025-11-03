<?php
session_start();
include "../../conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

$id = $_GET['id'] ?? 0;

if ($id) {
    $stmt = $conexion->prepare("DELETE FROM proveedores WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: ../proveedores.php");
exit;
