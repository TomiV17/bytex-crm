<?php
session_start();
if(!isset($_SESSION['usuario_id'])) exit("No autorizado");

include "../../conexion.php";

$empresa_id = $_SESSION['empresa_id'];
$id = $_POST['id'] ?? 0;
$nombre = $_POST['nombre'] ?? '';
$cuit = $_POST['cuit'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$email = $_POST['email'] ?? '';
$direccion = $_POST['direccion'] ?? '';

if(!$nombre){
    exit("El nombre es obligatorio");
}

if($id){
    // Editar proveedor
    $stmt = $conexion->prepare("UPDATE proveedores SET nombre=?, cuit=?, telefono=?, email=?, direccion=? WHERE id=? AND empresa_id=?");
    $stmt->bind_param("sssssi", $nombre, $cuit, $telefono, $email, $direccion, $id, $empresa_id);
    $stmt->execute();
} else {
    // Agregar proveedor
    $stmt = $conexion->prepare("INSERT INTO proveedores (nombre, cuit, telefono, email, direccion, empresa_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $nombre, $cuit, $telefono, $email, $direccion, $empresa_id);
    $stmt->execute();
}

header("Location: ../proveedores.php");
