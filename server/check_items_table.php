<?php
/**
 * Script para verificar la estructura de cotizacion_items
 */

require_once 'db.php';

header('Content-Type: application/json');

try {
    // Verificar estructura de cotizacion_items
    $stmt = $pdo->query("SHOW COLUMNS FROM cotizacion_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'table' => 'cotizacion_items',
        'columns' => $columns,
        'column_names' => array_column($columns, 'Field')
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
