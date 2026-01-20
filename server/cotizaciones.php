<?php
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

try {
    // GET: Obtener historial de cotizaciones con información del cliente
    if ($method === 'GET') {
        $stmt = $pdo->prepare("
            SELECT 
                c.id,
                c.cliente_id,
                c.fecha,
                c.subtotal,
                c.iva,
                c.total,
                c.estado,
                cl.razon_social as cliente_nombre,
                cl.nit_cedula as cliente_nit
            FROM cotizaciones c
            INNER JOIN clientes cl ON c.cliente_id = cl.id
            ORDER BY c.id DESC
        ");
        $stmt->execute();
        $cotizaciones = $stmt->fetchAll();
        
        echo json_encode([
            "success" => true,
            "data" => $cotizaciones,
            "count" => count($cotizaciones)
        ]);
    }
    
    // POST: Crear una nueva cotización con sus ítems (transacción)
    elseif ($method === 'POST') {
        // Obtener los datos JSON del cuerpo de la solicitud
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        // Validar que se recibieron los datos necesarios
        if (!$data || !isset($data['cliente_id']) || !isset($data['items'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "Datos incompletos. Se requiere cliente_id e items"
            ]);
            exit();
        }
        
        // Validar que hay ítems
        if (empty($data['items']) || !is_array($data['items'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "Debe incluir al menos un ítem en la cotización"
            ]);
            exit();
        }
        
        // Iniciar transacción
        $pdo->beginTransaction();
        
        try {
            // Calcular subtotal
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $cantidad = $item['cantidad'] ?? 1;
                $precio = $item['precio'] ?? 0;
                $subtotal += $cantidad * $precio;
            }
            
            // Calcular IVA (19%) y total
            $iva = $subtotal * 0.19;
            $total = $subtotal + $iva;
            
            // Insertar la cotización principal
            $stmt = $pdo->prepare("
                INSERT INTO cotizaciones (cliente_id, fecha, subtotal, iva, total, estado) 
                VALUES (:cliente_id, NOW(), :subtotal, :iva, :total, 'pendiente')
            ");
            
            $stmt->execute([
                ':cliente_id' => $data['cliente_id'],
                ':subtotal' => $subtotal,
                ':iva' => $iva,
                ':total' => $total
            ]);
            
            // Obtener el ID de la cotización recién creada
            $cotizacionId = $pdo->lastInsertId();
            
            // Insertar cada ítem de la cotización
            $stmtItem = $pdo->prepare("
                INSERT INTO cotizacion_items 
                (cotizacion_id, inventario_id, cantidad, precio_unitario, subtotal) 
                VALUES (:cotizacion_id, :inventario_id, :cantidad, :precio_unitario, :subtotal)
            ");
            
            foreach ($data['items'] as $item) {
                $cantidad = $item['cantidad'] ?? 1;
                $precioUnitario = $item['precio'] ?? 0;
                $subtotalItem = $cantidad * $precioUnitario;
                
                $stmtItem->execute([
                    ':cotizacion_id' => $cotizacionId,
                    ':inventario_id' => $item['id'],
                    ':cantidad' => $cantidad,
                    ':precio_unitario' => $precioUnitario,
                    ':subtotal' => $subtotalItem
                ]);
            }
            
            // Confirmar la transacción
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
            // Si algo falla, revertir todos los cambios
            $pdo->rollBack();
            throw $e;
        }
    }
    
    // Método no permitido
    else {
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "error" => "Método no permitido"
        ]);
    }
    
} catch (PDOException $e) {
    // Asegurarse de revertir si hay una transacción activa
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error en la base de datos: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error del servidor: " . $e->getMessage()
    ]);
}
?>
