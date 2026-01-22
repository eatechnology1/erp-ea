<?php
/**
 * Script para probar directamente la creación de una cotización
 * Este script simula exactamente lo que hace el frontend
 */

require_once 'db.php';

header('Content-Type: application/json');

try {
    // Datos de prueba
    $cliente_id = 1;
    $subtotal = 60000.00;
    $iva = 11400.00;
    $total = 71400.00;
    
    // Primero, verificar que las columnas existen
    $stmt = $pdo->query("SHOW COLUMNS FROM cotizaciones WHERE Field IN ('subtotal', 'iva', 'total', 'tipo_impuesto', 'aiu_administracion', 'aiu_imprevistos', 'aiu_utilidad')");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($columns) < 7) {
        throw new Exception("Faltan columnas en la tabla. Columnas encontradas: " . implode(', ', $columns));
    }
    
    // Intentar el INSERT exactamente como lo hace cotizaciones.php
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("
        INSERT INTO cotizaciones (
            cliente_id, fecha, subtotal, iva, total, 
            tipo_impuesto, aiu_administracion, aiu_imprevistos, aiu_utilidad,
            estado
        ) 
        VALUES (
            :cliente_id, NOW(), :subtotal, :iva, :total,
            :tipo_impuesto, :aiu_admin, :aiu_imp, :aiu_util,
            'borrador'
        )
    ");
    
    $result = $stmt->execute([
        ':cliente_id' => $cliente_id,
        ':subtotal' => $subtotal,
        ':iva' => $iva,
        ':total' => $total,
        ':tipo_impuesto' => 'IVA',
        ':aiu_admin' => 10.00,
        ':aiu_imp' => 5.00,
        ':aiu_util' => 5.00
    ]);
    
    $cotizacionId = $pdo->lastInsertId();
    
    // Rollback para no dejar datos de prueba
    $pdo->rollBack();
    
    echo json_encode([
        'success' => true,
        'message' => 'INSERT de prueba exitoso (rollback aplicado)',
        'cotizacion_id' => $cotizacionId,
        'columns_found' => $columns,
        'data_used' => [
            'cliente_id' => $cliente_id,
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => $total
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'sql_state' => $e->errorInfo[0] ?? null
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
