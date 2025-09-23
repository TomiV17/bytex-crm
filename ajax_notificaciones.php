<?php
session_start();
include "conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

$sql = "SELECT id, mensaje, fecha, leida 
        FROM notificaciones 
        WHERE usuario_id = ? 
        ORDER BY fecha DESC LIMIT 20";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$notificaciones = [];
$contador = 0;

while($n = $result->fetch_assoc()) {
    $notificaciones[] = $n;
    if ($n['leida'] == 0) $contador++;
}

header("Content-Type: application/json");
echo json_encode([
    'notificaciones' => $notificaciones,
    'contador' => $contador
]);
