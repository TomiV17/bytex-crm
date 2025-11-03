<?php
session_start();
include "../../conexion.php";

if (!isset($_GET['id'])) die("ID de presupuesto no especificado");
$id = intval($_GET['id']);

// --- Multiempresa ---
$empresa_id = $_SESSION['empresa_id'];
$is_global = $_SESSION['is_global_admin'] ?? false;

// --- Obtener presupuesto ---
$presupuesto = $conexion->query("SELECT * FROM presupuestos WHERE id=$id")->fetch_assoc();
if (!$presupuesto) die("Presupuesto no encontrado");

// --- Obtener clientes, usuarios y productos ---
$clientes = $conexion->query("SELECT id,nombre FROM clientes" . (!$is_global?" WHERE empresa_id=$empresa_id":"") . " ORDER BY nombre");
$usuarios = $conexion->query("SELECT id,nombre FROM usuarios" . (!$is_global?" WHERE empresa_id=$empresa_id":"") . " ORDER BY nombre");
$productos = $conexion->query("SELECT id,nombre,precio FROM productos" . (!$is_global?" WHERE empresa_id=$empresa_id":"") . " ORDER BY nombre");

// --- Obtener ítems existentes ---
$items = [];
$res_items = $conexion->query("SELECT pi.id, pi.producto_id, pi.cantidad, pi.precio, p.nombre AS producto_nombre 
                               FROM presupuesto_items pi 
                               JOIN productos p ON pi.producto_id=p.id
                               WHERE pi.presupuesto_id=$id");
while($row = $res_items->fetch_assoc()) $items[] = $row;

// --- Procesar formulario ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = intval($_POST['cliente_id']);
    $usuario_id = intval($_POST['usuario_id']);

    // --- Actualizar presupuesto ---
    $stmt = $conexion->prepare("UPDATE presupuestos SET cliente_id=?, usuario_id=? WHERE id=?");
    $stmt->bind_param("iii", $cliente_id, $usuario_id, $id);
    $stmt->execute();
    $stmt->close();

    // --- Actualizar ítems existentes ---
    if (!empty($_POST['item_id'])) {
        foreach ($_POST['item_id'] as $index => $item_id) {
            $cantidad = intval($_POST['cantidad'][$index]);
            $precio = floatval($_POST['precio'][$index]);
            $conexion->query("UPDATE presupuesto_items SET cantidad=$cantidad, precio=$precio WHERE id=$item_id");
        }
    }

    // --- Agregar nuevos ítems ---
    if (!empty($_POST['nuevo_producto_id'])) {
        foreach ($_POST['nuevo_producto_id'] as $index => $prod_id) {
            $prod_id = intval($prod_id);
            $cant = intval($_POST['nuevo_cantidad'][$index]);
            $prec = floatval($_POST['nuevo_precio'][$index]);
            if($prod_id>0 && $cant>0){
                $stmt = $conexion->prepare("INSERT INTO presupuesto_items (presupuesto_id, producto_id, cantidad, precio) VALUES (?,?,?,?)");
                $stmt->bind_param("iiid", $id, $prod_id, $cant, $prec);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // --- Eliminar ítems marcados ---
    if (!empty($_POST['eliminar_item'])) {
        foreach ($_POST['eliminar_item'] as $del_id) {
            $del_id = intval($del_id);
            $conexion->query("DELETE FROM presupuesto_items WHERE id=$del_id");
        }
    }

    header("Location: ver_presupuesto.php?id=$id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Presupuesto</title>
<link rel="stylesheet" href="../../public/css/estilos.css">
<style>
.item-row { display:flex; gap:10px; margin-bottom:5px; }
.item-row input, .item-row select { width:100px; }
</style>
</head>
<body>
<aside>
    <h2>Bytex Manager</h2>
    <nav class="menu">
        <button id="btnNotificaciones" class="campana">
            🔔 <span id="contadorNotificaciones">0</span>
        </button>

        <div class="menu-links">
            <a href="../../index.php">Panel</a>

            <!-- Submenu Operaciones -->
            <button class="submenu-btn">Operaciones ▾</button>
            <div class="submenu">
                <a href="../ventas.php">Ventas</a>
                <a href="../productos.php">Productos</a>
                <a href="../presupuestos.php">Presupuestos</a>
            </div>

            <!-- Submenu Gestión -->
            <button class="submenu-btn">Gestión ▾</button>
            <div class="submenu">
                <a href="../clientes.php">Clientes</a>
                <a href="../interacciones.php">Interacciones</a>
                <a href="../tareas.php">Tareas</a>
            </div>

            <a href="../usuarios.php">Usuarios</a>
            <a href="../empresas.php">Empresas</a>
            <a href="../proveedores.php" class="activo">Proveedores</a>
        </div>

        <div class="logout">
            <a href="../../public/logout.php">Cerrar Sesión</a>
        </div>
    </nav>
</aside>

<!-- Drawer de notificaciones FUERA del aside -->
<div id="modalNotificaciones" class="modal">
    <div class="modal-contenido">
        <h3>Notificaciones</h3>
        <ul id="listaNotificaciones">
            <li>Cargando...</li>
        </ul>
    </div>
</div>

<style>
.campana {
    position: relative;
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
}
.campana span {
    position: absolute;
    top: -8px;
    right: -8px;
    background: red;
    color: white;
    font-size: 12px;
    padding: 2px 6px;
    border-radius: 50%;
}
.modal {
    display: block;
    position: fixed;
    top: 50px;
    right: 0;
    width: 300px;
    max-height: 80vh;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #ccc;
    box-shadow: -2px 0 8px rgba(0,0,0,0.2);
    border-radius: 8px 0 0 8px;
    z-index: 1000;
    transform: translateX(100%);
    transition: transform 0.3s ease;
}
.modal.abierto {
    transform: translateX(0);
}


.modal-contenido {
    padding: 10px;
}
.modal-contenido ul {
    list-style: none;
    margin: 0;
    padding: 0;
}
.modal-contenido li {
    padding: 8px;
    border-bottom: 1px solid #eee;
}
.modal-contenido li.no-leida {
    font-weight: bold;
    background: #f9f9f9;
}
.submenu { display: none; margin-left: 10px; }
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
}
</style>
<main>
<h1>Editar Presupuesto #<?= $presupuesto['id'] ?></h1>

<form method="POST">
    <label>Cliente:</label>
    <select name="cliente_id" required>
        <?php while($c=$clientes->fetch_assoc()): ?>
        <option value="<?= $c['id'] ?>" <?= $c['id']==$presupuesto['cliente_id']?'selected':'' ?>><?= $c['nombre'] ?></option>
        <?php endwhile; ?>
    </select>

    <label>Usuario:</label>
    <select name="usuario_id" required>
        <?php while($u=$usuarios->fetch_assoc()): ?>
        <option value="<?= $u['id'] ?>" <?= $u['id']==$presupuesto['usuario_id']?'selected':'' ?>><?= $u['nombre'] ?></option>
        <?php endwhile; ?>
    </select>

    <h3>Items existentes</h3>
    <?php foreach($items as $item): ?>
    <div class="item-row">
        <input type="hidden" name="item_id[]" value="<?= $item['id'] ?>">
        <span><?= $item['producto_nombre'] ?></span>
        <input type="number" name="cantidad[]" value="<?= $item['cantidad'] ?>" min="1" required>
        <input type="number" step="0.01" name="precio[]" value="<?= $item['precio'] ?>" required>
        <label><input type="checkbox" name="eliminar_item[]" value="<?= $item['id'] ?>"> Eliminar</label>
    </div>
    <?php endforeach; ?>

    <h3>Agregar nuevos productos</h3>
    <div id="nuevos_items"></div>
    <button type="button" onclick="agregarItem()">+ Agregar producto</button>

    <br><br>
    <button type="submit" class="button">Guardar Cambios</button>
    <a href="ver_presupuesto.php?id=<?= $presupuesto['id'] ?>" class="button link">Volver</a>
</form>

<script>
const productos = <?= json_encode($productos->fetch_all(MYSQLI_ASSOC)) ?>;

function agregarItem(){
    const cont = document.getElementById('nuevos_items');
    const div = document.createElement('div');
    div.className='item-row';
    div.innerHTML = `
        <select name="nuevo_producto_id[]" required>
            <option value="">-- Producto --</option>
            ${productos.map(p=>`<option value="${p.id}" data-precio="${p.precio}">${p.nombre}</option>`).join('')}
        </select>
        <input type="number" name="nuevo_cantidad[]" value="1" min="1" required>
        <input type="number" step="0.01" name="nuevo_precio[]" value="0" required>
        <button type="button" onclick="this.parentElement.remove()">X</button>
    `;
    cont.appendChild(div);

    // Autocompletar precio al seleccionar producto
    const select = div.querySelector('select');
    const precioInput = div.querySelector('input[name="nuevo_precio[]"]');
    select.addEventListener('change', ()=> {
        const p = select.selectedOptions[0].dataset.precio || 0;
        precioInput.value = parseFloat(p);
    });
}
document.querySelectorAll('.submenu-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const submenu = btn.nextElementSibling;
        submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
    });
});
</script>

</main>
</body>
</html>
