<?php
session_start();
include "../../conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

$id = $_GET['id'] ?? 0;

$stmt = $conexion->prepare("DELETE FROM gastos WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: ../gastos.php");
exit;
