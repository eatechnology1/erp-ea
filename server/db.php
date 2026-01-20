<?php
// Permitir acceso desde cualquier origen (CORS) para desarrollo
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

// Responder a las solicitudes de "preflight" (cuando el navegador pregunta si puede conectar)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}


$host = 'localhost'; // En Hostinger siempre es localhost
$db   = 'u800651600_erp';
$user = 'u800651600_ea_tech';
$pass = 'EA.Tech.2025'; // Tu contraseña de base de datos
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Si quieres probar que conecta, descomenta la línea de abajo temporalmente:
    //echo json_encode(["status" => "Conexión exitosa a la BD"]);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error de conexión: " . $e->getMessage()]);
    exit();
}
?>  