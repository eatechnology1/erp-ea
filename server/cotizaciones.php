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
    // GET: Obtener historial de cotizaciones o detalle de una cotización específica
    if ($method === 'GET') {
        // Verificar si se solicita el detalle de una cotización específica
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $cotizacionId = intval($_GET['id']);
            
            // CONSULTA 1: Obtener la cabecera de la cotización con datos del cliente
            $stmt = $pdo->prepare("
                SELECT 
                    c.id,
                    c.cliente_id,
                    c.fecha,
                    c.subtotal,
                    c.iva,
                    c.total,
                    c.estado,
                    c.tipo_impuesto,
                    c.aiu_administracion,
                    c.aiu_imprevistos,
                    c.aiu_utilidad,
                    cl.razon_social as cliente_nombre,
                    cl.nit_cedula as cliente_nit,
                    cl.telefono as cliente_telefono,
                    cl.email as cliente_email,
                    cl.direccion as cliente_direccion
                FROM cotizaciones c
                INNER JOIN clientes cl ON c.cliente_id = cl.id
                WHERE c.id = :id
            ");
            $stmt->execute([':id' => $cotizacionId]);
            $cotizacion = $stmt->fetch();
            
            // Validar que existe la cotización
            if (!$cotizacion) {
                http_response_code(404);
                echo json_encode([
                    "success" => false,
                    "error" => "Cotización no encontrada"
                ]);
                exit();
            }
            
            // CONSULTA 2: Obtener los ítems de la cotización con datos del inventario
            $stmt = $pdo->prepare("
                SELECT 
                    ci.id,
                    ci.inventario_id,
                    ci.cantidad,
                    ci.precio_unitario,
                    ci.subtotal,
                    i.codigo,
                    i.nombre,
                    i.descripcion,
                    i.tipo
                FROM cotizacion_items ci
                INNER JOIN inventario i ON ci.inventario_id = i.id
                WHERE ci.cotizacion_id = :cotizacion_id
                ORDER BY ci.id ASC
            ");
            $stmt->execute([':cotizacion_id' => $cotizacionId]);
            $items = $stmt->fetchAll();
            
            // Devolver estructura completa
            echo json_encode([
                "success" => true,
                "cotizacion" => $cotizacion,
                "items" => $items
            ]);
            
        } else {
            // Sin ID: Listar todas las cotizaciones (historial)
            $stmt = $pdo->prepare("
                SELECT 
                    c.id,
                    c.cliente_id,
                    c.fecha,
                    c.subtotal,
                    c.iva,
                    c.total,
                    c.estado,
                    c.tipo_impuesto,
                    c.aiu_administracion,
                    c.aiu_imprevistos,
                    c.aiu_utilidad,
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
    }
    
    // POST: Crear una nueva cotización con transacción y validaciones estrictas
    elseif ($method === 'POST') {
        // Obtener los datos JSON del cuerpo de la solicitud
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        // VALIDACIÓN: Verificar que se recibieron datos válidos
        if (!$data) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "No se recibieron datos válidos o el JSON está malformado"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Verificar que existe cliente_id
        if (!isset($data['cliente_id'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El campo cliente_id es obligatorio"
            ]);
            exit();
        }
        
        // VALIDACIÓN: cliente_id debe ser numérico
        if (!is_numeric($data['cliente_id'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El cliente_id debe ser un valor numérico"
            ]);
            exit();
        }
        
        $cliente_id = intval($data['cliente_id']);
        
        // VALIDACIÓN: Verificar que existe el array items
        if (!isset($data['items']) || !is_array($data['items'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El campo items debe ser un array"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Verificar que hay al menos un ítem
        if (empty($data['items'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "Debe incluir al menos un ítem en la cotización"
            ]);
            exit();
        }
        
        // VALIDACIÓN CRÍTICA: Validar TODOS los ítems ANTES de iniciar la transacción
        foreach ($data['items'] as $index => $item) {
            // Validar que existe el ID del ítem
            if (!isset($item['id']) || !is_numeric($item['id'])) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "error" => "El ítem en la posición $index no tiene un ID válido"
                ]);
                exit();
            }
            
            // Validar que existe la cantidad
            if (!isset($item['cantidad']) || !is_numeric($item['cantidad'])) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "error" => "El ítem en la posición $index no tiene una cantidad válida"
                ]);
                exit();
            }
            
            $cantidad = intval($item['cantidad']);
            
            // VALIDACIÓN: Cantidad debe ser mayor a 0
            if ($cantidad <= 0) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "error" => "La cantidad del ítem en la posición $index debe ser mayor a 0"
                ]);
                exit();
            }
            
            // Validar que existe el precio
            if (!isset($item['precio']) || !is_numeric($item['precio'])) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "error" => "El ítem en la posición $index no tiene un precio válido"
                ]);
                exit();
            }
            
            $precio = floatval($item['precio']);
            
            // VALIDACIÓN: Precio debe ser mayor o igual a 0
            if ($precio < 0) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "error" => "El precio del ítem en la posición $index debe ser mayor o igual a 0"
                ]);
                exit();
            }
        }
        
        // TODAS LAS VALIDACIONES PASARON - Iniciar transacción
        $pdo->beginTransaction();
        
        try {
            // Obtener tipo de impuesto y porcentajes AIU
            $tipo_impuesto = isset($data['tipo_impuesto']) ? $data['tipo_impuesto'] : 'IVA';
            $aiu_admin = isset($data['aiu_administracion']) ? floatval($data['aiu_administracion']) : 10.00;
            $aiu_imp = isset($data['aiu_imprevistos']) ? floatval($data['aiu_imprevistos']) : 5.00;
            $aiu_util = isset($data['aiu_utilidad']) ? floatval($data['aiu_utilidad']) : 5.00;
            
            // Calcular subtotal validando cada operación
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $cantidad = intval($item['cantidad']);
                $precio = floatval($item['precio']);
                $subtotal += $cantidad * $precio;
            }
            
            // Calcular IVA o AIU según el tipo
            if ($tipo_impuesto === 'AIU') {
                // Calcular AIU
                $porcentaje_aiu = ($aiu_admin + $aiu_imp + $aiu_util) / 100;
                $iva = 0;
                $aiu = $subtotal * $porcentaje_aiu;
                $total = $subtotal + $aiu;
            } else {
                // Calcular IVA (por defecto)
                $iva = $subtotal * 0.19;
                $aiu = 0;
                $total = $subtotal + $iva;
            }
            
            // Insertar la cotización principal con tipo de impuesto y AIU
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
            
            $stmt->execute([
                ':cliente_id' => $cliente_id,
                ':subtotal' => $subtotal,
                ':iva' => $iva,
                ':total' => $total,
                ':tipo_impuesto' => $tipo_impuesto,
                ':aiu_admin' => $aiu_admin,
                ':aiu_imp' => $aiu_imp,
                ':aiu_util' => $aiu_util
            ]);
            
            // Obtener el ID de la cotización recién creada
            $cotizacionId = $pdo->lastInsertId();
            
            // Preparar statement para insertar ítems (optimización)
            $stmtItem = $pdo->prepare("
                INSERT INTO cotizacion_items 
                (cotizacion_id, inventario_id, cantidad, precio_unitario) 
                VALUES (:cotizacion_id, :inventario_id, :cantidad, :precio_unitario)
            ");
            
            // Insertar cada ítem de la cotización
            foreach ($data['items'] as $item) {
                $cantidad = intval($item['cantidad']);
                $precioUnitario = floatval($item['precio']);
                
                $stmtItem->execute([
                    ':cotizacion_id' => $cotizacionId,
                    ':inventario_id' => intval($item['id']),
                    ':cantidad' => $cantidad,
                    ':precio_unitario' => $precioUnitario
                ]);
            }
            
            // COMMIT: Todos los datos insertados correctamente
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
            // ROLLBACK: Si algo falla, revertir TODOS los cambios
            $pdo->rollBack();
            throw $e;
        }
    }
    
    // PUT: Actualizar el estado de una cotización
    elseif ($method === 'PUT') {
        // Obtener los datos JSON del cuerpo de la solicitud
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        // VALIDACIÓN: Verificar que se recibieron datos válidos
        if (!$data) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "No se recibieron datos válidos o el JSON está malformado"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Verificar que existe el ID
        if (!isset($data['id']) || !is_numeric($data['id'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El ID de la cotización es obligatorio y debe ser numérico"
            ]);
            exit();
        }
        
        $cotizacionId = intval($data['id']);
        
        // VALIDACIÓN: Verificar que existe el estado
        if (!isset($data['estado'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El estado es obligatorio"
            ]);
            exit();
        }
        
        $nuevoEstado = trim(strip_tags($data['estado']));
        
        // VALIDACIÓN ESTRICTA: Estados válidos permitidos
        $estadosValidos = ['borrador', 'enviada', 'aprobada', 'rechazada'];
        
        if (!in_array($nuevoEstado, $estadosValidos)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "Estado inválido. Los estados permitidos son: " . implode(', ', $estadosValidos)
            ]);
            exit();
        }
        
        // VALIDACIÓN: Verificar que la cotización existe
        $stmt = $pdo->prepare("SELECT id FROM cotizaciones WHERE id = :id");
        $stmt->execute([':id' => $cotizacionId]);
        
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "error" => "Cotización no encontrada"
            ]);
            exit();
        }
        
        // ACTUALIZACIÓN: Cambiar el estado
        $stmt = $pdo->prepare("
            UPDATE cotizaciones 
            SET estado = :estado 
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':estado' => $nuevoEstado,
            ':id' => $cotizacionId
        ]);
        
        echo json_encode([
            "success" => true,
            "message" => "Estado actualizado exitosamente",
            "id" => $cotizacionId,
            "nuevo_estado" => $nuevoEstado
        ]);
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
    // Asegurarse de revertir si hay una transacción activa
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error del servidor: " . $e->getMessage()
    ]);
}
?>
