<?php
session_start();
include "../../conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

$id = $_GET['id'] ?? 0;

$stmt = $conexion->prepare("SELECT * FROM gastos WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$gasto = $stmt->get_result()->fetch_assoc();

if (!$gasto) {
    die("Gasto no encontrado");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'] ?? $gasto['fecha'];
    $monto = $_POST['monto'] ?? $gasto['monto'];
    $categoria = $_POST['categoria'] ?? $gasto['categoria'];
    $descripcion = $_POST['descripcion'] ?? $gasto['descripcion'];
    $estado = $_POST['estado'] ?? $gasto['estado'];

    $stmt = $conexion->prepare("UPDATE gastos SET fecha=?, monto=?, categoria=?, descripcion=?, estado=? WHERE id=?");
    $stmt->bind_param("sdsssi", $fecha, $monto, $categoria, $descripcion, $estado, $id);
    $stmt->execute();

    header("Location: ../gastos.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Gasto</title>
<link rel="stylesheet" href="../../public/css/estilos.css">
</head>
<body>
<?php include "../aside.php"; ?>

<main>
<h1>Editar Gasto</h1>

<form method="post">
    <label>Fecha:</label>
    <input type="date" name="fecha" value="<?= $gasto['fecha'] ?>" required><br><br>

    <label>Monto:</label>
    <input type="number" step="0.01" name="monto" value="<?= $gasto['monto'] ?>" required><br><br>

    <label>Categoria:</label>
    <input type="text" name="categoria" value="<?= htmlspecialchars($gasto['categoria']) ?>" required><br><br>

    <label>Descripción:</label>
    <textarea name="descripcion"><?= htmlspecialchars($gasto['descripcion']) ?></textarea><br><br>

    <label>Estado:</label>
    <select name="estado">
        <option value="pendiente" <?= $gasto['estado']=='pendiente'?'selected':'' ?>>Pendiente</option>
        <option value="pagado" <?= $gasto['estado']=='pagado'?'selected':'' ?>>Pagado</option>
    </select><br><br>

    <button type="submit">Actualizar Gasto</button>
    <a href="../gastos.php" style="margin-left:10px;">Cancelar</a>
</form>
</main>
</body>
</html>
