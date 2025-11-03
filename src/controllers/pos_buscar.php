<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Content-Type: application/json");
    echo json_encode([]);
    exit;
}

include "../../conexion.php";

$q = isset($_GET['q']) ? trim($_GET['q']) : "";

$sql = "SELECT id, nombre, precio, stock FROM productos WHERE 1";
$params = [];

if ($q !== "") {
    $sql .= " AND nombre LIKE ?";
    $params[] = "%" . $q . "%";
}

$stmt = $conexion->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param("s", $params[0]);
}

$stmt->execute();
$result = $stmt->get_result();

$productos = [];
while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}

header("Content-Type: application/json");
echo json_encode($productos);
