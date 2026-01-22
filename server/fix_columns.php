<?php
/**
 * Script para agregar columnas faltantes a la tabla cotizaciones
 */

require_once 'db.php';

$queries = [
    "ALTER TABLE cotizaciones ADD COLUMN IF NOT EXISTS tipo_impuesto VARCHAR(50) DEFAULT 'IVA' AFTER estado",
    "ALTER TABLE cotizaciones ADD COLUMN IF NOT EXISTS aiu_administracion DECIMAL(5, 2) DEFAULT 10.00 AFTER tipo_impuesto",
    "ALTER TABLE cotizaciones ADD COLUMN IF NOT EXISTS aiu_imprevistos DECIMAL(5, 2) DEFAULT 5.00 AFTER aiu_administracion",
    "ALTER TABLE cotizaciones ADD COLUMN IF NOT EXISTS aiu_utilidad DECIMAL(5, 2) DEFAULT 5.00 AFTER aiu_imprevistos"
];

$results = [];
$success = true;

try {
    foreach ($queries as $index => $query) {
        try {
            $pdo->exec($query);
            $results[] = "✓ Columna " . ($index + 1) . " agregada correctamente";
        } catch (PDOException $e) {
            // Si la columna ya existe, no es un error crítico
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                $results[] = "⚠ Columna " . ($index + 1) . " ya existe (OK)";
            } else {
                $results[] = "✗ Error en columna " . ($index + 1) . ": " . $e->getMessage();
                $success = false;
            }
        }
    }
    
    // Verificar estructura final
    $stmt = $pdo->query("DESCRIBE cotizaciones");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $response = [
        'success' => $success,
        'message' => $success ? 'Columnas agregadas exitosamente' : 'Completado con errores',
        'details' => $results,
        'columns' => $columns
    ];
    
    // HTML output
    if (php_sapi_name() !== 'cli') {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Fix Cotizaciones Columns</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .container {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    max-width: 800px;
                    width: 100%;
                    padding: 40px;
                }
                h1 {
                    color: #333;
                    margin-bottom: 10px;
                    font-size: 28px;
                }
                .status {
                    display: inline-block;
                    padding: 8px 16px;
                    border-radius: 20px;
                    font-weight: 600;
                    margin-bottom: 30px;
                }
                .status.success {
                    background: #d4edda;
                    color: #155724;
                }
                .status.error {
                    background: #f8d7da;
                    color: #721c24;
                }
                .section {
                    margin-bottom: 25px;
                }
                .section h2 {
                    color: #555;
                    font-size: 18px;
                    margin-bottom: 12px;
                    border-bottom: 2px solid #667eea;
                    padding-bottom: 8px;
                }
                .list {
                    background: #f8f9fa;
                    border-radius: 8px;
                    padding: 15px;
                }
                .list-item {
                    padding: 8px 0;
                    border-bottom: 1px solid #e0e0e0;
                }
                .list-item:last-child {
                    border-bottom: none;
                }
                .column-badge {
                    display: inline-block;
                    background: #667eea;
                    color: white;
                    padding: 4px 12px;
                    border-radius: 12px;
                    margin: 4px;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>🔧 Fix Cotizaciones Columns</h1>
                <div class="status <?php echo $response['success'] ? 'success' : 'error'; ?>">
                    <?php echo $response['success'] ? '✓ EXITOSO' : '✗ CON ERRORES'; ?>
                </div>

                <div class="section">
                    <h2>📝 Detalles de Ejecución</h2>
                    <div class="list">
                        <?php foreach ($response['details'] as $detail): ?>
                            <div class="list-item"><?php echo htmlspecialchars($detail); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="section">
                    <h2>📋 Columnas en la Tabla</h2>
                    <div class="list">
                        <?php foreach ($response['columns'] as $column): ?>
                            <span class="column-badge"><?php echo htmlspecialchars($column); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="section" style="margin-top: 30px; text-align: center;">
                    <p style="color: #666;">
                        <?php if ($response['success']): ?>
                            ✅ Las columnas han sido agregadas. Recarga la página de cotizaciones.
                        <?php else: ?>
                            ⚠️ Revisa los errores anteriores.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    } else {
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    
    if (php_sapi_name() !== 'cli') {
        echo "<!DOCTYPE html><html><body style='font-family: sans-serif; padding: 40px;'>";
        echo "<h1 style='color: #dc3545;'>❌ Error</h1>";
        echo "<p style='background: #f8d7da; padding: 20px; border-radius: 8px; color: #721c24;'>";
        echo htmlspecialchars($e->getMessage());
        echo "</p></body></html>";
    } else {
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
}
?>
