<?php
/**
 * Script para verificar el contenido del archivo cotizaciones.php en producción
 */

// Mostrar el contenido del archivo cotizaciones.php
$file = __DIR__ . '/cotizaciones.php';

if (!file_exists($file)) {
    echo json_encode([
        'success' => false,
        'error' => 'Archivo cotizaciones.php no encontrado'
    ]);
    exit;
}

// Obtener información del archivo
$fileInfo = [
    'exists' => file_exists($file),
    'size' => filesize($file),
    'modified' => date('Y-m-d H:i:s', filemtime($file)),
    'readable' => is_readable($file)
];

// Leer las líneas 269-295 del archivo (donde está el INSERT)
$lines = file($file);
$insertSection = array_slice($lines, 268, 27); // líneas 269-295 (0-indexed)

echo json_encode([
    'success' => true,
    'file_info' => $fileInfo,
    'insert_query_section' => implode('', $insertSection),
    'line_numbers' => '269-295'
], JSON_PRETTY_PRINT);
?>
