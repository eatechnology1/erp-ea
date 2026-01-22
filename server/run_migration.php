<?php
/**
 * Script de migración para crear tablas de cotizaciones
 * Ejecutar este archivo directamente desde el navegador o CLI
 */

// Incluir la configuración de la base de datos
require_once 'db.php';

// Leer el archivo SQL
$sqlFile = __DIR__ . '/schema_cotizaciones.sql';

if (!file_exists($sqlFile)) {
    die(json_encode([
        'success' => false,
        'error' => 'Archivo schema_cotizaciones.sql no encontrado'
    ]));
}

$sql = file_get_contents($sqlFile);

// Remover comentarios y líneas vacías para mejor procesamiento
$sql = preg_replace('/--.*$/m', '', $sql);
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

// Separar las consultas por punto y coma
$queries = array_filter(
    array_map('trim', explode(';', $sql)),
    function($query) {
        return !empty($query);
    }
);

$results = [];
$errors = [];
$success = true;

try {
    // Ejecutar cada consulta
    foreach ($queries as $index => $query) {
        if (empty($query)) continue;
        
        try {
            $pdo->exec($query);
            $results[] = "✓ Consulta " . ($index + 1) . " ejecutada correctamente";
        } catch (PDOException $e) {
            $errorMsg = "✗ Error en consulta " . ($index + 1) . ": " . $e->getMessage();
            $errors[] = $errorMsg;
            
            // Si es un error de "tabla ya existe", no es crítico
            if (strpos($e->getMessage(), 'already exists') === false) {
                $success = false;
            }
        }
    }
    
    // Verificar que las tablas se crearon
    $stmt = $pdo->query("SHOW TABLES LIKE 'cotizacion%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $response = [
        'success' => $success && count($tables) >= 2,
        'message' => $success ? 'Migración ejecutada exitosamente' : 'Migración completada con errores',
        'queries_executed' => count($results),
        'tables_created' => $tables,
        'details' => $results,
        'errors' => $errors
    ];
    
    // Si se ejecuta desde el navegador, mostrar HTML bonito
    if (php_sapi_name() !== 'cli') {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Migración de Base de Datos - Cotizaciones</title>
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
                .success-icon { color: #28a745; }
                .error-icon { color: #dc3545; }
                .table-badge {
                    display: inline-block;
                    background: #667eea;
                    color: white;
                    padding: 4px 12px;
                    border-radius: 12px;
                    margin: 4px;
                    font-size: 14px;
                }
                .stats {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                    gap: 15px;
                    margin-top: 20px;
                }
                .stat-card {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 20px;
                    border-radius: 8px;
                    text-align: center;
                }
                .stat-number {
                    font-size: 32px;
                    font-weight: bold;
                }
                .stat-label {
                    font-size: 14px;
                    opacity: 0.9;
                    margin-top: 5px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>🗄️ Migración de Base de Datos</h1>
                <div class="status <?php echo $response['success'] ? 'success' : 'error'; ?>">
                    <?php echo $response['success'] ? '✓ EXITOSA' : '✗ CON ERRORES'; ?>
                </div>

                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $response['queries_executed']; ?></div>
                        <div class="stat-label">Consultas Ejecutadas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($response['tables_created']); ?></div>
                        <div class="stat-label">Tablas Creadas</div>
                    </div>
                </div>

                <?php if (!empty($response['tables_created'])): ?>
                <div class="section">
                    <h2>📋 Tablas Creadas</h2>
                    <div class="list">
                        <?php foreach ($response['tables_created'] as $table): ?>
                            <span class="table-badge"><?php echo htmlspecialchars($table); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($response['details'])): ?>
                <div class="section">
                    <h2>📝 Detalles de Ejecución</h2>
                    <div class="list">
                        <?php foreach ($response['details'] as $detail): ?>
                            <div class="list-item">
                                <span class="success-icon">✓</span> <?php echo htmlspecialchars($detail); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($response['errors'])): ?>
                <div class="section">
                    <h2>⚠️ Errores Encontrados</h2>
                    <div class="list">
                        <?php foreach ($response['errors'] as $error): ?>
                            <div class="list-item">
                                <span class="error-icon">✗</span> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="section" style="margin-top: 30px; text-align: center;">
                    <p style="color: #666;">
                        <?php if ($response['success']): ?>
                            ✅ La base de datos está lista. Puedes cerrar esta ventana y probar el módulo de cotizaciones.
                        <?php else: ?>
                            ⚠️ Revisa los errores anteriores. Es posible que las tablas ya existieran.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    } else {
        // CLI output
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    
    if (php_sapi_name() !== 'cli') {
        echo "<!DOCTYPE html><html><body style='font-family: sans-serif; padding: 40px;'>";
        echo "<h1 style='color: #dc3545;'>❌ Error en la Migración</h1>";
        echo "<p style='background: #f8d7da; padding: 20px; border-radius: 8px; color: #721c24;'>";
        echo htmlspecialchars($e->getMessage());
        echo "</p></body></html>";
    } else {
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
}
?>
