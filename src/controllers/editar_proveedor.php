<?php
session_start();
include "../../conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

$id = $_GET['id'] ?? 0;
$stmt = $conexion->prepare("SELECT * FROM proveedores WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$proveedor = $stmt->get_result()->fetch_assoc();

if (!$proveedor) {
    echo "Proveedor no encontrado";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $cuit = $_POST['cuit'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $email = $_POST['email'] ?? '';

    if ($nombre) {
        $stmt = $conexion->prepare("UPDATE proveedores SET nombre=?, cuit=?, telefono=?, email=? WHERE id=?");
        $stmt->bind_param("ssssi", $nombre, $cuit, $telefono, $email, $id);
        $stmt->execute();
        header("Location: ../proveedores.php");
        exit;
    } else {
        $error = "El nombre es obligatorio.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Proveedor</title>
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
<h1>Editar Proveedor</h1>
<?php if(!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
<form method="post">
    <label>Nombre:</label><br>
    <input type="text" name="nombre" value="<?= htmlspecialchars($proveedor['nombre']) ?>" required><br><br>
    <label>CUIT:</label><br>
    <input type="text" name="cuit" value="<?= htmlspecialchars($proveedor['cuit']) ?>"><br><br>
    <label>Teléfono:</label><br>
    <input type="text" name="telefono" value="<?= htmlspecialchars($proveedor['telefono']) ?>"><br><br>
    <label>Email:</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars($proveedor['email']) ?>"><br><br>
    <button type="submit">Actualizar Proveedor</button>
</form>
<a href="../proveedores.php">Volver</a>
</main>
<script>
    document.querySelectorAll('.submenu-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const submenu = btn.nextElementSibling;
        submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
    });
});
</script>
</body>
</html>
