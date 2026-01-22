<?php
/**
 * Script de diagnóstico para verificar la estructura de la tabla cotizaciones
 */

require_once 'db.php';

header('Content-Type: application/json');

try {
    // Obtener estructura de la tabla
    $stmt = $pdo->query("DESCRIBE cotizaciones");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Intentar un INSERT de prueba
    $testData = [
        'cliente_id' => 1,
        'subtotal' => 100.00,
        'iva' => 19.00,
        'total' => 119.00,
        'tipo_impuesto' => 'IVA',
        'aiu_admin' => 10.00,
        'aiu_imp' => 5.00,
        'aiu_util' => 5.00
    ];
    
    $insertQuery = "
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
    ";
    
    $testInsert = null;
    try {
        $stmt = $pdo->prepare($insertQuery);
        $stmt->execute($testData);
        $testInsert = [
            'success' => true,
            'id' => $pdo->lastInsertId(),
            'message' => 'INSERT de prueba exitoso'
        ];
        
        // Eliminar el registro de prueba
        $pdo->exec("DELETE FROM cotizaciones WHERE id = " . $pdo->lastInsertId());
    } catch (PDOException $e) {
        $testInsert = [
            'success' => false,
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ];
    }
    
    echo json_encode([
        'success' => true,
        'table_structure' => $columns,
        'column_names' => array_column($columns, 'Field'),
        'test_insert' => $testInsert,
        'query_used' => $insertQuery
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
