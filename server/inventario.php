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
    // GET: Obtener ítems del inventario con paginación del servidor
    if ($method === 'GET') {
        // PARÁMETROS DE PAGINACIÓN
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? max(1, min(1000, intval($_GET['limit']))) : 10;
        $busqueda = isset($_GET['q']) ? trim(strip_tags($_GET['q'])) : '';
        
        // Calcular OFFSET
        $offset = ($page - 1) * $limit;
        
        // CONSULTA 1: Contar total de registros (con filtro si existe búsqueda)
        if (!empty($busqueda)) {
            $stmtCount = $pdo->prepare("
                SELECT COUNT(*) as total 
                FROM inventario 
                WHERE codigo LIKE :busqueda 
                   OR nombre LIKE :busqueda 
                   OR descripcion LIKE :busqueda
            ");
            $stmtCount->execute([':busqueda' => "%$busqueda%"]);
        } else {
            $stmtCount = $pdo->query("SELECT COUNT(*) as total FROM inventario");
        }
        
        $totalRows = $stmtCount->fetch()['total'];
        
        // CONSULTA 2: Obtener datos paginados
        if (!empty($busqueda)) {
            $stmt = $pdo->prepare("
                SELECT * FROM inventario 
                WHERE codigo LIKE :busqueda 
                   OR nombre LIKE :busqueda 
                   OR descripcion LIKE :busqueda
                ORDER BY id DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':busqueda', "%$busqueda%", PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $pdo->prepare("
                SELECT * FROM inventario 
                ORDER BY id DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        $items = $stmt->fetchAll();
        
        // Devolver respuesta paginada
        echo json_encode([
            "success" => true,
            "data" => $items,
            "meta" => [
                "total_rows" => (int)$totalRows,
                "page" => $page,
                "limit" => $limit,
                "total_pages" => ceil($totalRows / $limit)
            ]
        ]);
    }
    
    // POST: Crear un nuevo ítem de inventario con validaciones estrictas
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
        
        // SANITIZACIÓN: Limpiar todos los campos de entrada
        $codigo = isset($data['codigo']) ? trim(strip_tags($data['codigo'])) : '';
        $nombre = isset($data['nombre']) ? trim(strip_tags($data['nombre'])) : '';
        $descripcion = isset($data['descripcion']) ? trim(strip_tags($data['descripcion'])) : '';
        $tipo = isset($data['tipo']) ? trim(strip_tags($data['tipo'])) : 'producto';
        
        // VALIDACIÓN: Código es obligatorio y no puede estar vacío
        if (empty($codigo)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El código es obligatorio y no puede estar vacío"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Nombre es obligatorio
        if (empty($nombre)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El nombre es obligatorio"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Precio base debe ser numérico
        if (!isset($data['precio_base']) || !is_numeric($data['precio_base'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El precio base debe ser un valor numérico"
            ]);
            exit();
        }
        
        $precio_base = floatval($data['precio_base']);
        
        // VALIDACIÓN: Precio base debe ser mayor o igual a 0
        if ($precio_base < 0) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El precio base debe ser mayor o igual a 0"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Tipo debe ser 'producto' o 'servicio'
        if (!in_array($tipo, ['producto', 'servicio'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El tipo debe ser 'producto' o 'servicio'"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Verificar que el código no se repita (índice UNIQUE en BD)
        $stmt = $pdo->prepare("SELECT id FROM inventario WHERE codigo = :codigo");
        $stmt->execute([':codigo' => $codigo]);
        
        if ($stmt->fetch()) {
            http_response_code(409); // Conflict
            echo json_encode([
                "success" => false,
                "error" => "Ya existe un ítem con ese código"
            ]);
            exit();
        }
        
        // INSERCIÓN: Todos los datos están validados y sanitizados
        // Optimizado para escalar a millones de registros (usa prepared statements)
        $stmt = $pdo->prepare("
            INSERT INTO inventario (codigo, nombre, descripcion, precio_base, tipo) 
            VALUES (:codigo, :nombre, :descripcion, :precio_base, :tipo)
        ");
        
        $stmt->execute([
            ':codigo' => $codigo,
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
            ':precio_base' => $precio_base,
            ':tipo' => $tipo
        ]);
        
        // Obtener el ID del ítem recién creado
        $nuevoId = $pdo->lastInsertId();
        
        echo json_encode([
            "success" => true,
            "message" => "Ítem creado exitosamente",
            "id" => $nuevoId
        ]);
    }
    
    // PUT: Actualizar un ítem de inventario existente
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
        
        // VALIDACIÓN: El ID es obligatorio para actualizar
        if (!isset($data['id']) || empty($data['id'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El ID del ítem es obligatorio para actualizar"
            ]);
            exit();
        }
        
        $id = intval($data['id']);
        
        // VALIDACIÓN: Verificar que el ítem existe
        $stmt = $pdo->prepare("SELECT id FROM inventario WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "error" => "Ítem no encontrado"
            ]);
            exit();
        }
        
        // SANITIZACIÓN: Limpiar todos los campos de entrada
        $codigo = isset($data['codigo']) ? trim(strip_tags($data['codigo'])) : '';
        $nombre = isset($data['nombre']) ? trim(strip_tags($data['nombre'])) : '';
        $descripcion = isset($data['descripcion']) ? trim(strip_tags($data['descripcion'])) : '';
        $tipo = isset($data['tipo']) ? trim(strip_tags($data['tipo'])) : 'producto';
        
        // VALIDACIÓN: Código es obligatorio
        if (empty($codigo)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El código es obligatorio y no puede estar vacío"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Nombre es obligatorio
        if (empty($nombre)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El nombre es obligatorio"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Precio base debe ser numérico
        if (!isset($data['precio_base']) || !is_numeric($data['precio_base'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El precio base debe ser un valor numérico"
            ]);
            exit();
        }
        
        $precio_base = floatval($data['precio_base']);
        
        // VALIDACIÓN: Precio base debe ser mayor o igual a 0
        if ($precio_base < 0) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El precio base debe ser mayor o igual a 0"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Tipo debe ser 'producto' o 'servicio'
        if (!in_array($tipo, ['producto', 'servicio'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El tipo debe ser 'producto' o 'servicio'"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Verificar que el código no exista en otro ítem
        $stmt = $pdo->prepare("SELECT id FROM inventario WHERE codigo = :codigo AND id != :id");
        $stmt->execute([':codigo' => $codigo, ':id' => $id]);
        
        if ($stmt->fetch()) {
            http_response_code(409); // Conflict
            echo json_encode([
                "success" => false,
                "error" => "Ya existe otro ítem con ese código"
            ]);
            exit();
        }
        
        // ACTUALIZACIÓN: Todos los datos están validados y sanitizados
        $stmt = $pdo->prepare("
            UPDATE inventario 
            SET codigo = :codigo, 
                nombre = :nombre, 
                descripcion = :descripcion, 
                precio_base = :precio_base, 
                tipo = :tipo 
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':id' => $id,
            ':codigo' => $codigo,
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
            ':precio_base' => $precio_base,
            ':tipo' => $tipo
        ]);
        
        echo json_encode([
            "success" => true,
            "message" => "Ítem actualizado exitosamente"
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
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error en la base de datos: " . $e->getMessage()
    ]);
}
?>
