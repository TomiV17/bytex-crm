<?php
session_start();
include "../../conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

$empresa_id = $_SESSION['empresa_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $cuit = $_POST['cuit'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $email = $_POST['email'] ?? '';

    if (!$nombre) {
        $error = "El nombre es obligatorio.";
    } else {
        $stmt = $conexion->prepare("INSERT INTO proveedores (nombre, cuit, telefono, email, empresa_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $nombre, $cuit, $telefono, $email, $empresa_id);
        $stmt->execute();
        header("Location: ../proveedores.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Agregar Proveedor</title>
<link rel="stylesheet" href="../../public/css/estilos.css">
</head>
<body>
<style>.submenu { display: none; margin-left: 10px; }
.submenu-btn { 
    background: none; 
    border: none; 
    font-size: 16px; 
    width: 100%; 
    text-align: left; 
    padding: 6px 0; 
    color: #222225; /* <- esto hace que el texto sea negro */
}
.submenu a {
    display: block;
    padding: 3px 0;
    color: #222225; /* texto negro */
    text-decoration: none; /* opcional: quita el subrayado */
}</style>
<main>
<h1>Agregar Proveedor</h1>
<?php if(!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
<form method="post">
    <label>Nombre:</label><br>
    <input type="text" name="nombre" required><br><br>
    <label>CUIT:</label><br>
    <input type="text" name="cuit"><br><br>
    <label>Teléfono:</label><br>
    <input type="text" name="telefono"><br><br>
    <label>Email:</label><br>
    <input type="email" name="email"><br><br>
    <button type="submit">Guardar Proveedor</button>
</form>
<a href="../proveedores.php">Volver</a>
</main>
</body>
<script>
    document.querySelectorAll('.submenu-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const submenu = btn.nextElementSibling;
        submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
    });
});
</script>
</html>
