<?php
session_start();
include "conexion.php";

if (!isset($_GET['id'])) {
    die("ID de presupuesto no especificado");
}

$id = intval($_GET['id']);

// --- Primero eliminar los items ---
$conexion->query("DELETE FROM presupuesto_items WHERE presupuesto_id=$id");

// --- Luego eliminar el presupuesto ---
$conexion->query("DELETE FROM presupuestos WHERE id=$id");

header("Location: presupuestos.php");
exit;
