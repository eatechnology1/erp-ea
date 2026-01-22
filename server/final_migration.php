<?php
/**
 * Script DEFINITIVO para agregar columnas faltantes
 * Este script verifica explícitamente si cada columna existe antes de agregarla
 */

require_once 'db.php';

header('Content-Type: text/html; charset=UTF-8');

$results = [];
$success = true;

try {
    // Obtener las columnas actuales de la tabla
    $stmt = $pdo->query("SHOW COLUMNS FROM cotizaciones");
    $existingColumns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $row['Field'];
    }
    
    // Definir las columnas que necesitamos agregar
    $columnsToAdd = [
        'tipo_impuesto' => "ALTER TABLE cotizaciones ADD COLUMN tipo_impuesto VARCHAR(50) DEFAULT 'IVA' AFTER estado",
        'aiu_administracion' => "ALTER TABLE cotizaciones ADD COLUMN aiu_administracion DECIMAL(5, 2) DEFAULT 10.00 AFTER tipo_impuesto",
        'aiu_imprevistos' => "ALTER TABLE cotizaciones ADD COLUMN aiu_imprevistos DECIMAL(5, 2) DEFAULT 5.00 AFTER aiu_administracion",
        'aiu_utilidad' => "ALTER TABLE cotizaciones ADD COLUMN aiu_utilidad DECIMAL(5, 2) DEFAULT 5.00 AFTER aiu_imprevistos",
        'subtotal' => "ALTER TABLE cotizaciones ADD COLUMN subtotal DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER aiu_utilidad",
        'iva' => "ALTER TABLE cotizaciones ADD COLUMN iva DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER subtotal",
        'total' => "ALTER TABLE cotizaciones ADD COLUMN total DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER iva"
    ];
    
    // Agregar cada columna si no existe
    foreach ($columnsToAdd as $columnName => $query) {
        if (!in_array($columnName, $existingColumns)) {
            try {
                $pdo->exec($query);
                $results[] = [
                    'column' => $columnName,
                    'status' => 'success',
                    'message' => 'Columna agregada exitosamente'
                ];
            } catch (PDOException $e) {
                $results[] = [
                    'column' => $columnName,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
                $success = false;
            }
        } else {
            $results[] = [
                'column' => $columnName,
                'status' => 'skipped',
                'message' => 'La columna ya existe'
            ];
        }
    }
    
    // Verificar estructura final
    $stmt = $pdo->query("SHOW COLUMNS FROM cotizaciones");
    $finalColumns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $finalColumns[] = [
            'Field' => $row['Field'],
            'Type' => $row['Type'],
            'Null' => $row['Null'],
            'Default' => $row['Default']
        ];
    }
    
} catch (Exception $e) {
    $success = false;
    $results[] = [
        'column' => 'GENERAL',
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migración DEFINITIVA - Cotizaciones</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .status-badge {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            margin-bottom: 30px;
            font-size: 18px;
        }
        .status-badge.success {
            background: #d4edda;
            color: #155724;
        }
        .status-badge.error {
            background: #f8d7da;
            color: #721c24;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #555;
            font-size: 20px;
            margin-bottom: 15px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .result-item {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            border-left: 4px solid #ccc;
        }
        .result-item.success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .result-item.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .result-item.skipped {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        .result-item strong {
            display: block;
            margin-bottom: 5px;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px;
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
        .highlight {
            background: #fff3cd;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Migración DEFINITIVA - Cotizaciones</h1>
        <div class="status-badge <?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $success ? '✓ COMPLETADO' : '✗ CON ERRORES'; ?>
        </div>

        <div class="section">
            <h2>📝 Resultados de la Migración</h2>
            <?php foreach ($results as $result): ?>
                <div class="result-item <?php echo $result['status']; ?>">
                    <strong><?php echo htmlspecialchars($result['column']); ?></strong>
                    <span><?php echo htmlspecialchars($result['message']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="section">
            <h2>📋 Estructura Final de la Tabla</h2>
            <table>
                <thead>
                    <tr>
                        <th>Campo</th>
                        <th>Tipo</th>
                        <th>Null</th>
                        <th>Default</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($finalColumns as $column): ?>
                        <?php 
                        $isNew = in_array($column['Field'], ['tipo_impuesto', 'aiu_administracion', 'aiu_imprevistos', 'aiu_utilidad', 'subtotal', 'iva', 'total']);
                        ?>
                        <tr class="<?php echo $isNew ? 'highlight' : ''; ?>">
                            <td><strong><?php echo htmlspecialchars($column['Field']); ?></strong></td>
                            <td><?php echo htmlspecialchars($column['Type']); ?></td>
                            <td><?php echo htmlspecialchars($column['Null']); ?></td>
                            <td><?php echo htmlspecialchars($column['Default'] ?? 'NULL'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section" style="text-align: center; margin-top: 40px;">
            <p style="color: #666; font-size: 18px;">
                <?php if ($success): ?>
                    ✅ <strong>Migración completada.</strong> Ahora puedes crear cotizaciones.
                <?php else: ?>
                    ⚠️ <strong>Revisa los errores anteriores.</strong>
                <?php endif; ?>
            </p>
        </div>
    </div>
</body>
</html>
