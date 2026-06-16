<?php
// ===============================
// CONFIG
// ===============================
$host     = 'localhost';
$username = 'gestion';
$password = '';
$dbname   = 'gestion_empresa';

// ===============================
// CONEXIÓN
// ===============================
$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// FIX: fuerza UTF-8 (acentos, ñ, etc.) en la conexión
$conn->set_charset("utf8mb4");

// ===============================
// CREAR BD
// ===============================
if (!$conn->query("
    CREATE DATABASE IF NOT EXISTS $dbname
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci
")) {
    die("Error creando la base de datos: " . $conn->error);
}

if (!$conn->select_db($dbname)) {
    die("Error seleccionando la base de datos: " . $conn->error);
}

// ===============================
// TABLAS
// ===============================
$queries = [
    "CREATE TABLE IF NOT EXISTS clientes (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nombre VARCHAR(100),
      telefono VARCHAR(30),
      email VARCHAR(100)
    )",
    "CREATE TABLE IF NOT EXISTS empleados (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nombre VARCHAR(100),
      cargo VARCHAR(50),
      salario DECIMAL(10,2)
    )",
    "CREATE TABLE IF NOT EXISTS proveedores (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nombre VARCHAR(100),
      telefono VARCHAR(30),
      email VARCHAR(100)
    )",
    "CREATE TABLE IF NOT EXISTS productos (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nombre VARCHAR(100),
      precio DECIMAL(10,2),
      stock INT
    )",
    "CREATE TABLE IF NOT EXISTS facturas (
      id INT AUTO_INCREMENT PRIMARY KEY,
      cliente_id INT,
      empleado_id INT,
      fecha DATE,
      total DECIMAL(10,2)
    )",
    "CREATE TABLE IF NOT EXISTS detalle_factura (
      id INT AUTO_INCREMENT PRIMARY KEY,
      factura_id INT,
      producto_id INT,
      cantidad INT,
      subtotal DECIMAL(10,2)
    )",
    "CREATE TABLE IF NOT EXISTS ventas (
      id INT AUTO_INCREMENT PRIMARY KEY,
      cliente_id INT,
      total DECIMAL(10,2),
      fecha DATE
    )",
    "CREATE TABLE IF NOT EXISTS gastos (
      id INT AUTO_INCREMENT PRIMARY KEY,
      concepto VARCHAR(100),
      monto DECIMAL(10,2),
      fecha DATE
    )"
];

// ===============================
// EJECUTAR TABLAS
// ===============================
// FIX: si una tabla falla al crearse, lo informa en vez de seguir en silencio
foreach ($queries as $q) {
    if (!$conn->query($q)) {
        die("Error creando tabla: " . $conn->error);
    }
}

// ===============================
// FUNCIÓN AUXILIAR PARA DATOS DE PRUEBA
// ===============================
// FIX: inserta datos de ejemplo usando prepared statements en vez de
// concatenar valores directamente en el SQL.
//
// $tabla     -> nombre de la tabla
// $columnas  -> array con los nombres de columna (sin "id")
// $tipos     -> string de tipos para bind_param (ej: "sss", "ids")
// $filas     -> array de arrays con los valores de cada fila
function seedIfEmpty($conn, $tabla, $columnas, $tipos, $filas) {

    $res = $conn->query("SELECT COUNT(*) as c FROM $tabla");
    if (!$res) return;

    $row = $res->fetch_assoc();
    if (!$row || $row['c'] > 0) return;

    $placeholders = implode(',', array_fill(0, count($columnas), '?'));
    $sql = "INSERT INTO $tabla (" . implode(',', $columnas) . ") VALUES ($placeholders)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) return;

    foreach ($filas as $fila) {
        $stmt->bind_param($tipos, ...$fila);
        $stmt->execute();
    }

    $stmt->close();
}

// ===============================
// INSERTS SOLO SI ESTÁ VACÍO
// ===============================

// CLIENTES
seedIfEmpty($conn, 'clientes', ['nombre', 'telefono', 'email'], 'sss', [
    ['Juan Perez',    '2211234567', 'juan@gmail.com'],
    ['Maria Gomez',   '2217654321', 'maria@gmail.com'],
    ['Carlos Lopez',  '2211111113', 'carlos.lopez@gmail.com'],
    ['Ana Martinez',  '2211111114', 'ana.martinez@gmail.com'],
]);

// GASTOS
seedIfEmpty($conn, 'gastos', ['concepto', 'monto', 'fecha'], 'sds', [
    ['Internet',      25000, '2026-06-01'],
    ['Electricidad',  18000, '2026-06-02'],
    ['Renta oficina', 50000, '2026-06-01'],
    ['Servicios',     12000, '2026-06-02'],
]);

// EMPLEADOS
seedIfEmpty($conn, 'empleados', ['nombre', 'cargo', 'salario'], 'ssd', [
    ['Juan Perez',   'Gerente',   50000],
    ['Maria Garcia', 'Vendedora', 30000],
    ['Carlos Lopez', 'Operario',  25000],
    ['Ana Martinez', 'Asistente', 20000],
]);

// PROVEEDORES
seedIfEmpty($conn, 'proveedores', ['nombre', 'telefono', 'email'], 'sss', [
    ['Distribuidor ABC', '2211111111', 'abc@supplier.com'],
    ['Supplier XYZ',     '2222222222', 'xyz@supplier.com'],
    ['Global Import',    '2233333333', 'global@supplier.com'],
]);

// PRODUCTOS
seedIfEmpty($conn, 'productos', ['nombre', 'precio', 'stock'], 'sdi', [
    ['Laptop Dell',      1200, 15],
    ['Mouse Logitech',     25, 50],
    ['Teclado Mecanico',   85, 30],
    ['Monitor LG 24',     300, 12],
    ['Cable HDMI',         10, 100],
    ['Webcam HD',          45, 25],
]);

// VENTAS
seedIfEmpty($conn, 'ventas', ['cliente_id', 'total', 'fecha'], 'ids', [
    [1, 2500, '2026-06-01'],
    [2,  150, '2026-06-02'],
    [3, 3200, '2026-06-02'],
    [1,  850, '2026-06-03'],
    [4, 2800, '2026-06-04'],
    [2,  420, '2026-06-04'],
    [3, 1200, '2026-06-05'],
]);
?>
