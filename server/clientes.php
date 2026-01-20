<?php
// Incluir la configuración de la base de datos
require_once 'db.php';

// Configurar headers CORS (ya están en db.php, pero los reforzamos aquí)
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
    // GET: Obtener todos los clientes
    if ($method === 'GET') {
        $stmt = $pdo->prepare("SELECT * FROM clientes ORDER BY id DESC");
        $stmt->execute();
        $clientes = $stmt->fetchAll();
        
        echo json_encode([
            "success" => true,
            "data" => $clientes
        ]);
    }
    
    // POST: Crear un nuevo cliente
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
        
        // Preparar la consulta de inserción
        $stmt = $pdo->prepare("
            INSERT INTO clientes (nit_cedula, razon_social, telefono, email, direccion) 
            VALUES (:nit_cedula, :razon_social, :telefono, :email, :direccion)
        ");
        
        // Ejecutar la consulta con los datos recibidos
        $stmt->execute([
            ':nit_cedula' => $data['nit_cedula'] ?? '',
            ':razon_social' => $data['razon_social'] ?? '',
            ':telefono' => $data['telefono'] ?? '',
            ':email' => $data['email'] ?? '',
            ':direccion' => $data['direccion'] ?? ''
        ]);
        
        // Obtener el ID del cliente recién creado
        $nuevoId = $pdo->lastInsertId();
        
        echo json_encode([
            "success" => true,
            "message" => "Cliente creado exitosamente",
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
