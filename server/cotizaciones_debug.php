<?php
// Habilitar reporte de errores detallado
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

// Incluir la configuración de la base de datos
require_once 'db.php';

// Configurar headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Content-Type: application/json; charset=UTF-8");

// Responder a las solicitudes de "preflight"
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Obtener el método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Log para debugging
file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " - Method: $method\n", FILE_APPEND);

try {
    if ($method === 'POST') {
        // Obtener los datos JSON del cuerpo de la solicitud
        $input = file_get_contents('php://input');
        file_put_contents(__DIR__ . '/debug_log.txt', "Input: $input\n", FILE_APPEND);
        
        $data = json_decode($input, true);
        
        if (!$data) {
            throw new Exception("No se recibieron datos válidos o el JSON está malformado");
        }
        
        if (!isset($data['cliente_id'])) {
            throw new Exception("El campo cliente_id es obligatorio");
        }
        
        if (!is_numeric($data['cliente_id'])) {
            throw new Exception("El cliente_id debe ser un valor numérico");
        }
        
        $cliente_id = intval($data['cliente_id']);
        
        if (!isset($data['items']) || !is_array($data['items'])) {
            throw new Exception("El campo items debe ser un array");
        }
        
        if (empty($data['items'])) {
            throw new Exception("Debe incluir al menos un ítem en la cotización");
        }
        
        // Calcular subtotal
        $subtotal = 0;
        foreach ($data['items'] as $item) {
            $cantidad = intval($item['cantidad']);
            $precio = floatval($item['precio']);
            $subtotal += $cantidad * $precio;
        }
        
        // Calcular IVA
        $iva = $subtotal * 0.19;
        $total = $subtotal + $iva;
        
        file_put_contents(__DIR__ . '/debug_log.txt', "Subtotal: $subtotal, IVA: $iva, Total: $total\n", FILE_APPEND);
        
        // Iniciar transacción
        $pdo->beginTransaction();
        
        try {
            // Insertar la cotización
            $stmt = $pdo->prepare("
                INSERT INTO cotizaciones (
                    cliente_id, fecha, subtotal, iva, total, 
                    tipo_impuesto, aiu_administracion, aiu_imprevistos, aiu_utilidad,
                    estado
                ) 
                VALUES (
                    :cliente_id, NOW(), :subtotal, :iva, :total,
                    'IVA', 10.00, 5.00, 5.00,
                    'borrador'
                )
            ");
            
            $stmt->execute([
                ':cliente_id' => $cliente_id,
                ':subtotal' => $subtotal,
                ':iva' => $iva,
                ':total' => $total
            ]);
            
            $cotizacionId = $pdo->lastInsertId();
            file_put_contents(__DIR__ . '/debug_log.txt', "Cotización ID: $cotizacionId\n", FILE_APPEND);
            
            // Insertar ítems
            $stmtItem = $pdo->prepare("
                INSERT INTO cotizacion_items 
                (cotizacion_id, inventario_id, cantidad, precio_unitario, subtotal) 
                VALUES (:cotizacion_id, :inventario_id, :cantidad, :precio_unitario, :subtotal)
            ");
            
            foreach ($data['items'] as $item) {
                $cantidad = intval($item['cantidad']);
                $precioUnitario = floatval($item['precio']);
                $subtotalItem = $cantidad * $precioUnitario;
                
                $stmtItem->execute([
                    ':cotizacion_id' => $cotizacionId,
                    ':inventario_id' => intval($item['id']),
                    ':cantidad' => $cantidad,
                    ':precio_unitario' => $precioUnitario,
                    ':subtotal' => $subtotalItem
                ]);
            }
            
            $pdo->commit();
            
            echo json_encode([
                "success" => true,
                "message" => "Cotización creada exitosamente",
                "id" => $cotizacionId,
                "subtotal" => $subtotal,
                "iva" => $iva,
                "total" => $total
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            file_put_contents(__DIR__ . '/debug_log.txt', "Error en transacción: " . $e->getMessage() . "\n", FILE_APPEND);
            throw $e;
        }
    } else {
        // Incluir el archivo original para otros métodos
        include 'cotizaciones.php';
    }
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $errorMsg = "Error en la base de datos: " . $e->getMessage();
    file_put_contents(__DIR__ . '/debug_log.txt', "$errorMsg\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $errorMsg
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $errorMsg = "Error del servidor: " . $e->getMessage();
    file_put_contents(__DIR__ . '/debug_log.txt', "$errorMsg\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $errorMsg
    ]);
}
?>
