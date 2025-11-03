<?php
session_start();

// Si no hay sesión activa, redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
include "../../conexion.php";

$id = $_GET['id'];
$conexion->query("DELETE FROM clientes WHERE id=$id");

header("Location: ../../public/index.php");
?>
