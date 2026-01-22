<?php
/**
 * Script para comparar las conexiones de base de datos
 */

require_once 'db.php';

header('Content-Type: application/json');

try {
    // Obtener información de la conexión
    $stmt = $pdo->query("SELECT DATABASE() as db_name");
    $dbInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener la lista de tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Obtener columnas de la tabla cotizaciones
    $stmt = $pdo->query("SHOW COLUMNS FROM cotizaciones");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Intentar un SELECT simple
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM cotizaciones");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'database' => $dbInfo['db_name'],
        'tables_count' => count($tables),
        'tables' => $tables,
        'cotizaciones_columns' => array_column($columns, 'Field'),
        'cotizaciones_count' => $count['count'],
        'pdo_driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
        'server_info' => $pdo->getAttribute(PDO::ATTR_SERVER_INFO)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
