<?php
/**
 * Script de Configuración y Datos de Prueba
 * 1. Crea las tablas si no existen.
 * 2. Inserta datos de prueba para visualizar el módulo.
 */

header('Content-Type: text/html; charset=UTF-8');
require_once 'db.php';

echo "<h1>Configuración de Proyectos y Presupuestos</h1>";

try {
    $pdo->beginTransaction();

    // 1. CREACIÓN DE TABLAS (SCHEMA)
    // ==========================================
    echo "<h3>1. Verificando Esquema de Base de Datos...</h3>";

    $sqlTables = "
    -- Tabla de Proyectos
    CREATE TABLE IF NOT EXISTS proyectos (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nombre VARCHAR(255) NOT NULL,
      cliente_id INT NOT NULL,
      fecha_inicio DATE NOT NULL,
      fecha_fin DATE NULL,
      estado ENUM('activo', 'finalizado', 'cancelado') DEFAULT 'activo',
      valor_cierre DECIMAL(15, 2) DEFAULT 0.00,
      costo_total DECIMAL(15, 2) DEFAULT 0.00,
      iva_porcentaje DECIMAL(5, 2) DEFAULT 19.00,
      administracion_porcentaje DECIMAL(5, 2) DEFAULT 0.00,
      improvistos_porcentaje DECIMAL(5, 2) DEFAULT 0.00,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_cliente (cliente_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Tabla de Categorías
    CREATE TABLE IF NOT EXISTS proyecto_categorias (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nombre VARCHAR(100) NOT NULL UNIQUE,
      descripcion TEXT NULL,
      color VARCHAR(7) DEFAULT '#00e5ff',
      icono VARCHAR(50) DEFAULT 'category',
      orden INT DEFAULT 0,
      activo BOOLEAN DEFAULT TRUE,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Tabla de Items
    CREATE TABLE IF NOT EXISTS proyecto_items (
      id INT AUTO_INCREMENT PRIMARY KEY,
      proyecto_id INT NOT NULL,
      categoria_id INT NOT NULL,
      descripcion TEXT NOT NULL,
      unidad VARCHAR(50) DEFAULT 'UN',
      cantidad DECIMAL(10, 2) NOT NULL DEFAULT 1.00,
      valor_unitario DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
      costo_materiales DECIMAL(15, 2) DEFAULT 0.00,
      mano_de_obra DECIMAL(15, 2) DEFAULT 0.00,
      orden INT DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE,
      FOREIGN KEY (categoria_id) REFERENCES proyecto_categorias(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sqlTables);
    echo "<p style='color:green'>✅ Tablas verificadas/creadas correctamente.</p>";

    // 2. INSERTAR CATEGORÍAS POR DEFECTO
    // ==========================================
    echo "<h3>2. Insertando Categorías...</h3>";
    $categorias = [
        ['Eléctrico', 'Instalaciones eléctricas', '#00e5ff', 'electric_bolt', 1],
        ['Domótica', 'Automatización y control', '#10b981', 'home_automation', 2],
        ['Iluminación', 'Diseño de iluminación', '#f59e0b', 'lightbulb', 3],
        ['Sonido', 'Audio profesional', '#8b5cf6', 'volume_up', 4],
        ['Redes', 'Infraestructura TI', '#3b82f6', 'router', 5],
        ['Seguridad', 'CCTV y Alarmas', '#ef4444', 'security', 6]
    ];

    $stmtCat = $pdo->prepare("INSERT INTO proyecto_categorias (nombre, descripcion, color, icono, orden) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE descripcion=VALUES(descripcion)");
    
    foreach ($categorias as $cat) {
        $stmtCat->execute($cat);
    }
    echo "<p style='color:green'>✅ Categorías insertadas.</p>";

    // 3. OBTENER O CREAR CLIENTE DE PRUEBA
    // ==========================================
    $stmtCliente = $pdo->query("SELECT id FROM clientes LIMIT 1");
    $clienteId = $stmtCliente->fetchColumn();

    if (!$clienteId) {
        echo "<p>⚠️ No se encontró cliente. Creando uno de prueba...</p>";
        $pdo->exec("INSERT INTO clientes (razon_social, nit_cedula, email, telefono, direccion) VALUES ('Cliente Demo SAS', '900123456', 'contacto@demo.com', '3001234567', 'Calle Falsa 123')");
        $clienteId = $pdo->lastInsertId();
    }
    echo "<p style='color:green'>✅ Cliente asignado (ID: $clienteId).</p>";

    // 4. INSERTAR PROYECTO DE PRUEBA
    // ==========================================
    echo "<h3>3. Creando Proyecto Demo...</h3>";
    
    // Verificar si ya existe para no duplicar
    $stmtCheck = $pdo->prepare("SELECT id FROM proyectos WHERE nombre = ?");
    $stmtCheck->execute(['Residencia Inteligente Model X']);
    
    if (!$stmtCheck->fetch()) {
        $stmtProyecto = $pdo->prepare("INSERT INTO proyectos (nombre, cliente_id, fecha_inicio, estado, valor_cierre, costo_total) VALUES (?, ?, CURDATE(), 'activo', 85000000, 55000000)");
        $stmtProyecto->execute(['Residencia Inteligente Model X', $clienteId]);
        $proyectoId = $pdo->lastInsertId();

        // Items para el proyecto
        // Obtener IDs de categorías
        $catStmt = $pdo->prepare("SELECT id FROM proyecto_categorias WHERE nombre = ?");
        
        // Items Eléctricos
        $catStmt->execute(['Eléctrico']);
        $catElectrico = $catStmt->fetchColumn();
        
        // Items Domótica
        $catStmt->execute(['Domótica']);
        $catDomotica = $catStmt->fetchColumn();

        $sqlItem = "INSERT INTO proyecto_items (proyecto_id, categoria_id, descripcion, unidad, cantidad, valor_unitario, costo_materiales, mano_de_obra) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtItem = $pdo->prepare($sqlItem);

        // Insertar items
        $stmtItem->execute([$proyectoId, $catElectrico, 'Cableado Estructurado Cat 6A', 'Mts', 500, 8500, 4500, 2000]);
        $stmtItem->execute([$proyectoId, $catElectrico, 'Tablero de Circuitos Inteligente', 'UN', 2, 4500000, 2800000, 500000]);
        $stmtItem->execute([$proyectoId, $catDomotica, 'Controlador Central HomeBrain', 'UN', 1, 12000000, 8000000, 1500000]);
        $stmtItem->execute([$proyectoId, $catDomotica, 'Sensores de Presencia IoT', 'Kits', 10, 450000, 250000, 80000]);

        echo "<p style='color:green'>✅ Proyecto 'Residencia Inteligente' creado con items.</p>";
    } else {
        echo "<p style='color:blue'>ℹ️ El proyecto demo ya existe.</p>";
    }

    $pdo->commit();
    echo "<h2>🎉 ¡TODO LISTO!</h2>";
    echo "<p>Ahora puedes volver a la aplicación y recargar la página de Proyectos.</p>";
    echo "<a href='../' style='padding:10px 20px; background:#00e5ff; color:black; text-decoration:none; border-radius:5px; font-weight:bold;'>Volver al ERP</a>";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<h2 style='color:red'>❌ ERROR CRÍTICO</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
