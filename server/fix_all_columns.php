<?php
/**
 * Script para agregar TODAS las columnas faltantes a la tabla cotizaciones
 */

require_once 'db.php';

$queries = [
    // Agregar columnas de totales
    "ALTER TABLE cotizaciones ADD COLUMN IF NOT EXISTS subtotal DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER aiu_utilidad",
    "ALTER TABLE cotizaciones ADD COLUMN IF NOT EXISTS iva DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER subtotal",
    "ALTER TABLE cotizaciones ADD COLUMN IF NOT EXISTS total DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER iva",
    
    // Agregar columnas de timestamps si no existen
    "ALTER TABLE cotizaciones ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER total",
    "ALTER TABLE cotizaciones ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at"
];

$results = [];
$success = true;

try {
    foreach ($queries as $index => $query) {
        try {
            $pdo->exec($query);
            $results[] = "✓ Consulta " . ($index + 1) . " ejecutada correctamente";
        } catch (PDOException $e) {
            // Si la columna ya existe, no es un error crítico
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                $results[] = "⚠ Consulta " . ($index + 1) . " - columna ya existe (OK)";
            } else {
                $results[] = "✗ Error en consulta " . ($index + 1) . ": " . $e->getMessage();
                $success = false;
            }
        }
    }
    
    // Verificar estructura final
    $stmt = $pdo->query("DESCRIBE cotizaciones");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [
        'success' => $success,
        'message' => $success ? 'Todas las columnas agregadas exitosamente' : 'Completado con errores',
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
            <title>Fix ALL Cotizaciones Columns</title>
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
                    max-width: 900px;
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
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }
                th, td {
                    padding: 10px;
                    text-align: left;
                    border-bottom: 1px solid #e0e0e0;
                }
                th {
                    background: #667eea;
                    color: white;
                    font-weight: 600;
                }
                tr:hover {
                    background: #f8f9fa;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>🔧 Fix ALL Cotizaciones Columns</h1>
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
                    <h2>📋 Estructura Completa de la Tabla</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Campo</th>
                                <th>Tipo</th>
                                <th>Null</th>
                                <th>Key</th>
                                <th>Default</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($response['columns'] as $column): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($column['Field']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($column['Type']); ?></td>
                                    <td><?php echo htmlspecialchars($column['Null']); ?></td>
                                    <td><?php echo htmlspecialchars($column['Key']); ?></td>
                                    <td><?php echo htmlspecialchars($column['Default'] ?? 'NULL'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="section" style="margin-top: 30px; text-align: center;">
                    <p style="color: #666;">
                        <?php if ($response['success']): ?>
                            ✅ Todas las columnas han sido agregadas. Ahora puedes crear cotizaciones.
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
