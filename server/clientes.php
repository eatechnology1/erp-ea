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
    
    // POST: Crear un nuevo cliente con validaciones estrictas
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
        $nit_cedula = isset($data['nit_cedula']) ? trim(strip_tags($data['nit_cedula'])) : '';
        $razon_social = isset($data['razon_social']) ? trim(strip_tags($data['razon_social'])) : '';
        $telefono = isset($data['telefono']) ? trim(strip_tags($data['telefono'])) : '';
        $email = isset($data['email']) ? trim(strip_tags($data['email'])) : '';
        $direccion = isset($data['direccion']) ? trim(strip_tags($data['direccion'])) : '';
        
        // VALIDACIÓN: NIT/Cédula es obligatorio
        if (empty($nit_cedula)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El NIT/Cédula es obligatorio"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Razón Social es obligatoria
        if (empty($razon_social)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "La Razón Social es obligatoria"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Longitud máxima del NIT (evitar overflow en BD)
        if (strlen($nit_cedula) > 20) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El NIT/Cédula no puede exceder 20 caracteres"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Longitud máxima de Razón Social
        if (strlen($razon_social) > 150) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "La Razón Social no puede exceder 150 caracteres"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Email debe ser válido si se proporciona
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El email proporcionado no es válido"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Verificar que el NIT/Cédula no exista ya (evitar duplicados)
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE nit_cedula = :nit_cedula");
        $stmt->execute([':nit_cedula' => $nit_cedula]);
        
        if ($stmt->fetch()) {
            http_response_code(409); // Conflict
            echo json_encode([
                "success" => false,
                "error" => "Ya existe un cliente con ese NIT/Cédula"
            ]);
            exit();
        }
        
        // INSERCIÓN: Todos los datos están validados y sanitizados
        $stmt = $pdo->prepare("
            INSERT INTO clientes (nit_cedula, razon_social, telefono, email, direccion) 
            VALUES (:nit_cedula, :razon_social, :telefono, :email, :direccion)
        ");
        
        $stmt->execute([
            ':nit_cedula' => $nit_cedula,
            ':razon_social' => $razon_social,
            ':telefono' => $telefono,
            ':email' => $email,
            ':direccion' => $direccion
        ]);
        
        // Obtener el ID del cliente recién creado
        $nuevoId = $pdo->lastInsertId();
        
        echo json_encode([
            "success" => true,
            "message" => "Cliente creado exitosamente",
            "id" => $nuevoId
        ]);
    }
    
    // PUT: Actualizar un cliente existente
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
                "error" => "El ID del cliente es obligatorio para actualizar"
            ]);
            exit();
        }
        
        $id = intval($data['id']);
        
        // VALIDACIÓN: Verificar que el cliente existe
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "error" => "Cliente no encontrado"
            ]);
            exit();
        }
        
        // SANITIZACIÓN: Limpiar todos los campos de entrada
        $nit_cedula = isset($data['nit_cedula']) ? trim(strip_tags($data['nit_cedula'])) : '';
        $razon_social = isset($data['razon_social']) ? trim(strip_tags($data['razon_social'])) : '';
        $telefono = isset($data['telefono']) ? trim(strip_tags($data['telefono'])) : '';
        $email = isset($data['email']) ? trim(strip_tags($data['email'])) : '';
        $direccion = isset($data['direccion']) ? trim(strip_tags($data['direccion'])) : '';
        
        // VALIDACIÓN: NIT/Cédula es obligatorio
        if (empty($nit_cedula)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El NIT/Cédula es obligatorio"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Razón Social es obligatoria
        if (empty($razon_social)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "La Razón Social es obligatoria"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Longitud máxima del NIT
        if (strlen($nit_cedula) > 20) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El NIT/Cédula no puede exceder 20 caracteres"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Longitud máxima de Razón Social
        if (strlen($razon_social) > 150) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "La Razón Social no puede exceder 150 caracteres"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Email debe ser válido si se proporciona
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "El email proporcionado no es válido"
            ]);
            exit();
        }
        
        // VALIDACIÓN: Verificar que el NIT/Cédula no exista en otro cliente
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE nit_cedula = :nit_cedula AND id != :id");
        $stmt->execute([':nit_cedula' => $nit_cedula, ':id' => $id]);
        
        if ($stmt->fetch()) {
            http_response_code(409); // Conflict
            echo json_encode([
                "success" => false,
                "error" => "Ya existe otro cliente con ese NIT/Cédula"
            ]);
            exit();
        }
        
        // ACTUALIZACIÓN: Todos los datos están validados y sanitizados
        $stmt = $pdo->prepare("
            UPDATE clientes 
            SET nit_cedula = :nit_cedula, 
                razon_social = :razon_social, 
                telefono = :telefono, 
                email = :email, 
                direccion = :direccion 
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':id' => $id,
            ':nit_cedula' => $nit_cedula,
            ':razon_social' => $razon_social,
            ':telefono' => $telefono,
            ':email' => $email,
            ':direccion' => $direccion
        ]);
        
        echo json_encode([
            "success" => true,
            "message" => "Cliente actualizado exitosamente"
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
