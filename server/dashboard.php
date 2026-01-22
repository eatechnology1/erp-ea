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

try {
    // Obtener total de clientes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes");
    $totalClientes = $stmt->fetch()['total'];
    
    // Obtener total de productos en inventario
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inventario");
    $totalProductos = $stmt->fetch()['total'];
    
    // Obtener total de cotizaciones
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cotizaciones");
    $totalCotizaciones = $stmt->fetch()['total'];
    
    // Obtener las 5 cotizaciones más recientes con información del cliente
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.fecha,
            c.total,
            c.estado,
            cl.razon_social as cliente_nombre,
            cl.nit_cedula as cliente_nit
        FROM cotizaciones c
        INNER JOIN clientes cl ON c.cliente_id = cl.id
        ORDER BY c.fecha DESC
        LIMIT 5
    ");
    $stmt->execute();
    $ultimasCotizaciones = $stmt->fetchAll();
    
    // Devolver respuesta JSON
    echo json_encode([
        "success" => true,
        "data" => [
            "total_clientes" => (int)$totalClientes,
            "total_productos" => (int)$totalProductos,
            "total_cotizaciones" => (int)$totalCotizaciones,
            "ultimas_cotizaciones" => $ultimasCotizaciones
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error en la base de datos: " . $e->getMessage()
    ]);
}
?>
