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
    // GET: Obtener todos los ítems del inventario (con búsqueda opcional)
    if ($method === 'GET') {
        // Verificar si hay un parámetro de búsqueda
        $busqueda = isset($_GET['q']) ? $_GET['q'] : '';
        
        if (!empty($busqueda)) {
            // Búsqueda con LIKE en código, nombre y descripción
            $stmt = $pdo->prepare("
                SELECT * FROM inventario 
                WHERE codigo LIKE :busqueda 
                   OR nombre LIKE :busqueda 
                   OR descripcion LIKE :busqueda
                ORDER BY id DESC
            ");
            $stmt->execute([':busqueda' => "%$busqueda%"]);
        } else {
            // Sin búsqueda, devolver todos
            $stmt = $pdo->prepare("SELECT * FROM inventario ORDER BY id DESC");
            $stmt->execute();
        }
        
        $items = $stmt->fetchAll();
        
        echo json_encode([
            "success" => true,
            "data" => $items,
            "count" => count($items)
        ]);
    }
    
    // POST: Crear un nuevo ítem de inventario
    elseif ($method === 'POST') {
        // Obtener los datos JSON del cuerpo de la solicitud
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        // Validar que se recibieron los datos necesarios
        if (!$data) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "No se recibieron datos válidos"
            ]);
            exit();
        }
        
        // Validar que el código no esté vacío
        if (empty($data['codigo'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El código es obligatorio"
            ]);
            exit();
        }
        
        // Verificar que el código no se repita
        $stmt = $pdo->prepare("SELECT id FROM inventario WHERE codigo = :codigo");
        $stmt->execute([':codigo' => $data['codigo']]);
        
        if ($stmt->fetch()) {
            http_response_code(409); // Conflict
            echo json_encode([
                "success" => false,
                "error" => "Ya existe un ítem con ese código"
            ]);
            exit();
        }
        
        // Preparar la consulta de inserción
        $stmt = $pdo->prepare("
            INSERT INTO inventario (codigo, nombre, descripcion, precio_base, tipo) 
            VALUES (:codigo, :nombre, :descripcion, :precio_base, :tipo)
        ");
        
        // Ejecutar la consulta con los datos recibidos
        $stmt->execute([
            ':codigo' => $data['codigo'],
            ':nombre' => $data['nombre'] ?? '',
            ':descripcion' => $data['descripcion'] ?? '',
            ':precio_base' => $data['precio_base'] ?? 0,
            ':tipo' => $data['tipo'] ?? 'producto'
        ]);
        
        // Obtener el ID del ítem recién creado
        $nuevoId = $pdo->lastInsertId();
        
        echo json_encode([
            "success" => true,
            "message" => "Ítem creado exitosamente",
            "id" => $nuevoId
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
