<?php
// Incluir la biblioteca SimpleXLSXGen
require_once 'SimpleXLSXGen.php';

// Configurar headers para descarga de Excel
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=plantilla_inventario.xlsx");
header("Pragma: no-cache");
header("Expires: 0");

// Crear datos para el Excel
$data = [
    // Encabezados (se mostrarán en negrita automáticamente)
    ['<style bgcolor="#4472C4" color="#FFFFFF"><b>Código</b></style>', 
     '<style bgcolor="#4472C4" color="#FFFFFF"><b>Nombre</b></style>', 
     '<style bgcolor="#4472C4" color="#FFFFFF"><b>Descripción</b></style>', 
     '<style bgcolor="#4472C4" color="#FFFFFF"><b>Precio Base</b></style>', 
     '<style bgcolor="#4472C4" color="#FFFFFF"><b>Tipo</b></style>'],
    
    // Filas de ejemplo
    ['PROD001', 'Producto Ejemplo', 'Descripción del producto de ejemplo', 10000, 'producto'],
    ['SERV001', 'Servicio Ejemplo', 'Descripción del servicio de ejemplo', 50000, 'servicio'],
    ['PROD002', 'Otro Producto', 'Puede dejar la descripción vacía si lo desea', 25000, 'producto'],
];

// Generar el archivo Excel
$xlsx = Shuchkin\SimpleXLSXGen::fromArray($data);

// Configurar anchos de columna
$xlsx->setColWidth(1, 15);  // Código
$xlsx->setColWidth(2, 30);  // Nombre
$xlsx->setColWidth(3, 40);  // Descripción
$xlsx->setColWidth(4, 15);  // Precio Base
$xlsx->setColWidth(5, 12);  // Tipo

// Descargar el archivo
$xlsx->downloadAs('plantilla_inventario.xlsx');
exit();
?>
