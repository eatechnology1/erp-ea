<?php
/**
 * API REST para Gestión de Proyectos y Presupuestos
 * Endpoints: GET (list/detail), POST (create), PUT (update), DELETE
 */


// Habilitar reporte de errores para depuración
ini_set('display_errors', 0); // No mostrar en HTML output
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Manejador de errores para devolver JSON siempre
function exception_handler($e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    exit;
}
set_exception_handler('exception_handler');

// Capturar errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && $error['type'] === E_ERROR) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Fatal Error: ' . $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
        exit;
    }
});

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once 'db.php';
} catch (Throwable $e) {
    throw new Exception("Error al conectar BD: " . $e->getMessage());
}


$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo);
            break;
        case 'POST':
            handlePost($pdo, $input);
            break;
        case 'PUT':
            handlePut($pdo, $input);
            break;
        case 'DELETE':
            handleDelete($pdo);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * GET - Listar proyectos o detalle de un proyecto
 */
function handleGet($pdo) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    
    if ($id) {
        // Detalle de proyecto específico
        getProyectoDetalle($pdo, $id);
    } else {
        // Listar todos los proyectos
        getProyectosList($pdo);
    }
}

/**
 * GET - Lista de proyectos con totales calculados
 */
function getProyectosList($pdo) {
    $sql = "SELECT 
                p.id,
                p.nombre,
                p.cliente_id,
                c.razon_social as cliente_nombre,
                p.fecha_inicio,
                p.fecha_fin,
                p.estado,
                p.valor_cierre,
                p.costo_total,
                p.iva_porcentaje,
                p.administracion_porcentaje,
                p.improvistos_porcentaje,
                (SELECT COUNT(*) FROM proyecto_items WHERE proyecto_id = p.id) as total_items,
                (SELECT COALESCE(SUM(cantidad * valor_unitario), 0) FROM proyecto_items WHERE proyecto_id = p.id) as subtotal_items,
                (SELECT COALESCE(SUM(costo_materiales), 0) FROM proyecto_items WHERE proyecto_id = p.id) as total_materiales,
                (SELECT COALESCE(SUM(mano_de_obra), 0) FROM proyecto_items WHERE proyecto_id = p.id) as total_mano_obra
            FROM proyectos p
            LEFT JOIN clientes c ON p.cliente_id = c.id
            ORDER BY p.fecha_inicio DESC, p.id DESC";
    
    $stmt = $pdo->query($sql);
    $proyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular métricas para cada proyecto
    foreach ($proyectos as &$proyecto) {
        $subtotal = floatval($proyecto['subtotal_items']);
        $costos = floatval($proyecto['total_materiales']) + floatval($proyecto['total_mano_obra']);
        
        $proyecto['utilidad'] = $subtotal - $costos;
        $proyecto['margen_porcentaje'] = $subtotal > 0 ? ($proyecto['utilidad'] / $subtotal) * 100 : 0;
        
        // Formatear números
        $proyecto['valor_cierre'] = floatval($proyecto['valor_cierre']);
        $proyecto['costo_total'] = floatval($proyecto['costo_total']);
        $proyecto['subtotal_items'] = $subtotal;
        $proyecto['total_materiales'] = floatval($proyecto['total_materiales']);
        $proyecto['total_mano_obra'] = floatval($proyecto['total_mano_obra']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $proyectos
    ]);
}

/**
 * GET - Detalle completo de un proyecto con items agrupados por categoría
 */
function getProyectoDetalle($pdo, $id) {
    // Obtener datos del proyecto
    $sql = "SELECT 
                p.*,
                c.razon_social as cliente_nombre,
                c.nit_cedula as cliente_nit
            FROM proyectos p
            LEFT JOIN clientes c ON p.cliente_id = c.id
            WHERE p.id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $proyecto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$proyecto) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Proyecto no encontrado']);
        return;
    }
    
    // Obtener items agrupados por categoría
    $sql = "SELECT 
                pi.*,
                pc.nombre as categoria_nombre,
                pc.color as categoria_color,
                pc.icono as categoria_icono
            FROM proyecto_items pi
            LEFT JOIN proyecto_categorias pc ON pi.categoria_id = pc.id
            WHERE pi.proyecto_id = :id
            ORDER BY pc.orden, pi.orden, pi.id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agrupar items por categoría
    $itemsPorCategoria = [];
    foreach ($items as $item) {
        $catId = $item['categoria_id'];
        if (!isset($itemsPorCategoria[$catId])) {
            $itemsPorCategoria[$catId] = [
                'id' => $catId,
                'nombre' => $item['categoria_nombre'],
                'color' => $item['categoria_color'],
                'icono' => $item['categoria_icono'],
                'items' => []
            ];
        }
        
        // Calcular total del item
        $item['valor_total'] = floatval($item['cantidad']) * floatval($item['valor_unitario']);
        $item['cantidad'] = floatval($item['cantidad']);
        $item['valor_unitario'] = floatval($item['valor_unitario']);
        $item['costo_materiales'] = floatval($item['costo_materiales']);
        $item['mano_de_obra'] = floatval($item['mano_de_obra']);
        
        $itemsPorCategoria[$catId]['items'][] = $item;
    }
    
    // Convertir a array indexado
    $proyecto['categorias'] = array_values($itemsPorCategoria);
    
    // Formatear números del proyecto
    $proyecto['valor_cierre'] = floatval($proyecto['valor_cierre']);
    $proyecto['costo_total'] = floatval($proyecto['costo_total']);
    $proyecto['iva_porcentaje'] = floatval($proyecto['iva_porcentaje']);
    $proyecto['administracion_porcentaje'] = floatval($proyecto['administracion_porcentaje']);
    $proyecto['improvistos_porcentaje'] = floatval($proyecto['improvistos_porcentaje']);
    
    echo json_encode([
        'success' => true,
        'data' => $proyecto
    ]);
}

/**
 * POST - Crear nuevo proyecto
 */
function handlePost($pdo, $input) {
    // Validaciones
    if (empty($input['nombre'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El nombre del proyecto es requerido']);
        return;
    }
    
    if (empty($input['cliente_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El cliente es requerido']);
        return;
    }
    
    if (empty($input['fecha_inicio'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'La fecha de inicio es requerida']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Insertar proyecto
        $sql = "INSERT INTO proyectos (
                    nombre, cliente_id, fecha_inicio, fecha_fin, estado,
                    valor_cierre, costo_total, iva_porcentaje,
                    administracion_porcentaje, improvistos_porcentaje
                ) VALUES (
                    :nombre, :cliente_id, :fecha_inicio, :fecha_fin, :estado,
                    :valor_cierre, :costo_total, :iva_porcentaje,
                    :administracion_porcentaje, :improvistos_porcentaje
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nombre' => trim($input['nombre']),
            'cliente_id' => intval($input['cliente_id']),
            'fecha_inicio' => $input['fecha_inicio'],
            'fecha_fin' => $input['fecha_fin'] ?? null,
            'estado' => $input['estado'] ?? 'activo',
            'valor_cierre' => $input['valor_cierre'] ?? 0,
            'costo_total' => $input['costo_total'] ?? 0,
            'iva_porcentaje' => $input['iva_porcentaje'] ?? 19,
            'administracion_porcentaje' => $input['administracion_porcentaje'] ?? 0,
            'improvistos_porcentaje' => $input['improvistos_porcentaje'] ?? 0
        ]);
        
        $proyectoId = $pdo->lastInsertId();
        
        // Insertar items si existen
        if (!empty($input['items']) && is_array($input['items'])) {
            insertItems($pdo, $proyectoId, $input['items']);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Proyecto creado exitosamente',
            'id' => $proyectoId
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * PUT - Actualizar proyecto existente
 */
function handlePut($pdo, $input) {
    if (empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID del proyecto es requerido']);
        return;
    }
    
    $id = intval($input['id']);
    
    try {
        $pdo->beginTransaction();
        
        // Actualizar proyecto
        $sql = "UPDATE proyectos SET
                    nombre = :nombre,
                    cliente_id = :cliente_id,
                    fecha_inicio = :fecha_inicio,
                    fecha_fin = :fecha_fin,
                    estado = :estado,
                    valor_cierre = :valor_cierre,
                    costo_total = :costo_total,
                    iva_porcentaje = :iva_porcentaje,
                    administracion_porcentaje = :administracion_porcentaje,
                    improvistos_porcentaje = :improvistos_porcentaje
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'nombre' => trim($input['nombre']),
            'cliente_id' => intval($input['cliente_id']),
            'fecha_inicio' => $input['fecha_inicio'],
            'fecha_fin' => $input['fecha_fin'] ?? null,
            'estado' => $input['estado'] ?? 'activo',
            'valor_cierre' => $input['valor_cierre'] ?? 0,
            'costo_total' => $input['costo_total'] ?? 0,
            'iva_porcentaje' => $input['iva_porcentaje'] ?? 19,
            'administracion_porcentaje' => $input['administracion_porcentaje'] ?? 0,
            'improvistos_porcentaje' => $input['improvistos_porcentaje'] ?? 0
        ]);
        
        // Actualizar items: eliminar todos y reinsertar
        if (isset($input['items']) && is_array($input['items'])) {
            $pdo->prepare("DELETE FROM proyecto_items WHERE proyecto_id = :id")->execute(['id' => $id]);
            insertItems($pdo, $id, $input['items']);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Proyecto actualizado exitosamente'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * DELETE - Eliminar proyecto
 */
function handleDelete($pdo) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID del proyecto es requerido']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM proyectos WHERE id = :id");
    $stmt->execute(['id' => $id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Proyecto eliminado exitosamente'
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Proyecto no encontrado']);
    }
}

/**
 * Función auxiliar para insertar items
 */
function insertItems($pdo, $proyectoId, $items) {
    $sql = "INSERT INTO proyecto_items (
                proyecto_id, categoria_id, descripcion, unidad,
                cantidad, valor_unitario, costo_materiales, mano_de_obra, orden
            ) VALUES (
                :proyecto_id, :categoria_id, :descripcion, :unidad,
                :cantidad, :valor_unitario, :costo_materiales, :mano_de_obra, :orden
            )";
    
    $stmt = $pdo->prepare($sql);
    
    foreach ($items as $index => $item) {
        $stmt->execute([
            'proyecto_id' => $proyectoId,
            'categoria_id' => intval($item['categoria_id']),
            'descripcion' => trim($item['descripcion']),
            'unidad' => $item['unidad'] ?? 'UN',
            'cantidad' => floatval($item['cantidad'] ?? 1),
            'valor_unitario' => floatval($item['valor_unitario'] ?? 0),
            'costo_materiales' => floatval($item['costo_materiales'] ?? 0),
            'mano_de_obra' => floatval($item['mano_de_obra'] ?? 0),
            'orden' => $index
        ]);
    }
}
