<?php
// Incluir la configuración de la base de datos y la biblioteca SimpleXLSX
require_once 'db.php';
require_once 'SimpleXLSX.php';

// Configurar headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// Responder a las solicitudes de "preflight"
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "error" => "Método no permitido. Use POST."
    ]);
    exit();
}

try {
    // Verificar que se haya subido un archivo
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "No se recibió ningún archivo o hubo un error en la carga"
        ]);
        exit();
    }
    
    $file = $_FILES['file'];
    
    // Verificar que sea un archivo Excel
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, ['xlsx', 'xls'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "El archivo debe ser de tipo Excel (.xlsx o .xls)"
        ]);
        exit();
    }
    
    // Leer el archivo Excel
    $xlsx = Shuchkin\SimpleXLSX::parse($file['tmp_name']);
    
    if (!$xlsx) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "error" => "No se pudo leer el archivo Excel: " . Shuchkin\SimpleXLSX::parseError()
        ]);
        exit();
    }
    
    // Obtener todas las filas
    $rows = $xlsx->rows();
    
    if (empty($rows)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "El archivo está vacío"
        ]);
        exit();
    }
    
    // La primera fila son los encabezados
    $headers = array_shift($rows);
    
    // Limpiar encabezados (remover formato HTML si existe)
    $headers = array_map(function($header) {
        return strip_tags(trim($header));
    }, $headers);
    
    // Validar encabezados esperados
    $expectedHeaders = ['Código', 'Nombre', 'Descripción', 'Precio Base', 'Tipo'];
    
    // Comparación flexible (ignorar mayúsculas/minúsculas y acentos)
    $headersNormalized = array_map('strtolower', $headers);
    $expectedNormalized = array_map('strtolower', $expectedHeaders);
    
    if ($headersNormalized !== $expectedNormalized) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Los encabezados del archivo no coinciden con la plantilla. Esperados: " . implode(', ', $expectedHeaders) . ". Encontrados: " . implode(', ', $headers)
        ]);
        exit();
    }
    
    // Arrays para resultados
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    $lineNumber = 1; // Empezamos en 1 (la fila 1 son los encabezados, datos empiezan en fila 2)
    
    // Procesar cada fila
    foreach ($rows as $row) {
        $lineNumber++;
        
        // Saltar filas vacías
        if (empty(array_filter($row))) {
            continue;
        }
        
        // Asignar valores a variables
        $codigo = isset($row[0]) ? trim($row[0]) : '';
        $nombre = isset($row[1]) ? trim($row[1]) : '';
        $descripcion = isset($row[2]) ? trim($row[2]) : '';
        $precio_base = isset($row[3]) ? trim($row[3]) : '';
        $tipo = isset($row[4]) ? trim(strtolower($row[4])) : '';
        
        // VALIDACIONES
        $rowErrors = [];
        
        // Validar código
        if (empty($codigo)) {
            $rowErrors[] = "El código es obligatorio";
        }
        
        // Validar nombre
        if (empty($nombre)) {
            $rowErrors[] = "El nombre es obligatorio";
        }
        
        // Validar precio_base
        if (empty($precio_base) && $precio_base !== '0') {
            $rowErrors[] = "El precio base es obligatorio";
        } elseif (!is_numeric($precio_base)) {
            $rowErrors[] = "El precio base debe ser un valor numérico";
        } elseif (floatval($precio_base) < 0) {
            $rowErrors[] = "El precio base debe ser mayor o igual a 0";
        }
        
        // Validar tipo
        if (empty($tipo)) {
            $rowErrors[] = "El tipo es obligatorio";
        } elseif (!in_array($tipo, ['producto', 'servicio'])) {
            $rowErrors[] = "El tipo debe ser 'producto' o 'servicio'";
        }
        
        // Si hay errores de validación, registrarlos y continuar
        if (!empty($rowErrors)) {
            $errorCount++;
            $errors[] = [
                "line" => $lineNumber,
                "codigo" => $codigo,
                "errors" => $rowErrors
            ];
            continue;
        }
        
        // Verificar si el código ya existe en la base de datos
        $stmt = $pdo->prepare("SELECT id FROM inventario WHERE codigo = :codigo");
        $stmt->execute([':codigo' => $codigo]);
        
        if ($stmt->fetch()) {
            $errorCount++;
            $errors[] = [
                "line" => $lineNumber,
                "codigo" => $codigo,
                "errors" => ["Ya existe un ítem con ese código"]
            ];
            continue;
        }
        
        // Intentar insertar el ítem
        try {
            $stmt = $pdo->prepare("
                INSERT INTO inventario (codigo, nombre, descripcion, precio_base, tipo) 
                VALUES (:codigo, :nombre, :descripcion, :precio_base, :tipo)
            ");
            
            $stmt->execute([
                ':codigo' => $codigo,
                ':nombre' => $nombre,
                ':descripcion' => $descripcion,
                ':precio_base' => floatval($precio_base),
                ':tipo' => $tipo
            ]);
            
            $successCount++;
            
        } catch (PDOException $e) {
            $errorCount++;
            $errors[] = [
                "line" => $lineNumber,
                "codigo" => $codigo,
                "errors" => ["Error al insertar: " . $e->getMessage()]
            ];
        }
    }
    
    // Verificar si se procesó al menos una fila
    if ($successCount === 0 && $errorCount === 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "El archivo no contiene datos para importar"
        ]);
        exit();
    }
    
    // Retornar resultados
    echo json_encode([
        "success" => true,
        "message" => "Importación completada",
        "results" => [
            "total" => $successCount + $errorCount,
            "success" => $successCount,
            "errors" => $errorCount,
            "error_details" => $errors
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error en el servidor: " . $e->getMessage()
    ]);
}
?>
