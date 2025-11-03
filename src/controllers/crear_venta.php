<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../public/login.php");
    exit;
}
include "../../conexion.php"; // Ajusta según la ubicación real

$empresa_id = $_SESSION['empresa_id'];
$is_global = $_SESSION['is_global_admin'] ?? false;

// --- Clientes y usuarios para selects ---
$clientes_query = "SELECT id, nombre FROM clientes " . (!$is_global ? "WHERE empresa_id=$empresa_id" : "") . " ORDER BY nombre";
$clientes = $conexion->query($clientes_query);

$usuarios_query = "SELECT id, nombre FROM usuarios " . (!$is_global ? "WHERE empresa_id=$empresa_id" : "") . " ORDER BY nombre";
$usuarios = $conexion->query($usuarios_query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ventas POS</title>
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.pos-container { display: flex; gap: 20px; }
.form-pos, .productos, .carrito { border: 1px solid #ccc; padding: 15px; border-radius: 8px; }
.form-pos { margin-bottom: 20px; }
.productos, .carrito { flex: 1; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
button { padding: 6px 12px; cursor: pointer; }
input, select { width: 100%; padding: 6px; margin-bottom: 10px; }
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
</head>
<body>

<h2>Registrar Venta</h2>
<a href="../ventas.php">Volver</a>
<!-- Formulario superior -->
<div class="form-pos">
    <label>Cliente:</label>
    <select id="cliente_id">
        <?php while($c = $clientes->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>"><?= $c['nombre'] ?></option>
        <?php endwhile; ?>
    </select>

    <label>Vendedor:</label>
    <select id="usuario_id">
        <?php while($u = $usuarios->fetch_assoc()): ?>
            <option value="<?= $u['id'] ?>"><?= $u['nombre'] ?></option>
        <?php endwhile; ?>
    </select>

    <?php if($is_global): ?>
        <label>Empresa:</label>
        <select id="empresa_id">
            <?php
            $res_emp = $conexion->query("SELECT id, nombre FROM empresas ORDER BY nombre");
            while($e = $res_emp->fetch_assoc()): ?>
                <option value="<?= $e['id'] ?>"><?= $e['nombre'] ?></option>
            <?php endwhile; ?>
        </select>
    <?php endif; ?>
</div>

<div class="pos-container">
    <!-- Listado de productos tipo POS -->
    <div class="productos">
        <h3>Buscar Producto</h3>
        <input type="text" id="buscar" placeholder="Nombre del producto...">
        <table id="tablaProductos">
            <thead>
                <tr><th>Nombre</th><th>Precio</th><th>Stock</th><th>Acción</th></tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <!-- Carrito tipo POS -->
    <div class="carrito">
        <h3>Carrito</h3>
        <table id="tablaCarrito">
            <thead>
                <tr><th>Producto</th><th>Cant</th><th>Precio</th><th>Subtotal</th><th>X</th></tr>
            </thead>
            <tbody></tbody>
        </table>
        <h4>Total: $<span id="total">0.00</span></h4>
        <label>Método de pago:
            <select id="metodoPago">
                <option>Efectivo</option>
                <option>Tarjeta</option>
                <option>Transferencia</option>
            </select>
        </label>
        <br><br>
        <button id="confirmar">Confirmar Venta</button>
    </div>
</div>

<script>
let carrito = [];

// Ajustar la ruta según ubicación real de pos_buscar.php
const rutaBuscar = "pos_buscar.php";

document.getElementById("buscar").addEventListener("keyup", function() {
    let q = this.value;
    fetch(rutaBuscar + "?q=" + encodeURIComponent(q))
        .then(res => res.json())
        .then(data => {
            let tbody = document.querySelector("#tablaProductos tbody");
            tbody.innerHTML = "";
            data.forEach(p => {
                let tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${p.nombre}</td>
                    <td>$${p.precio}</td>
                    <td>${p.stock}</td>
                    <td><button onclick="agregar(${p.id}, '${p.nombre}', ${p.precio})">➕</button></td>
                `;
                tbody.appendChild(tr);
            });
        });
});

function agregar(id, nombre, precio) {
    let item = carrito.find(p => p.id === id);
    if(item){ item.cantidad++; } else { carrito.push({id, nombre, precio, cantidad:1}); }
    renderCarrito();
}

function renderCarrito() {
    let tbody = document.querySelector("#tablaCarrito tbody");
    tbody.innerHTML = "";
    let total = 0;
    carrito.forEach((p,i) => {
        let subtotal = p.precio * p.cantidad;
        total += subtotal;
        let tr = document.createElement("tr");
        tr.innerHTML = `
            <td>${p.nombre}</td>
            <td>${p.cantidad}</td>
            <td>$${p.precio}</td>
            <td>$${subtotal.toFixed(2)}</td>
            <td><button onclick="eliminar(${i})">❌</button></td>
        `;
        tbody.appendChild(tr);
    });
    document.getElementById("total").textContent = total.toFixed(2);
}

function eliminar(i) {
    carrito.splice(i,1);
    renderCarrito();
}

document.getElementById("confirmar").addEventListener("click", () => {
    if(carrito.length===0) return alert("Carrito vacío");

    let cliente_id = document.getElementById("cliente_id").value;
    let usuario_id = document.getElementById("usuario_id").value;
    let metodo = document.getElementById("metodoPago").value;
    let empresa_id = document.getElementById("empresa_id") ? document.getElementById("empresa_id").value : <?= $empresa_id ?>;

    fetch("pos_guardar.php", {
        method:"POST",
        headers:{"Content-Type":"application/json"},
        body: JSON.stringify({carrito, cliente_id, usuario_id, metodo, empresa_id})
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.ok){
            alert("Venta registrada con éxito");
            carrito = [];
            renderCarrito();

            // Abrir ticket
            window.open(`ticket_venta.php?id=${data.venta_id}`, "_blank", "width=400,height=600");
        } else { 
            alert("Error: "+data.error); 
        }
    });
});

document.querySelectorAll('.submenu-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const submenu = btn.nextElementSibling;
        submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
    });
});
</script>

</body>
</html>
